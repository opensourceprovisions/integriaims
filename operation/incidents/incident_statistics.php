<?php

// Integria IMS - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2011-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

check_login ();

$filter['limit'] = 0;
$incidents = filter_incidents ($filter);

unset($filter['limit']);

/* Add a form to carry filter between statistics and search views */
echo '<form id="search_form" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_search&option=search" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}
print_input_hidden ("offset", get_parameter("offset"));
echo "</form>";

/* Add a form to generate HTML reports */
echo '<form id="html_report_form" method="post" target="_blank" action="index.php" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}

print_input_hidden ('sec2', 'operation/reporting/incidents_html');
print_input_hidden ('clean_output', 1);
echo "</form>";

/* Add a form to generate HTML reports */
echo '<form id="pdf_report_form" method="post" target="_blank" action="index.php" style="clear: both">';
foreach ($filter as $key => $value) {
	print_input_hidden ("search_".$key, $value);
}

print_input_hidden ('sec2', 'operation/reporting/incidents_html');
print_input_hidden ('clean_output', 1);
print_input_hidden ('pdf_output', 1);
echo '</form>';

if ($incidents == false) {
	echo ui_print_error_message (__('Nothing was found'), '', true, 'h3', true); 

} else {
	$simple_mode = true;
	if ($show_stats) {
		$simple_mode = false;
	}
	print_incidents_stats_simply ($incidents, false, $simple_mode);
}

?>
