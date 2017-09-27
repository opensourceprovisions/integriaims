<?php 
// INTEGRIA IMS
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2012 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2012 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.


/**
 * Filter all the incidents and return a list of matching elements.
 *
 * This function only return the incidents that can be accessed for the current
 * user with IR permission.
 *
 * @param array Key-value array of parameters to filter. It can handle this fields:
 *
 * string String to find in incident title.
 * status Status to search.
 * priority Priority to search.
 * id_group Incident group
 * id_product Incident affected product
 * id_company Incident affected company
 * id_inventory Incident affected inventory object
 * serial_number Incident affected inventory object's serial number
 * id_building Incident affected inventory object in a building
 * sla_fired Wheter the SLA was fired or not
 * id_incident_type Incident type
 * id_user Incident risponsable user
 * first_date Begin range date (range start)
 * last_date Begin range date (range end)
 *
 * @return array A list of matching incidents. False if no matches.
 */

// Avoid to mess AJAX with Javascript
if(defined ('AJAX')) {
	require_once ($config["homedir"]."/include/functions_graph.php");
}

include_once ($config["homedir"]."/include/graphs/fgraph.php");
enterprise_include("include/functions_users.php");
enterprise_include("include/functions_incidents.php");
enterprise_include($config["homedir"]."/include/functions_groups.php");

function filter_incidents ($filters, $count=false, $limit=true, $no_parents = false, $csv_mode = false) {
	global $config;

	/* Set default values if none is set */
	$filters['inverse_filter'] = isset ($filters['inverse_filter']) ? $filters['inverse_filter'] : false;
	$filters['string'] = isset ($filters['string']) ? $filters['string'] : '';
	$filters['status'] = isset ($filters['status']) ? $filters['status'] : 0;
	$filters['priority'] = isset ($filters['priority']) ? $filters['priority'] : -1;
	$filters['id_group'] = isset ($filters['id_group']) ? $filters['id_group'] : -1;
	$filters['id_company'] = isset ($filters['id_company']) ? $filters['id_company'] : 0;
	$filters['id_inventory'] = isset ($filters['id_inventory']) ? $filters['id_inventory'] : 0;
	$filters['id_incident_type'] = isset ($filters['id_incident_type']) ? $filters['id_incident_type'] : 0;
	$filters['id_user'] = isset ($filters['id_user']) ? $filters['id_user'] : '';
	$filters['id_user_or_creator'] = isset ($filters['id_user_or_creator']) ? $filters['id_user_or_creator'] : '';
	$filters['from_date'] = isset ($filters['from_date']) ? $filters['from_date'] : 0;
	$filters['first_date'] = isset ($filters['first_date']) ? $filters['first_date'] : '';
	$filters['last_date'] = isset ($filters['last_date']) ? $filters['last_date'] : '';	
	$filters['id_creator'] = isset ($filters['id_creator']) ? $filters['id_creator'] : '';
	$filters['editor'] = isset ($filters['editor']) ? $filters['editor'] : '';
	$filters['closed_by'] = isset ($filters['closed_by']) ? $filters['closed_by'] : '';
	$filters['resolution'] = isset ($filters['resolution']) ? $filters['resolution'] : '';
	$filters["offset"] = isset ($filters['offset']) ? $filters['offset'] : 0;
	$filters["group_by_project"] = isset ($filters['group_by_project']) ? $filters['group_by_project'] : 0;
	$filters["sla_state"] = isset ($filters['sla_state']) ? $filters['sla_state'] : 0;
	$filters["id_task"] = isset ($filters['id_task']) ? $filters['id_task'] : 0;
	$filters["left_sla"] = isset ($filters['left_sla']) ? $filters['left_sla'] : 0;
	$filters["right_sla"] = isset ($filters['right_sla']) ? $filters['right_sla'] : 0;
	$filters["show_hierarchy"] = isset ($filters['show_hierarchy']) ? $filters['show_hierarchy'] : 0;
	$filters["medals"] = isset ($filters['medals']) ? $filters['medals'] : 0;
	$filters["parent_name"] = isset ($filters['parent_name']) ? $filters['parent_name'] : '';
	
	///// IMPORTANT: Write an inverse filter for every new filter /////
	$is_inverse = $filters['inverse_filter'];
	
	$sql_clause = '';
	
	// Status
	if (!empty($filters['status'])) {
		// Not closed
		if ($filters['status'] == -10) {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND estado <> %d', STATUS_CLOSED);
			}
			else {
				$sql_clause .= sprintf(' AND estado = %d', STATUS_CLOSED);
			}
		}
		else {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND estado = %d', $filters['status']);
			}
			else {
				$sql_clause .= sprintf(' AND estado <> %d', $filters['status']);
			}
		}
	}

	// Priority
	if ($filters['priority'] != -1) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND prioridad = %d', $filters['priority']);
		}
		else {
			$sql_clause .= sprintf(' AND prioridad <> %d', $filters['priority']);
		}
	}
	
	// Group
	if ($filters['id_group'] != 1) {
		if ($filters['show_hierarchy']) {
			$children = groups_get_childrens($filters['id_group']);
			$ids = $filters['id_group'];
			foreach ($children as $child) {
				$ids .= ",".$child['id_grupo'];
			}	
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_grupo IN (%s)', $ids);
			}
			else {
				$sql_clause .= sprintf(' AND id_grupo NOT IN (%s)', $ids);
			}
		} else {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_grupo = %d', $filters['id_group']);
			}
			else {
				$sql_clause .= sprintf(' AND id_grupo <> %d', $filters['id_group']);
			}
		}

	}
	
	// User
	if (!empty($filters['id_user'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_usuario = "%s"', $filters['id_user']);
		}
		else {
			$sql_clause .= sprintf(' AND id_usuario <> "%s"', $filters['id_user']);
		}
	}
	
	// User or creator
	if (!empty($filters['id_user_or_creator'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND (id_usuario = "%s" OR id_creator = "%s")', $filters['id_user_or_creator'], $filters['id_user_or_creator']);
		}
		else {
			$sql_clause .= sprintf(' AND (id_usuario <> "%s" AND id_creator <> "%s")', $filters['id_user_or_creator'], $filters['id_user_or_creator']);
		}
	}
	
	// Resolution
	if (!empty($filters['resolution']) && $filters['resolution'] > -1) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND resolution = %d', $filters['resolution']);
		}
		else {
			$sql_clause .= sprintf(' AND resolution <> %d', $filters['resolution']);
		}
	}
	
	// Task
	if ($filters['id_task'] != 0) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_task = %d', $filters['id_task']);
		}
		else {
			$sql_clause .= sprintf(' AND id_task <> %d', $filters['id_task']);
		}
	}
	
	// Incidents
	if (!empty($filters['id_incident_type']) && $filters['id_incident_type'] != -1) {

		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_incident_type = %d', $filters['id_incident_type']);
		}
		else {
			$sql_clause .= sprintf(' AND id_incident_type <> %d', $filters['id_incident_type']);
		}

		// Incident fields
		$incident_fields = array();
		foreach ($filters as $key => $value) {
			// If matchs an incident field, ad an element to the array with their real id and its data
			if (preg_match('/^type_field_/', $key)) {
				$incident_fields[preg_replace('/^type_field_/', '', $key)] = $value;
			}
		}
		foreach ($incident_fields as $id => $data) {
			if (!empty($data)) {
				if (!$is_inverse) {
					$sql_clause .= sprintf(' AND id_incidencia IN (SELECT id_incident
																	FROM tincident_field_data
																	WHERE id_incident_field = "%s"
																		AND data = "%s")', $id, $data);
				}
				else {
					$sql_clause .= sprintf(' AND id_incidencia NOT IN (SELECT id_incident
																	FROM tincident_field_data
																	WHERE id_incident_field = "%s"
																		AND data = "%s")', $id, $data);
				}
			}
		}
	}
	
	// Date
	if (!empty($filters['from_date']) && $filters['from_date'] > 0) {
		$last_date_seconds = $filters['from_date'] * 24 * 60 * 60;
		$filters['first_date'] = date('Y-m-d H:i:s', time() - $last_date_seconds);
		
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND inicio >= "%s"', $filters['first_date']);
		}
		else {
			$sql_clause .= sprintf(' AND inicio < "%s"', $filters['first_date']);
		}
	}
	else {
		if (!empty($filters['first_date']) && !empty($filters['last_date'])) {
			// 00:00:00 to set date at the beginig of the day
			$start_time = strtotime($filters['first_date']);
			$start_date = date('Y-m-d 00:00:00', $start_time);
			// 23:59:59 to set date at the end of day
			$end_time = strtotime($filters['last_date']);
			$end_date = date('Y-m-d 23:59:59', $end_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio >= "%s"', $start_date);
				$sql_clause .= sprintf(' AND inicio <= "%s"', $end_date);
			}
			else {
				$sql_clause .= sprintf(' AND (inicio < "%s" OR inicio > "%s")',
					$start_date, $end_date);
			}
		}
		else if (!empty($filters['first_date'])) {
			// 00:00:00 to set date at the beginig of the day
			$start_time = strtotime($filters['first_date']);
			$start_date = date('Y-m-d 00:00:00', $start_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio >= "%s"', $start_date);
			}
			else {
				$sql_clause .= sprintf(' AND inicio < "%s"', $start_date);
			}
		}
		else if (!empty($filters['last_date'])) {
			// 23:59:59 to set date at the end of day
			$end_time = strtotime($filters['last_date']);
			$end_date = date('Y-m-d 23:59:59', $end_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio <= "%s"', $end_date);
			}
			else {
				$sql_clause .= sprintf(' AND inicio > "%s"', $end_date);
			}
		}
	}
	
	// Creator
	if (!empty($filters['id_creator'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_creator = "%s"', $filters['id_creator']);
		}
		else {
			$sql_clause .= sprintf(' AND id_creator <> "%s"', $filters['id_creator']);
		}
	}
	
	// Editor
	if (!empty($filters['editor'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND editor = "%s"', $filters['editor']);
		}
		else {
			$sql_clause .= sprintf(' AND editor <> "%s"', $filters['editor']);
		}
	}
	
	// Closed by
	if (!empty($filters['closed_by'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND closed_by = "%s"', $filters['closed_by']);
		}
		else {
			$sql_clause .= sprintf(' AND closed_by <> "%s"', $filters['closed_by']);
		}
	}
	
	// SLA
	$sla_filter = '';
	if (!empty($filters['sla_state'])) {
		$sla_fired_filter = 'AND (sla_disabled = 0 AND affected_sla_id <> 0)';
		$sla_not_fired_filter = 'AND (sla_disabled = 0 AND affected_sla_id = 0)';
		
		if ($filters['sla_state'] == 1) {
			$sla_filter = (!$is_inverse) ? $sla_fired_filter : $sla_not_fired_filter;
		}
		else if ($filters['sla_state'] == 2) {
			$sla_filter = (!$is_inverse) ? $sla_not_fired_filter : $sla_fired_filter;
		}
	}
	
	// Medals
	$medals_filter = '';
	if ($filters['medals']) {
		if ($filters['medals'] == 1) {
			if (!$is_inverse) {
				$medals_filter = 'AND gold_medals <> 0';
			}
			else {
				$medals_filter = 'AND gold_medals = 0';
			}
		} else if ($filters['medals'] == 2) {
			if (!$is_inverse) {
				$medals_filter = 'AND black_medals <> 0';
			}
			else {
				$medals_filter = 'AND black_medals = 0';
			}
		}
	}
	
	if (!empty($filters['parent_name'])) {
		$inventory_id = get_db_value('id', 'tinventory', 'name', $filters['parent_name']);
		
		if ($inventory_id) {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_incidencia IN (SELECT id_incident FROM tincident_inventory WHERE
					id_inventory = %d)', $inventory_id);
			}
			else {
				$sql_clause .= sprintf(' AND id_incidencia NOT IN (SELECT id_incident FROM tincident_inventory WHERE
					id_inventory = %d)', $inventory_id);
			}
		}
	}

	if (!isset($inventory_id)){
		$inventory_id = 0;
	}
	
	if ($no_parents) {
		$sql_clause .= ' AND id_incidencia NOT IN (SELECT id_incidencia FROM tincidencia WHERE id_parent <> 0)';
	}
	
	// Order
	if(!isset($filters['order_by'])){
		$filters['order_by'] = '';
	}
	if ($filters['order_by']) {
		$order_by_array = json_decode(clean_output($filters['order_by']), true);
	} else {
		$order_by_array = $filters['order_by'];
	}
	
	$order_by = '';
	if ($order_by_array) {
		foreach ($order_by_array as $key => $value) {
			if ($value) {
				$order_by .= " $key $value, ";
			}
		}
	}
	
	// Use config block size if no other was given
	if ($limit && !isset($filters['limit'])) {
		$filters['limit'] = $config['block_size'];
	}
	
	// Text filter
	$text_filter = '';
	if (!empty($filters['string'])) {
		if (!$is_inverse) {
			$text_filter = sprintf('AND (
				titulo LIKE "%%%s%%" OR descripcion LIKE "%%%s%%"
				OR id_creator LIKE "%%%s%%" OR id_usuario LIKE "%%%s%%"
				OR id_incidencia = %d
				OR id_incidencia IN (
					SELECT id_incident
					FROM tincident_field_data
					WHERE data LIKE "%%%s%%"))',
				$filters['string'], $filters['string'], $filters['string'],
				$filters['string'], $filters['string'], $filters['string']);
		}
		else {
			$text_filter = sprintf('AND (
				titulo NOT LIKE "%%%s%%" AND descripcion NOT LIKE "%%%s%%"
				AND id_creator NOT LIKE "%%%s%%" AND id_usuario NOT LIKE "%%%s%%"
				AND id_incidencia <> %d
				AND id_incidencia NOT IN (
					SELECT id_incident
					FROM tincident_field_data
					WHERE data LIKE "%%%s%%"))',
				$filters['string'], $filters['string'], $filters['string'],
				$filters['string'], $filters['string'], $filters['string']);
		}
	}
	
	//Select all items and return all information
	$sql = sprintf('SELECT * FROM tincidencia FD WHERE 1=1 %s %s %s %s ORDER BY %s actualizacion DESC',
		$sql_clause, $text_filter, $sla_filter, $medals_filter, $order_by);

	if (!$count && isset($filters['limit']) && $filters['limit'] > 0) {
		$sql_limit = sprintf(' LIMIT %d OFFSET %d', $filters['limit'], $filters['offset']);
		$sql .= $sql_limit;
	}
	
	$incidents = get_db_all_rows_sql($sql);
	
	if ($incidents === false) {
		if ($count) return 0;
		else return false;
	}

	$result = array();
	foreach ($incidents as $incident) {
		//Check external users ACLs
		$standalone_check = enterprise_hook('manage_standalone', array($incident, 'read'));

		//~ if ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check) {
			//~ continue;
		//~ }
		//~ else {
			//~ // Normal ACL pass if IR for this group or if the user is the incident creator
			//~ // or if the user is the owner or if the user has workunits
			//~ $check_acl = enterprise_hook('incidents_check_incident_acl', array($incident));
			//~ if (!$check_acl) {
				//~ continue;
			//~ }
		//~ }
		
		if ($standalone_check !== ENTERPRISE_NOT_HOOK) { // Enterprise
			if (($standalone_check == 0) || ($standalone_check == 1)) { // standalone user
				if ((!$standalone_check)) {
					continue;
				}	
			} else { // grouped user
				$check_acl = enterprise_hook('incidents_check_incident_acl', array($incident));
				if (!$check_acl) {
					continue;
				}
			}
		}
		
		$inventories = get_inventories_in_incident($incident['id_incidencia'], false);
		
		// Inventory
		if ($filters['id_inventory']) {
			$found = false;
			foreach ($inventories as $inventory) {
				if ($inventory['id'] == $filters['id_inventory']) {
					$found = true;
					break;
				}
			}
			
			if (!$is_inverse && !$found) continue;
			else if ($is_inverse && $found) continue;
		}
		
		// Company
		if ($filters['id_company']) {
			$found = false;
			$user_creator = $incident['id_creator'];
			$user_company = get_db_value('id_company', 'tusuario', 'id_usuario', $user_creator);
			
			// Don't match, dismiss incident
			if (!$is_inverse && $filters['id_company'] != $user_company) continue;
			// Match, dismiss incident
			if ($is_inverse && $filters['id_company'] == $user_company) continue;
		}
		
		// SLA
		if ($filters['left_sla']) {
			$percent_sla_incident = format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
			
			// Don't match, dismiss incident
			if (!$is_inverse && $filters['left_sla'] > $percent_sla_incident) continue;
			// Match, dismiss incident
			if ($is_inverse && $filters['left_sla'] <= $percent_sla_incident) continue;
		}
		
		if ($filters['right_sla']) {
			$percent_sla_incident = format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
			
			// Don't match, dismiss incident
			if (!$is_inverse && $filters['right_sla'] < $percent_sla_incident) continue;
			// Match, dismiss incident
			if ($is_inverse && $filters['right_sla'] >= $percent_sla_incident) continue;
		}
		
		if ($csv_mode) {
			if ($incident['id_incident_type']) {
				$incident['id_incident_type_name'] = incidents_get_incident_type_text($incident['id_incidencia']);
				$fields = get_db_all_rows_sql("SELECT id, label FROM tincident_type_field WHERE id_incident_type=".$incident['id_incident_type']);
				if ($fields !== false) {
					foreach ($fields as $field) {
						$data = get_db_value_sql("SELECT data FROM tincident_field_data WHERE id_incident_field=".$field['id']." AND id_incident=".$incident['id_incidencia']);
						$incident[safe_output($field['label'])] = $data; 
					}
				}
			}
		}
		
		array_push($result, $incident);
	}
	
	if ($count) return count($result);
	else return $result;
}


/**
 * Copy and insert in database a new file into incident
 *
 * @param int incident id
 * @param string file full path
 * @param string file description
 *
 */
 
function attach_incident_file ($id, $file_temp, $file_description, $file_name = "", $send_email = true) {
	global $config;
	
	$file_temp = safe_output ($file_temp); // Decoding HTML entities
	$filesize = filesize($file_temp); // In bytes
	if ($file_name != "") {
		$filename = $file_name;
	} else {
		$filename = basename($file_temp);
	}
	$filename = str_replace (array(" ", "(", ")"), "_", $filename); // Replace blank spaces
	$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
	
	$sql = sprintf ('INSERT INTO tattachment (id_incidencia, id_usuario,
			filename, description, size)
			VALUES (%d, "%s", "%s", "%s", %d)',
			$id, $config['id_user'], $filename, $file_description, $filesize);
	
	$id_attachment = process_sql ($sql, 'insert_id');
	
	incident_tracking ($id, INCIDENT_FILE_ADDED);
	
	$result_msg = ui_print_success_message(__('File added'), '', true);
	
	// Email notify to all people involved in this incident
	
	// Email in list email-copy
	if ($send_email) {
		$email_copy_sql = 'select email_copy from tincidencia where id_incidencia ='.$id.';';
		$email_copy = get_db_sql($email_copy_sql);
		if ($email_copy != "") { 
			mail_incident ($id, $config['id_user'], 0, 0, 2, 7);
		}
		if (($config["email_on_incident_update"] != 2) && ($config["email_on_incident_update"] != 4)){
			mail_incident ($id, $config['id_user'], 0, 0, 2);
		}
	}	
	// Copy file to directory and change name
	$file_target = $config["homedir"]."attachment/".$id_attachment."_".$filename;
	
	$copy = copy ($file_temp, $file_target);
	
	if (! $copy) {
		$result_msg = ui_print_error_message(__('File cannot be saved. Please contact Integria administrator about this error'), '', true);
		$sql = sprintf ('DELETE FROM tattachment
				WHERE id_attachment = %d', $id_attachment);
		process_sql ($sql);
	} else {
		// Delete temporal file
		unlink ($file_temp);

		// Adding a WU noticing about this
		$link = "<a target='_blank' href='operation/common/download_file.php?type=incident&id_attachment=".$id_attachment."'>".$filename."</a>";
		$note = "Automatic WU: Added a file to this issue. Filename uploaded: ". $link;
		$public = 1;
		$timeused = 0;
		create_workunit ($id, $note, $config["id_user"], $timeused, 0, "", $public, 0);
		
		$timestamp = print_mysql_timestamp();
		$sql = sprintf ('UPDATE tincidencia SET actualizacion = "%s" WHERE id_incidencia = %d', $timestamp, $id);
		process_sql ($sql);
	}
	
	return $result_msg;
}

/**
 * Update the updatetime of a incident with the current timestamp
 *
 * @param int incident id
 *
 */
 
 function update_incident_updatetime($incident_id) {
		$sql = sprintf ('UPDATE tincidencia SET actualizacion = "%s" WHERE id_incidencia = %d', print_mysql_timestamp(), $incident_id);

		process_sql ($sql);
 }

/**
 * Return an array with the incidents with a filter
 *
 * @param array List of incidents to get stats.
 * @param array/string filter for the query
 * @param bool only names or all the incidents
 *
 */
 
function get_incidents ($filter = array(), $only_names = false) {
		
	$all_incidents = get_db_all_rows_filter('tincidencia', $filter, '*');

	if ($all_incidents == false)
		return array ();
	
	global $config;
	$incidents = array ();
	
	foreach ($all_incidents as $incident) {
		//Check external users ACLs
		$standalone_check = enterprise_hook("manage_standalone", array($incident));

		if ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check) {
			continue;
		} else {
		
			//Normal ACL pass if IR for this group or if the user is the incident creator
			//or if the user is the owner or if the user has workunits
			
			$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident));
			
			if (!$check_acl)
				continue;		
				
		}
			
		if ($only_names) {
			$incidents[$incident['id_incidencia']] = $incident['titulo'];
		} else {
			array_push ($incidents, $incident);
		}		
	}
	return $incidents;
}

function has_workunits($id_user, $id_incident) {
	
	$sql = sprintf("SELECT COUNT(W.id) AS wu FROM tworkunit W, tworkunit_incident WI WHERE 
					WI.id_workunit = W.id AND WI.id_incident = %d AND W.id_user = '%s'", 
					$id_incident, $id_user);
	
	$res = get_db_row_sql ($sql);
	
	return $res["wu"];
	
}

/**
 * Return an array with the incident details, files and workunits
 *
 * @param array List of incidents to get stats.
 *
 */
 
function get_full_incident ($id_incident, $only_names = false) {
	$full_incident['details'] = get_db_row_filter('tincidencia',array('id_incidencia' => $id_incident),'*');
	$full_incident['files'] = get_incident_files ($id_incident, true);
	if($full_incident['files'] === false) {
		$full_incident['files'] = array();
	}
	$full_incident['workunits'] = get_incident_full_workunits ($id_incident);
	if($full_incident['workunits'] === false) {
		$full_incident['workunits'] = array();
	}
	
	return $full_incident;
}

/**
 * Return an array with the workunits (data included) of an incident
 *
 * @param array List of incidents to get stats.
 *
 */

function get_incident_full_workunits ($id_incident) {
	$workunits = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_incident WHERE
		tworkunit.id = tworkunit_incident.id_workunit AND tworkunit_incident.id_incident = $id_incident
		ORDER BY id_workunit DESC");
	if ($workunits === false)
		return array ();
	return $workunits;
}

/**
 * Return an array with statistics of a given list of incidents.
 *
 * @param array List of incidents to get stats.
 * */
function get_incidents_stats ($incidents) {
    global $config;

	$total = sizeof ($incidents);
	$opened = 0;
	$total_hours = 0;
	$total_lifetime = 0;
	$max_lifetime = 0;
	$oldest_incident = false;
    $scoring_sum = 0;
    $scoring_valid = 0;

	if ($incidents === false)
		$incidents = array ();
	foreach ($incidents as $incident) {
		if ($incident['actualizacion'] != '0000-00-00 00:00:00') {
			$lifetime = get_db_value ('UNIX_TIMESTAMP(actualizacion) - UNIX_TIMESTAMP(inicio)',
				'tincidencia', 'id_incidencia', $incident['id_incidencia']);
			if ($lifetime > $max_lifetime) {
				$oldest_incident = $incident;
				$max_lifetime = $lifetime;
			}
			$total_lifetime += $lifetime;
		}

        // Scoring avg.
        if ($incident["score"] > 0){
            $scoring_valid++;
            $scoring_sum = $scoring_sum + $incident["score"];
        }          
		$hours = get_incident_workunit_hours($incident['id_incidencia']);
		$total_hours += $hours;
		
		if ($incident['estado'] != 7) {
			$opened++;
		}

	}

	$closed = $total - $opened;
	$opened_pct = 0;
	$mean_work = 0;
	$mean_lifetime = 0;
	if ($total != 0) {
		$opened_pct = format_numeric ($opened / $total * 100);
		$mean_work = format_numeric ($total_hours / $total, 2);
	}
	
	if ($closed != 0) {
		$mean_lifetime = (int) ($total_lifetime / $closed) / 60;
	}
	
    // Get avg. scoring
    if ($scoring_valid > 0){
        $scoring_avg = $scoring_sum / $scoring_valid;
    } else 
        $scoring_avg = "N/A";

	// Get incident SLA compliance
	$sla_compliance = get_sla_compliance ($incidents);

    $data = array();

    $data ["total_incidents"] = $total;
    $data ["opened"] = $opened;
    $data ["closed"] = $total - $opened;
    $data ["avg_life"] = $mean_lifetime;
    $data ["avg_worktime"] = $mean_work;
    $data ["sla_compliance"] = $sla_compliance;
    $data ["avg_scoring"] = $scoring_avg;

    return $data;
}

/**
 * Print a table with statistics of a list of incidents.
 *
 * @param array List of incidents to get stats.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return Incidents stats if return parameter is true. Nothing otherwise
 */
function print_incidents_stats ($incidents, $return = false) {

    global $config;
    
	require_once ($config["homedir"]."/include/functions_graph.php");    
    
	$pdf_output = (int)get_parameter('pdf_output', 0);
	$ttl = $pdf_output+1;

	// Max graph legend string length (without the '...')
	$max_legend_strlen = 17;
	
	// Necessary for flash graphs
	include_flash_chart_script();

	// TODO: Move this function to function_graphs to encapsulate flash
	// chart script inclusion or make calls to functions_graph when want 
	// print a flash chart	

	$output = '';
	
	$total = sizeof ($incidents);
	$opened = 0;
	$total_hours = 0;
	$total_workunits = 0;
	$total_lifetime = 0;
	$max_lifetime = 0;
	$oldest_incident = false;
	$scoring_sum = 0;
	$scoring_valid = 0;

	if ($incidents === false)
		$incidents = array ();
		
		
	$assigned_users = array();
	$creator_users = array();
	
	$submitter_label = "";
	$user_assigned_label = "";
	
	$incident_id_array = array();
	
	//Initialize incident status array
	$incident_status = array();
	$incident_status[STATUS_NEW] = 0;
	$incident_status[STATUS_UNCONFIRMED] = 0;
	$incident_status[STATUS_ASSIGNED] = 0;
	$incident_status[STATUS_REOPENED] = 0;
	$incident_status[STATUS_VERIFIED] = 0;
	$incident_status[STATUS_RESOLVED] = 0;
	$incident_status[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status[STATUS_CLOSED] = 0;
	
	//Initialize priority array
	$incident_priority = array();
	$incident_priority[PRIORITY_INFORMATIVE] = 0;
	$incident_priority[PRIORITY_LOW] = 0;
	$incident_priority[PRIORITY_MEDIUM] = 0;
	$incident_priority[PRIORITY_SERIOUS] = 0;
	$incident_priority[PRIORITY_VERY_SERIOUS] = 0;
	$incident_priority[PRIORITY_MAINTENANCE] = 0;
	
	//Initialize status timing array
	$incident_status_timing = array();
	$incident_status_timing[STATUS_NEW] = 0;
	$incident_status_timing[STATUS_UNCONFIRMED] = 0;
	$incident_status_timing[STATUS_ASSIGNED] = 0;
	$incident_status_timing[STATUS_REOPENED] = 0;
	$incident_status_timing[STATUS_VERIFIED] = 0;
	$incident_status_timing[STATUS_RESOLVED] = 0;
	$incident_status_timing[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status_timing[STATUS_CLOSED] = 0;
	
	//Initialize users time array
	$users_time = array();
	
	//Initialize groups time array
	$groups_time = array();

	foreach ($incidents as $incident) {
		
		$inc_stats = incidents_get_incident_stats($incident["id_incidencia"]);
		
		if ($incident['actualizacion'] != '0000-00-00 00:00:00') {
			$lifetime = $inc_stats[INCIDENT_METRIC_TOTAL_TIME];
			if ($lifetime > $max_lifetime) {
				$oldest_incident = $incident;
				$max_lifetime = $lifetime;
			}
			$total_lifetime += $lifetime;
		}
		
		//Complete incident status timing array
		foreach ($inc_stats[INCIDENT_METRIC_STATUS] as $key => $value) {
			$incident_status_timing[$key] += $value;
		}
		
		//fill users time array
		foreach ($inc_stats[INCIDENT_METRIC_USER] as $user => $time) {
			if (!isset($users_time[$user])) {
				$users_time[$user] = $time;
			} else {
				$users_time[$user] += $time;
			}
		}
		
		//Inidents by group time
		foreach ($inc_stats[INCIDENT_METRIC_GROUP] as $key => $time) {
			if (!isset($groups_time[$key])) {
				$groups_time[$key] = $time;
			} else {
				$groups_time[$key] += $time;
			}
		}
		
		//Get only id from incident filter array
		//used for filter in some functions
		array_push($incident_id_array, $incident['id_incidencia']);		

		// Take count of assigned / creator users 

		if (isset ($assigned_users[$incident["id_usuario"]]))
			$assigned_users[$incident["id_usuario"]]++;
		else
			$assigned_users[$incident["id_usuario"]] = 1;
			
		if (isset ($creator_users[$incident["id_creator"]]))
			$creator_users[$incident["id_creator"]]++;
		else
			$creator_users[$incident["id_creator"]] = 1;
			
			
    	// Scoring avg.
    	
        if ($incident["score"] > 0){
            $scoring_valid++;
            $scoring_sum = $scoring_sum + $incident["score"];
        }
            
		$hours = get_incident_workunit_hours ($incident['id_incidencia']);

	    $workunits = get_incident_workunits ($incident['id_incidencia']);
	  
		$total_hours += $hours;

		$total_workunits = $total_workunits + sizeof ($workunits);
		
		
		//Open incidents
		if ($incident["estado"] != 7) {
			$opened++;
		}
		
		//Incidents by status
		$incident_status[$incident["estado"]]++;
		
		//Incidents by priority
		$incident_priority[$incident["prioridad"]]++;
		
	}

	$closed = $total - $opened;
	$opened_pct = 0;
	$mean_work = 0;
	$mean_lifetime = 0;

	if ($total != 0) {
		$opened_pct = format_numeric ($opened / $total * 100);
		$mean_work = format_numeric ($total_hours / $total, 2);
	}
	
	$mean_lifetime = $total_lifetime / $total;
	
    // Get avg. scoring
    if ($scoring_valid > 0){
        $scoring_avg = format_numeric($scoring_sum / $scoring_valid);
    } else {
        $scoring_avg = "N/A";
    }

	// Get incident SLA compliance
	$sla_compliance = get_sla_compliance ($incidents);
		
	//Create second table
    	
	// Find the 5 most active users (more hours worked)
	$most_active_users = array();

	if ($incident_id_array) {
		$most_active_users = get_most_active_users (8, $incident_id_array);
	}

	$users_label = '';
	$users_data = array();
	foreach ($most_active_users as $user) {
		$users_data[$user['id_user']] = $user['worked_hours'];
	}
	
	// Remove the items with no value
	foreach ($users_data as $key => $value) {
		if (!$value || $value <= 0) {
			unset($users_data[$key]);
		}
	}

	if(empty($most_active_users) || empty($users_data)) {
		$users_label = "<div class='container_adaptor_na_graphic2'>";
		$users_label .= graphic_error(false);
		$users_label .= __("N/A");
		$users_label .="</div>";
	}
	else {
		arsort($users_data);
		$users_label = "<br>";
		$users_label .= pie3d_graph ($config['flash_charts'], $users_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}
	
	// Find the 5 most active incidents (more worked hours)
	$most_active_incidents = get_most_active_incidents (5, $incident_id_array);
	$incidents_label = '';
	foreach ($most_active_incidents as $incident) {
		$inc_title = safe_output($incident['titulo']);
		if (strlen($inc_title) > $max_legend_strlen)
			$inc_title = substr($inc_title, 0, $max_legend_strlen) . "...";

		$incidents_data[$inc_title] = $incident['worked_hours'];
	}

	// Remove the items with no value
	foreach ($incidents_data as $key => $value) {
		if (!$value || $value <= 0) {
			unset($incidents_data[$key]);
		}
	}
	
	if(empty($most_active_incidents) || empty($incidents_data)) {
		$incidents_label .= graphic_error(false);
		$incidents_label .= __("N/A");
		$incidents_label = "<div class='container_adaptor_na_graphic'>".$incidents_label."</div>";
	}
	else {
		arsort($incidents_data);
		$incidents_label .= pie3d_graph ($config['flash_charts'], $incidents_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
		$incidents_label = "<div class='container_adaptor_graphic'>".$incidents_label."</div>";
	}

	// TOP X creator users
	
	$creator_assigned_data = array();
	
	foreach ($creator_users as $clave => $valor) {
		$creator_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($creator_assigned_data)) {
		$submitter_label = "<div style='width:300px; height:150px;'>";
		$submitter_label .= graphic_error(false);
		$submitter_label .= __("N/A");
		$submitter_label .="</div>";
	}
	else {
		arsort($creator_assigned_data);
		$submitter_label .= "<br/>".pie3d_graph ($config['flash_charts'], $creator_assigned_data , 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}

	// TOP X scoring users
	
	$scoring_label ="";
	$top5_scoring = get_best_incident_scoring (5, $incident_id_array);
	
	foreach ($top5_scoring as $submitter){
		$scoring_data[$submitter["id_usuario"]] = $submitter["total"];
	}
	
	if(empty($top5_scoring)) {
		$scoring_label .= graphic_error(false);
		$scoring_label .= __("N/A");
		$scoring_label = "<div class='container_adaptor_na_graphic2'>".$scoring_label."</div>";
	}
	else {
		arsort($scoring_data);
		$scoring_label .= "<br/>".pie3d_graph ($config['flash_charts'], $scoring_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}
	
	// TOP X assigned users
	
	$user_assigned_data = array();
	
	foreach ($assigned_users as $clave => $valor) {
		$user_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($user_assigned_data)) {
		$user_assigned_label = "<div style='width:300px; height:150px;'>";
		$user_assigned_label .= graphic_error(false);
		$user_assigned_label .= __("N/A");
		$user_assigned_label .="</div>";
	}
	else {
		arsort($user_assigned_data);
		$user_assigned_label .= "<br/>".pie3d_graph ($config['flash_charts'], $user_assigned_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);

	}
	
	// Show graph with incidents by group
	foreach ($incidents as $incident) {
		$grupo = safe_output(dame_grupo($incident["id_grupo"]));
		if (strlen($grupo) > $max_legend_strlen)
			$grupo = substr($grupo, 0, $max_legend_strlen) . "...";

		if (!isset( $incident_group_data[$grupo]))
			$incident_group_data[$grupo] = 0;

		$incident_group_data[$grupo] = $incident_group_data[$grupo] + 1;
	}
	arsort($incident_group_data);
	
	// Show graph with incidents by source group
	foreach ($incidents as $incident) {
		$grupo_src = safe_output(dame_grupo($incident["id_group_creator"]));
		if (strlen($grupo_src) > $max_legend_strlen)
			$grupo_src = substr($grupo_src, 0, $max_legend_strlen) . "...";
		
		if (!isset( $incident_group_data2[$grupo_src]))
			$incident_group_data2[$grupo_src] = 0;
		
		$incident_group_data2[$grupo_src] = $incident_group_data2[$grupo_src] + 1;
		
	}
	arsort($incident_group_data2);

	// Show graph with tickets open/close histogram
	$ticket_oc_graph = '<div class="pie_frame">' . graph_ticket_oc_histogram($incidents, 650, 250, $ttl) . "</div>";
	$container_title = __("Ticket Open/Close histogram");
	$container_ticket_oc = print_container('container_ticket_oc', $container_title, $ticket_oc_graph, 'open', true, true, "container_simple_title", "container_simple_div");

	// Show graph with tickets open/close histogram
	$ticket_activity_graph = '<div class="pie_frame">' . graph_ticket_activity_calendar($incidents) . "</div>";
	$container_title = __("Ticket activity");
	$container_ticket_activity = print_container('container_ticket_activity', $container_title, $ticket_activity_graph, 'open', true, true, "container_simple_title", "container_simple_div");

	//Print first table
	$output .= "<table class='listing' width=190px border=0 cellspacing=0 cellpadding=0 border=0 >";
	$output .= "<tr>";
	$output .= "<th>".__("Metric")."</th>";
	$output .= "<th>".__("Value")."</th>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td align=center><strong>".__('Total tickets')."</strong></td>";
	$output .= "<td valign=top align=center>";
	$output .= $total;
	$output .= "</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td align=center><strong>".__('Avg. life time')."</strong></td>";
	$output .= "<td valign=top align=center>";
	$output .= format_numeric ($mean_lifetime / 86400 , 2). " ". __("Days");
	$output .= "</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td align=center><strong>";
	$output .= __('Avg. work time');
	$output .= "</strong></td>";
	$output .= "<td align=center>".$mean_work.' '.__('Hours')."</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td align=center><strong>";
	$output .= __('Avg. Scoring');
	$output .= "</strong></td>";
	$output .= "<td align=center>".$scoring_avg."</td>";	
	$output .= "<tr>";
	$output .= "<td align=center><strong>";
	$output .= __('Total work time');
	$output .= "</strong></td>";
	$output .= "<td align=center>".$total_hours . " " . __("Hours")."</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td align=center><strong>";
	$output .= __('Total work units');
	$output .= "</strong></td>";
	$output .= "<td align=center>".$total_workunits."</td>";
	$output .= "</tr></table>";

	$container_title = __("Tickets statistics");
	$container_incident_statistics = print_container('container_incident_statistics', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");

	$output = "<div class='pie_frame'>".$incidents_label."</div>";
	$container_title = __("Top 5 active tickets");
	$container_top5_incidents = print_container('container_top5_incidents', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");

	if ($incidents) { 
		$output = graph_incident_statistics_sla_compliance($incidents, 300, 150, $ttl);    
	} else {
		$output = "<div style='width:300px; height:150px;'>";
		$output .= graphic_error(false);
		$output .= __("N/A");
		$output .="</div>";
	}
	$output = "<div class='container_adaptor_graphic'>".$output."</div>";
	$output = "<div class='pie_frame'>".$output."</div>";

	$container_title = __("SLA compliance");
	$container_sla_compliance = print_container('container_sla_compliance', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $status_aux .= "<table class='listing' style='width: 420px; margin: 10px auto;' cellspacing=0 cellpadding=0 border=0>";
	$status_aux .= "<tr>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Status")."</strong></th>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Number")."</strong></th>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Total time")."</strong></th>";
	$status_aux .= "</tr>";
	
		foreach ($incident_status as $key => $value) {
			$name = get_db_value ('name', 'tincident_status', 'id', $key);
			$status_aux .= "<tr>";
			$status_aux .= "<td>".$name."</td>";
			$status_aux .= "<td style='text-align:center;'>".$value."</td>";
			$time = $incident_status_timing[$key];
			$status_aux .= "<td style='text-align:center;'>".give_human_time($time,true,true,true)."</td>";
			$status_aux .= "</tr>";
		}
		
    $status_aux .= "</table>";

	$container_title = __("Ticket by status");
    $container_status_incidents = print_container('container_status_incidents', $container_title, $status_aux, 'no', true, true, "container_simple_title", "container_simple_div");  

	$priority_aux .= "<table class='listing table_priority_report' style='width: 420px;' cellspacing=0 cellpadding=0 border=0>";
	
	$priority_aux .= "<tr>";
	$priority_aux .= "<th style='text-align:center;'><strong>".__("Priority")."</strong></th>";
	$priority_aux .= "<th style='text-align:center;'><strong>".__("Number")."</strong></th>";
	$priority_aux .= "</tr>";	
	
		foreach ($incident_priority as $key => $value) {
			$priority_aux .= "<tr>";
			$priority_aux .= "<td>".get_priority_name ($key)."</td>";
			$priority_aux .= "<td style='text-align:center;'>".$value."</td>";
			$priority_aux .= "</tr>";
		}

	$priority_aux .= "</table>";


	$priority_aux = $priority_aux;

	$container_title = __("Tickets by priority");
    $container_priority_incidents = print_container('container_priority_incidents', $container_title, $priority_aux, 'no', true, true, "container_simple_title", "container_simple_div");  
    
	if ($oldest_incident) {
		
        $oldest_incident_time = get_incident_workunit_hours  ($oldest_incident["id_incidencia"]);
		$output = "<table class='listing'>";
		$output .= "<th>";
		$output .= __("Metric");
		$output .= "</th>";
		$output .= "<th>";
		$output .= __("Value");
		$output .= "</th>";
		$output .= "</tr>";	
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Ticket Id")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$oldest_incident['id_incidencia'].'">#'.$oldest_incident['id_incidencia']. "</strong></a>";
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Ticket title")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$oldest_incident['id_incidencia'].'">'.$oldest_incident['titulo']. "</strong></a>";				
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Worktime hours")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= $oldest_incident_time. " ". __("Hours");
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Lifetime")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= format_numeric($max_lifetime/86400). " ". __("Days");
		$output .= "</td>";
		$output .= "</tr>";		
		$output .= "</table>";            
	}	else  {
		
		$output = graphic_error(false);
		$output .= __("N/A");
		
	}

	$output_aux = "<div style='width:100%; height:170px;'>";
	$output_aux .= $output;
	$output_aux .="</div>";

	$container_title = __("Longest closed ticket");
    $container_longest_closed = print_container('container_longest_closed', $container_title, $output_aux, 'no', true, true, "container_simple_title", "container_simple_div");  
	
	$data = array (__('Open') => $opened, __('Closed') => $total - $opened);
	$data = array (__('Close') => $total-$opened, __('Open') => $opened);

	$output = pie3d_graph ($config['flash_charts'], $data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	$output = "<div class='pie_frame'>".$output."</div>";
    
	$container_title = __("Open / Close ticket");
    $container_openclose_incidents = print_container('container_openclose_incidents', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  
	
	$clean_output = get_parameter("clean_output");

	$container_title = __("Top active users");
	$output = "<div class='pie_frame'>".$users_label."</div>";
    $container_topactive_users = print_container('container_topactive_users', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $container_title = __("Top ticket submitters");
    $output = "<div class='pie_frame'>".$submitter_label."</div>";
    $container_topincident_submitter = print_container('container_topincident_submitter', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $container_title = __("Top assigned users");
    $output = "<div class='pie_frame'>".$user_assigned_label."</div>";
    $container_user_assigned = print_container('container_user_assigned', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

   	$container_title = __("Tickets by group");
   	$output = "<br/>".pie3d_graph ($config['flash_charts'], $incident_group_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
    $output = "<div class='pie_frame'>".$output."</div>";
    $container_incidents_group = print_container('container_incidents_group', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

   	$container_title = __("Tickets by creator group");
   	$output = "<br/>".pie3d_graph ($config['flash_charts'], $incident_group_data2, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
    $output = "<div class='pie_frame'>".$output."</div>";
    $container_incident_creator_group = print_container('container_incident_creator_group', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

	$container_title = __("Top 5 average scoring by user");
	$output = "<div class='pie_frame'>".$scoring_label."</div>";
    $container_top5_scoring = print_container('container_top5_scoring', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

	//Print second table
	$output = "<table class='listing' style='width: 420px; margin: 10px auto'>";
	$output .= "<tr>";
	$output .= "<th style='text-align:center;'><strong>".__("Group")."</strong></th>";
	$output .= "<th style='text-align:center;'><strong>".__("Time")."</strong></th>";
	$output .= "</tr>";

	$count = 1;
	arsort($groups_time);
	foreach ($groups_time as $key => $value) {
		
		//Only show first 5
		if ($count == 5) {
			break;
		}

		$output .= "<tr>";
		$group_name = get_db_value ('nombre', 'tgrupo', 'id_grupo', $key);
		$output .= "<td>".$group_name."</td>";
		$output .= "<td style='text-align: center'>".give_human_time($value,true,true,true)."</td>";
		$output .= "</tr>";
		$count++;
	}	
	
	$output .= "</table>";
		
	$container_title = __("Top 5 group by time");
    $container_top5_group_time = print_container('container_top5_group_time', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  
	

	$output ="<table class='listing' style='width: 420px; margin: 10px auto;'>";
	$output .= "<tr>";
	$output .= "<th style='text-align:center;'><strong>".__("User")."</strong></th>";
	$output .= "<th style='text-align:center;'><strong>".__("Time")."</strong></th>";
	$output .= "</tr>";
	
	$count = 1;
	arsort($users_time);
	foreach ($users_time as $key => $value) {
		
		//Only show first 5
		if ($count == 5) {
			break;
		}
		
		$output .= "<tr>";
		$user_real = get_db_value ('nombre_real', 'tusuario', 'id_usuario', $key);
		$output .= "<td>".$user_real."</td>";
		$output .= "<td style='text-align: center'>".give_human_time($value,true,true,true)."</td>";
		$output .= "</tr>";
		$count++;
	}	
	
	$output .= "</table>";

	$output .= "</table>";
		
	$container_title = __("Top 5 users by time");
    $container_top5_user_time = print_container('container_top5_user_time', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div"); 
	
	//First row
	echo $container_ticket_activity;

	//Second row
    echo $container_ticket_oc;
    
	//Third row
	echo $container_incident_statistics;
	echo $container_top5_incidents;
	echo $container_sla_compliance;

	//Fourth row
	echo $container_status_incidents;
	echo $container_priority_incidents;

	//Fifth row
	echo $container_longest_closed;
	echo $container_openclose_incidents;
	echo "<br><br>";

	//Sixth row
	echo $container_topactive_users;
	echo $container_topincident_submitter;
	echo $container_user_assigned;

	// Seventh row
	echo $container_incidents_group;
	echo $container_incident_creator_group;
	echo $container_top5_scoring;

	// Eight row
	echo $container_top5_group_time;
	echo $container_top5_user_time;
}

/**
 * Update affected inventory objects in an incident.
 *
 * @param int Incident id to update.
 * @param array List of affected inventory objects ids.
 */
function update_incident_inventories ($id_incident, $inventories) {
	error_reporting (0);
	$where_clause = '';
	
	if (empty ($inventories)) {
		$inventories = array (0);
	}
	
	$sql = sprintf("SELECT id_inventory FROM tincident_inventory WHERE id_incident=%d", $id_incident);

	$current_objects = process_sql($sql);

	$obj_array = array();

	foreach ($current_objects as $co) {
		$obj_array[] = $co["id_inventory"];
	}

	foreach ($inventories as $id_inventory) {
		
		//id_inventory should be not equal to zero
		if ($id_inventory == 0) {
			continue;
		}

		//If the element is in array delete from aux array becasue
		//this aux array 
		$elem_key = array_search($id_inventory, $obj_array);
		
		if ($elem_key !== false) {
			
			unset($obj_array[$elem_key]);
		} else {

			$sql = sprintf ('INSERT INTO tincident_inventory
				VALUES (%d, %d)',
				$id_incident, $id_inventory);
			$tmp = process_sql ($sql);
		
			if ($tmp !== false)
				incident_tracking ($id_incident, INCIDENT_INVENTORY_ADDED,
					$id_inventory);
		}
	}
	
	foreach ($obj_array as $ob) {

		$sql = sprintf ('DELETE FROM tincident_inventory
		WHERE id_inventory = %s',
		$ob);
	
		$tmp = process_sql ($sql);

		if ($tmp !== false)
			incident_tracking ($id_incident, INCIDENT_INVENTORY_REMOVED, $ob);

	}
}

/**
 * Get all the contacts who reported a incident
 *
 * @param int Incident id.
 * @param bool Wheter to return only the contact names (indexed by id) or all
 * the data.
 *
 * @return array An array with all the contacts who reported the incident. Empty
 * array if none was set.
 */
function get_incident_contact_reporters ($id_incident, $only_names = false) {
	$sql = sprintf ('SELECT tcompany_contact.*
		FROM tcompany_contact, tincident_contact_reporters
		WHERE tcompany_contact.id = tincident_contact_reporters.id_contact
		AND id_incident = %d', $id_incident);
	$contacts = get_db_all_rows_sql ($sql);
	if ($contacts === false)
		return array ();
	
	if ($only_names) {
		$retval = array ();
		foreach ($contacts as $contact) {
			$retval[$contact['id']] = $contact['fullname'];
		}
		return $retval;
	}
	
	return $contacts;
}


/**
* Return total hours assigned to incident
*
* $id_inc       integer         ID of incident
**/

function get_incident_workunit_hours ($id_incident) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
		FROM tworkunit, tworkunit_incident, tincidencia 
		WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tworkunit_incident.id_workunit = tworkunit.id
		AND tincidencia.id_incidencia = %d', $id_incident);
	
	return (float) get_db_sql ($sql);
}


/**
 * Return the last entered WU in a given incident
 *
 * @param int Incident id
 *
 * @return array WU structure
 */

function get_incident_lastworkunit ($id_incident) {
	$workunits = get_incident_workunits ($id_incident);
	if (!isset($workunits[0]['id_workunit']))
		return;
	$workunit_data = get_workunit_data ($workunits[0]['id_workunit']);
	return $workunit_data;
}


function mail_incident ($id_inc, $id_usuario, $nota, $timeused, $mode, $public = 1){
	global $config;
	include_once($config["homedir"].'/include/functions_user.php');
	include_once($config["homedir"].'/include/functions_db.php');
	include_once($config["homedir"].'/include/functions_db.mysql.php');

	clean_cache_db();

	$row = get_db_row ("tincidencia", "id_incidencia", $id_inc);
	$group_name = get_db_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$email_group = get_db_sql ("SELECT email_group FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$forced_email = get_db_sql ("SELECT forced_email FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$user_defect_group = get_db_sql ("SELECT id_user_default FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$email_from = get_db_sql ("SELECT email_from FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$type_ticket = get_db_sql ("SELECT name FROM tincident_type WHERE id = ".$row["id_incident_type"]);
	$titulo =$row["titulo"];
	$description = $row["descripcion"];
	$prioridad = get_priority_name($row["prioridad"]);
	$estado = render_status ($row["estado"]);
	$resolution = render_resolution ($row["resolution"]);
	$create_timestamp = $row["inicio"];
	$update_timestamp = $row["actualizacion"];
	$usuario = $row["id_usuario"];
	$creator = $row["id_creator"];
	$email_copy = $row["email_copy"];

	// Send email for owner and creator of this incident
	$email_creator = get_user_email ($creator);
	$company_creator = get_user_company ($creator, true);
	if(empty($company_creator)) {
		$company_creator = "";
	}
	else {
		$company_creator = " (".reset($company_creator).")";
	}
	
	$email_owner = get_user_email ($usuario);
	$company_owner = get_user_company ($usuario, true);
	if(empty($company_owner)) {
		$company_owner = "";
	}
	else {
		$company_owner = " (".reset($company_owner).")";
	}

	//check if user is disabled
	$owner_disabled = user_is_disabled ($usuario);
	$creator_disabled = user_is_disabled ($creator);
	
	$ticket_score = '';
	if  (($row["estado"] == 7) AND ($row['score'] == 0)) {
		$ticket_score =  $config["base_url"]."/index.php?sec=incidents&sec2=operation/incidents/incident_detail&id=$id_inc";
		//$ticket_score =  '<a href="'.$config["base_url"].'"/index.php?sec=incidents&sec2=operation/incidents/incident_detail&id="'.$id_inc.'">'."Click hear to scoring".'</a>';
	}

	//name for fields
	$sql_name_custom = 'select ttf.label from tincident_field_data tfd, tincident_type_field ttf 
						where tfd.id_incident_field = ttf.id and tfd.id_incident='.$id_inc.';';
	$name_custom = get_db_all_rows_sql($sql_name_custom);
	if ($name_custom === false) {
		$name_custom = array();
	}

	foreach ($name_custom as $p){
		//value according to the name of the custom fields 
		$sql_value_custom = "select tfd.data from tincident_field_data tfd, tincident_type_field ttf 
							 where tfd.id_incident_field = ttf.id and tfd.id_incident=".$id_inc." and ttf.label='".$p['label']."';";
		$value_custom = get_db_sql($sql_value_custom);
		$MACROS['_'.$p['label'].'_'] = $value_custom;
	}
				
	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_fullname_"] = dame_nombre_real ($usuario);
	$MACROS["_username_"] = $usuario;
	$MACROS["_incident_id_"] = $id_inc;
	$MACROS["_incident_title_"] = $titulo;
	$MACROS["_creation_timestamp_"] = $create_timestamp;
	$MACROS["_update_timestamp_"] = $update_timestamp;
	$MACROS["_group_"] = $group_name ;
	$MACROS["_author_"] = dame_nombre_real ($creator).$company_creator;
	$MACROS["_owner_"] = dame_nombre_real ($usuario).$company_owner;
	$MACROS["_priority_"] = $prioridad ;
	$MACROS["_status_"] = $estado;
	$MACROS["_resolution_"] = $resolution;
	$MACROS["_time_used_"] = $timeused;
	$MACROS["_incident_main_text_"] = $description;
	
	$access_dir = empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
	$MACROS["_access_url_"] = $access_dir."/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=$id_inc";
	$MACROS["_incident_epilog_"] = $row["epilog"];
	$MACROS["_incident_closed_by_"] = $row["closed_by"];
	$MACROS["_type_tickets_"] = $type_ticket;

	// Resolve code for its name
	switch ($mode){
	case 10: // Add Workunit
		//$subject = "[".$config["sitename"]."] Incident #$id_inc ($titulo) has a new workunit from [$id_usuario]";
		$company_wu = get_user_company ($id_usuario, true);
		if(empty($company_wu)) {
			$company_wu = "";
		}
		else {
			$company_wu = " (".reset($company_wu).")";
		}
		$MACROS["_wu_user_"] = dame_nombre_real ($id_usuario).$company_wu;
		$MACROS["_wu_text_"] = $nota; // Do not pass to safe_output. $nota is already HTML Safe in this point

		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 7 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 6 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);

		if(!$templa_body){
			$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update_wu.tpl", $MACROS);
		} else {
			$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}
		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_new_wu.tpl", $MACROS);
		} else {
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}
		break;
	case 0: // Incident update
		
		$attachments = "";
		$images = "";
		
		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 9 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 8 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);

		if(!$templa_body){
			$text .= template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		} else {
			$text .= template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}
		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_update.tpl", $MACROS);
		} else{
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}
		$attached_files = get_db_all_rows_sql ("SELECT * FROM tattachment WHERE id_incidencia=".$id_inc);
		if ($attached_files === false) {
			$attached_files = array();
		}
		$i = 0;
		$j=0;
		foreach ($attached_files as $file) {
			$file_name = $file['id_attachment'].'_'.$file['filename'];
			$access_public = get_db_value("value","tconfig","token","access_public");
			$access_protocol = get_db_value("value","tconfig","token","access_protocol");
			if ($access_protocol) {
				$protocol = "https://";
			} else {
				$protocol = "http://";
			}
			
			$ext = strtolower(substr($file['filename'], -3, 3));
			
			if ($ext == "jpg" || $ext == "png" || $ext == "gif") {
				
				$path_file = $protocol.$access_public.'/'.$config['baseurl'].'/attachment/'.$file_name;
				
				if ($j == 0) {
					$images = $path_file;
				} else {
					$images .= ','.$path_file;
				}
				$j++;
			} else {
				
				$path_file = $config['homedir']."attachment/".$file_name;
			
				if ($i == 0) {
					$attachments = $path_file;
				} else {
					$attachments .= ','.$path_file;
				}
				$i++;
			}
		}
		
		break;
	case 1: // Incident creation
		$attachments = "";
		$images = "";
		
		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 0 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 1 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);

		if(!$templa_body){
			$text .= template_process ($config["homedir"]."/include/mailtemplates/incident_create.tpl", $MACROS);
		} else {
			$text .= template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}

		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_create.tpl", $MACROS);
		} else {
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}

		$attached_files = get_db_all_rows_sql ("SELECT * FROM tattachment WHERE id_incidencia=".$id_inc);
		if ($attached_files === false) {
			$attached_files = array();
		}
		$i = 0;
		$j=0;
		foreach ($attached_files as $file) {
			$file_name = $file['id_attachment'].'_'.$file['filename'];
			$access_public = get_db_value("value","tconfig","token","access_public");
			$access_protocol = get_db_value("value","tconfig","token","access_protocol");
			if ($access_protocol) {
				$protocol = "https://";
			} else {
				$protocol = "http://";
			}
			
			$ext = strtolower(substr($file['filename'], -3, 3));
			
			if ($ext == "jpg" || $ext == "png" || $ext == "gif") {
				
				$path_file = $protocol.$access_public.'/'.$config['baseurl'].'/attachment/'.$file_name;
				
				if ($j == 0) {
					$images = $path_file;
				} else {
					$images .= ','.$path_file;
				}
				$j++;
			} else {
				
				$path_file = $config['homedir']."attachment/".$file_name;
			
				if ($i == 0) {
					$attachments = $path_file;
				} else {
					$attachments .= ','.$path_file;
				}
				$i++;
			}
		}
		
		break;
	case 2: // New attach
		$attachments = "";
		$images = "";

		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 9 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 4 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);
		
		if(!$templa_body){
			$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		} else {
			$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}
		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_attach.tpl", $MACROS);
		} else {
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}

		$attached_files = get_db_all_rows_sql ("SELECT * FROM tattachment WHERE id_incidencia=".$id_inc);
		if ($attached_files === false) {
			$attached_files = array();
		}
		$i = 0;
		$j=0;
		foreach ($attached_files as $file) {
			$file_name = $file['id_attachment'].'_'.$file['filename'];
			$access_public = get_db_value("value","tconfig","token","access_public");
			$access_protocol = get_db_value("value","tconfig","token","access_protocol");
			if ($access_protocol) {
				$protocol = "https://";
			} else {
				$protocol = "http://";
			}
			
			$ext = strtolower(substr($file['filename'], -3, 3));
			
			if ($ext == "jpg" || $ext == "png" || $ext == "gif") {
				
				$path_file = $protocol.$access_public.'/'.$config['baseurl'].'/attachment/'.$file_name;
				
				if ($j == 0) {
					$images = $path_file;
				} else {
					$images .= ','.$path_file;
				}
				$j++;
			} else {
				$path_file = $config['homedir']."attachment/".$file_name;
			
				if ($i == 0) {
					$attachments = $path_file;
				} else {
					$attachments .= ','.$path_file;
				}
				$i++;
			}
		}
	
		break;
	case 3: // Incident deleted
		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 9 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 5 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);
		if(!$templa_body){
			$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		} else {
			$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}
		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_delete.tpl", $MACROS);
		} else {
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}
		break;
    case 5: // Incident closed
		$MACROS["_ticket_score_"] = $ticket_score;
		
		$temp_group  = get_db_value('id_grupo', 'tgrupo', 'nombre', $group_name);
		$sql_body    = "SELECT name FROM temail_template WHERE template_action = 2 AND id_group =".$temp_group.";";
		$sql_subject = "SELECT name FROM temail_template WHERE template_action = 3 AND id_group =".$temp_group.";";
		$templa_body = get_db_sql($sql_body);
		$templa_subj = get_db_sql($sql_subject);
		if(!$templa_body){
			$text = template_process ($config["homedir"]."/include/mailtemplates/incident_close.tpl", $MACROS);
		} else {
			$text = template_process ($config["homedir"]."/include/mailtemplates/".$templa_body.".tpl", $MACROS);
		}
		if(!$templa_subj){
			$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_close.tpl", $MACROS);
		} else {
			$subject = template_process ($config["homedir"]."/include/mailtemplates/".$templa_subj.".tpl", $MACROS);
		}
        break;
   }
		
		
	// Create the TicketID for have a secure reference to incident hidden 
	// in the message. Will be used for POP automatic processing to add workunits
	// to the incident automatically.
	if ($public != 7) {
		//owner
		$msg_code = "TicketID#$id_inc";
		$msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] . $row["id_usuario"]),0,5);
		$msg_code .= "/" . $row["id_usuario"];
		integria_sendmail ($email_owner, $subject, $text, $attachments, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);
		
		//creator
		if ($email_owner != $email_creator) {
			$msg_code = "TicketID#$id_inc";
			$msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] . $row["id_creator"]),0,5);
			$msg_code .= "/".$row["id_creator"];
			integria_sendmail ($email_creator, $subject, $text, $attachments, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);
		}
		
		// Send emails to the people in the group added
		if($forced_email != 0) {
			$email_default = get_user_email ($user_defect_group);
			integria_sendmail ($email_default, $subject, $text, $attachments, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);
			if($email_group){
				$email_g = explode(',',$email_group);
				foreach ($email_g as $k){
					integria_sendmail ($k, $subject, $text, $attachments, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);	
				}
			}
		}
	
	}
	if ($public == 7) {
		// Send a copy to each address in "email_copy"
			if ($email_copy != ""){
				$emails = explode (",",$email_copy);
				foreach ($emails as $em){
					integria_sendmail ($em, $subject, $text, $attachments, "", $email_from, 0, "", "X-Integria: no_process", $images);
				}
			}
	}
	
	if ($public == 1) {
		// Send email for all users with workunits for this incident
		$sql1 = "SELECT DISTINCT(tusuario.direccion), tusuario.id_usuario FROM tusuario, tworkunit, tworkunit_incident WHERE tworkunit_incident.id_incident = $id_inc AND tworkunit_incident.id_workunit = tworkunit.id AND tworkunit.id_user = tusuario.id_usuario AND tusuario.disabled=0";
		if ($result=mysql_query($sql1)) {
			while ($row=mysql_fetch_array($result)){
				if (($row[0] != $email_owner) AND ($row[0] != $email_creator)){
					$msg_code = "TicketID#$id_inc";
					$msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] .  $row[1]),0,5);
					$msg_code .= "/". $row[1];
					integria_sendmail ( $row[0], $subject, $text, false, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);
				}
			}
		}

		// Send email to incident reporters associated to this incident
		if ($config['incident_reporter'] == 1){
			$contacts = get_incident_contact_reporters ($id_inc , true);
			if ($contats)
			foreach ($contacts as $contact) {
				$contact_email = get_db_sql ("SELECT email FROM tcompany_contact WHERE fullname = '$contact'");
				integria_sendmail ($contact_email, $subject, $text, false, $msg_code, $email_from, 0, "", "X-Integria: no_process", $images);
			}
		}
	}
}

function people_involved_incident ($id_inc){
	global $config;
	$row0 = get_db_row ("tincidencia", "id_incidencia", $id_inc);
	$people = array();
	
	array_push ($people, $row0["id_creator"]);
	 if (!in_array($row0["id_usuario"], $people)) {	
		array_push ($people, $row0["id_usuario"]);
	}
	
	// Take all users with workunits for this incident
	$sql1 = "SELECT DISTINCT(tusuario.id_usuario) FROM tusuario, tworkunit, tworkunit_incident WHERE tworkunit_incident.id_incident = $id_inc AND tworkunit_incident.id_workunit = tworkunit.id AND tworkunit.id_user = tusuario.id_usuario";
	if ($result = mysql_query($sql1)) {
		while ($row = mysql_fetch_array($result)){
			if (!in_array($row[0], $people))
				array_push ($people, $row[0]);
		}
	}
	
	return $people;
}

// Return TRUE if User has access to that incident

function user_belong_incident ($user, $id_inc) {
	return in_array($user, people_involved_incident ($id_inc));
}


/** 
 * Returns the n top creator users (users who create a new incident).
 *
 * @param lim n, number of users to return.
 */
function get_most_incident_creators ($lim, $incident_filter = false) {
	$sql = 'SELECT id_creator, count(*) AS total FROM tincidencia ';
	
	if ($incident_filter) {
		$filter_clause = join(",", $incident_filter);
		$sql .= ' WHERE id_incidencia IN ('.$filter_clause.') ';
	}
	
	$sql .= ' GROUP by id_creator ORDER BY total DESC LIMIT '. $lim;
	
	$most_creators = get_db_all_rows_sql ($sql);
	if ($most_creators === false) {
		return array ();
	}
	
	return $most_creators;
}

/** 
 * Returns the n top incident owner by scoring (users with best scoring).
 *
 * @param lim n, number of users to return.
 */
function get_best_incident_scoring ($lim, $incident_filter=false) {
	$sql = 'SELECT id_usuario, AVG(score) AS total FROM tincidencia';
	
	$filter_clause = '';
		
	if ($incident_filter) {
		
		$filter_clause = join(",", $incident_filter);
		$sql .= ' WHERE id_incidencia IN ('.$filter_clause.')';
	}
	
	$sql .= ' GROUP by id_usuario ORDER BY total DESC LIMIT '. $lim;
	
	$most_creators = get_db_all_rows_sql ($sql);
	
	$all_zero = true;
	
	foreach ($most_creators as $mc) {
		if ($mc['total'] != 0) {
			$all_zero = false;
			break;
		}
	}
	
	if ($most_creators === false || $all_zero) {
		
		return array ();
	}
	
	return $most_creators;
}

/*
 * Returns all incident type fields.
 */ 
function incidents_get_all_type_field ($id_incident_type, $id_incident) {
	
	global $config;
	
	$sql = "SELECT * FROM tincident_type_field WHERE id_incident_type = $id_incident_type ORDER BY `order`";
	$fields = get_db_all_rows_sql($sql);
	
	if ($fields === false) {
		$fields = array();
	}
	
	$all_fields = array();
	foreach ($fields as $id=>$field) {
		foreach ($field as $key=>$f) {

			if ($key == 'label') {
				$all_fields[$id]['label_enco'] = base64_encode($f);
			}
			$all_fields[$id][$key] = safe_output($f);
		}
	}

	foreach ($all_fields as $key => $field) {
		$id_incident_field = $field['id'];
		$data = safe_output(get_db_value_filter('data', 'tincident_field_data', array('id_incident'=>$id_incident, 'id_incident_field' => $id_incident_field), 'AND'));
		if ($data === false) {
			$all_fields[$key]['data'] = '';
		} else {
			$all_fields[$key]['data'] = $data;
		}
	}

	return $all_fields;
	
}

function incidents_metric_to_state($metric) {
	
	$state = "";
	
	switch ($metric) {
		case INCIDENT_METRIC_USER:
			$state = INCIDENT_USER_CHANGED;
			
			break;
		case INCIDENT_METRIC_GROUP:
			$state = INCIDENT_GROUP_CHANGED;
			
			break;
		case INCIDENT_METRIC_STATUS:
			$state = INCIDENT_STATUS_CHANGED;
			
			break;
		default:
			break;
	}
	
	return $state;
}

function incidents_state_to_metric($state) {
	$metric = "";
	
	switch ($state) {
		case INCIDENT_USER_CHANGED:
			$metric = INCIDENT_METRIC_USER;
			
			break;
		case INCIDENT_GROUP_CHANGED:
			$metric = INCIDENT_METRIC_GROUP;
			
			break;
		case INCIDENT_STATUS_CHANGED:
			$metric = INCIDENT_METRIC_STATUS;
			
			break;
		default:
			break;
	}
		
	return $metric;
}

function incidents_update_stats_item ($id_incident, $id_aditional, $metric, $time_from, $time_to) {

	$holidays_seconds = incidents_get_holidays_seconds_by_timerange($time_from, $time_to);
	$diff_time = $time_to - $time_from - $holidays_seconds;

	$filter = array('id_incident' => $id_incident, "metric" => $metric);
	
	switch ($metric) {
		case INCIDENT_METRIC_USER: 
			$filter["id_user"] = $id_aditional;
			break;
		case INCIDENT_METRIC_STATUS:
			$filter["status"] = $id_aditional;
			break;
		case INCIDENT_METRIC_GROUP:
			$filter["id_group"] = $id_aditional;
			break;
	}
	
	$stats_item = get_db_row_filter("tincident_stats", $filter);
	
	if ($stats_item) {
		//We have previous data for this stat, so update it
		$val_upd_time = array("seconds" => $stats_item["seconds"] + $diff_time);
		$val_upd_time_where = array("id" => $stats_item["id"]);

		process_sql_update("tincident_stats", $val_upd_time, $val_upd_time_where);
	} else {
		$values = array("id_incident" => $id_incident,
						"seconds" => $diff_time,
						"metric" => $metric);
						
		switch ($metric) {
			case INCIDENT_METRIC_USER: 
				$values["id_user"] = $id_aditional;
				break;
			case INCIDENT_METRIC_STATUS:
				$values["status"] = $id_aditional;
				break;
			case INCIDENT_METRIC_GROUP:
				$values["id_group"] = $id_aditional;
				break;	
			default:
				break;
		}

		process_sql_insert("tincident_stats", $values);
	}
}

function incidents_update_incident_stats_data ($incident) {
	
	$start_time = strtotime($incident["inicio"]);

	// Check valid date
	if ($start_time < strtotime('1970-01-01 00:00:00')) {
		return;
	}
	
	$id_incident = $incident["id_incidencia"];
	$last_incident_update = $incident["last_stat_check"];
	$last_incident_update_time = strtotime($last_incident_update);

	$now = time();

	$metrics = array(
			INCIDENT_METRIC_USER,
			INCIDENT_METRIC_STATUS,
			INCIDENT_METRIC_GROUP
		);

	foreach ($metrics as $metric) {

		$state = incidents_metric_to_state($metric);

		// Get the last updated item in the last incident update
		$sql = sprintf("SELECT timestamp, id_aditional
						FROM tincident_track
						WHERE id_incident = %d
							AND state = %d
							AND timestamp < '%s'
						ORDER BY timestamp DESC
						LIMIT 1",
						$id_incident, $state, $last_incident_update);
		$last_updated_value = process_sql($sql);
		if ($last_updated_value === false) {
			$last_updated_value = array();
		}

		// Get the changes of the metric from the incident track table
		// Get only the changes produced before the last incident update
		// in ascending order
		$sql = sprintf("SELECT timestamp, id_aditional
						FROM tincident_track
						WHERE id_incident = %d
							AND state = %d
							AND timestamp > '%s'
						ORDER BY timestamp ASC",
						$id_incident, $state, $last_incident_update);
		$track_values = process_sql($sql);
		if ($track_values === false) {
			$track_values = array();
		}

		// If there is no changes since the last incident update,
		// the actual value is updated
		if (count($track_values) < 1 && count($last_updated_value) > 0) {
			incidents_update_stats_item($id_incident, $last_updated_value[0]["id_aditional"], $metric, $last_incident_update_time, $now);
		}
		// Go over the changes to create the stat items and set the seconds
		// passed in every state
		for ($i = 0; $i < count($track_values); $i++) {
			
			$min_time = strtotime($track_values[$i]["timestamp"]);

			if ($track_values[$i+1]) {
				// There was a change after this change
				$max_time = strtotime($track_values[$i+1]["timestamp"]);
			} else {
				// The actual value
				$max_time = $now;
			}

			// Final update to the last metric item of the last incident update
			if (!$track_values[$i-1] && count($last_updated_value) > 0) {
				incidents_update_stats_item($id_incident, $last_updated_value[0]["id_aditional"], $metric, $last_incident_update_time, $min_time);
			}

			incidents_update_stats_item($id_incident, $track_values[$i]["id_aditional"], $metric, $min_time, $max_time);
		}
	}

	// total_time
	$filter = array(
			"metric" => INCIDENT_METRIC_STATUS, 
			"status" => STATUS_CLOSED, 
			"id_incident" => $id_incident
		);
	$closed_time = get_db_value_filter ("seconds", "tincident_stats", $filter);
	if (!$closed_time) {
		$closed_time = 0;
	}
	$start_time = strtotime($incident["inicio"]);
	$holidays_seconds = incidents_get_holidays_seconds_by_timerange($start_time, $now);
	$total_time = $now - $start_time - $closed_time - $holidays_seconds;

	$sql = sprintf("SELECT id
					FROM tincident_stats
					WHERE id_incident = %d
						AND metric = '%s'",
					$id_incident, INCIDENT_METRIC_TOTAL_TIME);
	$row = get_db_row_sql($sql);
	
	//Check if we have a previous stat metric to update or create it
	if ($row) {
		$val_upd = array("seconds" => $total_time);
		$val_where = array("id" => $row["id"]);
		process_sql_update("tincident_stats", $val_upd, $val_where);	
	} else {
		$val_new = array(
				"seconds" => $total_time,
				"metric" => INCIDENT_METRIC_TOTAL_TIME,
				"id_incident" => $id_incident
			);
		process_sql_insert("tincident_stats", $val_new);
	}

	// total_w_third
	$filter = array(
			"metric" => INCIDENT_METRIC_STATUS, 
			"status" => STATUS_PENDING_THIRD_PERSON, 
			"id_incident" => $id_incident
		);
	$third_time = get_db_value_filter ("seconds", "tincident_stats", $filter);
	if (!$third_time || $third_time < 0) {
		$third_time = 0;
	}
	$total_time -= $third_time;

	$sql = sprintf("SELECT id
					FROM tincident_stats
					WHERE id_incident = %d
						AND metric = '%s'",
					$id_incident, INCIDENT_METRIC_TOTAL_TIME_NO_THIRD);
	$row = get_db_row_sql($sql);
	
	//Check if we have a previous stat metric to update or create it
	if ($row) {
		$val_upd = array("seconds" => $total_time);
		$val_where = array("id" => $row["id"]);
		process_sql_update("tincident_stats", $val_upd, $val_where);	
	} else {
		$val_new = array(
				"seconds" => $total_time,
				"metric" => INCIDENT_METRIC_TOTAL_TIME_NO_THIRD,
				"id_incident" => $id_incident
			);
		process_sql_insert("tincident_stats", $val_new);
	}

	//Update last_incident_update field from tincidencia
	$update_values = array("last_stat_check" => date("Y-m-d H:i:s", $now));
	process_sql_update("tincidencia", $update_values, array("id_incidencia" => $id_incident));

}

function incidents_update_stats_data ($params = array()) {

	if (!isset($params["closed"])) {
		$closed = false;
	} else {
		$closed = $params["closed"];
	}

	if (!isset($params["time_limit"])) {
		$time_limit = false;
	} else {
		$time_limit = $params["time_limit"];
	}

	if (!isset($params["hours"])) {
		$hours = 1;
	} else {
		$hours = $params["hours"];
	}

	$now = time();
	$seconds = 60 * 60 * $hours;

	if ($closed) {
		$sql = "SELECT *
				FROM tincidencia
				ORDER BY id_incidencia DESC";
	} else {
		$sql = sprintf("SELECT *
						FROM tincidencia
						WHERE estado <> %d
						ORDER BY id_incidencia DESC", STATUS_CLOSED);
	}

	$new = true;
	while ($incident = get_db_all_row_by_steps_sql($new, $result, $sql)) {
		$new = false;

		$last_incident_update = $incident["last_stat_check"];
		$last_incident_update_time = strtotime($last_incident_update);

		// Update data if the last update was more than the hours indicated or if the time limit is false
		if ( !$time_limit || ( $time_limit && $last_incident_update_time < ($now - $seconds) ) ) {
			incidents_update_incident_stats_data($incident);
		}
	}
}

function incidents_get_incident_status_text ($id) {
	$status = get_db_value ('estado', 'tincidencia', 'id_incidencia', $id);
	
	$name = get_db_value ('name', 'tincident_status', 'id', $status);
	
	return $name;
}

function incidents_get_incident_status_id ($id) {
	$status = get_db_value ('estado', 'tincidencia', 'id_incidencia', $id);
	
	return $status;
}

function incidents_get_incident_priority_text ($id) {
	$priority = get_db_value ('prioridad', 'tincidencia', 'id_incidencia', $id);
	
	$name = get_priority_name($priority);
	
	return $name;
}

//This function is the opossite of render_priority, gets priority rendered and
//converts it to the original value
function incident_convert_priority($prior) {
		switch($prior) {
		
		case 0:
			return 10;
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
			return $prior-1;
		default:
			return false;
	}
}

function incidents_get_incident_group_text ($id) {
	$group = get_db_value ('id_grupo', 'tincidencia', 'id_incidencia', $id);
	
	$name = get_db_value ('nombre', 'tgrupo', 'id_grupo', $group);
	
	return $name;	
}

function incidents_get_incident_resolution_text ($id) {
	$resolution = get_db_value ('resolution', 'tincidencia', 'id_incidencia', $id);
	
	if ($resolution == 0) {
		$name = __("None");
	} else {
		$name = get_db_value ('name', 'tincident_resolution', 'id', $resolution);
	}
	
	return $name;	
}

function incident_create_attachment ($id_incident, $user, $filename, $path, $description) {
	
	$filesize = filesize($path); // In bytes
	$now = date ("Y-m-d H:i:s", time());
	
	$sql = sprintf ('INSERT INTO tattachment (id_incidencia, id_usuario,
			filename, description, size, `timestamp`)
			VALUES (%d, "%s", "%s", "%s", %d, "%s")',
			$id_incident, $user, $filename, $description, $filesize, $now);
	
	$id = process_sql ($sql, 'insert_id');
	
	return $id;
}

function incidents_get_incident_type_text ($id) {
	$type = get_db_value ('id_incident_type', 'tincidencia', 'id_incidencia', $id);
	
	if ($type == 0) {
		$name = __("None");
	} else {
		$name = get_db_value ('name', 'tincident_type', 'id', $type);
	}
	
	return $name;	
}

function incident_get_type_field_values ($id, $exclude_type = "") {
	$id_type = get_db_value ('id_incident_type', 'tincidencia', 'id_incidencia', $id);
	
	if ($exclude_type != "") {
		$exclude = " AND TF.type <> '$exclude_type'";
	}
	
	$sql = sprintf("SELECT TF.label, FD.data FROM tincident_field_data FD, tincident_type_field TF 
					WHERE TF.id_incident_type = %d AND TF.id = FD.id_incident_field
					AND FD.id_incident = %d $exclude", $id_type, $id);
					
	$fields = get_db_all_rows_sql($sql);
	
	if (!$fields) {
		$fields = array();
	}
	
	$ret_fields = array();

	foreach ($fields as $f) {
		$ret_fields[$f["label"]] = $f["data"];
	}
	
	return $ret_fields;
}

function incidents_get_incident_task_text ($id) {
	
	$task = get_db_value ('id_task', 'tincidencia', 'id_incidencia', $id);
		
	if ($task) {
		$name = get_db_value ('name', 'ttask', 'id', $task);
	} else {
		$name = __("None");
	}
	
	return $name;
}

function incidents_get_incident_stats ($id) {
	
	//Get all incident
	$raw_stats = get_db_all_rows_filter('tincident_stats', array('id_incident' => $id));

	if(!$raw_stats) {
		return array();
	}
	
	//Sort incident by type and metric into a hash table :)
	$stats = array();
	
	$stats[INCIDENT_METRIC_USER] = array();
	$stats[INCIDENT_METRIC_STATUS] = array(
			STATUS_NEW => 0,
			STATUS_UNCONFIRMED => 0,
			STATUS_ASSIGNED => 0,
			STATUS_REOPENED => 0,
			STATUS_VERIFIED => 0,
			STATUS_RESOLVED => 0,
			STATUS_PENDING_THIRD_PERSON => 0,
			STATUS_CLOSED => 0
		);
							
	$stats[INCIDENT_METRIC_GROUP] = array();
	$stats[INCIDENT_METRIC_TOTAL_TIME] = 0;
	$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] = 0;

	foreach ($raw_stats as $st) {
		
		switch ($st["metric"]) {
			case INCIDENT_METRIC_USER: 
				$stats[INCIDENT_METRIC_USER][$st["id_user"]] = $st["seconds"];
				break;
			case INCIDENT_METRIC_STATUS:
				$stats[INCIDENT_METRIC_STATUS][$st["status"]] = $st["seconds"];
				break;
			case INCIDENT_METRIC_GROUP:
				$stats[INCIDENT_METRIC_GROUP][$st["id_group"]] = $st["seconds"];
				break;	
			case INCIDENT_METRIC_TOTAL_TIME_NO_THIRD: 
				$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] = $st["seconds"];
				break;	
			case INCIDENT_METRIC_TOTAL_TIME:
				$stats[INCIDENT_METRIC_TOTAL_TIME] = $st["seconds"];
				break;
		}
	}
	
	//Get last metrics and update times until now
	$now = time();
	
	//Get last incident update check for total time metric
	$time_str = get_db_value_filter("last_stat_check", "tincidencia", array("id_incidencia" => $id));
	$unix_time = strtotime($time_str);
	$global_diff = ($now - $unix_time); //Time diff in seconds
	
	//Get non-working days from last stat update and delete the seconds :)
	$last_stat_check = get_db_value("last_stat_check", "tincidencia", "id_incidencia", $id);
	
	//Avoid to check for holidays since the begining of the time!
	if ($last_stat_check !== "0000-00-00 00:00:00") {
		$last_stat_check_time = strtotime($last_stat_check);
	} else {
		$last_stat_check_time = $now;
	}
	
	$holidays_seconds = incidents_get_holidays_seconds_by_timerange($last_stat_check_time, $now);
		
	$global_diff -= $holidays_seconds;

	$stats[INCIDENT_METRIC_TOTAL_TIME] += $global_diff;	
	
	//Fix last time track per metric	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_USER_CHANGED,$id);
	$last_track_user_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff	
	if (isset($stats[INCIDENT_METRIC_USER][$last_track_user_id])) {	
		$stats[INCIDENT_METRIC_USER][$last_track_user_id] += $global_diff;
	}
	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_GROUP_CHANGED,$id);
	$last_track_group_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff
	if (isset($stats[INCIDENT_METRIC_GROUP][$last_track_group_id])) {	
		$stats[INCIDENT_METRIC_GROUP][$last_track_group_id] += $global_diff;
	}
	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_STATUS_CHANGED,$id);
	$last_track_status_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff
	if (isset($stats[INCIDENT_METRIC_STATUS][$last_track_status_id])) {
		$stats[INCIDENT_METRIC_STATUS][$last_track_status_id] += $global_diff;
	}
	
	//If status not equal to pending on third person add this time to metric
	if ($last_track_status_id != STATUS_PENDING_THIRD_PERSON) {
		$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] += $global_diff;
	}
		
	return ($stats);
}

function incidents_get_holidays_seconds_by_timerange ($begin, $end) {

	//Get all holidays in this range and convert to seconds 
	$holidays = calendar_get_holidays_by_timerange($begin, $end);

	$day_in_seconds = 3600*24;
	
	$holidays_seconds = count($holidays)*$day_in_seconds;
	
	//We need to tune a bit the amount of seconds calculated before
	
	//1.- If start date was holiday only discount seconds from creation time to next day
	$str_date = date('Y-m-d',$begin);

	if (!is_working_day($str_date)) {
		
		//Calculate seconds to next day
		$start_day = strtotime($str_date);
		$finish_time = $start_day + $day_in_seconds;
		
		$aux_seconds = ($finish_time - $begin);
		
		$holidays_seconds = $holidays_seconds - $aux_seconds;
	}

	//2.- If finish date was holiday only discount seconds from now to begining of the day
	$str_date = date('Y-m-d',$end);
	
	if (!is_working_day($str_date)) {
		
		//Calculate seconds to next day
		$begining_day = strtotime($str_date);
		
		$aux_seconds = ($end - $begining_day);
		
		$holidays_seconds = $holidays_seconds - $aux_seconds;
	}	

	return $holidays_seconds;
}

//Get incident SLA
function incidents_get_incident_slas ($id_incident, $only_names = true) {
	
	$id_group = get_db_value ("id_grupo", "tincidencia", "id_incidencia", $id_incident);
	
	$sql = sprintf ('SELECT tsla.* FROM tgrupo, tsla WHERE tgrupo.id_sla = tsla.id
					AND tgrupo.id_grupo = %d', $id_group);
	$slas = get_db_all_rows_sql ($sql);
	
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

/*Filters or display result for incident search*/
function incidents_search_result ($filter, $ajax=false, $return_incidents = false, $print_result_count = false, $no_parents = false, $resolve_names = false, $report_mode = false, $csv_mode = false, $id_ticket = 0) {
	global $config;
	echo '<div class = "incident_table" id = "incident_table">';
	$params = "";

	foreach ($filter as $key => $value) {
		$params .= "&search_".$key."=".$value;
	}
	
	if ($filter['closed_by'] == '') {
		$filter['closed_by'] = get_parameter('search_closed_by', '');
	}
	if ($filter['from_date'] == '') {
		$filter['from_date'] = get_parameter('search_from_date', '');
	}

	//Only show incident for last year if there isn't a search by dates
	if (!$filter['first_date'] && !$filter['last_date']) {

		$filter_year = $filter;

		$now = print_mysql_timestamp();
		$year_in_seconds = 3600 * 24 * 365;

		$year_ago_unix = time() - $year_in_seconds;

		$year_ago = date("Y-m-d H:i:s", $year_ago_unix);
		$filter_year['first_date'] = $year_ago;
		$filter_year['last_date'] = $now;

		$count_this_year = filter_incidents($filter_year, true, false, $no_parents, $csv_mode);

		$aux_text = "(".$count_this_year.")".print_help_tip(__("Tickets created last year"),true);
	}

	if (!$report_mode) {
		//Add offset to filter parameters
		$offset = get_parameter("offset");
		$filter["offset"] = $offset;

		// Store the previous limit filter
		if(isset($filter["limit"])){
			$limit_aux = $filter["limit"];
		} else {
			$limit_aux = 0;
		}
	}
	// Set the limit filter to 0 to retrieve all the tickets for the array pagination
	$filter["limit"] = 0;

	// All the tickets the user sees are retrieved
	$incidents = filter_incidents($filter, false, true, $no_parents, $csv_mode);

	$count = empty($incidents) ? 0 : count($incidents);

	if ($resolve_names) {
		$incidents_aux = array();
		$i=0;
		foreach ($incidents as $inc) {
			$incidents_aux[$i]=$inc;
			$incidents_aux[$i]['estado'] = incidents_get_incident_status_text ($inc['id_incidencia']);
			$incidents_aux[$i]['resolution'] = incidents_get_incident_resolution_text ($inc['id_incidencia']);
			$incidents_aux[$i]['prioridad'] = incidents_get_incident_priority_text ($inc['id_incidencia']);
			$incidents_aux[$i]['id_grupo'] = incidents_get_incident_group_text ($inc['id_incidencia']);
			$incidents_aux[$i]['id_group_creator'] = incidents_get_incident_group_text ($inc['id_incidencia']);
			//~ $incidents_aux[$i]['id_incident_type'] = incidents_get_incident_type_text ($inc['id_incidencia']);
			$i++;
		}
		$incidents = $incidents_aux;
	}
	
	if ($return_incidents)
		return $incidents;

	if (!$report_mode) {
		// Set the limit filter to its previous value
		$filter["limit"] = $limit_aux;

		$url = "index.php?sec=incidents&sec2=operation/incidents/incident_search".$params;
		echo "<div class='clear_both'>";
			$incidents = print_array_pagination($incidents, $url, $offset);
		echo "</div>";
	}
	$statuses = get_indicent_status ();
	$resolutions = get_incident_resolutions ();
	
	// ORDER BY
	if ($filter['order_by'] && !is_array($filter['order_by'])) {
		$order_by = json_decode(clean_output($filter['order_by']), true);
	} else {
		$order_by = $filter['order_by'];
	}
	if (!$report_mode) {
		if (is_array($order_by) && array_key_exists("id_incidencia", $order_by) && ($order_by["id_incidencia"] != "")) {
			if ($order_by["id_incidencia"] == "DESC") {
				$id_order_image = "&nbsp;<a href='javascript:changeIncidentOrder(\"id_incidencia\", \"ASC\")'><img src='images/arrow_down_orange.png'></a>";
			} else {
				$id_order_image = "&nbsp;<a href='javascript:changeIncidentOrder(\"id_incidencia\", \"\")'><img src='images/arrow_up_orange.png'></a>";
			}
		} else {
			$id_order_image = "&nbsp;<a href='javascript:changeIncidentOrder(\"id_incidencia\", \"DESC\")'><img src='images/block_orange.png'></a>";
		}
		if (is_array($order_by) && array_key_exists("prioridad", $order_by) && ($order_by["prioridad"] != "")) {
			if ($order_by["prioridad"] == "DESC") {
				$priority_order_image = "<a href='javascript:changeIncidentOrder(\"prioridad\", \"ASC\")'><img src='images/arrow_down_orange.png'></a>";
			} else {
				$priority_order_image = "<a href='javascript:changeIncidentOrder(\"prioridad\", \"\")'><img src='images/arrow_up_orange.png'></a>";
			}
		} else {
			$priority_order_image = "<a href='javascript:changeIncidentOrder(\"prioridad\", \"DESC\")'><img src='images/block_orange.png'></a>";
		}
	} else {
		$id_order_image = "";
		$priority_order_image = "";
	}
	
	// ----------------------------------------
	// Here we print the result of the search
	// ----------------------------------------
	
	echo '<table width="100%" cellpadding="0" cellspacing="0" border="0px" class="listing" id="incident_search_result_table">';

	echo '<thead>';
	echo "<tr>";
	echo "<th>";
	echo print_checkbox ('incidentcb-all', "", false, true);
	echo "</th>";
	echo "<th>";
	echo __('ID') . $id_order_image;
	echo "</th>";
	echo "<th>";
	echo __('SLA');
	echo "</th>";
	if ($report_mode) {
		echo "<th>";
		echo __(' % SLA');
		echo "</th>";
	}
	echo "<th>";
	echo __('Ticket');
	echo "</th>";
	echo "<th>";
	echo __('Group')."/<br /><i>".__('Company')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Status')."/<br /><i>".__('Resolution')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Prior') . $priority_order_image;
	echo "</th>";
	echo "<th>";
	echo __('Updated')."/<br /><i>".__('Started')."</i>";
	echo "</th>";

	if ($config["show_creator_incident"] == 1)
		echo "<th>";
		echo __('Creator');	
		echo "</th>";
	if ($config["show_owner_incident"] == 1)
		echo "<th>";
		echo __('Owner');	
		echo "</th>";

	echo "</tr>";
	echo '</thead>';
	echo "<tbody>";

	if ($incidents == false) {
		echo '<tr><td colspan="11">'.__('Nothing was found').'</td></tr>';
	} else {

		foreach ($incidents as $incident) {
			/* We print the rows directly, because it will be used in a sortable
			   jQuery table and it only needs the rows */

			if ($incident["estado"] < 3 )
				$tr_status = 'class="red_row"';
			elseif ($incident["estado"] < 6 )
				$tr_status = 'class="yellow_row"';
			else
				$tr_status = 'class="green_row"';
				
			if ($incident["id_incidencia"] != $id_ticket) {
			
				echo '<tr '.$tr_status.' id="incident-'.$incident['id_incidencia'].'"';

				echo " style='border-bottom: 1px solid #ccc;' >";

				echo '<td>';
				print_checkbox_extended ('incidentcb-'.$incident['id_incidencia'], $incident['id_incidencia'], false, '', '', 'class="cb_incident"');
				echo '</td>';

				
				//Print incident link if not ajax, if ajax link to js funtion to replace parent
				$link = "index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_incidencia"];

				
				if ($ajax) {
					$link = "javascript:update_parent('".$incident["id_incidencia"]."')";
				}
				
				
				echo '<td>';
				if (!$report_mode) {
					echo '<strong><a href="'.$link.'">#'.$incident['id_incidencia'].'</a></strong></td>';
				} else {
					echo '<strong>'.'#'.$incident['id_incidencia'].'</strong></td>';
				}
				
				// SLA Fired ?? 
				if ($incident["affected_sla_id"] != 0)
					echo '<td><img src="images/exclamation.png" /></td>';
				else
					echo '<td></td>';
				
				// % SLA	
				if ($report_mode) {
					echo "<td>";
					 if ($incident["affected_sla_id"] != 0)
						echo format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
					 else 
						echo "";
					echo "</td>";
				}
				
				echo '<td>';

				if (!$report_mode) {							
					echo '<strong><a href="'.$link.'">'.ui_print_truncate_text(safe_output($incident['titulo']), 50).'</a></strong><br>';
				} else {
					echo '<strong>'.$incident['titulo'].'</strong><br>';
				}
				echo "<span>";
				echo incidents_get_incident_type_text($incident["id_incidencia"]); // Added by slerena 26Ago2013
				$sql = sprintf("SELECT * FROM tincident_type_field WHERE id_incident_type = %d", $incident["id_incident_type"]);
				$config['mysql_result_type'] = MYSQL_ASSOC;
				$type_fields = get_db_all_rows_sql($sql);
				
				$type_fields_values_text = "";
				if ($type_fields) {
					foreach ($type_fields as $type_field) {
						if ($type_field["show_in_list"]) {
							$field_data = get_db_value_filter("data", "tincident_field_data", array ("id_incident" => $incident["id_incidencia"], "id_incident_field" => $type_field["id"]));
							if ($field_data) {
								if ($type_field["type"] == "textarea") {
									$field_data = "<div style='display: inline-block;' title='$field_data'>" . substr($field_data, 0, 15) . "...</div>";
								}
								$type_fields_values_text .= " <div title='".$type_field["label"]."' style='display: inline-block;'>[".safe_output($field_data)."]</div>";
							}
						}
					}
				}
				echo "$type_fields_values_text";
				
				echo '</span></td>';
				echo '<td>'.get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);
				if ($config["show_creator_incident"] == 1){	
					$id_creator_company = get_db_value ("id_company", "tusuario", "id_usuario", $incident["id_creator"]);
					if($id_creator_company != 0) {
						$company_name = (string) get_db_value ('name', 'tcompany', 'id', $id_creator_company);	
						echo " /<br/> <span>$company_name</span>";
					}
				}
				echo '</td>';
				$resolution = isset ($resolutions[$incident['resolution']]) ? $resolutions[$incident['resolution']] : __('None');

				$gold = "";
				$black = "";
				if ($incident['gold_medals']) {
					$gold = print_image('images/insignia_dorada.png', true)."(".$incident['gold_medals'].")";
				}
				if ($incident['black_medals']) {
					$black = print_image('images/insignia_gris.png', true)."(".$incident['black_medals'].")";
				}
				
				echo '<td><strong>'.$statuses[$incident['estado']].'</strong><br /><em>'.$resolution.'</em>'.$gold.$black.'</td>';

				// priority
				echo '<td>';
				print_priority_flag_image ($incident['prioridad']);
				$last_wu = get_incident_lastworkunit ($incident["id_incidencia"]);
				if ($last_wu["id_user"] == $incident["id_creator"]){
					echo "<img src='images/comment.gif' title='".$last_wu["id_user"]."'>";
				}

				echo '</td>';
				
				echo '<td>'.human_time_comparation ($incident["actualizacion"]);
			
				// Show only if it's different
				if ($incident["inicio"] != $incident["actualizacion"]){
					echo " / <br /> [". human_time_comparation ($incident["inicio"]);
					echo "]";
				}
				//echo "<br>";
				echo '<span style="display:inline-table;">';
				/*
				if (isset($config["show_user_name"]) && ($config["show_user_name"])) {
					$updated_by = get_db_value('nombre_real', 'tusuario', 'id_usuario', $last_wu["id_user"]);
				}
				else {
					$updated_by = $last_wu["id_user"];
				}
				echo "&nbsp;$updated_by";
				*/
				echo "</span>";
				echo '</td>';
				
				if ($config["show_creator_incident"] == 1){	
					echo "<td>";
					if (isset($config["show_user_name"]) && ($config["show_user_name"])) {
						$incident_creator = get_db_value('nombre_real', 'tusuario', 'id_usuario', $incident["id_creator"]);
					} else {
						$incident_creator = $incident["id_creator"];
					}

					//~ echo substr($incident_creator,0,12);
					echo "&nbsp;$incident_creator";
					echo "</td>";
				}
				
				if ($config["show_owner_incident"] == 1){	
					echo "<td>";
					if (isset($config["show_user_name"]) && ($config["show_user_name"])) {
						$incident_owner = get_db_value('nombre_real', 'tusuario', 'id_usuario', $incident["id_usuario"]);
					} else {
						$incident_owner = $incident["id_usuario"];
					}
					echo "&nbsp;$incident_owner";
					echo "</td>";
				}
				
				echo '</tr>';
			}
		}
	}
	echo "</tbody>";
	echo "</table>";
	
	if (!$report_mode) {
		pagination($count, $url, $offset, false, $aux_text);
	}

	if ($print_result_count) {
		echo "<h5>".$count.__(" ticket(s) found")."</h5>";
	}
	echo "</div>";
}

//Returns color value (hex) for incident priority

function incidents_get_priority_color($incident) {
		
	switch ($incident["prioridad"]) {
		case PRIORITY_INFORMATIVE:
			return PRIORITY_COLOR_INFORMATIVE;
			break;
		case PRIORITY_LOW:
			return PRIORITY_COLOR_LOW;
			break;
		case PRIORITY_MEDIUM:
			return PRIORITY_COLOR_MEDIUM;
			break;
		case PRIORITY_SERIOUS:
			return PRIORITY_COLOR_SERIOUS;
			break;
		case PRIORITY_VERY_SERIOUS:
			return PRIORITY_COLOR_VERY_SERIOUS;
			break;
		case PRIORITY_MAINTENANCE:
		default:
			return PRIORITY_COLOR_MAINTENANCE;
			break;
	}
}

function incidents_get_by_notified_email ($email) {
	$sql = sprintf('SELECT * FROM tincidencia WHERE email_copy LIKE "%%%s%%"', $email);
	
	$result = process_sql($sql);	
	
	return $result;
}

function incidents_get_score_table ($id_ticket) {

	$output = '';

	$output .= "<table width=98% cellpadding=4 cellspacing=4><tr><td>";
	$output .= "<img src='images/award_star_silver_1.png' width=32>&nbsp;";
	$output .= "</td><td>";
	$output .=  __('Please, help to improve the service and give us a score for the resolution of this ticket. People assigned to this ticket will not view directly your scoring.');
	$output .= "</td><td>";
	$output .= "<select id=score_ticket name=score>";
	$output .= "<option value=10>".__("Very good, excellent !")."</option>";
	$output .= "<option value=8>".__("Good, very satisfied.")."</option>";
	$output .= "<option value=6>".__("It's ok, but could be better.")."</option>";
	$output .= "<option value=5>".__("Average. Not bad, not good.")."</option>";
	$output .= "<option value=4>".__("Bad, you must to better")."</option>";
	$output .= "<option value=2>".__("Very bad")."</option>";
	$output .= "<option value=1>".__("Horrible, you need to change it.")."</option>";
	$output .= "</select>";
	$output .="</td><td>";
	$output .=print_submit_button (__('Score'), 'accion', false, 'class="sub next"', true);
	$output .= "</td></tr></table>";
	$output .= "</form>";
	
	return $output;
}

function incidents_set_tracking ($id_ticket, $action, $priority, $status, $resolution, $user, $group) {
	
	switch ($action) {
		case 'update':
			$old_incident = get_incident ($id_ticket);
	
			//Add traces and statistic information
			$tracked = false;
			if ($old_incident['prioridad'] != $priority) {
				incident_tracking ($id_ticket, INCIDENT_PRIORITY_CHANGED, $priority);
				$tracked = true;
			} 
			if ($old_incident['estado'] != $status) {
				incident_tracking ($id_ticket, INCIDENT_STATUS_CHANGED, $status);
				$tracked = true;
			}
			if ($old_incident['resolution'] != $resolution) {
				incident_tracking ($id_ticket, INCIDENT_RESOLUTION_CHANGED, $resolution);
				$tracked = true;
			}
			if ($old_incident['id_usuario'] != $user) {
				incident_tracking ($id_ticket, INCIDENT_USER_CHANGED, $user);
				$tracked = true;
			}
			if ($old_incident["id_grupo"] != $group) {
				incident_tracking ($id_ticket, INCIDENT_GROUP_CHANGED, $group);
				$tracked = true;
			}
				
			if ($tracked == false) {
				incident_tracking ($id_ticket, INCIDENT_UPDATED);
			}
			break;
		case 'create':
			incident_tracking ($id_ticket, INCIDENT_CREATED);
			
			//Add traces and statistic information
			incident_tracking ($id_ticket, INCIDENT_PRIORITY_CHANGED, $priority);
			incident_tracking ($id_ticket, INCIDENT_STATUS_CHANGED, $status);
			incident_tracking ($id_ticket, INCIDENT_RESOLUTION_CHANGED, $resolution);
			incident_tracking ($id_ticket, INCIDENT_USER_CHANGED, $user);
			incident_tracking ($id_ticket, INCIDENT_GROUP_CHANGED, $group);
			break;
	}
	
	return;
}

function incidents_get_type_fields ($search_id_incident_type) {		
	global $config;
	
	if ($search_id_incident_type) {

		$sql = sprintf("SELECT *
				FROM tincident_type_field
				WHERE id_incident_type = %d
				ORDER BY `order`", $search_id_incident_type);
	
		$config['mysql_result_type'] = MYSQL_ASSOC;
		$type_fields = get_db_all_rows_sql($sql);
		
	} else {
			
		$sql = sprintf("SELECT DISTINCT (global_id)
						FROM tincident_type_field
						WHERE global_id != 0");
						
		$sql = sprintf("SELECT * FROM tincident_type_field
						WHERE global_id = id");
						
		$config['mysql_result_type'] = MYSQL_ASSOC;				
		$type_fields = get_db_all_rows_sql($sql);

	}
	
	if (!$type_fields) {
		$type_fields = array();
	}
		
	return $type_fields;
}

function incidents_hours_to_dayminseg ($hours) {
	
	$seconds = $hours * 3600;
	$days = floor($seconds/86400);
	$hours = floor(($seconds - ($days * 86400)) / 3600);
	$minutes = floor(($seconds - ($days * 86400) - ($hours * 3600)) / 60);
	 
	$result = "";
	if ($days > 0) {
		$result .=$days."d";
	}
	if ($hours > 0) {
		$result .=$hours."h";
	}
	if ($minutes > 0) {
		$result .=$minutes."m";
	}
	
	return $result;
}

function incidents_get_status_name($id_status) {
	
	$name = get_db_value ('name', 'tincident_status', 'id', $id_status);
	return $name;
}

function incidents_get_incident_sla_graph_seconds ($id_incident) {

	$seconds = array();
	$seconds["OK"] = 0;
	$seconds["FAIL"] = 0;

	$last_value = -1;
	$last_timestamp = array();
	$last_timestamp["OK"] = 0;
	$last_timestamp["FAIL"] = 0;

	$sql = sprintf("SELECT utimestamp, value
					FROM tincident_sla_graph_data
					WHERE id_incident = %s
					ORDER BY utimestamp ASC",
					$id_incident);

	$data = get_db_all_row_by_steps_sql(true, $result_sla, $sql);
	if (isset($data)) {
		if ($data["value"] == 1) {
			$last_timestamp["OK"] = $data["utimestamp"];
		} else {
			$last_timestamp["FAIL"] = $data["utimestamp"];
		}
		$last_value = $data["value"];
	}

	while ($data = get_db_all_row_by_steps_sql(false, $result_sla, $sql)) {

		if ($data["value"] != $last_value) {
			if ($data["value"] == 1) {
				$seconds["FAIL"] += $data["utimestamp"] - $last_timestamp["FAIL"];
				$last_timestamp["OK"] = $data["utimestamp"];
			} else {
				$seconds["OK"] += $data["utimestamp"] - $last_timestamp["OK"];
				$last_timestamp["FAIL"] = $data["utimestamp"];
			}
			$last_value = $data["value"];
		}
	}

	if ($last_value == 1) {
		$seconds["OK"] += time() - $last_timestamp["OK"];
	} elseif ($last_value == 0) {
		$seconds["FAIL"] += time() - $last_timestamp["FAIL"];
	}

	return $seconds;
}

function incidents_get_sla_graph_percentages ($incidents) {

	$slas = array();

	foreach ($incidents as $incident) {
		if (!isset($incident['sla_disabled'])){
			$incident['sla_disabled'] = '';
		}
		if ($incident['sla_disabled'] != 1) {
			$seconds = incidents_get_incident_sla_graph_seconds($incident["id_incidencia"]);

			$seconds_ok = $seconds["OK"];
			$seconds_fail = $seconds["FAIL"];
			$seconds_total = $seconds_ok + $seconds_fail;

			$slas[$incident['id_incidencia']] = ($seconds_ok / $seconds_total) * 100;
		}
	}

	return $slas;
}

function incidents_get_filter_tickets_tree ($filters, $mode = false, $id_task = false) {
	global $config;
	
	// TODO: Refactor to use the function 'filter_incidents'
	
	/* Set default values if none is set */
	$filters['inverse_filter'] = isset ($filters['inverse_filter']) ? $filters['inverse_filter'] : false;
	$filters['string'] = isset ($filters['string']) ? $filters['string'] : '';
	$filters['status'] = isset ($filters['status']) ? $filters['status'] : 0;
	$filters['priority'] = isset ($filters['priority']) ? $filters['priority'] : -1;
	$filters['id_group'] = isset ($filters['id_group']) ? $filters['id_group'] : -1;
	$filters['id_company'] = isset ($filters['id_company']) ? $filters['id_company'] : 0;
	$filters['id_inventory'] = isset ($filters['id_inventory']) ? $filters['id_inventory'] : 0;
	$filters['id_incident_type'] = isset ($filters['id_incident_type']) ? $filters['id_incident_type'] : 0;
	$filters['id_user'] = isset ($filters['id_user']) ? $filters['id_user'] : '';
	$filters['id_user_or_creator'] = isset ($filters['id_user_or_creator']) ? $filters['id_user_or_creator'] : '';
	$filters['from_date'] = isset ($filters['from_date']) ? $filters['from_date'] : 0;
	$filters['first_date'] = isset ($filters['first_date']) ? $filters['first_date'] : '';
	$filters['last_date'] = isset ($filters['last_date']) ? $filters['last_date'] : '';	
	$filters['id_creator'] = isset ($filters['id_creator']) ? $filters['id_creator'] : '';
	$filters['editor'] = isset ($filters['editor']) ? $filters['editor'] : '';
	$filters['closed_by'] = isset ($filters['closed_by']) ? $filters['closed_by'] : '';
	$filters['resolution'] = isset ($filters['resolution']) ? $filters['resolution'] : '';
	$filters["offset"] = isset ($filters['offset']) ? $filters['offset'] : 0;
	$filters["group_by_project"] = isset ($filters['group_by_project']) ? $filters['group_by_project'] : 0;
	$filters["id_task"] = isset ($filters['id_task']) ? $filters['id_task'] : -1;
	$filters["sla_state"] = isset ($filters['sla_state']) ? $filters['sla_state'] : 0;
	$filters["left_sla"] = isset ($filters['left_sla']) ? $filters['left_sla'] : 0;
	$filters["right_sla"] = isset ($filters['right_sla']) ? $filters['right_sla'] : 0;
	$filters["show_hierarchy"] = isset ($filters['show_hierarchy']) ? $filters['show_hierarchy'] : 0;
	$filters["medals"] = isset ($filters['medals']) ? $filters['medals'] : 0;
	$filters["parent_name"] = isset ($filters['parent_name']) ? $filters['parent_name'] : '';
	
	///// IMPORTANT: Write an inverse filter for every new filter /////
	$is_inverse = $filters['inverse_filter'];
	
	$sql_clause = '';
	
	// Status
	if (!empty($filters['status'])) {
		// Not closed
		if ($filters['status'] == -10) {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND estado <> %d', STATUS_CLOSED);
			}
			else {
				$sql_clause .= sprintf(' AND estado = %d', STATUS_CLOSED);
			}
		}
		else {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND estado = %d', $filters['status']);
			}
			else {
				$sql_clause .= sprintf(' AND estado <> %d', $filters['status']);
			}
		}
	}
	
	// Priority
	if ($filters['priority'] != -1) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND prioridad = %d', $filters['priority']);
		}
		else {
			$sql_clause .= sprintf(' AND prioridad <> %d', $filters['priority']);
		}
	}
	
	// Group
	if ($filters['id_group'] != 1) {
		if ($filters['show_hierarchy']) {
			$children = groups_get_childrens($filters['id_group']);
			$ids = $filters['id_group'];
			foreach ($children as $child) {
				$ids .= ",".$child['id_grupo'];
			}	
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_grupo IN (%s)', $ids);
			}
			else {
				$sql_clause .= sprintf(' AND id_grupo NOT IN (%s)', $ids);
			}
		} else {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_grupo = %d', $filters['id_group']);
			}
			else {
				$sql_clause .= sprintf(' AND id_grupo <> %d', $filters['id_group']);
			}
		}

	}
	
	// User
	if (!empty($filters['id_user'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_usuario = "%s"', $filters['id_user']);
		}
		else {
			$sql_clause .= sprintf(' AND id_usuario <> "%s"', $filters['id_user']);
		}
	}
	
	// User or creator
	if (!empty($filters['id_user_or_creator'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND (id_usuario = "%s" OR id_creator = "%s")', $filters['id_user_or_creator'], $filters['id_user_or_creator']);
		}
		else {
			$sql_clause .= sprintf(' AND (id_usuario <> "%s" AND id_creator <> "%s")', $filters['id_user_or_creator'], $filters['id_user_or_creator']);
		}
	}
	
	// Resolution
	if (!empty($filters['resolution']) && $filters['resolution'] > -1) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND resolution = %d', $filters['resolution']);
		}
		else {
			$sql_clause .= sprintf(' AND resolution <> %d', $filters['resolution']);
		}
	}
	
	// Task
	if ($id_task !== false) {
		// Don't apply the task inverse filter
		$sql_clause .= sprintf(' AND id_task = %d', $id_task);
	}
	else {
		if ($filters['id_task'] > 0) {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_task = %d', $filters['id_task']);
			}
			else {
				$sql_clause .= sprintf(' AND id_task <> %d', $filters['id_task']);
			}
		}
	}
	
	// Incidents
	if (!empty($filters['id_incident_type']) && $filters['id_incident_type'] != -1) {

		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_incident_type = %d', $filters['id_incident_type']);
		}
		else {
			$sql_clause .= sprintf(' AND id_incident_type <> %d', $filters['id_incident_type']);
		}

		// Incident fields
		$incident_fields = array();
		foreach ($filters as $key => $value) {
			// If matchs an incident field, ad an element to the array with their real id and its data
			if (preg_match('/^type_field_/', $key)) {
				$incident_fields[preg_replace('/^type_field_/', '', $key)] = $value;
			}
		}
		foreach ($incident_fields as $id => $data) {
			if (!empty($data)) {
				if (!$is_inverse) {
					$sql_clause .= sprintf(' AND id_incidencia IN (SELECT id_incident
																	FROM tincident_field_data
																	WHERE id_incident_field = "%s"
																		AND data LIKE "%%%s%%")', $id, $data);
				}
				else {
					$sql_clause .= sprintf(' AND id_incidencia NOT IN (SELECT id_incident
																	FROM tincident_field_data
																	WHERE id_incident_field = "%s"
																		AND data LIKE "%%%s%%")', $id, $data);
				}
			}
		}
	}
	
	// Date
	if (!empty($filters['from_date']) && $filters['from_date'] > 0) {
		$last_date_seconds = $filters['from_date'] * 24 * 60 * 60;
		$filters['first_date'] = date('Y-m-d H:i:s', time() - $last_date_seconds);
		
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND inicio >= "%s"', $filters['first_date']);
		}
		else {
			$sql_clause .= sprintf(' AND inicio < "%s"', $filters['first_date']);
		}
	}
	else {
		if (!empty($filters['first_date']) && !empty($filters['last_date'])) {
			// 00:00:00 to set date at the beginig of the day
			$start_time = strtotime($filters['first_date']);
			$start_date = date('Y-m-d 00:00:00', $start_time);
			// 23:59:59 to set date at the end of day
			$end_time = strtotime($filters['last_date']);
			$end_date = date('Y-m-d 23:59:59', $end_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio >= "%s"', $start_date);
				$sql_clause .= sprintf(' AND inicio <= "%s"', $end_date);
			}
			else {
				$sql_clause .= sprintf(' AND (inicio < "%s" OR inicio > "%s")',
					$start_date, $end_date);
			}
		}
		else if (!empty($filters['first_date'])) {
			// 00:00:00 to set date at the beginig of the day
			$start_time = strtotime($filters['first_date']);
			$start_date = date('Y-m-d 00:00:00', $start_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio >= "%s"', $start_date);
			}
			else {
				$sql_clause .= sprintf(' AND inicio < "%s"', $start_date);
			}
		}
		else if (!empty($filters['last_date'])) {
			// 23:59:59 to set date at the end of day
			$end_time = strtotime($filters['last_date']);
			$end_date = date('Y-m-d 23:59:59', $end_time);
			
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND inicio <= "%s"', $end_date);
			}
			else {
				$sql_clause .= sprintf(' AND inicio > "%s"', $end_date);
			}
		}
	}
	
	// Creator
	if (!empty($filters['id_creator'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND id_creator = "%s"', $filters['id_creator']);
		}
		else {
			$sql_clause .= sprintf(' AND id_creator <> "%s"', $filters['id_creator']);
		}
	}
	
	// Editor
	if (!empty($filters['editor'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND editor = "%s"', $filters['editor']);
		}
		else {
			$sql_clause .= sprintf(' AND editor <> "%s"', $filters['editor']);
		}
	}
	
	// Closed by
	if (!empty($filters['closed_by'])) {
		if (!$is_inverse) {
			$sql_clause .= sprintf(' AND closed_by = "%s"', $filters['closed_by']);
		}
		else {
			$sql_clause .= sprintf(' AND closed_by <> "%s"', $filters['closed_by']);
		}
	}
	
	// SLA
	$sla_filter = '';
	if (!empty($filters['sla_state'])) {
		$sla_fired_filter = 'AND (sla_disabled = 0 AND affected_sla_id <> 0)';
		$sla_not_fired_filter = 'AND (sla_disabled = 0 AND affected_sla_id = 0)';
		
		if ($filters['sla_state'] == 1) {
			$sla_filter = (!$is_inverse) ? $sla_fired_filter : $sla_not_fired_filter;
		}
		else if ($filters['sla_state'] == 2) {
			$sla_filter = (!$is_inverse) ? $sla_not_fired_filter : $sla_fired_filter;
		}
	}
	
	// Medals
	$medals_filter = '';
	if ($filters['medals']) {
		if ($filters['medals'] == 1) {
			if (!$is_inverse) {
				$medals_filter = 'AND gold_medals <> 0';
			}
			else {
				$medals_filter = 'AND gold_medals = 0';
			}
		} else if ($filters['medals'] == 2) {
			if (!$is_inverse) {
				$medals_filter = 'AND black_medals <> 0';
			}
			else {
				$medals_filter = 'AND black_medals = 0';
			}
		}
	}
	
	if (!empty($filters['parent_name'])) {
		$inventory_id = get_db_value('id', 'tinventory', 'name', $filters['parent_name']);
		
		if ($inventory_id) {
			if (!$is_inverse) {
				$sql_clause .= sprintf(' AND id_incidencia IN (SELECT id_incident FROM tincident_inventory WHERE
					id_inventory = %d)', $inventory_id);
			}
			else {
				$sql_clause .= sprintf(' AND id_incidencia NOT IN (SELECT id_incident FROM tincident_inventory WHERE
					id_inventory = %d)', $inventory_id);
			}
		}
	}
	
	if ($no_parents) {
		$sql_clause .= ' AND id_incidencia NOT IN (SELECT id_incidencia FROM tincidencia WHERE id_parent <> 0)';
	}
	
	// Order
	if ($filters['order_by'] && !is_array($filters['order_by'])) {
		$order_by_array = json_decode(clean_output($filters['order_by']), true);
	} else {
		$order_by_array = $filters['order_by'];
	}
	
	$order_by = '';
	if ($order_by_array) {
		foreach ($order_by_array as $key => $value) {
			if ($value) {
				$order_by .= " $key $value, ";
			}
		}
	}
	
	// Use config block size if no other was given
	if ($limit && !isset($filters['limit'])) {
		$filters['limit'] = $config['block_size'];
	}
	
	// Text filter
	$text_filter = '';
	if (!empty($filters['string'])) {
		if (!$is_inverse) {
			$text_filter = sprintf('AND (
				titulo LIKE "%%%s%%" OR descripcion LIKE "%%%s%%"
				OR id_creator LIKE "%%%s%%" OR id_usuario LIKE "%%%s%%"
				OR id_incidencia = %d
				OR id_incidencia IN (
					SELECT id_incident
					FROM tincident_field_data
					WHERE data LIKE "%%%s%%"))',
				$filters['string'], $filters['string'], $filters['string'],
				$filters['string'], $filters['string'], $filters['string']);
		}
		else {
			$text_filter = sprintf('AND (
				titulo NOT LIKE "%%%s%%" AND descripcion NOT LIKE "%%%s%%"
				AND id_creator NOT LIKE "%%%s%%" AND id_usuario NOT LIKE "%%%s%%"
				AND id_incidencia <> %d
				AND id_incidencia NOT IN (
					SELECT id_incident
					FROM tincident_field_data
					WHERE data LIKE "%%%s%%"))',
				$filters['string'], $filters['string'], $filters['string'],
				$filters['string'], $filters['string'], $filters['string']);
		}
	}
	
	switch ($mode) {
		case 'count':
			//Just count items
			$sql = sprintf('SELECT COUNT(id_incidencia) FROM tincidencia FD WHERE 1=1 %s %s %s %s',
				$sql_clause, $text_filter, $sla_filter, $medals_filter);
			return (int) get_db_value_sql($sql);
		break;
		case 'tasks':
			$sql = sprintf('SELECT id_task FROM tincidencia FD WHERE 1=1 %s %s %s %s GROUP BY id_task',
				$sql_clause, $text_filter, $sla_filter, $medals_filter);
			return get_db_all_rows_sql($sql);
		break;
		case 'tickets':
		default:
			//Select all items and return all information
			$sql = sprintf('SELECT * FROM tincidencia FD WHERE 1=1 %s %s %s %s ORDER BY %s actualizacion DESC',
				$sql_clause, $text_filter, $sla_filter, $medals_filter, $order_by);
			$incidents = get_db_all_rows_sql($sql);

			if ($incidents === false) return false;
		
			$result = array();
			foreach ($incidents as $incident) {
				//Check external users ACLs
				$standalone_check = enterprise_hook('manage_standalone', array($incident, 'read'));

				if ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check) {
					continue;
				}
				else {
					// Normal ACL pass if IR for this group or if the user is the incident creator
					// or if the user is the owner or if the user has workunits
					$check_acl = enterprise_hook('incidents_check_incident_acl', array($incident));
					if (!$check_acl) {
						continue;
					}
				}
				
				$inventories = get_inventories_in_incident($incident['id_incidencia'], false);
				
				// Inventory
				if ($filters['id_inventory']) {
					$found = false;
					foreach ($inventories as $inventory) {
						if ($inventory['id'] == $filters['id_inventory']) {
							$found = true;
							break;
						}
					}
					
					if (!$is_inverse && !$found) continue;
					else if ($is_inverse && $found) continue;
				}
				
				// Company
				if ($filters['id_company']) {
					$found = false;
					$user_creator = $incident['id_creator'];
					$user_company = get_db_value('id_company', 'tusuario', 'id_usuario', $user_creator);
					
					// Don't match, dismiss incident
					if (!$is_inverse && $filters['id_company'] != $user_company) continue;
					// Match, dismiss incident
					if ($is_inverse && $filters['id_company'] == $user_company) continue;
				}
				
				// SLA
				if ($filters['left_sla']) {
					$percent_sla_incident = format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
					
					// Don't match, dismiss incident
					if (!$is_inverse && $filters['left_sla'] > $percent_sla_incident) continue;
					// Match, dismiss incident
					if ($is_inverse && $filters['left_sla'] <= $percent_sla_incident) continue;
				}
				if ($filters['right_sla']) {
					$percent_sla_incident = format_numeric (get_sla_compliance_single_id ($incident['id_incidencia']));
					
					// Don't match, dismiss incident
					if (!$is_inverse && $filters['right_sla'] < $percent_sla_incident) continue;
					// Match, dismiss incident
					if ($is_inverse && $filters['right_sla'] >= $percent_sla_incident) continue;
				}
				
				array_push ($result, $incident);
			}
			
			return $result;
			break;
	}
}

/*Filters or display result for incident search*/
function incidents_search_result_group_by_project ($filter, $ajax=false, $return_incidents = false, $print_result_count = false) {
	global $config;

	// ----------------------------------------
	// Here we print the result of the search
	// ----------------------------------------
	echo '<table width="99%" cellpadding="0" cellspacing="0" border="0px" class="listing" id="incident_search_result_table">';

	echo '<thead>';
	echo "<tr>";
	echo "<th>";
	echo print_checkbox ('incidentcb-all', "", false, true);
	echo "</th>";
	echo "<th>";
	echo __('ID');
	echo "</th>";
	echo "<th>";
	echo __('SLA');
	echo "</th>";
	echo "<th>";
	echo __('Ticket');
	echo "</th>";
	echo "<th>";
	echo __('Group')."<br><i>".__('Company')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Status')."<br><i>".__('Resolution')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Priority');
	echo "</th>";
	echo "<th style='width: 70px;'>";
	echo __('Updated')."<br><i>".__('Started')."</i>";
	echo "</th>";

	if ($config["show_creator_incident"] == 1)
		echo "<th>";
		echo __('Creator');	
		echo "</th>";
	if ($config["show_owner_incident"] == 1)
		echo "<th>";
		echo __('Owner');	
		echo "</th>";

	echo "</tr>";
	echo '</thead>';
	echo "<tbody>";

	$tasks_in_tickets = incidents_get_filter_tickets_tree($filter, 'tasks');
	if ($tasks_in_tickets === false) {
		$tasks_in_tickets = array();
	}

	$i = 0;
	$tickets_str = '';
	$add_no_project = false;
	foreach ($tasks_in_tickets as $task_ticket) {
		if ($i == 0) {
			$tickets_str = $task_ticket['id_task'];
		} else {
			$tickets_str .= ','.$task_ticket['id_task'];
		}
		if ($task_ticket['id_task'] == 0) {
			$add_no_project = true;
		}
		$i++;
	}
	
	if (!empty($tasks_in_tickets)) {
		$sql = "SELECT t1.name as n_task, t2.name as n_project, t1.id as id_task FROM ttask t1, tproject t2
			WHERE t1.id_project=t2.id AND t1.id IN ($tickets_str)
			ORDER BY t2.name";
		$tickets = get_db_all_rows_sql($sql);
	} else {
		$tickets = false;
	}
	
	if ($tickets === false) {
		$tickets = array();
	}
	if ($add_no_project) {
		$tickets[$i]['id_task'] = 0;
		$tickets[$i]['n_project'] = __('No associated project');
	}

	foreach ($tickets as $task_ticket) {
		$task = get_db_row('ttask', 'id', $task_ticket['id_task']);
		
		$img = print_image ("images/input_create.png", true, array ("style" => 'vertical-align: middle;', "id" => $img_id));
		$img_task = print_image ("images/task.png", true, array ("style" => 'vertical-align: middle;'));
		
		//tickets in task
		$tickets_in_task = incidents_get_filter_tickets_tree($filter, 'tickets', $task_ticket['id_task']);
		$count_tickets = count($tickets_in_task);
			
		//print ticket with task
		if ($task_ticket['id_task'] != 0) {
			$project_name = $task_ticket['n_project'].' - '. $task_ticket['n_task'];
			
		} else {
			$project_name = $task_ticket['n_project'];
		}
		
		//print project-task
		echo '<tr><td colspan="10" valign="top">';
		echo "
		<a onfocus='JavaScript: this.blur()' href='javascript: check_rows(\"" . $task_ticket['id_task']. "\")'>" .
		$img . "&nbsp;" . $img_task ."&nbsp;" .  safe_output($project_name)."&nbsp;" ."($count_tickets)"."</a>"."&nbsp;&nbsp;";
		echo '</td></tr>';

		foreach ($tickets_in_task as $incident) {

			$class = $task_ticket['id_task']."-task";
			$tr_status = 'class="'.$class.'"';
			if ($incident["estado"] < 3 )
				$tr_status = 'class="red_row '.$class.'"';
			elseif ($incident["estado"] < 6 )
				$tr_status = 'class="yellow_row '.$class.'"';
			else
				$tr_status = 'class="green_row '.$class.'"';

			echo '<tr '.$tr_status.' id="incident-'.$incident['id_incidencia'].'"';

			echo " style='border-bottom: 1px solid #ccc;' >";
			echo '<td>';
			print_checkbox_extended ('incidentcb-'.$incident['id_incidencia'], $incident['id_incidencia'], false, '', '', 'class="cb_incident"');
			echo '</td>';
			
			//Print incident link if not ajax, if ajax link to js funtion to replace parent
			$link = "index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_incidencia"];
			
			if ($ajax) {
				$link = "javascript:update_parent('".$incident["id_incidencia"]."')";
			}
			
			echo '<td>';
			echo '<strong><a href="'.$link.'">#'.$incident['id_incidencia'].'</a></strong></td>';
			
			// SLA Fired ?? 
			if ($incident["affected_sla_id"] != 0)
				echo '<td width="25"><img src="images/exclamation.png" /></td>';
			else
				echo '<td></td>';
			
			echo '<td>';
							
			echo '<strong><a href="'.$link.'">'.$incident['titulo'].'</a></strong><br>';
			echo "<span style='font-size:11px;font-style:italic'>";
			echo incidents_get_incident_type_text($incident["id_incidencia"]); // Added by slerena 26Ago2013
			
			$sql = sprintf("SELECT *
							FROM tincident_type_field
							WHERE id_incident_type = %d", $incident["id_incident_type"]);
			$config['mysql_result_type'] = MYSQL_ASSOC;
			$type_fields = get_db_all_rows_sql($sql);
			
			$type_fields_values_text = "";
			if ($type_fields) {
				foreach ($type_fields as $type_field) {
					if ($type_field["show_in_list"]) {
						$field_data = get_db_value_filter("data", "tincident_field_data", array ("id_incident" => $incident["id_incidencia"], "id_incident_field" => $type_field["id"]));
						if ($field_data) {
							if ($type_field["type"] == "textarea") {
								$field_data = "<div style='display: inline-block;' title='$field_data'>" . substr($field_data, 0, 15) . "...</div>";
							}
							$type_fields_values_text .= " <div title='".$type_field["label"]."' style='display: inline-block;'>[$field_data]</div>";
						}
					}
				}
			}
			echo "&nbsp;$type_fields_values_text";
			
			echo '</span></td>';
			echo '<td>'.get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);
			if ($config["show_creator_incident"] == 1){	
				$id_creator_company = get_db_value ("id_company", "tusuario", "id_usuario", $incident["id_creator"]);
				if($id_creator_company != 0) {
					$company_name = (string) get_db_value ('name', 'tcompany', 'id', $id_creator_company);	
					echo "<br><span style='font-size:11px;font-style:italic'>$company_name</span>";
				}
			}
			echo '</td>';
			$resolution = isset ($resolutions[$incident['resolution']]) ? $resolutions[$incident['resolution']] : __('None');

			echo '<td class="f9"><strong>'.$statuses[$incident['estado']].'</strong><br /><em>'.$resolution.'</em></td>';

			// priority
			echo '<td>';
			print_priority_flag_image ($incident['prioridad']);
			$last_wu = get_incident_lastworkunit ($incident["id_incidencia"]);
			if ($last_wu["id_user"] == $incident["id_creator"]){
				echo "<br><img src='images/comment.gif' title='".$last_wu["id_user"]."'>";
			}

			echo '</td>';
			
			echo '<td style="font-size:11px;">'.human_time_comparation ($incident["actualizacion"]);
		
			// Show only if it's different
			if ($incident["inicio"] != $incident["actualizacion"]){
				echo "<br><em>[". human_time_comparation ($incident["inicio"]);
				echo "]</em>";
			}
			echo "<br>";
			echo '<span style="font-size:9px;">';
			if (isset($config["show_user_name"]) && ($config["show_user_name"])) {
					$updated_by = get_db_value('nombre_real', 'tusuario', 'id_usuario', $last_wu["id_user"]);
				} else {
					$updated_by = $last_wu["id_user"];
				}
			//~ echo $last_wu["id_user"];
			echo $updated_by;
			echo "</span>";
			echo '</td>';
			
			if ($config["show_creator_incident"] == 1){	
				echo "<td class='f9'>";
				$incident_creator = $incident["id_creator"];
				echo substr($incident_creator,0,12);
				echo "</td>";
			}
			
			if ($config["show_owner_incident"] == 1){	
				echo "<td class='f9'>";
				$incident_owner = $incident["id_usuario"];
				echo substr($incident_owner,0,12);
				echo "</td>";
			}
			
			echo '</tr>';
		}
	}
	echo '</tbody>';
	echo '</table>';
}

function incidents_get_incident_childs ($id_incident, $only_name = true) {
	
	$sql = "SELECT * FROM tincidencia WHERE id_parent=".$id_incident;
	$incident_childs = get_db_all_rows_sql($sql);
	
	if ($incident_childs == false) {
		$incident_childs = array();
	}
	
	if ($only_name) {
		$childs_result = array();
		foreach ($incident_childs as $child) {
			$childs_result[$child['id_incidencia']] = $child['titulo'];
		}
		return $childs_result;
		
	} else {
		return $incident_childs;
	}	
	
}

/**
 * Print a table with statistics of a list of incidents.
 *
 * @param array List of incidents to get stats.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return Incidents stats if return parameter is true. Nothing otherwise
 */
function print_incidents_stats_simply ($incidents, $return = false, $simple_mode = false) {

    global $config;
    
	require_once ($config["homedir"]."/include/functions_graph.php");    
    
	$pdf_output = (int)get_parameter('pdf_output', 0);
	$ttl = $pdf_output+1;

	// Max graph legend string length (without the '...')
	$max_legend_strlen = 25;
	
	// Necessary for flash graphs
	include_flash_chart_script();

	// TODO: Move this function to function_graphs to encapsulate flash
	// chart script inclusion or make calls to functions_graph when want 
	// print a flash chart	

	$output = '';
	
	$total = sizeof ($incidents);
	$opened = 0;
	$total_hours = 0;
	$total_workunits = 0;
	$total_lifetime = 0;
	$max_lifetime = 0;
	$oldest_incident = false;
	$scoring_sum = 0;
	$scoring_valid = 0;

	if ($incidents === false)
		$incidents = array ();
		
		
	$assigned_users = array();
	$creator_users = array();
	
	$submitter_label = "";
	$user_assigned_label = "";
	
	$incident_id_array = array();
	
	//Initialize incident status array
	$incident_status = array();
	$incident_status[STATUS_NEW] = 0;
	$incident_status[STATUS_UNCONFIRMED] = 0;
	$incident_status[STATUS_ASSIGNED] = 0;
	$incident_status[STATUS_REOPENED] = 0;
	$incident_status[STATUS_VERIFIED] = 0;
	$incident_status[STATUS_RESOLVED] = 0;
	$incident_status[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status[STATUS_CLOSED] = 0;
	
	//Initialize priority array
	$incident_priority = array();
	$incident_priority[PRIORITY_INFORMATIVE] = 0;
	$incident_priority[PRIORITY_LOW] = 0;
	$incident_priority[PRIORITY_MEDIUM] = 0;
	$incident_priority[PRIORITY_SERIOUS] = 0;
	$incident_priority[PRIORITY_VERY_SERIOUS] = 0;
	$incident_priority[PRIORITY_MAINTENANCE] = 0;
	
	//Initialize status timing array
	$incident_status_timing = array();
	$incident_status_timing[STATUS_NEW] = 0;
	$incident_status_timing[STATUS_UNCONFIRMED] = 0;
	$incident_status_timing[STATUS_ASSIGNED] = 0;
	$incident_status_timing[STATUS_REOPENED] = 0;
	$incident_status_timing[STATUS_VERIFIED] = 0;
	$incident_status_timing[STATUS_RESOLVED] = 0;
	$incident_status_timing[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status_timing[STATUS_CLOSED] = 0;
	
	//Initialize users time array
	$users_time = array();
	
	//Initialize groups time array
	$groups_time = array();
	foreach ($incidents as $incident) {
		
		$inc_stats = incidents_get_incident_stats($incident["id_incidencia"]);
		
		if ($incident['actualizacion'] != '0000-00-00 00:00:00') {
			if(isset($inc_stats[INCIDENT_METRIC_TOTAL_TIME])){
				$lifetime = $inc_stats[INCIDENT_METRIC_TOTAL_TIME];
				if ($lifetime > $max_lifetime) {
					$oldest_incident = $incident;
					$max_lifetime = $lifetime;
				}
				$total_lifetime += $lifetime;
			}
		}
		
		//Complete incident status timing array
		if(isset($inc_stats[INCIDENT_METRIC_STATUS])){
			foreach ($inc_stats[INCIDENT_METRIC_STATUS] as $key => $value) {
				$incident_status_timing[$key] += $value;
			}
		}

		//fill users time array
		if(isset($inc_stats[INCIDENT_METRIC_USER])){
			foreach ($inc_stats[INCIDENT_METRIC_USER] as $user => $time) {
				if (!isset($users_time[$user])) {
					$users_time[$user] = $time;
				} else {
					$users_time[$user] += $time;
				}
			}
		}
		
		//Inidents by group time
		if(isset($inc_stats[INCIDENT_METRIC_GROUP])){
			foreach ($inc_stats[INCIDENT_METRIC_GROUP] as $key => $time) {
				if (!isset($groups_time[$key])) {
					$groups_time[$key] = $time;
				} else {
					$groups_time[$key] += $time;
				}
			}
		}
		
		//Get only id from incident filter array
		//used for filter in some functions
		array_push($incident_id_array, $incident['id_incidencia']);		

		// Take count of assigned / creator users 

		if (isset ($assigned_users[$incident["id_usuario"]]))
			$assigned_users[$incident["id_usuario"]]++;
		else
			$assigned_users[$incident["id_usuario"]] = 1;
			
		if (isset ($creator_users[$incident["id_creator"]]))
			$creator_users[$incident["id_creator"]]++;
		else
			$creator_users[$incident["id_creator"]] = 1;
			
			
    	// Scoring avg.
    	
        if ($incident["score"] > 0){
            $scoring_valid++;
            $scoring_sum = $scoring_sum + $incident["score"];
        }
            
		$hours = get_incident_workunit_hours ($incident['id_incidencia']);

	    $workunits = get_incident_workunits ($incident['id_incidencia']);
	  
		$total_hours += $hours;

		$total_workunits = $total_workunits + sizeof ($workunits);
		
		
		//Open incidents
		if ($incident["estado"] != 7) {
			$opened++;
		}
		
		//Incidents by status
		$incident_status[$incident["estado"]]++;
		
		//Incidents by priority
		$incident_priority[$incident["prioridad"]]++;
		
	}

	$closed = $total - $opened;
	$opened_pct = 0;
	$mean_work = 0;
	$mean_lifetime = 0;

	if ($total != 0) {
		$opened_pct = format_numeric ($opened / $total * 100);
		$mean_work = format_numeric ($total_hours / $total, 2);
	}
	
	$mean_lifetime = $total_lifetime / $total;
	
    // Get avg. scoring
    if ($scoring_valid > 0){
        $scoring_avg = format_numeric($scoring_sum / $scoring_valid);
    } else {
        $scoring_avg = "N/A";
    }

	// Get incident SLA compliance
	$sla_compliance = get_sla_compliance ($incidents);
		
	//Create second table
    	
	// Find the 5 most active users (more hours worked)
	$most_active_users = array();

	if ($incident_id_array) {
		$most_active_users = get_most_active_users (8, $incident_id_array);
	}

	$users_label = '';
	$users_data = array();
	foreach ($most_active_users as $user) {
		$users_data[$user['id_user']] = $user['worked_hours'];
	}
	
	// Remove the items with no value
	foreach ($users_data as $key => $value) {
		if (!$value || $value <= 0) {
			unset($users_data[$key]);
		}
	}

	if(empty($most_active_users) || empty($users_data)) {
		$users_label = "<div class='container_adaptor_na_graphic2'>";
		$users_label .= graphic_error(false);
		$users_label .= __("N/A");
		$users_label .="</div>";
	}
	else {
		arsort($users_data);
		$users_label .= pie3d_graph ($config['flash_charts'], $users_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}
	
	// Find the 5 most active incidents (more worked hours)
	$most_active_incidents = get_most_active_incidents (5, $incident_id_array);
	$incidents_label = '';

	foreach ($most_active_incidents as $incident) {
		$incidents_data[$incident['id_incidencia']] = $incident['worked_hours'];
	}

	// Remove the items with no value
	foreach ($incidents_data as $key => $value) {
		if (!$value || $value <= 0) {
			unset($incidents_data[$key]);
		}
	}
	
	if(empty($most_active_incidents) || empty($incidents_data)) {
		$incidents_label .= graphic_error(false);
		$incidents_label .= __("N/A");
		$incidents_label = "<div class='container_adaptor_na_graphic'>".$incidents_label."</div>";
	}
	else {
		arsort($incidents_data);
		$incidents_label .= pie3d_graph ($config['flash_charts'], $incidents_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}

	// TOP X creator users
	
	$creator_assigned_data = array();
	
	foreach ($creator_users as $clave => $valor) {
		$creator_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($creator_assigned_data)) {
		$submitter_label = "<div style='width:300px; height:150px;'>";
		$submitter_label .= graphic_error(false);
		$submitter_label .= __("N/A");
		$submitter_label .="</div>";
	}
	else {
		arsort($creator_assigned_data);
		$submitter_label .= pie3d_graph ($config['flash_charts'], $creator_assigned_data , 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}

	// TOP X scoring users
	
	$scoring_label ="";
	$top5_scoring = get_best_incident_scoring (5, $incident_id_array);
	
	foreach ($top5_scoring as $submitter){
		$scoring_data[$submitter["id_usuario"]] = $submitter["total"];
	}
	
	if(empty($top5_scoring)) {
		$scoring_label .= graphic_error(false);
		$scoring_label .= __("N/A");
		$scoring_label = "<div class='container_adaptor_na_graphic2'>".$scoring_label."</div>";
	}
	else {
		arsort($scoring_data);
		$scoring_label .= pie3d_graph ($config['flash_charts'], $scoring_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	}
	
	// TOP X assigned users
	
	$user_assigned_data = array();
	
	foreach ($assigned_users as $clave => $valor) {
		$user_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($user_assigned_data)) {
		$user_assigned_label = "<div style='width:300px; height:150px;'>";
		$user_assigned_label .= graphic_error(false);
		$user_assigned_label .= __("N/A");
		$user_assigned_label .="</div>";
	}
	else {
		arsort($user_assigned_data);
		$user_assigned_label .= pie3d_graph ($config['flash_charts'], $user_assigned_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);

	}
	
	// Show graph with incidents by group
	foreach ($incidents as $incident) {
		$grupo = safe_output(dame_grupo($incident["id_grupo"]));
		if (strlen($grupo) > $max_legend_strlen)
			$grupo = substr($grupo, 0, $max_legend_strlen) . "...";

		if (!isset( $incident_group_data[$grupo]))
			$incident_group_data[$grupo] = 0;

		$incident_group_data[$grupo] = $incident_group_data[$grupo] + 1;
	}
	arsort($incident_group_data);
	
	// Show graph with incidents by source group
	foreach ($incidents as $incident) {
		$grupo_src = safe_output(dame_grupo($incident["id_group_creator"]));
		if (strlen($grupo_src) > $max_legend_strlen)
			$grupo_src = substr($grupo_src, 0, $max_legend_strlen) . "...";
		
		if (!isset( $incident_group_data2[$grupo_src]))
			$incident_group_data2[$grupo_src] = 0;
		
		$incident_group_data2[$grupo_src] = $incident_group_data2[$grupo_src] + 1;
		
	}
	arsort($incident_group_data2);

	// Show graph with tickets open/close histogram
	$ticket_oc_graph = '<div class="pie_frame">' . graph_ticket_oc_histogram($incidents, 650, 250, $ttl) . "</div>";
	$container_title = __("Ticket Open/Close histogram");
	$container_ticket_oc = print_container_div('container_ticket_oc', $container_title, $ticket_oc_graph, 'open', true, true, "container_simple_title", "container_simple_div");

	// Show graph with tickets open/close histogram
	$ticket_activity_graph = '<div class="pie_frame">' . graph_ticket_activity_calendar($incidents) . "</div>";
	$container_title = __("Ticket activity");
	$container_ticket_activity = print_container_div('container_ticket_activity', $container_title, $ticket_activity_graph, 'open', true, true, "container_simple_title", "container_simple_div");

	//Print first table
	$output .= "<table class='listing'>";
	$output .= "<tr>";
	$output .= "<th>".__("Metric")."</th>";
	$output .= "<th>".__("Value")."</th>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><strong>".__('Total tickets')."</strong></td>";
	$output .= "<td>";
	$output .= $total;
	$output .= "</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><strong>".__('Avg. life time')."</strong></td>";
	$output .= "<td>";
	$output .= format_numeric ($mean_lifetime / 86400 , 2). " ". __("Days");
	$output .= "</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><strong>";
	$output .= __('Avg. work time');
	$output .= "</strong></td>";
	$output .= "<td>".$mean_work.' '.__('Hours')."</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><strong>";
	$output .= __('Avg. Scoring');
	$output .= "</strong></td>";
	$output .= "<td>".$scoring_avg."</td>";	
	$output .= "<tr>";
	$output .= "<td><strong>";
	$output .= __('Total work time');
	$output .= "</strong></td>";
	$output .= "<td>".$total_hours . " " . __("Hours")."</td>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td><strong>";
	$output .= __('Total work units');
	$output .= "</strong></td>";
	$output .= "<td>".$total_workunits."</td>";
	$output .= "</tr></table>";

	$container_title = __("Tickets statistics");
	$container_incident_statistics = print_container_div('container_incident_statistics', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");


	$output = "<div class='pie_frame'>".$incidents_label."</div>";
	$container_title = __("Top 5 active tickets");
	$container_top5_incidents = print_container_div('container_pie_graphs container_top5_incidents', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");

	if ($incidents) { 
		$output = graph_incident_statistics_sla_compliance($incidents, 300, 150, $ttl);    
	} else {
		$output = "<div style='width:300px; height:150px;'>";
		$output .= graphic_error(false);
		$output .= __("N/A");
		$output .="</div>";
	}
	$output = "<div class='container_adaptor_graphic'>".$output."</div>";
	$output = "<div class='pie_frame'>".$output."</div>";

	$container_title = __("SLA compliance");
	$container_sla_compliance = print_container_div('container_pie_graphs container_sla_compliance', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $status_aux = "<table class='listing' style=''>";
	$status_aux .= "<tr>";
	$status_aux .= "<th><strong>".__("Status")."</strong></th>";
	$status_aux .= "<th><strong>".__("Number")."</strong></th>";
	$status_aux .= "<th><strong>".__("Total time (AVG)")."</strong></th>";
	$status_aux .= "</tr>";
	
		foreach ($incident_status as $key => $value) {
			$name = get_db_value ('name', 'tincident_status', 'id', $key);
			$status_aux .= "<tr>";
			$status_aux .= "<td>".$name."</td>";
			$status_aux .= "<td>".$value."</td>";
			// Arithmetical average
			$time = $incident_status_timing[$key] / count($incidents);
			$status_aux .= "<td>".give_human_time($time,true,true,true)."</td>";
			$status_aux .= "</tr>";
		}
		
    $status_aux .= "</table>";

	$container_title = __("Ticket by status");
    $container_status_incidents = print_container_div('container_status_incidents', $container_title, $status_aux, 'no', true, true, "container_simple_title", "container_simple_div");  

	$priority_aux = "<table class='listing table_priority_report'>";
	
	$priority_aux .= "<tr>";
	$priority_aux .= "<th><strong>".__("Priority")."</strong></th>";
	$priority_aux .= "<th><strong>".__("Number")."</strong></th>";
	$priority_aux .= "</tr>";	
	
		foreach ($incident_priority as $key => $value) {
			$priority_aux .= "<tr>";
			$priority_aux .= "<td>".get_priority_name ($key)."</td>";
			$priority_aux .= "<td>".$value."</td>";
			$priority_aux .= "</tr>";
		}

	$priority_aux .= "</table>";


	$priority_aux = $priority_aux;

	$container_title = __("Tickets by priority");
    $container_priority_incidents = print_container_div('container_priority_incidents', $container_title, $priority_aux, 'no', true, true, "container_simple_title", "container_simple_div");  
    
	if ($oldest_incident) {
		
        $oldest_incident_time = get_incident_workunit_hours  ($oldest_incident["id_incidencia"]);
		$output = "<table class='listing'>";
		$output .= "<th>";
		$output .= __("Metric");
		$output .= "</th>";
		$output .= "<th>";
		$output .= __("Value");
		$output .= "</th>";
		$output .= "</tr>";	
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Ticket Id")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$oldest_incident['id_incidencia'].'">#'.$oldest_incident['id_incidencia']. "</strong></a>";
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Ticket title")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$oldest_incident['id_incidencia'].'">'.$oldest_incident['titulo']. "</strong></a>";				
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Worktime hours")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= $oldest_incident_time. " ". __("Hours");
		$output .= "</td>";
		$output .= "</tr>";
		$output .= "<tr>";
		$output .= "<td>";
		$output .= "<strong>".__("Lifetime")."</strong>";
		$output .= "</td>";
		$output .= "<td>";
		$output .= format_numeric($max_lifetime/86400). " ". __("Days");
		$output .= "</td>";
		$output .= "</tr>";		
		$output .= "</table>";            
	}	else  {
		
		$output = graphic_error(false);
		$output .= __("N/A");
		
	}
 
	$output_aux = "<div style='width:100%; height:170px;'>";
	$output_aux .= $output;
	$output_aux .="</div>";

	$container_title = __("Longest closed ticket");
    $container_longest_closed = print_container_div('container_longest_closed', $container_title, $output_aux, 'no', true, true, "container_simple_title", "container_simple_div");  
	
	$data = array (__('Open') => $opened, __('Closed') => $total - $opened);
	$data = array (__('Close') => $total-$opened, __('Open') => $opened);

	$output = pie3d_graph ($config['flash_charts'], $data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $ttl);
	$output = "<div class='pie_frame'>".$output."</div>";
    
	$container_title = __("Open / Close ticket");
    $container_openclose_incidents = print_container_div('container_pie_graphs container_openclose_incidents', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  
	
	$clean_output = get_parameter("clean_output");

	$container_title = __("Top active users");
	$output = "<div class='pie_frame'>".$users_label."</div>";
    $container_topactive_users = print_container_div('container_pie_graphs container_topactive_users', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $container_title = __("Top ticket submitters");
    $output = "<div class='pie_frame'>".$submitter_label."</div>";
    $container_topincident_submitter = print_container_div('container_pie_graphs container_topincident_submitter', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

    $container_title = __("Top assigned users");
    $output = "<div class='pie_frame'>".$user_assigned_label."</div>";
    $container_user_assigned = print_container_div('container_pie_graphs container_user_assigned', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

   	$container_title = __("Tickets by group");
   	$output = pie3d_graph ($config['flash_charts'], $incident_group_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
    $output = "<div class='pie_frame'>".$output."</div>";
    $container_incidents_group = print_container_div('container_pie_graphs container_incidents_group', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

   	$container_title = __("Tickets by creator group");
   	$output = pie3d_graph ($config['flash_charts'], $incident_group_data2, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
    $output = "<div class='pie_frame'>".$output."</div>";
    $container_incident_creator_group = print_container_div('container_incident_creator_group', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

	$container_title = __("Top 5 average scoring by user");
	$output = "<div class='pie_frame'>".$scoring_label."</div>";
    $container_top5_scoring = print_container('container_top5_scoring', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  

	//Print second table
	$output = "<table class='listing' style=''>";
	$output .= "<tr>";
	$output .= "<th><strong>".__("Group")."</strong></th>";
	$output .= "<th><strong>".__("Time")."</strong></th>";
	$output .= "</tr>";

	$count = 1;
	arsort($groups_time);
	
	$count_groups = 0;
	foreach ($groups_time as $key => $value) {
		//Only show first 5
		if ($count == 5) {
			break;
		}
		$output .= "<tr>";
		$group_name = get_db_value ('nombre', 'tgrupo', 'id_grupo', $key);
		$output .= "<td>".$group_name."</td>";
		$output .= "<td>".give_human_time($value,true,true,true)."</td>";
		$output .= "</tr>";
		$count++;
		$count_groups++;
	}
	if (($count_groups < 5) && ($count_groups != 0)) {
		$output .= "<tr>";
		$output .= "<td>". __('Only have ') . $count_groups . __(' group/s with tickets') ."</td>";
		$output .= "<td>" . " " . "</td>";
		$output .= "</tr>";
	}
	
	$output .= "</table>";
		
	$container_title = __("Top 5 group by time");
    $container_top5_group_time = print_container_div('container_top5_group_time', $container_title, $output, 'no', true, true, "container_simple_title", "container_simple_div");  
	
	$output ="<table class='listing' style=''>";
	
	$output .= "<tr>";
	$output .= "<th><strong>".__("User")."</strong></th>";
	$output .= "<th><strong>".__("Time")."</strong></th>";
	$output .= "</tr>";
	
	$count = 1;
	$count_users = 0;
	arsort($users_time);
	foreach ($users_time as $key => $value) {
		
		//Only show first 5
		if ($count == 5) {
			break;
		}
		
		$output .= "<tr>";
		$user_real = get_db_value ('nombre_real', 'tusuario', 'id_usuario', $key);
		$output .= "<td>".$user_real."</td>";
		$output .= "<td>".give_human_time($value,true,true,true)."</td>";
		$output .= "</tr>";
		$count++;
		$count_users++;
	}
	if (($count_users < 5) && ($count_users != 0)) {
		$output .= "<tr>";
		$output .= "<td>". __('Only have ') . $count_users . __(' user/s with tickets') ."</td>";
		$output .= "<td>" . " " . "</td>";
		$output .= "</tr>";
	}
	
	
	$output .= "</table>";
		
	$container_title = __("Top 5 users by time");
    $container_top5_user_time = print_container_div('container_top5_user_time', $container_title, $output, 'open', true, true, "container_simple_title", "container_simple_div"); 

	if ($simple_mode) {
		// First row
		echo '<div style="float: left; width: 100%;">';
		echo $container_incidents_group;
		echo $container_topincident_submitter;
		echo $container_user_assigned;
		echo "</div>";
		
		// Second row
		echo '<div style="float: left; width: 100%;">';
		echo $container_incident_statistics;
		echo $container_top5_group_time;
		echo $container_sla_compliance;
		echo "</div>";
		
	} else {
		// First row
		echo '<div style="float: left; width: 100%;">';
		echo $container_incidents_group;
		echo $container_topincident_submitter;
		echo $container_user_assigned;
		echo "</div>";
		
		// Second row
		echo '<div style="float: left; width: 100%;">';
		echo $container_incident_statistics;
		echo $container_top5_group_time;
		echo $container_sla_compliance;
		echo "</div>";
		//echo '<br><br>';
	
		// Third row
		echo '<div style="float: left; width: 100%;">';
		echo $container_status_incidents;
		echo $container_priority_incidents;
		echo "</div>";
		
		//Fourth row
		echo '<div style="float: left; width: 100%;">';
		echo $container_topactive_users;
		echo $container_top5_incidents;
		echo $container_openclose_incidents;
		echo "</div>";
		
		//~ // Fifth row
		echo $container_ticket_oc;

		//~ // Sixth row
		echo $container_ticket_activity;
	}
	
	
}

function incidents_get_sla_info ($id_group) {
	global $config;
	
	$id_sla = get_db_value('id_sla', 'tgrupo', 'id_grupo', $id_group);
	
	$sla_info = false;
	if ($id_sla != false) {
		$sla_info = get_db_row('tsla', 'id', $id_sla);
	}
	
	return $sla_info;
}

function incidents_get_all_status() {
	global $config;
	
	$sql = 'SELECT id, name FROM tincident_status';
	
	$rows = get_db_all_rows_sql ($sql);
	if ($rows == false) {
		$rows = array();
	}
	$all_status = array ();
	foreach ($rows as $row)
		$all_status[$row['id']] = __($row['name']);
		
	return $all_status;
	
}

function incidents_get_resolution_name($id_resolution) {
	
	$name = get_db_value ('name', 'tincident_resolution', 'id', $id_resolution);
	return $name;
}


?>
