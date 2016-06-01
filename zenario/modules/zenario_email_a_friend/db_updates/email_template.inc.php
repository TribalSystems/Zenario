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
revision(1,
"
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
		 'zenario_email_a_friend__to_visitor_email_this_page_to_friend_en',
		 'To Visitor: Email this page to a friend',
		 'I thought you should see this web page!',
		 '" . sqlEscape(setting('email_address_from')) . "',
		 '[[sender_name]]',
		 '<p>Dear friend,</p>
		<p>I thought you should see this web page!</p>
		<p>Page title: [[page_title]]</p>
		<p>Page description: [[page_description]]
		<p>Link to page: <a href=\"[[link_to_page]]\">[[link_to_page]]</a></p>
		<p>This message was generated by: [[sender_name]], [[your_email]]</p>
		<p>A message from your friend [[sender_name]]: <br/>
			[[sender_message]]</p>
		<p>&nbsp;</p>
		<p>This is an auto-generated email from [[link_to_page]] .</p>',
		 NOW(),
		 " .(int)$_SESSION['admin_userid'] . ",
		 0
		)
"
); revision(3,

<<<_sql
	UPDATE [[DB_NAME_PREFIX]]email_templates
	SET `module_class_name` = 'zenario_email_a_friend'
	WHERE `code` = 'zenario_email_a_friend__to_visitor_email_this_page_to_friend_en'
_sql

);

?>