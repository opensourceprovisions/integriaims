<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


function check_workunit_permission ($id_workunit) {
	global $config;
	
	// Delete workunit with ACL / Project manager check
	$workunit = get_db_row ('tworkunit', 'id', $id_workunit);
	if ($workunit === false)
		return false;
	
	$id_user = $workunit["id_user"];
	$id_task = get_db_value ("id_task", "tworkunit_task", "id_workunit", $workunit["id"]);
	$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
	if ($id_user != $config["id_user"]
		&& ! give_acl ($config["id_user"], 0,"PM")
		&& ! project_manager_check ($id_project))
		return false;
	
	return true;
}

function delete_task_workunit ($id_workunit) {
	global $config;
	
	if (! check_workunit_permission ($id_workunit))
		return false;
	
	$sql = sprintf ('DELETE FROM tworkunit
		WHERE id = %d', $id_workunit);
	process_sql ($sql);
	$sql = sprintf ('DELETE FROM tworkunit_task
		WHERE id_workunit = %d', $id_workunit);
	return (bool) process_sql ($sql);
}

function lock_task_workunit ($id_workunit) {
	global $config;
	
	if (! check_workunit_permission ($id_workunit))
		return false;
	$sql = sprintf ('UPDATE tworkunit SET locked = "%s" WHERE id = %d',
		$config['id_user'], $id_workunit);
	return (bool) process_sql ($sql);
}

function create_workunit ($incident_id, $wu_text, $user, $timeused = 0, $have_cost = 0, $profile = "", $public = 1, $send_email = 1, $work_home = 0, $workflow = false) {
	global $config;
	
	$fecha = print_mysql_timestamp();
	$sql = sprintf ('UPDATE tincidencia
		SET affected_sla_id = 0, actualizacion = "%s"  
		WHERE id_incidencia = %d', $fecha, $incident_id);
	process_sql ($sql);
	
	$task_id = get_db_value ('id_task', 'tincidencia', 'id_incidencia', $incident_id);
	
	if (!$workflow) {
		incident_tracking ($incident_id, INCIDENT_WORKUNIT_ADDED);
	}
	
	// Add work unit if enabled
	$sql = sprintf ('INSERT INTO tworkunit (timestamp, duration, id_user, description, public, work_home)
			VALUES ("%s", %.2f, "%s", "%s", %d, %d)', $fecha, $timeused, $user, $wu_text, $public, $work_home);
	$id_workunit = process_sql ($sql, "insert_id");
	
	$sql = sprintf ('INSERT INTO tworkunit_incident (id_incident, id_workunit)
			VALUES (%d, %d)', $incident_id, $id_workunit);
	$res = process_sql ($sql);
	
	if($task_id){
		$sql = sprintf ('INSERT INTO tworkunit_task (id_task, id_workunit)
						VALUES (%d, %d)', $task_id, $id_workunit);
		$res = process_sql ($sql);
	}
	
	if ($res !== false) {
		$email_copy_sql = 'select email_copy from tincidencia where id_incidencia ='.$incident_id.';';
		$email_copy = get_db_sql($email_copy_sql);
		if ($send_email == 1){
			if ($email_copy != "") { 
				mail_incident ($incident_id, $user, $wu_text, $timeused, 10, 7);
			}
			if (($config["email_on_incident_update"] != 2) && ($config["email_on_incident_update"] != 4)) {
				mail_incident ($incident_id, $user, $wu_text, $timeused, 10);
			}
		}	
	} else {
		//Delete workunit
		$sql = sprintf ('DELETE FROM tworkunit WHERE id = %d',$id_workunit);
		return false;
	}
	
	return $id_workunit;
}

function create_new_table_multiworkunit ($number=false, $date=false) {
	global $config;
	
	//If not number return empty
	if (!$number) {
		return;
	}
	
	//Set several global variables
	if ($date) {
		$now = $date;
	} else {
		$now = (string) get_parameter ("givendate", date ("Y-m-d H:i:s"));
	}
	$start_date = substr ($now, 0, 10);
	$wu_user = $config["id_user"];
	$id_task = (int) get_parameter ("id_task",0);
	
	echo "<table id='wu_".$number."' class='search-table-button' width='100%'>";
	
	echo "<tr>";
	
	echo "<td colspan=4>";
	echo "<strong>".sprintf(__("Workunit  #%d"),$number)."</strong>";
	echo "</td>";
	
	//If number greater than 1 display a cross to delete workunit
	echo "<td id='del_wu_".$number."' style='text-align:right; padding-right: 10px'>";
	if ($number > 1) {
		echo "<a id='del_wu_".$number."' href='#'><img src='images/cross.png'></a>";
	} else {
		echo "&nbsp;";
	}
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
		
	// Show task combo if none was given.
	echo "<td>";	
	
	echo print_input_text ('start_date_'.$number, $start_date, '', 10, 20, true, __("Date"));
	echo"</td>";
	
	echo "<td colspan='2'>";
	echo combo_task_user_participant ($wu_user,true, $id_task, true, __("Task"), 'id_task_'.$number,true,false,'check_multiplewu_task();');
	echo "</td>";
	
	echo "<td>";
	if (dame_admin ($config['id_user'])) {
		echo combo_roles (true, 'id_profile_'.$number, __('Role'), true);
	} else {
		echo combo_user_task_profile ($id_task, 'id_profile_'.$number, 0, false, true);
	}	
	echo "</td>";
	
	echo "<td>";
	if (dame_admin ($config['id_user'])) {
	
		$params = array();
		$params['input_id'] = 'text-id_username_'.$number;
		$params['input_name'] = 'id_username';
		$params['input_value'] = $wu_user;
		$params['title'] = 'Username';
		$params['return'] = true;
		$params['return_help'] = true;
		
		echo user_print_autocomplete_input($params);
	}	
	echo "</td>";
	
	echo "</tr>";
	
	echo "<tr>";
	
	echo "<td>";
	echo print_input_text ('duration_'.$number, 4, false, 7, 7, true, __('Time used'));
	echo "</td>";
	
	echo "<td>";
	echo print_checkbox ('have_cost_'.$number, 1, false, true, __('Have cost'));
	echo "</td>";
	
	echo "<td>";
	echo print_checkbox ('public_'.$number, 1, true, true, __('Public'));
	echo "</td>";
	
	echo "<td>";
	echo print_checkbox ('forward_'.$number, 1, false, true, __('Forward').
	print_help_tip (__('If this checkbox is activated, propagation will be forward instead backward'), true));
	echo "</td>";
	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td>";
	echo print_checkbox ('work_home_'.$number, 1, false, true, __('Work from home'));
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td colspan=5 style='text-align:left;'>";
	echo print_textarea ('description_'.$number, 4, 30, false, '', true, __('Description'));
	echo "</td>";
	echo "</tr>";
		
	echo "</table>";
}

function create_single_workunit ($number) {
	global $config;
	
	$duration = (float) get_parameter ("duration_".$number);
	$timestamp = (string) get_parameter ("start_date_".$number);
	$description = (string) get_parameter ("description_".$number);
	$have_cost = (bool) get_parameter ("have_cost_".$number);
	$id_task = (int) get_parameter ("id_task_".$number, -1);
	$id_profile = (int) get_parameter ("id_profile_".$number);
	$public = (bool) get_parameter ("public_".$number);
	$id_user = (string) get_parameter ("id_username_".$number, $config['id_user']);
	$wu_user = $id_user;
	$forward = (bool) get_parameter ("forward_".$number);	
	$work_home = (bool) get_parameter ("work_home_".$number);
	
	// Multi-day assigment
	if ($forward && $duration > $config["hours_perday"]) {

		$total_days = ceil ($duration / $config["hours_perday"]);
		$total_days_sum = 0;
		$hours_day = 0;
		for ($i = 0; $i < $total_days; $i++) {
			if (! $forward)
				$current_timestamp = calcdate_business_prev ($timestamp, $i);
			else
				$current_timestamp = calcdate_business ($timestamp, $i);
			
			if (($total_days_sum + 8) > $duration)
				$hours_day = $duration - $total_days_sum;
			else 
				$hours_day = $config["hours_perday"];
			$total_days_sum += $hours_day;
			
			$sql = sprintf ('INSERT INTO tworkunit 
				(timestamp, duration, id_user, description, have_cost, id_profile, public, work_home) 
				VALUES ("%s", %f, "%s", "%s", %d, %d, %d, %d)',
				$current_timestamp, $hours_day, $id_user, $description,
				$have_cost, $id_profile, $public, $work_home);
				
			$id_workunit = process_sql ($sql, 'insert_id');
			if ($id_workunit !== false) {
				$sql = sprintf ('INSERT INTO tworkunit_task 
					(id_task, id_workunit) VALUES (%d, %d)',
					$id_task, $id_workunit);
				$result = process_sql ($sql, 'insert_id');
				if ($result !== false) {
					$result_output = true;
				} else {
					$result_output = false;
				}
			}
			else {
				$result_output = false;
			}
		}
		mail_project (0, $config['id_user'], $id_workunit, $id_task,
			"This is part of a multi-workunit assigment of $duration hours");
	} else {
		// Single day workunit
		$sql = sprintf ('INSERT INTO tworkunit 
				(timestamp, duration, id_user, description, have_cost, id_profile, public, work_home) 
				VALUES ("%s", %.2f, "%s", "%s", %d, %d, %d, %d)',
				$timestamp, $duration, $id_user, $description,
				$have_cost, $id_profile, $public, $work_home);
		$id_workunit = process_sql ($sql, 'insert_id');
		if ($id_workunit !== false) {
			$sql = sprintf ('INSERT INTO tworkunit_task 
					(id_task, id_workunit) VALUES (%d, %d)',
					$id_task, $id_workunit);
			$result = process_sql ($sql, 'insert_id');
			if ($result !== false) {
				$result_output = true;
				audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Spare work unit added", 
						'Workunit for '.$config['id_user'].' added to Task ID #'.$id_task);
				mail_project (0, $config['id_user'], $id_workunit, $id_task);
			}
			else {
				$result_output = false;
			}
		} else {
			$result_output = false;
		}
	}
	
	if ($id_workunit !== false) {
		set_task_completion ($id_task);
	}
	
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU INSERT", "Task $id_task. Desc: $description");
	
	$return = array("task" => $id_task,
					"date" => $timestamp,
					"role" => $id_profile,
					"cost" => $have_cost,
					"public" => $public,
					"user" => $id_user,
					"duration" => $duration,
					"forward" => $forward,
					"description" => $description,
					"result_out" => $result_output,
					"work_home" => $work_home);
	
	return $return;
}

function print_single_workunit_report ($mwur) {
	if ($mwur['result_out']) {
		echo ui_print_success_message (__('Workunit added'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('Problemd adding workunit.'), '', true, 'h3', true);
	}

	echo "<table class='search-table' width='99%'>";
	echo "<tr>";
	echo "<td>";
	echo "<strong>".sprintf(__("Workunit  #%d"),$mwur['id'])."</strong>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "<strong>".__("Date").": </strong>";
	echo $mwur['date'];
	echo "</td>";
	echo "<td colspan='2'>";
	echo "<strong>".__("Task").": </strong>";
	$task_name = get_db_value ("name", "ttask", "id", $mwur['task']);
	echo $task_name;
	echo "</td>";
	echo "<td colspan='2'>";
	echo "<strong>".__("Role").": </strong>";
	$role_name = get_db_value ("name", "trole", "id", $mwur['role']);
	echo $role_name;
	echo "</td>";
	echo "<td colspan='2'>";
	echo "<strong>".__("Username").": </strong>";
	echo $mwur['user'];
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "<strong>".__("Time used").": </strong>";
	echo $mwur['duration'];
	echo " ".__("hours");
	echo "</td>";
	echo "<td>";
	echo "<strong>".__("Have cost").": </strong>";
	echo "</td>";
	echo "<td>";
	echo print_checkbox_extended ("id", "nothing", $mwur['cost'], true, "", "" , true);
	echo "</td>";
	echo "<td>";
	echo "<strong>".__("Public").": </strong>";
	echo "</td>";
	echo "<td>";
	echo print_checkbox_extended ("id", "nothing", $mwur['public'], true, "", "" , true);
	echo "</td>";
	echo "<td>";
	echo "<strong>".__("Forward").": </strong>";
	echo "</td>";
	echo "<td>";
	echo print_checkbox_extended ("id", "nothing", $mwur['forward'], true, "", "" , true);
	echo "</td>";	
	echo "<td>";
	echo "<strong>".__("Split > 1 day").": </strong>";
	echo "</td>";
	echo "<td>";
	echo print_checkbox_extended ("id", "nothing", $mwur['split'], true, "", "" , true);
	echo "</td>";	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td>";
	echo "<strong>".__("Work from home").": </strong>";
	echo "</td>";
	echo "<td>";
	echo print_checkbox_extended ("id", "nothing", $mwur['work_home'], true, "", "" , true);
	echo "</td>";	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td colspan='5'>";
	echo "<strong>".__("Description")."</strong>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='4'>";
	echo $mwur['description'];
	echo "</td>";
	echo "</tr>";
	echo "</table>";	
}

//get user roles of a workunit with a task assigned in a workorder
function workunits_get_user_role ($id_user, $id_wo) {
	
	$roles = false;
	$id_task = get_db_value ('id_task', 'ttodo', 'id', $id_wo);
	if ($id_task) {
		$roles = user_get_task_roles ($id_user, $id_task);
	}
	
	if (!$roles) {
		$roles[0] = __('N/A');
	}
	
	return $roles;
}

function workunits_get_vacation_wu ($id_user, $year) {
	
	$holidays_wu = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task =-1 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");

	if ($holidays_wu) {
		return $holidays_wu;
	}
	
	return array();
}

function workunits_get_work_home_wu ($id_user, $year) {
	
	$work_home_wus = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59' AND tworkunit.work_home=1");

	if ($work_home_wus) {
		return $work_home_wus;
	}
	
	return array();
}

function  workunits_get_worked_project_wu ($id_user, $year) {
	
	$work_project_wus = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");

	if ($work_project_wus) {
		return $work_project_wus;
	}
	
	return array();
}

function  workunits_get_worked_ticket_wu ($id_user, $year) {
	
	$work_ticket_wus = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_incident WHERE tworkunit_incident.id_workunit = tworkunit.id AND tworkunit_incident.id_incident > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");

	if ($work_ticket_wus) {
		return $work_ticket_wus;
	}
	
	return array();
}

function workunits_print_table_massive_edition($id_task=0, $id_profile=0) {
	
	global $config;
	
	echo '<br><h2>'.__('Massive operations over selected items').'</h2>';
	$table = new StdClass;
	$table->class = 'search-table-button';
	$table->width = '100%';
	$table->data = array ();
	$table->colspan = array ();

	// Profile or role
	if (dame_admin ($config['id_user'])) {
		$table->data[0][0] = combo_roles (false, 'id_profile', __('Role'), true, true, '', true);
	}
	else {
		$table->data[0][0] = combo_user_task_profile ($id_task, 'id_profile', $id_profile, false, true, true);
	}

	// Show task combo if none was given.
	if (! $id_task) {
		$table->data[0][1] = combo_task_user_participant ($config['id_user'], true, 0, true, __('Task'), false, false, false, '', true);
	}
	else {
		$table->data[0][1] = combo_task_user_participant ($config['id_user'], true, $id_task, true, __('Task'), false, false, false, true);
	}

	// Various checkboxes

	$table->data[2][0] = print_checkbox ('have_cost', 1, '', true, __('Have cost'));

	$table->data[2][1] = print_checkbox ('keep_cost', 1, '', true, __('Keep cost'));

	$table->data[3][0] = print_checkbox ('public', 1, '', true, __('Public'));

	$table->data[3][1] = print_checkbox ('keep_public', 1, '', true, __('Keep public'));

	$table->colspan[5][0] = 2;
	$table->data[5][0] = print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
	$table->data[5][0] .= print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"', true);

	print_table ($table);	

}
