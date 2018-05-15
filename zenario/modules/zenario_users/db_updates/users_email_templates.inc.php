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

ze\dbAdm::revision(6, "
	INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`
	) VALUES 
		(
		 'zenario_users__to_user_account_activated',
		 'To User: Account Activated',
		 'Your account on [[cms_url]] has been activated',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		<p>Your account has been activated and you can now login to the extranet area using the following details:</p>
		<p>screen name: [[screen_name]]<br /> 
		email: [[email]]<br /> 
		password: [[password]]</p>
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[cms_url]] .</p>',
		 NOW(),
		 " .(int) ($_SESSION['admin_userid'] ?? false) . ",
		 0
		)
"
); ze\dbAdm::revision(40,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_users'
	WHERE `code` = 'zenario_users__to_user_account_activated'
_sql

);


ze\dbAdm::revision(43, 
	"INSERT IGNORE [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`
	) VALUES (
		'zenario_users__inactive_user_short_time',
		'To User: Inactive User (Short Time)',
		'We\'ve missed you',
		'<p>Dear [[salutation]] [[first_name]] [[last_name]],</p> we\'ve not seen you in a while. <br> Did you know that you can do these cool features on the portal: <br> ..... <br> ..... <br> [[link]]',
		NOW(),
		". (int) ze\admin::id(). ",
		0
	)"
	);
	
ze\dbAdm::revision(44, 
	"INSERT IGNORE [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`
	) VALUES (
		'zenario_users__inactive_user_long_time',
		'To User: Inactive User (Long Time)',
		'We\'ve missed you',
		'<p>Dear [[salutation]] [[first_name]] [[last_name]],</p> we\'ve not seen you in a long time. <br> Did you know that you can do these cool features on the portal: <br> ..... <br> ..... <br> [[link]]',
		NOW(),
		". (int) ze\admin::id(). ",
		0
	)"
);

ze\dbAdm::revision(59,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_users'
	WHERE `code` = 'zenario_users__inactive_user_long_time'
	OR `code` = 'zenario_users__inactive_user_short_time'
_sql

);
