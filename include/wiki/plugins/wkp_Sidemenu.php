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

class Sidemenu
{
	var $version = "1.0";
	
	var $desc = array(
		array("Sidemenu", "none")
	);
	
	function AdminPages() {
		$this->desc = array(
			array("<a href=\"$self" ."action=Sidemenu\" rel=\"nofollow\">Sidemenu</a>", "provides a page for the edit the content of sidemenu.")
		);
	}
	
	function template()
	{
		global $html;
		global $PG_DIR;
		
		$side_menu = '';
		if (file_exists($PG_DIR . "side_menu.txt")) {
			$side_menu = file_get_contents($PG_DIR . "side_menu.txt");
		}
		else {
			$file = @fopen($PG_DIR . "side_menu.txt", 'w');
			fwrite($file, ''); fclose($file);
		}
		
		$html = template_replace("plugin:SIDEMENU", $side_menu, $html);
	}
}
?>