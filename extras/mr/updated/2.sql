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

CREATE TABLE `tuser_field` ( 
	`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
	`label` varchar(100) NOT NULL DEFAULT '', 
	`type` enum('textarea','text','combo') DEFAULT 'text', 
	`combo_value` text, 
	PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tuser_field_data` ( 
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, 
	`id_user` varchar(60) NOT NULL DEFAULT '',
	`id_user_field` mediumint(8) unsigned NOT NULL, 
	`data` text, PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tincident_type_field ADD global_id mediumint(8) unsigned;

ALTER TABLE tinvoice ADD `internal_note` mediumtext NOT NULL;

ALTER TABLE tattachment ADD `id_invoice` bigint(20) NOT NULL default '0';