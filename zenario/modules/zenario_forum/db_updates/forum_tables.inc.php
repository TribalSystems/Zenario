<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

//Create a table to store extra information on forums
//This mirrors a content id and a content type (usually html, but this is not a restriction) from the CMS
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`date_updated` datetime NOT NULL,
		`updater_id` int(10) unsigned NULL,
		`thread_count` int(10) unsigned NOT NULL default 0,
		`post_count` int(10) unsigned NOT NULL default 0,
		`locked` tinyint(1) NOT NULL default 0,
		PRIMARY KEY  (`id`),
		INDEX (`content_id`, `content_type`),
		INDEX (`date_updated`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Create a table to store information on threads in each forum
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]threads`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]threads`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`forum_id` int(10) unsigned NOT NULL,
		`date_posted` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`last_updated_order` int(10) unsigned NOT NULL default 0,
		`employee_post` tinyint(1) NOT NULL default 0,
		`poster_id` int(10) unsigned NOT NULL,
		`updater_id` int(10) unsigned NULL,
		`view_count` int(10) unsigned NOT NULL default 0,
		`post_count` int(10) unsigned NOT NULL default 1,
		`title` varchar(255) NOT NULL,
		`shadow` tinyint(1) NOT NULL default 0,
		`sticky` tinyint(1) NOT NULL default 0,
		`locked` tinyint(1) NOT NULL default 0,
		`rating` int(10) signed NOT NULL default 0,
		PRIMARY KEY  (`id`),
		INDEX (`forum_id`),
		INDEX (`date_posted`),
		INDEX (`date_updated`),
		INDEX (`employee_post`),
		INDEX (`poster_id`),
		INDEX (`post_count`),
		INDEX (`locked`),
		INDEX (`rating`),
		INDEX `last_updated_order` (`forum_id`, `last_updated_order`),
		FULLTEXT INDEX (`title`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Create a table to store user posts
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`forum_id` int(10) unsigned NOT NULL,
		`thread_id` int(10) unsigned NOT NULL,
		`first_post` tinyint(1) NOT NULL default 0,
		`date_posted` datetime NOT NULL,
		`date_updated` datetime NULL,
		`employee_post` tinyint(1) NOT NULL default 0,
		`poster_id` int(10) unsigned NOT NULL,
		`updater_id` int(10) unsigned NULL,
		`message_text` text NOT NULL,
		`rating` int(10) signed NOT NULL default 0,
		PRIMARY KEY  (`id`),
		INDEX (`forum_id`),
		INDEX (`thread_id`),
		INDEX (`employee_post`),
		INDEX (`rating`),
		FULLTEXT INDEX (`message_text`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Create tables to store which threads a user has/has not read
//To try and cut down on storage space, the previous_visit column on the users table and the 
//date_updated column on the forums/threads tables get the first say.
//However this will not be 100% accurate, so these tables are used to store exceptions.
//There will also be some sort of age-limit on the two user_unread tables to stop them filling up with outdated data
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_unread_threads`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_unread_threads`(
		`forum_id` int(10) unsigned NOT NULL,
		`reader_id` int(10) unsigned NOT NULL,
		`unread_from` int(10) unsigned NOT NULL,
		`unread_to` int(10) unsigned NOT NULL,
		INDEX (`forum_id`, `reader_id`),
		INDEX (`forum_id`, `unread_from`),
		INDEX (`forum_id`, `unread_to`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Drop some tables which were created but not implemented for clarity
);	ze\dbAdm::revision( 20
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_post_ratings`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_tracked_threads`
_sql


//Modify the forums table to add more Content Items fields
);	ze\dbAdm::revision( 21
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD COLUMN `ordinal` int(10) unsigned NOT NULL default 1
	AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	CHANGE COLUMN `content_id` `forum_content_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	CHANGE COLUMN `content_type` `forum_content_type` varchar(20) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD COLUMN `thread_content_id` int(10) unsigned NOT NULL default 0
	AFTER `forum_content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD COLUMN `thread_content_type` varchar(20) NOT NULL default ''
	AFTER `thread_content_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD COLUMN `new_thread_content_id` int(10) unsigned NOT NULL default 0
	AFTER `thread_content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD COLUMN `new_thread_content_type` varchar(20) NOT NULL default ''
	AFTER `new_thread_content_id`
_sql


//Add/modify keys for the forums table
);	ze\dbAdm::revision( 22
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	DROP INDEX `content_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD KEY (`ordinal`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD UNIQUE KEY (`forum_content_id`, `forum_content_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD KEY (`thread_content_id`, `thread_content_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums`
	ADD KEY (`new_thread_content_id`, `new_thread_content_type`)
_sql


//Add/modify keys for the threads table
);	ze\dbAdm::revision( 24
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]threads`
	ADD KEY `title_key` (`title`)
_sql

);	ze\dbAdm::revision( 132
,<<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts_uploads`
_sql
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts_uploads`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`post_id` int(10) unsigned NOT NULL,
		`file_id` int(10) unsigned NOT NULL,
		`caption` varchar(60),
		PRIMARY KEY  (`id`),
		UNIQUE (`post_id`, `file_id`),
		KEY(`file_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);




//Convert all code from bbcode to sanitised HTML
if (ze\dbAdm::needRevision(160)) {
	
	require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/markitup/bbcode2html.inc.php';

	$allowable_tags = '<br><p><pre><blockquote><code><em><strong><span><sup><sub><ul><li><ol><a><img>';
	$allowedStyles = array('padding-left' => true, 'text-decoration' => true);
	
	$result = ze\row::query(ZENARIO_FORUM_PREFIX. 'user_posts', array('id', 'message_text'), array());
	while ($row = ze\sql::fetchAssoc($result)) {
		
		BBCode2Html($row['message_text'], false, true, true, false);
		
		ze\row::update(
			ZENARIO_FORUM_PREFIX. 'user_posts',
			array('message_text' => ze\ring::sanitiseHTML($row['message_text'], $allowable_tags, $allowedStyles)),
			$row['id']);
	}
	unset($row);
	unset($result);
	
	ze\dbAdm::revision(160);
}


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
ze\dbAdm::revision( 170
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums` MODIFY COLUMN `new_thread_content_type` varchar(20) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]forums` MODIFY COLUMN `thread_content_type` varchar(20) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]threads` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]threads` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts` MODIFY COLUMN `message_text` text CHARACTER SET utf8mb4 NOT NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_FORUM_PREFIX]]user_posts_uploads` MODIFY COLUMN `caption` varchar(60) CHARACTER SET utf8mb4 NULL
_sql

); 


