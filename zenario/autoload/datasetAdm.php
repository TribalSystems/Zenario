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



namespace ze;

class datasetAdm {




	//Formerly "registerDataset()"
	public static function register(
		$label, $table, $system_table = '',
		$extends_admin_box = '', $extends_organizer_panel = '',
		$view_priv = '', $edit_priv = ''
	) {
		$datasetId = \ze\row::set(
						'custom_datasets',
						[
							'label' => $label,
							'system_table' => $system_table,
							'extends_admin_box' => $extends_admin_box,
							'extends_organizer_panel' => $extends_organizer_panel,
							'view_priv' => $view_priv,
							'edit_priv' => $edit_priv],
						['table' => $table]);
		\ze\miscAdm::saveSystemFieldsFromTUIX($datasetId);
		return $datasetId;
	}

	//Formerly "registerDatasetSystemField()"
	public static function registerSystemField($datasetId, $type, $tabName, $fieldName, $dbColumn = false, $validation = 'none', $valuesSource = '', $fundamental = false, $isRecordName = false) {
	
		if ($dbColumn === false) {
			$dbColumn = $fieldName;
		}
	
		//Try to catch the case where a system field was automatically registered
		if ($fieldId = \ze\row::get(
			'custom_dataset_fields',
			'id',
			[
				'dataset_id' => $datasetId,
				'tab_name' => $tabName,
				'field_name' => $fieldName,
				'is_system_field' => 1]
		)) {
		
			//In this case, update the existing record and register it properly
			\ze\row::update(
				'custom_dataset_fields',
				[
					'type' => $type,
					'db_column' => $dbColumn,
					'validation' => $validation,
					'values_source' => $valuesSource,
					'fundamental' => $fundamental],
				$fieldId
			);
	
		} else {
			//Otherwise register a new field
			$fieldId = 
				\ze\row::set(
					'custom_dataset_fields',
					[
						'type' => $type,
						'tab_name' => $tabName,
						'field_name' => $fieldName,
						'validation' => $validation,
						'values_source' => $valuesSource,
						'fundamental' => $fundamental],
					['dataset_id' => $datasetId, 'db_column' => $dbColumn, 'is_system_field' => 1]);
		}
	
		if ($isRecordName) {
			\ze\row::update('custom_datasets', ['label_field_id' => $fieldId], $datasetId);
		}
	
		return $fieldId;
	}

	//Formerly "deleteDatasetField()"
	public static function deleteField($fieldId) {
		if (($field = \ze\dataset::fieldDetails($fieldId))
		 && ($dataset = \ze\dataset::details($field['dataset_id']))
		 && (!$field['protected'])
		 && (!$field['is_system_field'])) {
		
			\ze\module::sendSignal('eventDatasetFieldDeleted', ['datasetId' => $dataset['id'], 'fieldId' => $field['id']]);
		
			if ($field['type'] == 'file_picker') {
				$sql = "
					DELETE FROM ". DB_NAME_PREFIX. "custom_dataset_files_link
					WHERE field_id = ". (int) $field['id']. "
					  AND dataset_id = ". (int) $dataset['id'];
			
				if (\ze\sql::update($sql)) {
					\ze\dataset::removeUnusedFiles();
				}
		
			} elseif ($field['type'] == 'checkboxes') {
				$sql = "
					DELETE fv.*, vl.*
					FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS fv
					INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS vl
					ON vl.value_id = fv.id
					WHERE fv.field_id = ". (int) $field['id'];
				\ze\sql::update($sql);
		
			} elseif ($field['type'] != 'repeat_end') {
				$sql = "
					ALTER TABLE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`
					DROP COLUMN `". \ze\escape::sql($field['db_column']). "`";
				if ($field['repeat_start_id']) {
					$rows = \ze\row::get('custom_dataset_fields', 'max_rows', $field['repeat_start_id']);
					for ($i = 2; $i <= $rows; $i++) {
						$sql .= ",
							DROP COLUMN `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($field['db_column'], $i)) . "`";
					}
				}
				\ze\sql::update($sql);
			}
		
			\ze\row::delete('custom_dataset_fields', $field['id']);
			\ze\row::delete('custom_dataset_field_values', ['field_id' => $field['id']]);
		
			return true;
		} else {
			return false;
		}
	}

	//Formerly "deleteDataset()"
	public static function delete($dataset) {
		if ($dataset = \ze\dataset::details($dataset)) {
			$sql = "
				DELETE cd.*, t.*, f.*, fv.*, vl.*
				FROM ". DB_NAME_PREFIX. "custom_datasets AS cd
				LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_tabs AS t
				   ON t.dataset_id = cd.id
				LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS f
				   ON f.dataset_id = cd.id
				LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_field_values AS fv
				   ON fv.field_id = f.id
				LEFT JOIN ". DB_NAME_PREFIX. "custom_dataset_values_link AS vl
				   ON vl.value_id = fv.id
				WHERE cd.id = ". (int) $dataset['id'];
			\ze\sql::update($sql);
		}
	}

	//Formerly "getDatasetFieldDefinition()"
	public static function fieldDefinition($field) {
	
		switch ($field['type']) {
			case 'checkboxes':
			case 'file_picker':
			case 'repeat_end':
				return '';
		
			case 'repeat_start':
			case 'checkbox':
			case 'group':
				return " tinyint(1) NOT NULL default 0";
		
			case 'radios':
			case 'select':
			case 'dataset_select':
			case 'dataset_picker':
				return " int(10) unsigned NOT NULL default 0";
		
			case 'text':
			case 'url':
				if (!$field['create_index']) {
					return " TINYTEXT";
				}
			
			case 'centralised_radios':
			case 'centralised_select':
				return " varchar(255) NOT NULL default ''";
		
			case 'editor':
			case 'textarea':
				return " TEXT";
		
			case 'date':
				return " date";
		
			default:
				return false;
		}
	}

	//Formerly "listCustomFields()"
	public static function listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false, $hideEmptyOptGroupParents = false) {
		$dataset = \ze\dataset::details($dataset);
	
		$key = [];
	
		if (is_array($filter)) {
			if (isset($filter['type'])
			 || isset($filter['values_source'])) {
				$key = $filter;
			} else {
				$key['type'] = $filter;
			}
	
		} else {
			switch ($filter) {
				case 'boolean_and_list_only':
					$key['type'] = ['checkbox', 'checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'];
					break;
		
				case 'group_boolean_and_list_only':
					$key['type'] = ['group', 'checkbox', 'checkboxes', 'radios', 'select', 'centralised_radios', 'centralised_select'];
					break;
		
				case 'text_only':
					$key['type'] = ['text', 'textarea', 'url'];
					break;
		
				case 'boolean_and_groups_only':
					$key['type'] = ['checkbox', 'group'];
					break;
		
				case 'groups_only':
					$key['type'] = 'group';
					break;
		
				default:
					if (in_array($filter, 
						[
							'group',
							'checkbox',
							'checkboxes',
							'date',
							'editor',
							'radios',
							'centralised_radios',
							'select',
							'centralised_select',
							'dataset_select',
							'dataset_picker',
							'text',
							'textarea',
							'url'
						]
					)) {
						$key['type'] = $filter;
					} else {
						$key['type'] = ['!' => 'other_system_field'];
					}
			}
		}
	
		if ($customOnly) {
			$key['is_system_field'] = 0;
		}
		$key['dataset_id'] = $dataset['id'];
	
		if ($flat) {
			$columns = ['is_system_field', 'label', 'default_label'];
		} else {
			$columns = ['tab_name', 'is_system_field', 'fundamental', 'field_name', 'type', 'db_column', 'label', 'default_label', 'ord'];
		}
	
		$fields = \ze\row::getArray('custom_dataset_fields', $columns, $key, 'ord');
		$existingParents = [];
		foreach ($fields as &$field) {
			if ($field['is_system_field'] && $field['label'] == '') {
				$field['label'] = $field['default_label'];
			}
		
			if ($flat) {
				$field = $field['label'];
			} else {
				if ($useOptGroups) {
					$existingParents[$field['parent'] = 'tab__'. $field['tab_name']] = true;
				}
			}
		}
	
	
		//Add opt-groups for each tab
		if ($useOptGroups) {
			foreach (\ze\row::getArray('custom_dataset_tabs', ['is_system_field', 'name', 'label', 'default_label', 'ord'], ['dataset_id' => $dataset['id']]) as $tab) {
			
				if ($hideEmptyOptGroupParents && empty($existingParents['tab__'. $tab['name']])) {
					continue;
				}
			
				if ($tab['is_system_field'] && $tab['label'] == '') {
					$tab['label'] = $tab['default_label'];
				}
			
				$fields['tab__'. $tab['name']] = $tab;
			}
		}
	
	
		return $fields;
	}
	
	public static function getFieldTypeDescription($type, $tuixType = false) {
		switch ($type) {
			case 'group':
				return 'Group';
			case 'checkbox':
				return 'Flag';
			case 'checkboxes':
				return 'Checkboxes';
			case 'date':
				return 'Date';
			case 'editor':
				return 'Editor';
			case 'radios':
				return 'Radios';
			case 'centralised_radios':
				return 'Centralised radios';
			case 'select':
				return 'Select';
			case 'centralised_select':
				return 'Centralised select';
			case 'text':
				return 'Text';
			case 'textarea':
				return 'Textarea';
			case 'url':
				return 'URL';
			case 'other_system_field':
				if ($tuixType) {
					switch ($tuixType) {
						case 'html_snippet':
							return 'HTML snippet';
						case 'pick_items':
							return 'Item picker';
						case 'toggle':
							return 'Toggle';
						case 'grouping':
							return 'Grouping';
						default:
							return 'Unknown';
					}
				} else {
					return 'Other system field';
				}
			case 'dataset_select':
				return 'Dataset select';
			case 'dataset_picker':
				return 'Dataset picker';
			case 'file_picker':
				return 'File picker';
			case 'repeat_start':
				return 'Start of repeating section';
			case 'repeat_end':
				return 'End of repeating section';
			default:
				return 'Unknown';
		}
	}

	//Formerly "getGroupPickerCheckboxesForFAB()"
	public static function getGroupPickerCheckboxesForFAB() {
		//Populate the list of groups
		$lov = \ze\datasetAdm::listCustomFields('users', $flat = false, 'groups_only', $customOnly = false, $useOptGroups = true, $hideEmptyOptGroupParents = true);
	
		$parents = [];
		foreach ($lov as &$v) {
			if (!empty($v['parent'])) {
				$parents[$v['parent']] = true;
			}
		}
	
		//If there is only one tab that has any groups, turn this into a flat list
		//by removing the tabs and making the groups top-level
		if (count($parents) < 2) {
			foreach ($lov as $i => &$v) {
				if (empty($v['parent'])) {
					unset($lov[$i]);
				} else {
					unset($v['parent']);
				}
			}
		} else {
			//Otherwise keep it as nested checkboxes. I want to show the tabs, but I don't want
			//people to be able to select them
			foreach ($lov as &$v) {
				if (empty($v['parent'])) {
					$v['readonly'] =
					$v['disabled'] = true;
					$v['style'] = 'display: none;';
				}
			}
		}

		return $lov;
	}

	//Given a field, get details on each of its child-fields.
	//Formerly "getCustomFieldsChildren()"
	public static function getCustomFieldsChildren($field, &$fields) {
	
		$sql = "
			SELECT *
			FROM ". DB_NAME_PREFIX. "custom_dataset_fields
			WHERE dataset_id = ". (int) $field['dataset_id']. "
			  AND parent_id = ". (int) $field['id']. "
			  AND is_system_field = 0";
		$result = \ze\sql::select($sql);
		while ($child = \ze\sql::fetchAssoc($result)) {
			if (!isset($fields[$child['id']])) {
				$fields[$child['id']] = $child;
				\ze\datasetAdm::getCustomFieldsChildren($child, $fields);
			}
		}
	}

	//Formerly "getCustomTabsParents()"
	public static function getCustomTabsParents($tab, &$fields) {
		$tab['parent_id'] = $tab['parent_field_id'];
		\ze\datasetAdm::getCustomFieldsParents($tab, $fields);
	}

	//Formerly "getCustomFieldsParents()"
	public static function getCustomFieldsParents($field, &$fields) {
		if (empty($field['parent_id'])) {
			return;
		}
	
		if ($parent = \ze\row::get(
			'custom_dataset_fields',
			['id', 'dataset_id', 'tab_name', 'field_name', 'db_column', 'is_system_field', 'label', 'parent_id'],
			['dataset_id' => $field['dataset_id'], 'id' => $field['parent_id']]
		)) {
			if (!isset($fields[$parent['id']])) {
			
				if (!$parent['is_system_field']) {
					$parent['field_name'] = '__custom_field__'. ($parent['db_column'] ?: $parent['id']);
				}
				$parent['tab_name/field_name'] = $parent['tab_name']. '/'. $parent['field_name'];
			
				$fields[$parent['id']] = $parent;
			
				\ze\datasetAdm::getCustomFieldsParents($parent, $fields);
			}
		}
	}

	//Make child fields only visible if their parents are visible and checked
	//Formerly "setChildFieldVisibility()"
	public static function setChildFieldVisibility(&$field, $tags) {
		$parents = [];
		\ze\datasetAdm::getCustomFieldsParents($field, $parents);

		if (!empty($parents)) {
			$firstParent = true;
			$field['visible_if'] = '';
		
			foreach ($parents as $parent) {
				$field['visible_if'] .=
					($field['visible_if']? ' && ' : '').
					"zenarioAB.value('". \ze\escape::js($parent['field_name']). "', '". \ze\escape::js($parent['tab_name']). "') == 1";
		
				//Attempt to set the redraw_onchange property for that field if it is on the same tab as this one
				//(This may miss custom fields, so we'll need to set any we've missed below)
				if (!empty($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
				 && ($parentField = $tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
				 && (is_array($parentField))
				 && $parent['tab_name'] == $field['tab_name']) {
				
					$parentField['redraw_onchange'] = true;
				
					//Look for the immediate parent. If it's on this tab, and above the field,
					//try to give this field a higher indent.
					if ($firstParent
					 && !empty($parentField['ord'])
					 && (float) $parentField['ord'] < (float) $field['ord']
					 && empty($field['indent'])) {
					
						if (empty($parentField['indent'])) {
							$field['indent'] = 1;
						} else {
							$field['indent'] = 1 + (int) $parentField['indent'];
						}
					
					}
				}
				$firstParent = false;
			}
		}
	}



	//Formerly "checkColumnExistsInDB()"
	public static function checkColumnExistsInDB($table, $column) {
	
		if (!$table || !$column) {
			return false;
		}
	
		$sql = "
			SHOW COLUMNS
			FROM `". DB_NAME_PREFIX. \ze\escape::sql($table). "`
			WHERE Field = '". \ze\escape::sql($column). "'";
		
		if ($row = \ze\sql::fetchAssoc($sql)) {
			return $row['Type'];
		}
		return false;
	}

	//Formerly "createDatasetFieldInDB()"
	public static function createFieldInDB($fieldId, $oldName = false) {
		if (($field = \ze\dataset::fieldDetails($fieldId))
		 && ($dataset = \ze\dataset::details($field['dataset_id']))) {
		
			$exists = \ze\datasetAdm::checkColumnExistsInDB($dataset['table'], $field['db_column']);
		
			$oldColType = false;
			if (!$exists && $oldName && $oldName != $field['db_column']) {
				$oldColType = \ze\datasetAdm::checkColumnExistsInDB($dataset['table'], $oldName);
			}
		
			$keys = [];
			if (($exists && ($columnName = $field['db_column']))
			 || ($oldColType && ($columnName = $oldName))) {
			
				$sql = "
					SHOW KEYS
					FROM `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`
					WHERE Column_name = '". \ze\escape::sql($columnName). "'";
				$result = \ze\sql::select($sql);
				while ($row = \ze\sql::fetchAssoc($result)) {
					if ($row['Key_name'] != 'PRIMARY') {
						$keys[$row['Key_name']] = $row['Key_name'];
					}
				}
			}
		
			//Get the column definition
			$def = \ze\datasetAdm::fieldDefinition($field);
			if ($def === false) {
				echo \ze\admin::phrase('Error: bad field type!');
				exit;
			}
		
			//Start building the query we'll need
			$sql = "
				ALTER TABLE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`";
		
			if ($def === '') {
				//Some fields (e.g. checkboxes, file pickers) don't store their values in the table
				if ($exists) {
					//If they do have a column here we need to drop it
					$sql .= "
						DROP COLUMN";
			
				} else {
					//Otherwise there's nothing to do
					return;
				}
			
			} else {
				//Rename an existing column
				if ($oldColType) {
					$sql .= "
						CHANGE COLUMN `". \ze\escape::sql($oldName). "` ";
					
					if ($field['create_index'] && $oldColType == 'TINYTEXT') {
						//Bugfix - when changing a TINYTEXT to a varchar, remove any null values
						\ze\sql::update("
							UPDATE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`
							SET `". \ze\escape::sql($oldName). "` = ''
							WHERE `". \ze\escape::sql($oldName). "` IS NULL
						");
					}
			
				//Modify an existing column
				} elseif ($exists) {
					$sql .= "
						MODIFY COLUMN";
			
				//Create a new column
				} else {
					$sql .= "
						ADD COLUMN";
				}
			}
		
			$sql .= " `". \ze\escape::sql($field['db_column']). "`";
		
			if ($def === false) {
				echo \ze\admin::phrase('Error: bad field type!');
				exit;
			} else {
				$sql .= $def;
			}
		
			//Drop any existing keys
			foreach ($keys as $key) {
				$sqlK = "
					ALTER TABLE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`
					DROP KEY `". \ze\escape::sql($key). "`";
				\ze\sql::update($sqlK);
			}
		
			//Update the column
			\ze\sql::update($sql);
		
		
			//Add a key if needed
			if ($def !== '' && $field['create_index']) {
				$sql = "
					ALTER TABLE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`
					ADD KEY (`". \ze\escape::sql($field['db_column']). "`";
			
				if ($field['type'] == 'editor' || $field['type'] == 'textarea') {
					$sql .= "(255)";
				}
			
				$sql .= ")";
				\ze\sql::update($sql);
			}
		}
	}
	
	public static function createFieldMultiRowsInDB($fieldId, $oldName = false, $newRows = false, $oldRows = false) {
		//Update extra columns for repeating fields
		if (($field = \ze\dataset::fieldDetails($fieldId))
			&& ($dataset = \ze\dataset::details($field['dataset_id']))
			&& ($newRows || $oldRows)
		) {
			$sql = '';
			
			$exists = \ze\datasetAdm::checkColumnExistsInDB($dataset['table'], $field['db_column']);
			
			$oldColType = false;
			if (!$exists && $oldName && $oldName != $field['db_column']) {
				$oldColType = \ze\datasetAdm::checkColumnExistsInDB($dataset['table'], $oldName);
			}
			
			//Get the column definition
			$def = \ze\datasetAdm::fieldDefinition($field);
			if ($def === false) {
				echo \ze\admin::phrase('Error: bad field type!');
				exit;
			}
			
			$start = false;
			$stop = false;
			$deleting = false;
		
			//Only create
			if (!$oldRows) {
				$start = 2;
				$stop = $newRows;
			//Only delete
			} elseif (!$newRows) {
				$start = 2;
				$stop = $oldRows;
				$deleting = true;
			//Update existing and create some new
			} elseif ($newRows >= $oldRows) {
				$start = $oldRows + 1;
				$stop = $newRows;
			//Update existing and delete some old
			} elseif ($newRows < $oldRows) {
				$start = $newRows + 1;
				$stop = $oldRows;
				$deleting = true;
			//Just renaming
			} elseif ($newRows == $oldRows) {
				$start = 2;
			}
		
			if ($start !== false && $stop !== false) {
				for ($i = 2; $i <= $stop; $i++) {
					if ($i >= $start) {
						if ($deleting) {
							$sql .= ",
								DROP COLUMN `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($field['db_column'], $i)) . "`";
						} else {
							$sql .= ",
								ADD COLUMN `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($field['db_column'], $i)) . "`" . $def;
						}
					} else {
						if ($oldColType) {
							$sql .= ",
								CHANGE COLUMN `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($oldName, $i)) . "` `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($field['db_column'], $i)) . "`" . $def;
						} elseif ($exists) {
							$sql .= ",
								MODIFY COLUMN `" . \ze\escape::sql(\ze\dataset::repeatRowColumnName($field['db_column'], $i)) . "`" . $def;
						}
					}
				}
			}
			
			if ($sql) {
				$sql = "
					ALTER TABLE `". DB_NAME_PREFIX. \ze\escape::sql($dataset['table']). "`" . trim($sql, ',');
					\ze\sql::update($sql);
			}
		}
	}




	//Formerly "getCentralisedLists()"
	public static function centralisedLists() {
		$centralisedLists = [];
		$result = \ze\row::query('centralised_lists', ['module_class_name', 'method_name', 'label'], [], 'label');
		while ($row = \ze\sql::fetchAssoc($result)) {
			$method = $row['module_class_name'] . '::' . $row['method_name'];
			$centralisedLists[$method] = $row['label'];
		}
		return $centralisedLists;
	}
}