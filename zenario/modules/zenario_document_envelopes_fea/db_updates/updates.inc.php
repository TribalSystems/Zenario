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

ze\dbAdm::revision(1
, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`code` varchar(255) DEFAULT NULL,
		`name` varchar(255) DEFAULT NULL,
		`description` varchar(255) DEFAULT NULL,
		`keywords` varchar(255) DEFAULT NULL,
		`thumbnail_id` int(10) unsigned DEFAULT NULL,
		`created` datetime DEFAULT NULL,
		`created_admin_id` int(10) unsigned DEFAULT NULL,
		`created_user_id` int(10) unsigned DEFAULT NULL,
		`created_username` varchar(255) DEFAULT NULL,
		`last_edited` datetime DEFAULT NULL,
		`last_edited_admin_id` int(10) unsigned DEFAULT NULL,
		`last_edited_user_id` int(10) unsigned DEFAULT NULL,
		`last_edited_username` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `code` (`code`),
		KEY `name` (`name`)
	) ENGINE = [[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]documents_in_envelope`(
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`file_id` int(10) unsigned DEFAULT NULL,
		`envelope_id` int(10) unsigned DEFAULT NULL,
		`name` varchar(255) DEFAULT NULL,
		PRIMARY KEY (`id`)
	) ENGINE = [[ZENARIO_TABLE_ENGINE]] DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(3
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]documents_in_envelope`
	CHANGE `name` `file_format` varchar(10) DEFAULT NULL
_sql

); ze\dbAdm::revision(5
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes`
	ADD COLUMN `language_id` varchar(10) DEFAULT NULL AFTER `thumbnail_id`
_sql

); ze\dbAdm::revision(7
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]documents_in_envelope`
	ADD COLUMN `created` datetime DEFAULT NULL,
	ADD COLUMN `created_admin_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `created_user_id` int(10) unsigned DEFAULT NULL,
	ADD COLUMN `created_username` varchar(255) DEFAULT NULL
_sql

); ze\dbAdm::revision(8
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]documents_in_envelope`
	ADD COLUMN `thumbnail_file_id` int(10) unsigned DEFAULT NULL
_sql

); ze\dbAdm::revision(9
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]documents_in_envelope`
	DROP COLUMN `thumbnail_file_id`
_sql

); ze\dbAdm::revision(10
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes`
	ADD COLUMN `file_formats` varchar(255) DEFAULT NULL AFTER `language_id`
_sql

); ze\dbAdm::revision(11
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]][[ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX]]document_envelopes`
	CHANGE `description` `description` text DEFAULT NULL
_sql

);