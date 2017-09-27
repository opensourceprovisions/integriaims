<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

check_login ();

require_once('include/functions_user.php');

echo "<h2>" . __('IMPORT USERS'). "</h2>";
echo "<h4>" . __('FROM CSV') . integria_help ("import_from_csv", true) . "</h4>";

$upload_file = (int) get_parameter('upload_file');
$group = (int)get_parameter('group');
$profile = (int)get_parameter('perfil', 1);
$nivel = (int)get_parameter('nivel');
$pass_policy = (int)get_parameter('pass_policy');
$avatar_param = get_parameter('avatar');
//only avatar name (without extension)
$avatar_param = explode('.', $avatar_param);
$avatar = $avatar_param[0];

if ($upload_file) {
	if ($_FILES["file"]["error"] == 0) {

		if (($_FILES["file"]["type"] != 'text/csv') && ($_FILES["file"]["type"] != 'application/vnd.ms-excel')) {
			echo ui_print_error_message (__('Unsupported file type'), '', true, 'h3', true);
		}
		else {
			load_file ($_FILES["file"]["tmp_name"], $group, $profile, $nivel, $pass_policy, $avatar);
		}
	}
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table';
$table->size = array ();
$table->size[0] = '120px';
$table->align[3] = "right";
$table->data = array ();

if(!isset($id_group)){
	$id_group = '';
}
$table->data[0][0] = combo_groups_visible_for_me ($config['id_user'], 'group', 0, 'TW', $id_group, true);

$table->data[1][0] = "<label>".__('Profiles')."</label>";

$table->data[1][0] .= "<select name='perfil' class='w155' style='width: 218px;' >";
	$sql='SELECT * FROM tprofile ORDER BY name';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		$table->data[1][0] .= "<option value='".$row["id"]."'>".$row["name"];
	}
$table->data[1][0] .= '</select>';

$table->data[2][0] = "<label>".__('Global profile')."</label>";
$table->data[2][0] .= __('Standard user').'&nbsp;<input type="radio" class="chk" name="nivel" value="0" checked>';
$table->data[2][0] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
$table->data[2][0] .= __('External user').'&nbsp;<input type="radio" class="chk" name="nivel" value="-1">';

$table->data[3][1] = "<label>".__('Enable policy password')."</label>";
$table->data[3][1] .= __('Yes').'&nbsp;<input type="radio" class="chk" name="pass_policy" value="1">';
$table->data[3][1] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
$table->data[3][1] .= __('No').'&nbsp;<input type="radio" class="chk" name="pass_policy" value="0" checked>';

$ficheros = list_files('images/avatars/', "png", 1, 0, "small");
$avatar_forlist = $avatar . ".png";
$table->data[4][0] = print_select ($ficheros, "avatar", $avatar_forlist, '', '', 0, true, 0, false, __('Avatar'));

$table->data[7][0] = "<label>".__('Load file')."</label>";
$table->data[7][0] .= '<input class="sub" name="file" type="file" />';
$table->cellstyle[8][0] = "text-align:center;";
$table->data[8][0] = '<input type="submit" class="sub next" value="' . __('Upload File') . '" />';
echo "<div class='divform'>";
	echo '<form enctype="multipart/form-data" action="index.php?sec=users&sec2=godmode/usuarios/import_from_csv" method="POST">';
		print_input_hidden ('upload_file', 1);
		print_table ($table);
	echo '</form>';
echo '</div>';

?>
