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


class zenario_common_features__admin_boxes__plugin_settings extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (get('refiner__nest')) {
			$box['key']['instanceId'] = get('refiner__nest');
			$box['key']['nest'] = (int) get('id');
	
			$nestedItem = getNestDetails($box['key']['nest'], $box['key']['instanceId']);
			$box['key']['moduleId'] = $nestedItem['module_id'];

		} elseif (!ifNull(get('instanceId'), get('id')) && get('refiner__plugin')) {
			$box['key']['instanceId'] = false;
			$box['key']['nest'] = 0;
			$box['key']['moduleId'] = get('refiner__plugin');

		} else {
			$box['key']['instanceId'] = ifNull(get('instanceId'), get('id'));
			$box['key']['nest'] = (int) get('nest');
			$box['key']['moduleId'] = get('moduleId');
		}


		if ($box['key']['moduleId'] && $box['key']['instanceId']) {
			$module = getModuleDetails($box['key']['moduleId']);
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
		} elseif ($box['key']['moduleId']) {
			$module = getModuleDetails($box['key']['moduleId']);
			$instance = array('framework' => $module['default_framework'], 'css_class' => '');
		} else {
			$module = $instance = getPluginInstanceDetails($box['key']['instanceId']);
			$box['key']['moduleId'] = $instance['module_id'];
		}


		$box['key']['isVersionControlled'] = !empty($instance['content_id']);
		$box['key']['cID'] = ifNull(arrayKey($instance, 'content_id'), get('cID'), get('parent__cID'));
		$box['key']['cType'] = ifNull(arrayKey($instance, 'content_type'), get('cType'), get('parent__cType'));
		$box['key']['cVersion'] = ifNull(arrayKey($instance, 'content_version'), get('cVersion'));
		$box['key']['slotName'] = ifNull(arrayKey($instance, 'slot_name'), get('slotName'));
		$box['key']['languageId'] = ifNull(getContentLang($box['key']['cID'], $box['key']['cType']), setting('default_language'));


		if ($box['key']['isVersionControlled']) {
			$box['css_class'] .= ' zenario_wireframe_plugin_settings';
		} else {
			$box['css_class'] .= ' zenario_reusable_plugin_settings';
	
			if ($box['key']['nest']) {
				exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
			} elseif ($box['key']['instanceId']) {
				exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
			} else {
				exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
			}
		}


		$canEdit = false;
		if ($box['key']['isVersionControlled']) {
			if (isDraft($status = getContentStatus($box['key']['cID'], $box['key']['cType'])) || $box['key']['nest']) {
				$canEdit = checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
			} else {
				$canEdit =
					$status == 'published'
				 && checkPriv('_PRIV_CREATE_REVISION_DRAFT', $box['key']['cID'], $box['key']['cType'])
				 && $box['key']['cVersion'] == getLatestVersion($box['key']['cID'], $box['key']['cType']);
			}

		} else {
			if ($box['key']['nest']) {
				$canEdit = checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
			} else {
				$canEdit = checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
			}
		}

		$title = '';

		switch ($path) {
			case 'plugin_settings':

				if (empty($instance['instance_name'])) {
					//Load the XML description for this plugin, and get the default instance name
					$desc = false;
					if (loadModuleDescription($module['class_name'], $desc)) {
						$instanceName = $desc['default_instance_name'];
					} else {
						$instanceName = '';
					}
				} else {
					$instanceName = $instance['instance_name'];
				}

				//If this is a new instance, try and ensure that the name we are suggesting is unique
				if (!$box['key']['instanceId']) {
					$sql = "
						SELECT COUNT(*)
						FROM  ". DB_NAME_PREFIX. "plugin_instances
						WHERE name LIKE '". sqlEscape($instanceName). "%'";
					$result = sqlQuery($sql);
					$row = sqlFetchRow($result);
					if ($row[0]) {
						$instanceName .= ' ('. ($row[0] + 1). ')';
					}
				}
		
				$values['first_tab/instance_name'] = $instanceName;

				$valuesInDB = array();
				if ($box['key']['instanceId']) {
					$sql = "
						SELECT name, `value`
						FROM ". DB_NAME_PREFIX. "plugin_settings
						WHERE instance_id = ". (int) $box['key']['instanceId']. "
						  AND nest = ". (int) $box['key']['nest'];
					$result = sqlQuery($sql);
	
					while($row = sqlFetchAssoc($result)) {
						$valuesInDB[$row['name']] = $row['value'];
					}
				}

				foreach ($box['tabs'] as $tabName => &$tab) {
					if (is_array($tab)) {
						if (!$canEdit) {
							$tab['edit_mode'] = array('enabled' => false);
						} else {
							if (empty($tab['edit_mode'])) {
								$tab['edit_mode'] = array();
							}
			
							$tab['edit_mode']['enabled'] = true;
							$tab['edit_mode']['always_on'] = true;
							$tab['edit_mode']['enable_revert'] = true;
						}
		
						foreach ($tab['fields'] as $fieldName => &$field) {
							if (is_array($field)) {
								if (!empty($field['plugin_setting']['name']) && isset($valuesInDB[$field['plugin_setting']['name']])) {
									$field['value'] = $valuesInDB[$field['plugin_setting']['name']];
								}
							}
						}
					}
				}


				//Even if there are no settings for this plugin, then we still want to have
				//one or two fixed fields at the start; e.g. the name of the instance as the very first field
				//Nested modules have a little more information
				if ($box['key']['nest']) {
					$fields['first_tab/instance_name']['hidden'] = true;

				} else {
					if ($box['key']['instanceId']) {
						$fields['first_tab/instance_name']['read_only'] = true;
					}
					if ($box['key']['isVersionControlled']) {
						$fields['first_tab/instance_name']['hidden'] = true;
					}
				}
		
		
				// Get admin box title
				if ($box['key']['nest'] && $box['key']['isVersionControlled']) {
					$title = 
						adminPhrase('Editing a plugin of the "[[module]]" module, in the [[nest]]',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
								  'nest' => htmlspecialchars($instanceName)));
		
				} elseif ($box['key']['nest']) {
					$title = 
						adminPhrase('Editing a plugin of the "[[module]]" module, in the nest "[[nest]]"',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
								  'nest' => htmlspecialchars($instanceName)));
		
				} elseif ($box['key']['isVersionControlled']) {
					$title = 
						adminPhrase('Editing the [[module]]',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
		
				} elseif ($box['key']['instanceId']) {
					$title = 
						adminPhrase('Editing a plugin of the module "[[module]]"',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
		
				} else {
					$title = 
						adminPhrase('Creating a plugin of the "[[module]]" module',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
				}
		
				// Get modules description file
				$moduleDescription = "No module decription found for this plugin.";
				$path = moduleDescriptionFilePath($module['class_name']);
		
				$tags = zenarioReadTUIXFile(CMS_ROOT . $path);
				if ($tags && isset($tags['description']) && $tags['description']) {
					$moduleDescription = $tags['description'];
			
				//check inheritance 
				} else if ($tags && isset($tags['inheritance']['inherit_description_from_module']) && $tags['inheritance']['inherit_description_from_module']) {
					$path = moduleDescriptionFilePath($tags['inheritance']['inherit_description_from_module']);
					$tags = zenarioReadTUIXFile(CMS_ROOT . $path);
					if ($tags && isset($tags['description']) && $tags['description']) {
						$moduleDescription = $tags['description'];
					}
				}
				$fields['last_tab/module_description']['snippet']['html'] = 
					'<div class="module_description">' . $moduleDescription . '</div>';
		
				break;
	
			case 'plugin_css_and_framework':
		
				$instanceName = $instance['instance_name'];
		
				if ($canEdit) {
					$box['tabs']['last_tab']['edit_mode'] = array('enabled' => true);
				}

				//Load the values from the database
				if ($box['key']['nest']) {
					$sql = "
						SELECT name_or_title, framework, css_class
						FROM ". DB_NAME_PREFIX. "nested_plugins
						WHERE id = ". (int) $box['key']['nest'];
	
					$result = sqlQuery($sql);
					$row = sqlFetchAssoc($result);
					$values['last_tab/framework'] = $framework = $row['framework'];
					$values['last_tab/css_class'] = $row['css_class'];

				} else {
					$values['last_tab/framework'] = $framework = $instance['framework'];
					$values['last_tab/css_class'] = $instance['css_class'];
				}


				//Look for frameworks
				$fields['last_tab/framework']['values'] = listModuleFrameworks($module['class_name']);

				if (!empty($fields['last_tab/framework']['values'])) {
					if ($module['default_framework']
					 && isset($fields['last_tab/framework']['values'][$module['default_framework']])) {
						$fields['last_tab/framework']['values'][$module['default_framework']]['label'] .=
							adminPhrase(' (default)');
					}
					if (!isset($fields['last_tab/framework']['values'][$framework])) {
						$fields['last_tab/framework']['values'][$framework] =
							array('ord' => 0, 'label' => adminPhrase('[[framework]] (missing from filesystem)', array('framework' => $framework)));
					}

				} else {
					$fields['last_tab/framework']['hidden'] =
					$fields['last_tab/framework_source']['hidden'] = true;
				}
		
		
				//Attempt to load a list of CSS Class Names from the description file of the current Skin
				//to add choices in for the CSS Class Picker.
				//Do do this we need to get the Skin Id from the Layout Id - and thus need to know the Layout Id.
				//If we know which Content Item we're currently on somehow, we can look this up from the cID/cType/cVersion.
				//Otherwise, try looking at the Plugin placement tables for either a Layout or a Content Item.
				$skin = $skinDescriptionFilePath = false;
				if (($box['key']['cID']
				  && $box['key']['cVersion']
				  && $layoutId = contentItemTemplateId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']))
				 || (request('cID')
				  && request('cVersion')
				  && $layoutId = contentItemTemplateId(request('cID'), request('cType'), request('cVersion')))
				 || (request('parent__cID')
				  && request('parent__cVersion')
				  && $layoutId = contentItemTemplateId(request('parent__cID'), request('parent__cType'), request('parent__cVersion')))
				 || ($box['key']['instanceId']
				  && $layoutId = getRow('plugin_layout_link', 'layout_id', array('instance_id' => $box['key']['instanceId'])))
				 || ($box['key']['instanceId']
				  && ($plugin_item_link = getRow('plugin_item_link', array('content_id', 'content_type', 'content_version'), array('instance_id' => $box['key']['instanceId'])))
				  && ($layoutId = contentItemTemplateId($plugin_item_link['content_id'], $plugin_item_link['content_type'], $plugin_item_link['content_version'])))
				) {
					if ($skinId = templateSkinId($layoutId)) {
						if ($skin = getSkinFromId($skinId)) {
							$skinDescriptionFilePath = skinDescriptionFilePath($skin['family_name'], $skin['name'], true);
						}
					}
	
				}
		
				//Load all of the possible values, and add a "default" and a "custom" value
				$fields['last_tab/css_class']['values']['#default#']['label'] = $module['css_class_name']. '__default_style';
		
				if ($skin) {
					$i = 1;
					foreach (getSkinCSSClassNames($skin, 'plugin', $module['css_class_name']) as $cssClass => $label) {
						$fields['last_tab/css_class']['values'][$cssClass] = array('label' => $label, 'ord' => ++$i);
					}
				}
		
				foreach ($fields['last_tab/css_class']['values'] as &$value) {
					$value['label'] = $module['css_class_name']. ' '. $value['label'];
				}
		
				//If no CSS class was entered, pick the "default" value
				if ($values['last_tab/css_class'] == '') {
					$values['last_tab/css_class'] = '#default#';
		
				//If the value is not in the list, pick the "custom" value
				} elseif (empty($fields['last_tab/css_class']['values'][$values['last_tab/css_class']])) {
					$values['last_tab/css_class_custom'] = $values['last_tab/css_class'];
					$values['last_tab/css_class'] = '#custom#';
				}
		
				if ($skinDescriptionFilePath) {
					$fields['last_tab/css_class']['side_note'] =
						adminPhrase('Add CSS classes by picking existing ones from the list or by writing your own in the text box. A designer can add new styles to this list by editing the [[path]] file.', array('path' => $skinDescriptionFilePath));
				} else {
					$fields['last_tab/css_class']['side_note'] =
						adminPhrase('Add CSS classes by picking existing ones from the list or by writing your own in the text box.');
				}
		
				// Get admin box title
				if ($box['key']['nest'] && $box['key']['isVersionControlled']) {
					$title = 
						adminPhrase('Framework & CSS for a plugin of the "[[module]]" module, in the [[nest]]',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
								  'nest' => htmlspecialchars($instanceName)));
		
				} elseif ($box['key']['nest']) {
					$title = 
						adminPhrase('Framework & CSS for a plugin of the "[[module]]" module, in the nest "[[nest]]"',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
								  'nest' => htmlspecialchars($instanceName)));
		
				} elseif ($box['key']['isVersionControlled']) {
					$title = 
						adminPhrase('Framework & CSS for the [[module]]',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
		
				} else {
					$title = 
						adminPhrase('Framework & CSS for a plugin of the module "[[module]]"',
							array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
				}
		
				break;
		}


		$box['title'] = $title;
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_css_and_framework':
				if (!empty($values['last_tab/framework'])) {

					$module = getModuleDetails($box['key']['moduleId']);

					if ($frameworkFile = frameworkPath($values['last_tab/framework'], $module['class_name'], true)) {
						$values['last_tab/framework_source'] = file_get_contents($frameworkFile);
						$fields['last_tab/framework_source']['language'] = $frameworkFile;

					} else {
						$values['last_tab/framework_source'] = '';
						$fields['last_tab/framework_source']['language'] = '';
					}

				}
		
				break;
		}
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$box['confirm']['show'] = false;
		if (!$box['key']['instanceId']) {
			createNewInstance(
				$box['key']['moduleId'],
				$values['first_tab/instance_name'],
				$box['key']['instanceId'],
				$box['tabs']['first_tab']['errors'],
				$onlyValidate = true);

		} else {
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
	
			if ($instance['content_id']) {
				if (!isDraft($status = getContentStatus($instance['content_id'], $instance['content_type']))) {
					if ($status != 'published') {
						$box['tabs']['first_tab']['errors'][] = adminPhrase('This content item is not a draft and cannot be edited.');
					} else {
						$box['confirm']['show'] = true;
					}
				}
	
			} else {
				$usage = checkInstancesUsage($box['key']['instanceId'], false);
				$usagePublished = checkInstancesUsage($box['key']['instanceId'], true);
		
				if ($usagePublished || (!$box['key']['frontEnd'] && $usage > 0) || ($box['key']['frontEnd'] && $usage > 1)) {
			
					$box['confirm']['show'] = true;
					$box['confirm']['html'] = true;
					$box['confirm']['button_message'] = adminPhrase('Save');
			
					if ($box['key']['frontEnd']) {
						$box['confirm']['message'] = 
							'<p>'. adminPhrase('You are changing the settings of this plugin. The change will be <b>immediate</b> and cannot be undone.'). '</p>';
			
					} else {
						$box['confirm']['message'] = 
							'<p>'. adminPhrase('You are changing the settings of this plugin. The change will be <b>immediate</b> and cannot be undone.'). '</p>';
					}
			
					$box['confirm']['message'] .= 
							'<p>'. adminPhrase(
						'This will affect [[published]] content items immediately (as they are published), [[pages]] items in total.</p><p><a href="[[link]]" target="_blank">Click for a list of all content items affected</a> (this can be found normally in Organizer, under Modules).</span>',
						array('pages' => $usage,
								'published' => $usagePublished,
								'link' => htmlspecialchars(getPluginInstanceUsageStorekeeperDeepLink($instance['instance_id'], $instance['module_id'])))). '</p>';
				}
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
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
											foreach (explodeAndTrim($value['value']) as $file) {
												if ($location = getPathOfUploadedFileInCacheDir($file)) {
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
													foreach (explodeAndTrim($value['value']) as $fileId) {
														if ($fileId = (int) $fileId) {
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
		
	}
}
