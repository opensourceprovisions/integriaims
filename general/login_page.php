<?php
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

// Overrides the default background with the defined by the user
if (!empty($config['login_background'])) {
	?>
		<script type="text/javascript" language="javascript">
		$("body.login").css("background", "url(images/backgrounds/<?php echo $config['login_background']; ?>)");
		</script>
	<?php
}

echo '<div class="databox_login" id="login">';
$no_login = get_parameter('no_login', 0);

$action = "index.php";
$params = '';
foreach ($_GET as $name => $value) {
	$params .= $name.'='.$value.'&';
}
if ($params != '')
	$action .= '?'.$params;

echo '<form method="post" action="'.$action.'">';

print_input_hidden ('login', 1);
foreach ($_POST as $name => $value)
	print_input_hidden ($name, $value);

//Select style for global box, normal or fail
if (isset ($login_failed)) {

	echo "<div id='login_form_data_fail'>";

} else {
	echo "<div id='login_form_data'>";
}

// Failed login ??

if (isset ($login_failed)) {
	echo '<div class="databox_login_msg" >';
	echo '<h3>';
	echo __('Please re-enter your password');
	echo '</h3><br>';
	echo __("The password you entered is incorrect, please try again.");
	echo "<br><br>";
	echo __("Forgot your passowrd?");
	echo " <a href='index.php?recover=$nick'>";
	echo "<b>".__("Click here to reset it")."</b>";
	echo "</a> ";
	$nick = get_parameter ($nick);

	echo '</div>';
}

if (isset ($disable_login)) {
	echo '<div class="databox_login_msg" >';
	echo '<h3>';
	echo __('This user has disabled login');
	echo '</h3><br>';
	$nick = get_parameter ($nick);

	echo '</div>';
}

//Login table begins
echo "<table class='login_table'>";
	echo '<tr>';
		echo "<td rowspan=6 style='width: 250px; border-right: 1px solid #dadada'>";
			echo "<a href='index.php'>";
				if (isset($config["site_logo"]))
					echo '<img src="images/'.$config['site_logo'].'" alt="logo">';
				else
					echo '<img src="images/loginlogo.png" alt="logo">';
			echo '</a>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo "<td class='login_label'>";
			echo "<strong>".__("Username")."</strong>";
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo "<td class='login_input'>";
			echo print_input_text_extended ("nick", '', "nick", '', '',
				'', false, '', 'class="login" placeholder="'.__("Nick").'"', true);
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td class="login_label">';
			echo "<strong>".__("Password")."</strong>";
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo "<td class='login_input'>";
			echo print_input_text_extended ("pass", '', "pass", '',
				'', '', false, '', 'class="login" placeholder="'.__("Password").'"', true, true);
		echo '</td>';
	echo '</tr>';
	echo "<tr>";
		echo "<td class='login_button'>";
			echo '<input class="sub next login_button" 
				type="submit" value="'.__("LOGIN").'" name="Login">';
		echo '</td>';
	echo '</tr>';
echo "</table>";

	echo '<div id="ver_num">';
		echo '<div id="ver_num_rotate">';
			echo $config["version"];
		echo "</div>";
	echo "</div>";
	
echo "</div>";

//show object hidden
echo '<div id="show_login_hidden" style="display:none;">';
print_input_text('show_login', $no_login);
echo '</div>';

echo '</form>';
echo '</div>';

?>

<script type="text/javascript" language="javascript">
	document.getElementById('nick').focus();
	
	if ($("#text-show_login").val() == 1) {
		$("#login_form_data").css("display", "none");
		$("#login_form_data_fail").css("display", "none");
	}	

</script>
