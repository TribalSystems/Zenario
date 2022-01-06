<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


class zenario_common_features__admin_boxes__categories extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id'] && !$box['key']['sub_category']) {
			$record = ze\row::get('categories', true, $box['key']['id']);
			$box['key']['parent_id'] = $record['parent_id'];
			$values['name'] = $record['name'];
			$values['public'] = $record['public'];
			$values['code_name'] = $record['code_name'];
			if ($record['landing_page_equiv_id'] && $record['landing_page_content_type']) {
				$values['landing_page'] = ze\content::formatTag($record['landing_page_equiv_id'], $record['landing_page_content_type'], false);
			}
		} else if ($box['key']['sub_category']) {
			$box['key']['parent_id'] = $box['key']['id'];
			$box['key']['id'] = "";
		}else{
			$box['key']['parent_id'] = $_REQUEST['refiner__parent_category'] ?? false;
			if (!$box['key']['parent_id']) {
				$box['title'] = ze\admin::phrase('Creating a top-level category');
			} else {
				$box['title'] = ze\admin::phrase('Creating a category inside "[[name]]"', ['name' => ze\category::name($box['key']['parent_id'])]);
			}
		}
		

		if ($box['key']['sub_category']) {
			unset($box['title_for_existing_records']);
			//TODO maybe improve the wording
			$box['title'] = ze\admin::phrase('Creating a sub-category in "[[name]]"', ['name' => ze\category::name($box['key']['parent_id'])]);
		}
		
		if (ze::setting('enable_display_categories_on_content_lists') && ze::setting('enable_category_landing_pages')) {
			$fields['details/landing_page']['hidden'] = false;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$langs = ze\lang::getLanguages(false, false, true);
		
		foreach ($langs as $key => $lang) {
			
			if ($values['details/public']) {
				if ($box['key']['id']) {
					$phrase = ze\row::get('visitor_phrases', 'local_text', ['language_id' => $lang['id'], 'code' => '_CATEGORY_'. (int) $box['key']['id'], 'module_class_name' => 'zenario_common_features']);
					if (!$phrase) {
						$phrase = $values['details/name'];
					}
				} else {
					$phrase = $values['details/name'];
				}
				if (!isset($box['tabs']['details']['fields']['visitor_name_' . $key])) {
					$box['tabs']['details']['fields']['visitor_name_' . $key] =
						[
							'label' => 'Visitor name ' . $lang['english_name'] . ':',
							'type' => 'text',
							'hidden' => false,
							'indent' => 1,
							'ord' => 3.1,
							'validation' => ['required_if_not_hidden' => 'Please enter the name of this category as it will be seen by visitors, in '. $lang['english_name'] . '.'],
							'value' => $phrase
						];
				} else {
					$box['tabs']['details']['fields']['visitor_name_' . $key]['hidden'] = false;
				}
			} elseif (isset($box['tabs']['details']['fields']['visitor_name_' . $key])) {
				$box['tabs']['details']['fields']['visitor_name_' . $key]['hidden'] = true;
			}
		}
		//To show code name prepopulated value only one time and make sure it does'nt happen after opening the admin box.
		if (isset($fields['details/public']['copy_code_name']) && $values['details/public']) {
			
			if ($values['details/name'] && !$values['details/code_name'] ) {
			
				$values['details/code_name'] = $this->setCodeName(strtolower($values['details/name']));
			}
			unset($fields['details/public']['copy_code_name']);
		}
	}
	//To remove non-alphanumeric characters.
	public function setCodeName($textValue) {
		$formatText =  preg_replace( '/&/', 'and', $textValue);
		$formatText =  preg_replace('/\s/', '-',preg_replace( '/[^ \w-]/', '', $formatText));
		$formatText = substr($formatText,0, 50);
		return $formatText;
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (isset($values['name']) && ze\categoryAdm::exists($values['name'], $box['key']['id'], $box['key']['parent_id'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('A category called "[[name]]" already exists.', $values);
		}
		if (!empty($values['code_name']) && preg_match("/[^a-zA-Z0-9_-]/", $values['code_name'])) {
			$box['tabs']['details']['fields']['code_name']['error'] = ze\admin::phrase("Code name can only use characters a-z 0-9 _-.");
		}
		if (!empty($values['code_name']) && ze\row::exists('categories', ['code_name' => $values['code_name'], 'id' => ['!=' => $box['key']['id']]])) {
			$box['tabs']['details']['fields']['code_name']['error'] = ze\admin::phrase("Code name already in use.");
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$row = ['name' => $values['name'], 'public' => $values['public']];
		if(!empty($values['code_name'])){
		    $row['code_name'] = $values['code_name']; 
		
		}
		if (!empty($box['key']['parent_id'])) {
			$row['parent_id'] = $box['key']['parent_id'];
			$parentCategoryName = ze\row::get('categories', ["name"], ["id" => $row['parent_id']]);
			if(!$box['key']['id']){
			    $box['toast'] = ["message" => 'The sub-category "'.$values['name'].'" was successfully created inside "'.$parentCategoryName["name"].'".', "message_type" => "success"];
			}else {
			     $box['toast'] = ["message" => 'The sub-category "'.$values['name'].'" was successfully edited inside "'.$parentCategoryName["name"].'".', "message_type" => "success"];
			}
		}
		
		$row['landing_page_equiv_id'] = 0;
		$row['landing_page_content_type'] = '';
		if (!$fields['details/landing_page']['hidden'] && $values['details/landing_page']) {
			$equivId = $cType = false;
			ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $values['details/landing_page']);
			$row['landing_page_equiv_id'] = $equivId;
			$row['landing_page_content_type'] = $cType;
		}
		
		$categoryId = ze\row::set('categories', $row, $box['key']['id']);
		if ($box['key']['sub_category']) {
			$box['key']['id'] = $box['key']['parent_id'];
		} else {
			$box['key']['id'] = $categoryId;
		}
		
		if ($values['public']) {
			foreach (ze\lang::getLanguages() as $lang) {
				if (isset($values['visitor_name_' . $lang['id']]) &&$values['visitor_name_' . $lang['id']]) {
					ze\row::set('visitor_phrases', 
								['local_text'=> $values['visitor_name_' . $lang['id']], 
									'language_id' => $lang['id'],
									'code' => '_CATEGORY_'. (int) $categoryId,
									'module_class_name' => 'zenario_common_features'], 
								['language_id' => $lang['id'], 
									'code' => '_CATEGORY_'. (int) $categoryId, 
									'module_class_name' => 'zenario_common_features']
						);
				}
			}
		}
	}
	
}
