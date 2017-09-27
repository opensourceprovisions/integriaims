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

global $config;
include_once ("functions_incidents.php");
include_once ("functions_projects.php");

function combo_user_visible_for_me ($id_user, $form_name ="user_form", $any = false, $access = "IR", $return = false, $label = false, $both = true, $anygroup = false) {
	global $config;
	
	$userlist = array ();
	$output = '';

	$values = get_user_visible_users ($config['id_user'], $access, true, $both, $anygroup);
	if ($any)
		$values[''] = __('Any');

	$output .= print_select ($values, $form_name, $id_user, '', '', 0, true, false, false, $label);

	if ($return)
		return $output;
	echo $output;
}




function combo_groups_visible_for_me ($id_user, $form_name ="group_form", $any = 0, $perm = '', $id_group = 0, $return = false, $label = 1) {
	$output = '';

	$values = array ();

	$groups = get_user_groups ($id_user, $perm);
	
	if ($any) {
		$groups[1] = __('Any');
	} else {
         unset($groups[1]);
    }
	
	if ($label == 1)
		$output .= print_select ($groups, $form_name, $id_group, '', '', 0, true, false, false, __('Group'));
	else
		$output .= print_select ($groups, $form_name, $id_group, '', '', 0, true, false, false, '');

	if ($return)
		return $output;
	echo $output;
	return;
}

// Returns a combo with valid profiles for CURRENT user in this task
// ----------------------------------------------------------------------
function combo_user_task_profile ($id_task, $form_name = "work_profile", $selected = "", $id_user = false, $return = false, $no_change = false) {
	global $config;
	
	$output = '';
	
	if ($no_change) {
		$nothing = __('No change');
	} else {
		$nothing = '';
	}
	
	if (! $id_user)
		$id_user = $config['id_user'];
	$where_clause = '';
	if ($id_task)
		$where_clause = sprintf ('AND id_task = %d', $id_task);
	
	// Show only users assigned to this project
	$sql = sprintf ('SELECT trole.id, trole.name
		FROM trole_people_task, trole
		WHERE trole.id = trole_people_task.id_role
		%s
		AND id_user = "%s"
		ORDER BY name',
		$where_clause, $id_user);
	$output .= print_select_from_sql ($sql, $form_name, $selected, '', $nothing, '',
		true, false, false, __('Role'));
	
	if ($return)
		return $output;
	echo $output;
}


// Returns a combo with the users that belongs to a task
// ----------------------------------------------------------------------
function combo_users_task ($id_task, $icon_list = false, $return = false) {
	global $config;
	
	// Show only users assigned to this project
	$task_users = get_db_all_rows_field_filter ('trole_people_task', 'id_task', $id_task);
	$visible_users = get_user_visible_users ($config["id_user"], 'PR', true);
	$users = array ();
	
	if ($task_users) {
		foreach ($task_users as $user) {
			if (isset ($visible_users[$user['id_user']]))
				if ($icon_list)
					array_push ($users, $user);
				else
					$users[$user['id_user']] = $user['id_user'];
		}
	}
	
	$output = '';
	
	if (! $icon_list) {
		$output .= print_select ($users, 'user', '', '', '', '', true,
		0, true, false, false, "width:100px");
	}
	else {
		$text = __('Users').':<br />';
		$users_size = count($users);
		
		$count = 0;
		foreach ($users as $user) {
			$count++;
			$text .= $user["id_user"];
			if ($count < $users_size) {
				$text .= ", ";
			}
		}
		$output .= print_help_tip ($text, true, 'tip_people');
	}
	
	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with the users that belongs to a project
// ----------------------------------------------------------------------
function combo_users_project ($id_project){
	// Show only users assigned to this project
	$sql = "SELECT * FROM trole_people_project WHERE id_project = $id_project ORDER by id_user";
	$result = mysql_query($sql);
	echo "<select name='user' style='width: 100px;'>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id"]."'>".$row["id_user"]." / ".get_db_value ("name","trole","id",$row["id_role"]);
	}
	echo "</select>";
}

// Returns a combo with categories
// ----------------------------------------------------------------------
function combo_kb_categories ($id_category, $show_any = 0){
	global $config;

	echo "<select name='category' style='width: 180px;'>";

	if ($show_any == 1){
		if($id_category == 0) {
			$selected = "selected='selected'";
		}
		else {
			$selected = "";
		}
		echo "<option value='0' $selected>".__("Any")."</option>";
	}
		
	//$sql = "SELECT * FROM tkb_category WHERE id != $id_category ORDER by parent, name";
	$sql = "SELECT * FROM tkb_category ORDER by parent, name";
	$result = mysql_query($sql);

	while ($row=mysql_fetch_array($result)){

		if($row["id"] == $id_category) {
			$selected = "selected='selected'";
		}
		else {
			$selected = "";
		}

		$parent = get_db_value ("name","tkb_category","id",$row["parent"]);
		if ($parent != "") 
			echo "<option value='".$row["id"]."' $selected>".$parent . "/".$row["name"];
		else
			echo "<option value='".$row["id"]."' $selected>".$row["name"];
	}
	echo "</select>";
}


// Returns a combo with products
// ----------------------------------------------------------------------
function combo_kb_products ($id_product, $show_none = 0, $label = '', $return = false) {
	$output = '';
	
	$none = '';
	$none_value = '';
	if ($show_none) {
		$none = __('None');
		$none_value = 0;
	}
	
	$sql = "";
	$output = print_select_from_sql ('SELECT id, name FROM tkb_product ORDER BY name',
		'product', $id_product, '', $none, $none_value, true, false, false, $label);
	
	if ($return)
		return $output;
	echo $output;
}


// Returns a combo with ALL the users available
// ----------------------------------------------------------------------
function combo_users ($actual = "") {
	echo "<select name='user'>";
	if ($actual != ""){ // Show current option
		echo "<option>".$actual;
	}
	$sql = "SELECT * FROM tusuario WHERE id_usuario != '$actual'";
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		echo "<option>".$row["id_usuario"];
	}
	echo "</select>";
}


// Returns a combo with the groups available
// $mode is one ACL for access, like "IR", "AR", or "TW"
// ----------------------------------------------------------------------
function combo_groups ($actual = -1, $mode = "IR") {
	global $config;
	echo "<select id='group' name='group'>";
	if ($actual != -1){
		$sql = "SELECT * FROM tgrupo WHERE id_grupo = $actual";
		$result = mysql_query($sql);
		if ($row=mysql_fetch_array($result)){
			echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
		}
	}
	$sql="SELECT * FROM tgrupo WHERE id_grupo != $actual";
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if (give_acl ($config["id_user"], $row["id_grupo"], $mode) == 1)
			echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
	}
	echo "</select>";
}

// Returns a combo with the incident status available
// ----------------------------------------------------------------------
function combo_incident_status ($actual = -1, $disabled = 0, $actual_only = 0, $return = false, $for_massives = false, $script='', $nothing = 'select', $nothing_value = '0', $label = true, $name = false) {
	$output = '';

	if ($label) {
		$label = __('Status');
	} else {
		$label = "";
	}
	
	if (!$name) {
		$name = 'incident_status';
	}
	
	if ($disabled) {
		$value = __(get_db_value ('name', 'tincident_status', 'id', $actual));
		$output .= print_label ($label, '', '', true, $value);
		if ($return)
			return $output;
		echo $output;
		return;
	}
	if ($actual_only)
		$sql = sprintf ('SELECT id, name FROM tincident_status WHERE id = %d', $actual);
	else
		$sql = 'SELECT id, name FROM tincident_status';
	
	$rows = get_db_all_rows_sql ($sql);
	$values = array ();
	foreach ($rows as $row)
		$values[$row['id']] = __($row['name']);

	if($for_massives) {
		$output .= print_select ($values, 'mass_status', $actual, $script, __('Select'), -1,
			true, false, false, $label);
	}
	else {
		$output .= print_select ($values, $name, $actual, $script, $nothing, $nothing_value,
			true, false, false, $label);
	}

	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with the incident resolution
// ----------------------------------------------------------------------
function combo_incident_resolution ($actual = -1, $disabled = false, $return = false, $for_massives = false, $script = '', $label = true, $name = false, $style = false) {
	$output = '';
	
	if ($label) {
		$label = __('Resolution');
	} else {
		$label = "";
	}
	
	if (!$name) {
		$name = 'incident_resolution';
	}
	
	if ($disabled) {
		$resolutions = get_incident_resolutions ();
		$resolution = isset ($resolutions[$actual]) ? $resolutions[$actual] : __('None');
		
		$output .= print_label ($label, '', '', true, $resolution);
		if ($return)
			return $output;
		echo $output;
		return;
	}

	if($for_massives) {
		$output .= print_select (get_incident_resolutions (),
						'mass_resolution', $actual, '', __('Select'),
						-1, true, false, true, $label, false,$style);
	}
	else {
		$output .= print_select (get_incident_resolutions (),
						$name, $actual, $script, __('None'),
						0, true, false, true, $label, false, $style);
	}
	
	if ($return)
		return $output;
	echo $output;
}

function combo_incident_types ($selected, $disabled = false, $return = false) {
	$output = '';
	
	$types = get_incident_types ();
	
	if ($disabled) {
		$value = isset ($types[$selected]) ? $types[$selected] : __('None');
		$output .= print_label (__('Type'), '', '', true, $value);
		
		if ($return)
			return $output;
		echo $output;
		return;
	}
	
	$output .= print_select ($types, 'id_incident_type', $selected, '',
		__('None'), 0, true, false, true, __('Type'));
	if ($return)
		return $output;
	echo $output;
}


// Returns a combo with the tasks that current user could see
// ----------------------------------------------------------------------
function combo_task_user ($actual, $id_user, $disabled = 0, $show_vacations = 0, $return = false) {
        $output = '';

        if ($disabled) {
                $output .= print_label (__('Task'), '', '', true);
                $name = get_db_value ('name', 'ttask', 'id', $actual);
                if ($name === false)
                        $name = __('N/A');
                $output .= $name;
                if ($return)
                        return $output;
                echo $output;
                return;
        }

        $values = array ();
        $values[0] = __('N/A');
        if ($show_vacations == 1)
                $values[-1] = __('Vacations');

        $sql = sprintf ('SELECT ttask.id, ttask.name as tname, tproject.name as pname
                        FROM tproject, ttask, trole_people_task
                        WHERE ttask.id_project = tproject.id AND tproject.disabled = 0 AND ttask.id = trole_people_task.id_task
                        AND trole_people_task.id_user = "%s"
                        ORDER BY pname',
                        $id_user);
        $tasks = get_db_all_rows_sql ($sql);
        if ($tasks === false)
                $tasks = array ();
        foreach ($tasks as $task) {
                $values[$task['id']] = $task['pname']. " / ". $task['tname'];
        }
        $output = print_select ($values, 'task_user', $actual, '', '',
                                0, true, false, false, __('Task'));
        if ($return)
                return $output;
        echo $output;
        return;
}


// Returns a combo with the projects that current user could see
// ----------------------------------------------------------------------
function combo_project_user ($actual, $id_user, $disabled = 0, $return = false, $full_report = false
	, $start_date = null, $end_date = null, $user_id = false) {
	$output = '';
	global $config;

	if ($disabled) {
		$output .= print_label (__('Project'), '', '', true);
		$name = get_db_value ('name', 'tproject', 'id', $actual);
		if ($name === false)
			$name = __('N/A');
		$output .= $name;
		if ($return)
			return $output;
		echo $output;
		return;
	}

	$values = array ();
	$values[0] = __('N/A');

	if($full_report==100){
		if ($user_id != "")
			$user_search = " AND tworkunit.id_user = '".$user_id . "'";
		else
			$user_search = "";

		if ((dame_admin($config["id_user"])) OR ($config["id_user"] == $user_id)) {
			$sql = sprintf ('SELECT tproject.id as id, tproject.name as name
				FROM tproject, ttask, tworkunit_task, tworkunit
				WHERE tworkunit_task.id_workunit = tworkunit.id '. $user_search . '
				AND tworkunit_task.id_task = ttask.id
				AND ttask.id_project = tproject.id
				AND tworkunit.timestamp >= "%s"
				AND tworkunit.timestamp <= "%s"
				GROUP BY tproject.name',
				$start_date, $end_date);
		} else {
			$sql = sprintf ('SELECT tproject.id as id, tproject.name as name
				FROM tproject, ttask, tworkunit_task, tworkunit
				WHERE tworkunit_task.id_workunit = tworkunit.id '. $user_search . '
				AND tworkunit_task.id_task = ttask.id
				AND ttask.id_project = tproject.id
				AND tworkunit.timestamp >= "%s"
				AND tworkunit.timestamp <= "%s"
				AND tproject.id_owner = "%s" 
				GROUP BY tproject.name',
				$start_date, $end_date, $config["id_user"]);
		}
	} 
	else {
		$sql = sprintf ('SELECT tproject.id, tproject.name as name 
			FROM tproject, ttask, trole_people_task
			WHERE ttask.id_project = tproject.id AND tproject.disabled = 0 AND ttask.id = trole_people_task.id_task
			AND trole_people_task.id_user = "%s"
			ORDER BY pname',
			$id_user);
	}
	$projects = get_db_all_rows_sql ($sql);
	if ($projects === false)
		$projects = array ();
	foreach ($projects as $project) {
		$values[$project['id']] = $project['name'];
	}
	$output = print_select ($values, 'id_project', $actual, '', '',
				0, true, false, false, __('Project'));
	if ($return)
		return $output;
	echo $output;
	return;
}

// Returns a combo with the tasks that current user is working on
// ----------------------------------------------------------------------
function combo_task_user_participant_full_report ($id_user, $show_vacations = false, $actual = 0, $return = false,
 $label = false, $name = false, $nothing = true, $multiple = false, $script = '', $no_change=false, $disabled = false,
 $start_date = null, $end_date = null, $user_id = false) {
	$output = '';
	$values = array ();
	global $config;
	
	if ($show_vacations) {
		$values[-1] = "(*) ".__('Vacations');
		$values[-2] = "(*) ".__('Not working for disease');
		$values[-3] = "(*) ".__('Not justified');
	}
	
	if ($user_id != "")
		$user_search = " AND tworkunit.id_user = '".$user_id . "'";
	else
		$user_search = "";

	// ACL CHECK, show all info (user) or only related info for this user (current user) projects
	
	if ((dame_admin($config["id_user"])) OR ($config["id_user"] == $user_id)) {

		$sql = sprintf ('SELECT ttask.id, tproject.name AS project_name, ttask.name AS task_name
		FROM tproject, ttask, tworkunit_task, tworkunit
		WHERE tworkunit_task.id_workunit = tworkunit.id '. $user_search . '
		AND tworkunit_task.id_task = ttask.id
		AND ttask.id_project = tproject.id
		AND tworkunit.timestamp >= "%s"
		AND tworkunit.timestamp <= "%s"
		GROUP BY ttask.name',
		$start_date, $end_date);

	} else {
	
		// Show only info on my projects for this user
		// TODO: Move this to enterprise code.
		
		$sql = sprintf ('SELECT ttask.id, tproject.name AS project_name, ttask.name AS task_name
		FROM tproject, ttask, tworkunit_task, tworkunit
		WHERE tworkunit_task.id_workunit = tworkunit.id '. $user_search . '
		AND tworkunit_task.id_task = ttask.id
		AND ttask.id_project = tproject.id
		AND tworkunit.timestamp >= "%s"
		AND tworkunit.timestamp <= "%s"
		AND tproject.id_owner = "%s" 
		GROUP BY ttask.name',
		$start_date, $end_date, $config["id_user"]);

	}
	
	$tasks = get_db_all_rows_sql ($sql);
	if ($tasks)
	foreach ($tasks as $task){
		$values[$task['id']] = array('optgroup' => $task['project_name'], 'name' => '&nbsp;'.$task['task_name']);
	}
	
	
	if (!$name) {
		$name = 'id_task';
	}
	
	if ($nothing) {
		$nothing = __('N/A');
	} else {
		$nothing = '';
	}
	
	if ($no_change) {
		$nothing = __('No change');
	}
	
	$output .= print_select ($values, $name, $actual, $script, $nothing, 0, true,
		$multiple, false, $label, $disabled);
	print_select ($values, 'id_project', $actual, '', '',
				0, true, false, false, __('Project'));

	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with the tasks that current user is working on
// ----------------------------------------------------------------------
function combo_task_user_participant ($id_user, $show_vacations = false, $actual = 0, $return = false, $label = false, $name = false, $nothing = true, $multiple = false, $script = '', $no_change=false, $disabled = false) {
	$output = '';
	$values = array ();
	
	if ($show_vacations) {
		$values[-1] = "(*) ".__('Vacations');
		$values[-2] = "(*) ".__('Not working for disease');
		$values[-3] = "(*) ".__('Not justified');
	}
	
	$sql = sprintf ('SELECT ttask.id, tproject.name AS project_name, ttask.name AS task_name
					FROM ttask, trole_people_task, tproject
					WHERE ttask.id_project = tproject.id
					AND tproject.disabled = 0
					AND ttask.id = trole_people_task.id_task
					AND trole_people_task.id_user = "%s" 
					ORDER BY project_name, task_name', $id_user);
	
	//if (dame_admin ($id_user) && $multiple) {
	if (dame_admin ($id_user)) {

		$sql = 'SELECT ttask.id, tproject.name AS project_name, ttask.name AS task_name
				FROM ttask, tproject
				WHERE ttask.id_project = tproject.id
					AND tproject.disabled = 0
				ORDER BY project_name, task_name';
	}
	
	$tasks = get_db_all_rows_sql ($sql);

	if ($tasks)
	foreach ($tasks as $task){
		$values[$task['id']] = array('optgroup' => $task['project_name'], 'name' => '&nbsp;'.$task['task_name']);
	}
	
	
	if (!$name) {
		$name = 'id_task';
	}
	
	if ($nothing) {
		$nothing = __('N/A');
	} else {
		$nothing = '';
	}
	
	if ($no_change) {
		$nothing = __('No change');
	}
	
	$output .= print_select ($values, $name, $actual, $script, $nothing, '0', true,
		$multiple, false, $label, $disabled);

	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with the tasks with manage permission from the user
// ----------------------------------------------------------------------
function combo_task_user_manager ($id_user, $actual = 0, $return = false, $label = false, $name = false,
									$nothing = true, $multiple = false, $id_project = false, $id_task_out = false) {
	$output = '';
	$values = array ();
	
	if ($id_project) {
		$where = "AND id=$id_project";
	} else {
		$where = "";
	}
	
	if ($id_task_out) {
		$task_out = "AND id<>$id_task_out";
	} else {
		$task_out = "";
	}
	
	$sql = "select tp.* from tproject tp, ttask tt, trole_people_project tpp  
			where tp.id=tt.id_project and tpp.id_project=tp.id and disabled = 0 
			and tpp.id_user="."'".$id_user."'"." ORDER BY name;";

	$new = true;
	
	while ($project = get_db_all_row_by_steps_sql($new, $result_project, $sql)) {
		
		$sql = "SELECT *
				FROM ttask
				WHERE id_project=".$project['id']."
					AND id_project IN(SELECT id
									  FROM tproject
									  WHERE disabled=0)
					$task_out
				ORDER BY name";
		$new = true;
		
		$project_access = get_project_access ($id_user, $project['id']);
		// ACL - To continue, the user should have read access
		if ($project_access['read']) {
			
			while ($task = get_db_all_row_by_steps_sql($new, $result_task, $sql)) {
				$new = false;
				
				$task_access = get_project_access ($id_user, $project['id'], $task['id'], false, true);
				// ACL - To show the task, the user should have manage access
				if ($task_access['manage']) {
					$values[$task['id']] = array('optgroup' => $project['name'], 'name' => '&nbsp;'.$task['name']);
				}
			}
		} else {
			$new = false;
		}
	}
	
	if (!$name) {
		$name = 'id_task';
	}
	
	if ($nothing && $nothing !== true) {
		$nothing = $nothing;
	} elseif ($nothing) {
		$nothing = __('N/A');
	} else {
		$nothing = '';
	}

	$output .= print_select ($values, $name, $actual, '', $nothing, '0', true,
		$multiple, false, $label);

	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with the available roles
// ----------------------------------------------------------------------
function combo_roles ($include_na = false, $name = 'role', $label = '', $return = false, $manager = true, $selected = '', $no_change=false) {
	global $config;
	
	$output = '';
	
	$nothing = '';
	$nothing_value = '';
	if ($include_na) {
		$nothing = __('N/A');
		$nothing_value = 0;
	}
	if ($no_change) {
		$nothing = __('No change');
		$nothing_value = -1;
	}
	if ($manager) {
		$output .= print_select_from_sql ('SELECT id, name FROM trole',
			$name, $selected, '', $nothing, $nothing_value, true, false, false, $label);
	} else {
		$output .= print_select_from_sql ('SELECT id, name FROM trole WHERE id<>1',
			$name, $selected, '', $nothing, $nothing_value, true, false, false, $label);
	}
	
	if ($return)
		return $output;
	echo $output;
}

// Returns a combo with projects with id_user inside participants
// ----------------------------------------------------------------------
function combo_projects_user ($id_user, $name = 'project') {
	global $config;

	echo "<select name='$name' style='width:200px'>";
	$sql = "SELECT DISTINCT(id_project) FROM trole_people_project WHERE id_user = '$id_user'";
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		$nombre = get_db_sql("SELECT name FROM tproject WHERE disabled=0 AND id = ".$row[0]);
		if ($nombre != "")
		echo "<option value='".$row[0]."'>".$nombre;
	}
	echo "</select>";
}

function topi_richtext ($string) {
	$imageBullet = "<img src='images/bg_bullet_full_1.gif'>";
	$string = str_replace ( "->", $imageBullet, $string);
	$string = str_replace ( "*", $imageBullet, $string);
	$string = str_replace ( "[b]", "<b>",  $string);
	$string = str_replace ( "[/b]", "</b>",  $string);
	$string = str_replace ( "[u]", "<u>",  $string);
	$string = str_replace ( "[/u]", "</u>",  $string);
	$string = str_replace ( "[i]", "<i>",  $string);
	$string = str_replace ( "[/i]", "</i>",  $string);
	return $string;
}


function show_workunit_data ($workunit, $title, $enable_link = true) {
	global $config;
	
	$timestamp = $workunit["timestamp"];
	$duration = $workunit["duration"];
	$id_user = $workunit["id_user"];
	$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_user);
	$nota = $workunit["description"];
	$id_workunit = $workunit["id"];
	$public = $workunit["public"];
	$locked = $workunit["locked"];
	$profile = $workunit["id_profile"];

	$sql = sprintf ('SELECT tincidencia.id_grupo
			FROM tincidencia, tworkunit_incident
			WHERE tworkunit_incident.id_workunit = %d
			AND tincidencia.id_incidencia = tworkunit_incident.id_incident',
			$id_workunit);
	$id_group = get_db_sql ($sql);

	$sql = sprintf ('SELECT tworkunit_incident.id_incident
                        FROM tincidencia, tworkunit_incident
                        WHERE tworkunit_incident.id_workunit = %d
                        AND tincidencia.id_incidencia = tworkunit_incident.id_incident',
                        $id_workunit);
        $id_incident = get_db_sql ($sql);

	// ACL Check for visibility
	if (!$public && $id_user != $config["id_user"] && ! give_acl ($config["id_user"], $id_group, "IM"))
		return;

	// Show data
	echo '<div class="notetitle">';
	echo "<span>";
	print_user_avatar ($id_user, true);
	if ($enable_link) {
		echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_user'>";
		echo $id_user;
		echo "</a>";
	} else {
		echo $id_user;
	}
	echo " ".__('said').' <span title="'.$timestamp.'">'.human_time_comparation ($timestamp).'</span>';
	echo "</span>";

	// Public WU ?
	echo "<span style='float:right; margin-top: 7px; margin-bottom:0px; padding-right:10px;'>";
	if ($public == 1)
		echo "<img src='images/group.png' title='".__('Public Workunit')."' border=0>";
	else
		echo "<img src='images/delete.png' title='".__('Non public Workunit')."' border=0>";
	echo "</span>";

	// WU Duration 
	echo "<span style='float:right; margin-top: 12px; margin-bottom:0px; padding-right:10px;'>";
	
	// Have a cost ?
	if ($workunit["have_cost"] == 1)
		echo "<img src='images/dollar.png' title='".__('Have a cost')."' border=0>&nbsp;";
	
	echo $duration;
	echo "&nbsp; ".__('Hours');
	
	// Show profilename
	$profile_name = get_db_sql ("SELECT name FROM trole WHERE id = $profile");
	if ($profile_name != ""){
		echo "<i>(".$profile_name.")</i>";
	}
	
	echo "</span>";

	echo "</div>";

	// Body
	echo "<div class='notebody'>";
	
	$link_pattern = '/(https?:\/\/(?:(?!\s|&#x20;|\r|&#x0a;).)+)/i';
	$link_replace = '<a href="$1">$0</a>';
	
	if (strlen ($nota) > 3024) {
		echo "<div id='short_wu_$id_workunit'>";
		echo clean_output_breaks (substr (preg_replace($link_pattern, $link_replace, $nota), 0, 1024));
		echo "<br /><br />";
		echo "<a href='javascript:readMoreWU($id_workunit);'>";
		echo __('Read more...');
		echo "</a>";
		echo "</div>";
		echo "<div id='long_wu_$id_workunit' style='display:none;'>";
		echo clean_output_breaks (preg_replace($link_pattern, $link_replace, $nota));
		echo "</div>";
	} else {
		echo clean_output_breaks (preg_replace($link_pattern, $link_replace, $nota));
	}
	echo "</div>";
}

function show_workunit_user ($id_workunit, $full = 0, $show_multiple=true, $back_to_wu = false, $user = "", $timestamp_h = "", $timestamp_l = "") {
	global $config;
	
	$sql = "SELECT * FROM tworkunit WHERE id = $id_workunit";
	if ($res = mysql_query($sql))
		$row=mysql_fetch_array($res);
	else
		return;

	$timestamp = $row["timestamp"];
	$duration = $row["duration"];
	$id_user = $row["id_user"];
	$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_user);
	$nota = $row["description"];
	$have_cost = $row["have_cost"];
	$profile = $row["id_profile"];
	$public = $row["public"];
	$locked = $row["locked"];
	$work_home = $row["work_home"];
	$id_task = get_db_value ("id_task", "tworkunit_task", "id_workunit", $row["id"]);
	if (! $id_task) {
		$id_incident = get_db_value ("id_incident", "tworkunit_incident", "id_workunit", $row["id"]);
	}
	$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
	$id_profile = get_db_value ("id_profile", "tworkunit", "id", $id_workunit);
	$task_title = get_db_value ("name", "ttask", "id", $id_task);
	if (! $id_task) {
		$incident_title = get_db_value ("titulo", "tincidencia", "id_incidencia", $id_incident);
	}
	$project_title = get_db_value ("name", "tproject", "id", $id_project);

	// ACL Check for visibility
	if (!$public && $id_user != $config["id_user"]) {
		if ($id_task) {
			$task_access = get_project_access ($config["id_user"], false, $id_task, false, true);
			if (! $task_access["manage"]) {
				return;
			}
		} elseif (! give_acl ($config["id_user"], 0, "TM")) {
			return;
		}
	}
echo "<div id='wu_$id_workunit'>";
echo "<form method='post' action='index.php?sec=projects&sec2=operation/projects/task_workunit'>";
	// Show data
	echo "<div class='notetitle'>"; // titulo
	echo "<table class='' width='100%' style='margin: 0px; background: transparent;'>";
	echo "<tr><td rowspan=4 width='2%'>";
	print_user_avatar ($id_user, true);

	echo "<td width='20%'><b>";
	if ($id_task){
		echo __('Task')." </b> : ";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_task=$id_task&operation=view'>$task_title</A>";
	} else  {
		echo __('Ticket')." </b> : ";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=$id_incident'>$incident_title</A>";
	}
	echo "</td>";
	echo "<td><b>";
	if ($id_task) {
		echo __('Project')." </b> : ";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=$id_project'>$project_title</A>";
	} else {
		echo __('Group')."</b> : ";
		echo dame_nombre_grupo (get_db_sql ("SELECT id_grupo FROM tincidencia WHERE id_incidencia = $id_incident"));
	}
	echo "</td>";
	echo "<td width='13%'>";
	echo "<b>".__('Duration')."</b>";
	echo " : ".format_numeric($duration);
	echo "</td>";
	echo "<td>";
	// Public WU ?
	echo "<span style='margin-bottom:0px; padding-right:10px;'>";
	if ($public == 1)
		echo "<img src='images/group.png' title='".__('Public Workunit')."' />";
	else
		echo "<img src='images/delete.png' title='".__('Non public Workunit')."' />";
	echo "</span>";
	echo "</td></tr>";	
	echo "<tr>";
	
	echo "<td><b>";

	if ($have_cost != 0){
		$profile_cost = get_db_value ("cost", "trole", "id", $profile);
		$cost = format_numeric ($duration * $profile_cost);
		$cost = $cost ." &euro;";
	} else
		$cost = __('N/A');
	echo __('Cost');
	echo "</b>";
	echo " : ".$cost;
	echo "</td>";
	echo "<td><b>";
	echo __('Work from home');
	echo "</b>";
	if ($work_home == 0)
		$wfh = __('No');
	else 
		$wfh = __('Yes');
	echo " : ".$wfh;
	echo "</td>";
	echo "<td><b>";
	echo __('Profile');
	echo "</b>";
	$profile_name = get_db_value ("name", "trole", "id", $profile);
	echo " : ";
	echo ($profile_name == false) ? "N/A" : $profile_name;
	echo "</td>";
	if ($show_multiple) {
		echo "<td>";
		echo print_checkbox_extended ('op_multiple[]', $id_workunit, false, false, '', '', true);
		echo "</td>";
	}
	echo "</tr>";
	
	echo "</table>";
	echo "</div>";

	// Body
	//echo "<div class='notebody'>";
	$output = "<div class='notebody' id='wu_$id_workunit'>";
	$output .=  "<table width='100%'  class=''>";
	$output .= "<tr><td valign='top'>";

	if ((strlen($nota) > 1024) AND ($full == 0)) {
		$output .= topi_richtext (clean_output_breaks (substr ($nota, 0, 1024)));
		$output .= "<a href='index.php?sec=users&sec2=operation/users/user_workunit_report&id_workunit=".$id_workunit."&title=$task_title'>";
		$output .= __('Read more...');
		$output .= "</a>";
	} else {
		$output .= topi_richtext(clean_output_breaks($nota));
	}
	$output .= "<td valign='top'>";
	$output .= "<table width='100%'  class=''>";

	if ($_GET["sec2"] == "operation/users/user_workunit_report") {
		$myurl = "index.php?sec=users&sec2=operation/users/user_workunit_report&id=$id_user";
	} else {
		if ($id_project > 0) {
			$myurl = "index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_project=$id_project&id_task=$id_task";
		} else {
			$myurl = "index.php?sec=users&sec2=operation/users/user_workunit_report&id=$id_user";
		}
	}
	
	$belong_to_ticket = get_db_value_sql("SELECT * FROM tworkunit_incident WHERE id_workunit = ".$id_workunit);
	
	if (((project_manager_check($id_project) == 1) OR ($id_user == $config["id_user"]) OR  (give_acl($config["id_user"], 0, "TM"))) && (!$belong_to_ticket)) {
		$output .= "<tr><td align='right'>";
		$output .= "<a class='delete-workunit' id='delete-$id_workunit' href='$myurl&id_workunit=$id_workunit&operation=delete' onclick='if (!confirm(\"".__('Are you sure?')."\")) return false;'><img src='images/cross.png'  title='".__('Delete workunit')."'/></a>";
	}

	// Edit workunit
	if ((((project_manager_check($id_project) == 1) OR (give_acl($config["id_user"], 0, "TM")) OR ($id_user == $config["id_user"])) AND (($locked == "") OR (give_acl($config["id_user"], 0, "UM")) )) && (!$belong_to_ticket)) {
		$output .= "<tr><td align='right'>";
		$output .= "<a class='edit-workunit' id='edit-$id_workunit' href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_project=$id_project&id_task=$id_task&id_workunit=$id_workunit&id_profile=$id_profile&back_to_wu=$back_to_wu&user=$user&timestamp_l=$timestamp_l&timestamp_h=$timestamp_h'><img border=0 src='images/page_white_text.png' title='".__('Edit workunit')."'></a>";
		$output .= "</td>";
	}

	// Lock workunit
	if (!$belong_to_ticket) {
		if (((project_manager_check($id_project) == 1) OR (give_acl($config["id_user"], 0, "TM")) OR ($id_user == $config["id_user"])) AND (($locked == ""))) {
			$output .= "<tr><td align='right'>";
			$output .= "<a class='lock_workunit' id='lock-$id_workunit' href='$myurl&id_workunit=$id_workunit&operation=lock'><img src='images/lock.png' title='".__('Lock workunit')."'></a>";
			$output .= "</td>";
		} else {
			$output .= "<tr><td align='right'>";
			$output .= "<img src='images/rosette.png' title='".__('Locked by')." $locked'";
			$output .= "</td>";
		}
	}

  	$output .= "</tr></table>";
	$output .= "</tr></table>";
	$output .= "</div>";
	
	$title = "<a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_user'>";
	$title .= "<b>".$id_user."</b>";
	$title .= "</a>";
	$title .= " ".__('said on').' '. $timestamp;
	print_container_div("dest-".$id_workunit, $title, $output, 'closed', false, false, '', '', 1, '', "margin-top:0px;");
echo "</form>";
echo "</div>";
}

function form_search_incident ($return = false, $filter=false, $ajax=0) {
	include_once ("functions_user.php");
	global $config;
	$output = '';
	
	if (!$filter) {
		$inverse_filter = (bool) get_parameter ('search_inverse_filter');
		$search_string = (string) get_parameter ('search_string');
		$status = (int) get_parameter ('search_status', -10);
		$priority = (int) get_parameter ('search_priority', -1);
		$resolution = (int) get_parameter ('search_resolution', -1);
		$id_group = (int) get_parameter ('search_id_group');
		$id_inventory = (int) get_parameter ('search_id_inventory');
		$id_company = (int) get_parameter ('search_id_company');
		$search_id_user = (string) get_parameter ('search_id_user');
		$search_id_incident_type = (int) get_parameter ('search_id_incident_type');
		$date_from = (int) get_parameter("search_from_date");
		$date_start = (string) get_parameter("search_first_date");
		$date_end = (string) get_parameter("search_last_date");
		$search_creator = (string) get_parameter ('search_id_creator');
		$search_editor = (string) get_parameter ('search_editor');
		$search_closed_by = (string) get_parameter ('search_closed_by');
		$group_by_project = (bool) get_parameter('search_group_by_project');
		$sla_state = (int)get_parameter('search_sla_state', 0);
		$id_task = (int) get_parameter('search_id_task', 0);
		$left_sla = (int)get_parameter('search_left_sla', 0);
		$right_sla = (int)get_parameter('search_right_sla', 0);
		$show_hierarchy = (bool) get_parameter('search_show_hierarchy');
		$search_medal = get_parameter('search_medals');
		$name = get_parameter('parent_name');

		$type_fields = incidents_get_type_fields ($search_id_incident_type);
		
		$search_type_field = array();
		foreach ($type_fields as $key => $type_field) {
			$search_type_field[$type_field['id']] = (string) get_parameter ('search_type_field_'.$type_field['id']);
		}
	}
	else {
		$inverse_filter = (bool) $filter['inverse_filter'];
		$search_string = (string) $filter['string'];
		$priority = (int) $filter['priority'];
		$id_group = (int) $filter['id_group'];
		$status = (int) $filter['status'];
		$resolution = (int) $filter['resolution'];
		$id_company = (int) $filter['id_company'];
		$id_inventory = (int) $filter['id_inventory'];
		$search_id_incident_type = (int) $filter['id_incident_type'];
		$search_id_user = (string) $filter['id_user'];
		$date_from = (int) $filter['from_date'];
		$date_start = (string) $filter['first_date'];
		$date_end = (string) $filter['last_date'];
		$search_creator = (string) $filter['id_creator'];
		$search_editor = (string) $filter['editor'];
		$search_closed_by = (string) $filter['closed_by'];
		$group_by_project = (bool) $filter['group_by_project'];
		$sla_state = (int) $filter['sla_state'];
		$id_task = (int) $filter['id_task'];
		$left_sla = (int) $filter['left_sla'];
		$right_sla = (int) $filter['right_sla'];
		$show_hierarchy = (bool) $filter['show_hierarchy'];
		$search_medal = (int) $filter['medals'];
		$name = (string) $filter['parent_name']; //This is inventory obj name value !!!


		$type_fields = incidents_get_type_fields ($search_id_incident_type);

		$search_type_field = array();
		if ($type_fields) {
			foreach ($type_fields as $key => $type_field) {
				$search_type_field[$type_field['id']] = (string) $filter['type_field_'.$type_field['id']];
			}
		}
	}
	
	/* No action is set, so the form will be sent to the current page */
	$table = new stdclass;
	$table->width = "100%";
	$table->class = "search-table-button";
	$table->data = array ();
	
	
	// Filter text
	$table->data[0][0] = print_input_text ('search_string', $search_string,
		'', 30, 100, true, __('Text filter'));
	
	// Status
	$available_status = get_indicent_status();
	$available_status[-10] = __("Not closed");
	$table->data[0][1] = print_select ($available_status,'search_status', $status,'', __('Any'), 0, true, false, true,__('Status'));
	
	// Groups
	$groups = users_get_groups_for_select ($config['id_user'], "IW", true, true);
	$table->data[0][2] = print_select ($groups, 'search_id_group', $id_group, '', '', '', true, false, false, __('Group'));

	// Check Box
	$table->data[0][3] = print_checkbox_extended ('search_show_hierarchy', 1, $show_hierarchy, false, '', '', true, __('Show hierarchy'));
	
	$table_advanced = new stdclass;
	$table_advanced->width = "100%";
	$table_advanced->class = "search-table-button";
	
	
	$params_owner = array();
	$params_owner['input_id'] = 'text-search_id_user';
	$params_owner['input_name'] = 'search_id_user';
	$params_owner['input_value'] = $search_id_user;
	$params_owner['title'] = __('Owner');
	$params_owner['attributes'] = 'style="width: 210px;"';
	$params_owner['return'] = true;

	$table_advanced->data[1][0] = user_print_autocomplete_input($params_owner);
	
	$params_editor = array();
	$params_editor['input_id'] = 'text-search_editor';
	$params_editor['input_name'] = 'search_editor';
	$params_editor['input_value'] = $search_editor;
	$params_editor['title'] = __('Editor');
	$params_editor['attributes'] = 'style="width: 210px;"';
	$params_editor['return'] = true;

	$table_advanced->data[1][1] = user_print_autocomplete_input($params_editor);
	
	$params_closed_by = array();
	$params_closed_by['input_id'] = 'text-search_closed_by';
	$params_closed_by['input_name'] = 'search_closed_by';
	$params_closed_by['input_value'] = $search_closed_by;
	$params_closed_by['title'] = __('Closed by');
	$params_closed_by['attributes'] = 'style="width: 210px;"';
	$params_closed_by['return'] = true;

	$table_advanced->data[1][2] = user_print_autocomplete_input($params_closed_by);
	
	$params_creator = array();
	$params_creator['input_id'] = 'text-search_id_creator';
	$params_creator['input_name'] = 'search_id_creator';
	$params_creator['input_value'] = $search_creator;
	$params_creator['title'] = __('Creator');
	$params_creator['attributes'] = 'style="width: 210px;"';
	$params_creator['return'] = true;

	$table_advanced->data[1][3] = user_print_autocomplete_input($params_creator);
	
	$table_advanced->data[2][0] = print_select (get_priorities(), 'search_priority', $priority,
			'', __('Any'), -1, true, false, false, __('Priority'), false);

	$table_advanced->data[2][1] = print_select (get_incident_resolutions(), 'search_resolution', $resolution,
			'', __('Any'), -1, true, false, false, __('Resolution'), false);
			
	//$name = $id_inventory ? get_inventory_name ($id_inventory) : '';
	
	//Parent name
	$table_advanced->data[2][2] =  print_input_text_extended ("parent_name", $name, "text-parent_name", '', 20, 0, false, "", "class='inventory_obj_search' style='width:165px !important;'", true, false,  __('Inventory object'), false, true);
	$table_advanced->data[2][2] .= "&nbsp;&nbsp;" . print_image("images/add.png", true, array("onclick" => "show_inventory_search('','','','','','','','','','', '', '')", "style" => "cursor: pointer"));	
	$table_advanced->data[2][2] .= "&nbsp;&nbsp;" . print_image("images/cross.png", true, array("onclick" => "cleanParentInventory()", "style" => "cursor: pointer"));

	$table_advanced->data[2][2] .= print_input_hidden ('id_parent', $id_inventory, true);
	
	$table_advanced->data[2][3] = get_last_date_control ($date_from, 'search_from_date', __('Date'), $date_start, 'search_first_date', __('Created from'), $date_end, 'search_last_date', __('Created to'));
	$table_advanced->rowspan[2][3] = 2;
	$table_advanced->cellstyle[2][3] = "vertical-align:top;";	
	
	if (!get_standalone_user ($config["id_user"]))
		$table_advanced->data[4][0] = print_select (get_companies (), 'search_id_company', $id_company, '', __('Any'), 0, true, false, false, __('Company'));
			
	$table_advanced->data[4][1] = print_select (get_incident_types (), 'search_id_incident_type',
		$search_id_incident_type, 'javascript:change_type_fields_table(\''.__('Custom field').'\');', __('Any'), 0, true, false, false, __('Ticket type'));
		
	$table_advanced->data[4][3] = print_checkbox_extended ('search_group_by_project', 1, $group_by_project, false, '', '', true, __('Group by project/task'));

	$sla_states = array();
	$sla_states[1] = __('SLA is fired');
	$sla_states[2] = __('SLA is not fired');
	$table_advanced->data[5][0] = print_select ($sla_states, 'search_sla_state', $sla_state, '', __('Any'), 0, true, false, false, __('SLA'));
	
	$table_advanced->data[5][1] = combo_task_user_participant ($config["id_user"], 0, $id_task, true, __("Task"), 'search_id_task');
	
	$table_advanced->data[5][2] = "<div>";
	$table_advanced->data[5][2] .= "<div style='display: inline-block;'>" . print_input_text ('search_left_sla', $left_sla,'', 8, 0, true, __('SLA > (%)'), false) . "</div>";
	
	$table_advanced->data[5][2] .= "&nbsp;<div style='display: inline-block;'>" . print_input_text ('search_right_sla', $right_sla,'', 8, 0, true, __('SLA < (%)'), false) . "</div>";
	$table_advanced->data[5][2] .= "</div>";

	$medals = array();
	$medals[1] = __('Gold medals');
	$medals[2] = __('Black medals');
	$table_advanced->data[5][3] = print_select ($medals, 'search_medals', $search_medal, '', __('Any'), 0, true, false, false, __('Medals'));
	
	$table_type_fields = new StdClass();
	$table_type_fields->width = "100%";
	$table_type_fields->class = "search-table";
	$table_type_fields->data = array();
	$table_type_fields->align[0] = 'left';

	//Print custom field data

	$column = 0;
	$row = 0;
	if ($type_fields) {
		foreach ($type_fields as $key => $type_field) {
			$data = $search_type_field[$type_field['id']];
			switch ($type_field['type']) {
				case "text": 
					$input = print_input_text('search_type_field_'.$type_field['id'], $data, '', 30, 30, true, $type_field['label']);
					break;
				
				case "combo":
					$combo_values = explode(",", $type_field['combo_value']);
					$values = array();
					foreach ($combo_values as $value) {
						$values[$value] = $value;
					}
					$input = print_select ($values, 'search_type_field_'.$type_field['id'], $data, '', __('Any'), '', true, false, false, $type_field['label']);
					break;

				case "linked":
					$linked_values = explode(",", $type_field['linked_value']);
					$values = array();
					foreach ($linked_values as $value) {
						$value_without_parent =  preg_replace("/^.*\|/","", $value);
						$values[$value_without_parent] = $value_without_parent;
						
						$has_childs = get_db_all_rows_sql("SELECT * FROM tincident_type_field WHERE parent=".$type_field['id']);
						if ($has_childs) {
							$i = 0;
							foreach ($has_childs as $child) {
								if ($i == 0) 
									$childs = $child['id'];
								else 
									$childs .= ','.$child['id'];
								$i++;
							}
							$childs = "'".$childs."'";
							$script = 'javascript:change_linked_type_fields_table('.$childs.','.$type_field['id'].');';
						} else {
							$script = '';
						}
					}
					$input = print_select ($values, 'search_type_field_'.$type_field['id'], $data, $script, __('Any'), '', true, false, false, $type_field['label']);
					break;

				case "numeric":
					$input = print_input_number ('search_type_field_'.$type_field['id'], $data, 1, 1000000, '', true, $type_field['label']);
					break;

				case "date":
					$input = print_input_date('search_type_field_'.$type_field['id'], $data, '', '', '', true, $type_field['label']);
					break;

				case "textarea":
					$input = print_input_text('search_type_field_'.$type_field['id'], $data, '', 30, 30, true, $type_field['label']);
					break;
			}

			$table_type_fields->data[$row][$column] = $input;
			if ($column >= 3) {
				$column = 0;
				$row++;
			} else {
				$column++;
			}
		}
	}
	$table_advanced->colspan[6][0] = 4;
	if ($table_type_fields->data) {
		$table_type_fields_html = print_table($table_type_fields, true);
	}
	if(!isset($table_type_fields_html)){
		$table_type_fields_html = '';
	}	
	$table_advanced->data[6][0] = "<div id='table_type_fields'>". $table_type_fields_html ."</div>";

	$table->colspan['row_advanced'][0] = 5;
	$table->data['row_advanced'][0] = print_container_div('advanced_parameters_incidents_search', __('Advanced filter'), print_table($table_advanced, true), 'closed', true, true);
	
	//Store serialize filter
	serialize_in_temp($filter, $config["id_user"]);
	$table->colspan['button'][0] = 2;
	$table->colspan['button'][2] = 2;
	$table->data['button'][0] = '</br>';
	$table->data['button'][2] = print_submit_button (__('Filter'), 'search', false, 'class="sub search"', true);
	$table->data['button'][2] .= print_input_hidden('search_inverse_filter', (int) $inverse_filter, true);
	$table->data['button'][2] .= print_submit_button (__('Inverse filter'), 'submit_inverse_filter', false, 'class="sub search"', true);
	$table->data['button'][2] .= print_button(__('Export to CSV'), '', false, 'window.open(\'' . 'include/export_csv.php?export_csv_tickets=1'. '\')', 'class="sub"', true);
	
	// Inverse filter info
	$output .= '<div id="inverse_filter_info" style="display: '.($inverse_filter ? 'block' : 'none').';">';
	$output .= ui_print_message(__('Inverse filter enabled'), 'suc', 'style="display:inline;padding-top: 15px;padding-bottom: 15px;"', true, 'h3', false);
	$output .= print_help_tip(__('The result will be the items which doesn\'t match the filters')
		. '. ' . __('The select controls with \'Any\' or \'All\' selected will be ignored'), true);
	$output .= '<br /><br />';
	$output .= '</div>';
	
	if($ajax){
		$output .= '<form id="search_incident_form" method="post">';
	} else {
		$output .= '<form id="search_incident_form" method="post" onsubmit="incidents_gift();return false">';
	}
	//~ $output .= '<form id="search_incident_form" method="post">';
	$output .= '<div class="divresult_incidents">' . print_table ($table, true) . '</div>';
	$output .= '</form>';
		
	echo "<div class= 'dialog ui-dialog-content' id='search_inventory_window'></div>";
	
	// WARNING: Important for the inverse filter feature
	// Change the search_inverse_filter value when the form is submitted using the submit_inverse_filter or the search buttons
	// Show or hide the inverse filter info
	$output .= '<script type="text/javascript">';
		$output .= '$(document).ready(function () {';
			$output .= 'var inverseFilterInfo = document.getElementById("inverse_filter_info");';
			$output .= 'var filterForm = document.getElementById("search_incident_form");';
			$output .= 'var filterBtn = filterForm.elements["search"];';
			$output .= 'var inverseFilterBtn = filterForm.elements["submit_inverse_filter"];';
			$output .= 'var inverseFilter = filterForm.elements["search_inverse_filter"];';
			$output .= '$(filterBtn).click(function (e) {';
				$output .= 'inverseFilter.value = 0;';
				$output .= '$(inverseFilterInfo).hide();';
			$output .= '});';
			$output .= '$(inverseFilterBtn).click(function (e) {';
				$output .= 'inverseFilter.value = 1;';
				$output .= '$(inverseFilterInfo).show();';
			$output .= '});';
		$output .= '});';
	$output .= '</script>';
	
	if ($return)
		return $output;
	echo $output;
}

function form_search_users ($return = false, $filter=false) {
	
	include_once ("functions_user.php");
	global $config;
	$output = '';
	
	if (!$filter) {
		$offset = get_parameter ("offset", 0);
		$search_text = get_parameter ("search_text", "");
		$disabled_user = get_parameter ("disabled_user", -1);
		$level = get_parameter ("level", -10);
		$group = get_parameter ("group", 0);
	} else {
		$offset = (int) $filter['offset'];
		$search_text = (string) $filter['search_text'];
		$disabled_user = (int) $filter['disabled_user'];
		$level = (int) $filter['level'];
		$group = (int) $filter['group'];
	}

	$table = new StdClass();
	$table->id = "table-user_search";
	$table->width = "100%";
	$table->class = "search-table";
	$table->size = array ();
	$table->style = array ();
	$table->data = array ();

	$table->data[0][0] = print_input_text ("search_text", $search_text, '', 18, 0, true, __('Search text'));

	$user_status = array();
	$user_status[0] = __('Enabled');
	$user_status[1] = __('Disabled');
	$table->data[1][0] = print_select ($user_status, 'disabled_user', $disabled_user, '', __('Any'), -1, true, 0, false, __('User status'));

	$global_profile = array();
	$global_profile[-1] = __('Standalone');
	$global_profile[0] = __('Grouped');
	$global_profile[1] = __('Administrator');
	$table->data[2][0] = print_select ($global_profile, 'level', $level, '', __('Any'), -10, true, 0, false, __('User mode'));
	
	$group_name = get_user_groups();
	$group_name[-1] = __('Groupless');
	$table->data[3][0] = print_select ($group_name, 'group', $group, '', __('Any'), 0, true, 0, false, __('Group'));
	
	$table->colspan[2][0] = 4;
	$table->data[4][0] = print_submit_button (__('Search'), 'search', false, 'class="sub search"', true);
	
	$output .= '<form name="bskd" method=post id="saved-user-form" action="index.php?sec=users&sec2=godmode/usuarios/lista_usuarios">';
		$output .= print_table ($table, true);
	$output .= '</form>';
		
	if ($return)
		return $output;
	echo $output;
	
}

function incident_users_list ($id_incident, $return = false) {


	function render_sidebox_user_info ($user, $label){

		$output = "";
		$output .= '<div style="text-align:center;"><b>'.__($label).' </b></div>';
	        $output .= '<div class="user_info_sidebox">';
	        $output .= print_user_avatar ($user, true, true);
	        $output .= '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.$user.'">';
	        $output .= ' <strong>'.$user.'</strong></a><br>';
	        $user_data = get_db_row ("tusuario", "id_usuario", $user);
		if ($user_data["nombre_real"] != "")
			$output .= $user_data["nombre_real"]."<br>";
		if ($user_data["telefono"] != "")
		        $output .= $user_data["telefono"]."<br>";
		if ($user_data["direccion"] != "")
		        $output .= $user_data["direccion"];
	        if ($user_data["id_company"] != 0) {
	                $company_name = (string) get_db_value ('name', 'tcompany', 'id', $user_data['id_company']);
	                $output .= "<br>(<em>$company_name</em>)";
	        }
	        $output .= '</div>';
		return $output;
	}

	$output = '';
	
	$users = get_incident_users ($id_incident);

	$output .= '<ul id="incident-users-list" class="sidemenu">';

	// OWNER
	$output .= render_sidebox_user_info ($users['owner']['id_usuario'], "Responsible");

	// CREATOR
        $output .= render_sidebox_user_info ($users['creator']['id_usuario'], "Creator");

	// EDITOR (if different from CREATOR)
	$editor = get_db_sql ("SELECT editor FROM tincidencia WHERE id_incidencia = $id_incident");
	if (($editor != $users['creator']['id_usuario']) AND ($editor != "")){
	       $output .= render_sidebox_user_info ($editor, "Editor");
	}

	//if ($users['affected'])
	// PARTICIPANTS
	if ($users['affected'] == false) {
		$users['affected'] = array();
	}

	foreach ($users['affected'] as $user_item) {
		$user = $user_item["id_usuario"];
		if (!get_standalone_user($user)){
		        $output .= render_sidebox_user_info ($user, "Participant");
		} 
	}
	$output .= '</ul>';
	
	if ($return)
		return $output;
	echo $output;
}

function incident_details_list ($id_incident, $return = false) {
	$output = '';
	
	$incident = get_incident ($id_incident);
	
	$output .= '<ul id="incident-details-list" class="sidemenu">';
	$output .= '&nbsp;&nbsp;<strong>'.__('Open at').'</strong>: '.human_time_comparation($incident['inicio']);
	
	if ($incident['estado'] == 7) {
		$output .= '<br />&nbsp;&nbsp;<strong>'.__('Closed at').'</strong>: '.human_time_comparation($incident['cierre']);
	}
	if ($incident['actualizacion'] != $incident['inicio']) {
		$output .= '<br />&nbsp;&nbsp;<strong>'.__('Last update').'</strong>: '.human_time_comparation($incident['actualizacion']);
	}
	
	/* Show workunits if there are some */
	$workunit_count = get_incident_count_workunits ($id_incident);
	if ($workunit_count) {
		$work_hours = get_incident_workunit_hours ($id_incident);
		$workunits = get_incident_workunits ($id_incident);	
		$workunit_data = get_workunit_data ($workunits[0]['id_workunit']);
		$output .= '<br />&nbsp;&nbsp;<strong>'.__('Last work at').'</strong>: '.human_time_comparation ($workunit_data['timestamp']);
		$output .= '<br />&nbsp;&nbsp;<strong>'.__('Workunits').'</strong>: '.$workunit_count;
		$output .= '<br />&nbsp;&nbsp;<strong>'.__('Time used').'</strong>: '.$work_hours;
		$output .= '<br />&nbsp;&nbsp;<strong>'._('Done by').'</strong>: <em>'.$workunit_data['id_user'].'</em>';
	}
	
	$output .= '</ul>';
	
	if ($return)
		return $output;
	echo $output;
}


function print_table_pager ($id = 'pager', $hidden = true, $return = false) {
	global $config;
	
	$output = '';
	
	$output .= '<div id="'.$id.'" class="'.($hidden ? 'hide ' : '').'pager">';
	$output .= '<form>';
	$output .= '<img src="images/control_start_blue.png" class="first" />';
	$output .= '<img src="images/control_rewind_blue.png" class="prev" /> ';
	$output .= '<input type="text" size=3 class="pagedisplay" />';
	$output .= '<img src="images/control_fastforward_blue.png" class="next" />';
	$output .= '<img src="images/control_end_blue.png" class="last" />';
	$output .= '&nbsp;&nbsp;'. __("Items per page"). '&nbsp;';
	if (defined ('AJAX')) {
		$output .= '<select class="pagesize" style="display: none">';
		$output .= '<option selected="selected" value="10">10</option>';
	} else {
		$output .= '<select class="pagesize">';
		// The id of the following <option> is to recover from ajax the block size
		$output .= '<option id="block_size" selected="selected" value="'.$config['block_size'].'">'.$config['block_size'].'</option>';
		$output .= '<option value="'.($config['block_size'] * 2).'">'.($config['block_size'] * 2).'</option>';
		$output .= '<option value="'.($config['block_size'] * 3).'">'.($config['block_size'] * 3).'</option>';
		$output .= '<option value="'.($config['block_size'] * 5).'">'.($config['block_size'] * 5).'</option>';
		$output .= '<option value="'.($config['block_size'] * 10).'">'.($config['block_size'] * 10).'</option>';
		$output .= '</select>';
	}
	$output .= '</select>';
	$output .= '</form>';
	$output .= '</div>';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Returns a combo with product types
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function combo_product_types ($id_product, $show_any = 0) {
	global $config;
	
	enterprise_include('include/functions_form.php');
	$return = enterprise_hook ('combo_product_types_extra', array ($id_product, $show_any));
	if ($return !== ENTERPRISE_NOT_HOOK) {
		echo $return;
	} else {
		echo "<select name='product' style='width: 180px;'>";
		if ($show_any == 1){
			if($id_product == 0) {
				$selected = "selected='selected'";
			}
			else {
				$selected = "";
			}
			echo "<option value='0' $selected>".__("Any")."</option>";
		}	
		
		$sql = "SELECT * FROM tkb_product ORDER BY 2";

		$result = process_sql($sql);
		if($result == false) {
			$result = array();
		}

		$debug = "";
		foreach ($result as $row){
			if (give_acl($config["id_user"], $row["id_group"], "KR")){
				if($row["id"] == $id_product) {
					$selected = "selected='selected'";
				}
				else {
					$selected = "";
				}
				echo "<option value='".$row["id"]."' $selected>".$row["name"]."</option>";
			}
		}
		echo "</select>";
	}
}

// Returns a combo with download categories
// ----------------------------------------------------------------------
function combo_download_categories ($id_category, $show_any = false, $label = false, $return = false) {
	global $config;

	enterprise_include('include/functions_form.php');
	$result = enterprise_hook ('combo_download_categories_extra', array ($id_category, $show_any, $label, true));
	if ($result === ENTERPRISE_NOT_HOOK) {
		
		$sql = "SELECT * FROM tdownload_category ORDER BY 2";
		$result = process_sql($sql);
		if($result == false) {
			$result = array();
		}

		$categories = array();
		foreach ($result as $row){
			if (give_acl($config["id_user"], $row["id_group"], "KR")){
				$categories[$row["id"]] = $row["name"];
			}
		}

		if ($show_any) {
			$nothing = __('Any');
		} else {
			$nothing = '';
		}
		if ($label) {
			$label = __('Category');
		} else {
			$label = false;
		}
		$result = print_select ($categories, 'id_category', $id_category, '', $nothing, 0, $return, 0, false, $label);

	}
	
	if ($return) {
		return $result;
	} else {
		echo $result;
	}
}

// Returns a combo with the lead progress
// ----------------------------------------------------------------------
function combo_lead_progress ($actual = 0, $disabled = 0, $label = "", $return = false) {
	$output = '';

	$output .= '<div style="text-align:center;"><b>'.__($label).' </b></div>';

	if ($disabled) {
		$output = translate_lead_progress ($actual);
		if ($return)
			return $output;
		echo $output;
		return;
	}

	$output .= print_select (get_incident_origins (), 'incident_origin',
				$actual, '', __("None"), 0, true, false, false, __('Source'));
	
	if ($return)
		return $output;
	echo $output;
} 


// Returns the "legend" for a given lead progress
// ----------------------------------------------

function translate_lead_progress ($progress = 0) {

	$lead_progress = lead_progress_array();

	if (isset($lead_progress[$progress]))
		return $lead_progress[$progress];
	else
		return __("Other");
}

// Returns the "legend" for a given lead estimated close date
// ----------------------------------------------

function translate_lead_estimated_close_date ($estimated_close_date = "") {
	if (!empty($estimated_close_date) && ($unix_timestamp = strtotime($estimated_close_date)) > 0)
		return date('Y-m-d', $unix_timestamp);
	else
		return __("None");
}

// Returns the "legend" for a given lead estimated close date
// ----------------------------------------------

function translate_lead_estimated_sale ($estimated_sale = "") {
	global $config;
	if (!empty($estimated_sale))
		return $estimated_sale . " " . $config["currency"];
	else
		return __("None");
}

// Return an array with current legends for lead progress
// ------------------------------------------------------

function lead_progress_array (){

	$lprogress = get_db_all_rows_in_table("tlead_progress");

	$lead_progress = array();

	foreach ($lprogress as $l) {
		$lead_progress[$l["id"]] = $l["name"];
	}
	
	return $lead_progress;
}

// Returns the "legend" for a given WO progress
// ----------------------------------------------

function translate_wo_status ($progress = 0){

	$wo_progress = wo_status_array();

	if (isset($wo_progress[$progress]))
		return $wo_progress[$progress];
	else
		return __("Other");
}

// Return an array with current legends for WO status
// ------------------------------------------------------

function wo_status_array ($mode = 0){

	$wo_progress = array();
	$wo_progress[0] = __("Pending");
	$wo_progress[1] = __("Finished");
	
	if ($mode == 0)
		$wo_progress[2] = __("Validated");
	return $wo_progress;
}

function combo_roles_people_task ($id_task, $id_user, $label = '', $return = false) {
		
	$roles = get_db_all_rows_filter('trole_people_task', array('id_task'=>$id_task, 'id_user'=>$id_user), 'id_role');
	
	$user_roles = array();
	$output = '';
	
	if ($roles !== false) {
		foreach ($roles as $key=>$rol) {
			$rol_name = get_db_value('name', 'trole', 'id', $rol['id_role']);
			$user_roles[$rol['id_role']] = $rol_name;
		
		}
	}
	
	return print_select ($user_roles, 'id_profile', '', '', 0, 0,true, 0, false, $label);
	
}
?>
