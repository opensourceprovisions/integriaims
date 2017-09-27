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
$delete_project = (bool) get_parameter ('delete_project');
$activate_project = (bool) get_parameter ('activate_project');
$action = (string) get_parameter ('action');
$search_id_project_group = (int) get_parameter ('search_id_project_group',-1);
$search_text = (string) get_parameter ('search_text');


$project_permission = get_project_access ($config['id_user'], $id_project);

// Disable project
// ======================
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

// INSERT PROJECT
if ($action == 'insert') {
	
	if (!$project_permission['write']) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Forbidden", "User ".$id_user. " try to create project");
		return;
	}
	
	// Read input variables
	$id_owner = get_parameter ("id_owner", "");
	$name = (string) get_parameter ("name");
	$description = (string) get_parameter ('description');
	$start_date = (string) get_parameter ('start_date');
	$end_date = (string) get_parameter ('end_date');
	$id_project_group = (int) get_parameter ('id_project_group');
	$cc = get_parameter("cc", "");
	
	$error_msg = "";
	
	if($id_owner == "") {
		$id_owner = $config['id_user'];
		$owner_exists = true;
	}
	else {
		$owner_exists = get_user($id_owner);
	}
	if($owner_exists === false) {
		$error_msg  = ui_print_err_message (__('Project manager user does not exist'), '', true, 'h3', true);
		$id_project = false;
	}
	else {
		$sql = sprintf ('INSERT INTO tproject
			(name, description, start, end, id_owner, id_project_group, cc)
			VALUES ("%s", "%s", "%s", "%s", "%s", %d, "%s")',
			$name, $description, $start_date, $end_date, $id_owner,
			$id_project_group, $cc);
		$id_project = process_sql ($sql, 'insert_id');
	}
	
	if ($id_project === false) {
		echo ui_print_err_message (__('Project cannot be created, problem found.').$error_msg, '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('The project successfully created.').' #'.$id_project, '', true, 'h3', true);
		audit_db ($id_owner, $REMOTE_ADDR, "Project created", "User ".$config['id_user']." created project '$name'");
		
		project_tracking ($id_project, PROJECT_CREATED);
		
		// Add this user as profile 1 (project manager) automatically
		$sql = sprintf ('INSERT INTO trole_people_project
			(id_project, id_user, id_role)
			VALUES ("%s", "%s", 1)',
			$id_project, $id_owner, 1);
		process_sql ($sql);		
		// If current user is different than owner, add also current user
		if ($config['id_user'] != $id_owner) {
			$sql = sprintf ('INSERT INTO trole_people_project
				(id_project, id_user, id_role)
				VALUES (%d, "%s", 1)',
				$id_project, $config['id_user']);
			process_sql ($sql);
		}
	}
}

echo '<h2>'.__('Projects').'</h2>';
echo '<h4>'.__('Projects overview');
if ($search_id_project_group > -1 ) {
	echo "<div id='button-bar-title'>";
		echo "<ul>";
			echo '<li>';
				echo '<a href="index.php?sec=projects&sec2=operation/projects/project_overview">'.
						print_image("images/volver_listado.png",
						true, array("title" => 
								__('Back to list all groups'))).'</a>';
			echo '</li>';
		echo "</ul>";
	echo "</div>";
}
echo '</h4>';

$table = new stdClass;
$table->width = '20%';
$table->class = 'search-table';
$table->style = array ();
$table->data = array ();
$table->data[0][0] = '<b>'.__('Search').'</b>';
$table->data[1][0] = print_input_text ("search_text", $search_text, "", 25, 100, true);
$table->data[2][0] = '<b>'.__('Project group').'</b>';

$sql = "SELECT * FROM tproject_group";
$datos = get_db_all_rows_sql($sql);
$select = array();

if ($datos) {
	foreach ($datos as $data) {
		$select[$data["id"]] = $data["name"];
	}
}

$select[0] = __("Without group");
$table->data[3][0] = print_select($select, "search_id_project_group", $search_id_project_group, '', __("Any"), -1, true, false, true, false);
$table->data[4][0] = print_submit_button (__('Search'), "search_btn", false, '', true);

echo '<div class="divform">';
	echo '<form method="post">';
		print_table ($table);
	echo '</form>';
	
	if ($project_permission['write']) {
		echo '<form method="post" action="index.php?sec=projects&sec2=operation/projects/project_detail&create_project=1">';
			echo '<table class="search-table"><tr><td>';
				echo print_submit_button (__('Create project'));
			echo '</td></tr></table>';
		echo '</form>';
	}
echo '</div>';

$project_groups = false;
$where_clause = '';
if ($search_id_project_group > 0) {
	$where_clause = sprintf (" where tproject_group.id=$search_id_project_group ");
}
if ($search_id_project_group == -1 OR $search_id_project_group > 0) {
	$project_groups = process_sql("SELECT * FROM tproject_group".$where_clause." ORDER by name"); 
}

if($project_groups === false) {
	$project_groups = array();
}

if($where_clause == ""){
	$nogroup = array();
	$nogroup["id"] = 0;
	$nogroup["name"] = __('Without group');
	$nogroup["icon"] = '../group.png';
$project_groups[] = $nogroup;
}
if ($search_id_project_group > -1)
	$apertura = 'open';
else
	$apertura = 'closed';
echo "<div class='divresult'>";
$total_projects = 0;
foreach($project_groups as $group) {
	$info_general = "";
	// Get projects info
	$where_clause2 = "";
	if ($search_text != "") {
		$where_clause2 .= sprintf (" AND (tproject.name LIKE '%%%s%%' OR tproject.description LIKE '%%%s%%')", $search_text, $search_text);
	}
	$projects = get_db_all_rows_sql ("SELECT * FROM tproject WHERE disabled = 0 AND id_project_group = ".$group["id"].$where_clause2);
	if($projects === false) {
		$projects = array();
	}
	
	//Check project ACLs
	$aux_projects = array();
	foreach ($projects as $p) {
		$project_access = get_project_access ($config["id_user"], $p['id']);
		if ($project_access["read"]) {
			array_push($aux_projects, $p);
		}
	}
	
	//Set filtered projects
	$projects = $aux_projects;
	
	$nprojects = count($projects);
	$total_projects += $nprojects;
	if ($nprojects > 0 ) {
		$info_general .= "<tr>";
		//$info_general .= "<td class='no_border size_min'></td>";
		$info_general .= "<td style='width: 30%'><b>".__('Name')."</b></td>";
		$info_general .= "<td style='width: 15%'><b>".__('Manager')."</b></td>";
		$info_general .= "<td style='width: 15%'><b>".__('Completion')."</b></td>";
		$info_general .= "<td style='width: 15%'><b>".__('Last update')."</b></td>";
		if ($view_disabled == 0) {
			$info_general .= "<td><b>".__('Archive')."</b></td>";
		}
		elseif ($project['disabled'] && $project_permission['manage']) {
			$info_general .= "<td><b>".__('Delete/Unarchive')."</b></td>";
		}
		//$info_general .= "<td class='no_border size_max'></td>";
		$info_general .= "</tr>";	

		// Projects inside
		foreach($projects as $project) {
			$info_general .= "<tr>";
			//$info_general .= "<td class='no_border size_min'></td>";
			// Project name
			$info_general .= "<td><a href='index.php?sec=projects&sec2=operation/projects/project_detail&id_project=".$project["id"]."'>".$project["name"]."</a></td>";
		
			// Manager
			$info_general .= "<td>".$project['id_owner']."</a></td>";
			
			// Completion
			if ($project["start"] == $project["end"]) {
				$info_general .= '<td>'.__('Unlimited');
			}
			else {
				$completion = format_numeric (calculate_project_progress ($project['id']));
				$info_general .= "<td>";
				$info_general .= progress_bar($completion, 90, 20);
			}
			$info_general .= "</td>";
			
			// Last update time
			$sql = sprintf ('SELECT tworkunit.timestamp FROM ttask, tworkunit_task, tworkunit
							 WHERE ttask.id_project = %d AND ttask.id = tworkunit_task.id_task
							 AND tworkunit_task.id_workunit = tworkunit.id
							 ORDER BY tworkunit.timestamp DESC LIMIT 1', $project['id']);
			$timestamp = get_db_sql ($sql);
			$timestamp = explode(" ", $timestamp);
			if ($timestamp[0] != "")
				$info_general .= "<td><span style='font-size: 10px'>".$timestamp[0]."</span></td>";
			else
				$info_general .= "<td>".__('Never')."</td>";
			
			// Disable or delete
			$project_permission = get_project_access ($config['id_user'], $project['id']);
		
			if ($project['id'] != -1 && $project_permission['manage']) {
				if ($view_disabled == 0) {
					$info_general .= '<td><a href="index.php?sec=projects&sec2=operation/projects/project_overview&disable_project=1&id='.$project['id'].'" 
						onClick="if (!confirm(\''.__('Are you sure project archive?').'\')) return false;"><img src="images/icons/icono_archivar.png" /></a></td>';
				}
				elseif ($project['disabled'] && $project_permission['manage']) {
					$info_general .= '<td><a href="index.php?sec=projects&sec2=operation/projects/project&view_disabled=1&delete_project=1&id='.$project['id'].'"
						onClick="if (!confirm(\''.__('Are you sure delete project?').'\')) return false;">
						<img src="images/cross.png" /></a></td>';
					$info_general .= '<a href="index.php?sec=projects&sec2=operation/projects/project&view_disabled=1&activate_project=1&id='.$project['id'].'">
						<img src="images/unarchive.png" /></a></td>';
				}
			} else {
				$info_general .= '<td><img title=' . __('Forbidden') . ' src="images/lock_big.png" /></td>';
			}
			//$info_general .= "<td class='no_border'></td>";
			$info_general .= "</tr>";
			
		}
		if($nprojects == 0) {
			$info_general = "<tr>";
			// Project name
			//$info_general .= "<td class='no_border size_min'></td>";
			$info_general .= "<td colspan='5'>";
			$info_general .= "<b>" . __("This group doesn't have projects.") . "</b></td>";
			$info_general .= "</td>";
			$info_general .= "<td class='no_border'></td>";	
			$info_general .= "</tr>";
		}
		$title = "<img src='images/project_groups_small/" . $group["icon"] . "' style= 'float: left;'><a href='index.php?sec=projects&sec2=operation/projects/project_overview&search_id_project_group=".$group["id"]."'>".$group["name"]."</a>&nbsp; | &nbsp;".__('Nº Projects').": ".$nprojects;
		print_container('info_projects_'.$group["id"], $title, $info_general, $apertura, false, '10px', '', '', 6, 'no_border_bottom');
	}
}
if ($total_projects == 0) {
	ui_print_error_message(__('There are not projects to show.'));
}
echo "</div>";
?>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript">
	trim_element_on_submit("#text-search_text");
</script>
