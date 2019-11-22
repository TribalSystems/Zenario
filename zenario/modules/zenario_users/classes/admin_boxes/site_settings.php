<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
				$scheduledTaskManagerRunning = ze\row::exists('modules', ['class_name' => 'zenario_scheduled_task_manager', 'status' => 'module_running']);
				if (!$scheduledTaskManagerRunning 
					|| !ze::setting('jobs_enabled') 
					|| !ze\module::inc('zenario_scheduled_task_manager')
					|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobRemoveInactivePendingUsers')
				) {
					$values['unconfirmed_users/remove_inactive_users'] = false;
					$fields['unconfirmed_users/remove_inactive_users']['disabled'] = true;
					
					$fields['unconfirmed_users/remove_inactive_users']['side_note'] = 
						ze\admin::phrase('The scheduled task manager module must be running and the task "jobRemoveInactivePendingUsers" must be enabled to remove inactive users.');
				}
				
				if (!$scheduledTaskManagerRunning 
					|| !ze::setting('jobs_enabled') 
					|| !ze\module::inc('zenario_scheduled_task_manager')
					|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobSendInactiveUserEmail')
				) {
					$values['inactive_user_email/time_user_inactive_1'] = 
					$values['inactive_user_email/time_user_inactive_2'] = 
					$values['inactive_user_email/user_dataset_field_to_receive_emails'] = false;
					
					$fields['inactive_user_email/time_user_inactive_1']['disabled'] = 
					$fields['inactive_user_email/time_user_inactive_2']['disabled'] = 
					$fields['inactive_user_email/user_dataset_field_to_receive_emails']['disabled'] = true;
					
					$fields['inactive_user_email/time_user_inactive_1']['side_note'] = 
					$fields['inactive_user_email/time_user_inactive_2']['side_note'] = 
					$fields['inactive_user_email/user_dataset_field_to_receive_emails']['side_note'] = 
						ze\admin::phrase('The scheduled task manager module must be running and the task "jobSendInactiveUserEmail" must be enabled to remove inactive users.');
				}
		
				// Show list of users dataset tabs
				$dataset = ze\dataset::details('users');
				$result = ze\row::query('custom_dataset_tabs', ['label', 'default_label', 'ord', 'name'], ['dataset_id' => $dataset['id']], 'label');
				while ($row = ze\sql::fetchAssoc($result)) {
					$label = $row['label'] ? $row['label'] : $row['default_label'];
					if ($label !== false && $label !== '') {
						$fields['groups/default_groups_dataset_tab']['values'][$row['name']] = [
							'label' => $label,
							'ord' => $row['ord']
						];
						
						$fields['flags/default_flags_dataset_tab']['values'][$row['name']] = [
							'label' => $label,
							'ord' => $row['ord']
						];
					}
				}
				
				$link = ze\link::absolute() . '/zenario/admin/organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
				$fields['names/data_protection_link']['snippet']['html'] = ze\admin::phrase('See the <a target="_blank" href="[[link]]">data protection</a> panel for settings on how long to user sign-in and content-access logs.', ['link' => htmlspecialchars($link)]);
				break;
			
			case 'perms':
				//Check to see what's running
				$ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager', true);
				$ZENARIO_COMPANY_LOCATIONS_MANAGER_PREFIX = ze\module::prefix('zenario_company_locations_manager', true);
				
				if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX) {
					$box['lovs']['roles'] = ze\row::getValues($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name', 'id');
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
											if (($permName = ze\ring::chopPrefix('perm.', $fieldId))
											 && (ze\user::checkNamedPermExists($permName, $directlyAssignedToUser, $hasRoleAtCompany, $hasRoleAtLocation, $hasRoleAtLocationAtCompany, $onlyIfHasRolesAtAllAssignedLocations))) {
												
												//Break the permission code-name up into chunks
												$perm = explode('.', $fieldId);
												
												//Show the ze\user::canCreate() shortcut function if this is a create permission
												if ($thingToCreate = ze\ring::chopPrefix('create-', $perm[1])) {
													$field['side_note'] = '<code>ze\\user::canCreate(\''. htmlspecialchars($thingToCreate). '\'';
												} else {
													$field['side_note'] = '<code>ze\\user::can(\''. htmlspecialchars($perm[1]). '\'';
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
			case 'data_protection':
				//Show a table of users dataset fields and whether they are encrypted or not
				$dataset = ze\dataset::details('users');
				$sql = '
					SELECT cdf.id, cdf.db_column, cdf.is_system_field, cdf.type
					FROM ' . DB_PREFIX . 'custom_dataset_fields cdf
					INNER JOIN ' . DB_PREFIX . 'custom_dataset_tabs cdt
						ON cdf.tab_name = cdt.name
						AND cdf.dataset_id = cdt.dataset_id
					WHERE cdf.dataset_id = ' . (int)$dataset['id'] . '
					AND db_column != ""
					ORDER BY cdt.ord, cdf.ord';
				$result = ze\sql::select($sql);
				$html = '
					<table class="basic_table">
						<tr>
							<th style="width:25%;">' . htmlspecialchars(ze\admin::phrase('Database field name')) . '</th>
							<th style="width:25%;">' . htmlspecialchars(ze\admin::phrase('Data type')) . '</th>
							<th style="width:25%;">' . htmlspecialchars(ze\admin::phrase('Security')) . '</th>
							<th style="width:25%;">' . htmlspecialchars(ze\admin::phrase('Comment')) . '</th>
						</tr>';
				while ($row = ze\sql::fetchAssoc($result)) {
					$table = $row['is_system_field'] ? $dataset['system_table'] : $dataset['table'];
					
					if ($row['db_column'] == 'password') {
						$security = 'Encrypted';
						$comment = '1-way encrypted';
					} elseif (ze::$dbL->columnIsEncrypted($table, $row['db_column'])) {
						$security = 'Encrypted';
						$comment = '';
					} elseif (in_array($row['db_column'], ['salutation', 'first_name', 'last_name', 'screen_name', 'identifier', 'email']) || (!$row['is_system_field'] && $row['type'] == 'text')) {
						$security = 'Not encrypted';
						$comment = 'Can encrypt';
					} else {
						$security = 'Not encrypted';
						$comment = '';
					}
					
					$html .= '
						<tr>
							<td>' . htmlspecialchars($row['db_column']) . '</td>
							<td>' . htmlspecialchars(ze\datasetAdm::getFieldTypeDescription($row['type'])) . '</td>
							<td>' . htmlspecialchars(ze\admin::phrase($security)) . '</td>
							<td>' . htmlspecialchars(ze\admin::phrase($comment)) . '</td>
						</tr>';
				}
				$html .= '
					<table>';
				$fields['data_encryption/dataset_fields']['snippet']['html'] = $html;
				
				//Show the number of records currently stored
				$count = ze\row::count('user_signin_log');
				$note = ze\admin::nphrase('1 record currently stored.', '[[count]] records currently stored.', $count);
				
				if ($count) {
					$min = ze\row::min('user_signin_log', 'login_datetime');
					$note .= ' ' . ze\admin::phrase('Oldest record from [[date]].', ['date' => ze\admin::formatDateTime($min, '_MEDIUM')]);
				}
				
				$link = ze\link::absolute() . 'zenario/admin/organizer.php#zenario__users/nav/sign_in_log/panel';
				$note .= ' ' . '<a target="_blank" href="' . $link . '">View</a>';
				$fields['data_protection/period_to_delete_sign_in_log']['note_below'] = $note;
				
				$count = ze\row::count('user_content_accesslog');
				$note = ze\admin::nphrase('1 record currently stored.', '[[count]] records currently stored.', $count);
				
				if ($count) {
					$min = ze\row::min('user_content_accesslog', 'hit_datetime');
					$note .= ' ' . ze\admin::phrase('Oldest record from [[date]].', ['date' => ze\admin::formatDateTime($min, '_MEDIUM')]);
				}
				
				$link = ze\link::absolute() . 'zenario/admin/organizer.php#zenario__users/panels/access_log';
				$note .= ' ' . '<a target="_blank" href="' . $link . '">View</a>';
				$fields['data_protection/period_to_delete_the_user_content_access_log']['note_below'] = $note;
				
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
						&& ze\row::exists('users', ['screen_name' => ['!' => '']]);
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
				
				break;
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
				
				if ($values['passwords/min_extranet_user_password_length'] < 8 || $values['passwords/min_extranet_user_password_length'] > 32){
					$fields['passwords/min_extranet_user_password_length']['error'] = ze\admin::phrase('The minimum password length must be between [[min_password_length]] and [[max_password_length]].',
						['min_password_length' => 8, 'max_password_length' => 32]);
				}
				
				//Require at least one character requirement checkbox to be selected.
				if (!$values['passwords/a_z_lowercase_characters_in_user_password']
					&& !$values['passwords/a_z_uppercase_characters_in_user_password']
					&& !$values['passwords/0_9_numbers_in_user_password']
					&& !$values['passwords/symbols_in_user_password']) {
					
					$fields['passwords/a_z_lowercase_characters_in_user_password']['error'] =
					$fields['passwords/a_z_uppercase_characters_in_user_password']['error'] =
					$fields['passwords/0_9_numbers_in_user_password']['error'] = true;
					$fields['passwords/symbols_in_user_password']['error'] = ze\admin::phrase('Please select at least one password character requirement.');
				}
				break;
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
								$affectedUsers = [];
							} else {
								//If we're turning screen names on, only users with a screen name will be affected
								$affectedUsers = ['screen_name' => ['!' => '']];
							}
				
							//Clear the previous identifiers
							ze\row::update('users', ['identifier' => null], $affectedUsers);
				
							//Loop through all users and set identifiers
							$result = ze\row::query('users', ['id', 'screen_name', 'first_name', 'last_name', 'email'], $affectedUsers);
							while ($user = ze\sql::fetchAssoc($result)) {
								ze\row::update('users', ['identifier' => ze\userAdm::generateIdentifier($user['id'], $user)], $user['id']);
							}
						}
					}
				}
		}
	}
}
