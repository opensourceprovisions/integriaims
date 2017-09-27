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

// Load global vars
global $config;

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access", "Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

include_once ("include/functions_projects.php");


$id_user = $config["id_user"];
$operation = get_parameter ("operation");
$id_project = get_parameter ("id_project", -1);
$id_milestone = get_parameter ("id_milestone", 0);

$project_access = get_project_access ($id_user, $id_project);

if ($id_project != 1)
	$project_name = get_db_value ("name", "tproject", "id", $id_project);
else
	$project_name = "";

if ($id_project == -1){
	// Doesn't have access to this page
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to milestone manager without project");
	no_permission();
}
// ACL - To see the project milestones, you should have read access
if (! $project_access["read"]) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to access to milestone manager without permissions");
	no_permission();
}
// ACL - To manage a project milestone, you should have PM access
if ($operation != "" && !$project_access["write"]) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation","Trying to create or delete a milestone without permissions");
	no_permission();
}

// ---------------
// CREATE new milestone
// ---------------
if ($operation == "create2") {
	$name = get_parameter ("name");
	$description = get_parameter ("description");
	$timestamp = get_parameter ("timestamp");
	$timestamp = safe_output($timestamp);
	$id_project = get_parameter ("id_project");
	$sql_insert="INSERT INTO tmilestone (name, description, timestamp, id_project) VALUES ('$name','$description', '$timestamp', '$id_project') ";
	$result=mysql_query($sql_insert);
	if (! $result)
		echo ui_print_error_message (__('Not created. Error inserting data'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		$id_ms = mysql_insert_id();
	}
	
	$operation = "";
}
// ---------------
// UPDATE milestone
// ---------------
if ($operation == "update2") {
	$name = get_parameter ("name");
	$description = get_parameter ("description");
	$timestamp = get_parameter ("timestamp");
	$id_project = get_parameter ("id_project");
	$values = array("name" => $name, "description" => $description, "timestamp" => safe_output($timestamp), "id_project" => $id_project);
	
	$result = process_sql_update("tmilestone",$values, "id = $id_milestone");
	if (! $result)
		echo ui_print_error_message (__('Error to update or nothing to update'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully update'), '', true, 'h3', true); 
	}
	
	$operation = "";
}

// ---------------
// DELETE new todo
// ---------------
if ($operation == "delete") {
	$id_milestone = get_parameter ("id");
	$sql_delete= "DELETE FROM tmilestone WHERE id = $id_milestone";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);
	else
		echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	$operation = "";
}


// ---------------
// CREATE new todo (form)
// ---------------
if ($operation == "create" || $operation == "update") {
	
	$section_title = __('Milestone');
	$section_subtitle = '';
	if ($id_milestone) {
		$section_subtitle = __('Update');
		$milestone = get_db_row ("tmilestone", "id", $id_milestone);
		$name = $milestone["name"];
		$description = $milestone["description"];
		$timestamp = $milestone["timestamp"];
		$id_project = $milestone["id_project"];
	}
	else {
		$section_subtitle = __('Creation');
		$name = '';
		$description = '';
		$timestamp = date("Y-m-d H:i:s");
		//$id_project = '';
	}

	$p_menu = print_project_tabs();
	print_title_with_menu ($section_title, $section_subtitle, "milestones", 'projects', $p_menu, 'milestones');
	
	echo '<table class="search-table-button"  width="100%">';
	if ($id_milestone)
		echo '<form name="ilink" method="post" action="index.php?sec=projects&sec2=operation/projects/milestones&id_project='.$id_project.'&id_milestone='.$id_milestone.'&operation=update2">';
	else
		echo '<form name="ilink" method="post" action="index.php?sec=projects&sec2=operation/projects/milestones&id_project='.$id_project.'&operation=create2">';

	echo "<tr><td class='datos'><b>".__('Name') . "</b>";
	echo "<td class='datos'><input name='name' size=40 value='$name'>";
	
	echo "<tr><td class='datos2'><b>".__('Timestamp') . "</b>";
	echo "<td class='datos2'>";
	
	echo "<input type='text' id='timestamp' name='timestamp' size=15 value='$timestamp'>";

	echo "<tr><td class='datos' valign='top'><b>".__('Description') . "</b>";
	echo "<td class='datos'><textarea name='description' style='width:95%; height:100px'>";
	echo $description . "</textarea>";
	echo "</table>";
	
	$project_manager = get_db_value ("id_owner", "tproject", "id", $id_project);
    // milestone creation
    if ((give_acl($config["id_user"], 0, "PM")==1) OR ($project_manager == $config["id_user"])) {
	    echo "<div  class='button-form' >";

	    echo "<input type=hidden name='id_project' value='$id_project'>";
	    if ($id_milestone)
			echo "<input name='crtbutton' type='submit' class='sub upd' value='" . __('Update') . "'>";
		else
			echo "<input name='crtbutton' type='submit' class='sub create' value='" . __('Create') . "'>";
			
		echo "</div>";
    }
    
	
	echo '</form>';
}

// -------------------------
// Milestone view
// -------------------------
if ($operation == ""){
	
	$section_title = __('Milestones');
	$section_subtitle = __('Management');
	$p_menu = print_project_tabs();
	print_title_with_menu ($section_title, $section_subtitle, "milestones", 'projects', $p_menu, 'milestones');
	
	echo "<div class='divresult' >";
	echo "<table class='listing' width=100%>";
	echo "<th>".__('Milestone');
	echo "<th>".__('Description');
	echo "<th>".__('Timestamp');
	if ($project_access['write']) {
		echo "<th>".__("OP");
	}
	$color=1;
	if(!isset($tdcolor)){
		$tdcolor ="";
	}
	$sql1="SELECT * FROM tmilestone WHERE id_project = $id_project";
	if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			
			echo "<tr><td class='$tdcolor'>";
			echo $row["name"];
			
			echo "<td class='".$tdcolor."f9'>";
			echo $row["description"];
			
			echo "<td class='".$tdcolor."f9'>";
			echo $row["timestamp"];
			
			// DELETE
			if ($project_access['write']) {
				echo '<td class="'.$tdcolor.'">';
				echo '<a href="index.php?sec=projects&sec2=operation/projects/milestones&id_project='.$id_project.'&operation=update&id_milestone='.$row["id"].'"><img border=0 src="images/editor.png"></a>';
				$offset=0;
				$search_params='';
				echo "<a href='#' onClick='javascript: show_validation_delete_general(\"delete_milestones\",".$id_project.",".$row["id"].",".$offset.",\"".$search_params."\");'><img src='images/cross.png' title='".__('Delete')."'></a>";
			}
			
		}
	echo "</table>";
	echo "</div>";

    $project_manager = get_db_value ("id_owner", "tproject", "id", $id_project);
    // milestone creation
    if ($project_access['write']) {
		echo "<div class='divform' >";
	    echo "<table class='search-table' width=100%>";
	    echo "<tr><td align=right>";
    
	    echo "<form name='ms' method='POST'  action='index.php?sec=projects&sec2=operation/projects/milestones&operation=create&id_project=$id_project'>";
	    echo "<input type='submit' class='sub create' name='crt' value='".__('Create')."'>";
	    echo "</form>";
	    echo "</table>";
	    echo "</div>";
    }
} // Fin bloque else
echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";

?>


<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>
<script type="text/javascript" src="include/js/jquery-ui-timepicker-addon.js"></script>
<link rel="stylesheet" type="text/css" href="include/js/jquery-ui-timepicker-addon.css"/ >
<script type="text/javascript">

// Form validation
trim_element_on_submit('input[name="name"]');

add_complete_datepicker("#timestamp");

function add_complete_datepicker (element_picker, startDate_picker) {
	$(document).ready(function () {
		$(element_picker).datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "HH:mm:ss",
			minDate: startDate_picker
		});
	});
}

</script>
