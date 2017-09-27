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
-- temail_template
-- ---------------------------------------------------------------------
CREATE TABLE `temail_template` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `id_group` int(10) unsigned NOT NULL default '0',
  `template_action` varchar(200) NOT NULL default '',
  `predefined_templates` INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into temail_template (name, id_group, template_action, predefined_templates) values
('incident_create', 0, 0, 1),
('incident_subject_create', 0, 1, 1),
('incident_close', 0, 2, 1),
('incident_subject_close', 0, 3, 1),
('incident_subject_attach', 0, 4, 1),
('incident_subject_delete', 0, 5, 1),
('incident_subject_new_wu', 0, 6, 1),
('incident_update_wu', 0, 7, 1),
('incident_subject_update', 0, 8, 1),
('incident_update', 0, 9, 1),
('incident_sla_max_inactivity_time', 0, 10, 1),
('incident_sla_max_inactivity_time_subject', 0, 11, 1),
('incident_sla_max_response_time', 0, 12, 1),
('incident_sla_max_response_time_subject', 0, 13, 1),
('incident_sla_min_response_time', 0, 14, 1),
('incident_sla_min_response_time_subject', 0, 15, 1),
('new_entry_calendar', 0, 16, 1),
('update_entry_calendar', 0, 17, 1);