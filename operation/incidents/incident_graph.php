<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once ("include/functions_graph.php");

global $config;

check_login ();

echo "<h1>";
echo __("Ticket graph");
echo "<div id='button-bar-title'>";
echo "<ul>";
echo "<li>";
echo "<a id='search_form_submit' href='#'>".print_image("images/go-previous.png", true, array("title" => __("Back to search")))."</a>";
echo "</li>";
echo "</ul>";
echo "</div>";
echo "</h1>";

$incidents = filter_incidents ($filter);
if (empty($incidents))
	$incidents = array();

$incidents_by_user = array();

foreach ($incidents as $incident) {
	$row = array();
	
	$user_name = get_db_value('nombre_real', 'tusuario',
		'id_usuario', $incident['id_creator']);
	
	$row['id_creator'] = $incident['id_creator'];
	$row['id_incident'] = $incident['id_incidencia'];
	$row['incident_name'] = safe_output($incident['titulo']);
	$row['user_name'] = safe_output($user_name);
	$row['workunits'] = get_incident_count_workunits($incident['id_incidencia']);
	$row['hours'] = get_incident_workunit_hours($incident['id_incidencia']);
	$row['files'] = get_number_files_incident($incident['id_incidencia']);
	
	$incidents_by_user[] = $row;
}

/* Add a form to carry filter between statistics and search views */
echo '<form id="search_form" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_search&option=search" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}
print_input_hidden ("offset", get_parameter("offset"));
echo "</form>";


if (empty($incidents_by_user)) {
	ui_print_error_message(__('There are not tickets with this filter.'));
} else {
	print_bubble_incidents_per_user_graph($incidents_by_user);
}
?>
