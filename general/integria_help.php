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

?>
<html>
<head>
<meta http-equiv="expires" content="0" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="resource-type" content="document" />
<meta name="distribution" content="global" />
<meta name="author" content="Sancho Lerena <slerena@gmail.com>" />
<meta name="website" content="http://integriaims.com" />
<meta name="copyright" content="Artica Soluciones Tecnologicas (c) 2007-2011" />
<meta name="keywords" content="ticketing, management, project, incident, tracking, ITIL" />
<meta name="robots" content="index, follow" />
<link rel="icon" href="../images/integria_mini_logo.png" type="image/png" />
<link rel="stylesheet" href="../include/styles/integria.css" type="text/css" />
</head>
<body>

<?php

require_once ("../include/config.php");
require_once ("../include/functions.php");

if (!isset($config['style']))
	$config['style'] = "integria";

echo '<html><head><title>'.__('Integria IMS help system').'</title></head>';
echo '<link rel="stylesheet" href="../include/styles/'.$config['style'].'.css" type="text/css">';
echo '<body bgcolor=#ffffff style="background: none">';

$id = get_parameter ('id');
$help_file = $config["homedir"]."/include/help/".$config["language_code"]."/help_".$id.".php";
if (!file_exists ($help_file))
		$help_file = $config["homedir"]."/include/help/en/help_".$id.".php"; 

if (! $id || ! file_exists ($help_file)) {
	echo "<div class='databox' id='login'>";
	echo "<div class='blank'>";
	echo "<div style='text-align:center;' >";
	echo '<h1>';
	echo __('Help system error');
	echo "</h1>";

	echo "<h3>";
	echo __("No help section");
	echo "</h3>";
	
	echo '<div class="msg">'.__('Integria IMS help system has been called with a help reference that currently don\'t exist. There is no help content to show.').'</div>';
	echo "<br><br>";
	echo '<a href="index.php"><img src="../images/integria_small.png" border="0"></a><br>';
	echo "</div>";
	echo "</div>";
	echo "</div>";
	
	return;
}

/* Show help */

echo "<img src='../images/integria_white.png' align=left border=0>";
echo '<h1 style="padding-top:5px; text-align:right; font: bold 1.5em "Trebuchet MS", Arial, Sans-serif;
	text-transform: none; text-align: right">'.__('Integria IMS Help System').'</h1>';
echo "<div style='height:10px'></div>";
echo '<hr width="100%" size="1" />';
echo '<div style="font-family: verdana, arial; font-size: 11px; text-align:left">';
echo '<div style="font-size: 12px; margin-left: 30px; margin-right:25px;">';
echo "<br>";
require_once ($help_file);
echo '</div>';
echo '<br><br>';

include ('footer.php');
?>
</body>
</html>
