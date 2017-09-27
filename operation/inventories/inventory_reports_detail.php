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

$create = (bool) get_parameter ('create_report');
$update = (bool) get_parameter ('update_report');
$name = (string) get_parameter ('name');
$sql = (string) get_parameter ('sql');
$id_group = get_parameter('id_group', 0);
$id = (int) get_parameter ('id');
$pure = get_parameter ("pure", 0);

if ($id) {
	$report = get_db_row ('tinventory_reports', 'id', $id);
	if ($report === false)
		return;
	$name = $report['name'];
	$sql = $report['sql'];
	$id_group = $report['id_group'];
	
	$user_in_group = get_db_value_filter('id_grupo', 'tusuario_perfil', array('id_usuario'=>$config['id_user'],'id_grupo'=>$id_group));	
	if ($id_group == 1) {
		$user_in_group = 1;
	}
}


if ((!dame_admin ($config['id_user'])) && ($user_in_group == false)) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access inventory reports");
	include ("general/noaccess.php");
	return;
}

$result_msg = '';
if ($create) {
	$values['name'] = (string) get_parameter ('name');
	$values['sql'] = (string) get_parameter ('sql');
	$values['id_group'] = get_parameter('id_group', 0);
	
	$result = false;
	if (! empty ($values['name']))
		$result = process_sql_insert ('tinventory_reports', $values);
	
	if ($result) {
		$result_msg = ui_print_success_message (__("Successfully created"), '', true, 'h3', true);
		$id = $result;
	} else {
		$result_msg = ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		$id = false;
	}
}

if ($update) {
	$values['name'] = (string) get_parameter ('name');
	$values['sql'] = (string) get_parameter ('sql');
	$values['id_group'] = get_parameter('id_group');
	
	$result = false;
	if (! empty ($values['name']))
		$result = process_sql_update ('tinventory_reports', $values, array ('id' => $id));
	if ($result) {
		$result_msg = ui_print_success_message (__("Successfully updated"), '', true, 'h3', true);
	} else {
		$result_msg = ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	}
}

if ($id) {
	clean_cache_db();

	$report = get_db_row ('tinventory_reports', 'id', $id);
	if ($report === false)
		return;
	$name = $report['name'];
	$sql = $report['sql'];
	$id_group = $report['id_group'];
}

$render = get_parameter ("render",0);
$render_html = get_parameter ("render_html",0);

if ($render == 1){

	$search = array();
	
	//$search[] = "&#x0d;";
	$search[] = "\r";
	//$search[] = "&#x0a;";
	$search[] = "\n";
	$search[] = '"';
	$search[] = "'";
	//$search[] = ";";
	$search[] = ",";

	$report = get_db_row ('tinventory_reports', 'id', $id);
	if ($report === false)
		return;
	
	$filename = clean_output ($report['name']).'-'.date ("YmdHi");

	$config['mysql_result_type'] = MYSQL_ASSOC;

    ob_end_clean();

	// CSV Output
	header ('Content-Encoding: UTF-8');
	header ('Content-Type: text/csv; charset=UTF-8');
	header ('Content-Disposition: attachment; filename="'.$filename.'.csv"');
	$os_csv = substr(PHP_OS, 0 , 1);
	echo "\xEF\xBB\xBF";
	
	$rows = get_db_all_rows_sql (clean_output ($report['sql']));
	if ($rows === false)
		return;

	// Header
	echo safe_output (implode (';', array_keys (str_replace($search, " ", $rows[0]))))."\n";
	$standard_encoding = (bool) $config['csv_standard_encoding'];
	
	// Item / data
	foreach ($rows as $row) {
		$line = safe_output(implode(';', $row));

		if (!$standard_encoding){
			if($os_csv != "W"){
				echo mb_convert_encoding($line, 'UTF-16LE', 'UTF-8'). "\n";
			} else {
				echo $line . "\n";
			}
		}else{
			echo $line . "\n";
		}
	}
	exit;	

}

if ($render_html == 1){
	$report = get_db_row ('tinventory_reports', 'id', $id);
	if ($report === false)
		return;
	
	ini_set ("memory_limit", "3072M");
	ini_set ("max_execution_time", 600);
	
	echo "<h2>".__('Custom report')."</h2>";
	echo "<h4>".$report['name'];
		echo "<div id='button-bar-title'><ul>";
			echo "<li><a href='index.php?sec=projects&sec2=operation/inventories/inventory_reports'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back to Report")))."</a></li>";
		echo "</ul></div>";
	echo "</h4>";

	$config['mysql_result_type'] = MYSQL_ASSOC;
	$rows = get_db_all_rows_sql (clean_output ($report['sql']));
	if ($rows === false)
		return;
	
	//count $row chunk
	$row_chunk_cont = count(array_chunk($rows[0], 10));
	
	//keys $row chunk
	$row_chunk_keys = array_chunk(array_keys($rows[0]), 10);
		
	$table = array();
	for($i=0; $i < $row_chunk_cont; $i++){
		$table[$i][] = $row_chunk_keys[$i];
		foreach ($rows as $row) {
			$row_chunk = array_chunk($row, 10);
			$table[$i][] = $row_chunk[$i];
		}
	}

	foreach ($table as $t){
		echo "<table width=99% cellpadding=0 cellspacing=0 class=listing>";
		foreach ($t as $k => $tr){
			echo "<tr>";
			if ($k == 0){
				foreach ($tr as $item){
					echo "<th>".$item."</th>";
				}
			} else {
				foreach ($tr as $item){
					echo "<td>".$item."</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}
    return;
}

echo "<h2>".__('Inventory reports')."</h2>";
if ($id) {
	echo "<h4>".__('Update');
		echo "<div id='button-bar-title'><ul>";
			echo "<li><a href='index.php?sec=projects&sec2=operation/inventories/inventory_reports'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back to Report")))."</a></li>";
		echo "</ul></div>";
	echo "</h4>";
 } else {
	echo "<h4>".__('Create');
		echo "<div id='button-bar-title'><ul>";
			echo "<li><a href='index.php?sec=projects&sec2=operation/inventories/inventory_reports'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back to Report")))."</a></li>";
		echo "</ul></div>";
	echo "</h4>";
}
echo $result_msg;

$table = new stdClass;
$table->width = '100%';
$table->class = 'search-table-button';
$table->data = array ();
$table->colspan = array ();
$table->colspan[1][0] = 2;
$table->colspan[2][0] = 2;
	
$table->data[0][0] = print_input_text ('name', $name, '', 40, 255, true, __('Name'));

$groups = get_user_groups ($config['id_user'], "VR");
$groups[0] = __('None');
$table->data[0][1] = print_select ($groups, "id_group", $id_group, '', '', 0, true, false, false, __('Group'));

$table->data[1][0] = print_textarea ('sql', 10, 100, $sql, '', true, __('Report SQL sentence'));

if (dame_admin ($config['id_user'])) {
	if ($id) {
			$button = print_input_hidden ('update_report', 1, true);
			$button .= print_input_hidden ('id', $id, true);
			$button .= print_submit_button (__('Update'), 'update', false, 'class="sub upd"', true);
	} else {
		$button = print_input_hidden ('create_report', 1, true);
		$button .= print_submit_button (__('Create'), 'create', false, 'class="sub create"', true);
	}
}

echo '<form id="form-inventory_report" method="post">';
print_table ($table);
if (dame_admin ($config['id_user'])) 
	echo "<div class='button-form'>" . $button . "</div>";
echo '</form>';
?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
	
// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-inventory_report");
var rules, messages;
// Rules: #text-name
rules = {
	required: true/*,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_inventory_report: 1,
			inventory_report_name: function() { return $('#text-name').val() },
			inventory_report_id: "<?php echo $id?>"
        }
	}*/
};
messages = {
	required: "<?php echo __('Name required')?>"/*,
	remote: "<?php echo __('This inventory report already exists')?>"*/
};
add_validate_form_element_rules('#text-name', rules, messages);
// Rules: #textarea-sql
rules = {
	required: true
};
messages = {
	required: "<?php echo __('SQL sentence required')?>"
};
add_validate_form_element_rules('#textarea-sql', rules, messages);

</script>
