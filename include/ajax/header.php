<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once ('include/functions.php');
include_once ('include/functions_ui.php');
include_once ('include/functions_calendar.php');

$get_alerts = get_parameter ('get_alerts', 0);
$get_alert_popup = get_parameter('get_alert_popup', 0);
$check_alarms_popup = get_parameter('check_alarms_popup', 0);
$set_alarm_checked = get_parameter('set_alarm_checked', 0);

if ($get_alerts) {

	$check_cron = check_last_cron_execution ();
	$check_emails = check_email_queue ();
	$minutes_last_exec = check_last_cron_execution (true);
	$seconds_last_exec = $minutes_last_exec * 60;
	$queued_emails = check_email_queue (true);
	$update_manager_msg = get_parameter ('update_manager_msg', '');
	$check_alarm_calendar = check_alarm_calendar();
	$check_directory_permissions = check_directory_permissions();
	$check_minor_release_available = db_check_minor_relase_available ();
	$check_browser = check_browser();
	
	$alerts = '';
	
	if ($minutes_last_exec == '') {
		$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.__('Crontask not installed. Please check documentation!').'</h4>';
	}
	if (!$check_cron) {
		$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.__('Last time Crontask was executed was ').calendar_seconds_to_humand($seconds_last_exec).__(' ago').'</h4>';
	}
	if (!$check_emails) {
		$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.__('Too many pending mail in mail queue: ').$queued_emails.('. Check SMTP configuration').'</h4>';
	}
	if ($update_manager_msg != '') {
		$update_manager_msg .= "<br><a href='index.php?sec=godmode&sec2=godmode/setup/update_manager'>".
							__("Go to Update Manager")."</a>";
							
		$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.$update_manager_msg.'</h4>';

	}
	if ($check_alarm_calendar) {
		$alarm_calendar_msg = '';
		$alarms = check_alarm_calendar(false);

		if ($alarms) {
			foreach ($alarms as $alarm) {
				$time = substr($alarm['timestamp'], 11, 5);
				$msg = '<h3 style="float: left; font-size:18px !important; font-weight: bold; margin-top:32px;">'.__('Calendar alert: ').'</h3><h4 style="float: left; font-size:15px !important; font-weight: bold; margin-top:32px;"><a href="index.php?sec=agenda&sec2=operation/agenda/agenda">'.$time.' '. __($alarm['title']).'</a></h4><h5 style="float: left; font-size:12px !important; font-weight: bold; margin-top:32px;">'.$alarm['description'].'</h5>';
				$alarm_calendar_msg .= $msg;
			}
			$alerts .= $alarm_calendar_msg;
		}
	}
	if ($check_directory_permissions) {
		$attachment = check_writable_attachment();
		if ($attachment != '') {
			$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.$attachment.'</h4>';
		}
		
		$tmp = check_writable_tmp();
		if ($tmp != '') {
			$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.$tmp.'</h4>';
		}
		
		$mr = check_writable_mr();
		if ($mr != '') {
			$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.$mr.'</h4>';
		}
	}
	if ($check_minor_release_available) {
		$alerts .= '<h4 style="width:100%; float: left; font-size:15px !important; font-weight: bold; margin-top:32px;">'.__('You must logout and login again to update database schema.').'</h4>';
	}
	if ($check_browser) {
		$alerts .= '<h4>'.__('Recommended browsers are Firefox and Chrome. You are using another browser.').'</h4>';
	}
	
	echo $alerts;
	echo '<br>';
	echo '<br>';
	print_button (__("Close"), "modal_cancel", false, 'closeAlertDialog()');
	//echo "<input type='button' class='sub close' onClick='javascript: closeAlertDialog();' value='".__("Close")."''>";
	return;	
}

if ($get_alert_popup) {
	$id = get_parameter('id');
	
	$alarm_calendar_msg = '';
	$alarm = check_alarm_calendar(false, $id);
	$time = substr($alarm['timestamp'], 11, 5);

	$alarm_calendar_msg = '<h3><a style="font-family: Verdana; font-size:16px; font-weight: normal; line-height: 1.5em;" href="index.php?sec=agenda&sec2=operation/agenda/agenda">'.$time.' '. __($alarm['title']).'</a></h3><h4 style="font-family: Verdana; font-size:14px; font-weight: normal; line-height: 1.5em;">'.$alarm['description'].'</h4>';
	echo $alarm_calendar_msg;
	return;
		
}
	
if ($check_alarms_popup) {

	$alarms = check_alarm_calendar(false);
	$i = 0;
	foreach ($alarms as $alarm) {
		$file_path = $config["homedir"]."/attachment/calendar/alarm_".$alarm['id']."_".$alarm['timestamp'].".txt";
		if (!file_exists($file_path)) {
			$result[$i]['id'] = $alarm['id'];
			$i++;
		}
	}
	echo json_encode($result);
	return;
}

// TO DO: Create a field 'checked' in tagenda
if ($set_alarm_checked) {
	$id = get_parameter('id');
	$timestamp = get_db_value('timestamp', 'tagenda', 'id', $id);
	$full_path = $config["homedir"]."/attachment/calendar/alarm_".$id."_".$timestamp.".txt";
	$file = fopen ($full_path, "w");
	fclose($file);
	return;
}

?>
