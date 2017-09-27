<?php
// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008-2013 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

	global $config;

	check_login ();

	// TODO: Implement ACL check !
	$search_text = (string) get_parameter ('search_text');	
	$search_role = (int) get_parameter ("search_role");
	$search_country = (string) get_parameter ("search_country");
	$search_manager = (string) get_parameter ("search_manager");

	$where_clause = " 1 = 1 AND tcompany.id " . get_filter_by_company_accessibility($config["id_user"]);

	if ($search_text != "") {
		$where_clause .= sprintf (' AND name LIKE "%%%s%%" ', $search_text);
	}

	if ($search_role != 0){ 
		$where_clause .= sprintf (' AND id_company_role = %d', $search_role);
	}

	if ($search_country != ""){ 
		$where_clause .= sprintf (' AND country LIKE "%%s%%" ', $search_country);
	}

	if ($search_manager != ""){ 
		$where_clause .= sprintf (' AND manager = "%s" ', $search_manager);
	}

	$params = "&search_manager=$search_manager&search_text=$search_text&search_role=$search_role&search_country=$search_country";

	$filename = clean_output ('company_export').'-'.date ("YmdHi");

	ob_end_clean();

	// CSV Output

	header ('Content-Disposition: attachment; filename="'.$filename.'.csv"');
	header ('Content-Type: text/css; charset=utf-8');

	$config['mysql_result_type'] = MYSQL_ASSOC;

	$sql = "SELECT tcompany.id, tcompany.name as company_name, address, fiscal_id, country, website, comments, tcompany_role.name as role, manager FROM tcompany, tcompany_role WHERE $where_clause AND tcompany_role.id = tcompany.id_company_role ORDER BY tcompany.name DESC";

	$rows = get_db_all_rows_sql (clean_output ($sql));
	if ($rows === false)
		return;
	
	// Header
	echo safe_output (implode (',', array_keys ($rows[0])))."\n";
	
	// Item / data
	foreach ($rows as $row) {

		// Delete \r !!!
 		$row = str_replace ("&#x0d;", " ",  $row);

 		// Delete \n !!
		$row = str_replace ("&#x0a;", " ",  $row);

		// Delete , !!	
		$row = str_replace (",", " ",  $row);

		$buffer = safe_output (implode (',', $row))."\n";
		// Delete " !!!

 		$buffer = str_replace ('"', " ",  $buffer);

		// Delete ' !!!
 		$buffer = str_replace ("'", " ",  $buffer);

 		echo $buffer;
	}
    exit;

?>
