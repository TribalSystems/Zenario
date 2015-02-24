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

class zenario_users__admin_boxes__user__convert_to_user extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userDetails = getUserDetails(arrayKey($box,"key","id"));
		$values['details/email'] = $userDetails['email'];
		$values['details/salutation'] = $userDetails['salutation'];
		$values['details/first_name'] = $userDetails['first_name'];
		$values['details/last_name'] = $userDetails['last_name'];
	
		$box['title'] = "Converting the contact \"" . arrayKey($userDetails,"screen_name") . "\"";
	
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
		if (!setting('user_use_screen_name')) {
			$fields['details/screen_name']['hidden'] = true;
			$fields['details/screen_name']['validation'] = false;
			$fields['details/suggest_screen_name']['hidden'] = true;
		}
		if (!$values['details/email']) {
			$fields['details/email']['read_only'] =  false;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$cols = array(
			'email' => $values['details/email'],
			'salutation' => $values['details/salutation'],
			'first_name' => $values['details/first_name'],
			'last_name' => $values['details/last_name'],
			'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
		);
	
		if (setting('user_use_screen_name')) {
			$cols['screen_name'] = $values['details/screen_name'];
		}
		
		if ($e = isInvalidUser($cols, $box['key']['id'])) {
			//If there are errors, add them to the first tab
			foreach ($e->errors as $error) {
				$box['tabs']['details']['errors'][] = adminPhrase($error);
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (($id = (int)$box['key']['id']) && checkPriv('_PRIV_CHANGE_USER_STATUS')) {
			$sql ="
					UPDATE "
					. DB_NAME_PREFIX . "users
					SET
						status='active'
					WHERE
						id=" . $id;
			sqlQuery($sql);
			$cols = array(
				'email' => $values['details/email'],
				'salutation' => $values['details/salutation'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'terms_and_conditions_accepted' => $values['details/terms_and_conditions_accepted']
			);
	
			if (setting('user_use_screen_name')) {
				$cols['screen_name'] = $values['details/screen_name'];
			}
			
			$cols['password'] = $values['details/password'];
			
			$cols['password_needs_changing'] = $values['details/password_needs_changing'];
			
			saveUser($cols, $box['key']['id']);
			
			sendSignal("eventUserStatusChange",array("userId" => $id, "status" => "active"));
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (checkPriv('_PRIV_CHANGE_USER_STATUS')) {
			if (isset($fields['details/send_activation_email_to_user']) &&  $fields['details/send_activation_email_to_user']
				&& issetArrayKey($values,'details/email_to_send') && (inc('zenario_email_template_manager'))) {
				$mergeFields=getUserDetails($box['key']['id']);
				$mergeFields['password'] = $values['password'];
				$mergeFields['cms_url'] = absCMSDirURL();
				
				zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($mergeFields,'email'),arrayKey($values,'details/email_to_send'),$mergeFields);
			}
		}
	}
}
