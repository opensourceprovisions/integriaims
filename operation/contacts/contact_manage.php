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

check_login ();

include_once('include/functions_crm.php');

$id = (int) get_parameter ('id');
$id_company = (int) get_parameter ('id_company');

if (!isset($section_read_permission)) {
	$section_read_permission = check_crm_acl ('company', 'cr');
}
if (!isset($section_write_permission)) {
	$section_write_permission = check_crm_acl ('company', 'cw');
}
if (!isset($section_manage_permission)) {
	$section_manage_permission = check_crm_acl ('company', 'cm');
}

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to contacts without permission");
	include ("general/noaccess.php");
	exit;
}

if($id || $id_company) {
	if ($id) {
		$id_company = get_db_value ('id_company', 'tcompany_contact', 'id', $id);
	}
	
	if (!isset($read_permission)) {
		$read_permission = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
	}
	if (!isset($write_permission)) {
		$write_permission = check_crm_acl ('other', 'cw', $config['id_user'], $id_company);
	}
	if (!isset($manage_permission)) {
		$manage_permission = check_crm_acl ('other', 'cm', $config['id_user'], $id_company);
	}

	if ((!$read_permission && !$write_permission && !$manage_permission) || $id_company === false) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access a contact without permission");
		include ("general/noaccess.php");
		exit;
	}
}

$new_contact = (bool) get_parameter ('new_contact');
$create_contact = (bool) get_parameter ('create_contact');
$update_contact = (bool) get_parameter ('update_contact');
$delete_contact = (bool) get_parameter ('delete_contact');
$get_contacts = (bool) get_parameter ('get_contacts');
$offset = get_parameter ('offset', 0);

if ($get_contacts && $id) {
	$contract = get_contract ($id);
	$company = get_company ($contract['id']);
	$contacts = get_company_contacts ($company['id'], false);
	
	echo json_encode ($contacts);
	if (defined ('AJAX'))
		return;
}

// Create
if ($create_contact) {

	if (!$id_company) {
		echo ui_print_error_message (__('Error creating contact. Company is empty'), '', true, 'h3', true);
	} else {
		if (!$write_permission && !$manage_permission) {
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to create a new contact in a group without access");
			require ("general/noaccess.php");
			exit;
		}

		$fullname = (string) get_parameter ('fullname');
		$phone = (string) get_parameter ('phone');
		$mobile = (string) get_parameter ('mobile');
		$email = (string) get_parameter ('email');
		$position = (string) get_parameter ('position');
		
		$disabled = (int) get_parameter ('disabled');
		$description = (string) get_parameter ('description');

		$sql = sprintf ('INSERT INTO tcompany_contact (fullname, phone, mobile,
			email, position, id_company, disabled, description)
			VALUE ("%s", "%s", "%s", "%s", "%s", %d, %d, "%s")',
			$fullname, $phone, $mobile, $email, $position,
			$id_company, $disabled, $description);

		$id = process_sql ($sql, 'insert_id');
		
		if (defined ('AJAX')) {
			echo json_encode ($id);
			return;
		}
		
		if ($id === false) {
			echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			if(!isset($REMOTE_ADDR)){
				$REMOTE_ADDR = '';	
			}
			audit_db ($config['id_user'], $REMOTE_ADDR, "Contact created", "Contact named '$fullname' has been added");
		}
	}
}

// Update
if ($update_contact && $id) { // if modified any parameter
	
	$fullname = (string) get_parameter ('fullname');
	$phone = (string) get_parameter ('phone');
	$mobile = (string) get_parameter ('mobile');
	$email = (string) get_parameter ('email');
	$position = (string) get_parameter ('position');
	$disabled = (int) get_parameter ('disabled');
	$description = (string) get_parameter ('description');
	$id_company = (int) get_parameter ('id_company');
	
	if (!$id_company) {
		echo ui_print_error_message (__('Error updating contact. Company is empty'), '', true, 'h3', true);
	} else {
		if (!$write_permission && !$manage_permission) {
		   audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to update a contact in a group without access");
		   require ("general/noaccess.php");
		   exit;
		}

		$sql = sprintf ('UPDATE tcompany_contact
			SET description = "%s", fullname = "%s", phone = "%s",
			mobile = "%s", email = "%s", position = "%s",
			id_company = %d, disabled = %d WHERE id = %d',
			$description, $fullname, $phone, $mobile, $email, $position,
			$id_company, $disabled, $id);

		$result = process_sql ($sql);
		if ($result === false) {
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		} else {
			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			audit_db ($config['id_user'], '', "Contact updated", "Contact named '$fullname' has been updated");
		}
	}
}

// Delete
if ($delete_contact && $id) {
	if (!$manage_permission) {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to delete a contact in a group without access");
		require ("general/noaccess.php");
		exit;
	}

	$fullname = get_db_value  ('fullname', 'tcompany_contact', 'id', $id);
	$sql = sprintf ('DELETE FROM tcompany_contact WHERE id = %d', $id);
	process_sql ($sql);
	if(!isset($REMOTE_ADDR)){
		$REMOTE_ADDR = '';
	}
	audit_db ($config['id_user'], $REMOTE_ADDR, "Contact deleted", "Contact named '$fullname' has been deleted");
	echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	$id = 0;
}

// FORM (Update / Create)
if ($id || $new_contact) {
	if ($new_contact) {
		echo "<h4>".__('New Contact');
		echo integria_help ("contact_detail", true);
		echo "<div id='button-bar-title'>";
			echo "<ul>";
				echo "<li><a href='index.php?sec=customers&sec2=operation/contacts/contact_detail'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
			echo "</ul>";
		echo "</div>";
		echo "</h4>";
		if (!$section_write_permission && !$section_manage_permission) {
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to create a contact in a group without access");
			require ("general/noaccess.php");
			exit;
		}
		$id = 0;
		$fullname = (string) get_parameter ('fullname');
		$phone = (string) get_parameter ('phone');
		$mobile = (string) get_parameter ('mobile');
		$email = (string) get_parameter ('email');
		$position = (string) get_parameter ('position');
		$id_company = (int) get_parameter ('id_company');
		$disabled = (int) get_parameter ('disabled');
		$description = (string) get_parameter ('description');
		$id_contract = (int) get_parameter ('id_contract');
		if ($id_contract) {
			$id_company = (int) get_db_value ('id_company', 'tcontract', 'id', $id_contract);
		}
	} else {
		if (!$read_permission) {
			audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access a contact in a group without access");
			require ("general/noaccess.php");
			exit;
		}
		$contact = get_db_row ("tcompany_contact", "id", $id);
		$fullname = $contact['fullname'];
		$phone = $contact['phone'];
		$mobile = $contact['mobile'];
		$email = $contact['email'];
		$position = $contact['position'];
		$id_company = $contact['id_company'];
		$disabled = $contact['disabled'];
		$description = $contact['description'];
	}
	
	$table = new stdClass();
	$table->width = "100%";
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan[0][0] = 4;
	$table->colspan[1][0] = 4;
	$table->colspan[4][0] = 4;
	
	if ($new_contact || ($id && ($write_permission || $manage_permission)) ) {
		
		$table->class = "search-table-button";
		
		$table->data[0][0] = print_input_text ("fullname", $fullname, "", 60, 100, true, __('Full name'));
		
		$table->data[1][0] = print_input_text ("email", $email, "", 35, 100, true, __('Email'));
		$table->data[2][0] = print_input_text ("phone", $phone, "", 15, 60, true, __('Phone number'));
		$table->data[2][1] = print_input_text ("mobile", $mobile, "", 15, 60, true, __('Mobile number'));
		$table->data[3][0] = print_input_text ('position', $position, '', 25, 50, true, __('Position'));
		
		$params = array();
		$params['input_id'] = 'id_company';
		$params['input_name'] = 'id_company';
		$params['input_value'] = $id_company;
		$params['title'] = __('Company');
		$params['return'] = true;
		$table->data[3][1] = print_company_autocomplete_input($params);
		
		if ($id) {
			$table->data[3][1] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
			$table->data[3][1] .= "<img src='images/company.png'></a>";
		}
		
		$table->data[4][0] = print_textarea ("description", 10, 1, $description, '', true, __('Description'));
		
		
	} else {
		
		$table->class = "search-table";
		
		if($fullname == '') {
			$fullname = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[0][0] = "<b>".__('Full name')."</b><br>$fullname<br>";
		if($email == '') {
			$email = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[1][0] = "<b>".__('Email')."</b><br>$email<br>";
		if($phone == '') {
			$phone = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[2][0] = "<b>".__('Phone number')."</b><br>$phone<br>";
		if($mobile == '') {
			$mobile = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[2][1] = "<b>".__('Mobile number')."</b><br>$mobile<br>";
		if($position == '') {
			$position = '<i>-'.__('Empty').'-</i>';
		}		
		$table->data[3][0] = "<b>".__('Position')."</b><br>$position<br>";
		
		$company_name = get_db_value('name','tcompany','id',$id_company);

		$table->data[3][1] = "<b>".__('Company')."</b><br>$company_name";
			
		$table->data[3][1] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
		$table->data[3][1] .= "<img src='images/company.png'></a>";
		
		if($description == '') {
			$description = '<i>-'.__('Empty').'-</i>';
		}
		$table->data[4][0] = "<b>".__('Description')."</b><br>$description<br>";
	}
	
	echo '<form method="post" id="contact_form">';
	print_table ($table);
	if ($new_contact || ($id && ($write_permission || $manage_permission)) ) {
		echo "<div class='no' style='width:100%; text-align:right;'>";
			unset($table->data);
			$table->class = "button-form";
			if ($id) {
				$button = print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
				$button .= print_input_hidden ('update_contact', 1, true);
				$button .= print_input_hidden ('id', $id, true);
			} else {
				$button = print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
				$button .= print_input_hidden ('create_contact', 1, true);
			}
			$table->data['button'][0] = $button;
			$table->colspan['button'][0] = 2;
			print_table ($table);
		echo "</div>";
	}
	echo "</form>";
} 

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

$(document).ready (function () {

	var idUser = "<?php echo $config['id_user'] ?>";
	bindCompanyAutocomplete ('id_company', idUser);

});

// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-fullname');
trim_element_on_submit('#text-email');

if (<?php echo $id ?> > 0 || <?php echo json_encode($new_contact) ?> == true) {
	validate_form("#contact_form");
	var rules, messages;
	// Rules: #text-fullname
	//~ rules = {
		//~ required: true,
		//~ remote: {
			//~ url: "ajax.php",
			//~ type: "POST",
			//~ data: {
				//~ page: "include/ajax/remote_validations",
				//~ search_existing_contact: 1,
				//~ contact_name: function() { return $('#text-fullname').val() },
				//~ contact_id: "<?php echo $id?>"
			//~ }
		//~ }
	//~ };
	//~ messages = {
		//~ required: "<?php echo __('Name required')?>",
		//~ remote: "<?php echo __('This contact already exists')?>"
	//~ };
	//~ add_validate_form_element_rules('#text-fullname', rules, messages);
	// Rules: #text-email
	rules = {
		required: true,
		email: true,
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_existing_contact_email: 1,
				contact_email: function() { return $('#text-email').val() },
				contact_id: "<?php echo $id?>"
			}
		}
	};
	messages = {
		required: "<?php echo __('Email required')?>",
		email: "<?php echo __('Invalid email')?>",
		remote: "<?php echo __('This contact email already exists')?>"
	};
	add_validate_form_element_rules('#text-email', rules, messages);
}

</script>
