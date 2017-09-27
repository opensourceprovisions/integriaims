<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
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

if (!isset($read_permission)) {
	$read_permission = check_crm_acl ('lead', 'cr', $config['id_user'], $id);
	if (!$read_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead");
		include ("general/noaccess.php");
		exit;
	}
}

$sql = "SELECT * FROM tlead_history WHERE id_lead = $id ORDER BY timestamp DESC";

$activities = get_db_all_rows_sql ($sql);
$activities = print_array_pagination ($activities, "index.php?sec=customers&sec2=operation/leads/lead_detail&id=$id&op=history");

if ($activities !== false) {	
	if (sizeof($activities) == 0){
		echo "<h3>".__("There is no history")."</h3>";
	} else {

		unset ($table);
		$table = new stdClass();
		$table->width = "95%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->head = array ();
	
		$table->head[0] = __("Description");
		$table->head[1] = __("Timestamp");
		$table->head[2] = __("User ID");
		
		foreach ($activities as $activity) {
			$data = array ();

			$timestamp = $activity["timestamp"];
			$nota = $activity["description"];
			$id_usuario_nota = $activity["id_user"];

			$data[0] = $nota;
			$data[1] = human_time_comparation ($timestamp);
			$data[2] = $id_usuario_nota;

			array_push ($table->data, $data);
		}

		print_table ($table);
	}
} 
?>
