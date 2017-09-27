<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

check_login ();

if (defined ('AJAX')) {
	$create_custom_search = get_parameter('create_custom_search');
	$search_name = get_parameter('search_name');
	$form_values = get_parameter('form_values');

	$filter['inverse_filter'] = $form_values['search_inverse_filter'];
	$filter['string'] = $form_values['search_string'];
	$filter['status'] = $form_values['search_status'];
	$filter['priority'] = $form_values['search_priority'];
	$filter['id_group'] = $form_values['search_id_group'];
	$filter['resolution'] = $form_values['search_resolution'];
	$filter['id_product'] = $form_values['search_id_product'];
	$filter['id_company'] = $form_values['search_id_company'];
	$filter['id_inventory'] = $form_values['id_inventory'];
	$filter['id_incident_type'] = $form_values['search_id_incident_type'];
	$filter['id_user'] = $form_values['search_id_user'];
	$filter['id_creator'] = $form_values['search_id_creator'];
	$filter['editor'] = $form_values['search_editor'];
	$filter['closed_by'] = $form_values['search_closed_by'];
	$filter['order_by'] = $form_values['search_order_by'];
	$filter['from_date'] = $form_values['search_from_date'];
	$filter['first_date'] = $form_values['search_first_date'];
	$filter['last_date'] = $form_values['search_last_date'];
	$filter['group_by_project'] = $form_values['search_group_by_project'];
	$filter['sla_state'] = $form_values['search_sla_state'];
	$filter['id_task'] = $form_values['search_id_task'];
	$filter['left_sla'] = $form_values['search_left_sla'];
	$filter['right_sla'] = $form_values['search_right_sla'];
	$filter['show_hierarchy'] = $form_values['show_hierarchy'];
	$filter['medals'] = $form_values['medals'];
	$filter['parent_name'] = (string) get_parameter ('parent_name', '');
	
	$type_fields = incidents_get_type_fields ($filter['id_incident_type']);

	if ($type_fields) {
		foreach ($type_fields as $type_field) {
			$filter['type_field_'.$type_field['id']] = $form_values['search_type_field_'.$type_field['id']];
		}
	}

	$result = create_custom_search ($search_name, 'incidents', $filter);
}

global $config;

$option = get_parameter("option", "search");

$filter = array ();
$filter['inverse_filter'] = (bool) get_parameter('search_inverse_filter');
$filter['string'] = (string) get_parameter ('search_string');
$filter['priority'] = (int) get_parameter ('search_priority', -1);
$filter['id_group'] = (int) get_parameter ('search_id_group', 1);
$filter['status'] = (int) get_parameter ('search_status', -10);
$filter['resolution'] = (int) get_parameter ('search_resolution', -1);
$filter['id_product'] = (int) get_parameter ('search_id_product');
$filter['id_company'] = (int) get_parameter ('search_id_company');
$filter['id_inventory'] = (int) get_parameter ('id_inventory');
$filter['serial_number'] = (string) get_parameter ('search_serial_number');
$filter['sla_fired'] = (bool) get_parameter ('search_sla_fired');
$filter['id_incident_type'] = (int) get_parameter ('search_id_incident_type');
$filter['id_user'] = (string) get_parameter ('search_id_user', '');
$filter['id_creator'] = (string) get_parameter ('search_id_creator', '');
$filter['editor'] = (string) get_parameter ('search_editor', '');
$filter['closed_by'] = (string) get_parameter ('search_closed_by', '');
$filter['order_by'] = (string) get_parameter ('search_order_by', '');
$filter['from_date'] = (string) get_parameter('search_from_date', '');
$filter['first_date'] = (string) get_parameter('search_first_date', '');
$filter['last_date'] = (string) get_parameter('search_last_date', '');
$filter['group_by_project'] = (bool) get_parameter('search_group_by_project');
$filter['sla_state'] = (int) get_parameter ('search_sla_state');
$filter['id_task'] = (int) get_parameter('search_id_task');
$filter['left_sla'] = (int) get_parameter ('search_left_sla');
$filter['right_sla'] = (int) get_parameter ('search_right_sla');
$filter['show_hierarchy'] = (bool) get_parameter('search_show_hierarchy');
$filter['medals'] = (int) get_parameter ('search_medals', 0);
$filter['parent_name'] = (string) get_parameter ('parent_name', '');

$type_fields = incidents_get_type_fields ($filter['id_incident_type']);

if ($type_fields) {
	foreach ($type_fields as $type_field) {
		$filter['type_field_'.$type_field['id']] = (string) get_parameter ('search_type_field_'.$type_field['id']);
	}
}

switch ($option) {
	case "search":
		include("incident_search_logic.php");
		break;
	case "stats":
		include("incident_statistics.php");
		break;
	case 'graph':
		include("incident_graph.php");
		break;
	default:
		break;
}
//echo "</div>";

?>
<script type="text/javascript" src="include/js/integria_inventory.js"></script>
<script>

//Configure some actions to send forms stats
$(document).ready(function () {

	hide_all_rows();
	
	$("#search_form_submit").click(function (event) {
		event.preventDefault();
		$("#search_form").submit();
	});
	
	$("#html_report_submit").click(function (event) {
		event.preventDefault();
		$("#html_report_form").submit();
	});
	
	$("#pdf_report_submit").click(function (event) {
		event.preventDefault();
		$("#pdf_report_form").submit();
	});	
});

function hide_all_rows() {
	$("tr[class$='-task']").hide();
}

function check_rows(id_task) {
	if ($("tr[class$='"+id_task+"-task']").css('display') != "none") {
		$("tr[class$='"+id_task+"-task']").hide();
	} else {
		$("tr[class$='"+id_task+"-task']").show();
	}
} 

</script>
