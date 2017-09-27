<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

//Set character encoding to UTF-8 - fixes a lot of multibyte character
//headaches
if (function_exists ('mb_internal_encoding')) {
	mb_internal_encoding ("UTF-8");
}

$develop_bypass = 1;

// /include
require_once("include/ui.class.php");
require_once("include/system.class.php");
require_once("include/db.class.php");
require_once("include/user.class.php");

// /operation
require_once('operation/home.php');
require_once('operation/workunits.php');
require_once('operation/workunit.php');
require_once('operation/incidents.php');
require_once('operation/incident.php');
require_once('operation/calendars.php');


$system = System::getInstance();

$user = User::getInstance();
$user->hackInjectConfig();

$action = $system->getRequest('action');
if (!$user->isLogged()) {
	$action = 'login';
} elseif ($action == 'login') {
	$action = '';
}

if ($user->isLogged() && $action != "ajax") {
	
	$user_language = get_user_language ($system->getConfig('id_user'));
	if (file_exists ('../include/languages/'.$user_language.'.mo')) {
		$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
		$l10n->load_tables();
	}
}

switch ($action) {
	case 'ajax':
		$page = $system->getRequest('page', false);
		$method = $system->getRequest('method', false);
		
		switch ($page) {
			case 'user':
				$user->ajax($method);
				break;
			case 'workunits':
				$workunits = new Workunits();
				$workunits->ajax($method);
				break;
			case 'workunit':
				$workunit = new Workunit();
				$workunit->ajax($method);
				break;
			case 'incidents':
				$incidents = new Incidents();
				$incidents->ajax($method);
				break;
			case 'incident':
				$incident = new Incident();
				$incident->ajax($method);
				break;
			case 'calendars':
				$calendars = new Calendars();
				$calendars->ajax($method);
				break;
		}
		return;
		break;
	case 'login':
		if (!$user->checkLogin()) {
			$user->showLogin();
		}
		else {
			if ($user->isLogged()) {
				$user_language = get_user_language ($system->getConfig('id_user'));
				if (file_exists ('../include/languages/'.$user_language.'.mo')) {
					$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
					$l10n->load_tables();
				}
				$home = new Home();
				$home->show();
			}
			else {
				$user->showLoginFail();
			}
		}
		break;
	case 'logout':
		$user->logout();
		$user->showLogin();
		break;
	default:
		$page = $system->getRequest('page');
		switch ($page) {
			case 'home':
				$home = new Home();
				$home->show();
				break;
			case 'workunits':
				$workunits = new Workunits();
				$workunits->show();
				break;
			case 'workunit':
				$workunit = new Workunit();
				$workunit->show();
				break;
			case 'incidents':
				$incidents = new Incidents();
				$incidents->show();
				break;
			case 'incident':
				$incident = new Incident();
				$incident->show();
				break;
			case 'calendars':
				$calendars = new Calendars();
				$calendars->show();
				break;
			default:
				$home = new Home();
				$home->show();
		}
		break;
}
?>
