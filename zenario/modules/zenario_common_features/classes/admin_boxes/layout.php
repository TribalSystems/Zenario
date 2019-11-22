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


class zenario_common_features__admin_boxes__layout extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Two modes:
		//1. Creating a new Layout from an unregistered Template File
		if (ze\ring::engToBoolean($box['key']['create_layout_from_template_file']) && $box['key']['id']) {
			
			$details = explode('/', ze\ring::decodeIdForOrganizer($box['key']['id']), 2);
			$box['key']['family_name'] = $details[0];
			$box['key']['file_base_name'] = $details[1];
			$box['key']['id'] = '';
			
			$box['tabs']['template']['fields']['name']['value'] = ze\ring::decodeIdForOrganizer($box['key']['file_base_name'], '');
			
			$box['title'] = ze\admin::phrase('Registering the template file "[[file_base_name]].tpl.php" and creating a new layout', $box['key']);
		
		//2. Editing an existing Layout
		} else {
			if (!$details = ze\content::layoutDetails($box['key']['id'])) {
				exit;
			}
			
			$box['key']['family_name'] = $details['family_name'];
			$box['key']['file_base_name'] = $details['file_base_name'];
			$box['key']['current_name'] =
			$box['tabs']['template']['fields']['name']['value'] = $details['name'];
			$box['tabs']['template']['fields']['skin_id']['value'] = $details['skin_id'];
			$box['tabs']['template']['fields']['content_type']['value'] = $details['content_type'];
			$box['tabs']['css']['fields']['css_class']['value'] = $details['css_class'];
			$box['tabs']['css']['fields']['background_image']['value'] = $details['bg_image_id'];
			$box['tabs']['css']['fields']['bg_color']['value'] = $details['bg_color'];
			$box['tabs']['css']['fields']['bg_position']['value'] = $details['bg_position'];
			$box['tabs']['css']['fields']['bg_repeat']['value'] = $details['bg_repeat'];
			
			$box['identifier']['value'] = 'L'. str_pad($details['layout_id'], 2, '0', STR_PAD_LEFT);
			
			if ($box['key']['duplicate']) {
				$box['title'] = ze\admin::phrase('Duplicating the layout "[[id_and_name]]".', $details);
				$box['tabs']['template']['fields']['name']['value'] .= ' '. ze\admin::phrase('(copy)');
			
			} else {
				$box['title'] = ze\admin::phrase('Editing settings for the layout "[[id_and_name]]".', $details);
				
				if (isset($box['tabs']['template']['edit_mode'])) {
					$box['confirm']['message'] = 
						ze\admin::phrase('Warning! You are about to change the skin for the layout "[[name]]". Any content items that use this layout will immediately change their appearance to use the new skin.', $details).
						"\n\n".
						ze\admin::phrase('Are you sure you wish to proceed?');
				}
			}
		}
		
		$box['tabs']['template']['fields']['content_type']['readonly'] =
			$box['key']['id'] && !$box['key']['duplicate'] && (ze\row::exists('content_item_versions', ['layout_id' => $box['key']['id']]) || ze\row::exists('content_types', ['default_layout_id' => $box['key']['id']]));
		
		$box['tabs']['template']['fields']['path']['value'] =
			ze\content::templatePath($box['key']['family_name'], $box['key']['file_base_name']);

		
		$box['tabs']['template']['fields']['skin_id']['pick_items']['path'] = 
			'zenario__layouts/panels/template_families/hidden_nav/view_usable_skins//'. ze\ring::encodeIdForOrganizer($box['key']['family_name']). '//';
		
		//For new Layouts, check how many possible Skins there are for this Template Family.
		//If there is only one possible choice, choose it by default.
		if (!$box['key']['id']) {
			$result = ze\row::query('skins', 'id', ['family_name' => $box['key']['family_name'], 'missing' => 0]);
			if (($row1 = ze\sql::fetchAssoc($result))
			 && !($row2 = ze\sql::fetchAssoc($result))) {
				$box['tabs']['template']['fields']['skin_id']['value'] = $row1['id'];
			}
		}
		
		//Say what the default skin is for the Template Family, if one is set
		if (empty($box['tabs']['template']['fields']['skin_id']['value'])
		 && ($family = ze\layoutAdm::familyDetails($box['key']['family_name']))
		 && ($skin = ze\content::skinDetails($family['skin_id']))) {
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
			
			//Work out what the filename for the template should be
			if ($box['key']['duplicate']) {
				$newName = ze\layoutAdm::generateFileBaseName($values['template/name']);
			} else {
				$newName = ze\layoutAdm::generateFileBaseName($values['template/name'], $box['key']['id']);
			}
			
			//Check for any layouts with the same name.
			$key = ['family_name' => $box['key']['family_name'], 'name' => $values['template/name']];
			// If we're saving an existing one, the existing layout should be excluded from this check
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$key['layout_id'] = ['!' => $box['key']['id']];
			}
			
			//Don't allow 2 layouts with the same name, or the same filename
			if (ze\row::exists('layouts', $key)
			 || (!ze\ring::engToBoolean($box['key']['create_layout_from_template_file'])
			  && $newName != $box['key']['file_base_name']
			  && file_exists(ze\content::templatePath($box['key']['family_name'], $newName)))
			) {
				$box['tabs']['template']['errors'][] = ze\admin::phrase('The name for the layout must be unique.');
			}
			
			//Show a warning if changing an existing layout's skin
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$box['confirm']['show'] =
					$box['tabs']['template']['fields']['skin_id']['value']
				 != $box['tabs']['template']['fields']['skin_id']['current_value'];
			}
			
			//Skin should be mandatory if there is not a default value set on the template family
			if (!$values['template/skin_id']
			 && ($family = ze\layoutAdm::familyDetails($box['key']['family_name']))
			 && !($family['skin_id'])) {
				$box['tabs']['template']['errors'][] = ze\admin::phrase('Please select a skin.');
			}	
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze\ring::engToBoolean($box['tabs']['template']['edit_mode']['on'] ?? false) && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			
			$needToClearCache = false;
			
			$layout = [
				'family_name' => $box['key']['family_name'],
				'file_base_name' => $box['key']['file_base_name'],
				'name' => $values['template/name'],
				'content_type' => $values['content_type'],
				'skin_id' => $values['skin_id']];
			
			//If registering an existing layout in the system, try to keep it's id if it's of the forum "L01"
			if (!$box['key']['duplicate']
			 && ze\ring::engToBoolean($box['key']['create_layout_from_template_file'])
			 && $box['key']['file_base_name'][0] == 'L'
			 && ($targetLayoutId = (int) ze\ring::chopPrefix('L', $box['key']['file_base_name']))
			 && (!ze\row::exists('layouts', $targetLayoutId))) {
			
				$layout['layout_id'] = $targetLayoutId;
				$box['key']['id'] = ze\row::insert('layouts', $layout);
				$needToClearCache = true;
			}
			
			if ($box['key']['duplicate']) {
				$newName = ze\layoutAdm::generateFileBaseName($values['template/name']);
			} else {
				$newName = ze\layoutAdm::generateFileBaseName($values['template/name'], $box['key']['id']);
			}
			
			//If changing the name of a layout, attempt to copy its files
			if ($newName != $box['key']['file_base_name']) {
				if (ze\layoutAdm::copyFiles($box['key'], $newName)) {
					//If successful, note down the new name
					$layout['file_base_name'] = $newName;
					$needToClearCache = true;
				
				} else {
					//If duplicating, don't allow the duplication when the files could not be copied
					if ($box['key']['duplicate']) {
						echo ze\admin::phrase('The file system is not writable; your layout files could not be copied');
						exit;
					}
				}
			}
			
			//Save the layout in the database
			if ($box['key']['duplicate']) {
				$sourceTemplateId = $box['key']['id'];
				ze\layoutAdm::save($layout, $box['key']['id'], $sourceTemplateId);
			} else {
				ze\layoutAdm::save($layout, $box['key']['id']);
			}
			
			//Try to delete any files that are not in use
			ze\layoutAdm::delete($box['key'], false);
			
			//If a default Skin was not set for the Template Family, set it to the one picked for this Layout
			if ($values['template/skin_id']
			 && ($family = ze\layoutAdm::familyDetails($box['key']['family_name']))
			 && !($family['skin_id'])) {
				ze\row::update('template_families', ['skin_id' => $values['template/skin_id']], $box['key']['family_name']);
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
	}
}
