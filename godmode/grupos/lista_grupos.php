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

check_login();

include_once('include/functions_user.php');

$get_group_details = (bool) get_parameter ('get_group_details');
$id = (int) get_parameter ('id');
$delete_user = get_parameter('delete_user', 0);

if ($delete_user) {
	$id_user_delete = get_parameter('id_user_delete');
	$id_profile = get_parameter('id_profile');
	$id_group = get_parameter('id_group');
	user_delete_user_profile_group($id_user_delete, $id_profile, $id_group);
}

if ($get_group_details) {
	if (! give_acl ($config["id_user"], $id, "IR"))
		return;
	
	$default_user = get_db_value ('id_user_default', 'tgrupo', 'id_grupo', $id);
	$real_name = get_db_value ('nombre_real', 'tusuario', 'id_usuario', $default_user);
	$group = array ();
	$group['forced_email'] = get_db_value ('forced_email', 'tgrupo', 'id_grupo', $id);
	$group['user_real_name'] = $real_name;
	$group['id_user_default'] = $default_user;
	echo json_encode ($group);
	if (defined ('AJAX'))
		return;
}

if (! give_acl ($config["id_user"], 0, "UM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access group management");
	require ("general/noaccess.php");
	exit;
}

echo '<h2>'.__('Group management').'</h2>';
echo '<h4>'.__('List groups').'</h4>';

$create_group = (bool) get_parameter ('create_group');
$update_group = (bool) get_parameter ('update_group');
$delete_group = (bool) get_parameter ('delete_group');

// Create group
if ($create_group) {
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
    $parent = (int) get_parameter ('parent');
	$soft_limit = (int) get_parameter ('soft_limit', 0);
	$hard_limit = (int) get_parameter ('hard_limit', 0);
	$enforce_soft_limit = (int) get_parameter ('enforce_soft_limit');
	$id_inventory = (int) get_parameter("id_inventory", 0);
	$banner = (string) get_parameter ('banner');
	$forced_email = (int) get_parameter ('forced_email', 0);
	$id_user_default = (string) get_parameter ('id_user');
	$id_sla = (int) get_parameter ("id_sla");
	$autocreate_user = (int) get_parameter("autocreate_user", 0);
	$grant_access = (int) get_parameter("grant_access", 0);
	$send_welcome = (int) get_parameter("send_welcome", 0);
	$default_company = (int) get_parameter("default_company", 0);
	$welcome_email = (string) get_parameter ('welcome_email', "");
	$email_queue = (string) get_parameter ('email_queue', "");
	$default_profile = (int) get_parameter ('default_profile', 0);
	$user_level = (int) get_parameter ('user_level', 0);
	$incident_type = (int) get_parameter ('incident_type', 0);
	$email_from = (string) get_parameter ('email_from', "");
	$email_group = (string) get_parameter ('email_group', "");	

	$sql = "INSERT INTO tgrupo (nombre, icon, forced_email, banner, id_user_default, 
					soft_limit, hard_limit, enforce_soft_limit, id_sla, parent, id_inventory_default,
					autocreate_user, grant_access, send_welcome, default_company, welcome_email, 
					email_queue, default_profile, nivel, id_incident_type, email_from, email_group) 
					VALUES ('$name', '$icon', $forced_email, '$banner', '$id_user_default', $soft_limit, $hard_limit, 
						$enforce_soft_limit, $id_sla, $parent, $id_inventory, $autocreate_user, $grant_access,
						'$send_welcome', $default_company, '$welcome_email', '$email_queue', $default_profile, 
						$user_level, $incident_type, '$email_from', '$email_group')";
						
	$id = process_sql ($sql, 'insert-id');	
	if ($id === false)
		echo ui_print_error_message (__('There was a problem creating group'), '', true, 'h3', true);
	else {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Group management", "Created group $name");
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true); 
	}
	$id = 0;
}

// Update group
if ($update_group) {
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$parent = (int) get_parameter ('parent');
	$banner = (string) get_parameter ('banner');
	$forced_email = (bool) get_parameter ('forced_email');
	$id_user_default = (string) get_parameter ('id_user');
	$soft_limit = (int) get_parameter ('soft_limit');
	$hard_limit = (int) get_parameter ('hard_limit');
	$enforce_soft_limit = (bool) get_parameter ('enforce_soft_limit');
	$id_sla = get_parameter ("id_sla");
	$id_inventory = (int) get_parameter("id_inventory", 0);
	$autocreate_user = (int) get_parameter("autocreate_user", 0);
	$grant_access = (int) get_parameter("grant_access", 0);
	$send_welcome = (int) get_parameter("send_welcome", 0);
	$default_company = (int) get_parameter("default_company", 0);
	$welcome_email = (string) get_parameter ('welcome_email', "");
	$email_queue = (string) get_parameter ('email_queue', "");
	$default_profile = (int) get_parameter ('default_profile', 0);
	$user_level = (int) get_parameter ('user_level', 0);
	$incident_type = (int) get_parameter ('incident_type', 0);
	$email_from = (string) get_parameter ('email_from', "");
	$email_group = (string) get_parameter ('email_group', "");
	
	$sql = sprintf ('UPDATE tgrupo
		SET parent = %d, nombre = "%s", icon = "%s", forced_email = %d, 
		banner = "%s", id_user_default = "%s", soft_limit = %d, hard_limit = %d, 
		enforce_soft_limit = %d, id_sla = %d, id_inventory_default = %d, 
		autocreate_user = %d, grant_access = %d, send_welcome = %d,
		default_company = %d, welcome_email = "%s", email_queue = "%s", 
		default_profile = %d, nivel = %d, id_incident_type = %d, email_from = "%s", email_group = "%s"
		WHERE id_grupo = %d',
		 $parent, $name, $icon, $forced_email, $banner, $id_user_default, 
		 $soft_limit, $hard_limit, $enforce_soft_limit, $id_sla, $id_inventory, 
		 $autocreate_user, $grant_access, $send_welcome, $default_company, 
		 $welcome_email, $email_queue, $default_profile,$user_level, 
		 $incident_type, $email_from, $email_group, $id);

	$result = process_sql ($sql);

	if ($result === false)
		echo ui_print_error_message (__('There was a problem modifying group'), '', true, 'h3', true);
	else {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Group management", "Modified group now called '$name'");
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	}
}

// Delete group
if ($delete_group) {
	$name = get_db_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = $id");
	$sql = sprintf ('DELETE FROM tgrupo WHERE id_grupo = %d', $id);
	$result = process_sql ($sql);
	if ($result === false) {
		echo ui_print_error_message (__('There was a problem deleting group'), '', true, 'h3', true); 
	} else {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Group management", "Deleted group '$name'");
		echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	}
}

$offset = get_parameter ("offset", 0);
$search_text = get_parameter ("search_text", "");
echo "<div class='divform'>";
	echo "<form name='bskd' method=post action='index.php?sec=users&sec2=godmode/grupos/lista_grupos'>";
		echo "<table class='search-table' style='width: 100%;'>";
			echo "<tr>";
				echo "<td>";
				echo "<b>".__('Search text')."</b>&nbsp;&nbsp;";
					print_input_text ("search_text", $search_text, '', 20, 0, false);
				echo "</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td>";
					print_submit_button (__('Search'), '', false, 'class="sub next"', false, false);
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
	echo '<form method="post" action="index.php?sec=users&sec2=godmode/grupos/configurar_grupo">';
		echo "<table class='search-table' style='width: 100%;'>";
			echo "<tr>";
				echo "<td>";
					print_submit_button (__('Create'), 'create_btn', false, 'class="sub next"');
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo '</form>';
echo "</div>";

$groups = get_db_all_rows_sql ("SELECT * FROM tgrupo WHERE nombre LIKE '%$search_text%' ORDER BY nombre");

$groups = print_array_pagination ($groups, "index.php?sec=users&sec2=godmode/grupos/lista_grupos");

print_groups_table ($groups);

?>

<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">
trim_element_on_submit('#text-search_text');
</script>
