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

class zenario_users__organizer__users extends zenario_users {
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		flagEncryptedColumnsInOrganizer($panel, 'u', 'users');
		flagEncryptedColumnsInOrganizer($panel, 'PU', 'users');
		
		if (!$refinerName) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
		
		if ($refinerName == 'group_members') {
			$panel['refiners']['group_members']['sql'] = "custom.`". sqlEscape(datasetFieldDBColumn($refinerId)). "` = 1";
		}
		
		if ($refinerName == 'smart_group') {
			smartGroupSQL(
				$panel['refiners']['smart_group']['sql'],
				$panel['refiners']['smart_group']['table_join'],
				$refinerId, $list = true, 'u', 'custom');

		} else {
			unset($panel['columns']['opted_out']);
			unset($panel['columns']['opted_out_on']);
			unset($panel['columns']['opt_out_method']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		
		
		
		$hasUserTimersRunning = inc('zenario_user_timers');
		
		// Add dataset ID to import and export buttons
		$dataset = getDatasetDetails('users');
		$panel['collection_buttons']['import']['admin_box']['key']['dataset'] = 
		$panel['collection_buttons']['export']['admin_box']['key']['dataset'] = 
		$panel['collection_buttons']['donwload_sample_file']['admin_box']['key']['dataset'] = 
			$dataset['id'];
		
		// If no users, hide export button
		if (count($panel['items']) <= 0) {
			$panel['collection_buttons']['export']['hidden'] = true;
		}
		
		//Change the enum option for user type if this site is a hub or is connected to a hub
		if (zenario_users::validateUserSyncSiteConfig()) {
			if (zenario_users::thisIsHub()) {
				unset($panel['columns']['type']['values']['from_hub']);
				$panel['columns']['type']['values']['local'] = adminPhrase('Hub user');
			} else {
				$panel['columns']['type']['values']['local'] = adminPhrase('Satellite user');
			}
		}
		
		// Get group labels
		$groupNames = getRowsArray('custom_dataset_fields', 'label', array('type' => 'group', 'is_system_field' => 0));
		
		
		//Add user images to each user, if they have an image
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
				
			if (!empty($item['checksum'])) {
				$item['traits']['has_image'] = true;
				$img = '&usage=user&c='. $item['checksum'];
	
				$item['image'] = 'zenario/file.php?og=1'. $img;
				$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
			}
			
			if ($item['status'] == 'contact') {
				$item['traits']['is_contact'] = true;
			} elseif ($item['status'] == 'active'){
				$item['traits']['active'] = true;
			} else {
				$item['traits']['suspended'] = true;
			}
			
			if ($item['status'] == 'contact') {
				$item['row_css_class'] = 'contact';
			} elseif ($item['status'] == 'pending') {
				$item['row_css_class'] = 'user_pending';
			} elseif ($item['status'] == 'active') {
				if ($hasUserTimersRunning) {
					$timerStatus = getRow(ZENARIO_USER_TIMERS_PREFIX.'user_timer_link', 'status', $item['id']);
					if ($timerStatus == 'active') {
						$item['row_css_class'] = 'user_active_timer';
					} else {
						$item['row_css_class'] = 'user_active';
					}
				} else {
					$item['row_css_class'] = 'user_active';
				}
			}
			
			if ($item['status'] == 'contact') {
				$item['user_type'] = 'contact';
			} else {
				$item['user_type'] = 'user';
			}
			
			if ($item['last_login']) {
				$item['last_login'] = formatDateNicely($item['last_login'], setting('vis_date_format_med'));
				$item['readable_last_login'] = adminPhrase('Last login: [[last_login]]', array('last_login' => $item['last_login']));
			} elseif ($item['status'] != 'contact') {
				$item['readable_last_login'] = adminPhrase('Last login: Never');
			}
			
			$item['readable_name'] = implode(' ', array_filter(array($item['salutation'], $item['first_name'], $item['last_name'])));
			
			// Get a users groups
			$groups = getUserGroups($id);
			$firstGroupMessage = '';
			$counter = 0;
			foreach ($groups as $id => &$value) {
				$value = $groupNames[$id];
				if (++$counter == 1) {
					$firstGroupMessage .= $value;
				}
			}
			if ($firstGroupMessage && count($groups) > 1) {
				$firstGroupMessage .= ' and ' . (count($groups) - 1) . ' other groups';
			}
			$item['first_group'] = $firstGroupMessage;
			$item['groups'] = implode(', ', $groups);
			
			if ($groups) {
				$item['readable_groups'] = adminPhrase('Groups: [[groups]]', array('groups' => $item['groups']));
			} else {
				$item['readable_groups'] = adminPhrase('Groups: None');
			}
			
		}
	
		//Set a title
		if ($refinerName == 'group_members') {
			$groupDetails = zenario_users::getGroupDetails($refinerId);
				
			$panel['title'] = adminPhrase('Members of the Group "[[label]]"', $groupDetails);
			$panel['no_items_message'] = adminPhrase('This Group has no members.');
			$panel['item_buttons']['remove_users_from_this_group']['ajax']['confirm']['message'] = adminPhrase('Are you sure you wish to remove the User "[[identifier]]" from the Group "[[label]]"?', $groupDetails);
			$panel['item_buttons']['remove_users_from_this_group']['ajax']['confirm']['multiple_select_message'] = adminPhrase('Are you sure you wish to remove the selected Users from the Group "[[label]]"?', $groupDetails);
		}
	
		//Don't show the "Create User" or "Delete User" or "Import" buttons on a refiner
		if ($refinerName) {
			if($refinerName == 'suspended_users'){
				unset($panel['collection_buttons']['add']);
				unset($panel['collection_buttons']['import_dropdown']);
				unset($panel['quick_filter_buttons']);
				
			}else{
				unset($panel['collection_buttons']['add']);
				unset($panel['item_buttons']['delete']);
				unset($panel['collection_buttons']['import_dropdown']);
			}
			
		}
	
		if ($refinerName == 'group_members') {
			unset($panel['item_buttons']['add_users_to_groups']);
			unset($panel['item_buttons']['remove_users_from_groups']);
		}
		
		switch($refinerName){
			case 'suspended_users':
				$panel['title'] = adminPhrase('Suspended users');
				$panel['no_items_message'] = 'No suspended users.';
				unset($panel['trash']);
				unset($panel['quick_filter_buttons']['all']);
				unset($panel['quick_filter_buttons']['pending']);
				unset($panel['quick_filter_buttons']['active']);
				unset($panel['quick_filter_buttons']['contact']);
				
				break;
			
			case 'active_users':
				$panel['title'] = adminPhrase('Active users');
				$panel['no_items_message'] = 'No active users.';
				unset($panel['trash']);
				unset($panel['quick_filter_buttons']['all']);
				unset($panel['quick_filter_buttons']['pending']);
				unset($panel['quick_filter_buttons']['active']);
				unset($panel['quick_filter_buttons']['contact']);
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (post('delete_user') && checkPriv('_PRIV_DELETE_USER')) {
			foreach (explode(',', $ids) as $id) {
				deleteUser($id);
			}
	
		} elseif (post('remove_users_from_this_group') && post('refiner__group_members') && checkPriv('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $id) {
				addUserToGroup($id, post('refiner__group_members'), $remove = true);
			}
	
		} elseif (post('add_user_to_this_group') && post('refiner__group_members') && checkPriv('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $userId) {
				addUserToGroup($userId, post('refiner__group_members'));
			}
		
		//Set a new avatar for a User/Users
		} elseif (post('upload_image') && checkPriv('_PRIV_EDIT_USER')) {
			zenario_users::uploadUserImage($ids);
		//Remove the image for each user
		} elseif (post('delete_image') && checkPriv('_PRIV_EDIT_USER')) {
			zenario_users::deleteUserImage($ids);
		}
	}
}
