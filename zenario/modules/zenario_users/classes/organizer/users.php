<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
		
		if (!$refinerName) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
		
		if ($refinerName == 'group_members') {
			$panel['refiners']['group_members']['sql'] = "custom.`". sqlEscape(datasetFieldDBColumn($refinerId)). "` = 1";
		}
		
		if ($refinerName == 'smart_group') {
			$joins = array();
				
			if (($sg = getRow('smart_groups', array('id', 'name' , 'values'), $refinerId))
			&& (advancedSearchSQL($panel['refiners']['smart_group']['sql'], $joins, 'zenario__users/panels/users', $sg['values'], $sg['id']))) {
		
				foreach ($joins as $join => $dummy) {
					$panel['refiners']['smart_group']['table_join'] .= "
				". $join;
				}
		
				$panel['title'] = adminPhrase('Users in Smart Group "[[name]]"', $sg);
					
			} else {
				$panel['refiners']['smart_group']['sql'] = "FALSE";
		
				$panel['title'] = adminPhrase('Users in Smart Group');
				$panel['no_items_message'] = adminPhrase('There is a problem with this advanced search and it cannot be displayed.');
			}
				
			unset($panel['advanced_search']);
		} else {
			unset($panel['columns']['opted_out']);
			unset($panel['columns']['opted_out_on']);
			unset($panel['columns']['opt_out_method']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		// Add dataset ID to import button
		$dataset = getDatasetDetails('users');
		$panel['collection_buttons']['import']['admin_box']['key']['dataset'] = 
		$panel['collection_buttons']['donwload_sample_file']['admin_box']['key']['dataset'] = 
			$dataset['id'];
		
		//Change the enum option for user type if this site is a hub or is connected to a hub
		if (zenario_users::validateUserSyncSiteConfig()) {
			if (zenario_users::thisIsHub()) {
				unset($panel['columns']['type']['values']['from_hub']);
				$panel['columns']['type']['values']['local'] = adminPhrase('Hub user');
			} else {
				$panel['columns']['type']['values']['local'] = adminPhrase('Satellite user');
			}
		}
		
		//Add user images to each user, if they have an image
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
				
			if (!empty($item['checksum'])) {
				$item['traits']['has_image'] = true;
				$img = '&usage=user&c='. $item['checksum'];
	
				$item['image'] = 'zenario/file.php?sk=1'. $img;
				$item['list_image'] = 'zenario/file.php?skl=1'. $img;
			}
			
			if ($item['status'] == 'contact') {
				$item['traits']['is_contact'] = true;
			}
			
			$item['cell_css_classes'] = array();
			switch (arrayKey($item,'status')){
				case 'pending':
					$item['cell_css_classes']['status'] = 'orange';
					break;
				case 'active':
					$item['cell_css_classes']['status'] = 'blue';
					break;
				case 'suspended':
					$item['cell_css_classes']['status'] = 'brown';
					break;
				default:
					break;
			}
			
			if ($item['status'] == 'contact') {
				$item['row_css_class'] = 'contact';
			} elseif ($item['status'] == 'pending') {
				$item['row_css_class'] = 'user_pending';
			} elseif ($item['status'] == 'active') {
				if (inc('zenario_user_timers')) {
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
		}
	
		//Set a title
		if ($refinerName == 'group_members') {
			$groupDetails = zenario_users::getGroupDetails($refinerId);
				
			$panel['title'] = adminPhrase('Members of the Group "[[name]]"', $groupDetails);
			$panel['no_items_message'] = adminPhrase('This Group has no members.');
				
			$panel['item_buttons']['remove_users_from_group']['tooltip'] = adminPhrase('Remove User from the Group "[[name]]"', $groupDetails);
			$panel['item_buttons']['remove_users_from_group']['multiple_select_tooltip'] = adminPhrase('Remove Users from the Group "[[name]]"', $groupDetails);
			$panel['item_buttons']['remove_users_from_group']['ajax']['confirm']['message'] = adminPhrase('Are you sure you wish to remove the User "[[screen_name]]" from the Group "[[name]]"?', $groupDetails);
			$panel['item_buttons']['remove_users_from_group']['ajax']['confirm']['multiple_select_message'] = adminPhrase('Are you sure you wish to remove the selected Users from the Group "[[name]]"?', $groupDetails);
		}
	
		//Don't show the "Create User" or "Delete User" button on a refiner
		if ($refinerName) {
			unset($panel['collection_buttons']['add']);
			unset($panel['item_buttons']['delete']);
		}
	
		if ($refinerName != 'group_members') {
			unset($panel['collection_buttons']['add_user_to_group']);
		}
		
		if ($refinerName == 'smart_group') {
			//Smart group opted out functionally taken out 18/02/2014
			//only commenting outing the code incase we decide to put it back in
			/*foreach ($panel['items'] as &$item) {
				if ($item['opted_out']) {
					if (isset($item['row_css_class'])) {
						$item['row_css_class'] .= ' orange_line';
					} else {
						$item['row_css_class'] = 'orange_line';
					}
				}
			}*/
		}
		
		switch($refinerName){
			case 'suspended_users':
				$panel['title'] = adminPhrase('Suspended users');
				$panel['no_items_message'] = 'No suspended users.';
				unset($panel['trash']);
				
				break;
		}
		foreach ($panel['items'] as $K=>&$item) {
			if (count($userData = getUserDetails($K))){
				if($userData['status']!= 'contact') {
					if ($userData['status']=='active'){
						$panel['items'][$K]['traits']['active']=true;
					} else {
						$panel['items'][$K]['traits']['suspended']=true;
					}
				}
			}
		}
					
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (post('delete_user') && checkPriv('_PRIV_DELETE_USER')) {
			foreach (explode(',', $ids) as $id) {
				deleteUser($id);
			}
	
		} elseif (post('remove_users_from_group') && checkPriv('_PRIV_MANAGE_GROUP_MEMBERSHIP')) {
			foreach (explode(',', $ids) as $id) {
				addUserToGroup($id, $refinerId, $remove = true);
			}
	
		} elseif (post('add_users_to_group') && checkPriv('_PRIV_MANAGE_GROUP_MEMBERSHIP')) {
			foreach (explode(',', $ids) as $userId) {
				foreach (explode(',', $ids2) as $groupId) {
					addUserToGroup($userId, $groupId);
				}
			}
	
		} elseif (post('add_user_to_group') && post('refiner__group_members') && checkPriv('_PRIV_MANAGE_GROUP_MEMBERSHIP')) {
			foreach (explode(',', $ids) as $userId) {
				addUserToGroup($userId, post('refiner__group_members'));
			}
		
		//Set a new avatar for a User/Users
		} elseif (post('upload_image') && checkPriv('_PRIV_EDIT_USER')) {
			
			//Try to add the uploaded image to the database
			if ($imageId = addFileToDatabase('user', $_FILES['Filedata']['tmp_name'], $_FILES['Filedata']['name'], true)) {
				//Add image to each user
				foreach (explode(',', $ids) as $id) {
					updateRow('users', array('image_id' => $imageId), $id);
				}
				
				deleteUnusedImagesByUsage('user');
				
				echo 1;
				return null;
			} else {
				if($imageId) echo $error. "\n";
				return false;
			}
		
		} elseif (post('delete_image') && checkPriv('_PRIV_EDIT_USER')) {
			//Remove the image for each user
			foreach (explode(',', $ids) as $id) {
				updateRow('users', array('image_id' => 0), $id);
			}
			
			deleteUnusedImagesByUsage('user');
		
		} elseif (post('opt_out') && checkPriv('_PRIV_MANAGE_GROUP_MEMBERSHIP') && $refinerName == 'smart_group' && $refinerId) {
			foreach (explode(',', $ids) as $id) {
				if (!hasOptedOutOfSmartGroup($refinerId, $id)) {
					optOutOfSmartGroup($refinerId, $id, 'admin');
				}
			}
		
		} elseif (post('remove_opt_out') && checkPriv('_PRIV_MANAGE_GROUP_MEMBERSHIP') && $refinerName == 'smart_group' && $refinerId) {
			foreach (explode(',', $ids) as $id) {
				cancelOptOutOfSmartGroup($refinerId, $id);
			}
		}
	}
}
