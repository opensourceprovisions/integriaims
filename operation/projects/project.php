<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
global $REMOTE_ADDR;

check_login ();

// ACL
$section_access = get_project_access ($config["id_user"]);
if (! $section_access["read"]) {
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to project overview without permission");
	no_permission();
}

include_once ("include/functions_projects.php");
include_once ("include/functions_tasks.php");

$section_permission = get_project_access ($config['id_user']);

if (!$section_permission['read']) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access project detail");
	require ("general/noaccess.php");
	exit;
}

include_once ("include/functions_graph.php");

$id_project = (int) get_parameter ('id');
$delete_project = (bool) get_parameter ('delete_project');
$view_disabled = (int) get_parameter ('view_disabled');
$disable_project = (bool) get_parameter ('disable_project');
$activate_project = (bool) get_parameter ('activate_project');
$action = (string) get_parameter ('action');
$search_id_project_group = (int) get_parameter ('search_id_project_group');
$search_text = (string) get_parameter ('search_text');

$search_params = "&search_id_project_group=$search_id_project_group&search_text=$search_text";

$project_permission = get_project_access ($config['id_user'], $id_project);

// Disable project
if ($disable_project) {
	
	if (!$project_permission['manage']) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Forbidden", "User ".$config['id_user']." try to disable project #$id_project");
		require ("general/noaccess.php");
		exit;
	}
	
	$id_owner = get_db_value ('id_owner', 'tproject', 'id', $id_project);
	$sql = sprintf ('UPDATE tproject SET disabled = 1 WHERE id = %d', $id_project);
	process_sql ($sql);
	echo ui_print_success_message (__('Project successfully disabled'), '', true, 'h3', true);
	audit_db ($config['id_user'], $REMOTE_ADDR, "Project disabled", "User ".$config['id_user']." disabled project #".$id_project);
	project_tracking ($id_project, PROJECT_DISABLED);
}

// Reactivate project
if ($activate_project) {
	
	if (!$project_permission['manage']) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Forbidden", "User ".$config['id_user']." try to activate project #$id_project");
		require ("general/noaccess.php");
		exit;
	}
	
	$id_owner = get_db_value ('id_owner', 'tproject', 'id', $id_project);
	$sql = sprintf ('UPDATE tproject SET disabled = 0 WHERE id = %d', $id_project);
	process_sql ($sql);
	echo ui_print_success_message (__('Successfully reactivated'), '', true, 'h3', true);
	audit_db ($config['id_user'], $REMOTE_ADDR, "Project activated", "User ".$config['id_user']." activated project #".$id_project);
	project_tracking ($id_project, PROJECT_ACTIVATED);
}

// Delete
if ($delete_project) {
	
	if (!$project_permission['manage']) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Forbidden", "User ".$config['id_user']." try to delete project #$id_project");
		require ("general/noaccess.php");
		exit;
	}
	
	$id_owner = get_db_value ('id_owner', 'tproject', 'id', $id_project);
	delete_project ($id_project);
	echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
}

if ($view_disabled) {
	echo '<h2>'.__('Projects').'</h2>';
	echo '<h4>'.__('Archived projects');
	echo integria_help ("archieved_projects", true);
	echo '</h4>';
}

$table = new stdClass;
$table->class = 'search-table';
$table->style = array ();
$table->data = array ();
$table->data[0][0] = '<b>'.__('Search').'</b>';
$table->data[1][0] = print_input_text ("search_text", $search_text, "", 25, 100, true);
$table->data[2][0] = '<b>'.__('Group').'</b>';
$table->data[3][0] = print_select_from_sql ("SELECT * FROM tproject_group", "search_id_project_group", $search_id_project_group, '', __("Any"), '0', true, false, true, false);
$table->data[4][0] = print_submit_button (__('Search'), "search_btn", false, '', true);
$table->data[4][0] .= print_input_hidden ('delete_project', 1);

echo '<div class="divform">';
	echo '<form method="post" action="index.php?sec=projects&sec2=operation/projects/project&view_disabled=1">';
		print_table ($table);
	echo '</form>';
echo '</div>';
unset ($table);

$table = new stdClass;
$table->width = '99%';
$table->class = 'listing';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Manager');
$table->head[2] = __('Completion');
$table->head[3] = __('Updated');
$table->head[4] = '';
$table->data = array ();

$where_clause = "";
if ($search_text != "") {
	$where_clause .= sprintf (" AND (tproject.name LIKE '%%%s%%' OR tproject.description LIKE '%%%s%%')", $search_text, $search_text);
}
if ($search_id_project_group != 0) {
	$where_clause .= sprintf (" AND tproject.id_project_group=$search_id_project_group ");
}

$sql = get_projects_query ($config['id_user'], $where_clause, $view_disabled);
$new = true;

while ($project = get_db_all_row_by_steps_sql ($new, $result, $sql)) {
	
	$new = false;
	
	$project_permission = get_project_access ($config['id_user'], $project['id']);
	if (!$project_permission['read']) {
		continue;
	}
	$data = array ();
	
	// Project name
	$data[0] = '<a href="index.php?sec=projects&sec2=operation/projects/project_detail&id_project='.$project['id'].'">'.$project['name'].'</a>';
	$data[1] = $project["id_owner"];

	if ($project["start"] == $project["end"]) {
		$data[2] = __('Unlimited');
	} else {
		$completion = format_numeric (calculate_project_progress ($project['id']));
		$data[2] = progress_bar($completion, 90, 20);
	}

	// Last update time
	$sql = sprintf ('SELECT tworkunit.timestamp
		FROM ttask, tworkunit_task, tworkunit
		WHERE ttask.id_project = %d
		AND ttask.id = tworkunit_task.id_task
		AND tworkunit_task.id_workunit = tworkunit.id
		ORDER BY tworkunit.timestamp DESC LIMIT 1',
		$project['id']);
	$timestamp = get_db_sql ($sql);
	if ($timestamp != "")
		$data[3] = "<span style='font-size: 10px'>".human_time_comparation ($timestamp)."</span>";
	else
		$data[3] = __('Never');
	$offset = 0;
	$data[4] = '';
	// Disable or delete
	if ($project['id'] != -1 && $project_permission['manage']) {
		$table->head[4] = __('Delete/Unarchive');
		$data[4] = "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_project\",".$project['id'].",0,".$offset.",\"".$search_params."\");'><img src='images/icons/icono_papelera.png' title='".__('Delete')."'></a>";
		
		$data[4] .= '<a href="index.php?sec=projects&sec2=operation/projects/project&view_disabled=1&activate_project=1&id='.$project['id'].'">
			<img src="images/upload.png" /></a>';
	}
	
	array_push ($table->data, $data);
}
echo "<div class='divresult'>";
	if(empty($table->data)) {
		echo ui_print_error_message(__('No projects found'), '', true, 'h3', true);
	}
	else {
		print_table ($table);
	}
echo "</div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>
<script type="text/javascript">
trim_element_on_submit("#text-search_text");
</script>
