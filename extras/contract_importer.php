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

// Importer for Contracts (Sugar CRM 6.3.1).

// Format expected:
// "id","date_entered","date_modified","modified_user_id","assigned_user_id","deleted","name","description","created_by","billing_date","company_signed_date","contact","contact_id","account","account_id","opportunity","opportunity_id","customer_signed_date","expiration_notice","expiry_date","start_date","status","type","amount","assigned_user_name","id_c","account_email_c","email_c","contact_email_c"


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


$fields = array ("id","date_entered","date_modified","modified_user_id","assigned_user_id","deleted","name","description","created_by","billing_date","company_signed_date","contact","contact_id","account","account_id","opportunity","opportunity_id","customer_signed_date","expiration_notice","expiry_date","start_date","status","type","amount","assigned_user_name","id_c","account_email_c","email_c","contact_email_c");

/*CREATE TABLE `tcontract` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `contract_number` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  `date_begin` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `id_company` mediumint(8) unsigned NULL default NULL,
  `id_sla` mediumint(8) unsigned NULL default NULL,
  `id_group` mediumint(8) unsigned NULL default NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
* */

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
	
	
	print $values["name"];
	print " - ";
	print $values["account"];
	print " - ";
	print $values["start_date"];
	print " - ";
	print $values["expiry_date"];
	print "\n";
	
	$id_account = get_db_value ('id', 'tcompany', 'name', safe_input ($values["account"]));
	
	$temp=array();
	
	// Check if already exists

	$id_contract = get_db_value ('id', 'tcontract', 'name', safe_input ($values["name"]));

	if (($id_contract == "") AND ($id_account != "")){
		$temp["name"] = safe_input (trim($values["name"]));
		$temp["description"] =  safe_input (trim($values["description"]));
		$temp["date_begin"] =  safe_input (trim($values["start_date"]));
		$temp["date_end"] =  safe_input (trim($values["expiry_date"]));
		$temp["id_company"] =  $id_account;
		process_sql_insert ('tcontract', $temp);
	}
}
fclose ($file);
?>
