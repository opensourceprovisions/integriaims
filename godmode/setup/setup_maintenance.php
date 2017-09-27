<?php 

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
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
include_once('include/functions_setup.php');

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('maintenance', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["max_days_events"] = (int) get_parameter ("max_days_events", 30);
	$config["max_days_incidents"] = (int) get_parameter ("max_days_incidents", 0);
	$config["max_days_wu"] = (int) get_parameter ("max_days_wu", 0);
	$config["max_days_wo"] = (int) get_parameter ("max_days_wo", 0);
	$config["max_days_audit"] = (int) get_parameter ("max_days_audit", 15);
	$config["max_days_session"] = (int) get_parameter ("max_days_session", 7);
	$config["max_days_workflow_events"] = (int) get_parameter ("max_days_workflow_events", 900);
	$config["max_days_fs_files"] = (int) get_parameter ("max_days_fs_files", 7);
	$config["max_days_files_track"] = (int) get_parameter ("max_days_files_track", 15);
	
	update_config_token ("max_days_events", $config["max_days_events"]);
	update_config_token ("max_days_incidents", $config["max_days_incidents"]);
	update_config_token ("max_days_wu", $config["max_days_wu"]);
	update_config_token ("max_days_wo", $config["max_days_wo"]);
	update_config_token ("max_days_audit", $config["max_days_audit"]);
	update_config_token ("max_days_session", $config["max_days_session"]);
	update_config_token ("max_days_workflow_events", $config["max_days_workflow_events"]);
	update_config_token ("max_days_fs_files", $config["max_days_fs_files"]);
	update_config_token ("max_days_files_track", $config["max_days_files_track"]);
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

$row = array();
$cell = 0;

$row[$cell] = print_input_text ("max_days_events", $config["max_days_events"], '', 4, 4, true, __('Days to delete events') . integria_help ("old_events", true));
$cell++;

$row[$cell] = print_input_text ("max_days_incidents", $config["max_days_incidents"], '', 4, 4, true, __('Days to delete tickets') . integria_help ("old_incidents", true));
$cell++;

$row[$cell] = print_input_text ("max_days_wu", $config["max_days_wu"], '', 4, 4, true, __('Days to delete work units') . integria_help ("old_wu", true));
$cell++;

$table->data[] = $row;

$row = array();
$cell = 0;

$row[$cell] = print_input_text ("max_days_wo", $config["max_days_wo"], '', 4, 4, true, __('Days to delete work orders') . integria_help ("old_wo", true));
$cell++;

$row[$cell] = print_input_text ("max_days_audit", $config["max_days_audit"], '', 4, 4, true, __('Days to delete audit data') . integria_help ("old_audit", true));
$cell++;

$row[$cell] = print_input_text ("max_days_session", $config["max_days_session"], '', 4, 4, true, __('Days to delete sessions') . integria_help ("old_sessions", true));
$cell++;

$table->data[] = $row;

$row = array();
$cell = 0;

$row[$cell] = print_input_text ("max_days_workflow_events", $config["max_days_workflow_events"], '', 4, 4, true, __('Days to delete workflow events') . integria_help ("old_workflow_events", true));
$cell++;

$row[$cell] = print_input_text ("max_days_fs_files", $config["max_days_fs_files"], '', 4, 4, true, __('Days to delete old file sharing files') . integria_help ("old_fs_files", true));
$cell++;

$row[$cell] = print_input_text ("max_days_files_track", $config["max_days_files_track"], '', 4, 4, true, __('Days to delete old file tracking data') . integria_help ("old_files_track", true));
$cell++;

$table->data[] = $row;

$button = print_input_hidden ('update', 1, true);
$button .= print_submit_button (__('Reset to default'), 'reset_button', false, 'class="sub upd"', true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

echo '<form id="setup_maintenance" name="setup" method="post">';
print_table ($table);
	echo "<div class='button-form'>";
		echo $button;
	echo "</div>";
echo '</form>';

?>

<script type="text/javascript">
$(document).ready (function () {
	$("#submit-reset_button").click(function() {
		$("#text-max_days_events").val("");
		$("#text-max_days_incidents").val("");
		$("#text-max_days_wu").val("");
		$("#text-max_days_wo").val("");
		$("#text-max_days_audit").val("");
		$("#text-max_days_session").val("");
		$("#text-max_days_workflow_events").val("");
	});
});
</script>
