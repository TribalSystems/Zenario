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


//Create the tables needed for basic user commenting functionality
ze\dbAdm::revision( 1
//Create a table to show extra information on users, such as post counts and signatures
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`(
		`admin_id` int(10) unsigned NOT NULL,
		`draft_notification` tinyint(1) NOT NULL default 0,
		`published_notification` tinyint(1) NOT NULL default 0,
		`menu_node_notification` tinyint(1) NOT NULL default 0,
		PRIMARY KEY  (`admin_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql


); ze\dbAdm::revision(2
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
	ADD COLUMN `content_request_notification` tinyint(1) NOT NULL default 0
_sql

//Give the admin_content_notifications table a more sensible name!
); ze\dbAdm::revision(3
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admins_mirror`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
	RENAME TO `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admins_mirror`
_sql

//Create a proper linking table
); ze\dbAdm::revision(4
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror` (
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`content_version` int(10) unsigned NOT NULL,
		`admin_id` int(10) unsigned NOT NULL,
		`action_requested` enum('publish','trash','other') NOT NULL default 'other',
		`datetime_requested` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
		`note` mediumtext,
		PRIMARY KEY (`content_id`,`content_type`,`content_version`,`datetime_requested`),
		KEY (`admin_id`),
		KEY (`action_requested`),
		KEY (`datetime_requested`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

//Fix a bad column definition that's causing problems on MySQL 5.7
); ze\dbAdm::revision(13
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
	MODIFY COLUMN `datetime_requested` datetime NOT NULL DEFAULT '1970-01-01 00:00:00'
_sql

//Update this table to also record publish notes for content versions
); ze\dbAdm::revision(20
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
	ADD COLUMN `type` enum('request', 'note') NOT NULL default 'note' AFTER `admin_id`,
	CHANGE `datetime_requested` `datetime_created` datetime NOT NULL DEFAULT '1970-01-01 00:00:00'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
	SET type = 'request'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
	DROP COLUMN `action_requested`
_sql

);




