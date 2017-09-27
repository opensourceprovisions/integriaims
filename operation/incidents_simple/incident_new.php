<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// LOAD GLOBAL VARS
global $config;

// CHECK LOGIN AND ACLs
check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

// GET THE FIRST KNOWN GROUP OF THE USER
$user_groups = get_user_groups($config['id_user']);
$group_id = reset(array_keys($user_groups));

// GET PRIORITIES
$priorities = get_priorities();

// GET INCIDENT TYPES
$types = get_incident_types();

echo "<h1>".__('CREATE TICKET')."</h1>";
echo "<div id=msg-str></div>";
// NEW INCIDENT FORM
echo "<div id='form_file'>";

$table->width = '98%';
$table->class = 'search-table';
$table->id = "incident-editor";
$table->style = array();
$table->data = array ();
$table->colspan = array ();
$table->colspan[0][0] = 2;
$table->colspan[2][0] = 2;

$table->data[0][0] = print_input_text ('title', '', '', 100, 0, true, __('Title'));
$table->data[1][0] = print_select ($priorities, 'priority_form', 2, '', '', '', true, false, false, __('Priority'));
$table->data[1][1] = print_select($types, 'id_incident_type', '', 'javascript: show_incident_type_fields(1);', 'Select', '', true, 0, true, __('Ticket type'));
$table->data[2][0] = print_textarea ('description', 9, 80, '', '', true, __('Description'));

$action = 'index.php?sec=incidents&sec2=operation/incidents_simple/incidents';
$into_form = print_table($table, true);

$into_form .= '<h3><a href="javascript:toggle_file_addition();">'.__('Add a file').' ('.__('Optional').')<div style="float:left;"><img id="file_moreless" src="images/sort_down.png" border=0>&nbsp;</div></a></h3>';
$into_form .= '<div id="file_addition" style="display:none"><b>'.__('File').'</b>';
$into_form .= '___FILE___';
$into_form .= print_input_hidden ('create_incident', 1, true);
$into_form .= print_input_hidden ('group_id', $group_id, true);
$into_form .= print_textarea ('file_description', 2, 10, '', '', true, __('Description'));
$into_form .= "</div>";
$into_form .= "<div style='text-align:right;'>";
$into_form .= print_button (__('Create'), 'create_incident', false, '', 'style="margin-top:4px;" class="action_btn sub next"', true);
$into_form .= "</div>";

echo print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', 'button-create_incident', true, '___FILE___');
echo "</div>";

?>

<script type="text/javascript" src="include/js/integria_incident_search.js"></script>

<script type="text/javascript">
$(document).ready (function () {

	var id_group = '<?php echo $group_id; ?>';
	var id_user = '<?php echo $config['id_user']; ?>';	
	
	incident_limit("#button-create_incident", id_user, id_group);
});

//Validate form
$('#form-add-file').submit(function() {
	var title = $("#text-title").val();
	
	if (title.length == 0) {
		$("#text-title").fadeOut ('normal',function () {
			pulsate (this);
		});
				
		var error_msg = js_ui_print_error_message('Empty title');
				
		$('#msg-str').html(error_msg);
		return false;
	}

});

function js_ui_print_error_message(msg) {		
	var id = '<?php echo uniqid();?>';
	
	var cancel_button = '<a href="javascript:cancel_msg(\''+id+'\');"><img src="images/cancel.gif" border=0></a>';
	
	var error_msg = '<h3 id="msg_'+id+'" class="error">'+msg+' '+cancel_button+'</h3>';
	
	return error_msg;
}

function toggle_file_addition() {
	$('#file_addition').toggle();
	if($('#file_addition').css('display') == 'none') {
		$('#file_moreless').attr('src','images/sort_down.png');
	}
	else {
		$('#file_moreless').attr('src','images/sort_up.png');
	}
}

</script>
