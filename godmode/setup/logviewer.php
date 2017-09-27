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


global $config;
check_login();

if (give_acl($config["id_user"], 0, "FM")==0) {
    audit_db($config["id_user"],$config["REMOTE_ADDR"], "ACL Violation","Trying to access log viewer");
    require ("general/noaccess.php");
    exit;
}

$file_name = $config["homedir"]."integria.log";


$delete = get_parameter ("delete", 0);
if ($delete == 1){
	if (file_exists($file_name)){
		unlink ($file_name);
	}
}

echo "<h2>" . __("Error log") . "</h2>";
echo "<h4>" . __("View file") . "</h4>";

if (!file_exists($file_name)){
	echo "<h2 class='error'>".__("Log file is empty "). "(".$file_name.")</h2>";
}
else {
	$filesize = filesize($file_name);
	
	if ($filesize < 4000) { // If size is less than 4000, shows all the log file
		$offset = ceil($filesize / 4);
	}
	else {
		$offset = ceil(($filesize / 4 ) * 3); // Read only from the 3/4 part of file
	}
	$data = file_get_contents ($file_name, NULL, NULL, $offset);

	echo "<div class='under_tabs_info'>$file_name ".__("Reading from byte"). " " .$offset ."</div><br>";
	echo "<div style='width: 100%; text-align: right;'>";
	print_button(__("Delete logfile"), '', false, 'window.open(\'index.php?sec=godmode&sec2=godmode/setup/logviewer&delete=1\')', 'class="sub delete"');
	echo "</div>";
	echo "</br><textarea style='width: 99%; height: 500px;' name='$file_name'>";
	echo $data;
	echo "</textarea><br><br>";
}

?>
