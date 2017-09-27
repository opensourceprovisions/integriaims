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
-- Table tprofile
-- ---------------------------------------------------------------------
ALTER TABLE `tprofile` ADD `qa` tinyint(1) NOT NULL default '0';
INSERT INTO `tprofile` (`name`,`ir`,`iw`,`im`,`um`,`dm`,`fm`,`ar`,`aw`,`am`,`pr`,`pw`,`pm`,`tw`,`tm`,`kr`,`kw`,`km`,`vr`,`vw`,`vm`,`wr`,`ww`,`wm`,`cr`,`cw`,`cm`,`cn`,`frr`,`frw`,`frm`,`si`, `qa`) VALUES ('QA Engineer',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1);

-- ---------------------------------------------------------------------
-- Table tincidencia
-- ---------------------------------------------------------------------
ALTER TABLE `tincidencia` ADD `black_medals` int(10) NOT NULL default 0;
ALTER TABLE `tincidencia` ADD `gold_medals` int(10) NOT NULL default 0;
