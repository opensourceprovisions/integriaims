<?php

global $config;

check_login ();

$id = (int) get_parameter ('id');

$is_enterprise = false;

if (file_exists ("enterprise/include/functions_inventory.php")) {
	require_once ("enterprise/include/functions_inventory.php");
	$is_enterprise = true;
}

if ($is_enterprise) {
	$read_permission = inventory_check_acl($config['id_user'], $id);
	
	if (!$read_permission) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
		include "general/error_perms.php";
		exit;
	}
}

//**********************************************************************
// Tabs
//**********************************************************************
if(!isset($inventory_name)){
	$inventory_name = '';
}
print_inventory_tabs('tracking', $id, $inventory_name);

if (! $id) {
	require ("general/noaccess.php");
	exit;
}

$trackings = get_db_all_rows_field_filter ('tinventory_track', 'id_inventory', $id, 'timestamp DESC');

if ($trackings !== false) {
	$table = new stdClass;
	$table->width = "99%";
	$table->class = 'listing';
	$table->data = array ();
	$table->head = array ();
	$table->head[1] = __('Description');
	$table->head[2] = __('User');
	$table->head[3] = __('Date');
	
	foreach ($trackings as $tracking) {
		$data = array ();
		
		$data[0] = $tracking['description'];
		$data[1] = dame_nombre_real ($tracking['id_user']);
		$data[2] = $tracking['timestamp'];
		
		array_push ($table->data, $data);
	}
	echo "<center>";
	print_table ($table);
	echo "</center>";
} else {
	echo __('No data available');
}
?>
