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


// Load global vars

global $config;

check_login ();

require_once ('include/functions_db.php');
require_once ('include/functions_user.php');
include_once ("include/functions_projects.php");

// Get main variables and init
$id_task = get_parameter ("id_task", -1);
// id_task = -1 is for project people management, different than people task management

$id_project = get_parameter ("id_project", 0);
$operation = get_parameter ("action");
$result_output = "";
$search_params = "&id_task=$id_task&id_project=$id_project";
// ACL
if ($id_task == -1) {
	$project_permission = get_project_access ($config["id_user"], $id_project);
	if (!$project_permission["manage"]) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to project people manager without permissions");
		no_permission();
	}
} else {
	$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (!$task_permission["manage"]) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to project people manager without permissions");
		no_permission();
	}
}

// -----------
// Add user for this task
// -----------
if ($operation == "insert"){
	
	$id_role = get_parameter ("role", 0);
	$id_user = get_parameter ("user");
	
	if ($id_task != -1) {
		// People add for TASK
				
		// If this user is not assigned to the project, add him
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
				$result_output = ui_print_error_message (__('Error assigning access to project.'), '', true, 'h3', true);
				continue; // Does not insert the task
			}
		}
		
		// Comment this line, is a serious bug.
		//~ $temp_id_user = get_db_value ("id_user", "trole_people_task", "id_user", $id_user);
		$temp_id_user = $id_user;
		$temp_id_role = get_db_value('id', 'trole', 'id', $id_role);
		
		$filter = array();
		$filter['id_role']= $temp_id_role;
		$filter['id_user']= $temp_id_user ;
		$filter['id_task']= $id_task;
		
		$result_sql = get_db_value_filter('id_user', 'trole_people_task', $filter);
		
		if ( $result_sql !== false) {
			echo ui_print_error_message (__('Not created. Role already exists.'), '', true, 'h3', true);
		}
		else {
			$sql = "INSERT INTO trole_people_task
				(id_task, id_user, id_role) VALUES
				($id_task, '$temp_id_user', '$temp_id_role')";
			task_tracking ($id_task, TASK_MEMBER_ADDED);
			$id_task_inserted = process_sql ($sql, 'insert_id');
			
			if ($id_task_inserted !== false) {
				$result_output = ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
				audit_db ($config["id_user"], $config["REMOTE_ADDR"],
					"User/Role added to task", "User $id_user added to task " . get_db_value ("name", "ttask", "id", $id_task));
			}
			else {
				$update_mode = 0;
				$create_mode = 1;
				$result_output = ui_print_error_message (__('Not created. Error inserting data.'), '', true, 'h3', true);
			}
		}
	}
	else {
		// People add for whole PROJECT
		
		$filter['id_role']= $id_role;
		$filter['id_user']= $id_user;
		$filter['id_project']= $id_project;
		
		$result_sql = get_db_value_filter('id_user', 'trole_people_project', $filter);
		if ($result_sql !== false){
			echo ui_print_error_message (__('Not created. Role already exists.'), '', true, 'h3', true);
		}
		else {
			$sql = "INSERT INTO trole_people_project
				(id_project, id_user, id_role) VALUES
				($id_project, '$id_user', '$id_role')";
			
			$id_task_inserted = process_sql ($sql, 'insert_id');
		
			if ($id_task_inserted !== false) {
				$result_output = ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "User/Role added to project", "User $id_user added to project ".get_db_value ("name", "tproject", "id", $id_project));
			} else {
				$update_mode = 0;
				$create_mode = 1;
				$result_output = ui_print_error_message (__('Not created. Error inserting data.'), '', true, 'h3', true); 
			}
		}
	}
}

// DELETE Users from this project / task

if ($operation == "delete"){
	
	$id = get_parameter ("id",-1);

	// People delete for TASK
	if ($id_task != -1){
		$sql = "DELETE FROM trole_people_task WHERE id = $id";
		task_tracking ($id_task, TASK_MEMBER_DELETED);
	// People delete for whole PROJECT
	} else {
		$sql = "DELETE FROM trole_people_project WHERE id = $id";
	}
	if (mysql_query($sql)){
		$result_output = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
		$operation = "view";
	} else {
		$result_output = ui_print_error_message (__('Deleted'), '', true, 'h3', true);
	}
}

if ($operation == 'insert_all') {
	$all_people = get_parameter('people');
	
	if (empty($all_people)) {
		$update_mode = 0;
		$create_mode = 1;
		$result_output = ui_print_error_message (__('You must select user/role'), '', true, 'h3', true);
	}
	else {
	
		foreach ($all_people as $person) {
			$result = explode('/', $person);

			$id_user = $result[0];
			$id_role = $result[1];
			
			$filter['id_role']= $id_role;
			$filter['id_user']= $id_user;
			$filter['id_task']= $id_task;
			
			$role_name = get_db_value('name', 'trole', 'id', $id_role);
			
			$result_sql = get_db_value_filter('id_user', 'trole_people_task', $filter);
			
			if ( $result_sql !== false) {
				echo ui_print_error_message (__('Not created. Role already exists: ').$id_user.' / '.$role_name, '', true, 'h3', true);
			}
			else {
				$sql = "INSERT INTO trole_people_task
					(id_task, id_user, id_role) VALUES
					($id_task, '$id_user', '$id_role')";

				task_tracking ($id_task, TASK_MEMBER_ADDED);
				$id_task_inserted = process_sql ($sql, 'insert_id');
				
				if ($id_task_inserted !== false) {
					$result_output .= ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
					audit_db ($config["id_user"], $config["REMOTE_ADDR"],
						"User/Role added to task", "User $id_user added to task " . get_db_value ("name", "ttask", "id", $id_task));
				}
				else {
					$update_mode = 0;
					$create_mode = 1;
					$result_output .= ui_print_error_message (__('Not created. Error inserting data: ').$id_user.' / '.$role_name, '', true, 'h3', true);
				}
			}
		}
	}
}

// ---------------------
// Edition / View mode
// ---------------------
echo $result_output;

// MAIN PROJECT PEOPLE LIST
if ($id_task == -1){
	
	$section_title = __('Project people management');
	$section_subtitle = get_db_value('name', 'tproject','id',$id_project);
	$p_menu = print_project_tabs();
	print_title_with_menu ($section_title, $section_subtitle, "people_manager", 'projects', $p_menu, 'people');
}
else {
	$section_title = __('Task human resources management');
	$section_subtitle = get_db_value('name', 'ttask','id',$id_task);
	$t_menu = print_task_tabs();
	print_title_with_menu ($section_title, $section_subtitle, "people_manager", 'projects', $t_menu, 'people');
}
// Role / task assigment
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Only project owner or Project ADMIN could modify
echo "<div class='divform'>";
if ($id_task != -1){
	
	// Task people manager editor
	// ===============================
	$table = new StdClass;
	$table->width = '100%';
	$table->class = 'search-table';
	$table->id = "project_people";
	$table->size = array ();

	$table->head = array();
	$table->style = array();
	$table->data = array ();
	$table->colspan[2][1] = 2;

	$sql = "SELECT * FROM trole_people_project WHERE id_project = $id_project";
	$people = get_db_all_rows_sql($sql);

	if ($people === false) {
		$people = array();
	}
	$people_project = array();
	foreach ($people as $person) {
		$role_name = get_db_value('name', 'trole', 'id', $person['id_role']);
		$people_project[$person['id_user'].'/'.$person['id_role']] = $person['id_user'] .' / '. $role_name;
	}

	$table->data[0][0] ="<h4>".__('Add users already involved in this project')."</h4>";
	$table->data[1][0] = print_label(__('People involved in project').' / '.__('Role'), '', 'text', true);
	$table->data[2][0] = print_select ($people_project, "people[]", '', '', '', 0, true, 10, false, false, false, '');
	$table->data[3][0] = print_submit_button (__('Update'), 'upd_btn', false, 'class="sub next"', true);

	echo "<form id='form-project_people_manager' method='post' action='index.php?sec=projects&sec2=operation/projects/people_manager&id_project=$id_project&id_task=$id_task&action=insert_all'>";
	print_table($table);
	echo "</form>";
	
	echo "<form id='form-people_manager' method='post' action='index.php?sec=projects&sec2=operation/projects/people_manager&id_project=$id_project&id_task=$id_task&action=insert'>";
	echo "<table width=100% class='search-table'>";

	echo '<tr><td><h4>'.__('Add other users').'</h4>';
	echo "<tr><td>";
	echo "<label>" . __('Role') . "</label>";
	echo "<tr><td>";
	echo combo_roles ();

	echo "<tr><td>";
	$params['input_id'] = 'text-user';
	$params['input_name'] = 'user';
	$params['return'] = false;
	$params['return_help'] = true;
	$params['title'] = __('User');
	
	user_print_autocomplete_input($params);
	echo "<tr><td colspan=4>";
	echo "<input type=submit class='sub next' value='".__('Update')."'>";

	echo "</table>";
}
else {
	// PROYECT PEOPLE MANAGER editor
	// ===============================
	//echo "<h3>".__('Project role assignment')."</h3>";
	echo "<form id='form-people_manager' method='post' action='index.php?sec=projects&sec2=operation/projects/people_manager&id_project=$id_project&id_task=$id_task&action=insert'>";
	echo "<table width=100% class='search-table'>";

	echo "<tr><td>";
	echo __('Role');
	echo combo_roles ();

	echo "<tr><td>";
	echo __('User  ');
	
	$params['input_id'] = 'text-user';
	$params['input_name'] = 'user';
	$params['return'] = false;
	$params['return_help'] = false;
	
	user_print_autocomplete_input($params);
	echo "<tr><td colspan=4>";
	echo "<input type=submit class='sub next' value='".__('Update')."'>";

	echo "</table>";
}

echo "</div>";
	
// --------------------
// Main task form table
// --------------------
echo "<div class='divresult'>";
$assigned_role = '<tr>';
if ($id_task != -1) {
	$sql = "SELECT COUNT(*) total FROM trole_people_task where id_task = $id_task";
	$result = get_db_row_sql($sql);
	$assigned_role = "<table class='listing'>";
	if ($result["total"] > 0) {
		
		$sql = "SELECT * FROM trole_people_task where id_task = $id_task";
		$result = get_db_all_rows_sql($sql);
		
		$assigned_role .= "<th>".__('User');
		$assigned_role .= "<th>".__('Role');
		$assigned_role .= "<th>".__('Total work time (Hrs)');
		$columns = 2;
		if ($task_permission["manage"]) {
			$assigned_role .= "<th>".__('Delete');
			$columns = 3;
		}
		
		$color = 1;
		foreach ($result as $row) {
			
			$assigned_role .= "<tr><td>".$row["id_user"];
			$assigned_role .= "<td>".get_db_value('name','trole','id',$row["id_role"]);

            $assigned_role .= "<td>";
            $assigned_role .= "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_task=$id_task&id_user=".$row["id_user"]."'><b>";
            $assigned_role .= get_task_workunit_hours_user ($id_task, $row["id_user"]);
            $assigned_role .= "</a></b></td>";

			if ($task_permission["manage"]) {
				$assigned_role .= "<td>";
				$offset=0;
				$assigned_role .= "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_people_task_human\",".$id_project.",".$id_task.",".$row['id'].",\"".$search_params."\");'><img src='images/cross.png' title='".__('Delete')."'></a>";
			}
		}
	}
	$assigned_role .= "</table>";
}
else {
	
	$sql = "SELECT COUNT(*) total FROM trole_people_project WHERE id_project = $id_project";
	$result = get_db_row_sql($sql);
	
	$assigned_role = "<table class='listing'>";
	if ($result["total"] > 0) {
		
		$sql = "SELECT * FROM trole_people_project WHERE id_project = $id_project";
		$result = get_db_all_rows_sql($sql);
		$assigned_role .= "<th>".__('User');
		$assigned_role .= "<th>".__('Role');
		$assigned_role .= "<th>".__('Total work time (Hrs)');
		$columns = 2;
		if ($project_permission["manage"]) {
			$assigned_role .= "<th>".__('Delete');
			$columns = 3;
		}
		
		foreach ($result as $row) {
			
			$assigned_role .= "<tr><td >".$row["id_user"];
			$assigned_role .= "<td>".get_db_value('name','trole','id',$row["id_role"]);
            $assigned_role .= "<td>";
            $assigned_role .= "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_user=".$row["id_user"]."'><b>";
            $assigned_role .= get_project_workunits_hours_user ($id_project, $row["id_user"]);
            $assigned_role .= "</b></td>";
			
			if ($project_permission["manage"]) {
				$assigned_role .= "<td>";
				$offset=0;
				$assigned_role .= "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_people_manager\",".$row["id"].",0,".$offset.",\"".$search_params."\");'><img src='images/cross.png' title='".__('Delete')."'></a>";
			}
		}
	}
	$assigned_role .= "</table>";
}
if(!isset($columns)){
	$columns = '';
}
print_container_div('assigned_roles', __('Assigned roles'), $assigned_role, 'open', false, '10px', '', '', $columns, 'no_border_bottom');

// Role informational table
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$roles = "<table class='listing'>";
$roles .= "<tr>";
$roles .= "<th >".__('Name');
$roles .= "<th >".__('Description');
$roles .= "<th>".__('Cost');

$sql1='SELECT * FROM trole ORDER BY name';
$result = process_sql($sql1);

foreach ($result as $row) {
	$roles .= "<tr>";
	//$roles .= "<td class='no_border size_min'></td>";
	$roles .= "<td >".$row["name"];
	$roles .= '<td >'.$row["description"];
	$roles .= '<td>'.$row["cost"];
}
$roles .= "</table>";

print_container_div('people_roles', __('Available roles'), $roles, 'closed', false, '10px', '', '', 2, 'no_border_bottom');

echo "</div>";
echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>
<script src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" >
$(document).ready (function () {
	$("#textarea-description").TextAreaResizer ();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	var idProject = "<?php echo $id_project ?>";
	
	bindAutocomplete ("#text-user", idUser);
	bindAutocomplete ("#text-user_role", idUser, idProject);
		
});
trim_element_on_submit('input[name="user"]');
var input = "#text-user";

rules = {
	required: true,
	remote: {
		url: "ajax.php",
		type: "POST",
		data: {
			page: "include/ajax/remote_validations",
			search_non_existing_user_id: 1,
			user_id: function() { return $(input).val() }
		}
	}
};
messages = {
	remote: "<?php echo __('Invalid user')?>"
};
validate_form("#form-people_manager");
add_validate_form_element_rules(input, rules, messages);
</script>
