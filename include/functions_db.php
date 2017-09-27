<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once ('functions_ui.php');
require_once ("functions_inventories.php");
require_once ("functions_incidents.php");
require_once ("functions_db.mysql.php");
require_once ("functions_user.php");

// Load enterprise version functions

global $config;

enterprise_include ('include/functions_db.php');

/**
 * Function to check user permissions in a group.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/

function give_acl ($id_user, $id_group, $access) {
	global $config;
	
	$is_admin = get_db_value ('nivel', 'tusuario', 'id_usuario', $id_user);
	if ($is_admin == 1)
		return true;
		
	$return = enterprise_hook ('give_acl_extra', array ($id_user, $id_group, $access));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	
	// Opensource ACL implementation (not hooked to profiles / groups)
	$admin = get_admin_user($id_user);

	if (($access == "UM") AND ($admin == 0))
		return false;
	
	if (($access == "PM") AND ($admin == 0))
		return false;
	
	if (($access == "IM") AND ($admin == 0))
		return false;
	
	if (($access == "IM") AND ($admin == 0))
		return false;
	
	if (($access == "AM") AND ($admin == 0))
		return false;
	
	if (($access == "FM") AND ($admin == 0))
		return false;
	
	if (($access == "DM") AND ($admin == 0))
		return false;
	
	if (($access == "AM") AND ($admin == 0))
		return false;
	
	if (($access == "KM") AND ($admin == 0))
		return false;
	
	if (($access == "TM") AND ($admin == 0))
		return false;
		
	if (($access == "WM") AND ($admin == 0))
		return false;
	
	return true;
}

/**
 This function return 1 if target_user is visible for a user (id_user)
 with a specific permission bit on any of its profiles 
 * NOT ENABLED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
**/
function user_visible_for_me ($id_user, $target_user, $access = "") {
	global $config;
	
	$return = enterprise_hook ('user_visible_for_me_extra', array ($id_user, $target_user, $access));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
} 


// --------------------------------------------------------------- 
// audit_db, update audit log
// --------------------------------------------------------------- 

function audit_db ($id, $ip, $accion, $description, $extra = "") {
	require_once ("config.php");
	$today = date('Y-m-d H:i:s');
	
	$extra = mysql_real_escape_string ($extra);
	
	$utimestamp = time();
	$sql = 'INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp, extra_info) VALUES ("'.$id.'","'.$accion.'","'.$today.'","'.$ip.'","'.$description.'", '.$utimestamp.', "'.$extra.'")';
	process_sql ($sql);
}


// --------------------------------------------------------------- 
// logon_db, update entry in logon audit
// --------------------------------------------------------------- 

function logon_db ($id, $ip) {
	global $config;
	audit_db ($id, $ip, "Logon", "Logged in");
	$today = date ('Y-m-d H:i:s');
	// Update last registry of user to get last logon
	$sql = sprintf ('UPDATE tusuario SET fecha_registro = "%s" WHERE id_usuario = "%s"', $today, $id);
	process_sql ($sql);
}

// --------------------------------------------------------------- 
// logoff_db, also adds audit log
// --------------------------------------------------------------- 

function logoff_db ($id, $ip) {
	audit_db ($id, $ip, "Logoff", "Logged out");
}

// --------------------------------------------------------------- 
// Returns profile given ID
// --------------------------------------------------------------- 

function dame_perfil ($id) {
	return get_db_value ('name', 'tprofile', 'id', $id);
}


// --------------------------------------------------------------- 
// Returns group given ID
// --------------------------------------------------------------- 

function dame_grupo ($id_group) {
	return get_db_value ('nombre', 'tgrupo', 'id_grupo', $id_group);
}

// --------------------------------------------------------------- 
// Returns icon name given group ID
// --------------------------------------------------------------- 

function dame_grupo_icono ($id_group) {
	return get_db_value ('icon', 'tgrupo', 'id_grupo', $id_group);
}

// --------------------------------------------------------------- 
// Returns password (HASH) given user_id
// --------------------------------------------------------------- 

function dame_password ($id_user) {
	return get_db_value ('password', 'tusuario', 'id_usuario', $id_user);
}

// --------------------------------------------------------------- 
// Returns name of the user when given ID
// --------------------------------------------------------------- 

function dame_nombre_real ($id_user) {
	return get_db_value ('nombre_real', 'tusuario', 'id_usuario', $id_user);
}


// --------------------------------------------------------------- 
// This function returns ID of user who has created incident
// --------------------------------------------------------------- 

function get_incident_author ($id_incident) {
	return get_db_value ('id_usuario', 'tincidencia', 'id_incidencia', $id_incident);
}


// --------------------------------------------------------------- 
// Return name of a group when given ID
// --------------------------------------------------------------- 

function dame_nombre_grupo ($id_group) {
	return get_db_value ('nombre', 'tgrupo', 'id_grupo', $id_group);
} 

// --------------------------------------------------------------- 
// Returns number of files from a given incident
// --------------------------------------------------------------- 

function get_number_files_incident ($id_incident) {
	return (int) get_db_value ('COUNT(*)', 'tattachment', 'id_incidencia', $id_incident);
}


// --------------------------------------------------------------- 
// Returns number of files from a given incident
// --------------------------------------------------------------- 

function get_number_files_task ($id_task) {
	return (int) get_db_value ('COUNT(*)', 'tattachment', 'id_task', $id_task);
}


/**
* Return number of files associated to a project
*
* $id		integer 	ID of project
**/
function give_number_files_project ($id) {
	return (int) get_db_sql ('SELECT COUNT(*) FROM tattachment, ttask WHERE ttask.id_project = '.$id.' AND ttask.id = tattachment.id_task');
}


/**
* Return number of tasks associated to a project
*
* $id		integer 	ID of project
**/
function get_tasks_count_in_project ($id_project) {
	return (int) get_db_value ('COUNT(*)', 'ttask', 'id_project', $id_project);
}


/**
* Return total hours assigned to incidents assigned to a task
*
* $id_task	float 	ID of task
**/

function get_incident_task_workunit_hours ($id_task) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
		FROM tworkunit, tworkunit_incident, tincidencia 
		WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tworkunit_incident.id_workunit = tworkunit.id
		AND tincidencia.id_task = %d', $id_task);
	return get_db_sql ($sql);
}


/**
* Return total coast assigned to incidents assigned to a task
*
* $id_task	integer 	ID of task
**/

function get_incident_task_workunit_cost ($id_task) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration * trole.cost)
		FROM tworkunit, tworkunit_incident, tincidencia , trole
		WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tworkunit_incident.id_workunit = tworkunit.id
		AND tworkunit.have_cost = 1
        AND trole.id = tworkunit.id_profile
		AND tincidencia.id_task  = %d', $id_task);
	return get_db_sql ($sql);
}


/**
* Return total coast assigned to incidents assigned to a task
*
* $id_task	integer 	ID of task
**/

function get_incident_project_workunit_cost ($id_project) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration * trole.cost)
		FROM tworkunit, tworkunit_incident, tincidencia , trole, tproject, ttask
		WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tworkunit_incident.id_workunit = tworkunit.id
		AND tworkunit.have_cost = 1
		AND tproject.id = ttask.id_project 
        AND trole.id = tworkunit.id_profile
		AND tincidencia.id_task = ttask.id
		AND tproject.id = %d', $id_project);
	return get_db_sql ($sql);
}


/**
* Return total hours assigned in specific day by a user
*
* $id_user	string ID Of user
* $timestamp string date in format YYYY-MM-DD
**/

function get_wu_hours_user ($id_user, $timestamp) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
		FROM tworkunit
		WHERE tworkunit.id_user = "%s"
		AND tworkunit.timestamp LIKE "%s%%"', $id_user, $timestamp);
	return (int) get_db_sql ($sql);
}


/**
* Return total hours assigned to incidents assigned to tasks in a project
*
* $id_project	integer 	ID of project
**/

function get_incident_project_workunit_hours ($id_project) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_incident, tincidencia, ttask 
			WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
			AND tworkunit_incident.id_workunit = tworkunit.id
			AND ttask.id_project = %d
			AND ttask.id = tincidencia.id_task', $id_project);
	return (int) get_db_sql ($sql);
}



/**
* Return total number of incidents related to a task
*
* $id_task	integer 	ID of task
**/

function get_incident_task ($id_task) {
	global $config;
	$sql = sprintf ('SELECT COUNT(id_incidencia) 
			FROM tincidencia 
			WHERE id_task = %d', $id_task);
	return (int) get_db_sql ($sql);
}



/**
* Return total wu assigned to incident
*
* $id_incident   integer	 ID of incident
**/
function get_incident_count_workunits ($id_incident) {
	global $config;
	$sql = sprintf ('SELECT COUNT(tworkunit.duration) 
			FROM tworkunit, tworkunit_incident, tincidencia 
			WHERE   tworkunit_incident.id_incident = tincidencia.id_incidencia AND 
					tworkunit_incident.id_workunit = tworkunit.id AND
					 tincidencia.id_incidencia = %d', $id_incident);
	return (int) get_db_sql ($sql);
}


/**
* Return total hours assigned to project
*
* $id_project	integer 	ID of project
**/

function get_project_workunit_hours ($id_project, $with_cost = 0, $start_date = "", $end_date =""){ 
	global $config;
	
	$timesearch = "";
	if ($start_date != "")
		$timesearch = " AND tworkunit.timestamp >= '$start_date' AND tworkunit.timestamp <= '$end_date'";
	
	if ($with_cost != 0) {
		$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d
			AND tworkunit_task.id_workunit = tworkunit.id
			AND tworkunit.have_cost = 1 %s', $id_project, $timesearch);
	}
	else {
		$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d
			AND tworkunit_task.id_workunit = tworkunit.id %s',
			$id_project, $timesearch);
	}
	return (int) get_db_sql ($sql);
}

/**
* Return total wu assigned to project
*
* $id_project   integer	 ID of project
**/

function get_project_count_workunits ($id_project) {
	$sql = sprintf ('SELECT COUNT(tworkunit.duration) 
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d
			AND tworkunit_task.id_workunit = tworkunit.id',
			$id_project);
	return (int) get_db_sql ($sql);
}


/**
* Return total hours assigned to task
*
* $id_task	integer 	ID of task
**/
function get_task_workunit_hours ($id_task) {
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_task
			WHERE tworkunit_task.id_task = %d
			AND tworkunit_task.id_workunit = tworkunit.id',
			$id_task);
	
	return (int) get_db_sql ($sql);
}

/**
* Return total workunits assigned to task
*
* $id_task  integer	 ID of task
**/
function get_task_count_workunits ($id_task) {
	$sql = sprintf ('SELECT COUNT(tworkunit.duration) 
			FROM tworkunit, tworkunit_task
			WHERE tworkunit_task.id_task = %d
			AND tworkunit_task.id_workunit = tworkunit.id',
			$id_task);
	return (int) get_db_sql ($sql);
}


/**
* Return total workunits assigned to task for a specific user
*
* $id_task  integer	 ID of task
* $id_user  string	  ID of user
**/

function get_task_workunit_hours_user ($id_task, $id_user, $with_cost = 0, $start_date = false, $end_date = false) {
	if (($with_cost = 0) && (!$start_date) && (!$end_date)) {
		//Old code for old call of this function.
		$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_task 
			WHERE tworkunit_task.id_task = %d
				AND tworkunit.id_user = "%s"
				AND tworkunit_task.id_workunit = tworkunit.id',
			$id_task, $id_user);
		
		return (int) get_db_sql ($sql);
	}
	else {
		$timesearch = "";
		if ($start_date)
			$timesearch .= " AND tworkunit.timestamp >= '$start_date' ";
		if ($end_date)
			$timesearch .= " AND tworkunit.timestamp <= '$end_date'";
		
		if ($with_cost != 0) {
			$sql = sprintf ('SELECT SUM(tworkunit.duration) 
				FROM tworkunit, tworkunit_task
				WHERE tworkunit_task.id_workunit = tworkunit.id
					AND tworkunit_task.id_task = %d
					AND tworkunit.id_user = "%s"
					AND tworkunit.have_cost = 1 %s',
				$id_task, $id_user, $timesearch);
		}
		else {
			$sql = sprintf ('SELECT SUM(tworkunit.duration) 
				FROM tworkunit, tworkunit_task
				WHERE tworkunit_task.id_workunit = tworkunit.id
					AND tworkunit_task.id_task = %d
					AND tworkunit.id_user = "%s"
					%s',
				$id_task, $id_user, $timesearch);
		}
		
		return (int) get_db_sql ($sql);
	}
}

/**
* Return total wu assigned to project for a specific user
*
* $id_project   integer	 ID of project
**/

function get_project_workunits_hours_user ($id_project, $id_user) {
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d
			AND tworkunit.id_user = "%s"
			AND tworkunit_task.id_workunit = tworkunit.id',
			$id_project, $id_user);
	return (int) get_db_sql ($sql);
}


/**
* Calculate project completion
*
* Uses each task completion and priority and uses second
* to ponderate progress of this task. A average value of 
* ponderated values is made to give final result.
* $id_project 	integer 	ID of project
**/

function calculate_project_progress ($id_project){
	global $config;
	$sql = sprintf ('SELECT AVG(completion)
			FROM ttask
			WHERE id_project = %d',
			$id_project);
	return get_db_sql ($sql);
}

/**
* Calculate project deviation
*
* $id_project 	integer 	ID of project
**/

function calculate_project_deviation ($id_project) {
	global $config;
	
	$expected_length = get_db_sql ("SELECT SUM(hours)
		FROM ttask
		WHERE id_project = $id_project");
	
	if (empty($expected_length))
		return 0;
	
	$pr_hour = get_project_workunit_hours ($id_project, 1);
	
	$deviation_percent = format_numeric(100 -
		(abs(($pr_hour - $expected_length) / ($expected_length / 100))));
	
	return $deviation_percent;
}

/**
* Delete an incident
*
* Delete incident given its id and all its workunits
* $id_incident integer 	ID of incident
**/
 
function borrar_incidencia ($id_incident) {
	global $config;
	
	$incident_title = get_db_value ("titulo", "tincidencia", "id_incidencia", $id_incident);
	$sql = sprintf ('DELETE FROM tincidencia
			WHERE id_incidencia = %d', $id_incident);
	process_sql ($sql);
	$sql = sprintf ('SELECT id_workunit FROM tworkunit_incident
			WHERE id_incident = %d',$id_incident);
	$workunits = get_db_all_rows_sql ($sql);
	if ($workunits === false) {
		$workunits = array ();
	}
	foreach ($workunits as $workunit) {
		// Delete all note ID related in table
		$sql = sprintf ('DELETE FROM tworkunit WHERE id = %d',
				$workunit['id_workunit']);
		process_sql ($sql);
	}
	$sql = sprintf ('DELETE FROM tworkunit_incident
			WHERE id_incident = %d', $id_incident);
	process_sql ($sql);
	
	// Delete attachments
	$sql = sprintf ('SELECT id_attachment, filename
			FROM tattachment
			WHERE id_incidencia = %d', $id_incident);
	$attachments = get_db_all_rows_sql ($sql);
	if ($attachments === false) {
		$attachments = array ();
	}
	foreach ($attachments as $attachment) {
		// Unlink all attached files for this incident
		$id = $attachment["id_attachment"];
		$name = $attachment["filename"];
		unlink ($config["homedir"]."/attachment/".$id."_".$name);
	}
	
	$sql = sprintf ('DELETE FROM tattachment
			WHERE id_incidencia = %d', $id_incident);
	process_sql ($sql);
	$sql = sprintf ('DELETE FROM tincident_track
			WHERE id_incident = %d', $id_incident);
	process_sql ($sql);
	
	//Delete incident stats
	$sql = sprintf ('DELETE FROM tincident_stats 
			WHERE id_incident = %d', $id_incident);
	process_sql ($sql);

	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Ticket Management", "Deleted ticket $incident_title");
}

/**
* Delete an inventory object. All depending data will 
* deleted using SQL referencial integrity
*
* $id_inventory integer 	ID of inventory object
**/

// --------------------------------------------------------------- 
// Delete an inventory object given its id
// --------------------------------------------------------------- 
function borrar_objeto ($id) {
	$sql = sprintf ('DELETE FROM tinventory WHERE id = %d', $id);
	$result = process_sql ($sql);
	
	if ($result !== false) {
		$sql = sprintf ('DELETE FROM tobject_field_data WHERE id_inventory = %d', $id);
		$res = process_sql ($sql);
	}
	
	return $result;
}

// --------------------------------------------------------------- 
//  Update "contact" field in User table for username $nick
// --------------------------------------------------------------- 

function update_user_contact ($id_user) {
	$today = date ("Y-m-d H:i:s", time ());
	$sql = sprintf ('UPDATE tusuario set fecha_registro ="%s"
			WHERE id_usuario = "%s"',
			$today, $id_user);
	process_sql ($sql);
}

// ---------------------------------------------------------------
// Returns Admin value (0 no admin, 1 admin)
// ---------------------------------------------------------------

function get_admin_user ($id) {
	$nivel = get_db_value ('nivel', 'tusuario', 'id_usuario', $id);
	if ($nivel == 1)
		return true;
	// Be careful, other possible values on level could be implemented
	// In the future, so only "admin" value possible is 1
	return false;
}

// Wrapper for compatibility
function dame_admin ($id) {
    return get_admin_user ($id);
}

// ---------------------------------------------------------------
// Returns true is provided user is standalone
// ---------------------------------------------------------------

//~ function get_external_user ($id) {
	//~ $nivel = get_db_value ('nivel', 'tusuario', 'id_usuario', $id);
	//~ if ($nivel == -1)
		//~ return true;
	//~ return false;
//~ }


// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an valid user
// --------------------------------------------------------------- 

function check_login () { 
	if (isset ($_SESSION["id_usuario"])) {
		$id = $_SESSION["id_usuario"];
		$id_user = get_db_value ('id_usuario', 'tusuario', 'id_usuario', $id);
		if ($id == $id_user) {
			return false;
		}
	}
	global $config;
	require ($config["homedir"]."/general/noaccess.php");
	exit;
}


// ---------------------------------------------------------------
// 0 if it doesn't exist, 1 if it does, when given email
// ---------------------------------------------------------------

function existe($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";   
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		if ($rowdup=mysql_fetch_array($resq1)){ 
			return 1; 
		}
		else {
			return 0; 
		}
	} else { return 0 ; }
}

// ---------------------------------------------------------------
// Return if a task have childs
// Return date of end of this task of last of it's childs
// ---------------------------------------------------------------

function task_child_enddate ($id_task){
	global $config;
   
	$start_date =  task_start_date($id_task);
	$tasktime = get_db_sql ("SELECT hours FROM ttask WHERE id= $id_task");
	$tasktime = $tasktime / $config["hours_perday"];
	$end_date = calcdate_business ($start_date, $tasktime);
	
	$max = '1980-01-01';
	$query1="SELECT * FROM ttask WHERE id_parent_task = $id_task";
	$resq1=mysql_query($query1);  
	while ($row=mysql_fetch_array($resq1)){
		$thisvalue = $row["hours"];
		$thisstart = $row["start"];
		$childtime = $thisvalue / $config["hours_perday"];
		$childdate = calcdate_business ($thisstart, $childtime);

		$grandchilddate = task_child_enddate ($row["id"]);
		if ($grandchilddate != $childdate)
			$childdate = $grandchilddate;

		if (strtotime($childdate) > strtotime($max)){
			$max = $childdate;
		}
	}

	if (strtotime($max) > strtotime($end_date))
		return $max;
	else
		return $end_date;
}

// ---------------------------------------------------------------
// Return start date of a task
// If is a nested task, return parent task + assigned time for parent
// ---------------------------------------------------------------

function task_start_date ($id_task){
	global $config;
	
	$taskrow =  get_db_row ("ttask", "id", $id_task);
	return $taskrow["start"];
}

// ---------------------------------------------------------------
// Return true (1) if userid belongs to given project as any role
// ---------------------------------------------------------------

function user_belong_project ($id_user, $id_project, $real = 0) { 
	global $config;
	
	if ($real == 0 && dame_admin ($id_user) != 0)
		return 1;
	
	$sql = sprintf ('SELECT COUNT(*) FROM trole_people_project
		WHERE id_project = %d
		AND id_user = "%s"', $id_project, $id_user);
	return (bool) get_db_sql ($sql);
}

// ---------------------------------------------------------------
// Return true (1) if userid belongs to given task as any role
// ---------------------------------------------------------------

function user_belong_task ($id_user, $id_task, $real=0){ 
	global $config;

	if ($real == 0){
		if (dame_admin ($id_user) != 0)
			return 1;
	}
	
	$id_project = get_db_sql ("SELECT id_project FROM ttask WHERE id = $id_task");
	// Project manager always has access to all tasks of his project
	if (project_manager_check ($id_project) == 1 )
		return 1;

	$query1="SELECT COUNT(*) from trole_people_task WHERE id_task = $id_task AND id_user = '$id_user'";
		$resq1=mysql_query($query1);
		$rowdup=mysql_fetch_array($resq1);
	if ($rowdup[0] == 0)
		return 0;
	else
		return 1; // There is at least one role for this person in that project
}

function get_incident_resolution ($id_incident) {
	return get_db_value ('resolution', 'tincidencia', 'id_incidencia', $id_incident);
}

function get_incident_creator ($id_incident) {
	return (int) get_db_value ('id_creator', 'tincidencia', 'id_incidencia', $id_incident);
}

function get_incident_group ($id_incident) {
	return (int) get_db_value ('id_grupo', 'tincidencia', 'id_incidencia', $id_incident);
}

// --------------------------------------------------------------- 
// Return incident priority
// --------------------------------------------------------------- 

function get_incident_priority ($id_incident) {
	return get_db_value ('prioridad', 'tincidencia', 'id_incidencia', $id_incident);
}

/** 
 * Get an incident title.
 * 
 * @param id_incident Incident Id
 * 
 * @return string incident title.
 */
function get_incident_title ($id_incident) {
	return (string) get_db_value ('titulo', 'tincidencia', 'id_incidencia', $id_incident);
}

/** 
 * Get a user.
 * 
 * @param id_user User id
 * @param only_name Flag for return only user name (FALSE by default)
 * 
 * @return array A full user or user name.
 */
function get_user ($id_user, $only_name = false) {
	$user = get_db_row ('tusuario', 'id_usuario', $id_user);
	
	if($only_name) {
		$user_name['id_usuario'] = $id_user;
		return $user_name;
	}
	
	return $user;
}


/** 
 * Get a user email.
 * 
 * @param id_user User id
 * 
 * @return string User email.
 */
function get_user_email ($id_user) {
	return (string) get_db_value ('direccion', 'tusuario', 'id_usuario', $id_user);
}

function project_manager_check ($id_project, $id_user = false) {
	global $config;
	
	if ($id_user) {
		$filter['id_user'] = $id_user;
	} else {
		$filter['id_user'] = $config['id_user'];
	}
	$filter['id_project'] = $id_project;
	
	$role = get_db_value_filter ('MIN(id_role)', 'trole_people_project', $filter);
	if ($role == 1)
		return true;
	return false;
}

function incident_tracking ($id_incident, $state, $aditional_data = 0, $user = '') {
	global $config;
	
    if ($id_incident == 0)
        return;

	switch ($state) {
	case INCIDENT_CREATED:
		$description = 'Created';
		break;
	case INCIDENT_UPDATED:
		$description = 'Updated';
		break;
	case INCIDENT_WORKUNIT_ADDED:
		$description = 'Workunit added';
		break;
	case INCIDENT_FILE_ADDED:
		$description = 'File added';
		break;
	case INCIDENT_NOTE_ADDED:
		$description = 'Note added';
		break;
	case INCIDENT_FILE_REMOVED:
		$description = 'File removed';
		break;
	case INCIDENT_PRIORITY_CHANGED:
		$description = 'Priority changed';
		$priorities = get_priorities ();
		$description .= " -> ".$priorities[$aditional_data];
		break;
	case INCIDENT_STATUS_CHANGED:
		$description = 'Status changed';
		$description .= " -> ".get_db_value ("name", "tincident_status", "id", $aditional_data);
		break;
	case INCIDENT_RESOLUTION_CHANGED:
		$description = 'Resolution changed';
		$description .= " -> ".get_db_value ("name", "tincident_resolution", "id", $aditional_data);
		break;
	case INCIDENT_NOTE_DELETED:
		$description = 'Note deleted';
		break;
	case INCIDENT_USER_CHANGED:
		$description = 'Assigned user changed';
		$description .= ' -> '.get_db_value ('nombre_real', 'tusuario', 'id_usuario', $aditional_data);
		break;
	case INCIDENT_DELETED:
		$description = 'Incident deleted';
		break;
	case INCIDENT_CONTACT_ADDED:
		$description = 'Contact added';
		$description .= ' -> '.get_db_value ('fullname', 'tcompany_contact', 'id', $aditional_data);
		break;
	case INCIDENT_INVENTORY_ADDED:
		$description = 'Added inventory object ';
		$description .= " -> ".get_db_value ('name', 'tinventory', 'id', $aditional_data);
		break;
	case INCIDENT_GROUP_CHANGED:
		$description = "Group has changed";
		$description .= " -> ".get_db_value ("nombre", "tgrupo", "id_grupo", $aditional_data);
		break;
	case INCIDENT_INVENTORY_REMOVED:
		$description = 'Removed inventory object ';
		$description .= " -> ".get_db_value ('name', 'tinventory', 'id', $aditional_data);
		break;
	default:
		$description = 'Unknown update';
		break;
	}
	$fecha = print_mysql_timestamp();
	if ($user == '') {
		$user = $config['id_user'];
	}
	audit_db ($user, $config["REMOTE_ADDR"], "Ticket updated", $description);
	$sql = sprintf ('INSERT INTO tincident_track (id_user, id_incident,
		timestamp, state, id_aditional, description)
		VALUES ("%s", %d, "%s", %d, "%s", "%s")',
		$user, $id_incident, $fecha, $state, $aditional_data, $description);
	return process_sql ($sql, 'insert_id');
}

function task_tracking ($id_task, $state, $id_external = 0) {
	global $config;
	global $REMOTE_ADDR;

	$fecha = print_mysql_timestamp();
	audit_db ($config['id_user'], $REMOTE_ADDR, "Task tracking updated", "Task #id_task Status #$state");
	$sql = sprintf ('INSERT INTO ttask_track (id_user, id_task, timestamp,
		state, id_external)
		VALUES ("%s", %d, "%s", %d, %d)',
		$config['id_user'], $id_task, $fecha, $state, $id_external);
	return process_sql ($sql);
}

function project_tracking ($id_project, $state, $id_aditional = 0) {
	global $config;
	global $REMOTE_ADDR;

	$fecha = print_mysql_timestamp();
	audit_db ($config['id_user'], $REMOTE_ADDR, "Project tracking updated", "Project #$id_project status #$state");
	$sql = sprintf ('INSERT INTO tproject_track (id_user, id_project, timestamp,
		state, id_aditional)
		VALUES ("%s", %d, "%s", %d, %d)',
		$config['id_user'], $id_project, $fecha, $state, $id_aditional);
	return process_sql ($sql);
}


function delete_project ($id_project){
	global $config;
	$project_name = get_db_value ("name", "tproject", "id", $id_project);
	$query = "DELETE FROM trole_people_project WHERE id_project = $id_project";
	mysql_query($query);
	$query = "DELETE FROM trole_people_task, ttask WHERE ttask.id_project = $id_project AND trole_people_task.id_task = ttask.id";
	mysql_query($query);
	$query = "DELETE FROM ttask WHERE id_project = $id_project";
	mysql_query($query);
	$query = "DELETE FROM tproject WHERE id = $id_project";
	mysql_query($query);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Project Management", "Deleted project $project_name");
}

function delete_task ($id_task){
	global $config;
	// Have a parent ?
	$task = get_db_row ("ttask", "id", $id_task);
	if ($task["id_parent_task"] > 0){
		$query = "UPDATE tworkunit_task SET id_task = ".$task["id_parent_task"]." WHERE id_task = $id_task";
		mysql_query($query);
		$query = "DELETE FROM trole_people_task WHERE id_task = $id_task";
		mysql_query($query);
		$query = "DELETE FROM ttask WHERE id = $id_task";
		mysql_query($query);
	} else {
		$query = "DELETE FROM trole_people_task WHERE id_task = $id_task";
		mysql_query($query);
		$query = "DELETE FROM tworkunit_task, tworkunit WHERE tworkunit_task.id_task = $id_task AND tworkunit_task.id_workunit = tworkunit.id";
		mysql_query($query);
		$query = "DELETE FROM ttask WHERE id = $id_task";
		mysql_query($query);
	}

	//Remove task links
	$query = "DELETE FROM ttask_link WHERE source = $id_task OR target = $id_task";
	mysql_query($query);		
	
	//Set childs parent to 0 because if not the child task are missing.
	$query = sprintf("UPDATE ttask SET id_parent_task = 0 WHERE id_parent_task = %d", $id_task);
	
	process_sql($query);
	
	//insert_event ('TASK_DELETED', 0,0, $task["name"]);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Task Management", "Deleted task ".$task["name"]);
}

function mail_project ($mode, $id_user, $id_workunit, $id_task, $additional_msg = "") {
	global $config;

	$workunit = get_db_row ("tworkunit", "id", $id_workunit);
	$task	 = get_db_row ("ttask", "id", $id_task);
	$project  = get_db_row ("tproject", "id", $task["id_project"]);
	$id_project = $task["id_project"];
	$id_manager = $project["id_owner"];
	$cc_project = $project["cc"];
	$cc_task = $task["cc"];

	$MACROS["_time_used_"] = $workunit["duration"];
	
	$access_dir = empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
	$MACROS["_access_url_"] = $access_dir."/index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_task=$id_task";

	if ($workunit["have_cost"] == 1)
		$MACROS["_havecost_"] = __('Yes');
	else
		$MACROS["_havecost_"] = __('No');

	if ($workunit["public"] == 1)
		$MACROS["_public_"] = __('Yes');
	else
		$MACROS["_public_"] = __('No');

	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_taskname_"] = $task["name"];
	$MACROS["_projectname_"] =  $project["name"];
	$MACROS["_fullname_"] = dame_nombre_real ($project["id_owner"]);
	$MACROS["_update_timestamp_"] = $workunit["timestamp"];
	$MACROS["_owner_"] = $project["id_owner"];
	$MACROS["_wu_text_"] = $workunit["description"];
	$MACROS["_wu_user_"] = dame_nombre_real($id_user);
	$MACROS["_additional_message_"] = $additional_msg;
	$description = $workunit["description"];

	switch ($mode){
	case 0: // Workunit add
		$text = template_process ($config["homedir"]."/include/mailtemplates/project_wu_create.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/project_subject_wucreate.tpl", $MACROS);
		break;
	case 1: // Workunit updated
		$text = template_process ($config["homedir"]."/include/mailtemplates/project_wu_update.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/project_subject_wuupdate.tpl", $MACROS);
		break;
	}
	
	if (!user_is_disabled ($id_manager)) {
		// Send an email to project manager
		integria_sendmail (get_user_email($id_manager), $subject, $text);
	}
	if ($cc_project != "") {
		integria_sendmail ($cc_project, $subject, $text);
	}
	if ($cc_task != "") {
		integria_sendmail ($cc_task, $subject, $text);
	}
	
}

// TODO: Make todo mail using a template, like the other mails !


function mail_todo ($mode, $id_todo) {
	global $config;

	$todo = get_db_row ("ttodo", "id", $id_todo);
	$tcreated = $todo["created_by_user"];
	$tassigned = $todo["assigned_user"];

	// Only send mails when creator is different than owner
	if ($tassigned == $tcreated)
		return;

	$tlastupdate = $todo["last_update"];
	$tdescription = wordwrap($todo["description"], 70, "\n");
	$tprogress = translate_wo_status($todo["progress"]);
	$tpriority = get_priority_name ($todo["priority"]);
	$tname = $todo["name"];
	$url = $config["base_url"]."/index.php?sec=todo&sec2=operation/todo/todo&operation=update&id=$id_todo";

	switch ($mode){
	case 0: // Add
		$text = "TO-DO '$tname' has been CREATED by user $tcreated. You could track this todo in the following URL (need to use your credentials): $url\n\n";
		$subject = "[".$config["sitename"]."] New TO-DO from '$tcreated' : $tname";
		break;
	case 1: // Update
		$text = "TO-DO '$tname' has been UPDATED by user $tassigned. This TO-DO was created by user $tcreated. You could track this todo in the following URL (need to use your credentials): $url\n\n";
		$subject = "[".$config["sitename"]."] Updated TO-DO from '$tcreated' : $tname";
		break;
	case 2: // Delete
		$text = "TO-DO '$tname' has been DELETED by user $tassigned. This TO-DO was created by user $tcreated. You could track this todo in the following URL (need to use your credentials): $url\n\n";
		$subject = "[".$config["sitename"]."] Deleted TO-DO from '$tcreated' : $tname";
	}
	$text .= "
		---------------------------------------------------------------------
		TO-DO NAME  : $tname
		DATE / TIME : $tlastupdate
		CREATED BY  : $tcreated
		ASSIGNED TO : $tassigned
		PROGRESS    : $tprogress
		PRIORITY    : $tpriority
		DESCRIPTION
		---------------------------------------------------------------------
		$tdescription\n\n";

	$text = ascii_output ($text);
	$subject = ascii_output ($subject);
	// Send an email to both
	integria_sendmail (get_user_email ($tcreated), $subject, $text);
	integria_sendmail (get_user_email ($tassigned), $subject, $text);
}


/* Returns cost for a given task */

function task_workunit_cost ($id_task, $only_marked = true) {
	global $config;
	$total = 0;
	if ($only_marked)
		$res = mysql_query("SELECT id_profile, SUM(duration) FROM tworkunit, tworkunit_task
				WHERE tworkunit_task.id_task = $id_task AND 
				tworkunit_task.id_workunit = tworkunit.id AND 
				have_cost = 1 GROUP BY id_profile");
	else 
		$res = mysql_query("SELECT id_profile, SUM(duration) FROM tworkunit, tworkunit_task
				WHERE tworkunit_task.id_task = $id_task AND 
				tworkunit_task.id_workunit = tworkunit.id 
				GROUP BY id_profile");
	while ($row=mysql_fetch_array($res)){
		$cost_per_hour = get_db_sql ("SELECT cost FROM trole WHERE id = ".$row[0]);
		$total = $total + $cost_per_hour * $row[1];
	}
	return $total;
}

/* Returns cost for a given project */

function project_workunit_cost ($id_project, $only_marked = 1){
	global $config;
	$total = 0;
	$res = mysql_query("SELECT * FROM ttask WHERE id_project = $id_project");
	while ($row=mysql_fetch_array($res)){
		$total += task_workunit_cost ($row[0], $only_marked);
	}
	return $total;
}


function projects_active_user ($id_user) {
	$sql = "SELECT COUNT(DISTINCT(id_project)) FROM tproject, trole_people_project WHERE trole_people_project.id_user ='$id_user' AND trole_people_project.id_project = tproject.id AND tproject.disabled = 0";
	return get_db_sql ($sql);
}

function incidents_active_user ($id_user) {
	$sql = "SELECT COUNT(id_incidencia) FROM tincidencia WHERE (id_creator = '$id_user' OR id_usuario = '$id_user') AND estado IN (1,2,3,4,5)";
	return get_db_sql ($sql);
}

function todos_active_user ($id_user) {
	$sql = "SELECT COUNT(*) FROM ttodo WHERE assigned_user = '$id_user' AND progress < 1";
	return get_db_sql ($sql);
}

function get_user_vacations ($id_user, $year){
	global $config;
	$hours = get_db_sql ("SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task =-1 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");
	return format_numeric ($hours/$config["hours_perday"]);
}

function get_user_worked_days ($id_user, $year) {
	global $config;
	$hours = get_db_sql ("SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");
	return format_numeric ($hours/$config["hours_perday"]);
}

function get_user_incident_worked_days ($id_user, $year) {
	global $config;
	$hours = get_db_sql ("SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_incident WHERE tworkunit_incident.id_workunit = tworkunit.id AND tworkunit_incident.id_incident > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");
	return format_numeric ($hours/$config["hours_perday"]);
}

function get_user_other ($id_user, $year){
	global $config;
	$hours = get_db_sql ("SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task < -1 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59'");
	return format_numeric ($hours/$config["hours_perday"]);
}

function create_ical ( $date_from, $duration, $id_user, $title, $description ){
	require_once("config.php");

	$date_from_date = date('Ymd', strtotime("$date_from"));
	$date_from_time = date('His', strtotime("$date_from"));
	$date_to_date = date('Ymd', strtotime("$date_from + $duration hours"));
	$date_to_time = date('His', strtotime("$date_from + $duration hours"));
	
	// Define the file as an iCalendar file
	$output = "Content-Type: text/Calendar\n";
	// Give the file a name and force download
	$output .= "Content-Disposition: inline; filename=$id_user.ics\n";

	// Header of ics file
	$output .= "BEGIN:VCALENDAR\n";
	$output .= "VERSION:2.0\n";
	$output .= "PRODID:Integria\n";
	$output .= "METHOD:REQUEST\n";
	$output .= "BEGIN:VEVENT\n";
	$output .= "DTSTART:".$date_from_date."T".$date_from_time."\n";
	$output .= "DTEND:".$date_to_date."T".$date_to_time."\n";
	$output .= "DESCRIPTION:";
	$description = str_replace(chr(13).chr(10),"  ", $description);
	$output .= safe_output($description)."\n";
	$output .=  "SUMMARY:".safe_output($title)."\n";
	$output .=  "UID:$id_user\n";
	$output .=  "SEQUENCE:0\n";
	$output .=  "DTSTAMP:".date('Ymd').'T'.date('His')."\n";
	$output .=  "END:VEVENT\n";
	$output .=  "END:VCALENDAR\n";

	return $output;
}



function insert_event ($type, $id1 = 0, $id2 = 0, $id3 = ''){
   	require_once("config.php");
	$timestamp = date('Y-m-d H:i:s');

	$sql = "INSERT INTO tevent (type, id_user, timestamp, id_item, id_item2, id_item3) VALUES 
			('$type', '".$config["id_user"]."', '$timestamp', $id1, $id2, '$id3')";
	mysql_query($sql);
}

function get_groups ($order = 'nombre') {
	return get_db_all_rows_in_table ('tgrupo', $order);
}

function get_groups_id ($order = 'nombre') {
	return get_db_all_rows_sql (sprintf('SELECT id_grupo FROM tgrupo ORDER BY %s', $order));
}

/** 
 * Get all the groups a user has reading privileges.
 * 
 * @param id_user User id
 * @param permission Permission to have in the group (IR by default)
 * 
 * @return A list of the groups the user has reading privileges.
 */
function get_user_groups ($id_user = 0, $permission = 'IR', $all=true, $returnAllColumns = false) {
	if ($id_user === 0) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$user_groups = array ();
	$groups = get_groups ();

	// Admin have access to everything, so this loop is DIFFERENT from
	// loop below.
	if (get_admin_user($id_user)){
		foreach ($groups as $group) {
			if (!$all && ($group["id_grupo"] == 1)) {
				continue;
			}
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
		return $user_groups;
	}
		
	if (!$groups)
		return $user_groups;

	foreach ($groups as $group) {
		if (!$all && ($group["id_grupo"] == 1)) {
			continue;
		}
		if (! give_acl ($id_user, $group["id_grupo"], $permission))
			continue;
		if ($returnAllColumns) {
			$user_groups[$group['id_grupo']] = $group;
		}
		else {
			$user_groups[$group['id_grupo']] = $group['nombre'];
		}
	}
	
	return $user_groups;
}

/** 
 * This will return a list between ( ) for use in SQL
 * 
 * @param id_user User id
 * @param permission Permission to have in the group (IR by default)
 * 
 * @return A string ready to be used in the SQL 
 */

function get_user_groups_for_sql ($id_user, $access_profile = "VR"){
	global $config;
	$groups =  get_user_groups ($config["id_user"], $access_profile);

	$filter = "";
	
	foreach ($groups as $group => $group_name){
		$filter .= $group. " , ";
	}

	$filter = $filter . " 1 "; // Always 1... is "all"
		
	$filter = "( $filter )";
	return $filter;
}


/** 
 * Get all the user ids of the projects where a user is project manager
 * 
 * @param integer id_user User id
 * 
 * @return A list of user Ids of the same project that the user is project manager.
 */
function get_project_manager_users($id_user = "") {
	global $config;

	$values = array ();

	if ($id_user == "") {
		$id_user = $config['id_user'];
	}
	
	$sql = sprintf('SELECT id_user FROM trole_people_project WHERE
			id_user <> "%s" AND
			id_project IN 
				(SELECT id FROM tproject  WHERE 
					id_owner = "%s")', $id_user, $id_user);

	$users = get_db_all_rows_sql($sql);
	
	if($users === false) {
		return array();
	}
	
	$users_array = array();
	foreach($users as $user) {
		$users_array[] = $user['id_user'];
	}
	
	return $users_array;
}

/** 
 * Get all the visible users for a user.
 * 
 * @param integer id_user User id
 * @param string access Permission to check in the users (IR by default)
 * @param bool only_name Flag for return only users name (TRUE by default)
 * @param bool both Flag for check permissions in both sides (id_user sended and
 * 			list of users). If is false only will be checked on user list
 * 			(TRUE by default)
 * @param bool anygroup flag for check permissions in any group, not only in the user groups
 * @param string search filter to id_usuario
 * 
 * @return A list of users visible by the id_user sended.
 */
function get_user_visible_users ($id_user = 0, $access = "IR", $only_name = true, $both = true, $anygroup = false, $search = '', $check_acl=true) {
	global $config;
	
	$values = array ();
	
	if ($id_user === 0) {
		$id_user = $config['id_user'];
	}
	
	$level = get_db_sql("SELECT nivel FROM tusuario WHERE id_usuario = '$id_user'");
	
	// Standalone user only can see himself
	if ($level == -1){
		$values[$id_user] = get_db_sql ("SELECT nombre_real FROM tusuario WHERE id_usuario = '$id_user'");
		return $values;
	}
	
	$project_users = get_project_manager_users();
	
	if (!empty($project_users)) {
		$proj_users_condition = sprintf(' OR u.id_usuario in ("%s") ',implode('","',$project_users));
	}
	else {
		$proj_users_condition = '';
	}
	
	
	// Group All has id = 1
	if (give_acl ($id_user, 1, $access) && $both) {
		$companies_sql = '';
		if ($search != '') {
			$sql_companies = "SELECT id FROM tcompany WHERE name LIKE '%".$search."%'";
			$companies = get_db_all_rows_sql ($sql_companies);

			if ($companies == false) {
				$companies_sql = '';
			} else {
				$i = 0;
				foreach ($companies as $company) {
					if ($i == 0) {
						$companies_result = $company['id'];
					} else {
						$companies_result .= ','.$company['id'];
					}
					$i++;
				}

				$companies_sql = " OR id_company IN ($companies_result)";
			}
		}
		$sql = sprintf('SELECT * FROM tusuario WHERE id_usuario LIKE "%s" OR nombre_real LIKE "%s" %s ORDER BY id_usuario',"%$search%","%$search%", $companies_sql);

		$users = get_db_all_rows_sql ($sql);
		if ($users === false)
			$users = array ();
		foreach ($users as $user) {
			if ($only_name)
				$values[safe_output($user['id_usuario'])] = $user['nombre_real'];
			else
				$values[$user['id_usuario']] = $user;
		}
	}
	else {
		if($anygroup) {
			$groups = get_groups_id();
		}
		else {
			$sql = sprintf ('SELECT id_grupo FROM tusuario_perfil
					WHERE id_usuario = "%s"', $id_user);
			$groups = get_db_all_rows_sql ($sql);
		}
		
		if ($groups === false)
			$groups = array ();
		foreach ($groups as $group) {
			$sql = sprintf ('SELECT *
					FROM tusuario_perfil p, tusuario u
					WHERE p.id_usuario = u.id_usuario
					AND (u.id_usuario LIKE "%s" OR u.nombre_real LIKE "%s") AND (id_grupo = %d %s)
					ORDER BY u.id_usuario', "%$search%", "%$search%", $group['id_grupo'], $proj_users_condition);
			$users = get_db_all_rows_sql ($sql);
			if ($users === false)
				continue;
			foreach ($users as $user) {
/*
				if (! give_acl ($user["id_usuario"], $group['id_grupo'], $access) && 
					!in_array($user['id_usuario'], $project_users) && 
					$id_user != $user['id_usuario']) {
					continue;
				}
*/
				if ($check_acl) {
					if (! give_acl ($user["id_usuario"], $group['id_grupo'], $access) && 
						!in_array($user['id_usuario'], $project_users) && 
						$id_user != $user['id_usuario']) {
							continue;
					}
				} else {
					if (!in_array($user['id_usuario'], $project_users) && 
						$id_user != $user['id_usuario']) {
						continue;
					}
				}
				if ($only_name)
					$values[safe_output($user['id_usuario'])] = $user['nombre_real'];
				else
					$values[$user['id_usuario']] = $user;
			}
		}
	}

	return $values;
}

// Returns array with the users that belongs to a project
// ----------------------------------------------------------------------
function get_users_project ($id_project) {
	// Show only users assigned to this project
	$sql = "SELECT *
		FROM trole_people_project
		WHERE id_project = $id_project ORDER by id_user";
	$result = get_db_all_rows_sql ($sql);
	
	return $result;
}

function get_incident_workunits ($id_incident) {
	$workunits = get_db_all_rows_field_filter ('tworkunit_incident', 'id_incident',
					$id_incident, 'id_workunit DESC');
	if ($workunits === false)
		return array ();
	return $workunits;
}

function get_inventory_workunits ($id_inventory) {
	$sql = sprintf ("SELECT tworkunit.*, tincidencia.id_incidencia as id_incident
		FROM tworkunit, tworkunit_incident, tincidencia, tincident_inventory
		WHERE tworkunit.id = tworkunit_incident.id_workunit
		AND tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tincidencia.id_incidencia = tincident_inventory.id_incident
		AND tincident_inventory.id_inventory = %d ORDER BY timestamp DESC",
		$id_inventory);
	$workunits = get_db_all_rows_sql ($sql);
	if ($workunits === false)
		return array ();
	return $workunits;
}

function get_workunit_data ($id_workunit) {
	//~ return get_db_row ('tworkunit', 'id', $id_workunit);
	$result = get_db_row ('tworkunit', 'id', $id_workunit);

	foreach ($result as $key=>$data) {
		if (!is_numeric($key)) {
			$wu_data[$key] = $data;
		}
	}
	return $wu_data;
}

function get_building ($id_building) {
	return get_db_row ('tbuilding', 'id', $id_building);
}

function get_buildings ($only_names = true) {
	$buildings = get_db_all_rows_in_table ('tbuilding');
	if ($buildings === false)
		return array ();
	
	if ($only_names) {
		$retval = array ();
		foreach ($buildings as $building) {
			$retval[$building['id']] = $building['name'];
		}
		return $retval;
	}
	
	return $buildings;
}

function print_product_icon ($id_product, $return = false) {
	$output = '';
	
	$icon = (string) get_db_value ('icon', 'tkb_product', 'id', $id_product);
	
	$output .= '<img id="product-icon" width="16" height="16" ';
	if ($icon != '') {
		$output .= 'src="images/products/'.$icon.'"';
	} else {
		$output .= 'src="images/pixel_gray.png" style="display:none"';
	}
	$output .= ' />';
	
	if ($return)
		return $output;
	echo $output;
}

function get_manufacturers ($only_names = true) {
	$manufacturers = get_db_all_rows_in_table ('tmanufacturer');
	if ($manufacturers === false)
		return array ();
	
	if ($only_names) {
		$retval = array ();
		foreach ($manufacturers as $manufacturer) {
			$retval[$manufacturer['id']] = $manufacturer['name'];
		}
		return $retval;
	}
	
	return $manufacturers;
}

function get_sla ($id_sla) {
	return get_db_row ('tsla', 'id', $id_sla);
}

function get_slas ($only_names = true) {
	$slas = get_db_all_rows_in_table ('tsla');
	if ($slas == false)
		return array ();
	
	if ($only_names) {
		$result = array ();
		foreach ($slas as $sla) {
			$result[$sla['id']] = $sla['name'];
		}
		return $result;
	}
	return $slas;
}

function get_contract_sla ($id_contract, $only_name = true) {
	$sql = sprintf ('SELECT tsla.* FROM tcontract, tsla
			WHERE tcontract.id_sla = tsla.id
			AND tcontract.id = %d', $id_contract);
	$sla = get_db_row_sql ($sql);
	if ($sla == false)
		return array ();
	
	if ($only_name) {
		$result = array ();
		$result[$sla['id']] = $sla['name'];
		return $result;
	}
	return $sla;
}

function get_contract_company ($id_contract, $only_name = true) {
	$sql = sprintf ('SELECT tcompany.* FROM tcontract, tcompany
			WHERE tcontract.id_company = tcompany.id
			AND tcontract.id = %d', $id_contract);
	$company = get_db_row_sql ($sql);
	if ($company == false)
		return array ();
	
	if ($only_name) {
		$result = array ();
		$result[$company['id']] = $company['name'];
		return $result;
	}
	return $company;
}

function get_user_company ($id_user, $only_name = true) {
	$sql = sprintf ('SELECT tcompany.* FROM tusuario, tcompany
			WHERE tusuario.id_company = tcompany.id
			AND tusuario.id_usuario = "%s"', $id_user);
	$company = get_db_row_sql ($sql);
	if ($company == false)
		return array ();
	
	if ($only_name) {
		$result = array ();
		$result[$company['id']] = $company['name'];
		return $result;
	}
	return $company;
}

function get_incidents_on_inventory ($id_inventory, $only_names = true) {
	$sql = sprintf ('SELECT tincidencia.*
			FROM tincidencia, tincident_inventory
			WHERE tincidencia.id_incidencia = tincident_inventory.id_incident
			AND tincident_inventory.id_inventory = %d
			ORDER BY tincidencia.inicio DESC',
			$id_inventory);
	$all_incidents = get_db_all_rows_sql ($sql);
	if ($all_incidents == false)
		return array ();
	
	global $config;
	$incidents = array ();
	foreach ($all_incidents as $incident) {
		if (give_acl ($config['id_user'], $incident['id_grupo'], 'IR')) {
			if ($only_names) {
				$incidents[$incident['id']] = $incident['name'];
			} else {
				array_push ($incidents, $incident);
			}
		}
	}
	return $incidents;
}

//~ function get_incident_types ($only_names = true, $no_empty = false) {
function get_incident_types ($only_names = true, $no_empty = false, $id_user = "") {

	global $config;
	
	if ($id_user == "") {
		$id_user = $config['id_user'];
	}
	
	$level = get_db_value('nivel', 'tusuario', 'id_usuario', $id_user);
	if ($level == 1) {
		$final_types = get_db_all_rows_in_table ('tincident_type');
	} else {
		$user_groups = get_db_all_rows_sql ("SELECT id_grupo FROM tusuario_perfil WHERE id_usuario = '".$id_user."'");
		if ($user_groups === false) {
			$user_groups = array();
		}

		$types = get_db_all_rows_in_table ('tincident_type');
		if ($types == false)
			return array ();

		foreach ($types as $id=>$type) {
			if ($type['id_group'] != '0') {

				$groups = explode(',',$type['id_group']);
				foreach ($groups as $group) {
					$group = preg_replace('/^(&#x20;)\.*/',"",$group);
					foreach ($user_groups as $id => $ug) {
						if ($ug['id_grupo'] == 1) { //all
							$final_types[$type['id']] = $type;
						} else {
							$id_g = get_db_value('id_grupo','tgrupo', 'nombre', $group);
							if ($ug['id_grupo'] == $id_g) {
								$final_types[$type['id']] = $type;
							}
						}
					}
				}
			} else {
				$final_types[$type['id']] = $type;
			}
		}
	}

	$types = $final_types;

	if (!$no_empty) {
		//Add without type option
		array_push($types, array("id" => -1, "name" => __("Without type")));
	}	

	if ($only_names) {
		$result = array ();
		foreach ($types as $type) {
			$result[$type['id']] = $type['name'];
		}
		return $result;
	}

	return $types;
}

function print_user_avatar ($id_user = "", $small = false, $return = false) {
	if ($id_user == "") {
		global $config;
		$id_user = $config['id_user'];
	}
	$avatar =  get_db_value ('avatar', 'tusuario', 'id_usuario', $id_user);
	$output = '';
	if ($avatar != '') 
		$output .= '<img src="images/avatars/'.$avatar.'.png" class="avatar_small"/>';
	
	if ($return)
		return $output;
	echo $output;
}

function exists_custom_search_name ($name) {
	return (bool) get_db_value('id', 'tcustom_search', 'name', $name);
}

function create_custom_search ($name, $section, $search_values) {
	global $config;
	
	// It is needed to remove the html entities before serialize the array because
	// the different string lenght can cause an error when unserialize
	foreach ($search_values as $key => $search_value) {
		$search_value = clean_output($search_value);
	}

	$sql = sprintf ('INSERT INTO tcustom_search (section, name, id_user,
		form_values) VALUES ("%s", "%s", "%s", \'%s\')', 
		$section, $name, $config['id_user'], serialize ($search_values));
	return process_sql ($sql, 'insert-id');
}

function get_custom_search ($id_search, $section) {
	global $config;
	
	$sql = sprintf ('SELECT * FROM tcustom_search
		WHERE id = %d
		AND id_user = "%s"
		AND section = "%s"',
		$id_search, $config['id_user'], $section);
	return get_db_row_sql ($sql);
}

function get_incident_files ($id_incident, $order_desc = false) {
	if($order_desc) {
		$order = "id_attachment DESC";
	}
	else {
		$order = "";
	}
	
	return get_db_all_rows_field_filter ('tattachment', 'id_incidencia', $id_incident, $order);
}

function get_incident_file ($id_incident, $id_file) {
	return get_db_row_filter ('tattachment', array('id_incidencia' => $id_incident, 'id_attachment' => $id_file));
}

function get_incident_tracking ($id_incident) {
	return get_db_all_rows_field_filter ('tincident_track', 'id_incident', $id_incident);
}

function get_incident_users ($id_incident) {
	$incident = get_incident ($id_incident);
	$users = array ();
	$userswu = array();
	
	$users['owner'] = get_db_row ('tusuario', 'id_usuario', $incident['id_usuario']);
	$users['creator'] = get_db_row ('tusuario', 'id_usuario', $incident['id_creator']);
	$users['affected'] = people_involved_incident ($id_incident);

	$final_users=array();

	if ($users['affected'])
	foreach ($users['affected'] as $user) {
		if ($users['owner']['id_usuario'] == $user)
			continue;
		if ($users['creator']['id_usuario'] == $user)
			continue;

		$temp = array();
		$temp['id_usuario'] = $user;
		$final_users['affected'][] = $temp;
	}

	$final_users["owner"] = $users['owner'];
	$final_users["creator"] = $users['creator'];

	return $final_users;
}

function check_incident_sla_min_response ($id_incident) {
	$incident = get_incident ($id_incident);
	
	$sla_info = incidents_get_sla_info ($incident['id_grupo']);
	
	if ($sla_info != false) {
		$id_sla_type = $sla_info['id_sla_type'];
		
		switch ($id_sla_type) {
			case 0: //NORMAL SLA
				/* If closed, disable any affected SLA */
				if (($incident['estado'] == 6) || ($incident['estado'] == 7)) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 1: //THIRD PARTY SLA
				/* If closed, disable any affected SLA */
				if ($incident['estado'] <> 6) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 2: //NORMAL SLA AND THIRD PARTY SLA
				if ($incident['estado'] == 7) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
		}
	}

	$slas = incidents_get_incident_slas ($id_incident, false);
	
	$start = strtotime ($incident['inicio']);
	$now = time ();

	/* Check wheter it was updated before, so there's no need to check SLA */
	$update = strtotime ($incident['actualizacion']);

    // Check SLA here.
	foreach ($slas as $sla) {
		if ($now < ($start + $sla['min_response'] * 3600))
			 continue;

        // Incident owner is the last workunit author ?, then SKIP
    	$last_wu = get_incident_lastworkunit ($id_incident);
	
		if ($last_wu && ($last_wu["id_user"] != $incident["id_creator"])){
			return false;
        }

        if ($last_wu["timestamp"] != ""){
            $last_wu_time = strtotime ($last_wu['timestamp']);
        
    		if ($now < ($last_wu_time + $sla['min_response'] * 3600))
    			 continue;
        }

        // Datetime/Time check when exists (version compatibility code), this
        // was added as a 3.0 post-feature :-)

        if (isset($sla["five_daysonly"]) || isset($sla["no_holidays"])){

            $dow = date("w", time());
            $hod = date("G", time());

            // Skip if we're on weekend
            if (($sla["five_daysonly"] == 1) AND (($dow == 0) OR ($dow == 6))){
                continue;
            }
            
            //Check if holidays enabled and today is holidays
            $datecalc = date("Y-m-d", time()).' 00:00:00';
			if (($sla["no_holidays"] == 1) AND is_holidays ($datecalc)) {
				continue;
			}

            // Skip if we're out of job time
            if ($sla["time_from"] != $sla["time_to"]){
                if (($sla["time_from"] > $hod) OR ($sla["time_to"] < $hod)){
                    continue;
                }
            }
        }

        // Seems that we need to fire the SLA :(

	    $sql = sprintf ('UPDATE tincidencia
		    SET affected_sla_id = %d
		    WHERE id_incidencia = %d',
		    $sla['id'], $id_incident);
	    process_sql ($sql);
	
	    /* SLA has been fired */
	    return $sla['id'];
	}

    // No SLA fired.
	return false;
}

function check_incident_sla_max_inactivity ($id_incident) {
	$incident = get_incident ($id_incident);
	
	$sla_info = incidents_get_sla_info ($incident['id_grupo']);
	
	if ($sla_info != false) {
		$id_sla_type = $sla_info['id_sla_type'];
		
		switch ($id_sla_type) {
			case 0: //NORMAL SLA
				/* If closed, disable any affected SLA */
				if (($incident['estado'] == 6) || ($incident['estado'] == 7)) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 1: //THIRD PARTY SLA
				/* If closed, disable any affected SLA */
				if ($incident['estado'] <> 6) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 2: //NORMAL SLA AND THIRD PARTY SLA
				if ($incident['estado'] == 7) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
		}
	}
	
	$slas = incidents_get_incident_slas ($id_incident, false);
	$update = strtotime ($incident['actualizacion']);
	$now = time ();
	foreach ($slas as $sla) {

        // Datetime/Time check when exists (version compatibility code), this
        // was added as a 3.0 post-feature :-)

        if (isset($sla["five_daysonly"]) || isset($sla["no_holidays"])){
			
            $dow = date("w", time());
            $hod = date("G", time());

            // Skip if we're on weekend
            if (($sla["five_daysonly"] == 1) AND (($dow == 0) OR ($dow == 6))){
                continue;
            }
            
            //Check if holidays enabled and today is holidays
            $datecalc = date("Y-m-d", time()).' 00:00:00';;
			if (($sla["no_holidays"] == 1) AND is_holidays ($datecalc)) {
				continue;
			}

            // Skip if we're out of job time
            if ($sla["time_from"] != $sla["time_to"]){
                if (($sla["time_from"] > $hod) OR ($sla["time_to"] < $hod)){
                    continue;
                }
            }
        }

		if ($now < ($update + $sla['max_inactivity'] * 3600))
			 continue;
		$sql = sprintf ('UPDATE tincidencia
			SET affected_sla_id = %d
			WHERE id_incidencia = %d',
			$sla['id'], $id_incident);
		process_sql ($sql);
		
		/* SLA has expired */
		return $sla['id'];
	}
	
	return false;
}

function check_incident_sla_max_response ($id_incident) {
	$incident = get_incident ($id_incident);
	$sla_info = incidents_get_sla_info ($incident['id_grupo']);
	
	if ($sla_info != false) {
		$id_sla_type = $sla_info['id_sla_type'];
		
		switch ($id_sla_type) {
			case 0: //NORMAL SLA
				/* If closed, disable any affected SLA */
				if (($incident['estado'] == 6) || ($incident['estado'] == 7)) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 1: //THIRD PARTY SLA
				/* If closed, disable any affected SLA */
				if ($incident['estado'] <> 6) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
			case 2: //NORMAL SLA AND THIRD PARTY SLA
				if ($incident['estado'] == 7) {
					if ($incident['affected_sla_id']) {
						$sql = sprintf ('UPDATE tincidencia
							SET affected_sla_id = 0
							WHERE id_incidencia = %d',
							$id_incident);
						process_sql ($sql);
					}
					return false;
				}
			break;
		}
	}
	
	$slas = incidents_get_incident_slas ($id_incident, false);
	$start = strtotime ($incident['inicio']);
	$now = time ();
	foreach ($slas as $sla) {

        // Datetime/Time check when exists (version compatibility code), this
        // was added as a 3.0 post-feature :-)

        if (isset($sla["five_daysonly"]) || isset($sla["no_holidays"])){

            $dow = date("w", time());
            $hod = date("G", time());

            // Skip if we're on weekend
            if (($sla["five_daysonly"] == 1) AND (($dow == 0) OR ($dow == 6))){
                continue;
            }
            
            //Check if holidays enabled and today is holidays
            $datecalc = date("Y-m-d", time()).' 00:00:00';;
			if (($sla["no_holidays"] == 1) AND is_holidays ($datecalc)) {
				continue;
			}

            // Skip if we're out of job time
            if ($sla["time_from"] != $sla["time_to"]){
                if (($sla["time_from"] > $hod) OR ($sla["time_to"] < $hod)){
                    continue;
                }
            }
        }

		if ($now < ($start + $sla['max_response'] * 3600))
			 continue;
		$sql = sprintf ('UPDATE tincidencia
			SET affected_sla_id = %d
			WHERE id_incidencia = %d',
			$sla['id'], $id_incident);
		process_sql ($sql);
		
		/* SLA has expired */
		return $sla['id'];
	}
	
	return false;
}

function get_group_default_user ($id_group) {
	$id_user = get_db_value ('id_user_default', 'tgrupo', 'id_grupo', $id_group);
	return get_db_row ('tusuario', 'id_usuario', $id_user);
}

function get_group_default_inventory ($id_group, $only_id = false) {
	$id_inventory= get_db_value ('id_inventory_default', 'tgrupo', 'id_grupo', $id_group);
	
	if($only_id) {
		return $id_inventory;
	}
	
	return get_db_row ('tinventory', 'id', $id_inventory);
}

/** 
 * Returns the n most user with most open inciden assigned
 *
 * @param lim n, number of users to return.
 * @param id_incident, filter to one incident. If false, show all.
 */
function get_most_users_assigned ($lim, $id_incident = false) {
	
	if($id_incident === false) {
		$condition = '';
	}
	else {
		$incident_clause = join(",", $id_incident);
		$condition = ' AND id_incidencia IN ('.$incident_clause.')';
	}
	
	$sql = "SELECT id_usuario, count(id_usuario) AS total FROM tincidencia WHERE estado NOT IN (6,7) 
			$condition GROUP by id_usuario LIMIT ".$lim;


	$most_active_users = get_db_all_rows_sql ($sql);
	
	if ($most_active_users === false) {
		return array ();
	}

	return $most_active_users;
}

/** 
 * Returns the n most active users (users with more hours worked on incidents).
 *
 * @param lim n, number of users to return.
 * @param id_incident, filter to one incident. If false, show all.
 */
function get_most_active_users ($lim, $id_incident = false) {
	if($id_incident === false) {
		$condition = '';
	}
	else {
		if (is_array($id_incident)) {
			$incident_clause = join(",", $id_incident);
		} else {
			$incident_clause = $id_incident;
		}
		$condition = ' AND tworkunit_incident.id_incident IN ('.$incident_clause.')';
	}

	$most_active_users = get_db_all_rows_sql ("SELECT id_user, SUM(duration) as worked_hours
	                                          FROM tworkunit, tworkunit_incident
	                                          WHERE tworkunit.id = tworkunit_incident.id_workunit
	                                          $condition
	                                          GROUP BY id_user
	                                          ORDER BY worked_hours DESC LIMIT $lim");
	if ($most_active_users === false) {
		return array ();
	}

	return $most_active_users;
}

/** 
 * Returns the n most active incidents (incidents with more worked hours).
 *
 * @param lim n, number of incidents to return.
 */
function get_most_active_incidents ($lim, $incident_filter = false) {
		
	$filter_clause = '';	
		
	if ($incident_filter) {
		$filter_clause = join(",", $incident_filter);
		
		$filter_clause = 'AND tincidencia.id_incidencia IN ('.$filter_clause.')';
	}
	
	$sql = "SELECT tincidencia.id_incidencia, titulo, SUM(duration) AS worked_hours
	       FROM tworkunit, tworkunit_incident, tincidencia WHERE tworkunit.id = tworkunit_incident.id_workunit
	        AND tworkunit_incident.id_incident = tincidencia.id_incidencia $filter_clause 
	        GROUP BY tworkunit_incident.id_incident ORDER BY worked_hours DESC LIMIT ". $lim;

	$most_active_incidents = get_db_all_rows_sql ($sql);
	if ($most_active_incidents === false) {
		return array ();
	}

	return $most_active_incidents;
}


/** 
 * Returns the incident SLA compliance percentage, from a single ID incident
 *
 * @param, id_incident, numeric id (PK) from an incident
 */


function get_sla_compliance_single_id ($id_incident) {

        $temp = array();
        $temp2 = array();
        $temp2['id_incidencia'] = $id_incident;
        $temp[] = $temp2;
        return get_sla_compliance ($temp);
}

/** 
 * Returns the incident SLA compliance percentage, from a list of incidents, passed as arguments
 *
 * @param, incidents, array with a list of incidents
 */
function get_sla_compliance ($incidents) {
	global $config;

	require_once ($config['homedir']."/include/functions_incidents.php");

	if (empty($incidents)) {
		return 100;
	}

	$slas = incidents_get_sla_graph_percentages($incidents);

	$sum = array_sum($slas);
	$num = count($slas);

	if ($sum > 0)
		$avg = $sum / $num;
	else
		$avg = 100;

	return $avg;
}

function get_task_end_date_by_user ($now){
	global $config;
	
	$result = array();

	// Search for Project end in this date
	$sql = "SELECT tproject.name as pname, ttask.name as tname, ttask.end as tend, ttask.id as idt, trole_people_task.id_user as user FROM trole_people_task, tproject, ttask WHERE tproject.id = ttask.id_project AND trole_people_task.id_task = ttask.id AND ttask.end = '$now' GROUP BY idt, user";
	$res = mysql_query ($sql);
	while ($row=mysql_fetch_array ($res)){
		$result[] = $row["tname"] ."|".$row["idt"]."|".$row["tend"]."|".$row["pname"]."|".$row["user"];
	}
	return $result;
}

function create_wu_task ($id_task, $id_user, $description, $have_cost, $id_profile, $public, $duration, $timestamp){
	global $config;
	$sql = sprintf ('INSERT INTO tworkunit 
					(timestamp, duration, id_user, description, have_cost, id_profile, public) 
					VALUES ("%s", %f, "%s", "%s", %d, %d, %d)',
					$timestamp, $duration, $id_user, $description, $have_cost, $id_profile, $public);
	$id_workunit = process_sql ($sql, 'insert_id');
	if ($id_workunit !== false) {
		$sql = sprintf ('INSERT INTO tworkunit_task (id_task, id_workunit) VALUES (%d, %d)', -3, $id_workunit);
		$result = process_sql ($sql, 'insert_id');
		return $id_workunit;
	}
	return false;
}


function get_indicent_status () {
	$retval = array ();
	$status = get_db_all_rows_in_table ('tincident_status');
	
	__('New');
	__('Unconfirmed');
	__('Assigned');
	__('Re-opened');
	__('Verified'); 
	__('Pending on a third person');
	__('Closed');
	__('Pending to be closed');
	
	foreach ($status as $stat) {
		/* FIXME: This is a workaround since you don't change or add any status
		 on Integria setup */
		$retval[$stat['id']] = __($stat['name']);
	}
	
	return $retval;
}

function get_incident_resolutions () {
	$retval = array ();
	$resolutions = get_db_all_rows_in_table ('tincident_resolution', 'name');
	
	/* Translators: stands for "Incident is fixed" */
	__('Fixed');
	__('Invalid');
	__('Wont fix');
	__('Duplicate');
	__('Works for me');
	__('Incomplete');
	__('Expired');
	__('Moved');
	__('In process');
	
	foreach ($resolutions as $resolution) {
		$retval[$resolution['id']] = __($resolution['name']);
	}
	
	return $retval;
}

function render_resolution ($res){
	$res2 =  get_db_sql ("SELECT name FROM tincident_resolution WHERE id = ".$res);
	if ($res2 == "")
		return __("None");
	return __($res2);
}

function render_status ($sta){
	$estado = get_db_sql ("SELECT name FROM tincident_status WHERE id = ".$sta);
	return __($estado);
}

function company_invoice_total ($id_company){
	$estado = get_db_sql ("SELECT amount1+amount2+amount3+amount4+amount5 FROM tinvoice WHERE id_company = $id_company");
	return __($estado);
}

function return_user_report_types ($type){
	switch ($type){	
		case 1: return __("Custom report");
				break;
		case 2: return __("User activity graph");
				break;
		case 3: return __("User activity detailed");
				break;
		case 4: return __("Ticket global report");
				break;

	}
}

function get_user_report_types () {
	$types = array();
	$types[1] = __("Custom report");
	$types[2] = __("Project report");
	$types[3] = __("Ticket report");
	$types[4] = __("Ticket global report");
	$types[5] = __("Lead report");

	return $types;
}

function get_user_report_type ($type){
	$types = get_user_report_types();
	return $types[$type];
}

function get_object_types ($only_names = true, $only_show=false) {

	if ($only_show) {
		$sql = "SELECT * FROM tobject_type WHERE show_in_list = 1";
		$types = get_db_all_rows_sql ($sql);
	} else {
		$types = get_db_all_rows_in_table ('tobject_type');
	}

	if ($types == false)
		return array ();
	
	if ($only_names) {
		$result = array ();
		foreach ($types as $type) {
			$result[$type['id']] = $type['name'];
		}
		return $result;
	}
	return $types;
}

function inventory_tracking ($id_inventory, $state, $aditional_data = 0) {
	global $config;
	
    if ($id_inventory == 0)
        return;

	switch ($state) {
		case INVENTORY_CREATED:
			$description = __('Created');
			break;
			
		case INVENTORY_UPDATED:
			$description = __('Updated');
			break;
			
		case INVENTORY_INCIDENT_ADDED:
			$description = __('inventory object in ticket');
			$description .= " -> ".get_db_value ("titulo", "tincidencia", "id_incidencia", $aditional_data);
			break;
		
		case 'INVENTORY_DELETED':
			$description = __('Deleted');
			break;
		
		case INVENTORY_OWNER_CHANGED:
			$description = __('Owner changed') . " -> ";
			if ($aditional_data['old']) {
				$old = get_db_value ("nombre_real", "tusuario", "id_usuario", $aditional_data['old']);
				if ($old) {
					$aditional_data['old'] = $old;
				}
				$description .= "Old: '" . $aditional_data['old'] . "'";
			}
			if ($aditional_data['new']) {
				$new = get_db_value ("nombre_real", "tusuario", "id_usuario", $aditional_data['new']);
				if ($new) {
					$aditional_data['new'] = $new;
				}
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_NAME_CHANGED:
			$description = __('Name changed') . " -> ";
			if ($aditional_data['old']) {
				$description .= "Old: '" . $aditional_data['old'] . "'";
			}
			if ($aditional_data['new']) {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_CONTRACT_CHANGED:
			$description = __('Contract changed') . " -> ";
			if ($aditional_data['old']) {
				$old = get_db_value ("name", "tcontract", "id", $aditional_data['old']);
				if ($old) {
					$aditional_data['old'] = $old;
				}
				$description .= "Old: '" . $aditional_data['old'] . "'";
			}
			if ($aditional_data['new']) {
				$new = get_db_value ("name", "tcontract", "id", $aditional_data['new']);
				if ($new) {
					$aditional_data['new'] = $new;
				}
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_MANUFACTURER_CHANGED:
			$description = __('Manufacturer changed') . " -> ";
			if ($aditional_data['old']) {
				$old = get_db_value ("name", "tmanufacturer", "id", $aditional_data['old']);
				if ($old) {
					$aditional_data['old'] = $old;
				}
				$description .= "Old: '" . $aditional_data['old'] . "'";
			}
			if ($aditional_data['new']) {
				$new = get_db_value ("name", "tmanufacturer", "id", $aditional_data['new']);
				if ($new) {
					$aditional_data['new'] = $new;
				}
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_STATUS_CHANGED:
			$description = __('Status changed') . " -> ";
			if ($aditional_data['old']) {
				$description .= "Old: '" . __($aditional_data['old']) . "'";
			}
			if ($aditional_data['new']) {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . __($aditional_data['new']) . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_OBJECT_TYPE_CHANGED:
			$description = __('Object type changed') . " -> ";
			if ($aditional_data['old']) {
				$old = get_db_value ("name", "tincident_type", "id", $aditional_data['old']);
				if ($old) {
					$aditional_data['old'] = $old;
				}
				$description .= "Old: '" . $aditional_data['old'] . "'";
			}
			if ($aditional_data['new']) {
				$new = get_db_value ("name", "tincident_type", "id", $aditional_data['new']);
				if ($new) {
					$aditional_data['new'] = $new;
				}
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_RECEIPT_DATE_CHANGED:
			$description = __('Receipt date changed') . " -> ";
			if ($aditional_data['old'] && $aditional_data['old'] != '0000-00-00') {
				$description .= "Old: '" . __($aditional_data['old']) . "'";
			}
			if ($aditional_data['new'] && $aditional_data['new'] != '0000-00-00') {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . __($aditional_data['new']) . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_ISSUE_DATE_CHANGED:
			$description = __('Removal date changed') . " -> ";
			if ($aditional_data['old'] && $aditional_data['old'] != '0000-00-00') {
				$description .= "Old: '" . __($aditional_data['old']) . "'";
			}
			if ($aditional_data['new'] && $aditional_data['new'] != '0000-00-00') {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . __($aditional_data['new']) . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_PARENT_UPDATED:
			$description = __('Parent changed: ') . " -> ";
			$old = get_db_value ("name", "tinventory", "id", $aditional_data['old']);
			if ($old) {
				$aditional_data['old'] = $old;
			}
			$description .= "Old: '" . __($aditional_data['old']) . "'";
			if ($aditional_data['new']) {
				$new = get_db_value ("name", "tinventory", "id", $aditional_data['new']);
				if ($new) {
					$aditional_data['new'] = $new;
				}
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: '" . $aditional_data['new'] . "'";
			} else {
				if ($aditional_data['old']) {
					$description .= " - ";
				}
				$description .= "New: " . __('None');
			}
			break;
		
		case INVENTORY_PARENT_CREATED:
			$description = __('Parent added');
			$description .= " -> ".get_db_value ("name", "tinventory", "id", $aditional_data);
			break;
			
		case INVENTORY_OBJECT_TYPE:
			$description = __('Inventory object type added');
			$description .= " -> ".get_db_value ("name", "tobject_type", "id", $aditional_data);
			break;
			
		case INVENTORY_PUBLIC:
			$description = __('Inventory set to public');
			break;
			
		case INVENTORY_PRIVATE:
			$description = __('Inventory set to private');
			break;
		
		case INVENTORY_DESCRIPTION_CHANGED:
			$description = __('Description changed');
			break;
		
		case INVENTORY_COMPANIES_CREATED:
			$description = __('Companies added');
			break;
		
		case INVENTORY_COMPANIES_UPDATED:
			$description = __('Companies updated');
			break;
		
		case INVENTORY_USERS_CREATED:
			$description = __('Users added');
			break;
		
		case INVENTORY_USERS_UPDATED:
			$description = __('Users updated');
			break;
		
		default:
			$description = __('Unknown update');
			break;
	}
	$fecha = print_mysql_timestamp();	
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory updated", $description);
	$sql = sprintf ('INSERT INTO tinventory_track (id_user, id_inventory,
		timestamp, state, id_aditional, description)
		VALUES ("%s", %d, "%s", %d, "%s", "%s")',
		$config['id_user'], $id_inventory, $fecha, $state, $aditional_data, $description);
	return process_sql ($sql, 'insert_id');
}


/**
 * Function to check if an user can access to a kb item.
 * NOT ENABLED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function check_kb_item_accessibility ($id_user, $id_kb) {
	global $config;
	
	$return = enterprise_hook ('check_kb_item_accessibility_extra', array ($id_user, $id_kb));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

/**
 * Function to get the where clause for tkb_data to filter results
 * by kb product accessibility.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function get_filter_by_kb_product_accessibility () {
	global $config;
	
	$return = enterprise_hook ('get_filter_by_kb_product_accessibility_extra');
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return "WHERE 1=1";
}

/**
 * Function to check if an user can access to a fr item.
 * NOT ENABLED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function check_fr_item_accessibility ($id_user, $id_fr) {
	global $config;
	
	$return = enterprise_hook ('check_fr_item_accessibility_extra', array ($id_user, $id_fr));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

/**
 * Function to get the where clause for tdownload to filter results
 * by fr category accessibility.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function get_filter_by_fr_category_accessibility () {
	global $config;
	
	$return = enterprise_hook ('get_filter_by_fr_category_accessibility_extra');
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return "";
}

/**
 * Function to get the parent to a company
 */
function get_company_parent ($id_company) {
	global $config;
	
	return get_db_value ("id_parent", "tcompany", "id", $id_company);
}

/**
 * Function to get the access permissions to a company
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function check_company_acl ($id_user, $id_company, $flag) {
	global $config;
	
	if (dame_admin($id_user)) {
		return true;
	}
	$standalone = get_standalone_user ($id_user);
	$return = enterprise_hook ('check_company_acl_extra', array ($id_user, $id_company, $flag, $standalone));
		
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	if ($standalone) {
		$company = get_user_company ($id_user, false);
		// standalone users only can see the company which belongs
		if ($company['id'] != $id_company)
			return false;
	}
}

/**
 * This function returns an array with the descendants ids of the
 * company id passed as an argument.
 */
function get_company_descendants ($id_company) {
	global $config;
	
	$text_id_companies = "";
	$id_companies = array();
	if (is_array($id_company)) {
		for ($i = 0 ; $i < count($id_company) ; $i++) {
			$text_id_companies .= $id_company[$i];
			if ($i < count($id_company)-1) {
				$text_id_companies .= ", ";
			}
		}
	} else {
		$text_id_companies .= $id_company;
	}
	$sql = "SELECT id FROM tcompany WHERE id_parent IN (".$text_id_companies.")";
	$result = process_sql($sql);
	
	foreach ($result as $row){
		$id_companies[] = $row['id'];
	}
	if (count($id_companies) >= 1) {
		$id_companies = array_merge($id_companies, get_company_descendants($id_companies));
	}
	return $id_companies;
}

/**
 * Function to get a where filter to filter results
 * by accessible companies.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function get_filter_by_company_accessibility ($id_user) {
	global $config;
	
	$company = get_user_company ($id_user, false);
	if (get_standalone_user ($id_user))
		return "IN (".$company['id'].")";
	$return = enterprise_hook ('get_filter_by_company_accessibility_extra', array($company['id']));
	if ($return !== ENTERPRISE_NOT_HOOK && !dame_admin($id_user))
		return $return;
	return "";
}

// Returns the task of an invoice
function get_invoice_tax ($id_invoice) {
	$tax = get_db_value ('tax', 'tinvoice', 'id', $id_invoice);
	$long_tax = strlen($tax);
	if (substr($tax, -$long_tax, 1) == '{'){
		$tax = json_decode($tax,true);
	}
	return $tax;
}
// Returns the sum task of an invoice
function get_invoice_tax_sum ($id_invoice) {
	$tax = get_db_value ('tax', 'tinvoice', 'id', $id_invoice);
	$long_tax = strlen($tax);
	if (substr($tax, -$long_tax, 1) == '{'){
		$tax = json_decode($tax,true);
		$tax_sum = array_sum($tax);
	} else {
		$tax_sum = $tax; 
	}
	return $tax_sum;
}
// Returns the task_name of an invoice
function get_invoice_tax_name ($id_invoice) {
	$tax_name = get_db_value ('tax_name', 'tinvoice', 'id', $id_invoice);
	$long_tax_name = strlen($tax_name);
	if (substr($tax_name, -$long_tax_name, 1) == '{'){
		$tax_name = json_decode($tax_name,true);
	}
	return $tax_name;
}

// Returns the sum of the amounts of an invoice, with or without taxes
function get_invoice_amount ($id_invoice, $with_taxes = false) {

	$sum = get_db_value ('amount1+amount2+amount3+amount4+amount5', 'tinvoice', 'id', $id_invoice);
	
	if ($with_taxes && $sum) {
		$tax = get_invoice_tax ($id_invoice);
		if ($tax == false)
			return __('ERROR');
		$sum += $sum * ($tax / 100);
	}
	if ($sum == false)
		return __('ERROR');
	return $sum;
}

// Returns the discount before of an invoice
function get_invoice_discount_before ($id_invoice) {
	$discount_before = get_db_value ('discount_before', 'tinvoice', 'id', $id_invoice);
	
	return $discount_before;
}

function get_concept_invoice_discount_before ($id_invoice) {
	$concept_discount_before = get_db_value ('discount_concept', 'tinvoice', 'id', $id_invoice);
	
	return $concept_discount_before;	
}
// Returns the discount after of an invoice
function get_invoice_discount_after ($id_invoice) {
	$discount_after = get_db_value ('discount_after', 'tinvoice', 'id', $id_invoice);
	
	return $discount_after;
}
// Returns the irpf of an invoice
function get_invoice_irpf ($id_invoice) {
	$discount_irpf = get_db_value ('irpf', 'tinvoice', 'id', $id_invoice);
	
	return $discount_irpf;
}

function get_invoice_concept_retention($id_invoice){
	$discount_irpf_name = get_db_value ('concept_irpf', 'tinvoice', 'id', $id_invoice);
	
	return $discount_irpf_name;
}

function get_user_work_home ($id_user, $year){
	global $config;
	$hours = get_db_sql ("SELECT SUM(tworkunit.duration) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND tworkunit_task.id_task > 0 AND id_user = '$id_user' AND timestamp >= '$year-01-00 00:00:00' AND timestamp <= '$year-12-31 23:59:59' AND tworkunit.work_home=1");
	return format_numeric ($hours/$config["hours_perday"]);
}

function get_last_dates () {
	$last_dates = array();
	$last_dates[0] = __("Custom");
	$last_dates[1] = __("Last 24 hours");
	$last_dates[2] = __("Last 2 days");
	$last_dates[7] = __("Last week");
	$last_dates[15] = __("Last 15 days");
	$last_dates[30] = __("Last month");
	$last_dates[90] = __("Last trimester");

	return $last_dates;
}

function get_valid_users_num () {
	clean_cache_db();
	$filter = array(
			'enable_login' => 1,
			'disabled' => 0,
			'login_blocked' => 0
		);
	return get_db_value_filter("COUNT(id_usuario)", "tusuario", $filter);
}

function db_check_minor_relase_available () {
	global $config;
	
	$dir = $config["homedir"]."extras/mr";

	if (file_exists($dir) && is_dir($dir)) {
		if (is_readable($dir)) {
			$files = scandir($dir); // Get all the files from the directory ordered by asc
			if ($files !== false) {
				$pattern = "/^\d+\.sql$/";
				$sqlfiles = preg_grep($pattern, $files); // Get the name of the correct files
				$files = null;
				$pattern = "/\.sql$/";
				$replacement = "";
				$sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles); // Get the number of the file

				$sqlfiles = null;
				
				if ($sqlfiles_num) {
					foreach ($sqlfiles_num as $sqlfile_num) {
						$file = "$dir/$sqlfile_num.sql";
						if ($config["minor_release"] < $sqlfile_num) {
							return true;
						}
					}
				}
			}
		}
	}
	return false;
}

function get_users_in_group ($id_user = false, $id_group = false, $access = 'IR') {
	global $config;
	
	$return = enterprise_hook ('get_users_in_group_extra', array($id_user, $id_group, $access));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

function get_resolution_name ($id_resolution) {
	return get_db_value('name','tincident_resolution', 'id', $id_resolution);
}

function get_status_name ($id_status) {
	return get_db_value('name', 'tincident_status', 'id', $id_status);
}

// ---------------------------------------------------------------
// Returns true is provided user is standalone
// ---------------------------------------------------------------

function get_standalone_user ($id) {
	$nivel = get_db_value ('nivel', 'tusuario', 'id_usuario', $id);
	if ($nivel == -1)
		return true;
	return false;
}

?>
