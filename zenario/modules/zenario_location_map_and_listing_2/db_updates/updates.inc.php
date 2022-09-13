<?php

ze\dbAdm::revision(1

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]areas`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]areas` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) DEFAULT NULL,
		`ne_lat` decimal(21,18) NOT NULL,
		`ne_lng` decimal(21,18) NOT NULL,
		`sw_lat` decimal(21,18) NOT NULL,
		`sw_lng` decimal(21,18) NOT NULL,
		`zoom` int(10) unsigned NOT NULL,
		`polygon_points` text,
		`polygon_colour` varchar(10) DEFAULT '#000000',
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(2

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_map_icons`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_map_icons` (
		`location_id` int(10) unsigned NOT NULL,
		`icon_name` varchar(255) DEFAULT NULL,
		UNIQUE KEY `location_id` (`location_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

);