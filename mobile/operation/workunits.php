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

class Workunits {
	
	private $offset;
	private $id_workunit;
	private $operation;
	
	private $acl = 'PR';
	private $permission = false;
	
	function __construct () {
		$system = System::getInstance();
		
		$this->offset = (int) $system->getRequest('offset', 1);
		$this->id_workunit = (int) $system->getRequest('id_workunit', -1);
		$this->operation = (string) $system->getRequest('operation', "");
		
		// ACL
		$this->permission = $this->checkPermission($system->getConfig('id_user'), $this->acl,
											$this->operation, $this->id_workunit);
	}
	
	public function getPermission () {
		return $this->permission;
	}
	
	public function checkPermission ($id_user, $acl = 'PR', $operation = '', $id_workunit = -1) {
		$system = System::getInstance();
		
		$permission = false;
		if (dame_admin($id_user)) {
			$permission = true;
		} else {
			// Section access
			if ($system->checkACL($acl)) {
				// If the workunit exists, should belong to the user
				if ($operation == "delete_workunit") {
					if ($id_workunit > 0) {
						$user_workunit = get_db_value("id_user", "tworkunit", "id", $id_workunit);
						if (strcasecmp($id_user, $user_workunit) == 0) {
							$permission = true;
						}
					}
				} else {
					$permission = true;
				}
			}
		}
		
		return $permission;
	}
	
	private function getWorkUnitsQuery ($columns = "*", $order_by = "timestamp DESC, id", $limit = true) {
		$system = System::getInstance();
		
		$id_incident = $system->getRequest('id_incident', -1);
		
		$filter = '';
		if ($id_incident > 0) {
			$filter = " AND id = ANY(SELECT id_workunit
									 FROM tworkunit_incident
									 WHERE id_incident = $id_incident)";
		}
		if (dame_admin($system->getConfig('id_user'))) {
			$sql = "SELECT $columns
					FROM tworkunit
					WHERE 1=1
						$filter";
		} else {
			$sql = "SELECT $columns
					FROM tworkunit
					WHERE id_user = '".$system->getConfig('id_user')."'
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
	
	public function getCountWorkUnits () {
		$sql = $this->getWorkUnitsQuery("COUNT(id)", "", false);
		$count = get_db_sql($sql);
		
		return $count;
	}
	
	public function getNumPages () {
		$system = System::getInstance();
		
		$num_pages = ceil( $this->getCountWorkUnits() / $system->getPageSize() );
		return $num_pages;
	}
	
	public function getWorkUnitsList ($href = "", $delete_button = true, $delete_href = "", $ajax = false) {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		if ($href == "") {
			$href = "index.php?page=workunit";
		}
		
		$html = '';
		if (! $ajax) {
			$html = "<ul id='listview' class='ui-itemlistview' data-role='listview' data-count-theme='e'>";
		}
		if ($this->getCountWorkUnits() > 0) {
			$sql = $this->getWorkUnitsQuery();
			$new = true;
			while ( $workunit = get_db_all_row_by_steps_sql($new, $result_query, $sql) ) {
				$new = false;
				$html .= "<li>";
				$html .= "<a href='$href&id_workunit=".$workunit['id']."' class='ui-link-inherit' data-ajax='false'>";
					$date = strtotime($workunit['timestamp']);
					$html .= "<h3 class='ui-li-heading'>".date ("Y-m-d", $date)."</h3>";
					$html .= "<p class='ui-li-desc'><strong>".$workunit['id_user']."</strong></p>";
					$html .= "<p class='ui-li-desc'>".$workunit['description']."</p>";
					$html .= "<span class=\"ui-li-count\">".$workunit['duration']."&nbsp;".__('hours')."</span>";
				$html .= "</a>";
				
				if ($delete_button) {
					if ($delete_href == "") {
						$delete_href = 'index.php?page=workunits&operation=delete_workunit';
					}
					$options = array(
						'popup_id' => 'delete_popup_'.$workunit['id'],
						'delete_href' => $delete_href. '&id_workunit='.$workunit['id']
						);
					$html .= $ui->getDeletePopupHTML($options);
					$html .= "<a data-icon=\"delete\" data-rel=\"popup\" href=\"#delete_popup_".$workunit['id']."\"></a>";
				}
				$html .= "</li>";
			}
		} else {
			$html .= "<li>";
			$html .= "<h3 class='error'>".__('There is no workunits')."</h3>";
			$html .= "</li>";
		}
		if (! $ajax) {
			$html .= "</ul>";
		}
		
		return $html;
	}
	
	public function addWorkUnitsLoader($href = "") {
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
										postvars[\"page\"] = \"workunits\";
										postvars[\"method\"] = \"load_more_workunits\";
										postvars[\"offset\"] = page;
										postvars[\"href\"] = \"$href\";
										page++;
										
										$.post(\"index.php\",
											postvars,
											function (data) {
												if (data.length < 10) {
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
	
	public function showWorkUnits ($message = "") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		// Header
		$back_href = 'index.php?page=workunit';
		$ui->createDefaultHeader(__("Workunits"),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => $back_href)));
					
		// Content
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
			// Workunits listing
			$html = $this->getWorkUnitsList("", false);
			$ui->contentAddHtml($html);
			if ($this->getCountWorkUnits() > $system->getPageSize()) {
				$ui->contentAddHtml('<div style="text-align:center;" id="loading_rows">
										<img src="../images/spinner.gif">&nbsp;'
											. __('Loading...') .
										'</img>
									</div>');
				$this->addWorkUnitsLoader();
			}
		$ui->endContent();
		// Foooter buttons
		// New
		$button_new = "<a href='index.php?page=workunit' data-role='button'
							data-ajax='false' data-icon='plus'>".__('New')."</a>\n";
		// Pagination
		//$paginationCG = $ui->getPaginationControgroup("workunits", $this->offset, $this->getNumPages());
		$ui->createFooter($button_new);
		$ui->showFooter();
		$ui->showPage();
	}
	
	public function show () {
		if ($this->permission) {
			$system = System::getInstance();
			$message = "";
			switch ($this->operation) {
				case 'delete_workunit':
					$workunit = new Workunit();
					if ($workunit->getPermission()) {
						$result = $workunit->deleteWorkUnit($this->id_workunit);
						unset($workunit);
						if ($result) {
							$this->id_workunit = -1;
							$message = "<h2 class='suc'>".__('Successfully deleted')."</h2>";
						} else {
							$message = "<h2 class='error'>".__('An error ocurred while deleting the workunit')."</h2>";
						}
						$this->showWorkUnits($message);
					} else {
						$this->showNoPermission();
					}
					break;
				default:
					$this->showWorkUnits();
			}
		} else {
			$this->showNoPermission();
		}
	}
	
	private function showNoPermission () {
		$system = System::getInstance();
		
		audit_db ($system->getConfig('id_user'), $REMOTE_ADDR, "ACL Violation",
			"Trying to access to workunits section");
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to 
			authorized users only, please contact to system administrator 
			if you need assistance. <br><br>Please know that all attempts 
			to access this page are recorded in security logs of Integria 
			System Database');
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
				case 'load_more_workunits':
					if ($this->offset == 1 || $this->offset > $this->getNumPages()) {
						return;
					} else {
						$href = $system->getRequest('href', '');
						echo $this->getWorkUnitsList($href, false, "", true);
					}
					break;
			}
		}
	}
	
}

?>
