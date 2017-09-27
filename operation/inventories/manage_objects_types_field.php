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

check_login ();

if (! give_acl ($config["id_user"], 0, "PM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access Object Management");
	require ("general/noaccess.php");
	exit;
}

include_once("include/functions_objects.php");

//**********************************************************************
// Get actions
//**********************************************************************
$id_object_type = (int) get_parameter ('id');
$id_object_type_field = (int) get_parameter ('id_object_type_field');
$action = get_parameter ('action');

switch ($action) {
	default:
	case "create":
		$label = "";
		$type = "numeric";
		$combo_value = "";
		$external_table_name = "";
		$external_reference_field = "";
		$parent_table_name = "";
		$parent_reference_field = "";
		$unique = 0;
		$inherit = 0;
		$show_list = 0;
		$not_allow_updates = 0;
		$external_label = "";
		break;
	case "update":
		$object_type_field = get_db_row_filter('tobject_type_field', array('id' => $id_object_type_field));
		$label = $object_type_field["label"];
		$type = $object_type_field["type"];
		$combo_value = $object_type_field["combo_value"];
		$external_table_name = $object_type_field["external_table_name"];
		$external_reference_field = $object_type_field["external_reference_field"];
		$parent_table_name = $object_type_field["parent_table_name"];
		$parent_reference_field = $object_type_field["parent_reference_field"];
		$unique = $object_type_field["unique"];
		$inherit = $object_type_field["inherit"];
		$show_list = $object_type_field["show_list"];
		$not_allow_updates = $object_type_field["not_allow_updates"];
		$external_label = $object_type_field["external_label"];
		break;			
}

//**********************************************************************
// Tabs
//**********************************************************************

echo '<div id="tabs">';
echo '<h2>' . __('Inventory') . '</h2>';
echo '<h4>' . __('Object types management');
echo integria_help ("manage_objects", true);
/* Tabs list */
echo '<ul class="ui-tabs-nav">';
	
if (!empty($id_object_type)) {
	echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects&id=' . $id_object_type . '" title="'.__('Object details').'"><img src="images/eye.png" /></a></li>';
	echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list&id=' . $id_object_type . '" title="'.__('Fields').'"><img src="images/fields_tab.png" /></a></li>';
	echo '<li class="ui-tabs-selected"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_field&action=update&id_object_type_field=' . $id_object_type_field . '&id= ' . $id_object_type . '" title="'.__('Field management').'"><img src="images/manage_fields_tab.png" /></a></li>';
}
echo '</ul>';
echo "</h4>";
echo '</div>';

//**********************************************************************
// Field update form
//**********************************************************************
$table = new StdClass;
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

$table->data[0][0] = print_input_text ('label', $label, '', 45, 100, true, __('Label'));
$types = object_get_types();
$table->data[1][0] = print_select ($types, 'type', $type, '', '', "", true, false, false, __('Types'). print_help_tip(__('Field type to be filled later, if you choose "Combo" you have to select the values bellow. If you select "External" then you have to fill external table name and reference field.'), true));
$table->data[2][0] = print_textarea ('combo_value', 4, 10, $combo_value, '', true, __('Combo values').print_help_tip(__('If Type selected is "Combo" you have to fill this text with the select values separated by commas. E.g.: foo1,foo2'), true));
$table->data[3][0] = print_input_text ('external_table_name', $external_table_name, '', 45, 100, true, __('External table name'));
$table->data[4][0] = print_input_text ('external_label', $external_label, '', 45, 100, true, __('Field to be displayed'));
$table->data[5][0] = print_input_text ('external_reference_field', $external_reference_field, '', 45, 100, true, __('Primary key')).print_help_tip(__('Only needed if this is a parent table.'), true);
$table->data[6][0] = print_input_text ('parent_reference_field', $parent_reference_field, '', 45, 100, true, __('Foreign key')). print_help_tip(__('Only needed if this is a son table.'), true);
$table->data[7][0] = print_input_text ('parent_table_name', $parent_table_name, '', 45, 100, true, __('Parent table name')). print_help_tip(__('Only needed if this is a son table.'), true);
$table->data[8][0] = '<label>' . __('Unique') . print_help_tip(__('With this value checked the values in this field will be unique for all the inventory objects that use this field.'), true) . '</label>';
$table->data[9][0] = print_checkbox ('unique', 1, $unique, __('Unique'));
$table->data[10][0] = '<label>' . __('Inherit') . print_help_tip(__('With this value checked this field will inherit the values of owner, users and companies of the parent inventory object (at creation time).'), true) . '</label>';
$table->data[11][0] = print_checkbox ('inherit', 1, $inherit, __('Inherit'));
$table->data[12][0] = '<label>' . __('Show in list') . print_help_tip(__('With this value checked this field will be displayed in search list.'), true) . '</label>';
$table->data[13][0] = print_checkbox ('show_list', 1, $show_list, __('Show in list'));
$table->data[14][0] = '<label>' . __('Not allow updates') . print_help_tip(__('With this value checked this field will not be update when we receive remote inventory data'), true) . '</label>';
$table->data[15][0] = print_checkbox ('not_allow_updates', 1, $not_allow_updates, __('Not allow updates'));

echo "<form id='form-manage_objects_types_field' method='post' action='index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list'>";
print_table ($table);
	echo '<div style="width:100%;">';
		unset($table->data);
		$table->width = '100%';
		$table->class = "button-form";
		if (empty($id_object_type_field)) {
			$button = print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true);
			$button .= print_input_hidden ('id', $id_object_type, true);
			$button .= print_input_hidden ('action_db', 'insert', true);
			$button .= print_input_hidden ('action', 'create', true);
		} else {
			$button = print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true);
			$button .= print_input_hidden ('id', $id_object_type, true);
			$button .= print_input_hidden ('id_object_type_field', $id_object_type_field, true);
			$button .= print_input_hidden ('action_db', 'update', true);
			$button .= print_input_hidden ('action', 'update', true);
		}
		$table->data[16][0] = $button;
		print_table ($table);
	echo "</div>";	
echo "</form>";

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	var data_default = $("#type").val();
	
	if (data_default == "combo") {
		
		$("#table1-2").show();
	
	} else {
		
		$("#table1-2").hide();
		
	}

	if (data_default == "external") {
		
		$("#table1-3").show();
		$("#table1-4").show();
		$("#table1-5").show();
		$("#table1-6").show();
		
	} else {
		
		$("#table1-3").hide();
		$("#table1-4").hide();
		$("#table1-5").hide();
		$("#table1-6").hide();
		
	}		
	
	$("#type").change (function () {
		var data = this.value;

		if (data == "combo") {
			
			$("#table1-2").show("slow");
			
		} else {
			
			$("#table1-2").hide("slow");			
		}
		
		if (data == "external") {
			
			$("#table1-3").show("slow");
			$("#table1-4").show("slow");
			$("#table1-5").show("slow");
			$("#table1-6").show("slow");
			
		} else {
			
			$("#table1-3").hide("slow");
			$("#table1-4").hide("slow");
			$("#table1-5").hide("slow");
			$("#table1-6").hide("slow");
			
		}		
		
	})
});


// Form validation
trim_element_on_submit('#text-label');
validate_form("#form-manage_objects_types_field");
var rules, messages;
// Rules: #text-label
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_object_type_field: 1,
			object_type_field_name: function() { return $('#text-label').val() },
			object_type_id: "<?php echo $id_object_type?>",
			object_type_field_id: "<?php echo $id_object_type_field?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This label already exists')?>"
};
add_validate_form_element_rules('#text-label', rules, messages);

</script>
