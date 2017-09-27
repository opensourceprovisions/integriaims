<?PHP
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

global $config;
require_once ('include/functions_tasks.php');
require_once ('include/functions_workunits.php');
require_once ('include/functions_user.php');


if (check_login() != 0) {
 	audit_db("Noauth",$config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

$id_grupo = get_parameter ("id_grupo",0);
$id = get_parameter ('id', $config["id_user"]);

$real_user_id = $config["id_user"];

if ((give_acl($real_user_id, $id_grupo, "PR") != 1) AND (give_acl($$real_user_id, $id_grupo, "IR") != 1)){
 	// Doesn't have access to this page
	audit_db($real_user_id,$config["REMOTE_ADDR"], "ACL Violation","Trying to access to user monthly report without projects rights");
	include ("general/noaccess.php");
	exit;
}



//$users = get_user_visible_users();
$users = get_user_visible_users(0,'IR',true,true,false,'',false);

if (($id == "") || (($id != $real_user_id) && !in_array($id, array_keys($users)))) {
		audit_db("Noauth", $config["REMOTE_ADDR"], "No permission access", "Trying to access user workunit report");
		require ("general/noaccess.php");
		exit;
}

// Get parameters for actual Calendar show
$time = time();
$month = get_parameter ( "month", date('n', $time));
$year = get_parameter ( "year", date('y', $time));
$lock_month = get_parameter ("lock_month", "");

$today = date('j',$time);
$days_f = array();
$first_of_month = gmmktime(0,0,0,$month,1,$year);
$days_in_month=gmdate('t',$first_of_month);
$locale = $config["language_code"];

$prev_month = $month -1;
$prev_year = $year;
if ($prev_month == 0){
	$prev_month = 12;
	$prev_year = $prev_year -1;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13){
	$next_month = 1;
	$next_year = $next_year +1;
}
$day = date('d', strtotime("now"));

$from_one_month = "$prev_year-$prev_month-$day";


// Lock workunits for this month

//check_workunit_permission ($id_workunit) 
//lock_task_workunit ($id_workunit) 

if ($lock_month != ""){
	$this_month = date('Y-m-d H:i:s',strtotime("$year-$month-01"));
	$this_month_limit = date('Y-m-d H:i:s',strtotime("$year-$month-31"));
	
	$workunits = get_db_all_rows_sql ("SELECT id FROM tworkunit WHERE id_user='$id' AND locked = '' AND timestamp >= '$this_month' AND timestamp < '$this_month_limit'");

	foreach ($workunits as $workunit) {
		if (check_workunit_permission ($workunit["id"]))
			lock_task_workunit ($workunit["id"]);
	}
}

$now = date("Y-m-d H:i:s");
$now_year = date("Y");
$now_month = date("m");

$working_month = get_parameter ("working_month", $now_month);
$working_year = get_parameter ("working_year", $now_year);

$begin_month = "$now_year-$now_month-01 00:00:00";
$end_month = "$now_year-$now_month-31 23:59:59";

echo "<h2>".__('Monthly report') . "</h2>";

echo "<h4>" . __("For") . ": " . $id;
echo "<div id='button-bar-title'><ul>";
// Lock all workunits in this month

//~ $report_image = print_report_image ("index.php?sec=users&sec2=operation/user_report/monthly&month=$month&year=$year&id=$id", __("PDF report"));
//~ if ($report_image) {
	//~ echo "&nbsp;&nbsp;" . $report_image;
//~ }

if (!$pure) {
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/users/user_workunit_report&timestamp_l=$begin_month&timestamp_h=$end_month&id=$id'>";
		echo "<img src='images/page_white_text.png' border=0 title='". __("Show workunits"). "'>";
		echo "</a>";
	echo "</li>";
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly_graph&month=$now_month&year=$now_year&id=$id'>";
		echo "<img src='images/chart_bar.png' border=0 title='". __("Show graphs"). "'>";
		echo "</a>";
	echo "</li>";
	//~ echo "<li>";
		//~ echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly&lock_month=$month&month=$month&year=$year&id=$id'>";
		//~ echo "<img src='images/lock.png' border=0 title='". __("Lock all workunits in this month"). "'>";
		//~ echo "</a>";
	//~ echo "</li>";
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly&pure=1&id=$id'>";
		echo "<img src='images/html_tabs.png' border=0 title='". __("HTML"). "'>";
		echo "</a>";
	echo "</li>";
}
else {
	echo "<li>";
		echo " <a href='index.php?sec=users&sec2=operation/user_report/monthly&pure=0&id=$id'>";
		echo "<img src='images/flecha_volver.png' border=0 title='". __("Back"). "'>";
		echo "</a>";
	echo "</li>";
}

echo "</ul>";
echo "</div>";
echo "</h4>";

$first_of_month = gmmktime(0,0,0,$month,1,$year);

list($year, $month_name) = explode(',',gmstrftime('%Y,%B',$first_of_month));

echo "<table width=100% class='search-table' style='padding: 0px; border-spacing: 0px;'>";
echo "<tr><td colspan=4 class='calendar_annual_header' style='text-align: center;'>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$prev_month&year=$prev_year&id=$id'><img src='images/control_rewind_blue.png' title='" . __('Prev') . "' class='calendar_arrow'></a>";
echo "<span class='calendar-month' style='font-size: 0.93em; color: #FFFFFF; padding: 3px;'>" . strtoupper(htmlentities(ucfirst($month_name))) . " $year</span>";
echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$next_month&year=$next_year&id=$id'><img src='images/control_fastforward_blue.png' title='" . __('Next') . "' class='calendar_arrow'></a>";
echo "</td></tr>";
echo "<tr><td style='background-color: #FFF;'>";

echo "<div class='legend'><div style='background-color: #e3e9e9;' class='legend_box_color'></div><div class='legend_text'>".__('Not working day') . "</div></div>";
echo "<div class='legend'><div style='background-color: #FF7BFE;' class='legend_box_color'></div><div class='legend_text'>".__('Worked Incidents') . "</div></div>";
echo "<div class='legend'><div style='background-color: #FFFF80;' class='legend_box_color'></div><div class='legend_text'>".__('Vacation day') . "</div></div>";
echo "<div class='legend'><div style='background-color: #98FF8B;' class='legend_box_color'></div><div class='legend_text'>".__('Worked Projects') . "</div></div>";
echo "<div class='legend'><div style='background-color: #FFDE46;' class='legend_box_color'></div><div class='legend_text'>".__('Not Justified/Health issues') . "</div></div>";
echo "<div class='legend'><div style='background-color: #79d8ed;' class='legend_box_color'></div><div class='legend_text'>".__('Work in home') . "</div></div>";

if (give_acl($config["id_user"], 0, "PM")){
	echo "<td colspan=3 style='text-align: center; padding-top: 5px;'>";
	echo "<form id='form-monthly' method='post' action='index.php?sec=users&sec2=operation/user_report/monthly&month=$month&year=$year'>";
	
	$params['input_id'] = 'text-id_username';
	$params['input_name'] = 'id';
	$params['return'] = false;
	$params['return_help'] = false;
	$params['input_value']  = $id;
	user_print_autocomplete_input($params);
	
    echo "&nbsp;";
    print_submit_button (__('Show'), 'show_btn', false, 'class="next sub"');
    echo "</form>";
	echo "</td>";
}
echo "</tr><tr><td colspan=3 style='padding-bottom: 20px;'>";
// Generate calendar
echo generate_work_calendar ($year, $month, $days_f, 3, NULL, 1, "", $id);
echo "</td></tr>";
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
	
	bindAutocomplete ("#text-id_username", idUser);

});
// #text-user
validate_user ("#form-monthly", "#text-id_username", "<?php echo __('Invalid user')?>");
</script>
