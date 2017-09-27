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
-- Table tnewsboard (11/07/2014)
-- ---------------------------------------------------------------------
ALTER TABLE tnewsboard ADD `id_group` int(10) NOT NULL default 0;
ALTER TABLE tnewsboard ADD `expire` tinyint(1) DEFAULT 0;
ALTER TABLE tnewsboard ADD `expire_timestamp` DATETIME  NOT NULL DEFAULT 0;
ALTER TABLE tnewsboard ADD `creator` varchar(255) NOT NULL default '';
