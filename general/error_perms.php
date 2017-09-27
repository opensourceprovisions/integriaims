<?php
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

if (is_readable("include/config.php"))
	include "include/config.php";
else {
	$config["language_code"] = "en";
	$config["version"] = "3.0";
	$config["build_version"] = "dev";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>I N T E G R I A - config.php permission errors </title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/integria.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/integria.css" type="text/css">
</head>
<body>
<div align='center'>
<div id='login_f' style='width: 500px; margin-top: 100px; background-color: #ffffff; border: 2px solid #000000; padding: 10px;'>
	<h1 id="log_f" class="error">Bad permission for include/config.php</h1>
	<div>
		<img src="images/integria_white.png" border="0"></a><br><font size="1">
		<?php echo 'Integria '.$config["version"].' Build '.$config["build_version"]; ?>
		</font>
	</div>
	<div class="msg"><br><br>For security reasons, <i>config.php</i> must have restrictive permissions, and "other" users cannot read or write to it. It could be writed only for owner (usually www-data, wwwrun or http daemon user), normal operation is not possible until you change permissions for <i>include/config.php</i>file. Please do it, it's for your security.</div>
</div>
</div>
</body>
</html>
