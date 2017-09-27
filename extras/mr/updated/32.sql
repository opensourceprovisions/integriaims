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
-- Table tworkflow_status_mapping
-- ---------------------------------------------------------------------
CREATE TABLE `tworkflow_status_mapping` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `origin_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  `resolution_id` int(10) unsigned NOT NULL,
  `initial` int default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tobject_type_field
-- ---------------------------------------------------------------------
ALTER TABLE `tobject_type_field` ADD `external_label` text default null;

-- ---------------------------------------------------------------------
-- Table tincidencia
-- ---------------------------------------------------------------------
ALTER TABLE `tincidencia` ADD `blocked` tinyint unsigned NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table tnewsletter_content
-- ---------------------------------------------------------------------
ALTER TABLE `tnewsletter_content` MODIFY `plain`  LONGTEXT;
ALTER TABLE `tnewsletter_content` MODIFY `html`  LONGTEXT;
