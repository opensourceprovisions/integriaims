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

// Integria uses icons from famfamfam, licensed under CC Atr. 2.5
// Silk icon set 1.3 (cc) Mark James, http://www.famfamfam.com/lab/icons/silk/
// Integria uses Pear Image::Graph code
// Integria shares much of it's code with project Babel Enterprise and Pandora FMS,
// also a Free Software Project coded by some of the people who makes Integria.

// Set to 1 to do not check for installer or config file (for development!).
// Activate gives more error information, not useful for production sites
$develop_bypass = 0;

// If no config file, automatically try to install
if (! file_exists("include/config.php")) {
	// Check for installer presence
	if (! file_exists("install.php")) {
		include "general/error_noconfig.php";
		exit;
	}
	include ("install.php");
	exit;
}

// Check for installer presence
if (file_exists ("install.php")) {
	include "general/error_install.php";
	exit;
}

if (! is_readable ("include/config.php")) {
	include "general/error_perms.php";
	exit;
}
// Check perms for config.php
$perms = fileperms ('include/config.php');

if (! ($perms & 0600) && ! ($perms & 0660) && ! ($perms & 0640)) {
	include "general/error_perms.php";
	exit;
}

// Buffer the following html with PHP so we can store it to a variable later
$buffer_html = false;
if (isset($_POST["clean_output"])) {
	if ($_POST["clean_output"] == 1) {
		$buffer_html = true;
	}
}
if (isset($_GET["clean_output"])) {
	if ($_GET["clean_output"] == 1) {
		$buffer_html = true;
	}
}
if (isset($_POST["pdf_output"])) {
	if ($_POST["pdf_output"] == 1) {
		$buffer_html = true;
	}
}
if (isset($_GET["pdf_output"])) {
	if ($_GET["pdf_output"] == 1) {
		$buffer_html = true;
	}
}
if (isset($_POST["raw_output"])) {
	if ($_POST["raw_output"] == 1) {
		$buffer_html = true;
	}
}
if (isset($_GET["raw_output"])) {
	if ($_GET["raw_output"] == 1) {
		$buffer_html = true;
	}
}
if ($buffer_html) {
	ob_start();
}

require_once ('include/config.php');
require_once ('include/functions.php');
require_once ('include/functions_db.php');
require_once ('include/functions_html.php');
require_once ('include/functions_form.php');
require_once ('include/functions_calendar.php');
require_once ('include/auth/mysql.php');
require_once ('include/functions_db.mysql.php');
require_once ('include/functions_api.php');

include_once ("include/functions_update_manager.php");

if ($buffer_html) {
	$config["flash_charts"] = 0;
}

$is_enterprise = false;

/* Enterprise support */
if (file_exists ("enterprise/load_enterprise.php")) {
	require_once ("enterprise/load_enterprise.php");
	require_once ("enterprise/include/functions_license.php");
	$is_enterprise = true;
}

if (file_exists ("enterprise/include/functions_login.php")) {
	require_once ("enterprise/include/functions_login.php");
}

/* Load the basic configurations of extension and add extensions into menu. */
extensions_load_extensions ($config["extensions"]);

// Update user password
$change_pass = get_parameter('renew_password', 0);

if ($change_pass == 1) {
	
	$nick = $_POST["login"];

	//Checks if password has expired
	$check_status = check_pass_status($nick);

	if ($check_status != 0) {
		
		$password_old = (string) get_parameter ('old_password', '');
		$password_new = (string) get_parameter ('new_password', '');
		$password_confirm = (string) get_parameter ('confirm_new_password', '');
		$id = (string) get_parameter ('login', '');

		$changed_pass = login_update_password_check ($password_new, $password_confirm, $id, $password_old);

		if ($changed_pass) {
			//$_POST['renew_password'] = 0;
			require ("general/login_page.php");
		} else {
			//~ $expired_pass = true;
			$login_failed = true;
		}
	}
}

// Process external download id's
$external_download_id = get_parameter('external_download_id', "");
if ($external_download_id != ""){
	//Set some variables to use in download script
	$_POST["type"] = "external_release";
	$_POST["id_attachment"] = $external_download_id;
	include ("operation/common/download_file.php");
	exit;
}


$html_header = '<!--[if !IE]> -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<![endif]-->
<!--[if IE]>																																									
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<meta http-equiv="X-UA-Compatible" content="IE=9" >
<meta http-equiv="X-UA-Compatible" content="IE=8" >
<html xmlns="http://www.w3.org/1999/xhtml">
<![endif]-->';

// This is a clean and/or PDF output ?
$clean_output = get_parameter ("clean_output", 0);
$pdf_output = get_parameter ("pdf_output", 0);
$pdf_filename = get_parameter ("pdf_filename", "");
$pdf_path = get_parameter ("pdf_path", "");
$raw_output = get_parameter ("raw_output", 0);
$expired_pass = false;

echo $html_header;
echo "<title>" . $config["sitename"] . "</title>";

?>

<meta http-equiv="expires" content="never" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="resource-type" content="document" />
<meta name="distribution" content="global" />
<meta name="website" content="http://integriaims.com" />
<meta name="copyright" content="Artica Soluciones Tecnologicas (c) 2007-2012" />
<meta name="keywords" content="ticketing, management, project, ticket, tracking, ITIL" />
<meta name="robots" content="index, follow" />
<link rel="icon" href="images/integria_mini_logo.png" type="image/png" />
<link rel="stylesheet" href="include/styles/integria.css" type="text/css" />
<link rel="stylesheet" href="include/styles/sidemenu.css" type="text/css" />
<link rel="stylesheet" href="include/styles/integria_tip.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.accordion.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.datepicker.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.dialog.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.resizable.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.slider.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.tabs.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.core.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.theme.css" type="text/css" />
<link rel="stylesheet" href="include/styles/flora/flora.multiselect.css" type="text/css" />
<script type="text/javascript" src="include/js/jquery.js"></script>
<script type="text/javascript" src="include/js/calendar.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>
<script type="text/javascript" src="include/js/jquery-migrate-1.2.1.js"></script><!-- MIGRATE OLDER JQUERY CODE (TEMPORAL)-->
<script type="text/javascript" src="include/js/jquery.ui.core.js"></script>
<script type="text/javascript" src="include/js/jquery-ui.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.position.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.draggable.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.resizable.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.textarearesizer.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.dialog.js"></script>
<script type="text/javascript" src="include/js/jquery.form.js"></script>
<script type="text/javascript" src="include/js/jquery.axuploader.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/d3.v3.js"></script>

<!--[if lte IE 7]>
<script type="text/javascript" src="include/js/jquery.bgiframe.js"></script>
<link rel="stylesheet" href="include/styles/integria-ie-fixes.css" type="text/css" />
<![endif]-->

<?php

$login = get_parameter ('login');
$sec = get_parameter ('sec');
$sec2 = get_parameter ('sec2');
$recover = get_parameter('recover','');
$pure = (bool) get_parameter('pure',false);
$not_show_menu = 0;

if ($clean_output == 1) {
	echo '<link rel="stylesheet" href="include/styles/integria_clean.css" type="text/css" />';
}

// Password recovery
if ($recover != ""){
    require ('general/password_recovery.php');
    exit;
}

// Check request from IP's allowed in the API ACL list. Special request to generate PDF on crontask
$ip_origin = $_SERVER['REMOTE_ADDR'];
if (ip_acl_check ($ip_origin)) {
	// Only to see PDF reports!
	if (($pdf_output == 1) AND ($pdf_filename != "")){
		$scheduled_report_user = get_parameter ("scheduled_report_user","");
		$_SESSION['id_usuario'] = $scheduled_report_user;
	}
}

$minor_release_message = false;
$integria_free_days = 0;
$integria_free_wel = 0;
$custom_screen_loaded = false;
if ($is_enterprise) {
	$custom = get_db_value_sql('SELECT id FROM tcustom_screen WHERE home_enabled=1');
	if ($custom !== false) {
		$custom_screen_loaded = true;
	}
}
					
// Login process
if (! isset ($_SESSION['id_usuario']) && isset ($_GET["loginhash"])) {

	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($config["loginhash_pwd"] != "" && $loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
		logon_db ($loginhash_user, $_SERVER['REMOTE_ADDR']);
		$_SESSION['id_usuario'] = $loginhash_user;
		$config["id_user"] = $loginhash_user;
	}
	else {
			echo '<body class="login">';
			require ('general/login_page.php');
			exit;
	}
} elseif (! isset ($_SESSION['id_usuario']) && $login) {

	$nick = get_parameter ("nick");
	$pass = get_parameter ("pass");

	$config["auth_error"] = "";

	$nick_in_db = process_user_login ($nick, $pass);
	$is_admin = get_admin_user($nick_in_db);

	if (($nick_in_db !== false) && ($is_admin != 1) && ($is_enterprise) && ($config['enable_pass_policy'])) {

		$blocked = login_check_blocked($nick);

		if ($blocked) {
			echo '<body class="login">';
			require ('general/login_page.php');
			exit;
		}
		//Checks if password has expired
		$check_status = check_pass_status($nick, $pass);

		switch ($check_status) {
			case 1: //first change
			case 2: //pass expired
				$expired_pass = true;
				$_GET['no_login'] = 1;
				login_change_password($nick);
				break;
			case 0:
				$expired_pass = false;
				break;
		}
	}
	
	if (($nick_in_db !== false) && $expired_pass) { //login ok and password has expired
		echo '<body class="login">';
		require_once ('general/login_page.php');
		exit;
	} else if (($nick_in_db !== false) && (!$expired_pass)) { //login ok and password has not expired
		
		$check_managers = enterprise_hook("license_check_manager_users_num");
		$check_regulars = enterprise_hook("license_check_regular_users_num");
		$license_fail = false;	
		if (!$check_managers || !$check_regulars) {
			$license_fail = true;
			license_block_users();
		} 
		if ($is_admin) {
			$license_fail = false;
		}
			
		if (!$license_fail) {
			unset ($_GET["sec2"]);

			logon_db ($nick_in_db, $_SERVER['REMOTE_ADDR']);
			$_SESSION['id_usuario'] = $nick_in_db;
			$config['id_user'] = $nick_in_db;
			if ($sec2 == '') {
				if ($custom_screen_loaded) {
					$sec2 = 'enterprise/operation/custom_screens/custom_screens';
				} else {
					$sec2 = 'general/home';
				}
			}
			$minor_release_message = db_update_schema(); // MINOR RELEASES
			//compare first integria-free
			if (isset($config['tpage_size_wel'])){
				$integria_free_wel = 1;
			} else {
				$integria_free_wel = 0;
			}
			//compare date alert 3 days
			if (isset($config['integria_free_days'])){
				$integria_free_days = 1;
			} else {
				$integria_free_days = 0;
			}
		} else {
			echo '<body class="login">';
				require_once ('general/login_page.php');
				exit;
		}	

	} else { //login wrong
		$blocked = false;
		
		if (!$expired_pass) {	
			
			if ($is_admin != 1) {
				if ($is_enterprise)
					$blocked = login_check_blocked($nick);
				else
					$blocked = false;
			}
			
			if (!$blocked) {
				if ($is_enterprise){
					login_check_failed($nick); //Checks failed attempts
				}
				
				$first = substr ($pass, 0, 1);
				$last = substr ($pass, strlen ($pass) - 1, 1);
				$pass = $first . "****" . $last;
				
				if ($expired_pass == false) {
					$enable_login = get_db_value_sql("SELECT enable_login FROM tusuario WHERE id_usuario='".$nick."'");

					if ($enable_login == 0) {
						$disable_login = true;
					} else {
						$login_failed = true;
						unset($disable_login);
					}
				} else {
					unset($login_failed);
				}
				echo '<body class="login">';
				require_once ('general/login_page.php');
				exit ("</html>");
			} else {
				echo '<body class="login">';
				require_once ('general/login_page.php');
				exit ("</html>");
			}
		} else { 
			echo '<body class="login">';
			require_once ('general/login_page.php');
			exit ("</html>");
		}
	}
}
else if (! isset ($_SESSION['id_usuario'])) {

	// There is no user connected
	echo '</head>';
	echo '<body class="login">';
	require ('general/login_page.php');
	exit;
}
else {
	if (isset ($_SESSION['id_usuario'])) {
		$user_in_db = get_db_value_filter('id_usuario', 'tusuario', array('id_usuario'=>$_SESSION['id_usuario']));
		if ($user_in_db == false) {
			//logout
			$_REQUEST = array ();
			$_GET = array ();
			$_POST = array ();
			echo '<body class="login">';
			require ('general/login_page.php');
			$iduser = $_SESSION["id_usuario"];
			logoff_db ($iduser, $config["REMOTE_ADDR"]);
			unset($_SESSION["id_usuario"]);
			exit;
		}
	}
	
	// Create id_user variable in $config hash, of ALL pages.
	$config["id_user"] = $_SESSION['id_usuario'];
}

include ("include/config_process.php");

if ($buffer_html) {
	$config["flash_charts"] = 0;
}

load_menu_visibility();
?><script>var lang = {
	"Are you sure?" : "<?php echo __('Are you sure?')?>",
	"Added" : "<?php echo __('Added')?>",
	"Search inventory object" : "<?php echo __('Search inventory object')?>",
	"Already added" : "<?php echo __('Already added')?>",
	"Added" : "<?php echo __('Added')?>",
	"Search parent incident" : "<?php echo __('Search parent ticket')?>",
	"User search" : "<?php echo __('User search')?>",
	"There's no affected inventory object" : "<?php echo __('There\'s no affected inventory object')?>",
	"There's no affected object" : "<?php echo __('There\'s no affected object')?>",
	"Create incident" : "<?php echo __('Create ticket')?>",
	"Add workunit" : "<?php echo __('Add workunit')?>",
	"Submitting" : "<?php echo __('Submitting')?>",
	"Upload file" : "<?php echo __('Upload file')?>",
	"Search contact" : "<?php echo __('Search contact')?>",
	"Create contact" : "<?php echo __('Create contact')?>",
	"Search parent inventory" : "<?php echo __('Search parent inventory')?>"
};

</script>

<?php
// Log off
$logout = (bool) get_parameter ('logout');
if ($logout) {
	echo '</head>';
	echo '<body>';
	$_REQUEST = array ();
	$_GET = array ();
	$_POST = array ();
	echo '<body class="login">';
	require ('general/login_page.php');
	$iduser = $_SESSION["id_usuario"];
	logoff_db ($iduser, $config["REMOTE_ADDR"]);
	unset($_SESSION["id_usuario"]);
	exit;
}

// Common code for all operations
echo '</head>';
echo '<body>';

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
$session_id = session_id();
session_write_close ();

$id_menu = 'main';
// Special pages, which doesn't use sidemenu
if ($pure || empty($sec2) || $sec2 === 'general/home' ||
		$sec2 === 'enterprise/operation/custom_screens/custom_screens') {
	$not_show_menu = 1;
	$id_menu = 'main_pure';
}

// Clean output (for reporting or raw output
if ($clean_output == 0) {
?>
	<div id="wrap">
		<?php
		if (!$pure) {
			echo '<div id="header">';
				 require ("general/header.php"); 
			echo "</div>";
		}
		?>
		<!--
		<div id="menu">
		<?php require ("operation/main_menu.php"); ?>
		</div>
		-->
		

			<!-- This magic is needed to have it working in IE6.x and Firefox 4.0 -->
			<!-- DO NOT USE CSS HERE -->

		<div width=100% cellpadding=0 cellspacing=0 border=0 style='margin: 0px; padding: 0px; min-width: 1024px; '>
			
			<?php
			// Avoid render left menu for some special places (like home).
			if ($not_show_menu == 0){
					echo '<div id="sidebar">';
						require ("operation/side_menu.php"); 
						if (give_acl ($config["id_user"], 0, "AR"))
							require ("operation/tool_menu.php");
					echo '</div>';
			}
			?>
				
			<div id="<?php echo $id_menu; ?>">
				<?php			
				// Open a dialog if the database schema update has returned messages
				if ($minor_release_message) {
					echo "<div class= 'dialog ui-dialog-content' title='".__("Minor release update")."' id='mr_dialog'>$minor_release_message</div>";
						echo "<script type='text/javascript'>";
							echo "$(document).ready (function () {";
								echo "$('#mr_dialog').dialog ({
									resizable: true,
									draggable: true,
									modal: true,
									overlay: {
										opacity: 0.5,
										background: 'black'
									},
									width: 400,
									height: 200
								});";
								echo "$('#mr_dialog').dialog('open');";
						echo "	});";
					echo "</script>";
				}

				if ($integria_free_days) {
					echo "<div class= 'dialog ui-dialog-content' title='".__("You are less than three days as premium")."' id='three_day_dialog'>";
						echo "<div style='float:left; width:25%; padding-top:30px; text-align:center;'>";
						echo "<img src='images/icon_delete.png'></div>";
						echo "<div style='float:left; width:75%; padding-top:45px; font-size:12px;'>";
							echo "<b>".__('In 3 days you`ll lose the premium features from the 30 days trial. If you wish to purchase a premium license click')."</b>";
							echo "<a href='http://integriaims.com/' target='_blank'>".__(' Click here')."</a></br></div>";
					echo "</div>";
						echo "<script type='text/javascript'>";
							echo "$(document).ready (function () {";
								echo "$('#three_day_dialog').dialog ({
									resizable: true,
									draggable: true,
									modal: true,
									overlay: {
										opacity: 0.5,
										background: 'black'
									},
									width: 500,
									height: 200
								});";
								echo "$('#three_day_dialog').dialog('open');";
						echo "	});";
					echo "</script>";
				}

				if ($integria_free_wel) {
					echo "<div class= 'dialog ui-dialog-content' title='".__("Welcome Enterpise edition")."' id='welcome_day_dialog'>";
						echo "<div style='float:left; width:25%; padding-top:30px; text-align:center;'>";
						echo "<img src='images/icono_trial.png'></div>";
						echo "<div style='float:left; width:75%; padding-top:45px; font-size:12px;'>";
							echo "<b>".__('This is your first day of the 30 day enterprise edition trial ')."</b>";
							echo "<a href='http://integriaims.com/' target='_blank' style='color:#ff9000 !important;'>".__(' at our page.')."</a></br></br>";
						echo "<b>".__('Do you need help? ')."</b><a href='http://docs.integriaims.com/' target='_blank' style='color:#ff9000 !important;'> ".__(' Go to the wiki')."</a></div>";
					echo "</div>";
						echo "<script type='text/javascript'>";
							echo "$(document).ready (function () {";
								echo "$('#welcome_day_dialog').dialog ({
									resizable: true,
									draggable: true,
									modal: true,
									overlay: {
										opacity: 0.5,
										background: 'black'
									},
									width: 500,
									height: 200
								});";
								echo "$('#welcome_day_dialog').dialog('open');";
						echo "	});";
					echo "</script>";
				}

				if (get_parameter ('login', 0) !== 0) {
					// Display news dialog
					include_once("general/news_dialog.php");
				}

				// Page loader / selector
				if ($sec2 != "") {
					if (file_exists ($sec2.".php")) {
						if (! extensions_is_extension ($sec2.".php")) {
							require ($sec2.".php");
						} else {
							if ($sec != "godmode") {
								extensions_call_main_function (basename ($sec2.".php"));
							} else {
								extensions_call_godmode_function (basename ($sec2.".php"));
							}
						}
					} else {
						echo ui_print_error_message (__('Page not found'), '', true, 'h3', true);
					}
				}
				else {
					$custom_screen_loaded = false;
					if ($is_enterprise && (int)enterprise_include('custom_screens/CustomScreensManager.php', true) != ENTERPRISE_NOT_HOOK) {
						$custom_screens = CustomScreensManager::getInstance()->getCustomScreensList(false);
						if (!empty($custom_screens)) {
							foreach ($custom_screens as $id => $custom_screen) {
								if (isset($custom_screen['homeEnabled']) && (bool) $custom_screen['homeEnabled']) {
									enterprise_include('operation/custom_screens/custom_screens.php');
									$custom_screen_loaded = true;
								}
							}
						}
					}
					if (!$custom_screen_loaded) {
						require ("general/home.php");
					}
				}
				?>
			</div>
		</div>
	<!-- wrap ends here -->
	</div>

	<!-- footer starts here -->
	<div id="footer">
		<?php require("general/footer.php") ?>
	</div>
	<!-- footer ends here -->

<?php // end of clean output
} else {
	// clean output
	if ($sec2 != "") {
		if (file_exists ($sec2.".php")) {
			if (! extensions_is_extension ($sec2.".php")) {
				require ($sec2.".php");
			} else {
				if ($sec != "godmode") {
					extensions_call_main_function (basename ($sec2.".php"));
				} else {
					extensions_call_godmode_function (basename ($sec2.".php"));
				}
			}
		} else {
			echo "<br><b class='error'>".__('Page not found')."</b>";
		}
	} else {
		require ("general/home.php");  //default
	}
}

if ($pdf_output == 1){

    // Get current date time
    if (isset($_SERVER['REQUEST_TIME'])) {
		$time = $_SERVER['REQUEST_TIME'];
	} else {
		$time = time();
	}

	// Now collect the output buffer into a variable

	$html = ob_get_contents();
    $html .= "</body></html>";

    // Parse HTML and fix a few entries which makes problems with MPDF like <label> tag
    $html = str_replace ( "</label>" , "</label></b><br>" , $html);
    $html = str_replace ( "<label" , "<b><label" , $html);

	ob_end_clean();

	include("include/pdf_translator.php");

	$pdfObject = new PDFTranslator();
	
	// Set font from font defined in report
	$pdfObject->custom_font = $config["pdffont"];

	if ($custom_pdf) {
		$pdfObject->setMetadata(safe_output("Invoice", 'Integria IMS', 'Integria IMS', __("Integria IMS invoice")));
		
		$pdfObject->setFooterHTML($footer_text, true, true, true);
	} else {
		$pdfObject->setMetadata(safe_output("Integria IMS PDF Report", 'Integria IMS Report', 'Integria IMS', __("Automated Integria IMS report")));

		$html_header = '<table style="width: 100%; margin-bottom: 30px; border-bottom: 3px solid #FF7F00; background: #404040; color: #fff;">
					<tr>
						<td align="left" style="padding: 10px 20px 10px 10px;"><img src="images/'.$config["header_logo"].'" /></td>
					</tr></table>';
		
	
		$pdfObject->setHeaderHTML($html_header);
		$pdfObject->setFooterHTML("Integria IMS Report - ".date("D F d, Y H:i:s", $time));

		$html_cover = '<table style="width: 100%; margin-bottom: 30px; background: #404040; color: #fff;">
					<tr>
						<td align="center" style="padding: 10px 20px 10px 10px;"><img src="images/'.$config["header_logo"].'" /></td>
					</tr></table>';

		$report_name = urldecode(get_parameter("report_name", __("Integria IMS report")));
		$html_cover .= '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
		$html_cover .= '<div style="font-size: 36pt;text-align:center">'.$report_name.'</div>';
		$html_cover .= '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
		$html_cover .= '<div style="font-size: 18pt; text-align:right; font-style: italic">'.date("D F d, Y H:i:s", $time).'</div>';
		$pdfObject->addHTML($html_cover);
		$pdfObject->newPage();
	}
	
	// Clean all html entities before render to PDF
	$html = safe_output($html);
	
	$pdfObject->addHTML($html);
	
	if ($pdf_filename != "") {
		if ($pdf_path != "") {
			$pdfObject->writePDFfile ($pdf_filename, $pdf_path);
		} else {
			$pdfObject->writePDFfile ($pdf_filename);
		}
	}	
	else {
		$pdfObject->showPDF();
	}

    // Dirty thing, just for testing, do not use it
    // system ("rm /tmp/integria_graph_serialize_*");

}

if (($raw_output == 0) AND ($pdf_output == 0)){
    echo '</body></html>';
}
?>
