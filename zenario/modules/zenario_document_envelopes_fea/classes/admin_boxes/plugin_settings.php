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


class zenario_document_envelopes_fea__admin_boxes__plugin_settings extends zenario_document_envelopes_fea {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		ze\miscAdm::setupSlideDestinations($box, $fields, $values);
		$dataset = ze\dataset::details(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes');
		$fields['first_tab/custom_field_1']['pick_items']['path'] .= $dataset['id'] . '//';
		$fields['first_tab/custom_field_1']['pick_items']['info_button_path'] =
			'zenario__administration/panels/custom_datasets/item_buttons/edit_gui//'. $dataset['id']. '//';
		
		$fields['first_tab/custom_field_2']['pick_items'] = $fields['first_tab/custom_field_3']['pick_items'] = $fields['first_tab/custom_field_1']['pick_items'];
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$this->setupOverridesForPhrases($box, $fields, $values);
		
		if ($values['global_area/mode'] == 'view_document_envelope') {
			$fields['first_tab/enable.edit_document_envelope']['grouping'] =
				$fields['first_tab/to_state1.edit_document_envelope']['grouping'] =
				$fields['first_tab/to_state2.edit_document_envelope']['grouping'] =
				$fields['first_tab/enable.delete_document_envelope']['grouping'] =
				$fields['first_tab/show_edit_button_as_icon']['grouping'] =
				$fields['first_tab/show_delete_button_as_icon']['grouping'] = 'buttons';
			
			$fields['first_tab/show_thumbnail_image']['grouping'] =
				$fields['first_tab/canvas']['grouping'] =
				$fields['first_tab/width']['grouping'] =
				$fields['first_tab/height']['grouping'] =
				$fields['first_tab/retina']['grouping'] =
				$fields['first_tab/use_fallback_image']['grouping'] =
				$fields['first_tab/fallback_image']['grouping'] = 'options';
		} elseif ($values['global_area/mode'] == 'list_document_envelopes') {
			$fields['first_tab/enable.edit_document_envelope']['grouping'] =
				$fields['first_tab/to_state1.edit_document_envelope']['grouping'] =
				$fields['first_tab/to_state2.edit_document_envelope']['grouping'] =
				$fields['first_tab/enable.delete_document_envelope']['grouping'] = 'item_buttons';
			
			$fields['first_tab/show_thumbnail_image']['grouping'] =
				$fields['first_tab/canvas']['grouping'] =
				$fields['first_tab/width']['grouping'] =
				$fields['first_tab/height']['grouping'] =
				$fields['first_tab/retina']['grouping'] =
				$fields['first_tab/use_fallback_image']['grouping'] =
				$fields['first_tab/fallback_image']['grouping'] = 'show_columns';
			
			for ($i = 1; $i <= 3; $i++) {
				if (!$values['first_tab/enable.search_box'] || !$values['first_tab/custom_field_' . $i]) {
					$fields['first_tab/make_custom_field_' . $i . '_searchable']['disabled'] = true;
					$fields['first_tab/make_custom_field_' . $i . '_searchable']['side_note'] =
						$this->phrase('Please enable search panel and select a custom field.');
				} else {
					$fields['first_tab/make_custom_field_' . $i . '_searchable']['disabled'] = false;
					unset($fields['first_tab/make_custom_field_' . $i . '_searchable']['side_note']);
				}
			}
		}

		if ($values['global_area/mode'] == 'view_document_envelope' || $values['global_area/mode'] == 'edit_document_envelope' || $values['global_area/mode'] == 'create_document_envelope') {
			$fields['first_tab/show_name']['read_only'] =
			$fields['first_tab/show_description']['read_only'] = true;

			$values['first_tab/show_name'] =
			$values['first_tab/show_description'] = true;
		} else {
			$fields['first_tab/show_name']['read_only'] =
			$fields['first_tab/show_description']['read_only'] = false;
		}

		if ($values['global_area/mode'] == 'edit_document_envelope' || $values['global_area/mode'] == 'create_document_envelope') {
			$fields['first_tab/show_code']['label'] = 'Code (if checked, mandatory and unique)';
		} else {
			$fields['first_tab/show_code']['label'] = 'Code';
		}
		
		$hideImageSettings = (($values['global_area/mode'] != 'list_document_envelopes' && $values['global_area/mode'] != 'view_document_envelope') || !$values['first_tab/show_thumbnail_image']);
		$this->showHideImageOptions($fields, $values, 'first_tab', $hideImageSettings);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//..
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$modePath = $this->getPathFromMode($values['global_area/mode']);
		$feaPath = $box['key']['feaPath'] ?? false;
		if ($modePath == $feaPath) {
			ze\tuix::saveOverridePhrases($box, $values, $feaPath);
		}
	}
}