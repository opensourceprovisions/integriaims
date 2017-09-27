<?php
// Integria Enterprise
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

define ('FREE_USER', 'INTEGRIA-FREE');



function update_manager_main() {
	global $config;

	echo "<div class='note_simple'>";	
	echo "<p><b>";
	$license = $config['license'];
	if ($license == 'INTEGRIA-FREE') {
		echo "<h3 class='update'>".__('Your Integria IMS version number is') . ' ' . $config['db_scheme_build']."</h3>";
	} else {
		if ($config['current_package'] == 0) 
			echo "<h3 class='update'>".__('You do not have installed any updates of Integria IMS')."</h3>";
		else
			echo "<h3 class='update'>".__('Your Integria IMS version number is') . ' ' . $config['current_package']."</h3>";
	}
	
	echo "</b></p>";
	
	echo '<p class="info_update">' .
			__('This is a automatilly update Integria IMS. Be careful if you have changed any php file, please make a backup this modified files php. Because the update action ovewrite all php files in Integria IMS.') .
		'</p>' .
		'<p class="info_update">' .
			__('Update Manager sends anonymous information about Integria IMS usage (number of users). To disable it, just remove remote server address from Update Manager in main setup.') .
		'</p>';
		
	echo "<h3 class='update_online'>" . __('Online') . "</h3>";
	
	echo "<div id='box_online'>";
	echo "<div class='loading' style='width:100%; text-align: center;'>";
	print_image("images/wait.gif");
	echo "</div>";
	echo "<div class='checking_package' style='width:100%; text-align: center; display: none;'>";
	echo __('Checking for the newest package.');
	echo "</div>";
	echo "<div class='download_package' style='width:100%; text-align: center; display: none;'>";
	echo __('Downloading the package.');
	echo "</div>";
	echo "<div class='content'></div>";
	echo "<div class='progressbar' style='display: none;'><img class='progressbar_img' src='' /></div>";
	echo "</div>";
	echo "</div>";
	
	?>
	<script type="text/javascript">
		var version_update = "";
		var stop_check_progress = 0;
		
		$(document).ready(function() {
			check_online_packages();
			
			
		});
		
		function check_online_packages() {
			$("#box_online .checking_package").show();
			
			var parameters = {};
			parameters['page'] = 'godmode/setup/update_manager';
			parameters['check_online_free_packages'] = 1;
			
			jQuery.post(
				"ajax.php",
				parameters,
				function (data) {
					$("#box_online .checking_package").hide();
					
					$("#box_online .loading").hide();
					$("#box_online .content").html(data);
				},
				"html"
			);
		}
		
		function update_last_package(package, version) {
			version_update = version;
			
			$("#box_online .content").html("");
			$("#box_online .loading").show();
			$("#box_online .download_package").show();
			
			
			var parameters = {};
			parameters['page'] = 'godmode/setup/update_manager';
			parameters['update_last_free_package'] = 1;
			parameters['package'] = package;
			parameters['version'] = version;
			
			jQuery.post(
				"ajax.php",
				parameters,
				function (data) {
					if (data['in_progress']) {
						$("#box_online .loading").hide();
						$("#box_online .download_package").hide();
						
						$("#box_online .content").html(data['message']);
						
						install_package(package,version);
						setTimeout(check_progress_update, 1000);
					}
					else {
						$("#box_online .content").html(data['message']);
					}
				},
				"json"
			);
		}
		
		function install_package(package, version) {
			var parameters = {};
			parameters['page'] = 'godmode/setup/update_manager';
			parameters['install_package'] = 1;
			parameters['package'] = package;
			parameters['version'] = version;
			
			jQuery.post(
				"ajax.php",
				parameters,
				function (data) {
					if (data["status"] == "success") {
						$("#box_online .loading").hide();
						$("#box_online .progressbar").hide();
						$("#box_online .content").html(data['message']);
						stop_check_progress = 1;
					}
					else {
						$("#box_online .loading").hide();
						$("#box_online .progressbar").hide();
						$("#box_online .content").html(data['message']);
						stop_check_progress = 1;
					}
				},
				"json"
			);
		}
		
		function check_progress_update() {
			if (stop_check_progress) {
				return;
			}
			
			var parameters = {};
			parameters['page'] = 'godmode/setup/update_manager';
			parameters['check_update_free_package'] = 1;
			
			jQuery.post(
				"ajax.php",
				parameters,
				function (data) {
					if (stop_check_progress) {
						return;
					}
					
					if (data['correct']) {
						if (data['end']) {
							//$("#box_online .content").html(data['message']);
						}
						else {
							$("#box_online .progressbar").show();
							
							$("#box_online .progressbar .progressbar_img").attr('src',
								data['progressbar']);
							
							setTimeout(check_progress_update, 1000);
						}
					}
					else {
						//$("#box_online .content").html(data['message']);
					}
				},
				"json"
			);
		}
	</script>
	<?php
}


//Function to remove dir and files inside
 function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }

/**
 * The update copy entirire the tgz or fail (leave some parts copies and some part not).
 * This does make any thing with the BD.
 */
function update_manager_starting_update() {
	global $config;
	
	$path_package = $config['attachment_store'] .
		"/downloads/last_package.tgz";
	
	try {
		$phar = new PharData($path_package);
		rrmdir($config['attachment_store'] . "/downloads/temp_update/trunk");
		$phar->extractTo($config['attachment_store'] . "/downloads/temp_update");
	}
	catch (Exception $e) {
		// handle errors
		
		process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'fail',
						'message' => __('Failed extracting the package to temp directory.')
					)
				)
			),
			array('token' => 'progress_update_status'));
	}
	
	process_sql_update('tconfig',
		array('value' => 50),
		array('token' => 'progress_update'));
	
	$full_path = $config['attachment_store'] . "/downloads/temp_update/trunk";
	
	$result = update_manager_recurse_copy($full_path, $config['homedir'],
		array('install.php'));
	
	if (!$result) {
		process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'fail',
						'message' => __('Failed the copying of the files.')
					)
				)
			),
			array('token' => 'progress_update_status'));
	}
	else {
		process_sql_update('tconfig',
			array('value' => 100),
			array('token' => 'progress_update'));
		process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'end',
						'message' => __('Package extracted successfully.')
					)
				)
			),
			array('token' => 'progress_update_status'));
	}
}


function update_manager_recurse_copy($src, $dst, $black_list) { 
	$dir = opendir($src); 
	@mkdir($dst);
	@trigger_error("NONE");
	
	while (false !== ( $file = readdir($dir)) ) { 
		if (( $file != '.' ) && ( $file != '..' ) && (!in_array($file, $black_list))) { 
			if ( is_dir($src . '/' . $file) ) { 
				if (!update_manager_recurse_copy($src . '/' . $file,$dst . '/' . $file, $black_list)) {
					return false;
				}
			}
			else { 
				$result = copy($src . '/' . $file,$dst . '/' . $file);
				$error = error_get_last();
				
				if (strstr($error['message'], "copy(") ) {
					return false;
				}
			} 
		} 
	} 
	closedir($dir);
	
	return true;
} 

function update_manager_count_files($path) {
	$count = 0;
	$black_list = array('.', '..');
	$files = scandir($path);
	
	foreach ($files as $file) {
		if (in_array($file, $black_list))
			continue;
		
		if (is_dir(rtrim($path, '/') . '/' . $file)) {
			$count += update_manager_count_files(rtrim($path, '/') . '/' . $file);
		}
		else {
			$count++;
		}
	}
	
	return $count;
}

function update_manager_check_online_free_packages ($is_ajax = true) {
	global $config;
	
	$update_message = '';
	
	$users = get_valid_users_num();
	$license = $config['license'];

	if ($license == 'INTEGRIA-FREE') {
		$current_package = $config['db_scheme_build'];
	} else {
		$current_package = $config['current_package'];
	}

	$params = array('action' => 'newest_package',
			'license' => "INTEGRIA-FREE",
			'regular_limit_count' => license_count_regular_users(),
			'manager_limit_count' => license_count_manager_users(),
			'current_package' => $current_package,
			'version' => $config['version'],
			'build' => $config['build']);
		
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $config['url_updatemanager']);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_POST, true);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
	
	$result = curl_exec($curlObj);
	$http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
	curl_close($curlObj);
	
	if ($http_status >= 400 && $http_status < 500) {
		if ($is_ajax) {
			echo __("Server not found.");
		} else {
			$update_message = __("Server not found.");
		}
	}
	elseif ($http_status >= 500) {
		if ($is_ajax) {
			echo $result;
		} else {
			$update_message = $result;
		}
	}
	else {
		if ($is_ajax) {
			$result = json_decode($result, true);
			
			//~ if (!empty($result)) {
			if (!empty($result) && ($result != "[]")) {
				echo "<p><b>There is a new version:</b> " . $result[0]['version'] . "</p>";
				echo "<a href='javascript: update_last_package(\"" . base64_encode($result[0]["file_name"]) .
					"\", \"" . $result[0]['version'] ."\");'>" .
					__("Update to the last version") . "</a>";
			}
			else {
				echo __("There is no update available.");
			}
			return;
		} else {
			//~ if (!empty($result)) {
			if (!empty($result) && ($result != "[]")) {
				$result = json_decode($result, true);
				$update_message = "There is a new version: " . $result[0]['version'];
			}

			return $update_message;
		}
	}
		
}
?>
