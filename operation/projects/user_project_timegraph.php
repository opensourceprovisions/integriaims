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

$id_user = $config["id_user"];

$id_user_filter = get_parameter('user', $config["id_user"]);
$start_date = get_parameter('start_date', strftime("%F",strtotime("-1 month")));
$end_date = get_parameter('end_date', strftime("%F",strtotime("now")));

// ACL
$id_grupo = get_parameter ("id_grupo", 0);
$id_user=$config['id_user'];

if ((give_acl($id_user, $id_grupo, "PR") != 1) AND (give_acl($id_user, $id_grupo, "IR") != 1)) {
 	// Doesn't have access to this page
	audit_db($id_user,$config["REMOTE_ADDR"], "ACL Violation","Trying to access to user report without projects access or Incident access permissions");
	include ("general/noaccess.php");
	exit;
}
echo '<h2>'.__('Projects').'</h2>';
echo '<h4>'.__('Time per project graph');
echo integria_help ("user_project_time", true);
echo '</h4>';
echo '<div class="divform">';
	echo "<form id='form-user_project_time_graph' method='post'>";
		echo '<table class="search-table">';
			echo '<tr>';
				echo '<td>';
					$params['input_value'] = $id_user_filter;
					$params['input_id'] = 'text-user';
					$params['input_name'] = 'user';
					$params['return'] = false;
					$params['return_help'] = true;
					$params['title'] = '<b>' . __('User ') . '</b>';
					user_print_autocomplete_input($params);
				echo '</td>';
			echo '</tr><tr>';
				echo '<td><b>'.__('Start').' </b>';
					print_help_tip(__('If date is unspecified, entire time range will be used.'));
				echo '</td>';
			echo '</tr><tr>';
				echo '<td>';
					print_input_text ('start_date', $start_date, '', 23, 20);
				echo '</td>';
			echo '</tr><tr>';
				echo '<td><b>'.__('End').' </b>';
					print_help_tip(__('If date is unspecified, entire time range will be used.'));
				echo '</td>';
			echo '</tr><tr>';
				echo '<td>';
					print_input_text ('end_date', $end_date, '', 23, 20);
				echo '</td>';
			echo '</tr><tr>';
				echo '<td>';
					print_input_hidden ('action', 'update');
					print_submit_button (__('Update'), 'upd_btn', false);
				echo '</td>';
			echo '</tr>';
		echo "</table>";
	echo "</form>";
echo '</div>';

echo "<div id='time_graph' class='divresult'>";
	if (empty($start_date)) {
		$start_date = false;
	}

	if (empty($end_date)) {
		$end_date = false;
	}

	print_project_user_timegraph($id_user_filter, $start_date, $end_date);
echo "</div>";
?>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript">
	add_ranged_datepicker ("#text-start_date", "#text-end_date", null);
	
	
	$(document).ready (function () {
		var idUser = "<?php echo $config['id_user'] ?>";
		
		bindAutocomplete ("#text-user", idUser);
	});
	// #text-user
	validate_user ("#form-user_project_time_graph", "#text-user", "<?php echo __('Invalid user')?>");
</script>
