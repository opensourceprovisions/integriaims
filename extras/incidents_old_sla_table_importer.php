<?php

# INTEGRIA - the ITIL Management System
# http://integria.sourceforge.net
# ==================================================
# Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
# http://www.artica.es  <info@artica.es>

# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

// Table tincident_sla_graph migration


$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc < 2) {
	echo 'Usage: '.$argv[0].' username password'."\n";
	return 1;
}

$dir = realpath (dirname (__FILE__).'/..');
$path = get_include_path ();
set_include_path ($path.PATH_SEPARATOR.$dir);

$libs = array(
		'include/config.php',
		'include/functions.php',
		'include/functions_db.php'
	);
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

// Check credentials
$login = (bool) get_db_value_filter ('COUNT(*)', 'tusuario', array('id_usuario' => $username, 'password' => md5 ($password)));
if (! $login) {
	echo 'Wrong user/password'."\n";
	return 1;
}
if (! dame_admin ($username)) {
	echo 'The user must be admin'."\n";
	return 1;
}

function fill_new_sla_table () {

	echo "Filling the table 'tincident_sla_graph_data'...\n";

	$last_values = array();

	$sql = "SELECT id_incident, utimestamp, value
			FROM tincident_sla_graph
			ORDER BY utimestamp ASC";
	$new = true;
	while ($data = get_db_all_row_by_steps_sql($new, $result_sla, $sql)) {
		$new = false;

		$id_incident = $data["id_incident"];
		$value = $data["value"];
		$utimestamp = $data["utimestamp"];

		if ( !isset($last_values[$id_incident]) || (isset($last_values[$id_incident]) && $last_values[$id_incident] != $value) ) {
			$last_values[$id_incident] = $value;
			$values = array(
					"id_incident" => $id_incident,
					"utimestamp" => $utimestamp,
					"value" => $value
				);
			process_sql_insert("tincident_sla_graph_data", $values);
		}
	}

	echo "Filling the table 'tincident_sla_graph_data'... DONE\n";
}

function drop_old_sla_table () {
	echo "Deleting the table 'tincident_sla_graph'...\n";

	$sql = "DROP TABLE IF EXISTS tincident_sla_graph";
	process_sql($sql);

	echo "Deleting the table 'tincident_sla_graph'... DONE\n";
}

fill_new_sla_table();
drop_old_sla_table();

?>
