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
ALTER TABLE tattachment MODIFY COLUMN `timestamp` datetime NOT NULL default '0000-00-00 00:00:00';

-- ---------------------------------------------------------------------
-- Table tincident_type_field
-- ---------------------------------------------------------------------
ALTER TABLE tincident_type_field MODIFY COLUMN `combo_value` LONGTEXT default NULL;
ALTER TABLE tincident_type_field MODIFY COLUMN `linked_value` LONGTEXT default NULL;

-- ---------------------------------------------------------------------
-- Table tincidencia
-- ---------------------------------------------------------------------
ALTER TABLE tincidencia ADD COLUMN `extra_data3` varchar(100) NOT NULL default '';
ALTER TABLE tincidencia ADD COLUMN `extra_data4` varchar(100) NOT NULL default '';
