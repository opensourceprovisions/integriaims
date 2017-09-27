-- Integria - http://integria.sourceforge.net
-- ==================================================
-- Copyright (c) 2007-2016 Artica Soluciones Tecnologicas

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation; version 2
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.


INSERT INTO `tconfig` (`token`, `value`) VALUES  
('language_code','en_GB'),
('block_size','25'),
('db_scheme_version','5.0'),
('db_scheme_build','161223'),
('date_format', 'F j, Y, g:i a'),
('currency', 'eu'),
('sitename', 'Integria IMS'),
('hours_perday','8'),
('FOOTER_EMAIL','&lt;br&#x20;/&gt;&#x20;--&#x20;&lt;br&#x20;/&gt;&#x20;&lt;br&#x20;/&gt;&amp;nbsp;&#x20;&amp;nbsp;&#x20;&amp;nbsp;Thanks&#x20;for&#x20;your&#x20;time&lt;br&#x20;/&gt;&#x20;&amp;nbsp;&#x20;&amp;nbsp;&lt;br&#x20;/&gt;&#x20;&lt;img&#x20;src=&quot;http://integriaims.com/wp-content/uploads/2016/04/logo_integria-1.png&quot;&#x20;alt=&quot;&quot;&#x20;width=&quot;163&quot;&#x20;height=&quot;50&quot;&#x20;/&gt;&lt;br&#x20;/&gt;&amp;nbsp;&#x20;&amp;nbsp;&amp;nbsp;&lt;strong&gt;Integria&#x20;IMS&lt;/strong&gt;&lt;br&#x20;/&gt;&amp;nbsp;&#x20;&amp;nbsp;&#x20;&lt;a&#x20;href=&quot;http://192.168.50.2/integria/&quot;&gt;http://i&lt;/a&gt;ntegriains.com'),
('HEADER_EMAIL','&lt;br&#x20;/&gt;Hello,&#x20;this&#x20;is&#x20;an&#x20;automatic&#x20;message&#x20;from&#x20;Integria&#x20;IMS,&#x20;please&#x20;do&#x20;not&#x20;reply&#x20;this&#x20;mail.&lt;br&#x20;/&gt;&amp;nbsp;&lt;br&#x20;/&gt;&amp;nbsp;'),
('notification_period','24'),
('limit_size','250'),
('api_password',''),
('flash_charts','1'),
('fontsize', 6),
('auth_methods', 'mysql'),
('wiki_plugin_dir', 'include/wiki/plugins/'),
('conf_var_dir', 'wiki_data/'),
('enable_pass_policy', 0),
('pass_size', 4),
('pass_needs_numbers', 0),
('pass_needs_symbols', 0),
('pass_expire', 0),
('first_login', 0),
('mins_fail_pass', 5),
('number_attempts', 5),
('max_days_events', 30),
('max_days_incidents', 0),
('max_days_wu', 0),
('max_days_wo', 0),
('max_days_audit', 15),
('max_days_session', 7),
('max_days_workflow_events', 900),
('max_days_fs_files', 7),
('max_days_files_track', 15),
('update_manager_installed', 1),
('current_package', 58),
('url_updatemanager', 'https://firefly.artica.es/integriaupdate5/server.php'),
('license', 'INTEGRIA-FREE'),
('login_background', 'paisaje.jpg'),
('inventory_default_owner', 'admin'),
('minor_release', 56),
('ticket_owner_is_creator', 0);

-- Default password is 'integria'

INSERT INTO `tusuario` (id_usuario, nombre_real, password, comentarios, fecha_registro, direccion, telefono, nivel, avatar, lang, pwdhash, disabled) VALUES ('admin','Default Admin','2f62afb6e17e46f0717225bcca6225b7','Default Integria Admin superuser. Please change password ASAP','2007-03-27 18:59:39','admin@integria.sf.net','555-555-555',1,'moustache3','','',0);


INSERT INTO `tgrupo` (id_grupo, nombre, icon, parent, id_user_default) VALUES (1,'All','world.png',0, 'admin');
INSERT INTO `tgrupo` (id_grupo, nombre, icon, parent, id_user_default) VALUES (2,'Customer #A','eye.png',0, 'admin');
INSERT INTO `tgrupo` (id_grupo, nombre, icon, parent, id_user_default) VALUES (3,'Customer #B','eye.png',0, 'admin');
INSERT INTO `tgrupo` (id_grupo, nombre, icon, parent, id_user_default) VALUES (4,'Engineering','computer.png',0, 'admin');

INSERT INTO `tlanguage` VALUES ('en_GB','English');
INSERT INTO `tlanguage` VALUES ('es','Español');
INSERT INTO `tlanguage` VALUES ('ru','Русский');
INSERT INTO `tlanguage` VALUES ('fr','Français');
INSERT INTO `tlanguage` VALUES ('zh_CN','简化字');
INSERT INTO `tlanguage` VALUES ('de','Deutch');
INSERT INTO `tlanguage` VALUES ('pl','Polski');


INSERT INTO `tlink` VALUES  (1,'Artica','http://artica.es'), (2,'Integria IMS','http://integriaims.com/'), (3, 'Forum', 'http://forums.integriaims.com/'), (4, 'Support', 'https://support.artica.es/');

INSERT INTO `tproject` VALUES  (-1,'Non imputable hours (Special)','','0000-00-00','0000-00-00','',1,0, "");

ALTER TABLE tproject AUTO_INCREMENT = 1;

INSERT INTO `ttask` (`id`, `id_project`, `id_parent_task`, `name`, `description`, `completion`, `priority`, `dep_type`, `start`) VALUES (-1,-1,0,'Vacations','',0,0,0,'0000-00-00'),(-2,-1,0,'Health issues','',0,0,0,'0000-00-00'),(-3,-1,0,'Not justified','',0,0,0,'0000-00-00'), (-4,-1,0,'Workunits lost (without project/task)','',0,0,0,'0000-00-00');

ALTER TABLE ttask AUTO_INCREMENT = 1;

INSERT INTO tincident_resolution (id, name) VALUES 
(1,'Fixed'), 
(2,'Invalid'), 
(3,'Wont fix'), 
(4,'Duplicate'), 
(5,'Works for me'), 
(6,'Incomplete'), 
(7,'Expired'), 
(8,'Moved'), 
(9,'In process');

INSERT INTO tincident_status (id,name) VALUES 
(1,'New'), 
(2,'Unconfirmed'), 
(3,'Assigned'), 
(4,'Re-opened'), 
(5,'Pending to be closed'), 
(6,'Pending on a third person'), 
(7,'Closed');

INSERT INTO `trole` VALUES (1,'Project manager','',125),(2,'Systems engineer','',40),(3,'Junior consultant','',50),(4,'Junior programmer','',45),(5,'Senior programmer','',65),(6,'Analyst','',75),(7,'Senior consultant','',75),(8,'Support engineer','',30);

INSERT INTO `tprofile` VALUES (1,'Standard&#x20;user',1,1,0,0,0,0,1,1,0,1,0,0,1,0,1,1,0,1,1,0,1,1,0,1,1,0,0,1,0,0,0,0,1,0,0),(2,'Incident&#x20;Manager',1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1),(3,'Project&#x20;Manager',0,0,0,1,0,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0),(4,'Escalate',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0),(5,'QA&#x20;Engineer',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0);

INSERT INTO `tobject_type` VALUES (1,'Computer','','computer.png',2,0),
(2,'Pandora&#x20;agents','Imported&#x20;agents&#x20;from&#x20;Pandora&#x20;FMS','pandora.png',0,1),
(3,'CDROM','','cd-dvd.png',0,0),
(4,'NIC','','network.png',0,0),
(5,'Software','','box.png',0,0),
(6,'RAM','','memory-card.png',0,0),
(7,'Service','','engine.png',0,0),
(8,'CPU','','server.png',0,0),
(9,'HD','','harddisk.png',0,0),
(10,'Video','','system-monitor.png',0,0),
(11,'Patches','','attachment.png',0,0),
(12,'Monitors','','display.png',0,0),
(13,'Motherboard','','file-manager.png',0,0),
(14,'Printers','','printer.png',0,0),
(15,'product_ID','','card-id.png',0,0),
(16,'product_key','','keys.png',0,0),
(17,'Users','','human.png',0,0);

INSERT INTO `tobject_type_field` VALUES (1,1,'Serial&#x20;Number','numeric','','','','','',1,0,1,0,''),
(2,1,'CPU','text','','','','','',0,0,1,0,''),
(3,1,'Memory','text','','','','','',0,0,1,0,''),
(4,1,'IP&#x20;Address','text','','','','','',1,0,1,0,''),
(5,1,'MAC&#x20;Address','text','','','','','',1,0,1,0,''),
(6,1,'Users','text','','','','','',0,0,0,0,''),
(7,1,'File&#x20;system','text','','','','','',0,0,0,0,''),
(8,NULL,'OS','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(9,NULL,'IP Address','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(10,NULL,'URL Address','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(11,NULL,'ID Agent','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(12,2,'OS','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(13,2,'IP Address','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(14,2,'URL Address','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(15,2,'ID Agent','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(16,2,'Users','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(17,2,'File system','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(19,3,'Description','text','','','','','',0,0,0,0,''),
(20,3,'Mount&#x20;point','text','','','','','',0,0,0,0,''),
(22,4,'MAC','text','','','','','',1,0,0,0,''),
(23,4,'IP&#x20;Address','text','','','','','',0,0,0,0,''),
(26,5,'Version','text','','','','','',0,0,0,0,''),
(27,6,'Size','text','','','','','',0,0,0,0,''),
(28,6,'Type','text','','','','','',0,0,0,0,''),
(29,7,'Path','text','','','','','',0,0,0,0,''),
(30,7,'Status','text','','','','','',0,0,0,0,''),
(31,8,'Speed','text','','','','','',0,0,0,0,''),
(32,8,'Model','text','','','','','',0,0,0,0,''),
(33,9,'Capacity','text','','','','','',0,0,0,0,''),
(34,9,'Name','text','','','','','',0,0,0,0,''),
(35,10,'Memory','text','','','','','',0,0,0,0,''),
(36,10,'Type','text','','','','','',0,0,0,0,''),
(37,11,'Description','text','','','','','',0,0,0,0,''),
(38,11,'Extra','text','','','','','',0,0,0,0,''),
(39,11,'Internal&#x20;Code','text','','','','','',0,0,0,0,''),
(40,12,'Description','text','','','','','',0,0,0,0,''),
(41,13,'Name','text','','','','','',0,0,0,0,''),
(42,13,'Id','text','','','','','',0,0,0,0,''),
(43,14,'Name','text','','','','','',0,0,0,0,''),
(44,17,'User','text','','','','','',0,0,0,0,''),
(45,2,'OS Version','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(46,2,'Group','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(47,2,'Domain','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(48,2,'Hostname','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(49,2,'Architecture','text',NULL,NULL,NULL,'','',0,0,1,0,''),
(50,2,'SimID','text',NULL,NULL,NULL,'','',0,0,1,0,'');


INSERT INTO `tupdate_settings` VALUES ('customer_key', 'INTEGRIA-FREE'), ('updating_binary_path', 'Path where the updated binary files will be stored'), ('updating_code_path', 'Path where the updated code is stored'), ('dbname', ''), ('dbhost', ''), ('dbpass', ''), ('dbuser', ''), ('dbport', ''), ('proxy', ''), ('proxy_port', ''), ('proxy_user', ''), ('proxy_pass', '');

INSERT INTO tlead_progress (id,name) VALUES 
(0,'New'), 
(20,'Meeting arranged'), 
(40,'Needs discovered'), 
(60,'Proposal delivered'), 
(80,'Offer accepted'), 
(100,'Closed, not response or dead'),
(101,'Closed, lost'), 
(102,'Closed, invalid or N/A'), 
(200,'Closed successfully');

UPDATE tlead_progress SET `id`=0 WHERE `id`=1;

INSERT INTO `treport_type` (`nombre`) VALUES ('List');
INSERT INTO `treport_subtype` (`nombre`) VALUES ('Tickets');
INSERT INTO `tfolder_report` (`nombre`) VALUES ('Default Folder');

INSERT INTO `tmenu_visibility` (`menu_section`,`id_group`,`mode`) VALUES ('customers',1,0),('wiki',1,0);

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
