<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;
check_login ();

include_once ("include/functions_projects.php");
include_once ("include/functions_graph.php");
include_once ("include/functions_tasks.php");

// Get our main stuff
$id_project = get_parameter ('id_project', -1);
$id_task = get_parameter ('id_task', -1);
$operation = (string) get_parameter ('operation');
$gantt_editor = (int) get_parameter("gantt_editor");

$hours = 0;
$estimated_cost = 0;


// ACL Check for this task
$project_permission = get_project_access ($config["id_user"], $id_project);
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);

if ($operation == "") {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task detail without operation");
	no_permission();
} elseif ($operation == "create" && !manage_any_task($config['id_user'], $id_project)) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a task without access");
	no_permission();
} elseif ($operation == "insert") {
	$id_parent = (int) get_parameter ('parent');
	if ($id_parent == 0) {
		if (!$project_permission['manage']) {
			// Doesn't have access to this page
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to insert a task without access");
			no_permission();
		}
	}
	$task_permission = get_project_access ($config["id_user"], $id_project, $id_parent, false, true);
	if (!$task_permission['manage']) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to insert a task without access");
		no_permission();
	}
} elseif ($operation == "update" && !$task_permission['manage']) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a task without access");
	no_permission();
} elseif ($operation == "view" && !$task_permission['read']) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to view a task without access");
	no_permission();
}  elseif ($operation == "update" && $id_task == -1) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a task without task id");
	no_permission();
}

// Get names
if ($id_project)
	$project_name = get_db_value ('name', 'tproject', 'id', $id_project);
else
	$project_name = '';

if ($id_task)
	$task_name = get_db_value ('name', 'ttask', 'id', $id_task);
else
	$task_name = '';


// Init variables
$name = "";
$description = "";
$end = date("Y-m-d");
$start = date("Y-m-d");
$completion = 0;
$priority = 1;
$result_output = "";
$parent = 0;
$count_hours = 1;
$cc = "";


// Create task
if ($operation == "insert") {
	$name = get_parameter ('name');
	$start = get_parameter ('start_date', date ("Y-m-d"));
	$end = get_parameter ('end_date', date ("Y-m-d"));
	
	if ($name == '') {
		$operation = 'create';
		$result_output = ui_print_error_message (__('Name cannot be empty'), '', true, 'h3', true);
	}
	elseif (!strtotime ($start)){
		$operation = 'create';
		$result_output = ui_print_error_message (__('Malformed start date'), '', true, 'h3', true);	
	}
	elseif (!strtotime ($end)){
		$operation = 'create';
		$result_output = ui_print_error_message (__('Malformed end date'), '', true, 'h3', true);
	}
	elseif (strtotime ($start) > strtotime ($end)) {
		$operation = 'create';
		$result_output = ui_print_error_message (__('Begin date cannot be before end date'), '', true, 'h3', true);
	}
	else {
		$description = (string) get_parameter ('description');
		$priority = (int) get_parameter ('priority');
		$completion = (int) get_parameter ('completion');
		$parent = (int) get_parameter ('parent');
		$hours = (int) get_parameter ('hours');
		$periodicity = (string) get_parameter ('periodicity', 'none');
		$estimated_cost = (int) get_parameter ('estimated_cost');
		$count_hours = (int) get_parameter("count_hours");
		$cc = get_parameter('cc', '');
	
		$sql = sprintf ('INSERT INTO ttask (id_project, name, description, priority,
			completion, start, end, id_parent_task, hours, estimated_cost,
			periodicity, count_hours, cc)
			VALUES (%d, "%s", "%s", %d, %d, "%s", "%s", %d, %d, %f, "%s", %d, "%s")',
			$id_project, $name, $description, $priority, $completion, $start, $end,
			$parent, $hours, $estimated_cost, $periodicity, $count_hours, $cc);
		$id_task = process_sql ($sql, 'insert_id');
		if ($id_task !== false) {
			$result_output = ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Task added to project", "Task '$name' added to project '$id_project'");
			$operation = "view";
	
			// Show link to continue working with Task
			$result_output .= "<p><h3>";
			$result_output .= "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$id_task&operation=view'>";
			$result_output .= __("Continue working with task #").$id_task;
			$result_output .= "</a></h3></p>";
			// Add all users assigned to current project for new task or parent task if has parent
			if ($parent != 0)
				$query1="SELECT * FROM trole_people_task WHERE id_task = $parent";
			else
				$query1="SELECT * FROM trole_people_project WHERE id_project = $id_project";
			$resq1=mysql_query($query1);
			while ($row=mysql_fetch_array($resq1)) {
				$id_role_tt = $row["id_role"];
				$id_user_tt = $row["id_user"];
				$sql = "INSERT INTO trole_people_task
				(id_task, id_user, id_role) VALUES
				($id_task, '$id_user_tt', $id_role_tt)";
				mysql_query($sql);
			}
			task_tracking ($id_task, TASK_CREATED);
			project_tracking ($id_project, PROJECT_TASK_ADDED);


			//Update task links
			$links_0 = get_parameter("links_0");
			$links_1 = get_parameter("links_1");
			$links_2 = get_parameter("links_2");
			projects_update_task_links ($id_task, $links_0, 0);
			projects_update_task_links ($id_task, $links_1, 1);
			projects_update_task_links ($id_task, $links_2, 2);		
		}
		else {
			$update_mode = 0;
			$create_mode = 1;
			$result_output = ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		}
	}
}

// -----------
// Update task
// -----------
if ($operation == "update") {
	// Get current completion
	$current_completion = get_db_value('completion', 'ttask', 'id', $id_task);
	
	$name = (string) get_parameter ('name');
	$start = get_parameter ('start_date', date ("Y-m-d"));
	$end = get_parameter ('end_date', date ("Y-m-d"));
	
	if ($name == '') {
		$operation = 'update';
		$result_output = ui_print_error_message (__('Name cannot be empty'), '', true, 'h3', true);
	}
	elseif (!strtotime ($start)){
		$operation = 'update';
		$result_output = ui_print_error_message (__('Malformed start date'), '', true, 'h3', true);
	}
	elseif (!strtotime ($end)){
		$operation = 'update';
		$result_output = ui_print_error_message (__('Malformed end date'), '', true, 'h3', true);
	}
	elseif (strtotime ($start) > strtotime ($end)) {
		$operation = 'update';
		$result_output = ui_print_error_message (__('Begin date cannot be before end date'), '', true, 'h3', true);
	} else {
		$description = (string) get_parameter ('description');
		$priority = (int) get_parameter ('priority');
		$completion = (int) get_parameter ('completion');
		$parent = (int) get_parameter ('parent');
		$hours = (int) get_parameter ('hours');
		$periodicity = (string) get_parameter ('periodicity', 'none');
		$estimated_cost = (int) get_parameter ('estimated_cost');
		$count_hours = get_parameter("count_hours");
		$cc = get_parameter('cc', '');
		
		$sql = sprintf ('UPDATE ttask SET name = "%s", description = "%s",
				priority = %d, completion = %d,
				start = "%s", end = "%s", hours = %d,
				periodicity = "%s", estimated_cost = "%f",
				id_parent_task = %d, count_hours = %d,
				cc = "%s"
				WHERE id = %d',
				$name, $description, $priority, $completion, $start, $end,
				$hours, $periodicity, $estimated_cost, $parent, $count_hours,
				$cc, $id_task);
		
		if ($id_task != $parent) {
			$result = process_sql ($sql);		
		}
		else {
			$result = false;
		}

		//Update task links
		$links_0 = get_parameter("links_0");
		$links_1 = get_parameter("links_1");
		$links_2 = get_parameter("links_2");
		projects_update_task_links ($id_task, $links_0, 0);
		projects_update_task_links ($id_task, $links_1, 1);
		projects_update_task_links ($id_task, $links_2, 2);

		if ($result !== false) {
			$result_output = ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Task updated", "Task '$name' updated to project '$id_project'");
			$operation = "view";
			task_tracking ($id_task, TASK_UPDATED);
			
			
			// ONLY recalculate the complete if count hours flag is activated
			if($count_hours) {
				$hours = set_task_completion ($id_task);
			}
		}
		else {
			$result_output = ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		}
	}
	if ($gantt_editor) {
		echo $result_output;
		exit;
	}
}

// Edition / View mode
if ($operation == "view") {
	$task = get_db_row ('ttask', 'id', $id_task);
	
	// Get values
	$name = $task['name'];
	$description = $task['description'];
	$completion = $task['completion'];
	$priority = $task['priority'];
	$dep_type = $task['dep_type'];
	$start = $task['start'];
	$end = $task['end'];
	$estimated_cost = $task['estimated_cost'];
	$hours = $task['hours'];
	$parent = $task['id_parent_task'];
	$periodicity = $task['periodicity'];
	$count_hours = $task['count_hours'];
	$cc = $task['cc'];
		
} 

echo $result_output;

// ********************************************************************************************************
// Show forms
// ********************************************************************************************************

if ($id_task)
	$task_name = get_db_value ('name', 'ttask', 'id', $id_task);
else
	$task_name = '';

if (!$gantt_editor) {
	
	$section_title = __('Task management');
	if ($operation == "create") {
		// Print project menu on creation time.
		$section_subtitle = __("Create task");
		$p_menu = print_project_tabs();
		print_title_with_menu ($section_title, $section_subtitle, "create_task", 'projects', $p_menu, 'task_new');
	} else {
		$section_subtitle = $task_name;
		$t_menu = print_task_tabs('', $id_task);
		print_title_with_menu ($section_title, $section_subtitle, "create_task", 'projects', $t_menu, 'detail');
	}	
}
else {
	echo "<div id='button-bar-title' style='margin-top: 5px; margin-bottom: 9px;'>";
	echo "<ul>";
	echo "<li>";
		echo "<a onclick='toggle_editor_gantt($id_project, $id_task, \"stats\")'>".
		print_image ("images/chart_bar_dark.png", true, array("title" => __("Statistics"))) .
		"</a>";
	echo "</li>";
	echo "</ul>";
	echo "</div>";	
}

echo '<form id="form-new_project" method="post" action="index.php?sec=projects&sec2=operation/projects/task_detail">';
$table = new stdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->rowspan = array ();
$table->colspan = array ();
$table->style = array ();
$table->style[0] = 'width: 30%';
$table->style[1] = 'width: 30%';
$table->style[2] = 'width: 30%';
$table->data = array ();
$table->cellspacing = 2;
$table->cellpadding = 2;

$table->data[0][0] = print_input_text_extended ('name', $name, '', '', 40, 240, false, '', "style='width:300px;'", true, false, __('Name'));

$table->data[0][1] = print_select (get_priorities (), 'priority', $priority,
	'', '', '', true, false, false, __('Priority'));

if ($project_permission['manage'] || $operation == "view") {
	$combo_none = __('None');
} else {
	$combo_none = false;
}

$table->data[0][2] = combo_task_user_manager ($config['id_user'], $parent, true, __('Parent'), 'parent', $combo_none, false, $id_project, $id_task);

$table->data[1][0] = print_input_text_extended ('cc', $cc, '', '', 40,
			240, false, '', "style='width:300px;'", true, false, 
				__('CC') . print_help_tip (__("Email to notify changes in workunits"), true));
if(!isset($periodicity)){
	$periodicity = "";
}
$table->data[1][1] = print_select (get_periodicities (), 'periodicity', $periodicity, '', __('None'), 'none', true, false, false, __('Recurrence'));

$table->data[1][2] = print_checkbox_extended ('count_hours', 1, $count_hours,
		false, '', '', true, __('Completion based on hours') . 
		print_help_tip (__("Calculated task completion using workunits inserted by project members, if not it uses Completion field of this form"), true));

$table->data[2][0] = print_input_text ('start_date', $start, '', 8, 15, true, __('Start'));
$table->data[2][1] = print_input_text ('end_date', $end, '', 8, 15, true, __('End'));



$table->data[2][3] = print_input_text ('hours', $hours, '', 8, 5, true, __('Estimated hours'));
$table->data[2][3] .= "&nbsp;&nbsp;<a href='javascript: show_calculation();'>" . print_image('images/play.gif', true, array('title' => __('Calculate hours'))) . "</a>";

$table->data[3][0] = print_input_text ('estimated_cost', $estimated_cost, '', 8,
	11, true, __('Estimated cost'));
$table->data[3][0] .= ' '.$config['currency'];

$table->colspan[4][0] = 3;
$completion_label = __('Completion')." <em>(<span id=completion>".$completion."%</span>)</em>";

$table->data[4][0] = print_label ($completion_label, '', '', true,
	'<div id="slider" style="margin-top: 15px;margin-bottom: 15px; width: 99%;"><div class="ui-slider-handle"></div></div>');
$table->data[4][0] .= print_input_hidden ('completion', $completion, true);

//////TABLA ADVANCED
$links_1 = projects_get_task_links ($id_project, $id_task, 1);

$table_advanced = "<tr>";
$hint = print_help_tip (__("The task cannot start before all tasks in this section start"), true);
$table_advanced .= "<td style='text-align:left;'>" . print_select ($links_1, 'link_1', NULL,
								'', '', '', true, false, false, __('Start to start').$hint);
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: show_task_link_selector(1,".$id_project.",".$id_task.");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: remove_link(1);'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";

$links_0 = projects_get_task_links ($id_project, $id_task, 0);

$hint = print_help_tip (__("The task cannot start before all tasks in this section end"), true);
$table_advanced .= "<td style='text-align:left;'>" .  print_select ($links_0, 'link_0', NULL,
								'', '', '', true, false, false, __('Finish to start').$hint);
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: show_task_link_selector(0,".$id_project.",".$id_task.");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: remove_link(0);'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";

$links_2 = projects_get_task_links ($id_project, $id_task, 2);

$hint = print_help_tip (__("The task cannot end before all tasks in this section end, although it may end later"), true);
$table_advanced .= "<td style='text-align:left;'>" .  print_select ($links_2, 'link_2', NULL,
								'', '', '', true, false, false, __('Finish to finish').$hint);
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: show_task_link_selector(2,".$id_project.",".$id_task.");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
$table_advanced .= "&nbsp;&nbsp;<a href='javascript: remove_link(2);'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";

$table->colspan['row_links'][0] = 3;
$table->style['row_links'] = 'margin-top: 10px;';
$table->data['row_links'][0] = print_container('task_links', __('Task links'), $table_advanced, 'open', true, '10px', '', '', 2, 'no_border_bottom " style="width: 99%;"');

$table->colspan[5][0] = 3;
$table->data[5][0] = print_textarea ('description', 8, 30, $description, '',
	true, __('Description'));

print_table ($table);

$button = '';
echo '<div class="button-form" style="width:100%;">';
if (($operation != "create" && $task_permission['manage']) || $operation == "create") {
	unset($table->data);
	$table->width = '100%';
	$table->class = "button-form";
	if ($operation != "create") {

		if ($gantt_editor) {
			$button .= print_submit_button (__('Delete'), 'delete_btn', false, 'class="sub delete"', true);
		}
		$button .= print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
		$button .= print_input_hidden ('operation', 'update', true);
	} else {
		$button .= print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
		$button .= print_input_hidden ('operation', 'insert', true);
	}
	$button .= print_input_hidden ('id_project', $id_project, true);
	$button .= print_input_hidden ('id_task', $id_task, true);
	$table->data['button'][0] = $button;
	$table->colspan['button'][0] = 3;
}

	print_table($table);
echo '</div>';

//Print input hidden for task links which actually exist
foreach ($links_0 as $k => $l) {
	print_input_hidden('links_0[]', $k);
}

foreach ($links_1 as $k => $l) {
	print_input_hidden('links_1[]', $k);
}

foreach ($links_2 as $k => $l) {
	print_input_hidden('links_2[]', $k);
}
echo '</form>';
echo "<div id='task_search_modal'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Calculator")."' id='calculator_window'></div>";
?>
<script type="text/javascript" src="include/js/jquery.ui.slider.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/integria_projects.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

var gantt_editor = <?php echo $gantt_editor;?>;
var count_hours = <?php echo $count_hours;?>;
var id_task = <?php echo $id_task;?>;

// Datepicker
add_ranged_datepicker ("#text-start_date", "#text-end_date", function (datetext) {
	hours_day = <?php echo $config['hours_perday'];?>;
	start_date = $("#text-start_date").datepicker ("getDate"); 
	end_date = $(this).datepicker ("getDate");
	if (end_date < start_date) {
		pulsate (this);
	} else {
		hours = Math.floor ((end_date - start_date) / 86400000 * hours_day);
		hours = hours + hours_day;
		$("#text-hours").attr ("value", hours);
	}
});

$(document).ready (function () {
	
	$("#textarea-description").TextAreaResizer ();
	
	$("#slider").slider ({
		min: 0,
		max: 100,
		stepping: 1,
		value: <?php echo $completion?>,
		slide: function (event, ui) {
			$("#completion").empty ().append (ui.value+"%");
		},
		change: function (event, ui) {
			$("#hidden-completion").attr ("value", ui.value);
		}
	});

	if (gantt_editor) {
		$("#submit-update_btn").click(function () {
			submit_task_editor_form_gantt();
			return false;
		});

		$("#submit-delete_btn").click(function(){
			delete_task_editor_form_gantt();
			return false;
		})
	}

	if (count_hours) {
		$('#slider').slider( "option", "disabled", true );
	}

	$("#checkbox-count_hours").change(function (e) {
		toggle_count_hours_checkbox (id_task);
	});
});


// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-task_detail");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_task: 1,
			type: "view",
			task_name: function() { return $('#text-name').val() },
			task_id: <?php echo $id_task?>,
			project_id: <?php echo $id_project?>
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This task already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>

