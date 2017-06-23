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

class zenario_users__admin_boxes__impersonate extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']
		 && is_numeric($box['key']['id'])
		 && ($values['impersonate/user_id'] = (int) $box['key']['id'])) {
			$fields['impersonate/user_id']['hidden'] = true;
			
			$fields['impersonate/desc']['snippet']['html'] =
				adminPhrase('This action will log you in as user "[[name]]", so that you will see the site as they see it.',
					array('name' => getUserIdentifier($box['key']['id'])));
		} else {
			$box['max_height'] += 150;
			$fields['impersonate/desc']['snippet']['html'] =
				adminPhrase('This action will log you in as the selected user, so that you will see the site as they see it.',
					array('name' => getUserIdentifier($box['key']['id'])));
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (checkPriv('_PRIV_IMPERSONATE_USER')) {
			$logAdminOut = ($values['impersonate/options'] == 'logout');
			$setCookie = $values['impersonate/set_keep_me_logged_in_cookie'];
			$this->impersonateUser($values['impersonate/user_id'], $logAdminOut, $setCookie);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!$box['key']['openFromAdminToolbar']) {
			//Bypass the rest of the script in admin_boxes.ajax.php, and go to the new URL straight away
			closeFABWithFlags(['go_to_url' => absCMSDirURL()]);
			exit;
		}
	}
}