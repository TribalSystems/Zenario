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

//Migrate the old organizer_page_size setting
, <<<_sql
	INSERT IGNORE INTO `[[DB_NAME_PREFIX]]admin_settings`
	SELECT a.id, 'organizer_page_size', IFNULL(s.value, s.default_value)
	FROM `[[DB_NAME_PREFIX]]admins` AS a
	INNER JOIN `[[DB_NAME_PREFIX]]site_settings` AS s
	WHERE name IN ('organizer_page_size', 'storekeeper_page_size')
_sql


);