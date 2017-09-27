<?php
// INTEGRIA IMS - the ITIL Management System
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

include_once ("include/functions_graph.php");
include_once ("include/functions_projects.php");
	
// Real start
global $config;

if (!isset($config["base_url"]))
	exit;

// Security checks for this project
	check_login ();

$id_user = $_SESSION['id_usuario'];
$id_project = get_parameter ("id_project", -1);
$scale = get_parameter("scale", "month");
$show_actual = get_parameter("show_actual", 0);

if ($id_project != -1)
	$project_name = get_db_value ("name", "tproject", "id", $id_project);
else
	$project_name = "";
$clean_output = get_parameter ("clean_output", 0);

$project_access = get_project_access ($config['id_user'], $id_project);
// ACL - To see the project, you should have read access
if ($id_project != -1 && !$project_access['read']) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to the gant graph of the project $project_name");
	no_permission();
}

if (!$clean_output) {
	
	// Print title and menu.
	$section_title = __('Gantt graph');
	$section_subtitle =  __("Project:") . " " .$project_name;
	$p_menu = print_project_tabs('gantt');
	print_title_with_menu ($section_title, $section_subtitle, 'gantt', 'projects', $p_menu, 'gantt');
} else {
	echo "<h2>".__('Gantt graph'). "</h2><h4>" . __("Project:") . " " .$project_name;
	echo"</h4>";
}

$scales = array ("month" => __("Month"), "week" => __("Week"), "day" => __("Day"));
$op_actual = array(0 => __("No"), 1 => __("Yes"));

echo '<div id="msg_box"></div>';

echo "<form id='gantt_form' method='post'>";
echo "<table class='gantt_buttons'>";
echo "<tr>";
echo "<td>";
echo __("Show real planning").": ";
echo "</td>";
echo "<td>";
echo print_select ($op_actual, "show_actual", $show_actual, '', '', 0, true, 0, false, '', false, "width: 50px");
echo "</td>";
echo "<td>";
echo __("Scale").": ";
echo "</td>";
echo "<td>";
echo print_select ($scales, "scale", $scale, '', '', 0, true, 0, false, '', false, "width: 70px");
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";

echo '	<div id="gantt_here" style="width:100%; height:490px; margin: 40px auto"></div>';

echo '<div id="milestone_explanation" class="gantt_tooltip" style="display:none"></div>';

echo '<div id="task_tooltip" class="gantt_tooltip" style="display:none"></div>';

echo "<div id='task_editor'></div>";

?>

<script src="include/graphs/gantt/dhtmlxgantt.js" type="text/javascript" charset="utf-8"></script>
<link rel="stylesheet" href="include/graphs/gantt/dhtmlxgantt.css" type="text/css" media="screen" title="no title" charset="utf-8">
<script src="include/graphs/gantt/gantt_chart.js" type="text/javascript" charset="utf-8"></script>
<script src="include/js/integria_projects.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript">

//Get data for this project
var id_project = <?php echo $id_project?>;
var show_actual = <?php echo $show_actual?>;
var scale = "<?php echo $scale?>";

//Get data
var conf = get_gantt_data(id_project, show_actual, scale);

var tasks = conf.tasks;
var milestones = conf.milestones;
var min_scale = conf.min_scale;
var max_scale = conf.max_scale;

//Configure gantt graph
configure_gantt(scale, min_scale, max_scale, task_tooltip_gantt, show_task_editor_gantt, 
				task_creation_gantt, validate_link_gantt);

//Init gantt and fill the graph
gantt.init("gantt_here");
gantt.parse(tasks);
gantt_open_branches(tasks);

bind_event_gantt(tasks);

$(document).ready(function () {
	load_milestone_tooltip_generator();

	$("#show_actual, #scale").change (function (){
		$("#gantt_form").submit();
	});
	
});

</script>