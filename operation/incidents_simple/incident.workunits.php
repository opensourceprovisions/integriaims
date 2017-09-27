<?php

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

// CHECK LOGIN AND ACLs
check_login ();

// SET VARS
$width = '99%';

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

$incident_id = get_parameter('incident_id', 0);
if($incident_id == 0) {
	ui_print_error_message(__('Unable to load ticket'));
	exit;
}

// GET THE WORKUNITS
$incident['workunits'] = get_incident_full_workunits ($incident_id);
if($incident['workunits'] === false) {
	$incident['workunits'] = array();
}
	
// SHOW THE WORKUNITS
$table->class = 'result_table';
$table->width = $width;
$table->id = 'incident_search_result_table';
$separator_style = 'border-bottom: 1px solid rgb(204, 204, 204);border-top: 1px solid rgb(204, 204, 204);';
$table->style = array ();


$table->data = array();

$table->head = array ();

$row = 0;

if(empty($incident['workunits'])) {
	$table->colspan[$row][0] = 2;
	$table->data[$row][0] = '<i>'.__('No workunit was done in this ticket').'</i>';
}

foreach($incident['workunits'] as $k => $workunit) {
	$table->colspan[$row+$k][0] = 2;
	
	$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $workunit['id_user']);
	
	$wu = '';
	$wu = "<div class='notetitle' style='width:" . $width . "'>"; // titulo
	// Show data
	$wu .= "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
	$wu .= " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=".$workunit['id_user']."'>";
	$wu .= $workunit['id_user'];
	$wu .= "</a>";
	$wu .= ' '.__('said on').' '.$workunit['timestamp'];
	$wu .= "</div>";

	// Body
	$wu .= "<div class='notebody' style='width:" . $width . "'>";
	$wu .= clean_output_breaks($workunit['description']);
	$wu .= "</div>";
	
	$table->data[$row+$k][0] = $wu;
}

print_table($table);

unset($table);

// Add the description of the incident under the first workunit for usability
$description = get_db_value('descripcion','tincidencia','id_incidencia',$incident_id);
echo "<h3>".__("Ticket details")."</h3>";
echo "<div style='width: 98%' class='incident_details'><p>";
echo clean_output_breaks ($description);
echo "</div>";

?>
