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

check_login ();

if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access to offline update");
	require ("general/noaccess.php");
	exit;
}

include_once("include/functions_update_manager.php");
if (file_exists ("enterprise/include/functions_update_manager.php")) {
	require_once("enterprise/include/functions_update_manager.php");
	include_once("enterprise/include/functions_license.php");
}

if (defined ('AJAX')) {
	$users = get_valid_users_num();
	$license = $config['license'];
	$current_package = $config['current_package'];
	
	
	$check_online_free_packages = (bool)get_parameter('check_online_free_packages', 0);
	$update_last_free_package = (bool)get_parameter('update_last_free_package', 0);
	$check_update_free_package = (bool)get_parameter('check_update_free_package', 0);
	$install_package = (bool)get_parameter('install_package', 0);
	
	if ($check_online_free_packages) {

		update_manager_check_online_free_packages ();
		
		return;
	}
	
	if ($update_last_free_package) {
		
		$package = get_parameter('package', '');
		$version = get_parameter('version', '');
		$package_url = base64_decode($package);
		
		$params = array('action' => 'get_package',
			'license' => $license,
			'limit_count' => $users,
			'current_package' => $current_package,
			'package' => $package,
			'version' => $config['version'],
			'build' => $config['build']);
		
		$curlObj = curl_init();
		curl_setopt($curlObj, CURLOPT_URL, $package_url);
		curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlObj, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curlObj);
		$http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
		
		curl_close($curlObj);
		
		if (empty($result)) {
			echo json_encode(array(
				'in_progress' => false,
				'message' => __('Fail to update to the last package.')));
		}
		else {
			file_put_contents($config['attachment_store'] . "/downloads/last_package.tgz" , $result);
			
			echo json_encode(array(
				'in_progress' => true,
				'message' => __('Starting to update to the last package.')));
			
			
			$progress_update_status = get_db_value(
				'value', 'tconfig', 'token', 'progress_update_status');
			
			if (empty($progress_update_status)) {
				process_sql_insert('tconfig',
					array(
						'value' => 0,
						'token' => 'progress_update')
				);
				
				process_sql_insert('tconfig',
					array(
						'value' => json_encode(
							array(
								'status' => 'in_progress',
								'message' => ''
							)),
						'token' => 'progress_update_status')
					);
			}
			else {
				process_sql_update('tconfig',
					array('value' => 0),
					array('token' => 'progress_update'));
				
				process_sql_update('tconfig',
					array('value' => json_encode(
							array(
								'status' => 'in_progress',
								'message' => ''
							)
						)
					),
					array('token' => 'progress_update_status'));
			}
		}
		
		return;
	}
	
	if ($install_package) {
		$version = get_parameter('version', '');
		
		update_manager_starting_update();
		
		
		$sql_update = "UPDATE tconfig SET `value`='$version'
			WHERE `token`='current_package'";
		process_sql($sql_update);
		$config['current_package'] = $version;
		
		$sql_update = "UPDATE tconfig SET `value`='$version'
			WHERE `token`='db_scheme_build'";
		process_sql($sql_update);
		$config['db_scheme_build'] = $version;
		
		sleep(3);
		
		
		
		$return["status"] = "success";
		$return["message"]= __("The package is installed.");
		echo json_encode($return);
	}
	
	if ($check_update_free_package) {
		
		
		$progress_update = get_db_value ('value', 'tconfig',
			'token', 'progress_update');
		
		$progress_update_status = get_db_value ('value', 'tconfig',
			'token', 'progress_update_status');
		$progress_update_status = json_decode($progress_update_status, true);
		
		switch ($progress_update_status['status']) {
			case 'in_progress':
				$correct = true;
				$end = false;
				break;
			case 'fail':
				$correct = false;
				$end = false;
				break;
			case 'end':
				$correct = true;
				$end = true;
				break;
		}
		
		$progressbar_tag = progressbar($progress_update, 400, 20, "caca", $config['font']);
		preg_match("/src='(.*)'/", $progressbar_tag, $matches);
		$progressbar = $matches[1];
		
		echo json_encode(array(
			'correct' => $correct,
			'end' => $end,
			'message' => $progress_update_status['message'],
			'progressbar' => $progressbar
		));
		
		return;
	}
	
	
	enterprise_hook('update_manager_enterprise_ajax');
	return;
}


echo "<h2>" . __("Update Manager") . "</h2>";
echo "<h4>" . __("Update Integria") . "</h4>";

$enterprise = enterprise_hook('update_manager_enterprise_main');

if ($enterprise == ENTERPRISE_NOT_HOOK) {
	//Open view
	update_manager_main();
}
?>
