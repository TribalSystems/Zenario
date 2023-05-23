<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

//Add the email templates that this module uses by default.
//Currently there's no nice way to do this using an XML or YAML file, so they need to be
//added into the database using a SQL statement.

ze\dbAdm::revision(217, "
	INSERT INTO [[DB_PREFIX]]email_templates (
		module_class_name,
		`code`,
		template_name,
		`subject`,
		`body`,
		date_created,
		created_by_id,
		allow_attachments,
		use_standard_email_template
	) VALUES (
		'zenario_common_features',
		'zenario_common_features__notification_to_new_admin_no_password',
		'Notification to new Admin (no password)',
		'Your Zenario administrator account',
		'<h1>Administrator account created</h1>
<p>Dear&nbsp;[[first_name]] [[last_name]],</p>
<p>An administator account has been created for you in the site at [[cms_url]].</p>
<p>Your administrator username is <strong>[[username]]</strong>.</p>

<p>&nbsp;</p>
<p>What to do now:</p>
<p>1) Follow this link to create a password: [[new_admin_cms_url]]</p>
<p>2) Login and start administering your site!</p>
<p>Remember that you can always log in with administrator access by putting \"/admin\" after your site\'s domain name. Also please note that this is an Administrator login, not an Extranet User login (if the site has a password-protected area for visitors).</p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]]&nbsp;, please do not try to reply.<br> <br> Thank you.&nbsp;</p>',
		NOW(),
		". (int) ze\admin::id(). ",
		0,
		1
	)
	ON DUPLICATE KEY UPDATE
		module_class_name = VALUES(module_class_name),
		`subject` = VALUES(`subject`),
		`body` = VALUES(`body`)
",

<<<_sql
	UPDATE [[DB_PREFIX]]site_settings
	SET value = "zenario_common_features__notification_to_new_admin_no_password"
	WHERE name = "notification_to_new_admin"
_sql
,
	"
	INSERT INTO [[DB_PREFIX]]email_templates (
		module_class_name,
		`code`,
		template_name,
		`subject`,
		`body`,
		date_created,
		created_by_id,
		allow_attachments,
		use_standard_email_template
	) VALUES (
		'zenario_common_features',
		'zenario_common_features__to_admin_contact_form_submission',
		'To Admin: Contact Form Submission',
		'Contact Form Submission',
		'<p>Dear Admin,</p>
<p>A Contact Us popup form submission has been made with the following details:</p>
<p><strong>Name:</strong> [[first_name]] [[last_name]]</p>
<p><strong>Email:</strong> [[email]]</p>
<p><strong>Message:</strong></p>
<p>[[unlinked_textarea_4]]</p>
<p><strong>User has accepted terms and conditions:</strong> [[terms_and_conditions_accepted]]</p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]] .</p>',
		NOW(),
		". (int) ze\admin::id(). ",
		0,
		1
	)
	ON DUPLICATE KEY UPDATE
		module_class_name = VALUES(module_class_name),
		`subject` = VALUES(`subject`),
		`body` = VALUES(`body`)
	"

); ze\dbAdm::revision(228,
<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `body` = '<h1>Administrator account created</h1>
		<p>Dear&nbsp;[[first_name]] [[last_name]],</p>
		<p><br>An administator account has been created for you in the site at [[cms_url]].</p>
		<p>Your administrator username is <strong>[[username]]</strong>.</p>

		<p>&nbsp;</p>
		<p>What to do now:</p>
		<p>1) Follow this link to create a password:</p>
		<br /><p style="text-align: center;"><a style="background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;" href="[[new_admin_cms_url]]">CREATE A PASSWORD</a></p><br />
		<p>2) Login and start administering your site!</p>
		<p>Remember that you can always log in with administrator access by putting \"/admin\" after your site\'s domain name. Also please note that this is an Administrator login, not an Extranet User login (if the site has a password-protected area for visitors).</p>
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[cms_url]]&nbsp;, please do not try to reply.<br> <br> Thank you.&nbsp;</p>'
	WHERE `code` = "zenario_common_features__notification_to_new_admin_no_password"
_sql

); ze\dbAdm::revision(246,
<<<_sql
	DELETE FROM [[DB_PREFIX]]email_templates
	WHERE `code` = "zenario_common_features__notification_to_new_admin_no_password"
_sql
,

<<<_sql
	DELETE FROM [[DB_PREFIX]]site_settings
	WHERE name = "notification_to_new_admin"
_sql

);
