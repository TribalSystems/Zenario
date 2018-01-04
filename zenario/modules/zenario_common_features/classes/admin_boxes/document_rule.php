<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_common_features__admin_boxes__document_rule extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['id']) {
			if ($rule = getRow('document_rules', true, $box['key']['id'])) {
				$values['details/use'] = $rule['use'];
				$values['details/action'] = $rule['action'];
				$values['details/pattern'] = $rule['pattern'];
				$values['details/apply_second_pass'] = $rule['apply_second_pass'];
				$values['details/second_pattern'] = $rule['second_pattern'];
				$values['details/second_replacement'] = $rule['second_replacement'];
				$values['details/stop_processing_rules'] = $rule['stop_processing_rules'];
		
				switch ($values['details/action']) {
					case 'move_to_folder':
						$values['details/folder_id'] = $rule['folder_id'];
						break;
				
					case 'set_field':
						$values['details/field_id'] = $rule['field_id'];
						if ($values['details/replacement_is_regexp'] = $rule['replacement_is_regexp']) {
							$values['details/replacement'] = $rule['replacement'];
						} else {
							$values['details/field_value'] = $rule['replacement'];
						}
				}
				
				$box['title'] = adminPhrase('Editing auto-set rule [[ordinal]]', $rule);
			
			} else {
				exit;
			}
		} else {
			
		}
		
		//($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
		$fields['details/field_id']['values'] = listCustomFields('documents', false, array('!1' => 'dataset_select', '!2' => 'dataset_picker'), true, true);
		//($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false, $hideEmptyOptGroupParents = false)
		$fields['details/folder_id']['values'] = generateDocumentFolderSelectList();
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$fields['details/field_value']['values'] = array();
		$box['tabs']['details']['notices']['lov']['show'] =
		$box['tabs']['details']['notices']['date']['show'] =
		$box['tabs']['details']['notices']['checkbox']['show'] = false;
		
		
		$showField = false;
		$showFolder = false;
		$showReplacementValues = false;
		$showReplacementPattern = false;
		
		switch ($values['details/action']) {
			case 'move_to_folder':
				$showFolder = true;
		
				$fields['details/stop_processing_rules']['label'] =
					adminPhrase('Ignore any further folder moves if this rule matches');
				
				break;
				
			case 'set_field':
				$showField = true;
			
				if ($values['details/field_id']) {
					$field = getDatasetFieldDetails($values['details/field_id']);
					$fields['details/field_value']['values'] = getDatasetFieldLOV($field);
			
					$fields['details/stop_processing_rules']['label'] =
						adminPhrase('Stop processing rules for the field "[[label]]" if this rule matches', $field);
			
			
					switch ($field['type']) {
						case 'checkboxes':
						case 'radios':
						case 'select':
							//$showReplacementValues = true;
							//$values['details/replacement_is_regexp'] = 0;
							//$box['tabs']['details']['notices']['lov']['show'] = true;
							//break;
				
						case 'centralised_radios':
						case 'centralised_select':
							$showReplacementPattern = true;
							$showReplacementValues = true;
					
							if ($values['details/replacement_is_regexp']) {
								$box['tabs']['details']['notices']['lov']['show'] = true;
							}
							break;
				
						default:
							$showReplacementPattern = true;
							$values['details/replacement_is_regexp'] = 1;
					}
			
					if (!empty($box['tabs']['details']['notices'][$field['type']])) {
						$box['tabs']['details']['notices'][$field['type']]['show'] = true;
					}
			
			}
		}
		
		$fields['details/preg_match_help']['hidden'] = $showReplacementPattern;
		$fields['details/preg_replace_help']['hidden'] = !$showReplacementPattern;
		
		$fields['details/field_id']['hidden'] = !$showField;
		$fields['details/folder_id']['hidden'] = !$showFolder;
		$fields['details/stop_processing_rules']['hidden'] = !($showFolder || $showReplacementPattern || $showReplacementValues);
		$fields['details/replacement_is_regexp']['hidden'] = !$showReplacementPattern && !$showReplacementValues;
		$fields['details/replacement_is_regexp']['values'][0]['hidden'] = !$showReplacementValues;
		$fields['details/replacement_is_regexp']['values'][1]['hidden'] = !$showReplacementPattern;
		$fields['details/field_value']['hidden'] = !$showReplacementValues || $values['details/replacement_is_regexp'];
		$fields['details/replacement']['hidden'] = !$showReplacementPattern || !$values['details/replacement_is_regexp'];
		
		$fields['details/apply_second_pass']['hidden'] = !$showReplacementPattern;
		$fields['details/second_pattern']['hidden'] = !$showReplacementPattern || !$values['details/apply_second_pass'];
		$fields['details/second_replacement']['hidden'] = !$showReplacementPattern || !$values['details/apply_second_pass'];
	}
	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$values['details/use']) {
			$fields['details/use']['error'] = adminPhrase('Please select which part of the filename to check');
		}
		
		if (!$values['details/pattern']) {
			$fields['details/pattern']['error'] = adminPhrase('Please enter a pattern');
		} else {
			$return = false;
			
			if (@preg_match($values['details/pattern'], null) === false) {
				$return = false;
			} else {
				$return = preg_match($values['details/pattern'], 'test');
			}
	
			if ($return === false) {
				$fields['details/pattern']['error'] = adminPhrase('The pattern is invalid');
			}
		}
		
		//var_dump($values['details/action']);
		switch ($values['details/action']) {
			case 'move_to_folder':
				if (!$values['details/folder_id']) {
					$fields['details/folder_id']['error'] = adminPhrase('Please select a folder');
				}
				break;
			
			case 'set_field':
				if (!$values['details/field_id']) {
					$fields['details/field_id']['error'] = adminPhrase('Please select a field');
				
				} else {
					if ($values['details/replacement_is_regexp']) {
						if (!$values['details/replacement']) {
							$fields['details/replacement']['error'] = adminPhrase('Please enter a replacement pattern');
						} else {
							$return = null;
							try {
								$return = preg_replace('@(a)(b)(c)(d)(e)(f)(g)(h)(i)(j)(k)(l)(m)(n)(o)(p)(q)(r)(s)(t)(u)(v)(w)(x)(y)(z)@', $values['details/replacement'], 'test');
							} catch (Exception $e) {
								$return = null;
							}
			
							if ($return === null) {
								$fields['details/replacement']['error'] = adminPhrase('The replacement pattern is invalid');
							}
						}
						
						if ($values['details/apply_second_pass']) {
							if (!$values['details/second_pattern']) {
								$fields['details/second_pattern']['error'] = adminPhrase('Please enter a second pattern');
							
							} else {
								$return = false;
								try {
									$return = preg_match($values['details/second_pattern'], 'test');
								} catch (Exception $e) {
									$return = false;
								}
	
								if ($return === false) {
									$fields['details/second_pattern']['error'] = adminPhrase('The second pattern is invalid');
								}
							}
							
							if (!$values['details/second_replacement']) {
								$fields['details/second_replacement']['error'] = adminPhrase('Please enter a second replacement pattern');
							} else {
								$return = null;
								try {
									$return = preg_replace('@(a)(b)(c)(d)(e)(f)(g)(h)(i)(j)(k)(l)(m)(n)(o)(p)(q)(r)(s)(t)(u)(v)(w)(x)(y)(z)@', $values['details/second_replacement'], 'test');
								} catch (Exception $e) {
									$return = null;
								}
			
								if ($return === null) {
									$fields['details/second_replacement']['error'] = adminPhrase('The second replacement pattern is invalid');
								}
							}
							
						}
					}
				}
				
				break;
			
			default:
				$fields['details/action']['error'] = adminPhrase('Please select an action');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$rule = array(
			'use' => $values['details/use'],
			'action' => $values['details/action'],
			'pattern' => $values['details/pattern'],
			'field_id' => $values['details/field_id'],
			'stop_processing_rules' => $values['details/stop_processing_rules'],
			'replacement' => '',
			'apply_second_pass' => 0,
			'second_pattern' => '',
			'second_replacement' => '');
		
		if (!$box['key']['id']) {
			$sql = "
				SELECT 1 + IFNULL(MAX(ordinal), 0)
				FROM ". DB_NAME_PREFIX. "document_rules";
			$result = sqlSelect($sql);
			$row = sqlFetchRow($result);
			$rule['ordinal'] = $row[0];
		}
		
		switch ($values['details/action']) {
			case 'move_to_folder':
				$rule['field_id'] = 0;
				$rule['folder_id'] = $values['details/folder_id'];
				$rule['replacement_is_regexp'] = 0;
				break;
			
			case 'set_field':
				$rule['field_id'] = $values['details/field_id'];
				$rule['folder_id'] = 0;
				if ($rule['replacement_is_regexp'] = $values['details/replacement_is_regexp']) {
					$rule['replacement'] = $values['details/replacement'];
					
					if ($values['details/apply_second_pass']) {
						$rule['apply_second_pass'] = 1;
						$rule['second_pattern'] = $values['details/second_pattern'];
						$rule['second_replacement'] = $values['details/second_replacement'];
					}
					
				} else {
					$rule['replacement'] = $values['details/field_value'];
				}
				
				break;
		}
		
		$box['key']['id'] = setRow('document_rules', $rule, $box['key']['id']);
	}
}