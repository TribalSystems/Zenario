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

class zenario_users__admin_boxes__content extends zenario_users {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$equivId = $cID = $cType = false;
		
		if (empty($box['tabs']['privacy']['hidden'])) {
			$cType = $box['key']['cType'];
			
			$fields['privacy/group_ids']['values'] = getGroupPickerCheckboxesForFAB();
			$fields['privacy/smart_group_id']['values'] = getListOfSmartGroupsWithCounts();
			
			$contentTypeDetails = getContentTypeDetails($cType);
			
			//Default newly create items to content type setting
			$values['privacy/privacy'] = 'public';
			if ($contentTypeDetails) {
				$values['privacy/privacy'] = $contentTypeDetails['default_permissions'];
			}
			
			
			//Try to load the privacy options from the translation_chains table.
			if (($cID = $box['key']['source_cID'])
			 && ($cType)
			 && ($equivId = equivId($box['key']['source_cID'], $box['key']['cType']))
			 && ($chain = getRow('translation_chains', true, array('equiv_id' => $equivId, 'type' => $cType)))) {
				
				$values['privacy/privacy'] = $chain['privacy'];
				
				switch ($chain['privacy']) {
					case 'group_members':
						$values['privacy/group_ids'] =
							inEscape(getRowsArray('group_content_link', 'group_id', array('equiv_id' => $equivId, 'content_type' => $cType)), true);
						break;
					
					case 'call_static_method':
						if ($privacySettings = getRow('translation_chain_privacy', true, array('equiv_id' => $equivId, 'content_type' => $cType))) {
							$values['privacy/module_class_name'] = $privacySettings['module_class_name'];
							$values['privacy/method_name'] = $privacySettings['method_name'];
							$values['privacy/param_1'] = $privacySettings['param_1'];
							$values['privacy/param_2'] = $privacySettings['param_2'];
						}
						break;
					
					case 'in_smart_group':
					case 'logged_in_not_in_smart_group':
						$values['privacy/smart_group_id'] = $chain['smart_group_id'];
						break;
				}
			}
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && engToBooleanArray($box, 'tabs', 'privacy', 'edit_mode', 'on')) {
			
			switch ($values['privacy/privacy']) {
				case 'call_static_method':
					if (!$values['privacy/module_class_name']) {
						$box['tabs']['privacy']['errors'][] = adminPhrase('Please enter the class name of a module.');
		
					} elseif (!inc($values['privacy/module_class_name'])) {
						$box['tabs']['privacy']['errors'][] = adminPhrase('Please enter the class name of a module that you have running on this site.');
		
					} elseif ($values['privacy/method_name']
						&& !method_exists(
								$values['privacy/module_class_name'],
								$values['privacy/method_name'])
					) {
						$box['tabs']['privacy']['errors'][] = adminPhrase('Please enter the name of an existing public static method.');
					}
		
					if (!$values['privacy/method_name']) {
						$box['tabs']['privacy']['errors'][] = adminPhrase('Please enter the name of a public static method.');
					}
					break;
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$tagIds = array($box['key']['cType']. '_'. $box['key']['cID']);
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && engToBooleanArray($box, 'tabs', 'privacy', 'edit_mode', 'on')
		 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_PERMISSIONS')) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}
}