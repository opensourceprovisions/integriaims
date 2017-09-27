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

require_once('include/functions_crm.php');
include_once('include/functions_user.php');

if (defined ('AJAX')) {
	
	global $config;

	$get_data_child = (bool) get_parameter('get_data_child', 0);
	
 	if ($get_data_child) {

		$id_field = get_parameter('id_field', 0);
		if ($id_field) {
			$label_field = get_db_value_sql("SELECT label FROM tcompany_field WHERE id=".$id_field);
		} else {
			$label_field = get_parameter('label_field');
		}

		$label_field_enco = get_parameter('label_field_enco',0);
		if ($label_field_enco) {
			$label_field_enco = str_replace("&quot;","",$label_field_enco);
			$label_field = base64_decode($label_field_enco);
		}

		$id_parent = get_parameter('id_parent');
		$value_parent = get_parameter('value_parent');
		$value_parent = safe_input(safe_output(base64_decode($value_parent)));

		$sql = "SELECT linked_value FROM tcompany_field WHERE parent=".$id_parent."
			AND label='".$label_field."'";
		$field_data = get_db_value_sql($sql);

		$result = false;
		if ($field_data != "") {
			$data = explode(',', $field_data);
			foreach ($data as $item) {
				if ($value_parent == 'any') {

					$pos_pipe = strpos($item,'|')+1;
					$len_item = strlen($item);
					$value_aux = substr($item, $pos_pipe, $len_item);
					$result[$value_aux] = $value_aux;
					
				} else {
					$pattern = "/^".$value_parent."\|/";
					if (preg_match($pattern, $item)) {
						$value_aux = preg_replace($pattern, "",$item);
						$result[$value_aux] = $value_aux;
					}
				}
			}
		}

		$sql_id = "SELECT id FROM tcompany_field WHERE parent=".$id_parent."
					AND label='".$label_field."'";
		$result['id'] = get_db_value_sql($sql_id);
		$result['label'] = $label_field;
		$result['label_enco'] = base64_encode($label_field);
				
		$sql_labels = "SELECT label, id FROM tcompany_field WHERE parent=".$result['id'];

		$label_childs = get_db_all_rows_sql($sql_labels);

		if ($label_childs != false) {
			$i = 0;
			foreach($label_childs as $label) {
				if ($i == 0) {
					$result['label_childs'] = $label['label'];
					$result['id_childs'] = $label['id'];
					$result['label_childs_enco'] = base64_encode($label['label']);
				} else { 
					$result['label_childs'] .= ','.$label['label'];
					$result['id_childs'] .= ','.$label['id'];
					$result['label_childs_enco'] .= ','.base64_encode($label['label']);
				}
				$i++;
			}
		} else {
			$result['label_childs'] = '';
			$result['label_childs_enco'] = '';
		}

		echo json_encode($result);
		return;
	}

}

$id = (int) get_parameter ('id');
$id_invoice = (int) get_parameter('id_invoice');

$section_read_permission = check_crm_acl ('company', 'cr');
$section_write_permission = check_crm_acl ('company', 'cw');
$section_manage_permission = check_crm_acl ('company', 'cm');

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to the company section");
	include ("general/noaccess.php");
	exit;
}

if ($id && !($id_invoice)) {
	$read_permission = check_crm_acl ('company', 'cr', $config['id_user'], $id);
	$write_permission = check_crm_acl ('company', 'cw', $config['id_user'], $id);
	$manage_permission = check_crm_acl ('company', 'cm', $config['id_user'], $id);
	if (!$read_permission && !$write_permission && !$manage_permission) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a company");
		include ("general/noaccess.php");
		exit;
	}
}

$op = (string) get_parameter ("op", "");
$new_company = (bool) get_parameter ('new_company');
$create_company = (bool) get_parameter ('create_company');
$update_company = (bool) get_parameter ('update_company');
$delete_company = (bool) get_parameter ('delete_company');
$delete_invoice = get_parameter ('delete_invoice', 0);
$lock_invoice = get_parameter ('lock_invoice', 0);
$offset = get_parameter ('offset', 0);
$company_fields = get_db_all_rows_sql ("SELECT * FROM tcompany_field");

// Create OR Update
// ----------------

if (($create_company) OR ($update_company)) {
	
	$name = (string) get_parameter ('name');
	$address = (string) get_parameter ('address');
	$fiscal_id = (string) get_parameter ('fiscal_id', 0);
	$comments = (string) get_parameter ('comments');
	$id_company_role = (int) get_parameter ('id_company_role');
	$country = (string) get_parameter ("country");
	$website = (string) get_parameter ("website");
	$manager = (string) get_parameter ("manager");
	$id_parent = (int) get_parameter ("id_parent", 0);
	$payment_conditions = (int) get_parameter ("payment_conditions", 30);
	$last_update = get_parameter('last_update', "0000-00-00 00:00:00");

	
	if ($create_company){
		
		if (!$section_write_permission && !$section_manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a company");
			include ("general/noaccess.php");
			exit;
		}
		$last_update = safe_output($last_update);
		$sql = "INSERT INTO tcompany (name, address, comments, fiscal_id, id_company_role, website, country, manager, id_parent, payment_conditions, last_update)
					 VALUES ('$name', '$address', '$comments', '$fiscal_id', $id_company_role, '$website', '$country', '$manager', $id_parent, $payment_conditions, '$last_update')";

		$id = process_sql ($sql, 'insert_id');

		if ($id === false)
			echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
		else {
			
			foreach ($company_fields as $u) {

				$custom_value = get_parameter("custom_".$u["id"]);
				
				$sql = sprintf('INSERT INTO tcompany_field_data (`data`, `id_company`,`id_company_field`) VALUES ("%s", "%s", %d)',
							$custom_value, $id, $u["id"]);

				$res = process_sql($sql);

				if ($res === false) {
					echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
				}
			}
			echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Company Management", "Created company $name");
		}

	} else {

		// Update company
		if (!$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a company");
			include ("general/noaccess.php");
			exit;
		}

		$sql = "SELECT `date` FROM tcompany_activity WHERE id_company=$id ORDER BY `date` DESC LIMIT 1";
		$last_update = process_sql ($sql);

		if ($last_update == false) {
			$last_update = date("Y-m-d H:i:s");
		}
		
		$sql = sprintf ('UPDATE tcompany SET manager="%s", id_parent = %d, comments = "%s", name = "%s",
		address = "%s", fiscal_id = "%s", id_company_role = %d, country = "%s", website = "%s", last_update = "%s", payment_conditions = %d WHERE id = %d',
		$manager, $id_parent, $comments, $name, $address,
		$fiscal_id, $id_company_role, $country, $website, $last_update, $payment_conditions, $id);

		$result = mysql_query ($sql);
		if ($result === false)
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		else {
	
			//Add custom fields
			foreach ($company_fields as $u) {

				$custom_value = get_parameter("custom_".$u["id"]);
				
				$sql = sprintf('SELECT data FROM tcompany_field_data WHERE id_company = "%s" AND id_company_field = %d',
								$id, $u["id"]);
				
				$current_data = process_sql($sql);
				
				if ($current_data) {
					$sql = sprintf('UPDATE tcompany_field_data SET data = "%s" WHERE id_company = "%s" AND id_company_field = %d',
							$custom_value, $id, $u["id"]);
				} else {
					$sql = sprintf('INSERT INTO tcompany_field_data (`data`, `id_company`,`id_company_field`) VALUES ("%s", "%s", %d)',
							$custom_value, $id, $u["id"]);
				}

				$res = process_sql($sql);

				if ($res === false) {
					echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
				}
			}
			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Company Management", "Updated company $name");
		}
	}
}

// Lock/Unlock INVOICE
// ----------------
if ($lock_invoice == 1){
	
	$id_invoice = get_parameter ("id_invoice", "");
	
	if ($id_invoice) {
		$locked = crm_is_invoice_locked ($id_invoice);
		$res = crm_change_invoice_lock ($config["id_user"], $id_invoice);
		
		if ($res === -1) { // -1 equals to false permission to lock or unlock the invoice
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to lock/unlock an invoice");
			include ("general/noaccess.php");
			exit;
		} else {
			if ($locked && $res === 0) { // The invoice was locked and now is unlocked
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Invoice unlocked", "Invoice ID: $id_invoice");
			} elseif (!$locked && $res === 1) { // The invoice was unlocked and now is locked
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Invoice locked", "Invoice ID: $id_invoice");
			}
			clean_cache_db();
		}
	}
}

// Show tabs for a given company
// --------------------------------

if ($id) {
	
	if (! isset($read_permission)) {
		$read_permission = check_crm_acl ('company', 'cr', $config['id_user'], $id);
	}
	if (! isset($write_permission)) {
		$write_permission = check_crm_acl ('company', 'cr', $config['id_user'], $id);
	}
	if (! isset($manage_permission)) {
		$manage_permission = check_crm_acl ('company', 'cr', $config['id_user'], $id);
	}
	
	$op = get_parameter ("op", "");
	echo '<h2>';
	switch ($op) {
		case "activities":
			echo strtoupper(__('Activities'));
			break;
		case "files":
			echo strtoupper(__('Files'));
			break;
		case "invoices":
			echo strtoupper(__('Invoices'));
			break;
		case "leads":
			echo strtoupper(__('Leads'));
			break;
		case "contracts":
			echo strtoupper(__('Contracts'));
			break;
		case "contacts":
			echo strtoupper(__('Contacts'));
			break;
		case "projects":
			echo strtoupper(__('Projects'));
			break;
		default:
			echo strtoupper(__('Company details'));
	}
	echo '</h2>';
	
	$company = get_db_row ('tcompany', 'id', $id);
	echo '<h4>' . sprintf(__('Company: %s'), $company['name']);
	echo integria_help ("company_detail", true);
	echo '<ul style="height: 30px;" class="ui-tabs-nav">';
	if ($op == "projects")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=projects" title="'.__("Projects").'"><img src="images/projects_tab.png"/></a></li>';
	
	if ($op == "files")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=files" title="'.__("Files").'"><img src="images/disk.png"/></a></li>';
	
	/*
	if ($op == "inventory")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=inventory"><span>'.__("Inventory").'</span></a></li>';
	*/
	
	if ($op == "invoices")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=invoices" title="'.__("Invoices").'"><img src="images/invoice_dark.png"/></a></li>';
	
	if ($op == "leads")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=leads" title="'.__("Leads").'"><img src="images/leads_tab.png"/></a></li>';
	
	if ($op == "contracts")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=contracts" title="'.__("Contracts").'"><img src="images/contracts_tab.png"/></a></li>';
	
	if ($op == "contacts")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=contacts" title="'.__("Contacts").'"><img src="images/groups_small/system-users.png"/></a></li>';

	if ($op == "activities")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=activities" title="'.__("Activity").'"><img src="images/details_tab.png"/></a></li>';
	
	if ($op == "")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'" title="'.__("Company details").'"><img src="images/eye.png"/></a></li>';
	
	echo '<li class="ui-tabs">';
	echo '<a href="index.php?sec=customers&sec2=operation/companies/company_detail" title="'.__("Back to list").'"><img src="images/volver_listado.png"/></a></li>';
		
	echo '</ul>';
	 
	echo '</h4>';
	
	$message = get_parameter('message', '');
	if ($message != '') {
		echo ui_print_success_message (__($message), '', true, 'h3', true);
	}
}

// EDIT / CREATE FORM
if ((($id > 0) AND ($op=="")) OR ($new_company == 1)) {
	
	$disabled_write = false;
	$company_fields = get_db_all_rows_sql ("SELECT * FROM tcompany_field");


	if ($new_company) {
		if (!$section_write_permission && !$section_manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a company");
			include ("general/noaccess.php");
			exit;
		}
	} else { //edit or read
		if (!$write_permission && !$manage_permission) {
			$disabled_write = true;
		}
	}
	
	if($new_company) {
		echo "<h2>".__('Companies')."</h2>";
		echo "<h4>".__('New company');
		echo integria_help ("company_detail", true);
		echo "<div id='button-bar-title'>";
		echo "<ul>";
			echo "<li><a href='index.php?sec=customers&sec2=operation/companies/company_detail'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		echo "</ul>";
		echo "</div>";
		echo "</h4>";
	}

	if (!$new_company && $op == "") { 
		$name = $company['name'];
		$address = $company['address'];
		$comments = $company['comments'];
		$country = $company['country'];
		$address = $company['address'];
		$website = $company["website"];
		$id_company_role = $company['id_company_role'];
		$fiscal_id = $company['fiscal_id'];
		$id_parent = $company["id_parent"];
		$manager = $company["manager"];
		$last_update = $company["last_update"];
		$payment_conditions = $company["payment_conditions"];
		if ($last_update == "0000-00-00 00:00:00" || $last_update == "") {
			$last_update = get_db_sql ("SELECT MAX(date) FROM tcompany_activity WHERE id_company = ". $company["id"]);
		}
	} else {
		$name = "";
		$address = "";
		$comments = "";
		$id_company_role = "";
		$fiscal_id = "";
		$country = "";
		$website = "";
		$manager = $config["id_user"];
		$id_parent = 0;
		$last_update = date("Y-m-d H:i:s");
		$payment_conditions = 30;
	}
	
	$table = new StdClass();
	$table->width = '100%';
	$table->class = "search-table-button";
	$table->data = array ();
	$table->colspan = array ();

	$table->data[0][0] = print_input_text ('name', $name, '', 40, 100, true, __('Company name'), $disabled_write);
	

	if ($id > 0 && ($write_permission || $manage_permission)) {
		$table->data[0][0] .= "&nbsp;<a href='#' onClick='javascript: show_validation_delete(\"delete_company\",".$id.",0,0);' title='".__('Delete company')."'><img src='images/cross.png'></a>";
	}
	
	$help_tip = '';
	if (!$disabled_write) {
		$help_tip = print_help_tip (__("Type at least two characters to search"), true);
	}
	
	$table->data[0][1] = print_input_text_extended ('manager', $manager, 'text-user', '', 18, 30, $disabled_write, '',
	array(), true, '', __('Manager') . $help_tip);
	
	
	$parent_name = $id_parent ? crm_get_company_name($id_parent) : __("None");
	
	$table->data[0][2] = print_input_text_extended ("parent_name", $parent_name, "text-parent_name", '', 18, 0, true, "", "", true, false,  __('Parent company'));
	$table->data[0][2] .= print_input_hidden ('id_parent', $id_parent, true);
	$table->data[0][2] .= "&nbsp;<a href='javascript:show_company_search(\"\",\"\",\"\",\"\",\"\",\"\");' title='".__('Add parent')."'><img src='images/zoom.png'></a>";
	$table->data[0][2] .= "&nbsp;<a href='javascript:clearParent();' title='".__('Clear parent')."'><img src='images/cross.png'></a>";
	
	if (!$new_company) {
		$table->data[1][0] = print_input_text ("last_update", $last_update, "", 18, 100, true, __('Last update'), true);
	}
	else {
		$table->data[1][0] = print_input_text ("last_update", $last_update, "", 18, 100, true, __('Last update'), $disabled_write);
	}
	
	$table->data[1][1] = print_input_text ("fiscal_id", $fiscal_id, "", 18, 100, true, __('Fiscal ID'), $disabled_write);
	$table->data[1][2] = print_select_from_sql ('SELECT id, name FROM tcompany_role ORDER BY name',
		'id_company_role', $id_company_role, '', __('Select'), 0, true, false, false, __('Company Role'), $disabled_write);

	$table->data[2][0] = print_input_text ("website", $website, "", 30, 100, true, __('Website'), $disabled_write);
	$table->data[2][1] = print_input_text ("country", $country, "", 18, 100, true, __('Country'), $disabled_write);
	$days = array (30=>30,60=>60,90=>90,180=>180);
	$table->data[2][2] = print_select ($days, 'payment_conditions', $payment_conditions,"",'','0',true,0,true,__("Payment conditions"), $disabled_write);
	
	$column=3;
	$row=0;

	if($company_fields){
		foreach ($company_fields as $comp) {
			
			$data = get_parameter('custom_'.$comp["id"]);
			
			if(!$data){
				$sql_data = sprintf('SELECT data FROM tcompany_field_data WHERE id_company = "%s" AND id_company_field = %d', $id, $comp["id"]);
				
				$result = process_sql($sql_data);
				
				if($result) {
					$data = safe_output($result[0]["data"]);
				}
			}
		
			switch ($comp["type"]) {
				case "text": 
					$table->data[$column][$row] = print_input_text ("custom_".$comp["id"], $data, "", 18, 100, true, $comp["label"], $disabled_write);
					break;
				
				case "combo":
					$aux = split(",", $comp["combo_value"]);
					
					$options = array();

					foreach ($aux as $a) {
						$options[$a] = $a;
					}

					$table->data[$column][$row] = print_select ($options, 'custom_'.$comp["id"], $data, '', '', '0', true, false, false, $comp["label"]);
					break;

				case "linked":
					$linked_values = explode(",", $comp['linked_value']);
					
					if ($id) {
						$has_parent = get_db_value_sql("SELECT parent FROM tcompany_field WHERE id=".$comp['id']);
						if ($has_parent) {
							$parent_value = get_db_value_sql("SELECT `data` FROM tcompany_field_data WHERE id_company =".$id." AND id_company_field =".$has_parent);

							$values = array();
							foreach ($linked_values as $value) {
								$parent_found = preg_match("/^".$parent_value."\|/", $value);

								if ($parent_found) {
									$value_without_parent =  preg_replace("/^.*\|/","", $value);
									$values[$value_without_parent] = $value_without_parent;
								}
							}
						} else {
							foreach ($linked_values as $value) {
								$values[$value] = $value;
							}
						}
						
						$has_childs = get_db_all_rows_sql("SELECT * FROM tcompany_field WHERE parent=".$comp['id']);
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
							$script = 'javascript:change_linked_type_fields_table_company('.$childs.','.$comp['id'].');';
						} else {
							$script = '';
						}
						
					} else {
						$values = array();
						foreach ($linked_values as $value) {
							$value_without_parent =  preg_replace("/^.*\|/","", $value);

							$values[$value_without_parent] = $value_without_parent;
							$has_childs = get_db_all_rows_sql("SELECT * FROM tcompany_field WHERE parent=".$comp['id']);

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

								$script = 'javascript:change_linked_type_fields_table_company('.$childs.','.$comp['id'].');';
							} else {
								$script = '';
							}
						}
					}
					$table->data[$column][$row] = print_select ($values, 'custom_'.$comp['id'], $data, $script, __('Any'), '', true, false, false, $comp['label']);
					break;

				case "numeric":
					if($data == '') 
						$data = 0;
					$table->data[$column][$row] = print_input_number ('custom_'.$comp["id"], $data, 0, 1000000, '', true, $comp["label"], $disabled_write);
					break;

				case "date":
					$table->data[$column][$row] = print_input_date ('custom_'.$comp["id"], $data, '', '', '', true, $comp["label"], $disabled_write);
					break;

				case "textarea":
					if($column != 0){
						$column++;
					}
					$table->colspan[$column][0] = 3;
					$table->data[$column][0] = print_textarea ('custom_'.$comp["id"], 3, 1, $data, '', true, $comp["label"], $disabled_write);
					$column++;
					$row = -1;
					break;
			}
			
			if($row < 2){
				$row++;
			} else {
				$row=0;
				$column++;
			}
		}
		if ($row != 2){
			$column++;
		}
	}
	$column++;
	$table->colspan[$column][0] = 3;
	$table->data[$column][0] = print_textarea ('address', 3, 1, $address, '', true, __('Address'), $disabled_write);
	$column++;
	$table->colspan[$column][0] = 3;
	$table->data[$column][0] = print_textarea ("comments", 10, 1, $comments, '', true, __('Comments'), $disabled_write);
	
	echo '<form id="form-company_detail" method="post" action="index.php?sec=customers&sec2=operation/companies/company_detail">';
	print_table ($table);
	echo "<div style='width:100%; text-align:right;'>";
		unset($table->data);
		$table->class = "button-form";
		$table->width = "100%";
		if ($id > 0 && ($write_permission || $manage_permission)) {
			$button = print_submit_button (__('Update'), "update_btn", false, 'class="sub upd"', true);
			$button .= print_input_hidden ('update_company', 1, true);
			$button .= print_input_hidden ('id', $id, true);
		} elseif ( $id == 0 && $section_write_permission || $section_manage_permission) {
			$button = print_submit_button (__('Create'), "create_btn", false, 'class="sub upd"', true);
			$button .= print_input_hidden ('create_company', 1, true);
		}
			
		$table->data[6][0] = $button;
		$table->colspan[6][0] = 2;
		print_table ($table);
	echo "</div>";
	echo '</form>';
}

// Files
elseif ($op == "files") {
	include ("operation/companies/company_files.php");
}

// Activities
elseif ($op == "activities") {

	$op2 = get_parameter ("op2", "");
	if ($op2 == "add"){
		
		$datetime =  date ("Y-m-d H:i:s");
		$comments = get_parameter ("comments", "");
		
		if ($comments != "") {
			$sql = sprintf ('INSERT INTO tcompany_activity (id_company, written_by, date, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, $comments);
			process_sql ($sql, 'insert_id');
			$sql = sprintf ('UPDATE tcompany SET last_update = "%s" WHERE id = %d', $datetime, $id);
			process_sql ($sql);
		} else {
			echo ui_print_error_message (__('Error adding activity. Empty activity'), '', true, 'h3', true);
		}
	}
	
	$company_name = get_db_sql ("SELECT name FROM tcompany WHERE id = $id");
	
	$table = new StdClass();
	$table->width = "100%";
	$table->class = "search-table-button";
	$table->data = array ();
	$table->size = array ();
	$table->style = array ();

	$table->data[0][0] = "<h3>".__("Add activity")."</h3>";
	$table->data[1][0] = "<textarea name='comments' style='width:98%; height: 210px'></textarea>";

	echo '<form method="post" action="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=activities&op2=add">';
	print_table($table);
		echo "<div class='no' style='width:100%; text-align:right;'>";
			unset($table->data);
			$table->class = "button-form";
			$table->width = "100%";
			$table->data[0][0] = print_submit_button (__('Add activity'), "create_btn", false, 'class="sub next"', true);
			print_table($table);
		echo '</div>';
	echo '</form>';

	$contacts = crm_get_all_contacts (sprintf(" WHERE id_company = %d", $id));

	$sql = "SELECT * FROM tcompany_contact CC, tcontact_activity C WHERE CC.id_company = $id AND CC.id = C.id_contact ORDER BY C.creation DESC";

	$act_contacts = get_db_all_rows_sql($sql);

	$sql = "SELECT * FROM tcompany_activity WHERE id_company = $id ORDER BY date DESC";

	$activities = get_db_all_rows_sql ($sql);
	if ($activities == false) {
		$activities = array();
	}

	if ($act_contacts !== false) {
		$activities = array_merge($activities, $act_contacts);
	}

	if ($activities !== false) {
		
		$activities = print_array_pagination ($activities, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=activities");
		$aux_activities = array();

		foreach ($activities as $key => $act) {

			if (isset($act["date"])) {
				$aux_activities[$key] = $act["date"];
			} else {
				$aux_activities[$key] = $act["creation"];
			}
		}

		arsort($aux_activities);

		foreach ($aux_activities as $key=>$date) {

			$activity = $activities[$key];

			echo "<div class='notetitle'>"; // titulo

			if (isset($activity["id_contact"])) {
				$type = "group";
				$title = __("Contact");
				$timestamp = $activity["creation"];
				$contact_name = "&nbsp;&nbsp;".__("on contact").":&nbsp;".get_db_value("fullname", "tcompany_contact", "id", $activity["id_contact"]);
			} else {
				$type = "company";
				$title = __("Company");
				$timestamp = $activity["date"];
				$contact_name = "";
			}

			$nota = $activity["description"];
			$id_usuario_nota = $activity["written_by"];

			$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_usuario_nota);

			// Show data
			echo '<img src="images/'.$type.'.png"/ width="22px" title="'.$title.'">';
			echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
			echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_usuario_nota'>";
			echo $id_usuario_nota;
			echo "</a>";
			echo " ".__("said on $timestamp");
			echo $contact_name;
			echo "</div>";

			// Body
			echo "<div class='notebody'>";
			echo clean_output_breaks($nota);
			echo "</div>";
		}
	}
}

// CONTRACT LISTING
elseif ($op == "contracts") {
	if ($write_permission || $manage_permission) {

		$search_text = (string) get_parameter ('search_text');
		$search_company_role = (int) get_parameter ('search_company_role');
		$search_date_end = get_parameter ('search_date_end');
		$search_date_begin = get_parameter ('search_date_begin');
		$search_date_begin_beginning = get_parameter ('search_date_begin_beginning');
		$search_date_end_beginning = get_parameter ('search_date_end_beginning');
		$search_status = (int) get_parameter ('search_status', 1);
		$search_expire_days = (int) get_parameter ('search_expire_days');

		$search_params = "search_text=$search_text&search_company_role=$search_company_role&search_date_end=$search_date_end&search_date_begin=$search_date_begin&search_date_begin_beginning=$search_date_begin_beginning&search_date_end_beginning=$search_date_end_beginning&search_status=$search_status&search_expire_days=$search_expire_days";
		
		//$where_clause = "WHERE 1=1";
		$where_clause = "WHERE id_company=".$id;
		if ($search_text != "") {
			$where_clause .= sprintf (' AND (id_company IN (SELECT id FROM tcompany WHERE name LIKE "%%%s%%") OR 
				name LIKE "%%%s%%" OR 
				contract_number LIKE "%%%s%%")', $search_text, $search_text, $search_text);
		}
		
		if ($search_company_role) {
			$where_clause .= sprintf (' AND id_company IN (SELECT id FROM tcompany WHERE id_company_role = %d)', $search_company_role);
		}
		
		if ($search_date_end != "") {
			$where_clause .= sprintf (' AND date_end <= "%s"', $search_date_end);
		}
		
		if ($search_date_begin != "") {
			$where_clause .= sprintf (' AND date_end >= "%s"', $search_date_begin);
		}
			
		if ($search_date_end_beginning != "") {
			$where_clause .= sprintf (' AND date_begin <= "%s"', $search_date_end_beginning);
		}
		
		if ($search_date_begin_beginning != "") {
			$where_clause .= sprintf (' AND date_begin >= "%s"', $search_date_begin_beginning);
		}
		
		if ($search_status >= 0) {
			$where_clause .= sprintf (' AND status = %d', $search_status);
		}
		
		if ($search_expire_days > 0) {
			// Comment $today_date to show contracts that expired yet
			$today_date = date ("Y/m/d");
			$expire_date = date ("Y/m/d", strtotime ("now") + $search_expire_days * 86400);
			$where_clause .= sprintf (' AND (date_end < "%s" AND date_end > "%s")', $expire_date, $today_date);
		}


		$form = '<form action="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=contracts" method="post">';
		$form .= '<div class="divform">';
		$form .= "<table width=100% class='search-table'>";
		
		$form .= "<tr><td colspan=2>";
		$form .= print_input_text ("search_text", $search_text, "", 38, 100, true, __('Search'));
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_select (get_company_roles(), 'search_company_role',
			$search_company_role, '', __('All'), 0, true, false, false, __('Company roles'));	
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_select (get_contract_status(), 'search_status',
			$search_status, '', __('Any'), -1, true, false, false, __('Status'));	
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_select (get_contract_expire_days(), 'search_expire_days',
			$search_expire_days, '', __('None'), 0, true, false, false, __('Out of date'));	
		$form .= "</td></tr>";

		$form .= "<tr><td>";
		$form .= print_input_text ('search_date_begin_beginning', $search_date_begin_beginning, '', 15, 20, true, __('Begining From') .
			"<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_input_text ('search_date_end_beginning', $search_date_end_beginning, '', 15, 20, true, __('Begining To').
			"<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_input_text ('search_date_begin', $search_date_begin, '', 15, 20, true, __('Ending From')."<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
		$form .= print_input_text ('search_date_end', $search_date_end, '', 15, 20, true, __('Ending To'). "<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td></tr>";
		
		$form .= "<tr><td>";
			$form .= print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
		$form .= "</td></tr>";

		$form .= "</table>";
		$form .= "</div>";
		$form .= '</form>';

		echo $form;
	}

	//$contracts = get_contracts(false, "id_company = $id ORDER BY name");
	//$contracts = crm_get_user_contracts($config['id_user'], $contracts);
	
	$contracts = crm_get_all_contracts ($where_clause, 'name');
	
	$contracts = print_array_pagination ($contracts, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=contracts");
	
	if ($contracts !== false) {
		
		$table = new StdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->cellspacing = 0;
		$table->cellpadding = 0;
		$table->tablealign="left";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->colspan = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Contract number');
		$table->head[2] = __('Company');
		$table->head[3] = __('Begin');
		$table->head[4] = __('End');
		$table->head[5] = __('Status');
		$counter = 0;
	
		foreach ($contracts as $contract) {
			
			$data = array ();
		
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail&id_contract="
				.$contract["id"]."'>".$contract["name"]."</a>";
			$data[1] = $contract["contract_number"];
			$data[2] = get_db_value ('name', 'tcompany', 'id', $contract["id_company"]);
			$data[3] = $contract["date_begin"];
			$data[4] = $contract["date_end"] != '0000-00-00' ? $contract["date_end"] : "-";
			$data[5] = get_contract_status_name($contract["status"]);
		
			array_push ($table->data, $data);
		}
		if ($write_permission || $manage_permission)
			echo '<div class="divresult">';
		print_table ($table);
		if ($write_permission || $manage_permission)
			echo '</div>';
	}

	if ($write_permission || $manage_permission) {
		echo '<form method="post" action="index.php?sec=customers&sec2=operation/contracts/contract_detail&id_company='.$id.'">';
			echo '<div class="divform">';
				echo '<table class="search-table"><tr><td>';
					print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"');
					print_input_hidden ('new_contract', 1);
				echo '</td></tr></table>';
			echo '</div>';
		echo '</form>';
	}
}

// CONTACT LISTING
elseif ($op == "contacts") {
	
	if ($write_permission || $manage_permission) {
		echo '<div class="divform">';
		echo '<table class="search-table">';
		echo '<tr>';
		echo '<td>';
		echo '<form method="post" action="index.php?sec=customers&sec2=operation/contacts/contact_detail&id_company='.$id.'">';
		print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"');
		print_input_hidden ('new_contact', 1);
		echo '</form>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}
	$name = get_db_value ('name', 'tcompany', 'id', $id);
	
	$table->class = 'listing';
	$table->width = '100%';
	$table->head = array ();
	$table->head[0] = __('Contact');
	$table->head[1] = __('Email');
	$table->head[2] = __('Position');
	$table->head[3] = __('Details');
	
	$table->size = array ();
	$table->data = array ();
	
	$contacts = get_db_all_rows_sql ("SELECT * FROM tcompany_contact WHERE id_company = $id");

	if ($contacts === false)
		$contacts = array ();

	foreach ($contacts as $contact) {
		$data = array ();
		$data[0] = '<a href="index.php?sec=customers&sec2=operation/contacts/contact_detail&id='.$contact['id'].'">'.$contact['fullname']."</a>";
		$data[1] = $contact['email'];
		$details = '';
		if ($contact['phone'] != '')
			$details .= '<strong>'.__('Phone number').'</strong>: '.$contact['phone'].'<br />';
		if ($contact['mobile'] != '')
			$details .= '<strong>'.__('Mobile phone').'</strong>: '.$contact['mobile'].'<br />';
		$data[2] = $contact['position'];
		$data[3] = print_help_tip ($details, true, 'tip_view');
		array_push ($table->data, $data);			
	}
	if ($write_permission || $manage_permission)
		echo '<div class="divresult">';
	print_table ($table);
	if ($write_permission || $manage_permission)
		echo '</div>';
	
} // end of contact view

// INVOICES LISTING
elseif ($op == "invoices") {
	
	$permission = check_crm_acl ('invoice', '', $config['id_user'], $id);

	$new_invoice = get_parameter("new_invoice", 0);
	$operation_invoices = get_parameter ("operation_invoices", "");
	$view_invoice = get_parameter("view_invoice", 0);

	if ((!$permission && !$manage_permission) AND ($operation_invoices != "add_invoice")) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to an invoice");
		include ("general/noaccess.php");
		exit;
	}
	
	$company_name = get_db_sql ("SELECT name FROM tcompany WHERE id = $id");

	if (($operation_invoices != "") OR ($new_invoice != 0) OR ($view_invoice != 0 ) ){
			
		$id_invoice = get_parameter ("id_invoice", -1);
		if ($id_invoice) {
			$is_locked = crm_is_invoice_locked ($id_invoice);
			$lock_permission = crm_check_lock_permission ($config["id_user"], $id_invoice);
		}
		
		if ($new_invoice == 0 && $is_locked) {
			
			$locked_id_user = crm_get_invoice_locked_id_user ($id_invoice);
			// Show an only readable invoice
			echo "<h3 class=error>". __("Bloqued Invoice #"). $id_invoice;
			echo ' ('.__('Locked by ').$locked_id_user.')';
			echo ' <a href="index.php?sec=users&amp;sec2=operation/invoices/invoice_view
					&amp;id_invoice='.$id_invoice.'&amp;clean_output=1&amp;pdf_output=1">
					<img src="images/page_white_acrobat.png" title="'.__('Export to PDF').'"></a>';
			if ($lock_permission) {
				
				if ($is_locked) {
					$lock_image = 'lock.png';
					$title = __('Unlock');
				} else {
					$lock_image = 'lock_open.png';
					$title = __('Lock');
				}
				echo ' <a href="?sec=customers&sec2=operation/companies/company_detail
					&lock_invoice=1&id='.$id.'&op=invoices&id_invoice='.$id_invoice.'" 
					onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">
					<img src="images/'.$lock_image.'" title="'.$title.'"></a>';
			}
			echo "</h3>";
			include ("operation/invoices/invoice_view.php");
		}
		else {
			// Show edit/insert invoice
			include ("operation/invoices/invoices.php");
		}
	}

	// Operation_invoice changes inside the previous include

	if (($operation_invoices == "") AND ($new_invoice == 0) AND ($view_invoice == 0)) {
		if ($section_write_permission || $section_manage_permission) {

			// Invoice listing
			$search_text = (string) get_parameter ('search_text');
			$search_invoice_status = (string) get_parameter ('search_invoice_status');
			$search_last_date = (int) get_parameter ('search_last_date');
			$search_date_begin = get_parameter ('search_date_begin');
			$search_date_end = get_parameter ('search_date_end');
			$search_invoice_type = (string) get_parameter ('search_invoice_type', 'All');
			$search_company_role = (int) get_parameter ('search_company_role');
			$search_company_manager = (string) get_parameter ('search_company_manager');
			$search_contract_number = (string) get_parameter ('search_contract_number');

			$order_by = get_parameter ('order_by', '');

			$search_params = "&search_text=$search_text&search_invoice_status=$search_invoice_status&search_last_date=$search_last_date&search_date_end=$search_date_end&search_date_begin=$search_date_begin&order_by=$order_by&search_invoice_type=$search_invoice_type&search_company_role=$search_company_role&search_company_manager=$search_company_manager";
			
			$where_clause = "";
			
			$parent_company = get_db_value ('id_parent', 'tcompany', 'id', $id);
			if ((!$parent_company) && ($parent_company != '')) {
				$where_clause = "(id_company = $id OR id_company = $parent_company) ";
			} else {
				$where_clause = "id_company = $id ";
			}

			if ($search_text != "") {
				$where_clause .= sprintf (' AND (id_company IN (SELECT id FROM tcompany WHERE name LIKE "%%%s%%") OR 
					bill_id LIKE "%%%s%%" OR 
					description LIKE "%%%s%%")', $search_text, $search_text, $search_text);
			}
			if ($search_invoice_status != "") {
				if ($search_invoice_status == "active") {
					$where_clause .= ' AND status <> "canceled"';
				} else {
					$where_clause .= sprintf (' AND status = "%s"', $search_invoice_status);
				}
			}

			// last_date is in days
			if ($search_last_date) {
				$last_date_seconds = $search_last_date * 24 * 60 * 60;
				$search_date_begin = date('Y-m-d H:i:s', time() - $last_date_seconds);
				//$search_date_end = date('Y-m-d H:i:s');
				$search_date_end = "";
			}

			if ($search_date_begin != "") {
				$where_clause .= sprintf (' AND invoice_create_date >= "%s"', $search_date_begin);
			}
			if ($search_date_end != "") {
				$where_clause .= sprintf (' AND invoice_create_date <= "%s"', $search_date_end);
			}
			if ($search_invoice_type != "All") {
				$where_clause .= sprintf (' AND invoice_type = "%s"', $search_invoice_type);
			}
			if ($search_company_role > 0) {
				$where_clause .= sprintf (' AND id_company IN (SELECT id FROM tcompany WHERE id_company_role = %d)', $search_company_role);
			}
			if ($search_company_manager != "") {
				$where_clause .= sprintf (' AND id_company IN (SELECT id FROM tcompany WHERE manager = "%s")', $search_company_manager);
			}
			if ($search_contract_number != "") {
				$where_clause .= sprintf (' AND contract_number = "%s"', $search_contract_number);
			}

			$form = '<form method="post">';

			$table = new stdClass();
			$table->id = 'invoices_table';
			$table->width = '100%';
			$table->class = 'search-table';
			$table->size = array();
			$table->style = array();
			$table->style[1] = 'vertical-align:top;';
			$table->colspan[2][0] = 4;
			$table->rowspan[0][1] = 2;
			$table->data = array();

			$table->data[0][0] = print_input_text ("search_text", $search_text, "", 30, 100, true, __('Search'));

			$table->data[1][0] = get_last_date_control ($search_last_date, 'search_last_date', __('Date'), $search_date_begin, 'search_date_begin', __('From'), $search_date_end, 'search_date_end', __('To'));

			$sql = 'SELECT id, name FROM tcompany_role ORDER BY name';
			$table->data[2][0] = print_select_from_sql ($sql, 'search_company_role', $search_company_role, '', __('Any'), 0, true, false, false, __('Company Role'));
			
			$invoice_types = array('All'=>__('All'), 'Submitted'=>__('Submitted'), 'Received'=>__('Received'));
			$table->data[3][0] = print_select ($invoice_types, 'search_invoice_type', $search_invoice_type, '','', 0, true, 0, false, __('Invoice type'), false, 'width:150px;');
			$table->data[4][0] = print_input_text_extended ('search_company_manager', $search_company_manager, 'text-search_company_manager', '', 20, 50, false, '', array(), true, '', __("Manager"). print_help_tip (__("Type at least two characters to search"), true) );

			$table->data[5][0] = print_input_text ("search_contract_number", $search_contract_number, "", 20, 100, true, __('Contract number'));

			$invoice_status_ar = array();
			$invoice_status_ar['active'] = __("Active");
			$invoice_status_ar['pending'] = __("Pending");
			$invoice_status_ar['paid'] = __("Paid");
			$invoice_status_ar['canceled'] = __("Canceled");
			$table->data[6][0] = print_select ($invoice_status_ar, 'search_invoice_status', $search_invoice_status, '', __("Any"), '', true, 0, false, __('Invoice status'), false, 'width:150px;');
				
			$table->data[7][0] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
				
		
				$form .= print_table($table,true);
			
			
			$form .= '</form>';

			echo '<div class="divform">';
				
				echo $form;
				
				echo '<form method="post" action="index.php?sec=customers&sec2=operation/companies/company_detail&id='.$id.'&op=invoices">';
					echo '<table class="search-table"><tr><td>';
						print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"');
						print_input_hidden ('new_invoice', 1);
					echo '</td></tr></table>';
				echo '</form>';
				
			echo '</div>';
		}
	
		$invoices = crm_get_all_invoices ($where_clause, $order_by);
		
		if ($invoices !== false) {
			
			$invoices = print_array_pagination ($invoices, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=invoices");
			$table->width = "100%";
			$table->class = "listing";
			$table->cellspacing = 0;
			$table->cellpadding = 0;
			$table->tablealign="left";
			$table->data = array ();
			$table->size = array ();
			$table->style = array ();
			$table->style[0] = 'font-weight: bold';
			$table->colspan = array ();
			$table->head[0] = __('ID');
			//$table->head[1] = __('Description');
			$table->head[2] = __('Amount');
			$table->head[3] = __('Type');
			$table->head[4] = __('Status');
			$table->head[5] = __('Creation');
			$table->head[6] = __('Expiration');
			$table->head[7] = __('Options');
			
			$counter = 0;
		
			$company = get_db_row ('tcompany', 'id', $id);
		
			foreach ($invoices as $invoice) {
				
				$lock_permission = crm_check_lock_permission ($config["id_user"], $invoice["id"]);
				$is_locked = crm_is_invoice_locked ($invoice["id"]);
				$locked_id_user = false;
				if ($is_locked) {
					$locked_id_user = crm_get_invoice_locked_id_user ($invoice["id"]);
				}
				
				$data = array ();
			
				$url = "index.php?sec=customers&sec2=operation/companies/company_detail&view_invoice=1&id=".$id."&op=invoices&id_invoice=". $invoice["id"];

				$data[0] = "<a href='$url'>".$invoice["bill_id"]."</a>";
				//$data[1] = "<a href='$url'>".$invoice["description"]."</a>";
				$data[2] = format_numeric(get_invoice_amount ($invoice["id"])) ." ". strtoupper ($invoice["currency"]);

				$tax = get_invoice_tax_sum ($invoice["id"]);
				$tax_amount = get_invoice_amount ($invoice["id"]) * (1 + $tax/100);

				if ($tax != 0)
					$data[2] .= print_help_tip (__("With taxes"). ": ". format_numeric($tax_amount), true);
				
				$data[3] = __($invoice["invoice_type"]);
				$data[4] = __($invoice["status"]);
				$data[5] = "<span style='font-size: 10px'>".$invoice["invoice_create_date"]. "</span>";
				$data[6] = "<span style='font-size: 10px'>".$invoice["invoice_expiration_date"]. "</span>";
				$data[7] = '<a href="index.php?sec=users&amp;sec2=operation/invoices/invoice_view
					&amp;id_invoice='.$invoice["id"].'&amp;clean_output=1&amp;pdf_output=1&language='.$invoice['id_language'].'">
					<img src="images/page_white_acrobat.png" title="'.__('Export to PDF').'"></a>';
				if ($lock_permission) {
					
					if ($is_locked) {
						$lock_image = 'lock.png';
						$title = __('Unlock');
					} else {
						$lock_image = 'lock_open.png';
						$title = __('Lock');
					}
					$data[7] .= ' <a href="?sec=customers&sec2=operation/companies/company_detail
						&lock_invoice=1&id='.$invoice["id_company"].'&op=invoices&id_invoice='.$invoice["id"].'" 
						onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">
						<img src="images/'.$lock_image.'" title="'.$title.'"></a>';
				}
				if (!$is_locked) {
					$data[7] .= "<a href='#' onClick='javascript: show_validation_delete(\"delete_company_invoice\",".$invoice["id"].",".$id.",".$offset.");'><img src='images/cross.png' title='".__('Delete')."'></a>";	
				} else {
					if ($locked_id_user) {
						$data[7] .= ' <img src="images/administrator_lock.png" width="18" height="18"
						title="'.__('Locked by '.$locked_id_user).'">';
					}
				}
				
				array_push ($table->data, $data);
			}	
			if ($write_permission || $manage_permission)
				echo '<div class="divresult">';
			print_table ($table);
			if ($write_permission || $manage_permission)
				echo '</div>';	
		} 
		else {
			echo '<div class="divresult">';
				echo ui_print_error_message (__('empty search invoices'), '', true, 'h3', true);
			echo '</div>';
		}
	} 
}

// Leads listing
elseif ($op == "leads") {
	
	if ($section_write_permission || $section_manage_permission) {
		echo '<div class="divform">';
		echo '<table class="search-table">';
		echo '<tr>';
		echo '<td>';
		echo '<form method="post" action="index.php?sec=customers&sec2=operation/leads/lead_detail&id_company='.$id.'">';
		print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"');
		print_input_hidden ('new', 1);
		echo '</form>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}
	$leads = crm_get_all_leads ("WHERE id_company = $id and progress < 100");
	
	$leads = print_array_pagination ($leads, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id&op=leads");
	
	$company_name = get_db_sql ("SELECT name FROM tcompany WHERE id = $id");
	
	if ($leads !== false) {
		
		$table = new StdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->cellspacing = 0;
		$table->cellpadding = 0;
		$table->tablealign="left";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->colspan = array ();
		
		$table->head[0] = __('Fullname');
		$table->head[1] = __('Owner');
		$table->head[2] = __('Company');
		$table->head[3] = __('Updated at');
		$table->head[4] = __('Country');
		$table->head[5] = __('Progress');
		$table->head[6] = __('Estimated sale');
		$counter = 0;
			
		foreach ($leads as $lead) {	
			$data = array ();
		
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/leads/lead_detail&id="
				.$lead["id"]."'>".$lead["fullname"]."</a>";

			$data[1] = $lead["owner"];
			$data[2] = $lead["company"];
			$data[3] = $lead["modification"];
			$data[4] = $lead["country"];
			$data[5] = translate_lead_progress ($lead["progress"]);		
			$data[6] = format_numeric ($lead["estimated_sale"]);
			
			array_push ($table->data, $data);
		}	
		if ($write_permission || $manage_permission)
			echo '<div class="divresult">';
		print_table ($table);
		if ($write_permission || $manage_permission)
			echo '</div>';		
	}
}

// Projects
elseif ($op == 'projects') {
	$sql = "SELECT DISTINCT id_project FROM trole_people_task, ttask WHERE ttask.id = trole_people_task.id_task
			AND id_user IN (SELECT id_usuario FROM tusuario WHERE id_company=$id)";

	$company_projects = get_db_all_rows_sql($sql);
	if ($company_projects == false) {
		echo '<h4>'.__("No projects").'</h4>';
	}

	crm_print_company_projects_tree($company_projects);
	
	//div to show user info
	echo "<div class= 'dialog ui-dialog-content' title='".__("User info")."' id='user_info_window'></div>";
}

// No id passed as parameter
	
if ((!$id) AND ($new_company == 0)){

	$message = get_parameter('message', '');

	if ($message != '') {
		echo ui_print_success_message (__($message), '', true, 'h3', true);
	}
	
	// Search // General Company listing
	//echo "<div id='inventory-search-content'>";

	$search_text = (string) get_parameter ("search_text");	
	$search_role = (int) get_parameter ("search_role");
	$search_country = (string) get_parameter ("search_country");
	$search_manager = (string) get_parameter ("search_manager");
	$search_parent = (int) get_parameter ("search_parent");
	$search_date_begin = (string) get_parameter("search_date_begin");
	$search_date_end = (string) get_parameter("search_date_end");
	$search_min_billing = (float) get_parameter("search_min_billing");
	$order_by_activity = (string) get_parameter ("order_by_activity");
	$order_by_company = (string) get_parameter ("order_by_company");
	$order_by_billing = (string) get_parameter ("order_by_billing");
	
	$where_clause = "";
	$date = false;
	
	if ($search_text != "") {
		$where_clause .= sprintf (' AND ( `name` LIKE "%%%s%%" OR `country` LIKE "%%%s%%" OR tcompany.id LIKE "%%%s%%")  ', $search_text, $search_text, $search_text);
	}

	if ($search_role != 0){ 
		$where_clause .= sprintf (' AND id_company_role = %d', $search_role);
	}

	if ($search_country != ""){ 
		$where_clause .= sprintf (' AND country LIKE "%%%s%%" ', $search_country);
	}

	if ($search_manager != ""){ 
		$where_clause .= sprintf (' AND manager = "%s" ', $search_manager);
	}
	
	if ($search_parent != 0){ 
		$where_clause .= sprintf (' AND id_parent = %d ', $search_parent);
	}
	
	if ($search_date_begin != "") { 
		$where_clause .= " AND `last_update` >= '$search_date_begin 00:00:00'";
		$date = true;
	}

	if ($search_date_end != "") { 
		$where_clause .= " AND `last_update` <= '$search_date_end 23:59:59'";
		$date = true;
	}

	if ($search_min_billing != "") { 
		$having .= "HAVING `billing` >= $search_min_billing";
	}
	
	// ORDER
	$order_by = " ORDER BY ";
	if ($order_by_activity != "") {
		if ($order_by_activity == "ASC") {
			$order_by .= "tcompany.last_update ASC";
			$activity_order_image = "&nbsp;<a href='javascript:changeActivityOrder(\"DESC\")'><img src='images/arrow_down_orange.png'></a>";
		} else {
			$order_by .= "tcompany.last_update DESC";
			$activity_order_image = "&nbsp;<a href='javascript:changeActivityOrder(\"ASC\")'><img src='images/arrow_up_orange.png'></a>";
		}
		$company_order_image = "&nbsp;<a href='javascript:changeCompanyOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
		$billing_order_image = "&nbsp;<a href='javascript:changeBillingOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
	} elseif ($order_by_company != "") {
		if ($order_by_company == "DESC") {
			$order_by .= "tcompany.name DESC";
			$company_order_image = "&nbsp;<a href='javascript:changeCompanyOrder(\"ASC\")'><img src='images/arrow_down_orange.png'></a>";
		} else {
			$order_by .= "tcompany.name ASC";
			$company_order_image = "&nbsp;<a href='javascript:changeCompanyOrder(\"DESC\")'><img src='images/arrow_up_orange.png'></a>";
		}
		$activity_order_image = "&nbsp;<a href='javascript:changeActivityOrder(\"DESC\")'><img src='images/block_orange.png'></a>";
		$billing_order_image = "&nbsp;<a href='javascript:changeBillingOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
	} elseif ($order_by_billing != "") {
		if ($order_by_billing == "DESC") {
			$order_by .= "billing DESC";
			$billing_order_image = "&nbsp;<a href='javascript:changeBillingOrder(\"ASC\")'><img src='images/arrow_down_orange.png'></a>";
		} else {
			$order_by .= "billing ASC";
			$billing_order_image = "&nbsp;<a href='javascript:changeBillingOrder(\"DESC\")'><img src='images/arrow_up_orange.png'></a>";
		}
		$activity_order_image = "&nbsp;<a href='javascript:changeActivityOrder(\"DESC\")'><img src='images/block_orange.png'></a>";
		$company_order_image = "&nbsp;<a href='javascript:changeCompanyOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
	} else {
		$order_by .= "tcompany.last_update DESC";
		$activity_order_image = "&nbsp;<a href='javascript:changeActivityOrder(\"ASC\")'><img src='images/arrow_up_orange.png'></a>";
		$company_order_image = "&nbsp;<a href='javascript:changeCompanyOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
		$billing_order_image = "&nbsp;<a href='javascript:changeBillingOrder(\"ASC\")'><img src='images/block_orange.png'></a>";
	}
	
	echo "<h2>".__('Company management') . "</h2>";
	echo "<h4>".__('List Company');
		echo integria_help ("company_detail", true);
		echo "<div id='button-bar-title'>";
			echo "<ul>";
				echo "<li>";
					$where_clause = str_replace(array("\r", "\n"), '', $where_clause);
					$buttons = print_button(__('Export to CSV'), '', false, 'window.open(\'' . 'include/export_csv.php?export_csv_companies=1&where_clause=' . 
						str_replace('"', "\'", $where_clause) . '&date=' . $date . '\')', 'class="sub csv"', true);
					echo $buttons;
				echo "</li>";
				echo "<li>";
					echo "<a id='company_stats_form_submit' href='javascript: changeAction();'>".
						print_image ("images/chart_bar_dark.png", true, array("title" => __("Search statistics")))."</a>";
				echo "</li>";
			echo "</ul>";
		echo "</div>";
	echo "</h4>";
	
	
	$search_params = "&search_manager=$search_manager&search_text=$search_text&search_role=$search_role&search_country=$search_country&search_parent=$search_parent&search_date_begin=$search_date_begin&search_date_end=$search_date_end&search_min_billing=$search_min_billing&order_by_activity=$order_by_activity&order_by_company=$order_by_company&order_by_billing=$order_by_billing";
	
	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->style = array ();
	$table->data = array ();
	$table->data[0][0] = print_input_text_extended ("search_text", $search_text, "", '', 15, 100, false, '', "style='width:210px;'", true, '', __('Search').print_help_tip (__("Search according to company ID, name and country"), true));
	$table->data[0][1] = print_select_from_sql ('SELECT id, name FROM tcompany_role ORDER BY name',
		'search_role', $search_role, '', __('Select'), 0, true, false, false, __('Company Role'));
	$table->data[0][2] = print_input_text ("search_country", $search_country, "", 15, 100, true, __('Country'));
	$table->data[0][3] = print_input_text_extended ('search_manager', $search_manager, 'text-user', '', 15, 30, false, '', array(), true, '', __('Manager'). print_help_tip (__("Type at least two characters to search"), true));
	
	// $companies_name = crm_get_companies_list("", false, "ORDER BY name", true);
	// $table->data[1][0] = print_select ($companies_name, 'search_parent', $search_parent, '', __('Any'), 0, true, false, false, __('Parent'));
	$params = array();
	$params['input_id'] = 'search_parent';
	$params['input_name'] = 'search_parent';
	$params['input_value'] = $search_parent;
	$params['title'] = __('Parent');
	$params['return'] = true;
	$params['attributes'] = 'style="width:210px;"';
	
	$table->data[1][0] = print_company_autocomplete_input($params);

	$table->data[1][1] = print_input_text ('search_date_begin', $search_date_begin, '', 15, 20, true, __('Date from'));
	$table->data[1][2] = print_input_text ('search_date_end', $search_date_end, '', 15, 20, true, __('Date to'));
	$table->data[1][3] = print_input_text ('search_min_billing', $search_min_billing, '', 15, 20, true, __('Min. billing'));
	
	$form = '<form method="post" id="company_stats_form" action="index.php?sec=customers&sec2=operation/companies/company_detail">';
	$form .= '<div class="form_result">';
		$form .= "<div class='divresult_left' >";
			$form .= print_table ($table,true);
		$form .= "</div>";
		$form .= "<div class='divform_right' >";
			$form .= "<div class='button-form'>";
				// Input hidden for ORDER
				$form .= print_input_hidden ('order_by_activity', $order_by_activity,true);
				$form .= print_input_hidden ('order_by_company', $order_by_company,true);
				$form .= print_input_hidden ('order_by_billing', $order_by_billing,true);
				$form .=  print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
			$form .= "</div>";
		$form .= "</div>";
	$form .= '</div>';
	$form .= '</form>';
	
	
	print_container_div("companys_form",__("Companies form search"),$form, 'open', false, false);
	
	if(!isset($having)){
		$having = '';
	}
	$companies = crm_get_companies_list($where_clause, $date, $order_by, false, $having);
	
	if ($companies !== false && count($companies) != 0) {
		$companies = print_array_pagination ($companies, "index.php?sec=customers&sec2=operation/companies/company_detail$search_params", $offset);
		
		$table = new StdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->style = array ();
		$table->colspan = array ();
		$table->head[0] = __('ID');
		$table->head[1] = __('Company') . $company_order_image;
		$table->head[2] = __('Role');
		$table->head[3] = __('Contracts');
		$table->head[4] = __('Leads');
		$table->head[5] = __('Manager');
		$table->head[6] = __('Country');
		$table->head[7] = __('Last activity') . $activity_order_image;
		$table->head[8] = __('Billing') . $billing_order_image;
		$table->head[9] = __('Delete');
		
		foreach ($companies as $company) {		

			$data = array ();
			
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".
				$company["id"]."'>".$company["id"]."</a>";
			$data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".
				$company["id"]."'>".$company["name"]."</a>";
			//~ $data[2] = get_db_value ('name', 'tcompany_role', 'id', $company["id_company_role"]);
			//~ if ($data[2]) {
				//~ $data[2] = "<small>".$data[1]."</small>";
			//~ } else {
				//~ $data[2] = "";
			//~ }
			
			$role_name = get_db_value ('name', 'tcompany_role', 'id', $company["id_company_role"]);
			if ($role_name) {
				$data[2] = "<small>".$role_name."</small>";
			} else {
				$data[2] = "";
			}
			
			$sum_contratos = get_db_sql ("SELECT COUNT(id) FROM tcontract WHERE id_company = ".$company["id"]);
			if ($sum_contratos > 0) {
				$data[3] = "<a title='($sum_contratos)' href='index.php?sec=customers&sec2=operation/companies/company_detail&op=contracts&id=".
					$company['id']."'><img src='images/invoice.png'></a>";
			} else {
				$data[3] = "";
			}
			
			$sum_leads = get_db_sql ("SELECT COUNT(id) FROM tlead WHERE progress < 100 AND id_company = ".$company["id"]);
			if ($sum_leads > 0) {
				$leads_data = " ($sum_leads) ";
				$leads_data .= get_db_sql ("SELECT SUM(estimated_sale) FROM tlead WHERE progress < 100 AND id_company = ".$company["id"]);
				$data[4] = "<a title='$leads_data' href='index.php?sec=customers&sec2=operation/companies/company_detail&op=leads&id=".$company["id"]."'><img src='images/icon_lead.png'></a>";
			} else {
				$data[4] = "";
			}

			$data[5] = $company["manager"];
			$data[6] = $company["country"];
			
			// get last activity date for this company record
			$last_activity = get_db_sql ("SELECT MAX(date) FROM tcompany_activity WHERE id_company = ". $company["id"]);

			$data[7] = human_time_comparation ($last_activity);

			if (!$company["billing"]) {
				$company["billing"] = '0.00';
			}
			$data[8] = $company["billing"];// . " " . $config["currency"];

			$manage_permission = check_crm_acl ('company', 'cm', $config['id_user'], $company['id']);
			if ($manage_permission) {
				$data[9] ="<a href='#' onClick='javascript: show_validation_delete(\"delete_company\",".$company['id'].",0,".$offset.",\"".$search_params."\");'><img src='images/cross.png'></a>";
			} else {
				$data[9] = '';
			}
			
			array_push ($table->data, $data);
		}

		print_table ($table);
	} else {
		echo ui_print_error_message (__("There are not results for the search"), '', true, 'h3', true);
	}
	if(!isset($write_permission)){
		$write_permission = '';
	}
	if ($write_permission || $manage_permission) {
		echo '<form id="form-company_detail" method="post" action="index.php?sec=customers&sec2=operation/companies/company_detail">';
		echo "<div class='button-form'>";
				$button = print_submit_button (__('Create'), "create_btn", false, 'class="sub upd"', true);
				$button .= print_input_hidden ('new_company', 1, true);
			echo $button;
		echo "</div>";
		echo '</form>';
	}
}

echo "<div class= 'dialog ui-dialog-content' id='company_search_window'></div>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
?>

<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_crm.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/jquery-ui-timepicker-addon.js"></script>
<link rel="stylesheet" type="text/css" href="include/js/jquery-ui-timepicker-addon.css"/ >

<script type="text/javascript" >
	
add_ranged_datepicker ("#text-search_date_begin", "#text-search_date_end", null);
add_complete_datepicker ("#text-last_update");

function add_complete_datepicker (element_picker, startDate_picker) {
	$(document).ready(function () {
		$(element_picker).datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "hh:mm:ss",
			minDate: startDate_picker
		});
	});
}

add_datepicker ("input[type=date]");

$(document).ready (function () {
	$("#textarea-description").TextAreaResizer ();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	if (<?php echo json_encode($id) ?> <= 0 || <?php echo json_encode($disabled_write) ?> == false) {
		bindAutocomplete ('#text-user', idUser);
		bindCompanyAutocomplete ('search_parent', idUser);
	}
	
	// Form validation
	trim_element_on_submit('#text-search_text');
	trim_element_on_submit('#text-name');
	trim_element_on_submit('#text-fiscal_id');
	trim_element_on_submit('input[type=number]');
	validate_form("#form-company_detail");
	
	// id_user
	if ($("#company_stats_form").length > 0) {
		validate_user ("#company_stats_form", "#text-user", "<?php echo __('Invalid user')?>");
	} else if ("#form-company_detail") {
		validate_user ("#form-company_detail", "#text-user", "<?php echo __('Invalid user')?>");
	}
	
	var rules, messages;
	
	//Rules: input type number
	rules = {
		required: true,
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_input_number: 1,
				input_number: function() { return $('input[type = number]').val() }
			}
		}
	};
	messages = {
		required: "<?php echo __('Please enter numbers')?>"
	};
	add_validate_form_element_rules('input[type = number]', rules, messages);

	// Rules: #text-name
	rules = {
		required: true,
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_existing_company: 1,
				company_name: function() { return $('#text-name').val() },
				company_id: "<?php echo $id?>"
			}
		}
	};
	messages = {
		required: "<?php echo __('Name required')?>",
		remote: "<?php echo __('This company already exists')?>"
	};
	add_validate_form_element_rules('#text-name', rules, messages);
	// Rules: #text-fiscal_id
	rules = {
		//required: true,
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_existing_fiscal_id: 1,
				fiscal_id: function() { return $('#text-fiscal_id').val() },
				company_id: "<?php echo $id?>"
			}
		}
	};
	messages = {
		//required: "<?php echo __('Fiscal ID required')?>",
		remote: "<?php echo __('This fiscal id already exists')?>"
	};
	add_validate_form_element_rules('#text-fiscal_id', rules, messages);
	
	hide_all_rows();
	
});

function changeAction () {
	
	var f = document.forms.company_stats_form;

	f.action = "index.php?sec=customers&sec2=operation/companies/company_statistics";
	$("#company_stats_form").submit();
}

function changeActivityOrder (order) {
	$("#hidden-order_by_activity").val(order);
	$("#hidden-order_by_company").val('');
	$("#hidden-order_by_billing").val('');
	$("#company_stats_form").submit();
}

function changeCompanyOrder (order) {
	$("#hidden-order_by_company").val(order);
	$("#hidden-order_by_activity").val('');
	$("#hidden-order_by_billing").val('');
	$("#company_stats_form").submit();
}

function changeBillingOrder (order) {
	$("#hidden-order_by_billing").val(order);
	$("#hidden-order_by_activity").val('');
	$("#hidden-order_by_company").val('');
	$("#company_stats_form").submit();
}

function clearParent () {
	$("#hidden-id_parent").val(0);
	$("#text-parent_name").val('<?php echo __('None') ?>');
}

function hide_all_rows() {
	$("tr[class$='-project']").hide();
}

function show_detail(id_project) {
	if ($("tr[class='"+id_project+"-project']").css('display') != "none") {
		$("tr[class='"+id_project+"-project']").hide();
	} else {
		$("tr[class='"+id_project+"-project']").show();
	}
} 
</script>
