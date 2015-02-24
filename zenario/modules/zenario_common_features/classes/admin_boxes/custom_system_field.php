<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

class zenario_common_features__admin_boxes__custom_system_field extends module_base_class {
	
	protected function loadFieldDetails(&$box, &$tField, &$cField) {
		
		$dataset = getDatasetDetails($box['key']['dataset_id']);
		
		//Load details on this field
		$moduleFilesLoaded = array();
		$tags = array();
		loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box']);
		
		if (empty($tags[$dataset['extends_admin_box']]['tabs'][$box['key']['tab_name']]['fields'][$box['key']['field_name']])) {
			echo adminPhrase('The field you are trying to edit no longer exists');
			exit;
		} else {
			$tField = $tags[$dataset['extends_admin_box']]['tabs'][$box['key']['tab_name']]['fields'][$box['key']['field_name']];
		}
		
		$cField = getDatasetSystemFieldDetails($box['key']['dataset_id'], $box['key']['tab_name'], $box['key']['field_name']);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Make sure we have a dataset id set
		if (!$box['key']['dataset_id']) {
			if (!$box['key']['dataset_id'] = request('refiner__dataset_id')) {
				exit;
			}
		}
		
		//Work out the field and tab name
		if (!$box['key']['tab_name']
		 || !$box['key']['field_name']) {
			
			$details = explode('___', $box['key']['id']);
			if (count($details) == 4) {
				$box['key']['tab_name'] = $details[2];
				$box['key']['field_name'] = $details[3];
				
				if (!$box['key']['tab_name']
				 || !$box['key']['field_name']) {
					echo adminPhrase('Could not find the details of this field from the request.');
					exit;
				}
			}
		}
		
		
		//Attempt to get details of this field from the TUIX description
		$tField = $cField = false;
		$this->loadFieldDetails($box, $tField, $cField);
		
		$label = ifNull(arrayKey($tField, 'dataset_label'), arrayKey($tField, 'label'));
		$type = arrayKey($tField, 'type');
		$note_below = arrayKey($tField, 'note_below');
		
		//Only show the select field type from the list, or show "other_system_field" if the type is not in the list
		if (!$type || empty($fields['details/type']['values'][$type])) {
			$type = 'other_system_field';
		}
		$fields['details/type']['value'] = $type;
		$fields['details/type']['values'] = array($type => $fields['details/type']['values'][$type]);
		
		$values['details/label'] = $label;
		$values['display/note_below'] = $note_below;
		$values['display/type'] = $type;
		
		
		//Load the overrides
		if ($cField) {
			if ($cField['label']) {
				$values['details/label'] = $cField['label'];
			}
			if ($cField['note_below']) {
				$values['display/note_below'] = $cField['note_below'];
			}
		}
		
		
		$box['title'] = adminPhrase('Editing the system field "[[label]]"', $tField);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$tField = $cField = false;
		$this->loadFieldDetails($box, $tField, $cField);
		
		//Save the overrides (using setRow() to create a new row or update an existing row as needed).
		//But if an "override" is set to the same as the original value, then an empty string should be saved instead.
		setRow('custom_dataset_fields',
			array(
				'label' => ifNull(arrayKey($tField, 'dataset_label'), arrayKey($tField, 'label')) == $values['details/label']?
					'' : $values['details/label'],
				'note_below' => arrayKey($tField, 'note_below') == $values['display/note_below']?
					'' : $values['display/note_below']),
			array(
				'dataset_id' => $box['key']['dataset_id'],
				'tab_name' => $box['key']['tab_name'],
				'field_name' => $box['key']['field_name'],
				'is_system_field' => 1)
		);
		
	}
}
