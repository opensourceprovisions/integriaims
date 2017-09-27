<?php
// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


check_login ();

/* This page only works on AJAX */
if (! defined ('AJAX'))
	return;

$search_text = (string) get_parameter ('search_text');
$id_company = (int) get_parameter ('id_company');
$search_contact = (bool) get_parameter ('search_contact');

if ($search_contact) {
	$where_clause = "WHERE 1=1";
	if ($search_text != "") {
		$where_clause .= sprintf (' AND fullname LIKE "%%%s%%"', $search_text);
	}

	if ($id_company) {
		$where_clause .= sprintf (' AND id_company = %d', $id_company);
	}
	
	$sql = "SELECT * FROM tcompany_contact $where_clause ORDER BY id_company, fullname";
	$contacts = get_db_all_rows_sql ($sql);
	$companies = get_companies ();
	foreach ($contacts as $contact) {
		echo '<tr id="result-'.$contact['id'].'">';
		echo '<td><strong>'.$contact['fullname'].'</strong></td>';
		if (isset($companies[$contact['id_company']]))
			echo '<td>'.$companies[$contact['id_company']].'</td>';
		else	
			echo '<td></td>';
	}
	
	return;
}

$table->width = '90%';
$table->class = 'search-table';
$table->style = array ();
$table->style[0] = 'font-weight: bold;';
$table->data = array ();
$table->data[0][0] = print_input_text ("search_text", $search_text, "", 15, 100, true, __('Search'));
$table->data[0][1] = print_select (get_companies (), 'id_company', $id_company, '', 'All', 0, true, false, false, __('Company'));
$table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);;

echo '<div id="contact_search_result" style="display:none"></div>';

echo '<form id="contact_search_form" method="post">';
print_table ($table);
print_input_hidden ('search_contact', 1);
echo '</form>';

unset ($table);

$table->width = '90%';
$table->class = 'result_table listing';
$table->id = 'contact_search_result_table';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Company');

print_table ($table);

print_table_pager ('contact-pager', true);

?>
