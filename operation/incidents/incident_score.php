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

// Load global vars

$accion = "";
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

// Take input parameters
$id = (int) get_parameter ('id');
$score = get_parameter ('score');

if (is_numeric($id))
	$incident = get_db_row("tincidencia", "id_incidencia", $id);

// Security checks
if (!isset($incident)){
	echo ui_print_error_message (__("Invalid ticket ID"), '', true, 'h3', true);
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Ticket score hack", "Trying to access ticket score on a invalid ticket");
	no_permission();
	return;
}

if ($incident["id_creator"] != $config["id_user"]){
	echo ui_print_error_message (__("Non authorized ticket score review"), '', true, 'h3', true);
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Ticket score hack", "Trying to access ticket score on a non-authorship ticket");
	no_permission();
	return;
}

if (($incident["estado"] !=6) AND ($incident["estado"] != 7)){
	echo ui_print_error_message (__("Ticket cannot be scored until be closed"), '', true, 'h3', true);
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Ticket score hack", "Trying to access ticket score before closing ticket");
	no_permission();
	return;
}

// Score it !
$sql = "UPDATE tincidencia SET score = $score WHERE id_incidencia = $id";
process_sql ($sql);

echo "<h1>".__("Ticket scoring")."</h1>";
echo "<br><br>";
echo __("Thanks for your feedback, this help us to keep improving our job");


