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



//This file contains php scripts code for converting user data after some database structure changes



//Populate the identifier column for all users
if (needRevision(30130)) {
	
	foreach (getRowsArray('users', array('first_name', 'last_name', 'email')) as $userId => $userDetails) {
		saveUser($userDetails, $userId);
	}
	
	revision(30130);
}

// Set the default values for submit, next and back buttons text
if (needRevision(30752)) {
	foreach (getRowsArray('user_forms', 'id') as $id) {
		updateRow('user_forms', 
			array(
				'submit_button_text' => 'Submit',
				'default_next_button_text' => 'Next',
				'default_previous_button_text' => 'Back'), 
			$id);
	}
	revision(30752);
}


//Convert smart groups from the old format to the new formats
//(Note that this code is a reworking of the old zenario_users::advancedSearchTableJoins() function
// found in 7.0.5 and earlier)
if (needRevision(31740)) {
	
	$result = getRows('smart_groups', array('id', 'values'), array());
	while ($sg = sqlFetchAssoc($result)) {
		$ord = 0;
		
		if ($sg['values'] && ($values = json_decode($sg['values'], true))) {
			
			foreach (explode(',', arrayKey($values, 'first_tab','indexes')) as $index) {
				if (arrayKey($values, 'first_tab','rule_type_' . $index) == 'characteristic') {
					if ($fieldId = arrayKey($values, 'first_tab','rule_characteristic_picker_' . $index)) {
						$fieldValue = arrayKey($values, 'first_tab','rule_characteristic_values_picker_' . $index);
						
						insertRow('smart_group_rules', array(
							'smart_group_id' => $sg['id'],
							'ord' => ++$ord,
							'field_id' => $fieldId,
							'value' => $fieldValue
						));
					}
				}
				
				if (arrayKey($values, 'first_tab' , 'rule_type_' . $index) == 'group') {
					if ($groups = arrayKey($values, 'first_tab', 'rule_group_picker_' . $index)) {
						$groups = explode(',', $groups);
						array_filter($groups);
						$groupCount = count($groups);
						$groupLogic = arrayKey($values, 'first_tab' , 'rule_logic_' . $index);
						
						if ($groupLogic == 'any' && $groupCount > 1) {
						
							insertRow('smart_group_rules', array(
								'smart_group_id' => $sg['id'],
								'ord' => ++$ord,
								'field_id' => $groups[0],
								'field2_id' => arrayKey($groups, 1),
								'field3_id' => arrayKey($groups, 2)
							));
						} else {
							foreach ($groups as $groupId) {
								insertRow('smart_group_rules', array(
									'smart_group_id' => $sg['id'],
									'ord' => ++$ord,
									'field_id' => $groupId
								));
							}
						}
					}
				}
			}
			
			
			if (arrayKey($values, 'exclude','enable') ) {
				if (arrayKey($values, 'exclude','rule_type') == 'characteristic') {
					if ($fieldId = (arrayKey($values, 'exclude','rule_characteristic_picker'))) {
						$fieldValue = arrayKey($values, 'exclude','rule_characteristic_values_picker');
						
						insertRow('smart_group_rules', array(
							'smart_group_id' => $sg['id'],
							'ord' => ++$ord,
							'field_id' => $fieldId,
							'value' => $fieldValue,
							'not' => 1
						));
					}
				}
				
				if (arrayKey($values, 'exclude' , 'rule_type') == 'group') {
					if ($groups = arrayKey($values, 'exclude', 'rule_group_picker')) {
						$groups = explode(',', $groups);
						array_filter($groups);
						
						foreach ($groups as $groupId) {
							insertRow('smart_group_rules', array(
								'smart_group_id' => $sg['id'],
								'ord' => ++$ord,
								'field_id' => $groupId,
								'not' => 1
							));
						}
					}
				}
			}
		}
	}
	
	revision(31740);
}

revision( 31750
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]smart_groups`
	DROP COLUMN `values`
_sql
);


// Mark all sortable columns as indexed
if (needRevision(31762)) {
	updateRow('custom_dataset_fields', array('create_index' => 1), array('sortable' => 1));
	revision(31762);
}
