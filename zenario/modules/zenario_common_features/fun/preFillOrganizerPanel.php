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


switch ($path) {
	case 'zenario__menu/panels/menu_nodes':
		if (!get('refiner__show_language_choice') && !in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_missing_items'];
		}
		
		$numLanguages = getNumLanguages();
		if ($numLanguages < 2) {
			unset($panel['columns']['translations']);
			unset($panel['item_buttons']['zenario_trans__view']);
		}
		
		break;
	

	case 'zenario__users/panels/administrators':
		if (!$refinerName && !in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
		}

		
		break;
		
			
	case 'zenario__administration/panels/site_settings':
		//Either show the "site disabled" icon or the "site enabled" icon,
		//depending on whether the site is enabled or not
		if (setting('site_enabled')) {
			unset($panel['items']['site_disabled']);
		} else {
			unset($panel['items']['site_enabled']);
		}
		
		//If a favicon is set, change the icon of the favicon to that icon
		if (setting('favicon')
		 && ($icon = getRow('files', array('id', 'mime_type', 'filename', 'checksum'), setting('favicon')))) {
			if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
				$url = fileLink($icon['id']);
			} else {
				$width = $height = $url = false;
				imageLink($width, $height, $url, $icon['id'], 24, 23);
			}
			$panel['items']['favicon']['list_image'] = $url;
		}
		
		//Same for the site logo and the rebranding
		if (setting('brand_logo') == 'custom' && setting('custom_logo')) {
			$width = $height = $url = false;
			imageLink($width, $height, $url, setting('custom_logo'), 24, 23);
			$panel['items']['branding']['list_image'] = $url;
		}
		
		break;

	
	case 'zenario__content/panels/languages':
		//Check if a specific Content Type has been set
		if (get('refiner__content_type')) {
			$panel['key']['cType'] = get('refiner__content_type');
		} elseif (get('refiner__template')) {
			$panel['key']['cType'] = getRow('layouts', 'content_type', get('refiner__template'));
		}
		
		break;
	
	
	case 'zenario__content/panels/content_types':
		checkForMissingTemplateFiles();
		
		break;
	
	
	case 'zenario__content/hidden_nav/sitemap/panel':
		if (!setting('sitemap_enabled')) {
			header('HTTP/1.0 403 Forbidden');
			exit;
		}
		
		break;
	
	
	case 'zenario__content/panels/content':
	case 'zenario__content/panels/chained':
	case 'zenario__content/panels/language_equivs':
		return require funIncPath(__FILE__, 'content.preFillOrganizerPanel');
	
	
	case 'zenario__content/panels/categories':
		
		
		if (!$refinerName && !in($mode, 'get_item_name', 'get_item_links')) {
			$panel['title'] = adminPhrase('Top Level Categories');
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_top_level'];
		}
		
		if ($refinerName && $refinerName != 'parent_category') {
			unset($panel['item']['link']);
		}
		
		break;
	
	
	case 'generic_image_panel':
	case 'zenario__content/panels/inline_images_for_content':
	case 'zenario__content/panels/image_library':
		
		switch ($refinerName) {
			case 'content':
				$cID = $cType = false;
				getCIDAndCTypeFromTagId($cID, $cType, get('refiner__content'));
			
				if (!checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType)) {
					unset($panel['collection_buttons']['add']);
					unset($panel['collection_buttons']['upload']);
				}
				
				$panel['title'] =
					adminPhrase('Images used on the content item [[tag]], version [[version]]',
						array(
							'tag' => formatTagFromTagId(get('refiner__content')),
							'version' => getRow('content', 'admin_version', array('tag_id' => get('refiner__content')))));
				
				break;
			
			
			case 'tag':
				if ($tag = getRow('image_tags', true, array('name' => $refinerId))) {
					$panel['title'] = adminPhrase('Images that use the tag "[[name]]"', $tag);
					$panel['no_items_message'] = adminPhrase('There are no images that use the tag "[[name]]".', $tag);
					$panel['refiners']['tag']['table_join'] .=  (int) $tag['id'];
				} else {
					echo adminPhrase('Tag not found');
					exit;
				}
				
				
				break;
		}
		
		//Don't do anything fancy if we're just looking up a name
		if (in($mode, 'get_item_name', 'get_item_links')) {
			$panel['db_items']['table'] = '[[DB_NAME_PREFIX]]files AS f';
			unset($panel['refiner_required']);
			unset($panel['db_items']['where_statement']);
			unset($panel['columns']['usage_file_link']);
			unset($panel['columns']['usage_plugins']);
			unset($panel['columns']['usage_menu']);
			unset($panel['columns']['usage']);
			unset($panel['columns']['in_use']);
			unset($panel['columns']['in_use_here']);
			unset($panel['columns']['in_use_anywhere']);
			unset($panel['columns']['in_use_elsewhere']);
			unset($panel['columns']['sticky_flag']);
		
		} elseif (!$refinerName && $path == 'zenario__content/panels/image_library') {
			$ord = 1000;
			
			$tags = getRowsArray('image_tags', 'name', array(), 'name');
			
			$panel['quick_filter_buttons']['tags']['hidden'] = false;
			
			if (empty($tags)) {
				$panel['quick_filter_buttons']['tags']['disabled'] = true;
				
				$panel['quick_filter_buttons']['dummy_child'] = array(
					'disabled' => true,
					'ord' => $ord,
					'parent' => 'tags',
					'label' => adminPhrase('No tags exist, edit an image or click "Manage tags" to create tags')
				);
			
			} else {
				foreach ($tags as $tagId => $tagName) {
					++$ord;
					$codeName = 'tag_'. (int) $tagId;
				
					$panel['columns'][$codeName] = array(
						'db_column' => 'NULL',
						'search_column' => 
							"(
								SELECT 1
								FROM [[DB_NAME_PREFIX]]image_tag_link AS ". $codeName. "
								WHERE ". $codeName. ".image_id = f.id
								  AND ". $codeName. ".tag_id = ". (int) $tagId. "
							)",
						'filter_format' => 'yes_or_no'
					);
				
					$panel['quick_filter_buttons'][$codeName] = array(
						'ord' => $ord,
						'parent' => 'tags',
						'label' => $tagName,
						'column' => $codeName
					);
				}
			}
		}
		
		break;
		
	
	case 'zenario__modules/panels/modules':
		return require funIncPath(__FILE__, 'modules.preFillOrganizerPanel');
	
	
	case 'zenario__modules/panels/plugins':
		//The usage_layouts column will actually contain two columns
		//If the admin is sorting on this column, make sure that both columns
		//are being used to sort so the sorting appears to happen in a logical way
		$panel['columns']['usage_layouts']['sort_column'] =
			$panel['columns']['usage_layouts']['db_column'].
			', '.
			$panel['columns']['usage_archived_layouts']['db_column'];
	
		$panel['columns']['usage_layouts']['sort_column_desc'] =
			$panel['columns']['usage_layouts']['db_column'].
			' DESC, '.
			$panel['columns']['usage_archived_layouts']['db_column'].
			' DESC';
		break;
	
	
	case 'zenario__layouts/panels/layouts':
		
		if (!checkForChangesInCssJsAndHtmlFiles()) {
			checkForMissingTemplateFiles();
		}
		
		if (isset($_GET['refiner__trash'])) {
			$panel['title'] = adminPhrase('Archived Layouts');
			$panel['no_items_message'] = adminPhrase('No Layouts have been archived.');
			
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__trash'];
			
			unset($panel['columns']['archived']['title']);
			unset($panel['columns']['default']);
			unset($panel['collection_buttons']);
			unset($panel['trash']);
		
		} elseif ($refinerName == 'content_type') {
			unset($panel['trash']);
			unset($panel['columns']['archived']['title']);
			$panel['no_items_message'] = adminPhrase('There are no active Layouts for this Content Type.');
		
		} elseif ($refinerName || in($mode, 'get_item_name', 'get_item_links')) {
			unset($panel['trash']);
			
			if (isset($panel['db_items']['custom_where_statement__without_unregistered'])) {
				$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__without_unregistered'];
			} else {
				unset($panel['db_items']['where_statement']);
			}
		
		} else {
			$panel['trash']['empty'] = !checkRowExists('layouts', array('status' => 'suspended'));
		}
		
		if (isset($_GET['refiner__content_type'])) {
			unset($panel['columns']['content_type']['title']);
		}
		
		if (isset($_GET['refiner__template_family'])) {
			unset($panel['columns']['family_name']['title']);
		}
		
		break;
		

	case 'zenario__layouts/panels/template_families':
	case 'zenario__layouts/panels/skins':
	case 'zenario__layouts/panels/skin_files':
		
		checkForChangesInCssJsAndHtmlFiles();
	
		break;

	
	case 'zenario__languages/panels/languages':
		return require funIncPath(__FILE__, 'languages.preFillOrganizerPanel');

	
	case 'zenario__languages/panels/phrases':
	case 'zenario__languages/nav/vlp/vlp_chained/panel':
		return require funIncPath(__FILE__, 'vlp.preFillOrganizerPanel');

	
}

return false;