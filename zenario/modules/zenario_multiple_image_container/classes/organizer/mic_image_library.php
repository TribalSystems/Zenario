<?php
/*
 * Copyright (c) 2023, Tribal Limited
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



class zenario_multiple_image_container__organizer__mic_image_library extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {

    }

    public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$module = 'zenario_multiple_image_container';
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);

		$imageIds = [];
		$usage = [];
		$instances = ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['image'])) {
				foreach (explode(',', $instance['settings']['image']) as $imageId) {
					$imageIds[] = $imageId;
					if ($instance['egg_id']) {
						if (!isset($usage[$imageId]['nest'])) {
							$usage[$imageId]['nest'] = $instance['instance_id'];
							$usage[$imageId]['nests'] = 1;
						} else {
							$usage[$imageId]['nests']++;
						}
					} else {
						if (!isset($usage[$imageId]['plugin'])) {
							$usage[$imageId]['plugin'] = $instance['instance_id'];
							$usage[$imageId]['plugins'] = 1;
						} else {
							$usage[$imageId]['plugins']++;
						}
					}
				}
			}
		}
		
		if (!empty($imageIds)) {
			//Remove duplicates
			$imageIds = array_unique($imageIds);
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['in_use_anywhere'] = in_array($id, $imageIds);

			if (!empty($usage[$id])) {
				$usageLinks = self::imageUsageLinks($id);
				$item['where_used'] = implode('; ', ze\miscAdm::getUsageText($usage[$id], $usageLinks));
			}

			$item['image'] = 'zenario/file.php?c='. $item['checksum'] . '&usage=mic&og=1';
			
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

    public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//Upload a new file
		if (ze::post('upload') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			
			ze\fileAdm::exitIfUploadError(false, false, true, 'Filedata');
			
			//Check to see if an identical file has already been uploaded
			$existingFilename = false;
			if ($_FILES['Filedata']['tmp_name']
			 && ($existingChecksum = md5_file($_FILES['Filedata']['tmp_name']))
			 && ($existingChecksum = ze::base16To64($existingChecksum))) {
				$existingFilename = ze\row::get('files', 'filename', ['checksum' => $existingChecksum, 'usage' => 'mic']);
			}
			
			//Try to add the uploaded image to the database
			$fileId = ze\file::addToDatabase('mic', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), $mustBeAnImage = true, $deleteWhenDone = false, $addToDocstoreDirIfPossible = true);

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
		} elseif (ze::post('copy_to_image_library')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				if ($file = ze\row::get('files', ['filename', 'location', 'path', 'image_credit'], $id)) {
					$location = ze\file::docstorePath($file['path']);
					ze\file::addToDatabase('image', $location, $file['filename'], $mustBeAnImage = true, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, false, false, false, false, $file['image_credit']);
				}
			}
		} elseif (ze::post('delete') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\contentAdm::deleteUnusedImage($id);
			}
		
		//Delete images, even if they're used
		} elseif (ze::get('delete_in_use') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			$idsArray = ze\ray::explodeAndTrim($ids, true);
			$count = count($idsArray);
			if ($count == 1) {
				$id = $idsArray[0];
				$mrg = ze\row::get('files', ['filename'], $id);
				$usageLinks = self::imageUsageLinks($id);
				$usage = ze\fileAdm::getMICImageUsage($id);
				
				echo '
					<p>', ze\admin::phrase('Are you sure you wish to delete the image &quot;[[filename]]&quot;? It is in use in the following places:', $mrg), '</p>
					<ul><li>', implode('</li><li>', $usage), '</li></ul>';
			} elseif ($count > 0) {
				$usedImages = $unusedImaged = 0;
				foreach ($idsArray as $id) {
					if (ze\fileAdm::getMICImageUsage($id)) {
						$usedImages++;
					} else {
						$unusedImaged++;
					}
				}

				if ($usedImages > 0) {
					if ($unusedImaged > 0) {
						$phrase = ze\admin::nPhrase(
							'You have selected [[total_images]] images for deletion, but one of them is in use in Multiple Image Container(s). Are you sure you wish to delete them?',
							'You have selected [[total_images]] images for deletion, but [[unused_images]] of them are in use in Multiple Image Container(s). Are you sure you wish to delete them?',
							$unusedImaged,
							['unused_images' => $unusedImaged, 'total_images' => $count]
						);
					} else {
						$phrase = ze\admin::phrase(
							'You have selected [[used_images]] images for deletion. All of them are in use in Multiple Image Container(s). Are you sure you wish to delete them?',
							['used_images' => $usedImages]
						);
					}
				}

				echo '
					<p>', $phrase;
			}
		
		} elseif (ze::post('delete_in_use') && ze\priv::check('_PRIV_MANAGE_MEDIA')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\contentAdm::deleteImage($id);
			}
		}
    }

    public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		ze\file::stream($ids);
		exit;
	}

	protected function imageUsageLinks($id) {
		return [
			'plugins' => 'zenario__library/panels/image_library/hidden_nav/plugins_using_image//'. (int) $id. '//',
			'nests' => 'zenario__library/panels/image_library/hidden_nav/nests_using_image//'. (int) $id. '//'
		];
	}
}