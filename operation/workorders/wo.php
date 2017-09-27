<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
$operation = get_parameter ("operation");
$set_progress = (int) get_parameter ("set_progress", -1);
$progress = 0;

include_once ("include/functions_graph.php");
require_once ('include/functions_db.php');
require_once ('include/functions_ui.php');
require_once ('include/functions_user.php');
include_once ('include/functions_workorders.php');
include_once ('include/functions_projects.php');

$id = (int) get_parameter ("id");
$id_task = (int) get_parameter ("id_task");
$offset = get_parameter ("offset", 0);

$section_permission = get_project_access ($config['id_user']);
if (!$section_permission['read']) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access workorder section");
	require ("general/noaccess.php");
	exit;
}

if (defined ('AJAX')) {
	
	$change_combo_task = get_parameter ("change_combo_task", 0);
	
	if ($change_combo_task) {
		$id_user = get_parameter ("id_user", 0);
		$real_id_user = get_db_value ("id_usuario", "tusuario", "id_usuario", $id_user);
		
		if ($real_id_user) {
			echo combo_task_user_participant ($real_id_user, false, 0, true, __('Task'));
		} else {
			echo "<b>The user $id_user does not exist</b>";
		}
		return;
	}
}

// ---------------
// CREATE new todo
// ---------------
if ($operation == "insert") {
	$name = (string) get_parameter ("name");
	
	$priority = (int) get_parameter ("priority");
	$progress = (int) get_parameter ("progress");
	$description = (string) get_parameter ("description");
	$id_task = (int) get_parameter ("id_task");
	$timestamp = date ('Y-m-d H:i:s');
	$last_updated = $timestamp;

	$creator = get_parameter ("creator", $config["id_user"]);
	$assigned_user = (string) get_parameter ("assigned_user");
	$start_date = (string) get_parameter ("start_date");
	$end_date = (string) get_parameter ("end_date");
	$validation_date = "";
	$need_external_validation = (int) get_parameter ("need_external_validation");
	$id_wo_category = (int) get_parameter ("id_wo_category"); 
	$email_notify = (int) get_parameter ('email_notify');

	$sql = sprintf ('INSERT INTO ttodo (name, priority, assigned_user,
		created_by_user, progress, start_date, last_update, description, id_task, end_date, need_external_validation, id_wo_category, email_notify)
		VALUES ("%s", %d, "%s", "%s", %d, "%s", "%s", "%s", %d, "%s", %d, %d, %d)',
		$name, $priority, $assigned_user, $creator,
		$progress, $start_date, $last_updated, $description, $id_task, $end_date, $need_external_validation, $id_wo_category, $email_notify);
	
	$id = process_sql ($sql, 'insert_id');
	if (! $id)
		echo '<h3 class="error">'.__('Not created. Error inserting data').'</h3>';
	else {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>'; 
	}

	clean_cache_db();
	if ($email_notify) {
		mail_workorder ($id, 1);
	}
	
	$operation = "view"; // Keep in view/edit mode.

}

// ---------------
// UPDATE new todo
// ---------------
if ($operation == "update2") {
	$id = get_parameter ("id");
	$todo = get_db_row ("ttodo", "id", $id);
	
	$name = (string) get_parameter ("name", "");
	$id_task = get_parameter ("id_task", 0);
	$priority = get_parameter ("priority");
	$progress = get_parameter ("progress");
	$description = get_parameter ("description");
	$last_update = date('Y-m-d H:i:s');
	$creator = get_parameter ("creator", $config["id_user"]);
	$assigned_user = (string) get_parameter ("assigned_user");
	$start_date = (string) get_parameter ("start_date");
	$end_date = (string) get_parameter ("end_date");
	$validation_date = "";
	$need_external_validation = (int) get_parameter ("need_external_validation");
	$id_wo_category = (int) get_parameter ("id_wo_category");
	$email_notify = (int) get_parameter ('email_notify');
	
	if ($assigned_user != $todo['assigned_user']) {
		if ($section_permission['write']) {
			if (! get_workorder_acl($id)) {
				no_permission();
			}
		} else {
			if (! get_workorder_acl($id, 'strict')) {
				no_permission();
			}
		}
		$operation = ""; // Go to list.
		$unset_id = true;
	} else {
		if (! get_workorder_acl($id)) {
			no_permission();
		}
		$operation = "view"; // Keep in view/edit mode.
	}
	
	$sql_update = "UPDATE ttodo SET created_by_user = '$creator', need_external_validation = $need_external_validation, id_wo_category = $id_wo_category, start_date = '$start_date', end_date = '$end_date', assigned_user = '$assigned_user', id_task = $id_task, priority = '$priority', progress = '$progress', description = '$description', last_update = '$last_update', name = '$name', email_notify = $email_notify WHERE id = $id";
	
	$result=mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('Not updated. Error updating data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully updated')."</h3>";
	
	//mail_todo (1, $id);
	// TODO. Review this.
	
	clean_cache_db();
	if ($email_notify) {
		mail_workorder ($id, 0);
	}
	
	if ($unset_id === true) {
		unset($id);
	}
}

// ---------------
// DELETE todo
// ---------------
if ($operation == "delete") {
	$id_todo = get_parameter ("id");
	$todo = get_db_row ("ttodo", "id", $id_todo);

	if (! get_workorder_acl($id, 'delete')) {
		no_permission();
	}
	
	$sql_delete= "DELETE FROM ttodo WHERE id = $id_todo";
	
	$result=mysql_query($sql_delete);

	// TODO: Delete attachment from disk and database

	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>";

	clean_cache_db();
	
	$email_notify = $todo["email_notify"];
	
		if ($email_notify) {
			mail_workorder ($id, 3, false, $todo);
		}

	$operation = "";
}

// ---------------
// Set progress
// ---------------

if ($set_progress > -1 ) {
	$todo = get_db_row ("ttodo", "id", get_parameter ("id"));

	if (! get_workorder_acl($todo["id"])) {
		no_permission();
	}
	
	$datetime = date ("Y-m-d H:i:s");
	$sql_update = "UPDATE ttodo SET progress = $set_progress, last_update = '$datetime' WHERE id = ".$todo["id"];
	$result = process_sql($sql_update);
}

// ---------------
// CREATE new todo (form)
// ---------------

if ($operation == "create" || $operation == "update" || $operation == "view")  {
	if ($operation == "create") {
		echo "<h2>".__('New Work order')."</h2><h4>".__('Add work')."</h4>";
		
		$progress = 0;
		$priority = 2;
		$name = '';
		$description = '';
		$creator = $config["id_user"];
		$assigned_user = $config["id_user"];
		$start_date = date('Y-m-d');
		$end_date = "";
		$validation_date = "";
		$need_external_validation = 0;
		$id_wo_category = 0;  
		$owner = "";
		$email_notify = 0;
	} else {
		
		$todo = get_db_row ("ttodo", "id", $id);

		if (! get_workorder_acl($id)) {
			no_permission();
		}
		
		$creator = $todo["created_by_user"];
		$assigned_user = $todo["assigned_user"];
		$progress = $todo["progress"];
		$name = $todo["name"];
		$description = $todo["description"];
		$priority = $todo["priority"];
		$id_task = $todo["id_task"];
		$end_date = $todo["end_date"];
		$start_date = $todo["start_date"];
		$validation_date = $todo["validation_date"];
		$need_external_validation = $todo["need_external_validation"];
		$id_wo_category = $todo["id_wo_category"];
		$email_notify = $todo['email_notify'];
	}

	$tab = get_parameter ("tab", "");	

	if ($operation == "view" || $operation == "update") {

		$search_params="&owner=$assigned_user&creator=$creator";
		echo '<ul class="ui-tabs-nav">';

		if ($tab == "files")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=files&id='.$id.'"><span>'.__("Files").'</span></a></li>';
		
		if ($tab == "notes")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=notes&id='.$id.'"><span>'.__("Notes").'</span></a></li>';
		
		if ($tab == "wu")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=wu&id='.$id.'"><span>'.__("Add Workunit").'</span></a></li>';
		
		if ($tab == "")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id='.$id.'"><span>'.__("Workorder details").'</span></a></li>';
		
		echo '<li class="ui-tabs-title h1">' . __('Workorder details') . '</li>';
		echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=projects&sec2=operation/workorders/wo'.$search_params.'"><span>'.__("Search").'</span></a></li>';
		
		echo "</ul>";
		
		$name = get_db_value ('name', 'ttodo', 'id', $id);
		
		echo '<div class="under_tabs_info">' . sprintf(__('Workorder #%s: %s'), $id, $name) . '</div><br>';
	}

	// Create WU
	if ($tab == "wu") {
		$_POST["id_task"]=$id_task;
		include "operation/workorders/wo_workunits.php";
	}

	// Files
	if ($tab == "files") {
		$id_task = get_parameter("id_task");
		include "operation/workorders/wo_files.php";
	}

	// Files
	if ($tab == "notes") {
		include "operation/workorders/wo_notes.php";
	}	

	// Display main form / view 

	if ($tab == ""){
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'search-table-button';
		$table->colspan = array ();
		
		$table->colspan[6][0] = 2;
		$table->data = array ();
		
		$table->data[0][0] = print_input_text ('name', $name, '', 50, 120, true,
			__('Title'));
		
		$table->data[0][1] = print_select (get_priorities (), 'priority', $priority,
			'', '', '', true, false, false, __('Priority'));
		
		$table->data[1][0] = print_select_from_sql ('SELECT id, name FROM two_category ORDER BY name',
		'id_wo_category', $id_wo_category, '', __("Any"), 0, true, false, false,
		__('Category'));


		if ($creator != $config["id_user"]){
			$table->data[1][1] = print_label (__("Requires validation"), '', 'input', true);
			if ($need_external_validation == 1)
				$table->data[1][1] .= __("Yes");
			else
				$table->data[1][1] .= __("No");
			$table->data[1][1] .= print_input_hidden ("need_external_validation", $need_external_validation, true);
		} else
			$table->data[1][1] = print_checkbox ("need_external_validation", 1, $need_external_validation, true, __("Require external validation"));


		if ($creator != $config["id_user"]){
			$table->data[2][0] = print_label (__("Submitter"), '', 'input', true);
			$table->data[2][0] .= dame_nombre_real($creator);
			$table->data[2][0] .= print_input_hidden ("creator", $creator, true);
		} else {
			$table->data[2][0] = print_input_text_extended ('creator', $creator, 'text-user2', '', 15, 30, false, '',
				'', true, '', __('Submitter'))

			. print_help_tip (__("Type at least two characters to search"), true);
		}
		
		if ($creator != $config["id_user"] && !$section_permission['write']){
			$table->data[2][1] = print_label (__("Assigned user"), '', 'input', true);
			$table->data[2][1] .= dame_nombre_real($assigned_user);		
			$table->data[2][1] .= print_input_hidden ("assigned_user", $assigned_user, true);
		} else {
			$params['input_id'] = 'text-user';
			$params['input_name'] = 'assigned_user';
			$params['input_size'] = 30;
			$params['input_maxlength'] = 100;
			$params['input_value'] = $assigned_user;
			$params['title'] = 'Assigned user';
			$params['return'] = true;
			$params['return_help'] = true;
				
			$table->data[2][1] = user_print_autocomplete_input($params);
		}
		

		$table->data[3][0] = combo_task_user_participant ($config["id_user"], false, $id_task, true, __('Task'));
		if ($id_task) {
			$table->data[3][0] .= "&nbsp;&nbsp;<a id='task_link' title='".__('Open this task')."' target='_blank'href=' 
				index.php?sec=projects&sec2=operation/projects/task_detail&operation=view&id_task=$id_task'>";
			$table->data[3][0] .= "<img src='images/task.png'></a>";
		} else {
			$table->data[3][0] .= "&nbsp;&nbsp;<a id='task_link' title='".__('Open this task')."' target='_blank' href='javascript:;'></a>";
		}

		// Remove validated user if current user is not the creator OR this doesnt need to be validated
		if (($creator != $config["id_user"]) OR ($need_external_validation == 0))
			$wo_status_values = wo_status_array (1);	
		else
			$wo_status_values = wo_status_array (0);	


		$table->data[3][1] = print_select ($wo_status_values, 'progress', $progress, 0, '', -1, true, 0, false, __('Status') );
		
		if ($start_date == "0000-00-00 00:00:00") {
			$start_date = '';
		}
		
		if ($creator != $config["id_user"]){
			$table->data[4][0] = print_label (__("Start date"), '', 'input', true);
			$table->data[4][0] .= $start_date;
			$table->data[4][0] .= print_input_hidden ("start_date", $start_date, true);
		} else
			$table->data[4][0] = print_input_text ('start_date', $start_date , '', 25, 25, true, __('Start date'));
		
		if ($end_date == "0000-00-00 00:00:00"){
				$end_date = '';
		}

		if ($creator != $config["id_user"]){
			$table->data[4][1] = print_label (__("Deadline"), '', 'input', true);
			if ($end_date == "")
				$table->data[4][1] .= __("None");
			else
				$table->data[4][1] .= $end_date;
			$table->data[4][1] .= print_input_hidden ("end_date", $end_date, true);
		} else 
			$table->data[4][1] = print_input_text ('end_date', $end_date , '', 25, 25, true, __('Deadline'));

		$table->data[5][0] = print_checkbox_extended ('email_notify', 1, $email_notify,
                false, '', '', true, __('Notify changes by email'));
                
		$table->data[6][0] = print_textarea ('description', 12, 50, $description, '', true, __('Description'));

		if ($operation == 'create') {
			$button = print_submit_button (__('Create'), 'crt', false, 'class="sub create"', true);
			$button .= print_input_hidden ('operation', 'insert', true);
		} else {
			$button .= print_submit_button (__('Update'), 'upd', false, 'class="sub upd"', true);
			$button .= print_input_hidden ('operation', 'update2', true);
			$button .= print_input_hidden ('id', $id, true);
		}
		
		$table->data[7][0] = $button;
		$table->colspan[7][0] = 2;
		
		echo '<form id="form-wo" method="post">';
		print_table ($table);
		echo '</form>';
	}
}

// -------------------------
// Workorder listing
// -------------------------
if ($operation == "") {

	echo "<h1>".__('Work order management')."</h1>";

	$search_text = (string) get_parameter ('search_text');
	$id_wo_category = (int) get_parameter ('id_wo_category');
	$search_status = (int) get_parameter ("search_status",0);
	$owner = (string) get_parameter ("owner", "");

	$creator = (string) get_parameter ("creator", "");
	$id_category = get_parameter ("id_category");
	$id_project = (int) get_parameter ("id_project");
	$search_priority = get_parameter ("search_priority", -1);
	$need_validation =get_parameter("need_validation",0);

	$params = "&search_priority=$search_priority&search_status=$search_status&search_text=$search_text&id_category=$id_category&owner=$owner&creator=$creator&id_project=$id_project&need_validation=$need_validation";

	$where_clause = "WHERE 1=1 ";
	
	if ($need_validation){
		$where_clause .= " AND need_external_validation = 1 ";
	}

	if ($creator != ""){
		$where_clause .= " AND created_by_user = '$creator' ";
	}

	if ($search_priority > -1){
		$where_clause .= " AND priority = $search_priority ";
	}

	if ($owner != "") {
		$where_clause .= sprintf (' AND assigned_user =  "%s"', $owner);
	}

	if ($search_text != "") {
		$where_clause .= sprintf (' AND (name LIKE "%%%s%%" OR description LIKE "%%%s%%")', $search_text, $search_text);
	}
	
	if ($search_status > -1) {
		$where_clause .= sprintf (' AND progress = %d ', $search_status);
	}

	if ($id_category) {
		$where_clause .= sprintf(' AND id_wo_category = %d ', $id_category);
	}
	
	if ($id_project) {
		$where_clause .= sprintf(' AND id_task = ANY(SELECT id FROM ttask WHERE id_project = %d) ', $id_project);
	}

	echo '<form id="form-search_wo" action="index.php?sec=projects&sec2=operation/workorders/wo" method="post">';		
	
	$table = new StdClass();
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->width = "100%";

	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 15, 100, true, __('Search'));

	$table->data[0][1] = print_input_text_extended ('owner', $owner, 'text-user', '', 15, 30, false, '',
			'', true, '', __('Owner'))

		. print_help_tip (__("Type at least two characters to search"). ". " . __("Use '*' for get all values"), true);

	$table->data[0][2] = print_input_text_extended ('creator', $creator, 'text-user2', '', 15, 30, false, '',
			'', true, '', __('Submitter'))

		. print_help_tip (__("Type at least two characters to search"), true);


	$wo_status_values = wo_status_array ();		

	$table->data[1][0] = print_select ($wo_status_values, 'search_status', $search_status, '', __("Any"), -1, true, 0, false, __('WO Status') );

	$priorities = get_priorities();
	$table->data[1][1] = print_select ($priorities, 'search_priority', $search_priority, '', __("Any"), -1, true, 0, false, __('Priority') );
	
	$avatar = get_db_value ('avatar', 'tusuario', 'id_usuario', $config["id_user"]);
	if (!$avatar)
		$avatar = "avatar1";
	
	$table->data[1][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
	$table->data[1][2] .= ' <a href="index.php?sec=projects&sec2=operation/workorders/wo&owner='
		.$config["id_user"].'"><img src="images/avatars/'.$avatar.'.png" class="avatar_small" title="'.__('My WO\'s').'"></a>';
	$table->data[1][2] .= ' <a href="index.php?sec=projects&sec2=operation/workorders/wo&creator='
		.$config["id_user"].'"><img src="images/user_comment.png" title="'.__('My delegated WO\'s').'"></a>';
	
	$table->rowspan[0][3] = 3;
	
	if ($owner != "") {
		$table->data[0][3] = '<b>'.__('Submitters') .'</b>';
		$table->data[0][3] .= '<br>'. graph_workorder_num ('200', '100', 'submitter', $where_clause, 5);
	} else {
		$table->data[0][3] = '<b>'.__('Owners') .'</b>';
		$table->data[0][3] .= '<br><div class="pie_frame">'. graph_workorder_num ('200', '100', 'owner', $where_clause, 5) . '</div>';

	}
	
	print_table ($table);
	$table->data = array ();

	echo '<a href="javascript:;" onclick="$(\'#advanced_div\').slideToggle (); return false">';
	echo __('Advanced search &gt;&gt;');
	echo '</a>';
	echo '<div id="advanced_div" style="padding: 0px; margin: 0px; display: none;">';

	$table->data[0][0] = print_select_from_sql ('SELECT id, name FROM two_category ORDER BY name',
	'id_category', $id_category, '', __("Any"), 0, true, false, false,
	__('Category'));
	
	$table->data[0][1] = print_select_from_sql (get_projects_query($config['id_user']),
	'id_project', $id_project, '', __("Any"), 0, true, false, false,
	__('Project'));

	$table->data[0][2] =  print_checkbox ("need_validation", 1, $need_validation, true, __("Require validation"));
	print_table ($table);
	
	echo "</div>";
	echo '</form>';
	
	if ($owner == $config['id_user'] && $creator == "") {
		$order_by = "ORDER BY created_by_user, priority, last_update DESC";
	} elseif ($creator == $config['id_user'] && $owner == "") {
		$order_by = "ORDER BY assigned_user, priority, last_update DESC";
	} else {
		$order_by = "ORDER BY priority, last_update DESC";
	}
	
	$wos = get_workorders ($where_clause, $order_by);

	$wos = print_array_pagination ($wos, "index.php?sec=projects&sec2=operation/workorders/wo$params");

	if ($wos !== false) {
		unset ($table);
		$table = new StdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->rowstyle = array ();

		
		$table->head = array ();
		$table->head[0] = __('WO #');
		$table->head[2] = __('Name');
		$table->head[3] = __('Criticity');
		$table->head[4] = __('Status');
		$table->head[5] = __('Owner');
		$table->head[6] = __('Submmiter');
		$table->head[7] = __('Cat.');
		$table->head[8] = __('Deadline');
		$table->head[9] = __('Created/Updated');
		$table->head[10] = __('Options');
		/*$table->size[6] = '80px;';
		$table->size[5] = '130px;';*/
		

		foreach ($wos as $wo) {
			$data = array ();
			
			// Detect is the WO is pretty old 
			// Stored in $config["lead_warning_time"] in days, need to calc in secs for this

			$config["lead_warning_time"]= 7; // days
			$config["lead_warning_time"] = $config["lead_warning_time"] * 86400;

			if (calendar_time_diff ($wo["last_update"]) > $config["lead_warning_time"] ){
				$style = "background: #fff0f0";
			} else {
				$style = "";
			}

			if ($wo["end_date"] != "0000-00-00 00:00:00")
				if ($wo["end_date"] < date('Y-m-d H:i:s')){
					$style = "background: #fff0f0";
				}

			if ($wo["progress"] == 1)
					$style = "background: #f0fff0";	

			if ($wo["progress"] == 2)
					$style = "background: #f0f0ff";	


			$data[0] = "<a href='index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id=".
				$wo['id']."'>#<b>".$wo["id"]."</b></a>";

			$data[2] = "<a href='index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id=".
				$wo['id']."'>".short_string ($wo['name'],45)."</a>";

			if ($wo["id_task"] != 0){

				$id_project = get_db_value ("id_project", "ttask", "id", $wo["id_task"]);
				$project_title = short_string (get_db_value ("name", "tproject", "id", $id_project), 35);
				$task_title = short_string (get_db_value ("name", "ttask", "id", $wo["id_task"]), 35);
				$buffer = "<br><span style='font-size: 9px'>" . $project_title . " / " . $task_title . "</span>";
				$data[2] .= $buffer;
			}

			$data[3] = print_priority_flag_image ($wo["priority"], true);
			
			$data[4] = translate_wo_status($wo["progress"]);
			
			if ($wo["assigned_user"] == $config["id_user"]) {
				$data[5] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&owner='.$wo["assigned_user"].'">'.__("Me").'</a>';
			}
			else {
				$data[5] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&owner='.$wo["assigned_user"].'">'.$wo["assigned_user"].'</a>';
			}
			
			if ($wo["assigned_user"] != $wo["created_by_user"]) {
				if ($wo["created_by_user"] == $config["id_user"]) {
					$data[6] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&creator='.$wo["created_by_user"].'">'.__("Me").'</a>';
				} else {
					$data[6] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&creator='.$wo["created_by_user"].'">'.$wo["created_by_user"].'</a>';
				}
				if ($wo["need_external_validation"] == 1)
					$data[6] .= "<img src='images/bullet_delete.png' title='".__("Requires validation") . "'>";
			} else {
				if ($wo["created_by_user"] == $config["id_user"]) {
					$data[6] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&creator='.$wo["created_by_user"].'">'.__("Me").'</a>';
				} else {
					$data[6] = '<a href="index.php?sec=projects&sec2=operation/workorders/wo&creator='.$wo["created_by_user"].'">'.$wo["created_by_user"].'</a>';
				}
			}

			
			if ($wo["id_wo_category"]){
				$category = get_db_row ("two_category", "id", $wo["id_wo_category"]);
				$data[7] = "<img src='images/wo_category/".$category["icon"]."' title='".$category["name"]."'>";
			} else {
				$data[7] = "";
			}

			if ($wo["end_date"] != "0000-00-00 00:00:00")
				$data[8] = "<span style='font-size: 9px'>".substr($wo["end_date"],0,10). "</span>";
			else
				$data[8] = "--";

			$data[9] = "<span style='font-size: 9px'>". human_time_comparation($wo["start_date"]) . "<br>". human_time_comparation($wo["last_update"]). "</span>";
			
			$data[10] = "";
			if ($wo['assigned_user'] == $config["id_user"]){
				if ($wo["progress"] == 0){
					$data[10] .= "<a href='index.php?sec=projects&sec2=operation/workorders/wo$params&id=". $wo['id']."&set_progress=1'><img src='images/ack.png' title='".__("Set as finished")."'></a>";
				} 
			}

			if (($wo["progress"] < 2) AND ($wo["created_by_user"] == $config["id_user"]) AND ($wo["need_external_validation"] == 1) ){	
				$data[10] = "<a href='index.php?sec=projects&sec2=operation/workorders/wo$params&id="
					. $wo['id']."&set_progress=2&offset=$offset'><img src='images/rosette.png' title='".__("Validate")."'></a>";
			}

			// Evaluate different conditions to allow WO deletion
			$can_delete = dame_admin($config["id_user"]);

			if ($wo["created_by_user"] == $config["id_user"])
				$can_delete = 1;				

			if ($can_delete){
				$data[10] .= '&nbsp;&nbsp;<a href="index.php?sec=projects&sec2=operation/workorders/wo'
					.$params.'&operation=delete&id='.$wo['id'].'&offset='.$offset.'""onClick="if (!confirm(\''
					.__('Are you sure?').'\')) return false;"><img src="images/cross.png" title="' . __('Delete') . '"></a>';
			}

			array_push ($table->data, $data);
			array_push ($table->rowstyle, $style);
		}
		print_table ($table);
	}


} // Fin bloque else
?>

<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript" >

// Datepicker
add_ranged_datepicker ("#text-start_date", "#text-end_date", null);

// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-name');
validate_form("#form-wo");
var rules, messages;
// Rules: #text-name
rules = { required: true };
messages = { required: "<?php echo __('Name required')?>" };
add_validate_form_element_rules('#text-name', rules, messages);

$(document).ready (function () {
	$("#textarea-description").TextAreaResizer ();
	
	$("#id_task").change(function() {
		if ($("#id_task").val() > 0) {
			$("#task_link").html("<a id='task_link' title='<?php echo __('Open this task') ?>' target='_blank' "
				+ "href='index.php?sec=projects&sec2=operation/projects/task_detail&operation=view&id_task="
				+ $("#id_task").val() + "'><img src='images/task.png'></a>");
		} else {
			$("#task_link").html("");
		}
	});
	
	var idUser = "<?php echo $config['id_user'] ?>";
	
	if ($("#form-search_wo").length > 0) {
		bindAutocomplete ("#text-user", idUser);
		bindAutocomplete ("#text-user2", idUser);
		validate_user ("#form-search_wo", "#text-user", "<?php echo __('Invalid user')?>");
		validate_user ("#form-search_wo", "#text-user2", "<?php echo __('Invalid user')?>");
	} else if ($("#form-wo").length > 0) {
		var changeHandler = function (event, ui) {
			owner = $("#text-user").val();
			$.ajax({
				type: "POST",
				url: "ajax.php",
				data: {
					page: "operation/workorders/wo",
					change_combo_task: 1,
					id_user: owner
				},
				dataType: "html",
				success: function(data) {	
					$("#table1-3-0").html(data);
				}
			});
		};
		bindAutocomplete ("#text-user", idUser, false, changeHandler);
		validate_user ("#form-search_wo", "#text-user", "<?php echo __('Invalid user')?>");
		bindAutocomplete ("#text-user2", idUser, false, changeHandler);
		validate_user ("#form-search_wo", "#text-user2", "<?php echo __('Invalid user')?>");
	}
	
});

</script>
