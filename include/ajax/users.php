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

global $config;

$search_users = (bool) get_parameter ('search_users');
$search_users_ticket = (bool) get_parameter ('search_users_ticket');
$search_users_role = (bool) get_parameter ('search_users_role');
$get_group_info = (bool) get_parameter ('get_group_info');
$get_user_company = (bool) get_parameter ('get_user_company');
$delete_users = (bool) get_parameter ('delete_users');
$enable_users = (bool) get_parameter ('enable_users');
$disable_users = (bool) get_parameter ('disable_users');
$noaccess_table = (bool) get_parameter ('noaccess_table');

if ($search_users) {
	require_once ('include/functions_db.php');

	$id_user = $config['id_user'];
	$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
	$users = get_user_visible_users ($config['id_user'],"IR", false);
	
	if ($users === false)
		return;
		
	$res = array();
	
	foreach ($users as $user) {
		if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
			array_push($res, array("label" => safe_output($user['nombre_real'])." (".safe_output($user['id_usuario']).")", "value" => safe_output($user['id_usuario'])));
		}
	}
	
	echo json_encode($res);
	
	return;
}

if ($search_users_ticket) {
	require_once ('include/functions_db.php');

	$id_user = $config['id_user'];
	$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
	$id_group = (int) get_parameter('id_group');

	//~ $users = users_get_users_owners_or_creators ($config['id_user']);
	$users = users_get_users_owners_or_creators ($config['id_user'], $id_group);
	
	if ($users === false)
		return;
	
	$res = array();
	
	foreach ($users as $user) {
		if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
			array_push($res, array("label" => safe_output($user['nombre_real'])." (".safe_output($user['id_usuario']).")", "value" => safe_output($user['id_usuario'])));
		}
	}
	
	echo json_encode($res);
	
	return;
}

if ($search_users_role) {
	require_once ('include/functions_db.php');
	
	$id_project = (int) get_parameter ('id_project');
	$id_user = $config['id_user'];
	$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
	
	$users = get_users_project ($id_project);
	
	if ($users === false)
		return;

	$res = array();
	
	foreach ($users as $user) {
		if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
			array_push($res, array("label" => safe_output($user['nombre_real'])." (".$user['id_usuario'].")", "value" => $user['id_usuario']));
		}
	}
	
	echo json_encode($res);
	
	return;
}

if ($get_group_info) {
	$group = get_parameter("group");
	
	$res = get_db_row ("tgrupo", "id_grupo", $group);
	
	echo json_encode($res);
}

if ($get_user_company) {
	$id_user = get_parameter("id_user");
	$company = get_user_company ($id_user, false);
	if ($company != false) {
		$company['name'] = safe_output($company['name']);
	}
	
	echo json_encode($company);
	return;
}

if ($delete_users) {
	if (give_acl ($config["id_user"], 0, "UM")) {
		$users = explode(",", get_parameter("ids"));
		
		foreach ($users as $user) {
			if ($config["enteprise"] == 1){
				process_sql("DELETE FROM tusuario_perfil WHERE id_usuario = '$user'");
			}

			// Delete trole_people_task entries 
			process_sql("DELETE FROM trole_people_task WHERE id_user = '$user'");

			// Delete trole_people_project entries
			process_sql("DELETE FROM trole_people_project WHERE id_user = '$user'");	

			$result += process_sql("DELETE FROM tusuario WHERE id_usuario = '$user'");
		}
	}
	
	echo json_encode($result);
}

if ($enable_users) {
	if (give_acl ($config["id_user"], 0, "UM")) {
		$users = explode(",", get_parameter("ids"));
		
		foreach ($users as $user) {
			$result += process_sql("UPDATE tusuario SET disabled = 0 WHERE id_usuario = '$user'");
		}
	}
	
	echo json_encode($result);
}

if ($disable_users) {
	if (give_acl ($config["id_user"], 0, "UM")) {
		$users = explode(",", get_parameter("ids"));
		
		foreach ($users as $user) {
			$result += process_sql("UPDATE tusuario SET disabled = 1 WHERE id_usuario = '$user'");
		}
	}
	
	echo json_encode($result);
}

if ($noaccess_table) {
	$noacces = "";

	$noacces .= "<table>";
		$noacces .= "<tr>";
			$noacces .= "<td>";
				$noacces .= "<img style='float:left;' src='".$config["base_url"]."/images/icono_lock_grande.png'>";
			$noacces .= "</td>";
			$noacces .= "<td style='padding-left:10px;'>";
				$noacces .= '<h2 style="font-family: Verdana; font-size:16px !important; font-weight: bold; margin-top:2px;">'.__('You don\'t have access to this page').'</h2>';

				$noacces .= "<p style='font-family: Verdana; font-size:14px; font-weight: normal; line-height: 1.5em;'>" . 
						__('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br>Please know that all attempts to access this page are recorded in security logs of Integria System Database.');
				$noacces .= "</p>";
			$noacces .= "</td>";
		$noacces .= "</tr>";
	$noacces .= "</table>";

	echo $noacces;

	return;
}

?>
 	
