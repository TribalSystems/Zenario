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

class zenario_users__admin_boxes__consent extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$consentId = $box['key']['id'];
		$consent = ze\row::get('consents', true, $consentId);
		
		$fields['details/datetime']['snippet']['html'] = ze\admin::formatDateTime($consent['datetime']);
		$fields['details/ip_address']['snippet']['html'] = $consent['ip_address'];
		$fields['details/email_user']["hidden"] =true;
		$fields['details/last_name_user']["hidden"] = true;
		$fields['details/user']['snippet']['html'] = '';
		$user ='';
		if (!empty(trim($consent['first_name'] . ' ' . $consent['last_name']))) {
			$user .= $fields['details/user']['snippet']['html']  = trim($consent['first_name']);
			if(!empty($consent['first_name']) && (ze::$dbL->columnIsEncrypted('consents', 'first_name') || ze::$dbL->columnIsHashed('consents', 'first_name'))){
			    $fields['details/user']["encrypted"]  = true;
			}
			if(!empty($consent['last_name'])){
			    $fields['details/last_name_user']["hidden"] = false;
                $user .= $fields['details/last_name_user']['snippet']['html'] = ' ' . $consent['last_name'];
                if(ze::$dbL->columnIsEncrypted('consents', 'last_name') || ze::$dbL->columnIsHashed('consents', 'last_name')){
                    $fields['details/last_name_user']["encrypted"]  = true;
                }
                $fields['details/last_name_user']["same_row"] = true;
            } 
            if (!empty($consent['email'])) {
                $fields['details/email_user']["hidden"] = false;
                $user .= $fields['details/email_user']['snippet']['html'] = !empty(trim($consent['first_name'] . ' ' . $consent['last_name'])) ? ' (' . $consent['email'].')' : $consent['email'];
                $fields['details/email_user']["same_row"]= true;
                if(ze::$dbL->columnIsEncrypted('consents', 'email') || ze::$dbL->columnIsHashed('consents', 'email')){
                    $fields['details/email_user']["encrypted"]  = true;
                }
            }
		} elseif ($consent['email']) {
			$user .= $fields['details/user']['snippet']['html']= $consent['email'];
			if(ze::$dbL->columnIsEncrypted('consents', 'email') || ze::$dbL->columnIsHashed('consents', 'email')){
                    $fields['details/user']["encrypted"]  = true;
                    $fields['details/email_user']["same_row"] = true;
                }
		}
	
		$fields['details/consent_text']['snippet']['html'] = $consent['label'];
		
		if ($consent['source_name'] == 'form' && ze\module::inc('zenario_user_forms')) {
			$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name'], $consent['source_id']);
			if ($form) {
				$fields['details/source']['snippet']['html'] = ze\admin::phrase('Form ([[name]])', $form);
			} else {
				$fields['details/source']['snippet']['html'] = ze\admin::phrase('Unknown form');
			}
		} elseif ($consent['source_name'] == 'extranet_registration') {
			$fields['details/source']['snippet']['html'] = ze\admin::phrase('Extranet registration');
		} elseif ($consent['source_name'] == 'extranet_login') {
			$fields['details/source']['snippet']['html'] = ze\admin::phrase('Extranet login');
		} else {
			$fields['details/source']['snippet']['html'] = ze\admin::phrase('Unknown');
		}
		
		$box['title'] = ze\admin::phrase('Consent given by "[[user]]"', ['user' => $user]);
	}
	
}