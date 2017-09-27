<?php
// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

check_login ();

$id = (int) get_parameter ('id');

$incident_creator = get_db_value ("id_creator", "tincidencia", "id_incidencia", $id);

if (! give_acl($config["id_user"], 0, "IW")
	&& $config['id_user'] != $incident_creator) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket #".$id);
	include ("general/noaccess.php");
	return;
}

$title = get_db_value ("titulo", "tincidencia", "id_incidencia", $id);

echo '<div id="upload_result"></div>';

echo "<div id='upload_control'>";

$table->width = '100%';
$table->data = array ();


$table->data[0][0] = "<span style='font-size: 10px'>". __("Please note that you cannot upload .php or .pl files, as well other source code formats. Please compress that files prior to upload (using zip or gz)"). "</span>";

$table->data[1][0] = print_textarea ('file_description', 8, 1, '', "style='resize:none'", true, __('Description'));

if (defined ('AJAX'))
	$action = 'ajax.php?page=operation/incidents/incident_detail';
else
	$action = 'index.php?sec=incidents&sec2=operation/incidents/incident_detail';

$into_form = print_table ($table, true);
$into_form .= '<div class="button" style="width: '.$table->width.'">';
$into_form .= print_button (__('Upload'), 'upload', false, '', 'class="sub upload"', true);
$into_form .= '</div>';
$into_form .= print_input_hidden ('id', $id, true);
$into_form .= print_input_hidden ('upload_file', 1, true);

// Important: Set id 'form-add-file' to form. It's used from ajax control
print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', 'button-upload');

echo '</div>';
?>
