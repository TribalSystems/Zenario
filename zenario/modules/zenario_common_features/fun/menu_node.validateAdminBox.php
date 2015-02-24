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

$langs = getLanguages();
$numLangs = count($langs);

$createdLanguages = 0;
if (engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')) {
	foreach ($langs as $lang) {
		if ($values['text/menu_title__'. $lang['id']]) {
			++$createdLanguages;
		}
	}
	
	if (!$createdLanguages) {
		if ($numLangs > 1) {
			$box['tabs']['text']['errors'][] = adminPhrase('Please enter text in at least one language.');
		} else {
			$box['tabs']['text']['errors'][] = adminPhrase('_ERROR_MUST_ENTER_TITLE_FOR_MENU_ITEM');
		}
	}
}


if ($values['text/target_loc'] == 'exts') {
	if (engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')
	 || engToBooleanArray($box['tabs']['text'], 'edit_mode', 'on')) {
		foreach ($langs as $lang) {
			if ($values['text/menu_title__'. $lang['id']]
			 && !$values['text/ext_url__'. $lang['id']]) {
				$box['tabs']['text']['errors'][] = adminPhrase('Please set an external URL for [[english_name]].', $lang);
			}
		}
	}
}

if (engToBooleanArray($box['tabs']['advanced'], 'edit_mode', 'on')) {
	if (!empty($values['advanced/accesskey'])) {
		$ord = ord($values['advanced/accesskey']);
		if ($ord < 48 || ($ord > 57 && $ord < 65) || $ord > 90) {
			$box['tabs']['advanced']['errors'][] = adminPhrase('Access Keys may only be the capital letters A-Z, or the digits 0-9.');
		
		} else {
			$sql = "
				SELECT id
				FROM ". DB_NAME_PREFIX ."menu_nodes
				WHERE accesskey = '" . sqlEscape($values['advanced/accesskey']) . "'";
			
			if ($box['key']['id']) {
				$sql .= "
				  AND id != ". (int) $box['key']['id'];
			}
			
			if (($result = sqlQuery($sql)) && ($row = sqlFetchAssoc($result))) {
				$box['tabs']['advanced']['errors'][] =
					adminPhrase('The access key "[[accesskey]]" is in use! It is currently assigned to the Menu Node "[[menuitem]]".',
						array('accesskey' => $values['advanced/accesskey'], 'menuitem' => getMenuName($row['id'], $box['key']['languageId'])));
			}
		}
	}
	if (!empty($values['advanced/hide_by_static_method'])) {
		if (!$values['advanced/menu__module_class_name']) {
			$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin.');
		
		} elseif (!inc($values['advanced/menu__module_class_name'])) {
			$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the Class Name of a Plugin that you have running on this site.');
		
		} elseif ($values['advanced/menu__method_name']
			&& !method_exists(
					$values['advanced/menu__module_class_name'],
					$values['advanced/menu__method_name'])
		) {
			$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the name of an existing Static Method.');
		}
		
		if (!$values['advanced/menu__method_name']) {
			$box['tabs']['advanced']['errors'][] = adminPhrase('Please enter the name of a Static Method.');
		}
	}
}

return false;