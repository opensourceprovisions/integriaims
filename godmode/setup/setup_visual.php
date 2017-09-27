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
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access visual setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('visual', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["block_size"] = (int) get_parameter ("block_size", 20);
	$config["fontsize"] = (int) get_parameter ("fontsize", 10);
	$config["font"] = get_parameter ("font", "smallfont.ttf");
	$config["pdffont"] = get_parameter ("pdffont", "code.ttf");
	$config["site_logo"] = get_parameter ("site_logo", "custom_logos/integria_logo.png");
    $config["header_logo"] = get_parameter ("header_logo", "custom_logos/integria_logo_header.png");
    $config["login_background"] = (string) get_parameter ("login_background");
	$config["flash_charts"] = get_parameter ("flash_charts");
	
    update_config_token ("block_size", $config["block_size"]);
    update_config_token ("fontsize", $config["fontsize"]);
    update_config_token ("font", $config["font"]);
    update_config_token ("pdffont", $config["pdffont"]);
    update_config_token ("site_logo", $config["site_logo"]);
    update_config_token ("header_logo", $config["header_logo"]);
    update_config_token ("login_background", $config["login_background"]);
    update_config_token ("flash_charts", $config["flash_charts"]);

	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

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

$table->data[0][0] = print_select ($imagelist, 'site_logo', 
		$config["site_logo"], '', __('Default'), '',  true, 0, true,
		__('Site logo') . print_help_tip(__('You can place your custom images into the folder') . ": images/custom_logos", true));

$table->data[1][0] = print_select ($imagelist, 'header_logo', 
		$config["header_logo"], '', __('Default'), '',  true, 0, true,
		__('Header logo') . 
		print_help_tip(__('You can place your custom images into the folder') . ": images/custom_logos", true));

$backgrounds_list_jpg = list_files("images/backgrounds", "jpg", 1, 0);
$backgrounds_list_gif = list_files("images/backgrounds", "gif", 1, 0);
$backgrounds_list_png = list_files("images/backgrounds", "png", 1, 0);
$backgrounds_list = array_merge($backgrounds_list_jpg, $backgrounds_list_png);
$backgrounds_list = array_merge($backgrounds_list, $backgrounds_list_gif);
asort($backgrounds_list);
$table->data[2][0] = print_select ($backgrounds_list, 'login_background',
		$config["login_background"], '', __('Default'), '',  true, 0,
		true,  __('Login background') . print_help_tip(__('You can place your custom images into the folder') . ": images/backgrounds", true));

$table->data[3][0] = print_input_text ("block_size", $config["block_size"], '',
	5, 5, true, __('Block size for pagination'));

function get_font_files () {
	global $config;
	$base_dir = $config['homedir'].'/include/fonts';
	$files = list_files ($base_dir, ".ttf", 1, 0);
	
	$retval = array ();
	foreach ($files as $file) {
		$retval[$config['homedir'].'include/fonts/'.$file] = $file;
	}
	
	return $retval;
}

$fontlist = get_font_files ();

$flash_options = array();
$flash_options[0]="Disabled";
$flash_options[1]="Enabled";
$table->data[0][1] = print_checkbox ('flash_charts', $flash_options, $config["flash_charts"], true, __('Enable flash charts'));

$table->data[1][1] = print_select ($fontlist, 'pdffont', $config["pdffont"], '', '', '',  true, 0, true, __('Font for PDF')) ;

$table->data[2][1] = print_select ($fontlist, 'font', $config["font"], '', '', '',  true, 0, true, __('Font for graphs')) ;

$table->data[3][1] = print_input_text ("fontsize", $config["fontsize"], '', 3, 5, true, __('Graphics font size'));

$button = print_input_hidden ('update', 1, true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

echo "<form name='setup' method='post'>";
print_table ($table);

	echo "<div class='button-form'>";
		echo $button;
	echo "</div>";
echo '</form>';
?>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>
