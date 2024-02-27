<?php
/*
 * Copyright (c) 2024, Tribal Limited
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



class zenario_common_features__organizer__special_images extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {

    }

    public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
    	$imagesInUseIds = [];
    	$siteSettingNames = ['favicon', 'mobile_icon', 'default_icon', 'custom_logo', 'admin_link_custom_logo', 'custom_organizer_favicon'];
	
		foreach ($siteSettingNames as $siteSettingName) {
			if (ze::in($siteSettingName, 'custom_logo', 'admin_link_logo', 'custom_organizer_favicon')) {
				
				if ($siteSettingName == 'custom_logo') {
					$dependentSetting = ze::setting('brand_logo');
				} elseif ($siteSettingName == 'admin_link_custom_logo') {
					 $dependentSetting = ze::setting('admin_link_logo');
				} elseif ($siteSettingName == 'custom_organizer_favicon') {
					$dependentSetting = ze::setting('organizer_favicon');
				}
				
				if ($dependentSetting != 'custom') {
					continue;
				}
			}
			
			$settingValue = ze::setting($siteSettingName);
			if ($settingValue) {
				$imagesInUseIds[] = $settingValue;
			}
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['image'] = 'zenario/file.php?c='. $item['checksum'] . '&usage=site_setting&og=1';
			
			$classes = [];
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
			
			if (in_array($id, $imagesInUseIds)) {
				$item['image_in_use'] = true;
			}
		}
    }

    public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//Upload a new file
		if (ze::post('upload') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			
			ze\fileAdm::exitIfUploadError(false, false, true, 'Filedata');
			
			//Check to see if an identical file has already been uploaded
			$existingFilename = false;
			if ($_FILES['Filedata']['tmp_name']
			 && ($existingChecksum = md5_file($_FILES['Filedata']['tmp_name']))
			 && ($existingChecksum = ze::base16To64($existingChecksum))) {
				$existingFilename = ze\row::get('files', 'filename', ['checksum' => $existingChecksum, 'usage' => 'site_setting']);
			}
			
			//Try to add the uploaded image to the database
			$fileId = ze\file::addToDatabase('site_setting', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), $mustBeAnImage = true, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false);

			if ($fileId) {

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
		} elseif (ze::post('copy_to_image_library') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				if ($file = ze\row::get('files', ['filename', 'location', 'path', 'image_credit'], $id)) {
					ze\file::copyInDatabase('image', $id, $file['filename'], $mustBeAnImage = true, $addToDocstoreDirIfPossible = false);
				}
			}
		} elseif (ze::post('delete') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\contentAdm::deleteImage($id);
			}
		//Add an image from the library
		} elseif (ze::post('add_from_image_library') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			
			$newIds = [];
			foreach (ze\ray::explodeAndTrim($ids, true) as $i => $id) {
				if ($file = ze\row::get('files', ['filename', 'location', 'path', 'image_credit'], $id)) {
					$newIds[] = ze\file::copyInDatabase('site_setting', $id, $file['filename'], $mustBeAnImage = true, $addToDocstoreDirIfPossible = false);
				}
			}
			return $newIds;
		}
    }

    public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		ze\file::stream($ids);
		exit;
	}
}