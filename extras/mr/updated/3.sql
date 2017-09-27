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


ALTER TABLE tsla MODIFY COLUMN `min_response` float(11,2) NOT NULL DEFAULT 0.0;
ALTER TABLE tsla MODIFY COLUMN `max_response` float(11,2) NOT NULL DEFAULT 0.0;
ALTER TABLE tsla MODIFY COLUMN `max_inactivity` float(11,2) unsigned NULL default 96.0;

ALTER TABLE tworkunit ADD `work_home` tinyint(1) unsigned NOT NULL DEFAULT 0;
