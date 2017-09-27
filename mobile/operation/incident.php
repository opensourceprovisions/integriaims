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

class Incident {
	
	private $id_incident;
	private $title;
	private $description;
	private $group_id;
	
	private $id_creator;
	private $id_owner;
	private $status;
	private $priority;
	private $resolution;
	private $id_task;
	private $sla_disabled;
	private $id_incident_type;
	private $email_copy;
	private $email_notify;
	private $id_parent;
	private $epilog;
	
	private $operation;
	private $tab;
	
	private $acl = 'IR';
	private $permission = false;
	
	function __construct () {
		$system = System::getInstance();
		
		$this->id_incident = (int) $system->getRequest('id_incident', 0);
		$this->title = (string) $system->getRequest('title', "");
		$this->description = (string) $system->getRequest('description', "");
		$this->group_id = (int) $system->getRequest('group_id', -1);
		if ($this->group_id == -1) {
			// GET THE FIRST KNOWN GROUP OF THE USER
			$user_groups = get_user_groups($system->getConfig('id_user'));
			$group_id = reset(array_keys($user_groups));
			$this->group_id = $group_id;
			unset($group_id);
		}
		
		$this->id_creator = (string) $system->getRequest('id_creator', $system->getConfig('id_user'));
		$this->id_owner = (string) $system->getRequest('id_owner', "");
		$this->status = (int) $system->getRequest('status', 1);
		$this->priority = (int) $system->getRequest('priority', 2);
		$this->resolution = (int) $system->getRequest('resolution', 0);
		$this->id_task = (int) $system->getRequest('id_task', 0);
		$this->sla_disabled = (int) $system->getRequest('sla_disabled', 0);
		$this->id_incident_type = (int) $system->getRequest('id_incident_type', 0);
		$this->email_copy = (string) $system->getRequest('email_copy', "");
		$this->email_notify = (int) $system->getRequest('email_notify', -1);
		if ($this->email_notify == -1) {
			$this->email_notify = (int) get_db_value ("forced_email", "tgrupo", "id_grupo", $this->group_id);
		}
		$this->id_parent = (int) $system->getRequest('id_parent', 0);
		$this->epilog = (string) $system->getRequest('epilog', "");
		
		// insert, update, delete, view or ""
		$this->operation = (string) $system->getRequest('operation', "");
		// view, files or ""
		$this->tab = (string) $system->getRequest('tab', "view");
		
		// ACL
		$this->permission = $this->checkPermission ($system->getConfig('id_user'), $this->acl, $this->operation, $this->id_incident);
	}
	
	public function getPermission () {
		return $this->permission;
	}
	
	public function checkPermission ($id_user, $acl = 'IR', $operation = '', $id_incident = 0) {
		$system = System::getInstance();
		
		$permission = false;
		if (dame_admin($id_user)) {
			$permission = true;
		} else {
			if ($system->checkACL($this->acl)) {
				if ($id_incident > 0) {
					$incident_creator = get_db_value ("id_creator", "tincidencia", "id_incidencia", $id_incident);
					$incident_user = get_db_value ("id_usuario", "tincidencia", "id_incidencia", $id_incident);
					if (strcasecmp($id_user, $incident_creator) == 0 || strcasecmp($id_user, $incident_user) == 0) {
						switch ($operation) {
							case 'insert_file':
								if ($system->checkACL('IW') || $system->checkACL('IM')) {
									$permission = true;
								}
								break;
							case 'delete_file':
								if ($system->checkACL('IW') || $system->checkACL('IM')) {
									$permission = true;
								}
								break;
							case 'update_incident':
								if ($system->checkACL('IW') || $system->checkACL('IM')) {
									$permission = true;
								}
								break;
							case 'quick_update_incident':
								$quick_update_type = $system->getRequest('quick_update_type', "");
								switch ($quick_update_type) {
									case 'priority':
										if ($system->checkACL('IM') || $system->checkACL('IW')) {
											$permission = true;
										}
										break;
									case 'owner':
										if ($system->checkACL('IM') || ($system->checkACL('IW') && $system->getConfig('iw_creator_enabled'))) {
											$permission = true;
										}
										break;
									case 'resolution':
										if ($system->checkACL('IM')) {
											$permission = true;
										}
										break;
									case 'status':
										if ($system->checkACL('IM') || $system->checkACL('IW')) {
											$permission = true;
										}
										break;
								}
								break;
							case 'insert_workunit':
								if ($system->checkACL('IW') || $system->checkACL('IM')) {
									$permission = true;
								}
								break;
							case 'update_workunit':
								if ($system->checkACL('IW') || $system->checkACL('IM')) {
									// If the workunit exists, should belong to the user
									$id_workunit = (int) $system->getRequest('id_workunit', -1);
									$user_workunit = get_db_value("id_user", "tworkunit", "id", $id_workunit);
									if (strcasecmp($id_user, $user_workunit) == 0) {
										$permission = true;
									}
								}
								break;
							case 'delete_incident':
								if ($system->checkACL("IM") && strcasecmp($id_user, $incident_creator) == 0) {
									$permission = true;
								}
								break;
							default:
								$permission = true;
						}
					}
				} else if ($operation == "insert_incident") {
					if ($system->checkACL('IW') || $system->checkACL('IM')) {
						$permission = true;
					}
				} else if ($operation == "") {
					$permission = true;
				}
			}
		}
		
		return $permission;
	}
	
	public function setId ($id_incident) {
		$this->id_incident = $id_incident;
	}
	
	public function insertIncident ($title, $description, $group_id, $id_creator = "", $status = 1, $priority = 2, $resolution = 0, $id_task = 0, $sla_disabled = 0, $id_incident_type = 0, $email_copy = "", $email_notify = -1, $id_parent = 0, $epilog = "") {
		$system = System::getInstance();
		
		if ($id_creator == "") {
			$id_creator = $system->getConfig('id_user');
		}
		if ($email_notify == -1) {
			$email_notify = get_db_value ("forced_email", "tgrupo", "id_grupo", $group_id);
		}
		if ($id_parent == 0) {
			$idParentValue = 'NULL';
		}
		else {
			$idParentValue = sprintf ('%d', $id_parent);
		}
		
		$user_responsible = get_group_default_user ($group_id);
		$id_user_responsible = $user_responsible['id_usuario'];
		if ($id_user_responsible === false) {
			$id_user_responsible = $system->getConfig('id_user');
		}
		
		$id_inventory = get_group_default_inventory($group_id, true);
		
		// DONT use MySQL NOW() or UNIXTIME_NOW() because 
		// Integria can override localtime zone by a user-specified timezone.
		$timestamp = print_mysql_timestamp();
		
		$values = array(
			'inicio' => $timestamp,
			'actualizacion' => $timestamp,
			'titulo' => $title,
			'descripcion' => $description,
			'id_usuario' => $id_user_responsible,
			'estado' => $status,
			'prioridad' => $priority,
			'id_grupo' => $group_id,
			'id_creator' => $id_creator,
			'id_task' => $id_task,
			'resolution' => $resolution,
			'id_incident_type' => $id_incident_type,
			'sla_disabled' => $sla_disabled,
			'email_copy' => $email_copy
		);
		$id_incident = process_sql_insert('tincidencia', $values);
		
		if ($id_incident !== false) {
			
			if ( include_once ($system->getConfig('homedir')."/include/functions_incidents.php") ) {
				/* Update inventory objects in incident */
				update_incident_inventories ($id_incident, array($id_inventory));
			}
			
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Ticket created",
				"User ".$config['id_user']." created ticket #".$id_incident);
			
			incident_tracking ($id_incident, INCIDENT_CREATED);

			// Email notify to all people involved in this incident
			if ($email_notify) {
				mail_incident ($id_incident, $usuario, "", 0, 1);
			}
			
			// Insert data of incident type fields
			if ($id_incident_type > 0) {
				$sql_label = "SELECT `label` FROM `tincident_type_field` WHERE id_incident_type = $id_incident_type";
				$labels = get_db_all_rows_sql($sql_label);
			
				if ($labels === false) {
					$labels = array();
				}
				
				foreach ($labels as $label) {
					$id_incident_field = get_db_value_filter('id', 'tincident_type_field', array('id_incident_type' => $id_incident_type, 'label'=> $label['label']), 'AND');
					
					$values_insert['id_incident'] = $id_incident;
					$values_insert['data'] = $system->getRequest(base64_encode($label['label']));
					$values_insert['id_incident_field'] = $id_incident_field;
					$id_incident_field = get_db_value('id', 'tincident_type_field', 'id_incident_type', $id_incident_type);
					process_sql_insert('tincident_field_data', $values_insert);
				}
			}
			
			return $id_incident;
		}
	}

	public function quickIncidentUpdate ($id_incident, $type, $value) {
		$system = System::getInstance();

		$column = "";

		switch ($type) {
			case 'priority':
				$column = "prioridad";
				break;
			case 'owner':
				$column = "id_usuario";
				break;
			case 'resolution':
				$column = "resolution";
				break;
			case 'status':
				$column = "estado";
				break;
		}

		if ($column) {
			$res = process_sql_update ('tincidencia', array($column => $value), array("id_incidencia" => $id_incident));

			if ($res && include_once ($system->getConfig('homedir')."/include/functions_incidents.php")) {
				switch ($type) {
					case 'priority':
						incident_tracking ($id_incident, INCIDENT_PRIORITY_CHANGED, $value);
						break;
					case 'owner':
						incident_tracking ($id_incident, INCIDENT_USER_CHANGED, $value);
						break;
					case 'resolution':
						incident_tracking ($id_incident, INCIDENT_RESOLUTION_CHANGED, $value);
						break;
					case 'status':
						incident_tracking ($id_incident, INCIDENT_STATUS_CHANGED, $value);
						break;
				}
			}
		}
		
		return $res;

	}

	public function quickPriorityUpdate ($id_incident, $priority) {
		return $this->quickIncidentUpdate ($id_incident, "priority", $priority);
	}

	public function quickOwnerUpdate ($id_incident, $id_owner) {
		return $this->quickIncidentUpdate ($id_incident, "owner", $id_owner);
	}

	public function quickResolutionUpdate ($id_incident, $resolution) {
		return $this->quickIncidentUpdate ($id_incident, "resolution", $resolution);
	}

	public function quickStatusUpdate ($id_incident, $status) {
		return $this->quickIncidentUpdate ($id_incident, "status", $status);
	}
	
	public function deleteIncident ($id_incident) {
		$system = System::getInstance();
		
		$error = false;
		
		// tincident_contact_reporters
		$sql_delete = "DELETE FROM tincident_contact_reporters
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tincident_field_data
		$res = $sql_delete = "DELETE FROM tincident_field_data
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tincident_inventory
		$sql_delete = "DELETE FROM tincident_inventory
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tincident_sla_graph
		$sql_delete = "DELETE FROM tincident_sla_graph_data
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tincident_stats
		$sql_delete = "DELETE FROM tincident_stats
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tincident_track
		$sql_delete = "DELETE FROM tincident_track
					   WHERE id_incident = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tworkunit
		$sql_delete = "DELETE FROM tworkunit
					   WHERE id = ANY(SELECT id_workunit
									  FROM tworkunit_incident
									  WHERE id_incident = $id_incident)";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		// tattachment
		$sql_delete = "DELETE FROM tattachment
					   WHERE id_incidencia = $id_incident";
		$res = process_sql ($sql_delete);
		
		if ($res === false) {
			$error = true;
		}
		
		if (! $error) {
			// tincidencia
			$sql_delete = "DELETE FROM tincidencia
						   WHERE id_incidencia = ".$incident["id_incidencia"];
			process_sql ($sql_delete);
		}
		
		return !$error;
	}
	
	public function getIncidentSimpleForm ($action = "index.php?page=incident", $method = "POST") {
		$ui = Ui::getInstance();
		
		$options = array (
			'id' => 'form-incident',
			'action' => $action,
			'method' => $method,
			'enctype' => 'multipart/form-data',
			//'size' => '40',
			'data-ajax' => 'false'
			);
		$ui->beginForm($options);
		// Title
		$options = array(
			'name' => 'title',
			'label' => __('Title'),
			'value' => $this->title,
			'placeholder' => __('Title')
			);
		$ui->formAddInputText($options);
		// Priority
		$options = array(
			'name' => 'priority',
			'title' => __('Priority'),
			'label' => __('Priority'),
			'items' => get_priorities(),
			'selected' => $this->priority
			);
		$ui->formAddSelectBox($options);
		// Type
		$types = array();
		$types = get_incident_types();
		array_unshift($types, __('None'));
		$options = array(
			'name' => 'id_incident_type',
			'title' => __('Type'),
			'label' => __('Type'),
			'items' => $types,
			'selected' => $this->id_incident_type
			);
		$ui->formAddSelectBox($options);
		$ui->formAddHtml("<div id='type_fields'></div>");
		$ui->formAddHtml("<script type='text/javascript' src='include/javascript/functions_incidents.mobile.js'></script>");
		$ui->formAddHtml("<script type='text/javascript'>
							  $(document).ready (function () {
								  $('#select-id_incident_type').on('change', function () {
									  showIncidentTypeFields('#type_fields');
								  });
							  });
						  </script>");
		// Description
		$options = array(
				'name' => 'description',
				'label' => __('Description'),
				'value' => $this->description
				);
		$ui->formAddHtml($ui->getTextarea($options));
		$ui->contentBeginCollapsible(__('Optional file'));
			// File
			$options = array(
				'type' => 'file',
				'name' => 'file',
				'label' => __('File')
				);
			$file_inputs = $ui->getInput($options);
			// Description
			$options = array(
					'name' => 'description_file',
					'label' => __('File description'),
					'value' => ''
					);
			$file_inputs .= $ui->getTextarea($options);
		$ui->contentCollapsibleAddItem($file_inputs);
		$collapsible_file = $ui->getEndCollapsible("collapsible-form", "c");
		$ui->formAddHtml($collapsible_file);
		// Hidden operation (insert or update+id)
		if ($this->id_incident <= 0) {
			$options = array(
				'type' => 'hidden',
				'name' => 'operation',
				'value' => 'insert_incident'
				);
			$ui->formAddInput($options);
			// Submit button
			$options = array(
					'text' => __('Add'),
					'data-icon' => 'plus'
					);
			$ui->formAddSubmitButton($options);
		} else {
			$options = array(
				'type' => 'hidden',
				'name' => 'operation',
				'value' => 'update_incident'
				);
			$ui->formAddInput($options);
			$options = array(
				'type' => 'hidden',
				'name' => 'id_incident',
				'value' => $this->id_incident
				);
			$ui->formAddInput($options);
			// Submit button
			$options = array(
					'text' => __('Update'),
					'data-icon' => 'refresh'
					);
			$ui->formAddSubmitButton($options);
		}
		
		return $ui->getEndForm();
	}
	
	private function showIncidentSimpleForm ($message = "") {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		$back_href = "index.php?page=incidents";
		$ui->createDefaultHeader(__("New ticket"),
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
					'popup_content' => $message
					);
				
				$ui->contentAddHtml($ui->getPopupHTML($options));
				$ui->contentAddHtml("<script type=\"text/javascript\">
										$(document).on('pageshow', function() {
											$(\"#message_popup\").popup(\"open\");
										});
									</script>");
			}
			// Form
			$ui->contentAddHtml($this->getIncidentSimpleForm());
			
		$ui->endContent();
		// Foooter buttons
		// Add
		if ($this->id_incident <= 0) {
			$button_add = "<a onClick=\"$('#form-incident').submit();\" data-role='button' data-icon='plus'>"
							.__('Add')."</a>\n";
		} else {
			$button_add = "<a onClick=\"$('#form-incident').submit();\" data-role='button' data-icon='refresh'>"
							.__('Update')."</a>\n";
		}
		// Delete
		$button_delete = '';
		if ($this->id_incident > 0) {
			$button_delete = "<a href='index.php?page=incidents&operation=delete_incident&id_incident=".$this->id_incident."'
									data-role='button' data-ajax='false' data-icon='delete'>".__('Delete')."</a>\n";
		}
		$ui->createFooter("<div data-type='horizontal' data-role='controlgroup'>$button_add"."$button_delete</div>");
		$ui->showFooter();
		$ui->showPage();
	}


	public function getIncidentQuickForm ($incident = false, $action = "index.php?page=incident", $method = "POST") {
		$system = System::getInstance();
		$ui = Ui::getInstance();

		if (!$incident) {
			$incident = get_db_row ("tincidencia", "id_incidencia", $this->id_incident);
		}
		$resolution_text = incidents_get_incident_resolution_text($incident['id_incidencia']);
		

		$has_im = $system->checkACL("IM");
		$has_iw = $system->checkACL("IW");
		
		if ($has_iw || $has_im) {
			include_once ($system->getConfig('homedir')."/include/functions_incidents.php");

			$options = array (
				'id' => 'form-quick_update_incident',
				'action' => $action,
				'method' => $method,
				'data-ajax' => 'false'
				);
			$ui->beginForm($options);

			//If IW creator enabled flag is enabled, the user can change the creator
			if ($has_im || ($has_iw && $system->getConfig('iw_creator_enabled'))) {
				// Filter owner
				$options = array(
					'name' => 'quick_id_owner',
					'id' => 'text-id_owner',
					'label' => __('Owner'),
					'value' => $incident["id_usuario"],
					'placeholder' => __('Owner'),
					'autocomplete' => 'off'
					);
				$ui->formAddInputText($options);
				// Owner autocompletion
				// List
				$ui->formAddHtml("<ul id=\"ul-autocomplete_owner\" data-role=\"listview\" data-inset=\"true\"></ul>");
				// Autocomplete binding
				$ui->bindMobileAutocomplete("#text-id_owner", "#ul-autocomplete_owner", false);
			}

			// Priority
			$options = array(
				'name' => 'quick_priority',
				'id' => 'select-priority',
				'title' => __('Priority'),
				'label' => __('Priority'),
				'items' => get_priorities(),
				'selected' => $incident["prioridad"]
				);
			$ui->formAddSelectBox($options);

			if ($has_im) {
				// Resolution
				$values = array();
				$values[0] = __('None');
				$resolutions = get_incident_resolutions();
				foreach ($resolutions as $key => $value) {
					$values[$key] = $value;
				} 
				$options = array(
					'name' => 'quick_resolution',
					'id' => 'select-quick_resolution',
					'title' => __('Resolution'),
					'label' => __('Resolution'),
					'items' => $values,
					'selected' => $incident["resolution"]
					);
				$ui->formAddSelectBox($options);
			}

			// Filter status
			$values = array();
			$status_table = process_sql ("select * from tincident_status");
			foreach ($status_table as $status) {
				$values[$status['id']] = __($status['name']);
			}
			$options = array(
				'name' => 'quick_status',
				'id' => 'select-quick_status',
				'title' => __('Status'),
				'label' => __('Status'),
				'items' => $values,
				'selected' => $incident["estado"]
				);
			$ui->formAddSelectBox($options);

			// Hidden operation (update+id)
			$options = array(
				'type' => 'hidden',
				'name' => 'operation',
				'value' => 'quick_update_incident'
				);
			$ui->formAddInput($options);
			$options = array(
				'type' => 'hidden',
				'name' => 'quick_update_type',
				'id' => 'quick_update_type',
				'value' => ''
				);
			$ui->formAddInput($options);
			$options = array(
				'type' => 'hidden',
				'name' => 'quick_update_value',
				'id' => 'quick_update_value',
				'value' => ''
				);
			$ui->formAddInput($options);
			$options = array(
				'type' => 'hidden',
				'name' => 'id_incident',
				'value' => $this->id_incident
				);
			$ui->formAddInput($options);
			
			// Submit button
			$options = array(
				'text' => __('Update'),
				'data-icon' => 'refresh'
				);
			$ui->formAddSubmitButton($options);
			
			$ui->formAddHtml("<script type=\"text/javascript\">
								$(document).ready(function() {
									$('form#form-quick_update_incident').submit(function (e) {
										e.preventDefault();
										var form = e.target;
										$.ajax({
											type: \"POST\",
											url: \"../ajax.php\",
											data: {
												page: \"include/ajax/incidents\",
												set_params: 1,
												id_ticket: " . $this->id_incident . ",
												id_user: $('#text-id_owner').val(),
												id_priority: $('#select-priority').val(),
												id_resolution: $('#select-quick_resolution').val(),
												id_status: $('#select-quick_status').val(),
											},
											dataType: \"text\",
											success: function (data) {
												location.reload();
											}
										});
									});
								});
							</script>");
			
			return $ui->getEndForm();

		} else {
			return "";
		}
	}
	
	private function getIncidentDetail () {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$incident = get_db_row ("tincidencia", "id_incidencia", $this->id_incident);
		if (! $incident) {
			$html = "<h2 class=\"error\">".__('Ticket not found')."</h2>";
		} else {
			include_once ($system->getConfig('homedir')."/include/functions_incidents.php");
			
			// DETAILS
			$resolution = incidents_get_incident_resolution_text($incident['id_incidencia']);
			$priority = incidents_get_incident_priority_text($incident['id_incidencia']);
			$priority_image = print_priority_flag_image ($incident['prioridad'], true, "../");
			$group = incidents_get_incident_group_text($incident['id_incidencia']);
			$status = incidents_get_incident_status_text($incident['id_incidencia']);
			$type = incidents_get_incident_type_text($incident['id_incidencia']);
			
			// Get the status icon
			if ($incident['estado'] < 3) {
				$status_icon = 'status_new';
			} else if ($incident['estado'] < 7) {
				$status_icon = 'status_pending';
			} else {
				$status_icon = 'status_closed';
			}
			
			$ui->contentBeginGrid();


				// $options = array(
				// 	'action' => "index.php?page=incidents",
				// 	'method' => 'POST',
				// 	'data-ajax' => 'false'
				// 	);
				// $ui->beginForm($options);
				// 	// Filter status
				// 	$values = array();
				// 	$values[0] = __('Any');
				// 	$values[-10] = __('Not closed');
				// 	$status_table = process_sql ("select * from tincident_status");
				// 	foreach ($status_table as $status) {
				// 		$values[$status['id']] = __($status['name']);
				// 	} 
					
				// 	$options = array(
				// 		'name' => 'filter_status',
				// 		'title' => __('Status'),
				// 		'items' => $values,
				// 		'selected' => $this->filter_status
				// 		);
				// 	$ui->formAddSelectBox($options);
				// $form_html = $ui->getEndForm();


				$status_cell = "<div class='detail-element'>
								".__('Status')."<br>
								<img src='../images/$status_icon.png'><br>
								<strong>$status</strong>
							</div>";
				$ui->contentGridAddCell($status_cell);
				$group_cell = "<div class='detail-element'>
								".__('Group')."<br>
								<img src='../images/group.png'><br>
								<strong>$group</strong>
							</div>";
				$ui->contentGridAddCell($group_cell);
				$priority_cell = "<div class='detail-element'>
								".__('Priority')."<br>
								$priority_image<br>
								<strong>$priority</strong>
							</div>";
				$ui->contentGridAddCell($priority_cell);
				$resolution_cell = "<div class='detail-element'>
								".__('Resolution')."<br>
								<img src='../images/resolution.png'><br>
								<strong>$resolution</strong>
							</div>";
				$ui->contentGridAddCell($resolution_cell);
				$type_cell = "<div class='detail-element'>
								".__('Type')."<br>
								<img src='../images/incident.png'><br>
								<strong>$type</strong>
							</div>";
				$ui->contentGridAddCell($type_cell);
			$detail_grid = $ui->getContentEndGrid();
			
			$ui->contentBeginCollapsible(__('Details'));
				$ui->contentCollapsibleAddItem($detail_grid);
			$detail = $ui->getEndCollapsible("", "b", "c", false);
			$detail = "<div style='padding-left: 2px; padding-right: 2px;'>$detail</div>";
			
			// DESCRIPTION
			$description = false;
			if ($incident['descripcion'] != "") {
				$ui->contentBeginCollapsible(__('Description'));
					$ui->contentCollapsibleAddItem($incident['descripcion']);
				$description = $ui->getEndCollapsible("", "b", "c", false);
				
			}
			
			// CUSTOM FIELDS
			$custom_fields = false;
			if ($incident['id_incident_type']) {
				$type_name = get_db_value("name", "tincident_type", "id", $incident['id_incident_type']);
				$fields = incidents_get_all_type_field ($incident['id_incident_type'], $incident['id_incidencia']);
				
				$custom_fields = "";
				$ui->contentBeginCollapsible($type_name);
				foreach ($fields as $field) {
					$custom_fields = $field["label"].":&nbsp;<strong>".$field["data"]."</strong>";
					$ui->contentCollapsibleAddItem($custom_fields);
				}
				$custom_fields = $ui->getEndCollapsible("", "b", "c", false);
			}
			
			// PEOPLE
			$ui->contentBeginGrid();
				$name_creator = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident['id_creator']));
				$avatar_creator = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident['id_creator']));
				$creator_cell = "<div style='text-align: center;'>
										<div class='bubble_little'>
											<img src='../images/avatars/$avatar_creator.png'>
										</div>
									<strong style='color: #FF9933'>".__('Created by').":</strong><br>
									$name_creator
								</div>";
				$ui->contentGridAddCell($creator_cell);
				$name_owner = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident['id_usuario']));
				$avatar_owner = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident['id_usuario']));
				$owner_cell = "<div style='text-align: center;'>
										<div class='bubble_little'>
											<img src='../images/avatars/$avatar_owner.png'>
										</div>
									<strong style='color: #FF9933'>".__('Owned by').":</strong><br>
									$name_owner
								</div>";
				$ui->contentGridAddCell($owner_cell);
				if ($incident['estado'] == STATUS_CLOSED) {
					if (empty($incident["closed_by"])) {
						$name_closer = __('Unknown');
						$avatar_closer = '../avatar_unknown';
					} else {
						$name_closer = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident['closed_by']));
						$avatar_closer = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident['closed_by']));
					}
					$closer_cell = "<div style='text-align: center;'>
										<div class='bubble_little'>
											<img src='../images/avatars/$avatar_closer.png'>
										</div>
										<strong style='color: #FF9933'>".__('Closed by').":</strong><br>
										$name_creator
									</div>";
					$ui->contentGridAddCell($closer_cell);
				}
			$people_grid = $ui->getContentEndGrid();
			$ui->contentBeginCollapsible(__('People'));
				$ui->contentCollapsibleAddItem($people_grid);
			$people = $ui->getEndCollapsible("", "b", "c");
			
			// DATES
			$ui->contentBeginGrid();
				$created_timestamp = strtotime($incident['inicio']);
				$created_cell = "<table width='97%' style='text-align: center;' id='incidents_dates_square'>";
					$created_cell .= "<tr>";
						$created_cell .= "<td>".__('Created on').":</td>";
					$created_cell .= "</tr>";
					$created_cell .= "<tr>";
						$created_cell .= "<td id='created_on' class='mini_calendar'>";
							$created_cell .= "<table>";
								$created_cell .= "<tr>";
									$created_cell .= "<th>" . strtoupper(date('M\' y', $created_timestamp)) . "</th>";
								$created_cell .= "</tr>";
								$created_cell .= "<tr>";
									$created_cell .= "<td class='day'>" . date('d', $created_timestamp) . "</td>";
								$created_cell .= "</tr>";
								$created_cell .= "<tr>";
									$created_cell .= "<td class='time'><img src='../images/cal_clock_grey.png'>&nbsp;" . date('H:i:s', $created_timestamp) . "</td>";
								$created_cell .= "</tr>";
							$created_cell .= "</table>";
						$created_cell .= "</td>";
					$created_cell .= "</tr>";
				$created_cell .= "</table>";
				$ui->contentGridAddCell($created_cell);
				$updated_timestamp = strtotime($incident['actualizacion']);
				$updated_cell = "<table width='97%' style='text-align: center;' id='incidents_dates_square'>";
					$updated_cell .= "<tr>";
						$updated_cell .= "<td>".__('Updated on').":</td>";
					$updated_cell .= "</tr>";
					$updated_cell .= "<tr>";
						$updated_cell .= "<td id='updated_on' class='mini_calendar'>";
							$updated_cell .= "<table>";
								$updated_cell .= "<tr>";
									$updated_cell .= "<th>" . strtoupper(date('M\' y', $updated_timestamp)) . "</th>";
								$updated_cell .= "</tr>";
								$updated_cell .= "<tr>";
									$updated_cell .= "<td class='day'>" . date('d', $updated_timestamp) . "</td>";
								$updated_cell .= "</tr>";
								$updated_cell .= "<tr>";
									$updated_cell .= "<td class='time'><img src='../images/cal_clock_orange.png'>&nbsp;" . date('H:i:s', $updated_timestamp) . "</td>";
								$updated_cell .= "</tr>";
							$updated_cell .= "</table>";
						$updated_cell .= "</td>";
					$updated_cell .= "</tr>";
				$updated_cell .= "</table>";
				$ui->contentGridAddCell($updated_cell);
				if ($incident["estado"] == STATUS_CLOSED) {
					$closed_timestamp = strtotime($incident['cierre']);
					$closed_cell = "<table width='97%' style='text-align: center;' id='incidents_dates_square'>";
						$closed_cell .= "<tr>";
							$closed_cell .= "<td>".__('Closed on').":</td>";
						$closed_cell .= "</tr>";
						$closed_cell .= "<tr>";
							$closed_cell .= "<td id='closed_on' class='mini_calendar'>";
								$closed_cell .= "<table>";
									$closed_cell .= "<tr>";
										$closed_cell .= "<th>" . strtoupper(date('M\' y', $closed_timestamp)) . "</th>";
									$closed_cell .= "</tr>";
									$closed_cell .= "<tr>";
										$closed_cell .= "<td class='day'>" . date('d', $closed_timestamp) . "</td>";
									$closed_cell .= "</tr>";
									$closed_cell .= "<tr>";
										$closed_cell .= "<td class='time'><img src='../images/cal_clock_darkgrey.png'>&nbsp;" . date('H:i:s', $closed_timestamp) . "</td>";
									$closed_cell .= "</tr>";
								$closed_cell .= "</table>";
							$closed_cell .= "</td>";
						$closed_cell .= "</tr>";
					$closed_cell .= "</table>";
					$ui->contentGridAddCell($closed_cell);
				}
			$dates_grid = $ui->getContentEndGrid();
			$ui->contentBeginCollapsible(__('Dates'));
				$ui->contentCollapsibleAddItem($dates_grid);
			$dates = $ui->getEndCollapsible("", "b", "c");

			if ($system->getConfig('enabled_ticket_editor')) {
				$ui->contentBeginCollapsible(__('Quick edit'));
					$ui->contentCollapsibleAddItem($this->getIncidentQuickForm($incident));
				$quick_edit = $ui->getEndCollapsible("", "b", "c");
			} else {
				$quick_edit = "";
			}
			
			$html = "<h1 class='title'>".$incident['titulo']."</h1>";
			$html .= $detail;
			if (!$description || !$custom_fields) {
				if ($description) {
					$html .= $description;
				}
				if ($custom_fields) {
					$html .= $custom_fields;
				}
			} else {
				$ui->contentBeginGrid();
				$ui->contentGridAddCell($description);
				$ui->contentGridAddCell($custom_fields);
				$html .= $ui->getContentEndGrid();
			}
			$ui->contentBeginGrid();
				$ui->contentGridAddCell($people);
				$ui->contentGridAddCell($dates);
			$html .= $ui->getContentEndGrid();
			$html .= $quick_edit;
		}
		
		return $html;
	}
	
	public function insertIncidentFile ($file) {
		$system = System::getInstance();
		
		if ( include_once ($system->getConfig('homedir')."/include/functions_incidents.php") ) {
			include_once ($system->getConfig('homedir')."/include/functions_workunits.php");
			
			$filename = $_FILES[$file]['name'];
			$filename = str_replace (" ", "_", $filename);
			$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
			$correct_file_path = sys_get_temp_dir()."/$filename";
			$file_tmp = $_FILES[$file]['tmp_name'];
			if (rename($file_tmp, $correct_file_path)) {
				$file_path = $correct_file_path;
			} else {
				$file_path = $file_tmp;
			}
			$description_file = (string) $system->getRequest('description_file', '');
			
			$result = attach_incident_file ($this->id_incident, $file_path, $description_file);
			
			if (preg_match("/".__('File added')."/i", $result)) {
				$return = true;
			} else {
				$return = false;
			}
		} else {
			$return = false;
		}
		
		return $return;
	}
	
	private function getFilesQuery ($columns = "*", $order_by = "timestamp, id_usuario, filename") {
		$system = System::getInstance();
		
		$sql = "SELECT $columns
				FROM tattachment
				WHERE id_incidencia = '".$this->id_incident."'";
		if ($order_by != "") {
			$sql .= " ORDER BY $order_by";
		}
		
		return $sql;
	}
	
	private function getCountFiles () {
		$sql = $this->getFilesQuery("COUNT(id_attachment)", "");
		$count = get_db_sql($sql);
		
		return $count;
	}
	
	private function getFilesList ($href = "", $delete_button = false, $delete_href = "") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		if ($href == "") {
			$href = "index.php?page=incident&tab=file&id_incident=".$this->id_incident;
		}
		
		if (!session_id()) session_start();
		$_SESSION["id_usuario"] = $system->getConfig('id_user');
		session_write_close();

		$html = "<ul class='ui-itemlistview' data-role='listview' data-count-theme='e'>";
		if ($this->getCountFiles() > 0) {
			$sql = $this->getFilesQuery();
			$new = true;
			while ( $file = get_db_all_row_by_steps_sql($new, $result_query, $sql) ) {
				$new = false;
				$html .= "<li>";
				$html .= "<a data-ajax='false' target='_blank' href='../operation/common/download_file.php?type=incident&id_attachment=".$file['id_attachment']."' class='ui-link-inherit' target='_blank'>";
					$html .= "<h3 class='ui-li-heading'><img src='../images/attach.png'>&nbsp;".$file['filename']."</img></h3>";
					$html .= "<p class='ui-li-desc'>".$file['description']."</p>";
					$html .= "<span class=\"ui-li-aside\">".round($file['size']/1024,2)."&nbsp;KB</span>";
				$html .= "</a>";
				
				if ($delete_button) {
					if ($delete_href == "") {
						$delete_href = "index.php?page=incident&tab=files&operation=delete_file";
						$delete_href .= "&id_incident=".$this->id_incident;
					}
					$options = array(
						'popup_id' => 'delete_popup_'.$file['id_attachment'],
						'delete_href' => $delete_href."&id_file=".$file['id_attachment']
						);
					$html .= $ui->getDeletePopupHTML($options);
					$html .= "<a data-icon=\"delete\" data-rel=\"popup\" href=\"#delete_popup_".$file['id_attachment']."\"></a>";
				}
				$html .= "</li>";
			}
		} else {
			$html .= "<li>";
			$html .= "<h3 class='error'>".__('There is no files')."</h3>";
			$html .= "</li>";
		}
		$html .= "</ul>";
		
		return $html;
	}
	
	private function getFileForm ($action = "index.php?page=incident&tab=file", $method = "POST") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$options = array (
			'id' => 'form-incident_file',
			'action' => $action,
			'method' => $method,
			'enctype' => 'multipart/form-data',
			//'size' => '40',
			'data-ajax' => 'false'
			);
		$ui->beginForm($options);
			// Hidden id_incident
			$options = array(
				'type' => 'hidden',
				'name' => 'id_incident',
				'value' => $this->id_incident
				);
			$ui->formAddInput($options);
			// File
			$options = array(
				'type' => 'file',
				'name' => 'file',
				'label' => __('File'),
				'required' => 'required'
				);
			$ui->formAddInput($options);
			// Description
			$options = array(
					'name' => 'description_file',
					'label' => __('Description'),
					'value' => ''
					);
			$ui->formAddHtml($ui->getTextarea($options));
			// Hidden operation (insert or update+id)
			if (!isset($this->id_file) || $this->id_file < 0) {
				$options = array(
					'type' => 'hidden',
					'name' => 'operation',
					'value' => 'insert_file'
					);
				$ui->formAddInput($options);
				// Submit button
				$options = array(
					'text' => __('Add'),
					'data-icon' => 'plus'
					);
				$ui->formAddSubmitButton($options);
			} else {
				$options = array(
					'type' => 'hidden',
					'name' => 'operation',
					'value' => 'update_file'
					);
				$ui->formAddInput($options);
				$options = array(
					'type' => 'hidden',
					'name' => 'id_file',
					'value' => ''
					);
				$ui->formAddInput($options);
				// Submit button
				$options = array(
					'text' => __('Update'),
					'data-icon' => 'refresh'
					);
				$ui->formAddSubmitButton($options);
			}
			
		return $ui->getEndForm();
	}
	
	public function getFileUploadStatus ($file) {
		return $_FILES[$file]['error'];
	}
	
	public function translateFileUploadStatus ($status) {
		switch ($status) {
			case UPLOAD_ERR_OK:
				$message = true;
				break;
			case UPLOAD_ERR_INI_SIZE:
				$message = "<h2 class='error'>".__('The file exceeds the maximum size')."</h2>";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = "<h2 class='error'>".__('The file exceeds the maximum size')."</h2>";
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = "<h2 class='error'>".__('The uploaded file was only partially uploaded')."</h2>";
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = "<h2 class='error'>".__('No file was uploaded')."</h2>";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = "<h2 class='error'>".__('Missing a temporary folder')."</h2>";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = "<h2 class='error'>".__('Failed to write file to disk')."</h2>";
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = "<h2 class='error'>".__('File upload stopped by extension')."</h2>";
				break;
			
			default:
				$message = "<h2 class='error'>".__('Unknown upload error')."</h2>";
				break;
		}
		
		return $message;
	}
	
	private function showIncident ($tab = "view", $message = "") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		// Header options
		$header_title = __("Ticket")."&nbsp;#".$this->id_incident;
		$left_href = "index.php?page=incidents";
		$header_left_button = $ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => $left_href
					)
			);
		$right_href = "index.php?page=home";
		$header_right_button = $ui->createHeaderButton(
				array('icon' => 'home',
					'pos' => 'right',
					'text' => __('Home'),
					'href' => $right_href
					)
			);
		
		// Content
		$selected_tab_detail = "";
		$selected_tab_workunit = "";
		$selected_tab_file = "";
		
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
			switch ($tab) {
				case 'detail':
					$selected_tab_detail = "class=\"ui-btn-active ui-state-persist\"";
					$ui->contentAddHtml($this->getIncidentDetail());
					
					// Header options
					$right_href = "index.php?page=home"; // Edit in the future
					$header_right_button = $ui->createHeaderButton(
							array('icon' => 'home',
								'pos' => 'right',
								'text' => __('Home'),
								'href' => $right_href
								)
						);
					break;
				case 'edit':
					break;
				case 'workunits':
					$selected_tab_workunit = "class=\"ui-btn-active ui-state-persist\"";
					$workunits = new Workunits();
					$href = "index.php?page=incident&tab=workunit&id_incident=".$this->id_incident;
					$delete_button = false;
					$delete_href = "";
					
					// Workunits listing
					$html = $workunits->getWorkUnitsList($href, $delete_button, $delete_href);
					$ui->contentAddHtml($html);
					if ($workunits->getCountWorkUnits() > $system->getPageSize()) {
						$ui->contentAddHtml('<div style="text-align:center;" id="loading_rows">
												<img src="../images/spinner.gif">&nbsp;'
													. __('Loading...') .
												'</img>
											</div>');
						
						$workunits->addWorkUnitsLoader($href);
					}
					unset($workunits);
					
					// Header options
					$right_href = "index.php?page=incident&tab=workunit&id_incident=".$this->id_incident;
					$header_right_button = $ui->createHeaderButton(
							array('icon' => 'add',
								'pos' => 'right',
								'text' => __('New'),
								'href' => $right_href
								)
						);
					break;
				case 'workunit':
					$selected_tab_workunit = "class=\"ui-btn-active ui-state-persist\"";
					$workunit = new Workunit();
					$action = "index.php?page=incident&tab=workunit";
					$ui->contentAddHtml($workunit->getWorkUnitForm($action, "POST"));
					unset($workunit);
					
					// Header options
					if ($id_workunit = $system->getRequest('id_workunit', false)) {
						$header_title = __("Workunit")."&nbsp;#".$id_workunit;
					} else {
						$header_title = __("Workunit");
					}
					$right_href = "index.php?page=incident&tab=workunits&id_incident=".$this->id_incident;
					$header_right_button = $ui->createHeaderButton(
							array('icon' => 'grid',
								'pos' => 'right',
								'text' => __('List'),
								'href' => $right_href
								)
						);
					break;
				case 'files':
					$selected_tab_file = "class=\"ui-btn-active ui-state-persist\"";
					$ui->contentAddHtml($this->getFilesList());
					
					// Header options
					$right_href = "index.php?page=incident&tab=file&id_incident=".$this->id_incident;
					$header_right_button = $ui->createHeaderButton(
							array('icon' => 'add',
								'pos' => 'right',
								'text' => __('New'),
								'href' => $right_href
								)
						);
					break;
				case 'file':
					$selected_tab_file = "class=\"ui-btn-active ui-state-persist\"";
					$ui->contentAddHtml($this->getFileForm());
					
					// Header options
					$header_title = __("File");
					$right_href = "index.php?page=incident&tab=files&id_incident=".$this->id_incident;
					$header_right_button = $ui->createHeaderButton(
							array('icon' => 'grid',
								'pos' => 'right',
								'text' => __('List'),
								'href' => $right_href
								)
						);
					break;
				default:
					$tab = 'detail';
					$selected_tab_detail = "class=\"ui-btn-active ui-state-persist\"";
					$ui->contentAddHtml($this->getIncidentDetail());
			}
		$ui->endContent();
		
		// Header
		$ui->createHeader($header_title, $header_left_button, $header_right_button);
		// Navigation bar
		$tab_detail = "<a href='index.php?page=incident&tab=view&id_incident=".$this->id_incident."' $selected_tab_detail data-role='button' data-icon='info'>"
			.__('Info')."</a>\n";
		$tab_workunit = "<a href='index.php?page=incident&tab=workunits&id_incident=".$this->id_incident."' $selected_tab_workunit data-role='button' data-icon='star'>"
			.__('Workunit')."</a>\n";
		$tab_file = "<a href='index.php?page=incident&tab=files&id_incident=".$this->id_incident."' $selected_tab_file data-role='button' data-icon='plus'>"
			.__('Files')."</a>\n";
		$buttons = array ($tab_detail, $tab_workunit, $tab_file);
		$ui->addNavBar($buttons);
		$ui->showPage();
	}
	
	public function show () {
		if ($this->permission) {
			$system = System::getInstance();
			
			$message = "";
			switch ($this->operation) {
				case 'insert_incident':
					$result = $this->insertIncident($this->title, $this->description, $this->group_id,
													$this->id_creator, $this->status, $this->priority,
													$this->resolution, $this->id_task, $this->sla_disabled,
													$this->id_incident_type, $this->email_copy,
													$this->email_notify, $this->id_parent, $this->epilog);
					if ($result) {
						$this->id_incident = $result;
						
						// Insert file if exist
						$status = $this->getFileUploadStatus('file');
						$message = $this->translateFileUploadStatus($message);
						if ($message === true) {
							$this->insertIncidentFile('file');
						}
						
						$message = "<h2 class='suc'>".__('Successfully created')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while creating the ticket')."</h2>";
					}
					$incidents = new Incidents();
					$incidents->show($message);
					break;
				case 'insert_workunit':
					if ($this->id_incident > 0) {
						$date_workunit = (string) $system->getRequest('date_workunit', date ("Y-m-d"));
						$duration_workunit = (float) $system->getRequest('duration_workunit', $system->getConfig('iwu_defaultime'));
						$description_workunit = (string) $system->getRequest('description_workunit', "");
						$workunit = new Workunit();
						$result = $workunit->insertWorkUnit($system->getConfig('id_user'), $date_workunit,
															$duration_workunit, $description_workunit, false, $this->id_incident);
						unset($workunit);
						if ($result) {
							$message = "<h2 class='suc'>".__('Successfully created')."</h2>";
						} else {
							$message = "<h2 class='error'>".__('An error ocurred while adding the workunit')."</h2>";
						}
						$this->showIncident($this->tab, $message);
					}
					break;
				case 'insert_file':
					if ($this->id_incident > 0) {
						
						$status = $this->getFileUploadStatus('file');
						$message = $this->translateFileUploadStatus($message);
						
						if ($message === true) {
							$result = $this->insertIncidentFile('file');
							if ($result) {
								$message = "<h2 class='suc'>".__('File added')."</h2>";
							} else {
								$message = "<h2 class='error'>".__('An error ocurred while uploading the file')."</h2>";
							}
						}
						$this->showIncident($this->tab, $message);
					}
					break;
				case 'update_incident':
					$this->showIncidentSimpleForm();
					break;
				case 'quick_update_incident':
					if ($this->id_incident > 0) {

						$quick_update_type = $system->getRequest('quick_update_type', "");
						$quick_update_value = $system->getRequest('quick_update_value', "");

						if ($quick_update_type && $quick_update_value) {

							$result = $this->quickIncidentUpdate($this->id_incident, $quick_update_type, $quick_update_value);
							
							if ($result) {
								switch ($quick_update_type) {
									case 'priority':
										$this->priority = $quick_update_value;
										break;
									case 'owner':
										$this->id_owner = $quick_update_value;
										break;
									case 'resolution':
										$this->priority = $quick_update_value;
										break;
									case 'status':
										$this->status = $quick_update_value;
										break;
								}
								$message = "<h2 class='suc'>".__('Successfully updated')."</h2>";
							} else {
								$message = "<h2 class='error'>".__('An error ocurred while updating the incident')."</h2>";
							}
						}
						$this->showIncident($this->tab, $message);
					}
					break;
				case 'update_workunit':
					if ($this->id_incident > 0) {
						$id_workunit = (int) $system->getRequest('id_workunit', -1);
						$date_workunit = (string) $system->getRequest('date_workunit', date ("Y-m-d"));
						$duration_workunit = (float) $system->getRequest('duration_workunit', $system->getConfig('iwu_defaultime'));
						$description_workunit = (string) $system->getRequest('description_workunit', "");
						$workunit = new Workunit();
						$result = $workunit->updateWorkUnit($id_workunit, $system->getConfig('id_user'), $date_workunit,
															$duration_workunit, $description_workunit, false, $this->id_incident);
						unset($workunit);
						if ($result) {
							$message = "<h2 class='suc'>".__('Successfully updated')."</h2>";
						} else {
							$message = "<h2 class='error'>".__('An error ocurred while updating the workunit')."</h2>";
						}
						$this->showIncident($this->tab, $message);
					}
					break;
				case 'delete_incident':
					$result = $this->deleteIncident($this->id_incident);
					if ($result) {
						$this->id_incident = -1;
						$message = "<h2 class='suc'>".__('Successfully deleted')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while deleting the ticket')."</h2>";
					}
					$this->showIncidentSimpleForm($message);
					break;
				case 'delete_file':
					$this->showIncident($this->tab, $message);
					break;
				default:
					if ($this->id_incident > 0) {
						$this->showIncident($this->tab);
					} else {
						$this->showIncidentSimpleForm();
					}
			}
		}
		else {
			$this->showNoPermission();
		}
	}
	
	private function showNoPermission ($error = false) {
		$system = System::getInstance();
		
		audit_db ($system->getConfig('id_user'), $REMOTE_ADDR, "ACL Violation",
			"Trying to access to ticket section");
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
				case 'getIncidentTypeFields':
					$id_incident_type = $system->getRequest('id_incident_type');
					$id_incident = $system->getRequest('id_incident');		
					$fields = incidents_get_all_type_field ($id_incident_type, $id_incident);
					
					$fields_final = array();
					foreach ($fields as $f) {
						$f["data"] = safe_output($f["data"]);

						array_push($fields_final, $f);
					}

					echo json_encode($fields_final);
					return;
			}
		}
	}
	
}

?>
