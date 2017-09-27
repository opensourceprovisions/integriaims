<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
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

include_once('include/functions_crm.php');
include_once('include/functions_tags.php');

$section_read_permission = check_crm_acl ('lead', 'cr');
if (!$section_read_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to the lead section");
	include ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');
$tab = (string) get_parameter("tab");

$message = get_parameter('message', '');
if ($message != '') {
	echo ui_print_error_message (__($message), '', true, 'h3', true);
}

$title = "";
	
switch ($tab) {
	case "pipeline":
		$title = __('Lead pipeline');
		break;
	case "search":
		$title = __('Lead search');
		break;
	case "statistics":
		$title =__('Lead search statistics');
		break;		
	default:
		$title = __('Lead detail');
}

// Listing of contacts

$new = get_parameter("new");
$delete = get_parameter("delete");

//Don't show header if we are creating a new lead
if ((!$new && !$id && ($tab != "statistics")) && ($tab != "pipeline") || $delete) {

	echo "<div id='lead-search-content'>";
	echo "<h2>" . _("Leads") . "</h2>";
	echo "<h4>".$title;
	echo integria_help ("lead", true);
	echo "<div id='button-bar-title'>";
	echo "<ul>";
	
	if ($tab == "search" || $tab == "") {
		echo "<li>";
		// Custom search button
			echo "<a href='javascript:' onclick='toggleDiv (\"custom_search\")'>".__('Custom search')."</a>";
		echo "</li>";
		echo "<li>";
		echo "<a id='lead_stats_form_submit' href='javascript: changeAction(\"pipeline\");'>".print_image ("images/icon_lead.png", true, array("title" => __("Lead pipeline")))."</a>";
		echo "</li>";
	}
	
	echo "<li>";
	echo "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search'>".print_image ("images/zoom.png", true, array("title" => __("Search leads")))."</a>";
	echo "</li>";
	
	if ($tab == "search" || $tab == "") {
		echo "<li>";
		echo "<a id='lead_stats_form_submit' href='javascript: changeAction(\"statistics\");'>".print_image ("images/chart_bar_dark.png", true, array("title" => __("Search statistics")))."</a>";
		echo "</li>";
	}
	
	echo "</ul>";
	echo "</div>";
	echo "</h4>";
}
	
switch ($tab) {
	case "pipeline":
		include("lead_pipeline.php");
		break;
	case "search":
		include("lead_detail.php");
		break;
	case "statistics":
		include("lead_statistics.php");
		break;
	default:
		include("lead_detail.php");
}

?>