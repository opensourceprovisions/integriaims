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

require_once ('include/functions_db.php');
require_once ('include/functions_projects.php');
require_once ('include/functions_tasks.php');

$project_tasks = (int) get_parameter ('project_tasks');
$show_actual = (int) get_parameter('show_actual');
$update_task = (int) get_parameter('update_task');
$update_task_parent = (int) get_parameter('update_task_parent');
$get_task_links = (int) get_parameter('get_task_links');
$get_task_editor = (int) get_parameter('get_task_editor');
$delete_task = (int) get_parameter('delete_task');
$create_task = (int) get_parameter('create_task');
$get_task_statistics = (int) get_parameter('get_task_statistics');
$get_task_completion_hours = (int) get_parameter('get_task_completion_hours');
$check_link = (int) get_parameter("check_link");
$get_calculator = (int) get_parameter("get_calculator");
$get_workunits_task = (int) get_parameter('get_workunits_task', 0);
$get_scale = get_parameter('scale', 'month');

function fix_date ($date, $default='') {
	$date_array = preg_split ('/[\-\s]/', $date);
	if (sizeof($date_array) < 3) {
		return false;
	}

	if ($default != '' && $date_array[0] == '0000') {
		return $default;
	}
		
	return sprintf ('%02d/%02d/%04d', $date_array[2], $date_array[1], $date_array[0]);
}

function get_tasks_gantt (&$tasks, $project_id, $project_start, $project_end, $parent_id = 0, $depth = 0, $show_actual = 0) {
	global $config;

	$id_user = $config["id_user"];
    $result = mysql_query ('SELECT * FROM ttask 
                            WHERE id_parent_task = ' . $parent_id
                            . ' AND id_project = ' . $project_id);
    if ($result === false) {
    	return;
    }

    while ($row = mysql_fetch_array ($result)) {
		
		// ACL Check for this task
		// This user can see this task?	
		$task_access = get_project_access ($config["id_user"], $project_id, $row['id'], false, true);
		if ($task_access["read"]) {
			
			$task['id'] = $row['id'];
			$task['name'] = $row['name'];

			if ($show_actual) {
				$task["name"] .= " (".__("Planned").")";
			}

			$task['parent'] = $parent_id;
			$task['link'] = 'index.php?sec=projects&sec2=operation/projects/task_detail&id_project=' . $project_id .'&id_task=' . $row['id'] .'&operation=view';
			// start > end
			$task['start'] = fix_date ($row['start'], $project_start);
			$task['end'] = fix_date ($row['end'], $project_end);
			if (date_to_epoch ($task['start']) > date_to_epoch ($task['end'])) {
				$temp = $task['start'];
				$task['start'] = $task['end'];
				$task['end'] = $temp;
			}
			$task['real_start'] = fix_date (get_db_sql ('SELECT MIN(timestamp) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND timestamp <> \'0000-00-00 00:00:00\' AND id_task = ' . $row['id']), $task['start']);
			$task['real_end'] = fix_date (get_db_sql ('SELECT MAX(timestamp) FROM tworkunit, tworkunit_task WHERE tworkunit_task.id_workunit = tworkunit.id AND timestamp <> \'0000-00-00 00:00:00\' AND id_task = ' . $row['id']), $task['start']);
			$task['completion'] = $row['completion'];
			$task["actual_data"] = 0;

			$task["worked_hours"] = get_task_workunit_hours ($row["id"]);
			$task["hours"] = $row["hours"];
	
			array_push ($tasks, $task);
			
			//Add another task to represent real effort for the task
			if ($show_actual) {

				$task_aux = array();

				$task_aux["id"] = $task["id"]."act";
				$task_aux["actual_data"] = 1;
				$task_aux["parent"] = $task["parent"];

				if ($task['real_start']) {
					$task_aux["start"] = $task['real_start'];
				} else {
					$task_aux["start"] = $task['start'];
				}

				if ($task['real_end']) {
					$task_aux["end"] = $task['real_end'];
				} else {
					$task_aux["end"] = $task['start'];
				}
				$task_aux["completion"] = 0;
				$task_aux["name"] = $row["name"]." (".__("Actual").")";

				array_push ($tasks, $task_aux);
			}
			
			get_tasks_gantt ($tasks, $project_id, $project_start, $project_end, $task['id'], $depth + 1, $show_actual);
		}
    }
}

// Get project milestones
function get_milestones_gantt (&$milestones, $project_id, $parent_id = 0, $depth = 0) {
    $result = mysql_query ('SELECT * FROM tmilestone 
                            WHERE id_project = ' . $project_id);
    if ($result === false) {
    	return;
    }
    
    while ($row = mysql_fetch_array ($result)) {
    	$milestone['id'] = $row['id'];
    	$milestone['name'] = $row['name'];
    	$milestone['description'] = $row['description'];
    	$milestone['date'] = fix_date ($row['timestamp']);
    	array_push ($milestones, $milestone);
	}
}

// Get project tasks
if ($project_tasks) {
	
	$tasks = array();

	$from = '00/00/0000';
	$to = '00/00/0000';

	// Minimum date from project/tasks/workunits
	$min_start = fix_date (get_db_sql ('SELECT MIN(start) FROM ttask WHERE start <> \'0000-00-00\' AND id_project = ' . $project_tasks));

	if ($min_start !== false) {
		$from = $min_start;
	}

	// Maximun date from project/tasks/workunits
	$max_end = fix_date (get_db_sql ('SELECT MAX(end) FROM ttask WHERE end <> \'0000-00-00\' AND id_project = ' . $project_tasks));

	if ($max_end !== false) {
		$to = $max_end;
	}

	// Fix undefined start/end dates
	if ($from == '00/00/0000') {
		$from = $min_start = date ('d/m/Y');
	}

	if ($to == '00/00/0000') {
		$to = $max_end = date ('d/m/Y');
	}
	
	get_tasks_gantt ($tasks, $project_tasks, $from, $to, 0, 0, $show_actual);

	$order = 1;
	$link_counter = 1;

	$tasks_gantt = array();
	$links_gantt = array();
	foreach ($tasks as $t) {

		//Add tasks with gantt format

		//Replace / with - to get the correct time format
		$aux_date = str_replace ("/", "-", $t["start"]);
		$start_time = strtotime ($aux_date);
		
		$aux_date = str_replace ("/", "-", $t["end"]);
		$end_time = strtotime ($aux_date);
		
		$duration_time = $end_time - $start_time;
		
		$day_in_seconds = 3600 * 24;

		$duration = $duration_time / $day_in_seconds;

		//Add one day, we need to show task until end of last day
		//We add this for all tasks which has a duration and for 
		//all tasks which are the estimated task not the actual one
		if ($duration || !$t["actual_data"])
			$duration++;

		//Get number of people involver
		$sql_people = sprintf("SELECT id_user, id_role FROM trole_people_task WHERE id_task = %d", $t["id"]);

		$people = process_sql ($sql_people);

		$people_task = array();
		foreach ($people as $p) {
			$aux = array();

			$aux["name"] = safe_output(get_db_value("nombre_real", "tusuario", "id_usuario", $p["id_user"]));
			$aux["role"] = safe_output(get_db_value("name", "trole", "id", $p["id_role"]));

			array_push($people_task, $aux);
		}

		$aux = array("id" => $t["id"],
				"text" => safe_output($t["name"]),
				"start_date" => $t["start"],
				"progress" => $t["completion"]/100,
				"duration" => sprintf("%.2f",$duration),
				"estimated_hours" => $t["hours"],
				"people" => $people_task,
				"order" => $order,
				"actual_data" => $t["actual_data"],
				"id_project" => $project_tasks,
				"worked_hours" => $t["worked_hours"],
				"parent" => $t["parent"]);

		//Create a link if needed
		$sql = sprintf("SELECT * FROM ttask_link WHERE target = %d", $t["id"]);

		$links = get_db_all_rows_sql($sql);

		if (!$links) {
			$links = array();
		}

		foreach ($links as $l) {
			$aux_link = array("id" => $link_counter,
							"source" => $l["source"],
							"target" => $l["target"],
							"type" => $l["type"]);

			array_push($links_gantt, $aux_link);

			$link_counter++;
		}

		array_push($tasks_gantt, $aux);

		$order++;
	}

    $tasks_array = array("data" => $tasks_gantt, "links" => $links_gantt);

	$milestones = array();

	get_milestones_gantt($milestones, $project_tasks);

	$milestones_day = array();
	$milestones_month = array();
	$milestones_week = array();
	
	foreach ($milestones as $m) {

		$date_array = split("/", $m["date"]);

		$date_aux = mktime(0, 0, 0, $date_array[1], $date_array[0],$date_array[2]);
		$week = (int)date('W', $date_aux);
		
		if (!isset ($milestones_week[$week])) {
			$milestones_week[$week] = array();
		}

		array_push($milestones_week[$week], array("name" => safe_output($m["name"]), "date" => $m["date"]));

		if (!isset ($milestones_month[$date_array[1]])) {
			$milestones_month[$date_array[1]] = array();
		}

		array_push($milestones_month[$date_array[1]], array("name" => safe_output($m["name"]), "date" => $m["date"]));

		if (!isset ($milestones_day[$m["date"]])) {
			$milestones_day[$m["date"]] = array();
		}

		array_push($milestones_day[$m["date"]], array("name" => safe_output($m["name"]), "desc" => safe_output($m["description"])));
	}
	
	$milestones_array = array("month" => $milestones_month, "day" => $milestones_day, "week" => $milestones_week);

	$aux_date = str_replace ("/", "-", $min_start);
	$min_start_sec = strtotime($aux_date);
	$min_start_mili = $min_start_sec * 1000;

	$aux_date = str_replace ("/", "-", $max_end);
	$max_end_sec = strtotime($aux_date);
	$max_end_mili = $max_end_sec * 1000;
	
	// Add some space at end to avoid tooltip overload and too small tasks
	$last_task_seconds = get_db_sql ('SELECT UNIX_TIMESTAMP(end) - UNIX_TIMESTAMP(start) AS diff FROM ttask 
								   WHERE id_project=' . $project_tasks . ' 
								   ORDER BY UNIX_TIMESTAMP(end) 
								   DESC LIMIT 1');
								   
	switch ($get_scale) {
		
		case "month":
			$added_seconds = 15 * 86400 - $last_task_seconds;
			if ($added_seconds > 0) {
				$max_end_mili += $added_seconds * 1000;
			}
			break;
		case "week":
			$added_seconds = 5 * 86400 - $last_task_seconds;
			if ($added_seconds > 0) $max_end_mili += $added_seconds * 1000;
			break;
		default:
	}

	//Fix dates depending on scale
	$result = array("tasks" => $tasks_array, "milestones" => $milestones_array, "min_scale" => $min_start_mili, "max_scale" => $max_end_mili);

	echo json_encode($result);

	exit;
}

if ($update_task) {
	$id_task = get_parameter("id");
	$start_date = get_parameter("start_date");
	$end_date = get_parameter("end_date");
	$progress = get_parameter("progress");

	//Fix date
	$start_date = safe_output($start_date);
	$start_date = preg_replace ('/ \(.*\)/i', '', $start_date);
	$start_time = strtotime($start_date);
	$start_date = date("Y-m-d", $start_time);

	$end_date = safe_output($end_date);
	$end_date = preg_replace ('/ \(.*\)/i', '', $end_date);
	$end_time = strtotime($end_date);
	$end_date = date("Y-m-d", $end_time);

	//Fix progress
	$progress = $progress * 100;
	$progress = (int) $progress;

	$current_task = get_db_row_sql(sprintf("SELECT * FROM ttask WHERE id = %d", $id_task));

	//If task is the same don't update it and return and OK message
	if ($start_date == $current_task["start"]
		&& $end_date == $current_task["end"]
		&& $progress == $current_task["completion"]) {

		$res = true;

	} else {
		//If something is different then update task
		$sql = sprintf ('UPDATE ttask SET completion = %d,
				start = "%s", end = "%s" WHERE id = %d',
				$progress, $start_date, $end_date, $id_task);

		$res = process_sql ($sql);
	}

	if ($res) {	
		$msg = __("Task updated");
	} else {
		$msg = __("Error updating task");
	}

	$ret = array ("res" => $res, "msg" => $msg);
	echo json_encode($ret);
	exit;
}

if ($update_task_parent) {
	$target = get_parameter("target");
	$source = get_parameter("source");
	$type = get_parameter("type");

	/**
		link types:
			0: start to finish
			1: start to start
			2: finish to finish
	**/
	$links = array($source);
	
	$res = projects_update_task_links ($target, $links, $type, false);

	if ($res) {	
		$msg = __("Task updated");
	} else {
		$msg = __("Error updating task");
	}

	$ret = array ("res" => $res, "msg" => $msg);
	echo json_encode($ret);
	exit;
}

if ($get_task_links) {
	$type = (int) get_parameter("type");
	$id_project = get_parameter("id_project");
	$id_task = get_parameter("id_task");
	
	$task_aux = projects_get_task_available_links ($id_project, $id_task, $type);

	$table_link->class = 'databox';
	$table_link->width = '98%';
	$table_link->data = array ();

	$table_link->data[0][0] = print_label (__('Tasks'), '','',true);
	$table_link->data[0][1] = print_select ($task_aux, 'id_task', '',
		'', '', 0, true, false, false, '', '', 'width: 200px;');
	
	print_table($table_link);
	
	echo '<div style="width:'.$table_link->width.'" class="action-buttons button">';
		echo "<a href='javascript: load_link(".$type.");'>".__('Add')."<img src='images/go.png' /></a>";
	echo '</div>';
	
	exit;
}

if ($get_task_editor) {
	include($config["homedir"]."operation/projects/task_detail.php");
	exit;
}

if ($delete_task) {
	delete_task ($delete_task);

	$msg = __('Successfully deleted');

	$ret = array ("res" => true, "msg" => $msg);
	echo json_encode($ret);
	exit;
}

if ($create_task) {
	$name = get_parameter("text");
	$hours = get_parameter("duration");
	$id_project = get_parameter("id_project");
	$id_parent = get_parameter("id_parent");
	$start = get_parameter("start_date");

	//Calculate task start and end
	$start = safe_output($start);
	
	if ($start == "__NEW__") {
		$start = date ('Y-m-d');
	} else {
		$date_array = split(" ", $start);
		$date_array = split("-", $date_array[0]);
		$start = $date_array[2]."-".$date_array[1]."-".$date_array[0];
	}
	

	//By default tasks ends a day after it starts
	$end = date ('Y-m-d', strtotime($start. ' + 1 days'));

	$sql = sprintf('INSERT INTO ttask (`id_project`, `id_parent_task`, 
					`name`, `start`, `end`, `hours`) VALUES 
					(%d, %d, "%s", "%s", "%s", %d)', 
					$id_project, $id_parent, $name, $start, $end, $hours);

	$id_task = process_sql ($sql, 'insert_id');

	if ($id_task) {
		// Add all users assigned to current project for new task or parent task if has parent
		if ($id_parent != 0)
			$query1="SELECT * FROM trole_people_task WHERE id_task = $id_parent";
		else
			$query1="SELECT * FROM trole_people_project WHERE id_project = $id_project";
		$resq1=mysql_query($query1);
		while ($row=mysql_fetch_array($resq1)){
			$id_role_tt = $row["id_role"];
			$id_user_tt = $row["id_user"];
			$sql = "INSERT INTO trole_people_task
			(id_task, id_user, id_role) VALUES
			($id_task, '$id_user_tt', $id_role_tt)";
			mysql_query($sql);
		}
		task_tracking ($id_task, TASK_CREATED);
		project_tracking ($id_project, PROJECT_TASK_ADDED);
	}

	if ($id_task) {	
		$msg = __("Task created");
	} else {
		$msg = __("Error creating task");
	}

	$ret = array ("res" => $id_task, "msg" => $msg);
	echo json_encode($ret);
	exit;
}

if ($get_task_statistics) {
	include($config["homedir"]."operation/projects/task_report.php");
	exit;
}

if ($get_task_completion_hours) {
	$percentage = get_task_completion($get_task_completion_hours);

	echo json_encode(array("percentage" => $percentage));
	exit;
}

if ($check_link) {
	$source = get_parameter("source");
	$target = get_parameter("target");
	$type = get_parameter("type");

	$sql = sprintf("SELECT * FROM ttask_link WHERE type = %d
		AND ((source = %d AND target = %d) OR (source = %d AND target = %d))", 
			$type, $source, $target, $target, $source);
	
	$res = process_sql($sql);

	$valid = true;

	if ($res) {
		$valid = false;
	}

	echo json_encode(array("result" => $valid));
	exit;
}

if ($get_calculator) {
	
	$days = get_parameter('days', 0);
	$people = get_parameter('people', 0);
	
	$hours_per_day = $config['hours_perday'];
	$total = $days * $people * $hours_per_day;
	
	$table->width = "100%";
	$table->class = "search-table-button";
	$table->data = array ();
	$table->colspan = array ();
	$table->colspan[3][1] = 2;
	$table->data[0][0] = print_label(__('Days'), '', 'text', true);
	$table->data[0][1] = print_input_text ('days', $days, '', 15, 15, true);
	$table->data[1][0] = print_label(__('People'), '', 'text', true);
	$table->data[1][1] = print_input_text ('people', $people, '', 15, 15, true);
	$table->data[2][0] = print_label(__('Total hours'), '', 'text', true);
	$table->data[2][1] = print_input_text ('total', $total, '', 15, 15, true, '',true);
	
	$table->data[3][1] = print_submit_button (__('Set value'), 'set_value', false, 'class="sub upd"', true);
	$table->data[3][1] .= print_submit_button (__('Calculate'), 'calculate', false, 'class="sub search"', true);
	
	'<form id="calculator_form">';
	print_table ($table, false);
	'</form>';
	
	exit;
}

if($get_workunits_task){
	global $config;

	$id_task = get_parameter('id_task');
	$offset = get_parameter("offset");
	$block_size = $config['block_size'];
	// Get all the workunits
	$sql_count = sprintf('SELECT count(final.id_user) FROM ( SELECT tw1.id_user,tw1.timestamp,tw1.duration,tw1.description,NULL AS id_incidencia,
															NULL AS titulo,NULL AS estado
															FROM tworkunit tw1
															INNER JOIN tworkunit_task twt
															ON tw1.id = twt.id_workunit
															AND twt.id_task = %d
					UNION
						SELECT tw2.id_user,tw2.timestamp,tw2.duration,tw2.description,twin.id_incidencia,twin.titulo,twin.estado FROM tworkunit tw2
								INNER JOIN (SELECT twi.id_workunit,ti.id_incidencia,ti.titulo,tis.name AS estado
											FROM tworkunit_incident twi
											INNER JOIN tincidencia ti
											ON twi.id_incident = ti.id_incidencia
											AND ti.id_task = %d
											INNER JOIN tincident_status tis
											ON ti.estado = tis.id
											) twin
						ON tw2.id = twin.id_workunit
						) final
				ORDER BY final.id_user, final.timestamp',
				$id_task, $id_task);

	$sql = sprintf('SELECT final.id_user AS id_user,
					DATE(final.timestamp) AS date,
					final.duration AS duration,
					final.description AS content,
					final.id_incidencia AS ticket_id,
					final.titulo AS ticket_title,
					final.estado AS ticket_status
				FROM (
					SELECT tw1.id_user,
						tw1.timestamp,
						tw1.duration,
						tw1.description,
						NULL AS id_incidencia,
						NULL AS titulo,
						NULL AS estado
					FROM tworkunit tw1
					INNER JOIN tworkunit_task twt
						ON tw1.id = twt.id_workunit
							AND twt.id_task = %d
					
					UNION
					
					SELECT tw2.id_user,
						tw2.timestamp,
						tw2.duration,
						tw2.description,
						twin.id_incidencia,
						twin.titulo,
						twin.estado
					FROM tworkunit tw2
					INNER JOIN (
						SELECT twi.id_workunit,
							ti.id_incidencia,
							ti.titulo,
							tis.name AS estado
						FROM tworkunit_incident twi
						INNER JOIN tincidencia ti
							ON twi.id_incident = ti.id_incidencia
								AND ti.id_task = %d
						INNER JOIN tincident_status tis
							ON ti.estado = tis.id
					) twin
						ON tw2.id = twin.id_workunit
				) final
				ORDER BY final.id_user, final.timestamp limit %d offset %d',
				$id_task, $id_task, $block_size, $offset);
	
	$all_wu = get_db_all_rows_sql($sql);
	$count = get_db_value_sql($sql_count);
	
	if (!empty($all_wu)) {
		$table_wu = new StdClass();
		$table_wu->class = 'listing';
		$table_wu->head = array();
		$table_wu->head['person'] = __('Person');
		$table_wu->head['date'] = __('Date');
		$table_wu->head['duration'] = __('Duration ('.__('In hours').')');
		$table_wu->head['ticket_id'] = __('Ticket id');
		$table_wu->head['ticket_title'] = __('Ticket title');
		$table_wu->head['ticket_status'] = __('Ticket status');
		if (!$pdf_output)
			$table_wu->head['content'] = __('Content');
		$table_wu->data = array();
		
		foreach ($all_wu as $wu) {
			// Add the values to the row
			$row = array();
			$row['id_user'] = $wu['id_user'];
			$row['date'] = $wu['date'];
			$row['duration'] = (float)$wu['duration'];
			
			$row['ticket_id'] = $wu['ticket_id'] ? '#'.$wu['ticket_id'] : '';
			$row['ticket_title'] = $wu['ticket_title'];
			$row['ticket_status'] = $wu['ticket_status'];
			
			if (!$pdf_output) {
				$row['content'] = sprintf(
						'<div class="tooltip_title" title="%s">%s</div>',
						$wu['content'],
						print_image ("images/note.png", true)
					);
			}
			$table_wu->data[] = $row;
		}

		echo pagination ($count, $url_pag, $offset);
		echo '<div class = "get_workunits_task">';
			echo print_table ($table_wu);
		echo '<div>';
		echo pagination ($count, $url_pag, $offset, true);
	}
}

?>