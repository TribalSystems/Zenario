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


class zenario_copyright__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!$values['first_tab/display_single_year']) {
			$values['first_tab/display_single_year'] = 'current_year';
		}
		
		if (!$values['first_tab/end_year_type']) {
			$values['first_tab/end_year_type'] = 'current_year';
		}
		
		
		//Don't show the option to pick a translation chain when not linking to a content item, on single-language sites,
		//or on version controlled plugins.
		$fields['first_tab/use_translation']['hidden'] = 
			$values['first_tab/link_type'] != 'internal'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;

		//On multilingual sites, default the set the value of the use_translation option to enabled by default.
		//We'll achieve this by changing the value on opening the FAB, if we see it hidden.
		if (!empty($fields['first_tab/use_translation']['hidden'])
		 && !$box['key']['isVersionControlled']
		 && ze\lang::count() >= 2) {
			$values['first_tab/use_translation'] = 1;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
				
		if (($values['first_tab/year_display'] == 'display_single_year' && $values['first_tab/display_single_year'] == 'current_year')
		 || ($values['first_tab/year_display'] == 'display_year_range' && $values['first_tab/end_year_type'] == 'current_year')) {
			 $box['tabs']['first_tab']['notices']['caching_note']['show'] = true;
		} else {
			$box['tabs']['first_tab']['notices']['caching_note']['show'] = false;
		}
		
		//The tag IDs in translation chain pickers have a slightly different format.
		//This is needed for a technical reason, as meta-info about the selected items are stored by ID.
		//When displaying, change between formats depending on whether we are showing a specific content item or a translation chain.
		$values['first_tab/hyperlink_target'] =
			ze\contentAdm::convertBetweenTagIdAndTranslationChainId($values['first_tab/hyperlink_target'], $values['first_tab/use_translation']);
		
		//Don't show the option to pick a translation chain when not linking to a content item, on single-language sites,
		//or on version controlled plugins.
		$fields['first_tab/use_translation']['hidden'] = 
			$values['first_tab/link_type'] != 'internal'
		 || $box['key']['isVersionControlled']
		 || ze\lang::count() < 2;

		//Format the picker slightly differently when selecting a translation chain v.s selecting a content item.
		//Note: these are cosmetic changes only, for backwards compatibility reasons the values in the database and logic in the
		//PHP code is still exactly the same as it was in Zenario 9.4.
		if ($values['first_tab/use_translation'] && empty($fields['first_tab/use_translation']['hidden'])) {
			$fields['first_tab/hyperlink_target']['pick_items'] = $fields['first_tab/hyperlink_target__translation']['pick_items'];
			$fields['first_tab/hyperlink_target']['validation'] = $fields['first_tab/hyperlink_target__translation']['validation'];

		} else {
			$fields['first_tab/hyperlink_target']['pick_items'] = $fields['first_tab/hyperlink_target__specific']['pick_items'];
			$fields['first_tab/hyperlink_target']['validation'] = $fields['first_tab/hyperlink_target__specific']['validation'];
	
			if (!empty($fields['first_tab/use_translation']['hidden'])) {
				$fields['first_tab/hyperlink_target']['label'] = ze\admin::phrase('Content item:');
			}
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//The tag IDs in translation chain pickers have a slightly different format.
		//This is needed for a technical reason, as meta-info about the selected items are stored by ID.
		//For backwards compatibility reasons, always save the value in the old format
		$values['first_tab/hyperlink_target'] =
			ze\contentAdm::convertBetweenTagIdAndTranslationChainId($values['first_tab/hyperlink_target'], false);
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	
	
	
}
