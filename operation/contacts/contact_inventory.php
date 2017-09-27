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

enterprise_include("include/functions_inventory.php", true);
include_once('include/functions_crm.php');

$id = (int) get_parameter ('id');

$contact = get_db_row ('tcompany_contact', 'id', $id);

$read = check_crm_acl ('other', 'cr', $config['id_user'], $contact['id_company']);
if (!$read) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contact inventory without permission");
	include ("general/noaccess.php");
	exit;
}

$inv_obj = enterprise_hook('inventory_get_objects_by_contact', array($contact["id"]));
if ($inv_obj === ENTERPRISE_NOT_HOOK) {
	$inv_obj = array();
}

if (!$inv_obj) {
	echo ui_print_error_message (__("This contact doesn't have any inventory objects"), '', true, 'h3', true);
} else {
	$table = new stdClass();
	$table->class = "listing";
	$table->width = "99%";
	$table->head[0] = __("Id");
	$table->head[1] = __("Name");
	$table->head[2] = __("Object type");
	$table->head[3] = __("Owner");
	$table->head[4] = __("Manufacturer");
	$table->data = array();

	foreach ($inv_obj as $inv) {
		$data = array();

		if (give_acl($config["id_user"], 0, "VR")) {
			$link_start = '<a href="index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id='.$inv["id"].'">';
			$link_end = '</a>';
		} else {
			$link_start = "";
			$link_end = "";
		}

		$data[0] = $link_start."#".$inv["id"].$link_end;
		$data[1] = $link_start.$inv["name"].$link_end;

		$obj_type = get_db_value("name", "tobject_type", "id", $inv["id_object_type"]);

		if (!$obj_type) {
			$obj_type = __("N/A");
		}

		$data[2] = $obj_type;

		$owner = get_db_value("nombre_real", "tusuario", "id_usuario", $inv["owner"]);
	
		if (!$owner) {
			$owner = __("N/A");
		}	
	
		$data[3] = $owner;

		$manufacturer = get_db_value("name", "tmanufacturer", "id", $inv["id_manufacturer"]);
	
		if (!$manufacturer) {
			$manufacturer = __("N/A");
		}
	
		$data[4] = $manufacturer; 

		array_push($table->data, $data);
	}

	print_table($table);
}
?>
