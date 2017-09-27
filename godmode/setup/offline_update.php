<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

check_login ();

if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access to offline update");
	require ("general/noaccess.php");
	exit;
}

require_once('include/functions_db.php');

if (defined ('AJAX')) {
	
	$upload_file = (boolean) get_parameter("upload_file");
	if ($upload_file) {
		ob_clean();
		$return = array();
		
		if (isset($_FILES['upfile']) && $_FILES['upfile']['error'] == 0) {
			
			$extension = pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);
			
			// The package extension should be .oum
			if (strtolower($extension) === "oum") {
				
				$path = $_FILES['upfile']['tmp_name'];
				// The package files will be saved in [user temp dir]/integria_oum/package_name
				$destination = $config['attachment_store'] . "/tmp/integria_oum/" . $_FILES['upfile']['name'];
				// files.txt will have the names of every file of the package
				if (file_exists($destination."/files.txt")) {
					unlink($destination."/files.txt");
				}
				
				$zip = new ZipArchive;
				// Zip open
				if ($zip->open($path) === true) {
					// The files will be extracted one by one
					for($i = 0; $i < $zip->numFiles; $i++) {
						$filename = $zip->getNameIndex($i);
						
						if ($zip->extractTo($destination, array($filename))) {
							// Creates a file with the name of the files extracted
							file_put_contents ($destination."/files.txt", $filename."\n", FILE_APPEND | LOCK_EX);
						} else {
							// Deletes the entire extraction directory if a file can not be extracted
							delete_directory($destination);
							$return["status"] = "error";
							$return["message"] = __("There was an error extracting the file '".$filename."' from the package.");
							echo json_encode($return);
							return;
						}
					}
					// Creates a file with the number of files extracted
					file_put_contents ($destination."/files.info.txt", $zip->numFiles);
					// Zip close
					$zip->close();
					
					$return["status"] = "success";
					$return["package"] = $destination;
					echo json_encode($return);
					return;
				} else {
					$return["status"] = "error";
					$return["message"] = __("The package was not extracted.");
					echo json_encode($return);
					return;
				}
			} else {
				$return["status"] = "error";
				$return["message"] = __("Invalid extension. The package must have the extension .oum.");
				echo json_encode($return);
				return;
			}
		}
		
		$return["status"] = "error";
		$return["message"] = __("The file was not uploaded succesfully.");
		echo json_encode($return);
		return;
	}

	$install_package = (boolean) get_parameter("install_package");
	if ($install_package) {
		ob_clean();
		
		$package = (string) get_parameter("package");
		$package = trim($package);

		$is_package = preg_match("/^.*package_(\d*)/", $package);
		//$is_package = preg_match("/^[\w*\/*]*package_[\d*]/", $package);

		if ($is_package) {

			$current_package = get_db_value('value', 'tconfig', 'token', 'current_package');
			$pattern = "/\.oum$/";
			$replacement = "";
			$package_aux = preg_replace($pattern, $replacement, $package); // Remove extension .oum
			//$pattern = "/^[\w*\/*]*package_/";
			$pattern = "/^.*package_/";
			$package_num = preg_replace($pattern, $replacement, $package_aux); // Get the number of the package

			if ($current_package >= $package_num) {
				fclose($files_h);
				$return["status"] = "error";
				$return["message"]= __("Package "). $package_num . __(" is already installed.");
				echo json_encode($return);
				return;
			}
		}
		
		$package = clean_output($package);
		// All files extracted
		$files_total = $package."/files.txt";
		// Files copied
		$files_copied = $package."/files.copied.txt";
		$return = array();
		
		if (file_exists($files_copied)) {
			unlink($files_copied);
		}
		
		if (file_exists($package)) {
			
			if ($files_h = fopen($files_total, "r")) {
				
				while ($line = stream_get_line($files_h, 65535, "\n")) {
					$line = trim($line);
					
					// Tries to move the old file to the directory backup inside the extracted package
					if (file_exists($config["homedir"]."/".$line)) {
						rename($config["homedir"]."/".$line, $package."/backup/".$line);
					}
					// Tries to move the new file to the Integria directory
					$dirname = dirname($line);
					if (!file_exists($config["homedir"]."/".$dirname)) {
						$dir_array = explode("/", $dirname);
						$temp_dir = "";
						foreach ($dir_array as $dir) {
							$temp_dir .= "/".$dir;
							if (!file_exists($config["homedir"].$temp_dir)) {
								mkdir($config["homedir"].$temp_dir);
							}
						}
					}
					if (is_dir($package."/".$line)) {
						if (!file_exists($config["homedir"]."/".$line)) {
							mkdir($config["homedir"]."/".$line);
							file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX);
						}
					} else {
						if (rename($package."/".$line, $config["homedir"]."/".$line)) {
							
							// Append the moved file to the copied files txt
							if (!file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX)) {
								
								// If the copy process fail, this code tries to restore the files backed up before
								if ($files_copied_h = fopen($files_copied, "r")) {
									while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
										$line_c = trim($line_c);
										if (!rename($package."/backup/".$line, $config["homedir"]."/".$line_c)) {
											$backup_status = __("Some of your files might not be recovered.");
										}
									}
									if (!rename($package."/backup/".$line, $config["homedir"]."/".$line)) {
										$backup_status = __("Some of your files might not be recovered.");
									}
									fclose($files_copied_h);
								} else {
									$backup_status = __("Some of your old files might not be recovered.");
								}
								
								fclose($files_h);
								$return["status"] = "error";
								$return["message"]= __("Line '$line' not copied to the progress file.")."&nbsp;".$backup_status;
								echo json_encode($return);
								return;
							}
						} else {
							
							// If the copy process fail, this code tries to restore the files backed up before

							if ($files_copied_h = fopen($files_copied, "r")) {
								while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
									$line_c = trim($line_c);
									if (!rename($package."/backup/".$line, $config["homedir"]."/".$line)) {
										$backup_status = __("Some of your old files might not be recovered.");
									}
								}
								fclose($files_copied_h);
							} else {
								$backup_status = __("Some of your files might not be recovered.");
							}
							
							fclose($files_h);
							$return["status"] = "error";
							$return["message"]= __("File '$line' not copied.")."&nbsp;".$backup_status;
							echo json_encode($return);
							return;
						}
					}
				}
				fclose($files_h);
			} else {
				$return["status"] = "error";
				$return["message"]= __("An error ocurred while reading a file.");
				echo json_encode($return);
				return;
			}
		} else {
			$return["status"] = "error";
			$return["message"]= __("The package does not exist");
			echo json_encode($return);
			return;
		}
		
		if ($is_package) {
			$res = db_process_sql_update('tconfig', array('value'=>$package_num), array('token'=>'current_package'));
		}
		$return["status"] = "success";
		echo json_encode($return);
		return;
	}

	$check_install_package = (boolean) get_parameter("check_install_package");
	if ($check_install_package) {
		// 1 second
		//sleep(1);
		// Half second
		usleep(500000);
		
		ob_clean();
		
		$package = (string) get_parameter("package");
		// All files extracted
		$files_total = $package."/files.txt";
		// Number of files extracted
		$files_num = $package."/files.info.txt";
		// Files copied
		$files_copied = $package."/files.copied.txt";
		
		$files = @file($files_copied);
		if (empty($files))
			$files = array();
		$total = (int)@file_get_contents($files_num);
		
		$progress = 0;
		if ((count($files) > 0) && ($total > 0)) {
			$progress = format_numeric((count($files) / $total) * 100, 2);
			if ($progress > 100)
				$progress = 100;
		}
		
		$return = array();
		$return['info'] = (string) implode("<br />", $files);
		$return['progress'] = $progress;
		
		if ($progress >= 100) {
			unlink($files_total);
			unlink($files_num);
			unlink($files_copied);
		}
		
		echo json_encode($return);
		return;
	}
}


echo "<h2>" . __("Offline update") . "</h2>";
echo "<h4>" . __("Update Integria") . "</h4>";
?>

<form id="form-offline_update" class="fileupload_form" method="post" enctype="multipart/form-data">
	<div></div>
	<ul></ul>
</form>

<script src="include/js/jquery.fileupload.js"></script>
<script src="include/js/jquery.iframe-transport.js"></script>
<script src="include/js/jquery.knob.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>

<script type="text/javascript">
	
	form_upload();
	
	function form_upload () {
		//Thanks to: http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/
		var ul = $('#form-offline_update ul');
		
		$('#form-offline_update div').prop("id", "drop_file");
		$('#drop_file').html('<?php echo __('Drop the package here or ') ?> &nbsp;&nbsp;&nbsp;<a><?php echo __('browse it') ?></a>' +
			'<input name="upfile" type="file" id="file-upfile" accept=".oum" class="sub file" />');
		$('#drop_file a').click(function() {
			// Simulate a click on the file input button to show the file browser dialog
			$(this).parent().find('input').click();
		});

		// Initialize the jQuery File Upload plugin
		$('#form-offline_update').fileupload({
			
			url: 'ajax.php?page=godmode/setup/offline_update&upload_file=true',
			
			// This element will accept file drag/drop uploading
			dropZone: $('#drop_file'),

			// This function is called when a file is added to the queue;
			// either via the browse button, or via drag/drop:
			add: function (e, data) {
				$('#drop_file').slideUp();
				var tpl = $('<li class="offline-update-item"><input type="text" id="input-progress" value="0" data-width="65" data-height="65"'+
					' data-fgColor="#FF9933" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');
				
				// Append the file name and file size
				tpl.find('p').text(data.files[0].name)
							 .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

				// Add the HTML to the UL element
				ul.html("");
				data.context = tpl.appendTo(ul);

				// Initialize the knob plugin
				tpl.find('input').val(0);
				tpl.find('input').knob({
					'draw' : function () {
						$(this.i).val(this.cv + '%')
					}
				});

				// Listen for clicks on the cancel icon
				tpl.find('span').click(function() {

					if (tpl.hasClass('working') && typeof jqXHR != 'undefined') {
						jqXHR.abort();
					}

					tpl.fadeOut(function() {
						tpl.remove();
						$('#drop_file').slideDown();
					});

				});

				// Automatically upload the file once it is added to the queue
				data.context.addClass('working');
				var jqXHR = data.submit();
			},

			progress: function(e, data) {

				// Calculate the completion percentage of the upload
				var progress = parseInt(data.loaded / data.total * 100, 10);

				// Update the hidden input field and trigger a change
				// so that the jQuery knob plugin knows to update the dial
				data.context.find('input').val(progress).change();

				if (progress == 100) {
					data.context.removeClass('working');
					// Class loading while the zip is extracted
					data.context.addClass('loading');
				}
			},

			fail: function(e, data) {
				// Something has gone wrong!
				data.context.removeClass('working');
				data.context.removeClass('loading');
				data.context.addClass('error');
			},
			
			done: function (e, data) {
				
				var res = JSON.parse(data.result);
				
				if (res.status == "success") {
					data.context.removeClass('loading');
					data.context.addClass('suc');
				
					ul.find('li').find('span').unbind("click");
					
					// Transform the file input zone to show messages
					$('#drop_file').prop('id', 'log_zone');
					
					// Success messages
					$('#log_zone').html("<div><?php echo __('The package has been uploaded successfully.') ?></div>");
					$('#log_zone').append("<div><?php echo __('Remember that this package will override the actual Integria IMS files and it is recommended to do a backup before continue with the update.') ?></div>");
					$('#log_zone').append("<div><?php echo __('Click on the file below to begin.') ?></div>");
					
					// Show messages
					$('#log_zone').slideDown(400, function() {
						$('#log_zone').height(75);
						$('#log_zone').css("overflow", "auto");
					});
					
					// Bind the the begin of the installation to the package li
					ul.find('li').css("cursor", "pointer");
					ul.find('li').click(function () {
						
						ul.find('li').unbind("click");
						ul.find('li').css("cursor", "default");
						
						// Change the log zone to show the copied files
						$('#log_zone').html("");
						$('#log_zone').slideUp(200, function() {
							$('#log_zone').slideDown(200, function() {
								$('#log_zone').height(200);
								$('#log_zone').css("overflow", "auto");
							});
						});
						
						// Changed the data that shows the file li
						data.context.find('p').text("<?php echo __('Updating') ?>...");
						data.context.find('input').val(0).change();
						
						// Begin the installation
						install_package(res.package, 'filename');
					});
				} else {
					// Something has gone wrong!
					data.context.removeClass('loading');
					data.context.addClass('error');
					ul.find('li').find('span').click(function() { window.location.reload(); });
					
					// Transform the file input zone to show messages
					$('#drop_file').prop('id', 'log_zone');
					
					// Error messages
					$('#log_zone').html("<div>"+res.message+"</div>");
					
					// Show error messages
					$('#log_zone').slideDown(400, function() {
						$('#log_zone').height(75);
						$('#log_zone').css("overflow", "auto");
					});
				}
			}

		});

		// Prevent the default action when a file is dropped on the window
		$(document).on('drop_file dragover', function (e) {
			e.preventDefault();
		});
	}

    function install_package (package) {
		var parameters = {};
		parameters['page'] = 'godmode/setup/offline_update';
		parameters['install_package'] = 1;
		parameters['package'] = package;
		
		$('#form-offline_update ul').find('li').removeClass('suc');
		$('#form-offline_update ul').find('li').addClass('loading');
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function (data) {
				$('#form-offline_update ul').find('li').removeClass('loading');
				if (data.status == "success") {
					$('#form-offline_update ul').find('li').addClass('suc');
					$('#form-offline_update ul').find('li').find('p').html('<?php echo __('Package updated successfully.') ?>')
						.append("<i><?php echo __('If there are any database change, it will be applied on the next login.') ?></i>");
						
					check_install_package(package);
				} else {
					$('#form-offline_update ul').find('li').addClass('error');
					$('#form-offline_update ul').find('li').find('p').html('<?php echo __('Package not updated.') ?>')
						.append("<i>"+data.message+"</i>");
				}
				$('#form-offline_update ul').find('li').css("cursor", "pointer");
				$('#form-offline_update ul').find('li').click(function() { window.location.reload(); });
			}
		});
		
		// Check the status of the update
		//check_install_package(package);

	}
	
	function check_install_package (package) {
		var parameters = {};
		parameters['page'] = 'godmode/setup/offline_update';
		parameters['check_install_package'] = 1;
		parameters['package'] = package;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				// Print the updated files and take the scroll to the bottom
				$("#log_zone").html(data.info);
				$("#log_zone").scrollTop($("#log_zone").prop("scrollHeight"));
				
				// Change the progress bar
				if ($('#form-offline_update ul').find('li').hasClass('suc')) {
					$('#form-offline_update').find('ul').find('li').find('input').val(100).trigger('change');
				} else {
					$('#form-offline_update').find('ul').find('li').find('input').val(data['progress']).trigger('change');
				}
				
				// The class loading is present until the update ends
				var isInstalling = $('#form-offline_update ul').find('li').hasClass('loading');
				if (data.progress < 100 && isInstalling) {
					// Recursive call to check the update status
					check_install_package(package);
				}
			}
		})
	}
	
</script>
