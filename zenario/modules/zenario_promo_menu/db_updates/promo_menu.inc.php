<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

ze\dbAdm::revision(3

, <<<_sql
	DROP TABLE IF EXISTS
	`[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image`
_sql

, <<<_sql
	CREATE TABLE
	`[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` (
		`node_id` int(10) unsigned NOT NULL,
		`use_feature_image` tinyint(1) DEFAULT 0 NOT NULL,
		`image_id` int(10) unsigned NOT NULL,
		`canvas` enum('unlimited', 'fixed_width', 'fixed_height', 'fixed_width_and_height') DEFAULT 'unlimited' NOT NULL,
		`width` int(10) unsigned NOT NULL,
		`height` int(10) unsigned NOT NULL,
		`use_rollover_image` tinyint(1) DEFAULT 0 NOT NULL,
		`rollover_image_id` int(10) unsigned NOT NULL,
		`title` varchar(255) DEFAULT '' NOT NULL,
		`text` text,
		`link_type` enum('no_link', 'content_item', 'external_url') DEFAULT 'no_link' NOT NULL,
		`link_visibility` enum('always_show', 'private', 'logged_out', 'logged_in') DEFAULT 'always_show' NOT NULL,
		`dest_url` varchar(255) DEFAULT '' NOT NULL,
		`open_in_new_window` tinyint(1) DEFAULT 0 NOT NULL,
		PRIMARY KEY (`node_id`),
		KEY (`image_id`)
	) ENGINE=[[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(4

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	ADD COLUMN `offset` int(10) unsigned NOT NULL AFTER `height`
_sql

); ze\dbAdm::revision(5

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	CHANGE `canvas` `canvas` enum('unlimited', 'fixed_width', 'fixed_height', 'fixed_width_and_height', 'resize_and_crop') DEFAULT 'unlimited' NOT NULL
_sql

); ze\dbAdm::revision(6

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	CHANGE `offset` `offset` int(10) NOT NULL
_sql

); ze\dbAdm::revision(10

, <<<_sql
	ALTER TABLE [[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	ADD COLUMN `overwrite_alt_tag` varchar(255) DEFAULT NULL
_sql

//Attempt to convert some columns with a utf8-3-byte character set to a 4-byte character set
);	ze\dbAdm::revision( 20
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` SET `overwrite_alt_tag` = SUBSTR(`overwrite_alt_tag`, 1, 250) WHERE CHAR_LENGTH(`overwrite_alt_tag`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` MODIFY COLUMN `overwrite_alt_tag` varchar(250) CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` MODIFY COLUMN `text` text CHARACTER SET utf8mb4 NULL
_sql
, <<<_sql
	UPDATE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` SET `title` = SUBSTR(`title`, 1, 250) WHERE CHAR_LENGTH(`title`) > 250
_sql
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` MODIFY COLUMN `title` varchar(250) CHARACTER SET utf8mb4 NOT NULL default ''
_sql

);	ze\dbAdm::revision( 22
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image`
	ADD COLUMN `feature_image_is_retina` tinyint(1) DEFAULT 0 NOT NULL AFTER `image_id`
_sql

);