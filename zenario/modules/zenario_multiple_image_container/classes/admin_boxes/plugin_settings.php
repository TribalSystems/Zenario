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


class zenario_multiple_image_container__admin_boxes__plugin_settings extends zenario_multiple_image_container {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fields['first_tab/image']['side_note'] =
			ze\admin::phrase('If a JPG or PNG image is selected, Zenario will create and display a WebP version of the image. Fallback logic will be used for browsers which do not support WebP.');
		
		$box['css_class'] .= ' zenario_fab_multiple_image_container';

		if (!$values['first_tab/canvas']) {
			$box['tabs']['first_tab']['fields']['canvas']['value'] = 'fixed_width_and_height';
		}

		if (!$values['first_tab/width']) {
			$box['tabs']['first_tab']['fields']['width']['value'] = 375;
		}

		if (!$values['first_tab/height']) {
			$box['tabs']['first_tab']['fields']['height']['value'] = 250;
		}

		if (!$values['links/enlarge_canvas']) {
			$box['tabs']['links']['fields']['enlarge_canvas']['value'] = 'fixed_width_and_height';
		}

		if (!$values['links/enlarge_width']) {
			$box['tabs']['links']['fields']['enlarge_width']['value'] = 900;
		}

		if (!$values['links/enlarge_height']) {
			$box['tabs']['links']['fields']['enlarge_height']['value'] = 600;
		}

		$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tzip~k{"id"%3A"external_programs"}';
		$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
		$linkEnd = '</a>';

		$fields['links/zip_archive_name']['note_below'] = ze\admin::phrase(
			"Files up to a limit of [[max_unpacked_size]] MB will be made available to visitor, value set in [[link_start]]site settings[[link_end]]. Where total of file sizes exceeds this, multiple volumes will be offered.",
			[
				'link_start' => $linkStart,
				'link_end' => $linkEnd,
				'max_unpacked_size' => ze::setting('max_unpacked_size')
			]
		);

		//Check that docstore exists and is writable
		$docstoreWarning = '';
		$dir = ze::setting('docstore_dir');
		if ($dir) {
			if (!is_dir($dir . '/')) {
				$docstoreWarning = 'Warning: docstore folder does not exist. Saving is disabled. Check the [[site_settings_link]].';
			} elseif (!is_writable($dir)) {
				$docstoreWarning = 'Warning: docstore folder is not writable. Saving is disabled. Check the [[site_settings_link]].';
			}
		} else {
			$docstoreWarning = 'Warning: docstore path not set. Saving is disabled. Check the [[site_settings_link]].';
		}

		if ($docstoreWarning) {
			$siteSettingsLink = "<a href='organizer.php#zenario__administration/panels/site_settings//files_and_images~.site_settings~tdocstore_dir~k{\"id\"%3A\"files_and_images\"}' target='_blank'>site settings</a>";
			$box['tabs']['first_tab']['notices']['docstore_warning']['message'] = ze\admin::phrase($docstoreWarning, ['site_settings_link' => $siteSettingsLink]);
			$box['tabs']['first_tab']['notices']['docstore_warning']['show'] = true;

			$fields['first_tab/image']['disabled'] = true;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':

				$retinaSideNote = "If the source image is large enough,
                            the resized image will be output at twice its displayed width &amp; height
                            to appear crisp on retina screens.
                            This will increase the download size.
                            <br/>
                            If the source image is not large enough this will have no effect.";
				
				$cID = $cType = $pictureCID = $pictureCType = $imageId = false;
				
				//For every picked image on the Images tab, we need to make a copy of the template fields.
				$ord = 0;
				$valuesInDB = false;
				$enlargeImageOptionPicked = false;
				$imageIds = ze\ray::explodeAndTrim($values['first_tab/image']);
				foreach ($imageIds as $imageId) {
					if (!isset($box['tabs']['links']['fields']['image_'. $imageId])) {
						//Copy the template files, replacing znz with the id of the image
						$templateFields = json_decode(str_replace('znz', $imageId, json_encode($box['tabs']['links']['custom_template_fields'])), true);
				
						//Load the plugin setting values, if we've not yet done so
						if (!$valuesInDB) {
							ze\tuix::loadAllPluginSettings($box, $valuesInDB);
						}
				
						//For each template field, note down which image it was for, its value if it had one saved,
						//then and add it to the links tab.
						foreach ($templateFields as $settingName => &$field) {
							$field['for_image'] = $imageId;
							if (isset($valuesInDB[$settingName])) {
								$field['value'] = $valuesInDB[$settingName];
								
								//Remember if anything was set to "Enlarge image in floating box"
								if ($field['value'] == '_ENLARGE_IMAGE') {
									$enlargeImageOptionPicked = true;
								}
							}

							if ($settingName == 'alt_tag_' . (int) $imageId) {
								$field['value'] = ze\row::get('files', 'alt_tag', ['id' => (int) $imageId, 'usage' => 'mic']);
								$box['tabs']['links']['fields'][$settingName] = $field;
							}
							$box['tabs']['links']['fields'][$settingName] = $field;
						}
						unset($field);
						
						//Copy the metadata from the main image picker to each of the individual pickers.
						//This info is needed to display the image thumbnails properly.
						if (is_numeric($imageId)) {
							if ($image = ze\row::get('files', [
								'id', 'filename', 'size', 'width', 'height', 'checksum', 'short_checksum', 'usage', 'location'
							], $imageId)) {
								$image['css_class'] = 'media_image';
								$image['label'] = ze\admin::phrase('{{filename}} [{{width}} × {{height}}]', $image, false, '{{', '}}');
								$image['image'] = 'zenario/file.php?og=1&c='. $image['checksum']. '&usage='. $image['usage'];
								
								$box['tabs']['links']['fields']['image_'. $imageId]['values'][$imageId] = $image;
							}
						} else {
							if (ze\file::getPathOfUploadInCacheDir($imageId)) {
								$details = explode('/', \ze\ring::decodeIdForOrganizer($imageId), 5);
								
								if (isset($details[3])
								 && is_numeric($details[3])
								 && is_numeric($details[2])) {
								
									$image = [];
									$image['css_class'] = 'media_image';
									$image['filename'] = $details[1];
									$image['width'] = $details[2];
									$image['height'] = $details[3];
									$image['label'] = ze\admin::phrase('{{1}} [{{2}} × {{3}}]', $details, false, '{{', '}}');
									$image['image'] = 'zenario/file.php?og=1&getUploadedFileInCacheDir='. $imageId;
									
									$box['tabs']['links']['fields']['image_'. $imageId]['values'][$imageId] = $image;
								}
							}
						}
					}
					
					//Set the order of the fields.
					//We need to do this each time, as if someone rearranges the images, the fields will need to be rearranged as well.
					foreach ($box['tabs']['links']['custom_template_fields'] as $fieldName => &$field) {
						$settingName = str_replace('znz', $imageId, $fieldName);
						$box['tabs']['links']['fields'][$settingName]['ord'] = ++$ord;
						
					}
					
					//Remember if anything was set to "Enlarge image in floating box"
					if (isset($values['links/link_type_'. $imageId])
					 && $values['links/link_type_'. $imageId] == '_ENLARGE_IMAGE') {
						$enlargeImageOptionPicked = true;
					}
				}
		
				//Handle removed images by hiding all of their fields
				foreach ($box['tabs']['links']['fields'] as $settingName => &$field) {
					if (isset($field['for_image'])) {
						$field['hidden'] = !in_array($field['for_image'], $imageIds);
					}
				}
				
				$values['first_tab/link_type'] = $enlargeImageOptionPicked? '_ENLARGE_IMAGE' : '';
				
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden = false);
				
				$fields['links/enlarge_canvas']['side_note'] = ze\admin::phrase('This only has effect when you select "Enlarge image in a floating box" for an image.');

				$hidden = $values['first_tab/link_type'] != '_ENLARGE_IMAGE';
				$this->showHideImageOptions($fields, $values, 'links', $hidden, 'enlarge_');
				if ($values['links/enlarge_canvas'] != "unlimited") {
					$fields['links/enlarge_canvas']['side_note'] = $retinaSideNote;
				} else {
					$fields['links/enlarge_canvas']['side_note'] = "";
				}
				
				//Make sure the "Captions, links, enlarging" tab is never blank.
				$box['tabs']['links']['fields']['no_captions']['hidden'] = !empty($values['first_tab/image']);
				
				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$imageIds = ze\ray::explodeAndTrim($values['first_tab/image']);
		foreach ($imageIds as $imageId) {
			if (!empty($values['links/link_type_'. $imageId])) {
				switch ($values['links/link_type_'. $imageId]) {
					case '_CONTENT_ITEM':
						if (!$values['links/hyperlink_target_'. $imageId]) {
							$fields['links/hyperlink_target_'. $imageId]['error'] = true;
							$box['tabs']['links']['errors']['no_content_item'] = ze\admin::phrase('Please select a content item');
						}

						if ($values['links/link_to_anchor_'. $imageId]) {
							if (!$values['links/hyperlink_anchor_'. $imageId]) {
								$fields['links/hyperlink_anchor_'. $imageId]['error'] = true;
								$box['tabs']['links']['errors']['no_anchor_name'] = ze\admin::phrase('Please enter an anchor name');
							}
						}

						break;
					
					case '_EXTERNAL_URL':
						if (!$values['links/url_'. $imageId]) {
							$fields['links/url_'. $imageId]['error'] = true;
							$box['tabs']['links']['errors']['no_url'] = ze\admin::phrase('Please enter a URL');
						}

						break;
				}
			}
		}
	}

    public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path == 'plugin_settings') {
			
			//Make sure all images selected are stored in the docstore, and have the "usage" column value 'mic'.
			if ($values['first_tab/image']) {
				$sql = '
					SELECT id, filename
					FROM ' . DB_PREFIX . 'files
					WHERE id IN (' . ze\escape::in($values['first_tab/image'], 'numeric') . ')
					AND location = "db"';
				
				$result = ze\sql::select($sql);
				while ($file = ze\sql::fetchAssoc($result)) {
					$usage = [];
	
					$usageSql = "
						SELECT foreign_key_to, is_nest, is_slideshow, GROUP_CONCAT(DISTINCT foreign_key_id, foreign_key_char) AS concat
						FROM ". DB_PREFIX. "inline_images
						WHERE image_id = ". (int) $file['id']. "
						AND in_use = 1
						AND archived = 0
						AND foreign_key_to IN ('content', 'library_plugin', 'menu_node', 'email_template', 'newsletter', 'newsletter_template') 
						GROUP BY foreign_key_to, is_nest, is_slideshow";
					
					$usageResults = ze\sql::fetchAssocs($usageSql);
					
					foreach ($usageResults as $usageResult) {
						$keyTo = $usageResult['foreign_key_to'];
					
						if ($keyTo == 'content') {
							$usage['content_items'] = \ze\sql::fetchValue("
								SELECT GROUP_CONCAT(foreign_key_char, '_', foreign_key_id)
								FROM ". DB_PREFIX. "inline_images
								WHERE image_id = ". (int) $file['id']. "
								AND archived = 0
								AND foreign_key_to = 'content'
							");
						
						} elseif ($keyTo == 'library_plugin') {
							if ($usageResult['is_slideshow']) {
								$usage['slideshows'] = $usageResult['concat'];
								
							} elseif ($usageResult['is_nest']) {
								$usage['nests'] = $usageResult['concat'];
							
							} else {
								$usage['plugins'] = $usageResult['concat'];
							}
							
						} else {
							$usage[$keyTo. 's'] = $usageResult['concat'];
						}
					}
	
					$MICPluginsAndSettings = $nonMICPluginsAndSettings = [];
					if (!empty($usage['plugins'])) {
						//Make a list of plugin types in case this image needs to be duplicated later.
	
						foreach (explode(',', $usage['plugins']) as $plugin) {
							$pluginSql = '
								SELECT pi.id, ps.value, m.class_name
								FROM ' . DB_PREFIX . 'plugin_instances pi
								INNER JOIN ' . DB_PREFIX . 'modules m
									ON pi.module_id = m.id
								LEFT JOIN ' . DB_PREFIX . 'plugin_settings ps
									ON ps.instance_id = pi.id
								WHERE pi.id = ' . (int)$plugin . '
								AND ps.name = "image"';
							$pluginResult = ze\sql::fetchAssoc($pluginSql);
	
							if ($pluginResult) {
								if ($pluginResult['class_name'] == 'zenario_multiple_image_container') {
									$MICPluginsAndSettings[$pluginResult['id']] = ['value' => $pluginResult['value']];
								} else {
									$nonMICPluginsAndSettings[$pluginResult['id']] = ['value' => $pluginResult['value']];
								}
							}
						}
					}
	
					if (!empty($usage['nests'])) {
						//Make a list of plugin types in case this image needs to be duplicated later.
	
						foreach (explode(',', $usage['nests']) as $nest) {
							$pluginSql = '
								SELECT np.instance_id AS id, np.id AS egg_id, ps.value, m.class_name
								FROM ' . DB_PREFIX . 'nested_plugins np
								INNER JOIN ' . DB_PREFIX . 'modules m
									ON np.module_id = m.id
								LEFT JOIN ' . DB_PREFIX . 'plugin_settings ps
									ON ps.instance_id = np.instance_id
									AND ps.egg_id = np.id
								WHERE np.instance_id = ' . (int)$nest . '
								AND ps.name = "image"';
							$pluginResult = ze\sql::fetchAssoc($pluginSql);
	
							if ($pluginResult) {
								if ($pluginResult['class_name'] == 'zenario_multiple_image_container') {
									$MICPluginsAndSettings[$pluginResult['egg_id']] = ['value' => $pluginResult['value'], 'nest_id' => $pluginResult['id']];
								} else {
									$nonMICPluginsAndSettings[$pluginResult['egg_id']] = ['value' => $pluginResult['value'], 'nest_id' => $pluginResult['id']];
								}
							}
						}
					}
					
					$fileInfo = ze\row::get('files', ['filename', 'checksum', 'short_checksum'], ['id' => $file['id']]);
					$duplicateInMicLibrary = ze\row::get('files', 'id', ['checksum' => $fileInfo['checksum'], 'usage' => 'mic', 'id' => ['!=' => $file['id']]]);
					
					if (!empty($usage['slideshows']) || !empty($usage['content_items']) || count($nonMICPluginsAndSettings) > 0 || $duplicateInMicLibrary) {
						//If the image is used by anything else other than just MIC plugins, then duplicate the file with new usage value,
						//update any MIC plugin settings to use the new file ID instead,
						//and create the correct entry in the "inline_images" table.
						//Also move the file to docstore.
						if ($duplicateInMicLibrary) {
							$newFileId = $duplicateInMicLibrary;
						} else {
							$newFileId = ze\file::copyInDatabase('mic', $file['id'], false, true, $addToDocstoreDirIfPossible = true);
						}
	
						foreach ($MICPluginsAndSettings as $pluginId => $plugin) {
							$oldImageSettings = $plugin['value'];
							
							$newImageSettingsArray = [];
							$oldImageSettingsArray = explode(',', $oldImageSettings);
							foreach ($oldImageSettingsArray as $entry) {
								if ($entry == $file['id']) {
									$newImageSettingsArray[] = $newFileId;
								} else {
									$newImageSettingsArray[] = $entry;
								}
							}
							
							$newImageSettingsArray = array_unique($newImageSettingsArray);
							$newImageSettings = implode(',', $newImageSettingsArray);
	
							$wherePluginSettings = [
								'name' => 'image',
								'foreign_key_to' => 'multiple_files',
								'value' => ze\escape::in($oldImageSettings)
							];
	
							if (!empty($plugin['nest_id'])) {
								$wherePluginSettings['instance_id'] = (int)$plugin['nest_id'];
								$wherePluginSettings['egg_id'] = (int)$pluginId;
							} else {
								$whwherePluginSettingsere['instance_id'] = (int)$pluginId;
							}
	
							ze\row::update('plugin_settings', ['value' => ze\escape::in($newImageSettings)], $wherePluginSettings);
						}
					} else {
						//Alternatively, if the image is used only by MIC plugins, then just move it to docstore and update the "usage" column.
						$pathDS = false;
						ze\file::moveFileFromDBToDocstore($pathDS, $file['id'], $fileInfo['filename'], $fileInfo['checksum']);
	
						ze\row::update('files', ['usage' => 'mic', 'data' => NULL, 'path' => $pathDS, 'location' => 'docstore'], ['id' => (int)$file['id']]);
					}
				}
			}
		}
	}
}