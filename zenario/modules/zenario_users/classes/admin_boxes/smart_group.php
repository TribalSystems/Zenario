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

class zenario_users__admin_boxes__smart_group extends zenario_users {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
		
		$n = 0;
		if ($box['key']['id'] && ($details = ze\smartGroup::details($box['key']['id']))) {
			
			$values['smart_group/name'] = $details['name'];
			$values['smart_group/must_match'] = $details['must_match'];
			$box['key']['intended_usage'] = $details['intended_usage'];
			
			//Load all of the created rules
			$rules = ze\row::getAssocs('smart_group_rules', true, ['smart_group_id' => $box['key']['id']], 'ord');
			
			//Create a row of fields for each rule
			foreach ($rules as $rule) {
				switch ($rule['type_of_check']) {
					
					//Load information on user fields
					case 'user_field':
						//Check if a field is set, and if it's a supported field. Only add it if it is.
						if ($rule['field_id']
						 && ($field = ze\dataset::fieldDetails($rule['field_id']))
						 && (ze::in($field['type'], 'group', 'checkbox', 'consent', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
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
					
							} elseif ($field['type'] == 'checkbox' || $field['type'] == 'consent') {
								$values['smart_group/type__'. $n] = $field['type'];
								$values['smart_group/is_isnt_set__'. $n] = $rule['not']? 'isnt' : 'is';
							
							} else {
								$values['smart_group/type__'. $n] = 'list';
								$values['smart_group/value__'. $n] = $rule['value'];
								$values['smart_group/is_isnt__'. $n] = $rule['not']? 'isnt' : 'is';
							}
					
							//Note: fundamental fields need to appear selected in the first list, not the second
							//Note that currently "fundamental" is only implemented for lists.
							if ($field['fundamental'] && ze::in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
								$values['smart_group/type__'. $n] = $field['id'];
								$values['smart_group/field__'. $n] = '';
							}
						}
						break;
					
					case 'in_a_group':
						++$n;
						$values['smart_group/type__'. $n] = 'in_a_group';
						break;
					case 'not_in_a_group':
						++$n;
						$values['smart_group/type__'. $n] = 'not_in_a_group';
						break;
					//If a role is picked, set the select list
					case 'role':
						++$n;
						$values['smart_group/type__'. $n] = 'role';
						$values['smart_group/role__'. $n] = $rule['role_id'];
						break;		
						
					case 'activity_band':
						++$n;
						$values['smart_group/type__'. $n] = 'activity_band';
						$values['smart_group/activity_bands__'. $n] = $rule['activity_band_id'];
						$values['smart_group/is_isnt_in_activity_band__'. $n] = $rule['not']? 'isnt' : 'is';
						break;
						
				}
			}
			
			switch ($box['key']['intended_usage']) {
				case 'smart_newsletter_group':
					$box['title'] = ze\admin::phrase('Editing the smart newsletter group "[[name]]".', $details);
					break;
				case 'smart_permissions_group':
					$box['title'] = ze\admin::phrase('Editing the smart group "[[name]]".', $details);
					break;
			}
		
		} else {
			$box['key']['id'] = '';
			
			switch ($box['key']['intended_usage']) {
				case 'smart_newsletter_group':
					$box['title'] = ze\admin::phrase('Creating a smart newsletter group');
					break;
				case 'smart_permissions_group':
					$box['title'] = ze\admin::phrase('Creating a smart group');
					break;
			}
		}
		
		$changes = [];
		$multiRows = $this->setupRuleRows($box, $fields, $values, $changes, $filling = true, $n);
		$box['key']['num_rules'] = $multiRows['numRows'];
		
		
		
		//Check whether various other modules are running
		if (ze\module::prefix('assetwolf_2', $mustBeRunning = true)) {
			$box['key']['assetsExist'] = true;
		}
		if (ze\module::prefix('zenario_location_manager', $mustBeRunning = true)) {
			$box['key']['locationsExist'] = true;
		}
		if (ze\module::prefix('zenario_company_locations_manager', $mustBeRunning = true)) {
			$box['key']['companiesExist'] = true;
		}
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager', $mustBeRunning = true)) {
			$box['lovs']['roles'] = ze\row::getValues($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name', 'id');
			$box['key']['rolesExist'] = !empty($box['lovs']['roles']);
		}
		
		
		if($ZENARIO_USER_ACTIVITY_BANDS_PREFIX = ze\module::prefix('zenario_user_activity_bands', $mustBeRunning = true)){
			$box['lovs']['activity_band'] = ze\row::getValues($ZENARIO_USER_ACTIVITY_BANDS_PREFIX. 'activity_bands', 'name', [], 'name', 'id');
			$box['key']['activityBandsExist'] = !empty($box['lovs']['activity_band']);
		}
		
		
		//Keep track of which things have parents
		$unsets = [];
		$optGroups = [
			'checkboxs' => [],
			'consents' => [],
			'groups' => [],
			'lists' => []
		];
		
		//Get a list of tabs and fields, and loop through it
		$box['lovs']['fields'] = ze\datasetAdm::listCustomFields('users', $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
		
		foreach ($box['lovs']['fields'] as $fieldId => &$field) {
			
			if (empty($field['parent'])) {
				$field['hide_when_children_are_not_visible'] = true;
			}
			
			//Look for fields, exclude the tabs
			if (!empty($field['parent']) && !empty($field['type'])) {
				
				
				//If a field is flagged as "fundamental", add it to the main list and remove it from the second list
				//Note that currently "fundamental" is only implemented for lists.
				if ($field['fundamental'] && ze::in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
					$box['lovs']['types'][$fieldId] = [
						'ord' => $field['ord'],
						'parent' => 'user_fields',
						'label' => $field['label']
					];
					$unsets[] = $fieldId;
					continue;
				}
				
				//Otherwise add the field to either the Groups list, Checkboxes list or Lists list.
				switch ($field['type']) {
					case 'checkbox':
						$field['visible_if'] = "zenarioAB.valueOnThisRow('type__', field.id) == 'checkbox'";
						$box['key']['checkboxesExist'] = true;
						$optGroups['checkboxes'][$field['parent']] = true;
						break;
						
					case 'consent':
						$field['visible_if'] = "zenarioAB.valueOnThisRow('type__', field.id) == 'consent'";
						$box['key']['consentsExist'] = true;
						$optGroups['consents'][$field['parent']] = true;
						break;
						
					case 'group':
						$field['visible_if'] = "zenarioAB.valueOnThisRow('type__', field.id) == 'group'";
						$box['lovs']['types']['group']['label'] = 'Group';
						$box['key']['groupsExist'] = true;
						$box['key']['isMemberExist'] = true;
						$box['key']['isNotMemberExist'] = true;
						$optGroups['groups'][$field['parent']] = true;
						break;
			
					case 'radios':
					case 'centralised_radios':
					case 'select':
					case 'centralised_select':
						$field['visible_if'] = "zenarioAB.valueOnThisRow('type__', field.id) == 'list'";
						$box['key']['listsExist'] = true;
						$optGroups['lists'][$field['parent']] = true;
						break;
			
					case 'date':
						//Maybe implement filters for date fields at some point..?
					default:
						//Remove any fields that we don't handle
						$unsets[] = $fieldId;
						continue 2;
				}
			}
		}
		unset($field);
		
		foreach ($unsets as $fieldId) {
			unset($box['lovs']['fields'][$fieldId]);
		}
		
		//Loop through the list of fields again.
		//Catch the case where there was only one tab visible for a type.
		//In this case, don't bother showing the tabs in the list.
		foreach ($box['lovs']['fields'] as $fieldId => &$field) {
			if (!empty($field['parent']) && !empty($field['type'])) {
				switch ($field['type']) {
					case 'checkbox':
						if (count($optGroups['checkboxes']) < 2) {
							unset($field['parent']);
						}
						break;
					
					case 'consent':
						if (count($optGroups['consents']) < 2) {
							unset($field['parent']);
						}
						break;
			
					case 'group':
						if (count($optGroups['groups']) < 2) {
							unset($field['parent']);
						}
						break;
			
					case 'radios':
					case 'centralised_radios':
					case 'select':
					case 'centralised_select':
						if (count($optGroups['lists']) < 2) {
							unset($field['parent']);
						}
						break;
				}
			}
		}
		unset($field);
		
		
		return;
	}

	public function setupRuleRows(&$box, &$fields, &$values, $changes, $filling, $addRows = 0) {
		
		return ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = false,
			$box['tabs']['smart_group']['custom_template_fields'],
			$addRows,
			$minNumRows = 1,
			$tabName = 'smart_group',
			$deleteButtonCodeName = 'remove__znz'
		);
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
		
		$multiRows = $this->setupRuleRows(
			$box, $fields, $values, $changes, $filling = false,
			$addRows = !empty($box['tabs']['smart_group']['fields']['add__'. $box['key']['num_rules']]['pressed'])
		);
		$box['key']['num_rules'] = $multiRows['numRows'];
		
		
		
		
		//Set the LOV options for every picked field
		for ($n = 1; $n <= $box['key']['num_rules']; ++$n) {
			
			$fieldId = false;
			if ($type = $values['smart_group/type__'. $n]) {
				if (ze::in($type, 'group', 'checkbox', 'consent', 'list')) {
					$fieldId = (int) $values['smart_group/field__'. $n];
				} elseif (is_numeric($type)) {
					$fieldId = $type;
					$type = 'list';
				}
			}
			
			$box['tabs']['smart_group']['fields']['value__'. $n]['hidden'] = true;
			if ($fieldId && ($field = ze\dataset::fieldDetails($fieldId))) {
				
				//Set list of values
				if (ze::in($field['type'], 'radios', 'select', 'centralised_radios', 'centralised_select')) {
					$lov = ze\dataset::fieldLOV($field, $flat = false);
					$box['tabs']['smart_group']['fields']['value__'. $n]['values'] = $lov;
					$box['tabs']['smart_group']['fields']['value__'. $n]['hidden'] = empty($lov);
						
				}
			}
		}
		
		for ($n = 1; $n <= $box['key']['num_rules']; $n++) {
			if(ze\module::inc('zenario_user_activity_bands')){
				$activityBands = zenario_user_activity_bands::getActivityBands();
				if($activityBands && is_array($activityBands)){
					$i=1;
					foreach($activityBands as $activityBand){
						if(isset($fields['smart_group/activity_bands__'. $n])){
						$fields['smart_group/activity_bands__'. $n]['values'][$activityBand['id']] = [
							'ord' => ++$i,
							'label' => $activityBand['name'],
							'value' => $activityBand['id']
						];
						}
					}
				}
			}
		}
		
		$rules = $this->getRulesFromFields($box, $fields, $values);
		
		$fields['smart_group/no_rules_set_news']['hidden'] =
		$fields['smart_group/no_rules_set_perms']['hidden'] = !empty($rules);
		$fields['smart_group/members']['hidden'] = empty($rules);
		
		if (!empty($rules)) {
			
				$values['smart_group/members'] = ze\smartGroup::countMembers($rules);
		}
	}

	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
		
		if (ze\row::exists('smart_groups', ['name' => $values['smart_group/name'], 'id' => ['!' => $box['key']['id']]])) {
			$fields['smart_group/name']['error'] = ze\admin::phrase('A smart group with the name "[[smart_group/name]]" already exists. Please choose a different name.', $values);
		}
	}
	
	public function getRulesFromFields(&$box, &$fields, &$values) {
		$rules = [];
		for ($n = 1; $n <= $box['key']['num_rules']; ++$n) {
			
			if ($type = $values['smart_group/type__'. $n]) {
			
				$rule = [];
				$rule['field_id'] = 0;
				$rule['field2_id'] = 0;
				$rule['field3_id'] = 0;
				$rule['field4_id'] = 0;
				$rule['field5_id'] = 0;
				$rule['role_id'] = 0;
				$rule['value'] = null;
				$rule['not'] = 0;
				$rule['must_match'] = $values['smart_group/must_match'];
				$rule['intended_usage'] = $box['key']['intended_usage'];
								
				switch ($type) {
					//If a role is picked, set the select list
					case 'role':
						if ($rule['role_id'] = $values['smart_group/role__'. $n]) {
							$rule['type_of_check'] = $type;
							$rules[] = $rule;
						}
						break;
					case 'in_a_group':
							$rule['value'] = 'active';
							$rule['type_of_check'] = $type;
							$rules[] = $rule;
						
						break;
					case 'not_in_a_group':
							$rule['value'] = 'active';
							$rule['type_of_check'] = $type;
							$rules[] = $rule;
						
						break;			
						
					case 'activity_band':
						if ($rule['activity_band_id'] = $values['smart_group/activity_bands__'. $n]) {
							$rule['type_of_check'] = $type;
							$rule['not'] = ze\ring::engToBoolean($values['smart_group/is_isnt_in_activity_band__'. $n] == 'isnt');
							$rules[] = $rule;
						}
						break;
					
					default:
						//For each row, check that a field is selected (remembering that fields are in the
						//"type" select list if they are fundamental fields).
						$fieldId = false;
						if (ze::in($type, 'group', 'checkbox', 'consent', 'list')) {
							$fieldId = (int) $values['smart_group/field__'. $n];
						} elseif (is_numeric($type)) {
							$fieldId = $type;
							$type = 'list';
						}
			
						//Check if a field is selected, and if it is a supported type
						if ($fieldId
						 && ($field = ze\dataset::fieldDetails($fieldId))
						 && (ze::in($field['type'], 'group', 'checkbox', 'consent', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
							
							//Catch the case where someone has just changed the select lists and the types of field don't match up
							if (($type == 'group' xor $field['type'] == 'group')
							 || ($type == 'checkbox' xor $field['type'] == 'checkbox')
							 || ($type == 'consent' xor $field['type'] == 'consent')
							 || ($type == 'list' xor ze::in($field['type'], 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
								break;
							}
							
							$rule['type_of_check'] = 'user_field';
							$rule['field_id'] = $fieldId;
							
							if ($field['type'] == 'group') {
								$values['smart_group/type__'. $n] = 'group';
					
								if ($values['smart_group/is_isnt_in__'. $n] == 'is_one_of') {
									$rule['field2_id'] = $values['smart_group/field2__'. $n];
									$rule['field3_id'] = $values['smart_group/field3__'. $n];
									$rule['field4_id'] = $values['smart_group/field4__'. $n];
									$rule['field5_id'] = $values['smart_group/field5__'. $n];
								} else {
									$rule['not'] = ze\ring::engToBoolean($values['smart_group/is_isnt_in__'. $n] == 'isnt');
								}
				
							} elseif ($field['type'] == 'checkbox' || $field['type'] == 'consent') {
								$rule['not'] = ze\ring::engToBoolean($values['smart_group/is_isnt_set__'. $n] == 'isnt');
				
							} else {
								if (!$rule['value'] = $values['smart_group/value__'. $n]) {
									break;
								}
								$rule['not'] = ze\ring::engToBoolean($values['smart_group/is_isnt__'. $n] == 'isnt');
							}
				
							$rules[] = $rule;
						}
						break;
				}
			}
		}
		
		return $rules;
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
		
		//Save the basic details of the smart group
		$details = [];
		$details['name'] = $values['smart_group/name'];
		$details['must_match'] = $values['smart_group/must_match'];
		$details['last_modified_on'] = ze\date::now();
		$details['last_modified_by'] = ze\admin::id();
		
		if (!$box['key']['id']) {
			$details['created_on'] = ze\date::now();
			$details['created_by'] = ze\admin::id();
			$details['intended_usage'] = $box['key']['intended_usage'];
		}
		
		$box['key']['id'] = ze\row::set('smart_groups', $details, $box['key']['id']);
		
		
		//Loop through saving all of the rules
		$ord = 0;
		$ords = [];
		foreach ($this->getRulesFromFields($box, $fields, $values) as $rule) {
			$ords[] = ++$ord;
			$key = [];
			$key['ord'] = $ord;
			$key['smart_group_id'] = $box['key']['id'];
			unset($rule['must_match']);
			unset($rule['intended_usage']);
			
			ze\row::set('smart_group_rules', $rule, $key);
		}
		
		//Delete any old existing rules that weren't just overwritten when saving about
		$sql = "
			DELETE FROM ". DB_PREFIX. "smart_group_rules
			WHERE smart_group_id = ". (int) $box['key']['id'];
		
		if (!empty($ords)) {
			$sql .= "
			  AND ord NOT IN (". ze\escape::in($ords, 'numeric'). ")";
		}
		
		ze\sql::update($sql);
	}
}