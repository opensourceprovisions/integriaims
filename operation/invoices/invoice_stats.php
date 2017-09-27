<?PHP
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
include_once('include/functions_crm.php');

// Invoice listing
$search_text = (string) get_parameter ('search_text');
$search_invoice_status = (string) get_parameter ('search_invoice_status');
$search_last_date = (int) get_parameter ('search_last_date');
$search_date_begin = get_parameter ('search_date_begin');
$search_date_end = get_parameter ('search_date_end');
$search_invoice_type = (string) get_parameter ('search_invoice_type');
$search_company_role = (int) get_parameter ('search_company_role');
$search_company_manager = (string) get_parameter ('search_company_manager');

$pdf_report = get_parameter("pdf_output");

$graph_ttl = 1;
if ($pdf_report) {
	$graph_ttl = 2;
}

$search_params = "&search_text=$search_text&search_invoice_status=$search_invoice_status&search_last_date=$search_last_date&search_date_end=$search_date_end&search_date_begin=$search_date_begin&search_invoice_type=$search_invoice_type&search_company_role=$search_company_role&search_company_manager=$search_company_manager";

$read = check_crm_acl ('company', 'cr');

if (!$read) {
	include ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Invoices')."</h2>";
echo "<h4>".__('Invoice statistics');
	echo "<div id='button-bar-title'>";
		echo "<ul>";
		if(!$pure){
			echo "<li><a href='index.php?sec=customers&sec2=operation/invoices/invoice_detail".$search_params."'>" . print_image ("images/go-previous.png", true, array("title" => __("Back to project editor"))) . "</a></li>";
		} 
		if(!$pure){
			echo "<li><a href='index.php?sec=customers&sec2=operation/invoices/invoice_stats&pure=1'>".print_image ("images/html_tabs.png", true, array("title" => __("HTML")))."</a></li>";
		} else {
			echo "<li><a href='index.php?sec=customers&sec2=operation/invoices/invoice_stats&pure=0'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		}
		
		
		echo "</ul>";
	echo "</div>";
echo "</h4>";

$where_clause = " 1 = 1 ";

if ($search_text != "") {
	$where_clause .= sprintf ('AND (id_company IN (SELECT id FROM tcompany WHERE name LIKE "%%%s%%") OR 
		bill_id LIKE "%%%s%%" OR 
		description LIKE "%%%s%%")', $search_text, $search_text, $search_text);
}
if ($search_invoice_status != "") {
	$where_clause .= sprintf (' AND status = "%s"', $search_invoice_status);
}
// last_date is in days
if ($search_last_date) {
	$last_date_seconds = $search_last_date * 24 * 60 * 60;
	$search_date_begin = date('Y-m-d H:i:s', time() - $last_date_seconds);
	//$search_date_end = date('Y-m-d H:i:s');
	$search_date_end = "";
}
if ($search_date_begin != "") {
	$where_clause .= sprintf (' AND invoice_create_date >= "%s"', $search_date_begin);
}
if ($search_date_end != "") {
	$where_clause .= sprintf (' AND invoice_create_date <= "%s"', $search_date_end);
}
if ($search_invoice_type != "") {
	$where_clause .= sprintf (' AND invoice_type = "%s"', $search_invoice_type);
}
if ($search_company_role > 0) {
	$where_clause .= sprintf (' AND id_company IN (SELECT id FROM tcompany WHERE id_company_role = %d)', $search_company_role);
}
if ($search_company_manager != "") {
	$where_clause .= sprintf (' AND id_company IN (SELECT id FROM tcompany WHERE manager = "%s")', $search_company_manager);
}

$invoices = crm_get_all_invoices($where_clause);

$inv_data_currency = array();
$inv_total_currency = array();
$inv_data_company = array();

//Get all currency and company totals and legend
foreach ($invoices as $inv) {

	$inv["total"] = crm_get_amount_total_invoice($inv);

	if (! isset($inv_total_currency[$inv["currency"]])) {
		$inv_total_currency[$inv["currency"]] = 0;
	}

        if (! isset($legend[$inv["currency"]])) {
                $legend[$inv["currency"]] = $inv["currency"];
        }

	$inv_total_currency[$inv["currency"]] = $inv_total_currency[$inv["currency"]] + $inv["total"];

	$comp_name = crm_get_company_name($inv["id_company"]);
	if ( !isset($inv_data_company[$comp_name])) {
		$inv_data_company[$comp_name] = 0;
	}

	$inv_data_company[$comp_name] = $inv_data_company[$comp_name] + $inv["total"];
}

//Get data for graphs
foreach ($invoices as $inv) {
	
	$inv["total"] = crm_get_amount_total_invoice($inv);
	
	if (! isset($inv_data_currency[$inv["invoice_create_date"]])) {
		$inv_data_currency[$inv["invoice_create_date"]] = array();

		foreach ($inv_total_currency as $cur => $value) {
			$inv_data_currency[$inv["invoice_create_date"]][$cur] = 0;
		}
	}
		
	$inv_data_currency[$inv["invoice_create_date"]][$inv["currency"]] += $inv["total"];	
}

ksort($inv_data_currency);

arsort($inv_data_company, SORT_NUMERIC);

$table->id = 'company_list';
$table->class = 'listing';
$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->style = array ();

$table->head[0] = __('Currency');
$table->head[1] = __('Invoiced');

$i = 0;
foreach ($inv_total_currency as $curr => $val) {

	if ($i < 5) {
		$data = array();
		$data[0] = $curr;
		$data[1] = format_numeric($val);

		array_push ($table->data, $data);
	}
	$i++;
}

$currency_table = print_table($table, true);

switch ($search_invoice_type) {
	case 'Submitted':
		$container_title_history = __("Submitted billing history");
		break;
	case 'Received':
		$container_title_history = __("Received billing history");
		break;
	default:
		$container_title_history = __(" Submitted billing history");
		break;
}

$invoicing_graph = stacked_area_graph($config["flash_charts"], $inv_data_currency, 1050, 350, null, $legend, '', '', '', '', '' ,'' ,'' ,'', $graph_ttl, $config["base_url"]);
$container_invoicing_graph = '<div class="pie_frame">' .$invoicing_graph."</div>";


//Transform data for companies invoiced graph
$comp_invoiced_data = array();

foreach ($inv_data_company as $comp => $val) {
	$comp_name = safe_output($comp);
	$comp_name = substr($comp_name, 0 ,15);

	$val_aux = $val / 1000;
	$val_aux = sprintf("%.2f k ", $val_aux);
	$comp_name = $comp_name." (".$val_aux.")";
	$comp_invoiced_data[$comp_name] = $val;
}
//container_title_company
switch ($search_invoice_type) {
	case 'Submitted':
		$container_title_company = __("Submitted billing per company");
		break;
	case 'Received':
		$container_title_company = __("Received billing per company");
		break;
	default:
		$container_title_company = ("Submitted billing per company");
		break;
}

$companies_invoiced_graph = pie3d_graph ($config["flash_charts"], $comp_invoiced_data, 300, 150, __('others'), $config["base_url"], "", $config['font'], $config['fontsize'], $graph_ttl);
$companies_invoiced_graph = '<div class="pie_frame">' .$companies_invoiced_graph."</div>";

//container_title_currency
switch ($search_invoice_type) {
	case 'Submitted':
		$container_title_currency = __("Submitted billing per currency");
		break;
	case 'Received':
		$container_title_currency = __("Received billing per currency");
		break;
	default:
		$container_title_currency = __("Submitted billing per currency");
		break;
}
echo "<div style='clear: both;'>";
	echo print_container_div('history_invoiced', $container_title_history, $container_invoicing_graph, 'no', true, true, "container_simple_title", "container_simple_div");
echo "</div>";

echo "<div style='clear: both;'>";
	echo print_container_div('container_pie_graphs companies_invoiced', $container_title_company, $companies_invoiced_graph, 'no', true, true, "container_simple_title", "container_simple_div");
	echo print_container_div('currency_invoiced', $container_title_currency, $currency_table, 'no', true, true, "container_simple_title", "container_simple_div");
echo "</div>";
?>

