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

$contact = get_db_row ('tcompany_contact', 'id', $id);

$read = check_crm_acl ('other', 'cr', $config['id_user'], $contact['id_company']);
if (!$read) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contact files without permission");
	include ("general/noaccess.php");
	exit;
}

//Upload new file
$filename = get_parameter ('upfile', false);

//~ if (give_acl ($config['id_user'], 0, "CR") && (bool)$filename) {
        //~ $result_msg = '<h3 class="error">'.__('No file was attached').'</h3>';
        //~ /* if file */
        //~ if ($filename != "") {
                //~ $file_description = get_parameter ("file_description",
                                //~ __('No description available'));
//~ 
                //~ // Insert into database
                //~ $filename_real = safe_output ( $filename ); // Avoid problems with blank spaces
                //~ $file_temp = sys_get_temp_dir()."/$filename_real";
                //~ $file_new = str_replace (" ", "_", $filename_real);
                //~ $filesize = filesize($file_temp); // In bytes
//~ 
                //~ $sql = sprintf ('INSERT INTO tattachment (id_contact, id_usuario,
                                //~ filename, description, size)
                                //~ VALUES (%d, "%s", "%s", "%s", %d)',
                                //~ $id, $config['id_user'], $file_new, $file_description, $filesize);
//~ 
                //~ $id_attachment = process_sql ($sql, 'insert_id');
                //~ 
		//~ echo '<h3 class="suc">'.__('File added').'</h3>';
//~ 
                //~ // Copy file to directory and change name
                //~ $file_target = $config["homedir"]."/attachment/".$id_attachment."_".$file_new;
//~ 
                //~ if (! copy ($file_temp, $file_target)) {
                        //~ echo '<h3 class="error">'.__('File cannot be saved. Please contact Integria administrator about this error').'</h3>';
                        //~ $sql = sprintf ('DELETE FROM tattachment
                                        //~ WHERE id_attachment = %d', $id_attachment);
                        //~ process_sql ($sql);
                //~ } else {
                        //~ // Delete temporal file
                        //~ unlink ($file_temp);
                //~ }
        //~ }  else {
                //~ $error = $_FILES['userfile']['error'];
                //~ $error = 4;
                //~ switch ($error) {
                //~ case 1:
                        //~ $result_msg = '<h3 class="error">'.__('File is too big').'</h3>';
                        //~ break;
                //~ case 3:
                        //~ $result_msg = '<h3 class="error">'.__('File was partially uploaded. Please try again').'</h3>';
                        //~ break;
                //~ case 4:
                        //~ $result_msg = '<h3 class="error">'.__('No file was uploaded').'</h3>';
                        //~ break;
                //~ default:
                        //~ $result_msg = '<h3 class="error">'.__('Generic upload error').'(Code: '.$_FILES['userfile']['error'].')</h3>';
                //~ }
//~ 
                //~ echo $result_msg;
        //~ }
//~ }

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
                $result_msg = ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
                if (!unlink ($config["homedir"].'attachment/'.$id_attachment.'_'.$filename))
                        $result_msg = ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
        } else {
            $result_msg = ui_print_error_message (__('You have no permission'), '', true, 'h3', true);
        }

        echo $result_msg;
}

echo '<div id="upload_result"></div>';

//echo "<h3>".__('Add file')."</h3>";

//~ echo "<div id='upload_control' style='width: 80%;margin: 0 auto; clear:both'>";
//~ 
//~ $table->width = '100%';
//~ $table->data = array ();
//~ 
//~ 
//~ $table->data[0][0] = "<strong>".__("File formats supported")."</strong>";
//~ $table->data[0][0] .= print_help_tip (__("Please note that you cannot upload .php or .pl files, as well other source code formats. Please compress that files prior to upload (using zip or gz)"), true);
//~ $table->data[1][0] = print_textarea ('file_description', 8, 1, '', "style='resize:none'", true, __('Description'));
//~ 
//~ $action = 'index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=files';
//~ 
//~ $into_form = print_table ($table, true);
//~ $into_form .= '<div class="button" style="width: '.$table->width.'">';
//~ $into_form .= print_button (__('Upload'), 'upload', false, '', 'class="sub next"', true);
//~ $into_form .= '</div>';
//~ $into_form .= print_input_hidden ('id', $id, true);
//~ $into_form .= print_input_hidden ('upload_file', 1, true);
//~ 
//~ // Important: Set id 'form-add-file' to form. It's used from ajax control
//~ print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub', 'button-upload');
//~ 
//~ echo '</div>';

echo "<strong>".__("File formats supported")."</strong>";
echo print_help_tip (__("Please note that you cannot upload .php or .pl files, as well other source code formats. Please compress that files prior to upload (using zip or gz)"), true);

echo "<form id=\"form-contact_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
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
$table->id = 'contact_file_description';
$table->class = 'search-table-button';
$table->data = array();
$table->data[0][0] = print_textarea ("file_description", 5, 40, '', '', true, __('Description'));
$table->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
print_table($table);
echo "</div>";

$files = crm_get_contact_files ($id);

echo "<h3>".__('Files')."</h3>";

if (!$files) {
	echo ui_print_error_message (__("This contact doesn't have any file associated"), '', true, 'h3', true);
}
else {

	$table->class = "listing";
	$table->width = "100%";
	$table->id = "contact_files";
	$table->head[0] = __("Filename");
	$table->head[1] = __("Timestamp");
	$table->head[2] = __("Description");
	$table->head[3] = __("Size");

	if (give_acl ($config['id_user'], 0, "CW")) {
        	$table->head[4] = __('Delete');
	}
	
	$table->data = array();

	foreach ($files as $f) {
		$data = array();

     	$link = "operation/common/download_file.php?id_attachment=".$f["id_attachment"]."&type=contact&id_contact=" . $id;

        $data[0] = '<a target="_blank" href="'.$link.'">'. $f['filename'].'</a>';	
		
        $real_filename = $config["homedir"]."/attachment/".$f["id_attachment"]."_".rawurlencode ($f["filename"]);    
        
        $stat = stat ($real_filename);
        $data[1] = date ("Y-m-d H:i:s", $stat['mtime']);

		$data[2] = $f["description"];

		$data[3] = byte_convert ($f['size']);

	        if (give_acl ($config['id_user'], 0, "CW")) {
                	$data[4] = '<a class="delete" name="delete_file_'.$f["id_attachment"].'" href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$id.'&op=files&id_attachment='.$f["id_attachment"].'&delete_file=1"><img src="images/cross.png"></a>';
        	}

		array_push($table->data, $data);
	}

	print_table($table);
}
?>

<script src="include/js/jquery.fileupload.js"></script>
<script src="include/js/jquery.iframe-transport.js"></script>
<script src="include/js/jquery.knob.js"></script>
<script type="text/javascript">

$(document).ready (function () {
	form_upload();
});

function form_upload () {
	var file_list = $('#form-contact_files ul');

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#form-contact_files').fileupload({
		
		url: 'ajax.php?page=operation/contacts/contact_detail&upload_file=true&id=<?php echo $id; ?>',
		
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
			console.log(data);
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
		var tpl = $('<li><input type="text" id="input-progress" value="0" data-width="55" data-height="55"'+
			' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span>'+
			'<div class="contact_files_form"></div></li>');
		
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
		
		item.find(".contact_files_form").html($("#file_description_table_hook").html());

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
					page: "operation/contacts/contact_detail",
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
		var table_files = $("#contact_files");

		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: {
				page: "operation/contacts/contact_detail",
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