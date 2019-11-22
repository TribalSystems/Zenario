<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
ze\dbAdm::revision(1,
"INSERT IGNORE [[DB_PREFIX]]email_templates
(`code`,`template_name`,`subject`,`body`,`date_created`,`created_by_id`,`allow_attachments`)
VALUES 
('zenario_extranet_registration__to_user_email_verification_en',
 'To User: Email verification',
 'Account verification',
 '<p>Dear [[first_name]] [[last_name]],</p>
<p>Thank you for registering on <a href=\"[[cms_url]]\">[[cms_url]]</a> .</p>
<p>In order to complete your registration please click the link below to confirm your email address.</p>
<p><a href=\"[[email_confirmation_link]]\">[[email_confirmation_link]]</a></p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]] .</p>',
 NOW(),
 " .(int)$_SESSION['admin_userid'] . ",
0)"
,

"INSERT IGNORE [[DB_PREFIX]]email_templates
(`code`,`template_name`,`subject`,`body`,`date_created`,`created_by_id`,`allow_attachments`)
VALUES 
('zenario_extranet_registration__to_admin_user_signup_notification_en',
'To Admin: User signup notification',
'A new User has signed up',
'<p>Dear Admin,</p>
<p>A new User has signed up.</p>
<p>You can see the User\'s details in Organizer by clicking the link below:</p>
<p><a href=\"[[organizer_link]]\">[[organizer_link]]</a></p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]] .</p>',
 NOW(),
 " .(int)$_SESSION['admin_userid'] . ",
0)"
,

"INSERT IGNORE [[DB_PREFIX]]email_templates
(`code`,`template_name`,`subject`,`body`,`date_created`,`created_by_id`,`allow_attachments`)
VALUES 
('zenario_extranet_registration__to_user_account_activation_en',
'To User: Account activation',
'Your account is now active',
'<p>Dear [[first_name]] [[last_name]],</p>
<p>Your registration on <a href=\"[[cms_url]]\">[[cms_url]]</a> is now completed.</p>
<p>You can login to the website and access password protected areas.</p>
<p>To login please use the following credentials:</p>
<p>Screen name: [[screen_name]]</p>
<p>Email address: [[email]]</p>
<p>Password: [[password]]</p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]] .</p>',
 NOW(),
 " .(int)$_SESSION['admin_userid'] . ",
0)"
,

"INSERT IGNORE [[DB_PREFIX]]email_templates
(`code`,`template_name`,`subject`,`body`,`date_created`,`created_by_id`,`allow_attachments`)
VALUES 
('zenario_extranet_registration__to_admin_user_activation_notification_en',
'To Admin: User activation notification',
'User account activated',
'<p>Dear Admin,</p>
<p>The account for the User [[first_name]] [[last_name]] has been activated.</p>
<p>You can see the User\'s details in Organizer by clicking the link below:</p>
<p><a href=\"[[organizer_link]]\">[[organizer_link]]</a></p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]] .</p>',
 NOW(),
 " .(int)$_SESSION['admin_userid'] . ",
0)"

); ze\dbAdm::revision(82

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]codes`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]codes` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`code` varchar(255) NOT NULL UNIQUE,
		`description` text NULL
	)ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]code_groups`
_sql

, <<<_sql
	CREATE TABLE `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]code_groups` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`code_id` int(10) unsigned NOT NULL,
		`group_id` int(10) unsigned NOT NULL,
		UNIQUE KEY `code_group` (`code_id`,`group_id`)
	)ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql

); ze\dbAdm::revision(90,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_extranet_registration'
	WHERE `code` = 'zenario_extranet_registration__to_user_email_verification_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_extranet_registration'
	WHERE `code` = 'zenario_extranet_registration__to_admin_user_signup_notification_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_extranet_registration'
	WHERE `code` = 'zenario_extranet_registration__to_user_account_activation_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_extranet_registration'
	WHERE `code` = 'zenario_extranet_registration__to_admin_user_activation_notification_en'
_sql

); ze\dbAdm::revision(96,

<<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]codes`
_sql

, <<<_sql
	DROP TABLE IF EXISTS `[[DB_PREFIX]][[ZENARIO_EXTRANET_REGISTRATION_PREFIX]]code_groups`
_sql

);
