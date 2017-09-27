<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once("include/functions_incidents.php");
require_once ("enterprise/include/functions_incidents.php");


$get_incidents_search = get_parameter('get_incidents_search', 0);
$get_incident_name = get_parameter('get_incident_name', 0);
$get_contact_search = get_parameter('get_contact_search',0);
$get_group_search = get_parameter('get_group_search',0);
$get_user_search = get_parameter('get_user_search',0);
$clickin = get_parameter('clickin', 2);
$id_ticket = get_parameter('id_ticket', 0);

$set_priority = get_parameter('set_priority', 0);
$set_resolution = get_parameter('set_resolution', 0);
$set_status = get_parameter('set_status', 0);
$set_owner = get_parameter('set_owner', 0);
$set_ticket_score = get_parameter('set_ticket_score', 0);
$get_user_info = get_parameter('get_user_info', 0);
$hours_to_dms = get_parameter('hours_to_dms', 0);
$check_incident_childs = get_parameter('check_incident_childs', 0);
$check_custom_search = get_parameter('check_custom_search', 0);
$set_params = get_parameter('set_params', 0);
$search_ajax = (bool)get_parameter('search_ajax', 0);

if ($get_user_search) {
	
	$filter = array ();
	$filter['offset'] = get_parameter ("offset", 0);
	$filter['search_text'] = get_parameter ("search_text", "");
	$filter['disabled_user'] = get_parameter ("disabled_user", -1);
	$filter['level'] = get_parameter ("level", -10);
	$filter['group'] = get_parameter ("group", 0);
	$id_group = get_parameter("group");	
	$ajax = get_parameter("ajax");
	
	$filter_form = false;
	
	echo "<div class='divform'>";
	form_search_users (false, $filter_form);
	echo "</div>";
	//~ user_search_result($filter, $ajax, $size_page=$config["block_size"], $offset=$filter['offset'], $clickin);
	//~ user_search_result($filter, $ajax, $size_page=$config["block_size"], $offset=$filter['offset'], $clickin, false, false, false, false, true);
	user_search_result($filter, $ajax, $size_page=$config["block_size"], $offset=$filter['offset'], $clickin, false, false, false, $id_group, true);
}

if ($get_incident_name) {
	$id = get_parameter("id");
	
	$name = get_db_value ("titulo", "tincidencia", "id_incidencia", $id);
	
	echo $name;
}

if ($get_group_search) {
	$filter = get_parameter("filter");
	//Get group if was not defined
	if($id_grupo==0) {
		$id_grupo_incident = get_db_value("id_grupo", "tusuario_perfil", "id_usuario", $config['id_user']);
		
		//If no group assigned use ALL by default
		if (!$id_grupo_incident) {
			$id_grupo_incident = 1;
		}
		
	} else {
		$id_grupo_incident = $id_grupo;
	}


	if($filter){
		$groups_selected_prepare = str_replace("    ", "&nbsp;&nbsp;&nbsp;&nbsp;", safe_output($filter));
		$groups_selected_prepare = explode(", ", $groups_selected_prepare);
		
		$groups_prepare = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false, true, null, 'nombre'));
		
		$groups = array_diff($groups_prepare, $groups_selected_prepare);
		$groups_selected = array_intersect($groups_prepare, $groups_selected_prepare);
		
	} else {
		$groups = safe_output(users_get_groups_for_select ($config['id_user'], "IW", false, true, null, 'nombre'));
	}

	echo '<div class="div_ui div_left_ui">';
		echo print_select ($groups, "origin", $id_grupo_incident, '', '', 0, true, true, false);
	echo '</div>';
	echo '<div class="div_middle_ui">';
		echo '<a class="pass left"><img src="images/flecha_dcha.png"/></a><br/>';
		echo '<a class="passall left"><img src="images/go_finish.png"/></a><br/>';
		echo '<a class="remove right"><img src="images/flecha_izqda.png"/></a><br/>';
		echo '<a class="removeall right"><img src="images/go_begin.png"/></a>';
	echo '</div>';
	echo '<div class="div_ui div_right_ui">';
		echo print_select ($groups_selected, "destiny", '', '', '', 0, true, true, false, false, false, '', true);
	echo '</div>';	
	echo '<p class="button_send_groups"><input type="button" value='.__('Submit').' onclick="loadgroups()" /></p>';
	echo '</form>';
}

if ($get_contact_search) {

	include_once("include/functions_crm.php");

	$read = enterprise_hook('crm_check_user_profile', array($config['id_user'], 'cr'));

	if ($read !== ENTERPRISE_NOT_HOOK) {
		$enterprise = true;
	}

	if (!$read) {
		include ("general/noaccess.php");
		exit;
	}
	
	$search_text = (string) get_parameter ('search_text');
	$id_company = (int) get_parameter ('id_company', 0);
	
	//$where_clause = "WHERE 1=1 AND id_company " .get_filter_by_company_accessibility($config["id_user"]);
	$where_clause = "WHERE 1=1";
	if ($search_text != "") {
		$where_clause .= " AND (fullname LIKE '%$search_text%' OR email LIKE '%$search_text%'
					OR phone LIKE '%$search_text%' OR mobile LIKE '%$search_text%') ";
	}

	if ($id_company) {

		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}
	$params = "&search_text=$search_text&id_company=$id_company";

	$table->width = '99%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = print_input_text ("search_text", $search_text, "", 15, 100, true, __('Search'));
	
	$companies = crm_get_companies_list("", false, "", true);

	$table->data[0][1] = print_select ($companies, 'id_company', $id_company, '', 'All', 0, true, false, false, __('Company'));
	$table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
	echo '<form id="contact_search_form" method="post">';
	print_table ($table);
	echo '</form>';

	$contacts = crm_get_all_contacts ($where_clause);

	if ($read && $enterprise) {
		$contacts = crm_get_user_contacts($config['id_user'], $contacts);
	}

	$contacts = print_array_pagination ($contacts, "index.php?sec=customers&sec2=operation/contacts/contact_detail&params=$params", $offset);

	if ($contacts !== false) {
		unset ($table);
		$table->width = "99%";
		$table->class = "listing";
		$table->data = array ();
		$table->size = array ();
		$table->size[3] = '40px';
		$table->style = array ();
		// $table->style[] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = __('Full name');
		$table->head[1] = __('Company');
		$table->head[2] = __('Email');
		if($manage_permission) {
			$table->head[3] = __('Delete');
		}
		
		foreach ($contacts as $contact) {
			$data = array ();
			// Nameif (defined ('AJAX')) {
			$url = "javascript:loadContactEmail(\"".$contact['email']."\");";
			$data[0] = "<a href='".$url."'>".$contact['fullname']."</a>";
			$data[1] = "<a href='".$url."'>".get_db_value ('name', 'tcompany', 'id', $contact['id_company'])."</a>";
			$data[2] = $contact['email'];
			if($manage_permission) {
				$data[3] = '<a href="index.php?sec=customers&
							sec2=operation/contacts/contact_detail&
							delete_contact=1&id='.$contact['id'].'&offset='.$offset.'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png"></a>';
			}	
			array_push ($table->data, $data);
		}
		print_table ($table);
	}		
}

if ($set_ticket_score) {
	$id_ticket = get_parameter ('id_ticket');
	$score = get_parameter ('score');
	
	$sql = "UPDATE tincidencia SET score = $score WHERE id_incidencia = $id_ticket";
	process_sql ($sql);
}

if ($get_user_info) {

	$id_user = get_parameter('id_user');
	
	$info_user = get_db_row('tusuario', 'id_usuario', $id_user);
	
	$total_tickets_opened = get_db_value_sql("SELECT count(id_incidencia) 
									FROM tincidencia 
									WHERE estado<>7
									AND id_creator='$id_user'");

	echo "<table>";
	echo "<tr>";
	echo "<td>";
		if ($info_user['avatar'])
			print_image('images/avatars/' . $info_user['avatar'] . '.png', false, false);
		else
			print_image('images/avatars/avatar_notyet.png', false, false);
	echo "</td>";
	echo "<td vertical-align='middle'>";
			echo $info_user['nombre_real'];
			echo '<br>'.'('.$id_user.')';
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td align='left'>";
		echo '<b>'.__('Telephone: ').'</b>'.$info_user['telefono'];
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Email: ').'</b>'.$info_user['direccion'];
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Company: ').'</b>'.get_db_value('name', 'tcompany', 'id', $info_user['id_company']);
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Total tickets opened: ').'</b>'.$total_tickets_opened;
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td>";
		echo '<b>'.__('Comments ').'</b>';
	echo "</td>";
	echo "</tr>";
	
	echo "<tr align='left'>";
	echo "<td colspan=2>";
			echo $info_user['comentarios'];
	echo "</td>";
	echo "</tr>";

$user_fields = get_db_all_rows_sql ("SELECT t.label, t2.data 
							FROM tuser_field t, tuser_field_data t2
							WHERE t2.id_user='$id_user'
							AND t.id=t2.id_user_field");
if ($user_fields) {
	foreach ($user_fields as $field) {
		echo "<tr align='left'>";
		echo "<td>";
			echo '<b>'.$field["label"].': '.'</b>'.$field['data'];
		echo "</td>";
	}
}

	echo "</table>";
	return;
}

if ($hours_to_dms) {
	
	$hours = get_parameter('hours');

	$result = incidents_hours_to_dayminseg ($hours);	
	
	echo json_encode($result);
	return;
}

if ($check_incident_childs) {
	
	$id_incident = get_parameter('id_incident');
	
	if ($id_incident == 0) {
		echo false;
		return;
	}
	
	$sql = "SELECT id_incidencia, titulo FROM tincidencia WHERE id_parent=".$id_incident. " AND estado<>7";
	$incident_childs = get_db_all_rows_sql($sql);
	
	if ($incident_childs == false) {
		$incident_childs = array();
	}
	
	if (!empty($incident_childs)) {
		echo "<table>";
		echo "<tr>";
		echo "<td align='left' colspan=2>";
		echo '<b>'. __("Following tickets will be closed: ").'</b>';
		echo "</td>";
		echo "</tr>";

		foreach ($incident_childs as $child) {
			echo "<tr align='left'>";
			echo "<td>";
				echo $child['id_incidencia'].' - '.$child['titulo'];
			echo "</td>";
		}
		echo "</table>";
	} else {
		return false;
	}
	
	return;
}

if ($check_custom_search) {
	$sql = sprintf ('SELECT COUNT(id) FROM tcustom_search
		WHERE id_user = "%s" AND section = "incidents"
		ORDER BY name', $config['id_user']);
	$count_search = get_db_value_sql($sql);
	
	if (!$count_search) {
		$result = __("Ticket reports are based on custom searches. In order to elaborate a report, first you will need to define a custom search on which the report will be based.");
		$result .= integria_help ("custom_search", true);
		$result .= '<br><br><a href="index.php?sec=incidents&sec2=operation/incidents/incident_search">'.__('Go to Custom search').'</a>';

	} else {
		$result = false;
	}
	
	echo json_encode($result);
	return;
}

if ($set_params) {

	$id_ticket = get_parameter('id_ticket');
	$values = array();
	$values['prioridad'] = get_parameter ('id_priority');
	$values['resolution'] = get_parameter ('id_resolution');
	$values['estado'] = get_parameter ('id_status');
	$values['id_usuario'] = get_parameter ('id_user');
	$values['actualizacion'] = date('Y:m:d H:i:s');
	
	$id_group = get_parameter ('id_groups');
	$medal_option = get_parameter('medal_option', 0);
	
	if ($id_group) {
		$values['id_grupo'] = $id_group;
	}
	
	if ($medal_option) {
		switch ($medal_option) {
			case 1: //Add gold medal
				$num_gold = get_db_value('gold_medals', 'tincidencia', 'id_incidencia', $id_ticket);
				$values['gold_medals'] = $num_gold + 1;
			break;
			case 2: //Remove gold medal
				$num_gold = get_db_value('gold_medals', 'tincidencia', 'id_incidencia', $id_ticket);
				$values['gold_medals'] = $num_gold - 1;
			break;
			case 3: //Add black medal
				$num_black = get_db_value('black_medals', 'tincidencia', 'id_incidencia', $id_ticket);
				$values['black_medals'] = $num_black + 1;
			break;
			case 4: //Remove black medal
				$num_black = get_db_value('black_medals', 'tincidencia', 'id_incidencia', $id_ticket);
				$values['black_medals'] = $num_black - 1;
			break;
		}
	}

	$old_incident = get_incident ($id_ticket);
	
	if (!$old_incident['old_status2']) {
		$values['old_status'] = $old_incident["old_status"];
		$values['old_resolution'] = $old_incident["old_resolution"];
		$values['old_status2'] = $values['estado'];
		$values['old_resolution2'] = $values['resolution'];
	} else {
		if (($old_incident['old_status2'] == $values['estado']) && ($old_incident['old_resolution2'] == $values['resolution'])) {
			$values['old_status'] = $old_incident["old_status"];
			$values['old_resolution'] = $old_incident["old_resolution"];
			$values['old_status2'] = $old_incident["old_status2"];
			$values['old_resolution2'] = $old_incident["old_resolution2"];
		} else {
			$values['old_status'] = $old_incident["old_status2"];
			$values['old_resolution'] = $old_incident["old_resolution2"];
			$values['old_status2'] = $values['estado'];
			$values['old_resolution2'] = $values['resolution'];
		}
	}

	$result = db_process_sql_update('tincidencia', $values, array('id_incidencia'=>$id_ticket));
		
	if ($result) {
		
		$owner = get_db_value('id_usuario', 'tincidencia', 'id_incidencia', $id_ticket);
		
		// Email notify to all people involved in this incident
		// Email in list email-copy
		$email_copy_sql = 'select email_copy from tincidencia where id_incidencia ='.$id_ticket.';';
		$email_copy = get_db_sql($email_copy_sql);
		if ($email_copy != "") { 
			if($values['estado'] == 7){
				mail_incident ($id_ticket, $owner, "", 0, 5, 7);
			} else {
				mail_incident ($id_ticket, $owner, "", 0, 0, 7);
			}
		}
		if (($config["email_on_incident_update"] != 3) && ($config["email_on_incident_update"] != 4) && ($values['estado'] == 7)) { //add emails only closed
			mail_incident ($id_ticket, $owner, "", 0, 5);
		} else if ($config["email_on_incident_update"] == 0){ //add emails updates
			mail_incident ($id_ticket, $owner, "", 0, 0);
		}
		
		if ($old_incident['prioridad'] != $values['prioridad']) {
			incident_tracking ($id_ticket, INCIDENT_PRIORITY_CHANGED, $values['prioridad']);
		}
		if ($old_incident['resolution'] != $values['resolution']) {
			incident_tracking ($id_ticket, INCIDENT_RESOLUTION_CHANGED, $values['resolution']);
		}
		if ($old_incident['estado'] != $values['estado']) {
			if ($values['estado'] == 7) {
				$values_close['closed_by'] = $config['id_user'];
				$values_close['cierre'] = date('Y:m:d H:i:s');
				db_process_sql_update('tincidencia', $values_close, array('id_incidencia'=>$id_ticket));
			}
			incident_tracking ($id_ticket, INCIDENT_STATUS_CHANGED, $values['estado']);
		}
		if ($old_incident['id_usuario'] != $values['id_usuario']) {
			incident_tracking ($id_ticket, INCIDENT_USER_CHANGED, $values['id_usuario']);
		}
		audit_db ($old_incident['id_usuario'], $config["REMOTE_ADDR"], "Ticket updated", "User ".$config['id_user']." ticket updated #".$id_ticket);
		
		if ($medal_option) {
			switch ($medal_option) {
				case 1: //Add gold medal
					incident_tracking ($id_ticket, INCIDENT_GOLD_MEDAL_ADDED, $values['id_usuario']);
					audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Gold medal added", "Gold medal added by user ".$config['id_user']." to the ticket #".$id_ticket);
				break;
				case 2: //Remove gold medal
					incident_tracking ($id_ticket, INCIDENT_GOLD_MEDAL_REMOVED, $values['id_usuario']);
					audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Gold medal removed", "Gold medal removed by user ".$config['id_user']." to the ticket #".$id_ticket);
				break;
				case 3: //Add black medal
					incident_tracking ($id_ticket, INCIDENT_BLACK_MEDAL_ADDED, $values['id_usuario']);
					audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Black medal added", "Black medal added by user ".$config['id_user']." to the ticket #".$id_ticket);
				break;
				case 4: //Remove black medal
					incident_tracking ($id_ticket, INCIDENT_BLACK_MEDAL_REMOVED, $values['id_usuario']);
					audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Black medal removed", "Black medal removed by user ".$config['id_user']." to the ticket #".$id_ticket);
				break;
			}
		}
		
		enterprise_hook("incidents_run_realtime_workflow_rules", array($id_ticket));
	}
}

if ($search_ajax){
	
	$filter = array ();
	$filter['inverse_filter']     = (bool) get_parameter ('search_inverse_filter');
	$filter['string']             = (string) get_parameter ('search_string');
	$filter['status']             = (int) get_parameter ('search_status', -10);
	$filter['priority']           = (int) get_parameter ('search_priority', -1);
	$filter['id_group']           = (int) get_parameter ('search_id_group', 1);
	$filter['id_company']         = (int) get_parameter ('search_id_company');
	$filter['id_inventory']       = (int) get_parameter ('search_id_inventory');
	$filter['id_incident_type']   = (int) get_parameter ('search_id_incident_type');
	$filter['id_user']            = (string) get_parameter ('search_id_user', '');
	$filter['id_user_or_creator'] = (string) get_parameter ('id_user_or_creator');
	
	$filter['first_date']          = (string) get_parameter ('search_first_date');
	$filter['last_date']          = (string) get_parameter ('search_last_date');	
	$filter['id_creator']         = (string) get_parameter ('search_id_creator');
	$filter['editor']             = (string) get_parameter ('search_editor');
	$filter['closed_by']          = (string) get_parameter ('closed_by');
	$filter['resolution']         = (int) get_parameter ('search_resolution', -1);
	$filter["offset"]             = (int) get_parameter ('offset');
	$filter['group_by_project']   = (bool) get_parameter('search_group_by_project');
	$filter['sla_state']          = (string) get_parameter ('search_sla_state');
	
	$filter['id_task']            = (int) get_parameter ('search_id_task');	
	$filter['left_sla']           = (int) get_parameter ('search_left_sla');
	$filter['right_sla']          = (int) get_parameter ('search_right_left');
	$filter['show_hierarchy']     = (bool) get_parameter('search_show_hierarchy');
	
	$filter['parent_name']        = get_parameter('parent_name');
	$filter['serial_number']      = (string) get_parameter ('search_serial_number');
	$filter['search_from_date']   = (int) get_parameter("search_from_date");
	//$filter['id_product']       = (int) get_parameter ('search_id_product');
	$filter['medals']             = (int) get_parameter('search_medals');

	//custom fields
	$type_fields = incidents_get_type_fields ($filter['id_incident_type']);
	foreach ($type_fields as $key => $type_field) {
		$filter['type_field_'.$type_field['id']] = (string) get_parameter ('search_type_field_'.$type_field['id']);
	}

	//Store serialize filter
	serialize_in_temp($filter, $config["id_user"]);


	$ajax = get_parameter("ajax", "");

	if($ajax){
		$filter_form = false;
		echo "<div style='float:right; width:99%'>";
			form_search_incident (false, $filter_form, $ajax);
		echo "</div>";
	}
	
	incidents_search_result($filter, $ajax, false, true);
	//incidents_search_result($filter, $ajax, false, false, $no_parents, false, false, false, $id_ticket);
	return;
}

?>
