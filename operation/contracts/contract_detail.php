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

include_once('include/functions_crm.php');

if (defined ('AJAX')) {
	
	global $config;
	
	$upload_file = (bool) get_parameter('upload_file');
	$get_data_child = (bool) get_parameter('get_data_child', 0);
	
	if ($upload_file) {
		$result = array();
		$result["status"] = false;
		$result["filename"] = "";
		$result["location"] = "";
		$result["message"] = "";

		$upload_status = getFileUploadStatus("upfile");
		$upload_result = translateFileUploadStatus($upload_status);

		if ($upload_result === true) {
			$filename = $_FILES["upfile"]['name'];
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$invalid_extensions = "/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|
				pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
				htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i";
			
			if (!preg_match($invalid_extensions, $extension)) {
				$result["status"] = true;
				$result["location"] = $_FILES["upfile"]['tmp_name'];
				// Replace conflictive characters
				$filename = str_replace (" ", "_", $filename);
				$filename = filter_var($filename, FILTER_SANITIZE_URL);
				$result["name"] = $filename;

				$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$result["name"];

				if (copy($result["location"], $destination))
					$result["location"] = $destination;
			} else {
				$result["message"] = __('Invalid extension');
			}
		} else {
			$result["message"] = $upload_result;
		}
		echo json_encode($result);
		return;
	}

	$remove_tmp_file = (bool) get_parameter('remove_tmp_file');
	if ($remove_tmp_file) {
		$result = false;
		$tmp_file_location = (string) get_parameter('location');
		if ($tmp_file_location) {
			$result = unlink($tmp_file_location);
		}
		echo json_encode($result);
		return;
	}
	
 	if ($get_data_child) {

		$id_field = get_parameter('id_field', 0);
		if ($id_field) {
			$label_field = get_db_value_sql("SELECT label FROM tcontract_field WHERE id=".$id_field);
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

		$sql = "SELECT linked_value FROM tcontract_field WHERE parent=".$id_parent."
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

		$sql_id = "SELECT id FROM tcontract_field WHERE parent=".$id_parent."
					AND label='".$label_field."'";
		$result['id'] = get_db_value_sql($sql_id);
		$result['label'] = $label_field;
		$result['label_enco'] = base64_encode($label_field);
				
		$sql_labels = "SELECT label, id FROM tcontract_field WHERE parent=".$result['id'];

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
$id_contract = (int) get_parameter ('id_contract');
$id_company = (int) get_parameter ('id_company');

$section_read_permission = check_crm_acl ('contract', 'cr');
$section_write_permission = check_crm_acl ('contract', 'cw');
$section_manage_permission = check_crm_acl ('contract', 'cm');

$read_invoice = check_crm_acl ('company', 'cr');
$write_invoice = check_crm_acl ('company', 'cw');
$manage_invoice = check_crm_acl ('company', 'cm');

if (!$section_read_permission && !$section_write_permission && !$section_manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to the contracts section");
	include ("general/noaccess.php");
	exit;
}

$message = get_parameter('message', '');

if ($message != '') {
	echo ui_print_success_message (__($message), '', true, 'h3', true);
}

$contract_fields = get_db_all_rows_sql ("SELECT * FROM tcontract_field");
 
// Show tabs for a given contract
if ($id_contract) {
	
	$id_company = get_db_value('id_company', 'tinvoice', 'id', $id_contract);
	$contract_number = get_db_value('contract_number', 'tinvoice', 'id', $id_contract);

	$contract_data = get_db_row('tcontract', 'id', $id_contract);	
	$op = get_parameter ("op", "");
	echo "<h2>".__('Contract Management')."</h2>";
	echo '<h4>';
	switch ($op) {
		case "invoices":
			echo __('Invoices');
			break;
		default:
			echo __('Update Contract');
	}
	echo integria_help ("contract_detail", true);
	echo '<ul style="height: 30px;" class="ui-tabs-nav">';
	
	if ($op == "invoices")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';

	echo '<a href="index.php?sec=customers&sec2=operation/contracts/contract_detail&id='.$id_contract.'&id_contract='.$id_contract.'&id_company='.$id_company.'&contract_number='.$contract_number.'&op=invoices" title="'.__("Invoices").'"><img src="images/invoice_dark.png"/></a></li>';
	
	if ($op == "")
		echo '<li class="ui-tabs-selected">';
	else
		echo '<li class="ui-tabs">';

	echo '<a href="index.php?sec=customers&sec2=operation/contracts/contract_detail&id_contract='.$id_contract.'" title="'.__("Contract management").'"><img src="images/inventory_tab.png"/></a></li>';
	
		
	echo '</ul>';
	echo '</h4>';
	$message = get_parameter('message', '');
	if ($message != '') {
		echo ui_print_success_message (__($message), '', true, 'h3', true);
	}
}


$op = get_parameter ("op", "");

if ($op == "invoices") {

	$permission = check_crm_acl ('invoice', '', $config['id_user'], $id);
	$view_invoice = get_parameter("view_invoice", 0);
	$id_company = get_parameter('id_company');
	$id = get_parameter('id');
	$contract_data = get_db_row('tcontract', 'id', $id);

	if ((!$permission)) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to an invoice");
		include ("general/noaccess.php");
		exit;
	}

	if ($view_invoice) {
		$id_invoice = get_parameter('id_invoice', -1);
		$id = get_parameter('id_company');
		include ("operation/invoices/invoice_view.php");
	} else {
		$invoices = crm_get_all_invoices ("contract_number = "."'".$contract_data['contract_number']."'");
		$invoices = print_array_pagination ($invoices, "index.php?sec=customers&sec2=operation/contracts/contract_detail&id=$id&op=invoices&invoice_contract_number=".$contract_data['contract_number']);
		
		if ($invoices !== false) {
		
			$table->width = "98%";
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
			$table->head[2] = __('Amount');
			$table->head[3] = __('Type');
			$table->head[4] = __('Status');
			$table->head[5] = __('Creation');
			$table->head[6] = __('Expiration');
			
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
			
				$url = "index.php?sec=customers&sec2=operation/contracts/contract_detail&view_invoice=1&id_contract=".$id."&id=".$invoice['id_company']."&op=invoices&id_invoice=". $invoice["id"];

				$data[0] = "<a href='$url'>".$invoice["bill_id"]."</a>";

				$data[2] = format_numeric(get_invoice_amount ($invoice["id"])) ." ". strtoupper ($invoice["currency"]);

				$tax = get_invoice_tax_sum ($invoice["id"]);
				$tax_amount = get_invoice_amount ($invoice["id"]) * (1 + $tax/100);

				if ($tax != 0)
					$data[2] .= print_help_tip (__("With taxes"). ": ". format_numeric($tax_amount), true);
				
				$data[3] = __($invoice["invoice_type"]);
				$data[4] = __($invoice["status"]);
				$data[5] = "<span style='font-size: 10px'>".$invoice["invoice_create_date"]. "</span>";
				$data[6] = "<span style='font-size: 10px'>".$invoice["invoice_expiration_date"]. "</span>";
				
				array_push ($table->data, $data);
			}	
			print_table ($table);
			
		}
	}
	 
} 
elseif ($op == "") {

	$id = get_parameter('id_contract');
	
	if ($id || $id_company) {
	
		if ($id && !$id_company) {
			$id_company = get_db_value('id_company', 'tcontract', 'id', $id);
		}
		
		if ($id) {
			$read_permission = check_crm_acl ('contract', 'cr', $config['id_user'], $id);
			$write_permission = check_crm_acl ('contract', 'cw', $config['id_user'], $id);
			$manage_permission = check_crm_acl ('contract', 'cm', $config['id_user'], $id);
			if (!$read_permission && !$write_permission && !$manage_permission) {
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a contract");
				include ("general/noaccess.php");
				exit;
			}
		} elseif ($id_company) {
			$read_permission = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
			$write_permission = check_crm_acl ('other', 'cw', $config['id_user'], $id_company);
			$manage_permission = check_crm_acl ('other', 'cm', $config['id_user'], $id_company);
			if (!$read_permission && !$write_permission && !$manage_permission) {
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a contract");
				include ("general/noaccess.php");
				exit;
			}
		}
	}

	$get_sla = (bool) get_parameter ('get_sla');
	$get_company_name = (bool) get_parameter ('get_company_name');
	$new_contract = (bool) get_parameter ('new_contract');
	$create_contract = (bool) get_parameter ('create_contract');
	$update_contract = (bool) get_parameter ('update_contract');
	$delete_contract = (bool) get_parameter ('delete_contract');

	// Delete file
	$delete_file = (bool) get_parameter ('delete_file');
	if ($delete_file) {
		if ($manage_permission) {
			$id_attachment = get_parameter ('id_attachment');
			$filename = get_db_value ('filename', 'tattachment',
				'id_attachment', $id_attachment);
			$sql = sprintf ('DELETE FROM tattachment WHERE id_attachment = %d',
				$id_attachment);
			process_sql ($sql);
			$result_msg = ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
			if (!unlink ($config["homedir"].'attachment/'.$id_attachment.'_'.$filename))
				$result_msg = ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
		} else {
			$result_msg = ui_print_error_message (__('You have no permission'), '', true, 'h3', true);
		}
		
		echo $result_msg;
	}

	if ($get_sla) {
		$sla = get_contract_sla ($id, false);
		
		if (defined ('AJAX')) {
			echo json_encode ($sla);
			return;
		}
	}

	if ($get_company_name) {
		$company = get_contract_company ($id, true);

		if (defined ('AJAX')) {
			echo json_encode (reset($company));
			return;
		}
	}

	// CREATE
	if ($create_contract) {

		if (!$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a contract");
			require ("general/noaccess.php");
			exit;
		}

		$name = (string) get_parameter ('name');
		$contract_number = (string) get_parameter ('contract_number');
		$description = (string) get_parameter ('description');
		$date_begin = (string) get_parameter ('date_begin');
		$date_end = (string) get_parameter ('date_end');
		$private = (int) get_parameter ('private');
		$status = (int) get_parameter ('status', 1);
		$upfiles = (string) get_parameter('upfiles');
		
		$bill_id_exists = get_db_value('contract_number', 'tcontract', 'contract_number', $contract_number);
		if (!$bill_id_exists) {
			$sql = sprintf ('INSERT INTO tcontract (name, contract_number, description, date_begin,
				date_end, id_company, private, status)
				VALUE ("%s", "%s", "%s", "%s", "%s", %d, %d, %d)',
				$name, $contract_number, $description, $date_begin, $date_end,
				$id_company, $private, $status);

			$id = process_sql ($sql, 'insert_id');
			if ($id === false)
				echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
			else {
				
				foreach ($contract_fields as $u) {

					$custom_value = get_parameter("custom_".$u["id"]);
			
					$sql = sprintf('INSERT INTO tcontract_field_data (`data`, `id_contract`,`id_contract_field`) VALUES ("%s", "%s", %d)',
								$custom_value, $id, $u["id"]);
					$res = process_sql($sql);

					if ($res === false) {
						echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
					}
				}
				
				//update last activity
				$datetime =  date ("Y-m-d H:i:s");
				$comments = __("Created contract by ".$config['id_user']);
				$sql_add = sprintf ('INSERT INTO tcompany_activity (id_company, written_by, date, description) VALUES (%d, "%s", "%s", "%s")', $id_company, $config["id_user"], $datetime, $comments);
				process_sql ($sql_add);
				$sql_activity = sprintf ('UPDATE tcompany SET last_update = "%s" WHERE id = %d', $datetime, $id_company);
				$result_activity = process_sql ($sql_activity);
				
				// ATTACH A FILE IF IS PROVIDED
				$upfiles = json_decode(safe_output($upfiles), true);

				if (!empty($upfiles)) {
					foreach ($upfiles as $file) {
						if (is_array($file)) {
							if ($file['description']) {
								$file_description = $file['description'];
							} else {
								$file_description = __('No description available');
							}
							$file_result = crm_attach_contract_file ($id, $file["location"], $file_description, $file["name"]);
							
							$file_tmp = sys_get_temp_dir().'/'.$file["name"];
							$size = filesize ($file_tmp);
							$filename_encoded = $file_result . "_" . $file["name"];
						
							// Copy file to directory and change name
							$file_target = $config["homedir"]."/attachment/".$filename_encoded;
					
							if (!(copy($file_tmp, $file_target))){
								echo ui_print_error_message (__("Could not be attached"), '', true, 'h3', true);
							} else {
								// Delete temporal file
								echo ui_print_success_message (__("Successfully attached"), '', true, 'h3', true);
								$location = $file_target;
								unlink ($file_tmp);
							}
						}
					}
				}
				echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
				audit_db ($config['id_user'], $REMOTE_ADDR, "Contract created", "Contract named '$name' has been added");
			}
		} else {
			echo ui_print_error_message (__("This contract number already exists"), '', true, 'h3', true);
		}	
	$id = 0;
	}

	// UPDATE
	if ($update_contract) { // if modified any parameter
	$id = get_parameter('id_contract');	
		if (!$write_permission && !$manage_permission) {
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a contract");
			require ("general/noaccess.php");
			exit;
		}

		$name = (string) get_parameter ('name');
		$contract_number = (string) get_parameter ('contract_number');
		$description = (string) get_parameter ('description');
		$date_begin = (string) get_parameter ('date_begin');
		$date_end = (string) get_parameter ('date_end');
		$private = (int) get_parameter ('private');
		$status = (int) get_parameter ('status');
		$upfiles = (string) get_parameter('upfiles');
		$id_company = (int) get_parameter('id_company');


		$sql = sprintf ('UPDATE tcontract SET contract_number = "%s",
			description = "%s", name = "%s", date_begin = "%s",
			date_end = "%s", id_company = %d, private = %d, status = %d
			WHERE id = %d',
			$contract_number, $description, $name, $date_begin,
			$date_end, $id_company, $private, $status, $id);
		
		$result = process_sql ($sql);
		if ($result === false) {
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		} else {
			
			foreach ($contract_fields as $u) {

				$custom_value = get_parameter("custom_".$u["id"]);
				
				$sql = sprintf('SELECT data FROM tcontract_field_data WHERE id_contract = "%s" AND id_contract_field = %d',
								$id, $u["id"]);
				
				$current_data = process_sql($sql);
				
				if ($current_data) {
					$sql = sprintf('UPDATE tcontract_field_data SET data = "%s" WHERE id_contract = "%s" AND id_contract_field = %d',
							$custom_value, $id, $u["id"]);
				} else {
					$sql = sprintf('INSERT INTO tcontract_field_data (`data`, `id_contract`,`id_contract_field`) VALUES ("%s", "%s", %d)',
							$custom_value, $id, $u["id"]);
				}

				$res = process_sql($sql);

				if ($res === false) {
					echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
				}
			}
				
			//update last activity
			$datetime =  date ("Y-m-d H:i:s");
			$comments = __("Update contract ".$id. " by ".$config['id_user']);
			$sql_add = sprintf ('INSERT INTO tcompany_activity (id_company, written_by, date, description) VALUES (%d, "%s", "%s", "%s")', $id_company, $config["id_user"], $datetime, $comments);
			process_sql ($sql_add);
			$sql_activity = sprintf ('UPDATE tcompany SET last_update = "%s" WHERE id = %d', $datetime, $id_company);
			$result_activity = process_sql ($sql_activity);
			
		
			// ATTACH A FILE IF IS PROVIDED
			$upfiles = json_decode(safe_output($upfiles), true);
			if (!empty($upfiles)) {
				foreach ($upfiles as $file) {

					if (is_array($file)) {
						if ($file['description']) {
							$file_description = $file['description'];
						} else {
							$file_description = __('No description available');
						}
						$file_result = crm_attach_contract_file ($id, $file["location"], $file_description, $file["name"]);
						
						$file_tmp = sys_get_temp_dir().'/'.$file["name"];
						$size = filesize ($file_tmp);
						$filename_encoded = $file_result . "_" . $file["name"];
					
						// Copy file to directory and change name
						$file_target = $config["homedir"]."/attachment/".$filename_encoded;
				
						if (!(copy($file_tmp, $file_target))){
							echo ui_print_error_message (__("Could not be attached"), '', true, 'h3', true);
						} else {
							// Delete temporal file
							echo ui_print_success_message (__("Successfully attached"), '', true, 'h3', true);
							$location = $file_target;
							unlink ($file_tmp);
						}
					}
				}
			}

			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			audit_db ($config['id_user'], $REMOTE_ADDR, "Contract updated", "Contract named '$name' has been updated");
		}

		$id = 0;
	}


	// FORM (Update / Create)
	if ($id || $new_contract) {
		if ($new_contract) {
			echo "<h2>".__('Contract Management')."</h2>";
			echo '<h4>' . __('New contract');
			echo integria_help ("contract_detail", true);
			echo "<div id='button-bar-title'>";
				echo "<ul>";
					echo "<li><a href='index.php?sec=customers&sec2=operation/contracts/contract_detail'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
				echo "</ul>";
			echo "</div>";
			echo "</h4>";
			if (!$section_write_permission && !$section_manage_permission) {
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a contract");
				require ("general/noaccess.php");
				exit;
			}
			
			$name = "";
			$contract_number = "";
			$date_begin = date('Y-m-d');
			$date_end = $date_begin;
			$id_sla = "";
			$description = "";
			$private = 0;
			$status = 1;
		}
		else {
			
			if (!$read_permission && !$write_permission && !$manage_permission) {
				audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update a contract");
				require ("general/noaccess.php");
				exit;
			}
			
			$contract = get_db_row ("tcontract", "id", $id);
			$name = $contract["name"];
			$contract_number = $contract["contract_number"];
			$id_company = $contract["id_company"];
			$date_begin = $contract["date_begin"];
			$date_end   = $contract["date_end"];
			$description = $contract["description"];
			$id_sla = $contract["id_sla"];
			$private = $contract["private"];
			$status = $contract["status"];
		}
		
		$table->width = '100%';
		$table->class = 'search-table-button';
		$table->colspan = array ();
		$table->data = array ();
		
		if ($new_contract || ($id && ($write_permission || $manage_permission))) {
			
			$table->class = 'search-table-button';
			
			$params = array();
			$params['input_id'] = 'id_company';
			$params['input_name'] = 'id_company';
			$params['input_value'] = $id_company;
			$params['title'] = __('Company');
			$params['return'] = true;
			$table->data[0][0] = print_company_autocomplete_input($params);

			$table->data[0][1] = print_input_text ('name', $name, '', 40, 100, true, __('Contract name'));
			$table->data[1][0] = print_input_text ('contract_number', $contract_number, '', 40, 100, true, __('Contract number'));
			$table->data[1][1] = print_checkbox ('private', '1', $private, true, __('Private'). print_help_tip (__("Private contracts are visible only by users of the same company"), true));		
				
			$table->data[2][0] = print_input_text ('date_begin', $date_begin, '', 15, 20, true, __('Begin date'));
			$table->data[2][1] = print_input_text ('date_end', $date_end, '', 15, 20, true, __('End date'));
			
			if ($id_company) {
				$table->data[3][0] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
				$table->data[3][0] .= "<img src='images/company.png'></a>";
			}
			
			$table->data[3][1] = print_select (get_contract_status(), 'status', $status, '', '', '', true, 0, false,  __('Status'));


			$column=4;
			$row=0;

			if($contract_fields){
				foreach ($contract_fields as $comp) {
					
					$data = get_parameter('custom_'.$comp["id"]);
					
					if(!$data){
						$sql_data = sprintf('SELECT data FROM tcontract_field_data WHERE id_contract = "%s" AND id_contract_field = %d', $id, $comp["id"]);
						
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
								$has_parent = get_db_value_sql("SELECT parent FROM tcontract_field WHERE id=".$comp['id']);
								if ($has_parent) {
									$parent_value = get_db_value_sql("SELECT `data` FROM tcontract_field_data WHERE id_contract =".$id." AND id_contract_field =".$has_parent);

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
								
								$has_childs = get_db_all_rows_sql("SELECT * FROM tcontract_field WHERE parent=".$comp['id']);

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

									$script = 'javascript:change_linked_type_fields_table_contract('.$childs.','.$comp['id'].');';
								} else {
									$script = '';
								}
								
							} else {
								$values = array();
								foreach ($linked_values as $value) {
									$value_without_parent =  preg_replace("/^.*\|/","", $value);

									$values[$value_without_parent] = $value_without_parent;
									$has_childs = get_db_all_rows_sql("SELECT * FROM tcontract_field WHERE parent=".$comp['id']);

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

										$script = 'javascript:change_linked_type_fields_table_contract('.$childs.','.$comp['id'].');';
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
		
		$table->colspan[$column][0] = 2;


			$table->data[$column][0] = print_textarea ("description", 14, 1, $description, '', true, __('Description'));
			
			// Optional file update
			$html = "";
			$html .= "<div id=\"contract_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
			$html .= 	"<div id=\"drop_file\" style=\"padding:0px 0px;\">";
			$html .= 		"<table width=\"100%\">";
			$html .= 			"<td width=\"45%\">";
			$html .= 				__('Drop the file here');
			$html .= 			"<td>";
			$html .= 				__('or');
			$html .= 			"<td width=\"45%\">";
			$html .= 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
			$html .= 			"<tr>";
			$html .= 		"</table>";
			$html .= 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
			$html .= 		"<input type=\"hidden\" name=\"upfiles\" id=\"upfiles\" />"; // JSON STRING
			$html .= 	"</div>";
			$html .= 	"<ul></ul>";
			$html .= "</div>";

			$table_description = new stdClass;
			$table_description->width = '100%';
			$table_description->id = 'contract_file_description';
			$table_description->class = 'search-table-button';
			$table_description->data = array();
			$table_description->data[0][0] = print_textarea ("file_description", 3, 40, '', '', true, __('Description'));
			//$table_description->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
			$html .= "<div id='contract_file_description_table_hook' style='display:none;'>";
			$html .= print_table($table_description, true);
			$html .= "</div>";
			
			$column++;
			
			$table->colspan[$column][0] = 4;
			$table->data[$column][0] = print_container_div('file_upload_container', __('File upload'), $html, 'closed', true, true);
			
			$button = "<div class='button-form' style='width:100%; text-align:right;'>";
			if ($id) {
				$button .= print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
				$button .= print_input_hidden ('id_contract', $id, true);
				$button .= print_input_hidden ('update_contract', 1, true);
				
				//$table->data['button'][1] = $button;
				//$table->colspan['button'][1] = 2;
			} else {
				$button .= print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
				$button .= print_input_hidden ('create_contract', 1, true);
				
				//$table->data['button'][1] = $button;
				//$table->colspan['button'][1] = 2;
			}
			$button .= "</div>";
		}
		else {
			
			$table->class = 'search-table';

			$table->data[0][0] = "<b>".__('Contract name')."</b><br>$name<br>";
			if($contract_number == '') {
				$contract_number = '<i>-'.__('Empty').'-</i>';
			}		
			$table->data[1][0] = "<b>".__('Contract number')."</b><br>$contract_number<br>";
			
			$table->data[1][1] = "<b>".__('Status')."</b><br>".get_contract_status_name($status)."<br>";
			
			$table->data[2][0] = "<b>".__('Begin date')."</b><br>$date_begin<br>";
			$table->data[2][1] = "<b>".__('End date')."</b><br>$date_end<br>";
			
			$company_name = get_db_value('name','tcompany','id',$id_company);
			
			$table->data[3][0] = "<b>".__('Company')."</b><br>$company_name";
			
			$table->data[3][0] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
			$table->data[3][0] .= "<img src='images/company.png'></a>";
			
			$sla_name = get_db_value('name','tsla','id',$id_sla);
			
			$table->data[3][1] = "<b>".__('SLA')."</b><br>$sla_name<br>";
			if($description == '') {
				$description = '<i>-'.__('Empty').'-</i>';
			}		
			$table->data[3][1] = "<b>".__('Description')."</b><br>$description<br>";
		}
		
		echo '<form id="contract_form" method="post" action="index.php?sec=customers&sec2=operation/contracts/contract_detail">';
		print_table ($table);
		
			echo $button;
		echo "</form>";
		
		if ($id && ($write_permission || $manage_permission)) {
			//File list		
			echo "<h2>".__('Files')."</h2>";

			// Files attached to this contract
			$files = crm_get_contract_files ($id);
			if ($files === false) {
				$files = array();
				echo '<h4 id="no_files_message">'.__('No files were added to the contract').'</h4>';
				$hidden = "style=\"display:none;\"";
			}

			echo "<div style='width: 98%; margin: 0 auto;'>";
			echo "<table id='table-incident_files' $hidden class=listing cellpadding=0 cellspacing=0 width='100%'>";
			echo "<tr>";
			echo "<th>".__('Filename');
			echo "<th>".__('Timestamp');
			echo "<th>".__('Description');
			echo "<th>".__('ID user');
			echo "<th>".__('Size');

			if ($manage_permission) {
				echo "<th>".__('Delete');
			}

			foreach ($files as $file) {

				$link = "operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=contract";

				$real_filename = $config["homedir"]."/attachment/".$file["id_attachment"]."_".rawurlencode ($file["filename"]);    

				echo "<tr>";
				echo "<td valign=top>";
				echo '<a target="_blank" href="'.$link.'">'. $file['filename'].'</a>';
				echo "<td valign=top class=f9>".$file['timestamp'];
				echo "<td valign=top class=f9>". $file["description"];
				echo "<td valign=top>". $file["id_usuario"];
				echo "<td valign=top>". byte_convert ($file['size']);

				// Delete attachment
				if ($manage_permission) {
					echo "<td>". '<a class="delete" name="delete_file_'.$file["id_attachment"].'" href="index.php?sec=customers&sec2=operation/contracts/contract_detail&id_contract='.$id.'&id_attachment='.$file["id_attachment"].'&delete_file=1">
					<img src="images/cross.png"></a>';
				}
			}

			echo "</table>";
			echo "</div>";
		}
	}
	else {
				
		// Contract listing
		$search_text = (string) get_parameter ('search_text');
		$search_company_role = (int) get_parameter ('search_company_role');
		$search_date_end = get_parameter ('search_date_end');
		$search_date_begin = get_parameter ('search_date_begin');
		$search_date_begin_beginning = get_parameter ('search_date_begin_beginning');
		$search_date_end_beginning = get_parameter ('search_date_end_beginning');
		$search_status = (int) get_parameter ('search_status', 1);
		$search_expire_days = (int) get_parameter ('search_expire_days');

		$search_params = "search_text=$search_text&search_company_role=$search_company_role&search_date_end=$search_date_end&search_date_begin=$search_date_begin&search_date_begin_beginning=$search_date_begin_beginning&search_date_end_beginning=$search_date_end_beginning&search_status=$search_status&search_expire_days=$search_expire_days";
		
		$where_clause = "";
		
		if ($search_text != "") {
			$where_clause .= sprintf (' AND (tc.id_company IN (SELECT id FROM tcompany WHERE name LIKE "%%%s%%") OR tc.name LIKE "%%%s%%" OR tc.contract_number LIKE "%%%s%%" OR tc.id IN (SELECT id_contract FROM tcontract_field_data WHERE data LIKE "%%%s%%"))', $search_text, $search_text, $search_text, $search_text);
		}
		
		if ($search_company_role) {
			$where_clause .= sprintf (' AND tc.id_company IN (SELECT id FROM tcompany WHERE id_company_role = %d)', $search_company_role);
		}
		
		if ($search_date_end != "") {
			$where_clause .= sprintf (' AND tc.date_end <= "%s"', $search_date_end);
		}
		
		if ($search_date_begin != "") {
			$where_clause .= sprintf (' AND tc.date_end >= "%s"', $search_date_begin);
		}
			
		if ($search_date_end_beginning != "") {
			$where_clause .= sprintf (' AND tc.date_begin <= "%s"', $search_date_end_beginning);
		}
		
		if ($search_date_begin_beginning != "") {
			$where_clause .= sprintf (' AND tc.date_begin >= "%s"', $search_date_begin_beginning);
		}
		
		if ($search_status >= 0) {
			$where_clause .= sprintf (' AND tc.status = %d', $search_status);
		}
		
		if ($search_expire_days > 0) {
			// Comment $today_date to show contracts that expired yet
			$today_date = date ("Y/m/d");
			$expire_date = date ("Y/m/d", strtotime ("now") + $search_expire_days * 86400);
			$where_clause .= sprintf (' AND (tc.date_end < "%s" AND tc.date_end > "%s")', $expire_date, $today_date);
		}
		
		echo "<h2>".__('Contracts')."</h2>";
		echo "<h4>".__('List of contracts');
			echo integria_help ("contract_detail", true);
			echo "<div id='button-bar-title'>";
				echo "<ul>";
					echo "<li>";
						// Delete new lines from the string
						$where_clause = str_replace(array("\r", "\n"), '', $where_clause);
						echo print_button(__('Export to CSV'), '', false, 'window.open(\'include/export_csv.php?export_csv_contracts=1&where_clause=' . 
							str_replace('"', "\'", $where_clause) . '\')', 'class="sub csv"', true);
					echo "</li>";
				echo "</ul>";
			echo "</div>";
		echo "</h4>";
		
		
		$form = '<form action="index.php?sec=customers&sec2=operation/contracts/contract_detail" method="post" id="contracts_stats_form">';
		
		$form .= '<div class="form_result">';
		
		$form .= "<div class='divresult_left'>";
		$form .= "<table width=100% class='search-table-button'>";
		$form .= "<tr>";
		
		$form .= "<td colspan=2>";
		$form .= print_input_text ("search_text", $search_text, "", 38, 100, true, __('Search') . print_help_tip (__("Search according to contracts number, name and company"), true));
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_select (get_company_roles(), 'search_company_role',
			$search_company_role, '', __('All'), 0, true, false, false, __('Company roles'));	
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_select (get_contract_status(), 'search_status',
			$search_status, '', __('Any'), -1, true, false, false, __('Status'));	
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_select (get_contract_expire_days(), 'search_expire_days',
			$search_expire_days, '', __('None'), 0, true, false, false, __('Out of date'));	
		$form .= "</td>";
		
		$form .= "</tr>";
		$form .= "<tr>";
		
		$form .= "<td>";
		$form .= print_input_text ('search_date_begin_beginning', $search_date_begin_beginning, '', 15, 20, true, __('Begining From') .
			"<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_input_text ('search_date_end_beginning', $search_date_end_beginning, '', 15, 20, true, __('Begining To').
			"<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_input_text ('search_date_begin', $search_date_begin, '', 15, 20, true, __('Ending From')."<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td>";
		
		$form .= "<td>";
		$form .= print_input_text ('search_date_end', $search_date_end, '', 15, 20, true, __('Ending To').
			"<a href='#' class='tip'><span>". __('Date format is YYYY-MM-DD')."</span></a>");
		$form .= "</td>";
		$form .= "</tr>";
		
		$form .= "</table>";
		$form .= "</div>";
		
		$form .= "<div class='divform_right'>";
			$form .= "<div class='button-form' style='width:100%;'>";
				$form .= print_submit_button (__('Search'), 
					"search_btn", false, 'class="sub search"', true);
			$form .= "</div>";
		$form .= "</div>";
		
		$form .= "</div>";
		
		$form .= '</form>';
		
		print_container_div("contract_form",__("Contracts form search"),$form, 'open', false, false);
		
		$contracts = crm_get_all_contracts_with_custom_fields ($where_clause);
		if ($contracts !== false) {
			
			$contracts = print_array_pagination ($contracts, "index.php?sec=customers&sec2=operation/contracts/contract_detail&$search_params");
			
			$table = new StdClass();
			$table->width = "100%";
			$table->class = "listing";
			$table->cellspacing = 0;
			$table->cellpadding = 0;
			$table->tablealign="left";
			$table->data = array ();
			$table->size = array ();
			$table->style = array ();
			$table->colspan = array ();
			
			$table->head[0] = __('Name');
			$table->head[1] = __('Contract number');
			$table->head[2] = __('Company');
			$table->head[3] = __('Begin');
			$table->head[4] = __('End');
			
			//extraction header 
			$header = array_pop($contracts);
			$i = 5;
			foreach ($header as $key => $value) {
				$table->head[$i] = $value;
				$i++;	
			}

			if ($section_write_permission || $section_manage_permission) {
				$table->head[$i++] = __('Privacy');
				$table->head[$i++] = __('Delete');
			}
			if ($write_invoice || $manage_invoice) {
				$table->head[$i++] = __('Generate invoice');
			}
			
			$counter = 0;
			$i = 5;
			foreach ($contracts as $contract) {
				$data = array ();
				
				$data[0] = "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail&id_contract="
					.$contract["id"]."'>".$contract["name"]."</a>";
				$data[1] = $contract["contract_number"];
				$data[2] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$contract["id_company"]."'>";
				$data[2] .= get_db_value ('name', 'tcompany', 'id', $contract["id_company"]);
				$data[2] .= "</a>";
				
				$data[3] = $contract["date_begin"];
				$data[4] = $contract["date_end"] != '0000-00-00' ? $contract["date_end"] : "-";
				
				foreach ($header as $key => $value) {
					if($contract[$value] != ""){
						$data[$i] = $contract[$value];
					}
					else{
						$data[$i] = "--";
					}
					$i++;
				}
				
				if ($section_write_permission || $section_manage_permission) {
					// Delete
					if($contract["private"]) {
						$data[$i++] = __('Private');
					}
					else {
						$data[$i++] = __('Public');
					}

					$data[$i++] = "<a href='#' onClick='javascript: show_validation_delete(\"delete_contract\",".$contract["id"].",0,0,\"".$search_params."\");'><img src='images/cross.png'></a>";
				}
				if ($write_invoice || $manage_invoice) {
					$data[$i++] = ' <a method="POST" href="index.php?sec=customers&sec2=operation/invoices/invoices
					&generate=1&invoice_contract_number='.$contract["contract_number"].'&company_id='.$contract["id_company"].'" 
					onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">
					<img src="images/invoice.png" title="'.__('Generate invoice').'"></a>';
				}
				array_push ($table->data, $data);
			}
			echo '<div id= "inventory_only_table">';
				print_table ($table);
			echo '</div>';	
			
		} else {
			echo ui_print_error_message (__("There are not results for the search"), '', true, 'h3', true);
		}
		
		if ($section_write_permission || $section_manage_permission) {
			echo '<form method="post" action="index.php?sec=customers&sec2=operation/contracts/contract_detail">';
			echo '<div style="width: '.$table->width.';" class="button-form">';
			print_submit_button (__('Create'), 'new_btn', false, 'class="sub create"');
			print_input_hidden ('new_contract', 1);
			echo '</div>';
			echo '</form>';
		}
	}
		echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";
}

?>

<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="include/js/jquery.iframe-transport.js"></script>
<script type="text/javascript" src="include/js/jquery.knob.js"></script>
<script type="text/javascript" src="include/js/integria_crm.js"></script>

<script type="text/javascript">
	
add_ranged_datepicker ("#text-date_begin", "#text-date_end", null);
add_ranged_datepicker ("#text-search_date_begin_beginning", "#text-search_date_end_beginning", null);
add_ranged_datepicker ("#text-search_date_begin", "#text-search_date_end", null);

$(document).ready (function () {

	var idUser = "<?php echo $config['id_user'] ?>";
	bindCompanyAutocomplete ('id_company', idUser);
	
	$("#id_group").change (function() {
		refresh_company_combo();
	});
	
	if ($("#search_expire_days").val() > 0) {
		disable_dates();
	}
	
	$("#search_expire_days").change (function() {
		if ($("#search_expire_days").val() > 0) {
			disable_dates();
		} else {
			enable_dates();
		}
	});
	
	// Init the file upload
	form_upload();
	
});

function disable_dates () {
	$("#text-search_date_begin_beginning").prop('disabled', true);
	$("#text-search_date_end_beginning").prop('disabled', true);
	$("#text-search_date_begin").prop('disabled', true);
	$("#text-search_date_end").prop('disabled', true);
}

function enable_dates () {
	$("#text-search_date_begin_beginning").prop('disabled', false);
	$("#text-search_date_end_beginning").prop('disabled', false);
	$("#text-search_date_begin").prop('disabled', false);
	$("#text-search_date_end").prop('disabled', false);
}

function toggle_advanced_fields () {
	
	$("#advanced_fields").toggle();
}

function refresh_company_combo () {
	
	var group = $("#id_group").val();
	
	values = Array ();
	values.push ({name: "page",
		value: "operation/contracts/contract_detail"});
	values.push ({name: "group",
		value: group});
	values.push ({name: "get_group_combo",
		value: 1});
	jQuery.get ("ajax.php",
		values,
		function (data, status) {
			$("#id_company").remove();
			$("#label-id_company").after(data);
		},
		"html"
	);

}

function form_upload () {
	// Input will hold the JSON String with the files data
	var input_upfiles = $('input#upfiles');
	// JSON Object will hold the files data
	var upfiles = {};

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#contract_files').fileupload({
		
		url: 'ajax.php?page=operation/contracts/contract_detail&upload_file=true',
		
		// This element will accept file drag/drop uploading
		dropZone: $('#drop_file'),

		// This function is called when a file is added to the queue;
		// either via the browse button, or via drag/drop:
		add: function (e, data) {
			data.context = addListItem(0, data.files[0].name, data.files[0].size);

			// Automatically upload the file once it is added to the queue
			data.context.addClass('working');
			var jqXHR = data.submit();
		},

		progress: function(e, data) {

			// Calculate the completion percentage of the upload
			var progress = parseInt(data.loaded / data.total * 100, 10);

			// Update the hidden input field and trigger a change
			// so that the jQuery knob plugin knows to update the dial
			data.context.find('input').val(progress).change();

			if (progress >= 100) {
				data.context.removeClass('working');
				data.context.removeClass('error');
				data.context.addClass('loading');
			}
		},

		fail: function(e, data) {
			// Something has gone wrong!
			data.context.removeClass('working');
			data.context.removeClass('loading');
			data.context.addClass('error');
		},
		
		done: function (e, data) {
			
			var result = JSON.parse(data.result);
			
			if (result.status) {
				data.context.removeClass('error');
				data.context.removeClass('loading');
				data.context.addClass('working');

				// Increase the counter
				if (upfiles.length == undefined) {
					upfiles.length = 0;
				} else {
					upfiles.length += 1;
				}
				var index = upfiles.length;
				// Create the new element
				upfiles[index] = {};
				upfiles[index].name = result.name;
				upfiles[index].location = result.location;
				// Save the JSON String into the input
				input_upfiles.val(JSON.stringify(upfiles));

				// FORM
				addForm (data.context, index);
				
			} else {
				// Something has gone wrong!
				data.context.removeClass('working');
				data.context.removeClass('loading');
				data.context.addClass('error');
				if (result.message) {
					var info = data.context.find('i');
					info.css('color', 'red');
					info.html(result.message);
				}
			}
		}

	});

	// Prevent the default action when a file is dropped on the window
	$(document).on('drop_file dragover', function (e) {
		e.preventDefault();
	});

	function addListItem (progress, filename, filesize) {
		var tpl = $('<li>'+
						'<input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
						' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" />'+
						'<p></p>'+
						'<span></span>'+
						'<div class="contract_file_form"></div>'+
					'</li>');
		
		// Append the file name and file size
		tpl.find('p').text(filename);
		if (filesize > 0) {
			tpl.find('p').append('<i>' + formatFileSize(filesize) + '</i>');
		}

		// Initialize the knob plugin
		tpl.find('input').val(0);
		tpl.find('input').knob({
			'draw' : function () {
				$(this.i).val(this.cv + '%')
			}
		});

		// Listen for clicks on the cancel icon
		tpl.find('span').click(function() {

			if (tpl.hasClass('working') || tpl.hasClass('error') || tpl.hasClass('suc')) {

				if (tpl.hasClass('working') && typeof jqXHR != 'undefined') {
					jqXHR.abort();
				}

				tpl.fadeOut();
				tpl.slideUp(500, "swing", function() {
					tpl.remove();
				});

			}

		});
		
		// Add the HTML to the UL element
		var item = tpl.appendTo($('#contract_files ul'));
		item.find('input').val(progress).change();

		return item;
	}

	function addForm (item, array_index) {
		
		item.find(".contract_file_form").html($("#contract_file_description_table_hook").html());

		item.find("#submit-crt_btn").click(function(e) {
			e.preventDefault();
			
			$(this).prop('value', "<?php echo __('Update'); ?>");
			$(this).removeClass('create');
			$(this).addClass('upd');

			// Add the description to the array
			upfiles[array_index].description = item.find("#textarea-file_description").val();	
			// Save the JSON String into the input
			input_upfiles.val(JSON.stringify(upfiles));
		});

		// Listen for clicks on the cancel icon
		item.find('span').click(function() {
			// Remove the element from the array
			upfiles[array_index] = {};
			// Save the JSON String into the input
			input_upfiles.val(JSON.stringify(upfiles));
			// Remove the tmp file
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "operation/contracts/contract_detail",
					remove_tmp_file: true,
					location: upfiles[array_index].location
				}
			});
		});

	}

}

// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-name');
trim_element_on_submit('#text-contract_number');
validate_form("#contract_form");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_contract: 1,
			id_company: function() { return $('#id_company').val() },
			contract_name: function() { return $('#text-name').val() },
			contract_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This contract already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);
// Rules: #text-contract_number
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_contract_number: 1,
			contract_number: function() { return $('#text-contract_number').val() },
			contract_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Contract number required')?>",
	remote: "<?php echo __('This contract number already exists')?>"
};
add_validate_form_element_rules('#text-contract_number', rules, messages);

</script>


