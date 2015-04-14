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

class zenario_users__admin_boxes__user__welcome_email extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userDetails = getUserDetails(arrayKey($box,"key","id"));
		$values['details/email'] = $userDetails['email'];
		$values['details/salutation'] = $userDetails['salutation'];
		$values['details/first_name'] = $userDetails['first_name'];
		$values['details/last_name'] = $userDetails['last_name'];
	
		$box['title'] = "Sending welcome email to the user \"" . arrayKey($userDetails,"identifier") . "\"";
	
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
		$cols = array(
			'email' => $values['details/email'],
			'salutation' => $values['details/salutation'],
			'first_name' => $values['details/first_name'],
			'last_name' => $values['details/last_name']
		);
		
		if ($e = isInvalidUser($cols, $box['key']['id'])) {
			//If there are errors, add them to the first tab
			foreach ($e->errors as $error) {
				$box['tabs']['details']['errors'][] = adminPhrase($error);
			}
		} else if (!$values['details/email']) {
			$box['tabs']['details']['errors'][] = adminPhrase("The user must have an email address to send a welcome email.");
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (($id = (int)$box['key']['id'])) {
			if (issetArrayKey($values,'details/email_to_send') && (inc('zenario_email_template_manager'))) {
				$mergeFields=getUserDetails($box['key']['id']);
				if (isset($values['details/reset_password']) && $values['details/reset_password']) {
					$mergeFields['password'] = randomString(8);
					saveUser(array('password' => $mergeFields['password']), $box['key']['id']);
				} elseif (isset($values['details/include_password']) && $values['details/include_password']) {
					//show plain text password
				} else {
					$mergeFields['password'] = "********* (not shown)";
				}
				
				$mergeFields['cms_url'] = absCMSDirURL();
				
				zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($mergeFields,'email'),arrayKey($values,'details/email_to_send'),$mergeFields);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
}
