<?php
/*
 * Copyright (c) 2022, Tribal Limited
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



//This field should not be nullable and default to 'visitor' to work correctly...
ze\dbAdm::revision( 45403
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
//(N.b. this was added in an after-branch patch in 8.3 revision 46305, so we need to check if it's not already there.)
);	if (ze\dbAdm::needRevision(46801) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'custom_dataset_fields LIKE "db_update_running"'))	ze\dbAdm::revision( 46801
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `db_update_running` tinyint(1) NOT NULL DEFAULT '0' AFTER `db_column`
_sql

//Improvements to tracking user management events: create/edit.
//In addition to storing the created/edited date, Zenario will store the details of the user/admin
//who created/last edited the user account.
//Please note that similar changes were also implemented in the following modules:
//	- Location Manager (admin box and Organizer panel),
//	- Locations FEA (view/edit mode),
//	- Company Locations Manager,
//	- Companies FEA (view/edit mode).
//Refer the relevant module db_updates folder.
); ze\dbAdm::revision( 50191
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]users`
	ADD COLUMN `created_admin_id` int(10) unsigned DEFAULT NULL AFTER `created_date`,
	ADD COLUMN `created_user_id` int(10) unsigned DEFAULT NULL AFTER `created_admin_id`,
	ADD COLUMN `created_username` varchar(255) DEFAULT NULL after `created_user_id`,
	ADD COLUMN `last_edited_admin_id` int(10) unsigned DEFAULT NULL AFTER `modified_date`,
	ADD COLUMN `last_edited_user_id` int(10) unsigned DEFAULT NULL AFTER `last_edited_admin_id`,
	ADD COLUMN `last_edited_username` varchar(255) DEFAULT NULL after `last_edited_user_id`
_sql







//
//	Zenario 8.8
//

); ze\dbAdm::revision(51700
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_group_rules`
	MODIFY COLUMN `type_of_check`
		enum('user_field','role','activity_band','in_a_group','not_in_a_group')
	NOT NULL default 'user_field'
_sql


//Create a linking table for assigning users into countries
//(Note: it's a core table, but needs the country manager running before you see
// the option in the FAB to set it.)
); ze\dbAdm::revision( 51750
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]]user_country_link`
_sql

, <<<_sql
	 CREATE TABLE `[[DB_PREFIX]]user_country_link` (
		`user_id` int(10) unsigned NOT NULL,
		`country_id` varchar(5) NOT NULL,
		PRIMARY KEY (`user_id`, `country_id`),
		UNIQUE KEY (`country_id`, `user_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] CHARSET=[[ZENARIO_TABLE_CHARSET]] COLLATE=[[ZENARIO_TABLE_COLLATION]] 
_sql





//
//	Zenario 9.1
//

//Drop one of Robin's debug columns, it's not needed
); ze\dbAdm::revision(53900
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	DROP COLUMN `db_update_running`
_sql


//
//	Zenario 9.3
//

//In 9.3, we're going through and fixing the character-set on several columns that should
//have been using "ascii"
);	ze\dbAdm::revision(55140
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]user_country_link`
	MODIFY COLUMN `country_id` varchar(5) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
_sql

//Starting with 9.3, smart groups may now check whether a user has any or a specific active timer,
//or check if the user has no active timer.
); ze\dbAdm::revision(56352
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]smart_group_rules`
	MODIFY COLUMN `type_of_check`
		enum('user_field', 'role', 'activity_band', 'in_a_group', 'not_in_a_group', 'has_a_current_timer', 'has_no_current_timer') NOT NULL default 'user_field',
	ADD COLUMN `timer_template_id` int unsigned NOT NULL DEFAULT '0' AFTER `role_id`
_sql


);