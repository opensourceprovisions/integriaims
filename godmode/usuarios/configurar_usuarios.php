<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2012 Ártica Soluciones Tecnológicas
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

check_login ();

enterprise_include ('godmode/usuarios/configurar_usuarios.php');

if (! give_acl ($config["id_user"], 0, "UM")) {
	audit_db($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation","Trying to access user edition");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;

if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}

// Init. vars
$comentarios = "";
$direccion = "";
$telefono = "";
$password = "";
$update_user = "";
$lang = "";
$nombre_real = "";
$nivel = 0;
$disabled = 0;
$id_company = 0;
$location = "";
// Default is create mode (creacion)
$modo = "creacion";
$num_employee = "";
$enable_login = 1;
$avatar = "0";

$user_fields = get_db_all_rows_sql ("SELECT * FROM tuser_field");

if ($user_fields === false) {
	$user_fields = array();
}

if (isset($_GET["borrar_grupo"])) {
	$id_user_profile = get_parameter ('borrar_grupo');
	$id_user = safe_output(get_parameter ("update_user", ""));
	enterprise_hook('license_delete_user_profile', array ($id_user, $id_user_profile));
}

$action = get_parameter("action", "edit");
$alta = get_parameter("alta");

///////////////////////////////
// LOAD USER VALUES
///////////////////////////////
if (($action == 'edit' || $action == 'update') && !$alta) {
	$modo = "edicion";
	$update_user = safe_output(get_parameter ("update_user", ""));

	// Read user data to include in form
	$sql = "SELECT * FROM tusuario WHERE id_usuario = '".safe_input($update_user)."'";
	$rowdup = get_db_row_sql ($sql);

	if ($rowdup === false) {
		echo ui_print_error_message (__('There was a problem loading user'), '', true, 'h3', true);

		//echo "</table>";
		include ("general/footer.php");
		exit;
	}
	else {
		$password=$rowdup["password"];
		$comentarios=$rowdup["comentarios"];
		$direccion=$rowdup["direccion"];
		$telefono=$rowdup["telefono"]; 
		$nivel =$rowdup["nivel"]; 
		$nombre_real=$rowdup["nombre_real"];
		$avatar = $rowdup["avatar"];
		$lang = $rowdup["lang"];
		$disabled = $rowdup["disabled"];
		$id_company = $rowdup["id_company"];
		$num_employee = $rowdup["num_employee"];
		$enable_login = $rowdup["enable_login"];
		$location = $rowdup["location"];
	}
}

///////////////////////////////
// UPDATE USER
///////////////////////////////
if ($action == 'update')  {
	$enable_login = get_parameter("enable_login");
	$user_to_update = get_parameter ("update_user");
	$nivel = get_parameter ("nivel",0);
	
	enterprise_include ('include/functions_license.php', true);
	
	$users_check = true;
	if ($enable_login) {
		$old_enable_login = get_db_value_filter('enable_login', 'tusuario', array('id_usuario'=>$user_to_update));
		$old_nivel = get_db_value_filter('nivel', 'tusuario', array('id_usuario'=>$user_to_update));
		if ($old_nivel != $nivel) {
			if ($nivel) {
				$users_check = enterprise_hook('license_check_manager_users_num');
			} else {
				$users_check = enterprise_hook('license_check_regular_users_num');
			}
		} else {
			if ($old_enable_login) {
				$users_check = true;
			} else {
				$is_manager = enterprise_hook('license_check_manager_user',array($user_to_update));
				if ($is_manager) {
					$users_check = enterprise_hook('license_check_manager_users_num');
				} else {
					$users_check = enterprise_hook('license_check_regular_users_num');
				}
			}
		}
	}

	if ($users_check === true || $users_check === ENTERPRISE_NOT_HOOK || !$enable_login) {
		if (isset ($_POST["pass1"])) {
			$nombre_real = get_parameter ("nombre_real");
			$nombre_viejo = get_parameter ("update_user");
			$nombre = $nombre_viejo ;
			$password = get_parameter ("pass1");
			$password2 = get_parameter ("pass2");
			$lang = get_parameter ("lang");
			$disabled = get_parameter ("disabled");
			$id_company = get_parameter ("id_company");
			$num_employee = get_parameter ("num_employee");
			$location = get_parameter ("location", "");
			$nivel = get_parameter ("nivel",0);		
			if ($nivel == 1) {
				$enable_login = 1;
			}
			
			//chech if exists num employee
			$already_exists = false;
			if (isset($num_employee) && ($num_employee != '')) {
				$sql_num = "SELECT num_employee FROM tusuario
								WHERE id_usuario<>'$update_user'
								AND num_employee<>''";

				$result = process_sql($sql_num);
				if ($result === false) {
					$already_exists = false;
				} else {
					foreach ($result as $res) {
						if ($res['num_employee'] == $num_employee) {
							$already_exists = true;
						}
					}
				}
			}
			
			if ($password <> $password2){
				echo ui_print_error_message (__('Passwords don\'t match.'), '', true, 'h3', true);
			}
			else if ($already_exists) {
				echo ui_print_error_message (__('Number employee already exists.'), '', true, 'h3', true);
			}
			else {
				$direccion = trim (ascii_output(get_parameter ("direccion")));
				$telefono = get_parameter ("telefono");
				$comentarios = get_parameter ("comentarios");
				$avatar = get_parameter ("avatar");
				$avatar = substr($avatar, 0, strlen($avatar)-4);
				
				if (dame_password ($nombre_viejo) != $password){
					$password = md5($password);
					$sql = "UPDATE tusuario SET disabled= $disabled, `lang` = '$lang', nombre_real ='".safe_output($nombre_real)."', password = '".$password."', telefono ='".$telefono."', direccion ='".$direccion."', nivel = '$nivel', comentarios = '$comentarios', avatar = '$avatar', id_company = '$id_company', num_employee = '$num_employee', enable_login = $enable_login, location = '$location' WHERE id_usuario = '$nombre_viejo'";
				}
				else {
					$sql = "UPDATE tusuario SET disabled= $disabled, lang = '$lang', nombre_real ='".$nombre_real."', telefono ='".$telefono."', direccion ='".$direccion."', nivel = '".$nivel."', comentarios = '".$comentarios."', avatar = '$avatar', id_company = '$id_company', num_employee = '$num_employee', enable_login = $enable_login, location = '$location' WHERE id_usuario = '".$nombre_viejo."'";
				}
				
				$resq2 = process_sql($sql);

				// Add group / to profile
				// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
				if ($is_enterprise) {
					if (isset($_POST["grupo"])) {
						if ($_POST["grupo"] <> "") {
							$grupo = $_POST["grupo"];
							$perfil = $_POST["perfil"];
							$id_usuario_edit = $_SESSION["id_usuario"];
							$is_manager = enterprise_hook('license_check_manager_user',array($user_to_update));

							if (!$is_manager) {
								$is_manager_profile = enterprise_hook('license_check_manager_profile', array($perfil));
								if ($is_manager_profile) {
									$users_check = enterprise_hook('license_check_manager_users_num');
								} else {
									$users_check = true;
								}
							} else {
								$users_check = true;
							}
							
							if ($users_check || !$enable_login) {
								$res = enterprise_hook('associate_userprofile');
								if($res === false) {
									echo ui_print_error_message (__('There was a problem assigning user profile'), '', true, 'h3', true);
								}
							} else {
								echo ui_print_error_message (__('There was a problem assigning user profile. The number of users has reached the license limit'), '', true, 'h3', true);
							}	
						}
					}
				}

				//Add custom fields
				foreach ($user_fields as $u) {

					$custom_value = get_parameter("custom_".$u["id"]);
					
					$sql = sprintf('SELECT data FROM tuser_field_data WHERE id_user = "%s" AND id_user_field = %d',
									$update_user, $u["id"]);
					
					$current_data = process_sql($sql);
					
					if ($current_data) {
						$sql = sprintf('UPDATE tuser_field_data SET data = "%s" WHERE id_user = "%s" AND id_user_field = %d',
								$custom_value, $update_user, $u["id"]);
					} else {
						$sql = sprintf('INSERT INTO tuser_field_data (`data`, `id_user`,`id_user_field`) VALUES ("%s", "%s", %d)',
								$custom_value, $update_user, $u["id"]);
					}

					$res = process_sql($sql);

					if ($res === false) {
						echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
					}
				}

				$modo = "edicion";
				echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
			}
		}
		else {
			echo ui_print_error_message (__('There was a problem updating user'), '', true, 'h3', true);
		}
	} else {
		$enable_login = 0;
		echo ui_print_error_message (__('The number of users has reached the license limit. You can update users without enable login'), '', true, 'h3', true);
	}
} 

///////////////////////////////
// CREATE USER
///////////////////////////////
if ($action == 'create'){
	
	$enable_login = get_parameter("enable_login");
	$nivel = get_parameter ("nivel",0);

	enterprise_include ('include/functions_license.php', true);

	if ($is_enterprise) {
		if ($nivel == 1) {
			$users_check = enterprise_hook('license_check_manager_users_num');
		} else {
			$users_check = enterprise_hook('license_check_regular_users_num');
		}
	} else {
		$users_check = true;
	}
	
	if (!$users_check) {
		$enable_login = 0;
	}

	// Get data from POST
	$nombre = strtolower(get_parameter ("nombre"));
	$password = get_parameter ("pass1");
	$password2 = get_parameter ("pass2");
	$nombre_real = get_parameter ("nombre_real");
	$lang = get_parameter ("lang");
	if ($password <> $password2){
		echo ui_print_error_message (__('Passwords don\'t match. Please repeat again'), '', true, 'h3', true);
	}
	$direccion = rtrim(get_parameter ("direccion"));
	$telefono = get_parameter ("telefono");
	$id_company = get_parameter ("id_company");
	$comentarios = get_parameter ("comentarios");
	$password = md5($password);
	$avatar = get_parameter ("avatar");
	$avatar = substr($avatar, 0, strlen($avatar)-4);
	$disabled = get_parameter ("disabled");
		
	$ahora = date("Y-m-d H:i:s");
	$num_employee = get_parameter("num_employee");
	$location = get_parameter ("location", "");
	//~ $sql_insert = "INSERT INTO tusuario (id_usuario, direccion, password, telefono, fecha_registro, nivel, comentarios, nombre_real, num_employee, avatar, lang, disabled, id_company, enable_login, location) VALUES ('".$nombre."','".$direccion."','".$password."','".$telefono."','".$ahora."','".$nivel."','".$comentarios."','".$nombre_real."','".$num_employee."','$avatar','$lang','$disabled','$id_company', $enable_login, '$location')";
	$sql_insert = "INSERT INTO tusuario (id_usuario, direccion, password, telefono, fecha_registro, nivel, comentarios, nombre_real, num_employee, avatar, lang, disabled, id_company, enable_login, location, disabled_login_by_license) VALUES ('".$nombre."','".$direccion."','".$password."','".$telefono."','".$ahora."','".$nivel."','".$comentarios."','".$nombre_real."','".$num_employee."','$avatar','$lang','$disabled','$id_company', $enable_login, '$location', 0)";

	$resq1 = process_sql($sql_insert);
	
	if (! $resq1)
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	else {

		//Insert custom fields
		foreach ($user_fields as $u) {

			$custom_value = get_parameter("custom_".$u["id"]);
			
			$sql = sprintf('INSERT INTO tuser_field_data (`data`, `id_user`,`id_user_field`) VALUES ("%s", "%s", %d)',
						$custom_value, $nombre, $u["id"]);

			$res = process_sql($sql);

			if ($res === false) {
				echo ui_print_error_message (__('There was a problem updating custom fields'), '', true, 'h3', true);
			}
		}
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
	}

	$update_user = $nombre;
	$modo ="edicion";

	if (!$users_check) {
		echo ui_print_error_message (__('User has been created disabled. The number of users has reached the license limit'), '', true, 'h3', true);
	}
}

if (isset($_GET["alta"])){
	if ($_GET["alta"]==1){
		echo '<h2>'.__('Create user').'</h2>';
	}
}

if (isset($_GET["update_user"]) OR isset($_GET["nuevo_usuario"])){
	echo '<h2>'.__('Update user').'</h2>';
	echo '<h4>'.$update_user.'</h4>';
}

if (isset($_GET["alta"]))
	// Create URL
	echo '<form name="new_user" id="form-user_config" method="post" action="index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&nuevo_usuario=1">';
else
	// Update URL
	echo '<form name="user_mod" id="form-user_config" method="post" action="index.php?sec=users&sec2=godmode/usuarios/configurar_usuarios&update_user='.$update_user.'">';

echo "<table width='100%' class='search-table-button'>";

echo '<tr>';
echo '<td class="datos">'.__('User ID');
echo '<td class="datos" colspan=5>';

if (isset($_GET["alta"])){
    echo '<input type="text" size=15 name="nombre" id="nombre" value="'.$update_user.'">';
    print_help_tip (__("User cannot have Blank spaces", false));
} else {
    echo '<i>';
    echo $update_user;
    echo "</i>";
}

if (isset($avatar)){
	echo "<td class='datos' rowspan=6>";
	echo "<img src='images/avatars/".$avatar.".png' id='avatar_preview'>";
}

echo '<tr><td class="datos2">'. __('Activation');
echo '<td class="datos2">';
if($disabled) {
	$active_chk = '';
	$disabled_chk = ' checked';
}
else {
	$active_chk = ' checked';
	$disabled_chk = '';
}

echo __('Enabled').'&nbsp;<input type="radio" class="chk" name="disabled" value="0"'.$active_chk.'>';
echo "&nbsp;&nbsp;";
echo __('Disabled').'&nbsp;<input type="radio" class="chk" name="disabled" value="1"'.$disabled_chk.'>';

if ($nivel != 1) {
	echo '<tr><td class="datos2">'. __('Enable login');
	echo '<td class="datos2">';
	if($enable_login) {
		$active_chk_login = ' checked';
		$disabled_chk_login = '';
	}
	else {
		$active_chk_login = '';
		$disabled_chk_login = ' checked';
	}

	echo __('Enabled').'&nbsp;<input type="radio" class="chk" name="enable_login" value="1"'.$active_chk_login.'>';
	echo "&nbsp;&nbsp;";
	echo __('Disabled').'&nbsp;<input type="radio" class="chk" name="enable_login" value="0"'.$disabled_chk_login.'>';
} else {
	echo "<input type='hidden' name='enable_login' value='1'/>";
}
    
?>

<tr><td class="datos2"><?php echo __('Num. employee') ?>
<td class="datos2"><input type="text" name="num_employee" value="<?php echo $num_employee ?>">

<tr><td class="datos2"><?php echo __('Real name') ?>
<td class="datos2"><input type="text" size=25 name="nombre_real" value="<?php echo $nombre_real ?>">

<tr><td class="datos"><?php echo __('Password') ?>
<td class="datos"><input type="password" name="pass1" value="<?php echo $password ?>">
<tr><td class="datos2"><?php echo __('Password confirmation') ?>
<td class="datos2"><input type="password" name="pass2" value="<?php echo $password ?>">

<tr><td class="datos"><?php echo __('Email') ?>
<td class="datos"><input type="text" name="direccion" size="30" value="<?php echo $direccion ?>">


<?PHP
// Avatar
echo "<tr><td>".__('Avatar');
echo "<td>";

$ficheros = list_files('images/avatars/', "png", 1, 0, "small");
array_unshift($ficheros, __('None'));
$avatar_forlist = $avatar . ".png";
echo print_select ($ficheros, "avatar", $avatar_forlist, '', '', 0, true, 0, false, false);	

?>

<tr><td class="datos"><?php echo __('Telephone') ?>
<td class="datos" colspan=2><input type="text" name="telefono" value="<?php echo $telefono ?>">

<tr><td class="datos"><?php echo __('Company') . print_help_tip ("Type at least two characters to search", true); ?>
<td class="datos" colspan=2>
<?php

$params = array();
$params['input_id'] = 'id_company';
$params['input_name'] = 'id_company';
$params['input_value'] = $id_company;
$params['return_help'] = 0;
print_company_autocomplete_input($params);
//print_select (get_companies (), 'id_company', $id_company, '', __('None'), 0, false);

?>

<tr><td class="datos"><?php echo __('Location') ?>
<td class="datos" colspan=2><input type="text" name="location" value="<?php echo $location ?>">

<tr><td class="datos2"><?php echo __('User mode') ?>

<td class="datos2" colspan=2>
<?php if ($nivel == 1){
	echo __('Super administrator').'&nbsp;<input type="radio" class="chk" name="nivel" value="1" checked>';
	echo "&nbsp;&nbsp;";
	echo __('Grouped user').'&nbsp;<input type="radio" class="chk" name="nivel" value="0">';
	echo "&nbsp;&nbsp;";
	echo __('Standalone user').'&nbsp;<input type="radio" class="chk" name="nivel" value="-1">';
	
} elseif ($nivel == 0) {
	echo __('Super administrator').'&nbsp;<input type="radio" class="chk" name="nivel" value="1">';
	echo "&nbsp;&nbsp;";
	echo __('Grouped user').'&nbsp;<input type="radio" class="chk" name="nivel" value="0" checked>';
	echo "&nbsp;&nbsp;";
	echo __('Standalone user').'&nbsp;<input type="radio" class="chk" name="nivel" value="-1">';
} else {
	echo __('Super administrator').'&nbsp;<input type="radio" class="chk" name="nivel" value="1">';
	echo "&nbsp;&nbsp;";
	echo __('Grouped user').'&nbsp;<input type="radio" class="chk" name="nivel" value="0">';
	echo "&nbsp;&nbsp;";
	echo __('Standalone user').'&nbsp;<input type="radio" class="chk" name="nivel" value="-1" checked>';
}

print_help_tip (__("Standalone users cannot work inside a group, will show only it's own data. Grouped users works with the ACL system, and super administrators have full access to everything", false));

echo "<tr>";
echo "<td>";
echo __('Language');
echo "<td>";
print_select_from_sql ("SELECT * FROM tlanguage", "lang", $lang, '', __('Default'), '', false, false, true, false);

//Print user custom fields

//Clean cache to avoid strange behaviour
clean_cache_db();

foreach ($user_fields as $u) {

	echo "<tr>";
	echo "<td>";
	echo $u["label"];
	echo "</td>";

	$sql_data = sprintf('SELECT data FROM tuser_field_data WHERE id_user = "%s" AND id_user_field = %d',
								$update_user, $u["id"]);

	$result = process_sql($sql_data);

	$data = "";

	if($result) {
		$data = safe_output($result[0]["data"]);
	}
	
	switch ($u["type"]) {
		case "text":
			echo "<td>";
			echo "<input type='text' name='custom_".$u["id"]."' value='".$data."'>";
			echo "</td>";
			echo "</tr>";
			break;
		case "combo":
			$aux = split(",", $u["combo_value"]);
			
			$options = array();

			foreach ($aux as $a) {
				$options[$a] = $a;
			}

			echo "<td>";
			echo print_select ($options, 'custom_'.$u["id"], $data, '', '', '0', true,0,false);
			echo "</td>";
			echo "</tr>";
			break;
		case "textarea":
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan=2>";
			echo "<textarea cols=75 rows=3 name='custom_".$u["id"]."'>";
			echo $data;
			echo "</textarea>";
			echo "</td>";
			echo "</tr>";
			break;	
	}
}

?>

<tr><td class="datos" colspan="3"><?php echo __('Comments') ?>
<tr><td class="datos2" colspan="3"><textarea name="comentarios" cols="75" rows="3">
<?php echo $comentarios ?>
</textarea>

<?php

echo '<tr><td class="datos2">'.__('Total tickets');
echo '<td class="datos2"><b>';
echo get_db_sql ("SELECT COUNT(*) FROM tincidencia WHERE id_creator = '".$update_user."'");
echo "</b></td></tr>";

echo '<tr><td class="datos2">'.__('Reports');
echo '<td class="datos2">';

$now = date("Y-m-d H:i:s");
$now_year = date("Y");
$now_month = date("m");

$working_month = get_parameter ("working_month", $now_month);
$working_year = get_parameter ("working_year", $now_year);


// Workunit report (detailed)
echo "&nbsp;&nbsp;";
$end_month = urlencode($now);
$begin_month =  urlencode($working_year."-01-01 00:00:00");

echo "<a href='index.php?sec=users&sec2=operation/users/user_workunit_report&timestamp_l=$begin_month&timestamp_h=$end_month&id=$update_user'>";
echo "<img border=0 title='".__("Workunit report")."' src='images/page_white_text.png'></A>";



echo '<tr>';
if ($modo == "edicion") { // Only show groups for existing users
	enterprise_hook ('manage_profiles');
	echo "</table>";

	echo "<div class='button-form' style=''>";
	print_input_hidden ('action', 'update');
	echo "<input name='uptbutton' type='submit' class='sub next' value='".__('Update')."'>";
	echo "</div><br>";
}	

enterprise_hook ('show_delete_profiles');

echo "</td></tr>";
echo "</table>";

if (isset($_GET["alta"])){
	echo "<div class='button-form' style='width: 100%' >";
	echo '<input name="crtbutton" type="submit" class="sub create" value="'.__('Create').'">';
	print_input_hidden ('action', 'create');
	echo '</div>';
} 

echo "</form>";
?> 

<script src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

$(document).ready (function () {
	avatar = $("#avatar").val();
	if (avatar == 0) {
		$("#avatar_preview").fadeOut ();
	}

	$("#avatar").change (function () {
		icon = this.value;
		$("#avatar_preview").fadeOut ('normal', function () {
			if (icon != 0) {
				$(this).attr ("src", "images/avatars/"+icon).fadeIn ();
			}
		});
	});

	var idUser = "<?php echo $config['id_user'] ?>";
	bindCompanyAutocomplete("id_company", idUser);
});

// Form validation
trim_element_on_submit('input[name="nombre"]');
trim_element_on_submit('input[name="num_employee"]');
trim_element_on_submit('input[name="nombre_real"]');
trim_element_on_submit('input[name="direccion"]');
validate_form("#form-user_config");
var rules, messages;
// Rules: input[name="num_employee"]
rules = {
	//required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_user_num: 1,
			user_num: function() { return $('input[name="num_employee"]').val() },
			user_id: "<?php echo $update_user?>"
        }
	}
};
messages = {
	//required: "<?php echo __('Number required')?>",
	remote: "<?php echo __('This employee number already exists')?>"
};
add_validate_form_element_rules('input[name="num_employee"]', rules, messages);
// Rules: input[name="nombre_real"]
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_user_name: 1,
			user_name: function() { return $('input[name="nombre_real"]').val() },
			user_id: "<?php echo $update_user?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This name already exists')?>"
};
add_validate_form_element_rules('input[name="nombre_real"]', rules, messages);
// Rules: input[name="pass1"]
rules = {
	required: true
};
messages = {
	required: "<?php echo __('Password required')?>"
};
add_validate_form_element_rules('input[name="pass1"]', rules, messages);
// Rules: input[name="pass2"]
rules = {
	equalTo: 'input[name="pass1"]'
};
messages = {
	equalTo: "<?php echo __('The passswords must coincide')?>"
};
add_validate_form_element_rules('input[name="pass2"]', rules, messages);
// Rules: input[name="nombre"]
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_user_id: 1,
			user_id: function() { return $('input[name="nombre"]').val() }
        }
	}
};
messages = {
	required: "<?php echo __('ID required')?>",
	remote: "<?php echo __('This user ID already exists')?>"
};
add_validate_form_element_rules('input[name="nombre"]', rules, messages);
// MAIL
rules = {
	//required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			check_mail: 1,
			mail: function() { return $('input[name="direccion"]').val() }
        }
	}
};
messages = {
	remote: "<?php echo __('Type a correct mail direction')?>"
};
add_validate_form_element_rules('input[name="direccion"]', rules, messages);

rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			check_user_name: 1,
			user_id: function() { return $('input[name="nombre"]').val() }
        }
	}
};
messages = {
	remote: "<?php echo __('User ID has invalid characters or exceeds 30 characters')?>"
};
add_validate_form_element_rules('input[name="nombre"]', rules, messages);

</script>
