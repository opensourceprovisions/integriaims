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
class DB {
	private static $instance;
	
	private $system;
	private $engine;
	
	public function __construct($engine = 'mysql') {
		$this->system = &$system;
		$this->engine = $engine;
		
		switch ($engine) {
			case 'mysql':
				//NONE
				break;
		}
	}
	
	public static function getInstance($engine = 'mysql') {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self($engine);
		}
		
		return self::$instance;
	}
}
?>
