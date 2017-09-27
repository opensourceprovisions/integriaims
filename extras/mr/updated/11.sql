-- INTEGRIA - the ITIL Management System
-- http://integria.sourceforge.net
-- ==================================================
-- Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
-- http://www.artica.es  <info@artica.es>

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation; version 2
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.


-- ---------------------------------------------------------------------
-- Table tcustom_screen (28/07/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tcustom_screen` (
	`id` int(10) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`content` text NULL,
	`home_enabled` int(1) unsigned default 0,
	`menu_enabled` int(1) unsigned default 0,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tcustom_screen_widget (28/07/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tcustom_screen_widget` (
	`id` int(10) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`class` varchar(255) NOT NULL,
	`description` mediumtext NOT NULL,
	`icon` mediumtext NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uk_class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tcustom_screen_data (28/07/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tcustom_screen_data` (
	`id` int(10) NOT NULL auto_increment,
	`id_custom_screen` int(10) NOT NULL,
	`id_widget_type` int(10) NOT NULL,
	`column` int(2) unsigned NOT NULL,
	`row` int(3) unsigned NOT NULL,
	`data` text NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_custom_screen`) REFERENCES tcustom_screen(`id`) ON DELETE CASCADE, 
	FOREIGN KEY (`id_widget_type`) REFERENCES tcustom_screen_widget(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;