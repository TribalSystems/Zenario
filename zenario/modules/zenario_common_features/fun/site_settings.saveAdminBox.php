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

$changesToFiles = false;

//Loop through each field that would be in the Admin Box, and has the <site_setting> tag set
foreach ($box['tabs'] as $tabName => &$tab) {
	
	$workingCopyImages = $thumbnailWorkingCopyImages = false;
	$jpegOnly = true;
	
	if (is_array($tab) && engToBooleanArray($tab, 'edit_mode', 'on')) {
		foreach ($tab['fields'] as $fieldName => &$field) {
			if (is_array($field)) {
				if (!arrayKey($field, 'read_only') && $setting = arrayKey($field, 'site_setting', 'name')) {
					
					//Get the value of the setting. Hidden fields should count as being empty
					if (engToBooleanArray($field, 'hidden')
					 || engToBooleanArray($field, '_was_hidden_before')) {
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
					
					//Handle file pickers
					if (!empty($field['upload'])) {
						if ($filepath = getPathOfUploadedFileInCacheDir($value)) {
							$value = addFileToDatabase('site_setting', $filepath);
						}
						$changesToFiles = true;
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

//Tidy up any files in the database 
if ($changesToFiles) {
	$sql = "
		SELECT f.id
		FROM ". DB_NAME_PREFIX. "files AS f
		LEFT JOIN ". DB_NAME_PREFIX. "site_settings AS s
		   ON s.value = f.id
		  AND s.value = CONCAT('', f.id)
		WHERE f.`usage` = 'site_setting'
		  AND s.name IS NULL";
	
	$result = sqlSelect($sql);
	while ($file = sqlFetchAssoc($result)) {
		deleteFile($file['id']);
	}
}

return false;