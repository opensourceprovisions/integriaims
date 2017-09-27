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
-- Table tattachment
-- ---------------------------------------------------------------------
ALTER TABLE tattachment ENGINE = InnoDB;
ALTER TABLE `tattachment` ADD COLUMN `public_key` varchar(100) NULL UNIQUE;
ALTER TABLE `tattachment` ADD COLUMN `file_sharing` tinyint(1) unsigned NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table tattachment_track
-- ---------------------------------------------------------------------
CREATE TABLE `tattachment_track` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_attachment` bigint(20) unsigned NOT NULL,
	`timestamp` datetime NOT NULL,
	`id_user` varchar(60) NOT NULL default '',
	`action` enum('download', 'creation', 'modification', 'deletion') NOT NULL,
	`data` text NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;