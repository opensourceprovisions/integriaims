<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

require_once ('include/functions_inventories.php');
require_once ('include/functions_user.php');

$id = (int) get_parameter ('id');

if (defined ('AJAX')) {
	
	global $config;
	
	$show_type_fields = (bool) get_parameter('show_type_fields', 0);
	$show_external_data = (bool) get_parameter('show_external_data', 0);
	$update_external_id = (bool) get_parameter('update_external_id', 0);
	$get_company_name = (bool) get_parameter('get_company_name', 0);
	$get_user_name = (bool) get_parameter('get_user_name', 0);
	$get_external_child = (bool) get_parameter('get_external_child', 0);
 	
 	if ($show_type_fields) {
		$id_object_type = get_parameter('id_object_type');
		$id_inventory = get_parameter('id_inventory');
		$fields = inventories_get_all_type_field ($id_object_type, $id_inventory);
	
		echo json_encode($fields);
		return;
	}
	
	if ($show_external_data) {

		$external_table_name = get_parameter('external_table_name');
		$external_reference_field = get_parameter('external_reference_field');
		$data_id_external_table = get_parameter('id_external_table');
		
		$fields_ext = inventories_get_all_external_field ($external_table_name, $external_reference_field, $data_id_external_table);

		echo json_encode($fields_ext);
		return;
	}
	
	if ($update_external_id) {
		$id_object_type_field = get_parameter('id_object_type_field');
		$id_inventory = get_parameter('id_inventory');
		$id_value = get_parameter('id_value'); //new value for id field

		$exists = get_db_value_filter('data', 'tobject_field_data', array('id_object_type_field' => $id_object_type_field, 'id_inventory'=>$id_inventory));
		if ($exists) {
			$result = process_sql_update('tobject_field_data', array('data' => $id_value), array('id_object_type_field' => $id_object_type_field, 'id_inventory'=>$id_inventory), 'AND');
		} else {
			$result = process_sql_insert('tobject_field_data', array('data' => $id_value,'id_object_type_field' => $id_object_type_field, 'id_inventory'=>$id_inventory));
		}
		
		$child = array();
		
		if ($result) {
			$parent_name = get_db_value ('external_table_name', 'tobject_type_field', 'id', $id_object_type_field);
			$child = get_db_all_rows_sql ("SELECT id FROM tobject_type_field WHERE parent_table_name ='".$parent_name."'");			
		}

		echo json_encode($child);
		return;
	}
	
	if ($get_company_name) {
		$id_company = get_parameter('id_company');
		$name = get_db_value('name', 'tcompany', 'id', $id_company);

		echo json_encode($name);
		return;
	}
	
	if ($get_user_name) {
		$id_user = get_parameter('id_user');
		$name = get_db_value('nombre_real', 'tusuario', 'id_usuario', $id_user);

		echo json_encode($name);
		return;
	}
	
	if ($get_external_child) {
		$id_object_type_field = get_parameter('id_object_type_field');
		
		$parent_name = get_db_value ('external_table_name', 'tobject_type_field', 'id', $id_object_type_field);
		$child = get_db_all_rows_sql ("SELECT id FROM tobject_type_field WHERE parent_table_name ='".$parent_name."'");

		echo json_encode($child);
		return;
	}
}

$read_permission = enterprise_hook ('inventory_check_acl', array ($config['id_user'], $id));
$write_permission = enterprise_hook ('inventory_check_acl', array ($config['id_user'], $id, true));
$manage_permission = enterprise_hook ('inventory_check_acl', array ($config['id_user'], $id, false, false, true));

if ($read_permission === ENTERPRISE_NOT_HOOK) {
	$read_permission = true;
	$write_permission = true;
	$manage_permission = true;
}
else {
	if (!$read_permission && $id) {
		include ("general/noaccess.php");
		exit;
	}
}

$inventory_name = get_db_value('name', 'tinventory', 'id', $id);

if (!defined ('AJAX')) {
	if (!$id) {
		echo "<h2>".__('Create')."</h2>";
		echo "<h4>".__('Inventory object');
		echo integria_help ("inventory_detail", true);
		echo "</h4>";
	} 
	elseif ($inventory_name) {
		//**********************************************************************
		// Tabs
		//**********************************************************************
		/* Tabs list */
		print_inventory_tabs('details', $id, $inventory_name,$manage_permission);
	
		if ($id) {
			$inventory = get_inventory ($id);
		}
	}
}

$result_msg = '';

$update = (bool) get_parameter ('update_inventory');
$create = (bool) get_parameter ('create_inventory');
$name = (string) get_parameter ('name');
$description = (string) get_parameter ('description');
$id_contract = (int) get_parameter ('id_contract');
$id_parent = (int) get_parameter ('id_parent');
$id_manufacturer = (int) get_parameter ('id_manufacturer');
$owner = (string) get_parameter ('owner');
$public = (bool) get_parameter ('public');
$id_object_type = (int) get_parameter('id_object_type');
$is_unique = true;
$msg_err = '';
$inventory_status = get_parameter('inventory_status');
$receipt_date = get_parameter('receipt_date', date ('Y-m-d'));
$issue_date = get_parameter('issue_date', date ('Y-m-d'));

if ((isset($_POST['parent_name'])) && ($_POST['parent_name'] == '')) {
	$id_parent = 0;
}

// Delete inventory
$quick_delete = get_parameter("quick_delete");

if ($quick_delete) {

	$id_inv = $quick_delete;
	if (!$manage_permission) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete inventory #".$id_inv);
		include ("general/noaccess.php");
		exit;
	}
	
	$delete_inventory = process_sql_delete ('tinventory', array('id_parent' => $id_inv));
	$sql = "DELETE FROM tinventory WHERE id=$id_inv";
	
	$result = process_sql ($sql);
	
	if ($result) {
		$delete_data = process_sql_delete ('tobject_field_data', array('id_inventory' => $id_inv));
		$inventories_to_delete = get_db_all_rows_sql("SELECT id FROM tinventory WHERE id_parent=".$id_inv);
		if ($inventories_to_delete == false) {
			$inventories_to_delete = array();
		}
		foreach ($inventories_to_delete as $inventory) {
			$delete_inv = process_sql_delete ('tinventory', array('id_inventory' => $inventory['id']));
			if ($delete_inv) {
				$delete_data = process_sql_delete ('tobject_field_data', array('id_inventory' => $inventory['id']));
			}
		}
	}	

	if ($result !== false) {
		$result_msg = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	} else {
		$result_msg = ui_print_error_message (__('There was an error deleting inventory object'), '', true, 'h3', true);
	}
	
	$id = 0;
}

if ($update) {
	
	if (!$write_permission) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update inventory #".$id);
		include ("general/noaccess.php");
		exit;
	}
	
	$old_inventory = get_db_row('tinventory', 'id', $id);
	$old_parent = get_db_value('id_parent', 'tinventory', 'id', $id);

	$check_name = false;
	$msg_err_extra = "";
	if (($old_inventory['name'] != $name) AND (!$config['duplicate_inventory_name'])) {
		$check_name = get_db_value_filter('name', 'tinventory', array('name'=>$name));
	}
	if ($check_name != false) {
		$msg_err .= ui_print_error_message (__("Name must be unique"), '', true, 'h3', true);
		$result = false;
		$msg_err_extra = ui_print_error_message (__("Name must be unique"), '', true, 'h3', true);
	} else {
		
		//Preserve old inventory type if any
		if ($old_inventory["id_object_type"]) {
			$id_object_type = $old_inventory["id_object_type"];
		}
		
		$last_update = date ("Y/m/d", get_system_time());
		if ($inventory_status != 'issued') {
			$issue_date = '';
		}
		
		$sql = sprintf ('UPDATE tinventory SET name = "%s", description = "%s",
				id_contract = %d,
				id_parent = %d, id_manufacturer = %d, owner = "%s", public = %d, id_object_type = %d, last_update = "%s",
				status="%s", receipt_date = "%s", issue_date = "%s"
				WHERE id = %d',
				$name, $description, $id_contract,
				$id_parent,
				$id_manufacturer, $owner, $public, $id_object_type, $last_update, $inventory_status, $receipt_date, $issue_date, $id);

		$result = process_sql ($sql);	
		
		if ($result !== false) {
			inventory_tracking($id, INVENTORY_UPDATED);
			
			if ($owner != $old_inventory['owner']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['owner'];
				$aditional_data['new'] = $owner;
				inventory_tracking($id, INVENTORY_OWNER_CHANGED, $aditional_data);
			}
			if ($public != $old_inventory['public']) {
				if ($public)
					inventory_tracking($id, INVENTORY_PUBLIC);
				else 
					inventory_tracking($id, INVENTORY_PRIVATE);
			}
			if ($name != $old_inventory['name']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['name'];
				$aditional_data['new'] = $name;
				inventory_tracking($id, INVENTORY_NAME_CHANGED, $aditional_data);
			}
			if ($id_contract != $old_inventory['id_contract']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['id_contract'];
				$aditional_data['new'] = $id_contract;
				inventory_tracking($id, INVENTORY_CONTRACT_CHANGED, $aditional_data);
			}
			if ($id_manufacturer != $old_inventory['id_manufacturer']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['id_manufacturer'];
				$aditional_data['new'] = $id_manufacturer;
				inventory_tracking($id, INVENTORY_MANUFACTURER_CHANGED, $aditional_data);
			}
			if ($inventory_status != $old_inventory['status']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['status'];
				$aditional_data['new'] = $inventory_status;
				inventory_tracking($id, INVENTORY_STATUS_CHANGED, $aditional_data);
			}

			if ($id_object_type != $old_inventory['id_object_type']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['id_object_type'];
				$aditional_data['new'] = $id_object_type;
				inventory_tracking($id, INVENTORY_OBJECT_TYPE_CHANGED, $aditional_data);
			}
			if ($receipt_date != $old_inventory['receipt_date']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['receipt_date'];
				$aditional_data['new'] = $receipt_date;
				inventory_tracking($id, INVENTORY_RECEIPT_DATE_CHANGED, $aditional_data);
			}
			if ($issue_date != $old_inventory['issue_date']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['issue_date'];
				$aditional_data['new'] = $issue_date;
				inventory_tracking($id, INVENTORY_ISSUE_DATE_CHANGED, $aditional_data);
			}
			if ($description != $old_inventory['description']) {
				$aditional_data = array();
				$aditional_data['old'] = $old_inventory['description'];
				$aditional_data['new'] = $description;
				inventory_tracking($id, INVENTORY_DESCRIPTION_CHANGED, $aditional_data);
			}
		}
		
		//update object type fields
		if ($id_object_type != 0) {
			$sql_label = "SELECT `label`, `type`, `unique` FROM `tobject_type_field` WHERE id_object_type = $id_object_type";
			$labels = get_db_all_rows_sql($sql_label);
			
			if ($labels === false) {
				$labels = array();
			}
			
			foreach ($labels as $label) {
					
					$values['data'] = get_parameter (base64_encode($label['label']));
					
					if ($label['unique']) {
						$is_unique = inventories_check_unique_field($values['data'], $label['type']);
						$is_unique_distinct = inventories_check_unique_update($values, $label['type'] );
						if (!$is_unique) {
							$msg_err .= ui_print_error_message (__(" Field '").$label['label'].__("' not updated. Value must be unique"), '', true, 'h3', true);
						}
					}
					$id_object_type_field = get_db_value_filter('id', 'tobject_type_field', array('id_object_type' => $id_object_type, 'label'=> $label['label']), 'AND');
					
					
					
					$values['id_object_type_field'] = $id_object_type_field;
					$values['id_inventory'] = $id;
					
					$exists_id = get_db_value_filter('id', 'tobject_field_data', array('id_inventory' => $id, 'id_object_type_field'=> $id_object_type_field), 'AND');
					if ($is_unique_distinct['id_object_type_field'] != $values['id_object_type_field']){
						if ($exists_id) 
							process_sql_update('tobject_field_data', $values, array('id_object_type_field' => $id_object_type_field, 'id_inventory' => $id), 'AND');
						else
							process_sql_insert('tobject_field_data', $values);
					}
			}
			
			inventory_tracking($id,INVENTORY_OBJECT_TYPE, $id_object_type);
		}

		//parent
		if ($id_parent != 0) {	
			if ($old_parent != false) {
				//delete fields old parent
				$old_id_object_type_inherit = get_db_value('id_object_type', 'tinventory', 'id', $old_parent);
				//parent has object
				if ($old_id_object_type_inherit !== false) {
					$old_fields = get_db_all_rows_filter('tobject_type_field', array('id_object_type'=>$old_id_object_type_inherit, 'inherit' => 1));

					if ($old_fields === false) {
						$old_fields = array();
					}
					foreach ($old_fields as $key => $old) {
						process_sql_delete('tobject_field_data', array('id_object_type_field' => $old['id'], 'id_inventory' => $id));
					}
				}
				$aditional_data = array();
				$aditional_data['old'] = $old_parent;
				$aditional_data['new'] = $id_parent;
				inventory_tracking($id,INVENTORY_PARENT_UPDATED, $aditional_data);
			}
			
			inventory_tracking($id,INVENTORY_PARENT_CREATED, $id_parent);
			
			$id_object_type_inherit = get_db_value('id_object_type', 'tinventory', 'id', $id_parent);

			//parent has object
			if ($id_object_type_inherit !== false) {
				$inherit_fields = get_db_all_rows_filter('tobject_type_field', array('id_object_type'=>$id_object_type_inherit, 'inherit' => 1));
			
				if ($inherit_fields === false) {
					$inherit_fields = array();
				}

				foreach ($inherit_fields as $key=>$field) {
					$values = array();
					$values['id_object_type_field'] = $field['id'];
					$values['id_inventory'] = $id;
					$data = get_db_value_filter('data', 'tobject_field_data', array('id_inventory' => $id_parent, 'id_object_type_field' => $field['id']));
					$values['data'] = $data;

					process_sql_insert('tobject_field_data', $values);
				}
			}
		} else if ($old_parent != false) { // Parent set to none
			$aditional_data = array();
			$aditional_data['old'] = $old_parent;
			$aditional_data['new'] = '';
			inventory_tracking($id,INVENTORY_PARENT_UPDATED, $aditional_data);
		}
		
		$inventory_companies = get_parameter("inventory_companies");
		inventory_get_user_inventories(get_parameter ('companies', $inventory_companies), true);
		
		//if ($result_hook !== ENTERPRISE_NOT_HOOK) {
			// Update users in inventory
			$users = get_parameter ('users');
			$users = explode(", ", safe_output($users));	 
			inventory_update_users ($id, $users, true);
		//}

		//if ($result_hook !== ENTERPRISE_NOT_HOOK) {
			$companies = get_parameter ('companies');
			$companies = explode(", ", safe_output($companies));
			inventory_update_companies($id, $companies, true);
		//}	
	}
	if ($result !== false) {
		$result_msg = ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	} else {
		$result_msg = ui_print_error_message (__('There was an error updating inventory object'), '', true, 'h3', true);
	}
	
	if (defined ('AJAX')) {
		echo $result_msg;
		echo $msg_err;
		return;
	}
}

if ($create) {

	if (!$write_permission) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create inventory #".$id);
		include ("general/noaccess.php");
		exit;
	}
	
	$err_message = __('Could not be created');
	
	$last_update = date ("Y/m/d", get_system_time());
	
	if ($inventory_status == 'issued') {
		$issue_date = date ('Y-m-d');
	} else {
		$issue_date = '';
	}
	
	$inventory_id = get_db_value ('id', 'tinventory', 'name', $name);
	$check_name = false;
	if (!$config['duplicate_inventory_name']) {
		$check_name = get_db_value_filter('name', 'tinventory', array('name'=>$name));
	}
	//~ if($name == '') {
	if (($name == '') || ($check_name != false)) {
		if ($name == '') {
			$err_message .= ". ".__('Name cannot be empty').".";
		}
		if ($check_name != false) {
			$err_message .= ". ".__('Name must be unique').".";
		}
		$id = false;
	} else {

		$sql = sprintf ('INSERT INTO tinventory (name, description,
				id_contract, id_parent, id_manufacturer, owner, public, id_object_type, last_update, status, receipt_date, issue_date)
				VALUES ("%s", "%s", %d, %d, %d, "%s", %d, %d, "%s", "%s", "%s", "%s")',
				$name, $description, $id_contract,
				$id_parent, $id_manufacturer, $owner, $public, $id_object_type, $last_update, $inventory_status, $receipt_date, $issue_date);
		$id = process_sql ($sql, 'insert_id');

	}
	if ($id !== false) {
		
		inventory_tracking($id,INVENTORY_CREATED);
				
		if ($public)
				inventory_tracking($id,INVENTORY_PUBLIC);
			else 
				inventory_tracking($id,INVENTORY_PRIVATE);
		
		//insert data to incident type fields
		if ($id_object_type != 0) {
			$sql_label = "SELECT `label`, `unique`, `type` FROM `tobject_type_field` WHERE id_object_type = $id_object_type";
			$labels = get_db_all_rows_sql($sql_label);
		
			if ($labels === false) {
				$labels = array();
			}
			
			foreach ($labels as $label) {

				$id_object_field = get_db_value_filter('id', 'tobject_type_field', array('id_object_type' => $id_object_type, 'label'=> $label['label']), 'AND');
				
				$values_insert['id_inventory'] = $id;
				//~ $values_insert['data'] = get_parameter (base64_encode($label['label']));
				$data_name = get_parameter (base64_encode($label['label']));
				//~ $data_name_arr = explode("#", $data_name);
				//~ foreach($data_name_arr as $data) {
					//~ $values_insert['data'] .= safe_input($data);
				//~ }
				$values_insert['data']  = $data_name;
			
				if ($label['unique']) {
					$is_unique = inventories_check_unique_field($values_insert['data'], $label['type']);
					$is_unique_distinct = inventories_check_unique_update($values_insert, $label['type']);
					if (!$is_unique) {
						$msg_err .= ui_print_error_message (__(" Field '").$label['label'].__("' not created. Value must be unique"), '', true, 'h3', true);
					}
				}
				$values_insert['id_object_type_field'] = $id_object_field;
				$id_object_type_field = get_db_value('id', 'tobject_type_field', 'id_object_type', $id_object_type);

				if ($is_unique_distinct['id_object_type_field'] != $values_insert['id_object_type_field'])
					process_sql_insert('tobject_field_data', $values_insert);
			
			}
			
			inventory_tracking($id,INVENTORY_OBJECT_TYPE, $id_object_type);
		}
		
		//parent
		if ($id_parent != 0) {
			$id_object_type_inherit = get_db_value('id_object_type', 'tinventory', 'id', $id_parent);

			//parent has object
			if ($id_object_type_inherit !== false) {
				$inherit_fields = get_db_all_rows_filter('tobject_type_field', array('id_object_type'=>$id_object_type_inherit, 'inherit' => 1));
			
				if ($inherit_fields === false) {
					$inherit_fields = array();
				}
				
				foreach ($inherit_fields as $key=>$field) {
					$values = array();
					$values['id_object_type_field'] = $field['id'];
					$values['id_inventory'] = $id;
					$data = get_db_value_filter('data', 'tobject_field_data', array('id_inventory' => $id_parent, 'id_object_type_field' => $field['id']));
					$values['data'] = $data;
	
					process_sql_insert('tobject_field_data', $values);
				}
			}
			
			inventory_tracking($id,INVENTORY_PARENT_CREATED, $id_parent);
		}
		//company tinventory_ACL
		$companies = get_parameter ('companies');
		$companies = explode(", ", safe_output($companies));
		$result_companies = inventory_update_companies ($id, $companies);
		
		//users tynventory_ACL
		$users = get_parameter ('users');
		$users = explode(", ", safe_output($users));
		$result_users = inventory_update_users ($id, $users);

		$result_msg = ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		$result_msg .= "<h3><a href='index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id=$id'>".__("Click here to continue working with Object #").$id."</a></h3>";

	} else {
		$result_msg = ui_print_error_message ($err_message, '', true, 'h3', true);
	}
	
	$id = 0;
	$name = "";
	$description = "";
	$id_contract = "";
	$id_parent = "";
	$id_manufacturer = 0;
	$public = false;
	$owner = $config['id_user'];
	$id_object_type = 0;
	$inventory_status = '';
	$receipt_date = date ('Y-m-d');
	$issue_date = date ('Y-m-d');
}


if ($id) {

	if (!$read_permission) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access inventory #".$id);
		include ("general/noaccess.php");
		exit;
	}
	
	clean_cache_db();
	
	$inventory = get_db_row ('tinventory', 'id', $id);
	$name = $inventory['name'];
	$description = $inventory['description'];
	$id_contract = $inventory['id_contract'];
	$id_parent = $inventory['id_parent'];
	$id_manufacturer = $inventory['id_manufacturer'];
	$owner = $inventory['owner'];
	$public = $inventory['public'];
	$id_object_type = $inventory['id_object_type'];
	$inventory_status = $inventory['status'];
	$receipt_date = $inventory['receipt_date'];
	
	if ($inventory_status == 'issued') {
		$issue_date = $inventory['issue_date'];
	} else {
		$issue_date = date ('Y-m-d');
	}
}

if ($id && !$inventory_name) {
    echo ui_print_error_message (__("The inventory object doesn't exist"), '', true, 'h3', true);
}
else {

	$table = new stdClass;
	$table->class = 'search-table-button';
	$table->width = '100%';
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan[4][1] = 2;
	$table->colspan[5][0] = 3;
	$table->colspan[7][0] = 3;

	/* First row */

	if ($write_permission || !$id) {
		$table->data[0][0] = print_input_text_extended ('name', $name, '', '', 40, 128, false, "", "style='width:210px;'", true, false, __('Name'));
	} else {
		$table->data[0][0] = print_label (__('Name'), '', '', true, $name);
	}

	$params_assigned['input_id'] = 'text-owner';
	$params_assigned['input_name'] = 'owner';
	$params_assigned['input_value'] = $owner;
	$params_assigned['title'] = 'Owner';
	$params_assigned['return'] = true;
	$params_assigned['attributes'] = "style='width:210px;'";

	if ($write_permission || !$id) {
		$table->data[0][1] = user_print_autocomplete_input($params_assigned);
	} else {
		$table->data[0][1] = print_label (__('Owner'), '', '', true, $owner);
	}
	
	if ($write_permission || !$id) {
		$table->data[0][2] = print_checkbox_extended ('public', 1, $public, false, '', '', true, __('Public'));
	} else {
		$table->data[0][2] = print_checkbox_extended ('public', 1, $public, true, '', '', true, __('Public'));
	}
	
	if ($write_permission || !$id) {
	
		$parent_name = $id_parent ? get_inventory_name ($id_parent) : __("None");
	
		$table->data[1][0] = print_input_text_extended ("parent_name", $parent_name, "text-parent_name", '', 20, 0, false, "", "class='inventory_obj_search' style='width:210px;'", true, false,  __('Parent object'), false, true);
		$table->data[1][0] .= "&nbsp;&nbsp;" . print_image("images/add.png", true, array("onclick" => "show_inventory_search('','','','','','','','','','', '', '')", "style" => "cursor: pointer"));
		$table->data[1][0] .= "&nbsp;&nbsp;" . print_image("images/cross.png", true, array("onclick" => "cleanParentInventory()", "style" => "cursor: pointer"));	
		$table->data[1][0] .= print_input_hidden ('id_parent', $id_parent, true);

	} else {
		$parent_name = $id_parent ? get_inventory_name ($id_parent) : __('Not set');
	
		$table->data[1][0] = print_label (__('Parent object'), '', '', true, $parent_name);
		
		if ($id_parent)
			$table->data[1][0] .= '<a href="index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id='.$id_parent.'"><img src="images/go.png" /></a>';
	}

	$contracts = get_contracts ();
	$manufacturers = get_manufacturers ();

	if ($write_permission || !$id) {
		$table->data[1][1] = print_select ($contracts, 'id_contract', $id_contract,
		'', __('None'), 0, true, false, false, __('Contract'));

		$table->data[1][2] = print_select ($manufacturers, 'id_manufacturer',
		$id_manufacturer, '', __('None'), 0, true, false, false, __('Manufacturer'));
	} else {
		$contract = isset ($contracts[$id_contract]) ? $contracts[$id_contract] : __('Not set');
		$manufacturer = isset ($manufacturers[$id_manufacturer]) ? $manufacturers[$id_manufacturer] : __('Not set');
	
		$table->data[1][1] = print_label (__('Contract'), '', '', true, $contract);
		$table->data[1][2] = print_label (__('Manufacturer'), '', '', true, $manufacturer);
	}


	/* Third row */
	$all_inventory_status = inventories_get_inventory_status ();
	$table->data[2][0] = print_select ($all_inventory_status, 'inventory_status', $inventory_status, 'show_issue_date();', '', '', true, false, false, __('Status'));


	$companies[0] = "none";
	$users[0] = "none";

	if ($id) {
		$companies = inventory_get_companies ($id);
		$count_companies = count($companies);
		if($count_companies == 0){
			$companies[0] = __('None');
		}
		$users = inventory_get_users ($id);
		$count_users = count($users);
		if($count_users == 0){
			$users[0] = __('None');
		}
	}

	if($companies){
		foreach ($companies as $key => $value) {
			$company_id = $key . ', ';
			$company_name = $value . ',';
		}
	}

	if($users){
		foreach ($users as $key => $value) {
			$user_id = $key . ', ';
			$user_name = $value . ',';
		}
	}
	
	$table->data[2][1] = print_select ($companies, 'inventory_companies', NULL,'', '', '', true, false, false, __('Associated companies'));
	$table->data[2][1] .= "&nbsp;&nbsp;<a href='javascript: show_company_associated(\"$company_name\");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
	$table->data[2][1] .= "&nbsp;&nbsp;<a href='javascript: clean_company_groups();'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";
	
	$table->data[2][1] .= print_input_hidden ("companies",$company_id, true, 'selected-companies');
	$table->data[2][2] = print_select ($users, 'inventory_users', NULL,'', '', '', true, false, false, __('Associated users'));
	$table->data[2][2] .= "&nbsp;&nbsp;<a href='javascript: show_user_associated(\"$user_name\");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
	$table->data[2][2] .= "&nbsp;&nbsp;<a href='javascript: clean_users_groups();'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";
	
	$table->data[2][2] .= print_input_hidden ("users",$user_id, true, 'selected-users');
	
	$objects_type = get_object_types ();

	if ($id_object_type == 0) {
		$disabled = false;
	} else {
		$disabled = true;
	}

	if ($write_permission || !$id) {
		$table->data[3][0] = print_label (__('Object type'), '','',true);
		$table->data[3][0] .= print_select($objects_type, 'id_object_type', $id_object_type, 'show_fields();', 'Select', '', true, 0, true, false, $disabled);
	} else {
		$object_name = get_db_value('name', 'tobject_type', 'id', $id_object_type);
		$table->data[3][0] = print_label (__('Object type'), '', '', true, $object_name);
	
		//show object hidden
		echo '<div id="show_object_fields_hidden" style="display:none;">';
		print_input_text('show_object_hidden', 1);
		echo '</div>';
	
		//id_object_type hidden
		echo '<div id="id_object_type_hidden" style="display:none;">';
		print_input_text('id_object_type_hidden', $id_object_type);
		echo '</div>';
	}
	
	$table->data[3][1] = print_input_text ('receipt_date', $receipt_date, '', 15, 15, true, __('Receipt date'));

	$table->data[3][2] = print_input_text ('issue_date', $issue_date, '', 15, 15, true, __('Removal date'));

	/* Fourth row */
	$table->colspan[4][0] = 3;		
	$table->data[4][0] = "";


	/* Fifth row */
	//$table->data[5][1] = "</div>";

	$table->colspan[6][0] = 3;	
	/* Sixth row */
	$disabled_str = ! $write_permission ? 'readonly="1"' : '';
	$table->data[6][0] = print_textarea ('description', 15, 100, $description,
	$disabled_str, true, __('Description'));

	echo '<div class="result">'.$result_msg.$msg_err.'</div>';

	if ($write_permission || !$id) {
	
		echo '<form method="post" id="inventory_status_form">';
		print_table ($table);
			echo '<div style="width:100%;">';
				unset($table->data);
				$table->width = '100%';
				$table->class = "button-form";
				if ($id) {
					$button = print_input_hidden ('update_inventory', 1, true);
					$button .= print_input_hidden ('id', $id, true);
					$button .= print_submit_button (__('Update'), 'update', false, 'class="sub upd"', true);
				} else {
					$button = print_input_hidden ('create_inventory', 1, true);
					$button .= print_submit_button (__('Create'), 'create', false, 'class="sub create"', true);
				}
				
				$table->data[7][0] = $button;
				print_table ($table);
			echo '</div>';
		echo '</form>';
	} else {
		$table->class = 'search-table';
		print_table ($table);
	}

	//id_inventory hidden
	echo '<div id="id_inventory_hidden" style="display:none;">';
	print_input_text('id_object_hidden', $id);
	echo '</div>';

	echo "<div class= 'dialog ui-dialog-content' id='external_table_window'></div>";

	echo "<div class= 'dialog ui-dialog-content' id='inventory_search_window'></div>";

	echo "<div class= 'dialog ui-dialog-content' id='company_search_modal'></div>";

	echo "<div class= 'dialog ui-dialog-content' id='user_search_modal'></div>";

}
?>

<script type="text/javascript" src="include/js/jquery.metadata.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>
<script type="text/javascript" src="include/js/integria_inventory.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>

<script type="text/javascript">

add_datepicker ("#text-receipt_date");
add_datepicker ("#text-issue_date");

$(document).ready (function () {
	
	configure_inventory_form (false);
	
	show_issue_date();
	
	if ($("#text-show_object_hidden").val() == 1) { //user with only read permissions
		show_fields();
	} else {
		if ($("#id_object_type").val() != 0) {
			show_fields();
		}
	}

/*
	$("form.delete").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?'); ?>"))
			return false;
	});
*/
	
	var idUser = "<?php echo $config['id_user'] ?>";
	
	bindAutocomplete ("#text-owner", idUser);	
	
	$("#detele_inventory_submit_form").click(function (event) {
		event.preventDefault();

		$("#delete_inventory_form").submit();
	});

});

//Onchange
$("#text-owner").change(function() {
	id_name = $("#text-owner").val();
	console.log(id_name);
	onchange_owner_company();
});

// Form validation
trim_element_on_submit('#text-name');
validate_form("#inventory_status_form");
validate_user ("#inventory_status_form", "#text-owner", "<?php echo __('Invalid user')?>");
var rules, messages;

var id_object = $("#text-id_object_hidden").val();

if (!id_object) {
	// Rules: #text-name
	rules = {
		required: true,
		remote: {
				url: "ajax.php",
				type: "POST",
				data: {
					page: "include/ajax/remote_validations",
					search_duplicate_name: 1,
					inventory_name: function() { return $("#text-name").val() }
				}
			}
	};
	messages = {
		required: "<?php echo __('Name required')?>",
		remote: "<?php echo __('Duplicate name')?>"
	};
	add_validate_form_element_rules('#text-name', rules, messages);
}

// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
		type: "POST",
		data: {
			page: "include/ajax/remote_validations",
			search_existing_inventory: 1,
			name: function() { return $('#text-name').val() },
			inventory_id: <?php echo $id_inventory ?>
		}
	}
};
messages = {
	required: "<?php echo __('Name required'); ?>",
	remote: "<?php echo __('This name already exists'); ?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>

<?php //endif; ?>
