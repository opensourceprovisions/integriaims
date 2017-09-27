<?php
// Integria 1.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars

global $config;
$id_user = $config["id_user"];

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

// --------------------
// Workunit report
// --------------------
$now = date("Y-m-d H:i:s");
$now_year = date("Y");
$now_month = date("m");

$week_begin = get_parameter ( "working_week");

if ($week_begin == "")
	$begin_week = first_working_week() . " 00:00:00";
else
	$begin_week = $week_begin;

$end_week = date('Y-m-d H:i:s',strtotime("$begin_week + 1 week"));
$total_hours = 5 * $config["hours_perday"]; // TODO: subroutine to minus festive days (defined on DB by month)
$color = 0;

echo "<h3>";
echo __("Totals for week")." ".$begin_week." - ".$end_week." - ".$total_hours." ".__("Hours");
echo "</h3>";
echo "<table style='margin-left: 10px;' class='blank' width='200'>";
echo "<tr><td>";
echo "<form method='post' action='index.php?sec=users&sec2=operation/user_report/report_weekly'>";
working_weeks_combo();
echo "</td><td>";
echo "<input type=submit class='next' value='".__('Update')."'>";
echo "</form>";
echo "</table>";


echo '<table width="90%" class="listing">';
echo "<th>".__('User ID');
echo "<th>".__('Workunit report');
echo "<th>".__('Graph overview');
echo "<th>".__('Total hours');

$sql0= "SELECT * FROM tusuario";
if ($res0 = mysql_query($sql0)) {
	while ($row0=mysql_fetch_array($res0)){
		// Can current user have access to this user ?
		if ((user_visible_for_me ($config["id_user"], $row0["id_usuario"], "IM") == 1) OR 
			(user_visible_for_me ($config["id_user"], $row0["id_usuario"], "PM") == 1)) {
			$nombre = $row0["id_usuario"];
			$avatar = $row0["avatar"];
			$sql= "SELECT SUM(duration) FROM tworkunit WHERE timestamp > '$begin_week' AND timestamp < '$end_week' AND id_user = '$nombre'";
			if ($res = mysql_query($sql)) {	
				$row=mysql_fetch_array($res);
			}
		   
			echo "<tr><td>";
			echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'> ";
			if ($config["enteprise"] == 1){
				$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
				$result1=mysql_query($sql1);
				echo "<a href='#' class='tip'>&nbsp;<span>";
				if (mysql_num_rows($result1)){
					while ($row1=mysql_fetch_array($result1)){
						echo dame_perfil($row1["id_perfil"])."/ ";
						echo dame_grupo($row1["id_grupo"])."<br>";
					}
				} else {
					echo __('This user doesn\'t have any assigned profile/group');
					
				}
				echo "</span></a> ";
			}
			echo $nombre;

			// Text wu report 
			echo "<td><center>";
			echo "<a href='index.php?sec=users&sec2=operation/users/user_workunit_report&timestamp_l=$begin_week&timestamp_h=$end_week&id=$nombre'>";
			echo "<img border=0 src='images/page_white_text.png'></A>";

			// Graph stats montly report for X user
			echo "<td><center>";
			echo "<a href='index.php?sec=users&sec2=operation/user_report/weekly_graph&timestamp_l=$begin_week&timestamp_h=$end_week&id=$nombre'><img src='images/chart_bar.png' border=0></a></center></td>";
			
			echo "<td><center>";
			echo $row[0];
		}
	}
}
echo "</table>";
?>
