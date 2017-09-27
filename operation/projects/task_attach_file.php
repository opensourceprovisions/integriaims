<?PHP
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


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ADD FILE CONTROL
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

global $config;
check_login ();

$id_task = get_parameter ("id_task", -1);
$task_name = get_db_value ("name", "ttask", "id", $id_task);
$id_project = get_parameter ("id_project", -1);

// ACL
if ($id_task == -1){
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task tracking without task id");
	no_permission();
}

if ($id_project == -1) {
	$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
}
// ACL
if (! $id_project) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task tracking without project id");
	no_permission();
}

// ACL
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (! $task_permission["write"]) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task tracking without permission");
	no_permission();
}

echo "<h2>" . __('Upload file');
echo "<h4>" . __('Task').": ".$task_name."</h4>";

echo "<div class='divform' id='upload_control'>";
echo "<form method='POST' action='index.php?sec=projects&sec2=operation/projects/task_files&id_task=$id_task&id_project=$id_project&operation=attachfile' enctype='multipart/form-data' >";
echo "<table cellpadding=4 cellspacing=4 border=0 width='20%' class='search-table'>";
echo "<tr>";
echo '<td class="datos"><b>'.__('Filename') . "</b>";

$action = "";

$into_form = '';
$into_form .=  '<input type="file" name="userfile" value="userfile" class="sub" size="40">';
$into_form .=  '<tr><td class="datos2"><b>'.__('Description').'</b><br><input type="text" name="file_description" size=47 style="width:255px !important;">';
$into_form .=  "</td></tr>";
$into_form .=  "";
$into_form .=  '<tr><td style="text-align:center" class="datos2"><input type="submit" id="button-upload" name="upload" value="'.__('Upload').'" class="">';
$into_form .= "</table>";
echo $into_form;
echo "</form>";
// Important: Set id 'form-add-file' to form. It's used from ajax control
//print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', 'button-upload');

//echo "</div>";
echo '</div>';


?>
