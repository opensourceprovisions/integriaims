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

$upload_file = get_parameter('upload_file', 0);

if ($upload_file) {
	$result = array();
	$result["status"] = false;
	$result["message"] = "";
	$result["id_attachment"] = 0;
	
	$filename= $_FILES["upfile"]["name"];
	$file_tmp = $_FILES["upfile"]["tmp_name"];
	$size = $_FILES["upfile"]["size"];
	$id_company = get_parameter ("id", -1);
	$company = get_db_row ('tcompany', 'id', $id_company);
	$id_invoice = get_parameter ("id_invoice", -1);
	$description = get_parameter ("description", "");
	
	$upload_status = getFileUploadStatus("upfile");
	$upload_result = translateFileUploadStatus($upload_status);
	
	
	$sql = sprintf("INSERT INTO tattachment (id_invoice, id_usuario, filename, description, timestamp, size) VALUES (%d, '%s', '%s', '%s', '%s', %d)", $id_invoice, $config["id_user"], $filename, $description, date('Y-m-d H:i:s'), $size);
	$id_attach = process_sql ($sql, 'insert_id');

	$filename_encoded = $id_attach . "_" . $filename;
	
	// Copy file to directory and change name
	$file_target = $config["homedir"]."/attachment/".$filename_encoded;

	if (!(copy($file_tmp, $file_target))){
		$result["message"] =__("Could not be attached");
		$result["status"] = false;
	} else {
		// Delete temporal file
		$result["message"] = __("Successfully attached");
		$location = $file_target;
		unlink ($file_tmp);
		$result["id_attachment"] = $id_attach;
		$result["status"] = true;
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
	$id_invoice = (int) get_parameter("id");
	$file = get_db_row_filter ('tattachment', array('id_invoice' => $id_invoice, 'id_attachment' => $id_file));

	$html = "";
	if ($file) {
		$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=company";
		$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

		$html .= "<tr>";
		$html .= "<td valign=top>";
		$html .= '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';

		$stat = stat ($real_filename);
		
		$html .= "<td valign=top class=f9>". $file["description"];
		//$html .= "<td valign=top>". $file["id_usuario"];
		$html .= "<td valign=top>". byte_convert ($file['size']);
		$html .= "<td valign=top class=f9>".date ("Y-m-d", $stat['mtime']);
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