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

revision(3

, <<<_sql
	DROP TABLE IF EXISTS
	`[[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image`
_sql

, <<<_sql
	CREATE TABLE
	`[[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image` (
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
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); revision(4

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	ADD COLUMN `offset` int(10) unsigned NOT NULL AFTER `height`
_sql

); revision(5

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	CHANGE `canvas` `canvas` enum('unlimited', 'fixed_width', 'fixed_height', 'fixed_width_and_height', 'resize_and_crop') DEFAULT 'unlimited' NOT NULL
_sql

); revision(6

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	CHANGE `offset` `offset` int(10) NOT NULL
_sql

); revision(10

, <<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_PROMO_MENU_PREFIX]]menu_node_feature_image
	ADD COLUMN `overwrite_alt_tag` varchar(255) DEFAULT NULL
_sql

);