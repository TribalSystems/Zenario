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

				
class zenario_users__admin_boxes__site_settings extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($settingGroup) {
			case 'users':
				$groups = ze\datasetAdm::listCustomFields('users', $flat = false, 'boolean_and_groups_only', $customOnly = true, $useOptGroups = true);
				$box['tabs']['inactive_user_email']['fields']['user_dataset_field_to_receive_emails']['values'] = $groups;
		
				// Disable setting for deleting unconfirmed user accounts if scheduled task manager is not enabled
				$scheduledTaskManagerRunning = ze\row::exists('modules', array('class_name' => 'zenario_scheduled_task_manager', 'status' => 'module_running'));
				if (!$scheduledTaskManagerRunning 
					|| !ze::setting('jobs_enabled') 
					|| !ze\module::inc('zenario_scheduled_task_manager')
					|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobRemoveInactivePendingUsers')
				) {
					$values['unconfirmed_users/remove_inactive_users'] = false;
					$fields['unconfirmed_users/remove_inactive_users']['disabled'] = true;
					$fields['unconfirmed_users/remove_inactive_users']['side_note'] = 
						'The scheduled task manager module must be running and the task "jobRemoveInactivePendingUsers" must be enabled to remove inactive users.';
				}
		
				// Show list of users dataset tabs
				$dataset = ze\dataset::details('users');
				$result = ze\row::query('custom_dataset_tabs', array('label', 'default_label', 'ord', 'name'), array('dataset_id' => $dataset['id']), 'label');
				while ($row = ze\sql::fetchAssoc($result)) {
					$label = $row['label'] ? $row['label'] : $row['default_label'];
					if ($label !== false && $label !== '') {
						$fields['groups/default_groups_dataset_tab']['values'][$row['name']] = array(
							'label' => $label,
							'ord' => $row['ord']
						);
						
						$fields['flags/default_flags_dataset_tab']['values'][$row['name']] = array(
							'label' => $label,
							'ord' => $row['ord']
						);
					}
				}
				break;
			
			
			case 'perms':
				//Check to see what's running
				$ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager', true);
				$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX = ze\module::prefix('zenario_company_locations_manager', true);
				
				if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX) {
					$box['lovs']['roles'] = ze\row::getArray($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name', 'id');
				}
				
				//If the dev tools are on, add an example as a site note to each type of permissions check
				if (ze\admin::setting('show_dev_tools')) {
					foreach ($box['tabs'] as &$tab) {
						if (empty($tab['hidden']) && !empty($tab['fields']) && is_array($tab['fields'])) {
							foreach ($tab['fields'] as $fieldId => &$field) {
								if (!empty($field) && is_array($field)) {
									if ($fieldId) {
										
										$atLocation = stripos($fieldId, 'atLocation') !== false;
										$atCompany = stripos($fieldId, 'atCompany') !== false;
										
										//Don't show company/location related permissions if the modules are not running
										if ((!$ZENARIO_ORGANIZATION_MANAGER_PREFIX && ($atCompany || $atLocation))
										 || (!$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX && $atCompany)) {
											$field['hidden'] = true;
										
										} else {
											$directlyAssignedToUser =
											$hasRoleAtCompany =
											$hasRoleAtLocation =
											$hasRoleAtLocationAtCompany =
											$onlyIfHasRolesAtAllAssignedLocations = false;
										
											//Check if this field is one of the permissions
											if (ze\user::checkNamedPermExists($fieldId, $directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany, $onlyIfHasRolesAtAllAssignedLocations)) {
											
												//Break the permission code-name up into chunks
												$perm = explode('.', $fieldId);
											
												//Show the ze\user::canCreate() shortcut function if this is a create permission
												if ($thingToCreate = ze\ring::chopPrefix('create-', $perm[1])) {
													$field['side_note'] = '<code>checkUserCanCreate(\''. htmlspecialchars($thingToCreate). '\'';
												} else {
													$field['side_note'] = '<code>checkUserCan(\''. htmlspecialchars($perm[1]). '\'';
												}
											
												//Companies and locations always use the "unassigned" option, so don't bother showing it
												if ($fieldId != 'perm.create-company.unassigned'
												 && $fieldId != 'perm.create-location.unassigned') {
												
													//Show the target
													$field['side_note'] .= ', \''. htmlspecialchars($perm[2]). '\'';
												
													//Except for unassigned/oneself, show a number as an example for the third input
													if ($perm[2] != 'unassigned' && $perm[2] != 'oneself') {
														$field['side_note'] .= ', 123';
													}
												}
												$field['side_note'] .= ');</code>';
											}
										}
									}
								}
							}
						}
					}
				}
				
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($settingGroup) {
			case 'users':
		
				$fields['unconfirmed_users/max_days_user_inactive']['hidden'] = !$values['unconfirmed_users/remove_inactive_users'];
		
				if (isset($fields['names/user_use_screen_name'])) {
					$screenNamesOn = !empty($values['names/user_use_screen_name']);
					$screenNamesWereOn = !empty($fields['names/user_use_screen_name']['value']);
			
					$box['tabs']['names']['notices']['turning_off_screen_names']['show'] =
						$screenNamesWereOn && !$screenNamesOn;
			
					$box['tabs']['names']['notices']['turning_on_screen_names']['show'] =
						$screenNamesOn && !$screenNamesWereOn
						&& ze\row::exists('users', array('screen_name' => array('!' => '')));
				}
		
		
				if ($values['inactive_user_email/time_user_inactive_1']){
					$fields['inactive_user_email/inactive_user_email_template_1']['hidden'] = false;
				} else{
					$fields['inactive_user_email/inactive_user_email_template_1']['hidden'] = true;
				}
	
				if ($values['inactive_user_email/time_user_inactive_2']){
					$fields['inactive_user_email/inactive_user_email_template_2']['hidden'] = false;
				} else{
					$fields['inactive_user_email/inactive_user_email_template_2']['hidden'] = true;
				}
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($settingGroup) {
			case 'users':
				if ($values['inactive_user_email/time_user_inactive_1'] && !$values['inactive_user_email/inactive_user_email_template_1']){
					$fields['inactive_user_email/inactive_user_email_template_1']['error'] = ze\admin::phrase('Please select an email template for the first period.');
				}
		
				if ($values['inactive_user_email/time_user_inactive_2'] && !$values['inactive_user_email/inactive_user_email_template_2']){
					$fields['inactive_user_email/inactive_user_email_template_1']['error'] = ze\admin::phrase('Please select an email template for the second period.');
				}
				
				if ($values['passwords/min_extranet_user_password_length'] < 3 || $values['passwords/min_extranet_user_password_length'] > 32){
					$fields['passwords/min_extranet_user_password_length']['error'] = ze\admin::phrase('The minimum password lengt must be a number between 3 and 32.');
				}
				
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($settingGroup) {
			case 'users':
				//Changes to extranet user passwords..?
				if (ze\priv::check('_PRIV_EDIT_SITE_SETTING') && ze\ring::engToBoolean($box['tabs']['passwords']['edit_mode']['on'] ?? false)) {
			
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
							ze\row::update('users', array('identifier' => null), $affectedUsers);
				
							//Loop through all users and set identifiers
							$result = ze\row::query('users', array('id', 'screen_name', 'first_name', 'last_name', 'email'), $affectedUsers);
							while ($user = ze\sql::fetchAssoc($result)) {
								ze\row::update('users', array('identifier' => ze\userAdm::generateIdentifier($user['id'], $user)), $user['id']);
							}
						}
					}
				}
		}
	}
}
