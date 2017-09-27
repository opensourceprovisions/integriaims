<?php

global $config;
require_once ('include/functions_db.php');

function print_setup_tabs($selected_tab, $is_enterprise) {
	$setup_class = $visual_class = $password_class = $incidents_class = $mail_class = 
	$mailtemplates_class = $visibility_class = $inventory_class = $auth_class = $crm_class = $maintenance_class = $license_class = "";
	
	switch ($selected_tab) {
		case 'setup':
			$setup_class = 'button-bar-selected';
			$title = __('General setup');
			break;
		case 'visual':
			$visual_class = 'button-bar-selected';
			$title = __('Visual setup');
			break;
		case 'password':
			$password_class = 'button-bar-selected';
			$title = __('Password policy setup');
			break;
		case 'incidents':
			$incidents_class = 'button-bar-selected';
			$title = __('Incident setup');
			break;
		case 'mail':
			$mail_class = 'button-bar-selected';
			$title = __('Mail setup');
			break;
		case 'mailtemplates':
			$mailtemplates_class = 'button-bar-selected';
			$title = __('Mail templates setup').integria_help ("macros", true);
			break;
		case 'visibility':
			$visibility_class = 'button-bar-selected';
			$title = __('Visibility management');
			break;
		case 'inventory':
			$inventory_class = 'button-bar-selected';
			$title = __('Pandora FMS inventory');
			break;
		case 'auth':
			$auth_class = 'button-bar-selected';
			$title = __('Authentication configuration');
			break;
		case 'crm':
			$crm_class = 'button-bar-selected';
			$title = __('CRM setup');
			break;
		case 'maintenance':
			$maintenance_class = 'button-bar-selected';
			$title = (__('Old data maintenance'));
			break;
		case 'project':
			$project_class = 'button-bar-selected';
			$title = (__('Project management'));
			break;
		case 'license':
			$license_class = 'button-bar-selected';
			$title = (__('License'));
			break;
	}
	echo "<h2>" . __("Configuration Integria") . "</h2>";
	echo "<h4>" . $title;
	echo "<div id='button-bar-title'>";
	echo '<ul>';
	echo '<li class="' . $setup_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup"><span><img src="images/cog.png" title="'.__('Setup').'"></span></a></li>';
	echo '<li class="' . $visual_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_visual"><span><img src="images/chart_bar_dark.png" title="'.__('Visual setup').'"></span></a></li>';
	if ($is_enterprise) {
		echo '<li class="' . $password_class . '"><a href="index.php?sec=godmode&sec2=enterprise/godmode/setup/setup_password"><span valign=bottom><img src="images/lock_dark.png" title="'.__('Password policy').'"></span></a></li>';
	}
	echo '<li class="' . $incidents_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/incidents_setup"><span><img src="images/incident_dark.png" title="'.__('Incident setup').'"></span></a></li>';
	echo '<li class="' . $mail_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_mail"><span><img src="images/email_dark.png"  title="'.__('Mail setup').'"></span></a></li>';
	echo '<li class="' . $mailtemplates_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&search=1"><span><img src="images/email_edit.png"  title="'.__('Mail templates setup').'"></span></a></li>';
	if ($is_enterprise) {
		echo '<li class="' . $visibility_class . '"><a href="index.php?sec=godmode&sec2=enterprise/godmode/usuarios/menu_visibility_manager"><span valign=bottom><img src="images/eye.png" title="'.__('Visibility management').'"></span></a></li>';
	}
	echo '<li class="' . $inventory_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_pandora"><span><img src="images/inventory_dark.png"  title="'.__('Pandora FMS inventory').'"></span></a></li>';
	echo '<li class="' . $auth_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_auth"><span><img src="images/key.png"  title="'.__('Authentication').'"></span></a></li>';
	echo '<li class="' . $crm_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_crm"><span><img src="images/invoice_dark.png"  title="'.__('CRM setup').'"></span></a></li>';
	echo '<li class="' . $maintenance_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_maintenance"><span><img src="images/trash.png"  title="'.__('Old data maintenance').'"></span></a></li>';
	if(!isset($project_class)){
		$project_class ='';
	}
	echo '<li class="' . $project_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_project"><span><img src="images/star_dark.png"  title="'.__('Project management').'"></span></a></li>';
	echo '<li class="' . $license_class . '"><a href="index.php?sec=godmode&sec2=godmode/setup/setup_license"><span><img src="images/license.png"  title="'.__('License').'"></span></a></li>';
	echo '</ul>';

	echo '</div>';
	echo "</h4>";
}
?>
