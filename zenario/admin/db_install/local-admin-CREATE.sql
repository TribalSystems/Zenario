


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]action_admin_link`;
CREATE TABLE `[[DB_NAME_PREFIX]]action_admin_link` (
  `action_name` varchar(50) NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `action_name` (`action_name`,`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_settings`;
CREATE TABLE `[[DB_NAME_PREFIX]]admin_settings` (
  `admin_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` mediumtext,
  PRIMARY KEY (`admin_id`,`name`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`;
CREATE TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs` (
  `admin_id` int(10) unsigned NOT NULL,
  `checksum` varchar(22) NOT NULL DEFAULT '{}',
  `prefs` mediumtext,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admins`;
CREATE TABLE `[[DB_NAME_PREFIX]]admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authtype` enum('local','super') NOT NULL DEFAULT 'local',
  `global_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `password_salt` varchar(8) DEFAULT NULL,
  `password_needs_changing` tinyint(1) NOT NULL DEFAULT '0',
  `reset_password` varchar(50) DEFAULT NULL,
  `reset_password_salt` varchar(8) DEFAULT NULL,
  `reset_password_time` datetime DEFAULT NULL,
  `status` enum('active','deleted') NOT NULL DEFAULT 'deleted',
  `permissions` enum('all_permissions','specific_actions','specific_languages','specific_menu_areas') NOT NULL DEFAULT 'specific_actions',
  `specific_languages` text,
  `specific_content_items` text,
  `specific_menu_areas` text,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(80) CHARACTER SET ascii NOT NULL DEFAULT '',
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `created_date` datetime DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `image_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_browser` varchar(255) NOT NULL DEFAULT '',
  `last_browser_version` varchar(255) NOT NULL DEFAULT '',
  `last_platform` varchar(255) NOT NULL DEFAULT '',
  `is_client_account` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `authtype` (`authtype`),
  KEY `global_id` (`global_id`),
  KEY `username` (`username`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `email` (`email`),
  KEY `last_login` (`last_login`),
  KEY `last_login_ip` (`last_login_ip`),
  KEY `image_id` (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




REPLACE INTO `[[DB_NAME_PREFIX]]local_revision_numbers` VALUES
 ('admin/db_updates/step_2_update_the_database_schema','admin_tables.inc.php',[[INSTALLER_REVISION_NO]]),
 ('admin/db_updates/step_4_migrate_the_data','admin_tables.inc.php',[[INSTALLER_REVISION_NO]]);
