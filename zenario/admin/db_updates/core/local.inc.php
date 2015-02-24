<?php
/*
 * Copyright (c) 2015, Tribal Limited
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







//General Patch File

//Any updates that are not associated with a module
//	(i.e. updates that should apply to every version of the CMS)
//should be placed in here.

//Updates are applied using the revision function, which takes inputs in the following format:
	//The first input is the revision number
		//Revision numbers are used by the program as a way of tracking whether the revision
		//has already been applied.
		//For example, if the database has recorded in it that it's current revision number
		//for zenario/admin/db_updates/local/local.inc.php is revision #2390, then only revisions
		//numbered 2391 or higher will be run.
		//The following rules apply for picking revision numbers
			//Revision numbers need to appear in order
			//When adding a new revision number, it should be greater than the current revision number
				//stored in the zenario/admin/latest_revision_no.inc.php file
			//After adding a new revision number, the number stored in the
				//zenario/admin/latest_revision_no.inc.php file should be updated to reflect this
		//If you use the SVN version number of your latest SVN commit as the revision number all this will
		//work itself out.
	//Inputs after the first input should be SQL statements to run as part of that revision.
		//They will be run as is, however the subsitution string [[DB_NAME_PREFIX]] will be replaced by
		//the correct table name prefix.
		//For the first few SQL statements I have added below, I've gone out of my way to ensure
		//that they will not break if run twice. The reason for this is that they have already appeared
		//in the database change log, and I don't want to harm any database with the change log already applied.
		//From now on we won't have to worry about that, however.




//Add the `[[DB_NAME_PREFIX]]equiv_link` table if it does not exist
	revision( 2380
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]equiv_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]equiv_link` (
		`id` int(20) NOT NULL default 0,
		`shortcut_id` int(20) NOT NULL default 0
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add table `[[DB_NAME_PREFIX]]skins`
);	revision( 2385
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]skins`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]skins`(
		id int(10) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
		family_name varchar(50) NOT NULL,
		name varchar(255) NOT NULL,
		active tinyint(1) DEFAULT 0 NOT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add Birth Date column to users
);	revision( 2387
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD `birth_date` DATE NULL DEFAULT NULL
	AFTER `last_name`
_sql


//Add update_password_along_with_other_details option
);	revision( 2388
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]enet_user_val`
	ADD `update_password_along_with_other_details` ENUM( 'Yes', 'No' ) NOT NULL DEFAULT 'No'
	AFTER `auto_create_password`
_sql


//Added support for uploading jpegs
);	revision( 2403
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = 'jpg'
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = 'jpeg'
_sql


//Added support for uploading EPS and Illustrator files
);	revision( 2422
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = 'ai'
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = 'eps'
_sql


//Added support for some more document type
);	revision( 2465,
<<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` (`type`)
	VALUES ('png'), ('tif'), ('mp4'), ('mpeg')
_sql


//Add new columns to each of the content tables, to store whether each content item
//should log each access
);	revision( 2526
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql


//Also add these columns to the temp tables
);	revision( 2533
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	ADD `log_user_access` tinyint(1) NOT NULL DEFAULT 0
	AFTER `private`
_sql


//Replace min_admin_password_length config setting with min_admin_password_strength
);	revision( 2556
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		name = 'min_admin_password_strength',
		value_format = "RADIO('_WEAK','_MEDIUM','_STRONG','_VERY_STRONG')",
		value = IF(value < 6, '_WEAK',
				IF(value < 9, '_MEDIUM',
				IF(value < 12, '_STRONG',
					'_VERY_STRONG')))
	WHERE name = 'min_admin_password_strength'
_sql


//Create accesslog table for user access logging
);	revision( 2564
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_accesslog`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_content_accesslog` (
		`user_id` int(10) unsigned NOT NULL default 0,
		`hit_datetime` datetime default NULL,
		`ip` VARCHAR(40) default NULL,
		`content_id` int(10) unsigned NOT NULL default 0,
		`content_type` varchar(20) NOT NULL default '',
		`content_version` int(10) unsigned NOT NULL default 0,
		KEY user_id (user_id),
		KEY `content_type` (`content_type`),
		KEY `content_id` (`content_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Alter the admin_menu_items table to work off of names rather than numbers
//I'm adding a parent_name column, and putting a unique key on name and parent_name.
//Unique keys must be under 1000 bytes in size; this restriction forces me to reduce the
//size of the name column a little.
);	revision( 2589
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_menuitems`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]admin_menuitems` (
	  `id` int(20) unsigned NOT NULL auto_increment,
	  `parent_name` varchar(127) NOT NULL default '',
	  `name` varchar(127) NOT NULL default '',
	  `url` text NOT NULL,
	  `ordinal` int(20) unsigned NOT NULL default 0,
	  PRIMARY KEY  (`id`),
	  UNIQUE KEY `name` (`name`,`parent_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Added two new values for siteConfig 
);	revision( 2598
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET ordinal = ordinal + 2 WHERE section = 'General' AND ordinal > 6
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]site_settings` SET name = 'company', section = 'General', ordinal = 7, value_format = 'TEXT'
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]site_settings` SET name = 'url', section = 'General', ordinal = 8, value_format = 'TEXT'
_sql


//Add `[[DB_NAME_PREFIX]]template_family`
);	revision( 2623
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_family`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]template_family`(
		family_name varchar(50) NOT NULL,
		skin_id int(10) unsigned NULL DEFAULT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates` ADD COLUMN skin_id int(10) unsigned NULL
_sql


//The template_family table should have a row for each Template Family.
//Generate a list of Template Families that is is missing from the
//Templates table and populate it.
);	revision( 2625
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]template_family`(family_name)
	SELECT DISTINCT family_name
	FROM `[[DB_NAME_PREFIX]]templates`
	WHERE family_name NOT IN (
		SELECT family_name
		FROM `[[DB_NAME_PREFIX]]template_family`
	)

_sql


//Add new columns to each of the content tables, to store whether each content item
//should log each access
);	revision( 2662
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	ADD skin_id int(10) unsigned NULL DEFAULT NULL
	AFTER `template_id`
_sql


//Adding the group_images table incase anyone missed it from the 5.0.9 patch log
);	revision( 2668
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_images`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]group_images`(
		`group_id` int(10) unsigned NOT NULL default 0,
		`filename` varchar(50) NOT NULL default '',
		`mime_type` enum('image/jpeg','image/gif','image/png','image/jpg','image/pjpeg') NOT NULL default 'image/jpeg',
		`width` smallint(5) unsigned default NULL,
		`height` smallint(5) unsigned default NULL,
		`size` int(10) unsigned default NULL,
		`data` mediumblob,
		`checksum` varchar(32) NOT NULL default '',
		`usage` enum('thumbnail','medium','fullsize') default NULL,
		`sticky_flag` tinyint(1) NOT NULL default 0,
		PRIMARY KEY (`group_id`,`checksum`),
		KEY `group_id` (`group_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Added support logging of Diagnostic results
);	revision( 2712
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql


//Added support logging of Diagnostic results
);	revision( 2716

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_nlar_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_extranet_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum_tmp`
	ADD COLUMN `diagnostic_last_run` datetime NULL AFTER `diagnostic_page`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_forum_tmp`
	ADD COLUMN `diagnostic_last_status` tinyint(1) NULL AFTER `diagnostic_page`
_sql


//Move the IP column to the end of the user_content_accesslog table,
//and the user_id to just after the time
);	revision( 2763
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	MODIFY `ip` varchar(40) DEFAULT NULL AFTER `content_version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	MODIFY `user_id` int(10) unsigned NOT NULL default 0 AFTER `hit_datetime`
_sql


//Add a unique key to skins to stop accidental duplicate inserts
);	revision( 2770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD UNIQUE KEY (family_name, name)
_sql


//Move the IP column to the end of the user_content_accesslog table,
//and convert it from being a varchar to being an int
//Also move the user_id to just after the time
);	revision( 2800
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	CHANGE `ip` `ip_temp` varchar(40) DEFAULT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	ADD `ip` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_content_accesslog` SET
	`ip` = INET_ATON(`ip_temp`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	DROP COLUMN `ip_temp`
_sql


//Remove an old table that was there for testing.
);	revision( 2814
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]flagged_items`
_sql


//Ensure that the Template Family table is populated.
//There should be a Template Family entry for each Template Family being used in
//the Templates table
);	revision( 3024
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]template_family`
		(family_name, skin_id)
	SELECT DISTINCT
		family_name, NULL
	FROM `[[DB_NAME_PREFIX]]templates`
	WHERE family_name NOT IN (
		SELECT DISTINCT family_name
		FROM `[[DB_NAME_PREFIX]]template_family`)
_sql


//Add parent_id into the categories table
);	revision( 3035
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD `parent_id` int(10) unsigned NULL default NULL
_sql


//Add parent_id into the users table
);	revision( 3036
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD `parent_id` int(10) unsigned NULL default NULL
_sql


//Move parent_id columns up next to the ids
);	revision( 3069
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	MODIFY `parent_id` int(10) unsigned NULL default NULL AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY `parent_id` int(10) unsigned NULL default NULL AFTER `id`
_sql


//Changed setting names
);	revision( 3106
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'document_description_weighting' WHERE name = 'document_desc_weighting'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'document_keywords_weighting' WHERE name = 'document_keyword_weighting'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'html_description_weighting' WHERE name = 'html_desc_weighting'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'html_keywords_weighting' WHERE name = 'html_keyword_weighting'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'news_description_weighting' WHERE name = 'news_desc_weighting'
_sql

, <<<_sql
UPDATE `[[DB_NAME_PREFIX]]site_settings` SET name = 'news_keywords_weighting' WHERE name = 'news_keyword_weighting'
_sql


//Add avi into the allowed document types
);	revision( 3668
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` (`type`)
	VALUES ('avi');
_sql


//Create tables for modules, instances and slots
);	revision( 3787
//Remove old development versions of the table, for anyone with a dev version of zenario or version 5.0.12b
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_setting_defs`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_global`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_by_plugin`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_by_slot`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_by_item`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_slot`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_item`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_slot_item`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_site`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_slot_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_instances`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_inst_item_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs` (
		`plugin_id` int(10) unsigned NOT NULL,
		`setting_for` ENUM('slot', 'site') NOT NULL,
		`section` varchar(50) NOT NULL,
		`ordinal` int(10) unsigned NOT NULL default 0,
		`name` varchar(50) NOT NULL,
		`type` blob NOT NULL,
		`default_value` blob,
		`require` enum('OR','AND') NOT NULL default 'AND',
		`authtype` enum('both','local','super') NOT NULL default 'both',
		`satisfy_permissions` set(
			'perm_manage','perm_sysadmin','perm_publish','perm_author','perm_editmenu',
			'perm_manageforum','perm_override_author_lock','perm_view_users','perm_edit_users',
			'perm_view_groups','perm_edit_groups','perm_translate'
		) NOT NULL default '',
		PRIMARY KEY  (`plugin_id`, `name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_instances` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(250) NOT NULL,
		`plugin_id` int(10) unsigned NOT NULL,
		UNIQUE  KEY (`name`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_settings` (
		`plugin_id` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL,
		`setting_for` ENUM('slot', 'site') NOT NULL,
		`published` tinyint(1) NOT NULL default 0,
		`name` varchar(50) NOT NULL,
		`value` blob,
		PRIMARY KEY  (`plugin_id`, `instance_id`, `published`, `name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link` (
		`instance_id` int(10) unsigned NOT NULL,
		`family_name` varchar(50) NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY (`family_name`, `slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link` (
		`instance_id` int(10) unsigned NOT NULL,
		`family_name` varchar(50) NOT NULL,
		`template_id` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY (`family_name`, `template_id`, `slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link` (
		`instance_id` int(10) unsigned NOT NULL,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`content_version` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY (`content_id`, `content_type`, `content_version`, `slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Alter images table for storekeeper
);	revision( 3801
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `storekeeper_width` smallint(5) unsigned default NULL
	AFTER `sticky_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `storekeeper_height` smallint(5) unsigned default NULL
	AFTER `storekeeper_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `storekeeper_data` mediumblob
	AFTER `storekeeper_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `storekeeper_size` int(10) unsigned default NULL
	AFTER `storekeeper_data`
_sql


//Make sure that the name column in the plugin table is unique
);	revision( 3910
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD UNIQUE (`name`)
_sql


//Make sure that the menu plugin is running by default
);	revision( 3915
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'menu',
		`status` = '_ENUM_RUNNING'
_sql


//Add an "important" column to the plugin instances table
);	revision( 3918
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `important` tinyint(1) default 0 NOT NULL
_sql


//Make sure that the content plugin is running by default
);	revision( 3948
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'content',
		`status` = '_ENUM_RUNNING'
_sql


//Add archived column to images table
);	revision( 3961
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images` 
	ADD COLUMN `archived` tinyint(1) NOT NULL DEFAULT 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'document';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'event';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_extranet` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'extranet';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_forum` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'forum';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'html';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'news';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_nlar` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'nlar';
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` 
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS c 
	ON content_item_id = c.id 
		AND content_item_version = c.version 
	SET archived = 1 
	WHERE status = 'archived' 
		AND content_item_type = 'picture';
_sql


//Drop table plugin_slot_item_link if it has been created
//(It's another dev table that we forgot to trim in revision #3787)
);	revision( 3970
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_slot_item_link`
_sql


//Make sure that the breadcrumbs plugin is running by default
);	revision( 3979
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'breadcrumbs',
		`status` = '_ENUM_RUNNING'
_sql


//Add a categories column to the modules table
);	revision( 4000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `category` varchar(32) default '_MISC' NOT NULL
	AFTER `name`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`category` = '_CORE'
	WHERE `name` IN ('menu', 'breadcrumbs', 'content')
_sql


//Remove the "important" column in the plugin_instances table...
);	revision( 4001
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP COLUMN `important`
_sql


//...and add "backwards_compatibility_use"
);	revision( 4002
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `backwards_compatibility_use`
		ENUM(
			'none',
			'content_bodytop',
			'content_bodymain',
			'content_bodyfoot',
			'breadcrumbs'
		) default 'none' NOT NULL
_sql


//Add a column to the modules table to determine which modules use instances
);	revision( 4011
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `uses_instances` tinyint(1) default 0 NOT NULL
	AFTER `category`
_sql


//Remove permissions columns from plugin setting definitions
);	revision( 4020
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `require`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `authtype`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `satisfy_permissions`
_sql


//Remove the category column
);	revision( 4030
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `category`
_sql


//Alter the links table, expanding the size of the "block" column to
//match the size of the plugin slotname column
);	revision( 4100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `block` varchar(100) NOT NULL default ''
_sql


//Added new storekeeper columns to links table
);  revision( 4222
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `storekeeper_width` smallint(5) unsigned AFTER `img_checksum`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `storekeeper_height` smallint(5) unsigned AFTER `storekeeper_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `storekeeper_data` mediumblob AFTER `storekeeper_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `storekeeper_data` int(10) unsigned unsigned AFTER `storekeeper_data`
_sql

); revision( 4231
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `img_filename` varchar(5) NOT NULL AFTER `count_outgoing`
_sql


//Also add company_name columns column
); revision( 4241
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `company_name` varchar(255) NOT NULL DEFAULT '' AFTER `version`
_sql


//Convert the plugin names to the new format
); revision( 4242
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET `name` = CONCAT('zenario_', `name`, '_v1')
	WHERE `name` != 'api'
	  AND `name` NOT IN ('howarewe_outlets', 'howarewe_questionnaires')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET `name` = CONCAT(`name`, '_v1')
	WHERE `name` IN ('howarewe_outlets', 'howarewe_questionnaires')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET `path` = CONCAT('zenario/modules/zenario_', SUBSTR(`path`, 9, INSTR(`path`, '/db_updates')-9), '_v1/db_updates')
	WHERE SUBSTR(`path`, 1, 8) = 'zenario/modules/'
	  AND SUBSTR(`path`, -11) = '/db_updates'
	  AND `path` NOT IN ('zenario/modules/howarewe_outlets/db_updates', 'zenario/modules/howarewe_questionnaires/db_updates')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]local_revision_numbers`
	SET `path` = CONCAT('zenario/modules/', SUBSTR(`path`, 9, INSTR(`path`, '/db_updates')-9), '_v1/db_updates')
	WHERE `path` IN ('zenario/modules/howarewe_outlets/db_updates', 'zenario/modules/howarewe_questionnaires/db_updates')
_sql


//Add auto increment id to image table
); revision( 4265
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `id` int(10) unsigned auto_increment NOT NULL UNIQUE FIRST
_sql

//Added new storekeeper columns to links table
);  revision( 4271
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `storekeeper_size` int(10) unsigned AFTER `storekeeper_data`
_sql


//Added storekeeper thumbnail image fields to user and group image tables
);  revision( 4472
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_images` 
	ADD COLUMN `storekeeper_width` smallint(5) unsigned NULL AFTER sticky_flag;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_images` 
	ADD COLUMN `storekeeper_height` smallint(5) unsigned NULL AFTER storekeeper_width;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_images` 
	ADD COLUMN `storekeeper_data` mediumblob NULL AFTER storekeeper_height;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_images` 
	ADD COLUMN `storekeeper_size` int(10) unsigned NULL AFTER storekeeper_data;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_images` 
	ADD COLUMN `storekeeper_width` smallint(5) unsigned NULL AFTER sticky_flag;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_images` 
	ADD COLUMN `storekeeper_height` smallint(5) unsigned NULL AFTER storekeeper_width;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_images` 
	ADD COLUMN `storekeeper_data` mediumblob NULL AFTER storekeeper_height;
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_images` 
	ADD COLUMN `storekeeper_size` int(10) unsigned NULL AFTER storekeeper_data;
_sql


//Add a cache killer function to shortcut images
);  revision( 4500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD COLUMN `img_version` int(10) unsigned NOT NULL default 1 AFTER `img_checksum`
_sql


//Remove all set modules, due to a bug fix and another name change as they will no longer be reachable anyway
);	revision( 4515
, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
_sql

, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
_sql

, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
_sql


//Add a "target type" column to the Menu Nodes table
);	revision( 4560
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `target_loc` enum('int','ext','none') NOT NULL default 'int' AFTER `accesskey`
_sql


//Rename the language_picker_lite plugin to language_picker, unless the language_picker already exists
);	revision( 4580
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		name = 'zenario_language_picker_v1'
	WHERE name IN ('zenario_language_picker_v1', 'zenario_language_picker_lite_v1')
	ORDER BY name DESC
	LIMIT 1
_sql


);	revision( 4607
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies` ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT UNIQUE FIRST
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies` ADD COLUMN `archived` tinyint(1) NOT NULL DEFAULT 0
_sql


//Add plugin theme columns into the database
);	revision( 4646
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances` ADD COLUMN `theme` varchar(50) NOT NULL default '' AFTER `plugin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules` ADD COLUMN `default_theme` varchar(50) NOT NULL default '' AFTER `company_name`
_sql


//Add a "first section" column into the modules table
);	revision( 4660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules` ADD COLUMN `first_section` varchar(50) NOT NULL default '' AFTER `default_theme`
_sql


//Changing the format of the site_settings to the MySQL format used by the dynamic thickbox function
);	revision( 4750
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value_format = 'tinyint(1)',
		`value` = REPLACE(REPLACE(`value`, 'true', 1), 'false', '')
	WHERE value_format = "RADIO('true','false')"
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value_format = REPLACE(REPLACE(value_format, 'TEXT', 'varchar(255)'), 'RADIO', 'enum')
_sql

//Added Primary Key to template_family
);	revision( 4822
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD PRIMARY KEY (family_name)
_sql


//Re-ordered columns in the languages table
);	revision( 4929
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	MODIFY COLUMN `language_local_name` varchar(100) NOT NULL default '' AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	MODIFY COLUMN `default_search_page_alias` varchar(50) NOT NULL default '' AFTER `default_homepage_alias`
_sql


//Try to eliminate any duplicates from the raw_picture_store table
);	revision( 5100
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]temp_5105`
_sql

);	revision( 5101
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_picture_store`
	RENAME TO `[[DB_NAME_PREFIX]]temp_5105`
_sql

);	revision( 5102
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_picture_store`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]raw_picture_store` (
	  `content_id` int(10) unsigned default NULL,
	  `content_version` int(10) unsigned NOT NULL,
	  `data` longblob,
	  UNIQUE KEY (`content_id`,`content_version`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);	revision( 5103
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]raw_picture_store`
		(content_id, content_version, `data`)
	SELECT
		content_id, content_version, `data`
	FROM `[[DB_NAME_PREFIX]]temp_5105`
	GROUP BY content_id, content_version
_sql

);	revision( 5104
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]temp_5105`
_sql


//Update the Menu Nodes table, changing the default target-type to "none" to fix
//a bug with newly created Menu Nodes where the admin does not set the destination
);	revision( 5104
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	MODIFY COLUMN `target_loc` enum('int','ext','none') NOT NULL default 'none'
_sql


//Drop the backwards_compatibility_use column
);	revision( 5145
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP COLUMN `backwards_compatibility_use`
_sql


//Add a column for tracking which version of a plugin is currently running and has data
);	revision( 5175
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `has_data` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`
_sql


//Remove all uninitialised modules, as due to a change they will need to be re-inserted into the
//database to pick up their proper names
);	revision( 5170
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `status` = '_ENUM_INSTALLED'
_sql


//Change around the columns on the plugin_settings and plugin_setting_defs tables
);	revision( 5190

//Remove the primary keys, as the columns we are dropping are inside the keys
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP PRIMARY KEY
_sql

//Remove a primary key, as the columns we are dropping are inside it
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP PRIMARY KEY
_sql

//Remove the "setting_for" column, and remove the plugin_id column from the plugin settings table
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP COLUMN `plugin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP COLUMN `setting_for`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `setting_for`
_sql

//Add a new version of the primary key back
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD PRIMARY KEY (`instance_id`,`published`,`name`)
_sql


//Add the public name and members_are_employees columns to the groups table
);	revision( 5200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD COLUMN `public_name` varchar(255) NOT NULL default ''
	AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD COLUMN `members_are_employees` tinyint(1) NOT NULL default 0
	AFTER `description`
_sql


//Add the public name and members_are_employees columns to the groups table
);	revision( 5201
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_instance_cache`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache` (
		`instance_id` int(10) unsigned NOT NULL,
		`method_name` varchar(255) NOT NULL,
		`last_updated` datetime NOT NULL,
		`cache` text NOT NULL,
		PRIMARY KEY (`instance_id`, `method_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add a "type" column to the content type settings table
);	revision( 5220
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttype_settings`
	ADD COLUMN `type` blob
	AFTER `ordinal`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]contenttype_settings` SET
		`type` = 'int'
	WHERE `name` IN ('standard_width', 'standard_height')
_sql

//While I'm at it, rename the "value_format" column in the site_settings table to be
//in line with the plugin setting defs and the content types table
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	CHANGE COLUMN `value_format` `type` blob
_sql


//Change around the columns on the plugin_settings and plugin_setting_defs tables
);	revision( 5600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `allow_newsletters` tinyint(1) NOT NULL default 1
	AFTER `email_verified`
_sql


//Add a "label" column to the Plugin Settings definition table
);	revision( 5680
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD COLUMN `label` varchar(255) NOT NULL default ''
	AFTER `name`
_sql


//Removed the first_section column from the modules table as it is no longer needed
);	revision( 5685
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `first_section`
_sql


//Remove the "install error" column from the modules table, for any developers that had it
);	revision( 5690
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `install_error`
_sql


//Add a Plugin Id column into the primary key of the visitor phrases table
);	revision( 5692
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `plugin_id` int(20) unsigned NOT NULL default 0
	AFTER `language_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD PRIMARY KEY (`code`, `language_id`, `plugin_id`)
_sql


//Add an "invisible" column to the Menu Nodes table
);	revision( 5828
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `invisible` tinyint(1) NOT NULL default 0
	AFTER `active`
_sql


//Update admin toolbar buttons
);	revision ( 5878
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings` 
	DROP COLUMN `section`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings` 
	DROP COLUMN `ordinal`
_sql


//Forcebly insert the Community Features Plugin into the list of running modules
);	revision( 5941
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_cms_core_v1',
		`class_name` = 'zenario_cms_core',
		`display_name` = 'zenario_cms_core_v1',
		`status` = '_ENUM_RUNNING'
_sql


//Add a Plugin id column to the content-types table
);	revision( 5960
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	ADD COLUMN `plugin_id` int(20) unsigned NOT NULL default 0
_sql


//Clear the content types tables.
//(A revision in the data_conversion script will add them back from the Community Plugin)
);	revision( 5970
, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]contenttypes`
_sql

, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]contenttype_settings`
_sql


//Create content tables for the new content types in Pro Business
);	revision( 5985
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_audio`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_audio` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `filename` varchar(100) default NULL,
	  `mime_type` varchar(50) default NULL,
	  `size` int(10) unsigned default NULL,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` date default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`),
	  KEY `folder_id` (`folder_id`),
	  KEY `publication_date` (`publication_date`),
	  KEY `version` (`version`),
	  FULLTEXT KEY `title` (`title`),
	  FULLTEXT KEY `keywords` (`keywords`),
	  FULLTEXT KEY `description` (`description`),
	  FULLTEXT KEY `content_bodytop` (`content_bodytop`),
	  FULLTEXT KEY `content_bodymain` (`content_bodymain`),
	  FULLTEXT KEY `content_bodyfoot` (`content_bodyfoot`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_video`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_video` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `filename` varchar(100) default NULL,
	  `mime_type` varchar(50) default NULL,
	  `size` int(10) unsigned default NULL,
	  `width` smallint(5) unsigned default NULL,
	  `height` smallint(5) unsigned default NULL,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` date default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`),
	  KEY `folder_id` (`folder_id`),
	  KEY `publication_date` (`publication_date`),
	  KEY `version` (`version`),
	  FULLTEXT KEY `title` (`title`),
	  FULLTEXT KEY `keywords` (`keywords`),
	  FULLTEXT KEY `description` (`description`),
	  FULLTEXT KEY `content_bodytop` (`content_bodytop`),
	  FULLTEXT KEY `content_bodymain` (`content_bodymain`),
	  FULLTEXT KEY `content_bodyfoot` (`content_bodyfoot`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_audio_tmp`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_audio_tmp` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `filename` varchar(100) default NULL,
	  `mime_type` varchar(50) default NULL,
	  `size` int(10) unsigned default NULL,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` date default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add a "none" option to shortcuts
);	revision( 6000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `target_loc`
	enum('int', 'ext', 'none') NOT NULL default 'none'
_sql


//Fix for categories table parent_id field
);	revision( 6038
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	MODIFY COLUMN `parent_id` int(10) unsigned NOT NULL default 0
_sql

//Increase name field length
);	revision( 6051
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	MODIFY COLUMN `name` varchar(50) NOT NULL
_sql


//Add a key to the email column of the users table
);	revision( 6077
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`email`)
_sql


//Remove the "has data" column (functionality is now being handled by the compatibility option in the plugin_dependencies table
);	revision( 6100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `has_data`
_sql


//Add a new key to visitor phrases table, for checking if a Plugin has any phrases
);	revision( 6110
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`plugin_id`,`language_id`)
_sql


//Rename the Plugin "zenario_cms_core_community_v1" to "zenario_cms_core_v1"
//and "zenario_extranet_lite_v1" to "zenario_extranet_v1"
);	revision( 6122
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_cms_core_v1',
		class_name = 'zenario_cms_core'
	WHERE `name` = 'zenario_cms_core_community_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_extranet_v1',
		class_name = 'zenario_extranet'
	WHERE `name` = 'zenario_extranet_lite_v1'
_sql

); revision( 6168
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_video_store`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]raw_video_store` 
	(   
		`content_id` int(10) unsigned default NULL,   
		`content_version` int(10) unsigned NOT NULL,   
		`data` longblob,   
		UNIQUE KEY `content_id` (`content_id`,`content_version`) 
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Fixed a bug where the ip address column in the user_content_accesslog table was not correctly signed!
);	revision( 6229
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	MODIFY `ip` int(10) signed NOT NULL default 0
_sql


//Remove the placeholder for the Presentation content type
);	revision( 6240
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_presentation`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_presentation_tmp`
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]contenttypes`
	WHERE content_type_id = 'presentation'
_sql

); revision( 6268
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_audio_store`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]raw_audio_store` 
	(   
		`content_id` int(10) unsigned default NULL,   
		`content_version` int(10) unsigned NOT NULL,   
		`data` longblob,   
		UNIQUE KEY `content_id` (`content_id`,`content_version`) 
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Drop some 5.0 tables that we no longer need 
);	revision( 6360
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_menuitems`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_menuitem_perms`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_notes`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_page_perms`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]ancil_forum_badwords`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_eventtype1`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_eventtype2`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_extranet_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_forum_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_nlar_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]enet_steps`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]enet_user_field_labels`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]enet_user_val`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]folders`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]param_keys`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]param_values`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_params`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_site`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_slot`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_settings_for_slot_item`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_slot_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_slot_item_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]storekeeper_top_level_items`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_enet_step_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_forumthread_link`
_sql


//Drop some more 5.0 tables that we no longer need 
);	revision( 6370
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_stats_public`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]contentitem_price`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]creditcards`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]currencies`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]invoices`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]keyword_cache`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]known_referrers`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]newsletter_recip`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]newsletter_recip_log`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]newsletter_templates`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]order_items`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]orders`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]searched_keywords`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]transactions`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_event_attendees`
_sql


//Another 5.0 table
);	revision( 6390
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_link_blocks`
_sql


//Add a "detect" option to the languages table
);	revision( 6500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `detect` tinyint(1) NOT NULL default 0
	AFTER `language_local_name`
_sql


//Add a plugin_dependancies table
); revision( 6505
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_dependancies`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_dependencies`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_dependencies` (
		`plugin_id` int(10) unsigned NOT NULL,
		`plugin_name` varchar(255) NOT NULL,
		`plugin_class_name` varchar(255) NOT NULL,
		`dependency_plugin_class_name` varchar(255) NOT NULL,
		`type` enum('dependency', 'inheritance', 'compatibility') NOT NULL,
		UNIQUE KEY (`plugin_id`, `type`, `dependency_plugin_class_name`),
		KEY (`plugin_name`, `type`),
		KEY (`plugin_class_name`, `type`),
		KEY (`dependency_plugin_class_name`, `type`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add a dependency for the CMS on the Community Features
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		plugin_id = 0,
		plugin_name = '',
		plugin_class_name = '',
		dependency_plugin_class_name = 'zenario_cms_core',
		`type` = 'dependency'
_sql


//Add a key to the checksum column of the images and movies table
);	revision( 6520
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD KEY (`checksum`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	ADD KEY (`checksum`)
_sql


//Remove the skin_slot_link table, as this test dev feature is being removed
);	revision( 6525
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]skin_slot_link`
_sql


//Add a "use VLPs from class" column to the modules table...
);	revision( 6550
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `vlp_class` varchar(200) NOT NULL default ''
	AFTER `class_name`
_sql


//...and change the plugin_id on the visitor_phrases table to a plugin_class
);	revision( 6560
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `plugin_class` varchar(200) NOT NULL default ''
	AFTER `language_id`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` AS v SET
		v.plugin_class = (
			SELECT p.class_name
			FROM `[[DB_NAME_PREFIX]]modules` AS p
			WHERE p.id = v.plugin_id
		)
	WHERE v.plugin_id != 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP INDEX `plugin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP COLUMN `plugin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD PRIMARY KEY (`plugin_class`, `language_id`, `code`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD INDEX (`language_id`)
_sql


//Add an index on ordinal for shortcuts
);	revision( 6700
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	ADD INDEX (`ordinal`)
_sql


//Fix a bug where a lot of shortcut ordinals were set to zero
);	revision( 6710
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]links` SET
		`ordinal` = `id`
	WHERE `ordinal` = 0
_sql


//Add a request column to the plugin_instance_cache table
);	revision( 6900

, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	MODIFY COLUMN `method_name` varchar(64) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	ADD COLUMN `request` varchar(255) NOT NULL default '' AFTER method_name
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	ADD PRIMARY KEY (`instance_id`,`method_name`,`request`)
_sql

); revision( 6969
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `img_filename` varchar(50) NOT NULL
_sql


//Fix any bad data that is not valid for MySQL strict mode
);	revision( 7050
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]links` SET
		target_loc = 'none'
	WHERE target_loc NOT IN ('int','ext','none')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menuitems` SET
		redundancy = 'primary'
	WHERE redundancy NOT IN ('primary','secondary')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menuitems` SET
		target_loc = 'none'
	WHERE target_loc NOT IN ('int','ext','none')
_sql


//Convert the draft_exists column of the content_event table from an enum to a tinyint
);	revision( 7051
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `draft_exists` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_event` SET
		draft_exists = draft_exists - 1
_sql


//Convert the hide_private_items column of the links table from an enum to a tinyint
);	revision( 7052
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `hide_private_items` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]links` SET
		hide_private_items = hide_private_items - 1
_sql


//Convert the hide_private_items column of the content_news_tmp and content_event_tmp tables from an enum to a tinyint
);	revision( 7053
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	MODIFY COLUMN `draft_exists` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_news_tmp` SET
		draft_exists = draft_exists - 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `draft_exists` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_event_tmp` SET
		draft_exists = draft_exists - 1
_sql


//Fix problems with the Users table and strict mode
);	revision( 7054
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		status = 'pending'
	WHERE status NOT IN ('pending','active','suspended')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		address_pref = 'no_pref'
	WHERE address_pref NOT IN ('residential','business','no_pref')
	   OR address_pref IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `address_pref` enum('residential','business','no_pref') NOT NULL default 'no_pref'
_sql


//Fix problems with the target_window column on the links table and strict mode
);	revision( 7055
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `target_window` enum('blank','popup','same') NULL default NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]links` SET
		target_window = 'same'
	WHERE target_window NOT IN ('blank','popup')
	   OR target_window IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `target_window` enum('blank','popup','same') NOT NULL default 'same'
_sql


//Add Plugin Id columns to the slot/instance link tables for an efficiency tweak
);	revision( 7068
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	ADD COLUMN `plugin_id` int(10) unsigned NOT NULL default 0 FIRST
_sql


);	revision( 7069
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	ADD COLUMN `plugin_id` int(10) unsigned NOT NULL default 0 FIRST
_sql


);	revision( 7070
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	ADD COLUMN `plugin_id` int(10) unsigned NOT NULL default 0 FIRST
_sql


//Automatically convert the saved data for pagination extensions to the new format
); revision( 7097
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'zenario_cms_core::pagSelectList'
	WHERE name IN ('pagination_style', 'year_selector_style')
	  AND value = 'select_list'
_sql


, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'zenario_cms_core::pagSimpleNumbers'
	WHERE name IN ('pagination_style', 'year_selector_style')
	  AND value = 'simple_list_of_numbers'
_sql


, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'zenario_cms_core::pagSimpleNumbersWithMatches'
	WHERE name IN ('pagination_style', 'year_selector_style')
	  AND value = 'simple_list_of_numbers_with_matches'
_sql


//Create the content_video_tmp table if it has not been already
);	revision( 7120
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_video_tmp`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_video_tmp` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `filename` varchar(100) default NULL,
	  `mime_type` varchar(50) default NULL,
	  `size` int(10) unsigned default NULL,
	  `width` smallint(5) unsigned default NULL,
	  `height` smallint(5) unsigned default NULL,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` date default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`),
	  KEY `folder_id` (`folder_id`),
	  KEY `publication_date` (`publication_date`),
	  KEY `version` (`version`),
	  FULLTEXT KEY `title` (`title`),
	  FULLTEXT KEY `keywords` (`keywords`),
	  FULLTEXT KEY `description` (`description`),
	  FULLTEXT KEY `content_bodytop` (`content_bodytop`),
	  FULLTEXT KEY `content_bodymain` (`content_bodymain`),
	  FULLTEXT KEY `content_bodyfoot` (`content_bodyfoot`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//More fixes for strict mode
);	revision( 7122
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	MODIFY COLUMN `alias` varchar(50) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	MODIFY COLUMN `alias` varchar(50) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	MODIFY COLUMN `filename` varchar(100) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	MODIFY COLUMN `filename` varchar(100) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video_tmp`
	MODIFY COLUMN `content_head` text NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video_tmp`
	MODIFY COLUMN `content_bodytop` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video_tmp`
	MODIFY COLUMN `content_bodymain` mediumtext NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video_tmp`
	MODIFY COLUMN `content_bodyfoot` mediumtext NULL
_sql


//Fix a problem where the keys were not correctly defined for the lang_equiv_content table
);	revision( 7161

//Remove any invalid entries/bad data
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]lang_equiv_content`
	WHERE (content_id, content_type) IN (
		SELECT content_id, content_type
		FROM (
			SELECT content_id, content_type, count(*) AS c
			FROM `[[DB_NAME_PREFIX]]lang_equiv_content`
			GROUP BY content_id, content_type
		) AS x
		WHERE c > 1
	)
_sql

//Change the keys
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]lang_equiv_content`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]lang_equiv_content`
	ADD PRIMARY KEY (`content_type`,`content_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]lang_equiv_content`
	ADD INDEX (`language_id`)
_sql


//Remove most of the columns from the plugin_setting_defs table, as now only the default value should be stored
//Also add a primary key (which was missing!)
);	revision( 7275

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `section`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `ordinal`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `label`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP COLUMN `type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD PRIMARY KEY (`plugin_id`,`name`)
_sql


//Added ALTER TABLE to prevent strict problem with sticky flag
);	revision( 7286

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	MODIFY COLUMN `sticky_flag` tinyint(1) NOT NULL DEFAULT 0
_sql


//Update the format of the menu Plugin settings
);	revision( 7300

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = '_MENU_LEVEL_1'
	WHERE name = 'menu_start_from'
	  AND value = 'Menu Level 1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = '_MENU_LEVEL_2'
	WHERE name = 'menu_start_from'
	  AND value = 'Menu Level 2'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = '_MENU_LEVEL_3'
	WHERE name = 'menu_start_from'
	  AND value = 'Menu Level 3'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = '_MENU_LEVEL_ABOVE'
	WHERE name = 'menu_start_from'
	  AND value = 'Menu Level Above'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = '_MENU_CURRENT_LEVEL'
	WHERE name = 'menu_start_from'
	  AND value = 'Current Menu Level'
_sql


//Ensure that the default editor for admins is fckeditor
);	revision( 7383
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	DROP KEY `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD KEY `name` (`name`,`parent_id`,`language_id`)
_sql


//Remove the plugin_admin_thickbox_tabs table if it exists,
//as we're reading directly from the XML files for this now and don't need a table to store the info in
); revision( 7490
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_admin_thickbox_tabs`
_sql


//Added ALTER TABLE to prevent strict problem with shortcuts
);	revision( 7525
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]links`
	MODIFY COLUMN `img_filename` varchar(50) NOT NULL default ''
_sql


//Fix bad data in the instance link tables caused by a bug
);	revision( 7550
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link` AS l,
		   `[[DB_NAME_PREFIX]]plugin_instances` AS i SET
		l.plugin_id = i.plugin_id
	WHERE l.instance_id = i.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_temp_link` AS l,
		   `[[DB_NAME_PREFIX]]plugin_instances` AS i SET
		l.plugin_id = i.plugin_id
	WHERE l.instance_id = i.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_item_link` AS l,
		   `[[DB_NAME_PREFIX]]plugin_instances` AS i SET
		l.plugin_id = i.plugin_id
	WHERE l.instance_id = i.id
_sql


//Add some indices to the plugin links tables
);	revision( 7610
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	ADD INDEX (`instance_id`, `content_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	ADD INDEX (`instance_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	ADD INDEX (`instance_id`)
_sql


//Added a missing publication_date column to the events table
);	revision( 7620
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD COLUMN `publication_date` date default NULL
	AFTER `new_menu_text`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD COLUMN `publication_date` date default NULL
	AFTER `new_menu_text`
_sql


//Add a plugin_events table
); revision( 7631
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_events`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_events`(
		`event_name` varchar(127) NOT NULL,
		`plugin_id` int(10) unsigned NOT NULL,
		`plugin_name` varchar(255) NOT NULL,
		`plugin_class` varchar(200) NOT NULL,
		`suppresses_plugin_class` varchar(200) NOT NULL default '',
		UNIQUE KEY (`event_name`, `plugin_class`),
		KEY (`suppresses_plugin_class`),
		KEY (`plugin_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Convert the values of the pagination settings to the new function names
);	revision( 7650
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'zenario_cms_core::pagCloseWithNPIfNeeded'
	WHERE value IN ('zenario_cms_core::pagSimpleNumbers', 'zenario_cms_core::pagSimpleNumbersWithMatches')
	  AND name IN ('pagination_style', 'year_selector_style')
_sql


//Added a column onto the content types table to record which column that type uses for ordering by date
);	revision( 7725
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	ADD COLUMN `show_pub_date` tinyint(1) NOT NULL default 0
	AFTER `tag_prefix`
_sql


//Convert the name of the framework for the side-menus
);  revision( 8100
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instances` SET
		theme = 'neutral_side_menu'
	WHERE theme = 'side_menu_without_css'
_sql


//Rename the zenario_publication_date vlp class to zenario_content
);  revision( 8101
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` SET
		plugin_class = 'zenario_publication_date'
	WHERE plugin_class = 'zenario_content'
_sql


//Change the instance_id index on the slot contents table
);  revision( 8102
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	DROP INDEX `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	DROP INDEX `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	ADD INDEX `slot_name` (`instance_id`,`slot_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	ADD INDEX `slot_name` (`instance_id`,`slot_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	ADD INDEX `slot_name` (`instance_id`,`slot_name`)
_sql


//Create a new table for storing theme choices
);  revision( 8103
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link` (
		`skin_id` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		`instance_id` int(10) unsigned NOT NULL,
		`theme` varchar(50) NOT NULL,
		PRIMARY KEY (`skin_id`, `slot_name`, `instance_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Rename "theme" column to "framework" in the plugin_instances table
);  revision( 8104
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	CHANGE COLUMN `theme` `framework` varchar(50) NOT NULL default ''
_sql


//Convert the name of the framework for the side-menus
);  revision( 8130
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instances` SET
		framework = 'menu_with_title'
	WHERE framework IN ('neutral_side_menu', 'side_menu_without_css')
_sql


//Add an index on user_id to the group_user_link table
);	revision( 8585
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_user_link`
	ADD INDEX (`user_id`, `group_id`)
_sql


//Add a default_framework column into the modules table
);	revision( 8640
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules` ADD COLUMN `default_framework` varchar(50) NOT NULL default '' AFTER `company_name`
_sql


//Add open_in_new_window column
);	revision( 8650
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `open_in_new_window` tinyint(1) NOT NULL default 0
	AFTER `target_loc`
_sql


//Add a use_custom_framework to the plugin_instances table
);	revision( 8696
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `use_custom_framework` tinyint(1) NOT NULL default 0
	AFTER `framework`
_sql


//Added blog content type
);	revision( 8731
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_blog`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_blog` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` datetime default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`),
	  KEY `folder_id` (`folder_id`),
	  KEY `date_value` (`publication_date`),
	  KEY `version` (`version`),
	  FULLTEXT KEY `title` (`title`),
	  FULLTEXT KEY `keywords` (`keywords`),
	  FULLTEXT KEY `description` (`description`),
	  FULLTEXT KEY `content_bodytop` (`content_bodytop`),
	  FULLTEXT KEY `content_bodymain` (`content_bodymain`),
	  FULLTEXT KEY `content_bodyfoot` (`content_bodyfoot`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_blog_tmp`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_blog_tmp` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` text,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` datetime default NULL,
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql


//Added user notes field
);	revision( 8732

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `notes` text NULL
	AFTER `first_subscrip_end`
_sql


//Add a new table to store which modules are on which Template
);	revision( 8787

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]visitor_phrases_new`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_slot_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]template_slot_link` (
		`template_id` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY (`template_id`, `slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add a primary key to the visitor phrases table
);	revision( 8888

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]visitor_phrases_new` (
		`id` int(1) NOT NULL auto_increment,
		`code` varchar(100) NOT NULL default '',
		`language_id` varchar(5) NOT NULL default '',
		`plugin_class` varchar(200) NOT NULL default '',
		`local_text` text NOT NULL,
		`protect_flag` tinyint(1) NOT NULL default 0,
		`review_flag` tinyint(1) NOT NULL default 0,
		PRIMARY KEY  (`id`),
		UNIQUE KEY  (`plugin_class`,`language_id`,`code`),
		KEY `language_id` (`language_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]visitor_phrases_new`
		(`code`, `language_id`, `plugin_class`, `local_text`, `protect_flag`, `review_flag`)
	SELECT `code`, `language_id`, `plugin_class`, `local_text`, `protect_flag`, `review_flag`
	FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	ORDER BY `plugin_class`,`language_id`,`code`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]visitor_phrases`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases_new`
	RENAME TO `[[DB_NAME_PREFIX]]visitor_phrases`
_sql


//Add a new table to store which modules are on which Template
);	revision(8975
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]email_templates`
_sql

, <<<_sql
CREATE TABLE `[[DB_NAME_PREFIX]]email_templates`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`template_name` varchar(255) NOT NULL default '',
		`subject` tinytext NOT NULL,
		`email_address_from` varchar(100) NOT NULL default '',
		`email_name_from` varchar(255) NOT NULL default '',
		`body` text,
		`date_created` datetime NOT NULL,
		`created_by_id` int(10) unsigned NOT NULL,
		`created_by_authtype` enum('local','super') NOT NULL,
		`date_modified` datetime NULL,
		`modified_by_id` int(10) unsigned NULL,
		`modified_by_authtype` enum('local','super') NULL,
		`allow_attachments` TINYINT(1) DEFAULT 0, 
		PRIMARY KEY  (`id`),
		UNIQUE INDEX (`template_name`),
		INDEX (`date_modified`)
	) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=utf8
_sql

//Change publication date to datetime field
);	revision( 9013
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	MODIFY COLUMN `publication_date` datetime default NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	MODIFY COLUMN `publication_date` datetime default NULL
_sql


//Add some missing keys to the content tables
);	revision( 9060
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD KEY (`status`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	ADD KEY (`status`)
_sql


//Add another key to the content tables to try and speed up getting Plugin Instance Status
);	revision( 9130
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD KEY (`template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	ADD KEY (`template_id`)
_sql

// Added screen name
);	revision( 9137
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `screen_name` varchar(50) NULL AFTER `username`
_sql


//Rename the Plugin "zenario_temporary_memberships" to "zenario_temporary_memberships_v1"
);	revision( 9150
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_temporary_memberships_v1'
	WHERE `class_name` = 'zenario_temporary_memberships'
_sql


//Drop any table to do with Storekeeper from 5.1
);	revision( 9180
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]storekeeper_plugin_items`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]storekeeper_plugin_toolbar_buttons`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]storekeeper_plugin_objects`
_sql


//Added hash user
);	revision( 9211
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `hash` varchar(255) NULL default NULL
_sql


//Remove the __CHOOSE_A_LANGUAGE__ phrase, which isn't used anymore
);	revision( 9230
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE `code` = '__CHOOSE_A_LANGUAGE__'
_sql


//Change the data-structure used for previewing Plugin Instances
);	revision( 9270
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP COLUMN `published`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP KEY `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `parent_id` int(10) unsigned NOT NULL default 0
	AFTER `plugin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD UNIQUE KEY (`name`, `parent_id`)
_sql


//Rename the masthead modules
);	revision( 9272
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `class_name` IN ('zenario_animation', 'zenario_banner')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_animation_v1',
		`class_name` = 'zenario_animation',
		`vlp_class` = 'zenario_animation'
	WHERE `class_name` IN ('zenario_masthead_pro', 'zenario_movie')
	ORDER BY `class_name` = 'zenario_masthead_pro' DESC
	LIMIT 1
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_banner_v1',
		`class_name` = 'zenario_banner',
		`vlp_class` = 'zenario_banner'
	WHERE `class_name` IN ('zenario_masthead', 'zenario_image')
	ORDER BY `class_name` = 'zenario_masthead' DESC
	LIMIT 1
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `class_name` IN ('zenario_image', 'zenario_movie', 'zenario_masthead', 'zenario_masthead_pro')
_sql


//Rename the Links Plugin to the Summary Links Plugin
);	revision( 9282
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `class_name` = 'zenario_summary_link'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_summary_link_v1',
		`class_name` = 'zenario_summary_link',
		`vlp_class` = 'zenario_content_list'
	WHERE `class_name` = 'zenario_link'
_sql


//Drop the now unused overview tables
);	revision( 9310
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]overview_feeds`
_sql


//Drop the now unused menu_cache and site_diagnostics tables
);	revision( 9311
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_cache`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]site_diagnostics`
_sql


//Drop the now unused advert tables
);	revision( 9312
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]adverts`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]advert_counter`
_sql


//Drop the now unused favourites and flags tables
);	revision( 9313
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]favourite_items`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]flagged_content_items`
_sql


//Drop the now unused statistics tables
);	revision( 9314
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_daily`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_extrefers`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_intrefers`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_hourly`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_raw`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]stats_user_login_hist`
_sql


//Drop the now unused subscription_plans, user_subscriptions and tasks tables
);	revision( 9315
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]subscription_plans`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_subscriptions`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tasks`
_sql


//Set "Wide" Content Instances to have wide editors
);	revision( 9425
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings`
	SELECT id, 'editor_size', '_WIDE'
	FROM `[[DB_NAME_PREFIX]]plugin_instances`
	WHERE `name` LIKE '% Wide%'
	  AND plugin_id = (
	  	SELECT id
		FROM `[[DB_NAME_PREFIX]]modules`
		WHERE `name` = 'zenario_content_v1'
	  )
_sql


);	revision(9495
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristics`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristics`(
		`id` int(10) AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`ordinal` int(10) NOT NULL,
		`boolean_values` tinyint(1),
		UNIQUE(`name`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_characteristic_values`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_characteristic_values`(
		`id` int(10) AUTO_INCREMENT,
		`characteristic_id` int(10) NOT NULL,	
		`ordinal` int(10) NOT NULL,
		`name` varchar(255) NOT NULL,
		UNIQUE(`characteristic_id`,`name`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]characteristic_user_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`(
		`id` int(10) AUTO_INCREMENT,
		`user_id` int(10) NOT NULL,
		`characteristic_id` int(10) NOT NULL,
		`characteristic_value_id` int(10) NOT NULL,
		UNIQUE(`user_id`,`characteristic_value_id`),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql


);	revision( 9532
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	CHANGE COLUMN `characteristic_value_id` `characteristic_value_id` int(10)
_sql


);	revision( 9533
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	ADD CONSTRAINT UNIQUE(`user_id`,`characteristic_id`)
_sql


//Add a second checksum for images and movies
);	revision( 9588
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD COLUMN `checksum_inc_name` varchar(32) NULL default NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	ADD COLUMN `checksum_inc_name` varchar(32) NULL default NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD KEY (`checksum_inc_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	ADD KEY (`checksum_inc_name`)
_sql


//Add the type column back into the plugin_setting_defs table
);	revision( 9590
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD COLUMN `type` varchar(50) NOT NULL default ''
	AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD KEY (`type`, `plugin_id`)
_sql


//Add Maiden Name column to users
);	revision( 9621
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD `maiden_name` varchar(100) NOT NULL default ''
	AFTER `last_name`
_sql


//Add the nest column to the Plugin Settings table
);	revision( 9696
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `nest` smallint(4) unsigned NOT NULL default 0
	AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD PRIMARY KEY (`instance_id`, `name`, `nest`)
_sql


//Change the column definition above
);	revision( 9707
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	MODIFY `nest` int(10) unsigned NOT NULL default 0
_sql


//Add the nest column to the plugin_slot_theme_link table
);	revision( 9708
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD COLUMN `nest` int(10) unsigned NOT NULL default 0
	AFTER `slot_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD PRIMARY KEY (`skin_id`, `slot_name`, `nest`, `instance_id`)
_sql


//Create a new table for managing nested modules
);	revision( 9709
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]nested_plugins`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]nested_plugins` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`instance_id` int(10) unsigned NOT NULL,
		`tab` smallint(4) unsigned NOT NULL default 1,
		`ord` smallint(4) unsigned NOT NULL default 1,
		`plugin_id` int(10) unsigned NOT NULL,
		`framework` varchar(50) NOT NULL default '',
		PRIMARY KEY  (`id`),
		KEY `name` (`instance_id`, `tab`, `ord`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql


//Add support for tabs to nested modules
);	revision( 9754
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP KEY `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP KEY `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	DROP COLUMN `is_tab`
_sql


//Add support for tabs to nested modules
);	revision( 9755
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `is_tab` tinyint(1) NOT NULL DEFAULT 0
	AFTER `framework`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `tab_title` varchar(100) NOT NULL default ''
	AFTER `is_tab`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD KEY (`instance_id`, `is_tab`, `tab`, `ord`)
_sql


//Adding key to parent_id on users table
);	revision( 9817
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`parent_id`)
_sql


//Add support for tabs to nested modules
);	revision( 9825
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `nestable` tinyint(1) NOT NULL DEFAULT 0
	AFTER `uses_instances`
_sql


//Demoted the primary key on the images table to just a key, and add a new primary key that uses checksum not filename
);	revision( 9837
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` SET
		checksum = MD5(data)
	WHERE checksum = ''
	   OR checksum IS NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]images` SET
		checksum_inc_name = MD5(CONCAT(filename, checksum))
	WHERE checksum_inc_name = ''
	   OR checksum_inc_name IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD KEY (`content_item_id`,`content_item_version`,`filename`,`content_item_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]images`
	ADD PRIMARY KEY (`content_item_id`,`content_item_version`,`checksum_inc_name`,`content_item_type`)
_sql


//Add a "plugin_request" column to the Menu Nodes table
);	revision( 9888
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `plugin_request` varchar(32) NOT NULL default ''
	AFTER `content_type`
_sql


//Rename events to signals
);	revision( 9889
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]signals`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_events`
	RENAME TO `[[DB_NAME_PREFIX]]signals`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	CHANGE COLUMN `event_name` `signal_name` varchar(127) NOT NULL
_sql


//Remove content type specific Template Files
);	revision( 9898
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]templates` SET
		filename = substr(filename, 6)
	WHERE family_name = 'zenario-GPL-504'
_sql


//Update how the template_slot_link stores data to reflect the fact that template_id now points to an instance id; not a unique filename
);	revision( 9899
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_slot_link_cpy`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	RENAME TO `[[DB_NAME_PREFIX]]template_slot_link_cpy`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]template_slot_link` (
		`template_family_name` varchar(50) NOT NULL,
		`template_filename` varchar(50) NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY  (`template_family_name`,`template_filename`,`slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]template_slot_link`
		(template_family_name, template_filename, slot_name)
	SELECT DISTINCT
		t.family_name, t.filename, tslc.slot_name
	FROM `[[DB_NAME_PREFIX]]template_slot_link_cpy` AS tslc
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.template_id = tslc.template_id
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]template_slot_link_cpy`
_sql


//Demoted the primary key on the movies table to just a key, and add a new primary key that uses checksum not filename
);	revision( 9930
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]movies` SET
		checksum = MD5(data)
	WHERE checksum = ''
	   OR checksum IS NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]movies` SET
		checksum_inc_name = MD5(CONCAT(filename, checksum))
	WHERE checksum_inc_name = ''
	   OR checksum_inc_name IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	ADD KEY (`content_item_id`,`content_item_version`,`filename`,`content_item_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]movies`
	ADD PRIMARY KEY (`content_item_id`,`content_item_version`,`checksum_inc_name`,`content_item_type`)
_sql


); revision( 10081
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_recurring_event`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_recurring_event` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` mediumtext,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` datetime default NULL,
	  `private_view_recurring_event` tinyint(1) NOT NULL default 0,
	  `private_view_guests` enum('public','extranet','guests','owner') NOT NULL default 'owner',
	  `allow_join_recurring_event` enum('open','request_invitation','private') NOT NULL default 'private',
	  `joining_fee` tinyint(1) NOT NULL default 0,
	  `allow_max_guests` int(10) unsigned default NULL,
	  `confirmation_status` enum('tentative','confirmed','cancelled') default NULL,
	  `declare_full` tinyint(1) NOT NULL default 0,
	  `allow_friends` tinyint(1) default 0,
	  `recurring_event_type1` int(10) unsigned NOT NULL default 0,
	  `recurring_event_type2` int(10) unsigned NOT NULL default 0,
	  `start_date` date default NULL,
	  `start_time` time default NULL,
	  `end_date` date default NULL,
	  `end_time` time default NULL,
	  `specify_time` tinyint(1) NOT NULL default 0,
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
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`),
	  KEY `folder_id` (`folder_id`),
	  KEY `version` (`version`),
	  KEY `start_date` (`start_date`),
	  KEY `start_time` (`start_time`),
	  KEY `language_id` (`language_id`),
	  KEY `status` (`status`),
	  KEY `template_id` (`template_id`),
	  FULLTEXT KEY `title` (`title`),
	  FULLTEXT KEY `keywords` (`keywords`),
	  FULLTEXT KEY `description` (`description`),
	  FULLTEXT KEY `content_bodytop` (`content_bodytop`),
	  FULLTEXT KEY `content_bodymain` (`content_bodymain`),
	  FULLTEXT KEY `content_bodyfoot` (`content_bodyfoot`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_recurring_event_tmp`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_recurring_event_tmp` (
	  `id` int(10) unsigned NOT NULL default 0,
	  `version` int(10) unsigned NOT NULL default '1',
	  `title` varchar(250) NOT NULL default '',
	  `private` tinyint(1) NOT NULL default 0,
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `language_id` varchar(5) NOT NULL default 'en',
	  `status` enum('private_draft','reviewable_draft','published','hidden','archived') default 'private_draft',
	  `draft_exists` tinyint(1) NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `owner_id` int(10) unsigned NOT NULL default 0,
	  `owner_authtype` enum('local','super') default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `creating_author_authtype` enum('local','super') default NULL,
	  `allow_local_coauthor_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_authtype` enum('local','super') default NULL,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `publisher_authtype` enum('local','super') default NULL,
	  `published_datetime` datetime default NULL,
	  `hider_id` int(10) unsigned NOT NULL default 0,
	  `hider_authtype` enum('local','super') default NULL,
	  `hidden_datetime` datetime default NULL,
	  `archiver_id` int(10) unsigned NOT NULL default 0,
	  `archiver_authtype` enum('local','super') default NULL,
	  `archived_datetime` datetime default NULL,
	  `expiry_datetime` datetime default NULL,
	  `expiry_action` enum('delete','hide','alert') default NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `description` mediumtext,
	  `keywords` text,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned default NULL,
	  `content_head` text,
	  `content_bodytop` mediumtext,
	  `content_bodymain` mediumtext,
	  `content_bodyfoot` mediumtext,
	  `spare_varchar1` varchar(250) NOT NULL default '',
	  `spare_varchar2` varchar(250) NOT NULL default '',
	  `new_menu_text` varchar(250) NOT NULL default '',
	  `publication_date` datetime default NULL,
	  `private_view_recurring_event` tinyint(1) NOT NULL default 0,
	  `private_view_guests` enum('public','extranet','guests','owner') NOT NULL default 'owner',
	  `allow_join_recurring_event` enum('open','request_invitation','private') NOT NULL default 'private',
	  `joining_fee` tinyint(1) NOT NULL default 0,
	  `allow_max_guests` int(10) unsigned default NULL,
	  `confirmation_status` enum('tentative','confirmed','cancelled') default NULL,
	  `declare_full` tinyint(1) NOT NULL default 0,
	  `allow_friends` tinyint(1) default 0,
	  `recurring_event_type1` int(10) unsigned NOT NULL default 0,
	  `recurring_event_type2` int(10) unsigned NOT NULL default 0,
	  `start_date` date default NULL,
	  `start_time` time default NULL,
	  `end_date` date default NULL,
	  `end_time` time default NULL,
	  `specify_time` tinyint(1) NOT NULL default 0,
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
	  `seo_sitemap_changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') default NULL,
	  `seo_sitemap_priority` float(3,1) default NULL,
	  `diagnostic_page` tinyint(1) NOT NULL default 0,
	  `diagnostic_last_status` tinyint(1) default NULL,
	  `diagnostic_last_run` datetime default NULL,
	  `enable_rss` tinyint(1) default 0,
	  `microsite_id` int(10) unsigned default NULL,
	  PRIMARY KEY  (`id`,`version`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
_sql


);	revision( 10082
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event`
	ADD COLUMN `location_id` int(10) unsigned
	AFTER `day_sat_end_time`
_sql


//Add a key on screenname
);	revision( 10100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`screen_name`)
_sql


//Add a summary field to the content tables
);	revision( 10101
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event`
	ADD FULLTEXT KEY (`content_summary`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video_tmp`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	ADD COLUMN `content_summary` mediumtext
	AFTER `content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	ADD FULLTEXT KEY (`content_summary`)
_sql


//Convert "data_field" settings
);	revision( 10102
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'none'
	WHERE name = 'data_field'
	  AND value = 'None'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'description'
	WHERE name = 'data_field'
	  AND value = 'Description'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'content_bodytop'
	WHERE name = 'data_field'
	  AND value = 'Content Top'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'content_bodymain'
	WHERE name = 'data_field'
	  AND value = 'Content Main'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'content_bodyfoot'
	WHERE name = 'data_field'
	  AND value = 'Content Foot'
_sql


);	revision( 10204
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event_tmp`
	ADD COLUMN `location_id` int(10) unsigned
	AFTER `day_sat_end_time`
_sql


);	revision( 10228

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event`
	RENAME TO `[[DB_NAME_PREFIX]]content_recurringevent`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurring_event_tmp`
	RENAME TO `[[DB_NAME_PREFIX]]content_recurringevent_tmp`
_sql


);	revision( 10230
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]contenttypes`
	WHERE content_type_id = 'recurring_event'
_sql


//Change lanuage default pages from working off aliases to working off id
//and add language default registration/forgot password/change password/logout pages,
);	revision( 10231
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `homepage_id` int(10) unsigned NOT NULL default 0
	AFTER `detect`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `search_page_id` int(10) unsigned NOT NULL default 0
	AFTER `homepage_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `extranet_login_page_id` int(10) unsigned NOT NULL default 0
	AFTER `search_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `extranet_registration_page_id` int(10) unsigned NOT NULL default 0
	AFTER `extranet_login_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `extranet_password_reminder_page_id` int(10) unsigned NOT NULL default 0
	AFTER `extranet_registration_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `extranet_change_password_page_id` int(10) unsigned NOT NULL default 0
	AFTER `extranet_password_reminder_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `extranet_logout_page_id` int(10) unsigned NOT NULL default 0
	AFTER `extranet_change_password_page_id`
_sql


);	revision( 10232
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages` AS l SET
		homepage_id = IFNULL((
			SELECT c.id
			FROM `[[DB_NAME_PREFIX]]content_html` AS c
			WHERE c.language_id = l.id
			  AND alias = l.default_homepage_alias
			ORDER BY version DESC
			LIMIT 1
		), 0)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages` AS l SET
		search_page_id = IFNULL((
			SELECT c.id
			FROM `[[DB_NAME_PREFIX]]content_html` AS c
			WHERE c.language_id = l.id
			  AND alias = l.default_search_page_alias
			ORDER BY version DESC
			LIMIT 1
		), 0)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages` AS l SET
		extranet_login_page_id = IFNULL((
			SELECT c.id
			FROM `[[DB_NAME_PREFIX]]content_html` AS c
			WHERE c.language_id = l.id
			  AND alias = l.default_extranet_page_alias
			ORDER BY version DESC
			LIMIT 1
		), 0)
_sql


);	revision( 10233
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `default_homepage_alias`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `default_search_page_alias`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `default_extranet_page_alias`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `default_sitemap_alias`
_sql


);	revision( 10395,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	ADD COLUMN `stop_dates` TEXT
_sql


);	revision( 10396,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	DROP COLUMN `stop_dates` 
_sql

,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	ADD COLUMN `stop_dates` TEXT
	AFTER `location_id`
_sql


);	revision( 10587,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent_tmp`
	ADD COLUMN `stop_dates` TEXT
	AFTER `location_id`
_sql


);	revision( 10600,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent_tmp`
	ADD COLUMN `url` varchar(250)
	AFTER `location_id`
_sql


);	revision( 10601,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	ADD COLUMN `url` varchar(250)
	AFTER `location_id`
_sql


//Add support for Feature modules
);	revision( 10660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `feature_plugin` tinyint(1) NOT NULL DEFAULT 0
	AFTER `uses_instances`
_sql


//Forcebly run the Email Template Manager Plugin, as a few modules have developed dependencies
//for this Plugin and will be unstable without it
);	revision( 10725
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_email_template_manager_v1',
		`class_name` = 'zenario_email_template_manager',
		`display_name` = 'Email Template Manager',
		`status` = '_ENUM_RUNNING'
	ON DUPLICATE KEY UPDATE
		`status` = '_ENUM_RUNNING'
_sql


//Create a new table for favicons
);	revision( 10780
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]favicons` (
		`usage` enum('favicon','mobile') NOT NULL,
		`filename` varchar(50) NOT NULL default '',
		`mime_type` enum('image/jpeg','image/gif','image/png','image/jpg','image/pjpeg','image/vnd.microsoft.icon') NOT NULL,
		`width` smallint(5) unsigned default NULL,
		`height` smallint(5) unsigned default NULL,
		`size` int(10) unsigned default NULL,
		`data` mediumblob,
		`checksum` varchar(32) default NULL,
		PRIMARY KEY  (`usage`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Added next day finish field
);	revision( 10781
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	ADD COLUMN `next_day_finish` tinyint(1) NOT NULL DEFAULT 0 AFTER `specify_time`
_sql

);	revision( 10803
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent_tmp`
	ADD COLUMN `next_day_finish` tinyint(1) NOT NULL DEFAULT 0 AFTER `specify_time`
_sql


//Add support for Plugin Album
);	revision( 10899
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `keep_in_album` tinyint(1) NOT NULL DEFAULT 0
	AFTER `uses_instances`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `keep_in_album` tinyint(1) NOT NULL DEFAULT 0
	AFTER `framework`
_sql


//Update some the Plugin Instances for some modules to be in the Album by default
);	revision( 10900
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instances` SET
		`keep_in_album` = 1
	WHERE plugin_id IN (
		SELECT id
		FROM `[[DB_NAME_PREFIX]]modules`
		WHERE `name` IN (
			'zenario_menu_v1',
			'zenario_search_entry_box_predictive_probusiness_v1',
			'zenario_content_v1',
			'zenario_language_picker_v1',
			'zenario_menu_pro_v1',
			'zenario_comments_v1',
			'zenario_google_analytics_tracker',
			'zenario_google_ad_slot',
			'zenario_social_bookmarking_v1',
			'zenario_footer_v1',
			'zenario_language_picker_avls_v1',
			'zenario_footer_pro_v1',
			'zenario_search_entry_box_v1',
			'zenario_menu_forward_back_navigator_v1',
			'zenario_bookmark_email_print_v1',
			'zenario_breadcrumbs_v1',
			'zenario_extranet_v1'
		)
	)
_sql


//Add an index for the keep_in_album column
);	revision( 10901
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD INDEX (`keep_in_album`)
_sql


);	revision( 10978
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	ADD COLUMN `protected` tinyint(1) NOT NULL DEFAULT 0
_sql


//Add a missing index to the Templates table
);	revision( 11100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD INDEX (`family_name`)
_sql


//Remove the allow_newsletters column from the users table
);	revision( 11200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	DROP COLUMN `allow_newsletters`
_sql


//Remove NULLs as default values from some columns in the users table
);	revision( 11250
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `parent_id` int(10) unsigned default 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`parent_id` = 0
	WHERE `parent_id` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `screen_name` varchar(50) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`screen_name` = ''
	WHERE `screen_name` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `title` varchar(25) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`title` = ''
	WHERE `title` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `mobile` varchar(20) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`mobile` = ''
	WHERE `mobile` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `spare_varchar1` varchar(255) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`spare_varchar1` = ''
	WHERE `spare_varchar1` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `spare_varchar2` varchar(255) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`spare_varchar2` = ''
	WHERE `spare_varchar2` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `spare_varchar3` varchar(255) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`spare_varchar3` = ''
	WHERE `spare_varchar3` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `spare_varchar4` varchar(255) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`spare_varchar4` = ''
	WHERE `spare_varchar4` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `spare_varchar5` varchar(255) default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`spare_varchar5` = ''
	WHERE `spare_varchar5` IS NULL
_sql


//Reorder the primary key and add a missing index on the category link table
);	revision( 11300
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	ADD PRIMARY KEY (`category_id`,`content_type`,`item_id`,`version`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	ADD INDEX (`item_id`,`content_type`,`version`)
_sql


//Add a missing index on the groups table
);	revision( 11330
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD INDEX (`name`)
_sql


//Added a "public" flag to the categories table
//Also remove two unused columns
);	revision( 11400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD `public` tinyint(1) NOT NULL default 0
	AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD `landing_page_content_id` tinyint(1) NOT NULL default 0
	AFTER `public`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD `landing_page_content_type` varchar(20) NOT NULL default ''
	AFTER `landing_page_content_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	DROP COLUMN `restrict_content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	DROP COLUMN `language_id`
_sql


//Remove the no-longer used a "plugin_request" column from the Menu Nodes table
);	revision( 11425
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	DROP COLUMN `plugin_request`
_sql


//Added a "public" flag to the groups table
//Also remove the "public_name" column which wasn't multilingual
);	revision( 11430
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD `public` tinyint(1) NOT NULL default 0
	AFTER `name`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]groups` SET
		`public` = 1
	WHERE `public_name` != ''
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]visitor_phrases` (
		code,
		language_id,
		plugin_class,
		local_text)
	SELECT
		CONCAT('_GROUP_', g.id),
		l.id,
		'',
		g.public_name
	FROM `[[DB_NAME_PREFIX]]groups` AS g, `[[DB_NAME_PREFIX]]languages` AS l
	WHERE `public_name` != ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `public_name`
_sql

//Add indecies
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD INDEX (`public`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	ADD INDEX (`public`)
_sql


//Added an "allow_opt_in_opt_out" flag to the groups table
);	revision( 11440
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD `allow_opt_in_opt_out` tinyint(1) NOT NULL default 0
	AFTER `public`
_sql


//Added an "allow_opt_in_opt_out" flag to the groups table
);	revision( 11450
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD INDEX (`code`,`plugin_class`,`language_id`)
_sql


//Added support for uploading 7zip files
);	revision( 11550
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = '7z'
_sql


//Add Address line 3 to the users table
);	revision( 11650
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD `bus_address3` varchar(250) NOT NULL default ''
	AFTER `bus_address2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD `res_address3` varchar(250) NOT NULL default ''
	AFTER `res_address2`
_sql


//Create a new table for spare aliases
);	revision( 11660
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]spare_aliases`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]spare_aliases` (
		`alias` varchar(50) NOT NULL,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		PRIMARY KEY (`alias`),
		KEY (`content_type`, `content_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add a 'static method' option for signals
);	revision( 11771
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	ADD `static_method` tinyint(1) unsigned NOT NULL default 0
	AFTER `plugin_class`
_sql


//Change size of document text field
);	revision( 11805
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	MODIFY COLUMN `document_text` LONGTEXT NULL
_sql


//Create new tables for jobs
);	revision( 11950
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]jobs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]jobs` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`job_name` varchar(127) NOT NULL,
		`plugin_id` int(10) unsigned NOT NULL,
		`plugin_name` varchar(255) NOT NULL,
		`plugin_class` varchar(200) NOT NULL,
		`static_method` tinyint(1) unsigned NOT NULL default 0,
		`months` set('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec') NOT NULL default 'jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec',
		`days` set('mon','tue','wed','thr','fri','sat','sun') NOT NULL default 'mon,tue,wed,thr,fri,sat,sun',
		`hours` set('0h','1h','2h','3h','4h','5h','6h','7h','8h','9h','10h','11h','12h','13h','14h','15h','16h','17h','18h','19h','20h','21h','22h','23h') NOT NULL default '0h',
		`minutes` set('0m','5m','10m','15m','20m','25m','30m','35m','40m','45m','50m','55m','59m') NOT NULL default '0m',
		`first_n_days_of_month` tinyint(1) NOT NULL default 0,
		`log_on_action` tinyint(1) unsigned NOT NULL default '1',
		`log_on_no_action` tinyint(1) unsigned NOT NULL default 0,
		`email_on_action` tinyint(1) unsigned NOT NULL default 0,
		`email_on_no_action` tinyint(1) unsigned NOT NULL default 0,
		`last_run_started` datetime default NULL,
		`last_run_finished` datetime default NULL,
		`status` enum('never_run','rerun_scheduled','in_progress','action_taken','no_action_taken','error') NOT NULL default 'never_run',
		`last_successful_run` datetime default NULL,
		`last_action` datetime default NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `job_name` (`job_name`,`plugin_class`),
		KEY `plugin_id` (`plugin_id`),
		KEY `first_n_days_of_month` (`first_n_days_of_month`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]job_logs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]job_logs` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`job_id` int(10) unsigned NOT NULL,
		`status` enum('action_taken','no_action_taken','error') NOT NULL,
		`started` datetime default NULL,
		`finished` datetime default NULL,
		`note` text,
		PRIMARY KEY  (`id`),
		KEY `job_id` (`job_id`),
		KEY `status` (`status`),
		KEY `started` (`started`),
		KEY `finished` (`finished`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Create new tables for jobs
);	revision( 11960
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD `email_address_on_action` varchar(200) NOT NULL default ''
	AFTER `email_on_no_action`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD `email_address_on_no_action` varchar(200) NOT NULL default ''
	AFTER `email_address_on_action`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD `email_address_on_error` varchar(200) NOT NULL default ''
	AFTER `email_address_on_no_action`
_sql

);	revision( 11980

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD `attachment_body` text
	AFTER `body`
_sql

);	revision( 11984

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD `enable_attachment_body` tinyint(1) default 0 NOT NULL
	AFTER `attachment_body`
_sql


//Add keys to the user_content_accesslog table
	//Update: rewrote this so it won't cause a database error if there are duplicate rows.
);	revision( 12251
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_accesslog_new`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]user_content_accesslog_new` (
		`hit_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
		`user_id` int(10) unsigned NOT NULL default 0,
		`content_id` int(10) unsigned NOT NULL default 0,
		`content_type` varchar(20) NOT NULL default '',
		`content_version` int(10) unsigned NOT NULL default 0,
		`ip` int(10) NOT NULL default 0,
		PRIMARY KEY  (`hit_datetime`,`user_id`,`content_id`,`content_type`),
		KEY `user_id` (`user_id`),
		KEY `content_type` (`content_type`),
		KEY `content_id` (`content_id`),
		KEY `user_id_2` (`user_id`),
		KEY `content_id_2` (`content_id`),
		KEY `content_type_2` (`content_type`),
		KEY `ip` (`ip`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]user_content_accesslog_new`
	SELECT * FROM `[[DB_NAME_PREFIX]]user_content_accesslog`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_content_accesslog`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog_new`
	RENAME TO `[[DB_NAME_PREFIX]]user_content_accesslog`
_sql


//Fix a bad column definition
);	revision( 12295
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	MODIFY `landing_page_content_id` int(10) unsigned NOT NULL default 0
	AFTER `public`
_sql


);	revision(12619,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	ADD COLUMN `type` enum('list','boolean','text') AFTER `boolean_values`
_sql
,
<<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics`
	SET `type`=IF(boolean_values=1,'boolean','list')
_sql
,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	MODIFY `type` enum('list','boolean','text') NOT NULL
_sql


//Added support for docx
);	revision( 12650,
<<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` (`type`)
	VALUES ('docx'), ('xlsx')
_sql


);	revision(12657,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	DROP COLUMN `boolean_values`
_sql


//Added a new index to the menuitems table
);	revision( 13260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD INDEX (`target_loc`)
_sql


); 	revision( 13319
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_event`
	SET location_country_id = UCASE(location_country_id);
_sql


//Added a job enabled flag
);	revision( 13525
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD `enabled` tinyint(1) NOT NULL DEFAULT 0
	AFTER `static_method`
_sql


);	revision( 13595
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values`
	DROP INDEX `characteristic_id`
_sql


);	revision( 13596
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristic_values`
	CHANGE COLUMN `name` `name` TEXT  NOT NULL
_sql


//Remove some unused tables/columns
);	revision( 13620
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types`
	DROP COLUMN `enable_extract`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]mime_types`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `default_folder_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `enable_print`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `enable_emailfriend`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `enable_categories`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `special_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `disallow_normal_creation`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `bulk_upload`
_sql


); revision( 13653,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `image_filename` varchar(50) NOT NULL default '',
	ADD COLUMN `mime_type` enum('image/jpeg','image/gif','image/png','image/jpg','image/pjpeg') NOT NULL default 'image/jpeg',
	ADD COLUMN `width` smallint(5) unsigned default NULL,
	ADD COLUMN `height` smallint(5) unsigned default NULL,
	ADD COLUMN `size` int(10) unsigned default NULL,
	ADD COLUMN `data` mediumblob,
	ADD COLUMN `checksum` varchar(32) NOT NULL default '',
	ADD COLUMN `storekeeper_width` smallint(5) unsigned default NULL,
	ADD COLUMN `storekeeper_height` smallint(5) unsigned default NULL,
	ADD COLUMN `storekeeper_data` mediumblob,
	ADD COLUMN `storekeeper_size` int(10) unsigned default NULL
_sql


);	revision( 13654,
<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `checksum_inc_name` varchar(32) NOT NULL default ''
_sql


//Added support for uploading jpegs
);	revision( 13675
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]document_types` SET type = 'flv'
_sql


//Drop now unused columns from the site settings table
);	revision( 13760
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	DROP COLUMN `type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	DROP COLUMN `suppress_if_other_setting_false`
_sql


//Rename events for anyone who has them installed
);	revision( 13765
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]contenttypes` SET
		content_type_name_en = 'Single-day Event'
	WHERE content_type_id = 'event'
	  AND content_type_name_en = 'Event'
_sql


//Add a related_plugin_themes column to the Skins table
);	revision( 13766
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `related_plugin_themes` varchar(255) NOT NULL default ''
	AFTER `name`
_sql























		//					 //
		//  Changes for 6.0  //
		//					 //




//Forcebly run the HTML Snippet Plugin and the WYSIWYG Plugin
);	revision( 14000
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_html_snippet_v1',
		`class_name` = 'zenario_html_snippet',
		`display_name` = 'HTML Snippet',
		`status` = '_ENUM_RUNNING'
	ON DUPLICATE KEY UPDATE
		`status` = '_ENUM_RUNNING'
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_wysiwyg_editor_v1',
		`class_name` = 'zenario_wysiwyg_editor',
		`display_name` = 'HTML Snippet',
		`status` = '_ENUM_RUNNING'
	ON DUPLICATE KEY UPDATE
		`status` = '_ENUM_RUNNING'
_sql


//Changes for Plugin Settings in 6.0 to implement Version Controlled modules
); 	revision( 14005
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP INDEX `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	MODIFY COLUMN `name` varchar(250) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD INDEX (`name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `content_id` int(10) unsigned NOT NULL default 0
	AFTER `parent_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `content_type` varchar(20) NOT NULL default ''
	AFTER `content_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `content_version` int(10) unsigned NOT NULL default 0
	AFTER `content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `slot_name` varchar(100) NOT NULL default ''
	AFTER `content_version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD INDEX (`content_id`, `content_type`, `content_version`, `slot_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	MODIFY COLUMN `instance_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	MODIFY COLUMN `instance_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	MODIFY COLUMN `instance_id` int(10) unsigned NOT NULL default 0
_sql


//Updater the Theme Picker to work properly with Version Controlled modules in 6.0
); 	revision( 14010
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD COLUMN `plugin_id` int(10) unsigned NOT NULL default 0
	AFTER `nest`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_slot_theme_link` AS pstl
	   ON pstl.instance_id = pi.id
	SET pstl.plugin_id = pi.plugin_id
	WHERE pstl.nest = 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD PRIMARY KEY (`skin_id`,`slot_name`,`nest`,`plugin_id`,`instance_id`)
_sql


//Modify foreign keys to Admins so that they all point to the entries in the same database
); 	revision( 14015
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.owner_id = a.global_id
	  AND t.owner_authtype = 'super'
	SET t.owner_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.creating_author_id = a.global_id
	  AND t.creating_author_authtype = 'super'
	SET t.creating_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.last_author_id = a.global_id
	  AND t.last_author_authtype = 'super'
	SET t.last_author_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.publisher_id = a.global_id
	  AND t.publisher_authtype = 'super'
	SET t.publisher_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.hider_id = a.global_id
	  AND t.hider_authtype = 'super'
	SET t.hider_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS t
	   ON t.archiver_id = a.global_id
	  AND t.archiver_authtype = 'super'
	SET t.archiver_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]email_templates` AS t
	   ON t.created_by_id = a.global_id
	  AND t.created_by_authtype = 'super'
	SET t.created_by_id = a.id
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]email_templates` AS t
	   ON t.modified_by_id = a.global_id
	  AND t.modified_by_authtype = 'super'
	SET t.modified_by_id = a.id
_sql

//Update the data in the email templates table; drop the old authtype columns; sort out NULLs in a column definition
); 	revision( 14020
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates` SET
		`created_by_id` = 0
	WHERE `created_by_id` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	MODIFY COLUMN `created_by_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	DROP COLUMN `created_by_authtype`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates` SET
		`modified_by_id` = 0
	WHERE `modified_by_id` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	MODIFY COLUMN `modified_by_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	DROP COLUMN `modified_by_authtype`
_sql


//Changes for Content in 6.0
); 	revision( 14025
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content`
_sql

//Create a new table to store non-version controlled information
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content` (
	  `id` int(10) unsigned NOT NULL,
	  `type` varchar(20) NOT NULL,
	  `tag_id` varchar(32) NOT NULL,
	  `language_id` varchar(5) NOT NULL,
	  `alias` varchar(50) NOT NULL default '',
	  `first_created_datetime` datetime default NULL,
	  `visitor_version` int(10) unsigned NOT NULL default 0,
	  `admin_version` int(10) unsigned NOT NULL default 1,
	  `status` enum('first_draft','published_with_draft','hidden_with_draft','trashed_with_draft','published','hidden','trashed','deleted') NOT NULL default 'first_draft',
	  `lock_owner_id` int(10) unsigned NOT NULL default 0,
	  PRIMARY KEY  (`id`,`type`),
	  UNIQUE KEY  (`tag_id`),
	  KEY (`type`),
	  KEY (`language_id`),
	  KEY (`alias`),
	  KEY (`first_created_datetime`),
	  KEY (`visitor_version`),
	  KEY (`admin_version`),
	  KEY (`status`),
	  KEY (`lock_owner_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
		//  tag_id is a unique id for Storekeeper, and is made up of the cID concatenated with the cType.  //
		//  Unpublished drafts can be deleted as before, but a record of them is kept in the content table so that we do not re-use the id and potentially get bad data in linking tables.  //
		//  admin_version should always equal published_version or published_version + 1, or else 0 if a Content Item cannot be seen  //

//Populate the table as best we can from the version controlled table
	//Fill it with information from the most recent version that isn't a draft, or from drafts if no other version exists
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'audio',
		CONCAT(id, '_audio'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_audio`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'blog',
		CONCAT(id, '_blog'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_blog`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'document',
		CONCAT(id, '_document'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_document`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'event',
		CONCAT(id, '_event'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_event`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'html',
		CONCAT(id, '_html'),
		language_id,
		alias,
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_html`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'news',
		CONCAT(id, '_news'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_news`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'picture',
		CONCAT(id, '_picture'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_picture`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'recurringevent',
		CONCAT(id, '_recurringevent'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_recurringevent`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]content`
	SELECT
		id,
		'video',
		CONCAT(id, '_video'),
		language_id,
		'',
		NULL,
		IF (`status` = 'private_draft', 0, version),
		version,
		IF (`status` = 'private_draft', 'first_draft',
		 IF (`status` = 'archived', 'trashed', `status`)),
		0
	FROM `[[DB_NAME_PREFIX]]content_video`
	ORDER BY id, `status` != 'private_draft' DESC, version DESC
_sql


//Note down that unpublished drafts exists, as they may have been skipped in the statements above
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_audio` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'audio'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_blog` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'blog'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_document` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'document'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_event` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'event'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_html` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'html'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_news` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'news'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_picture` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'picture'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_recurringevent` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'recurringevent'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_video` AS oc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS nc
	   ON nc.id = oc.id
	  AND nc.type = 'video'
	  AND oc.status = 'private_draft'
	SET nc.admin_version = oc.version,
		nc.status = IF (nc.status = 'published', 'published_with_draft', IF (nc.status = 'hidden', 'hidden_with_draft', IF (nc.status = 'trashed', 'trashed_with_draft', nc.status)))
_sql


//More changes for Content in 6.0
); 	revision( 14030
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]versions`
_sql

//Create a single table to hold generic information for each of the Content Tables
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]versions` (
	  `id` int(10) unsigned NOT NULL,
	  `type` varchar(20) NOT NULL,
	  `tag_id` varchar(32) NOT NULL,
	  `version` int(10) unsigned NOT NULL,
	  `privacy` enum('public','all_extranet_users','group_members','specific_users','no_access') NOT NULL default 'public',
	  `log_user_access` tinyint(1) NOT NULL default 0,
	  `title` varchar(250) NOT NULL default '',
	  `description` mediumtext,
	  `keywords` text,
	  `content_summary` mediumtext,
	  `file_id` int(10) unsigned NOT NULL default 0,
	  `filename` varchar(50) NOT NULL default '',
	  `sticky_image_id` int(10) unsigned NOT NULL default 0,
	  `template_id` int(10) unsigned NOT NULL default 0,
	  `skin_id` int(10) unsigned NOT NULL default 0,
	  `created_datetime` datetime default NULL,
	  `creating_author_id` int(10) unsigned NOT NULL default 0,
	  `last_author_id` int(10) unsigned NOT NULL default 0,
	  `publisher_id` int(10) unsigned NOT NULL default 0,
	  `published_datetime` datetime default NULL,
	  `concealer_id` int(10) unsigned NOT NULL default 0,
	  `concealed_datetime` datetime default NULL,
	  `publication_date` datetime default NULL,
	  PRIMARY KEY  (`id`,`type`,`version`),
	  UNIQUE KEY  (`tag_id`,`version`),
	  KEY (`type`),
	  KEY (`version`),
	  KEY (`created_datetime`),
	  KEY (`published_datetime`),
	  KEY (`publication_date`),
	  KEY (`file_id`),
	  KEY (`sticky_image_id`),
	  KEY (`template_id`),
	  FULLTEXT KEY (`title`),
	  FULLTEXT KEY (`description`),
	  FULLTEXT KEY (`keywords`),
	  FULLTEXT KEY (`content_summary`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Insert the data from the old Content Tables into this new one, converting where we need to
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'audio',
		CONCAT(id, '_audio'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_audio`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'blog',
		CONCAT(id, '_blog'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		'' AS filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_blog`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'document',
		CONCAT(id, '_document'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_document`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'event',
		CONCAT(id, '_event'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		'' AS filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_event`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'html',
		CONCAT(id, '_html'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		'' AS filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_html`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'news',
		CONCAT(id, '_news'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		'' AS filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_news`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'picture',
		CONCAT(id, '_picture'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_picture`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'recurringevent',
		CONCAT(id, '_recurringevent'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		'' AS filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_recurringevent`
	ORDER BY id, version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]versions`
	SELECT
		id,
		'video',
		CONCAT(id, '_video'),
		version,
		IF (private = 0, 'public', 'all_extranet_users'),
		log_user_access,
		title,
		description,
		keywords,
		content_summary,
		0 AS file_id,
		filename,
		0 AS sticky_image_id,
		template_id,
		IFNULL(skin_id, 0),
		created_datetime,
		creating_author_id,
		last_author_id,
		publisher_id,
		published_datetime,
		IF (`status` = 'archived', archiver_id,
		 IF (`status` = 'hidden', hider_id, 0)),
		IF (`status` = 'archived', archived_datetime,
		 IF (`status` = 'hidden', hidden_datetime, NULL)),
		publication_date
	FROM `[[DB_NAME_PREFIX]]content_video`
	ORDER BY id, version
_sql


//Update the privacy enum to flag to flag if a Content Item is private for specific Users/Groups
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_content_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON l.content_id = v.id
	  AND l.content_type = v.type
	  AND l.content_version = v.version
	SET v.privacy = 'specific_users'
	WHERE v.privacy = 'all_extranet_users'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]group_content_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON l.content_id = v.id
	  AND l.content_type = v.type
	  AND l.content_version = v.version
	SET v.privacy = 'group_members'
	WHERE v.privacy = 'all_extranet_users'
_sql


//Try to populate the first_created_datetime column as best we can.
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	SET c.first_created_datetime = (
		SELECT MIN(v.created_datetime)
		FROM `[[DB_NAME_PREFIX]]versions` AS v
		WHERE c.id = v.id
		  AND c.type = v.type
	)
_sql


//Add indexing for Plugin Settings and Content in Plugin Settings
//And add foreign keys to Content/Images if a Setting links to Content/Images
); 	revision( 14035
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	CHANGE COLUMN `value` `value_old` blob
_sql

); 	revision( 14040
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `value` mediumtext
	AFTER `nest`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `is_content` enum('synchronized_setting', 'version_controlled_setting', 'version_controlled_content') NOT NULL default 'synchronized_setting'
	AFTER `value`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `foreign_key_to` enum('category','categories','content','email_template','file') NULL default NULL
	AFTER `is_content`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `foreign_key_id` int(10) unsigned NOT NULL default 0
	AFTER `foreign_key_to`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `foreign_key_type` varchar(20) NOT NULL default ''
	AFTER `foreign_key_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `dangling_cross_references` enum('keep','remove','delete_instance') NOT NULL default 'remove'
	AFTER `foreign_key_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX (`is_content`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX (`foreign_key_to`, `foreign_key_id`, `foreign_key_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX (`dangling_cross_references`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX (`value`(64))
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		`value` = CONVERT(`value_old` USING utf8)
_sql

//Update the format of tag_id in Plugin Settings to be cType_cID rather than cID_cType for sorting purposes
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.value = c.tag_id
	SET ps.value = CONCAT(c.type, '_', c.id),
		ps.foreign_key_to = 'content',
		ps.foreign_key_id = c.id,
		ps.foreign_key_type = c.type
_sql

); 	revision( 14045
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP COLUMN `value_old`
_sql


//Update the format of tag_id in the Content/Version tables to be cType_cID rather than cID_cType for sorting purposes
); 	revision( 14050
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content`
	SET tag_id = CONCAT(type, '_', id)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]versions`
	SET tag_id = CONCAT(type, '_', id)
_sql


//Add checksums to the raw storeage tables, just so they are easier to import from
); 	revision( 14055
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_audio_store`
	ADD COLUMN `checksum` varchar(32) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_picture_store`
	ADD COLUMN `checksum` varchar(32) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_video_store`
	ADD COLUMN `checksum` varchar(32) NOT NULL default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]raw_audio_store` SET
		checksum = MD5(data)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]raw_picture_store` SET
		checksum = MD5(data)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]raw_video_store` SET
		checksum = MD5(data)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_audio_store`
	ADD INDEX (`checksum`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_picture_store`
	ADD INDEX (`checksum`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]raw_video_store`
	ADD INDEX (`checksum`)
_sql


//Changes for Images and Files in 6.0
); 	revision( 14060
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]files`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]files` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `checksum` varchar(32) NOT NULL,
	  `usage` enum('content','favicon','group','inline','mobile_icon','template','user') NOT NULL,
	  `created_datetime` datetime default NULL,
	  `filename` varchar(50) NOT NULL,
	  `mime_type` varchar(32) NOT NULL,
	  `width` smallint(5) unsigned NOT NULL default 0,
	  `height` smallint(5) unsigned NOT NULL default 0,
	  `size` int(10) unsigned NOT NULL,
	  `location` enum('db', 'docstore') NOT NULL,
	  `data` longblob default NULL,
	  `path` varchar(128) NOT NULL default '',
	  `storekeeper_width` tinyint(3) unsigned NOT NULL default 0,
	  `storekeeper_height` tinyint(3) unsigned NOT NULL default 0,
	  `storekeeper_data` blob default NULL,
	  `storekeeper_list_width` tinyint(3) unsigned NOT NULL default 0,
	  `storekeeper_list_height` tinyint(3) unsigned NOT NULL default 0,
	  `storekeeper_list_data` blob default NULL,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY (`checksum`, `usage`),
	  KEY (`usage`),
	  KEY (`created_datetime`),
	  KEY (`filename`),
	  KEY (`mime_type`),
	  KEY (`location`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
	  //checksum
		//  Two identical files (for the same `usage`) will not be stored twice.  //
	  //`usage`
		//  Mainly used for access rights checks. E.g. Inline images have no permissions, you just need to know the checksums.  //
	  //filename
		//  Note that if two identical files are uploaded, this will be the file name from one of them.  //
	  //width
	  //height
		//  Width and height are for images only.  //
	  //location
		//  Files can be stored in either the docstore dir or in the data column  //
	  //path
		//  From now on files should be stored under the format docstore/id/filename, but this column is for backwards compatability purposes with the old format  //
	  //storekeeper_width
	  //storekeeper_height
	  //storekeeper_data
		//  Storekeeper thumbnails, for images only.  //


//Import images data from various tables into the new images table
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		IF (`usage` = 'mobile', 'mobile_icon', 'favicon'),
		filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]favicons`
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'group',
		filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]group_images`
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'user',
		filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]user_images`
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'content',
		c.created_datetime,
		c.filename,
		c.mime_type,
		NULL,
		NULL,
		c.size,
		'db',
		r.data,
		''
	FROM `[[DB_NAME_PREFIX]]raw_audio_store` AS r
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS c
	   ON c.id = r.content_id
	  AND c.version = r.content_version
	ORDER BY c.id, c.version
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'content',
		c.created_datetime,
		c.filename,
		c.mime_type,
		c.width,
		c.height,
		c.size,
		'db',
		r.data,
		''
	FROM `[[DB_NAME_PREFIX]]raw_picture_store` AS r
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS c
	   ON c.id = r.content_id
	  AND c.version = r.content_version
	ORDER BY c.id, c.version
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'content',
		c.created_datetime,
		c.filename,
		c.mime_type,
		NULL,
		NULL,
		c.size,
		'db',
		r.data,
		''
	FROM `[[DB_NAME_PREFIX]]raw_video_store` AS r
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS c
	   ON c.id = r.content_id
	  AND c.version = r.content_version
	ORDER BY c.id, c.version
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'template',
		image_filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]templates`
	WHERE size IS NOT NULL
	  AND size > 0
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'inline',
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]images`
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]files` (
		checksum,
		`usage`,
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		location,
		data,
		path
	) SELECT
		checksum,
		'inline',
		created_datetime,
		filename,
		mime_type,
		width,
		height,
		size,
		'db',
		data,
		''
	FROM `[[DB_NAME_PREFIX]]movies`
_sql

//Add new foreign keys to some tables to link to the new files table
); 	revision( 14065
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
	AFTER `active`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
	AFTER `status`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
	AFTER `skin_id`
_sql

//Populate these foreign keys
); 	revision( 14070
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]group_images` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]groups` AS g
	   ON l.group_id = g.id
	SET g.image_id = f.id
	WHERE f.`usage` = 'group'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]user_images` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]users` AS u
	   ON l.user_id = u.id
	SET u.image_id = f.id
	WHERE f.`usage` = 'user'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]raw_audio_store` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = l.content_id
	  AND v.type = 'audio'
	  AND v.version = l.content_version
	SET v.file_id = f.id
	WHERE f.`usage` = 'content'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]raw_picture_store` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = l.content_id
	  AND v.type = 'picture'
	  AND v.version = l.content_version
	SET v.file_id = f.id
	WHERE f.`usage` = 'content'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]raw_video_store` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = l.content_id
	  AND v.type = 'video'
	  AND v.version = l.content_version
	SET v.file_id = f.id
	WHERE f.`usage` = 'content'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.checksum = f.checksum
	SET t.image_id = f.id
	WHERE f.`usage` = 'template'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]images` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.value = CONCAT('image_', l.checksum_inc_name)
	SET ps.value = f.id,
		ps.foreign_key_to = 'file',
		ps.foreign_key_id = f.id
	WHERE f.`usage` = 'inline'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]movies` AS l
	   ON l.checksum = f.checksum
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.value = CONCAT('movie_', l.checksum_inc_name)
	SET ps.value = f.id,
		ps.foreign_key_to = 'file',
		ps.foreign_key_id = f.id
	WHERE f.`usage` = 'inline'
_sql


//Rename the setting name for HTML Snippets from 'display_this_html' to 'html'
);	revision( 14075
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` SET
		name = 'html'
	WHERE name = 'display_this_html'
_sql


//Convert Content to new format
); 	revision( 14080
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tmp_content_link`
_sql

//Create a temp table to store where the Content Areas are
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]tmp_content_link` (
	  `content_id` int(10) unsigned NOT NULL,
	  `content_type` varchar(20) NOT NULL,
	  `content_version` int(10) unsigned NOT NULL,
	  `slot_name` varchar(100) NOT NULL default '',
	  `instance_id` int(10) unsigned NOT NULL,
	  `content_area` varchar(32) NOT NULL default 'content_bodymain',
	  `editor_size` varchar(16) NOT NULL default '_NARROW',
	  `framework` varchar(50) NOT NULL default 'standard',
	  PRIMARY KEY (`content_id`, `content_type`, `content_version`, `slot_name`),
	  KEY (`instance_id`),
	  KEY (`content_area`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Populate this with the locations of the old Content Areas
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]tmp_content_link` (
		content_id,
		content_type,
		content_version,
		slot_name,
		instance_id
	) SELECT
		v.id,
		v.type,
		v.version,
		l.slot_name,
		l.instance_id
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.family_name = l.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.template_id = t.template_id
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]tmp_content_link` (
		content_id,
		content_type,
		content_version,
		slot_name,
		instance_id
	) SELECT
		v.id,
		v.type,
		v.version,
		l.slot_name,
		l.instance_id
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.template_id = l.template_id
_sql

, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]tmp_content_link` (
		content_id,
		content_type,
		content_version,
		slot_name,
		instance_id
	) SELECT
		l.content_id,
		l.content_type,
		l.content_version,
		l.slot_name,
		l.instance_id
	FROM `[[DB_NAME_PREFIX]]plugin_inst_item_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
_sql

//Note down if this is the head, main or foot content area
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.name = 'section'
	  AND ps.instance_id = tcl.instance_id
	  AND ps.value like 'content_body%'
	SET tcl.content_area = ps.value
_sql

//Copy the rest of the Instance Settings
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.name = 'editor_size'
	  AND ps.instance_id = tcl.instance_id
	SET tcl.editor_size = ps.value
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = tcl.instance_id
	SET tcl.framework = pi.framework
_sql


//Add Version Controlled WYSIWYG Editor wherever there are Content Display and Editor modules in the plugin_instances tab
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_instances` (
		plugin_id,
		content_id,
		content_type,
		content_version,
		slot_name,
		framework
	) SELECT
		new.id,
		tcl.content_id,
		tcl.content_type,
		tcl.content_version,
		tcl.slot_name,
		tcl.framework
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS new
	   ON new.name = 'zenario_wysiwyg_editor_v1'
_sql

//Update our temp table with these newly created ids
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON tcl.content_id = pi.content_id
	  AND tcl.content_type = pi.content_type
	  AND tcl.content_version = pi.content_version
	  AND tcl.slot_name = pi.slot_name
	SET tcl.instance_id = pi.id
_sql

//Migrate the Content by inserting new Plugin Settings for each of these
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_audio` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'audio'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_blog` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'blog'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_document` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'document'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_event` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'event'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_html` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'html'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_news` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'news'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_picture` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'picture'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_recurringevent` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'recurringevent'
	  AND tcl.content_version = c.version
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value,
		is_content
	) SELECT
		tcl.instance_id,
		'html',
		IF (tcl.content_area = 'content_bodytop', c.content_bodytop, IF (tcl.content_area = 'content_bodyfoot', c.content_bodyfoot, c.content_bodymain)),
		'version_controlled_content'
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
	INNER JOIN `[[DB_NAME_PREFIX]]content_video` AS c
	   ON tcl.content_id = c.id
	  AND tcl.content_type = 'video'
	  AND tcl.content_version = c.version
_sql

//Copy the rest of the settings from the Content Plugin Instances as well
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		value
	) SELECT
		tcl.instance_id,
		'editor_size',
		tcl.editor_size
	FROM `[[DB_NAME_PREFIX]]tmp_content_link` AS tcl
_sql

//Add Version Controlled WYSWIG Editor modules wherever there are Content Display and Editor modules into the three slot placement tables
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_item_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS new
	   ON new.name = 'zenario_wysiwyg_editor_v1'
	SET l.plugin_id = new.id,
		l.instance_id = 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_temp_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS new
	   ON new.name = 'zenario_wysiwyg_editor_v1'
	SET l.plugin_id = new.id,
		l.instance_id = 0
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS old
	   ON old.name = 'zenario_content_v1'
	  AND old.id = l.plugin_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS new
	   ON new.name = 'zenario_wysiwyg_editor_v1'
	SET l.plugin_id = new.id,
		l.instance_id = 0
_sql


//Drop the temporary table
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]tmp_content_link`
_sql


//Add a mime_types column to the document types table
); 	revision( 14085
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types`
	ADD COLUMN `mime_type` varchar(128) NOT NULL default ''
_sql


//Handle the tracking of inline images/files, and sticky images
); 	revision( 14090
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]inline_file_content_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]inline_file_content_link` (
	  `file_id` int(10) unsigned NOT NULL,
	  `filename` varchar(50) NOT NULL,
	  `content_id` int(10) unsigned NOT NULL,
	  `content_type` varchar(20) NOT NULL,
	  `content_version` int(10) unsigned NOT NULL,
	  PRIMARY KEY (`content_id`, `content_type`, `content_version`, `file_id`, `filename`),
	  KEY (`file_id`, `filename`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
	//  This linking table should really be kept up to date by scanning any Version Controlled HTML Snippets (and anything else that can accept images) for the links.
	//  Images that aren't in there should be removed; images that have been added by copying and pasting from another Content Item should be added.  //


//Migrate the sticky image flag into the 6.0 format
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]versions` AS v
	INNER JOIN `[[DB_NAME_PREFIX]]images` AS i
	   ON i.content_item_id = v.id
	  AND i.content_item_type = v.type
	  AND i.content_item_version = v.version
	  AND i.sticky_flag = 1
	INNER JOIN `[[DB_NAME_PREFIX]]files` AS f
	   ON f.`usage` = 'inline'
	  AND f.checksum = i.checksum
	SET v.sticky_image_id = f.id
_sql

//Populate the new inline_file_content_link table with the old data
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]inline_file_content_link`
	SELECT
		f.id,
		i.filename,
		i.content_item_id,
		i.content_item_type,
		i.content_item_version
	FROM `[[DB_NAME_PREFIX]]images` AS i
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON i.content_item_id = v.id
	  AND i.content_item_type = v.type
	  AND i.content_item_version = v.version
	INNER JOIN `[[DB_NAME_PREFIX]]files` AS f
	   ON f.`usage` = 'inline'
	  AND f.checksum = i.checksum
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]inline_file_content_link`
	SELECT
		f.id,
		m.filename,
		m.content_item_id,
		m.content_item_type,
		m.content_item_version
	FROM `[[DB_NAME_PREFIX]]movies` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON m.content_item_id = v.id
	  AND m.content_item_type = v.type
	  AND m.content_item_version = v.version
	INNER JOIN `[[DB_NAME_PREFIX]]files` AS f
	   ON f.`usage` = 'inline'
	  AND f.checksum = m.checksum
_sql

//Note that this probably won't be completely accurate; A PHP script will be run in zenario/admin/db_updates/data_conversion/local.inc.php
//to scan anything related to a Content Item and sync this table properly



//Changes for Language Special Pages in 6.0
); 	revision( 14105
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]special_pages`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]special_pages` (
		`content_id` int(10) unsigned NULL default NULL,
		`content_type` varchar(20) NULL default NULL,
		`page_type` varchar(64) NOT NULL,
		`create_lang_equiv_content` tinyint(1) unsigned NOT NULL default 0,
		`required_plugin_id` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY (`page_type`),
		UNIQUE KEY (`content_type`,`content_id`),
		KEY (`required_plugin_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql



//Migrate the old Special Page data
); 	revision( 14110
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT homepage_id, 'html', 'zenario_home'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE homepage_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

//If we didn't just create a homepage, add an empty row to ensure that the homepage will be first
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	VALUES (NULL, NULL, 'zenario_home')
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT extranet_login_page_id, 'html', 'zenario_login'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE extranet_login_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT search_page_id, 'html', 'zenario_search'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE search_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT extranet_registration_page_id, 'html', 'zenario_registration'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE extranet_registration_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT extranet_password_reminder_page_id, 'html', 'zenario_password_reminder'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE extranet_password_reminder_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT extranet_change_password_page_id, 'html', 'zenario_change_password'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE extranet_change_password_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]special_pages` (`content_id`, `content_type`, `page_type`)
	SELECT extranet_logout_page_id, 'html', 'zenario_logout'
	FROM `[[DB_NAME_PREFIX]]languages`
	WHERE extranet_logout_page_id != 0
	ORDER BY homepage_id = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_homepage_id') DESC
	LIMIT 1
_sql


//Drop old Image/File tables
); 	revision( 14115
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]favicons`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]group_images`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]images`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]movies`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_audio_store`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_picture_store`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]raw_video_store`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]user_images`
_sql


//Drop several unused columns from the templates table
); 	revision( 14120
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `usage_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `editor_width_bodymain`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `custom_bodytag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `enable_content_head`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `enable_content_bodytop`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `enable_content_bodymain`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `enable_content_bodyfoot`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `allow_rss`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `image_filename`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `mime_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `size`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `checksum`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `storekeeper_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `storekeeper_height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `storekeeper_data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `storekeeper_size`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP COLUMN `checksum_inc_name`
_sql


//Drop the old special page columns from the languages table
); 	revision( 14125
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `homepage_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `search_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `extranet_login_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `extranet_registration_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `extranet_password_reminder_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `extranet_change_password_page_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `extranet_logout_page_id`
_sql


//Drop the old temporary Content Tables
); 	revision( 14130
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_audio_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_blog_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_document_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_event_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_html_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_news_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_picture_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_recurring_event`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_recurring_event_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_recurringevent_tmp`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_video_tmp`
_sql


//We don't want to delete the old Content Tables, as if something went wrong with the
//Migration, or if there were ever any questions about what the Migration had done, it
//would be safer to keep the old data.
//So I'm renaming the tables with an "_old_" prefix to distinguish them
); 	revision( 14135
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_audio`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_audio`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_audio`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_blog`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_blog`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_blog`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_document`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_document`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_document`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_event`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_event`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_event`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_html`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_html`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_news`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_news`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_news`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_picture`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_picture`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_picture`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_recurringevent`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_recurringevent`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_recurringevent`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]_old_content_video`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_video`
	RENAME TO `[[DB_NAME_PREFIX]]_old_content_video`
_sql


//Attempt to make sure that the default Template ids are set for each Content Type, if they are not already.
//If setting a Template, prioritise a Template from the default_template_family,
//otherwise just use the first created Template of that Content Type.
); 	revision( 14400
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]contenttypes` AS ct SET
		ct.default_template_id = IFNULL((
			SELECT t.template_id
			FROM `[[DB_NAME_PREFIX]]templates` AS t
			WHERE t.content_type = ct.content_type_id
			ORDER BY t.family_name = (SELECT value FROM `[[DB_NAME_PREFIX]]site_settings` WHERE name = 'default_template_family') DESC, t.template_id
			LIMIT 1
		), 0)
	WHERE ct.default_template_id NOT IN (
		SELECT t2.template_id
		FROM `[[DB_NAME_PREFIX]]templates` AS t2
		WHERE t2.content_type = ct.content_type_id
	)
_sql


//Remove the default_template_family and default_homepage_id Site Settings
); 	revision( 14500
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]site_settings`
	WHERE name IN('default_template_family', 'default_homepage_id')
_sql


//Update the format of the parent_id column to not use NULLs
); 	revision( 14650
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]users` SET
		`parent_id` = 0
	WHERE `parent_id` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	MODIFY COLUMN `parent_id` int(10) unsigned NOT NULL default 0
_sql


//Handle a rename of the zenario_ecommerce_bundle_manager Plugin's directory name
//(Note this line will only work if it's uninstalled; the couple of sites with it installed will
// also need to rename the directory name in the local_revision_numbers table.)
); 	revision( 14660
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET `name` = 'zenario_ecommerce_bundle_manager_v1'
	WHERE `name` =  'zenario_ecommerce_bundle_manager'
_sql


//Add foreign keys to the plugin_settings table for Categories and Email Templates
); 	revision( 14680
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]email_templates` AS et
	   ON ps.value = et.id
	SET ps.value = et.id,
		ps.foreign_key_to = 'email_template',
		ps.foreign_key_id = et.id
	WHERE ps.name = 'confirmation_template'
	   OR ps.name LIKE '%email_template'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]categories` AS cat
	   ON ps.value = cat.id
	SET ps.value = cat.id,
		ps.foreign_key_to = 'category',
		ps.foreign_key_id = cat.id
	WHERE ps.name = 'category'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	SET ps.foreign_key_to = 'categories'
	WHERE ps.name = 'hierarchical_categories'
_sql


//Automatically set up all Banners to be deleted when their target Content Items are deleted as part of the migration to 6.0
); 	revision( 14700
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ht
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS lt
	   ON lt.instance_id = ht.instance_id
	  AND lt.nest = ht.nest
	  AND lt.name = 'link_type'
	  AND lt.value = '_CONTENT_ITEM'
	SET ht.dangling_cross_references = 'delete_instance'
	WHERE ht.name = 'hyperlink_target'
	  AND ht.value IS NOT NULL
	  AND ht.value != ''
_sql


//Update the format of a few settings for modules, so that there are no spaces in enum values
); 	revision( 14710
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'Screen_Name'
	WHERE value = 'Screen Name'
	  AND name = 'login_with'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'Most_Recent_First'
	WHERE value = 'Most recent first'
	  AND name = 'order'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'Oldest_First'
	WHERE value = 'Oldest first'
	  AND name = 'order'
_sql

//Switch extract-based Content Lists to using descriptions
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = 'description'
	WHERE value IN ('content_bodytop', 'content_bodymain', 'content_bodyfoot')
	  AND name = 'data_field'
_sql


//Add a flag to determine whether a Content Item's Summary should be synced with an inline editor
); 	revision( 14770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `lock_summary` tinyint(1) unsigned NOT NULL default 0
	AFTER `content_summary`
_sql


//Add a flag to determine whether a Content Item's Summary should be synced with an inline editor
); 	revision( 14780
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings`
		(instance_id, name, nest, value)
	SELECT instance_id, 'canvas', nest, 'fixed_width'
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name IN ('set_width', 'max_thumb_width')
	  AND value IS NOT NULL
	  AND value
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings`
		(instance_id, name, nest, value)
	SELECT instance_id, 'canvas', nest, 'fixed_height'
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name IN ('set_height', 'max_thumb_height')
	  AND value IS NOT NULL
	  AND value
	ON DUPLICATE KEY UPDATE value = 'fixed_width_and_height'
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings`
		(instance_id, name, nest, value)
	SELECT instance_id, 'w_canvas', nest, 'fixed_width'
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name IN ('w_width')
	  AND value IS NOT NULL
	  AND value
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings`
		(instance_id, name, nest, value)
	SELECT instance_id, 'w_canvas', nest, 'fixed_height'
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name IN ('w_height')
	  AND value IS NOT NULL
	  AND value
	ON DUPLICATE KEY UPDATE value = 'fixed_width_and_height'
_sql


//Rename the "CMS Core" modules to the "Features" modules
); 	revision( 14790
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]jobs` SET
		plugin_name = 'zenario_common_features_v1',
		plugin_class = 'zenario_common_features'
	WHERE plugin_name = 'zenario_cms_core_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]jobs` SET
		plugin_name = 'zenario_pro_features_v1',
		plugin_class = 'zenario_pro_features'
	WHERE plugin_name = 'zenario_cms_core_pro_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]jobs` SET
		plugin_name = 'zenario_probusiness_features_v1',
		plugin_class = 'zenario_probusiness_features'
	WHERE plugin_name = 'zenario_cms_core_probusiness_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]jobs` SET
		plugin_name = 'zenario_enterprise_features_v1',
		plugin_class = 'zenario_enterprise_features'
	WHERE plugin_name = 'zenario_cms_core_enterprise_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		plugin_name = 'zenario_common_features_v1',
		plugin_class_name = 'zenario_common_features'
	WHERE plugin_name = 'zenario_cms_core_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		plugin_name = 'zenario_pro_features_v1',
		plugin_class_name = 'zenario_pro_features'
	WHERE plugin_name = 'zenario_cms_core_pro_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		plugin_name = 'zenario_probusiness_features_v1',
		plugin_class_name = 'zenario_probusiness_features'
	WHERE plugin_name = 'zenario_cms_core_probusiness_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		plugin_name = 'zenario_enterprise_features_v1',
		plugin_class_name = 'zenario_enterprise_features'
	WHERE plugin_name = 'zenario_cms_core_enterprise_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		dependency_plugin_class_name = 'zenario_common_features'
	WHERE dependency_plugin_class_name = 'zenario_cms_core'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		dependency_plugin_class_name = 'zenario_pro_features'
	WHERE dependency_plugin_class_name = 'zenario_cms_core_pro'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		dependency_plugin_class_name = 'zenario_probusiness_features'
	WHERE dependency_plugin_class_name = 'zenario_cms_core_probusiness'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_dependencies` SET
		dependency_plugin_class_name = 'zenario_enterprise_features'
	WHERE dependency_plugin_class_name = 'zenario_cms_core_enterprise'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		name = 'zenario_common_features_v1',
		class_name = 'zenario_common_features'
	WHERE name = 'zenario_cms_core_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		name = 'zenario_pro_features_v1',
		class_name = 'zenario_pro_features'
	WHERE name = 'zenario_cms_core_pro_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		name = 'zenario_probusiness_features_v1',
		class_name = 'zenario_probusiness_features'
	WHERE name = 'zenario_cms_core_probusiness_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules` SET
		name = 'zenario_enterprise_features_v1',
		class_name = 'zenario_enterprise_features'
	WHERE name = 'zenario_cms_core_enterprise_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]signals` SET
		plugin_name = 'zenario_common_features_v1',
		plugin_class = 'zenario_common_features'
	WHERE plugin_name = 'zenario_cms_core_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]signals` SET
		plugin_name = 'zenario_pro_features_v1',
		plugin_class = 'zenario_pro_features'
	WHERE plugin_name = 'zenario_cms_core_pro_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]signals` SET
		plugin_name = 'zenario_probusiness_features_v1',
		plugin_class = 'zenario_probusiness_features'
	WHERE plugin_name = 'zenario_cms_core_probusiness_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]signals` SET
		plugin_name = 'zenario_enterprise_features_v1',
		plugin_class = 'zenario_enterprise_features'
	WHERE plugin_name = 'zenario_cms_core_enterprise_v1'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = REPLACE(value, 'zenario_cms_core::', 'zenario_common_features::')
	WHERE value LIKE 'zenario_cms_core::%'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = REPLACE(value, 'zenario_cms_core_pro::', 'zenario_pro_features::')
	WHERE value LIKE 'zenario_cms_core_pro::%'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = REPLACE(value, 'zenario_cms_core_probusiness::', 'zenario_probusiness_features::')
	WHERE value LIKE 'zenario_cms_core_probusiness::%'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		value = REPLACE(value, 'zenario_cms_core_enterprise::', 'zenario_enterprise_features::')
	WHERE value LIKE 'zenario_cms_core_enterprise::%'
_sql


//Drop the contenttype_settings table, keeping the height value for the content_max_filesize and converting it to a Site Setting
);	revision( 14800
, <<<_sql
	REPLACE INTO `[[DB_NAME_PREFIX]]site_settings` (`name`,  `value`)
	SELECT 'content_max_filesize', `value`
	FROM `[[DB_NAME_PREFIX]]contenttype_settings`
	WHERE `name` = 'max_filesize'
	ORDER BY `value` DESC
	LIMIT 1
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]contenttype_settings`
_sql


//Drop the show_pub_date column from the contenttypes table
);	revision( 14810
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	DROP COLUMN `show_pub_date`
_sql

//Add some missing indexes while we're at it
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	ADD INDEX (`content_type_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	ADD INDEX (`default_template_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	ADD INDEX (`plugin_id`)
_sql


//Drop the plugin_setting_defs table, and recreate it in a slightly new format
);	revision( 14830
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_setting_defs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs` (
		`plugin_id` int(10) unsigned NOT NULL,
		`plugin_class_name` varchar(255) NOT NULL,
		`name` varchar(50) NOT NULL,
		`default_value` mediumtext,
		PRIMARY KEY  (`plugin_id`,`name`),
		UNIQUE KEY  (`plugin_class_name`,`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Create a new local table as a replacement for the spare_urls nee url_alias_redirects global table
);	revision( 14870
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]spare_domain_names`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]spare_domain_names` (
		`requested_url` varchar(255) NOT NULL,
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		PRIMARY KEY `requested_url` (`requested_url`),
		KEY (`content_type`,`content_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Rename another Plugin Setting
); 	revision( 14890
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'image'
	WHERE name = 'overwrite_masthead_image'
_sql


//Drop the parent_id column from the plugin_instances table
);	revision( 14940
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP COLUMN `parent_id`
_sql


//Change how the index for Wireframe modules works slightly, to include Plugin Id
);	revision( 14950
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP INDEX `content_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD INDEX `wireframe_instance` (`content_id`, `content_type`, `content_version`, `slot_name`, `plugin_id`)
_sql


//Add template_id into the plugin_slot_theme_link table...
);	revision( 15080
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD COLUMN `template_id` int(10) unsigned NOT NULL default 0
	FIRST
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	ADD PRIMARY KEY (`template_id`,`skin_id`,`slot_name`,`nest`,`plugin_id`,`instance_id`)
_sql


//...then migrate the data
);	revision( 15090
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	SELECT t.template_id, s.id, pstl.slot_name, pstl.nest, pstl.plugin_id, pstl.instance_id, pstl.theme
	FROM `[[DB_NAME_PREFIX]]plugin_slot_theme_link` AS pstl
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON pstl.skin_id = s.id
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON s.family_name = t.family_name
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	WHERE template_id = 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	MODIFY COLUMN `template_id` int(10) unsigned NOT NULL
_sql


//Add an index to the filename column
);	revision( 15144
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD KEY (`filename`)
_sql


//Create a new caching table to cache Content for Search Purposes
);	revision( 15145
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_cache`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]content_cache` (
		`content_id` int(10) unsigned NOT NULL default '0',
		`content_type` varchar(20) NOT NULL default '',
		`content_version` int(10) unsigned NOT NULL default '0',
		`text` mediumtext,
		FULLTEXT KEY (`text`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Drop the full-text key on the value column of the plugin_settings table for the dev sites that had it
//(This won't do anything for most migrations as that update has since been removed.)
);	revision( 15150
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	DROP KEY `value_2`
_sql


//Add a last_modified_datetime column to the versions table
);	revision( 15220
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `last_modified_datetime` datetime default NULL
	AFTER `last_author_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD KEY (`last_modified_datetime`)
_sql

//Fixed a bug where the hidden/published columns of the versions table were set for draft Content Items
); 	revision( 15230
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]versions` AS v
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON v.id = c.id
	  AND v.type = c.type
	  AND v.version > c.visitor_version
	  AND c.visitor_version != 0
	SET v.publisher_id = 0,
		v.published_datetime = NULL
_sql


//All filename columns upper to varchar 255-s
);	revision( 15320
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_file_content_link`
	MODIFY COLUMN `filename` varchar(255) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `filename` varchar(255) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	MODIFY COLUMN `filename` varchar(255) NOT NULL default ''
_sql


//Handle some core table renames in zenario 6
);	revision( 15450
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_dependencies`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_dependencies`
	CHANGE COLUMN `plugin_name` `module_name` varchar(255) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_dependencies`
	CHANGE COLUMN `plugin_class_name` `module_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	CHANGE COLUMN `plugin_class_name` `module_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	CHANGE COLUMN `plugin_name` `module_name` varchar(255) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	CHANGE COLUMN `plugin_class` `module_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	CHANGE COLUMN `plugin_name` `module_name` varchar(255) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	CHANGE COLUMN `plugin_class` `module_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	CHANGE COLUMN `plugin_class` `module_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	CHANGE COLUMN `plugin_id` `module_id` int(10) unsigned NOT NULL
_sql

);	revision( 15500
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]slot_swatch_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_slot_theme_link`
	RENAME TO `[[DB_NAME_PREFIX]]slot_swatch_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]module_dependencies`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_dependencies`
	RENAME TO `[[DB_NAME_PREFIX]]module_dependencies`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	CHANGE COLUMN `dependency_plugin_class_name` `dependency_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	CHANGE COLUMN `suppresses_plugin_class` `suppresses_module_class_name` varchar(200) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	CHANGE COLUMN `related_plugin_themes` `related_swatches` varchar(255) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	CHANGE COLUMN `required_plugin_id` `required_module_id` int(10) unsigned NOT NULL default 0
_sql


//Add a uses_wireframes column to the modules table
);	revision( 15530
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `uses_wireframes` tinyint(1) NOT NULL default 0
	AFTER `uses_instances`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD KEY (`uses_wireframes`)
_sql


//Change the key of the Email Templates table
);	revision( 15588
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD COLUMN `code` varchar(255)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates`
	SET `code` = `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	CHANGE COLUMN `code` `code` varchar(255) NOT NULL UNIQUE AFTER `id`
_sql


//Increase size of name field on site_settings table from 50 to 255
);	revision( 15680
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	MODIFY COLUMN `name` varchar(255) NOT NULL default ''
_sql


//Modify the foreign key type column
);	revision( 15710
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	CHANGE COLUMN `foreign_key_type` `foreign_key_char` varchar(255) NOT NULL default ''
_sql

);	revision( 15715
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX `foreign_key_char` (`foreign_key_to`, `foreign_key_char`)
_sql


//Convert all of the Plugin Settings from zenario 5 that used to let you overwrite phrases to the new system
);	revision( 15797
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	   ON vp.module_class_name IN ('zenario_contact_form', 'zenario_content_list', 'zenario_plugin_nest')
	  AND vp.code = ps.value
	
	SET ps.name =
		IF (ps.name = 'heading', '%_HEADING%',
		IF (ps.name = 'Heading', '%_HEADING%',
		IF (ps.name = 'title_with_content', '%_HEADING%',
		IF (ps.name = 'title_without_content', '%_HEADING_NO_ITEMS%',
		IF (ps.name = 'more_link_text', '%_MORE%',
		IF (ps.name = 'title', '%_HEADING%',
			ps.name))))))
	
	WHERE vp.code IS NULL
	  AND ps.nest = 0
	  AND m.name IN (
		'zenario_contact_form_v1',
		'zenario_content_list_v1', 'zenario_content_list_probusiness_v1', 'zenario_content_list_enterprise_v1',
		'zenario_plugin_nest_v1', 'zenario_plugin_tabbed_nest_v1', 'zenario_plugin_nest_probusiness_v1',
		'zenario_slideshow_v1', 'zenario_slideshow_probusiness_v1')
	  AND ps.name IN (
		'title', 'heading', 'Heading',
		'title_with_content', 'title_without_content', 'more_link_text')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	   ON vp.module_class_name IN ('zenario_contact_form', 'zenario_content_list', 'zenario_plugin_nest')
	  AND vp.code = ps.value
	
	SET ps.name =
		IF (ps.name = 'heading', '%_HEADING%',
		IF (ps.name = 'Heading', '%_HEADING%',
		IF (ps.name = 'title_with_content', '%_HEADING%',
		IF (ps.name = 'title_without_content', '%_HEADING_NO_ITEMS%',
		IF (ps.name = 'more_link_text', '%_MORE%',
		IF (ps.name = 'title', '%_HEADING%',
			ps.name))))))
	
	WHERE vp.code IS NULL
	  AND ps.nest != 0
	  AND m.name IN (
		'zenario_contact_form_v1',
		'zenario_content_list_v1', 'zenario_content_list_probusiness_v1', 'zenario_content_list_enterprise_v1',
		'zenario_plugin_nest_v1', 'zenario_plugin_tabbed_nest_v1', 'zenario_plugin_nest_probusiness_v1',
		'zenario_slideshow_v1', 'zenario_slideshow_probusiness_v1')
	  AND ps.name IN (
		'title', 'heading', 'Heading',
		'title_with_content', 'title_without_content', 'more_link_text')
_sql


//Add a download_now option to the Menu Items table
);	revision( 15890
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `download_now` tinyint(1) NOT NULL default 0
	AFTER `content_type`
_sql


//Add an archived column to the Files table
);	revision( 15910
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `archived` tinyint(1) NOT NULL default 0
	AFTER `created_datetime`
_sql


//Add columns for URLs to the Spare Alias table
);	revision( 15915
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD COLUMN `target_loc` enum('int','ext') NOT NULL default 'int'
	AFTER `alias`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD COLUMN `ext_url` varchar(255) NOT NULL default ''
	AFTER `content_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD INDEX (`target_loc`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD INDEX (`ext_url`)
_sql


//Add date created column to the Spare Alias table
);	revision( 15920
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD COLUMN `created_datetime` datetime default NULL
	AFTER `ext_url`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	ADD INDEX (`created_datetime`)
_sql


//Rename yet another Plugin Setting
); 	revision( 15925
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'category'
	WHERE name = 'hierarchical_categories'
_sql


//Add some missing indexes to try and speed up the Plugin Instances panel
);	revision( 15980
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD INDEX (`module_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	ADD INDEX (`template_id`,`slot_name`)
_sql


//For existing zenario 5 sites migrating to zenario 6, update the visitor_version of any non-public Content Items to 0
//to mark that they are not viewable by Visitors
); 	revision( 15995
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content`
	SET visitor_version = 0
	WHERE status in ('first_draft','hidden_with_draft','trashed_with_draft','hidden','trashed','deleted')
_sql


//Change Site Settings from using a blog to using a text field
); 	revision( 16035
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	CHANGE COLUMN `value` `value_old` blob
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	ADD COLUMN `value` mediumtext
	AFTER `name`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		`value` = CONVERT(`value_old` USING utf8)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	DROP COLUMN `value_old`
_sql


//Drop unused columns from various tables
); 	revision( 16036
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	DROP COLUMN `allow_create_msg`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	DROP COLUMN `allow_reply_msg`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	DROP COLUMN `extranet_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	DROP COLUMN `extranet_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_varchar1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_varchar2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_varchar3`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_varchar4`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_varchar5`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_text1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_blob1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_date1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_date2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_datetime1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_datetime2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_time1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_time2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_enum1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_enum2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_int1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_int2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_float1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `spare_float2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `consent1`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `consent2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `keep_in_album`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP COLUMN `keep_in_album`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	DROP COLUMN `use_custom_framework`
_sql


//Drop the old countries table
);	revision( 16037
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]countries`
_sql


//Drop unused columns from various tables
); 	revision( 16040
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `feature_plugin`
_sql


//Drop unused columns from the Menu Items table...
); 	revision( 16209
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	DROP COLUMN `mode`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	DROP COLUMN `microsite_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	DROP COLUMN `parent_microsite_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	DROP COLUMN `microsite_gateway`
_sql

//...then add a new column for CSS classname
);	revision( 16210
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `css_class` varchar(50) NOT NULL default ''
	AFTER `rel_tag`
_sql


//Update the format of the URL in the site_disabled_message site setting
);	revision( 16235
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings` SET
		value = REPLACE(value, 'zenario/admin/organizer.php', '[[admin_link]]')
	WHERE name = 'site_disabled_message'
_sql


//Add a second new column to the Menu Items table
);	revision( 16240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD COLUMN `descriptive_text` mediumtext default ''
	AFTER `css_class`
_sql


//Add a few missing indexes to the Menu Nodes table
);	revision( 16450
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD INDEX (`active`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD INDEX (`invisible`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	ADD INDEX (`section_id`)
_sql


//Change how the scope of Swatches works in 6.0.2
//We need to completely change how Swatches are stored
//Also, we need to add auto-increments to the placement linking tables
//We'll do both by making new tables and copying the data into them
);	revision( 16500
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]new_inst_item_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]new_inst_item_link` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`module_id` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL default '0',
		`content_id` int(10) unsigned NOT NULL,
		`content_type` varchar(20) NOT NULL,
		`content_version` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY  (`content_id`,`content_type`,`content_version`,`slot_name`),
		KEY `instance_id` (`instance_id`,`content_type`),
		KEY `slot_name` (`instance_id`,`slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]new_inst_item_link` (
		`module_id`,
		`instance_id`,
		`content_id`,
		`content_type`,
		`content_version`,
		`slot_name`
	) SELECT
		`module_id`,
		`instance_id`,
		`content_id`,
		`content_type`,
		`content_version`,
		`slot_name`
	FROM `[[DB_NAME_PREFIX]]plugin_inst_item_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]new_inst_temp_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]new_inst_temp_link` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`module_id` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL default '0',
		`family_name` varchar(50) NOT NULL,
		`template_id` int(10) unsigned NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY  (`family_name`,`template_id`,`slot_name`),
		KEY `slot_name` (`instance_id`,`slot_name`),
		KEY `template_id` (`template_id`,`slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]new_inst_temp_link` (
		`module_id`,
		`instance_id`,
		`family_name`,
		`template_id`,
		`slot_name`
	) SELECT
		`module_id`,
		`instance_id`,
		`family_name`,
		`template_id`,
		`slot_name`
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]new_inst_temp_fam_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]new_inst_temp_fam_link` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`module_id` int(10) unsigned NOT NULL,
		`instance_id` int(10) unsigned NOT NULL default '0',
		`family_name` varchar(50) NOT NULL,
		`slot_name` varchar(100) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY  (`family_name`,`slot_name`),
		KEY `slot_name` (`instance_id`,`slot_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]new_inst_temp_fam_link` (
		`module_id`,
		`instance_id`,
		`family_name`,
		`slot_name`
	) SELECT
		`module_id`,
		`instance_id`,
		`family_name`,
		`slot_name`
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]new_swatch_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]new_swatch_link` (
		`placement_id` int(10) unsigned NOT NULL,
		`placement_type` enum('item','template','template_family','nested_plugin') NOT NULL,
		`skin_id` int(10) unsigned NOT NULL,
		`theme` varchar(50) NOT NULL,
		PRIMARY KEY  (`placement_type`,`placement_id`,`skin_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Remove the old linking tables and add the new ones back in their place by renaming them
);	revision( 16510
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]new_inst_temp_fam_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]new_inst_temp_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]new_inst_item_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_inst_item_link`
_sql


//Start converting the data in the old slot_swatch_link table to the new one
//Where the conversion may be ambiguous (e.g. the old system only matched Templates, whereas the new
//system matches Items, Templates and Template Families) we'll use the number of Content Items that
//use that Template to decide which values should be chosen.
);	revision( 16520
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]new_swatch_link`
	SELECT l.id, 'template_family', s.skin_id, s.theme
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.family_name = l.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]slot_swatch_link` AS s
	   ON s.template_id = t.template_id
	  AND s.module_id = l.module_id
	  AND s.instance_id = l.instance_id
	  AND s.slot_name = l.slot_name
	  AND s.nest = 0
	LEFT JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.template_id = t.template_id
	LEFT JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = v.id
	  AND c.type = v.type
	  AND c.visitor_version = v.version
	GROUP BY l.id, s.skin_id, s.theme
	ORDER BY COUNT(c.tag_id) DESC
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]new_swatch_link`
	SELECT l.id, 'template', s.skin_id, s.theme
	FROM `[[DB_NAME_PREFIX]]plugin_inst_temp_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]slot_swatch_link` AS s
	   ON s.template_id = l.template_id
	  AND s.module_id = l.module_id
	  AND s.instance_id = l.instance_id
	  AND s.slot_name = l.slot_name
	  AND s.nest = 0
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]new_swatch_link`
	SELECT l.id, 'item', s.skin_id, s.theme
	FROM `[[DB_NAME_PREFIX]]plugin_inst_item_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = l.content_id
	  AND v.type = l.content_type
	  AND v.version = l.content_version
	INNER JOIN `[[DB_NAME_PREFIX]]slot_swatch_link` AS s
	   ON s.template_id = v.template_id
	  AND s.module_id = l.module_id
	  AND s.instance_id = l.instance_id
	  AND s.slot_name = l.slot_name
	  AND s.nest = 0
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]new_swatch_link`
	SELECT l.id, 'nested_plugin', s.skin_id, s.theme
	FROM `[[DB_NAME_PREFIX]]nested_plugins` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]slot_swatch_link` AS s
	   ON s.nest = l.id
_sql


//Remove the old table and add the new one back in its place by renaming it
);	revision( 16530
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]slot_swatch_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]new_swatch_link`
	RENAME TO `[[DB_NAME_PREFIX]]slot_swatch_link`
_sql


//Drop an unused table from older versions of zenario
);	revision( 16545
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]resized_image_cache`
_sql


//Rename some tables that had names which were against our coding practice
);	revision( 16546
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]content_types`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]contenttypes`
	RENAME TO `[[DB_NAME_PREFIX]]content_types`
_sql


);	revision( 16547
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_nodes`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menuitems`
	RENAME TO `[[DB_NAME_PREFIX]]menu_nodes`
_sql


//Rename the Plugin linking tables, as they're now for Wireframe Plugins as well as Reusable Plugins
);	revision( 16548
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_item_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_item_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_item_link`
_sql


);	revision( 16549
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_temp_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_temp_link`
_sql


);	revision( 16550
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_temp_fam_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_inst_temp_fam_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_temp_fam_link`
_sql


//Forcebly run the Menu Vertical Module (and the Menu Vertical Pro Module as well if the Menu Pro Module is installed)
);	revision( 16640
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` SET
		`name` = 'zenario_menu_vertical_v1',
		`class_name` = 'zenario_menu_vertical',
		`display_name` = 'Menu (Vertical)',
		`status` = '_ENUM_RUNNING'
	ON DUPLICATE KEY UPDATE
		`status` = '_ENUM_RUNNING'
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]modules` (
		`name`,
		`class_name`,
		`display_name`,
		`status`
	) SELECT
		'zenario_menu_vertical_pro_v1',
		'zenario_menu_vertical_pro',
		'Menu (Vertical) Pro',
		'_ENUM_RUNNING'
	FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `name` = 'zenario_menu_pro_v1'
	ON DUPLICATE KEY UPDATE
		`status` = '_ENUM_RUNNING'
_sql


//Add a enable_summary_auto_update column to the Content Types table
);	revision( 16660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `enable_summary_auto_update` tinyint(1) NOT NULL default 0
	AFTER `tag_prefix`
_sql


//Force visitor phrases into the system for the Banner Plugin, for existing sites that are upgrading
//(New sites won't need this as this will be done normally when adding a language)
);	revision( 16690
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]visitor_phrases` (
		code,
		language_id,
		module_class_name,
		local_text
	) SELECT
		'_FIND_OUT_MORE',
		id,
		'zenario_banner',
		''
	FROM `[[DB_NAME_PREFIX]]languages`
_sql


//Update the tab title column for Nested Plugins to cover names for eggs
);  revision( 16700
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CHANGE COLUMN `tab_title` `name_or_title` varchar(250) NOT NULL default ''
_sql

//Populate the column with the display names of Plugins
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`  AS np
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET np.name_or_title = m.display_name
_sql

//Straight away convert any Banner Plugins to the new system
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`  AS np
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	  AND m.class_name = 'zenario_banner'
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = np.instance_id
	  AND ps.nest = np.id
	  AND ps.name = 'title'
	SET np.name_or_title = SUBSTR(ps.value, 1, 255)
	WHERE ps.value IS NOT NULL
	  AND ps.value != ''
_sql


//Start recording the time a Content Item was locked on
); 	revision( 16770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	ADD COLUMN `locked_datetime` datetime DEFAULT NULL
	AFTER `lock_owner_id`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` SET
		`locked_datetime` = NOW()
	WHERE `lock_owner_id` != 0
_sql


//Add a publish column to the special pages to create them straight away
); 	revision( 16840
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	ADD COLUMN `publish` tinyint(1) unsigned NOT NULL default 0
	AFTER `create_lang_equiv_content`
_sql


//Drop a column from the groups table that was missed earier, add a missing index
); 	revision( 16870
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	DROP COLUMN `consent3`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD INDEX (`title`)
_sql


//Add a format column to the plugin settings
); 	revision( 16910
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD COLUMN `format` enum('empty', 'text', 'html', 'translatable_text', 'translatable_html') NOT NULL default 'text'
	AFTER `is_content`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	ADD INDEX (`format`)
_sql

//Attempt to prepopulate it to either text or translatable_text
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		format = 'translatable_text'
	WHERE name IN ('title', 'text')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		format = 'translatable_text'
	WHERE name  = 'html'
	  AND is_content = 'version_controlled_content'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` SET
		format = 'translatable_html'
	WHERE SUBSTR(name, 1, 1) = '%'
_sql




//
// Change the table structure for Equivalences and Menu Nodes in zenario 6.0.3
//

); 	revision( 17032
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]categories`
	CHANGE COLUMN `landing_page_content_id` `landing_page_equiv_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	CHANGE COLUMN `content_id` `equiv_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	CHANGE COLUMN `content_id` `equiv_id` int(10) unsigned unsigned NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	ADD COLUMN `equiv_id` int(10) unsigned default NULL
	AFTER `tag_id`
_sql


//Remove the lang_equiv_content table, and convert it's information to a new column on the content table
); 	revision( 17033

//Firstly, where a Content Item is in the default language, its equiv_id should be its content_id
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	SET   c.equiv_id = c.id
	WHERE c.equiv_id IS NULL
	  AND c.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Secondly, where a Content Item is not in the default language but is chained to one that is, its equiv_id should be that item's content id
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	INNER JOIN `[[DB_NAME_PREFIX]]lang_equiv_content` AS lec_secondary
	   ON lec_secondary.content_id = c.id
	  AND lec_secondary.content_type = c.type
	INNER JOIN `[[DB_NAME_PREFIX]]lang_equiv_content` AS lec_primary
	   ON lec_primary.id = lec_secondary.id
	SET   c.equiv_id = lec_primary.content_id
	WHERE c.equiv_id IS NULL
	  AND lec_primary.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Thirdly, where a Content Item is in a chain but the default language is not involved, its equiv_id should be set to one of the items content_ids
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	INNER JOIN `[[DB_NAME_PREFIX]]lang_equiv_content` AS lec1
	   ON lec1.content_id = c.id
	  AND lec1.content_type = c.type
	SET   c.equiv_id = (SELECT MIN(lec2.content_id) FROM `[[DB_NAME_PREFIX]]lang_equiv_content` AS lec2 WHERE lec2.id = lec1.id)
	WHERE c.equiv_id IS NULL
_sql

//Finally, add equiv_ids for Content Items that had no chained equivalents
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content` AS c
	SET   c.equiv_id = c.id
	WHERE c.equiv_id IS NULL
_sql

//Remove the equivs table, which has now been completely replaced by the new column on the content table
); 	revision( 17034
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]lang_equiv_content`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	MODIFY COLUMN `equiv_id` int(10) unsigned NOT NULL
_sql


//Create a new menu_text table
); 	revision( 17035
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_text`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]menu_text` (
		`menu_id` int(10) unsigned NOT NULL,
		`language_id` varchar(10) NOT NULL DEFAULT 'en',
		`name` varchar(255) NOT NULL DEFAULT '',
		`ext_url` varchar(255) NOT NULL DEFAULT '',
		`descriptive_text` mediumtext,
		PRIMARY KEY (`menu_id`, `language_id`),
		KEY (`language_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Populate the menu_text table
); 	revision( 17036

//Update the Menu Node table to point to equivs where possible
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_nodes` AS m
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON m.equiv_id = c.id
	  AND m.content_type = c.type
	  AND m.target_loc = 'int'
	SET m.equiv_id = c.equiv_id
_sql

//Add text for Menu Nodes from the menu_nodes table that are in the primary language
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]menu_text`
	SELECT
		prim.id,
		prim.language_id,
		prim.name,
		prim.ext_url,
		prim.descriptive_text
	FROM `[[DB_NAME_PREFIX]]menu_nodes` AS prim
	WHERE prim.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Update all parent ids in the Menu Node table to point to menu ids for Menu Nodes in the primary language where possible, to aid migration
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_nodes` AS sec_child
	INNER JOIN `[[DB_NAME_PREFIX]]menu_nodes` AS sec
	   ON sec_child.section_id = sec.section_id
	  AND sec_child.parent_id = sec.id
	  AND sec_child.language_id = sec.language_id
	INNER JOIN `[[DB_NAME_PREFIX]]menu_nodes` AS prim
	   ON sec.section_id = prim.section_id
	  AND sec.equiv_id = prim.equiv_id
	  AND sec.content_type = prim.content_type
	  AND sec.redundancy = prim.redundancy
	  AND sec.target_loc = 'int'
	  AND prim.target_loc = 'int'
	  AND prim.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
	SET sec_child.parent_id = prim.id
	WHERE sec_child.language_id != IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Attempt to add text for Menu Nodes for Content Items are not in the primary language, going by equivalences
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]menu_text`
	SELECT
		prim.id,
		sec.language_id,
		sec.name,
		sec.ext_url,
		sec.descriptive_text
	FROM `[[DB_NAME_PREFIX]]menu_nodes` AS sec
	INNER JOIN `[[DB_NAME_PREFIX]]menu_nodes` AS prim
	   ON sec.section_id = prim.section_id
	  AND sec.equiv_id = prim.equiv_id
	  AND sec.content_type = prim.content_type
	  AND sec.redundancy = prim.redundancy
	  AND sec.target_loc = 'int'
	  AND prim.target_loc = 'int'
	  AND prim.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
	WHERE sec.language_id != IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Attempt to add text for other Menu Nodes that not in the primary language, where we can link them by parent and ordinal
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]menu_text`
	SELECT
		prim.id,
		sec.language_id,
		sec.name,
		sec.ext_url,
		sec.descriptive_text
	FROM `[[DB_NAME_PREFIX]]menu_nodes` AS sec
	INNER JOIN `[[DB_NAME_PREFIX]]menu_nodes` AS prim
	   ON sec.section_id = prim.section_id
	  AND sec.ordinal = prim.ordinal
	  AND sec.parent_id = prim.parent_id
	  AND prim.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
	WHERE sec.language_id != IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Attempt to add text for Menu Nodes missing from the secondary language, but with an equiv set
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]menu_text`
	SELECT
		prim.id,
		c.language_id,
		v.title,
		'',
		''
	FROM `[[DB_NAME_PREFIX]]menu_nodes` AS prim
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON prim.equiv_id = c.id
	  AND prim.content_type = c.type
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = c.id
	  AND v.type = c.type
	  AND v.version = c.visitor_version
	WHERE prim.language_id = IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql

//Remove any Menu Nodes that are not in the Primary Language
); 	revision( 17037
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]menu_nodes`
	WHERE language_id != IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1)))
_sql


//Drop the remaining columns on the Menu Nodes table
); 	revision( 17038
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `ext_url`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `language_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `descriptive_text`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `active`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `html_content_alias`
_sql


//Fix a key that was incorrectly set in the 6.0.3 beta
); 	revision( 17150
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	DROP KEY `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	ADD UNIQUE KEY (`equiv_id`, `type`, `language_id`)
_sql


//Add fallback_to_default_equiv column
); 	revision( 17160
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `fallback_to_default_equiv` tinyint(1) NOT NULL default 0
	AFTER `content_type`
_sql


//Sort out the Core Visitor Phrases
//Move the Visitor Phrases for dates and Language Names out of the core and into the Common Features Module
); 	revision( 17200
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]visitor_phrases`
	SET `code` = 'zenario_common_features'
	WHERE `code` IN (
		'_MONTH_LONG_01', '_MONTH_SHORT_01', '_WEEKDAY_0',
		'_MONTH_LONG_02', '_MONTH_SHORT_02', '_WEEKDAY_1',
		'_MONTH_LONG_03', '_MONTH_SHORT_03', '_WEEKDAY_2',
		'_MONTH_LONG_04', '_MONTH_SHORT_04', '_WEEKDAY_3',
		'_MONTH_LONG_05', '_MONTH_SHORT_05', '_WEEKDAY_4',
		'_MONTH_LONG_06', '_MONTH_SHORT_06', '_WEEKDAY_5',
		'_MONTH_LONG_07', '_MONTH_SHORT_07', '_WEEKDAY_6',
		'_MONTH_LONG_08', '_MONTH_SHORT_08',
		'_MONTH_LONG_09', '_MONTH_SHORT_09',
		'_MONTH_LONG_10', '_MONTH_SHORT_10',
		'_MONTH_LONG_11', '_MONTH_SHORT_11',
		'_MONTH_LONG_12', '_MONTH_SHORT_12'
	)
	  AND module_class_name = ''
_sql


//Multilingual Weekday and Month names are now used for the date formats in the Site Settings; migrate anything using the old formats
); 	revision( 17205
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]site_settings`
	SET `value` = REPLACE(REPLACE(REPLACE(`value`, '%W', '[[_WEEKDAY_%w]]'), '%b', '[[_MONTH_SHORT_%m]]'), '%M', '[[_MONTH_LONG_%m]]')
	WHERE `name` IN ('vis_date_format_long', 'vis_date_format_med', 'vis_date_format_short')
_sql


//Remove the fallback_to_default_equiv column
); 	revision( 17330
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `fallback_to_default_equiv`
_sql


//Migrate the Banner Plugin Settings, as a result to the change in the "enlarge_image_in_fancy_box" option
); revision( 17415
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		nest,
		value,
		is_content,
		format
	)
	SELECT
		instance_id,
		'link_type',
		nest,
		'_ENLARGE_IMAGE',
		is_content,
		format
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name = 'enlarge_image_in_fancy_box'
	  AND value IS NOT NULL
	  AND value != ''
	  AND value != 0
	ON DUPLICATE KEY UPDATE
		value = '_ENLARGE_IMAGE'
_sql


//Change the module_dependencies table, wiping any data in it
	//old definition: `type` enum('dependency','inheritance','compatibility') NOT NULL
); 	revision( 17420
, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]module_dependencies`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	MODIFY COLUMN `type` enum('dependency','inherit_frameworks','include_javascript','inherit_settings','inherit_swatches','allow_upgrades') NOT NULL
_sql


); 	revision( 17533
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP COLUMN `review_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `instance_id` int(10) unsigned NOT NULL default 0
	AFTER `protect_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `nest` int(10) unsigned NOT NULL default 0
	AFTER `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`instance_id`, `nest`)
_sql


//Migrate Phrase overrides for Reusable Plugins to the new format
	//Part one: convert the data
); 	revision( 17535
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]visitor_phrases` (
		code,
		language_id,
		module_class_name,
		local_text,
		instance_id,
		nest
	) SELECT
		CONCAT(REPLACE(ps.name, '%', ''), '_PLG_', ps.instance_id),
		IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1))),
		m.vlp_class,
		ps.value,
		ps.instance_id,
		ps.nest
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	WHERE ps.name LIKE '\%%\%'
	  AND ps.name != '%custom_framework%'
	  AND ps.is_content = 'synchronized_setting'
	  AND ps.nest = 0
	ON DUPLICATE KEY UPDATE
		instance_id = VALUES(instance_id),
		nest = VALUES(nest)
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]visitor_phrases` (
		code,
		language_id,
		module_class_name,
		local_text,
		instance_id,
		nest
	) SELECT
		CONCAT(REPLACE(ps.name, '%', ''), '_PLG_', ps.instance_id),
		IFNULL((SELECT s.value FROM `[[DB_NAME_PREFIX]]site_settings` AS s WHERE s.name = 'default_language'), IF('[[DEFAULT_LANGUAGE]]', '[[DEFAULT_LANGUAGE]]', (SELECT lang.id FROM `[[DB_NAME_PREFIX]]languages` AS lang LIMIT 1))),
		m.vlp_class,
		ps.value,
		ps.instance_id,
		ps.nest
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	WHERE ps.name LIKE '\%%\%'
	  AND ps.name != '%custom_framework%'
	  AND ps.is_content = 'synchronized_setting'
	  AND ps.nest != 0
	ON DUPLICATE KEY UPDATE
		instance_id = VALUES(instance_id),
		nest = VALUES(nest)
_sql


//Convert the foreign_key_to from an enum into a varchar, so Modules can add into it
); 	revision( 17540
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_settings`
	MODIFY `foreign_key_to` varchar(64) default NULL
_sql


//Fix a bug where the skins table the same key defined twice
);	revision( 17590
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP KEY `family_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP KEY `family_name_2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD UNIQUE KEY `family_name` (`family_name`,`name`)
_sql


//Migrate Phrase overrides for Reusable Plugins to the new format
	//Part two: delete the old data in the old format
); 	revision( 17595
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name LIKE '\%%\%'
	  AND name != '%custom_framework%'
	  AND is_content = 'synchronized_setting'
_sql


//Add a "detect" option to the languages table
); 	revision( 17620
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `sync_assist` tinyint(1) NOT NULL default 0
	AFTER `detect`
_sql


//Remove any flags that may have the wrong names, so that they will be updated by the language_names.inc.php file
);	revision( 17730
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE code = '__LANGUAGE_FLAG_FILENAME__'
	  AND module_class_name = ''
	  AND language_id IN (
		'ar','bg','cs','da','el','en','en-us','es','fi','fr','gu',
		'hi','hu','it','ja','kr','nl','no','pl','pt-br','pt-eu','ro',
		'ru','sv','th','tr','uk','vi','zh-si','zh-tr','ur','de','cy')
_sql


//The default logic for Banner destinations has changed for multilingual sites
//In order not to change any functionality in the migration, we should add the "use_translation"
//option to any existing Reusable Banners if your site has more than one language
); 	revision( 17885
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings` (
		instance_id,
		name,
		nest,
		value,
		is_content,
		format,
		foreign_key_to,
		foreign_key_id,
		foreign_key_char,
		dangling_cross_references
	) SELECT
		ps.instance_id,
		'use_translation',
		ps.nest,
		1,
		ps.is_content,
		ps.format,
		NULL,
		0,
		'',
		'remove'
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN (SELECT COUNT(*) AS c from `[[DB_NAME_PREFIX]]languages`) AS l ON  l.c > 1
	WHERE ps.is_content = 'synchronized_setting'
	  AND ps.name = 'hyperlink_target'
_sql


); 	revision( 18250
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD KEY (`detect`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD KEY (`sync_assist`)
_sql


);	revision( 18449
,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value =
		IF (ps.value = 'SHORT', '_SHORT',
		IF (ps.value = 'MEDIUM', '_MEDIUM',
		IF (ps.value = 'LONG', '_LONG',
			ps.value)))
	WHERE ps.nest = 0
	  AND m.name = 'zenario_event_calendar_v1'
	  AND ps.name = 'date_format'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET ps.value =
		IF (ps.value = 'SHORT', '_SHORT',
		IF (ps.value = 'MEDIUM', '_MEDIUM',
		IF (ps.value = 'LONG', '_LONG',
			ps.value)))
	WHERE ps.nest != 0
	  AND m.name = 'zenario_event_calendar_v1'
	  AND ps.name = 'date_format'
_sql


//Add columns for storing which Plugins provide RSS feeds to the versions tab;e
); 	revision( 18570
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `rss_slot_name` varchar(100) NOT NULL default ''
	AFTER `publication_date`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `rss_nest` int(10) unsigned NOT NULL default 0
	AFTER `rss_slot_name`
_sql


//Alter the modules table, change the format of the status column. Also add some keys.
); 	revision( 18596
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `status` `old_status` enum('_ENUM_INSTALLED','_ENUM_INITIALISED','_ENUM_RUNNING','_ENUM_SUSPENDED') NOT NULL default '_ENUM_INSTALLED'
_sql

); 	revision( 18597
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN  `status` enum('module_not_initialized','module_running','module_suspended') NOT NULL default 'module_not_initialized'
	AFTER `old_status`
_sql

); 	revision( 18598
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET status = 'module_running'
	WHERE old_status = '_ENUM_RUNNING'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET status = 'module_suspended'
	WHERE old_status IN ('_ENUM_INITIALISED', '_ENUM_SUSPENDED')
_sql

); 	revision( 18599
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD INDEX (`uses_instances`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD INDEX (`nestable`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD INDEX (`status`)
_sql

); 	revision( 18600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `old_status`
_sql

); revision(18718
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases`
	SET
		local_text = replace(local_text,'min_massword_length','min_password_length')
	WHERE
			module_class_name = 'zenario_extranet_change_password' 
	AND code = '_ERROR_NEW_PASSWORD_LENGTH'
_sql


//Fix a bug where the mime_type column on the files table was too short
); 	revision( 18750
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `mime_type` varchar(128) NOT NULL
_sql


//Add columns to tables for head and foot slots
); 	revision( 18770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `head_html` mediumtext
	AFTER `skin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `head_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `head_overwrite` tinyint(1) unsigned NOT NULL default 0
	AFTER `head_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `foot_html` mediumtext
	AFTER `head_overwrite`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `foot_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `foot_overwrite` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_visitor_only`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `head_html` mediumtext
	AFTER `skin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `head_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `head_overwrite` tinyint(1) unsigned NOT NULL default 0
	AFTER `head_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `foot_html` mediumtext
	AFTER `head_overwrite`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `foot_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `foot_overwrite` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_visitor_only`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `head_html` mediumtext
	AFTER `skin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `head_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `foot_html` mediumtext
	AFTER `head_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `foot_visitor_only` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_html`
_sql


//Remove any Visitor Phrases that were not associated with a Module
);	revision( 18870
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]visitor_phrases`
	  SET module_class_name = 'zenario_common_features'
	WHERE module_class_name = ''
	  AND code IN('__LANGUAGE_FLAG_FILENAME__', '__LANGUAGE_LOCAL_NAME__')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]visitor_phrases`
	  SET module_class_name = 'zenario_common_features'
	WHERE module_class_name = ''
	  AND code LIKE '_CATEGORY_%'
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]visitor_phrases`
	  SET module_class_name = 'zenario_common_features'
	WHERE module_class_name = ''
	  AND code LIKE '_GROUP_%'
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE module_class_name = ''
_sql


//Add a new class_name column to the jobs table
); 	revision( 18895
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD COLUMN `manager_class_name` varchar(200) NOT NULL default ''
	AFTER `id`
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]jobs`
	  SET manager_class_name = 'zenario_scheduled_task_manager'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	MODIFY COLUMN `manager_class_name` varchar(200) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	ADD KEY (`manager_class_name`)
_sql


//Fix a bug where some older installations had the nested_plugins table created in the wrong charactset
); 	revision( 19120
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	CONVERT TO CHARACTER SET utf8
_sql


//Update the format of the download_now Setting, as used by Banner and Content List Plugins, and Menu Nodes in the core
//(I'm doing a mass update for Plugin Settings here, as only the Banner and Content List Modules (and Modules that extend them) use that setting name.)
);	revision( 19130
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	CHANGE COLUMN `download_now` `use_download_page` tinyint(1) NOT NULL default 0
_sql

);	revision( 19140

,<<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_nodes`
	SET use_download_page = IF(use_download_page = 1, 0, 1)
	WHERE content_type = 'document'
_sql

,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = IF(value = 1, 0, 1),
		name = 'use_download_page'
	WHERE name = 'download_now'
_sql


//Add two extra columns to tables for cookie consent for head and foot slots
); 	revision( 19200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `head_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `foot_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `foot_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `head_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `foot_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `foot_html`
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `head_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `foot_cc` enum('not_needed', 'needed', 'required') NOT NULL default 'not_needed'
	AFTER `foot_html`
_sql


//Add a 'core' column to the document_types table, so we can tell the difference between CMS and user-added types
); 	revision( 19210
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types`
	ADD COLUMN `custom` tinyint(1) NOT NULL default 1
	AFTER `mime_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_types`
	ADD INDEX (`custom`)
_sql


//Add a 'core' column to the document_types table, so we can tell the difference between CMS and user-added types
); 	revision( 19410
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins`
	SET name_or_title = CONCAT('[[', name_or_title, ']]')
	WHERE is_tab = 1
	  AND name_or_title LIKE '\_%'
_sql


//Drop the old admin roles/actions tables, if they were created
);	revision( 19470
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_roles`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_actions`
_sql


//Change the `usage` column from an enaum to a varchar, to allow custom types for Modules
); 	revision( 19500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `usage` varchar(64) NOT NULL
_sql

//Also add a 'shared" column for the new pool logic
); 	revision( 19510
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `shared` tinyint(1) NOT NULL default 0
	AFTER `usage`
_sql

//Change how the inline_file_content_link table works to include more than just content
); 	revision( 19520
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]inline_file_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]inline_file_link` (
		`file_id` int(10) unsigned NOT NULL,
		`filename` varchar(255) NULL default NULL,
		`foreign_key_to` varchar(64) NOT NULL,
		`foreign_key_id` int(10) unsigned NOT NULL default 0,
		`foreign_key_char` varchar(255) NOT NULL default '',
		`foreign_key_version` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY (`foreign_key_to`,`foreign_key_id`,`foreign_key_char`,`foreign_key_version`,`file_id`),
		KEY (`file_id`),
		KEY (`filename`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]inline_file_link`
	SELECT file_id, filename, 'content', content_id, content_type, content_version
	FROM `[[DB_NAME_PREFIX]]inline_file_content_link`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]inline_file_content_link`
_sql

//Set the "shared" option for existing inline images/animations based on whether they are associated with a Content Item/Library plugin or not
); 	revision( 19550
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	LEFT JOIN `[[DB_NAME_PREFIX]]inline_file_link` AS l
	   ON l.foreign_key_to = 'content'
	  AND l.file_id = f.id
	LEFT JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.foreign_key_to = 'file'
	  AND ps.foreign_key_id = f.id
	SET f.shared = 1
	WHERE f.`usage` = 'inline'
	  AND f.archived = 0
	  AND l.foreign_key_to IS NULL
	  AND ps.foreign_key_to IS NULL
_sql

//Remove the old archived column
); 	revision( 19555
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	DROP COLUMN `archived`
_sql

//Add some keys for image ids, to speed up the deleteUnusedInlineFile() function
); 	revision( 19560
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]users`
	ADD KEY (`image_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]groups`
	ADD KEY (`image_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`image_id`)
_sql


//Add a extract column to the content_cache table
);	revision( 19650
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD COLUMN `extract` mediumtext
	AFTER `text`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD FULLTEXT KEY (`extract`)
_sql


//User characteristic table changes
);	revision( 19660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	ADD COLUMN `temp_type` varchar(8) NOT NULL default ''
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics`
	SET temp_type = type
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	MODIFY `type` enum('list_single_select','list_multi_select','boolean','text','textarea') NOT NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_characteristics`
	SET type = 'list_single_select'
	WHERE temp_type = 'list'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	DROP COLUMN `temp_type`
_sql

);	revision( 19670
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	DROP KEY `user_id_2`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	DROP KEY `user_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]characteristic_user_link`
	ADD UNIQUE KEY `user_characteristic_value` (`user_id`,`characteristic_id`,`characteristic_value_id`)
_sql


//Add more columns to the files table for image working copies
);	revision( 19680
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `working_copy_width` smallint(5) unsigned default NULL
	AFTER `storekeeper_list_data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `working_copy_height` smallint(5) unsigned default NULL
	AFTER `working_copy_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `working_copy_data` mediumblob
	AFTER `working_copy_height`
_sql

);	revision( 19690
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_wc_width` smallint(5) unsigned default NULL
	AFTER `working_copy_data`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_wc_height` smallint(5) unsigned default NULL
	AFTER `thumbnail_wc_width`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `thumbnail_wc_data` mediumblob
	AFTER `thumbnail_wc_height`
_sql


);	revision( 19691
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	ADD COLUMN `group_id` int(10) unsigned NULL
_sql


//Change the name of the second working copy image
);	revision( 19790
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `thumbnail_wc_width` `working_copy_2_width` smallint(5) unsigned default NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `thumbnail_wc_height` `working_copy_2_height` smallint(5) unsigned default NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	CHANGE COLUMN `thumbnail_wc_data` `working_copy_2_data` mediumblob
_sql


//Add columns for alt tags, titles and popout titles to the files table
);	revision( 19990
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `alt_tag` text
	AFTER `height`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `title` text
	AFTER `alt_tag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD COLUMN `floating_box_title` text
	AFTER `title`
_sql

//Attempt to automatically set some alt tags/titles from any Banners or Picture Content Items that have been created
);	revision( 20000
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS psl
	   ON psl.name = 'image'
	  AND psl.foreign_key_to = 'file'
	  AND psl.foreign_key_id = f.id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = psl.instance_id
	  AND ps.nest = psl.nest
	  AND ps.name = 'alt_tag'
	  AND ps.value IS NOT NULL
	  AND ps.value != ''
	  AND ps.value != f.filename
	SET f.alt_tag = ps.value
	WHERE f.mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS psl
	   ON psl.name = 'image'
	  AND psl.foreign_key_to = 'file'
	  AND psl.foreign_key_id = f.id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	   ON ps.instance_id = psl.instance_id
	  AND ps.nest = psl.nest
	  AND ps.name = 'title'
	  AND ps.value IS NOT NULL
	  AND ps.value != ''
	  AND ps.value != f.filename
	SET f.title = ps.value
	WHERE f.mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]files` AS f
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.file_id = f.id
	  AND v.title IS NOT NULL
	  AND v.title != ''
	  AND v.title != f.filename
	  AND v.title != v.filename
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = v.id
	  AND c.type = v.type
	  AND c.admin_version = v.version
	SET f.alt_tag = v.title,
		f.title = v.title
	WHERE f.mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
_sql

//Set the alt tag to the filename for any images that we couldn't pull from a Banner Plugin or a Picture Content Item
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]files` AS f
	SET f.alt_tag = f.filename
	WHERE f.mime_type IN ('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg')
	  AND f.alt_tag IS NULL
_sql


//Add a detect_lang_codes column to the languages table
);	revision( 20050
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `detect_lang_codes` varchar(100) NOT NULL default ''
	AFTER `detect`
_sql


//Update language id to be max 15 characters, rather than max 5 characters
);	revision( 20060
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	MODIFY COLUMN `id` varchar(15) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	MODIFY COLUMN `language_id` varchar(15) NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	MODIFY COLUMN `language_id` varchar(15) NOT NULL default ''
_sql


//Rename some language names
);	revision( 20070
, <<<_sql
	DELETE FROM  `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE (code, language_id, module_class_name) IN (
		('__LANGUAGE_LOCAL_NAME__', 'en-us', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'hi', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'gu', 'zenario_common_features')
	)
_sql

//Fix some incorrect language codes
	//English (British) should be en-gb, not en 
	//Korean should be ko, not kr
	//Chinese (Simplified) should be zh-hans, not zh-si
	//Chinese (Traditional) should be zh-hant, not zh-tr
//However don't make any changes if a language is already enabled!
, <<<_sql
	DELETE vp.*
	FROM  `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	LEFT JOIN `[[DB_NAME_PREFIX]]languages` AS l
	   ON vp.language_id = l.id
	WHERE l.id IS NULL
	  AND (vp.code, vp.language_id, vp.module_class_name) IN (
		('__LANGUAGE_FLAG_FILENAME__', 'en', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'en', 'zenario_common_features'),
		('__LANGUAGE_FLAG_FILENAME__', 'kr', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'kr', 'zenario_common_features'),
		('__LANGUAGE_FLAG_FILENAME__', 'zh-si', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'zh-si', 'zenario_common_features'),
		('__LANGUAGE_FLAG_FILENAME__', 'zh-tr', 'zenario_common_features'),
		('__LANGUAGE_LOCAL_NAME__', 'zh-tr', 'zenario_common_features')
	)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	LEFT JOIN `[[DB_NAME_PREFIX]]languages` AS l
	   ON vp.language_id = l.id
	SET vp.language_id = 'en-gb'
	WHERE l.id IS NULL
	  AND (vp.language_id, vp.module_class_name) IN (
		('en', 'zenario_common_features'),
		('en', 'zenario_common_features')
	)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	LEFT JOIN `[[DB_NAME_PREFIX]]languages` AS l
	   ON vp.language_id = l.id
	SET vp.language_id = 'ko'
	WHERE l.id IS NULL
	  AND (vp.language_id, vp.module_class_name) IN (
		('kr', 'zenario_common_features'),
		('kr', 'zenario_common_features')
	)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	LEFT JOIN `[[DB_NAME_PREFIX]]languages` AS l
	   ON vp.language_id = l.id
	SET vp.language_id = 'zh-hans'
	WHERE l.id IS NULL
	  AND (vp.language_id, vp.module_class_name) IN (
		('zh-si', 'zenario_common_features'),
		('zh-si', 'zenario_common_features')
	)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` AS vp
	LEFT JOIN `[[DB_NAME_PREFIX]]languages` AS l
	   ON vp.language_id = l.id
	SET vp.language_id = 'zh-hant'
	WHERE l.id IS NULL
	  AND (vp.language_id, vp.module_class_name) IN (
		('zh-tr', 'zenario_common_features'),
		('zh-tr', 'zenario_common_features')
	)
_sql


//Remove ".gif" from the flag filename phrases - it's now just assumed that they're all .gifs
);	revision( 20075
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases` SET
		local_text = REPLACE(local_text, '.gif', '')
	WHERE code = '__LANGUAGE_FLAG_FILENAME__'
	  AND module_class_name = 'zenario_common_features'
_sql


//Drop the local name and flag filename phrases from the languages table
//These should always be coming from Visitor Phrases
);	revision( 20080
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `language_local_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	DROP COLUMN `flag_file_name`
_sql


);  revision( 20252
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	ADD `last_sent` datetime
	AFTER `modified_by_id`
_sql


//Adjust the menu_sections table, adding a primary key
);	revision( 20390
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_sections`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]menu_sections` (
		`id` smallint(10) unsigned NOT NULL auto_increment,
		`section_name` varchar(20) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY  (`section_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]menu_sections`(`section_name`)
	VALUES ('Main'), ('Footer')
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]menu_sections`(`section_name`)
	SELECT DISTINCT `section_id`
	FROM `[[DB_NAME_PREFIX]]menu_nodes`
	WHERE section_id != ''
	ORDER BY section_id
_sql


//Recreate the table to cache menu parent/child relationships, using a numeric section id
); 	revision( 20395
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_hierarchy`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]menu_hierarchy` (
		`section_id` smallint(10) unsigned NOT NULL,
		`ancestor_id` int(10) unsigned NOT NULL,
		`child_id` int(10) unsigned NOT NULL,
		`separation` smallint(5) unsigned NOT NULL,
		PRIMARY KEY (`ancestor_id`,`child_id`),
		KEY `child_id` (`child_id`),
		KEY `separation` (`separation`),
		KEY `section_id` (`section_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Recreate the table to cache menu parent/child relationships, using a numeric section id
); 	revision( 20397
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `new_section_id` smallint(10) unsigned NOT NULL default 0
	AFTER `section_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD KEY (`new_section_id`)
_sql

); 	revision( 20398
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]menu_nodes` AS mn
	INNER JOIN `[[DB_NAME_PREFIX]]menu_sections` AS ms
	   ON mn.section_id = ms.section_name
	SET mn.new_section_id = ms.id
_sql

); 	revision( 20399
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP COLUMN `section_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	CHANGE COLUMN `new_section_id` `section_id` smallint(10) unsigned NOT NULL
_sql

); 	revision( 20400
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]menu_sections` AS ms
	   ON ps.name = 'menu_section'
	  AND ps.value = ms.section_name
	SET ps.value = ms.id,
		ps.foreign_key_to = 'menu_section',
		ps.foreign_key_id = ms.id
_sql


//Skins should no longer be selectable on a per-Content Item basis
	//There was a revision in 6.0.6 for developers where we completely dropped the skin_id column.
	//However this isn't exactly desirable, as we might want to query that column to see where the feature
	//had been used.
	//So I've removed that revision, and have added another revision to add the column back
	//(this is just so that any site that recieved the update has the same table structure as any other table).
	//Note that the updater will automatically ignore any errors that occur if this query is run on a database
	//that still has this column.
);	revision( 20440
//, <<<_sql
//	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
//	DROP COLUMN `skin_id`
//_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `skin_id` int(10) unsigned NOT NULL default 0
	AFTER `template_id`
_sql


//Create a table to store a list of TUIX files, and what's in them
); 	revision( 20500
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents` (
		`type` enum('admin_boxes', 'admin_toolbar', 'help', 'storekeeper') NOT NULL,
		`path` varchar(255) NOT NULL default '',
		`setting_group` varchar(255) NOT NULL default '',
		`module_name` varchar(255) NOT NULL,
		`filename` varchar(255) NOT NULL default '',
		PRIMARY KEY (`type`, `path`(80), `setting_group`(80), `module_name`(80), `filename`(80))
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add a foreign key for an image against a Menu Node
); 	revision( 20580
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
	AFTER `css_class`
_sql


//Add a new "in use" flag to the inline file link table; rather than deleting a file outright, the CMS should just update the flag.
); 	revision( 20600
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_file_link`
	ADD COLUMN `in_use` tinyint(1) NOT NULL default 0
	AFTER `foreign_key_version`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]inline_file_link`
	SET `in_use` = 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_file_link`
	ADD KEY (`in_use`)
_sql


//Remove the filename column from the inline_file_link table
); 	revision( 20610
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]inline_file_link`
	DROP COLUMN `filename`
_sql


//Start using the inline_file_link table for tracking Library plugin Images, rather than the Plugin Settings table
); 	revision( 20620
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]inline_file_link` (`file_id`, `foreign_key_to`)
	SELECT DISTINCT foreign_key_id, 'reusable_plugin'
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE is_content = 'synchronized_setting'
	  AND foreign_key_to = 'file'
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE is_content = 'synchronized_setting'
	  AND foreign_key_to = 'file'
	  AND name LIKE '~temporarily_linked_file~%'
_sql


//Add more columns to the Content Types table
);	revision( 20650
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `description_field` enum('optional', 'mandatory', 'hidden') NOT NULL default 'optional'
	AFTER `tag_prefix`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `keywords_field` enum('optional', 'mandatory', 'hidden') NOT NULL default 'optional'
	AFTER `description_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `summary_field` enum('optional', 'mandatory', 'hidden') NOT NULL default 'optional'
	AFTER `keywords_field`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `release_date_field` enum('optional', 'mandatory', 'hidden') NOT NULL default 'optional'
	AFTER `summary_field`
_sql


//Switch the "show_user_online_status" setting for Comments/Forums off; anyone who really wants this will need to switch it back on
); 	revision( 20660
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = ''
	WHERE name = 'show_user_online_status'
_sql


//Manually set some defaults in the Content Types table, as if the Content Types are already installed these defaults won't take effect
); 	revision( 20670
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_types`
	SET release_date_field = 'mandatory'
	WHERE content_type_id IN ('blog', 'news')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_types`
	SET release_date_field = 'hidden'
	WHERE content_type_id = 'event'
_sql


//Set some values for the site disabled messages, if they're not set already
); 	revision( 20700
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]site_settings`
	 (`name`, `value`)
	VALUES
	 ('site_enabled',''),
	 ('site_disabled_message','
		<p>A site is being built at this location.</p>
		<p><span class="x-small">If you are the Site Administrator please <a href="[[admin_link]]">click here</a> to manage your site.</span></p>'),
	 ('site_disabled_title','Welcome');
_sql


//Add one more column to the Content Types table
);	revision( 20770
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD COLUMN `writer_field` enum('optional', 'mandatory', 'hidden') NOT NULL default 'optional'
	AFTER `tag_prefix`
_sql


//IPs used to be stored as an integer. We should now store them as a string so we can handle Proxy lists and IPV6
);	revision( 20772
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	ADD COLUMN `new_ip` varchar(80) CHARACTER SET ascii NOT NULL default ''
	AFTER `ip`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	ADD KEY (`new_ip`)
_sql

);	revision( 20773
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]user_content_accesslog`
	SET new_ip = CONVERT(ip USING ascii)
	WHERE ip != 0
_sql

);	revision( 20774
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	DROP COLUMN `ip`
_sql

);	revision( 20775
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	CHANGE COLUMN `new_ip` `ip` varchar(80) CHARACTER SET ascii NOT NULL default ''
_sql


//Manually set some defaults in the Content Types table, as if the Content Types are already installed these defaults won't take effect
); 	revision( 20777
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]content_types`
	SET writer_field = 'hidden'
	WHERE content_type_id != 'blog'
_sql


//Adding writer_id and writer_name fields to versions table
); 	revision( 20778

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `writer_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `writer_name` varchar(255) NOT NULL default ''
_sql


//Migrate all of the Visitor Phrases for the Comments Module to the Anonymous Comments Module
); 	revision( 20800
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]visitor_phrases`
	SET module_class_name = 'zenario_anonymous_comments'
	WHERE module_class_name = 'zenario_comments'
_sql























		//					 //
		//  Changes for 6.1  //
		//					 //






//Remove Swatches and replace them with a new field on the Instance
); 	revision( 20900
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	MODIFY COLUMN `css_class` varchar(100) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]nested_plugins`
	ADD COLUMN `css_class` varchar(100) NOT NULL default ''
	AFTER `framework`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instances`
	ADD COLUMN `css_class` varchar(100) NOT NULL default ''
	AFTER `framework`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD COLUMN `css_class` varchar(100) NOT NULL default ''
	AFTER `skin_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `css_class` varchar(100) NOT NULL default ''
	AFTER `skin_id`
_sql

//Set all Plugins to use Swatch 1, in case they are not matched by a rule below
); 	revision( 20901
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		'1'
	)
_sql

//Update Swatch rules for Reusable Plugins placed at the Item Layer.
); 	revision( 20902
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = s.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_item_link` AS pil
	   ON pil.id = l.placement_id
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = pil.content_id
	  AND c.type = pil.content_type
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = pil.content_id
	  AND v.type = pil.content_type
	  AND v.version = pil.content_version
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.template_id = v.template_id
	  AND t.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	  ON pil.instance_id != 0
	 AND pil.instance_id = pi.id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'item'
_sql

//Update Swatch rules for Reusable Plugins placed at the Template Layer.
); 	revision( 20903
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = s.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_temp_link` AS ptl
	   ON ptl.id = l.placement_id
	  AND ptl.instance_id != 0
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.template_id = ptl.template_id
	  AND t.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ptl.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'template'
_sql

//Update Swatch rules for Reusable Plugins placed at the Template Family Layer.
); 	revision( 20904
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_temp_fam_link` AS ptfl
	   ON ptfl.id = l.placement_id
	  AND ptfl.instance_id != 0
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = ptfl.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	  AND s.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ptfl.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'template_family'
_sql

//Update Swatch rules for Wireframe Plugins placed at the Template Family Layer.
); 	revision( 20905
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_temp_fam_link` AS ptfl
	   ON ptfl.id = l.placement_id
	  AND ptfl.instance_id = 0
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = ptfl.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	  AND s.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.template_id = t.template_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.content_id = v.id
	  AND pi.content_type = v.type
	  AND pi.content_version = v.version
	  AND pi.slot_name = ptfl.slot_name
	  AND pi.module_id = ptfl.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'template_family'
_sql

//Update Swatch rules for Wireframe Plugins placed at the Template Layer.
); 	revision( 20906
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = s.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_temp_link` AS ptl
	   ON ptl.id = l.placement_id
	  AND ptl.instance_id = 0
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.template_id = ptl.template_id
	  AND t.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.template_id = t.template_id
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.content_id = v.id
	  AND pi.content_type = v.type
	  AND pi.content_version = v.version
	  AND pi.slot_name = ptl.slot_name
	  AND pi.module_id = ptl.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'template'
_sql

//Update Swatch rules for Wireframe Plugins placed at the Item Layer.
); 	revision( 20907
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	INNER JOIN `[[DB_NAME_PREFIX]]template_family` AS tf
	   ON tf.family_name = s.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_item_link` AS pil
	   ON pil.id = l.placement_id
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = pil.content_id
	  AND c.type = pil.content_type
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = pil.content_id
	  AND v.type = pil.content_type
	  AND v.version = pil.content_version
	INNER JOIN `[[DB_NAME_PREFIX]]templates` AS t
	   ON t.template_id = v.template_id
	  AND t.family_name = tf.family_name
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	  ON pil.instance_id = 0
	 AND pil.content_id = pi.content_id
	 AND pil.content_type = pi.content_type
	 AND pil.content_version = pi.content_version
	 AND pil.slot_name = pi.slot_name
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET pi.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		l.theme
	)
	WHERE l.placement_type = 'item'
_sql

//Update Swatch rules for Nested Plugins
//If there's no rule found, by default assume they've been using Swatch "1".
); 	revision( 20908
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]nested_plugins` AS np
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	LEFT JOIN  `[[DB_NAME_PREFIX]]slot_swatch_link` AS l
	   ON l.placement_id = np.id
	  AND l.placement_type = 'nested_plugin'
	LEFT JOIN `[[DB_NAME_PREFIX]]skins` AS s
	   ON s.id = l.skin_id
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d1
	   ON d1.type = 'inherit_swatches'
	  AND d1.module_class_name = m.class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d2
	   ON d2.type = 'inherit_swatches'
	  AND d2.module_class_name = d1.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d3
	   ON d3.type = 'inherit_swatches'
	  AND d3.module_class_name = d2.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d4
	   ON d4.type = 'inherit_swatches'
	  AND d4.module_class_name = d3.dependency_class_name
	LEFT JOIN `[[DB_NAME_PREFIX]]module_dependencies` AS d5
	   ON d5.type = 'inherit_swatches'
	  AND d5.module_class_name = d4.dependency_class_name
	SET np.css_class = CONCAT(
		IFNULL(d5.dependency_class_name,
			IFNULL(d4.dependency_class_name,
				IFNULL(d3.dependency_class_name,
					IFNULL(d2.dependency_class_name,
						IFNULL(d1.dependency_class_name, m.class_name
			))))),
		'__',
		IFNULL(l.theme, '1')
	)
	WHERE np.is_tab = 0
_sql

//Remove Swatches and replace them with a new field on the Instance
); 	revision( 20909
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `css_class` varchar(50) NOT NULL default ''
	AFTER `default_framework`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `default_theme`
_sql

); 	revision( 20920
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP COLUMN `related_swatches`
_sql

//Tidied up the inconsistent naming convention for variables that store CSS classes
);	revision( 20970
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `css_class` `css_class_name` varchar(200) NOT NULL default ''
_sql


//Add a new column to the content table to track how language codes should be displayed
); 	revision( 20990
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	ADD COLUMN `lang_code_in_url` enum('show', 'hide', 'default') NOT NULL default 'default'
	AFTER `alias`
_sql


//Rename all Module directories to remove the "_v1" prefix, so that the class name is now the same as the directory name.
//And remove the Module's "name" column, as this is now the same as the class name column
);	revision( 21040
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]jobs`
	DROP COLUMN `module_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	DROP COLUMN `module_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]signals`
	DROP COLUMN `module_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	CHANGE COLUMN `module_name` `module_class_name` varchar(200) NOT NULL
_sql


//Drop the frameworks_and_swatches table as it's not used anymore
); 	revision( 21050
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]frameworks_and_swatches`
_sql


//Change the module_dependencies table to remove the 'inherit_swatches' option, also wiping any data in it
); 	revision( 21060
, <<<_sql
	TRUNCATE TABLE `[[DB_NAME_PREFIX]]module_dependencies`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	MODIFY COLUMN `type` enum('dependency','inherit_frameworks','include_javascript','inherit_settings','allow_upgrades') NOT NULL
_sql


//Change the special pages table to use class names rather than ids
); 	revision( 21063
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	ADD COLUMN `module_class_name` varchar(200) NOT NULL default ''
_sql

); 	revision( 21066
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]special_pages` AS sp
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON sp.required_module_id = m.id
	SET sp.module_class_name = m.class_name
_sql

); 	revision( 21070
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	DROP COLUMN `required_module_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	ADD KEY (`module_class_name`)
_sql

); 	revision( 21191
, <<<_sql
	CREATE INDEX idx_characteristic_id ON  `[[DB_NAME_PREFIX]]user_characteristic_values`(characteristic_id)
_sql
, <<<_sql
	CREATE INDEX idx_characteristic_id ON  `[[DB_NAME_PREFIX]]characteristic_user_link`(characteristic_id)
_sql
, <<<_sql
	CREATE INDEX idx_characteristic_value_id ON  `[[DB_NAME_PREFIX]]characteristic_user_link`(characteristic_value_id)
_sql


//Add new `missing` columns to the Template Family/Skins tables
); 	revision( 21280
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD COLUMN `missing` tinyint(1) unsigned NOT NULL default 0
	AFTER `foot_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `missing` tinyint(1) unsigned NOT NULL default 0
	AFTER `name`
_sql

//Drop the `active` column from the Skins table, which isn't being used.
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP COLUMN `active`
_sql


//Adjust the keys on the templates table
); 	revision( 21290
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	DROP KEY `family_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`family_name`, `filename`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`content_type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`status`)
_sql

); 	revision( 21300
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	ADD KEY (`skin_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	ADD KEY (`skin_id`)
_sql


//Add a display name column to the Skins table (defaulting it to the directory name to start with)
//Also add a extension_of_skin column and some keys
); 	revision( 21330
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `display_name` varchar(255) NOT NULL default ''
	AFTER `name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `extension_of_skin` varchar(255) NOT NULL default ''
	AFTER `display_name`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]skins`
	SET display_name = name
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD KEY (`name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD KEY (`display_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD KEY (`extension_of_skin`)
_sql


//Drop the slot_swatch_link table as it's not used anymore
); 	revision( 21335
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]slot_swatch_link`
_sql


//Fix some keys with bad names
);	revision( 21340
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	DROP KEY `new_section_id`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD KEY (`section_id`)
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	DROP KEY `plugin_id`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	ADD UNIQUE KEY (`module_id`,`type`,`dependency_class_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	DROP KEY `plugin_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	ADD KEY (`type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	DROP KEY `plugin_class_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	ADD KEY (`module_class_name`,`type`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	DROP KEY `dependency_plugin_class_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]module_dependencies`
	ADD KEY (`dependency_class_name`,`type`)
_sql


, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	DROP KEY `plugin_class_name`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	ADD UNIQUE KEY (`module_class_name`,`name`)
_sql


//Drop the company_name and version columns from the modules tables, as these are no longer usedrop the `active` column from the Skins table, which isn't being used.
); 	revision( 21360
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	DROP COLUMN `company_name`
_sql


//Add some more columns to the skins table for some new features
); 	revision( 21400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `type` enum('component','usable') NOT NULL default 'usable'
	AFTER `display_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD COLUMN `css_class` varchar(100) NOT NULL default ''
	AFTER `extension_of_skin`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	DROP KEY `extension_of_skin`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD KEY (`type`)
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]skins`
	ADD KEY (`extension_of_skin`)
_sql
);

revision( 21504
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]email_templates`
	DROP COLUMN `attachment_body`, 
	DROP COLUMN `enable_attachment_body`
_sql


//Rename the templates table to layouts
);	revision( 21515
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	CHANGE COLUMN `template_id` `layout_id` int(10) unsigned NOT NULL AUTO_INCREMENT
_sql

); 	revision( 21520
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]layouts`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]templates`
	RENAME TO `[[DB_NAME_PREFIX]]layouts`
_sql


//Also rename the template families table as the previous name didn't follow our naming conventions (i.e. it wasn't plural).
); 	revision( 21525
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_families`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_family`
	RENAME TO `[[DB_NAME_PREFIX]]template_families`
_sql


//Rename "template" to "layout" in a few tables
); 	revision( 21530
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	DROP KEY `default_template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	CHANGE COLUMN `default_template_id` `default_layout_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	ADD KEY (`default_layout_id`)
_sql

); 	revision( 21535
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	DROP KEY `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	CHANGE COLUMN `template_id` `layout_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD KEY (`layout_id`)
_sql

); 	revision( 21540
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_temp_link`
	DROP KEY `template_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_temp_link`
	CHANGE COLUMN `template_id` `layout_id` int(10) unsigned NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_temp_link`
	ADD KEY (`layout_id`,`slot_name`)
_sql

); 	revision( 21545
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]plugin_layout_link`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_temp_link`
	RENAME TO `[[DB_NAME_PREFIX]]plugin_layout_link`
_sql


//Remove a table from the beta that's not now going to be used
); 	revision( 21550
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]grid_designs`
_sql


//Create a table to store a cache of Template Files in the directory system
); 	revision( 21555
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]template_files`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]template_files` (
		`family_name` varchar(50) NOT NULL,
		`filename` varchar(50) NOT NULL,
		`missing` tinyint(1) unsigned NOT NULL default 0,
		PRIMARY KEY (`family_name`,`filename`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Add new language setting - serch for content in the databse can be now performed using MATCH AGAINST (full_text) or LIKE (simple) methods
);revision( 21655
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `search_type` enum('full_text','simple') NOT NULL default 'full_text' AFTER `sync_assist`
_sql


//Increase Alias max length to 75 characters
);	revision( 21660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content`
	MODIFY COLUMN `alias` varchar(75) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]spare_aliases`
	MODIFY COLUMN `alias` varchar(75) NOT NULL
_sql

//Add new characteristic type
); revision( 21745
,
 <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_characteristics`
	MODIFY `type` enum('list_single_select','list_multi_select','boolean','date','text','textarea') NOT NULL
_sql














		//					   //
		//  Changes for 6.1.1  //
		//					   //





//Add a new type of TUIX file for slot controls
);	revision( 22200
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	MODIFY COLUMN `type` enum('admin_boxes', 'admin_toolbar', 'help', 'storekeeper', 'slot_controls') NOT NULL
_sql


//Remove the Template Family layer, and move all of the slot-placements to the layer below
);	revision( 22380
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_layout_link`
		(module_id, instance_id, family_name, layout_id, slot_name)
	SELECT
		ptfl.module_id,
		ptfl.instance_id,
		ptfl.family_name,
		l.layout_id,
		ptfl.slot_name
	FROM `[[DB_NAME_PREFIX]]plugin_temp_fam_link` AS ptfl
	INNER JOIN `[[DB_NAME_PREFIX]]layouts` AS l
	   ON l.family_name = ptfl.family_name
_sql

//Remove the Template Family layer, and move all of the slot-placements to the layer below
);	revision( 22390
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]template_families` AS tf
	   ON l.family_name = tf.family_name
	SET
		l.head_html = CONCAT(tf.head_html, '\n\n', l.head_html),
		l.head_visitor_only = IF (l.head_visitor_only = 1 AND tf.head_visitor_only = 1, 1, 0),
		l.head_cc =
			IF (l.head_cc = 'required' OR tf.head_cc = 'required', 'required',
				IF (l.head_cc = 'needed' OR tf.head_cc = 'needed', 'needed',
					'not_needed'
				)
			)
	WHERE l.head_overwrite = 0
	  AND l.head_html IS NOT NULL
	  AND tf.head_html IS NOT NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]template_families` AS tf
	   ON l.family_name = tf.family_name
	SET
		l.head_html = tf.head_html,
		l.head_visitor_only = tf.head_visitor_only,
		l.head_cc = tf.head_cc
	WHERE l.head_overwrite = 0
	  AND l.head_html IS NULL
	  AND tf.head_html IS NOT NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]template_families` AS tf
	   ON l.family_name = tf.family_name
	SET
		l.foot_html = CONCAT(tf.foot_html, '\n\n', l.foot_html),
		l.foot_visitor_only = IF (l.foot_visitor_only = 1 AND tf.foot_visitor_only = 1, 1, 0),
		l.foot_cc =
			IF (l.foot_cc = 'required' OR tf.foot_cc = 'required', 'required',
				IF (l.foot_cc = 'needed' OR tf.foot_cc = 'needed', 'needed',
					'not_needed'
				)
			)
	WHERE l.foot_overwrite = 0
	  AND l.foot_html IS NOT NULL
	  AND tf.foot_html IS NOT NULL
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts` AS l
	INNER JOIN `[[DB_NAME_PREFIX]]template_families` AS tf
	   ON l.family_name = tf.family_name
	SET
		l.foot_html = tf.foot_html,
		l.foot_visitor_only = tf.foot_visitor_only,
		l.foot_cc = tf.foot_cc
	WHERE l.foot_overwrite = 0
	  AND l.foot_html IS NULL
	  AND tf.foot_html IS NOT NULL
_sql

//Remove the tables/columns that are no longer used
);	revision( 22400
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `head_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `head_cc`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `head_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	DROP COLUMN `head_overwrite`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `foot_html`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `foot_cc`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_families`
	DROP COLUMN `foot_visitor_only`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	DROP COLUMN `foot_overwrite`
_sql

, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]plugin_temp_fam_link`
_sql
				
//Clear some possible junk data out of the plugin_layout_link table
); revision( 22970
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugin_layout_link`
	WHERE module_id = 0
	  AND instance_id = 0
_sql

); revision( 23100
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	MODIFY COLUMN `filename` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '';
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_files`
	MODIFY COLUMN `filename` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	MODIFY COLUMN `template_filename` varchar(255) CHARACTER SET ascii NOT NULL
_sql

//The default Template File was renamed part-way through the 6.1.1 beta.
//Automatically update the name so that we don't break anyone's dev site
); revision( 23120
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]layouts`
	SET filename = '2_col_responsive_fluid.tpl.php'
	WHERE filename = '2-col-responsive.tpl.php'
	  AND family_name = 'grid_templates'
_sql


//Forcebly run the Users Module for anyone who is updating from 6.1.0 and has the Pro Features Module running
);	revision( 23260
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]modules` (`class_name`, `display_name`, `status`)
	SELECT 'zenario_users', 'Users', status
	FROM `[[DB_NAME_PREFIX]]modules`
	WHERE `class_name` = 'zenario_pro_features'
_sql


//Add a new type of TUIX file for slot controls
);	revision( 23330
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	ADD COLUMN `last_modified` int(10) unsigned NOT NULL default 0
	AFTER `filename`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	ADD COLUMN `checksum` varchar(32) CHARACTER SET ascii NOT NULL DEFAULT ''
	AFTER `last_modified`
_sql
				

//Fix a bug where bad data was getting into the category_item_link table
); revision( 23640
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]category_item_link`
	WHERE category_id = 0
_sql
);	revision( 23644
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_accesslog`
	ADD COLUMN `id` int(10) unsigned NOT NULL auto_increment unique
	FIRST
_sql


//Add a Plugin Setting called "refine_by_category" to all Plugins that have a
//non-empty Plugin Setting called "category"
);	revision( 24090
,<<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]plugin_settings`(
		instance_id, name, nest, value, is_content, format
	) SELECT 
		instance_id, 'refine_by_category', nest, '1', is_content, format
	FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name = 'category'
	  AND value IS NOT NULL
	  AND value != ''
_sql




//Remove anything from the category_item_link table that's for a previous version, or for a translation
);	revision( 24105
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]category_item_link`
	WHERE (item_id, content_type, version) NOT IN (
		SELECT DISTINCT id, type, admin_version
		FROM `[[DB_NAME_PREFIX]]content`
		WHERE status != 'deleted'
		  AND id = equiv_id
	)
_sql


//Remove the version column from the category_item_link table, and change item_id to equiv_id
);	revision( 24110
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	DROP INDEX `item_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	DROP COLUMN `version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	CHANGE COLUMN `item_id` `equiv_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	ADD PRIMARY KEY (`category_id`,`content_type`,`equiv_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]category_item_link`
	ADD INDEX (`equiv_id`,`content_type`)
_sql



//Remove anything from the user_content_link table that's for a previous version, or for a translation
);	revision( 24123
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]user_content_link`
	WHERE (content_id, content_type, content_version) NOT IN (
		SELECT DISTINCT id, type, admin_version
		FROM `[[DB_NAME_PREFIX]]content`
		WHERE status != 'deleted'
		  AND id = equiv_id
	)
_sql


//Remove the version column from the user_content_link table, and change item_id to equiv_id
);	revision( 24124
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	DROP COLUMN `content_version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	CHANGE COLUMN `content_id` `equiv_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	ADD PRIMARY KEY (`user_id`,`content_type`,`equiv_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]user_content_link`
	ADD INDEX (`equiv_id`,`content_type`)
_sql



//Remove anything from the group_content_link table that's for a previous version, or for a translation
);	revision( 24125
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]group_content_link`
	WHERE (content_id, content_type, content_version) NOT IN (
		SELECT DISTINCT id, type, admin_version
		FROM `[[DB_NAME_PREFIX]]content`
		WHERE status != 'deleted'
		  AND id = equiv_id
	)
_sql


//Remove the version column from the group_content_link table, and change item_id to equiv_id
);	revision( 24126
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	DROP COLUMN `content_version`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	CHANGE COLUMN `content_id` `equiv_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	ADD PRIMARY KEY (`group_id`,`content_type`,`equiv_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]group_content_link`
	ADD INDEX (`equiv_id`,`content_type`)
_sql



//Create a table to represent translation chains
); 	revision( 24127
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]translation_chains`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]translation_chains` (
		`equiv_id` int(10) unsigned NOT NULL,
		`type` varchar(20) NOT NULL,
		`privacy` enum('public','all_extranet_users','group_members','specific_users','no_access') NOT NULL default 'public',
		`log_user_access` tinyint(1) NOT NULL default 0,
		PRIMARY KEY (`equiv_id`,`type`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Migrate old data
); 	revision( 24128
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]translation_chains` (equiv_id, type)
	SELECT DISTINCT equiv_id, type
	FROM `[[DB_NAME_PREFIX]]content`
	WHERE id = equiv_id
_sql

); 	revision( 24129
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]translation_chains` AS tc
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = tc.equiv_id
	  AND c.type = tc.type
	INNER JOIN `[[DB_NAME_PREFIX]]versions` AS v
	   ON v.id = tc.equiv_id
	  AND v.type = tc.type
	  AND v.version = c.admin_version
	SET tc.privacy = v.privacy,
		tc.log_user_access = v.log_user_access
_sql


//Drop the old data
); 	revision( 24130
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	DROP COLUMN `privacy`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	DROP COLUMN `log_user_access`
_sql


//Fix a bug where newly created Content Items were not included
); 	revision( 24140
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]translation_chains` (equiv_id, type)
	SELECT DISTINCT equiv_id, type
	FROM `[[DB_NAME_PREFIX]]content`
	WHERE id = equiv_id
_sql


//Add a wordcount for plain-text extracts
); 	revision( 24270
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD COLUMN `extract_wordcount` int(10) unsigned NOT NULL default 0
	AFTER `extract`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD KEY (`extract_wordcount`)
_sql














		//					   //
		//  Changes for 6.1.2  //
		//					   //







//Attempt to fix a problem where long paths could not be stored in the primary key by converting to ascii
);	revision( 24500
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	DROP PRIMARY KEY
_sql

);	revision( 24510
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	MODIFY COLUMN `path` varchar(255) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	MODIFY COLUMN `setting_group` varchar(255) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	MODIFY COLUMN `module_class_name` varchar(200) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	MODIFY COLUMN `filename` varchar(255) CHARACTER SET ascii NOT NULL
_sql

);	revision( 24520
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	ADD PRIMARY KEY (`type`,`path`,`setting_group`,`module_class_name`,`filename`)
_sql

);	revision( 24534
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `admin_notes` mediumtext
        AFTER `writer_name`
_sql

);	revision( 24650
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]site_settings`
	MODIFY COLUMN `default_value` mediumtext
_sql


//In 6.1.2 we no longer have customised phrases in the system.

//Create a table to hold a backup of the old phrases
); 	revision( 24652
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]disused_custom_phrases`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]disused_custom_phrases` (
		`code` varchar(100) NOT NULL default '',
		`language_id` varchar(15) NOT NULL default '',
		`module_class_name` varchar(200) NOT NULL,
		`local_text` text NOT NULL,
		`instance_id` int(10) unsigned NOT NULL default 0,
		`nest` int(10) unsigned NOT NULL default 0,
		`content_id` int(10) unsigned NOT NULL default 0,
		`content_type` varchar(20) NOT NULL default '',
		`content_alias` varchar(75) NOT NULL default '',
		KEY (`code`),
		KEY (`language_id`),
		KEY (`module_class_name`),
		KEY (`instance_id`),
		KEY (`nest`),
		KEY (`content_id`),
		KEY (`content_type`),
		KEY (`content_alias`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Copy the phrases into the table as a backup
); 	revision( 24654
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]disused_custom_phrases` (
		code,
		language_id,
		module_class_name,
		local_text,
		instance_id,
		nest
	) SELECT
		code,
		language_id,
		module_class_name,
		local_text,
		instance_id,
		nest
	FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE instance_id != 0
_sql

); 	revision( 24656
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]disused_custom_phrases` (
		code,
		language_id,
		module_class_name,
		local_text,
		content_id,
		content_type,
		content_alias
	) SELECT
		REPLACE(ps.name, '%', ''),
		c.language_id,
		m.class_name,
		ps.value,
		c.id,
		c.type,
		c.alias
	FROM `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	INNER JOIN `[[DB_NAME_PREFIX]]content` AS c
	   ON c.id = pi.content_id
	  AND c.type = pi.content_type
	  AND c.admin_version = pi.content_version
	WHERE ps.name LIKE '\%%\%'
_sql

//Delete all custom phrases
); 	revision( 24658
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]visitor_phrases`
	WHERE instance_id != 0
_sql

, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]plugin_settings`
	WHERE name LIKE '\%%\%'
_sql

//Drop the instance_id/nest columns from the visitor_phrases table
); 	revision( 24660
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP COLUMN `instance_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP COLUMN `nest`
_sql

//Drop the "admin_notes" column as this is getting a proper linking table now
);	revision( 24850
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	DROP COLUMN `admin_notes`
_sql


//Increase the length of the cache column in the plugin_instance_cache table to fix a bug
//in the Feed Reader Module where a large response causes a database error.
//(Actually, I think the Feed Reader Module is the only Module that actually uses this table...)
);	revision( 24980
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_instance_cache`
	MODIFY COLUMN `cache` mediumtext NOT NULL
_sql


//Remove the extension from the filename column, tidy up some column names
);	revision( 25000
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]layouts`
	SET filename = REPLACE(filename, '.tpl.php', '')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]template_files`
	SET filename = REPLACE(filename, '.tpl.php', '')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]template_slot_link`
	SET template_filename = REPLACE(template_filename, '.tpl.php', '')
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	CHANGE COLUMN `filename` `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_files`
	CHANGE COLUMN `filename` `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	CHANGE COLUMN `template_filename` `file_base_name` varchar(255) CHARACTER SET ascii NOT NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]template_slot_link`
	CHANGE COLUMN `template_family_name` `family_name` varchar(50) NOT NULL
_sql


//The default template file has a new name in 6.1.2, and was renamed in svn.
//Rename the filename in the database for any developers who were using it.
);	revision( 25240
, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]layouts`
	SET file_base_name = '2 Column Fluid'
	WHERE file_base_name = '2_col_responsive_fluid'
	  AND family_name = 'grid_templates'
_sql


//Increase the length of the "code" column on the phrases table so that we can
//store long codes/phrases
);	revision( 25270
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP KEY `plugin_class`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP KEY `language_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	DROP KEY `code`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	MODIFY COLUMN `code` text NOT NULL
_sql

);	revision( 25280
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`code`(250))
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`language_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`module_class_name`,`language_id`,`code`(100))
_sql


//Add some new columns to the visitor phrases table for recording phrases
);	revision( 25330
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `seen_in_visitor_mode` tinyint(1) NOT NULL default 0
	AFTER `protect_flag`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`seen_in_visitor_mode`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD COLUMN `seen_in_file` varchar(255) NOT NULL default ''
	AFTER `seen_in_visitor_mode`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	ADD KEY (`seen_in_file`)
_sql

//The local text column can now be null
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]visitor_phrases`
	MODIFY COLUMN `local_text` text
_sql

);























		//					 //
		//  Changes for 7.0  //
		//					 //




//Rename any modules called "tribiq_" to "zenario_"
	revision( 25490
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]modules`
	SET `vlp_class` = REPLACE(`vlp_class`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]module_dependencies`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_'),
		`dependency_class_name` = REPLACE(`dependency_class_name`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_setting_defs`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]signals`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_'),
		`suppresses_module_class_name` = REPLACE(`suppresses_module_class_name`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]special_pages`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_'),
		`page_type` = REPLACE(`page_type`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]visitor_phrases`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	SET `module_class_name` = REPLACE(`module_class_name`, 'tribiq_', 'zenario_')
_sql

, <<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]email_templates`
	SET `code` = REPLACE(`code`, 'tribiq_', 'zenario_')
_sql


//As the Module Schema has changed, mark that we need to re-read all of the module description files
); 	revision( 25500
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]local_revision_numbers`
	WHERE patchfile IN ('description.xml', 'description.yaml')
_sql




//Fix a bug where there was duplicate data in the visitor_phrases table
);	revision( 25630
, <<<_sql
	DELETE two
	FROM `[[DB_NAME_PREFIX]]visitor_phrases` AS two
	INNER JOIN `[[DB_NAME_PREFIX]]visitor_phrases` AS one
	   ON two.language_id = one.language_id
	  AND two.module_class_name = one.module_class_name
	  AND two.code = one.code
	WHERE two.id > one.id
_sql


//Add a missing key to the content cache table
	//Note that this was also added in a patch to 6.1.2, but the updater will just
	//ignore the error if the primary key already exists.
);	revision( 25750
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_cache`
	ADD PRIMARY KEY (`content_id`, `content_type`, `content_version`)
_sql


//add documents table, starting to implement hierarchical view of file/documents with folders
); revision( 25751
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]documents`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]documents` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `ordinal` int(10) NOT NULL,
	  `type` enum('file','folder') NOT NULL DEFAULT 'file',
	  `file_id` int(10) NULL,
	  `folder_id` int(10) NOT NULL DEFAULT 0,
	  `document_datetime` datetime NULL,
	  `thumbnail_id` int(10) NULL,
	  `document_tag_ids` varchar(255),
	   PRIMARY KEY (`id`),
	   UNIQUE KEY `file_id` (`file_id`),
	   KEY `ordinal` (`ordinal`),
	   KEY `type` (`type`),
	   KEY `folder_id` (`folder_id`),
	   KEY `document_tag_ids` (`document_tag_ids`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


); revision( 25752
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
		ADD COLUMN `folder_name` varchar(255) NULL
		AFTER `folder_id`
_sql

); revision( 25753
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
		DROP KEY `document_tag_ids`,
		DROP COLUMN `document_tag_ids`
_sql
,
<<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_tag_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]document_tag_link` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `document_id` int(10) NOT NULL,
	  `tag_id` int(10) NOT NULL,
	   PRIMARY KEY (`id`),
	   UNIQUE KEY `document_tag_link` (`document_id`, `tag_id`),
	   KEY `type` (`document_id`),
	   KEY `folder_id` (`tag_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
,
<<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_tags`
_sql
, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]document_tags` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `tag_name` varchar(255) NOT NULL,
	   PRIMARY KEY (`id`),
	   UNIQUE KEY `tag_name` (`tag_name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Drop the tag_prefix column, as it's not used any more
);	revision( 25945
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]content_types`
	DROP COLUMN `tag_prefix`
_sql


//Rename tribiq/file.php to zenario/file.php when moving from version 6 to version 7.
);	revision( 26300
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings`
	SET value = REPLACE(value, 'tribiq/file.php', 'zenario/file.php')
	WHERE value LIKE '%tribiq/file.php%'
	  AND name = 'html'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]email_templates`
	SET body = REPLACE(body, 'tribiq/file.php', 'zenario/file.php')
	WHERE body LIKE '%tribiq/file.php%'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]versions`
	SET content_summary = REPLACE(content_summary, 'tribiq/file.php', 'zenario/file.php')
	WHERE content_summary LIKE '%tribiq/file.php%'
_sql


//Fix a bug where some queries were slow on the plugin_item_link table
);	revision( 26690
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_item_link`
	DROP KEY `chris_test_key`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]plugin_item_link`
	ADD KEY `reusable_plugin_item_link` (`instance_id`, `content_id`, `content_type`, `content_version`) 
_sql


//Give the xml_file_tuix_contents table a more meaningful name to avoid confusion in the future
);	revision( 26950
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]xml_file_tuix_contents`
	RENAME TO `[[DB_NAME_PREFIX]]tuix_file_contents`
_sql


//Add a translate_phrases column to the languages table
);	revision( 27090
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD COLUMN `translate_phrases` tinyint(1) NOT NULL default 1
	AFTER `detect_lang_codes`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]languages`
	ADD KEY (`translate_phrases`)
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]languages`
	SET translate_phrases = 0
	WHERE id LIKE 'en-%'
	   OR id = 'en'
_sql


//Delete any animations that are stored as the animations module and all handling for them has been removed
);	revision( 27220
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]files`
	WHERE `usage` = 'inline'
	  AND mime_type = 'application/x-shockwave-flash'
_sql

); revision( 27230

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `scheduled_publish_datetime` datetime DEFAULT NULL
_sql

); revision( 27232

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `anchor` varchar(255) DEFAULT NULL
_sql

);  revision( 27233

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `module_class_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `method_name` varchar(255) DEFAULT NULL,
	ADD COLUMN `param_1` varchar(255) DEFAULT NULL,
	ADD COLUMN `param_2` varchar(255) DEFAULT NULL
_sql

); revision( 27234

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]menu_nodes`
	ADD COLUMN `rollover_image_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `image_id`
_sql

);	revision(27320
, <<<_sql
	DROP TABLE IF EXISTS [[DB_NAME_PREFIX]]documents_custom_data
_sql

, <<<_sql
	CREATE TABLE [[DB_NAME_PREFIX]]documents_custom_data (
		`document_id` int(10) unsigned NOT NULL,
		PRIMARY KEY (`document_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


); revision( 27341

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `extract` mediumtext DEFAULT NULL,
	ADD COLUMN `extract_wordcount` int(10) unsigned NOT NULL DEFAULT 0
_sql


);	revision( 27931
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	MODIFY COLUMN `path` varchar(255) NOT NULL default ''
_sql


//Rename a couple of module/plugin related columns
);	revision( 28000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `uses_instances` `is_pluggable` tinyint(1) NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	CHANGE COLUMN `uses_wireframes` `can_be_version_controlled` tinyint(1) NOT NULL default 0
_sql

//Add a column to the modules table to mark which ones are missing
);	revision( 28050
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]modules`
	ADD COLUMN `missing` tinyint(1) unsigned NOT NULL default 0
	AFTER `status`
_sql

//Create the document rules table
); revision( 28230
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	ADD COLUMN `do_not_auto_recode` tinyint(1) unsigned NOT NULL default 0
	AFTER `extract_wordcount`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]document_rules`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]document_rules` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `ordinal` int(10) unsigned NOT NULL default 0,
	  `pattern` mediumtext,
	  `action` enum('move_to_folder', 'set_field') NOT NULL,
	  `folder_id` int(10) unsigned NOT NULL default 0,
	  `field_id` int(10) unsigned NOT NULL default 0,
	  `replacement` mediumtext,
	  `replacement_is_regexp` tinyint(1) unsigned NOT NULL,
	  `stop_processing_rules` tinyint(1) unsigned NOT NULL default 1,
	   PRIMARY KEY (`id`),
	   KEY (`ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision( 28240
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `use` enum('filename_without_extension', 'filename_and_extension', 'extension') NOT NULL default 'filename_without_extension'
	AFTER `ordinal`
_sql

); revision( 28260
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]documents`
	CHANGE COLUMN `do_not_auto_recode` `dont_autoset_metadata` tinyint(1) unsigned NOT NULL default 0
_sql

); revision( 28460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `apply_second_pass` tinyint(1) unsigned NOT NULL default 0
	AFTER `replacement_is_regexp`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `second_pattern` mediumtext
	AFTER `apply_second_pass`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]document_rules`
	ADD COLUMN `second_replacement` mediumtext
	AFTER `second_pattern`
_sql


//Create a table which will be used for the hierarchial panel for selecting
//new menu node positions.
//It has an entry for every menu section, every menu node, and dupicate entries for
//everything which work as "child" selectors.
//Having to make a table for this is a big hack; at some point we should improve or rewrite
//the selection interface and remove the need for this table.
); revision( 28550
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]menu_positions`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]menu_positions` (
		`tag` char(18) NOT NULL,
		`section_id` smallint(10) unsigned NOT NULL,
		`menu_id` int(10) unsigned NOT NULL default 0,
		`is_dummy_child` tinyint(1) unsigned NOT NULL default 0,
		`parent_tag` char(18) NOT NULL,
		PRIMARY KEY (`tag`),
		KEY (`parent_tag`),
		UNIQUE KEY (`section_id`, `menu_id`, `is_dummy_child`)
	) ENGINE=MyISAM DEFAULT CHARSET=ascii
_sql

//Add some more keys to the files table
); revision( 28460
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_height`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_list_width`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]files`
	ADD KEY (`storekeeper_list_height`)
_sql


//Enable background images for Content Items/Layouts
); 	revision( 29000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `background_image_id` int(10) unsigned NOT NULL default 0
	AFTER `css_class`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `background_image_id` int(10) unsigned NOT NULL default 0
	AFTER `css_class`
_sql

//Remove the create_lang_equiv_content column from the special_pages table and replace it with an enum which has a new third option
); 	revision( 29030
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	ADD COLUMN `logic`
		ENUM('create_and_maintain_in_default_language', 'create_and_maintain_in_all_languages', 'create_in_default_language_on_install')
		NOT NULL default 'create_and_maintain_in_default_language'
	AFTER `create_lang_equiv_content`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]special_pages` SET
		`logic` = 'create_and_maintain_in_all_languages'
	WHERE create_lang_equiv_content = 1
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]special_pages`
	DROP COLUMN `create_lang_equiv_content`
_sql

//Automatically strip off all of the [[ double square brackets ]] from the title fields of banners
//as these are now not needed
);	revision( 29180
,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]plugin_instances` AS pi
	   ON pi.id = ps.instance_id
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = pi.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest = 0
	  AND m.class_name = 'zenario_banner'
	  AND ps.name = 'title'
	  AND ps.value LIKE '[[%]]'
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]plugin_settings` AS ps
	INNER JOIN `[[DB_NAME_PREFIX]]nested_plugins` AS np
	   ON np.id = ps.nest
	INNER JOIN `[[DB_NAME_PREFIX]]modules` AS m
	   ON m.id = np.module_id
	SET ps.value = REPLACE(REPLACE(ps.value, '[[', ''), ']]', '')
	WHERE ps.nest != 0
	  AND m.class_name = 'zenario_banner'
	  AND ps.name = 'title'
	  AND ps.value LIKE '[[%]]'
_sql


//Enable more background options for Content Items/Layouts
); 	revision( 29230
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	CHANGE COLUMN `background_image_id` `bg_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_color` varchar(64) NOT NULL default ''
	AFTER `bg_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_position` enum('left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom')
	NULL default NULL
	AFTER `bg_color`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]versions`
	ADD COLUMN `bg_repeat` enum('repeat', 'repeat-x', 'repeat-y', 'no-repeat')
	NULL default NULL
	AFTER `bg_position`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	CHANGE COLUMN `background_image_id` `bg_image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_color` varchar(64) NOT NULL default ''
	AFTER `bg_image_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_position` enum('left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom')
	NULL default NULL
	AFTER `bg_color`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	ADD COLUMN `bg_repeat` enum('repeat', 'repeat-x', 'repeat-y', 'no-repeat')
	NULL default NULL
	AFTER `bg_position`
_sql
); revision ( 29238

,<<<_sql
	UPDATE IGNORE `[[DB_NAME_PREFIX]]plugin_settings`
	SET name = 'user_form'
	WHERE name = 'user_profile_form'
_sql

); revision( 29239

,<<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]layouts`
	DROP COLUMN `image_id`
_sql

);
