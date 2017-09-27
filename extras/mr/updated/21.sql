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

ALTER TABLE `tworkflow_condition` ADD COLUMN `percent_sla` int unsigned NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table tobject_type
-- ---------------------------------------------------------------------
INSERT INTO `tobject_type` (`name`,`description`,`icon`,`min_stock`,`show_in_list`) 
VALUES ('Monitors','','display.png',0,0),
('Motherboard','','file-manager.png',0,0),
('Printers','','printer.png',0,0),
('product_ID','','card-id.png',0,0),
('product_key','','keys.png',0,0),
('Users','','human.png',0,0);

-- ---------------------------------------------------------------------
-- Table tobject_type_field
-- ---------------------------------------------------------------------

INSERT INTO `tobject_type_field` (`id_object_type`,`label`,`type`,`combo_value`,`external_table_name`,`external_reference_field`,`parent_table_name`,`parent_reference_field`,`unique`,`inherit`,`show_list`)
VALUES (2,'OS Version','text',NULL,NULL,NULL,'','',0,0,1),
(2,'Group','text',NULL,NULL,NULL,'','',0,0,1),
(2,'Domain','text',NULL,NULL,NULL,'','',0,0,1),
(2,'Hostname','text',NULL,NULL,NULL,'','',0,0,1),
(2,'Architecture','text',NULL,NULL,NULL,'','',0,0,1);
 
