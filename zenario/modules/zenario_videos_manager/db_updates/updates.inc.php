<?php

ze\dbAdm::revision(2
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`url` varchar(255) NOT NULL,
		`title` varchar(255) NOT NULL,
		`description` text,
		`date` date NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]video_categories`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]category_video_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]video_categories` (
		`video_id` int(10) unsigned NOT NULL,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`video_id`, `name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos_custom_data`
_sql
, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos_custom_data (
		`video_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`video_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

);

ze\dbAdm::revision(4
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL DEFAULT 0 AFTER `url`
_sql

); ze\dbAdm::revision(5
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]categories`
_sql
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]categories` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(250) CHARACTER SET utf8mb4 NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DELETE FROM `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]video_categories`
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]video_categories`
	DROP COLUMN `name`,
	ADD COLUMN `category_id` int(10) unsigned NOT NULL,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`video_id`, `category_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
	MODIFY COLUMN `url` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
	MODIFY COLUMN `title` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
	MODIFY COLUMN `description` text CHARACTER SET utf8mb4
_sql

); ze\dbAdm::revision(6
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
	ADD COLUMN `short_description` text CHARACTER SET utf8mb4 AFTER `title`
_sql

); ze\dbAdm::revision(11
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
	ADD COLUMN `created` datetime DEFAULT NULL,
	ADD COLUMN `created_admin_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `created_user_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `created_username` varchar(255) DEFAULT NULL,
	ADD COLUMN `last_edited` datetime DEFAULT NULL,
	ADD COLUMN `last_edited_admin_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `last_edited_user_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `last_edited_username` varchar(255) DEFAULT NULL
_sql

); ze\dbAdm::revision(12
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]videos`
	ADD COLUMN `language_id` varchar(10) DEFAULT NULL
_sql

//Rename a table to have a less confusing name
); ze\dbAdm::revision(13
, <<<_sql
	RENAME TABLE `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]video_categories`
	TO `[[DB_PREFIX]][[ZENARIO_VIDEOS_MANAGER_PREFIX]]category_video_link`
_sql

);