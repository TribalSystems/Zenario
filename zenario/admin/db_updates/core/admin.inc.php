<?php
/*
 * Copyright (c) 2014, Tribal Limited
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





//The username column need not be unique in the superadmins table
//Also drop the unused restrict_by_menuitem column
	revision( 2505,
<<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]admins DROP COLUMN `restrict_by_menuitem`
_sql

);	revision( 2513
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]admins DROP KEY `username`
_sql


//Add new password columns to the admin table
	//password_salt - used to store a salt for the password, if it is encrypted.
	//password_needs_changing - set if the user should change their password
);	revision( 2521
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]admins
	ADD `password_needs_changing` tinyint(1) NOT NULL DEFAULT 0
	AFTER `password`
_sql

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]admins
	ADD `password_salt` varchar(8) NULL DEFAULT NULL
	AFTER `password`
_sql


//Originally int was mistakenly used instead of tinyint above; fix that on any databases
//that made the original change
);	revision( 2525
, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]]admins
	MODIFY `password_needs_changing` tinyint(1) NOT NULL DEFAULT 0
_sql


//Add junior menu editor permission
);	revision( 2806
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` 
	ADD COLUMN `perm_editmenu_jnr` tinyint(1) NOT NULL DEFAULT 0
_sql


//Create new permissions tables
);	revision( 4830
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]action_admin_link`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]action_admin_link` (
		`action_name` varchar(50) NOT NULL,
		`admin_id` int(10) unsigned NOT NULL,
		UNIQUE KEY (`action_name`, `admin_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Fix any bad data that is not valid for MySQL strict mode
);	revision( 7050
, <<<_sql
	UPDATE [[DB_NAME_PREFIX]]admins SET
		status = 'pending'
	WHERE status NOT IN ('pending','active','suspended','deleted')
_sql


//Create new table for storing admin storekeeper settings
);	revision( 8430
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs` (
		`admin_id` int(10) unsigned NOT NULL,
		`admin_type` enum('local','super') NOT NULL,
		`prefs` mediumtext,
		PRIMARY KEY (`admin_id`, `admin_type`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql


//Drop the now unused overview tables
);	revision( 9310
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_overview_feeds`
_sql


//Changes for Admins in zenario 6
); 	revision( 14000
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `perm_editmenu_jnr`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `authtype` enum('local','super') NOT NULL default 'local'
	AFTER `id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `global_id` int(10) unsigned NOT NULL default 0
	AFTER `authtype`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`authtype`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`global_id`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`username`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`first_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`last_name`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`email`)
_sql


//remove authtype from the admin_storekeeper_prefs table
);	revision( 15100
, <<<_sql
	DELETE FROM `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	WHERE `admin_type` = 'super'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	DROP PRIMARY KEY
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	DROP COLUMN `admin_type`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	ADD PRIMARY KEY (`admin_id`)
_sql


//Some adjustments to the admins table in zenario 6.0.4
); 	revision( 18585
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `use_language_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `preferred_editor_id`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `note_email_min_priority`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `last_login_ip` int(10) NOT NULL default 0
	AFTER `last_login`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`last_login`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD INDEX (`last_login_ip`)
_sql


); 	revision( 18590
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins`
	SET `status` = 'deleted'
	WHERE `status` != 'active'
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	MODIFY COLUMN `status` enum('active','deleted') NOT NULL default 'deleted'
_sql


//Drop the admin_session table which is no longer used
);	revision( 18595
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_session`;
_sql


//Changes for Admins in zenario 6.0.5
//Add a reset password, so requesting a password reset does not change your current password
); 	revision( 19420
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `reset_password` varchar(50) NULL default NULL
	AFTER `password_needs_changing`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `reset_password_salt` varchar(8) NULL default NULL
	AFTER `reset_password`
_sql


//Changes for Admins in zenario 6.0.5
//Add a reset password, so requesting a password reset does not change your current password
); 	revision( 19495
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `reset_password_time` datetime NULL default NULL
	AFTER `reset_password_salt`
_sql


//Add a checksum column to the admin_storekeeper_prefs table, so we can easily tell if something has changed
); 	revision( 19700
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	ADD COLUMN `checksum` varchar(22) NOT NULL default '{}'
	AFTER `admin_id`
_sql

, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs` SET
	`checksum` = 'checksum_not_yet_set'
_sql


//IPs used to be stored as an integer. We should now store them as a string so we can handle Proxy lists and IPV6
);	revision( 20772
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `new_ip` varchar(80) CHARACTER SET ascii NOT NULL default ''
	AFTER `last_login_ip`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD KEY (`new_ip`)
_sql

);	revision( 20773
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]]admins`
	SET new_ip = CONVERT(last_login_ip USING ascii)
	WHERE last_login_ip != 0
_sql

);	revision( 20774
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP COLUMN `last_login_ip`
_sql

);	revision( 20775
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	CHANGE COLUMN `new_ip` `last_login_ip` varchar(80) CHARACTER SET ascii NOT NULL default ''
_sql


//Fix a key with a bad name
);	revision( 21340
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	DROP KEY `new_ip`
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD KEY (`last_login_ip`)
_sql

); revision (24535
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `image_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD KEY (`image_id`)
_sql

); revision (25342
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `last_browser` varchar(255) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `last_browser_version` varchar(255) NOT NULL default ''
_sql

); revision(25631
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins`
	ADD COLUMN `last_platform` varchar(255) NOT NULL default ''
_sql

);