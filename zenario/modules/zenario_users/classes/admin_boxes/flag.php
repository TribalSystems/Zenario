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

class zenario_users__admin_boxes__flag extends ze\moduleBaseClass {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$ids = ze\ray::explodeAndTrim($box['key']['id'], true);
		
		if (!$box['key']['count'] = count($ids)) {
			echo ze\admin::phrase('No users selected!');
			exit;
		
		} else if ($box['key']['count'] > 1) {
			if ($box['key']['remove']) {
				$box['save_button_message'] = ze\admin::phrase('Remove flag(s) from user');
				$box['title'] = ze\admin::phrase('Removing flag(s) from [[count]] user(s)', $box['key']);
				$fields['flags/desc']['snippet']['html'] =
					ze\admin::phrase('Flags will be removed from user.');
			} else {
				$box['save_button_message'] = ze\admin::phrase('Add a flag to user');
				$box['title'] = ze\admin::phrase('Adding flags to [[count]] users(s)', $box['key']);
				$fields['flags/desc']['snippet']['html'] =
					ze\admin::phrase('Flags will be added to users.');
			}
		} else {
			$box['key']['identifier'] = ze\user::identifier($box['key']['id']);
			if ($box['key']['remove']) {
				$box['save_button_message'] = ze\admin::phrase('Remove flag(s) from user');
				$box['title'] = ze\admin::phrase('Removing flag(s) from the user "[[identifier]]"', $box['key']);
				$fields['flags/desc']['snippet']['html'] =
					htmlspecialchars(ze\admin::phrase('Flags will be removed from the user "[[identifier]]"', $box['key']));
			} else {
				$box['save_button_message'] = ze\admin::phrase('Flag user');
				$box['title'] = ze\admin::phrase('Adding flag(s) to the user "[[identifier]]"', $box['key']);
				$fields['flags/desc']['snippet']['html'] =
					htmlspecialchars(ze\admin::phrase('Add flag(s) to the user "[[identifier]]"', $box['key']));
			}
		}
		
		//Populate the list of flags
		$inflagCount = 0;
		$totalflagCount = 0;
		$pickedItems = [];
		$fields['flags/flags']['indeterminates'] = [];
		
		$fields['flags/flags']['values'] = ze\datasetAdm::listCustomFields('users', $flat = false, 'checkbox', $customOnly = true);

		
		foreach ($fields['flags/flags']['values'] as $id => &$v) {
			//Note: these are multi-checkboxes fields. I want to show the tabs, but I don't want
			//people to be able to select them
			
			if (empty($v['db_column'])) {
				$v['readonly'] =
				$v['disabled'] = true;
				//$v['style'] = 'display: none;';
			} else {
				
				
				//Look up how many users are in each flag
				$count = ze\row::count('users_custom_data', [$v['db_column'] => 1, 'user_id' => $ids]);
				
				
				if ($count != 0) {
					++$inflagCount;
				}
				++$totalflagCount;
				
				//If some are in a flag and some are not, flag it as indeterminate
				if ($count != 0
				 && $count != $box['key']['count']) {
					$fields['flags/flags']['indeterminates'][$id] = true;
				}
				
				if ($box['key']['remove']) {
					//When removing, if some users are in a flag then it should start unchecked but be clickable.
					//Otherwise it should be unchecked and unclickable.
					if ($count == 0) {
						$v['readonly'] =
						$v['disabled'] = true;
					}
				} else {
					//When adding, if some users are not a flag then it should start unchecked but be clickable.
					//Otherwise it should be checked, and unclickable.
					if ($count == $box['key']['count']) {
						$v['readonly'] =
						$v['disabled'] = true;
						$pickedItems[] = $id;
					}
				}
			}
		}
		
		$values['flags/flags'] = implode(',', $pickedItems);
		
		
		if ($box['key']['remove']) {
			if ($inflagCount == 1) {
				$fields['flags/flags']['label'] = ze\admin::phrase('Remove flag:');
			} else {
				$fields['flags/flags']['label'] = ze\admin::phrase('Remove flags:');
			}
		} else {
			if ($totalflagCount - $inflagCount == 1) {
				$fields['flags/flags']['label'] = ze\admin::phrase('Flag:');
			} else {
				$fields['flags/flags']['label'] = ze\admin::phrase('Flags:');
			}
		}
		
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$initial = [];
		$current = [];
		
		if ($fields['flags/flags']['value']) {
			$initial = explode(',', $fields['flags/flags']['value']);
		}
		if ($fields['flags/flags']['current_value']) {
			$current = explode(',', $fields['flags/flags']['current_value']);
		}
		
		$diff = array_diff($current, $initial);
		
		if ($box['confirm']['show'] = (bool) ($box['key']['diff'] = count($diff))) {
			if ($box['key']['remove']) {
				if ($box['key']['count'] > 1) {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to remove [[count]] flags?', $box['key']);
					} else {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to remove 1 flag from [[count]] users?', $box['key']);
					}
				} else {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to remove [[diff]] flags from the user "[[identifier]]"?', $box['key']);
					} else {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to remove 1 flag from the user "[[identifier]]"?', $box['key']);
					}
				}
				$box['confirm']['button_message'] = ze\admin::phrase('Remove');
			
			} else {
				if ($box['key']['count'] > 1) {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to add [[diff]] flags to [[count]] users?', $box['key']);
				
					} else {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to add  1 flag to [[count]] users?', $box['key']);
					}
				} else {
					if ($box['key']['diff'] > 1) {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to add [[diff]] flags to the user "[[identifier]]"?', $box['key']);
				
					} else {
						$box['confirm']['message'] = ze\admin::phrase('Are you sure you wish to add 1 flag to the user "[[identifier]]"?', $box['key']);
					}
				}
				$box['confirm']['button_message'] = ze\admin::phrase('Flag');
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$initial = [];
		$current = [];
		
		if ($fields['flags/flags']['value']) {
			$initial = explode(',', $fields['flags/flags']['value']);
		}
		if ($fields['flags/flags']['current_value']) {
			$current = explode(',', $fields['flags/flags']['current_value']);
		}
		
		$diff = array_diff($current, $initial);
		
		if (ze\priv::check('_PRIV_EDIT_USER')) {
			foreach (explode(',', $box['key']['id']) as $userId) {
				if ($userId) {
					foreach ($diff as $flagId) {
						ze\user::addToGroup($userId, $flagId, $box['key']['remove']);
					}
				}
			}
		}
	}
}