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

revision(6, "
	INSERT IGNORE INTO [[DB_NAME_PREFIX]]email_templates (
		`code`,
		`template_name`,
		`subject`,
		`email_address_from`,
		`email_name_from`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`
	) VALUES 
		(
		 'zenario_users__to_user_account_activated',
		 'To User: Account Activated',
		 'Your account on [[cms_url]] has been activated',
		 '" . sqlEscape(setting('email_address_from')) . "',
		 '" . sqlEscape(setting('email_name_from')) . "',
		 '<p>Dear [[first_name]] [[last_name]],</p>
		<p>Your account has been activated and you can now login to the extranet area using the following details:</p>
		<p>screen name: [[screen_name]]<br /> 
		email: [[email]]<br /> 
		password: [[password]]</p>
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[cms_url]] .</p>',
		 NOW(),
		 " .(int) session('admin_userid') . ",
		 0
		)
		, 
		(
		 'zenario_users__to_user_account_suspended',
		 'To User: Account Suspended',
		 'Your account on [[cms_url]] has been suspended',
		 '" . sqlEscape(setting('email_address_from')) . "',
		 '" . sqlEscape(setting('email_name_from')) . "',
		'<p>Dear [[first_name]] [[last_name]],</p>
		<p>Your account has been suspended. You will no longer be able to access the extranet area of our website.</p>
		<p>If you feel this suspension has been made in error, please contact us.</p> 
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[cms_url]] .</p>',
		 NOW(),
		 " .(int) session('admin_userid') . ",
		 0
		)
");
