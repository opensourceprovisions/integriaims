<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$id = (int) get_parameter ('id');
$incident_creator = get_db_value ("id_creator", "tincidencia", "id_incidencia", $id);

$incident = get_db_row ('tincidencia', 'id_incidencia', $id);

//user with IR and incident creator see the information
$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident));
$standalone_check = enterprise_hook("manage_standalone", array($incident));

if (($check_acl !== ENTERPRISE_NOT_HOOK && !$check_acl) || ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check)) {
 	// Doesn't have access to this page
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation",
		'Trying to access files of ticket #'.$id." '".$titulo."'");
	if (!defined ('AJAX')) {
		include ("general/noaccess.php");
		exit;
	} else {
		return;
	}
}

$is_enterprise = false;

if ($check_acl != ENTERPRISE_NOT_HOOK) {
	$is_enterprise = true;
}

if (!$id) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access files of ticket #".$id);
	if (!defined ('AJAX')) {
		include ("general/noaccess.php");
		exit;
	} else {
		return;
	}
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
				$filename = str_replace (" ", "_", $filename); // Replace conflictive characters
				$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
				$file_tmp = $_FILES["upfile"]['tmp_name'];
				$filesize = $_FILES["upfile"]["size"]; // In bytes

				$values = array(
						"id_incidencia" => $id,
						"id_usuario" => $config['id_user'],
						"filename" => $filename,
						"description" => __('No description available'),
						"size" => $filesize,
						"timestamp" => date("Y-m-d H:i:s")
					);
				$id_attachment = process_sql_insert("tattachment", $values);

				if ($id_attachment) {
					incident_tracking ($id, INCIDENT_FILE_ADDED);
					// Email notify to all people involved in this incident
					// Email in list email-copy
					$email_copy_sql = 'select email_copy from tincidencia where id_incidencia ='.$id.';';
					$email_copy = get_db_sql($email_copy_sql);
					if ($email_copy != "") { 
						mail_incident ($id, $config['id_user'], 0, 0, 2, 7);
					}
					
					if (($config["email_on_incident_update"] != 2) && ($config["email_on_incident_update"] != 4)) {
						mail_incident ($id, $config['id_user'], 0, 0, 2);
					}

					$location = $config["homedir"]."/attachment/".$id_attachment."_".$filename;

					if (copy($file_tmp, $location)) {
						// Delete temporal file
						unlink ($file_tmp);
						$result["status"] = true;
						$result["id_attachment"] = $id_attachment;

						// Adding a WU noticing about this
						$link = "<a target=\"_blank\" href=\"operation/common/download_file.php?type=incident&id_attachment=".$id_attachment."\">".$filename."</a>";
						$nota = "Automatic WU: Added a file to this issue. Filename uploaded: ". $link;
						$timestamp = print_mysql_timestamp();

						$values = array(
								"timestamp" => $timestamp,
								"duration" => 0,
								"id_user" => $config['id_user'],
								"description" => $nota,
								"public" => 1
							);
						$id_workunit = process_sql_insert("tworkunit", $values);
						
						$values = array(
								"id_incident" => $id,
								"id_workunit" => $id_workunit
							);
						process_sql_insert("tworkunit_incident", $values);

						// Updating the ticket
						process_sql_update("tincidencia", array("actualizacion" => $timestamp), array("id_incidencia" => $id));
						enterprise_hook("incidents_run_realtime_workflow_rules", array($id));
						
					} else {
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
		$file = get_incident_file($id, $id_file);

		$html = "";
		if ($file) {
			$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=incident";
			$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

			$html .= "<tr>";
			$html .= "<td valign=top>";
			$html .= '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';

			$stat = stat ($real_filename);
			$html .= "<td valign=top class=f9>".date ("Y-m-d H:i:s", $stat['mtime']);

			$html .= "<td valign=top class=f9>". $file["description"];
			$html .= "<td valign=top>". $file["id_usuario"];
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

// Delete file
$delete_file = (bool) get_parameter ('delete_file');
if ($delete_file) {
	if (give_acl ($config['id_user'], $id_grupo, "IM")) {
		$id_attachment = get_parameter ('id_attachment');
		$filename = get_db_value ('filename', 'tattachment',
			'id_attachment', $id_attachment);
		$sql = sprintf ('DELETE FROM tattachment WHERE id_attachment = %d',
			$id_attachment);
		process_sql ($sql);
		$result_msg = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
		if (!unlink ($config["homedir"].'attachment/'.$id_attachment.'_'.$filename))
			$result_msg = ui_print_error_message (__('Could not be deleted'), '', true, 'h3', true);
		incident_tracking ($id, INCIDENT_FILE_REMOVED);
	} else {
		$result_msg = ui_print_error_message (__('You have no permission'), '', true, 'h3', true);
	}
	
	echo $result_msg;
}

if (!$clean_output) {
	echo "<br>";
	echo "<strong>".__("File formats supported")."</strong>";
	echo print_help_tip (__("Please note that you cannot upload .php or .pl files, as well other source code formats. Please compress that files prior to upload (using zip or gz)"), true);
	
	echo "<form id=\"form-incident_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
	echo 	"<div id=\"drop_file\" style=\"padding:0px 0px;\">";
	echo 		"<table width=\"100%\">";
	echo 			"<td width=\"45%\">";
	echo 				__('Drop the file here');
	echo 			"<td>";
	echo 				__('or');
	echo 			"<td width=\"45%\">";
	echo 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
	echo 		"</table>";
	echo 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
	echo 	"</div>";
	echo 	"<ul></ul>";
	echo "</form>";

	echo "<div id='file_description_table_hook' style='display:none;'>";
	$table = new stdClass;
	$table->width = '100%';
	$table->id = 'incident_file_description';
	$table->class = 'search-table-button';
	$table->data = array();
	$table->data[0][0] = print_textarea ("file_description", 5, 40, '', '', true, __('Description'));
	$table->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
	print_table($table);
	echo "</div>";

}


if ($clean_output) {
	echo '<h1 class="ticket_clean_report_title">'.__("Files")."</h1>";
} else {
	echo "<h3>".__('Files')."</h3>";
}

// Files attached to this incident
$files = get_incident_files ($id);
if ($files === false) {
	$files = array();
	echo '<h4 id="no_files_message">'.__('No files were added to the ticket').'</h4>';
	$hidden = "style=\"display:none;\"";
}else{
	$hidden ='';
}

echo "<div style='width: 90%; margin: 0 auto;'>";
echo "<table id='table-incident_files' $hidden class=listing cellpadding=0 cellspacing=0 width='100%'>";
echo "<tr>";
echo "<th>".__('Filename');
echo "<th>".__('Timestamp');
echo "<th>".__('Description');
echo "<th>".__('ID user');
echo "<th>".__('Size');

if (give_acl ($config['id_user'], $incident['id_grupo'], "IM") && !$clean_output) {
	echo "<th>".__('Delete');
}

foreach ($files as $file) {

	$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=incident";

	$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

	echo "<tr>";
	echo "<td valign=top>";
	echo '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';

	$stat = stat ($real_filename);
	echo "<td valign=top class=f9>" . $file['timestamp'];

	echo "<td valign=top class=f9>". $file["description"];
	echo "<td valign=top>". $file["id_usuario"];
	echo "<td valign=top>". byte_convert ($file['size']);

	// Delete attachment
	if (give_acl ($config['id_user'], $incident['id_grupo'], 'IM') && !$clean_output) {
		echo "<td>". '<a class="delete" name="delete_file_'.$file["id_attachment"].'" href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'&tab=files&id_attachment='.$file["id_attachment"].'&delete_file=1#incident-operations">
		<img src="images/cross.png"></a>';
	}

}

echo "</table>";
echo "</div>";

?>

<script src="include/js/jquery.fileupload.js"></script>
<script src="include/js/jquery.iframe-transport.js"></script>
<script src="include/js/jquery.knob.js"></script>

<script type="text/javascript">

$(document).ready (function () {
	form_upload();
});

function form_upload () {
	var file_list = $('#form-incident_files ul');

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#form-incident_files').fileupload({
		
		url: 'ajax.php?page=operation/incidents/incident_files&upload_file=true&id=<?php echo $id; ?>',
		
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
				addForm (data.context, result.id_attachment);
				
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
		var tpl = $('<li><input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
			' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span>'+
			'<div class="incident_file_form"></div></li>');
		
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
		var item = tpl.appendTo(file_list);
		item.find('input').val(progress).change();

		return item;
	}

	function addForm (item, file_id) {
		
		item.find(".incident_file_form").html($("#file_description_table_hook").html());

		item.find("span").click(function(e) {
			addFileRow(file_id);
		});

		item.find("#submit-crt_btn").click(function(e) {
			e.preventDefault();
			
			$(this).hide();
			item.find("#textarea-file_description").prop("disabled", true);
			item.removeClass('working');
			item.removeClass('error');
			item.addClass('loading');

			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "operation/incidents/incident_files",
					update_file_description: true,
					id: <?php echo $id; ?>,
					id_attachment: file_id,
					file_description: function() { return item.find("#textarea-file_description").val() }
				},
				dataType: "json",
				success: function (data) {
					
					if (data.status) {

						item.removeClass('loading');
						item.addClass('suc');
						item.find('span').click();

					} else {

						item.find("#textarea-file_description").prop("disabled", false);
						item.find("#submit-crt_btn").show();

						item.removeClass('loading');
						item.removeClass('suc');
						item.addClass('error');
						item.find("p").text(data.message);

					}
				}
			});
		});

	}

	function addFileRow (file_id) {
		var no_files_message = $("#no_files_message");
		var table_files = $("#table-incident_files");

		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: {
				page: "operation/incidents/incident_files",
				get_file_row: true,
				id: <?php echo $id; ?>,
				id_attachment: file_id
			},
			dataType: "html",
			success: function (data) {
				if (no_files_message.length > 0) {
					no_files_message.remove();
					table_files.show();
				}
				table_files.find("tbody").append(data);
			}
		});
	}

}

</script>
