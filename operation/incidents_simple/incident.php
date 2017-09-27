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

include("include/functions_workunits.php");

// SET VARS
$width = '100%';

// CHECK LOGIN AND ACLs
check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

// GET INCIDENT ID
$incident_id = (int) get_parameter('id', 0);

// GET ACTIVE TAB
$active_tab = get_parameter('active_tab', 'details');

if($incident_id == 0) {
	ui_print_error_message(__('Ticket not found'));
	exit;
}

// GET ACTION PARAMETERS
$upload_file = get_parameter('upload_file');
$add_workunit = get_parameter('add_workunit');

// ACTIONS
if($upload_file) {
	$filename = get_parameter('upfile');
	$file_description = get_parameter('description',__('No description available'));

	$file_temp = sys_get_temp_dir()."/$filename";
	
	$result = attach_incident_file ($incident_id, clean_output($file_temp), $file_description);
	
	echo $result;
	
	$active_tab = 'files';
}

if($add_workunit) {
	$note = get_parameter('note');
	$public = 1;
	$timeused = "0.05";
	
	$result = create_workunit ($incident_id, $note, $config["id_user"], $timeused, 0, "", $public, 0);
	
	if($result) {
		ui_print_success_message(__('Workunit added'));
	}
	else {
		ui_print_error_message(__('There was a problem adding workunit'));
	}
	$active_tab = 'workunits';
}

// GET INCIDENT FROM DATABASE
$incident = get_full_incident($incident_id);

// TABS
?>

<ul style="height: 30px;" class="ui-tabs-nav">
	<li class="ui-tabs" id="li_files">
		<a href='javascript:' id='tab_files' class='tab'><span><?php echo __('Files') ?></span></a>
	</li>	
	<li class="ui-tabs" id="li_workunits">
		<a href='javascript:' id='tab_workunits' class='tab'><span><?php echo __('Workunits') ?></span></a>
	</li>
	<li class="ui-tabs-selected" id="li_details">
		<a href='javascript:' id='tab_details' class='tab'><span><?php echo __('Details') ?></span></a>
	</li>	
	<li class="ui-tabs-title">
		<?php
			// PRINT INCIDENT
			echo "<h1>".__('Ticket')." #$incident_id - ".$incident['details']['titulo']." <a href='javascript:load_tab(\"current\");'><img src='images/refresh.png'></a></h1>";
		?>
	</li>
</ul>

<?php 

// ACTION BUTTONS
echo "<div style='width:$width;text-align:right;'>";
print_button (__('Add workunit'), 'add_workunit_show', false, '', 'style="margin-top:8px;" class="action_btn sub create"');
print_button (__('Add file'), 'add_file_show', false, '', 'style="margin-top:8px;" class="action_btn sub create"');
echo "</div>";

// ADD WORKUNIT FORM
echo "<div style='width:$width;display:none;' id='form_workunit' class='form_tabs'>";
echo "<form method='post' action=''>";
print_textarea ('note', 5, 10, '', '', false, __('Workunit'));
print_input_hidden ('add_workunit', 1);
print_input_hidden ('id', $incident_id);
echo "<div style='text-align:right'>";
print_submit_button (__('Add'), 'add_workunit_button', false, 'style="margin-top:4px;" class="sub create"');
echo "</div>";
echo "</form>";
echo "</div>";

// UPLOAD FILE FORM
echo "<div style='width:$width;display:none;' id='form_file' class='form_tabs'>";
$action = '';
$into_form = print_input_hidden ('id', $incident_id, true);
$into_form .= print_input_hidden ('upload_file', 1, true);
$into_form .= print_textarea ('description', 2, 10, '', '', true, __('Description'));
$into_form .= "<div style='text-align:right;'>";
$into_form .= print_button (__('Upload'), 'add_file', false, '', 'style="margin-top:4px;" class="action_btn sub upload"', true);
$into_form .= "</div>";
echo '<b>'.__('File').'</b>';
echo '<br>'.print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', 'button-add_file', true);
echo "</div>";

// LOADING DIV
echo '<div id="loading" style="width:60%;margin-top:20;display:none;">'.__('Loading').'...</div>';

// DATA DIVS
echo '<div id="details_data" class="tab_data"></div>';
echo '<div id="workunits_data" class="tab_data"></div>';
echo '<div id="files_data" class="tab_data"></div>';

// Div with the active tab name in all moment
echo "<div id='active_tab' style='display:none'>$active_tab</div>";
// Div with the incident id in all moment
echo "<div id='incident_id' style='display:none'>$incident_id</div>";
?>

<script type="text/javascript">
$(document).ready (function () {
	// FORMS SHOW/HIDE
	$('#button-add_workunit_show').click(function() {
		
		tab = $('#active_tab').html();
		if(tab != 'workunits') {
			load_tab('workunits');
		}
		
		$('#form_workunit').toggle();
		$('#form_file').hide();
	});
	
	$('#button-add_file_show').click(function() {
		
		tab = $('#active_tab').html();
		if(tab != 'files') {
			load_tab('files');
		}
		
		$('#form_file').toggle();
		$('#form_workunit').hide();
	});
	
	// MENU TABS
	$('.tab').click(function() {
		tab = $(this).attr('id').split('_')[1];
		
		// Hide all the forms
		$('.form_tabs').hide();
		
		// Load tab
		load_tab(tab);
	});
	
	// Load the active tab
	$('#tab_<?php echo $active_tab; ?>').trigger('click');
});

function load_tab(tab) {
	if(tab == 'current') {
		tab = $('#active_tab').html();
	}
	
	// Update tab info
	$('#active_tab').html(tab);
	
	incident_id = $('#incident_id').html();
	
	// Change tabs style
	$('.ui-tabs-selected').attr('class','ui-tabs');
	$('#li_'+tab).attr('class','ui-tabs-selected');

	// Delete all tabs
	$('.tab_data').html('');
	
	// Show loading
	$('#loading').show();
		
	var params = [];
	params.push("incident_id=" + incident_id);
	params.push("page=operation/incidents_simple/incident." + tab);
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'html',
		async: false,
		type: 'POST',
		url: action="ajax.php",
		success: function (data) {
			// Fill the tab div with the updated data
			$('#'+tab+'_data').html(data);
		}
	});
	
	// Hide loading
	$('#loading').hide();
}
</script>
