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


class zenario_common_features__admin_boxes__image_tag extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Hack to fix a bug where you can have a tag named "0".
		//Usually the FABs don't populate the id field if it's set to 0, however we
		//would specifically want that in this case
		if (isset($_REQUEST['id'])
		 && $_REQUEST['id'] == '0') {
			$box['key']['id'] = '0';
		}
		
		if ($box['key']['id'] != '') {
			if (!$details = ze\row::get('image_tags', true, ['name' => $box['key']['id']])) {
				echo ze\admin::phrase('Could not find a tag with the name "[[name]]"', $details);
				exit;
			}
			
			//Ensure image tags are all lower-case
			$box['key']['id'] = mb_strtolower($box['key']['id']);
			
			$values['details/name'] = $details['name'];
			$values['details/color'] = $details['color'];
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//Ensure image tags are all lower-case
		$values['details/name'] = mb_strtolower($values['details/name']);
		
		if ($values['details/name'] == $box['key']['id']) {
		} elseif (ze\row::exists('image_tags', ['name' => $values['details/name']])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('A tag name with this name already exists.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_MEDIA');
		
		$ids = [];
		if ($box['key']['id'] != '') {
			$ids = ['name' => $box['key']['id']];
		}
		
		ze\row::set(
			'image_tags',
			['name' => $values['details/name'], 'color' => $values['details/color']],
			$ids
		);
	}
}
