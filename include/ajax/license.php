<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$get_license_info = get_parameter('get_license_info', 0);

if ($get_license_info) {

	$expiry_day = get_parameter('expiry_day');
	$expiry_month = get_parameter('expiry_month');
	$expiry_year = get_parameter('expiry_year');
	$max_manager_users = get_parameter('max_manager_users');
	$max_regular_users = get_parameter('max_regular_users');
	$config['expiry_day'] = $expiry_day;
	$config['expiry_month'] = $expiry_month;
	$config['expiry_year'] = $expiry_year;
	$config['max_manager_users'] = $max_manager_users;
	$config['max_regular_users'] = $max_regular_users;

	enterprise_include('include/functions_license.php');
	
	// check license 
	$is_enteprise = enterprise_hook('license_show_info');
	
	// If Open show info
	if ($is_enteprise === ENTERPRISE_NOT_HOOK) {
		$table->width = '98%';
		$table->data = array ();
		$table->style = array();
		$table->style[0] = 'text-align: left';
		
		echo '<div style="float: left; width: 20%; margin-top: 40px; margin-left: 20px;">'; 
		print_image('images/lock_license.png', false);
		echo '</div>';
		
		$table->data[0][0] = '<strong>'.__('Expires').'</strong>';
		$table->data[0][1] = __('Never');
		$table->data[1][0] = '<strong>'.__('Platform Limit').'</strong>';
		$table->data[1][1] = __('Unlimited');
		$table->data[2][0] = '<strong>'.__('Current Platform Count').'</strong>';
		$table->data[2][1] = get_valid_users_num();
		$table->data[3][0] = '<strong>'.__('License Mode').'</strong>';
		$table->data[3][1] = __('Open Source Version');
		
		echo '<div style="width: 70%; margin-top: 30px; margin-left: 20px; float: right;">';
		print_table ($table);
		echo '</div>';
	}
}

return;

?>
