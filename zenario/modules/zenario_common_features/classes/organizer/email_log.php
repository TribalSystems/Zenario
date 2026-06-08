<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class zenario_common_features__organizer__email_log extends zenario_common_features {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//Information to view Data Protection settings
		$accessLogDuration = '';
		switch (ze::setting('period_to_delete_the_email_template_sending_log_headers')) {
			case 'never_delete':
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are stored forever.');
				break;
			case 0:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are not stored.');
				break;
			case 1:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 day.');
				break;
			case 7:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 week.');
				break;
			case 14:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 2 weeks.');
				break;
			case 30:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 month.');
				break;
			case 90:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 3 months.');
				break;
			case 180:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 6 months.');
				break;
			case 270:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 9 months.');
				break;
			case 365:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 year.');
				break;
			case 730:
				$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 2 years.');
				break;
			
		}
		$link = ze\link::absolute(). 'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
		$accessLogDuration .= ' ' . "<a target='_blank' href='" . $link . "'>View Data Protection settings</a>";
		$panel['notice']['show'] = true;
		$panel['notice']['message'] = $accessLogDuration.".";
		$panel['notice']['html'] = true;
	
		if ($refinerName == 'email_template') {
			$template = self::getTemplateByCode($refinerId);
			$panel['title'] = ze\admin::phrase('Emails sent using the template "[[template_name]]"', $template);
		}
		
		$userFormsModuleIsRunning = ze\module::inc('zenario_user_forms');
		foreach($panel['items'] as &$item) {
			$item['cell_css_classes'] = [];
			if ($item['status']=='success') {
				//To show debug mode 'On' in sent email log panel
				if ($item['debug_mode_flag'] == 1){
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent_debug_mode';
					$item['status'] = ze\admin::phrase('Success (debug mode)');
				} else { 
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent';
					$item['status'] = ze\admin::phrase('Sent');
				}
			} elseif ($item['status'] == 'failure') {
				if ($item['debug_mode_flag'] == 1){
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
					$item['status'] = ze\admin::phrase('Failed (debug mode)');
				} else { 
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
					$item['status'] = ze\admin::phrase('Failed Sending');
				}
				
			}
			
			if ($item['form_response_id'] && $userFormsModuleIsRunning) {
				$sql = "
					SELECT form.id
					FROM " . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . "user_forms AS form
					INNER JOIN " . DB_PREFIX . "user_response AS response
						ON form.id = response.form_id
					WHERE response.id = " . (int) $item['form_response_id'];
				$result = ze\sql::select($sql);
				$userFormId = ze\sql::fetchValue($result);
				
				if ($userFormId) {
					$href = 'organizer.php#zenario__user_forms/panels/user_forms/item_buttons/view_responses//' . (int) $userFormId . '//' . (int) $item['form_response_id'];
					$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
					$linkEnd = '</a>';
					
					$item['form_response_id'] = ze\admin::phrase(
						'[[link_start]]Form response ID [[form_response_id]][[link_end]]',
						['link_start' => $linkStart, 'form_response_id' => (int) $item['form_response_id'], 'link_end' => $linkEnd]
					);
				} else {
					$item['form_response_id'] = ze\admin::phrase('Form response ID [[form_response_id]] (response deleted)', ['form_response_id' => (int) $item['form_response_id']]);
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//...your PHP code...//
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		//...your PHP code...//
	}
}