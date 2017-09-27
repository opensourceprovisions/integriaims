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


global $config;

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access","Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

$id = get_parameter ("id",0);

$id_incident = get_parameter ("id_incident", 0);
$title = get_parameter ("title","");

// ********************************************************************
// Note detail of $id_note
// ********************************************************************

// If WU comes from an incident, get data from incident
if ($id_incident != 0){
	echo "<h3>";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident&id=$id_incident'>";
	echo __("Workunit detail for Incident #") . $id_incident;
	echo " - ". get_incident_title ($id_incident);
	echo "</a></h3>";
}
else {
	echo "<h2>$title</h2>"; 
}

$sql4='SELECT * FROM tworkunit WHERE id = '.$id;
$res4=mysql_query($sql4);
if ($row3=mysql_fetch_array($res4)){

	echo "<div class='notetitle'>"; // titulo

	$timestamp = $row3["timestamp"];
	$duration = $row3["duration"];
	$id_user = $row3["id_user"];
	$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_user);
	$description = $row3["description"];

	// Show data
	echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
	echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_user'>";
	echo $id_user;
	echo "</a>";
	echo ' '.__('said on').' '.$timestamp;
	echo "</div>";

	// Body
	echo "<div class='notebody'>";
	echo clean_output_breaks($description);
	echo "</div>";
} else 
	echo __('No data available');

?>
