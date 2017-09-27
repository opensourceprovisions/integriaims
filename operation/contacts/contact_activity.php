<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$id_company = get_db_value ('id_company', 'tcompany_contact', 'id', $id);
if ($id_company) {
	$read = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
	if (!$read) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to contact export");
		include ("general/noaccess.php");
		exit;
	}
}

// Activities
$op2 = get_parameter ("op2", "");

if ($op2 == "add"){
	$datetime =  date ("Y-m-d H:i:s");
	
	$comments = get_parameter ("comments", "");
	$sql = sprintf ('INSERT INTO tcontact_activity (id_contact, written_by, creation, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, $comments);
	process_sql ($sql, 'insert_id');
}

if ($op2 == "purge"){
	$activity_id = get_parameter ("activity_id");

	if ($id == 0)
		return;

	// TODO: Implement ACL
	$sql = sprintf ('DELETE FROM tcontact_activity WHERE id_contact = %d and id = %d', $id, $activity_id);
	process_sql ($sql);
}

// Add item form
$table = new stdClass();
$table->width = "100%";
$table->class = "search-table-button";
$table->data = array ();
$table->size = array ();
$table->style = array ();

$table->data[0][0] = "<h3>".__("Add activity")."</h3>";
$table->data[1][0] = "<textarea name='comments' style='width:98%; height: 210px'></textarea>";

echo '<form method="post" action="index.php?sec=customers&sec2=operation/contacts/contact_detail&op=activity&id='.$id.'&op2=add">';
print_table($table);
	echo "<div class='no' style='width:100%; text-align:right;'>";
		unset($table->data);
		$table->class = "button-form";
		$table->data[2][0] = print_submit_button (__('Add activity'), "create_btn", false, 'class="sub next"', true);
		print_table($table);
	echo '</div>';
echo '</form>';

$sql = "SELECT * FROM tcontact_activity WHERE id_contact = $id ORDER BY creation DESC";

$activities = get_db_all_rows_sql ($sql);
$activities = print_array_pagination ($activities, "index.php?sec=customers&sec2=operation/contacts/contact_detail&op=activity&id=$id");

if ($activities !== false) {	
	if (sizeof($activities) == 0){
		echo ui_print_error_message (__("There is no activity"), '', true, 'h3', true);
	} else {
		foreach ($activities as $activity) {
			echo "<div class='notetitle'>"; // titulo

			$timestamp = $activity["creation"];
			$nota = clean_output_breaks($activity["description"]);
			$id_usuario_nota = $activity["written_by"];

			$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_usuario_nota);

			// Show data
			echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
			echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_usuario_nota'>";
			echo $id_usuario_nota;
			echo "</a>";
			echo " ".__("said on $timestamp");

			// show delete activity only on owners
			
			if (give_acl($config["id_user"], 0, "CW")) {
				echo "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/contacts/contact_detail&op=activity&id=$id&op2=purge&activity_id=".$activity["id"]." '><img src='images/cross.png'></a>";
			}

			echo "</div>";

			// Body
			echo "<div class='notebody'>";
			echo clean_output_breaks($nota);
			echo "</div>";
		}
	}
} 
?>
