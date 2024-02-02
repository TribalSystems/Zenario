<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
ze\dbAdm::revision(1

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations (
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
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(4

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors (
		`id` int(10) unsigned AUTO_INCREMENT,
		`parent_id` int(10) unsigned NOT NULL DEFAULT 0,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores (
		`id` int(10) unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`ordinal` tinyint(1) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link (
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`sector_id` int(10) unsigned NOT NULL,
		`score_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`sector_id`,`score_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(6

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores
	DROP COLUMN `ordinal`
_sql

); ze\dbAdm::revision(7

, <<<_sql
	INSERT INTO [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores (`name`)
	VALUES ('Very Poor'),('Poor'),('Average'),('Good'),('Very Good')
_sql

); ze\dbAdm::revision(8

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_sector_score_link
	ADD COLUMN `sticky_flag` tinyint(1) NOT NULL DEFAULT 0
_sql

); ze\dbAdm::revision(9

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	CHANGE `town` `city` varchar(255) NULL
_sql

); ze\dbAdm::revision(10

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` (
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
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(11

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `summary` text NULL
_sql

); ze\dbAdm::revision(12

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors (
		`id` int(10) unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditor_scores
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditor_scores (
		`id` int(10) unsigned AUTO_INCREMENT,
	 	`accreditor_id` int(10) unsigned NOT NULL,
		`score` int(10) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`accreditor_id`,`score`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_accreditor_score_link
_sql

, <<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_accreditor_score_link (
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`accreditor_score_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`accreditor_score_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

); ze\dbAdm::revision(17

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	ADD UNIQUE KEY `name` (`name`)
_sql

); ze\dbAdm::revision(20

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	ADD UNIQUE KEY `parentage` (`parent_id`,`id`)
_sql

); ze\dbAdm::revision(23

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `contact_name` varchar(255) NULL AFTER `map_center_longitude`
_sql

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `notes` varchar(255) NULL AFTER `contact_name`
_sql


); ze\dbAdm::revision(28

,<<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link
_sql

,<<<_sql
	CREATE TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link
	(
		`id` int(10) unsigned AUTO_INCREMENT,
		`location_id` int(10) unsigned NOT NULL,
		`region_id` int(10) unsigned NOT NULL,
		`sticky_flag` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`),
		UNIQUE (`location_id`,`region_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

,<<<_sql
	INSERT INTO [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_region_link (location_id,region_id,sticky_flag)
	SELECT DISTINCT
		id,
		region_id,
		1
	FROM
		[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	WHERE 
			region_id IS NOT NULL
		AND region_id <> 0
	ORDER BY id
_sql
); ze\dbAdm::revision(29

,<<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	DROP COLUMN `region_id`
_sql

); ze\dbAdm::revision(30

,<<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]sectors
	DROP KEY name,
	ADD CONSTRAINT UNIQUE name_sibling(`name`,`parent_id`)
_sql

); ze\dbAdm::revision(32

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `status` enum('active','suspended') NOT NULL DEFAULT 'active' AFTER `map_center_longitude`
_sql

); ze\dbAdm::revision(38

, <<<_sql
	INSERT IGNORE INTO [[DB_PREFIX]]site_settings (`name`,`value`)
	VALUES ('zenario_location_manager__quick_sector_management','1')
_sql

); ze\dbAdm::revision(42

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `parent_id` int(10) AFTER `id`
_sql

);  ze\dbAdm::revision(43

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD KEY idxLocationParent(`parent_id`);
_sql

);  ze\dbAdm::revision(44

, <<<_sql
	INSERT IGNORE INTO [[DB_PREFIX]]site_settings (`name`,`value`)
	SELECT 'zenario_location_manager__sector_management' AS `name`,
		'quick' AS `value`
	FROM [[DB_PREFIX]]site_settings
	WHERE `name` = 'zenario_location_manager__quick_sector_management'
	  AND `value` = '1'
	ORDER BY `name`
_sql

, <<<_sql
	INSERT IGNORE INTO [[DB_PREFIX]]site_settings (`name`,`value`)
	SELECT 'zenario_location_manager__sector_management' AS `name`,
		'quick' AS `value`
	FROM [[DB_PREFIX]]site_settings
	WHERE `name` = 'zenario_location_manager__sector_score_management'
	  AND `value` = '1'
	ORDER BY `name`
_sql

);  ze\dbAdm::revision(47

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	ADD COLUMN `last_accessed` datetime NULL AFTER `url`
_sql

); ze\dbAdm::revision(48

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	MODIFY COLUMN `last_accessed` date NULL
_sql

//Fix a bug where some older installations had the location_images table created in the wrong charactset
); 	ze\dbAdm::revision( 52

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	CONVERT TO CHARACTER SET utf8
_sql

); 	ze\dbAdm::revision( 55

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `last_updated` datetime NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `last_updated_admin_id` datetime NULL
_sql

); 	ze\dbAdm::revision( 56

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `last_updated_admin_id` int(10) unsigned NULL
_sql

); 	ze\dbAdm::revision( 65

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	MODIFY COLUMN `filename` varchar(255) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`description`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`city`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`state`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`postcode`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`status`)
_sql

); 	ze\dbAdm::revision( 78

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `equiv_id` int(10) unsigned NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `content_type` varchar(255) NULL
_sql

, <<<_sql
	UPDATE [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations
	INNER JOIN [[DB_PREFIX]]content_items
	ON [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_item_id = [[DB_PREFIX]]content_items.id
	AND [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_item_type = [[DB_PREFIX]]content_items.type
	SET [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.equiv_id = [[DB_PREFIX]]content_items.equiv_id,
	[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations.content_type = [[DB_PREFIX]]content_items.type
_sql

);	ze\dbAdm::revision( 85

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `equiv_id`, 
	DROP COLUMN `content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	CHANGE COLUMN `content_item_id` `equiv_id` int(10) unsigned DEFAULT NULL, 
	CHANGE COLUMN `content_item_type` `content_type` varchar(255) DEFAULT NULL
_sql

, <<<_sql
	UPDATE 
		`[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` l
	INNER JOIN 
		`[[DB_PREFIX]]content_items` c
	ON
			l.equiv_id = c.id
		AND l.content_type = c.type
	SET
		l.equiv_id = c.equiv_id
_sql

);	ze\dbAdm::revision( 93
,<<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `external_id` varchar(255) NULL,
	ADD COLUMN `last_updated_via_import` datetime NULL
_sql

);	ze\dbAdm::revision( 94
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`external_id`)
_sql
);	ze\dbAdm::revision( 95
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	CHANGE COLUMN `map_center_latitude` `map_center_latitude` decimal(21,18) NULL,
	CHANGE COLUMN `map_center_longitude` `map_center_longitude` decimal(21,18) NULL,
	CHANGE COLUMN `map_zoom` `map_zoom` int(11) NULL DEFAULT 0
_sql
 
 
 
 
);	ze\dbAdm::revision( 115
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
_sql
 
); ze\dbAdm::revision( 116
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD COLUMN `ordinal` smallint(5) NOT NULL DEFAULT 1
_sql
 
);
ze\dbAdm::revision(117
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`checksum`)
_sql

);
ze\dbAdm::revision(118
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`image_id`)
_sql

);


//Migrate images out of the old bespoke tables and into the central store of images
if (ze\dbAdm::needRevision(119)) {
	
	$sql = "
		SELECT filename, data, checksum
		FROM ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_images where image_id = 0";
	$result = ze\sql::select($sql);
	
	while ($row = ze\sql::fetchAssoc($result)) {
		$imageId = ze\file::addFromString('location', $row['data'], $row['filename'], true);
		if ($imageId)
		{
		$sql2 = "
			UPDATE ". DB_PREFIX. ZENARIO_LOCATION_MANAGER_PREFIX. "location_images
			SET image_id = ". (int) $imageId. "
			WHERE checksum = '". ze\escape::asciiInSQL($row['checksum']). "'";
		$result2 = ze\sql::update($sql2);
	}
	}
	unset($row);
	
	
ze\dbAdm::revision(119);
	
}


	ze\dbAdm::revision( 120
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP PRIMARY KEY 
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `mime_type`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `width`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `height`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `size`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `data`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_width`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_height`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_data`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `storekeeper_size`
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD PRIMARY KEY (`location_id`,`image_id`)
_sql
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	ADD KEY (`ordinal`)
_sql
 
);	ze\dbAdm::revision( 121
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images`
	DROP COLUMN `checksum`
_sql
 
);ze\dbAdm::revision( 129
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `contact_name`
_sql
 
);ze\dbAdm::revision( 130
 
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `notes`
_sql

//Fix a bug where the filename of the files was sometimes not recorded in the location_images table
);	ze\dbAdm::revision( 131
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` AS li
	INNER JOIN `[[DB_PREFIX]]files` f
	   ON li.image_id = f.id
	SET li.filename = f.filename
	WHERE li.filename IS NULL
	   OR li.filename = ''
_sql
 
); ze\dbAdm::revision( 133
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `hide_pin` tinyint(5) NOT NULL default 0
	AFTER map_center_longitude
_sql

);

if (ze\dbAdm::needRevision(135)) {
	$dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	
	$columnsToMigrate = [];
	
	$key = [
		'db_column' => 'phone',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'Phone:', 'is_system_field' => 0];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('phone') as count FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE phone IS NOT NULL AND phone <> ''";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'phone';
		} else {
			//otherwise remove from custom dataset fields
			ze\row::delete('custom_dataset_fields', $key);
		}
	}
	
	
	$key = [
		'db_column' => 'fax',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'Fax:', 'is_system_field' => 0];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('fax') as count FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE fax IS NOT NULL AND fax <> ''";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'fax';
		} else {
			//otherwise remove from custom dataset fields
			ze\row::delete('custom_dataset_fields', $key);
		}
	}
	
	$key = [
		'db_column' => 'url',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'url:', 'is_system_field' => 0];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('url') as count FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE url IS NOT NULL AND url <> ''";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'url';
		} else {
			//otherwise remove from custom dataset fields
			ze\row::delete('custom_dataset_fields', $key);
		}
	}
	
	
	$key = [
		'db_column' => 'last_accessed',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'url last accessed:', 'is_system_field' => 0];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('last_accessed') as `count` FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE last_accessed IS NOT NULL AND last_accessed <> DATE('')";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'last_accessed';
		} else {
			//otherwise remove from custom dataset fields
			ze\row::delete('custom_dataset_fields', $key);
		}
	}
	
	
	$key = [
		'db_column' => 'summary',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'Summary:','is_system_field' => 0, 'tab_name' => 'details'];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('summary') as count FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE summary IS NOT NULL AND summary <> ''";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'summary';
		} else {
			//otherwise remove from custom dataset fields
			ze\row::delete('custom_dataset_fields', $key);
		}
	}
	
	
	if ($columnsToMigrate) {
		$columnsToMigrateCommaList = implode(',', $columnsToMigrate);
		
		$sql = "
			INSERT INTO " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data 
			(" . $columnsToMigrateCommaList . ",location_id) 
			SELECT ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= "IFNULL(l." . $column . ", ''), ";
		}
		
		$sql .= "
			id
			FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations as l 
			ORDER BY l.id
			ON DUPLICATE KEY UPDATE ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= $column . " = IFNULL(l." . $column . ", ''),";
		}
		
		$sql = rtrim($sql, ",");
		
		ze\sql::update($sql);
	}
	ze\dbAdm::revision(135);
}

ze\dbAdm::revision( 136
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `phone`,
	DROP COLUMN `fax`,
	DROP COLUMN `url`,
	DROP COLUMN `last_accessed`,
	DROP COLUMN `summary`
_sql

);

if (ze\dbAdm::needRevision(140)) {
	$dataset = ze\dataset::details(ZENARIO_LOCATION_MANAGER_PREFIX. 'locations');
	
	$columnsToMigrate = [];

	$key = [
		'db_column' => 'email',
		'dataset_id' => $dataset['id']];
	$cols = ['label' => 'Email:', 'is_system_field' => 0];
	
	if (ze\row::exists('custom_dataset_fields', $key)) {
		$sql = "SELECT COUNT('email') as count FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations 
			WHERE email IS NOT NULL AND email <> ''";
		if (ze\sql::fetchValue($sql) > 0) {
			//change to custom field
			$fieldId = ze\row::set('custom_dataset_fields', $cols, $key);
			ze\datasetAdm::createFieldInDB($fieldId);
			$columnsToMigrate[] = 'email';
		}
	}
	
	if ($columnsToMigrate) {
		$columnsToMigrateCommaList = implode(',', $columnsToMigrate);
		
		$sql = "
			INSERT INTO " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations_custom_data 
			(" . $columnsToMigrateCommaList . ",location_id) 
			SELECT ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= "IFNULL(l." . $column . ", ''), ";
		}
		
		$sql .= "
			id
			FROM " . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX. "locations as l 
			ORDER BY l.id
			ON DUPLICATE KEY UPDATE ";
		
		foreach ($columnsToMigrate as $column) {
			$sql .= $column . " = IFNULL(l." . $column . ", ''),";
		}
		
		$sql = rtrim($sql, ",");
		
		ze\sql::update($sql);
	}
	ze\dbAdm::revision(140);

}

ze\dbAdm::revision( 141
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	DROP COLUMN `email`
_sql

// Adding pending status to locations (Workstream T10170)
); ze\dbAdm::revision( 145

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `status` enum('pending','active','suspended') NOT NULL DEFAULT 'active'
_sql

//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
); ze\dbAdm::revision( 160
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]accreditors` MODIFY COLUMN `name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `address1` = SUBSTR(`address1`, 1, 250) WHERE CHAR_LENGTH(`address1`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `address1` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `address2` = SUBSTR(`address2`, 1, 250) WHERE CHAR_LENGTH(`address2`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `address2` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `city` = SUBSTR(`city`, 1, 250) WHERE CHAR_LENGTH(`city`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `city` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `content_type` = SUBSTR(`content_type`, 1, 250) WHERE CHAR_LENGTH(`content_type`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `content_type` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `country_id` varchar(5) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `description` = SUBSTR(`description`, 1, 250) WHERE CHAR_LENGTH(`description`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `description` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `external_id` = SUBSTR(`external_id`, 1, 250) WHERE CHAR_LENGTH(`external_id`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `external_id` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `locality` = SUBSTR(`locality`, 1, 250) WHERE CHAR_LENGTH(`locality`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `locality` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `postcode` = SUBSTR(`postcode`, 1, 250) WHERE CHAR_LENGTH(`postcode`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `postcode` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` SET `state` = SUBSTR(`state`, 1, 250) WHERE CHAR_LENGTH(`state`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations` MODIFY COLUMN `state` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` SET `filename` = SUBSTR(`filename`, 1, 250) WHERE CHAR_LENGTH(`filename`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]location_images` MODIFY COLUMN `filename` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores` SET `name` = SUBSTR(`name`, 1, 250) WHERE CHAR_LENGTH(`name`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]scores` MODIFY COLUMN `name` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql


); ze\dbAdm::revision(166
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD COLUMN `timezone` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default ''
	AFTER `hide_pin`
_sql

); ze\dbAdm::revision(167
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	CHANGE `last_updated` `last_edited` datetime DEFAULT NULL,
	CHANGE `last_updated_admin_id` `last_edited_admin_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `created` datetime DEFAULT NULL AFTER `content_type`,
	ADD COLUMN `created_admin_id` int(10) unsigned DEFAULT NULL AFTER `created`,
	ADD COLUMN `created_user_id` int(10) unsigned DEFAULT NULL AFTER `created_admin_id`,
	ADD COLUMN `created_username` varchar(255) DEFAULT NULL after `created_user_id`,
	ADD COLUMN `last_edited_user_id` int(10) unsigned DEFAULT NULL AFTER `last_edited_admin_id`,
	ADD COLUMN `last_edited_username` varchar(255) DEFAULT NULL after `last_edited_user_id`
_sql


//Add an index on country Id
); ze\dbAdm::revision(170
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	ADD KEY (`country_id`)
_sql

//Remove custom data for locations that had been deleted in the past.
); ze\dbAdm::revision(171
, <<<_sql
	DELETE lcd.*
	FROM [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations_custom_data lcd
	LEFT JOIN [[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations l
		ON l.id = lcd.location_id
	WHERE l.id IS NULL;
_sql

);

//Also delete images for deleted locations.
if (ze\dbAdm::needRevision(172)) {
	
	if (ze\module::inc('zenario_location_manager')) {
		$sql = '
			SELECT location_id, image_id
			FROM ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'location_images li
			LEFT JOIN ' . DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . 'locations l
				ON l.id = li.location_id
			WHERE l.id IS NULL';
		$result = ze\sql::select($sql);

		while ($row = ze\sql::fetchAssoc($result)) {
			zenario_location_manager::deleteImage($row['location_id'], $row['image_id']);
		}
	}
	
	
	ze\dbAdm::revision(172);
}




//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
ze\dbAdm::revision(180
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `country_id` varchar(5) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_LOCATION_MANAGER_PREFIX]]locations`
	MODIFY COLUMN `content_type` varchar(20) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
_sql

);

if (ze\dbAdm::needRevision(183)) {
	$keySql = "
		ALTER TABLE `" . ze\escape::sql(DB_PREFIX . ZENARIO_LOCATION_MANAGER_PREFIX . "locations") . "`
		ADD FULLTEXT KEY `description_fulltext_key` (`description`)";
	ze\sql::update($keySql);
	
	ze\dbAdm::revision(183);
}