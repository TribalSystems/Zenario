<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


class zenario_common_features__admin_boxes__layout extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
			if (!$details = ze\content::layoutDetails($box['key']['id'], $showUsage = true, $checkIfDefault = true, $getDefinition = false)) {
				exit;
			}
			$box['key']['current_name'] =
			$box['tabs']['template']['fields']['name']['value'] = $details['name'];
			$box['tabs']['template']['fields']['skin_id']['value'] = $details['skin_id'];
			$box['tabs']['template']['fields']['content_type']['value'] = $details['content_type'];
			$box['tabs']['css']['fields']['css_class']['value'] = $details['css_class'];
			$box['tabs']['css']['fields']['background_image']['value'] = $details['bg_image_id'];
			$box['tabs']['css']['fields']['bg_color']['value'] = $details['bg_color'];
			$box['tabs']['css']['fields']['bg_position']['value'] = $details['bg_position'];
			$box['tabs']['css']['fields']['bg_repeat']['value'] = $details['bg_repeat'];
			
			$box['identifier']['value'] = ze\layoutAdm::codeName($details['layout_id']);
			
			if ($box['key']['duplicate']) {
				$box['title'] = ze\admin::phrase('Duplicating the layout "[[id_and_name]]".', $details);
				$box['tabs']['template']['fields']['name']['value'] .= ' '. ze\admin::phrase('(copy)');
				$box['tabs']['template']['fields']['status']['hidden'] = true;
				$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['hidden'] = true;
				
			
			} else {
				$box['title'] = ze\admin::phrase('Editing settings for the layout "[[id_and_name]]".', $details);
				
				if (isset($box['tabs']['template']['edit_mode'])) {
					$box['confirm']['message'] = 
						ze\admin::phrase('Warning! You are about to change the skin for the layout "[[name]]". Any content items that use this layout will immediately change their appearance to use the new skin.', $details).
						"\n\n".
						ze\admin::phrase('Are you sure you wish to proceed?');
				}
			}
			
			$link = ze\link::absolute() . '/zenario/admin/organizer.php#zenario__layouts/panels/layouts/item_buttons/view_content//' . $box['key']['id'] . '//';
			
			if ($details['content_item_count'] == 1) {
				$sql = '
					SELECT DISTINCT
						ci.id, ci.type, ci.alias, civ.layout_id
					FROM ' . DB_PREFIX . 'content_items ci
					LEFT JOIN ' . DB_PREFIX . 'content_item_versions civ
						ON civ.id = ci.id
						AND ci.admin_version = civ.version
						AND ci.type = civ.type
					WHERE civ.layout_id = '. (int) $details['layout_id'];
				
				$result = ze\sql::select($sql);
				$contentItem = (ze\sql::fetchAssoc($result));
				$contentItem = ze\content::formatTag($contentItem['id'], $contentItem['type'], $contentItem['alias']);
				
				$box['tabs']['template']['fields']['name']['note_below'] = ze\admin::phrase(
					'<a href="' . $link . '" target="_blank">' . $contentItem . '</a> uses this layout.');
					
			} elseif ($details['content_item_count'] > 1) {
				$box['tabs']['template']['fields']['name']['note_below'] = ze\admin::phrase(
					'<a href="' . $link . '" target="_blank">[[content_item_count]] content items</a> use this layout.',
					['content_item_count' => $details['content_item_count']]);
			}
		
		
		//Layout status
		$box['tabs']['template']['fields']['status']['value'] = $details['status'];
		
		if ($details['status'] == 'active') {
			if (!$box['key']['duplicate'] && $details['default_layout_for_ctype'] != null) {
				//A content type must always have a default layout.
				//Check whether the currently selected layout is the default for a content type.
				//If it isn't, allow the user to make it the default,
				//but don't allow to leave a content item without a default layout entirely.
				$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['value'] = true;
				$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['disabled'] = true;
				//TODO
				$panelLink = ze\link::absolute() . '/zenario/admin/organizer.php#zenario__content/panels/content_types//' . $box['tabs']['template']['fields']['content_type']['value'];
				$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['note_below'] =
					ze\admin::phrase('To change the default layout, go to <a href="' . $panelLink . '" target="_blank">Settings for content types</a> panel.');
				
				//Don't allow archiving a layout that is the default for a content item.
				$box['tabs']['template']['fields']['status']['values']['suspended']['disabled'] = true;
				$box['tabs']['template']['fields']['status']['values']['suspended']['side_note'] = ze\admin::phrase('You cannot archive a layout that is the default layout for a content type.');
			}
		} elseif ($details['status'] == 'suspended') {
			//Don't allow using archived layouts as defaults.
			$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['disabled'] = true;
			$box['tabs']['template']['fields']['layout_is_detault_for_ctype']['side_note'] = ze\admin::phrase('You cannot set an archived layout as the default.');
			$box['identifier']['css_class'] = 'archived_layout';
		}		
		
		$box['tabs']['template']['fields']['content_type']['readonly'] =
			$box['key']['id'] && !$box['key']['duplicate'] && (ze\row::exists('content_item_versions', ['layout_id' => $box['key']['id']]) || ze\row::exists('content_types', ['default_layout_id' => $box['key']['id']]));
		
		$box['tabs']['template']['fields']['skin_id']['pick_items']['path'] = 
			'zenario__layouts/panels/skins';
		
		//For new Layouts, check how many possible Skins there are for this Template Family.
		//If there is only one possible choice, choose it by default.
		if (!$box['key']['id']) {
			$result = ze\row::query('skins', 'id', ['missing' => 0]);
			if (($row1 = ze\sql::fetchAssoc($result))
			 && !($row2 = ze\sql::fetchAssoc($result))) {
				$box['tabs']['template']['fields']['skin_id']['value'] = $row1['id'];
			}
		}
		
		//Say what the default skin is for the Template Family, if one is set
		if (empty($box['tabs']['template']['fields']['skin_id']['value'])
		 && ($skin = ze\content::skinDetails(1))) {
			$box['tabs']['template']['fields']['skin_id']['pick_items']['nothing_selected_phrase'] = 
				ze\admin::phrase('Use the default skin for this layout [[[display_name]]]', $skin);
		}
	}
	
	
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$box['tabs']['css']['fields']['css_class']['pre_field_html'] =
			'<span class="zenario_css_class_label">'.
				'zenario_'. $values['template/content_type']. '_layout'.
			'</span> ';
	}
	
	
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (ze\ring::engToBoolean($box['tabs']['template']['edit_mode']['on'] ?? false) && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			
			//Check for any layouts with the same name.
			$key = ['name' => $values['template/name']];
			
			// If we're saving an existing one, the existing layout should be excluded from this check
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$key['layout_id'] = ['!' => $box['key']['id']];
			}
			
			if (ze\row::exists('layouts', $key)) {
				$box['tabs']['template']['errors'][] = ze\admin::phrase('The name for the layout must be unique.');
			}
			
			//Show a warning if changing an existing layout's skin
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$box['confirm']['show'] =
					$box['tabs']['template']['fields']['skin_id']['value']
				 != $box['tabs']['template']['fields']['skin_id']['current_value'];
			}
			
			//Skin should be mandatory if there is not a default value set on the template family
			if (!$values['template/skin_id']) {
				$box['tabs']['template']['errors'][] = ze\admin::phrase('Please select a skin.');
			}	
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\ring::engToBoolean($box['tabs']['template']['edit_mode']['on'] ?? false) && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			
			$needToClearCache = false;
			
			$layout = [
				'name' => $values['template/name'],
				'content_type' => $values['content_type'],
				'skin_id' => $values['skin_id']];
			
			//Save the layout in the database
			if ($box['key']['duplicate']) {
				$sourceLayoutId = $box['key']['id'];
				ze\layoutAdm::save($layout, $box['key']['id'], $sourceLayoutId);
			} else {
				ze\layoutAdm::save($layout, $box['key']['id']);
			}
			
		}
		
		if (ze\ring::engToBoolean($box['tabs']['css']['edit_mode']['on'] ?? false) && ze\priv::check('_PRIV_EDIT_TEMPLATE') && $box['key']['id']) {
			$vals = [];
			$vals['css_class'] = $values['css/css_class'];
			
			if (($filepath = ze\file::getPathOfUploadInCacheDir($values['css/background_image']))
			 && ($imageId = ze\file::addToDatabase('background_image', $filepath, false, $mustBeAnImage = true))) {
				$vals['bg_image_id'] = $imageId;
			} else {
				$vals['bg_image_id'] = $values['css/background_image'];
			}
			
			$vals['bg_color'] = $values['css/bg_color'];
			$vals['bg_position'] = $values['css/bg_position']? $values['css/bg_position'] : null;
			$vals['bg_repeat'] = $values['css/bg_repeat']? $values['css/bg_repeat'] : null;
			
			ze\layoutAdm::save($vals, $box['key']['id']);
			
			ze\contentAdm::deleteUnusedBackgroundImages();
		}
		
		if ($needToClearCache) {
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
		}
		
		if ($values['layout_is_detault_for_ctype']) {
			ze\row::update('content_types', ['default_layout_id' => $box['key']['id']], ['content_type_id' => $values['template/content_type']]);
		}
		
		if (empty($fields['template/layout_is_detault_for_ctype']['disabled'])) {
			
			ze\row::update('layouts', ['status' => $values['template/status']], ['layout_id' => $box['key']['id']]);
		}
	}
}
