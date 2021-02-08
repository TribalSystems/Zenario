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
use Aws\S3\S3Client;

class zenario_common_features__admin_boxes__content extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
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
	
			} elseif ($box['key']['id'] && ($menuContentItem = ze\menu::getContentItem($box['key']['id']))) {
				//Edit an existing Content Item based on its Menu Node
				$box['key']['cID'] = $menuContentItem['equiv_id'];
				$box['key']['cType'] = $menuContentItem['content_type'];
				ze\content::langEquivalentItem($box['key']['cID'], $box['key']['cType'], ze::ifNull($box['key']['target_language_id'], ($_GET['languageId'] ?? false), ze::$defaultLang));
				$box['key']['source_cID'] = $box['key']['cID'];
		
				$box['key']['target_menu_section'] = ze\row::get('menu_nodes', 'section_id', $box['key']['id']);
		
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
			}
		}


		//If creating a new Content Item from the Content Items (and missing translations) in Language Panel,
		//or the Content Items in the Language X Panel, don't allow the language to be changed
		if (($_GET['refinerName'] ?? false) == 'language'
		 || (isset($_GET['refiner__language_equivs']) && ($_GET['refiner__language'] ?? false))) {
			$box['key']['target_language_id'] = $_GET['refiner__language'] ?? false;
		}
		
		
		//Only allow the language to be changed when duplicating or translating
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

		$contentType = ze\row::get('content_types', true, $box['key']['cType']);

		$content = $version = $status = $tag = false;
	
		//Specific Logic for Full Create
		//Try to load details on the source Content Item, if one is set
		if ($box['key']['source_cID']) {
			$content =
				ze\row::get(
					'content_items',
					['id', 'type', 'tag_id', 'language_id', 'equiv_id', 'alias', 'visitor_version', 'admin_version', 'status'],
					['id' => $box['key']['source_cID'], 'type' => $box['key']['cType']]);
		}


		if ($content) {
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
							ze\admin::phrase('Note: on this site, aliases are the same on all content items in a translation chain.');
					}
				}
				
				
				//Check to see if there are any library plugins on this page set at the item level
				$slots = [];
				ze\plugin::slotContents($slots, $box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'],
					$layoutId = false, $templateFamily = false, $templateFileBaseName = false,
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
					}
				}
				
				//If there are, show the plugins tab, with options for each one
				if ($numPlugins) {
					$box['tabs']['plugins']['hidden'] = false;
					
					$fields['plugins/desc']['snippet']['p'] =
						ze\admin::nphrase('There is 1 library plugin in use on this content item. Please select what you wish to do with this.',
							'There are [[count]] library plugins in use on this content item. Please select what you wish to do with them.',
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
				
				//
				$box['identifier']['css_class'] = ze\contentAdm::getItemIconClass($content['id'], $content['type'], true, $content['status']);
			}
	
			$values['meta_data/language_id'] = $content['language_id'];
	
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
				#$values['meta_data/in_sitemap'] = $version['in_sitemap'];
				$values['meta_data/exclude_from_sitemap'] = !$version['in_sitemap'];
				$values['css/css_class'] = $version['css_class'];
				$values['css/background_image'] = $version['bg_image_id'];
				$values['css/bg_color'] = $version['bg_color'];
				$values['css/bg_position'] = $version['bg_position'];
				$values['css/bg_repeat'] = $version['bg_repeat'];
				$values['file/file'] = $version['file_id'];
				$values['file/s3_file_id'] = $version['s3_file_id'];
				$values['file/s3_file_name'] = $version['s3_filename'];
				
				
		
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
		} else {
			//If we are enforcing a specific Content Type, ensure that only layouts of that type can be picked
			if ($box['key']['target_cType']) {
				$fields['meta_data/layout_id']['pick_items']['path'] =
					'zenario__layouts/panels/layouts/refiners/content_type//'. $box['key']['target_cType']. '//';
				
				
				//T10208, Creating content items: auto-populate release date and author where used
				$contentTypeDetails = ze\contentAdm::cTypeDetails($box['key']['target_cType']);

				if ($contentTypeDetails['writer_field'] != 'hidden'
				 && isset($fields['meta_data/writer_id'])
				 && ($adminDetails = ze\admin::details(ze\admin::id()))) {
					$values['meta_data/writer_id'] = ze\admin::id();
					$values['meta_data/writer_name'] = $adminDetails['first_name']. ' '. $adminDetails['last_name'];
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
			//Require _PRIV_VIEW_CONTENT_ITEM_SETTINGS for viewing an existing content item's settings
			ze\priv::exitIfNot('_PRIV_VIEW_CONTENT_ITEM_SETTINGS');

		} elseif ($box['key']['translate']) {
			//Require _PRIV_CREATE_TRANSLATION_FIRST_DRAFT for creating a translation
			if (!ze\priv::onLanguage('_PRIV_CREATE_TRANSLATION_FIRST_DRAFT', $box['key']['target_language_id'])) {
				exit;
			}

		} else {
			//Otherwise require _PRIV_CREATE_FIRST_DRAFT for creating a new content item
			ze\priv::exitIfNot('_PRIV_CREATE_FIRST_DRAFT', false, $box['key']['cType']);
		}



		//Set default values
		if ($content) {
			if ($box['key']['duplicate'] || $box['key']['translate']) {
				$values['meta_data/language_id'] = ze::ifNull($box['key']['target_language_id'], ze::ifNull($_GET['languageId'] ?? false, ($_GET['language'] ?? false), $content['language_id']));
			}
		} else {
			$values['meta_data/language_id'] = ze::ifNull($box['key']['target_language_id'], ($_GET['languageId'] ?? false), ze::$defaultLang);
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
				$layoutId = $contentType['default_layout_id'];
			}
			$contentType = ze\row::get('content_types', true, $box['key']['cType']);
			
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

		//Don't let the language be changed if this Content Item already exists, or will be placed in a set language for the menu
		if (!$box['key']['duplicate'] && ($content || $box['key']['target_menu_section'])) {
			$lockLanguageId = true;
		}

		if (isset($box['tabs']['categories']['fields']['desc'])) {
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				ze\admin::phrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					['link' => ' href="'. htmlspecialchars(ze\link::absolute(). 'zenario/admin/organizer.php#zenario__content/categories'). '" target="_blank"']);
		
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
					$tab['edit_mode']['always_on'] = true;
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

		if ($lockLanguageId) {
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
						WHERE v.tag_id = '" . ze\escape::sql($box['key']['id']) . "'
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
			//To show warning message for sheduled datetime content item in FAB
			$checkIfPublishsql = "SELECT id,scheduled_publish_datetime from ". DB_PREFIX. "content_item_versions 
					WHERE id IN (". (int) $box['key']['cID']. ")
			  AND scheduled_publish_datetime IS NOT NULL
			  AND published_datetime IS NULL AND publisher_id=0" ;
				$checkIfPublish = ze\sql::select($checkIfPublishsql);
				$getresult = ze\sql::fetchAssoc($checkIfPublish);

				if($getresult && $checkIfPublish)
				{
					if(sizeof($getresult)>0 )
					{
						$box['tabs']['meta_data']['notices']['scheduled_warning']['show'] = true;
						$box['tabs']['meta_data']['notices']['scheduled_warning']['message'] = "This item is scheduled to be published at " .ze\admin::formatDateTime($getresult['scheduled_publish_datetime'],'vis_date_format_med').".";
					}
				}
		
		}
		if (ze::setting('aws_s3_support')) {
			$fields['file/file']['label']= 'Local file:';
			$fields['file/s3_file_upload']['hidden']= false;
			$maxUploadSize = ze\file::fileSizeConvert(ze\dbAdm::apacheMaxFilesize());
			if (ze\dbAdm::apacheMaxFilesize() < 5368706371) {
				$maxS3UploadSize = $maxUploadSize;
			} else {
				$maxS3UploadSize = ze\file::fileSizeConvert(5368706371);
			}
			
			$box['tabs']['file']['fields']['document_desc']['snippet']['html'] = ze\admin::phrase('Please upload a local file (for storage in Zenario\'s docstore), maximum size [[maxUploadSize]].',['maxUploadSize' => $maxUploadSize]); 
			
			$box['tabs']['file']['fields']['s3_document_desc']['snippet']['html'] = ze\admin::phrase('You can upload a related file for storage on AWS S3, maximum size [[maxS3UploadSize]].',['maxS3UploadSize' => $maxS3UploadSize]);
		}
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$box['tabs']['file']['hidden'] = true;
		
		if (ze::setting('aws_s3_support')) {
			
			if($values['file/s3_file_remove']) {
				
				$s3_file_upload = "<iframe id=\"s3_file_upload\" name=\"s3_file_upload\" src=\"" .ze\link::protocol(). \ze\link::host(). SUBDIRECTORY.'zenario/s3FileUpload.php?cId='. $box['key']['cID'] .'&cType='. $box['key']['cType']. '&cVersion='. $box['key']['source_cVersion']."&remove=1\" style=\"border: none;\"></iframe>\n";
			
			} else {
				$s3_file_upload = "<iframe id=\"s3_file_upload\" name=\"s3_file_upload\" src=\"" .ze\link::protocol(). \ze\link::host(). SUBDIRECTORY.'zenario/s3FileUpload.php?cId='. $box['key']['cID'] .'&cType='. $box['key']['cType']. '&cVersion='. $box['key']['source_cVersion']."\" style=\"border: none;\"></iframe>\n";
			}
			
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
				$fields['meta_data/writer_name']['hidden'] = true;
			}
			if ($details['summary_field'] == 'hidden') {
				$fields['meta_data/content_summary']['hidden'] = true;
			}
		}

		if (isset($box['tabs']['meta_data']['fields']['writer_id'])
		 && !ze\ring::engToBoolean($box['tabs']['meta_data']['fields']['writer_id']['hidden'] ?? false)) {
			if ($values['meta_data/writer_id']) {
				if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
					if (empty($box['tabs']['meta_data']['fields']['writer_name']['current_value'])
					 || empty($box['tabs']['meta_data']['fields']['writer_id']['last_value'])
					 || $box['tabs']['meta_data']['fields']['writer_id']['last_value'] != $values['meta_data/writer_id']) {
						$adminDetails = ze\admin::details($values['meta_data/writer_id']);
						$box['tabs']['meta_data']['fields']['writer_name']['current_value'] = $adminDetails['first_name'] . " " . $adminDetails['last_name'];
					}
				}
		
				$fields['meta_data/writer_name']['hidden'] = false;
			} else {
				$fields['meta_data/writer_name']['hidden'] = true;
				$box['tabs']['meta_data']['fields']['writer_name']['current_value'] = "";
			}
	
			$box['tabs']['meta_data']['fields']['writer_id']['last_value'] = $values['meta_data/writer_id'];
		}


		if ($box['key']['cID']) {
			$languageId = ze\content::langId($box['key']['cID'], $box['key']['cType']);
			$specialPage = ze\content::isSpecialPage($box['key']['cID'], $box['key']['cType']);
		} else {
			$languageId = ($values['meta_data/language_id'] ?: ($box['key']['target_template_id'] ?: ze::$defaultLang));
			$specialPage = false;
		}

		$titleCounterHTML = '
			<div class="snippet__title" >
				<div id="snippet__title_length" class="[[initial_class_name]]">
					<span id="snippet__title_counter">[[initial_characters_count]]</span>
				</div>
			</div>';

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


	
		if (strlen($values['meta_data/title'])<1) {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_red', $titleCounterHTML);
			$fields['meta_data/title']['note_below'] = 'Please enter a title.';
		} elseif (strlen($values['meta_data/title'])<20)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_orange', $titleCounterHTML);
			$fields['meta_data/title']['note_below'] = 'For good SEO, make the title longer.';
		} elseif (strlen($values['meta_data/title'])<40)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
			$fields['meta_data/title']['note_below'] = 'For good SEO, make the title a little longer.';
		} elseif (strlen($values['meta_data/title'])<66)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_green', $titleCounterHTML);
			$fields['meta_data/title']['note_below'] = 'This is a good title length for SEO.';
		} else {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
			$fields['meta_data/title']['note_below'] = 'The title is a little long for good SEO as it may not be fully visible.';
		}
		$titleCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/title']), $titleCounterHTML);
		$box['tabs']['meta_data']['fields']['title']['post_field_html'] = $titleCounterHTML;


		if (strlen($values['meta_data/description'])<1) {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_red', $descriptionCounterHTML);
			$fields['meta_data/description']['note_below'] = 'For good SEO, enter a description. If this field is left blank, search engines will autogenerate descriptions which may not always be accurate.';
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
					ze\contentAdm::addAbsURLsToAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
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
					ze\contentAdm::addAbsURLsToAdminBoxField($box['tabs']['rawhtml'. $i]['fields']['content']);
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
			ze\contentAdm::addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
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
		
		if (isset($box['key']['id'])) {
			$fields['meta_data/suggest_alias_from_title']['hidden'] = true;
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id']);
			$equivId = ze\content::equivId($cID, $cType);
			$contentItemPrivacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $equivId]);
			
			if ($contentItemPrivacy != 'public') {
				unset($fields['meta_data/title']['post_field_html']);
				unset($fields['meta_data/title']['note_below']);
				unset($fields['meta_data/description']['post_field_html']);
				unset($fields['meta_data/description']['note_below']);
			}
		}
		if (!$values['meta_data/alias_changed']) {
			$fields['meta_data/suggest_alias_from_title']['style'] = 'display:none';
		} else {
			$fields['meta_data/suggest_alias_from_title']['style'] = '';
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
						$values['content1/content'] = '<h1>'. htmlspecialchars($values['meta_data/title']). '</h1>';
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

		if (ze\ray::issetArrayKey($values,'meta_data/writer_id') && !ze\ray::issetArrayKey($values,'meta_data/writer_name')) {
			$fields['meta_data/writer_name']['error'] = ze\admin::phrase('Please enter a writer name.');
		}

		if ($box['key']['translate']) {
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
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['cID'] && !ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			exit;
		}
		
		$isNewContentItem = !$box['key']['cID'];
		
		//Create a new Content Item, or a new Draft of a Content Item, as needed.
		$newDraftCreated = ze\contentAdm::createDraft($box['key']['cID'], $box['key']['source_cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['source_cVersion'], $values['meta_data/language_id']);
		$forceMarkAsEditsMade = $newDraftCreated;

		if (!$box['key']['cID']) {
			exit;
		} else {
			$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
		}

		$version = [];
		$newLayoutId = false;
		
		//If we're creating a new content item in the front-end, try to start off in Edit mode
		if ($isNewContentItem && !$box['key']['create_from_content_panel']) {
			$_SESSION['page_toolbar'] = 'edit';
			$_SESSION['page_mode'] = 'edit';
			$_SESSION['last_item'] = $box['key']['cType'].  '_'. $box['key']['cID'];
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
			$version['writer_name'] = $values['meta_data/writer_name'];
			#$version['in_sitemap'] = $values['meta_data/in_sitemap'];
			$version['in_sitemap'] = !$values['meta_data/exclude_from_sitemap'];
	
			ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
			$version['content_summary'] = $values['meta_data/content_summary'];
	
			if (isset($fields['meta_data/lock_summary_edit_mode']) && !$fields['meta_data/lock_summary_edit_mode']['hidden']) {
				$version['lock_summary'] = (int) $values['meta_data/lock_summary_edit_mode'];
			}
		}

		//Set the Layout
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $box['key']['cID'], $box['key']['cType'])) {
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
				}
			}
		}
		

		//Save the CSS and background
		if (ze\ring::engToBoolean($box['tabs']['css']['edit_mode']['on'] ?? false)
		 && ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $box['key']['cID'], $box['key']['cType'])) {
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
			 && ($filename = preg_replace('/([^.a-z0-9]+)/i', '_', basename($path)))
			 && ($fileId = ze\file::addToDocstoreDir('content', $path, $filename))) {
				$version['file_id'] = $fileId;
				$version['filename'] = $filename;
			} else {
				$version['file_id'] = $values['file/file'];
			}
			//To upload file on AWS s3 
			if ($values['file/s3_file_id']) {
					$version['s3_filename'] = $values['file/s3_file_name'];
					$version['s3_file_id'] = $values['file/s3_file_id'];
				} 
				else {
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
						ze\contentAdm::stripAbsURLsFromAdminBoxField($box['tabs']['rawhtml'. $i]['fields']['content']);
						ze\contentAdm::saveContent($values['rawhtml'. $i. '/content'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $slot,'zenario_html_snippet');
						$changes = true;
					}
				}
			}
		}
		
		//Update the content_item_versions table
		if ($changes) {
			ze\contentAdm::updateVersion($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $version, $forceMarkAsEditsMade);
		}


		//Update item Categories
		if (empty($box['tabs']['categories']['hidden'])
		 && ze\ring::engToBoolean($box['tabs']['categories']['edit_mode']['on'] ?? false)
		 && isset($values['categories/categories'])
		 && ze\priv::check('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES')) {
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
		
		
		$this->saveMenu($box, $fields, $values, $changes, $equivId);
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id_is_menu_node_id'] || $box['key']['id_is_parent_menu_node_id']) {
			$sectionId = isset($box['key']['target_menu_section']) ? $box['key']['target_menu_section'] : false;
			if ($menu = ze\menu::getFromContentItem($box['key']['cID'], $box['key']['cType'], $fetchSecondaries = false, $sectionId)) {
				$box['key']['id'] = $menu['id'];
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
					$fields['meta_data/path_of__menu_text_when_editing']['label'] = ze\admin::phrase('Path preview (top level):');
					
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
				}
				else
				{
					$values['meta_data/path_of__menu_text_when_editing'] = $values['meta_data/menu_text_when_editing_on_load'] = $menu['name']." [level 1]";
				}
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
				$values['meta_data/menu_pos_after'] =
				$values['meta_data/menu_pos_specific'] = $menu['section_id']. '_'. $menu['parent_id']. '_'. $underNode;
			
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
						SELECT menu_id, '". ze\escape::sql($values['meta_data/language_id']). "', '". ze\escape::sql($values['meta_data/menu_text']). "', descriptive_text
						FROM ". DB_PREFIX. "menu_nodes AS mn
						INNER JOIN ". DB_PREFIX. "menu_text AS mt
						   ON mt.menu_id = mn.id
						  AND mt.language_id = '". ze\escape::sql(ze\content::langId($box['key']['source_cID'], $box['key']['cType'])). "'
						WHERE mn.equiv_id = ". (int) $equivId. "
						  AND mn.content_type = '". ze\escape::sql($box['key']['cType']). "'
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
				ze\row::update('menu_text', ['name' => $values['meta_data/menu_text_when_editing']], ['menu_id' => $values['meta_data/menu_id_when_editing'], 'language_id' => $values['meta_data/language_id']]);
			}
		}
	}
}
