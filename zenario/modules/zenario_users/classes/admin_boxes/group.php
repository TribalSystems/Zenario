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

class zenario_users__admin_boxes__group extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($groupId = (int) $box['key']['id']) {
			$groupDetails = ze\row::get('custom_dataset_fields', ['label', 'db_column'], $groupId);
			
			$box['title'] = ze\admin::phrase('Editing the group "[[label]]"', $groupDetails);
			
			$values['details/name'] = $groupDetails['label'];
			$values['details/db_column'] = $groupDetails['db_column'];
			
			# The Users and AI and machine learning modules have an optional dependency on each other.
			# If both are running, show an option in the groups FAB to sync information abut the link between content items and this group into Qdrant payloads
			if (ze\module::inc('zenario_ai_qdrant')) {
				if (zenario_ai_qdrant::isPermissionGroup($groupId)) {
					$values['details/is_qdrant_perm_group'] = 1;
				}
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		// Make sure db_column is unique
		if (!$box['key']['id']
			|| (isset($fields['details/db_column']['current_value'])
				&& $fields['details/db_column']['current_value'] != $fields['details/db_column']['value']
			)
		) {
			$dataset = ze\dataset::details('users');
			if (ze\datasetAdm::checkColumnExistsInDB($dataset['table'], $fields['details/db_column']['current_value'])
				|| ze\datasetAdm::checkColumnExistsInDB($dataset['system_table'], $fields['details/db_column']['current_value'])
			) {
				$fields['details/db_column']['error'] =
					ze\admin::phrase('The code name "[[db_column]]" is already in use.', 
						['db_column' => $fields['details/db_column']['current_value']]);
			}
		}
		
		//Enforce that a maximum of 5 group-memberships can be enabled for syncing into qdrant.
		if (ze\module::inc('zenario_ai_qdrant')) {
			
			$wasSelected = (bool) $fields['details/is_qdrant_perm_group']['value'];
			$isSelected = (bool) $fields['details/is_qdrant_perm_group']['current_value'];
			
			if (!$wasSelected && $isSelected) {
				$groupIds = zenario_ai_qdrant::permissionGroups();
				
				//Work out how many groups we already have.
				$currentCount = count($groupIds);
				$maxLimit = 5;
				
				if ($currentCount >= $maxLimit) {
					$fields['details/is_qdrant_perm_group']['error'] = ze\admin::phrase('A maximum of 5 groups can be used.');
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
		
		$groupDetails = [
			'label' => $values['details/name'], 
			'db_column' => $values['details/db_column'],
		];
		
		$oldName = false;
		if ($box['key']['id']) {
			$oldName = ze\row::get('custom_dataset_fields', 'db_column', $box['key']['id']);
		
		} else {
			$dataset = ze\dataset::details('users');
			$tab_name = ze::setting('default_groups_dataset_tab');
			
			$sql = '
				SELECT
					IFNULL(MAX(ord), 1) + 1
				FROM '. DB_PREFIX. 'custom_dataset_fields
				WHERE dataset_id = ' . (int)$dataset['id'] . '
				AND tab_name = "' . ze\escape::sql($tab_name) . '"';
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchRow($result);
			$ord = $row[0];
			
			$groupDetails['type'] = 'group';
			$groupDetails['tab_name'] = $tab_name;
			$groupDetails['dataset_id'] = $dataset['id'];
			$groupDetails['is_system_field'] = 0;
			$groupDetails['ord'] = $ord;
		}
		
		$groupId = $box['key']['id'] = ze\row::set('custom_dataset_fields', $groupDetails, $box['key']['id']);
		
		ze\datasetAdm::createFieldInDB($box['key']['id'], $oldName);
		
		
		//Update which permission groups are stored in payloads in Qdrant
		if (ze\module::inc('zenario_ai_qdrant')) {
			
			$wasSelected = (bool) $fields['details/is_qdrant_perm_group']['value'];
			$isSelected = (bool) $fields['details/is_qdrant_perm_group']['current_value'];
			
			if ($wasSelected != $isSelected) {
				zenario_ai_qdrant::addOrRemovePermissionGroup($isSelected, $groupId);
			}
		}
	}
}