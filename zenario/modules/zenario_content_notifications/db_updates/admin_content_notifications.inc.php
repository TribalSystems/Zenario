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
//Create a table to show extra information on users, such as post counts and signatures
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`(
		`admin_id` int(10) unsigned NOT NULL,
		`draft_notification` tinyint(1) NOT NULL default 0,
		`published_notification` tinyint(1) NOT NULL default 0,
		`menu_node_notification` tinyint(1) NOT NULL default 0,
		PRIMARY KEY  (`admin_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add default values for site settings
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]site_settings`
	(name, default_value)
	VALUES
		('content_notification_email_subject', 'Website content notification: Item {{published_drafted_trashed}}'),
		('content_notification_email_body', 'This is an automated message from the website at {{url}}.

Item {{published_drafted_trashed}}, details:

Content item reference:	{{tag_id}}
Browser title: {{title}}
Who by:        {{admin_name}}
When:          {{datetime_when}}
Link:          {{hyperlink}}'),
		('menu_node_notification_email_subject','Website content notification: Menu node {{created_updated}}'),
		('menu_node_notification_email_body', 'This is an automated message from the website at {{url}}.

Menu node {{created_updated}}, details:

Content item reference:  {{tag_id}}
Browser title:           {{title}}
Who by:                  {{admin_name}}
When:                    {{datetime_when}}
Link:                    {{hyperlink}}

Previous menu node text: {{previous_menu_node}}
New menu node text:      {{new_menu_node}}'),
		('content_request_email_subject','Request to {{publish_draft_trash}} {{tag_id}}'),
		('content_request_email_body', 'The administrator {{admin_name}} on the site {{url}} is requesting 
to {{publish_draft_trash}} the content item {{tag_id}}.
    
Click here to go to that page: {{hyperlink}}.
    
You should log in with your administrator details and {{publish_draft_trash}} the item.')

	ON DUPLICATE KEY UPDATE
		default_value = VALUES(default_value)
_sql


);	ze\dbAdm::revision(2
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
	ADD COLUMN `content_request_notification` tinyint(1) NOT NULL default 0
_sql


//Give the admin_content_notifications table a more sensible name!
);	ze\dbAdm::revision(3
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admins_mirror`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admin_content_notifications`
	RENAME TO `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]admins_mirror`
_sql


//Create a proper linking table
);	ze\dbAdm::revision(4
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror` (
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`content_version` int(10) unsigned NOT NULL,
		`admin_id` int(10) unsigned NOT NULL,
		`action_requested` enum('publish','trash','other') NOT NULL default 'other',
		`datetime_requested` datetime DEFAULT NULL,
		`note` mediumtext,
		PRIMARY KEY (`content_id`,`content_type`,`content_version`,`datetime_requested`),
		KEY (`admin_id`),
		KEY (`action_requested`),
		KEY (`datetime_requested`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(10

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET default_value = 'This is an automated message from the website at {{url}}.

Item {{published_drafted_trashed}}, details:

Content item reference:	{{tag_id}}
Browser title: {{title}}
Who by:        {{admin_name}}
When:          {{datetime_when}}
Link:          {{hyperlink}}

To change your notification set-up, go to your profile at {{admin_profile_url}}'

	WHERE name = 'content_notification_email_body'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET default_value = 'This is an automated message from the website at {{url}}.

Menu node {{created_updated}}, details:

Content item reference:  {{tag_id}}
Browser title:           {{title}}
Who by:                  {{admin_name}}
When:                    {{datetime_when}}
Link:                    {{hyperlink}}

Previous menu node text: {{previous_menu_node}}
New menu node text:      {{new_menu_node}}

To change your notification set-up, go to your profile at {{admin_profile_url}}'

	WHERE name = 'menu_node_notification_email_body'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET default_value = 'The administrator {{admin_name}} on the site {{url}} is requesting 
to {{publish_draft_trash}} the content item {{tag_id}}.
    
Click here to go to that page: {{hyperlink}}.
    
You should log in with your administrator details and {{publish_draft_trash}} the item.

To change your notification set-up, go to your profile at {{admin_profile_url}}'

	WHERE name = 'content_request_email_body'
_sql


//Fix a bad column definition that's causing problems on MySQL 5.7
);	ze\dbAdm::revision(13
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_CONTENT_NOTIFICATIONS_PREFIX]]versions_mirror`
	MODIFY COLUMN `datetime_requested` datetime NOT NULL DEFAULT '1970-01-01 00:00:00'
_sql
);




