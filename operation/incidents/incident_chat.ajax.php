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

// Load global vars

check_login ();

define("MAX_TIMES", 10);

/* This page only works in AJAX */
if (! defined ('AJAX'))
	return;

global $config;

$dir = $config['homedir'];
$dir .= 'attachment' . '/chat';
if (!file_exists($dir) && !is_dir($dir)) {
	mkdir($dir);
}


$get_last_messages = (bool)get_parameter('get_last_messages', 0);
$send_message = (bool)get_parameter('send_message', 0);
$send_login = (bool)get_parameter('send_login', 0);
$send_logout = (bool)get_parameter('send_logout', 0);
$long_polling_check_messages = (bool)get_parameter('long_polling_check_messages', 0);
$get_last_global_counter = (bool)get_parameter('get_last_global_counter', 0);
$check_users = (bool)get_parameter('check_users', 0);
$save_message = (bool)get_parameter('save_message', 0);

$id = (int)get_parameter('id', 0);

if ($get_last_messages) {
	$time = (int)get_parameter('time', 24 * 60 * 60);
	users_get_last_messages($time);
}
if ($send_message) {
	$message = get_parameter('message', false);
	users_save_text_message($message);
}
if ($send_login) {
	users_save_login();
}
if ($send_logout) {
	users_save_logout();
}
if ($long_polling_check_messages) {
	$global_counter = (int)get_parameter('global_counter', 0);
	users_long_polling_check_messages($global_counter);
}
if ($get_last_global_counter) {
	users_get_last_global_counter();
}

if ($check_users) {
	users_check_users();
}

if ($save_message) {
	save_message_workunit();
}

function save_message_workunit() {
	global $config;
	global $dir;
	global $id;
	
	include("include/functions_workunits.php");
	
	$return = array('correct' => false);
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	$log_chat_file = $dir . '/incident.' . $id . '.log.json.txt';
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	
	$text_encode = @file_get_contents($log_chat_file);
	$log = json_decode($text_encode, true);//debugPrint($log);
	
	$txtChat = __('---------- CHAT -------------');
	$txtChat .= "\n";
	foreach ($log as $message) {
		if ($message['type'] == 'notification') {
			//Disabled at the moment
			continue;
			//$txtChat .= __("<<SYSTEM>>");
		}
		else {
			$txtChat .= $message['user_name'];
		}
		$txtChat .= " :> ";
		$txtChat .= $message['text'];
		
		$txtChat .= "\n";
	}
	
	create_workunit ($id, safe_input($txtChat), $config['id_user']);
	
	fclose($fp_global_counter);
	
	$return['correct'] = true;
	echo json_encode($return);
	
	return;
}

////////////////////////////////////////////////////////////////////////
//////////////////////WEBCHAT FUNCTIONS/////////////////////////////////
////////////////////////////////////////////////////////////////////////
function users_get_last_messages($last_time = false) {
	global $dir;
	global $id;
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	$timestamp = time();
	if ($last_time === false)
		$last_time = 24 * 60 * 60;
	$from = $timestamp - $last_time;
	
	$log_chat_file = $dir . '/incident.' . $id . '.log.json.txt';
	
	$return = array('correct' => false, 'log' => array());
	
	if (!file_exists($log_chat_file)) {
		touch($log_chat_file);
	}
	
	$text_encode = @file_get_contents($log_chat_file);
	$log = json_decode($text_encode, true);
	
	if ($log !== false) {
		if ($log === null)
			$log = array();
		
		$log_last_time = array();
		foreach ($log as $message) {
			if ($message['timestamp'] >= $from) {
				$log_last_time[] = $message;
			}
		}
		
		$return['correct'] = true;
		$return['log'] = $log_last_time;
		$return['global_counter'] = $global_counter_file;
	}
	
	echo json_encode($return);
	
	fclose($fp_global_counter);
	
	return;
}

function users_save_login() {
	global $config;
	global $dir;
	global $id;
	
	$file_global_user_list = $dir . '/incident.' . $id . '.user_list.json.txt';
	
	$user = get_db_row_filter('tusuario',
		array('id_usuario' => $config['id_user']));
	
	$message = sprintf(__('User %s login at %s'), $user['nombre_real'],
		date($config['date_format']));
	users_save_text_message($message, 'notification');
	
	//First lock the file
	$fp_user_list = fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	$user_list[$config['id_user']] = $user['nombre_real'];
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_logout() {
	global $config;
	global $dir;
	global $id;
	
	$return = array('correct' => false, 'users' => array());
	
	$file_global_user_list = $dir . '/incident.' . $id . '.user_list.json.txt';
	
	$user = get_db_row_filter('tusuario',
		array('id_usuario' => $config['id_user']));
		
	$message = sprintf(__('User %s logout at %s'), $user['nombre_real'],
		date($config['date_format']));
	users_save_text_message($message, 'notification');
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	unset($user_list[$config['id_user']]);
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_text_message($message = false, $type = 'message') {
	global $config;
	global $dir;
	global $id;
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	$log_chat_file = $dir . '/incident.' . $id . '.log.json.txt';
	
	$return = array('correct' => false);
	
	$id_user = $config['id_user'];
	$user = get_db_row_filter('tusuario',
		array('id_usuario' => $id_user));
	
	$message_data = array();
	$message_data['type'] = $type;
	$message_data['id_user'] = $id_user;
	$message_data['user_name'] = $user['nombre_real'];
	$message_data['text'] = safe_input_html($message);
	//The $message_data['timestamp'] set when adquire the files to save.
	
	
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	//Clean the file
	ftruncate($fp_global_counter, 0);
	
	$message_data['timestamp'] = time();
	$message_data['human_time'] = date($config['date_format'], $message_data['timestamp']);
	
	$global_counter = $global_counter_file + 1;
	
	$status = fwrite($fp_global_counter, $global_counter);
	
	if ($status === false) {
		fclose($fp_global_counter);
		
		echo json_encode($return);
		
		return;
	}
	else {
		$text_encode = @file_get_contents($log_chat_file);
		$log = json_decode($text_encode, true);
		$log[$global_counter] = $message_data;
		$status = file_put_contents($log_chat_file, json_encode($log));
		
		fclose($fp_global_counter);
		
		$return['correct'] = true;
		echo json_encode($return);
	}
	
	return;
}

function users_long_polling_check_messages($global_counter) {
	global $config;
	global $dir;
	global $id;
	
	$uniq = uniqid();
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	$log_chat_file = $dir . '/incident.' . $id . '.log.json.txt';
	
	$changes = false;
	
	$tries_general = 0;
	
	while (!$changes) {
		//First lock the file
		$fp_global_counter = @fopen($file_global_counter_chat, "a+");
		if ($fp_global_counter) {
			//Try to look MAX_TIMES times
			$tries = 0;
			$lock = true;
			
			while (!flock($fp_global_counter, LOCK_EX)) {
				$tries++;
				if ($tries > MAX_TIMES) {
					$lock = false;
					break;
				}
				
				sleep(1);
			}
			
			if ($lock) {
				@fscanf($fp_global_counter, "%d", $global_counter_file);
				if (empty($global_counter_file)) {
					$global_counter_file = 0;
				}
				
				if ($global_counter_file > $global_counter) {
					//TODO Optimize slice the array.
					
					$text_encode = @file_get_contents($log_chat_file);
					$log = json_decode($text_encode, true);
					
					$return_log = array();
					foreach ($log as $key => $message) {
						if ($key <= $global_counter) continue;
						
						$return_log[] = $message;
					}
					
					$return = array(
						'correct' => true,
						'global_counter' => $global_counter_file,
						'log' => $return_log);
					
					echo json_encode($return);
					
					fclose($fp_global_counter);
					
					return;
				}
			}
			fclose($fp_global_counter);
		}
		
		sleep(3);
		$tries_general = $tries_general + 3;
		
		if ($tries_general > MAX_TIMES) {
			break;
		}
	}
	
	echo json_encode(array('correct' => false));
	
	return;
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_global_counter($mode = 'json') {
	global $config;
	global $dir;
	global $id;
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			@fscanf($fp_global_counter, "%d", $global_counter_file);
			if (empty($global_counter_file)) {
				$global_counter_file = 0;
			}
			
			fclose($fp_global_counter);
		}
	}
	
	switch ($mode) {
		case 'json':
			echo json_encode(array('correct' => true, 'global_counter' => $global_counter_file));
			break;
		case 'return':
			return $global_counter_file;
			break;
		case 'session':
			$_SESSION['global_counter_chat'] = $global_counter_file;
			break;
	}
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_type_message() {
	global $config;
	global $dir;
	global $id;
	
	$return = 'false';
	
	$file_global_counter_chat = $dir . '/incident.' . $id . '.global_counter.txt';
	$log_chat_file = $dir . '/incident.' . $id . '.log.json.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			$text_encode = @file_get_contents($log_chat_file);
			$log = json_decode($text_encode, true);
			
			$last = end($log);
			
			$return = $last['type'];
			
			fclose($fp_global_counter);
		}
	}
	
	return $return;
}

function users_is_last_system_message() {
	$type = users_get_last_type_message();
	
	if ($type != 'message')
		return true;
	else
		return false;
}

function users_check_users() {
	global $config;
	global $dir;
	global $id;
	
	$return = array('correct' => false, 'users' => '');
	
	$file_global_user_list = $dir . '/incident.' . $id . '.user_list.json.txt';
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	fclose($fp_user_list);
	
	$return['correct'] = true;
	$return['users'] = implode('<br />', $user_list);
	echo json_encode($return);
	
	return;
}
?>
