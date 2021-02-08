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

//If a language has not yet been enabled, then we cannot do anything.
if (!\ze::$defaultLang
 || !\ze\row::exists('languages', [])) {
	return;
}

//Have we set up special pages before or is this the first time..?
//Check the special pages table, if there are already linked pages then this isn't the first time
$firstSetup = !\ze\row::exists('special_pages', ['equiv_id' => ['!' => 0]]);


//Look for any special pages
$sql = "
	SELECT *
	FROM ". DB_PREFIX. "special_pages
	ORDER BY
		module_class_name = 'zenario_common_features' DESC,
		page_type = 'zenario_home' DESC,
		module_class_name,
		page_type";


if ($resultSp = \ze\sql::select($sql)) {
	while ($sp = \ze\sql::fetchAssoc($resultSp)) {
		
		if (!$firstSetup
		 && $sp['logic'] == 'create_in_default_language_on_install') {
			//Only create dummy pages such as the "Second Page" on the first install, don't try to add or maintain their presence later
			continue;
		}
		
		$thisLang = false;
		$equivs = [];
		$langsToCreate = [];
		
		if (!$sp['equiv_id']) {
			//If the special page hasn't been created yet, make sure it is created.
			$langsToCreate[\ze::$defaultLang] = true;
		
		} else {
			$thisLang = \ze\content::langId($sp['equiv_id'], $sp['content_type']);
			$equivs = \ze\content::equivalences($sp['equiv_id'], $sp['content_type']);
		}
		
		//To ensure that the special page exists for the default language we may need to create one
		if ($thisLang && $thisLang != \ze::$defaultLang && !isset($equivs[\ze::$defaultLang])) {
			$langsToCreate[\ze::$defaultLang] = true;
		}
		
		if (!empty($langsToCreate)) {
			//Attempt to get details on the Plugin associated with this special page
			if ($sp['module_class_name'] && ($module = \ze\module::details($sp['module_class_name'], 'class'))) {
				$desc = false;
				if (!\ze\moduleAdm::loadDescription($module['class_name'], $desc)) {
					continue;
				}
				
				//Look through the special_pages tags for this special page
				//(Though most Modules that have special pages only have one special page.)
				if (!empty($desc['special_pages']) && is_array($desc['special_pages'])) {
					foreach($desc['special_pages'] as $page) {
						if (!empty($page['page_type']) && $page['page_type'] == $sp['page_type']) {
							foreach ($langsToCreate as $langId => $dummy) {
								//Create a new page
								$cID = $cIDFrom = $cVersion = $cVersionFrom = false;
								$cType = 'html';
								
								\ze\contentAdm::createDraft($cID, $cIDFrom, $cType, $cVersion, $cVersionFrom, $langId);
							
								//Try to work out what layout it should have
								$layoutId = \ze\layoutAdm::defaultId('html');
						
								if (!empty($page['layout'])) {
									$sql = "
										SELECT layout_id
										FROM ". DB_PREFIX. "layouts
										WHERE name LIKE '%". \ze\escape::like($page['layout']). "%'
										LIMIT 1";
									$resultL = \ze\sql::select($sql);
									if ($layout = \ze\sql::fetchAssoc($resultL)) {
										$layoutId = $layout['layout_id'];
									}
								}
							
							
								//Try to add an alias (so long as the alias is not taken)
								if ($alias = $page['default_alias'] ?? false) {
									if (!is_array(\ze\contentAdm::validateAlias($alias))) {
										\ze\row::set('content_items', ['alias' => $alias], ['id' => $cID, 'type' => $cType]);
									} else {
										$alias = '';
									}
								} else {
									$alias = '';
								}
							
								\ze\row::set('content_item_versions',
									['title' => ($page['default_title'] ?? false), 'layout_id' => $layoutId],
									['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
							
								if (!$sp['equiv_id']) {
									$sp['equiv_id'] = $cID;
									$sp['content_type'] = $cType;
								} else {
									//For multilingal sites, make sure that translations of special pages are marked as translations
									$sp['equiv_id'] = \ze\contentAdm::recordEquivalence($sp['equiv_id'], $cID, $cType);
								}
							
								//Update the special pages table to record which page is the special page,
								//unless we are using the create_in_default_language_on_install logic in which case we won't make
								//the created page a special page
								if ($sp['logic'] != 'create_in_default_language_on_install') {
									\ze\row::update(
										'special_pages',
										[
											'equiv_id' => $sp['equiv_id'],
											'content_type' => $sp['content_type']],
										[
											'page_type' => $sp['page_type']]);
								}
							
								//We'll need to put a something on this page
								//Work out a free main slot to put a Plugin in
								$template = \ze\row::get('layouts', ['layout_id', 'file_base_name', 'family_name'], $layoutId);
								$slotName = \ze\layoutAdm::mainSlotByName($template['family_name'], $template['file_base_name']);
						
								//Check if this Plugin is Slotable, and if so attempt to put this Plugin on the page
								if ($module['is_pluggable']) {
									//Otherwise set a Reusable Instance there
									if (!$instanceId = \ze\row::get('plugin_instances', 'id', ['module_id' => $module['id'], 'content_id' => 0])) {
										//Create a new reusable instance if one does not already exist
										$errors = [];
										\ze\pluginAdm::create(
											$module['id'],
											$desc['default_instance_name'],
											$instanceId,
											$errors, $onlyValidate = false, $forceName = true);
									}
							
									\ze\pluginAdm::updateItemSlot($instanceId, $slotName, $cID, $cType, $cVersion, $module['id']);
						
								//Otherwise have the option to place a HTML Snippet in there
								} elseif (!empty($page['default_content']) && ($snippetId = \ze\row::get('modules', 'id', ['class_name' => 'zenario_wysiwyg_editor']))) {
							
									//Try to find an editor
									if ($editorSlot = \ze\contentAdm::mainSlot($cID, $cType, $cVersion)) {
										$instanceId = \ze\row::insert(
											'plugin_instances',
											[
												'module_id' => $snippetId,
												'content_id' => $cID,
												'content_type' => $cType,
												'content_version' => $cVersion,
												'slot_name' => $editorSlot]);
							
										\ze\row::insert(
											'plugin_settings',
											[
												'instance_id' => $instanceId,
												'name' => 'html',
												'value' => $page['default_content'],
												'is_content' => 'version_controlled_content',
												'format' => 'translatable_html']);
									}
								}
								
								//Insert Menu Nodes
								$redundancy = 'primary';
								if (!empty($page['menu_title']) && ($sectionId = \ze\menu::sectionId('Main'))) {
									
									if ($menu = \ze\menu::getFromContentItem($sp['equiv_id'], $sp['content_type'], false, 'Main', true)) {
										$menuId = $menu['id'];
									
									} else {
										$menuId = \ze\menuAdm::save([
											'section_id' => $sectionId,
											'redundancy' => $redundancy,
											'name' => ($page['menu_title'] ?? false),
											'rel_tag' => ($page['menu_rel_tag'] ?? false),
											'target_loc' => 'int',
											'content_id' => $cID,
											'content_type' => $cType,
											'hide_private_item' => 
												\ze\ring::engToBoolean($page['only_show_to_visitors_who_are_logged_in'] ?? false)?
													3
												: (
													\ze\ring::engToBoolean($page['only_show_to_visitors_who_are_logged_out'] ?? false)?
														2
													:	0)]);
									}
									\ze\menuAdm::saveText($menuId, $langId, ['name' => ($page['menu_title'] ?? false)]);
									
									$redundancy = 'secondary';
								}
								
								if (!empty($page['footer_menu_title']) && ($sectionId = \ze\menu::sectionId('Footer'))) {
									
									if ($menu = \ze\menu::getFromContentItem($sp['equiv_id'], $sp['content_type'], false, 'Footer', true)) {
										$menuId = $menu['id'];
									
									} else {
										$menuId = \ze\menuAdm::save([
											'section_id' => $sectionId,
											'redundancy' => $redundancy,
											'name' => ($page['footer_menu_title'] ?? false),
											'rel_tag' => ($page['menu_rel_tag'] ?? false),
											'target_loc' => 'int',
											'content_id' => $cID,
											'content_type' => $cType,
											'hide_private_item' => 
												\ze\ring::engToBoolean($page['only_show_to_visitors_who_are_logged_in'] ?? false)?
													3
												: (
													\ze\ring::engToBoolean($page['only_show_to_visitors_who_are_logged_out'] ?? false)?
														2
													:	0)]);
									}
									\ze\menuAdm::saveText($menuId, $langId, ['name' => ($page['footer_menu_title'] ?? false)]);
								}
							
								//Update the wordcount and other stats
								\ze\contentAdm::syncInlineFileContentLink($cID, $cType, $cVersion);
							
								//Publish the page straight away if requested
								if ($sp['publish']) {
									\ze\contentAdm::publishContent($cID, $cType);
								}
							}
						}
					}
				}
			}
		}
	}
}
