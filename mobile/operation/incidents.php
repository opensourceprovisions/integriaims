<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Incidents {
	
	private $id_incident;
	private $offset;
	private $operation;
	private $filter_search;
	private $filter_status;
	private $filter_owner;
	
	private $acl = 'IR';
	private $permission = false;
	
	function __construct () {
		$system = System::getInstance();
		
		$this->id_incident = (int) $system->getRequest('id_incident', -1);
		$this->offset = (int) $system->getRequest('offset', 1);
		$this->operation = (string) $system->getRequest('operation', '');
		$this->filter_search = (string) $system->getRequest('filter_search', '');
		$this->filter_status = (int) $system->getRequest('filter_status', -10);
		$this->filter_owner = (string) $system->getRequest('filter_owner', '');
		
		// ACL
		$this->permission = $this->checkPermission ($system->getConfig('id_user'), $this->acl, $this->operation, $this->id_incident);
	}
	
	public function getPermission () {
		return $this->permission;
	}
	
	public function checkPermission ($id_user, $acl = 'IR', $operation = '', $id_incident = -1) {
		$system = System::getInstance();
		
		$permission = false;
		if (dame_admin($id_user)) {
			$permission = true;
		} else {
			if ($system->checkACL($this->acl)) {
				if ($id_incident > 0 && $operation == "delete") {
					$incident_creator = get_db_value ("id_creator", "tincidencia", "id_incidencia", $id_incident);
					if ($system->checkACL("IM") && strcasecmp($id_user, $incident_creator) == 0) {
						$permission = true;
					}
				} else {
					$permission = true;
				}
			}
		}
		
		return $permission;
	}
	
	private function getIncidentsQuery ($columns = "*", $order_by = "actualizacion DESC, prioridad DESC, titulo", $limit = true) {
		$system = System::getInstance();
		
		$filter = "";
		if ($this->filter_search != '') {
			$filter .= " AND (titulo LIKE '%".$this->filter_search."%'
								OR descripcion LIKE '%".$this->filter_search."%' 
								OR id_creator LIKE '%".$this->filter_search."%'
								OR id_usuario LIKE '%".$this->filter_search."%' 
								OR id_incidencia IN (SELECT id_incident
													 FROM tincident_field_data
													 WHERE data LIKE '%".$this->filter_search."%'))";
		}
		if ($this->filter_status != 0) {
			if ($this->filter_status == -10) {
				$filter .= " AND estado <> 7";
			} else {
				$filter .= " AND estado = ".$this->filter_status;
			}
		}
		if ($this->filter_owner != '') {
			$filter .= " AND id_usuario = '".$this->filter_owner."' ";
		}
		if (dame_admin($system->getConfig('id_user'))) {
			$sql = "SELECT $columns
					FROM tincidencia
					WHERE 1=1
					$filter";
		} else {
			$sql = "SELECT $columns
					FROM tincidencia
					WHERE (id_usuario = '".$system->getConfig('id_user')."'
						OR id_creator = '".$system->getConfig('id_user')."')
						$filter";
		}
		if ($order_by != "") {
			$sql .= " ORDER BY $order_by";
		}
		if ($limit) {
			$sql .= " LIMIT ".(int)(($this->offset -1) * $system->getPageSize()).", ".(int)$system->getPageSize();
		}
		
		return $sql;
	}
	
	public function getCountIncidents () {
		$sql = $this->getIncidentsQuery("COUNT(id_incidencia)", "", false);
		$count = get_db_sql($sql);
		
		return $count;
	}
	
	public function getNumPages () {
		$system = System::getInstance();
		
		$num_pages = ceil( $this->getCountIncidents() / $system->getPageSize() );
		return $num_pages;
	}
	
	public function getIncidentsList ($href = "", $ajax = false) {
		$system = System::getInstance();
		$ui = Ui::getInstance();

		if ($href == "") {
			$href = "index.php?page=incident";
		}

		$html = "";
		
		if (! $ajax) {
			$html .= "<ul id='listview' class='ui-itemlistview' data-role='listview'>";
		}
		if ($this->getCountIncidents() > 0) {
			$sql = $this->getIncidentsQuery();
			$new = true;
			while ( $incident = get_db_all_row_by_steps_sql($new, $result_query, $sql) ) {
				$new = false;
				// Background color
				if ($incident["estado"] < 3) {
					$background_color = "light-red-background";
				} elseif ($incident["estado"] < 7) {
					$background_color = "light-yellow-background";
				} elseif ($incident["estado"] == 7) {
					$background_color = "light-green-background";
				} else {
					$background_color = "";
				}
				$html .= "<li class=\"$background_color\">";
				$html .= "<a href='$href&id_incident=".$incident['id_incidencia']."' class='ui-link-inherit' data-ajax='false'>";
					//$html .= $ui->getPriorityFlagImage($incident['prioridad']);
					$html .= print_priority_flag_image ($incident['prioridad'], true, "../", "priority-list ui-li-icon");
					$html .= "<h3 class='ui-li-heading'>#".$incident['id_incidencia'];
					$html .= "&nbsp;&nbsp;-&nbsp;&nbsp;".$incident['titulo']."</h3>";
					$html .= "<p class='ui-li-desc'>".__('Owner').": ".$incident['id_usuario'];
					if ( include_once ($system->getConfig('homedir')."/include/functions_calendar.php") ) {
						$html .= "&nbsp;&nbsp;-&nbsp;&nbsp;".human_time_comparation($incident["actualizacion"])."&nbsp;".__('since the last update')."</p>";
					} else {
						$html .= "&nbsp;&nbsp;-&nbsp;&nbsp;".__('Last update').": ".$incident['actualizacion']."</p>";
					}
				$html .= "</a>";
				
				//~ $options = array(
					//~ 'popup_id' => 'delete_popup_'.$incident['id_incidencia'],
					//~ 'delete_href' => 'index.php?page=incidents&operation=delete&id_incident='.$incident['id_incidencia']
					//~ );
				//~ $html .= $ui->getDeletePopupHTML($options);
				//~ $html .= "<a data-icon=\"delete\" data-rel=\"popup\" href=\"#delete_popup_".$incident['id_incidencia']."\"></a>";
				$html .= "</li>";
			}
		} else {
			$html .= "<li>";
			$html .= "<h3 class='error'>".__('There is no tickets')."</h3>";
			$html .= "</li>";
		}
		if (! $ajax) {
			$html .= "</ul>";
		} else {
			ob_clean();
		}
		
		return $html;
	}
	
	public function addIncidentsLoader ($href = "") {
		$ui = Ui::getInstance();
		
		$script = "<script type=\"text/javascript\">
						var load_more_rows = 1;
						var page = 2;
						$(document).ready(function() {
							$(window).bind(\"scroll\", function () {
								
								if (load_more_rows) {
									if ($(this).scrollTop() + $(this).height()
										>= ($(document).height() - 100)) {
										
										load_more_rows = 0;
										
										postvars = {};
										postvars[\"action\"] = \"ajax\";
										postvars[\"page\"] = \"incidents\";
										postvars[\"method\"] = \"load_more_incidents\";
										postvars[\"offset\"] = page;
										postvars[\"href\"] = \"$href\";
										postvars[\"filter_search\"] = \"".$this->filter_search."\";
										postvars[\"filter_owner\"] = \"".$this->filter_owner."\";
										postvars[\"filter_status\"] = ".$this->filter_status.";
										page++;
										
										$.post(\"index.php\",
											postvars,
											function (data) {
												if (data.length < 3) {
													$(\"#loading_rows\").hide();
												} else {
													$(\"#listview\").append(data).listview('refresh');
													load_more_rows = 1;
												}
											},
											\"html\");
									}
								}
							});
						});
					</script>";
		
		$ui->contentAddHtml($script);
	}
	
	private function showIncidents ($message = "") {
		
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		$back_href = 'index.php?page=home';
		$ui->createDefaultHeader(__("Tickets"),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => $back_href)));
		$ui->beginContent();
			
			// Message popup
			if ($message != "") {
				$options = array(
					'popup_id' => 'message_popup',
					'popup_custom' => true,
					'popup_content' => $message
					);
				$ui->addPopup($options);
				$ui->contentAddHtml("<script type=\"text/javascript\">
										$(document).on('pageshow', function() {
											$(\"div.popup-back\")
												.click(function (e) {
													e.preventDefault();
													$(this).remove();
												})
												.show();
										});
									</script>");
			}
			
			$ui->contentBeginCollapsible(__('Filter'));
				$options = array(
					'action' => "index.php?page=incidents",
					'method' => 'POST',
					'data-ajax' => 'false'
					);
				$ui->beginForm($options);
					// Filter search
					$options = array(
						'name' => 'filter_search',
						'label' => __('Search'),
						'value' => $this->filter_search
						);
					$ui->formAddInputSearch($options);
					// Filter status
					$values = array();
					$values[0] = __('Any');
					$values[-10] = __('Not closed');
					$status_table = process_sql ("select * from tincident_status");
					foreach ($status_table as $status) {
						$values[$status['id']] = __($status['name']);
					} 
					
					$options = array(
						'name' => 'filter_status',
						'title' => __('Status'),
						'label' => __('Status'),
						'items' => $values,
						'selected' => $this->filter_status
						);
					$ui->formAddSelectBox($options);
					// Filter owner
					$options = array(
						'name' => 'filter_owner',
						'id' => 'text-filter_owner',
						'label' => __('Owner'),
						'value' => $this->filter_owner,
						'placeholder' => __('Owner'),
						'autocomplete' => 'off'
						);
					$ui->formAddInputText($options);
						// Owner autocompletion
						// List
						$ui->formAddHtml("<ul id=\"ul-autocomplete_owner\" data-role=\"listview\" data-inset=\"true\"></ul>");
						// Autocomplete binding
						$ui->bindMobileAutocomplete("#text-filter_owner", "#ul-autocomplete_owner");
					$options = array(
						'name' => 'submit_button',
						'text' => __('Apply filter'),
						'data-icon' => 'search'
						);
					$ui->formAddSubmitButton($options);
				$form_html = $ui->getEndForm();
			$ui->contentCollapsibleAddItem($form_html);
			$ui->contentEndCollapsible("collapsible-filter", "d");
			// Incidents listing
			$html = $this->getIncidentsList();
			$ui->contentAddHtml($html);
			if ($this->getCountIncidents() > $system->getPageSize()) {
				$ui->contentAddHtml('<div style="text-align:center;" id="loading_rows">
										<img src="../images/spinner.gif">&nbsp;'
											. __('Loading...') .
										'</img>
									</div>');
				$this->addIncidentsLoader();
			}
		$ui->endContent();
		// Foooter buttons
		// New
		$button_new = "<a href='index.php?page=incident' data-role='button'
							data-ajax='false' data-icon='plus'>".__('New')."</a>\n";
		// Pagination
		// $filter = "";
		// if ($this->filter_search != '') {
		// 	$filter .= "&filter_search=".$this->filter_search;
		// }
		// if ($this->filter_status) {
		// 	$filter .= "&filter_status=".$this->filter_status;
		// }
		// if ($this->filter_owner != '') {
		// 	$filter .= "&filter_owner=".$this->filter_owner;
		// }
		// $paginationCG = $ui->getPaginationControgroup("incidents$filter", $this->offset, $this->getNumPages());
		$ui->createFooter($button_new);
		$ui->showFooter();
		$ui->showPage();
	}
	
	public function show ($message = "") {
		if ($this->permission) {
			$system = System::getInstance();
			
			switch ($this->operation) {
				case 'delete':
					$incident = new Incident();
					$result = $incident->deleteIncident($this->id_incident);
					unset($incident);
					if ($result) {
						$this->id_incident = -1;
						$message = "<h2 class='suc'>".__('Successfully deleted')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while deleting the ticket')."</h2>";
					}
					break;
			}
			$this->showIncidents($message);
		}
		else {
			$this->showNoPermission();
		}
	}
	
	private function showNoPermission ($error = false) {
		$system = System::getInstance();
		
		audit_db ($system->getConfig('id_user'), $REMOTE_ADDR, "ACL Violation",
			"Trying to access to tickets section");
		if (! $error) {
			$error['title_text'] = __('You don\'t have access to this page');
			$error['content_text'] = __('Access to this page is restricted to 
				authorized users only, please contact to system administrator 
				if you need assistance. <br><br>Please know that all attempts 
				to access this page are recorded in security logs of Integria 
				System Database');
		}
		$home = new Home();
		$home->show($error);
	}
	
	public function ajax ($method = false) {
		$system = System::getInstance();
		
		if (!$this->permission) {
			return;
		}
		else {
			switch ($method) {
				case 'load_more_incidents':
					if ($this->offset == 1 || $this->offset > $this->getNumPages()) {
						return;
					} else {
						$href = $system->getRequest('href', '');
						$html = $this->getIncidentsList($href, true);
						echo $html;
					}
					break;
			}
		}
	}
	
}

?>
