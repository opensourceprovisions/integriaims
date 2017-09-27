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

include_once ("include/functions_projects.php");
include_once ("include/functions_graph.php");
include_once ("include/functions_user.php");

check_login ();

$id_project = (int) get_parameter ('id_project');
$id_user = $config["id_user"];

$start_date = get_parameter('start_date');
$end_date = get_parameter('end_date');

$id_user_filter = get_parameter('user', "");
$start_date = get_parameter('start_date', strftime("%F",strtotime("-1 year")));
$end_date = get_parameter('end_date', strftime("%F",strtotime("now")));

// ACL
$project_access = get_project_access ($id_user, $id_project);
if (! $project_access["read"]) {
	// Doesn't have access to this page
	audit_db ($id_user, $config["REMOTE_ADDR"], "ACL Violation",
		"Trying to access to project graph page");
	no_permission ();
}

// Print title and menu.
$section_title = __('Project management');
$section_subtitle =__('Time graph');
$p_menu = print_project_tabs();
print_title_with_menu ($section_title, $section_subtitle, "projects_timegraph", 'projects', $p_menu, 'time');

if ($id_project) {
echo "<div class='divform'>";	
	echo "<form id='form-time_graph' action='index.php?sec=projects&sec2=operation/projects/project_timegraph&id_project=" . $id_project . "' method='post'>";
		echo '<table class="search-table">';
			echo '<tr><td><b>'.__('User ').' </b>';
				$params = array();

				$params['input_value'] = $id_user_filter;
				$params['input_id'] = 'text-user';
				$params['input_name'] = 'user';
				$params['return'] = false;
				$params['return_help'] = false;

				user_print_autocomplete_input($params);
			echo '</td></tr>';
			echo '<tr><td><b>'.__('Start').' </b>';
				print_help_tip(__('Empty date is all range time of project'));
				print_input_text ('start_date', $start_date, '', 10, 20);
			echo '</td></tr>';
			echo '<tr><td><b>'.__('End').' </b>';
				print_help_tip(__('Empty date is all range time of project'));
				print_input_text ('end_date', $end_date, '', 10, 20);
			echo '</td></tr>';
			echo '<tr><td>';
				print_input_hidden ('id_project', $id_project);
				print_input_hidden ('action', 'update');
				print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"');
			echo '</td></tr>';
		echo "</table>";
	echo "</form>";
echo "</div>";
?>

	<script type="text/javascript">
		add_ranged_datepicker ("#text-start_date", "#text-end_date", null);
	</script>

<?php
	echo "<div id='time_graph' style='margin: 0px auto; width: 900px; float:right'></div>";
	if (empty($start_date)) {
		$start_date = false;
	}
	
	if (empty($end_date)) {
		$end_date = false;
	}
?>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript">
	add_ranged_datepicker ("#text-start_date", "#text-end_date", null);
	
	
	$(document).ready (function () {
		var idUser = "<?php echo $config['id_user'] ?>";
		
		bindAutocomplete ("#text-user", idUser);
	});
	// #text-user
	validate_user ("#form-time_graph", "#text-user", "<?php echo __('Invalid user')?>");
</script>

<?php
	echo "<div class = 'divresult'>";
	print_project_timegraph($id_project, $start_date, $end_date, $id_user_filter);
	echo "</div>";
}
