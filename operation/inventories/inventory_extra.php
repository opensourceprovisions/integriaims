<?php 
// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2007-2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (check_login () != 0) {
 	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

$id_group = get_inventory_group ($id);

if (! give_acl ($config['id_user'], $id_group, 'VR')) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
	include ("general/noaccess.php");
	exit;
}

echo '<h3>'.__('Extra details for the inventory object').' #'.$id.'</h3>';

require_once ('include/functions_inventories.php');


$has_permission = give_acl ($config['id_user'], $id_group, "VW");


$inventory = get_db_row ('tinventory', 'id', $id);
$generic_1 = $inventory['generic_1'];
$generic_2 = $inventory['generic_2'];
$generic_3 = $inventory['generic_3'];
$generic_4 = $inventory['generic_4'];
$generic_5 = $inventory['generic_5'];
$generic_6 = $inventory['generic_6'];
$generic_7 = $inventory['generic_7'];
$generic_8 = $inventory['generic_8'];

$labels = get_inventory_generic_labels ();

$table->class = 'databox';
$table->width = '90%';
$table->data = array ();
$table->colspan = array ();
$table->colspan[4][0] = 3;
$table->colspan[5][0] = 3;

/* First row */
if ($has_permission) {
	$table->data[0][0] = print_input_text ('generic_1', $generic_1, '', 40, 128, true,
		$labels['generic_1']);
	$table->data[0][1] = print_input_text ('generic_2', $generic_2, '', 40, 128, true,
		$labels['generic_2']);
} else {
	$table->data[0][0] = print_label ($labels['generic_1'], '', '', true, $generic_1);
	$table->data[0][1] = print_label ($labels['generic_2'], '', '', true, $generic_2);
}

/* Second row */
$disabled_str = ! $has_permission ? 'readonly="1"' : '';
$table->data[1][0] = print_textarea ($labels['generic_3'], 15, 100, $generic_3,
	$disabled_str, true, $labels['generic_3']);
$table->data[1][1] = print_textarea ($labels['generic_4'], 15, 100, $generic_4,
	$disabled_str, true, $labels['generic_4']);

/* Third row */
/* First row */
if ($has_permission) {
	$table->data[2][0] = print_input_text ('generic_5', $generic_5, '', 40, 128, true,
		$labels['generic_5']);
	$table->data[2][1] = print_input_text ('generic_6', $generic_6, '', 40, 128, true,
		$labels['generic_6']);
} else {
	$table->data[2][0] = print_label ($labels['generic_5'], '', '', true, $generic_5);
	$table->data[2][1] = print_label ($labels['generic_6'], '', '', true, $generic_6);
}

if ($has_permission) {	
	echo '<form method="post" id="inventory_extras_form">';
	print_table ($table);

	echo '<div style="width:'.$table->width.'" class="action-buttons button">';
	print_input_hidden ('update_extras', 1);
	print_input_hidden ('id', $id);
	print_submit_button (__('Update'), 'update', false, 'class="sub upd"');
	echo '</div>';
	echo '</form>';
} else {
	print_table ($table);
}
?>
