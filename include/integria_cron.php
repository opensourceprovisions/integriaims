<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include ("config.php");
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);
require_once ($config["homedir"].'/include/functions.php');
require_once ($config["homedir"].'/include/functions_calendar.php');
require_once ($config["homedir"].'/include/functions_groups.php');
require_once ($config["homedir"].'/include/functions_workunits.php');
require_once ($config["homedir"].'/include/functions_inventories.php');
require_once ($config["homedir"].'/include/functions_html.php');
require_once ($config["homedir"].'/include/functions_mail.php');

// Activate errors. Should not be anyone, but if something happen, should be
// shown on console.

error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

$config["id_user"] = 'System';
$now = time ();
$compare_timestamp = date ("Y-m-d H:i:s", $now - $config["notification_period"]*3600);
$human_notification_period = give_human_time ($config["notification_period"]*3600);

/**
 * This function delete temp files (mostly image temporaly serialize data)
 */

function delete_tmp_files(){

	if (function_exists('sys_get_temp_dir'))
		$dir =  sys_get_temp_dir ();
	else
		$dir = "/tmp";

	if ($dh = opendir($dir)) {
		while(($file = readdir($dh))!== false) {
			if (strpos("___".$file, "integria_serialize") || strpos("___".$file, "tmpIntegriaFileSharing")) {
				if (file_exists($dir."/".$file)) {
					if (is_dir($dir."/".$file)) {
						delete_directory($dir."/".$file);
					}
					else {
						$fdate = filemtime($dir."/".$file);
						$now = time();
						if ($now - $fdate > 3600){
							@unlink($dir."/".$file);
						} 
					}
				}
			}
		}
		closedir($dh);
	}
}
 
/** 
 * Interface to Integria API functionality.
 * 
 * @param string $url Url to Integria API with user, password and option (function to use).
 * @param string $postparameters Additional parameters to pass.
 *
 * @return variant The function result called in the API.
 */
function call_api($url, $postparameters = false) {

	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $url);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	if($postparameters !== false) {
		curl_setopt($curlObj, CURLOPT_POSTFIELDS, $postparameters);
	}
	$result = curl_exec($curlObj);
	curl_close($curlObj);

	return $result;
}

/**
 * This function is executed once per day and do several different subtasks
 * like email notify ending tasks or projects, events for this day, etc.
 */

function run_daily_check () {
	$current_date = date ("Y-m-d h:i:s");

	// Create a mark in event log
	process_sql ("INSERT INTO tevent (type, timestamp) VALUES ('DAILY_CHECK', '$current_date') ");

	// Do checks
	run_calendar_check ();
	run_project_check ();
	run_task_check ();
	run_autowu();
	run_auto_incident_close();
	delete_tmp_files();
	delete_old_audit_data();
	delete_old_event_data();
	delete_old_incidents();
	delete_old_wu_data();
	delete_old_wo_data();
	delete_old_sessions_data();
	delete_old_workflow_event_data();
	delete_old_fs_files_data();
	delete_old_files_track_data();
	remove_no_task_users_project();
}

/**
 * Auto close incidents mark as "pending to be deleted" and no activity in X hrs
 * Takes no parameters. Checked in the main loop.with the other tasks.
 */

function run_auto_incident_close () {
	global $config;

	if (empty($config["auto_incident_close"]) || $config["auto_incident_close"] <= 0)
		return;

	require_once ($config["homedir"]."/include/functions_incidents.php");

	$utimestamp = date("U");
	$limit = date ("Y-m-d H:i:s", $utimestamp - $config["auto_incident_close"] * 86400);

		// For each incident
	$incidents = get_db_all_rows_sql ("SELECT * FROM tincidencia WHERE estado IN (1,2,3,4,5) AND actualizacion < '$limit'");
	$mailtext = __("This ticket has been closed automatically by Integria after waiting confirmation to close this ticket for 
").$config["auto_incident_close"]."  ".__("days");

	if ($incidents) {
		foreach ($incidents as $incident) {
			
			// Set status to "Closed" (# 7) and solution to 7 (Expired)
			process_sql ("UPDATE tincidencia SET resolution = 7, estado = 7 WHERE id_incidencia = ".$incident["id_incidencia"]);

			// Add workunit
			create_workunit ($incident["id_incidencia"], $mailtext, $incident["id_usuario"], 0,  0, "", 1);
	
			// Send mail warning about this autoclose
			if (($config["email_on_incident_update"] != 2) && ($config["email_on_incident_update"] != 4)) {
				mail_incident ($incident["id_incidencia"], $incident["id_usuario"], $mailtext, 0, 10, 1);
			}
		}
	}

}

/**
 * Autofill WU ("Not justified") for users without reporting anything.
 * 
 * This function is used to verify that a user has $config["hours_perday"]
 * (as minimum) in each labor day in a period of time going from
 * now - $config["autowu_completion"] until now - ($config["autowu_completion"]*2)
 * If user dont have WU in that days, will create a WU associated 
 * to task "Not Justified" inside special project -1.
 */

function run_autowu () {
	global $config;

	$now = date ("Y-m-d");
	// getWorkingDays($startDate,$endDate,$holidays){

	$autowu = $config["autowu_completion"];

	if ($autowu == 0)
		return;

	$autowu2 = $autowu + 7; // Always work with one week of margin

	// Calc interval dates
	$start_date = date('Y-m-d', strtotime("$now - $autowu2 days"));
	$end_date = date('Y-m-d', strtotime("$now - $autowu days"));
	$current_date = $start_date;

	
	// For each user
	$users = get_db_all_rows_sql ("SELECT * FROM tusuario");

	$end_loop = 0;
	
	while ($end_loop == 0){
		foreach ($users as $user){
			if (!is_working_day($current_date))
				continue;

			
			// If this user is in no_wu_completion list, skip it.
			if (strpos("_____".$config["no_wu_completion"], $user["id_usuario"]) > 0 ){
				continue;
			}
			
			$user_wu = get_wu_hours_user ($user["id_usuario"], $current_date);
			if ($user_wu < $config["hours_perday"]) {
				$nombre = $user['nombre_real'];
				$email = $user['direccion'];

				$mail_description = "Integria IMS has entered an automated Workunit to 'Not justified' task because you've more than $autowu days without filling by a valid Workunit.";
	
				integria_sendmail ($email, "[".$config["sitename"]."] Automatic WU (Non justified) has been entered",  $mail_description );
	
				create_wu_task (-3, $user["id_usuario"], $mail_description, 0, 0, 0, $config["hours_perday"]-$user_wu, $current_date);
	
			}
			
		}
	$current_date = date('Y-m-d', strtotime("$current_date +1 day"));	
	if ($current_date == $end_date)
		$end_loop = 1;
	}
}

/**
 * Checks and notify user by mail if in current day there agenda items 
 */
function run_calendar_check () {
	global $config;

	$now = date ("Y-m-d");
	$events = get_event_date ($now, 1, "_ANY_");

	foreach ($events as $event){
		list ($timestamp, $event_data, $user_event) = split ("\|", $event);
		$user = get_db_row ("tusuario", "id_usuario", $user_event);
		$nombre = $user['nombre_real'];
		$email = $user['direccion'];
		$mail_description = "There is an Integria agenda event planned for today at ($timestamp): \n\n$event_data\n\n";
		integria_sendmail ($email, "[".$config["sitename"]."] Calendar event for today ",  $mail_description );
	}
}

/**
 * Checks and notify user by mail if in current day there are ending projects
 *
 */
function run_project_check () {
	global $config;

	$now = date ("Y-m-d");
	$projects = get_project_end_date ($now, 0, "_ANY_");

	foreach ($projects as $project){
		list ($pname, $idp, $pend, $owner) = split ("\|", $project);
		$user = get_db_row ("tusuario", "id_usuario", $owner);
		$nombre = $user['nombre_real'];
		$email = $user['direccion'];
		$mail_description = "There is an Integria project ending today ($pend): $pname. \n\nUse this link to review the project information:\n";
		$mail_description .= $config["base_url"]. "index.php?sec=projects&sec2=operation/projects/project_detail&id_project=$idp\n";

		integria_sendmail ($email, "[".$config["sitename"]."] Project ends today ($pname)",  $mail_description );
	}
}


/**
 * Checks and notify user by mail if in current day there are ending tasks
 */
function run_task_check () {
	global $config;

	$now = date ("Y-m-d");
	$baseurl = 
	$tasks = get_task_end_date_by_user ($now);
	
	foreach ($tasks as $task){
		list ($tname, $idt, $tend, $pname, $user) = split ("\|", $task);
		$user_row = get_db_row ("tusuario", "id_usuario", $user);
		$nombre = $user_row['nombre_real'];
		$email = $user_row['direccion'];
		
		$mail_description = "There is a task ending today ($tend) : $pname / $tname \n\nUse this link to review the project information:\n";
		$mail_description .= $config["base_url"]. "index.php?sec=projects&sec2=operation/projects/task_detail&id_task=$idt&operation=view\n";

		integria_sendmail ($email, "[".$config["sitename"]."] Task ends today ($tname)",  $mail_description );
	}
}



/**
 * Check if daily task has been executed in the last 24 hours.
 */
function check_daily_task () {
	$current_date = date ("Y-m-d");
	$current_date .= " 00:00:00";
	$result = get_db_sql ("SELECT COUNT(id) FROM tevent WHERE type = 'DAILY_CHECK' AND timestamp > '$current_date'");
	// Daily check has been executed in the past 24 hours.
	if ($result > 0)
		return false;

	return true;
}

//Inserts data into SLA graph table
function graph_sla($incident) {
	$id_incident = $incident['id_incidencia'];
	$utimestamp = time();
	
	//Get sla values for this incident
	$sla_affected = get_db_value("affected_sla_id", "tincidencia", 
						"id_incidencia", $id_incident);
	
	$values['id_incident'] = $id_incident;
	$values['utimestamp'] = $utimestamp;	
	
	//If incident is affected by SLA then the graph value is 0
	if ($sla_affected) {
		$values['value'] = 0;
	} else {
		$values['value'] = 1;
	}
	
	$sql = sprintf("SELECT value
					FROM tincident_sla_graph_data
					WHERE id_incident = %d
					ORDER BY utimestamp DESC",
					$id_incident);
	$result = get_db_row_sql($sql);
	$last_value = !empty($result) ? $result['value'] : -1;
	
	if ($values['value'] != $last_value) {
		//Insert SLA value in table
		process_sql_insert('tincident_sla_graph_data', $values);
	}
}

/**
 * Check an SLA min response value on an incident and send emails if needed.
 *
 * @param array Incident to check
 */
function check_sla_min ($incident) {
	global $compare_timestamp;
	global $config;

	$id_sla = check_incident_sla_min_response ($incident['id_incidencia']);

	if (! $id_sla)
		return false;
	
	$sla = get_db_row("tsla", "id", $id_sla);

	/* Check if it was already notified in a specified time interval */
	$sql = sprintf ('SELECT COUNT(id) FROM tevent
		WHERE type = "SLA_MIN_RESPONSE_NOTIFY"
		AND id_item = %d
		AND timestamp > "%s"',
		$incident['id_incidencia'],
		$compare_timestamp);
	$notified = get_db_sql ($sql);

	if ($notified > 0){
		return true;
   }

	/* We need to notify via email to the owner user */
	$user = get_user ($incident['id_usuario']);

	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_username_"] = $incident['id_usuario'];
	$MACROS["_fullname_"] = dame_nombre_real ($incident['id_usuario']);
	$MACROS["_group_"] = dame_nombre_grupo ($incident['id_grupo']);
	$MACROS["_incident_id_"] = $incident["id_incidencia"];
	$MACROS["_incident_title_"] = $incident['titulo'];
	$MACROS["_data1_"] = give_human_time ($sla['min_response']*60*60);
	
	$access_dir = empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
	$MACROS["_access_url_"] = $access_dir."/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia'];

	$sql_body    = "SELECT name FROM temail_template WHERE template_action = 14 AND id_group =".$incident['id_grupo'].";";
	$sql_subject = "SELECT name FROM temail_template WHERE template_action = 15 AND id_group =".$incident['id_grupo'].";";
	$templa_body = get_db_sql($sql_body);
	$templa_subj = get_db_sql($sql_subject);
	
	if(!$templa_body){
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_min_response_time.tpl", $MACROS);
	} else {
		$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
	}

	if(!$templa_subj){
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_min_response_time_subject.tpl", $MACROS);
	} else {
		$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
	}

	if ($sla['enforced'] == 1){
		integria_sendmail ($user['direccion'], $subject, $text);
		insert_event ('SLA_MIN_RESPONSE_NOTIFY', $incident['id_incidencia']);
		return true;
	} else {
		insert_event ('SLA_MIN_RESPONSE_NOTIFY', $incident['id_incidencia']);
		return true;
	}
}

/**
 * Check an SLA max response value on an incident and send emails if needed.
 *
 * @param array Incident to check
 */
function check_sla_max ($incident) {
	global $compare_timestamp;
	global $config;
	
	$id_sla = check_incident_sla_max_response ($incident['id_incidencia']);
	if (! $id_sla)
		return false;
	
		$sla = get_db_row("tsla", "id", $id_sla);

	/* Check if it was already notified in a specified time interval */
	$sql = sprintf ('SELECT COUNT(id) FROM tevent
		WHERE type = "SLA_MAX_RESPONSE_NOTIFY"
		AND id_item = %d
		AND timestamp > "%s"',
		$incident['id_incidencia'],
		$compare_timestamp);
	$notified = get_db_sql ($sql);

	if ($notified > 0)
		return true;
	
	/* We need to notify via email to the owner user */
	$user = get_user ($incident['id_usuario']);

	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_username_"] = $incident['id_usuario'];
	$MACROS["_fullname_"] = dame_nombre_real ($incident['id_usuario']);
	$MACROS["_group_"] = dame_nombre_grupo ($incident['id_grupo']);
	$MACROS["_incident_id_"] = $incident["id_incidencia"];
	$MACROS["_incident_title_"] = $incident['titulo'];
	$MACROS["_data1_"] = give_human_time ($sla['max_response']*3600);
	
	$access_dir = empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
	$MACROS["_access_url_"] = $access_dir."/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia'];

	$sql_body    = "SELECT name FROM temail_template WHERE template_action = 12 AND id_group =".$incident['id_grupo'].";";
	$sql_subject = "SELECT name FROM temail_template WHERE template_action = 13 AND id_group =".$incident['id_grupo'].";";
	$templa_body = get_db_sql($sql_body);
	$templa_subj = get_db_sql($sql_subject);
	
	if(!$templa_body){
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_max_response_time.tpl", $MACROS);
	} else {
		$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
	}

	if(!$templa_subj){
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_max_response_time_subject.tpl", $MACROS);
	} else {
		$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
	}

	if ($sla['enforced'] == 1){
		integria_sendmail ($user['direccion'], $subject, $text);
		insert_event ('SLA_MAX_RESPONSE_NOTIFY', $incident['id_incidencia']);
	} else {
		insert_event ('SLA_MAX_RESPONSE_NOTIFY', $incident['id_incidencia']);
	}
}

/**
 * Check an SLA inactivity value on an incident and send email (to incident owner) if needed.
 *
 * @param array Incident to check
 */
function check_sla_inactivity ($incident) {
	global $compare_timestamp;
	global $config;
	
	$id_sla = check_incident_sla_max_inactivity ($incident['id_incidencia']);
	if (! $id_sla)
		return false;
	
	$sla = get_db_row("tsla", "id", $id_sla);

	/* Check if it was already notified in a specified time interval */
	$sql = sprintf ('SELECT COUNT(id) FROM tevent
		WHERE type = "SLA_MAX_INACTIVITY_NOTIFY"
		AND id_item = %d
		AND timestamp > "%s"',
		$incident['id_incidencia'],
		$compare_timestamp);
	$notified = get_db_sql ($sql);

	if ($notified > 0)
		return true;
	
	/* We need to notify via email to the owner user */
	$user = get_user ($incident['id_usuario']);

	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_username_"] = $incident['id_usuario'];
	$MACROS["_fullname_"] = dame_nombre_real ($incident['id_usuario']);
	$MACROS["_group_"] = dame_nombre_grupo ($incident['id_grupo']);
	$MACROS["_incident_id_"] = $incident["id_incidencia"];
	$MACROS["_incident_title_"] = $incident['titulo'];
	$MACROS["_data1_"] = give_human_time ($sla['max_inactivity']*3600);
	
	$access_dir = empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
	$MACROS["_access_url_"] = $access_dir."/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia'];

	$sql_body    = "SELECT name FROM temail_template WHERE template_action = 10 AND id_group =".$incident['id_grupo'].";";
	$sql_subject = "SELECT name FROM temail_template WHERE template_action = 11 AND id_group =".$incident['id_grupo'].";";
	$templa_body = get_db_sql($sql_body);
	$templa_subj = get_db_sql($sql_subject);
	if(!$templa_body){
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_max_inactivity_time.tpl", $MACROS);
	} else {
		$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
	}
	if(!$templa_subj){
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_sla_max_inactivity_time_subject.tpl", $MACROS);
	} else {
		$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
	}
	if ($sla['enforced'] == 1){
		integria_sendmail ($user['direccion'], $subject, $text);
		insert_event ('SLA_MAX_INACTIVITY_NOTIFY', $incident['id_incidencia']);
	} else {
		insert_event ('SLA_MAX_INACTIVITY_NOTIFY', $incident['id_incidencia']);
	}
}

function run_mail_queue () {
	global $config;
	
	// Get pending mails
	$filter = array('status' => 0);
	
	$mails = get_db_all_rows_filter('tpending_mail', $filter);
	
	// No pending mails
	if ($mails === false) return;
	
	// Init mailer
	$mailer = null;
	try {
		// Use local mailer if host not provided - Attach not supported !!
		if (empty($config['smtp_host'])) {
			// Empty snmp conf. System sendmail transport
			$transport = mail_get_transport();
			$mailer = mail_get_mailer($transport);
		}
		else {
			$mailer = mail_get_mailer();
		}
	}
	catch (Exception $e) {
		integria_logwrite(sprintf("Mail transport failure: %s", $e->getMessage()));
		return;
	}
	
	foreach ($mails as $email) {
		try {
			//Check if the email was sent at least once
			if (mail_send($email, $mailer) > 0) {
				process_sql_delete('tpending_mail', array('id' => (int)$email['id']));
			}
			else {
				throw new Exception(__('The mail send failed'));
			}
		}
		// Error management!
		catch (Exception $e) {
			$retries = $email['attempts'] + 1;
			if ($retries > $config['smtp_queue_retries']) {
				$status = 1;
				insert_event('MAIL_FAILURE', 0, 0, $email['recipient'] . ' - ' . $e->getMessage());
			}
			else  {
				$status = 0;
			}
			$values = array('status' => $status, 'attempts' => $retries);
			$where = array('id' => (int)$email['id']);
			process_sql_update('tpending_mail', $values, $where);
			$to = trim(ascii_output($email['recipient']));
			integria_logwrite(sprintf('SMTP error sending to %s (%s)', $to, $e->getMessage()));
		}
	}
}

/**
 * This function deletes tsesion data with more than X days
 */
function delete_old_audit_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_audit"];
	
	if ($DELETE_DAYS > 0) {
		$limit = strtotime ("now") - ($DELETE_DAYS * 86400);
		$sql = "DELETE FROM tsesion WHERE utimestamp < $limit";
		process_sql ($sql);
	}
}

/**
 * This function deletes tevent data with more than X days. This function doesn't delete workflow events.
 */
function delete_old_event_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_events"];
	
	if ($DELETE_DAYS > 0) {
		$limit = date ("Y/m/d H:i:s", strtotime ("now") - ($DELETE_DAYS * 86400));
		$sql = "DELETE FROM tevent WHERE timestamp < '$limit'
			AND `type` NOT LIKE '%WORKFLOW%'";
		process_sql ($sql);
	}
}

/**
 * This function deletes incidents data with more than X days and closed.
 * Also deletes the data related to the deleted incidents.
 */
function delete_old_incidents () {
	global $config;
	
	//$config['months_to_delete_incidents'] DELETE FROM OTHER PLACES
	$DELETE_DAYS = (int) $config["max_days_incidents"];
	
	if ($DELETE_DAYS > 0) {
		$limit = date ("Y/m/d H:i:s", strtotime ("now") - $DELETE_DAYS * 86400);
		
		$sql_select = "SELECT id_incidencia
					   FROM tincidencia
					   WHERE cierre < '$limit'
						   AND cierre > '0000-00-00 00:00:00'
						   AND estado = 7";
		
		$new = true;
		while ($incident = get_db_all_row_by_steps_sql($new, $result, $sql_select)) {
			$new = false;
			
			$error = false;
			
			// tincident_contact_reporters
			$sql_delete = "DELETE FROM tincident_contact_reporters
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tincident_field_data
			$res = $sql_delete = "DELETE FROM tincident_field_data
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tincident_inventory
			$sql_delete = "DELETE FROM tincident_inventory
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tincident_sla_graph_data
			$sql_delete = "DELETE FROM tincident_sla_graph_data
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tincident_stats
			$sql_delete = "DELETE FROM tincident_stats
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tincident_track
			$sql_delete = "DELETE FROM tincident_track
						   WHERE id_incident = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			// tworkunit
			$workunits =  get_db_all_rows_sql ("SELECT * FROM tworkunit WHERE id IN (SELECT id_workunit FROM tworkunit_incident WHERE id_incident = ".$incident["id_incidencia"].")");
			if ($workunits === false) {
				$workunits = array();
			}
			foreach ($workunits as $workunit) {
				$sql_delete = "DELETE FROM tworkunit WHERE id = ".$workunit["id"];
				$res = process_sql ($sql_delete);
			}
				
			if ($res === false) {
				$error = true;
			}
	
			// tattachment
			$attachments =  get_db_all_rows_sql ("SELECT * FROM tattachment WHERE id_incidencia = ".$incident["id_incidencia"]);
			if ($attachments === false) {
				$attachments = array();
			}

			foreach ($attachments as $attachment) {
				unlink ($config["homedir"].'attachment/'.$attachment['id_attachment'].'_'.$attachment['filename']);
			}

			$sql_delete = "DELETE FROM tattachment
						   WHERE id_incidencia = ".$incident["id_incidencia"];
			$res = process_sql ($sql_delete);
			
			if ($res === false) {
				$error = true;
			}
			
			if (! $error) {
				// tincidencia
				$sql_delete = "DELETE FROM tincidencia
							   WHERE id_incidencia = ".$incident["id_incidencia"];
				process_sql ($sql_delete);
			}
		}
	}
}

/**
 * This function deletes tworkunit data with more than X days that
 * belong to tasks that belong to disabled projects.
 */
function delete_old_wu_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_wu"];
	
	if ($DELETE_DAYS > 0) {
		$limit = date ("Y/m/d H:i:s", strtotime ("now") - ($DELETE_DAYS * 86400));
		$sql = "DELETE FROM tworkunit
				WHERE timestamp < '$limit'
					AND timestamp > '0000-00-00 00:00:00'
					AND id = ANY(SELECT id_workunit
								 FROM tworkunit_task
								 WHERE id_task = ANY(SELECT id
													 FROM ttask
													 WHERE id_project = ANY(SELECT id
																			FROM tproject
																			WHERE disabled = 1
																				AND id > 0)))";
		process_sql ($sql);
	}
}

/**
 * This function deletes ttodo data with more than X days and closed.
 */
function delete_old_wo_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_wo"];
	
	if ($DELETE_DAYS > 0) {
		$limit = date ("Y/m/d H:i:s", strtotime ("now") - ($DELETE_DAYS * 86400));
		$sql = "DELETE FROM ttodo
				WHERE last_update < '$limit'
					AND last_update > '2000-01-01 00:00:00'
					AND progress > 0";
		process_sql ($sql);
	}
}

/**
 * This function deletes tsessions_php data with more than X days
 */
function delete_old_sessions_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_session"];
	
	if ($DELETE_DAYS > 0) {
		$limit = strtotime ("now") - ($DELETE_DAYS * 86400);
		$sql = "DELETE FROM tsessions_php WHERE last_active < $limit";
		process_sql ($sql);
	}
}

/**
 * This function deletes the files uploaded to the files sharing section
 * and its data in tattachment older than X days
 */
function delete_old_fs_files_data () {
	global $config;

	if (!isset($config["max_days_fs_files"]))
		$config["max_days_fs_files"] = 7; // 7 is the default value

	$DELETE_DAYS = (int) $config["max_days_fs_files"];
	
	if ($DELETE_DAYS > 0) {
		require_once($config["homedir"].'/operation/file_sharing/FileSharingPackage.class.php');

		$limit = time() - ($DELETE_DAYS * 86400);

		$sql = sprintf("SELECT id_attachment
						FROM tattachment
						WHERE file_sharing = 1
							AND timestamp < '%s'", date("Y-m-d", $limit));
		$old_files = get_db_all_rows_sql($sql);

		if (!empty($old_files)) {
			foreach ($old_files as $file) {
				$package = new FileSharingPackage($file['id_attachment']);
				$package->delete();
			}
		}
	}
}

/**
 * This function deletes the tattachment_track data older than X days
 */
function delete_old_files_track_data () {
	global $config;

	if (!isset($config["max_days_files_track"]))
		$config["max_days_files_track"] = 15; // 15 is the default value

	$DELETE_DAYS = (int) $config["max_days_files_track"];
	
	if ($DELETE_DAYS > 0) {
		$limit = time() - ($DELETE_DAYS * 86400);
		$sql = sprintf("DELETE FROM tattachment_track WHERE timestamp < '%s'", date("Y-m-d H-i-s", $limit));
		$res = process_sql($sql);
	}
}

/**
 * This function deletes tevent workflow data with more than X days.
 */
function delete_old_workflow_event_data () {
	global $config;
   
	$DELETE_DAYS = (int) $config["max_days_workflow_events"];
	
	if ($DELETE_DAYS > 0) {
		$limit = date ("Y/m/d H:i:s", strtotime ("now") - ($DELETE_DAYS * 86400));
		$sql = "DELETE FROM tevent WHERE timestamp < '$limit'
			AND `type` LIKE '%WORKFLOW%'";
		process_sql ($sql);
	}
}

/**
 * Remove users from projects when none task is assigned in this project
 * 
 * Project managers will not be removed.
 */
function remove_no_task_users_project () {
	
	global $config;
	
	$sql = 'DELETE FROM trole_people_project WHERE 
			ROW(id_user, id_project) NOT IN (
				SELECT id_user, id_project FROM trole_people_task 
				INNER JOIN ttask WHERE trole_people_task.id_task=ttask.id 
				GROUP BY id_user, id_project)
			AND id_role<>1';
			
	process_sql ($sql);
}

/**
 * Move file sharing items to file releases section (attachment/downloads) and 
 * remove it from file sharing section (attachment/file_sharing)
 */
function move_file_sharing_items () {
	global $config;

	$file_sharing_path = $config["homedir"]."attachment/file_sharing/";
	$new_path = $config["homedir"]."attachment/downloads/";

	if (is_dir($file_sharing_path)) {
		if ($dh = opendir($file_sharing_path)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($file_sharing_path . $file) && $file != "." && $file != "..") {
					$file_path = $file_sharing_path . $file . "/";
					if ($dh2 = opendir($file_sharing_path . $file)) {
						while (($file2 = readdir($dh2)) !== false) {
							if ($file2 != "." && $file2 != "..") {
								copy($file_path . $file2, $new_path . $file2);

								$external_id = sha1(random_string(12).date());
								$values = array(
										'name' => $file2,
										'location' => "attachment/downloads/$file2",
										'description' => "Migrated from file sharing",
										'id_category' => 0,
										'id_user' => $config["id_user"],
										'date' => date("Y-m-d H:i:s"),
										'public' => 1,
										'external_id' => $external_id
									);
								process_sql_insert("tdownload", $values);

								unlink($file_path . $file2);
							}
						}
					}
					closedir($dh2);
					rmdir($file_path);
				}
			}
		}
		closedir($dh);
		rmdir($file_sharing_path);
	}

	process_sql ("INSERT INTO tconfig (`token`,`value`) VALUES ('file_sharing_items_moved', 1)");
}

// ---------------------------------------------------------------------------
/* Main code goes here */
// ---------------------------------------------------------------------------

// Crontab control on tconfig, install first run to let user know where is

$installed = get_db_sql ("SELECT COUNT(*) FROM tconfig WHERE `token` = 'crontask'");
$previous = get_db_sql ("SELECT COUNT(*) FROM tconfig WHERE `token` = 'previous_crontask'");
$current_date = date ("Y/m/d H:i:s");
$file_sharing_items_moved = get_db_sql ("SELECT value FROM tconfig WHERE `token` = 'file_sharing_items_moved'");

if ($previous == 0) {
	process_sql ("INSERT INTO tconfig (`token`,`value`) VALUES ('previous_crontask', '$current_date')");
} else {
	$previous_cron_date = get_db_sql ("SELECT `value` FROM tconfig WHERE `token` = 'crontask'");
	process_sql ("UPDATE tconfig SET `value` = '$previous_cron_date' WHERE `token` = 'previous_crontask'");
}

if ($installed == 0){
	process_sql ("INSERT INTO tconfig (`token`,`value`) VALUES ('crontask', '$current_date')");
} else {
	process_sql ("UPDATE tconfig SET `value` = '$current_date' WHERE `token` = 'crontask'");
}

if (!$file_sharing_items_moved) {
	move_file_sharing_items();
}

// Daily check only

if (check_daily_task()){
	run_daily_check ();
}

// Call enterprise crontab

enterprise_include ("include/integria_cron_enterprise.php");

// Execute always (Send pending mails, SMTP)

run_mail_queue();

// Check SLA on active incidents (max. opened time without fixing and min. response)

$incidents = get_db_all_rows_sql ('SELECT * FROM tincidencia
	WHERE sla_disabled = 0
	AND estado NOT IN (7)');

if ($incidents === false)
	$incidents = array ();

if ($incidents)
	foreach ($incidents as $incident) {
		check_sla_min ($incident);
		check_sla_max ($incident);
		check_sla_inactivity ($incident);
		graph_sla($incident);
	}

// Check SLA for number of opened items.

$slas = get_slas (false);
// runs SLAs
foreach ($slas as $sla) {
	// searches the groups which has slas
	$sql = sprintf ('SELECT id_grupo FROM tgrupo WHERE id_grupo != 1 AND id_sla = %d', $sla['id']);
	
	$groups = get_db_all_rows_sql ($sql);
	if ($groups === false)
		$groups = array ();
	
	$noticed_groups = array ();
	
	foreach ($groups as $group) {
		// searches for incidents with SLA of a group
		$sql = sprintf ('SELECT id_incidencia, id_grupo 
			FROM tincidencia WHERE id_grupo = %d
			AND sla_disabled = 0 AND estado NOT IN (6,7)', $group['id_grupo']);
			
		$opened_incidents = get_db_all_rows_sql ($sql);
		
		//count tickes with sla
		if (sizeof ($opened_incidents) <= $sla['max_incidents']) 
			continue;
		
		/* There are too many open incidents */
		foreach ($opened_incidents as $incident) {
			/* Check if it was already notified in a specified time interval */
			$sql = sprintf ('SELECT COUNT(id) FROM tevent
				WHERE type = "SLA_MAX_OPENED_INCIDENTS_NOTIFY"
				AND id_item = %d AND timestamp > "%s"', $incident['id_grupo'], $compare_timestamp);
			
			$notified = get_db_sql ($sql);
			if ($notified > 0)
				continue;
			
			// Notify by mail for max. incidents opened (ONCE) to this 
			// the group email, if defined, if not, to default user.

			if (! isset ($noticed_groups[$incident['id_grupo']])) {
				$noticed_groups[$incident['id_grupo']] = 1;
				$group_name = dame_nombre_grupo ($incident['id_grupo']);
				$subject = "[".$config['sitename']."] Openened ticket limit reached ($group_name)";
				$body = "Opened ticket limit for this group has been exceeded. Please check open tickets.\n";
				if ($sla['enforced'] == 1){
					send_group_email ($incident['id_grupo'], $subject, $body);
					insert_event ('SLA_MAX_OPENED_INCIDENTS_NOTIFY', $incident['id_grupo']);
				} else {
					insert_event ('SLA_MAX_OPENED_INCIDENTS_NOTIFY', $incident['id_grupo']);
				}
			}
		}
	}
}

// Clean temporal directory

$temp_dir = $config["homedir"]."/attachment/tmp";
delete_all_files_in_dir ($temp_dir);

// Update the incident statistics

incidents_update_stats_data();

?>
