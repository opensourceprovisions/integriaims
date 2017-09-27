<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
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
check_login ();

require_once('include/functions_user.php');
require_once('include/functions_workunits.php');

$days_f = array();
$date = date('Y-m-d');


// --------------------
// Workunit report (yearly)
// --------------------
//	$now = date("Y-m-d H:i:s");
$year = date("Y");

$year = get_parameter ("year", $year);
$prev_year = $year -1 ;
$next_year = $year +1 ;	


$id_user_show = get_parameter ("id_user", $config["id_user"]);
$operation = get_parameter('operation');

if (($id_user_show != $config["id_user"]) AND (!give_acl($config["id_user"], 0, "PM"))){
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to another user yearly report without proper rights");
	include ("general/noaccess.php");
	exit;
}

// Extended ACL check for project manager
// TODO - Move to enteprrise, encapsulate in a general function
$users = get_user_visible_users();

if (($id_user_show == "") || (($id_user_show != $config["id_user"]) && !in_array($id_user_show, array_keys($users)))) {
		audit_db("Noauth", $config["REMOTE_ADDR"], "No permission access", "Trying to access user workunit report");
		require ("general/noaccess.php");
		exit;
}

switch ($operation) {
	case 'show_work_home':
		$wus = workunits_get_work_home_wu ($id_user_show, $year);
		$title = __('WORK FROM HOME');
	break;
	
	case 'show_vacations':
		$wus = workunits_get_vacation_wu ($id_user_show, $year);
		$title = __('VACATIONS');
	break;
	
	case 'show_worked_projects':
		$wus = workunits_get_worked_project_wu ($id_user_show, $year);
		$title = __('PROJECTS');
	break;
	
	case 'show_worked_tickets':
		$wus = workunits_get_worked_ticket_wu ($id_user_show, $year);
		$title = __('TICKETS');
	break;
	default:
	break;
}

echo "<h1>".__('Workunit resume for user')." ". $id_user_show. " - ".$title;

$report_image = print_report_image ("index.php?sec=users&sec2=operation/user_report/annual_workunits&operation=$operation&id_user=$id_user_show&year=$year", __("PDF report"));
if ($report_image) {
	echo "&nbsp;" . $report_image;
}

echo "<div id='button-bar-title'>";
	echo "<ul>";
	echo '<li>';
	echo '<a href="index.php?sec=users&sec2=operation/user_report/report_annual&id_user='.$id_user_show.'&year='.$year.'">'.print_image("images/go-previous.png", true, array("title" => __("Back to incident")))."</a>";
	echo '</li>';
	echo "</ul>";
echo "</div>";
echo "</h1>";

if (empty($wus)) {
	
	echo '<h4>'.__('No data to show').'</h4>';
	
} else {
	
	foreach ($wus as $workunit) {
		show_workunit_user ($workunit['id'], 0, false);
	}
	
	//workunits_print_table_massive_edition();

	echo '</div>';	
}

?>

<script type="text/javascript">
$(document).ready (function () {

	$(".lock_workunit").click (function () {
		var img = this;
		id = this.id.split ("-").pop ();
		values = Array ();
		values.push ({name: "page", value: "operation/users/user_spare_workunit"});
		values.push ({name: "operation", value: "lock"});
		values.push ({name: "id_workunit", value: id});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				$(img).fadeOut (function () {
					$(this).remove ();
				});
				$("#edit-"+id).fadeOut (function () {
					$(this).parent ("td").append (data);
					$(this).remove ();
				});
			},
			"html");
		return false;
	});
	
	$(".delete-workunit").attr ("onclick", "").click (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		var div = $(this).parents ("div.notebody");
		id = this.id.split ("-").pop ();
		values = Array ();
		values.push ({name: "page", value: "operation/users/user_spare_workunit"});
		values.push ({name: "operation", value: "delete"});
		values.push ({name: "id_workunit", value: id});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				$(div).prev ("div.notetitle").slideUp (function () {
					$(this).remove ();
				});
				$(div).slideUp (function () {
					$(this).remove ();
				});
			},
			"html");
		return false;
	});
	
	//WU Multiple delete
	$("#submit-delete_btn").click (function () {
				
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_delete_wu=1&ids=" + checkboxValues,
		dataType: "json",
		success: function (data, status) {
			var checkboxArray = checkboxValues.split(',');
			checkboxArray.forEach(function(item) {
				var div = document.getElementById("wu_"+item);
				div.remove();
			});
		}
		});
	});

	$("#submit-update_btn").click (function () {
		
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		var id_profile = $("#id_profile").val();
		var id_task = $("#id_task").val();
		var have_cost = $("#checkbox-have_cost").val();
		var is_public = $("#checkbox-public").val();
		var keep_cost = $("#checkbox-keep_cost").val();
		var keep_public = $("#checkbox-keep_public").val();
		
		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_update_wu=1&ids="+checkboxValues+"&id_profile="+id_profile+
			"&id_task="+id_task+"&have_cost="+have_cost+"&public="+is_public+"&keep_cost="+keep_cost+"&keep_public="+keep_public,
		dataType: "json",
		success: function (data, status) {
			location.reload();
		}
		});
	});

});
</script>

