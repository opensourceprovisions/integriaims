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

if ($id != 0) {
	
	$read_permission = check_crm_acl ('company', 'cr', $config['id_user'], $id);
	if (! $read_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to company files");
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

		$sql = sprintf("INSERT INTO tattachment (id_company, id_usuario, filename, description, timestamp, size) VALUES (%d, '%s', '%s', '%s', '%s', %d)", $id, $config["id_user"], $filename, $description, date('Y-m-d H:i:s'), $size);
		$id_attach = process_sql ($sql, 'insert_id');

		$filename_encoded = $id_attach . "_" . $filename;
		
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$filename_encoded;

		if (!(copy($file_tmp, $file_target))){
			echo ui_print_error_message (__('Could not be attached'), '', true, 'h3', true);
		} else {
			// Delete temporal file
			echo ui_print_success_message (__('Successfully attached'), '', true, 'h3', true);
			$location = $file_target;
			unlink ($file_tmp);
		}
		// Create record in tattachment
	}
}


// Control to upload file

//~ echo '<div class="divform">';
//~ echo '<table class="search-table">';
		//~ echo '<tr>';
		//~ echo '<td>';
//~ echo print_button (__('Upload a new file'), 'add_link', false, '$(\'#upload_div\').slideToggle (); return false', 'class="sub upload"');
//~ echo '</div>';
//~ echo '<div id="upload_div" style="display: none;" class="">';
//~ $target_directory = 'attachment';
//~ $action = "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=files&upload=1";				
//~ $into_form = "<input type='hidden' name='directory' value='$target_directory'><b>Description</b><input type=text name=description size=25>";
//~ print_input_file_progress($action,$into_form,'','sub upload');
//~ echo '</td>';
//~ echo '</tr>';
//~ echo '</table>';
//~ echo '</div>';
echo "<strong>".__("File formats supported")."</strong>";
echo print_help_tip (__("Please note that you cannot upload .php or .pl files, as well other source code formats. Please compress that files prior to upload (using zip or gz)"), true);

echo "<form id=\"form-company_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
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
$table->id = 'company_files_description';
$table->class = 'search-table-button';
$table->data = array();
$table->data[0][0] = print_textarea ("file_description", 5, 40, '', '', true, __('Description'));
$table->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
print_table($table);
echo "</div>";
// List of lead attachments

$sql = "SELECT * FROM tattachment WHERE id_company = $id ORDER BY timestamp DESC";
$files = get_db_all_rows_sql ($sql);

if ($files !== false) {
	$files = print_array_pagination ($files, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=files");
	unset ($table);
	$table->width = "100%";
	$table->class = "listing";
	$table->id = "company_files";
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
		
		$data[0] = "<a href='operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=company'>".$file["filename"] . "</a>";
		$data[1] = $file["description"];
		$data[2] = byte_convert($file["size"]);
		$data[3] = $file["timestamp"];

		// Todo. Delete files owner of lead and admins only
		if ( (dame_admin($config["id_user"])) || ($file["id_usuario"] == $config["id_user"]) ){
			$data[4] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=files&deletef=".$file["id_attachment"]."'><img src='images/cross.png'></a>";
		}

		array_push ($table->data, $data);
		array_push ($table->rowstyle, $style);
	}
	print_table ($table);
}
else {
	echo "<h3>". __('There is no files attached for this lead')."</h3>";
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
	var file_list = $('#form-company_files ul');

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#form-company_files').fileupload({
		
		url: 'ajax.php?page=include/ajax/companies&upload_file=true&id=<?php echo $id; ?>',
		
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
			'<div class="company_files_form"></div></li>');
		
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
		
		item.find(".company_files_form").html($("#file_description_table_hook").html());

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
					page: "include/ajax/companies",
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

					}
					else {

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
		var table_files = $("#company_files");

		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: {
				page: "include/ajax/companies",
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