<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load globar vars
global $config;
check_login();

if (! give_acl ($config["id_user"], 0, "UM")) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

include_once('include/functions_user.php');

if (isset($_GET["borrar_usuario"])){ // if delete user

	$nombre = safe_input ($_GET["borrar_usuario"]);
	user_delete_user($nombre);
	
}

$offset = get_parameter ("offset", 0);
$search_text = get_parameter ("search_text", "");
$disabled_user = get_parameter ("disabled_user", -1);
$level = get_parameter ("level", -10);
$group = get_parameter ("group", 0);

echo '<h2>'.__('User management') . '</h2>';
echo '<h4>'.__('List users') . '</h4>';

echo "<div style='width:100%' class='divform'>";
	if(!isset($filter_form)){
		$filter_form = '';
	}
	form_search_users (false, $filter_form);
	echo "<form method=post action='index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&alta=1'>";
		echo "<table style='width:20%' class='search-table'>";
			echo "<tr>";
				echo "<td>";
					echo "<input type='button' onclick='process_massive_operation(\"enable_users\")' class='sub people' name='en' value='".__('Enable selected')."'>";
					echo "<input type='button' onclick='process_massive_operation(\"disable_users\")' class='sub people' name='dis' value='".__('Disable selected')."'>";
					echo "<input type='button' onclick='if (confirm(\"".__('Are you sure?')."\")) process_massive_operation(\"delete_users\");' class='sub ' name='del' value='".__('Delete selected')."'>";
					echo "<input type='submit' class='sub create' name='crt' value='".__('Create')."'>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
echo "</div>";

if(!isset($ajax)){
	$ajax = '';
}
if(!isset($clickin)){
	$clickin = '';
}
user_search_result($filter_form, $ajax, $config["block_size"], $offset, $clickin, $search_text, $disabled_user, $level, $group);
?>

<script type="text/javascript" src="include/js/integria_users.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript">
	// Change the state of all the checkbox depending on the checkbox of the header
	$('input[name="all_user_checkbox"]').change(function (event) {
		$('input.user_checkbox').prop('checked', $(this).prop('checked'));
	});
	
	trim_element_on_submit('#text-search_text');
</script>
