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
include_once($config['homedir'].'/include/functions_setup.php');

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
print_setup_tabs('mail', $is_enterprise);

$update = (bool) get_parameter ("update");

$pending_ok = (bool) get_parameter ("pending_ok");
$pending_delete = (bool) get_parameter ("pending_delete");

if ($pending_ok){
	echo ui_print_success_message (__('Mail queue refreshed'), '', true, 'h3', true);
	process_sql ("UPDATE tpending_mail SET attempts = 0, status = 0 WHERE status = 1");
}

if ($pending_delete){
	echo ui_print_success_message (__('Mail queue deleted'), '', true, 'h3', true);
    process_sql ("DELETE FROM tpending_mail");
}

if ($update) {
	$config["notification_period"] = (int) get_parameter ("notification_period", 86400);
	$config["FOOTER_EMAIL"] = (string) get_parameter ("footer_email", "");
	$config["HEADER_EMAIL"] = (string) get_parameter ("header_email", "");
	$config["mail_from"] = (string) get_parameter ("mail_from");
	$config["smtp_user"] = (string) get_parameter ("smtp_user");
	$config["smtp_pass"] = (string) get_parameter ("smtp_pass");
	$config["smtp_host"] = (string) get_parameter ("smtp_host");
	$config["smtp_port"] = (string) get_parameter ("smtp_port");
	$config["smtp_proto"] = (string) get_parameter ("smtp_proto");
	$config["pop_user"] = (string) get_parameter ("pop_user");
	$config["pop_pass"] = (string) get_parameter ("pop_pass");
	$config["pop_host"] = (string) get_parameter ("pop_host");
	$config["pop_port"] = (string) get_parameter ("pop_port");
	$config["smtp_queue_retries"] = (int) get_parameter ("smtp_queue_retries", 10);
	$config["max_pending_mail"] = get_parameter ("max_pending_mail", 15);
	$config["batch_newsletter"] = get_parameter ("batch_newsletter", 0);
	$config["select_pop_imap"] = get_parameter("select_pop_imap");
	
	update_config_token ("HEADER_EMAIL", $config["HEADER_EMAIL"]);
	update_config_token ("FOOTER_EMAIL", $config["FOOTER_EMAIL"]);
	update_config_token ("notification_period", $config["notification_period"]);
	update_config_token ("mail_from", $config["mail_from"]);
	update_config_token ("smtp_port", $config["smtp_port"]);
	update_config_token ("smtp_host", $config["smtp_host"]);
	update_config_token ("smtp_user", $config["smtp_user"]);
	update_config_token ("smtp_pass", $config["smtp_pass"]);
	update_config_token ("smtp_proto", $config["smtp_proto"]);
	update_config_token ("pop_host", $config["pop_host"]);
	update_config_token ("pop_user", $config["pop_user"]);
	update_config_token ("pop_pass", $config["pop_pass"]);
	update_config_token ("pop_port", $config["pop_port"]);
	update_config_token ("smtp_queue_retries", $config["smtp_queue_retries"]);
	update_config_token ("max_pending_mail", $config["max_pending_mail"]);
	update_config_token ("batch_newsletter", $config["batch_newsletter"]);
	update_config_token ("select_pop_imap", $config["select_pop_imap"]);

	echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
}

$smtp_prococols = array(
	'' => __('None'),
	'ssl' => 'ssl',
	'sslv2' => 'sslv2',
	'sslv3' => 'sslv3',
	'tls' => 'tls'
);

$popimap = array(
	0 => __('POP'),
	1 => __('IMAP')
);

$table = new StdClass();
$table->width = '100%';
$table->class = 'search-table-button';
$table->colspan = array();

$cols = 3;

$table->data = array();

$row = array();
$row[] = print_input_text ("notification_period", $config["notification_period"],
	'', 7, 7, true, __('Notification period') . 
	integria_help ("notification_period", true));
$row[] = print_input_text ("mail_from", $config["mail_from"], '',
	30, 50, true, __('System mail from address'));
$table->data[] = $row;

$row = array();
$row[] = "<br /><h4>".__("SMTP Parameters"). integria_help ("mailsetup", true). "</h4>";
$table->data['smtp_title'] = $row;
$table->colspan['smtp_title'][0] = $cols;

$row = array();
$row[] = print_select ($smtp_prococols, 'smtp_proto', $config['smtp_proto'], '','','',true,0,true, __('Encryption'));
$row[] = print_input_text ("smtp_host", $config["smtp_host"],
	'', 35, 200, true, __('SMTP Host') . 
	print_help_tip (__("Left it blank if you want to use your local mail, instead an external SMTP host"), true));

$row[] = print_input_text ("smtp_port", $config["smtp_port"],
	'', 5, 10, true, __('SMTP Port'));
$table->data[] = $row;

$row = array();
$row[] = print_input_text ("smtp_user", $config["smtp_user"],
	'', 25, 200, true, __('SMTP User'));

$row[] = print_input_text ("smtp_pass", $config["smtp_pass"],
	'', 25, 200, true, __('SMTP Password'));
$row[] = print_button(__("Test"), 'test_smtp', false, '', 'class="sub"', true)
	. '<div id="test_smtp_images" style="display: inline;"></div>';
$table->data[] = $row;

$row = array();
$row[] = print_input_text ("smtp_queue_retries", $config["smtp_queue_retries"], '', 5, 10, true, __('SMTP Queue retries') . 
	print_help_tip (__("This are the number of attempts the mail queue try to send the mail. Should be high (20-30) if your internet connection have frequent downtimes and near zero if its stable"), true));
$row[] = print_input_text ("max_pending_mail", $config["max_pending_mail"], '',
	10, 255, true, __('Max pending mail') . 
	print_help_tip (__("Maximum number of queued emails. When this number is exceeded, an alert is activated"), true));
$row[] = print_input_text ("batch_newsletter", $config["batch_newsletter"], '',
	4, 255, true, __('Max. emails sent per execution') . 
	print_help_tip (__("This means, in each execution of the batch external process (integria_cron). If you set your cron to execute each hour in each execution of that process will try to send this ammount of emails. If you set the cron to run each 5 min, will try this number of mails."), true));
$table->data[] = $row;

$row = array();
$row[] = "<br /><h4>".__("POP/IMAP Parameters")."</h4>";
$table->data['pop-imap'] = $row;
$table->colspan['pop-imap'][0] = $cols;

$row = array();
$row[] = print_select ($popimap, "select_pop_imap", $config["select_pop_imap"], '','','',true,0,true, __('Select IMAP or POP'));
$row[] = print_input_text ("pop_host", $config["pop_host"],
	'', 25, 30, true, __('POP/IMAP Host') . print_help_tip (__("Use ssl://host.domain.com if want to use IMAP with SSL"), true));
$row[] = print_input_text ("pop_port", $config["pop_port"],
	'', 15, 30, true, __('POP/IMAP Port') . 
	print_help_tip (__("POP3: Port 110, IMAP: Port 143, IMAPS: Port 993, SSL-POP: Port 995"), true));
$table->data[] = $row;

$row = array();
$row[] = print_input_text ("pop_user", $config["pop_user"],
	'', 15, 30, true, __('POP/IMAP User'));
$row[] = print_input_text ("pop_pass", $config["pop_pass"], '', 15, 30, true, __('POP/IMAP Password'));
$table->data[] = $row;

$row = array();
$row[] = "<br /><h4>".__("Mail general texts")."</h4>";
$table->data['mail_header_footer'] = $row;
$table->colspan['mail_header_footer'][0] = $cols;

$row = array();
$row[] = print_textarea ("header_email", 9, 40, $config["HEADER_EMAIL"],
	'', true, __('Email header'));
$table->data['header_email'] = $row;
$table->colspan['header_email'][0] = $cols;

$row = array();
$row[] = print_textarea ("footer_email", 15, 40, $config["FOOTER_EMAIL"],
	'', true, __('Email footer'));
$table->data['footer_email'] = $row;
$table->colspan['footer_email'][0] = $cols;

$total_pending = get_db_sql ("SELECT COUNT(*) from tpending_mail");
$row = array();
$row[] = "<br /><h4>".__("Mail queue control")." : ". $total_pending . " " .__("mails in queue") . "</h4>";
$table->data['mail_queue_control'] = $row;
$table->colspan['mail_queue_control'][0] = $cols;

if ($total_pending > 0) {
	$row = array();

	$mail_queue = "<div style='height: 250px; overflow-y: auto;'>";
	$mail_queue .= "<table width=100% class=listing>";
	$mail_queue .= "<tr><th>". __("Date"). "<th>" . __("Recipient") . "<th>" . __("Subject") . "<th>" . __("Attempts")."<th>". __("Status")."</tr>";

	$mails = get_db_all_rows_sql ("SELECT * FROM tpending_mail LIMIT 1000");

	foreach ($mails as $mail) {
		$mail_queue .=  "<tr>";
		$mail_queue .=  "<td style='font-size: 9px;'>";
		$mail_queue .=  $mail["date"];
		$mail_queue .=  "<td>";
		$mail_queue .=  $mail["recipient"];
		$mail_queue .=  "<td style='font-size: 9px;'>";
		$mail_queue .=  $mail["subject"];
		$mail_queue .=  "<td>";
		$mail_queue .=  $mail["attempts"];
		if ($mail["status"] == 1)
			$mail_queue .=  "<td>".__("Bad mail");
		else
			$mail_queue .=  "<td>".__("Pending");
		$mail_queue .=  "</tr>";
	}

	$mail_queue .= "<tr></tr></table></div>";
	
	$row[] = $mail_queue;
	$table->data['mails_table'] = $row;
	$table->colspan['mails_table'][0] = $cols;
}

$button = print_input_hidden ('update', 1, true);

$button .= print_submit_button (__("Reactivate pending mails"), 'pending_ok', false, 'class="sub create"', true);
$button .= print_submit_button (__("Delete pending mails"), 'pending_delete', false, 'class="sub delete"', true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

echo "<form name='setup' method='post'>";
print_table ($table);
	echo "<div class='button-form'>";
		echo $button;
	echo "</div>";
echo '</form>';
?>

<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>
<script type="text/javascript">
	tinymce.init({
		selector: 'textarea',
		fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",
		force_br_newlines: true,
		force_p_newlines: false,
		forced_root_block: false,
		plugins: [
			'advlist autolink lists link image charmap print preview anchor',
			'searchreplace visualblocks code fullscreen',
			'insertdatetime media table contextmenu paste code'
		],
		menubar: false,
		toolbar: 'undo redo | styleselect | bold italic fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		content_css: 'include/js/tinymce/integria.css'
	});

	$(document).ready(function () {
		var checkTransport = function (host, port, proto, user, pass, cb) {
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				dataType: 'json',
				data: {
					check_transport: 1,
					page: 'include/ajax/mail',
					host: host,
					port: port,
					proto: proto,
					user: user,
					pass: pass
				}
			})
			.done(function(data, textStatus, xhr) {
				cb(null, data);
			})
			.fail(function(xhr, textStatus, errorThrown) {
				cb(errorThrown);
			});
		}
		
		var baseURL = '<?php echo $config['base_url_images']; ?>';
		
		var changeSmtpTestStatus = function (container) {
			return function (status, message) {
				status = status || '';
				message = message || '';
				
				$(container).find('img').remove();
				
				var image = document.createElement('img');
				image.title = message;
				$(image).tooltip({ track: true });
				
				if (status === 'loading') {
					image.src = baseURL + '/images/spinner.gif';
					$(container).append(image);
				}
				else if (status === 'success') {
					image.src = baseURL + '/images/success.png';
					$(container).append(image);
				}
				else if (status === 'failure') {
					image.src = baseURL + '/images/fail.png';
					$(container).append(image);
				}
			}
		}
		
		$('input#button-test_smtp').click(function (e) {
			e.preventDefault();
			
			var changeStatus = changeSmtpTestStatus(document.getElementById('test_smtp_images'));
			
			changeStatus('loading');
			
			var host = $('#text-smtp_host').val();
			var port = $('#text-smtp_port').val();
			var proto = $('#smtp_proto').val();
			var user = $('#text-smtp_user').val();
			var pass = $('#text-smtp_pass').val();
			
			checkTransport(host, port, proto, user, pass, function (err, data) {
				if (err) return changeStatus('failure', err);
				if (data.result) changeStatus('success', data.message);
				else changeStatus('failure', data.message);
			});
		});
	});
</script>
