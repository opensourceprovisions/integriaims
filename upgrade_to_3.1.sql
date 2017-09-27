-- Added 20121206

UPDATE tlanguage SET id_language = 'en_GB' WHERE id_language = 'en';
ALTER TABLE ttask ADD count_hours TINYINT(1) DEFAULT '1';

-- Added 20121210

CREATE TABLE `ttranslate_string` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang` tinytext NOT NULL,
  `string` text NOT NULL,
  `translation` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
