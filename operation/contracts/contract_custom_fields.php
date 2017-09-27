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

//~ enterprise_include ('godmode/usuarios/configurar_usuarios.php');

if (! give_acl ($config["id_user"], 0, "CM")) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access user field list");
	require ("general/noaccess.php");
	exit;
}


$delete = get_parameter("delete");

if ($delete) {
	$id = get_parameter("id");
	$name_delete = get_parameter("name_delete");

	$sql = sprintf("DELETE FROM tcontract_field WHERE id = %d", $id);
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
$parent = '';
$linked_value = '';
$show_in_list = 0;

if ($add_field) {
	$value = array();
	$value["label"]        = get_parameter("label");
	$value["type"]         = get_parameter("type");
	$value["combo_value"]  = get_parameter("combo_value");
	$value["parent"]       = get_parameter("parent");
	$value["linked_value"] = get_parameter("linked_value");
	$value["show_in_list"] = get_parameter("show_in_list");
	$error_combo = false;
	$error_linked = false;

	if ($value['type'] == 'combo') {
		if ($value['combo_value'] == '')
			$error_combo = true;
	}

	if ($value['type'] == 'linked') {
		if ($value['linked_value'] == '')
			$error_linked = true;
	}
	
	if ($value['label'] == '') {
		echo ui_print_error_message (__('Empty field name'), '', true, 'h3', true);
	} else if ($value['type'] == '0') {
		echo ui_print_error_message (__('Empty type field'), '', true, 'h3', true);
	} else if ($error_combo) {
		echo ui_print_error_message (__('Empty combo value'), '', true, 'h3', true);
	} else if ($error_linked) {
		echo ui_print_error_message (__('Empty linked value'), '', true, 'h3', true);
	} else {

		$result_field = process_sql_insert('tcontract_field', $value);
		
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
	$value_update["parent"] = get_parameter("parent");
	$value_update["linked_value"] = get_parameter("linked_value");
	$value_update["show_in_list"] = get_parameter("show_in_list", 0);
	
	$error_combo = false;
	$error_linked = false;

	if ($value_update['type'] == "combo") {
		if ($value_update['combo_value'] == '') 
			$error_combo = true;
	} 

	if ($value_update['type'] == 'linked') {
		if ($value_update['linked_value'] == '')
			$error_linked = true;
	}

	if ($value_update['label'] == '') {
		echo ui_print_error_message (__('Empty field name'), '', true, 'h3', true);
	} else if ($value_update['type'] == '0') {
		echo ui_print_error_message (__('Empty field type'), '', true, 'h3', true);
	} else if ($error_combo) {
		echo ui_print_error_message (__('Empty combo value'), '', true, 'h3', true);
	} else if ($error_linked) {
		echo ui_print_error_message (__('Empty linked value'), '', true, 'h3', true);
	} else {
		$result_update = process_sql_update('tcontract_field', $value_update, array('id' => $id_field));
		
		if ($result_update === false) {
			echo ui_print_error_message (__('Field could not be updated'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Field updated successfully'), '', true, 'h3', true);
		}
	}
}

echo "<h2>".__("Contract")."</h2>";
echo "<h4>".__("Custom fields");
	if ($id_field) {
		echo "<div id='button-bar-title'><ul>";
			echo "<li><a href='index.php?sec=customers&sec2=operation/contracts/contract_custom_fields'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		echo "</ul></div>";
	}
echo "</h4>";
		
$contract_fields = get_db_all_rows_sql ("SELECT * FROM tcontract_field");

if ($contract_fields === false) {
	$contract_fields = array ();
}

$id_field = get_parameter ('id_field');
if ($id_field) {
	$field_data   = get_db_row_filter('tcontract_field', array('id' => $id_field));
	$label        = $field_data['label'];
	$type 	      = $field_data['type'];
	$combo_value  = $field_data['combo_value'];
	$parent       = $field_data['parent'];
	$linked_value = $field_data['linked_value'];
	$show_in_list = $field_data['show_in_list'];
}

$table = new StdClass();
$table->width = "100%";
$table->class = "search-table";
$table->data = array ();

$table->data[0][0] = print_input_text ('label', $label, '', 45, 100, true, __('Field name'));
$types = array('text' =>__('Text'), 'textarea' => __('Textarea'), 'combo' => __('Combo'), 'linked' => __('Linked'), 'numeric' => __('Numeric'), 'date' => __('Date'));
$table->data[1][0] = print_checkbox ('show_in_list', 1, $show_in_list, true, __('Show in list'));

$table->data[2][0] = print_label (__("Type"), "label-id", 'text', true);
$table->data[2][0] .= print_select ($types, 'type', $type, '', __('Select type'), '0', true);

//combo values
$table->data['id_combo_value'][0] = print_input_text ('combo_value', $combo_value, '', 45, 255, true, __('Combo value').print_help_tip (__("Set values separated by comma"), true));

// Linked values
$sql = "SELECT id, label FROM tcontract_field WHERE type = 'linked'";
$parents_result = get_db_all_rows_sql($sql);

if ($parents_result == false) {
	$parents_result = array();
}

$parents = array();
foreach ($parents_result as $result) {
	$parents[$result['id']] = $result['label']; 
}

$table->data['id_parent_value'][0] = print_select ($parents, 'parent', $parent, '', __('Select parent'), '0', true, 0, true, __("Parent"));
$table->data['id_linked_value'][0] = print_textarea ('linked_value', 15, 1, $linked_value, '', true, __('Linked value').integria_help ("linked_values", true));

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
	echo '<form method="post" action="index.php?sec=customers&sec2=operation/contracts/contract_custom_fields">';
		print_table ($table);
	echo '</form>';
echo '</div>';

$table = new StdClass();
$table->width = '100%';
$table->class = 'listing';
$table->data = array ();
$table->head = array();
$table->style = array();

$table->head[0] = __("Name field");
$table->head[1] = __("Type");
$table->head[2] = __("Parent");
$table->head[3] = __("Value");
$table->head[4] = __("Show in list");
$table->head[5] = __("Action");

$data = array();

echo "<div class='divresult'>";

if (!empty($contract_fields)) {
	foreach ($contract_fields as $field) {
		$url_delete = "index.php?sec=customers&sec2=operation/contracts/contract_custom_fields&delete=1&id=".$field['id']."&name_delete=".$field["label"];
		$url_update = "index.php?sec=customers&sec2=operation/contracts/contract_custom_fields&id_field=".$field['id'];
		
		if ($field['label'] == '') {
			$data[0] = '';
		}
		else {
			$data[0] = "<a href='" . $url_update . "'>" . $field["label"]."</a>";
		}
		
		if ($field_type = '') {
			$data[1] = '';
		}
		else {
			$data[1] = $field["type"];
		}
		
		//Only linked
		if ($field["type"] == "linked") {
			$sql = "SELECT label FROM tcontract_field WHERE type = 'linked' AND id =".$field["parent"];
			$parent = get_db_sql($sql);
			$data[2] = $parent;
		}
		else {
			$data[2] = "";
		}

		//Only combo or linked
		if ($field["type"] == "combo") {
			$data[3] = ui_print_truncate_text($field["combo_value"], 60);
		} 
		else if ($field["type"] == "linked") {
			$data[3] = ui_print_truncate_text($field["linked_value"], 60);
 		}
 		else{
			$data[3] = "";
		}

		//Show in list yes or no
		if ($field["show_in_list"] == 0){
			$data[4] = __('No');
		}
		else{
			$data[4] = __('Yes');
		}

		$data[5] = "<a href='" . $url_update . "'> <img src='images/wrench.png' border=0 /></a>";
		$data[5] .= "<a onclick=\"if (!confirm('" . __('Are you sure delete custom field '. $field["label"] .' ?') . "')) return false;\" href='" . $url_delete . "'> <img src='images/cross.png' border=0 /></a>";
		array_push ($table->data, $data);
	}
	print_table($table);
} else {
	echo "<h2 class='error'>".__("No fields")."</h4>";
}
echo "</div>";

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script  type="text/javascript">
$("#type").change (function () {
	var type_val = $("#type").val();
	switch (type_val) {
		case "combo":
			$("#table1-id_combo_value-0").show();
			$("#table1-id_linked_value-0").hide();
			$("#table1-id_parent_value-0").hide();
		break;
		case "linked":
			$("#table1-id_linked_value-0").show();
			$("#table1-id_parent_value-0").show();
			$("#table1-id_combo_value-0").hide();
		break;
		default:
			$("#table1-id_combo_value-0").hide();
			$("#table1-id_linked_value-0").hide();
			$("#table1-id_parent_value-0").hide();
		break;
	}
}).change();

// Form validation
trim_element_on_submit('#text-label');
trim_element_on_submit('#text-combo_value');
</script>
