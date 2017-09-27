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

if (give_acl($config["id_user"], 0, "KR")==0) {
    audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access KB Browser");
    require ("general/noaccess.php");
    exit;
}


// Database Creation
// ==================
if (isset($_GET["create2"])){ // Create group

	if (give_acl($config["id_user"], 0, "KW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to write a new KB without privileges");
	    require ("general/noaccess.php");
    	exit;
    }
    
	$timestamp = date('Y-m-d H:i:s');
	$title = get_parameter ("title","Untitled article");
	$data = get_parameter ("data","");
	$id_product = get_parameter ("product","");
	$id_category = get_parameter ("category","");
	$id_language = get_parameter ("id_language", "");

	$sql_insert="INSERT INTO tkb_data (title, data, id_product, id_category, id_user, timestamp, id_language) 
		  		 VALUE ('$title','$data', '$id_product', '$id_category', '".$config["id_user"]."', '$timestamp', '$id_language') ";
	$result=mysql_query($sql_insert);	
	if (! $result)
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		$id_data = mysql_insert_id();
		//insert_event ("KB ITEM CREATED", $id_data, 0, $title);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Created kb item $id_data - $title");
	}
}

// Attach DELETE
// ==============
if (isset($_GET["delete_attach"])){

	if (give_acl($config["id_user"], 0, "KW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to delete an attach on a KB without privileges");
	    require ("general/noaccess.php");
    	exit;
    }

	$id_attachment = get_parameter ("delete_attach", 0);
	$id_kb = get_parameter ("update", 0);
	$attach_row = get_db_row ("tattachment", "id_attachment", $id_attachment);
	$nombre_archivo = $config["homedir"]."attachment/".$id_attachment."_".$attach_row["filename"];	
	$sql = " DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
	mysql_query($sql);
	unlink ($nombre_archivo);
	//insert_event ("KB ITEM UPDATED", $id_kb, 0, "File ".$attach_row["filename"]." deleted");
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Deleted kb item $id_kb - ".$attach_row["filename"]);
	echo ui_print_success_message (__('Attach deleted ok'), '', true, 'h3', true);
	unset ($id_kb);
}

// Database UPDATE
// ==================
if (isset($_GET["update2"])){ // if modified any parameter

	if (give_acl($config["id_user"], 0, "KW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to update an article on KB without privileges");
	    require ("general/noaccess.php");
    	exit;
    }

	$id = get_parameter ("id","");
	$timestamp = date('Y-m-d H:i:s');
	$title = get_parameter ("title","");
	$data = get_parameter ("data",0);
	$id_product = get_parameter ("product","");
	$id_category = get_parameter ("category","");
	$id_user = $config["id_user"];
	$id_language = get_parameter ("id_language", "");

	$sql_update ="UPDATE tkb_data
	SET title = '$title', data = '$data', id_language = '$id_language', timestamp = '$timestamp', id_user = '$id_user',
	id_category = $id_category, id_product = $id_product 
	WHERE id = $id";
	$result=mysql_query($sql_update);
	if (! $result)
		echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true); 
	else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		//insert_event ("KB ITEM UPDATED", $id, 0, $title);
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Updated kb item $id - $title");
	}

	if ( $_FILES['userfile']['name'] != "" ){ //if file
		$tipo = $_FILES['userfile']['type'];
		// Insert into database
		$filename = $_FILES['userfile']['name'];
		$filesize = $_FILES['userfile']['size'];

		$attach_description = get_parameter ("attach_description");

		$sql = "INSERT INTO tattachment (id_kb, id_usuario, filename, description, size ) VALUES (".$id.", '".$config["id_user"]. "','".$filename."','$attach_description', $filesize )";

		mysql_query($sql);
		$id_attachment=mysql_insert_id();
		echo ui_print_success_message (__('File added'), '', true, 'h3', true);
		// Copy file to directory and change name
		$nombre_archivo = $config["homedir"]."attachment/".$id_attachment."_".$filename;

		if (!(copy($_FILES['userfile']['tmp_name'], $nombre_archivo ))){
			$result_msg = ui_print_error_message (__('File cannot be saved. Please contact Integria administrator about this error'), '', true, 'h3', true); 
			$sql = " DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
			mysql_query($sql);
			unlink ($_FILES['userfile']['tmp_name']);
		} else {
			// Delete temporal file
			//insert_event ("KB ITEM UPDATED", $id, 0, "File $filename added");
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Created kb item $id - $filename");
		}
		echo $result_msg;

	}	
}

// Database DELETE
// ==================
if (isset($_GET["delete_data"])){ // if delete

	if (give_acl($config["id_user"], 0, "KW") != 1){
		audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to delete a KB without privileges");
	    require ("general/noaccess.php");
    	exit;
    }

	$id = get_parameter ("delete_data",0);
	$kb_title = get_db_sql ("SELECT title FROM tkb_data WHERE id = $id ");

	$sql_delete= "DELETE FROM tkb_data WHERE id = $id";		
	$result=mysql_query($sql_delete);
	
	if ($result=mysql_query("SELECT * FROM tattachment WHERE id_kb = $id")) {
		while ($row=mysql_fetch_array($result)){
				$nombre_archivo = $config["homedir"]."attachment/".$row["id_attachment"]."_".$row["filename"];	
				unlink ($nombre_archivo);
		}
		$sql = " DELETE FROM tattachment WHERE id_kb = ".$id;
		mysql_query($sql);
	}
	//insert_event ("KB ITEM DELETED", $id, 0, "Deleted KB $kb_title");
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "KB", "Deleted kb item $id - $kb_title");
	echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
}

if (isset($_GET["update2"])){
	$_GET["update"]= $id;
}


if ((isset($_GET["create"]) OR (isset($_GET["update"])))) {
	if (isset($_GET["create"])) {
		
		// CREATE form
		if  (!give_acl($config["id_user"], 0, "KW")) {
			return;
		}
		
	
		$data = "";
		$title = "";
		$id = -1;
		$id_product = 1;
		$id_category = 1;	
		$id_language = '';
		$id_incident = (int) get_parameter ("id_incident", 0);
		if ($id_incident) {
			// Get the product id of the first inventory object associated to the incident
			$id_product_db = get_db_sql ('SELECT id_product FROM tinventory WHERE id = (SELECT id_inventory FROM tincident_inventory WHERE id_incident = ' . $id_incident . ' LIMIT 1)');
			if ($id_product_db !== false) {
				$id_product = $id_product_db;
			}
			// Get incident data
			$incident = get_db_row_sql ('SELECT titulo, descripcion, epilog FROM tincidencia WHERE id_incidencia = ' . $id_incident);
			if ($incident !== false) {
				$title = $incident['titulo'];
				$data = $incident['descripcion'] . "\n\n" .$incident['epilog'];
			}
		}
	} else {
		$id = get_parameter ("update",-1);
		$row = get_db_row ("tkb_data", "id", $id);
		$data = $row["data"];
		$title = $row["title"];
		$id_product = $row["id_product"];
		$id_language = $row["id_language"];
		$id_category = $row["id_category"];
	}
	echo "<h2>".__('KB item management')."</h2>";
	if ($id == -1){
		echo "<h4>".__('Create a new KB item')."</h4>";
		echo "<form id='form-kb_item' name=prodman method='post' action='index.php?sec=kb&sec2=operation/kb/browse&create2'>";
	}
	else {
		echo "<h4>".__('Update existing KB item')."</h4>";
		echo "<form id='form-kb_item' enctype='multipart/form-data' name=prodman2 method='post' action='index.php?sec=kb&sec2=operation/kb/browse&update2'>";
		echo "<input type=hidden name=id value='$id'>";
	}
	
	echo '<table width="100%" class="search-table-button">';
	echo "<tr>";
	echo "<td class=datos>";
	echo "<b>" . __('Title') . "</b>";
	echo "<td class=datos>";
	echo "<input id='text-name' type=text size=60 name='title' value='$title'>";

	echo "<tr>";
	echo "<td>";
	echo "<b>" . __('Language') . "</b>";
	echo "<td>";
	echo print_select_from_sql ('SELECT id_language, name FROM tlanguage', 'id_language',
					$id_language, '', __("Any"), '', true, false, false, '');

	echo "<tr>";
	echo "<td class=datos2 valign=top>";
	echo "<b>" . __('Data') . "</b>";
	echo "<td class=datos2>";
	print_textarea ("data", 15, 40, $data, '', false,false);

	echo "<tr>";
	echo "<td class=datos2>";
	echo "<b>" . __('Product') . "</b>";
	echo "<td class=datos2>";
	combo_product_types($product, 0);

	echo "<tr>";
	echo "<td class=datos>";
	echo "<b>" . __('Category') . "</b>";
	echo "<td class=datos>";
	combo_kb_categories ($id_category);

	if ($id != -1){
		echo "<tr>";
		echo "<td class=datos>";
		echo "<b>" . __('Attach') . "</b>";
		echo "<td class=datos>";
		if ($id == -1)
			echo "<i>".__('Need to create first')."</i>";
		else {
			echo "<input type=file size=60 value='userfile' name='userfile'>";
			echo "<tr>";
			echo "<td class=datos>";
			echo "<b>" . __('Attach description') . "</b>";
			echo "<td class=datos>";
			echo "<input type=text size=60 name='attach_description' value=''>";
		}
	}
	

	echo "</table>";
	
	if ($id == -1)
		echo "<div class='button-form'>" . print_submit_button (__('Create'), 'crt_btn', false, 'class="sub create"', true) . "</div>";
	else
		echo "<div class='button-form'>" . print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true) . "</div>";

	echo "</form>";

	// Show list of attachments
	$sql1 = "SELECT * FROM tattachment WHERE id_kb = $id ORDER BY description";
	$result = mysql_query($sql1);
	if (mysql_num_rows($result) > 0) {
		echo "<h3>".__('Attachment list')."</h3>";
		echo '<table class="databox" width="90%">';
	 	while ($row=mysql_fetch_array($result)){
			echo "<tr>";
			echo "<td>";
			echo "<img src='images/disk.png'>&nbsp;";
			$attach_id = $row["id_attachment"];
			echo '<a href="attachment/'.$row["id_attachment"].'_'.rawurlencode ($row["filename"]).'">';
			echo $row["filename"];
			echo "</a>";
			echo "<td>";
			echo $row["description"];
			echo "<td>";
			
			if (give_acl($config["id_user"], 0, "KW") == 1){			
				echo "<a href='index.php?sec=kb&sec2=operation/kb/browse&update=$id&delete_attach=$attach_id'><img border=0 src='images/cross.png'></A>";
			}
		}
		echo "</table>";
	}
}

if ((!isset($_GET["update"])) AND (!isset($_GET["create"]))){


	// Show list of items
	// =======================

	echo "<h2>" . __('KB Data management') . "</h2><h4>" . __('Defined data') . "</h4>";
	
	// Search parameter 
	$free_text = get_parameter ("free_text", "");
	$product = get_parameter ("product", 0);
	$category = get_parameter ("category", 0);
	$id_language = get_parameter ("id_language", '');

	// Search filters
	echo "<div class='divform'>";
	echo '<form method="post" action="?sec=kb&sec2=operation/kb/browse">';
	echo '<table width="100%" class="search-table">';
	echo "<tr>";
	echo "<td>";
	echo "<label>" . __('Product types') . "</label>";
	combo_product_types($product, 1);
	echo "<tr>";
	echo "<td>";
	echo "<label>" . __('Categories') . "</label>";
	combo_kb_categories ($category, 1);

	echo "<tr>";
	echo "<td>";
	echo "<label>" . __('Search') . "</label>";
	echo "<input type=text name='free_text' size=25 value='$free_text'>";
	echo "<tr>";
	echo "<td>";
	echo "<label>" . __('Language') . "</label>";
	echo print_select_from_sql ('SELECT id_language, name FROM tlanguage', 'id_language',
						$id_language, '', __("Any"), '', true, false, false, '');
	echo "<tr>";
	echo "<td >";
	echo "<input type=submit class='sub search' value='".__('Search')."'>";

	echo "</td></tr></table></form></div>";

	// Search filter processing
	// ========================

	$sql_filter = "";

	if ($free_text != "")
		$sql_filter .= " AND (title LIKE '%$free_text%' OR data LIKE '%$free_text%')";

	if ($product != 0)
		$sql_filter .= " AND id_product = $product ";

	if ($category != 0)
		$sql_filter .= " AND id_category = $category ";

	if ($id_language != '')
		$sql_filter .= " AND id_language = '$id_language' ";
	
	$offset = get_parameter ("offset", 0);
	
	$condition = get_filter_by_kb_product_accessibility();
	
	$count = get_db_sql("SELECT COUNT(id) FROM tkb_data $condition $sql_filter");
	
	$sql1 = "SELECT * FROM tkb_data $condition $sql_filter ORDER BY title, id_category, id_product LIMIT $offset, ". $config["block_size"];
	$result = process_sql($sql1);

	$color = 0;
	echo "<div class='divresult'>";
	
	$result=db_process_sql($sql1);
	if ($result){
		pagination ($count, "index.php?sec=kb&sec2=operation/kb/browse&id_language=$id_language&free_text=$free_text&product=$product&category=$category", $offset);
		
		echo '<table width="100%" class="listing">';

		echo "<th>".__('Title')."</th>";
		echo "<th>".__('Category')."</th>";
		echo "<th>".__('Product')."</th>";
		echo "<th>".__('Language')."</th>";
		echo "<th>".__('Timestamp')."</th>";
		
		if (give_acl($config["id_user"], 0, "KW")) {
			echo "<th>".__('Action')."</th>";
		}
		
		foreach ($result as $key=>$row) {
			echo "<tr>";
			// Name
			echo "<td valign='top'><a href='index.php?sec=kb&sec2=operation/kb/browse_data&view=".$row["id"]."'>".short_string($row["title"],220)."</a></td>";

			// Category
			echo "<td class=f9>";
			$category_name = get_db_sql ("SELECT name FROM tkb_category  WHERE id = ".$row["id_category"]);
			$category_img = get_db_sql ("SELECT icon FROM tkb_category WHERE id = ".$row["id_category"]);
			if (!$category_img) {
				echo "";
			}
			else {
				echo "<img title='$category_name' src='images/groups_small/". $category_img . "'>";
			}
			// Product
			echo "<td class=f9>";
			$product_name = get_db_sql ("SELECT name FROM tkb_product WHERE id = ".$row["id_product"]);
			$product_img = get_db_sql ("SELECT icon FROM tkb_product WHERE id = ".$row["id_product"]);
			if (!$product_img) {
				echo "";
			}
			else {
				echo "<img title='$product_name' src='images/products/". $product_img . "'>";
			}
			// Language
			echo "<td class=f9>";
			echo $row["id_language"];

			// Timestamp
			echo "<td class='f9' valign='top'>";
			echo human_time_comparation($row["timestamp"]);
			
			if (give_acl($config["id_user"], 0, "KW")) {
				// Delete
				echo "<td class='f9' align='center' >";
				echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_data&update=".$row['id']."'><img border=0 title='".__('Edit')."' src='images/application_edit.png'></a>";
				echo "&nbsp;&nbsp;";
				echo '<a href="index.php?sec=kb&sec2=operation/kb/browse&delete_data='.$row["id"].'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"><img src="images/cross.png"></a>';
			}	

		}
		echo "</table>";
	} else {
		$downloads = array();
		echo ui_print_error_message (__('No items found'), '', true, 'h3', true); 
	}
	echo "</div>";
}

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

// Form validation
trim_element_on_submit('input[name="free_text"]');
trim_element_on_submit('#text-name');
validate_form("#form-kb_item");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_kb_item: 1,
			kb_item_name: function() { return $('#text-name').val() }
        }
	}
};
messages = {
	required: "<?php echo __('Title required')?>",
	remote: "<?php echo __('This title already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
