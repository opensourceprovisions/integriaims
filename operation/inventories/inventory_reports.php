
<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

require_once ('include/functions_inventories.php');

echo '<h2>'.__('Custom reports').'</h2>';
echo '<h4>'.__('List of reports').'</h4>';

$delete = (bool) get_parameter ('delete_report');
$pure = get_parameter ("pure", 0);

if ($delete) {
	$id = (int) get_parameter ('id');
	
	$result = process_sql_delete ('tinventory_reports', array ('id' => $id));
	
	if ($result !== false) {
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
	}
}

if (dame_admin ($config['id_user'])) {
	$reports = get_db_all_rows_in_table ('tinventory_reports');
} else {
	$all_groups_str = groups_get_user_groups($config['id_user']);
	$sql = "SELECT * FROM tinventory_reports WHERE id_group IN ".$all_groups_str;
	$reports = get_db_all_rows_sql($sql);
}

echo "<div class='divresult'>";

if ($reports === false) {
	echo ui_print_error_message (__('No reports were found'), '', true, 'h3', true);
} else {
	$table = new stdClass();
	$table->width = '99%';
	$table->class = 'listing';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Name/Edit');
	$table->head[1] = __('View');
	$table->head[2] = __('PDF');
	$table->head[3] = __('CSV');
	$table->head[4] = __('Delete');
	
	$table->size = array ();

	foreach ($reports as $report) {
		$data = array ();
		
		if (dame_admin ($config['id_user'])) {
			$data[0] = '<a href="index.php?sec=users&sec2=operation/inventories/inventory_reports_detail&id='.$report['id'].'">';
			$data[0] .= $report['name'];
			$data[0] .= '</a>';
		} else {
			$data[0] .= $report['name'];
		}
		
		$data[1] = print_html_report_image ("index.php?sec=users&sec2=operation/inventories/inventory_reports_detail&render_html=1&id=".$report['id'], __("HTML report"), "", "", 1);
		$data[2] = print_report_image ("index.php?sec=users&sec2=operation/inventories/inventory_reports_detail&render_html=1&id=".$report['id'], __("PDF report"));
		$data[3] = "<a href='index.php?sec=users&sec2=operation/inventories/inventory_reports_detail&render=1&raw_output=1&clean_output=1&id=".$report['id']."'><img src='images/binary.png'></a>";
		
		$data[4] = "<a href='index.php?sec=users&sec2=operation/inventories/inventory_reports&delete_report=1&id=".$report["id"]."'>";
		$data[4] .= '<img src="images/cross.png">';
		$data[4] .= '</a>';
		
		array_push ($table->data, $data);
	}

	print_table ($table);
}
echo "</div>";
echo "<div class='divform'>";
echo '<form method="post" action="index.php?sec=users&sec2=operation/inventories/inventory_reports_detail">';
echo '<table class="search-table" style="width: 100%">';
echo '<tr>';
echo '<td>';
if(dame_admin ($config['id_user'])) {
	print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"');
}
echo '</td>';
echo '</tr>';
echo '</table>';
echo '</form>';
echo '</div>';
?>
