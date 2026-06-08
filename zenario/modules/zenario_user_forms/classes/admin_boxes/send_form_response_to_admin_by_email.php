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

class zenario_user_forms__admin_boxes__send_form_response_to_admin_by_email extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$box['title'] = ze\admin::phrase('Re-sending form response [[id]] to administrators', ['id' => $box['key']['id']]);
		
		$adminId = ze\admin::id();
        $details = ze\row::get('admins', true, $adminId);
		$box['tabs']['send_form_response_to_admin_by_email']['fields']['from']['value'] = $details['first_name'] . ' ' . $details['last_name'] . ' (' . $details['email'] . ')';
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$url = ze\link::absolute();
		$adminIds = ze\ray::explodeAndTrim($values['send_form_response_to_admin_by_email/send_to_admin_ids']);
		
		$responseId = $box['key']['id'];
		$formIdAndIserId = ze\row::get('user_response', ['form_id', 'user_id'], ['id' => $responseId]);
		
		$form = zenario_user_forms::getForm($formIdAndIserId['form_id']);
		$formFields = zenario_user_forms::getFormFields($formIdAndIserId['form_id'], $responseId);
		
		$referrerContentTag = '';
		if ($form['handle_referrer_content_item']) {
			$referrerContentTag = ze\row::get('user_response_referrer_info', 'referrer_content_item', ['user_response_id' => $responseId]);
		}
		
		if (!empty($adminIds) && is_array($adminIds)) {
			foreach ($adminIds as $adminId) {
				$adminEmail = ze\row::get('admins', 'email', $adminId);
				
				zenario_user_forms::sendEmailResponseToAdmin(
					$responseId, $form, $formIdAndIserId['user_id'], $formFields,
					$url, $form['make_urls_non_clickable_admin'], $values['send_form_response_to_admin_by_email/comment'], ze\admin::id(), $adminEmail, $referrerContentTag
				);
			}
		}
	}
}