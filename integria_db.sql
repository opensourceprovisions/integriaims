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
-- Table structure for table `tusuario`
--

CREATE TABLE `tusuario` (
  `id_usuario` varchar(60) NOT NULL default '',
  `nombre_real` varchar(125) NOT NULL default '',
  `password` varchar(45) default NULL,
  `comentarios` varchar(200) default NULL,
  `fecha_registro` datetime NOT NULL default '0000-00-00 00:00:00',
  `direccion` varchar(100) default '',
  `telefono` varchar(100) default '',
  `nivel` tinyint(1) NOT NULL default '0',
  `avatar` varchar(100) default 'moustache4',
  `lang` varchar(10) default '',
  `pwdhash` varchar(100) default '',
  `disabled` int default 0,
  `id_company` int(10) unsigned NULL default 0,
  `force_change_pass` tinyint(1) unsigned NOT NULL default 0,
  `last_pass_change` DATETIME  NOT NULL DEFAULT 0,
  `last_failed_login` DATETIME  NOT NULL DEFAULT 0,
  `failed_attempt` int(4) NOT NULL DEFAULT 0,
  `login_blocked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `num_employee` varchar(125) NOT NULL default '',
  `enable_login` tinyint(1) NOT NULL default '1',
  `location` tinytext NOT NULL DEFAULT '',
  `disabled_login_by_license` tinyint(1) NOT NULL default '1',
   PRIMARY KEY  (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tgrupo` (
  `id_grupo` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  `banner` varchar(150) default NULL,
  `url` varchar(150) default NULL,
  `lang` varchar(10) default NULL,
  `parent` mediumint(8) unsigned NOT NULL default 0,
  `id_user_default` varchar(60) NOT NULL default '',
  `id_sla` mediumint(8) unsigned default NULL,
  `soft_limit` int(5) unsigned NOT NULL default 0,
  `hard_limit` int(5) unsigned NOT NULL default 0,
  `forced_email` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `email` varchar(128) default '',
  `enforce_soft_limit` int(2) unsigned NOT NULL default 0,
  `id_inventory_default` bigint(20) unsigned NOT NULL,
  `autocreate_user` int(5) unsigned NOT NULL default 0,
  `grant_access` int(5) unsigned NOT NULL default 0,
  `send_welcome` int(5) unsigned NOT NULL default 0,
  `default_company` mediumint(8) unsigned NOT NULL default 0,
  `email_queue` text,
  `email_group` text,
  `welcome_email` text,
  `default_profile` int(10) unsigned NOT NULL default 0,
  `nivel` tinyint(1) NOT NULL default '0',
  `id_incident_type` mediumint(8) unsigned NULL,
  `email_from` varchar(150) default '',
  PRIMARY KEY  (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tproject_group` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tproject` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(240) NOT NULL default '',
  `description` mediumtext NOT NULL,
  `start` date NOT NULL default '0000-00-00',
  `end` date NOT NULL default '0000-00-00',
  `id_owner` varchar(60)  NOT NULL default '',
  `disabled` int(2) unsigned NOT NULL default '0',
  `id_project_group` mediumint(8) unsigned NOT NULL default '0',
  `cc` varchar(150) default '',
  PRIMARY KEY  (`id`),
  KEY `iproject_idx_1` (`id_project_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ttask` (
  `id` int(10) NOT NULL auto_increment,
  `id_project` int(10) NOT NULL default 0,
  `id_parent_task` int(10) NULL default '0',
  `name` varchar(240) NOT NULL default '',
  `description` mediumtext NOT NULL,
  `completion` tinyint unsigned NOT NULL default '0',
  `priority` tinyint unsigned NOT NULL default '0',
  `dep_type` tinyint unsigned NOT NULL DEFAULT 0,
  `start` date NOT NULL default '0000-00-00',
  `end` date NOT NULL default '0000-00-00',
  `hours` int unsigned NOT NULL DEFAULT 0,
  `estimated_cost` float (9,2) unsigned NOT NULL DEFAULT 0.0,
  `periodicity` enum ('none', 'weekly', 'monthly', 'year', '21days', '10days', '15days', '60days', '90days', '120days', '180days') default 'none',
  `count_hours` TINYINT (1) DEFAULT '1',
  `cc` varchar(150) default '',
  PRIMARY KEY  (`id`),
  KEY `itask_idx_1` (`id_project`),
  FOREIGN KEY (`id_project`) REFERENCES tproject(`id`)
     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `tattachment`
--

CREATE TABLE `tattachment` (
  `id_attachment` bigint(20) unsigned NOT NULL auto_increment,
  `id_incidencia` bigint(20) NOT NULL default '0',
  `id_task` int(10) NULL default 0,
  `id_kb` bigint(20) NOT NULL default '0',
  `id_lead` bigint(20) NOT NULL default '0',
  `id_company` bigint(20) NOT NULL default '0',
  `id_todo` bigint(20) NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '',
  `id_contact` mediumint(8) NOT NULL default '0',
  `filename` varchar(255) NOT NULL default '',
  `description` text default '',
  `size` bigint(20) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_invoice` bigint(20) NOT NULL default '0',
  `id_contract` mediumint(8) unsigned NOT NULL,
  `public_key` varchar(100) NULL,
  `file_sharing` tinyint(1) unsigned NULL DEFAULT 0,
  UNIQUE (`public_key`),
  PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `tconfig`
--

CREATE TABLE `tconfig` (
  `id_config` int(10) unsigned NOT NULL auto_increment,
  `token` varchar(100) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `twizard` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_type` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  `id_wizard` mediumint(8) unsigned NULL,
  `id_group` text NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tsla` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  `min_response` float(11,2) NOT NULL DEFAULT 0.0,
  `max_response` float(11,2) NOT NULL DEFAULT 0.0,
  `max_incidents` int(11) NULL default NULL,
  `max_inactivity` float(11,2) unsigned NULL default 96.0,
  `enforced` tinyint NULL default 0,
  `five_daysonly` tinyint NULL default 0,
  `time_from` tinyint NULL default 0,
  `time_to` tinyint NULL default 0,
  `id_sla_base` mediumint(8) unsigned NULL default 0,
  `no_holidays` tinyint NULL default 0,
  `id_sla_type` mediumint(8) unsigned NULL default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `tincidencia`
--

CREATE TABLE `tincidencia` (
  `id_incidencia` bigint(20) unsigned NOT NULL auto_increment,
  `inicio` datetime NOT NULL default '0000-00-00 00:00:00',
  `cierre` datetime NOT NULL default '0000-00-00 00:00:00',
  `titulo` mediumtext DEFAULT NULL,
  `descripcion` mediumtext DEFAULT NULL,
  `id_usuario` varchar(60) NOT NULL default '',
  `estado` tinyint unsigned NOT NULL DEFAULT 0,
  `prioridad` tinyint unsigned NOT NULL DEFAULT 0,
  `id_grupo` mediumint(9) NOT NULL default 0,
  `actualizacion` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_creator` varchar(60) default NULL,
  `id_task` int(10) NOT NULL default '0',
  `resolution` tinyint unsigned NOT NULL DEFAULT 0,
  `epilog` mediumtext NOT NULL,
  `id_parent` bigint(20) unsigned NULL default NULL,
  `sla_disabled` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `affected_sla_id` tinyint unsigned NOT NULL DEFAULT 0,
  `id_incident_type` mediumint(8) unsigned NULL,
  `score` mediumint(8) default 0,
  `email_copy` mediumtext not NULL,
  `editor` varchar(60) NOT NULL default '',
  `id_group_creator` mediumint(9) NOT NULL default 0,
  `last_stat_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `closed_by` varchar(60) NOT NULL default '',
  `extra_data` varchar(100) NOT NULL default '',
  `extra_data2` varchar(100) NOT NULL default '',
  `blocked` tinyint unsigned NOT NULL DEFAULT 0,
  `old_status` tinyint unsigned NOT NULL DEFAULT 0,
  `old_resolution` tinyint unsigned NOT NULL DEFAULT 0,
  `old_status2` tinyint unsigned NOT NULL DEFAULT 0,
  `old_resolution2` tinyint unsigned NOT NULL DEFAULT 0,
  `extra_data3` varchar(100) NOT NULL default '',
  `extra_data4` varchar(100) NOT NULL default '',
  `black_medals` int(10) NOT NULL default 0,
  `gold_medals` int(10) NOT NULL default 0,

  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_idx_1` (`id_usuario`),
  KEY `incident_idx_2` (`estado`),
  KEY `incident_idx_3` (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tincidencia` ADD FOREIGN KEY (`id_parent`) REFERENCES tincidencia(`id_incidencia`)
   ON DELETE CASCADE;

--
-- Table structure for table `tlanguage`
--

DROP TABLE IF EXISTS `tlanguage`;
CREATE TABLE `tlanguage` (
  `id_language` varchar(6) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `tlink`
--

DROP TABLE IF EXISTS `tlink`;
CREATE TABLE `tlink` (
  `id_link` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tsesion` (
  `ID_sesion` bigint(4) unsigned NOT NULL auto_increment,
  `ID_usuario` varchar(60) NOT NULL default '0',
  `IP_origen` varchar(100) NOT NULL default '',
  `accion` varchar(100) NOT NULL default '',
  `descripcion` varchar(200) NOT NULL default '',
  `extra_info` TEXT default NULL,
  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID_sesion`),
  KEY `tsession_idx_1` (`ID_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_track` (
  `id_it` int(10) unsigned NOT NULL auto_increment,
  `id_incident` bigint(20) unsigned NOT NULL default '0',
  `state` int(10) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  `id_aditional` varchar(60) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `extra_info` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_it`),
  KEY `tit_idx_1` (`id_incident`),
  FOREIGN KEY (`id_incident`) REFERENCES tincidencia(`id_incidencia`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `ttask_track` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_task` int(10) NOT NULL default '0',
  `id_user` varchar(60) NOT NULL default '',
  `id_external` int(10) unsigned NOT NULL default '0',
  `state` tinyint unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `ttt_idx_1` (`id_task`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE, 
  FOREIGN KEY (`id_task`) REFERENCES ttask(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tproject_track` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_project` int(10) NOT NULL default '0',
  `id_user` varchar(60) NOT NULL default '',
  `state` tinyint unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_aditional`  int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `tpt_idx_1` (`id_project`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE,
  FOREIGN KEY (`id_project`) REFERENCES tproject(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkunit` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `duration` float (10,2) unsigned NOT NULL default '0',
  `id_user` varchar(125) DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `have_cost` tinyint unsigned NOT NULL DEFAULT 0,
  `id_profile` int(10) unsigned NOT NULL default '0',
  `locked` varchar(125) DEFAULT '',
  `public` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `work_home` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`),
  KEY `tw_idx_1` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkunit_task` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_task` int(10) NOT NULL default '0',
  `id_workunit` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `twt_idx_1` (`id_task`),
  FOREIGN KEY (`id_workunit`) REFERENCES tworkunit(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkunit_incident` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_incident` int(10) unsigned NOT NULL default '0',
  `id_workunit` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `twi_idx_1` (`id_incident`),
  KEY `twi_idx_2` (`id_workunit`),
  FOREIGN KEY (`id_workunit`) REFERENCES tworkunit(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tagenda` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` datetime NOT NULL default '2000-01-01 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  `public` tinyint unsigned NOT NULL DEFAULT 0,
  `alarm` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT 'N/A',
  `description` mediumtext NOT NULL DEFAULT '',
  PRIMARY KEY  (`id`),
  KEY `ta_idx_1` (`id_user`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_resolution` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_status` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `trole` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(125) NOT NULL default '',
  `description` varchar(255) DEFAULT '',
  `cost` int(8) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `trole_people_task` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_user` varchar(60) NOT NULL default '',
  `id_role` int(10) unsigned NOT NULL default '0',
  `id_task` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE,
  FOREIGN KEY (`id_role`) REFERENCES trole(`id`)
       ON DELETE CASCADE,
  FOREIGN KEY (`id_task`) REFERENCES ttask(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `trole_people_project` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_user` varchar(60) NOT NULL default '',
  `id_role` int(10) unsigned NOT NULL default '0',
  `id_project` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `trp_idx_1` (`id_user`),
  KEY `trp_idx_2` (`id_project`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE,
  FOREIGN KEY (`id_role`) REFERENCES trole(`id`)
       ON DELETE CASCADE,
  FOREIGN KEY (`id_project`) REFERENCES tproject(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Workorder reuses old todo table
CREATE TABLE `ttodo` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` tinytext default NULL,
  `progress` int(11) NOT NULL,
  `assigned_user` varchar(60)  NOT NULL default '',
  `created_by_user` varchar(60)  NOT NULL default '',
  `priority` int(11) NOT NULL,
  `description` mediumtext,
  `last_update` datetime NOT NULL default '2000-01-01 00:00:00',
  `id_task` int(10) default NULL,
  `start_date` datetime NOT NULL default '2000-01-01 00:00:00',
  `end_date` datetime NOT NULL default '2000-01-01 00:00:00',
  `validation_date` datetime NOT NULL default '2000-01-01 00:00:00',
  `need_external_validation` tinyint unsigned NOT NULL DEFAULT 0,
  `id_wo_category` int(10) default NULL,
  `email_notify` tinyint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`),
  KEY `tt_idx_1` (`assigned_user`),
  KEY `tt_idx_2` (`created_by_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Progress on workorders could be:
-- 0 pending (not started, postponed, old)
-- 1 on progress (currently working on)
-- 2 finished (done, for my side)
-- 3 validated (done, for other side)

CREATE TABLE `two_category` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` text default NULL,
  `icon` text default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tmilestone` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_project` int(10)  NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `name` varchar(250) NOT NULL default '',
  `description` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tm_idx_1` (`id_project`),
  FOREIGN KEY (`id_project`) REFERENCES tproject(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table for special days, non working or corporate vacations --

CREATE TABLE `tvacationday` (
  `id` int(6) unsigned NOT NULL auto_increment,
  `day` int(4) unsigned NOT NULL default '0',
  `month` int(4) unsigned NOT NULL default '0',
  `name` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinvoice` (
  `id` int(6) unsigned NOT NULL auto_increment,
  `id_user` varchar(60) default NULL,
  `id_task` int(10) unsigned NULL default NULL,
  `id_company` int(10) unsigned NULL default NULL,  
  `bill_id` tinytext NOT NULL DEFAULT '',
  `concept1` mediumtext DEFAULT NULL,
  `concept2` mediumtext DEFAULT NULL,
  `concept3` mediumtext DEFAULT NULL,
  `concept4` mediumtext DEFAULT NULL,
  `concept5` mediumtext DEFAULT NULL,
  `amount1` float(11,2) NOT NULL DEFAULT 0.0,
  `amount2` float(11,2) NOT NULL DEFAULT 0.0,
  `amount3` float(11,2) NOT NULL DEFAULT 0.0,
  `amount4` float(11,2) NOT NULL DEFAULT 0.0,
  `amount5` float(11,2) NOT NULL DEFAULT 0.0,
  `tax` mediumtext NOT NULL DEFAULT '',
  `irpf` float(11,2) NOT NULL DEFAULT '0.0',
  `concept_irpf` varchar(100) NOT NULL default '',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `description` mediumtext NOT NULL,
  `id_attachment` bigint(20) unsigned NULL default NULL,
  `locked` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_id_user` varchar(60) DEFAULT NULL,
  `invoice_create_date` date NOT NULL DEFAULT '0000-00-00',
  `invoice_payment_date` date DEFAULT NULL,
  `status` enum ('pending', 'paid', 'canceled') default 'pending',
  `invoice_type` enum ('Submitted', 'Received') default 'Submitted',
  `reference` text NOT NULL default '',
  `id_language` varchar(6) NOT NULL default '',
  `internal_note` mediumtext NOT NULL,
  `invoice_expiration_date` date NOT NULL DEFAULT '0000-00-00',
  `bill_id_pattern` tinytext NOT NULL DEFAULT '',
  `bill_id_variable` int(6) unsigned NOT NULL default 0,
  `contract_number` varchar(100) NOT NULL default '',
  `discount_before` float(11,2) NOT NULL DEFAULT '0.0',
  `discount_concept` varchar(100) NOT NULL default '',
  `tax_name` mediumtext NOT NULL default '',
  `rates` float(11,2) NOT NULL DEFAULT 0.0,
  `currency_change` VARCHAR(15) NOT NULL DEFAULT 'None',
  PRIMARY KEY  (`id`),
  KEY `tcost_idx_1` (`id_user`),
  KEY `tcost_idx_2` (`id_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Used to track notificacion (emails) for agenda,
-- incident SLA notifications, system events and more
-- in the future.

CREATE TABLE `tevent` (
  `id` int(6) unsigned NOT NULL auto_increment,
  `type` varchar(250) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  `id_item` int(11) unsigned NULL default NULL,
  `id_item2` int(11) unsigned NULL default NULL,
  `id_item3` varchar(250) default NULL,
  PRIMARY KEY  (`id`),
  KEY `tevent_idx_1` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Product: OS, OS/Windows, OS/Windows/IE
-- Category: Module, Plugin, Article, Howto, Workaround, Download, Patch, etc

CREATE TABLE `tkb_category` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text default NULL,
  `icon` varchar(75) default NULL,
  `parent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tkb_product` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text default NULL,
  `icon` varchar(75) default NULL,
  `parent` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tkb_data` (
  `id` int(6) unsigned NOT NULL auto_increment,
  `title` varchar(250) default NULL,
  `data` mediumtext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_language` varchar(6) NOT NULL default '',
  `id_user` varchar(150) NOT NULL default '',
  `id_product` mediumint(8) unsigned default 0,
  `id_category` mediumint(8) unsigned default 0,
  PRIMARY KEY  (`id`),
  KEY `tkb_idx_1` (`id_product`),
  KEY `tkb_idx_2` (`id_category`),
  KEY `tkb_idx_3` (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tbuilding` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcompany_role` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcompany` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(300) NOT NULL default '',
  `address` varchar(300) NOT NULL default '', 
  `fiscal_id` varchar(250) NULL default NULL,
  `country` tinytext NULL default NULL,
  `website` tinytext NULL default NULL,
  `comments` text NULL default NULL,
  `id_company_role` mediumint(8) unsigned NOT NULL,
  `id_parent` mediumint(8) unsigned default NULL,
  `manager` varchar(150) NOT NULL default '',
  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `payment_conditions` mediumint(8) NOT NULL default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcompany_contact` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_company` mediumint(8) unsigned NOT NULL,
  `fullname` varchar(150) NOT NULL default '',
  `email` varchar(100) NULL default NULL,
  `phone` varchar(55) NULL default NULL,
  `mobile` varchar(55) NULL default NULL,
  `position` varchar(150) NULL default NULL,
  `description` text NULL DEFAULT NULL,
  `disabled` tinyint(1) NULL default 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_contact_reporters` (
  `id_incident` bigint(20) unsigned NOT NULL,
  `id_contact` mediumint(8) unsigned NOT NULL,
  UNIQUE (`id_incident`, `id_contact`),
  FOREIGN KEY (`id_incident`) REFERENCES tincidencia(`id_incidencia`)
      ON DELETE CASCADE,
  FOREIGN KEY (`id_contact`) REFERENCES tcompany_contact(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcontract` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `contract_number` varchar(100) NOT NULL default '',
  `description` text NULL default NULL,
  `date_begin` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `id_company` mediumint(8) unsigned NULL default NULL,
  `id_sla` mediumint(8) unsigned NULL default NULL,
  `id_group` mediumint(8) unsigned NULL default NULL,
  `private` tinyint(2) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(3) NOT NULL DEFAULT 1,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tmanufacturer` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `address` varchar(250) NULL default NULL,
  `comments` varchar(250) NULL default NULL,
  `id_company_role` mediumint(8) unsigned NOT NULL,
  `id_sla` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinventory` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_object_type` mediumint(8) unsigned default NULL,
  `owner` varchar(60),
  `name` TEXT default '',
  `public` TINYINT(1) unsigned DEFAULT 1,
  `description` TEXT default NULL,
  `id_contract` mediumint(8) unsigned default NULL,
  `id_manufacturer` mediumint(8) unsigned default NULL,
  `id_parent` mediumint(8) unsigned default NULL,
  `show_list` TINYINT(1) unsigned DEFAULT 1,
  `last_update` date NOT NULL default '0000-00-00',
  `status` enum ('new', 'inuse', 'unused', 'issued') default 'new',
  `receipt_date` date NOT NULL default '0000-00-00',
  `issue_date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  KEY `tinv_idx_1` (`id_contract`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinventory_relationship` (
   `id_object_src` mediumint(8) unsigned NOT NULL,
   `id_object_dst`  mediumint(8) unsigned NOT NULL,
   KEY `tinvrsx_1` (`id_object_src`),
   KEY `tinvrsx_2` (`id_object_dst`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_inventory` (
  `id_incident` bigint(20) unsigned NOT NULL,
  `id_inventory` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`id_incident`, `id_inventory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ttask_inventory` (
  `id_task` int(10) NOT NULL,
  `id_inventory` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`id_task`, `id_inventory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcustom_search` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL,
  `section` varchar(20) NOT NULL,
  `id_user` varchar(60) NOT NULL,
  `form_values` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE (`id_user`, `name`, `section`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdownload` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(250) NOT NULL default '',
  `location` text NOT NULL, 
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `description` text NOT NULL, 
  `tag` text NOT NULL,
  `id_category` mediumint(8) unsigned NOT NULL default 0,
  `id_user` varchar(60) NOT NULL,
  `public` int(2) unsigned NOT NULL default 0,
  `external_id` text NOT NULL default '', 
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdownload_category` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(250) NOT NULL default '',
  `icon` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdownload_category_group` (
  `id_category` mediumint(8) unsigned NOT NULL,
  `id_group` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (id_category, id_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tdownload_tracking` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_download` mediumint(8) unsigned NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnewsboard` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `title` varchar(250) NOT NULL default '',
  `content` text NOT NULL, 
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_group` int(10) NOT NULL default 0,
  `expire` tinyint(1) DEFAULT 0,
  `expire_timestamp` DATETIME  NOT NULL DEFAULT 0,
  `creator` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE  `tprofile` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `ir` tinyint(1) NOT NULL default '0',
  `iw` tinyint(1) NOT NULL default '0',
  `im` tinyint(1) NOT NULL default '0',
  `um` tinyint(1) NOT NULL default '0',
  `dm` tinyint(1) NOT NULL default '0',
  `fm` tinyint(1) NOT NULL default '0',
  `ar` tinyint(1) NOT NULL default '0',
  `aw` tinyint(1) NOT NULL default '0',
  `am` tinyint(1) NOT NULL default '0',
  `pr` tinyint(1) NOT NULL default '0',
  `pw` tinyint(1) NOT NULL default '0',
  `pm` tinyint(1) NOT NULL default '0',
  `tw` tinyint(1) NOT NULL default '0',
  `tm` tinyint(1) NOT NULL default '0',
  `kr` tinyint(1) NOT NULL default '0',
  `kw` tinyint(1) NOT NULL default '0',
  `km` tinyint(1) NOT NULL default '0',
  `vr` tinyint(1) NOT NULL default '0',
  `vw` tinyint(1) NOT NULL default '0',
  `vm` tinyint(1) NOT NULL default '0',
  `wr` tinyint(1) NOT NULL default '0',
  `ww` tinyint(1) NOT NULL default '0',
  `wm` tinyint(1) NOT NULL default '0',
  `cr` tinyint(1) NOT NULL default '0',
  `cw` tinyint(1) NOT NULL default '0',
  `cm` tinyint(1) NOT NULL default '0',
  `cn` tinyint(1) NOT NULL default '0',
  `frr` tinyint(1) NOT NULL default '0',
  `frw` tinyint(1) NOT NULL default '0',
  `frm` tinyint(1) NOT NULL default '0',
  `si` tinyint(1) NOT NULL default '0',
  `qa` tinyint(1) NOT NULL default '0',
  `rr` tinyint(1) NOT NULL default '0',
  `rw` tinyint(1) NOT NULL default '0',
  `rm` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table structure for table `tusuario_perfil`
--
CREATE TABLE `tusuario_perfil` (
  `id_up` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(60) NOT NULL default '',
  `id_perfil` int(10) unsigned NOT NULL default '0',
  `id_grupo` mediumint(8) unsigned NOT NULL default '0',
  `assigned_by` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_up`),
  FOREIGN KEY (`id_usuario`) REFERENCES tusuario(`id_usuario`)
      ON DELETE CASCADE,
  FOREIGN KEY (`id_grupo`) REFERENCES tgrupo(`id_grupo`)
      ON DELETE CASCADE,
  FOREIGN KEY (`id_perfil`) REFERENCES tprofile(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tpending_mail` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `attempts` int(10) unsigned NOT NULL default 0,
  `status` int(2) unsigned NOT NULL default 0,
  `recipient` text DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `attachment_list` text DEFAULT NULL,
  `from` text DEFAULT NULL,
  `cc` text DEFAULT NULL,
  `extra_headers` text DEFAULT NULL,
  `image_list` text DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tmenu_visibility` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `menu_section` varchar(100) NOT NULL default '',
  `id_group` int(10) unsigned NOT NULL default '0',
  `mode` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcompany_activity` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_company` mediumint(8) unsigned NOT NULL,
  `written_by` varchar(60) NOT NULL default '',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `description` text NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_company`) REFERENCES tcompany(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tuser_report` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_user` varchar(60) NOT NULL default '',
  `name` text default NULL,
  `email` varchar(100) NOT NULL,
  `report_type` mediumint(8) unsigned default 0,
  `interval_days` integer unsigned NOT NULL default 7,
  `lenght` integer unsigned NOT NULL default 7,
  `last_executed` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_external` mediumint(8) unsigned NOT NULL,
  `id_project` int(11) default 0,
  `id_incidents_custom_search` mediumint(8) unsigned default 0,
  `id_leads_custom_search` mediumint(8) unsigned default 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_usuario`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_sla_graph_data` (
  `id_incident` int(10) NOT NULL default '0',
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `value` int(1) unsigned NOT NULL default '0',
    KEY `sla_graph_index1` (`id_incident`),
  KEY `idx_utimestamp_sla_graph` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- status could be 0-ready, 1-sent, 2-error

CREATE TABLE IF NOT EXISTS `tincident_stats` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_incident` bigint(20) unsigned NOT NULL default 0,
	`seconds` bigint(10) unsigned NOT NULL default 0,
	`metric` enum ('user_time', 'status_time', 'group_time', 'total_time', 'total_w_third') NOT NULL,
	`id_user` varchar(60) NOT NULL default '',
	`status` tinyint NOT NULL default 0,
	`id_group` mediumint(8) NOT NULL default 0,
	PRIMARY KEY (`id`),
	KEY `isx1` (`id_incident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_type_field` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `id_incident_type` mediumint(8) unsigned NOT NULL, 
  `label` varchar(100) NOT NULL default '', 
  `type` enum('text', 'textarea', 'combo', 'linked', 'numeric', 'date') DEFAULT 'text',
  `combo_value` LONGTEXT default NULL,
  `show_in_list` tinyint(1) unsigned NOT NULL default 0,
  `global_id` mediumint(8) unsigned,
  `parent` mediumint(8) unsigned default 0,
  `linked_value` LONGTEXT default NULL,
  `order` mediumint(8) unsigned default 0,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tincident_field_data` ( 
  `id` bigint(20) unsigned NOT NULL auto_increment, 
  `id_incident` bigint(20) unsigned NOT NULL,
  `id_incident_field` mediumint(0) unsigned NOT NULL,
  `data` text default NULL,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_type` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `name` varchar(100) NOT NULL default '', 
  `description` text NULL default NULL,
  `icon` text null default null,
  `min_stock` int(5) NOT NULL default 0,
  `show_in_list` tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_type_field` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `id_object_type` mediumint(8) unsigned, 
  `label` varchar(100) NOT NULL default '', 
  `type` enum ('numeric', 'text', 'combo', 'external', 'date') default 'text',
  `combo_value` text default NULL,
  `external_table_name` text default null,
  `external_reference_field` text default null,
  `parent_table_name` text default null,
  `parent_reference_field` text default null,
  `unique` int(1) default 0,
  `inherit` int(1) default 0,
  `show_list` TINYINT(1) unsigned DEFAULT 1,
  `not_allow_updates` TINYINT(1) unsigned DEFAULT 0,
  `external_label` text default null,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_field_data` ( 
  `id` bigint(20) unsigned NOT NULL auto_increment, 
  `id_inventory` bigint(20) unsigned NOT NULL,
  `id_object_type_field` mediumint(8) unsigned NOT NULL,
  `data` text default NULL,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tlead` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_company` mediumint(8) unsigned NOT NULL,
  `id_language` varchar(6) default NULL,
  `id_category` mediumint(8) unsigned default NULL,
  `owner` varchar(60) default NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `email` tinytext  default NULL,
  `phone` tinytext  default NULL,
  `mobile` tinytext  default NULL,
  `position` tinytext  default NULL,
  `company` tinytext  default NULL,
  `country` tinytext  default NULL,
  `description` mediumtext DEFAULT NULL,
  `creation` datetime NOT NULL default '0000-00-00 00:00:00',  
  `modification` datetime NOT NULL default '0000-00-00 00:00:00',  
  `progress` mediumint(5) NULL default 0,
  `estimated_sale` mediumint NULL default 0,
  `id_campaign` int(10) unsigned NOT NULL default 0,
  `executive_overview` tinytext  default NULL,
  `alarm` datetime NOT NULL default '0000-00-00 00:00:00',  
  `estimated_close_date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `id_company_idx` (`id_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_activity` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_lead` mediumint(8) unsigned NOT NULL,
  `written_by` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `creation` datetime NOT NULL default '0000-00-00 00:00:00',  
  PRIMARY KEY  (`id`),
  KEY `id_lead_idx` (`id_lead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_history` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_lead` mediumint(8) unsigned NOT NULL,
  `id_user` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',   
  PRIMARY KEY  (`id`),
  KEY `id_lead_idx` (`id_lead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcrm_template` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `subject` varchar(250) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `id_language` varchar(6) default NULL,
  `id_company` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tholidays` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `day` DATETIME  NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   UNIQUE (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinventory_track` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_inventory` bigint(20) unsigned NOT NULL default '0',
  `id_aditional` varchar(60) NOT NULL DEFAULT '0',
  `state` int(10) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tinventorytask_idx_1` (`id_inventory`),
  FOREIGN KEY (`id_inventory`) REFERENCES tinventory(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ttranslate_string` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`lang` TINYTEXT NOT NULL ,
	`string` TEXT NOT NULL DEFAULT '' ,
	`translation` TEXT NOT NULL DEFAULT '' , 
	PRIMARY KEY (`id`)
);

CREATE TABLE `tinventory_reports` (
  `id` mediumint unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `sql` text,
  `id_group` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*  30/05/2013 */
CREATE TABLE `tkb_product_group` (
  `id_product` mediumint(8) unsigned NOT NULL,
  `id_group` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (id_product, id_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinventory_acl` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_inventory` bigint(20) unsigned NOT NULL,
  `id_reference` varchar(60) NOT NULL default '',
  `type` enum ('user', 'company') default 'user',
  PRIMARY KEY (`id`),
  KEY `id_inventory_idx` (`id_inventory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `twiki_acl` (
  `page` varchar(200) NOT NULL default '',
  `read_page` varchar(200) NOT NULL default '',
  `write_page` varchar(200) NOT NULL default '',
  PRIMARY KEY (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE tsessions_php (
	`id_session` CHAR(52) NOT NULL,
	`last_active` INTEGER NOT NULL,
	`data` TEXT,
	PRIMARY KEY (`id_session`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tupdate_settings` ( 
	`key` varchar(255) default '', 
	`value` varchar(255) default '', PRIMARY KEY (`key`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ttodo_notes` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_todo` int(11) unsigned NOT NULL,
  `written_by` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `creation` datetime NOT NULL default '0000-00-00 00:00:00',  
  PRIMARY KEY  (`id`),
  KEY `id_todo_idx` (`id_todo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcontact_activity` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
  `id_contact` mediumint(8) unsigned NOT NULL, 
  `written_by` mediumtext, 
  `description` mediumtext, 
  `creation` datetime NOT NULL DEFAULT '0000-00-00 00:00:00', 
  PRIMARY KEY (`id`), 
  KEY `id_contact_idx` (`id_contact`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_progress` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcampaign` (
   `id` int(10) unsigned NOT NULL auto_increment,
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `title` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `expenses` mediumint NULL default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ttask_link` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source` int(10) NOT NULL default 0,
  `target` int(10) NOT NULL default 0,
  `type` int(1) NOT NULL default 0,
  PRIMARY KEY  (`id`, `source`, `target`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tuser_field` ( 
	`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
	`label` varchar(100) NOT NULL DEFAULT '', 
	`type` enum('textarea','text','combo') DEFAULT 'text', 
	`combo_value` text, 
	PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tuser_field_data` ( 
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, 
	`id_user` varchar(60) NOT NULL DEFAULT '',
	`id_user_field` mediumint(8) unsigned NOT NULL, 
	`data` text, PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkflow_rule` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` mediumtext DEFAULT NULL,
  `type` int NOT NULL default 0,
  `disabled` int default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkflow_condition` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` mediumtext DEFAULT NULL,
  `id_rule` bigint(20) unsigned NOT NULL,
  `operator` int default 0,
  `time_creation` bigint(20) NOT NULL default 0,
  `time_update` bigint(20) NOT NULL default 0,
  `id_group` int(10) NOT NULL default 0,
  `id_owner` varchar(200)  NOT NULL default '',
  `status` int unsigned NOT NULL default 0,
  `priority` int NOT NULL,
  `sla` int NOT NULL default 0,
  `string_match` varchar(250)  NOT NULL default '',
  `resolution` int NOT NULL default 0,
  `id_task` int(10) NOT NULL default 0,
  `id_ticket_type` int(10) NOT NULL default 0,
  `type_fields` varchar(300)  NOT NULL default '',
  `percent_sla` int unsigned NOT NULL default 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_rule`) REFERENCES tworkflow_rule(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkflow_action` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` mediumtext DEFAULT NULL,
  `id_rule` bigint(20) unsigned NOT NULL,
  `action_type` int NOT NULL default 0,
  `action_data` mediumtext DEFAULT NULL,
  `action_data2` text DEFAULT NULL,
  `action_data3` text DEFAULT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_rule`) REFERENCES tworkflow_rule(`id`)
       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdownload_type` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(250) NOT NULL,
  `description` text,
  `icon` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdownload_type_file` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_type` mediumint(8) unsigned NOT NULL,
  `id_download` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcustom_screen` (
        `id` int(10) NOT NULL auto_increment,
        `name` varchar(255) NOT NULL,
        `content` text NULL,
        `home_enabled` int(1) unsigned default 0,
        `menu_enabled` int(1) unsigned default 0,
        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- ---------------------------------------------------------------------
-- Table tcustom_screen_widget (28/07/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tcustom_screen_widget` (
        `id` int(10) NOT NULL auto_increment,
        `name` varchar(255) NOT NULL,
        `class` varchar(255) NOT NULL,
        `description` mediumtext NOT NULL,
        `icon` mediumtext NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tcustom_screen_data (28/07/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tcustom_screen_data` (
        `id` int(10) NOT NULL auto_increment,
        `id_custom_screen` int(10) NOT NULL,
        `id_widget_type` int(10) NOT NULL,
        `column` int(2) unsigned NOT NULL,
        `row` int(3) unsigned NOT NULL,
        `data` text NULL,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`id_custom_screen`) REFERENCES tcustom_screen(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`id_widget_type`) REFERENCES tcustom_screen_widget(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tattachment_track (05/11/2014)
-- ---------------------------------------------------------------------
CREATE TABLE `tattachment_track` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_attachment` bigint(20) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `id_user` varchar(60) NOT NULL default '',
  `action` enum('download', 'creation', 'modification', 'deletion') NOT NULL,
  `data` text NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tattachment_track (09/09/2015)
-- ---------------------------------------------------------------------
CREATE TABLE `ttag` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `colour` ENUM('blue', 'grey', 'green', 'yellow', 'orange', 'red') DEFAULT 'orange',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tlead_tag (09/09/2015)
-- ---------------------------------------------------------------------
CREATE TABLE `tlead_tag` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `tag_id` bigint(20) unsigned NOT NULL,
  `lead_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tag_id`) REFERENCES ttag(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lead_id`) REFERENCES tlead(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table tagenda_group (15/09/2015)
-- ---------------------------------------------------------------------
CREATE TABLE `tagenda_groups` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `agenda_id` int(10) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`agenda_id`) REFERENCES tagenda(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES tgrupo(`id_grupo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkflow_status_mapping` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `origin_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  `resolution_destination_id` int(10) unsigned NOT NULL,
  `resolution_origin_id` int(10) unsigned NOT NULL,
  `initial` int default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tfolder_report` (
 `id` bigint(20) unsigned NOT NULL auto_increment,
 `nombre`  mediumtext NOT NULL,
 `description`  text DEFAULT '',
 `private` tinyint(1) unsigned NOT NULL DEFAULT 0,
 `id_group` varchar(60) NOT NULL default "1",
 `id_user` varchar(60) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
  `type` tinyint unsigned NOT NULL DEFAULT 0,
  `subtype` tinyint unsigned NOT NULL DEFAULT 0,
  `id_group` varchar(60) NOT NULL default "1",
  `id_folder` bigint(20) unsigned default 0,
  `fields_to_show` longtext NOT NULL default '',
  `order_by` text NOT NULL default '',
  `group_by` text NOT NULL default '',
  `where_clause` text NOT NULL default '',
   PRIMARY KEY  (`id`),
   FOREIGN KEY (`id_folder`) REFERENCES tfolder_report(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport_type` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport_subtype` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `nombre`  mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
-- Table tcompany_field_data (24/05/2016)
-- ---------------------------------------------------------------------

CREATE TABLE `temail_template` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `id_group` int(10) unsigned NOT NULL default '0',
  `template_action` varchar(200) NOT NULL default '',
  `predefined_templates` INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `show_in_list` int(1) not null default 0, 
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
