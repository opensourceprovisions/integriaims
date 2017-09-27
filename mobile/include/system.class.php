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

if (!isset($config)) {
	require_once('../include/config.php');
	require_once ($config["homedir"].'/include/load_session.php');
}

//Singleton
class System {
	private static $instance;
	
	private $session;
	private $config;
	
	function __construct() {
		$this->loadConfig();
		
		DB::getInstance($this->getConfig('db_engine', 'mysql'));
		
		if (!session_id()) session_start();
		$this->session = $_SESSION;
		session_write_close();
	}
	
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	private function loadConfig() {
		global $config;
		
		$this->config = &$config;
	}
	
	public function getRequest($name, $default = null) {
		$return = $default;
		
		if (isset($_POST[$name])) {
			$return = safe_input ($_POST[$name]);
		}
		else {
			if (isset($_GET[$name])) {
				$return = safe_input ($_GET[$name]);
			}
		}
		
		return $return;
	}
	
	public function getConfig($name, $default = null) {
		if (!isset($this->config[$name])) {
			return $default;
		}
		else {
			return $this->config[$name];
		}
	}
	
	public function setSession($name, $value, $serialize = true) {
		$this->session[$name] = $serialize ? serialize($value) : $value;
		
		session_start();
		$_SESSION = $this->session;
		session_write_close();
	}
	
	public function getSession($name, $default = null) {
		if (!isset($this->session[$name])) {
			return $default;
		}
		else {
			return unserialize($this->session[$name]);
		}
	}
	
	public function sessionDestroy() {
		session_start();
		session_destroy();
	}
	
	public function getPageSize() {
		return 10;
	}
	
	public function checkACL($access = "AR", $group_id = 0) {
		if (give_acl($this->getConfig('id_user'), $group_id, $access)) {
			return true;
		}
		
		return false;
	}
	
}
?>
