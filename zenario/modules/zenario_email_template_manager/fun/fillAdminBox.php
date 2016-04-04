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


switch ($path) {
	case 'zenario_email_template':
		
		if ($box['key']['id']) {
			$details = $this->getTemplateByCode($box['key']['id']);
			
			if (empty($details)) {
				exit;
			}
			
			if ($box['key']['duplicate']) {
				$box['title'] = adminPhrase('Duplicating the email template "[[template_name]]"', $details);
				unset($box['title_for_existing_records']);
				unset($box['identifier']);
			} else {
				$box['identifier']['value'] = $details['id'];
			}
			$box['key']['numeric_id'] = $details['id'];
			
			
			
			$values['meta_data/template_name'] = $details['template_name'];
			$values['meta_data/subject'] = $details['subject'];
			$values['meta_data/email_address_from'] = $details['email_address_from'];
			$values['meta_data/email_name_from'] = $details['email_name_from'];
			$values['meta_data/debug_override'] = $details['debug_override'];
			$values['meta_data/debug_email_address'] = $details['debug_email_address'];
			$values['meta_data/send_cc'] = $details['send_cc'];
			$values['meta_data/cc_email_address'] = $details['cc_email_address'];
			$values['meta_data/send_bcc'] = $details['send_bcc'];
			$values['meta_data/bcc_email_address'] = $details['bcc_email_address'];
			$values['body/body'] = $details['body'];
		
		} else {
			$values['meta_data/email_address_from'] = setting('email_address_from');
			$values['meta_data/email_name_from'] = setting('email_name_from');
		}
		
		$style_formats = siteDescription('email_style_formats');
		if (!empty($style_formats)) {
			$box['tabs']['body']['fields']['body']['editor_options']['style_formats'] = $style_formats;
			$box['tabs']['body']['fields']['body']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
		
		break;
		
	case 'zenario_email_log_view':
		if ($box['key']['id']) {
			$logRecord = self::getLogRecordById($box['key']['id']);
			if(count($logRecord)) {
				$box['title'] = 'Email sent to "' .  htmlspecialchars(arrayKey($logRecord,'email_address_to')) . '" on ' .  formatDateNicely(arrayKey($logRecord,'sent_datetime')) . ' ' . formatTimeNicely(arrayKey($logRecord,'sent_datetime'),setting('vis_time_format'),'');
				$values['email_template_name'] = $logRecord['email_template_name'];
				$values['email_subject'] = $logRecord['email_subject'];
				$values['email_address_to'] = $logRecord['email_address_to'];
				$values['email_address_replyto'] = $logRecord['email_address_replyto'];
				$values['email_address_from'] = $logRecord['email_address_from'];
				$values['email_name_from'] = $logRecord['email_name_from'];
				
				$box['tabs']['email']['fields']['email_body_non_escaped']['snippet']['html'] = $logRecord['email_body'];
			}
		}
		break;
}
