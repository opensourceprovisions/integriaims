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

global $config;
check_login();

//include("include/functions_user.php");
enterprise_include("include/functions_groups.php");

if (! give_acl ($config["id_user"], 0, "UM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access group management");
	require ("general/noaccess.php");
	exit;
}

// Inic vars

$id = (int) get_parameter ('id');
$name = "";
$icon = "";
$id_user_default = "";
$id_user = "";
$banner = "";
$parent = "";
$forced_email = true;
$soft_limit = 5;
$hard_limit = 20;
$enforce_soft_limit = 1;
$id_sla = 0;
$email_from = '';
$email_group = '';

$creacion_grupo = (bool) get_parameter ('creacion_grupo');
	
if ($id) {
	$group = get_db_row ('tgrupo', 'id_grupo', $id);
	if ($group) {
		$name = $group['nombre'];
		$icon = $group['icon'];
		$id_user_default = $group['id_user_default'];
		$banner = $group['banner'];
		$parent = $group['parent'];
		$soft_limit = $group["soft_limit"];
		$hard_limit = $group["hard_limit"];
		$enforce_soft_limit = $group["enforce_soft_limit"];
		$forced_email = (bool) $group['forced_email'];
		$id_sla = $group["id_sla"];
		$id_user = get_db_value ('id_user_default', 'tgrupo', 'id_grupo', $id);
		$id_inventory = $group["id_inventory_default"];
		$inventory_name = get_inventory_name ($group["id_inventory_default"]);
		$autocreate_user = $group["autocreate_user"];
		$grant_access = $group["grant_access"];
		$send_welcome = $group["send_welcome"];
		$default_company = $group["default_company"];
		$welcome_email = $group["welcome_email"];
		$email_queue = $group["email_queue"];
		$default_profile = $group["default_profile"];
		$user_level = $group["nivel"];
		$incident_type = $group["id_incident_type"];
		$email_from = $group["email_from"];
		$email_group = $group["email_group"];
		
		//Inventory == zero is an empty string
		if ($id_inventory == 0) {
			$id_inventory = "";
		}
	} else {
		echo ui_print_error_message (__('There was a problem loading group'), '', true, 'h3', true);
		include ("general/footer.php");
		exit;
	}
}

echo '<h2>'.__('Group management').'</h2>';
if ($id)
	echo '<h4>'.__('Update group').'</h4>';
else
	echo '<h4>'.__('New group').'</h4>';

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->rowspan = array ();
$table->rowspan[0][2] = 5;
$table->data = array ();
$table->colspan[7][0] = 2;
/* First row */
$table->data[0][0] = print_input_text ('name', $name, '', 20, 0, true, __('Name'));
if ($config['enteprise'] == 1){
	$table->data[0][1] = print_checkbox ('forced_email', 1, $forced_email, true, __('Forced email'));
} else {
	$table->data[0][1] = print_checkbox ('enforce_soft_limit', 1, $enforce_soft_limit, true, __('Enforce soft limit'));
}
/* Banner preview image is a bit bigger */
$table->data[0][2] = '<span id="banner_preview">';
if ($id && $banner != '') {
	$table->data[0][2] .= ' <img src="images/group_banners/'.$banner.'" />';
}
$table->data[0][2] .= '</span>';

$table->data[2][0] = print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre',
	'parent', $parent, '', 'None', '', true, false, false, __('Parent'));


//$table->data[2][1] = combo_user_visible_for_me ($id_user_default, "id_user_default", 0, "IR", true, __('Default user'));

$params_creator['input_id'] = 'text-id_user';
$params_creator['input_name'] = 'id_user';
$params_creator['input_value'] = $id_user;
$params_creator['title'] = __('Default user');
$params_creator['return'] = true;
$params_creator['return_help'] = true;
$table->data[2][1] = user_print_autocomplete_input($params_creator);

/*$table->data[2][1] = print_input_text_extended ('id_user', $id_user, 'text-id_user', '', 15, 30, false, '',
			array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true, '', __('Default user'))
		. print_help_tip (__("Type at least two characters to search"), true);*/


$icons = list_files ('images/groups_small/', 'png', 0, true, '');
$table->data[3][0] = print_select ($icons, 'icon', $icon, '', 'None', '', true, false, false, __('Icon'));
$table->data[3][0] .= '&nbsp;&nbsp;<span id="icon_preview">';
if ($id && $icon != '') {
	$table->data[3][0] .= '<img src="images/groups_small/'.$icon.'" />';
}
$table->data[3][0] .= '</span>';

$banners = list_files ('images/group_banners/', 'png', 0, true);
$table->data[3][1] = print_select ($banners, "banner", $banner, '', 'None', '', true, false, false, __('Banner'));

$table->data[4][0] = print_input_text ('soft_limit', $soft_limit, '', 10, 0, true , __('Tickets Soft limit')).print_help_tip (__("If it's a standard user it shows the maximum nº of tickets for this group that one user can have opened at the same time. If it's a external user it shows the maximum nº of tickets for this group and user that one user can have opened at the same time"), true);

if ($config['enteprise'] == 1){
	$table->data[4][1] = print_checkbox ('enforce_soft_limit', 1, $enforce_soft_limit, true, __('Enforce soft limit'));
}
$table->data[5][0] = print_input_text ('hard_limit', $hard_limit, '', 10, 0, true , __('Tickets Hard limit')).print_help_tip (__("If it's a standard user it shows the nº of maximum tickets for this group, that one user could have in total (open or closed). If it's a external user it shows the nº of maximum tickets for user, for this group, that one user could have in total (open or closed). When this limit is exceeded, the user will receive a notification in the screen when he try to create a ticket, so he won't be able to create any more."), true);

$slas_aux = get_db_all_rows_sql("SELECT id, name FROM tsla ORDER BY name");

$slas = array();

$slas[0] = __("None");

foreach ($slas_aux as $s) {
	$slas[$s["id"]] = $s["name"];
}

$table->data[5][1] = print_select ($slas,
	'id_sla', $id_sla, '', '', 0, true, false, false, __('Ticket SLA'));
	
if(!isset($inventory_name)){
	$inventory_name = '';
}
$table->data[6][0] = print_input_text ('inventory_name', $inventory_name,'', 25, 0, true, __('Default Inventory object'), false);	
$table->data[6][0] .= '&nbsp;&nbsp;' . "<a href='javascript: show_inventory_search(\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\");' title='".__('Search parent')."'><img src='images/add.png' /></a>";
if(!isset($id_inventory)){
	$id_inventory = '';
}
$table->data[6][0] .= print_input_hidden ('id_inventory', $id_inventory, true);

$table->data[6][1] = print_input_text ('email_from', $email_from, '', 40, 0, true , __('Email from'));
if ($config['enteprise'] == 1){
	$table->data[7][0] = print_textarea ("email_group", 5, 40, $email_group,'', true, __('Email group').print_help_tip (__("Set values separated by comma. You can use regular expresions"), true));
}
echo '<form id="form-configurar_grupo" method="post" action="index.php?sec=users&sec2=godmode/grupos/lista_grupos">';
print_table ($table);

if(!isset($autocreate_user)){ $autocreate_user = ''; }
if(!isset($grant_access)){ $grant_access = ''; }
if(!isset($send_welcome)){ $send_welcome = ''; }
if(!isset($default_company)){ $default_company = ''; }
if(!isset($welcome_email)){ $welcome_email = ''; }
if(!isset($email_queue)){ $email_queue = ''; }
if(!isset($default_profile)){ $default_profile = ''; }
if(!isset($user_level)){ $user_level = ''; }
if(!isset($incident_type)){ $incident_type = ''; }
enterprise_hook("groups_email_queue_form", array($autocreate_user, $grant_access, $send_welcome, $default_company, $welcome_email, $email_queue,$default_profile,$user_level, $incident_type));

echo '<div class="button-form" style="width: '.$table->width.'">';

if ($id) {
	print_submit_button (__('Update'), '', false, 'class="sub upd"');
	print_input_hidden ('update_group', 1);
	print_input_hidden ('id', $id);
} else {
	print_submit_button (__('Create'), '', false, 'class="sub next"');
	print_input_hidden ('create_group', 1);
} 
echo '</div></form>';

echo "<div class= 'dialog ui-dialog-content' id='inventory_search_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria_inventory.js"></script>
<script type="text/javascript">
$(document).ready (function () {
	$("#icon").change (function () {
		icon = this.value;
		$("#icon_preview").fadeOut ('normal', function () {
			$(this).empty ().append ($(" <img />").attr ("src", "images/groups_small/"+icon))
				.fadeIn ();
		});
	});
	$("#banner").change (function () {
		banner = this.value;
		$("#banner_preview").fadeOut ('normal', function () {
			$(this).empty ().append ($(" <img />").attr ("src", "images/group_banners/"+banner))
				.fadeIn ();
		});
	});
	
	var idUser = "<?php echo $config['id_user'] ?>";
	
	bindAutocomplete ("#text-id_user", idUser);	
	
});

function loadInventory(id_inventory) {
	$('#hidden-id_inventory').val(id_inventory);
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_inventory_name=1&id_inventory="+ id_inventory,
		dataType: "text",
		success: function (name) {
			$('#text-inventory_name').val(name);
		}
	});	
	$("#inventory_search_window").dialog('close');
}

// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-configurar_grupo");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_group: 1,
			group_name: function() { return $('#text-name').val() },
			group_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This group already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);
// id_user
validate_user ("#form-configurar_grupo", "#text-id_user", "<?php echo __('Invalid user')?>");
</script>
