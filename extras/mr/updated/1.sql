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
-- Table structure for table `ttask_link`
--

CREATE TABLE `ttask_link` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source` int(10) NOT NULL default 0,
  `target` int(10) NOT NULL default 0,
  `type` int(1) NOT NULL default 0,
  PRIMARY KEY  (`id`, `source`, `target`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tinvoice (07/01/2014)
-- ---------------------------------------------------------------------
ALTER TABLE tinvoice ADD invoice_type enum ('Submitted', 'Received') default 'Submitted';
ALTER TABLE tinvoice ADD `id_language` varchar(6) NOT NULL default '';