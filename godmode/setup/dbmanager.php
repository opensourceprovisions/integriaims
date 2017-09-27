<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

check_login ();

if (! dame_admin ($config["id_user"])) {
        audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
        require ("general/noaccess.php");
        exit;
}


function dbmanager_query ($sql, &$error) {
	global $config;
	
	$retval = array();

	if ($sql == '')
		return false;
		
	$sql = html_entity_decode($sql, ENT_QUOTES);

	$result = mysql_query ($sql);
	if ($result === false) {
		$error = mysql_error ();
		return false;
	}
	
	if ($result === true) {
		if ($rettype == "insert_id") {
			return mysql_insert_id ();
		} elseif ($rettype == "info") {
			return mysql_info ();
		}
		return mysql_affected_rows ();
	}
	
	while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
		array_push ($retval, $row);
	}
	mysql_free_result ($result);
	
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}


function dbmgr_main () {

	echo '<link rel="stylesheet" href="include/styles/dbmanager.css" type="text/css" />';

	$sql = (string) get_parameter ('sql');
    $clean_output = get_parameter("clean_output", 0);

    if ($clean_output == 0){
	    echo "<h2>".__('Extensions'). "</h2><h4> ".__('Database interface');
	    $html_report_image = print_html_report_image ("index.php?sec=godmode&sec2=godmode/setup/dbmanager&sql=$sql", __("Report"));
		if ($html_report_image) {
			echo "&nbsp;&nbsp;" . $html_report_image;
		}
        
        echo "</h4>";
	    echo '<div class="note_simple">';
	    echo __("This is an advanced extension to interface with Integria IMS database directly using native SQL sentences. Please note that <b>you can damage</b> your Integria IMS installation if you don't know </b>exactly</b> what are you doing, this means that you can severily damage your setup using this extension. This extension is intended to be used <b>only by experienced users</b> with a depth knowledgue of Integria IMS.");
	    echo '</div>';

	    echo "<br />";
	    echo __("Some samples of usage:")." <blockquote><em>SHOW STATUS;<br />DESCRIBE tincidencia<br />SELECT * FROM tincidencia<br />UPDATE tincidencia SET sla_disabled = 1 WHERE inicio < '2010-01-10 00:00:00';</em></blockquote>";


	    echo "<br /><br />";
	    echo "<form method='post' action=''>";
	    print_textarea ('sql', 5, 50, html_entity_decode($sql, ENT_QUOTES));
        echo "<div class='button-form'>";
	    print_submit_button (__('Execute SQL'), '', false, 'class="sub next"');
	    echo "</div>";
	    echo "</form>";
    } else {
        echo "<form method='post' action=''>";
	    print_textarea ('sql', 2, 40, html_entity_decode($sql, ENT_QUOTES));
        echo "<div class='button-form'>";
	    print_submit_button (__('Execute SQL'), '', false, 'class="sub next"');
	    echo "</div>";
	    echo "</form>";
    }

	// Processing SQL Code
	if ($sql == '')
		return;
	
	$error = '';
	$result = dbmanager_query ($sql, $error);
	
	if ($result === false) {
		echo '<strong>An error has occured when querying the database.</strong><br />';
		echo $error;
		return;
	}
	
	if (! is_array ($result)) {
		echo "<strong>Output: <strong>".$result;
		return;
	}
	
	$table->width = '100%';
	$table->class = 'dbmanager';
	$table->head = array_keys ($result[0]);
	
	$table->data = $result;
	
	echo "<div style='overflow-x:scroll;'>";
	print_table ($table);
	echo "</div>";
}

dbmgr_main ();

?>
