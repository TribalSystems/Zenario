<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

revision(37, "
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
    ) VALUES (
    	'zenario_comments__to_admin_comment_notification_en',
        'To Admin: Comment notification',
        'Comment notification',
        '". sqlEscape(setting('email_address_from')). "',
        '". sqlEscape(setting('email_name_from')). "',
        '<p>Dear Admin,</p>
        <p>The following comment has been made on the page entitled &quot;[[page_title]]&quot;:</p>
        <p>Comment:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_username]]</p>
        <p>Link:<br/><a href=\"[[link]]\">[[link]]</a></p>
        <p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>',
        NOW(),
        ". (int) session('admin_userid'). ",
        0
    )
");

revision(104, "
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
    ) VALUES (
    	'zenario_comments__comment_report',
        'To Admin: Reported User Comment',
        'Reported User Comment',
        '". sqlEscape(setting('email_address_from')). "',
        '". sqlEscape(setting('email_name_from')). "',
        '<p>Dear Admin,</p>
        <p>A visitor to your website has reported the following User-submitted comment as offensive:</p>
		<p><b>URL: </b><a href=\"[[link]]\">[[link]]</a></p>
		<p><b>Poster: </b>[[poster_screen_name]]</p>
		<p><b>Last updated by: </b>[[last_editor_screen_name]]</p>
		<p><b>Reported message: </b><br/><p>[[text_message]]</p></p>
		<p><b>Reported by: </b>[[reporter_screen_name]]</p>
		<p><b>Visitor\'s comments: </b><br/><p>[[report_message]]</p></p>
		<p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>
		',
        NOW(),
        ". (int) session('admin_userid'). ",
        0
    )
");

revision(107, "
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
    ) VALUES (
    	'zenario_comments__comment_awaiting_approval',
        'To Admin: Comment awaiting approval',
        'Comment awaiting approval',
        '". sqlEscape(setting('email_address_from')). "',
        '". sqlEscape(setting('email_name_from')). "',
        '<p>Dear Admin,</p>
        <p>The following comment on the page entitled &quot;[[page_title]]&quot; is awaiting approval:</p>
        <p>Comment:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_screen_name]]</p>
        <p>Link:<br/><a href=\"[[link]]\">[[link]]</a></p>
        <p>&nbsp;</p>
        <p>Please log in and approve or delete the comment.</p>
        <p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>',
        NOW(),
        ". (int) session('admin_userid'). ",
        0
    )
");