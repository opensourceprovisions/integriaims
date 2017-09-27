<?php 

// INTEGRIA IMS v2.0
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2007-2008 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

/**
 * Sends an email to a group.
 *
 * If the group doesn't have an email configured, the email is only sent
 * to the default user.
 *
 * @param int Group id.
 * @param string Email subject.
 * @param string Email body.
 */
function send_group_email ($id_group, $subject, $body) {
	$group = get_db_row ("tgrupo", "id_grupo", $id_group);
	$name = $group['nombre'];
	$emails_group = $group['email_group'];
	$emails_forced_email = $group['forced_email'];
	/* If the group has no email, use the email of the risponsable */
	$email = get_user_email ($group['id_user_default']);
	integria_sendmail ($email, $subject, $body, false, "", $group['email_from']);
	if ($emails_group == '') {
		$email_group = explode(',',$emails_group);
		foreach ($email_group as $k){
			integria_sendmail ($k, $subject, $body, false, "", $group['email_from']);	
		}
	}
}

/**
 * Selects all groups (array (id => name)) or groups filtered
 *
 * @param mixed Array with filter conditions to retrieve groups or false.  
 *
 * @return array List of all groups
 */
function group_get_groups ($filter = false) {
	if ($filter === false) { 
		$grupos = get_db_all_rows_in_table ("tgrupo", "nombre");
	}
	else {
		$grupos = get_db_all_rows_filter ("tgrupo", $filter);
	}
	//$return = array ();
	if ($grupos === false) {
		return $return;
	}
	foreach ($grupos as $grupo) {
		$return[$grupo["id_grupo"]] = $grupo["nombre"];
	}
	return $return;
}

//Get users by group
function groups_get_users($id_group) {
	
	$sql = "SELECT * FROM tusuario_perfil, tusuario
		WHERE tusuario_perfil.id_grupo=$id_group
		AND tusuario_perfil.id_usuario=tusuario.id_usuario
		ORDER BY tusuario.id_usuario";
	
	$users_info = get_db_all_rows_sql($sql);
	
	return $users_info;
}

function print_groups_table ($groups) {
	
	enterprise_include("include/functions_groups.php");
	$return = enterprise_hook ('print_groups_table_extra', array($groups));
	if ($return === ENTERPRISE_NOT_HOOK){
		echo "<div class='divresult'>";
		echo '<table width="99%" class="listing" id="table1">';
		echo '<thead>';
		echo 	'<tr>';
		echo 		'<th class="header c0" scope="col">'.__('Users').'</th>';
		echo 		'<th class="header c1" scope="col">'.__('Icon').'</th>';
		echo 		'<th class="header c2" scope="col">'.__('Name').'</th>';
		echo 		'<th class="header c3" scope="col">'.__('Parent').'</th>';
		echo 		'<th class="header c4" scope="col">'.__('Delete').'</th>';
		echo 	'</tr>';
		echo '</thead>';
		$count = 0;
		if ($groups === false)
			$groups = array ();
			
		if (!empty($groups)) {
			foreach ($groups as $group) {
				$data = array ();
				
				$num_users = get_db_value ("COUNT(id_usuario)", "tusuario_perfil", "id_grupo", $group["id_grupo"]);
				if ($num_users > 0) {
					$users_icon = '<a href="javascript:"><img src="images/group.png" title="'.__('Show and hide the user list').'" /></a>';
				} else {
					$users_icon = '';
				}
				
				$icon = '';
				if ($group['icon'] != '')
					$icon = '<img src="images/groups_small/'.$group['icon'].'" />';
					
				if ($group["id_grupo"] != 1) {
					$group_name = '<a href="index.php?sec=users&sec2=godmode/grupos/configurar_grupo&id='.
						$group['id_grupo'].'">'.$group['nombre'].'</a>';
				} else {
					$group_name = $group["nombre"];
				}
				$parent = dame_nombre_grupo ($group["parent"]);
				
				//Group "all" is special not delete and no update
				if ($group["id_grupo"] != 1) {
					$delete_button = '<a href="index.php?sec=users&
							sec2=godmode/grupos/lista_grupos&
							id_grupo='.$group["id_grupo"].'&
							delete_group=1&id='.$group["id_grupo"].
							'" onClick="if (!confirm(\''.__('Are you sure?').'\')) 
							return false;">
							<img src="images/cross.png"></a>';
				} else {
					$delete_button = "";
				}
				
				echo '<tr id="table1-'.$count.'" style="border:1px solid #505050;" class="datos2">';
				echo 	'<td id="table1-'.$count.'-0" style="text-align:center; width:40px;" class="datos2">'.$users_icon.'</td>';
				echo 	'<td id="table1-'.$count.'-1" style="width:40px;" class="datos2">'.$icon.'</td>';
				echo 	'<td id="table1-'.$count.'-2" style=" font-weight: bold;" class="datos2">'.$group_name.'</td>';
				echo 	'<td id="table1-'.$count.'-3" style="" class="datos2">'.$parent.'</td>';
				echo 	'<td id="table1-'.$count.'-4" style=" text-align:center; width:40px;" class="datos2">'.$delete_button.'</td>';
				echo '</tr>';
				echo '<tr id="table1-'.$count.'-users" style="display:none;">';
				echo 	'<td colspan="5" style="text-align:center; background-color:#e6e6e6;">';
				echo 		'<table width="99%" cellpadding="0" cellspacing="0" border="0px" id="table_users_'.$count.'">';
				echo 			'<tr style="text-align:center;">';
				if ($num_users > 0) {
					$users_sql = "SELECT * FROM tusuario_perfil WHERE id_grupo =".$group["id_grupo"]." ORDER BY id_usuario"; 

					$count_users = 0;
					$new = true;
					while ($user = get_db_all_row_by_steps_sql($new, $result_users, $users_sql)) {
						$new = false;
						if ($count_users >= 4) {
							$count_users = 0;
							echo '</tr>';
							echo '<tr style="text-align:center;">';
						}
						$user_name = "<a href=\"index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&update_user=".$user['id_usuario']."\"><strong>".$user['id_usuario']."</strong></a>";
						$user_real_name = get_db_value("nombre_real", "tusuario", "id_usuario", $user['id_usuario']);
						$delete_icon = '<a href="index.php?sec=users&sec2=godmode/grupos/lista_grupos&delete_user=1&id_user_delete='.$user['id_usuario'].'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"><img src="images/cross.png"></a>';

						$user_name = "$user_name&nbsp;($user_real_name)&nbsp;".$delete_icon;
						echo 		'<td style="background-color:#e6e6e6;"">'.$user_name.'</td>';
						$count_users++;
					}
				} else {
					echo 			'<td style="background-color:#e6e6e6;"">'.__('There are no users').'</td>';
				}
				
				echo 			'</tr>';
				echo 		'</table>';
				echo 	'</td>';
				echo '</tr>';
				
				echo "<script type=\"text/javascript\">
					  $(document).ready (function () {
						  $(\"#table1-$count-0\").click(function() {
							  $(\"#table1-$count-users\").toggle();
						  });
					  });
					  </script>";	
				
				$count++;
			}
		}
		
		echo '</table>';
		if (empty($groups)) {
			echo ui_print_error_message (__("No groups"), '', true, 'h3', true);
		}
		echo '</div>';
	}
}

function groups_get_group_name($id_group) {
	$name = get_db_value('nombre', 'tgrupo', 'id_grupo', $id_group);
	
	return $name;
}

function groups_get_user_groups($id_user) {
	global $config;
	
	if ($id_user == "") {
		$id_user = $config['id_user'];
	}

	$groups = get_db_all_rows_filter('tusuario_perfil', array('id_usuario'=>$id_user), 'id_grupo');
	if ($groups === false) {
		$groups = array();
	}

	$group_ids = "(";
	$i = 0;
	foreach ($groups as $group) {
		if ($i == 0) {
			$group_ids .= $group['id_grupo'];
		} else {
			$group_ids .= ",".$group['id_grupo'];
		}
		$i++;
	}

	$group_ids .= ")";
	
	return $group_ids;
}

function groups_flatten_tree_groups($tree, $deep) {
	foreach ($tree as $key => $group) {
		$return[$key] = $group;
		unset($return[$key]['branch']);
		$return[$key]['deep'] = $deep;
		
		if (!empty($group['branch'])) {
			$return = $return +
				groups_flatten_tree_groups($group['branch'], $deep + 1);
		}
	}
	
	return $return;
}


/**
 * Make with a list of groups a treefied list of groups.
 *
 * @param array $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep The level of profundity in the branch.
 *
 * @return array The treefield list of groups.
 */
function groups_get_groups_tree_recursive($groups) {
	$return = array();
	
	$tree = $groups;
	foreach($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		// If the user has ACLs on a gruop but not in his father,
		// we consider it as a son of group "all"
		if(!in_array($group['parent'], array_keys($groups))) {
			$group['parent'] = 0;  
		}
		
		$tree[$group['parent']]['hash_branch'] = 1;
		$tree[$group['parent']]['branch'][$key] = &$tree[$key];
		
	}
	
	// Depends on the All group we give different format
	if (isset($groups[0])) {
		$tree = array($tree[0]);
	}
	else {
		$tree = $tree[0]['branch'];
	}
	
	$return = groups_flatten_tree_groups($tree, 0);
	
	return $return;
}

/**
 * Return a array of id_group of childrens (to branches down)
 *
 * @param integer $parent The id_group parent to search the childrens.
 * @param array $groups The groups, its for optimize the querys to DB.
 */
function groups_get_childrens($parent, $groups = null) {
	if (empty($groups)) {
		$groups = get_db_all_rows_in_table('tgrupo');
	}

	$return = array();
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		if ($group['parent'] == $parent) {
			$return = $return + array($group['id_grupo'] => $group) + groups_get_childrens($group['id_grupo'], $groups);
		}
	}
	
	return $return;
}
?>
