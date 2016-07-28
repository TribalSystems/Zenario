<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

//This file works like local.inc.php, but should contain any updates for local admin tables
//(i.e. updates that won't be re-run after a site-reset)


//Add a flag to identify client and supplier admins
revision( 31860
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `is_client_account` tinyint(1) NOT NULL DEFAULT 1
_sql


//Create a table to store admin settings
);	revision( 32300
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_settings`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]admin_settings` (
		`admin_id` int(10) unsigned NOT NULL,
		`name` varchar(255) NOT NULL DEFAULT '',
		`value` mediumtext,
		PRIMARY KEY (`admin_id`, `name`),
		KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Migrate the old _PRIV_VIEW_DEV_TOOLS permission
, <<<_sql
	INSERT INTO `[[DB_NAME_PREFIX]]admin_settings`
	SELECT admin_id, 'show_dev_tools', '1'
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_VIEW_DEV_TOOLS'
_sql



		//					 //
		//  Changes for 7.1  //
		//					 //


//In 7.1 we've added two new minor admin permissions.
//Copy the intitial values from two more powerful admin permissions, so we don't get lots of authors with
//"gaps" in their permissions
);	revision( 33300
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT '_PRIV_EDIT_MENU_TEXT', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_EDIT_MENU_ITEM'
_sql

, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT '_PRIV_CREATE_TRANSLATION_FIRST_DRAFT', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_CREATE_FIRST_DRAFT'
_sql

//Add a new enum to the admins table to store the new types of admin permission
);	revision( 33399
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `permissions` enum('all_permissions', 'specific_actions', 'specific_languages', 'specific_menu_areas')
		NOT NULL default 'specific_actions'
	AFTER `global_id`
_sql

);	revision( 33400
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]action_admin_link` AS aal
	   ON aal.action_name = '_ALL'
	  AND aal.admin_id = a.id
	SET `permissions` = 'all_permissions'
_sql

//Add some more new columns, and do a bit or re-arranging
);	revision( 33420
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	MODIFY COLUMN `permissions` enum('all_permissions', 'specific_actions', 'specific_languages', 'specific_menu_areas')
		NOT NULL default 'specific_actions'
	AFTER `status`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `specific_languages` text
	AFTER `permissions`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `specific_content_items` text
	AFTER `specific_languages`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `specific_menu_areas` text
	AFTER `specific_content_items`
_sql


//Add _PRIV_DELETE_TRASHED_CONTENT_ITEMS for emptying the trash,
//so we can give restricted admins this permission but not _PRIV_TRASH_CONTENT_ITEM.
//Any existing admins with _PRIV_TRASH_CONTENT_ITEM should initially be given this new permission.
);	revision( 33490
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT '_PRIV_DELETE_TRASHED_CONTENT_ITEMS', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_TRASH_CONTENT_ITEM'
_sql

//Merge the old _PRIV_CREATE_GROUP, _PRIV_EDIT_GROUP and _PRIV_DELETE_GROUP permissions into
//the new _PRIV_MANAGE_GROUP permission, using _PRIV_EDIT_GROUP to initially set it.
);	revision( 33720
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT DISTINCT '_PRIV_MANAGE_GROUP', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_EDIT_GROUP'
_sql

//Add a new permission for seeing the diagnostics screen,
//copying _PRIV_VIEW_SITE_SETTING for the initial values.
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT DISTINCT '_PRIV_VIEW_DIAGNOSTICS', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_VIEW_SITE_SETTING'
_sql

//Add a new permission for applying database updates.
//I'm being flexible and allowing any designer, manager, publisher or backup-manager
//to initially have this permission.
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT DISTINCT '_PRIV_APPLY_DATABASE_UPDATES', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name IN ('perm_designer', 'perm_manage', 'perm_publish', 'perm_restore')
_sql

//Add a new permission for managing countries and regions
//copying _PRIV_PUBLISH_CONTENT_ITEM for the initial values.
);	revision( 33770
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT DISTINCT '_PRIV_MANAGE_COUNTRY', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_PUBLISH_CONTENT_ITEM'
_sql

//Add a new permission for managing ecommerce
//copying _PRIV_PUBLISH_CONTENT_ITEM for the initial values.
);	revision( 33770
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]action_admin_link`
	SELECT DISTINCT '_PRIV_MANAGE_ECOMMERCE', admin_id
	FROM `[[DB_NAME_PREFIX]]action_admin_link`
	WHERE action_name = '_PRIV_PUBLISH_CONTENT_ITEM'
_sql




//Remove the old organizer_page_size setting
);	revision( 35700
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]admin_settings`
	WHERE name IN ('organizer_page_size', 'storekeeper_page_size')
_sql

);