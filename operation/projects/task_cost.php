<?PHP
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Global variables
require ("include/functions_tasks.php");
include_once ("include/functions_db.php");

global $config;
check_login ();

$id_task = get_parameter ("id_task", -1);
$task_name = get_db_value ("name", "ttask", "id", $id_task);
$id_project = get_parameter ("id_project", -1);

// ACL
if ($id_task == -1){
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task cost without task id");
	no_permission();
}

if ($id_project == -1) {
	$id_project = get_db_value ("id_project", "ttask", "id", $id_task);
}
// ACL
if (! $id_project) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task cost without project id");
	no_permission();
}

// ACL
$task_permission = get_project_access ($config["id_user"], $id_project, $id_task, false, true);
if (! $task_permission["write"]) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to task add cost control without permission");
	no_permission();
}


$operation = get_parameter ("operation", "");

if ($operation == "delete"){
	
	$id_invoice = get_parameter ("id_invoice", "");
	$invoice = get_db_row_sql ("SELECT * FROM tinvoice WHERE id = $id_invoice");
	
	// Do another security check, don't rely on information passed from URL
	
	if (($config["id_user"] = $invoice["id_user"]) OR ($id_task == $invoice["id_task"])){
			// Todo: Delete file from disk
			if ($invoice["id_attachment"] != ""){
				process_sql ("DELETE FROM tattachment WHERE id_attachment = ". $invoice["id_attachment"]);
			}
			process_sql ("DELETE FROM tinvoice WHERE id = $id_invoice");
	}
	
	$operation = "list";
}

if ($operation == "add"){
	
	$file = $_FILES["upfile"];
	$filename = $file["name"];
	$bill_id = get_parameter ("bill_id", "");
	$description = get_parameter ("description", "");
	$amount = (float) get_parameter ("amount", 0);
	$user_id = $config["id_user"];

	if ($file["error"] === 0){
		$file_temp = $file["tmp_name"];
		$filesize = $file["size"];
		
		// Creating the attach
		$sql = sprintf ('INSERT INTO tattachment (id_usuario, filename, description, size) VALUES ("%s", "%s", "%s", "%s")',
				$user_id, $filename, $description, $filesize);
		$id_attachment = process_sql ($sql, 'insert_id');
		
		// Copy file to directory and change name
		$file_target = $config["homedir"]."/attachment/".$id_attachment."_".$filename;
			
		if (! copy($file_temp, $file_target)) {
			$result_output = ui_print_error_message (__('File cannot be saved. Please contact Integria administrator about this error'), '', true, 'h3', true);
			$sql = "DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
			process_sql ($sql);
		} else {
			// Delete temporal file
			unlink ($file_temp);
		}
	} else {
		$id_attachment = 0;
	}
	
	// Creating the cost record
	$sql = sprintf ('INSERT INTO tinvoice (description, id_user, id_task,
	bill_id, concept1, amount1, id_attachment) VALUES ("%s", "%s", %d, "%s", "%s", "%s", %d)',
			$description, $user_id, $id_task, $bill_id, 'Task cost', $amount, $id_attachment);//Check
	
	$ret = process_sql ($sql, 'insert_id');
	if ($ret !== false) {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
	} else {
		echo ui_print_error_message (__('There was a problem creating adding the cost'), '', true, 'h3', true);
	}
	
	$operation = "list";
}

// Show form to create a new cost

if ($operation == "list"){

	$section_title = __('Cost unit listing');
	$section_subtitle =  __('Task') .": ".$task_name;
	$t_menu = print_task_tabs();
	print_title_with_menu ($section_title, $section_subtitle, false, 'projects', $t_menu, 'costs');
	
	echo "<h4>".__("Total cost for this task")."</h4>";
	
	echo "<div id='' class='divform'>";
	echo "<form method='POST' action='index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project' enctype='multipart/form-data' >";
	//~ $action = "index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project";
	
	$table = new StdClass();
	$table->id = 'cost_form';
	$table->width = '100%';
	$table->class = 'search-table';
	if(!isset($bill_id)){
		$bill_id = '';
	}
	$table->data[0][0] = "<b>" . __('Bill ID') . "</b>";
	$table->data[1][0] = print_input_text_extended ('bill_id', $bill_id, '', '', 15, 50, false, '', 'style="width:255px !important;"', true);
	if(!isset($amount)){
		$amount = '';
	}
	$table->data[2][0] = "<b>" . __('Amount') . "</b>";
	$table->data[3][0] = print_input_text_extended ('amount', $amount, '', '', 10, 20, false, '', 'style="width:255px !important;"', true);//Check
	
	$table->data[4][0] = "<b>" . __('Description') . "</b>";
	$table->data[5][0] = print_input_text_extended ('description', '', '', '', 60, 250, false, '', 'style="width:255px !important;"', true);
	
	$table->data[6][0] = "<b>" . __('Attach a file') . "</b>";
	$table->data[7][0] = '<input type="file" name="upfile" value="upfile" class="sub" size="30">';
	
	
	//~ if ($operation == "") {
		$table->align['button'] = 'center';
		$table->data[8]['button'] = print_submit_button (__('Add'), "crt", false, '', true);
		$table->data[8]['button'] .= print_input_hidden ('operation', "add", true);
		//~ $button_name = "button-crt";
	//~ } else {
		//~ $table->align['button'] = 'center';
		//~ $table->data[8]['button'] .= print_input_hidden ('id', $id_profile, true);
		//~ $table->data[8]['button'] .= print_input_hidden ('update_profile', 1, true);
		//~ $table->data[8]['button'] .= print_button (__('Update'), "upd", false, '', 'class=""', true);
		//~ $button_name = "button-upd";
	//~ }
	
	print_table ($table);	

	//~ print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', $button_name, false, '__UPLOAD_CONTROL__');
	
	echo "</form>";
	echo "</div>";
	
	echo "<div id='' class='divresult'>";
	echo "<div id='' class='divresult'>";
	
	$costs = get_db_all_rows_sql ("SELECT * FROM tinvoice WHERE id_task = $id_task");
	//if ($costs === false)
	//	$costs = array ();
	if(isset($cost)){
		$table->class = 'listing';
		$table->width = '100%';
		$table->data = array ();
		
		$table->head = array ();
		$table->head[0] = __('Description');
		$table->head[1] = __('Amount');
		$table->head[2] = __('Filename');
		$table->head[3] = __('Delete');
		
		foreach ($costs as $cost) {
			$data = array ();
			$data[0] = $cost["description"];
			$data[1] = get_invoice_amount($cost["id"]);// Check
			$id_invoice = $cost["id"];
			
			$filename = get_db_sql ("SELECT filename FROM tattachment WHERE id_attachment = ". $cost["id_attachment"]);
			
			$data[2] = 	"<a href='".$config["base_url"]."/attachment/".$cost["id_attachment"]."_".$filename."'>$filename</a>";
			
			if (($config["id_user"] = $cost["id_user"]) OR (project_manager_check ($id_project))){
				$data[3] = 	"<a href='index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project&operation=delete&id_invoice=$id_invoice '><img src='images/cross.png'></a>";
			}
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	} else {
		echo ui_print_error_message(__('No data found'), '', true, 'h3', true);
	}
	echo "</div>";
	echo "</div>";
}	


if ($operation == ""){

	//~ echo "<h2>";
	//~ echo __('Add cost unit')."</h2><h4>". __('Task') .": ".$task_name."</h4>";
	
	//~ echo "<div id='' class='divform'>";
	//~ echo "<form method='POST' action='index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project' enctype='multipart/form-data' >";
	//~ $action = "index.php?sec=projects&sec2=operation/projects/task_cost&id_task=$id_task&id_project=$id_project";
	
	//~ $table = new StdClass();
	//~ $table->id = 'cost_form';
	//~ $table->width = '100%';
	//~ $table->class = 'search-table';
	//~ 
	//~ $table->data[0][0] = "<b>" . __('Bill ID') . "</b>";
	//~ $table->data[1][0] .= print_input_text_extended ('bill_id', $bill_id, '', '', 15, 50, false, '', 'style="width:255px !important;"', true);
	//~ 
	//~ $table->data[2][0] = "<b>" . __('Amount') . "</b>";
	//~ $table->data[3][0] .= print_input_text_extended ('amount', $amount, '', '', 10, 20, false, '', 'style="width:255px !important;"', true);//Check
	//~ 
	//~ $table->data[4][0] = "<b>" . __('Description') . "</b>";
	//~ $table->data[5][0] .= print_input_text_extended ('description', '', '', '', 60, 250, false, '', 'style="width:255px !important;"', true);
	//~ 
	//~ $table->data[6][0] = "<b>" . __('Attach a file') . "</b>";
	//~ $table->data[7][0] = '<input type="file" name="upfile" value="upfile" class="sub" size="30">';
	
	
	//~ if ($operation == "") {
		//~ $table->align['button'] = 'center';
		//~ $table->data[8]['button'] .= print_button (__('Add'), "crt", false, '', 'class=""', true);
		//~ $table->data[8]['button'] .= print_input_hidden ('operation', "add", true);
		//~ $button_name = "button-crt";
	//~ } else {
		//~ $table->align['button'] = 'center';
		//~ $table->data[8]['button'] .= print_input_hidden ('id', $id_profile, true);
		//~ $table->data[8]['button'] .= print_input_hidden ('update_profile', 1, true);
		//~ $table->data[8]['button'] .= print_button (__('Update'), "upd", false, '', 'class=""', true);
		//~ $button_name = "button-upd";
	//~ }
	
	//~ print_table ($table);	

	//~ print_input_file_progress($action, $into_form, 'id="form-add-file"', 'sub next', $button_name, false, '__UPLOAD_CONTROL__');
	
	//~ echo "</form>";
	//~ echo "</div>";
}
?>
