<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

echo    '<div id="login" class="databox_login">
        <div id="login_form_data_fail">';
        
echo "<a href='index.php'>";
if (isset($config["site_logo"]))
	echo '<img src="images/'.$config['site_logo'].'" alt="logo">';
else
	echo '<img src="images/loginlogo.png" alt="logo">';

echo '</a>';        
       
$recover = get_parameter ("recover", "");
$hash = get_parameter ("hash", "");
$validated = false;

echo '<h3>';
echo __('Password recovery');
echo    '</h3>';

if (($recover == "") AND ($hash == "")){
    // This NEVER should happen. Anyway, a nice msg for hackers ;)
    echo __("Don't try to hack this form. All information is sent to the user by mail");
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "HACK_ATTEMPT", "Something dirty happen in password recovery");
}
elseif ($hash == ""){

    $randomhash = md5($config["sitename"].rand(0,100).$recover);
    $email = get_db_sql ("SELECT direccion FROM tusuario WHERE disabled = 0 AND id_usuario = '$recover'");
    $subject ="Password recovery for ".$config["sitename"];
    $text = "Integria has received a request for password reset from IP Address ".$_SERVER['REMOTE_ADDR'].". Enter this validation code for reset your password: $randomhash";

    if ($email != ""){    
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PASSWD_RECOVERY", "User: $recover");
        integria_sendmail ($email, $subject, $text);
        process_sql ("UPDATE tusuario SET pwdhash = '$randomhash' WHERE id_usuario = '$recover'");
    }

    // Doesnt show a error message (not valid email or not valid user 
    // to don't give any clues on valid users
	echo '<div class="databox_login_msg" >';
    echo __("Don't close this window, you will receive an email with instructions on how to change your password.");
    echo "<br><br>";
    echo __("Enter here the validation code you should have received by mail");
    echo '</div>';

} else {
    $check = get_db_sql ("SELECT id_usuario FROM tusuario WHERE id_usuario = '$recover' AND pwdhash = '$hash'");

    if (strtolower($check) == strtolower($recover)){
        $newpass =  substr(md5($config["sitename"].rand(0,100).$recover),0,6);
        echo '<div class="databox_login_msg_green" >';
        echo __("Your new password is");
        echo " : <b>";
        echo $newpass."</b>";
        echo "</div>";
        echo "<br>";
        echo "<a href='index.php'>";
        echo __("Click here to login");
        echo "</A>";
        echo "<br>";
        process_sql ("UPDATE tusuario SET password = md5('$newpass') WHERE id_usuario = '$recover'");
        $validated = true;
    } else {
		echo '<div class="databox_login_msg" >';
        echo __("Invalid validation code");
        echo '</div>';
    }
}

if (!$validated) {
	echo "<br>";
	echo "<form method=post>";
	echo '<table class="pass_validate_table">';
	echo "<tr><td colspan=2>";
	print_input_text ('hash', '', '', '', 50, false, __('Validation code'));
	echo "<td>";

	print_submit_button (__('Validate'), '', false, 'class="sub next"');
	echo "</form>";

	echo '</table>';
}

echo '</div>
</div>';
?>

