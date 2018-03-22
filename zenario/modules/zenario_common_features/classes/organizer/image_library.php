<?php
/*
 * Copyright (c) 2018, Tribal Limited
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



class zenario_common_features__organizer__image_library extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		switch ($path) {
			case 'generic_image_panel':
			case 'zenario__content/panels/inline_images_for_content':
			case 'zenario__content/panels/image_library':


				switch ($refinerName) {
					case 'content':
						$cID = $cType = false;
						ze\content::getCIDAndCTypeFromTagId($cID, $cType, ($_GET['refiner__content'] ?? false));
			
						if (!ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
							unset($panel['collection_buttons']['add']);
							unset($panel['collection_buttons']['upload']);
						}
				
						$panel['title'] =
							ze\admin::phrase('Images attached to the content item [[tag]], version [[version]]',
								[
									'tag' => ze\content::formatTagFromTagId($_GET['refiner__content'] ?? false),
									'version' => ze\row::get('content_items', 'admin_version', ['tag_id' => ($_GET['refiner__content'] ?? false)])]);
				
						break;
			
			
					case 'tag':
						if ($tag = ze\row::get('image_tags', true, ['name' => $refinerId])) {
							$panel['title'] = ze\admin::phrase('Images that use the tag "[[name]]"', $tag);
							$panel['no_items_message'] = ze\admin::phrase('There are no images that use the tag "[[name]]".', $tag);
							$panel['refiners']['tag']['table_join'] .=  (int) $tag['id'];
						} else {
							echo ze\admin::phrase('Tag not found');
							exit;
						}
				
				
						break;
				}
		
				//Don't do anything fancy if we're just looking up a name
				if (ze::in($mode, 'typeahead_search', 'get_item_name', 'get_item_links')) {
					$panel['db_items']['table'] = '[[DB_NAME_PREFIX]]files AS f';
					unset($panel['refiner_required']);
					unset($panel['columns']['usage_file_link']);
					unset($panel['columns']['usage_plugins']);
					unset($panel['columns']['usage_menu']);
					unset($panel['columns']['usage']);
					unset($panel['columns']['in_use']);
					unset($panel['columns']['in_use_here']);
					unset($panel['columns']['in_use_anywhere']);
					unset($panel['columns']['in_use_elsewhere']);
					unset($panel['columns']['sticky_flag']);
					
					if (ze::in($mode, 'get_item_name', 'get_item_links')) {
						unset($panel['db_items']['where_statement']);
					}
		
				} elseif (!$refinerName && $path == 'zenario__content/panels/image_library') {
					$ord = 1000;
			
					$tags = ze\row::getArray('image_tags', 'name', [], 'name');
			
					$panel['quick_filter_buttons']['tags']['hidden'] = false;
			
					if (empty($tags)) {
						$panel['quick_filter_buttons']['tags']['disabled'] = true;
				
						$panel['quick_filter_buttons']['dummy_child'] = [
							'disabled' => true,
							'ord' => $ord,
							'parent' => 'tags',
							'label' => ze\admin::phrase('No tags exist, edit an image or click "Manage tags" to create tags')
						];
			
					} else {
						foreach ($tags as $tagId => $tagName) {
							++$ord;
							$codeName = 'tag_'. (int) $tagId;
				
							$panel['columns'][$codeName] = [
								'db_column' => 'NULL',
								'search_column' => 
									"(
										SELECT 1
										FROM [[DB_NAME_PREFIX]]image_tag_link AS ". $codeName. "
										WHERE ". $codeName. ".image_id = f.id
										  AND ". $codeName. ".tag_id = ". (int) $tagId. "
									)",
								'filter_format' => 'yes_or_no'
							];
				
							$panel['quick_filter_buttons'][$codeName] = [
								'ord' => $ord,
								'parent' => 'tags',
								'label' => $tagName,
								'column' => $codeName
							];
						}
					}
				}
		
		}
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$tallRowsInListView = false;
		
		switch($path) {
			case 'zenario__content/panels/image_library':
				$tallRowsInListView = true;
				
				if (ze::in($mode, 'full', 'select', 'quick')) {
					$panel['columns']['tags']['tag_colors'] =
					$panel['columns']['filename']['tag_colors'] = ze\contentAdm::getImageTagColours($byId = false, $byName = true);
			
					foreach ($panel['items'] as $id => &$item) {
						
						$text = '';
						$otherPlugins = false;
						$otherContentItems = false;
						$usage_content = (int)$item['usage_content'];
						$usage_plugins = (int)$item['usage_plugins'];
						$usage_menu_nodes = (int)$item['usage_menu_nodes'];
				
						if ($item['in_use_anywhere']) {
							$mrg = ['used_on' => 'Used on'];
						} else {
							$mrg = ['used_on' => 'Attached to (not used)'];
						}
						
						if ($usage_content
						 && ($row = ze\sql::fetchAssoc('
								SELECT 
									foreign_key_id AS id, 
									foreign_key_char AS type
								FROM ' . DB_NAME_PREFIX . 'inline_images
								WHERE image_id = '. (int) $id. '
								AND foreign_key_to = "content"
								AND archived = 0
								LIMIT 1
						'))) {
							--$usage_content;
							$otherContentItems = true;
							$mrg['tag'] = ze\content::formatTag($row['id'], $row['type']);
							$text .= ze\admin::phrase('[[used_on]] "[[tag]]"', $mrg);
						
						} else
						if ($usage_plugins
						 && ($row = ze\sql::fetchAssoc('
								SELECT p.name, m.display_name
								FROM ' . DB_NAME_PREFIX . 'inline_images pii
								INNER JOIN ' . DB_NAME_PREFIX . 'plugin_instances p
								   ON pii.foreign_key_id = p.id
								  AND pii.image_id = '. (int) $id. '
								  AND pii.foreign_key_to = "library_plugin"
								  AND pii.foreign_key_id != 0
								INNER JOIN ' . DB_NAME_PREFIX . 'modules m
								   ON p.module_id = m.id
								WHERE p.name != ""
								  AND p.name IS NOT NULL
								LIMIT 1
						'))) {
							--$usage_plugins;
							$otherPlugins = true;
							$text = ze\admin::phrase('Used on plugin "[[name]]" of the module "[[display_name]]"', $row);
						}
						
						if ($usage_content) {
							if ($text) {
								$text .= ', ';
							} else {
								$text = ze\admin::phrase('Used on');
							}
							
							if ($otherContentItems) {
								$text .= ze\admin::nPhrase(
									'[[count]] other content item',
									'[[count]] other content items',
									$usage_content
								);
							} else {
								$text .= ze\admin::nPhrase(
									'[[count]] content item',
									'[[count]] content items',
									$usage_content
								);
							}
						}
						
						if ($usage_plugins) {
							if ($text) {
								$text .= ', ';
							} else {
								$text = ze\admin::phrase('Used on');
							}
							
							if ($otherPlugins) {
								$text .= ze\admin::nPhrase(
									'[[count]] other plugin',
									'[[count]] other plugins',
									$usage_plugins
								);
							} else {
								$text .= ze\admin::nPhrase(
									'[[count]] plugin',
									'[[count]] plugins',
									$usage_plugins
								);
							}
						}
						
						if ($usage_menu_nodes) {
							if ($text) {
								$text .= ', ';
							} else {
								$text = ze\admin::phrase('Used on'). ' ';
							}
							
							$text .= ze\admin::nPhrase(
								'[[count]] menu node',
								'[[count]] menu nodes',
								$usage_menu_nodes
							);
						}
						$item['all_usage_content'] = $text;
				
						$text = '';
						$usage_email_templates = (int)$item['usage_email_templates'];
						if ($usage_email_templates === 1) {
							$sql = '
								SELECT 
									e.template_name
								FROM ' . DB_NAME_PREFIX . 'inline_images ii
								INNER JOIN ' . DB_NAME_PREFIX . 'email_templates e
									ON ii.foreign_key_id = e.id
									AND ii.foreign_key_to = "email_template"
								WHERE image_id = ' . $item['id'] . '
								AND archived = 0';
							$result = ze\sql::select($sql);
							$row = ze\sql::fetchAssoc($result);
							$mrg['template_name'] = $row['template_name'];
							$text .= ze\admin::phrase('[[used_on]] "[[template_name]]"', $mrg);
				
						} elseif ($usage_email_templates > 1) {
							$mrg['count'] = $usage_email_templates;
							$text = ze\admin::phrase('[[used_on]] [[count]] email templates', $mrg);
						}
						$item['usage_email_templates'] = $text;
						
						if ($item['privacy'] == 'auto') {
							$item['tooltip'] = ze\admin::phrase('[[name]] is Hidden. (will become Public when placed on a public content item, or Private when placed on a private content item)', ['name' => $item['filename']]);
						} elseif ($item['privacy'] == 'private') {
							$item['tooltip'] = ze\admin::phrase('[[name]] is Private. (only a logged-in extranet user can access this image via an internal link; URL will change from time to time)', ['name' => $item['filename']]);
						} elseif ($item['privacy'] == 'public') {
							$item['tooltip'] = ze\admin::phrase('[[name]] is Public. (any visitor who knows the public link can access it)', ['name' => $item['filename']]);
						}
						
					}
				}
				
				break;
				
		
			case 'zenario__content/panels/inline_images_for_content':
				$tallRowsInListView = true;
				
				//If we're showing images for content items, remove all of the action-buttons if the current admin doesn't
				//have access to this content item
				$cID = $cType = false;
				if (!ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId)
				 || !(ze\priv::check('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $cID, $cType))) {
					unset($panel['collection_buttons']['add']);
					unset($panel['collection_buttons']['upload']);
					unset($panel['item_buttons']['make_sticky']);
					unset($panel['item_buttons']['make_unsticky']);
					unset($panel['item_buttons']['delete']);
					unset($panel['item_buttons']['remove']);
				}
		}
		
		foreach ($panel['items'] as $id => &$item) {
			
			$img = 'zenario/file.php?c='. $item['checksum'];
			
			if (!empty($panel['key']['usage']) && $panel['key']['usage'] != 'image') {
				$img .= '&usage='. rawurlencode($panel['key']['usage']);
			}
			
			if ($tallRowsInListView) {
				$item['list_image'] = $img. '&ogt=1';
			} else {
				$item['list_image'] = $img. '&ogl=1';
			}
			$item['image'] = $img. '&og=1';
			
			$classes = [];
			if (!empty($item['sticky_flag'])) {
				$classes[] = 'zenario_sticky';
			}
			if (!empty($item['privacy'])) {
				switch ($item['privacy']) {
					case 'auto':
						$classes[] = 'zenario_image_privacy_auto';
						break;
					case 'public':
						$classes[] = 'zenario_image_privacy_public';
						break;
					case 'private':
						$classes[] = 'zenario_image_privacy_private';
						break;
				}
			}
			if (!empty($classes)) {
				$item['row_class'] = implode(' ', $classes);
			}
			
			if (!empty($item['filename'])
			 && !empty($item['short_checksum'])
			 && !empty($item['duplicate_filename'])) {
				$item['filename'] .= ' '. ze\admin::phrase('[checksum [[short_checksum]]]', $item);
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		switch ($path) {
			case 'zenario__content/panels/image_library':
				$key = false;
				$privCheck = ze\priv::check('_PRIV_MANAGE_MEDIA');
		
				return require ze::funIncPath('zenario_common_features', 'media.handleOrganizerPanelAJAX');
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__content/panels/image_library':
				if ($_POST['download_image'] ?? false) {
					$fileId = $ids;
					$file =  ze\row::get('files', ['id', 'filename', 'path'], $fileId);
					if ($file) {
						if ($file['path']) {
							header('Content-Description: File Transfer');
							header('Content-Type: application/octet-stream');
							header('Content-Disposition: attachment; filename="'.$file['filename'].'"');
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header('Content-Transfer-Encoding: binary');
							header('Connection: Keep-Alive');
							header('Expires: 0');
							header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
							header('Pragma: public');
							header('Content-Length: ' . filesize(ze\file::docstorePath($file['id'])));
							
							readfile(ze\file::docstorePath($file['id']));
						} else {
							header('location: '. ze\link::absolute(). 'zenario/file.php?adminDownload=1&download=1&id=' . $file['id']);
						}
						exit;
					}
				}
				break;
		}
	}
}