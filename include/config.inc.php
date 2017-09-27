<?php
//  __         __                        __            __                  
// |__|.-----.|  |_ .-----..-----..----.|__|.---.-.   |__|.--------..-----.
// |  ||     ||   _||  -__||  _  ||   _||  ||  _  |   |  ||        ||__ --|
// |__||__|__||____||_____||___  ||__|  |__||___._|   |__||__|__|__||_____|
//                         |_____|                                         
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas
//  http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/* Sample tokens
$config["dbname"] = "integria";         // MySQL DataBase name
$config["dbuser"] = "integria";
$config["dbpass"] = "integria"; // DB Password
$config["dbhost"] = "localhost"; // DB Host
*/

// Display ALL errors until redirect to integria error log
error_reporting(E_ALL);

$path = dirname (__FILE__);
include ($path."/config_process.php");
?>
