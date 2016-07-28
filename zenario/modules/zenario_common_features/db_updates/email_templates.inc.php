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

//Add the email templates that this module uses by default.
//Currently there's no nice way to do this using an XML or YAML file, so they need to be
//added into the database using a SQL statement.

revision(195, "
	INSERT INTO [[DB_NAME_PREFIX]]email_templates (
		module_class_name,
		`code`,
		template_name,
		`subject`,
		email_address_from,
		email_name_from,
		`body`,
		date_created,
		created_by_id,
		allow_attachments
	) VALUES (
		'zenario_common_features',
		'zenario_common_features__notification_to_new_admin',
		'Notification to new Admin',
		'Your Zenario administrator account',
		'". sqlEscape(setting('email_address_from')). "',
		'". sqlEscape(setting('email_name_from')). "',
		'<h1>Administrator account created</h1>
<p>Dear&nbsp;[[first_name]] [[last_name]],</p>
<p><br>You have been created an administrator account on [[cms_url]].</p>
<p>Here are your administrator login details:</p>
<p>username: [[username]]</p>
<p>password: [[password]]</p>
<p>&nbsp;</p>
<p>What to do now:</p>
<p>1) Please follow this link to the Admin area of your site: [[cms_url]]admin</p>
<p>2) Follow the login procedure using the provided&nbsp;username and password.</p>
<p>3) Start administering your site!</p>
<p>Remember that you can always log in with administrator access by putting \"/admin\" after your site\'s domain name. Also please note that this is an Administrator login, not an Extranet User login (if the site has a password-protected area for visitors).</p>
<p>&nbsp;</p>
<p>This is an auto-generated email from [[cms_url]]&nbsp;, please do not try to reply.<br> <br> Thank you.&nbsp;</p>',
		NOW(),
		". (int) adminId(). ",
		0
	)
	ON DUPLICATE KEY UPDATE
		module_class_name = VALUES(module_class_name),
		`subject` = VALUES(`subject`),
		`body` = VALUES(`body`)
"
);
