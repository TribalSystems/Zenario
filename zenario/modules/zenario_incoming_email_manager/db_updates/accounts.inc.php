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


//Create a mirror table for jobs, with extra info for email accounts
	ze\dbAdm::revision( 4
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]accounts`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]accounts` (
		`job_id` int(10) unsigned NOT NULL,
		`script_enable` tinyint(1) unsigned NOT NULL default 0,
		`script_recipient_username` varchar(255) NULL,
		`fetch_enable` tinyint(1) unsigned NOT NULL default 0,
		`fetch_server` varchar(196) NULL,
		`fetch_username` varchar(128) NULL,
		`fetch_password` varchar(255) NOT NULL default '',
		`fetch_mailbox` varchar(255) NOT NULL default '',
		`fetch_keep_mail` tinyint(1) unsigned NOT NULL default 0,
		`fetch_processed_mailbox` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`job_id`),
		UNIQUE KEY (`script_recipient_username`),
		UNIQUE KEY (`fetch_server`, `fetch_username`),
		KEY (`script_enable`),
		KEY (`fetch_enable`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

//Add a new column for the error mailbox
);	ze\dbAdm::revision( 7
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]accounts`
	ADD COLUMN `fetch_error_mailbox` varchar(255) NOT NULL default ''
_sql


//Create a mirror table for job_logs, with extra info for emails
);	ze\dbAdm::revision( 9
, <<<_sql
	DROP TABLE IF EXISTS `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]account_logs`
_sql

, <<<_sql
	CREATE TABLE `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]account_logs` (
		`job_id` int(10) unsigned NOT NULL,
		`log_id` int(10) unsigned NOT NULL,
		`email_from` varchar(255) NOT NULL default '',
		`email_sent` datetime DEFAULT NULL,
		`email_subject` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`log_id`),
		UNIQUE KEY (`job_id`, `log_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

);
//drop unique key
ze\dbAdm::revision( 20
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_INCOMING_EMAIL_MANAGER_PREFIX]]accounts`
	DROP KEY `fetch_server`
_sql
);