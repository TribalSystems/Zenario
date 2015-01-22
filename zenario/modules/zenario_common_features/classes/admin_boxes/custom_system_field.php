<?php
/*
 * Copyright (c) 2014, Tribal Limited
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
	
	protected function loadFieldDetails(&$box) {
		
		$details = getDatasetDetails($box['key']['dataset_id']);
		
		//Load details on this field
		$moduleFilesLoaded = array();
		$tags = array();
		loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $details['extends_admin_box']);
		
		if (empty($tags[$details['extends_admin_box']]['tabs'][$box['key']['tab_name']]['fields'][$box['key']['field_name']])) {
			echo adminPhrase('The field you are trying to edit no longer exists');
			exit;
		} else {
			return $tags[$details['extends_admin_box']]['tabs'][$box['key']['tab_name']]['fields'][$box['key']['field_name']];
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!$box['key']['dataset_id']) {
			if (!$box['key']['dataset_id'] = request('refiner__dataset_id')) {
				exit;
			}
		}
		
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
		
		$field = $this->loadFieldDetails($box);
		$values['details/label'] = ifNull(arrayKey($field, 'dataset_label'), arrayKey($field, 'label'));
		$values['details/note_below'] = arrayKey($field, 'note_below');
		
		if ($details = getDatasetSystemFieldDetails($box['key']['dataset_id'], $box['key']['tab_name'], $box['key']['field_name'])) {
			if ($details['label']) $values['details/label'] = $details['label'];
			if ($details['note_below']) $values['details/note_below'] = $details['note_below'];
		}
		
		$box['title'] = adminPhrase('Customising the system field "[[label]]"', $field);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$field = $this->loadFieldDetails($box);
		
		setRow('custom_dataset_fields',
			array(
				'label' => ifNull(arrayKey($field, 'dataset_label'), arrayKey($field, 'label')) == $values['details/label']?
					'' : $values['details/label'],
				'note_below' => arrayKey($field, 'note_below') == $values['details/note_below']?
					'' : $values['details/note_below']),
			array(
				'dataset_id' => $box['key']['dataset_id'],
				'tab_name' => $box['key']['tab_name'],
				'field_name' => $box['key']['field_name'],
				'is_system_field' => 1)
		);
		
	}
}