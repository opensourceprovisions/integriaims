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

require_once ('include/functions_incidents.php');

$filter = array ();

$custom_search = (int) get_parameter ('custom_search');
$show_list = (bool) get_parameter('show_list', 0);
$show_stats = (bool) get_parameter('show_stats', 0);

$simple_mode = true;
if ($show_stats) {
	$simple_mode = false;
}
if ($custom_search) {
	$search = get_custom_search ($custom_search, 'incidents');
	if ($search && $search["form_values"]) { 
		$filter = unserialize($search["form_values"]);
	}

	//Get report data range
	$data_start = (string) get_parameter ('search_first_date');
	$data_end = (string) get_parameter ('search_last_date');
	if ($data_start && $data_end) {
		$filter['first_date'] = $data_start;
		$filter['last_date'] = $data_end;
	}

	$statuses = get_indicent_status ();
	$resolutions = get_incident_resolutions ();

	echo '<div style="width: 950px;">';
	echo '<h1>'.__('Tickets report').'</h1>';

	$output_full = "";

	$output = '';

	$output = "<table>";

	$output .= "<tr>";
	$output .= "<td>";
	$output .= "<strong>".__('Containing').': </strong>'.$filter['string'];
	$output .= "</td>";
	$output .= "<td>";
	if ($filter['status'] == -10) {
		$status_text = __("Not closed");
	} else {
		$status_text = $statuses[$filter['status']];
	}

	$output .= "<strong>".__('Status').': </strong>'.$status_text;
	$output .= "</td>";	
	$output .= "<td>";
	$output .= "<strong>".__('Priority').': </strong>'.print_priority_flag_image ($filter['priority'], true);
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('Group').': </strong>'.get_db_value ('nombre', 'tgrupo', 'id_grupo', $filter['id_group']);
	$output .= "</td>";			
	$output .= "<td>";
	$output .= "<strong>".__('Product').': </strong>'.get_db_value ('name', 'tkb_product', 'id', $filter['id_product']);
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('Company').': </strong>'.get_db_value ('name', 'tinventory', 'id', $filter['id_company']);
	$output .= "</td>";
	$output .= "</tr>";

	$output .= "<tr>";
	$output .= "<td>";
	$output .= "<strong>".__('Inventory').': </strong>'.get_inventory_name ($filter['id_inventory']);
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('Serial number').': </strong>'.$filter['serial_number'];
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('Building').': </strong>'.get_db_value ('name', 'tbuilding', 'id', $filter['id_building']);
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('User').': </strong>'.$filter['id_user'];
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('Ticket type').': </strong>'.get_db_value ('name', 'tincident_type', 'id', $filter['id_incident_type']);
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('From').': </strong>'.$filter['first_date'];
	$output .= "</td>";
	$output .= "<td>";
	$output .= "<strong>".__('To').': </strong>'.$filter['last_date'];
	$output .= "</td>";
	$output .= "</tr>";

	$output .= "</table>";
} else {
 	$filter['string'] = (string) get_parameter ('search_string');
 	$filter['status'] = (int) get_parameter ('status', -10 ); // By default, not closed
 	$filter['priority'] = (int) get_parameter ('search_priority', -1);
 	$filter['id_group'] = (int) get_parameter ('search_id_group', 1);
 	$filter['status'] = (int) get_parameter ('search_status', -10); // by default not closed
 	$filter['id_product'] = (int) get_parameter ('search_id_product');
 	$filter['id_company'] = (int) get_parameter ('search_id_company');
 	$filter['id_inventory'] = (int) get_parameter ('search_id_inventory');
 	$filter['serial_number'] = (string) get_parameter ('search_serial_number');
 	$filter['id_building'] = (int) get_parameter ('search_id_building');
 	$filter['sla_fired'] = (bool) get_parameter ('search_sla_fired');
 	$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
 	$filter['id_user'] = (string) get_parameter ('search_id_user', '');
 	$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
 	$filter['id_user'] = (string) get_parameter ('search_id_user', '');
 	$filter['first_date'] = (string) get_parameter ('search_first_date');
 	$filter['last_date'] = (string) get_parameter ('search_last_date');
}

if (!$output) {
	$output = __('All tickets');
}

$container_title = __("Ticket report parameters");
echo print_container('incident_report_parameters', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_incident_report_parameters ");  

if ($show_list) {

	$table->class = 'listing';
	$table->width = "95%";
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->head = array ();
	$table->head[0] = __('ID');
	$table->head[1] = __('SLA');
	$table->head[2] = __('% SLA');
	$table->head[3] = __('Ticket');
	$table->head[4] = __('Group')."<br><em>".__("Company")."</em>";
	$table->head[5] = __('Status')."<br /><em>".__('Resolution')."</em>";
	$table->head[6] = __('Priority');
	$table->head[7] = __('Updated')."<br /><em>".__('Started')."</em>";
	$table->head[8] = __('Responsible');
	$table->data = array ();

	$filter['limit'] = 0;
	$incidents = filter_incidents ($filter);
	unset($filter['limit']);

	if ($incidents) {
		//print_incidents_stats_simply ($incidents, false, $simple_mode);
		//echo '<div style="clear: both"></div>';
	}

	if ($incidents === false) {
		$table->colspan[0][0] = 9;
		$table->data[0][0] = __('Nothing was found');
		$incidents = array ();
	}

	foreach ($incidents as $incident) {
		$data = array ();
		
		$data[0] = '#'.$incident['id_incidencia'];
		$data[1] = '';
		if ($incident["affected_sla_id"] != 0)
			$data[1] = '<img src="images/exclamation.png" />';
		if ($incident["affected_sla_id"] != 0)
		$data[2] = format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
		else
		$data[2] = "";
		$data[3] = '<a href="'.$config["base_url"].'/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$incident['id_incidencia'].'">'.
			$incident['titulo'].'</a>';
		$data[4] = get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);
			
		if ($config["show_creator_incident"] == 1){	
			$id_creator_company = get_db_value ("id_company", "tusuario", "id_usuario", $incident["id_creator"]);
			if($id_creator_company != 0) {
				$company_name = (string) get_db_value ('name', 'tcompany', 'id', $id_creator_company);	
				$data[4].= "<br><span style='font-style:italic'>$company_name</span>";
			}
		}
		
		$resolution = isset ($resolutions[$incident['resolution']]) ? $resolutions[$incident['resolution']] : __('None');
		
		$data[5] = '<strong>'.$statuses[$incident['estado']].'</strong><br /><em>'.$resolution.'</em>';
		$data[6] = print_priority_flag_image ($incident['prioridad'], true);
		$data[7] = human_time_comparation ($incident["actualizacion"]);
		$data[7] .= '<br /><em>';
		$data[7] .=  human_time_comparation ($incident["inicio"]);
		$data[7] .= '</em>';
		
		$data[8] = $incident['id_usuario'];
		
		array_push ($table->data, $data);
	}

	echo '<h2>'.__('Ticket list').'</h2>';

	print_table ($table);
}

?>
