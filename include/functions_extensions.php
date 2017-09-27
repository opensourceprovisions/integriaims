<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


$extension_file = '';


/**
 * Scan the EXTENSIONS_DIR or ENTERPRISE_DIR.'/'.EXTENSIONS_DIR for search
 * the files extensions.
 *
 * @param bool $enterprise
 */
function extensions_get_extensions ($enterprise = false) {
	$dir = EXTENSIONS_DIR;
	$handle = false;
	if ($enterprise)
		$dir = ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;

	if (file_exists ($dir))
		$handle = @opendir ($dir);	
	
	if (empty ($handle))
		return;
		
	$file = readdir ($handle);
	$extensions = array ();
	$ignores = array ('.', '..');
	while ($file !== false) {
		if (in_array ($file, $ignores)) {
			$file = readdir ($handle);
			continue;
		}
		$filepath = realpath ($dir."/".$file);
		if (! is_readable ($filepath) || is_dir ($filepath) || ! preg_match ("/.*\.php$/", $filepath)) {
			$file = readdir ($handle);
			continue;
		}
		$extension['file'] = $file;
		$extension['side_menu'] = '';
		$extension['godmode_side_menu'] = '';
		$extension['tab'] = '';
		$extension['main_function'] = '';
		$extension['godmode_function'] = '';
		$extension['tab_function'] = '';
		$extension['enterprise'] = $enterprise;
		$extension['dir'] = $dir;
		$extensions[$file] = $extension;
		$file = readdir ($handle);
	}
	
	/* Load extensions in enterprise directory */
	if (! $enterprise && file_exists (ENTERPRISE_DIR.'/'.EXTENSIONS_DIR))
		return array_merge ($extensions, extensions_get_extensions (true));
	
	return $extensions;
}


/**
 * Load all extensions 
 *
 * @param array $extensions
 */
function extensions_load_extensions ($extensions) {
	global $config;
	global $extension_file;
	
	foreach ($extensions as $extension) {
		$extension_file = $extension['file'];
		require_once (realpath ($extension['dir'] . "/" . $extension_file));
	}
}


/**
 * Checks if the current page is an extension 
 *
 * @param string $page To check
 */
function extensions_is_extension ($page) {
	global $config;
	
	$filename = basename ($page);
	return isset ($config['extensions'][$filename]);
}


/**
 * This function adds a link to the extension with the given name in side menu.
 *
 * @param string name Name of the extension in the Operation menu
 * @param string sec Section where the extension should appear
 */
function extensions_add_side_menu_option ($name, $sec) {
	global $config;
	global $extension_file;
	
	/*
	$config['extension_file'] is set in extensions_load_extensions(),
	since that function must be called before any function the extension
	call, we are sure it will be set.
	*/
	
	$extension = &$config['extensions'][$extension_file];
	
	$option_side_menu['name'] = $name;
	$option_side_menu['sec'] = $sec;
	$option_side_menu['sec2'] = $extension['dir'] . '/' . mb_substr ($extension_file, 0, -4);
	
	$extension['side_menu'] = $option_side_menu;
}


/**
 * This function adds a link to the extension with the given name in Godmode menu.
 *
 * @param string name Name of the extension in the Godmode menu
 */
function extensions_add_godmode_side_menu_option ($name) {
	global $config;
	global $extension_file;
	
	/*
	$config['extension_file'] is set in extensions_load_extensions(),
	since that function must be called before any function the extension
	call, we are sure it will be set. */
	$option_side_menu['name'] = $name;
	$extension = &$config['extensions'][$extension_file];
	$option_side_menu['sec'] = 'godmode';
	$option_side_menu['sec2'] = $extension['dir'] . '/' . mb_substr ($extension_file, 0, -4);
	$extension['godmode_side_menu'] = $option_side_menu;
}


/**
 * Add a extension to a tab list
 * 
 * @param tabId Id of the extension tab
 * @param tabName Name of the extension tab
 * @param sec2 Section where the extension will appear
 * @param tabIcon Path to the image icon from the extensions dir
 * @param tabsId In case of the page has more than one tab list, you should specify its id
 */
function extensions_add_tab_option ($tabId, $tabName, $sec2, $tabIcon = "", $tabsId = "") {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$option_side_menu['id'] = $tabId;
	$option_side_menu['name'] = $tabName;
	$option_side_menu['sec2'] = $sec2;
	if ($extension['enterprise']) {
		$tabIcon = ENTERPRISE_DIR.'/'.EXTENSIONS_DIR.'/'.$tabIcon;
	} else {
		$tabIcon = EXTENSIONS_DIR.'/'.$tabIcon;
	}
	$option_side_menu['icon'] = $tabIcon;
	$option_side_menu['tabsId'] = $tabsId;
	$extension['tab'] = $option_side_menu;
}


/**
 * Add the function to call when user clicks on the Operation menu link
 *
 * @param string $function_name Callback function name
 */
function extensions_add_main_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['main_function'] = $function_name;
}


/**
 * Callback function for extensions in the console 
 *
 * @param string $filename with contents of the extension
 */
function extensions_call_main_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['main_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['main_function'], $params);
	}
}


/**
 * Add the function to call when user clicks on the godmode side menu link
 *
 * @param string $function_name Callback function name
 */
function extensions_add_godmode_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['godmode_function'] = $function_name;
}


/**
 * Callback function for godmode extensions
 *
 * @param string $filename File with extension contents
 */
function extensions_call_godmode_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['godmode_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['godmode_function'], $params);
	}
}


/**
 * Add the function to call when user clicks on the tab extension
 *
 * @param string $function_name Callback function name
 */
function extensions_add_tab_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['tab_function'] = $function_name;
}


/**
 * Callback function for tab extensions
 *
 * @param tabId Id of the extension tab
 * @param sec2 Section where the extension will appear
 * @param tabsId In case of the page has more than one tab list, you should specify its id
 */
function extensions_call_tab_function ($tabId, $sec2, $tabsId = "") {
	global $config;
	
	$extensions = &$config['extensions'];
	foreach ($extensions as $extension) {
		if ($extension['tab'] != '') {
			if ($extension['tab']['id'] == $tabId && $extension['tab']['sec2'] == $sec2) {
				if ($extension['tab_function'] != '') {
					$params = array ();
					call_user_func_array ($extension['tab_function'], $params);
				}
			}
		}
	}
}


/**
 * Print a block of the section extensions for the side menu
 *
 * @param string $sec
 * @param string $sec2
 */
function extensions_print_side_menu_subsection ($sec, $sec2) {
	global $config;
	global $show_projects, $show_incidents, $show_inventory, 
			$show_reports, $show_customers, $show_kb, 
			$show_file_releases, $show_people, $show_todo, 
			$show_agenda, $show_setup, $show_wiki;
	
	switch ($sec) {
		case "projects":
			$show_subsection = $show_projects != MENU_HIDDEN;
			break;
		case "incidents":
			$show_subsection = $show_incidents != MENU_HIDDEN;
			break;
		case "inventory":
			$show_subsection = $show_inventory != MENU_HIDDEN;
		case "reports":
			$show_subsection = $show_reports != MENU_HIDDEN;
			break;
		case "customers":
			$show_subsection = $show_customers != MENU_HIDDEN;
			break;
		case "kb":
			$show_subsection = $show_kb != MENU_HIDDEN;
			break;
		case "download":
			$show_subsection = $show_file_releases != MENU_HIDDEN;
			break;
		case "godmode":
			$show_subsection = $show_setup != MENU_HIDDEN;
			break;
		case "users":
			$show_subsection = $show_people != MENU_HIDDEN;
			break;
		case "wiki":
			$show_subsection = $show_wiki != MENU_HIDDEN;
			break;
		default:
			$show_subsection = false;
	}
	
	if (is_array ($config["extensions"]) && $show_subsection) {
		
		$has_extensions = false;
		
		foreach ($config["extensions"] as $extension) {
			
			if ($extension["side_menu"] == '' && $extension["godmode_side_menu"] == '') {
				continue;
			}
			if ($sec == "godmode" && $sec == $extension["godmode_side_menu"]["sec"]) {
				$has_extensions = true;
				
				if ($sec2 == $extension["godmode_side_menu"]["sec2"])
					$content .= "<li id='sidesel'>";
				else
					$content .= "<li>";
				$content .= "<a href='index.php?sec=".$extension["godmode_side_menu"]["sec"]."&sec2=".$extension["godmode_side_menu"]["sec2"]."'>"
					.$extension["godmode_side_menu"]["name"]."</a></li>";
				
			} else if ($sec == $extension["side_menu"]["sec"]) {
				$has_extensions = true;
				
				if ($sec2 == $extension["side_menu"]["sec2"])
					$content .= "<li id='sidesel'>";
				else
					$content .= "<li>";
				$content .= "<a href='index.php?sec=".$extension["side_menu"]["sec"]."&sec2=".$extension["side_menu"]["sec2"]."'>"
					.$extension["side_menu"]["name"]."</a></li>";
			}
		}
		
		if ($has_extensions) {
			echo "<div class='portlet'>";
				echo "<h3>".__('Extensions')."</h3>";
				echo "<ul class='sidemenu'>";
					echo $content;
				echo "</ul>";
			echo "</div>";
		}
	}
}


/**
 * Get tab extensions
 *
 * @param sec2 Section where the extension will appear
 * @param tabsId In case of the page has more than one tab list, you should specify its id
 */
function get_tab_extensions ($sec2, $tabsId = "") {
	global $config;
	
	$extension_tabs = array();
	$extensions = &$config['extensions'];
	foreach ($extensions as $extension) {
		if ($extension['tab'] != '') {
			if ($extension['tab']['sec2'] == $sec2) {
				if ($extension['tab']['tabsId'] == '' || ($extension['tab']['tabsId'] != '' && $extension['tab']['tabsId'] == $tabsId)) {
					$extension_tabs[] = $extension;
				}
			}
		}
	}
	
	return $extension_tabs;
}
?>
