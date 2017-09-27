<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

require_once ('include/functions_db.php');
require_once ('include/functions_agenda.php');

if (! give_acl ($config['id_user'], 0, "AR")) {
 	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access agenda");
	include ("general/noaccess.php");
	exit;
}

$show_agenda_entry 		= (bool) get_parameter('show_agenda_entry');
$update_agenda_entry 	= (bool) get_parameter('update_agenda_entry');
$delete_agenda_entry 	= (bool) get_parameter('delete_agenda_entry');

$id = (int) get_parameter('id');
$permission = agenda_get_entry_permission($config['id_user'], $id);

if ($show_agenda_entry) {
	
	$date = (string) get_parameter('date');
	
	$entry = array();
	if (!empty($id)) {
		$entry = get_db_row('tagenda', 'id', $id);
		if (!$entry) {
			$entry = array();
		}
		else {
			// Get the entry privacy
			$groups = get_db_all_rows_filter('tagenda_groups', array('agenda_id' => $id), 'group_id');
			if (empty($groups)) $groups = array();
			// Extract the groups from the result
			$groups = array_map(function ($item) {
				return $item['group_id'];
			}, $groups);
			
			if (!empty($groups))
				$entry['groups'] = $groups;
			else
				$entry['groups'] = array(0);
		}
	}
	
	echo "<div id='calendar_entry'>";
	
	if (!empty($id) && !$permission && !$entry['public']) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to view an agenda entry");
		include ("general/noaccess.php");
		exit;
	}
	
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	$table->rowspan = array ();
	$table->data = array ();
	
	$table->colspan[0][0] = 2;
	$table->data[0][0] = print_input_text ('entry_title', $entry['title'], '', 40, 100, true, __('Title'));
	$table->data[0][2] = print_checkbox ('entry_public', $entry['public'], $entry['public'], true, __('Public'));
	
	if (!$entry['duration']) {
		$entry['duration'] = 0;
	}
	$table->data[1][0] = print_input_text ('entry_duration', $entry['duration'], '', 6, 6, true, __('Duration in hours'));
	
	$alarms = array ();
	$alarms[60] = __('One hour');
	$alarms[120] = __('Two hours');
	$alarms[240] = __('Four hours');
	$alarms[1440] = __('One day');
	$table->data[1][1] = print_label(__('Alarm'), 'entry_alarm', 'select', true);
	$table->data[1][1] .= html_print_select ($alarms, 'entry_alarm', $entry['alarm'], '', __('None'), 0, true, false, false);
	
	$table->rowspan[1][2] = 2;
	$table->data[1][2] = html_print_entry_visibility_groups($config['id_user'], $entry['groups'], true);
	
	if (!$entry['timestamp']) {
		if (!$date) {
			$date = date ('Y-m-d');
		}
		$time = date ('H:i');
	} else {
		if (!$date) {
			$date = date ('Y-m-d', $entry['timestamp']);
		}
		$result	= explode( " ", $entry['timestamp']);
		$time = $result[1];
	}
	$table->data[2][0] = print_input_text ('entry_date', $date, '', 10, 20, true, __('Date'));
	$table->data[2][1] = print_input_text ('entry_time', $time, '', 10, 20, true, __('Time'));
	
	$table->colspan[3][0] = 3;
	$table->data[3][0] = print_textarea ('entry_description', 4, 50, $entry['description'], '', true, __('Description'));
	
	$button = print_button (__('Cancel'), 'cancel', false, '', 'class="sub blank"', true);
	
	if (empty($id)) {
		$button .= print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
	} elseif ($permission) {
		$button .= print_button (__('Delete'), 'delete', false, '', 'class="sub delete"', true);
		$button .= print_submit_button (__('Update'), 'create_btn', false, 'class="sub upd"', true);
	}
	
	$table->data['button'][0] = $button;
	$table->colspan['button'][0] = 3;
	
	echo '<form method="post">';
	print_table ($table);
	echo '</form>';
	echo "</div>";
	
}

if ($update_agenda_entry) {
	$config['mysql_result_type'] = MYSQL_ASSOC;
	if (!empty($id) && !$permission) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update an agenda entry");
		include ("general/noaccess.php");
		exit;
	}
	
	$title 			= (string) get_parameter('title');
	$description 	= (string) get_parameter('description');
	$date 			= (string) get_parameter('date', date('Y-m-d'));
	$time 			= (string) get_parameter('time', date('H:i'));
	$duration 		= (int) get_parameter('duration');
	$public 		= (int) get_parameter('public');
	$alarm 			= (int) get_parameter('alarm');
	$groups 		= get_parameter('groups', array());
	
	// The 0 group is the 'none' option
	if (in_array(0, $groups))
		$groups = array();
	
	$values = array(
			'public' => $public,
			'alarm' => $alarm,
			'timestamp' => $date . ' ' . $time,
			'id_user' => $config['id_user'],
			'title' => $title,
			'duration' => $duration,
			'description' => $description
		);
	
	$result = false;
	if (empty($id)) {
		$old_entry = array();
		$result = process_sql_insert('tagenda', $values);
	} else {
		$old_entry = get_db_row ('tagenda', 'id', $id);
		$result = process_sql_update('tagenda', $values, array('id' => $id));
	}
	
	if ($result !== false) {
		if (empty($id))
			$groups = agenda_process_privacy_groups($result, $public, $groups);
		else
			$groups = agenda_process_privacy_groups($id, $public, $groups);

		$i = 0;
		$str_groups = "";
		foreach ($groups as $key => $id_group) {
			$group_name = groups_get_group_name($id_group);
			if ($i == 0) {
				$str_groups = $group_name;
			} else {
				$str_groups .= ','.$group_name;
			}
			$i++;
		}

		$full_path = $config['homedir'].'/attachment/tmp/';
		$ical_text = create_ical ($date.' '.$time, $duration, $config['id_user'], $description, "Integria imported event: ".safe_output($title));
		$full_filename = $full_path.$config['id_user'].'-'.microtime(true).'.ics';
		$full_filename_h = fopen($full_filename, 'a');
		fwrite($full_filename_h, $ical_text);
		fclose($full_filename_h);

		$nombre = get_db_value('nombre_real', 'tusuario', 'id_usuario', $config['id_user']);
		$email = get_db_value('direccion', 'tusuario', 'id_usuario', $config['id_user']);
		
		//Macros
		$MACROS["_entry-username_"] = $config['id_user'];
		$MACROS["_entry-realname_"] = $nombre;
		
		$MACROS["_entry-date_"] = $date;
		$MACROS["_entry-time_"] = $time;
		$MACROS["_entry-title_"] = $title;
		$MACROS["_entry-description_"] = $description;
		$MACROS["_group_"] = $str_groups;

		$MACROS["_entry-date-old_"] = $old_entry['timestamp'];
		$MACROS["_entry-title-old_"] = $old_entry['title'];
		$MACROS["_entry-description-old_"] = $old_entry['description'];
		
		
		
		
		$emails = array();
		$users = false;
		if ($public) {
			$users_all = get_user_visible_users($config['id_user'], 'AR', false, true, true);
			if(is_array($users_all)){
				foreach ($users_all as $user){
					$id_group = get_db_value('id_grupo', 'tusuario_perfil', 'id_usuario', $user['id_usuario']);
					$user['id_grupo'] = $id_group;
					$users[] = $user;
				}
			}
		}
		else if (!empty($groups)) {
			$users = get_users_in_group($config['id_user'], $groups, 'AR');
		}
		if (is_array($users)) {
			$emails = array_reduce($users, function ($carry, $user) {
				$disabled = (bool) $user['disabled'];
				$email = trim($user['direccion']);
				$id_group = $user['id_grupo'];
				if (!$disabled && !empty($email)) {
					if (!in_array($email, $carry)){
						$carry[] = array('email' => $email, 'id_group' => $id_group,);
					}
				}
				return $carry;
			}, array());
		}
		else {
			$emails[0]= array('email' => $email, 'id_group' => 0);
		}
		$attachments = $full_filename;

		foreach ($emails as $email) {
			if (empty($id)) {
				if($email['id_group']){
					$sql_new    = "SELECT name FROM temail_template WHERE template_action = 16 AND id_group =".$email['id_group'].";";
					$templa_new = get_db_sql($sql_new);
				} else {
					$templa_new = '';
				}
				if ((!$templa_new) || ($templa_new == '')) {
					$mail_description = template_process ($config["homedir"]."/include/mailtemplates/new_entry_calendar.tpl", $MACROS);
					integria_sendmail($email['email'], "[".$config["sitename"]."] ".__("New calendar event"), $mail_description,  $attachments);
				} else {
					$mail_description = template_process ($config["homedir"]."/include/mailtemplates/".$templa_new.".tpl", $MACROS);
					integria_sendmail($email['email'], "[".$config["sitename"]."] ".__("New calendar event"), $mail_description,  $attachments);
				}
			} else {
				if($email['id_group']){
					$sql_update    = "SELECT name FROM temail_template WHERE template_action = 17 AND id_group =".$email['id_group'].";";
					$templa_update = get_db_sql($sql_update);
				} else {
					$templa_update = '';
				}
				if ((!$templa_update) || ($templa_update == '')) {
					$mail_description = template_process ($config["homedir"]."/include/mailtemplates/update_entry_calendar.tpl", $MACROS);
					integria_sendmail($email['email'], "[".$config["sitename"]."] ".__("Updated calendar event"), $mail_description,  $attachments);
				} else {
					$mail_description = template_process ($config["homedir"]."/include/mailtemplates/".$templa_update.".tpl", $MACROS);
					integria_sendmail($email['email'], "[".$config["sitename"]."] ".__("Updated calendar event"), $mail_description,  $attachments);
				}
			}
		}
		
		// unlink ($full_filename);
		
		if (empty($id)) {
			echo ui_print_success_message (__('The event was added to calendar'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('The event was updated'), '', true, 'h3', true);
		}
		echo "<br>";
		print_button (__('OK'), 'OK', false, '', 'class="sub blank"');
	} else {
		if (empty($id)) {
			echo ui_print_error_message (__('An error ocurred. Event not inserted.'), '', true, 'h3', true);
		} else {
			echo ui_print_error_message (__('An error ocurred. Event not updated.'), '', true, 'h3', true);
		}
		echo "<br>";
		print_button (__('OK'), 'OK', false, '', 'class="sub blank"');
	}
}


if ($delete_agenda_entry) {
	
	if (!empty($id) && !$permission) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete an agenda entry");
		include ("general/noaccess.php");
		exit;
	}
	
	$result = process_sql_delete('tagenda', array('id' => $id));
	
	if ($result !== false) {
		echo ui_print_success_message (__('The event was deleted'), '', true, 'h3', true);
		echo "<br>";
		print_button (__('OK'), 'OK', false, '', 'class="sub blank"');
	} else {
		echo ui_print_error_message (__('An error ocurred. Event not deleted'), '', true, 'h3', true);
		echo "<br>";
		print_button (__('OK'), 'OK', false, '', 'class="sub blank"');
	}
}

?>
