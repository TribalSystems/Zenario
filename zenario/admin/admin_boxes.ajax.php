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

/*
	This file is used to handle AJAX requests for admin boxes.
	It reads all relevant yaml files, then merge them together into a PHP array, calls module methods to process
	that array, and then finally sends them via JSON to the client.
	It also handles validation and saving.
*/

//To allow organizer.ajax.php to call this file, only include the header if it's not already been included
if (!class_exists('cms_core')) {
	require '../adminheader.inc.php';
	require CMS_ROOT. 'zenario/includes/tuix.inc.php';
	useGZIP();
} else {
	exitIfNotCheckPriv();
}


$mode = false;
$tagPath = '';
$modules = array();
$debugMode = (bool) ($_GET['_debug'] ?? false);
$loadDefinition = true;
$settingGroup = '';
$compatibilityClassNames = array();
cms_core::$skType = $type = 'admin_boxes';








//If this isn't the first load, attempt to load the defintion from the Storage
if (!($_POST['_fill'] ?? false) && !$debugMode) {
	//Load the information that we have from the client
	if (empty($_POST['_box'])) {
		echo adminPhrase('An error occurred when syncing this floating admin box with the server.');
		exit;
	}
	$tags = array();
	$clientTags = json_decode($_POST['_box'], true);
	
	loadCopyOfTUIXFromServer($tags, $clientTags);
	$loadDefinition = false;
	
	syncAdminBoxFromClientToServer($tags, $clientTags);
	$originalTags = $tags;
}


//See if there is a requested path.
$requestedPath = false;
if (!empty($_REQUEST['path'])) {
	$requestedPath = preg_replace('/[^\w\/]/', '', $_REQUEST['path']);
}
cms_core::$skPath = $requestedPath;

//The Plugin Settings Admin Boxes are a special case for looking up XML files.
//They need to include the Settings from the Plugin in question, and any modules it is compatable with
if ($requestedPath == 'plugin_settings') {
	if (($_GET['refiner__nest'] ?? false) && ($_GET['id'] ?? false)) {
		$egg = getNestDetails($_GET['id'] ?? false, ($_GET['refiner__nest'] ?? false));
		$module = getModuleDetails($egg['module_id']);
	
	} elseif (!($_GET['instanceId'] ?? false) && ($_GET['refiner__plugin'] ?? false)) {
		$module = getModuleDetails($_GET['refiner__plugin'] ?? false);
	
	} elseif ($_GET['moduleId'] ?? false) {
		$module = getModuleDetails($_GET['moduleId'] ?? false);
	
	} else {
		$module = getPluginInstanceDetails(ifNull($_GET['instanceId'] ?? false, ($_GET['id'] ?? false)));
	}
	
	if ($module) {
		$settingGroup = $module['class_name'];
		
		//Loop through each of the Plugin's Compatibilities
		foreach (getModuleInheritances($module['class_name'], 'inherit_settings') as $className) {
			$compatibilityClassNames[$className] = $className;
		}
	}

} elseif ($requestedPath == 'site_settings') {
	$settingGroup = $_REQUEST['id'] ?? false;
}


if ($loadDefinition) {
	//Scan the Module directory for Modules with the relevant TUIX files, read them, and get a php array
	$tags = array();
	$originalTags = array();
	$moduleFilesLoaded = array();
	loadTUIX($moduleFilesLoaded, $tags, $type, $requestedPath, $settingGroup, $compatibilityClassNames);
	
	
	//If we had a requested path, drill straight down to that level
	if ($requestedPath) {
		
		foreach(explode('/', $requestedPath) as $path) {
			if (isset($tags[$path]) && is_array($tags[$path])) {
				$tags = $tags[$path];
				$tagPath .= '/'. $path;
			
			} else {
				echo adminPhrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', array('path' => $requestedPath));
				exit;
			}
		}
	
	} else {
		//There's no "map" for admin Admin Boxes; they must have a path
		if ($type == 'admin_boxes') {
			echo adminPhrase('An Admin Box path was needed, but none was given.');
			exit;
		}
	}
}

if ($debugMode) {
	$staticTags = $tags;
}


//Admin Boxes require a specific path
if (!$requestedPath || empty($tags['class_name'])) {
	echo adminPhrase('An Admin Box path was needed, but none was given.');
	exit;
}



if (isset($tags['priv']) && !checkPriv($tags['priv'])) {
	echo adminPhrase('You do not have permissions to see this Admin Box.');
	exit;
}


if (!zenarioAJAXIncludeModule($modules, $tags, $type, $requestedPath, $settingGroup)) {
	echo adminPhrase('Could not activate the [[class_name]] Module.', array('class_name' => $tags['class_name']));
	exit;
}



if (TUIXLooksLikeFAB($tags)) {
	foreach ($tags['tabs'] as &$tab) {
		if (!empty($tab['class_name'])) {
			zenarioAJAXIncludeModule($modules, $tab, $type, $requestedPath, $settingGroup);
		}
		
		if (!empty($tab['fields']) && is_array($tab['fields'])) {
			foreach ($tab['fields'] as &$field) {
				if (!empty($field['class_name'])) {
					zenarioAJAXIncludeModule($modules, $field, $type, $requestedPath, $settingGroup);
				}
			}
		}
	}
}

//Remove anything the current admin has no access to
$removedColumns = false;
if ($loadDefinition) {
	zenarioParseTUIX2($tags, $removedColumns, $type, $requestedPath, $mode);
}
$values = array();

//Debug mode - show the TUIX before it's been modified
if ($debugMode) {
	displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
	exit;

//Special logic for Validating and Saving
} elseif (!($_POST['_fill'] ?? false)) {
	$doSave = false;
	$doFormat = true;
	$errorsReset = false;
	
	if ($_POST['_read_values'] ?? false) {
		//Given the JSON object for an Admin Box, strip everything out and just return the tabs/values
		$fields = array();
		$values = array();
		$changes = array();
		readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = false);
		
		//Values need to be in a 2d array format here
		$values2d = array();
		if (TUIXLooksLikeFAB($tags)) {
			foreach ($tags['tabs'] as $tabName => &$tab) {
				if (is_array($tab) && !empty($tab['fields']) && is_array($tab['fields'])) {
					$values2d[$tabName] = array();
					foreach ($tab['fields'] as $fieldName => &$field) {
						if (isset($values[$tabName. '/'. $fieldName])) {
							$values2d[$tabName][$fieldName] = $values[$tabName. '/'. $fieldName];
						}
					}
				}
			}
		}
		
		jsonEncodeForceObject($values2d);
		exit;
		
	} else if (($_POST['_validate'] ?? false) || ($_POST['_save'] ?? false) || ($_POST['_download'] ?? false)) {
		//Take the current state of the box as a JSON object, and validate it
		
		//Create a (read only) shortcut array to the values
		$fields = array();
		$values = array();
		$changes = array();
		readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = true, $checkLOVs = true);
		$errorsReset = true;
		
		//Apply standard validation formats
		if (TUIXLooksLikeFAB($tags)) {
			foreach ($tags['tabs'] as $tabName => &$tab) {
				//Check if the tab is in edit mode
				if (engToBoolean($tab['edit_mode']['on'] ?? false)) {
					applyValidationFromTUIXOnTab($tab);
				}
			}
		}
		
		//Apply the modules' specific validation
		foreach ($modules as $className => &$module) {
			$module->validateAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes, (bool) ($_POST['_save'] ?? false));
		}
		
		//If the Admin is trying to save, and the box was valid, fire the save methods
		if (($_POST['_save'] ?? false) || ($_POST['_download'] ?? false)) {
			
			//Check if there are any errors
			if (TUIXLooksLikeFAB($tags)) {
				$doSave = true;
				foreach ($tags['tabs'] as &$tab) {
					if (!empty($tab['errors']) && is_array($tab['errors'])) {
						foreach ($tab['errors'] as $error) {
							if ($error) {
								$doSave = false;
								break 2;
							}
						}
					}
					
					if (!empty($tab['fields']) && is_array($tab['fields'])) {
						foreach ($tab['fields'] as &$field) {
							if (!empty($field['error'])) {
								$doSave = false;
								break 2;
							}
						}
					}
				}
			}
			
			if ($doSave) {
				$tags['_sync']['flags'] = array(
					'valid' => false,
					'confirm' => false,
					'download' => false,
					'saved' => false
				);
				
				if (!($_POST['_download'] ?? false)) {
					$tags['_sync']['flags']['valid'] = true;
				}
				
				$download =
					engToBoolean($tags['download'] ?? false)
						//For backwards compatability with old code
						|| engToBoolean($tags['confirm']['download'] ?? false);
				
				//Check if a confirmation is needed
				if (engToBoolean($tags['confirm']['show'] ?? false) && !(($_POST['_confirm'] ?? false) || ($_POST['_download'] ?? false))) {
					$tags['_sync']['flags']['confirm'] = true;
					
				} else if ($download && !($_POST['_download'] ?? false)) {
					$tags['_sync']['flags']['download'] = true;
					$doFormat = $_POST['_save_and_continue'] ?? false;
					
				} else {
					$fields = array();
					$values = array();
					$changes = array();
					readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = false);
					
					foreach ($modules as $className => &$module) {
						$module->saveAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
					}
					
					//If there are custom fields, attempt to save them
					if (!empty($tags['key']['id'])) {
						if ($dataset = getRow('custom_datasets', true, array('extends_admin_box' => $requestedPath))) {
							
							if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
							
								$record = array();
								//Save the values of custom fields
								foreach (getRowsArray(
									'custom_dataset_fields',
									true,
									array('dataset_id' => $dataset['id'], 'is_system_field' => 0)
								) as $cfield) {
									$cFieldName = '__custom_field__'. ifNull($cfield['db_column'], $cfield['id']);
								
									if (!empty($tags['tabs'][$cfield['tab_name']]['edit_mode']['on'])
									 && isset($values[$cfield['tab_name']. '/'. $cFieldName])) {
									
										//Child fields should be blanked if their parents are not checked
										$parents = array();
										getCustomFieldsParents($cfield, $parents);
									
										if (!empty($parents)) {
											$cfield['visible_if'] = '';
											foreach ($parents as $parent) {
												if (empty($values[$parent['tab_name/field_name']])) {
													$values[$cfield['tab_name']. '/'. $cFieldName] = '';
												}
											}
										}
									
										//Checkboxes are stored in the custom_dataset_values_link table as there could be more than one of them
										if ($cfield['type'] == 'checkboxes') {
											updateDatasetCheckboxField($dataset['id'], $cfield['id'], $tags['key']['id'], $values[$cfield['tab_name']. '/'. $cFieldName]);
										
										//Save the values of file pickers, which are also stored in a different table
										} elseif ($cfield['type'] == 'file_picker') {
											updateDatasetFilePickerField($dataset['id'], $cfield, $tags['key']['id'], $values[$cfield['tab_name']. '/'. $cFieldName]);
										
										//Otherwise store the value in an array and at the end of the loop...
										} else {
											$cfieldValue = $values[$cfield['tab_name']. '/'. $cFieldName];
											
											//Make sure text fields are no longer the 255 characters long
											if ($cfield['type'] == 'text') {
												$cfieldValue = substr($cfieldValue, 0, 255);
											}
											
											$record[$cfield['db_column']] = $cfieldValue;
										}
									}
								}
								
								//...update the record.
								if (!empty($record)) {
									setRow($dataset['table'], $record, $tags['key']['id']);
								}
							}
						}
					}
					
					foreach ($modules as $className => &$module) {
						$module->adminBoxSaveCompleted($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
					}
				
					if ($download) {
						//Bugfix for IE 6
						if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
							session_cache_limiter(false);
							header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
							header('Pragma: public');
						}
					
						foreach ($modules as $className => &$module) {
							$module->adminBoxDownload($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
						}
						exit;
				
					} else {
						$tags['_sync']['flags']['saved'] = true;
						$doFormat = $_POST['_save_and_continue'] ?? false;
					}
				}
			}
		}
	}
	
	//If we're going to wind up displaying the box again, format it again
	if ($doFormat) {
		$fields = array();
		$values = array();
		$changes = array();
		readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = !$errorsReset);
		
		foreach ($modules as $className => &$module) {
			$module->formatAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
		}
	}
	
} else {
	//Logic for initialising an Admin Box
	if (!empty($tags['key']) && is_array($tags['key'])) {
		foreach ($tags['key'] as $key => &$value) {
			if (!empty($_GET[$key])) {
				$value = $_GET[$key];
			}
		}
	}
	
	//When opening an Admin Box, accept an array of arrays for initial values for fields
	$valuesWithFieldsMissing = array();
	if (!empty($_POST['_values']) && ($values = json_decode($_POST['_values'], true)) && (is_array($values))) {
		//If it is a valid array, loop through the tabs/fields in the input
		foreach ($values as $tabName => &$tab) {
			if (is_array($tab)) {
				foreach ($tab as $fieldName => &$value) {
					//If this matches with a field in the description, set the value
					if (isset($tags['tabs'][$tabName]['fields'][$fieldName])) {
						$tags['tabs'][$tabName]['fields'][$fieldName]['value'] = $value;
					
					//Otherwise note down that the field was missing, and remember the value for later
					} else {
						if (!isset($valuesWithFieldsMissing[$tabName])) {
							$valuesWithFieldsMissing[$tabName] = array();
						}
						$valuesWithFieldsMissing[$tabName][$fieldName] = $value;
					}
				}
			}
		}
	}
	
	$fields = array();
	$values = array();
	$changes = array();
	readAdminBoxValues($tags, $fields, $values, $changes, $filling = true, $resetErrors = false);
	
	//Run the fill admin box method
	foreach ($modules as $className => &$module) {
		$module->fillAdminBox($requestedPath, $settingGroup, $tags, $fields, $values);
	}
	
	//Look for any custom fields/tabs
	if ($dataset = getRow('custom_datasets', true, array('extends_admin_box' => $requestedPath))) {
		
		if (checkPriv('_PRIV_MANAGE_DATASET')) {
			$tags['configure'] = array(
				'link' => 'zenario/admin/organizer.php#zenario__administration/panels/custom_datasets/item_buttons/edit_gui//'. $dataset['id']. '//',
				'tooltip' => adminPhrase('This box is editable, go to [[label]] dataset editor', $dataset)
			);
		}
		
		//Define the array of tabs if it's not already defined
		if (!isset($tags['tabs'])
		 || !is_array($tags['tabs'])) {
			$tags['tabs'] = array();
		} else {
			//This code used to try and work out which tab came first, and ensure it stayed first
			//even if someone customising the dataset tried to move it. This wasn't working too smoothly
			//so I have disabled it for now and we will review whether it was needed in the first place.
			//$firstTabName = false;
			//foreach ($tags['tabs'] as $tabName => &$tab) {
			//	$firstTabName = $tabName;
			//	break;
			//}
		}
		
		//Look for customised tabs
		foreach(getRowsArray('custom_dataset_tabs', true, array('dataset_id' => $dataset['id'])) as $ctab) {
			
			//Create an entry for that tab if one was not already created
			if (!isset($tags['tabs'][$ctab['name']])
			 || !is_array($tags['tabs'][$ctab['name']])) {
				if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
					$tags['tabs'][$ctab['name']] =
						array('edit_mode' => array('enabled' => true));
				}
			}
			
			//Set properties
			//if ($ctab['ord'] && $ctab['name'] != $firstTabName) {
			if ($ctab['ord']) {
				$tags['tabs'][$ctab['name']]['ord'] = $ctab['ord'];
			}
			if ($ctab['label']) {
				$tags['tabs'][$ctab['name']]['label'] = $ctab['label'];
			}
			
			//If a tab has a parent field set, make it only visible if the parent is visible and checked
			$parents = array();
			getCustomTabsParents($ctab, $parents);
			
			if (!empty($parents)) {
				$tags['tabs'][$ctab['name']]['visible_if'] = '';
				foreach ($parents as $parent) {
					$tags['tabs'][$ctab['name']]['visible_if'] .=
						($tags['tabs'][$ctab['name']]['visible_if']? ' && ' : '').
						"zenarioAB.value('". jsEscape($parent['field_name']). "', '". jsEscape($parent['tab_name']). "') == 1";
					
					//Attempt to set the redraw_onchange property for that field if it is a core field
					//(This will miss custom fields, so we'll need to set them later)
					if (!empty($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
					 && ($parentField = $tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
					 && (is_array($parentField))) {
						$parentField['redraw_onchange'] = true;
					}
				}
			}
		}
		unset($ctab);
		
		if (!$dataset['view_priv'] || checkPriv($dataset['view_priv'])) {
			//Attempt to load current values
			$record = false;
			if (!empty($tags['key']['id'])) {
				$record = getRow($dataset['table'], true, $tags['key']['id']);
			}
			
			//Add custom fields
			foreach (getRowsArray(
				'custom_dataset_fields',
				true,
				array('dataset_id' => $dataset['id'], 'is_system_field' => 0),
				'ord'
			) as $cfield) {
				$cFieldName = '__custom_field__'. ifNull($cfield['db_column'], $cfield['id']);
			
				if (!isset($tags['tabs'][$cfield['tab_name']])
				 || !is_array($tags['tabs'][$cfield['tab_name']])
				 //Drawing of repeating dataset fields not implemented
				 || $cfield['type'] == 'repeat_start'
				 || $cfield['type'] == 'repeat_end') {
					continue;
				}
				if (!isset($tags['tabs'][$cfield['tab_name']]['fields'])
				 || !is_array($tags['tabs'][$cfield['tab_name']]['fields'])) {
					$tags['tabs'][$cfield['tab_name']]['fields'] = array();
				}
			
				if (in($cfield['type'], 'select', 'centralised_select', 'dataset_select')) {
					$cfield['empty_value'] = adminPhrase(' -- Select -- ');
				}
				
				//If this is a picker, try to set up the picked_items property
				if ($cfield['type'] == 'dataset_picker') {
					if ($cfield['dataset_foreign_key_id']
					 && ($otherDataset = getDatasetDetails($cfield['dataset_foreign_key_id']))
					 && ($otherDataset['extends_organizer_panel'])) {
					
						$cfield['pick_items'] = array(
							'path' => $otherDataset['extends_organizer_panel'],
							'min_path' => $otherDataset['extends_organizer_panel'],
							'target_path' => $otherDataset['extends_organizer_panel'],
							'disallow_refiners_looping_on_min_path' => true);
					} else {
						$cfield['pick_items'] = array(
							'disallow_refiners_looping_on_min_path' => true);
						
						$cfield['readonly'] = true;
					}
				
				//For file pickers, set up the picked_items and upload properties
				} elseif ($cfield['type'] == 'file_picker') {
					$cfield['pick_items'] = array(
						'path' => 'zenario__content/panels/dataset_files/refiners/field//'. $cfield['id']. '//',
						'min_path' => 'zenario__content/panels/dataset_files',
						'target_path' => 'zenario__content/panels/dataset_files',
						'multiple_select' => $cfield['multiple_select'],
						'disallow_refiners_looping_on_min_path' => true
					);
					
					$cfield['upload'] = array(
						'extensions' => $cfield['extensions'],
						'multi' => $cfield['multiple_select'],
						'drag_and_drop' => false,
						'reorder_items' => false
					);
					
				//If this field uses a LOV, load the values
				} elseif (in($cfield['type'], 'checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select', 'dataset_select')) {
					$cfield['values'] = getDatasetFieldLOV($cfield, false);
					
				//If this field uses autocomplete load values
				} elseif ($cfield['type'] == 'text') {
					if ($cfield['autocomplete']) {
						$cfield['values'] = getDatasetFieldLOV($cfield);
					}
					
					//Set maxlengths for text fields and text areas
					$cfield['maxlength'] = 0xff;
				
				} elseif ($cfield['type'] == 'textarea') {
					$cfield['maxlength'] = 0xffff;
					
				}
			
				if ($cfield['width']) {
					$cfield['style'] = 'width: '. $cfield['width']. 'em;';
				}
				if ($cfield['height']) {
					$cfield['rows'] = $cfield['height'];
				}
			
				if ($cfield['validation']
				 && $cfield['validation'] != 'none'
				 && $cfield['validation_message']) {
					$cfield['validation'] = array($cfield['validation'] => $cfield['validation_message']);
				} else {
					$cfield['validation'] = array();
				}
			
				if ($cfield['required']
				 && $cfield['required_message']) {
					$cfield['validation']['required'] = $cfield['required_message'];
				}
				
				// Handle field visibility
				if ($cfield['admin_box_visibility'] != 'show_on_condition') {
					unset($cfield['parent_id']);
					$cfield['hidden'] = ($cfield['admin_box_visibility'] == 'hide');
				}
			
				//Set the value of the field.
				if (!empty($tags['key']['id'])) {
					//Checkboxes and file pickers are not stored in the usual table
					if ($cfield['type'] == 'checkboxes' || $cfield['type'] == 'file_picker') {
						$cfield['value'] = datasetFieldValue($dataset, $cfield, $tags['key']['id']);
					
					} elseif ($record && isset($record[$cfield['db_column']])) {
						//Otherwise use the value from the record
						$cfield['value'] = $record[$cfield['db_column']];
					}
				}
				
				if (columnIsEncrypted($dataset['table'], $cfield['db_column'])) {
					$cfield['encrypted'] = true;
				}
			
			
				//Make child fields only visible if their parents are visible and checked
				setChildFieldVisibility($cfield, $tags);
			
				//If a field has children, be sure to redraw the form on change to display them
				$children = array();
				getCustomFieldsChildren($cfield, $children);
			
				if (!empty($children)) {
					$cfield['redraw_onchange'] = true;
				}
				
				if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
				} else {
					$cfield['readonly'] = true;
				}
				
				$cfield['class'] = 'zenario_fab_custom_field zenario_fab_custom_field__'. $cfield['type'];
				$cfield['row_class'] = 'zenario_fab_custom_field_row zenario_fab_custom_field_row__'. $cfield['type'];
				$cfield['label_class'] = 'zenario_fab_custom_field_label zenario_fab_custom_field_label__'. $cfield['type'];
				
				if ($cfield['type'] == 'group') {
					$cfield['type'] = 'checkbox';
				} else {
					$cfield['type'] = str_replace(array('centralised_', 'dataset_'), '', $cfield['type']);
				}
				
				if ($cfield['type'] == 'text') {
					$cfield['maxlength'] = 255;
				}
				
				$tags['tabs'][$cfield['tab_name']]['fields'][$cFieldName] = $cfield;
			}
		}
		
		//Look for customised system fields
		foreach(getRowsArray(
			'custom_dataset_fields',
			true,
			array('dataset_id' => $dataset['id'], 'is_system_field' => 1)
		) as $cfield) {
			if (!isset($tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']])
			 || !is_array($tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']])) {
				continue;
			}
			$customisedField = &$tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']];
			
			//Set properties
			if ($cfield['ord']) {
				$customisedField['ord'] = $cfield['ord'];
			}
			if ($cfield['label']) {
				$customisedField['label'] = $cfield['label'];
			}
			if ($cfield['note_below']) {
				$customisedField['note_below'] = htmlspecialchars($cfield['note_below']);
			}
			if ($cfield['side_note']) {
				$customisedField['side_note'] = htmlspecialchars($cfield['side_note']);
			}
			if ($cfield['allow_admin_to_change_visibility']) {
				if ($cfield['admin_box_visibility'] == 'hide') {
					$customisedField['hidden'] = true;
				} elseif (($cfield['admin_box_visibility'] == 'show_on_condition') && $cfield['parent_id']) {
					$customisedField['parent_id'] = $cfield['parent_id'];
					$customisedField['dataset_id'] = $dataset['id'];
					$customisedField['tab_name'] = $cfield['tab_name'];
					setChildFieldVisibility($customisedField, $tags);
				}
			}
			
			if ($cfield['type'] == 'text') {
				if ($cfield['autocomplete']) {
					$customisedField['values'] = getDatasetFieldLOV($cfield);
				}
			}
			
			
			if (columnIsEncrypted($dataset['system_table'], $cfield['db_column'])) {
				$customisedField['encrypted'] = true;
			}
		}
		unset($cfield);
	}
	
	//If this Admin Box uses dynamic fields then these won't have been created above
	//But they might be there now, so check any missing fields again.
	foreach ($valuesWithFieldsMissing as $tabName => &$tab) {
		foreach ($tab as $fieldName => &$value) {
			if (isset($tags['tabs'][$tabName]['fields'][$fieldName])) {
				$tags['tabs'][$tabName]['fields'][$fieldName]['value'] = $value;
			}
		}
	}
	unset($valuesWithFieldsMissing);
	
	$fields = array();
	$values = array();
	$changes = array();
	readAdminBoxValues($tags, $fields, $values, $changes, $filling = true, $resetErrors = false);
	
	foreach ($modules as $className => &$module) {
		$module->formatAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
	}
}




//Try to save a copy of the admin box in the cache directory
saveCopyOfTUIXOnServer($tags);

if (!empty($originalTags)) {
	$output = array();
	syncAdminBoxFromServerToClient($tags, $originalTags, $output);
	
	//Always send the flags as-is without skipping them
	if (isset($tags['_sync']['flags'])) {
		$output['_sync']['flags'] = $tags['_sync']['flags'];
	}
	
	$tags = $output;
}






//Display the output as JSON
header('Content-Type: text/javascript; charset=UTF-8');
jsonEncodeForceObject($tags);
