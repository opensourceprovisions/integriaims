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

$id_incident = (int) get_parameter ('id');
$delete_file = (bool) get_parameter ('delete_file');

if (!$id_incident) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access chat of ticket #".$id_incident);
	include ("general/noaccess.php");
	exit;
}

$incident = get_db_row ('tincidencia', 'id_incidencia', $id_incident);

//user with IR and incident creator see the information
if (! give_acl ($config["id_user"], $incident['id_grupo'], "IR")
	&& ($incident['id_creator'] != $config['id_user'])) {
 	// Doesn't have access to this page
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation",
		'Trying to access chat of ticket #'.$id_incident." '".$titulo."'");
	include ("general/noaccess.php");
	exit;
}

echo '<h3>'.__('Ticket').' #'.$id_incident.' - '.$incident['titulo'].'</h3>';

echo '<div class="result"></div>';

$table = null;

$table->width = '95%';
$table->style[1] = 'text-align: right; vertical-align: top;';

$table->data[0][0] = '<div id="chat_box" style="width: 95%;
	height: 300px; background: #ffffff; border: 1px inset black;
	overflow: auto; padding: 10px;"></div>';
$table->data[0][1] = '';

//Show the button to save only for assigned user
$exists_as_creator = get_db_row_filter('tincidencia',
	array('id_usuario' => $config['id_user'], 'id_incidencia' => $id_incident));
$exists_as_creator = !empty($exists_as_creator);

if ($exists_as_creator) {
$table->data[0][1] = '<span id="saving_in_progress" style="display: none;"><img src="images/spinner.gif" />' . __('Saving chat') . '</span>' .
	print_button(__("Save chat into workunit"), 'save', false, 'save_message()',
	'class="sub save" style="width: 100%"', true);
}

$table->data[0][1] .= '<h4>' . __('Users Online') . '</h4>' .
	'<div id="userlist_box" style="width: 75% !important; height: 200px !important;
		height: 300px; background: #ffffff; border: 1px inset black;
		overflow: auto; padding: 10px;"></div>';
$table->data[1][0] = print_input_text('message_box', '', '',
	100, 150, true);
$table->data[1][1] = print_button(__('Send'), 'send', false, 'send_message()',
	'class="sub next" style="width: 100%"', true);
//$table->data[1][1] .= print_button(__("Save chat into workunit"), 'save', false, 'save_message()',
//	'class="sub next" style="width: 100%"', true);

print_table($table);

?>
<span id="chat_active" style="display: none;">1</span>
<script type="text/javascript">
	var id_incident = <?php echo $id_incident; ?>;
	var page_ajax = 'operation/incidents/incident_chat.ajax';
	
	var global_counter_chat = 0;
	var chat_log = '';
	var user_move_scroll = false;
	var first_time = true;
	
	$(document).ready(function() {
		$("input[name=\"message_box\"]").keydown(function(e){
			//Enter key.
			if (e.keyCode == 13) {
				send_message();
			}
		});
		
		init_webchat();
	});
	
	$(window).unload(function () {
		exit_webchat();
	});
	
	function init_webchat() {
		send_login_message();
		long_polling_check_messages();
		check_users();
	}
	
	function save_message() {
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['save_message'] = 1;
		
		$("#saving_in_progress").width($("#button-save").width());
		$("#button-save").hide();
		$("#saving_in_progress").show();
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				$("#button-save").show();
				$("#saving_in_progress").hide();
				if (data['correct'] == 1) {
					alert('<?php echo __('Sucessful save the chat into workunit'); ?>');
				}
				else {
					alert('<?php echo __('Unsucessful save the chat into workunit'); ?>');
				}
			}
		});
	}
	
	function check_users() {
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['check_users'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					$("#userlist_box").html(data['users']);
				}
			}
		});
	}
	
	function long_polling_check_messages() {
		//Only it is because the Integria have a freak mode for tabs, I
		//think that I hate the jquery-ui-tabs.
		tab_selected_index = $("#tabs > ul").tabs ('option', 'selected');
		
		//Sorry for the magic number.
		if (tab_selected_index != 9) {
			exit_webchat();
			return; //Exit for the chat.
		}
		
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['long_polling_check_messages'] = 1;
		parameters['global_counter'] = global_counter_chat;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			timeout: 5000,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					check_users();
					
					if (first_time) {
						print_messages({
							0: {'type' : 'notification',
								'text': '<?php echo __('Connection established...get last 24h messages...');?>'}
							}, true);
						first_time = false;
					}
					global_counter_chat = data['global_counter'];
					print_messages(data['log']);
				}
				else {
					print_messages({
						0: {'type' : 'error',
							'text': '<?php echo __('Error in connection.');?>'}
						}, false);
				}
				long_polling_check_messages();
			},
			error: function(request, status, err) {
				long_polling_check_messages();
			}
		});
	}
	
	function print_messages(messages, clear_chat_box) {
		//event_add_text = true;
		
		if (typeof(clear_chat_box) == 'undefined') {
			clear_chat_box = false;
		}
		
		var html = '';
		
		$.each(messages, function(key, message) {
			html = html + '<div ';
			
			if (message['type'] == 'error') {
				html = html +  "style='color: red; font-style: italic;'";
			}
			else if (message['type'] == 'notification') {
				html = html +  "style='color: grey; font-style: italic;'";
			}
			else if (message['type'] == 'message') {
				html = html +  "style='color: black; font-style: normal;'";
			}
			html = html + '>';
			
			if (message['type'] != 'message') {
				html = html + message['text'];
			}
			else {
				html = html +
					'<span style="color: grey; font-style: italic;">' +
					message['human_time'] + '</span>';
				html = html + ' ' +
					'<span style="color: black; font-weight: bolder;">' +
					message['user_name'] + ':&gt; </span>';
				html = html + ' ' + message['text'];
			}
			
			html = html + '</div>';
		});
		
		
		
		if (clear_chat_box) {
			$("#chat_box").html(html);
		}
		else {
			$("#chat_box").append(html);
		}
		
		$("#chat_box").scrollTop($("#chat_box").attr('scrollHeight'));
	}
	
	function send_message() {
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['send_message'] = 1;
		parameters['message'] = $("input[name=\"message_box\"]").val();
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					$("input[name=\"message_box\"]").val('');
				}
				else {
					print_messages({
						0: {'type' : 'error',
							'text': '<?php echo __('Error sendding message.');?>'}
						}, false);
				}
			}
		});
	}
	
	function exit_webchat() {
		send_logout_message();
		get_last_global_counter();
	}
	
	function send_login_message() {
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['send_login'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
				}
				else {
					print_messages({
						0: {'type' : 'error',
							'text': '<?php echo __('Error login.');?>'}
						}, false);
				}
			}
		});
	}
	
	function send_logout_message() {
		var parameters = {};
		parameters['page'] = page_ajax;
		parameters['id'] = id_incident;
		parameters['send_logout'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
			}
		});
	}
</script>
