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

class zenario_users__admin_boxes__smart_group extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		
		//Get a list of tabs and fields, and loop through it
		$unsets = array();
		$datasetFields = listCustomFields('users', $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
		
		foreach ($datasetFields as $fieldId => &$field) {
			
			//Look for fields, exclude the tabs
			if (!empty($field['parent']) && !empty($field['type'])) {
				switch ($field['type']) {
					case 'group':
						$field['visible_if'] = "zenarioAB.value('type__znz') == 'group'";
						break;
			
					case 'checkbox':
						$field['visible_if'] = "zenarioAB.value('type__znz') == 'flag'";
						break;
			
					case 'radios':
					case 'centralised_radios':
					case 'select':
					case 'centralised_select':
						$field['visible_if'] = "zenarioAB.value('type__znz') == 'list'";
						break;
			
					case 'date':
						//Maybe implement filters for date fields at some point..?
					default:
						//Remove any fields that we don't handle
						$unsets[] = $fieldId;
						continue 2;
				}
				
				//If a field is flagged as "fundamental", add it to the main list and remove it from the second list
				//Note that currently "fundamental" is only implemented for lists.
				if ($field['fundamental'] && in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
					$box['tabs']['smart_group']['custom_template_fields']['type__znz']['values'][$fieldId] =
						array('ord' => $field['ord'], 'label' => $field['label']);
					$unsets[] = $fieldId;
				}
			}
		}
		unset($field);
		
		foreach ($unsets as $fieldId) {
			unset($datasetFields[$fieldId]);
		}
		
		$box['tabs']['smart_group']['custom_template_fields']['field__znz']['values'] = $datasetFields;
		
		$box['lovs']['dataset_groups'] =
			listCustomFields('users', $flat = false, $filter = 'groups_only', $customOnly = false, $useOptGroups = true);



		if ($box['key']['id'] && ($details = getSmartGroupDetails($box['key']['id']))) {
			$box['title'] = adminPhrase('Editing the smart group "[[name]]".', $details);
			$values['smart_group/name'] = $details['name'];
			$values['smart_group/must_match'] = $details['must_match'];
			
			//Load all of the created rules
			$rules = getRowsArray('smart_group_rules', true, array('smart_group_id' => $box['key']['id']), 'ord');
			
			//Create a row of fields for each rule
			$box['key']['num_rules'] = count($rules);
			$this->setupRuleRows($box, $fields, $values, $changes = array(), $filling = false);
			
			$n = 0;
			foreach ($rules as $rule) {
				//Check if a field is set, and if it's a supported field. Only add it if it is.
				if ($rule['field_id']
				 && ($field = getDatasetFieldDetails($rule['field_id']))
				 && (in($field['type'], 'group', 'checkbox', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
					++$n;
					
					$values['smart_group/field__'. $n] = $field['id'];
					
					if ($field['type'] == 'group') {
						$values['smart_group/type__'. $n] = 'group';
						
						if ($rule['field2_id']
						 || $rule['field3_id']
						 || $rule['field4_id']
						 || $rule['field5_id']) {
							$values['smart_group/field2__'. $n] = $rule['field2_id'];
							$values['smart_group/field3__'. $n] = $rule['field3_id'];
							$values['smart_group/field4__'. $n] = $rule['field4_id'];
							$values['smart_group/field5__'. $n] = $rule['field5_id'];
							$values['smart_group/is_isnt_in__'. $n] = 'is_one_of';
						} else {
							$values['smart_group/is_isnt_in__'. $n] = $rule['not']? 'isnt' : 'is';
						}
					
					} elseif ($field['type'] == 'checkbox') {
						$values['smart_group/type__'. $n] = 'flag';
						$values['smart_group/is_isnt_set__'. $n] = $rule['not']? 'isnt' : 'is';
					
					} else {
						$values['smart_group/type__'. $n] = 'list';
						$values['smart_group/value__'. $n] = $rule['value'];
						$values['smart_group/is_isnt__'. $n] = $rule['not']? 'isnt' : 'is';
					}
					
					//Note: fundamental fields need to appear selected in the first list, not the second
					//Note that currently "fundamental" is only implemented for lists.
					if ($field['fundamental'] && in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
						$values['smart_group/type__'. $n] = $field['id'];
						$values['smart_group/field__'. $n] = '';
					}
				
				//Remove unsupported fields from the list
				} else {
					--$box['key']['num_rules'];
				}
			}
		}
		
		
		return;
	}

	public function setupRuleRows(&$box, &$fields, &$values, $changes, $filling) {
		
		if ($box['key']['num_rules'] < 1) {
			$box['key']['num_rules'] = 1;
		}
		
		//Check to see if we need to add any new rules, and if so, add them by making a new copy of the template fields
		$n = 1;
		while (true) {
			
			$inRange = $n <= $box['key']['num_rules'];
			$fieldsExist = !empty($box['tabs']['smart_group']['fields']['field__'. $n]);
			
			if (!$inRange && !$fieldsExist) {
				break;
			
			} elseif ($inRange && !$fieldsExist) {
				$templateFields = json_decode(str_replace('znz', $n, json_encode($box['tabs']['smart_group']['custom_template_fields'])), true);
				
				foreach ($templateFields as $id => &$field) {
					$box['tabs']['smart_group']['fields'][$id] = $field;
				}
				unset($field);
			}
			
			++$n;
		}
		
		//Check if we need to remove any rules from the end
		$unsets = array();
		foreach ($box['tabs']['smart_group']['fields'] as $fieldId => &$field) {
			if (isset($field['custom_n'])) {
				if ($field['hidden'] = $field['custom_n'] > $box['key']['num_rules']) {
					$unsets[] = $fieldId;
				}
			}
		}
		unset($field);
		
		foreach ($unsets as $fieldId) {
			unset($box['tabs']['smart_group']['fields'][$fieldId]);
		}
		
		
		//We may have created and/or destroyed fields, so update the linking fields
		readAdminBoxValues($box, $fields, $values, $changes, $filling, $resetErrors = false, $preDisplay = true);
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		
		//Add a new rule at the end
		if (!empty($box['tabs']['smart_group']['fields']['add__'. $box['key']['num_rules']]['pressed'])) {
			++$box['key']['num_rules'];
		
		//Remove a rule, bumping any later rules up to it
		} else {
			for ($n = 1; $n <= $box['key']['num_rules']; ++$n) {
				if (!empty($box['tabs']['smart_group']['fields']['remove__'. $n]['pressed'])) {
					unset($box['tabs']['smart_group']['fields']['remove__'. $n]['pressed']);
					
					for (; $n <= $box['key']['num_rules']; ++$n) {
						
						//Loop through each field
						foreach ($box['tabs']['smart_group']['custom_template_fields'] as $fieldId => &$field) {
							if ($field['type'] == 'submit') {
								continue;
							}
							
							//Take the value of each field from the next rule, and paste it onto this rule
							$cutName = str_replace('znz', $n + 1, $fieldId);
							$pstName = str_replace('znz', $n, $fieldId);
					
							foreach (array('value', 'current_value') as $val) {
								if (isset($box['tabs']['smart_group']['fields'][$cutName][$val])) {
									$box['tabs']['smart_group']['fields'][$pstName][$val] =
										$box['tabs']['smart_group']['fields'][$cutName][$val];
									$box['tabs']['smart_group']['fields'][$cutName][$val] = '';
								} else {
									unset($box['tabs']['smart_group']['fields'][$pstName][$val]);
								}
							}
						}
						unset($field);
					}
					
					--$box['key']['num_rules'];
					break;
				}
			}
		}
		
		$this->setupRuleRows($box, $fields, $values, $changes, $filling = false);
		
		//Set the LOV options for every picked field
		$fields['smart_group/no_rules_set']['hidden'] = false;
		for ($n = 1; $n <= $box['key']['num_rules']; ++$n) {
			
			$fieldId = false;
			if ($type = $values['smart_group/type__'. $n]) {
				if (is_numeric($type)) {
					$fieldId = $type;
				} else
				if (($fid = $values['smart_group/field__'. $n])
				 && (is_numeric($fid))) {
					$fieldId = $fid;
				}
			}
			
			$box['tabs']['smart_group']['fields']['value__'. $n]['hidden'] = true;
			if ($field = getDatasetFieldDetails($fieldId)) {
				
				//Set list of values
				if (in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
					
					//Catch the case where the user has just changed the first select list and the wrong thing is selected
					if (!$type || in($type, 'group', 'flag')) {
					} else {
						$lov = getDatasetFieldLOV($field, $flat = false);
						$box['tabs']['smart_group']['fields']['value__'. $n]['values'] = $lov;
						$box['tabs']['smart_group']['fields']['value__'. $n]['hidden'] = empty($lov);
					}
				}
				
				$fields['smart_group/no_rules_set']['hidden'] = true;
			}
		}
		
		$rules = $this->getRulesFromFields($box, $fields, $values);
		$values['smart_group/members'] = countSmartGroupMembers($rules);
	}

	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		
		if (checkRowExists('smart_groups', array('name' => $values['smart_group/name'], 'id' => array('!' => $box['key']['id'])))) {
			$fields['smart_group/name']['error'] = adminPhrase('A smart group with the name "[[smart_group/name]]" already exists. Please choose a different name.', $values);
		}
	}
	
	public function getRulesFromFields(&$box, &$fields, &$values) {
		$rules = array();
		for ($n = 1; $n <= $box['key']['num_rules']; ++$n) {
			
			//For each row, check that a field is selected (remembering that fields are in the
			//"type" select list if they are fundamental fields).
			$fieldId = false;
			if ($type = $values['smart_group/type__'. $n]) {
				if (is_numeric($type)) {
					$fieldId = $type;
				} else
				if (($fid = $values['smart_group/field__'. $n])
				 && (is_numeric($fid))) {
					$fieldId = $fid;
				}
			}
			
			//Check if a field is selected, and if it is a supported type
			if ($fieldId
			 && ($field = getDatasetFieldDetails($fieldId))
			 && (in($field['type'], 'group', 'checkbox', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
				
				
				$rule = array();
				$rule['field_id'] = $fieldId;
				$rule['field2_id'] = 0;
				$rule['field3_id'] = 0;
				$rule['field4_id'] = 0;
				$rule['field5_id'] = 0;
				$rule['value'] = null;
				$rule['not'] = 0;
				$rule['must_match'] = $values['smart_group/must_match'];
				
				if ($field['type'] == 'group') {
					$values['smart_group/type__'. $n] = 'group';
					
					if ($values['smart_group/is_isnt_in__'. $n] == 'is_one_of') {
						$rule['field2_id'] = $values['smart_group/field2__'. $n];
						$rule['field3_id'] = $values['smart_group/field3__'. $n];
						$rule['field4_id'] = $values['smart_group/field4__'. $n];
						$rule['field5_id'] = $values['smart_group/field5__'. $n];
					} else {
						$rule['not'] = engToBoolean($values['smart_group/is_isnt_in__'. $n] == 'isnt');
					}
				
				} elseif ($field['type'] == 'checkbox') {
					$rule['not'] = engToBoolean($values['smart_group/is_isnt_set__'. $n] == 'isnt');
				
				} else {
					$rule['value'] = $values['smart_group/value__'. $n];
					$rule['not'] = engToBoolean($values['smart_group/is_isnt__'. $n] == 'isnt');
				}
				
				$rules[] = $rule;
			}
		}
		
		return $rules;
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		
		//Save the basic details of the smart group
		$details = array();
		$details['name'] = $values['smart_group/name'];
		$details['must_match'] = $values['smart_group/must_match'];
		$details['last_modified_on'] = now();
		$details['last_modified_by'] = adminId();
		
		if (!$box['key']['id']) {
			$details['created_on'] = now();
			$details['created_by'] = adminId();
		}
		
		$box['key']['id'] = setRow('smart_groups', $details, $box['key']['id']);
		
		
		//Loop through saving all of the rules
		$ord = 0;
		$ords = array();
		foreach ($this->getRulesFromFields($box, $fields, $values) as $rule) {
			$ords[] = ++$ord;
			$key = array();
			$key['ord'] = $ord;
			$key['smart_group_id'] = $box['key']['id'];
			unset($rule['must_match']);
			
			setRow('smart_group_rules', $rule, $key);
		}
		
		
		//Delete any old existing rules that weren't just overwritten when saving about
		$sql = "
			DELETE FROM ". DB_NAME_PREFIX. "smart_group_rules
			WHERE smart_group_id = ". (int) $box['key']['id'];
		
		if (!empty($ords)) {
			$sql .= "
			  AND ord NOT IN (". inEscape($ords, 'numeric'). ")";
		}
		
		sqlUpdate($sql);
	}
}