<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ======================================================
// Copyright (c) 2007-2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

enterprise_include('include/functions_crm.php', true);

function crm_get_companies_list ($sql_search, $date = false, $sql_order_by = "", $only_name = false, $sql_having = "") {
	global $config;
	
	if ($date) {
		$sql = "SELECT tcompany.*, (SELECT SUM(tinvoice.amount1 + tinvoice.amount2 + tinvoice.amount3 + tinvoice.amount4 + tinvoice.amount5)
									FROM tinvoice
									WHERE tcompany.id = tinvoice.id_company) AS billing
				FROM tcompany, tcompany_activity
				WHERE tcompany.id = tcompany_activity.id_company $sql_search
				GROUP BY tcompany.id
				$sql_having
				$sql_order_by
				";
	} else {
		$sql = "SELECT tcompany.*, (SELECT SUM(tinvoice.amount1 + tinvoice.amount2 + tinvoice.amount3 + tinvoice.amount4 + tinvoice.amount5)
									FROM tinvoice
									WHERE tcompany.id = tinvoice.id_company) AS billing
				FROM tcompany
				WHERE 1=1 $sql_search
				GROUP BY tcompany.id
				$sql_having
				$sql_order_by
				";
	}
	
	$companies = get_db_all_rows_sql($sql);
	if ($companies === false) {
		$companies = array();
	}

	$user_companies = enterprise_hook('crm_get_user_companies', array($config['id_user'], $companies, $only_name, $sql_search, $sql_order_by, $date, $sql_having));
	
	if ($user_companies !== ENTERPRISE_NOT_HOOK) {
		$companies = $user_companies;
	} else {
		if ($only_name) {
			$companies_name = array();
			foreach ($companies as $key=>$val)  {
				$companies_name[$val['id']] = $val['name']; 
			}
			$companies = $companies_name;
		}
	}
	
	return $companies;
}

function crm_get_company_name ($id_company) {
	
	$name = get_db_value('name', 'tcompany', 'id', $id_company);
	
	return $name;
}

//CHECK ACLS EXTERNAL USER
function crm_check_acl_external_user ($user, $id_company) {
	
	$user_data = get_db_row ('tusuario', 'id_usuario', $user);
	
	if ($user_data['id_company'] == $id_company) {
		return true;
	}
	return false;
}

// Checks if an invoice is locked. Returns 1 if is locked, 0 if not
// and false in case of error in the query.
function crm_is_invoice_locked ($id_invoice) {
	$locked = get_db_value('locked', 'tinvoice', 'id', $id_invoice);
	
	return $locked;
}

// Checks the id of the user that locked the invoice. Returns the id
// of the user in case of success or false in the case of the invoice
// does not exist or is not locked.
function crm_get_invoice_locked_id_user ($id_invoice) {
	
	if (!crm_is_invoice_locked ($id_invoice))
		return false;
	$user = get_db_value('locked_id_user', 'tinvoice', 'id', $id_invoice);
	
	return $user;
}

/**
 * Function to check if the user can lock the invoice.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function crm_check_lock_permission ($id_user, $id_invoice) {
	
	$return = enterprise_hook ('crm_check_lock_permission_extra', array ($id_user, $id_invoice));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

// Changes the lock state of an invoice. Returns -1 if the user have
// not permission to do this or the new lock state in case of success.
function crm_change_invoice_lock ($id_user, $id_invoice) {
	
	if (crm_check_lock_permission ($id_user, $id_invoice)) {
		
		$lock_status = crm_is_invoice_locked ($id_invoice);
		if ($lock_status == 1) {
			
			$values = array ('locked' => 0, 'locked_id_user' => NULL);
			$where = array ('id' => $id_invoice);
			if (process_sql_update ('tinvoice', $values, $where) > 0)
				return 0;
			return 1;
		} elseif ($lock_status == 0) {
			
			$values = array ('locked' => 1, 'locked_id_user' => $id_user);
			$where = array ('id' => $id_invoice);
			if (process_sql_update ('tinvoice', $values, $where) > 0)
				return 1;
			return 0;
		}
	}
	
	return -1;
}

function crm_get_all_leads ($where_clause, $order_by_clause = "ORDER BY creation DESC") {
	global $config;
	
	$sql = "SELECT * FROM tlead $where_clause $order_by_clause";
	$leads = get_db_all_rows_sql ($sql);
	
	$user_leads = enterprise_hook('crm_get_user_leads', array($config['id_user'], $leads));
	if ($user_leads !== ENTERPRISE_NOT_HOOK) {
		$leads = $user_leads;
	}
	
	return $leads;
}

function crm_get_all_contacts ($where_clause, $only_name = false) {
	global $config;
	
	$sql = "SELECT * FROM tcompany_contact $where_clause ORDER BY id_company, fullname";

	$contacts = get_db_all_rows_sql ($sql);
	
	$user_contacts = enterprise_hook('crm_get_user_contacts', array($config['id_user'], $contacts, $only_name));
	if ($user_contacts !== ENTERPRISE_NOT_HOOK) {
		$contacts = $user_contacts;
	} else {
		if ($only_name) {
			$contacts_name = array();
			foreach ($contacts as $key=>$val)  {
				$contacts_name[$val['id']] = $val['name']; 
			}
			$contacts = $contacts_name;
		}
	}
	
	return $contacts;
}

function crm_get_all_contracts ($where_clause, $order_by='date_end') {
	global $config;
	
	//$sql = "SELECT * FROM tcontract $where_clause ORDER BY date_end DESC";
	$sql = "SELECT * FROM tcontract $where_clause ORDER BY $order_by DESC";

	$contracts = get_db_all_rows_sql ($sql);
	if(!isset($only_name)){
		$only_name = '';
	}
	$user_contracts = enterprise_hook('crm_get_user_contracts', array($config['id_user'], $contracts, $only_name));
	if ($user_contracts !== ENTERPRISE_NOT_HOOK) {
		$contracts = $user_contracts;
	} else {
		if ($only_name) {
			$contracts_name = array();
			foreach ($contracts as $key=>$val)  {
				$contracts_name[$val['id']] = $val['name']; 
			}
			$contracts = $contracts_name;
		}
	}
	
	return $contracts;
}

function crm_get_all_contracts_with_custom_fields ($where_clause) {
	global $config;
	
	$sql = "select * from(
						select tc.*, tcf.label, tcfd.data 
						from tcontract as tc, tcontract_field as tcf, tcontract_field_data as tcfd  
						where tc.id= tcfd.id_contract and tcf.id = tcfd.id_contract_field and tcf.show_in_list = 1 " . $where_clause ."
	  	      		union 
						select tc.*, null as label, null as data 
						from tcontract as tc where 1=1 " . $where_clause . "
		      		) 
		as t order by 1";

	$contracts = get_db_all_rows_sql ($sql);
	
	//prepare $contracts
	if(is_array($contracts) || is_object($contracts)){
		
		//init vars
		$save_id = 0;
		$i=0;
		$k=1;
		$header=array();

		foreach ($contracts as $con) {
			//This controls whether there label and data, if not eliminate the array exists
			if($con['label'] == ''){
				unset($contracts[$i]['label']);
				unset($contracts[$i]['data']);
			}

			//This controls whether the following array has the same id
			if($save_id == $con['id']){
				//This controls exist label as key and data as value
				if(isset($con['label'])){
					//add array contracts label
					$contracts[$i-$k][$con['label']] = $con['data'];
					//for th table
					$header[$con['label']] = $con['label'];
				}
				//delete arrays with same id
				unset($contracts[$i]);
				$k++;	
			} else {
				//if isset label
				if(isset($con['label'])){
					//This controls exist label as key and data as value
					$contracts[$i][$con['label']] = $con['data'];
					//for th table
					$header[$con['label']] = $con['label'];
					//delete label and data
					unset($contracts[$i]['label']);
					unset($contracts[$i]['data']);
				}
				$k=1;
			}
			//keep the previous id
			$save_id = $con['id'];
			$i++;
		}
		//add new field for build table
		array_push($contracts , $header);
	}

	return $contracts;
}

function crm_get_all_invoices ($where_clause, $order_by='') {
	global $config;
	
	$sql = "SELECT * FROM tinvoice WHERE $where_clause ORDER BY invoice_create_date DESC";
	
	if ($order_by != '') {
		$sql = "SELECT * FROM tinvoice WHERE $where_clause ORDER BY $order_by DESC";
	}
	
	$invoices =  get_db_all_rows_sql ($sql);
	
	//~ if ($invoices_aux === false) {
		//~ $invoices_aux = array();
		//~ $invoices = false;
	//~ }
	//~ 
	//~ foreach ($invoices_aux as $key=>$invoice) {
		//~ $invoices[$key]['id'] = $invoice['id'];
		//~ $invoices[$key]['id_user'] = $invoice['id_user'];
		//~ $invoices[$key]['id_task'] = $invoice['id_task'];
		//~ $invoices[$key]['id_company'] = $invoice['id_company'];
		//~ $invoices[$key]['bill_id'] = $invoice['bill_id'];
		//~ $invoices[$key]['ammount'] = $invoice['ammount'];
		//~ $invoices[$key]['tax'] = $invoice['tax'];
		//~ $invoices[$key]['description'] = $invoice['description'];
		//~ $invoices[$key]['locked'] = $invoice['locked'];
		//~ $invoices[$key]['locked_id_user'] = $invoice['locked_id_user'];
		//~ $invoices[$key]['invoice_create_date'] = $invoice['invoice_create_date'];
		//~ $invoices[$key]['invoice_payment_date'] = $invoice['invoice_payment_date'];
		//~ $invoices[$key]['status'] = $invoice['status'];
	//~ 
	//~ }
	
	$user_invoices = enterprise_hook('crm_get_user_invoices', array($config['id_user'], $invoices));
	if ($user_invoices !== ENTERPRISE_NOT_HOOK) {
		$invoices = $user_invoices;
	}
	
	return $invoices;
}

// sum total invoices
function crm_get_total_invoiced($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company as id, SUM(amount1+amount2+amount3+amount4+amount5) as total_amount FROM tinvoice
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			GROUP BY id_company
			ORDER BY total_amount DESC
			";
	} else {
		$sql = "SELECT id_company as id, SUM(amount1+amount2+amount3+amount4+amount5) as total_amount FROM tinvoice
			GROUP BY id_company
			ORDER BY total_amount DESC
			";
	}
	
	
	$total = process_sql ($sql);

	return $total;
}

function crm_get_total_invoiced_graph($companies) {

	if (!$companies) {
		return false;
	}

	$companies_invoincing = array();
	foreach ($companies as $company) {
		if ($company["total_amount"] > 0) {

			$companies_invoincing[crm_get_company_name ($company['id'])] = $company["total_amount"];
		}
	}
	
	return $companies_invoincing;
}

//print top 10 invoices
function crm_print_most_invoicing_companies($companies) {
	
	$table->id = 'company_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Company');
	$table->head[1] = __('Invoiced');
	
	$i = 0;
	foreach ($companies as $key=>$company) {
	
		if ($i < 10 && $company['total_amount'] > 0) {
			$data = array();
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$company['id']."'>"
				. crm_get_company_name ($company['id']) . "</a>";

			$data[1] = format_numeric($company['total_amount']);

			array_push ($table->data, $data);
		}
		$i++;
	}
	
	return $table;
}

function crm_get_managers_invoicing ($where_clause = false) {

	if ($where_clause) {
		$sql = "SELECT id_user AS manager, SUM(amount1+amount2+amount3+amount4+amount5) AS total_amount
				FROM tinvoice
				WHERE id_user IN (SELECT tcompany.manager
								  FROM tcompany
								  WHERE 1=1 $where_clause)
				GROUP BY manager
				ORDER BY total_amount DESC";
	} else {
		$sql = "SELECT id_user AS manager, SUM(amount1+amount2+amount3+amount4+amount5) AS total_amount
				FROM tinvoice
				GROUP BY manager
				ORDER BY total_amount DESC";
	}
	
	$total = process_sql ($sql);

	return $total;

}

function crm_get_managers_invoicing_graph($managers) {

	if (!$managers) {
		return false;
	}

	$managers_invoincing = array();
	foreach ($managers as $manager) {
		if ($manager["total_amount"] > 0) {

			$managers_invoincing[$manager['manager']] = $manager["total_amount"];
		}
	}
	
	return $managers_invoincing;
}

//print top 10 manager invoices
function crm_print_most_invoicing_managers($managers) {
	
	$table->id = 'manager_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Manager');
	$table->head[1] = __('Invoiced');
	
	$i = 0;
	foreach ($managers as $key=>$manager) {
	
		if ($i < 10 && $manager['total_amount'] > 0) {
			$data = array();
			$data[0] = $manager['manager'];
			$data[1] = format_numeric($manager['total_amount']);

			array_push ($table->data, $data);
		}
		$i++;
	}
	
	return $table;
}

// count total activity
function crm_get_total_activity($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company as id, count(id) as total_activity FROM tcompany_activity
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			GROUP BY id_company
			ORDER BY total_activity DESC
			";
	} else {
		$sql = "SELECT id_company as id, count(id) as total_activity FROM tcompany_activity
			GROUP BY id_company
			ORDER BY total_activity DESC
			";
	}

	$activity_total = process_sql ($sql);

	return $activity_total;
}

//print top 10 activities
function crm_print_most_activity_companies($companies) {
	
	$table->id = 'company_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Company');
	$table->head[1] = __('Number');
	
	$i = 0;
	foreach ($companies as $key=>$company) {
	
		if ($i < 10) {
			$data = array();
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$company['id']."'>"
				. crm_get_company_name ($company['id']) . "</a>";

			$data[1] = $company['total_activity'];

			array_push ($table->data, $data);
		}
		$i++;
	}
	
	//print_table($table);
	return $table;
}

// count companies per country
function crm_get_total_country($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT country, count(id) as total_companies FROM tcompany
			WHERE id IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			AND country<>''
			GROUP BY country
			ORDER BY total_companies DESC
			";
	} else {
		$sql = "SELECT country, count(id) as total_companies FROM tcompany
			WHERE country<>''
			GROUP BY country
			ORDER BY total_companies DESC
			";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_country_graph($companies) {
	
	global $config;
	
	if ($companies === false) {
		return false;
	}
    
	require_once ("include/functions_graph.php");  
	
	$company_country = array();
	$i = 0;
	foreach ($companies as $key=>$company) {
		if ($i < 7) {
			$company_country[$company['country']] = $company['total_companies'];
		}
		$i++;
	}

	return $company_country;
}

// count users per company
function crm_get_total_user($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company, count(id_company) as total_users FROM tusuario
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			AND id_company<>0
			GROUP BY id_company
			ORDER BY total_users DESC
			";
	} else {
		$sql = "SELECT id_company, count(id_company) as total_users FROM tusuario
			WHERE id_company<>0
			GROUP BY id_company
			ORDER BY total_users DESC
			";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_user_graph($companies) {	
	global $config;
    
    if ($companies === false) {
		return false;
	}
	
	require_once ("include/functions_graph.php");  
	
	$company_user = array();
	$i = 0;
	foreach ($companies as $key=>$company) {
		if ($i < 10) {
			$company_name = crm_get_company_name($company['id_company']);
			$company_user[$company_name] = $company['total_users'];
		}
	}
	return $company_user;
}

// count leads per country
function crm_get_total_leads_country($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT country, count(id) as total_leads FROM tlead
			WHERE id IN (SELECT id FROM tlead
					 $where_clause)
			AND country<>''
			GROUP BY country
			ORDER BY total_leads DESC
			";
	} else {
		$sql = "SELECT country, count(id) as total_leads FROM tlead
			WHERE country<>''
			GROUP BY country
			ORDER BY total_leads DESC
			";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_lead_country_graph($leads) {
	
	global $config;
	
	if ($leads === false) {
		return false;
	}
    
	require_once ("include/functions_graph.php");  
	
	$lead_country = array();
	$i = 0;
	foreach ($leads as $key=>$lead) {
		if ($i < 7) {
			$lead_country[$lead['country']] = $lead['total_leads'];
		}
		$i++;
	}
	return $lead_country;
}

function crm_get_total_leads_funnel ($where_clause=false, $user = false) {
	
	//Sucess clause
	if (!$user) {
		$user_clasue = " OR progress = 200";
	} else {
		$user_clasue = " OR (owner = $user AND progress = 200)";
	}
	if(!isset($user_clause)){
		$user_clause = '';
	}
	if ($where_clause) {
		$sql = "SELECT COUNT(id) as total_leads, SUM(estimated_sale) as amount, progress, owner FROM tlead
			WHERE id IN (SELECT id FROM tlead
					 $where_clause $user_clause)
			GROUP BY progress
			ORDER BY total_leads DESC";
	} else {
		$sql = "SELECT COUNT(id) as total_leads, SUM(estimated_sale) as amount, progress, owner FROM tlead
			GROUP BY progress
			ORDER BY total_leads DESC";
	}
	
	$total = process_sql ($sql);

	return $total;
}

// count users per lead
function crm_get_total_leads_user($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT owner, count(id) as total_users FROM tlead
			WHERE id IN (SELECT id FROM tlead
					 $where_clause)
			AND owner<>''
			GROUP BY owner
			ORDER BY total_users DESC
			";
	} else {
		$sql = "SELECT owner, count(id) as total_users FROM tlead
			WHERE owner<>''
			GROUP BY owner
			ORDER BY total_users DESC
			";
	}
	
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_lead_user_graph($leads) {	
	global $config;
    
    if ($leads === false) {
		return false;
	}

	require_once ("include/functions_graph.php");  
	
	$lead_user = array();
	$i = 0;
	foreach ($leads as $key=>$lead) {
		if ($i < 7) {
			$lead_user[$lead['owner']] = $lead['total_users'];
		}
		$i++;
	}

	return $lead_user;
}

// sum estimated sales
function crm_get_total_sales_lead($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id, company, country, estimated_sale FROM tlead
			WHERE id IN (SELECT id FROM tlead
					$where_clause)
			AND estimated_sale<>0
			ORDER BY estimated_sale DESC
			";
	} else {
		$sql = "SELECT id, company, country, estimated_sale FROM tlead
			WHERE estimated_sale<>0
			ORDER BY estimated_sale DESC
			";
	}
	
	$total = process_sql ($sql);

	return $total;
}

function crm_print_estimated_sales_leads($leads) {
	$table->id = 'lead_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Company');
	$table->head[1] = __('Country');
	$table->head[2] = __('Total');
	
	$i = 0;

	foreach ($leads as $key=>$lead) {
	
		if ($i < 10) {
			$data = array();
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".$lead["id"]."'>".$lead['company']."</a>";
			$data[1] = "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".$lead["id"]."'>".$lead['country']."</a>";
			$data[2] = "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&id=".$lead["id"]."'>".$lead['estimated_sale']."</a>";

			array_push ($table->data, $data);
		}
		$i++;
	}

	return $table;
}

// all leads by creation date
function crm_get_total_leads_creation($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id, creation FROM tlead
			WHERE id IN (SELECT id FROM tlead
					$where_clause)
			ORDER BY creation 
			";
	} else {
		$sql = "SELECT id, creation FROM tlead
			ORDER BY creation
			";
	}
	
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_lead_creation_graph($data) {	
	global $config;

    if ($data === false) {
		return false;
	}

	require_once ($config['homedir']."include/functions_graph.php");  
	
	$start_unixdate = strtotime ($data[0]["creation"]);
    $end_unixdate = strtotime ("now");
    $period = $end_unixdate - $start_unixdate;
    $resolution = 10;
    
	$interval = (int) ($period / $resolution);

   	$min_necessary = 1;

	// Check available data
	if (count ($data) < $min_necessary) {
		return;
	}

	// Set initial conditions
	$chart = array();
	$names = array();
	$chart2 = array();

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $start_unixdate + ($interval * $i);
		$total = 0;
		$j = 0;

		while (isset ($data[$j])){
            $dftime = strtotime($data[$j]['creation']);

			if ($dftime >= $timestamp && $dftime < ($timestamp + $interval)) {
				$total += 1;
			}
			$j++;
		} 

    	$time_format = "M d H:i";
        $timestamp_human = clean_flash_string (date($time_format, $timestamp));
		$chart2[$timestamp_human]['leads'] = $total;
   	}
   	
   	return $chart2;
}

function crm_get_all_languages () {

	$languages = process_sql('SELECT id_language, name FROM tlanguage ORDER BY name');
	
	if ($languages === false) {
		$languages = array();
	}
	
	$all_languages = array();
	foreach ($languages as $key=>$language) {
		$all_languages[$language['id_language']] = $language['name'];
	}
	
	return $all_languages;
}

function crm_get_all_companies ($only_name = false) {
	
	$sql = "SELECT * FROM tcompany ORDER BY name";

	$companies = get_db_all_rows_sql ($sql);
	
	if ($only_name) {
		if ($companies === false) {
			return false;
		} else {
			$all_companies = array();
			foreach ($companies as $key=>$company) {
				$all_companies[$company['id']] = $company['name'];
			}
		}
		return $all_companies;
	}
	return $companies;
}

function crm_get_contact_files ($id_contact, $order_desc = false) {
	if($order_desc) {
			$order = "id_attachment DESC";
	}
	else { 
			$order = "";
	}

	return get_db_all_rows_field_filter ('tattachment', 'id_contact', $id_contact, $order);
}

// Count companies per owner
function crm_get_total_managers($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT manager, count(id) AS total_companies FROM tcompany
				WHERE id IN (SELECT id
								FROM tcompany
								WHERE 1=1 $where_clause)
					AND manager<>''
				GROUP BY manager
				ORDER BY total_companies DESC
				";
	} else {
		$sql = "SELECT manager, count(id) AS total_companies FROM tcompany
				WHERE manager<>''
				GROUP BY manager
				ORDER BY total_companies DESC
				";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_managers_graph($managers) {
	
	global $config;
	
	if ($managers === false) {
		return false;
	}
    
	require_once ("include/functions_graph.php");  
	
	$managers_companies = array();
	$i = 0;
	foreach ($managers as $key=>$manager) {
		if ($i < 7) {
			$managers_companies[$manager['manager']] = $manager['total_companies'];
		}
		$i++;
	}

	return $managers_companies;
}

function get_contract_expire_days () {
	$expire_days = array();
	$expire_days[7] = '< 7 ' . __('days');
	$expire_days[15] = '< 15 ' . __('days');
	$expire_days[30] = '< 30 ' . __('days');
	$expire_days[90] = '< 90 ' . __('days');
	
	return $expire_days;
}

function get_contract_status () {
	$status = array();
	$status[0] = __('Inactive');
	$status[1] = __('Active');
	$status[2] = __('Pending');
	
	return $status;
}

function get_contract_status_name ($status) {
	switch ($status) {
		case 0:
			$status = __('Inactive');
			break;
		case 1:
			$status = __('Active');
			break;
		case 2:
			$status = __('Pending');
			break;
		default:
			$status = __('Active');
	}
	return $status;
}

/*
 * This function get access permissions to CRM 
*/
function check_crm_acl ($type, $flag, $user=false, $id=false) {
	global $config;
	
	if (!$user) {
		$user = $config['id_user'];
	}
	
	$permission = false;
	
	switch ($type) {
		case 'company':
			if ($id) {
				$permission = enterprise_hook('crm_check_acl_company', array($user, $id, $flag));
			} else {
				$permission = enterprise_hook('crm_check_user_profile', array($user, $flag));
			}
			break;
		
		case 'other':
			if ($id) {
				$permission = enterprise_hook('crm_check_acl_other', array($user, $id, $flag));
			}
			break;
		
		case 'invoice':
			if ($id) {
				$permission = enterprise_hook('crm_check_acl_invoice', array($user, $id));
			}
			break;
		
		case 'lead':
			if ($id) {
				$permission = enterprise_hook('crm_check_acl_lead', array($user, $id, $flag));
			} else {
				$permission = enterprise_hook('crm_check_user_profile', array($user, $flag));
			}
			break;
			
		case 'contract':
			if ($id) {
				$permission = enterprise_hook('crm_check_acl_contract', array($user, $id, $flag));
			} else {
				$permission = enterprise_hook('crm_check_user_profile', array($user, $flag));
			}
			break;
	}
	
	if ($permission === ENTERPRISE_NOT_HOOK) {
		$permission = true;
	}
	
	return $permission;
}

function crm_check_if_exists ($id, $companies) {
	foreach ($companies as $company) {
		if ($company == $id) {
			return true;
		}
	}
	return false;
}

function crm_get_campaigns () {
	$campaigns = get_db_all_rows_in_table ("tcampaign");

	return $campaigns;
}

function crm_get_campaigns_combo_list () {
	$campaigns = crm_get_campaigns();

	if (!$campaigns) {
		return array();
	}

	$result = array();
	
	foreach ($campaigns as $camp) {
		$result[$camp["id"]] = $camp["title"];
	}

	return $result;
}

function crm_get_amount_total_invoice($field_invoice) {
	
	$total = $field_invoice['amount1']+$field_invoice['amount2']+$field_invoice['amount3']+$field_invoice['amount4']+$field_invoice['amount5'];
	return $total;
}

function crm_attach_contract_file ($id, $file_temp, $file_description, $file_name = "") {
	global $config;
	
	$file_temp = safe_output ($file_temp); // Decoding HTML entities
	$filesize = filesize($file_temp); // In bytes
	if ($file_name != "") {
		$filename = $file_name;
	} else {
		$filename = basename($file_temp);
	}
	
	$filename = str_replace (array(" ", "(", ")"), "_", $filename); // Replace blank spaces
	$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
	
	$timestamp = date('Y-m-d');
	$sql = sprintf ('INSERT INTO tattachment (id_contract, id_usuario,
			filename, description, size, timestamp)
			VALUES (%d, "%s", "%s", "%s", %d, "%s")',
			$id, $config['id_user'], $filename, $file_description, $filesize, $timestamp);
	
	$id_attachment = process_sql ($sql, 'insert_id');
	
	return $id_attachment;
}

function crm_get_contract_files ($id_contract, $order_desc = false) {
	if($order_desc) {
		$order = "id_attachment DESC";
	}
	else {
		$order = "";
	}
	
	return get_db_all_rows_field_filter ('tattachment', 'id_contract', $id_contract, $order);
}

function crm_print_company_projects_tree($projects) {
	
	require_once ("include/functions_tasks.php");  
	
	//~ echo '<table width="100%" cellpadding="0" cellspacing="0" border="0px" class="result_table listing" id="incident_search_result_table">';
		$img = print_image ("images/input_create.png", true, array ("style" => 'vertical-align: middle;', "id" => $img_id));
		$img_project = print_image ("images/note.png", true, array ("style" => 'vertical-align: middle;'));
		
		foreach ($projects as $project) {
			$project_name = get_db_value('name', 'tproject', 'id', $project['id_project']);
			//print project name
			//~ echo '<tr><td colspan="10" valign="top">';
				//~ echo "
				//~ <a onfocus='JavaScript: this.blur()' href='javascript: show_detail(\"" . $project['id_project']. "\")'>" .
				//~ $img . "&nbsp;" . $img_project ."&nbsp;" .  safe_output($project_name)."&nbsp;</a>"."&nbsp;&nbsp;";
			//~ echo '</td></tr>';

			$id_project = $project['id_project'];
			$people_inv = get_db_sql ("SELECT COUNT(DISTINCT id_user) FROM trole_people_task, ttask WHERE ttask.id_project=$id_project AND ttask.id = trole_people_task.id_task;");
			$total_hr = get_project_workunit_hours ($id_project);
			$total_planned = get_planned_project_workunit_hours($id_project);
			$project_data = get_db_row ('tproject', 'id', $id_project);			
			$start_date = $project_data["start"];
			$end_date = $project_data["end"];

			// Project detail
			$table_detail = "<table class='advanced_details_table alternate'>";
			
			$table_detail .= "<tr>";
			$table_detail .= '<td><b>'.__('Start date').' </b>';
			$table_detail .= "</td><td>";
			$table_detail .= $start_date;
			$table_detail .= "</td></tr>";
			
			$table_detail .= "<tr>";
			$table_detail .= '<td><b>'.__('End date').' </b>';
			$table_detail .= "</td><td>";
			$table_detail .= $end_date;
			$table_detail .= "</td></tr>";
			
			$table_detail .= "<tr>";
			$table_detail .= '<td><b>'.__('Total people involved').' </b>';
			$table_detail .= "</td><td>";
			$table_detail .= $people_inv;
			$table_detail .= "</td></tr>";
			
			//People involved (avatars)
			//Get users with tasks
			$sql = sprintf("SELECT DISTINCT id_user FROM trole_people_task, ttask WHERE ttask.id_project= %d AND ttask.id = trole_people_task.id_task", $id_project);

			$users_aux = get_db_all_rows_sql($sql);

			if(empty($users_aux)) {
				$users_aux = array();
			}
			
			$users_involved = array();

			foreach ($users_aux as $ua) {
				$users_involved[] = $ua['id_user'];
			}

			//Delete duplicated items
			if (empty($users_involved)) {
				$users_involved = array();
			}
			else {
				$users_involved = array_unique($users_involved);
			}

			$people_involved = "<div style='padding-bottom: 20px;'>";
			foreach ($users_involved as $u) {
				$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $u);
				if ($avatar != "") {
					$people_involved .= "<img src='images/avatars/".$avatar.".png' width=40 height=40 onclick='openUserInfo(\"$u\")' title='".$u."'/>";
				}
				else
					$people_involved .= "<img src='images/avatars/avatar_notyet.png' width=40 height=40 onclick='openUserInfo(\"$u\")' title='".$u."'/>";
			}
			$people_involved .= "</div>";

			$table_detail .= "<tr><td colspan='10'>";
			$table_detail .= $people_involved;
			$table_detail .= "</td></tr>";
			
			$table_detail .= "<tr>";
			$table_detail .= '<td><b>'.__('Total workunit (hr)').' </b>';
			$table_detail .= "</td><td>";
			$table_detail .= $total_hr . " (".format_numeric ($total_hr/$config["hours_perday"]). " ".__("days"). ")";
			$table_detail .= "</td></tr>";

			$table_detail .= "<tr>";
			$table_detail .= '<td><b>'.__('Planned workunit (hr)').' </b>';
			$table_detail .= "</td><td>";
			$table_detail .= $total_planned . " (".format_numeric ($total_planned/$config["hours_perday"]). " ". __("days"). ")";
			$table_detail .= "</td></tr>";
			
			$table_detail .= "</table>";

			$class = $project['id_project']."-project";
			$tr_status = 'class="'.$class.'"';
			
			//~ echo '<tr '.$tr_status.'><td>';
			print_container_div("project_".$project['id_project'], $project_name, $table_detail, 'closed', false, true, '', '', 1, '', 'width:32%; float:left;');
			//~ echo '</td></tr>';
			
		}
		
	//~ echo '</table>';
}

function crm_get_last_invoice_id () {
	global $config;

	$pattern = $config['invoice_id_pattern'];
	$results_pattern = preg_match('/(.*)\[.*\]/', $pattern, $matches);

	$sql = "SELECT bill_id FROM tinvoice WHERE bill_id_pattern = '".$pattern."' AND invoice_type = 'Submitted' AND bill_id LIKE '%".$matches[1]."%' ORDER BY bill_id DESC LIMIT 1";
	$last_id = get_db_sql($sql);

	if ($last_id == false) {
		preg_match('/.*\[(.*)\]/', $pattern, $other_matches);
		$last_id = $other_matches[1];
	}

	return $last_id;
}

function crm_get_next_invoice_id () {
	global $config;

	$pattern = $config['invoice_id_pattern'];
	$results_pattern = preg_match('/(.*)\[.*\]/', $pattern, $matches);

	$sql = "SELECT bill_id FROM tinvoice WHERE bill_id_pattern = '".$pattern."' AND invoice_type = 'Submitted' AND bill_id LIKE '%".$matches[1]."%' ORDER BY bill_id DESC LIMIT 1";
	$last_id = get_db_sql($sql);

	$last_id_variable = substr($last_id,strlen($matches[1]),strlen($last_id));	

	if ($last_id_variable) {
		$last_id = $last_id_variable+1;
	} else {
		preg_match('/.*\[(.*)\]/', $pattern, $other_matches);
		$last_id = $other_matches[1]+1;
	}

	preg_match('/.*\[.*(\].*)/', $pattern, $matches);

	$result_id = substr_replace ($pattern, $last_id, strpos($pattern, "["));
	$final = str_replace ("]", "", $matches[1]);
	$result_id .= $final;

	return $result_id;
}
?>
