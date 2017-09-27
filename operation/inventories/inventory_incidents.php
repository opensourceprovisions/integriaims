<?php 
// Integria 1.1 - http://integria.sourceforge.net
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

if (check_login () != 0) {
 	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

if (! give_acl ($config['id_user'], get_inventory_group ($id), 'IR')) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
	include ("general/noaccess.php");
	exit;
}

//**********************************************************************
// Tabs
//**********************************************************************
if(!isset($inventory_name)){
	$inventory_name = '';
}
print_inventory_tabs('incidents', $id, $inventory_name);

echo '<div class="divresult" id="">';
$table = new stdClass;
$table->width = '100%';
$table->class = 'listing';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Title');
$table->head[1] = __('Date');
$table->head[2] = __('Priority');
$table->head[3] = __('Status');
$table->head[4] = __('Assigned user');
$table->head[5] = __('View');

$incidents = get_incidents_on_inventory ($id, false);

foreach ($incidents as $incident) {
	$data = array ();
	
	if (! give_acl ($config['id_user'], $incident['id_grupo'], 'IR'))
		continue;
	
	$data[0] = $incident['titulo'];
	$data[1] = $incident['inicio'];
	$data[2] = print_priority_flag_image ($incident['prioridad'], true);
	$data[3] = get_db_value ('name', 'tincident_status', 'id', $incident['estado']);
	$user_avatar = get_db_value ('avatar', 'tusuario', 'id_usuario', $incident['id_usuario']);
	$data[4] = print_user_avatar ($incident['id_usuario'], true, true);
	$data[4] .= " ".$incident['id_usuario'];
	$data[5] = '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.
		$incident['id_incidencia'].'"><img src="images/zoom.png" /></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);
echo '</div>';

echo '<div class="divform" id="">';
echo "<table class='search-table'><tr><td>";
echo print_html_report_button ("index.php?sec=inventory&sec2=operation/reporting/incidents_html&search_id_inventory=$id", __('HTML report'), "submit-incident_report", "target='_blank'");
echo "</td></tr></table>";
echo '</div>';

?>
