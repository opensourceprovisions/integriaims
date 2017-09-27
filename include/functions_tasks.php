<?php 

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.


/**
 * Calculate task completion porcentage and set on task
 *
 * @param int Id of the task to calculate.
 */
function set_task_completion ($id_task) {
	$hours_worked = task_duration_recursive ($id_task);
	$hours_estimated = get_db_value ('hours', 'ttask', 'id', $id_task);

	if($hours_estimated == 0) {
		return 0;
	}
	
	$percentage_completed = ($hours_worked*100)/$hours_estimated;
	process_sql_update ('ttask', array('completion' => $percentage_completed), array('id' => $id_task));

	return $percentage_completed;
}

function get_task_completion ($id_task) {
	$hours_worked = task_duration_recursive ($id_task);
	$hours_estimated = get_db_value ('hours', 'ttask', 'id', $id_task);

	if($hours_estimated == 0) {
		return 0;
	}
	
	$percentage_completed = ($hours_worked*100)/$hours_estimated;

	$percentage_completed = round($percentage_completed);

	return $percentage_completed;
}

/**
* Return total hours assigned to task and subtasks (recursive)
*
* $id_task	integer 	ID of task
**/

function task_duration_recursive ($id_task){
	
	// Get all childs for this task
	$tasks = get_db_all_rows_sql ("SELECT id FROM ttask WHERE id_parent_task = '$id_task'");
	if ($tasks === false) {
		// No parents ?, break recursion and give WU/hr for this task.
		$tasks = array();
	}
	
	$sum = 0;
	foreach ($tasks as $task) {
		$sum += $sum + get_task_workunit_hours ($task['id']);
		task_duration_recursive ($task['id']);
	}
	return $sum + get_task_workunit_hours ($id_task);
}

/**
* Return total cost assigned to task on external costs attached
*
* $id_task	integer 	ID of task
**/

function task_cost_invoices ($id_task){
	$total = get_db_sql ("SELECT SUM(amount1+amount2+amount3+amount4+amount5) FROM tinvoice WHERE id_task = $id_task");
	return $total;
}

/**
* Return total cost assigned to task on external costs attached
*
* $id_task	integer 	ID of task
**/

function project_cost_invoices ($id_project){
	
	$tasks = get_db_all_rows_sql ("SELECT * FROM ttask WHERE id_project = $id_project");
	if ($tasks === false)
		$tasks = array ();
	
	$total = 0;
	foreach ($tasks as $task) {
		$total += task_cost_invoices ($task["id"]);
	}	
	return $total;
}

/**
* Return total hours assigned to project (planned)
*
* $id_project	integer 	ID of project
**/

function get_planned_project_workunit_hours ($id_project){ 
	global $config;
	
	$total = 0;
	$total = (int) get_db_sql ("SELECT SUM(hours) FROM ttask WHERE id_project = $id_project");
	return $total;
}

function tasks_print_tree ($id_project, $sql_search = '') {
	global $config;
	global $pdf_output;
	
	if ($pdf_output) {
		$graph_ttl = 2;
	} else {
		$graph_ttl = 1;
	}
	
	echo "<table class='blank' style='width:100%'>";
	echo "<tr><td style='width:60%' valign='top'>";
	
	$sql = "SELECT t.*
			FROM ttask t
			WHERE t.id_parent_task=0
				AND t.id>0
				AND t.id_project=$id_project
				$sql_search
			ORDER BY t.name";
	
	//$sql_search = base64_encode($sql_search);

	$sql_count = "SELECT COUNT(*) AS num
			FROM ttask t
			WHERE t.id_parent_task=0
				AND t.id>0
				AND t.id_project=$id_project
				$sql_search";
	
	$countRows = process_sql ($sql_count);
	
	if ($countRows === false)
		$countRows = 0;
	else
		$countRows = (int) $countRows[0]['num'];
	
	if ($countRows == 0) {
		echo ui_print_error_message (__('No tasks found'), '', true, 'h3', true);
		return;
	}
	
	$new = true;
	$count = 0;
	
	echo "<ul style='margin: 0; margin-top: 20px; padding: 0;'>\n";
	$first = true;
	
	while ($task = get_db_all_row_by_steps_sql($new, $result, $sql)) {
		
		$new = false;
		$count++;
		echo "<li style='margin: 0; padding: 0;'>";
		echo "<span style='display: inline-block;'>";
		
		$branches = array ();
		
		if ($first) {
			if ($count != $countRows) {
				$branches[] = true;
				$img = print_image ("images/tree/first_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image".$task['id']."_task_". $task['id'], "pos_tree" => "0"));
				$first = false;
			}
			else {
				$branches[] = false;
				$img = print_image ("images/tree/one_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image".$task['id']."_task_". $task['id'], "pos_tree" => "1"));
			}
		}
		else {
			if ($count != $countRows) {
				$branches[] = true;
				$img = print_image ("images/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image".$task['id']."_task_". $task['id'], "pos_tree" => "2"));
			} else {
				$branches[] = false;
				$img = print_image ("images/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image".$task['id']."_task_". $task['id'], "pos_tree" => "3"));
			}
		}
		
		$task_access = get_project_access ($config["id_user"], $id_project, $task["id"], false, true);
		if ($task_access["read"]) {
			
			// Background color
			if ($task["completion"] < 40) {
				$background_color = "background: #FFFFFF;";
			} else if ($task["completion"] < 90) {
				$background_color = "background: #FFE599;";
			} else if ($task["completion"] < 100) {
				$background_color = "background: #A4BCFA;";
			} else if ($task["completion"] == 100) {
				$background_color = "background: #B6D7A8;";
			} else {
				$background_color = "";
			}
			
			// Priority
			$priority = print_priority_flag_image ($task['priority'], true);
			
			// Task name
			$name = safe_output($task['name']);
			
			if (strlen($name) > 30) {
				$name = substr ($name, 0, 30) . "...";
				$name = "<a title='".safe_output($task['name'])."' href='index.php?sec=projects&sec2=operation/projects/task_detail
					&id_project=".$task['id_project']."&id_task=".$task['id']."&operation=view'>".$name."</a>";
			} else {
				$name = "<a href='index.php?sec=projects&sec2=operation/projects/task_detail
					&id_project=".$task['id_project']."&id_task=".$task['id']."&operation=view'>".$name."</a>";
			}
			if ($task["completion"] == 100) {
				$name = "<s>$name</s>";
			}
			
			// Time used on all child tasks + this task
			$recursive_timeused = task_duration_recursive ($task["id"]);
			
			// Completion
			$progress = progress_bar($recursive_timeused, 70, 20, $graph_ttl);
			
			// Estimation
			$imghelp = "Estimated hours = ".$task['hours'];
			$taskhours = get_task_workunit_hours ($task['id']);
			$imghelp .= ", Worked hours = $taskhours";
			$a = round ($task["hours"]);
			$b = round ($recursive_timeused);
			$mode = 2;
			
			if ($a > 0)
				$estimation = histogram_2values($a, $b, __("Planned"), __("Real"), $mode, 60, 18, $imghelp, $graph_ttl);
			else
				$estimation = "--";
			
			$time_used = _('Time used') . ": ";
			
			if ($taskhours == 0)
				$time_used .= "--";
			elseif ($taskhours == $recursive_timeused)
				$time_used .= $taskhours;
			else
				$time_used .= "<span title='".__('Total')."'>" .$recursive_timeused. "</span>". "<span title=".__('Task and Tickets')."> (".$taskhours.")</span>";
				
			$wu_incidents = get_incident_task_workunit_hours ($task["id"]);
		
			if ($wu_incidents > 0)
			$time_used .= "<span title='".__("Task Tickets")."'> (".$wu_incidents.")</span>";
			
			// People
			$people = combo_users_task ($task['id'], 1, true);
			$people .= ' ';
			$people .= get_db_value ('COUNT(DISTINCT(id_user))', 'trole_people_task', 'id_task', $task['id']);
			
			// Branches
			$branches_json = json_encode ($branches);
			
			// New WO / Incident
			$wo_icon = print_image ("images/paste_plain.png", true, array ("style" => 'vertical-align: middle;', "id" => "wo_icon", "title" => __('Work Unit')));
			$incident_icon = print_image ("images/incident.png", true, array ("style" => 'vertical-align: middle; height:19px; width:20px;', "id" => "incident_icon", "title" => __('Ticket')));;
			$wo_icon = "<a href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_project=".$task['id_project']."&id_task=".$task['id']."'>$wo_icon</a>";
			$incident_icon = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail&id_task=".$task['id']."'>$incident_icon</a>";
			$launch_icons = $wo_icon . "&nbsp;" . $incident_icon;
			
			echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$task['id'].",\"".$sql_search."\",0)'>";
			echo "<script type=\"text/javascript\">
					  $(document).ready (function () {
						  loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$task['id'].",\"".$sql_search."\",0);
					  });
				  </script>";
			echo $img;
			echo "</a>";
			echo "<span style='".$background_color." padding: 4px;'>";
			echo "<span style='vertical-align:middle; display: inline-block;'>".$priority."</span>";
			echo "<span style='margin-left: 5px; min-width: 250px; vertical-align:middle; display: inline-block;'>".$name."</span>";
			echo "<span title='" . __('Progress') . "' style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".$progress."</span>";
			echo "<span style='margin-left: 15px; min-width: 70px; vertical-align:middle; display: inline-block;'>".$estimation."</span>";
			echo "<span style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".$people."</span>";
			echo "<span style='margin-left: 15px; min-width: 200px; display: inline-block;'>".$time_used."</span>";
			echo "<span style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".__('New').": ".$launch_icons."</span>";
			echo "</span>";
		} else {
			
			// Task name
			$name = safe_output($task['name']);
			
			if (strlen($name) > 60) {
				$name = substr ($name, 0, 60) . "...";
				$name = "<div title='".safe_output($task['name'])."'>".$name."</a>";
			}
			if ($task["completion"] == 100) {
				$name = "<s>$name</s>";
			}
			
			// Priority
			$priority = print_priority_flag_image ($task['priority'], true);
			
			// Branches
			$branches_json = json_encode ($branches);
			
			echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$task['id'].",\"".$sql_search."\")'>";
			echo "<script type=\"text/javascript\">
					  $(document).ready (function () {
						  loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$task['id'].",\"".$sql_search."\");
					  });
				  </script>";
			echo $img;
			echo "</a>";
			echo "<span title='".__('You are not assigned to this task')."' style='padding: 4px;'>";
			echo "<span style='vertical-align:middle; display: inline-block;'>".$priority."</span>";
			echo "<span style='color: #D8D8D8; margin-left: 5px; display: inline-block;'>".$name."</span>";
			echo "</span>";
		}
		
		echo "<div hiddenDiv='1' loadDiv='0' style='display: none; margin: 0px; padding: 0px;' class='tree_view tree_div_".$task['id']."' id='tree_div".$task['id']."_task_".$task['id']."'></div>";
		echo "</li>";
	}
	
	echo "</ul>";
	echo "</td></tr>";
	echo "</table>";
	
	return;
}

?>
