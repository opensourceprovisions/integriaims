CREATE TABLE `ttag` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`name` VARCHAR(255) NOT NULL UNIQUE,
	`colour` ENUM('blue', 'grey', 'green', 'yellow', 'orange', 'red') DEFAULT 'orange',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_tag` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`tag_id` bigint(20) unsigned NOT NULL,
	`lead_id` mediumint(8) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`tag_id`) REFERENCES ttag(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`lead_id`) REFERENCES tlead(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;