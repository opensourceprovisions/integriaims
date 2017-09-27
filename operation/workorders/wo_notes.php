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

include_once ('include/functions_projects.php');

$id = get_parameter("id");

if (! get_workorder_acl($id)) {
	no_permission();
}

$add_note = get_parameter("addnote");
$delete = get_parameter("delete");

echo '<h1>'.__('Add a note').'</h1>';

if ($add_note) {

	$note = get_parameter("note");

	$now = print_mysql_timestamp();

	$res = workorders_insert_note ($id, $config["id_user"], $note, $now);
	
	if (! $res)
		echo '<h3 class="error">'.__('There was a problem creating the note').'</h3>';
	else
		echo '<h3 class="suc">'.__('Note was added successfully').'</h3>'; 

}

if ($delete) {
	$id_note = get_parameter("id_note");

	$note = get_db_row ("ttodo_notes", "id", $id_note); 

	$sql = sprintf("DELETE FROM ttodo_notes WHERE id = %d", $id_note);

	$res = process_sql($sql);

	if (! $res)
                echo '<h3 class="error">'.__('There was a problem deleting the note').'</h3>';
        else
                echo '<h3 class="suc">'.__('Note was deleted successfully').'</h3>';
	
	mail_workorder ($id, 5, $res, false, $note);
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array ();
$table->data = array ();
$table->size = array();
$table->style = array();

$table->data[0][0] = print_textarea ('note', 10, 70, '', "style='resize:none;'", true, __('Note'));
$table->data[1][0] = print_submit_button (__('Add'), 'addnote', false, 'class="sub next"', true);

echo '<form method="post" action="index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=notes&addnote=1&id='.$id.'">';

print_table ($table);

echo "</form>";

// List of WO attachments

$sql = "SELECT * FROM ttodo_notes WHERE id_todo = $id ORDER BY `creation` DESC";
$notes = get_db_all_rows_sql ($sql);	
if ($notes !== false) {
	echo "<h3>". __('Notes of this workorder')."</h3>";

	foreach ($notes as $note) {
			echo "<div class='notetitle'>"; // titulo

			$timestamp = $note["creation"];
			$nota = clean_output_breaks($note["description"]);
			$id_usuario_nota = $note["written_by"];

			$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_usuario_nota);

			// Show data
			echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
			echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_usuario_nota'>";
			echo $id_usuario_nota;
			echo "</a>";
			echo " ".__("said on $timestamp");

			// show delete activity only on owners
			$owner = get_db_value ("assigned_user", "ttodo", "id", $id);
			if ($owner == $config["id_user"])
				echo "&nbsp;&nbsp;<a href='index.php?sec=projects&sec2=operation/workorders/wo&id=$id&operation=view&tab=notes&delete=1&id_note=".$note["id"]." '><img src='images/cross.png'></a>";
			echo "</div>";

			// Body
			echo "<div class='notebody'>";
			echo clean_output_breaks($nota);
			echo "</div>";
		}
} else {
	echo "<h3>". __('There aren\'t notes for this workorder')."</h3>";
}


?>
