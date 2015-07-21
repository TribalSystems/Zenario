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

class zenario_users__admin_boxes__content_privacy extends zenario_users {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Populate the list of groups
		$lov = listCustomFields('users', $flat = false, 'groups_only', $customOnly = false, $useOptGroups = true, $hideEmptyOptGroupParents = true);
		
		//Note: these are multi-checkboxes fields. I want to show the tabs, but I don't want
		//people to be able to select them
		foreach ($lov as &$v) {
			if (empty($v['parent'])) {
				$v['readonly'] =
				$v['disabled'] = true;
				$v['style'] = 'display: none;';
			}
		}
		
		$box['tabs']['privacy']['fields']['group_members']['values'] = $lov;
		
		
		$combinedValues = true;
		
		$box['key']['originalId'] = $box['key']['id'];
		
		$total = 0;
		$tagSQL = "";
		$tagIds = array();
		$equivId = $cType = false;
		
		if (request('equivId') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('equivId');
		
		} elseif (request('cID') && request('cType')) {
			$box['key']['id'] = request('cType'). '_'. request('cID');
		}
		
		//Given a list of tag ids using cID and cType, convert them to equivIds and cTypes
		foreach (explode(',', $box['key']['id']) as $tagId) {
			if (getEquivIdAndCTypeFromTagId($equivId, $cType, $tagId)) {
				$tagId = $cType. '_'. $equivId;
				if (!isset($tagIds[$tagId])) {
					$tagIds[$tagId] = $tagId;
					++$total;
					
					//Attempt to see if the values of each chosen item match!
					
					//If we've already failed, stop trying
					if ($combinedValues !== false) {
						//Look up the values
						if ($chain = getRow('translation_chains', array('privacy', 'log_user_access'), array('equiv_id' => $equivId, 'type' => $cType))) {
							$chain['group_content_link'] = self::setupGroupOrUserCheckboxes('group_content_link', 'group_id', $equivId, $cType);
							$chain['user_content_link'] = self::setupGroupOrUserCheckboxes('user_content_link', 'user_id', $equivId, $cType);
							
							//If we've previously had some values, do these ones match?
							if (is_array($combinedValues)) {
								foreach ($chain as $key => $value) {
									if ($combinedValues[$key] != $chain[$key]) {
										//If they don't match, mark this as failed and give up
										$combinedValues = false;
										break;
									}
								}
							
							//If we've not had previous values, remember these ones!
							} else {
								$combinedValues = $chain;
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
		if (is_array($combinedValues)) {
			$values['privacy/privacy'] = $chain['privacy'];
			$values['privacy/log_user_access'] = $chain['log_user_access'];
			$values['privacy/group_members'] = self::setupGroupOrUserCheckboxes('group_content_link', 'group_id', $equivId, $cType);
			$values['privacy/specific_users'] = self::setupGroupOrUserCheckboxes('user_content_link', 'user_id', $equivId, $cType);
		}
		
		$numLanguages = getNumLanguages();
		if ($numLanguages > 1) {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the permissions of all content items in [[count]] translation chains.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing permissions for [[count]] translation chains',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing permissions for the content item "[[tag]]" and its translations',
						array('tag' => formatTag($equivId, $cType)));
			}
			
		} else {
			if ($total > 1) {
				$box['confirm']['show'] = true;
				$box['confirm']['message'] =
					adminPhrase('This will update the permissions of [[count]] content items.',
						array('count' => $total));
				
				$box['title'] =
					adminPhrase('Changing permissions for [[count]] content items',
						array('count' => $total));
			} else {
				$box['title'] =
					adminPhrase('Changing permissions for the content item "[[tag]]"',
						array('tag' => formatTag($equivId, $cType)));
			}
		}
		
		if ($total > 1) {
			$box['confirm']['message'] .=
				"\n\n".
				adminPhrase('The content items in all selected translation chains will be set to the permissions you selected.');
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && engToBooleanArray($box, 'tabs', 'privacy', 'edit_mode', 'on')) {
					
			$tagIds = explode(',', $box['key']['id']);
			foreach ($tagIds as $tagId) {
				if (getCIDAndCTypeFromTagId($cID, $cType, $tagId)) {
					if ($values['privacy/privacy'] != 'public' && ($specialPage = isSpecialPage($cID, $cType))) {
						$cID = $cType = false;
						if ($specialPage == 'zenario_login'
						 || $specialPage == 'zenario_registration'
						 || $specialPage == 'zenario_password_reminder'
						 || $specialPage == 'zenario_not_found'
						 || $specialPage == 'zenario_no_access') {
							$box['tabs']['privacy']['errors']['special'] = adminPhrase('Your selection includes a special page that must be publicly visible.');
						
						} elseif ($specialPage == 'zenario_home' && $values['privacy/privacy'] != 'all_extranet_users') {
							$box['tabs']['privacy']['errors']['home'] =
								adminPhrase('The home page must either be publicly visible or viewable by all Extranet Users.');
						
						} elseif ($specialPage == 'zenario_home' && !langSpecialPage('zenario_login', $cID, $cType)) {
							$box['tabs']['privacy']['errors']['home'] =
								adminPhrase('The home page may only be password-protected on sites with the Extranet Login Module running and a login page set up.');
						}
					}
				}
			}
				
			if (!$values['privacy/privacy']) {
				$box['tabs']['privacy']['errors'][] = adminPhrase('Please select an option.');
			}
			
			if ($values['privacy/privacy'] == 'specific_users'
			 && !$values['privacy/specific_users']) {
				$box['tabs']['privacy']['errors'][] = adminPhrase('Please select a User.');
			}
			
			if ($values['privacy/privacy'] == 'group_members'
			 && !$values['privacy/group_members']) {
				$box['tabs']['privacy']['errors'][] = adminPhrase('Please select a Group.');
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$tagIds = explode(',', $box['key']['id']);
		
		if (!empty($tagIds)
		 && empty($box['tabs']['privacy']['hidden'])
		 && engToBooleanArray($box, 'tabs', 'privacy', 'edit_mode', 'on')
		 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS')) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}
}