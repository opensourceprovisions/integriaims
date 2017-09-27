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

class AdminPages
{
	var $version = "1.0";
	
	var $desc = array(
		array("AdminPages", "none")
	);
	
	function AdminPages() {
		$this->desc = array(
			array("<a href=\"$self" ."action=AdminPages\" rel=\"nofollow\">AdminPages</a>", "provides admin frontend to delete and other actions in the pages.")
		);
	}
	
	function template()
	{
		global $self;
		global $html;
		global $translationAdminPages;
		
		if (!isset($translationAdminPages)) {
			$translation = 'Admin Pages';
		}
		
		$html = template_replace("plugin:ADMINPAGES", "<a href='$self". "action=admin_pages'>$translationAdminPages</a>", $html);
	}
	
	function action($action) {
		global $CON;
		global $TITLE;
		global $execute_actions;
		global $translation_strings;
		global $PG_DIR, $HIST_DIR;
		global $self;
		global $page;
		global $config;
		
		$is_enterprise = false;
		if (file_exists ("enterprise/include/functions_wiki.php")) {
			require_once ("enterprise/include/functions_wiki.php");
			$is_enterprise = true;
		}
		
		if (!isset($translation_strings)) {
			$title_text = 'Admin Pages';
			$delete_text = 'Delete';
			$correct_text = 'Correct delete page ';
			$incorrect_text = 'Incorrect delete page ';
		}
		else {
			$title_text = $translation_strings['title_text'];
			$delete_text = $translation_strings['delete_text'];
			$correct_text = $translation_strings['correct_text'];
			$incorrect_text = $translation_strings['incorrect_text'];
		}
		
		if ($execute_actions) {
			switch($action) {
				case 'delete_page':
					$correct = unlink("$PG_DIR$page.txt");
					if ($correct) {
						rrmdir($HIST_DIR.$page);
						plugin('action', 'regenerate-tags');
					}
					
					if ($correct) {
						$CON = $correct_text . $page;
					}
					else {
						$CON = $incorrect_text . $page;
					}
				case 'admin_pages':
					$TITLE = $title_text;
					
					for ($files = array(), $dir = opendir($PG_DIR); $f = readdir($dir);)
						if (substr($f, -4) == '.txt')
								$files[] = substr($f, 0, -4);
				
					sort($files);
				
					foreach ($files as $f) {
						
						$has_permission_read = true;
						if ($is_enterprise) {
							$has_permission_read =  wiki_get_write_acl ($config['id_user'], $f);
							if (!$has_permission_read) {
								$has_permission_read =  wiki_get_read_acl ($config['id_user'], $f);
							}
							if ($has_permission_read) {
								$list .= "<li><a href=\"$self" . "page=".u($f).'&amp;redirect=no">'.h($f)."</a>";
							}
						} else {
							$list .= "<li><a href=\"$self" . "page=".u($f).'&amp;redirect=no">'.h($f)."</a>";
						}
						
						$has_permission_write = true;
						if ($is_enterprise) {
							$has_permission_write =  wiki_get_write_acl ($config['id_user'], $f);
							
							if ($has_permission_write) {
								$list .= " (<a href='$self" . "page=".u($f)."&action=delete_page'>$delete_text</a>)</li>";
							}
						} else {
							$list .= " (<a href='$self" . "page=".u($f)."&action=delete_page'>$delete_text</a>)</li>";
						}
							
					}
				
					$CON .= "<ul>$list</ul>";
				
					return true;
					break;
			}
		}
	}
}

/*
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
				else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
} 
*/
?>
