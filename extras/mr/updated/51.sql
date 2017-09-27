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
-- Table tcontract_field 
-- ---------------------------------------------------------------------

CREATE TABLE `tcontract_field` ( 
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
  `label` varchar(100) NOT NULL DEFAULT '', 
  `type` enum('text', 'textarea', 'combo', 'linked', 'numeric', 'date') DEFAULT 'text', 
  `combo_value` text,
  `parent` mediumint(8) unsigned default 0,
  `linked_value` LONGTEXT default NULL,
  `order` mediumint(8) unsigned default 0, 
  PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tcontract_field_data 
-- ---------------------------------------------------------------------

CREATE TABLE `tcontract_field_data` ( 
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, 
  `id_contract` mediumint(8) unsigned NOT NULL,
  `id_contract_field` mediumint(8) unsigned NOT NULL, 
  `data` text, 
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_contract_field`) REFERENCES tcontract_field(`id`)
          ON DELETE CASCADE,
    FOREIGN KEY (`id_contract`) REFERENCES tcontract(`id`)
          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
