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

if (! give_acl ($config['id_user'], 0, "VR")) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access inventory list");
	include ("general/noaccess.php");
	exit;
}

echo '<h2>' . __('Inventory') . '</h2>';
echo '<h4>' . __('Overview');
	echo integria_help ("inventory", true);
	echo "<div id='button-bar-title'>";
	echo "<ul>";
	
	echo "<li class='view_normal_button'>";
	echo "<a href='#' onclick='change_view_pure()'>" .
		print_image ("images/html_tabs.png", true, array("title" => __("Test"))) . "</a>";
	echo "</li>";
	
	echo "<li class='view_normal_button'>";
	echo "<a id='listview_form_submit' href='#' onclick='change_view_list()'>" .
		print_image ("images/list_view.png", true, array("title" => __("List view"))) .
		"</a>";
	echo "</li>";
	
	echo "<li class='view_normal_button'>";
	echo "<a id='treeview_form_submit' href='#' onclick='change_view_tree()'>" .
		print_image ("images/tree_view.png", true, array("title" => __("Tree view"))) .
		"</a>";
	echo "</li>";
	
	echo "<li class='view_pure_button' style='display: none'>";
		echo "<a href='#' onclick='change_return_view()'>" .
		print_image ("images/flecha_volver.png", true, array("title" => __("Test"))) . "</a>";
	echo "</li>";
	echo "</ul>";
	echo "</div>";

echo '</h4>';

$id = (int) get_parameter ('id');

// Delete oject
if (get_parameter("quick_delete")) {
	$id_inv = get_parameter("quick_delete");
	
	if (give_acl ($config['id_user'], 0, "VW")) {
		borrar_objeto ($id_inv);
		echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
		$pr = 1;
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "Object deleted","User ".$config['id_user']." deleted object #".$id_inv);
	} else {
		audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Forbidden","User ".$config['id_user']." try to delete object");
		echo ui_print_error_message (__('There was a problem deleting object'), '', true, 'h3', true);
		no_permission();
		$pr = 2;
	}
	
	$massive_number_loop = get_parameter ('massive_number_loop', -1);	
	// AJAX (Massive operations)
	if ($massive_number_loop > -1) {
		ob_clean();
		echo json_encode($massive_number_loop);
		return;
	}
}

/* Extras update, temporal patch (slerena, 26Ago2010) */
$update_extras = get_parameter("update_extras", 0);
if ($update_extras == 1){
	$generic_1 = (string) get_parameter ('generic_1');
	$generic_2 = (string) get_parameter ('generic_2');
	$generic_3 = (string) get_parameter ('generic_3');
	$generic_4 = (string) get_parameter ('generic_4');
	$generic_5 = (string) get_parameter ('generic_5');
	$generic_6 = (string) get_parameter ('generic_6');
	$generic_7 = (string) get_parameter ('generic_7');
	$generic_8 = (string) get_parameter ('generic8');
	$has_permission = give_acl ($config['id_user'], $id_group, "VW");
	if (! $has_permission) {
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to 
			update inventory extras without permission#".$id);
		include ("general/noaccess.php");
		exit;
	}
	$result = process_sql_update ('tinventory',
                array ('generic_1' => $generic_1,
                        'generic_2' => $generic_2,
                        'generic_3' => $generic_3,
                        'generic_4' => $generic_4,
                        'generic_5' => $generic_5,
                        'generic_6' => $generic_6,
                        'generic_7' => $generic_7,
                        'generic_8' => $generic_8),
                array ('id' => $id));

	if ($result !== false) {
		$result_msg = ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	} else {
		$result_msg = ui_print_error_message (__('There was an error updating inventory object'), '', true, 'h3', true);
	}
	echo $result_msg;
	
}

echo '<div class="result">';

require_once ('inventory_search.php');

echo '</div>';
echo '<div id="inventories-stats"></div>';

?>

<script type="text/javascript" src="include/js/jquery.metadata.js"></script>
<script type="text/javascript" src="include/js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="include/js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria_inventory.js"></script>
<script type="text/javascript" src="include/js/fixed-bottom-box.js"></script>

<script type="text/javascript">

var id_inventory;
var old_inventory = 0;

function tab_loaded (event, tab) {
	if (tab.index == 1) {
		configure_inventory_form (true);
		
		if (id_inventory == old_inventory) {
			return;
		}
		
		if ($(".inventory-menu").css ('display') != 'none') {
			$(".inventory-menu").slideUp ('normal', function () {
				configure_inventory_side_menu (id_inventory, false);
				$(this).slideDown ();
			});
		} else {
			configure_inventory_side_menu (id_inventory, false);
			$(".inventory-menu").slideDown ();
		}
		old_inventory = id_inventory;
	} else if (tab.index == 6) {
		$("table#tree tr").click (function () {
			id = this.id.split ("-").pop ();
			check_inventory (id);
		});
	}
}

function check_inventory (id) {
	values = Array ();
	values.push ({name: "page",
		value: "operation/inventories/inventory_detail"});
	values.push ({name: "id",
		value: id});
	values.push ({name: "check_inventory",
		value: 1});
	jQuery.get ("ajax.php",
		values,
		function (data, status) {
			if (data == 1) {
				show_inventory_details (id);
			} else {
				result_msg_error ("<?php echo __('Unable to load inventory')?> #" + id);
			}
		},
		"html"
	);
}

function show_inventory_details (id) {
	id_inventory = id;
	$("#tabs > ul").tabs ("url", 1, "ajax.php?page=operation/inventories/inventory_detail&id=" + id);
	$("#tabs > ul").tabs ("url", 2, "ajax.php?page=operation/inventories/inventory_extra&id=" + id);
	$("#tabs > ul").tabs ("url", 3, "ajax.php?page=operation/inventories/inventory_incidents&id=" + id);
	$("#tabs > ul").tabs ("url", 4, "ajax.php?page=operation/inventories/inventory_contracts&id=" + id);
	$("#tabs > ul").tabs ("url", 5, "ajax.php?page=operation/inventories/inventory_contacts&id=" + id);
	$("#tabs > ul").tabs ("url", 6, "ajax.php?page=operation/inventories/inventory_workunits&id=" + id);
	$("#tabs > ul").tabs ("url", 7, "ajax.php?page=operation/inventories/inventory_tree&id=" + id);
	$("#tabs > ul").tabs ("enable", 1).tabs ("enable", 2).tabs ("enable", 3)
		.tabs ("enable", 4).tabs ("enable", 5).tabs ("enable", 6).tabs ("enable", 7);

	if (tabs.data ("selected.tabs") == 1) {
		$("#tabs > ul").tabs ("load", 1);
	} else {
		$("#tabs > ul").tabs ("select", 1);
	}


}

var tabs;
var first_search = false;

$(document).ready (function () {
	tabs = $("#tabs > ul").tabs ({"load" : tab_loaded});
		
	$("table#inventory_search_result_table th").click (function () {
		$("table#inventory_search_result_table span.indent").remove ();
	});

	$("#tabs > ul").tabs ({"load" : tab_loaded});

<?php if ($id) : ?>
	id_inventory = <?php echo $id ?>;
	$(".inventory-menu").slideDown ();
	check_inventory (<?php echo $id; ?>);
<?php endif; ?>
	
	$("#saved-searches-form").submit (function () {
		search_values = get_form_input_values ('inventory_search_form');
		
		values = get_form_input_values (this);
		values.push ({name: "page", value: "operation/inventories/inventory_search"});
		$(search_values).each (function () {
			values.push ({name: "form_values["+this.name+"]", value: this.value});
		});
		values.push ({name: "create_custom_search", value: 1});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				result_msg (data);
			},
			"html"
		);
		return false;
	});
	
	$("#saved_searches").change (function () {
		if (this.value == 0) {
			$("#delete_custom_search").hide ();
			return;
		}
		$("#delete_custom_search").show ();
		
		values = Array ();
		values.push ({name: "page", value: "operation/inventories/inventory_search"});
		values.push ({name: "get_custom_search_values", value: 1});
		values.push ({name: "id_search", value: this.value});
		
		jQuery.get ("ajax.php",
			values,
			function (data, status) {
				load_form_values ("inventory_search_form", data);
				$("#inventory_search_form").submit ();
			},
			"json"
		);
	});
	
	$("#delete_custom_search").click (function () {
		id_search = $("#saved_searches").attr ("value");
		values = Array ();
		values.push ({name: "page", value: "operation/inventories/inventory_search"});
		values.push ({name: "delete_custom_search", value: 1});
		values.push ({name: "id_search", value: id_search});
		
		jQuery.get ("ajax.php",
			values,
			function (data, status) {
				result_msg (data);
				$("#delete_custom_search").hide ();
				$("#saved_searches").attr ("value", 0);
				$("option[value="+id_search+"]", "#saved_searches").remove ();
			},
			"html"
		);
		return false;
	});
	
	$("#inventory_search_form").submit (function () {
		$("#saved_searches_table td:gt(1)").show ();
	});
	
	$("#goto-inventory-form").submit (function () {
		id = $("#text-id", this).attr ("value");
		check_inventory (id);

		return false;
	});
	
	configure_inventory_search_form (<?php echo $config['block_size']?>,
		function (id, name) {
			check_inventory (id);
		},
		function (form) {
			val = get_form_input_values (form);
			
			val.push ({name: "page",
					value: "operation/inventories/inventory_search"});
			val.push ({name: "show_stats",
					value: 1});
			$("#inventories-stats").hide ().empty ();
			jQuery.post ("ajax.php",
				val,
				function (data, status) {
					$("#inventories-stats").empty ().append (data).slideDown ();
				},
				"html"
			);
		}
	);
});

</script>
