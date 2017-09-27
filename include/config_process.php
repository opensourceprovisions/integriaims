<?php
error_reporting(E_ALL ^ E_DEPRECATED);
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

// Integria version
$config["build"]="161223";
$config["version"]="5.0";
$config["build_version"] = $config["build"];

if (!isset($_SERVER["REMOTE_ADDR"]))
    $config["REMOTE_ADDR"] = "command_line";
else
    $config["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];

// Set specific session name for this instance
session_name (md5($config["build"].$config["REMOTE_ADDR"].$config["dbpass"].$config["dbname"]));

if (! defined ('ENTERPRISE_DIR'))
	define ('ENTERPRISE_DIR', 'enterprise', FALSE);

//Calculate homedir variable to allow the load of all libs
$path = dirname (__FILE__);
$path2 = str_replace('\\','/',$path);
$array_path = explode("/", $path2);
$last = array_pop($array_path);

$last = array_pop($array_path);

$config["base_url_dir"]="/".$last."/";

$config["homedir"] = join("/", $array_path);

$config["homedir"] .= $config["base_url_dir"];

if (!isset($config['baseurl']))
	$config["baseurl"] = $config["base_url_dir"];

// Detect enterprise version
// NOTE: If you override this value without enterprise code, you will break 
// the code and get several problems!

if (file_exists($config["homedir"]."/".ENTERPRISE_DIR."/include/functions_db.php"))
	$config["enteprise"] = 1;
else
	$config["enteprise"] = 0;

if (! defined ('EXTENSIONS_DIR'))
	define ('EXTENSIONS_DIR', 'extensions');

require_once ($config["homedir"]."/include/functions_extensions.php");
// Fill an array with data of the extensions
$config["extensions"] = extensions_get_extensions ();

// Read remaining config tokens from DB
if (! mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"])) {
	include ($config["homedir"]."/general/error_databaseconnect.php");
	exit;
}

mysql_select_db ($config["dbname"]);

require_once ($config["homedir"].'/include/functions.php');
require_once ($config["homedir"].'/include/functions_db.php');
require_once ($config["homedir"].'/include/functions_config.php');
require_once ($config["homedir"].'/include/streams.php');
require_once ($config["homedir"].'/include/gettext.php');
require_once ($config["homedir"].'/include/constants.php');

// Load config from database
load_config();

// Activate log on disk for errors and other information
if ($config["error_log"] == 1){
	error_reporting(E_ALL & ~E_NOTICE);
	ini_set("display_errors", 0);
	ini_set("error_log", $config["homedir"]."/integria.log");
}

$l10n = NULL;

config_prepare_session();
require_once ($config["homedir"].'/include/load_session.php');
session_start();

global $develop_bypass;

/* Help to debug problems. Override global PHP configuration */
if (!isset($develop_bypass)) $develop_bypass = 0;

if ($develop_bypass) {

	// Develop mode, show all notices and errors on Console (and log it)
	if (version_compare(PHP_VERSION, '5.3.0') >= 0)
	{
		error_reporting(E_ALL & ~E_DEPRECATED);
	}
	else
	{
		error_reporting(E_ALL);
	}
	ini_set("display_errors", 1);

}

// User language selection prevails over system-wide defined language.

if (isset ($_SESSION['id_usuario'])){
	$temp = get_db_value ('lang', 'tusuario', 'id_usuario', $_SESSION['id_usuario']);
    if ($temp != "")
        $config['language_code'] = $temp;
}

if (isset ($_GET['language'])){
    $config['language_code'] = $_GET['language'];
}

if (file_exists ($config["homedir"].'/include/languages/'.$config['language_code'].'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ($config["homedir"].'/include/languages/'.$config['language_code'].'.mo'));
	$l10n->load_tables ();
}

// Set a the system timezone default 
if ((!isset($config["timezone"])) OR ($config["timezone"] == "")){
        $config["timezone"] = "Europe/Berlin";
}

if ((!isset($config["ticket_owner_is_creator"])) OR ($config["ticket_owner_is_creator"] == "")){
        $config["ticket_owner_is_creator"] = 1;
}

date_default_timezone_set($config["timezone"]);

include_once ($config["homedir"]."/include/functions_html.php");
include_once ($config["homedir"]."/include/functions_form.php");
include_once ($config["homedir"]."/include/functions_calendar.php");

// Function include_graphs_dependencies() it's called in the code below
require_once("include_graph_dependencies.php");

include_graphs_dependencies($config['homedir'].'/');



//Compound base url
$protocol = "http";
if ($config["access_protocol"]) {
	$protocol = "https";
}

$port = "";

if ($config["access_port"]) {
	$port = ":".$config["access_port"];
}

$server_addr = $config["access_public"]; 
if (isset($_SERVER["SERVER_NAME"])) {
	$server_addr = $_SERVER["SERVER_NAME"];
}

$config["base_url"] = $protocol."://".$server_addr.$port.$config["baseurl"].'/';
$config["base_url_images"] = "//".$server_addr.$port.$config["baseurl"].'/';

//Compound public url
$config["public_url"] = $protocol."://".$config["access_public"].$port.$config["baseurl"];

// Beware: DONT LET BLANK LINES AFTER PHP END CODE MARK BELOW !!
?>
