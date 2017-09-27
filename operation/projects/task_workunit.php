<?php

global $config;

check_login ();

require_once ('include/functions_workunits.php');
include_once ("include/functions_projects.php");
include_once ("include/functions_user.php");

if (defined ('AJAX')) {
	$multiple_delete_wu = get_parameter('multiple_delete_wu', 0);
	$multiple_update_wu = get_parameter('multiple_update_wu', 0);
	
	if ($multiple_delete_wu) {
		$ids = get_parameter('ids');
		
		if ($ids == '') {
			return;
		}
		
		
		$result_ids = explode(',', $ids);
		$result = '';
		foreach ($result_ids as $id) {
			$success = delete_task_workunit ($id);
		}

		echo json_encode($result);
		return;
	}
	
	if ($multiple_update_wu) {

		$ids = get_parameter('ids');
		
		if ($ids == '') {
			return;
		}
		
		$id_profile = get_parameter('id_profile');
		$id_task = get_parameter('id_task');
		$have_cost = get_parameter('have_cost');
		$public = get_parameter('public');
		$keep_cost = get_parameter('keep_cost');
		$keep_public = get_parameter('keep_public');
		
		$result_ids = explode(',', $ids);
		$result = '';
	
		foreach ($result_ids as $id) {

			$values = array();
			
			$wu_data = get_db_row_filter('tworkunit', array('id'=>$id));
			
			$values['id_profile'] = $id_profile;
			$values['have_cost'] = ($have_cost == "true") ? 1: 0;
			$values['public'] = ($public == "true") ? 1: 0;
			
			if ($id_profile == -1) { //No change option
				$values['id_profile'] = $wu_data['id_profile'];
			}
			if ($keep_cost == "true") {
				$values['have_cost'] = $wu_data['have_cost'];
			}
			if ($keep_public == "true") {
				$values['public'] = $wu_data['public'];
			}
			
			$result = db_process_sql_update('tworkunit', $values, array('id'=>$id));

			$id_workunit_task = get_db_sql ("SELECT id_task FROM tworkunit_task WHERE id_workunit = $id");
			$values_task['id_task'] = $id_task;
			if ($id_task == 0) { //No change option
				$values_task['id_task'] = $id_workunit_task;
			}
			$result_task = db_process_sql_update('tworkunit_task', $values_task, array('id_workunit'=>$id)); 
		}
		echo json_encode('ok');
		return;
		
	}
}

$id_project = (int) get_parameter ("id_project");
$id_task = (int) get_parameter ("id_task");
$id_user_filter = (string) get_parameter ("id_user", "");
$operation = (string) get_parameter ("operation");
$pure = get_parameter ("pure", 0);

// ACL
if (! $id_project) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation","Trying to access to task manager without project");
	no_permission();
}
$project_permission = get_project_access ($config["id_user"], $id_project);
if (!$project_permission["read"]) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to view workunit without permission");
	no_permission();
}
if ($id_task > 0) {
	$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (!$task_permission["read"]){
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to view workunit without task permission");
		no_permission();
	}
}


// Get names
$project_name = get_db_value ("name", "tproject", "id", $id_project);

$task_name = "";
if ($id_task != 0)
	$task_name = get_db_value ("name", "ttask", "id", $id_task);

// Lock Workunit
if ($operation == "lock") {
	lock_task_workunit ($id_workunit);
}

// ADD / UPDATE Workunit
if ($operation == "workunit") {
	
	// ACL
	if (! $task_permission["write"]){
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to add/update a workunit in a task without permission");
		no_permission();
	}
	
	$id_workunit = (int) get_parameter ('id_workunit');
	$insert = false;
	if ($id_workunit == 0) {
		$insert = true;
	}
	$duration = (float) get_parameter ("duration");
	$time = (string) get_parameter ('time');
	$date = (string) get_parameter ('date');
	$timestamp = $date." ".$time;
	$real_timestamp = date ('Y-m-d H:i:s');
	$description = (string) get_parameter ('description');
	$have_cost = (bool) get_parameter ('have_cost');
	$user_role = (int) get_parameter ('work_profile');

	if ($insert) {
		// INSERT
		$sql = sprintf ('INSERT INTO tworkunit (timestamp, duration, id_user,
			description, have_cost, id_profile)
			VALUES ("%s", %.2f, "%s", "%s", %d, %d)',
			$timestamp, $duration, $config['id_user'], $description,
			$have_cost, $user_role);
		$result = process_sql ($sql, 'insert_id');
		$id_workunit = $result;
	} else {
		// UPDATE WORKUNIT
		$sql = sprintf ('UPDATE tworkunit
			SET timestamp = "%s", duration = %.2f, description = "%s",
			have_cost = %d, id_profile = %d
			WHERE id = %d',
			$timestamp, $duration, $description, $have_cost,
			$user_role, $id_workunit);
		$result = process_sql ($sql);
	}
	
	if ($result) {
		$task = get_db_row ('ttask', 'id', $id_task);
		$current_hours = get_task_workunit_hours ($id_task);
		if ($insert) {
			mail_project (0, $config['id_user'], $id_workunit, $id_task);
			$sql = sprintf ('INSERT INTO tworkunit_task (id_task, id_workunit)
				VALUES (%d, %d)',
				$id_task, $id_workunit);
			process_sql ($sql);
			$result_output = ui_print_success_message (__('Workunit added'), '', true, 'h3', true);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU", "Inserted PWU. $description");
			task_tracking ($id_task, TASK_WORKUNIT_ADDED, $id_workunit);

			/* Autocomplete task progress */

                	if ($task['completion'] < 100) {
	                       /* Get expected task completion, based on worked hours */
       		               $expected_completion = round_number (floor ($current_hours * 100 / $task['hours']));

	                        $current_hours += $duration;
                        	$expected_completion =  round_number (floor ($current_hours * 100 / $task['hours']));
                        	$sql = sprintf ('UPDATE ttask
                                SET completion = %d
                                WHERE id = %d',
                                $expected_completion, $id_task);
                        	process_sql ($sql);
                	}
		} else {
			mail_project (1, $config['id_user'], $id_workunit, $id_task);
			$result_output = ui_print_success_message (__('Workunit updated'), '', true, 'h3', true);
			audit_db ($config["id_user"], $config["REMOTE_ADDR"], "PWU", "Updated PWU. $description");
		}
		
	} else {
		$result_output = ui_print_error_message (__('There was a problem adding workunit'), '', true, 'h3', true);
	}
	$operation = "view";
}

// DELETE Workunit
if ($operation == "delete") {
	
	// ACL
	if (! $task_permission["write"]){
		// Doesn't have access to this page
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to delete a workunit in a task without permission");
		no_permission();
	}
	
	$success = delete_task_workunit ($id_workunit);
	if (! $success) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation",
			"Trying to delete WU $id_workunit without rigths");
		include ("general/noaccess.php");
		return;
	}
	
	$result_output = ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);

	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "Work unit deleted", "Workunit for ".$config['id_user']);
}

// Render
if (isset($result_output))
	echo $result_output;

// Specific task
if ($id_task != 0) { 
	
	$sql_filter = "";
    if ($id_user_filter != "")
        $sql_filter = " AND tworkunit.id_user = '$id_user_filter' ";

	if ($task_permission["manage"])  {

	$sql= sprintf ('SELECT tworkunit.id
			FROM tworkunit, tworkunit_task 
			WHERE tworkunit_task.id_task = %d '. $sql_filter . '
			AND tworkunit_task.id_workunit = tworkunit.id
			ORDER BY tworkunit.timestamp DESC', $id_task);
	} else {
				$sql= sprintf ('SELECT tworkunit.id
			FROM tworkunit, tworkunit_task 
			WHERE tworkunit_task.id_task = %d '. $sql_filter . '
			AND id_user = "'.$config["id_user"].'" 
			AND tworkunit_task.id_workunit = tworkunit.id
			ORDER BY tworkunit.timestamp DESC', $id_task);		
	}
	if (!$pure) {
		$section_title = __('Workunit resume');
		$section_subtitle = $project_name.' - ' . __('All tasks');
		$t_menu = print_task_tabs('workunits');
		print_title_with_menu ($section_title, $section_subtitle, "task_workunit", 'projects', $t_menu, 'workunits');
	} else {
		echo '<h2>'.__('Workunit resume') . "</h2>";
		echo '<h4>' . $project_name.' - ' . __('All tasks');
		echo integria_help ("task_workunit", true);
		echo '<ul class="ui-tabs-nav"><li class="ui-tabs">';
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_task=$id_task&pure=0' title='".__("Back to view")."'><img src='images/go-previous.png'></a>";
		echo '</li>';
		echo '</ul>';
		echo '</h4>';
	}
} 
elseif ($id_project != 0) {
	// Whole project
	
	$sql_filter = "";
    if ($id_user_filter != "")
        $sql_filter = " AND tworkunit.id_user = '$id_user_filter' ";


	if ($project_permission["manage"])  {
		$sql = sprintf ('SELECT tworkunit.id
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d '. $sql_filter .' 
			AND tworkunit_task.id_workunit = tworkunit.id
			ORDER BY tworkunit.timestamp DESC', $id_project);
	} else {
		$sql = sprintf ('SELECT tworkunit.id
			FROM tworkunit, tworkunit_task, ttask 
			WHERE tworkunit_task.id_task = ttask.id
			AND ttask.id_project = %d '. $sql_filter .' 
			AND id_user = "'.$config["id_user"].'"
			AND tworkunit_task.id_workunit = tworkunit.id
			ORDER BY tworkunit.timestamp DESC', $id_project);
		
	}
	
	if (!$pure) {
		$section_title = __('Workunit resume');
		$section_subtitle = $project_name.' - ' . __('All tasks');
		$p_menu = print_project_tabs('workunits');
		print_title_with_menu ($section_title, $section_subtitle, "task_workunit", 'projects', $p_menu, 'workunits');
	} else {
		echo '<h2>'.__('Workunit resume') . "</h2>";
		echo '<h4>' . $project_name.' - ' . __('All tasks');
		echo integria_help ("task_workunit", true);
		echo '<ul class="ui-tabs-nav"><li class="ui-tabs">';
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&pure=0' title='".__("Back to view")."'><img src='images/go-previous.png'></a>";
		echo '</li>';
		echo '</ul>';
		echo '</h4>';
	}
}


$workunits = get_db_all_rows_sql ($sql);
if ($workunits) {
	foreach ($workunits as $workunit) {
		show_workunit_user ($workunit['id']);
	}
}

echo '<div id="show_multiple_edit">';

echo '<h4>'.__('Massive operations over selected items').'</h4>';
$table = new StdClass;
$table->class = 'search-table-button';
$table->width = '100%';
$table->data = array ();
$table->colspan = array ();

// Profile or role
if (dame_admin ($config['id_user'])) {
	$table->data[0][0] = combo_roles (false, 'id_profile', __('Role'), true, true, '', true);
}
else {
	$table->data[0][0] = combo_user_task_profile ($id_task, 'id_profile', $id_profile, false, true, true);
}

// Show task combo if none was given.
if (! $id_task) {
	$table->data[0][1] = combo_task_user_participant ($config['id_user'], true, 0, true, __('Task'), false, false, false, '', true);
}
else {
	$table->data[0][1] = combo_task_user_participant ($config['id_user'], true, $id_task, true, __('Task'), false, false, false, true);
}

// Various checkboxes

$table->data[2][0] = print_checkbox ('have_cost', 1, '', true, __('Have cost'));

$table->data[2][1] = print_checkbox ('keep_cost', 1, '', true, __('Keep cost'));

$table->data[3][0] = print_checkbox ('public', 1, '', true, __('Public'));

$table->data[3][1] = print_checkbox ('keep_public', 1, '', true, __('Keep public'));

$table->colspan[5][0] = 2;
$table->data[5][0] = print_submit_button (__('Update'), 'update_btn', false, 'class="sub upd"', true);
$table->data[5][0] .= print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"', true);

print_table ($table);	

echo '</div>';
echo "<div class= 'dialog ui-dialog-content' title='".__("Delete")."' id='item_delete_window'></div>";	
?>

<script type="text/javascript">
$(document).ready (function () {
	$(".lock_workunit").click (function () {
		var img = this;
		id = this.id.split ("-").pop ();
		values = Array ();
		values.push ({name: "page", value: "operation/users/user_spare_workunit"});
		values.push ({name: "operation", value: "lock"});
		values.push ({name: "id_workunit", value: id});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				$(img).fadeOut (function () {
					$(this).remove ();
				});
				$("#edit-"+id).fadeOut (function () {
					$(this).parent ("td").append (data);
					$(this).remove ();
				});
			},
			"html");
		return false;
	});
	
	$(".delete-workunit").attr ("onclick", "").click (function () {
		var div = $(this).parents ("div.notebody");
		id = this.id.split ("-").pop ();
		show_validation_delete_general("delete_task_wu", id, ' ', ' ' );
		/*
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		values = Array ();
		values.push ({name: "page", value: "operation/users/user_spare_workunit"});
		values.push ({name: "operation", value: "delete"});
		values.push ({name: "id_workunit", value: id});
		jQuery.post ("ajax.php",
			values,
			function (status) {
				$("#wu_"+id).remove();
			},
			"html");
		return false;
		*/
	});
	
	//WU Multiple delete
	$("#submit-delete_btn").click (function () {
				
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_delete_wu=1&ids=" + checkboxValues,
		dataType: "json",
		success: function (data, status) {
			var checkboxArray = checkboxValues.split(',');
			checkboxArray.forEach(function(item) {
				var div = document.getElementById("wu_"+item);
				div.remove();
			});
		}
		});
	});

	$("#submit-update_btn").click (function () {
		
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;

		var checkboxValues = "";
		$('input[name="op_multiple[]"]:checked').each(function() {
			if (checkboxValues == "")
				checkboxValues += this.value;
			else 
				checkboxValues += ","+this.value;
		});	

		var id_profile = $("#id_profile").val();
		var id_task = $("#id_task").val();
		var have_cost = document.getElementById('checkbox-have_cost').checked;
		var is_public = document.getElementById('checkbox-public').checked;
		var keep_cost = document.getElementById('checkbox-keep_cost').checked;
		var keep_public = document.getElementById('checkbox-keep_public').checked;
		
		$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&multiple_update_wu=1&ids="+checkboxValues+"&id_profile="+id_profile+
			"&id_task="+id_task+"&have_cost="+have_cost+"&public="+is_public+"&keep_cost="+keep_cost+"&keep_public="+keep_public,
		dataType: "json",
		success: function (data, status) {
			location.reload();
		}
		});
	});
});
</script>
