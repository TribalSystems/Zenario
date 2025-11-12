<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


class zenario_users__privacy_options_base extends zenario_users {
	
	
	protected function fillPrivacySettings($path, $settingGroup, &$box, &$fields, &$values) {
		$duplicatingOrTranslating = (!empty($box['key']['duplicate']) || !empty($box['key']['duplicate_from_menu']) || !empty($box['key']['translate']));
		
		if (!ze\module::isRunning('zenario_extranet')) {
			foreach ($fields['privacy/privacy']['values'] as $name => $setting) {
				if ($name != 'public') {
					$fields['privacy/privacy']['values'][$name]['hidden'] = true;
				}
			}

			$fields['privacy/privacy_settings_disabled_note']['hidden'] = false;
		}
		
		$userCount = ze\row::count('users', ['status' => 'active']);
		$userCountPhrase = ze\admin::nPhrase('1 user', '[[count]] users', $userCount, ['count' => $userCount]);
		ze\lang::applyMergeFields($fields['privacy/privacy']['values']['logged_in']['label'], ['user_count' => $userCountPhrase]);
		
		$fields['privacy/group_ids']['values'] = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
		$fields['privacy/smart_group_id']['values'] = ze\contentAdm::getListOfSmartGroupsWithCounts();
		
		if ($duplicatingOrTranslating) {
			$fields['privacy/group_ids_original']['values'] = $fields['privacy/group_ids']['values'];
			$fields['privacy/smart_group_id_original']['values'] = $fields['privacy/smart_group_id']['values'];
		}

		if (count($fields['privacy/smart_group_id']['values']) > 0) {
			unset($box['tabs']['privacy']['fields']['no_smart_groups_defined']);
		} else {
			unset($fields['privacy/smart_group_id']['visible_if']);
			$fields['privacy/smart_group_id']['hidden'] = true;
		}
	
		if (ze\module::inc('zenario_organization_manager')) {
			$fields['privacy/role_ids']['values'] = zenario_organization_manager::getRoleTypesIndexedByIdOrderedByName();
		} else {
			$fields['privacy/role_ids']['hidden'] =
			$fields['privacy/privacy']['values']['with_role']['hidden'] = true;
		}
		
		
		# The Users and AI and machine learning modules have an optional dependency on each other.
		# If both are running, mention which group are enabled to work with the semantic search plugin
		if (ze\module::inc('zenario_ai_qdrant')) {
			foreach ($fields['privacy/group_ids']['values'] as $groupId => &$val) {
				if (zenario_ai_qdrant::isPermissionGroup($groupId)) {
					$val['label'] .= ' '. ze\admin::phrase('(semantic search enabled)');
				}
			}
			unset($val);
		}
		
		if ($duplicatingOrTranslating) {
			$this->setupPrivacySettingsForOriginalContentItem($box);
		}
	}
		
	protected function loadPrivacySettings($tagIdsCSV, $path, $settingGroup, &$box, &$fields, &$values) {
		$tagIds = [];
		$theseValues =
		$lastValues = false;
		$combinedValues = true;
		$equivId = $cType = false;
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (ze\ray::explodeAndTrim($tagIdsCSV) as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					
					//Attempt to see if all of the chosen items have the same values
					
					//If we've already failed trying to match values, stop trying
					if ($combinedValues !== false) {
						
						//Look up the values for this content item
						$sql = "
							SELECT
								tc.privacy, tc.at_location, tc.smart_group_id,
								tcp.module_class_name, tcp.method_name, tcp.param_1, tcp.param_2
							FROM ". DB_PREFIX. "translation_chains AS tc
							LEFT JOIN ". DB_PREFIX. "translation_chain_privacy AS tcp
							   ON tc.equiv_id = tcp.equiv_id
							  AND tc.type = tcp.content_type
							WHERE tc.equiv_id = ". (int) $equivId. "
							  AND tc.type = '". ze\escape::asciiInSQL($cType). "'";
						
						if ($chain = ze\sql::fetchAssoc($sql)) {
							
							if ($chain['privacy'] == 'group_members') {
								$chain['group_ids'] =
									ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]), true);
							} else {
								$chain['group_ids'] = '';
							}
							
							if ($chain['privacy'] == 'with_role') {
								$chain['role_ids'] =
									ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]), true);
							} else {
								$chain['role_ids'] = '';
							}
							
							$theseValues = json_encode($chain);
							
							//If the values were the same as last time, combine them
							if ($lastValues === false
							 || $lastValues == $theseValues) {
								$lastValues = $theseValues;
								$combinedValues = $chain;
							
							//If they're not, we can't combine them, and will need to show the settings as empty
							} else {
								$combinedValues = false;
							}
						}
					}
				}
			}
		}
		
		//If all the values match, display them!
		if (!empty($combinedValues) && is_array($combinedValues)) {
			$values['privacy/privacy'] = $combinedValues['privacy'];
			$values['privacy/at_location'] = $combinedValues['at_location'];
			$values['privacy/group_ids'] = $combinedValues['group_ids'];
			$values['privacy/role_ids'] = $combinedValues['role_ids'];
			$values['privacy/smart_group_id'] = $combinedValues['smart_group_id'];
			$values['privacy/module_class_name'] = $combinedValues['module_class_name'];
			$values['privacy/method_name'] = $combinedValues['method_name'];
			$values['privacy/param_1'] = $combinedValues['param_1'];
			$values['privacy/param_2'] = $combinedValues['param_2'];
			
			$box['key']['privacy_settings_on_load']['privacy_setting_value'] = $values['privacy/privacy'];
			
			switch ($values['privacy/privacy']) {
				case 'group_members':
					$box['key']['privacy_settings_on_load']['group_ids'] = $values['privacy/group_ids'];
					break;
				case 'in_smart_group':
				case 'logged_in_not_in_smart_group':
					$box['key']['privacy_settings_on_load']['smart_group_id'] = $values['privacy/smart_group_id'];
					break;
				case 'with_role':
					$box['key']['privacy_settings_on_load']['role_ids'] = $values['privacy/role_ids'];
					$box['key']['privacy_settings_on_load']['at_location'] = $values['privacy/at_location'];
					break;
				case 'call_static_method':
					$box['key']['privacy_settings_on_load']['module_class_name'] = $values['privacy/module_class_name'];
					$box['key']['privacy_settings_on_load']['method_name'] = $values['privacy/method_name'];
					$box['key']['privacy_settings_on_load']['param_1'] = $values['privacy/param_1'];
					$box['key']['privacy_settings_on_load']['param_2'] = $values['privacy/param_2'];
					break;
			}
		}
		
		return $tagIds;
	}
	
	
	
	protected function validatePrivacySettings($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		switch ($values['privacy/privacy']) {
			case 'call_static_method':
				if (!$values['privacy/module_class_name']) {
					$fields['privacy/module_class_name']['error'] = ze\admin::phrase('Please enter the class name of a module.');
	
				} elseif (!ze\module::inc($values['privacy/module_class_name'])) {
					$fields['privacy/module_class_name']['error'] = ze\admin::phrase('Please enter the class name of a module that you have running on this site.');
	
				} elseif ($values['privacy/method_name']
					&& !method_exists(
							$values['privacy/module_class_name'],
							$values['privacy/method_name'])
				) {
					$fields['privacy/method_name']['error'] = ze\admin::phrase('Please enter the name of an existing public static method.');
				}
	
				if (!$values['privacy/method_name']) {
					$fields['privacy/method_name']['error'] = ze\admin::phrase('Please enter the name of a public static method.');
				}
				break;
		}
	}
	
	
	protected function savePrivacySettings($tagIds, $values) {
		$equivId = $cType = false;
		foreach ($tagIds as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				
				$key = ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType];
				$chain = ['privacy' => $values['privacy/privacy']];
				
				if ($chain['privacy'] == 'group_members') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/group_ids']);
				} else {
					ze\row::delete('group_link', $key);
				}
				
				$key['link_to'] = 'role';
				if ($chain['privacy'] == 'with_role') {
					ze\miscAdm::updateLinkingTable('group_link', $key, 'link_to_id', $values['privacy/role_ids']);
					$chain['at_location'] = $values['privacy/at_location'];
				} else {
					ze\row::delete('group_link', $key);
					$chain['at_location'] = 'any';
				}
				
				$key = ['equiv_id' => $equivId, 'content_type' => $cType];
				if ($chain['privacy'] == 'call_static_method') {
					ze\row::set('translation_chain_privacy', [
						'module_class_name' => $values['privacy/module_class_name'],
						'method_name' => $values['privacy/method_name'],
						'param_1' => $values['privacy/param_1'],
						'param_2' => $values['privacy/param_2']
					], $key);
				} else {
					ze\row::delete('translation_chain_privacy', $key);
				}
				
				if (ze::in($chain['privacy'], 'in_smart_group', 'logged_in_not_in_smart_group')) {
					$chain['smart_group_id'] = $values['privacy/smart_group_id'];
				} else {
					$chain['smart_group_id'] = 0;
				}
				
				//Save the privacy settings
				ze\row::set(
					'translation_chains',
					$chain,
					['equiv_id' => $equivId, 'type' => $cType]);
				
				
				//For document content items, we'll need to check if any files should now be either
				//added or removed from the public/documents/ directory.
				if ($cType == 'document') {
					
					//Look for all of the content items in this translation chain
					$sql = "
						SELECT c.id, c.type, c.visitor_version
						FROM ". DB_PREFIX. "content_items AS c
						WHERE c.equiv_id = ". (int) $equivId. "
						  AND c.type = '". \ze\escape::asciiInSQL($cType). "'
						  AND c.visitor_version != 0";
					
					foreach (\ze\sql::select($sql) as $content) {
						\ze\contentAdm::reviewDocumentsInPublicDir($content['id'], $content['type'], $content['visitor_version']);
					}
				}
				
				\ze\module::sendSignal('eventContentPrivacyUpdated', ['equivId' => $equivId,'cType' => $cType]);
			}
		}
	}
	
	private function setupPrivacySettingsForOriginalContentItem(&$box) {
		$box['tabs']['privacy']['fields']['privacy_original'] = $box['tabs']['privacy']['fields']['privacy'];
		$box['tabs']['privacy']['fields']['privacy_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['privacy_original']['label'] = ze\admin::phrase('Permission to access (original):');
		$box['tabs']['privacy']['fields']['privacy_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['privacy_original']['validation']);
		unset($box['tabs']['privacy']['fields']['privacy_original']['format_onchange']);
		
		$box['tabs']['privacy']['fields']['group_ids_original'] = $box['tabs']['privacy']['fields']['group_ids'];
		$box['tabs']['privacy']['fields']['group_ids_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['group_ids_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['group_ids_original']['validation']);
		unset($box['tabs']['privacy']['fields']['group_ids_original']['format_onchange']);
		unset($box['tabs']['privacy']['fields']['group_ids_original']['visible_if']);
		$box['tabs']['privacy']['fields']['group_ids_original']['visible_if'] = [
			'zenarioAB.valueIs' => 'privacy_original, group_members'
		];
		
		$box['tabs']['privacy']['fields']['smart_group_id_original'] = $box['tabs']['privacy']['fields']['smart_group_id'];
		$box['tabs']['privacy']['fields']['smart_group_id_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['smart_group_id_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['smart_group_id_original']['validation']);
		unset($box['tabs']['privacy']['fields']['smart_group_id_original']['format_onchange']);
		unset($box['tabs']['privacy']['fields']['smart_group_id_original']['visible_if']);
		$box['tabs']['privacy']['fields']['smart_group_id_original']['visible_if'] = [
			'zenarioAB.valueIn' => 'privacy_original, in_smart_group, logged_in_not_in_smart_group'
		];
		
		$box['tabs']['privacy']['fields']['role_ids_original'] = $box['tabs']['privacy']['fields']['role_ids'];
		$box['tabs']['privacy']['fields']['role_ids_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['role_ids_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['role_ids_original']['validation']);
		unset($box['tabs']['privacy']['fields']['role_ids_original']['format_onchange']);
		unset($box['tabs']['privacy']['fields']['role_ids_original']['visible_if']);
		$box['tabs']['privacy']['fields']['role_ids_original']['visible_if'] = [
			'zenarioAB.valueIs' => 'privacy_original, with_role'
		];
		
		$box['tabs']['privacy']['fields']['at_location_original'] = $box['tabs']['privacy']['fields']['at_location'];
		$box['tabs']['privacy']['fields']['at_location_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['at_location_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['at_location_original']['format_onchange']);
		
		$box['tabs']['privacy']['fields']['module_class_name_original'] = $box['tabs']['privacy']['fields']['module_class_name'];
		$box['tabs']['privacy']['fields']['module_class_name_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['module_class_name_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['module_class_name_original']['visible_if']);
		$box['tabs']['privacy']['fields']['module_class_name_original']['visible_if'] = [
			'zenarioAB.valueIs' => 'privacy_original, call_static_method'
		];
		
		$box['tabs']['privacy']['fields']['method_name_original'] = $box['tabs']['privacy']['fields']['method_name'];
		$box['tabs']['privacy']['fields']['method_name_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['method_name_original']['readonly'] = true;
		
		$box['tabs']['privacy']['fields']['param_1_original'] = $box['tabs']['privacy']['fields']['param_1'];
		$box['tabs']['privacy']['fields']['param_1_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['param_1_original']['readonly'] = true;
		
		$box['tabs']['privacy']['fields']['param_2_original'] = $box['tabs']['privacy']['fields']['param_2'];
		$box['tabs']['privacy']['fields']['param_2_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['param_2_original']['readonly'] = true;
		
		$box['tabs']['privacy']['fields']['signal_name_original'] = $box['tabs']['privacy']['fields']['signal_name'];
		$box['tabs']['privacy']['fields']['signal_name_original']['indent'] = 1;
		$box['tabs']['privacy']['fields']['signal_name_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['signal_name_original']['readonly'] = true;
		unset($box['tabs']['privacy']['fields']['signal_name_original']['hide_with_previous_field']);
		unset($box['tabs']['privacy']['fields']['signal_name_original']['visible_if']);
		$box['tabs']['privacy']['fields']['signal_name_original']['visible_if'] = [
			'zenarioAB.valueIs' => 'privacy_original, send_signal'
		];
		
		$box['tabs']['privacy']['fields']['privacy_part_2_original'] = $box['tabs']['privacy']['fields']['privacy_part_2'];
		$box['tabs']['privacy']['fields']['privacy_part_2_original']['grouping'] = 'privacy_grouping_left';
		$box['tabs']['privacy']['fields']['privacy_part_2_original']['snippet']['show_split_values_from'] = 'privacy_original';
		unset($box['tabs']['privacy']['fields']['privacy_part_2_original']['note_below']);
	}
}