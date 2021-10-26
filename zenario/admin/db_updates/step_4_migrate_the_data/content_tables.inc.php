<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
*/



//Delete any cached frameworks, as the format has changed slightly in this version
if (ze\dbAdm::needRevision(45600)) {
	
	\ze\skinAdm::clearCacheDir();
	
	ze\dbAdm::revision(45600);
}

//(N.b. this was added in an after-branch patch in 8.3 revision 46308, but is safe to re-run.)
ze\dbAdm::revision(47037
,  <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates`
	SET period_to_delete_log_headers = 0
	WHERE period_to_delete_log_headers = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]email_templates`
	SET period_to_delete_log_content = 0
	WHERE period_to_delete_log_content = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_email_template_sending_log_headers'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_email_template_sending_log_content'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_sign_in_log'
	AND value = 'never_save'
_sql
,  <<<_sql
	UPDATE `[[DB_PREFIX]]site_settings`
	SET value = 0
	WHERE name = 'period_to_delete_the_user_content_access_log'
	AND value = 'never_save'
_sql

);

//A bug caused uploaded documents to be added to the database instead of the docstore.
//Now the bug is fixed we need to sort out the bugged documents by re-uploading them into
//the docstore.
//(N.b. this was added in an after-branch patch in 8.3 revision 46310, but is safe to re-run.)
if (ze\dbAdm::needRevision(47601)) {
	$sql = "
		SELECT d.id, d.file_id, f.filename, f.location, f.path, f.short_checksum, f.usage
		FROM " . DB_PREFIX . "documents d
		INNER JOIN " . DB_PREFIX . "files f
			ON d.file_id = f.id
		WHERE d.type = 'file' 
		AND d.privacy = 'public'";
	$result = ze\sql::select($sql);
	while ($doc = ze\sql::fetchAssoc($result)) {
	
		if (!file_exists(CMS_ROOT. 'public/downloads/'. $doc['short_checksum']. '/'. ze\file::safeName($doc['filename']))) {
			$publicLink = ze\document::generatePublicLink($doc['id']);
		
			if (ze::isError($publicLink)) {
				$location = ze\file::link($doc['file_id']);
				$newFileId = ze\file::addToDocstoreDir($doc['usage'], rawurldecode(CMS_ROOT . $location), $doc['filename']);
			
				if ($newFileId) {
					ze\row::update('documents', ['file_id' => $newFileId], $doc['id']);
				}
			} 
		}
	}
	
	ze\dbAdm::revision(47601);
}

//This code would migrate email templates to using curly brackets, if we choose to go and do that

////Update email templates to use {{ and }} for merge fields rather than [[ and ]].
//if (ze\dbAdm::needRevision(48400)) {
//	$sql = "
//		SELECT id, subject, email_address_from, email_name_from, body
//		FROM " . DB_PREFIX . "email_templates";
//	$result = ze\sql::select($sql);
//	while ($et = ze\sql::fetchAssoc($result)) {
//		$etId = $et['id'];
//		unset($et['id']);
//		
//		foreach ($et as &$val) {
//			$val = preg_replace('@\{\{(\s*\w+?\s*)\}\}@', '[[$1]]', $val);
//		}
//		unset($val);
//		
//		ze\row::update('email_templates', $et, $etId);
//	}
//	
//	ze\dbAdm::revision(48400);
//}




//Content Summary List (and Blog News List), and search modules had an update of settings.
//The max number of elements is now a text field rather than a dropdown of values.
if (ze\dbAdm::needRevision(48641)) {
	$sql = '
		SELECT ps.instance_id, ps.egg_id, ps.name, ps.value, ps.is_content, m.class_name
		FROM ' . DB_PREFIX . 'plugin_settings ps
		LEFT JOIN ' . DB_PREFIX . 'plugin_instances pi
			ON ps.instance_id = pi.id
		LEFT JOIN ' . DB_PREFIX . 'modules m
			ON pi.module_id = m.id
		WHERE ps.name = "page_size";';
			
	$result = ze\sql::select($sql);
	
	$modules = [
		'zenario_search_entry_box',
		'zenario_search_entry_box_predictive_probusiness',
		'zenario_search_results',
		'zenario_search_results_pro',
		'zenario_advanced_search',
		'zenario_content_list',
		'zenario_blog_news_list'
	];
	
	$values = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 20, 50];
	
	while ($row = ze\sql::fetchAssoc($result)) {
		if (in_array($row['class_name'], $modules) && in_array($row['value'], $values)) {
			ze\row::insert('plugin_settings', ['instance_id' => $row['instance_id'], 'egg_id' => $row['egg_id'], 'name' => 'maximum_results_number', 'value' => $row['value'], 'is_content' => $row['is_content']], $ignore = true);
			ze\row::update('plugin_settings', ['value' => 'maximum_of'], ['instance_id' => $row['instance_id'], 'egg_id' => $row['egg_id'], 'name' => 'page_size']);
		}
	}
	
	ze\dbAdm::revision(48641);
}


//Migrate any custom TUIX to the new system
ze\dbAdm::revision( 50330

//Look for any existing custom TUIX defined in the plugin settings, and attempt to copy it into the new table
, <<<_sql
	INSERT INTO `[[DB_PREFIX]]tuix_snippets` (name, custom_yaml, custom_json)
	SELECT CONCAT('Migrated TUIX Snippet ', SUBSTR(SHA1(cj.value), 1, 10)), MIN(cy.value), cj.value
	FROM [[DB_PREFIX]]plugin_settings AS cj
	INNER JOIN [[DB_PREFIX]]plugin_settings AS cy
	   ON cy.instance_id = cj.instance_id
	  AND cy.egg_id = cj.egg_id
	  AND cy.name = '~custom_yaml~'
	WHERE cj.name = '~custom_json~'
	  AND cj.value IS NOT NULL
	  AND cj.value != ''
	  AND TRIM(cj.value) != ''
	GROUP BY cj.value
_sql

//Update the plugin settings to point back to the table
, <<<_sql
	UPDATE [[DB_PREFIX]]plugin_settings AS cj
	INNER JOIN [[DB_PREFIX]]tuix_snippets AS tc
	   ON tc.custom_json =  cj.value
	SET cj.name = '~tuix_snippet~',
		cj.value = tc.id
	WHERE cj.name = '~custom_json~'
_sql

, <<<_sql
	DELETE FROM [[DB_PREFIX]]plugin_settings
	WHERE name IN ('~custom_yaml~', '~custom_json~')
_sql
);


//Try to give any new TUIX snippets a more helpful name
if (ze\dbAdm::needRevision(50340)) {
	foreach (ze\row::getAssocs('tuix_snippets', ['name', 'custom_json']) as $tuixSnippetId => $tuix) {
		if (($custom = json_decode($tuix['custom_json'], true))
		 && (is_array($custom))
		 && (!empty($custom))) {
			
			foreach (['columns', 'item_buttons'] as $tag) {
				if (!empty($custom[$tag]) && is_array($custom[$tag])) {
					$tuix['name'] .= ' ('. $tag. ': '. implode(', ', array_keys($custom[$tag])). ')';
				}
			}
			ze\row::set('tuix_snippets', $tuix, $tuixSnippetId);
		}
	}
	ze\dbAdm::revision(50340);
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

//Plugin settings cleanup for Content Summary List and Blog/News List
if (ze\dbAdm::needRevision(51052)) {
	foreach (['zenario_content_list', 'zenario_blog_news_list'] as $module) {
		if (ze\module::isRunning($module)) {
			$instances = ze\module::getModuleInstancesAndPluginSettings($module);
			foreach ($instances as $instance) {
				//Plugin settings cleanup #1: there are 2 identical settings for showing content items in any/all selected categories.
				//Remove one and set its value to the remaining setting.
				
				//Check if the old setting is in use.
				if (isset($instance['settings']['refine_type_content_with_matching_categories'])) {
			
					//Setting is in use, and the filtering option is "Show content with categories matching the current content item"
					if (isset($instance['settings']['category_filters_dropdown']) && ($instance['settings']['category_filters_dropdown'] == 'show_content_with_matching_categories')) {
						//Check if the target value already exists in the DB: set the value if so...
						if (isset($instance['settings']['refine_type'])) {
							ze\row::update(
								'plugin_settings',
								['value' => ze\escape::sql($instance['settings']['refine_type_content_with_matching_categories'])],
								['name' => 'refine_type', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
							);
						} else {
							//... otherwise create it.
							ze\row::insert(
								'plugin_settings',
								['name' => 'refine_type', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id'], 'value' => ze\escape::sql($instance['settings']['refine_type_content_with_matching_categories'])]
							);
						}
					}
			
					//Delete the old setting entry.
					ze\row::delete(
						'plugin_settings',
						['name' => 'refine_type_content_with_matching_categories', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
				}
		
				//Plugin settings cleanup #2: fix a bug where there are categories selected, but the Categories filter dropdown has no value.
				if (!empty($instance['settings']['category'])) {
					if (empty($instance['settings']['category_filters_dropdown'])) {
						ze\row::set(
							'plugin_settings',
							['value' => 'choose_categories_to_display_or_omit'],
							['name' => 'category_filters_dropdown', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
						);
					}
				}
			}
		}
	}
	ze\dbAdm::revision(51052);
}

//Plugin settings update for User Redirector:
//replaced the old redirection rules with the ones from Extranet Login
if (ze\dbAdm::needRevision(51053)) {
	if (ze\module::isRunning('zenario_user_redirector')) {
		$instances = ze\module::getModuleInstancesAndPluginSettings('zenario_user_redirector');
		foreach ($instances as $instance) {
			//Previously the plugin supported up to 4 redirection rules. Migrate these rules.
			$counter = 0;
			foreach (range(1, 4) as $i) {
				$condition = false;
				if ($i == 1) {
					$condition = (!empty($instance['settings']['group_' . $i]) && !empty($instance['settings']['redirect_' . $i]));
				} elseif ($i >= 2 && $i <= 4) {
					$condition = (!empty($instance['settings']['show_' . $i]) && !empty($instance['settings']['group_' . $i]) && !empty($instance['settings']['redirect_' . $i]));
				}

				if ($condition) {
					//The old logic only allowed redirection rules based on group membership.
					//The new logic requires to specify the rule type.
					ze\row::insert(
						'plugin_settings',
						['name' => 'redirect_rule_type__' . (int)$i, 'value' => 'group', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
					
					//Rename the old settings
					ze\row::update(
						'plugin_settings',
						['name' => 'redirect_rule_group__' . (int)$i],
						['name' => 'group_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);

					ze\row::update(
						'plugin_settings',
						['name' => 'redirect_rule_content_item__' . (int)$i],
						['name' => 'redirect_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
					);
			
					//Delete the old settings entries if necessary
					if ($i >= 2 && $i <= 4) {
						ze\row::delete(
							'plugin_settings',
							['name' => 'show_' . (int)$i, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
						);
					}

					$counter++;
				}
			}

			//Insert new value for the new radios
			ze\row::insert(
				'plugin_settings',
				['name' => 'show_welcome_page', 'value' => '_ALWAYS', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
			);

			//Save the total count of redirection rules
			ze\row::insert(
				'plugin_settings',
				['name' => 'number_of_redirect_rules', 'value' => (int)$counter, 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
			);
			
			//Handle the default redirect
			if (!empty($instance['settings']['show_default']) && !empty($instance['settings']['redirect_default'])) {
				//Rename the old setting
				ze\row::update(
					'plugin_settings',
					['name' => 'welcome_page'],
					['name' => 'redirect_default', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
				);

				//Delete the old setting entry
				ze\row::delete(
					'plugin_settings',
					['name' => 'show_default', 'instance_id' => $instance['instance_id'], 'egg_id' => $instance['egg_id']]
				);
			}
		}
	}
	ze\dbAdm::revision(51053);
}


//Work around a bug where the fea_type property in TUIX files is only read when the files change,
//however in some weird cases it was possible for the checksum of the file to be recorded before
//the code that read the value was implemented.
//(Note: this was back-patched to 8.7, but is safe to re-run.)
if (ze\dbAdm::needRevision(51900)) {
	
	if (is_dir(CMS_ROOT. 'cache/tuix')) {
		ze\row::update('tuix_file_contents', ['last_modified' => 0, 'checksum' => ''], []);
		ze\cache::deleteDir(CMS_ROOT. 'cache/tuix', 1);
		ze\cache::cleanDirs(true);
	}
	
	ze\dbAdm::revision(51900);
}


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
				$addressToOverriddenBy = \ze::setting('debug_override_email_address');

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

				ze\server::sendEmail($subject, $message, EMAIL_ADDRESS_GLOBAL_SUPPORT, $addressToOverriddenBy);
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
			$fileValue = 1;
			$fileUnit = 'MB';
		} else {
			$fileSizeConvertValue = ze\file::fileSizeConvert($filesizevalue);
			$convertArray = explode(' ', $fileSizeConvertValue);
			$fileValue = $convertArray[0];
			$fileUnit = $convertArray[1];
		}
		
		ze\site::setSetting('content_max_filesize', $filesizevalue);
		ze\site::setSetting('content_max_filesize_unit', $filesizeUnit);
	
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
				
				$layout['json_data'] = $data;
				$layout['json_data_hash'] = ze::hash64(json_encode($data), 8);
				
				ze\row::update('layouts', [
					'json_data' => $layout['json_data'],
					'json_data_hash' => $layout['json_data_hash']
				], $layout['layout_id']);
				
				ze\gridAdm::updateMetaInfoInDB($data, $layout);
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