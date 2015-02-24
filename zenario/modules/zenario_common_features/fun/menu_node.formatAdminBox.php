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

$box['tabs']['advanced']['fields']['rollover_image_id']['hidden'] = 
	!$values['advanced/use_rollover_image'];

$box['tabs']['advanced']['fields']['menu__module_class_name']['hidden'] = 
$box['tabs']['advanced']['fields']['menu__method_name']['hidden'] = 
$box['tabs']['advanced']['fields']['menu__param_1']['hidden'] = 
$box['tabs']['advanced']['fields']['menu__param_2']['hidden'] = 
	!$values['advanced/hide_by_static_method'];

$box['tabs']['text']['fields']['hyperlink_target']['hidden'] = 
$box['tabs']['text']['fields']['hyperlink_anchor']['hidden'] = 
$box['tabs']['text']['fields']['hide_private_item']['hidden'] = 
$box['tabs']['text']['fields']['use_download_page']['hidden'] = 
	$values['text/target_loc'] != 'int';

$box['tabs']['text']['fields']['open_in_new_window']['hidden'] = 
	$values['text/target_loc'] != 'int'
 && $values['text/target_loc'] != 'ext'
 && $values['text/target_loc'] != 'exts';

$box['tabs']['text']['fields']['ext_url']['hidden'] = 
	$values['text/target_loc'] != 'ext';

$equivs = $cID = $cType = false;
if ($values['text/target_loc'] == 'int'
 && (getCIDAndCTypeFromTagId($cID, $cType, $values['text/hyperlink_target']))
 && ($cType == 'document')) {
	$box['tabs']['text']['fields']['use_download_page']['hidden'] = false;
} else {
	$box['tabs']['text']['fields']['use_download_page']['current_value'] = false;
	$box['tabs']['text']['fields']['use_download_page']['hidden'] = true;
}


$langs = getLanguages();
$numLangs = count($langs);

//For multilingal sites, add a note about using the Content Item in the default language if no translation is set.
//(But use the langEquivalentItem() function to work out what language will actually be used.)
$box['tabs']['text']['fields']['multilingual_description']['hidden'] = true;
if ($cID && $cType && $numLangs > 1) {
	langEquivalentItem($cID, $cType, $langId = true);
	$mainLang = getContentLang($cID, $cType);
	
	$box['tabs']['text']['fields']['multilingual_description']['hidden'] = false;
	$box['tabs']['text']['fields']['multilingual_description']['snippet']['html'] =
		' '.
		adminPhrase(
			'If text is specified for a menu node but no translation of the content item exists, the menu node will link to the item in [[english_name]].',
			$langs[$mainLang]);
	
	$equivs = equivalences($cID, $cType);
}


//Set the current Menu Path from the current title and the parent path
foreach ($langs as $lang) {
	zenario_common_features::setMenuPath($box['tabs']['text']['fields'], 'menu_title__'. $lang['id'], 'current_value');
	
	if (!empty($equivs) && empty($equivs[$lang['id']])) {
		$box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['note_below'] = adminPhrase('Translated content item missing.');
	} else {
		unset($box['tabs']['text']['fields']['path_of__menu_title__'. $lang['id']]['note_below']);
	}
	
	$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['disabled'] =
		empty($values['text/menu_title__'. $lang['id']]);
	
	$box['tabs']['text']['fields']['ext_url__'. $lang['id']]['hidden'] = 
		$values['text/target_loc'] != 'exts';
}