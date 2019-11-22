<?php
/*
 * Copyright (c) 2019, Tribal Limited
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



//Rename the admin_storekeeper_prefs table to admin_organizer_prefs
ze\dbAdm::revision( 38821
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]admin_organizer_prefs`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_storekeeper_prefs`
	RENAME TO `[[DB_PREFIX]]admin_organizer_prefs`
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 40150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `email` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `first_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `last_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `password` varchar(50) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `password_salt` varchar(8) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `reset_password` varchar(50) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `reset_password_salt` varchar(8) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `specific_content_items` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `specific_languages` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `specific_menu_areas` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins` MODIFY COLUMN `username` varchar(50) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_organizer_prefs` MODIFY COLUMN `checksum` varchar(22) CHARACTER SET utf8mb4 NOT NULL default '{}'
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_organizer_prefs` MODIFY COLUMN `prefs` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admin_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql

); ze\dbAdm::revision(44780
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD `session_id` varchar(50) NULL
_sql

//Add a hash column to admins to allow admins to be created without a password and a personal link to the site can be sent instead
//and then they can enter their password themselves
//(N.b. this was added in an after-branch patch in 8.1 revision 44270, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(44787) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'admins LIKE "hash"'))	ze\dbAdm::revision( 44787
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `hash` varchar(28) CHARACTER SET ascii NOT NULL DEFAULT ''
_sql

//T11306
//All administrators without the "every possible permission" option checked should lose the ability to
//create, restore and download database backups.
//(You can reverse this if you wish, it's just a one-time change that's made upon updating.)
); ze\dbAdm::revision(45050
, <<<_sql
	DELETE FROM `[[DB_PREFIX]]action_admin_link`
	WHERE action_name IN ('_PRIV_BACKUP_SITE', '_PRIV_RESTORE_SITE')
_sql



//Rework to the restricted admin permissions
); ze\dbAdm::revision(48600
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	CHANGE COLUMN `permissions` `old_permissions`
		enum('all_permissions','specific_actions','specific_languages','specific_menu_areas')
		NOT NULL DEFAULT 'specific_actions'
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `permissions`
		enum('all_permissions', 'specific_actions', 'specific_areas')
		NOT NULL DEFAULT 'specific_actions'
	AFTER `status`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins`
	SET `permissions` = 'all_permissions'
	WHERE old_permissions = 'all_permissions'
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]admins`
	SET `permissions` = 'specific_areas'
	WHERE old_permissions IN ('specific_languages', 'specific_menu_areas')
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `old_permissions`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	DROP COLUMN `specific_menu_areas`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	MODIFY COLUMN `specific_languages` varchar(255) CHARACTER SET ascii
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]admins`
	ADD COLUMN `specific_content_types` varchar(255) CHARACTER SET ascii
	AFTER `specific_content_items`
_sql

);