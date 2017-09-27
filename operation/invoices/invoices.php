<?PHP
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
check_login ();

include_once('include/functions_crm.php');

$id_company = get_parameter ("id", -1);
$company = get_db_row ('tcompany', 'id', $id_company);
$id_invoice = get_parameter ("id_invoice", -1);
$operation_invoices = get_parameter ("operation_invoices");

$read = check_crm_acl ('company', 'cr');
$write = check_crm_acl ('company', 'cw');
$manage = check_crm_acl ('company', 'cm');
$check_table_hidden = 0;

if ($id_invoice > 0 || $id_company > 0) {
	if ($id_company < 1 && $id_invoice > 0) {
		$id_company = get_db_value ('id_company', 'tinvoice', 'id', $id_invoice);
	}
	if ($id_company > 0) {
		$permission = check_crm_acl ('invoice', '', $config['id_user'], $id_company);
		if (!$permission) {
			include ("general/noaccess.php");
			exit;
		} elseif (!$write && !$manage && $read) {
			include ("operation/invoices/invoice_view.php");
			return;
		}
	} else {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access an invoice");
		include ("general/noaccess.php");
		exit;
	}
	
	if (crm_is_invoice_locked ($id_invoice)) {
		include ("operation/invoices/invoice_view.php");
		return;
	}
}

// Delete file
$deletef = get_parameter ("deletef", "");
if ($deletef != ""){
	$file = get_db_row ("tattachment", "id_attachment", $deletef);
	if ( (dame_admin($config["id_user"])) || ($file["id_usuario"] == $config["id_user"]) ){
		$sql = "DELETE FROM tattachment WHERE id_attachment = $deletef";
		process_sql ($sql);	
		$filename = $config["homedir"]."/attachment/". $file["id_attachment"]. "_" . $file["filename"];
		unlink ($filename);
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	}
}

if ($operation_invoices == "update_invoice"){
	
	$filename = get_parameter ('upfile', false);
	$reference = get_parameter ("reference", "");
	$bill_id = get_parameter ("bill_id", "");
	$description = get_parameter ("description", "");
	$concept = array();
	$concept[0] = get_parameter ("concept1", "");
	$concept[1] = get_parameter ("concept2", "");
	$concept[2] = get_parameter ("concept3", "");
	$concept[3] = get_parameter ("concept4", "");
	$concept[4] = get_parameter ("concept5", "");
	$amount = array();
	$amount[0] = (float) get_parameter ("amount1", 0);
	$amount[1] = (float) get_parameter ("amount2", 0);
	$amount[2] = (float) get_parameter ("amount3", 0);
	$amount[3] = (float) get_parameter ("amount4", 0);
	$amount[4] = (float) get_parameter ("amount5", 0);
	
	$user_id = $config["id_user"];
	$invoice_create_date = get_parameter ("invoice_create_date");
	$invoice_payment_date = get_parameter ("invoice_payment_date");
	
	//~ tax 
	$taxarray = array();
	$cont = 1;
	foreach ( $_POST as $key => $campo) {
		if ($key === 'tax'.$cont){
			$taxarray[$key] = get_parameter ($key, 0.00);
			$cont++;
		}
	}
	$taxencode = json_encode($taxarray);
	$tax = json_decode($taxencode,true);	
	
	$irpf = get_parameter ("irpf", 0.00);
	$concept_irpf = get_parameter ("concept_irpf");
	$currency = get_parameter ("currency", "EUR");
	$currency_change = get_parameter("currency_change", _('None'));
	$rates = get_parameter("rates", 0);
	$invoice_status = get_parameter ("invoice_status", 'pending');
	$invoice_type = get_parameter ("invoice_type", "Submitted");
	$language = get_parameter('id_language', $config['language_code']);
	$internal_note = get_parameter('internal_note', "");
	$invoice_expiration_date = get_parameter ("invoice_expiration_date");
	$invoice_contract_number = get_parameter("invoice_contract_number");
	
	//~ tax_name 
	$tax_name_array = array();
	$cont = 1;
	foreach ( $_POST as $key => $campo) {
		if ($key === 'tax_name'.$cont){
			$tax_name_array[$key] = get_parameter ($key, 0.00);
			$cont++;
		}
	}
	$tax_name_encode = json_encode($tax_name_array);
	$tax_name = json_decode($tax_name_encode,true);
	
	$discount_before = (float) get_parameter ("discount_before", 0.00);
	$discount_concept = get_parameter ("discount_concept", "");
	$create_calendar_event = get_parameter('calendar_event');

	if ($invoice_type == "Received") {
		$bill_id_variable = 0;
	} else {
		$bill_id_variable = get_parameter('bill_id_variable', 0);
	}
	$bill_id_pattern = $config['invoice_id_pattern'];
	
	// Updating the invoice
	$values = array();
	$values['description'] = $description;
	$values['id_user'] = $user_id;
	$values['id_company'] = $id_company;
	$values['reference'] = $reference;
	$values['bill_id'] = $bill_id;
	$values['concept1'] = $concept[0];
	$values['concept2'] = $concept[1];
	$values['concept3'] = $concept[2];
	$values['concept4'] = $concept[3];
	$values['concept5'] = $concept[4];
	$values['amount1'] = $amount[0];
	$values['amount2'] = $amount[1];
	$values['amount3'] = $amount[2];
	$values['amount4'] = $amount[3];
	$values['amount5'] = $amount[4];
	$values['status'] = $invoice_status;
	$values['tax'] = json_encode($taxarray);
	$values['irpf'] = $irpf;
	$values['concept_irpf'] = $concept_irpf;
	$values['currency'] = $currency;
	$values['currency_change'] = $currency_change;
	$values['rates'] = $rates;

	$values['invoice_create_date'] = $invoice_create_date;
	$values['invoice_payment_date'] = $invoice_payment_date;
	$values['invoice_expiration_date'] = $invoice_expiration_date;
	
	$values['invoice_type'] = $invoice_type;
	$values['id_language'] = $language;
	$values['internal_note'] = $internal_note;
	
	$values['bill_id_variable'] = $bill_id_variable;
	$values['bill_id_pattern'] = $bill_id_pattern;
	$values['contract_number'] = $invoice_contract_number;
	$values['tax_name'] = json_encode($tax_name_array);
	$values['discount_before'] = $discount_before;
	$values['discount_concept'] = $discount_concept;

	$where = array('id' => $id_invoice);
	
	$ret = process_sql_update ('tinvoice', $values, $where);

	if ($create_calendar_event) { 
			$now = date('Y-m-d H:i:s');
			$time = substr($now, 11, 18);
			$title = __('Reminder: Invoice ').$bill_id.__(' payment date'); 
			
			$sql_event2 ="DELETE FROM tagenda WHERE title='".$title."';";
			
			process_sql ($sql_event2);
			
			$sql_event ="INSERT INTO tagenda (public, alarm, timestamp, id_user,
				title, duration, description)
				VALUES (0, '1440', '$invoice_payment_date $time', '".$config['id_user']."', '$title',
				0, '')";
			
			$result = process_sql ($sql_event);
		}

	if ($ret !== false) {
		$company_name = get_db_value('name', 'tcompany', 'id', $id_company);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Invoice updated", "Invoice Bill ID: ".$bill_id.", Company: $company_name");
		
		//update last activity
		$datetime =  date ("Y-m-d H:i:s");
		$comments = __("Invoice ".$id_invoice." updated by ".$config['id_user']);
		$sql_add = sprintf ('INSERT INTO tcompany_activity (id_company, written_by, date, description) VALUES (%d, "%s", "%s", "%s")', $id_company, $config["id_user"], $datetime, $comments);
		process_sql ($sql_add);
		$sql_activity = sprintf ('UPDATE tcompany SET last_update = "%s" WHERE id = %d', $datetime, $id_company);
		$result_activity = process_sql ($sql_activity);
		
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem updating the invoice'), '', true, 'h3', true);
	}
}

if ($operation_invoices == "add_invoice"){
	
	$filename = get_parameter ('upfile', false);
	$bill_id = get_parameter ("bill_id", "");
	$reference = get_parameter ("reference", "");
	$description = get_parameter ("description", "");
	$concept = array();
	$concept[0] = get_parameter ("concept1", "");
	$concept[1] = get_parameter ("concept2", "");
	$concept[2] = get_parameter ("concept3", "");
	$concept[3] = get_parameter ("concept4", "");
	$concept[4] = get_parameter ("concept5", "");
	$amount = array();
	$amount[0] = (float) get_parameter ("amount1", 0);
	$amount[1] = (float) get_parameter ("amount2", 0);
	$amount[2] = (float) get_parameter ("amount3", 0);
	$amount[3] = (float) get_parameter ("amount4", 0);
	$amount[4] = (float) get_parameter ("amount5", 0);
	$user_id = $config["id_user"];
	$invoice_create_date = get_parameter ("invoice_create_date");
	$invoice_payment_date = get_parameter ("invoice_payment_date");
	$invoice_expiration_date = get_parameter ("invoice_expiration_date");
	//~ tax
	$taxarray = array();
	$cont = 1;
	foreach ( $_POST as $key => $campo) {
		if ($key === 'tax'.$cont){
			$taxarray[$key] = get_parameter ($key, 0.00);
			$cont++;
		 }
	}
	$taxencode = json_encode($taxarray);
	$tax = json_decode($taxencode,true);
		
	$irpf = get_parameter ("irpf", 0.00);
	$concept_irpf = get_parameter ("concept_irpf");
	//~ tax_name 
	$tax_name_array = array();
	$cont = 1;
	foreach ( $_POST as $key => $campo) {
		if ($key === 'tax_name'.$cont){
			$tax_name_array[$key] = get_parameter ($key, 0.00);
			$cont++;
		 }
	}
	$tax_name_encode = json_encode($tax_name_array);
	$tax_name = json_decode($tax_name_encode,true);
	
	$currency = get_parameter ("currency", "EUR");
	$currency_change = get_parameter ("currency_change", __('None'));
	$rates = get_parameter("rates", 0);
	$invoice_status = get_parameter ("invoice_status", 'pending');
	$invoice_type = get_parameter ("invoice_type", "Submitted");
	$create_calendar_event = get_parameter('calendar_event');
	$language = get_parameter('id_language', $config['language_code']);
	$internal_note = get_parameter ("internal_note", "");
	$invoice_contract_number = get_parameter('invoice_contract_number');
	$discount_before = get_parameter ("discount_before", 0.00);
	$discount_concept = get_parameter ("discount_concept", "");
	
	if ($invoice_type == "Received") {
		$bill_id_variable = 0;
	} else {
		$bill_id_variable = get_parameter('bill_id_variable', 0);
	}
	$bill_id_pattern = $config['invoice_id_pattern'];
	
	if ($filename != ""){
		$file_temp = sys_get_temp_dir()."/$filename";
		$filesize = filesize($file_temp);
		
		// Creating the attach
		$sql = sprintf ('INSERT INTO tattachment (id_usuario, filename, description, size) VALUES ("%s", "%s", "%s", "%s")',
				$user_id, $filename, $description, $filesize);
		$id_attachment = process_sql ($sql, 'insert_id');
		
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$id_attachment."_".$filename;
			
		if (! copy($file_temp, $file_target)) {
			$result_output = ui_print_error_message (__('File cannot be saved. Please contact Integria administrator about this error'), '', true, 'h3', true);
			$sql = "DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
			process_sql ($sql);
		} else {
			// Delete temporal file
			unlink ($file_temp);
		}
	} else {
		$id_attachment = 0;
	}
	
	$bill_id_exists = get_db_value('bill_id', 'tinvoice', 'bill_id', $bill_id);
	if (!$bill_id_exists) {
		// Creating the cost record
		$sql = sprintf ("INSERT INTO tinvoice (description, id_user, id_company,
		bill_id, id_attachment, invoice_create_date, invoice_payment_date, tax, irpf, concept_irpf, currency, status,
		concept1, concept2, concept3, concept4, concept5, amount1, amount2, amount3,
		amount4, amount5, reference, invoice_type, id_language, internal_note, invoice_expiration_date, bill_id_pattern, bill_id_variable, contract_number, discount_before, discount_concept, tax_name, currency_change, rates) VALUES ('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s','%s', '%s',
		'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", $description, $user_id, $id_company,
		$bill_id, $id_attachment, $invoice_create_date, $invoice_payment_date, json_encode($taxarray), $irpf, $concept_irpf,$currency,
		$invoice_status, $concept[0], $concept[1], $concept[2], $concept[3], $concept[4], $amount[0], $amount[1],
		$amount[2], $amount[3], $amount[4], $reference, $invoice_type, $language, $internal_note, $invoice_expiration_date, $bill_id_pattern, $bill_id_variable, $invoice_contract_number, $discount_before, $discount_concept, json_encode($tax_name_array), $currency_change, $rates);
		
		$id_invoice = process_sql ($sql, 'insert_id');
		if ($id_invoice !== false) {
			if ($create_calendar_event) { 
				$now = date('Y-m-d H:i:s');
				$time = substr($now, 11, 18);
				$title = __('Reminder: Invoice ').$bill_id.__(' payment date'); 

				$sql_event ="INSERT INTO tagenda (public, alarm, timestamp, id_user,
					title, duration, description)
					VALUES (0, '1440', '$invoice_payment_date $time', '".$config['id_user']."', '$title',
					0, '')";

				$result = process_sql ($sql_event);

			}
			
			$company_name = get_db_value('name', 'tcompany', 'id', $id_company);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Invoice created", "Invoice Bill ID: ".$bill_id.", Company: $company_name");
			
			//update last activity
			$datetime =  date ("Y-m-d H:i:s");
			$comments = __("Invoice created by ".$config['id_user']);
			$sql_add = sprintf ('INSERT INTO tcompany_activity (id_company, written_by, date, description) VALUES (%d, "%s", "%s", "%s")', $id_company, $config["id_user"], $datetime, $comments);
			process_sql ($sql_add);
			$sql_activity = sprintf ('UPDATE tcompany SET last_update = "%s" WHERE id = %d', $datetime, $id_company);
			$result_activity = process_sql ($sql_activity);
				
			echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
			$operation_invoices = "update_invoice";
		} else {
			echo ui_print_error_message (__('There was a problem creating the invoice'), '', true, 'h3', true);
		}
	} else {
		echo ui_print_error_message (__('This bill ID already exists'), '', true, 'h3', true);
	}
}

if ($id_invoice > 0){
	
	$invoice = get_db_row ('tinvoice', 'id', $id_invoice);
	$reference = $invoice["reference"];
	$bill_id = $invoice["bill_id"];
	$description = $invoice["description"];
	$concept = array();
	$concept[0] = $invoice["concept1"];
	$concept[1] = $invoice["concept2"];
	$concept[2] = $invoice["concept3"];
	$concept[3] = $invoice["concept4"];
	$concept[4] = $invoice["concept5"];
	$amount = array();
	$amount[0] = $invoice["amount1"];
	$amount[1] = $invoice["amount2"];
	$amount[2] = $invoice["amount3"];
	$amount[3] = $invoice["amount4"];
	$amount[4] = $invoice["amount5"];
	$id_attachment = $invoice["id_attachment"];
	$invoice_create_date = $invoice["invoice_create_date"];
	$invoice_payment_date = $invoice["invoice_payment_date"];
	$invoice_expiration_date = $invoice["invoice_expiration_date"];
	$id_company = $invoice["id_company"];
	$long_tax = strlen($invoice["tax"]);
	if (substr($invoice["tax"], -$long_tax, 1) == '{'){
		$tax = json_decode($invoice["tax"]);
	} else {
		$tax = $invoice["tax"];
	}
	$irpf = $invoice["irpf"];
	$concept_irpf = $invoice["concept_irpf"];
	$currency = $invoice["currency"];
	$currency_change = $invoice["currency_change"];
	$rates = $invoice["rates"]; 
	$invoice_status = $invoice["status"];
	$invoice_type = $invoice['invoice_type'];
	$language = $invoice['id_language'];
	$internal_note = $invoice['internal_note'];
	$bill_id_variable = $invoice['bill_id_variable'];
	$invoice_contract_number = $invoice['contract_number'];
	$long_tax_name = strlen($invoice["tax_name"]);
	if (substr($invoice["tax_name"], -$long_tax_name, 1) == '{'){
		$tax_name = json_decode($invoice["tax_name"]);
	} else {
		$tax_name = $invoice["tax_name"];	
	}
	$discount_before = $invoice["discount_before"];
	$discount_concept = $invoice["discount_concept"];

}
else {
	
	if ($id_company > 0) {
		$permission = check_crm_acl ('invoice', '', $config['id_user'], $id_company);
		if (!$permission) {
			include ("general/noaccess.php");
			exit;
		}
	}
	if (!$write && !$manage) {
		include ("general/noaccess.php");
		exit;
	}
	
	$bill_id = "";
	$reference = "";
	$description = "";
	$id_attachment = "";
	$invoice_create_date = date("Y-m-d");
	$invoice_payment_date = "";
	$invoice_expiration_date = "";
	$tax = 0;
	$irpf = 0;
	$concept_irpf = "";
	$currency = "EUR";
	$currency_change = __('None');
	$rates = 0;
	$invoice_status = "pending";
	$invoice_type = "Submitted";
	$language = $config['language_code'];
	$internal_note = "";
	$bill_id_variable = 0;
	$tax_name = "";
	$discount_before = 0;
	$discount_concept = "";
}

echo "<h2>" . __('Invoices');
if ($id_invoice == "-1") {
	echo "<h4>" . __('Add new invoice');
	echo integria_help ("invoice_detail", true);
	echo "<div id='button-bar-title'>";
		echo "<ul>";
			echo "<li><a href='index.php?sec=customers&sec2=operation/invoices/invoice_detail'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back")))."</a></li>";
		echo "</ul>";
	echo "</div>";
}
else {
	$is_update = true;
	
	echo "<h4>" . __('Update invoice'). " ".$invoice["bill_id"];;
	echo integria_help ("invoice_detail", true);
	echo ' <a href="index.php?sec=users&amp;sec2=operation/invoices/invoice_view
				&amp;id_invoice='.$id_invoice.'&amp;clean_output=1&amp;pdf_output=1&language='.$language.'">
				<img src="images/page_white_acrobat.png" title="'.__('Export to PDF').'"></a>';
	if ($lock_permission) {
		echo ' <a href="?sec=customers&sec2=operation/companies/company_detail
			&lock_invoice=1&id='.$id_company.'&op=invoices&id_invoice='.$id_invoice.'" 
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">
			<img src="images/lock_open.png" title="'.__('Lock').'"></a>';
	}
	echo " <a href='#' onClick='javascript: show_validation_delete(\"delete_company_invoice\",".$id_invoice.",".$id_company.");'><img src='images/cross.png' title='".__('Delete')."'></a>";
}
echo "</h4>";

$generate = get_parameter('generate', 0);
if ($generate) {
	$invoice_contract_number = get_parameter('invoice_contract_number');
} else {
	$invoice_contract_number = get_db_value('contract_number', 'tinvoice', 'id', $id_invoice);
}

$table = new StdClass();
$table->id = 'cost_form';
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();

if ($id_company > 0) {
	$id_company_from_contract = get_parameter('id_company', '');

	if (isset($id_company_from_contract) && (!empty($id_company_from_contract))) {
		$id_company = $id_company_from_contract;
	} else {
		$id_company = get_parameter('id');
	}

	$company_name = get_db_value ("name", "tcompany", "id", $id_company);
	$table->colspan[0][0] = 2;
	$table->data[0][0] = print_input_text ('company_name', $company_name, '', 50, 100, true, __('Company'), true);
	$table->data[0][0] .= "<input type=hidden name='id' value='$id_company'>";
} else {
	$params = array();
	$params['input_id'] = 'id';
	$params['input_name'] = 'id';
	$params['title'] = __('Company');
	$params['return'] = true;
	$params['input_value'] = get_parameter('company_id');
	$table->colspan[0][0] = 2;
	$table->data[0][0] = print_company_autocomplete_input($params);
}

$invoice_types = array('Submitted'=>'Submitted', 'Received'=>'Received');
$table->data[0][2] = print_select ($invoice_types, 'invoice_type', $invoice_type, '','', 0, true, false, false, __('Type'));

$table->data[0][3] = print_input_text ('reference', $reference, '', 25, 100, true, __('Reference'));
$table->data[1][0] = print_input_text ('bill_id', $bill_id, '', 25, 100, true, __('Bill ID'));

if ($bill_id == ""){ // let's show the latest Invoice ID generated in the system
	$last_invoice_generated = crm_get_last_invoice_id();
	$table->data[1][0] .= "<div id='last_id'><span style='font-size: 9px'> ". __("Last generated ID: "). $last_invoice_generated . "</span></div>";
}

$invoice_status_ar = array();
$invoice_status_ar['pending']= __("Pending");
$invoice_status_ar['paid']= __("Paid");
$invoice_status_ar['canceled']= __("Canceled");
$table->data[1][1] = print_select ($invoice_status_ar, 'invoice_status',
	$invoice_status, '','', 0, true, false, false, __('Invoice status'));

$table->data[1][2] = print_input_text ('invoice_create_date', $invoice_create_date, '', 15, 50, true, __('Invoice creation date'));
$table->data[1][3] = print_checkbox_extended ('calendar_event', 1, '', false, '', '', true, __('Create calendar event'));

if ($id_invoice != -1) {
	$disabled = true;
} else {
	$disabled = false;
}

$table->data[2][0] = print_input_text ('invoice_expiration_date', $invoice_expiration_date, '', 15, 50, true,__('Invoice expiration date'));
$table->data[2][1] = print_input_text ('invoice_payment_date', $invoice_payment_date, '', 15, 50, true,__('Invoice effective payment date'));

$table->data[2][2] = print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name', 'id_language', 
	$language, '', '', '', true, false, false, __('Language'));
	
$table->data[2][3] = print_input_text ('invoice_contract_number', $invoice_contract_number, '', 15, 50, true,__('Contract number'));

$table->data[3][0] = print_input_text ('currency', $currency, '', 5, 30, true, __('Currency'));
$table->data[3][1] = print_input_text_extended ('currency_change', $currency_change, 'text-currency_change', false, 5, 30, false, '', "oninput='change_currency_title()'", true, false, __('Equivalent currency'));
$table->data[3][2] = print_input_text_extended ('rates', $rates, 'text-rates', false, 5, 30, false, '', "oninput='calculate_rate_all()'",true, false, __('Rates'));

$table->colspan[4][0] = 2;
$table->data[4][0] = "<br/><h4>".__('Concept')."</h4>";
$table->data[4][2] = "<br/><h4>".__('Amount')."</h4>";
$table->data[4][3] = "<br/><h4 id='currency_change_title' >". $currency_change ."</h4>";

$table->colspan[5][0] = 2;
if(!isset($amount[0])){
	$amount[0] = '';
}
if(!isset($concept[0])){
	$concept[0] = '';
}
$rate1 = sprintf("%.2f", $amount[0] * $rates);
$table->data[5][0] = print_input_text ('concept1', $concept[0], '', 60, 250, true);
$table->data[5][2] = print_input_text_extended ('amount1', $amount[0], 'text-amount1', '', 18, 20, false, '', "oninput='calculate_rate(\"text-amount1\", \"text-rate1\");'",true);
$table->data[5][3] = print_input_text ('rate1', $rate1, '', 18, 20, true);
if(!isset($amount[1])){
	$amount[1] = '';
}
if(!isset($concept[1])){
	$concept[1] = '';
}
$table->colspan[6][0] = 2;
$rate2 = sprintf("%.2f", $amount[1] * $rates);
$table->data[6][0] = print_input_text ('concept2', $concept[1], '', 60, 250, true);
$table->data[6][2] = print_input_text_extended ('amount2', $amount[1], 'text-amount2', '', 18, 20, false, '', "oninput='calculate_rate(\"text-amount2\", \"text-rate2\")'",true);
$table->data[6][3] = print_input_text ('rate2', $rate2, '', 18, 20, true);
if(!isset($amount[2])){
	$amount[2] = '';
}
if(!isset($concept[2])){
	$concept[2] = '';
}
$table->colspan[7][0] = 2;
$rate3 = sprintf("%.2f", $amount[2] * $rates);
$table->data[7][0] = print_input_text ('concept3', $concept[2], '', 60, 250, true);
$table->data[7][2] = print_input_text_extended ('amount3', $amount[2], 'text-amount3', '', 18, 20, false, '', "oninput='calculate_rate(\"text-amount3\", \"text-rate3\")'",true);
$table->data[7][3] = print_input_text ('rate3', $rate3, '', 18, 20, true);
if(!isset($amount[3])){
	$amount[3] = '';
}
if(!isset($concept[3])){
	$concept[3] = '';
}
$table->colspan[8][0] = 2;
$rate4 = sprintf("%.2f", $amount[3] * $rates);
$table->data[8][0] = print_input_text ('concept4', $concept[3], '', 60, 250, true);
$table->data[8][2] = print_input_text_extended ('amount4', $amount[3], 'text-amount4', '', 18, 20, false, '', "oninput='calculate_rate(\"text-amount4\", \"text-rate4\")'",true);
$table->data[8][3] = print_input_text ('rate4', $rate4, '', 18, 20, true);
if(!isset($amount[4])){
	$amount[4] = '';
}
if(!isset($concept[4])){
	$concept[4] = '';
}
$table->colspan[9][0] = 2;
$rate5 = sprintf("%.2f", $amount[4] * $rates);
$table->data[9][0] = print_input_text ('concept5', $concept[4], '', 60, 250, true);
$table->data[9][2] = print_input_text_extended ('amount5', $amount[4], 'text-amount5', '', 18, 20, false, '', "oninput='calculate_rate(\"text-amount5\", \"text-rate5\")'",true);
$table->data[9][3] = print_input_text ('rate5', $rate5, '', 18, 20, true);

$table->colspan[10][0] = 2;
$table->data[10][0] = '<br/>' . print_input_text ('discount_concept', $discount_concept, '', 60, 50, true, __('Concept discount'));
$table->colspan[10][2] = 2;
$table->data[10][2] = '<br/>' . print_input_text ('discount_before', $discount_before, '', 5, 20, true, __('Discount before taxes (%)'));

$contcampo2 = 1;
if (is_string($tax_name)){
	$table->colspan[11][0] = 2;
	$table->data[11][0] = print_input_text ('tax_name1', $tax_name, '', 60, 50, true, __('Concept tax'));
	$table->data[11][0] .= '</br>';
	$contcampo2++;
}else{
	foreach ( $tax_name as $key => $campo) { 
		if ($key === 'tax_name1'){
			$table->colspan[11][0] = 2;
			$table->data[11][0] = print_input_text ('tax_name'.$contcampo2, $campo, '', 60, 50, true, __('Concept tax'));
			$table->data[11][0] .= '</br>';
		}else{
			if ($campo != ""){
				$table->colspan[11][0] = 2;
				$table->data[11][0] .= print_input_text ('tax_name'.$contcampo2, $campo, '', 60, 50, true,'');
				$table->data[11][0] .= '</br>';
			}else{
				$contcampo2--;
			}
		}
		$contcampo2++;
	}
}

$contcampo = 1;	
if (is_numeric($tax)){
	$table->colspan[11][2] = 2;
	$table->data[11][2] = print_input_text ('tax1', $tax, '', 5, 20, true, __('Taxes (%)'));
	$table->data[11][2] .= "<a href='#' id='agregarCampo'><img src='images/input_create.png' /></a>";
	$table->data[11][2] .= '</br>';
	$contcampo++;
}else{
	foreach ( $tax as $key => $campo) { 
		if ($key === 'tax1'){
			$table->colspan[11][2] = 2;
			$table->data[11][2] = print_input_text ('tax'.$contcampo, $campo, '', 5, 20, true, __('Taxes (%)'));
			$table->data[11][2] .= "<a href='#' id='agregarCampo'><img src='images/input_create.png' /></a>";
			$table->data[11][2] .= '</br>';
		}else{
			if ($campo != 0){
				$table->colspan[11][2] = 2;
				$table->data[11][2] .= print_input_text ('tax'.$contcampo, $campo, '', 5, 20, true,'');
				$table->data[11][2] .= '</br>';
			}else{
				$contcampo--;
			}
		}
		$contcampo++;
	}
}


$table->colspan[12][0] = 2;
$table->data[12][0] = print_input_text ('concept_irpf', $concept_irpf, '', 60, 50, true, __('Concept Retention'));
$table->colspan[12][2] = 2;
$table->data[12][2] = print_input_text ('irpf', $irpf, '', 5, 20, true, __('Retention (%)'));

echo '<div id="msg_ok_hidden" style="display:none;">';
	echo ui_print_success_message (__('Custom search saved'), '', true, 'h3', true);
echo '</div>';

if ($id_invoice != -1) {
	$amount = get_invoice_amount ($id_invoice);
	$discount_before = get_invoice_discount_before ($id_invoice);
	$tax = get_invoice_tax ($id_invoice);
	$taxlength = count($tax);
	$contador = 1;
	$result = 0;
	foreach ( $tax as $key => $campo) { 
		$result = $result + $campo;
		$contador++;
	}
	$tax = $result;
	$irpf = get_invoice_irpf($id_invoice);
	//~ Descuento sobre el total
	$before_amount = $amount * ($discount_before/100);
	$total_before = round($amount - $before_amount, 2);
	//~ Se aplica sobre el descuento los task 
	$tax_amount = $total_before * ($tax/100);
	//~ Se aplica sobre el descuento el irpf
	$irpf_amount = $total_before * ($irpf/100);
	$total = round($total_before + $tax_amount - $irpf_amount, 2);
	$table->colspan[13][0] = 2;
	if ($total < 0) {
		$total = abs($total);
		$total = format_numeric($total,2);
		$total = "-" . $total;
		$table->data[13][0] = print_label(__('Total amount: ').$total.' '.$invoice['currency'], 'total_amount', 'text', true);
	}
	else {
		$table->data[13][0] = print_label(__('Total amount: ').format_numeric($total,2).' '.$invoice['currency'], 'total_amount', 'text', true);
	}
	
	$table->colspan[13][2] = 2;
	if ($amount < 0) {
		$amount = abs($amount);
		$amount = format_numeric($amount,2);
		$amount = "-" . $amount;
		$table->data[13][2] = print_label(__('Total amount without taxes or discounts: '). $amount .' '.$invoice['currency'], 'total_amount_without_taxes', 'text', true);
	}
	else {
		$table->data[13][2] = print_label(__('Total amount without taxes or discounts: ').format_numeric($amount,2).' '.$invoice['currency'], 'total_amount_without_taxes', 'text', true);
	}
}


$table->colspan[14][0] = 4;
$table->data[14][0] = print_textarea ('description', 5, 40, $description, '', true, __('Description'));

$table->colspan[15][0] = 4;
$table->data[15][0] = print_textarea ('internal_note', 5, 40, $internal_note, '', true, __('Internal note'));

echo '<form id="form-invoice" method="post" enctype="multipart/form-data" action="index.php?sec=customers&sec2=operation/companies/company_detail
&view_invoice=1&op=invoices">';
	print_table ($table);
	echo '<div class="button-form" style="width:'.$table->width.';">';
	if ($id_invoice != -1) {
		print_submit_button (__('Update'), 'button-upd', false, 'class="sub upd"');
		print_input_hidden ('id', $id);
		print_input_hidden ('operation_invoices', "update_invoice");
		print_input_hidden ('id_invoice', $id_invoice);
		print_input_hidden ('bill_id_variable', $bill_id_variable);	
	} else {
		print_submit_button (__('Add'), 'button-crt', false, 'class="sub next"');
		print_input_hidden ('operation_invoices', "add_invoice");
		print_input_hidden ('id_invoice', $id_invoice);
		print_input_hidden ('bill_id_variable', $bill_id_variable);
	}
	echo '</div>';
echo '</form>';
if ($id_invoice != -1) {
	
	echo "<h4>" . __('Files') . '</h4>';

	//~ $target_directory = 'attachment';
	$action = "include/ajax/invoices&id=$id_company&id_invoice=$id_invoice&op=invoices&view_invoice=1&upload_file=1";				
	//~ $into_form = "<input type='hidden' name='directory' value='$target_directory'><b>Description</b><input type=text name=description size=60>";
	//~ print_input_file_progress($action,$into_form,'','sub upload');	
	echo "<form id=\"form-invoice_files\" class=\"fileupload_form\" method=\"post\" enctype=\"multipart/form-data\">";
	echo 	"<div id=\"drop_file\" style=\"padding:0px 0px;\">";
	echo 		"<table width=\"100%\">";
	echo 			"<td width=\"45%\">";
	echo 				__('Drop the file here');
	echo 			"<td>";
	echo 				__('or');
	echo 			"<td width=\"45%\">";
	echo 				"<a id=\"browse_button\">" . __('browse it') . "</a>";
	echo 		"</table>";
	echo 		"<input name=\"upfile\" type=\"file\" id=\"file-upfile\" class=\"sub file\" />";
	echo 	"</div>";
	echo 	"<ul></ul>";
	echo "</form>";

	echo "<div id='file_description_table_hook' style='display:none;'>";
	$table = new stdClass;
	$table->width = '100%';
	$table->id = 'invoice_file_description';
	$table->class = 'search-table-button';
	$table->data = array();
	$table->data[0][0] = print_textarea ("description", 5, 40, '', '', true, __('Description'));
	$table->data[1][0] = print_submit_button (__('Add'), 'crt_btn', false, 'class="sub create"', true);
	print_table($table);
	echo "</div>";

	// List of invoice attachments
	$sql = "SELECT * FROM tattachment WHERE id_invoice = $id_invoice ORDER BY timestamp DESC";
	$files = get_db_all_rows_sql ($sql);
	
	if ($files !== false) {
		$files = print_array_pagination ($files, "index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company&id_invoice=$id_invoice&op=invoices&view_invoice=1");
		unset ($table);
		
		$table = new stdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->id = "invoice_files";
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->rowstyle = array ();

		$table->head = array ();
		$table->head[0] = __('Filename');
		$table->head[1] = __('Description');
		$table->head[2] = __('Size');
		$table->head[3] = __('Date');
		$table->head[4] = __('Ops.');

		foreach ($files as $file) {
			$data = array ();
			
			$data[0] = "<a href='operation/common/download_file.php?id_attachment=".$file["id_attachment"]."&type=company'>".$file["filename"] . "</a>";
			$data[1] = $file["description"];
			$data[2] = format_numeric($file["size"]);
			$data[3] = $file["timestamp"];

			// Todo. Delete files owner and admins only
			if ( (dame_admin($config["id_user"])) || ($file["id_usuario"] == $config["id_user"]) ){
				$data[4] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company&id_invoice=$id_invoice&op=invoices&view_invoice=1&deletef=".$file["id_attachment"]."'><img src='images/cross.png'></a>";
			}

			array_push ($table->data, $data);
			array_push ($table->rowstyle, $style);
		}
		print_table ($table);

	}
	else {
		$check_table_hidden = 1;
		$table = new stdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->id = "invoice_files";
		
		$table->head = array ();
		$table->head[0] = __('Filename');
		$table->head[1] = __('Description');
		$table->head[2] = __('Size');
		$table->head[3] = __('Date');
		$table->head[4] = __('Ops.');

		print_table ($table);
		echo "<h3 id='no_files_invoices' >". __('There is no files attached for this invoice')."</h3>";
	}
}

//is_update hidden
echo '<div id="div_is_update_hidden" style="display:none;">';
	print_input_text('is_update_hidden', $is_update);
echo '</div>';

//invoice_id hidden
echo '<div id="invoice_hidden" style="display:none;">';
	print_input_text('invoice_id_hidden', $id_invoice);
echo '</div>';

//invoice_type hidden
echo '<div id="invoice_type_hidden" style="display:none;">';
	print_input_text('invoice_type_hidden', $invoice_type);
echo '</div>';
?>

<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script src="include/js/jquery.fileupload.js"></script>
<script src="include/js/jquery.iframe-transport.js"></script>
<script src="include/js/jquery.knob.js"></script>

<script type="text/javascript" src="include/js/agenda.js"></script>

<script type="text/javascript">
	
// Datepicker
add_ranged_datepicker ("#text-invoice_create_date", "#text-invoice_payment_date", null);
add_ranged_datepicker ("#text-invoice_payment_date", "#text-invoice_expiration_date", null);

// Form validation
trim_element_on_submit('#text-bill_id');

validate_form("#form-invoice");
var rules, messages;

if (<?php echo json_encode((int)$id_company) ?> <= 0) {
	// Rules: #id
	rules = { required: true };
	messages = { required: "<?php echo __('Company required'); ?>" };
	add_validate_form_element_rules('#id', rules, messages);
}

// Rules: #text-bill_id
rules = {
	required: true,
	remote: {
		url: "ajax.php",
		type: "POST",
		data: {
			page: "include/ajax/remote_validations",
			search_existing_invoice: 1,
			invoice_type: function() { return $('#invoice_type').val() },
			bill_id: function() { return $('#text-bill_id').val() },
			invoice_id: <?php echo $id_invoice ?>
		}
	}
};
messages = {
	required: "<?php echo __('Bill ID required'); ?>",
	remote: "<?php echo __('This bill ID already exists'); ?>"
};
add_validate_form_element_rules('#text-bill_id', rules, messages);

// Rules: #text-tax
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
var cont = 1;
var taxlength = "<?php echo $taxlength; ?>";
while (cont <= taxlength){
	add_validate_form_element_rules('#text-tax'+ cont, rules, messages);
	cont++;
}
// Rules: #text-rates
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('#text-rates', rules, messages);
// Rules: #text-irpf
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('#text-irpf', rules, messages);
// Rules: input[name="amount1"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount1"]', rules, messages);
// Rules: input[name="amount2"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount2"]', rules, messages);
// Rules: input[name="amount3"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount3"]', rules, messages);
// Rules: input[name="amount4"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount4"]', rules, messages);
// Rules: input[name="amount5"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount5"]', rules, messages);
// Rules: input[name="rate1"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="rate1"]', rules, messages);
// Rules: input[name="rate2"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="rate2"]', rules, messages);
// Rules: input[name="rate3"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="rate3"]', rules, messages);
// Rules: input[name="rate4"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="amount4"]', rules, messages);
// Rules: input[name="rate5"]
rules = { number: true };
messages = { number: "<?php echo __('Invalid number')?>" };
add_validate_form_element_rules('input[name="rate5"]', rules, messages);


$(document).ready (function () {
	if (<?php echo $check_table_hidden ?> == 1) {
		$('#invoice_files').css('display', 'none');
	}

	form_upload();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	autoGenerateID = "<?php echo $config['invoice_auto_id'] ?>";
	is_update = $("#text-is_update_hidden").val();
	invoice_id = $("#text-invoice_id_hidden").val();
	invoice_type = $("#text-invoice_type_hidden").val();
	
	if (autoGenerateID) {
		if ($("#invoice_type").val() == 'Submitted') {
			if (!is_update) {
				invoiceGenerateID();
			} else {
				$("#text-bill_id").prop('readonly', true);
			}
			$("#text-bill_id").prop('readonly', true);
		}
		
		//$("#last_id").css('display', 'none');
	} 
	
	if (<?php echo json_encode((int)$id_company) ?> <= 0) {
		bindCompanyAutocomplete ('id', idUser, 'invoice');
	}
	
	$("#invoice_type").click (function () {
		if (autoGenerateID) {
			if ($("#invoice_type").val() == 'Submitted') {
				$("#text-bill_id").prop('readonly', true);
				if (!is_update) {
					invoiceGenerateID();
				} else {
					invoice_id = $("#text-invoice_id_hidden").val();
					if (invoice_type == "Submitted") {
						invoiceGetID(invoice_id);
						$("#text-bill_id").prop('readonly', true);
					} else {
						invoiceGenerateID();
					}
					
				}
			} else {
				$("#text-bill_id").prop('disabled', false);
				$("#text-bill_id").prop('readonly', '');
				$("#text-bill_id").prop('value', "");
			}
		} else {	
			if ($("#invoice_type").val() == 'Received') {
				$("#last_id").css('display', 'none');
			} else {
				$("#last_id").css('display', '');
				$("#text-bill_id").prop('readonly', '');
			}
			$("#text-bill_id").prop('value', "");
		}
	});

	var contenedor = $("#cost_form-11-0"); //ID del contenedor
	var contenedor1 = $("#cost_form-11-2"); //ID del contenedor
	var linkagregar = $("#agregarCampo"); //ID del Botón Agregar
	var FieldCount = "<?php echo $contcampo; ?>";
	var FieldCount2 = "<?php echo $contcampo2; ?>";
	//~ var FieldCount = 2; //para el seguimiento de los campos
	$(linkagregar).click(function (e) {
		e.preventDefault();
		$(contenedor1).append('<input type="text" id="text-tax'+ FieldCount +'" maxlength="20" size="5" value="0" name="tax'+ FieldCount +'" /></br>');
		rules = { number: true };
		messages = { number: "<?php echo __('Invalid number')?>" };
		add_validate_form_element_rules('#text-tax'+ FieldCount, rules, messages);
		$(contenedor).append('<input type="text" id="text-tax_name'+ FieldCount2 +'" maxlength="50" size="20" name="tax_name'+ FieldCount2 +'" /></br>');
		FieldCount++;
		FieldCount2++;
	});	
});

function invoiceGenerateID () {

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: {
			'page': 'include/ajax/crm',
			'get_invoice_id': 1
		},
		dataType: "json",
		async: false,
		success: function (data) {
			dataUnserialize = data.split(';;;;');
			id_bill = dataUnserialize[0];
			id_variable = dataUnserialize[1];
			
			$("#text-bill_id").attr('value', id_bill);
			$("#hidden-bill_id_variable").attr('value', id_variable);
			$("#hidden-bill_id").attr('value', id_bill);
		}
	});
}

function invoiceGetID (id) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: {
			'page': 'include/ajax/crm',
			'get_old_invoice_id': 1,
			'id': id
		},
		dataType: "json",
		async: true,
		success: function (bill_id) {
			$("#text-bill_id").attr('value', bill_id);
		}
	});
}

function change_currency_title() {
	var value = $('#text-currency_change').val();
	if(value){
		$('#currency_change_title').empty(value);
		$('#currency_change_title').append(value);	
	} else {
		$('#currency_change_title').empty(value);
		$('#currency_change_title').append('none');
	}
}

function calculate_rate(id_input, id_result) {
	var rate     = $('#text-rates').val(); 
	var amount1  = $('#'+ id_input).val();
	var result1  = (rate * amount1).toFixed(2);
	$('#'+ id_result).val(result1);	
}

function calculate_rate_all() {
	var rate   = $('#text-rates').val();
	var result = "";
	var amount = {};
		amount[0] = $('#text-amount1').val();
		amount[1] = $('#text-amount2').val();
		amount[2] = $('#text-amount3').val();
		amount[3] = $('#text-amount4').val();
		amount[4] = $('#text-amount5').val();
	
	$.each(amount, function(key, value) {
		if (value){
			key++;
			result = (value * rate).toFixed(2);
			$('#text-rate'+ key).val(result);	
		}
	});
}

function form_upload () {
	var file_list = $('#form-invoice_files ul');

	$('#drop_file #browse_button').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$("#file-upfile").click();
	});

	// Initialize the jQuery File Upload plugin
	$('#form-invoice_files').fileupload({
		
		url: 'ajax.php?page=<?php echo $action; ?>',
		
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
			
				// FORM
				addForm (data.context, result.id_attachment);
				
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
		var tpl = $('<li><input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
			' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span>'+
			'<div class="invoice_file_form"></div></li>');
		
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
		var item = tpl.appendTo(file_list);
		item.find('input').val(progress).change();

		return item;
	}

	function addForm (item, file_id) {
		
		item.find(".invoice_file_form").html($("#file_description_table_hook").html());

		item.find("span").click(function(e) {
			addFileRow(file_id);
		});

		item.find("#submit-crt_btn").click(function(e) {
			e.preventDefault();
			
			$(this).hide();
			item.find("#textarea-description").prop("disabled", true);
			item.removeClass('working');
			item.removeClass('error');
			item.addClass('loading');

			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "include/ajax/invoices",
					update_file_description: true,
					id: <?php echo $id_invoice; ?>,
					id_attachment: file_id,
					file_description: function() { return item.find("#textarea-description").val() }
				},
				dataType: "json",
				success: function (data) {
					if (data.status) {
						item.removeClass('loading');
						item.addClass('suc');
						item.find('span').click();
					}
					else {
						item.find("#textarea-description").prop("disabled", false);
						item.find("#submit-crt_btn").show();
						item.removeClass('loading');
						item.removeClass('suc');
						item.addClass('error');
						item.find("p").text(data.message);
					}
				}
			});
		});

	}

	function addFileRow (file_id) {
		var no_files_message = $("#no_files_message");
		var table_files = $("#invoice_files");
		var check_table_hidden = <?php echo $check_table_hidden ?>;
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: {
				page: "include/ajax/invoices",
				get_file_row: true,
				id: <?php echo $id_invoice; ?>,
				id_attachment: file_id
			},
			dataType: "html",
			success: function (data) {
				if (no_files_message.length > 0) {
					no_files_message.remove();
					table_files.show();
				}
				if (check_table_hidden){
					$('#no_files_invoices').remove();
					$('#invoice_files').css('display', '');
				}
				table_files.find("tbody").append(data);
			}
		});
	}
}
</script>
