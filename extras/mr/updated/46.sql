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
-- Table tmenu_visibility
-- ---------------------------------------------------------------------
ALTER TABLE `tprofile` ADD `rr` tinyint(1) NOT NULL default '0';
ALTER TABLE `tprofile` ADD `rw` tinyint(1) NOT NULL default '0';
ALTER TABLE `tprofile` ADD `rm` tinyint(1) NOT NULL default '0';

UPDATE `tconfig` SET `value` = '5.0' WHERE `token` = 'db_scheme_version';

CREATE TABLE `tfolder_report` (
 `id` bigint(20) unsigned NOT NULL auto_increment,
 `nombre`  mediumtext NOT NULL,
 `description`  text DEFAULT '',
 `private` tinyint(1) unsigned NOT NULL DEFAULT 0,
 `id_group` varchar(60) NOT NULL default "1",
 `id_user` varchar(60) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
  `type` tinyint unsigned NOT NULL DEFAULT 0,
  `subtype` tinyint unsigned NOT NULL DEFAULT 0,
  `id_group` varchar(60) NOT NULL default "1",
  `id_folder` bigint(20) unsigned default 0,
  `fields_to_show` longtext NOT NULL default '',
  `order_by` text NOT NULL default '',
  `group_by` text NOT NULL default '',
  `where_clause` text NOT NULL default '',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport_type` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport_subtype` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `treport_type` (`nombre`) VALUES ('List');
INSERT INTO `treport_subtype` (`nombre`) VALUES ('Tickets');
INSERT INTO `tfolder_report` (`nombre`) VALUES ('Default Folder');
