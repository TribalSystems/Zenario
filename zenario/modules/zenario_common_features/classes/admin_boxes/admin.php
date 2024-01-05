<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__admin_boxes__admin extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['view_profile']) {
			$box['key']['id'] = ze\admin::id();
		}
		
		
		//Show the code names of permissions to developers
		if (ze\admin::setting('show_dev_tools')) {
			foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
				if (is_array($field)
				 && !empty($field['type'])
				 && !empty($field['is_admin_permission'])
				 && ze\ring::engToBoolean($field['is_admin_permission'])) {
					
					if ($field['type'] == 'checkboxes' && !empty($field['values'])) {
						foreach ($field['values'] as $valueName => &$value) {
							$value['post_field_html'] = ' (<code>'. htmlspecialchars($valueName). '</code>)';
						}
						unset($value);
					}
				}
			}
			unset($field);
		}
		

		if ($box['key']['id']) {
			if (!$details = ze\row::get('admins', true, $box['key']['id'])) {
				exit;

			} elseif ($details['authtype'] != 'local') {
				$box['tabs']['details']['edit_mode']['enabled'] = false;
				$box['tabs']['password']['hidden'] = true;
				$box['tabs']['permissions']['edit_mode']['enabled'] = false;

			} elseif (ze\priv::check('_PRIV_EDIT_ADMIN') || ($path == 'zenario_admin_edit_self' && (ze\admin::id() == $box['key']['id']))) {
				$box['tabs']['details']['edit_mode']['enabled'] = true;
				$box['tabs']['permissions']['edit_mode']['enabled'] = true;
			}
			
			if ($details['status'] == 'deleted') {
				$box['tabs']['password']['hidden'] = true;
			}

			
			//Load this admin's settings
			if (is_array($box['tabs'] ?? false)) {
				foreach ($box['tabs'] as $tabName => &$tab) {
					if (is_array($tab)
					 && !empty($tab['fields'])
					 && is_array($tab['fields'])) {
						foreach ($tab['fields'] as &$field) {
							if (($settingName = $field['admin_setting']['name'] ?? false)
							 && (false !== ($settingValue = ze\row::get('admin_settings', 'value', ['name' => $settingName, 'admin_id' => $box['key']['id']])))) {
								$field['value'] = ze\row::get('admin_settings', 'value', ['name' => $settingName, 'admin_id' => $box['key']['id']]);
							}
						}
						
						if ($details['status'] == 'deleted'
						 && isset($tab['notices']['is_trashed'])) {
							
							$tab['notices']['is_trashed']['show'] = true;
							unset($tab['edit_mode']);
						}
					}
				}
			}

			$box['key']['authtype'] = $details['authtype'];
			$box['key']['global_id'] = $details['global_id'];
			
			
			$values['details/username'] = $details['username'];
			$values['details/first_name'] = $details['first_name'];
			$values['details/last_name'] = $details['last_name'];
			$values['details/email'] = $details['email'];
			$values['details/is_client_account'] = $details['is_client_account'];
			$values['permissions/permissions'] = $details['permissions'];
			$values['permissions/specific_content_items'] = $details['specific_content_items'];
			$values['permissions/specific_content_types'] = $details['specific_content_types'];

			if ($box['key']['id'] && $path == 'zenario_admin_edit_self') {
				$fields['details/email']['read_only'] = true;
				$fields['details/email']['note_below'] = ze\admin::phrase('To change this, close this box and click the "Change email address" button.');
			}
			
			$allPerms = $details['permissions'] == 'all_permissions';
			$isCurrentAdmin = $box['key']['id'] == ze\admin::id();
			
			
			//Get a list of permissions that an Admin is in
			$perms = ze\admin::loadPerms($box['key']['id']);

			//Ensure the array fields is sorted by ordinal, as there is logic in PHP that relies on them being in
			//the right order.
			uasort($box['tabs']['permissions']['fields'], ['zenario_common_features', 'sortFieldsByOrd']);

			//Set the checkboxes for permissions up appropriately
			foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
				if (is_array($field)
				 && !empty($field['type'])
				 && !empty($field['is_admin_permission'])
				 && ze\ring::engToBoolean($field['is_admin_permission'])) {
					
					if ($field['type'] == 'checkbox') {
						$field['value'] = $allPerms || !empty($perms[$fieldName]);
							
					} elseif ($field['type'] == 'checkboxes' && !empty($field['values'])) {
						$items = [];
						foreach ($field['values'] as $valueName => &$value) {
							if (!empty($perms[$valueName])) {
								$items[] = $valueName;
							}
						}
						unset($value);
						
						if ($allPerms) {
							$field['value'] = ze\escape::in(array_keys($field['values']), false);
						} else {
							$field['value'] = ze\escape::in($items, false);
						}
					}

					//Don't let an Admin remove their own management rights.
					if ($isCurrentAdmin && ze::in($fieldName, 'perm_manage', 'perm_manage_permissions')) {
						$field['readonly'] = true;
					}
				}
			}
			unset($field);
			
			//Admins shouldn't be able to change themselves into a limited admin
			if ($isCurrentAdmin) {
				$fields['permissions/permissions']['values']['specific_areas']['disabled'] = true;
			}
			
			if ($box['key']['id'] == ze\admin::id()) {
				$box['title'] = ze\admin::phrase('Editing your profile');
				
				if ($details['authtype'] != 'local') {
					$box['tabs']['details']['fields']['desc']['snippet']['html'] =
						ze\admin::phrase("Your details are stored in a global database outside of this site's database. You can only make changes via the control site.");
					$box['tabs']['permissions']['fields']['desc']['snippet']['html'] =
						ze\admin::phrase("Your permissions are stored in a global database outside of this site's database. You can only make changes via the control site.");
				} else {
					$box['tabs']['details']['fields']['desc']['hidden'] =
					$box['tabs']['password']['fields']['desc']['hidden'] =
					$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
				}
			
			} elseif ($details['authtype'] != 'local') {
				$box['title'] = ze\admin::phrase('Editing the account of multi-site administrator "[[username]]"', $details);
				
				$box['tabs']['details']['fields']['desc']['snippet']['html'] =
					ze\admin::phrase("This administrator's details are stored in a global database outside of this site's database. You can only make changes via the control site.");
				$box['tabs']['permissions']['fields']['desc']['snippet']['html'] =
					ze\admin::phrase("This administrator's permissions are stored in a global database outside of this site's database. You can only make changes via the control site.");
			
			} else {
				$box['title'] = ze\admin::phrase('Editing the account of local administrator "[[username]]"', $details);
				
				$box['tabs']['details']['fields']['desc']['snippet']['html'] =
					ze\admin::phrase("Use this screen to change this administrator's details.");
				$box['tabs']['password']['fields']['desc']['snippet']['html'] =
					ze\admin::phrase("Use this screen to change this administrator's password.");
				$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
			}
			
			//Show a notice for inactive admins
			if (ze\admin::isInactive($box['key']['id'])) {
				$box['tabs']['details']['notices']['is_inactive']['show'] = true;
				
				if ($details['last_login']) {
					$box['tabs']['details']['notices']['is_inactive']['message'] = ze\admin::phrase(
						"This administrator hasn't logged in since [[last_login]], [[days]] days ago.", 
						[
							'last_login' => ze\admin::formatDate($details['last_login'], '_MEDIUM'), 
							'days' => (string)floor((strtotime('now') - strtotime($details['last_login'])) / 60 / 60 / 24)
						]
					);
				} else {
					$box['tabs']['details']['notices']['is_inactive']['message'] = ze\admin::phrase(
						"This administrator was created on [[created_date]] and has never logged in.", 
						[
							'created_date' => ze\admin::formatDate($details['created_date'], '_MEDIUM')
						]
					);
				}
				
				
			}
			
		} else {
			ze\priv::exitIfNot('_PRIV_CREATE_ADMIN');
			
			$box['title'] = ze\admin::phrase('Creating a local administrator');
			
			$box['tabs']['details']['fields']['desc']['snippet']['html'] =
				ze\admin::phrase("Use this screen to define a new local administrator for this site.");
			$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
			
			
			
			$box['tabs']['details']['edit_mode']['enabled'] =
			$box['tabs']['password']['edit_mode']['enabled'] =
			$box['tabs']['permissions']['edit_mode']['enabled'] = true;
			$box['tabs']['details']['edit_mode']['use_view_and_edit_mode'] =
			$box['tabs']['password']['edit_mode']['use_view_and_edit_mode'] =
			$box['tabs']['permissions']['edit_mode']['use_view_and_edit_mode'] =
			$box['tabs']['details']['edit_mode']['enable_revert'] =
			$box['tabs']['password']['edit_mode']['enable_revert'] =
			$box['tabs']['permissions']['edit_mode']['enable_revert'] = false;
			$values['is_client_account'] = true;
			
			//Show a notice that the admin will set their own password
			$box['tabs']['password']['notices']['new_admin']['show'] = true;
		}
		
		$fields['permissions/specific_content_types']['values'] = ze\row::getValues('content_types', 'content_type_name_en', [], 'content_type_name_en');
		
		$adminAuthType = ze\row::get('admins', 'authtype', ze\admin::id());
		if ($adminAuthType == 'local') {
			$fields['is_client_account']['disabled'] = true;
		}
		$limit = ze\site::description('max_local_administrators');
		$limitCount = (int)$limit;
		if (!$limit) {
			$limit = 'unlimited';
		}
		$fields['details/is_client_account']['note_below'] = ze\admin::nPhrase(
			'If checked, the administrator is a client account. This site allows for [[limit]] local administrator.', 
			'If checked, the administrator is a client account. This site allows for [[limit]] local administrators.', 
			$limitCount,
			['limit' => $limit]);
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Loop through the fields on the permissions tab, looking for parent checkboxes, child checkboxes, and the
		//toggles that show/hide the children.
		//However, we need to hide every field instead if the "This administrator has every possible permission" option is checked
		$rowStart = false;
		$specificActionsOpen = $values['permissions/permissions'] == 'specific_actions';
		
		foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
			if (is_array($field) && !empty($field['type'])) {
				
				$isAdminPerm = (!empty($field['is_admin_permission']) && ze\ring::engToBoolean($field['is_admin_permission']));
				$isInAdminPermGrouping = (!empty($field['grouping']) && $field['grouping'] == 'specific_actions');
				
				//Hide every other field if the "This administrator has every possible permission" option is checked.
				//(Except for the dev tools, which is a hard-coded exception.)
				if ($isAdminPerm || $isInAdminPermGrouping) {
					$field['hidden'] = !$specificActionsOpen;
				}
				
				if ($isAdminPerm && $specificActionsOpen) {
			
					//Hide every other field if the "This administrator has every possible permission" option is checked.
					//(Except for the dev tools, which is a hard-coded exception.)
					if ($values['permissions/permissions'] != 'specific_actions') {
						$field['hidden'] = true;
			
					} else {
						unset($field['hidden']);
			
						//Look for checkboxes on their own, add some styling around them
						if (!ze\ring::engToBoolean($field['same_row'] ?? false) && $field['type'] == 'checkbox') {
							$rowStart = $fieldName;
							$field['row_class'] = 'zenario_perms';
						}

						//Look for single checkboxes that are immediately followed by multiple checkboxes.
						//In this case turn the single checkbox into a role, which encompases several actions.
						if ($rowStart && $field['type'] == 'checkboxes' && !empty($field['values'])) {

							//Add a toggle field just after the single checkbox, which can show/hide the multiple checkboxes
							$toggle = $rowStart. '_toggle';
							if (empty($box['tabs']['permissions']['fields'][$toggle])) {
								$box['tabs']['permissions']['fields'][$toggle] = [
										'grouping' => 'specific_actions',
										'ord' => $box['tabs']['permissions']['fields'][$rowStart]['ord']. '.1',
										'type' => 'toggle',
										'same_row' => true,
										'visible_if' => 'zenarioAB.togglePressed(field, 1)',
										'redraw_onchange' => true,
										'can_be_pressed_in_view_mode' => true];
							}

							$field['row_class'] = 'zenario_hierarchical_perms';
							$field['visible_if'] = 'zenarioAB.togglePressed(field, 2)';



							//Loop through each value for the child checkboxes and count them.
							$n = 0;
							foreach ($field['values'] as $valueName => &$value) {
								++$n;
							}
							unset($value);

							//Count how many children have been checked
							if (empty($values['permissions'. '/'. $fieldName])) {
								$c = 0;
							} else {
								$c = count(ze\ray::explodeAndTrim($values['permissions'. '/'. $fieldName]));
							}

							//Set the "X / Y" display on the toggle
							$box['tabs']['permissions']['fields'][$toggle]['value'] =
							$box['tabs']['permissions']['fields'][$toggle]['current_value'] = $c. '/'. $n;

							//Check or uncheck the parent, depending on if at least one child is checked.
							//Also set a CSS class on the row around the parent depending on how many were checked.
							$parentChecked = true;
							if ($c == 0) {
								$parentChecked = false;
								$box['tabs']['permissions']['fields'][$rowStart]['row_class'] = 'zenario_perms zenario_permgroup_empty';
						
							} elseif ($c < $n) {
								$box['tabs']['permissions']['fields'][$rowStart]['row_class'] = 'zenario_perms zenario_permgroup_half_full';
						
							} else {
								$box['tabs']['permissions']['fields'][$rowStart]['row_class'] = 'zenario_perms zenario_permgroup_full';
							}

							$box['tabs']['permissions']['fields'][$rowStart][
							ze\ring::engToBoolean($box['tabs']['permissions']['fields']['edit_mode']['on'] ?? false)? 'current_value' : 'value'
									] = $parentChecked;

							//Set up JavaScript logic to update all of this when an Admin changes the value of a checkbox
							$field['onclick'] =
							$field['onchange'] =
							"zenarioAB.adminPermChange('". ze\escape::js($rowStart). "', '". ze\escape::js($fieldName). "', '". ze\escape::js($toggle). "');";
							$box['tabs']['permissions']['fields'][$rowStart]['onclick'] =
							$box['tabs']['permissions']['fields'][$rowStart]['onchange'] =
							"zenarioAB.adminParentPermChange('". ze\escape::js($rowStart). "', '". ze\escape::js($fieldName). "', '". ze\escape::js($toggle). "');";

							$rowStart = $toggle = false;
						}
					}
				}
			}
		}

		return false;
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$editing =
		ze\ring::engToBoolean($box['tabs']['details']['edit_mode']['on'] ?? false)
		|| ze\ring::engToBoolean($box['tabs']['password']['edit_mode']['on'] ?? false)
		|| ze\ring::engToBoolean($box['tabs']['permissions']['edit_mode']['on'] ?? false);

		if ($box['key']['id']) {
			if (!$details = ze\row::get('admins', true, $box['key']['id'])) {
				exit;
			}

			if ($details['status'] == 'trashed') {
				exit;
			}

			if ($editing && ($details['authtype'] != 'local' || (!ze\priv::check('_PRIV_EDIT_ADMIN') && !($path == 'zenario_admin_edit_self' && (ze\admin::id() == $box['key']['id']))))) {
				exit;
			}
			
			if (ze\ring::engToBoolean($box['tabs']['password']['edit_mode']['on'] ?? false)) {
				ze\priv::exitIfNot('_PRIV_CHANGE_ADMIN_PASSWORD');
			}

		} else {
			ze\priv::exitIfNot('_PRIV_CREATE_ADMIN');
		}

		//Exit if any of these:
		//Edit mode is off,
		//No admin ID,
		//Admin without permissions is trying to edit another admin's details.

		//PLEASE NOTE: An admin with no permissions can still edit their own profile (First name, Last name),
		//though the Username/Email fields and the "Is client account" checkbox are all read-only.
		if (ze\ring::engToBoolean($box['tabs']['details']['edit_mode']['on'] ?? false)
			&& (
				!$box['key']['id']
				|| ($path == 'zenario_admin_edit_self' && (ze\admin::id() == $box['key']['id']))
				|| ze\priv::exitIfNot('_PRIV_EDIT_ADMIN')
			)
		) {

			//Attempt to ensure that username and email are unique.
			//However if the Admin is not trying to change the username/email, then apply a "grandfather rule" and let existing bad data stay as it is.
			if (!$box['key']['id'] || $values['details/username'] != $details['username']) {
				if (ze\row::exists('admins', ['username' => $values['details/username'], 'id' => ['!' => (int) $box['key']['id']]])
				|| (ze\db::connectGlobal()
						&& ze\row\g::exists('admins', ['username' => $values['details/username'], 'id' => ['!' => (int) $box['key']['global_id']]]))) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('An Administrator with this Username already exists. Please choose a different Username.');
				} elseif (preg_match('/[A-Z]/', $values['details/username'])) {
					$fields['details/username']['error'] = ze\admin::phrase('The admin username must not contain upper case characters.');
				}
			}

			if (!$box['key']['id'] || $values['details/email'] != $details['email']) {
				if (ze\row::exists('admins', ['email' => $values['details/email'], 'id' => ['!' => (int) $box['key']['id']]])
				|| (ze\db::connectGlobal()
						&& ze\row\g::exists('admins', ['email' => $values['details/email'], 'id' => ['!' => (int) $box['key']['global_id']]]))) {
					$box['tabs']['details']['errors'][] = ze\admin::phrase('A Zenario administrator with this already exists with this email address. Please either edit that administrator or enter a different email address.');
				}
			}
		}
		
		//Check the password fields
		if ($box['key']['id'] && ze\ring::engToBoolean($box['tabs']['password']['edit_mode']['on'] ?? false)) {
			$passwordValidation = \ze\user::checkPasswordStrength($values['password/password']);
			if (!$values['password/password']) {
				$box['tabs']['password']['errors'][] = ze\admin::phrase('Please enter a password.');

			} elseif (!$passwordValidation['password_matches_requirements']) {
				$box['tabs']['password']['errors'][] = ze\admin::phrase('The password provided is not strong enough. Please make the password longer, or try mixing in upper and lower case letters, numbers or non-alphanumeric characters.');

			} elseif (!$values['password/password_confirm']) {
				$box['tabs']['password']['errors'][] = ze\admin::phrase('Please enter a password in both password fields.');

			} elseif ($values['password/password'] != $values['password/password_confirm']) {
				$box['tabs']['password']['errors'][] = ze\admin::phrase('Please ensure that the passwords you submit are identical.');
			}
		}
		
		if (!empty($box['tabs']['permissions']['edit_mode']['on'])) {
			if ($values['permissions/permissions'] == 'specific_areas') {
				if (!$values['permissions/specific_content_items']
				 && !$values['permissions/specific_content_types']) {
					$box['tabs']['permissions']['errors'][] = ze\admin::phrase('Please select at least one content item.');

				}
			}
		}

		return false;
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!$box['key']['id'] && !zenario_common_features::canCreateAdditionalAdmins()) {
			return false;
		}
		
		
		//The password fields are on different tabs in create/edit modes
		$pTab = false;
		if ($newAdmin = !$box['key']['id']) {
			$pTab = 'details';
		} elseif (ze\ring::engToBoolean($box['tabs']['password']['edit_mode']['on'] ?? false)) {
			$pTab = 'password';
			ze\priv::exitIfNot('_PRIV_CHANGE_ADMIN_PASSWORD');
		}

		if (ze\ring::engToBoolean($box['tabs']['details']['edit_mode']['on'] ?? false)) {
			
			$details = [
				'username' => $values['details/username'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name']
			];

			if ($path != 'zenario_admin_edit_self') {
				$details['email'] = $values['details/email'];
			}
			
			$adminAuthType = ze\row::get('admins', 'authtype', ze\admin::id());
			if ($adminAuthType == 'super') {
				$details['is_client_account'] = $values['details/is_client_account'];
			}
			
			if ($newAdmin) {
				$details['status'] = 'active';
				$details['created_date'] = ze\date::now();
			} else {
				$details['modified_date'] = ze\date::now();
			}

			$box['key']['id'] = ze\row::set('admins', $details, (int) $box['key']['id']);
			ze\contentAdm::deleteUnusedImagesByUsage('admin');
			
			if ($newAdmin) {
				ze\adminAdm::updateHash($box['key']['id']);
			}
		}
		
		//send email if inform by email checked
		if ($newAdmin) {
			$source = [];
			$dir = CMS_ROOT. 'zenario/admin/welcome/';
			$file = 'email_templates.yaml';
			if (substr($file, 0, 1) != '.') {
				$tagsToParse = ze\tuix::readFile($dir. $file);
				ze\tuix::parse($source, $tagsToParse, 'welcome');
				unset($tagsToParse);
			}
			
			$emailTemplateName = 'notification_to_new_admin_no_password';
			$emailTemplate = $source['welcome']['email_templates'][$emailTemplateName];

			$message = $emailTemplate['body'];
			$message = nl2br($message);
		
			if (ze\module::inc('zenario_email_template_manager')) {
				zenario_email_template_manager::putBodyInTemplate($message);
			}

			$subject = $emailTemplate['subject'];

			$hash = ze\row::get('admins', 'hash', $box['key']['id']);
			$email_details = [
				'username' => $values['details/username'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'email' => $values['details/email'],
				'cms_url' => ze\link::absolute(),
				'new_admin_cms_url' => ze\link::absolute() . 'admin.php?task=new_admin&hash=' . $hash
			];

			$nameTo = ze::ifNull(trim($email_details['first_name'] . ' ' . $email_details['last_name']), $email_details['username']);
			
			foreach ($email_details as $pattern => $replacement) {
				$message = str_replace('[['. $pattern. ']]', $replacement, $message);
			};
			
			ze\server::sendEmailSimple(
				$subject, $message, $isHTML = true,
				//New admin emails should always be sent to the intended recipient,
				//even if debug mode is on.
				$ignoreDebugMode = true,
				$email_details['email'],
				$nameTo, $addressFrom = false, $nameFrom = $emailTemplate['from']
			);
		}
		
		//Set a password
		if (!$newAdmin && ze\ring::engToBoolean($box['tabs']['password']['edit_mode']['on'] ?? false) && $values['password/password']) {
			ze\adminAdm::setPassword($box['key']['id'], $values['password/password'], ze\ring::engToBoolean($values['password/password_needs_changing']), $isPasswordReset = false);
		}


		if (ze\ring::engToBoolean($box['tabs']['permissions']['edit_mode']['on'] ?? false)) {
			
			//Look for checkboxes set up as permission fields
			$perms = [];
			
			$isEditingThemselves = $box['key']['id'] == ze\admin::id();
			$isAuthor = ($values['permissions/perm_author_permissions'] ?? '')
					 && strpos($values['permissions/perm_author_permissions'], '_PRIV_EDIT_DRAFT') !== false;
			
			foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
				//Ignore info tags, non-fields and anything that's not a checkbox/checkboxes.
				if (is_array($field)
				 && !empty($field['type'])
				 && !empty($field['is_admin_permission'])
				 && ze\ring::engToBoolean($field['is_admin_permission'])) {
					
					$isEditingThemselvesHere = $isEditingThemselves && ze::in($fieldName,
						'perm_manage', 'perm_manage_permissions'
					);
					$isntAuthorHere = !$isAuthor && ze::in($fieldName,
						'perm_editmenu', 'perm_editmenu_permissions',
						'perm_publish', 'perm_publish_permissions',
						'perm_designer', 'perm_designer_permissions'
					);
					
					//For single checkboxes, just save one permission
					if ($field['type'] == 'checkbox') {
						//Don't let an Admin change their own management rights.
						if ($isEditingThemselvesHere) {
							$perms[$fieldName] = ze\priv::check($fieldName);
						} elseif ($isntAuthorHere) {
							$perms[$fieldName] = false;
						} else {
							$perms[$fieldName] = !empty($values['permissions'. '/'. $fieldName]);
						}
							
					//For multiple checkboxes, save one permission per checkbox
					} else
					if ($field['type'] == 'checkboxes' && !empty($field['values'])) {
						foreach ($field['values'] as $valueName => &$value) {
							if ($isEditingThemselvesHere) {
								$perms[$valueName] = ze\priv::check($valueName);
							} elseif ($isntAuthorHere) {
								$perms[$valueName] = false;
							} else {
								$perms[$valueName] = in_array($valueName, ze\ray::explodeAndTrim($values['permissions'. '/'. $fieldName]));
							}
						}
						unset($value);
					}
				}
			}
			
			$details = [
				'specific_content_items' => '',
				'specific_content_types' => ''];
			
			if ($values['permissions/permissions'] == 'specific_areas') {
				$details['specific_content_items'] = $values['permissions/specific_content_items'];
				$details['specific_content_types'] = $values['permissions/specific_content_types'];
			}
			ze\adminAdm::savePerms($box['key']['id'], $values['permissions/permissions'], $perms, $details);

			if ($box['key']['id'] == ze\admin::id()) {
				ze\admin::setSession(ze\admin::id());
				$this->needReload = true;
			}
		}
		
		//Save admin settings
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab) && ze\ring::engToBoolean($tab['edit_mode']['on'] ?? false)) {
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (is_array($field)) {
						if (!($field['readonly'] ?? false)
						 && !($field['read_only'] ?? false)
						 && $settingName = $field['admin_setting']['name'] ?? false) {
					
							//Get the value of the setting. Hidden fields should count as being empty
							if (ze\ring::engToBoolean($field['hidden'] ?? false)
							 || ze\ring::engToBoolean($field['_was_hidden_before'] ?? false)) {
								$value = '';
							} else {
								$value = ze\ray::value($values, $tabName. '/'. $fieldName);
							}
							
							ze\row::set('admin_settings', ['value' => $value], ['name' => $settingName, 'admin_id' => $box['key']['id']]);
						}
					}
				}
			}
		}

		return false;
	}
	
	private $needReload = false;
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($this->needReload) {
			ze\tuix::closeWithFlags(['reload_organizer' => true]);
			exit;
		}
	}
}
