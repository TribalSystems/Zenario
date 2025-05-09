<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
class zenario_users__admin_boxes__content extends zenario_users__privacy_options_base {
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$cID = $box['key']['source_cID'];
		$cType = $box['key']['cType'];
		$equivId = $chain = false;
		
		//Try to load the privacy options from the translation_chains table.
		if ($cID && $cType
		 && ($equivId = ze\content::equivId($box['key']['source_cID'], $box['key']['cType']))
		 && ($chain = ze\row::get('translation_chains', true, ['equiv_id' => $equivId, 'type' => $cType]))) {
			
			$values['privacy/privacy'] = $chain['privacy'];
			$values['privacy/at_location'] = $chain['at_location'];
		}

		
		if (empty($box['tabs']['privacy']['hidden'])) {
			$cType = $box['key']['cType'];
			
			$this->fillPrivacySettings($path, $settingGroup, $box, $fields, $values);
			
			if ($chain) {
				$this->loadPrivacySettings($cType. '_'. $equivId, $path, $settingGroup, $box, $fields, $values);
			
			} else {
				//Default newly create items to content type setting
				$values['privacy/privacy'] = 'public';
				if ($contentTypeDetails = ze\contentAdm::cTypeDetails($cType)) {
					$values['privacy/privacy'] = $contentTypeDetails['default_permissions'];
				}
			}
		}
		
		if (!$cID || !$cType) {
			unset(
				$fields['privacy/group_ids']['format_onchange'],
				$fields['privacy/smart_group_id']['format_onchange'],
				$fields['privacy/role_ids']['format_onchange'],
				$fields['privacy/at_location']['format_onchange'],
				$fields['privacy/module_class_name']['oninput'],
				$fields['privacy/method_name']['oninput'],
				$fields['privacy/param_1']['oninput'],
				$fields['privacy/param_2']['oninput'],
				$fields['privacy/content_item_privacy_info_warning']
			);
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Hide the site-map/search engine preview for non-public pages
		if ($values['privacy/privacy'] != 'public') {
			$fields['meta_data/excluded_from_sitemap']['hidden'] = false;
			$fields['meta_data/included_in_sitemap']['hidden'] = true;
		}
		
		$cID = $box['key']['source_cID'];
		$cType = $box['key']['cType'];
		
		if ($box['key']['id'] && $cID && $cType && isset($fields['privacy/content_item_privacy_info_warning'])) {
			$showWarning = false;
			if ($values['privacy/privacy'] != $box['key']['privacy_settings_on_load']['privacy_setting_value']) {
				$showWarning = true;
			} else {
				switch ($box['key']['privacy_settings_on_load']['privacy_setting_value']) {
					case 'group_members':
						if ($values['privacy/group_ids'] != $box['key']['privacy_settings_on_load']['group_ids']) {
							$showWarning = true;
						}
						break;
					case 'in_smart_group':
					case 'logged_in_not_in_smart_group':
						if ($values['privacy/smart_group_id'] != $box['key']['privacy_settings_on_load']['smart_group_id']) {
							$showWarning = true;
						}
						break;
					case 'with_role':
						if (
							$values['privacy/role_ids'] != $box['key']['privacy_settings_on_load']['role_ids']
							|| $values['privacy/at_location'] != $box['key']['privacy_settings_on_load']['at_location']
						) {
							$showWarning = true;
						}
						break;
				}
			}
			
			if ($showWarning) {
				unset($fields['privacy/content_item_privacy_info_warning']['row_class']);
			} else {
				$fields['privacy/content_item_privacy_info_warning']['row_class'] = 'zfab_inline_warning_hidden';
			}
		}
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)) {
			
			$this->validatePrivacySettings($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$tagIds = [$box['key']['cType']. '_'. $box['key']['cID']];
		
		if (empty($box['tabs']['privacy']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['privacy']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
			$this->savePrivacySettings($tagIds, $values);
		}
	}
}