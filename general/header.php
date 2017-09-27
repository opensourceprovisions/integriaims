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

check_login();

$is_login = get_parameter('login', 0);

// We need to strip HTML entities if we want to use in a sql search
$search_string = safe_output (get_parameter ("search_string",""));

// I prefer to make the layout with tables here, it's more exact and 
// doesnt depend of CSS interpretation. Please DO NOT TOUCH.

//echo "<table class='table_header' border=0 cellpadding=0 cellspacing=0>";
echo "<div class='table_header'>";
	//echo "<tr>";
		echo "<div id='logo_container'>";
			// Custom logo per group
			if ($config["enteprise"] == 1){
				$banner = "";
				$mygroup = get_first_group_of_user ($config["id_user"]);
				if ($mygroup != "")
					$banner = get_db_sql ("SELECT banner FROM tgrupo WHERE id_grupo = ".$mygroup);
				if ($banner != "")
					echo '<a href="index.php"><img src="images/group_banners/'.$banner.'" title="'.__('Home').'"/></a>';	
				else
					echo '<a href="index.php"><img src="images/'.$config["header_logo"].'" title="'.__('Home').'"/></a>';
			} else { 
				echo '<a href="index.php"><img src="images/'.$config["header_logo"].'" title="'.__('Home').'"/></a>';
			}
		echo '</div>';
		echo '<div class="header_menu">';
			echo '<div id="menu">';
			require ("operation/main_menu.php");
			echo '</div>';
		echo '</div>';
		echo '<div class="header_search_icon">';
			echo '<div class="header_search">';
				echo "<form method=post action='index.php?sec2=operation/search'>";
					echo "<input id='global_search' type=text name='search_string' size=20 value='$search_string'>";
				echo '</form>';
			echo '</div>';
			echo '<div class="header_icons">';
				//// This div is necessary for javascript actions. Dont touch ///
				echo '<div style="font-size: 0px; display: inline;" id="id_user">'.$config['id_user']."</div>";
				/////////////////////////////////////////////////////////////////
				$got_alerts = 0;
				$check_cron_exec = check_last_cron_execution ();
				$check_email_queue = check_email_queue();
				$result_check_update_manager = '';
				$check_alarm_calendar = check_alarm_calendar();
				$check_directory_permissions = check_directory_permissions();
				$check_minor_release_available = false;
				$check_browser = check_browser();
				if (dame_admin($config['id_user'])) {
					$check_minor_release_available = db_check_minor_relase_available ();
				}
				if ($is_login && dame_admin($config['id_user'])) { //check if user has logged and user is admin. Check update manager once.
					if ($config["enable_update_manager"]) {
						if ($config["enteprise"] == 1) {
							enterprise_include("include/functions_update_manager.php");
							$result_check_update_manager = update_manager_check_packages_for_header();
						} else {
							$result_check_update_manager = update_manager_check_online_free_packages (false);
						}
					}
					print_input_hidden ('result_check_update_manager', $result_check_update_manager);
				}
				if (!$check_cron_exec || !$check_email_queue || ($result_check_update_manager != '') || ($check_alarm_calendar) || ($check_directory_permissions) || ($check_minor_release_available) || ($check_browser)) {
					$got_alerts = 1;
					echo '<a href: >'.print_image('images/header_warning.png', true, array("onclick" => "openAlerts()","alt" => __('Warning'), "id" => "alerts", 'title' => __('Warning'))).'</a>';
				}
				echo '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.$config['id_user'].'" >';
					if (dame_admin ($config['id_user']))
						echo print_image('images/header_suit.png', true, array("alt" => $config['id_user'], 'title' => $config['id_user']));
					else
						echo print_image('images/header_user.png', true, array("alt" => $config['id_user'], 'title' => $config['id_user']));
				echo '</a>';
				echo '<a href="index.php?logout=1">' . print_image('images/header_logout.png', true, array("alt" => __('Logout'), 'title' => __('Logout'))) . '</a>';
				if (isset($config["id_user"]) && dame_admin($config["id_user"]) && $show_setup != MENU_HIDDEN) {
					echo '<a href="index.php?sec=godmode&sec2=godmode/setup/setup" id="setup_link"><img src="images/header_setup.png" title="' . __('Setup') . '"></a>';
				}
			echo '</div>';
		echo '</div>';
	//echo '</tr>';
echo '</div>';

echo "<div class= 'dialog ui-dialog-content' title='".__("Notices")."' id='alert_window'></div>";

//one div per alarm calendar
if ($check_alarm_calendar) {
	$alarms = check_alarm_calendar(false);
	foreach ($alarms as $alarm) {
		echo "<div class= 'dialog ui-dialog-content' id='popup_alert_window_".$alarm['id']."'></div>";
	}
}

?>

<script type="text/javascript" src="include/js/integria_header.js"></script>
<script type="text/javascript" src="include/js/integria.js"></script>

<script type="text/javascript">

function set_alarm_checked(id) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/header&set_alarm_checked=1&id="+id,
		dataType: "html",
		success: function(data){
			
		}

	});
}

function open_popup_alarm(id) {

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/header&get_alert_popup=1&id="+id,
		dataType: "html",
		success: function(data){	
			
			$("#popup_alert_window_"+id).html (data);
			$("#popup_alert_window_"+id).show ();

			$("#popup_alert_window_"+id).dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					title: "Calendar alert",
					close: function() {
						set_alarm_checked(id);
					},
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 340,
					height: 200
				});
			$("#popup_alert_window_"+id).dialog('open');
			
		}
	});
}

function check_alarms() {

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/header&check_alarms_popup=1",
		dataType: "json",
		success: function(data){
			jQuery.each (data, function (id, value) {
				var id = value['id'];
				open_popup_alarm(id);
			});
		}
			
	});
}

$(document).ready (function () {
	
	<?php
		if ($got_alerts) {
	?>
			$("#alerts").effect("pulsate", {times:6}, 5000);
	<?php
		}
	?>

	$("#global_search").focusin(function () {
		$("#global_search").animate({width: "150px"}, 500);
	});

	$("#global_search").focusout(function () {
		$("#global_search").animate({width: "85px"}, 500);
	});
	
	<?php
		if ($check_alarm_calendar) {
	?>
			check_alarms();
	<?php
		}
	?>
		
});
</script>
