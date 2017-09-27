<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2013 Ártica Soluciones Tecnológicas
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

if (! give_acl ($config["id_user"], 0, "WOM")) {
	audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access WO Category Management");
	require ("general/noaccess.php");
	exit;
}

$id_user = $config["id_user"];
$create2 = get_parameter ("create2");
$update2 = get_parameter ("update2");

// Database Creation
// ==================
if ($create2){ // Create category
	$name = get_parameter ("name","");
	$icon = get_parameter ("icon","");

	$sql_insert="INSERT INTO two_category (name, icon) 
		  		 VALUE ('$name', '$icon')";

	$result=mysql_query($sql_insert);	
	if (! $result)
		echo "<h3 class='error'>".__('Could not be created')."</h3>"; 
	else {
		echo "<h3 class='suc'>".__('Successfully created')."</h3>";
		$id_cat = mysql_insert_id();
	}
	
}

// Database UPDATE
// ==================
if ($update2){ // if modified any parameter
	$id = get_parameter ("id","");
	$name = get_parameter ("name","");
	$icon = get_parameter ("icon", "");

	$sql_update ="UPDATE two_category
	SET name = '$name', icon = '$icon' 
	WHERE id = $id";
	$result=mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('Could not be updated')."</h3>"; 
	else {
		echo "<h3 class='suc'>".__('Successfully updated')."</h3>";
	}
}


// Database DELETE
// ==================
if (isset($_GET["delete_cat"])){ // if delete
	$id = get_parameter ("delete_cat",0);
	// First delete from tagente_modulo
	$sql_delete= "DELETE FROM two_category WHERE id = $id";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Successfully deleted')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Cannot be deteled')."</h3>";
}

// CREATE form
if ((isset($_GET["create"]) OR (isset($_GET["update"])))) {
	if (isset($_GET["create"])){
		$id_group = 0;
		$name = "";
		$id = -1;
	} else {
		$id = get_parameter ("update",-1);
		$row = get_db_row ("two_category", "id", $id);
		$name = $row["name"];
		$icon = $row["icon"];
	}

	if ($id == -1){
		echo "<h1>".__('Create a new category')."</a></h1>";
		echo "<form id='categoryform' name=catman method='post'>";
		echo "<input type=hidden name='create2' value=1>";
	}
	else {
		echo "<h1>".__('Update existing category')."</a></h1>";
		echo "<form id='categoryform' name=catman method='post'>";
		echo "<input type=hidden name=id value='$id'>";
		echo "<input type=hidden name='update2' value=1>";
	}
	
	echo '<table width="100%" class="search-table-button">';
	echo "<tr>";
	echo "<td class=datos>";
	echo "<label>" . __('Name') . "</label>";
	echo "<input type=text size=20 name=name value=".$name.">";

    echo "<td class=datos>";
	echo "<label>" . __('Icon') . "</label>";
	$files = list_files ('images/wo_category/', "png", 1, 0);
	print_select ($files, 'icon', $icon, '', __('None'), "");
	echo "<td class=datos>";
	if ($id == -1)
		print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create" style="margin-top: 20px;"');
	else
		print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd" style="margin-top: 20px;"');
	echo "</table>";
	echo "</form>";

}

// Show list of categories
// =======================
if ((!isset($_GET["update"])) AND (!isset($_GET["create"]))){
	echo "<h1>".__('Workorder category management')." &raquo; ".__('Defined categories')."</h1>";
	$sql1='SELECT * FROM two_category ORDER BY name';
	$color =0;
	if ($result=mysql_query($sql1)){
		echo '<table width="100%" class="listing">';
		echo "<th>".__('Name')."</th>";
		echo "<th>".__('Icon')."</th>";
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
			// Name
			echo "<td class='$tdcolor' valign='top'><b><a href='index.php?sec=projects&sec2=operation/workorders/wo_category&update=".$row["id"]."'>".$row["name"]."</a></b></td>";

			// Icon
			echo "<td class='".$tdcolor."f9' valign=top >";
			echo "<img src='images/wo_category/".$row["icon"]."'>";

			// Delete
			echo "<td class='".$tdcolor."f9' align='center' valign='top'>";
			echo "<a href='index.php?sec=projects&sec2=operation/workorders/wo_category&
						delete_cat=".$row["id"]."' 
						onClick='if (!confirm(\' ".__('Are you sure?')."\')) 
						return false;'>
						<img border='0' src='images/cross.png'></a>";
		}
		echo "</table>";
	}			
	echo '<div style="width: 100%;" class="button">';
	echo "<form method=post action='index.php?sec=projects&sec2=operation/workorders/wo_category&create=1'>";
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"');
	echo "</form></div>";
} // end of list

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" >
// Form validation
trim_element_on_submit('input[name="name"]');
validate_form("#categoryform");
// Rules: input[name="name"]
var rules, messages;
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
          page: "include/ajax/remote_validations",
          search_name_category: 1,
          category_name: function() { return $('input[name="name"]').val() },
          category_id: <?php echo $id; ?>
        }
	}
};
messages = {
	required: "<?php echo __('Name required'); ?>",
	remote: "<?php echo __('This Name Category already exists'); ?>"
};
add_validate_form_element_rules('input[name="name"]', rules, messages);
</script>
