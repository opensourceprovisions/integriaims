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
-- Table tincidencia
-- ---------------------------------------------------------------------
ALTER TABLE `tincidencia` ADD `old_status` tinyint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `tincidencia` ADD `old_resolution` tinyint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `tincidencia` ADD `old_status2` tinyint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `tincidencia` ADD `old_resolution2` tinyint unsigned NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table tworkflow_status_mapping
-- ---------------------------------------------------------------------
ALTER TABLE `tworkflow_status_mapping` ADD `resolution_origin_id` int(10) unsigned NOT NULL;
ALTER TABLE `tworkflow_status_mapping` CHANGE COLUMN `resolution_id` `resolution_destination_id` int(10) unsigned NOT NULL;
