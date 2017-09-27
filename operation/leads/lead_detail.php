<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
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

include_once($config['homedir'].'/include/functions_crm.php');
include_once($config['homedir'].'/include/functions_tags.php');

$section_read_permission = check_crm_acl ('lead', 'cr');
$section_write_permission = check_crm_acl ('lead', 'cw');
$section_manage_permission = check_crm_acl ('lead', 'cm');

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to the lead section");
	include ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');
$id_company = (int) get_parameter ('id_company');

if ($id || $id_company) {
	
	if ($id) {
		$read_permission = check_crm_acl ('lead', 'cr', $config['id_user'], $id);
		$write_permission = check_crm_acl ('lead', 'cw', $config['id_user'], $id);
		$manage_permission = check_crm_acl ('lead', 'cm', $config['id_user'], $id);
		if (!$read_permission && !$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead");
			include ("general/noaccess.php");
			exit;
		}
	} elseif ($id_company) {
		$read_permission = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
		$write_permission = check_crm_acl ('other', 'cw', $config['id_user'], $id_company);
		$manage_permission = check_crm_acl ('other', 'cm', $config['id_user'], $id_company);
		if (!$read_permission && !$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead");
			include ("general/noaccess.php");
			exit;
		}
	}
}

// AJAX - START
if (is_ajax()) {
	ob_clean();

	$upload_file = (bool) get_parameter('upload_file');
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

				$ds = DIRECTORY_SEPARATOR;
				$destination = $config["homedir"].$ds."attachment".$ds."tmp".$ds.$result["name"];

				if (move_uploaded_file($result["location"], $destination))
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

	$remove_tmp_file = (bool) get_parameter('remove_tmp_file');
	if ($remove_tmp_file) {
		$result = false;
		$tmp_file_location = (string) get_parameter('location');
		if ($tmp_file_location) {
			$result = unlink($tmp_file_location);
		}
		echo json_encode($result);
		return;
	}
}
// AJAX - END

$new = (bool) get_parameter ('new');
$create = (bool) get_parameter ('create');
$update = (bool) get_parameter ('update');
$delete = (bool) get_parameter ('delete');
$get = (bool) get_parameter ('get');
$close = (bool) get_parameter('close');
$make_owner = (bool) get_parameter ('make_owner');
$offset = get_parameter('offset', 0);

$search_btn = (string) get_parameter("search_btn");
$id_search = (int) get_parameter ('saved_searches');
$create_custom_search = (bool) get_parameter ('save_search');
$delete_custom_search = (bool) get_parameter ('delete_custom_search');

$massive_leads_update = (bool) get_parameter("massive_leads_update");
$total_result = array();
$num_loop = (int) get_parameter("massive_leads_num_loop");
$total_result["num_loop"] = $num_loop;

// Create
if ($create) {
	
	if ($id_company) {
		if (!$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a lead");
			require ("general/noaccess.php");
			exit;
		}
	} else {
		if (!$section_write_permission && !$section_manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a lead");
			require ("general/noaccess.php");
			exit;
		}
	}
	
	$fullname = (string) get_parameter ('fullname');
	$phone = (string) get_parameter ('phone');
	$mobile = (string) get_parameter ('mobile');
	$email = (string) get_parameter ('email');
	$position = (string) get_parameter ('position');
	$company = (string) get_parameter ('company');
	$description = (string) get_parameter ('description');	
	$country = (string) get_parameter ('country');	
	$id_language = (string) get_parameter ('id_language');
	$owner = (string) get_parameter ('owner');
	$estimated_sale = (string) get_parameter ('estimated_sale');
	$id_category = (int) get_parameter ('product');
	$progress = (string) get_parameter ('progress');
	$campaign = (int) get_parameter("campaign");
	$executive_overview = (string) get_parameter ('executive_overview');
	$date_alarm = get_parameter('alarm_date', '');
	$time_alarm = get_parameter('alarm_time', '');
	$estimated_close_date = (string) get_parameter ('estimated_close_date');
	$tags = get_parameter('tags', array());
	
	$datetime_alarm = $date_alarm.' '.$time_alarm;

	$values = array(
			'modification' => date('Y-m-d H:i:s'),
			'creation' => date('Y-m-d H:i:s'),
			'fullname' => $fullname,
			'phone' => $phone,
			'mobile' => $mobile,
			'email' => $email,
			'position' => $position,
			'id_company' => $id_company,
			'description' => $description,
			'company' => $company,
			'country' => $country,
			'id_language' => $id_language,
			'owner' => $owner,
			'estimated_sale' => $estimated_sale,
			'id_category' => $id_category,
			'progress' => $progress,
			'id_campaign' => $campaign,
			'executive_overview' => $executive_overview,
			'alarm' => $datetime_alarm,
			'estimated_close_date' => $estimated_close_date
		);
	$id = process_sql_insert('tlead', $values);

	if ($id !== false) {
		// Assign tags to the leads
		if (!empty($tags)) {
			create_lead_tag($id, $tags);
		}
		
		$values = array(
				'id_lead' => $id,
				'id_user' => $config["id_user"],
				'timestamp' => date ("Y-m-d H:i:s"),
				'description' => "Created lead"
			);
		process_sql_insert('tlead_history', $values);
		
		//create agenda entry
		if ($date_alarm != '') {
			$public = 0;
			$alarm = 60;
			$date = $date_alarm;
			if ($time_alarm != '') {
				$time = $time_alarm;
			} else {
				$time = date ('H:i');
			}
			
			$title = 'LEAD #'.$id;
			$duration = 0;
			$description = "ALARM: LEAD ".$fullname;
			$values = array(
					'public' => $public,
					'alarm' => $alarm,
					'timestamp' => $date . " " . $time,
					'id_user' => $config['id_user'],
					'title' => $title,
					'duration' => $duration,
					'description' => $description
				);
			$result = process_sql_insert('tagenda', $values);
		}
	}
	
	if ($id === false) {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		if(!isset($REMOTE_ADDR)){
			$REMOTE_ADDR = '';
		}
		audit_db ($config['id_user'], $REMOTE_ADDR, "Lead created", "Lead named '$fullname' has been added");
		$new = false;
	}

	// Clean up all inputs
	unset ($_POST);
}

// Make owner
if ($make_owner){

	if (!$write_permission && !$manage_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to make owner of a lead");
		require ("general/noaccess.php");
		exit;
	}

	$id_user = get_parameter("id_user", -1);
	if (!$id_user || $id_user == -1) {
		$id_user = $config["id_user"];
	}
	
	// Get company of current user
	$id_company = get_db_value  ('id_company', 'tusuario', 'id_usuario', $id_user);
	
	if ($id_company == false){
		$id_company = 0;
	}

	// Update lead with current user/company to take ownership of the lead.
	$values = array(
			'id_company' => $id_company,
			'owner' => $id_user,
			'modification' => date('Y-m-d H:i:s')
		);
	$where = array('id' => $id);
	$result = process_sql_update('tlead', $values, $where);

	// Add tracking info.
	$datetime =  date ("Y-m-d H:i:s");
	$sql = sprintf ('INSERT INTO tlead_history (id_lead, id_user, timestamp, description) VALUES (%d, "%s", "%s", "%s")', $id, $id_user, $datetime, "Take ownership of lead");
	process_sql ($sql);
	$make_owner = 0;
	
	if ($result && $massive_leads_update && is_ajax()) {
		$total_result['assigned'] = true;
	}
}

// Update
if ($update) { // if modified any parameter
	
	if (!$write_permission && !$manage_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a lead");
		require ("general/noaccess.php");
		exit;
	}
	
	$fullname = (string) get_parameter ('fullname');
	$phone = (string) get_parameter ('phone');
	$mobile = (string) get_parameter ('mobile');
	$email = (string) get_parameter ('email');
	$position = (string) get_parameter ('position');
	$company = (string) get_parameter ('company');
	$description = (string) get_parameter ('description');	
	$country = (string) get_parameter ('country');	
	$id_language = (string) get_parameter ('id_language');
	$owner = (string) get_parameter ('owner');
	$progress = (string) get_parameter ('progress');
	$estimated_sale = (string) get_parameter ('estimated_sale');
	$id_category = (int) get_parameter ('product');
	$id_campaign = (int) get_parameter ('campaign');
	$date_alarm = get_parameter('alarm_date', '');
	$time_alarm = get_parameter('alarm_time', '');
	$datetime_alarm = !empty($time_alarm) ? $date_alarm.' '.$time_alarm : $date_alarm;
	$executive_overview = (string) get_parameter ('executive_overview');
	$estimated_close_date = (string) get_parameter ('estimated_close_date');
	$tags = get_parameter('tags', array());

	// Detect if it's a progress change

	$old_progress = false;
	$old_estimated_close_date = false;
	$old_estimated_sale = false;
	
	$old_alarm = false;
	$old_name = false;

	$old_lead = process_sql("SELECT * FROM tlead WHERE id = $id");
	if (!empty($old_lead)) {
		$old_lead = $old_lead[0];
		$old_progress = !empty($old_lead['progress']) ? $old_lead['progress'] : false;
		$old_estimated_close_date = !empty($old_lead['estimated_close_date']) ? date('Y-m-d', strtotime($old_lead['estimated_close_date'])) : false;
		$old_estimated_sale = !empty($old_lead['estimated_sale']) ? $old_lead['estimated_sale'] : false;
		
		$old_alarm = !empty($old_lead['alarm']) ? $old_lead['alarm'] : false;
		$old_name = !empty($old_lead['fullname']) ? $old_lead['fullname'] : false;
	}

	$values = array(
			'modification' => date('Y-m-d H:i:s'),
			'description' => $description,
			'fullname' => $fullname,
			'phone' => $phone,
			'mobile' => $mobile,
			'email' => $email,
			'position' => $position,
			'id_company' => $id_company,
			'country' => $country,
			'owner' => $owner,
			'progress' => $progress,
			'id_language' => $id_language,
			'estimated_sale' => $estimated_sale,
			'company' => $company,
			'id_category' => $id_category,
			'id_campaign' => $id_campaign,
			'alarm' => $datetime_alarm,
			'executive_overview' => $executive_overview,
			'estimated_close_date' => $estimated_close_date
		);
	$where = array('id' => $id);

	$result = process_sql_update('tlead', $values, $where);
	if ($result === false) {
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	} else {
		// Assign tags to the leads
		if (!empty($tags)) {
			create_lead_tag($id, $tags);
		}
		
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		audit_db ($config['id_user'], '', "Lead updated", "Lead named '$fullname' has been updated");

		$datetime = date ("Y-m-d H:i:s");	

		$values = array(
				'id_lead' => $id,
				'id_user' => $config["id_user"],
				'timestamp' => $datetime,
				'description' => "Lead updated"
			);
		$result = process_sql_insert('tlead_history', $values);

		if (($old_progress !== false && $old_progress != $progress)
				|| ($old_estimated_close_date !== false && $old_estimated_close_date != $estimated_close_date)
				|| ($old_estimated_sale !== false && $old_estimated_sale != $estimated_sale)) {

			if ($old_progress != $progress) {
				$label = translate_lead_progress($old_progress) . " -> " . translate_lead_progress($progress);
				$values['description'] = "Lead progress updated. $label";
				$result = process_sql_insert('tlead_history', $values);
			}
			if ($old_estimated_close_date != $estimated_close_date) {
				$label = translate_lead_estimated_close_date($old_estimated_close_date) . " -> " . translate_lead_estimated_close_date($estimated_close_date);
				$values['description'] = "Lead estimated close date updated. $label";
				$result = process_sql_insert('tlead_history', $values);
			}
			if ($old_estimated_sale != $estimated_sale) {
				$label = translate_lead_estimated_sale($old_estimated_sale) . " -> " . translate_lead_estimated_sale($estimated_sale);
				$values['description'] = "Lead estimated sale updated. $label";
				$result = process_sql_insert('tlead_history', $values);
			}
		}
		
		if (!empty($old_alarm) && $old_alarm != '0000-00-00 00:00:00' && !empty($old_name) && (empty($datetime_alarm) || $datetime_alarm == '0000-00-00 00:00:00')) {

			$old_description = "ALARM: LEAD ".$old_name;
			$sql = "DELETE FROM tagenda WHERE timestamp = '$old_alarm' AND description = '$old_description'";
			$res = process_sql ($sql);
		}
		else if (!empty($date_alarm) && $date_alarm != '0000-00-00') {

			if ($time_alarm == '') {
				$time_alarm = date ('H:i');
				$datetime_alarm = $date_alarm ." ". $time_alarm;
			}

			if (!empty($old_alarm) && !empty($old_name) && $old_alarm != '0000-00-00 00:00:00') {

				$old_description = "ALARM: LEAD ".$old_name;
				$description = "ALARM: LEAD ".$fullname;

				$id_agenda = get_db_value_sql("SELECT id FROM tagenda WHERE timestamp = '$old_alarm' AND description = '$old_description'");
				$values = array(
						'timestamp' => $datetime_alarm,
						'id_user' => $config['id_user'],
						'description' => $description
					);
				process_sql_update('tagenda', $values, array('id' => $id_agenda));
			}
			else if (empty($old_alarm) || $old_alarm == '0000-00-00 00:00:00') {

				$public = 0;
				$alarm = 60;
				$date = $date_alarm;
				if ($time_alarm != '') {
					$time = $time_alarm;
				} else {
					$time = date ('H:i');
				}
				$datetime = $date.' '.$time;
				$title = 'LEAD #'.$id;
				$duration = 0;
				$description = "ALARM: LEAD ".$fullname;
				
				$values = array(
						'public' => $public,
						'alarm' => $alarm,
						'timestamp' => $date . " " . $time,
						'id_user' => $config['id_user'],
						'title' => $title,
						'duration' => $duration,
						'description' => $description
					);
				$result = process_sql_insert('tagenda', $values);
			}
			
		}
	}
}

// Close
if ($close) {

	if (!$write_permission && !$manage_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to close a lead");
		require ("general/noaccess.php");
		exit;
	}

	$values = array('progress' => 100);
	$where = array('id' => $id);
	$result = process_sql_update('tlead', $values, $where);

	if ($result > 0) {
		$values = array(
				'id_lead' => $id,
				'id_user' => $config["id_user"],
				'timestamp' => date ("Y-m-d H:i:s"),
				'description' => "Lead closed"
			);
		process_sql_insert('tlead_history', $values);

		echo ui_print_success_message (__('Successfully closed'), '', true, 'h3', true);
		$id = 0;

		if ($massive_leads_update && is_ajax()) {
			$total_result['closed'] = true;
		}
	}
}

// Delete
if ($delete) {
	
	if (!$write_permission && !$manage_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete a lead");
		require ("general/noaccess.php");
		exit;
	}

	//check if lead exists
	$exists = get_db_value  ('id', 'tlead', 'id', $id);

	if (!$exists) {
		echo ui_print_error_message (__('Error deleting lead'), '', true, 'h3', true);
		$id = 0; // Force go listing page.
	} else {
		$fullname = get_db_value  ('fullname', 'tlead', 'id', $id);
		$sql = sprintf ('DELETE FROM tlead WHERE id = %d', $id);
		process_sql ($sql);
		audit_db ($config['id_user'], $REMOTE_ADDR, "Lead deleted", "Lead named '$fullname' has been deleted");

		$sql = sprintf ('DELETE FROM tlead_activity WHERE id_lead = %d', $id);
		process_sql ($sql);

		$sql = sprintf ('DELETE FROM tlead_history WHERE id_lead = %d', $id);
		process_sql ($sql);

		$sql = sprintf ('SELECT id FROM tlead WHERE id = %d', $id);
		$result = process_sql ($sql);
		if (!$result) {
			echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
			$id = 0; // Force go listing page.

			if ($massive_leads_update && is_ajax()) {
				$total_result['deleted'] = true;
			}
		}
	}
}

if (is_ajax() && $massive_leads_update) {
	ob_clean();
	echo json_encode($total_result);
	return;
}

// Filter for custom search
$filter = array ();
$filter['search_text'] = (string) get_parameter ('search_text');
$filter['id_company'] = (int) get_parameter ('id_company_search');
$filter['last_date'] = (int) get_parameter ('last_date_search');
$filter['start_date'] = (string) get_parameter ('start_date_search');
$filter['end_date'] = (string) get_parameter ('end_date_search');
$filter['country'] = (string) get_parameter ('country_search', "");
$filter['id_category'] = (int) get_parameter ('product');
$filter['progress'] = (int) get_parameter ('progress_search');
$filter['progress_major_than'] = (int) get_parameter ('progress_major_than_search');
$filter['progress_minor_than'] = (int) get_parameter ('progress_minor_than_search');
$filter['owner'] = (string) get_parameter ("owner_search");
$filter['show_100'] = (int) get_parameter ("show_100_search");
$filter['id_language'] = (string) get_parameter ("id_language", "");
$filter['est_sale'] = (int) get_parameter ("est_sale_search", 0);
$filter['show_not_owned'] = (int) get_parameter ("show_not_owned_search");
$filter['tags'] = get_parameter('tags', array());

/* Create a custom saved search*/
if ($create_custom_search && !$id_search) {
	
	$search_name = (string) get_parameter ('search_name');

	$duped_name = exists_custom_search_name($search_name);
	
	if (!$duped_name) {
		$result = create_custom_search ($search_name, 'leads', $filter);
			if ($result === false) {
			echo ui_print_error_message (__('Could not create custom search'), '', true, 'h3', true);
		}
		else {
			echo ui_print_success_message (__('Custom search saved'), '', true, 'h3', true);
		}
	}
	else {
		echo ui_print_error_message (__('This name already exist'), '', true, 'h3', true);
	}
}

/* Get a custom search*/
if ($id_search && !$delete_custom_search) {
	
	$search = get_custom_search ($id_search, 'leads');
	
	if ($search) { 
		
		if ($search["form_values"]) {
			
			$filter = unserialize($search["form_values"]);
			echo ui_print_success_message (sprintf(__('Custom search "%s" loaded'), $search["name"]), '', true, 'h3', true);
		}
		else {
			echo ui_print_error_message (sprintf(__('Could not load "%s" custom search'), $search["name"]), '', true, 'h3', true);	
		}
	}
	else {
		echo ui_print_error_message (__('Could not load custom search'), '', true, 'h3', true);
	}
}

/* Delete a custom saved search */
if ($id_search && $delete_custom_search) {
	
	$sql = sprintf ('DELETE FROM tcustom_search
		WHERE id_user = "%s"
		AND id = %d',
		$config['id_user'], $id_search);
	$result = process_sql ($sql);
	if ($result === false) {
		echo ui_print_error_message (__('Could not delete custom search'), '', true, 'h3', true);
	}
	else {
		$id_search = false;
		echo ui_print_success_message (__('Custom search deleted'), '', true, 'h3', true);
	}
}

// FORM (Update / Create)
if ($id || $new) {
	if ($new) {
		
		if (!$section_write_permission && !$section_manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a lead");
			require ("general/noaccess.php");
			exit;
		}
		
		$id = 0;

		$id_company = (int) get_parameter ('id_company');
		$fullname = (string) get_parameter ('fullname');
		$phone = (string) get_parameter ('phone');
		$mobile = (string) get_parameter ('mobile');
		$email = (string) get_parameter ('email');
		$position = (string) get_parameter ('position');
		$company = (string) get_parameter ('company');
		$description = (string) get_parameter ('description');	
		$country = (string) get_parameter ('country');	
		$id_language = (string) get_parameter ('id_language');
		$owner = (string) get_parameter ('owner');
		$progress = (string) get_parameter ('progress');
		$estimated_sale = (string) get_parameter ('estimated_sale');
		$id_category = (int) get_parameter ('product');
		$campaign = (int) get_parameter ("campaign");
		$executive_overview = (string)get_parameter('executive_overview');
		$estimated_close_date = (string) get_parameter ('estimated_close_date');
		$alarm_date = get_parameter('alarm_date');
		$alarm_time = get_parameter('alarm_time');
		$tags = get_parameter('tags', array());

	} else {
		
		clean_cache_db();
		$lead = get_db_row ("tlead", "id", $id);
		$id_company = $lead['id_company'];
		$fullname = $lead['fullname'];
		$phone = $lead['phone'];
		$mobile = $lead['mobile'];
		$email = $lead['email'];
		$position = $lead['position'];
		$company = $lead['company'];
		$description = $lead['description'];	
		$country = $lead['country'];	
		$id_language = $lead['id_language'];
		$owner = $lead['owner'];
		$progress = $lead['progress'];
		$estimated_sale = $lead['estimated_sale'];
		$creation = $lead["creation"];
		$modification = $lead["modification"];
		$id_category = $lead["id_category"];
		$campaign = $lead["id_campaign"];
		$executive_overview = $lead['executive_overview'];
		$alarm = $lead['alarm'];
		$alarm = explode(' ', $alarm);
		$alarm_date = $alarm[0];
		$alarm_time = $alarm[1];
		$estimated_close_date = $lead['estimated_close_date'];
		$tags = get_lead_tag_ids($lead['id']);
	}
	
	// Show tabs
	if ($id) {

		$op = get_parameter ("op", "");
		echo '<h2>';
		switch ($op) {
			case "activity":
				echo strtoupper(__('Activity'));
				break;
			case "history":
				echo strtoupper(__('Tracking'));
				break;
			case "mail":
				echo strtoupper(__('Mail reply'));
				break;
			case "files":
				echo strtoupper(__('Files'));
				break;
			case "forward":
				echo strtoupper(__('Forward lead'));
				break;
			default:
				echo strtoupper(__('Lead details'));
		}
		echo '</h2>';
		
		$name = get_db_value ('fullname', 'tlead', 'id', $id);
		echo '<h4>' . sprintf(__('Lead #%s: %s'), $id, $name);
		echo integria_help ("lead", true);
		echo '<ul style="height: 30px;" class="ui-tabs-nav">';
		if ($op == "files")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'&op=files" title="'.__("Files").'"><img src="images/disk.png"/></a></li>';
		
		echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id_company.'" title="'.__("Company").'"><img src="images/groups_small/house.png"/></a></li>';
		
		if ($op == "forward")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'&op=forward" title="'.__("Forward lead").'"><img src="images/email_dark.png"/></a></li>';
		
		// Show mail tab only on owned leads
		$lead_owner = get_db_value ("owner", "tlead", "id", $id);

		if ($lead_owner == $config["id_user"] || dame_admin($config["id_user"])){
			if ($op == "mail")
				echo '<li class="ui-tabs-selected">';
			else
				echo '<li class="ui-tabs">';
			echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'&op=mail" title="'.__("Mail reply").'"><img src="images/email_edit.png"/></a></li>';
		}
		
		if ($op == "history")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'&op=history" title="'.__("Tracking").'"><img src="images/list_view.png"/></a></li>';

		if ($op == "activity")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'&op=activity" title="'.__("Activity").'"><img src="images/eye.png"/></a></li>';
		
		if ($op == "")
			echo '<li class="ui-tabs-selected">';
		else
			echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&id='.$id.'" title="'.__("Lead details").'"><img src="images/leads_tab.png"/></a></li>';

		echo '<li class="ui-tabs">';
		echo '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search" title="'.__("Back to list").'"><img src="images/volver_listado.png"/></a></li>';
				
		echo '</ul>';
		echo '</h4>';
		
	}
	if(!isset($op)){
		$op ='';
	}
	switch ($op) {
		case "activity":
			// Load tab activity
			include "lead_activity.php";
			return;
		case "history":
			// Load tab history/tracking
			include "lead_history.php";
			return;
		case "mail":
			// Load tab mail
			include "lead_mail.php";
			return;
		case "files":
			// Load tab files
			include ("lead_files.php");
			return;
		case "forward":
			// Load tab forward
			include "lead_forward.php";
			return;
	}
	$table = new stdClass();
	$table->width = "100%";
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan['tags'][0] = 4;
	$table->colspan[10][0] = 4;
	
	if ($section_write_permission || $section_manage_permission) {
		
		$table->class = "search-table-button";
		
		if ($id == 0) {
			echo "<h2>".__('Leads management')."</h2>";
			echo "<h4>".__('Create lead');
			echo integria_help ("lead", true);
			echo "<div id='button-bar-title'><ul>";
				echo "<li><a href='index.php?sec=customers&sec2=operation/leads/lead'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
			echo "</ul></div>";
			echo "</h4>";
		}

		$campaigns = crm_get_campaigns_combo_list();

		$table->data[0][0] = print_checkbox ("duplicated_leads", 0, false, true, __('Allow duplicated leads'));

		$table->data[0][1] = print_select ($campaigns, 'campaign', $campaign, 0, __("None"), 0, true, 0, false, __('Campaign') );			

		$table->data[1][0] = print_input_text ("fullname", $fullname, "", 60, 100, true, __('Full name'));
		$table->data[1][1] = print_input_text ("company", $company, "", 60, 100, true, __('Company name'));
		$table->data[2][0] = print_input_text ("email", $email, "", 18, 100, true, __('Email'));
		$table->data[2][1] = print_input_text ("country", $country, "", 18, 100, true, __('Country'));
		$table->data[3][0] = print_input_text ("estimated_sale", $estimated_sale, "", 18, 100, true, __('Estimated sale'));
		$table->data[3][0] .= print_help_tip (__("Use only integer values, p.e: 23000 instead 23,000 or 23,000.00"), true);

		$estimated_close_date = !empty($estimated_close_date) && $estimated_close_date != '0000-00-00 00:00:00' ? date("Y-m-d", strtotime($estimated_close_date)) : "";
		$table->data[3][1] = print_input_text ("estimated_close_date", $estimated_close_date, "", 18, 20, true, __('Estimated close date'));

		$table->data[4][0] = print_input_text ("phone", $phone, "", 18, 60, true, __('Phone number'));

		$progress_values = lead_progress_array ();

		$table->data[4][1] = print_select ($progress_values, 'progress', $progress, '', '', 0, true, 0, false, __('Lead progress') );

		$table->data[5][0] = print_input_text ('position', $position, '', 18, 50, true, __('Position'));

		$table->data[5][1] = print_input_text ("mobile", $mobile, "", 18, 60, true, __('Mobile number'));
		
		$table->data[6][0] = print_input_text_extended ('owner', $owner, 'text-user', '', 18, 30, false, '',
			array(), true, '', __("Owner") )
		. print_help_tip (__("Type at least two characters to search"), true);

		// Show delete control if its owned by the user
		if ($id && ( ($config["id_user"] == $owner) || dame_admin($config["id_user"]) ) ){
			$table->data[6][0] .= " <a title='".__('Delete this lead')."' href='#' onClick='javascript: show_validation_delete(\"delete_lead\",".$id.",0,".$offset.");'><img src='images/cross.png'></a>";
		}

		// Show "close" control if it's owned by the user
		if ($progress < 100 && $id && ( ($config["id_user"] == $owner) || dame_admin($config["id_user"]) ) ) {
			$table->data[6][0] .= " <a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".
			$id."&close=1'><img src='images/lock.png' title='".__("Close this lead")."'></a>";
		}
		
		// Show take control is owned by nobody
		if (($owner == "" || dame_admin($config["id_user"])) && $progress < 100 && $id) {
			$table->data[6][0] .= " <a title='".__('Take control')."'
				href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=$id&make_owner=1'>
				<img src='images/award_star_silver_1.png'></a>";
		}
		
		if(!isset($creation)){
			$creation = '';
		}
		if(!isset($modification)){
			$modification = '';
		}
		$languages = crm_get_all_languages();
		$table->data[6][1] = print_select ($languages, 'id_language', $id_language, '', __('Select'), '', true, 0, true,  __('Language'));
		
		$table->data[7][0] = "<b>". __("Creation / Last update"). "</b><br><span style='font-size: 10px'>";
		$table->data[7][0] .=  "$creation / $modification </span>";

		$params = array();
		$params['input_id'] = 'id_company';
		$params['input_name'] = 'id_company';
		$params['input_value'] = $id_company;
		$params['title'] = __('Managed by');
		$params['return'] = true;
		$params['attributes'] = "style='width:210px;'";
		$table->data[7][1] = print_company_autocomplete_input($params);

		if ($id_company) {
			$table->data[7][1] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
			$table->data[7][1] .= "<img src='images/company.png'></a>";
		}

		$table->data[8][0] = print_input_text ("executive_overview", $executive_overview, "", 60, 100, true, __('Executive overview'));
		
		$table->data[8][1] = combo_kb_products ($id_category, true, 'Product type', true);

		$table->data[9][0] = "<div style=\"display: inline-block;\">" . print_input_text ("alarm_date", $alarm_date, "", 15, 20, true, __('Alarm - date')) . "</div>&nbsp;";
		$table->data[9][0] .= "<div style=\"display: inline-block;\">" . print_input_text ("alarm_time", $alarm_time, "", 15, 20, true, __('Alarm - time')) . "</div>";
		$table->data[9][0] .= '&nbsp;'.print_image("images/cross.png", true, array("onclick" => "cleanAlarm()"));
		
		// Tags
		$tag_editor_props = array('name' => 'tags', 'selected_tags' => $tags);
		$table->data['tags'][0] = print_label (__('Tags'), '', 'select', true);
		$table->data['tags'][0] .= html_render_tags_editor($tag_editor_props, true);
		
		$table->data[10][0] = print_textarea ("description", 10, 1, $description, '', true, __('Description'));
		
	}
	else {
		
		$table->class = "search-table";
		
		if($fullname == '') {
			$fullname = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[0][0] = "<b>".__('Full name')."</b><br>$fullname<br>";
		if($email == '') {
			$email = '<i>-'.__('Empty').'-</i>';
		}		

		$table->data[0][1] = "<b>".__('Company')."</b><br>$company<br>";

		$table->data[1][0] = "<b>".__('Email')."</b><br>$email<br>";
		if($phone == '') {
			$phone = '<i>-'.__('Empty').'-</i>';
		}		

		$table->data[1][1] = "<b>".__('Country')."</b><br>$country<br>";

		$table->data[2][0] = "<b>".__('Est. Sale')."</b><br>$estimated_sale<br>";
				
		$table->data[3][0] = "<b>".__('Phone number')."</b><br>$phone<br>";

		$table->data[3][1] = "<b>".__('Mobile number')."</b><br>$mobile<br>";
		
		if($position == '') {
			$position = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[4][0] = "<b>".__('Position')."</b><br>$position<br>";
		
		$company_name = get_db_value('name','tcompany','id',$id_company);

		$table->data[4][1] = "<b>".__('Company')."</b><br>$company_name";
			
		$table->data[4][1] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
		$table->data[4][1] .= "<img src='images/company.png'></a>";

		$table->data[5][0] = "<b>".__('Owner')."</b><br>$owner<br>";
		$table->data[5][1] = "<b>".__('Language')."</b><br>$id_language<br>";
	
		if($description == '') {
			$description = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[6][0] = "<b>".__('Description')."</b><br>$description<br>";
		
		// Tags view
		if ($id) {
			$full_tags = get_lead_tags($id, array(TAGS_TABLE_ID_COL => $tags));
			
			if (!empty($full_tags))
				$table->data['tags'][0] = html_render_tags_view($full_tags, true);
		}
	}
	
	echo '<form method="post" id="lead_form">';
	print_table ($table);
	if ($section_write_permission || $section_manage_permission) {
		echo "<div style='text-align:right; width:100%'>";
			unset($table->data);
			$table->class = "button-form";
			if ($id) {
				$button = print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
				$button .= print_input_hidden ('update', 1, true);
				$button .= print_input_hidden ('id', $id, true);
			} else {
				$button = print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
				$button .= print_input_hidden ('create', 1, true);
			}
			$table->data[1][0] = $button;
			print_table ($table);
		echo "</div>";
	}
	echo "</form>";

}
else {
	
	//FORM AND TABLE TO MANAGE CUSTOM SEARCHES
	$table = new stdClass;
	$table->id = 'saved_searches_table';
	$table->width = '99%';
	$table->class = 'search-table-button';
	$table->size = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[1] = 'font-weight: bold';
	$table->data = array ();
	$sql = sprintf ('SELECT id, name FROM tcustom_search
					 WHERE id_user = "%s"
						 AND section = "leads"
					 ORDER BY name',
					 $config['id_user']);
	$table->data[0][0] = print_select_from_sql ($sql, 'saved_searches', $id_search, '', __('None'), 0, true, false, true, __('Custom searchs'));

	//If a custom search was selected display cross
	if ($id_search) {
		$table->data[0][0] .= '<a href="index.php?sec=customers&sec2=operation/leads/lead&tab=search&delete_custom_search=1&saved_searches='.$id_search.'">';
		$table->data[0][0] .= '<img src="images/cross.png" title="' . __('Delete') . '"/></a>';
	} else {
		$table->data[0][1] = print_input_text ('search_name', '', '', 40, 60, true, __('Save current search'));
		$table->data[0][2] = print_submit_button (__('Save'), 'save_search', false, 'class="sub save" style="margin-top: 13px;"', true);
	}

	echo '<div id="custom_search" style="display: none;">';
	echo '<form id="form-saved_searches" method="post" action="index.php?sec=customers&sec2=operation/leads/lead&tab=search">';
	foreach ($filter as $key => $value) {
		if ($key == "search_text") {
			print_input_hidden ("search_text", $value, false, '', false, "filter-search_text");
		}
		elseif ($key == "id_language") {
			print_input_hidden ("id_language", $value, false, '', false, "filter-id_language");
		}
		elseif ($key == "id_category") {
			print_input_hidden ("product", $value, false, '', false, "filter-product");
		}
		else if ($key == "tags") {
			foreach ($value as $val) {
				print_input_hidden ("tags[]", $val, false, '', false, "filter-tags");
			}
		}
		else {
			print_input_hidden ($key."_search", $value, false, '', false, "filter-".$key."_search");
		}
	}
	print_table ($table);
	echo '</form>';
	echo '</div>';
	
	if ($id_search) {
		$search_text = $filter['search_text'];
		$id_company = $filter['id_company'];
		$last_date = $filter['last_date'];
		$start_date = $filter['start_date'];
		$end_date = $filter['end_date'];
		$country = $filter['country'];
		$id_category = $filter['id_category'];
		$progress = $filter['progress'];
		$progress_major_than = $filter['progress_major_than'];
		$progress_minor_than = $filter['progress_minor_than'];
		$owner = $filter['owner'];
		$show_100 = $filter['show_100'];
		$id_language = $filter['id_language'];
		$est_sale = $filter['est_sale'];
		$show_not_owned = $filter['show_not_owned'];
		$tags = $filter['tags'];
	} else {
		$search_text = (string) get_parameter ('search_text');
		$id_company = (int) get_parameter ('id_company_search');
		$last_date = (int) get_parameter ('last_date_search');
		$start_date = (string) get_parameter ('start_date_search');
		$end_date = (string) get_parameter ('end_date_search');
		$country = (string) get_parameter ('country_search');
		$id_category = (int) get_parameter ('product');
		$progress = (int) get_parameter ('progress_search');
		$progress_major_than = (int) get_parameter ('progress_major_than_search', -1);
		$progress_minor_than = (int) get_parameter ('progress_minor_than_search', -1);
		$owner = (string) get_parameter ("owner_search");
		$show_100 = (int) get_parameter ("show_100_search");
		$id_language = (string) get_parameter ("id_language", "");
		$est_sale = (int) get_parameter ("est_sale_search", 0);
		$show_not_owned = (int) get_parameter ("show_not_owned_search");
		$tags = get_parameter('tags', array());
	}

	$search_params = "&est_sale_search=$est_sale&id_language_search=$id_language&search_text=$search_text&id_company_search=$id_company&last_date_search=$last_date&start_date_search=$start_date&end_date_search=$end_date&country_search=$country&product=$id_category&progress_search=$progress&progress_minor_than_search=$progress_minor_than&progress_major_than_search=$progress_major_than&show_100_search=$show_100&owner_search=$owner&show_not_owned_search=$show_not_owned";
	
	if (!empty($tags)) {
		$search_params .= '&tags[]='.implode('&tags[]=', $tags);
	}
	
	$where_group = "";

	if ($show_100){
		$where_clause = "WHERE 1=1 $where_group ";
	} else {
		$where_clause = "WHERE progress < 100 $where_group ";
	}

	if ($est_sale != ""){
		$where_clause .= " AND estimated_sale >= $est_sale ";
	}

	if ($id_language != ""){
		$where_clause .= " AND id_language = '$id_language' ";
	}

	if ($show_not_owned) {
		$where_clause .= " AND owner = '' ";
	}
	
	if ($owner != ""){
		$where_clause .= sprintf (' AND owner =  "%s"', $owner);
	}
	
	if ($search_text != "") {
		$where_clause .= ' AND (';
		if (is_int((int)$search_text) && (int)$search_text > 0) {
			$where_clause .= sprintf ('id = %d OR ', (int)$search_text);
		}
		$where_clause .= sprintf ('fullname LIKE "%%%s%%" OR description LIKE "%%%s%%" OR company LIKE "%%%s%%" or email LIKE "%%%s%%"', $search_text, $search_text, $search_text, $search_text);
		$where_clause .= ')';
	}

	if ($id_company) {
		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}

	// last_date is in days
	if ($last_date) {
		$last_date_seconds = $last_date * 24 * 60 * 60;
		$start_date = date('Y-m-d H:i:s', time() - $last_date_seconds);
		//$end_date = date('Y-m-d H:i:s');
		$end_date = "";
	}

	if ($start_date) {
		$where_clause .= sprintf (' AND creation >= "%s"', $start_date);
	}

	if ($end_date) {
		$where_clause .= sprintf (' AND creation <= "%s"', $end_date);
	}

	if ($country) {
		$where_clause .= sprintf (' AND country LIKE "%%%s%%"', $country);
	}

	if ($progress > 0) {
		$where_clause .= sprintf (' AND progress = %d ', $progress);
	}

	if ($progress_minor_than > 0) {
		$where_clause .= sprintf (' AND progress <= %d ', $progress_minor_than);
	}

	if ($progress_major_than > 0) {
		$where_clause .= sprintf (' AND progress >= %d ', $progress_major_than);
	}

	if ($id_category) {
		$where_clause .= sprintf(' AND id_category = %d ', $id_category);
	}
	
	// Tags filter
	if (!empty($tags)) {
		$lead_ids = get_leads_with_tags(array(TAGS_TABLE_ID_COL => $tags));
		
		// Some leads
		if (!empty($lead_ids) && is_array($lead_ids))
			$where_clause .= sprintf(' AND id IN (%s) ', implode(',', $lead_ids));
		// None lead found
		else
			$where_clause .= ' AND id IN (-1) ';
	}
	
	$form = '<form id="lead_stats_form" action="index.php?sec=customers&sec2=operation/leads/lead&tab=search" method="post">';		

	$table->class = 'search-table-button';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->width = "100%";
	$table->colspan = array();
	$table->colspan['tags'][0] = 4;

	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 21, 100, true, __('Search'));
	
	$table->data[0][1] = print_input_text_extended ('owner_search', $owner, 'text-user', '', 21, 30, false, '',
			array(), true, '', __('Owner'). print_help_tip (__("Type at least two characters to search"), true));

	$table->data[0][2] =  print_checkbox ("show_100_search", 1, $show_100, true, __("Show finished leads"));


	$table->data[1][0] = print_input_text ("country_search", $country, "", 21, 100, true, __('Country'));

	$table->data[1][1] = print_input_text ("est_sale_search", $est_sale, "", 21, 100, true, __('Estimated Sale'));
	
	$table->data[1][2] =  print_checkbox ("show_not_owned_search", 1, $show_not_owned, true, __("Show not owned leads"));

	
	$tag_editor_props = array('name' => 'tags', 'selected_tags' => $tags);
	$table->data['tags'][0] = print_label (__('Tags'), '', 'select', true);
	$table->data['tags'][0] .= html_render_tags_editor($tag_editor_props, true); 
	
	//~ $table_advanced->class = 'search-table';
	//~ $table_advanced->style = array ();
	//~ $table_advanced->style[0] = 'font-weight: bold;';
	//~ $table_advanced->data = array ();
	//~ $table_advanced->width = "99%";
	//~ $table_advanced->colspan = array();
	//~ $table_advanced->colspan[1][1] = 2;
	$table_advanced = '<tr>';
	$params = array();
	$params['input_id'] = 'id_company_search';
	$params['input_name'] = 'id_company_search';
	$params['input_value'] = $id_company;
	$params['title'] = __('Managed by');
	$params['return'] = true;
	$table_advanced .= '<td>';
	$table_advanced .= print_company_autocomplete_input($params);
	$table_advanced .= '</td><td>';
	$table_advanced .= combo_kb_products ($id_category, true, 'Product type', true);
	$table_advanced .= '</td>';
	$table_advanced .= "<td rowspan=2 valign=top>".get_last_date_control ($last_date, 'last_date_search', __('Date'), $start_date, 'start_date_search', __('Start date'), $end_date, 'end_date_search', __('End date'));
	$table_advanced .= '</td>';
	$table_advanced .= '<tr><td>';
	$progress_values = lead_progress_array ();
	$table_advanced .= print_select ($progress_values, 'progress_search', $progress, '', __('Any'), 0, true, 0, false, __('Lead progress') );
	$table_advanced .= '</td><td>';
	$table_advanced .= print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name',
		'id_language', $id_language, '', __('Any'), '', true, false, false, __('Language'));
	$table_advanced .= '<tr>';
	
	$table->data['advanced'][2] = print_container('lead_search_advanced', __('Advanced search'), $table_advanced, 'closed', true, false,'','no_border',3);
	$table->colspan['advanced'][2] = 4;
	// Delete new lines from the string
	$where_clause = str_replace(array("\r", "\n"), '', $where_clause);
	
	$form .= "<div class='divresult_left'>";
		$form .= print_table ($table,true);
	$form .= '</div>';
	$table->data = array ();
	
	$form .= "<div class='divform_right'>";
		$form .= "<div class='button-form'><ul>";
			$form .= '<li>' . print_button(__('Export to CSV'), '', false, 
				'window.open(\'include/export_csv.php?export_csv_leads=1&where_clause=' . 
					str_replace('"', "\'", $where_clause) . '\')', 'class="sub csv"', true) . '</li>';
			$form .='<li>' . print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true). '</li>';
		$form .= '</ul></div>';
	$form .= '</div>';
	$form .= '</form>';
	
	print_container_div("lead_form",__("Leads form search"),$form, 'open', false, false);
		
	$leads = crm_get_all_leads ($where_clause);

	if ($leads == false) {
		$count_total_leads = 0;
	} else {
		$count_total_leads = count($leads);
	}

	if ($leads !== false) {
		$leads = print_array_pagination ($leads, "index.php?sec=customers&sec2=operation/leads/lead&tab=search$search_params", $offset);
		unset ($table);
		
		$table = new stdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->rowstyle = array ();

		$table->style[0] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = print_checkbox ('leadcb-all', "", false, true);
		$table->head[1] = __('#');
		$table->head[2] = __('Product');
		$table->head[3] = __('Full name');
		$table->head[4] = __('Managed by');
		$table->head[5] = __('Progress');
		$table->head[6] = __('Est. Sale');
		$table->head[7] = __('L.');
		$table->head[8] = __('Country');
		$table->head[9] = __('Created')."<br>".__('Updated');
		$table->head[10] = __('Op.');
		$table->size[6] = '80px;';
		$table->size[5] = '130px;';
		$table->size[10] = '40px;';

		$lead_warning_time = $config["lead_warning_time"] * 86400;
		
		foreach ($leads as $lead) {
			$data = array ();
			
			// Detect is the lead is pretty old 
			// Stored in $config["lead_warning_time"] in days, need to calc in secs for this
			if (calendar_time_diff ($lead["modification"]) > $lead_warning_time ){
				$table->rowclass[] = "red_row";
			} else {
				$table->rowclass[] = "";
			}

			$data[0] = print_checkbox_extended ('leadcb-'.$lead['id'], $lead['id'], false, '', '', 'class="cb_lead"', true);

			$data[1] = "<b><a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".
				$lead['id']."'>#".$lead['id']."</a></b>";


			$data[2] = print_product_icon ($lead['id_category'], true);

			if ($lead['executive_overview'] != '') {
				$overview = print_help_tip ($lead['executive_overview'], true);
			} else {
				$overview = '';
			}
 			$data[3] = "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".
				$lead['id']."'>".$lead['fullname'].$overview."</a><br>";
				$data[3] .= "<span style='font-size: 9px'><i>".$lead["company"]."</i></span>";


			$data[4] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$lead['id_company']."'>".get_db_value ('name', 'tcompany', 'id', $lead['id_company'])."</a>";
			if ($lead["owner"] != "")
				$data[4] .= "<br><i>" . $lead["owner"] . "</i>";

			$data[5] = translate_lead_progress ($lead['progress']) . " <i>(".$lead['progress']. "%)</i>";
			
			if ($lead['estimated_sale'] != 0)
				$data[6] = format_numeric($lead['estimated_sale']);
			else
				$data[6] = "--";
		
			$data[7] = "<img src='images/lang/".$lead["id_language"].".png'>"; 
	
			$data[8] =  ucfirst(strtolower($lead['country']));
			$data[9] = "<span style='font-size: 9px' title='". $lead['creation'] . "'>" . human_time_comparation ($lead['creation']) . "</span>";
			$data[9] .= "<br><span style='font-size: 9px'>". human_time_comparation ($lead['modification']). "</span>";

			if ($lead['progress'] < 100 && $lead['owner'] == "")
				$data[10] = "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".
				$lead['id']."&make_owner=1&offset=$offset'><img src='images/award_star_silver_1.png' title='".__("Take ownership of this lead")."'></a>&nbsp;";
			else
				$data[10] = "";


			// Close that lead
			if ($lead['progress'] < 100 && ((($config["id_user"] == $lead["owner"] && ($section_write_permission || $section_manage_permission)) || dame_admin($config["id_user"])))) {
				$data[10] .= "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".
				$lead['id']."&close=1&offset=$offset'><img src='images/lock.png' title='".__('Close this lead')."'></a>";
			}

			// Show delete control if its owned by the user
			if (($config["id_user"] == $lead["owner"] && ($section_write_permission || $section_manage_permission)) || dame_admin($config["id_user"])) {
				$data[10] .= "<a href='#' onClick='javascript: show_validation_delete(\"delete_lead\",".$lead["id"].",0,".$offset.");'><img src='images/cross.png'></a>";
			} else {
				if ($lead["owner"] == ""){
					if ($section_write_permission || $section_manage_permission) {				
						$data[10] .= "<a href='#' onClick='javascript: show_validation_delete(\"delete_lead\",".$lead["id"].",0,".$offset.");'><img src='images/cross.png'></a>";
					}
				}
			}

			array_push ($table->data, $data);

		}
		print_table ($table);
		
		echo "<h5>".$count_total_leads.__(" lead(s) found")."</h5>";
	}
	
	if ($section_write_permission || $section_manage_permission) {
		echo '<form method="post" action="index.php?sec=customers&sec2=operation/leads/lead&tab=search">';
		echo '<div class="button-form" style="text-align:right;">';
		print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"');
		print_input_hidden ('new', 1);
		echo '</div>';
		echo '</form>';
	}

	unset($table);
	$table = new stdClass();
	$table->class = 'search-table-button';
	$table->width = '100%';
	$table->id = 'lead_massive';
	$table->data = array();
	$table->style = array ();

	$table->data[0][0] = print_checkbox ('mass_delete_leads', false, false, true, __('Delete'));
	$table->data[0][1] = print_checkbox ('mass_close_leads', false, false, true, __('Close'));
	$visible_users = get_user_visible_users ($config['id_user']);
	$table->data[0][2] = print_select($visible_users, 'mass_assigned_user_leads', '0', '', __('Select'), -1, true, false, true, __('Assigned user'));

	$table->data[0][3] = "<div class='button-form'>" . print_submit_button (__('Update'), 'massive_leads_update', false, 'class="sub next"', true) . "</div>";

	$massive_oper_leads = print_table ($table, true);

	echo print_container_div('massive_oper_leads', __('Massive operations over selected items'), $massive_oper_leads, 'closed', true, '20px');
}

echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria_crm.js"></script>

<script type="text/javascript" >

function datepicker_hook () {
	add_datepicker ('input[name*="alarm_date"]', null);
	add_datepicker ('input#text-estimated_close_date', null);
}

add_ranged_datepicker ("#text-start_date_search", "#text-end_date_search", null);

// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-fullname');
trim_element_on_submit('#text-email');
trim_element_on_submit('#text-from');
trim_element_on_submit('#text-to');
trim_element_on_submit('#text-cco');
trim_element_on_submit('#text-contract_number');

if (<?php echo $id ?> > 0 || <?php echo json_encode($new) ?> == true) {
	validate_form("#lead_form");
	var rules, messages;

	// Rules: #text-fullname
	rules = {
		required: true//,
/*
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_existing_lead: 1,
				lead_name: function() { return $('#text-fullname').val() },
				lead_id: "<?php echo $id?>"
			}
		}
*/
	};
	messages = {
		required: "<?php echo __('Name required')?>",
		remote: "<?php echo __('This name already exists')?>"
	};
	add_validate_form_element_rules('#text-fullname', rules, messages);

	// Rules: #text-email
	rules = {
		required: true,
		email: true,
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_existing_lead_email: 1,
				lead_email: function() { return $('#text-email').val() },
				lead_id: "<?php echo $id?>"
			}
		}
	};
	messages = {
		required: "<?php echo __('Email required')?>",
		email: "<?php echo __('Invalid email')?>",
		remote: "<?php echo __('This lead email already exists')?>"
	};
	add_validate_form_element_rules('#text-email', rules, messages);

	// Rules: #text-estimated_sale
	rules = { number: true };
	messages = { number: "<?php echo __('Invalid number')?>" };
	add_validate_form_element_rules('#text-estimated_sale', rules, messages);

	// Rules: #text-user
	rules = { required: true };
	messages = { required: "<?php echo __('Please, select an user')?>" };
	add_validate_form_element_rules('#text-user', rules, messages);

	// Rules: #id_language
	rules = { required: true };
	messages = { required: "<?php echo __('Please, select a language')?>" };
	add_validate_form_element_rules('#id_language', rules, messages);
}

$(document).ready (function () {
	
	datepicker_hook();
	
	$("#saved_searches").change(function() {
		$("#form-saved_searches").submit();
	});

	$("#progress_search").change(function() {
		var checkbox = $("#checkbox-show_100_search");
		
		if ($(this).val() >= 100) {
			checkbox.prop("checked", true);
		} else {
			checkbox.prop("checked", false);
		}
	});

	//JS for massive operations
	$("#checkbox-leadcb-all").change(function() {
		$(".cb_lead").prop('checked', $("#checkbox-leadcb-all").prop('checked'));
	});

	$(".cb_lead").click(function(event) {
		event.stopPropagation();
	});
	
	$("#submit-massive_leads_update").click(function(event) {
		process_massive_leads_update();
	});
	
	$("#textarea-description").TextAreaResizer ();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	var onAutocompleteChange = function(event, ui) {
		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: {
				page: "include/ajax/users",
				get_user_company: 1,
				id_user: $('#text-user').val()
			},
			dataType: "json",
			success: function(data) {
				$('#id_company').val(data.name);
				$('#hidden-id_company').val(data.id);
			}
		});
	};
	bindAutocomplete("#text-user", idUser);
	$("#text-user").change(onAutocompleteChange);
	bindCompanyAutocomplete("id_company", idUser);
	bindCompanyAutocomplete("id_company_search", idUser);
	
	if ($("#lead_stats_form").length > 0) {
		validate_user ("#lead_stats_form", "#text-user", "<?php echo __('Invalid user')?>");
	} else if ($("#lead_form").length > 0) {
		validate_user ("#lead_form", "#text-user", "<?php echo __('Invalid user')?>");
	}
	
	$("#checkbox-duplicated_leads").click(function () {
		changeAllowDuplicatedLeads ();
	});
});

function changeAction(tab) {
	
	var f = document.forms.lead_stats_form;
	
	f.action = "index.php?sec=customers&sec2=operation/leads/lead&tab="+tab+"<?php echo $search_params ?>";
	$("#lead_stats_form").submit();
}

// Add or remove the search of duplicated lead names and emails
function changeAllowDuplicatedLeads () {
	
	var checked = $("#checkbox-duplicated_leads").is(":checked");
	
	if (checked) {
		//$('#text-fullname').rules("remove", "remote");
		$('#text-email').rules("remove", "remote");
	} else {
/*
		$('#text-fullname').rules("add", { remote: {
				url: "ajax.php",
				type: "POST",
				data: {
					page: "include/ajax/remote_validations",
					search_existing_lead: 1,
					lead_name: function() { return $('#text-fullname').val() },
					lead_id: "<?php echo $id?>"
				}
			}
		});
*/
		$('#text-email').rules("add", { remote: {
				url: "ajax.php",
				type: "POST",
				data: {
					page: "include/ajax/remote_validations",
					search_existing_lead_email: 1,
					lead_email: function() { return $('#text-email').val() },
					lead_id: "<?php echo $id?>"
				}
			}
		});
	}
}

function process_massive_leads_update () {
	var checked_ids = new Array();
	var delete_leads;
	var close_leads;
	var assigned_user;

	$(".cb_lead").each(function() {
		id = this.id.split ("-").pop ();
		checked = $(this).prop('checked');
		if(checked) {
			$(this).prop('checked', false);
			checked_ids.push(id);
		}
	});

	if(checked_ids.length == 0) {
		alert(__("No items selected"));
	}
	else {
		delete_leads = $("#checkbox-mass_delete_leads").prop("checked");
		close_leads = $("#checkbox-mass_close_leads").prop("checked");
		assigned_user = $("#mass_assigned_user_leads").val();
		if(delete_leads == false && close_leads == false && assigned_user == -1) {
			alert(__("Nothing to update"));
		}
		else {
			var deleted = 0;
			var closed = 0;
			var assigned = 0;

			for(var i = 0; i < checked_ids.length; i++) {
				values = Array ();
				values.push ({
					name: "massive_leads_update",
					value: true
				});
				values.push ({
					name: "massive_leads_num_loop",
					value: i
				});
				values.push ({
					name: "page",
					value: "operation/leads/lead_detail"
				});
				values.push ({
					name: "id",
					value: checked_ids[i]
				});
				if(assigned_user != -1) {
					values.push ({
						name: "id_user",
						value: assigned_user
					});
					values.push ({
						name: "make_owner",
						value: true
					});
				}
				if(close_leads == true) {
					values.push ({
						name: "close",
						value: true
					});
				}
				if(delete_leads == true) {
					values.push ({
						name: "delete", 
						value: true
					});
				}
				$.ajax ({
					url: "ajax.php",
					data: values,
					success: function (data) {
								
								if (data.assigned) {
									assigned += 1;
								}
								if (data.closed) {
									closed += 1;
								}
								if (data.deleted) {
									deleted += 1;
								}
								
								if ((assigned > 0 || closed > 0 || deleted > 0) && data.num_loop >= checked_ids.length -1) {
									// if(assigned_user != -1 && assigned < checked_ids.length) {
									// 	alert(checked_ids.length - assigned + " <?php echo __('leads were not assigned') ?>");
									// }
									// if(close_leads == true && closed < checked_ids.length) {
									// 	alert(checked_ids.length - closed + " <?php echo __('leads were not closed') ?>");
									// }
									// if(delete_leads == true && deleted < checked_ids.length) {
									// 	alert(checked_ids.length - deleted + " <?php echo __('leads were not deleted') ?>");
									// }
									location.reload();
								}
							},
					dataType: "json"
				});
			}
		}
	}	
}

function cleanAlarm() {
	$('#text-alarm_date').val('');
	$('#text-alarm_time').val('');
}

</script>
