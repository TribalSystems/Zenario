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

class zenario_users__admin_boxes__mass_group_membership extends module_base_class {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$ids = explodeAndTrim($box['key']['id'], true);
		
		if (!$box['key']['count'] = count($ids)) {
			echo adminPhrase('No users selected!');
			exit;
		
		} else if ($box['key']['count'] > 1) {
			if ($box['key']['remove']) {
				$box['save_button_message'] = adminPhrase('Remove users from group(s)');
				$box['title'] = adminPhrase('Removing [[count]] users from group(s)', $box['key']);
				$fields['groups/desc']['snippet']['html'] =
					adminPhrase('Users will be removed from any group that you mark with a tick. They will not be added to any groups.');
			} else {
				$box['save_button_message'] = adminPhrase('Add users to group(s)');
				$box['title'] = adminPhrase('Adding [[count]] users to group(s)', $box['key']);
				$fields['groups/desc']['snippet']['html'] =
					adminPhrase('Users will be added to every group that you mark with a tick. They will not be removed from any groups.');
			}
		} else {
			$box['key']['identifier'] = getUserIdentifier($box['key']['id']);
			if ($box['key']['remove']) {
				$box['save_button_message'] = adminPhrase('Remove user from group(s)');
				$box['title'] = adminPhrase('Removing the user "[[identifier]]" from group(s)', $box['key']);
				$fields['groups/desc']['snippet']['html'] =
					htmlspecialchars(adminPhrase('"[[identifier]]" will be removed from any group that you mark with a tick. They will not be added to any groups.', $box['key']));
			} else {
				$box['save_button_message'] = adminPhrase('Add user to group(s)');
				$box['title'] = adminPhrase('Adding the user "[[identifier]]" to group(s)', $box['key']);
				$fields['groups/desc']['snippet']['html'] =
					htmlspecialchars(adminPhrase('"[[identifier]]" will be added to every group that you mark with a tick. They will not be removed from any groups.', $box['key']));
			}
		}
		
		//Populate the list of groups
		$inGroupCount = 0;
		$totalGroupCount = 0;
		$pickedItems = array();
		$fields['groups/groups']['indeterminates'] = array();
		$fields['groups/groups']['values'] = listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
		
		foreach ($fields['groups/groups']['values'] as $id => &$v) {
			//Note: these are multi-checkboxes fields. I want to show the tabs, but I don't want
			//people to be able to select them
			if (empty($v['parent']) || empty($v['db_column'])) {
				$v['readonly'] =
				$v['disabled'] = true;
				$v['style'] = 'display: none;';
			
			} else {
				//Look up how many users are in each group
				$count = selectCount('users_custom_data', [$v['db_column'] => 1, 'user_id' => $ids]);
				
				if ($count != 0) {
					++$inGroupCount;
				}
				++$totalGroupCount;
				
				//If some are in a group and some are not, flag it as indeterminate
				if ($count != 0
				 && $count != $box['key']['count']) {
					$fields['groups/groups']['indeterminates'][$id] = true;
				}
				
				if ($box['key']['remove']) {
					//When removing, if some users are in a group then it should start unchecked but be clickable.
					//Otherwise it should be unchecked and unclickable.
					if ($count == 0) {
						$v['readonly'] =
						$v['disabled'] = true;
					}
				} else {
					//When adding, if some users are not a group then it should start unchecked but be clickable.
					//Otherwise it should be checked, and unclickable.
					if ($count == $box['key']['count']) {
						$v['readonly'] =
						$v['disabled'] = true;
						$pickedItems[] = $id;
					}
				}
			}
		}
		
		$values['groups/groups'] = implode(',', $pickedItems);
		
		if ($box['key']['remove']) {
			if ($inGroupCount == 1) {
				$fields['groups/groups']['label'] = adminPhrase('Remove from group:');
			} else {
				$fields['groups/groups']['label'] = adminPhrase('Remove from groups:');
			}
		} else {
			if ($totalGroupCount - $inGroupCount == 1) {
				$fields['groups/groups']['label'] = adminPhrase('Add to group:');
			} else {
				$fields['groups/groups']['label'] = adminPhrase('Add to groups:');
			}
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$initial = array();
		$current = array();
		
		if ($fields['groups/groups']['value']) {
			$initial = explode(',', $fields['groups/groups']['value']);
		}
		if ($fields['groups/groups']['current_value']) {
			$current = explode(',', $fields['groups/groups']['current_value']);
		}
		
		$diff = array_diff($current, $initial);
		
		if ($box['confirm']['show'] = (bool) ($box['key']['diff'] = count($diff))) {
			if ($box['key']['remove']) {
				if ($box['key']['count'] > 1) {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to remove [[count]] users from [[diff]] groups?', $box['key']);
					} else {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to remove [[count]] users from 1 group?', $box['key']);
					}
				} else {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to remove the user "[[identifier]]" from [[diff]] groups?', $box['key']);
					} else {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to remove the user "[[identifier]]" from 1 group?', $box['key']);
					}
				}
				$box['confirm']['button_message'] = adminPhrase('Remove');
			
			} else {
				if ($box['key']['count'] > 1) {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to add [[count]] users to [[diff]] groups?', $box['key']);
				
					} else {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to add [[count]] users to 1 group?', $box['key']);
					}
				} else {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to add the user "[[identifier]]" to [[diff]] groups?', $box['key']);
				
					} else {
						$box['confirm']['message'] = adminPhrase('Are you sure you wish to add the user "[[identifier]]" to 1 group?', $box['key']);
					}
				}
				$box['confirm']['button_message'] = adminPhrase('Add');
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$initial = array();
		$current = array();
		
		if ($fields['groups/groups']['value']) {
			$initial = explode(',', $fields['groups/groups']['value']);
		}
		if ($fields['groups/groups']['current_value']) {
			$current = explode(',', $fields['groups/groups']['current_value']);
		}
		
		$diff = array_diff($current, $initial);
		
		if (checkPriv('_PRIV_EDIT_USER')) {
			foreach (explode(',', $box['key']['id']) as $userId) {
				if ($userId) {
					foreach ($diff as $groupId) {
						addUserToGroup($userId, $groupId, $box['key']['remove']);
					}
				}
			}
		}
	}
}