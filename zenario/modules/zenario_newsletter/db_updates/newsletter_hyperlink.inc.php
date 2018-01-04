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


revision( 108

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`newsletter_id` int(10) NOT NULL,
		`link_ordinal` int(10) NOT NULL,
		`text_or_image` enum('text','image') NOT NULL,
		`link_text` varchar(100) NULL,
		`hyperlink` varchar(100) NOT NULL,
		`clickthrough_count` int(10) NOT NULL default 0,
		`last_clicked_date` datetime NULL,
		PRIMARY KEY  (`id`),
		INDEX (`newsletter_id`),
		INDEX (`link_ordinal`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);

revision( 109

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		ADD COLUMN `hyperlink_hash` varchar(40) NOT NULL DEFAULT ''
	AFTER `id`
_sql
);

revision( 111

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `hyperlink` `hyperlink` varchar(255) NOT NULL
_sql
);


revision( 112

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `link_text` `link_text` varchar(255) NOT NULL
_sql
);

revision( 173

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `link_text` `link_text` text
_sql

);

revision( 181

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletters_hyperlinks`
		CHANGE COLUMN `hyperlink` `hyperlink` text
_sql

);
