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

class zenario_users__admin_boxes__user__welcome_email extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//If it looks like a site is supposed to be using encryption, but it's not set up properly,
		//show an error message.
		ze\pdeAdm::showNoticeOnFABIfConfIsBad($box);
		
		
		$infoNote = '';
		
		$userIds = explode(',', $box['key']['id']);
		$userIdsCount = count($userIds);
		if ($userIdsCount == 1) {
			$userDetails = ze\user::details($userIds[0]);
			$box['title'] = "Sending activation email to the user \"" . $userDetails["identifier"] . "\"";
			$fields['details/do_not_include_personal_info_snippet']['hidden'] = true;
			$infoNote .= 'As this site is configured to store only encrypted passwords for users (not plain text), the user\'s password will not be shown.';
			
			$fields['details/email_to_send_body']['label'] = ze\admin::phrase('Email body (modify as required):');
		} else {
			$box['title'] = "Sending activation emails to " . count($userIds) . " users";
			$infoNote .= 'You are about to send activation emails to [[count]] selected users.<br> <br>';
			$infoNote .= 'As this site is configured to store only encrypted passwords for users (not plain text), the users\' passwords will not be shown.';
			
			$fields['details/email_to_send_body']['label'] = ze\admin::phrase('Email body:');
		}
		
		$fields['details/non_plain_text_info']['snippet']['html'] = ze\admin::phrase($infoNote, ['count' => $userIdsCount]);
		
		$fields['details/email_to_send']['value'] = ze::setting('default_activation_email_template');
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($values['details/email_to_send'] && ze\module::inc('zenario_email_template_manager')) {
			$userIds = explode(',', $box['key']['id']);
			
			$template = zenario_email_template_manager::getTemplateByCode($values['details/email_to_send']);
			
			if (!empty($template) && is_array($template)) {
				if (count($userIds) == 1) {
					$userId = $userIds[0];
					$mergeFields = ze\user::userDetailsForEmails($userId);
					$mergeFields['cms_url'] = ze\link::absolute();
			
					ze\lang::applyMergeFields($template['body'], $mergeFields);
				}
			
				$values['details/email_to_send_body'] = $template['body'];
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$userIds = explode(',', $box['key']['id']);
		foreach ($userIds as $userId) {
			$user = ze\row::get('users', ['identifier', 'email'], $userId);
			if (!$user['email']) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('The user "[[identifier]]" must have an email address to send a activation email.', $user);
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$userIds = explode(',', $box['key']['id']);
		if ($userIds) {
			if ($values['details/email_to_send'] && (ze\module::inc('zenario_email_template_manager'))) {
				foreach ($userIds as $userId) {
					$mergeFields = ze\user::userDetailsForEmails($userId);
					$mergeFields['cms_url'] = ze\link::absolute();
					
					zenario_email_template_manager::sendEmailsUsingTemplate(
						$mergeFields['email'], $values['details/email_to_send'], $mergeFields,
						[], [], false, false, false, false, false, $customBody = $values['details/email_to_send_body']
					);
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}
