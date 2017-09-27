CREATE TABLE `tworkflow_rule` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` mediumtext DEFAULT NULL,
  `type` int NOT NULL default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tworkflow_condition` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `description` mediumtext DEFAULT NULL,
  `id_rule` bigint(20) unsigned NOT NULL,
  `operator` int default 0,
  `time_creation` bigint(20) NOT NULL default 0,
  `time_update` bigint(20) NOT NULL default 0,
  `id_group` int(10) NOT NULL default '0',
  `id_owner` varchar(200)  NOT NULL default '',
  `status` int unsigned NOT NULL default 0,
  `priority` int NOT NULL,
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
