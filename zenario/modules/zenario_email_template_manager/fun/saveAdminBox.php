<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
		
		$values['meta_data/body'] = ze\ring::sanitiseWYSIWYGEditorHTML($values['meta_data/body'], true);
	
		//Try and ensure that we use absolute URLs where possible
		ze\contentAdm::addAbsURLsToAdminBoxField($fields['meta_data/body']);
		
		
		$files = [];
		$columns = [];
		
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['template_name'] = $values['meta_data/template_name'];
			$columns['subject'] = $values['meta_data/subject'];
			
			if ($values['meta_data/from_details'] == "site_settings"){
				$columns['email_address_from'] = ze::setting('email_address_from');
				$columns['email_name_from'] = ze::setting('email_name_from');
			}else{
				$columns['email_address_from'] = $values['meta_data/email_address_from'];
				$columns['email_name_from'] = $values['meta_data/email_name_from'];
			}
			
			$columns['from_details'] = $values['meta_data/from_details'];
			
			if ($values['meta_data/mode'] == 'debug') {
				$columns['debug_override'] = true;
				$columns['debug_email_address'] = $values['meta_data/debug_email_address'];
			} else {
				$columns['debug_override'] = false;
				$columns['debug_email_address'] = '';
			}

			$columns['cc_email_address'] =
				($columns['send_cc'] = $values['meta_data/send_cc'])? $values['meta_data/cc_email_address'] : '';

			$columns['bcc_email_address'] =
				($columns['send_bcc'] = $values['meta_data/send_bcc'])? $values['meta_data/bcc_email_address'] : '';
				
			$columns['include_a_fixed_attachment'] = $values['meta_data/include_a_fixed_attachment'];
			$columns['selected_attachment'] = ($values['meta_data/include_a_fixed_attachment']) ? $values['meta_data/selected_attachment'] : false;
			$columns['allow_visitor_uploaded_attachments'] = $values['meta_data/allow_visitor_uploaded_attachments'];
			$columns['when_sending_attachments'] = $values['meta_data/when_sending_attachments'];
			
			
			switch ($values['meta_data/use_standard_email_template']) {
				case 'no':
					$columns['use_standard_email_template'] = 0;
					break;
				case 'yes':
					$columns['use_standard_email_template'] = 1;
					break;
				case 'twig':
					$columns['use_standard_email_template'] = 2;
					break;
			}
			
			$columns['body'] = $values['meta_data/body'];
			$htmlChanged = false;
			ze\file::addImageDataURIsToDatabase($columns['body'], ze\link::absolute());
			ze\contentAdm::syncInlineFileLinks($files, $columns['body'], $htmlChanged);
		}
		
		if (ze\ring::engToBoolean($box['tabs']['data_deletion']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['period_to_delete_log_headers'] = $values['data_deletion/period_to_delete_log_headers'];
			if ($values['data_deletion/period_to_delete_log_headers'] && $values['data_deletion/delete_log_content_sooner']) {
				$columns['period_to_delete_log_content'] = $values['data_deletion/period_to_delete_log_content'];
			} else {
				$columns['period_to_delete_log_content'] = '';
			}
		}
		
		if (ze\ring::engToBoolean($box['tabs']['advanced']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['head'] = $values['advanced/head'];
		}
		
		if (!empty($columns)) {
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$columns['date_modified'] = ze\date::now();
				$columns['modified_by_id'] = ze\admin::id();
				ze\row::update('email_templates', $columns, ['code' => $box['key']['id']]);
			} else {
				$columns['date_created'] = ze\date::now();
				$columns['created_by_id'] = ze\admin::id();
				$columns['code'] = microtime(). session_id();
				$box['key']['id'] = $box['key']['numeric_id'] = ze\row::insert('email_templates', $columns);
				ze\row::update('email_templates', ['code' => $box['key']['id']], ['id' => $box['key']['id']]);
			}
		}
		
		//Record the images used in this email template.
		$key = ['foreign_key_to' => 'email_template', 'foreign_key_id' => $box['key']['numeric_id'], 'foreign_key_char' => $box['key']['id']];
		ze\contentAdm::syncInlineFiles($files, $key, $keepOldImagesThatAreNotInUse = false);
		
		break;
}
