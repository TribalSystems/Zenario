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


switch ($path) {
	case 'zenario_email_template':
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			
			$key = ['template_name' => $values['meta_data/template_name']];
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$key['code'] = ['!' => $box['key']['id']];
			}
			
			if (ze\row::exists('email_templates', $key)) {
				$box['tabs']['meta_data']['errors'][] = ze\admin::phrase('Please ensure the Name you give this Email Template is Unique.');
			}
			
			$headersDays = $values['data_deletion/period_to_delete_log_headers'];
			$contentDays = $values['data_deletion/period_to_delete_log_content'];
			
			if ($values['data_deletion/delete_log_content_sooner']
				&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
					|| (is_numeric($headersDays) && $contentDays == 'never_delete')
				)
			) {
				$fields['data_deletion/period_to_delete_log_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
			}
			
			if ($values['meta_data/include_a_fixed_attachment'] == true && $values['meta_data/selected_attachment']) {
				$privacy = ze\row::get('documents', 'privacy', ['id' => $values['meta_data/selected_attachment']]);
				
				if ($privacy == 'offline') {
					$fields['meta_data/selected_attachment']['error'] = true;
				}
			}
			
		}
		break;
	case 'site_settings':
		if ($settingGroup == 'data_protection') {
			//Make sure you cannot ask content to be stored longer than headers
			$headersDays = $values['data_protection/period_to_delete_the_email_template_sending_log_headers'];
			$contentDays = $values['data_protection/period_to_delete_the_email_template_sending_log_content'];
			
			if ($values['data_protection/delete_form_response_log_content_sooner']
				&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
					|| (is_numeric($headersDays) && $contentDays == 'never_delete')
				)
			) {
				$fields['data_protection/period_to_delete_the_email_template_sending_log_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
			}
		}
		break;
		
}
