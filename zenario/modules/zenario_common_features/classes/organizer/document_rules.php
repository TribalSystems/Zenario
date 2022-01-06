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

class zenario_common_features__organizer__document_rules extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//ze\datasetAdm::listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
		$panel['columns']['field_id']['values'] = ze\datasetAdm::listCustomFields('documents', false, ['!' => 'group', '!' => 'checkboxes', '!' => 'other_system_field'], true, true);
		$panel['columns']['folder_id']['values'] = ze\miscAdm::generateDocumentFolderSelectList(true);
		
		//Look up all of the fields that have been used
		$lovs = [];
		foreach (array_unique(ze\row::getValues('document_rules', 'field_id', ['action' => 'set_field', 'replacement_is_regexp' => 0])) as $fieldId) {
			$lovs[$fieldId] = ze\dataset::fieldLOV($fieldId);
		}
		
		//For each row, attempt to show LOV values if they have been used.
		//Otherwise show the raw data
		foreach ($panel['items'] as $id => &$item) {
			switch ($item['action']) {
				case 'move_to_folder':
					$item['field_id'] = '';
					break;
				
				case 'set_field':
					$item['folder_id'] = '';
					
					if (!$item['replacement_is_regexp']
					 && isset($lovs[$item['field_id']][$item['replacement']])) {
				
						$item['set_to'] = $lovs[$item['field_id']][$item['replacement']];
				
					} else {
						$item['set_to'] = $item['replacement'];
					}
			}
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if ($_POST['reorder'] ?? false) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\row::update('document_rules', ['ordinal' => $_POST['ordinals'][$id]], $id);
			}
			
		} elseif ($_POST['duplicate'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if ($rule = ze\row::get('document_rules', true, $id)) {
					$sql = "
						UPDATE ". DB_PREFIX. "document_rules
						SET ordinal = ordinal + 1
						WHERE ordinal > ". (int) $rule['ordinal'];
					ze\sql::update($sql);
					
					unset($rule['id']);
					$rule['ordinal'] = 1 + (int) $rule['ordinal'];
					
					return ze\row::insert('document_rules', $rule);
				}
			}
			
		} elseif ($_POST['delete'] ?? false) {
			ze\priv::exitIfNot('_PRIV_EDIT_DOCUMENTS');
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\row::delete('document_rules', $id);
			}
		}
		
		//Tidy up the ordinals
		$i = 0;
		foreach (ze\row::getValues('document_rules', 'id', [], 'ordinal') as $id) {
			ze\row::update('document_rules', ['ordinal' => ++$i], $id);
		}
	}
	
}