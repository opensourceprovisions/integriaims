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
print_setup_tabs('license', $is_enterprise);

$update = (bool) get_parameter ("update");
$unblock_users = (bool) get_parameter("unblock_users", 0);

if ($update) {

	# Update of Integria license 
	$update_manager_installed = get_db_value('value', 'tconfig', 'token', 'update_manager_installed');

	if ($update_manager_installed == 1) {
		$license_info_key = get_parameter('license_info_key', '');
		if (empty($license_info_key)) {
			$license_info_key = 'INTEGRIA-FREE';
		}
		
		$sql_update = "UPDATE tconfig SET `value`='$license_info_key'
			WHERE `token`='license'";
		$update_manage_settings_result = process_sql($sql_update);
		$config["license"] = $license_info_key;

		$config["url_updatemanager"] = get_parameter ("url_updatemanager", $config["url_updatemanager"]);
        update_config_token ("url_updatemanager", $config["url_updatemanager"]);

	} else {
		$sql_insert = "INSERT INTO tconfig (`token`, `value`) VALUES ('update_manager_installed', '1');";
		process_sql  ($sql_insert);
		$sql_insert = "INSERT INTO tconfig (`token`, `value`) VALUES ('license', 'INTEGRIA-FREE');";
        process_sql  ($sql_insert);
		$sql_insert = "INSERT INTO tconfig (`token`, `value`) VALUES ('current_package', '0');";
        process_sql  ($sql_insert);
		$sql_insert = "INSERT INTO tconfig (`token`, `value`) VALUES ('url_updatemanager', 'https://artica.es/integriaupdate4/server.php');";
        process_sql  ($sql_insert);	
	}

	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
}

if ($unblock_users) {
	license_unblock_users();
}


// Render SYSTEM language code, not current language.
$table = new stdClass();
$table->width = '99%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();


$table->data[0][0] = __('License information');
$table->data[0][0] = print_input_text ('license_info_key', $config['license'], '', 70, 255, true, __('License key'));
$table->data[0][0] .= "&nbsp;<a id='dialog_license_info' title='".__("License Info")."' href='javascript: show_license_info(\"" . $config["expiry_day"] . "\", \"" . $config["expiry_month"] . "\",\"" . $config["expiry_year"] . "\",\"" . $config["max_manager_users"] . "\",\"" . $config["max_regular_users"] . "\")'>".print_image('images/lock.png', true, array('class' => 'bot', 'title' => __('License info'))).'</a>';
$table->data[0][0] .= '<div id="dialog_show_license" style="display:none"></div>';

echo "<form name='setup_license' method='post'>";

print_table ($table);

echo "<div class='button-form'>";
	print_input_hidden ('unblock_users', 1);
	print_submit_button (__('Enable users login'), 'upd_button', false, 'class="sub upd"');
	print_input_hidden ('update', 1);
	print_submit_button (__('Update license'), 'upd_button', false, 'class="sub upd"');
echo "</div>";
	
echo '</form>';
?>

<script type="text/javascript" src="include/js/integria.js"></script>

<script type="text/javascript">
$(document).ready (function () {

});
</script>
