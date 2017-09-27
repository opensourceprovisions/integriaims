<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



function load_config() {
	global $config;
	
	require_once ($config["homedir"].'/include/functions_db.php');
	
	$configs = get_db_all_rows_in_table ('tconfig');
	
	if ($configs === false) {
		include ($config["homedir"]."/general/error_invalidconfig.php");
		exit;
	}
	
	foreach ($configs as $c) {
		$config[$c["token"]] = $c["value"];
	}
	
	if (!isset ($config["block_size"]))
		$config["block_size"] = 25;
	
	if (!isset($config["notification_period"]))
		$config["notification_period"] = "86400";
	
	if (!isset ($config["autowu_completion"]))
		$config["autowu_completion"] = "0";
	
	if (!isset ($config["no_wu_completion"]))
		$config["no_wu_completion"] = "";
	
	if (!isset ($config["FOOTER_EMAIL"]))
		$config["FOOTER_EMAIL"] = __('Please do NOT answer this email, it has been automatically created by Integria (http://integria.sourceforge.net).');
	
	if (!isset ($config["HEADER_EMAIL"]))
		$config["HEADER_EMAIL"] = "Hello, \n\nThis is an automated message coming from Integria\n\n";
	
	if (!isset ($config["currency"]))
		$config["currency"] = "€";
	
	if (!isset ($config["hours_perday"]))
		$config["hours_perday"] = 8;
	
	if (!isset ($config["limit_size"]))
		$config["limit_size"] = 1000;
	
	if (!isset ($config["sitename"])) 
		$config["sitename"] = "INTEGRIA";
	
	if (!isset ($config["fontsize"]))
		$config['fontsize'] = 7;
	
	if (!isset ($config["incident_reporter"]))
		$config['incident_reporter'] = 0;
	
	if (!isset ($config["show_owner_incident"]))
		$config["show_owner_incident"] = 1;
	
	if (!isset ($config["show_creator_incident"]))
		$config["show_creator_incident"] = 1;
	
	if (!isset ($config["smtp_host"])){
		$config["smtp_host"] = "localhost";
	}
	
	if (!isset($config["wokring_weekends"])) {
		$config["wokring_weekends"] = 0;
	}
	
	if (!isset ($config["smtp_user"])){
		$config["smtp_user"] = "";
	}
	
	if (!isset ($config["smtp_pass"])){
		$config["smtp_pass"] = "";
	}
	
	if (!isset ($config["smtp_port"])){
		$config["smtp_port"] = 25;
	}
	
	if (!isset ($config["pop_host"])){
		$config["pop_host"] = "localhost";
	}
	
	if (!isset ($config["pop_port"])){
		$config["pop_port"] = 110;
	}
	
	if (!isset ($config["pop_user"])){
		$config["pop_user"] = "";
	}
	
	if (!isset ($config["pop_pass"])){
		$config["pop_pass"] = "";
	}
	
	if (!isset ($config["audit_delete_days"])){
		$config["audit_delete_days"] = 45;
	}
	
	if (!isset ($config["iwu_defaultime"])){
		$config["iwu_defaultime"] = "0.25";
	}
	
	if (!isset ($config["pwu_defaultime"])){
		$config["pwu_defaultime"] = "4";
	}
	
	if (!isset ($config["timezone"])){
		$config["timezone"] = "Europe/Madrid";
	}
	
	if (!isset ($config["api_acl"])){
		$config["api_acl"] = "127.0.0.1";
	}
	
	if (!isset ($config["auto_incident_close"])){
		$config["auto_incident_close"] = "72";
	}

	if (!isset ($config["language_code"])) {
		$config["language_code"] = "en_GB";
	}
	
	if (!isset ($config["flash_charts"])) {
		$config["flash_charts"] = true;
	}
	
	// Mail address used to send mails
	if (!isset ($config["mail_from"]))
		$config["mail_from"] = "integria@localhost.com";
	
	if (!isset ($config["site_logo"])){
		$config["site_logo"] = "integria_logo.png";
	}
	
	if (!isset ($config["header_logo"])){
		$config["header_logo"] = "integria_logo_header.png";
	}
	
	if (!isset ($config["email_on_incident_update"])){
		$config["email_on_incident_update"] = 0;
	}
	
	if (!isset ($config["error_log"])){
		$config["error_log"] = 1;
	}
	
	if (!isset ($config["sql_query_limit"]))
		$config["sql_query_limit"] = 1500;
	
	if (!isset ($config["pdffont"]))
		$config["pdffont"] = $config["homedir"]."include/fonts/FreeSans.ttf";
	
	if (!isset ($config["font"])){
		$config["font"] = $config["homedir"]."/include/fonts/smallfont.ttf";
	}
	
	if (!isset ($config["audit_category_default"])) {
		$config["audit_category_default"] = 1;
	}
	
	if (!isset($config["max_file_size"])) {
		$config["max_file_size"] = "50M";
	}
	
	if (!isset($config["want_chat"])){
		$config["want_chat"] = 0;
	}
	
	if (!isset($config["lead_warning_time"])){
		$config["lead_warning_time"] = "7";
	}
	
	if (!isset($config["incident_creation_wu"])){
		$config["incident_creation_wu"] = 0;
	}
	
	if (!isset($config["graphviz_win"])){
		$config["graphviz_win"] = "C:\Program Files\Graphviz 2.28\bin";
	}
	
	if (!isset($config["months_to_delete_incidents"])){
		$config["months_to_delete_incidents"] = 12;
	}
	
	if (!isset ($config["working_weekends"])){
		$config["working_weekends"] = 0;
	}
	
	if (!isset ($config["mask_emails"])){
		$config["mask_emails"] = 0;
	}
	
	if (!isset ($config['attachment_store'])) {
		$config['attachment_store'] = $config['homedir'].'attachment';
	}
	
	if (!isset ($config['session_timeout'])) {
		$config['session_timeout'] = 9000;
	}
	
	if (!isset ($config['update_manager_installed'])) {
		$config['update_manager_installed'] = 1;
	}
	
	if (!isset ($config["inventory_path"])) {
		$config["inventory_path"] = $config["homedir"]."attachment/inventory";
	}
	
	if (!isset ($config["remote_inventory_type"])) {
		$config["remote_inventory_type"] = 0;
	}
	
	if (!isset ($config["inventory_default_owner"])) {
		$config["inventory_default_owner"] = "";
	}
	if (!isset ($config["smtp_queue_retries"])) {
		$config["smtp_queue_retries"] = 10;
	}

	if (!isset ($config["url_updatemanager"])) {
		$config["url_updatemanager"] = "";
	}

	if (!isset ($config["access_protocol"])) {
		$config["access_protocol"] = false;
	}

	if (!isset ($config["access_port"])) {
		$config["access_port"] = "";
	}

	if (!isset ($config["access_public"])) {
		if (isset($_SERVER["SERVER_NAME"])) {
			$config["access_public"] = $_SERVER["SERVER_NAME"];
		} else {
			$config["access_public"] = "localhost";
		}
		update_config_token ("access_public", $config["access_public"]);
	}

	if (!isset($config["license"])){
		$config["license"]= "INTEGRIA-FREE";
		update_config_token ("license", $config["license"]);
	}
	if (!isset($config["enabled_ticket_editor"])){
		$config["enabled_ticket_editor"] = 0;
	}
	if (!isset ($config["enable_update_manager"])) {
		$config["enable_update_manager"] = true;
	}
	if (!isset($config["invoice_auto_id"])) {
		$config["invoice_auto_id"] = 0;
	}
	if (!isset($config["invoice_id_pattern"])) {
		$config["invoice_id_pattern"] = "15/[1000]";
	}
	if (!isset($config["change_creator_owner"])){
		$config["change_creator_owner"] = 1;
	}
	if (!isset($config["max_direct_download"])) {
		$config["max_direct_download"] = 100;
	}
	if (!isset($config["external_modify_tickets"])){
		$config["external_modify_tickets"] = 1;
	}
}

function config_prepare_session() {
	global $config;
	
	
	// Change the session timeout value to session_timeout minutes  // 8*60*60 = 8 hours
	$sessionCookieExpireTime = $config["session_timeout"] * 60;
	ini_set('session.gc_maxlifetime', $sessionCookieExpireTime);
	session_set_cookie_params ($sessionCookieExpireTime);
	
	// Reset the expiration time upon page load //session_name() is default name of session PHPSESSID
	
	if (isset($_COOKIE[session_name()]))
		setcookie(session_name(), $_COOKIE[session_name()], time() + $sessionCookieExpireTime, "/");
	
	ini_set("post_max_size",$config["max_file_size"]);
	ini_set("upload_max_filesize",$config["max_file_size"]);
}

function load_menu_visibility() {
	global $show_projects;
	global $show_incidents;
	global $show_inventory;
	global $show_reports;
	global $show_kb;
	global $show_file_releases;
	global $show_people;
	global $show_todo;
	global $show_agenda;
	global $show_setup;
	global $show_box;
	global $show_wiki;
	global $show_customers;
	global $config;
	
	// Get visibility permissions to sections
	//~ $show_projects = enterprise_hook ('get_menu_section_access', array ('projects'));
	$show_projects = enterprise_hook ('get_menu_section_mode', array ('projects'));
	if($show_projects == ENTERPRISE_NOT_HOOK) {
		$show_projects = MENU_FULL;
	}
	//~ $show_incidents = enterprise_hook ('get_menu_section_access', array ('incidents'));
	$show_incidents = enterprise_hook ('get_menu_section_mode', array ('incidents'));
	if($show_incidents == ENTERPRISE_NOT_HOOK) {
		$show_incidents = MENU_FULL;
	}
	//~ $show_inventory = enterprise_hook ('get_menu_section_access', array ('inventory'));
	$show_inventory = enterprise_hook ('get_menu_section_mode', array ('inventory'));
	if($show_inventory == ENTERPRISE_NOT_HOOK) {
		$show_inventory = MENU_FULL;
	}
	//~ $show_reports = enterprise_hook ('get_menu_section_access', array ('reports'));
	$show_reports = enterprise_hook ('get_menu_section_mode', array ('reports'));
	if($show_reports == ENTERPRISE_NOT_HOOK) {
		$show_reports = MENU_FULL;
	}
	//~ $show_kb = enterprise_hook ('get_menu_section_access', array ('kb'));
	$show_kb = enterprise_hook ('get_menu_section_mode', array ('kb'));
	if($show_kb == ENTERPRISE_NOT_HOOK) {
		$show_kb = MENU_FULL;
	}
	//~ $show_file_releases = enterprise_hook ('get_menu_section_access', array ('file_releases'));
	$show_file_releases = enterprise_hook ('get_menu_section_mode', array ('file_releases'));
	if($show_file_releases == ENTERPRISE_NOT_HOOK) {
		$show_file_releases = MENU_FULL;
	}
	//~ $show_people = enterprise_hook ('get_menu_section_access', array ('people'));
	$show_people = enterprise_hook ('get_menu_section_mode', array ('people'));
	if($show_people == ENTERPRISE_NOT_HOOK) {
		$show_people = MENU_FULL;
	}
	//~ $show_agenda = enterprise_hook ('get_menu_section_access', array ('agenda'));
	$show_agenda = enterprise_hook ('get_menu_section_mode', array ('agenda'));
	if($show_agenda == ENTERPRISE_NOT_HOOK) {
		$show_agenda = MENU_FULL;
	}
	//~ $show_setup = enterprise_hook ('get_menu_section_access', array ('setup'));
	$show_setup = enterprise_hook ('get_menu_section_mode', array ('setup'));
	if($show_setup == ENTERPRISE_NOT_HOOK) {
		$show_setup = MENU_FULL;
	}
	//~ $show_wiki = enterprise_hook ('get_menu_section_access', array ('wiki'));
	$show_wiki = enterprise_hook ('get_menu_section_mode', array ('wiki'));
	if($show_wiki == ENTERPRISE_NOT_HOOK) {
		$show_wiki = MENU_FULL;
	}
	//~ $show_customers = enterprise_hook ('get_menu_section_access', array ('customers'));
	$show_customers = enterprise_hook ('get_menu_section_mode', array ('customers'));
	if($show_customers == ENTERPRISE_NOT_HOOK) {
		$show_customers = MENU_FULL;
	}
	$sec = get_parameter('sec', '');
	
	
	if (!isset($customers))
		$customers = "";
	
	$show_box = ($sec == "projects" && $show_projects == MENU_FULL) || 
				($sec == "incidents" && $show_incidents == MENU_FULL) || 
				($sec == "inventory" && $show_inventory == MENU_FULL) || 
				($sec == "reports" && $show_reports == MENU_FULL) || 
				($sec == "kb" && $show_kb == MENU_FULL) || 
				($sec == "download" && $show_file_releases == MENU_FULL) || 
				($sec == "users" && $show_people == MENU_FULL) || 
				($sec == "godmode" && $show_setup == MENU_FULL) ||
				($sec == "wiki" && $show_wiki == MENU_FULL) ||
				($sec == "customers" && $customers == MENU_FULL) ||
				dame_admin($config['id_user']);
}

?>
