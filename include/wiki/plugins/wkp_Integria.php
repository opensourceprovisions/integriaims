<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Integria
{
	var $version = "1.0";
	
	var $desc = array(
		array("Integria plugin", "none")
	);
	
	function Integria() {
		$this->desc = array(
			array("<a href=\"$self" ."action=integria\" rel=\"nofollow\">Integria</a>", "provides integration with Integria IMS.")
		);
	}
	
	function check_no_read() {
		global $config;
		global $id_grupo;
		
		if (!give_acl ($config['id_user'], $id_grupo, "WR")) {
			return true;
		}
		else{
			return false;
		}
	}
	
	function check_no_save() {
		global $config;
		global $id_grupo;
		
		if (!give_acl ($config['id_user'], $id_grupo, "Ww")) {
			return true;
		}
		else{
			return false;
		}
	}
	
	function check_no_action($action) {
		global $config, $id_grupo;
		
		if ($action == 'upload') {
			if (!give_acl ($config['id_user'], $id_grupo, "Ww")) {
				return true;
			}
			else{
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	function check_no_new_page() {
		global $config;
		global $id_grupo;
		
		if (!give_acl ($config['id_user'], $id_grupo, "WM")) {
			return true;
		}
		else{
			return false;
		}
	}
	
	function show_message_no_new_page() {
		global $execute_actions;
		global $CON;
		
		if ($execute_actions) {
			ob_start();
				include ("general/noaccess.php");
			$html = ob_get_clean();
			$CON = $html;
			
			return true;
		}
	}
	
	function show_message_no_save() {
		global $execute_actions;
		global $CON;
		
		if ($execute_actions) {
			ob_start();
				include ("general/noaccess.php");
			$html = ob_get_clean();
			$CON = $html;
			
			return true;
		}
	}
	
	function show_message_no_read() {
		global $execute_actions;
		global $CON;
		global $config;
		global $id_grupo;
		
		if ($execute_actions) {
			ob_start();
				include ("general/noaccess.php");
			$html = ob_get_clean();
			$CON = $html;
			
			return true;
		}
		
		return false;
	}
	
	function loadMetadata($dir_name, $metadata_lionwiki) {
		global $HIST_DIR;
		global $return_loadMetadata;
		
		$es = fopen("$HIST_DIR$dir_name/meta.integria", 'r');
		while (($serialize_data = fgets($es)) !== false) {
			$data = unserialize($serialize_data);
			if ($metadata_lionwiki[0] == $data['time']) {
				$return_loadMetadata = $data['user'];
				break;
			}
		}
		fclose($es);
		
		return false;
	}
	
	function writingPage() {
		global $HIST_DIR;
		global $page;
		global $action;
		global $rightnow;
		global $config;
		
		switch ($action) {
			case 'save':
				// Backup old revision
				@mkdir($HIST_DIR.$page, 0777); // Create directory if does not exist
				
				$es = fopen("$HIST_DIR$page/meta.integria", 'a');
				
				$data = array();
				$data['time'] = $rightnow;
				$data['user'] = $config['id_user'];
				
				fputs ($es, serialize($data) . "\n");
				
				fclose($es);
				break;
		}
		
		return false;
	}
}
?>