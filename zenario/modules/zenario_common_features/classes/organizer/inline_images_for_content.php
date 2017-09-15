<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

//The "Images for content items" panel is handled using the same logic as the image library
includeModuleSubclass('zenario_common_features', 'organizer', 'zenario__content/panels/image_library');

class zenario_common_features__organizer__inline_images_for_content extends zenario_common_features__organizer__image_library {
	
	protected static function setFeatureImage($content, $imageId = 0) {
		updateVersion($content['id'], $content['type'], $content['admin_version'], array('feature_image_id' => $imageId));
		syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (!$content = getRow('content_items', array('id', 'type', 'admin_version'), array('tag_id' => $refinerId))) {
			exit;

		} elseif (($_POST['make_sticky'] ?? false) && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
			self::setFeatureImage($content, $ids);

		} elseif (($_POST['make_unsticky'] ?? false) && checkPriv('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
			self::setFeatureImage($content, 0);

		} else {
			$key = array(
				'foreign_key_to' => 'content',
				'foreign_key_id' => $content['id'],
				'foreign_key_char' => $content['type'],
				'foreign_key_version' => $content['admin_version']);
			$privCheck = checkPriv('_PRIV_EDIT_DRAFT', $content['id'], $content['type']);
	
			$return = require funIncPath('zenario_common_features', 'media.handleOrganizerPanelAJAX');
			
			//If this is an image upload, or an image was picked from the library,
			//and the "Flag the first-uploaded image as feature image" option is enabled for this content type,
			//make the first image the feature image if there wasn't already a feature image
			if (!empty($return)
			 && (($_POST['upload'] ?? false) || ($_POST['add'] ?? false))
			 && getRow('content_types', 'auto_flag_feature_image', ['content_type_id' => $content['type']])
			 && !getRow('content_item_versions', 'feature_image_id', ['id' => $content['id'], 'type' => $content['type'], 'version' => $content['admin_version']])) {
				$imageIds = explodeAndTrim($return);
				self::setFeatureImage($content, $imageIds[0]);
			}
			
			return $return;
		}
	}
	
}