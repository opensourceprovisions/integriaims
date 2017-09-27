<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2013 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! $id) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead forward");
	include ("general/noaccess.php");
	exit;
}

$write_permission = check_crm_acl ('lead', 'cw', $config['id_user'], $id);
$manage_permission = check_crm_acl ('lead', 'cm', $config['id_user'], $id);
if (!$write_permission && !$manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to a lead forward");
	include ("general/noaccess.php");
	exit;
}

$lead = get_db_row('tlead','id',$id);
$user = get_db_row("tusuario", "id_usuario", $config["id_user"]);
$company_user = get_db_sql ("select name FROM tcompany where id = ". $user["id_company"]);

$from = get_parameter ("from", $user["direccion"]);
$to = get_parameter ("to", "");
$subject = get_parameter ("subject", "");
$mail = get_parameter ("mail", "");
$send = (int) get_parameter ("send",0);
$cco = get_parameter ("cco", "");

// Send mail
if ($send) {
	if (($subject != "") AND ($from != "") AND ($to != "")) {
		echo ui_print_success_message (__('Mail queued'), '', true, 'h3', true);

		integria_sendmail ($to, $subject, $mail, false, "", $from, true);

		if ($cco != "")
			integria_sendmail ($cco, $subject, $mail, false, "", $from, true);

		$datetime =  date ("Y-m-d H:i:s");	
		// Update tracking
		$sql = sprintf ('INSERT INTO tlead_history (id_lead, id_user, timestamp, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, "Forwarded lead by mail to $to");
		process_sql ($sql);

		// Update activity
		$comments = __("Forwarded lead by mail to $to"). "&#x0d;&#x0a;" . $mail; // this adds &#x0d;&#x0a; 
		$sql = sprintf ('INSERT INTO tlead_activity (id_lead, written_by, creation, description) VALUES (%d, "%s", "%s", "%s")', $id, $config["id_user"], $datetime, $comments);
		process_sql ($sql);

	} else {
		echo ui_print_error_message (__('Could not be created'), '', true, 'h3', true);
	}
}


// Mark with case ID
$subject = __("Lead forward"). " [#$id] : " . $lead["company"] . " / ". $lead["country"] ;



$mail =  "<b>".__("Lead details"). "</b><br><br>";
$mail .= " ".__("Name") . ": ". $lead["fullname"] . "<br>";
$mail .= " ".__("Company") . ": ". $lead["company"] . "<br>";
$mail .= " ".__("Position") . ": ". $lead["position"] . "<br>";
$mail .= " ".__("Country") . ": ". $lead["country"] . "<br>";
$mail .= " ".__("Language") . ": ". $lead["id_language"] . "<br>";
$mail .= " ".__("Email") . ": ". $lead["email"] . "<br>";
$mail .= " ".__("Phone") . ": ". $lead["phone"] . " / " .$lead["mobile"]. "<br>";
$mail .= " ".__("Comments") . ": ". $lead["description"] . "<br>";
$mail .= "<br>";
$mail .= "--<br>";
$mail .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;".$user["nombre_real"];
$mail .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;".$user["direccion"];
$mail .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;".$company_user;

$table = new StdClass();
$table->width = "100%";
$table->class = "search-table-button";
$table->data = array ();
$table->size = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';

$table->colspan[1][0] = 3;
$table->colspan[2][0] = 3;
$table->colspan[3][0] = 3;

$table->data[0][0] = print_input_text ("from", $from, "", 30, 100, true, __('From'));
$table->data[0][1] = print_input_text ("to", $to, "", 30, 100, true, __('To'));
$table->data[0][2] = print_input_text ("cco", $cco, "", 30, 100, true, __('Send a copy to'));
$table->data[1][0] = print_input_text ("subject", $subject, "", 130, 100, true, __('Subject'));
$table->data[2][0] = print_textarea ("mail", 10, 1, $mail, 'style="height:350px;"', true, __('E-mail'));
$table->data[3][0] = print_submit_button (__('Send email'), 'apply_btn', false, 'class="sub upd"', true);
$table->data[3][0] .= print_input_hidden ('id', $id, true);
$table->data[3][0] .= print_input_hidden ('send', 1, true);


echo '<form method="post" id="lead_mail_go">';
print_table ($table);
echo "</form>";

?>
<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>
<script type="text/javascript">
tinymce.init({
  selector: 'textarea',
force_br_newlines : true,
    force_p_newlines : false,
    forced_root_block : false,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table contextmenu paste code'
  ],
  menubar: false,
  toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
  content_css: 'include/js/tinymce/integria.css',

});

</script>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript" >

validate_form("#lead_mail_go");
// Rules: #text-from
rules = {
	required: true,
	email: true
};
messages = {
	required: "<?php echo __('Email from required')?>",
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-from', rules, messages);
// Rules: #text-to
rules = {
	required: true,
	email: true
};
messages = {
	required: "<?php echo __('Email to required')?>",
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-to', rules, messages);
// Rules: #text-cco
rules = {
	email: true
};
messages = {
	email: "<?php echo __('Invalid email')?>"
};
add_validate_form_element_rules('#text-cco', rules, messages);

</script>
