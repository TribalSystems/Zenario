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

class zenario_users__admin_boxes__group extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$groupDetails = getRow('custom_dataset_fields', array('label', 'db_column'), $box['key']['id']);
			
			$box['title'] = adminPhrase('Editing the group "[[label]]"', $groupDetails);
			
			$values['details/name'] = $groupDetails['label'];
			$values['details/db_column'] = $groupDetails['db_column'];
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		// Make sure db_column is unique
		if (!$box['key']['id']
			|| (isset($fields['details/db_column']['current_value'])
				&& $fields['details/db_column']['current_value'] != $fields['details/db_column']['value']
			)
		) {
			$dataset = getDatasetDetails('users');
			if (checkColumnExistsInDB($dataset['table'], $fields['details/db_column']['current_value'])
				|| checkColumnExistsInDB($dataset['system_table'], $fields['details/db_column']['current_value'])
			) {
				$fields['details/db_column']['error'] =
					adminPhrase('The code name "[[db_column]]" is already in use.', 
						array('db_column' => $fields['details/db_column']['current_value']));
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		
		$groupDetails = array(
			'label' => $values['details/name'], 
			'db_column' => $values['details/db_column'],
		);
		
		$oldName = false;
		if ($box['key']['id']) {
			$oldName = getRow('custom_dataset_fields', 'db_column', $box['key']['id']);
		
		} else {
			$dataset = getDatasetDetails('users');
			$tab_name = setting('default_groups_dataset_tab');
			
			$sql = '
				SELECT
					IFNULL(MAX(ord), 1) + 1
				FROM '. DB_NAME_PREFIX. 'custom_dataset_fields
				WHERE dataset_id = ' . (int)$dataset['id'] . '
				AND tab_name = "' . sqlEscape($tab_name) . '"';
			$result = sqlQuery($sql);
			$row = sqlFetchRow($result);
			$ord = $row[0];
			
			$groupDetails['type'] = 'group';
			$groupDetails['tab_name'] = $tab_name;
			$groupDetails['dataset_id'] = $dataset['id'];
			$groupDetails['is_system_field'] = 0;
			$groupDetails['ord'] = $ord;
		}
		
		$box['key']['id'] = setRow('custom_dataset_fields', $groupDetails, $box['key']['id']);
		
		createDatasetFieldInDB($box['key']['id'], $oldName);
	}
}