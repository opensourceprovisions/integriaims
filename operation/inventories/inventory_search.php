<?php

// Integria 2.0 - http://integria.sourceforge.net
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

if (defined ('AJAX')) {
	
	global $config;
	
	$print_subtree = get_parameter('print_subtree', 0);
	
	$id_item = get_parameter ('id_item');
	$lessBranchs = get_parameter('less_branchs');
	$type = get_parameter('type');
	$id_father = get_parameter('id_father');
	$sql_search = get_parameter('sql_search', '');
	$id_object_type = get_parameter("id_object_type_search");
	$end = get_parameter("end");
	$last_update = get_parameter("last_update");

	$ref_tree = (string)get_parameter("ref_tree");

	if ($type == 'object_types') {
		if (empty($id_item))
			$id_item = 0;

		$sql = base64_decode($sql_search);
		
		// The id_object_type can be NULL !!
		if (empty($id_item))
			$sql .= " AND (i.id_object_type IS NULL OR i.id_object_type = $id_item)";
		else
			$sql .= " AND i.id_object_type = $id_item";
		
		if ($last_update == 1) {
			$sql .= " ORDER BY i.last_update DESC";
		} else {
			$sql .= " ORDER BY i.name ASC";
		}

		//If there is a father the just print the object (we only filter in first level)
		if ($id_father) {
			$sql = "SELECT * FROM tinventory WHERE id_parent = $id_father AND id_object_type = $id_item";
			if ($last_update == 1) {
				$sql .= " ORDER BY last_update DESC";
			} else {
				$sql .= " ORDER BY name ASC";
			}			
		}
		
		$cont_aux = get_db_all_rows_sql($sql);

		$count_blanks = strlen($ref_tree);		

		$cont = enterprise_hook ('inventory_get_user_inventories', array ($config['id_user'], $cont_aux));
		if ($cont === ENTERPRISE_NOT_HOOK) {
			$cont = $cont_aux;
		}

		if (!$cont) {
			$cont = array();
		}
		
		$countRows = count($cont);

		//Empty Branch
		if ($countRows == 0) {

			echo "<ul style='margin: 0; padding: 0;'>\n";
			echo "<li style='margin: 0; padding: 0;'>";
			echo "<i>" . __("Empty") . "</i>";
			echo "</li>";
			echo "</ul>";
			
			return;
		}
		
		//Branch with items
		$new = true;
		$count = 0;
		echo "<ul style='margin: 0; padding: 0;'>\n";
		
		$cont_size = count($cont);

		$end = 1;

		foreach ($cont as $row) {

			if ($row != end($cont)) {
				$end = 0;
			}

			$aux_ref_tree = $ref_tree."".$count;
			
			$new = false;
			$count++;

			$less = $lessBranchs;
			if ($count != $countRows)
				$img = print_image ("images/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $aux_ref_tree. "_inventory_" . $row["id"], "pos_tree" => "2"));
			else {
				$less = $less + 2; // $less = $less or 0b10
				$img = print_image ("images/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $aux_ref_tree. "_inventory_" . $row["id"], "pos_tree" => "3"));
			}
			echo "<li style='margin: 0; padding: 0;'>";

	
			
			echo "<a onfocus='JavaScript: this.blur()'
						href='javascript: loadTable(\"inventory\",\"" . $row["id"] . "\", " . $less . ", \"" . $id_item . "\",  \"" . $sql_search . "\", \"". $aux_ref_tree ."\", \"". $end ."\")'>";
			
			echo $img;
			
			echo $row["name"]. '&nbsp;&nbsp;<a href="index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id='.$row['id'].'">'.print_image ("images/application_edit.png", true, array ("style" => 'vertical-align: middle;')).'</a>';
			
			echo "</a>";

			if ($end) {
				echo "<div hiddenDiv='1' loadDiv='0' class='tree_view' id='tree_div" . $aux_ref_tree . "_inventory_" . $row["id"] . "'></div>";
			} else {
				echo "<div hiddenDiv='1' loadDiv='0' class='tree_view tree_view_branch' id='tree_div" . $aux_ref_tree . "_inventory_" . $row["id"] . "'></div>";
			}
			echo "</li>";
			
		} 
	}
	echo "</ul>\n";
			
	//TERCER NIVEL DEL ARBOL.
	if ($type == 'inventory') {
	
		$sql = "SELECT id FROM tinventory WHERE `id_parent`=$id_item";

		$cont_invent = get_db_all_rows_sql($sql);
		
		$cont = enterprise_hook ('inventory_get_user_inventories', array ($config['id_user'], $cont_invent));
		
		if ($cont === ENTERPRISE_NOT_HOOK) {
			$cont = $cont_invent;
		}

		if (!$cont) {
			$cont = array();
		}

		$countRows = count($cont);

		$count_blanks = strlen($ref_tree);
	
		if ($countRows == false)
			$countRows = 0;
	
		if ($countRows == 0) {
			echo "<ul style='margin: 0; padding: 0;'>\n";
			echo "<li style='margin: 0; padding: 0;'>";
			echo "<i>" . __("Empty") . "</i>";
			echo "</li>";
			echo "</ul>";
			return;
		}
	
		//Branch with items
		$new = true;
		$count = 0;


		$clause = "";
		if ($cont) {
			
			foreach ($cont as $c) {
				$clause .= $c["id"].",";
			}

			$clause = substr($clause,0,-1);

			$clause = " AND tinventory.id IN ($clause)";
		}

		$sql = "SELECT DISTINCT(tinventory.id_object_type), tobject_type.* FROM tinventory, tobject_type 
				WHERE tinventory.id_object_type = tobject_type.id".$clause. " ORDER BY tobject_type.name ASC";
		
		$cont = get_db_all_rows_sql($sql);

		// Add no object type
		$last_key = count($cont);
		$cont[$last_key]['name'] = __('No object type');
		$cont[$last_key]['icon'] ="box.png";
		$cont[$last_key]['id'] = 0;

		echo "<ul style='margin: 0; padding: 0;'>\n";
		
		$cont_size = count($cont);

		$end = 1;
		foreach ($cont as $row) {

			if ($row != end($cont)) {
				$end = 0;
			}

			$aux_ref_tree = $ref_tree."".$count;
			
			$new = false;
			$count++;

			echo "<li style='margin: 0; padding: 0;'>";

			$less = $lessBranchs;
			if ($count != $countRows) {
				$img = print_image ("images/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $aux_ref_tree. "_object_types_" . $row["id"], "pos_tree" => "2"));
			} else {
				$less = $less + 2; // $less = $less or 0b10
				$img = print_image ("images/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $aux_ref_tree. "_object_types_" . $row["id"], "pos_tree" => "3"));
			}
			
			echo "<a onfocus='JavaScript: this.blur()'
						href='javascript: loadTable(\"object_types\",\"" . $row["id"] . "\", " . $less . ", \"" . $id_item . "\",  \"" . $sql_search . "\", \"". $aux_ref_tree ."\", \"". $end ."\")'>";

			echo $img;
			echo "<img src='images/objects/".$row["icon"]."' style='vertical-align: middle'>";
			echo '&nbsp;'.$row["name"];
			echo "</a>";
			
			if ($end) {
				echo "<div hiddenDiv='1' loadDiv='0' class='tree_view' id='tree_div" . $aux_ref_tree . "_object_types_" . $row["id"] . "'></div>";
			} else {
				echo "<div hiddenDiv='1' loadDiv='0' class='tree_view tree_view_branch' id='tree_div" . $aux_ref_tree . "_object_types_" . $row["id"] . "'></div>";
			}
			echo "</li>";
		}
		echo "</ul>\n";

	}
	
return;
}

	$sql_search = '';
	$sql_search_pagination = '';
	$params = array();
	$params_array = (string)get_parameter('params', '');
	if ($params_array != ''){	
		$params_array = base64_decode($params_array);
		$params_array = json_decode($params_array, true);
	}
	if(isset($params_array['object_fields_custom']) && $params_array['object_fields_custom'] != ''){
		$object_fields_custom = $params_array['object_fields_custom'];
	}
	
	
	//$offset = (int)get_parameter('offset', $params_array['offset']);	
	//$params['offset'] = $offset;

	//field object_fields
	if(isset($params_array['object_fields']) && $params_array['object_fields'] != ''){
		$object_fields = $params_array['object_fields'];
	} else {
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
	}

	//id_type_object
	if(isset($params_array['id_object_type_search']) && $params_array['id_object_type_search'] != ''){
		$id_object_type = $params_array['id_object_type_search'];
	} else {
		$id_object_type = (int)get_parameter('id_object_type_search', 1);
	}
	$params['id_object_type_search'] = $id_object_type;

	if ($object_fields) {
		$params['object_fields'] = $object_fields;
		if(isset($params_array['count_object_custom_fields']) && $params_array['count_object_custom_fields'] != ''){
			$count_object_custom_fields = $params_array['count_object_custom_fields'];
		} else {
			$count_object_custom_fields = 0;
		}
		foreach ($object_fields as $key => $value) {
			if ($key < 10){
				if (!isset($pr)){
					$pr = ' i.'.$value;
				} else {
					$pr .= ',i.'.$value;
				}
			} else {
				if (!isset($tr)){
					$tr = $value;
					$count_object_custom_fields++;
				} else {
					$tr .= ','.$value;
					$count_object_custom_fields++;
				}
			}
		}
		if(isset($tr)){
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
			//id_type_object
			$params['object_fields_custom'] = $object_fields_custom;
		}

		//search word in the inventory only name, description,id,status and custom fields
		if(isset($params_array['search_free']) && $params_array['search_free'] != ''){
			$search_free = $params_array['search_free'];
		} else {
			$search_free = (string)get_parameter ('search_free', '');
		}

		if ($id_object_type != -1 && !empty($object_fields) && $search_free != '') {
			$params['search_free']= $search_free;
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
			$params['search_free'] = $search_free;
			if($search_free){
				$sql_search .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
				$sql_search_pagination .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
				$sql_search_count .= " AND (i.name LIKE '%$search_free%' OR i.description LIKE '%$search_free%' OR i.id LIKE '%$search_free%' OR i.status LIKE '%$search_free%')";
			}
		}
	}
	//owner
	if(isset($params_array['owner']) && $params_array['owner'] != ''){
		$owner = $params_array['owner'];
	} else {
		$owner = (string)get_parameter('owner', '');
	}
	if ($owner != '') {
		$sql_search .= " AND i.owner = '$owner'";
		$sql_search_pagination .= " AND i.owner = '$owner'";
		$sql_search_count .= " AND i.owner = '$owner'";
		$params['owner'] = $owner;
	}

	//block size
	if(isset($params_array['block_size']) && $params_array['block_size'] != ''){
		$block_size = $params_array['block_size'];
	}
	else {
		$block_size = (int)get_parameter('block_size', $config['block_size']);
	}

	//manufacturer
	if(isset($params_array['id_manufacturer']) && $params_array['id_manufacturer'] != ''){
		$id_manufacturer = $params_array['id_manufacturer'];
	} else {
		$id_manufacturer = get_parameter ('id_manufacturer', 0);
	}

	if ($id_manufacturer != 0) {
		$sql_search .= " AND i.id_manufacturer = $id_manufacturer";
		$sql_search_pagination .= " AND i.id_manufacturer = $id_manufacturer";
		$sql_search_count .= " AND i.id_manufacturer = $id_manufacturer";
		$params['id_manufacturer'] = $id_manufacturer;
	}

	//contract
	if(isset($params_array['id_contract']) && $params_array['id_contract'] != ''){
		$id_contract = $params_array['id_contract'];
	} else {
		$id_contract = (int)get_parameter ('id_contract', 0);
	}

	if ($id_contract != 0) {
		$sql_search .= " AND i.id_contract = $id_contract";
		$sql_search_pagination .= " AND i.id_contract = $id_contract";
		$sql_search_count .= " AND i.id_contract = $id_contract";
		$params['id_contract'] = $id_contract;
	}

	//status
	if(isset($params_array['inventory_status']) && $params_array['inventory_status'] != ''){
		$inventory_status = $params_array['inventory_status'];
	} else {
		$inventory_status = (string)get_parameter('inventory_status', '0');
	}

	if (($inventory_status != '0') && ($inventory_status != 'All')) {
		$sql_search .= " AND i.status = '$inventory_status'";
		$sql_search_pagination .= " AND i.status = '$inventory_status'";
		$sql_search_count .= " AND i.status = '$inventory_status'";
		$params['inventory_status'] = $inventory_status;
	}

	//Company
	if(isset($params_array['id_company']) && $params_array['id_company'] != ''){
		$id_company = $params_array['id_company'];
	} else {
		$id_company = (int)get_parameter('id_company', 0);
	}

	if ($id_company != 0) {
		$sql_search .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$sql_search_pagination .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$sql_search_count .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='company' AND id_reference='$id_company')";
		$params['id_company'] = $id_company;
	}

	//Associated_user
	if(isset($params_array['associated_user']) && $params_array['associated_user'] != ''){
		$associated_user = $params_array['associated_user'];
	} else {
		$associated_user = (string)get_parameter('associated_user', "");
	}

	if ($associated_user != '') {
		$sql_search .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$sql_search_pagination .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$sql_search_count .= " AND i.id IN (SELECT id_inventory FROM tinventory_acl WHERE `type`='user' AND id_reference='$associated_user')";
		$params['associated_user'] = $associated_user;
	}

	//Parent name
	if(isset($params_array['parent_name']) && $params_array['parent_name'] != ''){
		$parent_name = $params_array['parent_name'];
	} else {
		$parent_name = get_parameter ('parent_name', 'None');
	}
	
	if ($parent_name != 'None') {
		$sql_parent_name = "select id from tinventory where name ='". $parent_name."';";
		$id_parent_name = get_db_sql($sql_parent_name);

		$sql_search .= " AND i.id_parent =" . $id_parent_name;
		$sql_search_pagination .= " AND i.id_parent =" . $id_parent_name;
		$sql_search_count .=  " AND i.id_parent =" . $id_parent_name;
		$params['parent_name'] = $parent_name;

	}

	//sort table
	if(isset($params_array['sort_mode']) && $params_array['sort_mode'] != ''){
		$sort_mode = $params_array['sort_mode'];
	} else {
		$sort_mode = (string)get_parameter('sort_mode', 'asc');
	}

	if(isset($params_array['sort_field_num']) && $params_array['sort_field_num'] != ''){
		$sort_field_num = $params_array['sort_field_num'];
	} else {
		$sort_field_num = (int)get_parameter('sort_field', 1);
	}

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
	if(isset($params_array['mode']) && $params_array['mode'] != ''){
		$mode = $params_array['mode'];
	} else {
		$mode = (string)get_parameter('mode', "list");
	}

	if ($mode == 'list'){
		$last_update = (int)get_parameter('last_update', 0);
		if(!$last_update){
			$sql_search .= " order by $sort_field $sort_mode ";
			$sql_search_pagination .= " group by i.id order by $sort_field $sort_mode ";
		} else {
			$sql_search .= " order by i.last_update";
			$sql_search_pagination .= " group by i.id order by i.last_update desc ";
		}
		$sql_search_count .=  " group by i.id";
		$params['mode'] = $mode;
		$params['sort_field_num'] = $sort_field_num;
		$params['sort_mode'] = $sort_mode;
		$params['count_object_custom_fields'] =$count_object_custom_fields;
		$params['last_update'] = $last_update;
		$params['block_size'] = $block_size;
	}

if (!$pure) {
	echo '<form id="tree_search" method="post" onsubmit="tree_search_submit();return false">';
		//field object types
		$select_object = '<div class = "divform">';
			$select_object .= '<table class="search-table"><tr><td>';
				$objects_type = get_object_types ();
				$objects_type[-1] = __('All');
				$select_object .= print_label (__('Object type'), '','',true);
				$select_object .= print_select($objects_type, 'id_object_type_search', $params['id_object_type_search'], 'change_object_type();', '', '', true, 4, false, false, false, '');
			$select_object .= '</td></tr></table>';
		$select_object .= '</div>';
		
		print_container_div("inventory_type_object",__("Select type object").print_help_tip (__("Select ALL to see all objects"), true),$select_object, 'open', false, false);

		//field label object types
		$select_label_object = '<div class = "divform" id = "pr">';
			$select_label_object .= '<table class="search-table"><tr><td>';
				$select_label_object .= print_label (__('Object fields').'<span id="object_fields_select_all"><a href="javascript: select_all_object_field()" >'.__('Select all').'</a><span>', '','',true);
				$select_label_object .= '<div id = "object_fields_search_check" class="div_multiselect" >';
				//checkbox id
				if ($params['object_fields'][0]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[0]" checked value="id" id="id"><label for="id">'.__('ID').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[0]" value="id" id="id"><label for="id">'.__('ID').'</label>';
				}

				//checkbox name
				if ($params['object_fields'][1]){	
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[1]" checked value="name" id="name"><label for="name">'.__('Name').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[1]" value="name" id="name"><label for="name">'.__('Name').'</label>';
				}

				//checkbox owner
				if ($params['object_fields'][2]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[2]" checked value="owner" id="owner"><label for="owner">'.__('Owner').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[2]" value="owner" id="owner"><label for="owner">'.__('Owner').'</label>';
				}

				//checkbox id_parent
				if ($params['object_fields'][3]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[3]" checked value="id_parent" id="id_parent"><label for="id_parent">'.__('Parent object').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[3]" value="id_parent" id="id_parent"><label for="id_parent">'.__('Parent object').'</label>';
				}

				//checkbox id_object_type
				if ($params['object_fields'][4]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[4]" checked value="id_object_type" id="id_object_type"><label for="id_object_type">'.__('Object type').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[4]" value="id_object_type" id="id_object_type"><label for="id_object_type">'.__('Object type').'</label>';
				}

				//checkbox manufacturer
				if ($params['object_fields'][5]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[5]" checked value="id_manufacturer" id="id_manufacturer"><label for="id_manufacturer">'.__('Manufacturer').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[5]" value="id_manufacturer" id="id_manufacturer"><label for="id_manufacturer">'.__('Manufacturer').'</label>';
				}

				//checkbox id_contract
				if ($params['object_fields'][6]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[6]" checked value="id_contract" id="id_contract"><label for="id_contract">'.__('Contract').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[6]" value="id_contract" id="id_contract"><label for="id_contract">'.__('Contract').'</label>';
				}

				//checkbox status
				if ($params['object_fields'][7]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[7]" checked value="status" id="status"><label for="status">'.__('Status').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[7]" value="status" id="status"><label for="status">'.__('Status').'</label>';
				}

				//checkbox status
				if ($params['object_fields'][8]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[8]" checked value="receipt_date" id="receipt_date"><label for="receipt_date">'.__('Receipt date').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[8]" value="receipt_date" id="receipt_date"><label for="receipt_date">'.__('Receipt date').'</label>';

				}

				//checkbox status
				if ($params['object_fields'][9]){
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[9]" checked value="issue_date" id="issue_date"><label for="issue_date">'.__('Issue date').'</label>';
				} else {
					$select_label_object .= '<input type="checkbox" class="checkbox_object_field" name="object_fields[9]" value="issue_date" id="issue_date"><label for="issue_date">'.__('Issue date').'</label>';

				}

				//checkbox custom fields
				if ($params['object_fields_custom']){
					$i=10;
					foreach ($params['object_fields_custom'] as $object) {
						if(isset($params['object_fields'][$i])){
							$select_label_object .= '<input name="object_fields['.$i.']" checked class="checkbox_object_field" value="'.$object['id'].'" type="checkbox" id="'.$object['id'].'">';
						} else {
							$select_label_object .= '<input name="object_fields['.$i.']" class="checkbox_object_field" value="'.$object['id'].'" type="checkbox" id="'.$object['id'].'">';
						}	
						$select_label_object .= '<label for="'.$object['id'].'">'.$object['label'].'</label>';
						$i++;
					}
				}
				$select_label_object .=		'</div>';
			$select_label_object .= '</td></tr></table>';
		$select_label_object .= '</div>';

		print_container_div("inventory_column",__("Column editor"),$select_label_object, 'open', false, false);
	 
		$search_other = "<div class='divresult_inventory'>";
		$table_search = new StdClass();
		$table_search->class = 'search-table-button';
		$table_search->width = '100%';
		$table_search->data = array ();
		$table_search->size[0] = "40%";
		$table_search->size[1] = "35%";
		
		//find
		$table_search->data[0][0] = print_input_text ('search_free', $params['search_free'], '', 25, 128, true, __('Search'). print_help_tip (__("Search by id, name, status, description and custom fields"), true));
		
		//associate company
		$companies = get_companies();
		$companies[0] = __("All");
		if(!isset($params['id_company'])){
			$params['id_company'] = 0;
		}
		$table_search->data[0][1] = print_select ($companies, 'id_company', $params['id_company'],'', '', 0, true, false, false, __('Associated company'), '', 'width: 218px;');

		//owner
		if(!isset($params['owner'])){
			$params['owner'] = "";
		}
		$params_assigned['input_id'] = 'text-owner';
		$params_assigned['input_name'] = 'owner';
		$params_assigned['input_value'] = $params['owner'];
		$params_assigned['title'] = 'Owner';
		$params_assigned['return'] = true;

		$table_search->data[0][2] = user_print_autocomplete_input($params_assigned);
		
		//Contract
		$contracts = get_contracts ();
		if(!isset($params['id_contract'])){
			$params['id_contract'] = '';	
		}
		$table_search->data[1][0] = print_select ($contracts, 'id_contract', $params['id_contract'],
			'', __('None'), 0, true, false, false, __('Contract'), '', '');

		//Manufacturer
		if(!isset($params['id_manufacturer'])){
			$params['id_manufacturer'] = '';
		}
		$manufacturers = get_manufacturers ();
		$table_search->data[1][1] = print_select ($manufacturers, 'id_manufacturer',
		$params['id_manufacturer'], '', __('None'), 0, true, false, false, __('Manufacturer'), '','');

		//User Assoc
		if(!isset($params['associated_user'])){
			$params['associated_user'] = '';
		}
		$params_associated['input_id'] = 'text-associated_user';
		$params_associated['input_name'] = 'associated_user';
		$params_associated['input_value'] = $params['associated_user'];
		$params_associated['title'] = __('Associated user');
		$params_associated['return'] = true;
	
		$table_search->data[1][2] = user_print_autocomplete_input($params_associated);
		
		//status
		$all_inventory_status = inventories_get_inventory_status ();
		array_unshift($all_inventory_status, __("All"));
		if(!isset($params['inventory_status'])){
			$params['inventory_status'] = 'All';
		}
		$table_search->data[2][0] = print_select ($all_inventory_status, 'inventory_status', $params['inventory_status'], '', '', '', true, false, false, __('Status'));

		//Parent name
		if(!isset($params['parent_name'])){
			$params['parent_name'] = '';
		}
		$table_search->data[2][1] =  print_input_text_extended ("parent_name", $params['parent_name'], "text-parent_name", '', 20, 0, false, "", "class='inventory_obj_search' style='width:165px !important;'", true, false,  __('Parent object'), false, true);
		$table_search->data[2][1] .= "&nbsp;&nbsp;" . print_image("images/add.png", true, array("onclick" => "show_inventory_search('','','','','','','','','','', '', '')", "style" => "cursor: pointer"));	
		$table_search->data[2][1] .= "&nbsp;&nbsp;" . print_image("images/cross.png", true, array("onclick" => "cleanParentInventory()", "style" => "cursor: pointer"));	
		//$table_search->data[2][1] .= print_input_hidden ('id_parent', $id_parent, true);

		//check
		$table_search->data[2][2] = print_checkbox_extended ('last_update', 1, $params['last_update'],
		false, '', '', true, __('Last updated'));

		//input pagination size
		$table_search->data[3][0] = '<label id="label-text-block_size" for="text-block_size">'.__('Block size for pagination').print_help_tip (__("Selects the paging block. By default it's set in the general options and limited to 2-1000"), true).'</label>';
		$table_search->data[3][0] .= '<input type="number" required pattern="^[2-100]" name="block_size" id="text-block_size" value="'.$params['block_size'].'" size="2" min="2" max="1000">';

		//order column table hidden
		$table_search->data[3][0] .= print_input_hidden ('sort_field', $params['sort_field_num'], true, false, 'sort_field');
		$table_search->data[3][0] .= print_input_hidden	('sort_mode', $params['sort_mode'], true, false, 'sort_mode');
		
		//offset pagination hidden
		if(!isset($params['offset'])){
			$params['offset'] = '';
		}
		$table_search->data[3][0] .= print_input_hidden	('offset', $params['offset'], true, false, 'offset');
		
		//mode: list, tree, pure
		$table_search->data[3][0] .= print_input_hidden ('mode', $params['mode'], true, false, 'mode');

		//csv querys
		$filter["query"] = $sql_search;
		$filter["query_pag"] = $sql_search_pagination;
		serialize_in_temp($filter, $config["id_user"]);
		
		//tree_search_submit()
		$table_search->data[3][1] = print_button(__('Export to CSV'), '', false, 'tree_search_submit(); window.open(\'' . 'include/export_csv.php?export_csv_inventory=1'.'\');', 'class="sub csv"', true);

		//button
		$table_search->data[3][2] = print_submit_button (__('Search'), 'search', false, 'class="sub search"', true);

		$search_other .= print_table($table_search, true);
		$search_other .= '</div>';
		
		print_container_div("inventory_form",__("Inventory form search"),$search_other, 'open', false, false);
	echo '</form>';
}

$write_permission = enterprise_hook ('inventory_check_acl', array ($config['id_user'], $id, true));	
$page = (int)get_parameter('page', 1);

switch ($mode) {
	case 'tree':
		echo '<div class = "inventory_tree_table" id = "inventory_tree_table">';
			inventories_print_tree($sql_search_pagination, $last_update);
		echo '</div>';
		break;
	case 'list':
		echo '<div id="tmp_data"></div>';
		echo '<div class = "inventory_list_table" id = "inventory_list_table">';
			echo '<div id= "inventory_only_table">';
				inventories_show_list2($sql_search, $sql_search_count, $params, $block_size, 0, $count_object_custom_fields, $sql_search_pagination);
			echo '</div>';
		echo '</div>';
		break;
	default:
		echo '<div id="tmp_data"></div>';
		echo '<div class = "inventory_list_table" id = "invetory_list_table">';
			echo '<div id= "inventory_only_table">';
				inventories_show_list2($sql_search, $sql_search_count, $params, $block_size, 0, $count_object_custom_fields, $sql_search_pagination);
			echo '</div>';
		echo '</div>';
		
}

echo "<div class= 'dialog ui-dialog-content' id='inventory_search_window'></div>";

?>

<script type="text/javascript">

$(document).ready (function () {

	var idUser = "<?php echo $config['id_user']; ?>";
	
	bindAutocomplete ("#text-owner", idUser);
	bindAutocomplete ("#text-associated_user", idUser);

	// Form validation
	trim_element_on_submit('#text-search_free');
	
	if ($("#tree_search").length > 0) {
		validate_user ("#tree_search", "#text-owner", "<?php echo __('Invalid user')?>");
	}
	
	//JS for massive operations
	$("#checkbox-inventorycb-all").change(function() {
		$(".cb_inventory").prop('checked', $("#checkbox-inventorycb-all").prop('checked'));
	});

	$(".cb_inventory").click(function(event) {
		event.stopPropagation();
	});

	//order colums table
	enable_table_ajax_headers();
});

</script>
