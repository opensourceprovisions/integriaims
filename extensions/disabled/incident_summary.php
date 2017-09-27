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

function check_incident_access ($id) {
	global $config;
	
	if ($id) {
		$incident = get_incident ($id);

		if ($incident !== false) {
			$id_grupo = $incident['id_grupo'];
		} else {
			echo "<h1>".__("Ticket")."</h1>";
			echo ui_print_error_message (__("There is no information for this ticket"), '', true, 'h3', true);
			echo "<br>";
			echo "<a style='margin-left: 90px' href='index.php?sec=incidents&sec2=operation/incidents/incident_search'>".__("Try the search form to find the ticket")."</a>";
			return false;
		}
	}

	if (isset($incident)) {
		//Incident creators must see their incidents
		$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident));
		$standalone_check = enterprise_hook("manage_standalone", array($incident));

		if (($check_acl !== ENTERPRISE_NOT_HOOK && !$check_acl) || ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check)) {

			// Doesn't have access to this page
			audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket (External user) ".$id);
			include ("general/noaccess.php");
			return false;
		}
	} else if (! give_acl ($config['id_user'], $id_grupo, "IR")) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket ".$id);
		include ("general/noaccess.php");
		return false;
	} else {
		//No incident but ACLs enabled
		echo ui_print_error_message (__("The ticket doesn't exist"), '', true, 'h3', true);
		return false;
	}
	
	return true;
}

function print_incident () {
	global $config, $pdf_output, $pdf_filename;
	
	$id = (int) get_parameter("id");
	$incident = get_incident($id);
	
	if (!check_incident_access($id)) {
		return;
	}
	
	if ($pdf_output) {
		ob_clean();
		$pdf_filename = "incident_$id.pdf";
	} else {
		$report_button = print_report_button ("index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=$id&tab=summary", __('Export'));
		if ($report_button) {
			echo "<div style='padding:7px; float:right;'>";
			echo $report_button;
			echo "</div>";
		}
	}
	
	echo "<div style='padding-left:7px;'>";
	
	echo "<table style='font: 1em Verdana, Helvetica, Arial, 'Trebuchet MS', Arial, Sans-serif; color:#505050; width:620px; text-align:left;'>";
	
	if ($pdf_output) {
		echo "	<tr>";
		echo "		<td style='text-align:center; font-size:14px; color:#505050;'>";
		echo "			<h1>#".$incident["id_incidencia"]."&nbsp;&nbsp;".$incident["titulo"]."</h1><br><br>";
		echo "		</td>";
		echo "	</tr>";
	}
	
	if ($incident["id_creator"]) {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Creator')."</b> - ".$incident["id_creator"];
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["id_usuario"]) {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Owner')."</b> - ".$incident["id_usuario"];
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["estado"]) {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Status')."</b> - ".incidents_get_incident_status_text ($incident["id_incidencia"]);
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["inicio"] != "0000-00-00 00:00:00") {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Created on')."</b> - ".strftime("%e %B %Y (%H:%M)", strtotime($incident["inicio"]));
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["cierre"] != "0000-00-00 00:00:00") {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Closed on')."</b> - ".strftime("%e %B %Y (%H:%M)", strtotime($incident["cierre"]));
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["descripcion"]) {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Description')."</b>";
		echo "			<div style='width:620px; word-wrap:break-word;'>".$incident["descripcion"]."</div>";
		echo "		</td>";
		echo "	</tr>";
	}
	if ($incident["epilog"]) {
		echo "	<tr>";
		echo "		<td style='font-size:16px; color:#505050;'>";
		echo "			<b>".__('Epilog')."</b>";
		echo "			<div style='width:620px; word-wrap:break-word;'>".$incident["epilog"]."</div>";
		echo "		</td>";
		echo "	</tr>";
	}
	echo "</table>";
	echo "</div>";
}

extensions_add_tab_option ('summary', __('Ticket summary'), "operation/incidents/incident_dashboard_detail", "../images/note.png", "indicent-details-view");
extensions_add_tab_function ('print_incident');
?>
