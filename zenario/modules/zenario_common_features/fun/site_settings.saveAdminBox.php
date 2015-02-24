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


if (!checkPriv('_PRIV_EDIT_SITE_SETTING')) {
	exit;
}


//Loop through each field that would be in the Admin Box, and has the <site_setting> tag set
foreach ($box['tabs'] as $tabName => &$tab) {
	
	$workingCopyImages = $thumbnailWorkingCopyImages = false;
	$jpegOnly = true;
	
	if (!isInfoTag($tabName) && engToBooleanArray($box, 'tabs', $tabName, 'edit_mode', 'on')) {
		foreach ($tab['fields'] as $fieldName => &$field) {
			if (!isInfoTag($fieldName)) {
				if (!arrayKey($field, 'read_only') && $setting = arrayKey($field, 'site_setting', 'name')) {
					
					//Get the value of the setting. Hidden fields should count as being empty
					if (engToBooleanArray($box, 'tabs', $tabName, 'fields', $fieldName, 'hidden')) {
						$value = '';
					} else {
						$value = arrayKey($values, $tabName. '/'. $fieldName);
					}
					
					//Setting the primary domain to "none" should count as being empty
					if ($setting == 'primary_domain') {
						if ($value == 'none') {
							$value = '';
						}
					}
					
					//On multisite sites, don't allow local Admins to change the directory paths
					if (in($setting, 'backup_dir', 'docstore_dir') && globalDBDefined() && !session('admin_global_id')) {
						continue;
					}
					
					//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
					//Convert the format back when saving
					if (($setting == 'working_copy_image_size' && empty($values['image_sizes/working_copy_image']))
					 || ($setting == 'thumbnail_wc_image_size' && empty($values['image_sizes/thumbnail_wc']))) {
						$value = '';
					}
					
					$settingChanged = $value != setting($setting);
					setSetting($setting, $value);
					
					if ($settingChanged) {
						//Handle changing the default language of the site
						if ($setting == 'default_language') {
							//Update the special pages, creating new ones if needed
							addNeededSpecialPages();
							
							//Resync every content equivalence, trying to make sure that the pages for the new default language are used as the base
							$sql = "
								SELECT DISTINCT equiv_id, type
								FROM ". DB_NAME_PREFIX. "content
								WHERE status NOT IN ('trashed','deleted')";
							$equivResult = sqlQuery($sql);
							while ($equiv = sqlFetchAssoc($equivResult)) {
								resyncEquivalence($equiv['equiv_id'], $equiv['type']);
							}
						
						} elseif ($setting == 'jpeg_quality') {
							$workingCopyImages = $thumbnailWorkingCopyImages = true;
						
						} elseif ($setting == 'thumbnail_wc_image_size') {
							$thumbnailWorkingCopyImages = true;
							$jpegOnly = false;
						
						} elseif ($setting == 'working_copy_image_size') {
							$workingCopyImages = true;
							$jpegOnly = false;
						}
					}
				}
			}
		}
	}
	
	if ($workingCopyImages || $thumbnailWorkingCopyImages) {
		rerenderWorkingCopyImages($workingCopyImages, $thumbnailWorkingCopyImages, true, $jpegOnly);
	}
}

//Save the logo/Organizer favicon
if (isset($values['logo/brand_logo'])) {
	if ($values['logo/brand_logo'] != 'custom'
	 || !$values['logo/logo']) {
		deleteRow('files', array('usage' => 'brand_logo'));
	
	} elseif ($filepath = getPathOfUploadedFileInCacheDir($values['logo/logo'])) {
		$mimeType = documentMimeType($filepath);
	
		if ($mimeType == 'image/gif' || $mimeType == 'image/png'
		 || $mimeType == 'image/jpg' || $mimeType == 'image/jpeg') {
			deleteRow('files', array('usage' => 'brand_logo'));
			addFileToDatabase('brand_logo', $filepath);
		}
	}
}
if (isset($values['sk/organizer_favicon'])) {
	if ($values['sk/organizer_favicon'] != 'custom'
	 || !$values['sk/favicon']) {
		deleteRow('files', array('usage' => 'organizer_favicon'));
	
	} elseif ($filepath = getPathOfUploadedFileInCacheDir($values['sk/favicon'])) {
		$mimeType = documentMimeType($filepath);
	
		if ($mimeType == 'image/gif' || $mimeType == 'image/png'
		 || $mimeType == 'image/vnd.microsoft.icon' || $mimeType == 'image/x-icon') {
			deleteRow('files', array('usage' => 'organizer_favicon'));
			addFileToDatabase('organizer_favicon', $filepath);
		}
	}
}

return false;