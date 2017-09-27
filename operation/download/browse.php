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
check_login();

if (give_acl($config["id_user"], 0, "FRR")==0) {
    audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access Downloads browser");
    require ("general/noaccess.php");
    exit;
}

require_once ('include/functions_file_releases.php');

if (defined ('AJAX')) {

	$get_external_id = get_parameter ("get_external_id", 0);
	if ($get_external_id) {
		echo sha1(random_string(12).date());
		return;
	}

	$upload_file = get_parameter ("upload_file", 0);
	if ($upload_file) {
		$result = array();
		$result["status"] = false;
		$result["message"] = "";

		if (give_acl($config["id_user"], 0, "FRW") != 1){
			audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to create a new Download file without privileges");
			$result["message"] = __('Error');
			echo json_encode($result);
			return;
		}

		$upload_status = getFileUploadStatus("upfile");
		$upload_result = translateFileUploadStatus($upload_status);

		if ($upload_result === true) {
			$filename = $_FILES["upfile"]['name'];
			//$filename = str_replace (" ", "_", $filename); // Replace conflictive characters
			//$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
			$location = "attachment/downloads/$filename";
			
			$file_path = $config["homedir"]."/".$location;
			$file_tmp = $_FILES["upfile"]['tmp_name'];
			
			if (copy($file_tmp, $file_path)) {
				unlink ($file_tmp);
				$result["status"] = true;
				$result["filename"] = $filename;
			} else {
				unlink ($file_tmp);
				$result["message"] = __('The file could not be copied');
			}

		} else {
			$result["message"] = $upload_result;
		}

		echo json_encode($result);
		return;
	}

	$insert_fr = get_parameter ("insert_fr", 0);
	if ($insert_fr) {
		$result = array();
		$result["status"] = false;
		$result["message"] = "";

		if (give_acl($config["id_user"], 0, "FRW") != 1){
			audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to create a new Download file without privileges");
			$result["message"] = __('Error');
			echo json_encode($result);
			return;
		}

		// Database Creation
		// ==================
		$timestamp = date('Y-m-d H:i:s');
		$name = get_parameter ("name","");

		if ($name != "") {
			$filename = get_parameter ("filename","");
			$description = get_parameter ("description", "");
			$id_category = get_parameter ("id_category", "");
			$id_type = (int) get_parameter ("id_type", -1);
			$public = (int) get_parameter ("public");
			$external_id = (string) get_parameter ("external_id", "");
			if (empty($external_id)) {
				$external_id = sha1(random_string(12).date());
			}
			
			$values = array(
					'name' => $name,
					'location' => "attachment/downloads/$filename",
					'description' => $description,
					'id_category' => $id_category,
					'id_user' => $config["id_user"],
					'date' => $timestamp,
					'public' => $public,
					'external_id' => $external_id
				);
			$id = process_sql_insert("tdownload", $values);	
			if ($id) {

				$result["status"] = true;
				$result["message"] = __('Successfully created');
				if ($id_type > 0) {
					insert_type_file($id, $id_type);
				}
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Download", "Created download item $id_data - $name");

			} else {
				$result["message"] = __('Could not be created'); 
			}
		} else {
			$result["message"] = __('Name required');
			$result["repeat"] = true;
		}
		// ==================

		echo json_encode($result);
		return;
	}

	$delete_file = (bool) get_parameter("delete_file");
	if ($delete_file) {
		$result = array();
		$result["status"] = false;
		$result["message"] = "";

		if (!give_acl($config["id_user"], 0, "FRW")) {
			audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation", "Trying to delete a file without privileges");
			$result["message"] = __('Error');
			echo json_encode($result);
			return;
		}

		$file = (string) clean_output(get_parameter("file"));
		if ($file) {
			$file_path = $config["homedir"]. "/". "attachment/downloads/" . $file;
			$result["status"] = unlink ($file_path);
		}
		
		echo json_encode($result);
		return;
	}
}

$delete_btn = get_parameter ("delete_btn", 0);

// File deletion
// ==================

if ($delete_btn){
	$location = clean_output (get_parameter ("location",""));
	
	$file_path = $config["homedir"]. "/". "attachment/downloads/" . $location;

	unlink ($file_path);
	$_GET["create"]=1;

}

// Database UPDATE
// ==================
if (isset($_GET["update2"])){ // if modified any parameter

	if (give_acl($config["id_user"], 0, "FRW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to update a download without privileges");
	    require ("general/noaccess.php");
    	exit;
    }

	$id = get_parameter ("id","");
	
		
	if ($id != "" && ! check_fr_item_accessibility($config["id_user"], $id)) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a File Releases forbidden item");
		require ("general/noaccess.php");
		exit;
	}
	

	$timestamp = date('Y-m-d H:i:s');

	$name = get_parameter ("name","");
	// Location should not be changed never.
	$description = get_parameter ("description","");
	$id_category = get_parameter ("id_category", 0);
	$id_type = get_parameter ("id_type", 0);
	$public = (int) get_parameter ("public",0);
	$external_id = (string) get_parameter ("external_id", "");

	if (empty($external_id)) {
		$external_id = sha1(random_string(12).date());
	}

	$sql_update ="UPDATE tdownload
	SET public = $public, external_id = '$external_id', name = '$name', description = '$description', id_category = $id_category WHERE id = $id";

	$result=mysql_query($sql_update);

	if (! $result)
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	else {
		if ($id_type > 0) {
			insert_type_file($id, $id_type);
		} else {
			delete_type_file($id);
		}

		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		//insert_event ("DOWNLOAD ITEM UPDATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Download", "Updated download item $id - $name");
	}
}

// ==================================================================
// Database DELETE
// ==================================================================

if (isset($_GET["delete_data"])){ // if delete

	if (give_acl($config["id_user"], 0, "FRW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to delete a Download without privileges");
		require ("general/noaccess.php");
		exit;
	}

	$id = get_parameter ("delete_data",0);
	
	if ($id && ! check_fr_item_accessibility($config["id_user"], $id)) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete a File Releases forbidden item");
		require ("general/noaccess.php");
		exit;
	}
	
	$download_title = get_db_sql ("SELECT name FROM tdownload WHERE id = $id ");
	$file_path = get_db_sql ("SELECT location FROM tdownload WHERE id = $id ");

	$file_path = $config["homedir"]."/".$file_path;

	if ($file_path)
		unlink (safe_output($file_path));
	
	$sql_delete= "DELETE FROM tdownload WHERE id = $id";		
	$result=mysql_query($sql_delete);

	delete_type_file($id);

	$sql_delete= "DELETE FROM tdownload_tracking WHERE id_download = $id";		
	$result=mysql_query($sql_delete);

	//insert_event ("DOWNLOAD ITEM DELETED", $id, 0, "Deleted Download $download_title");
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Download", "Deleted download item  $download_title");
	echo ui_print_error_message (__('Successfully deleted'), '', true, 'h3', true);
}

if (isset($_GET["update2"])){
	$_GET["update"]= $id;
}

// ==================================================================
// CREATE form
// ==================================================================

if ((isset($_GET["create"]) OR (isset($_GET["update"])))) {
	if (isset($_GET["create"])){	
		$name = "";
		$location = "";
		$id_category = 1;
		$id_type = 0;
		$id = -1;
		$description = "";	
		$external_id = sha1(random_string(12).date());
		$public = 0;

	} else {
		$id = get_parameter ("update",-1);
		
		if ($id != -1 && ! check_fr_item_accessibility($config["id_user"], $id)) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a File Releases forbidden item");
			require ("general/noaccess.php");
			exit;
		}
		
		$row = get_db_row ("tdownload", "id", $id);
		
		$name = $row["name"];
		$description =$row["description"];
		$location = $row["location"];
		$id_category = $row["id_category"];
		$id_type = get_db_value("id_type", "tdownload_type_file", "id_download", $row["id"]);
		$timestamp = $row["date"];
		$down_id_user = $row["id_user"];
		$public = $row["public"];
		$external_id = $row["external_id"];
	}

	if ($id == -1) {
		
		echo "<h2>".__('Downloads')."</h2>";
		echo "<h4>".__('Create a new file release')."</h4>";

		$current_directory = $config["homedir"]. "/attachment/downloads";

		if (!is_writable($current_directory)) {
			echo ui_print_error_message (__('Current directory is not writtable by HTTP Server'), '', true, 'h3', true);
			echo "<p>";
			echo __('Please check that current directory has write rights for HTTP server');
			echo "</p>";

		} else {
			
			// This chunk of code is to do not show in the combo with files, files already as file downloads
			// (slerena, Sep2011)

		    $location = basename ($location);
		    $files = get_download_files();
		    $files_db  = get_db_all_rows_sql ("SELECT * FROM tdownload WHERE location LIKE 'attachment/downloads/%'");
			if($files_db == false) {
				$files_db = array();
			}

			$files_in = array();
		    foreach ($files_db as $file_db){
		        $files_in[basename($file_db['location'])] = 1;
		    }

		    $files_not_in = array();
		    foreach ($files as $file) {
		        if(!isset($files_in[$file])) {
		            $files_not_in[$file] = $file;
		        }
		    }

			echo "<form id=\"form-file_releases\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
			echo 	"<div id=\"drop_file\">";
			echo 		"<table width=\"99%\">";
			echo 			"<td width=\"30%\">";
			echo 				__('Drop the file here');
			echo 			"<td>";
			echo 				__('or');
			echo 			"<td width=\"30%\">";
			echo 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
			echo 			"<td>";
			echo 				__('or');
			echo 			"<td width=\"30%\">";
			echo 				print_select ($files_not_in, 'location', $location, '', __('Select'), 0, true, 0, false, __('Choose file from repository'));
			echo 				"&nbsp;" . integria_help ("choose_download", true);
			echo 				print_image("images/cross.png", true, array('class' => 'delete', 'style' => 'display: none;'));
			echo 		"</table>";
			echo 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
			echo 	"</div>";
			echo 	"<ul></ul>";
			echo "</form>";
		}

	}
	else {
		echo "<h2>".__('Downloads')."</h2>";
		echo "<h4>".__('Update existing file release')."</h4>";
	}
	
	$table = new stdClass;
	$table->width = '100%';
	$table->id = 'download_data';
	$table->class = 'search-table-button';
	$table->data = array();
	$table->colspan = array();
	$table->colspan[0][1] = 2;
	$table->colspan[2][0] = 3;
	$table->colspan[3][0] = 3;

	$table->data[0][0] = print_input_text ('name', $name, '', 40, 100, true, __('Name'));
	$table->data[0][1] = print_input_text ('external_id', $external_id, '', 60, 100, true, __('External ID'), true);
	$table->data[1][0] = print_checkbox ("public", 1, $public, true, __('Public link'));
	$table->data[1][1] = combo_download_categories ($id_category, 0, __('Main category'), true);
	$table->data[1][2] = print_select (get_file_types(true, true), 'id_type', $id_type, '', '', 0, true, 0, false, __('Main type'));
	$table->data[2][0] = print_textarea ("description", 5, 40, $description, '', true, __('Description'));
	
	if ($id == -1) {

		$table->data[3][0] = "<div class='button-form'>" . print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true) . "</div>";
		$form_file_release = '<form style="display:none;" id="form-file_release" enctype="multipart/form-data" name=prodman2 method="post" action="index.php?sec=download&sec2=operation/download/browse&create2=1">';
	
	} else {

		$table->data[3][0] = "<div class='button-form'>" . print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true) . "</div>";
		$form_file_release = "<form id='form-file_release' enctype='multipart/form-data' name=prodman2 method='post' action='index.php?sec=download&sec2=operation/download/browse&update2=1'>";
		$form_file_release .= "<input id='id_download' type=hidden name=id value='$id'>";
	}

	$form_file_release .= print_table($table, true);
	$form_file_release .= "</form>";

	echo $form_file_release;

}


if ((!isset($_GET["update"])) AND (!isset($_GET["create"]))) {
	
	$show_types = (bool) get_parameter ("show_types", 0);
	
	if ($show_types) {

		echo "<h2>".__('Download')."</h2><h4>".__('Types')."</h4>";
		print_file_types_table();

	}
	else {

		// ==================================================================
		// Show search controls
		// ==================================================================
		
		echo "<h2>".__('Downloads')."</h2><h4>".__('Defined data')."</h4>";
		
		// Search parameter 
		$free_text = get_parameter ("free_text", "");
		$category = get_parameter ("id_category", 0);
		$id_type = get_parameter ("id_type", 0);
		
		// Search filters
		$table = new stdClass;
		$table->width = '100%';
		$table->class = 'search-table';
		$table->data = array();
		$table->colspan = array();
		$table->colspan[1][0] = 3;

		$table->data[0][0] = print_input_text ('free_text', $free_text, '', 40, 100, true, __('Search'));
		$table->data[1][1] = combo_download_categories ($category, true, true, true);
		$table->data[2][2] = print_select (get_file_types(true), 'id_type', $id_type, '', __('Any'), 0, true, 0, false, __('Type'));
		$table->data[3][0] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);

		echo "<div class='divform'>";
		echo '<form method="post" action="index.php?sec=download&sec2=operation/download/browse&id_type=' . $id_type . '&id_category=' . $category . '">';
		echo print_table($table, true);
		echo "</form>";
		echo "</div>";
		
		// ==================================================================
		// Download listings
		// ==================================================================
		
		$sql_filter = "";
		
		if ($free_text != "")
			$sql_filter .= " AND name LIKE '%$free_text%' OR description LIKE '%$free_text%'";
		
		if ($category > 0)
			$sql_filter .= " AND id_category = $category ";

		if ($id_type > 0)
			$sql_filter .= " AND id IN (SELECT id_download FROM tdownload_type_file WHERE id_type = $id_type) ";
		if ($id_type == -1)
			$sql_filter .= " AND id NOT IN (SELECT id_download FROM tdownload_type_file) ";
		
		$offset = get_parameter ("offset", 0);
		$condition = get_filter_by_fr_category_accessibility();
		$count = get_db_sql("SELECT COUNT(id) FROM tdownload WHERE 1=1 $condition $sql_filter");
		pagination ($count, "index.php?sec=download&sec2=operation/download/browse&id_category=$id_category&id_type=$id_type&free_text=$free_text", $offset);
		
		$sql = "SELECT * FROM tdownload WHERE 1=1 $condition $sql_filter ORDER BY date DESC, name LIMIT $offset, ". $config["block_size"];
		
		$color = 0;
		
		$downloads = process_sql($sql);
		
		echo "<div class='divresult'>";
		
		if($downloads == false) {
			$downloads = array();
			echo ui_print_error_message (__('No Downloads found'), '', true, 'h3', true);
		}
		else {

			$table = new stdClass;
			$table->width = '100%';
			$table->class = 'listing';
			$table->head = array();
			$table->data = array();
			$table->colspan = array();

			$table->head[0] = __('Name');
			$table->head[1] = __('Size');
			$table->head[2] = __('Category');
			$table->head[3] = __('Type');
			if (give_acl($config["id_user"], 0, "FRW")) {
				$table->head[4] = __('Downloads');
				$table->head[5] = __('Public link');
			}
			$table->head[6] = __('Date');
			if (give_acl($config["id_user"], 0, "FRW")){
				$table->head[7] = __('Admin');
			}

			foreach($downloads as $row) {

				$row["location"] = safe_output($row["location"]);

				$data = array();

				// Name
				$path_file_baseurl = $config["base_url"].'/'.$row['location'];
				$path_file_homedir = $config["homedir"].'/'.$row['location'];
				$size_bytes = filesize($path_file_homedir);
				$size_mb = ($size_bytes / (1024*1024));

				if ($size_mb < $config['max_direct_download']) {
					$data[0] = "<a title='".$row["description"]."' href='operation/common/download_file.php?type=release&id_attachment=".$row["id"]."'>";
				} else {
					$data[0] = "<a title='".$row["description"]."' href='".$config["base_url"].$row['location']."' download>";
				}
				$data[0] .= $row["name"];
				if ($row["description"] != ""){
					$data[0] .=  " <img src='images/informacion.png'>";
				}
				$data[0] .= "</a>";
				
				// Size
				$data[1] = format_for_graph(filesize($config["homedir"].$row["location"]),1,".",",",1024);

				// Category
				$img = get_db_sql ("SELECT icon FROM tdownload_category WHERE id = ".$row["id_category"]);
				if ($img) {
					$data[2] = "<img src='images/download_category/".get_db_sql ("SELECT icon FROM tdownload_category WHERE id = ".$row["id_category"]). "'>";
				}
				else {
					$data[2] = "";
				}
				
				// Type
				$row["id_type"] = get_db_value("id_type", "tdownload_type_file", "id_download", $row["id"]);
				if (!$row["id_type"]) {
					$row["id_type"] = -1;
				}
				$data[3] = get_download_type_icon($row["id_type"]);

				if (give_acl($config["id_user"], 0, "FRW")){
					// Downloads
					$data[4] = get_db_sql ("SELECT COUNT(*) FROM tdownload_tracking where id_download = ".$row["id"]);

					// Public URL
					if ($row["public"]){
						$url = $config["base_url"] . "/operation/common/download_file.php?type=external_release&id_attachment=".$row["external_id"];
						$data[5] = "<a href='$url'><img src='images/world.png'></a>";
					} else {
						$data[5] = "";
					}
				}

				// Timestamp
				$data[6] = human_time_comparation($row["date"]);

				if (give_acl($config["id_user"], 0, "FRW")){

					// Edit
					$data[7] = "<a href='index.php?sec=download&sec2=operation/download/browse&update=".$row["id"]."'><img border='0' src='images/wrench.png'></a>";
					$data[7] .= "&nbsp;&nbsp;";
					// Delete
					$data[7] .= "<a href='index.php?sec=download&sec2=operation/download/browse&delete_data=" . $row["id"] . "&id_type=" . $row["id_type"] . "' onClick='if (!confirm(\' ".__('Are you sure?')."\')) return false;'><img border='0' src='images/cross.png'></a>";
				}

				array_push ($table->data, $data);

			}
			
			print_table($table);
		}
		echo "</div>";
	}

}

//external_id hidden
echo '<div id="external_id_hidden" style="display:none;">';
	print_input_text('external_id_hidden', $external_id);
echo '</div>';
?>

<script src="include/js/jquery.fileupload.js"></script>
<script src="include/js/jquery.iframe-transport.js"></script>
<script src="include/js/jquery.knob.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

$(document).ready (function () {
	form_upload();
});

function form_upload () {
	var file_list = $('#form-file_releases ul');
	var selectedRF = new Array();

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	$('#drop_file select').change(function() {
		var value = $('#drop_file select').val();

		if (value != 0) {
			$('#drop_file img.img_help').hide();
			$('#drop_file img.delete').show();

			if (selectedRF.indexOf(value) == -1) {
				
				selectedRF.push(value);

				var item = addListItem (100, value, 0);
				item.addClass('working');
				
				var form_div = addForm (item, value);
			}

		} else {
			$('#drop_file img.delete').hide();
			$('#drop_file img.img_help').show();
		}
	});

	$('#drop_file img.delete')
		.css('cursor', 'pointer')
		.click(function(e) {
			var file = $('#drop_file select').find(':selected').val();
			$('#drop_file select').find(':selected').remove();
			$('#drop_file select').val(0).change();
			
			$('form#form-file_releases ul>li[data-file="' + file + '"]>span').click();
			jQuery.ajax({
				url: 'ajax.php',
				type: 'POST',
				dataType: 'json',
				data: {
					page: 'operation/download/browse',
					delete_file: 1,
					file: file
				}
			});
		});

	// Initialize the jQuery File Upload plugin
	$('#form-file_releases').fileupload({
		
		url: 'ajax.php?page=operation/download/browse&upload_file=true',
		
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
			
				// FORM
				var form_div = addForm (data.context, result.filename);
				
			} else {
				// Something has gone wrong!
				data.context.removeClass('working');
				data.context.removeClass('loading');
				data.context.addClass('error');
				data.context.find('i').text(result.message);
			}
		}

	});

	// Prevent the default action when a file is dropped on the window
	$(document).on('drop_file dragover', function (e) {
		e.preventDefault();
	});

	function addListItem (progress, filename, filesize) {
		var tpl = $('<li class="file-release-item" data-file="' + filename + '"><input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
			' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span>'+
			'<div class="download_form"></div></li>');

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

				var i = selectedRF.indexOf(filename);
				if (i != -1) {
					selectedRF.splice(i, 1);
				}

				tpl.fadeOut();
				tpl.slideUp(500, "swing", function() {
					tpl.remove();
				});
			}

		});
		
		// Add the HTML to the UL element
		var item = tpl.appendTo(file_list);
		item.find('input').val(progress).change();

		return item;
	}

	function addForm (item, filename) {
		var form_div = item.find(".download_form");
		form_div.html($("#form-file_release").html());

		item.find("#text-name").val(filename);

		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: {
				page : "operation/download/browse",
				get_external_id : 1
			},
			dataType: "text",
			success: function (data) {
				item.find("#text-external_id").val(data);
			}
		});

		item.find("#submit-crt_btn").click(function(e) {
			e.preventDefault();
			
			$(this).hide();
			item.find("#text-name").prop("disabled", true);
			item.find("#text-external_id").prop("disabled", true);
			item.find("#checkbox-public").prop("disabled", true);
			item.find("#id_category").prop("disabled", true);
			item.find("#id_type").prop("disabled", true);
			item.find("#textarea-description").prop("disabled", true);
			item.removeClass('working');
			item.removeClass('error');
			item.addClass('loading');
			
			if (item.find("#text-external_id").val() == "") {
				external_id = $("#text-external_id_hidden").val();
				item.find("#text-external_id").val(external_id);
			}

			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "operation/download/browse",
					insert_fr: true,
					filename: filename,
					name: function() {
						return item.find("#text-name").val();
					},
					external_id: function() {
						return item.find("#text-external_id").val();
					},
					public: function() {
						return item.find("#checkbox-public").prop("checked") ? 1 : 0;
					},
					id_category: function() {
						return item.find("#id_category").val();
					},
					id_type: function() {
						return item.find("#id_type").val();
					},
					description: function() {
						return item.find("#textarea-description").val();
					},
				},
				dataType: "json",
				success: function (data) {
					
					if (data.status) {

						item.removeClass('loading');
						item.removeClass('working');
						item.removeClass('error');
						item.addClass('suc');
						item.find('span').click();

					} else {

						item.find("#text-name").prop("disabled", false);
						item.find("#text-external_id").prop("disabled", false);
						item.find("#checkbox-public").prop("disabled", false);
						item.find("#id_category").prop("disabled", false);
						item.find("#id_type").prop("disabled", false);
						item.find("#textarea-description").prop("disabled", false);
						item.find("#submit-crt_btn").show();

						item.removeClass('loading');
						item.removeClass('working');
						item.removeClass('suc');
						item.addClass('error');
						item.find("p").text(data.message);

					}
				}
			});
		});

		return form_div;
	}

}

// Form validation
trim_element_on_submit('input[name="free_text"]');
trim_element_on_submit('#text-name');
validate_form("#form-file_release");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_download: 1,
			download_name: function() { return $('#text-name').val() },
			download_id: function() { return $('#id_download').val() }
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This download already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
