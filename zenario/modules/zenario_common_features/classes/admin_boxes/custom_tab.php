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

class zenario_common_features__admin_boxes__custom_tab extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!$box['key']['dataset_id']) {
			if (!$box['key']['dataset_id'] = request('refiner__dataset_id')) {
				exit;
			}
		}
		
		if ($box['key']['id']) {
			$details = getDatasetTabDetails($box['key']['dataset_id'], $box['key']['id']);
			
			$values['details/label'] = $details['label'];
			$values['details/parent_field_id'] = $details['parent_field_id'];
			
			$box['title'] = adminPhrase('Editing the tab "[[label]]"', $details);
		} else {
			$box['title'] = adminPhrase('Creating a tab in the admin box for the dataset "[[label]]"', getDatasetDetails($box['key']['dataset_id']));
		}
		
		//List all possible parent fields...
		$fields['details/parent_field_id']['values'] =
			listCustomFields($box['key']['dataset_id'], false, 'boolean_and_groups_only', false, true);
			//listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
		
		//...but exclude any fields on this tab!
		if ($box['key']['id']) {
			foreach (getRowsArray(
				'custom_dataset_fields',
				'id',
				array('dataset_id' => $box['key']['dataset_id'], 'tab_name' => $box['key']['id'])
			) as $fieldId) {
				unset($fields['details/parent_field_id']['values'][$fieldId]);
			}
		}
		
		if (empty($fields['details/parent_id']['values'])) {
			$fields['details/parent_id']['empty_value'] = adminPhrase(' -- No fields of the right type exist -- ');
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			$cols = array(
				'label' => $values['details/label'],
				'parent_field_id' => $values['details/parent_field_id']);
			
			if (!$box['key']['id']) {
				
				$sql = "
					SELECT
						IFNULL(MAX(ord), 0) + 1,
						IFNULL(MAX(CAST(REPLACE(name, '__custom_tab_', '') AS UNSIGNED)), 0) + 1
					FROM ". DB_NAME_PREFIX. "custom_dataset_tabs
					WHERE dataset_id = ". (int) $box['key']['dataset_id'];
				
				$result = sqlQuery($sql);
				$row = sqlFetchRow($result);
				$cols['ord'] = $row[0];
				$box['key']['id'] = '__custom_tab_'. $row[1];
			}
			
			setRow('custom_dataset_tabs', $cols, array('name' => $box['key']['id'], 'dataset_id' => $box['key']['dataset_id']));
		}
	}
}
