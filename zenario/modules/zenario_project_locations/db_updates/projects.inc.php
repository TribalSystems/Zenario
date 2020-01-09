<?php
/*
 * Copyright (c) 2020, Tribal Limited
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
ze\dbAdm::revision(3

, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_locations
_sql
	
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_locations` (
	 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	 `name` varchar(255) NOT NULL,
	 `summary` text NOT NULL,
	 `client_name` varchar(255) NOT NULL,
	 `architect_name` varchar(255) NOT NULL,
	 `contractor_name` varchar(255) NOT NULL,
	 `address1` varchar(255) DEFAULT NULL,
	 `address2` varchar(255) DEFAULT NULL,
	 `locality` varchar(255) DEFAULT NULL,
	 `city` varchar(255) DEFAULT NULL,
	 `state` varchar(255) DEFAULT NULL,
	 `postcode` varchar(255) DEFAULT NULL,
	 `country_id` varchar(5) DEFAULT NULL,
	 `region_id` varchar(5) DEFAULT NULL,
	 `latitude` decimal(21,18) DEFAULT NULL,
	 `longitude` decimal(21,18) DEFAULT NULL,
	 `map_zoom` int(11) DEFAULT '0',
	 `map_center_latitude` decimal(21,18) DEFAULT NULL,
	 `map_center_longitude` decimal(21,18) DEFAULT NULL,
	 `image_id` int(10) unsigned NOT NULL default 0,
	 `equiv_id` int(10) unsigned DEFAULT NULL,
	 `content_type` varchar(255) DEFAULT NULL,
	 `last_updated` datetime DEFAULT NULL,
	 `last_updated_admin_id` int(10) unsigned DEFAULT NULL,
	 PRIMARY KEY (`id`),
	 KEY `name` (`name`),
	 KEY (`country_id`),
	 KEY (`region_id`),
	 KEY (`equiv_id`),
	 KEY (`content_type`),
	 KEY (`image_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
	
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_sector_link
_sql
	
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_sector_link` (
	 `project_location_id` int(10) unsigned NOT NULL,
	 `sector_id` int(10) unsigned NOT NULL,
	 PRIMARY KEY `project_location_id` (`project_location_id`,`sector_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
	
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_service_link
_sql
	
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_service_link` (
	 `project_location_id` int(10) unsigned NOT NULL,
	 `service_id` int(10) unsigned NOT NULL,
	 PRIMARY KEY `project_location_id` (`project_location_id`,`service_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
	
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_sectors
_sql
	
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_sectors` (
	 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	 `name` varchar(255) NOT NULL,
	 PRIMARY KEY (`id`),
	 KEY (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
	
, <<<_sql
	DROP TABLE IF EXISTS [[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_services
_sql
	
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_services` (
	 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	 `name` varchar(255) NOT NULL,
	 PRIMARY KEY (`id`),
	 KEY (`name`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql
);ze\dbAdm::revision(25
, <<<_sql
ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_locations`
	ADD alt_tag VARCHAR(255) AFTER image_id;
_sql
);ze\dbAdm::revision(26
, <<<_sql
ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_locations`
	ADD sort INT(10);
_sql
);ze\dbAdm::revision(27
, <<<_sql
ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROJECT_LOCATIONS_PREFIX]]project_location_services`
	ADD sort INT(10);
_sql
);