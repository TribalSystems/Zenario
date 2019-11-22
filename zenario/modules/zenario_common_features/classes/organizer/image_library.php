<?php
/*
 * Copyright (c) 2019, Tribal Limited
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
		
		
		
		
		switch ($refinerName) {
			case 'images_for_content_item':
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId);
	
				if (!ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
					unset($panel['collection_buttons']['add']);
					unset($panel['collection_buttons']['upload']);
				}
		
				$panel['title'] =
					ze\admin::phrase('Images attached to the content item [[tag]], version [[version]]', [
						'tag' => ze\content::formatTag($cID, $cType),
						'version' => ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType])
					]);
				
				//If we're showing images for content items, remove all of the action-buttons if the current admin doesn't
				//have access to this content item
				if (!ze\priv::check('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $cID, $cType)) {
					unset(
						$panel['collection_buttons']['add'],
						$panel['collection_buttons']['upload'],
						$panel['item_buttons']['flag_as_feature'],
						$panel['item_buttons']['unflag_as_feature'],
						$panel['item_buttons']['delete'],
						$panel['item_buttons']['remove']
					);
				}
				
				unset($panel['item_buttons']['send_to_documents']);
		
				break;
			
			
			case 'images_for_newsletter':
				$details = [];
				if (ze\module::inc('zenario_newsletter')) {
					$details = zenario_newsletter::details($refinerId);
				}
				
				$panel['title'] = ze\admin::phrase('Images in the Newsletter "[[newsletter_name]]"', $details);
				$panel['no_items_message'] = ze\admin::phrase('There are no images in this Newsletter.');
		
				if ($details['status'] != '_DRAFT') {
					unset($panel['collection_buttons']['add']);
					unset($panel['collection_buttons']['upload']);
					unset($panel['collection_buttons']['delete_inline_file']);
				}
				
				unset(
					$panel['columns']['is_featured_image'],
					$panel['item_buttons']['flag_as_feature'],
					$panel['item_buttons']['unflag_as_feature']
				);
				
				unset($panel['item_buttons']['send_to_documents']);
				
				break;

	
			case 'tag':
				if ($tag = ze\row::get('image_tags', true, ['name' => $refinerId])) {
					$panel['title'] = ze\admin::phrase('Images that use the tag "[[name]]"', $tag);
					$panel['no_items_message'] = ze\admin::phrase('There are no images that use the tag "[[name]]".', $tag);
					$panel['refiners']['tag']['table_join'] .=  (int) $tag['id'];
					unset($panel['item_buttons']['send_to_documents']);
				} else {
					echo ze\admin::phrase('Tag not found');
					exit;
				}
			
			default:
				unset(
					$panel['columns']['in_use_here'],
					$panel['columns']['is_featured_image'],
					$panel['collection_buttons']['add'],
					$panel['item_buttons']['flag_as_feature'],
					$panel['item_buttons']['unflag_as_feature'],
					$panel['item_buttons']['remove']
				);
		
		
				break;
		}

		$addFullDetails = ze::in($mode, 'full', 'quick', 'select');
		
		
		//Don't do anything fancy if we're just looking up a name or something
		if (!$addFullDetails) {
			$panel['db_items']['table'] = '[[DB_PREFIX]]files AS f';
			unset($panel['refiner_required']);
			unset($panel['columns']['usage_file_link']);
			unset($panel['columns']['usage_plugins']);
			unset($panel['columns']['usage_menu']);
			unset($panel['columns']['usage']);
			unset($panel['columns']['in_use']);
			unset($panel['columns']['in_use_here']);
			unset($panel['columns']['in_use_anywhere']);
			unset($panel['columns']['in_use_elsewhere']);
			unset($panel['columns']['is_featured_image']);
			
			if (ze::in($mode, 'get_item_name', 'get_item_links')) {
				unset($panel['db_items']['where_statement']);
			}
		
		} else {
			
			
			//Don't show archived images in the image library, or the "view images using tags" refiner
			if (!$refinerName || $refinerName == 'tag') {
				$panel['db_items']['where_statement'] .= '
					AND f.archived = 0';
			}
			
			
			//If this is the image library, add quick-filters for images tags
			if (!$refinerName) {
				$ord = 1000;
	
				$tags = ze\row::getValues('image_tags', 'name', [], 'name');
	
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
									FROM [[DB_PREFIX]]image_tag_link AS ". $codeName. "
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
	
	protected function imageUsageLinks($id) {
		return [
			'plugins' => 'zenario__content/panels/image_library/hidden_nav/plugins_using_image//'. (int) $id. '//',
			'nests' => 'zenario__content/panels/image_library/hidden_nav/nests_using_image//'. (int) $id. '//',
			'slideshows' => 'zenario__content/panels/image_library/hidden_nav/slideshows_using_image//'. (int) $id. '//',
			'content_items' => 'zenario__content/panels/image_library/hidden_nav/content_items_using_image//'. (int) $id. '//',

			'menu_nodes' => 'zenario__content/panels/image_library/hidden_nav/menu_nodes_using_image//'. (int) $id. '//',
			'email_templates' => 'zenario__content/panels/image_library/hidden_nav/email_templates_using_image//'. (int) $id. '//',
			'newsletters' => 'zenario__content/panels/image_library/hidden_nav/newsletters_using_image//'. (int) $id. '//',
			'newsletter_templates' => 'zenario__content/panels/image_library/hidden_nav/newsletter_templates_using_image//'. (int) $id. '//'
		];
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$addFullDetails = ze::in($mode, 'full', 'quick', 'select');
		
		$tallRowsInListView = true;	//Is this ever false now..?
		
		if ($addFullDetails) {
			$panel['columns']['tags']['tag_colors'] =
			$panel['columns']['filename']['tag_colors'] = ze\contentAdm::getImageTagColours($byId = false, $byName = true);
			
			foreach ($panel['items'] as $id => &$item) {
				
				
				if (!empty($item['in_use_anywhere'])) {
					$usageLinks = self::imageUsageLinks($id);
					$usage = ze\fileAdm::getImageUsage($id);
					$item['where_used'] = implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
				}
				
				
				if (isset($item['privacy'])) {
					if ($item['privacy'] == 'auto') {
						$item['tooltip'] = ze\admin::phrase('[[name]] is Hidden. (will become Public when placed on a public content item, or Private when placed on a private content item)', ['name' => htmlspecialchars($item['filename'])]);
					} elseif ($item['privacy'] == 'private') {
						$item['tooltip'] = ze\admin::phrase('[[name]] is Private. (only a logged-in extranet user can access this image via an internal link; URL will change from time to time)', ['name' => htmlspecialchars($item['filename'])]);
					} elseif ($item['privacy'] == 'public') {
						$item['tooltip'] = ze\admin::phrase('[[name]] is Public. (any visitor who knows the public link can access it)', ['name' => htmlspecialchars($item['filename'])]);
					}
				}
			}
		}
		
		foreach ($panel['items'] as $id => &$item) {
			
			$img = 'zenario/file.php?c='. $item['checksum'];
			
			if (!empty($panel['key']['usage']) && $panel['key']['usage'] != 'image') {
				$img .= '&usage='. rawurlencode($panel['key']['usage']);
			}
			$item['image'] = $img. '&og=1';
			
			$classes = [];
			if (!empty($item['is_featured_image'])) {
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
	
	
	

	protected static function setFeatureImage($content, $imageId = 0) {
		ze\contentAdm::updateVersion($content['id'], $content['type'], $content['admin_version'], ['feature_image_id' => $imageId]);
		ze\contentAdm::syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
	}
	
	//If this is an image upload, or an image was picked from the library,
	//and the "Flag the first-uploaded image as feature image" option is enabled for this content type,
	//make the first image the feature image if there wasn't already a feature image
	protected static function setFirstUploadedImageAsFeatureImage($content, $imageId) {
		if (ze\row::get('content_types', 'auto_flag_feature_image', ['content_type_id' => $content['type']])
		 && !ze\row::get('content_item_versions', 'feature_image_id', ['id' => $content['id'], 'type' => $content['type'], 'version' => $content['admin_version']])) {
			self::setFeatureImage($content, $imageId);
		}
	}
	
	
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($refinerName) {
			case 'images_for_content_item':
				if (!$content = ze\row::get('content_items', ['id', 'type', 'admin_version'], ['tag_id' => $refinerId])) {
					exit;

				} elseif (($_POST['flag_as_feature'] ?? false) && ze\priv::check('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
					self::setFeatureImage($content, $ids);
					return;

				} elseif (($_POST['unflag_as_feature'] ?? false) && ze\priv::check('_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE', $content['id'], $content['type'])) {
					self::setFeatureImage($content, 0);
					return;

				} else {
					$key = [
						'foreign_key_to' => 'content',
						'foreign_key_id' => $content['id'],
						'foreign_key_char' => $content['type'],
						'foreign_key_version' => $content['admin_version']];
					$privCheck = ze\priv::check('_PRIV_EDIT_DRAFT', $content['id'], $content['type']);
				}
				
				break;
				
			
			case 'images_for_newsletter':
				$key = [
					'foreign_key_to' => 'newsletter',
					'foreign_key_id' => $refinerId];
				$privCheck = ze\priv::check('_PRIV_EDIT_NEWSLETTER');
				
				break;
			
			
			case 'tag':
			default:
				$key = false;
				$privCheck = ze\priv::check('_PRIV_MANAGE_MEDIA');
				
				break;
		}
		
		//Upload a new file
		if (($_POST['upload'] ?? false) && $privCheck) {

			//Check to see if an identical file has already been uploaded
			$existingFilename = false;
			if ($_FILES['Filedata']['tmp_name']
			 && ($existingChecksum = md5_file($_FILES['Filedata']['tmp_name']))
			 && ($existingChecksum = ze::base16To64($existingChecksum))) {
				$existingFilename = ze\row::get('files', 'filename', ['checksum' => $existingChecksum, 'usage' => 'image']);
			}

			//Try to add the uploaded image to the database
			$fileId = ze\file::addToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true);

			if ($fileId) {

				//If this was a content item or newsletter, attach the uploaded image to the content item/newsletter
				if ($key) {
					$key['image_id'] = $fileId;
					ze\row::set('inline_images', [], $key);
				}
				
				if ($refinerName == 'images_for_content_item') {
					self::setFirstUploadedImageAsFeatureImage($content, $fileId);
				
				//If uploading an image when viewing an image tag, assign that tag to the new image
				} elseif ($refinerName == 'tag') {
					if ($imageTagId = ze\row::get('image_tags', 'id', ['name' => $refinerId])) {
						ze\row::set('image_tag_link', [], ['tag_id' => $imageTagId, 'image_id' => $fileId]);
					}
				}

				if ($existingFilename && $existingFilename != $_FILES['Filedata']['name']) {
					echo '<!--Message_Type:Warning-->',
						ze\admin::phrase('This file already existed on the system, but with a different name. "[[old_name]]" has now been renamed to "[[new_name]]".',
							['old_name' => $existingFilename, 'new_name' => $_FILES['Filedata']['name']]);
				} else {
					echo 1;
				}


				return $fileId;

			} else {
				echo ze\admin::phrase('Please upload a valid GIF, JPG, PNG or SVG image.');
				return false;
			}

		//Add an image from the library
		} elseif (($_POST['add'] ?? false) && $key && $privCheck) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $i => $id) {
				$key['image_id'] = $id;
				ze\row::set('inline_images', [], $key);
				
				if (!$i) {
					if (!$refinerName == 'images_for_content_item') {
						self::setFirstUploadedImageAsFeatureImage($content, $id);
					}
				}
			}
			return $ids;

		//Mark images as public
		} elseif (($_POST['mark_as_public'] ?? false) && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\row::update('files', ['privacy' => 'public'], $id);
			}

		//Mark images as private
		} elseif (($_POST['mark_as_private'] ?? false) && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\row::update('files', ['privacy' => 'private'], $id);
				ze\file::deletePublicImage($id);
			}

		//Delete an unused image
		} elseif (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\contentAdm::deleteUnusedImage($id);
			}
		
		//Delete images, even if they're used
		} elseif (ze::get('delete_in_use') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			$mrg = ze\row::get('files', ['filename'], $ids);
			$usageLinks = self::imageUsageLinks($ids);
			$usage = ze\fileAdm::getImageUsage($ids);
			
			echo '
				<p>', ze\admin::phrase('Are you sure you wish to delete the image &quot;[[filename]]&quot;? It is in use in the following places:', $mrg), '</p>
				<ul><li>', implode('</li><li>', ze\miscAdm::getUsageText($usage, $usageLinks, $fullPath = true)), '</li></ul>';
		
		} elseif (ze::post('delete_in_use') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			ze\contentAdm::deleteImage($ids);

		//Detach an image from a content item or newsletter
		} elseif (($_POST['remove'] ?? false) && $key && $privCheck) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				$key['image_id'] = $id;
				$key['in_use'] = 0;
				ze\row::delete('inline_images', $key);
			}
		
		} elseif ($_POST['view_public_link'] ?? false) {

			$rememberWhatThisWas = ze::$mustUseFullPath;
			ze::$mustUseFullPath = false;

			$width = $height = $url = false;
			if (ze\file::imageLink($width, $height, $url, $ids)) {

				echo
					'<!--Message_Type:Success-->',
					'<h3>', ze\admin::phrase('The URL to your image is shown below:'), '</h3>',
					'<p>', ze\admin::phrase('Full hyperlink:<br/>[[full]]<br/><br/>Internal hyperlink:<br/>[[internal]]<br/>',
						[
							'full' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars(ze\link::absolute(). $url). '"/>',
							'internal' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars($url). '"/>'
					]), '</p>',
					'<p>', ze\admin::phrase('If you later make this image private, these links will stop working.'), '</p>';

			} else {
				echo
					'<!--Message_Type:Error-->',
					ze\admin::phrase('Could not generate public link');
			}

			ze::$mustUseFullPath = $rememberWhatThisWas;
		
		
		//Send an image to Documents
		} elseif ($_POST['send_to_documents'] ?? false) {
			if ($file = \ze\row::get('files', ['filename', 'location', 'data', 'privacy'], $ids)) {
			
				if ($file['location'] == 'db') {
				
					if ($fileId = \ze\file::addFromString(
						'documents',
						$file['data'], $file['filename'],
						$mustBeAnImage = true, $addToDocstoreDirIfPossible = true
					)) {
						if ($file['privacy'] == 'auto') {
							$file['privacy'] = 'offline';
						}
			
						//If the image is public, a public link will be automatically generated.
						ze\document::create($fileId, $file['filename'], 0, $file['privacy']);
					}
				}
			}
			
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		ze\file::stream($ids);
		exit;
	}
}