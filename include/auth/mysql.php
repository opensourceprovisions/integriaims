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


// Integria uses icons from famfamfam, licensed under CC Atr. 2.5
// Silk icon set 1.3 (cc) Mark James, http://www.famfamfam.com/lab/icons/silk/
// Integria uses Pear Image::Graph code
// Integria shares much of it's code with project Babel Enterprise and Pandora FMS,
// also a Free Software Project coded by some of the people who makes Integria.

/**
 * @package Include/auth
 */

if (!isset ($config)) {
	die ('
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="../../images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="../styles/pandora.css" type="text/css">
</head>
<body>
<div id="main" style="float:left; margin-left: 100px">
<div align="center">
<div id="login_f">
	<h1 id="log_f" class="error">You cannot access this file</h1>
	<div>
		<img src="../../images/pandora_logo.png" border="0"></a>
	</div>
	<div class="msg">
		<span class="error"><b>ERROR:</b>
		You can\'t access this file directly!</span>
	</div>
</div>
</div>
</body>
</html>
');
}

//include_once($config['homedir'] . "/include/functions_profile.php");
enterprise_include ('include/auth/mysql.php');

$config["user_can_update_info"] = true;
$config["user_can_update_password"] = true;
$config["admin_can_add_user"] = true;
$config["admin_can_delete_user"] = true;
$config["admin_can_disable_user"] = false; //currently not implemented
$config["admin_can_make_admin"] = true;

/**
 * Get the user id field on a mixed structure.
 *
 * This function is needed to make auth system more compatible and independant.
 *
 * @param mixed User structure to get id. It might be a row returned from
 * tusuario or tusuario_perfil. If it's not a row, the int value is returned.
 *
 * @return int User id of the mixed parameter.
 */
function get_user_id ($user) {
	if (is_array ($user)){
		if (isset ($user['id_user']))
			return $user['id_user'];
		elseif (isset ($user['id_usuario']))
			return $user['id_usuario'];
		else
			return false;
	} else {
		return $user;
	}
}

/**
 * Gets a Users info
 * 
 * @param mixed User id
 *
 * @return mixed An array of users
 */
function get_user_info ($user) {
	return get_db_row ("tusuario", "id_usuario", get_user_id ($user));
}


/** 
 * Checks if a user is administrator.
 * 
 * @param string User id.
 * 
 * @return bool True is the user is admin
 */
/*function is_user_admin ($id_user) {
	/* This code below was here, but I don't understand WHY. This always returns TRUE ¿?¿?

	static $is_admin = -1;
	
	if ($is_admin !== -1)
		return $is_admin;
	*/

/*	$is_admin = (bool) db_get_value ('is_admin', 'tusuario', 'id_user', $id_user);
	return $is_admin;
}*/

/** 
 * Check is a user exists in the system
 * 
 * @param mixed User id.
 * 
 * @return bool True if the user exists.
 */
function is_user ($user) {
	$user = get_db_row('tusuario', 'id_usuario', get_user_id ($user));
	if (! $user)
		return false;
	return true;
}

/**
 * Authenticate against an LDAP server.
 *
 * @param string User login
 * @param string User password (plain text)
 *
 * @return bool True if the login is correct, false in other case
 */
function ldap_process_user_login ($login, $password) {
	global $config;

	if (! function_exists ("ldap_connect")) {
		$config["auth_error"] = 'Your installation of PHP does not support LDAP';
		return false;
	}

	// Connect to the LDAP server
	$ds = @ldap_connect ($config["ldap_server"], $config["ldap_port"]);

	if (!$ds) {
		$config["auth_error"] = 'Error connecting to LDAP server';
		return false;
	}

	// Set the LDAP version
	ldap_set_option ($ds, LDAP_OPT_PROTOCOL_VERSION, $config["ldap_version"]);

	if ($config["ldap_start_tls"]) {
		if (!@ldap_start_tls ($ds)) { 
			$config["auth_error"] = 'Could not start TLS for LDAP connection';
			@ldap_close ($ds);
			return false;
		}
	}

	if (strlen($password) == 0 || !@ldap_bind ($ds, $config["ldap_login_attr"]."=".$login.",".$config["ldap_base_dn"], $password)) {
		$config["auth_error"] = 'User not found in database or incorrect password';
		@ldap_close ($ds);
		return false;
	}

	@ldap_close ($ds);
	return true;
}

/**
 * Checks if a user is in the autocreate blacklist.
 *
 * @param string User
 *
 * @return bool True if the user is in the blacklist, false otherwise.
 */
function is_user_blacklisted ($user) {
	global $config;
	
	$blisted_users = explode (',', $config['autocreate_blacklist']);
	foreach ($blisted_users as $blisted_user) {
		if ($user == $blisted_user) {
			return true;
		}
	}
	
	return false;
}


/**
 * Create a new user
 *
 * @return bool false
 */
function create_user ($id_user, $password, $user_info) {
	$values = $user_info;
	$values["id_usuario"] = $id_user;
	$values["password"] = md5 ($password);
	//$values["last_connect"] = 0;
	$values["fecha_registro"] = get_system_time ();

	return (@process_sql_insert ("tusuario", $values)) !== false;
}


/**
 * process_user_login accepts $login and $pass and handles it according to current authentication scheme
 *
 * @param string $login 
 * @param string $pass
 *
 * @return mixed False in case of error or invalid credentials, the username in case it's correct.
 */
function process_user_login ($login, $pass) {
	global $config, $mysql_cache;
	include_once($config['homedir'] . "/include/functions_profile.php");

	// Always authenticate admins against the local database
	if (strtolower ($config["auth_methods"]) == 'mysql'|| dame_admin ($login)) {	
		$sql = sprintf ("SELECT `id_usuario`, `password` FROM `tusuario` WHERE `disabled` = 0 AND `id_usuario` = '%s' AND `enable_login` = 1", $login);
		
		$row = get_db_row_sql ($sql);
		//Check that row exists, that password is not empty and that password is the same hash
		if ($row !== false && $row["password"] !== md5 ("") && $row["password"] == md5 ($pass)) {
			// Login OK
			// Nick could be uppercase or lowercase (select in MySQL
			// is not case sensitive)
			// We get DB nick to put in PHP Session variable,
			// to avoid problems with case-sensitive usernames.
			// Thanks to David Muñiz for Bug discovery :)
			return $row["id_usuario"];
		}
		else {
			$mysql_cache["auth_error"] = "User not found in database or incorrect password";
		}

		return false;

	// Remote authentication
	}
	else {
		
		switch ($config["auth_methods"]) {
			
			// LDAP
			case 'ldap':

				$sql = sprintf ("SELECT `disabled` FROM `tusuario` WHERE `id_usuario` = '%s'", $login);
				$disabled = get_db_sql ($sql);

				// Check if user is disabled
				if ($disabled == 1){
					$config["auth_error"] = "User not found in database or incorrect password";
					return false;
				}
				
				if (ldap_process_user_login ($login, $pass) === false) {
					$config["auth_error"] = "User not found in database or incorrect password";
					return false;
				}
				break;
				
			// Active Directory
			case 'ad':
				if (enterprise_hook ('ad_process_user_login', array ($login, $pass)) === false) {
					return false;
				}
				break;

			// Remote Pandora FMS
			/* case 'pandora':
				
				break;

			// Remote Babel Enterprise
			case 'babel':
				
				break;

			// Remote Integria
			case 'integria':
				
				break; */

			// Unknown authentication method
			default:
				$config["auth_error"] = "User not found in database or incorrect password";
				return false;
		}
		
		// Authentication ok, check if the user exists in the local database
		if (is_user ($login)) {
			return $login;
		}

		// The user does not exist and can not be created
		if ($config['autocreate_remote_users'] == 0 || is_user_blacklisted ($login)) {
			$config["auth_error"] = "Ooops User not found in database or incorrect password";
			return false;
		}

		// Create the user in the local database
		if (create_user ($login, $pass, array ('nombre_real' => $login, 'comentarios' => 'Imported from ' . $config['auth_methods'])) === false) {
			$config["auth_error"] = "User not found in database or incorrect password";
			return false;
		}

		profile_create_user_profile ($login, $config['default_remote_profile'], $config['default_remote_group']);	
		return $login;
	}

	return false;
}

/**
 * Update the password in MD5 for user pass as id_user with
 * password in plain text.
 * 
 * @param string user User ID
 * @param string password Password in plain text.
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function update_user_password ($user, $password_new) {
	return process_sql_update ('tusuario',
		array ('password' => md5 ($password_new)),
		array ('id_usuario' => $user));
}

?>
