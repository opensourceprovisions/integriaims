<?php

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

global $config;

check_login ();

include_once('include/functions_crm.php');

$id = (int) get_parameter ('id');
$id_company = (int) get_parameter ('id_company');

$section_read_permission = check_crm_acl ('company', 'cr');
$section_write_permission = check_crm_acl ('company', 'cw');
$section_manage_permission = check_crm_acl ('company', 'cm');

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contacts without permission");
	include ("general/noaccess.php");
	exit;
}

if (defined ('AJAX')) {
	$upload_file = (bool) get_parameter("upload_file");
	if ($upload_file) {
		$result = array();
		$result["status"] = false;
		$result["message"] = "";
		$result["id_attachment"] = 0;

		$upload_status = getFileUploadStatus("upfile");
		$upload_result = translateFileUploadStatus($upload_status);

		if ($upload_result === true) {
			$filename = $_FILES["upfile"]['name'];
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$invalid_extensions = "/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|
				pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
				htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i";
			
			if (!preg_match($invalid_extensions, $extension)) {
				// Insert into database
                $filename_real = safe_output ( $filename ); // Avoid problems with blank spaces
                $file_temp = $_FILES["upfile"]['tmp_name'];;
                $file_new = str_replace (" ", "_", $filename_real);
                $filesize = $_FILES["upfile"]["size"]; // In bytes

                $sql = sprintf ('INSERT INTO tattachment (id_contact, id_usuario,
                                filename, description, size)
                                VALUES (%d, "%s", "%s", "%s", %d)',
                                $id, $config['id_user'], $file_new, $file_description, $filesize);

                $id_attachment = process_sql ($sql, 'insert_id');
                if ($id_attachment) {
					unlink ($file_tmp);
					$result["status"] = true;
					$result["id_attachment"] = $id_attachment;
				}
			} else {
				$result["message"] = __('Invalid extension');
			}

		} else {
			$result["message"] = $upload_result;
		}
		echo json_encode($result);
		return;
	}

	$update_file_description = (bool) get_parameter("update_file_description");
	if ($update_file_description) {
		$id_file = (int) get_parameter("id_attachment");
		$file_description = get_parameter("file_description");
		$result = array();
		$result["status"] = false;
		$result["message"] = "";

		$result['status'] = (bool) process_sql_update('tattachment',
			array('description' => $file_description), array('id_attachment' => $id_file));

		if (!$result['status'])
			$result['message'] = __('Description not updated');

		echo json_encode($result);
		return;
	}

	$get_file_row = (bool) get_parameter("get_file_row");
	if ($get_file_row) {
		$id_file = (int) get_parameter("id_attachment");
		
		$file = get_db_row_filter ('tattachment', array('id_contact' => $id, 'id_attachment' => $id_file));

		$html = "";
		if ($file) {
			$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=contact";
			$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

			$html .= "<tr>";
			$html .= "<td valign=top>";
			$html .= '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';

			$stat = stat ($real_filename);
			$html .= "<td valign=top class=f9>".date ("Y-m-d H:i:s", $stat['mtime']);

			$html .= "<td valign=top class=f9>". $file["description"];
			//$html .= "<td valign=top>". $file["id_usuario"];
			$html .= "<td valign=top>". byte_convert ($file['size']);

			// Delete attachment
			if (give_acl ($config['id_user'], $incident['id_grupo'], 'IM')) {
				$html .= "<td>". '<a class="delete" name="delete_file_'.$file["id_attachment"]
				.'" href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='
				.$id.'&tab=files&id_attachment='.$file["id_attachment"].'&delete_file=1#incident-operations">
				<img src="images/cross.png"></a>';
			}
		}

		echo $html;
		return;
	}
}


if ($id || $id_company) {
	if ($id) {
		$id_company = get_db_value ('id_company', 'tcompany_contact', 'id', $id);
	}
	
	$read_permission = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
	$write_permission = check_crm_acl ('other', 'cw', $config['id_user'], $id_company);
	$manage_permission = check_crm_acl ('other', 'cm', $config['id_user'], $id_company);
	
	if ((!$read_permission && !$write_permission && !$manage_permission) || $id_company === false) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access a contact without permission");
		include ("general/noaccess.php");
		exit;
	}
}

$op = get_parameter("op", "details");

if ($id == 0) {
	echo "<h2>".__('Contact management')."</h2>";
	if (!$new_contact){
		echo "<h4>".__('List of Contact');
		echo integria_help ("contact_detail", true);
		echo "</h4>";
	}
	
}

if ($id != 0) {
	
	echo '<h2>';
	switch ($op) {
		case "files":
			echo strtoupper(__("Files"));
			break;
		case "activity":
			echo strtoupper(__("Activity"));
			break;
		case "details":
			echo strtoupper(__('Contact details'));
			break;
		case "incidents":
			echo strtoupper(__('Tickets'));
			break;
		case "inventory":
			echo strtoupper(__('Inventory'));
			break;
		default:
			echo strtoupper(__('Details'));
	}

	echo '</h2>';
	$contact = get_db_row ('tcompany_contact', 'id', $id);
	echo '<h4>' . sprintf(__('Contact: %s'), $contact['fullname']);
	echo integria_help ("contact_detail", true);
	echo '<ul style="height: 30px;" class="ui-tabs-nav">';
	if ($op == "files")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=files" title="'.__("Files").'"><img src="images/disk.png"/></a></li>';

	if ($op == "inventory")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=inventory" title="'.__("Inventory").'"><img src="images/inventory_tab.png"/></a></li>';

	if ($op == "incidents")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=incidents" title="'.__("Tickets").'"><img src="images/incident_dark.png"/></a></li>';

	if ($op == "activity")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=activity" title="'.__("Activity").'"><img src="images/list_view.png"/></a></li>';

	if ($op == "details")
		echo '<li class="ui-tabs-selected">';
	else   
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=details" title="'.__("Contact details").'"><img src="images/details_tab.png"/></a></li>';

	echo '</ul>';
	echo '</h4>';
}

switch ($op) {
	case "incidents":
		include("contact_incidents.php");
		break;
	case "inventory":
		include("contact_inventory.php");
		break;
	case "details":
		include("contact_manage.php");
		break;
	case "files":
		include("contact_files.php");
		break;
	case "activity": 
		include("contact_activity.php");
		break;	
	default:
		include("contact_manage.php");
}

if ($id == 0 && !$new_contact) {
	
	$search_text = (string) get_parameter ('search_text');
	$id_company = (int) get_parameter ('id_company', 0);
	
	$where_clause = "WHERE 1=1";
	if ($search_text != "") {
		$where_clause .= " AND (fullname LIKE '%$search_text%' OR email LIKE '%$search_text%' OR phone LIKE '%$search_text%' OR mobile LIKE '%$search_text%') ";
	}

	if ($id_company) {
		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}
	$search_params = "&search_text=$search_text&id_company=$id_company";

	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 20, 100, true, __('Search'). print_help_tip (__("Search according to contact name, phone, email and mobile"), true));
	
	$params = array();
	$params['input_id'] = 'id_company';
	$params['input_name'] = 'id_company';
	$params['input_value'] = $id_company;
	$params['title'] = __('Company');
	$params['return'] = true;
	$table->data[1][0] = print_company_autocomplete_input($params);

	$table->data[2][0] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
	// Delete new lines from the string
	$where_clause = str_replace(array("\r", "\n"), '', $where_clause);
	$table->data[3][0] = print_button(__('Export to CSV'), '', false,
		'window.open(\'include/export_csv.php?export_csv_contacts=1&where_clause=' . 
			str_replace("'", "\'", $where_clause) . '\')', 'class="sub"', true);
	
	echo "<div class='divform'>";
		echo '<form id="contact_search_form" method="post">';
			print_table ($table);
		echo '</form>';
		//Show create button only when contact list is displayed
		if(($section_write_permission || $section_manage_permission) && !$id && !$new_contact) {
			echo '<form method="post" action="index.php?sec=customers&sec2=operation/contacts/contact_detail">';
			unset($table->data);
			$table->data[0][0] = print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"',true);
			$table->data[0][0] .= print_input_hidden ('new_contact', 1);
			print_table ($table);
			echo '</form>';
		}
	echo '</div>';

	$contacts = crm_get_all_contacts ($where_clause);
	
	echo "<div class='divresult'>";
	if ($contacts !== false) {
		
		$contacts = print_array_pagination ($contacts, "index.php?sec=customers&sec2=operation/contacts/contact_detail&params=$search_params", $offset);
		unset ($table);
		$table = new stdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->size[3] = '40px';
		$table->style = array ();
		// $table->style[] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = __('Full name');
		$table->head[1] = __('Company');
		$table->head[2] = __('Email');
		if($section_write_permission || $section_manage_permission) {
			$table->head[3] = __('Delete');
		}
		
		foreach ($contacts as $contact) {
			$data = array ();
			// Name
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&id=".
				$contact['id']."'>".$contact['fullname']."</a>";
			$data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$contact['id_company']."'>".get_db_value ('name', 'tcompany', 'id', $contact['id_company'])."</a>";
			$data[2] = $contact['email'];
			if($section_write_permission || $section_manage_permission) {
				$data[3] = '<a href="index.php?sec=customers&
							sec2=operation/contacts/contact_detail&
							delete_contact=1&id='.$contact['id'].'&offset='.$offset.'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png"></a>';
			}	
			array_push ($table->data, $data);
		}
		
		print_table ($table);
	} else {
		echo ui_print_error_message (__("There are not results for the search"), '', true, 'h3', true);
	}
	echo '</div>';
}
?>
