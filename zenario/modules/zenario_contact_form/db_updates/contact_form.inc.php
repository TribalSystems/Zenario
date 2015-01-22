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
revision(13,
 "INSERT IGNORE 
 		[[DB_NAME_PREFIX]]email_templates
 		(
			`code`,
 			`template_name`,
 			`subject`,
 			`email_address_from`,
 			`email_name_from`,
 			`body`,
 			`date_created`,
 			`created_by_id`,
 			`allow_attachments`
 		)
	VALUES 
		(
			'zenario_contact_form__to_admin_contact_form_en',
			'To Admin: Contact form',
			'Contact form',
			'" . sqlEscape(setting('email_address_from')) . "',
			'" . sqlEscape(setting('email_name_from')) . "',
			'<p>Dear Admin,</p>
			<p>A contact form submission has been made with the following details:</p>
			<p>[[Name_field_label]] [[Name_field]]<br />
			[[Email_field_label]] [[Email_field]]<br />
			[[Phone_field_label]] [[Phone_field]]<br />
			[[Additional_field_1_label]] [[Additional_field_1]]<br />
			[[Additional_field_2_label]] [[Additional_field_2]]<br />
			[[Additional_field_3_label]] [[Additional_field_3]]<br />
			[[Additional_field_4_label]] [[Additional_field_4]]<br />
			[[Additional_field_5_label]] [[Additional_field_5]]<br />
			[[Additional_field_6_label]] [[Additional_field_6]]<br />
			[[Textarea_field_label]]<br />[[Textarea_field]]</p>
			<p>&nbsp;</p>
			<p>This is an auto-generated email from [[cms_url]] .</p>',
			NOW(),
			 " .(int)$_SESSION['admin_userid'] . ",
			0
		)"
);


?>