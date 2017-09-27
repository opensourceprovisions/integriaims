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

if (! give_acl($config["id_user"], 0, "VM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access Manufacturer section");
	require ("general/noaccess.php");
	exit;
}

echo '<h2>' . __('Manufacturers') . '</h2>';
echo '<h4>' . __('Management').'</h4>';

$id = (int) get_parameter ('id');
$new_manufacturer = (bool) get_parameter ('new_manufacturer');
$create_manufacturer = (bool) get_parameter ('create_manufacturer');
$update_manufacturer = (bool) get_parameter ('update_manufacturer');
$delete_manufacturer = (bool) get_parameter ('delete_manufacturer');

// CREATE
if ($create_manufacturer) {
	$name = (string) get_parameter ('name');
	$comments = (string) get_parameter ('comments');
	$address = (string) get_parameter ('address');
	$id_company_role = (int) get_parameter ('id_company_role');
	$id_sla = (int) get_parameter ('id_sla');

	$sql = sprintf ('INSERT INTO tmanufacturer (`name`, `comments`, `address`,
		`id_sla`, `id_company_role`)
		VALUE ("%s", "%s", "%s", %d, %d)',
		$name, $comments, $address, $id_sla, $id_company_role);

	$id = process_sql ($sql, 'insert_id');
	if ($id === false) {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Manufacturer", "Created manufacturer $name");
	}
	$id = 0;
}

// UPDATE
if ($update_manufacturer) {
	$id = (string) get_parameter ('id');
	$name = (string) get_parameter ('name');
	$comments = (string) get_parameter ('comments');
	$address = (string) get_parameter ('address');
	$id_company_role = (int) get_parameter ('id_company_role');
	$id_sla = (int) get_parameter ('id_sla');

	$sql = sprintf ('UPDATE tmanufacturer
		SET address = "%s", id_sla = %d, id_company_role = %d,
		comments = "%s", name = "%s" WHERE id = %d',
		$address, $id_sla, $id_company_role, $comments, $name, $id);
	$result = process_sql ($sql);
	if ($result === false)
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Manufacturer", "Updated manufacturer $name");
	}

	$id = 0;
}

// DELETE
// ==================
if ($delete_manufacturer) {
	$name = get_db_value ('name', 'tmanufacturer', 'id', $id);
	$sql = sprintf ('DELETE FROM tmanufacturer WHERE id = %d', $id);
	process_sql ($sql);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Manufacturer", "Deleted manufacturer $name");
	echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	$id = 0;
}

// FORM (Update / Create)
if ($id || $new_manufacturer) {
	if ($new_manufacturer) {
		$id = 0;
		$name = "";
		$comments = "";

		$address = "";
		$id_sla = "";
		$id_company_role = "";
	} else {
		$manufacturer = get_db_row ('tmanufacturer', 'id', $id);
		$name = $manufacturer["name"];
		$comments = $manufacturer["comments"];
		$address = $manufacturer["address"];
		$id_sla = $manufacturer["id_sla"];
		$id_company_role = $manufacturer["id_company_role"];
	}
	
	$table = new StdClass();
	$table->width = "100%";
	$table->class = "search-table-button";
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan[1][0] = 2;
	$table->colspan[2][0] = 2;
	
	$table->data[0][0] = print_input_text ("name", $name, "", 60, 100, true, __('Name'));
	$table->data[0][1] = print_select_from_sql ('SELECT id, name FROM tcompany_role ORDER BY name',
		'id_company_role', $id_company_role, '', __('Select'), '0', true, false, false, __('Company role'));
	
	/*$table->data[1][1] = print_select_from_sql ('SELECT id, name FROM tsla ORDER BY name',
		'id_sla', $id_sla, '', __('Select'), '0', true, false, false, __('Base SLA'));*/
	
	$table->data[1][0] = print_textarea ("address", 4, 1, $address, '', true, __('Address'));
	$table->data[2][0] = print_textarea ("comments", 10, 1, $comments, '', true, __('Comments'));
	
	echo '<form id="form-manufacturer_detail" method="post" action="index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail">';
	print_table ($table);
		echo '<div style="width:100%;">';
			unset($table->data);
			$table->width = '100%';
			$table->class = "button-form";
			if ($id) {
				$button = print_submit_button (__('Update'), "update_btn", false, 'class="sub upd"', true);
				$button .= print_input_hidden ('update_manufacturer', 1, true);
				$button .= print_input_hidden ('id', $id, true);
			} else {
				$button = print_input_hidden ('create_manufacturer', 1, true);
				$button .= print_submit_button (__('Create'), "create_btn", false, 'class="sub create"', true);
			}
			$table->data[3][0] = $button;
			$table->colspan[3][0] = 2;
			print_table ($table);
		echo "</div>";
	echo "</form>";
}
else {
	$search_text = (string) get_parameter ('search_text');
	
	$where_clause = "";
	if ($search_text != "") {
		$where_clause = sprintf ('WHERE name LIKE "%%%s%%" OR comments LIKE "%%%s%%"',
			$search_text, $search_text);
	}
	
	$table = new StdClass();
	$table->width = '100%';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = __('Search');
	$table->data[0][0] .= print_input_text ("search_text", $search_text, "", 25, 100, true);
	$table->data[1][0] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);;
	
	echo "<div class='divform'>";
		echo '<form method="post" action="index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail">';
			print_table ($table);
		echo '</form>';
		echo '<form method="post" action="index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail">';
			unset ($table->data);
			$table->data[0][0] = print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"', true);
			$table->data[0][0] .= print_input_hidden ('new_manufacturer', 1);
			print_table ($table);
		echo '</form>';
	echo '</div>';
	
	$sql = "SELECT * FROM tmanufacturer $where_clause ORDER BY name";
	$manufacturers = get_db_all_rows_sql ($sql);
	echo "<div class='divresult'>";
	if ($manufacturers !== false) {
		$manufacturers = print_array_pagination ($manufacturers, "index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail");
		
		unset ($table);
		$table = new StdClass();
		$table->width = "100%";
		$table->class = "listing";
		$table->data = array ();
		$table->style = array ();
		$table->style[1] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = __('ID');
		$table->head[1] = __('Name');
		$table->head[2] = __('Address');
		$table->head[3] = __('Company role');
		$table->head[4] = __('Delete');
		
		foreach ($manufacturers as $manufacturer) {
			$data = array ();
			
			$data[0] = '<a href="index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail&id='.
				$manufacturer['id'].'">'.$manufacturer['id'].'</a>';
			$data[1] = '<a href="index.php?sec=inventory&sec2=operation/manufacturers/manufacturer_detail&id='.
				$manufacturer['id'].'">'.$manufacturer['name'].'</a>';
			$data[2] = substr ($manufacturer['address'], 0, 50). "...";
			$data[3] = get_db_value ('name', 'tcompany_role', 'id', $manufacturer['id_company_role']);
			$data[4] = '<a href="index.php?sec=inventory&
						sec2=operation/manufacturers/manufacturer_detail&
						delete_manufacturer=1&id='.$manufacturer['id'].'"
						onClick="if (!confirm(\''.__('Are you sure?').'\'))
						return false;">
						<img src="images/cross.png"></a>';
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	} else {
		echo ui_print_error_message (__("Empty manufacturers"), '', true, 'h3', true);
	}
	echo "</div>";
} // end of list

?>


<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

// Form validation
trim_element_on_submit('#text-search_text');
trim_element_on_submit('#text-name');
validate_form("#form-manufacturer_detail");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_manufacturer: 1,
			manufacturer_name: function() { return $('#text-name').val() },
			manufacturer_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This manufacturer already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
