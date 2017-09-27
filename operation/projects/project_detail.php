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

include_once ("include/functions_projects.php");
include_once ("include/functions_tasks.php");
include_once ("include/functions_graph.php");

$create_mode = 0;
$name = "";
$description = "";
$end_date = "";
$start_date = "";
$id_project = -1; // Create mode by default
$result_output = "";
$id_project_group = 0;
$cc = "";

$action = (string) get_parameter ('action');
$id_project = (int) get_parameter ('id_project');

$create_project = (bool) get_parameter ('create_project');


$graph_ttl = 1;

if ($pdf_output) {
	$graph_ttl = 2;
}

$section_access = get_project_access ($config['id_user']);
if ($id_project) {
	$project_access = get_project_access ($config['id_user'], $id_project);
}

// ACL - To access to this section, the required permission is PR
if (!$section_access['read']) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to project detail section");
	no_permission();
}
// ACL - If creating, the required permission is PW
if ($create_project && !$section_access['write']) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to create a project");
	no_permission();
}
// ACL - To view an existing project, belong to it is required
if ($id_project && !$project_access['read']) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to view a project");
	no_permission();
}


// Update project
if ($action == 'update') {
	// ACL - To update an existing project, project manager permission is required
	if ($id_project && !$project_access['manage']) {
		audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to update the project $id_project");
		no_permission();
	}
	$user = get_parameter('id_owner');
	$name = get_parameter ("name");
	$description = get_parameter ('description');
	$start_date = get_parameter ('start_date');
	$end_date = get_parameter ('end_date');
	$id_project_group = get_parameter ("id_project_group");
	$cc = get_parameter('cc', '');
	$sql = sprintf ('UPDATE tproject SET 
			name = "%s", description = "%s", id_project_group = %d,
			start = "%s", end = "%s", id_owner = "%s", cc = "%s"
			WHERE id = %d',
			$name, $description, $id_project_group,
			$start_date, $end_date, $user, $cc, $id_project);
	$result = process_sql ($sql);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Project updated", "Project $name");
	if ($result !== false) {
		project_tracking ($id_project, PROJECT_UPDATED);
		$result_output = ui_print_success_message (__('The project successfully updated'), '', true, 'h3', true);
	} else {
		$result_output = ui_print_error_message (__('Could not update project'), '', true, 'h3', true);
	}
}

// Edition / View mode
if ($id_project) {
	$project = get_db_row ('tproject', 'id', $id_project);
	
	$name = $project["name"];
	$description = $project["description"];
	$start_date = $project["start"];
	$end_date = $project["end"];
	$owner = $project["id_owner"];
	$id_project_group = $project["id_project_group"];
	$cc = $project["cc"];
} 


// Show result of previous operations
echo $result_output;

// Create project form
if ($create_project) {
	$email_notify = 0;
	$iduser_temp = $_SESSION['id_usuario'];
	$titulo = "";
	$prioridad = 0;
	$id_grupo = 0;
	$grupo = dame_nombre_grupo (1);
	$owner = $config["id_user"];
	$estado = 0;
	$actualizacion = date ("Y/m/d H:i:s");
	$inicio = $actualizacion;
	$id_creator = $iduser_temp;
	$create_mode = 1;
	$id_project_group = 0;
} 

if ($id_project)
	echo '<form method="post" id="form-new_project">';
else
	echo '<form method="post" id="form-new_project" action="index.php?sec=projects&sec2=operation/projects/project_overview">';

// Main project table
if ($create_mode == 0){
	
	// Print title and menu.
	$section_title = __('Project management');
	$section_subtitle = get_db_value ("name", "tproject", "id", $id_project);
	$p_menu = false;
	$print_help = false;

	if (!$clean_output) {
		$print_help = "project_detail";
		$p_menu = print_project_tabs('overview');
	}
	
	print_title_with_menu ($section_title, $section_subtitle, $print_help, 'projects', $p_menu, 'overview');
}
else {
	echo '<h2>'.__('Projects').'</h2>';
	echo '<h4>'.__('Create project').'</h4>';

	// Right/Left Tables
	$table = new stdClass;
	$table->width = '100%';
	$table->class = "search-table-button";
	$table->style[0] = 'width: 20%';
	$table->style[1] = 'width: 20%';
	$table->style[2] = 'width: 20%';
	$table->style[3] = 'width: 20%';
	$table->data = array ();
	$table->cellspacing = 4;
	$table->cellpadding = 4;

	// Project info
	//$project_info = '<table class="search-table-button" style="margin-top: 0px;">';

	// Name

	$table->data[0][0] = '<b>'.__('Name').' </b><br >';
	$table->data[0][0] .= '<input type="text" name="name" size=50 value="'.$name.'">';
	
	$id_owner = get_db_value ( 'id_owner', 'tproject', 'id', $id_project);
	$table->data[0][1] = "<b>".__('Project manager')." </b><br >";
	$table->data[0][1] .= print_input_text_extended ('id_owner', $owner, 'text-id_owner', '', 15, 20, false, '',
				'', true, '','');

	$table->data[0][2] = '<b>' .  __('Project group') . "</b><br >";
	if (!$clean_output) {
		$table->data[0][2] .= print_select_from_sql ("SELECT * from tproject_group ORDER BY name",
			"id_project_group", $id_project_group, "", __('None'), '0',
			true, false, true, false);
	} else {
		$table->data[0][2] .= get_db_value ("name", "tproject_group", "id", $id_project_group);
	}
	
	// CC
	$table->data[1][0] = '<b>'.__('CC').print_help_tip (__("Email to notify changes in workunits"), true).' </b><br >';
	$table->data[1][0] .= '<input type="text" name="cc" size=50 value="'.$cc.'">';

	// start and end date
	$table->data[1][1] = '<b>'.__('Start').' </b><br >';
	$table->data[1][1] .= print_input_text ('start_date', $start_date, '', 10, 10, true);

	$table->data[1][2] = '<b>'.__('End').' </b><br >';
	$table->data[1][2] .= print_input_text ('end_date', $end_date, '', 10, 10, true);

	// Description
	$table->colspan[7][0] = 4;
	$table->data[7][0] = "<b>".__("Description")."</b>";
	$table->data[7][0] .= '<textarea name="description" style="height: 140px;">';
	$table->data[7][0] .= $description;
	$table->data[7][0] .= "</textarea>";

	print_table ($table);

	echo '<div style="width:100%;">';
	if (!$clean_output)  {
		
		unset($table->data);
		$table->class = "button-form";
		$table->colspan[8][0] = 4;
		//$table->data[8][0] .= ;
		
		if ($id_project && $project_access['manage']) {
			$table->data[8][0] = print_input_hidden ('id_project', $id_project, true);
			$table->data[8][0] .= print_input_hidden ('action', 'update', true);
			$table->data[8][0] .= print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true);
		} elseif (!$id_project) {
			$table->data[8][0] = print_input_hidden ('action', 'insert');
			$table->data[8][0] .= print_submit_button (__('Create'), 'create_btn', false, 'class="sub create"', true);
		}
		
		//$table->data .= "</td></tr>";
		print_table ($table);
	}
	echo '</div>';
	//$project_info .= "</table>";

	//echo $project_info;
	//echo print_container('project_info', __('Project info'), $project_info, 'open', false, '10px', '', '', 5, 'no_border_bottom');

}

if ($id_project) {

// Project info
echo '<div class="divform">';
echo '<table class="search-table">';
	//progress bar
	echo "<tr>";
		echo '<td colspan=2><b>'.__('Current progress').' </b></td>';
	echo "</tr><tr>";
		echo '<td colspan=2><span>';
		$completion =  format_numeric(calculate_project_progress ($id_project));
		echo progress_bar($completion, 218, 20, $graph_ttl);
		echo "</span></td>";
	echo "</tr>";
	
	// Name
	echo '<tr>';
		echo '<td colspan=2><b>'.__('Name').' </b></td>';
	echo "</tr><tr>";
		echo '<td colspan=2><input type="text" name="name" value="'.$name.'"></td>';
	echo '</tr>';
	
	// Owner
	$id_owner = get_db_value ( 'id_owner', 'tproject', 'id', $id_project);
	echo '<tr>';
		echo "<td colspan=2><b>".__('Manager')." </b></td>";
	echo "</tr><tr>";
		echo "<td colspan=2>" . print_input_text_extended ('id_owner', $owner, 'text-id_owner', '', 25, 20, false, '', '', true, '',''). "</td>" ;
	echo "</tr>";
	
	// Project group
	echo "<tr>";	
		echo "<td colspan=2><b>". __('Project group')."</b><br>";
	echo "</tr><tr>";
		if (!$clean_output) {
			echo "<td colspan=2>". print_select_from_sql ("SELECT * from tproject_group ORDER BY name", "id_project_group", $id_project_group, "", __('None'), '0', true, false, true, false) ."</td>";
		} else {
			echo "<td colspan=2>". get_db_value ("name", "tproject_group", "id", $id_project_group) ."</td>";
		}
	echo "</tr>";
	
	// CC
	echo '<tr>';
		echo '<td colspan=2><b>'.__('CC').print_help_tip (__("Email to notify changes in workunits"), true).' </b></td>';
	echo '</tr><tr>';
		echo '<td colspan=2><input type="text" name="cc" size=25 value="'.$cc.'"></td>';
	echo '</tr>';
	
	// start and end date
	echo '<tr>';
		echo '<td><b>'.__('Start').' </b>';
		echo print_input_text ('start_date', $start_date, '', 11, 20, true) ."</td>";
	echo '</tr><tr>';	
		echo '<td><b>'.__('End').' </b>';
		echo print_input_text ('end_date', $end_date, '', 11, 20, true) ."</td>";
	echo '</tr>';

	// Description
	echo '<tr>';
		echo "<td colspan=2><b>".__("Description")."</b></td>";
	echo '</tr><tr>';
		echo '<td colspan=2><textarea name="description">'. $description ."</textarea></td>";
	echo "</tr>";
	
	// Buttom
	echo "<tr><td colspan=2>";
		if ($project_access['manage']) {
			echo print_input_hidden ('id_project', $id_project, true);
			echo print_input_hidden ('action', 'update', true);
			echo print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"', true);
		} 
	echo "</td></tr>";
echo "</table>";

	// People involved
	//Get users with tasks
	$sql = sprintf("SELECT DISTINCT id_user FROM trole_people_task, ttask WHERE ttask.id_project= %d AND ttask.id = trole_people_task.id_task", $id_project);
	
	$users_aux = get_db_all_rows_sql($sql);
	
	if(empty($users_aux)) {
		$users_aux = array();
	}
	
	foreach ($users_aux as $ua) {
		$users_involved[] = $ua['id_user'];
	}
	
	//Delete duplicated items
	if (empty($users_involved)) {
		$users_involved = array();
	}
	else {
		$users_involved = array_unique($users_involved);
	}
	
	$people_involved = "<tr><td colspan='2'>";
	foreach ($users_involved as $u) {
		$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $u);
		if ($avatar != "") {
			$people_involved .= "<img src='images/avatars/".$avatar.".png' onclick='openUserInfo(\"$u\")' title='".$u."'/>";
		}
		else
			$people_involved .= "<img src='images/avatar_notyet.png' onclick='openUserInfo(\"$u\")' title='".$u."'/>";
	}
	$people_involved .= "</td></tr>";
	echo "<div class='listing-border'>";
		echo print_container('project_involved_people', __('People involved'), $people_involved);
	echo "</div>";
echo "</div>";

echo "<div class='divresult'>";	
	// Calculation
	$people_inv = get_db_sql ("SELECT COUNT(DISTINCT id_user) FROM trole_people_task, ttask WHERE ttask.id_project=$id_project AND ttask.id = trole_people_task.id_task;");
	$total_hr = get_project_workunit_hours ($id_project);
	$total_planned = get_planned_project_workunit_hours($id_project);
	$total_planned = get_planned_project_workunit_hours($id_project);

	$expected_length = get_db_sql ("SELECT SUM(hours) FROM ttask WHERE id_project = $id_project");
	$pr_hour = get_project_workunit_hours ($id_project, 1);
    $deviation = format_numeric(($pr_hour-$expected_length)/$config["hours_perday"]);
	$total = project_workunit_cost ($id_project, 1);
    $real = project_workunit_cost ($id_project, 0);

	$real = $real + get_incident_project_workunit_cost ($id_project);

	// Labour
	$labour = "<tr>";
	$labour .= '<td>'.__('Total people involved'). '</td><td>';
	$labour .= $people_inv;
	$labour .= "</td></tr>";

	$labour .= "<tr>";
	$labour .= '<td>'.__('Total workunit (hr)').'</td><td>';
	$labour .= $total_hr . " (".format_numeric ($total_hr/$config["hours_perday"]). " ".__("days"). ")";
	$labour .= "</td></tr>";

	$labour .= "<tr>";
	$labour .= '<td>'.__('Planned workunit (hr)').'</td><td>';
	$labour .= $total_planned . " (".format_numeric ($total_planned/$config["hours_perday"]). " ". __("days"). ")";
	$labour .= "</td></tr>";
	
	$labour .= "<tr>";
	$labour .= '<td>'.__('Total payable workunit (hr)').'</td><td>';
	if ($pr_hour > 0)
		$labour .= $pr_hour;
	else
		$labour .= __("N/A");
	$labour .= "</td></tr>";
	
	$labour .= "<tr>";
	$labour .= '<td>'.__('Proyect length deviation (days)').'</td><td>';
	$labour .= abs($deviation/8). " ".__('Days');
	$labour .= "</td></tr>";
	

	
	// People involved
	//Get users with tasks
	$sql = sprintf("SELECT DISTINCT id_user FROM trole_people_task, ttask WHERE ttask.id_project= %d AND ttask.id = trole_people_task.id_task", $id_project);
	
	$users_aux = get_db_all_rows_sql($sql);
	
	if(empty($users_aux)) {
		$users_aux = array();
	}
	
	foreach ($users_aux as $ua) {
		$users_involved[] = $ua['id_user'];
	}
	
	//Delete duplicated items
	if (empty($users_involved)) {
		$users_involved = array();
	}
	else {
		$users_involved = array_unique($users_involved);
	}
	
	$people_involved = "<div style='padding-bottom: 20px;'>";
	foreach ($users_involved as $u) {
		$avatar = get_db_value ("avatar", "tusuario", "id_usuario", $u);
		if ($avatar) {
			$people_involved .= "<img src='images/avatars/".$avatar.".png' width=40 height=40 onclick='openUserInfo(\"$u\")' title='".$u."'/>";
		} else {
			$people_involved .= "<img src='images/avatars/avatar_notyet.png' width=40 height=40 onclick='openUserInfo(\"$u\")' title='".$u."'/>";
		}
	}
	$people_involved .= "</div>";
	
	
	
	// Task distribution
	$task_distribution = '<div class="pie_frame">' . graph_workunit_project (350, 150, $id_project, $graph_ttl) . '</div>';

	// Budget
	$budget = "<tr>";
	$budget .= '<td>'.__('Project profitability').'</td><td>';
	if ($real > 0) {
		$budget .=  format_numeric(($total/$real)*100) . " %" ;
	} else 
		$budget .= __("N/A");
	$budget .= "</td></tr>";

	$budget .= "<tr>";
	$budget .= '<td>'.__('Deviation').'</td><td>';
	
	$deviation_percent = calculate_project_deviation ($id_project);
	$budget .= $deviation_percent ."%";
	$budget .= "</td></tr>";

	$budget .= "<tr>";
	$budget .= '<td>'.__('Project costs').'</td><td>';
	
	// Task distribution
	$task_distribution = '<div class="pie_frame">' . graph_workunit_project (350, 150, $id_project, $graph_ttl). "</div>";
	// Workload distribution
	$workload_distribution =  '<div class="pie_frame">' . graph_workunit_project_user_single (350, 150, $id_project, $graph_ttl). "</div>";
	
	// Project activity graph
	$project_activity = project_activity_graph ($id_project, 750, 300, true, 1, 50, true);
	if ($project_activity) {
		$project_activity = '<tr><td colspan="2" style="padding:20px;">' . $project_activity . '</td></tr>';
	}
	
	// Costs (client / total)
	$real = project_workunit_cost ($id_project, 0);
	$external = project_cost_invoices ($id_project);
	$total_project_costs = $external + $real;

	$budget .= format_numeric( $total_project_costs) ." ". $config["currency"];
	if ($external > 0)
		$budget .= "<span title='External costs to the project'> ($external)</span>";	
	$budget .= "</td></tr>";
	
	$total_per_profile = projects_get_cost_by_profile ($id_project, false);
	
	if (!empty($total_per_profile)) {
		foreach ($total_per_profile as $name=>$total_profile) {
			if ($total_profile) {
				$budget .= "<tr>";
				$budget .= '<td>&nbsp;&nbsp;&nbsp;&nbsp;'.__($name).'</td>';
				$budget .= '<td>'.format_numeric($total_profile)." ". $config["currency"].'</td>';
				$budget .= "</tr>";
			}
		}
	}
	
	$budget .= "<tr>";
	$budget .= '<td>'.__('Charged to customer').'</td><td>';
	$budget .= format_numeric($total) . " ". $config["currency"];
	$budget .= "</td></tr>";
	
	$total_per_profile_havecost = projects_get_cost_by_profile ($id_project, true);
	
	if (!empty($total_per_profile_havecost)) {
		foreach ($total_per_profile_havecost as $name=>$total_profile) {
			if ($total_profile) {
				$budget .= "<tr>";
				$budget .= '<td>&nbsp;&nbsp;&nbsp;&nbsp;'.__($name).'</td>';
				$budget .= '<td>'.format_numeric($total_profile)." ". $config["currency"].'</td>';
				$budget .= "</tr>";
			}
		}
	}
	
	$budget .= "<tr>";
	$budget .= '<td>'.__('Average Cost per Hour').'</td><td>';
	if ($total_hr > 0)
		$budget .= format_numeric ($total_project_costs / $total_hr) . " " . $config["currency"];
	else
		$budget .= __("N/A");
	$budget .= "</td></tr>";
	
	//Print containers
	echo print_container('project_labour', __('Labour'), $labour);
	echo "<div class='divhalf divhalf-left divhalf-border'>";
		echo print_container_div('container_pie_graphs project_workload_distribution', __('Workload distribution'), $workload_distribution);
	echo "</div>";
	echo "<div class='divhalf divhalf-right divhalf-border'>";
		echo print_container_div('container_pie_graphs project_task_distribution', __('Task distribution'), $task_distribution);
	echo "</div>";
	if ($project_activity) {
		echo print_container('project_activity', __('Project activity'), $project_activity, 'closed');
	}
	echo print_container('project_budget', __('Budget'), $budget);
}
echo "</div>";
echo "</form>";

//div to show user info
echo "<div class= 'dialog ui-dialog-content' title='".__("User info")."' id='user_info_window'></div>";

?>
<script type="text/javascript" src="include/js/jquery.ui.slider.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.autocomplete.js"></script>
<script type="text/javascript" src="include/js/integria_incident_search.js"></script>

<script type="text/javascript">

add_ranged_datepicker ("#text-start_date", "#text-end_date", null);

$(document).ready (function () {
	$("textarea").TextAreaResizer ();
	
	var idUser = "<?php echo $config['id_user'] ?>";
	bindAutocomplete ("#text-id_owner", idUser);	
});

// Form validation
trim_element_on_submit('input[name="name"]');
validate_form("#form-new_project");
// #text-id_owner
validate_user ("#form-new_project", "#text-id_owner", "<?php echo __('Invalid user')?>");
var rules, messages;

// Rules: input[name="name"]
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
          page: "include/ajax/remote_validations",
          search_existing_project: 1,
          project_name: function() { return $('input[name="name"]').val() },
          project_id: <?php echo $id_project; ?>
        }
	}
};
messages = {
	required: "<?php echo __('Name required'); ?>",
	remote: "<?php echo __('This project already exists'); ?>"
};
add_validate_form_element_rules('input[name="name"]', rules, messages);

</script>
