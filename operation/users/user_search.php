<?php

// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

check_login ();

$id_profile = (int) get_parameter ('user_profile_search');
$id_group = (int) get_parameter ('user_group_search');
$search_string = (string) get_parameter ('search_string');
$search = (bool) get_parameter ('search');

if ($search) {
	$users = get_user_visible_users ($config['id_user'], "IR", false);
	
	$total_users = 0;
	foreach ($users as $user) {
		if ($search_string != '') {
			if (stripos ($user['id_usuario'], $search_string) === false 
				&& stripos ($user['nombre_real'], $search_string) === false)
				continue;
		}
		
		if ($id_profile) {
			$sql = sprintf ("SELECT COUNT(*) FROM tusuario_perfil
					WHERE id_perfil = %d
					AND id_usuario = '%s'",
					$id_profile, $user['id_usuario']);
			$has_profile = get_db_sql ($sql);
			if (! $has_profile)
				continue;
		}
		
		if ($id_group != -1) {
			$sql = sprintf ("SELECT COUNT(*) FROM tusuario_perfil
					WHERE id_grupo = %d
					AND id_usuario = '%s'",
					$id_group, $user['id_usuario']);
			$has_profile = get_db_sql ($sql);
			if (! $has_profile)
				continue;
		}
		
		echo '<tr id="result-'.$user['id_usuario'].'">';
		echo '<td>'.$user['id_usuario'].'</td>';
		echo '<td>'.$user['nombre_real'].'</td>';
		echo '<td>'.$user['comentarios'].'</td>';
		echo '</tr>';
		$total_users++;
	}
	
	if ($total_users == 0) {
		echo '<tr colspan="4">'.__('No users found').'</tr>';
	}
	
	if (defined ('AJAX'))
		return;
}


$table->data = array ();
$table->width = '90%';

$table->data[0][0] = print_select_from_sql ('SELECT id,name FROM trole ORDER BY 2',
					'user_profile_search', $id_profile, '',
					__('Any'), 0, true, false, false, __('Role'));

$table->data[0][1] = print_select (get_user_groups (), 'user_group_search',
			$id_group, '', __('Any'), -1,
			true, false, false, __('Group'));

$table->data[2][0] = print_input_text ('search_string', '', '', 20, 255, true,
			__('Name'));
$table->data[2][1] = print_submit_button (__('Search'), 'search_button', false, 'class="sub search"', true);

echo '<form id="user_search_form" method="post">';
print_table ($table);
print_input_hidden ('search', 1);
echo '</form>';

unset ($table);
$table->class = 'hide result_table listing';
$table->width = '90%';
$table->id = 'user_search_result_table';
$table->head = array ();
$table->head[0] = __('Username');
$table->head[1] = __('Real name');
$table->head[2] = __('Comments');

print_table ($table);

echo '<div id="users-pager" class="hide pager">';
echo '<form>';
echo '<img src="images/control_start_blue.png" class="first" />';
echo '<img src="images/control_rewind_blue.png" class="prev" />';
echo '<input type="text" class="pagedisplay" />';
echo '<img src="images/control_fastforward_blue.png" class="next" />';
echo '<img src="images/control_end_blue.png" class="last" />';
echo '<select class="pagesize" style="display: none">';
echo '<option selected="selected" value="10">10</option>';
echo '</select>';
echo '</form>';
echo '</div>';

?>
