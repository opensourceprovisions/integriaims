<?php

// INTEGRIA IMS
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2012 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

/* Function that includes all graph dependencies */
function include_graphs_dependencies($homeurl = '') {
	include_once($homeurl . 'include/graphs/functions_fsgraph.php');
	include_once($homeurl . 'include/graphs/functions_gd.php');
	include_once($homeurl . 'include/graphs/functions_utils.php');
}
	
?>
