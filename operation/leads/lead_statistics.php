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

$read = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cr'));
$enterprise = false;

if ($result === ENTERPRISE_NOT_HOOK) {
	$read = true;
} else {
	$enterprise = true;
	if (!$read) {
		include ("general/noaccess.php");
		exit;
	}
}

$search_text = (string) get_parameter ('search_text');
$id_company = (int) get_parameter ('id_company_search');
$last_date = (int) get_parameter ('last_date_search');
$start_date = (string) get_parameter ('start_date_search');
$end_date = (string) get_parameter ('end_date_search');
$country = (string) get_parameter ('country_search');
$id_category = (int) get_parameter ('product');
$progress = (int) get_parameter ('progress_search');
$progress_major_than = (int) get_parameter ('progress_major_than_search');
$progress_minor_than = (int) get_parameter ('progress_minor_than_search');
$owner = (string) get_parameter ("owner_search");
$show_100 = (int) get_parameter ("show_100_search");
$id_language = (string) get_parameter ("id_language", "");
$est_sale = (int) get_parameter ("est_sale_search", 0);
//$clean_output = (int) get_parameter ("clean_output");
//$pdf_output = (int) get_parameter ("pdf_output");
$report_name = get_parameter("report_name");
$show_not_owned = (int) get_parameter ("show_not_owned_search");
$pure = (bool) get_parameter('pure',0);

$tags = get_parameter('tags', array());

if (!$report_name) {
	$report_name = __("Leads report");
}

if ($pdf_output) {
	$ttl = 2;
}

$params = "&est_sale_search=$est_sale&id_language_search=$id_language&search_text=$search_text&id_company_search=$id_company&last_date_search=$last_date&start_date_search=$start_date&end_date_search=$end_date&country_search=$country&product=$id_category&progress_search=$progress&progress_minor_than_search=$progress_minor_than&progress_major_than_search=$progress_major_than&show_100_search=$show_100&owner_search=$owner&show_not_owned_search=$show_not_owned";

if (!empty($tags)) {
	$params .= '&tags[]='.implode('&tags[]=', $tags);
}

echo "<h2>".__('Leads') . "</h2>";
echo "<h4>".__('Lead search statistics');

if (!$pure) {
	echo integria_help ("lead", true);
}
	echo "<div id='button-bar-title'>";
		echo "<ul>";
		echo "<li>";
		if(!$pure){
			echo "<a id='search_form_submit' href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&$params'>".print_image("images/go-previous.png", true, array("title" => __("Back to search")))."</a></li>";
		} else {
			echo "<a id='search_form_submit' href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&$params'>".print_image("images/chart_bar_dark.png", true, array("title" => __("")))."</a></li>";
		}
		if(!$pure){
			echo "<li><a href='index.php?sec=customers&sec2=operation/leads/lead&tab=statistics&pure=1'>".print_image ("images/html_tabs.png", true, array("title" => __("HTML")))."</a></li>";
		} else {
			echo "<li><a href='index.php?sec=customers&sec2=operation/leads/lead&tab=statistics&pure=0'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		}
		echo "</ul>";
	echo "</div>";

echo "</h4>";

$where_clause = "WHERE (1=1 $where_group ";

if ($est_sale != ""){
	$where_clause .= " AND estimated_sale >= $est_sale ";
}

if ($id_language != ""){
	$where_clause .= " AND id_language = '$id_language' ";
}

if ($show_not_owned) {
	$where_clause .= " AND owner = '' ";
}

if ($owner != ""){
	$where_clause .= sprintf (' AND owner =  "%s"', $owner);
}

if ($search_text != "") {
	$where_clause .= sprintf (' AND fullname LIKE "%%%s%%" OR description LIKE "%%%s%%" OR company LIKE "%%%s%%" or email LIKE "%%%s%%"', $search_text, $search_text, $search_text, $search_text);
}

if ($id_company) {
	$where_clause .= sprintf (' AND id_company = %d', $id_company);
}

// last_date is in days
if ($last_date) {
	$last_date_seconds = $last_date * 24 * 60 * 60;
	$start_date = date('Y-m-d H:i:s', time() - $last_date_seconds);
	//$end_date = date('Y-m-d H:i:s');
	$end_date = "";
}

if ($start_date) {
	$where_clause .= sprintf (' AND creation >= "%s"', $start_date);
}

if ($end_date) {
	$where_clause .= sprintf (' AND creation <= "%s"', $end_date);
}

if ($country) {
	$where_clause .= sprintf (' AND country LIKE "%%%s%%"', $country);
}

if ($progress > 0) {
	$where_clause .= sprintf (' AND progress = %d ', $progress);
}

if ($progress_minor_than > 0) {
	$where_clause .= sprintf (' AND progress <= %d ', $progress_minor_than);
}

if ($progress_major_than > 0) {
	$where_clause .= sprintf (' AND progress >= %d ', $progress_major_than);
}

if ($id_category) {
	$where_clause .= sprintf(' AND id_category = %d ', $id_category);
}

// Tags filter
if (!empty($tags)) {
	$lead_ids = get_leads_with_tags(array(TAGS_TABLE_ID_COL => $tags));
	
	// Some leads
	if (!empty($lead_ids) && is_array($lead_ids))
		$where_clause .= sprintf(' AND id IN (%s) ', implode(',', $lead_ids));
	// None lead found
	else
		$where_clause .= ' AND id IN (-1) ';
}

$where_clause .= ")";

$table->class = 'blank';
$table->width = '100%';
$table->data = array ();
$table->style = array ();
$table->valign = array ();
$table->colsapan = array();
$table->valign[0] = "top";
$table->valign[1] = "top";

//FUNNEL

$leads_funnel = crm_get_total_leads_funnel($where_clause);

if ($read && $enterprise) {
	$leads_funnel = crm_get_user_leads($config['id_user'], $leads_funnel);
}

if ($leads_funnel != false) {
	$data = array();

	$lead_progress = lead_progress_array();

	$total_leads = count($total_leads_array);
	
	foreach ($lead_progress as $key => $name) {
		$data[$key] = array("title" => $name, "completion" => 0);
	}

	//Calculate total number of leads
	$total_leads = 0;
	foreach ($leads_funnel as $lf) {

		if ($lf["progress"] < 100 ||$lf["progress"] == 200) {
			$total_leads = $total_leads + $lf["total_leads"];
		}
	} 

	foreach ($leads_funnel as $lf) {
		$completion = ($lf["total_leads"] / $total_leads) * 100;

		if ($total_leads <= 0) {
			$completion = 0;
		} else {
			$completion = ($lf["total_leads"] / $total_leads) * 100;
		}

		$data[$lf["progress"]]["completion"] = $completion;
		$data[$lf["progress"]]["amount"] = $lf["amount"];
	}
	
	$leads_funnel_content = funnel($data, $config["font"], $ttl, $config["homedir"]);
} else {
	$leads_funnel_content = __('No data to show');
}

$leads_country_content_funnel = '<div class="pie_frame">' . $leads_funnel_content . '</div>';



//CONVERSION RATE
$success_leads_array = crm_get_all_leads($where_clause." AND progress = 200 ");
$total_leads_array = crm_get_all_leads($where_clause);

if ($read && $enterprise) {
	$success_leads_array = crm_get_user_leads($config['id_user'], $success_leads_array);
	$total_leads_array = crm_get_user_leads($config['id_user'], $total_leads_array);
}

$total_success = 0;
if ($success_leads_array) {
	$total_success = count($success_leads_array);
}

$total_leads = count($total_leads_array);
$conversion_rate = $total_success / $total_leads * 100;

$total_amount_success = 0;
if (isset($data[200]["amount"])) {
	$total_amount_success = $data[200]["amount"];
}

if (!$clean_output) {

	$leads_conversion_rate = "<table class='conversion_rate'>";
	$leads_conversion_rate .= "<tr>";
	$leads_conversion_rate .= "<td class='conversion_value'>";
	$leads_conversion_rate .= sprintf("%.2f %%",$conversion_rate);
	$leads_conversion_rate .= "</td>";
	$leads_conversion_rate .= "</tr>";
	$leads_conversion_rate .= "<tr>";
	$leads_conversion_rate .= "<td>";
	$leads_conversion_rate .= __("Total amount")."<br><br>";
	$leads_conversion_rate .= $total_amount_success." ".$config["currency"];
	$leads_conversion_rate .= "</td>";
	$leads_conversion_rate .= "</tr>";
	$leads_conversion_rate .= "</table>";

} else {

	$leads_conversion_rate = "<table style='width: 98%; margin: 0 auto;'>";
	$leads_conversion_rate .= "<tr>";
	$leads_conversion_rate .= "<td style='padding-top: 20px; font-size: 45pt; font-weight: bold; text-align:center'>";
	$leads_conversion_rate .= sprintf("%.2f %%",$conversion_rate);
	$leads_conversion_rate .= "</td>";
	$leads_conversion_rate .= "</tr>";
	$leads_conversion_rate .= "<tr>";
	$leads_conversion_rate .= "<td style='padding-top: 36px; padding-bottom: 37px; font-size: 18pt; font-weight: bold; text-align: center;'>";
	$leads_conversion_rate .= __("Total amount")."<br><br>";
	$leads_conversion_rate .= $total_amount_success." ".$config["currency"];
	$leads_conversion_rate .= "</td>";
	$leads_conversion_rate .= "</tr>";
	$leads_conversion_rate .= "</table>";

}

$leads_conversion_rate = '<div class="pie_frame">' . $leads_conversion_rate . '</div>';

$container_title = __('Conversion ratio');

if (!$clean_output) {
	$container_title .= "&nbsp;".print_help_tip(__("Conversion ratio is calculated using closed leads (keep in mind that closed leads don't appear in search by default)"),true);
}


//COUNTRIES
$leads_country = crm_get_total_leads_country($where_clause);

if ($read && $enterprise) {
	$leads_country = crm_get_user_leads($config['id_user'], $leads_country);
}
$leads_country = crm_get_data_lead_country_graph($leads_country);

if ($leads_country != false) {
	$leads_country_content = pie3d_graph ($config['flash_charts'], $leads_country, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$leads_country_content = __('No data to show');
}

$leads_country_content = '<div class="pie_frame">' . $leads_country_content . '</div>';


//USERS
$leads_user = crm_get_total_leads_user($where_clause);

if ($read && $enterprise) {
	$leads_user = crm_get_user_leads($config['id_user'], $leads_user);
}
$leads_user = crm_get_data_lead_user_graph($leads_user);

if ($leads_user !== false) {
	$leads_user_content = pie3d_graph ($config['flash_charts'], $leads_user, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize']-1, $ttl);
} else {
	$leads_user_content = __('No data to show');
}

$leads_user_content = '<div class="pie_frame">' . $leads_user_content . '</div>';



//TOP 10 ESTIMATED SALES
$where_clause_top10 = $where_clause." AND progress < 100";
$leads_sales = crm_get_total_sales_lead($where_clause_top10);

if ($read && $enterprise) {
	$leads_sales = crm_get_user_leads($config['id_user'], $leads_sales);
}

if ($leads_sales != false) {
	$leads_sales_content = print_table(crm_print_estimated_sales_leads($leads_sales), true);
} else {
	$leads_sales_content = '<div class="pie_frame">' . __('No data to show') . '</div>';
}



//NEW LEADS
$leads_creation = crm_get_total_leads_creation($where_clause);

if ($read && $enterprise) {
	$leads_creation = crm_get_user_leads($config['id_user'], $leads_creation);
}

$leads_creation = crm_get_data_lead_creation_graph($leads_creation);

if ($leads_creation !== false) {

	$area_width = 400;
	$area_height = 250;

	if ($clean_output) {
		$area_width = 240;
		$area_height = 155;		
	}

	$leads_creation_content = area_graph($config['flash_charts'], $leads_creation, $area_width, $area_height, "#2179B1", '', '', '', "", "", $config["base_url"], "", '', '', '', $ttl);
} else {
	$leads_creation_content = __('No data to show');
}

$leads_creation_content = '<div class="pie_frame"><br>' . $leads_creation_content . '</div>';


echo "<div style='clear: both;'>";
	echo print_container_div('funnel', __('Leads Funnel'), $leads_country_content_funnel, 'no', true, true, "container_simple_title", "container_simple_div");
	echo print_container_div('conversion_rate', $container_title, $leads_conversion_rate, 'no', true, true, "container_simple_title", "container_simple_div");
	echo print_container_div('container_pie_graphs leads_per_country', __('Leads per country'), $leads_country_content, 'no', true, true, "container_simple_title", "container_simple_div");
echo "</div>";

echo "<div style='clear: both;'>";
	echo print_container_div('container_pie_graphs users_per_lead', __('Users per lead'), $leads_user_content, 'no', true, true, "container_simple_title", "container_simple_div");
	echo print_container_div('top_10_sales', __('Top 10 estimated sales'), $leads_sales_content, 'no', true, true, "container_simple_title", "container_simple_div");
	echo print_container_div('new_leads', __('New leads'), $leads_creation_content, 'no', true, true, "container_simple_title", "container_simple_div");
echo "</div>";
?>
