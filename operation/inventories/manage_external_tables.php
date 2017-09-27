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
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access external table management");
	require ("general/noaccess.php");
	exit;
}

$external_table = get_parameter('external_table');
$id_object_type = get_parameter('id');
$delete_row = get_parameter('delete_row', 0);
$update_row = get_parameter('update_row', 0);
$add_row = get_parameter('add_row', 0); //add new line to enter data
$insert_row = get_parameter('insert_row', 0);

if ($delete_row) {
	$key = get_parameter('key');
	$key_value = get_parameter('key_value');
	
	$result = process_sql_delete ($external_table, array($key=>$key_value));
	
	if ($result) {
		echo ui_print_success_message (__('Deleted row'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem deleting row'), '', true, 'h3', true);
	}
}

if ($update_row) {	
	$key = get_parameter('key');
	$key_value = get_parameter('key_value');
	
	$fields = get_db_all_rows_sql("DESC ".$external_table);
	
	if ($fields == false) {
		$fields = array();
	}
	
	foreach ($fields as $field) {
		if ($field['Field'] != $key) {
			$values[$field['Field']] = get_parameter($field['Field']);
		}
	}
	
	$result = process_sql_update ($external_table, $values, array($key=>$key_value));
	
	if ($result) {
		echo ui_print_success_message (__('Updated row'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem updating row'), '', true, 'h3', true);
	}
}

if ($insert_row) {
	$fields = get_db_all_rows_sql("DESC ".$external_table);
	$key = get_parameter('key');
	
	if ($fields == false) {
		$fields = array();
	}
	
	foreach ($fields as $field) {
		if ($field['Field'] != $key) {
			$values[$field['Field']] = get_parameter($field['Field']);
		}
	}
	
	$result_insert = process_sql_insert ($external_table, $values);
	
	if ($result_insert) {
		echo ui_print_success_message (__('Inserted row'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem inserting row'), '', true, 'h3', true);
	}
}

echo "<h1>".__('External table management')."</h1>";

$table = new stdClass;
$table->width = '98%';
$table->class = 'search-table';
$table->id = "external-editor";

$table->data = array ();


$ext_tables = inventories_get_external_tables ($id_object_type);

$table->data[0][0] = print_select ($ext_tables, 'external_table', $external_table, '', __('None'), "", true, false, false, __('Select external table'));

$button = '<div style=" text-align: right;">';
$button .= print_submit_button (__('Add row'), 'search', false, 'class="sub search"', true);
$button .= '</div>';

$table->data[1][1] = $button;

echo '<form id="add_row" method="post" action="index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&id='.$id_object_type.'&external_table='.$external_table.'&add_row=1">';
print_table($table);
echo '</form>';


if ($external_table) {
	
	$table_list->width = '99%';
			
	$table_list->class = 'listing';
	$table_list->data = array ();
	$table_list->head = array ();
	$table_list->size = array ();

	$table_fields = get_db_all_rows_sql("DESC ".$external_table);
	if ($table_fields == false) {
		$table_fields = array();
	}

	$table_data = get_db_all_rows_sql("SELECT * from ".$external_table);
	if ($table_data == false) {
		$table_data = array();
	}

	$i = 0;
	$key = "";
	foreach ($table_fields as $field) {
		
		if ($field['Key'] == 'PRI') {
			$key = $field['Field'];
		}
		$data = array ();
		$table_list->head[$i] = $field['Field'];

		$i++;
	}

	if (!empty($table_fields)) {
		$table_list->size[$i] = '430px';
		$table_list->head[$i] = __('Actions');
	}


	foreach ($table_data as $value) {
		
		$x = 0;
		$params = "";
		$num_params = 0;

		foreach ($table_fields as $field) {
			if ($key == $field['Field']) {
				$key_value = $value[$field['Field']];
				$data[$x] = $value[$field['Field']];
			} else {
				$data[$x] = print_input_text ($field['Field']."_".$key_value, $value[$field['Field']], '', 30, 100, true,'');
				if ($num_params != 0) {
					$params .= '|'.$field['Field'];
				} else {
					$params .= $field['Field'];
				}

				$num_params++;
			}
			$x++;
		}
		
		$data[$x] ="<a title='" . __("Edit fields") . "' href='javascript:update_row(".$key_value.",\"".$params."\");'><img src='images/accept.png'></a>";
		$data[$x] .= '<a title=' . __("Delete row") . ' href=index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&delete_row=1&id='.$id_object_type.'&external_table='.$external_table.'&key='.
					$key.'&key_value='.$key_value.'><img src="images/fail.png"></a>';
		array_push ($table_list->data, $data);

	}

	if ($add_row) {
		$j = 0;
		$data = array();
		foreach ($table_fields as $field) {
			if ($key == $field['Field']) {
				$data[$j] = "";
			} else {
				$data[$j] = print_input_text ($field['Field'], '', '', 30, 100, true,'');
			}
			$j++;
		}
		
		$data[$j] = '<a id="link_add_row" title=' . __("Add row") . ' href=""><img src="images/add.png"></a>';
					
		array_push ($table_list->data, $data);
	}
echo '<form id="external_form" name="external_edition_form" method="post" action="index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&insert_row=1">';
	print_input_hidden ('id', $id_object_type, false);
	print_input_hidden ('external_table', $external_table, false);
	print_input_hidden ('key', $key, false);
	print_table($table_list);
echo '</form>';
}

print_input_hidden ('base_url_homedir', base_url(), false);
print_input_hidden ('id', $id_object_type, false);
if(!isset($key)){
	$key = '';
}
print_input_hidden ('key', $key, false);

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
	
$(document).ready (function () {
	$("#external_table").change( function() {
		external_table = $("#external_table").val();
		id_object_type = $("#hidden-id").val();
		var url = $("#hidden-base_url_homedir").val()+"/index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&external_table="+external_table+"&id="+id_object_type;
		window.location = url;
	});
	
	$("#link_add_row").click(function (event) {
		event.preventDefault();
		$("#external_form").submit();
	});
});

function update_row(key_value, fields) {
	external_table = $("#external_table").val();
	id_object_type = $("#hidden-id").val();
	key = $("#hidden-key").val();
	
	var url = $("#hidden-base_url_homedir").val()+"/index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&update_row=1&key="+key+"&key_value="+key_value+"&external_table="+external_table+"&id="+id_object_type;
	var fields_arr = fields.split('|');

	jQuery.each (fields_arr, function (id, field_name) {

		value = $("#text-"+field_name+"_"+key_value).val();
		url += "&"+field_name+"="+value;
	});

	window.location = url;
}

</script>
