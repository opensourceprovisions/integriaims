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


CREATE TABLE `tincident_sla_graph_data` (
  `id_incident` int(10) NOT NULL default '0',
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `value` int(1) unsigned NOT NULL default '0',
    KEY `sla_graph_index1` (`id_incident`),
  KEY `idx_utimestamp_sla_graph` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;