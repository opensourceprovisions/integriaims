<?php
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

if (!isset($config["id_user"]))
	return;

global $show_projects;
global $show_incidents;
global $show_inventory;
global $show_reports;
global $show_customers;
global $show_kb;
global $show_file_releases;
global $show_people;
global $show_todo;
global $show_agenda;
global $show_setup;
global $show_wiki;


// PROJECTS
echo "<nav id='menu_nav'>";
echo "<ul id='menu_slide'>";
if ($sec == "projects" && give_acl ($config["id_user"], 0, "PR") && $show_projects != MENU_HIDDEN) {
		
	$section_permission = get_project_access ($config["id_user"]);
	$manage_any_task = manage_any_task ($config["id_user"]);
	
	// if for active li project
	if (($sec2 == "operation/projects/project_overview") || 
		($sec2 == "operation/projects/user_project_timegraph") || 
		($sec2 == "operation/projects/project_detail") || 
		($sec2 == "operation/projects/role_user_global") || 
		($sec2 == "operation/projects/project") ||
		($sec2 == "operation/projects/project_detail") ||
		($sec2 == "operation/projects/task_planning") ||
		($sec2 == "operation/projects/project_timegraph") ||
		($sec2 == "operation/projects/project_tracking") ||
		($sec2 == "operation/projects/task") ||
		($sec2 == "operation/projects/project_report") ||
		($sec2 == "operation/projects/task_detail") ||
		($sec2 == "operation/projects/gantt") ||
		($sec2 == "operation/projects/milestones") ||
		($sec2 == "operation/projects/people_manager") ||
		($sec2 == "operation/projects/task_workunit") ||
		($sec2 == "operation/projects/task_files") ||
		($sec2 == "operation/projects/task_tracking") ||
		($sec2 == "operation/users/user_spare_workunit") ||
		($sec2 == "operation/projects/task_cost") ||
		($sec2 == "operation/projects/task_move") ||
		($sec2 == "operation/projects/task_emailreport"))
		echo "<li title='".__('Projects')."' data-status='closed' id='sideselproject' class='sideselcolor'>";
	else
		echo "<li title='".__('Projects')."' data-status='closed' id='proyectos'>";
	//echo "<a   title='".__('Projects')."'href='index.php?sec=projects&sec2=operation/projects/project_overview'>1</a>";
		
		echo "<ul>";
			echo "<li><h1>".__('Projects')."</h1></li>";
			// Project overview
			if (($sec2 == "operation/projects/project_overview") AND (!isset($_REQUEST["view_disabled"])) )
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/project_overview'>".__('Projects overview')."</a></li>";

			// Project tree
			if ($sec2 == "operation/projects/user_project_timegraph")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/user_project_timegraph'>".__('Project timemap')."</a></li>";

			//Project create
			if ($section_permission['write']) {
				if ($sec2 == "operation/projects/project_detail" && $id_project < 0)
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&create_project=1'>".__('Create project')."</a></li>";
			}
			
			if($show_projects != MENU_LIMITED && $show_projects != MENU_MINIMAL) {
				// View disabled projects
				if (($sec2 == "operation/projects/project") AND (isset($_REQUEST["view_disabled"])) )
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=projects&sec2=operation/projects/project&view_disabled=1'>".__('Archived projects')."</a></li>";
			}
			if ($manage_any_task) {
				// Global user/role/task assigment
				if ($sec2 == "operation/projects/role_user_global")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=projects&sec2=operation/projects/role_user_global'>".__('Global assigment')."</a></li>";
			}		
			
		echo "</ul>";
	echo "</li>";
	
}

// Project group manager
if (give_acl ($config["id_user"], 0, "PM") && $sec == "projects" && $show_projects == MENU_FULL) {
			  
	if ($sec2=="operation/projects/project_group_detail")
		echo "<li title='".__('Projects groups')."' data-status='closed' id='sideselgrupos' class='sideselcolor'>";
	else
		echo "<li title='".__('Projects groups')."' data-status='closed' id='grupos'>";
		
		echo "<ul>";
            echo "<li><h1>".__('Projects groups')."</h1></li>";
            // Building overview
			if ($sec2=="operation/projects/project_group_detail")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/project_group_detail'>".__('Project groups')."</a></li>";
		echo "</ul>";
	echo "</li>";
}

/*
// Workorders
if ((($sec == "projects" ))&& ( $show_projects != MENU_HIDDEN )) {
	echo "<div class='portlet'>";
	echo "<h3>".__('Workorders')."</h3>";
	echo "<ul class='sidemenu'>";

	// Todo overview
	if (($sec2 == "operation/workorders/wo") && (!isset($_GET["operation"])))
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner=".$config["id_user"]."'>".__('Workorders')."</a></li>";
	
	if (($sec2 == "operation/workorders/wo")) {
		echo "<li style='margin-left: 15px; font-size: 10px;'>";
		echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo&operation=create'>".__('New WO')."</a>";
		echo "</li>";
	}

	if (dame_admin($config["id_user"])){
		// WO category management
		if (($sec2 == "operation/workorders/wo_category") && (!isset($_GET["operation"])))
			echo "<li id='sidesel'>";
		else 
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo_category'>".__('WO Categories')."</a></li>";

		if (($sec2 == "operation/workorders/wo_category")){
			echo "<li style='margin-left: 15px; font-size: 10px;'>";
			echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo_category&create=1'>".__('New category')."</a>";
			echo "</li>";
		}
	}
	echo "</ul></div>";
}
*/

// INCIDENTS
if ($sec == "incidents" && (give_acl ($config['id_user'], 0, "IR") || get_standalone_user($config["id_user"])) && $show_incidents != MENU_HIDDEN || $sec == "incidents" && give_acl ($config['id_user'], 0, "SI") && $show_incidents != MENU_HIDDEN) {
	$id_incident = get_parameter ('id');
	
	if (($sec2 == "operation/incidents/incident_dashboard") || ($sec2 == "operation/incidents/incident") || ($sec2 == "operation/incidents/incident_search") || 
	($sec2 == "operation/incidents/incident_detail") || ($sec2 == "operation/incidents/incident_reports") || ($sec2 == "operation/incidents/incident_dashboard_detail"))
		echo "<li title='".__('Incidents')."' data-status='closed' id='sideselticket' class='sideselcolor'>";
	else
		echo "<li title='".__('Incidents')."' data-status='closed' id='ticket'>";
	//echo "<a title='".__('Incidents')."' href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard'>1</a>";
		
		echo "<ul>";
			echo "<li><h1>".__('Incidents')."</h1></li>";

			if (give_acl ($config['id_user'], 0, "IR") || (get_standalone_user($config["id_user"]))) {
				// Incident overview
				if (give_acl ($config['id_user'], 0, "IR") && (!get_standalone_user($config["id_user"]))) {
					if ($sec2 == "operation/incidents/incident_dashboard")
						echo "<li id='sidesel'>";
					else
						echo "<li>";
					echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard'>".__('Tickets overview')."</a></li>";
					$search_id_user = (bool) get_parameter ('search_id_user', false);
				}
				//~ My Tickets
				if (!get_standalone_user($config["id_user"])) {
					if ($sec2 == "operation/incidents/incident_search" && $search_id_user)
						echo "<li id='sidesel'>";
					else
						echo "<li>";
					echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search&id_myticket=1&search_id_user=".$config['id_user']."'>".__('My tickets')."</a></li>";
				}
				
				//~ Search Tickets
				if (give_acl ($config['id_user'], 0, "IR")) {
					if ($sec2 == "operation/incidents/incident_search"  && !$search_id_user)
						echo "<li id='sidesel'>";
					else
						echo "<li>";
					echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search'>".__('All tickets')."</a></li>";
				}
			}
			if (give_acl ($config['id_user'], 0, "IW") || give_acl ($config['id_user'], 0, "SI") || (get_standalone_user($config["id_user"]))) {
				// Incident creation
				if ($sec2 == "operation/incidents/incident_detail")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail' id='link_create_incident'>".__('Create ticket')."</a></li>";
			}
			
			if (give_acl ($config['id_user'], 0, "IR") && (!get_standalone_user($config["id_user"]))) {
				// Incident reports
				if ($sec2 == "operation/incidents/incident_reports")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_reports' id='link_incident_report'>".__('Reports')."</a></li>";
			}
			if (give_acl ($config['id_user'], 0, "IR") && (!get_standalone_user($config["id_user"]))) {
				if ($sec2 == "operation/incidents/incident_dashboard_detail")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo '<a href="" >'.__('Ticket #').'&nbsp;</a>';
				echo '<form action="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail" method="POST">';
				print_input_text ('id', '', '', 4, 10);
				echo '</form>';
				echo '</li>';
			}
		echo "</ul>";
			
		// Incident type and SLA management only PM
		if (give_acl ($config['id_user'], 0, "IM") && (get_standalone_user($config["id_user"]) == false)) {
			
			if ($sec2 == "operation/incidents/type_detail")
				echo "<li title='".__('Tickets')."' data-status='closed' id='sideseltipo_ticket' class='sideselcolor'>";
			else
				echo "<li title='".__('Tickets')."' data-status='closed' id='tipo_ticket'>";
			//echo "<a   title='".__('Ticket Types')."' href='index.php?sec=incidents&sec2=operation/incidents/type_detail'>1</a></li>";
			echo "<ul>";
				echo "<li><h1>".__('Tickets')."</h1></li>";
				if ($sec2 == "operation/incidents/type_detail")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=incidents&sec2=operation/incidents/type_detail'>".__("Ticket Types")."</a></li>";
			echo "</ul>";
			echo "</li>";
			
			
			// SLA Management
			if ($sec2 == "operation/slas/sla_detail")
				echo "<li title='".__('SLA')."' data-status='closed' id='sideselsla' class='sideselcolor'>";
			else
				echo "<li title='".__('SLA')."' data-status='closed' id='sla'>";
				
			echo "<ul>";
				echo "<li><h1>".__('SLA')."</h1></li>";
				if ($sec2 == "operation/slas/sla_detail")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=incidents&sec2=operation/slas/sla_detail'>".__("SLA Management")."</a></li>";
			echo "</ul>";
			echo "</li>";
		}
		
		//Workflow rules
		if (get_admin_user($config['id_user'])) {
			enterprise_include ("operation/sidemenu_workflow_rules.php");
		}
			
	echo "</li>";
}

// INVENTORY
if ($sec == "inventory" && give_acl ($config['id_user'], 0, "VR") && $show_inventory != MENU_HIDDEN) {
	$id_inventory = (int) get_parameter ('id');
	
	if (($sec2 == "operation/inventories/inventory") || ($sec2 == "operation/inventories/inventory_detail") || ($sec2 == "operation/inventories/inventory"))
		echo "<li title='".__('Inventory')."' data-status='closed' id='sideselinventario' class='sideselcolor'>";
	else
		echo "<li title='".__('Inventory')."' data-status='closed' id='inventario'>";
	//echo "<a title='".__('Inventory')."' href='index.php?sec=inventory&sec2=operation/inventories/inventory'>1</a>";
    echo "<ul>";
		echo "<li><h1>".__('Inventory')."</h1></li>";
           
	// Incident overview
	if ($sec2 == "operation/inventories/inventory")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory'>".__('Inventory overview')."</a></li>";
	//echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_search'>".__('Inventory overview')."</a></li>";

	if (give_acl ($config["id_user"], 0, "VW")) {
		// Incident creation
		if ($sec2 == "operation/inventories/inventory_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_detail'>".__('Create inventory object')."</a></li>";
	}
	
	echo '<li>';
	echo '<a href="" onclick="return false">'.__('Inventory #').'</a>';
	//echo '<form id="goto-inventory-form">';
	echo "<form action='index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id=$id_inventory&check_inventory=1' method='post'>";
	print_input_text ('id', $id_inventory ? $id_inventory : '', '', 3, 10);
	echo '</form>';
	echo '</li>';
	
	
	if (give_acl ($config["id_user"], 0, "VW")) {
		if ($sec2=="operation/manufacturers/manufacturer_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail'>".__('Manufacturer overview')."</a></li>";
	}

	if ($sec2=="operation/inventories/inventory_import_objects")
		echo "<li id='sidesel'>";
	else
		echo "<li>";
	echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_import_objects'>".__('Import objects from CSV')."</a></li>";
	
	echo "</ul>";
	
			
//~ Revisar
	//~ // Dynamic inventory sub options menu
	//~ echo '<h3>'.__('Inventory').' #<span class="id-inventory-menu">';
	//~ if ($id_inventory)
		//~ echo $id_inventory;
	//~ echo '</span></h3>';
//~ 
	//~ echo "<ul class='sidemenu'>";
		//~ echo '<li>';
			//~ echo '<a id="inventory-create-incident" href="index.php?sec=incidents&sec2=operation/incidents/incident_detail&id_inventory='.$id_inventory.'">'.__('Create ticket').'</a>';
		//~ echo '</li>';
	//~ echo "</ul>";
	echo "</li>";
}

// Customers 
if ($sec == "customers" && give_acl ($config["id_user"], 0, "CR") && $show_customers != MENU_HIDDEN) {
	
	if ((($sec2=="operation/companies/company_detail") AND (!$new_company)) || ($sec2 == "operation/companies/company_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) ||
	($sec2 == "operation/companies/company_custom_fields" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) ||
	(($sec2=="operation/companies/company_role") && (!isset($_GET["create"]))) || (($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && !$new_invoice) || 
	(($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) || (($sec2=="operation/contracts/contract_detail") && !$new_contract) ||
	($sec2 == "operation/contracts/contract_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) || (($sec2=="operation/contacts/contact_detail") && !$new_contact) || 
	($sec2 == "operation/contacts/contact_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) || (($sec2=="operation/leads/lead") AND !$new_lead) || 
	($sec2 == "operation/leads/lead" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))))
		echo "<li title='".__('Customers')."' data-status='closed' id='sideselclientes' class='sideselcolor'>";
	else
		echo "<li title='".__('Customers')."' data-status='closed' id='clientes'>";
	//echo "<a   title='".__('Customers')."' href='index.php?sec=customers&sec2=operation/companies/company_detail'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('Customers')."</h1></li>";
	
		// Company
		$new_company = (int) get_parameter('new_company');
		
		if (($sec2=="operation/companies/company_detail") AND (!$new_company))
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/companies/company_detail'>".__('Companies')."</a></li>";
		
		if (($sec2 == "operation/companies/company_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) ||
		 	($sec2 == "operation/companies/company_custom_fields" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM")))){
			if ($new_company)
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/companies/company_detail&new_company=1'>".__('New company')."</a></li>";
			if ($sec2 == "operation/companies/company_custom_fields" && (give_acl ($config["id_user"], 0, "CW")))
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/companies/company_custom_fields'>".__('Company custom fields')."</a></li>";
		}
		
		// Company roles
		if (give_acl ($config["id_user"], 0, "CM")) {
			if (($sec2=="operation/companies/company_role") && (!isset($_GET["create"])))
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=customers&sec2=operation/companies/company_role'>".__('Company roles')."</a></li>";
		}

		// Invoices
		$new_invoice = (int) get_parameter('new_invoice');
		
		if (($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && !$new_invoice)
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/invoices/invoice_detail'>".__('Invoices')."</a></li>";
		
		// new
		if (($sec2 == "operation/invoices/invoice_detail" || $sec2 == "operation/invoices/invoices") && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
			if ($new_invoice)
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/invoices/invoices&new_invoice=1'>".__('New invoice')."</a></li>";
		}
		
		// Contract overview
		$new_contract = (int) get_parameter('new_contract');
		
		if (($sec2=="operation/contracts/contract_detail") && !$new_contract)
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail'>".__('Contracts')."</a></li>";

		// new
		if ($sec2 == "operation/contracts/contract_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
			if ($new_contract)
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/contracts/contract_detail&new_contract=1'>".__('New contract')."</a></li>";
		}
		if ($sec2 == "operation/contracts/conctract_custom_fields" && (give_acl ($config["id_user"], 0, "CW")))
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/contracts/contract_custom_fields'>".__('Manage contract fields')."</a></li>";

		// Contact overview
		$new_contact = (int) get_parameter('new_contact');
		
		if (($sec2=="operation/contacts/contact_detail") && !$new_contact)
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail'>".__('Contacts')."</a></li>";

		if ($sec2 == "operation/contacts/contact_detail" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
			if ($new_contact)
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&new_contact=1'>".__('New contact')."</a></li>";
		}

		// Lead management
		$new_lead = (int) get_parameter('new');

		if (($sec2=="operation/leads/lead") AND !$new_lead)
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=customers&sec2=operation/leads/lead'>".__('Leads')."</a></li>";

		if ($sec2 == "operation/leads/lead" && (give_acl ($config["id_user"], 0, "CW") || give_acl ($config["id_user"], 0, "CM"))) {
			if ($new_lead)
				echo "<li id='sidesel' style='margin-left: 15px;'>";
			else
				echo "<li style='margin-left: 15px;'>";
			echo "<a href='index.php?sec=customers&sec2=operation/leads/lead&tab=search&new=1'>".__('New lead')."</a></li>";
		}
		echo "</ul>";
	echo "</li>";
}

// CRM Template Manager
if ($sec == "customers" && give_acl ($config["id_user"], 0, "CM") && $show_customers != MENU_HIDDEN) {
	
	if ($sec2=="operation/leads/template_manager")
		echo "<li title='".__('CRM Templates')."' data-status='closed' id='sideseltemplates' class='sideselcolor'>";
	else
		echo "<li title='".__('CRM Templates')."' data-status='closed' id='templates'>";
		//echo "<a   title='".__('CRM Templates')."' href='index.php?sec=customers&sec2=operation/leads/template_manager'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('CRM Templates')."</h1></li>";

			// Building overview
			if ($sec2=="operation/leads/template_manager")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=customers&sec2=operation/leads/template_manager'>".__('Manage templates')."</a></li>";

		echo "</ul>";
	echo "</li>";
}

// MANUFACTURER
//~ if ($sec == "inventory" && give_acl ($config["id_user"], 0, "VM") && $show_inventory != MENU_HIDDEN) {
	//~ if ($sec2=="operation/manufacturers/manufacturer_detail")
		//~ echo "<li title='".__('Manufacturers')."' data-status='closed' id='sideselfabricantes' class='sideselcolor'>";
	//~ else
		//~ echo "<li title='".__('Manufacturers')."' data-status='closed' id='fabricantes'>";
		//~ //echo "<a title='".__('Manufacturers')."' href='index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail'>1</a>";
		//~ echo "<ul>";
			//~ echo "<li><h1>".__('Manufacturers')."</h1></li>";
			//~ // Building overview
			//~ if ($sec2=="operation/manufacturers/manufacturer_detail")
				//~ echo "<li id='sidesel'>";
			//~ else
				//~ echo "<li>";
			//~ echo "<a href='index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail'>".__('Manufacturer overview')."</a></li>";
		//~ echo "</ul>";
	//~ echo "</li>";
//~ }

// Product types
if ($sec == "inventory" && give_acl($config["id_user"], 0, "PM") && $show_inventory != MENU_HIDDEN) {
	if (($sec2=="operation/inventories/manage_objects") || ($sec2=="operation/inventories/inventory_import_objects"))
		echo "<li title='".__('Inventory objects')."' id='sideselobjetos_inventario' data-status='closed' class='sideselcolor'>";
	else
		echo "<li title='".__('Inventory objects')."' data-status='closed' id='objetos_inventario'>";
	//echo "<a title='".__('Inventory objects')."' href='index.php?sec=inventory&sec2=operation/inventories/manage_objects'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('Inventory objects')."</h1></li>";

			// Building overview
			if ($sec2=="operation/inventories/manage_objects")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=inventory&sec2=operation/inventories/manage_objects'>".__('Object types')."</a></li>";

			// Building overview
			//~ if ($sec2=="operation/inventories/inventory_import_objects")
				//~ echo "<li id='sidesel'>";
			//~ else
				//~ echo "<li>";
			//~ echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory_import_objects'>".__('Import objects from CSV')."</a></li>";
		echo "</ul>";
	echo "</li>";
}
// Revisar me falta icono
// KNOWLEDGE BASE (KB)
if ($sec == "kb" && give_acl ($config["id_user"], 0, "KR") && $show_kb != MENU_HIDDEN) {
	
	if (($sec2 == "operation/kb/browse") || (($sec2 == "operation/kb/browse") AND (isset($_GET["create"]))) || ($sec2 == "operation/kb/manage_cat") ||
	($sec2 == "operation/inventories/manage_prod") || ($sec2 == "operation/kb/manage_perms"))
		echo "<li title='".__('Knowledge Base')."' data-status='closed' id='sideselknowledge_base' class='sideselcolor'>";
	else
		echo "<li title='".__('Knowledge Base')."' data-status='closed' id='knowledge_base'>";
	//echo "<a title='".__('Knowledge Base')."' href='index.php?sec=kb&sec2=operation/kb/browse'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('Knowledge Base')."</h1></li>";
			// KB Browser
			if ($sec2 == "operation/kb/browse")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=kb&sec2=operation/kb/browse'>".__('Browse')."</a></li>";

			if  (give_acl($config["id_user"], 0, "KW")) {
				// KB Add
				if (($sec2 == "operation/kb/browse") AND (isset($_GET["create"])))
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=kb&sec2=operation/kb/browse&create=1'>".__('Create KB item')."</a></li>";
			}

			if  (give_acl($config["id_user"], 0, "KM")) {
				// KB Manage Cat.
				if ($sec2 == "operation/kb/manage_cat")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_cat'>".__('Manage categories')."</a></li>";
				// KB Product Cat.
				if ($sec2 == "operation/inventories/manage_prod")
						echo "<li id='sidesel'>";
				else
						echo "<li>";
				echo "<a href='index.php?sec=kb&sec2=operation/inventories/manage_prod'>".__('Product types')."</a></li>";
				// KB Manage access
				if ($sec2 == "operation/kb/manage_perms")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_perms'>".__('Manage access')."</a></li>";
			}
		echo "</ul>";
	echo "</li>";
}
// Revisar el icono
// Downloads (FR)
if ($sec == "download" && give_acl ($config["id_user"], 0, "FRR") && $show_file_releases != MENU_HIDDEN) {
	
	if (($sec2 == "operation/download/browse" AND !isset($_GET["create"])) || (($sec2 == "operation/download/browse") AND (isset($_GET["create"]))) ||
	($sec2 == "operation/download/manage_types") || ($sec2 == "operation/download/manage_cat") || ($sec2 == "operation/download/manage_perms"))
		echo "<li title='".__('File releases')."' data-status='closed' id='sideseldownloads' class='sideselcolor'>";
	else
		echo "<li title='".__('File releases')."' data-status='closed' id='downloads'>";
	//echo "<a title='".__('File releases')."' href='index.php?sec=download&sec2=operation/download/browse&show_types=1'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('File releases')."</h1></li>";

			// Browser
			if ($sec2 == "operation/download/browse" AND !isset($_GET["create"]))
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=download&sec2=operation/download/browse&show_types=1'>".__('Browse')."</a></li>";

			if  (give_acl($config["id_user"], 0, "FRW")) {
				// Create / Manage downloads
				if (($sec2 == "operation/download/browse") AND (isset($_GET["create"])))
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=download&sec2=operation/download/browse&create=1'>".__('Create file release')."</a></li>";
			}
			if  (give_acl($config["id_user"], 0, "FRM")) {
				// FR Manage Types
				if ($sec2 == "operation/download/manage_types")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=download&sec2=operation/download/manage_types'>".__('Manage types')."</a></li>";

				// FR Manage Cat.
				if ($sec2 == "operation/download/manage_cat")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=download&sec2=operation/download/manage_cat'>".__('Manage categories')."</a></li>";
				
				// FR Manage access
				if ($sec2 == "operation/download/manage_perms")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=download&sec2=operation/download/manage_perms'>".__('Manage access')."</a></li>";
			}
		echo "</ul>";
	echo "</li>";
}

// God Mode
if ($sec == "godmode" && $show_setup != MENU_HIDDEN) {
	if (($sec2 == "godmode/setup/setup") || ($sec2 == "godmode/setup/update_manager") || ($sec2 == "godmode/setup/offline_update") || ($sec2 == "godmode/setup/filemgr") ||
	($sec2 == "godmode/setup/newsboard") || ($sec2 == "godmode/setup/dbmanager") || ($sec2 == "godmode/setup/links") || ($sec2 == "godmode/setup/event") || ($sec2 == "godmode/setup/audit") ||
	($sec2 == "godmode/setup/logviewer") || ($sec2 == "enterprise/godmode/setup/translate_string") || ($sec2 == "enterprise/godmode/setup/custom_screens_editor") || ($sec2 == "godmode/setup/setup_tags"))
		echo "<li title='".__('Setup')."' data-status='closed' id='sideselgestion' class='sideselcolor'>";
	else
		echo "<li title='".__('Setup')."' data-status='closed' id='gestion'>";
	//echo "<a   title='".__('Setup')."' href='index.php?sec=godmode&sec2=godmode/setup/setup'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('Setup')."</h1></li>";
	
			// Main Setup
			if ($sec2 == "godmode/setup/setup")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/setup'>".__('Setup')."</a></li>";
			$is_enterprise = false;
			if (file_exists ("enterprise/load_enterprise.php")) {
				$is_enterprise = true;
			}
			if( !$is_enterprise || ( $is_enterprise && $config['license'] != 'INTEGRIA-FREE' ) ){
				// Update Manager
				if ($sec2 == "godmode/setup/update_manager")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=godmode&sec2=godmode/setup/update_manager'>".__('Update')."</a></li>";
			}
			
			if($is_enterprise){
				// Offline update manager
				if ($sec2 == "godmode/setup/offline_update")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=godmode&sec2=godmode/setup/offline_update'>".__('Offline update')."</a></li>";
			}
		/* DISABLED UNTIL WE FIX IT
			// Update Manager
			if ($sec2 == "godmode/updatemanager/main")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/updatemanager/main'>".__('Update')."</a></li>";

			// Setup Update Manager
			if ($sec2 == "godmode/updatemanager/settings")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/updatemanager/settings'>".__('Configure updates')."</a></li>";
		*/
			// File/Image management
			if ($sec2 == "godmode/setup/filemgr")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/filemgr'>".__('File manager')."</a></li>";

			// Newsboard
			if ($sec2 == "godmode/setup/newsboard")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/newsboard'>".__('News board')."</a></li>";

			// DB manager
			if ($sec2 == "godmode/setup/dbmanager")
					echo "<li id='sidesel'>";
			else
					echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/dbmanager'>".__('DB Manager')."</a></li>";

			// Link management
			if ($sec2 == "godmode/setup/links")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/links'>".__('Links')."</a></li>";

			// Event management
			if ($sec2 == "godmode/setup/event")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/event'>".__('System events')."</a></li>";

			// Audit management
			if ($sec2 == "godmode/setup/audit")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/audit'>".__('Audit log')."</a></li>";

			// Log viewer
			if ($sec2 == "godmode/setup/logviewer")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/logviewer'>".__('Error log')."</a></li>";
			
			// Pandora FMS translation
			enterprise_include("godmode/sidemenu_translate_setup.php");

			// Pandora FMS Custom Screens
			enterprise_include("godmode/sidemenu_custom_screens_editor.php");

			// Tags management
			if ($sec2 == "godmode/setup/setup_tags")
				echo "<li id='sidesel'>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/setup_tags'>".__('Tags management')."</a></li>";		
		echo "</ul>";
	echo "</li>";
}
// Revisar icono y link con q
// Users
if (($sec == "users") OR ($sec == "user_audit") && $show_people != MENU_HIDDEN) {
	
	if (($sec2 == "operation/users/user_edit") || ($sec2 == "operation/users/user_spare_workunit") || 
	($sec2 == "operation/user_report/monthly") || ($sec2 == "operation/users/user_task_assigment"))
		echo "<li title='".__('Myself')."' data-status='closed' id='sideselmyself' class='sideselcolor'>";
	else
		echo "<li title='".__('Myself')."' data-status='closed' id='myself'>";
	//echo "<a   title='".__('Myself')."' href='index.php?sec=users&sec2=operation/user_report/report_monthly'>1</a>";
		echo "<ul>";
			echo "<li><h1>".__('Myself')."</h1></li>";
	
			// Edit my user
			if ($sec2 == "operation/users/user_edit")
				if (isset ($_REQUEST['id']) && $_REQUEST['id'] == $config['id_user'])
					echo "<li id='sidesel'>";
				else
					echo "<li>";
			else
				echo "<li>";
			echo "<a href='index.php?sec=users&sec2=operation/users/user_edit&id=".$config['id_user']."'>".__('Edit my user')."</a></li>";

			if (give_acl ($config["id_user"], 0, "PR") && $show_people != MENU_LIMITED && $show_people != MENU_MINIMAL) {
				// Add spare workunit
				if ($sec2 == "operation/users/user_spare_workunit")
				echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=users&sec2=operation/users/user_spare_workunit'>".__('Add spare workunit')."</a></li>";


				$now = date("Y-m-d H:i:s");
				$now_year = date("Y");
				$now_month = date("m");


				// My tasks
				if ($sec2 == "operation/users/user_task_assigment")
				echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo "<a href='index.php?sec=users&sec2=operation/users/user_task_assigment'>".__( "My task assigments")."</a></li>";
				
			echo "</ul>";
		echo "</li>";
	}
	
	// PEOPLE REPORTING	
	if  ((give_acl($config["id_user"], 0, "PR") || give_acl($config["id_user"], 0, "IR")) 
			&& $show_people != MENU_LIMITED && $show_people != MENU_MINIMAL) {
		
		if (($sec2 == "operation/user_report/report_full") || ($sec2 == "operation/user_report/report_monthly") || ($sec2 == "operation/user_report/report_annual") || 
		($sec2 == "operation/user_report/holidays_calendar") || ($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail") ||
		($sec2 == "enterprise/operation/user/schedule_reports"))
			echo "<li title='".__('People reporting')."' data-status='closed' id='sideselinformes' class='sideselcolor'>";
		else	
			echo "<li title='".__('People reporting')."' data-status='closed' id='informes'>";
		//echo "<a title='".__('People reporting')."'href='index.php?sec=users&sec2=operation/user_report/report_monthly'>1</a>";
			echo "<ul>";
				echo "<li><h1>".__('People reporting')."</h1></li>";		

		// Full report 
		if ($sec2 == "operation/user_report/report_full")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_full'>".__('Full report')."</a></li>";
		
		// My workunit report
		if ($sec2 == "operation/user_report/monthly")
		echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/monthly&month=$now_month&year=$now_year&id=".$config['id_user']."'>".__('Calendar Workunit report')."</a></li>";
		
		// Basic report (monthly)
		if ($sec2 == "operation/user_report/report_monthly")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_monthly'>".__('Monthly report')."</a></li>";

		// Basic report (annual)
		if ($sec2 == "operation/user_report/report_annual")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/report_annual'>".__('Annual report')."</a></li>";
		
		if ($sec2 == "operation/user_report/holidays_calendar")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=users&sec2=operation/user_report/holidays_calendar'>".__('Holidays calendar')."</a></li>";
		
		if ($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo '<a href="index.php?sec=users&sec2=operation/inventories/inventory_reports">'.__('Custom reports').'</a>';
		echo '</li>';

		enterprise_hook ('show_programmed_reports', array($sec2));

		echo "</ul></li>";	
	}
	else {
		
		if (($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail") || ($sec2 == "enterprise/operation/user/schedule_reports"))
			echo "<li title='".__('People reporting')."' data-status='closed' id='sideselinformes' class='sideselcolor'>";
		else	
			echo "<li title='".__('People reporting')."' data-status='closed' id='informes'>";
		//echo "<a title='".__('People reporting')."'href='index.php?sec=users&sec2=operation/user_report/report_monthly'>1</a>";
			echo "<ul>";
				echo "<li><h1>".__('People reporting')."</h1></li>";
		
				if ($sec2 == "operation/inventories/inventory_reports" || $sec2 == "operation/inventories/inventory_reports_detail")
					echo "<li id='sidesel'>";
				else
					echo "<li>";
				echo '<a href="index.php?sec=users&sec2=operation/inventories/inventory_reports">'.__('Custom reports').'</a>';
				echo '</li>';

				enterprise_hook ('show_programmed_reports', array($sec2));
		
		echo "</ul></li>";	
	}
//Revisar icono
	// PEOPLE MANAGEMENT
	if (give_acl($config["id_user"], 0, "UM") && $show_people != MENU_LIMITED){
		if($show_people != MENU_MINIMAL) {
			
			if (($sec2 == "godmode/usuarios/lista_usuarios") || ($sec2 == "godmode/usuarios/lista_usuarios") || ($sec2 == "godmode/usuarios/user_field_list") ||
			($sec2 == "godmode/usuarios/import_from_csv") || ($sec2 == "godmode/usuarios/role_manager") || ($sec2 == "godmode/grupos/lista_grupos") ||
			($sec2 == "enterprise/godmode/usuarios/menu_visibility_manager") || ($sec2 == "enterprise/godmode/usuarios/profile_list") ||
			($sec2 == "godmode/usuarios/configurar_usuarios") || ($sec2 == "godmode/grupos/configurar_grupo")) {
				echo "<li title='".__('People management')."' data-status='closed' id='sideselmanage' class='sideselcolor'>";
			}
			else {
				echo "<li title='".__('People management')."' data-status='closed' id='manage'>";
			}
			//echo "<a   title='".__('People management')."' href='index.php?sec=users&sec2=godmode/usuarios/lista_usuarios'>1</a>";
				echo "<ul>";
					echo "<li><h1>".__('People management')."</h1></li>";
			
					// Usermanager
					if ($sec2 == "godmode/usuarios/lista_usuarios" || $sec2 == "godmode/usuarios/configurar_usuarios"){
						if ($sec2 == "godmode/usuarios/lista_usuarios") {
							echo "<li id='sidesel'>";
						} else {
							echo "<li>";
						}
						echo "<a href='index.php?sec=users&sec2=godmode/usuarios/lista_usuarios'>".__('Manage users')."</a>";	
						if ($sec2 == "godmode/usuarios/configurar_usuarios") {
							echo "<li id='sidesel' style='margin-left: 15px;'>";
						}
						else {
							echo "<li style='margin-left: 15px;'>";
						}
						echo "<a href='index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&alta=1'>".__('Create user')."</a></li>";
					
					} else {
						echo "<li>";
						echo "<a href='index.php?sec=users&sec2=godmode/usuarios/lista_usuarios'>".__('Manage users')."</a>";
					}
					
					// Group manager
					if ($sec2 == "godmode/grupos/lista_grupos" || $sec2 == "godmode/grupos/configurar_grupo"){
						if ($sec2 == "godmode/grupos/lista_grupos"){ 
							echo "<li id='sidesel'>";
						} else {
							echo "<li>";
						}
						echo "<a href='index.php?sec=users&sec2=godmode/grupos/lista_grupos'>".__('Manage groups')."</a></li>";
						
						if ($sec2 == "godmode/grupos/configurar_grupo") {
							echo "<li id='sidesel' style='margin-left: 15px;'>";
						}
						else {
							echo "<li style='margin-left: 15px;'>";
						}
						echo "<a href='index.php?sec=users&sec2=godmode/grupos/configurar_grupo'>".__("Create group")."</a></li>";

					} else {
						echo "<li>";
						echo "<a href='index.php?sec=users&sec2=godmode/grupos/lista_grupos'>".__('Manage groups')."</a></li>";
					}
					
					
					enterprise_include ("operation/sidemenu_user_mgmt.php");
					
					if ($sec2 == "godmode/usuarios/user_field_list")
						echo "<li id='sidesel'>";
					else
						echo "<li>";   
					echo "<a href='index.php?sec=users&sec2=godmode/usuarios/user_field_list'>".__('Manage user fields')."</a></li>";
					echo "</li>";

					if ($sec2 == "godmode/usuarios/import_from_csv")
						echo "<li id='sidesel'>";
					else
						echo "<li>";
					echo "<a href='index.php?sec=users&sec2=godmode/usuarios/import_from_csv'>".__('Import from CSV')."</a></li>";
					echo "</li>";
					
					// Rolemanager
					if ($sec2 == "godmode/usuarios/role_manager")
						echo "<li id='sidesel'>";
					else
						echo "<li>";
					echo "<a href='index.php?sec=users&sec2=godmode/usuarios/role_manager'>".__('Manage roles')."</a></li>";
					
				}
				
				if($show_people != MENU_MINIMAL) {
					echo "</ul>";
					echo "</li>";
				}
	}
}

// Wiki
if ($sec == "wiki" && $show_wiki != MENU_HIDDEN)  {
	
	if (($sec2 == "operation/wiki/wiki"))
		echo "<li title='".__('Wiki')."' data-status='closed' id='sideselwiki' class='sideselcolor'>";
	else		
		echo "<li title='".__('Wiki')."' data-status='closed' id='wiki'>";
	//echo "<a   title='".__('Wiki')."' href='index.php?sec=users&sec2=operation/user_report/report_monthly'>1</a>";
		echo "<ul>";
		
		// Todo overview
		if ($sec2 == "operation/wiki/wiki")
			echo "<li id='sidesel'>";
		else
			echo "<li>";
		echo "<a href='index.php?sec=wiki&sec2=operation/wiki/wiki'>".__('Wiki')."</a></li>";
		echo "</li>";
			$is_enterprise = false;
			if (file_exists ("enterprise/include/functions_wiki.php")) {
				require_once ("enterprise/include/functions_wiki.php");
				$is_enterprise = true;
			}
			
			if (!give_acl ($config['id_user'], $id_grupo, "WW") || (get_standalone_user($config["id_user"]))) {

				//~ $conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
					//~ <li>{plugin:UPLOAD}</li>
					//~ <li>{RECENT_CHANGES}</li>
					//~ <li>{SYNTAX}</li>
					//~ {plugin:SIDEMENU}';
				$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
					<li>{RECENT_CHANGES}</li>
					<li>{SYNTAX}</li>
					{plugin:SIDEMENU}';
			}
			elseif (!give_acl ($config['id_user'], $id_grupo, "WM") || (get_standalone_user($config["id_user"]))) {

				$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
					<li>{plugin:UPLOAD}</li>
					<li>{RECENT_CHANGES}</li>
					<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
					{plugin:SIDEMENU}';
			}
			else {
				$translationAdminPages = __('Admin Pages');
				if ($is_enterprise) {
					$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
						<li>{plugin:ADMINPAGES}</li>
						<li>{plugin:UPLOAD}</li>
						<li>{RECENT_CHANGES}</li>
						<li>{EDIT}</li>
						<li>{HISTORY}</li>
						<li>{READ}</li>
						<li>{WRITE}</li>
						<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
						{plugin:SIDEMENU}';
				} else {
					$conf['fallback_template'] = '<li>{SEARCH_FORM}{SEARCH_INPUT}<br />{SEARCH_SUBMIT}{/SEARCH_FORM}</li>
					<li>{plugin:ADMINPAGES}</li>
					<li>{plugin:UPLOAD}</li>
					<li>{RECENT_CHANGES}</li>
					<li>{EDIT}</li>
					<li>{HISTORY}</li>
					<li><a href="index.php?sec=wiki&sec2=operation/wiki/wiki&action=syntax">Syntax</a></li>
					{plugin:SIDEMENU}';
				}
			}
			$conf['plugin_dir'] = 'include/wiki/plugins/';
			$conf['self'] = 'index.php?sec=wiki&sec2=operation/wiki/wiki' . '&';
			$conf_var_dir = 'var/';
			if (isset($config['wiki_plugin_dir']))
				$conf_plugin_dir = $config['wiki_plugin_dir'];
			if (isset($config['conf_var_dir']))
				$conf_var_dir = $config['conf_var_dir'];
			$conf['var_dir'] = $conf_var_dir;
		
			require_once("include/wiki/lionwiki_lib.php");	
			ob_start();
			lionwiki_show($conf, false);
			$form_search = ob_get_clean();
		echo $form_search;
		echo "</ul>";
		echo "</li>";
}
//revisar esto no lo entiendo muy bien
// EXTENSIONS
extensions_print_side_menu_subsection($sec, $sec2);

// Calendar box
if (give_acl ($config['id_user'], $id_grupo, "AR")) {
$month = get_parameter ("month", date ('n'));
$year = get_parameter ("year", date ('y'));
if (($sec2 == "operation/agenda/agenda"))
	echo "<li title='".__('Schedule')."' data-status='closed' id='sideselcalendario' class='sideselcolor'>";
else
	echo "<li title='".__('Schedule')."' data-status='closed' id='calendario'>";
	//echo "<a title='".__('Calendar')."' href='index.php?sec=agenda&sec2=operation/agenda/agenda'>1</a>";
	echo "<ul>";
		echo "<li><h1>".__('Calendar')."</h1></li>";
		
		echo '<li id="calendar_div">';
			echo generate_calendar ($year, $month, array(), 1, NULL, $config["first_day_week"]);
		echo '</li>';
		echo "<li>";
			echo '<a href="index.php?sec=agenda&sec2=operation/agenda/agenda">';
				echo "<img style='vertical-align:middle' width='20px' src='images/calendar_orange.png'>&nbsp;".__('Full calendar');
			echo '</a>';
		echo "</li>";
if (give_acl ($config['id_user'], $id_grupo, "AW")) {
		echo "<li>";
			if ($sec == 'agenda') {
				echo "<a href='javascript:;' onClick='show_agenda_entry(0, \"\", 0, true)'>
					<img src='images/add.png'>&nbsp;".__('Add entry')."</a>";
			} else {
				echo "<a href='javascript:;' onClick='show_agenda_entry(0, \"\", 0, false)'>
					<img style='vertical-align:middle' src='images/add.png'>&nbsp;".__('Add entry')."</a>";
			}
		echo "</li>";
}
	echo "</ul>";
echo "</li>";
}
// End of calendar box

// Testing boxes for side menus
$user_row = get_db_row ("tusuario", "id_usuario", $config['id_user']);

$avatar = $user_row["avatar"];
$realname = $user_row["nombre_real"];
$email = $user_row["direccion"];
$description = $user_row["comentarios"];
$userlang = $user_row["lang"];
$telephone = $user_row["telefono"];

$now = date("Y-m-d H:i:s");
$now_year = date("Y");
$now_month = date("m");
$working_month = get_parameter ("working_month", $now_month);
$working_year = get_parameter ("working_year", $now_year);

if (($sec2 == "operation/users/user_edit"))
	echo "<li title='".__('User info')."' data-status='closed' id='sideselinfo_usuario' class='sideselcolor'>";
else
	echo "<li title='".__('User info')."' data-status='closed' id='info_usuario'>";
	//echo "<a title='".__('User Info')."'href='index.php?sec=agenda&sec2=operation/users/user_edit'>1</a>";
	echo "<ul>";
		echo "<li><h1>".__('User Info')."</h1></li>";
		echo '<li>';
			echo '<div class="portletBody" id="userdiv">';
				echo '<div style="float: left; padding: 7px 7px 0px 0px; ">';
					if($avatar){
						echo '<img src="images/avatars/'.$avatar.'.png" style="height: 30px;" />';
					} else {
						echo '<img src="images/avatars/avatar_notyet.png" style="height: 30px;" />';
					}
				echo '</div>';
				echo '<div style="float: left;">';
					echo '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.$config['id_user'].'">';
						echo '<strong>'.$config['id_user'].'</strong>';
					echo '</a>';
					echo '<em style="display: block; margin-top: -2px;">'.$realname.'</em>';
				echo '</div>';
				echo "<div style='clear:both; margin-bottom: 10px;'></div>";
			echo '</div>';
			// Link to workunit calendar (month)
			echo '<a style="margin-right:5px;" href="index.php?sec=users&sec2=operation/user_report/monthly&month='.$now_month.'&year='.$now_year.'&id='.$config['id_user'].'" />';
			echo '<img src="images/workunit_report27.png" title="'.__('Workunit report').'" style="width: 24px;"/></a>';

			if (give_acl ($config["id_user"], 0, "IR")) {
				echo "&nbsp;";
				echo "<a style='margin-right:5px; '  href='index.php?sec=incidents&sec2=operation/incidents/incident_search&search_id_user=".$config['id_user']."'>";
				echo '<img src="images/tickets27.png" title="'.__('My tickets').'" style="width: 24px;"></a>';
			}
			if (give_acl ($config["id_user"], 0, "PR")) {
				// Link to Work user spare inster
				echo '<a style="margin-right:5px;" href="index.php?sec=users&sec2=operation/users/user_spare_workunit">';
				echo '<img src="images/workunit27.png" title="'.__('Workunit').'"  style="width: 24px;"></a>';

				// Link to User detailed graph view
				echo '<a style="margin-right:5px;" href="index.php?sec=users&sec2=operation/user_report/report_full&user_id='.$config['id_user'].'">';
				echo '<img src="images/fullgraphsreport27.png" title="'.__('Full graph report').'"  style="width: 24px;"></a>';

				// Week Workunit meter
				$begin_week = week_start_day ();
				$begin_week .= " 00:00:00";
				$end_week = date ('Y-m-d H:i:s', strtotime ("$begin_week + 1 week"));
				$total_hours = 5 * $config["hours_perday"];
				$sql = sprintf ('SELECT SUM(duration)
					FROM tworkunit WHERE timestamp > "%s"
					AND timestamp < "%s"
					AND id_user = "%s"',
					$begin_week, $end_week, $config['id_user']);
				$week_hours = get_db_sql ($sql);
				$ratio = $week_hours." ".__('over')." ".$total_hours;
				if ($week_hours < $total_hours)
					echo '<img src="images/exclamacion27.png" title="'.__('Week workunit time not fully justified').' - '.$ratio.'" style="width: 24px;"/>';
				else
					echo '<img src="images/ok27.png" title="'.__('Week workunit are fine').' - '.$ratio.'"  style="width: 24px;">';
				
				//echo "&nbsp;<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner=".$config["id_user"]."'><img src='images/paste_plain.png' title='".__("Work Orders")."' border=0></a>";
				echo "<a href='index.php?sec=users&sec2=operation/users/user_task_assigment'>
							<img src='images/tareas_asignadas.png' title='".__( "My task assigments")."' style='width: 24px;' /></a>";		
			}
			// Div for the calendar entry
			echo "<div class='dialog ui-dialog-content' id='agenda_entry'></div>";
		echo '</li>';
	echo "</ul>";
echo "</li>";
echo "</ul>";
echo "</nav>";

//~ 
//~ // Div for the calendar entry
echo "<div class='dialog ui-dialog-content' id='agenda_entry'></div>";
?>

<script type="text/javascript" src="include/js/agenda.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.slider.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript">
// Form validation
trim_element_on_submit('#text-id');
</script>
