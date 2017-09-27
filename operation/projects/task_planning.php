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

include_once ("include/functions_tasks.php");
include_once ("include/functions_graph.php");

$id_project = (int) get_parameter ('id_project');

if (! $id_project) {// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task manager without project");
	no_permission ();
}

$project_access = get_project_access ($config["id_user"], $id_project);
if (!$project_access["read"]) {
	audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task manager of unauthorized project");
	no_permission ();
}

$project = get_db_row ('tproject', 'id', $id_project);
$update = get_parameter("update", false);
$create = get_parameter("create", false);
$delete = get_parameter("delete", false);

if (!$update && !$create && !$delete) {
	if (! manage_any_task($config["id_user"], $id_project)) {
		audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task manager of unauthorized project");
		no_permission ();
	}
}

//Delete task
if($delete) {
	
	$task_access = get_project_access ($config["id_user"], $id_project, $delete);
	//Check if admin or project manager before delete the task
	if (! $task_access["manage"]) {
		audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to delete a task without permission");
		no_permission ();
	}
	
	delete_task ($delete);
	echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	project_tracking ($id_project, PROJECT_TASK_DELETED);
}

//Update tasks
if ($update) {
	
	//Get all task from DB to know the ids
	$sql = sprintf("SELECT id FROM ttask WHERE id_project = %d", $id_project);
	$task = get_db_all_rows_sql ($sql);
	$succ = 0;
	foreach ($task as $t) {
		
		//Get all post parameters for this task
		$id = $t['id'];
		
		$task_access = get_project_access ($config["id_user"], $id_project, $id);
		if (! $task_access["manage"]) {
			continue;
		}
		
		$name = get_parameter("name_$id");
		$owner = get_parameter ("owner_$id");
		$start = get_parameter ("start_$id");
		$end = get_parameter ("end_$id");
		$completion = get_parameter("status_$id");
					
		//hour fields hidden
		$hours = ((strtotime ($end) - strtotime ($start)) / (3600*24)) * $config['hours_perday'];
		$hours = $hours + $config['hours_perday'];
		
		//Check if the date was set properly
		//If there is a problem nothing will be updated
		if (strtotime ($start) > strtotime ($end)) {
			//Check date properly set
			echo ui_print_error_message (sprintf(__('Begin date cannot be before end date in task %s'), $name), '', true, 'h3', true);
			continue;
		}
		
		//Check name not empty
		//If there is a problem nothing will be updated
		if ($name == '') {
			echo ui_print_error_message (__('Task name cannot be empty'), '', true, 'h3', true);
			continue;
		}
		
				
		//Update task information
		$sql = sprintf ('UPDATE ttask SET name = "%s", completion = %d,
			start = "%s", end = "%s", hours = %d WHERE id = %d',
			$name, $completion, $start, $end, $hours, $id);
		$result = process_sql ($sql);
		
		//Check owners of this tasks and update them
		// if the task as more than one user don't update the users
		// if the task as only one user then update it!
		$sql = sprintf("SELECT COUNT(*) as num_users FROM trole_people_task where id_task = %d", $id);
		
		$result1 = process_sql($sql);		
		
		$result2 = true;//To avoid strange messages with many task users
		
		if ($result1[0]['num_users'] == 1) {		
			$sql = sprintf("SELECT id_role FROM trole_people_project 
					WHERE id_project = %d AND id_user = '%s'", $id_project, $config['id_user']);
						
			$id_role = process_sql($sql);
			$id_role = $id_role[0]['id_role'];
		
			$sql = sprintf('UPDATE trole_people_task SET id_user = "%s", id_role = %d WHERE id_task = %d', 
							$owner, $id_role, $id);

			$result2 = process_sql($sql);
		}
				
		if (($result !== false) && ($result1 !== false) && ($result2 !== false)) {
			$succ++;
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Task updated", "Task '$name' updated to project '$id_project'");
			task_tracking ($id, TASK_UPDATED);
		} else {
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		}

	}
	echo ui_print_success_message (sprintf(__('%d tasks successfully updated'), $succ), '', true, 'h3', true);
}

//Create a new task
if ($create) {
	
	$tasklist = get_parameter ("tasklist");
	
	// Massive creation of tasks
	if ($tasklist != "") {

		$tasklist = safe_output ($tasklist);

		$parent = (int) get_parameter ('padre');
		$start = get_parameter ('start_date2', date ("Y-m-d"));
		$end = get_parameter ('end_date2', date ("Y-m-d"));
		$owner = get_parameter('dueno');
		
		if ($parent) {
			$project_access = get_project_access ($config["id_user"], $id_project);
			if (!$project_access["manage"]) {
				$task_access = get_project_access ($config["id_user"], $id_project, $parent);
				if (!$task_access["manage"]) {
					audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to create tasks in an unauthorized project");
					no_permission ();
				}
			}
		}
		else {
			$project_access = get_project_access ($config["id_user"], $id_project);
			if (!$project_access["manage"]) {
				audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to create tasks in an unauthorized project");
				no_permission ();
			}
		}
		
		$data_array = preg_split ("/\n/", $tasklist);
		
		foreach ($data_array as $data_item){
			$data = trim($data_item);
			
			if ($data != "") {
				$sql = sprintf ('INSERT INTO ttask (id_project, name, id_parent_task, start, end) 
								VALUES (%d, "%s", %d, "%s", "%s")',
								$id_project, safe_input ($data), $parent, $start, $end);

				$id_task = process_sql ($sql, 'insert_id');
				
				if ($id_task) {
					$sql = sprintf("SELECT id_role FROM trole_people_project
									WHERE id_project = %d AND id_user = '%s'", $id_project, $owner);
					
					$id_role = process_sql($sql);
					$role = $id_role[0]['id_role'];

					$sql = sprintf('INSERT INTO trole_people_task (id_user, id_role, id_task)
									VALUES ("%s", %d, %d)', $owner, $role, $id_task);

					$result2 = process_sql($sql);
					if (! $result2) {
						echo ui_print_error_message (__('An error ocurred setting the permissions for the task'), '', true, 'h3', true);
					}
				}
				else {
					echo ui_print_error_message (__('The task could not be created'), '', true, 'h3', true);
				}

			}
		}
		echo ui_print_success_message (__('Project created successfully'), '', true, 'h3', true);
	} 
}

$project_name =  get_db_value ("name", "tproject", "id", $id_project);
	
// Print title and menu.
$section_title = __('Project management');
$section_subtitle =__("Task planning")." &raquo; $project_name";
$p_menu = print_project_tabs();
print_title_with_menu ($section_title, $section_subtitle, "task_planning", 'projects', $p_menu, 'task_plan');

//Calculate task summary stats!

//Draw task status statistics by hand!
$sql = sprintf("SELECT id, completion FROM ttask WHERE id_project = %d", $id_project);

$res = process_sql($sql);
if (empty($res)) {
	$res = array();
}

$verified = 0;
$completed = 0;
$in_process = 0;
$pending = 0;

foreach ($res as $r) {
	if ($r['completion'] < 40) {
		$pending++;
	}
	else if ($r['completion'] < 90) {
		$in_process++;
	}
	else if ($r['completion'] < 100) {
		$completed++;
	}
	else if ($r['completion'] == 100) {
		$verified++;
	}
}

//Get project users
$sql = sprintf("SELECT DISTINCT(id_user) FROM trole_people_project WHERE id_project = %d", $id_project);
$users_db = get_db_all_rows_sql ($sql);

if (is_array($users_db) || is_object($users_db)){
	foreach ($users_db as $u) {
		$users[$u['id_user']] = $u['id_user'];
	}
} else {
	$users="";
}

echo "<div class='divform'>";
//Form for task creation
echo "<form id='form-new_tasks' method='post' action='index.php?sec=projects&sec2=operation/projects/task_planning&id_project=".$id_project."'>";
echo "<table class='search-table'>";
	
	// Taskname
	echo "<tr><td><b>". __('Put taskname in each line'). "</b></td></tr>";
	echo "<tr><td>";
		print_textarea ('tasklist', 3, 30);
	echo "</td></tr>";
	
	// User assigned by default
	echo "<tr><td><b>". __("Owner"). "</b></td></tr>";
	echo "<tr><td>"; 
		print_select ($users, "dueno", $config['id_user'], '', '', 0, false, 0, false);
	echo "</td></tr>";
	
	//Task parent combo
	echo "<tr><td><b>". __('Parent'). "</b></td></tr>";
	echo "<tr><td>";
		combo_task_user_manager ($config['id_user'], 0, false, "", 'padre', __('None'), false, $id_project);
	echo "</td></tr>";
	
	//Start date and End date
	echo "<tr>";
		echo "<td><b>".	__('Start'). "</b><tr><td>";
		$start = date ("Y-m-d");
		print_input_text_extended ("start_date2", $start, "start_date", '', 11, 15, 0, '', "", false, false);
	echo "</tr><tr>";
		echo "<td><b>".	__('End'). "</b><tr><td>";
		$end = date ("Y-m-d");
		print_input_text_extended ("end_date2", $end, "end_date", '', 11, 15, 0, '', "", false, false);
	echo "</td></tr>";
	
	//Create button
	echo "<tr><td colspan='2'>";
		print_input_hidden ('create', 1);
		print_submit_button (__('Create'), '', false);
	echo "</td></tr>";
echo "</table>";
echo "</form>";
echo "<table class='search-table'>";
	echo "<tr><td>";
		print_button (__('Update'), 'update', false, 'document.forms[\'form-tasks\'].submit()','class="submit_update"');
	echo "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='divresult'>";
echo "<table class = 'listing'><tr>";
	echo "<th>".__("Verified").":<span style='background-color:#d2e7a4;'>&nbsp;".$verified."&nbsp;</span></th>";
	echo "<th>".__("Completed").":<span style='background-color:#b8e0fd;'>&nbsp;".$completed."&nbsp;</span></th>";
	echo "<th>".__("In process").":<span style='background-color:#fceaa2;'>&nbsp;".$in_process."&nbsp;</span></th>";
	echo "<th>".__("Pending").":<span style='background-color:#FFF;'>&nbsp;".$pending."&nbsp;</span></th>";
echo "</tr></table>";

$content_general = "<tr><td valign='top' >";
		$content = '<tr><td colspan="2" valign=top style="height:250px;">'.graph_workunit_project_user_single(350, 150, $id_project).'</td></tr>';
	$content_general .=	print_container('planning_hours_worked', __("Hours worked"), $content, 'no', true, '10px');
$content_general .= "</td><td valign='top' >";
		$content = '<tr><td colspan="2" valign=top style="height:250px;">'. graph_workunit_project_task_status(350, 150, $id_project).'</td></tr>';
	$content_general .= print_container('planning_hours_summary_task', __("Summary task status"), $content, 'no', true, '10px');
$content_general .= "</td><td valign='top'  >";
		$content = '<tr><td colspan="2" valign=top style="height:250px;">'. graph_project_task_per_user(350, 150, $id_project).'</td></tr>';
	$content_general .= print_container('planning_hours_task_user', __("Task per user"), $content, 'no', true, '10px', '', '', 1, 'less_widht');
$content_general .= "</td></tr>";
print_container('task_information', __("Task Information"), $content_general, 'closed', false, '10px', '', '', 2);

//Starting main form for this view
echo "<form id='form-tasks' method='post' action='index.php?sec=projects&sec2=operation/projects/task_planning&id_project=".$id_project."'>";
print_input_hidden('update', 'update');
//Create table and table header.
echo "<table class='listing'>";
	echo "<thead>";
		echo "<tr>";
			echo "<th>".__('Task')."</th>";
			echo "<th>".__('Owner')."</th>";
			echo "<th>".__('Start date')."</th>";
			echo "<th>".__('End date')."</th>";
			echo "<th>".__('Hours worked')."</th>";
			echo "<th>".__('Delay (days)')."</th>";
			echo "<th>".__('Status')."</th>";
			// Last column (Del)
			echo "<th>".__('Op.')."</th>";
		echo "</tr>";
	echo "</thead>";
	//Print table content
	echo "<tbody>";
		show_task_tree ($table, $id_project, 0, 0, $users);
	echo "</tbody>";
echo "</table>";
echo "</form>";
echo "</div>";
echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
function show_task_row ($table, $id_project, $task, $level, $users) {
	global $config;
	
	$id_task = $task['id'];
	
	// Second column (Task  name)
	$prefix = '';
	for ($i = 0; $i < $level; $i++)
		$prefix .= '<img src="images/small_arrow_right_green.gif" style="position: relative; top: 5px;"> ';
	
	echo "<td>";
	
	echo $prefix.print_input_text ("name_".$id_task, $task['name'], "", 10, 0, true);
	
	echo"</td>";
	
	// Thrid column (Owner)Completion
	echo "<td>";

	$owners = get_db_value ('COUNT(DISTINCT(id_user))', 'trole_people_task', 'id_task', $task['id']);
	
	if ($owners > 1) {
		echo combo_users_task ($task['id'], 1, true);
		echo ' ';
		echo $owners;
	} else {
		$owner_id = get_db_value ('id_user', 'trole_people_task', 'id_task', $task['id']);
		print_select ($users, "owner_".$id_task, $owner_id, '', '', 0, false, 0, true, false, false, 'width: 90px');	
	}
	
	echo "</td>";
	
	// Fourth column (Start date)
	echo "<td>";
	print_input_text_extended ("start_".$id_task, $task['start'], "start_".$id_task, '', 9, 15, 0, '', 'style="font-size:9px;"');
	
	echo "</td>";

	// Fifth column (End date)
	echo "<td>";
	print_input_text_extended ("end_".$id_task, $task['end'], "end_".$id_task, '', 9, 15, 0, '', 'style="font-size:9px;"');
	echo "</td>";
	
	//Worked time based on workunits
	$worked_time = get_task_workunit_hours ($id_task);
	echo "<td>".$worked_time."</td>";
	
	// Sixth column (Delay)
	//If task was completed delay is 0
	if ($task['completion']) {
		$delay = 0;
	} else {
		//If was not completed check for time delay from end to now
		$end = strtotime ($task['end']);
		$now = time ();
		
		$a_day_in_sec = 3600*24;
		
		if ($now > $end) {
			$diff = $now - $end;
			$delay = $diff / $a_day_in_sec;
			$delay = round ($delay, 1);
		} else {
			$delay = 0;
		}
	}
	
	echo "<td>".$delay."</td>";

	// Seventh column (Delay)
	
	//Task status
	/*
	 * 0%-40% = Pending
	 * 41%-90% = In process
	 * 91%-99% = Completed
	 * 100% = Verified
	 * 
	 */
	 
	//Check selected status 
	$selected = 0;
	if ($task['completion'] < 40) {
		$selected = 0;
	} else if ($task['completion'] < 90) {
		$selected = 45;
	} else if ($task['completion'] < 100) {
		$selected = 95;
	} else if ($task['completion'] == 100) {
		$selected = 100;
	}
	
	$fields = array();
	$fields[0] = __("Pending");
	$fields[45] = __("In process");
	$fields[95] = __("Completed");
	$fields[100] = __("Verified");
	
	echo "<td>";	
	
	print_select  ($fields, "status_".$id_task, $selected, '', '', 0, false, 0, true, false, false, "width: 100px;");
	echo"</td>";

	// Last Edit and del column. (Del) Only for PM flag
	//Create new task only if PM && TM flags or PW and project manager.
	echo "<td style='text-align:center;'>";
	echo '<a href="index.php?sec=projects&sec2=operation/projects/task_detail&id_project='.$id_project.'&id_task='.$task['id'].'&operation=view">';
	echo '<img style="margin-right: 6px;" src="images/wrench.png">';
	echo '</a>';
	$offset=0;
	$search_params='';
	echo "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_task_panning\",".$id_project.",".$task["id"].",".$offset.",\"".$search_params."\");'><img src='images/cross.png' title='".__('Delete')."'></a>";
	echo "</td>";
}

function show_task_tree (&$table, $id_project, $level, $id_parent_task, $users) {
	global $config;
	
	$sql = sprintf ('SELECT * FROM ttask
		WHERE id_project = %d
		AND id_parent_task = %d
		ORDER BY name', $id_project, $id_parent_task);
	$new = true;
	
	while ($task = get_db_all_row_by_steps_sql($new, $result, $sql)) {
		$new = false;
		
		//If user belong to task then create a new row in the table
		$task_access = get_project_access ($config['id_user'], $id_project, $task['id'], false, true);
		
		if ($task_access['manage']) {
			//Each tr has the task id as the html id object!
			//Check completion for tr background color
			
			if ($task['completion'] < 40) {
				$color = "#FFF";
			} else if ($task['completion'] < 90) {
				$color = "#fceaa2";
			} else if ($task['completion'] < 100) {
				$color = "#b8e0fd";
			} else if ($task['completion'] == 100) {
				$color = "#d2e7a4";
			}
			
			echo "<tr id=".$task['id']." bgcolor='$color'>";
				show_task_row ($table, $id_project, $task, $level, $users);
			echo "</tr>";
		}
		show_task_tree ($table, $id_project, $level + 1, $task['id'], $users);
	}
}

?>

<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

//Configure calendar dates
add_task_planning_datepicker();

// Form validation
validate_form("#form-tasks");
validate_form("#form-new_tasks");
var rules, messages;
// Rules: #textarea-tasklist
rules = {
	required: true,
	remote: {
		url: "ajax.php",
		type: "POST",
		data: {
		  page: "include/ajax/remote_validations",
		  search_existing_task: 1,
		  type: "create",
		  task_name: function() { return $("#textarea-tasklist").val() },
		  project_id: <?php echo $id_project?>
		}
	}
};
messages = {
	required: "<?php echo __('Task required')?>",
	remote: "<?php echo __('Existing tasks are not permitted')?>"
};
add_validate_form_element_rules('#textarea-tasklist', rules, messages);

// Rules: [id*='text-name']
rules = {
	required: true,
	remote: {
		url: "ajax.php",
		type: "POST",
		data: {
		  page: "include/ajax/remote_validations",
		  search_existing_task: 1,
		  type: "view",
		  task_name: function() { return $(this).val() },
		  project_id: <?php echo $id_project?>
		}
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This task already exists')?>"
};
add_validate_form_element_rules('[id*=\'text-name\']', rules, messages);

$(document).ready (function () {
	
	//Change row color dinamically when status is changed
	$('select[name^="status"]').change(function() {
		name = $(this).attr('name');
		id = name.substr(7)
		color="#b8e0fd";
		completion = $(this).val();
		
		if (completion < 40) {
			color = "#b8e0fd";
		} else if (completion < 90) {
			color = "#fceaa2";
		} else if (completion < 100) {
			color = "#b8e0fd";
		} else if (completion == 100) {
			color = "#D2E7A4";
		}
		
		$('#'+id).attr('bgcolor', color);
	});
});

</script>

