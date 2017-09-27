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
print_setup_tabs('setup', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["block_size"] = (int) get_parameter ("block_size", 20);
	$config["language_code"] = (string) get_parameter ("language_code", "en_GB");
	$config["sitename"] = (string) get_parameter ("sitename", "Integria IMS");
	$config["fontsize"] = (int) get_parameter ("fontsize", 10);
	$config["incident_reporter"] = (int) get_parameter ("incident_reporter", 0);
	$config["timezone"] = get_parameter ("timezone", "Europe/Madrid");
	$config["api_acl"] = get_parameter ("api_acl", "*");
	$config["api_password"] = get_parameter ("api_password", "");
	$config["site_logo"] = get_parameter ("site_logo", "integria_logo.png");
    	$config["header_logo"] = get_parameter ("header_logo", "integria_logo_header.png");
	$config["error_log"] = get_parameter ("error_log", 0);
	$config["flash_charts"] = get_parameter ("flash_charts", 1);
	$config["max_file_size"] = get_parameter ("max_file_size", 1);
	$config["first_day_week"] = get_parameter ("first_day_week", 0);
	$config["url_updatemanager"] = get_parameter ("url_updatemanager", "");
	$config["access_protocol"] = get_parameter("access_protocol");
	$config["access_port"] = get_parameter("access_port", "");
	$config["access_public"] = get_parameter ("access_public", $_SERVER["SERVER_NAME"]);
	$config["loginhash_pwd"] = get_parameter("loginhash_pwd", "");
	$config["csv_standard_encoding"] = (int) get_parameter("csv_standard_encoding");
	$config["enable_update_manager"] = get_parameter("enable_update_manager");
	$config["max_direct_download"] = get_parameter("max_direct_download");

    if ($is_enterprise) {
		$config["enable_pass_policy"] = get_parameter ("enable_pass_policy", 0);
		$config["pass_size"] = get_parameter ("pass_size", 4);
		$config["pass_needs_numbers"] = get_parameter ("pass_needs_numbers", 0);
		$config["pass_needs_symbols"] = get_parameter ("pass_needs_symbols", 0);
		$config["pass_expire"] = get_parameter ("pass_expire", 0);
		$config["first_login"] = get_parameter ("first_login", 1);
		$config["mins_fail_pass"] = get_parameter ("mins_fail_pass", 5);
		$config["number_attempts"] = get_parameter ("number_attempts", 5);
	}
 
    update_config_token ("timezone", $config["timezone"]);	

    //TODO: Change all "process_sqlxxx" for update_config_token in following code:

	update_config_token("language_code", $config["language_code"]);
    update_config_token ("sitename", $config["sitename"]);
    update_config_token ("max_file_size", $config["max_file_size"]);

	process_sql ("DELETE FROM tconfig WHERE token = 'incident_reporter'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('incident_reporter', '".$config["incident_reporter"]."')");
	update_config_token ("api_acl", $config["api_acl"]);
	update_config_token ("api_password", $config["api_password"]);
	update_config_token ("error_log", $config["error_log"]);
	update_config_token ("first_day_week", $config["first_day_week"]);
	
	update_config_token ("access_protocol", $config["access_protocol"]);
	update_config_token ("access_port", $config["access_port"]);
	update_config_token ("url_updatemanager", $config["url_updatemanager"]);
	update_config_token ("access_public", $config["access_public"]);

	update_config_token ("loginhash_pwd", $config["loginhash_pwd"]);

	update_config_token ("csv_standard_encoding", $config["csv_standard_encoding"]);
	update_config_token ("enable_update_manager", $config["enable_update_manager"]);
	update_config_token ("max_direct_download", $config["max_direct_download"]);
	
	if ($is_enterprise) {
		update_config_token ("enable_pass_policy", $config["enable_pass_policy"]);
		update_config_token ("pass_size", $config["pass_size"]);
		update_config_token ("pass_needs_numbers", $config["pass_needs_numbers"]);
		update_config_token ("pass_needs_symbols", $config["pass_needs_symbols"]);
		update_config_token ("pass_expire", $config["pass_expire"]);
		update_config_token ("first_login", $config["first_login"]);
		update_config_token ("mins_fail_pass", $config["mins_fail_pass"]);
		update_config_token ("number_attempts", $config["number_attempts"]);
	}
	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
}
// Render SYSTEM language code, not current language.
$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

$incident_reporter_options[0] = __('Disabled');
$incident_reporter_options[1] = __('Enabled');

$language_config = get_db_value('value', 'tconfig', 'token', 'language_code');

$table->data[0][0] = print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name',
	'language_code', $language_config, '', '', '', true, false, false,
	__('Language'));

$table->data[0][1] = print_input_text ("sitename", $config["sitename"], '',
	30, 50, true, __('Sitename'));

$error_log_options[0] = __('Disabled');
$error_log_options[1] = __('Enabled');
$table->data[1][0] = print_checkbox ("error_log", $error_log_options, 
		$config["error_log"], true, __('Enable error log') . 
			print_help_tip (__("This errorlog is on /integria.log"), true));

$table->data[1][1] = print_input_text ("timezone", $config["timezone"], '',
	15, 30, true, __('Timezone for integria'));

$table->data[2][0] = print_textarea ("api_acl", 2, 1, $config["api_acl"], 'style="max-width: 280px;"', true, __('List of IP with access to API') . 
	print_help_tip (__("List of IP (separated with commas which can access to the integria API. Use * for any address (INSECURE!)"), true), false);

$table->data[2][1] = print_input_password ("api_password", $config["api_password"], '',
	30, 255, true, __('API password'));


$days_of_week = get_days_of_week();
$table->data[4][0] = print_select ($days_of_week, "first_day_week", $config["first_day_week"], '','','',true,0,false, __('First day of the week'));

$table->data[4][1] = print_input_text ("url_updatemanager", $config["url_updatemanager"], '',
	35, 255, true, __('URL update manager'));

$table->data[5][0] = print_input_text ("loginhash_pwd", $config["loginhash_pwd"], '',
	30, 255, true, __('Loginhash password'));

$table->data[5][1] = print_checkbox ("access_protocol", 1, $config["access_protocol"], true, __('Enable HTTPS access'));

$table->data[6][0] = print_input_text ("access_port", $config["access_port"], '',
	10, 255, true, __('Access port') . 
	print_help_tip (__("Leave blank to use default port (80)"), true));

$table->data[6][1] = print_input_text ("access_public", $config["access_public"],
	'', 30, 50, true, __('Public access to server') . 
	print_help_tip (__("Public IP or name for the server, for example (23.45.67.3 or mydomain.com)"), true));

$csv_standard_encoding = !isset($config['csv_standard_encoding']) ? false : (bool) $config['csv_standard_encoding'];
$table->data[7][0] = print_label(__('CSV encoding type'), '', '', true);
$table->data[7][0] .=  __('Excel') . '&nbsp;' . print_radio_button ('csv_standard_encoding', 0, '', $csv_standard_encoding, true);
$table->data[7][0] .= print_help_tip (__("The Excel type may not be compatible with other applications"), true);
$table->data[7][0] .=  '&nbsp;&nbsp;' . __('Other') . '&nbsp;' . print_radio_button ('csv_standard_encoding', 1, '', $csv_standard_encoding, true);

$table->data[7][1] = print_checkbox ("enable_update_manager", 1, $config["enable_update_manager"], true, __('Enable update manager updates'));

$table->data[8][0] = print_input_text ("max_direct_download", $config["max_direct_download"], '',10, 255, true, __('Maximum direct download size (MB)'));

$table->data[8][1] = print_input_text ("max_file_size", $config["max_file_size"], '',
	10, 255, true, __('Max. Upload file size'));
	
echo "<form name='setup' method='post'>";
print_table ($table);

	echo "<div class='button-form'>";
		print_input_hidden ('update', 1);
		print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
	echo "</div>";
echo '</form>';
?>

<script type="text/javascript" src="include/js/integria.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>
