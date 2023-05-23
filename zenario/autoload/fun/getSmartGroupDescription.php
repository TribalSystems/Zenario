<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


if (!$smartGroup = \ze\row::get('smart_groups', ['intended_usage', 'must_match'], $smartGroupId)) {
	return '';
}

$rules = \ze\row::getAssocs(
	'smart_group_rules',
	['type_of_check', 'field_id', 'field2_id', 'field3_id', 'field4_id', 'field5_id', 'role_id', 'timer_template_id', 'activity_band_id', 'not', 'value'],
	['smart_group_id' => $smartGroupId],
	'ord'
);
$list = $smartGroup['intended_usage'] == 'smart_newsletter_group';

//If there are no rules, newsletter groups should return everyone, but permissions groups should return nobody.
if (empty($rules)) {
	if ($list) {
		return \ze\admin::phrase('All users and contacts');
	} else {
		return \ze\admin::phrase('No one');
	}
}
$or = count($rules) > 1 && $smartGroup['must_match'] == 'any';


$descs = [];

foreach ($rules as $rule) {
	
	switch ($rule['type_of_check']) {
		
		//Handle rules on user-fields
		case 'user_field':
			//Check if a field is set, load the details, and check if it's a supported field. Only add it if it is.
			if ($rule['field_id']
			 && ($field = \ze\dataset::fieldBasicDetails($rule['field_id']))
			 && (\ze::in($field['type'], 'group', 'checkbox', 'consent', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
		
				if ($field['is_system_field'] && $field['label'] == '') {
					$field['label'] = $field['default_label'];
				}
		
				$desc = '';
		
				if ($field['type'] == 'group') {
					if ($rule['not']) {
						$desc .= 'Not a member of '. $field['label'];
					} else {
						$desc .= 'Member of '. $field['label'];
		
						//If you filter by group, an "OR" logic containing multiple groups is allowed.
						//Check if multiple groups have been picked...
						$groups = [];
						if ($field['type'] == 'group') {
							if ($rule['field2_id']) $groups[] = $rule['field2_id'];
							if ($rule['field3_id']) $groups[] = $rule['field3_id'];
							if ($rule['field4_id']) $groups[] = $rule['field4_id'];
							if ($rule['field5_id']) $groups[] = $rule['field5_id'];
						}
				
						//...if so, list these extra groups too
						if (!empty($groups)) {
							$lastI = count($groups) - 1;
							foreach ($groups as $i => $groupId) {
								if ($i == $lastI) {
									$desc .= ' or ';
								} else {
									$desc .= ', ';
								}
								$desc .= \ze\row::get('custom_dataset_fields', 'label', $groupId);
							}
						}
					}
			
				} else {
					$desc .= $field['label'];
			
					if ($rule['not']) {
						$desc .= ' is not ';
					} else {
						$desc .= ' is ';
					}
			
					switch ($field['type']) {
						case 'checkbox':
						case 'consent':
							$desc .= 'set';
							break;
				
						//List of values work via a numeric value id
						case 'radios':
						case 'select':
							$desc .= '"'. \ze\row::get('custom_dataset_field_values', 'label', $rule['value']). '"';
							break;
				
						//Centralised lists work via a text value
						default:
							$desc .= '"'. $rule['value']. '"';
					}
				}
		
				$descs[] = $desc;
			}
			break;
			
		case 'role':
			if ($rule['role_id']
			 && ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager', $mustBeRunning = true))
			 && ($role = \ze\row::get($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', ['name'], $rule['role_id']))) {
				
				if ($list) {
					$descs[] = \ze\admin::phrase('Has access to the [[name]] role at any location', $role);
				} else {
					$descs[] = \ze\admin::phrase('Has access to the [[name]] role at the location specified in the URL', $role);
				}
			}
			break;
		case 'in_a_group':
			
			$descs[] = \ze\admin::phrase('Is a member of any group');
			break;
		case 'not_in_a_group':
			
			$descs[] = \ze\admin::phrase('Is not a member of any group');
			break;
		case 'has_a_current_timer':
			if ($ZENARIO_USER_TIMERS_PREFIX = ze\module::prefix('zenario_user_timers', $mustBeRunning = true)) {
				if ($rule['timer_template_id']) {
					$timer = ze\row::get($ZENARIO_USER_TIMERS_PREFIX . 'user_timer_templates', ['name'], ['id' => $rule['timer_template_id']]);
					$descs[] = \ze\admin::phrase('Has a current timer [[name]]', $timer);
				} else {
					$descs[] = \ze\admin::phrase('Has any current timer');
				}
			}
			break;
		case 'has_no_current_timer':
			if ($ZENARIO_USER_TIMERS_PREFIX = ze\module::prefix('zenario_user_timers', $mustBeRunning = true)) {
				if ($rule['timer_template_id']) {
					$timer = ze\row::get($ZENARIO_USER_TIMERS_PREFIX . 'user_timer_templates', ['name'], ['id' => $rule['timer_template_id']]);
					$descs[] = \ze\admin::phrase('Has an expired timer [[name]] and no current timer', $timer);
				} else {
					$descs[] = \ze\admin::phrase('Has any expired timer and no active timers');
				}
			}
			break;
		case 'activity_band':
			if($rule['activity_band_id']
			&& $ZENARIO_USER_ACTIVITY_BANDS_PREFIX = \ze\module::prefix('zenario_user_activity_bands', $mustBeRunning = true)){
				
				$activityBand = \ze\row::get($ZENARIO_USER_ACTIVITY_BANDS_PREFIX. 'activity_bands', ['name'], $rule['activity_band_id']);
				
				if($rule['not']){
					$descs[] = \ze\admin::phrase('Not in activity band "[[name]]"', $activityBand);
				}else{
					$descs[] = \ze\admin::phrase('In activity band "[[name]]"', $activityBand);
				}
			}
			break;
		
	}
}

if ($or) {
	return implode(' or ', $descs);
} else {
	return implode('; ', $descs);
}