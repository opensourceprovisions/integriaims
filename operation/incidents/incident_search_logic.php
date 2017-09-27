<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars

check_login ();

require_once ('include/functions_incidents.php');

if (defined ('AJAX')) {
	ob_clean();
	$get_type_fields_table = (boolean) get_parameter("get_type_fields_table");

	if ($get_type_fields_table) {

		$id_incident_type = (int) get_parameter("id_incident_type");

		$table_type_fields = new stdclass;
		$table_type_fields->width = "100%";
		$table_type_fields->class = "search-table";
		$table_type_fields->data = array();
		$table_type_fields->colspan = array();
		
		$type_fields = incidents_get_type_fields ($id_incident_type);
		
		$column = 0;
		$row = 0;
		if ($type_fields) {
			foreach ($type_fields as $key => $type_field) {
				switch ($type_field['type']) {
					case "text": 
						$input = print_input_text('search_type_field_'.$type_field['id'], $data, '', 30, 30, true, $type_field['label']);
						break;
					
					case "combo":
						$combo_values = explode(",", $type_field['combo_value']);
						$values = array();
						foreach ($combo_values as $value) {
							$values[$value] = $value;
						}
						$input = print_select ($values, 'search_type_field_'.$type_field['id'], $data, '', __('Any'), '', true, false, false, $type_field['label']);
						break;

					case "linked":
						$linked_values = explode(",", $type_field['linked_value']);
						$values = array();
						foreach ($linked_values as $value) {
							$value_without_parent =  preg_replace("/^.*\|/","", safe_output($value));
							$values[$value_without_parent] = $value_without_parent;
							
							$has_childs = get_db_all_rows_sql("SELECT * FROM tincident_type_field WHERE parent=".$type_field['id']);
							if ($has_childs) {
								$i = 0;
								foreach ($has_childs as $child) {
									if ($i == 0) 
										$childs = $child['id'];
									else 
										$childs .= ','.$child['id'];
									$i++;
								}
								$childs = "'".$childs."'";
								$script = 'javascript:change_linked_type_fields_table('.$childs.','.$type_field['id'].');';
							} else {
								$script = '';
							}
						}
						$input = print_select ($values, 'search_type_field_'.$type_field['id'], $data, $script, __('Any'), '', true, false, false, $type_field['label']);
						break;

					case "numeric":
						$input = print_input_number ('search_type_field_'.$type_field['id'], $data, 1, 1000000, '', true, $type_field['label']);
						break;

					case "date":
						$input = print_input_date ('search_type_field_'.$type_field['id'], $data, '', '', '', true, $type_field['label']);
						break;

					case "textarea":
						if($row != 0){
							$column++;
							$row=0;
						}
						$table_type_fields->colspan[$column][0] = 4;
						$input = print_textarea ('search_type_field_'.$type_field['id'], 5, 5, $data, '', true, $type_field['label']);
						$textarea=1; 
						break;
				}				
				
				$table_type_fields->data[$column][$row] = $input;
				if ($textarea){
					$column++;
					$row = 0;
					$textarea = 0;
				}else if ($row < 3) {
					$row++;
				} else {
					$row=0;
					$column++;
				}
			}
			if ($table_type_fields->data) {
				print_table($table_type_fields);
			}
		}
	}
	return;
}

echo "<div id='incident-search-content'>";

echo "<h2>" . __("Support") . "</h2>";
if (get_parameter ('id_myticket') == 1){
	echo "<h4>" .__('My Tickets');
} else {
	echo "<h4>" .__('Ticket search');
}

echo integria_help ("incident_search", true);
echo "<div id='button-bar-title' style='margin-right: 12px;'>";
echo "<ul>";
print_autorefresh_button_ticket();
echo "<li style=''>";
echo "<a href='javascript:' onclick='toggleDiv (\"custom_search\")'>".__('Custom filter')."&nbsp;".integria_help ("custom_search", true)."</a>";
echo "</li>";

echo "</ul>";
echo "</div>";
echo "</h4>";
$search_form = (bool) get_parameter ('search_form');
$create_custom_search = (bool) get_parameter ('save-search');
$delete_custom_search = (bool) get_parameter ('delete_custom_search');
$id_search = get_parameter ('saved_searches');
$serialized_filter = get_parameter("serialized_filter");

//If serialize filter use the filter stored in a file in tmp dir
if ($serialized_filter) {
	$filter = unserialize_in_temp($config["id_user"]);
}

//Filter auxiliar array 
$filter_form = $filter;

$has_im  = give_acl ($config['id_user'], $filter_form['id_group'], "IM");

echo '<div id="msg_ok_hidden" style="display:none;">';
	echo ui_print_success_message (__('Custom filter saved'), '', true, 'h3', true);
echo '</div>';
echo '<div id="msg_error_hidden" style="display:none;">';
	echo ui_print_error_message (__('Could not create custom filter'), '', true, 'h3', true);
echo '</div>';

/* Get a custom filter*/
if ($id_search && !$delete_custom_search) {
	
	$search = get_custom_search ($id_search, 'incidents');
	
	if ($search) { 
		
		if ($search["form_values"]) {
			
			$filter = unserialize($search["form_values"]);
			$filter_form = $filter;
			
			echo ui_print_success_message (__('Custom filter "%s" loaded'), '', true, 'h3', true);
		}
		else {
			echo ui_print_error_message (__('Could not load custom filter'), '', true, 'h3', true);
		}
	}
	else {
		echo ui_print_error_message (__('Could not load custom filter'), '', true, 'h3', true);
	}
}

/* Delete a custom saved search via AJAX */
if ($delete_custom_search) {
	
	$sql = sprintf ('DELETE FROM tcustom_search
		WHERE id_user = "%s"
		AND id = %d',
		$config['id_user'], $id_search);
	$result = process_sql ($sql);
	if ($result === false) {
		echo ui_print_error_message (__('Could not delete custom filter'), '', true, 'h3', true);
	}
	else {
		echo ui_print_success_message (__('Custom filter deleted'), '', true, 'h3', true);
	}
}

//FORM AND TABLE TO MANAGE CUSTOM SEARCHES
$table = new stdClass;
$table->id = 'saved_searches_table';
$table->width = '100%';
$table->class = 'search-table-button';
$table->size = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->data = array ();
$sql = sprintf ('SELECT id, name FROM tcustom_search
	WHERE id_user = "%s"
	AND section = "incidents"
	ORDER BY name',
	$config['id_user']);
$table->data[0][0] = print_select_from_sql ($sql, 'saved_searches', $id_search, '', __('Select'), 0, true, false, true, __('Custom filters'));

//If a custom search was selected display cross
if ($id_search) {
	$table->data[0][0] .= '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_search&delete_custom_search=1&saved_searches='.$id_search.'">';
	$table->data[0][0] .= '<img src="images/cross.png" title="' . __('Delete') . '"/></a>';
}
$table->data[0][1] = print_input_text ('search_name', '', '', 40, 60, true, __('Save current filter'));
$table->data[0][2] = print_submit_button (__('Save'), 'save-search', false, 'class="sub save" style="margin-top: 13px;"', true);

echo '<div id="custom_search" style="display: none;">';
echo '<form id="saved-searches-form" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_search">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}
print_input_hidden ("offset", get_parameter("offset"));
print_table ($table);
echo '</form>';
echo '</div>';

/* Show search form via AJAX */

form_search_incident (false, $filter_form);

echo '<div id="pager" class="hide pager">';
echo '<form>';
echo '<img src="images/control_start_blue.png" class="first" />';
echo '<img src="images/control_rewind_blue.png" class="prev" />';
echo '<input type="text" class="pager pagedisplay" size=5 />';
echo '<img src="images/control_fastforward_blue.png" class="next" />';
echo '<img src="images/control_end_blue.png" class="last" />';
echo '<select class="pager pagesize" style="display:none">';
echo '<option selected="selected" value="5">5</option>';
echo '</select>';
echo '</form>';
echo '</div>';

if ($filter_form['group_by_project']) {
	incidents_search_result_group_by_project ($filter);
} else {
	incidents_search_result($filter, false, false, true);
}

/* Add a form to carry filter between statistics and search views */
echo '<form id="stats_form" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_search&option=stats" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}
print_input_hidden ("offset", get_parameter("offset"));
echo "</form>";

/* Add a form to carry filter between graphs and search views */
echo '<form id="graph_incidents_form" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_search&option=graph" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}
print_input_hidden ("offset", get_parameter("offset"));
echo "</form>";

//Store serialize filter
serialize_in_temp($filter, $config["id_user"]);
$resolution = 0;
$parent_name = 0;
$id_parent = 0;

$table = new StdClass();
$table->class = 'search-table-button';
$table->width = '100%';
$table->id = 'incident_massive';
$table->data = array();
$table->style = array ();

$table->data[0][0] = combo_incident_status (-1, 0, 0, true, true);
$table->data[0][1] = print_select (get_priorities (),'mass_priority', -1, '', __('Select'), -1, true, 0, true, __('Priority'), false, 'min-width: 70px;');
$table->data[0][2] = combo_incident_resolution ($resolution, false, true, true);
$table->data[0][3] = print_select_from_sql('SELECT id_usuario, nombre_real FROM tusuario;', 'mass_assigned_user', '0', '', __('Select'), -1, true, false, true, __('Owner'));

//Task
$table->data[1][0] = combo_task_user (0, $config["id_user"], 0, 0, true);
//Groups
$table->data[1][1] =  print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo;', 'mass_groups', '0', '', __('Select'), -1, true, false, true, __('Groups'));

if ($has_im) {
	//Parent ticket
	$table->data[1][2] = print_input_text ('search_parent', $parent_name, '', 10, 100, true, __('Parent ticket'));
	$table->data[1][2] .= print_input_hidden ('id_parent', $id_parent, true);
	$table->data[1][2] .= print_image("images/cross.png", true, array("onclick" => "clean_parent_field()", "style" => "cursor: pointer"));
	//Delete tickets
	$table->data[1][3] = "<b>" . __("Delete all tickets") . "</b>";
	$table->data[1][3] .= print_image("images/cross.png", true, array("onclick" => "delete_massive_tickets()", "style" => "cursor: pointer"));
}

$table->data[2][0] = "<div class='button-form'>" . print_submit_button (__('Update'), 'massive_update', false, 'class="sub next"', true) . "</div>";
$table->colspan[2][0] = 4;

$massive_oper_incidents = print_table ($table, true);

echo print_container_div('massive_oper_incidents', __('Massive operations over selected items'), $massive_oper_incidents, 'closed', true, '20px');

echo "<div class= 'dialog ui-dialog-content' title='".__("Tickets")."' id='inventory_search_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.metadata.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>

<script type="text/javascript">

// Datepicker
add_ranged_datepicker ("#text-search_first_date", "#text-search_last_date", null);

//Javascript search form configuration
$(document).ready(function () {
	$("#stats_form_submit").click(function (event) {
		event.preventDefault();
		$("#stats_form").submit();
	});
	
	$("#graph_incidents").click(function (event) {
		event.preventDefault();
		$("#graph_incidents_form").submit();
	});
		
	$("#saved_searches").change(function() {
		$("#saved-searches-form").submit();
	});
	
	//JS for massive operations
	$("#checkbox-incidentcb-all").change(function() {
		$(".cb_incident").prop('checked', $("#checkbox-incidentcb-all").prop('checked'));
	});

	$(".cb_incident").click(function(event) {
		event.stopPropagation();
	});
	
	$("#submit-massive_update").click(function(event) {
		process_massive_updates();
	});
	
	// Form validation
	trim_element_on_submit('#text-search_string');
	trim_element_on_submit('#text-search_name');
	trim_element_on_submit('#text-inventory_name');
	
	//Autocomplete for owner search field
	var idUser = "<?php echo $config['id_user'] ?>";
	
	bindAutocomplete ("#text-search_id_user", idUser);
	bindAutocomplete ("#text-search_id_creator", idUser);
	bindAutocomplete ("#text-search_editor", idUser);
	bindAutocomplete ("#text-search_closed_by", idUser);
	
	if ($("#search_incident_form").length > 0) {
		validate_user ("#search_incident_form", "#text-search_id_user", "<?php echo __('Invalid user')?>");	
		validate_user ("#search_incident_form", "#text-search_id_creator", "<?php echo __('Invalid user')?>");
		validate_user ("#search_incident_form", "#text-search_editor", "<?php echo __('Invalid user')?>");
		validate_user ("#search_incident_form", "#text-search_closed_by", "<?php echo __('Invalid user')?>");
	}
	
	/*Open parent search popup*/
	$("#text-search_parent").focus(function () {
		parent_search_form('');
	});
	
	$("#submit-save-search").click (function () {
		search_name = $('#text-search_name').val();

		search_values = get_form_input_values ('search_incident_form');
		
		values = get_form_input_values (this);
		values.push ({name: "page", value: "operation/incidents/incident_search"});
		$(search_values).each (function () {
			values.push ({name: "form_values["+this.name+"]", value: this.value});
		});
		values.push ({name: "create_custom_search", value: 1});
		values.push ({name: "search_name", value: search_name});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				if (status == 'success') {
					$("#msg_ok_hidden").show ();
					msg = "Custom search saved";
				} else {
					$("#msg_error_hidden").show ();
					msg = "Could not create custom search";
				}
				location.reload();
			},
			"html"
		);
		return false;
	});
});

function changeIncidentOrder(element, order) {
	$('#hidden-search_order_by').val('{ "'+element+'" : "'+order+'" }');
	$('#saved-searches-form').submit();
}

function loadInventory(id_inventory) {
	
	$('#hidden-id_inventory').val(id_inventory);
	$('#text-inventory_name').val(id_inventory);
	
	$("#search_inventory_window").dialog('close');
}

</script>
