<?php

revision( 1

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CTYPE_EVENT_PREFIX]]content_event`(
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
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);

?>