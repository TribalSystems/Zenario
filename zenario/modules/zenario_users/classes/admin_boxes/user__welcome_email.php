<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
		$userIds = explode(',', $box['key']['id']);
		if (count($userIds) == 1) {
			$userDetails = getUserDetails($userIds[0]);
			$box['title'] = "Sending welcome email to the user \"" . $userDetails["identifier"] . "\"";
		} else {
			$box['title'] = "Sending welcome emails to " . count($userIds) . " users";
		}
		
		$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('User Activated',false);
		if (count($layouts)==0) {
			$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('Account Activated',false);
		}
		if (count($layouts)){
			$template = current($layouts);
			$fields['details/email_to_send']['value'] = arrayKey($template,'code');
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!setting('plaintext_extranet_user_passwords')) {
			//uses encryped passwords
			$fields['details/reset_password']['hidden'] = false;
			$fields['details/include_password']['hidden'] = true;
			$fields['details/plain_text_info']['hidden'] = true;
			$fields['details/non_plain_text_info']['hidden'] = false;
		} else {
			//uses plain text passwords
			$fields['details/reset_password']['hidden'] = true;
			$fields['details/include_password']['hidden'] = false;
			$fields['details/plain_text_info']['hidden'] = false;
			$fields['details/non_plain_text_info']['hidden'] = true;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$userIds = explode(',', $box['key']['id']);
		foreach ($userIds as $userId) {
			$user = getRow('users', array('identifier', 'email'), $userId);
			if (!$user['email']) {
				$box['tabs']['details']['errors'][] = adminPhrase('The user "[[identifier]]" must have an email address to send a welcome email.', $user);
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$userIds = explode(',', $box['key']['id']);
		if ($userIds) {
			if ($values['details/email_to_send'] && (inc('zenario_email_template_manager'))) {
				foreach ($userIds as $userId) {
					$mergeFields = getUserDetails($userId);
					if (isset($values['details/reset_password']) && $values['details/reset_password']) {
						$mergeFields['password'] = randomString(8);
						saveUser(array('password' => $mergeFields['password']), $userId);
					} elseif (isset($values['details/include_password']) && $values['details/include_password']) {
						//show plain text password
					} else {
						$mergeFields['password'] = "********* (not shown)";
					}
					
					$mergeFields['cms_url'] = absCMSDirURL();
					
					zenario_email_template_manager::sendEmailsUsingTemplate($mergeFields['email'], $values['details/email_to_send'], $mergeFields);
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}
