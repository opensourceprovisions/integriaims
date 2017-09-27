<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
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

include_once("include/functions_crm.php");

// We need to strip HTML entities if we want to use in a sql search
$search_string = get_parameter ("search_string","");

// Delete spaces from start and end of the search string
$search_string = safe_input(trim(safe_output($search_string)));

if ($search_string == ""){

    echo "<h2>";
    echo __("Global Search");
    echo "</h2>";
    echo "<h4>";
    echo __("Empty search string");
    echo "</h4>";
    return;
}

echo "<h2>";
echo __("Global Search");
echo "</h2>";

echo "<h4>";
echo __("Searching for");
echo "...";
echo "<i> '". safe_output($search_string) ."'</i>";
echo "</h4>";

/* 

This code is a general search view, the first version, will be improved in the future. This will render in a single page, output for:

	* Incident data (title and/or #id)
    * Project / Task title

	* KB Articles problem
	* Inventory object
	* Companies
	* Contracts
	* Contacts

*/

// Incidents
if (give_acl($config["id_user"], 0, "IR") && $show_incidents != MENU_HIDDEN){

	$sql = "SELECT id_incidencia, inicio, titulo, estado FROM tincidencia WHERE titulo LIKE '%$search_string%' OR id_incidencia = '$search_string'";

	$incidents = get_db_all_rows_sql ($sql);
	
	if ($incidents !== false) {


		echo "<h3>";
		echo __("Ticket management");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head[0] = __('# ID');
		$table->head[1] = __('Title');
		$table->head[2] = __('Creation datetime');
		$table->head[3] = __('Status');
		$table->head[4] = __('WU time (hr)');

		$statuses = get_indicent_status ();

		foreach ($incidents as $incident) {
			$data = array ();
			if ((user_belong_incident ($config["id_user"], $incident["id_incidencia"]))
			OR (dame_admin ($config["id_user"]))) {

				$data[0] = $incident["id_incidencia"];
				$data[1] = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident["id_incidencia"]."'>".$incident["titulo"]."</a>";
				$data[2] = $incident["inicio"];
				$data[3] = $statuses[$incident['estado']];
				$data[4] = get_incident_workunit_hours($incident["id_incidencia"]);
				array_push ($table->data, $data);
			}
		}

		print_table ($table);
	}
}


// Projects
if (give_acl($config["id_user"], 0, "PR") && $show_projects != MENU_HIDDEN){

	$sql = "SELECT tproject.id as project_id, ttask.id as task_id, tproject.name as pname, ttask.name as tname FROM 
			tproject, ttask WHERE tproject.disabled = 0 AND ttask.id_project = tproject.id AND (ttask.name LIKE '%$search_string%' 
			OR tproject.name  LIKE '%$search_string%')";

	$tasks = get_db_all_rows_sql ($sql);
	
	if ($tasks !== false) {

		echo "<h3>";
		echo __("Project management");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head = array();
		$table->head[0] = __('Project');
		$table->head[1] = __('Task');

		foreach ($tasks as $task) {
			$data = array ();
		
		if (user_belong_project ($config["id_user"], $task["project_id"])){

				$data[0] = "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=".$task["project_id"]."'>".$task["pname"]."</a>";

				$data[1] = "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=".$task["project_id"]."&id_task=".$task["task_id"]."&operation=view'>".$task["tname"]."</a>";

				array_push ($table->data, $data);
			}
		}
		print_table ($table);
	}
}

// Users - Only for UM
if (give_acl($config["id_user"], 0, "UM")){

	$sql = "SELECT * FROM tusuario WHERE nombre_real LIKE '%".$search_string."%' OR id_usuario LIKE '%".$search_string."%' OR direccion LIKE '%".$search_string."%' OR comentarios LIKE '%".$search_string."%' ";
	$users = get_db_all_rows_sql ($sql);
	
	if ($users !== false) {

		echo "<h3>";
		echo __("People");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head[0] = __('Full name');
		$table->head[1] = __('Full report');
		$table->head[2] = __('Monthly report');
		$table->head[3] = __('Assigned WO\'s');
		$table->head[4] = __('Assigned tickets');	
		foreach ($users as $user) {

			$data = array ();

			// CHECK ACK !!
			if (user_visible_for_me ($config["id_user"], $user["id_usuario"], "")){
				$data[0] = "<a href='index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&update_user=".$user["id_usuario"]."'>".$user["nombre_real"]." ( ". $user["id_usuario"]." ) "."</a>";

				$data[1] = "<a href='index.php?sec=users&sec2=operation/user_report/report_full&only_projects=1&wu_reporter=".$user["id_usuario"]."'>". "<img title='".__("Full report")."' src='images/page_white_stack.png'>" . "</a>";

				$data[2] = "<a href='index.php?sec=users&sec2=operation/user_report/monthly&id=".$user["id_usuario"]."'><img src='images/clock.png'></a>";
				$data[3] = "<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner=".$user["id_usuario"]."'><img src='images/paste_plain.png'></a>";
				$data[4] = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_id_user=".$user["id_usuario"]."'><img src='images/incident.png'></a>";
				array_push ($table->data, $data);
			}
		}
		print_table ($table);
	}
}

// KB
if (give_acl($config["id_user"], 0, "KR") && $show_kb != MENU_HIDDEN){

	$sql = "SELECT tkb_category.name as category, tkb_product.name as product, tkb_data.title as kb_name, tkb_data.id as kb_id FROM tkb_data, tkb_product, tkb_category WHERE title LIKE '%".$search_string."%' AND tkb_data.id_category = tkb_category.id AND tkb_data.id_product = tkb_product.id";
	$kbs = get_db_all_rows_sql ($sql);
	
	if ($kbs !== false) {
		unset($table);
		echo "<h3>";
		echo __("Knowledge Base");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head[0] = __('KB');
		$table->head[1] = __('Product');
		$table->head[2] = __('Category');

		foreach ($kbs as $kb) {
			$data = array ();
		
			$data[0] = "<a href='index.php?sec=kb&sec2=operation/kb/browse_data&view=".$kb["kb_id"]."'>".$kb["kb_name"]."</a>";
			$data[1] = $kb["product"];
			$data[2] = $kb["category"];
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
}

// Contact
if ( check_crm_acl('company', 'cr') && $show_inventory != MENU_HIDDEN ) {

	$where_clause = " WHERE fullname LIKE '%".$search_string."%' OR email LIKE '%".$search_string."%' OR phone LIKE '%".$search_string."%' OR mobile LIKE '%".$search_string."%'";
	$contacts = crm_get_all_contacts ($where_clause);
	
	if ($contacts) {
		
		echo "<h3>";
		echo __("Contacts");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Company');
		$table->head[2] = __('Email');
		$table->head[3] = __('Position');

		foreach ($contacts as $contact) {
			$data = array ();
		
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&id=".$contact["id"]."'>".$contact["fullname"].'</a>';
			$data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$contact["id_company"]."'>" . 	
			get_db_sql ("SELECT name FROM tcompany WHERE id = " . $contact["id_company"]). "</a>";
			$data[2] = $contact["email"];
			$data[3] = $contact["position"];
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
}

// Contracts
if ( check_crm_acl ('contract', 'cr') && $show_customers != MENU_HIDDEN ) {

        $where_clause = " WHERE name LIKE '%".$search_string."%' OR description LIKE '%".$search_string."%' AND id_group in ". get_user_groups_for_sql ($config["id_user"]);

        $contracts = crm_get_all_contracts ($where_clause);
        
        if ($contracts) {

                echo "<h3>";
                echo __("Contracts");
                echo "</h3>";
				
				$table = new StdClass();
                $table->width = '100%';
                $table->class = 'listing';
                $table->data = array ();
                $table->size = array ();
                $table->style = array ();
                $table->head[0] = __('Name');
                $table->head[1] = __('Company');
                $table->head[2] = __('Date Begin');
                $table->head[3] = __('Date End');

                foreach ($contracts as $contract) {
                        $data = array ();

                        $data[0] = "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail&id=".$contract["id"]."'>".$contract["name"].'</a>';
                        $data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$contract["id_company"]."'>" .
                        $data[2] = $contract["date_begin"];
                        $data[3] = $contract["date_end"];
                        array_push ($table->data, $data);
                }
                print_table ($table);
        }
}


// Companies
if ( check_crm_acl('company', 'cr') && $show_customers != MENU_HIDDEN ){

	$where_clause = " AND (tcompany.name LIKE '%".$search_string."%' OR tcompany.id IN (SELECT id_company
																FROM tcompany_activity
																WHERE description LIKE '%$search_string%'))";
	
	$companies = crm_get_companies_list($where_clause, false, "ORDER BY name");
	
	if ($companies) {
		
		echo "<h3>";
		echo __("Companies");
		echo "</h3>";
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->style = array ();
		$table->head = array();
		$table->head[0] = __('Company');
		$table->head[1] = __('Role');
		
		foreach ($companies as $company) {
			$data = array ();
	
			$data[0] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=".$company["id"]."'>" . 	
			$company["name"]. "</a>";
			$data[1] = get_db_sql ("SELECT name FROM tcompany_role WHERE id = ".$company["id_company_role"]);
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
}

// Invoices
if ( check_crm_acl('company', 'cr') && $show_customers != MENU_HIDDEN ){

        $where_clause = " bill_id LIKE '%".$search_string."%' ";
        $invoices = crm_get_all_invoices ($where_clause);

        if ($invoices) {

            echo "<h3>";
            echo __("Invoices");
            echo "</h3>";
			
			$table = new StdClass();
            $table->width = "100%";
			$table->class = "listing";
			$table->data = array ();
			$table->size = array ();
			$table->style = array ();
			$table->colspan = array ();
			$table->head[0] = __('ID');
			$table->head[1] = __('Company');
			$table->head[2] = __('Amount');
			$table->head[3] = __('Status');
			$table->head[5] = __('Creation');

            foreach ($invoices as $invoice) {
                    $data = array ();

                    $id_title = $invoice["concept1"];
					$data[0] = "<a title='$id_title' href='index.php?sec=customers&sec2=operation/companies/company_detail&view_invoice=1&id=".$invoice["id_company"]."&op=invoices&id_invoice=".$invoice["id"]."'>".$invoice["bill_id"]."</a>";

                    if ($invoice["id_company"] != 0){
						$company_name = get_db_value ("name", "tcompany", "id", $invoice["id_company"]);
						$data[1] = "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&view_invoice=1&id=".$invoice["id_company"]."&op=invoices&id_invoice=".$invoice["id"]."'>".$company_name."</a>";
					} else {
						$data[1] = __("N/A");
					}
					
					$partial = get_invoice_amount ($invoice["id"]);
					
					if (isset($total[$invoice["currency"]]))
						$total[$invoice["currency"]] = $total[$invoice["currency"]] + $partial;
					else
						$total[$invoice["currency"]] = $partial;

					$data[2] = format_numeric($partial). " " . strtoupper ($invoice["currency"]);

					$tax = get_invoice_tax ($invoice["id"]);
					$tax_amount = get_invoice_amount ($invoice["id"]) * (1 + $tax/100);
					if ($tax != 0 && $tax_amount > 0)
						$data[2] .= print_help_tip (__("With taxes"). ": ".format_numeric($tax_amount)." ".strtoupper($invoice["currency"]), true);

					$data[3] = __($invoice["status"]);
					$data[5] = "<span style='font-size: 10px'>".$invoice["invoice_create_date"] . "</span>";

                    array_push ($table->data, $data);
            }

            print_table ($table);
        }
}

// Leads
if ( check_crm_acl('lead', 'cr') && $show_customers != MENU_HIDDEN ){

        $where_clause = " WHERE fullname LIKE '%".$search_string."%'
							OR email LIKE '%".$search_string."%'
							OR company LIKE '%".$search_string."%'";
        $leads = crm_get_all_leads ($where_clause);

        if ($leads) {

                echo "<h3>";
                echo __("Leads");
                echo "</h3>";
				
				$table = new StdClass();
                $table->width = '100%';
                $table->class = 'listing';
                $table->data = array ();
                $table->size = array ();
                $table->style = array ();
                $table->head = array();
                $table->head[0] = __('Name');
				$table->head[1] = __('Email');
                $table->head[1] = __('Company');

                foreach ($leads as $lead) {
                        $data = array ();

                        $data[0] = "<a href='index.php?sec=customers&sec2=operation/leads/lead_detail&id=".$lead["id"]."'>" .
                        $lead["fullname"]. "</a>";
                        $data[1] = $lead["email"];
						$data[1] = $lead["company"];

                        array_push ($table->data, $data);
                }
                print_table ($table);
        }
}


// Wiki search
if (give_acl ($config['id_user'], $id_grupo, "WR")) {

	require_once("include/wiki/lionwiki_lib.php");

	$conf_plugin_dir = 'include/wiki/plugins/';
	$conf_var_dir = 'var/';
	if (isset($config['wiki_plugin_dir']))
	        $conf_plugin_dir = $config['wiki_plugin_dir'];
	if (isset($config['conf_var_dir']))
	        $conf_var_dir = $config['conf_var_dir'];

	$conf['wiki_title'] = 'Wiki';
	$conf['self'] = 'index.php?sec=wiki&sec2=operation/wiki/wiki' . '&';
	$conf['plugin_dir'] = $conf_plugin_dir;
	$conf['var_dir'] = $conf_var_dir;
//	$conf['custom_style'] = file_get_contents ($config["homedir"]."/include/styles/wiki.css");
	$conf['fallback_template'] = $conf['custom_style'].  '

	<div id="wiki_view">
        <table width="100%" cellpadding="0">
                <tr><td colspan="3"><h3>{PAGE_TITLE}</h3></td></tr>
                <tr>
                        <td colspan="3">
                                {<div style="color:#F25A5A;font-weight:bold;"> ERROR </div>}
                                {CONTENT} {<div style="background: #EBEBED"> plugin:TAG_LIST </div>}
                                {plugin:TOOLBAR_TEXTAREA}
                                {CONTENT_FORM} {RENAME_INPUT <br/><br/>} {CONTENT_TEXTAREA}
                                {EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p>{/CONTENT_FORM}
                        </td>
                </tr>
        </table>
</div>';



	// Yes, this is dirty but works like a charm :))

	$action="search";

	$_REQUEST["query"]=safe_output($search_string);
	$_REQUEST["action"]="search";
	lionwiki_show($conf);

}

echo "<strong>";
echo "-- ";
echo __("End of search");
echo " --";
echo "</strong>";


?>
