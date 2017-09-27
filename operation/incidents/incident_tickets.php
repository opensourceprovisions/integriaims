<?php
// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (check_login () != 0) {
	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access","Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

$id_incident = (int) get_parameter ('id');
$incidents = incidents_get_incident_childs ($id_incident, false);


if (count ($incidents) == 0) {
	echo ui_print_error_message (__('There\'s no tickets associated to this ticket'), '', true, 'h3', true);
}
else {
	
	$table = new StdClass();
	$table->class = 'listing';
	$table->width = '100%';
	$table->head = array ();
	$table->head[0] = __('ID');
	$table->head[1] = __('Name');
	$table->head[2] = __('Group');
	$table->head[3] = __('Status');
	$table->head[4] = __('Creator');
	$table->head[5] = __('Owner');
	$table->size = array ();
	$table->size[0] = '40px';
	$table->data = array();
	
	$data = array();
	foreach ($incidents as $incident) {
		//Print incident link if not ajax, if ajax link to js funtion to replace parent
		$link = "index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_incidencia"];
		$data[0] = '<strong><a href="'.$link.'">#'.$incident['id_incidencia'].'</a></strong>';
		$data[1] = '<strong><a href="'.$link.'">'.$incident['titulo'].'</a></strong>';
		$data[2] = get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);
		$data[3] = get_db_value ("name", "tincident_status", "id", $incident['estado']);
		$data[4] = $incident['id_creator'];
		$data[5] = $incident['id_usuario'];
		array_push($table->data, $data);
	}
	
	print_table ($table);
}


?>
