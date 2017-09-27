<?php
// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (check_login () != 0) {
	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access","Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

$id_incident = (int) get_parameter ('id');

$inventories = get_inventories_in_incident ($id_incident, false);

$table->class = 'listing';
$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Name');
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->size = array ();
$table->size[4] = '40px';
$table->size[5] = '40px';
$table->data = array();

//echo "<h3>".__('Inventory objects')."</h3>";

if (count ($inventories) == 0) {
	echo ui_print_error_message (__('There\'s no inventory objects associated to this ticket'), '', true, 'h3', true);
}
else {
	fill_inventories_table($inventories, $table);

	print_table ($table);

	unset($table);
}

$creator = get_db_value('id_creator','tincidencia','id_incidencia',$id_incident);
$company = get_user_company($config['id_user']);

echo "<h3>".__('Inventory objects related').print_help_tip (__('Inventory objects with a contract of the ticket creator company'), true)."</h3>";

if(empty($company)) {
	echo ui_print_error_message (__('The ticket creator has not company associated.'), '', true, 'h3', true);
	return;
}

$company_id = reset(array_keys($company));

$inventories = get_inventories_in_company ($company_id, false);

$table = new StdClass();
$table->class = 'listing';
$table->width = '100%';
$table->head = array ();
$table->head[0] = __('Name');
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->size = array ();
$table->size[4] = '40px';
$table->size[5] = '40px';
$table->data = array();

if (count ($inventories) == 0) {
	echo ui_print_error_message (__('There\'s no inventory objects associated to the creator company'), '', true, 'h3', true);
	return;
}

fill_inventories_table($inventories, $table);

print_table ($table);

unset($table);

?>
