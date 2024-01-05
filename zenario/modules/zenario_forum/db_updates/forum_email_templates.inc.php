<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
    	'zenario_forum__to_admin_new_thread_notification_en',
        'To Admin: New thread notification',
        'New thread notification',
        '<p>Dear Admin,</p>
        <p>A new thread has been created in the forum entitled &quot;[[forum_title]]&quot;:</p>
        <p>Title:<br/>[[thread_title]]</p>
        <p>Message:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_username]]</p>
        <p>Link to Forum:<br/><a href=\"[[forum_link]]\">[[forum_link]]</a></p>
        <p>Link to Thread:<br/><a href=\"[[thread_link]]\">[[thread_link]]</a></p>
        <p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )

", "

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
    	'zenario_forum__to_admin_post_notification_en',
        'To Admin: Post notification',
        'Post notification',
        '<p>Dear Admin,</p>
        <p>The following message has been left on the thread entitled &quot;[[thread_title]]&quot; in the &quot;[[forum_title]]&quot; forum:</p>
        <p>Message:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_username]]</p>
        <p>Link to Forum:<br/><a href=\"[[forum_link]]\">[[forum_link]]</a></p>
        <p>Link to Thread:<br/><a href=\"[[thread_link]]\">[[thread_link]]</a></p>
        <p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )

", "

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
    	'zenario_forum__new_thread_subs_en',
        'To User: New thread notification',
        'New thread notification',
        '<p>Dear [[subscriber_screen_name]],</p>
        <p>You are subscribed to new threads in the forum entitled &quot;[[forum_title]]&quot;,
        	and asked to be notified when a new thread is posted in that forum.</p>
        <p>Since your last visit a thread has been posted as follows:</p>
        <p>Title:<br/>[[thread_title]]</p>
        <p>Message:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_screen_name]]</p>
        <p>Link to Forum:<br/><a href=\"[[forum_link]]\">[[forum_link]]</a></p>
        <p>Link to Thread:<br/><a href=\"[[thread_link]]\">[[thread_link]]</a></p>
        <p>&nbsp;</p>
        <p>Further new threads may have been posted since this email was sent.</p>
        <p><small>This is an auto-generated email. Please visit [[cms_url]] to change your notification settings.</small></p>',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )

", "

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
    	'zenario_forum__post_subs_en',
        'To User: Reply notification',
        'Reply notification',
        '<p>Dear [[subscriber_screen_name]],</p>
        <p>You are subscribed to the thread entitled &quot;[[thread_title]]&quot; in the &quot;[[forum_title]]&quot; forum,
        	and asked to be notified when a message is posted in that thread.</p>
        <p>Since your last visit a message has been posted as follows:</p>
        <p>Message:<br/>[[message]]</p>
        <p>Posted By:<br/>[[poster_screen_name]]</p>
        <p>Link to Forum:<br/><a href=\"[[forum_link]]\">[[forum_link]]</a></p>
        <p>Link to Thread:<br/><a href=\"[[thread_link]]\">[[thread_link]]</a></p>
        <p>&nbsp;</p>
        <p>Further messages may have been posted since this email was sent.</p>
        <p><small>This is an auto-generated email. Please visit [[cms_url]] to change your notification settings.</small></p>',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )
");

ze\dbAdm::revision(107, "
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
    	'zenario_forum__post_report',
        'To Admin: Reported User Forum Post',
        'Reported User Post',
        '<p>Dear Admin,</p>
        <p>A visitor to your website has reported the following User-submitted forum post as offensive:</p>
		<p><b>URL: </b><a href=\"[[link]]\">[[link]]</a></p>
		<p><b>Poster: </b>[[poster_screen_name]]</p>
		<p><b>Last updated by: </b>[[last_editor_screen_name]]</p>
		<p><b>Reported post: </b><br/><p>[[text_message]]</p></p>
		<p><b>Reported by: </b>[[reporter_screen_name]]</p>
		<p><b>Reporter\'s comments: </b><br/><p>[[report_message]]</p></p>
		<p>&nbsp;</p>
        <p><small>This is an auto-generated email from [[cms_url]].</small></p>
		',
        NOW(),
        ". (int) ($_SESSION['admin_userid'] ?? false). ",
        0,
        1
    )
");

ze\dbAdm::revision(140,

<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_forum'
	WHERE `code` = 'zenario_forum__to_admin_new_thread_notification_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_forum'
	WHERE `code` = 'zenario_forum__to_admin_post_notification_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_forum'
	WHERE `code` = 'zenario_forum__new_thread_subs_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_forum'
	WHERE `code` = 'zenario_forum__post_subs_en'
_sql

,<<<_sql
	UPDATE [[DB_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_forum'
	WHERE `code` = 'zenario_forum__post_report'
_sql

);
