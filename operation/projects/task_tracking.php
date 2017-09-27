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

// Load global vars

global $config;

check_login ();

$id_task = get_parameter ("id_task", -1);

// ACL
$task_permission = get_project_access ($config["id_user"], false, $id_task, false, true);
if (! $task_permission["read"]) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task tracking without permission");
	no_permission();
}

$cabecera=0;
$sql4='SELECT * FROM ttask_track WHERE id_task= '.$id_task;

$section_title = __('Task tracking');
$section_subtitle = get_db_sql("SELECT name FROM ttask WHERE id = $id_task");
$t_menu = print_task_tabs();
print_title_with_menu ($section_title, $section_subtitle, "task_tracking", 'projects', $t_menu, 'tracking');

echo "<table class='listing' width=630>";

if ($res4=mysql_query($sql4)){
	echo "<tr><th>".__('Status')."<th>".__('User')."<th>".__('Timestamp');
	while ($row2=mysql_fetch_array($res4)) {
		$timestamp = $row2["timestamp"];
		$state = $row2["state"];
		$user = $row2["id_user"];
		$external_data = $row2["id_external"];		
		echo '<tr><td>';

		switch($state){
			case 11: $descripcion = __('Task added');
				break;
            case 12: $descripcion = __('Task updated');
                break;
            case 14: $descripcion = __('Workunit added');
                break;
            case 15: $descripcion = __('File added');
                break;			
            case 16: $descripcion = __('Task completion progress updated');
                break;
            case 17: $descripcion = __('Task finished');
                break;
            case 18: $descripcion = __('Task member updated');
                break;
            case 19: $descripcion = __('Task moved');
                break;
            case 20: $descripcion = __('Task deleted');
                break;
 			default: $descripcion = __('Unknown');
		}
		
		echo $descripcion;
		echo '<td>';
		$nombre_real = dame_nombre_real($user);
		echo " $nombre_real";

		echo '<td class="f9">';
		echo $timestamp;
	}
echo "</table>"; 
} else
	echo __('No data available');

?>
