<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

				
class zenario_pro_features__admin_boxes__menu_section extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id'] && ($section = getRow('menu_sections', array('section_name'), $box['key']['id']))) {
			$box['tabs']['menu_section']['fields']['section_name']['value'] = $section['section_name'];
			$box['title'] = adminPhrase('Renaming the Menu Section "[[section_name]]"', $section);
		} else {
			$box['key']['id'] = '';
			$box['tabs']['menu_section']['edit_mode']['always_on'] = true;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
			if (preg_replace('/\S/', '', $values['menu_section/section_name'])) {
				$box['tabs']['menu_section']['errors'][] = adminPhrase('The name may not contain spaces.');
			
			} else
			if (checkRowExists(
				'menu_sections',
				array('section_name' => $values['menu_section/section_name'], 'id' => array('!' => $box['key']['id']))
			)) {
				$box['tabs']['menu_section']['errors'][] =
					adminPhrase('The Menu Section "[[section_name]]" already exists.', $values['menu_section']);
			}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_ADD_MENU_SECTION');
		
		//Add if new
		if (!$box['key']['id']) {
			insertRow('menu_sections', array('section_name' => $values['menu_section/section_name']));
		
		//Rename if changed
		} elseif ($values['menu_section/section_name'] != $box['key']['id']) {
			updateRow('menu_sections', array('section_name' => $values['menu_section/section_name']), $box['key']['id']);
		}
		recalcMenuPositionsTopLevel();
	}

}
