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

class zenario_user_forms_list_part_completed extends ze\moduleBaseClass {
	
	protected $userId;
	protected $data = [];
	
	public function init() {
		return true;
	}
	
	public function showSlot() {
		$this->userId = ze\user::id();
		
		if ($this->userId) {
			$this->data['User_Logged_In'] = true;
			
			if (ze\module::inc('zenario_user_forms')) {
				$userPartCompletedFormResponses = ze\row::getArray(ZENARIO_USER_FORMS_PREFIX . 'user_partial_response', ['id', 'form_id', 'response_datetime'], ['user_id' => (int) $this->userId]);
				
				if ($userPartCompletedFormResponses && is_array($userPartCompletedFormResponses) && count($userPartCompletedFormResponses) > 0) {
					
					foreach ($userPartCompletedFormResponses as $userPartCompletedFormResponse) {
						$cID = $cType = $state = false;
						\ze\content::pluginPage($cID, $cType, $state, 'zenario_user_forms', 'form_'. $userPartCompletedFormResponse['form_id']);
						
						$viewLink = ze\link::toPluginPage('zenario_user_forms', 'form_' . $userPartCompletedFormResponse['form_id'], false, false, '', false, $checkPerm = true);
						
						if ($viewLink) {
							$this->data['User_Part_Complete_Form_Responses'][$userPartCompletedFormResponse['id']] = [
								'Page_Title' => ze\content::title($cID, $cType),
								'Date' => ze\date::formatDateTime($userPartCompletedFormResponse['response_datetime']),
								'View_Link' => $viewLink
							];
						}
					}
				}
			}
		}
		
		$this->twigFramework($this->data);
	}
}