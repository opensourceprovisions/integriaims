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


echo "<h2>".__('Annual report')."</h2>";
echo "<h4>".__('For user').": ". $id_user_show;

echo "<div id='button-bar-title'><ul>";
if ($clean_output == 0) {
	// link full screen
	if ($pure) {
		$html_report_image = print_html_report_image ("index.php?sec=users&sec2=operation/user_report/report_annual&id_user=$id_user_show&year=$year",
			__("Full screen"), "", array("pure" => 0));
	}
	else {
		$html_report_image = print_html_report_image ("index.php?sec=users&sec2=operation/user_report/report_annual&id_user=$id_user_show&year=$year",
			__("Full screen"), "", array("pure" => 1));
	
		if ($html_report_image) {
			echo "<li>" . $html_report_image . "</li>";
		}
		
		// link PDF report
		$report_image = print_report_image ("index.php?sec=users&sec2=operation/user_report/report_annual&id_user=$id_user_show&year=$year", __("PDF report"));
		if ($report_image) {
			echo "<li>" . $report_image . "</li>";
		}
	}
}

echo "</ul>";
echo "</div>";
echo "</h4>";

echo "<table class='search-table' width=100% style='text-align:right;'><tr><td>";
echo "<table style='margin: 0px auto; '><tr>";
echo "<td style='text-align:right;'>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/annual_workunits&operation=show_vacations&year=$year&id_user=$id_user_show'>".__('Vacations days').'</a>';
echo "<td class='day_vacation day_legend'>";
echo get_user_vacations ($id_user_show, $year). "</td>";

echo "<td style='text-align:right; padding-left: 35px;'>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/annual_workunits&operation=show_worked_projects&year=$year&id_user=$id_user_show'>".__('Days worked (projects)').'</a>';
echo "<td class='day_worked_projects day_legend'>";
echo get_user_worked_days ($id_user_show, $year). "</td>";

echo "<td style='text-align:right; padding-left: 35px;'>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/annual_workunits&operation=show_worked_tickets&year=$year&id_user=$id_user_show'>".__('Days worked (tickets)').'</a>';
echo "<td class='day_worked_incidents day_legend'>";
echo get_user_incident_worked_days ($id_user_show, $year). "</td>";

echo "<td style='text-align:right; padding-left: 35px;'>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/annual_workunits&operation=show_work_home&year=$year&id_user=$id_user_show'>".__('Work from home').'</a>';
echo "<td class='day_work_home day_legend'>";
echo get_user_work_home ($id_user_show, $year). "</td>";

echo "<td style='text-align:right; padding-left: 35px;'>";
echo __('Other');
echo "<td class='day_other day_legend'>";
echo get_user_other ($id_user_show, $year);

echo "<td style='text-align:right; padding-left: 35px;'>";
echo __('Non-working days');
echo "<td class='day_holiday day_legend'>";
echo get_non_working_days ($year);

echo "</table>";

echo "</td></tr></table>";

echo "<table style='margin: 0px auto; text-align: center; padding: 0px; width: 100%; border-spacing: 0px;' class='search-table'>";
echo "<tr><td colspan=4 class='calendar_annual_header'>";
if($pdf_output == 0) {
	// Prev. year
	echo "<a href='index.php?sec=users&sec2=operation/user_report/report_annual&year=$prev_year&id_user=$id_user_show&clean_output=$clean_output'><img src='images/control_rewind_blue.png' title='" . __('Previous year') . "' class='calendar_arrow'></a>";
}
echo "<span class='calendar-month' style='font-size: 0.93em; color: #FFFFFF; padding: 3px;'>$year</span>";
if($pdf_output == 0) {
	// Next. year
	echo "<a href='index.php?sec=users&sec2=operation/user_report/report_annual&year=$next_year&id_user=$id_user_show&clean_output=$clean_output'><img src='images/control_fastforward_blue.png' title='" . __('Next year') . "' class='calendar_arrow'></a>";
}
echo "</td></tr>";
echo "<tr><td colspan=4>";
echo "<form id='form-report_annual' name='xx' method=post action='index.php?sec=users&sec2=operation/user_report/report_annual'>";
echo "<table cellpadding=4 cellspacing=4 style='margin: 0px auto;'>";
echo "<tr><td>";

if (give_acl($config["id_user"], 0, "PM") && $pdf_output == 0){		

	echo "<input type='hidden' name='year' value='$year'>";
	
	echo "<td>";
	// Show user
	$params['input_id'] = 'text-id_user';
	$params['input_name'] = 'id_user';
	$params['return'] = false;
	$params['return_help'] = false;
	$params['input_value'] = $id_user_show;
	user_print_autocomplete_input($params);
	
	echo "</td>";	
			
	echo "<td>";
	print_submit_button (__('Go'), 'sub_btn', false, 'class="upd sub"');
	echo "</td>";
}
echo "</table></form>";

echo "</td></tr>";
echo "<tr>";

$calendars = array();
for ($ax = 1; $ax < 13; $ax++){
	$calendars[] = generate_small_work_calendar ($year, $ax, $days_f, 3, $config["first_day_week"], "en", $id_user_show);
}

$index = 0;
for ($ax = 1; $ax < 13; $ax++){
	if (fmod($ax-1,4) == 0)
		echo "<tr>";
	echo "<td valign=top style='font-size: 10px; padding-right: 10px; padding-left: 10px; padding-bottom: 10px; text-align: center;'>";
	
	$this_month = date('Y-m-d H:i:s',strtotime("$year-$ax-01"));
	$this_month_limit = date('Y-m-d H:i:s',strtotime("$year-$ax-31"));

	$work_hours = get_db_sql ("SELECT SUM(duration) FROM tworkunit WHERE id_user='$id_user_show' AND locked = '' AND timestamp >= '$this_month' AND timestamp < '$this_month_limit'");

	if ($work_hours == "")
		$work_hours = 0;	
	
	$locked_hours = get_db_sql ("SELECT SUM(duration) FROM tworkunit WHERE id_user='$id_user_show' AND locked != '' AND timestamp >= '$this_month' AND timestamp < '$this_month_limit'");
	
	if ($locked_hours == "")
		$locked_hours = 0;	
	
	echo __("Total") . " : " . $work_hours;
	echo " - ";
	echo __("Locked"). " : " . $locked_hours;        
	
	echo $calendars[$index];
	$index++;
}

echo "</table>";

?>

<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>

<script type="text/javascript">

// Datepicker
add_datepicker ("#text-start_date", null);

$(document).ready (function () {
$("#textarea-description").TextAreaResizer ();

var idUser = "<?php echo $config['id_user'] ?>";

bindAutocomplete ("#text-id_user", idUser);	

});
// #text-id_user
validate_user ("#form-report_annual", "#text-id_user", "<?php echo __('Invalid user')?>");
</script>
