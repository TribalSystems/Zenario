<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
		
		ze\tuix::flagEncryptedColumns($panel, 'u', 'users');
		ze\tuix::flagEncryptedColumns($panel, 'PU', 'users');
		
		if (!$refinerName) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}
		
		if ($refinerName == 'group_members') {
			$panel['refiners']['group_members']['sql'] = "custom.`". ze\escape::sql(ze\dataset::fieldDBColumn($refinerId)). "` = 1";
		}
		
		if ($refinerName == 'smart_group') {
			ze\smartGroup::sql(
				$panel['refiners']['smart_group']['sql'],
				$panel['refiners']['smart_group']['table_join'],
				$refinerId, $list = true, 'u', 'custom');

		} else {
			unset($panel['columns']['opted_out']);
			unset($panel['columns']['opted_out_on']);
			unset($panel['columns']['opt_out_method']);
		}
		
		if (isset($panel['collection_buttons']['users_dataset'])) {
			$panel['collection_buttons']['users_dataset']['link']['path'] =
				'zenario__administration/panels/custom_datasets/item_buttons/edit_gui//'.
				ze\dataset::details('users', 'id').
				'//';
		}
	}
	
	function getEncryptedColumns($table) {
		$db = \ze::$dbL;
		$tableName = $db->prefix. $table;
		
		$cols = [];
	
		if (!isset($db->cols[$tableName])) {
			$db->checkTableDef($tableName);
		}
		
		if (!empty($db->cols[$tableName])) {
			foreach ($db->cols[$tableName] as $col) {
				if ($col->encrypted) {
					$cols[] = $col;
				}
			}
		}
		
		return $cols;
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		
		
		
		$hasUserTimersRunning = ze\module::inc('zenario_user_timers');
		
		// Add dataset ID to import and export buttons
		$dataset = ze\dataset::details('users');
		$panel['collection_buttons']['import']['admin_box']['key']['dataset'] = 
		$panel['collection_buttons']['export']['admin_box']['key']['dataset'] = 
		$panel['collection_buttons']['donwload_sample_file']['admin_box']['key']['dataset'] = 
			$dataset['id'];
		
		// If no users, hide export button
		if (count($panel['items']) <= 0) {
			$panel['collection_buttons']['export']['hidden'] = true;
		}
		
		
		
		//Add user images to each user, if they have an image
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = [];
				
			if (!empty($item['checksum'])) {
				$item['traits']['has_image'] = true;
				$img = '&usage=user&c='. $item['checksum'];
	
				$item['image'] = 'zenario/file.php?og=1'. $img;
			}
			
			if ($item['status'] == 'contact') {
				$item['traits']['is_contact'] = true;
			} elseif ($item['status'] == 'active'){
				$item['traits']['active'] = true;
			} else {
				$item['traits']['suspended'] = true;
			}
			
			if ($item['status'] == 'contact') {
				$item['row_class'] = 'contact';
			} elseif ($item['status'] == 'pending') {
				$item['row_class'] = 'user_pending';
			} elseif ($item['status'] == 'active') {
				if ($hasUserTimersRunning) {
					$timerStatus = ze\row::get(ZENARIO_USER_TIMERS_PREFIX.'user_timer_link', 'status', $item['id']);
					if ($timerStatus == 'active') {
						$item['row_class'] = 'user_active_timer';
					} else {
						$item['row_class'] = 'user_active';
					}
				} else {
					$item['row_class'] = 'user_active';
				}
			}
			
			if ($item['status'] == 'contact') {
				$item['user_type'] = 'contact';
			} else {
				$item['user_type'] = 'user';
			}
			
			if ($item['last_login']) {
				$lastLoginDate = ze\admin::formatDate($item['last_login'], ze::setting('vis_date_format_med'));
				$item['last_login'] = ze\admin::phrase('[[last_login]]', ['last_login' => $lastLoginDate]);
			} elseif ($item['status'] != 'contact') {
				$item['last_login'] = ze\admin::phrase('Never logged in');
			}
			
			// Get a users groups
			$groups = ze\user::groups($id, true, true);
			$item['groups'] = implode(', ', $groups);
			
			//$firstGroupMessage = '';
			//$counter = 0;
			//foreach ($groups as $id => &$value) {
			//	$value = $groupNames[$id];
			//	if (++$counter == 1) {
			//		$firstGroupMessage .= $value;
			//	}
			//}
			//if ($firstGroupMessage && count($groups) > 1) {
			//	$firstGroupMessage .= ' and ' . (count($groups) - 1) . ' other groups';
			//}
			//$item['first_group'] = $firstGroupMessage;
			//$item['groups'] = implode(', ', $groups);
			
			//if ($groups) {
			//	$item['readable_groups'] = ze\admin::phrase('Groups: [[groups]]', ['groups' => $item['groups']]);
			//} else {
			//	$item['readable_groups'] = ze\admin::phrase('Groups: None');
			//}
			
		}
	
		//Set a title
		if ($refinerName == 'group_members') {
			$groupDetails = zenario_users::getGroupDetails($refinerId);
				
			$panel['title'] = ze\admin::phrase('Members of the Group "[[label]]"', $groupDetails);
			$panel['no_items_message'] = ze\admin::phrase('This Group has no members.');
			$panel['item_buttons']['remove_users_from_this_group']['ajax']['confirm']['message'] = str_replace(['<<', '>>'], ['[[', ']]'], ze\admin::phrase('Are you sure you wish to remove the user/contact "<<identifier>>" from the group "[[label]]"?', ['label' => $groupDetails['label']]));
			ze\lang::applyMergeFields($panel['item_buttons']['remove_users_from_this_group']['ajax']['confirm']['multiple_select_message'], ['label' => $groupDetails['label'], 'item_count' => '[[item_count]]']);
		}
	
		//Don't show the "Create User" or "Delete User" or "Import" buttons on a refiner
		if ($refinerName) {
			if($refinerName == 'suspended_users'){
				unset($panel['collection_buttons']['add']);
				unset($panel['collection_buttons']['import_dropdown']);
				unset($panel['quick_filter_buttons']);
				
			}else{
				unset($panel['collection_buttons']['add']);
				if ($refinerName == 'group_members' || $refinerName == 'smart_group') {
					//unset($panel['item_buttons']['delete']);
				} else {
					unset($panel['item_buttons']['delete']);
				}
				unset($panel['collection_buttons']['import_dropdown']);
			}
			
		}
	
		if ($refinerName == 'group_members') {
			unset($panel['item_buttons']['add_users_to_groups']);
			unset($panel['item_buttons']['remove_users_from_groups']);
		}
		
		switch($refinerName){
			case 'suspended_users':
				$panel['title'] = ze\admin::phrase('Suspended users');
				$panel['no_items_message'] = 'No suspended users.';
				unset($panel['trash']);
				unset($panel['quick_filter_buttons']['all']);
				unset($panel['quick_filter_buttons']['pending']);
				unset($panel['quick_filter_buttons']['active']);
				unset($panel['quick_filter_buttons']['contact']);
				
				break;
			
			case 'active_users':
				$panel['title'] = ze\admin::phrase('Active users');
				$panel['no_items_message'] = 'No active users.';
				unset($panel['trash']);
				unset($panel['quick_filter_buttons']['all']);
				unset($panel['quick_filter_buttons']['pending']);
				unset($panel['quick_filter_buttons']['active']);
				unset($panel['quick_filter_buttons']['contact']);
				
				break;
		}
		
		
		//If there are no results
		if (empty($panel['items'])) {
			//Find any encrypted/hashed columns...
			$cols = array_merge(self::getEncryptedColumns('users'), self::getEncryptedColumns('users_custom_data'));
	
	
			if (!empty($cols)) {
				//...and override the "no items in search" message.
				$noResultsMessage = 'There are no users/contacts matching your search. Please note: ';
				
				
				$encryptedColumns = $hashedColumns = [];
				foreach ($cols as $col) {
					//Give nice names to any encrypted or hashed columns
					if ($col->hashed == true) {
						$hashedColumns[] = ucwords(str_replace('_', ' ', $col->col));
					} else {
						$encryptedColumns[] = ucwords(str_replace('_', ' ', $col->col));
					}
				}
				
				if (!empty($encryptedColumns)) {
					$encryptedColumnsMessage = ze\admin::phrase("\n\n". 'The column(s) ' . implode(', ', $encryptedColumns) . ' are encrypted and cannot be searched.');
					$noResultsMessage .= $encryptedColumnsMessage;
				}
				
				if (!empty($hashedColumns)) {
					$hashedColumnsMessage = ze\admin::phrase("\n\n". 'The column(s) ' . implode(', ', $hashedColumns) . ' are encrypted and hashed. They can only be searched with exact matches.');
					$noResultsMessage .= $hashedColumnsMessage;
				}
				
				$panel['no_items_in_search_message'] = $noResultsMessage;
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (($_POST['delete_user'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $id) {
				ze\userAdm::delete($id);
			}
		
		} elseif (($_POST['remove_users_from_this_group'] ?? false) && ($_POST['refiner__group_members'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $id) {
				ze\user::addToGroup($id, ($_POST['refiner__group_members'] ?? false), $remove = true);
			}
	
		} elseif (($_POST['add_user_to_this_group'] ?? false) && ($_POST['refiner__group_members'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $userId) {
				ze\user::addToGroup($userId, ($_POST['refiner__group_members'] ?? false));
			}
		} elseif (($_POST['suspend_user'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			foreach (explode(',', $ids) as $id) {
				static::suspendUser($id);
			}
			
			echo '<!--Toast_Type:success-->';
			echo '<!--Toast_Message:'. ze\escape::hyp(ze\admin::phrase('Item saved, but your filter prevents it from appearing')). '-->';
			
			
		//Set a new avatar for a User/Users
		} elseif (($_POST['upload_image'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			zenario_users::uploadUserImage($ids);
		//Remove the image for each user
		} elseif (($_POST['delete_image'] ?? false) && ze\priv::check('_PRIV_EDIT_USER')) {
			zenario_users::deleteUserImage($ids);
		}
	}
}
