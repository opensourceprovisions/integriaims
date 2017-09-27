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
include_once('include/functions_setup.php');

enterprise_include("include/functions_setup.php");

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('incidents', $is_enterprise);

$update = (bool) get_parameter ("update");
$add_day = (bool) get_parameter ("add_day");
$del_day = (bool) get_parameter ("del_day");

if ($add_day) {
	
	$new_day = get_parameter("new_day");
	
	//If new day added then add to list
	if ($new_day) { 
	
		$sql = sprintf("INSERT INTO tholidays (`day`) VALUES ('%s')", $new_day);
	
		process_sql($sql);
	}
}

if ($del_day) {
	$day = get_parameter("day");
	
	$sql = sprintf("DELETE FROM tholidays WHERE `id` = '".$day."'");
	
	process_sql ($sql);
}

if ($update) {
	$status = (array) get_parameter ('status');
	$resolutions = (array) get_parameter ('resolutions');
	$config["working_weekends"] = (int) get_parameter("working_weekends", 0);
	$config["mask_emails"] = (int) get_parameter("mask_emails", 0);
	$config["iwu_defaultime"] = get_parameter ("iwu_defaultime", 0.25);
	$config["email_on_incident_update"] = get_parameter ("email_on_incident_update", 0);
	$config["limit_size"] = (int) get_parameter ("limit_size");
	$config["show_owner_incident"] = (int) get_parameter ("show_owner_incident", 0);
	$config["show_creator_incident"] = (int) get_parameter ("show_creator_incident", 0);
	$config["auto_incident_close"] = get_parameter ("auto_incident_close", "72");
	$config["iw_creator_enabled"] = get_parameter ("iw_creator_enabled", 0);
	$config["incident_creation_wu"] = get_parameter ("incident_creation_wu", 0);
	$config["incident_type_change"] = (int) get_parameter ("incident_type_change", 0);
	$config["change_incident_datetime"] = (int) get_parameter ("change_incident_datetime", 0);
	$config["enabled_ticket_editor"] = get_parameter ("enabled_ticket_editor", 0);
	$config["ticket_owner_is_creator"] = (int) get_parameter ("ticket_owner_is_creator", 0);
	$config["show_user_name"] = (int) get_parameter ("show_user_name", 0);
	$config["required_ticket_type"] = (int) get_parameter ("required_ticket_type", 0);
	$config["show_creator_blank"] = (int) get_parameter ("show_creator_blank", 0);
	$config["change_creator_owner"] = (int) get_parameter ("change_creator_owner", 0);
	$config["check_closed_incidents"] = (int) get_parameter ("check_closed_incidents", 0);
	$config["days_check_closed_incidents"] = (int) get_parameter ("days_check_closed_incidents", 15);
	$config["external_modify_tickets"] = (int) get_parameter ("external_modify_tickets", 0);
	
	
	update_config_token ("working_weekends", $config["working_weekends"]);	
	update_config_token ("mask_emails", $config["mask_emails"]);
	update_config_token ("iwu_defaultime", $config["iwu_defaultime"]);
	update_config_token ("email_on_incident_update", $config["email_on_incident_update"]);
	update_config_token ("limit_size", $config["limit_size"]);
	update_config_token ("show_owner_incident", $config["show_owner_incident"]);
	update_config_token ("show_creator_incident", $config["show_creator_incident"]);
	update_config_token ("auto_incident_close", $config["auto_incident_close"]);
	update_config_token ("iw_creator_enabled", $config["iw_creator_enabled"]);
	update_config_token ("incident_creation_wu", $config["incident_creation_wu"]);
	update_config_token ("incident_type_change", $config["incident_type_change"]);
	update_config_token ("change_incident_datetime", $config["change_incident_datetime"]);
	update_config_token ("enabled_ticket_editor", $config["enabled_ticket_editor"]);
	update_config_token ("ticket_owner_is_creator", $config["ticket_owner_is_creator"]);
	update_config_token ("show_user_name", $config["show_user_name"]);
	update_config_token ("required_ticket_type", $config["required_ticket_type"]);
	update_config_token ("show_creator_blank", $config["show_creator_blank"]);
	update_config_token ("check_closed_incidents", $config["check_closed_incidents"]);
	update_config_token ("days_check_closed_incidents", $config["days_check_closed_incidents"]);
	update_config_token ("external_modify_tickets", $config["external_modify_tickets"]);
		
	foreach ($status as $id => $name) {
		$sql = sprintf ('UPDATE tincident_status SET name = "%s"
			WHERE id = %d',
			$name, $id);
		process_sql ($sql);
	}
	
	foreach ($resolutions as $id => $name) {
		$sql = sprintf ('UPDATE tincident_resolution SET name = "%s"
			WHERE id = %d',
			$name, $id);
		process_sql ($sql);
	}
		
	echo ui_print_success_message (__('Updated successfuly'), '', true, 'h3', true);
}

echo '<form method="post">';

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table';
$table->colspan = array ();
$table->data = array ();

$status = get_db_all_rows_in_table ('tincident_status');

foreach ($status as $stat) {
	$data = array ();
	
	$data[0] = print_input_text ('status['.$stat['id'].']', $stat['name'],
		'', 35, 255, true);
	
	array_push ($table->data, $data); 
}

$table_status = print_table ($table,true);

$table->data = array ();

$resolutions = get_db_all_rows_in_table ('tincident_resolution');

foreach ($resolutions as $resolution) {
	$data = array ();
	
	$data[0] = print_input_text ('resolutions['.$resolution['id'].']', $resolution['name'],
		'', 35, 255, true);
	
	array_push ($table->data, $data); 
}

$table_resolutions = print_table ($table, true);

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table';
$table->colspan = array ();
$table->data = array ();

$date_table = "<table >";
$date_table .= "<tr>";
$date_table .= "<td>";
$date_table .= "<input id='new_day' type='text' name='new_day' width='15' size='15'>";
$date_table .= "</td>";
$date_table .= "<td>";
$date_table .= "<input type='submit' class='sub create' name='add_day' value='".__("Add")."'>";
$date_table .= "</td>";
$date_table .= "</tr>";
$date_table .= "</table>";

$table->data[0][0] = print_checkbox ("working_weekends", 1, $config["working_weekends"], 
					true, __("Weekends are working days"));
$table->data[0][1] = $date_table;

$holidays_array = calendar_get_holidays();

if ($holidays_array == false) {
	$holidays = "<center><em>".__("No holidays defined")."</em></center>";
} else {
	
	$holidays = "<table>";
	
	foreach ($holidays_array as $ha) {
		$holidays .= "<tr>";
		$holidays .= "<td>";
		$holidays .= $ha["day"];
		$holidays .= "</td>";
		$holidays .= "<td>";
		$holidays .= "<a href='index.php?sec=godmode&sec2=godmode/setup/incidents_setup&del_day=1&day=".$ha["id"]."'><img src='images/cross.png'></a>";
		$holidays .= "</td>";
		$holidays .= "</tr>";
	}
	
	$holidays .= "</table>";
}

$table->data[0][1] .= $holidays;

$holidays_table = print_table($table, true);


$table_anonym = enterprise_hook('setup_print_incident_anonymize');

if ($table_anonym === ENTERPRISE_NOT_HOOK) {
	$table_anonym = "";
}

$incident_reporter_options_email[0] = __('All');
$incident_reporter_options_email[1] = __('Creation, closed, workunit and attachments');
$incident_reporter_options_email[2] = __('Creation and closed');
$incident_reporter_options_email[3] = __('Workunit and attachment');
$incident_reporter_options_email[4] = __('None');

$incident_reporter_options[0] = __('Disabled');
$incident_reporter_options[1] = __('Enabled');

$newsletter_options[0] = __('Disabled');
$newsletter_options[1] = __('Enabled');

$ticket_options[0] = __('Disabled');
$ticket_options[1] = __('Enabled');

echo "<table width='100%' class='search-table-button'>";
echo "<tr>";
echo "<td style=''>".print_input_text ("iwu_defaultime", $config["iwu_defaultime"], '',
	5, 5, true, __('Ticket WU Default time'))."</td>";

echo "<td style=''>".print_select ($incident_reporter_options_email, "email_on_incident_update", $config["email_on_incident_update"], '','','',true, 0, false, __('Send email on every ticket update'))."</td>";

echo "<td style=''>".print_input_text ("limit_size", $config["limit_size"], '',5, 5, true, __('Max. tickets by search') .
	integria_help ("limit_size", true))."</td>";
echo "</tr>";

echo "<tr>";
echo "<td style=''>".print_checkbox ("show_owner_incident", $incident_reporter_options, $config["show_owner_incident"], true, __('Show ticket owner'))."</td>";	

echo "<td style=''>".print_checkbox ("show_creator_incident", $incident_reporter_options, $config["show_creator_incident"], true, __('Show ticket creator'))."</td>";

echo "<td style=''>".print_input_text ("auto_incident_close", $config["auto_incident_close"], '', 10, 10, true, __('Auto ticket close').
	integria_help ("auto_incident_close", true))."</td>";
echo "</tr>";

echo "<tr>";
echo "<td style=''>".print_checkbox ("iw_creator_enabled", 1, $config["iw_creator_enabled"], true, __('Enable IW to change creator'))."</td>";

echo "<td style=''>".print_checkbox ("incident_creation_wu", $newsletter_options, $config["incident_creation_wu"], true, __('Editor adds a WU on ticket creation'))."</td>";

echo "<td style=''>".print_checkbox ("enabled_ticket_editor", $ticket_options, $config["enabled_ticket_editor"], true, __('Enable quick edit mode'))."</td>";

echo "<tr>";
echo "<td style=''>".print_checkbox ("incident_type_change", 1, $config["incident_type_change"], true, __('Allow to change the ticket type'))."</td>";

echo "<td style=''>".print_checkbox ("change_incident_datetime", 1, $config["change_incident_datetime"], true, __('Allow to set the date/time in creation '))."</td>";

echo "</tr>";

echo "<tr>";
echo "<td style=''>".print_checkbox ("ticket_owner_is_creator", 1, $config["ticket_owner_is_creator"], true, __('Ignore user defined by the group for owner'))."</td>";

echo "<td style=''>".print_checkbox ("show_user_name", 1, $config["show_user_name"], true, __('Show user name instead of id in the ticket search'))."</td>";

echo "<td style=''>".print_checkbox ("required_ticket_type", 1, $config["required_ticket_type"], true, __('Required ticket type'))."</td>";
echo "</tr>";

echo "<tr>";
echo "<td style=''>".print_checkbox ("show_creator_blank", 1, $config["show_creator_blank"], true, __('Ignore user creator by default'))."</td>";

echo "<td style=''>".print_checkbox ("change_creator_owner", 1, $config["change_creator_owner"], true, __('Allow to change user creator and user owner'))."</td>";

echo "<td style=''>".print_checkbox ("external_modify_tickets", 1, $config["external_modify_tickets"], true, __('Allow to external users to modify their tickets'))."</td>";
echo "</tr>";

if ($is_enterprise) {
	echo "<tr>";
	echo "<td><h3>".__('Workflow')."</h3></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td style=''>".print_checkbox ("check_closed_incidents", 1, $config["check_closed_incidents"], true, __('Check closed tickets when running workflow rules'))."</td>";
	echo "<td style=''>".print_input_text ("days_check_closed_incidents", $config["days_check_closed_incidents"], '', 5, 255, true, __('Days to check closed tickets'));
	echo "</tr>";
}

echo "<tr>";
echo "<td><h3>".__('Status')."</h3></td>";
echo "<td><h3>".__('Resolutions')."</h3></td>";
echo "<td><h3>".__("Non-working days")."</h3></td>";
echo "</tr>";

echo "<tr>";
echo "<td style='vertical-align:top; width: 280px'>".$table_status."</td>";
echo "<td style='vertical-align:top; width: 280px'>".$table_resolutions."</td>";
echo "<td style='vertical-align:top; '>".$holidays_table;
echo $table_anonym;
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<div class='button-form'>";
print_input_hidden ('update', 1);
print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo "</div>";

echo '</form>';
?>

<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>

<script>
add_datepicker ("#new_day", null);
</script>
