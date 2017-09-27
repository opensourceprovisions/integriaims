<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-10 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

?>
<center>
<?PHP
global $config;

if ($config["enteprise"] == 1)
	$enterprise = "Enterprise Edition";
else
	$enterprise = "OpenSource Edition";

if (!$config["minor_release"])
	$config["minor_release"] = 0;

//~ echo 'Integria IMS <b>'.$enterprise.' '.$config["version"].' Build '.$config["build_version"].' MR'.$config["minor_release"].' Package '.$config['current_package'].'</b><br>';
echo 'Integria IMS <b>'.$enterprise.' '.$config["version"].' MR'.$config["minor_release"].' Package '.$config['current_package'].'</b><br>';

if (isset($_SESSION['id_usuario'])) {
	
	if(file_exists('general/license/integria_info_'.$config["language_code"].'.html')) {
		$language_info = $config["language_code"];
	}
	else {
		$language_info = 'en';
	}
	
	echo '<a target="_new" href="general/license/integria_info_'.$language_info.'.html">';
	if (isset($_SERVER['REQUEST_TIME'])) {
		$time = $_SERVER['REQUEST_TIME'];
	} else {
		$time = time();
	}
	echo __('Page generated at')." ".date("D F d, Y H:i:s", $time)."<br>";
}
?>
</center>

