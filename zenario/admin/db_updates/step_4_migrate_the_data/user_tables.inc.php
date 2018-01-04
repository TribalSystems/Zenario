<?php
/*
 * Copyright (c) 2018, Tribal Limited
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
			
			foreach (explode(',', ($values['first_tab']['indexes'] ?? false)) as $index) {
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
								'field2_id' => ($groups[1] ?? false),
								'field3_id' => ($groups[2] ?? false)
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
			
			
			if ($values['exclude']['enable'] ?? false) {
				if (($values['exclude']['rule_type'] ?? false) == 'characteristic') {
					if ($fieldId = $values['exclude']['rule_characteristic_picker'] ?? false) {
						$fieldValue = $values['exclude']['rule_characteristic_values_picker'] ?? false;
						
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
					if ($groups = $values['exclude']['rule_group_picker'] ?? false) {
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

if (needRevision(35944)) {
		$users = getRowsArray('users', array('id','identifier'));
		if ($users){
			foreach ($users as $user){
				if(!$user['identifier']){
					$identifier = generateUserIdentifier($user['id']);
					updateRow('users', array('identifier' => $identifier), array('id'=>$user['id']));
				}
			}
		}
	revision(35944);
}

// Migrate dataset fields organizer visibility columns to enum
if (needRevision(36076)) {
	$result = getRows('custom_dataset_fields', array('id', 'show_in_organizer', 'show_by_default', 'always_show'), array());
	while ($field = sqlFetchAssoc($result)) {
		$orgVisibility = false;
		if ($field['show_by_default']) {
			$orgVisibility = 'show_by_default';
		} elseif ($field['always_show']) {
			$orgVisibility = 'always_show';
		} elseif ($field['show_in_organizer']) {
			$orgVisibility = 'hide';
		}
		if ($orgVisibility) {
			updateRow('custom_dataset_fields', array('organizer_visibility' => $orgVisibility), $field['id']);
		}
	}
	revision(36076);
}

revision( 36077
, <<<_sql
	ALTER TABLE `[[DB_NAME_PREFIX]]custom_dataset_fields`
	DROP COLUMN `show_in_organizer`,
	DROP COLUMN `show_by_default`,
	DROP COLUMN `always_show`
_sql
);
resetDatabaseStructureCache();

// Migrate checkbox column data from plugin setting to user fields table
if (needRevision(36505)) {
	$sql = '
		UPDATE ' . DB_NAME_PREFIX . 'user_form_fields uff
		LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
			ON uff.user_field_id = cdf.id
		SET uff.value_field_columns = 1
		WHERE (uff.field_type = "checkboxes"
			OR cdf.type = "checkboxes"
		)';
	sqlQuery($sql);
	
	$sql = '
		SELECT
		(
			SELECT ps.value
			FROM ' . DB_NAME_PREFIX . 'plugin_settings ps
			WHERE ps.instance_id = pi.id
			AND ps.name = "checkbox_columns"
		) AS checkbox_columns,
		(
			SELECT ps.value
			FROM ' . DB_NAME_PREFIX . 'plugin_settings ps
			WHERE ps.instance_id = pi.id
			AND ps.name = "user_form"
		) AS user_form
		FROM ' . DB_NAME_PREFIX . 'plugin_instances pi
		INNER JOIN ' . DB_NAME_PREFIX . 'modules m
			ON pi.module_id = m.id
			AND m.class_name = "zenario_user_forms"';
	$result = sqlSelect($sql);
	while ($row = sqlFetchAssoc($result)) {
		$sql = '
			SELECT uff.id, uff.field_type, cdf.type
			FROM ' . DB_NAME_PREFIX . 'user_form_fields uff
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ' . (int)$row['user_form'] . '
			AND (uff.field_type = "checkboxes"
				OR cdf.type = "checkboxes"
			)';
		$result2 = sqlSelect($sql);
		while ($row2 = sqlFetchAssoc($result2)) {
			if(!empty($row['checkbox_columns'])) {
				updateRow('user_form_fields', array('value_field_columns' => $row['checkbox_columns']), array('id' => $row2['id']));
			}
		}
	}
	revision(36505);
}








//Migrate any slides that used to use logged_in_with_field/logged_in_without_field/without_field options
//by creating a smart group
if (needRevision(37220)) {
	
	//Look for any old settings in 
	$ord = 0;
	$result = getRows('tmp_migrate_slide_visibility', true, array());
	while ($sg = sqlFetchAssoc($result)) {
		
		//Save the basic details of the smart group
		$details = array();
		$details['name'] = 'Migrated slide visibility #'. ++$ord;
		$details['last_modified_on'] = now();
		$details['last_modified_by'] = adminId();
		$details['created_on'] = now();
		$details['created_by'] = adminId();
		
		if ($smartGroupId = insertRow('smart_groups', $details, $ignore = true)) {
			insertRow('smart_group_rules', array(
				'smart_group_id' => $smartGroupId,
				'ord' => 1,
				'field_id' => $sg['field_id'],
				'value' => $sg['field_value']
			));
			
			updateRow('nested_plugins',
				array('smart_group_id' => $smartGroupId),
				array('is_slide' => 1, 'id' => explode(',', $sg['slide_ids']))
			);
		}
	}
	
	revision(37220);
}

revision( 37230
, <<<_sql
	DROP TABLE `[[DB_NAME_PREFIX]]tmp_migrate_slide_visibility`
_sql
);


//In version 7.4, we want to move the user_forms and user_form_fields tables, which were previously core tables,
//into the User Forms module.
//For sites that were running 7.3 and earlier, we'll rename the tables to preserve the data in them.
//For fresh installs, just delete the tables and rely on the User Forms modules to create them again.
if (needRevision(37235)) {
	
	//Check if the user forms module is running
	if ($prefix = getModulePrefix('zenario_user_forms', $mustBeRunning = true)) {
		//If not, drop tables if exist
		$sql = "
			DROP TABLE IF EXISTS `". DB_NAME_PREFIX. $prefix. "user_forms`";
		sqlQuery($sql);
		
		$sql = "
			DROP TABLE IF EXISTS `". DB_NAME_PREFIX. $prefix. "user_form_fields`";
		sqlQuery($sql);
		
		//If so, rename tables
		$sql = "
			RENAME TABLE `". DB_NAME_PREFIX. "user_forms` TO `". DB_NAME_PREFIX. $prefix. "user_forms`";
		sqlQuery($sql);
		
		$sql = "
			RENAME TABLE `". DB_NAME_PREFIX. "user_form_fields` TO `". DB_NAME_PREFIX. $prefix. "user_form_fields`";
		sqlQuery($sql);
	
	} else {
		//If not, drop tables if exist
		$sql = "
			DROP TABLE IF EXISTS `". DB_NAME_PREFIX. "user_forms`";
		sqlQuery($sql);
		
		$sql = "
			DROP TABLE IF EXISTS `". DB_NAME_PREFIX. "user_form_fields`";
		sqlQuery($sql);
	}
	
	
	revision(37235);
}


//If anyone has been using the extranet registration module in version 7.6 or 7.7 before the patch,
//the send_delayed_registration_email column will have been created on the wrong table.
//Move the data and delete the old column.
if (needRevision(40780)) {
	
	if (sqlNumRows('SHOW COLUMNS FROM '. DB_NAME_PREFIX. 'users_custom_data LIKE "send_delayed_registration_email"')) {
		sqlUpdate('
			UPDATE '. DB_NAME_PREFIX . 'users AS u
			INNER JOIN '. DB_NAME_PREFIX. 'users_custom_data AS ucd
			   ON ucd.user_id = u.id
			  AND ucd.send_delayed_registration_email = 1
			SET u.send_delayed_registration_email = 1
		');
	}
	
	if ($details = getDatasetFieldDetails('users', 'send_delayed_registration_email')) {
		deleteDatasetField($details['id']);
	}
	
	revision(40780);
}

//Delete old template from DB
if (needRevision(41253)) {
	$code = 'zenario_users__to_user_account_suspended';
	$sql = "
		DELETE FROM " . DB_NAME_PREFIX . "email_templates
		WHERE code = '". sqlEscape($code) . "'";
	sqlUpdate($sql);
	removeItemFromPluginSettings('email_template', 0, $code);
	
	revision(41253);
}