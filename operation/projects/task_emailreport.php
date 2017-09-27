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
check_login ();

// Get parameters
$id_project = get_parameter ('id_project');
$id_task = get_parameter ('id_task', -1);
$project_manager = get_db_value ('id_owner', 'tproject', 'id', $id_project);
$operation = (string) get_parameter ('operation');
$title = get_parameter ("title", "");
$description = get_parameter ("description", "");

// ACL
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (!$task_permission["manage"]) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task email report  without permission");
	no_permission();
}

if ($operation == "generate_email") {
	$task_participants = get_db_all_rows_sql ("SELECT direccion, nombre_real FROM tusuario, trole_people_task WHERE tusuario.id_usuario = trole_people_task.id_user AND trole_people_task.id_task = $id_task");
	$participants ="";
	foreach ($task_participants as $participant){
		$participant["direccion"];
		$text = ascii_output ($description);
		$subject = ascii_output ($title);
		integria_sendmail ($participant["direccion"], $subject, $text);
	}
	echo ui_print_success_message (__("Operation successfully completed"), '', true, 'h3', true);
}

// Get names
if ($id_project)
	$project_name = get_db_value ('name', 'tproject', 'id', $id_project);
else
	$project_name = '';

if ($id_task)
	$task = get_db_row ('ttask', 'id', $id_task);

$task_days = $task["hours"] / $config["hours_perday"];
$task_cost = $task['estimated_cost']. $config["currency"];
$prio_array = get_priorities();

$task_participants = get_db_all_rows_sql ("SELECT direccion, nombre_real FROM tusuario, trole_people_task WHERE tusuario.id_usuario = trole_people_task.id_user AND trole_people_task.id_task = $id_task");
$participants ="";
foreach ($task_participants as $participant){
	$participants .= $participant["nombre_real"]. ", ";
}

$title = "[".$config["sitename"]."] Task report - $project_name / ".$task["name"];

$description = sprintf ( "This is a resume of task %s. This report has been sent by Project manager [%s]

------------------------------------------------------------------------------------------------------
Start - End             : %s - %s
Priority                : %s
Estimated length (days) : %d
Estimated cost          : %s
Current progress        : %d %%
Participants            : %s
Description
------------------------------------------------------------------------------------------------------
%s
------------------------------------------------------------------------------------------------------
", $task["name"], $project_manager, $task["start"], $task["end"], get_priority_name($task["priority"]), $task_days, $task_cost, $task["completion"], $participants, $task["description"]);

$section_title = __("Task report details");
$section_subtitle =  $project_name . " >> " .$task["name"];
$t_menu = print_task_tabs();
print_title_with_menu ($section_title, $section_subtitle, "task_emailreport", 'projects', $t_menu, 'email');

echo "<form method=post action=''>";
echo "<table width=100% class=search-table-button>";
echo "<tr><td>";
print_input_text ('title', $title, '', 80, 175, false, __('Subject'));
echo "<tr><td>";
print_textarea ('description', 15, 50, $description, '',	false, __('Message text'));
echo "<tr><td>";
echo '</table>';

$button = '';
echo '<div style="width:100%;">';
$table = new StdClass;
$table->width = '100%';
$table->class = "button-form";
$button .= print_submit_button (__('Send'), 'create_btn', false, 'class="sub create"', true);
$button .= print_input_hidden ('operation', 'generate_email',true);
$button .= print_input_hidden ('id_project', $id_project, true);
$button .= print_input_hidden ('id_task', $id_task, true);

$table->data[7][0] = $button;
$table->colspan[7][0] = 2;

print_table($table);
echo '</div>';
echo '</form>';

?>
<script type="text/javascript">

$(document).ready (function () {
	$("#textarea-description").TextAreaResizer ();
});
</script>
