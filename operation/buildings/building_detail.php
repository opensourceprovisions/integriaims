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
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access company section");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');
$new_building = (bool) get_parameter ('new_building');
$create_building = (bool) get_parameter ('create_building');
$update_building = (bool) get_parameter ('update_building');
$delete_building = (bool) get_parameter ('delete_building');

// CREATE
if ($create_building) {
	$name = get_parameter ("name","");
	$description = get_parameter ("description", "");
	$sql = sprintf ('INSERT INTO tbuilding (`name`, `description`)
		VALUE ("%s", "%s")', $name, $description);

	$id = process_sql ($sql, 'insert-id');
	if ($id === false)
		echo '<h3 class="error">'.__('Could not be created').'</h3>';
	else {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>';
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Building", "Created building $id - $name");
	}
	$id = 0;
}

// UPDATE
if ($update_building) {
	$name = get_parameter ("name","");
	$description = get_parameter ("description", "");

	$sql = sprintf ('UPDATE tbuilding
		SET description = "%s", name = "%s" WHERE id = %d',
		$description, $name, $id);

	$result = process_sql ($sql);
	if ($result === false)
		echo '<h3 class="error">'.__('Building cannot be updated').'</h3>';
	else {
		echo '<h3 class="suc">'.__('Successfully updated').'</h3>';
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Building", "Updated building $id - $name");
	}
	$id = 0;
}

// DELETE
if ($delete_building) {
	$name = get_db_value ('name', 'tbuilding', 'id', $id);
	$sql = sprintf ('DELETE FROM tbuilding WHERE id = %d', $id);
	process_sql ($sql);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Building", "Deleted building $id - $name");
	echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	$id = 0;
}

echo '<h2>'.__('Building management').'</h2>';

// FORM (Update / Create)
if ($id || $new_building) {
	if ($new_building) {
		$id = 0;
		$name = "";
		$description = "";
	} else {
		$building = get_db_row ('tbuilding', 'id', $id);
		$name = $building['name'];
		$description = $building['description'];
	}
	
	$table->width = "90%";
	$table->class = "databox";
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan[1][0] = 2;
	
	$table->data[0][0] = print_input_text ('name', $name, '', 60, 100, true, __('Building name'));
	$table->data[1][0] = print_textarea ('description', 14, 1, $description, '', true, __('Description'));
	
	
	echo '<form method="post" action="index.php?sec=inventory&sec2=operation/buildings/building_detail">';
	print_table ($table);
	echo '<div class="button" style="width: '.$table->width.'">';
	if ($id) {
		print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', false);
		print_input_hidden ('update_building', 1);
		print_input_hidden ('id', $id);
	} else {
		print_submit_button (__('Create'), 'create_btn', false, 'class="sub next"', false);
		print_input_hidden ('create_building', 1);
	}
	echo '</div>';
	echo '</form>';
} else {
	$search_text = (string) get_parameter ('search_text');
	
	$where_clause = '';
	if ($search_text != "") {
		$where_clause .= sprintf ('WHERE name LIKE "%%%s%%"', $search_text);
	}

	$table->width = '400px';
	$table->class = 'search-table';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = __('Search');
	$table->data[0][1] = print_input_text ("search_text", $search_text, "", 25, 100, true);
	$table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);;
	
	echo '<form method="post" action="index.php?sec=inventory&sec2=operation/types/type_detail">';
	print_table ($table);
	echo '</form>';

	$sql = "SELECT * FROM tbuilding $where_clause ORDER BY name";
	$buildings = get_db_all_rows_sql ($sql);
	
	if ($buildings !== false) {
		$table->width = '90%';
		$table->class = 'listing';
		$table->data = array ();
		$table->size = array ();
		$table->size[2] = '40px';
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->head[0] = __('Name');
		$table->head[1] = __('Description');
		$table->head[2] = __('Delete');
		
		foreach ($buildings as $building) {
			$data = array ();
			
			$data[0] = '<a href=index.php?sec=inventory&sec2=operation/buildings/building_detail&id='.
				$building['id'].'">'.$building['name'].'</a>';
			$data[1] = substr ($building['description'], 0, 50). "...";
			$data[2] = '<a href="index.php?sec=inventory&
						sec2=operation/buildings/building_detail&
						delete_building=1&id='.$building['id'].'"
						onClick="if (!confirm(\''.__('Are you sure?').'\'))
						return false;"><img src="images/cross.png"></a>';
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
	
	echo '<form method="post" action="index.php?sec=inventory&sec2=operation/buildings/building_detail">';
	echo '<div class="button" style="width: '.$table->width.'">';
	print_submit_button (__('Create'), 'new_btn', false, 'class="sub next"');
	print_input_hidden ('new_building', 1);
	echo '</div>';
	echo '</form>';
}
?>
