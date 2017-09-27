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

include_once('include/functions_crm.php');
include_once('include/functions_incidents.php');
$id = (int) get_parameter ('id');

$contact = get_db_row ('tcompany_contact', 'id', $id);

$read = check_crm_acl ('other', 'cr', $config['id_user'], $contact['id_company']);
if (!$read) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contact tickets without permission");
	include ("general/noaccess.php");
	exit;
}

$email = safe_output($contact["email"]);
$email = trim($email);
$email = safe_input($email);

$incidents = incidents_get_by_notified_email ($email);

if (!$incidents) {
    echo ui_print_error_message (__("This contact doesn't have any ticket associated"), '', true, 'h3', true);
} else {

	$table->class = "listing";
	$table->width = "99%";
	$table->head[0] = __("ID");
	$table->head[1] = __("Ticket");
	$table->head[2] = __("Status");
	$table->head[3] = __("Priority");
	$table->head[4] = __("Updated");
	$table->data = array();

	foreach ($incidents as $inc) {
		$data = array();

		if (give_acl($config["id_user"], 0, "IR")) {
			$link_start = '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$inc["id_incidencia"].'">';
			$link_end = '</a>';
		} else {
			$link_start = "";
			$link_end = "";
		}

		$data[0] = $link_start."#".$inc["id_incidencia"].$link_end;
		$data[1] = $link_start.$inc["titulo"].$link_end;
	
		$status = get_db_value("name", "tincident_status", "id", $inc["estado"]);

		$data[2] = $status;
		$data[3] = print_priority_flag_image ($inc['prioridad'], true);
		$data[4] = human_time_comparation ($inc["actualizacion"]); 
	
		array_push($table->data, $data);
	}

	print_table($table);
}
?>
