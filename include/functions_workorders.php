<?php

function mail_workorder ($id_wo, $mode, $id_note = false, $wo_info = false, $note_info = false){
	global $config;

	$wo = $wo_info;

	if (!$wo_info) {	
		$wo = get_db_row ("ttodo", "id", $id_wo);
	}
	
	// Only send mails when creator is different than owner
	if ($wo['assigned_user'] == $wo['created_by_user'])
		return;
		
	$MACROS["_sitename_"] = $config['sitename'];
	$MACROS["_wo_id_"] = $wo['id'];
	$MACROS["_wo_name_"] = $wo['name'];
	$MACROS["_wo_last_update_"] = $wo['last_update'];
	$MACROS["_wo_created_by_user_"] = $wo['created_by_user'];
	$MACROS["_wo_assigned_user_"] = $wo['assigned_user'];
	$MACROS["_wo_progress_"] = translate_wo_status($wo['progress']);
	$MACROS["_wo_priority_"] = get_priority_name($wo['priority']);
	$MACROS["_wo_description_"] = wordwrap($wo["description"], 70, "\n");
	$MACROS["_wo_url_"] = $config["base_url"]."/index.php?sec=projects&sec2=operation/workorders/wo&operation=view&id=$id_wo";
	$MACROS["_wo_title_"] = $wo['name'];
	$MACROS["_wo_delete_user_"] = $config["id_user"];

	//Replace note macros if needed
	if ($id_note) {

		if (!$note_info) {
			$note_info = get_db_row ('ttodo_notes', 'id', $id_note);
		}
	
		$MACROS["_wo_note_created_by_user_"] = $note_info["written_by"];
		$MACROS["_wo_notes_url_"] = $config["base_url"]."/index.php?sec=projects&sec2=operation/workorders/wo&operation=view&tab=notes&id=$id_wo";
		$MACROS["_wo_note_info_"] = $note_info["description"];
		$MACROS["_wo_note_delete_user_"] = $config["id_user"];
	}

	// Send email for assigned and creator of this workorder
	$email_creator = get_user_email ($wo['created_by_user']);
	$email_assigned = get_user_email ($wo['assigned_user']);

	switch ($mode) {
		case 0: // WO update
			$text = template_process ($config["homedir"]."/include/mailtemplates/wo_update.tpl", $MACROS);
			$subject = template_process ($config["homedir"]."/include/mailtemplates/wo_subject_update.tpl", $MACROS);
			break;
		
		case 1: // WO creation
			$text = template_process ($config["homedir"]."/include/mailtemplates/wo_create.tpl", $MACROS);
			$subject = template_process ($config["homedir"]."/include/mailtemplates/wo_subject_create.tpl", $MACROS);
			break;
			
		case 3: // WO deleted 
			$text = template_process ($config["homedir"]."/include/mailtemplates/wo_delete.tpl", $MACROS);
			$subject = template_process ($config["homedir"]."/include/mailtemplates/wo_subject_delete.tpl", $MACROS);
			break;

		case 4: //New note
			$text = template_process ($config["homedir"]."/include/mailtemplates/wo_new_note.tpl", $MACROS);
                        $subject = template_process ($config["homedir"]."/include/mailtemplates/wo_subject_new_note.tpl", $MACROS);
			break;

                case 5: //Delete note
                        $text = template_process ($config["homedir"]."/include/mailtemplates/wo_delete_note.tpl", $MACROS);
                        $subject = template_process ($config["homedir"]."/include/mailtemplates/wo_subject_delete_note.tpl", $MACROS);
                        break;
	}

	$msg_code = "WO#$id_wo";
	$msg_code .= "/".substr(md5($id_wo . $config["smtp_pass"] . $wo["assigned_user"]),0,5);
	$msg_code .= "/" . $wo["assigned_user"];;
	
	integria_sendmail ($email_assigned, $subject, $text, false, $msg_code);
	
	$msg_code = "WO#$id_wo";
	$msg_code .= "/".substr(md5($id_wo . $config["smtp_pass"] . $wo["created_by_user"]),0,5);
    $msg_code .= "/".$wo["created_by_user"];

	integria_sendmail ($email_creator, $subject, $text, false, $msg_code);

}

function workorders_insert_note ($id, $user, $note, $date) {
	$sql = sprintf('INSERT INTO ttodo_notes (`id_todo`,`written_by`,`description`, `creation`)
                                        VALUES (%d, "%s", "%s", "%s")', $id, $user, $note, $date);

        $res = process_sql ($sql, 'insert_id');
	
	mail_workorder ($id, 4, $res);

	return $res;
}

?>
