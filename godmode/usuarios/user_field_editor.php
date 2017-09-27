<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
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

check_login ();

enterprise_include ('godmode/usuarios/configurar_usuarios.php');

if (! give_acl ($config["id_user"], 0, "UM")) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access user field edition");
	require ("general/noaccess.php");
	exit;
}

$id_field = get_parameter ('id_field');
if ($id_field) {
	$field_data = get_db_row_filter('tuser_field', array('id' => $id_field));

	$label = $field_data['label'];
	$type = $field_data['type'];
	$combo_value = $field_data['combo_value'];

}
$id_field = get_parameter ('id_field');
echo '<h2>'.__('User fields editor').'</h2>';

if ($id_field)
	echo '<h4>'.__('Update').'</h4>';
else
	echo '<h4>'.__('Create').'</h4>';

$table = new StdClass();
$table->width = "100%";
$table->class = "search-table";
$table->data = array ();

if(!isset($label)){
	$label = '';
}
$table->data[0][0] = print_input_text ('label', $label, '', 45, 100, true, __('Field name'));

$types = array('text' =>__('Text'), 'textarea' => __('Textarea'), 'combo' => __('Combo'));
$table->data[1][0] = print_label (__("Type"), "label-id", 'text', true);
if(!isset($type)){
	$type = '';
}
$table->data[1][0] .= print_select ($types, 'type', $type, '', __('Select type'), '0', true);

if(!isset($combo_value)){
	$combo_value = '';
}
$table->data['id_combo_value'][0] = print_input_text ('combo_value', $combo_value, '', 45, 100, true, __('Combo value').print_help_tip (__("Set values separated by comma"), true));

if (!$id_field) {
	$button = print_input_hidden('add_field', 1, true);
	$button .= print_submit_button (__('Create'), 'create_btn', false, 'class="sub next"', true);
} else {
	$button = print_input_hidden('update_field', 1, true);
	$button .= print_input_hidden('add_field', 0, true);
	$button .= print_input_hidden('id_field', $id_field, true);
	$button .= print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
}

$table->data['button'][0] = $button;
$table->colspan['button'][0] = 3;

echo "<div class='divform'>";
echo '<form method="post" action="index.php?sec=users&sec2=godmode/usuarios/user_field_list">';
print_table ($table);
echo '</form>';
echo '</div>';

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script  type="text/javascript">
$(document).ready (function () {
	if ($("#type").val() == "combo") {
		$("#table1-id_combo_value-0").css ('display', '');
	} else {
		$("#table1-id_combo_value-0").css ('display', 'none');
	}

});

$("#type").change (function () {
	if ($("#type").val() == "combo") {
		$("#table1-id_combo_value-0").css ('display', '');
	} else {
		$("#table1-id_combo_value-0").css ('display', 'none');
	}
});


// Form validation
trim_element_on_submit('#text-label');
trim_element_on_submit('#text-combo_value');

</script>
