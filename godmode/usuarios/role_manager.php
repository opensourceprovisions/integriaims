<?php

// Integria 1.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load globar vars
global $config;

check_login ();

if (! give_acl($config["id_user"], 0, "UM")) {
	audit_db ($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation", "Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

//  INSERTION
if (isset($_POST["create"])){ // If create
	$name = get_parameter ('name');
	$description = get_parameter ('description');
	$cost = get_parameter ('cost');
	$sql_insert="INSERT INTO trole (name,description,cost) VALUES ('$name','$description','$cost') ";
	$result=mysql_query($sql_insert);	
	if (! $result)
		echo ui_print_error_message (__('Not created. Error inserting data'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		$id = mysql_insert_id();
	}
}

// UPDATE
if (isset($_POST["update"])){ // if update
	$id = (int) get_parameter ('id');
	$name = get_parameter ('name');
	$description = get_parameter ('description');
	$cost = get_parameter ('cost');
	$sql_update = "UPDATE trole SET
					cost = '$cost', name = '".$name."',
					description = '$description'
				   WHERE id = '$id'";
	$result=mysql_query($sql_update);
	if (! $result)
		echo ui_print_error_message (__('Not updated. Error updating data'), '', true, 'h3', true);
	else
		echo ui_print_success_message (__('Succcessfully updated'), '', true, 'h3', true);
}

// DELETE
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (isset($_GET["delete"])){ // if delete
	$id = get_parameter ('delete');
	// Role 1 cannot be deleted (project manager)
	if ($id > 1) { 
		$sql_delete= "DELETE FROM trole WHERE id = ".$id;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);
		else
			echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	} else
		echo ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);

}

// EDIT ROLE
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
	if (isset($_GET["form_edit"])){
		$creation_mode = 0;
			$id = get_parameter ('id');
			$sql1='SELECT * FROM trole WHERE id = '.$id;
			$result=mysql_query($sql1);
			if ($row=mysql_fetch_array($result)){
					$name = $row["name"];
					$description = $row["description"];
					$cost = $row["cost"];
				}
			else echo ui_print_error_message (__('Name error'), '', true, 'h3', true);
	} else { // form_add
		$creation_mode =1;
		$name = "";
		$description = "No description";
		$cost = 0;
	}

	// Create link
	echo "<h2>".__('Role management')." </h2>";
	if ($creation_mode == 1)
		echo "<h4> ".__('Add role')."</h4>";
	else
		echo "<h4> ".__('Update role')."</h4>";
	
	echo "<div class='divform'>";
	echo '<form id="form-role_manager" name="ilink" method="post" action="index.php?sec=users&sec2=godmode/usuarios/role_manager">';
	echo '<table class="search-table" width="20%">';
	if ($creation_mode == 1) {
		echo "<input type='hidden' name='create' value='1'>";
	} else {
		echo "<input type='hidden' name='update' value='1'>";
		echo "<input type='hidden' name='id' value='$id'>";
	}
	
	echo '<tr><td class="datos"><label>'.__('Role').'</label><tr><td class="datos"><input id="text-role" type="text" name="name" size="25" value="'.$name.'">';
	
	echo '<tr><td class="datos2"><label>'.__('Description').'</label><tr><td class="datos2"><input type="text" name="description" size="55" value="'.$description.'">';

	echo '<tr><td class="datos"><label>'.__('Cost').'</label><tr><td class="datos"><input id="text-cost" type="text" name="cost" size="6" value="'.$cost.'">';
	
	echo "<tr><td><input name='crtbutton' type='submit' class='sub next' value='".__('Update')."'>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
}
else {
	// Role viewer
	// ~~~~~~~~~~~~~~~~~~~~~~~
	// Main list view for Links editor
	echo "<h2>".__('Role management')."</h2>";
	echo "<h4>".__('List Role')."</h4>";
	
	echo "<div class='divresult'>";
	echo "<table width='100%' class='listing'>";
	echo "<th>".__('Name');
	echo "<th>".__('Description');
	echo "<th>".__('Cost');
	echo "<th>".__('Delete');
	$sql1='SELECT * FROM trole ORDER BY id';
	$result=mysql_query($sql1);
	$color=1;
	while ($row=mysql_fetch_array($result)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><td valign='top' class='$tdcolor'><b><a href='index.php?sec=users&sec2=godmode/usuarios/role_manager&form_edit=1&id=".$row["id"]."'>".$row["name"]."</a></b>";
		echo '<td class="'.$tdcolor.'">'.$row["description"];
		echo '<td class="'.$tdcolor.'">'.$row["cost"];
		echo '<td class="'.$tdcolor.'">';
		if ($row["id"] >1){
			echo '<a href="index.php?sec=users&sec2=godmode/usuarios/role_manager&id='.$row["id"].'&delete='.$row["id"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border=0 src="images/cross.png"></a>';
		}
	}
	echo "</table>";
	echo "</div>";
	
	echo "<div class='divform'>";
	echo "<form method='post' action='index.php?sec=users&sec2=godmode/usuarios/role_manager&form_add=1'>";
	echo "<table class='search-table' width='100%'>";
	echo "<tr><td>";
	
	echo "<input type='submit' class='sub create' name='form_add' value='".__('Add')."'>";
	echo "</table></form>";
	echo "</div>";
} // Fin bloque else

?>

<script src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

// Form validation
trim_element_on_submit('#text-role');
trim_element_on_submit('#text-cost');
validate_form("#form-role_manager");
var rules, messages;
// Rules: #text-role
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_role: 1,
			role_name: function() { return $('#text-role').val() },
			role_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Role required')?>",
	remote: "<?php echo __('This role already exists')?>"
};
add_validate_form_element_rules('#text-role', rules, messages);

</script>
