<?php
// Integria 5.0 - http://integria.sourceforge.net
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

require_once('include/functions_tags.php');

if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>" . __("Tags management") . "</h2>";
echo "<h4>" . __("List of tags") . "</h4>";

// Tag info
$id = (int) get_parameter('id');
$name = (string) get_parameter('name');
$colour = (string) get_parameter('colour');

// Actions
$action = (string) get_parameter('action');
$create = $action === 'create';
$update = $action === 'update';
$delete = $action === 'delete';

if ($create || $update || $delete) {
	$crud_operation = array();
	$crud_operation['result'] = false;
	$crud_operation['message'] = '';
}

// Data processing
if ($create) {
	// name and colour required
	if (!empty($name) && !empty($colour)) {
		if (! exists_tag_name($name)) {
			try {
				$values = array(
						TAGS_TABLE_NAME_COL => $name,
						TAGS_TABLE_COLOUR_COL => $colour
					);
				$result = create_tag($values);
				$crud_operation['result'] = $result;
				
				if ($result !== false) {
					$id = 0;
					$name = '';
					$colour = '';
				}
				
				$crud_operation['message'] .= ui_print_result_message($result,
					__('Tag created successsfully'),
					__('There was an error creating the tag'), '', true);
			}
			catch (Exception $e) {
				$crud_operation['message'] .= ui_print_error_message($e->getMessage(), '', true);
			}
		}
		else {
			$crud_operation['message'] .= ui_print_error_message(__('The name already exists'), '', true);
		}
	}
	else {
		$crud_operation['message'] .= ui_print_error_message(__('Some required values are missing'), '', true);
	}
}
else if ($update) {
	// id, name and colour required
	if (!empty($id) && !empty($name) && !empty($colour)) {
		// Check the name
		$allow_name = exists_tag_name($name);
		
		// Check if the new name is the same as the old
		if (!$allow_name) {
			// Get the old name. This function returns an array of names for the matched filter
			$old_names = get_available_tag_names(array(TAGS_TABLE_ID_COL => $id));
			if (!empty($old_names)) {
				$old_name = array_shift($old_names);
				
				if (!empty($old_name) && $old_name != $name)
					$allow_name = true;
			}
		}
		
		if ($allow_name) {
			try {
				$values = array(
						TAGS_TABLE_NAME_COL => $name,
						TAGS_TABLE_COLOUR_COL => $colour
					);
				$result = update_tag($id, $values);
				$crud_operation['result'] = $result;
				
				// Result can be 0 if the target has the same values as the source
				if ($result !== false) {
					$result = true;
					
					// Prepare the values for another creation
					$id = 0;
					$name = '';
					$colour = '';
				}
				
				$crud_operation['message'] .= ui_print_result_message($result,
					__('Tag updated successsfully'),
					__('There was an error updating the tag'), '', true);
			}
			catch (Exception $e) {
				$crud_operation['message'] .= ui_print_error_message($e->getMessage(), '', true);
			}
		}
		else {
			$crud_operation['message'] .= ui_print_error_message(__('The name already exists'), '', true);
		}
	}
	else {
		$crud_operation['message'] .= ui_print_error_message(__('Some required values are missing'), '', true);
	}
}
else if ($delete) {
	// id required
	if (!empty($id)) {
		try {
			$result = delete_tag($id);
			$crud_operation['result'] = $result;
			
			// Result can be 0 if the target does not exist
			if ($result !== false) {
				$result = true;
				
				// Prepare the values for another creation
				$id = 0;
				$name = '';
				$colour = '';
			}
			
			$crud_operation['message'] .= ui_print_result_message($result,
				__('Tag deleted successsfully'),
				__('There was an error deleting the tag'), '', true);
		}
		catch (Exception $e) {
			$crud_operation['message'] .= ui_print_error_message($e->getMessage(), '', true);
		}
	}
	else {
		$crud_operation['message'] .= ui_print_error_message(__('Some required values are missing'), '', true);
	}
}

// Echo the result of the CRUD operation
if (isset($crud_operation))
	echo $crud_operation['message'];
$table = new stdClass();
$table->width = '100%';
$table->class = 'search-table';
$table->style = array ();
$table->colspan = array ();
$table->style[0] = 'font-weight: bold;';
$table->style[1] = 'text-align: left;';
$table->style[2] = 'font-weight: bold;';
$table->style[3] = 'text-align: left;';
$table->style[4] = 'font-weight: bold';
$table->style[5] = 'text-align: left;';
$table->style[6] = 'text-align: right;';
$table->data = array ();

// Name
$table->data[0][0] = __('Name') . '&nbsp;';
$table->data[0][0] .= print_input_text('name', $name, '', 25, 100, true);

// Colour
$table->data[1][0] = __('Colour') . '<br>';
$tag_colours = get_available_tag_colours();
$table->data[1][0] .= print_select($tag_colours, 'colour', $colour, '', '', '', true, false, false);

// Preview
$table->data[3][0] = __('Preview') . '&nbsp;';
$table->data[4][0] = '<span id="tag-preview"></span>';

$table->data[6][0] = print_input_hidden('id', $id, true);
if (empty($id)) {
	$table->data[6][0] .= print_input_hidden('action', 'create', true);
}
else {
	$table->data[6][0] .= print_input_hidden('action', 'update', true);
}
$table->data[6][0] .= print_submit_button(__('Add'), 'create_btn', false, 'class="sub create"', true);
$table->data[6][0] .= print_submit_button(__('Update'), 'update_btn', false, 'class="sub upd"', true);
$table->data[6][0] .= print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"', true);

echo "<div class='divform'>";
echo '<form id="tags-form" method="POST">';
print_table($table);
echo '</form>';
echo '</div>';

// List
$tags = get_available_tags();
html_render_tags_view_manage ($tags);

?>

<script type="text/javascript">
(function ($) {
	var $idHidden = $('input#hidden-id');
	var $nameInput = $('input#text-name');
	var $colourInput = $('select#colour');
	var $tagSpan = $('span#tag-preview');
	
	var $form = $('form#tags-form');
	var $actionHidden = $('input#hidden-action');
	var $createSubmit = $('input#submit-create_btn');
	var $updateSubmit = $('input#submit-update_btn');
	var $deleteSubmit = $('input#submit-delete_btn');
	
	var updateFormStatus = function (status) {
		if (status === 'creating') {
			$actionHidden.val('create');
			$createSubmit.show();
			$updateSubmit.hide();
			$deleteSubmit.hide();
		}
		else if (status === 'updating') {
			$actionHidden.val('update');
			$createSubmit.hide();
			$updateSubmit.show();
			$deleteSubmit.show();
		}
	}
	
	// Update the form status
	if (parseInt($idHidden.val()) > 0)
		updateFormStatus('updating');
	else
		updateFormStatus('creating');
	
	var updateTagPreviewName = function (event) {
		var name = this.value;
		
		$tagSpan.html(name);
	}
	
	var updateTagPreviewColour = function (event) {
		var colour = this.value;
		
		$tagSpan
			.prop('class', '')
			.addClass('tag')
			.addClass('label')
			.addClass(colour);
	}
	
	// Bind and fire keyup event
	$nameInput.keyup(updateTagPreviewName).keyup();
	// Bind and fire change event
	$colourInput.change(updateTagPreviewColour).change();
	
	// Catch the delete button click
	$form.submit(function(event) {
		// Change the action if the delete button was clicked
		var btn_name = $(this).find('input[type="submit"]:focus').prop('name');
		if (btn_name === 'delete_btn') {
			$actionHidden.val('delete');
		}
	});
	
	// Tags list. A click on the tag will load the tag edition
	var $tagsView = $('div.tags-view');
	
	// Change the status of the form on the tag clicks
	$tagsView.on('click', 'span.tag.label', function (event) {
		event.preventDefault();
		
		if (typeof event.target !== 'undefined') {
			// Get the label info from the target element
			var id = $(event.target).data('id');
			var name = $(event.target).data('name');
			var colour = $(event.target).data('colour');
			
			// Deselect the tag edition
			if ($idHidden.val() == id) {
				// Change the status of the form
				updateFormStatus('creating');
				
				// Update the id input
				$idHidden.val(0);
				// Change the value of the name input and fire the keyup event
				$nameInput.val('').keyup();
				// Change the value of the colour select and fire the change event
				$colourInput.val('').change();
			}
			// Select the tag edition
			else {
				// Change the status of the form
				updateFormStatus('updating');
				
				// Update the id input
				$idHidden.val(id);
				// Change the value of the name input and fire the keyup event
				$nameInput.val(name).keyup();
				// Change the value of the colour select and fire the change event
				$colourInput.val(colour).change();
			}
				
		}
	});
	$tagsView
		.children('span.tag.label')
		.css('cursor', 'pointer')
		.tooltip({ content: '<?php echo __("Click to enable/disable edition"); ?>' });
	
})(window.jQuery);
</script>
