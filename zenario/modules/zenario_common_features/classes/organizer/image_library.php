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



class zenario_common_features__organizer__image_library extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		switch ($path) {
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
							adminPhrase('Images attached to the content item [[tag]], version [[version]]',
								array(
									'tag' => formatTagFromTagId(get('refiner__content')),
									'version' => getRow('content_items', 'admin_version', array('tag_id' => get('refiner__content')))));
				
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
		
		}
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch($path) {
			case 'zenario__content/panels/image_library':
				if (in($mode, 'full', 'select', 'quick')) {
					$panel['columns']['tags']['tag_colors'] =
					$panel['columns']['filename']['tag_colors'] = getImageTagColours($byId = false, $byName = true);
			
					foreach ($panel['items'] as $id => &$item) {
						$text = '';
						$comma = false;
				
						if ($item['in_use_anywhere']) {
							$mrg = array('used_on' => 'Used on');
						} else {
							$mrg = array('used_on' => 'Attached to');
						}
				
						$usage_content = (int)$item['usage_content'];
						$usage_plugins = (int)$item['usage_plugins'];
						$usage_menu_nodes = (int)$item['usage_menu_nodes'];
						$contentUsage = $usage_content + $usage_plugins + $usage_menu_nodes;
						if ($contentUsage === 1) {
							if ($usage_content === 1) {
								$sql = '
									SELECT 
										foreign_key_id AS id, 
										foreign_key_char AS type
									FROM ' . DB_NAME_PREFIX . 'inline_images
									WHERE image_id = ' . (int)$item['id'] . '
									AND foreign_key_to = "content"
									AND archived = 0';
								$result = sqlSelect($sql);
								$row = sqlFetchAssoc($result);
						
								$mrg['tag'] = formatTag($row['id'], $row['type']);
								$text .= adminPhrase('[[used_on]] "[[tag]]"', $mrg);
					
							} elseif ($usage_plugins === 1) {
								$sql = '
									SELECT p.name, m.display_name
									FROM ' . DB_NAME_PREFIX . 'inline_images pii
									INNER JOIN ' . DB_NAME_PREFIX . 'plugin_instances p
										ON pii.foreign_key_id = p.id
										AND pii.image_id = ' . (int)$item['id'] . '
										AND pii.foreign_key_to = "library_plugin"
										AND pii.foreign_key_id != 0
									INNER JOIN ' . DB_NAME_PREFIX . 'modules m
										ON p.module_id = m.id';
								$result = sqlSelect($sql);
								$row = sqlFetchAssoc($result);
								if ($row['name'] && $row['display_name']) {
									$text = adminPhrase('Used on plugin "[[name]]" of the module "[[display_name]]"', $row);
								} else {
									$text = adminPhrase('Used on 1 plugin');
								}
							} else {
								$text = adminPhrase('Used on 1 menu node');
							}
						} elseif ($contentUsage > 1) {
							$text .= $mrg['used_on']. ' ';
							if ($usage_content > 0) {
								$text .= nAdminPhrase(
									'[[count]] content item',
									'[[count]] content items',
									$usage_content,
									array('count' => $usage_content)
								);
								$comma = true;
							}
							if ($usage_plugins > 0) {
								if ($comma) {
									$text .= ', ';
								}
								$text .= nAdminPhrase(
									'[[count]] plugin',
									'[[count]] plugins',
									$usage_plugins,
									array('count' => $usage_plugins)
								);
								$comma = true;
							}
							if ($usage_menu_nodes > 0) {
								if ($comma) {
									$text .= ', ';
								}
								$text .= nAdminPhrase(
									'[[count]] menu node',
									'[[count]] menu nodes',
									$usage_menu_nodes,
									array('count' => $usage_menu_nodes)
								);
							}
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
							$result = sqlSelect($sql);
							$row = sqlFetchAssoc($result);
							$mrg['template_name'] = $row['template_name'];
							$text .= adminPhrase('[[used_on]] "[[template_name]]"', $mrg);
				
						} elseif ($usage_email_templates > 1) {
							$mrg['count'] = $usage_email_templates;
							$text = adminPhrase('[[used_on]] [[count]] email templates', $mrg);
						}
						$item['usage_email_templates'] = $text;
					}
				}
				
				break;
				
		
			case 'zenario__content/panels/inline_images_for_content':
				//If we're showing images for content items, remove all of the action-buttons if the current admin doesn't
				//have access to this content item
				$cID = $cType = false;
				if (!getCIDAndCTypeFromTagId($cID, $cType, $refinerId)
				 || !(checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $cID, $cType))) {
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
			
			if ($path == 'zenario__content/panels/image_library') {
				$item['list_image'] = $img. '&ogt=1';
			} else {
				$item['list_image'] = $img. '&ogl=1';
			}
			$item['image'] = $img. '&og=1';
			
			$classes = array();
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
				$item['row_css_class'] = implode(' ', $classes);
			}
			
			if (!empty($item['filename'])
			 && !empty($item['short_checksum'])
			 && !empty($item['duplicate_filename'])) {
				$item['filename'] .= ' ['. $item['short_checksum']. ']';
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		switch ($path) {
			case 'zenario__content/panels/inline_images_for_content':
				if (!$content = getRow('content_items', array('id', 'type', 'admin_version'), array('tag_id' => $refinerId))) {
					exit;
		
				} elseif (post('make_sticky') && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
					updateVersion($content['id'], $content['type'], $content['admin_version'], array('sticky_image_id' => $ids));
					syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
		
				} elseif (post('make_unsticky') && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
					updateVersion($content['id'], $content['type'], $content['admin_version'], array('sticky_image_id' => 0));
					syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
		
				} else {
					$key = array(
						'foreign_key_to' => 'content',
						'foreign_key_id' => $content['id'],
						'foreign_key_char' => $content['type'],
						'foreign_key_version' => $content['admin_version']);
					$privCheck = checkPriv('_PRIV_EDIT_DRAFT', $content['id'], $content['type']);
			
					return require funIncPath('zenario_common_features', 'media.handleOrganizerPanelAJAX');
				}
				
				break;
				
	
			case 'zenario__content/panels/image_library':
				$key = false;
				$privCheck = checkPriv('_PRIV_MANAGE_MEDIA');
		
				return require funIncPath('zenario_common_features', 'media.handleOrganizerPanelAJAX');
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}