<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


//Create a table to store smart groups
revision( 136
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_groups`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]smart_groups`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(50) NOT NULL,
		`values` mediumtext,
		`created_on` datetime,
		`created_by` int(10) unsigned NOT NULL default 0,
		`last_modified_on` datetime,
		`last_modified_by` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `name` (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);	revision( 139
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]]smart_group_opt_outs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]]smart_group_opt_outs`(
		`smart_group_id` int(10) unsigned NOT NULL,
		`user_id` int(10) unsigned NOT NULL,
		`opted_out_on` datetime NOT NULL,
		`opt_out_method` varchar(20) NOT NULL,
		PRIMARY KEY  (`smart_group_id`, `user_id`),
		KEY (`opted_out_on`),
		KEY (`opt_out_method`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);


//Work around for a bug where the smart-group tables were not in the backups - recreate them if they are not there!
revision( 24340
, <<<_sql
	CREATE TABLE IF NOT EXISTS `[[DB_NAME_PREFIX]]smart_groups`(
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(50) NOT NULL,
		`values` mediumtext,
		`created_on` datetime,
		`created_by` int(10) unsigned NOT NULL default 0,
		`last_modified_on` datetime,
		`last_modified_by` int(10) unsigned NOT NULL default 0,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `name` (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	CREATE TABLE IF NOT EXISTS `[[DB_NAME_PREFIX]]smart_group_opt_outs`(
		`smart_group_id` int(10) unsigned NOT NULL,
		`user_id` int(10) unsigned NOT NULL,
		`opted_out_on` datetime NOT NULL,
		`opt_out_method` varchar(20) NOT NULL,
		PRIMARY KEY  (`smart_group_id`, `user_id`),
		KEY (`opted_out_on`),
		KEY (`opt_out_method`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);