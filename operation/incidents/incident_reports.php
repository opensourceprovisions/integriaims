<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

check_login ();

require_once ('include/functions_incidents.php');

$date_from = (int) get_parameter("search_from_date");
$date_start = (string) get_parameter("search_first_date");
$date_end = (string) get_parameter("search_last_date");
$show_list = (bool) get_parameter('show_list', 0);
$show_stats = (bool) get_parameter('show_stats', 0);
$id_search = (int) get_parameter('saved_searches');
$search = (string) get_parameter('search');
$pure = (bool) get_parameter('pure',0);

		
echo "<h2>" .__('Support')."</h2>";
echo "<h4>" .__('Ticket reports');
	if(!$pure){
		echo integria_help ("incident_report", true);
	}
	echo "<div id='button-bar-title'><ul>";
		if(!$pure){
			echo "<li><a href='index.php?sec=incidents&sec2=operation/incidents/incident_reports&saved_searches=$id_search&show_stats=$show_stats&show_list=$show_list&pure=1'>".print_image ("images/html_tabs.png", true, array("title" => __("HTML")))."</a></li>";
		} else {
			echo "<li><a href='index.php?sec=incidents&sec2=operation/incidents/incident_reports&saved_searches=$id_search&show_stats=$show_stats&show_list=$show_list&pure=0'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		}
	echo "</ul></div>";
echo "</h4>";

$table_search = new stdClass;
$table_search->width = '100%';
$table_search->class = 'search-table';
$table_search->size = array ();
$table_search->style = array ();
$table_search->colspan = array ();
$table_search->rowspan = array ();
$table_search->colspan[4][0] = 4;
$table_search->data = array ();

$sql = sprintf ('SELECT id, name FROM tcustom_search
	WHERE id_user = "%s"
	AND section = "incidents"
	ORDER BY name',
	$config['id_user']);
$table_search->data[0][0] = print_select_from_sql ($sql, 'saved_searches', $id_search, '', __('Select'), 0, true, false, true, __('Custom searches'));

$table_search->data[1][0] = print_checkbox_extended ('show_list', 1, $show_list, false, '', '', true, __('Show list'));
$table_search->data[2][0] = print_checkbox_extended ('show_stats', 1, $show_stats, false, '', '', true, __('Show stats'));

$table_search->data[4][0] = print_submit_button (__('Search'), 'search', false, 'class="sub search"', true);
$table_search->colspan[4][0] = 4;
if(!$pure){
	echo "<div class= 'divform'>";
		echo '<form method="post">';
		print_table ($table_search);
		echo '</form>';
	echo '</div>';
}
if($pure){
	echo "<div class='divresult' style='width: 98%;'>";
} else {
	echo "<div class='divresult'>";
}
if (isset($search)) {	
	$custom_search = get_custom_search ($id_search, 'incidents');
	if ($custom_search) {		
		if ($custom_search["form_values"]) {
			
			$filter = unserialize($custom_search["form_values"]);
			$filter_form = $filter;
		}
	}
	else {
		echo ui_print_error_message (__('Could not load custom search'), '', true, 'h3', true);
	}
	
	include("incident_statistics.php");
	
	if ($show_list) {
		$filter['first_date'] = (string) get_parameter ('search_first_date', '');
 		$filter['last_date'] = (string) get_parameter ('search_last_date', '');
		$filter['limit'] = 0;
		$incidents = filter_incidents ($filter);
		unset($filter['limit']);

		$statuses = get_indicent_status ();
		$resolutions = get_incident_resolutions ();
		if ($incidents) {
			$table = new StdClass();
			$table->class = 'listing';
			$table->width = "100%";
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
		
			foreach ($incidents as $incident) {
				$data = array ();
				
				$link = "index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_incidencia"];
				
				$data[0] = '<strong><a href="'.$link.'">#'.$incident['id_incidencia'].'</a></strong></td>';
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

			print_table ($table);
		}
	}
}
echo "</div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Warning")."' id='custom_search'></div>";
?>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>

<script type="text/javascript">

add_ranged_datepicker ("#text-search_first_date", "#text-search_last_date", null);

$(document).ready (function () {
	check_custom_search();
});

function check_custom_search() {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/incidents&check_custom_search=1",
		dataType: "json",
		success: function (data) {
			
			if (data != false) {
				$("#custom_search").html (data);
				$("#custom_search").show ();

				$("#custom_search").dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 520,
						height: 180
				});
				$("#custom_search").dialog('open');
			}
		}
	});
}
</script>
