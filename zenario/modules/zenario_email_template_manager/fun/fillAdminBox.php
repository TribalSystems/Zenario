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


switch ($path) {
	case 'zenario_email_template':
		
		if ($box['key']['id']) {
			$details = $this->getTemplateByCode($box['key']['id']);
			
			if (empty($details)) {
				exit;
			}
			
			if ($box['key']['duplicate']) {
				$box['title'] = adminPhrase('Duplicating the Email Template "[[template_name]]"', $details);
			} else {
				$box['title'] = adminPhrase('Viewing/Editing the Email Template "[[template_name]]"', $details);
			}
			
			$box['key']['numeric_id'] = $details['id'];
			
			$box['tabs']['meta_data']['fields']['template_name']['value'] = $details['template_name'];
			$box['tabs']['meta_data']['fields']['subject']['value'] = $details['subject'];
			$box['tabs']['meta_data']['fields']['email_address_from']['value'] = $details['email_address_from'];
			$box['tabs']['meta_data']['fields']['email_name_from']['value'] = $details['email_name_from'];
			$box['tabs']['body']['fields']['body']['value'] = $details['body'];
		
		} else {
			
			$box['title'] = adminPhrase('Creating an Email Template');
			$box['tabs']['meta_data']['fields']['email_address_from']['value'] = setting('email_address_from');
			$box['tabs']['meta_data']['fields']['email_name_from']['value'] = setting('email_name_from');
			
		}

		if ($box['key']['id']) {
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['path'] = 'zenario__email_template_manager/panels/email_templates/item_buttons/images//'. (int) $box['key']['id']. '//';
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['min_path'] =
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['max_path'] =
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['target_path'] = 'zenario__content/panels/email_images_for_email_templates';
		} else {
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['path'] =
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['min_path'] =
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['max_path'] =
			$box['tabs']['body']['fields']['body']['insert_image_button']['pick_items']['target_path'] = 'zenario__content/panels/email_images_shared';
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
