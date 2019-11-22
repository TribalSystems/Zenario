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


//This file works like local.inc.php, but should contain any updates for user-related tables



//Drop the session_id column from the users table as it's not used for anything
ze\dbAdm::revision( 39000
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	DROP COLUMN `session_id`
_sql


//Drop the columns from the user_signin_log table that repeat the user's first name/last name/screen name/email
); ze\dbAdm::revision( 39060
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_signin_log`
	DROP COLUMN `screen_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_signin_log`
	DROP COLUMN `first_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_signin_log`
	DROP COLUMN `last_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_signin_log`
	DROP COLUMN `email`
_sql


//Drop and recreate the hash column on the users table to use base64 instead of base16.
//(N.b. this will also delete any existing hashes but that's okay, they're only supposed to be temporary anyway.)
); ze\dbAdm::revision( 39730
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	DROP COLUMN `hash`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	ADD COLUMN `hash` varchar(28) CHARACTER SET ascii NOT NULL default ''
	AFTER `content_type`
_sql

);	ze\dbAdm::revision(39810
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_group_rules`
	CHANGE `type_of_check` `type_of_check` enum('user_field','role','activity_band') NOT NULL default 'user_field'
_sql

);	ze\dbAdm::revision(39820
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_group_rules`
	ADD COLUMN `activity_band_id` int(10) unsigned NOT NULL default 0
	AFTER `role_id`
_sql

);	ze\dbAdm::revision(40040
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type`
		enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker','flag')
	NOT NULL default 'other_system_field'
_sql

);	ze\dbAdm::revision(40050
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type`
		enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker')
	NOT NULL default 'other_system_field'
_sql


//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 40150
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `email` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `first_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `identifier` varchar(50) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `last_login_ip` varchar(255) CHARACTER SET ascii NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `last_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `password_salt` varchar(8) CHARACTER SET ascii NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `salutation` varchar(25) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users` MODIFY COLUMN `screen_name` varchar(50) CHARACTER SET utf8mb4 NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `default_label` varchar(64) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `label` varchar(64) CHARACTER SET utf8mb4 NOT NULL default ''
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `note_below` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `required_message` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `side_note` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `validation_message` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]]custom_dataset_fields` SET `values_source_filter` = SUBSTR(`values_source_filter`, 1, 250) WHERE CHAR_LENGTH(`values_source_filter`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields` MODIFY COLUMN `values_source_filter` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql

);	ze\dbAdm::revision(40160
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]user_admin_box_tabs`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]user_characteristics`
_sql

);  ze\dbAdm::revision(40401
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type` enum('group','checkbox','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker','repeat_start','repeat_end') NOT NULL DEFAULT 'other_system_field',
	ADD COLUMN `min_rows` tinyint(1) unsigned NOT NULL DEFAULT '0',
	ADD COLUMN `max_rows` tinyint(1) unsigned NOT NULL DEFAULT '0',
	ADD COLUMN `repeat_start_id` int(10) unsigned NOT NULL DEFAULT '0'
_sql




//Add new system field for users called send_delayed_registration_email
//(N.b. this was added in an after-branch patch in 7.6 revision 40193, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(40790) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'users LIKE "send_delayed_registration_email"'))	ze\dbAdm::revision( 40790
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	ADD COLUMN `send_delayed_registration_email` tinyint(1) NOT NULL default 0
	AFTER `last_updated_timestamp`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	ADD KEY (`send_delayed_registration_email`)
_sql


//Add a new column to allow system fields to be hidden from the dataset editor. Must be set to allow changing the visibility.
); ze\dbAdm::revision( 40881
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `allow_admin_to_change_export` tinyint(1) NOT NULL DEFAULT 0 AFTER `allow_admin_to_change_visibility`
_sql

//Add readonly flag for dataset fields
); ze\dbAdm::revision( 41743
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `readonly` tinyint(1) NOT NULL DEFAULT 0 AFTER `sortable`
_sql

//Remove the user syncing tech, as no-one uses it now
); ze\dbAdm::revision( 44170
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	DROP COLUMN `global_id`
_sql


//Fix a bad table definition that sometimes causes a db-error when restoring a backup/migrating a site
); ze\dbAdm::revision( 44180
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_content_accesslog`
	MODIFY COLUMN `hit_datetime` datetime NOT NULL DEFAULT '1970-01-01 00:00:00'
_sql

//Delete where user IP addresses are stored according to GDPR (General Data Protection Regulation)
//(N.b. this was added in an after-branch patch in 8.1 revision 44267, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(44501) && ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'users LIKE "last_login_ip"'))	ze\dbAdm::revision( 44501
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_content_accesslog`
	DROP COLUMN `ip`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_signin_log`
	DROP COLUMN `ip`
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	DROP COLUMN `last_login_ip`
_sql

//Add a column to the users table to record the creation method
); ze\dbAdm::revision( 45063
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	ADD COLUMN `creation_method_note` varchar(255) CHARACTER SET utf8mb4 NOT NULL default '' AFTER `creation_method`
_sql

//Add new dataset field type "consent"
); ze\dbAdm::revision( 45301
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	MODIFY COLUMN `type` enum('group','checkbox','consent','checkboxes','date','editor','radios','centralised_radios','select','centralised_select','text','textarea','url','other_system_field','dataset_select','dataset_picker','file_picker','repeat_start','repeat_end') NOT NULL DEFAULT 'other_system_field'
_sql

//This field should not be nullable and default to 'visitor' to work correctly...
); ze\dbAdm::revision( 45403
, <<<_sql
	UPDATE `[[DB_PREFIX]]users`
	SET `creation_method` = 'visitor'
	WHERE `creation_method` IS NULL
_sql

, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	MODIFY COLUMN `creation_method` enum('visitor','admin') NOT NULL DEFAULT 'visitor'
_sql

//Add a property to dataset fields to record whether it should be filterable or not
//"searchable" now means it should work with the quick-search ("text" type only property)
//"filterable" means it should work with the right side filters
); ze\dbAdm::revision( 45601
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `filterable` tinyint(1) NOT NULL DEFAULT '0' AFTER `searchable`
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]custom_dataset_fields`
	SET `filterable` = 1
	WHERE `searchable` = 1 AND `is_system_field` = 0
_sql

, <<<_sql
	UPDATE `[[DB_PREFIX]]custom_dataset_fields`
	SET `searchable` = 0
	WHERE `is_system_field` = 0 AND `type` != "text"
_sql

//Add a property to dataset fields for when their db column is being updated on the custom data table.
//For long queries this can be used to avoid database errors for when the stored name and actual name 
//is out of sync. 
); ze\dbAdm::revision( 46305
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `db_update_running` tinyint(1) NOT NULL DEFAULT '0' AFTER `db_column`
_sql

);





