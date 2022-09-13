<?php

ze\dbAdm::revision(1
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`(
		`id` int(10) unsigned NOT NULL,
		`version` int(10) unsigned NOT NULL,
		`start_date` date NULL,
		`start_time` time NULL,
		`end_date` date NULL,
		`end_time` time NULL,
		`specify_time` tinyint(1) NOT NULL default 0,
		`next_day_finish` tinyint(1) NOT NULL default 0,
		`day_sun_on` tinyint(1) NOT NULL default 0,
		`day_sun_start_time` time NULL,
		`day_sun_end_time` time NULL,
		`day_mon_on` tinyint(1) NOT NULL default 0,
		`day_mon_start_time` time NULL,
		`day_mon_end_time` time NULL,
		`day_tue_on` tinyint(1) NOT NULL default 0,
		`day_tue_start_time` time NULL,
		`day_tue_end_time` time NULL,
		`day_wed_on` tinyint(1) NOT NULL default 0,
		`day_wed_start_time` time NULL,
		`day_wed_end_time` time NULL,
		`day_thu_on` tinyint(1) NOT NULL default 0,
		`day_thu_start_time` time NULL,
		`day_thu_end_time` time NULL,
		`day_fri_on` tinyint(1) NOT NULL default 0,
		`day_fri_start_time` time NULL,
		`day_fri_end_time` time NULL,
		`day_sat_on` tinyint(1) NOT NULL default 0,
		`day_sat_start_time` time NULL,
		`day_sat_end_time` time NULL,
		`location_id` int(10) unsigned NULL,
		`url` varchar(250) NULL,
		`stop_dates` text,
		UNIQUE KEY  (`id`,`version`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(38
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	ADD COLUMN `url_is_private` tinyint(1) NOT NULL default 0 AFTER `url`
_sql

); ze\dbAdm::revision(39
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	DROP COLUMN `url_is_private`,
	ADD COLUMN `private_url` varchar(250) NULL
_sql

); ze\dbAdm::revision(40
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	DROP COLUMN `private_url`
_sql

); ze\dbAdm::revision(42
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	ADD COLUMN `location` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL AFTER `location_id`
_sql

); ze\dbAdm::revision(45
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	MODIFY COLUMN `url` text
_sql

); ze\dbAdm::revision(49
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
	ADD COLUMN `online` tinyint(1) NOT NULL default 0
_sql

);