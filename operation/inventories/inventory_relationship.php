<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas

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
$inventory_name = get_db_value('name', 'tinventory', 'id', $id);


$is_enterprise = false;

if (file_exists ("enterprise/include/functions_inventory.php")) {
	require_once ("enterprise/include/functions_inventory.php");
	$is_enterprise = true;
}

$write_permission = true;

if ($is_enterprise) {
	$read_permission = inventory_check_acl($config['id_user'], $id);

	$write_permission = inventory_check_acl($config['id_user'], $id, true);

	
	if (!$read_permission) {
		include ("general/noaccess.php");
		exit;
	}
}

//**********************************************************************
// Tabs
//**********************************************************************

print_inventory_tabs('relationships', $id, $inventory_name);

$delete_link = get_parameter ('delete_link', 0);
$add_link = get_parameter ('add_link', 0);
$ids_str = '';

if ($delete_link) {
	$id_src = get_parameter('id_src');
	$id_dst = get_parameter('id_dst');

	$result = process_sql_delete ('tinventory_relationship', array ('id_object_src' => $id_src, 'id_object_dst' => $id_dst));
	
	if ($result) {
		echo ui_print_success_message (__("Inventory relationship deleted"), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__("Error deleting inventory relationship"), '', true, 'h3', true);
	}
}

if ($add_link) {
	$id_dst = get_parameter('link', 0);
	$id_src = get_parameter('id_src');
		
	$sql = "INSERT INTO tinventory_relationship (id_object_src, id_object_dst) VALUES ($id_src, $id_dst)";
	$result = process_sql($sql);
	
	if ($result) {
		echo ui_print_success_message (__("Inventory relationship added"), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__("Error adding inventory relationship"), '', true, 'h3', true);
	}
}

$sql_links = "SELECT * FROM tinventory_relationship 
			WHERE `id_object_src`=$id OR `id_object_dst`=$id";
			
$all_links = get_db_all_rows_sql($sql_links);

if ($all_links == false) {
	$all_links = array();
}

$table = new stdClass;
$table->width = '100%';
$table->class = 'listing';
$table->data = array ();
$table->head = array();
$table->style = array();
$table->size = array ();
$table->size[0] = '40%';
$table->size[1] = '40%';
$table->size[2] = '20%';
	
if (empty($all_links)) {
	echo "<h4>".__('No links')."</h4>";
} else {

	$table->head[0] = __("Source");
	$table->head[1] = __("Destination");
	$table->head[2] = __("Operation");

	$data = array();

	$ids_str .= $id;
	foreach ($all_links as $key => $link) {
		$id_src = $link['id_object_src'];
		$id_dst = $link['id_object_dst'];
		
		$ids_str .= ','.$id_src.','.$id_dst;
		
		$url = "index.php?sec=inventory&sec2=operation/inventories/inventory_relationship&delete_link=1&id_src=$id_src&id_dst=$id_dst&id=$id";
		$url_id_src = 'index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id='.$id_src;
		$url_id_dst = 'index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id='.$id_dst;
		
		$data[0] = "<a href=".$url_id_src.">". inventories_link_get_name($id_src) ."</a>";
		$data[1] = "<a href=".$url_id_dst.">". inventories_link_get_name($id_dst) ."</a>";
		$data[2] = "<a onclick=\"if (!confirm('" . __('Are you sure?') . "')) return false;\" href='" . $url . "'>
		<img src='images/cross.png' border=0 /></a>";

		array_push ($table->data, $data);
	}
}

if ($ids_str == '') {
	$available_links = get_db_all_rows_sql("SELECT `id`,`name` FROM tinventory WHERE id NOT IN ($id)");
} else {
	$available_links = get_db_all_rows_sql("SELECT `id`,`name` FROM tinventory WHERE id NOT IN ($ids_str)");
}

if ($available_links == false) {
	$available_links = array();
}

$available_inventory = array();
foreach ($available_links as $key => $inventory) {
	$available_inventory[$inventory['id']] = $inventory['name'];
}

if ($write_permission) {
	$url = "index.php?sec=inventory&sec2=operation/inventories/inventory_relationship&add_link=1&id_src=$id&id=$id";

	$data[0] = "<form name=dataedit method=post action='" . $url . "'>";
	$data[0] .= print_select($available_inventory, "link", '', '', __("Select inventory"), 0, true);

	$data[1] = "";

	$data[2] = print_input_image("add_link", "images/add.png", 1, '', true);
	$data[2] .= "</form>";

	array_push ($table->data, $data);
}

print_table($table);
?>
