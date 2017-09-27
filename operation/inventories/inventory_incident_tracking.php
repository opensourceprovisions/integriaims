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

global $config;

include_once ("include/functions_graph.php");

check_login ();


$id_grupo = "";
$creacion_incidente = "";

$id = (int) get_parameter ('id');
$clean_output = get_parameter('clean_output');
if (! $id) {
	require ("general/noaccess.php");
	exit;
}

echo '<h3>'.__('Tickets affecting inventory object').' #'.$id.'</h3>';

//**********************************************************************
// Tabs
//**********************************************************************

echo '<div id="tabs">';

/* Tabs list */
echo '<ul class="ui-tabs-nav">';
echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id=' . $id . '"><span>'.__('Details').'</span></a></li>';
echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/inventory_relationship&id=' . $id . '"><span>'.__('Relationships').'</span></a></li>';
echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/inventory_tracking&id=' . $id . '"><span>'.__('Tracking').'</span></a></li>';
echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/inventory_incidents&id=' . $id . '"><span>'.__('Tickets').'</span></a></li>';
echo '<li class="ui-tabs-selected"><a href="index.php?sec=inventory&sec2=operation/inventories/inventory_incident_tracking&id=' . $id . '"><span>'.__('Tickets Tracking').'</span></a></li>';

echo '</ul>';
echo '</div>';

$incidents = get_incidents_on_inventory ($id, false);

if ($incidents === false) {
	$incidents = array();
	echo __('No data available');

} 

foreach ($incidents as $key=>$incident) {
	$trackings = get_db_all_rows_field_filter ('tincident_track', 'id_incident', $incident['id_incidencia'], 'timestamp DESC');

	if ($trackings !== false) {
		
		echo '<h4>'.__('Ticket').' #'.$incident['id_incidencia'].'</h4>';
		
		$table->width = "98%";
		$table->class = 'listing';
		$table->data = array ();
		$table->head = array ();
		$table->head[1] = __('Description');
		$table->head[2] = __('User');
		$table->head[3] = __('Date');
		
		foreach ($trackings as $tracking) {
			$data = array ();
			
			$data[0] = $tracking['description'];
			$data[1] = dame_nombre_real ($tracking['id_user']);
			$data[2] = $tracking['timestamp'];
			
			array_push ($table->data, $data);
		}
		
		print_table ($table);

	} else {
		echo __('No data available');
	}
}
?>
