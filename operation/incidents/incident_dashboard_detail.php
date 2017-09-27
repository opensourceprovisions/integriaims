<?php
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

include_once ("include/functions_graph.php");
include_once ("include/functions_user.php");

echo "<div class= 'dialog ui-dialog-content' id='info_inventory_window'></div>";

//id could be passed by another view, if not, we try to get from parameters
if(!isset($id)) {
	$id = get_parameter("id", false);
}

if ($id) {
	$incident = get_incident ($id);

	if ($incident !== false) {
		$id_grupo = $incident['id_grupo'];
	} else {
		echo "<h1>".__("Ticket")."</h1>";
		echo ui_print_error_message (__("There is no information for this ticket"), '', true, 'h3', true);
		echo "<br>";
		echo "<a style='margin-left: 90px' href='index.php?sec=incidents&sec2=operation/incidents/incident_search'>".__("Try the search form to find the incident")."</a>";
		return;
	}
}

if (isset($incident)) {
	//Incident creators must see their incidents
	$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident));
	$standalone_check = enterprise_hook("manage_standalone", array($incident));

	if (($check_acl !== ENTERPRISE_NOT_HOOK && !$check_acl) || ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check)) {

	 	// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket (External user) ".$id);
		include ("general/noaccess.php");
		exit;
	}
} else if (! give_acl ($config['id_user'], $id_grupo, "IR")) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to ticket ".$id);
	include ("general/noaccess.php");
	exit;
} else {
	//No incident but ACLs enabled
	echo ui_print_error_message (__("The ticket doesn't exist"), '', true, 'h3', true);
	return;
}

if  (($incident["id_creator"] == $config["id_user"]) AND ($incident["estado"] == 7) AND ($incident['score'] == 0)) {
	if (($incident["score"] == 0)) {
		$score_msg = incidents_get_score_table ($id_ticket);
		echo $score_msg;
	}
}

/* Users affected by the incident */
$table = new stdClass();
$table->width = '100%';
$table->class = "none";
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->style = array();
$table->data = array ();
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->style [0] = "vertical-align: top";
$table->style [1] = "vertical-align: top";

$resolution = incidents_get_incident_resolution_text($id);
$priority = incidents_get_incident_priority_text($id);
$priority_image = print_priority_flag_image ($incident['prioridad'], true);
$group = incidents_get_incident_group_text($id);
$status = incidents_get_incident_status_text($id);
$type = incidents_get_incident_type_text($id);

// Get the status color and icon
if ($incident['estado'] < 3) {
	$status_color = STATUS_COLOR_NEW;
	$status_icon = 'status_new';
}
else if ($incident['estado'] < 7) {
	$status_color = STATUS_COLOR_PENDING;
	$status_icon = 'status_pending';
}
else {
	$status_color = STATUS_COLOR_CLOSED;
	$status_icon = 'status_closed';
}

// Details
//$incident_details = "<table width='97%' id='details_table'>";
$incident_details = "<tr>";
$incident_details .= "<td>".__("Status")."</td>";
$incident_details .= "<td>".__("Group")."</td>";
$incident_details .= "<td>".__("Priority")."</td>";
$incident_details .= "<td>".__("Resolution")."</td>";
$incident_details .= "<td>".__("Type")."</td>";
$incident_details .= "</tr>";
$incident_details .= "<tr>";
$incident_details .= "<td>" . print_image('images/' . $status_icon . '.png', true) . "</td>";
$incident_details .= "<td>" . print_image('images/group.png', true) . "</td>";
$incident_details .= "<td>" . $priority_image . "</td>";
$incident_details .= "<td>" . print_image('images/resolution.png', true) . "</td>";
$incident_details .= "<td>" . print_image('images/incident.png', true) . "</td>";
$incident_details .= "</tr>";
$incident_details .= "<tr class='bold incident_details_bottom'>";
$incident_details .= "<td>".$status."</td>";
$incident_details .= "<td>".$group."</td>";
$incident_details .= "<td>".$priority."</td>";
$incident_details .= "<td>".$resolution."</td>";
$incident_details .= "<td>".$type."</td>";
$incident_details .= "</tr>";
//$incident_details .= "</table>";

$left_side = print_container('incident_details', __('Details'), $incident_details, 'open', true, true, '', 'no_border_center', 5, 'less_widht');

/* Description */
$incident_description = clean_output_breaks($incident["descripcion"]);

$incident_table = "<tr>";
$incident_table .= "<td colspan=6 style='max-width: 300px; word-wrap: break-word;'>".$incident_description."</td>";
$incident_table .= "</tr>";

$left_side .= print_container('incident_description', __('Description'), $incident_table, 'open', true, true, '', '', 5, 'less_widht');

// Advanced details
$editor = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident["editor"]));
$creator_group = get_db_value_filter ("nombre", "tgrupo", array("id_grupo" => $incident["id_group_creator"]));

if ($incident["sla_disabled"]) {
	$sla = __("Yes");
}  else {
	$sla = __("No");
}

$task = incidents_get_incident_task_text($id);
if ($incident["id_parent"]){
	$parent = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_parent"]."'>".__("Ticket")." #".$incident["id_parent"]."</a>";
} else {
	$parent = __("Ticket")." #".$incident["id_parent"];
}

$objects = get_inventories_in_incident($id);

if ($objects) {
	$objects = implode(", ", $objects);
}
else {
	$objects = __("None");
}

$emails = $incident["email_copy"];

$incident_adv_details = "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/editor.png', true)."</td>";
$incident_adv_details .= "<td>".__("Editor").":</td><td><b>".$editor."</b></td>";
$incident_adv_details .= "</tr>";

$incident_adv_details .= "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/group.png', true)."</td>";
$incident_adv_details .= "<td>".__("Creator group").":</td><td><b>".$creator_group."</b></td>";
$incident_adv_details .= "</tr>";

$incident_adv_details .= "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/incident.png', true)."</td>";
$incident_adv_details .= "<td>".__("Parent ticket").":</td><td><b>".$parent."</b></td>";
$incident_adv_details .= "</tr>";

$incident_adv_details .= "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/task.png', true)."</td>";
$incident_adv_details .= "<td>".__("Task").":</td><td><b>".$task."</b></td>";
$incident_adv_details .= "</tr>";

$incident_adv_details .= "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/sla.png', true)."</td>";
$incident_adv_details .= "<td>".__("SLA disabled").":</td><td><b>".$sla."</b></td>";
$incident_adv_details .= "</tr>";

$incident_adv_details .= "<tr>";
$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/object.png', true)."</td>";
$incident_adv_details .= "<td>".__("Objects affected").":</td><td><b>".$objects."</b></td>";
$incident_adv_details .= "</tr>";

if ($emails) {
	$incident_adv_details .= "<tr>";
	$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/email.png', true)."</td>";
	$incident_adv_details .= "<td>".__("Email").":</td><td><b>".$emails."</b></td>";
	$incident_adv_details .= "</tr>";
}

if (($incident['gold_medals']) || ($incident['black_medals'])) {
	$incident_adv_details .= "<tr>";
	$incident_adv_details .= "<td class='advanced_details_icons'>".print_image('images/insignia_naranja.png', true)."</td>";
	$incident_adv_details .= "<td>".__("Medals").":</td><td>".print_image('images/insignia_dorada.png', true)."(".$incident['gold_medals'].")".print_image('images/insignia_gris.png', true)."(".$incident['black_medals'].")"."</td>";
	$incident_adv_details .= "</tr>";
}

$left_side .= print_container('incident_adv_details', __('Advanced details'), $incident_adv_details, 'open', true, true, '', 'no_border', 5, 'less_widht');

if ($incident["id_incident_type"]) {

	$type_name = get_db_value("name", "tincident_type", "id", $incident["id_incident_type"]);

	$incident_custom_fields = "<table class='advanced_details_table alternate'>";
	$incident_custom_fields .= "<tr>";
	$incident_custom_fields .= "<td><b>".$type_name."</b></td>";
	$incident_custom_fields .= "</tr>";

	$fields = incidents_get_all_type_field ($incident["id_incident_type"], $id);

	foreach ($fields as $f) {

		if ($f["type"] != "textarea") {
			$incident_custom_fields .= "<tr>";
			$incident_custom_fields .= "<td>";
				$incident_custom_fields .= "<table>";
				$incident_custom_fields .= "<tr>";
				$incident_custom_fields .= "<td><b>".$f["label"].":</b></td><td>".safe_output($f["data"])."</td>";
				$incident_custom_fields .= "</tr>";
				$incident_custom_fields .= "</table>";
			$incident_custom_fields .= "</td>";
			$incident_custom_fields .= "</tr>";	
		} else {
			$incident_custom_fields .= "<tr>";
			$incident_custom_fields .= "<td>";
				$incident_custom_fields .= "<table>";
				$incident_custom_fields .= "<tr>";
				$incident_custom_fields .= "<td><b>".$f["label"].":"."</b></td>";
				$incident_custom_fields .= "</tr>";
				$incident_custom_fields .= "</table>";
			$incident_custom_fields .= "</td>";
			$incident_custom_fields .= "</tr>";	
			$incident_custom_fields .= "<tr>";
			$incident_custom_fields .= "<td>";	
				$incident_custom_fields .= "<table>";
				$incident_custom_fields .= "<tr>";
				$incident_custom_fields .= "<td>".clean_output_breaks($f["data"])."</td>";
				$incident_custom_fields .= "</tr>";
				$incident_custom_fields .= "</table>";
			$incident_custom_fields .= "</td>";
			$incident_custom_fields .= "</tr>";	
		}

	}

	$incident_custom_fields .= "</table>";

	$left_side .= print_container_div('incident_custom_fields', __('Custom fields'), $incident_custom_fields, 'open',true, true, '' , 'no_border',4, 'less_widht');
}

/**** DASHBOARD RIGHT SIDE ****/

// People
//$incident_users .= "<table style='width: 100%;'>";
$incident_users = "<tr>";

$long_name_creator = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident["id_creator"]));
$avatar_creator = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident["id_creator"]));

$incident_users .= "<td>";
$creator = $incident['id_creator'];
$options["onclick"]="openUserInfo(\"$creator\")";
if($avatar_creator){
	$incident_users .= '<div class="bubble">' . print_image('images/avatars/' . $avatar_creator . '.png', true, $options) . '</div>';
} else {
	$incident_users .= '<div class="bubble"></div>';
}
$incident_users .= '<span>' . __('Created by') . ':</span><br>' . $long_name_creator;
$incident_users .= "</td>";

$long_name_asigned = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident["id_usuario"]));
$avatar_asigned = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident["id_usuario"]));
$owner = $incident['id_usuario'];
$options["onclick"]="openUserInfo(\"$owner\")";

$incident_users .= "<td>";
if($avatar_asigned){
	$incident_users .= '<div class="bubble">' . print_image('images/avatars/' . $avatar_asigned . '.png', true, $options) . '</div>';
} else {
	$incident_users .= '<div class="bubble">'. print_image('images/avatars/avatar_notyet.png', true, $options) . '</div>';
}
$incident_users .= '<span>' . __('Owned by') . ':</span><br>' . $long_name_asigned;
$incident_users .= "</td>";

$avatar_closer = get_db_value_filter ("avatar", "tusuario", array("id_usuario" => $incident["closed_by"]));

$incident_users .= "<td>";
$incident_users .= '<div class="bubble">';
if ($incident["estado"] != STATUS_CLOSED) {
	$long_name_closer = '<em>' . __('Not closed yet') . '</em>';
	$incident_users .= print_image('images/avatar_notyet.png', true);
}
else if (empty($incident["closed_by"])) {
	$long_name_closer = '<em>' . __('Unknown') . '</em>';
	$incident_users .= print_image('images/avatar_unknown.png', true);
}
else {
	$long_name_closer = get_db_value_filter ("nombre_real", "tusuario", array("id_usuario" => $incident["closed_by"]));
	$closer = $incident['closed_by'];
	$options["onclick"]="openUserInfo(\"$closer\")";
	$incident_users .= print_image('images/avatars/' . $avatar_closer  . '.png', true, $options);
}
$incident_users .= '</div>';
$incident_users .= '<span>' . __('Closed by') . ':</span><br>' . $long_name_closer;
$incident_users .= "</td>";


$incident_users .= "</tr>";

//$incident_users .= "</table>";

$right_side = print_container('incident_users', __('People').print_help_tip (_('Click on icons for more details'), true), $incident_users,'open',true,'','','',4, 'less_widht');

// Quick editor
if ($config['enabled_ticket_editor']) {

	if (get_standalone_user($config["id_user"])) {
		$check_standalone_acl = enterprise_hook("manage_standalone", array($incident, "write"));
		
		if ($check_standalone_acl === ENTERPRISE_NOT_HOOK || $check_standalone_acl) {
			$has_im  = true;
			$has_iw = true;
		}
	} else {
		$has_im  = give_acl ($config['id_user'], $id_grupo, "IM")
			|| $config['id_user'] == $incident['id_creator'];
		
		$has_iw = give_acl ($config['id_user'], $id_grupo, "IW")
			|| $config['id_user'] == $incident['id_usuario']
			|| $config['id_user'] == $incident['id_creator'];
	}
	
	if (!$pure) {
		if ($has_iw) {
			$incident_data = get_incident ($id);

			$resolution = $incident_data['resolution'];
			$priority = $incident_data['prioridad'];
			$owner = $incident_data['id_usuario'];
			$status = $incident['estado'];
			$id_incident_type = $incident['id_incident_type'];
			
			//add
			$id_group_type = safe_output(get_db_value("id_group", "tincident_type", "id", $id_incident_type));
			if($id_group_type != "" && $id_group_type != "0"){
				$groups_all = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
				$groups_selected = explode(', ', $id_group_type);
				$groups = array_intersect($groups_all, $groups_selected);
			} else {
				$groups = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false,  true));
			}

			//$ticket_editor .= "<table style='width: 100%;'>";
			$ticket_editor = "<tr>";
			$ticket_editor .= "<td>";
			$ticket_editor .= print_select (get_priorities (true), 'priority_editor', $priority, "", '','', true, false, false, __('Priority'), false, '');
			$ticket_editor .= "</td>";
			$ticket_editor .= "<td>";
				
			//If IW creator enabled flag is enabled, the user can change the creator
			$src_code = print_image('images/group.png', true, false, true);
		
			$params_assigned['input_id'] = 'text-owner_editor';
			$params_assigned['input_name'] = 'owner_editor';
			$params_assigned['input_value'] = $owner;
			$params_assigned['title'] = __('Owner');
			$params_assigned['help_message'] = __("User assigned here is user that will be responsible to manage tickets. If you are opening a ticket and want to be resolved by someone different than yourself, please assign to other user");
			$params_assigned['return'] = true;
			$params_assigned['return_help'] = true;
			
			if ($has_im || ($has_iw && $config['iw_creator_enabled'])){

				$ticket_editor .= user_print_autocomplete_input($params_assigned);
						
			} else {
				
				$params_assigned['disabled'] = true;
				$ticket_editor .= user_print_autocomplete_input($params_assigned);
			}
				
			$ticket_editor .= "</td>";
			$ticket_editor .= "</tr>";

			$ticket_editor .= "<tr>";
			$ticket_editor .= "<td>";

			if ($has_im)
				$ticket_editor .= combo_incident_resolution ($resolution, false, true, false, "");
			else {
				$ticket_editor .= print_label (__('Resolution'), '','',true, render_resolution($resolution));
			}
			$ticket_editor .= "</td>";
			$ticket_editor .= "<td>";
			$ticket_editor .= combo_incident_status ($status, false, 0, true, false, "");
			$ticket_editor .= "</td>";
			$ticket_editor .= "</tr>";
			
			$ticket_editor .= "<tr><td>";
			
			if(!isset($blocked_incident)){
				$blocked_incident = '';
			}
			
			$ticket_editor .= print_select ($groups, "grupo_form", $id_grupo, '', '', 0, true, false, false, __('Group'), $blocked_incident);
			$ticket_editor .= "</td>";
			
			$qa_profile = give_acl ($config['id_user'], $id_grupo, "QA");
		
			if ($qa_profile) {
				$ticket_editor .= "<td>";
				$qa_options = array('1'=>__('Add gold medal'),
									'2'=>__('Remove gold medal'),
									'3'=>__('Add black medal'),
									'4'=>__('Remove black medal'));
				if(!isset($medal_option)){
					$medal_option = '';
				}
				$ticket_editor .= print_select ($qa_options, "medal_option", $medal_option, '', 'Select', 0, true, false, false, __('Medals'), $blocked_incident);
				$ticket_editor .= "</td>";
			}
			
			$ticket_editor .= "</tr>";
			
			$ticket_editor .= "<tr><td>";
			$ticket_editor .= "";
			$ticket_editor .= "</td><td align=right>";
			$ticket_editor .= "<input type='button' name='update' value='".__("Update")."' class='sub upd' onClick='setParams($id);' />";
			$ticket_editor .= "</td>";
			$ticket_editor .= "</tr>";

			//$ticket_editor .= "</table>";

			$right_side .= print_container('ticket_editor', __('Quick edit'), $ticket_editor,'open', true, true,'','no_border',4, 'less_widht');
		}
	}
} //end quick editor

// Incident dates
if ($incident["cierre"] == "0000-00-00 00:00:00") {
	$close_text = __("Not yet");
} else {
	$close_text = $incident["cierre"];
}

//$incident_dates .= "<table width='97%' style='text-align: center;' id='incidents_dates_square'>";
$incident_dates = "<tr>";
$incident_dates .= "<td>".__("Created on").":</td>";
$incident_dates .= "<td>".__("Updated on").":</td>";
$incident_dates .= "<td>".__("Closed on").":</td>";
$incident_dates .= "</tr>";
$incident_dates .= "<tr>";
$incident_dates .= "<td id='created_on' class='mini_calendar'>";

$created_timestamp = strtotime($incident["inicio"]);
$created_on = "<table><tr><th>" . strtoupper(date('M\' y', $created_timestamp)) . "</th></tr>";
$created_on .= "<tr><td class='day'>" . date('d', $created_timestamp) . "</td></tr>";
$created_on .= "<tr><td class='time'>" . print_image('images/cal_clock_grey.png', true) . ' ' . date('H:i:s', $created_timestamp) . "</td></tr></table>";

$incident_dates .= $created_on . "</td>";
$incident_dates .= "<td id='updated_on' class='mini_calendar'>";

$updated_timestamp = strtotime($incident["actualizacion"]);
$updated_on = "<table class='no_border'><tr><th>" . strtoupper(date('M\' y', $updated_timestamp)) . "</th></tr>";
$updated_on .= "<tr><td class='day'>" . date('d', $updated_timestamp) . "</td></tr>";
$updated_on .= "<tr><td class='time'>" . print_image('images/cal_clock_orange.png', true) . ' ' . date('H:i:s', $updated_timestamp) . "</td></tr></table>";

$incident_dates .= $updated_on . "</td>";
$incident_dates .= "</td>";
$incident_dates .= "<td id='closed_on' class='mini_calendar'>";

if ($incident["estado"] == STATUS_CLOSED) {
	$closed_timestamp = strtotime($incident["cierre"]);
	$closed_on = "<table><tr><th>" . strtoupper(date('M\' y', $closed_timestamp)) . "</th></tr>";
	$closed_on .= "<tr><td class='day'>" . date('d', $closed_timestamp) . "</td></tr>";
	$closed_on .= "<tr><td class='time'>" . print_image('images/cal_clock_darkgrey.png', true) . ' ' . date('H:i:s', $closed_timestamp) . "</td></tr></table>";
}
else {
	$closed_on = "<table><tr><th>?</th></tr>";
	$closed_on .= "<tr><td class='day not_yet'>" . strtoupper(__('Not yet')) . "</td></tr>";
	$closed_on .= "</table>";
}

$incident_dates .= $closed_on . "</td>";
$incident_dates .= "</td>";
$incident_dates .= "</tr>";
//$incident_dates .= "</table>";

$right_side .= print_container('incident_dates', __('Dates'), $incident_dates, 'open',true,true,'','no_border_center',4, 'less_widht');

// Review Score
if  (($incident["id_creator"] == $config["id_user"]) AND ($incident["estado"] == 7) AND ($incident['score'] != 0)) {
	
	if (give_acl($config["id_user"], 0, "IM")){

		$num_stars = round(($incident['score']*5)/10);
		//$ticket_score = "<table style='width: 98%;'>";
		$ticket_score .= "<tr>";
		$ticket_score .= "<td>";
		$ticket_score .= '<div class="bubble">' . print_image('images/avatars/' . $avatar_creator . '.png', true, false) . '</div>';
		$ticket_score .= "</td>";
		$ticket_score .= "<td>";
		$ticket_score .= __("Scoring").": ". $incident["score"]. "/10";
		$ticket_score .= '<br>';
		
		
		for ($stars=0;$stars<$num_stars;$stars++) {
			$ticket_score .= print_image("images/star_naranja.png", true);
		}
		
		$empty_stars=5-$num_stars;
		for ($stars=0;$stars<$empty_stars;$stars++) {
			$ticket_score .= print_image("images/star_dark.png", true);
		}

		$ticket_score .= "</td>";

		$ticket_score .= "</tr>";
		//$ticket_score .= "</table>";
	}
	
	$right_side .= print_container('ticket_score', __('Review score'), $ticket_score,'opem', true, true, '', '', 5, 'less_widht');
}

// SLA information
if ($incident["sla_disabled"]) {
	//$incident_sla .= '<table width="97%">';
	$incident_sla = '<tr>';
	$incident_sla .= "<td style='text-align: center;'>";
	$incident_sla .= "<em>".__("SLA disabled")."</em>";
	$incident_sla .= "</td>";
	$incident_sla .= "</tr>";
	//$incident_sla .= "</table>";
} else {
	//$incident_sla .= '<table width="97%" style="border-spacing: 10px;">';
	$incident_sla = '<tr>';
	$incident_sla .= "<td>";
	$incident_sla .= __('SLA history compliance for: '); 
	$incident_sla .= "</td>";
	$incident_sla .= "<td style='vertical-align: bottom;'>";

	$a_day = 24*3600;

	$fields = array($a_day => "1 day",
					2*$a_day => "2 days",
					7*$a_day => "1 week",
					14*$a_day => "2 weeks",
					30*$a_day => "1 month");

	$period = get_parameter("period", $a_day);
	$ttl = 1;

	if ($clean_output) {
		$ttl = 2;
	}

	if ($clean_output) {
		$incident_sla .= "<strong>".$fields[$period]."</strong>";
	} else {
		$incident_sla .= print_select ($fields, "period", $period, 'reload_sla_slice_graph(\''.$id.'\');', '', '', true, 0, false, false, false, 'width: 75px');
	}

	$incident_sla .= "</td>";
	$incident_sla .= "<td colspan=2 style='text-align: center; width: 50%;'>";
	$incident_sla .= __('SLA total compliance (%)'). ': ';
	$incident_sla .= format_numeric (get_sla_compliance_single_id ($id));
	$incident_sla .= "</td>";
	$incident_sla .= "</tr>";
	$incident_sla .= "<tr>";
	$incident_sla .= "<td id=slaSlicebarField colspan=2 style='text-align: center; padding: 1px 2px 1px 5px;'>";
	$incident_sla .= graph_sla_slicebar ($id, $period, 155, 15, $ttl);
	$incident_sla .= "</td>";
	$incident_sla .= "<td colspan=2 style='text-align: center;' >";
	$incident_sla .= "<div class='pie_frame'>";
	$incident_sla .= graph_incident_sla_compliance ($id, 155, 80, $ttl);
	$incident_sla .= "</div>";	
	$incident_sla .= "</td>";
	$incident_sla .= "<tr>";
	//$incident_sla .= "</table>";
}

$right_side .= print_container('incident_sla', __('SLA information'), $incident_sla, 'open', true, true, '', 'no_border', 4, 'less_widht');

$table->data[0][0] = $left_side;
$table->data[0][1] = $right_side;

echo "<div id='indicent-details-view'>";

echo '<h2>'.__('Tickets').' #'.$incident["id_incidencia"].'</h2><h4>'. $incident['titulo'];


if (!$pure) {
	echo integria_help ("incident_dashboard_detail", true);
	echo "<div id='button-bar-title'>";
	echo "<ul>";

	//Only incident manager and user with IR flag which are owners and admin can edit incidents
	$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident, false, "IW"));

	if (get_standalone_user($config["id_user"])) {
		$check_standalone_acl = enterprise_hook("manage_standalone", array($incident, "write"));
		
		if ($check_standalone_acl === ENTERPRISE_NOT_HOOK || $check_standalone_acl) {
			echo "<li>";
			echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_detail&id='.$id.'">'.print_image("images/application_edit.png", true, array("title" => __("Edit"))).'</a>';
			echo "</li>";
		}
	} else {
		if ($check_acl === ENTERPRISE_NOT_HOOK || $check_acl) {
			echo "<li>";
			echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_detail&id='.$id.'">'.print_image("images/application_edit.png", true, array("title" => __("Edit"))).'</a>';
			echo "</li>";
		}
	}
	echo '<li>';
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'&tab=workunits#incident-operations">'.print_image("images/icono_comentarios.png", true, array("title" => __('Comments'))).'</a>';
	echo '</li>';
	echo '<li>';
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'&tab=files#incident-operations">'.print_image("images/disk.png", true, array("title" => __('Files'))).'</a>';
	echo '</li>';
	echo '<li>';
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'&pure=1&tab=tracking#incident-operations">'.print_image("images/chart_bar_dark.png", true, array("title" => __('Statistics'))).'</a>';
	echo '</li>';

	$tab_extensions = get_tab_extensions($sec2, "indicent-details-view");
	foreach ($tab_extensions as $tab_extension) {
		echo '<li>';
		echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.'&tab='.$tab_extension['tab']['id'].'">'.print_image($tab_extension['tab']['icon'], true, array("title" => __($tab_extension['tab']['name']))).'</a>';
		echo '</li>';
	}

	echo '<li class="ui-tabs">';
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&serialized_filter=1'>".print_image ("images/volver_listado.png", true, array("title" => __("Back to search")))."</a>";
	echo '</li>';
	echo "</ul>";
	echo "</div>";
	echo "</h4>";

	$tab = get_parameter("tab", "");
	foreach ($tab_extensions as $tab_extension) {
		if ($tab == $tab_extension['tab']['id']) {
			extensions_call_tab_function($tab, $sec2, "indicent-details-view");
			return;
		}
	}
}
else {
	
	echo "<div id='button-bar-title'>";
	echo "<ul>";
	echo '<li class="ui-tabs">';
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$id."'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back to ticket")))."</a>";
	echo '</li>';
	echo "</ul>";
	echo "</div>";
	echo "</h4>";
}

print_table($table);

//Display a minimal version for a report view
if ($clean_output) {

	include("incident_tracking.php");
	include("incident_files.php");
	include("incident_workunits.php");

}
else {

	echo "<a name='incident-operations'></a>";

	echo "<div id='tab' class='ui-tabs-panel' style='font-size:0.90em !important'>";
	$tab = get_parameter("tab", "workunits");
	switch ($tab) {
		case "workunits":
			echo "<h4>".__('Add comment');
			break;
		case "files":
			echo "<h4>".__('Add file');
			break;
		case "inventory":
			echo "<h4>".__('Inventory objects');
			break;
		case "contacts":
			echo "<h4>".__('Contacts');
			break;
		case "tracking":
			echo "<h4>".__('Tracking');
			break;
		case "tickets":
			echo "<h4>".__('Tickets');
			break;
		default:
			break;
	}
	//Print lower menu tab
	echo '<ul class="ui-tabs-nav">';

	if ($tab === "contacts") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}

	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=contacts#incident-operations"><img src="images/contacts.png" title="'.__('Contacts').'"/></a>';
	echo '</li>';

	if ($tab === "inventory") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}

	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=inventory#incident-operations"><img src="images/inventory_tab.png" title="'.__('Inventory').'"/></a>';
	echo '</li>';

	if ($tab === "tracking") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}

	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=tracking#incident-operations"><img src="images/details_tab.png" title="'.__('Tracking').'"/></a>';
	echo '</li>';

	if ($tab === "files") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=files#incident-operations"><img src="images/fichero_tab.png" title="'.__('Files').'"/></a>';
	echo '</li>';

	if ($tab === "workunits") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}
	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=workunits#incident-operations"><img src="images/icono_comentarios.png" title="'.__('Comments').'"/></a>';
	echo '</li>';
	
	if ($tab === "tickets") {
		echo '<li class="ui-tabs-selected">';
	} else {
		echo '<li class="ui-tabs">';
	}

	echo '<a href="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id.
			'&tab=tickets#incident-operations"><img src="images/tickets_tab.png" title="'.__('Associated tickets').'"/></a>';
	echo '</li>';

	//echo '<li class="ui-tabs-title">';
	
	//echo '</li>';

	echo '</ul>';
	echo "</h4>";
	switch ($tab) {
		case "workunits":
			include("incident_workunits.php");
			break;
		case "files":
			include("incident_files.php");
			break;
		case "inventory":
			include("incident_inventory_detail.php");
			break;
		case "contacts":
			include("incident_inventory_contacts.php");
			break;
		case "tracking":
			include("incident_tracking.php");
			break;
		case "tickets":
			include("incident_tickets.php");
			break;
		default:
			break;
	}
	
	
	echo "</div>";

	//echo "</div>";
}

//parameter to reload page
print_input_hidden ('base_url_homedir', base_url(), false);

//div to show user info
echo "<div class= 'dialog ui-dialog-content' title='".__("User info")."' id='user_info_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Warning")."' id='ticket_childs'></div>";
//id_incident hidden
echo '<div id="id_incident_hidden" style="display:none;">';
	print_input_text('id_incident_hidden', $id);
echo '</div>';
?>

<script type="text/javascript" src="include/js/integria_incident_search.js"></script>

<script type="text/javascript">
	
$(document).ready (function () {

	status = $('#incident_status').val();
		

	set_allowed_status();
	set_allowed_resolution();

	$("#incident_status").change(function () {
		var status = $(this).val();
		set_allowed_resolution();
	});

});

$('.incident_container h2').click(function() {
	var arrow = $('#' + $(this).attr('id') + ' img').attr('src');
	var arrow_class = $('#' + $(this).attr('id') + ' img').attr('class');
	var new_arrow = '';
	
	if (arrow_class == 'arrow_down') {
		new_arrow = arrow.replace(/_down/gi, "_right");
		$('#' + $(this).attr('id') + ' img').attr('class', 'arrow_right')
	}
	else {
		new_arrow = arrow.replace(/_right/gi, "_down");
		$('#' + $(this).attr('id') + ' img').attr('class', 'arrow_down')
	}
	
	$('#' + $(this).attr('id') + ' img').attr('src', new_arrow);
});

$("#submit-accion").click(function () {
	var id_ticket = "<?php echo $id ?>";
	var score = $("#score_ticket").val();
	setTicketScore(id_ticket, score);
});

var idUser = "<?php echo $config['id_user'] ?>";

bindAutocomplete("#text-owner_editor", idUser);

</script>
