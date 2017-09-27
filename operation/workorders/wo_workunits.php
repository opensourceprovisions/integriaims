<?php

// Integria IMS - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

require_once ('include/functions_tasks.php');
require_once ('include/functions_workunits.php');

$id_workorder = (int) get_parameter ('id');
$insert_workunit = (bool) get_parameter ('insert_workunit');

$result_msg = '';

//Check if we have id passed by parameter or by script loading
if (!$id_workorder) {

	if ($id) {
		$id_workorder = $id;
	} else { 
		audit_db ($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to workorder #".$id_workorder);
		include ("general/noaccess.php");
		return;
	}
}

$id_task = get_db_value ("id_task", "ttodo", "id", $id_workorder);
if (!$id_task) {
	echo "<h3 class='error'>".__("The workorder does not have a task associated")."</h3>";
	return;
}

$assigned_user = get_db_value ("assigned_user", "ttodo", "id", $id_workorder);

$task_permission = get_project_access ($config['id_user'], false, $id_task, false, true);
if (!$task_permission['read']) {
	audit_db ($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to workorder #".$id_workorder);
	include ("general/noaccess.php");
	exit;
}


// Workunit ADD
if ($insert_workunit) {
	$timestamp = print_mysql_timestamp();
	$description = (string) get_parameter ("nota");
	$duration = (float) get_parameter ('duration');
	$have_cost = (int) get_parameter ('have_cost');
	$profile = (int) get_parameter ('id_profile');
	$public = (bool) get_parameter ('public');

	// Single day workunit
	$sql = sprintf ('INSERT INTO tworkunit 
			(timestamp, duration, id_user, description, have_cost, id_profile, public) 
			VALUES ("%s", %.2f, "%s", "%s", %d, %d, %d)',
			$timestamp, $duration, $config['id_user'], $description,
			$have_cost, $profile, $public);
	$id_workunit = process_sql ($sql, 'insert_id');
	if ($id_workunit !== false) {
		$sql = sprintf ('INSERT INTO tworkunit_task 
				(id_task, id_workunit) VALUES (%d, %d)',
				$id_task, $id_workunit);
		$result = process_sql ($sql, 'insert_id');
		if ($result !== false) {
			$result_msg = '<h3 class="suc">'.__('Workunit added successfully').'</h3>';
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Spare work unit added", 
					'Workunit for '.$config['id_user'].' added to Task ID #'.$id_task);
			mail_project (0, $config['id_user'], $id_workunit, $id_task);
		} else {
			$result_msg = '<h3 class="error">'.__('An error ocurred').'</h3>';
		}
	} else {
		$result_msg = '<h3 class="error">'.__('An error ocurred').'</h3>';
	}
	
	if ($id_workunit !== false) {
		set_task_completion ($id_task);
	}
	
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU INSERT", "Task $id_task. Desc: $description");

	echo $result_msg;
}

$roles = workunits_get_user_role ($assigned_user, $id_workorder);
//Add workunit form
//echo "<h3>".__('Add workunit')."</h3>";

$table->width = '100%';
$table->class = 'integria_form';
$table->colspan = array ();
$table->colspan[1][0] = 6;
$table->colspan[2][0] = 6;
$table->data = array ();
$table->size = array();
$table->style = array();
$table->style[0] = 'vertical-align: top; padding-top: 20px;';
$table->style[1] = 'vertical-align: top; padding-top: 20px;';
$table->style[2] = 'vertical-align: top;';
$table->style[3] = 'vertical-align: top;';
$table->style[4] = 'vertical-align: top;';
$table->style[5] = 'vertical-align: top;';
$table->data[0][0] = print_image('images/calendar_orange.png', true) . '&nbsp' . print_mysql_timestamp(0, "Y-m-d");
$table->data[0][1] = print_image('images/clock_orange.png', true) . '&nbsp' . print_mysql_timestamp(0, "H:i:s");
//$table->data[0][2] = combo_roles (1, 'id_profile', __('Profile'), true);
$table->data[0][2] = print_select($roles, 'id_profile', '', '', '', '', true, false, true, __('Profile'));
$table->data[0][3] = print_input_text ("duration", $config["pwu_defaultime"], '', 7,  10, true, __('Time used'));
$table->data[0][4] = print_checkbox ('have_cost', 1, false, true, __('Have cost'));
$table->data[0][5] = print_checkbox ('public', 1, true, true, __('Public'));

$table->data[1][0] = print_textarea ('nota', 10, 70, '', "style='resize:none;'", true, __('Description'));

$button = '<div style="width: 100%; text-align: right; padding-bottom: 20px;">';
$button .= '<span id="sending_data" style="display: none;">' . __('Sending data...') . '<img src="images/spinner.gif" /></span>';
$button .= print_submit_button (__('Add'), 'addnote', false, 'class="sub create"', true);
$button .= print_input_hidden ('insert_workunit', 1, true);
$button .= print_input_hidden ('id', $id_workorder, true);
$button .= '</div>';

$table->data[2][0] = $button;

echo '<form id="form-add-workunit" method="post" action="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=wu&id='.$id_workorder.'&tab=wu">';

echo "<div style='width: 98%; padding-left: 7px;'>";
print_table ($table);
echo "</div>";

echo "</form>";
?>
