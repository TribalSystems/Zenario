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


class zenario_common_features__admin_boxes__categories extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$record = getRow('categories', true, $box['key']['id']);
			$box['key']['parent_id'] = $record['parent_id'];
			$values['name'] = $record['name'];
			$values['public'] = $record['public'];
			
		} else {
			$box['key']['parent_id'] = request('refiner__parent_category');
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		if(isset($values['name']) && !$values['name']) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a name');
		}
		if (isset($values['name']) && checkIfCategoryExists($values['name'], $box['key']['id'], $box['key']['parent_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('A category called "[[name]]" already exists.', array('name' => htmlspecialchars($values['name'])));
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$row = array('name' => $values['name'], 'public' => $values['public']);
		
		if (!empty($box['key']['parent_id'])) {
			$row['parent_id'] = $box['key']['parent_id'];
		}
		
		$box['key']['id'] = setRow('categories', $row, $box['key']['id']);
		
		if ($values['public']) {
			foreach (getLanguages() as $lang) {
				if (!checkRowExists('visitor_phrases', array('language_id' => $lang['id'], 'code' => '_CATEGORY_'. (int) $box['key']['id'], 'module_class_name' => 'zenario_common_features'))) {
					$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "visitor_phrases SET
					language_id = '". sqlEscape($lang['id']). "',
					local_text = '". sqlEscape($values['name']). "',
					code = '_CATEGORY_". (int) $box['key']['id']. "',
					module_class_name = 'zenario_common_features'";
					sqlQuery($sql);
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Put the key back to what it originally was, to prevent highlighting bugs in Organizer
		$box['key']['id'] = $box['key']['originalId'];
	}
}
