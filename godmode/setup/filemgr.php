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

// Upload file
if (isset($_GET["upload_file"])) {
	$file = $_FILES['upfile'];
	
	if ($file["error"] === 0){ //if file
		$filename = $file["name"];
		$file_tmp = $file["tmp_name"];
		$directory = get_parameter ("directory","");

		// Copy file to directory and change name
		$file_target = $config["homedir"]."/".$directory."/".$filename;
		if (!(copy($file_tmp, $file_target))){
			echo ui_print_error_message (__("Could not be attached"), '', true, 'h3', true);
		}
		else {
			// Delete temporal file
			echo ui_print_success_message (__("Successfully attached"), '', true, 'h3', true);
			$location = $file_target;
			unlink ($file_tmp);
		}
		
	}
}

// Delete file
$delete = get_parameter ("delete", "");
if ($delete != ""){
	//echo ui_print_success_message (__("Deleting file"), '', true, 'h3', true);
	$file = get_parameter ("delete", "");
	$directory = get_parameter ("directory", "");

	$full_filename = $directory . "/". $file;
	if (!is_dir ($full_filename)){
		echo ui_print_success_message (__("Deleting")." ".$full_filename, '', true, 'h3', true);
		unlink ($full_filename);
	}
}

echo "<h2>".__("File manager")."</h2>";
echo "<h4>".__("Upload images")."</h4>";

$current_directory = get_parameter ("directory", "/");

// CREATE DIR
// Upload file
if (isset($_GET["create_dir"])) {
	$newdir = get_parameter ("newdir","");
	if ($newdir != ""){
		mkdir($current_directory."/".$newdir);
		echo ui_print_success_message (__("Created directory ") . $newdir, '', true, 'h3', true);
	}

}

// A miminal security check to avoid directory traversal
if (preg_match("/\.\./", $current_directory))
	$current_directory = "images";
if (preg_match("/^\//", $current_directory))
	$current_directory = "images";
if (preg_match("/^manager/", $current_directory))
	$current_directory = "images";

echo "<div class='divform'>";

echo "<form method='post' action='index.php?sec=godmode&sec2=godmode/setup/filemgr'>";
echo "<table width='100%' class='search-table'>";

echo "<tr><td class='datos'>";

$available_directory["images"] = "images";
$available_directory["images/custom_logos"] = "images/custom_logos";
$available_directory["attachment"] = "attachment";
$available_directory["attachment/downloads"] = "attachment/downloads";
// Current directory
$available_directory[$current_directory] = $current_directory;

print_select ($available_directory, 'directory', $current_directory, '', '', '',  false, false, 0, '',"","");
echo "<tr><td class='datos'>";
echo "<input type=submit class='sub next' style='margin-top:12px;' value='".__("Go")."'>";
echo "</table>";
echo "</form>";

if (is_writable($current_directory)) {
	
	echo "<form method='post' action='index.php?sec=godmode&sec2=godmode/setup/filemgr&upload_file=1' enctype='multipart/form-data'>";
	echo "<table width='100%' class='search-table'>";
	echo "<tr><td class='datos'>";
	
	$action = 'index.php?sec=godmode&sec2=godmode/setup/filemgr&upload_file';
	
	echo "<tr><td class='datos'>";
	$into_form = "<input type='hidden' name='directory' value='$current_directory'><input type='file' name='upfile'>";
	echo $into_form;
	
	//print_input_file_progress($action,$into_form,'','sub next',false);
	echo "<tr><td class='datos'>";
	echo "<input type=submit class='sub next' style='margin-top:12px;' value='".__("Upload")."'>";
	echo "</table>";
	echo "</form>";
	
	echo "<form method='post' action='index.php?sec=godmode&sec2=godmode/setup/filemgr&create_dir=1&directory=$current_directory'>";
	echo "<table width='100%' class='search-table'>";
	echo "<tr><td class='datos'>";
	echo __("Create directory");
	echo "<tr><td class='datos'>";
	echo "<input type=text size=15 name='newdir'>";
	echo "<tr><td class='datos'>";
	echo "<input type=submit value='Make dir' class='sub next'>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
}
else {
	echo "<tr><td class='datos'>";
	echo "<input type=submit class='sub next' style='margin-top:12px;' value='".__("Go")."'>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
	echo ui_print_success_message (__('Current directory is not writtable by HTTP Server') . $newdir, '', true, 'h3', true);
	echo '<p>';
	echo __('Please check that current directory has write rights for HTTP server');
	echo "</p>";
}

echo "<div class='divresult' style=''>";
echo "<h4 style='width: 100% !important;'>".__("Current directory"). " : ".$current_directory . " <a href='index.php?sec=godmode&sec2=godmode/setup/filemgr&directory=$current_directory'><img src='images/arrow_refresh.png' border=0 title='" . __('Refresh') . "'></a></h4>";
// Upload form

	// List files
	
	$directoryHandler = "";
	$result = array ();
	if (! $directoryHandler = @opendir ($current_directory)) {
		echo ("<pre>\nerror: directory \"$current_directory\" doesn't exist!\n</pre>\n");
		return 0;
	}
	
	while (false !== ($fileName = @readdir ($directoryHandler))) {
		$result[$fileName] = $fileName;
		// TODO: Read filetype (image, directory)
		//       If directory create a link to navigate.
	}
	asort($result, SORT_STRING);
	
	if (@count ($result) === 0) {
		echo __("No files found");
	}
	else {
		// This code divide the directories to the rest of files
		// to show them at the top
		$dirs = array();
		$nondirs = array();
		array_walk($result, function($value, $key) {
			global $current_directory, $dirs, $nondirs;
			$fullfilename = $current_directory.'/'.$value;
			if (is_dir($fullfilename)) {
				$dirs[$key] = $value;
			} else {
				$nondirs[$key] = $value;
			}
		});
		array_multisort($dirs, $nondirs);
		$result = array_merge($dirs, $nondirs);
		$dirs = null;
		$nondirs = null;
		
		echo "<table width='100%' class='listing' style='width: 100% '>";
		
		$prev_dir = explode( "/", $current_directory );
		$prev_dir_str = "";
		for ($ax = 0; $ax < (count($prev_dir)-1); $ax++){

			$prev_dir_str .= $prev_dir[$ax];
			if ($ax < (count($prev_dir)-2))
				$prev_dir_str .= "/";
		}

		if ($prev_dir_str != ""){
			echo "<tr><td colspan=6>";
			echo "<a href='index.php?sec=godmode&sec2=godmode/setup/filemgr&directory=$prev_dir_str'>".__("Go prev. directory")." <img src='images/go-previous.png' border=0></a>";
			echo "</th></tr>";
		}
		echo "<tr><th>";
		echo __("Filename");
		echo "<th>";
		echo __("Image info");
		echo "<th>";
		echo __("Last update");
		echo "<th>";
		echo __("Owner");
		echo "<th>";
		echo __("Perms");
		echo "<th>";
		echo __("Filesize");
		echo "<th>";
		echo __("File type");
		echo "<th>";
		echo __("Directory");
		echo "<th>";
		echo __("Del");
		while (@count($result) > 0){
			$temp = array_shift ($result);
			$fullfilename = $current_directory.'/'.$temp;

			$mimetype = "none";
			if (function_exists("mime_content_tyep")) {
				$mimetype = mime_content_type($fullfilename);
			}
			else if (function_exists("finfo_open")) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
    				$mimetype = finfo_file($finfo, $fullfilename);
				finfo_close($finfo);	
			}
			if (($temp != "..") AND ($temp != ".")){
				echo "<tr><td>";
				if (!is_dir ($current_directory.'/'.$temp)) {	
					echo "<a href='$fullfilename'>$temp</A>";
				}
				else
					echo "<a href='index.php?sec=godmode&sec2=godmode/setup/filemgr&directory=$current_directory/$temp'>/$temp</a>";

				echo "<td>";
				if (preg_match("/image/", $mimetype)) {
					list($ancho, $altura, $tipo, $atr) = getimagesize($fullfilename);
					echo $ancho." x ".$altura." px";
				}	
				echo "<td>";
				if (!is_dir ($fullfilename))
					echo date("F d Y H:i:s.", filemtime($fullfilename));
				echo "<td>";
				if (!is_dir ($fullfilename))
					echo fileowner($fullfilename);

				echo "<td>";
				if (!is_dir ($fullfilename))
					if (!is_readable($fullfilename))
						echo "<font color=#ff0000>";
					echo __("Read");				

				echo "<td>";
				if (!is_dir ($fullfilename))
					echo filesize($fullfilename);
				else
					echo "&lt;DIR&gt;";

				echo "<td>";
				if (!is_dir ($fullfilename))
					echo $mimetype;
				else
					echo "&lt;DIR&gt;";

				echo "<td align=center>";
				if (!is_dir ($fullfilename))
					echo "<img src='images/disk.png' border=0>";
				else
					echo "<img src='images/drive_network.png' border=0>";
				echo "<td>";
				echo "<a href='index.php?sec=godmode&sec2=godmode/setup/filemgr&directory=$current_directory&delete=$temp'><img src='images/cross.png' border=0></a>";
			}
		}
		echo "</table>";
	}
echo "</div>";

?>

