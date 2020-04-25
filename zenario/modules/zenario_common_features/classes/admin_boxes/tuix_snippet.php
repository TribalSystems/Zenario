<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_common_features__admin_boxes__tuix_snippet extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$details = ze\row::get('tuix_snippets', true, $box['key']['id']);
			$values['details/name'] = $details['name'];
			$values['details/custom_yaml'] = $details['custom_yaml'];
			$values['details/custom_json'] = $details['custom_json'];
		
			$box['last_updated'] = ze\admin::formatLastUpdated($details);
		} else {
			unset($box['save_and_continue_button_message']);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//Compile the YAML that the user entered into JSON
		//(N.b. JSON is a lot quicker to parse and read than YAML,
		// so I'm saving a JSON copy for efficiency's sake.)
		if (empty(trim($values['details/custom_yaml']))) {
			$values['details/custom_json'] = '';
		
		
		} elseif (ze\tuix::mixesTabsAndSpaces($values['details/custom_yaml'])) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Your YAML code contains a mixture of tabs and spaces for indentation and cannot be read.');
		
		} else {
			//Hack to try and catch any errors/notices in Spyc by grabbing the output
			ob_start();
				try {
					$tuix = \Spyc::YAMLLoadString(trim($values['details/custom_yaml']));
					$values['details/custom_json'] = json_encode($tuix, JSON_FORCE_OBJECT);
			
				} catch (\Exception $e) {
					$box['tabs']['details']['errors'][] = $e->getMessage();
					$values['details/custom_json'] = '';
				}
			
			if ($error = ob_get_clean()) {
				$box['tabs']['details']['errors'][] = $error;
			}
		}
		
		//Check the name is unique
		if ($values['details/name'] && ze\row::exists('tuix_snippets', ['name' => $values['details/name'], 'id' => ['!' =>  $box['key']['id']]])) {
			$fields['details/name']['error'] = ze\admin::phrase('Please enter a unique name; [[name]] is already in use.', $values);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$details = [
			'name' => $values['details/name'],
			'custom_yaml' => $values['details/custom_yaml'],
			'custom_json' => $values['details/custom_json']
		];
		
		ze\admin::setLastUpdated($details, !$box['key']['id']);
		
		$box['key']['id'] = ze\row::set('tuix_snippets', $details, $box['key']['id']);
	}
}