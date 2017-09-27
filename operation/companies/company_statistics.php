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

include_once ('include/functions_crm.php');

$read = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cr'));
$write = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cw'));
$manage = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cm'));
$enterprise = false;

if ($result === ENTERPRISE_NOT_HOOK) {
	$read = true;
	$write = true;
	$manage = true;
	
} else {
	$enterprise = true;
	if (!$read) {
		include ("general/noaccess.php");
		exit;
	}
}

$search_text = (string) get_parameter ('search_text');	
$search_role = (int) get_parameter ("search_role");
$search_country = (string) get_parameter ("search_country");
$search_manager = (string) get_parameter ("search_manager");
$search_parent = get_parameter ("search_parent");
$search_date_begin = get_parameter ('search_date_begin');
$search_date_end = get_parameter ('search_date_end');
$search_min_billing = (float) get_parameter("search_min_billing");
$order_by_activity = (string) get_parameter ("order_by_activity");
$order_by_company = (string) get_parameter ("order_by_company");
$order_by_billing = (string) get_parameter ("order_by_billing");
$pure = (bool) get_parameter('pure',0);

echo "<div id='incident-search-content'>";
echo "<h2>".__('Companies') . "</h2>";
echo "<h4>".__('Search statistics');
echo "<div id='button-bar-title'>";
echo "<ul>";
	if(!$pure){
		echo "<li><a id='search_form_submit' href='index.php?sec=customers&sec2=operation/companies/company_detail&search_text=$search_text&search_role=$search_role&search_country=$search_country&search_manager=$search_manager&search_parent=$search_parent&search_date_begin=$search_date_begin&search_date_end=$search_date_end&search_min_billing=$search_min_billing&order_by_activity=$order_by_activity&order_by_company=$order_by_company&order_by_billing=$order_by_billing'>".print_image("images/go-previous.png", true, array("title" => __("Back to search")))."</a></li>";
	} 
	if(!$pure){
		echo "<li><a href='index.php?sec=customers&sec2=operation/companies/company_statistics&pure=1'>".print_image ("images/html_tabs.png", true, array("title" => __("HTML")))."</a></li>";
	} else {
		echo "<li><a href='index.php?sec=customers&sec2=operation/companies/company_statistics&pure=0'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
	}
echo "</ul>";
echo "</div>";
echo "</h4>";

$where_clause = '';

if ($search_text != "") {
	$where_clause .= sprintf (' AND ( name LIKE "%%%s%%" OR country LIKE "%%%s%%")  ', $search_text, $search_text);
}

if ($search_role != 0) {
	$where_clause .= sprintf (' AND id_company_role = %d', $search_role);
}

if ($search_country != "") { 
	$where_clause .= sprintf (' AND country LIKE "%%s%%" ', $search_country);
}

if ($search_manager != "") {
	$where_clause .= sprintf (' AND manager = "%s" ', $search_manager);
}

if ($search_parent != 0) {
	$where_clause .= sprintf (' AND id_parent = %d ', $search_parent);
}

if ($search_date_begin != "") {
	$where_clause .= " AND `last_update` >= $search_date_begin";
	$date = true;
}

if ($search_date_end != "") {
	$where_clause .= " AND `last_update` <= $search_date_end";
	$date = true;
}

if ($search_min_billing != "") { 
	$having .= "HAVING `billing` >= $search_min_billing";
}



//COUNTRIES
$companies_country = crm_get_total_country($where_clause);

if ($read && $enterprise) {
	$companies_country = crm_get_total_country_acl($where_clause);
}

$companies_country = crm_get_data_country_graph($companies_country);

if ($companies_country != false) {
	$companies_country_content = pie3d_graph ($config['flash_charts'], $companies_country, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$companies_country_content = __('No data to show');
}

$companies_country_content = '<div class="pie_frame">' . $companies_country_content . '</div>';



// MANAGERS
if ($read && $enterprise) {
	$manager_companies = crm_get_total_managers_acl($where_clause);
} else {
	$manager_companies = crm_get_total_managers($where_clause);
}

$manager_companies = crm_get_data_managers_graph($manager_companies);

if ($manager_companies != false) {
	$companies_per_manager = pie3d_graph ($config['flash_charts'], $manager_companies, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$companies_per_manager = __('No data to show');
}

$companies_per_manager = '<div class="pie_frame">' . $companies_per_manager . '</div>';

//USERS
$companies_user = crm_get_total_user($where_clause);

if ($read && $enterprise) {
	$companies = array();
	foreach ($companies_user as $company) {
		$companies[$company["id_company"]]["id"] = $company["id_company"];
		$companies[$company["id_company"]]["total_users"] = $company["total_users"];
	}
	$companies_user = crm_get_user_companies($config['id_user'], $companies);

	$companies = array();
	foreach ($companies_user as $company) {
		$companies[$company["id_company"]] = $company["total_users"];
	}
	$companies_user = $company;
	$company = null;
}

$companies_user = crm_get_data_user_graph($companies_user);

if ($companies_user != false) {
	$companies_user_content = pie3d_graph ($config['flash_charts'], $companies_user, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$companies_user_content = __('No data to show');
}

$companies_user_content = '<div class="pie_frame">' . $companies_user_content . '</div>';



//INVOICING VOLUME
$companies_invoincing = crm_get_total_invoiced($where_clause);

if ($read && $enterprise) {
	$companies_invoincing = crm_get_user_companies($config['id_user'], $companies_invoincing);
}

$companies_invoincing = crm_get_total_invoiced_graph($companies_invoincing);

if ($companies_invoincing != false) {
	$companies_invoincing_volume = pie3d_graph ($config['flash_charts'], $companies_invoincing, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
} else {
	$companies_invoincing_volume = __('No data to show');
}

$companies_invoincing_volume = '<div class="pie_frame">' . $companies_invoincing_volume . '</div>';



//TOP 10 ACTIVITY
$companies_activity = crm_get_total_activity($where_clause);

if ($read && $enterprise) {
	$companies_activity = crm_get_user_companies($config['id_user'], $companies_activity);
}

if ($companies_activity != false) {
	$companies_activity_content = print_table(crm_print_most_activity_companies($companies_activity), true);
} else {
	$companies_activity_content = '<div>' . __('No data to show') . '</div>';
}



//MANAGERS INVOICING VOLUME
if ($read && $enterprise) {
	$managers_invoicing = crm_get_invoicing_managers_acl($config['id_user'], $where_clause);
} else {
	$managers_invoicing = crm_get_managers_invoicing($where_clause);
}

$managers_invoicing = crm_get_managers_invoicing_graph($managers_invoicing);

if ($managers_invoicing != false) {
	$managers_invoicing_volume = pie3d_graph ($config['flash_charts'], $managers_invoicing, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$managers_invoicing_volume = __('No data to show');
}

$managers_invoicing_volume = '<div class="pie_frame">' . $managers_invoicing_volume . '</div>';



//TOP 10 INVOICING
$companies_invoincing = crm_get_total_invoiced($where_clause);

if ($read && $enterprise) {
	$companies_invoincing = crm_get_user_companies($config['id_user'], $companies_invoincing);
}

if ($companies_invoincing != false) {
	$companies_invoincing_content = print_table(crm_print_most_invoicing_companies($companies_invoincing), true);
} else {
	$companies_invoincing_content = '<div>' . __('No data to show') . '</div>';
}



//TOP 10 MANAGERS INVOICING
if ($read && $enterprise) {
	$managers_invoicing = crm_get_invoicing_managers_acl($config['id_user'], $where_clause);
} else {
	$managers_invoicing = crm_get_managers_invoicing($where_clause);
}

if ($managers_invoicing != false) {
	$managers_invoicing_content = print_table(crm_print_most_invoicing_managers($managers_invoicing), true);
} else {
	$managers_invoicing_content = '<div>' . __('No data to show') . '</div>';
}



echo "<div style='clear: both;'>";
	echo print_container_div('container_pie_graphs companies_per_county', __('Companies per country'), $companies_country_content, 'no', true, '10px');
	echo print_container_div('container_pie_graphs companies_per_manager', __('Companies per manager'), $companies_per_manager, 'no', true, '10px');
	echo print_container_div('container_pie_graphs companies_per_user', __('Users per company'), $companies_user_content, 'no', true, '10px');
echo "</div>";
echo "<div style='clear: both;'>";
	echo print_container_div('container_pie_graphs invoicing_volume', __('Invoicing volume'), $companies_invoincing_volume, 'no', true, '10px');
	echo print_container_div('container_pie_graphs managers_invoicing_volume', __('Managers invoicing volume'), $managers_invoicing_volume, 'no', true, '10px');
echo "</div>";
echo "<div style='clear: both;'>";
	echo print_container_div('top_10_activity', __('Top 10 activity'), $companies_activity_content, 'no', true, '10px');
	echo print_container_div('top_10_invoicing', __('Top 10 invoicing'), $companies_invoincing_content, 'no', true, '10px');
	echo print_container_div('top_10_managers_invoicing', __('Top 10 managers invoicing'), $managers_invoicing_content, 'no', true, '10px');
echo "</div>";
?>
