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


class zenario_common_features__admin_boxes__content_type_details extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$box['tabs']['details']['fields']['default_layout_id']['pick_items']['path'] =
			'zenario__content/panels/content_types/hidden_nav/layouts//'. $box['key']['id']. '//';
		
		foreach (getContentTypeDetails($box['key']['id']) as $col => $value) {
			if ($col == 'enable_categories') {
				$box['tabs']['details']['fields'][$col]['value'] = $value ? 'enabled' : 'disabled';
			} else {
				$box['tabs']['details']['fields'][$col]['value'] = $value;
			}
		}
		
		switch ($box['key']['id']) {
			case 'html':
			case 'document':
			case 'picture':
			case 'video':
			case 'audio':
				//HTML, Document, Picture, Video and Audio fields cannot currently be mandatory
				foreach (array('description_field', 'keywords_field', 'summary_field', 'release_date_field') as $field) {
					$box['tabs']['details']['fields'][$field]['values']['mandatory']['hidden'] = true;
				}
				
				break;
				
			
			case 'event':
				//Event release dates must be hidden as it is overridden by another field
				$box['tabs']['details']['fields']['release_date_field']['hidden'] = true;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['details/default_layout_id'] || !($template = getTemplateDetails($values['details/default_layout_id']))) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please select a default layout.');
		
		} elseif ($template['status'] != 'active') {
			$box['tabs']['details']['errors'][] = adminPhrase('The default layout must be an active layout.');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (checkPriv('_PRIV_EDIT_CONTENT_TYPE')) {
			
			$vals = array(
				'content_type_name_en' => $values['details/content_type_name_en'],
				'description_field' => $values['details/description_field'],
				'keywords_field' => $values['details/keywords_field'],
				'writer_field' => $values['details/writer_field'],
				'summary_field' => $values['details/summary_field'],
				'release_date_field' => $values['details/release_date_field'],
				'enable_summary_auto_update' => $values['details/enable_summary_auto_update'],
				'enable_categories' => ($values['details/enable_categories'] == 'enabled') ? 1 : 0,
				'default_layout_id' => $values['details/default_layout_id']);
			
			if ($values['details/summary_field'] == 'hidden') {
				$vals['enable_summary_auto_update'] = 0;
			}
			
			switch ($box['key']['id']) {
				case 'document':
				case 'picture':
				case 'html':
					//HTML/Document/Picture fields cannot currently be mandatory
					foreach (array('description_field', 'keywords_field', 'summary_field', 'release_date_field') as $field) {
						if ($vals[$field] == 'mandatory') {
							$vals[$field] = 'optional';
						}
					}
					
					break;
					
				
				case 'event':
					//Event release dates must be hidden as it is overridden by another field
					$vals['release_date_field'] = 'hidden';
			}
			
			updateRow('content_types', $vals, $box['key']['id']);
		}
	}
}
