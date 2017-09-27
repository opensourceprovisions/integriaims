<?PHP

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
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ADD WORKUNIT CONTROL
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

global $config;

check_login ();

$id = (int) get_parameter ("id");

$incident_creator = get_db_value ("id_creator", "tincidencia", "id_incidencia", $id);

if (! give_acl ($config["id_user"], get_incident_group ($id), "IW")
	&& $config['id_user'] != $incident_creator) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket #".$id);
	include ("general/noaccess.php");
	exit;
}

$title = get_db_value ("titulo", "tincidencia", "id_incidencia", $id);
$id_task = get_db_value ("id_task", "tincidencia", "id_incidencia", $id);

echo "<h3><img src='images/award_star_silver_1.png'>&nbsp;&nbsp;";
echo __('Add workunit')." &raquo; $title</h3>";

$now =  print_mysql_timestamp();

$table->width = '100%';
$table->class = 'databox';
$table->colspan = array ();
$table->colspan[2][0] = 3;
$table->data = array ();

$table->data[0][0] = "<i>$now</i>";
$table->data[0][1] = combo_roles (1, 'work_profile', __('Profile'), true);

$table->data[1][0] = print_input_text ("duration", $config["iwu_defaultime"], '', 7,  10, true, __('Time used'));
$table->data[1][1] = print_checkbox ('have_cost', 1, false, true, __('Have cost'));
$table->data[1][2] = print_checkbox ('public', 1, true, true, __('Public'));

$table->data[2][0] = print_textarea ('nota', 10, 70, '', "style='resize:none;'", true, __('Description'));

echo '<form id="form-add-workunit" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_detail">';

print_table ($table);

echo '<div style="width: 100%" class="button">';
echo '<span id="sending_data" style="display: none;">' . __('Sending data...') . '<img src="images/spinner.gif" /></span>';
print_submit_button (__('Add'), 'addnote', false, 'class="sub next"');
print_input_hidden ('insert_workunit', 1);
print_input_hidden ('id', $id);
echo '</div>';
echo "</form>";
?>
