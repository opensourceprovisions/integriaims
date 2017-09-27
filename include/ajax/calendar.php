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

require_once ('include/functions_calendar.php');
require_once ('include/functions_workunits.php');


global $config;

$get_events = (bool) get_parameter ('get_events');
$get_holidays = (bool) get_parameter ('get_holidays');
$get_non_working_days = (bool) get_parameter ('get_non_working_days');

if ($get_events) {

	$start_date = get_parameter("start_date");
	$end_date = get_parameter("end_date");
	$show_projects = get_parameter("show_projects");
	$show_tasks = get_parameter("show_tasks");
	$show_events = get_parameter("show_events");
	$show_wo = get_parameter("show_wo", false);
	$show_wu = get_parameter("show_wu");
	$show_clients = get_parameter("show_clients");
	$events = calendar_get_events_agenda($start_date, $end_date, $pn, $config['id_user'], $show_projects, $show_tasks, $show_events, $show_wo, $show_clients, $show_wu);

	$events_result = array();

	//Clean name output
	foreach ($events as $ev) {
		$ev["name"] = safe_output($ev["name"]);
		array_push($events_result, $ev);
	}
	
	echo json_encode($events_result);
	
	return;
}

if ($get_holidays) {

	$start_date = get_parameter("start_date");
	$end_date = get_parameter("end_date");

	$id_user = get_parameter("id_user", "");

	if (!$id_user) {
		$users = get_user_visible_users ($config["id_user"]);
		$users_ids = array_keys($users);
	} else {
		$users_ids = $id_user;
	}

	$holidays = calendar_get_users_holidays_date_range($start_date, $end_date, $users_ids);

	echo json_encode($holidays);

	return;
}

if ($get_non_working_days) {
	$year = safe_output(get_parameter("year"));
	
	$result = calendar_get_non_working_days($year);
	
	echo json_encode($result);
	
	return;
}

?>
 	
