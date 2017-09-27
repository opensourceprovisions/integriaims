<?php

global $config;
enterprise_include ('include/functions_projects.php', true);

 /**
 * Get an SQL query with the accessible projects
 * by accessible companies.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
 * 
 * @param id_user User ID
 * @param where_clause More filters for the WHERE clause of the query
 * @param disabled 1 to return the disabled projects
 * @param real Flag for use or not the admin permissions
 * 
 * @return string SQL query
*/
function get_projects_query ($id_user, $where_clause = "", $disabled = 0, $real = false) {
	
	$return = enterprise_hook ('get_projects_query_extra', array($id_user, $where_clause, $disabled, $real));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return "SELECT *
			FROM tproject
			WHERE disabled=$disabled
				$where_clause
			ORDER BY name";
}

/**
 * Get an SQL query with the accessible tasks
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
 * 
 * @param id_user User ID
 * @param id_project Project Id
 * @param where_clause More filters for the WHERE clause of the query
 * @param disabled 1 to return the tasks of disabled projects
 * @param real Flag for use or not the admin and project manager permissions
 * 
 * @return string SQL query
*/
function get_tasks_query ($id_user, $id_project, $where_clause = "", $disabled = 0, $real = false) {
	
	$return = enterprise_hook ('get_tasks_query_extra', array($id_user, $id_project, $where_clause, $disabled, $real));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return "SELECT *
			FROM ttask
			WHERE id_project=$id_project
				AND id_project=ANY(SELECT id
								  FROM tproject
								  WHERE disabled=$disabled)
				$where_clause
			ORDER BY name";
}

/**
 * Get the project or task accessibility
 *
 * @param id_user User ID
 * @param id_project Project Id. If false, only check the read flag
 * @param id_task Task Id. If true, check the project accessibitity
 * @param real Flag for use or not the admin and project manager permissions
 * @param search_in_hierarchy Flag for search inherited permissions
 * 
 * @return string SQL query
 * 
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function get_project_access ($id_user, $id_project = false, $id_task = false, $real = false, $search_in_hierarchy = false) {
	
	$permission = array();
	$permission['read'] = true;
	$permission['write'] = true;
	$permission['manage'] = true;
	
	$return = enterprise_hook ('get_project_access_extra', array($id_user, $id_project, $id_task, $real, $search_in_hierarchy));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	
	return $permission;
}

/**
 * Get the number of readable tasks of a project for an user
 *
 * @param id_user User ID
 * @param id_project Project Id
 * @param id_parent Only count the tasks with that parent
 * 
 * @return int Count of tasks
*/
function get_accesible_task_count ($id_user, $id_project, $id_parent = false) {
	
	if ($id_parent !== false) {
		$parent = "id_parent_task=$id_parent";
	} else {
		$parent = "1=1";
	}
	
	$sql = "SELECT id
			FROM ttask
			WHERE $parent
				AND id_project=$id_project";
	$count = 0;
	$new = true;
	while ($task = get_db_all_row_by_steps_sql($new, $result_project, $sql)) {
		$new = false;
		
		$task_access = get_project_access ($id_user, $id_project, $task['id'], false, true);
		if ($task_access['read']) {
			$count++;
		}
		
	}
	return $count;
}

/**
 * Get the if the user can manage almost one task
 *
 * @param id_user User ID
 * @param id_project Project Id. Check the tasks of one or all projects
 * 
 * @return boolean
 * 
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function manage_any_task ($id_user, $id_project = false, $permission_type = "manage") {
	
	$return = enterprise_hook ('manage_any_task_extra', array($id_user, $id_project, $permission_type));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
	
}

function get_workorder_acl ($id_workorder, $type = '', $id_user = false) {
	
	$return = enterprise_hook ('get_workorder_acl_extra', array($id_workorder, $type, $id_user));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
	
}

function get_workorders ($where_clause = "", $order_by = "") {
	
	$sql = "SELECT * FROM ttodo ".$where_clause." ".$order_by;
	
	$return = enterprise_hook ('get_workorders_extra', array($where_clause, $order_by));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return get_db_all_rows_sql ($sql);
}

function project_number_task_user ($id_project, $id_user) {

	$sql = sprintf("SELECT id FROM ttask WHERE id_project= %d", $id_project);

	$tasks = get_db_all_rows_sql($sql);

	if ($tasks == false) {
		return 0;
	}

	$clause = "";

	foreach ($tasks as $t) {
		$clause .= $t["id"].",";
	}

	$clause = "(".substr($clause,0,-1).")";


	$sql = sprintf ('SELECT COUNT(id) FROM trole_people_task WHERE 
					id_task IN %s AND id_user = "%s"', $clause, $id_user);

	return (int) get_db_sql ($sql);
}

function projects_get_task_links ($id_project, $id_task, $type) {

	$sql = sprintf("SELECT T.id, T.name FROM ttask T, ttask_link TL WHERE 
			T.id = TL.source AND TL.type = %d AND T.id_project = %d AND T.id != %d AND TL.target = %d",
			$type, $id_project, $id_task, $id_task);

	$tasks = get_db_all_rows_sql($sql);
	
	$task_aux = array();
	if (is_array($tasks) || is_object($tasks)){
		foreach ($tasks as $t) {
			$task_aux[$t["id"]] = $t["name"];
		}
	}
	return $task_aux;
}

function projects_get_task_available_links ($id_project, $id_task, $type) {

	$sql = sprintf("SELECT * FROM ttask WHERE id_project = %d AND 
			id != %d AND id NOT IN (SELECT source FROM ttask_link WHERE type = %d AND target = %d)",
			$id_project, $id_task, $type, $id_task);

	$tasks = get_db_all_rows_sql($sql);

	$task_aux = array();
	foreach ($tasks as $t) {
		$task_aux[$t["id"]] = $t["name"];
	}

	return $task_aux;
}

function projects_update_task_links ($id_task, $links, $type, $delete_previous=true) {

	//Delete links
	if ($delete_previous) {
		$sql = sprintf("DELETE FROM ttask_link WHERE type = %d AND target = %d", $type, $id_task);

		$ret = process_sql($sql);
	}
	if (is_array($links) || is_object($links)){
		foreach ($links as $l) {
			$sql = sprintf ("INSERT INTO ttask_link (`source`, `target`, `type`) VALUES (%d, %d, %d)", $l, $id_task, $type);
			$ret = process_sql($sql);

			if (!$ret) {
				break;
			}
		}
	}
	
	return $ret;
}

function projects_get_cost_task_by_profile ($id_task, $id_profile=false, $have_cost=false) {
	if ($id_profile) {
		if ($have_cost) {
			$sql = "SELECT id_profile, SUM(duration) as total_duration FROM tworkunit, tworkunit_task
					WHERE tworkunit_task.id_task = $id_task 
					AND id_profile= $id_profile
					AND have_cost = 1
					AND tworkunit_task.id_workunit = tworkunit.id 
					GROUP BY id_profile";
		} else {
			$sql = "SELECT id_profile, SUM(duration) as total_duration FROM tworkunit, tworkunit_task
					WHERE tworkunit_task.id_task = $id_task 
					AND id_profile= $id_profile
					AND tworkunit_task.id_workunit = tworkunit.id 
					GROUP BY id_profile";
		}
	} else { //all profiles
		if ($have_cost) {
			$sql = "SELECT id_profile, SUM(duration) as total_duration FROM tworkunit, tworkunit_task
					WHERE tworkunit_task.id_task = $id_task 
					AND have_cost = 1
					AND tworkunit_task.id_workunit = tworkunit.id 
					GROUP BY id_profile";
		} else {
			$sql = "SELECT id_profile, SUM(duration) as total_duration FROM tworkunit, tworkunit_task
					WHERE tworkunit_task.id_task = $id_task 
					AND tworkunit_task.id_workunit = tworkunit.id 
					GROUP BY id_profile";
		}
	}

	$duration = get_db_row_sql ($sql);

	$total = 0;
	
	if ($duration != false) {
			$role_info = get_db_row_sql ("SELECT name, cost FROM trole WHERE id = ".$duration['id_profile']);

			if ($role_info != false) {
				$cost_per_hour = $role_info['cost'];
				$profile_name = $role_info['name'];
				$total = $cost_per_hour * $duration['total_duration'];
			}
	}
	return $total;
}

function projects_get_project_profiles ($id_project) {
	
	$project_profiles = get_db_all_rows_sql ("SELECT distinct(id_role), trole.name 
			FROM trole_people_project, trole
			WHERE id_project=$id_project
			AND trole.id=trole_people_project.id_role");
			
	$task_profiles = get_db_all_rows_sql ("SELECT distinct(id_role), trole.name 
			FROM trole_people_task, trole
			WHERE trole_people_task.id_task IN (SELECT id FROM ttask WHERE id_project=$id_project)
			AND trole.id=trole_people_task.id_role");
	
	if ($project_profiles == false) {
		$project_profiles = array();
	}
	if ($task_profiles == false) {
		$task_profiles = array();
	}
	
	$results = array_merge($project_profiles, $task_profiles);
	
	if (!empty($results)) {
		foreach ($results as $result) {
			$all_profiles[$result['id_role']]['id_role'] = $result['id_role'];
			$all_profiles[$result['id_role']]['name'] = $result['name'];
		}
	}
	if(isset($all_profiles)){
		return $all_profiles;
	}
}

function projects_get_cost_by_profile ($id_project, $have_cost=false) {
	
	$total_per_profile = array();
	
	$project_profiles = projects_get_project_profiles ($id_project);		
	$project_tasks = get_db_all_rows_sql("SELECT * FROM ttask WHERE id_project = $id_project");
	
	if ($project_profiles) {
		foreach ($project_profiles as $profile) {
			if (is_array($project_tasks) || is_object($project_tasks)){
				foreach ($project_tasks as $task) {
					$total_per_profile[$profile['name']] += projects_get_cost_task_by_profile ($task['id'], $profile['id_role'], $have_cost);
				}
			}
		}
	}
	return $total_per_profile;
}

 function project_get_icon ($id_project, $return = false) {
	$output = '';
	
	$icon = (string) get_db_value ('icon', 'tproject_group', 'id', $id_project);

	$output .= '<img id="product-icon"';
	if ($icon != '') {
		$output .= 'src="images/project_groups_small/'.$icon.'"';
	} else {
		$output .= 'src="images/project_groups_small/applications-accessories.png"';
	}
	$output .= ' />';
	
	if ($return)
		return $output;
	echo $output;
}
/*
 * Project menu associative array
 * (it should be used with print_title_with_menu)
 * 
 * */
function print_project_tabs($selected_tab = '') {
	
	global $config;
	
	$id_project = get_parameter ('id_project', -1);
	$id_task = get_parameter ('id_task', -1);
	
	// Get id_task but not id_project
	if (($id_task != -1) AND ($id_project == -1)){
		$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
	}
	
	// ACL Permissions
	$section_permission = get_project_access ($config["id_user"]);
	$manage_any_task = manage_any_task ($config["id_user"]);
	
	if ($id_project > 0) {
		$project_permission = get_project_access ($config["id_user"], $id_project);
		$manage_any_task_in_project = manage_any_task ($config["id_user"], $id_project);
	}
	
	$p_menu = array ();
	
	$p_menu['overview'] = array (
		'title' => __('Project overview'),
		'link' => "operation/projects/project_detail&id_project=" . $id_project,
		'img' => "images/eye.png",
	);
	
	if ($manage_any_task_in_project) {
		$p_menu['task_plan'] = array (
			'title' => __('Task planning'),
			'link' => "operation/projects/task_planning&id_project=" . $id_project,
			'img' => "images/task_planning.png",
		);
	}
	
	$p_menu['time'] = array (
		'title' => __('Time graph'),
		'link' => "operation/projects/project_timegraph&id_project=" . $id_project,
		'img' => "images/chart_pie.png",
	);
	
	$p_menu['tracking'] = array (
		'title' => __('Project traking'),
		'link' => "operation/projects/project_tracking&id_project=" . $id_project,
		'img' => "images/clock_tab.png",
	);
	
	$task_number = get_tasks_count_in_project ($id_project);
	if ($task_number > 0) {	
		$p_menu['task_list'] = array (
			'title' => __('Task list') . " (" . $task_number . ")",
			'link' => "operation/projects/task&id_project=" . $id_project,
			'img' => "images/tree_list.png",
		);
	} else {
		$p_menu['task_list'] = array (
			'title' => __('Task list') . " (" . __("Empty") . ")",
			'img' => "images/tree_list_disabled.png",
		);
	}
	
	if ($manage_any_task_in_project) {
		$p_menu['task_new'] = array (
			'title' => __('New task'),
			'link' => "operation/projects/task_detail&operation=create&id_project=" . $id_project,
			'img' => "images/new_tab.png",
		);
	}
	
	$p_menu['gantt'] = array (
		'title' => __('Gantt chart'),
		'link' => "operation/projects/gantt&id_project=" . $id_project,
		'img' => "images/gantt.png",
	);
	
	$p_menu['milestones'] = array (
		'title' => __('Milestones'),
		'link' => "operation/projects/milestones&id_project=" . $id_project,
		'img' => "images/milestone.png",
	);
	
	if ($project_permission['manage']) {
		$p_menu['people'] = array (
			'title' => __('People'),
			'link' => "operation/projects/people_manager&id_project=" . $id_project,
			'img' => "images/contacts.png",
		);
	}

	$totalhours = get_project_workunit_hours ($id_project);
	$totalwu = get_project_count_workunits ($id_project);
	if ($totalwu > 0) {
		$p_menu['workunits'] = array (
			'title' => __('Workunits') . " (" .$totalhours . " " . __("Hours") . ")",
			'link' => "operation/projects/task_workunit&id_project=" . $id_project,
			'img' => "images/workunit_tab.png",
		);
	} else {
		$p_menu['workunits'] = array (
			'title' => __('Workunit') . " (" . __("Empty") . ")",
			'img' => "images/workunit_disabled.png",
		);
	}

	$numberfiles = give_number_files_project ($id_project);
	if ($numberfiles > 0){
		$p_menu['files'] = array (
			'title' => __('Files') . "(" . $numberfiles . ")",
			'link' => "operation/projects/task_files&id_project=" . $id_project,
			'img' => "images/products/folder.png",
		);
	} else {
		$p_menu['files'] = array (
			'title' => __('Files') . "(" . __("Empty") . ")",
			'img' => "images/folder_disabled.png",
		);
	}
	
	if ($selected_tab == 'overview') {
		$p_menu['report'] = array (
			'title' => __('Project report'),
			'link' => "operation/projects/project_report&id_project=" . $id_project,
			'img' => "images/chart_bar_dark.png",
		);
	}
		
	if ($selected_tab == 'task_list') {
		$p_menu['report_task'] = array (
			'title' => __('Tasks report'),
			'link' => "operation/projects/task&id_project=" . $id_project . "&pure=1",
			'img' => "images/chart_bar_dark.png",
		);
	}
	
	if ($selected_tab == 'gantt') {
		$p_menu['report_gant'] = array (
			'title' => __('Full screen Gantt'),
			'link' => "operation/projects/gantt&id_project=" . $id_project . "&clean_output=1",
			'img' => "images/chart_bar_dark.png",
			'target' => "top",
		);
	}
	
	if ($selected_tab == 'workunits') {
		$p_menu['report_gant'] = array (
			'title' => __('Tasks report'),
			'link' => "operation/projects/task_workunit&id_project=" . $id_project . "&pure=1",
			'img' => "images/chart_bar_dark.png",
		);
	}
	
	return $p_menu;
}

/*
 * Task menu associative array
 * (it should be used with print_title_with_menu)
 * 
 * */
function print_task_tabs($selected_tab = '', $id_task_param = false) {
	
	global $config;
	
	$id_project = get_parameter ('id_project', -1);	
	$id_task = ($id_task_param !== false) ? $id_task_param : get_parameter ('id_task', -1);
	
	// Get id_task but not id_project
	if (($id_task != -1) AND ($id_project == -1)){
		$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
	}
	
	$task_permission = array ();
	if ($id_task > 0) {
		$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
	}
	
	$t_menu = array ();
	
	$t_menu['overview_project'] = array (
		'title' => __('Project overview'),
		'link' => "operation/projects/project_detail&id_project=" . $id_project,
		'img' => "images/eye.png",
	);
	
	$t_menu['overview'] = array (
		'title' => __('Tasks overview'),
		'link' => "operation/projects/task&id_project=" . $id_project,
		'img' => "images/tree_list.png",
	);
	
	$t_menu['detail'] = array (
		'title' => __('Task detail'),
		'link' => "operation/projects/task_detail&id_project=" . $id_project . "&id_task=" . $id_task . "&operation=view",
		'img' => "images/inventory_dark.png",
	);
	
	$t_menu['tracking'] = array (
		'title' => __('Task traking'),
		'link' => "operation/projects/task_tracking&id_project=" . $id_project . "&id_task=" . $id_task . "&operation=view",
		'img' => "images/clock_tab.png",
	);
	
	if ($task_permission['write']) {
		$t_menu['workunit_add'] = array (
			'title' => __('Add workunit'),
			'link' => "operation/users/user_spare_workunit&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/multiple_workunits_tab.png",
		);
	
		$t_menu['costs'] = array (
			'title' => __('View external costs'),
			'link' => "operation/projects/task_cost&id_project=" . $id_project . "&id_task=" . $id_task . "&operation=list",
			'img' => "images/money.png",
		);
	}
	
	if ($task_permission['manage']) {
		$t_menu['people'] = array (
			'title' => __('People'),
			'link' => "operation/projects/people_manager&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/contacts.png",
		);
		
		$t_menu['email'] = array (
			'title' => __('E-mail report'),
			'link' => "operation/projects/task_emailreport&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/email_dark.png",
		);
		
		$t_menu['move'] = array (
			'title' => __('Move task'),
			'link' => "operation/projects/task_move&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/move_task.png",
		);
	}
	
	$totalhours = get_task_workunit_hours ($id_task);
	$totalwu = get_task_count_workunits ($id_task);
	if ($totalwu > 0) {
		$t_menu['workunits'] = array (
			'title' => __('Workunits') . " (" .$totalhours . " " . __("Hours") . ")",
			'link' => "operation/projects/task_workunit&id_project=" . $id_project. "&id_task=" . $id_task,
			'img' => "images/workunit_tab.png",
		);
	} else {
		$t_menu['workunits'] = array (
			'title' => __('Workunit') . " (" . __("Empty") . ")",
			'link' => "",
			'img' => "images/workunit_disabled.png",
		);
	}

	$numberfiles = give_number_files_project ($id_project);
	//if ($numberfiles > 0){
		$t_menu['files'] = array (
			'title' => __('Files') . "(" . $numberfiles . ")",
			'link' => "operation/projects/task_files&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/products/folder.png",
		);
	/*} else {
		$t_menu['files'] = array (
			'title' => __('Files') . "(" . __("Empty") . ")",
			'img' => "images/folder_disabled.png",
		);
	}*/
	
	if ($selected_tab == 'detail') {
		$t_menu['report'] = array (
			'title' => __('Task report'),
			'link' => "operation/projects/task_report&id_project=" . $id_project . "&id_task=" . $id_task,
			'img' => "images/chart_bar_dark.png",
		);
	}
	
		
	if ($selected_tab == 'workunits') {
		$t_menu['report_gant'] = array (
			'title' => __('Tasks report'),
			'link' => "operation/projects/task_workunit&id_project=" . $id_project . "&id_task=" . $id_task . "&pure=1",
			'img' => "images/chart_bar_dark.png",
		);
	}
	
	return $t_menu;
}

?>
