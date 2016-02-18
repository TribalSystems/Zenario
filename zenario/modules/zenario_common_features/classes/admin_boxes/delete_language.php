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


class zenario_common_features__admin_boxes__delete_language extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		$box['tabs']['site']['fields']['username']['value'] = session('admin_username');
		$box['tabs']['site']['notices']['are_you_sure']['message'] =
			adminPhrase(
				'Are you sure that you wish to delete the Language "[[lang]]"? All Content Items and Menu Node text in this Language will also be deleted. THIS CANNOT BE UNDONE!',
				array('lang' => getLanguageName($box['key']['id'])));
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		$details = array();
		
		if (!allowDeleteLanguage($box['key']['id'])) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('You cannot delete the default language of your site.');
		}
		
		if (!$values['site/password']) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Please enter your password.');
		
		} elseif (!engToBoolean(checkPasswordAdmin(session('admin_username'), $details, $values['site/password']))) {
			$box['tabs']['site']['errors'][] =
				adminPhrase('Your password was not recognised. Please check and try again.');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		deleteLanguage($box['key']['id']);
	}
}
