<?php 
// INTEGRIA IMS
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2016 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

global $config;

require_once($config["homedir"].'/include/swiftmailer/swift_required.php');

/**
 * Returns a valid transport for Swift_Mailer.
 *
 * @param mixed[] $snmp_conf Connection parameters.
 * An empty array will cause a sendmail transport be returned.
 * Params:
 *   string host  Server hostname
 *   int    port  Server port
 *   string proto Connection protocol: ssl, sslv2, sslv3 or tsl. Empty for tcp
 *   string user  Auth username
 *   string pass  Auth password
 *
 * @return Swift_Transport The mail transport.
 *
 * @throws Swift_TransportException if the transport can't connect
 */
function mail_get_transport ($snmp_conf = array()) {
	//~ debugPrint($snmp_conf, true);
	if (empty($snmp_conf)) {
		// Use the system sendmail
		debugPrint('sendmail', true);
		return Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -t -bv');
	}
	
	if (empty($snmp_conf['host']) || empty($snmp_conf['port'])) {
		throw new Exception('Invalid SNMP connection data');
	}
	
	//~ debugPrint('smtp', true);
	$transport = Swift_SmtpTransport::newInstance();
	$transport->setHost($snmp_conf['host']);
	$transport->setPort((int) $snmp_conf['port']);
	$transport->setUsername($snmp_conf['user']);
	$transport->setPassword($snmp_conf['pass']);
	
	if (!empty($snmp_conf['proto'])) {
		$transport->setEncryption($snmp_conf['proto']);
	}
	
	// Try the connection. Can throw a 'Swift_TransportException'
	$transport->start();
	
	return $transport;
}

/**
 * Returns a valid mailer for Swift.
 *
 * @param Swift_Transport $transport Mail transport (optional).
 *   If the transport is empty, a transport will be created using
 *   the $config values. If $config['smtp_host'] is empty, the mailer
 *   will be the local sendmail. 
 *
 * @return Swift_Mailer The mailer which can send messages.
 *
 * @throws Swift_TransportException if the transport can't connect
 */
function mail_get_mailer ($transport = null) {
	global $config;
	
	if (empty($transport)) {
		$transport_conf = array();
		if (!empty($config['smtp_host'])) {
			$transport_conf['host'] = $config['smtp_host'];
			
			if (!empty($config['smtp_port'])) {
				$transport_conf['port'] = $config['smtp_port'];
			}
			if (!empty($config['smtp_user'])) {
				$transport_conf['user'] = $config['smtp_user'];
			}
			if (!empty($config['smtp_pass'])) {
				$transport_conf['pass'] = $config['smtp_pass'];
			}
			if (!empty($config['smtp_proto'])) {
				$transport_conf['proto'] = $config['smtp_proto'];
			}
		}
		$transport = mail_get_transport($transport_conf);
	}
	
	return Swift_Mailer::newInstance($transport);
}

/**
 * Sends an email.
 *
 * @param Swift_Transport $email 
 * @param Swift_Mailer $mailer Mailer (optional).
 *   If the mailer is empty, a mailer will be created using
 *   the $config values.
 *
 * @return int Number of sent messages.
 *
 * @throws Swift_TransportException if the transport can't connect
 * @throws Exception Unexpected excptions
 */
function mail_send ($email, $mailer = null) {
	global $config;
	
	if (empty($email)) return;
	if (empty($mailer)) $mailer = mail_get_mailer();
	
	// Headers must be comma separated
	$extra_headers = (isset($email["extra_headers"]))
		? explode(",", $email["extra_headers"])
		: array();
	
	$message = Swift_Message::newInstance($email['subject']);
	
	if (empty($email['from'])) {
		$message->setFrom($config['mail_from']);
	}
	else {
		$message->setFrom($email['from']);
	}
	
	if (!empty($email['cc'])) {
		$message->setCc($email['cc']);
	}

	$to = trim(ascii_output($email['recipient']));
	$toArray = array_map('trim', explode(',', $to));
	if ($toArray) {
		$to = $toArray;
	}
	
	$message->setTo($to);

	if (!empty($email['image_list'])) {
		$images = explode(',', $email['image_list']);
		$body_images = '';
		foreach ($images as $image) {
			if (!file_exists($image)) continue;
			$data = file_get_contents($image);
			if ($data) {
				$embed_image = $message->embed(Swift_Image::fromPath($image));
				$body_images .= '<br><img src="' . $embed_image .'"/>';
			}
		}
	}
	
	$message->setBody('<html><body>'.$email['body'].$body_images.'</body></html>', 'text/html');
	
	if (!empty($email['attachment_list'])) {
		$attachments = explode(',', $email['attachment_list']);
		foreach ($attachments as $attachment) {
			if (is_file($attachment)) {
				$message->attach(Swift_Attachment::fromPath($attachment));
			}
		}
	}
	
	return $mailer->send($message);
}

?>
