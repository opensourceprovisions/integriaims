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
$operation = "view";
$gantt_editor = get_parameter("gantt_editor");

$hours = 0;
$estimated_cost = 0;


// ACL Check for this task
$project_permission = get_project_access ($config["id_user"], $id_project);
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);

if (!$task_permission['read']) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to view a task without access");
	no_permission();
}

// Get names
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

// ********************************************************************************************************
// Show forms
// ********************************************************************************************************
echo '<h2>'.__('Task statistics'). "</h2>";
echo "<h4>".$task_name;
if (!$gantt_editor) {
	echo "<div id='button-bar-title'>";
	echo "<ul>";
	echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$id_task&operation=view'>".
		print_image ("images/go-previous.png", true, array("title" => __("Statistics"))) .
		"</a>";
	echo "</li>";
	echo "</ul>";
	echo "</div>";
	echo'</h1>';
} else {
	echo "<div id='button-bar-title' style='margin-top: 5px; margin-bottom: 9px;'>";
	echo "<ul>";
	echo "<li>";
		echo "<a onclick='toggle_editor_gantt(".$id_project.", ".$id_task.", \"editor\")'>".
		print_image ("images/go-previous.png", true, array("title" => __("Statistics"))) .
		"</a>";
	echo "</li>";
	echo "</ul>";
	echo "</div>";		
}
echo "</h4>";
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

$table->width = '100%';
$table->class = 'search-table';
$table->rowspan = array ();
$table->colspan = array ();
$table->style = array ();
$table->style[0] = 'vertical-align: top; width: 30%';
$table->style[1] = 'vertical-align: top; width: 30%';
$table->style[2] = 'vertical-align: top; width: 30%';
$table->data = array ();
$table->cellspacing = 2;
$table->cellpadding = 2;


// Task activity graph
$task_activity = task_activity_graph ($id_task, 600, 150, true, true);
$table_advanced->width = '100%';
$table_advanced->class = 'search-table';
$table_advanced->size = array ();
$table_advanced->style = array();
$table_advanced->data = array ();
$table_advanced->style[0] = "text-align: center";	
if ($task_activity) {
	
	$task_activity = '<div class="graph_frame">' . $task_activity . '</div>';
	$table_advanced->data[0][0] = $task_activity;
} else {
	$task_activity = '<div class="graph_frame">' .__("There isn't activity for this task"). '</div>';
	$table_advanced->data[0][0] = $task_activity;
}

$table->colspan['row_task_activity'][0] = 3;
$table->data['row_task_activity'][0] = print_container_div('task_activity_chart', __('Task activity'), print_table($table_advanced, true), 'open', true, true);	
//~ echo "<h4>" . __('Task activity') . "</h4>";
//~ print_table ($table_advanced);

$table_advanced->width = '100%';
$table_advanced->class = 'search-table';
$table_advanced->size = array ();
$table_advanced->style = array();
$table_advanced->data = array ();	

$worked_time =  get_task_workunit_hours ($id_task);
$table_advanced->data[0][0] = print_label (__('Worked hours'), '', '', true, $worked_time.' '.__('Hrs'));

$subtasks = task_duration_recursive ($id_task);
if ($subtasks > 0)
	$table_advanced->data[0][0] .= "<span title='Subtasks WU/Hr'> ($subtasks)</span>";

$incident_wu = get_incident_task_workunit_hours ($id_task);
if ($incident_wu > 0)
	$table_advanced->data[0][0] .= "<span title='Ticket'>($incident_wu)</span>";

$table_advanced->data[0][0] .= print_label (__('Imputable costs'), '', '', true,
	task_workunit_cost ($id_task, 1).' '.$config['currency']);

$incident_cost = get_incident_task_workunit_cost ($id_task);
if ($incident_cost > 0)
	$incident_cost_label = "<span title='".__("Ticket costs")."'> ($incident_cost) </span>";
else
	$incident_cost_label = "";
	
$total_cost = $external_cost + task_workunit_cost ($id_task, 0) + $incident_cost;

$table_advanced->data[0][0] .= print_label (__('Total costs'), '', '', true,
	$total_cost . $incident_cost_label. $config['currency']);

$avg_hr_cost = format_numeric ($total_cost / $worked_time, 2);
$table_advanced->data[0][0] .= print_label (__('Average Cost per hour'), '', '', true,
	$avg_hr_cost .' '.$config['currency']);	

$external_cost = 0;
$external_cost = task_cost_invoices ($id_task);

if (!$external_cost) {
	$external_cost = 0;
}

$table_advanced->data[0][0] .= print_label (__("External costs"), '', '', true);
$table_advanced->data[0][0] .= $external_cost . " " . $config["currency"];	

// Abbreviation for "Estimated"
$labela = __('Est.');
$labelb = __('Real');
$a = round ($hours);
$b = round (get_task_workunit_hours ($id_task));

$image = histogram_2values($a, $b, $labela, $labelb);
$table_advanced->data[0][1] = print_label (__('Estimated hours'), '', '', true, $image);

$labela = __('Total');
$labelb = __('Imp');
$a = round (task_workunit_cost ($id_task, 0));
$b = round (task_workunit_cost ($id_task, 1));
$image = histogram_2values($a, $b, $labela, $labelb);
$table_advanced->data[0][1] .= print_label (__('Imputable estimation'), '', '', true, $image);	

$labela = __('Est.');
$labelb = __('Real');
$a = $estimated_cost;
$b = round (task_workunit_cost ($id_task, 1));
$image = histogram_2values($a, $b, $labela, $labelb);
$table_advanced->data[0][1] .= print_label (__('Cost estimation'), '', '', true, $image);	

//Workload distribution chart
$image = graph_workunit_task (200, 170, $id_task, true);
$image = '<div class="graph_frame">' . $image . '</div>';
$table_advanced->data[0][2] = print_label (__('Workunit distribution'), '', '', true, $image);

$table->colspan['row_task_stats'][0] = 3;
$table->data['row_task_stats'][0] = print_container_div('task_stats', __('Task statitics'), print_table($table_advanced, true), 'open', true, true);	

print_table ($table);

?>