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
print_setup_tabs('crm', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["invoice_logo"] = (string) get_parameter ("invoice_logo");
	$config["invoice_header"] = (string) get_parameter ("invoice_header");
	$config["invoice_footer"] = (string) get_parameter ("invoice_footer");
	$config["invoice_tax_name"] = (string) get_parameter ("invoice_tax_name");
	$config["lead_warning_time"] = get_parameter ("lead_warning_time", "7");
	$config["invoice_auto_id"] = get_parameter("invoice_auto_id");
	$config["invoice_id_pattern"] = (string)get_parameter("invoice_id_pattern", "15/[1000]");
	
	update_config_token ("invoice_logo", $config["invoice_logo"]);
	update_config_token ("invoice_header", $config["invoice_header"]);
	update_config_token ("invoice_footer", $config["invoice_footer"]);
	update_config_token ("invoice_tax_name", $config["invoice_tax_name"]);
	update_config_token ("lead_warning_time", $config["lead_warning_time"]);
	update_config_token ("invoice_auto_id", $config["invoice_auto_id"]);
	update_config_token ("invoice_id_pattern", $config["invoice_id_pattern"]);


	//Update lead progress names
	$progress["0"] = get_parameter("progress_0");
	$progress["20"] = get_parameter("progress_20");
	$progress["40"] = get_parameter("progress_40");
	$progress["60"] = get_parameter("progress_60");
	$progress["80"] = get_parameter("progress_80");
	$progress["100"] = get_parameter("progress_100");
	$progress["101"] = get_parameter("progress_101");
	$progress["102"] = get_parameter("progress_102");
	$progress["200"] = get_parameter("progress_200");

	foreach ($progress as $key => $value) {
		process_sql_update ('tlead_progress', array ('name' => $value), array ('id' => $key));
	}

	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

// Gets all .png, .jpg and .gif files from "images" directory
// and returns an array with their names
function get_logo_files () {
	$base_dir = 'images/custom_logos';
	$files = list_files ($base_dir, ".png", 1, 0);
	$files = array_merge($files, list_files ($base_dir, ".jpg", 1, 0));
	$files = array_merge($files, list_files ($base_dir, ".gif", 1, 0));
	
	$retval = array ();
	foreach ($files as $file) {
		$retval["custom_logos/$file"] = $file;
	}
	
	return $retval;
}

$imagelist = get_logo_files ();

$table->colspan[0][0] = 2;
$table->data[0][0] = "<h4>".__('Invoice generation parameters')."</h4>";

$table->data[1][0] = print_select ($imagelist, 'invoice_logo', 
		$config["invoice_logo"], '', __('None'), 'none',  true, 0, 
		true, __('Invoice header logo') . print_help_tip (__('You can submit your own logo in "images/custom_logo" folder using the file uploader'), true));

$table->data[1][1] = print_input_text ('invoice_tax_name', 
	$config["invoice_tax_name"], '', 10, 10, true, 
	__('Invoice tax name') . print_help_tip (__('For example: VAT'), true));

$table->colspan[3][0] = 2;
$table->data[3][0] = print_textarea ('invoice_header', 5, 40, $config["invoice_header"], '', true, __('Invoice header'));

$table->colspan[4][0] = 2;
$table->data[4][0] = print_textarea ('invoice_footer', 5, 40, $config["invoice_footer"], '', true, __('Invoice footer'));

$table->data[5][0] = print_checkbox ("invoice_auto_id", 1, $config["invoice_auto_id"], true, __('Enable auto ID'));
$table->data[5][1] = print_input_text ('invoice_id_pattern', $config["invoice_id_pattern"], '', 10, 20, true, __('Invoice ID pattern') . print_help_tip (__('Data not in square brackets will be fixed, while data in square brackets will be the number from which a sequence will be calculated. Example: FAC[100]'), true));

$table->colspan[7][0] = 2;
$table->data[7][0] = "<h4>".__('Lead parameters')."</h4>";

$table->data[8][0] = "<h5>".__('Lead progress defintion')."</h5>";

$table->data[8][1] = print_input_text ("lead_warning_time", $config["lead_warning_time"], '',
	5, 255, true, __('Days to warn on inactive leads'));

$progress_values = lead_progress_array ();

$closed_lead_tip = print_help_tip (__('This status means that lead is closed'), true);

$table->data[10][0] = print_input_text ('progress_0', $progress_values["0"], '', 50, 100, true);
$table->data[11][0] = print_input_text ('progress_20', $progress_values["20"], '', 50, 100, true);
$table->data[12][0] = print_input_text ('progress_40', $progress_values["40"], '', 50, 100, true);
$table->data[13][0] = print_input_text ('progress_60', $progress_values["60"], '', 50, 100, true);
$table->data[14][0] = print_input_text ('progress_80', $progress_values["80"], '', 50, 100, true);
$table->data[15][0] = print_input_text ('progress_100', $progress_values["100"], '', 50, 100, true).$closed_lead_tip;
$table->data[16][0] = print_input_text ('progress_101', $progress_values["101"], '', 50, 100, true).$closed_lead_tip;
$table->data[17][0] = print_input_text ('progress_102', $progress_values["102"], '', 50, 100, true).$closed_lead_tip;
$table->data[18][0] = print_input_text ('progress_200', $progress_values["200"], '', 50, 100, true).$closed_lead_tip;


$button = print_input_hidden ('update', 1, true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

echo '<form name="setup" method="post">';
print_table ($table);

	echo "<div class='button-form'>";
		echo $button;
	echo "</div>";
echo '</form>';

?>

<script type="text/javascript">
$(document).ready (function () {
	$("#textarea-invoice_header").TextAreaResizer ();
	$("#textarea-invoice_footer").TextAreaResizer ();
});
</script>
