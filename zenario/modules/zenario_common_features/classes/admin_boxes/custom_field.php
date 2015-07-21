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

class zenario_common_features__admin_boxes__custom_field extends module_base_class {
	
	protected function dynamicallyCreateValueFieldsFromTemplate(&$box, &$fields, &$values) {
		$numValues = (int) $box['key']['numValues'];
		
		for ($i = 1; $i <= $numValues; ++$i) {
			//Add new fields that should be there by copying the template fields
			if (!isset($box['tabs']['lov']['fields']['id'. $i])) {
				$box['tabs']['lov']['fields']['id'. $i] = $box['tabs']['lov']['custom__template_fields']['id'];
				$box['tabs']['lov']['fields']['label'. $i] = $box['tabs']['lov']['custom__template_fields']['label'];
				$box['tabs']['lov']['fields']['delete'. $i] = $box['tabs']['lov']['custom__template_fields']['delete'];
				$box['tabs']['lov']['fields']['nudge_up'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_up'];
				$box['tabs']['lov']['fields']['nudge_down'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_down'];
				$box['tabs']['lov']['fields']['id'. $i]['ord'] = 10*$i + 1;
				$box['tabs']['lov']['fields']['label'. $i]['ord'] = 10*$i + 2;
				$box['tabs']['lov']['fields']['delete'. $i]['ord'] = 10*$i + 3;
				$box['tabs']['lov']['fields']['nudge_up'. $i]['ord'] = 10*$i + 4;
				$box['tabs']['lov']['fields']['nudge_down'. $i]['ord'] = 10*$i + 5;
			}
			
			$box['tabs']['lov']['fields']['id'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['label'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['delete'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_up'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_down'. $i]['hidden'] = false;
		}
		
		//Don't show the first nudge-up button or the last nudge-down button
		if ($numValues > 0) {
			$box['tabs']['lov']['fields']['nudge_up'. 1]['hidden'] = true;
			$box['tabs']['lov']['fields']['nudge_down'. $numValues]['hidden'] = true;
		}
	}
	
	protected function nudge(&$box, &$fields, &$values, $i, $j) {
		$id = $values['lov/id'. $i];
		$label = $values['lov/label'. $i];
		$values['lov/id'. $i] = $values['lov/id'. $j];
		$values['lov/label'. $i] = $values['lov/label'. $j];
		$values['lov/id'. $j] = $id;
		$values['lov/label'. $j] = $label;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!$box['key']['id']) {
			exit;
		}
		
		if (is_numeric($box['key']['id'])) {
			$field = getDatasetFieldDetails($box['key']['id']);
			
			if ($field['is_system_field']) {
				echo adminPhrase('You cannot use this tool to edit a system field');
				exit;
			}
			
			$dataset = getDatasetDetails($field['dataset_id']);
			$box['key']['dataset_id'] = $field['dataset_id'];
			
			$values['details/db_column'] = $field['db_column'];
			$values['details/label'] = $field['label'];
			
			
			$values['details/type'] = $field['type'];
			$values['details/values_source'] = $field['values_source'];
			$values['details/values_source_filter'] = $field['values_source_filter'];
			$values['details/protected'] = $field['protected'];
			$values['details/include_in_export'] = $field['include_in_export'];
			
			$values['display/width'] = $field['width'];
			$values['display/height'] = $field['height'];
			$values['display/parent_id'] = $field['parent_id'];
			$values['display/note_below'] = $field['note_below'];
			
			$values['validation/required'] = $field['required'];
			$values['validation/required_message'] = $field['required_message'];
			$values['validation/validation'] = $field['validation'];
			$values['validation/validation_message'] = $field['validation_message'];
			
			$values['organizer/show_in_organizer'] = $field['show_in_organizer'];
			$values['organizer/searchable'] = $field['searchable'];
			$values['organizer/sortable'] = $field['sortable'];
			$values['organizer/visibility'] = $field['always_show']? 2 : ($field['show_by_default']? 1 : 0);
			
			$box['title'] = adminPhrase('Editing the field "[[label]]"', $field);
			
			//Prevent admins from switching types if they are different field definitions
			$unsets = array();
			foreach ($fields['details/type']['values'] as $type => $label) {
				$oldType = getDatasetFieldDefinition($field['type']);
				$newType = getDatasetFieldDefinition($type);
				
				//Allow turning varchar fields into longer TEXT fields
				if ($oldType === " varchar(255) NOT NULL default ''" && $newType === " TEXT") {
					continue;
				}
				
				//Allow turning TEXT fields into varchar fields if they are not too long
				if ($newType === " varchar(255) NOT NULL default ''" && $oldType === " TEXT") {
					$sql = "
						SELECT 1
						FROM `". DB_NAME_PREFIX. sqlEscape($dataset['table']). "`
						WHERE LENGTH(`". sqlEscape($field['db_column']). "`) > 255
						LIMIT 1";
					
					$result = sqlQuery($sql);
					if (!sqlFetchAssoc($result)) {
						continue;
					}
				}
				
				//Disallow anything that's not compatable
				if ($oldType !== $newType) {
					$unsets[] = $type;
				}
			}
			foreach ($unsets as $unset) {
				unset($fields['details/type']['values'][$unset]);
			}
			
			//Load the lists of values for checkboxes/radiogroups/select lists
			if (in($field['type'], 'checkboxes', 'radios', 'select')) {
				
				$lov = getDatasetFieldLOV($field, false);
				$numValues = $box['key']['numValues'] = count($lov);
				$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
				
				$i = 0;
				foreach ($lov as $lovId => $v) {
					++$i;
					$box['tabs']['lov']['fields']['id'. $i]['value'] = $lovId;
					$box['tabs']['lov']['fields']['label'. $i]['value'] = $v['label'];
				}
			}
		
		

		
		} else {
			if (!$box['key']['dataset_id'] = request('refiner__dataset_id')) {
				exit;
			}
			$dataset = getDatasetDetails($box['key']['dataset_id']);
			
			$box['key']['tab_name'] = $box['key']['id'];
			$box['key']['id'] = false;
			
			$box['title'] = adminPhrase('Creating a field for the dataset "[[label]]"', getDatasetDetails($box['key']['dataset_id']));
			
			//Start lists of values with two empty values
			$box['key']['numValues'] = 2;
			$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
		}
		
		//List all possible parent fields...
		$fields['display/parent_id']['values'] =
			listCustomFields($box['key']['dataset_id'], false, 'boolean_and_groups_only', false, true);
			//listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
		
		//...being careful not to let something be a parent of itself, or any of its children!
		if ($box['key']['id'] && isset($field)) {
			$children = array($field['id'] => $field);
			getCustomFieldsChildren($field, $children);
			foreach ($children as $child) {
				unset($fields['display/parent_id']['values'][$child['id']]);
			}
		}
		
		//Groups are a special type of field that can only be created under the "users" dataset
		if ($dataset['table'] != 'users_custom_data') {
			unset($fields['details/type']['values']['group']);
		}
		
		if (empty($fields['display/parent_id']['values'])) {
			$fields['display/parent_id']['empty_value'] = adminPhrase(' -- No fields of the right type exist -- ');
		}
		
		if($values['details/protected']){
			$fields['details/db_column']['read_only']=true;
			$fields['details/type']['read_only']=true;
		}
			
		if (!checkPriv('_PRIV_PROTECT_UNPROTECT_DATASET_FIELD')){
			$fields['details/protected']['read_only'] = true;
		}
		
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		
		if (in($values['details/type'], 'checkboxes', 'radios', 'select')) {
			$numValues = (int) $box['key']['numValues'];
		
			//Add new blank fields when the Admin presses the "add" button
			if (!empty($box['tabs']['lov']['fields']['add']['pressed'])) {
				$box['key']['numValues'] = (int) $numValues + (int) $values['lov/add_num'];
		
			} elseif ($numValues > 1) {
				//Watch out for the admin pressing the delete or nudge buttons
				for ($i = 1; $i <= $numValues; ++$i) {
					if (!empty($box['tabs']['lov']['fields']['delete'. $i]['pressed'])) {
					
						//If they press the delete button, loop through all the remaining values and nudge them up by one
						for ($j = $i; $j < $numValues; ++$j) {
							$this->nudge($box, $fields, $values, $j, $j + 1);
						}
					
						//Delete the last field (which will now contain the value we're deleting)
						unset($box['tabs']['lov']['fields']['id'. $numValues]);
						unset($box['tabs']['lov']['fields']['label'. $numValues]);
						unset($box['tabs']['lov']['fields']['delete'. $numValues]);
						unset($box['tabs']['lov']['fields']['nudge_up'. $numValues]);
						unset($box['tabs']['lov']['fields']['nudge_down'. $numValues]);
					
						//Reduce the field count by one
						$box['key']['numValues'] = --$numValues;
					
						break;
				
					//Handle nudge up or down
					} else
					if (!empty($box['tabs']['lov']['fields']['nudge_up'. $i]['pressed']) && $i > 1) {
						$this->nudge($box, $fields, $values, $i - 1, $i);
						break;
					} else
					if (!empty($box['tabs']['lov']['fields']['nudge_down'. $i]['pressed']) && $i < $numValues) {
						$this->nudge($box, $fields, $values, $i, $i + 1);
						break;
					}
				}
			}
			$this->dynamicallyCreateValueFieldsFromTemplate($box, $fields, $values);
		}
		
		if ($values['details/values_source']
		 && ($source = explode('::', $values['details/values_source'], 3))
		 && (!empty($source[0]))
		 && (!empty($source[1]))
		 && (!isset($source[2]))
		 && (inc($source[0]))) {
			$listInfo = call_user_func($source, ZENARIO_CENTRALISED_LIST_MODE_INFO);
			$fields['values_source_filter']['hidden'] = !$listInfo['can_filter'];
			if (!empty($listInfo['filter_label'])) {
				$fields['values_source_filter']['label'] = $listInfo['filter_label'];
			}
		}
		
		if (is_numeric($box['key']['id'])) {
			if($values['details/protected']){
				$fields['details/db_column']['read_only']=true;
				$fields['details/type']['read_only']=true;
			}else{
				$fields['details/db_column']['read_only']=false;
				$fields['details/type']['read_only']=false;
			}
		}
		
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$dataset = getDatasetDetails($box['key']['dataset_id']);
		
		
		if (isset($fields['details/db_column']['current_value'])){
			if (!$box['key']['id']
			 || $fields['details/db_column']['current_value'] != $fields['details/db_column']['value']) {
				if (checkColumnExistsInDB($dataset['table'], $fields['details/db_column']['current_value'])
				 || checkColumnExistsInDB($dataset['system_table'], $fields['details/db_column']['current_value'])) {
					$fields['details/db_column']['error'] =
						adminPhrase('The code name "[[db_column]]" is already in use in this dataset.', 
							array('db_column' => $fields['details/db_column']['current_value']));
				}
			}
		}
		
		
		
		
		
		
		
		
		if ($values['validation/required']
		 && !$values['validation/required_message']) {
			$fields['validation/required_message']['error'] = adminPhrase('Please enter a message if not complete.');
		}
		
		if ($values['validation/validation']
		 && $values['validation/validation'] != 'none'
		 && !$values['validation/validation_message']) {
			$fields['validation/validation_message']['error'] = adminPhrase('Please enter a message if not valid.');
		}
		
		if ($saving
		 && in($values['details/type'], 'checkboxes', 'radios', 'select')) {
			
			$lovByLabel = array();
			$numValues = (int) $box['key']['numValues'];
			
			for ($i = 1; $i <= $numValues; ++$i) {
				if (isset($values['lov/label'. $i])
				 && $values['lov/label'. $i] !== '') {
					
					if (!isset($lovByLabel[$values['lov/label'. $i]])) {
						$lovByLabel[$values['lov/label'. $i]] = true;
					} else {
						$box['tabs']['lov']['errors'][] =
							adminPhrase('You have entered "[[label]]" more than once.', array('label' => $values['lov/label'. $i]));
					}
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//if (!empty($box['tabs']['details']['edit_mode']['on'])) {
			$IsCentralised = ($values['details/type'] == 'centralised_radios' || $values['details/type'] == 'centralised_select');
			$cols = array(
				'db_column' => $values['details/db_column'],
				'label' => $values['details/label'],
			
				'type' => $values['details/type'],
				'values_source' => $IsCentralised ? $values['details/values_source'] : '',
				'values_source_filter' => $IsCentralised ? $values['details/values_source_filter'] : '',
				'width' => $values['display/width'],
				'height' => $values['display/height'],
				'parent_id' => $values['display/parent_id'],
				'note_below' => $values['display/note_below'],
			
				'required' => $values['validation/required'],
				'required_message' => $values['validation/required']?
					$values['validation/required_message'] : '',
				'validation' => $values['validation/validation'],
				'validation_message' => $values['validation/validation'] && $values['validation/validation'] != 'none'?
					$values['validation/validation_message'] : '',
			
				'show_in_organizer' => $values['organizer/show_in_organizer'],
				'searchable' => $values['organizer/show_in_organizer'] && $values['organizer/searchable'],
				'sortable' => $values['organizer/show_in_organizer'] && $values['organizer/sortable'],
				'show_by_default' => $values['organizer/show_in_organizer'] && $values['organizer/visibility'] == 1,
				'always_show' => $values['organizer/show_in_organizer'] && $values['organizer/visibility'] == 2,
				
				'label' => $values['details/label'],
				'include_in_export' => $values['details/include_in_export']);
			
			//if (checkPriv('_PRIV_PROTECT_DATASET_FIELD')) {
				$cols['protected'] = $values['details/protected'];
			//}
			
			$oldName = false;
			if (!$box['key']['id']) {
				$cols['tab_name'] = $box['key']['tab_name'];
				$cols['dataset_id'] = $box['key']['dataset_id'];
				$cols['is_system_field'] = 0;
				
				$sql = "
					SELECT
						IFNULL(MAX(ord), 0) + 1
					FROM ". DB_NAME_PREFIX. "custom_dataset_fields
					WHERE dataset_id = ". (int) $box['key']['dataset_id'];
				
				$result = sqlQuery($sql);
				$row = sqlFetchRow($result);
				$cols['ord'] = $row[0];
			
			} else {
				if ($oldField = getDatasetFieldDetails($box['key']['id'])) {
					$oldName = $oldField['db_column'];
				}
			}
			
			$box['key']['id'] = setRow('custom_dataset_fields', $cols, $box['key']['id']);
			
			createDatasetFieldInDB($box['key']['id'], $oldName);
			
			
			if (in($values['details/type'], 'checkboxes', 'radios', 'select')) {
				
				$existingValues = array();
				$newValues = array();
				
				$numValues = (int) $box['key']['numValues'];
				
				for ($i = 1; $i <= $numValues; ++$i) {
					if (isset($values['lov/label'. $i])
					 && $values['lov/label'. $i] !== '') {
						if (!empty($values['lov/id'. $i])) {
							$existingValues[$values['lov/id'. $i]] =
								array('label' => $values['lov/label'. $i], 'ord' => $i);
						} else {
							$newValues[] =
								array('label' => $values['lov/label'. $i], 'ord' => $i, 'field_id' => $box['key']['id']);
						}
					}
				}
				
				//Delete any existing values that were removed
					//To do - what about checking whether people have these chosen?!?
				if ($box['key']['id']) {
					$sql = "
						DELETE cdfv.*, cdvl.*
						FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
						LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS cdvl
						   ON cdfv.id = cdvl.value_id
						WHERE cdfv.field_id = ". (int) $box['key']['id'];
					
					if (!empty($existingValues)) {
						$sql .= "
							AND cdfv.id NOT IN(". inEscape(array_keys($existingValues), 'numeric'). ")";
					}
					
					sqlQuery($sql);
				}
				
				//Update the existing values
				foreach ($existingValues as $lovId => $v) {
					updateRow('custom_dataset_field_values', $v, $lovId);
				}
				
				//Add any new values
				foreach ($newValues as $v) {
					insertRow('custom_dataset_field_values', $v);
				}
			}
		//}
	}
}