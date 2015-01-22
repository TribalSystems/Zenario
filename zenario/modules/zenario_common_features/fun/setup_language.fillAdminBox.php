<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


if (!$box['key']['id']) {
	exit;
	
} elseif ($lang = getRow('languages', array('detect', 'detect_lang_codes', 'search_type', 'translate_phrases'), $box['key']['id'])) {
	$box['tabs']['settings']['fields']['detect']['value'] = $lang['detect'];
	$box['tabs']['settings']['fields']['detect_lang_codes']['value'] = $lang['detect_lang_codes'];
	$box['tabs']['settings']['fields']['search_type']['value'] = $lang['search_type'];
	$box['tabs']['settings']['fields']['translate_phrases']['value'] = $lang['translate_phrases'];
	
	$box['title'] = adminPhrase('Editing settings for "[[language]]"', array('language' => getLanguageName($box['key']['id'])));

} else {
	$box['title'] = adminPhrase('Enabling the Language "[[language]]" to the Site', array('language' => getLanguageName($box['key']['id'])));
	exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
	$box['save_button_message'] = adminPhrase('Enable Language');
	
	$box['tabs']['settings']['edit_mode']['always_on'] = true;
	
	//Don't default translations to on for English
	if ($box['key']['id'] == 'en'
	 || substr($box['key']['id'], 0, 3) == 'en-') {
	} else {
		$values['settings/translate_phrases'] = 1;
	}
	
	switch ($box['key']['id']) {
		case 'zh-hans':
		case 'zh-hant':
		case 'ko':
		case 'ja':
		case 'vi':
			$box['tabs']['settings']['fields']['search_type']['value'] = 'simple';
			break;
		default:
			$box['tabs']['settings']['fields']['search_type']['value'] = 'full_text';
			break;
	}

}

$box['tabs']['settings']['fields']['english_name']['value'] =
	getRow('visitor_phrases', 'local_text', array('code' => '__LANGUAGE_ENGLISH_NAME__', 'language_id' => $box['key']['id'], 'module_class_name' => 'zenario_common_features'));

$box['tabs']['settings']['fields']['language_local_name']['value'] =
	getRow('visitor_phrases', 'local_text', array('code' => '__LANGUAGE_LOCAL_NAME__', 'language_id' => $box['key']['id'], 'module_class_name' => 'zenario_common_features'));

$box['tabs']['settings']['fields']['flag_filename']['value'] =
	getRow('visitor_phrases', 'local_text', array('code' => '__LANGUAGE_FLAG_FILENAME__', 'language_id' => $box['key']['id'], 'module_class_name' => 'zenario_common_features'));

if (empty($box['tabs']['settings']['fields']['detect_lang_codes']['value'])) {
	$box['tabs']['settings']['fields']['detect_lang_codes']['value'] = $box['key']['id'];
}