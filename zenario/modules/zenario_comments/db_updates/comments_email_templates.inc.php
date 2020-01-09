<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

ze\dbAdm::revision(80, "
    INSERT IGNORE INTO [[DB_PREFIX]]email_templates (
    	`code`,
        `template_name`,
        `subject`,
        `body`,
        `date_created`,
        `created_by_id`,
        `allow_attachments`,
        `use_standard_email_template`
    ) VALUES (
    	'zenario_comments__comment_subs_en',
        'To User: Comment notification',
        'New comment notification',
        '<p>Dear [[subscriber_screen_name]],</p>
        <p>You are subscribed to the page entitled &quot;[[page_title]]&quot;,
        	and asked to be notified when a comment is posted on that page.</p>
        <p>Since your last visit a comment has been posted as follows:</p>
        <p>Comment:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_screen_name]]</p>
        <p>Link:<br/><a href=\"[[link]]\">[[link]]</a></p>
        <p>&nbsp;</p>
        <p>Further comments may have been posted since this email was sent.</p>
        <p><small>This is an auto-generated email. Please visit [[cms_url]] to change your notification settings.</small></p>',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )
");

ze\dbAdm::revision(140,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_comments'
	WHERE `code` = 'zenario_comments__comment_subs_en'
_sql

);