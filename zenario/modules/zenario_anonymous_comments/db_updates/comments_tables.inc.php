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


//Create the tables needed for basic user commenting functionality
ze\dbAdm::revision( 1

//Create a table to store user comments
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`date_posted` datetime NOT NULL,
		`date_updated` datetime NULL,
		`employee_post` tinyint(1) NOT NULL default 0,
		`poster_id` int(10) unsigned NOT NULL,
		`updater_id` int(10) unsigned NULL,
		`message_text` text NOT NULL,
		`rating` int(10) signed NOT NULL default 0,
		PRIMARY KEY  (`id`),
		INDEX (`content_id`, `content_type`),
		INDEX (`poster_id`),
		FULLTEXT KEY (`message_text`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql

//Create a table to store extra information on content items with comments
//This mirrors a content id and a content type (usually html, but this is not a restriction) from the CMS
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items`(
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`date_updated` datetime NULL,
		`updater_id` int(10) unsigned NULL,
		`post_count` int(10) unsigned NOT NULL default 0,
		`locked` tinyint(1) NOT NULL default 0,
		PRIMARY KEY (`content_id`, `content_type`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]]
_sql


//Add a status column to comments
);	ze\dbAdm::revision( 28
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD COLUMN `status` enum('draft','pending','published') NOT NULL default 'published'
	AFTER `date_updated`
_sql


//Add an index on date_posted
);	ze\dbAdm::revision( 36
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD INDEX (`date_posted`)
_sql


//Add poster name and email columns for anonymous comments
);	ze\dbAdm::revision( 126
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD COLUMN `poster_name` varchar(50) NOT NULL default ''
	AFTER `poster_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD COLUMN `poster_email` varchar(100) NOT NULL default ''
	AFTER `poster_name`
_sql


//Add an ip address and session id column
);	ze\dbAdm::revision( 127
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD COLUMN `poster_ip` varchar(80) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default ''
	AFTER `poster_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	ADD COLUMN `poster_session_id` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default ''
	AFTER `poster_ip`
_sql

); ze\dbAdm::revision( 139
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items`
	ADD COLUMN send_notification_email tinyint(1), 
	ADD COLUMN notification_email_template varchar(255), 
	ADD COLUMN notification_email_address varchar(255), 
	ADD COLUMN enable_subs tinyint(1), 
	ADD COLUMN comment_subs_email_template varchar(255)
_sql



//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 170
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` SET `comment_subs_email_template` = SUBSTR(`comment_subs_email_template`, 1, 250) WHERE CHAR_LENGTH(`comment_subs_email_template`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` MODIFY COLUMN `comment_subs_email_template` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` SET `notification_email_address` = SUBSTR(`notification_email_address`, 1, 250) WHERE CHAR_LENGTH(`notification_email_address`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` MODIFY COLUMN `notification_email_address` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` SET `notification_email_template` = SUBSTR(`notification_email_template`, 1, 250) WHERE CHAR_LENGTH(`notification_email_template`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]comment_content_items` MODIFY COLUMN `notification_email_template` varchar(250) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments` MODIFY COLUMN `content_type` varchar(20) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments` MODIFY COLUMN `message_text` text CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments` MODIFY COLUMN `poster_email` varchar(100) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments` MODIFY COLUMN `poster_name` varchar(50) CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] NOT NULL default ''
_sql

); ze\dbAdm::revision( 171
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_ANONYMOUS_COMMENTS_PREFIX]]user_comments`
	DROP COLUMN `poster_ip`
_sql

);


