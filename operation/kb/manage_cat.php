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

if (! give_acl ($config["id_user"], 0, "KM")) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access KB Management");
	require ("general/noaccess.php");
	exit;
}

$id_user = $config["id_user"];

// Database Creation
// ==================
if (isset($_GET["create2"])){ // Create group
	$name = get_parameter ("name","");
	$parent = get_parameter ("category",0);
	$icon = get_parameter ("icon","");
	$description = get_parameter ("description","");
	$sql_insert="INSERT INTO tkb_category (name, description, parent, icon) 
		  		 VALUE ('$name','$description', '$parent', '$icon') ";
	$result=mysql_query($sql_insert);	
	if (! $result)
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		$id_cat = mysql_insert_id();
		//insert_event ("CATEGORY CREATED", $id_cat, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Created category $id_cat - $name");
	}
	
}


// Database UPDATE
// ==================
if (isset($_GET["update2"])){ // if modified any parameter
	$id = get_parameter ("id","");
	$name = get_parameter ("name","");
	$parent = get_parameter ("category",0);
	$icon = get_parameter ("icon","");
	$description = get_parameter ("description","");
	$sql_update ="UPDATE tkb_category
	SET name = '$name', icon = '$icon', description = '$description', parent = '$parent' 
	WHERE id = $id";
	$result=mysql_query($sql_update);
	if (! $result)
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);  
	else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		//insert_event ("CATEGORY UPDATED", $id, 0, $name);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Updated category $id - $name");
	}
}


// Database DELETE
// ==================
if (isset($_GET["delete_cat"])){ // if delete
	$id = get_parameter ("delete_cat",0);
	// First delete from tagente_modulo
	$sql_delete= "DELETE FROM tkb_category WHERE id = $id";

	// Move parent who has this product to 0
	mysql_query("UPDATE tkb_category SET parent = 0 WHERE parent = $id");		
	$result = mysql_query($sql_delete);
	if ($result)
		echo ui_print_error_message (__('Successfully deleted'), '', true, 'h3', true); 
	else
		echo ui_print_success_message (__('Cannot be deteled'), '', true, 'h3', true);
}



// CREATE form
if ((isset($_GET["create"]) OR (isset($_GET["update"])))) {
	if (isset($_GET["create"])){
		$icon = "";
		$description = "";
		$name = "";
		$id = -1;
		$parent = -1;
	} else {
		$id = get_parameter ("update",-1);
		$row = get_db_row ("tkb_category", "id", $id);
		$description = $row["description"];
		$name = $row["name"];
		$icon = $row["icon"];
		$parent = $row["parent"];
	}
	
	echo "<h2>".__('Category management')."</h2>";
	if ($id == -1){
		echo "<h4>".__('Create a new category')."</h4>";
		echo "<form id='form-kb_category' name=catman method='post' action='index.php?sec=kb&
						sec2=operation/kb/manage_cat&create2'>";
	}
	else {
		echo "<h4>".__('Update existing category')."</h4>";
		echo "<form id='form-kb_category' name=catman method='post' action='index.php?sec=kb&
						sec2=operation/kb/manage_cat&update2'>";
		echo "<input id='id_kb_category' type=hidden name=id value='$id'>";
	}
	
	echo '<table width="100%" class="search-table-button">';
	echo "<tr>";
	echo "<td class=datos>";
	echo "<b>" . __('Name') . "</b>";
	echo "<td class=datos>";
	echo "<input id='text-name' type=text size=20 name=name value='$name'>";

	echo "<tr>";
	echo "<td class=datos2>";
	echo "<b>" . __('Description') . "</b>";
	echo "<td class=datos2>";
	echo "<input type=text size=50 name=description value='$description'>";

	echo "<tr>";
	echo "<td class=datos>";
	echo "<b>" . __('Icon') . "</b>";
	echo "<td class=datos>";
	$files = list_files ('images/groups_small/', "png", 1, 0);
		print_select ($files, 'icon', $icon, '', __('None'), "");
		
	if ($icon)
		$img = "<img src='images/groups_small/$icon' />";
	else
		$img = '';
	echo "<span id='preview_image'>$img</span>";
	echo "<tr>";
	echo "<td class=datos2>";
	echo "<b>" . __('Parent') . "</b>";
	echo "<td class=datos2>";
	combo_kb_categories ( $parent, true);
	echo "</table>";
	if ($id == -1)
		echo "<div class='button-form'>" . print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true) . "</div>";
	else
		echo "<div class='button-form'>" . print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true) . "</div>";
	echo "</form>";

}

// Show list of categories
// =======================
if ((!isset($_GET["update"])) AND (!isset($_GET["create"]))){
	echo "<h2>".__('KB Category management')." </h2><h4>".__('Defined categories')."</h4>";
	$sql1='SELECT * FROM tkb_category ORDER BY parent, name';
	$color =0;
	
	echo '<div class="divform"><table class="search-table"><tr><td>';
	echo "<form method=post action='index.php?sec=kb&sec2=operation/kb/manage_cat&create=1'>";
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
	echo "</form></table></div>";
	
	echo '<div class="divresult">';
	if ($result=mysql_query($sql1)){
		echo '<table width="100%" class="listing">';
		echo "<th>".__('Icon')."</th>";
		echo "<th>".__('Name')."</th>";
		echo "<th>".__('Parent')."</th>";
		echo "<th>".__('Description')."</th>";
		echo "<th>".__('Items')."</th>";
		echo "<th>".__('Delete')."</th>";
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			// Icon
			echo "<td class='$tdcolor'>";
			echo "<img src='images/groups_small/".$row["icon"]."' border='0'>";
			echo "</td>";
			// Name
			echo "<td class='$tdcolor'><b><a href='index.php?sec=kb&sec2=operation/kb/manage_cat&update=".$row["id"]."'>".$row["name"]."</a></b></td>";
			// Parent
			echo "<td class='".$tdcolor."f9'>".get_db_sql ("SELECT name FROM tkb_category WHERE id = ".$row["parent"]);

			// Descripcion
			echo "<td class='".$tdcolor."f9'>";
			echo substr($row["description"],0,55);
			if (strlen($row["description"])> 55)
				echo "...";


			// Items
			echo "<td class='".$tdcolor."f9'>";
			echo get_db_sql ("SELECT COUNT(id) FROM tkb_data WHERE id_category = ".$row["id"]);

			// Delete
			echo "<td class='".$tdcolor."f9'>";
			echo "<a href='index.php?sec=kb&
						sec2=operation/kb/manage_cat&
						delete_cat=".$row["id"]."' 
						onClick='if (!confirm(\' ".__('Are you sure?')."\')) 
						return false;'>
						<img border='0' src='images/cross.png'></a>";
		}
		echo "</table>";
	}
	echo "</div>";
} // end of list

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script  type="text/javascript">

// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-kb_category");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_kb_category: 1,
			kb_category_name: function() { return $('#text-name').val() },
			kb_category_id: function() { return $('#id_kb_category').val() }
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This category already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

$('#icon').change(function(){
	var img = $(this).val();
	$('#preview_image').html('<img src="images/groups_small/'+img+'" />');
});
</script>
