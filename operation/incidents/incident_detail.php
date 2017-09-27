<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

check_login ();

require_once ('include/functions_incidents.php');
require_once ('include/functions_user.php');
require_once ('include/functions_workunits.php');

if (defined ('AJAX')) {
	
	global $config;
	
	$show_type_fields = (bool) get_parameter('show_type_fields', 0);
	$get_data_child = (bool) get_parameter('get_data_child', 0);
	$upload_file = (bool) get_parameter('upload_file');
	$remove_tmp_file = (bool) get_parameter('remove_tmp_file');
	$get_owner = (bool) get_parameter('get_owner', 0);
	$reopen_ticket = (bool) get_parameter('reopen_ticket', 0);
	$get_initial_status = (bool) get_parameter('get_initial_status', 0);
	$get_allowed_status = (bool) get_parameter('get_allowed_status', 0);
	$get_allowed_resolution = (bool) get_parameter('get_allowed_resolution', 0);
	$set_ticket_groups = (bool) get_parameter('set_ticket_groups', 0);
 	
 	if ($show_type_fields) {
 		$config['mysql_result_type'] = MYSQL_ASSOC;
		$id_incident_type = get_parameter('id_incident_type');
		$id_incident = get_parameter('id_incident');		
		$fields = incidents_get_all_type_field ($id_incident_type, $id_incident);
	
		$blocked = get_db_value_filter('blocked', 'tincidencia', array('id_incidencia'=>$id_incident));
		
		$fields_final = array();
		foreach ($fields as $f) {
			$f["data"] = safe_output($f["data"]);
			$f["blocked"] = $blocked;
			if ($f["type"] == "linked") {
				// Label parent
				$label_parent = get_db_value('label', 'tincident_type_field', 'id', $f['parent']);
				$f['label_parent'] = !empty($label_parent) ? $label_parent : '';
				$f['label_parent_enco'] = base64_encode($f['label_parent']);
				
				// Label childs
				if (empty($f['global_id'])) {
					$filter = array('parent' => $f['id']);
				}
				// Global item
				else {
					// Search for the childrem items created under the incident type
					$filter = array(
						'parent' => $f['global_id'],
						'id_incident_type' => $f['id_incident_type']
					);
				}
				$label_childs = get_db_all_rows_filter('tincident_type_field', $filter, 'label');
				
				$f['label_childs'] = '';
				$f['label_childs_enco'] = '';
				if ($label_childs !== false) {
					$i = 0;
					foreach($label_childs as $label) {
						if ($i == 0) {
							$f['label_childs'] = $label['label'];
							$f['label_childs_enco'] = base64_encode($label['label']);
						}
						else { 
							$f['label_childs'] .= ','.$label['label'];
							$f['label_childs_enco'] .= ','.base64_encode($label['label']);
						}
						$i++;
					}
				}
			}

			array_push($fields_final, $f);
		}
		
		echo json_encode($fields_final);
		return;
	}

	if ($upload_file) {
		$result = array();
		$result["status"] = false;
		$result["filename"] = "";
		$result["location"] = "";
		$result["message"] = "";

		$upload_status = getFileUploadStatus("upfile");
		$upload_result = translateFileUploadStatus($upload_status);

		if ($upload_result === true) {
			$filename = $_FILES["upfile"]['name'];
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$invalid_extensions = "/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|
				pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
				htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i";
			
			if (!preg_match($invalid_extensions, $extension)) {
				$result["status"] = true;
				$result["location"] = $_FILES["upfile"]['tmp_name'];
				// Replace conflictive characters
				$filename = str_replace (" ", "_", $filename);
				$filename = filter_var($filename, FILTER_SANITIZE_URL);
				$result["name"] = $filename;

				$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$result["name"];

				if (copy($result["location"], $destination))
					$result["location"] = $destination;
			} else {
				$result["message"] = __('Invalid extension');
			}
		} else {
			$result["message"] = $upload_result;
		}
		echo json_encode($result);
		return;
	}

	if ($remove_tmp_file) {
		$result = false;
		$tmp_file_location = (string) get_parameter('location');
		if ($tmp_file_location) {
			$result = unlink($tmp_file_location);
		}
		echo json_encode($result);
		return;
	}
	
	if ($get_data_child) {
		
		$id_field = get_parameter('id_field', 0);
		if ($id_field) {
			$label_field = get_db_value_sql("SELECT label FROM tincident_type_field WHERE id=".$id_field);
		} else {
			$label_field = get_parameter('label_field');
		}

		$label_field_enco = get_parameter('label_field_enco',0);
		if ($label_field_enco) {
			$label_field_enco = str_replace("&quot;","",$label_field_enco);
			$label_field = base64_decode($label_field_enco);
		}

		$id_parent = get_parameter('id_parent');
		$value_parent = get_parameter('value_parent');
		$value_parent = safe_input(base64_decode($value_parent));

		$sql = "SELECT linked_value FROM tincident_type_field WHERE parent=".$id_parent."
			AND label='".$label_field."'";
		$field_data = get_db_value_sql($sql);

		$result = false;
		if ($field_data != "") {
			$data = explode(',', $field_data);
			foreach ($data as $item) {
				if ($value_parent == 'any') {

					$pos_pipe = strpos($item,'|')+1;
					$len_item = strlen($item);
					$value_aux = substr($item, $pos_pipe, $len_item);
					$result[safe_output($value_aux)] = safe_output($value_aux);
					
				} else {
					$pattern = "/^".$value_parent."\|/";
					if (preg_match($pattern, $item)) {
						$value_aux = preg_replace($pattern, "",$item);
						$result[safe_output($value_aux)] = safe_output($value_aux);
					}
				}
			}
		}

		$sql_id = "SELECT id FROM tincident_type_field WHERE parent=".$id_parent."
					AND label='".$label_field."'";
		$result['id'] = get_db_value_sql($sql_id);
		$result['label'] = $label_field;
		$result['label_enco'] = base64_encode($label_field);
				
		$sql_labels = "SELECT label, id FROM tincident_type_field WHERE parent=".$result['id'];

		$label_childs = get_db_all_rows_sql($sql_labels);

		if ($label_childs != false) {
			$i = 0;
			foreach($label_childs as $label) {
				if ($i == 0) {
					$result['label_childs'] = $label['label'];
					$result['id_childs'] = $label['id'];
					$result['label_childs_enco'] = base64_encode($label['label']);
				} else { 
					$result['label_childs'] .= ','.$label['label'];
					$result['id_childs'] .= ','.$label['id'];
					$result['label_childs_enco'] .= ','.base64_encode($label['label']);
				}
				$i++;
			}
		} else {
			$result['label_childs'] = '';
			$result['label_childs_enco'] = '';
		}

		echo json_encode($result);
		return;
		
	}
	
	if ($get_owner) {
		$id_group = get_parameter('id_group');
		
		if ($config['ticket_owner_is_creator']) {
			$assigned_user_for_this_incident = $config['id_user'];
		} else {
			$assigned_user_for_this_incident = get_db_value("id_user_default", "tgrupo", "id_grupo", $id_group);
		}
		
		echo json_encode($assigned_user_for_this_incident);
		return;

	}
	
	if ($reopen_ticket) {
		$id = (int) get_parameter('id_incident');
		$incident = get_incident ($id);
		$result = process_sql_update ('tincidencia', array('estado' => 4), array('id_incidencia'=>$id));

		if ($result) {
			incidents_set_tracking ($id, 'update', $incident['prioridad'], 4, $incident['resolution'], $config['id_user'], $incident['id_grupo']);
			audit_db ($id_author_inc, $config["REMOTE_ADDR"], "Ticket updated", "User ".$config['id_user']." ticket updated #".$id);
		}
		
		echo json_encode($result);
		return;
	}

	if ($get_initial_status) {
		
		$initial_status = enterprise_hook("incidents_get_initial_status");

		if ($initial_status == ENTERPRISE_NOT_HOOK) {
			$initial_status = incidents_get_all_status();
		}
		
		ksort($initial_status);
		echo json_encode($initial_status);
		return;
	}

	if ($get_allowed_status) {
		$status = get_parameter('status');
		$resolution = get_parameter('resolution', 0);
		
		$allowed_status = enterprise_hook("incidents_get_allowed_status", array($status, true, $resolution));
		
		if ($allowed_status == ENTERPRISE_NOT_HOOK) {
			$allowed_status = incidents_get_all_status();
		}
		
		ksort($allowed_status);
		echo json_encode($allowed_status);
		return;
		
	}
	
	if ($get_allowed_resolution) {
		$status = (int)get_parameter('status');
		$resolution = (int)get_parameter('resolution', 0);
		$id_incident = (int)get_parameter('id_incident');

		$allowed_resolution = enterprise_hook("incidents_get_allowed_resolution", array($status, $resolution, $id_incident));
		
		if ($allowed_resolution == ENTERPRISE_NOT_HOOK) {
			$allowed_resolution = get_incident_resolutions();
		} else {
			if (($status != 7) || (empty($allowed_resolution))) {
				$allowed_resolution = array();
				$allowed_resolution[0] = __("None");
			} 
		}
		
		$i = 0;
		foreach ($allowed_resolution as $id => $value) {
			$resolution_aux[$i][$id] = $value;
			$i++;
		}
		
		echo json_encode($resolution_aux);

		return;
	}
	
	if ($set_ticket_groups) {
		$id_grupo = get_parameter('id_grupo');
		$id_incident_type = (int)get_parameter('id_incident_type');
		$option_any = (int)get_parameter('option_any');
		$id_group_type = safe_output(get_db_value("id_group", "tincident_type", "id", $id_incident_type));
		if($id_group_type != "" && $id_group_type != "0"){
			if(give_acl ($config['id_user'], $id_grupo, "SI")){
				$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "SI", false,  true));
			}
			else{
				$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
			}
			//$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
			$id_group_type = str_replace("    ", "&nbsp;&nbsp;&nbsp;&nbsp;", $id_group_type);
			$groups_selected = explode(', ', $id_group_type);
			$groups = array_intersect(safe_output($groups_all), $groups_selected);
			if($option_any){
				$groups[0] = __('Any');
			}
		} else {
			if(give_acl ($config['id_user'], $id_grupo, "SI")){
				$groups = safe_output(users_get_groups_for_select ($config['id_user'], "SI", false,  true));
			}
			else{
				$groups = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
			}
			if($option_any){
				$groups[0] = __('Any');
			}
		}
		$groups_renamed = array();
		$i=0;
		foreach ($groups as $key => $value) {
			$groups_renamed[$i][0] = $key;
			$groups_renamed[$i][1] = $value;
			$i++;
		}
		echo json_encode($groups_renamed);
		return;
	}
}

$id_grupo = (int) get_parameter ('id_grupo');
$id = (int) get_parameter ('id');
$id_task = (int) get_parameter ('id_task');

$is_enterprise = false;

/* Enterprise support */
if (file_exists ("enterprise/load_enterprise.php")) {
	require_once ("enterprise/load_enterprise.php");
	require_once ("enterprise/include/functions_incidents.php");
	$is_enterprise = true;
}


if ($id) {
	$incident = get_incident ($id);
	if ($incident !== false) {
		$id_grupo = $incident['id_grupo'];
	}
	
	$blocked_incident = get_db_value_filter('blocked', 'tincidencia', array('id_incidencia'=>$id));
}

$check_incident = (bool) get_parameter ('check_incident');

if ($check_incident) {
	// IR and incident creator can see the incident
	if ($incident !== false && (give_acl ($config['id_user'], $id_grupo, "IR")
		|| ($incident["id_creator"] == $config["id_user"]))){
	
		if ((get_standalone_user($config["id_user"])) AND ($incident["id_creator"] != $config["id_user"]))
			echo 0;
		else
			echo 1;
	}
	else
		echo 0;
	if (defined ('AJAX'))
		return;
}

if (isset($incident)) {
	//Incident creators must see their incidents
	$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident, false, "IW"));

	if ($check_acl !== ENTERPRISE_NOT_HOOK && !$check_acl) {
	 	// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket  (Standalone user) ".$id);
		include ("general/noaccess.php");
		exit;
	}
}elseif (!give_acl ($config['id_user'], $id_grupo, "IR") && !give_acl ($config['id_user'], $id_grupo, "SI") && (!get_standalone_user($config["id_user"]))) {
	// Doesn't have access to this page
	
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket ".$id);
	include ("general/noaccess.php");
	exit;
}

$id_grupo = 0;
$texto = "";
$result_msg = "";

$action = get_parameter ('action');

if ($action == 'get-details-list') {
	incident_details_list ($id);
	if (defined ('AJAX'))
		return;
}

if ($action == 'get-users-list') {
	incident_users_list ($id);
	if (defined ('AJAX'))
		return;
}

// Delete incident
$quick_delete = get_parameter("quick_delete");
if ($quick_delete) {
	$id_inc = $quick_delete;
	$sql2="SELECT * FROM tincidencia WHERE id_incidencia=".$id_inc;
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	if ($row2) {
		$id_author_inc = $row2["id_usuario"];
		if (give_acl ($config['id_user'], $row2["id_grupo"], "IM") || $config['id_user'] == $id_author_inc) {
			borrar_incidencia($id_inc);

			$result_msg = __('Ticket successfully deleted');
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "Ticket deleted","User ".$config['id_user']." deleted ticket #".$id_inc);
		} else {
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Forbidden","User ".$config['id_user']." try to delete ticket");
			$result_msg = __('There was a problem deleting ticket');
			no_permission();
		}
	}
	
	$return_values = array();
	$return_values['result'] = $result_msg;
	$massive_number_loop = get_parameter ('massive_number_loop', -1);
	$return_values['massive_number_loop'] = $massive_number_loop;
	// AJAX (Massive operations)
	if ($return_values['massive_number_loop'] > -1) {
		ob_clean();
		echo json_encode($return_values);
		return;
	}
}


if ($action == 'update') {
	// Number of loop in the massive operations. No received in not massive ones
	$massive_number_loop = get_parameter ('massive_number_loop', -1);
	
	$old_incident = get_incident ($id);

	$user = get_parameter('id_user', '');
	
	$grupo = get_parameter ('grupo_form', $old_incident['id_grupo']);
	
	if ($user == '') {
		$sql = 'SELECT id_user_default FROM tgrupo WHERE id_grupo = '.$grupo;
		$default_user = get_db_value_sql($sql);

		if (($default_user == '') || (!$default_user)) {
			$user = $old_incident['id_usuario'];
		} else {
			$user = $default_user;
		}
	}

	$id_author_inc = get_incident_author ($id);
	$titulo = get_parameter ('titulo', $old_incident['titulo']);
	$sla_disabled = (int) get_parameter ('sla_disabled'); //Get SLA given on submit
	$description = get_parameter ('description', $old_incident['descripcion']);
	$priority = get_parameter ('priority_form', $old_incident['prioridad']);
	$estado = get_parameter ('incident_status', $old_incident['estado']);

	$epilog = get_parameter ('epilog', $old_incident['epilog']);
	$resolution = get_parameter ('incident_resolution', $old_incident['resolution']);
	if ($estado != STATUS_CLOSED) {
		$resolution = 0;
	}
	$id_task = (int) get_parameter ('id_task', $old_incident['id_task']);
	$id_incident_type = get_parameter ('id_incident_type', $old_incident['id_incident_type']);
	if ($_POST['id_parent'] == 0) {
		$id_parent = 0;
	} else {
		$id_parent = (int) get_parameter ('id_parent', $old_incident['id_parent']);
	}
	$id_creator = get_parameter ('id_creator', $old_incident['id_creator']);
	$email_copy = get_parameter ('email_copy', '');
	$closed_by = get_parameter ('closed_by', $old_incident['closed_by']);
	$blocked = get_parameter('blocked', $old_incident['blocked']);
	
	if (!$old_incident['old_status2']) {
		$old_status = $old_incident["old_status"];
		$old_resolution = $old_incident["old_resolution"];
		$old_status2 = $estado;
		$old_resolution2 = $resolution;
	} else {
		if (($old_incident['old_status2'] == $estado) && ($old_incident['old_resolution2'] == $resolution)) {
			$old_status = $old_incident["old_status"];
			$old_resolution = $old_incident["old_resolution"];
			$old_status2 = $old_incident["old_status2"];
			$old_resolution2 = $old_incident["old_resolution2"];
		} else {
			$old_status = $old_incident["old_status2"];
			$old_resolution = $old_incident["old_resolution2"];
			$old_status2 = $estado;
			$old_resolution2 = $resolution;
		}
		
	}

	if (($id_incident_type != 0) && ($massive_number_loop == -1)) {	//in the massive operations no change id_incident_type

		$sql_label = "SELECT `label` FROM `tincident_type_field` WHERE id_incident_type = $id_incident_type";
		$labels = get_db_all_rows_sql($sql_label);
		
		if ($labels === false) {
			$labels = array();
		}
	
		foreach ($labels as $label) {
			$values['data'] = get_parameter (base64_encode($label['label']));
			$id_incident_field = get_db_value_filter('id', 'tincident_type_field', array('id_incident_type' => $id_incident_type, 'label'=> $label['label']), 'AND');
			$values['id_incident_field'] = $id_incident_field;
			$values['id_incident'] = $id;
			
			$exists_id = get_db_value_filter('id', 'tincident_field_data', array('id_incident' => $id, 'id_incident_field'=> $id_incident_field), 'AND');
			if ($exists_id) 
				process_sql_update('tincident_field_data', $values, array('id_incident_field' => $id_incident_field, 'id_incident' => $id), 'AND');
			else
				process_sql_insert('tincident_field_data', $values);
		}
	}
	
	if ($id_parent == 0) {
		$idParentValue = null;
	}
	else {
		$idParentValue = sprintf ('%d', $id_parent);
	}
	$timestamp = print_mysql_timestamp();
	
	$values = array(
			'email_copy' => $email_copy,
			'actualizacion' => $timestamp,
			'id_creator' => $id_creator,
			'titulo' => $titulo,
			'estado' => $estado,
			'id_grupo' => $grupo,
			'id_usuario' => $user,
			'closed_by' => $closed_by,
			'prioridad' => $priority,
			'descripcion' => $description,
			'epilog' => $epilog,
			'id_task' => $id_task,
			'resolution' => $resolution,
			'id_incident_type' => $id_incident_type,
			'id_parent' => $idParentValue,
			'affected_sla_id' => 0,
			'sla_disabled' => $sla_disabled,
			'blocked' => $blocked,
			'old_status' => $old_status,
			'old_resolution' => $old_resolution,
			'old_status2' => $old_status2,
			'old_resolution2' => $old_resolution2
		);
		
	// When close incident set close date to current date
	if ($estado == 7) {
		$values['cierre'] = $timestamp;
	}
	// When re-open incident set score to 0
	if(($incident["score"] != 0) AND ($incident["estado"] != 7)){
		$values['score'] = 0;
	}

	$result = process_sql_update('tincidencia', $values, array('id_incidencia' => $id));

	//Add traces and statistic information
	incidents_set_tracking ($id, 'update', $priority, $estado, $resolution, $user, $grupo);
	audit_db ($id_author_inc, $config["REMOTE_ADDR"], "Ticket updated", "User ".$config['id_user']." ticket updated #".$id);
	
	if ($estado == 7) {
		$values_childs['estado'] = $estado;
		$values_childs['closed_by'] = $closed_by;
		
		$childs = incidents_get_incident_childs($id);
		if (!empty($childs)) {
			foreach ($childs as $id_child=>$name) {
				$result_child = process_sql_update('tincidencia', $values_childs, array('id_incidencia' => $id_child));
				audit_db ($id_author_inc, $config["REMOTE_ADDR"], "Ticket updated", "User ".$config['id_user']." ticket updated #".$id_child);
			}
		}
	}

	$old_incident_inventories = array_keys(get_inventories_in_incident($id));
	
	$incident_inventories = get_parameter("inventories");
		
	/* Update inventory objects in incident */
	update_incident_inventories ($id, get_parameter ('inventories', $incident_inventories));
	
	if (!$result)
		$result_msg = __('There was a problem updating ticket/s');
	else {
		$result_msg = __('Ticket/s successfully updated');
		if ($is_enterprise) {
			incidents_run_realtime_workflow_rules ($id);
		}
	}
	
	// Email notify to all people involved in this incident
	// Email in list email-copy
	if ($email_copy != "") { 
		if (($incident["score"] == 0) || ($old_status == 7)) {
			if($estado == 7){
				mail_incident ($id, $user, "", 0, 5, 7);
			} else {
				mail_incident ($id, $user, "", 0, 0, 7);
			}
		}
	}
	// Email owner and creator
	if (($incident["score"] == 0) || ($old_status == 7)) {
		if (($config["email_on_incident_update"] != 3) && ($config["email_on_incident_update"] != 4) && ($estado == 7)) { //add emails only closed
    		mail_incident ($id, $user, "", 0, 5);
    	} else if ($config["email_on_incident_update"] == 0){ //add emails updates
    		mail_incident ($id, $user, "", 0, 0);
		}
	}

	if ( $epilog && ($epilog != $old_incident['epilog']) ) {
		if (include_once('include/functions_workunits.php')) {
			$wu_text = __("Resolution epilog") . ": " . $epilog;
			create_workunit ($id, $wu_text, $config['id_user'], 0, 0, "", 1, 0);
		}
	}
	$return_values = array();
	$return_values['result'] = $result_msg;
	$return_values['massive_number_loop'] = $massive_number_loop;
	
	// AJAX (Massive operations)
	if ($return_values['massive_number_loop'] > -1) {
		ob_clean();
		echo json_encode($return_values);
		return;
	}
	
}

if ($action == "insert" && !$id) {
	$grupo = (int) get_parameter ('grupo_form');
	
	// Read input variables
	$titulo = get_parameter ('titulo', "");
	$description =  get_parameter ('description');
	$priority = get_parameter ('priority_form');
	$id_creator = get_parameter ('id_creator', $config["id_user"]);
	$estado = get_parameter ("incident_status");
	$resolution = get_parameter ("incident_resolution");
	if ($estado != STATUS_CLOSED) {
		$resolution = 0;
	}
	$id_task = (int) get_parameter ("id_task");
	$id_incident_type = get_parameter ('id_incident_type');
	$sla_disabled = (bool) get_parameter ("sla_disabled");
	$id_parent = (int) get_parameter ('id_parent');
	$email_copy = get_parameter ("email_copy", "");
	$creation_date = get_parameter ("creation_date", "");
	$creation_time = get_parameter ("creation_time", "");
	$upfiles = (string) get_parameter('upfiles');
	
	$old_status = $estado;
	$old_resolution = $resolution;
	
	
	$usuario = get_parameter('id_user', '');
	$valid_user = true;
	if ($usuario == '') {
		$sql = 'SELECT id_user_default FROM tgrupo WHERE id_grupo = '.$grupo;

		$default_user = get_db_value_sql($sql);

		if (($default_user == '') || (!$default_user)) {
			$usuario = $config['id_user'];
		} else {
			$usuario = $default_user;
		}
		
	}
	else {
		$is_admin = get_db_value ('nivel', 'tusuario', 'id_usuario', $usuario);
		if ($is_admin != 1) {
			$valid_user = get_db_value_sql("SELECT id_up FROM tusuario_perfil where id_usuario = '$usuario' AND (id_grupo = $grupo OR id_grupo = 1)");
		}
	}
	
	$closed_by = get_parameter ("closed_by", '');
	$blocked = get_parameter ("blocked", 0);

	// Redactor user is ALWAYS the currently logged user entering the incident. Cannot change. Never.
	$editor = $config["id_user"];

    $id_group_creator = get_parameter ("id_group_creator", $grupo);

	$creator_exists = get_user($id_creator);
	$user_exists = get_user($usuario);

	if (!$valid_user) {
		$result_msg  = ui_print_error_message (__('User not valid to this group'), '', true, 'h3', true);
	}
	else if ($titulo == "") {
		$result_msg  = ui_print_error_message (__('Title cannot be empty'), '', true, 'h3', true);
	} 
	else if($creator_exists === false) {
		$result_msg  = ui_print_error_message (__('Creator user does not exist'), '', true, 'h3', true);
	}
	else if($user_exists === false) {
		$result_msg  = ui_print_error_message (__('Owner user does not exist'), '', true, 'h3', true);
	}
	else {
		if ($id_parent == 0) {
			$idParentValue = null;
		}
		else {
			$idParentValue = sprintf ('%d', $id_parent);
		}
		
		// DONT use MySQL NOW() or UNIXTIME_NOW() because 
		// Integria can override localtime zone by a user-specified timezone.
		
		if ($config["change_incident_datetime"] && $creation_date && $creation_time) {
			$timestamp = "$creation_date $creation_time";
		} else {
			$timestamp = print_mysql_timestamp();
		}
		
		$values = array(
				'inicio' => $timestamp,
				'actualizacion' => $timestamp,
				'titulo' => $titulo,
				'descripcion' => $description,
				'id_usuario' => $usuario,
				'closed_by' => $closed_by,
				'estado' => $estado,
				'prioridad' => $priority,
				'id_grupo' => $grupo,
				'id_creator' => $id_creator,
				'id_task' => $id_task,
				'resolution' => $resolution,
				'id_incident_type' => $id_incident_type,
				'id_parent' => $idParentValue,
				'sla_disabled' => $sla_disabled,
				'email_copy' => $email_copy,
				'editor' => $editor,
				'id_group_creator' => $id_group_creator,
				'blocked' => $blocked,
				'old_status' => $old_status,
				'old_resolution' => $old_resolution
			);
		$id = process_sql_insert ('tincidencia', $values);

		if ($id !== false) {
			/* Update inventory objects in incident */
			update_incident_inventories ($id, get_parameter ('inventories'));
			$result_msg = ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			$result_msg .= '<h4><a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'">'.__('Please click here to continue working with incident #').$id."</a></h4>";

			//Add traces and statistic information	
			incidents_set_tracking ($id, 'create', $priority, $estado, $resolution, $usuario, $grupo);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"],
				"Ticket created",
				"User ".$config['id_user']." created incident #".$id);
			
			// Create automatically a WU with the editor ?
			if ($config["incident_creation_wu"] == 1){
				$wu_text = __("WU automatically created by the editor on the incident creation.");
				// Do not send mail in this WU
				create_workunit ($id, $wu_text, $editor, $config["iwu_defaultime"], 0, "", 1, 0);
			}
			
			//insert data to incident type fields
			if ($id_incident_type != 0) {
				$sql_label = "SELECT `label` FROM `tincident_type_field` WHERE id_incident_type = $id_incident_type";
				$labels = get_db_all_rows_sql($sql_label);
			
				if ($labels === false) {
					$labels = array();
				}
				
				foreach ($labels as $label) {
					$id_incident_field = get_db_value_filter('id', 'tincident_type_field', array('id_incident_type' => $id_incident_type, 'label'=> $label['label']), 'AND');
					
					$values_insert['id_incident'] = $id;
					$values_insert['data'] = get_parameter (base64_encode($label['label']));
					$values_insert['id_incident_field'] = $id_incident_field;
					//$id_incident_field = get_db_value('id', 'tincident_type_field', 'id_incident_type', $id_incident_type);
					process_sql_insert('tincident_field_data', $values_insert);
				}
			}
			
			// ATTACH A FILE IF IS PROVIDED
			$upfiles = json_decode(safe_output($upfiles), true);
			if (!empty($upfiles)) {
				include_once('include/functions_workunits.php');
				foreach ($upfiles as $file) {
					if (is_array($file)) {
						if ($file['description']) {
							$file_description = $file['description'];
						} else {
							$file_description = __('No description available');
						}
						$file_result = attach_incident_file ($id, $file["location"], $file_description, $file["name"], false);
					}
				}
			}

			// EXECUTE WORKFLOW RULES AT REALTIME
			if ($is_enterprise) {
				incidents_run_realtime_workflow_rules ($id);
			}
			
			// Email notify to all people involved in this incident
			if ($email_copy != "") { 
				mail_incident ($id, $usuario, "", 0, 1, 7);
			}
			if (($config["email_on_incident_update"] != 3) && ($config["email_on_incident_update"] != 4)) {
				mail_incident ($id, $usuario, "", 0, 1);
			}
			
			// If the ticket creation is successful, redirect the page to the ticket dashboard detail of the new ticket
			echo "<script type=\"text/javascript\">";
			//~ echo	"document.location.search= \"?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=$id\"";
			echo	"document.location.search= \"?sec=incidents&sec2=operation/incidents/incident_search\"";
			echo "</script>";
			exit;
			
		} else {
			$result_msg = ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		}
	}
	
	if (defined ('AJAX')) {
		echo $result_msg;
		return;
	}
	
}

// Edit / Visualization MODE - Get data from database
if ($id) {
	$create_incident = false;
	
	//We could have several problems with cache on incident update
	clean_cache_db();
	
	$incident = get_db_row ('tincidencia', 'id_incidencia', $id);
	// Get values
	$titulo = $incident["titulo"];
	$description = $incident["descripcion"];
	$inicio = $incident["inicio"];
	$actualizacion = $incident["actualizacion"];
	$estado = $incident["estado"];
	$priority = $incident["prioridad"];
	$usuario = $incident["id_usuario"];
	$nombre_real = dame_nombre_real($usuario);
	$id_grupo = $incident["id_grupo"];
	$id_creator = $incident["id_creator"];
	$resolution = $incident["resolution"];
	$epilog = $incident["epilog"];
	$id_task = $incident["id_task"];
	$id_parent = $incident["id_parent"];
	$sla_disabled = $incident["sla_disabled"];
	$affected_sla_id = $incident["affected_sla_id"];
	$id_incident_type = $incident['id_incident_type'];
    $email_copy = $incident["email_copy"];
	$editor = $incident["editor"];
    $id_group_creator = $incident["id_group_creator"];
    $closed_by = $incident["closed_by"];
    $blocked = $incident["blocked"];

	$grupo = dame_nombre_grupo($id_grupo);
        $score = $incident["score"];

}
else {
	$create_incident = true;
	$titulo = "";
	$description = "";
	$priority = 2;
	$id_grupo =0;
	$grupo = dame_nombre_grupo (1);
	$id_parent = 0;
	$usuario= $config["id_user"];
	$estado = 1;
	$resolution = 0;
    $score = 0;
	$epilog = "";
	if ($config['show_creator_blank']) {
		$id_creator = "";
	}
	else {
		$id_creator = $config['id_user'];
	}
	
	$sla_disabled = 0;
	$id_incident_type = 0;
	$affected_sla_id = 0;
    $email_copy = "";
	$editor = $config["id_user"];
    $id_group_creator = 0;
    $closed_by= "";
    $blocked = 0;

}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Show the form
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

$default_responsable = "";

if (! $id) {
	if ($config["enteprise"] == 1){
		// How many groups has this user ?
		$number_group = get_db_sql ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario = '$usuario'");
		// Take first group defined for this user
		$default_id_group = get_db_sql ("SELECT id_grupo FROM tusuario_perfil WHERE id_usuario = '$usuario' LIMIT 1");
	}
	else {
		$default_id_group = 1;
		$number_group = 1;
	}
}

//The user with IW flag can modify all data from the incident.

//~ $has_permission = (give_acl ($config['id_user'], $id_grupo, "IW") || ((get_standalone_user($config["id_user"])) && ($incident["id_creator"] == $config["id_user"])));
$has_permission = (give_acl ($config['id_user'], $id_grupo, "IW") || ((get_standalone_user($config["id_user"]))));
$has_im  = give_acl ($config['id_user'], $id_grupo, "IM");
$has_iw = (give_acl ($config['id_user'], $id_grupo, "IW") || ((get_standalone_user($config["id_user"])) && ($incident["id_creator"] == $config["id_user"])));


if ($id) {	
	
	echo "<h2>";
	if ($affected_sla_id != 0) {
		echo '<img src="images/exclamation.png" border=0 valign=top title="'.__('SLA Fired').'">&nbsp;&nbsp;';
	}

	echo __('Ticket').' #'.$id.'</h2><h4>'.ui_print_truncate_text($incident['titulo'],50);
	echo integria_help ("create_tickets", true);
	echo "&nbsp;&nbsp;".'<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'">';
	echo print_image("images/world.png", true, array("title" => __("Link to ticket"))).'</a>';
	
    if (give_acl($config["id_user"], 0, "IM")){
        if ($incident["score"] > 0){
            echo "( ".__("Scoring");
            echo " ". $incident["score"]. "/10 )";
        }
    }
	
	echo "<div id='button-bar-title'>";
	echo "<ul>";
	echo '<li>';
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'">'.print_image("images/go-previous.png", true, array("title" => __("Back to incident")))."</a>";
	echo '</li>';

	//KB only appears for closed status
	if (give_acl ($config['id_user'], $id_grupo, "KW") && ($incident["estado"] == 7)) {
		echo "<li>";
		echo '<form id="kb_form" name="kb_form" ';
		echo 'class="action" method="post" action="index.php?sec=kb&sec2=operation/kb/manage_data&create=1">';
		print_input_hidden ('id_incident', $id, false);
		echo '<a href="#" id="kb_form_submit">'.__("Add to KB").'</a>';
		echo '</form>';
		echo "</li>";
	}	

	echo '<li>';
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&serialized_filter=1'>".print_image("images/volver_listado.png", true, array("title" => __("Back to list")))."</a>";
	echo '</li>';
		
	/* Delete incident */
	if ($has_im) {
		echo "<li>";
		echo '<form id="delete_incident_form" name="delete_incident_form" class="delete action" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_detail">';
		print_input_hidden ('quick_delete', $id, false);
		echo '<a href="#" id="detele_incident_submit_form">'.print_image("images/papelera_gris.png", true, array("title" => __("Delete"))).'</a>';
		echo '</form>';
		echo "</li>";
		
	}
	echo "</ul>";
	echo "</div>";	

	echo "</h4>";
	
	
	
    // Score this incident  
    if ($id){
		if (($incident["score"] == 0) AND (($incident["id_creator"] == $config["id_user"]) AND ( 
    	($incident["estado"] == 7)))) {
			echo incidents_get_score_table ($id);
    	}
    }

}
else {
	if (! defined ('AJAX')) {
		echo "<h2>".__('Support')."</h2>";
		echo "<h4>".__('Create ticket');
		echo integria_help ("create_tickets", true);
		echo "</h4>";
	}
}

echo '<div class="result">'.$result_msg.'</div>';
$table = new stdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->id = "incident-editor";
$table->size = array ();
$table->size[0] = '430px';
$table->size[1] = '';
$table->size[2] = '';
$table->head = array();
$table->style = array();
$table->data = array ();
$table->cellspacing = 2;
$table->cellpadding = 2;

//~ if (($has_permission && (!isset($blocked_incident))) || (give_acl ($config['id_user'], $id_grupo, "SI") && (!isset($blocked_incident)))) {
if (($has_permission && (!$blocked_incident)) || (give_acl ($config['id_user'], $id_grupo, "SI") && (!$blocked_incident))) {
	$table->data[0][0] = print_input_text_extended ('titulo', $titulo, '', '', 55, 100, false, '', "style='width:300px;'", true, false, __('Title'));
} else {
	$table->data[0][0] = print_label (__('Title'), '', '', true, $titulo);
}

//Get group if was not defined
if($id_grupo==0) {
	$id_grupo_incident = get_db_value("id_grupo", "tusuario_perfil", "id_usuario", $config['id_user']);
	
	//If no group assigned use ALL by default
	if (!$id_grupo_incident) {
		$id_grupo_incident = 1;
	}
	
} else {
	$id_grupo_incident = $id_grupo;
}

$types = get_incident_types (true, $config['required_ticket_type']);
$table->data[0][1] = print_label (__('Ticket type') . print_help_tip (__("If the ticket type is changed, the group can change with it"), true), '','',true);

//Disabled incident type if any, type changes not allowed
if ($id <= 0 || $config["incident_type_change"] == 1 || dame_admin ($config['id_user'])) {
	$disabled_itype = false;
} else {
	$disabled_itype = true;
}
if (!isset($blocked_incident)){
	$blocked_incident = 0;	
}
if ($disabled_itype || $blocked_incident) {
	$disabled_itype = true;
}

if ($config['required_ticket_type']) {
	$select = '';
} else {
	$select = 'select';
}

if (give_acl ($config['id_user'], $id_grupo, "IW") || give_acl ($config['id_user'], $id_grupo, "SI") || (get_standalone_user($config["id_user"]))) {
	$table->data[0][1] .= print_select($types, 'id_incident_type', $id_incident_type, '', $select, '', true, 0, true, false, $disabled_itype);
} else if (give_acl ($config['id_user'], $id_grupo, "SI")){
	$group_escalate_sql = 'select g.nombre from tusuario_perfil u, tgrupo g where g.id_grupo=u.id_grupo and u.id_usuario = "'.$config['id_user'].'"';
	$group_escalate_p = get_db_all_rows_sql($group_escalate_sql);
	foreach ($group_escalate_p as $v) {
		$group_escalate .= $v['nombre']. '|';
	}
	$group_escalate = rtrim($group_escalate, "|");
	$types_escalate_sql = 'select id, name from tincident_type where id_group REGEXP "'.$group_escalate.'"';
	$types_escalate_s = get_db_all_rows_sql($types_escalate_sql);
	$types_escalate = array();
	foreach ($types_escalate_s as $v) {
		$types_escalate[$v['id']] = $v['name'];
	}
	$table->data[0][1] .= print_select($types_escalate, 'id_incident_type', $id_incident_type, '', $select, '', true, 0, true, false, $disabled_itype);
}
$id_group_type = safe_output(get_db_value("id_group", "tincident_type", "id", $id_incident_type));

if($id_group_type != "" && $id_group_type != "0"){
	if(give_acl ($config['id_user'], $id_grupo, "SI")){
		$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "SI", false,  true));
	}
	else{
		$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
	}
	$id_group_type = str_replace("    ", "&nbsp;&nbsp;&nbsp;&nbsp;", $id_group_type);
	$groups_selected = explode(', ', $id_group_type);
	$groups = array_intersect($groups_all, $groups_selected);
} else {
	if(give_acl ($config['id_user'], $id_grupo, "SI")){
		$groups = safe_output(users_get_groups_for_select ($config['id_user'], "SI", false,  true));
	}
	else{
		$groups = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
	}
	$groups_selected = explode(', ', $id_group_type);
}

$table->data[0][2] = print_select ($groups, "grupo_form", $id_grupo_incident, '', '', 0, true, false, false, __('Group'), $blocked_incident) . "<div id='group_spinner'></div>";
$disabled = false;

if ($disabled) {
	$table->data[1][0] = print_label (__('Priority'), '', '', true,
		$priority);
} else {
	$table->data[1][0] = print_select (get_priorities (),
		'priority_form', $priority, '', '',
		'', true, false, false, __('Priority'), $blocked_incident);
}

$table->data[1][0] .= '&nbsp;'. print_priority_flag_image ($priority, true);

$table->data[1][1] = combo_incident_status ($estado, $blocked_incident, 0, true, false, '', '', 0);


if ($incident["estado"] != STATUS_CLOSED) {
	$table->data[1][2] = "<div id='div_incident_resolution' style='display: none;'>";
} else {
	$table->data[1][2] = "<div id='div_incident_resolution'>";
}
	
$table->data[1][2] .= combo_incident_resolution ($resolution, $blocked_incident, true);
$table->data[1][2] .= "</div>";

if(!isset($incident["estado"])){
	$incident["estado"] = '';
}
if ($incident["estado"] != STATUS_CLOSED) {
		$table->data[1][3] = "<div id='div_incident_block' style='display: none;'>";
	} else {
		$table->data[1][3] = "<div id='div_incident_block'>";
	}

$table->data[1][3] .= print_checkbox ("blocked", 1, $blocked, true, __('Blocked'), $blocked_incident);
$table->data[1][3] .= "</div>";

//If IW creator enabled flag is enabled, the user can change the creator
if ($has_im || ($has_iw && $config['iw_creator_enabled'])){

	$disabled_creator = false;
	
	if (!$config["change_creator_owner"] || $blocked_incident || get_standalone_user($config['id_user'])) {
		$disabled_creator = true;
	}
	
	$params_creator['input_id'] = 'text-id_creator';
	$params_creator['input_name'] = 'id_creator';
	$params_creator['input_value'] = $id_creator;
	$params_creator['title'] = __('Creator');
	$params_creator['return'] = true;
	$params_creator['return_help'] = true;
	$params_creator['disabled'] = $disabled_creator;
	$params_creator['attributes'] = 'style="width:210px;"';
	$table->data[2][0] = user_print_autocomplete_input($params_creator);
	
	if (!$disabled_creator) {
		//add button to display info user for creator
		$table->data[2][0] .= "&nbsp;&nbsp;<a href='javascript: incident_show_user_search(\"\", 0);'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
	}
} else {
	$table->data[2][0] = "<input type='hidden' name=id_creator value=$id_creator>";
}

//Check owner for incident
if ($create_incident) {

	if ($config['ticket_owner_is_creator']) {
		$assigned_user_for_this_incident = $usuario;
	} else {
		$assigned_user_for_this_incident = get_db_value("id_user_default", "tgrupo", "id_grupo", $id_grupo_incident);
	}
} else {
	$assigned_user_for_this_incident = $usuario;
}

if ($has_im) {
	$src_code = print_image('images/group.png', true, false, true);
	$disabled_creator = false;
	
	if (!$config["change_creator_owner"] || $blocked_incident || get_standalone_user($config['id_user'])) {
		$disabled_creator = true;
	}
	
	$params_assigned['input_id'] = 'text-id_user';
	$params_assigned['input_name'] = 'id_user';
	$params_assigned['input_value'] = $assigned_user_for_this_incident;
	$params_assigned['title'] = __('Owner');
	$params_assigned['help_message'] = __("The user assigned here will be responsible for managing tickets. If you are opening a ticket and want it to be solved by someone other than yourself, assign this value to another user.");
	$params_assigned['return'] = true;
	$params_assigned['return_help'] = true;
	$params_assigned['disabled'] = $disabled_creator;
	$params_assigned['attributes'] = 'style="width:210px;"';
	$table->data[2][1] = user_print_autocomplete_input($params_assigned);
	
	if (!$disabled_creator) {
		//add button to display info user for owner
		$table->data[2][1] .= "&nbsp;&nbsp;<a href='javascript: incident_show_user_search(\"\", 1);'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
	}
} else {
	$table->data[2][1] = print_input_hidden ('id_user', $assigned_user_for_this_incident, true, __('Owner'));
	$table->data[2][1] .= print_label (__('Owner'), 'id_user', '', true,
	'<div id="plain-id_user">'.dame_nombre_real ($assigned_user_for_this_incident).'</div>');
}

// closed by
if (!$create_incident){
	$params_closed['input_id'] = 'text-closed_by';
	$params_closed['input_name'] = 'closed_by';
	$params_closed['input_value'] = $closed_by;
	$params_closed['title'] = __('Closed by');
	$params_closed['help_message'] = __("User assigned here is user that will be responsible to close the ticket.");
	$params_closed['return'] = true;
	$params_closed['return_help'] = true;
	$params_closed['disabled'] = $blocked_incident;
	$params_closed['attributes'] = 'style="width:210px;"';
	//Only print closed by option when incident status is closed
	if ($incident["estado"] == STATUS_CLOSED) {
		$table->data[2][2] = "<div id='closed_by_wrapper'>";
	} else {
		$table->data[2][2] = "<div id='closed_by_wrapper' style='display: none'>";
	}
	$table->data[2][2] .= user_print_autocomplete_input($params_closed);
	$table->data[2][2] .= "</div>";
} else if ($create_incident && $config["change_incident_datetime"]) {
	$date = date('Y-m-d');
	$time = date('H:i');
		
	$table->data[2][2] = print_input_text ('creation_date', $date, '', 10, 100, true, __('Creation date'), $blocked_incident);
	$table->data[2][2] .= print_input_text ('creation_time', $time, '', 10, 100, true, __('Creation time'), $blocked_incident)
		.print_help_tip (__("The format should be hh:mm"), true);
}

$table->colspan[4][0] = 3;		
//$table->data[4][0] = "<tr id='row_show_type_fields' colspan='4'></tr>";
$table->data[4][0] = "";

//////TABLA ADVANCED
$table_advanced = new stdClass();
$table_advanced->width = '100%';
$table_advanced->class = 'search-table';
$table_advanced->size = array ();
$table_advanced->size[0] = '33%';
$table_advanced->size[1] = '33%';
$table_advanced->size[2] = '33%';
$table_advanced->style = array();
$table_advanced->data = array ();
$table_advanced->colspan[1][1] = 2;

// Table for advanced controls
if ($editor) {
	$table_advanced->data[0][0] = print_label (__('Editor'), '', '', true, $editor);
} else {
	$table_advanced->data[0][0] = "&nbsp;";
}

if ($has_im && $create_incident){
    $groups = get_user_groups ($config['id_user'], "IW");
	$table_advanced->data[0][1] = print_select ($groups, "id_group_creator", $id_grupo_incident, '', '', 0, true, false, false, __('Creator group'), $blocked_incident);
} elseif ($create_incident) {
	$table_advanced->data[0][1] = print_label (__('Creator group'), '', '', true, dame_nombre_grupo ($id_grupo_incident));
} elseif ($id_group_creator) {
	$table_advanced->data[0][1] = print_label (__('Creator group'), '', '', true, dame_nombre_grupo ($id_group_creator));
}

if ($has_im || give_acl ($config['id_user'], $id_grupo, "SI")){
	$table_advanced->data[0][2] = print_checkbox ('sla_disabled', 1, $sla_disabled,	true, __('SLA disabled'), $blocked_incident);

} else {
	$table_advanced->data[0][2] = print_input_hidden ('sla_disabled', 0, true);
}

$parent_name = $id_parent ? (__('Ticket').' #'.$id_parent) : __('None');

if ($has_im || give_acl ($config['id_user'], $id_grupo, "SI")) {
	
	$table_advanced->data[3][0] = print_input_text ('search_parent', $parent_name, '', 10, 100, true, __('Parent ticket'), $blocked_incident);
	$table_advanced->data[3][0] .= print_input_hidden ('id_parent', $id_parent, true);

	if (!$blocked_incident) {
		$table_advanced->data[3][0] .= "&nbsp;&nbsp;<a href='javascript: parent_search_form(\"\", $id)'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
		$table_advanced->data[3][0] .= print_image("images/cross.png", true, array("onclick" => "clean_parent_field()", "style" => "cursor: pointer"));
	}
}

// Show link to go parent incident
if ($id_parent)
	$table_advanced->data[3][0] .= '&nbsp;<a target="_blank" href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id_parent.'"><img src="images/go.png" /></a>';

// Task
$table_advanced->data[3][1] = combo_task_user_participant ($config["id_user"], 0, $id_task, true, __("Task").print_help_tip (__("Task of proyect relation with this ticket"), true), false, true, false, '', false, $blocked_incident);

if ($id_task > 0){
	$table_advanced->data[3][1] .= "&nbsp;&nbsp;<a id='task_link' title='".__('Open this task')."' target='_blank'
							href='index.php?sec=projects&sec2=operation/projects/task_detail&operation=view&id_task=$id_task'>";
	$table_advanced->data[3][1] .= "<img src='images/task.png'></a>";
} else {
	$table_advanced->data[3][1] .= "&nbsp;&nbsp;<a id='task_link' title='".__('Open this task')."' target='_blank' href='javascript:;'></a>";
}


$table_advanced->data[1][1] = print_input_text ('email_copy', $email_copy,"",70,500, true, __("Additional email addresses") . print_help_tip(__("If you will put two or more e-mail adresses, can you put this adresses separated with comma"),true), $blocked_incident);
if (!$blocked_incident) {
	$table_advanced->data[1][1] .= "&nbsp;&nbsp;<a href='javascript: incident_show_contact_search();'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
}

if ($create_incident) {

		$id_inventory = (int) get_parameter ('id_inventory');
		
		$inventories = array ();
		
		if ($id_inventory) {
			if (! give_acl ($config['id_user'], $id_inventory, "VR")) {
				audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation",
					"Trying to access inventory #".$id);
			} else {
				$inventories[$id_inventory] = get_db_value ('name', 'tinventory',
					'id', $id_inventory);
			}
		}
		
		$table_advanced->data[3][2] = print_select ($inventories, 'incident_inventories', NULL,
						'', '', '', true, false, false, __('Objects inventory affected'));

		$table_advanced->data[3][2] .= "&nbsp;&nbsp;<a href='javascript: show_inventory_search(\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";

		$table_advanced->data[3][2] .= "&nbsp;&nbsp;<a href='javascript: removeInventory();'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";
} else {
	$inventories = get_inventories_in_incident ($id);
	
	$table_advanced->data[3][2] = print_select ($inventories, 'incident_inventories',
						NULL, '', '', '',
						true, false, false, __('Objects inventory affected'), $blocked_incident);

	if (!$blocked_incident) {
		$table_advanced->data[3][2] .= "&nbsp;&nbsp;<a href='javascript: show_inventory_search(\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\");'>" . print_image('images/add.png', true, array('title' => __('Add'))) . "</a>";
		$table_advanced->data[3][2] .= "&nbsp;&nbsp;<a href='javascript: removeInventory();'>" . print_image('images/cross.png', true, array('title' => __('Remove'))) . "</a>";
	}

}

foreach ($inventories as $inventory_id => $inventory_name) {
	$table_advanced->data[3][2] .= print_input_hidden ("inventories[]", $inventory_id, true, 'selected-inventories');
}

// END TABLE ADVANCED

$table->colspan['row_advanced'][0] = 4;
$table->data['row_advanced'][0] = print_container_div('advanced_parameters_incidents_form', __('Advanced parameters'), print_table($table_advanced, true), 'closed', true, true);


$table->colspan[9][0] = 4;
$table->colspan[10][0] = 4;
$disabled_str = $disabled ? 'readonly="1"' : '';

$table->data[9][0] = print_textarea ('description', 9, 80, $description, $disabled_str,
true, __('Description'), $blocked_incident);

// This is never shown in create form
if (!$create_incident){

	//Show or hidden epilog depending on incident status
	if ($incident["estado"] != STATUS_CLOSED) {		
		$table->data[10][0] = "<div id='epilog_wrapper' style='display: none;'>";
	} else {
		$table->data[10][0] = "<div id='epilog_wrapper'>";
	}
		
	$table->data[10][0] .= print_textarea ('epilog', 5, 80, $epilog, $disabled_str,	true, __('Resolution epilog'), $blocked_incident);
	
	$table->data[10][0] .= "</div>";
} else {
	// Optional file update
	$html = "";
	$html .= "<div id=\"incident_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
	$html .= 	"<div id=\"drop_file\" style=\"padding:0px 0px;\">";
	$html .= 		"<table width=\"100%\">";
	$html .= 			"<td width=\"45%\">";
	$html .= 				__('Drop the file here');
	$html .= 			"<td>";
	$html .= 				__('or');
	$html .= 			"<td width=\"45%\">";
	$html .= 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
	$html .= 			"<tr>";
	$html .= 		"</table>";
	$html .= 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
	$html .= 		"<input type=\"hidden\" name=\"upfiles\" id=\"upfiles\" />"; // JSON STRING
	$html .= 	"</div>";
	$html .= 	"<ul></ul>";
	$html .= "</div>";

	$table_description = new stdClass;
	$table_description->width = '100%';
	$table_description->id = 'incident_file_description';
	$table_description->class = 'search-table-button';
	$table_description->data = array();
	$table_description->data[0][0] = print_textarea ("file_description", 3, 40, '', '', true, __('Description'));
	$table_description->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
	$html .= "<div id='file_description_table_hook' style='display:none;'>";
	$html .= print_table($table_description, true);
	$html .= "</div>";

	$table->colspan[11][0] = 4;
	$table->data[11][0] = print_container_div('file_upload_container', __('File upload'), $html, 'closed', true, true);
}

if ($create_incident) {
	$button = "<div class='button-form'>";
	$button .= print_input_hidden ('action', 'insert', true);
	if ((give_acl ($config["id_user"], 0, "IW")) || (give_acl ($config['id_user'], $id_grupo, "SI"))) {
		$button .= print_submit_button (__('Create'), 'action2', false, 'class="sub create"', true);
	}
	$button .= '</div>';
} else {
	$button = "<div class='button-form'>";
	$button .= print_input_hidden ('id', $id, true);
	$button .= print_input_hidden ('action', 'update', true);
	$button .= print_submit_button (__('Update'), 'action2', false, 'class="sub upd"', true);
	$button .= '</div>';
}

//~ $table->colspan['button'][0] = 4;
//~ $table->data['button'][0] = $button;

if ($has_permission || give_acl ($config['id_user'], $id_grupo, "SI")){
	if ($create_incident) {
		$action = 'index.php?sec=incidents&sec2=operation/incidents/incident_detail';
		echo '<form id="incident_status_form" method="post" enctype="multipart/form-data">';
		print_table ($table);
		
		//echo print_container_div('advanced_parameters_incidents_form', __('Advanced parameters'), print_table($table_advanced, true), 'closed', true, false);
		//echo "<h4>" . __('File upload')."</h4>";
		//echo $html;
		echo $button;
		echo '</form>';
	} else {
		echo '<form id="incident_status_form" method="post">';
		print_table ($table);
		//echo print_container_div('advanced_parameters_incidents_form', __('Advanced parameters'), print_table($table_advanced, true), 'closed', true, false);
		echo $button;
		echo '</form>';
	}
} else {
	print_table ($table);
}

//id_incident hidden
echo '<div id="id_incident_hidden" style="display:none;">';
	print_input_text('id_incident_hidden', $id);
echo '</div>';

//id_user hidden
echo '<div id="id_user_hidden" style="display:none;">';
	print_input_text('id_user_hidden', $config['id_user']);
echo '</div>';

//is_enterprise hidden
echo '<div id="is_enterprise_hidden" style="display:none;">';
	print_input_text('is_enterprise_hidden', $is_enterprise);
echo '</div>';

echo "<div class= 'dialog ui-dialog-content' title='".__("Inventory objects")."' id='inventory_search_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Tickets")."' id='parent_search_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Contacts")."' id='contact_search_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Users")."' id='users_search_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Warning")."' id='ticket_childs'></div>";

?>

<script type="text/javascript" src="include/js/jquery.metadata.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>
<script type="text/javascript" src="include/js/integria_inventory.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="include/js/jquery.iframe-transport.js"></script>
<script type="text/javascript" src="include/js/jquery.knob.js"></script>

<script  type="text/javascript">
//datepicker
add_datepicker("#text-creation_date");


$(document).ready (function () {

	id_group = $("#grupo_form").val();
	id_incident = $('#text-id_incident_hidden').val();
	status = $('#incident_status').val();
	
	no_change_owner = <?php echo $config['ticket_owner_is_creator']?>;
		
	if (id_incident == 0) {
		if (!no_change_owner) {
			set_ticket_owner(id_group);
		}
		set_initial_status();
	} else {
		set_allowed_status();
		if (status == 7) {
			set_allowed_resolution();
		}
	}

	// Incident type combo change event
	$("#id_incident_type").change( function() {
		var selected = $(this).val();
		
		var first_id_incident_type = <?php echo json_encode($id_incident_type) ?>;
		if (first_id_incident_type > 0) {
			$.data(this, 'current', first_id_incident_type);
			first_id_incident_type = 0;
		}
		
		if (<?php echo json_encode($id) ?> > 0 && $.data(this, 'current') > 0) {
			if (!confirm("<?php echo __('If you change the type, you will lost the information of the customized type fields. \n\nDo you want to continue?'); ?>")) {
				$(this).val($.data(this, 'current'));
				return false;
			}
		}
		
		$.data(this, 'current', $(this).val());
		show_incident_type_fields();
		add_datepicker ("input[type=date]");
	});
	
	// Link to the task
	$("#id_task").change(function() {
		if ($("#id_task").val() > 0) {
			$("#task_link").html("<a id='task_link' title='<?php echo __('Open this task') ?>' target='_blank' "
				+ "href='index.php?sec=projects&sec2=operation/projects/task_detail&operation=view&id_task="
				+ $("#id_task").val() + "'><img src='images/task.png'></a>");
		} else {
			$("#task_link").html("");
		}
	});
	
	//Verify incident limit on view display and on group change
	var id_incident = <?php echo $id?>;
	var id_user = $("#text-id_user").val();
	var id_group = $("#grupo_form").val();
	var id_group_p = <?php echo $id_grupo?>;
	
	//Only check incident on creation (where there is no id)
	if (id_incident == 0) {
		id_user_ticket = $('#text-id_user_hidden').val();
		if (id_group != null) {
			incident_limit("#submit-accion", id_user_ticket, id_group);
		}
	}

	//order groups for select
	$("#id_incident_type").change (function () {
		set_ticket_groups();
	});
	
	$("#grupo_form").change (function () {
		id_user = $("#text-id_user").val();
		id_user_ticket = $('#text-id_user_hidden').val();
		id_group = $("#grupo_form").val();
		id_incident = <?php echo $id?>;

		if (id_incident == 0) {
			if (id_group != null) {
				incident_limit("#submit-accion", id_user_ticket, id_group);
			}
		}
		
		var group = $("#grupo_form").val();
		
		var group_info = get_group_info(group);

		bindAutocomplete("#text-id_creator", idUser,false,false,true,group);
		bindAutocomplete("#text-id_user", idUser,false,false,true,group);
		bindAutocomplete("#text-closed_by", idUser,false,false,true,group);

		validate_ticket_user ("#incident_status_form", "#text-id_creator", "<?php echo __('Invalid user')?>",group);
		validate_ticket_user ("#incident_status_form", "#text-id_user", "<?php echo __('Invalid user')?>",group);
		validate_ticket_user ("#incident_status_form", "#text-closed_by", "<?php echo __('Invalid user')?>",group);
			
		if (!no_change_owner) {	
			$("#text-id_user").val(group_info.id_user_default);
			$("#plain-id_user").html(group_info.id_user_default);
			$("#hidden-id_user").val(group_info.id_user_default);
		}
		
	});
	
	/*Open parent search popup
	$("#text-search_parent").focus(function () {
		parent_search_form('', '<?php echo $id?>');
	});
	*/
	//Validate form
	$("#incident_status_form").submit(function () {
		var title = $("#text-titulo").val();
		var creator = $("#text-id_creator").val();
		var assigned_user = $("#text-id_user").val();
		var closed_by = $("#text-closed_by").val();
		
		//Restore borders color
		$("#text-titulo").css("border-color", "#9A9B9D");
		$("#text-id_creator").css("border-color", "#9A9B9D");
		$("#text-id_user").css("border-color", "#9A9B9D");
		$("#text-closed_by").css("border-color", "#9A9B9D");
		
		//Validate fields
		if (title == "") {
			$("#text-titulo").css("border-color", "red");
			$("div.result").html("<h3 class='error'><?php echo __("Title field is empty")?></h3>");
			window.scrollTo(0,0);
			return false;
		}
		
		if (creator == "") {
			$("#text-id_creator").css("border-color", "red");
			$("div.result").html("<h3 class='error'><?php echo __("Creator field is empty")?></h3>");
			window.scrollTo(0,0);
			return false;
		}
		
		if (assigned_user == "") {
			$("#text-id_user").css("border-color", "red");
			$("div.result").html("<h3 class='error'><?php echo __("Owner field is empty")?></h3>");
			window.scrollTo(0,0);
			return false;
		}
		
		var status = $("#incident_status").val();
		
		//If closed not empty closed by
		if (status == 7) {
			
			if (closed_by == "") {
				$("#text-closed_by").css("border-color", "red");
				$("div.result").html("<h3 class='error'><?php echo __("Closed by field is empty")?></h3>");
				window.scrollTo(0,0);
				return false;
			}
		}
				
		return true;
		
	});
		
	//JS to create KB artico from incident
	$("#kb_form_submit").click(function (event) {
		event.preventDefault();
		$("#kb_form").submit();
	});
	
	//JS to delete incident
	$("#detele_incident_submit_form").click(function (event) {
		event.preventDefault();
		
		//Show confirm dialog
		var res = confirm("<?php echo __("Are you sure?");?>");
		if (res) {
			$("#delete_incident_form").submit();
		}
	});
	
	//Show hide epilog field
	$("#incident_status").change(function () {
		
		var status = $(this).val();
		
		//Display epilog and closed by field
		if (status == 7) {
			$("#epilog_wrapper").show();
			$("#closed_by_wrapper").show();
			$("#div_incident_resolution").show();
			$("#text-closed_by").val("<?php echo $config['id_user'] ?>");
			$("#blocked").show();
			
			pulsate("#epilog_wrapper");
			pulsate("#closed_by_wrapper");
			pulsate("#div_incident_resolution");
			pulsate("#div_incident_block");
			
			set_allowed_resolution();
			
			id_incident = $('#text-id_incident_hidden').val();
			
			if (id_incident != 0) {
				$.ajax({
					type: "POST",
					url: "ajax.php",
					data: "page=include/ajax/incidents&check_incident_childs=1&id_incident="+id_incident,
					dataType: "text",
					success: function (data) {
						if (data != false) {
							$("#ticket_childs").html (data);
							$("#ticket_childs").show ();

							$("#ticket_childs").dialog ({
									resizable: true,
									draggable: true,
									modal: true,
									overlay: {
										opacity: 0.5,
										background: "black"
									},
									width: 420,
									height: 350
								});
							$("#ticket_childs").dialog('open');
						}
					}
				});
			}
			
		} else {
			$("#epilog_wrapper").hide();
			$("#closed_by_wrapper").hide();
			$("#div_incident_resolution").hide();
			$("#div_incident_block").hide();
		}
	});
	
	if ($("#id_incident_type").val() != "0") {
		show_incident_type_fields();
		add_datepicker ("input[type=date]");
	}
			
	var idUser = "<?php echo $config['id_user'] ?>";
	var idGroup = $("#grupo_form").val();
	
	//~ bindAutocomplete("#text-id_creator", idUser);
	//~ bindAutocomplete("#text-id_user", idUser);
	//~ bindAutocomplete("#text-closed_by", idUser);
	bindAutocomplete("#text-id_creator", idUser,false,false,true,idGroup);
	bindAutocomplete("#text-id_user", idUser,false,false,true,idGroup);
	bindAutocomplete("#text-closed_by", idUser,false,false,true,idGroup);
	
	if ($("#incident_status_form").length > 0){
		//~ validate_user ("#incident_status_form", "#text-id_creator", "<?php echo __('Invalid user')?>");
		//~ validate_user ("#incident_status_form", "#text-id_user", "<?php echo __('Invalid user')?>");
		//~ validate_user ("#incident_status_form", "#text-closed_by", "<?php echo __('Invalid user')?>");
		validate_ticket_user ("#incident_status_form", "#text-id_creator", "<?php echo __('Invalid user')?>",idGroup);
		validate_ticket_user ("#incident_status_form", "#text-id_user", "<?php echo __('Invalid user')?>",idGroup);
		validate_ticket_user ("#incident_status_form", "#text-closed_by", "<?php echo __('Invalid user')?>",idGroup);
	}
	
	$("#tgl_incident_control").click(function() {
		 fila = document.getElementById('incident-editor-row_advanced-0');
		  if (fila.style.display != "none") {
			fila.style.display = "none"; //ocultar fila 
		  } else {
			fila.style.display = ""; //mostrar fila 
		  }
	});
	
	$("#priority_form").change (function () {
		var level = this.value;
		var color;
		var name;
		
		switch (level) {
			case "10":
				color = "blue";
				name = "maintenance";
				break;
			case "0":
				color = "gray";
				name = "informative";
				break;
			case "1":
				color = "green";
				name = "low";
				break;
			case "2":
				color = "yellow";
				name = "medium";
				break;
			case "3":
				color = "orange";
				name = "serious";
				break;
			case "4":
				color = "red";
				name = "critical";
				break;
			default:
				color = "blue";
		}
		$(".priority-color").fadeOut('normal', function () {
			$(this).attr ("src", "images/priority_"+name+".png").fadeIn();
		});
	});
		
	$("#go_search").click(function (event) {
		event.preventDefault();
		$("#go_search_form").submit();
	});
	
	$("#submit-accion").click(function () {
		var id_ticket = "<?php echo $id ?>";
		var score = $("#score_ticket").val();
		setTicketScore(id_ticket, score);
	});

	var action = '<?php echo $action;?>';

	//~ if(action != 'update'){
	if (id_incident == 0) {
		set_ticket_groups();
	}

	// Init the file upload
	form_upload();

});


function loadInventory(id_inventory) {
	
	$('#incident_status_form').append ($('<input type="hidden" value="'+id_inventory+'" class="selected-inventories" name="inventories[]" />'));

	$("#inventory_search_window").dialog('close');

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_inventory_name=1&id_inventory="+ id_inventory,
		dataType: "text",
		success: function (name) {
			$('#incident_inventories').append($('<option></option>').html(name).attr("value", id_inventory));
			
		}
	});
}

function removeInventory() {

	s= $("#incident_inventories").prop ("selectedIndex");

	selected_id = $("#incident_inventories").children (":eq("+s+")").attr ("value");
	$("#incident_inventories").children (":eq("+s+")").remove ();
	$(".selected-inventories").each (function () {
		if (this.value == selected_id)
			$(this).remove ();
	});
		
	//idInventory = $('#incident_inventories').val();
	//$("#incident_inventories").find("option[value='" + idInventory + "']").remove();
	
}

function form_upload () {
	// Input will hold the JSON String with the files data
	var input_upfiles = $('input#upfiles');
	// JSON Object will hold the files data
	var upfiles = {};

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#incident_files').fileupload({
		
		url: 'ajax.php?page=operation/incidents/incident_detail&upload_file=true',
		
		// This element will accept file drag/drop uploading
		dropZone: $('#drop_file'),

		// This function is called when a file is added to the queue;
		// either via the browse button, or via drag/drop:
		add: function (e, data) {
			data.context = addListItem(0, data.files[0].name, data.files[0].size);

			// Automatically upload the file once it is added to the queue
			data.context.addClass('working');
			var jqXHR = data.submit();
		},

		progress: function(e, data) {

			// Calculate the completion percentage of the upload
			var progress = parseInt(data.loaded / data.total * 100, 10);

			// Update the hidden input field and trigger a change
			// so that the jQuery knob plugin knows to update the dial
			data.context.find('input').val(progress).change();

			if (progress >= 100) {
				data.context.removeClass('working');
				data.context.removeClass('error');
				data.context.addClass('loading');
			}
		},

		fail: function(e, data) {
			// Something has gone wrong!
			data.context.removeClass('working');
			data.context.removeClass('loading');
			data.context.addClass('error');
		},
		
		done: function (e, data) {
			
			var result = JSON.parse(data.result);
			
			if (result.status) {
				data.context.removeClass('error');
				data.context.removeClass('loading');
				data.context.addClass('working');

				// Increase the counter
				if (upfiles.length == undefined) {
					upfiles.length = 0;
				} else {
					upfiles.length += 1;
				}
				var index = upfiles.length;
				// Create the new element
				upfiles[index] = {};
				upfiles[index].name = result.name;
				upfiles[index].location = result.location;
				// Save the JSON String into the input
				input_upfiles.val(JSON.stringify(upfiles));

				// FORM
				addForm (data.context, index);
				
			} else {
				// Something has gone wrong!
				data.context.removeClass('working');
				data.context.removeClass('loading');
				data.context.addClass('error');
				if (result.message) {
					var info = data.context.find('i');
					info.css('color', 'red');
					info.html(result.message);
				}
			}
		}

	});

	// Prevent the default action when a file is dropped on the window
	$(document).on('drop_file dragover', function (e) {
		e.preventDefault();
	});

	function addListItem (progress, filename, filesize) {
		var tpl = $('<li>'+
						'<input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
						' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" />'+
						'<p></p>'+
						'<span></span>'+
						'<div class="incident_file_form"></div>'+
					'</li>');
		
		// Append the file name and file size
		tpl.find('p').text(filename);
		if (filesize > 0) {
			tpl.find('p').append('<i>' + formatFileSize(filesize) + '</i>');
		}

		// Initialize the knob plugin
		tpl.find('input').val(0);
		tpl.find('input').knob({
			'draw' : function () {
				$(this.i).val(this.cv + '%')
			}
		});

		// Listen for clicks on the cancel icon
		tpl.find('span').click(function() {

			if (tpl.hasClass('working') || tpl.hasClass('error') || tpl.hasClass('suc')) {

				if (tpl.hasClass('working') && typeof jqXHR != 'undefined') {
					jqXHR.abort();
				}

				tpl.fadeOut();
				tpl.slideUp(500, "swing", function() {
					tpl.remove();
				});

			}

		});
		
		// Add the HTML to the UL element
		var item = tpl.appendTo($('#incident_files ul'));
		item.find('input').val(progress).change();

		return item;
	}

	function addForm (item, array_index) {
		
		item.find(".incident_file_form").html($("#file_description_table_hook").html());

		item.find("#submit-crt_btn").click(function(e) {
			e.preventDefault();
			
			$(this).prop('value', "<?php echo __('Update'); ?>");
			$(this).removeClass('create');
			$(this).addClass('upd');

			// Add the description to the array
			upfiles[array_index].description = item.find("#textarea-file_description").val();	
			// Save the JSON String into the input
			input_upfiles.val(JSON.stringify(upfiles));
		});

		// Listen for clicks on the cancel icon
		item.find('span').click(function() {
			// Remove the element from the array
			upfiles[array_index] = {};
			// Save the JSON String into the input
			input_upfiles.val(JSON.stringify(upfiles));
			// Remove the tmp file
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "operation/incidents/incident_detail",
					remove_tmp_file: true,
					location: upfiles[array_index].location
				}
			});
		});

	}

}

function set_ticket_owner(id_group) {
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=operation/incidents/incident_detail&get_owner=1&id_group="+ id_group,
		dataType: "json",
		success: function (data) {
			$("#text-id_user").val(data);
		}
	});
}

function set_ticket_groups() {
	
	var id_group_p = <?php echo $id_grupo;?>;
	var id_incident_type = $("#id_incident_type").val();
	var id_grupo_incident = <?php echo $id_grupo_incident;?>;
	
	show_incident_groups_fields(id_incident_type, id_group_p, null, function (err, incident_groups) {
		var obj = incident_groups;
		$("#grupo_form option").remove();
		if(obj.length > 0){
			$.each (obj, function (id, value) {
				value_aux = value[1].replace(/&nbsp;/g, '\u00a0');
				$("#grupo_form").append(new Option(value_aux, value[0]));
			});
		} else {
			$("#grupo_form").append(new Option('', ''));
		}
		$("#grupo_form option[value=" + id_grupo_incident + "]").attr("selected", "selected");
		
		group = $("#grupo_form").val();

		if (!no_change_owner) {
			set_ticket_owner(group);
		}
	});
}

// Form validation
trim_element_on_submit('#text-titulo');
trim_element_on_submit('#text-email_copy');

validate_form("#incident_status_form");
var rules, messages;
// Rules: #text-titulo
//~ rules = {
	//~ required: true,
	//~ remote: {
		//~ url: "ajax.php",
        //~ type: "POST",
        //~ data: {
			//~ page: "include/ajax/remote_validations",
			//~ search_existing_incident: 1,
			//~ incident_name: function() { return $('#text-titulo').val() },
			//~ incident_id: "<?php echo $id_incident?>"
        //~ }
	//~ }
//~ };
//~ messages = {
	//~ required: "<?php echo __('Title required')?>",
	//~ remote: "<?php echo __('This ticket already exists')?>"
//~ };
//~ add_validate_form_element_rules('#text-titulo', rules, messages);

</script>
