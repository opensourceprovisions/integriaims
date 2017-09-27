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

check_login();

if (! give_acl ($config["id_user"], 0, "IM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation",
		"Trying to access SLA Management");
	require ("general/noaccess.php");
	exit;
}

echo '<h2>'.__('Support').'</h2>';
echo "<h4>".__('SLA Management');
echo integria_help ("sla", true);
echo "</h4>";

$id = (int) get_parameter ('id');
$new_sla = (bool) get_parameter ('new_sla');
$create_sla = (bool) get_parameter ('create_sla');
$update_sla = (bool) get_parameter ('update_sla');
$delete_sla = (bool) get_parameter ('delete_sla');

// CREATE
if ($create_sla) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$min_response = (float) get_parameter ('min_response');
	$max_response = (float) get_parameter ('max_response');
	$max_incidents = (int) get_parameter ('max_incidents');
	$max_inactivity = (float) get_parameter ('max_inactivity');
	$id_sla_base = (int) get_parameter ('id_sla_base');
	$enforced = (int) get_parameter ('enforced');

    $five_daysonly = (int) get_parameter ("five_daysonly", 0);
    $time_from = (int) get_parameter ("time_from", 0);
    $time_to = (int) get_parameter ("time_to", 0);
    $no_holidays = (int) get_parameter ('no_holidays', 0);
    $id_sla_type = (int) get_parameter ('id_sla_type', 0);

	$sql = sprintf ('INSERT INTO tsla (`name`, `description`, id_sla_base,
		min_response, max_response, max_incidents, `enforced`, five_daysonly, time_from, time_to, max_inactivity, no_holidays, id_sla_type)
		VALUE ("%s", "%s", %d, %.2f, %.2f, %d, %d, %d, %d, %d, %.2f, %d, %d)',
		$name, $description, $id_sla_base, $min_response,
		$max_response, $max_incidents, $enforced, $five_daysonly, $time_from, $time_to, $max_inactivity, $no_holidays, $id_sla_type);

	$id = process_sql ($sql);
	if ($id === false)
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
        audit_db ($config["id_user"], $config["REMOTE_ADDR"], "SLA Created",
		"Created a new SLA ($name)", $sql);
	}
	$id = 0;
}

// UPDATE
// ==================
if ($update_sla) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$min_response = (float) get_parameter ('min_response');
	$max_response = (float) get_parameter ('max_response');

	$max_incidents = (int) get_parameter ('max_incidents');
	$id_sla_base = (int) get_parameter ('id_sla_base');
	$enforced = (int) get_parameter ('enforced');
    $five_daysonly = (int) get_parameter ("five_daysonly", 0);
    $time_from = (int) get_parameter ("time_from", 0);
    $time_to = (int) get_parameter ("time_to", 0);
    $max_inactivity = (float) get_parameter ('max_inactivity');
    $no_holidays = (int) get_parameter ('no_holidays', 0);
    $id_sla_type = (int) get_parameter ('id_sla_type', 0);

	$sql = sprintf ('UPDATE tsla SET max_inactivity = %.2f, enforced = %d, description = "%s",
		name = "%s", max_incidents = %d, min_response = %.2f, max_response = %.2f,
		id_sla_base = %d, five_daysonly = %d, time_from = %d, time_to = %d, no_holidays = %d, id_sla_type = %d WHERE id = %d', $max_inactivity, 
		$enforced, $description, $name, $max_incidents, $min_response,
		$max_response, $id_sla_base, $five_daysonly, $time_from, $time_to, $no_holidays, $id_sla_type, $id);

	$result = process_sql ($sql);
	if (! $result)
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
        audit_db ($config["id_user"], $config["REMOTE_ADDR"], "SLA Modified",
		"Updated SLA ($name)", $sql);
	}
	$id = 0;
}

// DELETE
// ==================
if ($delete_sla) {
	$name = get_db_value ('name', 'tsla', 'id', $id);
	$sql = sprintf ('DELETE FROM tsla WHERE id = %d', $id);
	$result = process_sql ($sql);
    audit_db ($config["id_user"], $config["REMOTE_ADDR"], "SLA Deleted",
		"Delete SLA ($name)", $sql);
	echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	$id = 0;
}

// FORM (Update / Create)
if ($id || $new_sla) {
	if ($new_sla) {
		$name = "";
		$description = "";
		$min_response = 48.0;
		$max_response = 480.0;
		$max_incidents = 10;
		$max_inactivity = 96.0;
		$id_sla_base = 0;
		$enforced = 1;
        $five_daysonly = 1;
        $time_from = 8;
        $time_to = 18;
		$no_holidays = 1;
		$id_sla_type = 0;
	} else {
		$sla = get_db_row ('tsla', 'id', $id);
		$name = $sla['name'];
		$description = $sla['description'];
		$min_response = $sla['min_response'];
		$max_response = $sla['max_response'];
		$max_incidents = $sla['max_incidents'];
		$max_inactivity = $sla['max_inactivity'];
		$id_sla_base = $sla['id_sla_base'];
		$enforced = $sla['enforced'];
        $five_daysonly = $sla["five_daysonly"];
        $time_from = $sla["time_from"];
        $time_to = $sla["time_to"];
        $no_holidays = $sla["no_holidays"];
        $id_sla_type = $sla["id_sla_type"];

	}
	
	$table = new StdClass();
	$table->width = "100%";
	$table->class = "search-table-button";
	$table->data = array ();
	$table->colspan = array ();
	$table->align = array ();
	$table->colspan[3][0] = 4;
	
	$table->data[0][0] = print_input_text ("name", $name, "", 30, 100, true, __('SLA name'));
	$table->data[0][1] = print_checkbox ('enforced', 1 ,$enforced, true, __('Enforced'));
	
	$table->data[0][2] = print_select_from_sql ('SELECT id, name FROM tsla ORDER BY name',
		'id_sla_base', $id_sla_base, '', __('None'), 0, true, false, false, __('SLA Base'));
		
	$id_sla_type_arr[0] = __("Normal SLA");
	$id_sla_type_arr[1] = __("Third party SLA");
	$id_sla_type_arr[2] = __("Both");
					
	$table->data[0][3] = print_select ($id_sla_type_arr, 'id_sla_type', $id_sla_type,'', '', '0', true, 0, false, __('SLA Type'));

	$table->data[1][0] = print_label(__('Max. response time (in hours)'), '', 'text', true);
	$table->data[1][0] .= "<input type='text' name='min_response' id='text-min_response' value='$min_response' size='5' maxlenght='100' onChange='hours_to_dms(\"min\")'>";
		
	$min_response_time = incidents_hours_to_dayminseg ($min_response);
	$table->data[1][0] .= print_input_text ('min_response_time', $min_response_time, '',
		7, 100, true, '', true);

	$table->data[1][1] = print_label(__('Max. resolution time (in hours)'), '', 'text', true);
	$table->data[1][1] .= "<input type='text' name='max_response' id='text-max_response' value='$max_response' size='5' maxlenght='100' onChange='hours_to_dms(\"max\")'>";
	$max_response_time = incidents_hours_to_dayminseg ($max_response);
	$table->data[1][1] .= print_input_text ('max_response_time', $max_response_time, '',
		7, 100, true, '', true);

	$table->data[1][2] = print_input_text ("max_incidents", $max_incidents, '',
		5, 100, true, __('Max. tickets at the same time'));

	$table->data[1][3] = print_label(__('Max. ticket inactivity (in hours)'), '', 'text', true);
	$table->data[1][3] .= "<input type='text' name='max_inactivity' id='text-max_inactivity' value='$max_inactivity' size='5' maxlenght='100' onChange='hours_to_dms(\"inactivity\")'>";
	$max_inactivity_time = incidents_hours_to_dayminseg ($max_inactivity);
	$table->data[1][3] .= print_input_text ('max_inactivity_time', $max_inactivity_time, '',
		7, 100, true, '', true);
		

	$table->data[2][0] = print_input_text ('time_from', $time_from, '',
		5, 10, true, __('Start hour to compute SLA'));
	$table->data[2][1] = print_input_text ('time_to', $time_to, '',
		5, 10, true, __('Last hour to compute SLA'));		
		
	$table->data[2][2] = print_checkbox ('five_daysonly', 1 ,$five_daysonly, true, __('Disable SLA on weekends'));

	$table->data[2][3] = print_checkbox ('no_holidays', 1 ,$no_holidays, true, __('Disable SLA on holidays'));



	$table->data[3][0] = print_textarea ("description", 8, 1, $description, '', true, __('Description'));

	
	
	echo '<form id="form-sla_detail" method="post" action="index.php?sec=incidents&sec2=operation/slas/sla_detail">';
	print_table ($table);
		echo '<div style="width:100%;">';
			unset($table->data);
			$table->width = '100%';
			$table->class = "button-form";
			if ($id) {
				$button = print_submit_button (__('Update'), "update_btn", false, 'class="sub upd"', true);
				$button .= print_input_hidden ('update_sla', 1, true);
				$button .= print_input_hidden ("id", $id, true);
			} else {
				$button = print_input_hidden ('create_sla', 1, true);
				$button .= print_submit_button (__('Create'), "create_btn", false, 'class="sub next"', true);
			}
			
			$table->data[4][0] = $button;
			$table->colspan[4][0] = 4;
			print_table ($table);
		echo "</div>";	
	echo "</form>";
}
else {
	$search_text = (string) get_parameter ('search_text');
	
	$where_clause = "";
	if ($search_text != "") {
		$where_clause = sprintf ('WHERE name LIKE "%%%s%%"
			OR description LIKE "%%%s%%"',
			$search_text, $search_text);
	}
	$table = new StdClass();
	$table->width = '100%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = __('Search');
	$table->data[0][0] .= print_input_text ("search_text", $search_text, "", 20, 100, true);
	$table->data[1][0] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);;
	
	echo "<div class='divform'>";
		echo '<form method="post" action="index.php?sec=incidents&sec2=operation/slas/sla_detail">';
			print_table ($table);
		echo '</form>';
		echo '<form id="form-sla_detail" method="post" action="index.php?sec=incidents&sec2=operation/slas/sla_detail">';
			unset($table->data);
			$table->data[0][0] = print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"',true);
			$table->data[0][0] .= print_input_hidden ('new_sla', 1);
			print_table ($table);
		echo '</form>';
	echo "</div>";
	
	
	$sql = "SELECT * FROM tsla $where_clause ORDER BY name";
	$slas = get_db_all_rows_sql ($sql);
	
	if ($slas !== false) {
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->head[0] = __('Name');
		$table->head[1] = __('Max.Response');
		$table->head[2] = __('Max.Resolution');
		$table->head[3] = __('Max.Tickets');
		$table->head[4] = __('Max.Inactivity');
		$table->head[5] = __('Enforced');
		$table->head[6] = __('Parent');
		$table->head[7] = __('Delete');
		
		foreach ($slas as $sla) {
			$data = array ();
			
			$data[0] = "<a href='index.php?sec=incidents&sec2=operation/slas/sla_detail&id=".$sla['id']."'>".$sla['name']."</a>";
			$data[1] = incidents_hours_to_dayminseg($sla['min_response']);
			$data[2] = incidents_hours_to_dayminseg($sla['max_response']);
			$data[3] = $sla['max_incidents'];
			$data[4] = incidents_hours_to_dayminseg($sla['max_inactivity']);
			
			if ($sla['enforced'] == 1)
				$data[5] = __("Yes");
			else
				$data[5] = __("No");
			$data[6] = get_db_value ('name', 'tsla', 'id', $sla['id_sla_base']);
			$data[7] = '<a href="index.php?sec=incidents&
						sec2=operation/slas/sla_detail&
						delete_sla=1&id='.$sla['id'].'"
						onClick="if (!confirm(\''.__('Are you sure?').'\'))
						return false;">
						<img src="images/cross.png"></a>';
			array_push ($table->data, $data);
		}
		echo "<div class='divresult'>";
			print_table ($table);
		echo "</div>";
	}
}
?>


<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>

<script  type="text/javascript">
	
// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-name');
validate_form("#form-sla_detail");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_sla: 1,
			sla_name: function() { return $('#text-name').val() },
			sla_id: "<?php echo $id ?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required') ?>",
	remote: "<?php echo __('This name already exists') ?>"
};
add_validate_form_element_rules('#text-name', rules, messages);
	
</script>
