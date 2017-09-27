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

// "id","date_entered","date_modified","modified_user_id","created_by","description","deleted","assigned_user_id","salutation","first_name","last_name","title","department","do_not_call","phone_home","phone_mobile","phone_work","phone_other","phone_fax","primary_address_street","primary_address_city","primary_address_state","primary_address_postalcode","primary_address_country","alt_address_street","alt_address_city","alt_address_state","alt_address_postalcode","alt_address_country","assistant","assistant_phone","lead_source","reports_to_id","birthdate","portal_name","portal_active","portal_app","campaign_id","email_address","account_name","assigned_user_name"


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

$fields = array ("id","date_entered","date_modified","modified_user_id","created_by","description","deleted","assigned_user_id","salutation","first_name","last_name","title","department","do_not_call","phone_home","phone_mobile","phone_work","phone_other","phone_fax","primary_address_street","primary_address_city","primary_address_state","primary_address_postalcode","primary_address_country","alt_address_street","alt_address_city","alt_address_state","alt_address_postalcode","alt_address_country","assistant","assistant_phone","lead_source","reports_to_id","birthdate","portal_name","portal_active","portal_app","campaign_id","email_address","account_name","assigned_user_name");

$nfields = count ($fields);
while (($data = fgetcsv ($file, 0, $separator)) !== false) {
	/* Auto fill values */
	$len = count ($data);
	if ($len < $nfields)
		$data = array_pad ($data, $nfields, '');
	elseif ($len > $nfields)
		$data = array_slice ($data, NULL, $nfields);
	
	$values = array_combine ($fields, $data);
	
	$values['fullname']= $values["first_name"]. " " . $values["last_name"];
			
	
	print $values["fullname"];
	print " - ";
	print $values["email_address"];
	print " - ";
	print $values["account_name"];
	print "\n";
	
	$id_company = get_db_value ('id', 'tcompany', 'name', safe_input ($values["account_name"]));
	
	$temp=array();
	
	// Check if already exists

/*
 * CREATE TABLE `tcompany_contact` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_company` mediumint(8) unsigned NOT NULL,
  `fullname` varchar(150) NOT NULL default '',
  `email` varchar(100) NULL default NULL,
  `phone` varchar(55) NULL default NULL,
  `mobile` varchar(55) NULL default NULL,
  `position` varchar(150) NULL default NULL,
  `description` text NULL DEFAULT NULL,
  `disabled` tinyint(1) NULL default 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
* */

	$id_contact = get_db_value ('id', 'tcompany_contact', 'fullname', safe_input ($values["fullname"]) );
	
	if (($id_contact == "") AND ($id_company != "")) {
		
		$temp["fullname"] = safe_input (trim($values['fullname']));
		$temp["email"] = safe_input (trim($values["email_address"]));
		$temp["phone"] =  safe_input (trim($values["phone_home"]));
		$temp["mobile"] = safe_input (trim($values["phone_mobile"]));
		$temp["description"] = safe_input (trim($values["description"]));
		$temp["position"] = safe_input (trim($values["title"]));
		$temp["id_company"] = $id_company;
		process_sql_insert ('tcompany_contact', $temp);
	}
}
fclose ($file);
?>
