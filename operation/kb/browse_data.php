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

if (! give_acl ($config["id_user"], 0, "KR")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access KB Browser");
	require ("general/noaccess.php");
	exit;
}

// Review form
if (! isset ($_GET["view"])) {
	return;
}

$edit_perm = false;
if (give_acl ($config["id_user"], 0, "KW")){
	$edit_perm = true;
}

$id = (int) get_parameter ('view');

if ($id && ! check_kb_item_accessibility($config["id_user"], $id)) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to KB forbidden item");
	require ("general/noaccess.php");
	exit;
}

$kb_data = get_db_row ("tkb_data", "id", $id);
$data = $kb_data["data"];
$title = $kb_data["title"];
$timestamp = $kb_data["timestamp"];
$id_language = $kb_data["id_language"];

$product = '';
if ($kb_data["id_product"])
	$product = get_db_value ('name', 'tkb_product', 'id', $kb_data['id_product']);

$category = '';
if ($kb_data["id_category"])
	$category = get_db_value ('name', 'tkb_category', 'id', $kb_data['id_category']);

echo '<h2>'.__('KB article review') . '</h2>';

echo "<h4>" . $title;
$report_image = print_report_image ("index.php?sec=kb&sec2=operation/kb/browse_data&view=$id", __("PDF"));
if ($report_image) {
	echo "&nbsp;&nbsp;&nbsp;";
	echo $report_image;
}

if ($edit_perm){
	echo "&nbsp;&nbsp;&nbsp;";
	echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_data&update=".$kb_data['id']."'><img border=0 title='".__('Edit')."' src='images/application_edit.png'></a>";
}


	echo "<div id='button-bar-title'>";
	echo "<ul>";
	echo '<li>';
	echo '<a href="index.php?sec=kb&sec2=operation/kb/browse">'.print_image("images/go-previous.png", true, array("title" => __("Back to list")))."</a>";
	echo '</li>';
	
echo '</h4>';

$avatar = get_db_value ('avatar', 'tusuario', 'id_usuario', $kb_data['id_user']);

//echo "<p><b></b>";

// Title header
echo "<div class='notetitle' style='height: 50px;'>"; 
echo "<table class='blank' border=0 width='100%' cellspacing=0 cellpadding=0 style='background: transparent; line-height: 12px; border: 0px; margin-left: 0px;margin-top: 0px;'>";
echo "<tr><td rowspan=3 width='7%'>";
echo "<img src='images/avatars/".$avatar.".png' class='avatar_small'>";

echo "<td width='50%'><b>";
echo __('Author')." </b> : ";
echo '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.$kb_data['id_user'].'">';
echo $kb_data['id_user'];
echo "</a>";
echo "<td> <b>";
echo __('Product')." </b> : ";
echo $product;

echo "<td><b>";
echo __('Language');
echo "</b> : ".$id_language;

echo "<tr>";
echo "<td>";

echo " ".__("Wrote on ").$timestamp;
echo "<td>";
echo "<b>";
echo __('Category')." </b> : ";
echo $category;

//~ echo "<td align=right>";
//~ if (give_acl ($config["id_user"], 0, "KW")){
	//~ echo "<a href='index.php?sec=kb&sec2=operation/kb/manage_data&update=".$kb_data['id']."'><img border=0 title='".__('Edit')."' src='images/application_edit.png'></a>";
//~ }

echo "</table>";
echo "</div>";

// Body
echo "<div class='notebody'>";
echo "<table class='blank' width='100%' cellpadding=0 cellspacing=0>";
echo "<tr><td valign='top'>";
echo clean_output_breaks ($data);
echo "</table>";
echo "</div>";


// Show list of attachments
$attachments = get_db_all_rows_field_filter ('tattachment', 'id_kb', $id, 'description');
if ($attachments !== false && $id) {
	echo '<h3>'.__('Attachment list').'</h3>';
	
	$table->width = '735';
	$table->class = 'listing';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Filename');
	$table->head[1] = __('Description');
	
	foreach ($attachments as $attachment) {
		$data = array ();
		
		$attach_id = $attachment['id_attachment'];
		$link = 'operation/common/download_file.php?type=kb&id_attachment='.$attachment['id_attachment'];
		$data[0] = '<a href="'.$link.'" title="'.$attachment['description'].'">';
		$data[0] .= '<img src="images/disk.png"/> ';
		$data[0] .= $attachment['filename'];
		$data[0] .= '</a>';
		$data[1] = $attachment['description'];
		
		array_push ($table->data, $data);
	}
	print_table ($table);
	echo "</div>";
}
?>
