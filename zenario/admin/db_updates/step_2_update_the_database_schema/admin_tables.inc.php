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

//This file works like local.inc.php, but should contain any updates for local admin tables
//(i.e. updates that won't be re-run after a site-reset)



//Rename the admin_storekeeper_prefs table to admin_organizer_prefs
ze\dbAdm::revision( 38821
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]admin_organizer_prefs`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_storekeeper_prefs`
	RENAME TO `[[DB_NAME_PREFIX]]admin_organizer_prefs`
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 40150
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `email` varchar(200) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `first_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `last_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `password` varchar(50) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `password_salt` varchar(8) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `reset_password` varchar(50) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `reset_password_salt` varchar(8) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `specific_content_items` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `specific_languages` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `specific_menu_areas` text CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admins` MODIFY COLUMN `username` varchar(50) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_organizer_prefs` MODIFY COLUMN `checksum` varchar(22) CHARACTER SET utf8mb4 NOT NULL default '{}'
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_organizer_prefs` MODIFY COLUMN `prefs` mediumtext CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]admin_settings` MODIFY COLUMN `value` mediumtext CHARACTER SET utf8mb4 NULL
_sql

);