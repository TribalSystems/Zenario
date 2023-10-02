<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

require_once CMS_ROOT. ze::moduleDir('zenario_users', 'classes/admin_boxes/_privacy_options_base.php');
class zenario_users__admin_boxes__content_privacy extends zenario_users__privacy_options_base {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id_is_menu_node_id']) {
			$box['key']['menu_node_id'] = $box['key']['id'];
			
			$menuContentItem = ze\menu::getContentItem($box['key']['id']);
			$box['key']['id'] = $menuContentItem['content_type'] . '_' . $menuContentItem['content_id'];
		}

		//Set up the primary key from the requests given
		if ($box['key']['id'] && empty($box['key']['cID'])) {
			ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);
		}

		//Check admin permission, including specific content item/type permission.
		if (!ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$box['tabs']['privacy']['edit_mode']['enabled'] = false;
		}
		
		$content =
			ze\row::get(
				'content_items',
				['id', 'type', 'tag_id', 'language_id', 'equiv_id', 'alias', 'visitor_version', 'admin_version', 'status'],
				['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
		
		if ($content && isset($content['id'], $content['type'], $content['status'])) {
			$box['identifier']['css_class'] = ze\contentAdm::getItemIconClass($content['id'], $content['type'], true, $content['status']);
		}
		
		
		$this->fillPrivacySettings($path, $settingGroup, $box, $fields, $values);
		
		
		$box['key']['originalId'] = $box['key']['id'];
		
		
		if (ze::request('equivId') && ze::request('cType')) {
			$box['key']['id'] = ze::request('cType'). '_'. ze::request('equivId');
		
		} elseif (ze::request('cID') && ze::request('cType')) {
			$box['key']['id'] = ze::request('cType'). '_'. ze::request('cID');
		}
		
		
		$tagIds = $this->loadPrivacySettings($box['key']['id'], $path, $settingGroup, $box, $fields, $values);
		
		if (empty($tagIds)) {
			exit;
		}
		$box['key']['id'] = implode(',', $tagIds);
		$total = count($tagIds);
		
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
						['tag' => ze\content::formatTagFromTagId($box['key']['id'])]);
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
						['tag' => ze\content::formatTagFromTagId($box['key']['id'])]);
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
			
			$this->validatePrivacySettings($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$tagIds = explode(',', $box['key']['id']);
		
		if (!empty($tagIds)
		 && empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['enabled'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}

	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id_is_menu_node_id']) {
			$box['key']['id'] = $box['key']['menu_node_id'];
		}
	}
}