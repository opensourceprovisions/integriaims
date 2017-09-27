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

check_login ();

$id_user = (string) get_parameter ('id');

$user = get_db_row ('tusuario', 'id_usuario', $id_user);
if ($user === false) {
	no_permission ();
	return;
}

if (! user_visible_for_me ($config["id_user"], $id_user)) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Forbidden", "User ".$config["id_user"]." tried to access to user detail of '$id_user'");
	no_permission ();
}

echo '<h2>'.__('User details').'</h2>';
echo '<h4>'.$id_user.'</h4>';

$upload_avatar = (bool) get_parameter ('upload_avatar');
$update_user = (bool) get_parameter ('update_user');

$has_permission = false;
if ($id_user == $config['id_user']) {
	$has_permission = true;
} else {
	$groups = get_user_groups ($id_user);
	foreach ($groups as $group) {
		if (give_acl ($config['id_user'], $group['id'], 'UM')) {
			$has_permission = true;
			break;
		}
	}
}

/* Get fields for user */
$email = $user['direccion'];
$phone = $user['telefono'];
$real_name = $user['nombre_real'];
$avatar = $user['avatar'];
$comments = $user['comentarios'];
$lang = $user['lang'];
$id_company = $user['id_company'];
$location = $user['location'];

// Upload a new avatar
if ($upload_avatar) {
	if (! $has_permission) {
		audit_db ($_SESSION["id_usuario"], $REMOTE_ADDR, "Security Alert. Trying to modify another user: (".$id_user.") ", "Security Alert");
		no_permission ();
	}
	
	if ($_FILES["upfile"]["error"] == UPLOAD_ERR_OK) {
		$tmp_name = $_FILES["upfile"]["tmp_name"];
		$filename = $_FILES["upfile"]["name"];
		
		$filename = str_replace (array(" ", "(", ")"), "_", $filename); // Replace blank spaces
		$filename = filter_var($filename, FILTER_SANITIZE_URL); // Replace conflictive characters
		
		$mimetype = mime_content_type($tmp_name);
		if ($mimetype == "image/png") {
			
			$size = getimagesize($tmp_name);
			if ($size[0] <= 150 && $size[1] <= 150) {
				
				$upload_result = move_uploaded_file($tmp_name, $config["homedir"]."/images/avatars/$filename");
				if ($upload_result) {
					echo ui_print_success_message (__("Avatar successfully uploaded"), '', true, 'h3', true);
				} else {
					unlink($tmp_name);
					echo ui_print_error_message (__("The avatar could not be uploaded"), '', true, 'h3', true);
				}
				
			} else {
				unlink($tmp_name);
				echo ui_print_error_message (__("The maximum dimensions of the avatar are 150x150px"), '', true, 'h3', true);
			}
			
		} else {
			unlink($tmp_name);
			echo ui_print_error_message (__("The avatar should be a PNG file"), '', true, 'h3', true);
		}
		
	}
	$update_user = ($update_user === true ) ? $update_user : false;
}

// Get user ID to modify data of current user.
if ($update_user) {
	if (! $has_permission) {
		audit_db ($_SESSION["id_usuario"], $REMOTE_ADDR, "Security Alert. Trying to modify another user: (".$id_user.") ", "Security Alert");
		no_permission ();
	}
	
	$password = (string) get_parameter ('password');
	$password_confirmation = (string) get_parameter ('password_confirmation');
	$email = (string) get_parameter ('email');
	$phone = (string) get_parameter ('phone');
	$real_name = (string) get_parameter ('real_name');
	$avatar = (string) get_parameter ('avatar');
	$avatar = substr ($avatar, 0, strlen ($avatar) - 4);
	$comments = (string) get_parameter ('comments');
	$lang = (string) get_parameter ('language_code');
	$location = (string) get_parameter ('location');
	
	if (! $avatar) {
		$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $id_user);
	}
	
	$error = false;
	if ($password != '' && md5 ($password) != $user['password']) {
		if ($password != $password_confirmation) {
			echo ui_print_error_message (__('Passwords don\'t match'), '', true, 'h3', true);
			$error = true;
		} else {
			// Only when change password
			$sql = sprintf ('UPDATE tusuario
				SET password = MD5("%s")
				WHERE id_usuario = "%s"',
				$password, $id_user);
		}
	} else {
		$sql = sprintf ('UPDATE tusuario
			SET nombre_real = "%s", telefono = "%s", direccion = "%s",
			avatar = "%s", comentarios = "%s", lang = "%s", location = "%s"
			WHERE id_usuario = "%s"',
			$real_name, $phone, $email, $avatar,
			$comments, $lang, $location, $id_user);
	}
	
	if (! $error) {
		$result = process_sql ($sql);
		
		if ($result !== false) {
			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
		} else {
			echo ui_print_error_message (__('Could not be updated'), '', true, 'h3', true);
		}
	}
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->rowspan = array ();
$table->rowspan[0][3] = 4;
$table->rowspan[0][2] = 3;
$table->colspan = array ();
$table->colspan[5][0] = 5;
$table->size = array ();
$table->size[0] = '30%';
$table->size[1] = '30%';
$table->size[2] = '20%';
$table->size[3] = '15%';
$table->data = array ();

$table->data[0][0] = print_label (__('User ID'), '', '', true, $id_user);
if ($has_permission) {
	$table->data[0][1] = print_input_text ('real_name', $real_name, '', 20, 125, true, __('Real name'));
} else {
	$table->data[0][1] = print_label (__('Real name'), '', '', true, $real_name);
}
$avatar_help_tip = print_help_tip (__('The avatar should be PNG type and its height or width can not exceed the 150px'), true, 'tip" style="padding-top:0px;');
$table->data[0][2] = print_label (__('Image'). $avatar_help_tip, '', '', true) ;
$table->data[0][2] .= "<br><br>";
$files = list_files ('images/avatars/', "png", 1, 0, "small");
$table->data[0][2] .= "<div id='avatar_box' mode='select'>";
$table->data[0][2] .= "<div id='avatar_select'>";
$table->data[0][2] .= print_select ($files, "avatar", $avatar, '', '', 0, true, 0, true, false, false, 'margin-top: 5px; margin-bottom: 5px;');
if ($has_permission) {
	$table->data[0][2] .= "<div style='text-align:center;'>";
	$table->data[0][2] .= print_button (__('Upload new avatar'), 'upload_avatar', false, 'change_avatar_mode();', 'class="sub next" style="float: left;"', true);
	$table->data[0][2] .= "</div>";
}
$table->data[0][2] .= "</div>";
if ($has_permission) {
	$table->data[0][2] .= "<div id='avatar_upload' style='display:none; float: left;'>";
	$table->data[0][2] .= "<form id='form-avatar_upload' method='post' action='index.php?sec=users&amp;sec2=operation/users/user_edit' enctype='multipart/form-data'>
								<div style='text-align: center;'>
									<input id='file-upfile' type='file' accept='image/png' name='upfile' class='file sub' style='margin: 5px; width: 80%;'>
									<input id='submit-upload_avatar' type='submit' value='".__('Upload')."' class='sub upload'>
									<input id='hidden-upload_avatar' type='hidden' name='upload_avatar' value='true'>
									<input type='button' class='sub next' value='".__('Cancel')."' onclick='change_avatar_mode();'>
								</div>
							</form>";
	$table->data[0][2] .= "</div>";
}
if($avatar){
	$avatar = $avatar.".png";
	$table->data[0][3] = '<img id="avatar-preview" src="images/avatars/'.$avatar.'">';
} else {
	$table->data[0][3] = '<img id="avatar-preview" src="images/avatars/avatar_notyet.png">';	
}
$table->data[0][3] .= "</div>";

$company_name = get_db_value('name','tcompany','id',$id_company);
$table->data[1][0] = "<b>".__('Company')."</b><br>$company_name";

if($company_name === false) {
	$company_name = '<i>-'.__('None').'-</i>';
	$table->data[1][0] = "<b>".__('Company')."</b><br>$company_name";
}
else {
	$table->data[1][0] = "<b>".__('Company')."</b><br>$company_name";
	$table->data[1][0] .= "&nbsp;&nbsp;<a href='index.php?sec=customers&sec2=operation/companies/company_detail&id=$id_company'>";
	$table->data[1][0] .= "<img src='images/company.png'></a>";
}

$table->data[1][1] = print_input_text ('location', $location, '', 20, 250, true, __('Location'));

if ($has_permission) {
	$table->data[2][0] = print_input_text ('email', $email, '', 20, 60, true, __('Email'));
	$table->data[2][0] .= print_help_tip (__('You can add several email addresses, separated by commas.'), true);
	$table->data[2][1] = print_input_text ('phone', $phone, '', 20, 40, true, __('Telephone'));
	$table->data[4][0] = print_select_from_sql ("SELECT id_language, name FROM tlanguage ORDER BY name",
		'language_code', $lang, '', __('Default'), '', true, false, false, __('Language'));

	$table->data[4][1] = "<b>".__('Total tickets opened'). "</b><br><input type=text readonly size=5 value='". get_db_sql ("SELECT COUNT(*) FROM tincidencia WHERE id_creator = '".$id_user."'"). "'>";

	$table->data[5][0] = print_textarea ('comments', 8, 55, $comments, '', true, __('Comments'));
} else {
	$email = ($email != '') ? $email : __('Not provided');
	$phone = ($phone != '') ? $phone : __('Not provided');
	
	$table->data[2][0] = print_label (__('Email'), '', '', true, $email);
	$table->data[2][1] = print_label (__('Telephone'), '', '', true, $phone);
	if ($user['comentarios'] != '')
		$table->data[3][0] = print_label (__('Comments'), '', '', true, $comments);
}

if ($has_permission) {
	echo '<form id="form-user_edit" method="post" action="index.php?sec=users&sec2=operation/users/user_edit" enctype="multipart/form-data">';
	
	print_table ($table);
	echo "<div class='button-form' >";
		$data = print_submit_button (__('Update'), 'upd_btn', false, 'class="upd sub"', true);
		$data .= print_input_hidden ('update_user', 1, true);
		echo $data;
	echo "</div>";
	
	$table->data = array ();
	$table->data[0][0] = print_input_password ('password', '', '', 20, 20, true, __('Password'));
	$table->data[0][1] = print_input_password ('password_confirmation', '', '', 20, 20, true, __('Password confirmation'));
	
	echo '<h1>'.__('Change password').'</h1>';
	
	$data = "<div class='button-form' >" . print_submit_button (__('Update'), 'pass_upd_btn', false, 'class="upd sub"', true);
	$data .= print_input_hidden ('update_user', 1, true);
	$data .= print_input_hidden ('id', $user["id_usuario"], true) . "</div>";
	
	$table->data[0][3] = $data;
	
	print_table ($table);

	echo '</form>';
} else {
	print_table ($table);
}

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

function change_avatar_mode () {
	
	if ($("#avatar_box").prop('mode') == 'upload') {
		$("#avatar_box").prop('mode', 'select');
		$("#avatar_upload").hide("slide", {direction: 'right'}, 250, function() {
			$("#avatar_select").show("slide", {direction: 'left'}, 250);
		});
	} else {
		$("#avatar_box").prop('mode', 'upload');
		$("#avatar_select").hide("slide", {direction: 'right'}, 250, function() {
			$("#avatar_upload").show("slide", {direction: 'left'}, 250);
		});
	}
}

$(document).ready (function () {
	
	$("#submit-upd_btn").click(function() {
		$("#hidden-upload_avatar").val(0);
		$("#hidden-update_user").val(1);
	});
	$("#submit-pass_upd_btn").click(function() {
		$("#hidden-upload_avatar").val(0);
		$("#hidden-update_user").val(1);
	});
	$("#submit-upload_avatar").click(function() {
		$("#hidden-update_user").val(0);
		$("#hidden-upload_avatar").val(1);
	});
	
	$("#avatar").change (function () {
		icon = this.value.substr (0, this.value.length - 4);
		
		$("#avatar-preview").fadeOut ('normal', function () {
			$(this).attr ("src", "images/avatars/"+icon+".png").fadeIn ();
		});
	});
	
	$('textarea').TextAreaResizer ();
});

// Form validation
trim_element_on_submit('#text-real_name');
trim_element_on_submit('#text-email');
validate_form("#form-user_edit");
var rules, messages;
// Rules: #text-real_name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_user_name: 1,
			user_name: function() { return $('#text-real_name').val() },
			user_id: "<?php echo $id_user?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This name already exists')?>"
};
add_validate_form_element_rules('#text-real_name', rules, messages);
// Rules: #text-email
rules = {
	required: true,
	//email: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_user_email: 1,
			user_email: function() { return $('#text-email').val() },
			user_id: "<?php echo $id_user?>"
        }
	}
};
messages = {
	required: "<?php echo __('Email required')?>",
	//email: "<?php echo __('Invalid email')?>",
	remote: "<?php echo __('This email already exists')?>"
};
add_validate_form_element_rules('#text-email', rules, messages);
// MAIL
rules = {
	//required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			check_mail: 1,
			mail: function() { return $('#text-email').val() }
        }
	}
};
messages = {
	remote: "<?php echo __('Type a correct mail direction')?>"
};
add_validate_form_element_rules('#text-email', rules, messages);

</script>
