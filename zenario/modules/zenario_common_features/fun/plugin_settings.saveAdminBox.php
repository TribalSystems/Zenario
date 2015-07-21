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


$instance = array();

if ($box['key']['instanceId']) {
	$instance = getPluginInstanceDetails($box['key']['instanceId']);
}

//Load details of this Instance, and check for permissions to save
if (!empty($instance['content_id'])) {
	
	//If this Wireframe is already on a draft, then there's no need to create one
	if (isDraft($instance['content_id'], $instance['content_type'])) {
		exitIfNotCheckPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
	
	//Don't create a draft for Nested modules, the interface can't handle this
	} elseif ($box['key']['nest']) {
		return;
	
	//Otherwise create a new draft
	} else {
		exitIfNotCheckPriv('_PRIV_CREATE_REVISION_DRAFT', $instance['content_id'], $instance['content_type']);
		
		//Create a new Content Item, or a new Draft of a Content Item, if this wireframe isn't already on a draft.
		$cVersionTo = $instance['content_version'];
		createDraft($instance['content_id'], $instance['content_id'], $instance['content_type'], $cVersionTo, $instance['content_version']);
		$box['key']['cVersion'] = $cVersionTo;
		
		//This wireframe will now be using a new instance id on the newly created draft
		$box['key']['instanceId'] =
			getVersionControlledPluginInstanceId(
				$instance['content_id'], $instance['content_type'], $cVersionTo, $instance['slot_name'], $instance['module_id']);
			
		//Remove the slot name, to force the CMS to reload the entire page
		$box['key']['slotName'] = false;
	}

} elseif ($box['key']['nest']) {
	exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');

} else {
	exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
	
	//Handle creating a new instance
	if (!$box['key']['instanceId']) {
		$errors = array();
		createNewInstance(
			$box['key']['moduleId'],
			$values['first_tab/instance_name'],
			$box['key']['instanceId'],
			$errors);
		
		$box['key']['id'] = $box['key']['instanceId'];
		$instance = getPluginInstanceDetails($box['key']['instanceId']);
	}
}

$syncLibraryPluginFiles = array();
$syncContent = false;
$pk = array(
	'instance_id' => $box['key']['instanceId'],
	'nest' => $box['key']['nest']);


switch ($path) {
	case 'plugin_settings':

		//Loop through each field that would be in the Admin Box, and has the <plugin_setting> tag set
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab) && engToBooleanArray($box, 'tabs', $tabName, 'edit_mode', 'on')) {
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (is_array($field)) {
						if (!empty($field['plugin_setting']['name'])) {
							$pk['name'] = $field['plugin_setting']['name'];
					
							//Delete the value for a field if it was hidden...
							if (engToBooleanArray($field, 'hidden')
							 || engToBooleanArray($field, '_was_hidden_before')) {
								deleteRow('plugin_settings', $pk);
					
							//...or a multiple edit field that is not marked as changed
							} else
							if (isset($field['multiple_edit'])
							 && !$changes[$tabName. '/'. $fieldName]) {
								deleteRow('plugin_settings', $pk);
					
							} else {
								//Otherwise save the field in the plugin_settings table.
								$value = array();
								$value['value'] = arrayKey($values, $tabName. '/'. $fieldName);
								
								
								//Handle file/image uploaders by adding these files to the system
								if (!empty($field['upload'])) {
									$fileIds = array();
									foreach (explode(',', $value['value']) as $file) {
										if ($location = getPathOfUploadedFileInCacheDir(trim($file))) {
											$fileIds[] = addFileToDatabase('image', $location);
										} else {
											$fileIds[] = $file;
										}
									}
									$value['value'] = implode(',', $fileIds);
								}
							
						
								//The various different types of foreign key should be registered
								if (!$value['value'] || empty($field['plugin_setting']['foreign_key_to'])) {
									$value['dangling_cross_references'] = 'remove';
									$value['foreign_key_to'] = NULL;
									$value['foreign_key_id'] = 0;
									$value['foreign_key_char'] = '';
						
								} else {
									$value['dangling_cross_references'] = ifNull(arrayKey($field, 'plugin_setting', 'dangling_cross_references'), 'remove');
									$value['foreign_key_to'] = $field['plugin_setting']['foreign_key_to'];
						
									if ($field['plugin_setting']['foreign_key_to'] == 'categories') {
										$value['foreign_key_id'] = 0;
										$value['foreign_key_char'] = '';
							
									} elseif ($field['plugin_setting']['foreign_key_to'] == 'content') {
										$cID = $cType = false;
										getCIDAndCTypeFromTagId($cID, $cType, $value['value']);
								
										$value['foreign_key_id'] = $cID;
										$value['foreign_key_char'] = $cType;
							
									} elseif ($field['plugin_setting']['foreign_key_to'] == 'email_template') {
										$value['foreign_key_id'] = 0;
										$value['foreign_key_char'] = $value['value'];
							
									} elseif (in($field['plugin_setting']['foreign_key_to'], 'category', 'file', 'menu_section')) {
										$value['foreign_key_id'] = $value['value'];
										$value['foreign_key_char'] = '';
							
									} elseif (is_numeric($value['value'])) {
										$value['foreign_key_id'] = $value['value'];
										$value['foreign_key_char'] = $value['value'];
							
									} else {
										$value['foreign_key_id'] = 0;
										$value['foreign_key_char'] = $value['value'];
									}
								}
								
								//Work out whether this is a version controlled or synchronized Instance
								if (!$instance['content_id']) {
									$value['is_content'] = 'synchronized_setting';
									
									switch (arrayKey($field, 'plugin_setting', 'foreign_key_to')) {
										case 'file':
											if ($fileId = (int) trim($value['value'])) {
												$syncLibraryPluginFiles[$fileId] = array('id' => $fileId);
											}
											break;
										
										case 'multiple_files':
											foreach (explode(',', $value['value']) as $fileId) {
												if ($fileId = (int) trim($fileId)) {
													$syncLibraryPluginFiles[$fileId] = array('id' => $fileId);
												}
											}
											break;
									}
											
						
								} elseif (engToBooleanArray($field, 'plugin_setting', 'is_searchable_content')) {
									$value['is_content'] = 'version_controlled_content';
									$syncContent = true;
						
								} else {
									$value['is_content'] = 'version_controlled_setting';
							
									if (in(arrayKey($field, 'plugin_setting', 'foreign_key_to'), 'file', 'multiple_files')) {
										$syncContent = true;
									}
								}
						
								if (!$trimedValue = trim($value['value'])) {
									$value['format'] = 'empty';
						
								} elseif (html_entity_decode($trimedValue) != $trimedValue || strip_tags($trimedValue) != $trimedValue) {
									if (engToBooleanArray($field, 'plugin_setting', 'translate')) {
										$value['format'] = 'translatable_html';
									} else {
										$value['format'] = 'html';
									}
						
								} else {
									if (engToBooleanArray($field, 'plugin_setting', 'translate')) {
										$value['format'] = 'translatable_text';
									} else {
										$value['format'] = 'text';
									}
								}
								
								if (isset($field['plugin_setting']['is_email_address'])) {
									$value['is_email_address'] = $field['plugin_setting']['is_email_address'];
								} else {
									$value['is_email_address'] = NULL;
								}
								
								setRow('plugin_settings', $value, $pk);
							}
						}
					}
				}
			}
		}


		//Set the Nested Plugin's name
		if ($box['key']['nest']) {
			//For Nested Plugins, check to see if there is a Plugin Setting with the <use_value_for_plugin_name> tag set,
			//which should be the name of the Nested Plugin
			//Empty or Hidden fields don't count; otherwise the value of <use_value_for_plugin_name> indicates which field has priority.
			$eggName = false;
			$eggNameCurrentPriority = false;
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (is_array($tab)
				 && !engToBooleanArray($tab, 'hidden')
				 && !engToBooleanArray($tab, '_was_hidden_before')
				 && !empty($tab['fields']) && is_array($tab['fields'])) {
			
					foreach ($tab['fields'] as $fieldName => &$field) {
						if (is_array($field)
						 && !empty($values[$tabName. '/'. $fieldName])
						 && !empty($field['plugin_setting']['use_value_for_plugin_name'])
						 && !engToBooleanArray($field, 'hidden')
						 && !engToBooleanArray($field, '_was_hidden_before')
						 && ($eggNameCurrentPriority === false || $eggNameCurrentPriority > (int) $field['plugin_setting']['use_value_for_plugin_name'])) {
					
							$eggName = $values[$tabName. '/'. $fieldName];
							$editMode = engToBooleanArray($tab, 'edit_mode', 'on')? '_' : '';
							$eggNameCurrentPriority = (int) $field['plugin_setting']['use_value_for_plugin_name'];
					
							//Attempt to get a display value, rather than the actual value
							$items = explode(',', $eggName);
							if (!empty($field['values'][$items[0]])) {
								$eggName = $field['values'][$items[0]];
					
							} elseif (!empty($field['values'][$eggName])) {
								$eggName = $field['values'][$eggName];
					
							} elseif (!empty($field['_display_value'])) {
								$eggName = $field['_display_value'];
							}
						}
					}
				}
			}
	
			if (!$eggName) {
				$eggName = getModuleDisplayName($box['key']['moduleId']);
			}
	
			updateRow('nested_plugins', array('name_or_title' => $eggName), $box['key']['nest']);
		}
		
		break;
	
	
	case 'plugin_css_and_framework':

		//Save the framework, if set
		$vals = array();
		$vals['framework'] = $values['last_tab/framework'];
		
		
		//The "default" value is stored as an empty string
		if ($values['last_tab/css_class'] == '#default#') {
			$vals['css_class'] = '';
		
		//If the value is not in the list, pick the "custom" value
		} elseif ($values['last_tab/css_class'] == '#custom#') {
			$vals['css_class'] = $values['last_tab/css_class_custom'];
		
		} else {
			$vals['css_class'] = $values['last_tab/css_class'];
		}
		
		if ($box['key']['nest']) {
			updateRow('nested_plugins', $vals, $box['key']['nest']);
		} else {
			updateRow('plugin_instances', $vals, $box['key']['instanceId']);
		}
		
		break;
}


//Clear anything that is cached for this instance
$sql = "
	DELETE
	FROM ". DB_NAME_PREFIX. "plugin_instance_cache
	WHERE instance_id = ". (int) $box['key']['instanceId'];
sqlQuery($sql);


if ($instance['content_id']) {
	if ($syncContent) {
		syncInlineFileContentLink($instance['content_id'], $instance['content_type'], $instance['content_version']);
	}
	
	//Update the last modified date on the Content Item if this is a Wireframe Plugin
	updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);

} else {
	syncInlineFiles(
		$syncLibraryPluginFiles,
		array('foreign_key_to' => 'library_plugin', 'foreign_key_id' => $box['key']['instanceId']),
		$keepOldImagesThatAreNotInUse = false);
}

return false;