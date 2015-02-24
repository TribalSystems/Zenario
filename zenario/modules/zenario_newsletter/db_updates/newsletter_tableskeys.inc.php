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


//Add some missing keys
revision( 1
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `username`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `email`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `email_sent`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `time_received`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP INDEX `time_clicked_through`
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `username` (`newsletter_id`, `username`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email` (`newsletter_id`, `email`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email_sent` (`newsletter_id`, `email_sent`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `time_received` (`newsletter_id`, `time_received`)
_sql

, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `time_clicked_through` (`newsletter_id`, `time_clicked_through`)
_sql

);

revision( 49
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	ADD INDEX `email_overridden_by` (`newsletter_id`, `email_overridden_by`)
_sql

);

revision( 117
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]][[ZENARIO_NEWSLETTER_PREFIX]]newsletter_user_link`
	DROP COLUMN `time_received`
_sql

);
?>