<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once ('../../include/config.php');
require_once ('../../include/functions.php');
require_once ('../../include/functions_db.php');

global $config;

session_start();

if (isset($_SESSION["id_usuario"]))
	$config["id_user"] = $_SESSION['id_usuario'];
else {
    audit_db("",$config["REMOTE_ADDR"], "ACL Violation","Trying to access Downloads");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

check_login();

$id_user = $config["id_user"];
$id_attachment = get_parameter ("id_attachment", 0);

$data = get_db_row ("tattachment", "id_attachment", $id_attachment);

if (!isset($data)){
    echo "No valid attach id";
    exit;
}

$id_kb = $data["id_kb"];

if (! give_acl ($config["id_user"], 0, "KR")){
    echo "You dont have access to Knoledgue base files";
    exit;
}

// Beware of users trying to get access to attach of Incidents or Projects from here!

if ($id_kb == 0){
    echo "You dont have access to that file";
    exit;
}


// Allow download file

$fileLocation = $config["homedir"]."/attachment/".$data["id_attachment"]."_".$data["filename"];

$last_name = $data["filename"];

if (file_exists($fileLocation)){
	header('Content-type: aplication/octet-stream;');
	header('Content-type: ' . returnMIMEType($fileLocation) . ';');
	header("Content-Length: " . filesize($fileLocation));
	header('Content-Disposition: attachment; filename="' . $last_name . '"');
	readfile($fileLocation);

} else {
	echo "<h1>Error locating file</h1>";
	echo "<i>".$fileLocation."</i>";
	echo "File is missing in disk storage. Please contact the administrator";
	exit;
}


?>
