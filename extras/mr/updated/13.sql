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
-- Table tattachment (01/10/2014)
-- ---------------------------------------------------------------------

ALTER TABLE `tattachment` ADD `id_contract` mediumint(8) unsigned NOT NULL;

-- ---------------------------------------------------------------------
-- Table tgrupo
-- ---------------------------------------------------------------------

ALTER TABLE `tgrupo` ADD `email_from` varchar(150) default '';

-- ---------------------------------------------------------------------
-- Table tproject
-- ---------------------------------------------------------------------

ALTER TABLE `tproject` ADD `cc` varchar(150) default '';

-- ---------------------------------------------------------------------
-- Table ttask
-- ---------------------------------------------------------------------

ALTER TABLE `ttask` ADD `cc` varchar(150) default '';

