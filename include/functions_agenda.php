<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
enterprise_include ('include/functions_agenda.php', true);

function agenda_get_entry_permission ($id_user, $id_entry) {
	
	$return = enterprise_hook ('agenda_get_entry_permission_extra', array($id_user, $id_entry));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

function agenda_process_privacy_groups ($entry_id, $public, $groups = array()) {
	$return = enterprise_hook('agenda_process_privacy_groups_extra', array($entry_id, $public, $groups));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return array();
}

function html_print_entry_visibility_groups ($id_user = false, $selected_groups = array(), $return = false) {
	$return = enterprise_hook ('html_print_entry_visibility_groups_extra', array($id_user, $selected_groups, $return));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return '';
}


function get_event_date_sql ($start_date, $end_date, $id_user = '') {
	global $config;
	
	if (empty($id_user))
		$id_user = $config["id_user"];
	
	$return = enterprise_hook ('get_event_date_sql_extra', array($start_date, $end_date, $id_user));
	if ($return !== ENTERPRISE_NOT_HOOK) {
		$sql = $return;
	}
	else {
		$sql = sprintf("SELECT *
						FROM tagenda
						WHERE (id_user = '%s' OR public = 1)
							AND timestamp >= '%s'
							AND timestamp <= '%s'
						ORDER BY timestamp ASC",
						$id_user, $end_date, $start_date);
	}
	
	return $sql;
}

?>
