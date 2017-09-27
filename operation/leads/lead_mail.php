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


if (! $id) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead forward");
	include ("general/noaccess.php");
	exit;
}

$write_permission = check_crm_acl ('lead', 'cw', $config['id_user'], $id);
$manage_permission = check_crm_acl ('lead', 'cm', $config['id_user'], $id);
if (!$write_permission && !$manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead forward");
	include ("general/noaccess.php");
	exit;
}

$id_template = get_parameter ("id_template");

$lead = get_db_row('tlead','id',$id);

$user = get_db_row("tusuario", "id_usuario", $config["id_user"]);
$template = get_db_row("tcrm_template", "id", $id_template);
$id_company = $lead["id_company"];

$from = get_parameter ("from", $user["direccion"]);
$to = get_parameter ("to", $lead["email"]);
$subject = get_parameter ("subject", $template["subject"]);
$mail = get_parameter ("mail", $template["description"]);
$send = (int) get_parameter ("send",0);
$cco = get_parameter ("cco", "");

// Send mail
if ($send) {
	if (($subject != "") AND ($from != "") AND ($to != "")) {
		echo ui_print_success_message (__('Mail queued'), '', true, 'h3', true);
		$cc = $config["mail_from"];
		
		$subject_mail = "[Lead#$id] " . $subject;

		// ATTACH A FILE IF IS PROVIDED
		$upfiles = (string) get_parameter("upfiles");
		$upfiles = json_decode(safe_output($upfiles));
		if (!empty($upfiles)) {
			// Save the attachments
			$bad_files = array();
			foreach ($upfiles as $key => $attachment) {
				$size = filesize ($attachment);
				$filename = basename($attachment);

				$values = array(
						'id_lead' => $id,
						'id_usuario' => $config["id_user"],
						'filename' => $filename,
						'description' => __('Mail attachment'),
						'timestamp' => date('Y-m-d H:i:s'),
						'size' => $size
					);
				$id_attachment = process_sql_insert('tattachment', $values);

				if ($id_attachment) {
					// Copy file to directory and change name
					$ds = DIRECTORY_SEPARATOR;
					$filename_encoded = $id_attachment . "_" . $filename;
					$file_target = $config["homedir"].$ds."attachment".$ds.$filename_encoded;

					if (!copy($attachment, $file_target)) {
						$bad_files[] = $key;
						unlink ($attachment);
						process_sql_delete('tattachment', array('id_attachment' => $id_attachment));
					}
				} else {
					$bad_files[] = $key;
					unlink ($attachment);
				}
			}
			foreach ($bad_files as $index) {
				unset($upfiles[$index]);
			}

			$upfiles = implode( ",", $upfiles);
		} else {
			$upfiles = false;
		}

		integria_sendmail ($to, $subject_mail, html_entity_decode($mail), $upfiles, "", $from, true, $cc, "X-Integria: no_process");

		if ($cco != "")
			integria_sendmail ($cco, $subject_mail, html_entity_decode($mail), $upfiles, "", $from, true);

		// Lead update
		if ($lead["progress"] == 0 ){
			//Update lead progress is was on 0%
			$sql = sprintf ('UPDATE tlead SET modification = "%s", progress = %d WHERE id = %d',
		date('Y-m-d H:i:s'), 10, $id);
		} else {
			$sql = sprintf ('UPDATE tlead SET modification = "%s" WHERE id = %d',
		date('Y-m-d H:i:s'), $id);
		}
		process_sql ($sql);		

		// Update tracking
		$sql = sprintf ('INSERT INTO tlead_history (id_lead, id_user, timestamp, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, "Send mail from CRM");
		process_sql ($sql);

		// Update activity
		$datetime =  date ("Y-m-d H:i:s");
		$comments = __("Send email from CRM"). "&#x0d;&#x0a;".__("Subject"). " : ". $subject . "&#x0d;&#x0a;" . $mail; // this adds &#x0d;&#x0a; 
		$sql = sprintf ('INSERT INTO tlead_activity (id_lead, written_by, creation, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, $comments);
		process_sql ($sql);

	} else {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	}
}


// Mark with case ID

// Replace mail macros
/*_DEST_NAME_ -> Lead fullname
_DEST_EMAIL_ -> Lead email
_SRC_NAME_ -> Current user fullname
_SRC_EMAIL_ -> Current user email
*/

$mail = str_replace ("_DEST_NAME_", $lead["fullname"], $mail);
$mail = str_replace ("_DEST_EMAIL_", $lead["email"], $mail);
$mail = str_replace ("_SRC_NAME_", $user["nombre_real"], $mail);
$mail = str_replace ("_SRC_EMAIL_", $user["direccion"], $mail);

$sql = "SELECT id, name FROM tcrm_template WHERE id_language = '". $lead["id_language"]. "' ORDER BY name DESC";

$id_template = (int) get_parameter ("id_template");

echo "<div class='divform'>";
	// Show form with available templates for this useraco
	echo '<form method="post" id="lead_mail_filter">';
		echo "<table width=100% class='search-table'>";
		echo "<tr><td>";
		echo print_select_from_sql ($sql, 'id_template', $id_template, '', __("None"), 0, true, false, true, __("CRM Template to use"));
		echo "</td></tr><tr><td>";
		print_submit_button (__('Apply'), 'apply_btn', false, 'class="sub upd"', false);
		print_input_hidden ('id', $id);
		echo "</td></tr></table>";
	echo "</form>";
echo "</div>";

$sql = "SELECT `description` FROM tlead_activity 
			WHERE id_lead = $id
			ORDER BY creation DESC LIMIT 1";
			
$result = process_sql ($sql);

if ($result !== false) {
	$last_email = $result[0]['description'];
} else {
	$last_email = "";
}

$table = new StdClass();
$table->width = "100%";
$table->class = "search-table-button";
$table->data = array ();
$table->size = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';

$table->colspan[1][0] = 3;
$table->colspan[2][0] = 3;
$table->colspan[3][0] = 3;
$table->colspan[4][0] = 3;
$table->colspan[5][0] = 3;

if (!$subject) {
	$subject = __("Commercial Information");
}

$table->data[0][0] = print_input_text ("from", $from, "", 30, 100, true, __('From'));
$table->data[0][1] = print_input_text ("to", $to, "", 30, 100, true, __('To'));
$table->data[0][2] = print_input_text ("cco", $cco, "", 30, 100, true, __('Send a copy to'));
$table->data[1][0] = '<label id="label-text-subject" for="text-subject">'.__("Subject").'</label> [Lead#'.$id.']&nbsp;'.print_input_text ("subject", $subject, "", 80, 100, true);
$table->data[2][0] = print_textarea ("mail", 10, 1, $mail, 'style="height:350px;"', true, __('E-mail'));

$html = "<div id=\"lead_files\" class=\"fileupload_form\">";
$html .= 	"<div id=\"drop_file\" style=\"padding:0px 0px;\">";
$html .= 		"<table width=\"100%\">";
$html .= 			"<td width=\"45%\">";
$html .= 				__('Drop the file here');
$html .= 			"<td>";
$html .= 				__('or');
$html .= 			"<td width=\"45%\">";
$html .= 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
$html .= 			"<tr>";
$html .= 		"</table>";
$html .= 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
$html .= 		"<input type=\"hidden\" name=\"upfiles\" id=\"upfiles\" />"; // JSON STRING
$html .= 	"</div>";
$html .= 	"<ul></ul>";
$html .= "</div>";
//$table->data[3][0] = print_container('attachment_upload_container', __('Attachments'), $html, 'closed', true, false);

$table->data[4][0] = print_submit_button (__('Send email'), 'apply_btn', false, 'class="sub upd"', true);
$table->data[4][0] .= print_input_hidden ('id', $id, true);
$table->data[4][0] .= print_input_hidden ('send', 1, true);

$table->data[5][0] = print_textarea ("last_mail", 10, 1, $last_email, 'style="height:350px;"', true, __('Last E-mail'));

echo "<div class='divresult'>";
echo '<form method="post" id="lead_mail_go">';
print_table ($table);
echo "<h4>".__('Attachments')."</h4>";
echo $html;
echo "</form>";
echo "</div>";

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="include/js/jquery.iframe-transport.js"></script>
<script type="text/javascript" src="include/js/jquery.knob.js"></script>


<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>
<script type="text/javascript">
tinymce.init({
  selector: 'textarea',
force_br_newlines : true,
    force_p_newlines : false,
    forced_root_block : false,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table contextmenu paste code'
  ],
  menubar: false,
  toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
  content_css: 'include/js/tinymce/integria.css',

});

</script>

<script type="text/javascript" >

function form_upload () {
	// Input will hold the JSON String with the files data
	var input_upfiles = $('input#upfiles');
	// Javascript array will hold the files data
	var upfiles = [];

	function updateInputArray() {
		input_upfiles.val(JSON.stringify(upfiles));
	}

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#lead_files').fileupload({
		
		url: 'ajax.php?page=operation/leads/lead&upload_file=true&id='+<?php echo $id; ?>,
		
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

				// Add the new element
				upfiles.push(result.location);
				updateInputArray();
				// Add a listener to remove the item in case of removing the list item
				data.context.find('span').click(function() {
					var index = upfiles.indexOf(result.location);
					if (index > -1) {
						$.ajax({
							type: 'POST',
							url: 'ajax.php',
							data: {
								page: "operation/incidents/lead",
								remove_tmp_file: true,
								location: upfiles[index],
								id: "<?php echo $id; ?>"
							}
						});
						upfiles.splice(index, 1);
						updateInputArray();
					}
				});
				
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
		var tpl = $('<li>'+
						'<input type="text" id="input-progress" value="0" data-width="55" data-height="55"'+
						' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" />'+
						'<p></p>'+
						'<span></span>'+
						'<div class="incident_file_form"></div>'+
					'</li>');
		
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
		var item = tpl.appendTo($('#lead_files ul'));
		item.find('input').val(progress).change();

		return item;
	}
}

form_upload();

validate_form("#lead_mail_go");
// Rules: #text-from
rules = {
	required: true,
	email: true
};
messages = {
	required: "<?php echo __('Email from required')?>",
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-from', rules, messages);
// Rules: #text-to
rules = {
	required: true,
	email: true
};
messages = {
	required: "<?php echo __('Email to required')?>",
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-to', rules, messages);
// Rules: #text-cco
rules = {
	email: true
};
messages = {
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-cco', rules, messages);

</script>
