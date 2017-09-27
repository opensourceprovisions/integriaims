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

//Singleton
class User {
	private static $instance;
	
	private $user;
	private $logged = false;
	private $errorLogin = false;
	private $logout_action = false;
	
	public function __construct($user = null, $password = null) {
		$this->user = $user;
		$this->errorLogin = false;
		
		global $config;
		require_once ($config["homedir"] . '/include/auth/mysql.php');
		
		if (process_user_login($this->user, $password)) {
			$this->logged = true;
			$this->hackInjectConfig();
		}
		else {
			$this->logged = false;
		}
	}
	
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			//Check if is in the session
			$system = System::getInstance();
			$user = $system->getSession('integria_user', null);
			
			if (!empty($user)) {
				self::$instance = $user;
			}
			else {
				self::$instance = new self;
			}
		}
		
		return self::$instance;
	}
	
	public function hackInjectConfig() {
		//hack for compatibility
		
		if ($this->logged) {
			global $config;
			
			$system = System::getInstance();
			
			$config['id_user'] = $this->user;
			
			$system->setSession('id_usuario', $this->user, false);
			$system->setSession('integria_user', $this);
		}
	}
	
	public function isLogged() {
		$system = System::getInstance();
		
		$autologin = $system->getRequest('autologin', false);
		if ($autologin) {
			$user = $system->getRequest('user', null);
			$password = $system->getRequest('password', null);
			
			if ($this->checkLogin($user, $password)) {
				$this->hackInjectConfig();
			}
		}
		
		return $this->logged;
	}
	
	public function checkLogin($user = null, $password = null) {
		$system = System::getInstance();
		
		if (($user == null) && ($password == null)) {
			$user = $system->getRequest('user', null);
			$password = $system->getRequest('password', null);
		}
		
		if (!empty($user) && !empty($password)) {
			global $config;
			if (include_once($system->getConfig("homedir")."/include/auth/mysql.php")) {
				if (process_user_login($user, $password) !== false) {
					$this->logged = true;
					$this->user = $user;
					$this->errorLogin = false;
				}
				else {
					$this->logged = false;
					$this->errorLogin = true;
				}
			} else {
				$this->logged = false;
				$this->errorLogin = true;
			}
		}
		
		if ($this->logged) {
			$this->hackInjectConfig();
		}
		
		return $this->logged;
	}
	
	public function logout() {
		$this->user = null;
		$this->logged = false;
		$this->errorLogin = false;
		$this->logout_action = true;
		
		$system = System::getInstance();
		$system->setSession('id_usuario', null);
		$system->setSession('integria_user', null);
	}
	
	public function showLogin() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		if ($this->errorLogin) {
			
			$options['type'] = 'onStart';
			$options['title_text'] = __('Login Failed');
			$options['content_text'] = __('User not found in database or incorrect password.');
			$ui->addDialog($options);
			
		}
		$logo = "<img src='../images/integria_logo_header.png' style='border:0px;' alt='Home' >";
		$ui->createHeader("<div style='text-align:center;'>$logo</div>", null, null, "logo");
		$ui->showFooter();
		$ui->beginContent();
			
			$ui->beginForm();
			$ui->formAddHtml(print_input_hidden('action', 'login', true));
			$options = array(
				'name' => 'user',
				'value' => $this->user,
				'placeholder' => __('User'),
				'label' => __('User')
				);
			$ui->formAddInputText($options);
			$options = array(
				'name' => 'password',
				'value' => '',
				'placeholder' => __('Password'),
				'label' => __('Password')
				);
			$ui->formAddInputPassword($options);
			$options = array(
				'value' => __('Login'),
				'icon' => 'star',
				'icon_pos' => 'right'
				);
			$ui->formAddSubmitButton($options);
			$ui->endForm();
		$ui->endContent();
		$ui->showPage();
		
		$this->errorLogin = false;
		$this->logout_action = false;
	}
	
	public function getIdUser() {
		return $this->user; //Oldies methods
	}
	
	public function ajax ($method = false) {
		
		if (! $this->isLogged()) {
			return;
		}
		
		switch ($method) {
			case 'search_users':
				$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
				$users = get_user_visible_users ($this->getIdUser(),"IR", false);
				
				if ($users === false)
					return;
					
				$res = array();
				
				foreach ($users as $user) {
					if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
						array_push($res, array("label" => safe_output($user['nombre_real'])." (".$user['id_usuario'].")", "value" => $user['id_usuario']));
					}
				}
				
				echo json_encode($res);
				break;
			case 'search_users_role':
				$id_project = (int) get_parameter ('id_project');
				$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
				
				$users = get_users_project ($id_project);
				
				if ($users === false)
					return;

				$res = array();
				
				foreach ($users as $user) {
					if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
						array_push($res, array("label" => safe_output($user['nombre_real'])." (".$user['id_usuario'].")", "value" => $user['id_usuario']));
					}
				}
				
				echo json_encode($res);
				break;
		} 
		
	}
	
}
?>
