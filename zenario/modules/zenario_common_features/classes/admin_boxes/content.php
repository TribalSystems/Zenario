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


class zenario_common_features__admin_boxes__content extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//Include an option to create a Menu Node and/or Content Item as a new child of an existing menu Node
		if ($box['key']['id_is_menu_node_id'] || $box['key']['id_is_parent_menu_node_id']) {
	
			if ($box['key']['id'] && $box['key']['id_is_parent_menu_node_id']) {
				//Create a new Content Item/Menu Node under an existing one
				$box['key']['target_menu_parent'] = $box['key']['id'];
		
				$box['key']['target_menu_section'] = getRow('menu_nodes', 'section_id', $box['key']['id']);
	
			} elseif ($box['key']['id'] && ($menuContentItem = getContentFromMenu($box['key']['id']))) {
				//Edit an existing Content Item based on its Menu Node
				$box['key']['cID'] = $menuContentItem['equiv_id'];
				$box['key']['cType'] = $menuContentItem['content_type'];
				langEquivalentItem($box['key']['cID'], $box['key']['cType'], ifNull($box['key']['target_language_id'], get('languageId'), setting('default_language')));
				$box['key']['source_cID'] = $box['key']['cID'];
		
				$box['key']['target_menu_section'] = getRow('menu_nodes', 'section_id', $box['key']['id']);
		
			} else {
				$box['key']['target_menu_section'] = ifNull($box['key']['target_menu_section'], request('sectionId'), request('refiner__section'));
			}
			$box['key']['id'] = false;
		}

		if ($path == 'zenario_content') {
			//Include the option to duplicate a Content Item based on a MenuId
			$values['meta_data/domain_and_subdir_container'] = httpOrHttps() . httpHost() . SUBDIRECTORY;
	
			if ($box['key']['duplicate_from_menu']) {
				//Handle the case where a language id is in the primary key
				if ($box['key']['id'] && !is_numeric($box['key']['id']) && get('refiner__menu_node_translations')) {
					$box['key']['target_language_id'] = $box['key']['id'];
					$box['key']['id'] = get('refiner__menu_node_translations');
		
				} elseif (is_numeric($box['key']['id']) && get('refiner__language')) {
					$box['key']['target_language_id'] = get('refiner__language');
				}
		
				if ($menuContentItem = getContentFromMenu($box['key']['id'])) {
					$box['key']['source_cID'] = $menuContentItem['equiv_id'];
					$box['key']['cType'] = $menuContentItem['content_type'];
					$box['key']['id'] = false;
		
				} else {
					echo adminPhrase('No content item was found for this menu node');
					exit;
				}
	
			//Include the option to duplicate to create a ghost in an Translation Chain,
			//and handle the case where a language id is in the primary key
			} else
			//Version for opening from the "translation chain" panel in Organizer:
			if (
				$box['key']['translate']
			 && request('refinerName') == 'zenario_trans__chained_in_link'
			 && !getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], $box['key']['id'])
			 && getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], request('refiner__zenario_trans__chained_in_link'))
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['id'] = null;
			} else
			//Version for opening from the "translation chain" panel in the menu area in Organizer:
			if (
				$box['key']['translate']
			 && request('refinerName') == 'zenario_trans__chained_in_link__from_menu_node'
			 && request('equivId')
			 && request('cType')
			 && request('language')
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['source_cID'] = request('equivId');
				$box['key']['id'] = null;
			} else
			//Version for opening from the Admin Toolbar
			if (
				$box['key']['translate']
			 && request('cID') && request('cType')
			 && !getCIDAndCTypeFromTagId($box['key']['source_cID'], $box['key']['cType'], $box['key']['id'])
			) {
				$box['key']['target_language_id'] = $box['key']['id'];
				$box['key']['id'] = null;
				$box['key']['source_cID'] = request('cID');
				$box['key']['cType'] = request('cType');
				$box['key']['cID'] = '';
			}
		}


		//If creating a new Content Item from the Content Items (and missing translations) in Language Panel,
		//or the Content Items in the Language X Panel, don't allow the language to be changed
		if (get('refinerName') == 'language'
		 || (isset($_GET['refiner__language_equivs']) && get('refiner__language'))) {
			$box['key']['target_language_id'] = get('refiner__language');
		}
		
		
		//Only allow the language to be changed when duplicating or translating
		$lockLanguageId = false;
		if ($box['key']['target_language_id'] || $box['key']['duplicate'] || $box['key']['translate']) {
			$lockLanguageId = true;
		}

		//Populate the language select list
		getLanguageSelectListOptions($fields['meta_data/language_id']);

		//Set up the primary key from the requests given
		if ($box['key']['id'] && !$box['key']['cID']) {
			getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']);

		} elseif (!$box['key']['id'] && $box['key']['cID'] && $box['key']['cType']) {
			$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
		}

		if ($box['key']['cID'] && !$box['key']['cVersion']) {
			$box['key']['cVersion'] = getLatestVersion($box['key']['cID'], $box['key']['cType']);
		}

		if ($box['key']['cID'] && !$box['key']['source_cID']) {
			$box['key']['source_cID'] = $box['key']['cID'];
			$box['key']['source_cVersion'] = $box['key']['cVersion'];

		} elseif ($box['key']['source_cID'] && !$box['key']['source_cVersion']) {
			$box['key']['source_cVersion'] = getLatestVersion($box['key']['source_cID'], $box['key']['cType']);
		}

		//If we're duplicating a Content Item, check to see if it has a Menu Node as well
		if ($box['key']['duplicate'] || $box['key']['translate']) {
			$box['key']['cID'] = $box['key']['cVersion'] = false;
	
			if ($menu = getMenuItemFromContent($box['key']['source_cID'], $box['key']['cType'])) {
				$box['key']['target_menu_parent'] = $menu['parent_id'];
				$box['key']['target_menu_section'] = $menu['section_id'];
			}
		}

		//Enforce a specific Content Type
		if (request('refiner__content_type')) {
			$box['key']['target_cType'] = request('refiner__content_type');
		}
		
		if (!empty($box['key']['create_from_toolbar'])) {
			$fields['meta_data/language_id']['disabled'] = true;
		}
		
		//Remove the ability to create a Menu Node if location information for the menu has not been provided
		if (!$box['key']['target_menu_section']) {
			$fields['meta_data/create_menu']['hidden'] = true;
	
			if ($path == 'zenario_quick_create') {
				$fields['meta_data/more_options_menu']['hidden'] = true;
				$fields['meta_data/more_options_button_menu']['hidden'] = true;
			}

		} elseif ($box['key']['target_menu_parent']) {
			$values['meta_data/menu_parent_path'] = getMenuPath($box['key']['target_menu_parent'], ifNull($box['key']['target_language_id'], get('languageId'), get('language')));
		}

		$contentType = getRow('content_types', true, $box['key']['cType']);

		$content = $version = $status = $tag = false;
		if ($path == 'zenario_quick_create') {
			//Specific Logic to Quick Create
	
			$box['key']['id'] = $box['key']['cID'] = $box['key']['cVersion'] = $box['key']['source_cID'] = $box['key']['source_cVersion'] = false;
			$box['key']['cType'] = 'html';

		} else {
			//Specific Logic for Full Create
	
			//Try to load details on the source Content Item, if one is set
			if ($box['key']['source_cID']) {
				$content =
					getRow(
						'content_items',
						array('id', 'type', 'tag_id', 'language_id', 'alias', 'visitor_version', 'admin_version', 'status'),
						array('id' => $box['key']['source_cID'], 'type' => $box['key']['cType']));
			}
	
	
			if ($content) {
				if ($box['key']['duplicate'] || $box['key']['translate']) {
					//If duplicating, check for a Menu Node
					if ((($box['key']['translate'] && checkPriv('_PRIV_EDIT_MENU_TEXT')) || (!$box['key']['translate'] && checkPriv('_PRIV_ADD_MENU_ITEM')))
					 && ($currentMenu = getMenuItemFromContent($box['key']['source_cID'], $box['key']['cType']))
			
					//When duplicating to a new/different language, if Menu Text already exists in the new Language,
					//but a Content Item does not, rely on the existing Menu Text and don't offer to create a new one
					 && (!($lang = ifNull($box['key']['target_language_id'], get('languageId'), get('language')))
					  || ($lang == $content['language_id'])
					  || !(checkRowExists('menu_text', array('menu_id' => $currentMenu['id'], 'language_id' => $lang))))) {
						
						$box['key']['target_menu_section'] = $currentMenu['section_id'];
						$values['meta_data/menu_title'] = $currentMenu['name'];
						$values['meta_data/menu_path'] = getMenuPath($currentMenu['parent_id'], $lang);
						$values['meta_data/create_menu'] = 1;

						
					} else {
						$values['meta_data/create_menu'] = '';
						$fields['meta_data/create_menu']['hidden'] = true;
						$box['key']['target_menu_section'] = null;
						$box['key']['target_menu_parent'] = null;
					}
					
					if ($box['key']['translate']) {
						$values['meta_data/alias'] = $content['alias'];
						$fields['meta_data/create_menu']['hidden'] = true;
						$box['tabs']['categories']['hidden'] = true;
						$box['tabs']['privacy']['hidden'] = true;
			
						if (!setting('translations_different_aliases')) {
							$fields['meta_data/alias']['read_only'] = true;
							unset($box['tabs']['meta_data']['fields']['alias']['note_below']);
						}
					}
		
				} else {
					//The options to set the alias, menu text, categories or privacy (if it is there!) should be hidden when not creating something
					$fields['meta_data/alias']['hidden'] = true;
					$fields['meta_data/create_menu']['hidden'] = true;
					$values['meta_data/create_menu'] = '';
					$box['tabs']['categories']['hidden'] = true;
					$box['tabs']['privacy']['hidden'] = true;
					$box['key']['target_menu_section'] = null;
					$box['key']['target_menu_parent'] = null;
					
					$box['identifier']['css_class'] = getItemIconClass($content['id'], $content['type'], true, $content['status']);
				}
		
				$values['meta_data/language_id'] = $content['language_id'];
		
				$fields['meta_data/layout_id']['pick_items']['path'] = 
					'zenario__layouts/panels/layouts/refiners/content_type//' . $content['type']. '//';
		
				if ($version =
					getRow(
						'content_item_versions',
						true,
						array('id' => $box['key']['source_cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['source_cVersion']))
				) {
					$values['meta_data/title'] = $version['title'];
					$values['meta_data/description'] = $version['description'];
					$values['meta_data/keywords'] = $version['keywords'];
					$values['meta_data/publication_date'] = $version['publication_date'];
					$values['meta_data/writer_id'] = $version['writer_id'];
					$values['meta_data/writer_name'] = $version['writer_name'];
					$values['meta_data/content_summary'] = $version['content_summary'];
					$values['meta_data/layout_id'] = $version['layout_id'];
					$values['css/css_class'] = $version['css_class'];
					$values['css/background_image'] = $version['bg_image_id'];
					$values['css/bg_color'] = $version['bg_color'];
					$values['css/bg_position'] = $version['bg_position'];
					$values['css/bg_repeat'] = $version['bg_repeat'];
					$values['file/file'] = $version['file_id'];
			
					if ($box['key']['cID'] && $contentType['enable_summary_auto_update']) {
						$values['meta_data/lock_summary_view_mode'] =
						$values['meta_data/lock_summary_edit_mode'] = $version['lock_summary'];
						$fields['meta_data/lock_summary_view_mode']['hidden'] =
						$fields['meta_data/lock_summary_edit_mode']['hidden'] = false;
					}
			
					if (isset($box['tabs']['categories']['fields']['categories'])) {
						setupCategoryCheckboxes(
							$fields['categories/categories'], true,
							$box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion']);
					}
			
					$tag = formatTag($box['key']['source_cID'], $box['key']['cType'], arrayKey($content, 'alias'));
			
					$status = adminPhrase('archived');
					if ($box['key']['source_cVersion'] == $content['visitor_version']) {
						$status = adminPhrase('published');
			
					} elseif ($box['key']['source_cVersion'] == $content['admin_version']) {
						if ($content['admin_version'] > $content['visitor_version']) {
							$status = adminPhrase('draft');
						} elseif ($content['status'] == 'hidden' || $content['status'] == 'hidden_with_draft') {
							$status = adminPhrase('hidden');
						} elseif ($content['status'] == 'trashed' || $content['status'] == 'trashed_with_draft') {
							$status = adminPhrase('trashed');
						}
					}
				}
			} else {
				//If we are enforcing a specific Content Type, ensure that only layouts of that type can be picked
				if ($box['key']['target_cType']) {
					$fields['meta_data/layout_id']['pick_items']['path'] =
						'zenario__layouts/panels/layouts/refiners/content_type//'. $box['key']['target_cType']. '//';
					
					
					//T10208, Creating content items: auto-populate release date and author where used
					$contentTypeDetails = getContentTypeDetails($box['key']['target_cType']);
	
					if ($contentTypeDetails['writer_field'] != 'hidden'
					 && isset($fields['meta_data/writer_id'])
					 && ($adminDetails = getAdminDetails(adminId()))) {
						$values['meta_data/writer_id'] = adminId();
						$values['meta_data/writer_name'] = $adminDetails['first_name']. ' '. $adminDetails['last_name'];
					}
	
					if ($contentTypeDetails['release_date_field'] != 'hidden'
					 && isset($fields['meta_data/publication_date'])) {
						$values['meta_data/publication_date'] = dateNow();
					}
				}
			}
		}


		//We should have loaded or found the cID by now, if this was for editing an existing content item.
		//If there's no cID then we're creating a new content item
		if ($box['key']['cID']) {
			//Require _PRIV_VIEW_CONTENT_ITEM_SETTINGS for viewing an existing content item's settings
			exitIfNotCheckPriv('_PRIV_VIEW_CONTENT_ITEM_SETTINGS');

		} elseif ($box['key']['translate']) {
			//Require _PRIV_CREATE_TRANSLATION_FIRST_DRAFT for creating a translation
			if (!checkPrivForLanguage('_PRIV_CREATE_TRANSLATION_FIRST_DRAFT', $box['key']['target_language_id'])) {
				exit;
			}

		} else {
			//Otherwise require _PRIV_CREATE_FIRST_DRAFT for creating a new content item
			exitIfNotCheckPriv('_PRIV_CREATE_FIRST_DRAFT');
		}



		//Set default values
		if ($content) {
			if ($box['key']['duplicate'] || $box['key']['translate']) {
				$values['meta_data/language_id'] = ifNull($box['key']['target_language_id'], ifNull(get('languageId'), get('language'), $content['language_id']));
			}
		} else {
			$values['meta_data/language_id'] = ifNull($box['key']['target_language_id'], get('languageId'), setting('default_language'));
		}
		
		if (!$version) {
			//Attempt to work out the default template and Content Type for a new Content Item
			if (($layoutId = ifNull($box['key']['target_template_id'], get('refiner__template')))
			 && ($box['key']['cType'] = getRow('layouts', 'content_type', $layoutId))) {
		
				$contentType = getRow('content_types', true, $box['key']['cType']);
	
			} elseif ($box['key']['target_menu_parent']
				   && ($cItem = getRow('menu_nodes', array('equiv_id', 'content_type'), array('id' => $box['key']['target_menu_parent'], 'target_loc' => 'int')))
				   && ($cItem['content_type'] == 'html' || $path != 'zenario_quick_create')
				   && ($cItem['admin_version'] = getRow('content_items', 'admin_version', array('id' => $cItem['equiv_id'], 'type' => $cItem['content_type'])))
				   && ($layoutId = contentItemTemplateId($cItem['equiv_id'], $cItem['content_type'], $cItem['admin_version']))) {
		
				$box['key']['cType'] = $cItem['content_type'];
				$contentType = getRow('content_types', true, $box['key']['cType']);
	
			} else {
				if ($path == 'zenario_quick_create') {
					$box['key']['cType'] = 'html';
				} else {
					$box['key']['cType'] = ifNull($box['key']['target_cType'], $box['key']['cType'], 'html');
				}
		
				$contentType = getRow('content_types', true, $box['key']['cType']);
				$layoutId = $contentType['default_layout_id'];
			}
			
			$values['meta_data/layout_id'] = $layoutId;
			
			// Load content type default menu options
			if ($contentType['default_parent_menu_node']) {
				
				$values['meta_data/menu_options'] = 'add_to_menu';
				
				$menuNode = getRow('menu_nodes', array('id', 'section_id'), $contentType['default_parent_menu_node']);
				$dummyNode = 1;
				$menuNodeId = $menuNode['id'];
				if ($contentType['menu_node_position'] == 'start') {
					
					// Get first child menu node
					$sql = '
						SELECT id
						FROM ' . DB_NAME_PREFIX . 'menu_nodes
						WHERE parent_id = ' . (int)$menuNode['id'] . '
						ORDER BY ordinal
						LIMIT 1';
					$result = sqlSelect($sql);
					$row = sqlFetchRow($result);
					
					if ($row[0]) {
						$menuNodeId = $row[0];
						$dummyNode = 0;
					}
				}
				$values['meta_data/add_to_menu'] = $menuNode['section_id'] . '_' . $menuNodeId . '_' . $dummyNode;
				
				if ($contentType['menu_node_position_edit'] == 'force') {
					$fields['meta_data/warning_message']['snippet']['html'] = adminPhrase('The initial menu position for content items of this type has been locked.');
					$fields['meta_data/warning_message']['hidden'] = false;
					$fields['meta_data/menu_options']['disabled'] = true;
					$fields['meta_data/add_to_menu']['pick_items']['hide_select_button'] = true;
					$fields['meta_data/add_to_menu']['pick_items']['hide_remove_button'] = true;
				}
			
			// If no default menu node position try to calculate it from the current pages menu section
			} elseif (!empty($box['key']['create_from_toolbar'])) {
				
				$currentMenuNode = getMenuItemFromContent($box['key']['from_cID'], $box['key']['from_cType']);
				if ($currentMenuNode) {
					$values['meta_data/menu_options'] = 'add_to_menu';
					
					$menuNodeId = 0;
					if ($currentMenuNode['parent_id']) {
						$menuNodeId = $currentMenuNode['parent_id'];
					}
					
					$values['meta_data/add_to_menu'] = $currentMenuNode['section_id'] . '_' . $menuNodeId . '_1';
				}
			}
			
			if (isset($box['tabs']['categories']['fields']['categories'])) {
				setupCategoryCheckboxes($box['tabs']['categories']['fields']['categories'], true);
		
				if ($categories = get('refiner__category')) {
					
					$categories = explodeAndTrim($categories);
					$inCategories = array_flip($categories);
			
					foreach ($categories as $catId) {
						$categoryAncestors = array();
						getCategoryAncestors($catId, $categoryAncestors);
				
						foreach ($categoryAncestors as $catAnId) {
							if (!isset($inCategories[$catAnId])) {
								$categories[] = $catAnId;
							}
						}
					}
			
					$values['categories/categories'] = $categories;
				}
			}
		}
		if (!$version && $box['key']['target_alias']) {
			$values['meta_data/alias'] = $box['key']['target_alias'];
		}
		if (!$version && $box['key']['target_title']) {
			$values['meta_data/title'] = $box['key']['target_title'];
		}
		if (!$version && $box['key']['target_menu_title'] && isset($box['tabs']['meta_data']['fields']['menu_title'])) {
			$values['meta_data/menu_title'] = $box['key']['target_menu_title'];
		}

		if (!empty($values['meta_data/menu_title'])) {
			if (arrayKey($box,'tabs','meta_data','fields','menu_parent_path','value')) {
				$values['meta_data/menu_path'] =
					$values['meta_data/menu_parent_path'].
					' -> '.
					$values['meta_data/menu_title'];
			} else {
				$values['meta_data/menu_path'] =
					$values['meta_data/menu_title'];
			}
		}

		//Don't let the language be changed if this Content Item already exists, or will be placed in a set language for the menu
		if (!$box['key']['duplicate'] && ($content || $box['key']['target_menu_section'])) {
			$lockLanguageId = true;
		}

		if (isset($box['tabs']['categories']['fields']['desc'])) {
			$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
				adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
					array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/categories'). '" target="_blank"'));
		
				if (checkRowExists('categories', array())) {
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
			   && ($box['key']['cVersion'] < $content['admin_version'] || !checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType']))
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
						adminPhrase('Creating a translation in "[[lang]]" of the content item "[[tag]]" ([[old_lang]]).',
							array('tag' => $tag, 'old_lang' => $content['language_id'], 'lang' => getLanguageName($box['key']['target_language_id'])));
			
				} elseif ($box['key']['source_cVersion'] < $content['admin_version']) {
					$box['title'] =
						adminPhrase('Duplicating the [[status]] (version [[version]]) Content Item "[[tag]]"',
							array('tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']));
				} else {
					$box['title'] =
						adminPhrase('Duplicating the [[status]] content item "[[tag]]"',
							array('tag' => $tag, 'status' => $status));
				}
			} else {
				if ($box['key']['source_cVersion'] < $content['admin_version']) {
					$box['title'] =
						adminPhrase('Viewing metadata of content item "[[tag]]", version [[version]] ([[status]])',
							array('tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']));
				} else {
					$box['title'] =
						adminPhrase('Editing metadata (version-controlled) of content item "[[tag]]", version [[version]] ([[status]])',
							array('tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']));
				}
			}
		} elseif (($box['key']['target_cType'] || (!$box['key']['id'] && $box['key']['cType'])) && $contentType) {
			$box['title'] = adminPhrase('Creating a content item, [[content_type_name_en]]', $contentType);
		}

		//Remove the Menu Creation options if an Admin does not have the permissions to create a Menu Item
		if (($box['key']['translate'] && !checkPriv('_PRIV_EDIT_MENU_TEXT'))
		 || (!$box['key']['translate'] && !checkPriv('_PRIV_ADD_MENU_ITEM'))) {
			$values['meta_data/create_menu'] = '';
			$fields['meta_data/create_menu']['hidden'] = true;
			$box['key']['target_menu_section'] = null;
			$box['key']['target_menu_parent'] = null;
		}

		if ($lockLanguageId) {
			$box['tabs']['meta_data']['fields']['language_id']['read_only'] = true;
		}


		if (empty($fields['meta_data/create_menu']['hidden'])) {
			$values['meta_data/create_menu'] =
			$box['tabs']['meta_data']['fields']['create_menu']['current_value'] = 1;
		}


		//Attempt to load the content into the content tabs for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])) {
			$i = 0;
			$slots = array();
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']
			 && ($slots = pluginMainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], false, false))
			 && (!empty($slots))) {
	
				//Set the content for each slot, with a limit of four slots
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
					$values['content'. $i. '/content'] =
						getContent($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], $slot);
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
		
		//For top-level menu modes, add a note to the "path" field to make it clear that it's
		//at the top level
		if (!$values['meta_data/menu_parent_path']) {
			$fields['meta_data/menu_path']['label'] = adminPhrase('Path (top level):');
		}
	}

	public function formatContentFAB($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$box['tabs']['file']['hidden'] = true;

		if (!$box['key']['cID']) {
			if ($values['meta_data/layout_id']) {
				$box['key']['cType'] = getRow('layouts', 'content_type', $values['meta_data/layout_id']);
			}
		}
		$fields['css/background_image']['side_note'] = '';
		$fields['css/bg_color']['side_note'] = '';
		$fields['css/bg_position']['side_note'] = '';
		$fields['css/bg_repeat']['side_note'] = '';
		$box['tabs']['meta_data']['notices']['archived_template']['show'] = false;

		if ($values['meta_data/layout_id']
		 && ($layout = getTemplateDetails($values['meta_data/layout_id']))) {
	
			if ($layout['status'] != 'active') {
				$box['tabs']['meta_data']['notices']['archived_template']['show'] = true;
			}
	
			if ($layout['bg_image_id']) {
				$fields['css/background_image']['side_note'] = htmlspecialchars(
					adminPhrase("Setting a background image here will override the background image set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_color']) {
				$fields['css/bg_color']['side_note'] = htmlspecialchars(
					adminPhrase("Setting a background color here will override the background color set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_position']) {
				$fields['css/bg_position']['side_note'] = htmlspecialchars(
					adminPhrase("Setting a background position here will override the background position set on this item's layout ([[id_and_name]]).", $layout));
			}
			if ($layout['bg_repeat']) {
				$fields['css/bg_repeat']['side_note'] = htmlspecialchars(
					adminPhrase("Setting an option here will override the option set on this item's layout ([[id_and_name]]).", $layout));
			}
		}
		
		$fields['meta_data/description']['hidden'] = false;
		$fields['meta_data/writer']['hidden'] = false;
		$fields['meta_data/keywords']['hidden'] = false;
		$fields['meta_data/publication_date']['hidden'] = false;
		$fields['meta_data/content_summary']['hidden'] = false;
		if ($path != 'zenario_quick_create' && $box['key']['cType'] && $details = getContentTypeDetails($box['key']['cType'])) {
			if ($details['description_field'] == 'hidden') {
				$fields['meta_data/description']['hidden'] = true;
			}
			if ($details['keywords_field'] == 'hidden') {
				$fields['meta_data/keywords']['hidden'] = true;
			}
			if ($details['release_date_field'] == 'hidden') {
				$fields['meta_data/publication_date']['hidden'] = true;
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
		 && !engToBooleanArray($box['tabs']['meta_data']['fields']['writer_id'], 'hidden')) {
			if ($values['meta_data/writer_id']) {
				if (engToBooleanArray($box, 'tabs', 'meta_data', 'edit_mode', 'on')) {
					if (empty($box['tabs']['meta_data']['fields']['writer_name']['current_value'])
					 || empty($box['tabs']['meta_data']['fields']['writer_id']['last_value'])
					 || $box['tabs']['meta_data']['fields']['writer_id']['last_value'] != $values['meta_data/writer_id']) {
						$adminDetails = getAdminDetails($values['meta_data/writer_id']);
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
			$languageId = getContentLang($box['key']['cID'], $box['key']['cType']);
			$specialPage = isSpecialPage($box['key']['cID'], $box['key']['cType']);
		} else {
			$languageId = ifNull($values['meta_data/language_id'], $box['key']['target_template_id'], setting('default_language'));
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
	
		$googlePreviewHTML = '									
			<div  class="google_preview_container">
					<h3 class="google_preview_title">
						<span id="google_preview_title">
							[[google_preview_title]]
						</span>
					</h3>
					<div class="google_preview_url">
						<div>
							<cite id="google_preview_url">[[google_preview_url]]</cite>
						</div>
						<span id="google_preview_description" class="google_preview_description">
							[[google_preview_description]]
						</span>
					</div>
				</div>';


	
		if (strlen($values['meta_data/title'])<1) {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_red', $titleCounterHTML);
		} elseif (strlen($values['meta_data/title'])<20)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_orange', $titleCounterHTML);
		} elseif (strlen($values['meta_data/title'])<40)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
		} elseif (strlen($values['meta_data/title'])<66)  {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_green', $titleCounterHTML);
		} else {
			$titleCounterHTML = str_replace('[[initial_class_name]]', 'title_yellow', $titleCounterHTML);
		}
		$titleCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/title']), $titleCounterHTML);
		$box['tabs']['meta_data']['fields']['title']['post_field_html'] = $titleCounterHTML;


		if (strlen($values['meta_data/description'])<1) {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_red', $descriptionCounterHTML);
		} elseif (strlen($values['meta_data/description'])<50)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_orange', $descriptionCounterHTML);
		} elseif (strlen($values['meta_data/description'])<100)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
		} elseif (strlen($values['meta_data/description'])<156)  {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_green', $descriptionCounterHTML);
		} else {
			$descriptionCounterHTML = str_replace('[[initial_class_name]]', 'description_yellow', $descriptionCounterHTML);
		}
		$descriptionCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/description']), $descriptionCounterHTML);
		$box['tabs']['meta_data']['fields']['description']['post_field_html'] = $descriptionCounterHTML;


		$keywordsCounterHTML = str_replace('[[initial_characters_count]]', strlen($values['meta_data/keywords']) , $keywordsCounterHTML);
		$box['tabs']['meta_data']['fields']['keywords']['post_field_html'] = $keywordsCounterHTML;

		$title =  $values['meta_data/title'];
		displayHTMLAsPlainText($title, 65 );
		$googlePreviewHTML = str_replace('[[google_preview_title]]', $title, $googlePreviewHTML );

		$description =  $values['meta_data/description'];
		displayHTMLAsPlainText($description, 155 );
		$googlePreviewHTML = str_replace('[[google_preview_description]]', $description, $googlePreviewHTML );

		$alias = $values['meta_data/alias'];
		displayHTMLAsPlainText($alias, 50 );
		if ($alias) {
				$googlePreviewHTML = str_replace('[[google_preview_url]]', httpOrHttps() . httpHost() . SUBDIRECTORY . $alias,  $googlePreviewHTML);
		} else {
			if ($link = linkToItem($box['key']['source_cID'], $box['key']['cType'], true, '', false, false, true)) {
				$googlePreviewHTML = str_replace('[[google_preview_url]]', $link,  $googlePreviewHTML);
			} else {
				$googlePreviewHTML = str_replace('[[google_preview_url]]', '', $googlePreviewHTML);
			}
		}

		$fields['meta_data/google_preview']['hidden'] = 
			!($values['meta_data/alias'] || $values['meta_data/title'] || $values['meta_data/description']);


		$fields['meta_data/google_preview']['snippet']['html'] = $googlePreviewHTML;
		
		
		//Set up content tabs (up to four of them), for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])) {
			$i = 0;
			$slots = array();
			if ($box['key']['source_cID']
			 && $box['key']['cType']
			 && $box['key']['source_cVersion']) {
				$slots = pluginMainSlot($box['key']['source_cID'], $box['key']['cType'], $box['key']['source_cVersion'], false, false, $values['meta_data/layout_id']);
			} else {
				$slots = pluginMainSlotOnLayout($values['meta_data/layout_id'], false, false);
			}

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
		
					$box['tabs']['content'. $i]['hidden'] = false;
					if (count($slots) == 1) {
						$box['tabs']['content'. $i]['label'] = adminPhrase('Main content');
			
					} elseif (strlen($slot) <= 20) {
						$box['tabs']['content'. $i]['label'] = $slot;
			
					} else {
						$box['tabs']['content'. $i]['label'] = substr($slot, 0, 8). '...'. substr($slot, -8);
					}
					addAbsURLsToAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
			
					require_once CMS_ROOT. 'zenario/admin/grid_maker/grid_maker.inc.php';
					if (zenario_grid_maker::readLayoutCode($values['meta_data/layout_id'], $justCheck = true, $quickCheck = true)) {
						$fields['content'. $i. '/thumbnail']['hidden'] = false;
						$fields['content'. $i. '/thumbnail']['snippet']['html'] = '
							<p style="text-align: center;">
								<a>
									<img src="'. htmlspecialchars(
										absCMSDirURL(). 'zenario/admin/grid_maker/ajax.php?loadDataFromLayout='. (int) $values['meta_data/layout_id']. '&highlightSlot='. rawurlencode($slot). '&thumbnail=1&width=150&height=200'
									). '" width="150" height="200" style="border: 1px solid black;"/>
								</a>
							</p>';
			
					} else {
						$fields['content'. $i. '/thumbnail']['hidden'] = true;
						$fields['content'. $i. '/thumbnail']['snippet']['html'] = '';
					}
				}
			}
			
			// Hide dropdown if no content tabs are visible
			if ($i <= 1) {
				$box['tabs']['content_dropdown']['hidden'] = true;
				if ($i == 1) {
					unset($box['tabs']['content1']['parent']);
				}
			}
			
			// Hide extra content tabs
			while (++$i <= 4) {
				$box['tabs']['content'. $i]['hidden'] = true;
				$fields['content'. $i. '/thumbnail']['snippet']['html'] = '';
			}
		}
		if (isset($box['tabs']['meta_data']['fields']['content_summary'])) {
			addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
		}
	}

	public function formatMenu($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		
		$fields['meta_data/menu_title']['hidden'] =
		$fields['meta_data/menu_path']['hidden'] =
		$fields['meta_data/menu_parent_path']['hidden'] =
			empty($box['key']['target_menu_section'])
				|| !$values['meta_data/create_menu'];
		
		if (!empty($box['key']['create_from_toolbar'])) {
			$fields['meta_data/menu_title']['hidden'] = $values['meta_data/menu_options'] != 'add_to_menu';
			$fields['meta_data/menu_title']['indent'] = 1;
		}
		
		$fields['meta_data/menu_options']['hidden'] = 
			empty($box['key']['create_from_toolbar']);
		
		$fields['meta_data/add_to_menu']['hidden'] = 
			empty($box['key']['create_from_toolbar']) || $values['meta_data/menu_options'] != 'add_to_menu';
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$this->formatContentFAB($path, $settingGroup, $box, $fields, $values, $changes);
		$this->formatMenu($path, $settingGroup, $box, $fields, $values, $changes);
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$box['confirm']['show'] = false;
		$box['confirm']['message'] = '';

		if ($path == 'zenario_quick_create') {
			if (!$values['meta_data/layout_id']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please select a layout.');
			}

		} else {
			if (!$box['key']['cID']) {
				if (!$values['meta_data/layout_id']) {
					$box['tab'] = 'meta_data';
					$box['tabs']['meta_data']['errors'][] = adminPhrase('Please select a layout.');
				} else {
					$box['key']['cType'] = getRow('layouts', 'content_type', $values['meta_data/layout_id']);
				}
	
			} else {
				validateChangeSingleLayout($box, $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $values['meta_data/layout_id'], $saving);
			}
	
			if (!checkContentTypeRunning($box['key']['cType'])) {
				$box['tabs']['meta_data']['errors'][] =
					adminPhrase(
						'Drafts of "[[cType]]" type content items cannot be created as their handler module is missing or not running.',
						array('cType' => $box['key']['cType']));
			}
		}

		if (!$values['meta_data/title']) {
			$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a title.');
		}

		if (!empty($values['meta_data/alias'])) {
			$errors = false;
			if ($box['key']['translate']) {
				if (setting('translations_different_aliases')) {
					$errors = validateAlias($values['meta_data/alias'], false, $box['key']['cType'], equivId($box['key']['source_cID'], $box['key']['cType']));
				}
			} else {
				$errors = validateAlias($values['meta_data/alias']);
			}
			if (!empty($errors) && is_array($errors)) {
				$box['tabs']['meta_data']['errors'] = array_merge($box['tabs']['meta_data']['errors'], $errors);
			}
		}


		if ($path != 'zenario_quick_create' && $box['key']['cType'] && $details = getContentTypeDetails($box['key']['cType'])) {
			if ($details['description_field'] == 'mandatory' && !$values['meta_data/description']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a description.');
			}
			if ($details['keywords_field'] == 'mandatory' && !$values['meta_data/keywords']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter keywords.');
			}
			if ($details['release_date_field'] == 'mandatory' && !$values['meta_data/publication_date']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a release date.');
			}
			if ($details['writer_field'] == 'mandatory' && !$values['meta_data/writer_id']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please select a writer.');
			}
			if ($details['summary_field'] == 'mandatory' && !$values['meta_data/content_summary']) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a summary.');
			}
		}

		if (issetArrayKey($values,'meta_data/writer_id') && !issetArrayKey($values,'meta_data/writer_name')) {
			$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter a writer name.');
		}

		if (!empty($box['key']['target_menu_section'])
		 && $values['meta_data/create_menu']
		 && !$values['meta_data/menu_title']) {
			$box['tabs']['meta_data']['errors'][] = adminPhrase('Please enter the menu node text.');
		}

		if ($box['key']['translate']) {
			$equivId = equivId($box['key']['source_cID'], $box['key']['cType']);
	
			if (checkRowExists('content_items', array('equiv_id' => $equivId, 'type' => $box['key']['cType'], 'language_id' => $values['meta_data/language_id']))) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('This translation already exists.');
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['cID'] && !checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			exit;
		}
		
		//Create a new Content Item, or a new Draft of a Content Item, as needed.
		createDraft($box['key']['cID'], $box['key']['source_cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['source_cVersion'], $values['meta_data/language_id']);

		if (!$box['key']['cID']) {
			exit;
		} else {
			$box['key']['id'] = $box['key']['cType'].  '_'. $box['key']['cID'];
		}

		$version = array();
		$newLayoutId = false;


		//Save the values of each field in the Meta Data tab
		if (engToBoolean($box['tabs']['meta_data']['edit_mode']['on'])
		 && checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			//Only save aliases for first drafts
			if (!empty($values['meta_data/alias']) && $box['key']['cVersion'] == 1) {
				if (!$box['key']['translate'] || setting('translations_different_aliases')) {
					setRow('content_items', array('alias' => tidyAlias($values['meta_data/alias'])), array('id' => $box['key']['cID'], 'type' => $box['key']['cType']));
				}
			}
	
			//Create Menu Nodes for first drafts
			if ($values['meta_data/create_menu'] && $box['key']['cVersion'] == 1 && $box['key']['target_menu_section']) {
				if (($box['key']['translate'] && checkPriv('_PRIV_EDIT_MENU_TEXT')) || (!$box['key']['translate'] && checkPriv('_PRIV_ADD_MENU_ITEM'))) {
			
					if (($box['key']['duplicate'] || $box['key']['translate'])
					 && recordEquivalence($box['key']['source_cID'], $box['key']['cID'], $box['key']['cType'])
					 && ($menu = getMenuItemFromContent($box['key']['source_cID'], $box['key']['cType']))) {
						//Try to use one equivalent Menu Node rather than creating two copies, if duplicationg into a new Language
						$menuId = $menu['mID'];
			
					} elseif (!$box['key']['translate']) {
						$submission = array();
						$submission['target_loc'] = 'int';
						$submission['content_id'] = $box['key']['cID'];
						$submission['content_type'] = $box['key']['cType'];
						$submission['content_version'] = $box['key']['cVersion'];
						$submission['parent_id'] = $box['key']['target_menu_parent'];
						$submission['section_id'] = $box['key']['target_menu_section'];
				
						$menuId = saveMenuDetails($submission);
					}
			
					saveMenuText($menuId, $values['meta_data/language_id'], array('name' => $values['meta_data/menu_title']));
				}
			}
			
			//Create menu node from toolbar
			if (!empty($box['key']['create_from_toolbar']) 
				&& $values['meta_data/menu_options'] == 'add_to_menu' 
				&& $values['meta_data/add_to_menu']
			) {
				$contentType = getContentTypeDetails($box['key']['cType']);
				addContentItemsToMenu($box['key']['id'], $values['meta_data/add_to_menu'], $contentType['hide_menu_node']);
				
				$menuId = getRow('menu_nodes', 'id', array('equiv_id' => $box['key']['cID'], 'content_type' => $box['key']['cType']));
				saveMenuText($menuId, $values['meta_data/language_id'], array('name' => $values['meta_data/menu_title']));
			}

			//Set the title
			$version['title'] = $values['meta_data/title'];
			
			if ($path == 'zenario_quick_create') {
				$version['layout_id'] = $values['meta_data/layout_id'];
			} else {
				$version['description'] = $values['meta_data/description'];
				$version['keywords'] = $values['meta_data/keywords'];
				$version['publication_date'] = $values['meta_data/publication_date'];
				$version['writer_id'] = $values['meta_data/writer_id'];
				$version['writer_name'] = $values['meta_data/writer_name'];
		
				stripAbsURLsFromAdminBoxField($box['tabs']['meta_data']['fields']['content_summary']);
				$version['content_summary'] = $values['meta_data/content_summary'];
		
				if (isset($fields['meta_data/lock_summary_edit_mode']) && !$fields['meta_data/lock_summary_edit_mode']['hidden']) {
					$version['lock_summary'] = (int) $values['meta_data/lock_summary_edit_mode'];
				}
			}
		}

		//Set the Layout
		if (engToBooleanArray($box, 'tabs', 'meta_data', 'edit_mode', 'on')
		 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $box['key']['cID'], $box['key']['cType'])) {
			$newLayoutId = $values['meta_data/layout_id'];
		}

		//Save the CSS and background
		if (engToBooleanArray($box, 'tabs', 'css', 'edit_mode', 'on')
		 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_TEMPLATE', $box['key']['cID'], $box['key']['cType'])) {
			$version['css_class'] = $values['css/css_class'];
	
			if (($filepath = getPathOfUploadedFileInCacheDir($values['css/background_image']))
			 && ($imageId = addFileToDatabase('background_image', $filepath, false, $mustBeAnImage = true))) {
				$version['bg_image_id'] = $imageId;
			} else {
				$version['bg_image_id'] = $values['css/background_image'];
			}
	
			$version['bg_color'] = $values['css/bg_color'];
			$version['bg_position'] = $values['css/bg_position']? $values['css/bg_position'] : null;
			$version['bg_repeat'] = $values['css/bg_repeat']? $values['css/bg_repeat'] : null;
		}

		//Save the chosen file, if a file was chosen
		if (engToBooleanArray($box, 'tabs', 'file', 'edit_mode', 'on')) {
			if ($values['file/file']
			 && ($path = getPathOfUploadedFileInCacheDir($values['file/file']))
			 && ($filename = preg_replace('/([^.a-z0-9]+)/i', '_', basename($path)))
			 && ($fileId = addFileToDocstoreDir('content', $path, $filename))) {
				$version['file_id'] = $fileId;
				$version['filename'] = $filename;
			} else {
				$version['file_id'] = $values['file/file'];
			}
		}

		//Update the latest version
		if (!empty($version) || $newLayoutId) {
			updateVersion($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $version);
	
			//Update the layout
			if ($newLayoutId) {
				changeContentItemLayout($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $newLayoutId);
			}
		}


		//Save the content tabs (up to four of them), for each WYSIWYG Editor
		if (isset($box['tabs']['content1'])
		 && checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
			$i = 0;
			$slots = pluginMainSlot($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], false, false, $values['meta_data/layout_id']);

			if (!empty($slots)) {
				foreach ($slots as $slot) {
					if (++$i > 4) {
						break;
					}
			
					if (!empty($box['tabs']['content'. $i]['edit_mode']['on'])) {
						stripAbsURLsFromAdminBoxField($box['tabs']['content'. $i]['fields']['content']);
						saveContent($values['content'. $i. '/content'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $slot);
					}
				}
			}
		}


		//Update item Categories
		if (empty($box['tabs']['categories']['hidden'])
		 && engToBooleanArray($box, 'tabs', 'categories', 'edit_mode', 'on')
		 && isset($values['categories/categories'])
		 && checkPriv('_PRIV_EDIT_CONTENT_ITEM_CATEGORIES')) {
			setContentItemCategories($box['key']['cID'], $box['key']['cType'], explodeAndTrim($values['categories/categories']));
		}

		//Record and equivalence if this Content Item was duplicated into another Language
		if ($box['key']['translate']) {
			if ($equivId = recordEquivalence($box['key']['source_cID'], $box['key']['cID'], $box['key']['cType'])) {
				//Create copies of any Menu Node Text into this language
				$sql = "
					INSERT IGNORE INTO ". DB_NAME_PREFIX. "menu_text
						(menu_id, language_id, name, descriptive_text)
					SELECT menu_id, '". sqlEscape($values['meta_data/language_id']). "', name, descriptive_text
					FROM ". DB_NAME_PREFIX. "menu_nodes AS mn
					INNER JOIN ". DB_NAME_PREFIX. "menu_text AS mt
					   ON mt.menu_id = mn.id
					  AND mt.language_id = '". sqlEscape(getContentLang($box['key']['source_cID'], $box['key']['cType'])). "'
					WHERE mn.equiv_id = ". (int) $equivId. "
					  AND mn.content_type = '". sqlEscape($box['key']['cType']). "'";
				sqlQuery($sql);
			}
		}

		if (isset($version['bg_image_id'])) {
			deleteUnusedBackgroundImages();
		}
		
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['id_is_menu_node_id'] || $box['key']['id_is_parent_menu_node_id']) {
			if ($menu = getMenuItemFromContent($box['key']['cID'], $box['key']['cType'], $fetchSecondaries = false, $sectionId = $box['key']['target_menu_section'])) {
				$box['key']['id'] = $menu['id'];
			}
		}
	}
}
