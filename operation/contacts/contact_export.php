<?php
// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$search_text = (string) get_parameter ('search_text');
$id_company = (int) get_parameter ('id_company');

// Check if current user have access to this company.
if ($id_company) {
	$read = check_crm_acl ('other', 'cr', $config['id_user'], $id_company);
	if (!$read) {
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to contact export");
		include ("general/noaccess.php");
		exit;
	}
}

$where_clause = "WHERE tcompany_contact.id_company = tcompany.id $where_group "
	. "AND tcompany_contact.id_company ". get_filter_by_company_accessibility($config["id_user"]);
if ($search_text != "") {
	$where_clause .= sprintf (' AND fullname LIKE "%%%s%%"', $search_text);
}
if ($id_company) {
	$where_clause .= sprintf (' AND id_company = %d', $id_company);
}

$sql = "SELECT tcompany_contact.fullname, tcompany.name as company_name, 
tcompany_contact.email, tcompany_contact.phone, tcompany_contact.mobile, 
tcompany_contact.position, tcompany_contact.description FROM tcompany_contact, 
tcompany $where_clause ORDER BY id_company, fullname";

$filename = clean_output ('contacts_export').'-'.date ("YmdHi");

ob_end_clean();

// CSV Output

header ('Content-Disposition: attachment; filename="'.$filename.'.csv"');
header ('Content-Type: text/css; charset=utf-8');

$config['mysql_result_type'] = MYSQL_ASSOC;

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
