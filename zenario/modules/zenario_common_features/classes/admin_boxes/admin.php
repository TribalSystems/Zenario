<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__admin extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['view_profile']) {
			$box['key']['id'] = adminId();
		}

		if ($box['key']['id']) {
			if (!$details = getRow('admins', true, $box['key']['id'])) {
				exit;

			} elseif ($details['authtype'] != 'local') {
				$box['tabs']['details']['edit_mode']['enabled'] = false;
				$box['tabs']['password']['hidden'] = true;
				$box['tabs']['permissions']['edit_mode']['enabled'] = false;

			} elseif (checkPriv('_PRIV_EDIT_ADMIN')) {
				$box['tabs']['details']['edit_mode']['enabled'] = true;
				$box['tabs']['permissions']['edit_mode']['enabled'] = true;
			}
			
			if ($details['status'] == 'deleted') {
				$box['tabs']['password']['hidden'] = true;
			}

			
			//Load this admin's settings
			if (is_array(arrayKey($box,'tabs'))) {
				foreach ($box['tabs'] as $tabName => &$tab) {
					if (is_array($tab)
					 && !empty($tab['fields'])
					 && is_array($tab['fields'])) {
						foreach ($tab['fields'] as &$field) {
							if (($settingName = arrayKey($field, 'admin_setting', 'name'))
							 && (false !== ($settingValue = getRow('admin_settings', 'value', array('name' => $settingName, 'admin_id' => $box['key']['id']))))) {
								$field['value'] = getRow('admin_settings', 'value', array('name' => $settingName, 'admin_id' => $box['key']['id']));
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
			$values['details/image'] = $details['image_id'];
			$values['details/is_client_account'] = $details['is_client_account'];
			$values['permissions/permissions'] = $details['permissions'];
			$values['permissions/specific_languages'] = $details['specific_languages'];
			$values['permissions/specific_content_items'] = $details['specific_content_items'];
			$values['permissions/specific_menu_areas'] = $details['specific_menu_areas'];
			
			$allPerms = $details['permissions'] == 'all_permissions';
			$isCurrentAdmin = $box['key']['id'] == adminId();
			
			
			//Get a list of permissions that an Admin is in
			$perms = loadAdminPerms($box['key']['id']);

			//Ensure the array fields is sorted by ordinal, as there is logic in PHP that relies on them being in
			//the right order.
			uasort($box['tabs']['permissions']['fields'], array('zenario_common_features', 'sortFieldsByOrd'));

			//Set the checkboxes for permissions up appropriately
			foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
				if (is_array($field)
				 && !empty($field['type'])
				 && !empty($field['is_admin_permission'])
				 && engToBoolean($field['is_admin_permission'])) {
					
					if ($field['type'] == 'checkbox') {
						$field['value'] = $allPerms || !empty($perms[$fieldName]);
							
					} elseif ($field['type'] == 'checkboxes' && !empty($field['values'])) {
						$items = array();
						foreach ($field['values'] as $valueName => &$value) {
							if (!empty($perms[$valueName])) {
								$items[] = $valueName;
							}
						}
						
						if ($allPerms) {
							$field['value'] = inEscape(array_keys($field['values']), false);
						} else {
							$field['value'] = inEscape($items, false);
						}
					}

					//Don't let an Admin remove their own management rights.
					if ($isCurrentAdmin && in($fieldName, 'perm_manage', 'perm_manage_permissions')) {
						$field['read_only'] = true;
					}
				}
			}
			
			//Admins shouldn't be able to change themselves into a limited admin
			if ($isCurrentAdmin) {
				$fields['permissions/permissions']['values']['specific_languages']['disabled'] = true;
				$fields['permissions/permissions']['values']['specific_menu_areas']['disabled'] = true;
			}
			
			if ($box['key']['id'] == adminId()) {
				$box['title'] = adminPhrase('Editing your profile');
				
				if ($details['authtype'] != 'local') {
					$box['tabs']['details']['fields']['desc']['snippet']['html'] =
						adminPhrase("Your details are stored in a global database outside of this site's database. You can only make changes via the control site.");
					$box['tabs']['permissions']['fields']['desc']['snippet']['html'] =
						adminPhrase("Your permissions are stored in a global database outside of this site's database. You can only make changes via the control site.");
				} else {
					$box['tabs']['details']['fields']['desc']['hidden'] =
					$box['tabs']['password']['fields']['desc']['hidden'] =
					$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
				}
			
			} elseif ($details['authtype'] != 'local') {
				$box['title'] = adminPhrase('Editing the account of multi-site administrator "[[username]]"', $details);
				
				$box['tabs']['details']['fields']['desc']['snippet']['html'] =
					adminPhrase("This administrator's details are stored in a global database outside of this site's database. You can only make changes via the control site.");
				$box['tabs']['permissions']['fields']['desc']['snippet']['html'] =
					adminPhrase("This administrator's permissions are stored in a global database outside of this site's database. You can only make changes via the control site.");
			
			} else {
				$box['title'] = adminPhrase('Editing the account of local administrator "[[username]]"', $details);
				
				$box['tabs']['details']['fields']['desc']['snippet']['html'] =
					adminPhrase("Use this screen to change this administrator's details.");
				$box['tabs']['password']['fields']['desc']['snippet']['html'] =
					adminPhrase("Use this screen to change this administrator's password.");
				$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
			}

		} else {
			exitIfNotCheckPriv('_PRIV_CREATE_ADMIN');
			
			$box['title'] = adminPhrase('Creating a local administrator');
			
			$box['tabs']['details']['fields']['desc']['snippet']['html'] =
				adminPhrase("Use this screen to define a new local administrator for this site.");
			$box['tabs']['permissions']['fields']['desc']['hidden'] = true;
			
			
			
			$box['tabs']['details']['edit_mode']['enabled'] =
			$box['tabs']['details']['edit_mode']['always_on'] =
			$box['tabs']['password']['edit_mode']['enabled'] =
			$box['tabs']['password']['edit_mode']['always_on'] =
			$box['tabs']['permissions']['edit_mode']['enabled'] =
			$box['tabs']['permissions']['edit_mode']['always_on'] = true;
			$box['tabs']['details']['edit_mode']['enable_revert'] =
			$box['tabs']['password']['edit_mode']['enable_revert'] =
			$box['tabs']['permissions']['edit_mode']['enable_revert'] = false;
			$values['is_client_account'] = true;
		}
		
		$fields['permissions/specific_languages']['values'] = array();
		foreach (getLanguages(false, true, true) as $langId => $lang) {
			$fields['permissions/specific_languages']['values'][$langId] = $lang['english_name']. ' ('. $lang['id']. ')';
		}
		
		$adminAuthType = getRow('admins', 'authtype', adminId());
		if ($adminAuthType == 'local') {
			$fields['is_client_account']['disabled'] = true;
		}
		$limit = siteDescription('max_local_administrators');
		$limitCount = (int)$limit;
		if (!$limit) {
			$limit = 'unlimited';
		}
		$fields['details/is_client_account']['note_below'] = nAdminPhrase(
			'If checked, the Administrator is a client account. This site allows for [[limit]] local administrator.', 
			'If checked, the Administrator is a client account. This site allows for [[limit]] local administrators.', 
			$limitCount,
			array('limit' => $limit));
		
		$fields['permissions/permissions']['values']['specific_languages']['side_note'] =
			adminPhrase($fields['permissions/permissions']['values']['specific_languages']['side_note'],
				array('default_language' => ifNull(setting('default_language'), 'en')));
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Loop through the fields on the permissions tab, looking for parent checkboxes, child checkboxes, and the
		//toggles that show/hide the children.
		//However, we need to hide every field instead if the "This administrator has every possible permission" option is checked
		$rowStart = false;
		$specificActionsOpen = $values['permissions/permissions'] == 'specific_actions';
		
		foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
			if (is_array($field) && !empty($field['type'])) {
				
				$isAdminPerm = (!empty($field['is_admin_permission']) && engToBoolean($field['is_admin_permission']));
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
						if (!engToBooleanArray($field, 'same_row') && $field['type'] == 'checkbox') {
							$rowStart = $fieldName;
							$field['row_class'] = 'zenario_perms';
						}

						//Look for single checkboxes that are immediately followed by multiple checkboxes.
						//In this case turn the single checkbox into a role, which encompases several actions.
						if ($rowStart && $field['type'] == 'checkboxes' && !empty($field['values'])) {

							//Add a toggle field just after the single checkbox, which can show/hide the multiple checkboxes
							$toggle = $rowStart. '_toggle';
							if (empty($box['tabs']['permissions']['fields'][$toggle])) {
								$box['tabs']['permissions']['fields'][$toggle] = array(
										'grouping' => 'specific_actions',
										'ord' => $box['tabs']['permissions']['fields'][$rowStart]['ord']. '.1',
										'type' => 'toggle',
										'same_row' => true,
										'visible_if' => 'zenarioAB.togglePressed(1, tuixObject)',
										'redraw_onchange' => true,
										'can_be_pressed_in_view_mode' => true);
							}

							$field['row_class'] = 'zenario_hierarchical_perms';
							$field['visible_if'] = 'zenarioAB.togglePressed(2)';



							//Loop through each value for the child checkboxes and count them.
							$n = 0;
							foreach ($field['values'] as $valueName => &$value) {
								++$n;
							}

							//Count how many children have been checked
							if (empty($values['permissions'. '/'. $fieldName])) {
								$c = 0;
							} else {
								$c = count(explodeAndTrim($values['permissions'. '/'. $fieldName]));
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
							engToBooleanArray($box['tabs']['permissions']['fields'], 'edit_mode', 'on')? 'current_value' : 'value'
									] = $parentChecked;

							//Set up JavaScript logic to update all of this when an Admin changes the value of a checkbox
							$field['onclick'] =
							$field['onchange'] =
							"zenarioAB.adminPermChange('". jsEscape($rowStart). "', '". jsEscape($fieldName). "', '". jsEscape($toggle). "');";
							$box['tabs']['permissions']['fields'][$rowStart]['onclick'] =
							$box['tabs']['permissions']['fields'][$rowStart]['onchange'] =
							"zenarioAB.adminParentPermChange('". jsEscape($rowStart). "', '". jsEscape($fieldName). "', '". jsEscape($toggle). "');";

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
		engToBooleanArray($box['tabs']['details'], 'edit_mode', 'on')
		|| engToBooleanArray($box['tabs']['password'], 'edit_mode', 'on')
		|| engToBooleanArray($box['tabs']['permissions'], 'edit_mode', 'on');

		if ($box['key']['id']) {
			if (!$details = getRow('admins', true, $box['key']['id'])) {
				exit;
			}

			if ($details['status'] == 'trashed') {
				exit;
			}

			if ($editing && ($details['authtype'] != 'local' || !checkPriv('_PRIV_EDIT_ADMIN'))) {
				exit;
			}

		} else {
			exitIfNotCheckPriv('_PRIV_CREATE_ADMIN');
		}

		if (engToBooleanArray($box['tabs']['details'], 'edit_mode', 'on') && (!$box['key']['id'] || exitIfNotCheckPriv('_PRIV_EDIT_ADMIN'))) {

			//Attempt to ensure that username and email are unique.
			//However if the Admin is not trying to change the username/email, then apply a "grandfather rule" and let existing bad data stay as it is.
			if (!$box['key']['id'] || $values['details/username'] != $details['username']) {
				if (checkRowExists('admins', array('username' => $values['details/username'], 'id' => array('!' => (int) $box['key']['id'])))
				|| (connectGlobalDB()
						&& checkRowExists('admins', array('username' => $values['details/username'], 'id' => array('!' => (int) $box['key']['global_id']))))) {
					$box['tabs']['details']['errors'][] = adminPhrase('An Administrator with this Username already exists. Please choose a different Username.');
				}
				connectLocalDB();
			}

			if (!$box['key']['id'] || $values['details/email'] != $details['email']) {
				if (checkRowExists('admins', array('email' => $values['details/email'], 'id' => array('!' => (int) $box['key']['id'])))
				|| (connectGlobalDB()
						&& checkRowExists('admins', array('email' => $values['details/email'], 'id' => array('!' => (int) $box['key']['global_id']))))) {
					$box['tabs']['details']['errors'][] = adminPhrase('An Administrator with this Email Address already exists. Please choose a different Email Address.');
				}
				connectLocalDB();
			}
		}
		
		
		//The password fields are on different tabs in create/edit modes
		$pTab = false;
		if ($newAdmin = !$box['key']['id']) {
			$pTab = 'details';
		} elseif (engToBooleanArray($box['tabs']['password'], 'edit_mode', 'on')) {
			$pTab = 'password';
			exitIfNotCheckPriv('_PRIV_CHANGE_ADMIN_PASSWORD');
		}
		
		//Check the password fields
		if ($pTab) {
			if (!$values[$pTab. '/'. 'password']) {
				$box['tabs'][$pTab]['errors'][] = adminPhrase('Please enter a Password.');

			} elseif (!checkPasswordStrength($values[$pTab. '/'. 'password'])) {
				$box['tabs'][$pTab]['errors'][] = adminPhrase('The password provided is not strong enough. Please make the password longer, or try mixing in upper and lower case letters, numbers or non-alphanumeric characters.');

			} elseif (!$values[$pTab. '/'. 'password_confirm']) {
				$box['tabs'][$pTab]['errors'][] = adminPhrase('Please enter a password in both password fields.');

			} elseif ($values[$pTab. '/'. 'password'] != $values[$pTab. '/'. 'password_confirm']) {
				$box['tabs'][$pTab]['errors'][] = adminPhrase('Please ensure that the passwords you submit are identical.');
			}
		}
		
		if (engToBooleanArray($box['tabs']['permissions'], 'edit_mode', 'on')) {
			switch ($values['permissions/permissions']) {
				case 'specific_languages':
					if (!$values['permissions/specific_languages']
					 && !$values['permissions/specific_content_items']) {
						$box['tabs']['permissions']['errors'][] = adminPhrase('Please select a language.');
					}
					break;
				
				case 'specific_menu_areas':
					if (!$values['permissions/specific_menu_areas']) {
						$box['tabs']['permissions']['errors'][] = adminPhrase('Please select an area of the menu.');
					}
					break;
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
		} elseif (engToBooleanArray($box['tabs']['password'], 'edit_mode', 'on')) {
			$pTab = 'password';
			exitIfNotCheckPriv('_PRIV_CHANGE_ADMIN_PASSWORD');
		}

		if (engToBooleanArray($box['tabs']['details'], 'edit_mode', 'on')) {
			
			$details = array(
					'username' => $values['details/username'],
					'first_name' => $values['details/first_name'],
					'last_name' => $values['details/last_name'],
					'email' => $values['details/email']);
			
			$adminAuthType = getRow('admins', 'authtype', adminId());
			if ($adminAuthType == 'super') {
				$details['is_client_account'] = $values['details/is_client_account'];
			}
			
			if ($values['details/image'] && ($filepath = getPathOfUploadedFileInCacheDir($values['details/image']))) {
				$image_id = addFileToDatabase('admin', $filepath, false, true);
				$details['image_id'] = $image_id;
			
			} else {
				$details['image_id'] = $values['details/image'];
			}
			
			if ($newAdmin) {
				$details['status'] = 'active';
				$details['created_date'] = now();
			} else {
				$details['modified_date'] = now();
			}

			$box['key']['id'] = setRow('admins', $details, (int) $box['key']['id']);
			deleteUnusedImagesByUsage('admin');
		}
		
		//send email if inform by email checked
		if ($pTab
		 && $values[$pTab. '/inform_by_email']
		 && inc('zenario_email_template_manager')) {
			
			$email_details = array(
				'username' => $values['details/username'],
				'first_name' => $values['details/first_name'],
				'last_name' => $values['details/last_name'],
				'email' => $values['details/email'],
				'password' => $values[$pTab. '/password'],
				'cms_url' => absCMSDirURL());
			
			zenario_email_template_manager::sendEmailsUsingTemplate($email_details['email'], setting('notification_to_new_admin'), $email_details, array());
		}
		
		//Set a password
		if ($pTab) {
			setPasswordAdmin($box['key']['id'], $values[$pTab. '/'. 'password'], engToBoolean($values[$pTab. '/'. 'password_needs_changing']), $isPasswordReset = false);
		}


		if (engToBooleanArray($box['tabs']['permissions'], 'edit_mode', 'on')) {
			
			//Look for checkboxes set up as permission fields
			$perms = array();
			
			foreach ($box['tabs']['permissions']['fields'] as $fieldName => &$field) {
				//Ignore info tags, non-fields and anything that's not a checkbox/checkboxes.
				if (is_array($field)
				 && !empty($field['type'])
				 && !empty($field['is_admin_permission'])
				 && engToBoolean($field['is_admin_permission'])) {

					//For single checkboxes, just save one permission
					if ($field['type'] == 'checkbox') {
						//Don't let an Admin change their own management rights.
						if ($box['key']['id'] == adminId() && in($fieldName, 'perm_manage', 'perm_manage_permissions')) {
							$perms[$fieldName] = checkPriv($fieldName);
						} else {
							$perms[$fieldName] = !empty($values['permissions'. '/'. $fieldName]);
						}
							
					//For multiple checkboxes, save one permission per checkbox
					} else
					if ($field['type'] == 'checkboxes' && !empty($field['values'])) {
						foreach ($field['values'] as $valueName => &$value) {
							if ($box['key']['id'] == adminId() && in($fieldName, 'perm_manage', 'perm_manage_permissions')) {
								$perms[$valueName] = checkPriv($valueName);
							} else {
								$perms[$valueName] = in_array($valueName, explodeAndTrim($values['permissions'. '/'. $fieldName]));
							}
						}
					}
				}
			}
			
			$details = array(
				'specific_languages' => '',
				'specific_content_items' => '',
				'specific_menu_areas' => '');
			
			switch ($values['permissions/permissions']) {
				case 'specific_languages':
					$details['specific_languages'] = $values['permissions/specific_languages'];
					$details['specific_content_items'] = $values['permissions/specific_content_items'];
					break;
				
				case 'specific_menu_areas':
					$details['specific_menu_areas'] = $values['permissions/specific_menu_areas'];
					break;
			}
			saveAdminPerms($box['key']['id'], $values['permissions/permissions'], $perms, $details);

			if ($box['key']['id'] == adminId()) {
				setAdminSession(adminId());
				$this->needReload = true;
			}
		}
		
		//Save admin settings
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab) && engToBooleanArray($tab, 'edit_mode', 'on')) {
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (is_array($field)) {
						if (!arrayKey($field, 'readonly')
						 && !arrayKey($field, 'read_only')
						 && $settingName = arrayKey($field, 'admin_setting', 'name')) {
					
							//Get the value of the setting. Hidden fields should count as being empty
							if (engToBooleanArray($field, 'hidden')
							 || engToBooleanArray($field, '_was_hidden_before')) {
								$value = '';
							} else {
								$value = arrayKey($values, $tabName. '/'. $fieldName);
							}
							
							setRow('admin_settings', array('value' => $value), array('name' => $settingName, 'admin_id' => $box['key']['id']));
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
			closeFABWithFlags(['reload_organizer' => true]);
			exit;
		}
	}
}
