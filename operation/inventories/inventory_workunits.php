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

check_login ();

$id = (int) get_parameter ('id');

if (! give_acl ($config['id_user'], get_inventory_group ($id), "VR")) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access inventory search");
	include ("general/noaccess.php");
	exit;
}

echo '<h3>'.__('Workunits done in inventory object').' #'.$id.'</h3>';

$workunits = get_inventory_workunits ($id);
foreach ($workunits as $workunit) {
	$title = get_db_value ('titulo', 'tincidencia', 'id_incidencia', $workunit['id_incident']);
	show_workunit_data ($workunit, $title);
}
?>
