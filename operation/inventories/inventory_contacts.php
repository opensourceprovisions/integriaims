<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2007-2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$id = (int) get_parameter ('id');

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
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
		include ("general/noaccess.php");
		exit;
	}
}

require_once ('include/functions_inventories.php');

$inventory = get_db_row ('tinventory', 'id', $id);


//**********************************************************************
// Tabs
//**********************************************************************
if(!isset($inventory_name)){
	$inventory_name = '';
}
print_inventory_tabs('contacts', $id, $inventory_name);
$table = new stdClass;
$table->width = '99%';
$table->class = 'listing';
$table->head = array ();
$table->size = array ();

$table->head[0] = __('Company');
$table->head[1] = __('Contact');
$table->head[2] = __('Position');
$table->head[3] = __('Details');

if ($write_permission) {
	$table->head[4] = __('Edit');
	$table->size[4] = '40px';
	$table->align[4] = 'center';
}

$table->size[3] = '40px';
$table->align[3] = 'center';

$table->data = array ();

$contacts = get_inventory_contacts ($id, false);

if ($contacts === false)
	$contacts = array ();

$companies = get_companies ();

foreach ($contacts as $contact) {
	$data = array ();
	if(!isset($companies[$contact['id_company']])){
		$companies[$contact['id_company']] = '';
	}
	$data[0] = $companies[$contact['id_company']];
	$data[1] = $contact['fullname'];
	$details = '';
	if ($contact['phone'] != '')
		$details .= '<strong>'.__('Phone number').'</strong>: '.$contact['phone'].'<br />';
	if ($contact['mobile'] != '')
		$details .= '<strong>'.__('Mobile phone').'</strong>: '.$contact['mobile'].'<br />';
	$data[2] = $contact['position'];
	$data[3] = print_help_tip ($details, true, 'tip_view');
	if ($write_permission) {
		$data[4] = '<a href="index.php?sec=inventory&sec2=operation/contacts/contact_detail&id='.$contact['id'].'&id_inventory='.$id.'">'.
				'<img src="images/setup.gif" /></a>';
	}
	array_push ($table->data, $data);
}
print_table ($table);
?>
