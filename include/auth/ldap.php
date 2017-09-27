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
 
 include_once("include/functions_profile.php");
 
 
$config["user_can_update_info"] = false;
$config["user_can_update_password"] = false;
$config["admin_can_add_user"] = false;
$config["admin_can_delete_user"] = false;
$config["admin_can_disable_user"] = false; //Not implemented
$config["admin_can_make_admin"] = false;

//Required and optional keys for this function to work
$req_keys = array ("ldap_server", "ldap_base_dn", "ldap_login_attr", "ldap_admin_group_name", "ldap_admin_group_attr", "ldap_admin_group_type", "ldap_user_filter", "ldap_user_attr");
$opt_keys = array ("ldap_port", "ldap_start_tls", "ldap_version", "ldap_admin_dn", "ldap_admin_pwd");

global $ldap_cache; //Needs to be globalized because config_process_config () function calls this file first and the variable would be local and subsequently lost
$ldap_cache = array ();
$ldap_cache["error"] = "";
$ldap_cache["ds"] = "";

//Put each required key in a variable.
foreach ($req_keys as $key) {
	if (!isset ($config["auth_methods"][$key])) {
		user_error ("Required key ".$key." not set", E_USER_ERROR);
	}
}

// Convert group name to lower case to prevent problems
$config["auth_methods"]["ldap_admin_group_attr"] = strtolower ($config["auth_methods"]["ldap_admin_group_attr"]);
$config["auth_methods"]["ldap_admin_group_type"] = strtolower ($config["auth_methods"]["ldap_admin_group_type"]);

foreach ($opt_keys as $key) {
	if (!isset ($config["auth_methods"][$key])) {
		switch ($key) {
			case "ldap_start_tls":
				$config["auth_methods"][$key] = false;
				continue;
			case "ldap_version":
				$config["auth_methods"][$key] = 0;
				continue;
			case "ldap_admin_dn":
			case "ldap_admin_pwd":	
				$config["auth_methods"][$key] = "";
				continue;
			default:
				//Key not implemented
				continue;
		}
	}
}

//Reference the global use authorization error to last ldap error.
$config["auth_error"] = &$ldap_cache["error"];

unset ($req_keys, $opt_keys);


/**
 * Function to validate the user and password for a given login. Error messages in $ldap_cache["error"];
 *
 * @param string User login
 * @param string User password (plain text)
 *
 * @return bool True if the login is correct, false in other case
 */
function ldap_valid_login ($login, $password) {
	global $ldap_cache, $config;

	if (! function_exists ("ldap_connect")) {
		die ("Your installation of PHP does not support LDAP");
	}
	
	$ret = false;
	if (!empty ($config["auth_methods"]["ldap_port"])) {
		$ds = @ldap_connect ($config["auth_methods"]["ldap_server"], $config["auth_methods"]["ldap_port"]); //Since this is a separate bind, we don't store it global
	} else {
		$ds = @ldap_connect ($config["auth_methods"]["ldap_server"]); //Since this is a separate bind we don't store it global
	}
	if ($ds) {
		if ($config["auth_methods"]["ldap_version"] > 0) {
			ldap_set_option ($ds, LDAP_OPT_PROTOCOL_VERSION, $config["auth_methods"]["ldap_version"]);
		}
		
		if ($config["auth_methods"]["ldap_start_tls"] && !@ldap_start_tls ($ds)) {
			$ldap_cache["error"] .= 'Could not start TLS for LDAP connection';
			return $ret;
		}
		
		if (ldap_search_user ($login)) {
			$r = @ldap_bind ($ds, $config["auth_methods"]["ldap_login_attr"]."=".$login.",".$config["auth_methods"]["ldap_base_dn"], $password);
			if (!$r) {
				$ldap_cache["error"] .= 'Invalid login';
				//$ldap_cache["error"] .= ': incorrect password'; // uncomment for debugging
			} else {
				$ret = true;
			}
		} else {
			$ldap_cache["error"] .= 'Invalid login';
			//$ldap_cache["error"] .= ': no such user';
		}
		@ldap_close ($ds);
	} else {
		$ldap_cache["error"] .= 'Error connecting to LDAP server';
	}
	return $ret;
}

/**
 * Function to search the dn for a given user. Error messages in $ldap_cache["error"];
 *
 * @param string User login
 *
 * @return mixed The DN if the user is found, false in other case
 */
function ldap_search_user ($login) {
	global $ldap_cache, $config;
	
	$nick = false;
	if (ldap_connect_bind ()) {
		$sr = @ldap_search ($ldap_cache["ds"], $config["auth_methods"]["ldap_base_dn"], "(&(".$config["auth_methods"]["ldap_login_attr"]."=".$login.")".$config["auth_methods"]["ldap_user_filter"].")", array_values ($config["auth_methods"]["ldap_user_attr"]));
		
		if (!$sr) {
			$ldap_cache["error"] .= 'Error searching LDAP server: ' . ldap_error ($ldap_cache["ds"]);
		} else {
			$info = @ldap_get_entries ($ldap_cache["ds"], $sr );
			if ( $info['count'] != 1 ) {
				$ldap_cache["error"] .= 'Invalid user';
			} else {
				$nick = $info[0]['dn'];
			}
			@ldap_free_result ($sr);
		}
		@ldap_close ($ldap_cache["ds"]);
	}
	return $nick;
}



/**
 * Connects and binds to the LDAP server
 * Tries to connect as $config["auth"]["ldap_admin_dn"] if we set it.
 * @return boolean Bind result or false
 */
function ldap_connect_bind () {
	global $ldap_cache, $config;
	
	if (! function_exists ('ldap_connect')) {
		die ('Your installation of PHP does not support LDAP');
	}
	
	$ret = false;
	
	if (!empty ($config["auth_methods"]["ldap_port"]) && !is_resource ($ldap_cache["ds"])) {
		$ldap_cache["ds"] = @ldap_connect ($config["auth_methods"]["ldap_server"], $config["auth_methods"]["ldap_port"]);
	} elseif (!is_resource ($ldap_cache["ds"])) {
		$ldap_cache["ds"] = @ldap_connect ($config["auth_methods"]["ldap_server"]);
	} else {
		return true;
	}
	
	if ($ldap_cache["ds"]) {
		if (!empty ($config["auth_methods"]["ldap_version"])) {
			ldap_set_option($ldap_cache["ds"], LDAP_OPT_PROTOCOL_VERSION, $config["auth_methods"]["ldap_version"]);
		}
		
		if (!empty ($config["auth_methods"]["ldap_start_tls"])) {
			if (!ldap_start_tls ($ldap_cache["ds"])) {
				$ldap_cache["error"] .= 'Could not start TLS for LDAP connection';
				return $ret;
			}
		}

		if (!empty ($config["auth_methods"]["ldap_admin_dn"])) {
			$r = @ldap_bind ($ldap_cache["ds"], $config["auth_methods"]["ldap_admin_dn"], $config["auth_methods"]["ldap_admin_pwd"]);
		} else {
			$r = @ldap_bind ($ldap_cache["ds"]);
		}
		
		if (!$r) {
			$ldap_cache["error"] .= 'Invalid bind login for LDAP Server or (in case of OpenLDAP 2.x) could not connect';
			return $ret;
		}
		return true;
	} else {
		$ldap_cache["error"] .= 'Error connecting to LDAP server';
		return $ret;
	}
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
	if (!ldap_valid_login ($login, $pass)) {
		return false;
	} 
	global $config;
		
	$profile = get_db_value ("id_usuario", "tusuario_perfil", "id_usuario", $login);
	
	if ($profile === false && empty ($config["auth_methods"]["create_user_undefined"])) {
		$config["auth_error"] = "No profile"; //Error message, don't translate
		return false; //User doesn't have a profile so doesn't have access
	} elseif ($profile === false && !empty ($config["auth_methods"]["create_user_undefined"])) {
		$ret = profile_create_user_profile ($login); //User doesn't have a profile but we are asked to create one
		if ($ret === false) {
			$config["auth_error"] = "Profile creation failed"; //Error message, don't translate
			return false; //We couldn't create the profile for some or another reason
		}
	}
	
	return $login;
}


?>
