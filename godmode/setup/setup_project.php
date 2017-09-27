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
print_setup_tabs('project', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {

	$config["no_wu_completion"] = (string) get_parameter ("no_wu_completion", "");
	$config["hours_perday"] = (int) get_parameter ("hours_perday", "8");
	$config["autowu_completion"] = (int) get_parameter ("autowu_completion", 0);
	$config["pwu_defaultime"] = get_parameter ("pwu_defaultime", 4);
	$config["currency"] = (string) get_parameter ("currency", "€");
	
/*
	process_sql ("DELETE FROM tconfig WHERE token = 'no_wu_completion'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('no_wu_completion', '".$config["no_wu_completion"]."')");
*/

	update_config_token ("no_wu_completion", $config["no_wu_completion"]);
	update_config_token ("hours_perday", $config["hours_perday"]);
	update_config_token ("autowu_completion", $config["autowu_completion"]);
	update_config_token ("pwu_defaultime", $config["pwu_defaultime"]);
	update_config_token ("currency", $config["currency"]);

	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);

}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

$table->data[0][0] = print_input_text ("no_wu_completion", $config["no_wu_completion"], '',
	20, 500, true, __('No WU completion users') . integria_help ("no_wu_completion", true));

$table->data[0][1] = print_input_text ("hours_perday", $config["hours_perday"], '',
	5, 5, true, __('Work hours per day') . integria_help ("hours_perday", true));

$table->data[1][0] = print_input_text ("autowu_completion", $config["autowu_completion"],
	'', 7, 7, true, __('Auto WU Completion (days)') . integria_help ("autowu_completion", true));

$table->data[1][1] = print_input_text ("pwu_defaultime", $config["pwu_defaultime"], '',
	5, 5, true, __('Project WU Default time'));

$table->data[2][0] = print_input_text ("currency", $config["currency"], '',
	3, 3, true, __('Currency'));
	
$button = print_input_hidden ('update', 1, true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

echo "<form name='setup_project' method='post'>";
print_table ($table);
	echo "<div class='button-form'>";
		echo $button;
	echo "</div>";
echo '</form>';
?>
