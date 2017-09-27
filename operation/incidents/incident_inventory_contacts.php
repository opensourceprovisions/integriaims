<?php
// Integria 2.0 - http://integria.sourceforge.net
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

if (check_login () != 0) {
	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access","Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_inventories.php');
require_once ('include/functions_incidents.php');

$id_incident = $id;

$inventories = get_inventories_in_incident ($id_incident, false);

$table = new StdClass();
$table->class = 'listing';
$table->width = '100%';
$table->head = array ();
$table->head[0] = __('Relationship');
$table->head[1] = __('Company');
$table->head[2] = __('Contact');
$table->head[3] = __('Details');
$table->head[4] = __('Edit');
$table->size = array ();
$table->size[3] = '40px';
$table->size[4] = '40px';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->data = array ();

$table_data = array();

//Add key incident users (owner, editor, closed by, creator)
$inc_info = get_incidents("id_incidencia = ". $id_incident);

$inc_info = $inc_info[0];

$key_users = array("owner" => array("user" => $inc_info["id_usuario"], "role" => __("Owner")),
                "creator" => array("user" => $inc_info["id_creator"], "role" => __("Creator")),
                "editor" => array("user" => $inc_info["editor"], "role" => __("Editor")),
                "closed" => array ("user" => $inc_info["closed_by"], "role" => __("Closed by")));

$key_users_info = array();

//Get user infor and get user role
foreach ($key_users as $ku) {
	$role_desc = ""; //Role description within the incident

	if ($ku["user"]) {
		if (isset($key_users_info[$ku["user"]])) {
				$key_users_info[$ku["user"]] .= ", ".$ku["role"];
		} else {
				$key_users_info[$ku["user"]] = $ku["role"];
		}
	}
}

//Get all users with a workunit in the incident

$sql = sprintf("SELECT W.id_user FROM tworkunit W, tworkunit_incident WI  
                WHERE W.id = WI.id_workunit AND WI.id_incident = %d", $id_incident);

$wu_users = process_sql($sql);

foreach ($wu_users as $wu) {
	if (!isset($key_users_info[$wu["id_user"]])) {
		$key_users_info[$wu["id_user"]] = __("Participant");
	}
}

$incident_contacts = array();	

foreach ($inventories as $inventory) {
	$contacts = get_inventory_contacts ($inventory['id'], false);
	
	foreach ($contacts as $contact) {
		$data = array ();

		$contact["inventory"] = $inventory["name"];
		$contact["company"] = get_db_value("name", "tcompany", "id", $contact["id_company"]);
		if ($contact["type"] == "user" && isset($key_users_info[$contact["id"]])) {
			$contact["type"] = "user";
			$contact["relationship"] = "key_user";
			$contact["role"] = $key_users_info[$contact["id"]];
			unset($key_users_info[$contact["id"]]);
		} else {
			$contact["relationship"] = "object";
			$contact["type"] = "contact";
		}
		$incident_contacts[$contact["id"]] = $contact;	
	}
}

//Get emails from notify by email incident's field
$emails = array();

if ($inc_info["email_copy"]) {
	$emails = explode(',', $inc_info["email_copy"]);
}

foreach ($emails as $email) {
	//Search for users
	$email_info = get_db_row ("tusuario", "direccion", $email);

	if ($email_info) {
		$contact_info = array("id" => $email_info["id_usuario"],
							"type" => "user",
							"id_company" => $email_info["id_company"],
							"company" => get_db_value("name", "tcompany", "id", $email_info["id_company"]),
							"inventory" => __("N/A"),
							"fullname" => $email_info["nombre_real"],
							"email" => $email_info["direccion"],
							"phone" => $email_info["telefono"],
							"mobile" => __("N/A"),
							"position" => __("N/A"));
	} else {
		//Search for contact
		$email_aux = safe_output($email);
		$email_aux = trim($email_aux);
		
		$email_info = get_db_row("tcompany_contact", "email", $email_aux);
		
		if ($email_info) {     
			$contact_info= $email_info;
			$contact_info["type"] = "contact";
			$contact_info["inventory"] = __("N/A");
			$contact_info["company"] = get_db_value("name", "tcompany", "id", $contact_info["id_company"]);
		} else {
			//We only have email address
			$contact_info = array("id" => $email,
							"fullname" => $email,
							"type" => "email",
							"company" => __("N/A"),
							"email" => $email,
							"phone" => __("N/A"),
							"inventory" => __("N/A"),
							"mobile" => __("N/A"),
							"position" => __("N/A"));
		}
	}

	$contact_info["relationship"] = "email";

	//If was set don't insert in array
	if(isset($incident_contacts[$email_info["id"]])) {
		continue;
	}
	
	$incident_contacts[$contact_info["id"]] = $contact_info;
}      

//Add key incident users (owner, editor, closed by, creator) also participant (has workunits) and notify by email addresses

foreach (array_keys($key_users_info) as $key) {

	$fullname = get_db_value  ('nombre_real', 'tusuario', 'id_usuario', $key);

	$co = get_db_value("id_company", "tusuario", "id_usuario", $key);

	if ($co) {
		$co = get_db_value("name", "tcompany", "id", $co);
	} else {
		$co = __("N/A");
	}

	$contact_data = array("id" => $key,
						"fullname" =>  $fullname,
						"type" => "user",
						"company" => $co,
						"email" => get_db_value("direccion", "tusuario", "id_usuario", $key),
						"phone" => get_db_value('telefono', 'tusuario', 'id_usuario', $key),
						"inventory" => __("N/A"),
						"mobile" => __("N/A"),
						"position" => __("N/A"),
						"relationship" => "key_user", 
						"role" => $key_users_info[$key]);
	
	$incident_contacts[$key] = $contact_data;
}

//Add info to data table
foreach ($incident_contacts as $ic) {
	$data = array();

	switch($ic["relationship"]) {
		case "key_user":
			$data[0] = $ic["role"];
			break;
		case "email":
			$data[0] = __("Notified by email");
			break;
		case "object":
			$data[0] = __("Object")." (".$ic["inventory"].")";
			break;
	}
	
	$data[1] = $ic["company"];

	$data[2] = $ic["fullname"];	

	$details = '';
	if ($ic['phone'] == '') {
		$ic["phone"] = __("N/A");
	}
    $details .= '<strong>'.__('Phone number').'</strong>: '.$ic['phone'].'<br />';
        
	if ($ic['mobile'] == '') {
		$ic["mobile"] = __("N/A");
	}
    $details .= '<strong>'.__('Mobile phone').'</strong>: '.$ic['mobile'].'<br />';
	
	if ($ic['email'] == '') {
		$ic["email"] = __("N/A");		
	}
    $details .= '<strong>'.__('Email').'</strong>: '.$ic['email'].'<br />';
        
	//$data[3] = print_help_tip ($details, true, 'tip_view');
	$data[3] = '<a href="#incident-operations" onClick="inventory_contact_details(\''.$ic["phone"].'\', \''.$ic["mobile"].'\', \''.$ic["email"].'\')">';
	$data[3] .= "<img src=images/zoom.png>";	
	$data[3] .= '</a>';
	if ($ic["type"] == "user") {
		$data[4] = '<a href="index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&update_user='.$ic['id'].'">'.
						'<img src="images/wrench.png" /></a>';
	} else if($ic["type"] == "contact") {
		$data[4] = '<a href="index.php?sec=inventory&sec2=operation/contacts/contact_detail&id='.$ic['id'].'">'.
							'<img src="images/wrench.png" /></a>';
	} else {
		$data[4] = "";
	}

	array_push($table->data, $data);
}


print_table ($table);

echo '<div id="detail_info" title="'.__("Contact details").'"></div>';

$table = new StdClass();
$table->width = "100%";
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Company');
$table->head[1] = __('Contact');
$table->head[2] = __('Details');
$table->head[3] = __('Edit');

if ($config['incident_reporter'] == 1){
	$contacts = get_incident_contact_reporters ($id_incident); 

	foreach ($contacts as $contact) {
		$data = array ();
		
		$data[0] = get_db_value  ('name', 'tcompany', 'id', $contact['id_company']);
		$data[1] = $contact['fullname'];
		$details = '';
		if ($contact['phone'] != '')
			$details .= '<strong>'.__('Phone number').'</strong>: '.$contact['phone'].'<br />';
		if ($contact['mobile'] != '')
			$details .= '<strong>'.__('Mobile phone').'</strong>: '.$contact['mobile'].'<br />';
		if ($contact['position'] != '')
			$details .= '<strong>'.__('Position').'</strong>: '.$contact['position'].'<br />';
		$data[2] = print_help_tip ($details, true, 'tip_view');
		$data[3] = '<a href="index.php?sec=inventory&sec2=operation/contacts/contact_detail&id='.$contact['id'].'">'.
				'<img src="images/setup.gif" /></a>';
		array_push ($table->data, $data);
	}

	echo '<h4>'.__('Contacts who reported this ticket').'</h4>';
	print_table ($table);
}

?>
