<?php
/*
 * Copyright (c) 2014, Tribal Limited
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
	case 'plugin_settings':
		if (!inc('zenario_ctype_picture')) {
			unset($box['tabs']['first_tab']['fields']['image_source']['values']['_PICTURE']);
		}
	
		
		$box['tabs']['first_tab']['fields']['use_rollover']['hidden'] = 
		$box['tabs']['first_tab']['fields']['image']['hidden'] = 
			$values['first_tab/image_source'] != '_CUSTOM_IMAGE';
		
		$box['tabs']['first_tab']['fields']['picture']['hidden'] =
			$values['first_tab/image_source'] != '_PICTURE';
		
		
		$cID = $cType = $pictureCID = $pictureCType = $imageId = false;
		(($values['first_tab/image_source'] == '_CUSTOM_IMAGE'
		  && ($imageId = $values['first_tab/image']))
		
		 || ($values['first_tab/image_source'] == '_PICTURE'
		  && (getCIDAndCTypeFromTagId($pictureCID, $pictureCType, $values['first_tab/picture']))
		  && ($imageId = getRow("versions", "file_id", array("id" => $pictureCID, 'type' => $pictureCType, "version" => contentVersion($pictureCID, $pictureCType)))))
		 
		 || ($values['first_tab/image_source'] == '_STICKY_IMAGE'
		  && (getCIDAndCTypeFromTagId($cID, $cType, $values['destination/hyperlink_target']))
		  && ($imageId = itemStickyImageId($cID, $cType))));
		
		$box['tabs']['first_tab']['fields']['canvas']['hidden'] = 
		$box['tabs']['first_tab']['fields']['alt_tag']['hidden'] = 
		$box['tabs']['first_tab']['fields']['image_title']['hidden'] = 
			!$imageId;
		
		$box['tabs']['first_tab']['fields']['floating_box_title']['hidden'] = 
			!$imageId
		 || $values['first_tab/image_source'] == '_STICKY_IMAGE'
		 || $values['destination/link_type'] != '_ENLARGE_IMAGE';
		
		if ($imageId && $image = getRow('files', array('width', 'height', 'alt_tag', 'title', 'floating_box_title'), $imageId)) {
			$editModeOn = engToBooleanArray($box['tabs']['first_tab'], 'edit_mode', 'on');
			
			$box['tabs']['first_tab']['fields']['alt_tag']['multiple_edit']['original_value'] = $image['alt_tag'];
			if ($box['first_display'] && !$values['first_tab/alt_tag']) {
				$box['tabs']['first_tab']['fields']['alt_tag']['value'] = $image['alt_tag'];
			}
			if ($editModeOn && !$changes['first_tab/alt_tag']) {
				$box['tabs']['first_tab']['fields']['alt_tag']['current_value'] = $image['alt_tag'];
			}

			$box['tabs']['first_tab']['fields']['image_title']['multiple_edit']['original_value'] = $image['title'];
			if ($box['first_display'] && !$values['first_tab/image_title']) {
				$box['tabs']['first_tab']['fields']['image_title']['value'] = $image['title'];
			}
			if ($editModeOn && !$changes['first_tab/image_title']) {
				$box['tabs']['first_tab']['fields']['image_title']['current_value'] = $image['title'];
			}

			$box['tabs']['first_tab']['fields']['floating_box_title']['multiple_edit']['original_value'] = $image['floating_box_title'];
			if ($box['first_display'] && !$values['first_tab/floating_box_title']) {
				$box['tabs']['first_tab']['fields']['floating_box_title']['value'] = $image['floating_box_title'];
			}
			if ($editModeOn && !$changes['first_tab/floating_box_title']) {
				$box['tabs']['first_tab']['fields']['floating_box_title']['current_value'] = $image['floating_box_title'];
			}
		
			$this->getImageHtmlSnippet($values['image'], $fields['image_thumbnail']['snippet']['html']);
			$this->getImageHtmlSnippet($values['rollover_image'], $fields['rollover_image_thumbnail']['snippet']['html']);
				
		} else {
			$box['tabs']['first_tab']['fields']['alt_tag']['multiple_edit']['original_value'] = '';
			$box['tabs']['first_tab']['fields']['image_title']['multiple_edit']['original_value'] = '';
			$box['tabs']['first_tab']['fields']['floating_box_title']['multiple_edit']['original_value'] = '';
		}
		
		$box['first_display'] = false;
		

		$box['tabs']['first_tab']['fields']['width']['hidden'] = 
			$box['tabs']['first_tab']['fields']['canvas']['hidden']
		 || !in($values['first_tab/canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');

		$box['tabs']['first_tab']['fields']['height']['hidden'] = 
			$box['tabs']['first_tab']['fields']['canvas']['hidden']
		 || !in($values['first_tab/canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');

		$box['tabs']['first_tab']['fields']['offset']['hidden'] = 
			$box['tabs']['first_tab']['fields']['canvas']['hidden']
		 || $values['first_tab/canvas'] != 'resize_and_crop';

		$box['tabs']['first_tab']['fields']['rollover_image']['hidden'] = 
			$box['tabs']['first_tab']['fields']['use_rollover']['hidden']
		 || !$values['first_tab/use_rollover'];
		
		
		if (isset($box['tabs']['text']['fields']['use_phrases'])) {
			$box['tabs']['text']['fields']['use_phrases']['hidden'] =
				getNumLanguages() <= 1
			 && strpos($values['text/text'], '[[') === false
			 && strpos($values['text/text'], ']]') === false
			 && strpos($values['text/title'], '[[') === false
			 && strpos($values['text/title'], ']]') === false;
		}
		
		
		$box['tabs']['destination']['fields']['hyperlink_target']['hidden'] = 
		$box['tabs']['destination']['fields']['hide_private_item']['hidden'] = 
		$box['tabs']['destination']['fields']['use_download_page']['hidden'] = 
			$values['destination/link_type'] != '_CONTENT_ITEM';

		$box['tabs']['destination']['fields']['get_translation']['hidden'] = 
			$values['destination/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled']
		 || getNumLanguages() < 2;

		$box['tabs']['destination']['fields']['target_blank']['hidden'] = 
			$values['destination/link_type'] != '_CONTENT_ITEM'
		 && $values['destination/link_type'] != '_EXTERNAL_URL';

		$box['tabs']['destination']['fields']['use_translation']['hidden'] = 
			$values['destination/link_type'] != '_CONTENT_ITEM'
		 || $box['key']['isVersionControlled'];

		$box['tabs']['destination']['fields']['url']['hidden'] = 
			$values['destination/link_type'] != '_EXTERNAL_URL';

		$box['tabs']['destination']['fields']['enlarge_canvas']['hidden'] = 
			$values['destination/link_type'] != '_ENLARGE_IMAGE';

		$box['tabs']['destination']['fields']['enlarge_width']['hidden'] = 
			$box['tabs']['destination']['fields']['enlarge_canvas']['hidden']
		 || ($values['destination/enlarge_canvas'] != 'fixed_width'
		  && $values['destination/enlarge_canvas'] != 'fixed_width_and_height');

		$box['tabs']['destination']['fields']['enlarge_height']['hidden'] = 
			$box['tabs']['destination']['fields']['enlarge_canvas']['hidden']
		 || ($values['destination/enlarge_canvas'] != 'fixed_height'
		  && $values['destination/enlarge_canvas'] != 'fixed_width_and_height');
		
		$cID = $cType = false;
		if ($values['destination/link_type'] == '_CONTENT_ITEM'
		 && (getCIDAndCTypeFromTagId($cID, $cType, $values['destination/hyperlink_target']))
		 && ($cType == 'document')) {
			$box['tabs']['destination']['fields']['use_download_page']['hidden'] = false;
		} else {
			$box['tabs']['destination']['fields']['use_download_page']['current_value'] = false;
			$box['tabs']['destination']['fields']['use_download_page']['hidden'] = true;
		}
		
		if	(($values['first_tab/image_source']  == '_CUSTOM_IMAGE' && !($values['first_tab/use_rollover']))
			|| $values['first_tab/image_source']  == '_PICTURE' ) {
				$box['tabs']['destination']['fields']['link_type']['values']['_ENLARGE_IMAGE'] = array('ord'=>4,'label'=>'Enlarge image in fancy box');
		} else {
			unset($box['tabs']['destination']['fields']['link_type']['values']['_ENLARGE_IMAGE']);
			if ($values['destination/link_type']=='_ENLARGE_IMAGE') {
				$box['tabs']['destination']['fields']['link_type']['current_value'] = '_NO_LINK';
				$box['tabs']['destination']['fields']['link_type']['value'] = '_NO_LINK';
			}
		}
		
		
		if (isset($box['tabs']['first_tab']['fields']['canvas'])
		 && empty($box['tabs']['first_tab']['fields']['canvas']['hidden'])) {
			if ($values['first_tab/canvas'] == 'fixed_width') {
				$box['tabs']['first_tab']['fields']['width']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
			
			} else {
				unset($box['tabs']['first_tab']['fields']['width']['note_below']);
			}
			
			if ($values['first_tab/canvas'] == 'fixed_height'
			 || $values['first_tab/canvas'] == 'fixed_width_and_height') {
				$box['tabs']['first_tab']['fields']['height']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
			
			} elseif ($values['first_tab/canvas'] == 'resize_and_crop') {
				$box['tabs']['first_tab']['fields']['height']['note_below'] =
					adminPhrase('Images may be scaled up or down maintaining aspect ratio.');
			
			} else {
				unset($box['tabs']['first_tab']['fields']['height']['note_below']);
			}
		}
		
		if (isset($box['tabs']['destination']['fields']['enlarge_canvas'])
		 && empty($box['tabs']['destination']['fields']['enlarge_canvas']['hidden'])) {
			if ($values['destination/enlarge_canvas'] == 'fixed_width') {
				$box['tabs']['destination']['fields']['enlarge_width']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
			
			} else {
				unset($box['tabs']['destination']['fields']['enlarge_width']['note_below']);
			}
			
			if ($values['destination/enlarge_canvas'] == 'fixed_height'
			 || $values['destination/enlarge_canvas'] == 'fixed_width_and_height') {
				$box['tabs']['destination']['fields']['enlarge_height']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio, but will never be scaled up.');
			
			} else {
				unset($box['tabs']['destination']['fields']['enlarge_height']['note_below']);
			}
		}

		break;
}