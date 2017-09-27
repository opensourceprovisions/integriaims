<?php

// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Ramon Novoa, rnovoa@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Print child details recursively
function print_child_tree ($id, $depth = 0) {
	$children = get_inventory_children ($id);
	
	if ($children === false || sizeof ($children) == 0) {
		return;
	}
	
	foreach ($children as $child) {
		print_inventory_object ($child['id'], $children, array (), true, true, $depth);
		
		if ($child['id_contract']) {
			
			/* Only check ACLs if the inventory has a contract */
			if (! give_acl ($config['id_user'], get_inventory_group ($child['id']), "VR")) {
				continue;
			} else {
				print_child_tree ($child['id'], $depth + 1);
			}
		}
	}
}

require_once ('include/functions_inventories.php');

global $config;

check_login ();

$id = (int) get_parameter ('id');

if (! give_acl ($config['id_user'], get_inventory_group ($id), "VR")) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access inventory object tree");
	include ("general/noaccess.php");
	exit;
}

echo '<h3>'.__('Object').' #'.$id.'</h3>';
echo '<table id="tree" class="result_table listing" width="100%" >';
echo '<thead>';
echo '<th>'.__('ID').'</th>';
echo '<th>'.__('Name').'</th>';
echo '<th>'.__('Active Incidents').'</th>';
echo '<th>'.__('Company').'</th>';
echo '<th>'.__('Building').'</th>';
echo '<th>'.__('Title').'</th>';
echo '</thead>';
echo '<tbody>';
print_child_tree ($id, 1);
echo '</tbody>';
echo '</table>';
?>
