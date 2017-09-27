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

include_once('include/functions_user.php');

$get_external_data = get_parameter('get_external_data', 0);
$get_inventory_search = get_parameter('get_inventory_search', 0);
$get_company_associated = get_parameter('get_company_associated', 0);
$get_user_associated = get_parameter('get_user_associated', 0);
$get_inventory_name = (bool) get_parameter('get_inventory_name', 0);
$select_fields = get_parameter('select_fields', 0);
$change_table = get_parameter('change_table', 0);
$printTable = get_parameter('printTable', 0);
$printTableMoreInfo = get_parameter('printTableMoreInfo', 0);
$get_item_info = (bool) get_parameter('get_item_info', 0);
$form_inventory = (bool) get_parameter('form_inventory', 0);
$quick_delete = (bool) get_parameter('quick_delete', 0);
$change_owner = (bool) get_parameter('change_owner', 0);

if ($select_fields) {
	$id_object_type = get_parameter('id_object_type');
	
	$fields_sql = 'select label, id from tobject_type_field where show_list=1 and id_object_type='.$id_object_type;
	$fields = get_db_all_rows_sql($fields_sql);

	if ($fields === false) {
		$fields = array();
	}
	
	$object_fields = array();
	foreach ($fields as $key => $field) {
		$object_fields[$field['id']] = $field['label'];
	}

	echo json_encode($object_fields);
	return;
}

if ($quick_delete) {
	$id_inv = get_parameter('id_inventory');
	if (give_acl ($config['id_user'], 0, "VW")) {
		borrar_objeto ($id_inv);
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "Object deleted","User ".$config['id_user']." deleted object #".$id_inv);
	} else {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Forbidden","User ".$config['id_user']." try to delete object");
		echo ui_print_error_message (__('There was a problem deleting object'), '', true, 'h3', true);
		no_permission();
	}
	
	return;
}

if ($form_inventory) {

	$id_object_type = (int)get_parameter('id_object_type', 1);
	$object_fields_default = array();
		$object_fields_default[0] = 'id';
		$object_fields_default[1] = 'name';
		$object_fields_default[2] = 'owner';
		$object_fields_default[3] = 'id_parent';
		$object_fields_default[4] = 'id_object_type';
		$object_fields_default[5] = 'id_manufacturer';
		$object_fields_default[6] = 'id_contract';
		$object_fields_default[7] = 'status';
		$object_fields_default[8] = 'receipt_date';
		$object_fields_default[9] = 'issue_date';

	$object_fields = get_parameter('object_fields', $object_fields_default);

	$sql_object_fields_custom = 'select label, id from tobject_type_field where show_list=1 and id_object_type='.$id_object_type;
	$object_fields_custom = get_db_all_rows_sql($sql_object_fields_custom);
	$params['object_fields'] = $object_fields_default;
	$params['object_fields_custom'] = $object_fields_custom;
	
	form_inventory($params);
	
	return;
}

if ($change_table) {
	$sql_search = '';
	$sql_search_pagination = '';


	$search_free = (string)get_parameter ('search_free', '');

	$params = array();
	//offset
	//$offset = (int)get_parameter('offset', 0);
	//$params['offset'] = $offset;

	//block size
	$block_size = (int)get_parameter('block_size', $config['block_size']);
	$params['block_size'] = $block_size;

	//id del objeto
	$id_object_type = (int)get_parameter('id_object_type_search', 1);
	$params['id_object_type_search'] = $id_object_type;

	//este campo es mortal
	$object_fields_default = array();
		$object_fields_default[0] = 'id';
		$object_fields_default[1] = 'name';
		$object_fields_default[2] = 'owner';
		$object_fields_default[3] = 'id_parent';
		$object_fields_default[4] = 'id_object_type';
		$object_fields_default[5] = 'id_manufacturer';
		$object_fields_default[6] = 'id_contract';
		$object_fields_default[7] = 'status';
		$object_fields_default[8] = 'receipt_date';
		$object_fields_default[9] = 'issue_date';

	$object_fields = get_parameter('object_fields', $object_fields_default);
	if ($object_fields) {
		$params['object_fields'] = $object_fields;
		
		$count_object_custom_fields = 0;
		foreach ($object_fields as $key => $value) {
			if ($key < 10){
				if (!$pr){
					$pr = ' i.'.$value;
				} else {
					$pr .= ',i.'.$value;
				}
			} else {
				if (!$tr){
					$tr = $value;
					$count_object_custom_fields++;
				} else {
					$tr .= ','.$value;
					$count_object_custom_fields++;
				}
			}
		}
		if($tr){
			$sql_search = 'SELECT '.$pr.', o.label, t.data FROM tinventory i, tobject_field_data t, tobject_type_field o where t.id_object_type_field= o.id and i.id = t.id_inventory and t.id_object_type_field IN ('.$tr.')';
			
			$sql_search_pagination = 'SELECT '.$pr.' FROM tinventory i, tobject_field_data t, tobject_type_field o where t.id_object_type_field= o.id and i.id = t.id_inventory';
			$sql_search_count = 'SELECT i.id, i.name FROM tinventory i, tobject_field_data t, tobject_type_field o where t.id_object_type_field= o.id and i.id = t.id_inventory';
		} else {
			$sql_search = 'SELECT '.$pr.' FROM tinventory i WHERE 1=1';
			$sql_search_pagination = 'SELECT '.$pr.' FROM tinventory i WHERE 1=1';
			$sql_search_count = 'SELECT i.id, i.name FROM tinventory i WHERE 1=1';
		}
		

		if ($id_object_type != -1) {
			$sql_search .= " AND i.id_object_type = $id_object_type";
			$sql_search_pagination .= " AND i.id_object_type = $id_object_type";
			$sql_search_count .= " AND i.id_object_type = $id_object_type";
			
			//for object fields
			$sql_object_fields_custom = 'select label, id from tobject_type_field where show_list=1 and id_object_type='.$id_object_type;
			$object_fields_custom = get_db_all_rows_sql($sql_object_fields_custom);
			$params['object_fields_custom'] = $object_fields_custom;
		}
		
		//search word in the inventory only name, description,id,status and custom fields
		if ($id_object_type != -1 && !empty($object_fields) && $search_free != '') {
			$string_fields_object_types == '';
			$string_fields_types == '';
			foreach ($object_fields as $k=>$f) {
				if (is_numeric($f)){
					if($string_fields_object_types == ''){
						$string_fields_object_types = "$f";
					} else {
						$string_fields_object_types .= ",$f ";
					}
				}
			}		
			$params['search_free']= $search_free;
			
			if ($string_fields_object_types){
				$sql_search .= " AND ((t.`id_object_type_field` IN ($string_fields_object_types) ";	
				$sql_search_pagination .= " AND ((t.`id_object_type_field` IN ($string_fields_object_types) ";		
				$sql_search .= "AND t.`data` LIKE '%$search_free%')";
				$sql_search_pagination .= "AND t.`data` LIKE '%$search_free%')";
				$sql_search .= "OR (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%'))";
				$sql_search_pagination .= "OR (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%'))";

				$sql_search_count .= " AND ((t.`id_object_type_field` IN ($string_fields_object_types) ";		
				$sql_search_count .= "AND t.`data` LIKE '%$search_free%')";
				$sql_search_count .= "OR (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%'))";
			} else {
				if($search_free){
					$sql_search .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
					$sql_search_pagination .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
					$sql_search_count .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
				}
			}
		
		} else { //búsqueda solo en nombre y descripción de inventario
			if($search_free){
				$sql_search .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
				$sql_search_pagination .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
				$sql_search_count .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";

				$params['search_free'] = $search_free;
			}
		}
	}
	//propietario
	$owner = (string)get_parameter('owner', '');
	if ($owner != '') {
		$sql_search .= " AND i.owner = '$owner'";
		$sql_search_pagination .= " AND i.owner = '$owner'";
		$sql_search_count .= " AND i.owner = '$owner'";
		$params['owner'] = $owner;
	}

	//manufacturer
	$id_manufacturer = get_parameter ('id_manufacturer', 0);
	if ($id_manufacturer != 0) {
		$sql_search .= " AND i.id_manufacturer = $id_manufacturer";
		$sql_search_pagination .= " AND i.id_manufacturer = $id_manufacturer";
		$sql_search_count .= " AND i.id_manufacturer = $id_manufacturer";
		$params['id_manufacturer'] = $id_manufacturer;
	}

	//contract
	$id_contract = (int)get_parameter ('id_contract', 0);
	if ($id_contract != 0) {
		$sql_search .= " AND i.id_contract = $id_contract";
		$sql_search_pagination .= " AND i.id_contract = $id_contract";
		$sql_search_count .= " AND i.id_contract = $id_contract";
		$params['id_contract'] = $id_contract;
	}

	//status
	$inventory_status = (string)get_parameter('inventory_status', '0');
	if ($inventory_status != '0') {
		$sql_search .= " AND i.status = '$inventory_status'";
		$sql_search_pagination .= " AND i.status = '$inventory_status'";
		$sql_search_count .= " AND i.status = '$inventory_status'";
		$params['inventory_status'] = $inventory_status;
	}

	//Company
	$id_company = (int)get_parameter('id_company', 0);
	if ($id_company != 0) {
		$sql_search .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$sql_search_pagination .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$sql_search_count .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$params['id_company'] = $id_company;
	}

	//Associated_user
	$associated_user = (string)get_parameter('associated_user', "");
	if ($associated_user != '') {
		$sql_search .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$sql_search_pagination .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$sql_search_count .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$params['associated_user'] = $associated_user;
	}

	//Parent name
	$parent_name = get_parameter ('parent_name', 'None');
	if ($parent_name != 'None') {
		$sql_parent_name = "select id from tinventory where name ='". $parent_name."';";
		$id_parent_name = get_db_sql($sql_parent_name);

		$sql_search .= " AND i.id_parent =" . $id_parent_name;
		$sql_search_pagination .= " AND i.id_parent =" . $id_parent_name;
		$sql_search_count .=  " AND i.id_parent =" . $id_parent_name;
		$params['parent_name'] = $parent_name;

	}

	//sort table
	
	$last_update = (int)get_parameter('last_update', 0);
	$sort_mode = (string)get_parameter('sort_mode', 'asc');
	$sort_field_num = (int)get_parameter('sort_field', 1);
	switch ($sort_field_num) {
		case 0: $sort_field = "id";break;
		case 1: $sort_field = "name";break;
		case 2: $sort_field = "owner";break;
		case 7: $sort_field = "status";break;
		case 8: $sort_field = "receipt_date";break;
		default:
			$sort_field = "name";
			break;
	}	
	//mode list or tree
	$mode = (string)get_parameter('mode', "list");
	if ($mode == 'list'){
		if(!$last_update){
			$sql_search .= " order by $sort_field $sort_mode ";
			$sql_search_pagination .= " group by i.id order by $sort_field $sort_mode ";
		} else {
			$sql_search .= " order by i.last_update desc";
			$sql_search_pagination .= " group by i.id order by i.last_update desc ";
		}
		
		$sql_search_count .=  " group by i.id";
		$params['mode'] = $mode;
		$params['sort_field_num'] = $sort_field_num;
		$params['sort_mode'] = $sort_mode;
		$params['count_object_custom_fields'] =$count_object_custom_fields;
		$params['last_update'] = $last_update;
	}
	if($mode == 'list'){
		inventories_show_list2($sql_search, $sql_search_count, $params, $block_size, 0, $count_object_custom_fields, $sql_search_pagination);
	} else {
		inventories_print_tree($sql_search_count, $last_update);
	}
	return;
}

if ($printTable) {
	$id_item = get_parameter('id_item');
	$type = get_parameter('type');
	$id_father = get_parameter('id_father');

	inventories_printTable($id_item, $type, $id_father);
	return;
}

if ($get_item_info) {
	$id_item = get_parameter('id_item');
	$id_father = get_parameter('id_father');

	echo json_encode(inventories_get_info($id_item, $id_father));
	return;
}

if ($get_inventory_name) {
	$id_inventory = get_parameter('id_inventory');
	$name = get_db_value('name', 'tinventory', 'id', $id_inventory);

	echo safe_output($name);
	return;
}

if ($get_external_data) {
	$table_name = get_parameter('table_name');
	$id_table = (string) get_parameter('id_table');
	$element_name = get_parameter('element_name');
	$id_object_type_field = get_parameter('id_object_type_field');
	$id_parent_value = get_parameter('id_parent_value', 0);
	$id_parent_table = get_parameter('id_parent_table', "");
	$external_label = get_parameter('external_label', "");

	//We use MYSQL_QUERY becase we need this to fail silently to not show
	//SQL errors on screen
	$exists = mysql_query("SELECT * FROM ".$table_name." LIMIT 1");

	if (!$exists) {
		echo ui_print_error_message (__("External table is not present"), '', true, 'h3', true);
		return;
	}
	
	$sql_ext = "SHOW COLUMNS FROM ".$table_name;
	$desc_ext = get_db_all_rows_sql($sql_ext);

	$parent_reference_field = get_db_value_sql('SELECT parent_reference_field FROM tobject_type_field WHERE id='.$id_object_type_field);
	
	$fields = array();
	foreach ($desc_ext as $key=>$ext) {
		if ($parent_reference_field == $ext['Field']) {
			continue;
		}
		$fields[$ext['Field']] = $ext['Field'];
	}

	if ($id_parent_value) {
		$id_parent = get_parameter("id_parent",0);
		$table_name_parent = get_db_value_sql("SELECT parent_table_name FROM tobject_type_field WHERE id=".$id_object_type_field);
		$id_reference_parent = get_db_value_sql("SELECT parent_reference_field FROM tobject_type_field WHERE id=".$id_object_type_field);
		$parent_label = get_db_value_sql("SELECT external_label FROM tobject_type_field WHERE id=".$id_parent);
		$id_parent_value = get_db_value_sql('SELECT '.$id_parent_table.' FROM '.$table_name_parent.' WHERE '.$parent_label.'="'.$id_parent_value.'"');
		$external_data = get_db_all_rows_sql("SELECT * FROM $table_name WHERE ".$id_reference_parent."=".$id_parent_value);
	} else {
		$external_data = get_db_all_rows_in_table($table_name);
	}

	if ($external_data !== false) {
	
		$table->class = 'listing';
		$table->width = '98%';
		$table->data = array ();
		$table->head = array ();
		
		$keys = array_keys($fields);
	
		$i = 0;
		foreach ($keys as $k=>$head) {
			$table->head[$i] =$head;
			if ($head == $id_table)
				$pos_id = $i+1;
			$i++;
		}
	
		foreach ($external_data as $key => $ext_data) {
			$j = 0;
			$data_name = $ext_data[$external_label];

			foreach ($ext_data as $k => $dat) {

				if ($k == $id_table) {
					$val_id = $dat;
				}
				if (array_key_exists($k, $fields)) {
					//~ $data[$j] = "<a href='javascript: enviar(" . $val_id . ", " . $element_name . ", " . $id_object_type_field . ")'>".$dat."</a>";	
					$data[$j] = "<a href='javascript: enviar(" . $val_id . ", " . $element_name . ", " . $id_object_type_field . ", \"" . safe_output($data_name) . "\")'>".safe_output($dat)."</a>";	
				}
				$j++;
			}
			array_push ($table->data, $data);
		}

		print_table ($table);
	} else {
		echo "<h4>".__("No data to show")."</h4>";
	}
	return;
}

//Search formulary modal
if ($get_inventory_search) {
	$search = get_parameter('search', 0);
	$search_free = get_parameter ('search_free', '');
	$id_object_type_search = get_parameter ('id_object_type_search', 0);
	$owner = get_parameter('owner_search', '');
	$id_manufacturer = get_parameter ('id_manufacturer_search', 0);
	$id_contract = get_parameter ('id_contract_search', 0);
	$last_update = get_parameter ('last_update_search');
	$offset = get_parameter('offset', 0);
	$inventory_status = (string)get_parameter('inventory_status_search', "0");
	$id_company = (int)get_parameter('id_company', 0);
	$associated_user = (string)get_parameter('associated_user_search', "");
	$fields_selected = get_parameter('object_fields_search');
	$table_search->class = 'search-table';
	$table_search->width = '98%';
	$table_search->data = array ();
	
	$table_search->data[0][0] = print_input_text ('search_free_modal', $search_free, '', 40, 128, true, __('Search'));
	
	$objects_type = get_object_types ();
	$table_search->data[0][1] = print_label (__('Object type'), '','',true);
	$table_search->data[0][1] .= print_select($objects_type, 'id_object_type_search_modal', $id_object_type_search, 'show_type_fields();', 'Select', '', true, 0, true, false, false, 'width: 200px;');
	
	$table_search->data[0][2] = print_label (__('Object fields'), '','',true);
	
	$object_fields_search = array();
	
	if ($fields_selected != '') {
		$fields = explode(',',$fields_selected);
		foreach ($fields as $selected) {
			$label_field = get_db_value('label', 'tobject_type_field', 'id', $selected);
			$object_fields_search[$selected] = $label_field;
		}
	}
	$table_search->data[0][2] .= print_select($object_fields_search, 'object_fields_search[]', '', '', 'Select', '', true, 4, true, false, false, 'width: 200px;');
	
	$params_assigned['input_id'] = 'text-owner_search';
	$params_assigned['input_name'] = 'owner_search';
	$params_assigned['input_value'] = $owner;
	$params_assigned['title'] = 'Owner';
	$params_assigned['return'] = true;
	$table_search->data[1][0] = user_print_autocomplete_input($params_assigned);
	
	$contracts = get_contracts ();
	$manufacturers = get_manufacturers ();
	
	$table_search->data[1][1] = print_select ($contracts, 'id_contract_search_modal', $id_contract,
		'', __('None'), 0, true, false, false, __('Contract'), '', 'width: 200px;');
	$table_search->data[1][2] = print_select ($manufacturers, 'id_manufacturer_search_modal',
		$id_manufacturer, '', __('None'), 0, true, false, false, __('Manufacturer'), '','width: 200px;');
	
	$table_search->data[1][3] = print_checkbox_extended ('last_update_search_modal', 1, $last_update,
		false, '', '', true, __('Last updated'));
	
	$all_inventory_status = inventories_get_inventory_status ();
	array_unshift($all_inventory_status, __("All"));
	$table_search->data[2][0] = print_select ($all_inventory_status, 'inventory_status_search_modal', $inventory_status, '', '', '', true, false, false, __('Status'));
	
	$params_associated['input_id'] = 'text-associated_user_search';
	$params_associated['input_name'] = 'associated_user_search';
	$params_associated['input_value'] = $associated_user;
	$params_associated['title'] = __('Associated user');
	$params_associated['return'] = true;
	$table_search->data[2][1] = user_print_autocomplete_input($params_associated);
	
	$companies = get_companies();
	$companies[0] = __("All");
	$table_search->data[2][2] = print_select ($companies, 'id_company_modal', $id_company,'', '', 0, true, false, false, __('Associated company'), '', 'width: 200px;');
	
	$table_search->data[3][0] = "&nbsp;";
	$table_search->colspan[3][0] = 4;
	$buttons = '<div style="width:'.$table_search->width.'" class="action-buttons button">';
	$buttons .= "<input type='button' class='sub next' onClick='javascript: loadParams(\"$search_free\");' value='".__("Search")."'>";
	$buttons .= '</div>';
	$table_search->data[4][0] = $buttons;
	$table_search->colspan[4][0] = 4;
	print_table($table_search);	

	$sql_search = 'SELECT tinventory.* FROM tinventory , tobject_field_data t, tobject_type_field o where t.id_object_type_field= o.id and tinventory.id = t.id_inventory';
	$sql_search_count = 'SELECT COUNT(tinventory.id) FROM tinventory, tobject_field_data t, tobject_type_field o where t.id_object_type_field= o.id and tinventory.id = t.id_inventory';	
	

	if ($search) {
		$params .= "&search_free=$search_free";
		$params = '&search=1';
		if ($id_object_type_search != 0 && !empty($fields_selected) && $search_free != '') {		
			if ($fields_selected){
				$sql_search .= " AND ((t.`id_object_type_field` IN ($fields_selected) ";			
				$sql_search .= "AND t.`data` LIKE '%$search_free%')";
				$sql_search .= "OR (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%'))";

				$sql_search_count .= " AND ((t.`id_object_type_field` IN ($fields_selected) ";		
				$sql_search_count .= "AND t.`data` LIKE '%$search_free%')";
				$sql_search_count .= "OR (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%'))";
			} else {
				$sql_search .= " AND (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%')";
				$sql_search_count .= " AND (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%')";
			}
		
		} else { //búsqueda solo en nombre y descripción de inventario
			$params['search_free'] = $search_free;
			$sql_search .= " AND (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%')";
			$sql_search_count .= " AND (tinventory.name LIKE '%$search_free%' OR tinventory.description LIKE '%$search_free%' OR tinventory.id LIKE '%$search_free%' OR tinventory.status LIKE '%$search_free%')";
		}

		if ($id_object_type_search) {
			$params .= "&id_object_type_search=$id_object_type";
			$sql_search .= " AND tinventory.id_object_type = $id_object_type_search";
			$sql_search_count .= " AND tinventory.id_object_type = $id_object_type_search";
		}
		if ($owner != '') {
			$sql_search .= " AND tinventory.owner = '$owner'";
			$sql_search_count .= " AND tinventory.owner = '$owner'";
			$params .= "&owner=$owner";
		}
		if ($id_manufacturer != 0) {
			$sql_search .= " AND tinventory.id_manufacturer = $id_manufacturer";
			$sql_search_count .= " AND tinventory.id_manufacturer = $id_manufacturer";
			$params .= "&id_manufacturer=$id_manufacturer";
		}
		if ($id_contract != 0) {
			$sql_search .= " AND tinventory.id_contract = $id_contract";
			$sql_search_count .= " AND tinventory.id_contract = $id_contract";
			$params .= "&id_contract=$id_contract";
		}
		if ($inventory_status != "0") {
			$sql_search .= " AND tinventory.status = '$inventory_status'";
			$sql_search_count .= " AND tinventory.status = '$inventory_status'";
			$params .= "&inventory_status=$inventory_status";
		}
		if ($id_company != 0) {
			$sql_search .= " AND tinventory.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
			$sql_search_count .= " AND tinventory.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
			$params .= "&id_company=$id_company";
		}
		if ($associated_user != '') {
			$sql_search .= " AND tinventory.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
			$sql_search_count .= " AND tinventory.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
			$params .= "&associated_user=$associated_user";
		}
		
			$sql_search .= " group by tinventory.id";
		

	} 

	inventories_show_list($sql_search, $sql_search_count, $params, $last_update, 1);
		
	return;
}

if ($get_company_associated) {
	$filter = get_parameter("filter");
	if($filter != 'none,'){
	
		$companies_prepare = get_companies();
		$companies_selected_prepare = explode(",", $filter);
		
		$companies = array_diff($companies_prepare, $companies_selected_prepare);
		$companies_selected = array_intersect($companies_prepare, $companies_selected_prepare);	
	
	} else {
		$companies = get_companies();
	}
	
	echo '<div class="div_ui div_left_ui">';
		echo print_select ($companies, "origin", '', '', '', 0, true, true, false);
	echo '</div>';
	echo '<div class="div_middle_ui">';
		echo '<a class="pass left"><img src="images/flecha_dcha.png"/></a><br/>';
		echo '<a class="passall left"><img src="images/go_finish.png"/></a><br/>';
		echo '<a class="remove right"><img src="images/flecha_izqda.png"/></a><br/>';
		echo '<a class="removeall right"><img src="images/go_begin.png"/></a>';
	echo '</div>';
	echo '<div class="div_ui div_right_ui">';
		echo print_select ($companies_selected, "destiny", '', '', '', 0, true, true, false, false, false, '', true);
	echo '</div>';	
	echo '<p class="button_send_groups"><input type="button" value='.__('Submit').' onclick="load_company_groups()" /></p>';
	echo '</form>';

	return;
}

if ($get_user_associated) {
	$filter = get_parameter("filter");
	
	if($filter != 'none,'){
		$name_prepare = get_user_visible_users($config['id_user']);
		$name_selected_prepare = explode(",", $filter);
		
		$name = array_diff($name_prepare, $name_selected_prepare);

		$name_selected = array_intersect($name_prepare, $name_selected_prepare);
	} else {
		$name = get_user_visible_users($config['id_user']);
	}

	echo '<div class="div_ui div_left_ui">';
		echo print_select ($name, "origin_users", '', '', '', 0, true, true, false);
	echo '</div>';
	echo '<div class="div_middle_ui">';
		echo '<a class="pass left"><img src="images/flecha_dcha.png"/></a><br/>';
		echo '<a class="passall left"><img src="images/go_finish.png"/></a><br/>';
		echo '<a class="remove right"><img src="images/flecha_izqda.png"/></a><br/>';
		echo '<a class="removeall right"><img src="images/go_begin.png"/></a>';
	echo '</div>';
	echo '<div class="div_ui div_right_ui">';
		echo print_select ($name_selected, "destiny", '', '', '', 0, true, true, false, false, false, '', true, "destiny_users");
	echo '</div>';	
	echo '<p class="button_send_groups"><input type="button" value='.__('Submit').' onclick="load_users_groups()" /></p>';
	echo '</form>';

	return;
}

if ($printTableMoreInfo) {

	$id_inventory = get_parameter('id_inventory');
	
	$id_object_type = get_db_value_sql('SELECT id_object_type FROM tinventory WHERE id='.$id_inventory);

	if ($id_object_type) {
		$object_fields = get_db_all_rows_sql("SELECT * FROM tobject_type_field WHERE id_object_type=".$id_object_type);

		if ($object_fields == false) {
			$object_fields = array();
		}
		$table_info->class = 'list';
		$table_info->width = '98%';
		$table_info->data = array ();
		
		$i = 0;
		foreach ($object_fields as $field) {
			$value = get_db_value_sql("SELECT data FROM tobject_field_data WHERE id_inventory=".$id_inventory." AND id_object_type_field=".$field['id']);

			if ($value == "") {
				$value = "--";
			}
			$table_info->data[$i][0] = print_label ($field['label'], '','',true);
			$table_info->data[$i][1] = $value;
			$i++;
		}
		
		print_table($table_info);
		return;
	} else {
		echo "<b>".__('No data to show')."</b>";
		return;
	}
	
}

if ($change_owner){
	$config['mysql_result_type'] = MYSQL_ASSOC;
	$id_name = get_parameter('id_name');
	$sql = "SELECT com.id, com.name FROM tcompany com, tusuario usr WHERE usr.id_company = com.id AND id_usuario='" . $id_name ."';";
	$name_company = get_db_all_rows_sql($sql);
	$name_company = json_encode($name_company, true);
	echo safe_output($name_company);
	return;
}

?>