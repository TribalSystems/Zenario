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


//Create a table to store extra options for tabs
revision( 2
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`(
		`tab_id` int(10) unsigned NOT NULL,
		`visibility` enum('everyone','logged_out','logged_in','in_group','without_group','not_in_group','has_characteristic','without_characteristic','not_has_characteristic','call_static_method') NOT NULL default 'everyone',
		`group_id` int(10) unsigned NOT NULL default 0,
		`characteristic_id` int(10) unsigned NOT NULL default 0,
		`plugin_class` varchar(200) NOT NULL default '',
		`method_name` varchar(127) NOT NULL default '',
		PRIMARY KEY  (`tab_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add two extra columns for function parameters
);	revision( 22
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	ADD COLUMN `param_1` varchar(200) NOT NULL default ''
	AFTER `method_name`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	ADD COLUMN `param_2` varchar(200) NOT NULL default ''
	AFTER `param_1`
_sql

);	revision( 45
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	DROP COLUMN `group_id`
_sql


//Migrate characteristics to datasets/custom fields
);	revision( 47
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	CHANGE COLUMN `visibility` `visibility_old` enum('everyone','logged_out','logged_in','in_group','without_group','not_in_group','has_characteristic','without_characteristic','not_has_characteristic','call_static_method') NOT NULL default 'everyone'
_sql

);	revision( 48
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	ADD COLUMN `visibility` enum('everyone','logged_out','logged_in','logged_in_with_field','logged_in_without_field','without_field','call_static_method') NOT NULL default 'everyone'
	AFTER `visibility_old`
_sql

);	revision( 49
, <<<_sql
	UPDATE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	SET visibility =
		IF (visibility_old IN ('in_group','has_characteristic'), 'logged_in_with_field',
			IF (visibility_old IN ('without_group','without_characteristic'), 'logged_in_without_field',
				IF (visibility_old IN ('not_in_group','not_has_characteristic'), 'without_field',
					visibility_old
		)))
_sql

);	revision( 50
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	DROP COLUMN `visibility_old`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	CHANGE COLUMN `characteristic_id` `field_id` int(10) unsigned NOT NULL default 0
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	CHANGE COLUMN `plugin_class` `module_class_name` varchar(200) NOT NULL default ''
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX]]tabs`
	ADD COLUMN `field_value` varchar(255) NOT NULL default ''
	AFTER `field_id`
_sql
);