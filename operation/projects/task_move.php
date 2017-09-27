<?php

// Integria 1.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

// Get our main stuff
$id_project = get_parameter ("id_project", -1);
$id_task = get_parameter ("id_task", -1);


// ACL
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (!$task_permission["manage"]) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task move without permission");
	no_permission();
}

$section_title = __('Move task to other project');
$name_task  = get_db_value ("name", "ttask", "id", $id_task);
$section_subtitle =  __('Task'). ": " . $name_task;
$t_menu = print_task_tabs();
print_title_with_menu ($section_title, $section_subtitle, "task_move", 'projects', $t_menu, 'move');

//TASK MOVE Operation
echo "<div class='divform'>";
echo "<form name='project_move' method='POST' action='index.php?sec=projects&sec2=operation/projects/task&operation=move&id_project=$id_project&id_task=$id_task'>";

echo '<table width="20%" class="search-table" >';

// Project combo
echo '<tr><td class="datos"><b>'.__('Destination project').'</b>';
combo_projects_user ($config["id_user"], 'target_project');
echo '<tr></td><td class="datos">';
echo '<input type="submit" class="sub create" name="accion" value="'.__('Move').'" border="0">';
echo "</td></tr></table></form>";
echo "</div>";