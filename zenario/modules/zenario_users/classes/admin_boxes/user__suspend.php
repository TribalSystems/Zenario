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

class zenario_users__admin_boxes__user__suspend extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$userDetails = getUserDetails(arrayKey($box,"key","id"));
	
		$box['title'] = "Suspending the user \"" . arrayKey($userDetails,"identifier") . "\"";
	
		$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('User Suspended',false);
	
		if (count($layouts)==0) {
			$layouts = zenario_email_template_manager::getTemplatesByNameIndexedByCode('Account Suspended',false);
		}
	
		if (count($layouts)){
			$template = current($layouts);
			$fields['email_to_send']['value'] = arrayKey($template,'code');
		}
		
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['email_to_send']['hidden'] = !issetArrayKey($values,'email/send_email_to_user');
		
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (($id = (int)$box['key']['id']) && checkPriv('_PRIV_CHANGE_USER_STATUS')) {
			$sql ="
					UPDATE "
					. DB_NAME_PREFIX . "users
					SET
						status='suspended',
						suspended_date=NOW()
					WHERE
						id=" . $id;
			sqlQuery($sql);
			sendSignal("eventUserStatusChange",array("userId" => $id, "status" => "suspended"));
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (checkPriv('_PRIV_CHANGE_USER_STATUS')) {
			if (issetArrayKey($values,'email/send_email_to_user') && issetArrayKey($values,'email/email_to_send') && (inc('zenario_email_template_manager'))) {
				$mergeFields=getUserDetails($box['key']['id']);
				$mergeFields['cms_url'] = absCMSDirURL();
	
				$result = sendSignal("requestAdditionalEmailTemplateMergeFields",array("userId" => $box['key']['id']));
	
				if (is_array($result)) {
					foreach ($result as $moduleName => $moduleResult) {
						$mergeFields = array_merge($mergeFields,$moduleResult);
					}
				}
	
				zenario_email_template_manager::sendEmailsUsingTemplate(arrayKey($mergeFields,'email'),arrayKey($values,'email/email_to_send'),$mergeFields);
			}
		}
	}
}
