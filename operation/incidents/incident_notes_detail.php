<?php

// TOPI 
// ========================================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars

global $config;

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access","Trying to access ticket viewer");
	require ("general/noaccess.php");
	exit;
}

$id_nota = get_parameter ("id",0);
$id_incident = get_parameter ("id_inc",0);

// ********************************************************************
// Note detail of $id_note
// ********************************************************************

$sql4='SELECT * FROM tnota WHERE id_nota = '.$id_nota;
$res4=mysql_query($sql4);
if ($row3=mysql_fetch_array($res4)){

	echo "<div class='notetitle'>"; // titulo

	$timestamp = $row3["timestamp"];
	$nota = $row3["nota"];
	$id_usuario_nota = $row3["id_usuario"];

	$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_usuario_nota);

	// Show data
	echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>&nbsp;";
	echo " <a href='index.php?sec=users&sec2=operation/users/user_edit&id=$id_usuario_nota'>";
	echo $id_usuario_nota;
	echo "</a>";
	echo " ".__("said on $timestamp");
	echo "</div>";

	// Body
	echo "<div class='notebody'>";
	echo clean_output_breaks($nota);
	echo "</div>";
} else 
	echo __('No data available');

?>
