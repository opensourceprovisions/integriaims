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
-- Table temail_template
-- ---------------------------------------------------------------------


DELETE FROM temail_template WHERE `name`='new_entry_calendar' AND predefined_templates = 1;
DELETE FROM temail_template WHERE `name`='update_entry_calendar' AND predefined_templates = 1;

INSERT INTO temail_template (name, id_group, template_action, predefined_templates) VALUES
('new_entry_calendar', 0, 16, 1),
('update_entry_calendar', 0, 17, 1);
