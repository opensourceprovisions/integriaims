<?php

global $config;
check_login ();

$res = include_once($config["homedir"].'include/functions_crm.php');

$id_invoice = (int) get_parameter ("id_invoice", -1);
if ($id_invoice == -1) {
	echo '<h3>'.__('The invoice number is required'),'</h3>';
	return;
}
$invoice = get_db_row ("tinvoice", "id", $id_invoice);
if (!$invoice) {
	echo '<h3>'.__('This invoice does not exists').'</h3>';
	return;
}

// ACL
if (!isset($permission)) {
	$id_company = get_db_value("id_company", "tinvoice", "id", $id_invoice);
	$permission = check_crm_acl ('invoice', '', $config['id_user'], $id_company);
}
if (!$permission) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to an invoice view without permission");
	no_permission();
} // ACL

$company_to = get_db_row ("tcompany", "id", $invoice["id_company"]);
$tax2 = get_db_value ('tax', 'tinvoice', 'id', $id_invoice);
$amount = get_invoice_amount ($id_invoice);
$tax = get_invoice_tax_sum ($id_invoice);

$irpf = get_invoice_irpf($id_invoice);
$tax_name = get_invoice_tax_name ($id_invoice);
$discount_before = get_invoice_discount_before ($id_invoice);
$concept_discount_before = get_concept_invoice_discount_before ($id_invoice);
$concept_retention = get_invoice_concept_retention($id_invoice);

//~ Descuento sobre el total
$before_amount = $amount * ($discount_before/100);
$total_before = round($amount - $before_amount, 2);
//~ Se aplica sobre el descuento los task 
$tax_amount = $total_before * ($tax/100);
//~ Se aplica sobre el descuento el irpf
$irpf_amount = $total_before * ($irpf/100);
$total = round($total_before + $tax_amount - $irpf_amount, 2);

$custom_pdf = true;
$pdf_filename = "invoice_".$invoice["bill_id"].".pdf";
$header_logo = "images/".$config["invoice_logo"];
$header_text = $config["invoice_header"];
$footer_text= $config["invoice_footer"];

if($invoice['rates']){
	$add_currency_change = $invoice['rates'] * $amount;
	$total_currency_change = $invoice['rates'] * $total;
}
// The template of the invoice view can be changed here
include ("invoice_template.php");

?>
