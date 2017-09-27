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

// Load global vars
global $config;

check_login ();

include_once ("include/functions_projects.php");

// ACL
$section_access = get_project_access ($config['id_user']);
if (! $section_access['read']) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to global assignment");
	no_permission();
}
// If the user don't manage any task, he can't do anything in this section.
// The check below can restrict their access if is needed.
//~ if (! manage_any_task ($config["id_user"])) {
	//~ audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to global assignment");
	//~ no_permission();
//~ }

$id_user = get_parameter ("id_user", $config["id_user"]);
$id_role = get_parameter ("roles", 0);
if (isset($_POST["tasks"])){
	$tasks = (array) $_POST["tasks"];
}
$delete = get_parameter ("delete", 0);
if ($delete) {
	$id_project = get_db_value ('id_project', 'ttask', 'id', $delete);
	$project_access = get_project_access ($config['id_user'], $id_project);
	$task_access = get_project_access ($config['id_user'], $id_project, $delete);
	// ACL - To delete a task, you should have TW permission and belong to the task or be project manager
	if ($project_access['manage'] || $task_access['manage']) {
		$id_task = $delete;
		$sql = "DELETE FROM trole_people_task WHERE id_task = $id_task AND id_user = '$id_user'";
		$resq1=mysql_query($sql);
		echo ui_print_success_message (__("Assigment removed succesfully"), '', true, 'h3', true);
	} else {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete the task $delete");
		echo ui_print_error_message (__("You do not have permission to delete this task"), '', true, 'h3', true);
	}
}

$add = get_parameter ("add", 0);
if ($add && $id_role) {
	
	foreach ($tasks as $id_task) {
		
		$id_project = get_db_value ('id_project', 'ttask', 'id', $id_task);
		$task = get_db_value ('name', 'ttask', 'id', $id_task);
		if (!$id_project) {
			echo ui_print_error_message (__('Error. Task is not assigned to a project.'), '', true, 'h3', true);
			continue; // Does not insert the project and the task
		}
		
		$project_access = get_project_access ($config['id_user'], $id_project);
		$task_access = get_project_access ($config['id_user'], $id_project, $id_task);
		
		// ACL - To add an user to a task, you should be project manager
		if (!$project_access['manage'] && !$task_access['manage']) {
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to add an user to the task $task");
			echo ui_print_error_message (__("You don't have permission to add an user to the task $task"), '', true, 'h3', true);
			continue; // Does not insert the project and the task
		}
		
		// ACL - To add the project manager role to an user, you should be project manager
		if ($id_role == 1 && !$project_access['manage']) {
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to add the task $task");
			echo ui_print_error_message (__("You do not have permission to add the project manager role to an user"), '', true, 'h3', true);
			continue; // Does not insert the project and the task
		}
		
		// User->Project insert
		$filter = array();
		$filter['id_user'] = $id_user;
		$filter['id_project'] = $id_project;
		
		$result_sql = get_db_value_filter ('MIN(id_role)', 'trole_people_project', $filter);
		if ($result_sql == false){
			$sql = "INSERT INTO trole_people_project
					(id_project, id_user, id_role) VALUES
					($id_project, '$id_user', '$id_role')";
			
			$result_sql = process_sql ($sql, 'insert_id');
			
			if ($result_sql !== false) {
				$project = get_db_value ('name', 'tproject', 'id', $id_project);
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "User/Role added to project", "User $id_user added to project $project");
			} else {
				$project = get_db_value ('name', 'tproject', 'id', $id_project);
				$result_output = ui_print_error_message (__('Error assigning access to project'), '', true, 'h3', true);
				continue; // Does not insert the task
			}
		}
		
		// User->Task insert
		$filter = array();
		$filter['id_user']= $id_user;
		$filter['id_task']= $id_task;
		
		$result_sql = get_db_value_filter ('MIN(id_role)', 'trole_people_task', $filter);
		if ($result_sql == false){
			$sql = "INSERT INTO trole_people_task
					(id_task, id_user, id_role) VALUES
					($id_task, '$id_user', '$id_role')";
			
			$result_sql = process_sql ($sql, 'insert_id');
			if ($result_sql !== false) {
				$project = get_db_value ('name', 'tproject', 'id', $id_project);
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "User/Role added to project", "User $id_user added to project $project");
			} else {
				$task = get_db_value ("name", "ttask", "id", $id_task);
				$result_output = ui_print_error_message (__('Error assigning access to task'), '', true, 'h3', true);
			}
		} else {
			
			$sql = "UPDATE trole_people_task
					SET id_role=$id_role
					WHERE id_user='$id_user'
						AND id_task=$id_task";
			
			$result_sql = process_sql ($sql);
			if ($result_sql !== false) {
				$role = get_db_value ("name", "trole", "id", $id_role);
				$task = get_db_value ("name", "ttask", "id", $id_task);
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Role of task updated",
					"User $id_user has now the role $role in the task $task");
			} else {
				$task = get_db_value ("name", "ttask", "id", $id_task);
				$result_output = ui_print_error_message (__('Error updating the role of the task'), '', true, 'h3', true);
			}
		}
		
	}
	
}

// Title

echo '<h2>'.__('Projects').'</h2>';
echo '<h4>'.__('Global assignment');
if ($id_user != "")
	echo " &raquo; ".__("For user"). " ".$id_user;
	echo integria_help ("role_user_global", true);
echo "</h4>";

// Controls
echo "<div class='divform'>";
	echo "<form name='xx' method=post action='index.php?sec=projects&sec2=operation/projects/role_user_global'>";
		// Select user
		$table = new stdClass;
		$table->id = "cost_form";
		$table->width = "99%";
		$table->class = "search-table";
		$table->data = array ();
		$table->data[0][0] = '<b>'.__('User ').'</b>' . print_help_tip (__("Type at least two characters to search"), true);
		$table->data[1][0] = print_input_text_extended ('id_user', $id_user, 'text-id_user', '', 26, 30, false, '', '', true, '');
		$table->data[2][0] = print_submit_button (__('Go'), 'sub_btn', false, '', true);
		print_table ($table);
		unset($table);
	echo "</form>";
	// Form to give project/task access
	echo "<form name='form-access' method=post action='index.php?sec=projects&sec2=operation/projects/role_user_global&add=1&id_user=$id_user'>";
		$table = new stdClass;
		$table->id = "cost_form";
		$table->width = "99%";
		$table->class = "search-table";
		$table->data = array ();
		$table->data[0][0] = '<b>'.__('Tasks').'</b>';
		$table->data[1][0] = combo_task_user_manager ($config['id_user'], 0, true, '', 'tasks[]', '', true);
		if (dame_admin($config['id_user'])) {
			$table->data[2][0] = '<b>'.__('Role').'</b>';
			$table->data[3][0] = combo_roles (false, "roles", '', true);
		} else {
			$table->data[2][0] = '<b>'.__('Role').'</b>';
			$table->data[3][0] = combo_roles (false, "roles", '', true, false);
		}
		$table->data[2][0] .= integria_help ("project_roles", true);
		$table->data[4][0] = print_submit_button (__('Add'), 'sub_btn', false, '', true);
		print_table ($table);
	echo "</form>";
echo "</div>";
// Table
echo "<div class='divresult'>";
	echo "<table class='listing'>";
		echo "<th>".__("Project");
		echo "<th>".__("Task");
		echo "<th>".__("Role");
		echo "<th>".__("WU");
		echo "<th>".__("WU/Tsk");
		echo "<th align='center'>".__("Delete")."</th>";

		$sql = get_projects_query ($id_user, "", 0, true);

		$new = true;
		$color=1;
		while ($project = get_db_all_row_by_steps_sql($new, $result_project, $sql)) {
			
			$sql = get_tasks_query ($id_user, $project['id'], "", 0, true);
			$new = true;
			
			$project_access = get_project_access ($config['id_user'], $project['id']);
			// ACL - To see the project, you should have read access
			if (!$project_access['read']) {
				$new = false;
				continue; // Does not show this project tasks
			}
			
			while ($task = get_db_all_row_by_steps_sql($new, $result_task, $sql)) {
				$new = false;
				
				$belong_task = user_belong_task ($id_user, $task['id'], true);
				$task_access = get_project_access ($config['id_user'], $project['id'], $task['id'], false, true);
				// ACL - To see the task, you should have read access
				if (!$task_access['read']) {
					continue; // Does not show this task
				}
				
				$role = get_db_sql ("SELECT name
									 FROM trole
									 WHERE id IN(SELECT id_role
												 FROM trole_people_task
												 WHERE id_user='$id_user'
													AND id_task=".$task['id'].")");
				echo "<tr>";
				echo "<td>";
				echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&id_project=".$project['id']."'>".$project['name']."</a>";
				echo "<td><b><a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=".$project['id']."&id_task=".$task['id']."&operation=view'>".$task['name']."</a></b>";
				echo "<td>".$role;
				if ($belong_task) {
					echo "<td>".get_task_workunit_hours_user ($task["id"], $id_user);
					echo "<td>".get_task_workunit_hours ($task["id"]);
				} else {
					echo "<td>";
					echo "<td>";
				}
				$offset = 0;
				$search_params = '';
				if ($task_access['manage'] && $belong_task) {
					echo "<td align='center'><a href='#' onClick='javascript: show_validation_delete_general(\"delete_role_user_global\",".$task['id'].",\"".$id_user."\",".$offset.",\"".$search_params."\");'><img src='images/icons/icono_papelera.png' title='".__('Delete')."'></a>";
				} else {
					echo "<td align='center'>";
				}
			}
			$new = false;
		}
	echo "</table>";
echo "</div>";
echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.multiselect.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>
<script type="text/javascript">

$(document).ready (function () {
	$("#textarea-description").TextAreaResizer ();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	
	bindAutocomplete ('#text-id_user', idUser);
	
	$("#tasks\\[\\]").multiselect({
		noneSelectedText: "<?php echo __('Select options') ?>",
		selectedText: "# <?php echo __('selected') ?>",
		checkAllText: "<?php echo __('Check all') ?>",
		uncheckAllText: "<?php echo __('Uncheck all') ?>",
		height: 400,
		minWidth: 225,
		selectedList: 1
	});
	
	
});
</script>
