<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

ze\dbAdm::revision(12, "
	INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
		`module_class_name`,
		`code`,
		`template_name`,
		`subject`,
		`body`,
		`date_created`,
		`created_by_id`,
		`allow_attachments`,
		`use_standard_email_template`
	) VALUES (
		'zenario_user_consent_forms',
		'zenario_user_consent_forms__reconfirm_consent_notification_en',
		'To User: We need your permission' ,
		'We need your permission',
		'<h1>We need your permission</h1>
		<p>Dear [[first_name]] [[last_name]],</p>

        The law is changing, and in order to maintain your data on this website we need you to reconfirm that we may do this. To reconfirm, please click here:<br /> 

        [[content_item]]<br /> 

        <p>This is an auto-generated email from [[cms_url]].</p>',
		 NOW(),
		". (int) ze\admin::id(). ",
		0,
		1
	)
"
);

ze\dbAdm::revision(12,

 <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	ADD COLUMN `consent_hash` varchar(28) NULL
		
_sql

);

ze\dbAdm::revision(14,

 <<<_sql
	ALTER TABLE [[DB_PREFIX]]users 
	MODIFY COLUMN `consent_hash` varchar(35) NULL
		
_sql

);


