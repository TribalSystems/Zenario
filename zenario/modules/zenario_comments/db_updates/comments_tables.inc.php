<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]users`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]users`(
		`user_id` int(10) unsigned NOT NULL,
		`latest_activity` datetime NULL,
		`previous_visit` datetime NULL,
		`post_count` int(10) unsigned NOT NULL default 0,
		`karma` int(10) signed NOT NULL default 0,
		`signature` varchar(255) NOT NULL default '',
		`email_upon_pm` tinyint(1) NOT NULL default '0',
		PRIMARY KEY  (`user_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql


//Change the logic for tracking read/unread threads
);	ze\dbAdm::revision( 5
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]users`
	DROP COLUMN `previous_visit`
_sql


//Drop a table which was created but not implemented for clarity
);	ze\dbAdm::revision( 23
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]user_messages`
_sql


//Drop another table which was created but not implemented
);	ze\dbAdm::revision( 24
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]user_comment_ratings`
_sql


//Create a table for user subscriptions
);	ze\dbAdm::revision( 80
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]user_subscriptions`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_COMMENTS_PREFIX]]user_subscriptions`(
		`user_id` int(10) unsigned NOT NULL,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`forum_id` int(10) unsigned NOT NULL default 0,
		`thread_id` int(10) unsigned NOT NULL default 0,
		`date_subscribed` datetime NOT NULL,
		`last_notified` datetime NULL default NULL,
		PRIMARY KEY  (`user_id`, `content_id`, `content_type`, `forum_id`, `thread_id`),
		INDEX (`forum_id`, `thread_id`),
		INDEX (`content_id`, `content_type`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

);
