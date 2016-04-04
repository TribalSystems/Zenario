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

				
class zenario_users__admin_boxes__site_settings extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		
		
		$groups = listCustomFields('users', $flat = false, 'boolean_and_groups_only', $customOnly = true, $useOptGroups = true);
		$box['tabs']['inactive_user_email']['fields']['user_dataset_field_to_receive_emails']['values'] = $groups;
		
		// Disable setting for deleting unconfirmed user accounts if scheduled task manager is not enabled
		$scheduledTaskManagerRunning = checkRowExists('modules', array('class_name' => 'zenario_scheduled_task_manager', 'status' => 'module_running'));
		if (!$scheduledTaskManagerRunning 
			|| !setting('jobs_enabled') 
			|| !inc('zenario_scheduled_task_manager')
			|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobRemoveInactivePendingUsers')
		) {
			$values['unconfirmed_users/remove_inactive_users'] = false;
			$fields['unconfirmed_users/remove_inactive_users']['disabled'] = true;
			$fields['unconfirmed_users/remove_inactive_users']['side_note'] = 
				'The scheduled task manager module must be running and the task "jobRemoveInactivePendingUsers" must be enabled to remove inactive users.';
		}
		
		// Show list of users dataset tabs
		$dataset = getDatasetDetails('users');
		$result = getRows('custom_dataset_tabs', array('label', 'ord', 'name'), array('dataset_id' => $dataset['id']), 'label');
		while ($row = sqlFetchAssoc($result)) {
			$fields['groups/default_groups_dataset_tab']['values'][$row['name']] = array(
				'label' => $row['label'],
				'ord' => $row['ord']
			);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($box['tabs']['passwords']['notices']['turn_plaintext_on_warning'])
		 && isset($fields['passwords/plaintext_extranet_user_passwords']['current_value'])) {
			
			$box['tabs']['passwords']['notices']['turn_plaintext_on_warning']['show'] =
				$fields['passwords/plaintext_extranet_user_passwords']['current_value']
			 && !$fields['passwords/plaintext_extranet_user_passwords']['value'];
				
			$box['tabs']['passwords']['notices']['turn_plaintext_off_warning']['show'] =
				$fields['passwords/plaintext_extranet_user_passwords']['value']
			 && !$fields['passwords/plaintext_extranet_user_passwords']['current_value'];
		}
		
		$fields['unconfirmed_users/max_days_user_inactive']['hidden'] = !$values['unconfirmed_users/remove_inactive_users'];
		
		if (isset($fields['names/user_use_screen_name'])) {
			$screenNamesOn = !empty($values['names/user_use_screen_name']);
			$screenNamesWereOn = !empty($fields['names/user_use_screen_name']['value']);
			
			$box['tabs']['names']['notices']['turning_off_screen_names']['show'] =
				$screenNamesWereOn && !$screenNamesOn;
			
			$box['tabs']['names']['notices']['turning_on_screen_names']['show'] =
				$screenNamesOn && !$screenNamesWereOn
				&& checkRowExists('users', array('screen_name' => array('!' => '')));
		}
		
		
		if ($values['inactive_user_email/time_user_inactive_1']){
			$fields['inactive_user_email/inactive_user_email_template_1']['hidden'] = false;
		}else{
			$fields['inactive_user_email/inactive_user_email_template_1']['hidden'] = true;
		}
	
		if ($values['inactive_user_email/time_user_inactive_2']){
			$fields['inactive_user_email/inactive_user_email_template_2']['hidden'] = false;
		}else{
			$fields['inactive_user_email/inactive_user_email_template_2']['hidden'] = true;
		}
		
		
		
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['inactive_user_email/time_user_inactive_1'] && !$values['inactive_user_email/inactive_user_email_template_1']){
			$fields['inactive_user_email/inactive_user_email_template_1']['error'] = adminPhrase('Please select an email template for the first period.');
		}
		
		if ($values['inactive_user_email/time_user_inactive_2'] && !$values['inactive_user_email/inactive_user_email_template_2']){
			$fields['inactive_user_email/inactive_user_email_template_1']['error'] = adminPhrase('Please select an email template for the second period.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Changes to extranet user passwords..?
		if (checkPriv('_PRIV_EDIT_SITE_SETTING') && engToBooleanArray($box, 'tabs', 'passwords', 'edit_mode', 'on')) {
			
			//If someone is turning off plaintext passwords, encrypt any that are still stored as plaintext
			if (isset($values['passwords/plaintext_extranet_user_passwords'])
			 && !$values['passwords/plaintext_extranet_user_passwords']) {
				foreach (getRowsArray('users', array('id', 'password'), array('password_salt' => null)) as $user) {
					setUsersPassword($user['id'], $user['password'], -1, false);
				}
			}
			
			if (isset($fields['names/user_use_screen_name'])) {
				//If the "use screen names" option is changed, regenerate the user identifiers
				$screenNamesOn = !empty($values['names/user_use_screen_name']);
				$screenNamesWereOn = !empty($fields['names/user_use_screen_name']['value']);
				if ($screenNamesOn != $screenNamesWereOn) {
				
					if ($screenNamesWereOn) {
						//If we're turning screen names off, all users will be affected!
						$affectedUsers = array();
					} else {
						//If we're turning screen names on, only users with a screen name will be affected
						$affectedUsers = array('screen_name' => array('!' => ''));
					}
				
					//Clear the previous identifiers
					updateRow('users', array('identifier' => null), $affectedUsers);
				
					//Loop through all users and set identifiers
					$result = getRows('users', array('id', 'screen_name', 'first_name', 'last_name', 'email'), $affectedUsers);
					while ($user = sqlFetchAssoc($result)) {
						updateRow('users', array('identifier' => generateUserIdentifier($user['id'], $user)), $user['id']);
					}
				}
			}
		}
	}
}
