<?php
// Integria IMS - The ITIL Management System
// =========================================
// Copyright (c) 2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars

global $config;

check_login ();

$id_user = $_SESSION['id_usuario'];
$id_project = get_parameter ("id_project", -1);
$id_task = get_parameter ("id_task", -1);
$operation = get_parameter ("operation", "");
// Get names
if ($id_project != 1)
	$project_name = get_db_value ("name", "tproject", "id", $id_project);
else
	$project_name = "";

if ($id_task != 1)
	$task_name = get_db_value ("name", "ttask", "id", $id_task);
else
	$task_name = "";

if ($id_project == -1) {
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task information without a valid project");
	include ("general/noaccess.php");
	exit;
}
// ACL
$task_access = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (! $task_access["read"]) {
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task information without task permission");
	no_permission();
}

// Specific task
if ($id_task != -1){ 
	$sql= "SELECT * FROM tincidencia WHERE id_task = $id_task";
	$incidents = get_db_all_rows_sql ($sql);
}

echo "<h3>".__('Related incidents');
echo " - ".__('Task')." - ".$task_name."</h3>";
echo "<table width=90% class='listing'>";
echo "<tr><th>"; 
echo __('Incident');
echo "<th>"; 
echo __('Title');
echo "<th>";  
echo __('WU Hours');
echo "<th>"; 
echo __('Start');
echo "<th>"; 
echo __('End');

$incidents = print_array_pagination ($incidents, "index.php");

foreach ($incidents as $incident){

	echo "<tr>";
	echo "<td>";
	echo $incident["id_incidencia"];

	echo "<td>";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident&id=".$incident["id_incidencia"]."'>";
	echo $incident["titulo"];
	echo "</a>";

	echo "<td>";
	echo get_incident_workunit_hours ($incident["id_incidencia"]);
	
	echo "<td class='f9'>";
	echo $incident["inicio"];

	echo "<td class='f9'>";
	echo $incident["cierre"];
}
echo "</table>";

?>
