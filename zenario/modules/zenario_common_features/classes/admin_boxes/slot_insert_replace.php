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


class zenario_common_features__admin_boxes__slot_insert_replace extends ze\moduleBaseClass {

	//Zenario 10.1 we're experimenting with showing some options in a FAB
	//instead of directly in the slot drop-down, to reduce clutter.
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$ord = 0;
		
		//Check the key to see what options we should show, and add a radioin a grouping for each one.
		//The calling function will set the text for a label in the key for each option that
		//should be visible.
		foreach ([
			'insert_reusable_on_item_layer',
			'insert_reusable_on_layout_layer',
			'insert_nest_on_item_layer',
			'insert_nest_on_layout_layer',
			'insert_slideshow_on_item_layer',
			'insert_slideshow_on_layout_layer',
			'replace_reusable_on_item_layer',
			'replace_reusable_on_layout_layer',
			'replace_nest_on_item_layer',
			'replace_nest_on_layout_layer',
			'replace_slideshow_on_item_layer',
			'replace_slideshow_on_layout_layer'
		] as $controlName) {
			if ($controlLabel = $box['key'][$controlName] ?? null) {
				
				$groupingName = 'grp__'. $controlName;
				$radioName = $controlName;
				$noteName = 'note__'. $controlName;
				
				$box['tabs']['details']['fields'][$groupingName] = [
					'ord' => ++$ord,
                    'type' => 'grouping',
                    'name' => $groupingName,
                    'grouping_css_class' => 'zenario_vertical_radio',
                    'grouping_wrapper_css_class' => 'zenario_vertical_radios',
                    'grouping_onclick' => "zenarioAB.clickingGroupingChecksBox('". $radioName. "');"
                ];
				$box['tabs']['details']['fields'][$radioName] = [
					'ord' => ++$ord,
                    'grouping' => $groupingName,
                    'type' => 'radio',
                    'name' => 'choice',
                    'label' => $controlLabel,
                    'redraw_onchange' => true
                ];
				$box['tabs']['details']['fields'][$noteName] = [
					'ord' => ++$ord,
                    'grouping' => $groupingName,
                    'indent' => 1,
                    'notices_below' => [
                    	'note' => $box['tabs']['details']['custom_notes'][$controlName]
					]
                ];
			}
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//Check at least something is selected when trying to press the continue button.
		$choice = false;
		foreach ($values as $val) {
			if ($val) {
				$choice = true;
				break;
			}
		}
		if (!$choice) {
			$box['tabs']['details']['errors']['nothing_selected'] = ze\admin::phrase('Please select an option.');
		}
		
		//N.b. no further validation needed; we'll be handing control back to the calling logic which will
		//check which option was selected.
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}