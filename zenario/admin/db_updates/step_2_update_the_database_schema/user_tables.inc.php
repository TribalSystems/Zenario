<?php
/*
 * Copyright (c) 2024, Tribal Limited
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




//
//	Zenario 9.1
//

//Drop a column that was just there for debugging, it's not needed
	ze\dbAdm::revision(53900
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


//
//	Zenario 9.6
//

//In 9.6, we're changing the required/read only checkboxes of the dataset editor
//to be in line with User Forms: there will now be a selector with the values
//mandatory/read only/mandatory on condition/mandatory if visible.
//There will also be a further update in step 4 which addresses cases where a field was mandatory and read only
//at the same time. They will now be marked as read only.
);	ze\dbAdm::revision(58750
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]custom_dataset_fields`
	ADD COLUMN `mandatory_if_visible` tinyint(1) NOT NULL DEFAULT '0' AFTER `required`,
	ADD COLUMN `mandatory_condition_field_id` int(10) unsigned DEFAULT '0' AFTER `mandatory_if_visible`,
	ADD COLUMN `mandatory_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `mandatory_condition_field_id`,
	ADD COLUMN `mandatory_condition_checkboxes_operator` enum('AND', 'OR') NOT NULL DEFAULT 'AND' AFTER `mandatory_condition_invert`,
	ADD COLUMN `mandatory_condition_field_value` longtext CHARACTER SET [[ZENARIO_TABLE_CHARSET]] COLLATE [[ZENARIO_TABLE_COLLATION]] DEFAULT NULL AFTER `mandatory_condition_checkboxes_operator`,
	ADD COLUMN `visible_condition_field_id` int(10) unsigned DEFAULT '0',
	ADD COLUMN `visible_condition_invert` tinyint(1) NOT NULL DEFAULT 0 AFTER `visible_condition_field_id`,
	ADD COLUMN `visible_condition_field_value` varchar(255) DEFAULT NULL AFTER `visible_condition_invert`
_sql



);	ze\dbAdm::revision(59460
, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 ADD COLUMN `email_verified_temp` enum('verified', 'not_verified', 'email_not_set') DEFAULT 'email_not_set' AFTER `email_verified`
_sql

, <<<_sql
	 UPDATE `[[DB_PREFIX]]users`
	 SET email_verified_temp = IF(email_verified, 'verified', 'not_verified')
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 DROP COLUMN `email_verified`
_sql

, <<<_sql
	 ALTER TABLE `[[DB_PREFIX]]users`
	 CHANGE COLUMN `email_verified_temp` `email_verified` enum('verified', 'not_verified', 'email_not_set') DEFAULT 'email_not_set'
_sql

);