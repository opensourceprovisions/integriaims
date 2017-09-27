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
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access user field list");
	require ("general/noaccess.php");
	exit;
}


$delete = get_parameter("delete");

if ($delete) {
	$id = get_parameter("id");

	$sql = sprintf("DELETE FROM tuser_field WHERE id = %d", $id);

	$res = process_sql($sql);

	if ($res) {
		echo ui_print_success_message (__('Field deleted'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem deleting field'), '', true, 'h3', true);
	}
}

$id_field = get_parameter ('id_field');
$add_field = (int) get_parameter('add_field', 0);
$update_field = (int) get_parameter('update_field', 0);

$label = '';
$type = '';
$combo_value = '';

if ($add_field) {
	$value = array();
	$value["label"] = get_parameter("label");
	$value["type"] = get_parameter("type");
	$value["combo_value"] = get_parameter("combo_value");

	if ($value['type'] == 'combo') {
		if ($value['combo_value'] == '')
			$error_combo = true;
	}
	
	if ($value['label'] == '') {
		echo ui_print_error_message (__('Empty field name'), '', true, 'h3', true);
	} else if ($error_combo) {
		echo ui_print_error_message (__('Empty combo value'), '', true, 'h3', true);
	} else {

		$result_field = process_sql_insert('tuser_field', $value);
		
		if ($result_field === false) {
			echo ui_print_error_message (__('Field could not be created'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Field created successfully'), '', true, 'h3', true);
			$id_field = $result_field;
		}
	}

}

if ($update_field) { //update field to incident type
	$id_field = get_parameter ('id_field');
	
	$value_update['label'] = get_parameter('label');
	$value_update['type'] = get_parameter ('type');
	$value_update['combo_value'] = get_parameter ('combo_value', '');
	$error_update = false;

	if ($value_update['type'] == "combo") {
		if ($value_update['combo_value'] == '') 
			$error_update = true;
	} 
	if ($error_update) {
		echo ui_print_error_message (__('Field could not be updated. Empty combo value'), '', true, 'h3', true);
	} else {
		$result_update = process_sql_update('tuser_field', $value_update, array('id' => $id_field));
		
		if ($result_update === false) {
			echo ui_print_error_message (__('Field could not be updated'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Field updated successfully'), '', true, 'h3', true);
		}
	}
}

echo "<h2>".__("User fields")."</h2>";
echo "<h4>".__("List fields")."</h4>";
		
$user_fields = get_db_all_rows_sql ("SELECT * FROM tuser_field");

if ($user_fields === false) {
	$user_fields = array ();
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'listing';
$table->data = array ();
$table->head = array();
$table->style = array();
$table->size = array ();
$table->size[0] = '30%';
$table->size[1] = '20%';
$table->size[2] = '30%';

$table->head[0] = __("Name field");
$table->head[1] = __("Type");
$table->head[2] = __("Value");
$table->head[4] = __("Action");

$data = array();

echo "<div class='divresult'>";

if (!empty($user_fields)) {
	foreach ($user_fields as $field) {
		$url_delete = "index.php?sec=users&sec2=godmode/usuarios/user_field_list&delete=1&id=".$field['id'];
		$url_update = "index.php?sec=users&sec2=godmode/usuarios/user_field_editor&id_field=".$field['id'];
		
		if ($field['label'] == '') {
			$data[0] = '';
		} else {
			$data[0] = $field["label"];
		}
		
		if ($field_type = '') {
			$data[1] = '';
		} else {
			$data[1] = $field["type"];
		}
		
		if ($field["type"] == "combo") {
			$data[2] = $field["combo_value"];
		} else {
			$data[2] = "";
		}
				
		$data[4] = "<a
		href='" . $url_update . "'>
		<img src='images/wrench.png' border=0 /></a>";
		$data[4] .= "<a
		onclick=\"if (!confirm('" . __('Are you sure?') . "')) return false;\" href='" . $url_delete . "'>
		<img src='images/cross.png' border=0 /></a>";
		
		array_push ($table->data, $data);
	}
	print_table($table);
} else {
	echo "<h2 class='error'>".__("No fields")."</h4>";
}
echo "</div>";

echo '<div class="divform">';
	echo "<form id='form-add_field' name=dataedit method=post action='index.php?sec=users&sec2=godmode/usuarios/user_field_editor'>";
		echo "<table class='search-table'>";
			echo "<tr>";
				echo "<td>";
					print_submit_button (__('Add field'), 'create_btn', false, 'class="sub create"', false);
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
echo '</div>';

?>