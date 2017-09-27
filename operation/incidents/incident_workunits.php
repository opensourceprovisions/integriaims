<?php

// Integria IMS - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$id_grupo = "";
$creacion_incidente = "";
$id_incident = (int) get_parameter ('id');
$title = '';

// INFO: The workunits are treated like commits into the tickets sections

require_once ('include/functions_workunits.php');

//Check if we have id passed by parameter or by script loading
if (!$id_incident) {

	if ($id) {
		$id_incident = $id;
	} else { 
		audit_db ($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to incident #".$id_incident);
		include ("general/noaccess.php");
		return;
	}
}

// Obtain group of this incident
$incident = get_incident ($id_incident);

$result_msg = '';

//user with IR and incident creator see the information
$check_acl = enterprise_hook("incidents_check_incident_acl", array($incident));
$standalone_check = enterprise_hook("manage_standalone", array($incident));

if (($check_acl !== ENTERPRISE_NOT_HOOK && !$check_acl) || ($standalone_check !== ENTERPRISE_NOT_HOOK && !$standalone_check)) {
	audit_db ($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to incident #".$id_incident);
	include ("general/noaccess.php");
	exit;
}

$is_enterprise = false;

if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}

// Workunit ADD
$insert_workunit = (bool) get_parameter ('insert_workunit');
if ($insert_workunit) {
	$timestamp = print_mysql_timestamp();
	$nota = get_parameter ("nota");
	$timeused = (float) get_parameter ('duration');
	$have_cost = (int) get_parameter ('have_cost');
	$profile = (int) get_parameter ('work_profile');
	$public = (bool) get_parameter ('public');

	// Adding a new workunit to a incident in NEW status
	// Status go to "Assigned" and Owner is the writer of this Workunit
	//~ if (($incident["estado"] == 1) AND ($incident["id_creator"] != $config['id_user'])){
		//~ $sql = sprintf ('UPDATE tincidencia SET id_usuario = "%s", estado = 3,  affected_sla_id = 0, actualizacion = "%s" WHERE id_incidencia = %d', $config['id_user'], $timestamp, $id_incident);

		//~ incident_tracking ($id_incident, INCIDENT_STATUS_CHANGED, 3);
	
		//~ incident_tracking ($id_incident, INCIDENT_USER_CHANGED, $config["id_user"]);

	//~ } else {
		//~ $sql = sprintf ('UPDATE tincidencia SET affected_sla_id = 0, actualizacion = "%s" WHERE id_incidencia = %d', $timestamp, $id_incident);
	//~ }

	//~ process_sql ($sql);

	$workunit_id = create_workunit ($id_incident, $nota, $config["id_user"], $timeused, $have_cost, $profile, $public);
	
	if (is_ajax()) {
		// Clean the output
		// A non empty ouptput will treated as a successful response
		ob_clean();
		
		if ($workunit_id !== false) {
			// Return the updated list
			$workunits = get_incident_workunits($id_incident);
			if ($workunits) {
				ob_clean();
				
				foreach ($workunits as $workunit) {
					$workunit_data = get_workunit_data($workunit["id_workunit"]);
					
					echo '<div class="comment">';
					show_workunit_data ($workunit_data, $title);
					echo '</div>';
				}
			}
		}
		
		if ($is_enterprise) {
			incidents_run_realtime_workflow_rules ($id_incident);
		}
		return;
	}
	else {
		$result_msg = ui_print_success_message (__('Comment added successfully'), '', true, 'h3', true);
		echo $result_msg;
	}
	
	if ($is_enterprise) {
		incidents_run_realtime_workflow_rules ($id_incident);
	}
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table';
$table->colspan = array ();
$table->colspan[1][0] = 6;
$table->colspan[2][0] = 6;
$table->data = array ();
$table->size = array();
$table->style = array();
//~ $table->style[0] = 'vertical-align: top; padding-top: 10px;';
//~ $table->style[1] = 'vertical-align: top; padding-top: 10px;';
//~ $table->style[2] = 'vertical-align: top;';
//~ $table->style[3] = 'vertical-align: top;';
//~ $table->style[4] = 'vertical-align: top;';
//~ $table->style[5] = 'vertical-align: top;';
$table->data[0][0] = print_image('images/calendar_orange.png', true) . '&nbsp' . print_mysql_timestamp(0, "Y-m-d");
$table->data[0][1] = print_image('images/clock_orange.png', true) . '&nbsp' . print_mysql_timestamp(0, "H:i:s");
$table->data[0][2] = combo_roles (1, 'work_profile', __('Profile'), true);
$table->data[0][3] = print_input_text ("duration", $config["iwu_defaultime"], '', 7,  10, true, __('Time used'));
$table->data[0][4] = print_checkbox ('have_cost', 1, false, true, __('Have cost'));
$table->data[0][5] = print_checkbox ('public', 1, true, true, __('Public'));

$table->data[1][0] = print_textarea ('nota', 10, 70, '', "style='resize:none;'", true, __('Description'));

$button = '<div style="width: 100%; text-align: right;">';
$button .= '<span id="sending_data" style="display: none;">' . __('Sending data...') . '<img src="images/spinner.gif" /></span>';
$button .= print_submit_button (__('Add'), 'addnote', false, 'class="sub create"', true);
$button .= print_input_hidden ('insert_workunit', 1, true);
$button .= print_input_hidden ('id', $id_incident, true);
$button .= '</div>';

$table->data[2][0] = $button;

if (!$clean_output) {

	echo '<form id="form-add-workunit" method="post" action="index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id='.$id_incident.'&tab=workunits#incident-operations">';

	echo "<div style='width: 100%;'>";
	print_table ($table);
	echo "</div>";

	echo "</form>";

	echo "<h4>".__('Comments')."</h4>";
	
} else {
	echo "<h4 class='ticket_clean_report_title'>".__('Comments')."</h4>";
}

echo '<div id="comment-list">';

// Workunit view
$workunits = get_incident_workunits ($id_incident);

if (!$workunits) {
	echo '<h4>'.__('No comment was done in this ticket').'</h4>';
	return;
}

foreach ($workunits as $workunit) {
	$workunit_data = get_workunit_data ($workunit['id_workunit']);
	
	echo '<div class="comment">';
	show_workunit_data ($workunit_data, $title);
	echo '</div>';
}

echo '</div>';

?>

<script type="text/javascript">
	// Enclose in its own scope
	(function ($) {
		// Comment form controller
		var $commentForm = $("form#form-add-workunit");
		var $commentList = $("div#comment-list");

		var $commentProfile = $commentForm.find('select#work_profile');
		var $commentDuration = $commentForm.find('input#text-duration');
		var $commentHaveCost = $commentForm.find('input#checkbox-have_cost');
		var $commentPublic = $commentForm.find('input#checkbox-public');
		var $commentText = $commentForm.find('textarea#textarea-nota');
		
		var $modalImgContainer = $('<div></div>');
		var $modalBackdrop = $('<div></div>');
		var modalClickHandler = function (event) {
			event.preventDefault();
			$modalBackdrop.hide();
			$modalImgContainer
				.hide()
				.empty();
			event.stopPropagation();
		}
		
		$modalBackdrop
			.hide()
			.prop('id', 'modal-backdrop')
			.addClass('modal-backdrop')
			.click(modalClickHandler)
			.prependTo('body');
		
		$modalImgContainer
			.hide()
			.prop('id', 'modal-img-container')
			.addClass('modal-img-container')
			.click(modalClickHandler)
			.prependTo('body');
		
			
		var getCommentFiles = function (target) {
			var files = [];
			
			target
				.find('div.comment>div.notebody>a')
					.each(function(index, el) {
						var uri = parseURL(el.href);
						//el.search = el.search + '&content_disposition=inline';
						
						var auxPathname = uri.pathname;
						var pathnameArr = auxPathname.split('/');
						var pathnameChecks = true;
						// Script
						if (pathnameArr.pop() !== 'download_file.php')
							pathnameChecks = false;
						// Subsection
						if (pathnameArr.pop() !== 'common')
							pathnameChecks = false;
						// Section
						if (pathnameArr.pop() !== 'operation')
							pathnameChecks = false;
						
						if (pathnameChecks
								&& typeof uri.search.type !== 'undefined'
								&& uri.search.type === 'incident'
								&& typeof uri.search.id_attachment !== 'undefined'
								&& uri.search.id_attachment.length > 0) {
							files.push({
								target: $(el).parent(),
								URI: uri
							});
						}
					});
			
			return files;
		}
		
		var imageClickHandler = function (event) {
			event.preventDefault();
			
			if (typeof event.target !== 'undefined') {
				var $element = $(event.target);
				
				$modalBackdrop.show();
				$modalImgContainer.html($element.clone()).show();
			}
		}
		
		var loadImage = function (target, URI) {
			var $imageContainer = $('<div>', {
				id: 'comment-image-container-' + URI.search.id_attachment,
				class: 'comment-image-container'
			});
			
			target.append($imageContainer);
			
			isValidImg(URI.href, function (url, img) {
				if (img !== false) {
					var $loadedImg = $(img);
					$loadedImg
						.prop('id', 'comment-image-' + URI.search.id_attachment)
						.addClass('comment-image')
						.addClass('loaded')
						.click(imageClickHandler);
					
					$imageContainer.append('<br>', $loadedImg)
				}
				else {
					$imageContainer.remove();
				}
			});
		}
		
		var loadImages = function (target) {
			var files = getCommentFiles(target);
			
			$.each(files, function(index, val) {
				loadImage(val.target, val.URI);
			});
			
			target.on('click', 'img.comment-image.loaded', imageClickHandler);
		}
		
		$commentForm.submit(function(e) {
			e.preventDefault();
			
			$("#sending_data").show();
			
			var enableInputs = function() {
				$commentForm.find('input, textarea, button, select').prop("disabled", false);
			}
			var disableInputs = function() {
				$commentForm.find('input, textarea, button, select').prop("disabled", true);
			}
			var cleanInputs = function() {
				$commentProfile.val(0);
				$commentDuration.val(0);
				$commentHaveCost.prop('checked', false);
				$commentPublic.prop('checked', true);
				$commentText.val('');
			}
			
			var errorMessage = "<?php echo __('Error') . '. ' . __('The comment was not created'); ?>";
			
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				dataType: 'html',
				data: {
					page: 'operation/incidents/incident_workunits',
					id: <?php echo json_encode($id_incident); ?>,
					insert_workunit: 1,
					nota: function () {
						return $commentText.val();
					},
					duration: function () {
						return $commentDuration.val();
					},
					have_cost: function () {
						return $commentHaveCost.prop("checked") ? 1 : 0;
					},
					work_profile: function () {
						return $commentProfile.val();
					},
					public: function () {
						return $commentPublic.prop("checked") ? 1 : 0;
					}
				},
			})
			.done(function(data) {
				if (data.length > 0) {
					cleanInputs();
					
					$commentList.html(data);
					
					loadImages($commentList);
				}
				else {
					alert(errorMessage);
				}
			})
			.fail(function() {
				alert(errorMessage);
			})
			.always(function() {
				$("#sending_data").hide();
				enableInputs();
			});
			
		});
		
		loadImages($commentList);
		
	})(jQuery);
</script>