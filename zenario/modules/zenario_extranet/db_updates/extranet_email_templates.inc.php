<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

ze\dbAdm::revision(6,
	"INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`,
		`module_class_name`
	) VALUES 
		(
		 'zenario_users__to_user_account_activated',
		 'To User: Account activated',
		 'Your account on [[cms_url]] has been activated',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		<p>Your account has been activated and you can now log in using your email address and password.</p>
		<p>&nbsp;</p>
		<p>Click here to go to the site now: [[cms_url]]</p>',
		 NOW(),
		 " .(int) ($_SESSION['admin_userid'] ?? false) . ",
		 0,
		 1,
		 'zenario_extranet'
		)
"
); ze\dbAdm::revision(43, 
	"INSERT IGNORE [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`,
		`module_class_name`
	) VALUES (
		'zenario_users__inactive_user_short_time',
		'To User: Inactive User (Short Time)',
		'We\'ve missed you',
		'<p>Dear [[salutation]] [[first_name]] [[last_name]],</p> we\'ve not seen you in a while. <br> Did you know that you can do these cool features on the portal: <br> ..... <br> ..... <br> [[link]]',
		NOW(),
		". (int) ze\admin::id(). ",
		0,
		1,
		'zenario_extranet'
	)"

); ze\dbAdm::revision(44, 
	"INSERT IGNORE [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`,
		`module_class_name`
	) VALUES (
		'zenario_users__inactive_user_long_time',
		'To User: Inactive User (Long Time)',
		'We\'ve missed you',
		'<p>Dear [[salutation]] [[first_name]] [[last_name]],</p> we\'ve not seen you in a long time. <br> Did you know that you can do these cool features on the portal: <br> ..... <br> ..... <br> [[link]]',
		NOW(),
		". (int) ze\admin::id(). ",
		0,
		1,
		'zenario_extranet'
	)"

); ze\dbAdm::revision(119, 
	'DELETE FROM [[DB_PREFIX]]email_templates
	WHERE code IN ("zenario_users__inactive_user_long_time", "zenario_users__inactive_user_short_time")'
);

ze\dbAdm::revision(125,
	"INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`,
		`module_class_name`
	) VALUES 
		(
		 'zenario_users__to_user_password_reset',
		 'To User: Password reset by admin',
		 'Your account password on [[cms_url]] has been reset',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		<p>[[password_reset_message]]</p>
		<p>Click here to go to the site now: [[cms_url]]</p>',
		 NOW(),
		 " .(int) ($_SESSION['admin_userid'] ?? false) . ",
		 0,
		 1,
		 'zenario_extranet'
		)
"
);

ze\dbAdm::revision(126,
	"UPDATE [[DB_PREFIX]]email_templates
	SET `template_name` = 'To User: Account activated by admin'
	WHERE `code` = 'zenario_users__to_user_account_activated'
	AND `template_name` = 'To User: Account activated'
"
);

ze\dbAdm::revision(127,
	"INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`,
		`module_class_name`
	) VALUES 
		(
		 'zenario_extranet__to_user_email_verification_admin',
		 'To User: Email verification by admin',
		 'Email address verification',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		<p>Please click the button below to confirm your email address on <a href=\"[[cms_url]]\">[[cms_url]]</a>.</p>
		<p>&nbsp;</p>
		<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[email_confirmation_link]]\">CONFIRM EMAIL</a></p>
		<p>&nbsp;</p>
		<p>If the above link doesn\'t work, copy the following link and paste it into your browser:</p>
		<p><a href=\"[[email_confirmation_link]]\">[[email_confirmation_link]]</a></p>
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[cms_url]] .</p>',
		 NOW(),
		 " .(int) ($_SESSION['admin_userid'] ?? false) . ",
		 0,
		 1,
		 'zenario_extranet'
		)
"
);

ze\dbAdm::revision(128,
	"UPDATE [[DB_PREFIX]]email_templates
	SET `body` = '<p>Dear [[first_name]] [[last_name]],</p>
		<p>Please click the button below to confirm your email address on <a href=\"[[cms_url]]\">[[cms_url]]</a>.</p>
		<p>&nbsp;</p>
		<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[email_confirmation_link]]\">CONFIRM EMAIL</a></p>
		<p>&nbsp;</p>
		<p>If the above link doesn\'t work, copy the following link and paste it into your browser:</p>
		<p><a href=\"[[email_confirmation_link]]\">[[email_confirmation_link]]</a></p></p>'
	WHERE `code` = 'zenario_extranet__to_user_email_verification_admin'
	AND `template_name` = 'To User: Email verification by admin'
"
);