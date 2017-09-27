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
-- Table tobject_type_field
-- ---------------------------------------------------------------------

ALTER TABLE `tobject_type_field` MODIFY `type` enum('numeric', 'text', 'combo', 'external', 'date') DEFAULT 'text';
ALTER TABLE `tcontract_field` ADD `show_in_list` int(1) not null default 0; 