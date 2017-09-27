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

// Load global vars

global $config;

check_login ();

require_once ('include/functions_tasks.php');
require_once ('include/functions_workunits.php');
require_once ('include/functions_user.php');

if (defined ('AJAX')) {

	global $config;

	$get_task_roles = (bool) get_parameter ('get_task_roles');
	$get_user_task_roles = (bool) get_parameter ('get_user_task_roles');
	
	//Get only user roles
	if ($get_user_task_roles) {

		$id_user = get_parameter ('id_user');
		$id_task = get_parameter ('id_task');
		
		$roles = user_get_task_roles ($id_user, $id_task);
		
		echo json_encode($roles);

		return;
	}

 	// Get the roles assigned to user in the project of a given task
	if ($get_task_roles) {
		$id_user = get_parameter ('id_user');
		$id_task = get_parameter ('id_task');

		$id_project = get_db_value('id_project','ttask','id',$id_task);
		
		// If the user is Project Manager, all the roles are retrieved. 
		// If not, only the assigned roles
		
		if(give_acl($id_user, 0, "PM")) {
			$roles = get_db_all_rows_filter('trole',array(),'id, name');
		}
		else {
			$roles = get_db_all_rows_sql('SELECT trole.id, trole.name FROM trole, trole_people_project WHERE id_role = trole.id AND id_user = "'.$id_user.'" AND id_project = '.$id_project);
		}	

		echo json_encode($roles);

		return;
	}
	
	if (get_parameter("get_new_mult_wu")) {
		$number = get_parameter ("next");
		$date = get_parameter("given_date");
		create_new_table_multiworkunit($number, $date);	
		
		return;
	}
}

$operation = (string) get_parameter ("operation");
$now = (string) get_parameter ("givendate", date ("Y-m-d H:i:s"));
$public = (bool) get_parameter ("public", 1);
$id_project = (int) get_parameter ("id_project");
$id_workunit = (int) get_parameter ('id_workunit');
$id_task = (int) get_parameter ("id_task",0);
$id_incident = (int) get_parameter ("id_incident", 0);
$work_home = get_parameter ("work_home", 0);
$back_to_wu = get_parameter("back_to_wu", 0);
$user = get_parameter ("user");
$timestamp_h = get_parameter ("timestamp_h");
$timestamp_l = get_parameter ("timestamp_l");

if ($id_task == 0){
    // Try to get id_task from tworkunit_task
    $id_task = get_db_sql ("SELECT id_task FROM tworkunit_task WHERE id_workunit = $id_workunit");
}

// If id_task is set, ignore id_project and get it from the task
if ($id_task) {
	$id_project = get_db_value ('id_project', 'ttask', 'id', $id_task);
}

if ($id_incident == 0){
	$id_incident = get_db_value ('id_incident', 'tworkunit_incident', 'id_workunit', $id_workunit);
}

if ($id_task >0){ // Skip vacations, holidays etc

	if (! user_belong_task ($config["id_user"], $id_task) && !give_acl($config["id_user"], 0, "UM") ){
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task workunit form without permission");
		no_permission();
	}
}

// Lock Workunit
if ($operation == "lock") {
	$success = lock_task_workunit ($id_workunit);
	if (! $success) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation",
			"Trying to lock WU $id_workunit without rigths");
		if (!defined ('AJAX'))
			include ("general/noaccess.php");
		return;
	}
	
	$result_output = ui_print_success_message (__('Locked successfully'), '', true, 'h3', true);
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Work unit locked",
		"Workunit for ".$config['id_user']);
	
	if (defined ('AJAX')) {
		echo '<img src="images/rosette.png" title="'.__('Locked by').' '.$config['id_user'].'" />';
		print_user_avatar ($config['id_user'], true);
		return;
	}
}

if ($id_workunit) {
	$sql = sprintf ('SELECT *
		FROM tworkunit
		WHERE tworkunit.id = %d', $id_workunit);
	$workunit = get_db_row_sql ($sql);
	
	$belong_to_ticket = get_db_value_sql("SELECT * FROM tworkunit_incident WHERE id_workunit = ".$id_workunit);

	if (($workunit === false) OR $belong_to_ticket) {
		require ("general/noaccess.php");
		return;
	}
	
//	$id_task = $workunit['id_task'];
//	$id_project = get_db_value ('id_project', 'ttask', 'id', $id_task);

	$id_user = $workunit['id_user'];
	$wu_user = $id_user;
	$duration = $workunit['duration']; 
	$description = $workunit['description'];
	$have_cost = $workunit['have_cost'];
	$id_profile = $workunit['id_profile'];
	$now = $workunit['timestamp'];
	$public = (bool) $workunit['public'];
	$now_date = substr ($now, 0, 10);
	$now_time = substr ($now, 10, 8);
	$work_home = $workunit['work_home'];
	
	if ($id_user != $config["id_user"] && ! project_manager_check ($id_project) ) {
		if (!give_acl($config["id_user"], 0, "UM")){
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation",
			"Trying to access non owned workunit");
			require ("general/noaccess.php");
			return;
		}
	}
}
else {
	$id_user = $config["id_user"];
	$wu_user = $id_user;
	$duration = $config["pwu_defaultime"]; 
	$description = "";
	$id_inventory = array();
	$have_cost = false;
	$public = true;
	$id_profile = "";
	$work_home = false;
	// $now is passed as parameter or get current time by default
}

// Insert workunit
if ($operation == 'insert') {
	$duration = (float) get_parameter ("duration");
	$timestamp = (string) get_parameter ("start_date");
	$description = (string) get_parameter ("description");
	$have_cost = (bool) get_parameter ("have_cost");
	$id_task = (int) get_parameter ("id_task", -1);
	$id_profile = (int) get_parameter ("id_profile");
	$public = (bool) get_parameter ("public");
	$forward = (bool) get_parameter ("forward");
	$split = (bool) get_parameter ("split");
	$id_user = (string) get_parameter ("id_username", $config['id_user']);
	$wu_user = $id_user;
	$work_home = get_parameter ("work_home");
	// Multi-day assigment
	// Forward
	if (($forward) && ($duration > $config["hours_perday"])) {
		$total_days = ceil ($duration / $config["hours_perday"]);
		$total_days_sum = 0;
		$hours_day = 0;
		for ($i = 0; $i < $total_days; $i++) {
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
					$result_output = ui_print_success_message (__('Workunit added'), '', true, 'h3', true);
				} else {
					$result_output = ui_print_error_message (__('Problem adding workunit.'), '', true, 'h3', true);
				}
			}
			else {
				$result_output = ui_print_error_message (__('Problem adding workunit.'), '', true, 'h3', true);
			}
		}
		mail_project (0, $config['id_user'], $id_workunit, $id_task,
			"This is part of a multi-workunit assigment of $duration hours");
	// backward
	}
	elseif (($split) && ($duration > $config["hours_perday"])) {
		$total_days = ceil ($duration / $config["hours_perday"]);
		$total_days_sum = 0;
		$hours_day = 0;
		for ($i = 0; $i < $total_days; $i++) {
			$current_timestamp = calcdate_business_prev ($timestamp, $i);
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
					$result_output = ui_print_success_message (__('Workunit added'), '', true, 'h3', true);
				} else {
					$result_output = ui_print_error_message (__('Problem adding workunit.'), '', true, 'h3', true);
				}
			}
			else {
				$result_output = ui_print_error_message (__('Problem adding workunit.'), '', true, 'h3', true);
			}
		}
		mail_project (0, $config['id_user'], $id_workunit, $id_task,
			"This is part of a multi-workunit assigment of $duration hours");
	}
	else {
		if (!$id_task) {
			if ($duration > 24 /*max hours in one day*/) {
				$result_output = ui_print_error_message (__('Workunit must be less than 24 hours of tasks'), '', true, 'h3', true);
			}
		}
		else {
			$tasks = sprintf ('SELECT id_workunit FROM tworkunit_task WHERE id_task = %d', $id_task);
			$tasks = process_sql ($tasks);
			$num_hours2 = 0;
			if (is_array($tasks) || is_object($tasks)){
				foreach ($tasks as $task) {
					$sql_hours = sprintf('SELECT duration FROM tworkunit WHERE id = %d AND timestamp = "%s"', $task['id_workunit'], $timestamp);
					$num_hours = process_sql($sql_hours);
					
					if ($num_hours) {
						$num_hours2 += $num_hours[0]['duration'];
					}
				}
			}
			if (($duration + $num_hours2) > 24 /*max hours in one day*/) {
				$result_output = ui_print_error_message (__('Workunit must be less than 24 hours of tasks'), '', true, 'h3', true);
			}
			else {
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
						$result_output = ui_print_success_message (__('Workunit added'), '', true, 'h3', true);
						audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Spare work unit added", 
								'Workunit for '.$config['id_user'].' added to Task ID #'.$id_task);
						mail_project (0, $config['id_user'], $id_workunit, $id_task);
					}
					else {
						$result_output = ui_print_error_message (__('Problemd adding workunit.'), '', true, 'h3', true);
					}
				} else {
					$result_output = ui_print_error_message (__('Problemd adding workunit.'), '', true, 'h3', true);
				}
			}
		}
	}
	
	if ($id_workunit !== false) {
		set_task_completion ($id_task);
	}
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU", "Inserted PWU. Task: $id_task. Desc: $description");
}


if ($operation == "delete") {
	$success = delete_task_workunit ($id_workunit);
	if (! $success) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation",
			"Trying to delete WU $id_workunit without rigths");
		include ("general/noaccess.php");
		return;
	}
	
	$result_output = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Work unit deleted", "Workunit for ".$config['id_user']);
	
	if (defined ('AJAX'))
		return $success;
}

// Edit workunit
if ($operation == 'update') {
	$duration = (float) get_parameter ("duration");
	$timestamp = (string) get_parameter ("start_date");
	$start_date = $timestamp;
	
	$description = (string) get_parameter ("description");
	$have_cost = (bool) get_parameter ("have_cost");
	$id_profile = (int) get_parameter ("id_profile");
	$public = (bool) get_parameter ("public");
	$wu_user = (string) get_parameter ("id_username", $config['id_user']);
	$id_task = (int) get_parameter ("id_task",0);
	$work_home = get_parameter ("work_home");
	
	// UPDATE WORKUNIT
	$sql = sprintf ('UPDATE tworkunit
		SET timestamp = "%s", duration = %.2f, description = "%s",
		have_cost = %d, id_profile = %d, public = %d, id_user = "%s",
		work_home = %d 
		WHERE id = %d',
		$timestamp, $duration, $description, $have_cost,
		$id_profile, $public, $wu_user, $work_home, $id_workunit);
	$result = process_sql ($sql);

	if ($id_task !=0) {
	    // Old old association
	    process_sql ("DELETE FROM tworkunit_task WHERE id_workunit = $id_workunit");
	    // Create new one
            $sql = sprintf ('INSERT INTO tworkunit_task
                            (id_task, id_workunit) VALUES (%d, %d)',
                                        $id_task, $id_workunit);
            $result = process_sql ($sql, 'insert_id');
	}
	$result_output = ui_print_success_message (__('Workunit updated'), '', true, 'h3', true);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU", "Updated PWU. $description");
	
	if ($result !== false) {
		set_task_completion ($id_task);
	}
}

$multiple_wu_report = array();

if ($operation == 'multiple_wu_insert') {
	
	//Walk post array looking for 
	$i = 1;
	while(true) {
		
		if (!get_parameter("start_date_".$i)) {
			break;
		} 
		
		//Add single workunit
		$res = create_single_workunit($i);
		
		$res['id'] = $i;
		
		array_push($multiple_wu_report, $res);
		
		//Look next item
		$i++;
	}
}



echo "<div id='tabs'>";

if ($id_workunit) {
	echo "<h2>" . __('Update workunit');
}
else {
	echo "<h2>" . __('Add workunit');
}
echo "<h4>";
if ($id_task) {
	echo get_db_value ('name', 'ttask', 'id', $id_task);
}
else
	echo __("New work unit");
echo integria_help ("user_spare_workunit", true);
echo "<ul class='ui-tabs-nav'>";

echo "<ul class='ui-tabs-nav'>";
//If single workunit update multiple addition is disabled
if ($id_workunit) {
	echo "<li id='tabmenu2' class='ui-tabs-disabled'>";
} else {
	
	//If the multiple_wu_insert option was sent this tab is selected
	if ($operation == 'multiple_wu_insert') {
		echo "<li id='tabmenu2' class='ui-tabs-selected'>";
	} else {
		echo "<li id='tabmenu2' class='ui-tabs'>";
	}
}
	echo "<a href='#tab2' title='".__("Multiple WU")."'><img src='images/multiple_workunits_tab.png' /></a>";
	echo "</li>";
	
//If the multiple_wu_insert option was sent single wu is disabled
if ($operation == 'multiple_wu_insert') {
	echo "<li id='tabmenu1' class='ui-tabs-disabled'>";
} else {
	echo "<li id='tabmenu1' class='ui-tabs-selected'>";
}
echo "<a href='#tab1' title='".__("Single WU")."'><img src='images/workunit_tab.png' /></a>";
echo "</li>";
if ($sec == 'projects') {
	echo "<li id='tabmenu3' class='ui-tabs-disabled'>";

	if ($back_to_wu) {
		echo "<a href='index.php?sec=users&sec2=operation/users/user_workunit_report&id=" . $user . "&timestamp_h=" . $timestamp_h . "&timestamp_l=".$timestamp_l."'><img src='images/flecha_volver.png' /></a>";
	} else {
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=" . $id_project . "&id_task=" . $id_task . "&operation=view'><img src='images/flecha_volver.png' /></a>";
	}
	echo "</li>";
}
echo "</ul>";
echo "</h4>";
echo "</div>";

//If we inserted multiple workunits then 
if ($operation == 'multiple_wu_insert') {
	echo "<div id='tab1' class='ui-tabs-panel ui-tabs-hide'>"; //Single WU
}
else {
	echo "<div id='tab1' class='ui-tabs-panel'>"; //Single WU
}

if ($id_workunit) {
	$wu_user = get_db_value ('id_user', 'tworkunit', 'id', $id_workunit);
}
else {
	$wu_user = $config["id_user"];
}

//Print result output if any
if (isset($result_output)) {
	echo $result_output;
}

$table = new StdClass;
$table->class = 'search-table-button';
$table->width = '100%';
$table->data = array ();
$table->colspan = array ();
$table->colspan[6][0] = 3;

if (!isset($start_date))
	$start_date = substr ($now, 0, 10);

$table->data[0][0] = print_input_text ('start_date', $start_date, '', 10, 20,
	true, __('Date'));

// Profile or role
if (dame_admin ($config['id_user'])) {
	$table->colspan[1][0] = 3;
	$table->data[1][0] = combo_roles (true, 'id_profile', __('Role'), true, true, $id_profile);
	//$table->data[0][1] = combo_roles_people_task ($id_task, $config['id_user'], __('Role'));
}
else {
	$table->colspan[1][0] = 3;
	$table->data[1][0] = combo_user_task_profile ($id_task, 'id_profile',
		$id_profile, false, true);
}

// Show task combo if none was given.
if (! $id_task) {
	$table->data[0][1] = combo_task_user_participant ($wu_user,
		true, 0, true, __('Task'));
}
else {
	$table->data[0][1] = combo_task_user_participant ($wu_user,
	true, $id_task, true, __('Task'));
}

// Time used
$table->data[2][0] = print_input_text ('duration', $duration, '', 7, 7,
	true, __('Time used'));

if (dame_admin ($config['id_user'])) {
	$table->colspan[2][1] = 3;
	
	$params = array();
	$params['input_id'] = 'text-id_username';
	$params['input_name'] = 'id_username';
	$params['input_value'] = $wu_user;
	$params['title'] = 'Username';
	$params['return'] = true;
	$params['return_help'] = true;
	$params['attributes'] = "style='width:210px;'";
	
	$table->data[2][1] = user_print_autocomplete_input($params);
}

// Various checkboxes
$table->data[3][0] = print_checkbox ('have_cost', 1, $have_cost, true,
	__('Have cost'));
$table->data[3][1] = print_checkbox ('public', 1, $public, true, __('Public'));

if (! $id_workunit) {
	$table->data[4][0] = print_checkbox ('forward', 1, 
		false, true, __('Forward') . print_help_tip (__('If this checkbox is activated, propagation will be forward'), true));
	
	$table->data[4][1] = print_checkbox ('split', 1, false, true, 
		__('Backward')  . print_help_tip (__('If this checkbox is activated, propagation will be backward'),
		true));
}

$table->data[5][0] = print_checkbox ('work_home', 1, $work_home, true, __('Work from home'));

$table->data[6][0] = print_textarea ('description', 10, 30, $description,
	'', true, __('Description'));

echo '<form id="single_task_form" method="post" onsubmit="return validate_single_form()">';
print_table ($table);

$button = '';
echo '<div style="width:100%;">';
	unset($table->data);
	$table->width = '100%';
	$table->class = "button-form";
	if ($id_workunit) {
		$button = print_input_hidden ('operation', 'update', true);
		$button .= print_input_hidden ('id_workunit', $id_workunit, true);
		$button .= print_input_hidden ("wu_user", $wu_user, true);
		$button .= print_submit_button (__('Update'), 'btn_upd', false, 'class="sub upd"', true);
	}
	else {
		$button .= print_input_hidden ('operation', 'insert', true);
		$button .= print_submit_button (__('Add'), 'btn_add', false, 'class="sub create"', true);
	}
	$button .= print_input_hidden ('timestamp', $now, true);

	$table->data[7][0] = $button;
	$table->colspan[7][0] = 2;

	print_table($table);
echo '</div>';
echo '</form>';

echo "</div>";

//If not workunit update then enable the multiple workunit option
if (!$id_workunit) {
	if ($operation == 'multiple_wu_insert') {
		echo "<div id='tab2' class='ui-tabs-panel'>"; //Multiple WU
		echo "<table width='100%' class='search-table-button'>";
		echo "<tr>";
		echo "<td style='text-align: right;'>";
		echo print_button (__('Add new parse Workunit'), 'add_link', false, 'location.href=\'index.php?sec=users&sec2=operation/users/user_spare_workunit\'', 'class="sub create"');
		echo "</td>";
		echo "</table>";
		foreach ($multiple_wu_report as $number => $mwur) {
			print_single_workunit_report($mwur);
		}
	}
	else {
		echo "<div id='tab2' class='ui-tabs-panel ui-tabs-hide'>"; //Multiple WU
		echo '<form id="multiple_task_form" method="post" onsubmit="return validate_multiple_form()">';
		print_input_hidden ('operation', 'multiple_wu_insert');
		
		//Massive work unit list
		create_new_table_multiworkunit(1);
		echo "<table width='100%' class='button-form'>";
		echo "<tr>";
		echo "<td style='width: 90%;'>";
		echo "</td>";
		echo "<td>";
		echo print_button (__('Add'), 'add_multi_wu', false, '', 'class="sub create"');
		echo "</td>";
		echo "<td>";
		echo print_submit_button (__('Save'), 'btn_upd', false, 'class="sub save"');
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo '</form>';
	}
	echo '</div>';
}


?>

<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>

<script type="text/javascript">

function datepicker_hook () {
	add_datepicker ('input[name*="start_date"]', null);
}

function username_hook () {
	var idUser = "<?php echo $config['id_user'] ?>";
	
	bindAutocomplete ('input[name~="id_username"]', idUser);
	
	validate_user ("#single_task_form", 'input[name~="id_username"]', "<?php echo __('Invalid user')?>");
	$('input[name*="id_username"]').each( function () {
		validate_user ("#multiple_task_form", '#'+$(this).prop('id'), "<?php echo __('Invalid user')?>");
	});
}

//single task validation form
function validate_single_form() {
	var val = $("#id_task").val();
	var forward = $("#checkbox-forward:checked").val();
	var back = $("#checkbox-split:checked").val();
	console.log(forward);
	console.log(back);
	if (val == 0) {
		
		var error_textbox = document.getElementById("error_task");
	
		if (error_textbox == null) {
			$('#single_task_form').prepend("<h3 id='error_task' class='error'><?php echo __("Task was not selected")?></h3>");
		}
		
		pulsate("#id_task");
		pulsate("#error_task");
		return false;  
		
	}
	 
	if (forward == 1 && back == 1) {
		
		var error_textbox = document.getElementById("error_check");
		console.log(error_textbox);
		if (error_textbox == null) {
			$('#single_task_form').prepend("<h3 id='error_check' class='error'><?php echo __("You can not have both click forward and backward")?></h3>");
		}
		
		pulsate("#checkbox-forward");
		pulsate("#checkbox-split");
		pulsate("#error_check");
		return false;  
		
	}
	
	return true;
}

//Multiple task validation form
function validate_multiple_form() {

	var val = $("select[id^='id_task_']").val();

	if (val == 0) {
		
		var id_element = $("select[id^='id_task_']").attr("id");
		
		var number_id =  id_element.slice(8, id_element.length);
		
		var error_textbox = document.getElementById("error_task");
	
		if (error_textbox == null) {
			$("#wu_"+number_id).before("<h3 id='error_task' class='error'><?php echo __("Task was not selected")?></h3>");
		}
		
		pulsate("#"+id_element);
		pulsate("#error_task");
		return false;  
		
	} 
	
	return true;
}

function del_wu_button () {
	
	var cross1 = "<a id='del_wu_1' href='#'><img src='images/cross.png'></a>";
	
	$("#del_wu_1").html("");
		
	$("#del_wu_1").html(cross1);
	
	$('a[id^="del_wu"]').click(function (e) {
		//Prevent default behavior
		e.preventDefault();
		var id_element = $(this).attr("id");
		var number_id =  id_element.slice(7, id_element.length);
		
		$("table[id='wu_"+number_id+"']").remove();

	});
}

function show_task_role () {
	
	id_task = $("#id_task").val();
	
	if ($("#text-id_username").val() == undefined) {
		id_user = "<?php echo $config['id_user']; ?>";
	} else {
		id_user = $("#text-id_username").val();
	}
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&get_user_task_roles=1&id_task=" + id_task + "&id_user=" + id_user,
		dataType: "json",
		success: function (data, status) {
				$("#id_profile").hide ().empty ();
				
				if(data != false) {
					$(data).each (function () {
						$("#id_profile").append ($('<option value="'+this.id+'">'+this.name+'</option>'));
					});
				} else {
					$("#id_profile").append ($('<option value="0"><?php echo __('N/A'); ?></option>'));
				}
				
				$("#id_profile").show ();
			}
		});
}

function check_multiplewu_task () {

	id_task = $("select[id^='id_task_']").attr("id");
	number =  id_task.slice(8, id_task.length);
	
	if ($("#text-id_username_"+number).val() == undefined) {
		id_user = "<?php echo $config['id_user']; ?>";
	} else {
		id_user = $("#text-id_username_"+number).val();
	}
	
	id_task_value = $("#id_task_"+number).val();

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&get_user_task_roles=1&id_task=" + id_task_value + "&id_user=" + id_user,
		dataType: "json",
		success: function (data, status) {
			$("#id_profile_"+number).hide ().empty ();
			
			if(data != false) {
				$(data).each (function () {
					$("#id_profile_"+number).append ($('<option value="'+this.id+'">'+this.name+'</option>'));
				});
			} else {
				$("#id_profile_"+number).append ($('<option value="0"><?php echo __('N/A'); ?></option>'));
			}
			$("#id_profile_"+number).show ();
		}
	});	
}


$(document).ready (function () {
	//Configure calendar datepicker
	datepicker_hook();
	
	//Configure username selector
	username_hook();
	
	$("#textarea-description").TextAreaResizer ();
	
	////////Configure menu tab interaction///////
	$('#tabmenu1').not('.ui-tabs-disabled').click (function (e){
		e.preventDefault();//Deletes default behavior
	
		//Change CSS tabs
		//tab1 selected
		$('#tabmenu1').addClass("ui-tabs-selected");
		$('#tabmenu1').removeClass("ui-tabs");
		
		//tab2 not selecteed
		$('#tabmenu2').addClass("ui-tabs");
		$('#tabmenu2').removeClass("ui-tabs-selected");
		
		//Show/hide divs
		$('#tab2').addClass("ui-tabs-hide");
		$('#tab1').removeClass("ui-tabs-hide");
	});

	$('#tabmenu2').not('.ui-tabs-disabled').click (function (e){
		e.preventDefault();//Deletes default behavior
	
		//Change CSS tabs
		//tab2 selected
		$('#tabmenu2').addClass("ui-tabs-selected");
		$('#tabmenu2').removeClass("ui-tabs");
		
		//tab1 not selecteed
		$('#tabmenu1').addClass("ui-tabs");
		$('#tabmenu1').removeClass("ui-tabs-selected");

		//Show/hide divs				
		$('#tab1').addClass("ui-tabs-hide");
		$('#tab2').removeClass("ui-tabs-hide");
	});	
	
	/////Add new table to add a massive task/////
	$("#button-add_multi_wu").click (function () {

		var error_textbox = document.getElementById("error_task");

		if (error_textbox != null) {

			$("#error_task").remove();
		}
		
		var valid_form = validate_multiple_form();
		
		if (valid_form) {
		
			var number_wu = $('#wu_1').siblings().length;
			var givendate = $("#text-start_date_1").val();

			values = Array ();
			values.push ({name: "page",
						value: "operation/users/user_spare_workunit"});
			values.push ({name: "get_new_mult_wu",
				value: 1});
			values.push ({name: "next",
				value: number_wu});
			values.push ({name: "given_date",
				value: givendate});
			jQuery.get ("ajax.php",
				values,
				function (data, status) {
					
					$("#wu_"+(number_wu-1)).before(data);
					
					//Reset datepicker hook function
					datepicker_hook();
					
					//Reset username selector hook
					username_hook();
					
					//Assign del button action
					del_wu_button();
				},
				"html"
			);		
		}
	});
	
	$("#id_task").change(function () {
		show_task_role();
	});
});

</script>
