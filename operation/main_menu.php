<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (!isset($config["id_user"]))
	return;

echo "<ul>";

// Projects
if (give_acl($config["id_user"], 0, "PR") && ($show_projects != MENU_HIDDEN) && (get_standalone_user($config["id_user"]) == false)){
    // Project
    if ($sec == "projects")
	    echo "<li id='current' class='project'>";
    else
	    echo "<li class='project'>";
	echo "<div>|</div>";
    echo "<a href='index.php?sec=projects&sec2=operation/projects/project_overview'>".__('Projects')."</a></li>";
}

// Support submenus ACLs
if (get_standalone_user($config["id_user"])) {
	$incidents_acl    = $show_incidents != MENU_HIDDEN;
	$escalate_acl     = false;
	$kb_acl           = false;
	$download_acl     = false;
} else {
	$incidents_acl    = give_acl($config["id_user"], 0, "IR") && $show_incidents != MENU_HIDDEN;
	$escalate_acl     = give_acl($config["id_user"], 0, "SI") && $show_incidents != MENU_HIDDEN;
	$kb_acl           = give_acl($config["id_user"], 0, "KR") && $show_kb != MENU_HIDDEN;
	$download_acl     = give_acl($config["id_user"], 0, "FRR");

}

$incidents_link        = 'index.php?sec=incidents&sec2=operation/incidents/incident_dashboard';
$escalate_tickets_link = 'index.php?sec=incidents&sec2=operation/incidents/incident_detail';
$kb_link               = 'index.php?sec=kb&sec2=operation/kb/browse';
$download_link         = 'index.php?sec=download&sec2=operation/download/browse&show_types=1';


$support_link = 'javascript:';
if ($incidents_acl) {
	$support_link = $incidents_link;
}
else if ($escalate_acl) {
	$support_link = $escalate_tickets_link;
}
else if ($kb_acl) {
	$support_link = $kb_link;
}
else if ($download_acl) {
	$support_link = $download_link;
}

// Support
if ($incidents_acl || $kb_acl || $download_acl || $escalate_acl) {
    // Incident
    if ($sec == "incidents" || $sec == "download" || $sec == "kb")
	    echo "<li id='current' class='support'>";
    else
	    echo "<li class='support'>";
	echo "<div>|</div>";
	echo "<a href='" . $support_link . "'>".__('Support')."</a>";
	
	echo '<ul class="submenu support_submenu">';
	// Incidents
	if ($incidents_acl){
		// Incident
		if ($sec == "incidents" )
			echo "<li id='current' class='incident'>";
		else
			echo "<li class='incident'>";
		echo "<a href='" . $incidents_link . "'>".__('Tickets')."</a></li>";
	}
	// KB
	if ($kb_acl) {
		if ($sec == "kb" )
			echo "<li id='current' class='kb'>";
		else
			echo "<li class='kb'>";
		echo "<a href='" . $kb_link . "'>".__('KB')."</a></li>";
	}
	// FILE RELEASES
	if ($download_acl) {
		if ($show_file_releases != MENU_HIDDEN) {
			// File Releases
			if ($sec == "download" )
				echo "<li id='current' class='files'>";
			else
				echo "<li class='files'>";
			echo "<a href='" . $download_link . "'>".__('File Releases')."</a></li>";
		}
	}

	echo '</ul>';
	echo "</li>";
	
}

// Inventory
if (give_acl($config["id_user"], 0, "VR") && (get_standalone_user($config["id_user"]) == false) && $show_inventory != MENU_HIDDEN) {
    if ($sec == "inventory" )
	    echo "<li id='current' class='inventory'>";
    else
	    echo "<li class='inventory'>";
    echo "<div>|</div>";
    echo "<a href='index.php?sec=inventory&sec2=operation/inventories/inventory'>".__('Inventory')."</a></li>";
}

enterprise_include("include/functions_reporting.php", true);
enterprise_hook("enterprise_main_menu_reports", array($show_reports, $sec));

// Customers

if ((give_acl($config["id_user"], 0, "CR") || (give_acl($config["id_user"], 0, "CN"))) && (get_standalone_user($config["id_user"]) == false) && $show_customers != MENU_HIDDEN) {
    if ($sec == "customers" )
	    echo "<li id='current' class='customer'>";
    else
	    echo "<li class='customer'>";
    echo "<div>|</div>";
    if (give_acl($config["id_user"], 0, "CR"))
    	echo "<a href='index.php?sec=customers&sec2=operation/companies/company_detail'>".__('Customers')."</a></li>";
}

if (($show_people != MENU_HIDDEN) && (get_standalone_user($config["id_user"]) == false)) {
	// Users
	if ($sec == "users" )
		echo "<li id='current' class='people'>";
	else
		echo "<li class='people'>";
	echo "<div>|</div>";
	echo "<a href='index.php?sec=users&sec2=operation/user_report/report_monthly'>".__('People')."</a></li>";
}

// Wiki
if (give_acl($config["id_user"], 0, "WR") && $show_wiki != MENU_HIDDEN && (get_standalone_user($config["id_user"]) == false)) {
	// Wiki
	if ($sec == "wiki" )
		echo "<li id='current' class='wiki'>";
	else
		echo "<li class='wiki'>";
	echo "<div>|</div>";
	echo "<a href='index.php?sec=wiki&sec2=operation/wiki/wiki'>" . __('Wiki') . "</a>";
	echo "<div>|</div></li>";
}

// Custom Screens
if (((int)enterprise_include('custom_screens/CustomScreensManager.php', true) != ENTERPRISE_NOT_HOOK) && (get_standalone_user($config["id_user"]) == false)) {
	$custom_screens = CustomScreensManager::getInstance()->getCustomScreensList(false);

	if (!empty($custom_screens)) {
		
		$custom_link = '';
		foreach ($custom_screens as $custom_screen_id => $custom_screen) {
			if (isset($custom_screen['menuEnabled']) && (bool) $custom_screen['menuEnabled']) {
				//First custom screen
				$custom_link = 'index.php?sec=custom_screen-'.$custom_screen_id.'&sec2=enterprise/operation/custom_screens/custom_screens&id='.$custom_screen_id;
				break;
			}
		}
		if (!empty($custom_link)) {
			if ($sec == "custom_screen" )
				echo "<li id='current' class='custom_screen'>";
			else
				echo "<li class='custom_screen'>";
			echo "<a href='" . $custom_link . "'>".__('Custom screens')."</a>";
			
			echo '<ul class="submenu custom_submenu">';
			if ((int)enterprise_include('custom_screens/CustomScreensManager.php', true) != ENTERPRISE_NOT_HOOK) {
				$custom_screens = CustomScreensManager::getInstance()->getCustomScreensList(false);
				if (!empty($custom_screens)) {
					foreach ($custom_screens as $custom_screen_id => $custom_screen) {
						if (isset($custom_screen['menuEnabled']) && (bool) $custom_screen['menuEnabled']) {
							if ($sec == "custom_screen-$custom_screen_id")
								echo "<li id='current' class='custom_screen'>";
							else
								echo "<li class='custom_screen'>";
								
							$len = strlen($custom_screen['name']);
							if ($len <= 12) {
								$str_custom_name = $custom_screen['name'];
								$title = "";
							} else {
								$str_custom_name = substr($custom_screen['name'], 0, 9)."...";
								$title = "title='" . $custom_screen['name'] . "'";
							}
							echo "<a href='index.php?sec=custom_screen-$custom_screen_id&sec2=enterprise/operation/custom_screens/custom_screens&id=$custom_screen_id' $title>" . $str_custom_name . "</a></li>";
						}
					}
				}
			}
			echo '</ul>';
			echo "</li>";
		}
	}
	
}

echo "</ul>";

?>
<script type="text/javascript">	
var wizard_tab_showed = 0;
var screen_tab_showed = 0;
var last_id = false;

/* <![CDATA[ */
$(document).ready (function () {
	var total_width = $("div#main").width();
	var less_width = total_width - 200;
	var percent = Math.round((less_width * 100)/total_width) - 2;
	
	$("div.divresult").css('width', percent+'%');
	
	// Control the tab and subtab hover. When mouse leave one, 
	// check if is hover the other before hide the subtab
	$('li.support a').hover(agent_wizard_tab_show, agent_wizard_tab_hide);
	
	$('.support_submenu').hover(agent_wizard_tab_show, agent_wizard_tab_hide);
	
	$('li.custom_screen a').hover(custom_screen_tab_show, custom_screen_tab_hide);
	
	$('.custom_submenu').hover(custom_screen_tab_show, custom_screen_tab_hide);
	
	$('#menu_slide').click(function(element) {
		var id = element.target.id;
		var status = $("#"+id).data("status");
		if (status == 'closed') {
			if (last_id != false) {
				$("#"+last_id+" ul").hide();
				$("#"+last_id).data("status", "closed");
			}
			
			$("#"+id+" ul").show();
			$("#"+id).data("status", "open");
			
			//autofocus tickets
			if(id=='sideselticket'){
				$("#text-id").focus();
			}
			else if (id=='ticket'){
				$("#text-id").focus();
			}
			
			//autofocus inventory 
			if(id=='sideselinventario'){
				$("#text-id").focus();
			}
			else if (id=='inventario'){
				$("#text-id").focus();
			}

			last_id = id;
		}
		if (status == 'open') {
			$("#"+id+" ul").hide();
			$("#"+id).data("status", "closed");
		}
	});
	
	$('#menu_slide2').click(function(element) {
		
		var id = element.target.id;
		var status = $("#"+id).data("status");
		if (status == 'closed') {
			if (last_id != false) {
				$("#"+last_id+" ul").hide();
				$("#"+last_id).data("status", "closed");
			}
			
			$("#"+id+" ul").show();
			$("#"+id).data("status", "open");
			last_id = id;
		}
		if (status == 'open') {
			$("#"+id+" ul").hide();
			$("#"+id).data("status", "closed");
		}
	});
	
	$('div#main').click(function() {
		
		if (last_id != false) {
			$("#"+last_id+" ul").hide();
		}
	});
	$('div#header').click(function() {
		
		if (last_id != false) {
			$("#"+last_id+" ul").hide();
		}
	});
	
});

// Set the position and width of the subtab
function agent_wizard_tab_setup() {
	/*
		$('.support_submenu').css('left', $('li.support a').offset().left - $('#wrap').offset().left)
		$('.support_submenu').css('top', $('li.support a').offset().top + $('li.support a').height() + 12)
		$('.support_submenu').css('width', $('li.support a').width() + 50);
	*/
}

function agent_wizard_tab_show() {
	agent_wizard_tab_setup();
	wizard_tab_showed = wizard_tab_showed + 1;
	
	if(wizard_tab_showed == 1) {
		$('.support_submenu').show("fast");
	}
}

function agent_wizard_tab_hide() {
	wizard_tab_showed = wizard_tab_showed - 1;

	setTimeout(function() {
		if(wizard_tab_showed <= 0) {
			$('.support_submenu').hide("fast");
		}
	},500);
}

$(window).resize(function() {
	//agent_wizard_tab_setup();
	//custom_screen_tab_setup();
	var total_width = $("div#main").width();
	var less_width = total_width - 200;
	var percent = Math.round((less_width * 100)/total_width) - 2;
	
	$("div.divresult").css('width', percent+'%');
});

// Set the position and width of the subtab
function custom_screen_tab_setup() {		
	/*
	$('.custom_submenu').css('left', $('li.custom_screen a').offset().left - $('#wrap').offset().left)
	$('.custom_submenu').css('top', $('li.custom_screen a').offset().top + $('li.custom_screen a').height() + 12)
	$('.custom_submenu').css('width', $('li.custom_screen a').width() + 6);
	*/
}

function custom_screen_tab_show() {
	custom_screen_tab_setup();
	screen_tab_showed = screen_tab_showed + 1;
	
	if(screen_tab_showed == 1) {
		$('.custom_submenu').show("fast");
	}
}

function custom_screen_tab_hide() {
	screen_tab_showed = screen_tab_showed - 1;
	
	setTimeout(function() {
		if(screen_tab_showed <= 0) {
			$('.custom_submenu').hide("fast");
		}
	},500);
}

/* ]]> */
</script>


