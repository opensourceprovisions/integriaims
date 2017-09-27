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

$search_companies = (bool) get_parameter ('search_companies');
$get_company_id = (bool) get_parameter ('get_company_id');
$upload_file = (bool) get_parameter ('upload_file');
$update_file_description = (bool) get_parameter("update_file_description");
$get_file_row = (bool) get_parameter("get_file_row");

if ($search_companies) {
	require_once ('include/functions_db.php');
	require_once('include/functions_crm.php');

	$id_user = (string) get_parameter ('id_user', $config['id_user']);
	$string = (string) get_parameter ('term'); // term is what autocomplete plugin gives
	$type = (string) get_parameter ('type');
	$filter = (string) get_parameter ('filter'); // complements the main filter
	if ($filter) {
		$filter = safe_output($filter);
	}

	$where_clause = sprintf (' AND (tcompany.id = %d
									OR tcompany.name LIKE "%%%s%%"
									OR tcompany.country LIKE "%%%s%%"
									OR tcompany.manager LIKE "%%%s%%") AND tcompany.manager = "%s"', $string, $string, $string, $string, $id_user);
	
	$companies = crm_get_companies_list($where_clause . $filter, false, "ORDER BY name", true);

	if (!$companies) {
		return;
	}
	
	$result = array();
	
	foreach ($companies as $id => $name) {
		switch ($type) {
			case 'invoice':
				if (check_crm_acl('invoice', '', $id_user, $id)) {
					array_push($result, array("label" => safe_output($name), "value" => $id));
				}
				break;
			
			default:
				array_push($result, array("label" => safe_output($name), "value" => $id));
				break;
		}
	}
	
	echo json_encode($result);
	return;
}

if ($get_company_id) {
	require_once ('include/functions_db.php');
	require_once('include/functions_crm.php');

	$id_user = (string) get_parameter ('id_user', $config['id_user']);
	$company_name = (string) get_parameter ('company_name');
	
	$result = get_db_value("id", "tcompany", "name", $company_name);
	
	echo json_encode($result);
	return;
}

if ($upload_file) {
	$id_company = get_parameter("id");
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
			$filename = str_replace (" ", "_", $filename); // Replace conflictive characters
			$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
			$file_tmp = $_FILES["upfile"]['tmp_name'];
			$filesize = $_FILES["upfile"]["size"]; // In bytes

			$values = array(
					"id_company" => $id_company,
					"id_usuario" => $config['id_user'],
					"filename" => $filename,
					"description" => "",
					"size" => $filesize,
					"timestamp" => date("Y-m-d")
				);
			$id_attachment = process_sql_insert("tattachment", $values);

			if ($id_attachment) {
				$location = $config["homedir"]."/attachment/".$id_attachment."_".$filename;

				if (copy($file_tmp, $location)) {
					// Delete temporal file
					unlink ($file_tmp);
					$result["status"] = true;
					$result["id_attachment"] = $id_attachment;
				}
				else {
					unlink ($file_tmp);
					process_sql_delete ('tattachment', array('id_attachment' => $id_attachment));
					$result["message"] = __('The file could not be copied');
				}
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

if ($get_file_row) {
	$id_file = (int) get_parameter("id_attachment");
	$id_company = (int) get_parameter("id");
	$file = get_db_row_filter ('tattachment', array('id_company' => $id_company, 'id_attachment' => $id_file));

	$html = "";
	if ($file) {
		$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=company";
		$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

		$html .= "<tr>";
		$html .= "<td valign=top>";
		$html .= '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';
		
		$html .= "<td valign=top class=f9>". $file["description"];
		//$html .= "<td valign=top>". $file["id_usuario"];
		$html .= "<td valign=top>". byte_convert ($file['size']);
		
		$stat = stat ($real_filename);
		$html .= "<td valign=top class=f9>".date ("Y-m-d H:i:s", $stat['mtime']);
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

?>