<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

$error = false;

if (!($input = trim($input))) {
	$error = ze\admin::phrase('The file is empty.');
	return false;
}

if ($isXML) {
	$xml = $input;

} else {


	//Attempt to convert html title to xml
	if (($pos1 = stripos($input, '<title>'))
	 && ($pos2 = stripos($input, '</title>'))
	 && ($pos1 < $pos2)) {
		$input =
			substr($input, 0, $pos1 + 7).
			zenario_pro_features::HTMLToXML(substr($input, $pos1 + 7, $pos2 - $pos1 - 7)).
			substr($input, $pos2);
	}
	
	
	$xml = '<?xml version="1.0" encoding="UTF-8"?>';
	$ndLevels = [];
	$input = preg_split('@<div\s+id=([\'"])zenario:(\w+)([,;]?)(.*?)\1([^>]*?)/?>@s', $input, -1,  PREG_SPLIT_DELIM_CAPTURE);
	
	$levels = [];
	foreach ($input as $i => &$thing) {
		switch ($i % 6) {
			case 2:
				$ndLevels[ceil($i/6)] = $thing == 'egg' || $thing == 'setting' || $thing == 'slide';
		}
	}
	
	foreach ($input as $i => &$thing) {
		if ($i == 0) {
			$xml .= $thing;
		
		} else {
			$lastThingWasSecondLevel = ze\ray::value($ndLevels, ceil($i/6) - 1);
			$thisThingIsSecondLevel = ze\ray::value($ndLevels, ceil($i/6));
			$nextThingWillBeSecondLevel = ze\ray::value($ndLevels, ceil($i/6) + 1);
			
			switch ($i % 6) {
				case 1:
					//Opening/closing quotes
					break;
					
				case 2:
					//The first attribute value will be the name of this thing
					$type = $thing;
					break;
					
				case 3:
					//The seperator being used for the end of attribute values
					//This was a semi-column initially during development, but later changed to a comma due to several bugs
					//caused by overlapping with the semi-column in HTML escaping.
					//Note the seperator for the start of the values is always a colon.
					$sep = $thing;
					break;
					
				case 4:
					//The rest of the attributes. Break them up between name and value pairs using the two seperators mentioned above,
					//and then write them as XML tags
					$isHTML = true;
					if ($lastThingWasSecondLevel && !$thisThingIsSecondLevel) {
						$xml .= '</plugin>';
					}
					
					if ($thing && strpos($thing, ':') === false && strpos($thing, ':') === false) {
						if (!$thing = zenario_pro_features::decode($thing)) {
							$error = ze\admin::phrase('The file cannot be imported. Either the file was exported from a different site, or the encoded settings have been corrupted.');
							return false;
						}
					}
					
					$xml .= '<'. $type;
					foreach (explode($sep, $thing) as $atts) {
						$att = explode(':', $atts, 2);
						if (!empty($att[0])) {
							$xml .= ' '. $att[0]. '="'. zenario_pro_features::HTMLToXML($att[1] ?? false). '"';
							
							if ($att[0] == 'format' && (($att[1] ?? false) == 'text' || ($att[1] ?? false) == 'translatable_text')) {
								$isHTML = false;
							}
						}
					}
					break;
					
				case 5:
					//Attempt to convert html attributes to xml attributes
					$jnput = preg_split('@([\'"])(.*?)\1@s', $thing, -1,  PREG_SPLIT_DELIM_CAPTURE);
					foreach ($jnput as $j => &$thjng) {
						switch ($j % 3) {
							case 2:
								$xml .= '"'. zenario_pro_features::HTMLToXML($thjng). '"';
								break;
							case 0:
								$xml .= $thjng;
								break;
						}
					}
					
					$xml .= '>';
					
					break;
				
				case 0:
					$thing = trim($thing);
					
					if (substr($thing, -7) == '</html>') {
						foreach (explode('</div>', $thing, $thisThingIsSecondLevel? -2 : -1) as $j => $snippet) {
							if ($j) {
								$xml .= ze\escape::xml('</div>');
							}
							
							if ($isHTML) {
								$xml .= ze\escape::xml($snippet);
							} else {
								$xml .= zenario_pro_features::HTMLToXML($snippet);
							}
						}
						
						if ($thisThingIsSecondLevel) {
							$xml .= '</'. $type. '></plugin></body></html>';
						} else {
							$xml .= '</'. $type. '></body></html>';
						}
						
					} else {
						$divs = 1;
						if (!$thisThingIsSecondLevel && $nextThingWillBeSecondLevel) {
							$divs = 0;
						} elseif ($thisThingIsSecondLevel && !$nextThingWillBeSecondLevel) {
							$divs = 2;
						}
						
						for ($j = 0; $j < $divs; ++$j) {
							if (substr($thing, -6) == '</div>') {
								$thing = trim(substr($thing, 0, -6));
							}
						}
						
						if ($isHTML) {
							$xml .= ze\escape::xml($thing);
						} else {
							$xml .= zenario_pro_features::HTMLToXML($thing);
						}
						
						if ($divs > 0) {
							$xml .= '</'. $type. '>';
						}
					}
					
					break;
			}
		}
	}
	
	if (!($xml = strip_tags($xml, '<html><target><title><description><keywords><summary><menu><menu_desc><template><plugin><setting><slide><egg>'))) {
		$error = ze\admin::phrase('The file format has been corrupted and the file could not be read.');
		return false;
	}
}


//Don't send error emails if someone is trying to import a file from a different site
//(meaning site IDs don't match)
ze::ignoreErrors();
	$xml = ze\deprecated::SimpleXMLString($xml);
ze::noteErrors();


if (!$xml) {
	$error = ze\admin::phrase('The file could not be read. Either it comes from a different site, or the file format has been corrupted.');
	return false;

} else {
	
	//Work out which Content Item this is for
	if ($xml->target) {
		$targetCID = (int) $xml->target->attributes()->cID;
		$targetCType = (string) $xml->target->attributes()->cType;
		
		if (!$cID) {
			$cID = $targetCID;
			$cType = $targetCType;
		}
	}
	
	//Check which Content Item we are updating, and stop if we can't find the target
	if (!$cID || !$cType || !ze\row::exists('content_items', ['id' => $cID, 'type' => $cType])) {
		$error = ze\admin::phrase('The target Content Item could not be found.');
		return false;
	
	} elseif (!ze\priv::check(false, $cID, $cType)) {
		$error = ze\admin::phrase("This content item is locked by another administrator, or you don't have the permissions to modify it.");
		return false;
	
	//If we're only checking if the file is valid, stop here
	} elseif ($onlyValidate) {
		return true;
	}
	
	$cVersion = false;
	if (!ze\content::isDraft($cID, $cType)) {
		ze\contentAdm::createDraft($cID, $cID, $cType, $cVersion);
	} else {
		$cVersion = ze\content::latestVersion($cID, $cType);
	}
	
	$content = ze\row::get('content_items', true, ['id' => $cID, 'type' => $cType]);

	
	//Try to save the names of Menu Nodes from the exports
	//Firstly, check which Menu Nodes have been created for this Equivalence, and in which section
	$existingMenuItems = [];
	$menuNodes = ze\row::query('menu_nodes', ['id', 'section_id'], ['target_loc' => 'int', 'equiv_id' => $content['equiv_id'], 'content_type' => $cType]);
	while ($menuNode = ze\sql::fetchAssoc($menuNodes)) {
		//Convert section_id to a string
		$menuNode['section_id'] = ze\menu::sectionName($menuNode['section_id']);
		
		if (!isset($existingMenuItems[$menuNode['section_id']])) {
			$existingMenuItems[$menuNode['section_id']] = [];
		}
		
		$existingMenuItems[$menuNode['section_id']][$menuNode['id']] = $menuNode['id'];
	}
	
	//Secondly, get the details of Menu Nodes that currently exist for this Equivalence
	$menuItemsInImport = [];
	if ($xml->menu) {
		foreach ($xml->menu as $menu) {
			$sectionId = (string) $menu->attributes()->section_id;
			$mID = (string) $menu->attributes()->id;
			
			if (!isset($menuItemsInImport[$sectionId])) {
				$menuItemsInImport[$sectionId] = [];
			}
			
			$menuItemsInImport[$sectionId][$mID] = [];
			$menuItemsInImport[$sectionId][$mID]['name'] = zenario_pro_features::getValue($menu);
		}
	}
	
	if ($xml->menu_desc) {
		foreach ($xml->menu_desc as $menu) {
			$sectionId = (string) $menu->attributes()->section_id;
			$mID = (string) $menu->attributes()->id;
			
			if (isset($menuItemsInImport[$sectionId][$mID])) {
				$menuItemsInImport[$sectionId][$mID]['descriptive_text'] = zenario_pro_features::getValue($menu);
			}
		}
	}
	
	//Finally, loop through each and try to match them up
	foreach ($menuItemsInImport as $sectionId => $menus) {
		foreach ($menus as $mID => $details) {
			if (isset($existingMenuItems[$sectionId])) {
				
				//Try to match by Menu Id
				if (isset($existingMenuItems[$sectionId][$mID])) {
					ze\menuAdm::saveText($mID, $content['language_id'], $details);
				
				//If that fails, check how many Menu Nodes are in this section.
				//If there's only one in both the import and the export, then we can match that way
				} elseif (count($menus) == 1 && count($existingMenuItems[$sectionId]) == 1) {
					foreach ($existingMenuItems[$sectionId] as $replaceMID) {
						ze\menuAdm::saveText($replaceMID, $content['language_id'], $details);
						break;
					}
				}
			}
		}
	}
	
	
	
	//Update metadata from the import file
	$version = [];
	$version['title'] = zenario_pro_features::getValue($xml->title);
	$version['description'] = zenario_pro_features::getValue($xml->description);
	$version['keywords'] = zenario_pro_features::getValue($xml->keywords);
	$version['content_summary'] = zenario_pro_features::fixHTML(zenario_pro_features::getValue($xml->summary));
	
	//Try to set/change the template of the Content Item to one that best matches the template mentioned in the import file
	if ($xml->template) {
		$sql = "
			SELECT layout_id
			FROM ". DB_PREFIX. "layouts
			WHERE content_type = '". ze\escape::sql($cType). "'
			ORDER BY
				name = '". ze\escape::sql($xml->template->attributes()->name). "' DESC,
				layout_id ASC
			LIMIT 1";
		
		if (($result = ze\sql::select($sql)) && ($row = ze\sql::fetchAssoc($result))) {
			$version['layout_id'] = $row['layout_id'];
		}
	}
	
	ze\contentAdm::updateVersion($cID, $cType, $cVersion, $version);
	
	
	//Get information on the template we're using
	if (isset($version['layout_id'])) {
		$template = ze\row::get('layouts', ['name', 'layout_id'], $version['layout_id']);
	} else {
		$template = ze\row::get('layouts', ['layout_id', 'name'], ze\content::layoutId($cID, $cType, $cVersion));
	}
	
	//Loop through the slots on the template, seeing what Modules are placed where
	$slotContents = [];
	ze\plugin::slotContents(
		$slotContents,
		$cID, $cType, $cVersion,
		$layoutId = false,
		$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
		$runPlugins = false);
	
	
	$slotsOnTemplate = zenario_pro_features::getSlotsOnTemplate($template['layout_id']);
	
	$pluginsToRemoveInTemplate = [];
	foreach ($slotsOnTemplate as $slotName) {
		if (!empty($slotContents[$slotName]['content_id'])
		 && !empty($slotContents[$slotName]['instance_id'])
		 && ($instance = ze\plugin::details($slotContents[$slotName]['instance_id']))) {
			$className = $instance['class_name'];
			
			$pluginsToRemoveInTemplate[$slotName] = $className;
		}
	}
	
	
	//Loop through the import, seeing what Modules are placed in what order
	$pluginsInImport = [];
	$pluginsToAddFromImport = [];
	$matchesImportToTemplate = [];
	$matchesTemplateToImport = [];
	if ($xml->plugin) {
		foreach ($xml->plugin as $plugin) {
			if (($className = (string) $plugin->attributes()->{'class'})
			 && ($slotName = (string) $plugin->attributes()->slot)) {
				$pluginsInImport[$slotName] = $className;
				
				//Does this slot match with what's in the Layout?
				if (isset($pluginsToRemoveInTemplate[$slotName]) && $pluginsToRemoveInTemplate[$slotName] == $className) {
					//If so, note down this match, and remove all other mention of the slot
					//from the arrays so we don't try to move it somewhere else
					$matchesImportToTemplate[$slotName] = $slotName;
					$matchesTemplateToImport[$slotName] = $slotName;
					
					unset($pluginsToRemoveInTemplate[$slotName]);
				
				} else {
					//Otherwise, note down that we need to match things up
					$pluginsToAddFromImport[$slotName] = $className;
				}
			}
		}
	}
	
	//Try to handle the case where the same number of Plugins exist, but they are
	//just in different places, by adding them into slots as we see them
	$changes = true;
	while ($changes) {
		$changes = false;
		foreach ($pluginsToRemoveInTemplate as $tSlotName => $tClassName) {
			foreach ($pluginsToAddFromImport as $iSlotName => $iClassName) {
				if ($tClassName == $iClassName) {
					$matchesImportToTemplate[$iSlotName] = $tSlotName;
					$matchesTemplateToImport[$tSlotName] = $iSlotName;
					
					unset($pluginsToRemoveInTemplate[$tSlotName]);
					unset($pluginsToAddFromImport[$iSlotName]);
					
					$changes = true;
					continue 3;
				}
			}
		}
	}
	
	if (!empty($pluginsToAddFromImport)) {
		//So we can try and keep things in order, for each Plugin that we did place, work out where the last Plugin was placed
		$previousSlot = '';
		$previousSlots = [];
		foreach ($pluginsInImport as $slotName => $className) {
			if (isset($matchesImportToTemplate[$slotName])) {
				$previousSlot = $matchesImportToTemplate[$slotName];
			}
			$previousSlots[$slotName] = $previousSlot;
		}
		
		//Loop through any remaining Plugins in the import, and put them in the next empty slot after the slot that they were in the import
		foreach ($pluginsToAddFromImport as $iSlotName => $className) {
			$passedSlot = false;
			for ($i = 0; $i < 2; ++$i) {
				foreach ($slotsOnTemplate as $tSlotName) {
					
					if ($passedSlot) {
						//Add this Plugin to the next empty slot alphabetically after where it was in the import
						//(Note that a slot with a Wireframe Plugin currently in it, but that was not mentioned in the import,
						// is considered empty for this purpose.)
						if (!isset($matchesTemplateToImport[$tSlotName])
						 && (isset($pluginsToRemoveInTemplate[$tSlotName]) || empty($slotContents[$tSlotName]['instance_id']))
						) {
							$matchesImportToTemplate[$iSlotName] = $tSlotName;
							$matchesTemplateToImport[$tSlotName] = $iSlotName;
							unset($pluginsToRemoveInTemplate[$tSlotName]);
							continue 3;
						}
					
					} elseif ($tSlotName >= ($previousSlots[$iSlotName] ?? '')) {
						$passedSlot = true;
					}
				}
				$passedSlot = true;
			}
		}
	}
	
	
	//Remove any existing Plugins that are in the template but were not matched with anything in the import
	foreach ($pluginsToRemoveInTemplate as $slotName => $className) {
		if (isset($slotsOnTemplate[$slotName]['level']) && $slotsOnTemplate[$slotName]['level'] > 1) {
			//If we are trying to remove a Plugin that was set at the Layout/Template Family level,
			//then we need to make the slot opaque at the item level
			ze\pluginAdm::updateItemSlot(0, $slotName, $cID, $cType, $cVersion);
		} else {
			//Otherwise we just need to make sure that the slot is empty at the item level
			ze\pluginAdm::updateItemSlot('', $slotName, $cID, $cType, $cVersion);
		}
	}
	
	
	//Add the Plugins in from the Import
	if ($xml->plugin) {
		foreach ($xml->plugin as $plugin) {
			if (($className = (string) $plugin->attributes()->{'class'})
			 && ($slotName = (string) $plugin->attributes()->slot)
			 && ($moduleId = ze\module::id($className))) {
				
				if ($slotName = $matchesImportToTemplate[$slotName] ?? false) {
					$images = [];
					$nestedPlugins = [];
					
					if ($instanceId = $slotContents[$slotName]['instance_id'] ?? false) {
						//Look for any Nested Tabs that match up with the Nested Tabs we are importing
						if ($plugin->slide) {
							foreach ($plugin->slide as $slide) {
								if ($id = 
									ze\row::get('nested_plugins', 'id',
										[
											'instance_id' => $instanceId,
											'is_slide' => 1,
											'slide_num' => (int) $slide->attributes()->slideNum])
								) {
									$nestedPlugins[] = $id;
								}
							}
						}
						
						//Look for any Nested Plugins that match up with the Nested Plugins we are importing
						if ($plugin->egg) {
							foreach ($plugin->egg as $egg) {
								if ($nestedModuleId = ze\module::id($egg->attributes()->class)) {
									if ($id = 
										ze\row::get('nested_plugins', 'id', 
											[
												'instance_id' => $instanceId,
												'is_slide' => 0,
												'slide_num' => (int) $egg->attributes()->slideNum,
												'ord' => (int) $egg->attributes()->ord,
												'module_id' => $nestedModuleId])
									) {
										$nestedPlugins[] = $id;
									}
								}
							}
						}
						
						//Remove any Nested Tabs/Plugins that are in the database, and don't match up to the ones we just found.
						//Otherwise we'll try to preserve their ids, in order to preserve any Swatch choices that are linked to them
						$sql = "
							DELETE FROM ". DB_PREFIX. "nested_plugins
							WHERE instance_id = ". (int) $instanceId;
						
						if (!empty($nestedPlugins)) {
							$sql .= "
							  AND id NOT IN (". implode(',', $nestedPlugins). ")";
						}
						ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
					
					
						//Check for any existing file/image-links for the current Plugin, and note down what they are for use later
						$result = ze\row::query('plugin_settings', ['name', 'egg_id', 'value'], ['instance_id' => $instanceId, 'foreign_key_to' => 'file']);
						while ($row = ze\sql::fetchAssoc($result)) {
							if (!isset($images[$row['egg_id']])) {
								$images[$row['egg_id']] = [];
							}
							
							$images[$row['egg_id']][$row['name']] = $row['value'];
						}
					}
					
					
					//Place the Plugin on the slot, and get its Wireframe Instance Id
					//If is is the same type of Plugin that was there before, this should not change
					ze\pluginAdm::updateItemSlot(0, $slotName, $cID, $cType, $cVersion, $moduleId, $copySwatchUp = true);
					$instanceId = ze\plugin::vcId($cID, $cType, $cVersion, $slotName, $moduleId);
					
					//Remove any settings
					$key = ['instance_id' => $instanceId];
					ze\row::delete('plugin_instance_store', $key);
					ze\row::delete('plugin_settings', $key);
					
					//Add the new settings
					ze\row::set('plugin_instances', ['framework' => (string) $plugin->attributes()->framework], $instanceId);
					
					//Import/update the tabs
					if ($plugin->slide) {
						foreach ($plugin->slide as $slide) {
							ze\row::set(
								'nested_plugins',
								[
									'ord' => 0,
									'module_id' => 0,
									'name_or_title' => zenario_pro_features::getValue($slide)],
								[
									'instance_id' => $instanceId,
									'is_slide' => 1,
									'slide_num' => (int) $slide->attributes()->slideNum]);
						}
					}
					
					//Import/update the Nested Plugins
					$nestedPlugins = [];
					if ($plugin->egg) {
						foreach ($plugin->egg as $egg) {
							if ($nestedModuleId = ze\module::id($egg->attributes()->class)) {
								$slideNum = (int) $egg->attributes()->slideNum;
								$ord = (int) $egg->attributes()->ord;
								if (!isset($nestedPlugins[$slideNum])) {
									$nestedPlugins[$slideNum] = [];
								}
								
								$nestedPlugins[$slideNum][$ord] = ze\row::set(
									'nested_plugins',
									[
										'framework' => $egg->attributes()->framework,
										'name_or_title' => $egg->attributes()->name_or_title],
									[
										'instance_id' => $instanceId,
										'is_slide' => 0,
										'slide_num' => $slideNum,
										'ord' => $ord,
										'module_id' => $nestedModuleId]);
							}
						}
					}
					
					if ($plugin->setting) {
						foreach ($plugin->setting as $setting) {
							$slideNum = (int) $setting->attributes()->slideNum;
							$ord = (int) $setting->attributes()->ord;
							
							if (!$slideNum && !$ord) {
								$key['egg_id'] = 0;
							
							} elseif (!empty($nestedPlugins[$slideNum][$ord])) {
								$key['egg_id'] = $nestedPlugins[$slideNum][$ord];
							
							} else {
								continue;
							}
							
							$key['name'] = (string) $setting->attributes()->name;
							
							$value = [];
							$value['value'] = zenario_pro_features::getValue($setting);
							$value['is_content'] = (string) $setting->attributes()->is_content;
							$value['format'] = (string) $setting->attributes()->format;
							$value['foreign_key_to'] = NULL;
							$value['foreign_key_id'] = 0;
							$value['foreign_key_char'] = '';
							
							if (ze::in($value['format'], 'html', 'translatable_html')) {
								$value['value'] = zenario_pro_features::fixHTML($value['value']);
							}
							
							switch ($keyTo = (string) $setting->attributes()->foreign_key_to) {
								case 'content':
									$linkedCID = $linkedCType = false;
									if ((!ze\content::getCIDAndCTypeFromTagId($linkedCID, $linkedCType, $value['value']))
									 || (!$status = ze\content::status($linkedCID, $linkedCType))
									 || ($status == 'deleted' || $status == 'trashed')) {
										continue 2;
									}
									
									$value['foreign_key_to'] = $keyTo;
									$value['foreign_key_id'] = $linkedCID;
									$value['foreign_key_char'] = $linkedCType;
									break;
								
								case 'email_template':
									if (!ze\row::exists('email_templates', ['code' => $value['value']])) {
										continue 2;
									}
									
									$value['foreign_key_to'] = $keyTo;
									$value['foreign_key_id'] = 0;
									$value['foreign_key_char'] = $value['value'];
									break;
								
								case 'categories':
									foreach (explode(',', $value['value']) as $cat) {
										if (!ze\row::exists('categories', $cat)) {
											continue 3;
										}
									}
									
									$value['foreign_key_to'] = $keyTo;
									break;
								
								case 'category':
									if (!ze\row::exists('categories', $value['value'])) {
										continue 2;
									}
									
									$value['foreign_key_to'] = $keyTo;
									$value['foreign_key_id'] = $value['value'];
									break;
									
								case 'file':
									//Check the existing files to see if one was linked in this position before the export.
									//If so, don't change it
									if (!empty($images[$key['egg_id']][$key['name']])) {
										$value['value'] = $images[$key['egg_id']][$key['name']];
									
									} elseif (!ze\row::exists('files', $value['value'])) {
										continue 2;
									}
									
									$value['foreign_key_to'] = $keyTo;
									$value['foreign_key_id'] = $value['value'];
									break;
							}
							
							
							ze\row::set('plugin_settings', $value, $key);
						}
					}
				}
			}
		}
	}
	
	
	ze\contentAdm::syncInlineFileContentLink($cID, $cType, $cVersion);
	
	return true;
}
