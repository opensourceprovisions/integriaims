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

// PHP Calendar (version 2.3), written by Keith Devens
// http://keithdevens.com/software/php_calendar
// License: Artistic Licence


global $config;

require_once($config['homedir'].'/include/functions_agenda.php');

// Replace internal PHP function, not present in all PHP engines.
if (!function_exists("cal_days_in_month")){
	function cal_days_in_month($month, $year) { 
		return date('t', mktime(0, 0, 0, $month+1, 0, $year)); 
	}
}

function print_timestamp ($unixtime = 0){
	if ($unixtime == 0){
		$unixtime = time();
	}
	if (!is_numeric ($unixtime)) {
		$unixtime = strtotime ($unixtime);
	}
	$config["date_format"] = 'F j, Y, g:i a';	
	return date ($config["date_format"], $unixtime);
}

function print_mysql_timestamp ($unixtime = 0, $format = "Y-m-d H:i:s"){
	if ($unixtime == 0){
                $unixtime = time();
        }
        if (!is_numeric ($unixtime)) {
                $unixtime = strtotime ($unixtime);
        }
	return date ($format, $unixtime);

}

/**
 * Get an array with events for a given date. Returns an array
 *
 * $param $now  - Current date (only Y-M-D)
 * $param $days - Margin in (days) to compare from $now (default 1)
 * $param $ud_user - If void, get from session. __ANY__ for all users.
 */

function get_event_date ($now, $days_margin = 1, $id_user = ""){
	global $config;
	
	if ($id_user == "")
		$id_user = $config["id_user"];

	$now = "$now 00:00:00";
	$days_margin = $days_margin*24;
	$now3 = date('Y-m-d', strtotime("$now + $days_margin hours"));
	$now3 = "$now3 00:00:00";
	$result = array();

	if ($id_user == "_ANY_" || dame_admin($id_user)) {
		$sql = sprintf("SELECT *
						FROM tagenda
						WHERE timestamp >= '%s'
							AND timestamp <= '%s'
						ORDER BY timestamp ASC",
						$now, $now3);
		// Notify all events for today and return user
	}
	else {
		$sql = get_event_date_sql($now3, $now, $id_user);
	}

	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["timestamp"] ."|".$row["title"]."|".$row["id_user"];
	}
	
	return $result;
}

function get_project_end_date ($now, $days_margin = 0, $id_user = ""){
	global $config;
	
	if ($id_user == "")
		$id_user = $config["id_user"];
	
	$now3 = date('Y-m-d', strtotime("$now + $days_margin days"));
	$result = array();

	// Search for Project end in this date
	if ($id_user == '_ANY_' || dame_admin($id_user))
		$sql = "SELECT tproject.name as pname, tproject.end as pend, tproject.id as idp, tproject.id_owner as id_owner, tproject.start as pstart FROM tproject WHERE tproject.end >= '$now' AND tproject.end <= '$now3' GROUP BY idp";
	else
		$sql = "SELECT tproject.name as pname, tproject.end as pend, tproject.id as idp, tproject.id_owner as id_owner, tproject.start as pstart FROM trole_people_project, tproject WHERE trole_people_project.id_user = '$id_user' AND trole_people_project.id_project = tproject.id AND tproject.end >= '$now' AND tproject.end <= '$now3' GROUP BY idp";

	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["pname"] ."|".$row["idp"]."|".$row["pend"]."|".$row["id_owner"]."|".$row["pstart"];
	}
	return $result;
}

function calendar_get_project_date_range($start_date, $end_date, $id_user = "") {
	global $config;
	
	$id_user = $config["id_user"];
	
	$result = array();

	// Search for Project end in this date
	$sql = "SELECT tproject.name as pname, tproject.end as pend, tproject.id as idp, tproject.id_owner as id_owner, tproject.start as pstart FROM trole_people_project, tproject WHERE trole_people_project.id_user = '$id_user' AND trole_people_project.id_project = tproject.id AND ((tproject.start >= '$start_date' AND tproject.start <= '$end_date') OR (tproject.end >= '$start_date' AND tproject.end <= '$end_date')) GROUP BY idp";

	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["pname"] ."|".$row["idp"]."|".$row["pend"]."|".$row["id_owner"]."|".$row["pstart"];
	}
	return $result;
}

/** TODO
*/
function get_project_milestone ($now, $id_user = ""){
	global $config;
}

function get_task_end_date ($now, $days_margin = 0, $id_user = ""){
	global $config;
	
	if ($id_user == "")
		$id_user = $config["id_user"];

	$now3 = date('Y-m-d', strtotime("$now + $days_margin days"));
	$result = array();

	// Search for tasks that end in this date
	if (dame_admin($id_user))
		$sql = "SELECT tproject.name as pname, tproject.id as idp, ttask.name as tname, ttask.end as tend, ttask.id as idt, ttask.start as pstart FROM tproject, ttask WHERE tproject.id = ttask.id_project AND ttask.end >= '$now' AND ttask.end <= '$now3' GROUP BY idt";
	else
		$sql = "SELECT tproject.name as pname, tproject.id as idp, ttask.name as tname, ttask.end as tend, ttask.id as idt, ttask.start as pstart FROM trole_people_task, tproject, ttask WHERE tproject.id = ttask.id_project AND trole_people_task.id_user = '$id_user' AND trole_people_task.id_task = ttask.id AND ttask.end >= '$now' AND ttask.end <= '$now3' GROUP BY idt";
	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["tname"] ."|".$row["idt"]."|".$row["tend"]."|".$row["pname"]."|".$row["idp"]."|".$row["pstart"];
	}
	return $result;
}

function calendar_get_task_date_range($start, $end, $id_user = "") {

	global $config;
	
	
	$id_user = $config["id_user"];

	$result = array();

	// Search for tasks that end in this date
	$sql = "SELECT tproject.name as pname, tproject.id as idp, ttask.name as tname, ttask.end as tend, ttask.id as idt, ttask.start as pstart FROM trole_people_task, tproject, ttask WHERE tproject.id = ttask.id_project AND trole_people_task.id_user = '$id_user' AND trole_people_task.id_task = ttask.id AND ((ttask.end >= '$start' AND ttask.end <= '$end') OR (ttask.start >= '$start' AND ttask.start <= '$end')) GROUP BY idt";

	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["tname"] ."|".$row["idt"]."|".$row["tend"]."|".$row["pname"]."|".$row["idp"]."|".$row["pstart"];
	}
	return $result;

}

function get_wo_end_date ($now, $days_margin = 0, $id_user = ""){
	global $config;
	
	if ($id_user == "")
		$id_user = $config["id_user"];

	$now3 = date('Y-m-d', strtotime("$now + $days_margin days"));
	$result = array();

	// Search for tasks that end in this date
	if (dame_admin($id_user))
		$sql = "SELECT ttodo.id as idwo, ttodo.name as woname, ttodo.assigned_user woowner, ttodo.created_by_user as wocreator, ttodo.priority as wopriority, ttodo.end_date as woend, ttodo.start_date as wostart FROM ttodo WHERE ttodo.progress = 0 AND ttodo.end_date >= '$now' AND ttodo.end_date <= '$now3' GROUP BY idwo ORDER BY ttodo.end_date";
	else
		$sql = "SELECT ttodo.id as idwo, ttodo.name as woname, ttodo.assigned_user woowner, ttodo.created_by_user as wocreator, ttodo.priority as wopriority, ttodo.end_date as woend, ttodo.start_date as wostart FROM ttodo WHERE ttodo.progress = 0 AND (ttodo.assigned_user = '$id_user' OR ttodo.created_by_user = '$id_user') AND ttodo.end_date >= '$now' AND ttodo.end_date <= '$now3' GROUP BY idwo ORDER BY ttodo.end_date";
	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["idwo"] ."|".$row["woname"]."|".$row["woowner"]."|".$row["wocreator"]."|".$row["wopriority"]."|".$row["woend"]."|".$row["wostart"];
	}
	return $result;
}

function calendar_get_wo_date_range($start, $end){
	global $config;
	
	
	$id_user = $config["id_user"];

	$result = array();

	$sql = "SELECT ttodo.id as idwo, ttodo.name as woname, ttodo.assigned_user woowner, ttodo.created_by_user as wocreator, ttodo.priority as wopriority, ttodo.end_date as woend, ttodo.start_date as wostart FROM ttodo WHERE ttodo.progress = 0 AND ttodo.assigned_user = '$id_user' AND ((ttodo.end_date >= '$start' AND ttodo.end_date <= '$end') OR (ttodo.start_date >= '$start' AND ttodo.start_date <= '$end')) GROUP BY idwo";
	
	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["idwo"] ."|".$row["woname"]."|".$row["woowner"]."|".$row["wocreator"]."|".$row["wopriority"]."|".$row["woend"]."|".$row["wostart"];
	}
	return $result;
}

function calendar_get_wu_date_range($start, $end){
	global $config;
	
	
	$id_user = $config["id_user"];

	$result = array();

	//$sql = "SELECT ttodo.id as idwo, ttodo.name as woname, ttodo.assigned_user woowner, ttodo.created_by_user as wocreator, ttodo.priority as wopriority, ttodo.end_date as woend, ttodo.start_date as wostart FROM ttodo WHERE ttodo.progress = 0 AND ttodo.assigned_user = '$id_user' AND ((ttodo.end_date >= '$start' AND ttodo.end_date <= '$end') OR (ttodo.start_date >= '$start' AND ttodo.start_date <= '$end')) GROUP BY idwo";
	$sql = "SELECT * FROM tworkunit WHERE id_user = '$id_user' AND (timestamp > '$start' AND timestamp < '$end')";
	
	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["id"] ."|".$row["description"]."|".$row["id_user"]."|".$row["timestamp"]."|".$row["duration"];
	}
	return $result;
}

function calendar_get_events_agenda ($start, $end, $pn = array(), $id_user = "", $show_projects=1, $show_tasks=1, $show_events=1, $show_wo=0, $show_clients=1, $show_wu=1){
    global $config;

	$cal_events = array();

	$day_in_seconds = 24*3600;

	//Calculate mysql dates
	$mysql_start = date('Y-m-d', $start);
	$mysql_end = date('Y-m-d', $end);

	//Get project information
	if ($show_projects) {
		$agenda_project = calendar_get_project_date_range($mysql_start, $mysql_end);
		
		foreach ($agenda_project as $agenda_pitem){

			list ($pname, $idp, $pend, $owner, $pstart) = explode ("|", $agenda_pitem);

			$url = "index.php?sec=projects&sec2=operation/projects/project_detail&id_project=".$idp;

			array_push($cal_events, array("name" =>$pname, "start" => strtotime($pstart), "end" => strtotime($pend), "bgColor" => "#E46554", "allDay" => true, "url" => $url));
		}
	}

	//Get tasks information
	if ($show_tasks) {
		$agenda_task = calendar_get_task_date_range($mysql_start, $mysql_end);
		foreach ($agenda_task as $agenda_titem){
			
			list ($tname, $idt, $tend, $pname, $idp, $tstart) = explode ("|", $agenda_titem);

			$url = "index.php?sec=projects&sec2=operation/projects/task_detail&id_task=".$idt."&operation=view";

			array_push($cal_events, array("name" =>$tname, "start" => strtotime($tstart), "end" => strtotime($tend), "bgColor" => "#80D580", "allDay" => true, "url" => $url));	
		}
	}

	if ($show_wo) {
		$agenda_wo = calendar_get_wo_date_range($mysql_start, $mysql_end, $config["id_user"]);
		foreach ($agenda_wo as $agenda_woitem){
			list ($idwo, $woname, $woowner, $wocreator, $wopriority, $woend, $wostart) = explode ("|", $agenda_woitem);

			$url = "index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id=".$idwo;

			$wopriority_img = print_priority_flag_image ($wopriority, true);
			array_push($cal_events, array("name" =>$woname, "start" => strtotime($wostart), "end" => strtotime($woend), "bgColor" => "#6A6D6D", "allDay" => true, "url" => $url));	
		}
	}

	if ($show_wu) {
		$agenda_wu = calendar_get_wu_date_range($mysql_start, $mysql_end, $config["id_user"]);
		foreach ($agenda_wu as $agenda_wuitem){
			list ($idwu, $wudescription, $wuid_user, $wutimestamp, $wuduration) = explode ("|", $agenda_wuitem);
			$wudescription = ($wudescription != false) ? $wudescription : __("No description Workunit ID:")." ".$idwu;
			$url = "index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_workunit=".$idwu;
			debugPrint($wutimestamp,true);
			//$wopriority_img = print_priority_flag_image ($wopriority, true);
			array_push($cal_events, array("name" =>$wudescription, "start" => strtotime($wutimestamp), 
				"bgColor" => "#6A6D6D", "allDay" => true, "url" => $url));	
		}
	}

	if ($show_events) {
		for($i=$start;$i<=$end; $i = $i + $day_in_seconds){
			
			$mysql_date = date('Y-m-d', $i);
			
			// Search for agenda item for this date
			$sqlquery = "SELECT * FROM tagenda WHERE timestamp LIKE '$mysql_date%' ORDER BY timestamp ASC";
			
	 		$res=mysql_query($sqlquery);

			while ($row=mysql_fetch_array($res)){
				
				$event_public = $row["public"];
				$event_user = $row["id_user"];
				
	            if (agenda_get_entry_permission($id_user, $row["id"])){
	            	
	            	$dur_sec = $row["duration"]*3600; //Duration in seconds

	            	$start_timestamp = strtotime($row["timestamp"]);

	            	$start_timestamp = $start_timestamp + $dur_sec;

	            	$end_date = $start_timestamp;

	            	$url_date = date("Y-m-d", $start_timestamp);

	            	$url = "javascript: show_agenda_entry(".$row["id"].", '".$url_date."', '0', true)";

					array_push($cal_events, array("name" =>$row["title"], "start" => strtotime($row["timestamp"]),  "end" => $end_date, "bgColor" => "#8EC8DF", "allDay" => false, "url" => $url));    
	            }
			}
		}
	}
	
	if ($show_clients and !$show_events) {
		for($i=$start;$i<=$end; $i = $i + $day_in_seconds){
			
			$mysql_date = date('Y-m-d', $i);
			
			// Search for agenda item for this date
			$sqlquery = "SELECT tagenda.* FROM tagenda, tinvoice WHERE tagenda.title like CONCAT('%',(tinvoice.bill_id),'%') and tagenda.timestamp LIKE '$mysql_date%' ORDER BY tagenda.timestamp ASC";
	 		$res=mysql_query($sqlquery);

			while ($row=mysql_fetch_array($res)){
				
				$event_public = $row["public"];
				$event_user = $row["id_user"];
				
	            if (agenda_get_entry_permission($id_user, $row["id"])){
	            	
	            	$dur_sec = $row["duration"]*3600; //Duration in seconds

	            	$start_timestamp = strtotime($row["timestamp"]);

	            	$start_timestamp = $start_timestamp + $dur_sec;

	            	$end_date = $start_timestamp;

	            	$url_date = date("Y-m-d", $start_timestamp);

	            	$url = "javascript: show_agenda_entry(".$row["id"].", '".$url_date."', '0', true)";

					array_push($cal_events, array("name" =>$row["title"], "start" => strtotime($row["timestamp"]),  "end" => $end_date, "bgColor" => "#8EC8DF", "allDay" => false, "url" => $url));    
	            }
			}
		}
	}
	
	

	return $cal_events;
}

// Original function
function generate_calendar ($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array()){
	
	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = strtoupper(htmlentities(ucfirst($month_name))).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="calendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
	if($n) $n = '&nbsp;<span class="calendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';


	$calendar = '<table style="padding: 0px; margin: 0px auto;" class="calendar"><tr>';

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td style="font-size: 8px" colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}

		$content  = $day;
		$my_classes = "";
		$mysql_date = "$year-$month-$day";
		
		if (!is_working_day("$year-$month-$day")) {
			$days[$day][1] = "day_holiday";
			$days[$day][2] = "$day";
   		}
		
		$agenda_eventt = get_event_date ($mysql_date);
		foreach ($agenda_eventt as $agenda_event){
			list ($timestamp, $event) = explode ("|", $agenda_event);
			$days[$day][1] = "agenda";
			$days[$day][2] = "$day";
			$days[$day][0] = "index.php?sec=agenda&sec2=operation/agenda/agenda&month=$month&year=$year"; 
			$days[$day][3] = $event;
		}
		
		$agenda_wo = get_wo_end_date ($mysql_date);
		foreach ($agenda_wo as $agenda_woitem){
			list ($idwo, $woname) = explode ("|", $agenda_woitem);
			$days[$day][1] = "workorder";
			$days[$day][2] = "$day";
			$days[$day][0] = "index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id=$idwo";
			$days[$day][3] = $woname;
		}

		$agenda_task = get_task_end_date ($mysql_date);
		foreach ($agenda_task as $agenda_titem){
			list ($tname, $idt, $tend, $pname) = explode ("|", $agenda_titem);
			$days[$day][1] = "task";
			$days[$day][2] = "$day";
			$days[$day][0] = "index.php?sec=projects&sec2=operation/projects/task_detail&id_task=$idt&operation=view";
			$days[$day][3] = $pname . " / ". $tname;
		}
			
		$agenda_project = get_project_end_date ($mysql_date);
		foreach ($agenda_project as $agenda_pitem){
			list ($pname, $idp, $pend) = explode ("|", $agenda_pitem);
			$days[$day][1] = "project";
			$days[$day][2] = "$day";
			$days[$day][0] = "index.php?sec=projects&sec2=operation/projects/task&id_project=$idp";
			$days[$day][3] = $pname;
		}
		
		$time = time();
		$today = date('j',$time);
		$today_m = date('n',$time);
		$today_style = "";
		if (($today == $day) && ($today_m == $month))
			$today_style .= " style='border: 1px solid #FF9933; color: #FF9933;'";		

		if(isset($days[$day]) and is_array($days[$day])){
			@list($link, $classes, $content, $tooltip) = $days[$day];
			$calendar .= '<td '.$today_style.' class="'.htmlspecialchars($classes).'">';
			if ($link)
				$calendar .= '<a title="'.$tooltip.'" href="'.htmlspecialchars($link).'">'.$content.'</a></td>';
			else
			 	$calendar .= $content.'</td>';
		}
		else {
			$calendar .= "<td $today_style>$content</td>";
		}
	}
	if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days


	$calendar_outer = '<table style="padding: 0px; margin: 0px auto;" class="calendar_outer_orange"><tr>';
	$calendar_outer .= '<th colspan=7 class="calendar-month"><center><b>'.$title.'</b></center></th></tr><tr>';
	$calendar_outer .= '<td>' . $calendar . '</td></tr></table>';

	return $calendar_outer."</tr>\n</table>\n";
}



// Original function
function generate_small_work_calendar ($year, $month, $days = array(), $day_name_length = 3, $first_day = 0, $pn = array(), $id_user = ""){
	$current_month = date('m', time());
	$current_year = date('Y', time());

	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="calendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
	if($n) $n = '&nbsp;<span class="calendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';
	
	if ($month == $current_month && $year == $current_year) {
		$calendar_outer_class = "calendar_outer_orange";
	}
	else {
		$calendar_outer_class = "calendar_outer_grey";
	}
	$calendar =  '<table class="' . $calendar_outer_class . '" style="margin: 0px auto;"><tr><th class="calendar-month">'.$p.'<a href="index.php?sec=users&sec2=operation/user_report/monthly&month='.$month.'&year='.$year.'&id='.$id_user.'">'.strtoupper(safe_output($title)).'</a>'.$n.'</th></tr><tr><td>';
	$calendar .= '<table class="calendar">'."\n<tr>";

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.substr($d,0,1).'</th>';
			//$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td class="calendar" colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}
		
		// Show SUM workunits for that day (BLUE) - work from home
		$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE id_user = '$id_user' AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' AND tworkunit.work_home=1";

		$res=mysql_query($sqlquery);
		if ($row=mysql_fetch_array($res)){
			$workhours_e = $row[0];
			if ($workhours_e > 0){
				$normal = 5;
            }
			else {
				// Show SUM workunits for that day (YELLOW) - ORANGE (not justified)
				$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE id_user = '$id_user' AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task <-1 AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

				$res=mysql_query($sqlquery);

				
				if ($row=mysql_fetch_array($res)){
					$workhours_d = $row[0];
					if ($workhours_d > 0){
						$normal = 4;
		            }
					else {
						// Show SUM workunits for that day (MAGENTA) - incident wu
						$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_incident WHERE id_user = '$id_user' AND tworkunit_incident.id_workunit = tworkunit.id AND tworkunit_incident.id_incident != -1 AND  timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

						$res=mysql_query($sqlquery);
						if ($row=mysql_fetch_array($res)){
							$workhours_c = $row[0];
							if ($workhours_c > 0){
								$normal = 3;
							}
							else {
								// Show SUM workunits for that day (YELLOW) - holidays
								$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE id_user = '$id_user' AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task =-1 AND  timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

								$res=mysql_query($sqlquery);
								if ($row=mysql_fetch_array($res)){
									$workhours_b = $row[0];
									if ($workhours_b > 0){
										$normal = 2;
						            }
									else {
										// Show SUM workunits for that day (GREEN) - standard wu
										$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE id_user = '$id_user' AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";
										$normal = 0;
										$res=mysql_query($sqlquery);
										if ($row=mysql_fetch_array($res)){
											$workhours_a = $row[0];
											if ($workhours_a > 0){
												$normal = 1;
								            }
										}
									}
								}
							}
						}
					}
				}
			}
		}

		$mylink = "index.php?sec=users&sec2=operation/users/user_workunit_report&id=$id_user&timestamp_l=$year-$month-$day 00:00:00&timestamp_h=$year-$month-$day 23:59:59";

		if (!is_working_day("$year-$month-$day")) {
			$total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
            $calendar .= "<td class='calendar day_holiday'><a href='$mylink' title='$total_wu'>$day</a></td>";
   		}
	    elseif ($normal == 0)
    		$calendar .= "<td class='calendar'>$day</td>";
        elseif ($normal == 1){
			$total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
            $calendar .= "<td class='calendar day_worked_projects' style='background-color: #98FF8B'><a href='$mylink' title='$total_wu'>$day</a></td>";
		} 
        elseif ($normal == 2) {
			$total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
            $calendar .= "<td class='calendar day_vacation' style='background-color: #FFFF80'><a href='$mylink' title='$total_wu'>$day</a></td>";
		}
        elseif ($normal == 3) {
            $total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
            $calendar .= "<td class='calendar day_worked_incidents' style='background-color: #FF7BFE; color: #FFFFFF'><a href='$mylink' title='$total_wu'>$day</a></td>";
		}
		elseif ($normal == 5) {
			$total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
			$calendar .= "<td class='calendar day_work_home' style='background-color: #79d8ed'><a href='$mylink' title='$total_wu'>$day</a></td>";
		}
		elseif ($normal == 4) {
            $total_wu = $workhours_a + $workhours_c + $workhours_b + $workhours_d;
            $calendar .= "<td class='calendar day_other' style='background-color: #FFDE46'><a href='$mylink' title='$total_wu'>$day</a></td>";
        }
	}
	if($weekday != 7) $calendar .= '<td class="calendar" colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

	$calendar .= '</td></tr></table>'; // Calendar_outer
	return $calendar."</tr>\n</table>\n";
}

// Main function to show integria calendar with WU time on it
function generate_work_calendar ($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array(), $id_user = "", $show_hours_tip = true) {

	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = ''.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'&nbsp;';
	if($n) $n = '&nbsp;'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n);

	$calendar = '<table cellpadding=10 cellspacing=0 class="month_calendar"><tr>'."\n";
	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d) {
			$d = strtoupper($d);
			$calendar .= '<th width=70 abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		}
		$calendar .= '<th width=70>'.strtoupper(__('Week Total')).'</th>';
		$calendar .= "</tr>\n<tr>";
	}
	$time = time();
	$today = date('j',$time);
	$today_m = date('n',$time);

	// Initial empty days

	if($weekday > 0) 
		$calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; 

	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; //start a new week
			$ending_week = "$year-$month-$day 00:00:00";
			if ($day < 8) {
				$before_week = date('Y-m-d H:i:s',strtotime("$year-$month-1"));
			} else {
				$before_week = date('Y-m-d H:i:s',strtotime("$ending_week - 1 week"));
			}
			$this_week = date('Y-m-d H:i:s',strtotime("$year-$month-$day"));

			// Show SUM for that week
			$workhours = get_db_sql ("SELECT SUM(duration) FROM tworkunit WHERE id_user = '$id_user' AND timestamp < '$this_week' AND timestamp >= '$before_week'");
			
			$locked_hours = get_db_sql ("SELECT SUM(duration) FROM tworkunit WHERE id_user = '$id_user' AND timestamp < '$this_week' AND timestamp >= '$before_week' AND locked != ''");
			if ($locked_hours == "")
				$locked_hours = 0;
				
			if ($workhours > 0){

				$calendar .= "<td style='background-color: #e3e9e9;'><b><center><a title='Locked: $locked_hours hrs' href='index.php?sec=users&sec2=operation/users/user_workunit_report&id=".$id_user."&timestamp_l=".$before_week."&timestamp_h=".$this_week."'>$workhours";
				if (($locked_hours != 0) AND ($locked_hours != $workhours)){
					$calendar .= "<br><div style='font-size:7px'>$locked_hours " . _("Locked") . "</div></a></center></b></td>";
				}
			} else {
				$calendar .= "<td style='background-color: #e3e9e9;'><center> -- </center></td>";
			}
			
			
			
			$calendar .= "</tr>\n<tr>";
		}
		if(isset($days[$day]) and is_array($days[$day])){
			@list($link, $classes, $content) = $days[$day];
			if (is_null($content))
				$content  = $day;
			$calendar .= '<td'.($classes ? ' class="'.htmlspecialchars($classes).'">' : '>').
				($link ? '<b><a href="'.htmlspecialchars($link).'">'.$content.'</a>' : $content).'</b><br><br><br><br><br></td>';
		}


        $workhours_d = 0; 
        $workhours_c = 0; 
        $workhours_b = 0;
        $workhours_a = 0;
		$workhours_e = 0;

        // Show SUM workunits for that day (GREEN) - standard wu
        $sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND id_user = '$id_user' AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";
        $normal = 0;
        $res=mysql_query($sqlquery);
        if ($row=mysql_fetch_array($res)){
            $workhours_a = $row[0];
            if ($workhours_a > 0){
                $normal = 1;
            }
        }

        // Show SUM workunits for that day (YELLOW) - holidays
        $sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task =-1 AND id_user = '$id_user' AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

        $res=mysql_query($sqlquery);
        if ($row=mysql_fetch_array($res)){
            $workhours_b = $row[0];
            if ($workhours_b > 0){
                $normal = 2;
            }
        }

        // Show SUM workunits for that day (MAGENTA) - incident wu
        $sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_incident WHERE tworkunit_incident.id_workunit = tworkunit.id AND tworkunit_incident.id_incident != -1 AND id_user = '$id_user' AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

        $res=mysql_query($sqlquery);
        if ($row=mysql_fetch_array($res)){
            $workhours_c = $row[0];
            if ($workhours_c > 0){
                $normal = 3;
            }
        }

        // Show SUM workunits for that day (YELLOW) - ORANGE (not justified)
        $sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task <-1 AND id_user = '$id_user' AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' ";

        $res=mysql_query($sqlquery);
        if ($row=mysql_fetch_array($res)){
            $workhours_d = $row[0];
            if ($workhours_d > 0){
                $normal = 4;
            }
        }
        
        // Show SUM workunits for that day (BLUE) - work from home
		$sqlquery = "SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE id_user = '$id_user' AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND timestamp >= '$year-$month-$day 00:00:00' AND timestamp <= '$year-$month-$day 23:59:59' AND tworkunit.work_home=1";

		$res=mysql_query($sqlquery);
		if ($row=mysql_fetch_array($res)){
			$workhours_e = $row[0];
			if ($workhours_e > 0){
				$normal = 5;
            }
		}

		if (($day == $today) && ($today_m == $month))
            $border = "border: 2px dotted #707070";
        else {
            if (is_working_day("$year-$month-$day") == 1)
                $border = "";
            else
                $border = "border: 1px dashed #aBaBa9;";
        }
		
		$mysql_time= "";
		$event_string = "";
		$event_privacy = 0;
		$event_alarm = 0;
        $mydiff = 0;

		if ($day < 10)
			$day = "0".$day;
		$mysql_date = "$year-$month-$day";

        $workhours = $workhours_a + $workhours_b + $workhours_c + $workhours_d;
        $hours = "";
        
        //Check if this days is in holidays list if but also check for working ours
        $class = 'day_empty';
		if (!is_working_day("$year-$month-$day")) {
			$class = 'day_holiday';
		}
        if ($workhours_a > 0){
            $class = 'day_worked_projects';
            $mydiff++;
        } 
        if ($workhours_b > 0){
            $class = 'day_vacation';
            $mydiff++;
        } 
        if ($workhours_c > 0){
            $class = 'day_worked_incidents';
            $mydiff++;
		} 
        if ($workhours_d > 0){
            $class = 'day_other';
            $mydiff++;
		} 
		if ($workhours_e > 0){
            $class = 'day_work_home';
            $mydiff++;
		} 
		
        $calendar .= "<td valign='top' class='$class' style='$border; height: 70px; width: 70px;' ><b><a href='index.php?sec=users&sec2=operation/users/user_spare_workunit&givendate=$year-$month-$day'>$day</a></b>";

        if ($mydiff > 1 && $show_hours_tip){
            $calendar .= "<a href='#' class='tip'>&nbsp;<span>";
            $calendar .= __("Task/projects"). " : ". $workhours_a . "<br>";
            $calendar .= __("Vacations"). " : ". $workhours_b . "<br>";
            $calendar .= __("Incidents"). " : ". $workhours_c . "<br>";
            $calendar .= __("Non-Justified"). " : ". $workhours_d . "<br>";
            $calendar .= __("Work from home"). " : ". $workhours_e . "<br>";
            $calendar .= "</a>";
        }
        $calendar .= "<br><br>";

		$calendar .= "<center><a  href='index.php?sec=users&sec2=operation/users/user_workunit_report&id=".$id_user."&timestamp_l=".$mysql_date. " 00:00:00"."&timestamp_h=".$mysql_date."  23:59:59'><i>".$workhours."</i></a></center></td>";

	}
	if($weekday != 7) { // remaining "empty" days
		$calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; 
		$day --;
		$ending_week = "$year-$month-$day 23:59:59";
		$weekday_offset = 7 - $weekday;
		$before_week = date('Y-m-d H:i:s',strtotime("$ending_week - $weekday days"));	
		$this_week = date('Y-m-d H:i:s',strtotime("$year-$month-$day 23:59.00"));
		// Show SUM for that week
		$sqlquery = "SELECT SUM(duration) FROM tworkunit WHERE id_user = '$id_user' AND timestamp < '$this_week' AND timestamp > '$before_week'";
		$res=mysql_query($sqlquery);
		if ($row=mysql_fetch_array($res)){
			$workhours = $row[0];
			if ($workhours > 0){
				$calendar .= "<td><b><center><a  href='index.php?sec=users&sec2=operation/users/user_workunit_report&id=".$id_user."&timestamp_l=".$before_week."&timestamp_h=".$this_week."'>".$workhours." ".__('Hours')."</a></center></b></td>";
			} else {
				$calendar .= "<td style='background-color: #e3e9e9;'><center> -- </center></td>";
			}
		}
		$calendar .= "</tr>\n<tr>";
	}
	return $calendar."</tr>\n</table>\n";
}


// This functions calculates the next date only using business days
// First parameter is entered in YYYY-MM-DD, and second is hours

function calcdate_business ($datecalc, $duedays) {
    $datecalc = strtotime ($datecalc);
    $i = 1;
    while ($i <= $duedays) {
        $datecalc += 86400; // Add a day.
        $date_info  = getdate( $datecalc );
        if (($date_info["wday"] == 0) or ($date_info["wday"] == 6) )  {
            $datecalc += 86400; // Add a day.
            continue;
        }
        $i++;
    }
    return date ("Y-m-d", $datecalc);
}

// This functions calculates the previous date only using business days
// First parameter is entered in YYYY-MM-DD, and second is hours

function calcdate_business_prev ($datecalc, $duedays) {
	$datecalc = strtotime ($datecalc);
	$i = 1;
	while ($i <= $duedays) {
		$datecalc -= 86400; // Add a day.
		$date_info  = getdate( $datecalc );
		if (($date_info["wday"] == 0) or ($date_info["wday"] == 6) )  {
			$datecalc -= 86400; // Add a day.
			continue;
		}
		$i++;
	}
	return date ("Y-m-d", $datecalc);
}

function is_holidays ($datecalc) {
	
	$date_formated = $datecalc;
	
	$date = $date_formated.' 00:00:00';
	
	$id = get_db_value_filter("id", "tholidays", array("day" => $date));

	//If there is in the list is holidays
	if ($id) {
		return 1;
	}
	
	return 0;
}

function is_working_day ($datecalc) {
	global $config;
	
	$date_formated = $datecalc;
	$datecalc = strtotime ($datecalc);
	$date1 = getdate($datecalc);
	
	if (($date1["wday"] == 0) OR ($date1["wday"] == 6))
		//Check if weekends are working days or not
		if ($config["working_weekends"]) {
			return 1;
		} else {
			return 0;
		}
	else {
		if (is_holidays ($date_formated)) {
			return 0;
		}
	}
	
	return 1;
}

// ---------------------------------------------------------------
// Return string with time-threshold in secs, mins, days or weeks
// ---------------------------------------------------------------
// $flag_hide_zero Used to hide 0's in higher periods
function give_human_time ($int_seconds, $flag_hide_zero = true, $brief_time=false, $empty_zeros=false) {
	$key_suffix = 's';
	$periods = array (
		'year'   => 31556926,
		'month'  => 2629743,
		'day'    => 86400,
		'hour'   => 3600,
		'minute' => 60,
		'second' => 1
	);

	// do the loop thang
	foreach ($periods as $key => $length) {
		// calculate
		$temp = floor ($int_seconds / $length);

		// determine if temp qualifies to be passed to output
		if (!$flag_hide_zero || $temp > 0) {
			// store in an array
			$build[] = $temp.' '.$key.($temp!=1?'s':null);

			// set flag to false, to allow 0's in lower periods
			$flag_hide_zero = false;
		}

		// get the remainder of seconds
		$int_seconds = fmod($int_seconds, $length);
	}

	// return output, if !empty, implode into string, else output $if_reached
	
	if (empty($build)) {
		if (!$empty_zeros) {
			$ret_text = __("Unknown");
		} else {
			$ret_text = __("0 seconds");
		}
	} else {
		if (!$brief_time) {
			$ret_text = implode(', ', $build);
		} else {
			$size = count($build);
			
			if ($size > 2) {
				$aux_build = array();

				$aux_build[0] = $build[0];
				$aux_build[1] = $build[1];

				$build = $aux_build;
			}
	
			$ret_text = implode(', ', $build);
		}
	}


	return $ret_text;
}


// ---------------------------------------------------------------
// Return string with time-threshold in secs, mins, days or weeks
// This function returns the time in a clear way than give_human_time
// This function could replace give_human_time in other views
// ---------------------------------------------------------------
function calendar_seconds_to_humand ($int_seconds) {
	$key_suffix = 's';
	$periods = array (
		'year'   => 31556926,
		'month'  => 2629743,
		'day'    => 86400,
		'hour'   => 3600,
		'minute' => 60,
		'second' => 1
	);

	// do the loop thang
	foreach ($periods as $key => $length) {
		// calculate
		$temp = floor ($int_seconds / $length);

		// determine if temp qualifies to be passed to output
		if ($temp > 0) {
			// store in an array
			$build[] = $temp.' '.$key.($temp!=1?'s':null);
		}

		// get the remainder of seconds
		$int_seconds = fmod($int_seconds, $length);
	}

	//Generate return string
	$str_ret = __("0 seconds");
	
	if (!empty($build)) {	
		$str_ret = implode(', ', $build);
	}
	return $str_ret;
}

/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return int Unix timestamp
 */
function get_system_time () {
	global $config;
	static $time = 0;
	
	if ($time != 0)
		return $time;

	$config["timesource"] = "system";
	
	if ($config["timesource"] = "sql") {
		$time = get_db_sql ("SELECT UNIX_TIMESTAMP()");
		if (empty ($time)) {
			return time ();
		}
		return $time;
	} else {
		return time ();
	}
}

/*
 	int calendar_time_diff (utimestamp / formatted time string  
 	Return seconds passed since given date
*/

function calendar_time_diff ($timestamp) {
	global $config;
	
	if (!is_numeric ($timestamp)) {
		$timestamp = strtotime ($timestamp);
	}
	
	$seconds = get_system_time () - $timestamp;

	return $seconds;
}

function human_time_comparation ($timestamp) {
	global $config;
	
	if (!is_numeric ($timestamp)) {
		$timestamp = strtotime ($timestamp);
	}
	
	$seconds = get_system_time () - $timestamp;

	return human_time_description_raw ($seconds);
}

/** 
 * INTERNAL (use print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days.
 * 
 * @param int $seconds Seconds elapsed time
 * @param int $exactly If it's true, return the exactly human time
 * 
 * @return string A human readable translation of minutes.
 */
function human_time_description_raw ($seconds, $exactly = false) {

	if ((empty ($seconds)) OR ($seconds < 0)) {
		return __('Now'); 
		// slerena 25/03/09
		// Most times $seconds is empty is because last contact is current date
		// Put here "uknown" or N/A or something similar is not a good idea
	}

	if ($exactly) {
		$secs = $seconds % 60;
		$mins = ($seconds /60) % 60;
		$hours = ($seconds / 3600) % 24;
		$days = ($seconds / 86400) % 30;
		$months = format_numeric ($seconds / 2592000, 0);
		
		if (($mins == 0) && ($hours == 0) && ($days == 0) && ($months == 0))
			return format_numeric ($secs, 0).' '.__('seconds');
		else if (($hours == 0) && ($days == 0) && ($months == 0))
			return sprintf("%02d",$mins).':'.sprintf("%02d",$secs);
		else if (($days == 0) && ($months == 0))
			return sprintf("%02d",$hours).':'.sprintf("%02d",$mins).':'.sprintf("%02d",$secs);
		else if (($months == 0))
			return $days.' '.__('days').' 
'.sprintf("%02d",$hours).':'.sprintf("%02d",$mins).':'.sprintf("%02d",$secs);
		else
			return $months.' '.__('months').' '.$days.' '.__('days').' 
'.sprintf("%02d",$hours).':'.sprintf("%02d",$mins).':'.sprintf("%02d",$secs);	
	}
	
	if ($seconds < 60)
		return format_numeric ($seconds, 0)." ".__('seconds');
	
	if ($seconds < 3600) {
		$minutes = floor($seconds / 60);
		$seconds = $seconds % 60;
		if ($seconds == 0)
			return $minutes.' '.__('minutes');
		$seconds = sprintf ("%02d", $seconds);
		return $minutes.':'.$seconds.' '.__('minutes');
	}
	
	if ($seconds < 86400)
		return format_numeric ($seconds / 3600, 0)." ".__('hours');
	
	if ($seconds < 2592000)
		return format_numeric ($seconds / 86400, 0)." ".__('days');
	
	if ($seconds < 15552000)
		return format_numeric ($seconds / 2592000, 0)." ".__('months');
	
	return "+6 ".__('months');
}

function working_days ($month = "", $year = "" ){
	if (($month == "") OR ($year == "")){
		$date = date('Y-m-d');
		$year = substr($date, 0,4);
		$month = substr($date, 5, 2);
	}
	
	$d_daysinmonth = date('t', mktime(0,0,0,$month,1,$year));  // how many days in month
	$full_weeks = ceil ($d_daysinmonth / 7);
	$festive_days = floor(($d_daysinmonth / 7) * 2);
	$total_working_days = $d_daysinmonth - $festive_days;
	$total_working_hours = $total_working_days * 8;
	return $total_working_days;
}

function working_weeks_combo () {
	$date = date('Y-m-d');
	$year = substr($date, 0,4);
	$month = substr($date, 5, 2);
	$day = substr($date, 8, 2);

	$d_daysinmonth = date('t', mktime(0,0,0,$month,1,$year));  // how many days in month
	$full_weeks = ceil ($d_daysinmonth / 7);
	$d_firstdow = date('w', mktime(0,0,0,$month,'1',$year));     // FIRST falls on what day of week (0-6)
	$ajuste = $d_firstdow -1;
	if ($ajuste >= 0)
		$new_date = date('Y-m-d', strtotime("$year-$month-01 - $ajuste days"));
	else {
		$ajuste = $ajuste * -1;
		$new_date = date('Y-m-d', strtotime("$year-$month-01 + $ajuste days"));
	}

	echo '<select name="working_week">';
	for ($ax=0; $ax < $full_weeks; $ax++){
		echo "<option>".date('Y-m-d', strtotime($new_date. "+ $ax week"));
	}
	echo "</select>";
}


function first_working_week (){
	$date = date('Y-m-d');
	$year = substr($date, 0,4);
	$month = substr($date, 5, 2);
	$day = substr($date, 8, 2);
	$d_daysinmonth = date('t', mktime(0,0,0,$month,1,$year));  // how many days in month
	$full_weeks = ceil ($d_daysinmonth / 7);
	$d_firstdow = date('w', mktime(0,0,0,$month,'1',$year));     // FIRST falls on what day of week (0-6)
	$ajuste = $d_firstdow -1;
	if ($ajuste >= 0)
		$new_date = date('Y-m-d', strtotime("$year-$month-01 - $ajuste days"));
	else {
		$ajuste = $ajuste * -1;
		$new_date = date('Y-m-d', strtotime("$year-$month-01 + $ajuste days"));
	}
	return $new_date;
}

function week_start_day (){
	return date('Y-m-d', date('U')-(date('w')+6)%7*86400);
}

function getmonth ($m = 0) {
	return (($m==0 ) ? date ("F") : date ("F", mktime (0, 0, 0, $m)));
}

// Return working days (of $config["hours_perday"] hours) given a total in hours)
function get_working_days ( $hours ) {
	global $config;
	return ($hours / $config["hours_perday"]);
}


/**
* Converts a unix timestamp to iCal format (UTC) - if no timezone is
* specified then it presumes the uStamp is already in UTC format.
* tzone must be in decimal such as 1hr 45mins would be 1.75, behind
* times should be represented as negative decimals 10hours behind
* would be -10
* 
* $uStamp longint UNIX timestamp
* $tzone  float   Timezone  
*/

function unixToiCal($uStamp = 0, $tzone = 0.0) {
	$uStampUTC = $uStamp + ($tzone * 3600);       
	$stamp  = date("Ymd\THis\Z", $uStampUTC);	
	return $stamp;       
}

/**
* Returns the no. of business days between two dates and it skeeps the holidays
*
* $startDate string Startdate for interval of check (yyyy-mm-dd)
* $endDate   string Startdate for interval of check (yyyy-mm-dd)
* $holidays array Array containing holidays (yyyy-mm-dd)
*
**/

function getWorkingDays($startDate,$endDate,$holidays){
    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
    //We add one to inlude both dates in the interval.
    $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;

    $no_full_weeks = floor($days / 7);
    $no_remaining_days = fmod($days, 7);

    //It will return 1 if it's Monday,.. ,7 for Sunday
    $the_first_day_of_week = date("N",strtotime($startDate));
    $the_last_day_of_week = date("N",strtotime($endDate));

    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
    if ($the_first_day_of_week <= $the_last_day_of_week){
        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
    }
    else{
        if ($the_first_day_of_week <= 6) $no_remaining_days--;
        //In the case when the interval falls in two weeks, there will be a Sunday for sure
        $no_remaining_days--;
    }

    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
   $workingDays = $no_full_weeks * 5;
    if ($no_remaining_days > 0 )
    {
      $workingDays += $no_remaining_days;
    }

    //We subtract the holidays
    foreach($holidays as $holiday){
        $time_stamp=strtotime($holiday);
        //If the holiday doesn't fall in weekend
        if (strtotime($startDate) <= $time_stamp && $time_stamp <= strtotime($endDate) && date("N",$time_stamp) != 6 && date("N",$time_stamp) != 7)
            $workingDays--;
    }

    return $workingDays;

	/* Samples: 
		$holidays=array("2006-12-25","2006-12-26","2007-01-01");
		echo getWorkingDays("2006-12-22","2007-01-06",$holidays)
 		=> will return 8
	*/

}

function calendar_get_holidays() {
	return get_db_all_rows_in_table ("tholidays", "day");	
}

function calendar_get_non_working_days ($year = false) {
	global $config;
	
	$holidays = get_db_all_rows_in_table ("tholidays", "day");
	
	if (!$year || $config["working_weekends"]) {
		return $holidays;
	}
	
	$res = array();
	foreach ($holidays as $holiday) {
		$day = new DateTime($holiday["day"]);
		$res[] = $day->format("Y-m-d");
	}
	
	$start = new DateTime("$year-1-1");
	$end = new DateTime("$year-12-31");
	$endTimestamp = $end->getTimestamp();
	
	if (($start->format("w") == 6) || ($start->format("w") == 0)) {
		$d = $start;
	} else {
		$d = $start->modify("next saturday");
	}
	
	$oneday = new DateInterval("P1D");
	$sixdays = new DateInterval("P6D");
	
	while ($d->getTimestamp() <= $endTimestamp) {
		$res[] = $d->format("Y-m-d");
		$d = $d->add($oneday);
		if ($d->getTimestamp() <= $endTimestamp) {
			$res[] = $d->format("Y-m-d");
		}
		$d = $d->add($sixdays);
	}
	
	return $res;
}

function mysql_timestamp ($unix_time){
    return date('Y-m-d H:i:s', $unix_time);
}

function calendar_get_holidays_by_timerange ($begin_unix, $end_unix) {
	$day_in_seconds = 3600*24;
	
	//Normalize dates to 00:00:00
	
	$norm = date('Y-m-d', $begin_unix);
	
	$begin_unix = strtotime($norm);
	
	$norm = date('Y-m-d', $end_unix);
	
	$end_unix = strtotime($norm);
	
	$holidays = array();;
		
	for ($i=$begin_unix; $i<=$end_unix; $i=$i+$day_in_seconds) {
		
		$str_date = date('Y-m-d', $i);
		
		if (!is_working_day($str_date)) {
			array_push($holidays, $str_date);
		}
	}
	
	return $holidays;
}

function get_non_working_days ($year) {
	$defined_non_working_days = (int) get_db_value ('COUNT(*)', 'tholidays');
	$weekend_days = 0;
	
	if ($config['working_weekends'] == 0) {
		$start = strtotime($year . '-1-1 00:00:00');
		$end = strtotime($year . '-12-31 23:59:59');
		
		// Obtain number of saturdays & sundays on this year.
		
		// First: search first monday of the year and count sat&sun from day 1 to this day
		while (1) {
			$week_day = date('w', $start);
			
			switch($week_day){
				case 0:
				case 6:
					$weekend_days++;
					break;
				case 1:
					break 2; // Go out from while
			}
			
			$start += 86400;
		}
		
		// Second: search last sunday of the year and count sat from this day to day 31
		while (1) {
			$week_day = date('w', $end);
			
			switch($week_day){
				case 6:
					$weekend_days++;
					break;
				case 0:
					break 2; // Go out from while
			}
			
			$end -= 86400;
		}
		
		// Third: obtain number of weeks from these two dates and multiply by 2
		$weeks = ceil(($end - $start) / 86400 / 7);
		$weekend_days += $weeks * 2;
	}
	
	return $defined_non_working_days + $weekend_days;
}

function calendar_get_users_holidays_date_range($start, $end, $id_user) {

	$mysql_start = date('Y-m-d', $start);
	$mysql_end = date('Y-m-d', $end);

	$user_clause = "";
	if (is_array($id_user)) {

		$aux_ids = array();
		foreach ($id_user as $iu) {
			array_push($aux_ids, "'".$iu."'");
		}
		$user_clause = "id_user IN (".join(",",$aux_ids).")";
	} else {
		$user_clause = "id_user = '$id_user'";
	}

	$sql = sprintf("SELECT tworkunit.timestamp AS date, tworkunit.id_user as user FROM tworkunit, tworkunit_task WHERE 
					$user_clause AND tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task =-1 AND 
					timestamp >= '$mysql_start' AND timestamp <= '$mysql_end' ORDER BY date ASC");
	
	$res = process_sql($sql);

	if (!$res) {
		$res = array();
	}

	$holidays = array();
	$holidays_counters = array();
	$holidays_last_day = array();

	foreach ($res as $r) {
		
		if (!isset($holidays[$r["user"]])) {
			$holidays[$r["user"]] = array();
			$holidays_counters[$r["user"]] = -1;
			$holidays_last_day[$r["user"]] = 0;
		}

		//Calculate start and end for the holiday value
		if (isset($holidays_last_day[$r["user"]])) {
			
			//Last day minus current day is 1 we increase dates for stored holidays
			$last_time = strtotime($holidays_last_day[$r["user"]]);
			$current_time = strtotime($r["date"]);
			$day_in_seconds = 24*3600;
		
			if ($current_time - $last_time <= $day_in_seconds) {
				
				$pos = $holidays_counters[$r["user"]];
				
				$holidays[$r["user"]][$pos]["end"] = strtotime($r["date"]);
				$holidays_last_day[$r["user"]] = strtotime($r["date"]);
			} else {
				array_push($holidays[$r["user"]], array("start" => strtotime($r["date"]), "end" => strtotime($r["date"])));
				$holidays_last_day[$r["user"]] = $r["date"];
				$holidays_counters[$r["user"]]++;
			}
		}

		//Update last day
		$holidays_last_day[$r["user"]] = $r["date"];
		
	}

	$full_holidays = array();

	$colors = array("#aa3333", "#33aa33", "#3a3a3a", "#3333aa",
					"#045FB4", "#DF7401", "#01DF3A", "#BE81F7",
					"#8181F7", "#81BEF7", "#F781F3", "#F7D358",
					"#F78181", "#FA8258", "#BCF5A9", "#A9A9F5",
					"#D0A9F5", "#E2A9F3", "#A9F5D0", "#A9F5E1",
					"#BDBDBD", "#E6E6E6", "#F6CECE", "#6AA4B2",
					"#6AB277", "#B28E6A", "#B26A97", "#776AB2",
					"#A58EB4", "#8EB490", "#8E96B4", "#B48E98");

	$i = 0;
	$colors_size = count($colors);
	foreach ($holidays as $key => $values) {
		$i = $i % $colors_size;

		$name = get_db_value("nombre_real", "tusuario", "id_usuario", $key);
		$url = "index.php?sec=users&sec2=operation/user_report/holidays_calendar&custom_dates=1&id_user=".$key;

		array_push($full_holidays, array("name" => safe_output($name), "dates" => $values, "bgColor" => $colors[$i], "link" => $url));

		$i++;
	}

	return $full_holidays;
}

?>
