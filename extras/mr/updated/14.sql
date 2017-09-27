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
-- Table tworkflow_condition
-- ---------------------------------------------------------------------

ALTER TABLE `tworkflow_condition` ADD `sla` int NOT NULL default 0;
ALTER TABLE `tworkflow_condition` ADD `string_match` varchar(250)  NOT NULL default '';
ALTER TABLE `tworkflow_condition` ADD `resolution` int NOT NULL default 0;
ALTER TABLE `tworkflow_condition` ADD `id_task` int(10) NOT NULL default 0;
ALTER TABLE `tworkflow_condition` ADD `id_ticket_type` int(10) NOT NULL default 0;
ALTER TABLE `tworkflow_condition` ADD `type_fields` varchar(300)  NOT NULL default '';

-- ---------------------------------------------------------------------
-- Table tinventory_reports
-- ---------------------------------------------------------------------

ALTER TABLE `tinventory_reports` ADD `id_group` mediumint(8) unsigned NOT NULL;
