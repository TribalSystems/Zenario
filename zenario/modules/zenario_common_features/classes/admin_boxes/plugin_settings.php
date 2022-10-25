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


class zenario_common_features__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	protected $skinWritableDir = false;
	
	protected function getPluginCSSFilepath(&$box, $thisPlugin) {
		
		if ($thisPlugin && !empty($box['key']['isVersionControlled'])) {
			return false;
		
		} elseif (!empty($box['key']['skinId'])) {
			$skin = ze\row::get('skins', ['id', 'name'], $box['key']['skinId']);
			$this->skinWritableDir = ze\content::skinPath($skin['name']). 'editable_css/';
			return $this->skinWritableDir. '2.'. $this->getPluginCSSName($box, $thisPlugin). '.css';
		
		//If we possibly can, try to get the Skin that this plugin is being shown on
		} elseif (ze::$skinId) {
			$box['key']['skinId'] = ze::$skinId;
			$this->skinWritableDir = ze\content::skinPath(). 'editable_css/';
			return $this->skinWritableDir. '2.'. $this->getPluginCSSName($box, $thisPlugin). '.css';
		
		} else
		if ($box['key']['cID']
		 && ($content = ze\row::get('content_items', true, ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]))
		 && ($chain = ze\row::get('translation_chains', true, ['equiv_id' => $content['equiv_id'], 'type' => $box['key']['cType']]))
		 && ($version = ze\row::get('content_item_versions', true, ['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']]))) {
			ze\content::setShowableContent($content, $chain, $version, false);
			$box['key']['skinId'] = ze::$skinId;
			$this->skinWritableDir = ze\content::skinPath(). 'editable_css/';
			return $this->skinWritableDir. '2.'. $this->getPluginCSSName($box, $thisPlugin). '.css';
		}
		
		//If we don't know where this plugin will be used, check to see if there's only
		//one skin on this site. If so, show the CSS options anyway as we know what skin
		//will be used!
		$skins = ze\row::getAssocs('skins', ['id', 'name'], ['missing' => 0]);
		
		if (count($skins) == 1) {
			$skin = array_pop($skins);
			$box['key']['skinId'] = $skin['id'];
			$this->skinWritableDir = ze\content::skinPath($skin['name']). 'editable_css/';
			return $this->skinWritableDir. '2.'. $this->getPluginCSSName($box, $thisPlugin). '.css';
		}
		
		//If that didn't work, check how many layouts are marked as "active",
		//and how many different skins they use. If it's only one, use the same
		//logic as above
		$sql = "
			SELECT DISTINCT skin_id
			FROM ". DB_PREFIX. "layouts
			WHERE `status` = 'active'";
		
		$result = ze\sql::select($sql);
		
		if ((ze\sql::numRows($result) == 1)
		 && ($skinId = ze\sql::fetchValue($result))
		 && ($skin = $skins[$skinId] ?? false)) {
			$box['key']['skinId'] = $skin['id'];
			$this->skinWritableDir = ze\content::skinPath($skin['name']). 'editable_css/';
			return $this->skinWritableDir. '2.'. $this->getPluginCSSName($box, $thisPlugin). '.css';
		}
		
		//Otherwise, this site uses multiple skins, and we can't determine that
		//a specific one will be used
		return false;
	}
	
	protected function getPluginCSSName(&$box, $thisPlugin) {
		
		$baseCSSName = ze\row::get('modules', 'css_class_name', $box['key']['moduleId']);
		
		if (!$thisPlugin || $box['key']['cID'] == -1) {
			return $baseCSSName;
		}
		
		$cssName = $baseCSSName;
		
		if ($box['key']['isVersionControlled']) {
			$cssName = $box['key']['cType']. '_'. $box['key']['cID']. '_'. $box['key']['slotName']. '_'. $cssName;
		
			if ($box['key']['eggId']) {
				$row = ze\row::get('nested_plugins', true, $box['key']['eggId']);
				$cssName .= '_'. $row['tab']. '_'. $row['ord'];
			}
			
		} else {
			$cssName .= '_'. $box['key']['instanceId'];
			
			if ($box['key']['eggId']) {
				$cssName .= '_'. $box['key']['eggId'];
			}
		}
		
		return $cssName;
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$module = $instance = $egg = [];
		ze\tuix::setupPluginFABKey($box['key'], $module, $instance, $egg);
		
		
		
		$module['display_name'] = ze\module::displayName($box['key']['moduleId']);


		$box['key']['isVersionControlled'] = !empty($instance['content_id']);
		$box['key']['cID'] = ze::ifNull($instance['content_id'] ?? false, ze::get('cID'), ze::get('parent__cID'));
		$box['key']['cType'] = ze::ifNull($instance['content_type'] ?? false, ze::get('cType'), ze::get('parent__cType'));
		$box['key']['cVersion'] = ze::ifNull($instance['content_version'] ?? false, ze::get('cVersion'));
		$box['key']['slotName'] = ze::ifNull($instance['slot_name'] ?? false, ze::get('slotName'));
		$box['key']['languageId'] = ze::ifNull(ze\content::langId($box['key']['cID'], $box['key']['cType']), ze::$defaultLang);
		
		
		switch ($box['key']['moduleClassName']) {
			case 'zenario_plugin_nest':
				$module['pluginAdminName'] = $pluginAdminName = \ze\admin::phrase('nest');
				$module['ucPluginAdminName'] = $ucPluginAdminName = \ze\admin::phrase('Nest');
				$module['pPluginAdminName'] =
				$module['pluginsOfThisType'] = \ze\admin::phrase('nests');
				break;
			case 'zenario_slideshow':
			case 'zenario_slideshow_simple':
				$module['pluginAdminName'] = $pluginAdminName = \ze\admin::phrase('slideshow');
				$module['ucPluginAdminName'] = $ucPluginAdminName = \ze\admin::phrase('Slideshow');
				$module['pPluginAdminName'] =
				$module['pluginsOfThisType'] = \ze\admin::phrase('slideshows');
				break;
			default:
				$module['pluginAdminName'] = $pluginAdminName = \ze\admin::phrase('plugin');
				$module['ucPluginAdminName'] = $ucPluginAdminName = \ze\admin::phrase('Plugin');
				$module['pPluginAdminName'] = \ze\admin::phrase('plugins');
				$module['pluginsOfThisType'] = \ze\admin::phrase('plugins of this type');
		}
		
		
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab)) {
				if (isset($tab['label'])) {
					$tab['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $tab['label']));
				}
				if (!empty($tab['fields']) && is_array($tab['fields'])) {
					foreach ($tab['fields'] as $fieldName => &$field) {
						if (is_array($field)) {
							if (isset($field['label'])) {
								$field['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $field['label']));
							}
						}
					}
				}
			}
		}
		if (isset($box['tabs']['first_tab']['fields']['duplicate_or_rename']['values'])) {
			$dorValues = &$box['tabs']['first_tab']['fields']['duplicate_or_rename']['values'];
			
			if (isset($dorValues['replace'])) {
				$dorValues['replace']['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $dorValues['replace']['label']));
			}
			
			if (isset($dorValues['duplicate'])) {
				$dorValues['duplicate']['label'] = str_replace('~plugin~', $pluginAdminName, str_replace('~Plugin~', $ucPluginAdminName, $dorValues['duplicate']['label']));
			}
		}

		

		if ($box['key']['isVersionControlled'] || !$box['key']['instanceId']) {
			unset($box['identifier']);
		
		} elseif ($box['key']['eggId']) {
			$box['identifier']['label'] = ze\admin::phrase('Nested plugin');
			$box['identifier']['value'] = ' ';
			
		} else {
			$box['identifier']['value'] = ze\plugin::codeName($box['key']['instanceId'], $box['key']['moduleClassName']);
			$box['identifier']['label'] = $ucPluginAdminName;
		}
		
		if ($box['key']['isVersionControlled']) {
			$box['css_class'] .= ' zenario_wireframe_plugin_settings';
		} else {
			$box['css_class'] .= ' zenario_reusable_plugin_settings';
	
			if ($box['key']['eggId']) {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			} elseif ($box['key']['instanceId']) {
				ze\priv::exitIfNot('_PRIV_VIEW_REUSABLE_PLUGIN');
			} else {
				ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
			}
		}


		$canEdit = false;
		if ($box['key']['isVersionControlled']) {
			if (ze\content::isDraft($status = ze\content::status($box['key']['cID'], $box['key']['cType'])) || $box['key']['eggId']) {
				$canEdit = ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
			} else {
				$canEdit =
					$status == 'published'
				 && ze\priv::check('_PRIV_CREATE_REVISION_DRAFT', $box['key']['cID'], $box['key']['cType'])
				 && $box['key']['cVersion'] == ze\content::latestVersion($box['key']['cID'], $box['key']['cType']);
			}

		} else {
			if ($box['key']['eggId']) {
				$canEdit = ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN');
			} else {
				$canEdit = ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN');
			}
		}

		$title = '';

		switch ($path) {
			case 'plugin_settings':

				if (empty($instance['name'])) {
					//Load the XML description for this plugin, and get the default instance name
					$desc = false;
					if (ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
						$instanceName = $desc['default_instance_name'];
					} else {
						$instanceName = '';
					}
				} else {
					$instanceName = $instance['name'];
				}
				
				$titleMrg = [
					'module' => $module['display_name'],
					'instanceName' => $instanceName
				];

				//If this is a new instance, try and ensure that the name we are suggesting is unique
				if (!$box['key']['instanceId']) {
					$count = ze\row::count('plugin_instances', ['name' => ['LIKE' => $instanceName. '%']]);
					if ($count) {
						$instanceName .= ' ('. ($count + 1). ')';
					}
				}
		
				$values['first_tab/instance_name'] = $instanceName;
				
				
				//Load the current values of the plugin settings from the database
				$valuesInDB = [];
				ze\tuix::loadAllPluginSettings($box, $valuesInDB);
				
				
				//Work out which mode and framework this plugin is using
				$box['key']['lastMode'] = $box['key']['mode'];
				$box['key']['moduleClassNameForPhrases'] = $module['vlp_class'];
				
				if ($box['key']['framework']) {
					self::setupOverridesForPhrasesInFrameworks(false, $box, $valuesInDB);
				}
				
				

				if (!empty($box['tabs']) && is_array($box['tabs'])) {
					foreach ($box['tabs'] as $tabName => &$tab) {
						if (is_array($tab)) {
							if (!$canEdit) {
								$tab['edit_mode'] = ['enabled' => false];
							} else {
								if (empty($tab['edit_mode'])) {
									$tab['edit_mode'] = [];
								}
			
								$tab['edit_mode']['enabled'] = true;
								$tab['edit_mode']['always_on'] = true;
								$tab['edit_mode']['enable_revert'] = true;
							}
						
							if (!empty($tab['fields']) && is_array($tab['fields'])) {
								foreach ($tab['fields'] as $fieldName => &$field) {
									if (is_array($field)) {
										if ($name = $field['plugin_setting']['name'] ?? false) {
											if (!isset($field['plugin_setting']['value'])) {
												$field['plugin_setting']['value'] = $field['value'] ?? '';
											}
											if (isset($valuesInDB[$name])) {
												$field['value'] = $valuesInDB[$name];
											}
										}
									}
								}
							}
						}
					}
				}


				//Hide the name for plugins that don't use this (e.g. nested/version controlled plugins)
				if ($box['key']['eggId'] || $box['key']['isVersionControlled']) {
					$fields['first_tab/plugin_name']['hidden'] =
					$fields['first_tab/instance_name']['hidden'] =
					$fields['first_tab/duplicate_or_rename']['hidden'] = true;
				
				} else {
					//Show/hide the various options for renaming/duplicating
					$a = $fields['first_tab/duplicate_or_rename']['values']['rename']['hidden'] = !$this->canRenamePlugin($box);
					$b = $fields['first_tab/duplicate_or_rename']['values']['replace']['hidden'] = !$this->canReplacePlugin($box);
					$c = $fields['first_tab/duplicate_or_rename']['values']['duplicate']['hidden'] = !$this->canDuplicatePlugin($box);
					
					if ($a) {
						if ($b && $c) {
							//If all are hidden, hide the field completely.
							$fields['first_tab/duplicate_or_rename']['hidden'] = true;
							
							$fields['first_tab/instance_name']['redraw_onchange'] =
							$fields['first_tab/instance_name']['redraw_immediately_onchange'] = false;
						} else {
							$fields['first_tab/instance_name']['side_note'] = ze\admin::phrase('Type here to save as a new plugin.');
						}
					} else {
						if ($b && $c) {
							$fields['first_tab/instance_name']['side_note'] = ze\admin::phrase('Type here to rename this plugin.');
						} else {
							$fields['first_tab/instance_name']['side_note'] = ze\admin::phrase('Type here to rename this plugin or save as a new plugin.');
							
							$fields['first_tab/duplicate_or_rename']['pre_field_html'] =
								'<div class="zfab_plugin_rename_warning warning_icon">'.
									ze\admin::phrase("You've changed this plugin's name; please select whether you want to:").
								'</div>';
						}
					}
				}
		
		
				// Get admin box title
				
				if ($box['key']['eggId'] && $box['key']['isVersionControlled']) {
					if ($box['key']['isSlideshow']) {
						$title = ze\admin::phrase('[[module]], in version controlled slideshow [[instanceName]]', $titleMrg);
					} else {
						$title = ze\admin::phrase('[[module]], in version controlled nest [[instanceName]]', $titleMrg);
					}
		
				} elseif ($box['key']['eggId']) {
					if ($box['key']['isSlideshow']) {
						$title =  ze\admin::phrase('[[module]], in slideshow "[[instanceName]]"', $titleMrg);
					} else {
						$title =  ze\admin::phrase('[[module]], in nest "[[instanceName]]"', $titleMrg);
					}
		
				} elseif ($box['key']['isVersionControlled']) {
					$title = ze\admin::phrase('Version controlled [[module]]', $titleMrg);
		
				} elseif ($box['key']['instanceId']) {
					switch ($module['class_name']) {
						case 'zenario_plugin_nest':
							$title = ze\admin::phrase('Nest');
							break;
						default:
							$title = $titleMrg['module'];
					}
		
				} else {
					switch ($module['class_name']) {
						case 'zenario_plugin_nest':
							$title = ze\admin::phrase('New nest');
							break;
						default:
							$title = 
								ze\admin::phrase('New [[module]]', $titleMrg);
					}
				}
		
				// Get modules description file
				$moduleDescription = "No module decription found for this plugin.";
				$path = ze\moduleAdm::descriptionFilePath($module['class_name']);
		
				$tags = ze\tuix::readFile(CMS_ROOT . $path);
				if ($tags && isset($tags['description']) && $tags['description']) {
					$moduleDescription = $tags['description'];
			
				//check inheritance 
				} else if ($tags && isset($tags['inheritance']['inherit_description_from_module']) && $tags['inheritance']['inherit_description_from_module']) {
					$path = ze\moduleAdm::descriptionFilePath($tags['inheritance']['inherit_description_from_module']);
					$tags = ze\tuix::readFile(CMS_ROOT . $path);
					if ($tags && isset($tags['description']) && $tags['description']) {
						$moduleDescription = $tags['description'];
					}
				}
				$fields['last_tab/module_description']['snippet']['html'] = 
					'<div class="module_description">' . $moduleDescription . '</div>';
				
				
				//Load a list of TUIX Snippet names defined on the site
				$ord = 0;
				foreach (ze\row::getValues('tuix_snippets', 'name', [], 'name', 'id') as $tuixSnippetId => $tuixSnippetName) {
					$fields['tuix_snippet/~tuix_snippet~']['values'][$tuixSnippetId] = [
						'ord' => ++$ord,
						'label' => $tuixSnippetName. ' (ID '. $tuixSnippetId. ')'
					];
				}
				
				if (empty($fields['tuix_snippet/~tuix_snippet~']['values'])) {
					$fields['tuix_snippet/~tuix_snippet~']['empty_value'] = ze\admin::phrase(' -- No TUIX snippets defined for this site -- ');
				}
				
				//Add a link to the TUIX Snippets panel
				$fields['tuix_snippet/desc2']['snippet']['html'] =
					'<a
						target="_blank"
						href="'. ze\link::absolute(). 'zenario/admin/organizer.php#zenario__modules/panels/tuix_snippets"
					>'. ze\admin::phrase('Create/edit TUIX Snippets'). '</a>';
		
			
			//Experimenting with having plugin settings and frameworks visible on the same tab again
			//	break;
			//
			//case 'plugin_css_and_framework':
		
				$titleMrg = [
					'module' => $module['display_name'],
					'instanceName' => $instanceName
				];
		
				if ($canEdit) {
					$box['tabs']['all_css_tab']['edit_mode'] =
					$box['tabs']['this_css_tab']['edit_mode'] =
					$box['tabs']['framework_tab']['edit_mode'] = ['enabled' => true];
				}

				//Load the values from the database
				if ($box['key']['eggId']) {
					$sql = "
						SELECT name_or_title, framework, css_class
						FROM ". DB_PREFIX. "nested_plugins
						WHERE id = ". (int) $box['key']['eggId'];
	
					$result = ze\sql::select($sql);
					$row = ze\sql::fetchAssoc($result);
					$values['framework_tab/framework'] = $framework = $row['framework'];
					$values['this_css_tab/css_class'] = $row['css_class'];

				} else {
					$values['framework_tab/framework'] = $framework = $instance['framework'];
					$values['this_css_tab/css_class'] = $instance['css_class'];
				}
				
				$fields['this_css_tab/css_class']['custom__default'] =
					$module['css_class_name']. '__default_style';
				
				if ($values['this_css_tab/css_class'] == '') {
					$values['this_css_tab/css_class'] = $fields['this_css_tab/css_class']['custom__default'];
				}


				//Look for frameworks
				$fields['framework_tab/framework']['values'] = ze\pluginAdm::listFrameworks($module['class_name']);

				if (!empty($fields['framework_tab/framework']['values'])) {
					if ($module['default_framework']
					 && isset($fields['framework_tab/framework']['values'][$module['default_framework']])) {
						$fields['framework_tab/framework']['values'][$module['default_framework']]['label'] .=
							ze\admin::phrase(' (default)');
					}
					if (!isset($fields['framework_tab/framework']['values'][$framework])) {
						$fields['framework_tab/framework']['values'][$framework] =
							['ord' => 0, 'label' => ze\admin::phrase('[[framework]] (missing from filesystem)', ['framework' => $framework])];
					}

				} else {
					$fields['framework_tab/framework']['hidden'] =
					$fields['framework_tab/framework_path']['hidden'] =
					$fields['framework_tab/framework_source']['hidden'] = true;
					$fields['framework_tab/no_frameworks_message']['hidden'] = false;
				}
				
				
				$thisCSSPath = $this->getPluginCSSFilepath($box, true);
				$thisCSSName = $this->getPluginCSSName($box, true);
				$allCSSPath = $this->getPluginCSSFilepath($box, false);
				$allCSSName = $this->getPluginCSSName($box, false);
				
				$skinIsEditable = (bool) ze\row::get('skins', 'enable_editable_css', $box['key']['skinId']);
				
				if ($box['key']['skinId']) {
					$module['display_name_plural'] = ze\admin::pluralPhrase($module['display_name']);
					
					$fields['this_css_tab/css_path']['snippet']['label'] = $thisCSSPath;
					$values['this_css_tab/css_filename'] = basename($thisCSSPath);
					
					$fields['all_css_tab/css_class']['snippet']['span'] = $module['css_class_name'];
					$fields['all_css_tab/css_path']['snippet']['label'] = $allCSSPath;
					$values['all_css_tab/css_filename'] = basename($allCSSPath);
					
					if ($thisCSSPath) {
						$file_exists = file_exists($filepath = CMS_ROOT. $thisCSSPath);
						
						if (!$skinIsEditable) {
							$is_writable = false;
							$fields['this_css_tab/use_css_file']['side_note'] = ze\admin::phrase("The ability to edit CSS for this skin has been disabled. Edit the skin's <code>description.yaml</code> file to enable it.");
						
						} elseif (!ze\priv::check('_PRIV_EDIT_CSS')) {
							$is_writable = false;
							$fields['this_css_tab/use_css_file']['side_note'] = ze\admin::phrase('Disabled because your administrator account does not have the <em>Edit skin and plugin CSS</em> permission.');
						
						} else {
							if ($file_exists) {
								$is_writable = is_writable($filepath);
							} else {
								$is_writable = is_writable(CMS_ROOT. $this->skinWritableDir);
							}
							
							if (!$is_writable) {
								$fields['this_css_tab/use_css_file']['side_note'] = ze\admin::phrase('The &quot;editable_css&quot; directory is not writable.');
							}
						}
						
						$values['this_css_tab/use_css_file'] = $file_exists;
						$fields['this_css_tab/use_css_file']['hidden'] = !$file_exists && !$skinIsEditable;
						$fields['this_css_tab/use_css_file']['readonly'] =
						$fields['this_css_tab/css_source']['readonly'] = !$is_writable;
				
						if ($file_exists
						 && ($css = file_get_contents($filepath))
						 && (trim($css))) {
							$values['this_css_tab/css_source'] = $css;
						} else {
							$values['this_css_tab/css_source'] =
'.'. $thisCSSName. ' {
	/* This class will be applied to just this '. $module['pluginAdminName']. '. */
}';
						}
					} else {
						$fields['this_css_tab/use_css_file']['hidden'] =
						$fields['this_css_tab/css_path']['hidden'] =
						$fields['this_css_tab/css_source']['hidden'] = true;
					}
					
					
					$file_exists = file_exists($filepath = CMS_ROOT. $allCSSPath);
					
					if (!$skinIsEditable) {
						$is_writable = false;
						$fields['all_css_tab/use_css_file']['side_note'] = ze\admin::phrase("The ability to edit CSS for this skin has been disabled. Edit the skin's <code>description.yaml</code> file to enable it.");
					
					} elseif (!ze\priv::check('_PRIV_EDIT_CSS')) {
						$is_writable = false;
						$fields['all_css_tab/use_css_file']['side_note'] = ze\admin::phrase('Disabled because your administrator account does not have the <em>Edit skin and plugin CSS</em> permission.');
					
					} else {
						if ($file_exists) {
							$is_writable = is_writable($filepath);
						} else {
							$is_writable = is_writable(CMS_ROOT. $this->skinWritableDir);
						}
						
						if (!$is_writable) {
							$fields['all_css_tab/use_css_file']['side_note'] = ze\admin::phrase('The &quot;editable_css&quot; directory is not writable.');
						}
					}
					
					$values['all_css_tab/use_css_file'] = $file_exists;
					$fields['all_css_tab/use_css_file']['hidden'] = !$file_exists && !$skinIsEditable;
					$fields['all_css_tab/use_css_file']['readonly'] =
					$fields['all_css_tab/css_source']['readonly'] = !$is_writable;
				
					if ($file_exists
					 && ($css = file_get_contents($filepath))
					 && (trim($css))) {
						$values['all_css_tab/css_source'] = $css;
					} else {
						$values['all_css_tab/css_source'] =
'.'. $module['css_class_name']. ' {
	/* This class will be applied to all '. $module['pluginsOfThisType']. '. */
}

.'. $module['css_class_name']. '__default_style {
	/* This class will be applied to all '. $module['pluginsOfThisType']. ',
	   unless overridden or removed for a specific '. $module['pluginAdminName']. '. */
}';
					}
					
					$fields['this_css_tab/css_class']['onchange'] = "
						if (this.value == '') {
							this.value = '". $module['css_class_name']. "__default_style';
						}
					";
					
					$fields['this_css_tab/css_class']['pre_field_html'] =
						'<span class="zenario_css_class_label">'. htmlspecialchars(
							$module['css_class_name']. ' '. ($thisCSSName? $thisCSSName. ' ' : '')
						). '</span>';
					
					$fields['this_css_tab/css_class']['label'] = ze\admin::phrase('CSS classes for this [[pluginAdminName]] of the module [[class_name]]:', $module);
					$fields['this_css_tab/css_class']['side_note'] = ze\admin::phrase("A module-wide class is always applied, and a numbered class for this particular [[pluginAdminName]]. You can use this text box to add any additional classes you need. The text box reverts to the module's default style if not specified otherwise.", $module);
					$fields['this_css_tab/use_css_file']['label'] = ze\admin::phrase('Enter CSS for this [[pluginAdminName]]:', $module);
				
					$fields['all_css_tab/css_class']['label'] = ze\admin::phrase('CSS class for all [[pPluginAdminName]] of the module [[class_name]]:', $module);
					$fields['all_css_tab/css_class']['side_note'] = ze\admin::phrase('A module-wide class is always applied.');
					$fields['all_css_tab/use_css_file']['label'] = ze\admin::phrase('Enter CSS for all plugins of the module [[class_name]]:', $module);
					
					if ($module['css_class_name']
					 && $module['css_class_name'] != $module['class_name']) {
					 	$fields['this_css_tab/css_inheritance_note']['hidden'] =
					 	$fields['all_css_tab/css_inheritance_note']['hidden'] = false;
						$fields['this_css_tab/css_inheritance_note']['note_below'] =
						$fields['all_css_tab/css_inheritance_note']['note_below'] = ze\admin::phrase('Note: [[pPluginAdminName]] of the module [[class_name]] use CSS from the module [[css_class_name]].', $module);
					}
					
					$box['tabs']['all_css_tab']['label'] = ze\admin::phrase('CSS (all [[pluginsOfThisType]])', $module);
				} else {
					$box['tabs']['cant_determine_which_skin']['hidden'] = false;
					$box['tabs']['this_css_tab']['hidden'] = true;
					$box['tabs']['all_css_tab']['hidden'] = true;
				}
		
				break;
		}


		$box['title'] = $title;
		
		//Set a flag if this is a plugin in a conductor
		$box['key']['usesConductor'] = ze\pluginAdm::conductorEnabled($box['key']['instanceId']);
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				
				//Change the framework phrases if the mode has changed
				if ($box['key']['framework']) {
					$mode = $values['mode'] ?? '';
				
					if ($box['key']['lastMode'] != $mode) {
						$box['key']['lastMode'] = $box['key']['mode'] = $mode;
					
						self::setupOverridesForPhrasesInFrameworks(true, $box);
					}
				}
				
				
				//If the user picks a TUIX Snippet, show the source code for reference.
				$tuixSnippet = $values['tuix_snippet/~tuix_snippet~'] ?? '';
			
				if ($box['key']['lastTuixSnippet'] != $tuixSnippet) {
					$box['key']['lastTuixSnippet'] = $tuixSnippet;
					
					if ($tuixSnippet) {
						$fields['tuix_snippet/~custom_yaml~']['hidden'] = false;
						$values['tuix_snippet/~custom_yaml~'] = ze\row::get('tuix_snippets', 'custom_yaml', $tuixSnippet);
					} else {
						$fields['tuix_snippet/~custom_yaml~']['hidden'] = true;
						$values['tuix_snippet/~custom_yaml~'] = '';
					}
				}
				
				
			//Experimenting with having plugin settings and frameworks visible on the same tab again
			//	break;
			//case 'plugin_css_and_framework':
			
			
				if (!empty($values['framework_tab/framework'])) {

					$module = ze\module::details($box['key']['moduleId']);

					if ($frameworkFile = ze\plugin::frameworkPath($values['framework_tab/framework'], $module['class_name'])) {
		
						$mode = $box['key']['mode'];
						if ($mode
						 && file_exists($modeDir = dirname($frameworkFile). '/modes/'. $mode. '.twig.html')) {
							
							$values['framework_tab/framework_source'] = file_get_contents($modeDir);
							$fields['framework_tab/framework_source']['language'] = $modeDir;
							$fields['framework_tab/framework_path']['hidden'] = false;
							$fields['framework_tab/framework_path']['snippet']['label'] = $modeDir;
						
						} else {
							$values['framework_tab/framework_source'] = file_get_contents($frameworkFile);
							$fields['framework_tab/framework_source']['language'] = $frameworkFile;
							$fields['framework_tab/framework_path']['hidden'] = false;
							$fields['framework_tab/framework_path']['snippet']['label'] = $frameworkFile;
						}

					} else {
						$values['framework_tab/framework_source'] = '';
						$fields['framework_tab/framework_source']['language'] = '';
						$fields['framework_tab/framework_path']['hidden'] = true;
						$fields['framework_tab/framework_path']['snippet']['label'] = '';
					}
					
					$box['tabs']['this_css_tab']['notices']['golive']['show'] =
						$box['key']['isVersionControlled']
					 && empty($fields['this_css_tab/use_css_file']['readonly'])
					 && $values['this_css_tab/use_css_file'];

				}
		
				break;
		}
		
	}
	
	//Should we offer the ability to rename the plugin
	protected function canRenamePlugin($box) {
		return $box['key']['instanceId'] && !$box['key']['eggId'] && !$box['key']['isVersionControlled'];
	}
	
	//Should we offer the ability to duplicate the plugin
	protected function canDuplicatePlugin($box) {
		return $this->canRenamePlugin($box) && !$this->canReplacePlugin($box);
	}
	
	//If editing a library plugin from the front-end, check to see if the admin is allowed to copy/replace the plugin
	protected function canReplacePlugin($box) {
		if (!$this->canRenamePlugin($box)
		 || !$box['key']['cID']
		 || !$box['key']['frontEnd']
		 || !$box['key']['instanceId']
		 || $box['key']['isVersionControlled']) {
			return false;
		}
		
		if (ze\row::exists('plugin_item_link', [
			'instance_id' => $box['key']['instanceId'],
			'content_id' => $box['key']['cID'],
			'content_type' => $box['key']['cType'],
			'content_version' => $box['key']['cVersion'],
			'slot_name' => $box['key']['slotName']
		])) {
			if (ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $box['key']['cID'], $box['key']['cType'])) {
				return 'item';
			}
		}
		
		if (ze\row::exists('plugin_layout_link', [
			'instance_id' => $box['key']['instanceId'],
			'layout_id' => ze\content::layoutId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']),
			'slot_name' => $box['key']['slotName']
		])) {
			if (ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
				return 'layout';
			}
		}
		
		return false;
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		$duplicate_or_rename = '';
		
		switch ($path) {
			case 'plugin_settings':
				
				$duplicate_or_rename = $values['first_tab/duplicate_or_rename'];
				
				
				if ($this->canRenamePlugin($box)) {
					if ($fields['first_tab/instance_name']['current_value'] != $fields['first_tab/instance_name']['value']) {
						if (!$fields['first_tab/instance_name']['value']) {
							$fields['first_tab/instance_name']['error'] = ze\phrase::admin('Please enter a new name for this plugin.');
						
						} else {
							//Check to see if an instance of that name already exists
							$sql = "
								SELECT 1
								FROM ". DB_PREFIX. "plugin_instances
								WHERE name =  '". ze\escape::sql($values['first_tab/instance_name']). "'
								  AND id != ". (int) $box['key']['id'];
				
							if (ze\sql::fetchRow($sql)) {
								$fields['first_tab/instance_name']['error'] =
									ze\admin::phrase('A plugin with the name "[[name]]" already exists. Please choose a different name.', ['name' => $values['first_tab/instance_name']]);
							}
						}
					}
				}
				
		}
		
		
		$box['confirm']['show'] = false;
		if (!$box['key']['instanceId']) {
			ze\pluginAdm::create(
				$box['key']['moduleId'],
				$values['first_tab/instance_name'],
				$box['key']['instanceId'],
				$box['tabs']['first_tab']['errors'],
				$onlyValidate = true);

		} else {
			$instance = ze\plugin::details($box['key']['instanceId']);
	
			if ($instance['content_id']) {
				if (!ze\content::isDraft($status = ze\content::status($instance['content_id'], $instance['content_type']))) {
					if ($status != 'published') {
						$box['tabs']['first_tab']['errors'][] = ze\admin::phrase('This content item is not a draft and cannot be edited.');
					} else {
						$box['confirm']['show'] = true;
					}
				}
			
			} elseif ($duplicate_or_rename == 'duplicate') {
				//Add a warning when duplicating
				$box['confirm']['show'] = true;
				$box['confirm']['button_message'] = ze\admin::phrase('Save');
				$box['confirm']['message'] = 
					ze\admin::phrase("This will create a new plugin oin the library with the name \"[[name]]\".\n\nProceed?",
						['name' => $values['first_tab/instance_name']]
					);
			
			} elseif ($duplicate_or_rename == 'replace') {
				//Add a warning when replacing...
				$box['confirm']['show'] = true;
				$box['confirm']['button_message'] = ze\admin::phrase('Save');
				$box['confirm']['message'] = 
					ze\admin::phrase("This will create a new plugin with the name \"[[name]]\".\n\nThe new plugin will be inserted into the slot.\n\nProceed?",
						['name' => $values['first_tab/instance_name']]
					);
				
			} else {
				$mrg = [
					'pages' => ze\pluginAdm::usage($box['key']['instanceId'], false),
					'published' => ze\pluginAdm::usage($box['key']['instanceId'], true)];
		
				if ($mrg['published'] > 0) {
			
					$box['confirm']['show'] = true;
					$box['confirm']['html'] = true;
					$box['confirm']['button_message'] = ze\admin::phrase('Save');
			
					$box['confirm']['message'] = 
						'<p>'. ze\admin::phrase('You are changing the settings of this plugin. The change will be <b>immediate</b> and cannot be undone.'). '</p>';
					
					if ($mrg['pages'] > 1) {
						$mrg['link'] = htmlspecialchars(ze\pluginAdm::usageOrganizerLink($instance['instance_id'], $instance['module_id']));
						if ($mrg['published'] > 1) {
							$box['confirm']['message'] .= 
								'<p>'. ze\admin::phrase('This will affect [[published]] content items immediately (as they are published), [[pages]] items in total.', $mrg). '</p>';
						} else {
							$box['confirm']['message'] .= 
								'<p>'. ze\admin::phrase('This will affect 1 content item immediately (as it is published), [[pages]] items in total.', $mrg). '</p>';
						}
						
						$box['confirm']['message'] .= 
							'<p>'. ze\admin::phrase('<a href="[[link]]" target="_blank">Click for a list of all content items affected</a> (this can be found normally in Organizer, under Modules).', $mrg). '</p>';
					
					} else
					if ($mrg['published'] == 1
					 && ($citems = ze\pluginAdm::usage($box['key']['instanceId'], true, false, true))
					 && (!empty($citems))
					 && ($mrg['tag'] = ze\content::formatTag($citems[0]['id'], $citems[0]['type']))) {
						
						$box['confirm']['message'] .= 
							'<p>'. ze\admin::phrase('This will affect the content item &quot;[[tag]]&quot; immediately as it is published.', $mrg). '</p>';
					}
				
				} elseif ($path == 'plugin_settings' && $fields['first_tab/instance_name']['current_value'] != $fields['first_tab/instance_name']['value'] && $this->canRenamePlugin($box)) {
					//Add a warning when just renaming...
					$box['confirm']['show'] = true;
					$box['confirm']['button_message'] = ze\admin::phrase('Save');
					$box['confirm']['message'] = ze\admin::phrase("Rename this plugin?");
				}
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$module = ze\module::details($box['key']['moduleId']);
		$instance = [];
		
		
		//Handle duplicating/renaming
		if ($path == 'plugin_settings') {
			if ($fields['first_tab/instance_name']['current_value'] != $fields['first_tab/instance_name']['value']) {
				if ($values['first_tab/duplicate_or_rename'] == 'rename' && $this->canRenamePlugin($box)) {
					$eggId = false;
					ze\pluginAdm::rename($box['key']['instanceId'], $eggId, $values['first_tab/instance_name'], $createNewInstance = false);
			
				} elseif ($values['first_tab/duplicate_or_rename'] == 'duplicate' && $this->canDuplicatePlugin($box)) {
					$eggId = false;
					ze\pluginAdm::rename($box['key']['instanceId'], $eggId, $values['first_tab/instance_name'], $createNewInstance = true);
					$box['key']['id'] = $box['key']['instanceId'];
			
				} elseif ($values['first_tab/duplicate_or_rename'] == 'replace' && ($level = $this->canReplacePlugin($box))) {
					$eggId = false;
					ze\pluginAdm::rename($box['key']['instanceId'], $eggId, $values['first_tab/instance_name'], $createNewInstance = true);
					$box['key']['id'] = $box['key']['instanceId'];
				
					switch ($level) {
						case 'item':
							//Insert a Reuasble Plugin into a slot
							ze\pluginAdm::updateItemSlot($box['key']['instanceId'], $box['key']['slotName'], $box['key']['cID'], $box['key']['cType']);
							break;
					
						case 'layout':
							ze\pluginAdm::updateLayoutSlot($box['key']['instanceId'], $box['key']['slotName'], ze\content::layoutId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']));
							break;
					}
			
				}
			}
		}
		
		
		if ($box['key']['instanceId']) {
			$instance = ze\plugin::details($box['key']['instanceId']);
		}

		//Load details of this Instance, and check for permissions to save
		if (!empty($instance['content_id'])) {
	
			//If this Wireframe is already on a draft, then there's no need to create one
			if (ze\content::isDraft($instance['content_id'], $instance['content_type'])) {
				ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version']);
	
			//Don't create a draft for Nested modules, the interface can't handle this
			} elseif ($box['key']['eggId']) {
				return;
	
			//Otherwise create a new draft
			} else {
				ze\priv::exitIfNot('_PRIV_CREATE_REVISION_DRAFT', $instance['content_id'], $instance['content_type']);
		
				//Create a new Content Item, or a new Draft of a Content Item, if this wireframe isn't already on a draft.
				$cVersionTo = $instance['content_version'];
				ze\contentAdm::createDraft($instance['content_id'], $instance['content_id'], $instance['content_type'], $cVersionTo, $instance['content_version']);
				$box['key']['cVersion'] = $cVersionTo;
		
				//This wireframe will now be using a new instance id on the newly created draft
				$box['key']['instanceId'] =
					ze\plugin::vcId(
						$instance['content_id'], $instance['content_type'], $cVersionTo, $instance['slot_name'], $instance['module_id']);
			
				//Remove the slot name, to force the CMS to reload the entire page
				$box['key']['slotName'] = false;
			}

		} elseif ($box['key']['eggId']) {
			ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');

		} else {
			ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
	
			//Handle creating a new instance
			if (!$box['key']['instanceId']) {
				$errors = [];
				ze\pluginAdm::create(
					$box['key']['moduleId'],
					$values['first_tab/instance_name'],
					$box['key']['instanceId'],
					$errors);
		
				$box['key']['id'] = $box['key']['instanceId'];
				$instance = ze\plugin::details($box['key']['instanceId']);
			}
		}

		$syncContent = false;
		$pk = [
			'instance_id' => $box['key']['instanceId'],
			'egg_id' => $box['key']['eggId']];


		switch ($path) {
			case 'plugin_settings':
				//Loop through each field that would be in the Admin Box, and has the <plugin_setting> tag set
				if (!empty($box['tabs']) && is_array($box['tabs'])) {
					foreach ($box['tabs'] as $tabName => &$tab) {
						if (is_array($tab) && ze\ring::engToBoolean($box['tabs'][$tabName]['edit_mode']['on'] ?? false)) {
							if (!empty($tab['fields']) && is_array($tab['fields'])) {
								foreach ($tab['fields'] as $fieldName => &$field) {
									if (is_array($field)) {
										if (!empty($field['plugin_setting']['name'])) {
											
											$ps = $field['plugin_setting'];
											
											$pk['name'] = $ps['name'];
											
											$defaultValue = '';
											if (isset($ps['value'])) {
												$defaultValue = $ps['value'];
											} elseif (isset($field['value'])) {
												$defaultValue = $field['value'];
											}
											
											//Don't save a value for a field if it was hidden...
											if (empty($ps['save_value_when_hidden'])
											 && (ze\ring::engToBoolean($tab['hidden'] ?? false)
											  || ze\ring::engToBoolean($tab['_was_hidden_before'] ?? false)
											  || ze\ring::engToBoolean($field['hidden'] ?? false)
											  || ze\ring::engToBoolean($field['_was_hidden_before'] ?? false))) {
												
												if ($ps['save_empty_value_when_hidden'] ?? $defaultValue) {
													//If a setting has a default value, we'll need to store a blank in the database
													//to make it clear that the field was hidden and not set
													ze\row::set('plugin_settings', ['value' => ''], $pk);
												} else {
													//Otherwise we can just delete the row
													ze\row::delete('plugin_settings', $pk);
												}
					
											//...or a multiple edit field that is not marked as changed
											} else
											if (isset($field['multiple_edit'])
											 && !$changes[$tabName. '/'. $fieldName]) {
												ze\row::delete('plugin_settings', $pk);
					
											//...or fields that have not changed, and have the "dont_save_default_value"
											//option set.
											} else
											if (!empty($ps['dont_save_default_value'])
											 && $defaultValue
											 && (!isset($field['current_value'])
											  || $field['current_value'] == $defaultValue)) {
												ze\row::delete('plugin_settings', $pk);
					
											} else {
												//Otherwise save the field in the plugin_settings table.
												$value = [];
												$value['value'] = ze\ray::value($values, $tabName. '/'. $fieldName);
								
								
												//Handle file/image uploaders by adding these files to the system
												if (!empty($field['upload'])) {
													$fileIds = [];
													foreach (ze\ray::explodeAndTrim($value['value']) as $file) {
														if ($location = ze\file::getPathOfUploadInCacheDir($file)) {
															$fileIds[] = ze\file::addToDatabase('image', $location);
														} else {
															$fileIds[] = $file;
														}
													}
													$value['value'] = implode(',', $fileIds);
												}
							
						
												//The various different types of foreign key should be registered
												if (!$value['value'] || empty($ps['foreign_key_to'])) {
													$value['dangling_cross_references'] = 'remove';
													$value['foreign_key_to'] = NULL;
													$value['foreign_key_id'] = 0;
													$value['foreign_key_char'] = '';
						
												} else {
													$value['dangling_cross_references'] = (($ps['dangling_cross_references'] ?? false) ?: 'remove');
													
													switch ($value['foreign_key_to'] = $ps['foreign_key_to']) {
														case 'categories':
															$value['foreign_key_id'] = 0;
															$value['foreign_key_char'] = '';
															break;
														
														case 'content':
															$cID = $cType = false;
															ze\content::getCIDAndCTypeFromTagId($cID, $cType, $value['value']);
								
															$value['foreign_key_id'] = $cID;
															$value['foreign_key_char'] = $cType;
															break;
													
														case 'email_template':
															$value['foreign_key_id'] = 0;
															$value['foreign_key_char'] = $value['value'];
															break;
													
														case 'category':
														case 'document':
														case 'file':
														case 'menu_section':
														case 'user_form':
															$value['foreign_key_id'] = $value['value'];
															$value['foreign_key_char'] = '';
															break;
														
														//Don't try to record multiple files in the index
														case 'multiple_files':
															$value['foreign_key_id'] = 0;
															$value['foreign_key_char'] = '';
															break;
													
														default:
															if (is_numeric($value['value'])) {
																$value['foreign_key_id'] = $value['value'];
																$value['foreign_key_char'] = $value['value'];
							
															} else {
																$value['foreign_key_id'] = 0;
																$value['foreign_key_char'] = $value['value'];
															}
															break;
													}
												}
								
												//Work out whether this is a version controlled or synchronized Instance
												if (!$instance['content_id']) {
													$value['is_content'] = 'synchronized_setting';
											
						
												} elseif (ze\ring::engToBoolean($ps['is_searchable_content'] ?? false)) {
													$value['is_content'] = 'version_controlled_content';
													$syncContent = true;
						
												} else {
													$value['is_content'] = 'version_controlled_setting';
							
													if (ze::in($ps['foreign_key_to'] ?? false, 'file', 'multiple_files')) {
														$syncContent = true;
													}
												}
						
												if (!$trimedValue = trim($value['value'])) {
													$value['format'] = 'empty';
						
												} elseif (html_entity_decode($trimedValue) != $trimedValue || strip_tags($trimedValue) != $trimedValue) {
													if (ze\ring::engToBoolean($ps['translate'] ?? false)) {
														$value['format'] = 'translatable_html';
													} else {
														$value['format'] = 'html';
													}
						
												} else {
													if (ze\ring::engToBoolean($ps['translate'] ?? false)) {
														$value['format'] = 'translatable_text';
													} else {
														$value['format'] = 'text';
													}
												}
								
												if (isset($ps['is_email_address'])) {
													$value['is_email_address'] = $ps['is_email_address'];
												} else {
													$value['is_email_address'] = NULL;
												}
								
												ze\row::set('plugin_settings', $value, $pk);
											}
										}
									}
								}
							}
						}
					}
				}
				
				//Set the Nested Plugin's name
				if ($box['key']['instanceId']){
					//For Nested Plugins, check to see if there is a Plugin Setting with the <use_value_for_plugin_name> tag set,
					//which should be the name of the Nested Plugin
					//Empty or Hidden fields don't count; otherwise the value of <use_value_for_plugin_name> indicates which field has priority.
					$eggName = false;
					$eggNameCurrentPriority = false;
					foreach ($box['tabs'] as $tabName => &$tab) {
						if (is_array($tab)
						 && !ze\ring::engToBoolean($tab['hidden'] ?? false)
						 && !ze\ring::engToBoolean($tab['_was_hidden_before'] ?? false)
						 && !empty($tab['fields']) && is_array($tab['fields'])) {
			
							foreach ($tab['fields'] as $fieldName => &$field) {
								if (is_array($field)
								 && !empty($values[$tabName. '/'. $fieldName])
								 && !empty($field['plugin_setting']['use_value_for_plugin_name'])
								 && !ze\ring::engToBoolean($field['hidden'] ?? false)
								 && !ze\ring::engToBoolean($field['_was_hidden_before'] ?? false)
								 && ($eggNameCurrentPriority === false || $eggNameCurrentPriority > (int) $field['plugin_setting']['use_value_for_plugin_name'])) {
					
									$eggName = $values[$tabName. '/'. $fieldName];
									$editMode = ze\ring::engToBoolean($tab['edit_mode']['on'] ?? false)? '_' : '';
									$eggNameCurrentPriority = (int) $field['plugin_setting']['use_value_for_plugin_name'];
									
									//T10290 - for an internal link, only record the tag id and not the alias and publishing status
									// (which will get out of date!)
									if (empty($field['pick_items']['target_path'])
									 || $field['pick_items']['target_path'] != 'zenario__content/panels/content') {
										
										//Attempt to get a display value, rather than the actual value
										$items = explode(',', $eggName);
										if (!empty($field['values'][$items[0]])) {
										    $eggName = !is_array($field['values'][$items[0]]) ? $field['values'][$items[0]] : $field['values'][$items[0]]['label'];
					
										} elseif (!empty($field['values'][$eggName])) {
											$eggName = $field['values'][$eggName];
					
										} elseif (!empty($field['_display_value'])) {
											$eggName = $field['_display_value'];
										}
									}
								}
							}
						}
					}
					
					if (is_array($eggName)) {
						$eggName = $eggName['label'] ?? false;
					}
	
					if ($eggName) {
						$eggName = ze\module::displayName($box['key']['moduleId']). ': '. $eggName;
					} else {
						$eggName = ze\module::displayName($box['key']['moduleId']);
					}
					$nestedpk = [
						'instance_id' => $box['key']['instanceId'],
						'egg_id' => $box['key']['eggId'],
						'name' => 'nested_title'
						];
					$nestedvalue['instance_id'] = $box['key']['instanceId'];
					$nestedvalue['name'] = 'nested_title';
					$nestedvalue['egg_id'] = $box['key']['eggId'];
					$nestedvalue['value'] = $eggName;
					ze\row::set('plugin_settings', $nestedvalue, $nestedpk);
				}
				if($box['key']['eggId']) {	
					ze\row::update('nested_plugins', ['name_or_title' => mb_substr($eggName, 0, 250, 'UTF-8')], $box['key']['eggId']);
				}

				if ($instance['content_id']) {
					if ($syncContent) {
						ze\contentAdm::syncInlineFileContentLink($instance['content_id'], $instance['content_type'], $instance['content_version']);
					}
	
					//Update the last modified date on the Content Item if this is a Wireframe Plugin
					ze\contentAdm::updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);

				} else {
					ze\contentAdm::resyncLibraryPluginFiles($box['key']['instanceId'], $instance);
				}
				
				//Update the request vars for this slide
				ze\pluginAdm::setSlideRequestVars($box['key']['instanceId']);
				
				
			//Experimenting with having plugin settings and frameworks visible on the same tab again
			//	break;
			//
			//case 'plugin_css_and_framework':
				
				if ($values['this_css_tab/css_class'] == $fields['this_css_tab/css_class']['custom__default']) {
					$values['this_css_tab/css_class'] = '';
				}
				
				//Save the framework, if set
				$vals = [];
				$vals['framework'] = $values['framework_tab/framework'];
				$vals['css_class'] = $values['this_css_tab/css_class'];
		
				if ($box['key']['eggId']) {
					ze\row::update('nested_plugins', $vals, $box['key']['eggId']);
				} else {
					ze\row::update('plugin_instances', $vals, $box['key']['instanceId']);
				}
				
				//Save the CSS files, if they were there
				if ($box['key']['skinId'] && ze\priv::check('_PRIV_EDIT_CSS') && ze\row::get('skins', 'enable_editable_css', $box['key']['skinId'])) {
					$thisCSSPath = $this->getPluginCSSFilepath($box, true);
					$thisCSSName = $this->getPluginCSSName($box, true);
					$allCSSPath = $this->getPluginCSSFilepath($box, false);
					$allCSSName = $this->getPluginCSSName($box, false);
					
					if ($thisCSSPath) {
						if (file_exists($filepath = CMS_ROOT. $thisCSSPath)) {
							if (is_writable($filepath)) {
								if ($values['this_css_tab/use_css_file']) {
									file_put_contents($filepath, $values['this_css_tab/css_source']);
								} else {
									unlink($filepath);
								}
							}
						} else {
							if ($values['this_css_tab/use_css_file']
							 && is_writable(CMS_ROOT. $this->skinWritableDir)) {
								file_put_contents($filepath, $values['this_css_tab/css_source']);
								\ze\cache::chmod($filepath, 0666);
							}
						}
					}
					
					if (file_exists($filepath = CMS_ROOT. $allCSSPath)) {
						if (is_writable($filepath)) {
							if ($values['all_css_tab/use_css_file']) {
								file_put_contents($filepath, $values['all_css_tab/css_source']);
							} else {
								unlink($filepath);
							}
						}
					} else {
						if ($values['all_css_tab/use_css_file']
						 && is_writable(CMS_ROOT. $this->skinWritableDir)) {
							file_put_contents($filepath, $values['all_css_tab/css_source']);
							\ze\cache::chmod($filepath, 0666);
						}
					}
					
					if (!($_REQUEST['_save_and_continue'] ?? false)) {
						//If the CSS files have changed, and we opened up from the front-end,
						//unset the slotName from the key to force the toolkit to reload the whole page.
						if ((isset($fields['this_css_tab/use_css_file']['current_value']) && $fields['this_css_tab/use_css_file']['current_value'] != $fields['this_css_tab/use_css_file']['value'])
						 || (isset($fields['this_css_tab/css_source']['current_value']) && $fields['this_css_tab/css_source']['current_value'] != $fields['this_css_tab/css_source']['value'])
						 || (isset($fields['all_css_tab/use_css_file']['current_value']) && $fields['all_css_tab/use_css_file']['current_value'] != $fields['all_css_tab/use_css_file']['value'])
						 || (isset($fields['all_css_tab/css_source']['current_value']) && $fields['all_css_tab/css_source']['current_value'] != $fields['all_css_tab/css_source']['value'])) {
					
							if ($box['key']['cID'] && $box['key']['cType'] && $box['key']['slotName']) {
								$_SESSION['scroll_slot_on_'. $box['key']['cType']. '_'. $box['key']['cID']] = $box['key']['slotName'];
								unset($box['key']['slotName']);
							}
						}
					}
					
					ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
				}
				
		
				break;
		}


		//Clear anything that is cached for this instance
		$sql = "
			DELETE
			FROM ". DB_PREFIX. "plugin_instance_store
			WHERE is_cache = 1
			  AND instance_id = ". (int) $box['key']['instanceId'];
		ze\sql::update($sql);
		
	}
	
	
	
	
	
	
	
	
	
	
	//This function scans the module code/framework of a plugin, looking for simple cases of phrases being used.
	//It then creates a tab with plugin settings that serve as overrides for these.
	public static function setupOverridesForPhrasesInFrameworks($hideExistingFields, &$box, $valuesInDB = [], $moduleClassName = null, $moduleClassNameForPhrases = null, $mode = null, $framework = null) {
		
		$fields = &$box['tabs']['phrases.framework']['fields'];
		
		//Disable and hide any previous fields, so they're not shown and don't get saved in the plugin settings table
		if ($hideExistingFields && !empty($fields)) {
			foreach ($fields as &$field) {
				if (is_array($field) && !empty($field) && !ze::in($field['type'] ?? '', 'button', 'submit')) {
					$field['hidden'] = true;
				}
			}
		}
		
		$mode = $box['key']['mode'] ?? $mode;
		$moduleClassName = $box['key']['moduleClassName'] ?? $moduleClassName;
		$moduleClassNameForPhrases = $box['key']['moduleClassNameForPhrases'] ?? $moduleClassNameForPhrases;
		$phrases = [];
		$pInCode = false;
		$pInTwig = false;
		
		//Get the contents of the framework file
		if (($framework = $box['key']['framework'] ?? $framework)
		 && ($frameworkFile = ze\plugin::frameworkPath($framework, $moduleClassName))
		 && ($twig = file_get_contents(CMS_ROOT. $frameworkFile))) {
		
			//If there's a sub-framework for this mode, get the contents of that as well
			if ($mode
			 && file_exists($modeDir = CMS_ROOT. dirname($frameworkFile). '/modes/'. $mode. '.twig.html')) {
				$twig .= file_get_contents($modeDir);
			}
		
		
			//Use a regular expression to look for any simple cases of phrases being used.
			$split = preg_split('@\{\{\s*([\'"])(.*?)\1\s*\|\s*trans\s*\}\}@', $twig, -1,  PREG_SPLIT_DELIM_CAPTURE);
			$count = count($split);

			for ($i = 2; $i < $count; $i += 3) {
				$code = stripslashes($split[$i]);
				$phrases[$code] = $code;
				$pInTwig = true;
			}
		}
		
		
		
		$limit = 5;
		do {
			$nextMode = false;
			//Attempt to get phrases from the PHP code as well.
			//If this is a modal plugin, look for the mode in the classes subdirectory, otherwise try looking at the module_code.php.
			//Note that I am not handling modules/classes that extend other modules/classes right now.
			if ($usesClassesDir = $mode
			 && ($modeDir = ze::moduleDir($moduleClassName, 'classes/visitor/'. $mode. '.php', true))
			 && (file_exists($modeDir = CMS_ROOT. $modeDir))) {
				$php = file_get_contents($modeDir);
			} else {
				$php = file_get_contents(CMS_ROOT. ze::moduleDir($moduleClassName, 'module_code.php'));
			}
		
			if ($php) {
				//Use token_get_all() to parse the file.
				//This is a lot more reliable than trying to use a regular expression.
				$tokens = token_get_all($php);
			
				foreach ($tokens as &$token) {
					if (!is_string($token)) {
						$token = $token[1];
					}
				}
				unset($token);
			
				//Remove any whitespaces
				$tokens = array_values(array_filter(array_map('trim', $tokens)));
			
			
				//Look through the tokens we got, looking for calls to $this->phrase()
				$mi = count($tokens) - 4;
			
				for ($i = 0; $i < $mi; ++$i) {
	
					if ($tokens[$i    ] == '$this'
					 && $tokens[$i + 1] == '->'
					 && $tokens[$i + 2] == 'phrase'
					 && $tokens[$i + 3] == '(') {
						$phrase = $tokens[$i + 4];
					
						//Check that the phrase code is a string.
						//(We won't try to handle the case where it's a variable.)
						if ($phrase[0] == "'"
						 || $phrase[0] == '"') {
							$code = stripslashes(substr($phrase, 1, -1));
							$phrases[$code] = $code;
							$pInCode = true;
						}
					}
				
					//Watch out for plugin modes that extend other plugins
					if ($usesClassesDir
					 && $tokens[$i    ] == 'class'
					 && $tokens[$i + 1] == $moduleClassName. '__visitor__'. $mode
					 && $tokens[$i + 2] == 'extends'
					 && ($extendedMode = ze\ring::chopPrefix($moduleClassName. '__visitor__', $tokens[$i + 3]))) {
						$nextMode = $extendedMode;
					}
				}
			}
		} while (--$limit > 0 && ($mode = $nextMode));

		
		
		
		
		if ($pInCode) {
			if ($pInTwig) {
				$box['tabs']['phrases.framework']['label'] = ze\admin::phrase('Phrases');
			} else {
				$box['tabs']['phrases.framework']['label'] = ze\admin::phrase('Phrases (PHP code)');
			}
		
		} else {
			if ($pInTwig) {
				$box['tabs']['phrases.framework']['label'] = ze\admin::phrase('Phrases (framework)');
			} else {
				$box['tabs']['phrases.framework']['hidden'] = true;
				return;
			}
		}
		$box['tabs']['phrases.framework']['hidden'] = false;
		
		
		
		
		
		
		
		$ord = 1000;
		
		$html = '
			<table class="zfab_customise_phrases cols_2"><tr>
				<th>Original Phrase</th>
				<th>Customised Phrase</th>
			</tr>';
		
		$fields['phrase_table_start'] = [
			'ord' => 100,
			'snippet' => [
				'html' => $html
			]
		];
		
		
		foreach ($phrases as $code => &$defaultText) {
			if ($code[0] == '_') {
				$defaultText = ze\sql::fetchValue("
					SELECT local_text
					FROM ". DB_PREFIX. "visitor_phrases
					WHERE `code` = '". ze\escape::sql($code). "'
					  AND language_id = '". ze\escape::sql(ze::$defaultLang). "'
					  AND module_class_name = '". ze\escape::sql($moduleClassNameForPhrases). "'
				") ?: $defaultText;
			}
		}
		unset($defaultText);
		asort($phrases);	
		
		foreach ($phrases as $code => $defaultText) {
			$ppath = 'phrase.framework.'. $code;
			
			if (!isset($fields[$ppath])) {
				
				$pre_field_html = '
					<tr><td>
						'. htmlspecialchars($defaultText);
			
				if ($code[0] == '_') {
					$pre_field_html .= '
						<br/>
						<span>(<span>'. htmlspecialchars($code). '</span>)</span>';
				}
			
				$pre_field_html .= '
					</td><td>';
				
				$fields[$ppath] = [
					'ord' => ++$ord,
					'same_row' => true,
					'pre_field_html' => $pre_field_html,
					'type' => strpos(trim($defaultText), "\n") === false? 'text' : 'textarea',
					'post_field_html' => '</td></tr>'
				];
		
				if (isset($valuesInDB[$ppath])) {
					$fields[$ppath]['value'] = $valuesInDB[$ppath];
				} else {
					$fields[$ppath]['value'] = $defaultText;
				}
			
			} else {
				$fields[$ppath]['hidden'] = false;
			}
			
			$fields[$ppath]['ord'] = ++$ord;
			$fields[$ppath]['plugin_setting'] = [
				'name' => $ppath,
				'value' => $defaultText,
				'dont_save_default_value' => true,
				'save_empty_value_when_hidden' => false
			];
		}
	
		$fields['phrase_table_end'] = [
			'ord' => 999999,
			'same_row' => true,
			'snippet' => [
				'html' => '
					</table>'
			]
		];
	
		if (\ze\row::exists('languages', ['translate_phrases' => 1])) {
			$mrg = [
				'def_lang_name' => htmlspecialchars(\ze\lang::name(\ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(\ze\link::absolute(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			];
		
			$fields['phrase_table_end']['show_phrase_icon'] = true;
			$fields['phrase_table_end']['snippet']['html'] .= '
				<br/>
				<span>'.
				\ze\admin::phrase('<a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg).
				'</span>';
		}
	}
	
}
