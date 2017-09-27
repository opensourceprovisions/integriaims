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
-- Table tcompany_field (24/05/2016)
-- ---------------------------------------------------------------------

CREATE TABLE `tcompany_field` ( 
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
-- Table tcompany_field_data (24/05/2016)
-- ---------------------------------------------------------------------

CREATE TABLE `tcompany_field_data` ( 
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, 
  `id_company` mediumint(8) unsigned NOT NULL,
  `id_company_field` mediumint(8) unsigned NOT NULL, 
  `data` text, 
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_company_field`) REFERENCES tcompany(`id`)
          ON DELETE CASCADE,
    FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Add type date
-- ---------------------------------------------------------------------

ALTER TABLE `tincident_type_field` MODIFY `type` enum('text', 'textarea', 'combo', 'linked', 'numeric', 'date') DEFAULT 'text';

-- ---------------------------------------------------------------------
-- change type text
-- ---------------------------------------------------------------------

ALTER TABLE `tincident_type` MODIFY `id_group` text NOT NULL default '';


-- ---------------------------------------------------------------------
-- tpending_mail
-- ---------------------------------------------------------------------

ALTER TABLE `tpending_mail` ADD COLUMN `image_list` text DEFAULT NULL;

-- ---------------------------------------------------------------------
-- tconfig
-- ---------------------------------------------------------------------

UPDATE tconfig SET `value`='Please do not respond directly this email, has been automatically created by Integria (http://integriaims.com).\n\nThanks for your time and have a nice day\n\n' WHERE `token`='FOOTER_EMAIL';
