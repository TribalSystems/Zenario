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
	$box['tabs']['meta_data']['fields']['domain_and_subdir_container']['value'] = httpOrHttps() . httpHost() . SUBDIRECTORY;
	
	if ($box['key']['duplicate'] && $box['key']['duplicate_from_menu']) {
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
if ($box['key']['target_language_id'] || $box['key']['duplicate'] || $box['key']['translate']) {
	$box['key']['lock_language_id'] = true;
}

//Populate the language select list
getLanguageSelectListOptions($box['tabs']['meta_data']['fields']['language_id']);

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
if ($box['key']['duplicate']) {
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


//Remove the ability to create a Menu Node if location information for the menu has not been provided
if (!$box['key']['target_menu_section']) {
	$box['tabs']['meta_data']['fields']['create_menu']['hidden'] = true;
	
	if ($path == 'zenario_quick_create') {
		$box['tabs']['meta_data']['fields']['more_options_menu']['hidden'] = true;
		$box['tabs']['meta_data']['fields']['more_options_button_menu']['hidden'] = true;
	}

} elseif ($box['key']['target_menu_parent']) {
	$box['tabs']['meta_data']['fields']['menu_parent_path']['value'] = getMenuPath($box['key']['target_menu_parent']);
}

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
				'content',
				array('id', 'type', 'tag_id', 'language_id', 'alias', 'visitor_version', 'admin_version', 'status'),
				array('id' => $box['key']['source_cID'], 'type' => $box['key']['cType']));
	}
	
	
	if ($content) {
		if ($box['key']['duplicate']) {
			//If duplicating, check for a Menu Node
			if (checkPriv('_PRIV_ADD_MENU_ITEM')
			 && ($currentMenu = getMenuItemFromContent($box['key']['source_cID'], $box['key']['cType']))
			
			//When duplicating to a new/different language, if Menu Text already exists in the new Language,
			//but a Content Item does not, rely on the existing Menu Text and don't offer to create a new one
			 && (!($lang = ifNull($box['key']['target_language_id'], get('languageId'), get('language')))
			  || ($lang == $content['language_id'])
			  || !(checkRowExists('menu_text', array('menu_id' => $currentMenu['id'], 'language_id' => $lang))))) {
				
				$box['key']['source_menu'] = $currentMenu['mID'];
				$box['key']['source_menu_parent'] = $currentMenu['parent_id'];
				$box['key']['target_menu_section'] = $currentMenu['section_id'];
				$box['tabs']['meta_data']['fields']['menu_title']['value'] = $currentMenu['name'];
				$box['tabs']['meta_data']['fields']['menu_path']['value'] = getMenuPath($currentMenu['parent_id']);
			} else {
				$box['tabs']['meta_data']['fields']['create_menu']['hidden'] = true;
				$box['key']['target_menu_section'] = null;
				$box['key']['target_menu_parent'] = null;
			}
		
		} elseif ($box['key']['translate']) {
			$box['tabs']['meta_data']['fields']['alias']['value'] = $content['alias'];
			$box['tabs']['meta_data']['fields']['create_menu']['hidden'] = true;
			$box['tabs']['categories']['hidden'] = true;
			$box['tabs']['privacy']['hidden'] = true;
		
		} else {
			//The options to set the alias, menu text, categories or privacy (if it is there!) should be hidden when not creating something
			$box['tabs']['meta_data']['fields']['alias']['hidden'] = true;
			$box['tabs']['meta_data']['fields']['create_menu']['hidden'] = true;
			$box['tabs']['categories']['hidden'] = true;
			$box['tabs']['privacy']['hidden'] = true;
		}
		
		//$box['tabs']['meta_data']['fields']['status']['value'] = $content['status'];
		$box['tabs']['meta_data']['fields']['language_id']['value'] = $content['language_id'];
		
		$box['tabs']['template']['fields']['layout_id']['pick_items']['path'] = 'zenario__content/panels/content_types/hidden_nav/layouts//'. $content['type']. '//';
		
		if ($version =
			getRow(
				'versions',
				true,
				array('id' => $box['key']['source_cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['source_cVersion']))
		) {
			$box['tabs']['meta_data']['fields']['title']['value'] = $version['title'];
			$box['tabs']['meta_data']['fields']['description']['value'] = $version['description'];
			$box['tabs']['meta_data']['fields']['keywords']['value'] = $version['keywords'];
			$box['tabs']['meta_data']['fields']['publication_date']['value'] = $version['publication_date'];
			$box['tabs']['meta_data']['fields']['writer_id']['value'] = $version['writer_id'];
			$box['tabs']['meta_data']['fields']['writer_name']['value'] = $version['writer_name'];
			$box['tabs']['meta_data']['fields']['content_summary']['value'] = $version['content_summary'];
			$box['tabs']['template']['fields']['layout_id']['value'] = $version['layout_id'];
			$box['tabs']['css']['fields']['css_class']['value'] = $version['css_class'];
			$box['tabs']['css']['fields']['background_image']['value'] = $version['bg_image_id'];
			$box['tabs']['css']['fields']['bg_color']['value'] = $version['bg_color'];
			$box['tabs']['css']['fields']['bg_position']['value'] = $version['bg_position'];
			$box['tabs']['css']['fields']['bg_repeat']['value'] = $version['bg_repeat'];
			$box['tabs']['file']['fields']['file']['value'] = $version['file_id'];
			
			if ($box['key']['cID'] && getRow('content_types', 'enable_summary_auto_update', $box['key']['cType'])) {
				$box['tabs']['meta_data']['fields']['lock_summary_view_mode']['value'] =
				$box['tabs']['meta_data']['fields']['lock_summary_edit_mode']['value'] = $version['lock_summary'];
				$box['tabs']['meta_data']['fields']['lock_summary_view_mode']['hidden'] =
				$box['tabs']['meta_data']['fields']['lock_summary_edit_mode']['hidden'] = false;
			}
			
			if (isset($box['tabs']['categories']['fields']['categories'])) {
				setupCategoryCheckboxes(
					$box['tabs']['categories']['fields']['categories'], true,
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
			$box['tabs']['template']['fields']['layout_id']['pick_items']['path'] = 'zenario__content/panels/content_types/hidden_nav/layouts//'. $box['key']['target_cType']. '//';
		}
	}
}


//Set default values
if ($content) {
	if ($box['key']['duplicate'] || $box['key']['translate']) {
		$box['tabs']['meta_data']['fields']['language_id']['value'] = ifNull($box['key']['target_language_id'], ifNull(get('languageId'), get('language'), $content['language_id']));
	}
} else {
	$box['tabs']['meta_data']['fields']['language_id']['value'] = ifNull($box['key']['target_language_id'], get('languageId'), setting('default_language'));
}

if (!$version) {
	//Attempt to work out the default template and Content Type for a new Content Item
	if (($layoutId = ifNull($box['key']['target_template_id'], get('refiner__template')))
	 && ($box['key']['cType'] = getRow('layouts', 'content_type', $layoutId))) {
	
	} elseif ($box['key']['target_menu_parent']
		   && ($cItem = getRow('menu_nodes', array('equiv_id', 'content_type'), array('id' => $box['key']['target_menu_parent'], 'target_loc' => 'int')))
		   && ($cItem['content_type'] == 'html' || $path != 'zenario_quick_create')
		   && ($cItem['admin_version'] = getRow('content', 'admin_version', array('id' => $cItem['equiv_id'], 'type' => $cItem['content_type'])))
		   && ($layoutId = contentItemTemplateId($cItem['equiv_id'], $cItem['content_type'], $cItem['admin_version']))) {
	
	} else {
		if ($path == 'zenario_quick_create') {
			$box['key']['cType'] = 'html';
		} else {
			$box['key']['cType'] = ifNull($box['key']['target_cType'], $box['key']['cType'], 'html');
		}
		$layoutId = getRow('content_types', 'default_layout_id', array('content_type_id' => $box['key']['cType']));
	}
	
	if (isset($box['tabs']['meta_data']['fields']['layout_id']) && empty($box['tabs']['meta_data']['fields']['layout_id']['value'])) {
		$box['tabs']['meta_data']['fields']['layout_id']['value'] = $layoutId;
	} elseif (isset($box['tabs']['template']['fields']['layout_id']) && empty($box['tabs']['template']['fields']['layout_id']['value']))  {
		$box['tabs']['template']['fields']['layout_id']['value'] = $layoutId;
	}
	
	if (isset($box['tabs']['categories']['fields']['categories'])) {
		setupCategoryCheckboxes($box['tabs']['categories']['fields']['categories'], true);
		
		if (($categories = get('refiner__category')) || ($categories = $box['key']['target_categories'])) {
			$box['key']['target_categories'] = $categories;
			
			$categories = explode(',', $categories);
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
			
			$box['tabs']['categories']['fields']['categories']['value'] = $box['key']['target_categories'];
		}
	}
}
if (!$version && $box['key']['target_alias']) {
	$box['tabs']['meta_data']['fields']['alias']['value'] = $box['key']['target_alias'];
}
if (!$version && $box['key']['target_title']) {
	$box['tabs']['meta_data']['fields']['title']['value'] = $box['key']['target_title'];
}
if (!$version && $box['key']['target_menu_title'] && isset($box['tabs']['meta_data']['fields']['menu_title'])) {
	$box['tabs']['meta_data']['fields']['menu_title']['value'] = $box['key']['target_menu_title'];
}

if (!empty($box['tabs']['meta_data']['fields']['menu_title']['value'])) {
	if (arrayKey($box,'tabs','meta_data','fields','menu_parent_path','value')) {
		$box['tabs']['meta_data']['fields']['menu_path']['value'] =
			$box['tabs']['meta_data']['fields']['menu_parent_path']['value'].
			' -> '.
			$box['tabs']['meta_data']['fields']['menu_title']['value'];
	} else {
		$box['tabs']['meta_data']['fields']['menu_path']['value'] =
			$box['tabs']['meta_data']['fields']['menu_title']['value'];
	}
}

//Don't let the language be changed if this Content Item already exists, or will be placed in a set language for the menu
if (!$box['key']['duplicate'] && ($content || $box['key']['target_menu_section'])) {
	$box['key']['lock_language_id'] = true;
}

if (isset($box['tabs']['categories']['fields']['desc'])) {
	$box['tabs']['categories']['fields']['desc']['snippet']['html'] = 
		adminPhrase('You can put content item(s) into one or more categories. (<a[[link]]>Define categories</a>.)',
			array('link' => ' href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__content/categories'). '" target="_blank"'));
		
		if (checkRowExists('categories', array())) {
			$box['tabs']['categories']['fields']['no_categories']['hidden'] = true;
		} else {
			$box['tabs']['categories']['fields']['categories']['hidden'] = true;
		}
}



//Turn edit mode on if we will be creating a new Content Item
if (!$box['key']['cID'] || $box['key']['cID'] != $box['key']['source_cID']) {
	foreach ($box['tabs'] as $i => &$tab) {
		if (!isInfoTag($i) && isset($tab['edit_mode'])) {
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
		if (!isInfoTag($i) && isset($tab['edit_mode'])) {
			$tab['edit_mode']['enabled'] = false;
		}
	}

} else {
	foreach ($box['tabs'] as $i => &$tab) {
		if (!isInfoTag($i) && isset($tab['edit_mode'])) {
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
				adminPhrase('Viewing version-controlled settings for the [[status]] (version [[version]]) content item "[[tag]]"',
					array('tag' => $tag, 'status' => $status, 'version' => $box['key']['source_cVersion']));
		} else {
			$box['title'] =
				adminPhrase('Editing version-controlled settings for the [[status]] content item "[[tag]]"',
					array('tag' => $tag, 'status' => $status));
		}
	}
}

//Remove the Menu Creation options if an Admin does not have the permissions to create a Menu Item
if (($box['key']['target_menu_parent'] && !checkPriv('_PRIV_ADD_MENU_ITEM'))
 || (!$box['key']['target_menu_parent'] && !checkPriv('_PRIV_ADD_MENU_ITEM'))) {
	$box['tabs']['meta_data']['fields']['create_menu']['hidden'] = true;
	$box['key']['target_menu_section'] = null;
	$box['key']['target_menu_parent'] = null;
}

if ($box['key']['lock_language_id']) {
	$box['tabs']['meta_data']['fields']['language_id']['read_only'] = true;
}


if (empty($box['tabs']['meta_data']['fields']['create_menu']['hidden'])) {
	$box['tabs']['meta_data']['fields']['create_menu']['value'] =
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


if ($box['key']['cID']) {
	$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
} else {
	$box['key']['id'] = null;
}