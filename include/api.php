<?php
// INTEGRIA IMS v2.1
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

if (!file_exists("config.php")){
	echo "ERROR: Cannot open config.php";
	exit;
}

include_once ("config.php");

require_once ($config["homedir"].'/include/functions_api.php');

//Get the parameters and parse if necesary.
$ip_origin = $_SERVER['REMOTE_ADDR'];
$op = get_parameter('op', false);
$params = get_parameter('params', '');
$token = get_parameter('token', ',');
$user = get_parameter('user', false);
$pass = get_parameter('pass', '');
$user_pass = get_parameter('user_pass', '');
$return_type = get_parameter('return_type', 'csv');

$info = get_parameter('info', false);
if ($info == "version") {
	if ($config["enteprise"] == 1)
		$enterprise = "Enterprise Edition";
	else
		$enterprise = "OpenSource Edition";
	if (!$config["minor_release"])
		$config["minor_release"] = 0;

	echo "Integria IMS $enterprise ".$config["version"]." Build ".$config["build_version"]." MR".$config["minor_release"];
	exit;
}

$api_password = get_db_value_filter('value', 'tconfig', array('token' => 'api_password'));

$correct_login = false;

if (ip_acl_check ($ip_origin)){
	$user_stored_pass = get_db_value ("password", "tusuario", "id_usuario", $user);
	if (md5 ($user_pass) === $user_stored_pass){
		if (!empty($api_password)) {
			if ($pass === $api_password) {
				$correct_login = true;
			}
		} else {
			$correct_login = true;
		}
	}
}

if(!$correct_login) {
	sleep(15);
	exit;
}

switch ($op){

	case "create_lead":
	{
		$params = explode($token, $params);
		echo api_create_lead ($return_type, $user, $params);
		break;
	}

	case "create_user":
	{
		$params = explode($token, $params);
		echo api_create_users ($return_type, $user, $params);
		break;
	}

	case "get_incidents":
	{
		$params = explode($token, $params);
		echo api_get_incidents ($return_type, $user, $params);
		break;
	}

	case "get_incident_details":
	{
		echo api_get_incident_details ($return_type, $user, $params);
		break;
	}
	case "create_incident":
	{
		$params = explode($token, $params);
		api_create_incident ($return_type, $user, $params);
		break;
	}
	case "update_incident":
	{
		$params = explode($token, $params);
		echo api_update_incident ($return_type, $user, $params);
		break;
	}
	case "delete_incident":
	{
		echo api_delete_incident ($return_type, $user, $params);
		break;
	}
	case "get_incident_workunits":
	{
		echo api_get_incident_workunits ($return_type, $user, $params);
		break;
	}
	case "create_workunit":
	{
		$params = explode($token, $params);
		api_create_incident_workunit ($return_type, $user, $params);
		break;
	}
	case "get_incident_files":
	{
		echo api_get_incident_files ($return_type, $user, $params);
		break;
	}
	case "download_file":
	{
		echo api_download_file ($return_type, $user, $params);
		break;
	}
	case "attach_file":
	{
		$params = explode($token, $params);
		echo api_attach_file ($return_type, $user, $params);
		break;
	}
	case "delete_file":
	{
		echo api_delete_file ($return_type, $user, $params);
		break;
	}
	case "get_incident_tracking":
	{
		echo api_get_incident_tracking ($return_type, $user, $params);
		break;
	}
	case "get_incidents_resolutions":
	{
		echo api_get_incidents_resolutions ($return_type, $user);
		break;
	}
	case "get_incidents_status":
	{
		echo api_get_incidents_status ($return_type, $user);
		break;
	}
	case "get_groups":
	{
		echo api_get_groups ($return_type, $user, $params);
		break;
	}
	case "get_users":
	{
		echo api_get_users ($return_type, $user);
		break;
	}
	case "get_stats":
	{
		echo api_get_stats ($return_type, $params, $token, $user);
		break;
	}
	case "get_inventories":
	{
		echo api_get_inventories ($return_type, $user, $params);
		break;
	}
	case "validate_user":
		$params = explode($token, $params);
		echo api_validate_user ($return_type, $user, $params);
		break;
	case "get_last_cron_execution":
	{
		echo api_get_last_cron_execution ($return_type, $user, $params);
		break;
	}
	case "get_previous_cron_execution":
	{
		echo api_get_previous_cron_execution ($return_type, $user, $params);
		break;
	}
	case "get_num_queued_emails":
	{
		echo get_num_queued_emails ($return_type, $user, $params);
		break;
	}
	case "get_last_invoice_id":
	{
		echo api_get_last_invoice_id($return_type);
		break;
	}
	case "get_invoice":
	{
		echo api_get_invoice($return_type, $params);
		break;
	}
	case "create_invoice":
	{
		$params = explode($token, $params);
		echo api_create_invoice($return_type, $params);
		break;
	}
	case "create_company":
	{
		$params = explode($token, $params);
		echo api_create_company($return_type, $params);
		break;
	}
	case "user_exists":
	{
		echo api_get_user_exists($return_type, $params);
		break;
	}
	case "delete_user":
	{
		echo api_delete_user($return_type, $params);
		break;
	}
	case "mark_created_incident":
	{
		$params = explode($token, $params);
		echo api_mark_created_incident ($return_type, $params);
		break;
	}
	case "mark_updated_incident":
	{
		$params = explode($token, $params);
		echo api_mark_updated_incident ($return_type, $params);
		break;
	}
	case "ovo_manager":
	{
		$params = explode($token, $params);
		echo api_ovo_manager ($return_type, $params);
		break;
	}
	default: 
	{
	}
	sleep(15);
}
?>
