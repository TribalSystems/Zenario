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


exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');

if (!$langId = $box['key']['id']) {
	exit;
}

$cItemsInLangKey = array('language_id' => $box['key']['id'], 'status' => array('!' => 'deleted'));
$pagesExist = checkRowExists('content_items', $cItemsInLangKey);


if (engToBooleanArray($box['tabs']['settings'], 'edit_mode', 'on')) {
	setRow('languages', array(), $langId);

	setRow(
		'visitor_phrases',
		array(
			'local_text' => $values['settings/english_name'],
			'protect_flag' => 1),
		array(
			'code' => '__LANGUAGE_ENGLISH_NAME__',
			'language_id' => $box['key']['id'],
			'module_class_name' => 'zenario_common_features'));
	
	setRow(
		'visitor_phrases',
		array(
			'local_text' => $values['settings/language_local_name'],
			'protect_flag' => 1),
		array(
			'code' => '__LANGUAGE_LOCAL_NAME__',
			'language_id' => $box['key']['id'],
			'module_class_name' => 'zenario_common_features'));
	
	setRow(
		'visitor_phrases',
		array(
			'local_text' => decodeItemIdForStorekeeper($values['settings/flag_filename']),
			'protect_flag' => 1),
		array(
			'code' => '__LANGUAGE_FLAG_FILENAME__',
			'language_id' => $box['key']['id'],
			'module_class_name' => 'zenario_common_features'));
	
	updateRow(
		'languages',
		array(
			'detect' => $values['settings/detect'], 
			'detect_lang_codes' => $values['settings/detect_lang_codes'], 
			'translate_phrases' => $values['settings/translate_phrases'], 
			'search_type'=> ($values['settings/search_type'] == 'simple'? 'simple' : 'full_text'),
			'domain'=> ($values['settings/use_domain'] && getNumLanguages() > 1? $values['domain'] : '')
		),
		$box['key']['id']);
}

//Check if a default language has been set, and set it now if not
if ($addedFirstLanguage = !setting('default_language')) {
	setSetting('default_language', $langId);
}

//Update the special pages, creating new ones if needed
addNeededSpecialPages();

//Add any new phrases for this language
importPhrasesForModules($langId);

//If we're adding a new language (i.e. no content items previously existed), show a message
//warning the admin what pages were just made.
if (!$pagesExist) {
	
	//Check for the pages that were just made
	$contentItems = getRowsArray('content_items', array('id', 'type', 'alias'), $cItemsInLangKey, 'id');
	
	if (!empty($contentItems)) {
		if (count($contentItems) < 2) {
			$toastMessage =
				adminPhrase(
					'&quot;[[tag]]&quot; was created as a home page. You should review and publish this content item.',
					array('tag' => htmlspecialchars(formatTag($contentItems[0]['id'], $contentItems[0]['type'], $contentItems[0]['alias'], $box['key']['id']))));
		
		} else {
			$toastMessage =
				adminPhrase('The following content items were just created, you should review and publish them:').
				'<ul>';
			
			foreach ($contentItems as $contentItem) {
				$toastMessage .= '<li>'. htmlspecialchars(formatTag($contentItem['id'], $contentItem['type'], $contentItem['alias'], $box['key']['id'])). '</li>';
			}
			
			$toastMessage .= '</ul>';
		}
		
		$box['toast'] = array(
			'message' => $toastMessage,
			'options' => array('timeOut' => 0, 'extendedTimeOut' => 0));
	}

}


//Go to the language in the enabled languages panel.
//We should also reload the page if any of the language names were changed, or if this was the first language to be added
$box['popout_message'] = '<!--Go_To_URL:zenario/admin/welcome.php?task=reload_sk&og='. rawurlencode('zenario__languages/panels/languages//'. $langId). '-->';

return false;