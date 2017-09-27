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

require_once ('include/functions_tasks.php');
require_once ('include/functions_workunits.php');

if (defined ('AJAX')) {
	$multiple_delete_wu = get_parameter('multiple_delete_wu', 0);
	$multiple_update_wu = get_parameter('multiple_update_wu', 0);
	
	if ($multiple_delete_wu) {
		$ids = get_parameter('ids');
		
		if ($ids == '') {
			return;
		}
		
		
		$result_ids = explode(',', $ids);
		$result = '';
		foreach ($result_ids as $id) {
			$success = delete_task_workunit ($id);
		}
		
		if ($success) {
			$result = 'Succesfully deleted';
		}
		else {
			$result = 'Error in delete wu';
		}

		echo json_encode($result);
		return;
	}
	
	if ($multiple_update_wu) {
		
		$ids = get_parameter('ids');
	
		if ($ids == '') {
			return;
		}
		
		$id_profile = get_parameter('id_profile');
		$id_task = get_parameter('id_task');
		$have_cost = get_parameter ("have_cost");
		$public = get_parameter('public');
		$keep_cost = get_parameter ("keep_cost");
		$keep_public = get_parameter('keep_public');
		
		$result_ids = explode(',', $ids);
		$result = '';
	
		foreach ($result_ids as $id) {

			$values = array();
			
			$wu_data = get_db_row_filter('tworkunit', array('id'=>$id));
			
			$values['id_profile'] = $id_profile;
			$values['have_cost'] = ($have_cost == "true") ? 1: 0;
			$values['public'] = ($public == "true") ? 1: 0;
			
			if ($id_profile == -1) { //No change option
				$values['id_profile'] = $wu_data['id_profile'];
			}
			if ($keep_cost == "true") {
				$values['have_cost'] = $wu_data['have_cost'];
			}
			if ($keep_public == "true") {
				$values['public'] = $wu_data['public'];
			}
			
			$result = db_process_sql_update('tworkunit', $values, array('id'=>$id));

			$id_workunit_task = get_db_sql ("SELECT id_task FROM tworkunit_task WHERE id_workunit = $id");
			$values_task['id_task'] = $id_task;
			if ($id_task == 0) { //No change option
				$values_task['id_task'] = $id_workunit_task;
			}
			$result_task = db_process_sql_update('tworkunit_task', $values_task, array('id_workunit'=>$id));
			if ($result && $result_task) {
				$msg = __('Succesfully updated');
			}
			else {
				$msg = __('Error in update');
				break;
			}
		}
		echo json_encode($msg);
		return;
		
	}
}

$id_user = $config["id_user"];

check_login ();

$id_workunit = get_parameter ("id_workunit", -1);
$id = get_parameter ("id");

// Optional search: by task
$id_task = get_parameter ("id_task", 0);

$operation = get_parameter ("operation");

$users = get_user_visible_users();

if (($id != "") && ($id != $id_user) && in_array($id, array_keys($users))) {
	if (give_acl($id_user, 0, "PW"))
		$id_user = $id;
	else {
		audit_db("Noauth", $config["REMOTE_ADDR"], "No permission access", "Trying to access user workunit report");
				require ("general/noaccess.php");
				exit;
	}
	
}

$timestamp_l = get_parameter ("timestamp_l");
$timestamp_h = get_parameter ("timestamp_h");

// ---------------
// Lock Workunit
// ---------------

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

// ---------------
// DELETE Workunit
// ---------------

if ($operation == "delete"){
	// Delete workunit with ACL / Project manager check
	$id_workunit = get_parameter ("id_workunit");
	$sql = "SELECT * FROM tworkunit WHERE id = $id_workunit";
	if ($res = mysql_query($sql)) 
		$row=mysql_fetch_array($res);
	else
		return;
	
	$id_user_wu = $row["id_user"];
	if (($id_user_wu == $config["id_user"]) OR (give_acl($config["id_user"], 0,"PM") ==1 ) OR (project_manager_check($id_project) == 1)){
		mysql_query ("DELETE FROM tworkunit where id = '$id_workunit'");
		if (mysql_query ("DELETE FROM tworkunit_task where id_workunit = '$id_workunit'")){
				$result_output = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
				audit_db ($id_user, $config["REMOTE_ADDR"], "Work unit deleted", "Workunit for $id_user");
		} else {
			$result_output = ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);
		}
	} else {
		audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to delete WU $id_workunit without rigths");
		include ("general/noaccess.php");
		exit;
	}
}

// --------------------
// Workunit report
// --------------------

$ahora = date("Y-m-d H:i:s");
if ($timestamp_h == "")
	$timestamp_h == $ahora ;
echo "<h2>";

echo __('Workunit personal report for user');
echo " '". dame_nombre_real($id_user). "'.";


echo "</h2>";

echo "<h4>";
echo __("Between dates");
if ($timestamp_l != "" AND $timestamp_h != "")
	echo " : ".$timestamp_l. " ".__("to")." ".$timestamp_h;

if ($id_task != 0)
    echo __("Task"). " : ".get_db_sql("SELECT name FROM ttask WHERE id = $id_task");

$now_year = date("Y");
$now_month = date("m");

echo "<div id='button-bar-title'><ul>";
if (!$pure) {    
   echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$now_month&year=$now_year&id=$id'>";
		echo "<img src='images/calendar_orange.png' border=0 title='". __("Show calendar"). "'>";
		echo "</a>";
	echo "</li>";
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly_graph&month=$now_month&year=$now_year&id=$id'>";
		echo "<img src='images/chart_bar.png' border=0 title='". __("Show graphs"). "'>";
		echo "</a>";
	echo "</li>";
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/users/user_workunit_report&timestamp_l=$timestamp_l&timestamp_h=$timestamp_h&id=$id&pure=1'>";
		echo "<img src='images/html_tabs.png' border=0 title='". __("HTML"). "'>";
		echo "</a>";
	echo "</li>";
} else {
	echo "<li>";
		echo " <a href=index.php?sec=users&sec2=operation/users/user_workunit_report&timestamp_l=$timestamp_l&timestamp_h=$timestamp_h&pure=0&id=$id'>";
		echo "<img src='images/flecha_volver.png' border=0 title='". __("Back"). "'>";
		echo "</a>";
	echo "</li>";
}
	echo "</ul>";
	echo "</div>";  
   
echo "</h4>";

if ($id_workunit != -1){
	$sql= "SELECT * FROM tworkunit WHERE tworkunit.id = $id_workunit";
} else {
    if ($id_task == 0){
	    if ($timestamp_l != "" && $timestamp_h != "")
		    $sql= "SELECT * FROM tworkunit WHERE tworkunit.id_user = '$id_user' AND timestamp >= '$timestamp_l' AND timestamp <= '$timestamp_h' ORDER BY timestamp DESC";
	    else 
		    $sql= "SELECT * FROM tworkunit WHERE tworkunit.id_user = '$id_user' ORDER BY timestamp DESC";
    } else {
        if ($timestamp_l != "" && $timestamp_h != "")
		    $sql= "SELECT * FROM tworkunit, tworkunit_task WHERE tworkunit.id_user = '$id_user' AND timestamp >= '$timestamp_l' AND timestamp <= '$timestamp_h' AND tworkunit_task.id_task = $id_task AND tworkunit_task.id_workunit = tworkunit.id ORDER BY timestamp DESC";
	    else 
		    $sql= "SELECT * FROM tworkunit, tworkunit_task WHERE tworkunit.id_user = '$id_user' AND tworkunit_task.id_task = $id_task AND tworkunit_task.id_workunit = tworkunit.id ORDER BY timestamp DESC";
    }
}

$sql = safe_output ($sql);

$alldata = get_db_all_rows_sql ($sql);
foreach ($alldata as $row){ 
	
	if ($row["id"] != -1)
        show_workunit_user ($row['id'], 1, true, true, $id_user, $timestamp_h, $timestamp_l);
	else 
		show_workunit_user ($row['id'], 0, true, true, $id_user, $timestamp_h, $timestamp_l);
}

echo '<div id="show_multiple_edit">';

echo '<h2>'.__('Massive operations over selected items').'</h2>';

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
$have_cost = 0;
$keep_cost = 0;
$public = 0;
$keep_public = 0;

$table->data[2][0] = print_checkbox ('have_cost', 1, $have_cost, true, __('Have cost'));

$table->data[2][1] = print_checkbox ('keep_cost', 1, $keep_cost, true, __('Keep cost'));

$table->data[3][0] = print_checkbox ('public', 1, $public, true, __('Public'));

$table->data[3][1] = print_checkbox ('keep_public', 1, $keep_public, true, __('Keep public'));

$table->colspan[5][0] = 2;
$table->data[5][0] = print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
$table->data[5][0] .= print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"', true);

print_table ($table);	

echo '</div>';	
?>

<script type="text/javascript">
$(document).ready (function () {
	//WU Multiple delete
	$("#submit-delete_btn").click (function () {
				
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_delete_wu=1&ids=" + checkboxValues,
		dataType: "json",
		success: function (data, status) {
			var checkboxArray = checkboxValues.split(',');
			checkboxArray.forEach(function(item) {
				var div = document.getElementById("wu_"+item);
				div.remove();
			});
			launch_alert_delete(data);
			location.reload();
		}
		});
		function launch_alert_delete(msg) {
			alert(msg);
		}
	});

	$("#submit-update_btn").click (function () {
		
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		var id_profile = $("#id_profile").val();
		var id_task = $("#id_task").val();
		var have_cost = document.getElementById('checkbox-have_cost').checked;
		var is_public = document.getElementById('checkbox-public').checked;
		var keep_cost = document.getElementById('checkbox-keep_cost').checked;
		var keep_public = document.getElementById('checkbox-keep_public').checked;
		
		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_update_wu=1&ids="+checkboxValues+"&id_profile="+id_profile+
			"&id_task="+id_task+"&have_cost="+have_cost+"&public="+is_public+"&keep_cost="+keep_cost+"&keep_public="+keep_public,
		dataType: "json",
		success: function (data, status) {
			launch_alert_update(data);
			location.reload();
		}
		});
		function launch_alert_update(msg) {
			alert(msg);
		}
	});
});
</script>
