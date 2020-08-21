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

class zenario_users__admin_boxes__content extends zenario_users {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$cID = $box['key']['source_cID'];
		$cType = $box['key']['cType'];
		$equivId = $chain = false;
		
		//Try to load the privacy options from the translation_chains table.
		if ($cID && $cType
		 && ($equivId = ze\content::equivId($box['key']['source_cID'], $box['key']['cType']))
		 && ($chain = ze\row::get('translation_chains', true, ['equiv_id' => $equivId, 'type' => $cType]))) {
			
			$values['privacy/privacy'] = $chain['privacy'];
			$values['privacy/at_location'] = $chain['at_location'];
		}
		
		if (empty($box['tabs']['privacy']['hidden'])) {
			$cType = $box['key']['cType'];
			
			$fields['privacy/group_ids']['values'] = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
			$fields['privacy/smart_group_id']['values'] = ze\contentAdm::getListOfSmartGroupsWithCounts();
		
			if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager')) {
				$fields['privacy/role_ids']['values'] = ze\row::getValues($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name');
			} else {
				$fields['privacy/role_ids']['hidden'] =
				$fields['privacy/privacy']['values']['with_role']['hidden'] = true;
			
			}
			
			
			if ($chain) {
				
				switch ($chain['privacy']) {
					case 'group_members':
						$ids = ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]);
						
						//Catch the case where one or more IDs reference a deleted group.
						//The query below will return existing groups.
						$sql = '
							SELECT id
							FROM ' . DB_PREFIX . 'custom_dataset_fields
							WHERE id IN (' . ze\escape::in($ids, true) . ')';
						$result = ze\sql::select($sql);
						
						$groupIds = ze\sql::fetchValues($result);
						$values['privacy/group_ids'] = ze\escape::in($groupIds, true);
						break;
					
					case 'with_role':
						$values['privacy/role_ids'] =
							ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]), true);
						break;
					
					case 'call_static_method':
						if ($privacySettings = ze\row::get('translation_chain_privacy', true, ['equiv_id' => $equivId, 'content_type' => $cType])) {
							$values['privacy/module_class_name'] = $privacySettings['module_class_name'];
							$values['privacy/method_name'] = $privacySettings['method_name'];
							$values['privacy/param_1'] = $privacySettings['param_1'];
							$values['privacy/param_2'] = $privacySettings['param_2'];
						}
						break;
					
					case 'in_smart_group':
					case 'logged_in_not_in_smart_group':
						$values['privacy/smart_group_id'] = $chain['smart_group_id'];
						break;
				}
			
			} else {
				//Default newly create items to content type setting
				$values['privacy/privacy'] = 'public';
				if ($contentTypeDetails = ze\contentAdm::cTypeDetails($cType)) {
					$values['privacy/privacy'] = $contentTypeDetails['default_permissions'];
				}
			}
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Hide the site-map/search engine preview for non-public pages
		if ($values['privacy/privacy'] != 'public') {
			$fields['meta_data/excluded_from_sitemap']['hidden'] = false;
			$fields['meta_data/included_in_sitemap']['hidden'] = true;
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)) {
			
			switch ($values['privacy/privacy']) {
				case 'call_static_method':
					if (!$values['privacy/module_class_name']) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the class name of a module.');
		
					} elseif (!ze\module::inc($values['privacy/module_class_name'])) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the class name of a module that you have running on this site.');
		
					} elseif ($values['privacy/method_name']
						&& !method_exists(
								$values['privacy/module_class_name'],
								$values['privacy/method_name'])
					) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the name of an existing public static method.');
					}
		
					if (!$values['privacy/method_name']) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the name of a public static method.');
					}
					break;
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$tagIds = [$box['key']['cType']. '_'. $box['key']['cID']];
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS')) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}
}