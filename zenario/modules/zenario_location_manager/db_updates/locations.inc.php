<?php
/*
 * Copyright (c) 2017, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');
revision(1

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations (
		`id` int(10) unsigned AUTO_INCREMENT,
		`description` varchar(255) NOT NULL,
		`address1` varchar(255) NULL,
		`address2` varchar(255) NULL,
		`locality` varchar(255) NULL,
		`town` varchar(255) NULL,
		`state` varchar(255) NULL,
		`postcode` varchar(255) NULL,
		`country_id` varchar(5) NULL,
		`region_id` int(10) unsigned NULL,
		`phone` varchar(255) NULL,
		`fax` varchar(255) NULL,
		`email` varchar(255) NULL,
		`url` varchar(255) NULL,
		`latitude` decimal(21,18) NULL,
		`longitude` decimal(21,18) NULL,
		`map_zoom` int(11) NULL DEFAULT 15,
		`map_center_latitude` decimal(21,18) NULL DEFAULT 54.041900000000000000,
		`map_center_longitude` decimal(21,18) NULL DEFAULT -1.941900000000000000,
		`content_item_id` int(10) unsigned NULL,
		`content_item_type` varchar(255) NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
); revision(3

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `region_id` int(10) unsigned NULL AFTER country_id
_sql

); revision(4

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors (
		`id` int(10) unsigned AUTO_INCREMENT,
		`parent_id` int(10) unsigned NOT NULL DEFAULT 0,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores (
		`id` int(10) unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`ordinal` tinyint(1) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link (
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`sector_id` int(10) unsigned NOT NULL,
		`score_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`sector_id`,`score_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(6

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores
	DROP COLUMN `ordinal`
_sql

); revision(7

, <<<_sql
	INSERT INTO [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores (`name`)
	VALUES ('Very Poor'),('Poor'),('Average'),('Good'),('Very Good')
_sql

); revision(8

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link
	ADD COLUMN `sticky_flag` tinyint(1) NOT NULL DEFAULT 0
_sql

); revision(9

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	CHANGE `town` `city` varchar(255) NULL
_sql

); revision(10

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` (
	  `location_id` int(10) unsigned NOT NULL default '0',
	  `filename` varchar(50) NOT NULL default '',
	  `mime_type` enum('image/jpeg','image/gif','image/png','image/jpg','image/pjpeg') NOT NULL default 'image/jpeg',
	  `width` smallint(5) unsigned default NULL,
	  `height` smallint(5) unsigned default NULL,
	  `size` int(10) unsigned default NULL,
	  `data` mediumblob,
	  `checksum` varchar(32) NOT NULL default '',
	  `usage` enum('thumbnail','fullsize') default NULL,
	  `sticky_flag` tinyint(1) NOT NULL default '0',
	  `storekeeper_width` smallint(5) unsigned default NULL,
	  `storekeeper_height` smallint(5) unsigned default NULL,
	  `storekeeper_data` mediumblob,
	  `storekeeper_size` int(10) unsigned default NULL,
	  PRIMARY KEY  (`location_id`,`checksum`),
	  KEY `location_id` (`location_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(11

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `summary` text NULL
_sql

); revision(12

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors (
		`id` int(10) unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditor_scores
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditor_scores (
		`id` int(10) unsigned AUTO_INCREMENT,
	 	`accreditor_id` int(10) unsigned NOT NULL,
		`score` int(10) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`accreditor_id`,`score`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_accreditor_score_link
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_accreditor_score_link (
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`accreditor_score_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`accreditor_score_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(17

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	ADD UNIQUE KEY `name` (`name`)
_sql

); revision(20

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	ADD UNIQUE KEY `parentage` (`parent_id`,`id`)
_sql

); revision(23

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `contact_name` varchar(255) NULL AFTER `map_center_longitude`
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `notes` varchar(255) NULL AFTER `contact_name`
_sql


); revision(28

,<<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link
_sql

,<<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link
	(
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`region_id` int(10) unsigned NOT NULL,
		`sticky_flag` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`region_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

,<<<_sql
	INSERT INTO [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link (location_id,region_id,sticky_flag)
	SELECT DISTINCT
		id,
		region_id,
		1
	FROM
		[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	WHERE 
			region_id IS NOT NULL
		AND region_id <> 0
_sql
); revision(29

,<<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	DROP COLUMN `region_id`
_sql

); revision(30

,<<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	DROP KEY name,
	ADD CONSTRAINT UNIQUE name_sibling(`name`,`parent_id`)
_sql

); revision(32

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `status` enum('active','suspended') NOT NULL DEFAULT 'active' AFTER `map_center_longitude`
_sql

); revision(38

, <<<_sql
	INSERT IGNORE INTO [[DB_NAME_PREFIX]]site_settings (`name`,`value`)
	VALUES ('zenario_location_manager__quick_sector_management','1')
_sql

); revision(42

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `parent_id` int(10) AFTER `id`
_sql

);  revision(43

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `parent_id` int(10) AFTER `id`
_sql
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD KEY idxLocationParent(`parent_id`);
_sql

);  revision(44

, <<<_sql
	INSERT IGNORE INTO [[DB_NAME_PREFIX]]site_settings (`name`,`value`)
	SELECT 'zenario_location_manager__sector_management' AS `name`,
		'quick' AS `value`
	FROM [[DB_NAME_PREFIX]]site_settings
	WHERE `name` = 'zenario_location_manager__quick_sector_management'
		AND `value` = '1'
_sql

, <<<_sql
	INSERT IGNORE INTO [[DB_NAME_PREFIX]]site_settings (`name`,`value`)
	SELECT 'zenario_location_manager__sector_management' AS `name`,
		'quick' AS `value`
	FROM [[DB_NAME_PREFIX]]site_settings
	WHERE `name` = 'zenario_location_manager__sector_score_management'
		AND `value` = '1'
_sql

);  revision(47

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `last_accessed` datetime NULL AFTER `url`
_sql

); revision(48

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	MODIFY COLUMN `last_accessed` date NULL
_sql

//Fix a bug where some older installations had the location_images table created in the wrong charactset
); 	revision( 52

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	CONVERT TO CHARACTER SET utf8
_sql

); 	revision( 55

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `last_updated` datetime NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `last_updated_admin_id` datetime NULL
_sql

); 	revision( 56

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `last_updated_admin_id` int(10) unsigned NULL
_sql

); 	revision( 65

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	MODIFY COLUMN `filename` varchar(255) NOT NULL default ''
_sql

); 	revision( 78

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `equiv_id` int(10) unsigned NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `content_type` varchar(255) NULL
_sql

, <<<_sql
	UPDATE [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	INNER JOIN [[DB_NAME_PREFIX]]content_items
	ON [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_item_id = [[DB_NAME_PREFIX]]content_items.id
	AND [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_item_type = [[DB_NAME_PREFIX]]content_items.type
	SET [[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.equiv_id = [[DB_NAME_PREFIX]]content_items.equiv_id,
	[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_type = [[DB_NAME_PREFIX]]content_items.type
_sql

);	revision( 85

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `equiv_id`, 
	DROP COLUMN `content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	CHANGE COLUMN `content_item_id` `equiv_id` int(10) unsigned DEFAULT NULL, 
	CHANGE COLUMN `content_item_type` `content_type` varchar(255) DEFAULT NULL
_sql

, <<<_sql
	UPDATE 
		`[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` l
	INNER JOIN 
		`[[DB_NAME_PREFIX]]content_items` c
	ON
			l.equiv_id = c.id
		AND l.content_type = c.type
	SET
		l.equiv_id = c.equiv_id
_sql

);	revision( 93
,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `external_id` varchar(255) NULL,
	ADD COLUMN `last_updated_via_import` datetime NULL
_sql

);	revision( 94
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`external_id`)
_sql
);	revision( 95
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	CHANGE COLUMN `map_center_latitude` `map_center_latitude` decimal(21,18) NULL,
	CHANGE COLUMN `map_center_longitude` `map_center_longitude` decimal(21,18) NULL,
	CHANGE COLUMN `map_zoom` `map_zoom` int(11) NULL DEFAULT 0
_sql
 
 
 
 
);	revision( 115
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
_sql
 
); revision( 116
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD COLUMN `ordinal` smallint(5) NOT NULL DEFAULT 1
_sql
 
);
revision(117
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`checksum`)
_sql

);
revision(118
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`image_id`)
_sql

);


//Migrate images out of the old bespoke tables and into the central store of images
if (needRevision(119)) {
	
	$sql = "
		SELECT filename, data, checksum
		FROM ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_images where image_id = 0";
	$result = sqlQuery($sql);
	
	while ($row = sqlFetchAssoc($result)) {
		$imageId = Ze\File::addFromString('location', $row['data'], $row['filename'], true);
		if ($imageId)
		{
		$sql2 = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_images
			SET image_id = ". (int) $imageId. "
			WHERE checksum = '". sqlEscape($row['checksum']). "'";
		$result2 = sqlQuery($sql2);
	}
	}
	unset($row);
	
	
revision(119);
	
}


	revision( 120
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP PRIMARY KEY 
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `mime_type`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `width`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `height`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `size`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `data`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_width`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_height`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_data`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_size`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD PRIMARY KEY (`location_id`,`image_id`)
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`ordinal`)
_sql
 
);	revision( 121
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `checksum`
_sql
 
);revision( 129
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `contact_name`
_sql
 
);revision( 130
 
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `notes`
_sql

//Fix a bug where the filename of the files was sometimes not recorded in the location_images table
);	revision( 131
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` AS li
	INNER JOIN `[[DB_NAME_PREFIX]]files` f
	   ON li.image_id = f.id
	SET li.filename = f.filename
	WHERE li.filename IS NULL
	   OR li.filename = ''
_sql
 
); revision( 133
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `hide_pin` tinyint(5) NOT NULL default 0
	AFTER map_center_longitude
_sql
 
// Adding pending status to locations (Workstream T10170)
); revision( 145

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `status` enum('pending','active','suspended') NOT NULL DEFAULT 'active'
_sql

);


