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

// Load global vars
global $config;
include_once('include/functions_setup.php');

check_login ();

if (defined ('AJAX')) {
	$onchange_template = get_parameter('onchange_template', 0);
	$onchange_actions  = get_parameter('onchange_actions', 0);
	$search_existing_template_name = get_parameter('search_existing_template_name', 0);
	$show_hide_tiny = get_parameter('show_hide_tiny', 0); 

	if($onchange_template){
		$template_name = get_parameter('template_name');	
		$full_filename = "include/mailtemplates/" . $template_name;
		$data[0] = html_entity_decode(file_get_contents ($full_filename));

		$template_name_substr = substr($template_name,0,-4);
		$data[1] = get_db_value('id_group', 'temail_template', 'name', $template_name_substr);
		$data[2] = get_db_value('template_action', 'temail_template', 'name', $template_name_substr);
		$data[3] = get_db_value('predefined_templates', 'temail_template', 'name', $template_name_substr);

	echo json_encode($data);
	return;
	}
}

if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('mailtemplates', $is_enterprise);

//values for defect
$create_template = get_parameter('create_template', 0);
$update_template = get_parameter('update_template', 0);
$delete_template = get_parameter('delete_template', 0);
$search          = get_parameter('search', 0);
$edit            = get_parameter('edit', 0);
$create          = get_parameter('create', 0);

$template_name   = get_parameter('template_name', '');
$action_template = get_parameter('template_action', 0);
$template_group  = get_parameter('template_group', 0);
$data            = get_parameter('data', '');
$name_template   = get_parameter('name_template', 0);

//function create templates fixed
function get_template_action () {
	$actions_template[0]  = __('Create incident body'); //[incident_create.tpl] => incident_create.tpl
	$actions_template[1]  = __('Create incident subject'); //[incident_subject_create.tpl] => incident_subject_create.tpl
	
	$actions_template[2]  = __('Close incident body'); //[incident_close.tpl] => incident_close.tpl
	$actions_template[3]  = __('Close incident subject'); //[incident_subject_close.tpl] => incident_subject_close.tpl
	
	$actions_template[4]  = __('Attach incident subject'); //[incident_subject_attach.tpl] => incident_subject_attach.tpl
	
	$actions_template[5]  = __('Delete incident subject'); //[incident_subject_delete.tpl] => incident_subject_delete.tpl
	
	$actions_template[6]  = __('New WU incident subject'); //[incident_subject_new_wu.tpl] => incident_subject_new_wu.tpl
	
	$actions_template[7]  = __('Update WU incident body'); //[incident_update_wu.tpl] => incident_update_wu.tpl

	$actions_template[8]  = __('Update incident subject'); //[incident_subject_update.tpl] => incident_subject_update.tpl
	$actions_template[9]  = __('Update incident body'); //[incident_update.tpl] => incident_update.tpl
	
	$actions_template[10] = __('SLA max inactivity time incident body'); //[incident_sla_max_inactivity_time.tpl] => incident_sla_max_inactivity_time.tpl
	$actions_template[11] = __('SLA max inactivity time incident subject'); //[incident_sla_max_inactivity_time_subject.tpl] => incident_sla_max_inactivity_time_subject.tpl
	$actions_template[12] = __('SLA max response time incident body'); //[incident_sla_max_response_time.tpl] => incident_sla_max_response_time.tpl
    $actions_template[13] = __('SLA max response time incident subject'); //[incident_sla_max_response_time_subject.tpl] => incident_sla_max_response_time_subject.tpl
    
    $actions_template[14] = __('SLA min response time incident body'); //[incident_sla_min_response_time.tpl] => incident_sla_min_response_time.tpl
    $actions_template[15] = __('SLA min response time incident subject'); //[incident_sla_min_response_time_subject.tpl] => incident_sla_min_response_time_subject.tpl
    
    $actions_template[16] = __('New entry calendar body'); //[new_entry_calendar.tpl] => new_entry_calendar_.tpl
    $actions_template[17] = __('Update entry calendar body'); //[update_entry_calendar.tpl] => update_entry_calendar.tpl

  
	return $actions_template;
}
//update template
if ($update_template && $edit) {
	$id = get_parameter('id', '');
	$update_values["name"] = substr(get_parameter('template_name', ''), 0, -4);
	$update_values["id_group"] = get_parameter('template_group', 0);
	$update_values['template_action'] = get_parameter('template_action', '');
	$template_name = $update_values["name"];
	$sql = "SELECT id FROM temail_template where name = '".$update_values["name"]."' AND id_group = ".$update_values["id_group"]." AND template_action = ".$update_values['template_action'];
	$error_update = get_db_sql($sql);
	
	$data =  unsafe_string (str_replace ("\r\n", "\n", get_parameter("template_content","")));
	$file = "include/mailtemplates/".$template_name.".tpl";

	$fileh = fopen ($file, "wx");
	
	if (fwrite ($fileh, $data)){
		$predefined_templates = get_db_value('predefined_templates', 'temail_template', 'name', $update_values["name"]);
    	if($predefined_templates == 0){
    		if(!$error_update){
    			$id = get_db_value('id', 'temail_template', 'name', $update_values["name"]);
    			$result = process_sql_update('temail_template', $update_values, array('id'=>$id));
    		}
		} else {
			$result = 1;
		}
		if($result != false){
			echo ui_print_success_message (__('File successfully updated'), '', true, 'h3', true);
		} else {
			echo ui_print_error_message(__('Problem updating file'), '', true, 'h3', true);
		}
    } else {
    	echo ui_print_error_message(__('Problem updating file ff'), '', true, 'h3', true);
    }
	fclose ($fileh);
}

//create template
if ($create) {
	$insert_values["name"] = get_parameter('template_name', '');
	$insert_values["id_group"] = get_parameter('template_group', 0);
	$insert_values['template_action'] = get_parameter('template_action', '');
	$template_name = $insert_values["name"];
	$template_group = $insert_values["id_group"];

	$data =  unsafe_string (str_replace ("\r\n", "\n", get_parameter("template_content", "")));
	$file = "include/mailtemplates/".$template_name.".tpl";
	$fileh = fopen ($file, "w");

	if (fwrite ($fileh, $data)){
		$template_id = process_sql_insert("temail_template", $insert_values);
		if ($template_id != false) {
			echo ui_print_success_message (__('File successfully created'), '', true, 'h3', true);
		} else {
			echo ui_print_error_message(__('Problem creating file'), '', true, 'h3', true);
		}
	} else {
		echo ui_print_error_message(__('Problem creating file ff'), '', true, 'h3', true);
	}
	fclose ($fileh);
	chmod($file, 0777);
}

//delete template
if ($delete_template){
	$id_template = get_parameter('id_template', '');
	if($id_template){
		$template_name_delete = get_db_value('name', 'temail_template', 'id', $id_template);
		$file = "include/mailtemplates/".$template_name_delete.".tpl";
		$remove_file = unlink($file);
		if($remove_file){
			$sql = 'DELETE from temail_template WHERE id ='. $id_template;
			$remove_bbdd = mysql_query($sql);
			if($remove_bbdd){
				echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
			} else {
				echo ui_print_error_message(__('Problem deleting template.'), '', true, 'h3', true);	
			}
		} else {
			echo ui_print_error_message(__('Problem deleting template ff'), '', true, 'h3', true);	
		}
	} else {
		echo ui_print_error_message(__('Problem deleting template'), '', true, 'h3', true);
	}
}

if($search){
	//parameters search
	$search_name                = get_parameter('search_name', '');
	$search_id_group            = get_parameter('search_id_group', 0);
	$search_template_action     = get_parameter('search_template_action', -1);
	$search_template_predefined = get_parameter('search_template_predefined', 0);

	$search_params = "&search_name=$search_name&search_id_group=$search_id_group&search_template_action=$search_template_action&search_template_predefined=$search_template_predefined";

	//create form-search
	$table = new stdClass;
	$table->class = 'search-table';
	$table->style = array ();
	$table->data  = array ();
	//search-name
	$table->data[0][0] = print_input_text ("search_name", $search_name, "", 25, 100, true, __('Search'));

	//search-groups
	$groups = get_db_all_rows_sql ("SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre");
	if ($groups == false) {
		$groups = array();
	}
	$user_groups = array();
	foreach ($groups as $group) {
		$user_groups[$group['id_grupo']] = $group['nombre']; 
	}
	$user_groups[0] = __('None'); 
	$table->data[1][0] = print_select ($user_groups, "search_id_group", $search_id_group, '', '', 0, true, false, false, __('Group'), false) . "<div id='group_spinner'></div>";

	//search_actions
	$templatelist = get_template_action();
	$templatelist[-1] = __('None'); 
	$table->data[2][0] = print_select ($templatelist, 'search_template_action', $search_template_action,'', '', '',  true, 0, true, __('Actions'),false, "");

	//search_predefined
	$template_predefined = array();
	$template_predefined[0] = __('All');
	$template_predefined[1] = __('No');
	$template_predefined[2] = __('Yes');
	$table->data[3][0] = print_select ($template_predefined, 'search_template_predefined', $search_template_predefined, '', '', '', true, 0, true, __('Predefined', false, ''));

	//button_submit
	$table->data[4][0] = print_submit_button (__('Search'), "search_btn", false, '', true);
	//$table->data[4][0] .= print_input_hidden ('delete_project', 1);

	echo '<div class="divform">';
		echo '<form method="post" action="index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&search=1">';
			print_table ($table);
		echo '</form>';
		echo '<form method="post" action="index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&create_template=1">';
			echo '<table class="search-table"><tr><td>';
				echo print_submit_button (__('Create Template'));
			echo '</td></tr></table>';
		echo '</form>';
	echo '</div>';

	$where = "SELECT * FROM temail_template WHERE 1=1";
	
	if($search_name){
		$where .= " AND name LIKE '%".$search_name."%'";
	} 

	if($search_id_group){
		$where .= " AND id_group =".  $search_id_group;
	}

	if($search_template_action != -1){
		$where .= " AND template_action =". $search_template_action;
	}

	if($search_template_predefined){
		if($search_template_predefined == 1){
			$where .= " AND predefined_templates = 1";
		} else {
			$where .= " AND predefined_templates = 0";
		}
	}

	$where .=' ORDER BY id DESC';

	$templates_array = get_db_all_rows_sql($where);
	if($templates_array){
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->colspan = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('Template actions');
		$table->head[3] = __('Templates Predefined');
		$table->head[4] = __('Actions');
		$table->data = array ();

		$offset = 0;
		foreach ($templates_array as $key => $value) {
			$data = array ();
			// templates name
			$data[0] = '<a href="index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&update_template=1&name_template='.$value['name'].'">'.$value['name'].'</a>';
			// templates group
			$name_group = get_db_value ('nombre', 'tgrupo', 'id_grupo', $value['id_group']);
			if($name_group){
				$data[1] = $name_group;
			} else{
				$data[1] = '--';
			}
			// templates actions
			$name_action_template =	get_template_action();
			$data[2] = $name_action_template[$value['template_action']];
			//predefined
			if($value['predefined_templates']){
				$data[3] = __('Yes');
			} else {
				$data[3] = __('No');
			}
			// actions
			$data[4] = '<a href="index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&update_template=1&name_template='.$value['name'].'"><img src="images/editor.png" /></a>';
			if(!$value['predefined_templates']){
				$data[4] .= "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_template\",".$value['id'].",0,".$offset.",\"".$search_params."\");'><img src='images/cross.png' title='".__('Delete')."'></a>";
			}
			array_push ($table->data, $data);
		}
	echo "<div class='divresult'>";
		print_table($table);
	echo "</div>";
	} else {
		echo "<div class='divresult'>";
			echo ui_print_error_message(__('No email templates found'), '', true, 'h3', true);
		echo "</div>";
	}
}

if($create_template || $update_template){
	$table = new StdClass();
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	$table->colspan[1][0] = 4;
	$table->data = array ();

	if ($update_template) {
		$templatelist = get_template_files ('');
		$template = $name_template . '.tpl';
		$full_filename = "include/mailtemplates/" . $name_template.".tpl";
		$data = html_entity_decode(file_get_contents ($full_filename));

		$table->data[0][0] = print_select ($templatelist, 'template_name', $templatelist[$template], '', '', '',  true, 0, true, __('Name'),false, "");
	} else {
		$table->data[0][0] = print_input_text ('template_name', $template_name, '', 60, 100, true, __('Name'), false);
	}

	$groups = get_db_all_rows_sql ("SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre");
	if ($groups == false) {
		$groups = array();
	}
	$user_groups = array();
	foreach ($groups as $group) {
		$user_groups[$group['id_grupo']] = $group['nombre']; 
	}

	$templatelist = get_template_action();
	$table->data[0][1] = print_select ($templatelist, 'template_action', $action,'', '', '',  true, 0, true, __('Actions'),false, "");

	$table->data[0][2] = print_select ($user_groups, "template_group", $template_group, '', '', 0, true, false, false, __('Group'), false) . "<div id='group_spinner'></div>"; 
	$table->data[0][3] = "<a href='index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&search=1'><img src='images/flecha_volver.png' title='".__('Back to list')."'/></a>";
	$table->data[1][0] = print_textarea ("template_content", 30, 44, $data,'', true, __('Template contents'));

	if ($update_template) {
		$url = "index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&update_template=1&edit=1&name_template=".$name_template;
	} else {
		$url = "index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&search=1&create=1";
	}
	echo "<form id='form-create_template' method='post' action='".$url."'>";
	print_table ($table);
		echo "<div class='button-form'>";
			if ($update_template) {
				print_submit_button (__('Update'), 'action2', false, 'onClick="disabled_to_readonly();" class="sub upd"');
			} else {
				print_submit_button (__('Create'), 'action2', false, 'class="sub upd"');
			}
		echo "</div>";
	echo '</form>';
}

echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
echo "<div class= 'dialog ui-dialog-content' title='".__("Caution")."' id='change_template_alert'></div>";


function get_template_files ($field) {
	$base_dir = 'include/mailtemplates';
	$files = list_files ($base_dir, ".tpl", 1, 0);
	
	$retval = array ();
	foreach ($files as $file) {
		$retval[$file] = $file;
	}
	return $retval;
}

?>

<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>
<script src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	var create_template = "<?php echo $create_template; ?>";
	var search_template = "<?php echo $search; ?>";
	if (search_template == 0){
		if(create_template == 0){
			onchange_template();
		} else {
			onchange_actions(0, 1);
		}
		$("#template_name").change(onchange_template);
		$("#template_action").change(function(){
			onchange_actions(value_template_action, 0);
		});
		$("textarea").TextAreaResizer ();
	}
});

function disabled_to_readonly(){
	$('#template_group').attr('disabled', false);
	$('#template_action').attr('disabled', false);
}

function inicialiced(){
	tinymce.init({
	    selector: 'textarea',
	    fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",
	    force_br_newlines : true,
	    force_p_newlines : false,
	    forced_root_block : false,
	    plugins: [
	    	'advlist autolink lists link image charmap print preview anchor',
	    	'searchreplace visualblocks code fullscreen',
	    	'insertdatetime media table contextmenu paste code'
	  	],
	  	menubar: false,
	  	toolbar: 'undo redo | styleselect | bold italic fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
	  	content_css: 'include/js/tinymce/integria.css',
	});
}

function onchange_template() {
	var template_name = $("#template_name").val();
	inicialiced();
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=godmode/setup/setup_mailtemplates&onchange_template=1&template_name="+template_name,
		dataType: "json",
		success: function(data){
			if(data[2] == 2 || data[2] ==0 || data[2] ==10 || data[2] ==12 || data[2] ==14 || data[2] ==7 || data[2] ==9 || data[2] ==16 || data[2] ==17) {
				var editor = tinyMCE.get('textarea-template_content');
				tinyMCE.activeEditor.setContent(data[0]);
			} else {
				var editor = tinyMCE.get('textarea-template_content');
				if(editor){
					editor.remove();
				}
				$('#textarea-template_content').val(data[0]);
			}
			$('#template_group').val(data[1]);
			$('#template_action').val(data[2]);
			value_template_action = data[2];
			if (data[3] == 1){
				$('#template_group').attr('disabled', true);
				$('#template_action').attr('disabled', true);
			} else {
				$('#template_group').attr('disabled', false);
				$('#template_action').attr('disabled', false);
			}
		}
	});
}

function onchange_actions(id, new_create) {
	if(new_create){
		var editor = tinyMCE.get('textarea-template_content');
		var data   = $('#template_action').val();
		value_template_action = data;
		
		if(data == 2 || data ==0 || data ==10 || data ==12 || data ==14 || data ==7 || data ==9 || data ==16 || data ==17) {
			$('#textarea-template_content').empty();
			$('#textarea-template_content').val('');
			tinymce.init({
			    selector: 'textarea',
			    fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",
			    force_br_newlines : true,
			    force_p_newlines : false,
			    forced_root_block : false,
			    plugins: [
			    	'advlist autolink lists link image charmap print preview anchor',
			    	'searchreplace visualblocks code fullscreen',
			    	'insertdatetime media table contextmenu paste code'
			  	],
			  	menubar: false,
			  	toolbar: 'undo redo | styleselect | bold italic fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
			  	content_css: 'include/js/tinymce/integria.css',
			});
		} else {
			if(editor){
				editor.remove();
			}
			$('#textarea-template_content').empty();
			$('#textarea-template_content').val('');
		}
	}
	else{
		show_alert_change_actions_templates('change_function', id);
	}
}

function show_alert_change_actions_templates (name, id) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/mail&change_template_alert=1",
		dataType: "html",
		success: function(data){
			$("#change_template_alert").html (data);
			$("#change_template_alert").show ();
			$("#change_template_alert").dialog ({
					resizable: false,
					draggable: false,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 440,
					height: 195
				});
			$("#change_template_alert").dialog('open');
			$("#change_template_form").submit(function (e){
				e.preventDefault();
				var editor = tinyMCE.get('textarea-template_content');
				var data   = $('#template_action').val();
				value_template_action = data;
				
				if(data == 2 || data ==0 || data ==10 || data ==12 || data ==14 || data ==7 || data ==9 || data ==16 || data ==17) {
					$('#textarea-template_content').empty();
					$('#textarea-template_content').val('');
					tinymce.init({
					    selector: 'textarea',
					    fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",
					    force_br_newlines : true,
					    force_p_newlines : false,
					    forced_root_block : false,
					    plugins: [
					    	'advlist autolink lists link image charmap print preview anchor',
					    	'searchreplace visualblocks code fullscreen',
					    	'insertdatetime media table contextmenu paste code'
					  	],
					  	menubar: false,
					  	toolbar: 'undo redo | styleselect | bold italic fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
					  	content_css: 'include/js/tinymce/integria.css',
					});
				} else {
					if(editor){
						editor.remove();
					}
					$('#textarea-template_content').empty();
				$('#textarea-template_content').val('');
				}

				$("#change_template_alert").dialog('close');

			});
			$("#button-modal_cancel").click(function (e){
				e.preventDefault();
				$("#change_template_alert").dialog('close');
				$("#template_action").val(id);
			});
			$('.ui-widget-overlay').click(function(e){
				e.preventDefault();
				$("#change_template_alert").dialog('close');
				$("#template_action").val(id);
				
			});

		}
	});

}

trim_element_on_submit('input[name="template_name"]');
validate_form("#form-create_template");
var rules, messages;
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_template_name: 1,
			template_name: function() { return $('input[name="template_name"]').val() }
        }
	}
};
messages = {
	//required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This name already exists')?>"
};
add_validate_form_element_rules('input[name="template_name"]', rules, messages);
</script>