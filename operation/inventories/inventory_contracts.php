<?php

// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2007-2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

if (check_login () != 0) {
 	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access",
 		"Trying to access inventory viewer");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

if (! give_acl ($config['id_user'], get_inventory_group ($id), 'VR')) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
	include ("general/noaccess.php");
	return;
}

echo '<h3>'.__('Contract details on inventory object').' #'.$id.'</h3>';

$contracts = get_inventory_contracts ($id, false);

$table->class = 'inventory-contracts databox';
$table->width = '740px';
$table->colspan = array ();
$table->colspan[1][1] = 3;
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';

foreach ($contracts as $contract) {
	$table->data = array ();
	$table->id = 'inventory-contracts-table-'.$contract['id'];
	
	$table->data[0][0] = __('Company');
	$table->data[0][1] = get_db_value ('name', 'tcompany', 'id', $contract['id_company']);
	$table->data[0][2] = __('Contract');
	$table->data[0][3] = $contract['name'];
	
	$table->data[1][0] = __('Description');
	$table->data[1][1] = $contract['description'];
	
	$table->data[2][0] = __('Date begin');
	$table->data[2][1] = $contract['date_begin'];
	if ($contract['date_end'] != '0000-00-00') {
		$table->data[2][2] = __('Date end');
		$table->data[2][3] = $contract['date_end'];
	}
	
	$sla = get_sla ($contract['id_sla']);
	$table->data[3][0] = __('SLA');
	$table->data[3][1] = get_db_value ('name', 'tsla', 'id', $contract['id_sla']);
	
	$table->data[4][0] = __('Minimun response');
	$table->data[4][1] = $sla['min_response'].' '.__('Hours');
	$table->data[4][2] = __('Maximun response');
	$table->data[4][3] = $sla['max_response'].' '.__('Hours');
	
	$table->data[5][0] = __('Maximun tickets');
	$table->data[5][1] = $sla['max_incidents'];
	
	print_table ($table);
	echo '<p />';
}

?>
