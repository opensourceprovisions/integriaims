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

$fields = array ('name',
	'description',
	'serial_number',
	'part_number',
	'comments',
	'confirmed',
	'cost',
	'ip_address',
	'id_contract',
	'id_product',
	'id_sla',
	'id_manufacturer',
	'id_building',
	'id_parent',
	'generic_1',
	'generic_2',
	'generic_3',
	'generic_4',
	'generic_5',
	'generic_6',
	'generic_7',
	'generic_8');
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
	
	/* Check parent */
	if (is_int ($values['id_parent']))
		$id_parent = (int) get_db_value ('id', 'tinventory', 'id', $values['id_parent']);
	else
		$id_parent = (int) get_db_value ('id', 'tinventory', 'name', (string) $values['id_parent']);
	$values['id_parent'] = $id_parent ? $id_parent : NULL;
	
	/* Check if the inventory item already exists */
	$id_inventory = (int) get_db_value_filter ('id', 'tinventory', 
		array ('name' => $values['name'],
			'id_parent' => $values['id_parent']));
	process_values ($values, $id_inventory);
	if ($id_inventory) {
		process_sql_update ('tinventory',
			$values,
			array ('id' => $id_inventory));
		echo 'Updated inventory "'.$values['name'].'"';
	} else {
		process_sql_insert ('tinventory',
			$values);
		echo 'Inserted inventory "'.$values['name'].'"';
	}
	
	echo "\n";
}
fclose ($file);
?>
