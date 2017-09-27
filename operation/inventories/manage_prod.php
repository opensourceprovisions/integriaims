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

if (! give_acl ($config["id_user"], 0, "KM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access KB Management");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');
$create = (bool) get_parameter ('create');
$insert_product = (bool) get_parameter ('insert_product');
$update_product = (bool) get_parameter ('update_product');
$delete_product = (bool) get_parameter ('delete_product');
$get_icon = (bool) get_parameter ('get_icon');

if ($get_icon) {
	$icon = (string) get_db_value ('icon', 'tkb_product', 'id', $id);
	
	if (defined ('AJAX')) {
		echo $icon;
		return;
	}
}

// Database Creation
// ==================
if ($insert_product) {
	$name = (string) get_parameter ("name");
	$icon = (string) get_parameter ("icon");
	$description = (string) get_parameter ("description");
	
	$sql = sprintf ('INSERT INTO tkb_product (name, description, icon) 
			VALUES ("%s", "%s", "%s")',
			$name, $description, $icon);
	$id = process_sql ($sql, 'insert_id');
	if (! $id) {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		//insert_event ("PRODUCT CREATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Created product $id - $name");
	}
	$id = 0;
}

// Database UPDATE
if ($update_product) {
	$name = (string) get_parameter ("name");
	$icon = (string) get_parameter ("icon");
	$description = (string) get_parameter ("description");
	
	$sql = sprintf ('UPDATE tkb_product SET name = "%s", icon = "%s",
		description = "%s" WHERE id = %s',
		$name, $icon, $description, $id);
	$result = process_sql ($sql);
	if (! $result) {
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		//insert_event ("PRODUCT UPDATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Updated product $id - $name");
	}
}


// Database DELETE
// ==================
if ($delete_product) {
	$sql = sprintf ('DELETE FROM tkb_product WHERE id = %d', $id);
	$result = process_sql ($sql);

	if ($result)
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	else
		echo ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
	unset ($id);
}

if ($create || $id) {
	if ($create) {
		$icon = "";
		$description = "";
		$name = "";
		$id = -1;
	} else {
		$product = get_db_row ("tkb_product", "id", $id);
		$description = $product["description"];
		$name = $product["name"];
		$icon = $product["icon"];
	}
	
	echo "<h2>".__('Product management')."</h2>";
	if ($id == -1) {
		echo "<h4>".__('Create a new product')."</h4>";
	} else {
		echo "<h4>".__('Update existing product')."</h4>";
	}
	
	$table = new StdClass();
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	$table->data = array ();
	
	$table->data[0][0] = print_input_text ('name', $name, '', 45, 100, true, __('Name'));
	
	$files = list_files ('images/products/', "png", 1, 0);
	$table->data[1][0] = print_select ($files, 'icon', $icon, '', __('None'), "", true, false, false, __('Icon'));
	$table->data[1][0] .= print_product_icon ($id, true);
	$table->data[2][0] = print_textarea ('description', 10, 50, $description, '',
		true, __('Description'));
		
	if ($id == -1) {
		$button = print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true);
		$button .= print_input_hidden ('insert_product', 1, true);
	} else {
		$button = print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true);
		$button .= print_input_hidden ('id', $id, true);
		$button .= print_input_hidden ('update_product', 1, true);
	}
	
	//$table->data['button'][0] = $button;
	
	echo '<form id="form-product_type" method="post" action="index.php?sec=kb&sec2=operation/inventories/manage_prod">';
	print_table ($table);
	echo "<div class='button-form'>" . $button . "</div>";
	echo "</form>";
}

// Show list of product
// =======================
if (! $id && ! $create) {
	$products = get_db_all_rows_in_table ('tkb_product', 'name');
	
	
	
	echo "<h2>".__('Defined products')."</h2>";
	echo "<h4>".__('List products')."</h4>";
	
	echo '<div class="divform">';
	echo '<form method="post" action="index.php?sec=kb&sec2=operation/inventories/manage_prod" >';
	echo '<table class="search-table">';
	echo '<tr>';
	echo '<td>';
	print_input_hidden ('create', 1);
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
	echo "</table></form></div>";
	
	echo '<div class="divresult">';
	if ($products !== false) {
		
		$table = new StdClass();
		$table->width = '100%';
		$table->class = 'listing';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('ID');
		$table->head[1] = __('Icon');
		$table->head[2] = __('Name');
		$table->head[3] = __('Description');
		$table->head[4] = __('Items');
		$table->head[5] = __('Delete');
		$table->style = array ();
		$table->style[2] = 'font-weight: bold';
		$table->align = array ();
		$table->align[5] = 'center';
		
		echo '<table width="99%" class="listing">';
		foreach ($products as $product) {
			$data = array ();
			$data[0] = $product['id'];
			$data[1] = print_product_icon ($product['id'], true);
			$data[2] = '&nbsp;&nbsp;<a href="index.php?sec=kb&sec2=operation/inventories/manage_prod&id='.
				$product['id'].'">'.$product['name'].'</a>';
			$data[3] = substr ($product["description"], 0, 200);
			$data[4] = get_db_value ('COUNT(id)', 'tkb_data', 'id_product', $product['id']);
			$data[5] = '<a href=index.php?sec=kb&sec2=operation/inventories/manage_prod&delete_product=1&id='.
				$product["id"].' onClick="if (!confirm(\''.__('Are you sure?').'\'))
				return false;"><img src="images/cross.png"></a>';
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
	echo "</div>";
} // end of list

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	$("#icon").change (function () {
		data = this.value;
		$("#product-icon").fadeOut ('normal', function () {
			$("#product-icon").attr ("src", "images/products/"+data).fadeIn ();
		});
	})
});


// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-product_type");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_product_type: 1,
			product_type_name: function() { return $('#text-name').val() },
			product_type_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This product type already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
