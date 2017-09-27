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

if (! give_acl ($config["id_user"], 0, "PM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access Object Management");
	require ("general/noaccess.php");
	exit;
}

include_once("include/functions_objects.php");

//**********************************************************************
// Get actions
//**********************************************************************

$id = (int) get_parameter ('id');
$create = (bool) get_parameter ('create');
$insert_object = (bool) get_parameter ('insert_object');
$update_object = (bool) get_parameter ('update_object');
$delete_object = (bool) get_parameter ('delete_object');
$get_icon = (bool) get_parameter ('get_icon');

//**********************************************************************
// Ajax
//**********************************************************************

if ($get_icon) {
	$icon = (string) get_db_value ('icon', 'tobject_type', 'id', $id);
	
	if (defined ('AJAX')) {
		echo $icon;
		return;
	}
}

//**********************************************************************
// Tabs
//**********************************************************************

echo '<div id="tabs">';
echo '<h2>' . __('Inventory Object') . '</h2>';
echo '<h4>' . __('Management');
echo integria_help ("manage_objects", true);
/* Tabs list */
echo '<ul class="ui-tabs-nav">';

if (!empty($id)) {
	echo '<li class="ui-tabs-selected"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects&id=' . $id . '" title="'.__('Object details').'"><img src="images/eye.png"/></a></li>';
	echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list&id=' . $id . '" title="'.__('Fields').'"><img src="images/fields_tab.png"/></a></li>';
}
echo '</ul>';
echo '</h2>';
echo '</div>';

//**********************************************************************
// Actions
//**********************************************************************

// Creation
if ($insert_object) {
	$name = (string) get_parameter ("name");
	$icon = (string) get_parameter ("icon");
	$min_stock = (int) get_parameter ("min_stock");
	$description = (string) get_parameter ("description");
	$show_in_list = (int) get_parameter("show_in_list");
	
	$sql = sprintf ('INSERT INTO tobject_type (name, description, icon, min_stock, show_in_list)
			VALUES ("%s", "%s", "%s", %d, %d)',
			$name, $description, $icon, $min_stock, $show_in_list);
	$id = process_sql ($sql, 'insert_id');
	if (! $id) {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	} else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		//insert_event ("OBJECT TYPE CREATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Created object $id - $name");
	}
	$id = 0;
}

// Update
if ($update_object) {
	$name = (string) get_parameter ("name");
	$icon = (string) get_parameter ("icon");
	$min_stock = (int) get_parameter ("min_stock");
	$description = (string) get_parameter ("description");
	$show_in_list = (int) get_parameter("show_in_list");
	
	$sql = sprintf ('UPDATE tobject_type SET name = "%s", icon = "%s", min_stock = %d,
		description = "%s", show_in_list = %d WHERE id = %s',
		$name, $icon, $min_stock, $description, $show_in_list, $id);
		
	$result = process_sql ($sql);
	if (! $result) {
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true); 
	} else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		//insert_event ("PRODUCT UPDATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Inventory Management", "Updated object $id - $name");
	}
}

// Delete
if ($delete_object) {
	// Move parent who has this product to 0
	$sql = sprintf ('DELETE FROM tobject_type_field WHERE id_object_type = %d', $id);
	process_sql ($sql);
	
	$sql = sprintf ('DELETE FROM tobject_type WHERE id = %d', $id);
	$result = process_sql ($sql);

	if ($result)
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	else
		echo ui_print_error_message (__("Could not be deleted"), '', true, 'h3', true);
		
	$id = 0;
}

//**********************************************************************
// Object edition form
//**********************************************************************
if ($create || $id) {
	if ($create) {
		$icon = "";
		$description = "";
		$name = "";
		$id = -1;
		$min_stock = 0;
		$show_in_tree = 0;
	} else {
		$object = get_db_row ("tobject_type", "id", $id);
		$description = $object["description"];
		$name = $object["name"];
		$icon = $object["icon"];
		$min_stock = $object["min_stock"];
		$show_in_list = $object["show_in_list"];
	}

	/*if ($id == -1) {
		echo "<h3>".__('Create a new object')."</h3>";
	} else {
		echo "<h3>".__('Update existing object')."</h3>";
	}*/
	$table = new StdClass;
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	
	$table->colspan[3][0] = 2;
	$table->data = array ();
	
	$table->data[0][0] = print_input_text ('name', $name, '', 45, 100, true, __('Name'));
	if(!isset($show_in_list)){
		$show_in_list ='';	
	}
	$table->data[0][1] = '<label>' . __('Show in tree view') . print_help_tip(__('If this value is checked this object type will appear as a root inside inventory\'s tree view.'), true) . '</label>';
	$table->data[0][1] .= print_checkbox ('show_in_list', 1, $show_in_list, __('Show in tree view'));	
	
	$files = list_files ('images/objects/', "png", 1, 0);
	$table->data[1][0] = print_select ($files, 'icon', $icon, '', __('None'), "", true, false, false, __('Icon'));
	$table->data[1][0] .= objects_get_icon ($id, true);

	$table->data[1][1] = print_input_text ('min_stock', $min_stock, '', 45, 100, true, __('Min. stock'));

	$table->data[2][0] = print_textarea ('description', 10, 50, $description, '',
		true, __('Description'));
	$table->colspan[2][0] = 2;
	
	echo '<form id="form-manage_objects" method="post">';
	print_table ($table);
		echo '<div style="width:100%;">';
			unset($table->data);
			$table->width = '100%';
			$table->class = "button-form";
			if ($id == -1) {
				$button = print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"', true);
				$button .= print_input_hidden ('insert_object', 1, true);
			} else {
				$button = print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true);
				$button .= print_input_hidden ('id', $id, true);
				$button .= print_input_hidden ('update_object', 1, true);
			}
			
			$table->data[3][0] = $button;
			print_table ($table);
		echo '</div>';
	echo '</form>';
}

//**********************************************************************
// List objects
//**********************************************************************
if (! $id && ! $create) {
	$objects = get_db_all_rows_in_table ('tobject_type', 'name');
	$table = new StdClass;
	$table->width = '99%';
	echo "<div class='divresult'>";
	if ($objects !== false) {	
		$table->class = 'listing';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('ID');
		$table->head[1] = __('Name');
		$table->head[2] = __('Description');
		$table->head[3] = __('Items');
		$table->head[4] = __('Actions');
		$table->style = array ();
		$table->style[1] = 'font-weight: bold';
		$table->align = array ();
		
		//echo '<table width="90%" class="listing">';
		foreach ($objects as $object) {
			
			$has_external_fields = get_db_value_sql("SELECT COUNT(id) FROM tobject_type_field WHERE type='external' AND id_object_type=".$object['id']);
			
			$data = array ();
			$data[0] = ' <a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects&id='.
				$object['id'].'">'.$object['id'].'</a>';
			$data[1] = objects_get_icon ($object['id'], true);
			$data[1] .= ' <a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects&id='.
				$object['id'].'">'.$object['name'].'</a>';
			$data[2] = substr ($object["description"], 0, 200);
			$data[3] = objects_count_fields($object['id']);
			if ($has_external_fields) {
				$data[4] = '<a title=' . __("Edit external tables") . ' href=index.php?sec=inventory&sec2=operation/inventories/manage_external_tables&id='.
					$object["id"].'><img src="images/resolution.png"></a>';
			} else {
				$data[4] = '<img src="images/resolution_disabled.png">';
			}
			$data[4] .= '<a title=' . __("Fields") . ' href=index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list&id='.
				$object["id"].'><img src="images/page_white_text.png"></a>';
			$data[4] .= '<form style="display:inline;" method="post" onsubmit="if (!confirm(\''.__('Are you sure?').'\'))
				return false;">';
			$data[4] .= print_input_hidden ('delete_object', 1, true);
			$data[4] .= print_input_hidden ('id', $object["id"], true);
			$data[4] .= print_input_image ('delete', 'images/cross.png', 1, '', true, '',array('title' => __('Delete')));
			$data[4] .= '</form>';
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	} else {
		echo "<h4>".__('No objects')."</h4>";
	}
	echo "</div>";
	echo '<div class="divform">';
	echo '<form method="post">';
	echo '<table class="search-table">';
	echo '<tr>';
	echo '<td>';
	print_input_hidden ('create', 1);
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	echo "</form></div>";
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
		}).change();
	})
});


// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-manage_objects");
var rules, name;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_object_type: 1,
			object_type_name: function() { return $('#text-name').val() },
			object_type_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This object type already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
