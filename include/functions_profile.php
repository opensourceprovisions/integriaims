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


/**
 * Create Profile for User
 *
 * @param string User ID
 * @param int Profile ID (default 1 => AR)
 * @param int Group ID (default 1 => All)
 * @param string Assign User who assign the profile to user.
 *
 * @return mixed Number id if succesful, false if not
 */
function profile_create_user_profile ($id_user, $id_profile = 1, $id_group = 0, $assignUser = false) {
	global $config;

	if (empty ($id_profile) || $id_group < 0)
	return false;

	if (isset ($config["id_usuario"])) {
		//Usually this is set unless we call it while logging in (user known by auth scheme but not by pandora)
		$assign = $config["id_usuario"];
	} else {
		$assign = $id_user;
	}

	if ($assignUser !== false)
	$assign = $assignUser;

	$insert = array (
		"id_usuario" => $id_user,
		"id_perfil" => $id_profile,
		"id_grupo" => $id_group,
		"assigned_by" => $assign
	);

	return process_sql_insert ("tusuario_perfil", $insert);
}


/**
 * Selects all profiles (array (id => name)) or profiles filtered
 *
 * @param mixed Array with filter conditions to retrieve profiles or false.  
 *
 * @return array List of all profiles
 */
function profile_get_profiles ($filter = false) {
	if ($filter === false) { 
		$profiles = get_db_all_rows_in_table ("tprofile", "name");
	}
	else {
		$profiles = get_db_all_rows_filter ("tprofile", $filter);
	}
	$return = array ();
	if ($profiles === false) {
		return $return;
	}
	foreach ($profiles as $profile) {
		$return[$profile["id"]] = $profile["name"];
	}
	return $return;
}


?>
