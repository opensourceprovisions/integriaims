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

include_once('include/functions_crm.php');

$read = true;

$read = check_crm_acl ('lead', 'cr');
if (!$read) {
	include ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

$filter = (bool) get_parameter ('filter');
$order_item = (string) get_parameter ('order_item', 'estimated_sale');
$show_closed = (int) get_parameter ('show_closed');

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
$show_not_owned = (int) get_parameter ("show_not_owned_search");

$tags = get_parameter('tags', array());

$params = "&est_sale_search=$est_sale&id_language_search=$id_language&search_text=$search_text&id_company_search=$id_company&last_date_search=$last_date&start_date_search=$start_date&end_date_search=$end_date&country_search=$country&product=$id_category&progress_search=$progress&progress_minor_than_search=$progress_minor_than&progress_major_than_search=$progress_major_than&show_100_search=$show_100&owner_search=$owner&show_not_owned_search=$show_not_owned";

if (!empty($tags)) {
	$params .= '&tags[]='.implode('&tags[]=', $tags);
}


echo "<h2>". __('Leads') . "</h2>";
echo "<h4>". __('Lead pipeline');
echo integria_help ("lead", true);
echo "<div id='button-bar-title'>";
	echo "<ul>";
		// Filter button
		echo "<li>";
			echo "<a href='javascript:' onclick='toggleDiv (\"pipeline_filter\")'>".__('Filter form')."</a>";
		echo "</li>";
		echo "<li>";
			echo "<a id='search_form_submit' href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&$params'>".
				print_image("images/go-previous.png", true, array("title" => __("Back to search")))."</a>";
		echo "</li>";
	echo "</ul>";
echo "</div>";
echo "</h4>" ;

$where_clause = '';

if ($est_sale != "") {
	$where_clause .= " AND estimated_sale >= $est_sale ";
}

if ($id_language != "") {
	$where_clause .= " AND id_language = '$id_language' ";
}

if ($show_not_owned) {
	$where_clause .= " AND owner = '' ";
}

if ($owner != "") {
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

$table->width="100%";
$table->data = array();
$table->head = array();
$table->size = array();
$table->class="pipeline-table";

$table->size[0] = "20%";
$table->size[1] = "20%";
$table->size[2] = "20%";
$table->size[3] = "20%";
$table->size[4] = "20%";

$progress = lead_progress_array ();

$i = 0;
foreach ($progress as $k => $v) {
	// The leads are closed from the 100
	if ($k >= 100) {
		if ((bool)$show_closed && ($k == 100 || $k >= 200)) {
			if ($k >= 200) {
				$where = "WHERE 1=1 $where_clause AND progress = $k AND UNIX_TIMESTAMP(modification) > " . (time() - SECONDS_1YEAR);
			}
			else {
				$v = __('Closed unsuccessfully');
				$progress_closed = array();
				foreach ($progress as $progress_value => $progress_description) {
					if ($progress_value >= 100 && $progress_value < 200)
						$progress_closed[] = $progress_value;
				}
				if (empty($progress_closed))
					continue;
				$where = "WHERE 1=1 $where_clause AND progress IN (".implode(",", $progress_closed).") AND UNIX_TIMESTAMP(modification) > " . (time() - SECONDS_3MONTHS);
			}
		}
		else {
			continue;
		}
	}
	else {
		$where = "WHERE 1=1 $where_clause AND progress = $k";
	}
	$leads = crm_get_all_leads ($where, "ORDER BY $order_item DESC");

	if(!$leads) {
		$leads = array();
		$num_leads = 0;
	} else {
		$num_leads = count($leads);
	}
	
	$amount = 0;
	foreach ($leads as $lead) {
		$amount += $lead["estimated_sale"];
	}
	
	if (!$amount) {
		$amount = 0;
	}

	$table_header = "<table class='pipeline-header'>";
	$table_header .= "<tr>";
	$table_header .= "<td class='pipeline-header-title'>";
	$table_header .= "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&owner_search=".$config["id_user"]."&progress_major_than_search=".$k."&progress_minor_than_search=".$k."'>";
	$table_header .= $v;
	$table_header .= "</a>";
	$table_header .= "</td>";
	$table_header .= "<td rowspan='2'>";
	$table_header .= "<div class='pipeline-arrow'></div>";
	$table_header .= "</td>";
	$table_header .= "</tr>";
	$table_header .= "<tr>";
	$table_header .= "<td class='pipeline-header-subtitle'>";
	$table_header .= $amount." ".$config["currency"]." ".__("from")." ".$num_leads." ".__("leads"); 
	$table_header .= "</td>";
	$table_header .= "</tr>";
	$table_header .="</table>";

	$table->head[$i] = $table_header;

	$lead_list = "<ul class='pipeline-list'>";

	// Stored in $config["lead_warning_time"] in days, need to calc in secs for this
	$lead_warning_time = $config["lead_warning_time"] * 86400;	

	foreach ($leads as $l) {

		$lead_list .= "<li class='pipeline-list'>";
		$lead_list .= "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".$l["id"]."'>";

		$char_truncate = 18;
		
		$company = $l["company"];

		if (!$company) {
			$company = __("N/A");
		}

		$company = ui_print_truncate_text($company, $char_truncate, false, true);
		if ($l['executive_overview'] != '') {
			$company .= print_help_tip ($l['executive_overview'], true);
		}
	
		$lead_list .= "<div class='pipeline-list-title'>";
		$lead_list .= $company;

		$lead_list .= "</div>";

		$lead_list .= "<div class='pipeline-list-subtitle'>";

		$name = strtolower($l["fullname"]);
		$name = ucwords($name);

		$country = $l["country"];

		if (!$country) {
			$country = __("N/A");
		}

		$details = $name."&nbsp;&nbsp;(".$country.")";

		$details_size = strlen(safe_output($details));

		$char_truncate = 30;

		$details = ui_print_truncate_text($details, $char_truncate, false, true);

		$lead_list .= "<div class='pipeline-list-details'>".$details."</div>";

		if (!empty($l['owner'])) {

			$char_truncate = 35;

			$owner = $l['owner'];
			$details = ui_print_truncate_text($owner, $char_truncate, false, true);

			$lead_list .= "<div class='pipeline-list-owner'>".$owner."</div>";
		}

		if (!empty($l['estimated_close_date']) && $l['estimated_close_date'] != '0000-00-00 00:00:00') {

			$estimated_close_date = date('Y-m-d', strtotime($l['estimated_close_date']));

			$lead_list .= "<div title= '".__('Estimated close date')."' class='pipeline-list-estimated_close_date'>".$estimated_close_date."</div>";
		}

		// Detect if the lead is pretty old 
		if (calendar_time_diff ($l["modification"]) > $lead_warning_time) {
			$human_time_lead = human_time_comparation ($l['modification']);

			$time_title = sprintf (__("Updated %s ago"), $human_time_lead);
		
			$lead_list .= "<img class='pipeline-warning-icon' src='images/header_warning.png' title='".$time_title."' alt='".$time_title."'>";
		}

		// Detect if the lead have specific changes in the last 7 days
		$sql = sprintf("SELECT id_lead, id_user, description, UNIX_TIMESTAMP(timestamp) AS datetime
						FROM tlead_history
						WHERE id_lead = %d
							AND UNIX_TIMESTAMP(timestamp) >= %d
						ORDER BY datetime DESC", $l['id'], time() - SECONDS_1WEEK);
		$lead_history = process_sql($sql);
		
		if ($lead_history !== false) {
			$changes_info = array();
			foreach ($lead_history as $id => $entry) {
				if (preg_match("/^Lead .+ updated\. .*$/", $entry['description'])) {
					$changes_info[] = date("Y-m-d H:i:s", $entry['datetime']) . ": " . safe_output($entry['description']);
				}
			}
			if (!empty($changes_info)) {
				$changes_info_size = count($changes_info);
				$changes_info_title = "";
				foreach ($changes_info as $key => $change_info) {
					$changes_info_title .= $change_info;
					if ($key < $changes_info_size -1)
						$changes_info_title .=  "\n\n";
				}
				$lead_list .= "<img class='pipeline-changes-icon' src='images/info.png' title='".$changes_info_title."' alt='".$changes_info_title."'>";
			}
		}

		$lead_list .= $l["estimated_sale"]." ".$config["currency"];


		$product_name = __("None");
		$product_icon = "misc.png";

		if ($l["id_category"]) {
			$product_name = get_db_value("name", "tkb_product", "id", $l["id_category"]);

			$product_icon = get_db_value("icon", "tkb_product", "id", $l["id_category"]);
		}

		$product_img = "<img class='pipeline-product-icon' src='images/products/".$product_icon."' title='".$product_name."' alt='".$product_name."'>";

		$lead_list .= $product_img;
		$lead_list .= "</div>";
		$lead_list .= "</a>";
		$lead_list .= "</li>";
	}

	$lead_list .= "</ul>";

	$table->data[2][$i] = $lead_list;
	$i++;
}

// Filter form
$table_filter = new stdClass;
$table_filter->id = 'pipeline_filter_form_table';
$table_filter->width = '100%';
$table_filter->class = 'search-table-button';
$table_filter->data = array();

$row = array();
$sql = sprintf ('SELECT id, name FROM tcustom_search
				 WHERE id_user = "%s"
					 AND section = "leads"
				 ORDER BY name',
				 $config['id_user']);
$order_items = array(
		'estimated_sale' => __('Estimated sale'),
		'modification' => __('Modification date'),
		'estimated_close_date' => __('Estimated close date')
	);
$row[] = print_select($order_items, 'order_item', $order_item, '', '', 0, true, false, false, __('Order by'));
$row[] = print_checkbox('show_closed', 1, (bool)$show_closed, true, __('Show closed leads'));
$row[] = "<div class='button-form'>" . print_submit_button(__('Filter'), 'filter', false, 'class="sub save" style="margin-top: 13px;"', true) . "</div>";

$table_filter->data[] = $row;

echo '<div id="pipeline_filter" style="display: none;">';
echo '<form id="form-pipeline_filter" method="post" action="index.php?sec=customers&sec2=operation/leads/lead&tab=pipeline&'.$params.'">';
print_table($table_filter);
echo '</form>';
echo '</div>';

// Table pipeline
print_table($table);

?>
