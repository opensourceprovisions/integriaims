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
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access Object Type Management");
	require ("general/noaccess.php");
	exit;
}

include_once("include/functions_objects.php");

//**********************************************************************
// Get actions
//**********************************************************************

$id_object_type = (int) get_parameter ('id');
$delete_object_type_field = get_parameter ('delete_object_type_field', 0);
$action_db = get_parameter ('action_db', '');

$id_object_type_field = (int) get_parameter ('id_object_type_field');
$label = get_parameter('label');
$type = get_parameter('type');
$unique = (bool)get_parameter('unique', 0);
$inherit = get_parameter('inherit', 0);	
$combo_value = '';
$external_table_name = '';
$external_reference_field = '';
$parent_table_name = '';
$parent_reference_field = '';
$show_list = get_parameter('show_list', 0);
$not_allow_updates = get_parameter('not_allow_updates', 0);
$external_label = "";

//**********************************************************************
// Tabs
//**********************************************************************

echo '<div id="tabs">';
echo '<h2>' . strtoupper(__('Inventory')) . '</h2>';
echo '<h4>' . strtoupper(__('Field list'));
echo integria_help ("manage_objects", true);
/* Tabs list */

echo '<ul class="ui-tabs-nav">';
if (!empty($id_object_type)) {
	echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects&id=' . $id_object_type . '" title="'.__('Object details').'"><img src="images/eye.png"/></a></li>';
	echo '<li class="ui-tabs-selected"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list&id=' . $id_object_type . '" title="'.__('Fields').'"><img src="images/fields_tab.png"/></a></li>';
}
echo '</ul>';
echo '</h4>';
echo '</div>';

//**********************************************************************
// Actions
//**********************************************************************

switch ($type) {
	case "combo":
		$combo_value = get_parameter('combo_value');
		break;
	case "external":
		$external_table_name = get_parameter('external_table_name', '');
		$external_reference_field = get_parameter('external_reference_field', '');
		$parent_table_name = get_parameter('parent_table_name', '');
		$parent_reference_field = get_parameter('parent_reference_field', '');
		$external_label = get_parameter('external_label', '');
		break;
	default:
		break;
}

switch ($action_db) {
	case "insert":
		$sql = sprintf ('INSERT INTO tobject_type_field (id_object_type, label, type, combo_value, 
						external_table_name, external_reference_field, `unique`, inherit, show_list, parent_table_name, parent_reference_field, not_allow_updates, external_label) 
				VALUES (%d, "%s", "%s", "%s", 
						"%s", "%s", %d, %d, %d, "%s", "%s", %d, "%s")',
				$id_object_type, $label, $type, $combo_value,
				$external_table_name, $external_reference_field, $unique, $inherit, $show_list, $parent_table_name, $parent_reference_field, $not_allow_updates, $external_label);
				
		$id_object_type_field = process_sql ($sql, 'insert_id');
		
		if (! $id_object_type_field) {
			echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			//insert_event ("OBJECT TYPE CREATED", $id_object_type_field, $id_object_type, $label);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Created object type $id_object_type_field - $label");
		}
							
		break;
	case "update":
		$sql = sprintf ('UPDATE tobject_type_field SET label = "%s", type = "%s",
		combo_value = "%s", external_table_name = "%s", external_reference_field = "%s",
		`unique` = %d, inherit = %d, show_list = %d, parent_table_name = "%s", parent_reference_field = "%s", not_allow_updates = %d, external_label = "%s"
		WHERE id = %d', 
		$label, $type, 
		$combo_value, $external_table_name, $external_reference_field, 
		$unique, $inherit, $show_list, $parent_table_name, $parent_reference_field, 
		$not_allow_updates, $external_label, $id_object_type_field);

		$result = process_sql ($sql);
		
		if (! $result) {
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true); 
		}
		else {
			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			//insert_event ("OBJECT TYPE UPDATED", $id_object_type_field, $id_object_type, $label);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Updated object type $id_object_type_field - $label");
		}
	
		break;
	default:
		break;
}

// Delete Field
if ($delete_object_type_field) {
	$id_object_type_field = (int) get_parameter ('id_object_type_field');
	
	$sql = sprintf ('DELETE FROM tobject_type_field WHERE id = %d', $id_object_type_field);
	$result = process_sql ($sql);

	if ($result)
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true); 
	else
		echo ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
	$id = 0;
}

//**********************************************************************
// List fields
//**********************************************************************

$objects_type_fields = get_db_all_rows_field_filter ('tobject_type_field', 'id_object_type', $id_object_type, 'id');

$table = new StdClass;
$table->width = '99%';
echo '<div class="divresult">';
if ($objects_type_fields !== false) {
	//echo "<h3>".__('Defined objects types fields')."</h3>";
	
	$table->class = 'listing';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Label');
	$table->head[1] = __('Type');
	$table->head[2] = __('Unique');
	$table->head[3] = __('Inherit');
	$table->head[4] = __('Show in list');
	$table->head[5] = __('Actions');
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->align = array ();
	
	echo '<table width="90%" class="listing">';
	foreach ($objects_type_fields as $objects_type_field) {
		$data = array ();
		
		$data[0] = '<a href=index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_field&action=update&id_object_type_field='.$objects_type_field["id"].'&id='.
			$objects_type_field["id_object_type"].'>' . $objects_type_field['label'] . '</a>';
		$data[1] = substr ($objects_type_field["type"], 0, 200);
		$data[2] = ($objects_type_field['unique']? __('Yes'):__('No'));
		$data[3] = ($objects_type_field['inherit']? __('Yes'):__('No'));
		$data[4] = ($objects_type_field['show_list']? __('Yes'):__('No'));
		$data[5] = '<form style="display:inline;" method="post" onsubmit="if (!confirm(\''.__('Are you sure?').'\'))
			return false;">';
		$data[5] .= print_input_hidden ('delete_object_type_field', 1, true);
		$data[5] .= print_input_hidden ('id', $id_object_type, true);
		$data[5] .= print_input_hidden ('id_object_type_field', $objects_type_field["id"], true);
		$data[5] .= print_input_image ('delete', 'images/cross.png', 1, '', true, '',array('title' => __('Delete')));
		$data[5] .= '</form>';
		
		array_push ($table->data, $data);
	}
	print_table ($table);
} else {
	echo "<h4>".__('No objects types fields');
	echo integria_help ("manage_objects", true);
	echo "</h4>";
}
echo'</div>';

echo '<div class="divform">';
	echo '<form method="post" action="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_field">';
		echo '<table class="search-table">';
			echo '<tr>';
				echo '<td>';
					print_input_hidden ('action', 'create');
					print_input_hidden ('id', $id_object_type);
					print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	echo '</form>';
echo'</div>';

?>
