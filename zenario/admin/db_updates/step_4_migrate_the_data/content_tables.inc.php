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



//Code for converting table data, after the more drastic database structure changes




//Some old updates. Not currently needed but I'm just keeping the code
//here so I can easily find it if I ever need to re-issue them again.

/*
//Scan anything related to a Content Item and sync the inline_images table properly
if (ze\dbAdm::needRevision(30731)) {
	
	$result = ze\row::query('content_items', ['id', 'type', 'visitor_version', 'admin_version'], [], ['type', 'id']);
	while ($row = ze\sql::fetchAssoc($result)) {
		
		if ($row['visitor_version']) {
			ze\contentAdm::syncInlineFileContentLink($row['id'], $row['type'], $row['visitor_version']);
		}
		if ($row['admin_version'] && $row['admin_version'] != $row['visitor_version']) {
			ze\contentAdm::syncInlineFileContentLink($row['id'], $row['type'], $row['admin_version']);
		}
	}
	
	ze\dbAdm::revision(30731);
}

//Update Organizer's image thumbnails
if (ze\dbAdm::needRevision(31200)) {
	$docstoreDir = ze::setting('docstore_dir');
	
	foreach ([
		['thumbnail_180x130_data', 'thumbnail_180x130_width', 'thumbnail_180x130_height', 180, 130],
		['thumbnail_64x64_data', 'thumbnail_64x64_width', 'thumbnail_64x64_height', 64, 64],
		['thumbnail_24x23_data', 'thumbnail_24x23_width', 'thumbnail_24x23_height', 24, 23]
	] as $c) {

		$sql = "
			SELECT id, location, path, filename, data, mime_type, width, height
			FROM ". DB_PREFIX. "files
			WHERE mime_type IN ('image/gif', 'image/png', 'image/jpeg')
			  AND width != 0
			  AND height != 0
			  AND (`". ze\escape::sql($c[0]). "` IS NULL
				OR `". ze\escape::sql($c[1]). "` > ". (int) $c[3]. "
				OR `". ze\escape::sql($c[2]). "` > ". (int) $c[4]. "
				OR (	width > ". (int) $c[3]. "
					AND height > ". (int) $c[4]. "
					AND `". ze\escape::sql($c[1]). "` < ". (int) $c[3]. "
					AND `". ze\escape::sql($c[2]). "` < ". (int) $c[4]. "
			))";
		$result = ze\sql::select($sql);

		while($img = ze\sql::fetchAssoc($result)) {
			if ($img['location'] == 'docstore') {
				if ($docstoreDir && is_file($docstoreDir. '/'. $img['path'])) {
					$img['data'] = file_get_contents($docstoreDir. '/'. $img['path']. '/'. $img['filename']);
				} else {
					continue;
				}
			}
	
			ze\file::resizeImageString($img['data'], $img['mime_type'], $img['width'], $img['height'], $c[3], $c[4]);
			$img['data'] = "
				UPDATE ". DB_PREFIX. "files SET
					`". ze\escape::sql($c[0]). "` = '". ze\escape::sql($img['data']). "',
					`". ze\escape::sql($c[1]). "` = ". (int) $img['width']. ",
					`". ze\escape::sql($c[2]). "` = ". (int) $img['height']. "
				WHERE id = ". (int) $img['id'];
			ze\sql::update($img['data']);
			unset($img);
		}
	}
	
	ze\dbAdm::revision(31200);
}

//Attempt to clear the cache/frameworks directory
if (ze\dbAdm::needRevision(36380)) {
	if (is_dir(CMS_ROOT. 'cache/frameworks')
	 && !ze\server::isWindows()
	 && ze\server::execEnabled()) {
		exec('rm -r '. escapeshellarg(CMS_ROOT. 'cache/frameworks'));
		ze\cache::cleanDirs();
	}
	
	ze\dbAdm::revision(36380);
}

//Delete any cached frameworks, as the format has changed slightly in this version
if (ze\dbAdm::needRevision(45600)) {
	
	\ze\skinAdm::clearCacheDir();
	
	ze\dbAdm::revision(45600);
}

//In 8.6 we've added the cache/scans/ and cache/stop_flags/ directories,
//call the cleanDirs() function to try and ensure that they're created properly
if (ze\dbAdm::needRevision(50420)) {
	
	if (\ze::$dbL
	 && \ze::$dbL->con
	 && is_dir(CMS_ROOT. 'cache/tuix')
	 && !is_dir(CMS_ROOT. 'cache/stop_flags')) {
		ze\cache::cleanDirs(true);
	}
	
	ze\dbAdm::revision(50420);
}
*/


//There was a conductor bug where if you re-ordered the slides, the slide numbers got updated on the
//slides themselves but not on the nests going between them.
//Fix any bad data where this has happened.
if (ze\dbAdm::needRevision(52200)) {
	
	$result = ze\row::query('nested_plugins', ['instance_id', 'slide_num', 'states'], ['is_slide' => 1, 'states' => ['!' => '']]);
	foreach ($result as $slide) {
		foreach (ze\ray::explodeAndTrim($slide['states']) as $state) {
			$key = ['instance_id' => $slide['instance_id'], 'from_state' => $state];
			ze\row::update('nested_paths', ['slide_num' => $slide['slide_num']], $key);
		}
	}
	
	ze\dbAdm::revision(52200);
}

//Starting with this release, all Multiple Image Container files will be stored in the docstore.
//Their "usage" value will be "mic".
//Any existing image that is used for other purposes (not just MIC plugins) will be duplicated with the new "usage" value,
//and all plugins where it was used will instead use the new file.
if (ze\dbAdm::needRevision(52205)) {
	
	$module = 'zenario_multiple_image_container';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);

		$imageIds = [];
		$instances = ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['image'])) {
				foreach (explode(',', $instance['settings']['image']) as $imageId) {
					$imageIds[] = $imageId;
				}
			}
		}

		if (!empty($imageIds)) {
			//Remove duplicates
			$imageIds = array_unique($imageIds);
			$imageIds = implode(',', $imageIds);

			$imageCount = 0;
			$imagesMovedToDocstore = [];
			$imagesDuplicatedAndMovedToDocstore = [];

			//Check which of these images are stored in the DB, and move them to docstore.
			$sql = '
				SELECT id, filename
				FROM ' . DB_PREFIX . 'files
				WHERE id IN (' . ze\escape::in($imageIds, 'numeric') . ')
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

				if (!empty($usage['slideshows']) || !empty($usage['content_items']) || count($nonMICPluginsAndSettings) > 0) {
					//If the image is used by anything else other than just MIC plugins, then duplicate the file with new usage value,
					//update any MIC plugin settings to use the new file ID instead,
					//and create the correct entry in the "inline_images" table.
					//Also move the file to docstore.
					$oldFileInfo = ze\row::get('files', ['filename', 'short_checksum'], ['id' => $file['id']]);
					$newFileId = ze\file::copyInDatabase('mic', $file['id'], false, true, $addToDocstoreDirIfPossible = true);

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
						$newImageSettings = implode(',', $newImageSettingsArray);

						$wherePluginSettings = [
							'name' => 'image',
							'foreign_key_to' => 'multiple_files',
							'value' => ze\escape::in($oldImageSettings)
						];

						$whereInlineImages = [
							'image_id' => (int)$file['id'],
							'in_use' => 1,
							'archived' => 0,
							'is_slideshow' => 0,
							'foreign_key_to' => 'library_plugin'
						];

						if (!empty($plugin['nest_id'])) {
							$wherePluginSettings['instance_id'] = (int)$plugin['nest_id'];
							$wherePluginSettings['egg_id'] = (int)$pluginId;

							$whereInlineImages['foreign_key_id'] = (int)$plugin['nest_id'];
							$whereInlineImages['is_nest'] = 1;
						} else {
							$whwherePluginSettingsere['instance_id'] = (int)$pluginId;

							$whereInlineImages['foreign_key_id'] = (int)$pluginId;
							$whereInlineImages['is_nest'] = 0;
						}

						ze\row::update('plugin_settings', ['value' => ze\escape::in($newImageSettings)], $wherePluginSettings);
						ze\row::update('inline_images', ['image_id' => (int)$newFileId], $whereInlineImages);
						$imageCount ++;
						$imagesDuplicatedAndMovedToDocstore[$file['id']] = $oldFileInfo;
						$imagesDuplicatedAndMovedToDocstore[$file['id']]['new_id'] = $newFileId;
						$imagesDuplicatedAndMovedToDocstore[$file['id']]['docstore_path'] = ze\row::get('files', 'path', ['id' => $newFileId]);
					}
				} else {
					//Alternatively, if the image is used only by MIC plugins, then just move it to docstore and update the "usage" column.
					$fileInfo = ze\row::get('files', ['filename', 'checksum', 'short_checksum'], ['id' => $file['id']]);
					
					$pathDS = false;
					ze\file::moveFileFromDBToDocstore($pathDS, $file['id'], $fileInfo['filename'], $fileInfo['checksum']);

					ze\row::update('files', ['usage' => 'mic', 'data' => NULL, 'path' => $pathDS, 'location' => 'docstore'], ['id' => (int)$file['id']]);
					$imageCount ++;
					$imagesMovedToDocstore[$file['id']] = $fileInfo;
					$imagesMovedToDocstore[$file['id']]['docstore_path'] = $pathDS;
				}
			}

			if ($imageCount > 0) {
				$subject = ze\admin::phrase('Zenario [[zenario_version]] update: images moved to docstore', ['zenario_version' => ZENARIO_VERSION]);

				$message = ze\admin::phrase('Number of images moved to docstore: [[count]]', ['count' => (int)$imageCount]);
				$message .= "\n";

				if (count($imagesMovedToDocstore) > 0) {
					$message .= "\n";
					$message .= ze\admin::phrase('Images moved to docstore:');
					foreach ($imagesMovedToDocstore as $movedImageId => $movedImageInfo) {
						$message .= "\n";
						$message .= ze\admin::phrase(
							'ID: [[id]], filename: [[filename]], short checksum: [[short_checksum]]',
							['id' => $movedImageId, 'filename' => $movedImageInfo['filename'], 'short_checksum' => $movedImageInfo['short_checksum']]
						);
					}
				}

				if (count($imagesDuplicatedAndMovedToDocstore) > 0) {
					$message .= "\n";
					$message .= ze\admin::phrase('Images duplicated and moved to docstore:');
					foreach ($imagesDuplicatedAndMovedToDocstore as $movedImageId => $movedImageInfo) {
						$message .= "\n";
						$message .= ze\admin::phrase(
							'Previous ID: [[previous_id]], new ID: [[new_id]], filename: [[filename]], short checksum: [[short_checksum]], docstore path: [[docstore_path]]',
							[
								'previous_id' => $movedImageId,
								'new_id' => $movedImageInfo['new_id'],
								'filename' => $movedImageInfo['filename'],
								'short_checksum' => $movedImageInfo['short_checksum'],
								'docstore_path' => $movedImageInfo['docstore_path']
							]
						);
					}
				}

				ze\server::sendEmailSimple($subject, $message, $isHTML = false);
			}
		}
	}
	
	ze\dbAdm::revision(52205);
}

if (ze\dbAdm::needRevision(52219)) {
	$module = 'assetwolf_2';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);
		$instances = ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['enable.metadata']) && $instance['settings']['enable.metadata'] == 1) {
				ze\row::update('plugin_settings', ['value' => 'show_all'], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'enable.metadata']);
			}
		}
	}

	ze\dbAdm::revision(52219);
}
//For Maximum Content File Size settings we need to update value from bytes to MB
if (ze\dbAdm::needRevision(52220)) {
	$filesizevalue = ze::setting('content_max_filesize', false);
	$filesizeUnit = ze::setting('content_max_filesize_unit', false);
	
	if ($filesizevalue && !$filesizeUnit) {
		
		if ($filesizevalue < 1000000) {
			$newFileValue = 1;
			$newFileUnit = 'MB';
		} else {
			$fileSizeConvertValue = ze\file::fileSizeConvert($filesizevalue);
			$convertArray = explode(' ', $fileSizeConvertValue);
			$newFileValue = $convertArray[0];
			$newFileUnit = $convertArray[1];
		}
		
		ze\site::setSetting('content_max_filesize', $newFileValue);
		ze\site::setSetting('content_max_filesize_unit', $newFileUnit);
	
	} elseif (!$filesizevalue) {
		ze\site::setSetting('content_max_filesize', 20);
		ze\site::setSetting('content_max_filesize_unit', 'MB');
	}
	
	ze\dbAdm::revision(52220);
}



//
//	Changes for layouts in 9.0
//

if (ze\dbAdm::needRevision(52526)) {
	
	if (\ze::$dbL
	 && \ze::$dbL->con
	 && !is_dir(CMS_ROOT. 'cache/layouts')) {
		ze\cache::cleanDirs(true);
	}
	
	$missingLayoutFiles = [];
	
	//Look for all existing grid layouts, with files stored in the zenario_custom/templates/grid_templates/ directory
	foreach (ze\row::getAssocs('layouts', true) as $layout) {
		
		//Check whether a layout file exists to migrate from
		if (($is_file = is_file($path = CMS_ROOT. ($relPath = 'zenario_custom/templates/grid_templates/'. $layout['file_base_name']. '.tpl.php')))
		 && ($html = @file_get_contents($path))) {
			if (($data = ze\gridAdm::readCode($html, false, false))
			 && (!empty($data['cells']))
			 && (ze\gridAdm::validateData($data))) {
				ze\gridAdm::trimData($data);
				ze\gridAdm::saveLayoutData($layout['layout_id'], $data);
			}
		
		//If the layout doesn't already exist, check:
			//Is it active?
			//Has it already been migrated, maybe in a previous sweep?
			//Does it look like a grid layout?
		//If the answers are "yes", "no" and "yes", then raise it as a problem!
		} else {
			if ($layout['status'] == 'active'
			 && empty($layout['json_data'])
			 && !empty($layout['cols'])) {
				$missingLayoutFiles[] = $relPath;
			}
		}
	}
	
	
	if (!empty($missingLayoutFiles)) {
		$mrg = [
			'example' => $missingLayoutFiles[0]
		];
		
		echo ze\admin::nPhrase(
			'Your layouts need migrating to Zenario 9, but [[example]] and 1 other file-pair are missing from the disk or not readable.',
			'Your layouts need migrating to Zenario 9, but [[example]] and [[count]] other file-pairs are missing from the disk or not readable.',
			count($missingLayoutFiles) - 1,
			$mrg,
			'Your layouts need migrating to Zenario 9, but [[example]] and its .css equivalent are missing or not readable.'
		);
		exit;
	}
	
	if (is_dir(CMS_ROOT. 'zenario_custom/templates/grid_templates/skins/')
	 && !is_dir(CMS_ROOT. 'zenario_custom/skins/')) {
		echo ze\admin::phrase('Your skins need migrating to Zenario 9, please move the zenario_custom/templates/grid_templates/skins/ directory to zenario_custom/skins/');
		exit;
	}
	
	ze\dbAdm::revision(52526);
}


//Drop the file_base_name column, now we don't need it any more
if (ze\dbAdm::needRevision(52527) && !ze\sql::numRows('SHOW COLUMNS FROM '. DB_PREFIX. 'layouts LIKE "file_base_name"')) ze\dbAdm::revision(52527
, <<<_sql
	ALTER TABLE `[[DB_PREFIX]]layouts` 
	DROP COLUMN `file_base_name`
_sql
);


//In 9.0, some of the debug options for the page/plugin caching have changed.
//Clear the old values, so I don't have to deal with any unsupported changes.
if (ze\dbAdm::needRevision(53250)) {
	ze\site::setSetting('caching_debug_info', '');
	ze\site::setSetting('limit_caching_debug_info_by_ip', '');
	ze\dbAdm::revision(53250);
}

//Clear the cache after processing the skins
if (ze\dbAdm::needRevision(53251)) {
	ze\skinAdm::clearCache();
}

//Event calendar module settings update
if (ze\dbAdm::needRevision(53601)) {
	if (ze\module::isRunning('zenario_event_calendar')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_event_calendar');
		
		foreach ($instances as $instance) {
			if ($instance['settings']['event_count'] == 'event_count_on') {
				$newValue = 1;
			} else {
				$newValue = 0;
			}
			ze\row::update('plugin_settings', ['value' => (int) $newValue], ['instance_id' => (int) $instance['instance_id'], 'egg_id' => (int) $instance['egg_id'], 'name' => 'event_count']);
			
			ze\row::insert('plugin_settings',
				[
					'instance_id' => (int) $instance['instance_id'],
					'egg_id' => (int) $instance['egg_id'],
					'name' => 'enable_popup',
					'value' => 1
				],
				$ignore = true
			);

			if ($instance['settings']['view_mode'] == 'year_view') {
				ze\row::insert('plugin_settings',
					[
						'instance_id' => (int) $instance['instance_id'],
						'egg_id' => (int) $instance['egg_id'],
						'name' => 'show_other_periods',
						'value' => 'previous_and_next'
					],
					$ignore = true
				);
			}

			if (!empty($instance['settings']['first_event'])) {
				$value = 'first_event';
			} else {
				$value = 'all_events';
			}

			ze\row::insert('plugin_settings',
				[
					'instance_id' => (int) $instance['instance_id'],
					'egg_id' => (int) $instance['egg_id'],
					'name' => 'show_event_titles',
					'value' => ze\escape::sql($value)
				],
				$ignore = true
			);
		}
	}

	ze\dbAdm::revision(53601);
}

//Event Calendar settings update: in the popup, the title is now mandatory,
//and the setting can be removed to avoid clutter.
if (ze\dbAdm::needRevision(53602)) {
	if (ze\module::isRunning('zenario_event_calendar')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_event_calendar');
		
		foreach ($instances as $instance) {
			ze\row::delete('plugin_settings',
				[
					'instance_id' => (int) $instance['instance_id'],
					'egg_id' => (int) $instance['egg_id'],
					'name' => 'show_title'
				]
			);
		}
	}

	ze\dbAdm::revision(53602);
}

//In 9.1, there were 2 removed frameworks for Menu Vertical plugins.
//Their features were integrated with the standard framework as plugin settings.
//This logic will set the framework to standard, and enable the relevant setting.
if (ze\dbAdm::needRevision(53802)) {
	$module = 'zenario_menu_vertical';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);

		//Non-nested plugins
		$pluginInstancesSql = '
			SELECT id, framework
			FROM ' . DB_PREFIX . 'plugin_instances
			WHERE module_id = ' . (int)$moduleId . '
			AND framework IN ("menu_with_name", "menu_with_title")';
		$pluginInstancesResult = \ze\sql::select($pluginInstancesSql);

		while ($row = \ze\sql::fetchAssoc($pluginInstancesResult)) {
			if ($row['framework'] == 'menu_with_title') {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['id'], 'egg_id' => 0, 'name' => 'show_parent_menu_node_text']);
			} elseif ($row['framework'] == 'menu_with_name') {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['id'], 'egg_id' => 0, 'name' => 'change_welcome_message']);
			}
			ze\row::set('plugin_instances', ['framework' => 'standard'], ['id' => $row['id']]);
		}
		
		//Nested plugins
		$nestedPluginInstancesSql = '
			SELECT instance_id, id, framework
			FROM ' . DB_PREFIX . 'nested_plugins
			WHERE module_id = ' . (int)$moduleId . '
			AND framework IN ("menu_with_name", "menu_with_title")';
		$nestedPluginsResult = \ze\sql::select($nestedPluginInstancesSql);

		while ($row = \ze\sql::fetchAssoc($nestedPluginsResult)) {
			if ($row['framework'] == 'menu_with_title') {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['instance_id'], 'egg_id' => $row['id'], 'name' => 'show_parent_menu_node_text']);
			} elseif ($row['framework'] == 'menu_with_name') {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['instance_id'], 'egg_id' => $row['id'], 'name' => 'change_welcome_message']);
			}
			
			ze\row::set('nested_plugins', ['framework' => 'standard'], ['instance_id' => $row['instance_id'], 'id' => $row['id']]);
		}
	}

	ze\dbAdm::revision(53802);
}

//WebP support: remove the public images folder and recreate it, so that webp images are generated.
if (ze\dbAdm::needRevision(53803)) {
	//Change the time limit to 10 min
	//in case there are multiple images being created
	//on the Diagnostics screen.
	set_time_limit(60 * 10);

	$publicImagesDir = CMS_ROOT . 'public/images';
	if (is_dir($publicImagesDir)) {
		if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
			exec('rm -rf '. escapeshellarg($publicImagesDir));
		} else {
			ze\cache::deleteDir($publicImagesDir, 2);
		}
		
		ze\cache::cleanDirs($force = true);
	}

	ze\dbAdm::revision(53803);
}

//Writer profiles: create writer profiles for admins
//and update the content item values to use the profile instead of admin ID.
if (ze\dbAdm::needRevision(54404)) {
	//Get a list of admins used as content item writers.
	$sql = "
		SELECT DISTINCT a.id
		FROM " . DB_PREFIX . "content_item_versions v
		INNER JOIN " . DB_PREFIX . "content_items ci
			ON ci.id = v.id
			AND ci.type = v.type
		INNER JOIN " . DB_PREFIX . "admins a
			ON a.id = v.writer_id
		WHERE ci.status IN('published', 'published_with_draft')";
	$result = ze\sql::select($sql);
	$adminWriters = ze\sql::fetchValues($result);

	if (is_array($adminWriters) && count($adminWriters) > 0) {
		//Create writer profiles for these admins.
		$adminWriterProfiles = [];

		foreach ($adminWriters as $index => $adminId) {
			$adminDetails = ze\admin::details($adminId);

			$createdInfo = [];
			ze\admin::setLastUpdated($createdInfo, true);

			$writerProfileId = ze\row::set(
				'writer_profiles',
				[
					'admin_id' => (int) $adminId,
					'first_name' => ze\escape::sql(($adminDetails['first_name'] ?? '')),
					'last_name' => ze\escape::sql(($adminDetails['last_name'] ?? '')),
					'email' => ze\escape::sql(($adminDetails['email'] ?? '')),
					'type' => 'administrator',
					'created' => $createdInfo['created'],
					'created_admin_id' => $createdInfo['created_admin_id']
				],
				[]
			);

			$adminWriterProfiles[$adminId] = $writerProfileId;
		}

		//Update the content item metadata to use the new writer profile instead of admin ID.
		if (count($adminWriterProfiles) > 0) {
			$sql = "
				SELECT v.id, v.type, v.version, v.writer_id
				FROM " . DB_PREFIX . "content_item_versions v
				INNER JOIN " . DB_PREFIX . "content_items ci
					ON ci.id = v.id
					AND ci.type = v.type
				WHERE v.writer_id IN (" . ze\escape::in($adminWriters) . ")
				AND ci.status IN('published', 'published_with_draft')";
			$result = ze\sql::select($sql);

			while ($row = ze\sql::fetchAssoc($result)) {
				ze\row::set(
					'content_item_versions',
					[
						'writer_id' => (int) $adminWriterProfiles[$row['writer_id']]
					],
					[
						'id' => $row['id'],
						'type' => $row['type'],
						'version' => $row['version'],
						'writer_id' => $row['writer_id']
					]
				);
			}
		}
	}

	//Afterwards, remove dangling cross-references.
	$sql = "
		UPDATE " . DB_PREFIX . "content_item_versions v
		INNER JOIN " . DB_PREFIX . "content_items ci
			ON ci.id = v.id
			AND ci.type = v.type
		SET v.writer_id = 0
		WHERE ci.status NOT IN('published', 'published_with_draft')";
	$result = ze\sql::update($sql);

	ze\dbAdm::revision(54404);
}

//In 9.2, there was a new plugin setting and 1 removed framework for Multiple Image Container plugin.
//This logic will set the framework to standard, and enable the relevant setting.
if (ze\dbAdm::needRevision(54631)) {
	$module = 'zenario_multiple_image_container';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);

		//Non-nested plugins
		$pluginInstancesSql = '
			SELECT id, framework
			FROM ' . DB_PREFIX . 'plugin_instances
			WHERE module_id = ' . (int)$moduleId . '
			AND framework = "image_then_title"';
		$pluginInstancesResult = \ze\sql::select($pluginInstancesSql);

		while ($row = \ze\sql::fetchAssoc($pluginInstancesResult)) {
			ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['id'], 'egg_id' => 0, 'name' => 'show_caption_above_thumbnail']);
			ze\row::set('plugin_instances', ['framework' => 'standard'], ['id' => $row['id']]);
		}
		
		//Nested plugins
		$nestedPluginInstancesSql = '
			SELECT instance_id, id, framework
			FROM ' . DB_PREFIX . 'nested_plugins
			WHERE module_id = ' . (int)$moduleId . '
			AND framework = "image_then_title"';
		$nestedPluginsResult = \ze\sql::select($nestedPluginInstancesSql);

		while ($row = \ze\sql::fetchAssoc($nestedPluginsResult)) {
			ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => $row['instance_id'], 'egg_id' => $row['id'], 'name' => 'show_caption_above_thumbnail']);
			ze\row::set('nested_plugins', ['framework' => 'standard'], ['instance_id' => $row['instance_id'], 'id' => $row['id']]);
		}
	}

	ze\dbAdm::revision(54631);
}

//Fix blank phrases in Extranet Base Login module
if (ze\dbAdm::needRevision(54801)) {
	$module = 'zenario_extranet';
	if (ze\module::isRunning($module)) {
		$defaultLangId = ze::$defaultLang;

		$instances = ze\module::getModuleInstancesAndPluginSettings($module);

		if (!empty($instances) && is_array($instances)) {
			foreach ($instances as $instance) {
				foreach ([
					'invalid_email_error_text' => "Your email address didn't appear to be in a valid format.",		
					'screen_name_required_error_text' => "Please enter your screen name.",		
					'email_address_required_error_text' => "Please enter your email address.",			
					'password_required_error_text' => "Please enter your password.",		
					'no_new_password_error_text' => "Please enter new password.",
					'no_new_repeat_password_error_text' => "Please repeat your new password."
				] as $fieldName => $value) {
					if (isset($instance['settings'][$fieldName]) && !$instance['settings'][$fieldName]) {
						ze\row::update(
							'plugin_settings',
							['value' => ze\escape::sql($value)],
							['name' => ze\escape::sql($fieldName), 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
						);
					}
				}
			}
		}
	}

	ze\dbAdm::revision(54801);
}

//In 9.3, the "floor" schematic object type was renamed to "room".
//Rename the site settings to match...
if (ze\dbAdm::needRevision(55152)) {
	$module = 'assetwolf_2_schematics_2';
	if (ze\module::isRunning($module)) {
		foreach (['assetwolf_schematic_floor_room', 'assetwolf_schematic_floor_female', 'assetwolf_schematic_floor_male', 'assetwolf_schematic_floor_disabled', 'assetwolf_schematic_floor_family'] as $oldSettingName) {
			$value = ze::setting($oldSettingName);

			switch ($oldSettingName) {
				case 'assetwolf_schematic_floor_room':
					$newSettingName = 'assetwolf_schematic_room_generic';
					break;
				case 'assetwolf_schematic_floor_female':
					$newSettingName = 'assetwolf_schematic_room_female';
					break;
				case 'assetwolf_schematic_floor_male':
					$newSettingName = 'assetwolf_schematic_room_male';
					break;
				case 'assetwolf_schematic_floor_disabled':
					$newSettingName = 'assetwolf_schematic_room_disabled';
					break;
				case 'assetwolf_schematic_floor_family':
					$newSettingName = 'assetwolf_schematic_room_family';
					break;
			}

			ze\site::setSetting($newSettingName, $value);
			ze\row::delete('site_settings', ['name' => $oldSettingName]);
		}
	}

	ze\dbAdm::revision(55152);
}

//... and the plugin settings too.
if (ze\dbAdm::needRevision(55153)) {
	$module = 'assetwolf_2_schematics_2';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);
		$instances = ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['enable.schematic_floors']) && $instance['settings']['enable.schematic_floors'] == 1) {
				ze\row::set('plugin_settings', ['value' => 1], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'enable.add_a_room']);
			}
		}

		ze\row::delete('plugin_settings', ['name' => 'enable.schematic_floors']);
	}

	ze\dbAdm::revision(55153);
}

//In 9.3, the "Cookie consent" plugin setting in HTML snippet and Twig snippet
//had one value removed. Migrate value 3 to value 2.
if (ze\dbAdm::needRevision(55601)) {
	$modules = ['zenario_html_snippet', 'zenario_twig_snippet'];
	foreach ($modules as $module) {
		if (ze\module::isRunning($module)) {
			$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);
			$instances = ze\module::getModuleInstancesAndPluginSettings($module);
			
			foreach ($instances as $instance) {
				if (!empty($instance['settings']['cookie_consent']) && $instance['settings']['cookie_consent'] == 'required') {
					ze\row::update('plugin_settings', ['value' => 'needed'], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'cookie_consent']);
				}
			}
		}
	}

	ze\dbAdm::revision(55601);
}

if (ze\dbAdm::needRevision(56251)) {
	$module = 'zenario_location_map_and_listing_2';
	if (ze\module::isRunning($module)) {
		$moduleId = ze\row::get('modules', 'id', ['class_name' => $module]);
		$instances = ze\module::getModuleInstancesAndPluginSettings($module);
		
		foreach ($instances as $instance) {
			if (!empty($instance['settings']['location_display']) && $instance['settings']['hide_filters_list'] == 'show_all_locations') {
				if (!empty($instance['settings']['hide_filters_list']) && $instance['settings']['hide_filters_list'] == 1) {
					ze\row::set('plugin_settings', ['value' => 'show_all_locations_with_filter'], ['instance_id' => (int)$instance['instance_id'], 'egg_id' => (int)$instance['egg_id'], 'name' => 'location_display']);
				}
			}
		}

		ze\row::delete('plugin_settings', ['name' => 'hide_filters_list']);
	}

	ze\dbAdm::revision(56251);
}


//Clear out the cache/tuix/ directory, as I've changed the naming format
//Note: This is a re-issued revision. It's been run before in previous versions, but I'm bumping it
//as I want to run it again.
if (ze\dbAdm::needRevision(56600)) {
	
	if (is_dir(CMS_ROOT. 'cache/tuix')) {
		ze\row::update('tuix_file_contents', ['last_modified' => 0, 'checksum' => ''], []);
		ze\cache::deleteDir(CMS_ROOT. 'cache/tuix', 1);
		ze\cache::cleanDirs(true);
	}
	
	ze\dbAdm::revision(56600);
}


//A little bit more migration for the data in the layout_head_and_foot table.
//In the step 2 migraiton file, we tried to match a layout to the header/footer that looked like
//it was the most common one, and copy the basic settings.
//There are some more settings that need to be copied in the JSON data though, so try to come back
//and copy those now.
//Note: this will only work when migrating an existing site, not when doing a fresh install
//or site reset. There's another revision just below that will handle this case.
if (ze\dbAdm::needRevision(56660)) {
	
	$headerInfo = ze\row::get('layout_head_and_foot', true, ['for' => 'sitewide']);
	if ($headerInfo) {
		if ($layoutData = ze\row::get('layouts', 'json_data', [
			'cols' => $headerInfo['cols'],
			'min_width' => $headerInfo['min_width'],
			'max_width' => $headerInfo['max_width'],
			'fluid' => $headerInfo['fluid'],
			'responsive' => $headerInfo['responsive']
		])) {
			unset($layoutData['cells']);
			ze\row::update('layout_head_and_foot', [
				'head_json_data' => $layoutData,
				'foot_json_data' => (object) []		//N.b. I want an empty object here, not an empty array.
			], []);
		}
	}
	
	ze\dbAdm::revision(56660);
}


//Catch the case where this is a fresh install, site reset, or we couldn't migrate the settings
//from a layout to the site-wide header.
if (ze\dbAdm::needRevision(56790)) {
	
	$headerInfo = ze\row::get('layout_head_and_foot', true, ['for' => 'sitewide']);
	if (!$headerInfo
	  || empty($headerInfo['cols'])
	  || empty($headerInfo['head_json_data'])) {
		
		//Several things in the CMS will break if there is no layout header information.
		//Save some default values into the header to address this. 
		$data = \ze\gridAdm::sensibleDefault();
		$details = ['foot_json_data' => (object) []];	//N.b. I want an empty object here, not an empty array.
		
		ze\gridAdm::trimData($data);
		ze\gridAdm::updateHeaderMetaInfoInDB($data, $details);
	}
	
	ze\dbAdm::revision(56790);
}


//We've recently discovered a bug where some SVG files had their width and height incorrectly
//read as 100✖️100 because our functions incorrectly read their metadata.
//This has been fixed, but we need to watch out for any SVG that looks like it might have been
//affected and rescan it.
if (ze\dbAdm::needRevision(57200)) {
	
	if (function_exists('simplexml_load_string')) {
		foreach (ze\row::getValues('files', 'id', [
			'mime_type' => 'image/svg+xml', 'width' => 100, 'height' => 100
		]) as $imageId) {
			$file = ze\row::get('files', ['width', 'height', 'data'], $imageId);
			
			if (ze\file::getWidthAndHeightOfSVG($file, $file['data'])) {
				unset($file['data']);
				ze\row::update('files', $file, $imageId);
			}
			unset($file);
		}
	}

	ze\dbAdm::revision(57200);
}

//In 9.4, the Maximum user image file size setting was updated
//to match similar changes to maximum file upload size from revision 52220
//(from a field that only accepts bytes to a shorter field with a unit selector).
//Update the value to work correctly.
if (ze\dbAdm::needRevision(57211)) {
	if (ze\module::inc('zenario_users')) {
		$filesizevalue = ze::setting('max_user_image_filesize', false);
		$filesizeUnit = ze::setting('max_user_image_filesize_unit', false);
	
		if ($filesizevalue && !$filesizeUnit) {
		
			if ($filesizevalue < 1000000) {
				$newFileValue = 50;
				$newFileUnit = 'KB';
			} else {
				$fileSizeConvertValue = ze\file::fileSizeConvert($filesizevalue);
				$convertArray = explode(' ', $fileSizeConvertValue);
				$newFileValue = $convertArray[0];
				$newFileUnit = $convertArray[1];
			}
		
			ze\site::setSetting('max_user_image_filesize', $newFileValue);
			ze\site::setSetting('max_user_image_filesize_unit', $newFileUnit);
	
		} elseif (!$filesizevalue) {
			ze\site::setSetting('max_user_image_filesize', 50);
			ze\site::setSetting('max_user_image_filesize_unit', 'KB');
		}
	}
	
	ze\dbAdm::revision(57211);
}

//On a very small number of sites, people replied on being able to access the ze\row library in Twig Snippets,
//however this has recently been locked down as a measure to improve security.
//They now need to specifically create functions for the queries they'll need, and whitelist them.
//There are actually only a few small number of places where we know someone has done this, so I can write
//a SQL statement that should catch and fix the known bad cases.
if (ze\dbAdm::needRevision(57700)) {
	
	$sql = "
		SELECT id, custom_yaml
		FROM ". DB_PREFIX. "tuix_snippets
		WHERE custom_yaml LIKE '%ze(%'
	";
	
	foreach (ze\sql::select($sql) as $ts) {
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'date\', \'relative\'',
			'ze(\'date\', \'formatRelativeDateTime\'',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'user\', \'can\'',
			'ze(\'user\', \'currentUserCan\'',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'row\', \'get\', constant(\'ASSETWOLF_2_PREFIX\') ~ \'nodes\', \'schema_id\', nodeId)',
			'ze(\'assetwolf\', \'nodeSchemaId\', nodeId)',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'row\', \'get\', constant(\'ASSETWOLF_2_PREFIX\') ~ \'schema_fields\', \'metric_id\', {"key" : id, "schema_id": schemaId})',
			'ze(\'assetwolf\', \'fieldMetricId\', id, schemaId)',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'row\', \'get\', constant(\'ASSETWOLF_2_PREFIX\') ~ \'metrics\', \'period\', metricId)',
			'ze(\'assetwolf\', \'metricPeriod\', metricId)',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'row\', \'get\', constant(\'ASSETWOLF_2_PREFIX\') ~ \'metrics\', \'run_frequency\', metricId)',
			'ze(\'assetwolf\', \'metricRunFrequency\', metricId)',
		$ts['custom_yaml']);
		
		$ts['custom_yaml'] = str_replace(
			'ze(\'row\\\\da\', \'getValues\', \'datapoints\', \'timestamp_1\', {node_id: item.id}, [\'timestamp_1\', \'DESC\'], false, false, 2)',
			'ze(\'assetwolf\', \'lastTwoDatapointsFromNode\', item.id)',
		$ts['custom_yaml']);
		
		
		try {
			$tuix = \Spyc::YAMLLoadString(trim($ts['custom_yaml']));
			$ts['custom_json'] = json_encode($tuix, JSON_FORCE_OBJECT);
	
		} catch (\Exception $e) {
			$ts['custom_json'] = '';
		}
		
		ze\row::update('tuix_snippets', [
			'custom_yaml' => $ts['custom_yaml'],
			'custom_json' => $ts['custom_json']
		], $ts['id']);
	}
	
	ze\dbAdm::revision(57700);
}


//Same as above, but this time replace a line where the constant() function was used
if (ze\dbAdm::needRevision(57710)) {
	
	$sql = "
		SELECT id, custom_yaml
		FROM ". DB_PREFIX. "tuix_snippets
		WHERE custom_yaml LIKE '%constant(%'
	";
	
	foreach (ze\sql::select($sql) as $ts) {
		
		$ts['custom_yaml'] = str_replace(
			'constant(\'ASSETWOLF_TSM\')',
			'ze(\'assetwolf\', \'timestampMultipler\')',
		$ts['custom_yaml']);
		
		
		try {
			$tuix = \Spyc::YAMLLoadString(trim($ts['custom_yaml']));
			$ts['custom_json'] = json_encode($tuix, JSON_FORCE_OBJECT);
	
		} catch (\Exception $e) {
			$ts['custom_json'] = '';
		}
		
		ze\row::update('tuix_snippets', [
			'custom_yaml' => $ts['custom_yaml'],
			'custom_json' => $ts['custom_json']
		], $ts['id']);
	}
	
	ze\dbAdm::revision(57710);
}



//The format of cached plugins has changed in 9.5.
//Attempt to clear out the cache/pages directory to avoid accidentally loading the previous format
if (ze\dbAdm::needRevision(57775)) {
	if (is_dir(CMS_ROOT. 'cache/pages')
	 && !ze\server::isWindows()
	 && ze\server::execEnabled()) {
		exec('rm -r '. escapeshellarg(CMS_ROOT. 'cache/pages'));
	}
	ze\cache::cleanDirs(true);
	
	ze\dbAdm::revision(57775);
}

//In 9.5, the Maximum location image file size setting was updated
//from a field that only accepts bytes to a shorter field with a unit selector.
//Update the value to work correctly.
if (ze\dbAdm::needRevision(57776)) {
	if (ze\module::inc('zenario_location_manager')) {
		$filesizevalue = ze::setting('max_location_image_filesize', false);
		$filesizeUnit = ze::setting('max_location_image_filesize_unit', false);
	
		if ($filesizevalue && !$filesizeUnit) {
			//The default value was 50 KB, so keep that, but if the old value would have reached 1 GB or more, cap it at 1023 MB.
			if ($filesizevalue < 1000000) {
				$newFileValue = 50;
				$newFileUnit = 'KB';
			} elseif ($filesizevalue >= 1072693248) {
				$newFileValue = 1023;
				$newFileUnit = 'MB';
			} else {
				$fileSizeConvertValue = ze\file::fileSizeConvert($filesizevalue);
				$convertArray = explode(' ', $fileSizeConvertValue);
				$newFileValue = $convertArray[0];
				$newFileUnit = $convertArray[1];
			}
		
			ze\site::setSetting('max_location_image_filesize', $newFileValue);
			ze\site::setSetting('max_location_image_filesize_unit', $newFileUnit);
	
		} elseif (!$filesizevalue) {
			ze\site::setSetting('max_location_image_filesize', 50);
			ze\site::setSetting('max_location_image_filesize_unit', 'KB');
		}
	}
	
	ze\site::setSetting('max_location_image_filesize_override', 'limit_max_image_file_size');
	
	ze\dbAdm::revision(57776);
}

//In 9.5, the maximum image/attachment file size settings were further redesigned.
//Now, the "Override" setting is a checkbox, not a radio anymore.
//Tidy up the settings.
if (ze\dbAdm::needRevision(57777)) {
	if (ze\module::inc('zenario_users')) {
		$userImageOverrideValue = ze::setting('max_user_image_filesize_override', false);
	
		if ($userImageOverrideValue == 'use_global_max_attachment_file_size') {
			ze\site::setSetting('max_user_image_filesize_override', false);
		} else {
			ze\site::setSetting('max_user_image_filesize_override', true);
		}
	}
	
	if (ze\module::inc('zenario_location_manager')) {
		$locationImageOverrideValue = ze::setting('max_location_image_filesize_override', false);
	
		if ($locationImageOverrideValue == 'use_global_max_image_file_size') {
			ze\site::setSetting('max_location_image_filesize_override', false);
		} else {
			ze\site::setSetting('max_location_image_filesize_override', true);
		}
	}
	
	if (ze\module::inc('zenario_user_forms')) {
		$formAttachmentOverrideValue = ze::setting('zenario_user_forms_max_attachment_file_size_override', false);
	
		if ($formAttachmentOverrideValue == 'use_global_max_attachment_file_size') {
			ze\site::setSetting('zenario_user_forms_max_attachment_file_size_override', false);
		} else {
			ze\site::setSetting('zenario_user_forms_max_attachment_file_size_override', true);
		}
	}
	
	ze\dbAdm::revision(57777);
}

//Update the Captcha radios to correctly migrate if a site was using Captcha.
//Please note: this was backpatched from 9.5 to 9.4, and is safe to run multiple times.
if (ze\dbAdm::needRevision(57983)) {
	$captchaRadiosValue = ze::setting('captcha_status_and_version');
	if ($captchaRadiosValue == 'not_enabled') {
		$siteKey = ze::setting('google_recaptcha_site_key');
		$secretKey = ze::setting('google_recaptcha_secret_key');
		
		if (!empty($siteKey) && !empty($secretKey)) {
			ze\site::setSetting('captcha_status_and_version', 'enabled_v2');
		}
	}
	
	ze\dbAdm::revision(57983);
}



//Check for missing @media print { ... } rules in skin stylesheets.
//Please note: this was backpatched from 9.6 to 9.5, however is safe to run multiple times.
if (ze\dbAdm::needRevision(58564)) {
	
	
	$printFiles = [];
	
	foreach (\ze\row::getValues('skins', ['id', 'name', 'display_name'], ['missing' => 0]) as $skin) {
		$printFile = \ze\content::skinPath($skin['name']). 'editable_css/print.css';
		
		if (file_exists(CMS_ROOT. $printFile)) {
			if ($css = file_get_contents(CMS_ROOT. $printFile)) {
				if (!preg_match('/@media\s+print\b/i', $css)) {
					if (is_writable(CMS_ROOT. $printFile)) {
						file_put_contents(CMS_ROOT. $printFile, "\n". '@media print {'. "\n\n". $css. "\n\n". '}'. "\n");
					} else {
						$printFiles[] = $printFile;
					}
				}
			}
		}
	}
	
	
	if (!empty($printFiles)) {
		$mrg = [
			'example' => $printFiles[0]
		];
		
		echo ze\admin::nPhrase(
			'The print-stylesheet at [[example]] and 1 other are missing their "@media print { ... }" rule. Please either add this, or make the files writable so this script can automatically add the rule.',
			'The print-stylesheet at [[example]] and [[count]] others are missing their "@media print { ... }" rule. Please either add this, or make the files writable so this script can automatically add the rule.',
			count($printFiles) - 1,
			$mrg,
			'The print-stylesheet at [[example]] is missing its "@media print { ... }" rule. Please either add this, or make the file writable so this script can automatically add the rule.'
		);
		exit;
	}
	
	ze\dbAdm::revision(58564);
}

