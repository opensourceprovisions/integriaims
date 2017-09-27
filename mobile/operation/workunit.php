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

class Workunit {
	
	private $id_workunit;
	private $id_task;
	private $id_incident;
	private $date_workunit;
	private $duration_workunit;
	private $description_workunit;
	private $operation;
	
	private $acl = 'PR';
	private $permission = false;
	
	function __construct () {
		$system = System::getInstance();
		
		$this->id_workunit = (int) $system->getRequest('id_workunit', -1);
		$this->id_task = $system->getRequest('id_task', false);
		$this->id_incident = (int) $system->getRequest('id_incident', -1);
		$this->date_workunit = (string) $system->getRequest('date_workunit', date ("Y-m-d"));
		$this->duration_workunit = (float) $system->getRequest('duration_workunit',
			($this->id_incident > 0) ? 0 : $system->getConfig('pwu_defaultime'));
		$this->description_workunit = (string) $system->getRequest('description_workunit', "");
		// insert, update, delete, view or ""
		$this->operation = (string) $system->getRequest('operation', "");
		
		// ACL
		$this->permission = $this->checkPermission($system->getConfig('id_user'), $this->acl,
											$this->operation, $this->id_workunit, $this->id_task,
											$this->id_incident);
	}
	
	public function getPermission () {
		return $this->permission;
	}
	
	public function checkPermission ($id_user, $acl = 'PR', $operation = '', $id_workunit = -1, $id_task = -1, $id_incident = -1) {
		$system = System::getInstance();
		
		$permission = false;
		if (dame_admin($id_user)) {
			$permission = true;
			
		} else {
			// Section access
			if ($system->checkACL($acl)) {
				// workunit for task
				if ($id_task !== false && $id_task > 0) {
					if ( include_once ($system->getConfig('homedir')."/include/functions_projects.php") ) {
						$task_access = get_project_access ($id_user, 0, $id_task, false, true);
						// Task access
						if ($task_access["write"] || $task_access["manage"]) {
							// If the workunit exists, should belong to the user
							if ($operation != "" && $operation != "insert_workunit") {
								$user_workunit = get_db_value("id_user", "tworkunit", "id", $id_workunit);
								if (strcasecmp($id_user, $user_workunit) == 0) {
									$permission = true;
								}
							} else {
								$permission = true;
							}
						}
					}
				// workunit for incident
				} elseif ($id_incident > 0) {
					// Incident access
					if ($system->checkACL('IW') || $system->checkACL('IM')) {
						// If the workunit exists, should belong to the user
						if ($operation != "" && $operation != "insert_workunit") {
							$user_workunit = get_db_value("id_user", "tworkunit", "id", $id_workunit);
							if (strcasecmp($id_user, $user_workunit) == 0) {
								$permission = true;
							}
						} else {
							$permission = true;
						}
					}
				} else {
					$permission = true;
				}
			}
		}
		// With this operations, the workunit should have id
		if ( ($operation == "view" || $operation == "update_workunit" || $operation == "delete_workunit")
				&& $id_workunit < 0) {
			$permission = false;
		}
		
		return $permission;
	}
	
	public function setId ($id_workunit) {
		$this->id_workunit = $id_workunit;
	}
	
	private function setValues ($id_workunit, $id_task, $id_incident, $date_workunit, $duration_workunit, $description_workunit, $operation) {
		$this->id_workunit = $id_workunit;
		$this->id_task = $id_task;
		$this->id_incident = $id_incident;
		$this->date_workunit = $date_workunit;
		$this->duration_workunit = $duration_workunit;
		$this->description_workunit = $description_workunit;
		$this->operation = $operation;
	}
	
	public function insertWorkUnit ($id_user, $date_workunit, $duration_workunit = 4, $description_workunit = "", $id_task = false, $id_incident = -1) {
		$system = System::getInstance();
		
		$sql = sprintf ("INSERT INTO tworkunit 
				(timestamp, duration, id_user, description) 
				VALUES ('%s', %.2f, '%s', '%s')",
				$date_workunit, $duration_workunit, $id_user, $description_workunit);
		$id_workunit = process_sql ($sql, "insert_id");
		
		if ($id_workunit) {
			if ($id_task !== false && $id_task !== 0) {
				$sql = sprintf ("INSERT INTO tworkunit_task 
						(id_task, id_workunit) VALUES (%d, %d)",
						$id_task, $id_workunit);
				$result = process_sql ($sql, 'insert_id');
				if ($result) {
					include_once ($system->getConfig('homedir')."/include/functions_tasks.php");
					set_task_completion ($this->id_task);
					return $id_workunit;
				}
			} elseif ($id_incident > 0) {
				$sql = sprintf ("INSERT INTO tworkunit_incident 
						(id_incident, id_workunit) VALUES (%d, %d)",
						$id_incident, $id_workunit);
				$result = process_sql ($sql, 'insert_id');
				if ($result) {
					return $id_workunit;
				}
			} else {
				return $id_workunit;
			}
		}
		
		return false;
	}
	
	public function updateWorkUnit ($id_workunit, $id_user, $date_workunit, $duration_workunit = 4, $description_workunit = "", $id_task = false, $id_incident = -1) {
		$system = System::getInstance();
		
		$sql = sprintf ("UPDATE tworkunit
			SET timestamp = '%s', duration = %.2f, description = '%s',
			id_user = '%s' WHERE id = %d",
			$date_workunit, $duration_workunit, $description_workunit,
			$id_user, $id_workunit);
		$result = process_sql ($sql);
		
		$old_id_task = get_db_value("id_task", "tworkunit_task", "id_workunit", $id_workunit);
		if ($old_id_task !== false && $old_id_task != $id_task) {
			process_sql ("DELETE FROM tworkunit_task WHERE id_workunit = ".$id_workunit);
		}
		$old_id_incident = get_db_value("id_incident", "tworkunit_incident", "id_workunit", $id_workunit);
		if ($old_id_incident && $old_id_incident != $id_incident) {
			process_sql ("DELETE FROM tworkunit_incident WHERE id_workunit = ".$id_workunit);
		}
		
		if ($id_task !== false && $id_task !== 0) {
			$sql = sprintf ("INSERT INTO tworkunit_task 
					(id_task, id_workunit) VALUES (%d, %d)",
					$id_task, $id_workunit);
			$result = process_sql ($sql, 'insert_id');
			if ($result) {
				include_once ($system->getConfig('homedir')."/include/functions_tasks.php");
				set_task_completion ($id_task);
				return true;
			}
		} elseif ($id_incident > 0) {
			$sql = sprintf ("INSERT INTO tworkunit_incident 
					(id_incident, id_workunit) VALUES (%d, %d)",
					$id_incident, $id_workunit);
			$result = process_sql ($sql, 'insert_id');
			if ($result) {
				return true;
			}
		} else {
			return true;
		}
		
		return false;
	}
	
	public function deleteWorkUnit ($id_workunit) {
		
		$result = process_sql ("DELETE FROM tworkunit WHERE id = ".$id_workunit);
		if ($result) {
			$id_task = get_db_value("id_task", "tworkunit_task", "id_workunit", $id_workunit);
			$id_incident = get_db_value("id_incident", "tworkunit_incident", "id_workunit", $id_workunit);
			if ($id_task) {
				$result = process_sql ("DELETE FROM tworkunit_task WHERE id_workunit = ".$id_workunit);
				if ($result) {
					$system = System::getInstance();
					include_once ($system->getConfig('homedir')."/include/functions_tasks.php");
					set_task_completion ($this->id_task);
					return true;
				}
			} elseif ($id_incident) {
				$result = process_sql ("DELETE FROM tworkunit_incident WHERE id_workunit = ".$id_workunit);
				if ($result) {
					return true;
				}
			} else {
				return true;
			}
		}
		
		return false;
	}
	
	public function getWorkUnitForm ($action = "index.php?page=workunit", $method = "POST") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		if ($this->id_workunit > 0) {
			$workunit = get_db_row ("tworkunit", "id", $this->id_workunit);
			if ($workunit) {
				$id_task = get_db_value ("id_task", "tworkunit_task", "id_workunit", $workunit['id']);
				$id_incident = get_db_value ("id_incident", "tworkunit_incident", "id_workunit", $workunit['id']);
				if ($id_incident == false) {
					$id_incident = -1;
				}
				$date = strtotime($workunit['timestamp']);
				$this->setValues ($workunit['id'], $id_task, $id_incident, date ("Y-m-d", $date),
									$workunit['duration'], $workunit['description'], 'view');
			}
		}
		
		$options = array (
			'id' => 'form-workunit',
			'action' => $action,
			'method' => $method
			);
		$ui->beginForm($options);
			// Date
			$options = array(
				'name' => 'date_workunit',
				'label' => __('Date'),
				'value' => $this->date_workunit,
				'placeholder' => __('Date')
				);
			$ui->formAddInputDate($options);
			// Hours
			$options = array(
				'name' => 'duration_workunit',
				'label' => __('Hours'),
				'type' => 'number',
				'step' => 'any',
				'min' => '0.00',
				'value' => $this->duration_workunit,
				'placeholder' => __('Hours')
				);
			$ui->formAddInput($options);
			
			// Tasks combo or hidden id_incident
			if ($this->id_incident < 0) {
				
				$sql = "SELECT ttask.id, tproject.name, ttask.name
						FROM ttask, trole_people_task, tproject
						WHERE ttask.id_project = tproject.id
							AND tproject.disabled = 0
							AND ttask.id = trole_people_task.id_task
							AND trole_people_task.id_user = '".$system->getConfig('id_user')."'
						ORDER BY tproject.name, ttask.name";
				if (dame_admin ($system->getConfig('id_user'))) {
					$sql = "SELECT ttask.id, tproject.name, ttask.name 
							FROM ttask, tproject
							WHERE ttask.id_project = tproject.id
								AND tproject.disabled = 0
							ORDER BY tproject.name, ttask.name";
				}
				$tasks = get_db_all_rows_sql ($sql);
				
				$values[-3] = "(*) ".__('Not justified');
				$values[-2] = "(*) ".__('Not working for disease');
				$values[-1] = "(*) ".__('Vacations');
				//$values[0] =  __('N/A');
				if ($tasks) {
					foreach ($tasks as $task) {
						$values[$task[0]] = array('optgroup' => $task[1], 'name' => $task[2]);
					}
				}
				$selected = ($this->id_task === false) ? 0 : $this->id_task;
				$options = array(
					'name' => 'id_task',
					'title' => __('Task'),
					'label' => __('Task'),
					'items' => $values,
					'selected' => $selected
					);
				$ui->formAddSelectBox($options);
			} else {
				$options = array(
					'type' => 'hidden',
					'name' => 'id_incident',
					'value' => $this->id_incident
					);
				$ui->formAddInput($options);
			}
			// Description
			$options = array(
					'name' => 'description_workunit',
					'label' => __('Description'),
					'value' => $this->description_workunit
					);
			$ui->formAddHtml($ui->getTextarea($options));
			// Hidden operation (insert or update+id)
			if ($this->id_workunit < 0) {
				$options = array(
					'type' => 'hidden',
					'name' => 'operation',
					'value' => 'insert_workunit'
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
					'value' => 'update_workunit'
					);
				$ui->formAddInput($options);
				$options = array(
					'type' => 'hidden',
					'name' => 'id_workunit',
					'value' => $this->id_workunit
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
	
	public function showWorkUnit ($message = "") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		// Header
		$back_href = "index.php?page=home";
		$right_href = "index.php?page=workunits";
		if ($this->id_workunit < 0) {
			$title = __("Workunit");
		} else {
			$title = __("Workunit")."&nbsp;#".$this->id_workunit;
		}
		$ui->createHeader($title,
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => $back_href
					)
				),
			$ui->createHeaderButton(
				array('icon' => 'grid',
					'pos' => 'right',
					'text' => __('List'),
					'href' => $right_href
					)
				)
			);
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
			$html = $this->getWorkUnitForm();
			$ui->contentAddHtml($html);
		$ui->endContent();
		// Foooter buttons
		// Add
		if ($this->id_workunit < 0) {
			$button_add = "<a onClick=\"$('#form-workunit').submit();\" data-role='button' data-icon='plus'>"
							.__('Add')."</a>\n";
		} else {
			$button_add = "<a onClick=\"$('#form-workunit').submit();\" data-role='button' data-icon='refresh'>"
							.__('Update')."</a>\n";
		}
		// Delete
		$button_delete = '';
		if ($this->id_workunit > 0) {
			$button_delete = "<a href='index.php?page=workunits&operation=delete_workunit&id_workunit=".$this->id_workunit."' 
									data-role='button' data-ajax='false' data-icon='delete'>".__('Delete')."</a>\n";
		}
		$ui->createFooter("<div data-type='horizontal' data-role='controlgroup'>$button_add"."$button_delete</div>");
		$ui->showFooter();
		$ui->showPage();
	}
	
	public function show () {
		$system = System::getInstance();
		
		if ($this->permission) {
			$message = "";
			switch ($this->operation) {
				case 'insert_workunit':
					$result = $this->insertWorkUnit($system->getConfig('id_user'),
													$this->date_workunit, $this->duration_workunit,
													$this->description_workunit, $this->id_task,
													$this->id_incident);
					if ($result) {
						$this->setId($result);
						$message = "<h2 class='suc'>".__('Successfully created')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while creating the workunit')."</h2>";
					}
					break;
				case 'update_workunit':
					$result = $this->updateWorkUnit($this->id_workunit, $system->getConfig('id_user'),
													$this->date_workunit, $this->duration_workunit,
													$this->description_workunit, $this->id_task,
													$this->id_incident);
					if ($result) {
						$message = "<h2 class='suc'>".__('Successfully updated')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while updating the workunit')."</h2>";
					}
					break;
				case 'delete_workunit':
					$result = $this->deleteWorkUnit($this->id_workunit);
					if ($result) {
						$this->id_workunit = -1;
						$message = "<h2 class='suc'>".__('Successfully deleted')."</h2>";
					} else {
						$message = "<h2 class='error'>".__('An error ocurred while deleting the workunit')."</h2>";
					}
					break;
				case 'view':
					break;
				default:
			}
			$this->showWorkUnit($message);
		}
		else {
			switch ($this->operation) {
				case 'insert_workunit':
					$error['title_text'] = __('You can\'t insert this workunit');
					$error['content_text'] = __('You have done an operation that
						surpass your permissions. Is possible that you can\'t add a
						workunit to this task. Please contact to system administrator 
						if you need assistance. <br><br>Please know that all attempts 
						to access this page are recorded in security logs of Integria 
						System Database');
					break;
				case 'update_workunit':
					$error['title_text'] = __('You can\'t update this workunit');
					$error['content_text'] = __('You have done an operation that
						surpass your permissions. Is possible that you can\'t add a
						workunit to this task. Please contact to system administrator 
						if you need assistance. <br><br>Please know that all attempts 
						to access this page are recorded in security logs of Integria 
						System Database');
					break;
				case 'delete_workunit':
					$error['title_text'] = __('You can\'t delete this workunit');
					$error['content_text'] = __('You have done an operation that surpass
						your permissions. Please contact to system administrator 
						if you need assistance. <br><br>Please know that all attempts 
						to access this page are recorded in security logs of Integria 
						System Database');
					break;
			}
			$this->showNoPermission($error);
		}
	}
	
	public function showNoPermission ($error = false) {
		$system = System::getInstance();
		
		audit_db ($system->getConfig('id_user'), $REMOTE_ADDR, "ACL Violation",
			"Trying to access to workunit section");
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
	
	public function ajax ($parameter2 = false) {
		// Fill me in the future
	}
	
}

?>
