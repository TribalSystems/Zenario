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

class zenario_common_features__admin_boxes__custom_system_tab extends module_base_class {
	
	protected function loadTabDetails(&$box) {
		
		$details = getDatasetDetails($box['key']['dataset_id']);
		
		//Load details on this tab
		$moduleFilesLoaded = array();
		$tags = array();
		loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $details['extends_admin_box']);
		
		if (empty($tags[$details['extends_admin_box']]['tabs'][$box['key']['id']])) {
			echo adminPhrase('The tab you are trying to edit no longer exists');
			exit;
		} else {
			return $tags[$details['extends_admin_box']]['tabs'][$box['key']['id']];
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!$box['key']['dataset_id']) {
			if (!$box['key']['dataset_id'] = request('refiner__dataset_id')) {
				exit;
			}
		}
		
		if (!$box['key']['id']) {
			exit;
		}
		
		$tab = $this->loadTabDetails($box);
		$values['details/label'] = ifNull(arrayKey($tab, 'dataset_label'), arrayKey($tab, 'label'));
		
		if ($details = getDatasetTabDetails($box['key']['dataset_id'], $box['key']['id'])) {
			if ($details['label']) $values['details/label'] = $details['label'];
		}
		
		$box['title'] = adminPhrase('Renaming the system tab "[[label]]"', $tab);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$tab = $this->loadTabDetails($box);
		
		setRow('custom_dataset_tabs',
			array(
				'label' => ifNull(arrayKey($tab, 'dataset_label'), arrayKey($tab, 'label')) == $values['details/label']?
					'' : $values['details/label']),
			array(
				'dataset_id' => $box['key']['dataset_id'],
				'name' => $box['key']['id'])
		);
		
	}
}
