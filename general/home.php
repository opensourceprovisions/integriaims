<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include $config["homedir"]."/include/functions_graph.php";

$noinfo = 1;

if (!isset($config["id_user"]))
	$config["id_user"] = $_SESSION['id_usuario'];

///////////////
//Get queries to know if there is info or not
//////////////

//AGENDA
$now = date('Y-m-d', strtotime("now"));
$now3 = date('Y-m-d', strtotime("now + 10 days"));
$agenda = get_db_sql ("SELECT COUNT(*) FROM tagenda WHERE  (id_user ='".$config["id_user"]."' OR public = 1) AND timestamp > '$now' AND timestamp < '$now3'");

$agenda  += get_db_sql ("SELECT COUNT(tproject.name) FROM trole_people_project, tproject WHERE trole_people_project.id_user = '".$config["id_user"]."' AND trole_people_project.id_project = tproject.id AND tproject.end >= '$now' AND tproject.end <= '$now3'");

$agenda += get_db_sql ("SELECT COUNT(ttask.name) FROM trole_people_task, ttask WHERE trole_people_task.id_user = '".$config["id_user"]."' AND trole_people_task.id_task = ttask.id AND ttask.end >= '$now' AND ttask.end <= '$now3'");	


//TODO
$todo = get_db_sql ("SELECT COUNT(*) FROM ttodo WHERE assigned_user = '".$config["id_user"]."'");

//PROJECTS
$projects = projects_active_user ($config["id_user"]);	

//INCIDENTS
$incidents = incidents_active_user ($config["id_user"]);

$info = false;

if ($news || $agenda || $todo || $projects || $incidents) {
	$info = true;
}

if ($info) {
	
	echo "<div style='width:100%;'>";
	echo '<table class="landing_table" width=100%>';
	echo "<tr>";
	// LEFT SIDE
	echo "<td>";
	
	// ==============================================================
	// Show Incident items
	// ==============================================================

	$incidents_home = '';
	if ($incidents > 0){
		$sql_2 = "SELECT * FROM tincidencia WHERE (id_creator = '".$config["id_user"]."' OR id_usuario = '".$config["id_user"]."') AND estado IN (1,2,3,4,5) ORDER BY actualizacion DESC limit 5";
		
		$result_2 = mysql_query($sql_2);
		if ($result_2){
			$incidents_home .= "<table width=100% class='landing_incidents listing'>";
			$incidents_home .= "<tr><th>"._("Status")."</th><th>".__("Priority")."</th><th>".__("Updated")."</th><th>".__("Ticket")."</th><th>".__("Last WU")."</th></tr>";
		}
		while ($row_2 = mysql_fetch_array($result_2)){
			$idi = $row_2["id_incidencia"];
			$incidents_home .= "<tr><td>";
			$incidents_home .= render_status($row_2["estado"]);
			$incidents_home .= "<td>";
			$incidents_home .=print_priority_flag_image ($row_2['prioridad'], true);
			$incidents_home .= "<td>";
			$incidents_home .= human_time_comparation ($row_2["actualizacion"]);
			$incidents_home .= "<td>";
			
			$incidents_home .= "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=$idi'>";

			$incidents_home .= $row_2["titulo"];
			$incidents_home .= "</b></a>";
			$incidents_home .= "</td>";
			$incidents_home .= "<td style='font-size: 10px'>";
			$last_wu = get_incident_lastworkunit ($idi);
			$incidents_home .= $last_wu["id_user"];

			$incidents_home .= "</td></tr>";
		}
		if (isset($row_2))
			$incidents_home .= "</table>";
	} else {
		$incidents_home .= "<div class='landing_empty'>";
		$incidents_home .= __("There aren't active incidents");
		$incidents_home .= "</div>";		
	}
	
	$much_more = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard'>";
	$much_more .= "<img class='much_more' src='images/flecha_dcha.png' title='" . __('Incidents') . "'>";
	$much_more .= "</a>";
	
	$subtitle = "<span class='landing_subtitle'>";
	$subtitle .= __('Total active incidents').": ".incidents_active_user ($config["id_user"]);
	$subtitle .= "</span>";
	
	echo print_container_div('incidents_home', __('Incidents') . $subtitle . $much_more, $incidents_home, 'no', false, true,'','','','',"height: 400px;");
	
	
	// ==============================================================
	// Show Agenda items
	// ==============================================================

	$agenda_home = '';
	if ($agenda > 0){

		$time_array = array();
		$text_array = array();
		$type_array = array();
		$data = array();
		$count = 0;
		
		//Get agenda events
		$sql_2 = "SELECT * FROM tagenda WHERE (id_user ='".$config["id_user"]."' OR public = 1) AND timestamp > '$now' AND timestamp < '$now3' ORDER BY timestamp ASC";
		$result_2 = get_db_all_rows_sql($sql_2);
			
		foreach ($result_2 as $r) {
			$time_array[$count] = $r["timestamp"];
			$text_array[$count] = $r["title"];
			$type_array[$count] = "agenda";
			$count++;
		}

		// Search for Project end in this date
		$sql = "SELECT tproject.name as pname, tproject.end as pend, tproject.id as idp FROM trole_people_project, tproject WHERE trole_people_project.id_user = '".$config["id_user"]."' AND trole_people_project.id_project = tproject.id AND tproject.end >= '$now' AND tproject.end <= '$now3' group by idp";
		$res = mysql_query ($sql);
		while ($row=mysql_fetch_array ($res)){
			$pname = $row["pname"];
			$idp = $row["idp"];
			
			$content = "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=$idp'>".$pname."</a>";
			
			$time_array[$count] = $row["pend"];
			$text_array[$count] = $content;
			$type_array[$count] = "project";
			$count++;
		}

		// Search for Task end in this date
		$sql = "SELECT ttask.name as tname, ttask.end as tend, ttask.id as idt FROM trole_people_task, ttask WHERE trole_people_task.id_user = '".$config["id_user"]."' AND trole_people_task.id_task = ttask.id AND ttask.end >= '$now' AND ttask.end <= '$now3' group by idt";
		$res = mysql_query ($sql);
		while ($row=mysql_fetch_array ($res)){
			$tname = $row["tname"];
			$idt = $row["idt"];
			$tend = $row["tend"];
			
			$content = "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_task=$idt&operation=view'>".$tname."</a>";
			
			$time_array[$count] = $row["tend"];
			$text_array[$count] = $content;
			$type_array[$count] = "task";
			$count++;
		}
		
		//Sort time array and print only first five entries :)
		asort($time_array);
	
		$agenda_home .= "<table class='landing_incidents listing' width=100%>";
		$agenda_home .= "<tr><th>".__("Type")."</th><th>".__("Title")."</th><th>".__("Deadline")."</th></tr>";

		$print_counter = 0;
		foreach ($time_array as $key => $time) {
			
			$type_name = "";
			switch ($type_array[$key]) {
				case "agenda":
					$type_name = __("Agenda event");
					break;
				case "project":
					$type_name = __("Project end");
					break;
				case "task":
					$type_name = __("Task end");
					break;
			}
			
			$agenda_home .= "<tr>";
			$agenda_home .= "<td>".$type_name."</td><td>".$text_array[$key]."</td><td>".$time."</td>";
			
			$print_counter++;
			if ($print_counter == 5) {
				break;
			}
		}
		
		$agenda_home .= "</table>";
		
	} else {
		$agenda_home .= "<div class='landing_empty'>";
		$agenda_home .= __("There aren't meetings in your agenda");
		$agenda_home .= "</div>";
	}
	$agenda_home .= "</div>";
	
	$much_more = "<a href='index.php?sec=agenda&sec2=operation/agenda/agenda'>";
	$much_more .= "<img class='much_more' src='images/flecha_dcha.png' title='" . __('See more') . "'>";
	$much_more .= "</a>";
	
	$subtitle = "<span class='landing_subtitle'>";
	$subtitle .= __('First 5 events for next ten days');
	$subtitle .= "</span>";
	
	echo print_container_div('agenda_home', __('Agenda') . $subtitle . $much_more, $agenda_home, 'no');
	
	echo "</td>";

	// RIGHT SIDE
	echo "<td>";
	echo "<div class='landing_content'>";
	
	// ==============================================================
	// Show Projects items
	// ==============================================================
	
	$projects_home = '';
	
	if ($projects > 0){
		$from_one_month = date('Y-m-d', strtotime("now - 1 month"));

		$graph_result = graph_workunit_project_user (500, 200, $config["id_user"], $from_one_month, 0, true);

		//If there is an error in graph the graph functions returns a string
		$projects_home .= "<div class='landing_empty'>";
		$projects_home .= "<div class='graph_frame'>" . $graph_result . "</div>";
		$projects_home .= "</div>";		
	} else {
		$projects_home .= "<div class='landing_empty'>";
		$projects_home .= __("There aren't active projects");
		$projects_home .= "</div>";	
	}
	
	$much_more = "<a href='index.php?sec=projects&sec2=operation/projects/project_overview'>";
	$much_more .= "<img class='much_more' src='images/flecha_dcha.png' title='" . __('See more') . "'>";
	$much_more .= "</a>";
	
	$subtitle = "<span class='landing_subtitle'>";
	$subtitle .= __("Activity") . ": ";
	$subtitle .= projects_active_user ($config["id_user"]);
	$subtitle .= "</span>";
	
	echo print_container_div('projects_home', __('Projects') . $subtitle . $much_more, $projects_home, 'no', false, true,'','','','',"height: 400px;");
	
	echo "</td>";
	echo "</tr>";	
	echo "</table>";
	
	echo "</div>";

} else {
	 if (give_acl ($config["id_user"], 0, "AR")){
		include "operation/agenda/agenda.php";
	 } else {
		echo "<h1>". __("Welcome to Integria")."</h1>";
	 }
}

$check_browser = check_browser();

if ($check_browser) {	
	$browser_message = '<h4>'.__('Recommended browsers are Firefox and Chrome. You are using another browser.').'</h4>';
	echo "<div class= 'dialog ui-dialog-content' title='".__("Info")."' id='browser_dialog'>$browser_message</div>";
	echo "<script type='text/javascript'>";
	echo "	$(document).ready (function () {";
	echo "		$('#browser_dialog').dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: 'black'
					},
					width: 400,
					height: 150
				});";
	echo "		$('#browser_dialog').dialog('open');";
	echo "	});";
	echo "</script>";
}
?>


<script>
//Animate content
$(document).ready(function (){
	$(".landing_content").hide();
	$(".landing_content").slideDown('slow');
});
</script>
