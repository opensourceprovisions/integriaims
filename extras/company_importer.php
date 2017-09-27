<?php

// INTEGRIA IMS v3.0
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

// Importer for Companies (Sugar CRM 6.3.1).

// Format expected:
// "id","name","date_entered","date_modified","modified_user_id","created_by","description","deleted","assigned_user_id","account_type","industry","annual_revenue","phone_fax","billing_address_street","billing_address_city","billing_address_state","billing_address_postalcode","billing_address_country","rating","phone_office","phone_alternate","website","ownership","employees","ticker_symbol","shipping_address_street","shipping_address_city","shipping_address_state","shipping_address_postalcode","shipping_address_country","parent_id","sic_code","campaign_id","email_address","account_name","assigned_user_name"


$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc < 4) {
	echo 'Usage: '.$argv[0].' username password csvfile [separator]'."\n";
	return 1;
}

$dir = realpath (dirname (__FILE__).'/..');
$path = get_include_path ();
set_include_path ($path.PATH_SEPARATOR.$dir);

$libs = array ('include/config.php',
	'include/functions.php',
	'include/functions_db.php');
foreach ($libs as $file) {
	if (! @include_once ($file)) {
		echo 'Could not access '.$file."\n";
		set_include_path ($path);
		return 1;
	}
}

set_include_path ($path);

function process_values (&$values, $id_inventory) {
	/* Check empty values */
	$values['id_manufacturer'] = $values['id_manufacturer'] ? $values['id_manufacturer'] : NULL;
	$values['id_building'] = $values['id_building'] ? $values['id_building'] : NULL;
	$values['id_sla'] = $values['id_sla'] ? $values['id_sla'] : NULL;
	$values['id_product'] = $values['id_product'] ? $values['id_product'] : NULL;
	$values['id_contract'] = $values['id_contract'] ? $values['id_contract'] : NULL;
	
	foreach ($values as $field => $value) {
		if ($id_inventory)
			$values[$field] = (isset ($values[$field][0]) && $values[$field][0] == '`') ?
				get_db_value ($values[$field], 'tinventory', 'id', $id_inventory) :
				$values[$field];
		else
			$values[$field] = (isset ($values[$field][0]) && $values[$field][0] == '`') ? '' : $values[$field];
	}
}

$username = $argv[1];
$password = $argv[2];
$filepath = $argv[3];
$separator = isset ($argv[4]) ? $argv[4] : ',';

// Check credentials

if (! dame_admin ($username)) {
	echo 'Wrong user/password'."\n";
	return 1;
}

$user = (bool) get_db_value_filter ('COUNT(*)', 'tusuario',
	array ('id_usuario' => $username,
		'password' => md5 ($password)));
if (! $user) {
	echo 'Wrong user/password'."\n";
	return 1;
}

if ($filepath == "-") {
	$file = fopen ('php://stdin', 'r');
	$filepath = 'STDIN';
} else {
	$file = @fopen ($filepath, 'r');
}
if (! $file) {
	echo 'Could not open '.$filepath."\n";
	return 1;
}


$fields = array ("id","name","date_entered","date_modified","modified_user_id","created_by","description","deleted","assigned_user_id","account_type","industry","annual_revenue","phone_fax","billing_address_street","billing_address_city","billing_address_state","billing_address_postalcode","billing_address_country","rating","phone_office","phone_alternate","website","ownership","employees","ticker_symbol","shipping_address_street","shipping_address_city","shipping_address_state","shipping_address_postalcode","shipping_address_country","parent_id","sic_code","campaign_id","email_address","account_name","assigned_user_name");

$nfields = count ($fields);
while (($data = fgetcsv ($file, 0, $separator)) !== false) {
	/* Auto fill values */
	$len = count ($data);
	if ($len < $nfields)
		$data = array_pad ($data, $nfields, '');
	elseif ($len > $nfields)
		$data = array_slice ($data, NULL, $nfields);
	
	$values = array_combine ($fields, $data);
	
	if (empty ($values['name']))
		continue;
	
	// Check parent 
	if ($values["account_type"] == "")
		$values["account_type"] = "Other";
	
	print $values["name"];
	print " - ";
	print $values["account_type"];
	print "\n";
	
	$id_company_role = get_db_value ('id', 'tcompany_role', 'name', safe_input ($values["account_type"]));
	
	if ($id_company_role == ""){
			$temp = array();
			$temp["name"] = safe_input ($values["account_type"]);
			$id_company_role = process_sql_insert ('tcompany_role', $temp);
			// Created new company role
			print "[*] Created new company role ".$temp["name"]. " with ID $id_company_role \n";
	}

	$temp=array();
	
	// Check if already exists

	$id_company = get_db_value ('id', 'tcompany', 'name', safe_input ($values["name"]));
	if ($id_company == "") {
		$temp["name"] = safe_input ($values["name"]);
		$temp["address"] = safe_input ($values["billing_address_street"] . "\n". $values["billing_address_city"] . "\n". $values["billing_address_state"] . "\n". $values["billing_address_postalcode"] . "\n". $values["billing_address_country"]);
		$temp["comments"] =  safe_input ($values["description"] . "\n". $values["phone_office"] . "\n". $values["phone_alternate"] . "\n". $values["website"]);
		$temp["id_company_role"] = $id_company_role;
		process_sql_insert ('tcompany', $temp);
	}
}
fclose ($file);
?>
