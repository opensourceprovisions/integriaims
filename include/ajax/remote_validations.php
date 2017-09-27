<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
$search_existing_template_name = (bool) get_parameter ('search_existing_template_name');
$search_existing_project = (bool) get_parameter ('search_existing_project');
$search_name_category = (bool) get_parameter ('search_name_category');
$search_existing_task = (bool) get_parameter ('search_existing_task');
$search_existing_incident = (bool) get_parameter ('search_existing_incident');
$search_existing_incident_type = (bool) get_parameter ('search_existing_incident_type');
$search_existing_project_group = (bool) get_parameter ('search_existing_project_group');
$search_existing_sla = (bool) get_parameter ('search_existing_sla');
$search_existing_object = (bool) get_parameter ('search_existing_object');
$search_existing_object_type = (bool) get_parameter ('search_existing_object_type');
$search_existing_object_type_field = (bool) get_parameter ('search_existing_object_type_field');
$search_existing_object_type_field_data = (bool) get_parameter ('search_existing_object_type_field_data');
$search_existing_manufacturer = (bool) get_parameter ('search_existing_manufacturer');
$search_existing_company = (bool) get_parameter ('search_existing_company');
$search_existing_fiscal_id = (bool) get_parameter ('search_existing_fiscal_id');
$search_existing_company_role = (bool) get_parameter ('search_existing_company_role');
$search_existing_invoice = (bool) get_parameter ('search_existing_invoice');
$search_existing_inventory = (bool) get_parameter ('search_existing_inventory');
$search_existing_contract = (bool) get_parameter ('search_existing_contract');
$search_existing_contract_number = (bool) get_parameter ('search_existing_contract_number');
$search_existing_contact = (bool) get_parameter ('search_existing_contact');
$search_existing_contact_email = (bool) get_parameter ('search_existing_contact_email');
$search_existing_lead = (bool) get_parameter ('search_existing_lead');
$search_existing_lead_email = (bool) get_parameter ('search_existing_lead_email');
$search_existing_crm_template = (bool) get_parameter ('search_existing_crm_template');
$search_existing_kb_item = (bool) get_parameter ('search_existing_kb_item');
$search_existing_kb_category = (bool) get_parameter ('search_existing_kb_category');
$search_existing_product_type = (bool) get_parameter ('search_existing_product_type');
$search_existing_download = (bool) get_parameter ('search_existing_download');
$search_existing_file_category = (bool) get_parameter ('search_existing_file_category');
$search_existing_file_type = (bool) get_parameter ('search_existing_file_type');
$search_existing_user_id = (bool) get_parameter ('search_existing_user_id');
$search_non_existing_user_id = (bool) get_parameter ('search_non_existing_user_id');
$search_existing_user_name = (bool) get_parameter ('search_existing_user_name');
$search_existing_user_num = (bool) get_parameter ('search_existing_user_num');
$search_existing_user_email = (bool) get_parameter ('search_existing_user_email');
$search_existing_role = (bool) get_parameter ('search_existing_role');
$search_existing_group = (bool) get_parameter ('search_existing_group');
$search_duplicate_name = (bool) get_parameter ('search_duplicate_name');
$search_input_number = (bool) get_parameter ('search_input_number');
$check_mail = (bool) get_parameter ('check_mail');
$check_user_name = (bool) get_parameter ('check_user_name');
$check_allowed_users = (bool) get_parameter ('check_allowed_users', 0);

if ($search_existing_project) {
	require_once ('include/functions_db.php');
	
	$project_name = get_parameter ('project_name');
	$project_id = (int) get_parameter ('project_id');
	$old_project_name = "";
	
	// If edition mode, get the name of editing project
	if ($project_id) {
		$old_project_name = get_db_value("name", "tproject", "id", $project_id);
	}
	
	// Checks if the project is in the db
	$query_result = get_db_value("name", "tproject", "name", $project_name);
	if ($query_result) {
		if ($project_name != $old_project_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_name_category) {
	require_once ('include/functions_db.php');
	
	$category_name = get_parameter ('category_name');
	$category_id = (int) get_parameter ('category_id');
	$old_category_name = "";
	
	// If edition mode, get the name of editing project
	if ($category_id) {
		$old_category_name = get_db_value("name", "two_category", "id", $category_id);
	}
	
	// Checks if the project is in the db
	$query_result = get_db_value("name", "two_category", "name", $category_name);
	if ($query_result) {
		if ($category_name != $old_category_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;

}
elseif ($search_existing_task) {
	require_once ('include/functions_db.php');
	
	$project_id = (int) get_parameter ('project_id');
	$operation_type = (string) get_parameter ('type');
	
	if ($operation_type == "create") {
		
		$tasks_names = get_parameter ('task_name');
		$tasks_names = safe_output($tasks_names);
		$tasks_names = preg_split ("/\n/", $tasks_names);
		
		foreach ($tasks_names as $task_name) {
			$task_name = safe_input($task_name);
			$query_result = get_db_value_filter ("name", "ttask",
				array('name' => $task_name, 'id_project' => $project_id));
			if ($query_result) {
				// Exists. Validation error
				echo json_encode(false);
				return;
			}
		}
		
	}
	elseif ($operation_type == "view") {
		
		$task_name = get_parameter ('task_name');
		$old_task_id = get_parameter ('task_id');
		
		if (!$project_id) {
			$project_id = get_db_value("id_project", "ttask", "id", $old_task_id);
		}
		// Name of the edited task
		$old_task_name = get_db_value("name", "ttask", "id", $old_task_id);
		
		// Checks if the task is in the db
		$query_result = get_db_value_filter ("name", "ttask",
			array('name' => $task_name, 'id_project' => $project_id));
		if ($query_result) {
			if ($query_result != $old_task_name) {
				// Exists. Validation error
				echo json_encode(false);
				return;
			}
		}
		
	}
	
	// Does not exist or is the edited
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_incident) {
	require_once ('include/functions_db.php');
	$incident_name = get_parameter ('incident_name');
	$incident_id = get_parameter ('incident_id', 0);
	$old_incident_name = "";
	
	if ($incident_id) {
		$old_incident_name = get_db_value("titulo", "tincidencia", "id_incidencia", $incident_id);
	}
	
	// Checks if the incident is in the db
	$query_result = get_db_value("titulo", "tincidencia", "titulo", $incident_name);
	if ($query_result) {
		if ($incident_name != $old_incident_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;

}
elseif ($search_input_number){
	$input_number = get_parameter ('input_number');
		if(is_numeric($input_number)) {
			echo json_encode(true);
			return;
		} else {
			echo json_encode(false);
			return;
		}


}
elseif ($search_existing_incident_type) {
	require_once ('include/functions_db.php');
	$incident_type_name = get_parameter ('type_name');
	$incident_type_id = get_parameter ('type_id', 0);
	$old_incident_type_name = "";
	
	if ($incident_type_id) {
		$old_incident_type_name = get_db_value("name", "tincident_type", "id", $incident_type_id);
	}
	
	// Checks if the incident type is in the db
	$query_result = get_db_value("name", "tincident_type", "name", $incident_type_name);
	if ($query_result) {
		if ($incident_type_name != $old_incident_type_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_project_group) {
	require_once ('include/functions_db.php');
	$group_name = get_parameter ('group_name');
	$group_id = get_parameter ('group_id', 0);
	$old_group_name = "";
	
	if ($group_id) {
		$old_group_name = get_db_value("name", "tproject_group", "id", $group_id);
	}
	
	// Checks if the group is in the db
	$query_result = get_db_value("name", "tproject_group", "name", $group_name);
	if ($query_result) {
		if ($group_name != $old_group_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_sla) {
	require_once ('include/functions_db.php');
	$sla_name = get_parameter ('sla_name');
	$sla_id = get_parameter ('sla_id', 0);
	$old_sla_name = "";
	
	if ($sla_id) {
		$old_sla_name = get_db_value("name", "tsla", "id", $sla_id);
	}
	
	// Checks if the sla is in the db
	$query_result = get_db_value("name", "tsla", "name", $sla_name);
	if ($query_result) {
		if ($sla_name != $old_sla_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_object) {
	require_once ('include/functions_db.php');
	$object_name = get_parameter ('object_name');
	$object_id = get_parameter ('object_id', 0);
	$old_object_name = "";
	
	if ($object_id) {
		$old_object_name = get_db_value("name", "tinventory", "id", $object_id);
	}
	
	// Checks if the object is in the db
	$query_result = get_db_value("name", "tinventory", "name", $object_name);
	if ($query_result) {
		if ($object_name != $old_object_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_object_type) {
	require_once ('include/functions_db.php');
	$object_type_name = get_parameter ('object_type_name');
	$object_type_id = get_parameter ('object_type_id', 0);
	$old_object_type_name = "";
	
	if ($object_type_id) {
		$old_object_type_name = get_db_value("name", "tobject_type", "id", $object_type_id);
	}
	
	// Checks if the object type is in the db
	$query_result = get_db_value("name", "tobject_type", "name", $object_type_name);
	if ($query_result) {
		if ($object_type_name != $old_object_type_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_object_type_field) {
	require_once ('include/functions_db.php');
	$object_type_field_name = get_parameter ('object_type_field_name');
	$object_type_id = get_parameter ('object_type_id');
	$object_type_field_id = get_parameter ('object_type_field_id', 0);
	$old_object_type_field_name = "";
	
	if ($object_type_field_id) {
		$old_object_type_field_name = get_db_value("label", "tobject_type_field", "id", $object_type_field_id);
	}
	
	// Checks if the object type field is in the db
	$query_result = get_db_value_filter ("label", "tobject_type_field",
			array('label' => $object_type_field_name, 'id_object_type' => $object_type_id));
	if ($query_result) {
		if ($object_type_field_name != $old_object_type_field_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_object_type_field_data) {
	require_once ('include/functions_db.php');
	require_once ('include/functions_inventories.php');
	$object_type_field_data = get_parameter ('object_type_field_data');
	$object_type_field_id = get_parameter ('object_type_field_id');
	$object_type_field_type = get_parameter ('object_type_field_type');
	$inventory_id = get_parameter ('inventory_id', 0);
	$unique = get_db_value("unique", "tobject_type_field", "id", $object_type_field_id);
	
	// Checks if the object type field data brokes any unique constraint in the db
	if ($unique) {
		if (!inventories_check_unique_field($object_type_field_data,
				$object_type_field_type)) {
			if ($inventory_id) {
				$query_result = get_db_value_filter ("id", "from tobject_field_data",
						array('id_inventory' => $inventory_id,
							'data' => $object_type_field_data));
				if (!$query_result) {
					// Exists. Validation error
					echo json_encode(false);
					return;
				}
			} else {
				// Exists. Validation error
				echo json_encode(false);
				return;
			}
		}
	} else {
		if (!inventories_check_no_unique_field($object_type_field_data,
				$object_type_field_type)) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_manufacturer) {
	require_once ('include/functions_db.php');
	$manufacturer_name = get_parameter ('manufacturer_name');
	$manufacturer_id = get_parameter ('manufacturer_id', 0);
	$old_manufacturer_name = "";
	
	if ($manufacturer_id) {
		$old_manufacturer_name = get_db_value("name", "tmanufacturer", "id", $manufacturer_id);
	}
	
	// Checks if the manufacturer is in the db
	$query_result = get_db_value("name", "tmanufacturer", "name", $manufacturer_name);
	if ($query_result) {
		if ($manufacturer_name != $old_manufacturer_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_company) {
	require_once ('include/functions_db.php');
	$company_name = get_parameter ('company_name');
	$company_id = get_parameter ('company_id', 0);
	$old_company_name = "";
	
	if ($company_id) {
		$old_company_name = get_db_value("name", "tcompany", "id", $company_id);
	}
	
	// Checks if the company is in the db
	$query_result = get_db_value("name", "tcompany", "name", $company_name);
	if ($query_result) {
		if ($company_name != $old_company_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_fiscal_id) {
	require_once ('include/functions_db.php');
	$fiscal_id = get_parameter ('fiscal_id');
	$company_id = get_parameter ('company_id', 0);
	$old_fiscal_id = -1;
	
	if ($company_id) {
		$old_fiscal_id = get_db_value("fiscal_id", "tcompany", "id", $company_id);
	}
	
	// Checks if the fiscal id is in the db
	$query_result = get_db_value("fiscal_id", "tcompany", "fiscal_id", $fiscal_id);
	if ($query_result) {
		if ($fiscal_id != $old_fiscal_id) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_company_role) {
	require_once ('include/functions_db.php');
	$company_role_name = get_parameter ('company_role_name');
	$company_role_id = get_parameter ('company_role_id', 0);
	$old_company_role_name = "";
	
	if ($company_role_id) {
		$old_company_role_name = get_db_value("name", "tcompany_role", "id", $company_role_id);
	}
	
	// Checks if the company role is in the db
	$query_result = get_db_value("name", "tcompany_role", "name", $company_role_name);
	if ($query_result) {
		if ($company_role_name != $old_company_role_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_invoice) {
	require_once ('include/functions_db.php');
	$bill_id = (string) get_parameter ('bill_id');
	$invoice_id = get_parameter ('invoice_id', 0);
	$old_bill_id = -1;
	$invoice_type = get_parameter ('invoice_type');
	
	if ($invoice_type == 'Received') {
		// Don't check Bill ID
		echo json_encode(true);
		return;
	}
	
	if ($invoice_id) {
		$old_bill_id = get_db_value("bill_id", "tinvoice", "id", $invoice_id);
	}
	
	// Checks if the bill id is in the db
	$query_result = get_db_value("bill_id", "tinvoice", "bill_id", $bill_id);
	if ($query_result) {
		if ($bill_id != $old_bill_id) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_contract) {
	require_once ('include/functions_db.php');
	$contract_name = get_parameter ('contract_name');
	$contract_id = get_parameter ('contract_id', 0);
	$id_company = get_parameter('id_company', 0);
	$old_contract_name = "";
	
	if ($contract_id) {
		$old_contract_name = get_db_value("name", "tcontract", "id", $contract_id);
	}
	
	// Checks if the contract is in the db
	$query_result = get_db_value_filter('name', 'tcontract', array('name'=>$contract_name, 'id_company'=>$id_company));
	if ($query_result) {
		if ($contract_name != $old_contract_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_contract_number) {
	require_once ('include/functions_db.php');
	$contract_number = get_parameter ('contract_number');
	$contract_id = get_parameter ('contract_id', 0);
	$old_contract_number = -1;
	
	if ($contract_id) {
		$old_contract_number = get_db_value("contract_number",
			"tcontract", "id", $contract_id);
	}
	
	// Checks if the contract number is in the db
	$query_result = get_db_value("contract_number", "tcontract",
		"contract_number", $contract_number);
	if ($query_result) {
		if ($contract_number != $old_contract_number) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_contact) {
	require_once ('include/functions_db.php');
	$contact_name = get_parameter ('contact_name');
	$contact_id = get_parameter ('contact_id', 0);
	$old_contact_name = "";
	
	if ($contact_id) {
		$old_contact_name = get_db_value("fullname", "tcompany_contact", "id", $contact_id);
	}
	
	// Checks if the contact is in the db
	$query_result = get_db_value("fullname", "tcompany_contact", "fullname", $contact_name);
	if ($query_result) {
		if ($contact_name != $old_contact_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_contact_email) {
	require_once ('include/functions_db.php');
	$contact_email = get_parameter ('contact_email');
	$contact_id = get_parameter ('contact_id', 0);
	$old_contact_email = -1;
	
	if ($contact_id) {
		$old_contact_email = get_db_value("email", "tcompany_contact", "id", $contact_id);
	}
	
	// Checks if the contact email is in the db
	$query_result = get_db_value("email", "tcompany_contact", "email", $contact_email);
	if ($query_result) {
		if ($contact_email != $old_contact_email) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_lead) {
	require_once ('include/functions_db.php');
	$lead_name = get_parameter ('lead_name');
	$lead_id = get_parameter ('lead_id', 0);
	$old_lead_name = -1;
	
	if ($lead_id) {
		$old_lead_name = get_db_value("fullname",
			"tlead", "id", $lead_id);
	}
	
	// Checks if the lead is in the db
	$query_result = get_db_value("fullname", "tlead",
		"fullname", $lead_name);
	if ($query_result) {
		if ($lead_name != $old_lead_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_lead_email) {
	require_once ('include/functions_db.php');
	$lead_email = get_parameter ('lead_email');
	$lead_id = get_parameter ('lead_id', 0);
	$old_lead_email = -1;
	
	if ($lead_id) {
		$old_lead_email = get_db_value("email",
			"tlead", "id", $lead_id);
	}
	
	// Checks if the lead email is in the db
	$query_result = get_db_value("email", "tlead",
		"email", $lead_email);
	if ($query_result) {
		if ($lead_email != $old_lead_email) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_crm_template) {
	require_once ('include/functions_db.php');
	$crm_template_name = get_parameter ('crm_template_name');
	$crm_template_id = get_parameter ('crm_template_id', 0);
	$old_crm_template_name = -1;
	
	if ($crm_template_id) {
		$old_crm_template_name = get_db_value("name",
			"tcrm_template", "id", $crm_template_id);
	}
	
	// Checks if the crm template is in the db
	$query_result = get_db_value("name", "tcrm_template",
		"name", $crm_template_name);
	if ($query_result) {
		if ($crm_template_name != $old_crm_template_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_kb_item) {
	require_once ('include/functions_db.php');
	$kb_item_name = get_parameter ('kb_item_name');
	$kb_item_id = get_parameter ('kb_item_id', 0);
	$old_kb_item_name = "";
	
	if ($kb_item_id) {
		$old_kb_item_name = get_db_value("title", "tkb_data", "id", $kb_item_id);
	}
	
	// Checks if the kb item is in the db
	$query_result = get_db_value("title", "tkb_data", "title", $kb_item_name);
	if ($query_result) {
		if ($kb_item_name != $old_kb_item_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_kb_category) {
	require_once ('include/functions_db.php');
	$kb_category_name = get_parameter ('kb_category_name');
	$kb_category_id = get_parameter ('kb_category_id', 0);
	$old_kb_category_name = "";
	
	if ($kb_category_id) {
		$old_kb_category_name = get_db_value("name", "tkb_category", "id", $kb_category_id);
	}
	
	// Checks if the kb category is in the db
	$query_result = get_db_value("name", "tkb_category", "name", $kb_category_name);
	if ($query_result) {
		if ($kb_category_name != $old_kb_category_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_product_type) {
	require_once ('include/functions_db.php');
	$product_type_name = get_parameter ('product_type_name');
	$product_type_id = get_parameter ('product_type_id', 0);
	$old_product_type_name = "";
	
	if ($product_type_id) {
		$old_product_type_name = get_db_value("name", "tkb_product", "id", $product_type_id);
	}
	
	// Checks if the product type is in the db
	$query_result = get_db_value("name", "tkb_product", "name", $product_type_name);
	if ($query_result) {
		if ($product_type_name != $old_product_type_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_download) {
	require_once ('include/functions_db.php');
	$download_name = get_parameter ('download_name');
	$download_id = get_parameter ('download_id', 0);
	$old_download_name = "";
	
	if ($download_id) {
		$old_download_name = get_db_value("name", "tdownload", "id", $download_id);
	}
	
	// Checks if the download is in the db
	$query_result = get_db_value("name", "tdownload", "name", $download_name);
	if ($query_result) {
		if ($download_name != $old_download_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_file_category) {
	require_once ('include/functions_db.php');
	$file_category_name = get_parameter ('file_category_name');
	$file_category_id = get_parameter ('file_category_id', 0);
	$old_file_category_name = "";
	
	if ($file_category_id) {
		$old_file_category_name = get_db_value("name", "tdownload_category", "id", $file_category_id);
	}
	
	// Checks if the category is in the db
	$query_result = get_db_value("name", "tdownload_category", "name", $file_category_name);
	if ($query_result) {
		if ($file_category_name != $old_file_category_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_file_type) {
	require_once ('include/functions_db.php');
	$file_type_name = get_parameter ('file_type_name');
	$file_type_id = get_parameter ('file_type_id', 0);
	$old_file_type_name = "";
	
	if ($file_type_id) {
		$old_file_type_name = get_db_value("name", "tdownload_type", "id", $file_type_id);
	}
	
	// Checks if the category is in the db
	$query_result = get_db_value("name", "tdownload_type", "name", $file_type_name);
	if ($query_result) {
		if ($file_type_name != $old_file_type_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_user_id) {
	require_once ('include/functions_db.php');
	$user_id = get_parameter ('user_id');
	
	// Checks if the id is in the db
	$query_result = get_db_value("id_usuario", "tusuario", "id_usuario", $user_id);
	if ($query_result) {
		// Exists. Validation error
		echo json_encode(false);
		return;
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_non_existing_user_id) {
	require_once ('include/functions_db.php');
	$user_id = get_parameter ('user_id', '');
	
	if ($user_id == '') {
		echo json_encode(true);
		return;
	}
	
	$users = get_user_visible_users ($config['id_user'], "IR", false);
	foreach ($users as $user) {
		if(preg_match('/^'.$user_id.'$/i', $user['id_usuario']) || preg_match('/^'.$user_id.'$/i', $user['nombre_real'])|| preg_match('/^'.$user_id.'$/i', $user['num_employee'])) {
			echo json_encode(true);
			return;
		}
	}
	// Does not exist
	echo json_encode(false);
	return;
	
}
elseif ($search_existing_user_name) {
	require_once ('include/functions_db.php');
	$user_name = get_parameter ('user_name');
	$user_id = get_parameter ('user_id', 0);
	$old_user_name = "";
	
	if ($user_id) {
		$old_user_name = get_db_value("nombre_real", "tusuario", "id_usuario", $user_id);
	}
	
	// Checks if the user is in the db
	$query_result = get_db_value("nombre_real", "tusuario", "nombre_real", $user_name);
	if ($query_result) {
		//Use str to lower to allow an user to change it's 
		//fullname letters from upper to lower case and viceversa
		$user_name = strtolower($user_name);
		$old_user_name = strtolower($old_user_name);
		if ($user_name != $old_user_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_user_num) {
	require_once ('include/functions_db.php');
	$user_num = get_parameter ('user_num');
	$user_id = get_parameter ('user_id', 0);
	$old_user_num = -1;
	
	if ($user_id) {
		$old_user_num = get_db_value("num_employee", "tusuario", "id_usuario", $user_id);
	}
	
	// Checks if the employee number is in the db
	$query_result = get_db_value("num_employee", "tusuario", "num_employee", $user_num);
	if ($query_result) {
		if ($user_num != $old_user_num) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif($search_existing_template_name){
	require_once ('include/functions_db.php');
	$name_template = get_parameter ('template_name', '');
	if($name_template == ''){
		echo json_encode(true);
		return;
	}
	if(preg_match('/[^A-Za-z0-9_ -]/', $name_template)){
		echo json_encode(__('Do not include spaces or symbols'));
		return;
	}
	$template_exist = get_db_value("id", "temail_template", "name", $name_template);
	
	if($template_exist) {
		echo json_encode(false);
		return;
	} else {
		echo json_encode(true);
		return;
	}
}
elseif ($search_existing_user_email) {
	require_once ('include/functions_db.php');
	$user_email = get_parameter ('user_email');
	$user_id = get_parameter ('user_id', 0);
	$old_user_email = "";
	
	if ($user_id) {
		$old_user_email = get_db_value("direccion", "tusuario", "id_usuario", $user_id);
	}
	
	// Checks if the user email is in the db
	$query_result = get_db_value("direccion", "tusuario", "direccion", $user_email);
	if ($query_result) {
		if ($user_email != $old_user_email) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_role) {
	require_once ('include/functions_db.php');
	$role_name = get_parameter ('role_name');
	$role_id = get_parameter ('role_id', 0);
	$old_role_name = "";
	
	if ($role_id) {
		$old_role_name = get_db_value("name", "trole", "id", $role_id);
	}
	
	// Checks if the role is in the db
	$query_result = get_db_value("name", "trole", "name", $role_name);
	if ($query_result) {
		if ($role_name != $old_role_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_group) {
	require_once ('include/functions_db.php');
	$group_name = get_parameter ('group_name');
	$group_id = get_parameter ('group_id', 0);
	$old_group_name = "";
	
	if ($group_id) {
		$old_group_name = get_db_value("nombre", "tgrupo", "id_grupo", $group_id);
	}
	
	// Checks if the group is in the db
	$query_result = get_db_value("nombre", "tgrupo", "nombre", $group_name);
	if ($query_result) {
		if ($group_name != $old_group_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;
	
}
elseif ($search_existing_inventory) {
	require_once ('include/functions_db.php');
	$name = (string) get_parameter ('name');
	$inventory_id = get_parameter ('inventory_id', 0);
	$old_name = -1;
	//~ $invoice_type = get_parameter ('invoice_type');
	//~ 
	//~ if ($invoice_type == 'Received') {
		//~ // Don't check Bill ID
		//~ echo json_encode(true);
		//~ return;
	//~ }
	
	if ($inventory_id) {
		$old_name = get_db_value("name", "tinventory", "id", $inventory_id);
	}
	
	// Checks if the name is in the db
	$query_result = get_db_value("name", "tinventory", "name", $name);
	if ($query_result) {
		if ($name != $old_name) {
			// Exists. Validation error
			echo json_encode(false);
			return;
		}
	}
	// Does not exist
	echo json_encode(true);
	return;

}
else if ($check_mail) {
	$mail = get_parameter('mail', '');
	$check = check_correct_mail($mail);
	
	if ($check) {
		echo json_encode(true);
		return;
	}
	else {
		echo json_encode(false);
		return;
	}
}

if ($search_duplicate_name) {			
	if ((!isset($config['duplicate_inventory_name'])) || ($config['duplicate_inventory_name'])) {
		echo json_encode(true);
		return;
	} else {
		$inventory_name = get_parameter ('inventory_name');
		$exists = get_db_value ('id', 'tinventory', 'name', $inventory_name);
		if ($exists) {
			echo json_encode(false);
			return;
		} else {
			echo json_encode(true);
			return;
		}
	}
}


if ($check_user_name) {

	$user_id = get_parameter("user_id");

	$special_characters = preg_match ("/.*(&#x20;)|(&ntilde;)|(acute;)|(uml;).*/", $user_id);
	(strlen($user_id) > 30) ? $too_long = true : $too_long = false;
	
	if (($special_characters) || ($too_long)) {
		echo json_encode(false);
		return;
	} else {
		echo json_encode(true);
		return;
	}
}

if ($check_allowed_users) {

	require_once ('include/functions_db.php');
	$user_id = get_parameter ('user_id', '');
	$id_group = get_parameter ('id_group', '');
	
	if ($user_id == '') {
		echo json_encode(true);
		return;
	}
	
	if ($id_group != '') {
		$filter['group'] = $id_group;
	} else {
		$filter = false;
	}
	
	//~ $query_users = users_get_allowed_users_query ($config['id_user'], false);
	$query_users = users_get_allowed_users_query ($config['id_user'], $filter);
	$users = get_db_all_rows_sql($query_users);
	foreach ($users as $user) {
		if(preg_match('/^'.$user_id.'$/i', $user['id_usuario']) || preg_match('/^'.$user_id.'$/i', $user['nombre_real'])|| preg_match('/^'.$user_id.'$/i', $user['num_employee'])) {
			echo json_encode(true);
			return;
		}
	}
	// Does not exist
	echo json_encode(false);
	return;
	
}

?>
