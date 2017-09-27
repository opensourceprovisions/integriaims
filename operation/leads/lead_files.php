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

if (!isset($read_permission)) {
	$read_permission = check_crm_acl ('lead', 'cr', $config['id_user'], $id);
	if (!$read_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead");
		include ("general/noaccess.php");
		exit;
	}
}

// Delete file

$deletef = get_parameter ("deletef", "");
if ($deletef != ""){
	$file = get_db_row ("tattachment", "id_attachment", $deletef);
	if ( (dame_admin($config["id_user"])) || ($file["id_usuario"] == $config["id_user"]) ){
		$sql = "DELETE FROM tattachment WHERE id_attachment = $deletef";
		process_sql ($sql);	
		$filename = $config["homedir"]."/attachment/". $file["id_attachment"]. "_" . $file["filename"];
		unlink ($filename);
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);

	}
}

// Upload file
if (isset($_GET["upload"])) {
	
	if (isset($_POST['upfile']) && ( $_POST['upfile'] != "" )){ //if file
		$filename= $_POST['upfile'];
		$file_tmp = sys_get_temp_dir().'/'.$filename;
		$size = filesize ($file_tmp);
		$description = get_parameter ("description", "");

		$sql = sprintf("INSERT INTO tattachment (id_lead, id_usuario, filename, description, timestamp, size) VALUES (%d, '%s', '%s', '%s', '%s', %d)", $id, $config["id_user"], $filename, $description, date('Y-m-d H:i:s'), $size);
		$id_attach = process_sql ($sql, 'insert_id');

		$filename_encoded = $id_attach . "_" . $filename;
		
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$filename_encoded;

		if (!(copy($file_tmp, $file_target))){
			echo ui_print_error_message (__("Could not be attached"), '', true, 'h3', true);
		} else {
			// Delete temporal file
			echo ui_print_success_message (__("Successfully attached"), '', true, 'h3', true);
			$location = $file_target;
			unlink ($file_tmp);
		}


		// Create record in tattachment
		
	}
}


// Control to upload file


echo '<div class="divform">';
echo '<table class="search-table">';
echo '<tr>';
echo '<td>';
echo print_button (__('Upload a new file'), 'add_link', false, '$(\'#upload_div\').slideToggle (); return false', 'class="sub upload"');

echo '<div id="upload_div" style="display: none;" class="">';
$target_directory = 'attachment';
$action = "index.php?sec=customers&sec2=operation/leads/lead_detail&id=$id&op=files&upload=1";				
$into_form = "<input type='hidden' name='directory' value='$target_directory'><b>Description</b>&nbsp;<input type=text name=description size=20>";
print_input_file_progress($action,$into_form,'','sub upload');
echo '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';

// List of lead attachments

$sql = "SELECT * FROM tattachment WHERE id_lead = $id ORDER BY timestamp DESC";
$files = get_db_all_rows_sql ($sql);	

echo "<div style='width: 86%; float: right;'>";
echo "<div class='divresult'>";
if ($files !== false) {
	$files = print_array_pagination ($files, "index.php?sec=customers&sec2=operation/leads/lead_detail&id=$id&op=files");
	unset ($table);
	$table->width = "100%";
	$table->class = "listing";
	$table->data = array ();
	$table->size = array ();
	$table->style = array ();
	$table->rowstyle = array ();

	$table->head = array ();
	$table->head[0] = __('Filename');
	$table->head[1] = __('Description');
	$table->head[2] = __('Size');
	$table->head[3] = __('Date');
	$table->head[4] = __('Ops.');

	foreach ($files as $file) {
		$data = array ();
		
		$data[0] = "<a href='operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=lead'>".$file["filename"] . "</a>";
		$data[1] = $file["description"];
		$data[2] = format_numeric($file["size"]);
		$data[3] = $file["timestamp"];

		// Todo. Delete files owner of lead and admins only
		if ( (dame_admin($config["id_user"])) || ($file["id_usuario"] == $config["id_user"]) ){
			$data[4] = "<a href='index.php?sec=customers&sec2=operation/leads/lead_detail&id=$id&op=files&deletef=".$file["id_attachment"]."'><img src='images/cross.png'></a>";
		}

		array_push ($table->data, $data);
		array_push ($table->rowstyle, $style);
	}
	print_table ($table);

} else {
	echo ui_print_error_message (__('There is no files attached for this lead'), '', true, 'h3', true);
}
echo "</div>";
echo "</div>";

?>
