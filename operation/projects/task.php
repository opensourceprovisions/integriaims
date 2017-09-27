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

include_once ("include/functions_graph.php");
include_once ("include/functions_tasks.php");
include_once ("include/functions_projects.php");



$graph_ttl = 1;

if ($pdf_output) {
	$graph_ttl = 2;
}

if (defined ('AJAX')) {
	
	global $config;
	
	$print_subtree = get_parameter('print_subtree', 0);
	
	$id_project = get_parameter ('id_project');
	
	// ACL
	$project_access = get_project_access ($config["id_user"], $id_project);
	if (! $project_access["read"]) {
		// Doesn't have access to this page
		return;
	}
	
	$id_item = get_parameter ('id_item');
	$branches_json = get_parameter('branches_json');
	$orden_tree = get_parameter('orden_tree');
	$branches = json_decode ($branches_json, true);
	$id_father = get_parameter('id_father');
	$sql_search = base64_decode(get_parameter('sql_search', ''));
	
	if ($print_subtree) {
		
		$sql_tasks = "SELECT t.*
					  FROM ttask t
					  WHERE t.id_parent_task=$id_item
						  AND t.id>0
						  AND t.id_project=$id_project
						  $sql_search
					  ORDER BY t.name";
		
		$sql_tasks_count = "SELECT COUNT(*)
							FROM ttask t
							WHERE t.id_parent_task=$id_item
								AND t.id>0
								AND t.id_project=$id_project
								$sql_search";
		
		if (dame_admin($config['id_user'])) {
			$sql_wo = "SELECT tw.*
					    FROM tworkunit tw
						 INNER JOIN tworkunit_task twt ON
						 tw.id = twt.id_workunit
						 AND id_task=$id_item";
			$sql_wo_count = "SELECT COUNT(*)
							  FROM tworkunit tw
							 INNER JOIN tworkunit_task twt ON
							 tw.id = twt.id_workunit
							 AND id_task=$id_item";
		} else {
			$sql_wo = "SELECT tw.*
					   FROM tworkunit tw
							 INNER JOIN tworkunit_task twt ON
							 tw.id = twt.id_workunit
							 AND id_task=$id_item
						  WHERE id_user='".$config['id_user']."'";
			$sql_wo_count = "SELECT COUNT(*)
							 FROM tworkunit tw
							 INNER JOIN tworkunit_task twt ON
							 tw.id = twt.id_workunit
							 AND id_task=$id_item
							WHERE id_user='".$config['id_user']."'";
		}
		
		if (dame_admin($config['id_user'])) {
			$sql_incidents = "SELECT *
							  FROM tincidencia
							  WHERE id_task=$id_item
								  AND estado<>7
							  ORDER BY titulo";
			$sql_incidents_count = "SELECT COUNT(*)
									FROM tincidencia
									WHERE id_task=$id_item
										AND estado<>7";
		} else {
			$sql_incidents = "SELECT *
							  FROM tincidencia
							  WHERE id_task=$id_item
								 AND estado<>7
								 AND (id_usuario='".$config['id_user']."'
									 OR id_creator='".$config['id_user']."')
							  ORDER BY titulo";
			$sql_incidents_count = "SELECT COUNT(*)
									FROM tincidencia
									WHERE id_task=$id_item
										AND estado<>7
										AND (id_usuario='".$config['id_user']."'
											OR id_creator='".$config['id_user']."')";
		}
		
		$countRows = process_sql ($sql_tasks_count);
		$countWOs = process_sql ($sql_wo_count);
		$countIncidents = process_sql ($sql_incidents_count);
		
		if ($countRows === false)
			$countRows = 0;
		else
			$countRows = (int) $countRows[0][0];
		
		if ($countWOs === false)
			$countWOs = 0;
		else
			$countWOs = (int) $countWOs[0][0];
			
		if ($countIncidents === false)
			$countIncidents = 0;
		else
			$countIncidents = (int) $countIncidents[0][0];
		
		if ($countRows == 0 && $countWOs == 0 && $countIncidents == 0) {
			ob_clean();
			return;
		}
		
		// TASKS
		$new = true;
		$count = 0;
		echo "<ul style='margin: 0; padding: 0;'>\n";
		
		while ($task = get_db_all_row_by_steps_sql($new, $result, $sql_tasks)) {
			$sql_count_issue = "select count(*) as num from tincidencia where id_task=".($task['id']);
			$count_issue = process_sql ($sql_count_issue);
			$new = false;
			$count++;
			echo "<li style='margin: 0; padding: 0;'>";
			echo "<span style='display: inline-block;'>";
			
			$branches_aux = $branches;
			
			foreach ($branches as $branch) {
				if ($branch) {
					print_image ("images/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
				} else {
					print_image ("images/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
				}
			}
			
			if ($count < $countRows || $countWOs > 0 || $countIncidents > 0) {
				$branches_aux[] = true;
				$img = print_image ("images/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "2"));
			} else {
				$branches_aux[] = false;
				$img = print_image ("images/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "3"));
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
				//~ debugPrint("************************************",true);
				//~ debugPrint($name,true);
				//~ debugPrint($countRows,true);
				//~ debugPrint($countWOs,true);
				//~ debugPrint($countIncidents,true);
				//~ debugPrint("************************************",true);
				
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
				
				// Completion
				$progress = progress_bar($task['completion'], 70, 20, $graph_ttl);
				
				// Estimation
				$imghelp = "Estimated hours = ".$task['hours'];
				$taskhours = get_task_workunit_hours ($task['id']);
				$imghelp .= ", Worked hours = $taskhours";
				$a = round ($task["hours"]);
				$b = round ($taskhours);
				$mode = 2;
				
				if ($a > 0)
					$estimation = histogram_2values($a, $b, __("Planned"), __("Real"), $mode, 60, 18, $imghelp, $graph_ttl);
				else
					$estimation = "--";
				
				// Time used on all child tasks + this task
				$recursive_timeused = task_duration_recursive ($task["id"]);
				
				$time_used = __('Time used') . ": ";
				
				if ($taskhours == 0)
					$time_used .= "--";
				elseif ($taskhours == $recursive_timeused)
					$time_used .= $taskhours;
				else
					$time_used .= $taskhours . "<span title='Subtasks WU/HR'> (".$recursive_timeused. ")</span>";
					
				$wu_incidents = get_incident_task_workunit_hours ($task["id"]);
			
				if ($wu_incidents > 0)
				$time_used .= "<span title='".__("Time spent in related tickets")."'> ($wu_incidents)</span>";
				
				// People
				$people = combo_users_task ($task['id'], 1, true);
				$people .= ' ';
				$people .= get_db_value ('COUNT(DISTINCT(id_user))', 'trole_people_task', 'id_task', $task['id']);
				
				// Branches
				$branches_json = json_encode ($branches_aux);
				
				if ($name == 'Task1')
					debugPrint($countRows,true);
				$width = 250 - ($orden_tree * 15) . 'px';
				
				// New WO / Incident
				$wo_icon = print_image ("images/paste_plain.png", true, array ("style" => 'vertical-align: middle;', "id" => "wo_icon", "title" => __('Work unit')));
				$incident_icon = print_image ("images/incident.png", true, array ("style" => 'vertical-align: middle;', "id" => "incident_icon", "title" => __('Ticket')));;
				$wo_icon = "<a href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_project=".$task['id_project']."&id_task=".$task['id']."'>$wo_icon</a>";
				$incident_icon = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail&id_task=".$task['id']."'>$incident_icon</a>";
				$launch_icons = $wo_icon . "&nbsp;" . $incident_icon;
				
				echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$id_item.",\"".$sql_search."\",".$orden_tree.")'>";
				echo "<script type=\"text/javascript\">
						  loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$id_item.",\"".$sql_search."\",".$orden_tree.");
					  </script>";
				echo $img;
				echo "</a>";
				echo "<span style='".$background_color." padding: 4px;'>";
				echo "<span style='vertical-align:middle; display: inline-block;'>".$priority."</span>";
				echo "<span style='margin-left: 5px; min-width: $width; vertical-align:middle; display: inline-block;'>".$name."</span>";
				echo "<span title='" . __('Progress') . "' style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".$progress."</span>";
				echo "<span style='margin-left: 15px; min-width: 70px; vertical-align:middle; display: inline-block;'>".$estimation."</span>";
				echo "<span style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".$people."</span>";
				echo "<span style='margin-left: 15px; min-width: 200px; display: inline-block;'>".$time_used."</span>";
				echo "<span style='margin-left: 15px; vertical-align:middle; display: inline-block;'>".__('New').": ".$launch_icons."</span>";
				echo "</span>";
			} else {
				
				// Task name
				$name = safe_output($task['name']);
				if ($name == 'Task1')
					debugPrint($countRows,true);
					
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
				$branches_json = json_encode ($branches_aux);
				
				echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$id_item.",\"".$sql_search."\")'>";
				echo "<script type=\"text/javascript\">
						  loadTasksSubTree(".$task['id_project'].",".$task['id'].",\"".$branches_json."\", ".$id_item.",\"".$sql_search."\");
					  </script>";
				echo $img;
				echo "</a>";
				echo "<span title='".__('You are not assigned to this task')."' style='padding: 4px;'>";
				echo "<span style='vertical-align:middle; display: inline-block;'>".$priority."</span>";
				echo "<span style='color: #D8D8D8; margin-left: 5px; display: inline-block;'>".$name."</span>";
				echo "</span>";
				
			}
			
			echo "<div hiddenDiv='1' loadDiv='0' style='display: none; margin: 0px; padding: 0px;' class='tree_view tree_div_".$task['id']."' id='tree_div".$id_item."_task_".$task['id']."'></div>";
			echo "</li>";
		}
		
		// WORK ORDERS
		$new = true;
		$count = 0;
		
		while ($wo = get_db_all_row_by_steps_sql($new, $result, $sql_wo)) {
			
			$new = false;
			$count++;
			echo "<li style='margin: 0; padding: 0;'>";
			echo "<span style='display: inline-block;'>";
			
			foreach ($branches as $branch) {
				if ($branch) {
					print_image ("images/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
				} else {
					print_image ("images/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
				}
			}
			
			if ($count < $countWOs || $countIncidents > 0) {
				$img = print_image ("images/tree/leaf.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "2"));
			} else {
				$img = print_image ("images/tree/last_leaf.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "3"));
			}
			
			// Background color
			if ($wo["progress"] == 0 && $wo["end_date"] != "0000-00-00 00:00:00") {
				if ($wo["end_date"] < date('Y-m-d H:i:s')) {
					$background_color = "background: #fff0f0;";
				}
			} elseif ($wo["progress"] == 1) {
				$background_color = "background: #f0fff0;";
			} elseif ($wo["progress"] == 2) {
				$background_color = "background: #f0f0ff;";
			} else {
				$background_color = "";
			}
			
			// WO icon
			$wo_icon = "<a href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_task=" . 
							$id_item."&id_workunit=".$wo['id']."'>" . 
								print_image ("images/paste_plain.png", 
									true, array ("style" => 
									'vertical-align: middle;', 
									"id" => "wo_icon", 
									"title" => __('Work unit'))) . 
						"</a>";
			
			// Priority
			$priority = print_priority_flag_image ($wo['priority'], true);
			
			// WO name
			$name = safe_output($wo['description']);
			
			if (strlen($name) > 48) {
				$name = substr ($name, 0, 48) . "...";
				$name = "<a title='".safe_output($wo['name'])."'
					href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_task=".$id_item."&id_workunit=".$wo['id']."'>".$name."</a>";
			} else {
				$name = "<a href='index.php?sec=projects&sec2=operation/users/user_spare_workunit&id_task=".$id_item."&id_workunit=".$wo['id']."'>".$name."</a>";
			}
			if ($wo["progress"] > 0) {
				$name = "<s>$name</s>";
			}
			
			// Owner
			$owner = safe_output($wo['id_user']);
			if (strlen($owner) > 10) {
				//~ $owner = "<a title='".safe_output($wo['assigned_user'])."'
					//~ href='index.php?sec=projects&sec2=operation/workorders/wo&owner="
						//~ .$owner."'>".substr ($owner, 0, 10)."...</a>";
				$owner = substr ($owner, 0, 10);
			} else {
				//~ $owner = "<a href='index.php?sec=projects&sec2=operation/workorders/wo&owner="
					//~ .$owner."'>".$owner."</a>";
			}
			
			
			echo $img;
			echo "<span style='".$background_color." padding: 4px;'>";
			echo "<span style='vertical-align:middle; display: inline-block;'>".$wo_icon."</span>";
			echo "<span style='margin-left: 4px; vertical-align:middle; display: inline-block;'>".$priority."</span>";
			echo "<span style='margin-left: 5px; min-width: 400px; vertical-align:middle; display: inline-block;'>".$name."</span>";
			echo "<span style='margin-left: 5px; min-width: 140px; vertical-align:middle; display: inline-block;'><small>"
				.__('Owner').":</small> <b>".$owner."</b></span>";
			echo "<span style='margin-left: 5px; min-width: 140px; vertical-align:middle; display: inline-block;'><small>"
				.__('Time used ').":</small> <b>".$wo['duration']."</b></span>";
			echo "</span>";
			echo "</li>";
		}
		
		// INCIDENTS
		$new = true;
		$count = 0;
		
		while ($incident = get_db_all_row_by_steps_sql($new, $result, $sql_incidents)) {
			
			$new = false;
			$count++;
			echo "<li style='margin: 0; padding: 0;'>";
			echo "<span style='display: inline-block;'>";
			
			foreach ($branches as $branch) {
				if ($branch) {
					print_image ("images/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
				} else {
					print_image ("images/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
				}
			}
			
			if ($count < $countIncidents) {
				$img = print_image ("images/tree/leaf.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "2"));
			} else {
				$img = print_image ("images/tree/last_leaf.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id_item. "_task_" . $task["id"], "pos_tree" => "3"));
			}
			
			// Background color
			if ($incident["estado"] < 3) {
				$background_color = "background: #FFDAD3;";
			} elseif ($incident["estado"] < 7) {
				$background_color = "background: #FFFCA0;";
			} elseif ($incident["estado"] == 7) {
				$background_color = "background: #DAFFCC;";
			} else {
				$background_color = "";
			}
			
			// Incident icon
			$incident_icon = print_image ("images/incident.png", true, 
				array ("style" => 'vertical-align: middle;', "id" => "incident_icon", "title" => __('Ticket')));
			
			// Priority / Criticity
			$priority = print_priority_flag_image ($incident['prioridad'], true);
			
			// Incident name
			$name = safe_output($incident['titulo']);
			
			if (strlen($name) > 48) {
				$name = substr ($name, 0, 48) . "...";
				$name = "<a title='".safe_output($incident['titulo'])."'
					href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia']."'>".$name."</a>";
			} else {
				$name = "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia']."'>".$name."</a>";
			}
			if ($incident["estado"] == 7) {
				$name = "<s>$name</s>";
			}
			
			// Owner
			$owner = safe_output($incident['id_usuario']);
			if (strlen($owner) > 10) {
				$owner = "<div style='display: inline-block;' title='".safe_output($incident['id_usuario'])."'>".substr ($owner, 0, 10)."...</div>";
			}
			
			// Submitter
			$submitter = safe_output($incident['id_creator']);
			if (strlen($submitter) > 10) {
				$submitter = "<div style='display: inline-block;' title='".safe_output($incident['id_creator'])."'>".substr ($submitter, 0, 10)."...</div>";
			}
			
			// Status
			$status = get_db_value("name", "tincident_status", "id", $incident['estado']);
			if (strlen($status) > 10) {
				$status = "<div style='display: inline-block;' title='$status'>".substr ($status, 0, 10)."...</div>";
			}
			
			echo $img;
			echo "<span style='".$background_color." padding: 4px;' class='red'>";
			echo "<span style='vertical-align:middle; display: inline-block;'>".$incident_icon."</span>";
			echo "<span style='margin-left: 3px; vertical-align:middle; display: inline-block;'>".$priority."</span>";
			echo "<span style='margin-left: 5px; min-width: 400px; vertical-align:middle; display: inline-block;'>".$name."</span>";
			echo "<span style='margin-left: 5px; min-width: 140px; vertical-align:middle; display: inline-block;'><small>"
				.__('Owner').":</small> <b>".$owner."</b></span>";
			echo "<span style='margin-left: 5px; min-width: 140px; vertical-align:middle; display: inline-block;'><small>"
				.__('Creator').":</small> <b>".$submitter."</b></span>";
			if ($status) {
				echo "<span style='margin-left: 5px; vertical-align:middle; display: inline-block;'><small>"
					.__('Status').":</small> <b>".$status."</b></span>";
			}
			echo "</span>";
			echo "</span>";
			echo "</li>";
		}

		echo "</ul>";
		
		return;
	}
}


$id_project = (int) get_parameter ("id_project");

// ACL
if (! $id_project) {
	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task manager without project");
	no_permission ();
}
$project_access = get_project_access ($config["id_user"], $id_project);
if (! $project_access["read"]) {
	// Doesn't have access to this page
	audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task manager of unauthorized project");
	no_permission ();
}

$project = get_db_row ('tproject', 'id', $id_project);

$id_task = (int) get_parameter ('id');
$operation = (string) get_parameter ('operation');

if ($operation == 'move') {
	
	// ACL
	$task_access = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	if (! $task_access["manage"]) {
		// Doesn't have access to this page
		audit_db($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to move a task without permission");
		no_permission ();
	}
	
	$target_project = get_parameter ("target_project");
	$id_task = get_parameter ("id_task");
	if ((dame_admin($config['id_user'])==1) OR (project_manager_check ($id_project) == 1)){
		$sql = sprintf ('UPDATE ttask
			SET id_project = %d,
			id_parent_task = 0
			WHERE id = %d', $target_project, $id_task);
		process_sql ($sql);
		
		// Move subtasks of this task
		$sql = sprintf ('UPDATE ttask
			SET id_project = %d WHERE id_parent_task = %d', $target_project, $id_task);
		process_sql ($sql);
		
		
		task_tracking ($id_task, TASK_MOVED);
	}
	else {
		no_permission ();
	}
}

// MAIN LIST OF TASKS
$search_text = (string) get_parameter ('search_text', '');


	if (!$pure) {
		// Print title and menu.
		$section_title = __('Task management');
		$section_subtitle = $project['name'];
		$p_menu = print_project_tabs('task_list');
		print_title_with_menu ($section_title, $section_subtitle, "task", 'projects', $p_menu, 'task_list');
	 }
	 else {
		echo '<h2>'.__('Task management').'</h2>';
		echo '<h4>'.$project['name'];
		echo "<div id='button-bar-title'>";
		echo "<ul>";
		echo '<li class="ui-tabs">';
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=$id_project'>".print_image ("images/flecha_volver.png", true, array("title" => __("Back to task list")))."</a>";
		echo '</li>';
		echo "</ul>";
		echo "</div>";
		echo '</h4>';
	 }


$where_clause = ' 1=1 ';
if ($search_text != "")
	$where_clause .= sprintf (' AND name LIKE "%%%s%%" OR description LIKE "%%%s%%"',
		$search_text, $search_text);

// DON'T DELETE THIS YET - TEMPORARILY COMMENTED
//~ $table->width = '400px';
//~ $table->class = 'search-table';
//~ $table->style = array ();
//~ $table->style[0] = 'font-weight: bold;';
//~ $table->style[2] = 'font-weight: bold;';
//~ $table->data = array ();
//~ $table->data[0][0] = __('Search');
//~ $table->data[0][1] = print_input_text ("search_text", $search_text, "", 25, 100, true);
//~ $table->data[0][2] = print_submit_button (__('Search'), "search_btn", false, 'class="sub search"', true);
//~ 
//~ echo '<form method="post">';
//~ if ($clean_output == 0)
    //~ print_table ($table);
//~ echo '</form>';

if ($clean_output == 1) {
	
	unset ($table);

	$table->width = '90%';
	$table->class = 'listing';
	$table->data = array ();
	$table->style = array ();
	$table->style[0] = 'font-size: 11px;';
	$table->head = array ();
	$table->head[0] = __('Name');
	$table->head[1] = __('Pri');
	$table->head[2] = __('Progress');
	$table->head[3] = __('Estimation');
	$table->head[4] = __('Time used');
	$table->head[5] = __('People');
	$table->head[6] = __('Start/End');
	$table->align = array ();
	$table->align[1] = 'left';
	$table->align[2] = 'center';
	$table->align[3] = 'center';
	$table->align[4] = 'center';
	$table->align[8] = 'center';

	$table->style[6] = "font-size: 9px";

	echo project_activity_graph ($id_project, 650, 150, false, $graph_ttl);

	$color = 1;

	show_task_tree ($table, $id_project, 0, 0, $where_clause);
	
	if (empty($table->data)) {
		echo ui_print_error_message (__('No tasks found'), '', true, 'h3', true);
	}
	else {
		print_table ($table);
	}
	
} else {
	tasks_print_tree ($id_project);
}

function show_task_row ($table, $id_project, $task, $level) {
	global $config;
	global $graph_ttl;
	
	$data = array ();

	// Task  name
	$data[0] = '';
	
	for ($i = 0; $i < $level; $i++)
		$data[0] .= '<img src="images/small_arrow_right_green.gif" style="position: relative; top: 5px;"> ';
		
	
	$data[0] .= '<a href="index.php?sec=projects&sec2=operation/projects/task_detail&id_project='. $id_project.'&id_task='.$task['id'].'&operation=view">'. $task['name'].'</a>';

	// Priority
    $data[1] = print_priority_flag_image ($task['priority'], true);
	
	// Completion
	
	$data[2] = progress_bar($task["completion"], 70, 20, $graph_ttl);
	
	// Estimation
	$imghelp = "Estimated hours = ".$task["hours"];
	$taskhours = get_task_workunit_hours ($task["id"]);

	$imghelp .= ", Worked hours = $taskhours";
	$a = round ($task["hours"]);
	$b = round ($taskhours);
	$mode = 2;

	if ($a > 0)
		$data[3] = histogram_2values($a, $b, __("Planned"), __("Real"), $mode, 60, 18, $imghelp, $graph_ttl);
	else
		$data[3] = '--';

	// Time used on all child tasks + this task
	$recursive_timeused = task_duration_recursive ($task["id"]);
	
	if ($taskhours == 0)
		$data[4] = "--";
	elseif ($taskhours == $recursive_timeused)
		$data[4] = $taskhours;
	else
		$data[4] = $taskhours . "<span title='Subtasks WU/HR'> (".$recursive_timeused. ")</span>";
		
	$wu_incidents = get_incident_task_workunit_hours ($task["id"]);
	
	
	if ($wu_incidents > 0)
	$data[4] .= "<span title='".__("Time spent in related tickets")."'> ($wu_incidents) </span>";

	// People
	$data[5] = combo_users_task ($task['id'], 1, true);
	$data[5] .= ' ';
	$data[5] .= get_db_value ('COUNT(DISTINCT(id_user))', 'trole_people_task', 'id_task', $task['id']);

	if ($task["start"] == $task["end"]){
		$data[6] = date ('Y-m-d', strtotime ($task['start'])) . "<br>";
		$data[6] .= __('Recurrence').': '.get_periodicity ($task['periodicity']);
	} else {
		// Start
		$start = strtotime ($task['start']);
		$end = strtotime ($task['end']);
		$now = time ();
		
		$data[6] = date ('Y-m-d', $start) ."<br>";
		
		if ($task['completion'] == 100) {
			$data[6] .= '<span style="color: green">';
		} else {
			if ($now > $end)
				$data[6] .= '<span style="color: red">';
			else
				$data[6] .= '<span>';
		}
		$data[6] .= date ('Y-m-d', $end);
		$data[6] .= '</span>';
	}

	array_push ($table->data, $data);
}

function show_task_tree (&$table, $id_project, $level, $id_parent_task, $where_clause) {
	global $config;
	
	$sql = sprintf ('SELECT * FROM ttask
		WHERE %s
		AND id_project = %d
		AND id_parent_task = %d
		ORDER BY name',
		$where_clause, $id_project, $id_parent_task);
	$tasks = get_db_all_rows_sql ($sql);
	if ($tasks === false)
		return;
	foreach ($tasks as $task) {
		if (user_belong_task ($config['id_user'], $task['id']))
			show_task_row ($table, $id_project, $task, $level);
		show_task_tree ($table, $id_project, $level + 1, $task['id'], $where_clause);
	}
}

?>

<script type="text/javascript" src="include/js/integria_projects.js"></script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript">

// Form validation
trim_element_on_submit("#text-search_text");

</script>
