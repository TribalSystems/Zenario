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

class zenario_common_features__organizer__custom_tabs_and_fields extends module_base_class {
	
	public static function sortByOrdinal($a, $b) {
		if ((float) $a['ordinal'] == (float) $b['ordinal']) {
			return 0;
		}
		return ((float) $a['ordinal'] < (float) $b['ordinal']) ? -1 : 1;
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//Load details on this data-set
		if ($dataset = getDatasetDetails($refinerId)) {
			
			$panel['title'] = adminPhrase('Managing the dataset "[[label]]"', $dataset);
			
			//If this extends a system admin box, load the system tabs and fields
			if ($dataset['extends_admin_box']) {
				
				//Read system tabs and fields from TUIX
				$moduleFilesLoaded = array();
				$tags = array();
				loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box']);
				
				//Loop through system tabs
				if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
				 && is_array($tags[$dataset['extends_admin_box']]['tabs'])) {
					$tabCount = 0;
					foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
						if (is_array($tab)) {
							
							$panel['items'][$tabName] = array(
								'is_tab' => true,
								'is_system' => true,
								'css_class' => 'system_tab_visible',
								//'disable_reorder' => true,
								'ordinal' => (float) ifNull(arrayKey($tab, 'ord'), ++$tabCount),
								'type' => 'system_tab',
								'label' => ifNull(arrayKey($tab, 'dataset_label'), arrayKey($tab, 'label')),
								'tab_name' => $tabName
							);
							
							//Loop through system fields
							if (!empty($tab['fields'])
							 && is_array($tab['fields'])) {
								$fieldCount = 0;
								foreach ($tab['fields'] as $fieldName => $field) {
									if (is_array($field)) {
										
										$id = '___system_field___'. $tabName. '___'. $fieldName;
										
										$panel['items'][$id] = array(
											'is_field' => true,
											'is_system' => true,
											'css_class' => 'system_field_visible',
											//'disable_reorder' => true,
											'ordinal' => (float) ifNull(arrayKey($field, 'ord'), ++$fieldCount),
											'type' => 'system_field',
											'label' => ifNull(arrayKey($field, 'dataset_label'), arrayKey($field, 'label')),
											'parent_id' => $tabName,
											'tab_name' => $tabName,
											'field_name' => $fieldName
										);
								
										//if (isset($panel['items'][$tabName])) {
											$panel['items'][$tabName]['has_children'] = true;
										//}
									}
								}
							}
						}
					}
				}
			}
			
			foreach(getRowsArray('custom_dataset_tabs', true, array('dataset_id' => $refinerId)) as $ctab) {
				
				//Look for custom tabs
				if (chopPrefixOffOfString($ctab['name'], '__custom_tab_') !== false) {
					
					$panel['items'][$ctab['name']] = array(
						'is_tab' => true,
						'ordinal' => (float) $ctab['ord'],
						'type' => 'tab',
						'label' => $ctab['label'],
						'tab_name' => $ctab['name'],
						'css_class' => 'custom_tab_visible'
					);
					
					$parents = array();
					getCustomTabsParents($ctab, $parents);
					
					if (!empty($parents)) {
						$panel['items'][$ctab['name']]['parents'] = '';
						foreach ($parents as $parent) {
							if ($parent['label']) {
								$panel['items'][$ctab['name']]['parents'] .= str_replace(':', '', $parent['label']). ' -> ';
							} else {
								$panel['items'][$ctab['name']]['parents'] .= $parent['field_name']. ' -> ';
							}
						}
					}
				
				//Look for customised system tabs
				} elseif ($dataset['extends_admin_box']) {
					if (isset($panel['items'][$ctab['name']])) {
						if ($ctab['ord']) {
							//Don't change the ordinal of the one we moved to the start
							if ($panel['items'][$ctab['name']]['ordinal'] != 0) {
								$panel['items'][$ctab['name']]['ordinal'] = $ctab['ord'];
							}
						}
						if ($ctab['label']) {
							$panel['items'][$ctab['name']]['label'] = $ctab['label'];
						}
					}
				}
			}
			
			//Look for custom fields
			foreach(getRowsArray(
				'custom_dataset_fields',
				true,
				array('dataset_id' => $refinerId, 'is_system_field' => 0)
			) as $id => $field) {
				
				$panel['items'][$id] = array(
					'is_field' => true,
					'parent_id' => $field['tab_name'],
					'tab_name' => $field['tab_name'],
					'db_column' => $field['db_column'],
					'ordinal' => (float) $field['ord'],
					'type' => $field['type'],
					'label' => $field['label'],
					'protected' => $field['protected'],
					'css_class' => $field['protected']? 'custom_field_protected_visible' : 'custom_field_visible'
					//'css_class' => 'zenario_char_'. $field['type'],
					//('group', 'checkbox', 'checkboxes', 'date', 'editor', 'radios', 'select', 'text', 'textarea', 'url')
				);
				
				$countSetting = setting('dataset_field_used_count');
				
				switch($countSetting) {
					case 'always':
						$panel['items'][$id]['user_count'] = countDatasetFieldRecords($field);
						break;
					case 'never':
						unset($panel['columns']['user_count']);
						break;
					case 'if_indexed':
						if ($field['sortable']) {
							$panel['items'][$id]['user_count'] = countDatasetFieldRecords($field);
						}
						break;
				}
				
				if (isset($panel['items'][$id]['user_count'])) {
					if ($panel['items'][$id]['user_count'] == 0) {
						$panel['items'][$id]['user_count'] = "Not used";
					} elseif ($panel['items'][$id]['user_count'] == 1) {
						$panel['items'][$id]['user_count'] = "1 record";
					} elseif ($panel['items'][$id]['user_count'] > 1) {
						$panel['items'][$id]['user_count'] .= " records";
					}
				}
				
				
				$parents = array();
				getCustomFieldsParents($field, $parents);
				
				if (!empty($parents)) {
					$panel['items'][$id]['parents'] = '';
					foreach ($parents as $parent) {
						if ($parent['label']) {
							$panel['items'][$id]['parents'] .= str_replace(':', '', $parent['label']). ' -> ';
						} else {
							$panel['items'][$id]['parents'] .= $parent['field_name']. ' -> ';
						}
					}
				}
				
				//if (isset($panel['items'][$field['tab_name']])) {
					$panel['items'][$field['tab_name']]['has_children'] = true;
				//}
			}
			
			//Look for customised system fields
			foreach(getRowsArray(
				'custom_dataset_fields',
				true,
				array('dataset_id' => $refinerId, 'is_system_field' => 1)
			) as $field) {
				
				$id = '___system_field___'. $field['tab_name']. '___'. $field['field_name'];
				
				if (isset($panel['items'][$id])) {
					if ($field['ord']) {
						$panel['items'][$id]['ordinal'] = $field['ord'];
					}
					if ($field['label']) {
						$panel['items'][$id]['label'] = $field['label'];
					}
				}
			}
			
		}
	}
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		//Load details on this data-set
		if ($dataset = getDatasetDetails($refinerId)) {
		
			//Delete empty tabs
			if (post('delete_tab') && checkPriv('_PRIV_MANAGE_DATASET')) {
				
				foreach (explode(',', $ids) as $id) {
					if (!checkRowExists('custom_dataset_fields', array('dataset_id' => $refinerId, 'tab_name' => $id))) {
						deleteRow('custom_dataset_tabs', array('dataset_id' => $refinerId, 'name' => $id));
					}
				}
		
			} else if (post('reorder') && checkPriv('_PRIV_MANAGE_DATASET')) {
			
				//Handling reordering tabs and fields
				$customTabs = array();
				$systemTabs = array();
				$customFields = array();
				$systemFields = array();
			
				$errorTabsMustBeTopLevel = false;
				$errorFieldsMustBeChildren = false;
				$errorSystemFieldsCannotBeMovedBetweenTabs = false;
			
				foreach (explode(',', $ids) as $id) {
				
					$details = array(
						'id' => $id,
						'parent' => arrayKey($_POST, 'parent_ids', $id),
						'ord' => arrayKey($_POST, 'ordinals', $id));
				
					if (chopPrefixOffOfString($id, '__custom_tab_') !== false) {
						$details['is_field'] = false;
						$details['tab_name'] = $id;
						$customTabs[$id] = $details;
				
					} elseif ($explode = chopPrefixOffOfString($id, '___system_field___')) {
						if (($explode = explode('___', $explode))
						 && (!empty($explode[0]))
						 && (!empty($explode[1]))) {
						
							$details['is_field'] = true;
							$details['tab_name'] = $explode[0];
							$details['field_name'] = $explode[1];
							$systemFields[$id] = $details;
						
							if ($details['tab_name'] != $details['parent']) {
								$errorSystemFieldsCannotBeMovedBetweenTabs = true;
							}
						} else {
							continue;
						}
				
					} elseif (is_numeric($id)) {
						$details['is_field'] = true;
						$details['tab_name'] = $details['parent'];
						$customFields[$id] = $details;
				
					} else {
						$details['is_field'] = false;
						$details['tab_name'] = $id;
						$systemTabs[$id] = $details;
					}
				
					if ($details['is_field']) {
						if (!$details['parent']) {
							$errorFieldsMustBeChildren = true;
						}
					} else {
						if ($details['parent']) {
							$errorTabsMustBeTopLevel = true;
						}
					}
				}
			
				if ($errorTabsMustBeTopLevel
				 || $errorFieldsMustBeChildren
				 || $errorSystemFieldsCannotBeMovedBetweenTabs) {
				
					echo '<!--Message_Type:Error-->';
					
					//if ($errorTabsMustBeTopLevel) {
					//	echo adminPhrase('Tabs must be placed at the top level.'). ' ';
					//}
					//if ($errorFieldsMustBeChildren) {
					//	echo adminPhrase('Fields must be placed on the second level under a tab.'). ' ';
					//}
					//if ($errorSystemFieldsCannotBeMovedBetweenTabs) {
					//	echo adminPhrase('System fields may not be moved between tabs.'). ' ';
					//}
					
					echo adminPhrase('Tabs must be placed at the top level and fields must be placed on the second level under a tab.');
					
					if ($dataset['extends_admin_box']) {
						echo "\n\n";
						//echo adminPhrase('The first system tab may not be moved, and system fields may not be moved between tabs.');
						echo adminPhrase('System fields may not be moved between tabs.');
					}
					
					exit;
				}
			
				foreach ($systemTabs as $details) {
					setRow(
						'custom_dataset_tabs',
						array(
							'ord' => $details['ord']),
						array(
							'dataset_id' => $refinerId,
							'name' => $details['tab_name']));
				}
			
				foreach ($customTabs as $details) {
					updateRow(
						'custom_dataset_tabs',
						array(
							'ord' => $details['ord']),
						array(
							'dataset_id' => $refinerId,
							'name' => $details['tab_name']));
				}
			
				foreach ($systemFields as $details) {
					setRow(
						'custom_dataset_fields',
						array(
							'ord' => $details['ord']),
						array(
							'dataset_id' => $refinerId,
							'tab_name' => $details['tab_name'],
							'field_name' => $details['field_name'],
							'is_system_field' => 1));
				}
			
				foreach ($customFields as $details) {
					updateRow(
						'custom_dataset_fields',
						array(
							'tab_name' => $details['tab_name'],
							'ord' => $details['ord']),
						array(
							'dataset_id' => $refinerId,
							'id' => $details['id'],
							'is_system_field' => 0));
				}
			
			} elseif (post('delete_field') && checkPriv('_PRIV_MANAGE_DATASET')) {
				deleteDatasetField($ids);
			}
		}
	}
}