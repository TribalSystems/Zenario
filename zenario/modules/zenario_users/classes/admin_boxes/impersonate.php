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

class zenario_users__admin_boxes__impersonate extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']
		 && is_numeric($box['key']['id'])
		 && ($values['impersonate/user_id'] = (int) $box['key']['id'])) {
			$fields['impersonate/user_id']['hidden'] = true;
			
			$fields['impersonate/desc']['snippet']['html'] =
				ze\admin::phrase('This action will log you in as user "[[name]]", so that you will see the site as they see it.',
					['name' => ze\user::identifier($box['key']['id'])]);
		} else {
			
			//This code would auto-populate the picker, however this logic is done elsewhere
			//if (!empty($_COOKIE['COOKIE_LAST_EXTRANET_EMAIL'])) {
			//	$values['impersonate/user_id'] = ze\row::get('users', 'id', ['email' => $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL']]);
			//
			//} elseif (!empty($_COOKIE['COOKIE_LAST_EXTRANET_SCREEN_NAME'])) {
			//	$values['impersonate/user_id'] = ze\row::get('users', 'id', ['screen_name' => $_COOKIE['COOKIE_LAST_EXTRANET_EMAIL']]);
			//}
			
			$box['max_height'] += 150;
			$fields['impersonate/desc']['snippet']['html'] =
				ze\admin::phrase('This action will log you in as the selected user, so that you will see the site as they see it.',
					['name' => ze\user::identifier($box['key']['id'])]);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\priv::check('_PRIV_IMPERSONATE_USER')) {
			$this->impersonateUser($values['impersonate/user_id'],
				$values['impersonate/options'] == 'logout',
				$values['impersonate/remember_me'],
				$values['impersonate/log_me_in']);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!$box['key']['openFromAdminToolbar']) {
			//Bypass the rest of the script in admin_boxes.ajax.php, and go to the new URL straight away
			ze\tuix::closeWithFlags(['go_to_url' => ze\link::absolute()]);
			exit;
		}
	}
}