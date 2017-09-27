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


CREATE TABLE `tdownload_type` (
	`id` mediumint(8) unsigned NOT NULL auto_increment,
	`name` varchar(250) NOT NULL,
	`description` text,
	`icon` varchar(100),
	PRIMARY KEY (`id`)
);

CREATE TABLE `tdownload_type_file` (
	`id` mediumint(8) unsigned NOT NULL auto_increment,
	`id_type` mediumint(8) unsigned NOT NULL,
	`id_download` mediumint(8) unsigned NOT NULL,
	PRIMARY KEY (`id`)
);