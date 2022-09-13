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

class zenario_common_features__admin_boxes__content extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Experimental feature that lets an admin edit multiple content items by
		//selecting multiple in Organizer, but then editing them one at a time.
		//If there are multiple ids in the id, enable openNextMode.
		if (!empty($box['key']['id'])
		 && false !== strpos($box['key']['id'], ',')) {
			$box['key']['openNextMode'] = true;
		}
		
		//When we are using openNextMode, put the first id we're editing $box['key']['id']
		//and any remaining ids into $box['key']['nextIds'].
		if ($box['key']['openNextMode']) {
			$ids = ze\ray::explodeAndTrim($box['key']['id']);
			$box['key']['id'] = array_shift($ids);
			
			if (!empty($ids)) {
				$box['key']['nextIds'] = implode(',', $ids);
			}
			
			//Clear some imcompatible features to avoid bugs
			$box['key']['cID'] =
			$box['key']['cType'] =
			$box['key']['cVersion'] =
			$box['key']['from_cID'] =
			$box['key']['from_cType'] =
			$box['key']['target_cType'] =
			$box['key']['source_cID'] =
			$box['key']['source_cVersion'] =
			$box['key']['translate'] =
			$box['key']['equivId'] =
			$box['key']['duplicate'] =
			$box['key']['duplicate_from_menu'] = '';
		}
		
		
		
		//Try to set an example URL format, for use in the SEO preview box
		$sql = "
			SELECT ci.id, ci.type, ci.equiv_id, ci.alias, ci.language_id
			FROM ". DB_PREFIX. "content_items AS ci
			LEFT JOIN ". DB_PREFIX. "special_pages AS sp
			   ON sp.equiv_id = ci.equiv_id
			  AND sp.content_type = ci.type
			WHERE `type` = 'html'
			AND sp.equiv_id IS NULL
			ORDER BY id
			LIMIT 1";
		
		if ($egContent = ze\sql::fetchAssoc($sql)) {
			$values['meta_data/url_format'] =
				ze\link::toItem($egContent['id'], $egContent['type'], false, '', $egContent['alias'],
					false, $forceAliasInAdminMode = true,
					$egContent['equiv_id'], $egContent['language_id'], false,
					$useHierarchicalURLsIfEnabled = false, $overrideAlias = '[[alias]]', $overrideLangId = '[[langId]]'
				);
		}


		
		//Include an option to create a Menu Node and/or Content Item as a new child of an existing menu Node
		if ($box['key']['id_is_menu_node_id'] || $box['key']['id_is_parent_menu_node_id']) {
	
			if ($box['key']['id'] && $box['key']['id_is_parent_menu_node_id']) {
				//Create a new Content Item/Menu Node under an existing one
				$box['key']['target_menu_parent'] = $box['key']['id'];
		
				$box['key']['target_menu_section'] = ze\row::get('menu_nodes', 'section_id', $box['key']['id']);
				$box['key']['cType'] = $box['key']['target_cType'];
	
			} elseif ($box['key']['id'] && $box['key']['id_is_menu_node_id']) {
				if ($box['key']['edit_linked_content_item'] && ($menuContentItem = ze\menu::getContentItem($box['key']['id']))) {
					//Edit an existing Content Item based on its Menu Node
					$box['key']['cID'] = $menuContentItem['equiv_id'];
					$box['key']['cType'] = $menuContentItem['content_type'];
					ze\content::langEquivalentItem($box['key']['cID'], $box['key']['cType'], ze::ifNull($box['key']['target_language_id'], ($_GET['languageId'] ?? false), ze::$defaultLang));
					$box['key']['source_cID'] = $box['key']['cID'];
			
					$box['key']['target_menu_section'] = ze\row::get('menu_nodes', 'section_id', $box['key']['id']);
				} else {
					//Create a new Content Item/Menu Node under an existing child one
					$box['key']['target_menu'] = $box['key']['id'] . '_' . $box['key']['target_menu_section'];
					
					$box['key']['target_menu_section'] = ze\row::get('menu_nodes', 'section_id', $box['key']['id']);
					$box['key']['cType'] = $box['key']['target_cType'];
				}

			} else {
				$box['key']['target_menu_section'] = ze::ifNull($box['key']['target_menu_section'], ($_REQUEST['sectionId'] ?? false), ($_REQUEST['refiner__section'] ?? false));
			}
			$box['key']['id'] = false;
		}

		if ($path == 'zenario_content') {
			//Include the option to duplicate a Content Item based on a MenuId
			if ($box['key']['duplicate_from_menu']) {
				//Handle the case where a language id is in the primary key
				if ($box['key']['id'] && !is_numeric($box['key']['id']) && ($_GET['refiner__menu_node_translations'] ?? false)) {
					$box['key']['target_language_id'] = $box['key']['id'];
					$box['key']['id'] = $_GET['refiner__menu_node_translations'] ?? false;
		
				} elseif (is_numeric($box['key']['id']) && ($_GET['refiner__language'] ?? false)) {
					$box['key']['target_language_id'] = $_GET['refiner__language'] ?? false;
				}
		
				if ($menuContentItem = ze\menu::getContentItem($box['key']['id'])) {
					$box['key']['source_cID'] = $menuContentItem['equiv_id'];
					$box['key']['cType'] = $menuContentItem['content_type'];
					$box['key']['id'] = false;
		
				} else {
					echo ze\admin::phrase('No content item was found for this menu node');
					exit;
				}
	
			//Include the option to duplicate to create a ghost in an Translation Chain,
			//and handle the case where a language id is in the primary key
			} else
			//Version for opening from the "translation chain" panel in Organizer:
			if (
				$box['key']['translate']
			 && ($_REQUEST['refinerName'] ?? false) == 'zenario_trans__chained_in_link'
			 && !ze\content::getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], $box['key']['id'])
			 && ze\content::getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], ($_REQUEST['refiner__zenario_trans__chained_in_link'] ?? false))
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['id'] = null;
			} else
			//Version for opening from the "translation chain" panel in the menu area in Organizer:
			if (
				$box['key']['translate']
			 && ($_REQUEST['refinerName'] ?? false) == 'zenario_trans__chained_in_link__from_menu_node'
			 && ($_REQUEST['equivId'] ?? false)
			 && ($_REQUEST['cType'] ?? false)
			 && ($_REQUEST['language'] ?? false)
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['source_cID'] = $_REQUEST['equivId'] ?? false;
				$box['key']['id'] = null;
			} else
			//Version for opening from the Admin Toolbar
			if (
				$box['key']['translate']
			 && ($_REQUEST['cID'] ?? false) && ($_REQUEST['cType'] ?? false)
			 && !ze\content::getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], $box['key']['id'])
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['id'] = null;
				$box['key']['source_cID'] = $_REQUEST['cID'] ?? false;
				$box['key']['cType'] = $_REQUEST['cType'] ?? false;
				$box['key']['cID'] = '';
			} else {
				$numEnabledLanguages = ze\lang::count();
				if ($numEnabledLanguages == 1) {
					$box['key']['target_language_id'] = ze::$defaultLang;
				}
			}

			if (isset($fields['meta_data/release_date'])) {
				//Work out the placeholder format.
				$format = ze::setting('organizer_date_format');
				$placeholder = '';
				switch ($format) {
					case 'd/m/y':
						$placeholder = 'd/mm/yy';
						break;
					case 'd/m/yy':
						$placeholder = 'd/mm/yyyy';
						break;
					case 'dd/mm/y':
						$placeholder = 'dd/mm/yy';
						break;
					case 'dd/mm/yy':
						$placeholder = 'dd/mm/yyyy';
						break;
					case 'dd.mm.yy':
						$placeholder = 'dd.mm.yyyy';
						break;
					case 'm/d/y':
						$placeholder = 'm/d/yy';
						break;
					case 'm/d/yy':
						$placeholder = 'm/d/yyyy';
						break;
					case 'mm/dd/y':
						$placeholder = 'mm/dd/yy';
						break;
					case 'mm/dd/yy':
						$placeholder = 'mm/dd/yyyy';
						break;
					case 'yy-mm-dd':
						$placeholder = 'yyyy-mm-dd';
						break;
					case 'd M y':
						$placeholder = 'd Mon yy';
						break;
					case 'd M yy':
						$placeholder = 'd Mon yyyy';
						break;
					case 'M d, yy':
						$placeholder = 'Mon d, yyyy';
						break;
				}

				$fields['meta_data/release_date']['placeholder'] = $placeholder;
			}
		}


		//If creating a new Content Item from the Content Items (and missing translations) in Language Panel,
		//or the Content Items in the language X Panel, don't allow the language to be changed
		if (($_GET['refinerName'] ?? false) == 'language'
		 || (isset($_GET['refiner__language_equivs']) && ($_GET['refiner__language'] ?? false))) {
			$box['key']['target_language_id'] = $_GET['refiner__language'] ?? false;
		}
		
		
		//Only allow the language to be changed when duplicating or translating.
		//Also only allow if there is more than 1 language enabled on the site.
		$lockLanguageId = false;
		if ($box['key']['target_language_id'] || $box['key']['duplicate'] || $box['key']['translate']) {
			$lockLanguageId = true;
		}

		//Populate the language select list
		ze\contentAdm::getLanguageSelectListOptions($fields['meta_data/language_id']);

		//Set up the primary key from the requests given
		if ($box['key']['id'] && !$box['key']['cID']) {
			ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);

		} elseif (!$box['key']['id'] && $box['key']['cID'] && $box['key']['cType']) {
			$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
		}

		if ($box['key']['cID'] && !$box['key']['cVersion']) {
			$box['key']['cVersion'] = ze\content::latestVersion($box['key']['cID'], $box['key']['cType']);
		}

		if ($box['key']['cID'] && !$box['key']['source_cID']) {
			$box['key']['source_cID'] = $box['key']['cID'];
			$box['key']['source_cVersion'] = $box['key']['cVersion'];

		} elseif ($box['key']['source_cID'] && !$box['key']['source_cVersion']) {
			$box['key']['source_cVersion'] = ze\content::latestVersion($box['key']['source_cID'], $box['key']['cType']);
		}

		//If we're duplicating a Content Item, check to see if it has a Menu Node as well
		if ($box['key']['duplicate'] || $box['key']['translate']) {
			$box['key']['cID'] = $box['key']['cVersion'] = false;
	
			if ($menu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType'])) {
				$box['key']['target_menu_parent'] = $menu['parent_id'];
				$box['key']['target_menu_section'] = $menu['section_id'];
			}
		}

		//Enforce a specific Content Type
		if ($_REQUEST['refiner__content_type'] ?? false) {
			$box['key']['target_cType'] = $_REQUEST['refiner__content_type'] ?? false;
		}
		
		if (!empty($box['key']['create_from_toolbar'])) {
			$fields['meta_data/language_id']['disabled'] = true;
		}
		
		//Set the from_cID if the source_cID is set
		if ($box['key']['source_cID']) {
			$box['key']['from_cID'] = $box['key']['source_cID'];
			$box['key']['from_cType'] = $box['key']['cType'];
		}

		$contentType = ze\row::get('content_types', true, $box['key']['cType'] ?: $box['key']['target_cType']);

		$content = $version = $status = $tag = false;
	
		//Specific Logic for Full Create
		//Try to load details on the source Content Item, if one is set
		if ($box['key']['source_cID']) {
			$content =
				ze\row::get(
					'content_items',
					['id', 'type', 'tag_id', 'language_id', 'equiv_id', 'alias', 'visitor_version', 'admin_version', 'status'],
					['id' => $box['key']['source_cID'], 'type' => $box['key']['cType']]);
			
			if (!$content) {
				echo ze\admin::phrase('Source content item not found');
				exit;
			}
		}

		$allowPinning = ze\row::get('content_types', 'allow_pinned_content', ['content_type_id' => $box['key']['cType']]);
		$fields['meta_data/pinned']['hidden'] = !$allowPinning;

		//Pinning
		if ($allowPinning) {
			$scheduledTaskManagerIsRunning = ze\module::inc('zenario_scheduled_task_manager');
			$masterSwitchIsOn = $scheduledTaskManagerIsRunning && zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false);
			$cronTabConfiguredCorrectly = $scheduledTaskManagerIsRunning && zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true);
			$jobUnpinContentIsEnabled = $scheduledTaskManagerIsRunning && zenario_scheduled_task_manager::checkScheduledTaskRunning('jobUnpinContent');
			//Scheduled Task Manager status
			if ($scheduledTaskManagerIsRunning) {
				if (!$masterSwitchIsOn || !$cronTabConfiguredCorrectly || !$jobUnpinContentIsEnabled) {
					$fields['meta_data/pinned_duration']['values']['fixed_duration']['disabled'] =
					$fields['meta_data/pinned_duration']['values']['fixed_date']['disabled'] =
					$fields['meta_data/unpin_date']['disabled'] =
					$fields['meta_data/pinned_fixed_duration_value']['disabled'] =
					$fields['meta_data/pinned_fixed_duration_unit']['disabled'] = true;
				}
			} else {
				//If STM is not running, then hide the timed unpinning options.
				//Please note: there is additional code below for existing content items
				//which checks if there already is a value selected despite STM not running.
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['hidden'] =
				$fields['meta_data/pinned_duration']['values']['fixed_date']['hidden'] =
				$fields['meta_data/unpin_date']['hidden'] =
				$fields['meta_data/pinned_fixed_duration_value']['hidden'] =
				$fields['meta_data/pinned_fixed_duration_unit']['hidden'] = true;
			}

			//Disable timed unpinning if needed
			if (!$scheduledTaskManagerIsRunning) {
				$fields['meta_data/pinned_duration']['values']['fixed_date']['side_note'] =
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['side_note'] = ze\admin::phrase(
					'Scheduled unpinning is not available. The Scheduled Task Manager is not installed.'
				);
			} elseif (!$masterSwitchIsOn) {
				$fields['meta_data/pinned_duration']['values']['fixed_date']['side_note'] =
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['side_note'] = ze\admin::phrase(
					'Scheduled unpinning is not available. The Scheduled Task Manager is enabled, but the master switch is Off.'
				);
			} elseif (!$cronTabConfiguredCorrectly) {
				$fields['meta_data/pinned_duration']['values']['fixed_date']['side_note'] =
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['side_note'] = ze\admin::phrase(
					'Scheduled unpinning is not available. The Scheduled Task Manager is installed and the master switch is On, but the crontab is not set up correctly.'
				);
			} elseif (!$jobUnpinContentIsEnabled) {
				$fields['meta_data/pinned_duration']['values']['fixed_date']['side_note'] =
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['side_note'] = ze\admin::phrase(
					'Scheduled unpinning is not available. The Scheduled Task Manager is set up correctly, but the scheduled unpinning task (jobUnpinContent) is not enabled.'
				);
			}

			if (!$scheduledTaskManagerIsRunning || !$masterSwitchIsOn || !$cronTabConfiguredCorrectly || !$jobUnpinContentIsEnabled) {
				$fields['meta_data/pinned_duration']['values']['fixed_date']['disabled'] =
				$fields['meta_data/pinned_duration']['values']['fixed_duration']['disabled'] = true;
			}
		}

		if ($content) {
			//On the language selector, disable languages for which translations already exist,
			//and mark the currently selected language.
			if (!ze\content::isSpecialPage($box['key']['cID'], $box['key']['cType'])) {
				if ($content['language_id'] && $fields['meta_data/language_id']['values'][$content['language_id']]) {
					$fields['meta_data/language_id']['values'][$content['language_id']]['label'] .= ' (' . ze\admin::phrase('selected') . ')';
				}

				$contentEquivId = ze\content::equivId($content['id'], $content['type']);
				$otherTranslationsResult =
					ze\row::query(
						'content_items',
						'language_id',
						['equiv_id' => (int) $contentEquivId, 'type' => $box['key']['cType'], 'id' => ['!' => $content['id']]]);
				$otherTranslations = ze\sql::fetchValues($otherTranslationsResult);
				if (!empty($otherTranslations)) {
					foreach ($otherTranslations as $otherTranslation) {
						if ($fields['meta_data/language_id']['values'][$otherTranslation]) {
							$fields['meta_data/language_id']['values'][$otherTranslation]['disabled'] = true;
							$fields['meta_data/language_id']['values'][$otherTranslation]['label'] .= ' (' . ze\admin::phrase('translation already exists') . ')';
						}
					}
				}
			}

			if ($box['key']['duplicate'] || $box['key']['translate']) {
				//Don't allow the layout to be changed when duplicating
				$fields['meta_data/layout_id']['readonly'] = true;
				
				if ($box['key']['translate']) {
					$values['meta_data/alias'] = $content['alias'];
					$box['tabs']['categories']['hidden'] = true;
					$box['tabs']['privacy']['hidden'] = true;
		
					if (!ze::setting('translations_different_aliases')) {
						$fields['meta_data/alias']['readonly'] = true;
						$box['tabs']['meta_data']['fields']['alias']['note_below'] =
							ze\admin::phrase('All content items in a translation chain have the same alias (see site settings).');
					}
				}
				
				
				//Check to see if there are any library plugins on this page set at the item level
				$slots = [];
				ze\plugin::slotContents($slots, $box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'],
					$layoutId = false,
					$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
					$runPlugins = false
				);
				
				$numPlugins = 0;
				foreach ($slots as $slotName => $slot) {
					if (!empty($slot['instance_id'])
					 && empty($slot['instance_id']['content_id'])
					 && $slot['level'] == 1) {
						
						$instance = ze\plugin::details($slot['instance_id']);
						
						++$numPlugins;
						$suffix = '__'. $numPlugins;
						$values['plugins/slotname'. $suffix] = $slotName;
						$values['plugins/module'. $suffix] = ze\module::displayName($slot['module_id']);
						$values['plugins/instance_id'. $suffix] = $slot['instance_id'];
						$values['plugins/plugin'. $suffix] = $instance['instance_name'] . ' (' . $instance['name'] . ')';
						$values['plugins/new_name'. $suffix] =  ze\admin::phrase('[[name]] (copy)', $instance);
						
						$className = ze\module::className($slot['module_id']);
						
						switch ($className) {
							case 'zenario_plugin_nest':
								$fields['plugins/action'. $suffix]['empty_value'] = ze\admin::phrase(' - Select what to do with this nest - ');
								$fields['plugins/action'. $suffix]['values']['original']['label'] = ze\admin::phrase('Use same nest');
								break;
								
							case 'zenario_slideshow':
							case 'zenario_slideshow_simple':
								$fields['plugins/action'. $suffix]['empty_value'] = ze\admin::phrase(' - Select what to do with this slideshow - ');
								$fields['plugins/action'. $suffix]['values']['original']['label'] = ze\admin::phrase('Use same slideshow');
								break;
							
							default:
								$fields['plugins/action'. $suffix]['empty_value'] = ze\admin::phrase(' - Select what to do with this plugin - ');
								$fields['plugins/action'. $suffix]['values']['original']['label'] = ze\admin::phrase('Use same plugin');
								break;
						}
						
						$fields['plugins/action'. $suffix]['values']['duplicate']['label'] = ze\admin::phrase('Make a copy');
						$fields['plugins/action'. $suffix]['values']['empty']['label'] = ze\admin::phrase('Leave the slot empty');
						
						$fields['plugins/action'. $suffix]['values']['original']['ord'] = 1;
						$fields['plugins/action'. $suffix]['values']['duplicate']['ord'] = 1.1;
						$fields['plugins/action'. $suffix]['values']['empty']['ord'] = 1.2;
						
						
					}
					
					
				}
				
				//If there are, show the plugins tab, with options for each one
				if ($numPlugins) {
					$box['tabs']['plugins']['hidden'] = false;
					
					$fields['plugins/desc']['snippet']['p'] =
						ze\admin::nphrase('There is 1 library plugins/nests/slideshows in slots on this content item. Please select what you wish to do with this.',
							'There are [[count]] library plugins/nests/slideshows in slots on this content item. Please select what you wish to do with them.',
							$numPlugins
						);
						
					
					$changes = [];
					ze\tuix::setupMultipleRows(
						$box, $fields, $values, $changes, $filling = true,
						$box['tabs']['plugins']['custom_template_fields'],
						$numPlugins,
						$minNumRows = 0,
						$tabName = 'plugins'
					);
				}
				
	
			} else {
				//When editing an existing content item, make the example in the SEO preview box a little more accurate
				$values['meta_data/url_format'] =
					ze\link::toItem($content['id'], $content['type'], false, '', $content['alias'],
						false, $forceAliasInAdminMode = true,
						$content['equiv_id'], $content['language_id']
					);

				//The options to set the alias, categories or privacy (if it is there!) should be hidden when not creating something
				$fields['meta_data/alias']['hidden'] = true;
				$box['tabs']['categories']['hidden'] = true;
				$box['tabs']['privacy']['hidden'] = true;
				// Change code for Special page FAB
				$specialpagesresult = ze\row::get('special_pages', ['page_type'], ['equiv_id' => $content['equiv_id'], 'content_type' =>$content['type']]);
				$pagetype = '';

				if ($specialpagesresult){
					$pagetype = str_replace('_', ' ', ze\ring::chopPrefix('zenario_', $specialpagesresult['page_type'], true)); 
				}
				if($pagetype){
						$fields['meta_data/special_page_message']['hidden'] = false;
						$fields['meta_data/special_page_message']['snippet']['html']= 'This is a special page: '.$pagetype.' page';
				}
				if (array_key_exists("refinerName",$_GET)){
					if($_GET['refinerName'] == 'special_pages'){

						if($specialpagesresult['page_type']=='zenario_not_found' || $specialpagesresult['page_type']=='zenario_no_access'){
							$fields['meta_data/no_menu_warning']['hidden'] = true;
						}
					}
					
				}
				
				$box['identifier']['css_class'] = ze\contentAdm::getItemIconClass($content['id'], $content['type'], true, $content['status']);
			}
	
			$values['meta_data/language_id'] = $values['meta_data/language_id_on_load'] = $content['language_id'];
	
			$fields['meta_data/layout_id']['pick_items']['path'] = 
				'zenario__layouts/panels/layouts/refiners/content_type//' . $content['type']. '//';
	
			if ($version =
				ze\row::get(
					'content_item_versions',
					true,
					['id' => $box['key']['source_cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['source_cVersion']])
			) {
				
				$values['meta_data/title'] = $version['title'];
				$values['meta_data/description'] = $version['description'];
				$values['meta_data/keywords'] = $version['keywords'];
				$values['meta_data/release_date'] = $version['release_date'];
				$values['meta_data/writer_id'] = $version['writer_id'];
				$values['meta_data/writer_name'] = $version['writer_name'];
				$values['meta_data/content_summary'] = $version['content_summary'];
				$values['meta_data/layout_id'] = $version['layout_id'];
				$values['meta_data/exclude_from_sitemap'] = !$version['in_sitemap'];
				$values['meta_data/apply_noindex_meta_tag'] = $version['apply_noindex_meta_tag'];
				$values['css/css_class'] = $version['css_class'];
				$values['css/background_image'] = $version['bg_image_id'];
				$values['css/bg_color'] = $version['bg_color'];
				$values['css/bg_position'] = $version['bg_position'];
				$values['css/bg_repeat'] = $version['bg_repeat'];
				$values['file/file'] = $version['file_id'];
				$values['file/s3_file_id'] = $version['s3_file_id'];
				$values['file/s3_file_name'] = $version['s3_filename'];
				
				if ($box['key']['duplicate'] || $box['key']['translate']) {
					$values['meta_data/menu_text'] = $values['meta_data/title'];
				}
				
				//If a file has already been selected, don't rely on Zenario's standard function for
				//automatically looking up the label, as the internal filename might have been changed
				//and be different to the one used here. Specifically use this filename.
				if ($version['file_id']) {
					if ($file = \ze\file::labelDetails($version['file_id'], $version['filename'])) {
						
						if (empty($fields['file/file']['values'])) {
							$fields['file/file']['values'] = [];
						}
						$fields['file/file']['values'][$file['id']] = $file;
					}
				}
				
				if ($values['meta_data/writer_name']) {
					$fields['meta_data/writer_id']['note_below'] = ze\admin::phrase(
						"Zenario 9.2 migration: please note the previous writer name was [[writer_name]].",
						['writer_name' => $values['meta_data/writer_name']]
					);
				}

				if (ze::setting('aws_s3_support')) {
					if ($values['file/s3_file_id'] && ze\module::inc('zenario_ctype_document')) {
						$s3FileDetails = zenario_ctype_document::getS3FileDetails($values['file/s3_file_id']);

						if (!empty($s3FileDetails) && isset($s3FileDetails['ContentType'])) {
							$values['file/s3_mime_type'] = $s3FileDetails['ContentType'];
						}

						$fields['file/s3_mime_type']['show_as_a_span'] = true;
					}
				}

				$values['meta_data/pinned'] = $version['pinned'];
				$values['meta_data/pinned_duration'] = $version['pinned_duration'];
				$values['meta_data/unpin_date'] = $version['unpin_date'];
				$values['meta_data/pinned_fixed_duration_value'] = $version['pinned_fixed_duration_value'];
				$values['meta_data/pinned_fixed_duration_unit'] = $version['pinned_fixed_duration_unit'];

				if ($allowPinning) {
					if ($values['meta_data/pinned'] && ze::in($values['meta_data/pinned_duration'], 'fixed_date', 'fixed_duration')) {
						if (!$scheduledTaskManagerIsRunning) {
							$fields['meta_data/pinned_error_scheduled_task_manager_not_running']['hidden'] = false;
						} elseif (!$masterSwitchIsOn) {
							$fields['meta_data/pinned_error_scheduled_task_master_switch_is_off']['hidden'] = false;
						} elseif (!$cronTabConfiguredCorrectly) {
							$fields['meta_data/pinned_error_scheduled_task_master_not_set_up_correctly']['hidden'] = false;
						} elseif (!$jobUnpinContentIsEnabled) {
							$fields['meta_data/pinned_error_scheduled_task_master_job_not_running']['hidden'] = false;
						}

						if (!$masterSwitchIsOn || !$cronTabConfiguredCorrectly || !$jobUnpinContentIsEnabled) {
							$fields['meta_data/pinned_duration']['values']['fixed_duration']['hidden'] =
							$fields['meta_data/pinned_duration']['values']['fixed_date']['hidden'] =
							$fields['meta_data/unpin_date']['hidden'] =
							$fields['meta_data/pinned_fixed_duration_value']['hidden'] =
							$fields['meta_data/pinned_fixed_duration_unit']['hidden'] = false;
						}

						$scheduledTaskHref = ze\link::absolute() . 'organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
						$linkStart = '<a href="' . htmlspecialchars($scheduledTaskHref) . '" target="_blank">';
						$linkEnd = "</a>";
						$errorFields = [
							'pinned_error_scheduled_task_manager_not_running',
							'pinned_error_scheduled_task_master_switch_is_off',
							'pinned_error_scheduled_task_master_not_set_up_correctly',
							'pinned_error_scheduled_task_master_job_not_running'
						];
						foreach ($errorFields as $errorField) {
							ze\lang::applyMergeFields($fields['meta_data/' . $errorField]['snippet']['html'], ['link_start' => $linkStart, 'link_end' => $linkEnd]);
						}
					}
				}
				
				if ($box['key']['cID'] && $contentType['enable_summary_auto_update']) {
					$values['meta_data/lock_summary_view_mode'] =
					$values['meta_data/lock_summary_edit_mode'] = $version['lock_summary'];
					$fields['meta_data/lock_summary_view_mode']['hidden'] =
					$fields['meta_data/lock_summary_edit_mode']['hidden'] = false;
				}
		
				if (isset($box['tabs']['categories']['fields']['categories'])) {
					ze\categoryAdm::setupFABCheckboxes(
						$fields['categories/categories'], true,
						$box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion']);
				}
		
				$tag = ze\content::formatTag($box['key']['source_cID'], $box['key']['cType'], ($content['alias'] ?? false));
		
				$status = ze\admin::phrase('archived');
				if ($box['key']['source_cVersion'] == $content['visitor_version']) {
					$status = ze\admin::phrase('published');
		
				} elseif ($box['key']['source_cVersion'] == $content['admin_version']) {
					if ($content['admin_version'] > $content['visitor_version'] && $content['status'] != 'hidden') {
						$status = ze\admin::phrase('draft');
					} elseif ($content['status'] == 'hidden' || $content['status'] == 'hidden_with_draft') {
						$status = ze\admin::phrase('hidden');
					} elseif ($content['status'] == 'trashed' || $content['status'] == 'trashed_with_draft') {
						$status = ze\admin::phrase('trashed');
					}
				}
			}

			//Location (DB, docstore, s3)
			if ($values['file/file']) {
				$fileInfo = ze\row::get('files', ['location', 'path'], $values['file/file']);
				$storageString = "Stored in the [[storage_location]]";
				if (!empty($fileInfo['location']) && $fileInfo['location'] == 'docstore') {
					$storageString .= ", folder name [[folder_name]].";
				} else {
					$storageString .= ".";
				}
				
				if ($fileInfo['path'] && !ze\file::docstorePath($fileInfo['path'])) {
					ze\lang::applyMergeFields($fields['file/file_is_missing']['snippet']['html'], ['path' => $fileInfo['path']]);
					$fields['file/file_is_missing']['hidden'] = false;
				}

				$fields['file/file']['note_below'] = ze\admin::phrase($storageString, ['storage_location' => $fileInfo['location'] ?? '', 'folder_name' => $fileInfo['path'] ?? '']);
			}
		} else {
			//If we are enforcing a specific Content Type, ensure that only layouts of that type can be picked
			if ($box['key']['target_cType']) {
				$fields['meta_data/layout_id']['pick_items']['path'] =
					'zenario__layouts/panels/layouts/refiners/content_type//'. $box['key']['target_cType']. '//';
				
				
				//T10208, Creating content items: auto-populate release date and author where used
				$contentTypeDetails = ze\contentAdm::cTypeDetails($box['key']['target_cType']);

				if ($contentTypeDetails['writer_field'] != 'hidden' && isset($fields['meta_data/writer_id'])) {
					$currentAdminId = ze\admin::id();

					//Check if this admin has a writer profile.
					$writerProfile = ze\row::get('writer_profiles', ['id'], ['admin_id' => (int) $currentAdminId]);
					if ($writerProfile) {
						$values['meta_data/writer_id'] = $writerProfile['id'];
					}
				}

				if ($contentTypeDetails['release_date_field'] != 'hidden'
				 && isset($fields['meta_data/release_date'])) {
					$values['meta_data/release_date'] = ze\date::ymd();
				}
			}
		}


		//We should have loaded or found the cID by now, if this was for editing an existing content item.
		//If there's no cID then we're creating a new content item
		if ($box['key']['cID']) {
			//Any admin can at least view a content item's details

		} elseif ($box['key']['translate']) {
			//When making a translation, check if the admin is allowed to make a translation in this language
			if (!ze\priv::onLanguage('_PRIV_EDIT_DRAFT', $box['key']['target_language_id'])) {
				exit;
			}

		} else {
			//Otherwise require _PRIV_EDIT_DRAFT for creating a new content item
			ze\priv::exitIfNot('_PRIV_EDIT_DRAFT', false, $box['key']['cType']);
		}



		//Set default values
		if ($content) {
			if ($box['key']['duplicate'] || $box['key']['translate']) {
				$values['meta_data/language_id'] = $values['meta_data/language_id_on_load'] = ze::ifNull($box['key']['target_language_id'], ze::ifNull($_GET['languageId'] ?? false, ($_GET['language'] ?? false), $content['language_id']));
			}
		} else {
			$values['meta_data/language_id'] = $values['meta_data/language_id_on_load'] = ze::ifNull($box['key']['target_language_id'], ($_GET['languageId'] ?? false), ze::$defaultLang);
		}
		
		if (!$version) {
			//Attempt to work out the default template and Content Type for a new Content Item
			if (($layoutId = ze::ifNull($box['key']['target_template_id'], ($_GET['refiner__template'] ?? false)))
			 && ($box['key']['cType'] = ze\row::get('layouts', 'content_type', $layoutId))) {
		
	
			} elseif ($box['key']['target_menu_parent']
				   && ($cItem = ze\row::get('menu_nodes', ['equiv_id', 'content_type'], ['id' => $box['key']['target_menu_parent'], 'target_loc' => 'int']))
				   && ($cItem['admin_version'] = ze\row::get('content_items', 'admin_version', ['id' => $cItem['equiv_id'], 'type' => $cItem['content_type']]))
				   && ($layoutId = ze\content::layoutId($cItem['equiv_id'], $cItem['content_type'], $cItem['admin_version']))) {
		
				$box['key']['cType'] = $cItem['content_type'];
	
			} else {
				$box['key']['cType'] = ($box['key']['target_cType'] ?: ($box['key']['cType'] ?: 'html'));
				
				if ($contentType && is_array($contentType) && !empty($contentType['default_layout_id'])) {
					$layoutId = $contentType['default_layout_id'];
				} else {
					$layoutId = 0;
				}
			}
			
			$values['meta_data/layout_id'] = $layoutId;
			
			if (isset($box['tabs']['categories']['fields']['categories'])) {
				
				ze\categoryAdm::setupFABCheckboxes($box['tabs']['categories']['fields']['categories'], true);
		
				if ($categories = $_GET['refiner__category'] ?? false) {
					
					$categories = ze\ray::explodeAndTrim($categories);
					$inCategories = array_flip($categories);
			
					foreach ($categories as $catId) {
						$categoryAncestors = [];
						ze\categoryAdm::ancestors($catId, $categoryAncestors);
				
						foreach ($categoryAncestors as $catAnId) {
							if (!isset($inCategories[$catAnId])) {
								$categories[] = $catAnId;
							}
						}
					}
			
					$box['tabs']['categories']['fields']['categories']['value'] = implode(',', $categories);
				}
			}
		}
		
		if (!$version && $box['key']['target_alias']) {
			$values['meta_data/alias'] = $box['key']['target_alias'];
		}
		if (!$version && $box['key']['target_title']) {
			$values['meta_data/title'] = $box['key']['target_title'];
		}
		if (isset($box['tabs']['categories']['fields']['desc'])) {
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'organizer.php#zenario__content/panels/categories'). '" target="_blank"']);
		
				if (ze\row::exists('categories', [])) {
					$fields['categories/no_categories']['hidden'] = true;
				} else {
					$fields['categories/categories']['hidden'] = true;
				}
		}



		//Turn edit mode on if we will be creating a new Content Item
		if (!$box['key']['cID'] || $box['key']['cID'] != $box['key']['source_cID']) {
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = true;
					$tab['edit_mode']['on'] = true;
				}
			}

		//And turn it off if we are looking at an archived version of an existing Content Item, or a locked Content Item
		} elseif ($box['key']['cID']
			   && $content
			   && ($box['key']['cVersion'] < $content['admin_version'] || !ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType']))
		) {
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = false;
				}
			}

		} else {
			foreach ($box['tabs'] as $i => &$tab) {
				if (is_array($tab) && isset($tab['edit_mode'])) {
					$tab['edit_mode']['enabled'] = true;
				}
			}
		}

		if ($box['key']['source_cID']) {
			if ($box['key']['cID'] != $box['key']['source_cID']) {
				if ($box['key']['target_language_id'] && $box['key']['target_language_id'] != $content['language_id']) {
					$box['title'] =
						ze\admin::phrase('Creating a translation in "[[lang]]" of the content item "[[tag]]" ([[old_lang]]).',
							['tag' => $tag, 'old_lang' => $content['language_id'], 'lang' => ze\lang::name($box['key']['target_language_id'])]);
					//Check if the source content item is in the menu.
					//If it is, offer to add the translation to the menu.
					//Otherwise, do not show the menu section.
					$sourceContentItemMenu = ze\menu::getFromContentItem($box['key']['source_cID'], $box['key']['cType']);
					if (!$sourceContentItemMenu) {
						$fields['meta_data/menu_text']['hidden'] = true;
					} else {
						unset($fields['meta_data/menu_text']['indent']);
					}
					$fields['meta_data/menu_invisible']['hidden'] = true;
			
				} elseif ($box['key']['source_cVersion'] < $content['admin_version']) {
					$box['title'] =
						ze\admin::phrase('Duplicating the [[status]] (version [[version]]) Content Item "[[tag]]"',
							['tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']]);
				} else {
					$box['title'] =
						ze\admin::phrase('Duplicating the [[status]] content item "[[tag]]"',
							['tag' => $tag, 'status' => $status]);
				}
			} else {
				if ($box['key']['source_cVersion'] < $content['admin_version']) {
					$box['title'] =
						ze\admin::phrase('Viewing metadata of content item "[[tag]]", version [[version]] ([[status]])',
							['tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']]);
				} else {
					$box['title'] =
						ze\admin::phrase('Editing metadata (version-controlled) of content item "[[tag]]", version [[version]] ([[status]])',
							['tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']]);
				}
			}
		} elseif (($box['key']['target_cType'] || (!$box['key']['id'] && $box['key']['cType'])) && $contentType) {
			$box['title'] = ze\admin::phrase('Creating a content item, [[content_type_name_en]]', $contentType);
		}

		if ($lockLanguageId || ($box['key']['cID'] && $box['key']['cType'] && ze\content::isSpecialPage($box['key']['cID'], $box['key']['cType']))) {
			$box['tabs']['meta_data']['fields']['language_id']['show_as_a_span'] = true;
		}


		//Attempt to load the content into the content tabs for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])) {
			$i = 0;
			$slots = [];
			
			$moduleIds = ze\module::id('zenario_wysiwyg_editor');
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']
			 && ($slots = ze\contentAdm::mainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $moduleIds, false))
			 && (!empty($slots))) {
	
				//Set the content for each slot, with a limit of four slots
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
					$values['content'. $i. '/content'] =
						ze\contentAdm::getContent($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $slot);
					$fields['content'. $i. '/content']['pre_field_html'] =
						'<div class="zfab_content_in">'. ze\admin::phrase('Edit [[slotName]] (WYSIWYG area):', ['slotName' => $slot]). '</div>';
				}
			}
		}
		
		//Attempt to load the raw html into the content tabs for each RAW HTML
		if (isset($box['tabs']['rawhtml1'])) {
			$i = 0;
			$slots = [];
			$moduleIds = ze\module::id('zenario_html_snippet');
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']
			 && ($slots = ze\contentAdm::mainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $moduleIds, false))
			 && (!empty($slots))) {
	
				//Set the content for each slot, with a limit of four slots
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
					$values['rawhtml'. $i. '/content'] =
						ze\contentAdm::getContent($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $slot, 'zenario_html_snippet');
					$fields['rawhtml'. $i. '/content']['pre_field_html'] =
						'<div class="zfab_content_in">'. ze\admin::phrase('Edit [[slotName]] (Raw HTML):', ['slotName' => $slot]). '</div>';
				}
			}
		}

		// Hide categories if not enabled by cType
		if (!$contentType['enable_categories']) {
			$box['tabs']['categories']['hidden'] = true;
		}


		if ($box['key']['cID']) {
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
			$fields['meta_data/layout_id']['hidden'] = true;
		} else {
			$box['key']['id'] = null;
		}
		
		
		$this->fillMenu($box, $fields, $values, $contentType, $content, $version);
		
		if ($values['css/background_image'] || $values['css/bg_color'] || $values['css/bg_position'] || $values['css/bg_repeat']) {
			$values['css/customise_background'] = true;
		}
		//To show history tab in content FAB
		if($box['key']['id']){
			$box['tabs']['history']['hidden'] = false;
			$content = ze\row::get('content_items', true, ['tag_id' => $box['key']['id']]);
			$sql = "SELECT version, created_datetime, 
							(SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.creating_author_id) as creating_author,
							last_modified_datetime, 
							(SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.last_author_id) as last_author,
							published_datetime,scheduled_publish_datetime,
							(SELECT username FROM " . DB_PREFIX . "admins as a WHERE a.id = v.publisher_id) as publisher
						FROM " . DB_PREFIX . "content_item_versions as v 
						WHERE v.tag_id = '" . ze\escape::asciiInSQL($box['key']['id']) . "'
						ORDER BY v.version desc";
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result) > 0 ) {
				
				$fields['history/th_version']['hidden'] =
				$fields['history/th_created']['hidden'] =
				$fields['history/th_last_edited']['hidden'] =
				$fields['history/th_status']['hidden'] =
				$fields['history/th_published']['hidden'] =
				$fields['history/th_comments']['hidden'] = false;
				
				
				$fields['history/no_history_recorded']['hidden'] = true;
				
				$totalRowNum = 0;
				while ($row = ze\sql::fetchAssoc($result)) {
					++$totalRowNum;
					$suffix = '__' . $totalRowNum;
					
					$values['history' . '/version' . $suffix] = $row['version'];
					
					$bycreating_author='';
					$bypublisher='';
					$bylast_author='';
					
					if($row['creating_author'])
						$bycreating_author = ' by '.$row['creating_author'];
				
					if($row['last_author'])
						$bylast_author = ' by '.$row['last_author'];
					
					if($row['publisher'])
						$bypublisher = ' by '.$row['publisher'];

					$values['history' . '/last_edited' . $suffix] = ze\admin::formatDateTime($row['last_modified_datetime'], 'vis_date_format_med').$bylast_author;
					$values['history' . '/published' . $suffix] = ze\admin::formatDateTime($row['published_datetime'], 'vis_date_format_med').$bypublisher;
					$values['history' . '/created' . $suffix]  = ze\admin::formatDateTime($row['created_datetime'],'vis_date_format_med').$bycreating_author;
					$values['history' . '/status' . $suffix] = ze\contentAdm::getContentItemVersionStatus($content, $row['version']);
					if($values['history' . '/status' . $suffix] == 'draft') {
						if($content['lock_owner_id']) {
							$admin_details = ze\admin::details($content['lock_owner_id']);
							$values['history' . '/comments' . $suffix] = ze\admin::phrase('Locked by [[username]]', $admin_details);
						}
					}
					if ($totalRowNum > 500) {
						break;
					}
				}
				
			}
			$changes = [];
				ze\tuix::setupMultipleRows(
					$box, $fields, $values, $changes, $filling = true,
					$box['tabs']['history']['custom_template_fields'],
					$totalRowNum,
					$minNumRows = 0,
					$tabName = 'history'
				);
			//To show warning message for locked content item in FAB	
		   	if($content['lock_owner_id']) {
				$box['tabs']['meta_data']['notices']['locked_warning']['show'] = true;
				$admin_details = ze\admin::details($content['lock_owner_id']);

				if(ze\admin::id() == $content['lock_owner_id'])
				{
					$box['tabs']['meta_data']['notices']['locked_warning']['message'] = ze\admin::phrase('This item is locked by you.');
				}
				else{
					$box['tabs']['meta_data']['notices']['locked_warning']['message'] = ze\admin::phrase('This item is locked by [[username]].', $admin_details);
				}
			
			}
			
			
			//Show a note if this is scheduled to be published
			$sql = "
				SELECT c.id, c.type, v.scheduled_publish_datetime
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "content_item_versions AS v
				   ON v.id = c.id
				  AND v.type = c.type
				  AND v.version = c.admin_version
				WHERE c.id = ". (int) $box['key']['cID']. "
				  AND c.type = '". ze\escape::asciiInSQL($box['key']['cType']). "'
				  AND v.scheduled_publish_datetime IS NOT NULL";
		
			if ($row = ze\sql::fetchAssoc($sql)) {
				$box['tabs']['meta_data']['notices']['scheduled_warning']['show'] = true;
				
				$row['publication_time'] = 
					ze\admin::formatDateTime($row['scheduled_publish_datetime'], 'vis_date_format_med');
				
				$box['tabs']['meta_data']['notices']['scheduled_warning']['message'] =
					ze\admin::phrase("This item is scheduled to be published at [[publication_time]].", $row);
			}
		}
		
		if (ze::setting('aws_s3_support') && ze\module::inc('zenario_ctype_document')) {
			$fields['file/file']['label'] = ze\admin::phrase('Local file:');
			$fields['file/s3_file_upload']['hidden'] = false;
			$maxUploadSize = ze\file::fileSizeConvert(ze\dbAdm::apacheMaxFilesize());
			
			if (ze\dbAdm::apacheMaxFilesize() < ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'), ze::setting('content_max_filesize_unit'))) {
				$maxLocalUploadSize = $maxUploadSize;
			} else {
				$maxLocalUploadSize = ze\file::fileSizeConvert(ze\file::fileSizeBasedOnUnit(ze::setting('content_max_filesize'), ze::setting('content_max_filesize_unit')));
			}

			if (ze\dbAdm::apacheMaxFilesize() < 5368706371) {
				$maxS3UploadSize = $maxUploadSize;
			} else {
				$maxS3UploadSize = ze\file::fileSizeConvert(5368706371);
			}
			
			$box['tabs']['file']['fields']['document_desc']['snippet']['html'] =
				ze\admin::phrase('Please upload a local file (for storage in Zenario\'s docstore), maximum size [[maxLocalUploadSize]].', ['maxLocalUploadSize' => $maxLocalUploadSize]); 
			
			$box['tabs']['file']['fields']['s3_document_desc']['snippet']['html'] =
				ze\admin::phrase('You can upload a related file for storage on AWS S3, maximum size [[maxS3UploadSize]].', ['maxS3UploadSize' => $maxS3UploadSize]);
		} else {
			$fields['file/s3_file_upload']['hidden'] = true;
			$fields['file/s3_mime_type']['hidden'] = true;
		}

		if (!$contentType['enable_css_tab']) {
			$box['tabs']['css']['hidden'] = true;
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$box['tabs']['file']['hidden'] = true;
		if (ze::setting('aws_s3_support') && ze\module::inc('zenario_ctype_document')) {
			$src = ze\link::protocol(). \ze\link::host(). SUBDIRECTORY.'zenario/s3FileUpload.php';
			$requests = '?cId='. $box['key']['cID'] .'&cType='. $box['key']['cType']. '&cVersion='. $box['key']['source_cVersion'] . '&mime_type=' . htmlspecialchars($values['file/s3_mime_type']);

			if ($values['file/s3_file_remove']) {
				$requests .= "&remove=1";
			}
				
			$s3_file_upload = "<iframe id=\"s3_file_upload\" name=\"s3_file_upload\" src=\"" . $src . $requests . "\" style=\"border: none;\"></iframe>\n";
			
			$fields['file/s3_file_upload']['snippet']['html'] = $s3_file_upload;
		}
		
				
		if (!$box['key']['cID']) {
			if ($values['meta_data/layout_id']) {
				$box['key']['cType'] = ze\row::get('layouts', 'content_type', $values['meta_data/layout_id']);
			}
		}
		$fields['css/background_image']['side_note'] = '';
		$fields['css/bg_color']['side_note'] = '';
		$fields['css/bg_position']['side_note'] = '';
		$fields['css/bg_repeat']['side_note'] = '';
		$box['tabs']['meta_data']['notices']['archived_template']['show'] = false;

		if ($values['meta_data/layout_id']
		 && ($layout = ze\content::layoutDetails($values['meta_data/layout_id']))) {
	
			if ($layout['status'] != 'active') {
				$box['tabs']['meta_data']['notices']['archived_template']['show'] = true;
			}
	
			if ($layout['bg_image_id']) {
				$fields['css/background_image']['side_note'] = htmlspecialchars(
					ze\admin::phrase("Setting a background image here will override the background image set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_color']) {
				$fields['css/bg_color']['side_note'] = htmlspecialchars(
					ze\admin::phrase("Setting a background color here will override the background color set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_position']) {
				$fields['css/bg_position']['side_note'] = htmlspecialchars(
					ze\admin::phrase("Setting a background position here will override the background position set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_repeat']) {
				$fields['css/bg_repeat']['side_note'] = htmlspecialchars(
					ze\admin::phrase("Setting an option here will override the option set on this item's layout ([[id_and_name]]).", $layout));
			}
		}
		
		$fields['meta_data/description']['hidden'] = false;
		$fields['meta_data/writer']['hidden'] = false;
		$fields['meta_data/keywords']['hidden'] = false;
		$fields['meta_data/release_date']['hidden'] = false;
		$fields['meta_data/content_summary']['hidden'] = false;
		if ($box['key']['cType'] && $details = ze\contentAdm::cTypeDetails($box['key']['cType'])) {
			if ($details['description_field'] == 'hidden') {
				$fields['meta_data/description']['hidden'] = true;
			}
			if ($details['keywords_field'] == 'hidden') {
				$fields['meta_data/keywords']['hidden'] = true;
			}
			if ($details['release_date_field'] == 'hidden') {
				$fields['meta_data/release_date']['hidden'] = true;
			}
			if ($details['writer_field'] == 'hidden') {
				$fields['meta_data/writer_id']['hidden'] = true;
			}
			if ($details['summary_field'] == 'hidden') {
				$fields['meta_data/content_summary']['hidden'] = true;
			}
		}


		if ($box['key']['cID']) {
			$languageId = ze\content::langId($box['key']['cID'], $box['key']['cType']);
			$specialPage = ze\content::isSpecialPage($box['key']['cID'], $box['key']['cType']);
		} else {
			$languageId = ($values['meta_data/language_id'] ?: ($box['key']['target_template_id'] ?: ze::$defaultLang));
			$specialPage = false;
		}

		$descriptionCounterHTML = '
			<div class="snippet__description" >
				<div id="snippet__description_length" class="[[initial_class_name]]">
					<span id="snippet__description_counter">[[initial_characters_count]]</span>
				</div>
			</div>';

		$keywordsCounterHTML = '
			<div class="snippet__keywords" >
				<div id="snippet__keywords_length" >
					<span id="snippet__keywords_counter">[[initial_characters_count]]</span>
				</div>
			</div>';


		if (strlen($values['meta_data/title']) < 1) {
			$fields['meta_data/title']['note_below'] = 'Please enter a title.';
		} elseif (strlen($values['meta_data/title']) < 20)  {
			$fields['meta_data/title']['note_below'] = 'For good SEO, make the title longer.';
		} elseif (strlen($values['meta_data/title']) < 40)  {
			$fields['meta_data/title']['note_below'] = 'For good SEO, make the title a little longer.';
		} elseif (strlen($values['meta_data/title']) < 66)  {
			$fields['meta_data/title']['note_below'] = 'This is a good title length for SEO.';
		} else {
			$fields['meta_data/title']['note_below'] = 'The title may not be fully visible in search engine results.';
		}

		$fields['meta_data/title']['onclick'] = $fields['meta_data/title']['oninput'];


		if (strlen($values['meta_data/description'])<1) {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_red', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'For good SEO, enter a description. If this field is left blank, search engines will auto-generate a description which may not be as well-worded.';
		} elseif (strlen($values['meta_data/description'])<50)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_orange', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'For good SEO, make the description longer to entice people to click through from a result list.';
		} elseif (strlen($values['meta_data/description'])<100)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'For good SEO, make the description a little longer to entice people to click through from a result list.';
		} elseif (strlen($values['meta_data/description'])<156)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_green', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'This is a good description length for SEO.';
		} else {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'The description is a little long for good SEO as it may not be fully visible.';
		}
		$descriptionCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/description']), $descriptionCounterHTML);
		$box['tabs']['meta_data']['fields']['description']['post_field_html'] = $descriptionCounterHTML;


		$keywordsCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/keywords']) , $keywordsCounterHTML);
		$box['tabs']['meta_data']['fields']['keywords']['post_field_html'] = $keywordsCounterHTML;
		
		$WYSIWYGCount=0;
		$RawCount=0;
		//Set up content tabs (up to four of them), for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])) {
			$i = 0;
			$slots = [];
			$rawslots = [];
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']) {
				// As per T11743 we need to show slot for more than one module ID 
				
				$rawmoduleIds = ze\module::id('zenario_html_snippet');
				$rawslots = ze\contentAdm::mainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $rawmoduleIds, false, $values['meta_data/layout_id']);

				$moduleIds = ze\module::id('zenario_wysiwyg_editor');
				
				
				$slots = ze\contentAdm::mainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $moduleIds, false, $values['meta_data/layout_id']);
			} else {
				$slots = ze\layoutAdm::mainSlot($values['meta_data/layout_id'], false, false);
			}
			
			if (!empty($slots)) {
				$rawslot = sizeof($rawslots);
			}

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
		
					$box['tabs']['content'. $i]['hidden'] = false;
					if (count($slots) == 1 && $rawslot<1) {
						$box['tabs']['content'. $i]['label'] = ze\admin::phrase('Main content');
			
					} elseif (strlen($slot) <= 20) {
						$box['tabs']['content'. $i]['label'] = $slot;
			
					} else {
						$box['tabs']['content'. $i]['label'] = substr($slot, 0, 8). '...'. substr($slot, -8);
					}
					$WYSIWYGCount++;
					
					//Try and ensure that we use relative URLs where possible
					ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
				}
			}
			
	
			// Hide extra content tabs
			while (++$i <= 4) {
				$box['tabs']['content'. $i]['hidden'] = true;
			}
		}
		
		//Set up content tabs (up to four of them), for each Raw HTML Snippets
		if (isset($box['tabs']['rawhtml1'])) {
			$i = 0;
			$slots = [];
			$moduleIds = ze\module::id('zenario_html_snippet');
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']) {
				$slots = ze\contentAdm::mainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $moduleIds, false, $values['meta_data/layout_id']);
			} else {
				$slots = ze\layoutAdm::mainSlot($values['meta_data/layout_id'], $moduleIds, false);
			}

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}

					$box['tabs']['rawhtml'. $i]['hidden'] = false;
					if (count($slots) == 1 && $WYSIWYGCount==0) {
						$box['tabs']['rawhtml'. $i]['label'] = ze\admin::phrase('Main content');
			
					} elseif (strlen($slot) <= 20) {
						$box['tabs']['rawhtml'. $i]['label'] = $slot;
			
					} else {
						$box['tabs']['rawhtml'. $i]['label'] = substr($slot, 0, 8). '...'. substr($slot, -8);
					}
					$RawCount++;
					
					//Try and ensure that we use relative URLs where possible
					ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['rawhtml'. $i]['fields']['content']);
				}
			}
			

			// Hide extra content tabs
			while (++$i <= 4) {
				$box['tabs']['rawhtml'. $i]['hidden'] = true;
			}
		}
		// Hide dropdown if no content tabs are visible
			$bothCount = $WYSIWYGCount+$RawCount;
			if ($bothCount <= 1) {
				$box['tabs']['content_dropdown']['hidden'] = true;
				if ($bothCount == 1 ) {
					unset($box['tabs']['rawhtml1']['parent']);
					unset($box['tabs']['content1']['parent']);
				}
				
			}
		
		if (isset($box['tabs']['meta_data']['fields']['content_summary'])) {
			//Try and ensure that we use relative URLs where possible
			ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
		}
		
		//Show the options for the site-map/search engine preview by default
		$fields['meta_data/excluded_from_sitemap']['hidden'] = true;
		$fields['meta_data/included_in_sitemap']['hidden'] = false;
		
		if ($box['key']['cID']
		 && ze::in(ze\content::isSpecialPage($box['key']['cID'], $box['key']['cType']), 'zenario_not_found', 'zenario_no_access')) {
			
			//Hide these options for the 403/404 pages
			$fields['meta_data/excluded_from_sitemap']['hidden'] = false;
			$fields['meta_data/included_in_sitemap']['hidden'] = true;
		}

		$fields['meta_data/apply_noindex_meta_tag']['note_below'] = ze\admin::phrase(
			'<p>
				Put a <code>noindex</code> meta tag in the <head> section of the page when displaying this content item. This will be of the format:
				<br \>
				<code>&lt;meta name=&quot;robots&quot; content=&quot;noindex&quot;&gt;</code>
			</p>'
		);
		
		if (isset($box['key']['id'])) {
			$fields['meta_data/suggest_alias_from_title']['hidden'] = true;
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id']);
			$equivId = ze\content::equivId($cID, $cType);
			$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $equivId, 'type' => $cType]);
			
			if ($contentItemPrivacy != 'public') {
				unset($fields['meta_data/title']['post_field_html']);
				unset($fields['meta_data/title']['note_below']);
				unset($fields['meta_data/description']['post_field_html']);
				unset($fields['meta_data/description']['note_below']);
			}

			//Extra info field when editing content items on a site
			//with multiple languages enabled
			$languagesEnabledOnSite = ze\lang::getLanguages(false, false, $defaultLangFirst = true);
			$numLanguageEnabled = count($languagesEnabledOnSite);
			$defaultLanguage = ze::$defaultLang;

			if ($numLanguageEnabled > 1) {
				$mainLanguageContentItemSql = "
					SELECT id, type, language_id, alias, visitor_version, admin_version, status, tag_id
					FROM ". DB_PREFIX. "content_items
					WHERE equiv_id = " . (int) $equivId . "
					AND type = '" . ze\escape::asciiInSQL($box['key']['cType']) . "'
					AND id = " . (int) $equivId;
				$mainLanguageContentItemResult = ze\sql::select($mainLanguageContentItemSql);
				$mainLanguageContentItem = ze\sql::fetchAssoc($mainLanguageContentItemResult);

				$translationsCountSql = "
					SELECT COUNT(id)
					FROM ". DB_PREFIX. "content_items
					WHERE equiv_id = " . (int) $equivId . "
					AND type = '" . ze\escape::asciiInSQL($box['key']['cType']) . "'
					AND id <> " . (int) $box['key']['cID'];
				$translationsCountResult = ze\sql::select($translationsCountSql);
				$translationsCount = ze\sql::fetchValue($translationsCountResult);

				$translationChainHref =
					ze\link::absolute(). 'organizer.php#zenario__content/panels/content/refiners/content_type//' 
					. htmlspecialchars($box['key']['cType']) . '//item_buttons/zenario_trans__view//' . htmlspecialchars($mainLanguageContentItem['tag_id']) . '//';
				$translationChainLinkStart = '<a href="' . $translationChainHref . '" target="_blank">';
				$translationChainLinkEnd = '</a>';
				$viewTranslationChainPhrase = ze\admin::phrase('[[link_start]]View translation chain[[link_end]]', ['link_start' => $translationChainLinkStart, 'link_end' => $translationChainLinkEnd]);
				
				$fields['meta_data/content_item_translation_info']['hidden'] = false;
				$fields['meta_data/content_item_translation_info']['snippet']['html'] = '<div class="zenario_fbInfo">';

				if ($box['key']['cID'] == $equivId) {
					//This is the main or the only content item in the chain.
					if ($values['meta_data/language_id_on_load'] == $defaultLanguage) {
						//If this is the content item in the site's main language,
						//show the translation counter.
						
						$fields['meta_data/content_item_translation_info']['snippet']['html'] .=
							ze\admin::nPhrase(
								"This content item has 1 translation.",
								"This content item has [[translation_count]] translations.",
								(int) $translationsCount,
								['translation_count' => (int) $translationsCount]
							);
						$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
					} else {
						if ($translationsCount > 0) {
							$defaultLanguageName = $languagesEnabledOnSite[$defaultLanguage]['english_name'];
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .=
								ze\admin::phrase(
									'This item is in a translation chain with no item in the default language ([[default_language_name]]).',
									['default_language_name' => $defaultLanguageName]
								);
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
						} else {
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ze\admin::phrase('This item is not in a translation chain.');
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
						}
					}
				} else {
					//This is a translation.
					if ($mainLanguageContentItem['language_id'] == $defaultLanguage) {
						//The main item in this chain is in the site's default language.
						$mainLanguageContentItemTag = ze\content::formatTag($mainLanguageContentItem['id'], $mainLanguageContentItem['type'], ($mainLanguageContentItem['alias'] ?? false));
						
						$fields['meta_data/content_item_translation_info']['snippet']['html'] .=
							ze\admin::phrase('This item is in the translation chain of "[[tag]]".', ['tag' => $mainLanguageContentItemTag]);
						$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
					} else {
						if ($translationsCount > 0) {
							$defaultLanguageName = $languagesEnabledOnSite[$defaultLanguage]['english_name'];
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .=
								ze\admin::phrase(
									'This item is in a translation chain with no item in the default language ([[default_language_name]]).',
									['default_language_name' => $defaultLanguageName]
								);
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
						} else {
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ze\admin::phrase('This item is not in a translation chain.');
							$fields['meta_data/content_item_translation_info']['snippet']['html'] .= ' ' . $viewTranslationChainPhrase;
						}
					}
				}

				$fields['meta_data/content_item_translation_info']['snippet']['html'] .= '</div>';
			}
		}
		if (!$values['meta_data/alias_changed']) {
			$fields['meta_data/suggest_alias_from_title']['style'] = 'display:none';
		} else {
			$fields['meta_data/suggest_alias_from_title']['style'] = '';
		}

		unset($fields['meta_data/pinned_fixed_duration_value']['note_below']);
		$allowPinning = ze\row::get('content_types', 'allow_pinned_content', ['content_type_id' => $box['key']['cType']]);
		if ($allowPinning && $values['meta_data/pinned'] && $values['meta_data/pinned_duration'] == 'fixed_duration') {
			if (preg_match('/^[0-9]{1,2}$/', $values['meta_data/pinned_fixed_duration_value'])) {
				//Work out the unpin date
				$newEndDate = new DateTime();
				$newEndDate->setTime(00, 00);
				//Work out if this is supposed to be singular day/week or plural days/weeks.
				if ($values['meta_data/pinned_fixed_duration_value'] > 1) {
					$unit = $values['meta_data/pinned_fixed_duration_unit'] . 's';
				} else {
					$unit = $values['meta_data/pinned_fixed_duration_unit'];
				}

				//Example: "+1 day". "+2 weeks" etc.
				$newEndDate->modify('+' . $values['meta_data/pinned_fixed_duration_value'] . ' ' . $unit);
				$unpinDate = ze\admin::formatDate($newEndDate);

				$taskId = (int) ze\row::get('jobs', 'id', ['job_name' => 'jobUnpinContent']);
				$scheduledTaskHref = ze\link::absolute() . 'organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks//' . $taskId . '~.zenario_job~ttime_and_day~k{"id"%3A"' . $taskId . '"}';
				$linkStart = '<a href="' . htmlspecialchars($scheduledTaskHref) . '" target="_blank">';
				$linkEnd = "</a>";
				
				$fields['meta_data/pinned_fixed_duration_value']['note_below'] = ze\admin::phrase(
					'Will be unpinned on the first run of scheduled task jobUnpinContent on or after [[date_and_time]]. [[link_start]]Click for more info.[[link_end]]',
					['date_and_time' => $unpinDate, 'link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			}
		}
		
		
		$this->autoSetTitle($box, $fields, $values);
	}
	
	public function autoSetTitle(&$box, &$fields, &$values) {
		
		//If we've creating a new content item...
		if (!$box['key']['cID'] && !$box['key']['source_cID']) {
			
			//...and the admin just changed the title...
			if ($box['key']['last_title'] != $values['meta_data/title']) {
				
				//Check if there's a main content area
				if (isset($box['tabs']['content1']['hidden'])
				 && empty($box['tabs']['content1']['hidden'])) {
					
					//Check if the main content area is empty, or was set by this algorithm before.
					if (empty($values['content1/content'])
					 || !($existingText = trim(str_replace('&nbsp;', ' ', strip_tags($values['content1/content']))))
					 || ($existingText == $box['key']['last_title'])
					 || ($existingText == htmlspecialchars($box['key']['last_title']))) {
						
						$whenCreatingPutTitleInBody = ze\row::get('content_types', 'when_creating_put_title_in_body', $box['key']['cType'] ?: $box['key']['target_cType']);
						if ($whenCreatingPutTitleInBody) {
							$values['content1/content'] = '<h1>'. htmlspecialchars($values['meta_data/title']). '</h1>';
						}
					}
				}
				
				//Check if there's a main content area
				if (isset($box['tabs']['rawhtml1']['hidden'])
				 && empty($box['tabs']['rawhtml1']['hidden'])) {
					
					//Check if the main content area is empty, or was set by this algorithm before.
					if (empty($values['rawhtml1/content'])
					 || !($existingText = trim(str_replace('&nbsp;', ' ', strip_tags($values['rawhtml1/content']))))
					 || ($existingText == $box['key']['last_title'])
					 || ($existingText == htmlspecialchars($box['key']['last_title']))) {
						$values['rawhtml1/content'] = '<h1>'. htmlspecialchars($values['meta_data/title']). '</h1>';
					}
				}
				
				$box['key']['last_title'] = $values['meta_data/title'];
			}
		}
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$this->autoSetTitle($box, $fields, $values);
		
		$box['confirm']['show'] = false;
		$box['confirm']['message'] = '';
		
		if (!$box['key']['cID']) {
			if (!$values['meta_data/layout_id']) {
				$box['tab'] = 'meta_data';
				$fields['meta_data/layout_id']['error'] = ze\admin::phrase('Please select a layout.');
			} else {
				$box['key']['cType'] = ze\row::get('layouts', 'content_type', $values['meta_data/layout_id']);
			}

		} else {
			ze\layoutAdm::validateChangeSingleLayout($box, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $values['meta_data/layout_id'], $saving);
		}

		if (!ze\contentAdm::isCTypeRunning($box['key']['cType'])) {
			$box['tabs']['meta_data']['errors'][] =
				ze\admin::phrase(
					'Drafts of "[[cType]]" type content items cannot be created as their handler module is missing or not running.',
					['cType' => $box['key']['cType']]);
		}

		if (!$values['meta_data/title']) {
			$fields['meta_data/title']['error'] = ze\admin::phrase('Please enter a title.');
		}

		if (!empty($values['meta_data/alias'])) {
			$errors = false;
			if ($box['key']['translate']) {
				if (ze::setting('translations_different_aliases')) {
					$errors = ze\contentAdm::validateAlias($values['meta_data/alias'], false, $box['key']['cType'], ze\content::equivId($box['key']['source_cID'], $box['key']['cType']));
				}
			} else {
				$errors = ze\contentAdm::validateAlias($values['meta_data/alias']);
			}
			if (!empty($errors) && is_array($errors)) {
				$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
			}
		}


		if ($box['key']['cType'] && $details = ze\contentAdm::cTypeDetails($box['key']['cType'])) {
			if ($details['description_field'] == 'mandatory' && !$values['meta_data/description']) {
				$fields['meta_data/description']['error'] = ze\admin::phrase('Please enter a description.');
			}
			if ($details['keywords_field'] == 'mandatory' && !$values['meta_data/keywords']) {
				$fields['meta_data/keywords']['error'] = ze\admin::phrase('Please enter keywords.');
			}
			if ($details['release_date_field'] == 'mandatory' && !$values['meta_data/release_date']) {
				$fields['meta_data/release_date']['error'] = ze\admin::phrase('Please enter a release date.');
			}
			if ($details['writer_field'] == 'mandatory' && !$values['meta_data/writer_id']) {
				$fields['meta_data/writer_id']['error'] = ze\admin::phrase('Please select a writer.');
			}
			if ($details['summary_field'] == 'mandatory' && !$values['meta_data/content_summary']) {
				$fields['meta_data/content_summary']['error'] = ze\admin::phrase('Please enter a summary.');
			}
		}

		if ($box['key']['translate'] || ($box['key']['cID'] && $values['meta_data/language_id'] != $values['meta_data/language_id_on_load'])) {
			$equivId = ze\content::equivId($box['key']['source_cID'], $box['key']['cType']);
	
			if (ze\row::exists('content_items', ['equiv_id' => $equivId, 'type' => $box['key']['cType'], 'language_id' => $values['meta_data/language_id']])) {
				$box['tabs']['meta_data']['errors'][] = ze\admin::phrase('This translation already exists.');
			}
		}
		
		$errorsOnTab = false;
		foreach ($box['tabs']['plugins']['fields'] as $field) {
			if (isset($field['error'])) {
				$errorsOnTab = true;
				break;
			}
		}
		
		if ($errorsOnTab) {
			$fields['plugins/table_end']['error'] = ze\admin::phrase('Please select an action for each plugin.');
		}

		//Pinned duration
		if ($values['meta_data/pinned'] && ze::in($values['meta_data/pinned_duration'], 'fixed_date', 'fixed_duration')) {
			if ($values['meta_data/pinned_duration'] == 'fixed_date') {
				if (!$values['meta_data/unpin_date']) {
					$fields['meta_data/unpin_date']['error'] = ze\admin::phrase('Please select the unpin date.');
				} else {
					$dateFrom = new DateTime();
					$timestampFrom = $dateFrom->getTimestamp();

					$dateTo = new DateTime($values['meta_data/unpin_date']);
					$timestampTo = $dateTo->getTimestamp();

					if ($timestampFrom > $timestampTo) {
						$fields['meta_data/unpin_date']['error'] = ze\admin::phrase('The unpin date cannot be in the past or on the same day.');
					}
				}
			} elseif ($values['meta_data/pinned_duration'] == 'fixed_duration') {
				if (!preg_match('/^[0-9]{1,2}$/', $values['meta_data/pinned_fixed_duration_value']) || $values['meta_data/pinned_fixed_duration_value'] < 1 || $values['meta_data/pinned_fixed_duration_value'] > 99) {
					$fields['meta_data/pinned_fixed_duration_value']['error'] = ze\admin::phrase('The value needs to be a whole number between 1 and 99.');
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['cID'] && !ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			exit;
		}
		
		$isNewContentItem = !$box['key']['cID'];
		
		//Create a new Content Item, or a new Draft of a Content Item, as needed.
		$newDraftCreated = ze\contentAdm::createDraft($box['key']['cID'], $box['key']['source_cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['source_cVersion'], $values['meta_data/language_id']);

		if (!$box['key']['cID']) {
			exit;
		} else {
			$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
		}

		$version = [];
		$newLayoutId = false;
		
		//If we're creating a new content item in the front-end, try to start off in Edit mode
		if (($isNewContentItem && !$box['key']['create_from_content_panel']) || $box['key']['duplicate']) {
			$_SESSION['page_toolbar'] = 'edit';
			$_SESSION['page_mode'] = 'edit';
			$_SESSION['last_item'] = $box['key']['cType'].  '_'. $box['key']['cID']. '.'. $box['key']['cVersion'];

			if ($box['key']['duplicate']) {
				$_SESSION['zenario__content_item_duplicated'] = true;
			} else {
				$_SESSION['zenario__content_item_created'] = true;
			}
		}


		//Save the values of each field in the Meta Data tab
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'])
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			//Only save aliases for first drafts
			if (!empty($values['meta_data/alias']) && $box['key']['cVersion'] == 1) {
				if (!$box['key']['translate'] || ze::setting('translations_different_aliases')) {
					ze\row::set('content_items', ['alias' => ze\contentAdm::tidyAlias($values['meta_data/alias'])], ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
				}
			}

			//Set the title
			$version['title'] = $values['meta_data/title'];
			$version['description'] = $values['meta_data/description'];
			$version['keywords'] = $values['meta_data/keywords'];
			$version['release_date'] = $values['meta_data/release_date'];
			$version['writer_id'] = $values['meta_data/writer_id'];
			$version['in_sitemap'] = !$values['meta_data/exclude_from_sitemap'];
			$version['apply_noindex_meta_tag'] = ($values['meta_data/exclude_from_sitemap'] && $values['meta_data/apply_noindex_meta_tag']);

			//Pinned status
			$version['pinned'] = $values['meta_data/pinned'];

			//Default values in case a content item is not pinned
			$version['pinned_duration'] =
			$version['unpin_date'] =
			$version['pinned_fixed_duration_unit'] = null;
			$version['pinned_fixed_duration_value'] = 0;

			if ($values['meta_data/pinned']) {
				$version['pinned_duration'] = $values['meta_data/pinned_duration'];

				switch ($values['meta_data/pinned_duration']) {
					case 'fixed_date':
						$version['unpin_date'] = $values['meta_data/unpin_date'];
						break;
					case 'fixed_duration':
						$version['pinned_fixed_duration_value'] = (int) $values['meta_data/pinned_fixed_duration_value'];
						$version['pinned_fixed_duration_unit'] = $values['meta_data/pinned_fixed_duration_unit'];
						
						//Work out the unpin date
						$newEndDate = new DateTime();
						//Work out if this is supposed to be singular day/week or plural days/weeks.
						if ($values['meta_data/pinned_fixed_duration_value'] > 1) {
							$unit = $values['meta_data/pinned_fixed_duration_unit'] . 's';
						} else {
							$unit = $values['meta_data/pinned_fixed_duration_unit'];
						}

						//Example: "+1 day". "+2 weeks" etc.
						$newEndDate->modify('+' . $values['meta_data/pinned_fixed_duration_value'] . ' ' . $unit);
						$version['unpin_date'] = $newEndDate->format('Y-m-d H:i:s');
						break;
					case 'until_unpinned':
						//Do nothing, the values are already set
						break;
				}
			}
	
			//Try and ensure that we use relative URLs where possible
			ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
			
			$version['content_summary'] = $values['meta_data/content_summary'];
	
			if (isset($fields['meta_data/lock_summary_edit_mode']) && !$fields['meta_data/lock_summary_edit_mode']['hidden']) {
				$version['lock_summary'] = (int) $values['meta_data/lock_summary_edit_mode'];
			}
		}

		//Set the Layout
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$newLayoutId = $values['meta_data/layout_id'];
		}
		
		
		//If the admin selected the duplicate option for any plugins, duplicate those plugins and put the copies in the slots
		//where the old ones were.
		if ($box['key']['duplicate'] || $box['key']['translate']) {
			$startAt = 1;
			for ($n = $startAt; (($suffix = '__'. $n) && (!empty($fields['plugins/instance_id'. $suffix]))); ++$n) {
				
				if ($values['plugins/action'. $suffix] == 'duplicate') {
					$newName = $values['plugins/new_name'. $suffix];
					$slotName = $values['plugins/slotname'. $suffix];
					$instanceId = $values['plugins/instance_id'. $suffix];
					$eggId = false;
					ze\pluginAdm::rename($instanceId, $eggId, $newName, $createNewInstance = true);
					ze\pluginAdm::updateItemSlot($instanceId, $slotName, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
				} elseif ($values['plugins/action'. $suffix] == 'empty') {
					
					$slotName = $values['plugins/slotname'. $suffix];
					$instanceId = '';
					$eggId = false;
					ze\pluginAdm::updateItemSlot($instanceId, $slotName, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
				}
			}
		}
		

		//Save the CSS and background
		if (ze\ring::engToBoolean($box['tabs']['css']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$version['css_class'] = $values['css/css_class'];
	
			//Only save background if "customise background" checkbox is ticked.
			if ($values['css/customise_background']) {
				if (($filepath = ze\file::getPathOfUploadInCacheDir($values['css/background_image']))
				 && ($imageId = ze\file::addToDatabase('background_image', $filepath, false, $mustBeAnImage = true))) {
					$version['bg_image_id'] = $imageId;
				} else {
					$version['bg_image_id'] = $values['css/background_image'];
				}
				
				$version['bg_color'] = $values['css/bg_color'];
				$version['bg_position'] = $values['css/bg_position']? $values['css/bg_position'] : null;
				$version['bg_repeat'] = $values['css/bg_repeat']? $values['css/bg_repeat'] : null;
			} else {
				$version['bg_image_id'] = $version['bg_color'] = '';
				$version['bg_position'] = $version['bg_repeat'] = null;
			}
	
			
		}

		//Save the chosen file, if a file was chosen
		if (ze\ring::engToBoolean($box['tabs']['file']['edit_mode']['on'] ?? false)) {
			if ($values['file/file']
			 && ($path = ze\file::getPathOfUploadInCacheDir($values['file/file']))
			 && ($filename = preg_replace('/([^.a-z0-9_\(\)\[\]]+)/i', '-', basename($path)))
			 && ($fileId = ze\file::addToDocstoreDir('content', $path, $filename))) {
				$version['file_id'] = $fileId;
				$version['filename'] = $filename;
			} else {
				$version['file_id'] = $values['file/file'];
			}

			if ($version['file_id']) {
				if ($box['key']['cType'] && ze::in($box['key']['cType'], 'audio', 'document', 'picture', 'video')) {
					//Editing a draft
					if (!$newDraftCreated && $box['key']['cVersion'] == $box['key']['source_cVersion']) {
						//Look up the file ID for the published version.
						//Then look up the file ID for the current draft.
						//Delete the file if no other content item uses it, but beware not to affect the published version.
						$currentPublishedVersion = ze\row::get('content_items', 'visitor_version', ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
						$currentPublishedFileId = ze\row::get('content_item_versions', 'file_id', ['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $currentPublishedVersion]);

						$currentDraftFileId = ze\row::get('content_item_versions', 'file_id', ['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']]);

						if ($currentDraftFileId != $currentPublishedFileId && $currentDraftFileId != $version['file_id']) {
								ze\file::deleteMediaContentItemFileIfUnused($box['key']['cID'], $box['key']['cType'], $currentDraftFileId);
						}
					}
				}
			}
			
			//To upload file on AWS s3 
			if ($values['file/s3_file_id']) {
				$version['s3_filename'] = $values['file/s3_file_name'];
				$version['s3_file_id'] = $values['file/s3_file_id'];
			} else {
				$version['s3_file_id'] = $values['file/s3_file_id'];
			}
			
		}
		
		$changes = !empty($version);

		//Update the layout
		if ($newLayoutId) {
			ze\layoutAdm::changeContentItemLayout($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $newLayoutId);
			$changes = true;
		}

		//Save the content tabs (up to four of them), for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$i = 0;
			$moduleIds = ze\module::id('zenario_wysiwyg_editor');
			$slots = ze\contentAdm::mainSlot($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $moduleIds, false, $values['meta_data/layout_id']);

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
			
					if (!empty($box['tabs']['content'. $i]['edit_mode']['on'])) {
						//Try and ensure that we use relative URLs where possible
						ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
						
						ze\contentAdm::saveContent($values['content'. $i. '/content'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $slot);
						$changes = true;
					}
				}
			}
		}
		
		//Save the content tabs (up to four of them), for each RAW HTML
		if (isset($box['tabs']['rawhtml1'])
		 && ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$i = 0;
			$moduleIds = ze\module::id('zenario_html_snippet');
			$slots = ze\contentAdm::mainSlot($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $moduleIds, false, $values['meta_data/layout_id']);

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
			
					if (!empty($box['tabs']['rawhtml'. $i]['edit_mode']['on'])) {
						//Try and ensure that we use relative URLs where possible
						ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['rawhtml'. $i]['fields']['content']);
						
						ze\contentAdm::saveContent($values['rawhtml'. $i. '/content'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $slot,'zenario_html_snippet');
						$changes = true;
					}
				}
			}
		}
		
		//Update the content_item_versions table
		if ($changes) {
			ze\contentAdm::updateVersion($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $version);
		}


		//Update item Categories
		if (empty($box['tabs']['categories']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['categories']['edit_mode']['on'] ?? false)
		 && isset($values['categories/categories'])
		 && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			ze\categoryAdm::setContentItemCategories($box['key']['cID'], $box['key']['cType'], ze\ray::explodeAndTrim($values['categories/categories']));
		}

		//Record and equivalence if this Content Item was duplicated into another Language
		$equivId = false;
		if ($box['key']['translate']) {
			$equivId = ze\contentAdm::recordEquivalence($box['key']['source_cID'], $box['key']['cID'], $box['key']['cType']);
		}

		if (isset($version['bg_image_id'])) {
			ze\contentAdm::deleteUnusedBackgroundImages();
		}
		
		//If changing the language of an existing content item, save it now.
		if ($values['meta_data/language_id_on_load'] != $values['meta_data/language_id']) {
			ze\row::set('content_items', ['language_id' => $values['meta_data/language_id']], ['id' => $box['key']['cID'], 'type' => $box['key']['cType']]);
			
			//If this content item's language gets changed to the site's default language,
			//the equiv ID of the entire chain will be changed to this content item's one.
			//Otherwise, nothing will happen.
			ze\contentAdm::resyncEquivalence($box['key']['cID'], $box['key']['cType']);
		}

		if ($version['file_id']) {
			if ($box['key']['cType'] && $box['key']['cType'] == 'document' && ze\module::inc('zenario_ctype_document')) {
				zenario_ctype_document::rescanExtract($box['key']['cType'] . '_' . $box['key']['cID']);
			}
		}
		
		//Save the menu.
		//Please note: If duplicating a content item which is attached to a menu node,
		//the code below will make sure the new menu node has the same privacy setting.
		$this->saveMenu($box, $fields, $values, $changes, $equivId);
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id_is_menu_node_id'] || $box['key']['id_is_parent_menu_node_id']) {
			$sectionId = isset($box['key']['target_menu_section']) ? $box['key']['target_menu_section'] : false;
			if ($menu = ze\menu::getFromContentItem($box['key']['cID'], $box['key']['cType'], $fetchSecondaries = false, $sectionId)) {
				$box['key']['id'] = $menu['id'];
			}
		}
		
		if (!array_key_exists("refinerName",$_GET)){
			if ($values['meta_data/alias']) {
				ze\tuix::closeWithFlags(['go_to_url' => $values['meta_data/alias']]);
				exit;
			}
		}
	}
	
	
	
	
	
	
	protected function fillMenu(&$box, &$fields, &$values, $contentType, $content, $version) {
		
		//Menu positions are in the format CONCAT(section_id, '_', menu_id, '_', child_options)
		//Possible options for "child_options" are:
		$beforeNode = 0;
		$underNode = 1;
		$underNodeAtStart = 2;	//N.b. this option is not supported by position pickers using Organizer Select, but supported by ze\menuAdm::addContentItems() when saving
		$defaultPos = '';
		
		//If a content item was set as the "from" or "source", attempt to get details of its primary menu node
		if ($box['key']['from_cID']) {
			$menu = ze\menu::getFromContentItem($box['key']['from_cID'], $box['key']['from_cType']);
			
			//Change the default to "after" if there's a known position
			$defaultPos = 'after';
		
		//Watch out for the "create a child" option from Organizer
		} elseif ($box['key']['target_menu_parent']) {
			$menu = ze\menu::details($box['key']['target_menu_parent']);
			$defaultPos = 'under';
		
		} elseif ($box['key']['target_menu']) {
			$menu = ze\menu::details($box['key']['target_menu']);
			$defaultPos = 'under';
		
		} else {
			$menu = false;
		}
		
		
		//Look for suggested menu nodes
		$suggestedPositions = [];
		if ($box['key']['cType'] != 'html') {
			foreach (ze\row::getAssocs('menu_nodes', ['id', 'section_id'], ['restrict_child_content_types' => $box['key']['cType']]) as $menuNode) {
				$mPath = ze\menuAdm::pathWithSection($menuNode['id'], true). ' › '. ze\admin::phrase('[ Create at the start ]');
				$mVal = $menuNode['section_id']. '_'. $menuNode['id']. '_'. $underNodeAtStart;
				
				$suggestedPositions[$mVal] = $mPath;
			}
		}
		$suggestionsExist = !empty($suggestedPositions);
		$suggestionsForced = $suggestionsExist && $contentType['menu_node_position_edit'] == 'force';
		

		//Don't show the option to add a menu node when editing an existing content item...
		if ($box['key']['cID']) {
			
			$fields['meta_data/create_menu_node']['hidden'] = true;
			unset($fields['meta_data/no_menu_warning']['indent']);
			$values['meta_data/create_menu_node'] = '';
			
			if (
			//...or if an Admin does not have the permissions to create a menu node...
				//(Though allow this through for restricted admins if they are forced to create a content item in one of the suggested places.)
			 ($box['key']['translate'] && !ze\priv::check('_PRIV_EDIT_MENU_TEXT'))
			 || (!$box['key']['translate'] && !$suggestionsForced && !ze\priv::check('_PRIV_ADD_MENU_ITEM'))
		
			//...or when translating a content item without a menu node.
			 || ($box['key']['translate'] && !$menu)
			 ) {
			
				$fields['meta_data/menu']['hidden'] = true;
			}
			
			if ($menu) {
				$values['meta_data/menu_content_status'] = $content['status'];
				//For top-level menu nodes, add a note to the "path" field to make it clear that it's
				//at the top level
				if ($menu['parent_id'] == 0) {
					$fields['meta_data/path_of__menu_text_when_editing']['label'] = ze\admin::phrase('Menu path preview (top level):');
					
				}
                //To show multilevel menu nodes "path"				
				$values['meta_data/menu_id_when_editing'] = $menu['mID'];
				$values['meta_data/menu_text_when_editing'] = $values['meta_data/menu_text_when_editing_on_load'] = $menu['name'];
				if ($menu['parent_id'] > 0) {
					$mPath = ze\menuAdm::pathWithSection($menu['id'], true);
					$mPath = str_replace("Main ›","",$mPath);
					$mpathArr = explode(' › ',$mPath);
					$parentPath = explode( '› '.$menu['name'] ,$mPath);
					//$parentNode= ze\row::getAssocs('menu_text', ['menu_id','name'], ['menu_id' => $menu['parent_id']]);
					if(is_array($mpathArr) && $mpathArr){
						$values['meta_data/parent_path_of__menu_text_when_editing'] = $values['meta_data/menu_text_when_editing_on_load']=$parentPath[0];
						$values['meta_data/path_of__menu_text_when_editing'] = $values['meta_data/menu_text_when_editing_on_load'] = $mPath." [level ".count($mpathArr)."]";
					}
				} else {
					$values['meta_data/path_of__menu_text_when_editing'] = $values['meta_data/menu_text_when_editing_on_load'] = $menu['name']." [level 1]";
				}
				$fields['meta_data/no_menu_warning']['hidden'] = true;
			} elseif (empty($contentType['prompt_to_create_a_menu_node'])) {
				$fields['meta_data/no_menu_warning']['hidden'] = true;
			}
		
		//If we're translating, add the ability to add the text but hide all of the options about setting a position
		} elseif ($box['key']['translate']) {
			$fields['meta_data/menu_pos'] =
			$fields['meta_data/menu_pos_suggested'] =
			$fields['meta_data/menu_pos_before'] =
			$fields['meta_data/menu_pos_under'] =
			$fields['meta_data/menu_pos_after'] =
			$fields['meta_data/menu_pos_specific']['hidden'] = true;
			$fields['meta_data/create_menu_node']['hidden'] = true;
			$values['meta_data/create_menu_node'] = 1;
		
		} else {
			if ($menu) {
				//Set the menu positions for before/after/under
				$values['meta_data/menu_pos_before'] = $menu['section_id']. '_'. $menu['id']. '_'. $beforeNode;
				$values['meta_data/menu_pos_under'] = $menu['section_id']. '_'. $menu['id']. '_'. $underNode;

				if ($box['key']['target_menu']) {
					$values['meta_data/menu_pos_after'] =
					$values['meta_data/menu_pos_specific'] = $menu['section_id']. '_'. $menu['id']. '_'. $underNode;
				} else {
					$values['meta_data/menu_pos_after'] =
					$values['meta_data/menu_pos_specific'] = $menu['section_id']. '_'. $menu['parent_id']. '_'. $underNode;
				}
			
				//That last line of code above will actually place the new menu node at the end of the current line.
				//If there's a menu node after the current one, then that's not technically the position after this one,
				//so we'll need to correct this.
				if ($nextNodeId = ze\sql::fetchValue('
					SELECT id
					FROM '. DB_PREFIX. 'menu_nodes
					WHERE section_id = '. (int) $menu['section_id']. '
					  AND parent_id = '. (int) $menu['parent_id']. '
					  AND ordinal > '. (int) $menu['ordinal']. '
					ORDER BY ordinal ASC
					LIMIT 1
				')) {
					$values['meta_data/menu_pos_after'] = $menu['section_id']. '_'. $nextNodeId. '_'. $beforeNode;
				}
				
				$values['meta_data/menu_pos'] = $defaultPos;
			
			} else {
				//Remove the before/under/after options if we didn't find them above
				unset($fields['meta_data/menu_pos']['values']['before']);
				unset($fields['meta_data/menu_pos']['values']['under']);
				unset($fields['meta_data/menu_pos']['values']['after']);
				
				//If we know the menu section we're aiming to create in, at least pre-populate that
				if ($box['key']['target_menu_section']) {
					$values['meta_data/menu_pos_specific'] = $box['key']['target_menu_section']. '_0_'. $underNode;
				}
				
				//Default the "create a menu node" checkbox to the value in the content type settings
				$values['meta_data/create_menu_node'] = $contentType['prompt_to_create_a_menu_node'] ?? 1;
			}
			
			if (empty($contentType['prompt_to_create_a_menu_node'])) {
				$fields['meta_data/no_menu_warning']['hidden'] = true;
			}
			
			//If there were some suggestions, default the radio-group to select them over the specific option
			if ($suggestionsExist) {
				$values['meta_data/menu_pos'] = 'suggested';
				$fields['meta_data/menu_pos_suggested']['values'] = $suggestedPositions;
				
				if (count($suggestedPositions) > 1) {
					$fields['meta_data/menu_pos']['values']['suggested']['label'] = ze\admin::phrase('Suggested positions');
				}
				
				//Lock down the choice to only suggestions, if this is enabled in the content type settings
				if ($suggestionsForced) {
					$fields['meta_data/menu_pos']['hidden'] =
					$fields['meta_data/menu_pos']['readonly'] = true;
					$fields['meta_data/menu_pos_locked_warning']['hidden'] = false;
					$fields['meta_data/menu_pos_suggested']['hide_with_previous_outdented_field'] = false;
				}

			} else {
				$values['meta_data/menu_pos'] = 'specific';
				unset($fields['meta_data/menu_pos']['values']['suggested']);
			}
		}
	}
		
		
		
	
	
	public function saveMenu(&$box, &$fields, &$values, $changes, $equivId) {

		if ($box['key']['cVersion'] == 1) {
		
			//If translating a content item with a menu node, add the translated menu text
			if ($box['key']['translate']) {
				if ($equivId
				 && $values['meta_data/create_menu_node']
				 && ze\priv::check('_PRIV_EDIT_MENU_TEXT')) {
		
					//Create copies of any Menu Node Text into this language
					$sql = "
						INSERT IGNORE INTO ". DB_PREFIX. "menu_text
							(menu_id, language_id, name, descriptive_text)
						SELECT menu_id, '". ze\escape::asciiInSQL($values['meta_data/language_id']). "', '". ze\escape::sql($values['meta_data/menu_text']). "', descriptive_text
						FROM ". DB_PREFIX. "menu_nodes AS mn
						INNER JOIN ". DB_PREFIX. "menu_text AS mt
						   ON mt.menu_id = mn.id
						  AND mt.language_id = '". ze\escape::asciiInSQL(ze\content::langId($box['key']['source_cID'], $box['key']['cType'])). "'
						WHERE mn.equiv_id = ". (int) $equivId. "
						  AND mn.content_type = '". ze\escape::asciiInSQL($box['key']['cType']). "'
						ORDER BY mn.id";
					ze\sql::update($sql);
				}
			
			//If creating a new content item, add a new menu node at the specified position
			} else {
				if ($values['meta_data/create_menu_node']
				 && ($values['meta_data/menu_pos'] == 'suggested' || ze\priv::check('_PRIV_ADD_MENU_ITEM'))) {
				
					$menuIds = [];
					switch ($values['meta_data/menu_pos']) {
						case 'suggested':
							$menuIds = ze\menuAdm::addContentItems($box['key']['id'], $values['meta_data/menu_pos_suggested']);
							break;
						case 'before':
							$menuIds = ze\menuAdm::addContentItems($box['key']['id'], $values['meta_data/menu_pos_before']);
							break;
						case 'after':
							$menuIds = ze\menuAdm::addContentItems($box['key']['id'], $values['meta_data/menu_pos_after']);
							break;
						case 'under':
							$menuIds = ze\menuAdm::addContentItems($box['key']['id'], $values['meta_data/menu_pos_under']);
							break;
						case 'specific':
							$menuIds = ze\menuAdm::addContentItems($box['key']['id'], $values['meta_data/menu_pos_specific']);
							break;
					}
				
					if ($menuId = array_shift($menuIds)) {
						ze\menuAdm::saveText($menuId, $values['meta_data/language_id'], ['name' => $values['meta_data/menu_text']]);

						if ($box['key']['duplicate']) {
							//If duplicating a content item which is attached to a menu node,
							//check its privacy setting, and copy to the new menu node.
							$currentMenu = ze\menu::getFromContentItem($box['key']['from_cID'], $box['key']['from_cType']);
							if (!empty($currentMenu)) {
								ze\row::set('menu_nodes', ['hide_private_item' => $currentMenu['hide_private_item']], ['id' => $menuId]);
							}
						}
					}
				}
			}
		}
		
		//If editing an existing content item, check if the admin has changed the menu node text. Update accordingly.
		if ($box['key']['id']) {
			if (
				$values['meta_data/menu_id_when_editing']
				&& $values['meta_data/menu_text_when_editing']
				&& $values['meta_data/menu_text_when_editing_on_load']
				&& $values['meta_data/menu_text_when_editing'] != $values['meta_data/menu_text_when_editing_on_load']
			) {
				ze\row::update('menu_text', ['name' => $values['meta_data/menu_text_when_editing'], 'language_id' => $values['meta_data/language_id']], ['menu_id' => $values['meta_data/menu_id_when_editing'], 'language_id' => $values['meta_data/language_id_on_load']]);
			}
		}
	}
}
