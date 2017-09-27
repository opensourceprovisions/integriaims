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

--
-- Table structure for table `tlink`
--

UPDATE `tlink` SET `name` = 'Artica',`link` = 'http://artica.es' WHERE `id_link` = 1;
UPDATE `tlink` SET `name` = 'Integria IMS',`link` = 'http://integriaims.com/' WHERE `id_link` = 2;
UPDATE `tlink` SET `name` = 'Forum', `link` = 'http://forums.integriaims.com/' WHERE `id_link` = 3;
INSERT INTO `tlink` (`name`,`link`) VALUES ('Support', 'https://artica.es/integria/');
