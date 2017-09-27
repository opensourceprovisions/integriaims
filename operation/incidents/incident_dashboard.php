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

global $config;

check_login ();

// Get start date of first incident to use it on filters
$first_start = get_db_value_sql ("SELECT UNIX_TIMESTAMP(inicio) FROM tincidencia ORDER BY inicio ASC");

if (!empty($first_start)) {
	$first_start = date ("Y-m-d", $first_start);
}

echo "<h2>" . __("Support") . "</h2>";
echo "<h4>" . __("Tickets overview");
echo integria_help ("incident_dashboard", true);
echo "</h4>";

/* Users affected by the incident */
$table = new StdClass();
$table->width = '100%';
$table->class = "overview_tiquets";
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->style = array();
$table->data = array ();
$table->style [0] = "vertical-align: top;";
$table->style [1] = "vertical-align: top";

$custom = '';

$custom_searches = get_db_all_rows_filter ("tcustom_search", array("id_user" => $config["id_user"], "section" => "incidents"));

$counter = 0;
$max_per_file = 5;

if ($custom_searches === false) {
		
        $custom .= "<tr>";
        $custom .= "<td>";
        $custom .= "<em>".__("There aren't custom search defined for this user")."</em>";
        $custom .= "</td>";
        $custom .= "</tr>";
} else {
	$custom = "<tr>";
	foreach ($custom_searches as $cs) {
		
		$c_search = get_custom_search ($cs['id'], 'incidents');
		
		if ($c_search) { 
			if ($c_search["form_values"]) {
				$filter_search = unserialize($c_search["form_values"]);
			}
		}
		
		// We omit the count for each incident due it's great impact in load speed
        // Just display the custom search name +link to that search.
        // Maybe in posterior development we can let choose the system admin to show totals for each
        // custom search or not, at this time my dashboard takes 6-8 secs with this enable, very bad user experience.
        //$count_cs = filter_incidents ($filter_search, true);
		
		$custom .="<td>";
		$custom .="<div class='custom_search'>";
		$custom .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&saved_searches=".$cs["id"]."'>".$cs["name"]."</a><br>";
		$custom .="</div>";
		$custom .="</td>";
	}
	$custom .= "</tr>";
}

$table->colspan[0][0] = 2;
$table->data[0][0] = print_container('incident_custom_search', __('Custom search'), $custom, 'open', true, '20px', '', 'no_border', count($custom_searches), '');

$groups = get_user_groups();

asort($groups);

$search_by_group = "";

// Remove group All for this filter
unset($groups[1]);

$no_groups = true;
$count = 0;
foreach ($groups as $key => $grp) {

	$incidents = get_incidents("id_grupo = $key AND estado <> 7", true);
	
	if ($incidents) {
		if ($count % 2 == 0) {
			$search_by_group .= "<tr>";
		}
			
		$search_by_group .= "<td>";
		$search_by_group .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_first_date=" . $first_start . "&search_id_group=".$key."'>";
		$search_by_group .= $grp." (".count($incidents).")";
		$search_by_group .= "</a>";
		$search_by_group .= "</td>";
			
		if ($count % 2 != 0) {
			$search_by_group .= "</tr>";
		}
		
		$count++;
		$no_groups = false;
	}	
}

if ($no_groups) {
	$search_by_group .= "<tr>";
	$search_by_group .= "<td>";
	$search_by_group .= "<em>".__("There aren't groups")."</em>";
	$search_by_group .= "</td>";
	$search_by_group .= "</tr>";
}

$left_side = print_container('incident_search_by_group', __('Search by group'), $search_by_group, 'open', true, '20px', '', 'no_border', 1, 'less_widht');

$rows = get_db_all_rows_sql ("SELECT DISTINCT(ti.id_usuario), tu.avatar 
								FROM tincidencia ti, tusuario tu 
								WHERE tu.id_usuario = ti.id_usuario 
								ORDER BY ti.id_usuario ASC");

$search_by_owner = "";

if (!$rows) {

	$search_by_owner .="<tr>";
	$search_by_owner .="<td>";
	$search_by_owner .="<em>".__("There aren't owners defined");
	$search_by_owner .="</td>";
	$search_by_owner .="</tr>";

} else {

	$count = 0;

	foreach ($rows as $key => $owners) {

		$incidents = get_incidents("id_usuario = '".$owners["id_usuario"]."' AND estado <> 7", true);

		//If no incident not show owner
		//1.- Because this user hasn't got incident assigned
		//2.- Because the operator hasn't got enough privileges to see the incidents
		if (!$incidents) {
			continue;
		}		
		
		if ($count % 4 == 0) {
			$search_by_owner .= "<tr>";
		}

		$incidents = get_incidents("id_usuario = '".$owners["id_usuario"]."' AND estado <> 7", true);
		
		$search_by_owner .= "<td>";
		$search_by_owner .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_first_date=" . $first_start . "&search_id_user=".$owners["id_usuario"]."'>";
		if($owners["avatar"]){
			$search_by_owner .= '<div class="bubble_little">' . print_image('images/avatars/' . $owners["avatar"] . '.png', true) . '</div>';
		} else {
			$search_by_owner .= '<div class="bubble_little">' . print_image('images/avatars/avatar_notyet.png', true) . '</div>';
		}
		$long_name = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $owners["id_usuario"]));
	
		$search_by_owner .= $long_name." (".count($incidents).")";
		$search_by_owner .= "</a>";
		$search_by_owner .= "</td>";
		
		if ($count % 4 == 3) {
			$search_by_owner .= "</tr>";
		}

		//Increase counter
		$count++;
	}
}

$left_side .= print_container('incident_search_by_owner', __('Search by owner'), $search_by_owner, 'open', true, '20px', '', 'no_border', 5, 'less_widht');


/**** DASHBOARD RIGHT SIDE ****/

$rows = get_db_all_rows_sql ("SELECT id, name FROM tincident_status");

$search_by_status = "";

foreach ($rows as $key => $status) {
	
	if ($key % 2 == 0) {
		$search_by_status .= "<tr>";
	}
		$incidents = get_incidents(array("estado" => $status["id"]));
		
		$search_by_status .= "<td>";
		$search_by_status .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_first_date=" . $first_start . "&search_status=".$status["id"]."'>";
		$search_by_status .= __($status["name"])." (".count($incidents).")";
		$search_by_status .= "</a>";
		$search_by_status .= "</td>";
		
	if ($key % 2 != 0) {
		$search_by_status .= "</tr>";
	}
}

$right_side = print_container('incident_search_by_status', __('Search by status'), $search_by_status, 'open', true, '20px', '', 'no_border', 1, '');

$rows = get_db_all_rows_sql ("SELECT id, name FROM tincident_type ORDER BY name ASC");

$search_by_type = "";

if (!$rows) {
	$search_by_type .="<tr>";
	$search_by_type .="<td>";
	$search_by_type .="<em>".__("There aren't ticket types defined")."</em>";
	$search_by_type .="</td>";
	$search_by_type .="</tr>";

} else {
	$count = 0;

	$rows = get_incident_types();

	foreach ($rows as $id => $name) {
		
		if ($count % 2 == 0) {
			$search_by_type .= "<tr>";
		}
		
		if ($id != -1) {
			$incidents = get_incidents("id_incident_type = ".$id." AND estado <> 7", true);
		} else {
			//Without type means type 0 and NULL
			$incidents = get_incidents("(id_incident_type = 0 OR id_incident_type IS NULL) AND estado <> 7", true);
		}
	
		$search_by_type .= "<td>";
		$search_by_type .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_first_date=" . $first_start . "&search_id_incident_type=".$id."'>";
		$search_by_type .= $name." (".count($incidents).")";
		$search_by_type .= "</a>";
		$search_by_type .= "</td>";
		
		if ($count % 2 != 0) {
			$search_by_type .= "</tr>";
		}		
		$count++;
	}
}

$right_side .= print_container('incident_search_by_type', __('Search by type'), $search_by_type, 'open', true, '20px', '', 'no_border', 1, '');

//Get open incident and count them by priority
$incidents = get_incidents("estado <> 7", false);

$rows = array();
foreach ($incidents as $inc) {

	if (isset($rows[$inc["prioridad"]])) {
		$rows[$inc["prioridad"]]++;
	} else {
		$rows[$inc["prioridad"]] = 1;
	}
}

$search_by_priority ="<tr>";

for ($i = 0; $i<=5; $i++) {
	// Change the priority code to database code
	if($i == 0) {
		$db_priority = 10;
	}
	else {
		$db_priority = $i-1;
	}

	$incident_fake = array();
	$incident_fake["prioridad"] = $db_priority;
	
	$search_by_priority .= "<td style='padding:0px;'>";
	$search_by_priority .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_first_date=" . $first_start . "&search_priority=".$db_priority."'>";
	$search_by_priority .= "<div class='div_search_priority' style='background: " . incidents_get_priority_color($incident_fake) . ";'>";

	// Search in query totals for each priority (based on DB codes, not user codes)

	$priority_count = 0;

	if (!isset($rows[$db_priority])) {
		$count_priority = 0;
	} else {
		$count_priority = $rows[$db_priority];
	}

	$search_by_priority .= $count_priority;

	$search_by_priority .= "</div></a>";
	$search_by_priority .= "</td>";
}

$search_by_priority .="</tr>";

$right_side .= print_container('incident_search_by_priority', __('Search by priority'), $search_by_priority,'open',true,true,'','no_border',5,'search_by_priority ');

$table->data[1][0] = $left_side;
$table->data[1][1] = $right_side;

print_table($table);

?>

<script type="text/javascript">
$('.incident_container h2').click(function() {
	var arrow = $('#' + $(this).attr('id') + ' img').attr('src');
	var arrow_class = $('#' + $(this).attr('id') + ' img').attr('class');
	var new_arrow = '';
	
	if (arrow_class == 'arrow_down') {
		new_arrow = arrow.replace(/_down/gi, "_right");
		$('#' + $(this).attr('id') + ' img').attr('class', 'arrow_right')
	}
	else {
		new_arrow = arrow.replace(/_right/gi, "_down");
		$('#' + $(this).attr('id') + ' img').attr('class', 'arrow_down')
	}
	
	$('#' + $(this).attr('id') + ' img').attr('src', new_arrow);
});

</script>
