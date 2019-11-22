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

class zenario_users__admin_boxes__content_privacy extends zenario_users {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$fields['privacy/group_ids']['values'] = ze\datasetAdm::getGroupPickerCheckboxesForFAB();
		$fields['privacy/smart_group_id']['values'] = ze\contentAdm::getListOfSmartGroupsWithCounts();
		
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = ze\module::prefix('zenario_organization_manager')) {
			$fields['privacy/role_ids']['values'] = ze\row::getValues($ZENARIO_ORGANIZATION_MANAGER_PREFIX. 'user_location_roles', 'name', [], 'name');
		} else {
			$fields['privacy/role_ids']['hidden'] =
			$fields['privacy/privacy']['values']['with_role']['hidden'] = true;
		}
		
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = [];
		$equivId = $cType = false;
		
		if (($_REQUEST['equivId'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['equivId'] ?? false);
		
		} elseif (($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)) {
			$box['key']['id'] = ($_REQUEST['cType'] ?? false). '_'. ($_REQUEST['cID'] ?? false);
		}
		
		$theseValues =
		$lastValues = false;
		$combinedValues = true;
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explode(',', $box['key']['id']) as $tagId) {
			if (ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
					
					//Attempt to see if all of the chosen items have the same values
					
					//If we've already failed trying to match values, stop trying
					if ($combinedValues !== false) {
						
						//Look up the values for this content item
						$sql = "
							SELECT
								tc.privacy, tc.smart_group_id,
								tcp.module_class_name, tcp.method_name, tcp.param_1, tcp.param_2
							FROM ". DB_PREFIX. "translation_chains AS tc
							LEFT JOIN ". DB_PREFIX. "translation_chain_privacy AS tcp
							   ON tc.equiv_id = tcp.equiv_id
							  AND tc.type = tcp.content_type
							WHERE tc.equiv_id = ". (int) $equivId. "
							  AND tc.type = '". ze\escape::sql($cType). "'";
						
						if ($chain = ze\sql::fetchAssoc($sql)) {
							
							if ($chain['privacy'] == 'group_members') {
								$chain['group_ids'] =
									ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]), true);
							} else {
								$chain['group_ids'] = '';
							}
							
							if ($chain['privacy'] == 'with_role') {
								$chain['role_ids'] =
									ze\escape::in(ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]), true);
							} else {
								$chain['role_ids'] = '';
							}
							
							$theseValues = json_encode($chain);
							
							//If the values were the same as last time, combine them
							if ($lastValues === false
							 || $lastValues == $theseValues) {
								$lastValues = $theseValues;
								$combinedValues = $chain;
							
							//If they're not, we can't combine them, and will need to show the settings as empty
							} else {
								$combinedValues = false;
							}
						}
					}
				}
			}
		}
		
		if (empty($tagIds)) {
			exit;
		} else {
			$box['key']['id'] = implode(',', $tagIds);
		}
		
		//If all the values match, display them!
		if (!empty($combinedValues) && is_array($combinedValues)) {
			$values['privacy/privacy'] = $combinedValues['privacy'];
			$values['privacy/group_ids'] = $combinedValues['group_ids'];
			$values['privacy/role_ids'] = $combinedValues['role_ids'];
			$values['privacy/smart_group_id'] = $combinedValues['smart_group_id'];
			$values['privacy/module_class_name'] = $combinedValues['module_class_name'];
			$values['privacy/method_name'] = $combinedValues['method_name'];
			$values['privacy/param_1'] = $combinedValues['param_1'];
			$values['privacy/param_2'] = $combinedValues['param_2'];
		}
		
		$numLanguages = ze\lang::count();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the permissions of all content items in [[count]] translation chains.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing permissions for [[count]] translation chains',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing permissions for the content item "[[tag]]" and its translations',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					ze\admin::phrase('This will update the permissions of [[count]] content items.',
						['count' => $total]);
				
				$box['title'] =
					ze\admin::phrase('Changing permissions for [[count]] content items',
						['count' => $total]);
			} else {
				$box['title'] =
					ze\admin::phrase('Changing permissions for the content item "[[tag]]"',
						['tag' => ze\content::formatTag($equivId, $cType)]);
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				ze\admin::phrase('The content items in all selected translation chains will be set to the permissions you selected.');
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)) {
					
			$tagIds = explode(',', $box['key']['id']);
			foreach ($tagIds as $tagId) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					if ($values['privacy/privacy'] != 'public' && ($specialPage = ze\content::isSpecialPage($cID, $cType))) {
						$cID = $cType = false;
						if ($specialPage == 'zenario_login'
						 || $specialPage == 'zenario_registration'
						 || $specialPage == 'zenario_password_reminder'
						 || $specialPage == 'zenario_not_found'
						 || $specialPage == 'zenario_no_access') {
							$box['tabs']['privacy']['errors']['special'] = ze\admin::phrase('Your selection includes a special page that must be publicly visible.');
						
						} elseif ($specialPage == 'zenario_home' && $values['privacy/privacy'] != 'logged_in') {
							$box['tabs']['privacy']['errors']['home'] =
								ze\admin::phrase('The home page must either be publicly visible or viewable by all Extranet Users.');
						
						} elseif ($specialPage == 'zenario_home' && !ze\content::langSpecialPage('zenario_login', $cID, $cType)) {
							$box['tabs']['privacy']['errors']['home'] =
								ze\admin::phrase('The home page may only be password-protected on sites with the Extranet Login Module running and a login page set up.');
						}
					}
				}
			}
			
			switch ($values['privacy/privacy']) {
				case 'call_static_method':
					if (!$values['privacy/module_class_name']) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the class name of a module.');
		
					} elseif (!ze\module::inc($values['privacy/module_class_name'])) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the class name of a module that you have running on this site.');
		
					} elseif ($values['privacy/method_name']
						&& !method_exists(
								$values['privacy/module_class_name'],
								$values['privacy/method_name'])
					) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the name of an existing public static method.');
					}
		
					if (!$values['privacy/method_name']) {
						$box['tabs']['privacy']['errors'][] = ze\admin::phrase('Please enter the name of a public static method.');
					}
					break;
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$tagIds = explode(',', $box['key']['id']);
		
		if (!empty($tagIds)
		 && empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS')) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}
}