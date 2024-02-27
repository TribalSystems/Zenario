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

namespace ze;

class contentAdm {


	public static function statusPhrase($status) {
		switch ($status) {
			case 'first_draft':
				return \ze\admin::phrase('First Draft');
			case 'hidden':
				return \ze\admin::phrase('Hidden');
			case 'hidden_with_draft':
				return \ze\admin::phrase('Hidden with Draft');
			case 'published':
				return \ze\admin::phrase('Published');
			case 'published_with_draft':
				return \ze\admin::phrase('Published with Draft');
			case 'unlisted':
				return \ze\admin::phrase('Published unlisted');
			case 'unlisted_with_draft':
				return \ze\admin::phrase('Published unlisted with Draft');
			case 'trashed':
				return \ze\admin::phrase('Trashed');
			case 'trashed_with_draft':
				return \ze\admin::phrase('Trashed with Draft');
		}
	
		return '';
	}

	public static function cTypeDetails($cType) {
	
		if (is_array($cType)) {
			//Allow people to just use this function for formatting, if they already have the row from the db
			$details = $cType;
		} else {
			$details = \ze\row::get('content_types', true, \ze\escape::ascii($cType));
		}
		
		if ($details) {
		
			if (!$details['content_type_plural_en']) {
				$details['content_type_plural_en'] == $details['content_type_name_en'];
			}
	
			$char2 = substr($details['content_type_plural_en'], 1, 1);
			if ($char2 === strtolower($char2)) {
				$details['content_type_plural_lower_en'] = strtolower(substr($details['content_type_plural_en'], 0, 1)). substr($details['content_type_plural_en'], 1);
			} else {
				$details['content_type_plural_lower_en'] = $details['content_type_plural_en'];
			}
		}
	
		return $details;
	}


	public static function isCTypeRunning($cType) {
		return
			$cType == 'html' || (
				($moduleId = \ze\row::get('content_types', 'module_id', ['content_type_id' => $cType]))
			 && (\ze::in(\ze\module::status($moduleId), 'module_running', 'module_is_abstract'))
			 && (\ze::moduleDir(\ze\module::className($moduleId)))
			);
	}
	
	public static function versionStatus($cVersion, $visitorVersion, $adminVersion, $status) {

		if (\ze\content::isDraft($status)
		 && $cVersion == $adminVersion
		 && $cVersion != $visitorVersion) {
			return 'draft';
	
		} elseif (($cVersion == $adminVersion && $status == 'published')
			   || ($cVersion == $visitorVersion && $status == 'published_with_draft')) {
		
			return 'published';

		} elseif (($cVersion == $adminVersion && $status == 'unlisted')
			   || ($cVersion == $visitorVersion && $status == 'unlisted_with_draft')) {
		
			return 'unlisted';

		} elseif (($cVersion == $adminVersion && $status == 'hidden')
			   || ($cVersion == $adminVersion - 1 && $status == 'hidden_with_draft')) {
	
			return 'hidden';

		} elseif (($cVersion == $adminVersion && $status == 'trashed')
			   || ($cVersion == $adminVersion - 1 && $status == 'trashed_with_draft')) {
	
			return 'trashed';

		} else {
			return 'archived';
		}
	}

	//Reverse of the above
	public static function getSettingsFromDefaultMenuPosition($position, &$parentId, &$startOrEnd) {
	
		$parentTagParts = explode('_', $position);
	
		if ($parentId = (int) ($parentTagParts[1] ?? 0)) {
			if (empty($parentTagParts[2])) {
				$parentId = \ze\row::get('menu_nodes', 'parent_id', $parentId);
				$startOrEnd = 'start';
			} else {
				$startOrEnd = 'end';
			}
		}
	
		return (bool) $parentId;
	}

	//Some common functionality for formatting content item rows in Organizer panels
	public static function formatItemRow(&$item) {
		
		$sql = "
			SELECT t.menu_id AS isTranslated
			FROM ". DB_PREFIX. "menu_nodes AS m
			LEFT JOIN ". DB_PREFIX. "menu_text AS t
			   ON t.menu_id = m.id
			  AND t.language_id = '". \ze\escape::sql($item['language_id']). "'
			WHERE m.equiv_id = ". (int) $item['equiv_id']. "
			  AND m.content_type = '". \ze\escape::sql($item['type']). "'
			  AND m.target_loc = 'int'";
		
		$menuNodes = 0;
		$translatedMenuNodes = 0;
		foreach (\ze\sql::select($sql) as $row) {
			++$menuNodes;
			
			if ($row['isTranslated']) {
				++$translatedMenuNodes;
			}
		}
		
		if ($translatedMenuNodes == 0 && $menuNodes > 0) {
			$item['menunodecounter'] = 'menu_node_text_missing';
		} else {
			$item['menunodecounter'] = $translatedMenuNodes;
		}
		
		$item['number_of_categories'] =
			\ze\sql::fetchValue("
				SELECT COUNT(cil.category_id)
				FROM ". DB_PREFIX. "category_item_link AS cil
				WHERE cil.equiv_id = ". (int) $item['equiv_id']. "
				  AND cil.content_type = '". \ze\escape::sql($item['type']). "'
			");
	}



	public static function createDraft(&$cIDTo, $cIDFrom, $cType, &$cVersionTo, $cVersionFrom = false, $languageId = false, $adminId = false, $useCIDIfItDoesntExist = false, $cTypeFrom = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function updateVersion($cID, $cType, $cVersion, $version = []) {
		
		//If the result of the ze\contentAdm::checkIfVersionChanged() has been cached,
		//clear the cached value, as it might not be in date any more
		$version['version_changed'] = 'not_checked';
		
		//Mark down the current time and the currently logged in admin
		$version['last_modified_datetime'] = \ze\date::now(true);
		$version['last_author_id'] = $_SESSION['admin_userid'] ?? false;
		
		\ze\row::update('content_item_versions', $version, ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	}

	public static function checkIfVersionChanged($version) {
		
		//If we have access to the pro features module, and a version previous exists,
		//we can tell accurately whether something has changed.
		if ($version['version'] > 1
		 && \ze\module::inc('zenario_pro_features')
		 && \ze\row::exists(
			'content_item_versions',
			['id' => $version['id'], 'type' => $version['type'], 'version' => $version['version'] -1]
		)) {
			//Cache the result of this check in the `version_changed` column, so we don't have to keep calculating
			//it every time it's displayed, if there have been no changes since last time.
			switch ($version['version_changed']) {
				
				case 'no_changes_made':
					//Special case, if we are sure that no changes have been made,
					//return null not false, so the caller can check for this if they need to.
					return null;
				
				case 'changes_made':
					return true;
				
				//If the cache was empty, we'll need to work out whether changes in fact have been made
				case 'not_checked':
					
					//Do an export of both versions, and check if the contents have changed.
					$before = '';
					$after = '';
			
					\zenario_pro_features::createExportFile($before, false, false, $version['id'], $version['type'], $version['version'] -1);
					\zenario_pro_features::createExportFile($after, false, false, $version['id'], $version['type'], $version['version']);
					
					//Sometimes the HTML in the exports can be inconsistent, e.g. if tinyMCE has done some code tidying on the HTML text.
					//Have a few rules ot make sure any whitespace is consistent around <html> tags, but still try to pick up
					//a change if an admin adds a space intentionally.
					$before = str_replace(["\n", "\r", '/>', '>', '<'], [' ', ' ', '>', ' > ', ' < '], $before);
					$after = str_replace(["\n", "\r", '/>', '>', '<'], [' ', ' ', '>', ' > ', ' < '], $after);
					$before = preg_replace('@\s+@', ' ', $before);
					$after = preg_replace('@\s+@', ' ', $after);
					
					//Add a rule to catch inconsistent HTML attributes that flick between an empty string and not being present
					$before = preg_replace('@ \w+=""@', '', $before);
					$after = preg_replace('@ \w+=""@', '', $after);
					
					//Excluding all of the exceptions above, if there are some HTML changes between the two versions,
					//we should consider that there have been changes made.
					$versionChanged = $before != $after;
					
					\ze\row::update(
						'content_item_versions',
						['version_changed' => $versionChanged? 'changes_made' : 'no_changes_made'],
						['id' => $version['id'], 'type' => $version['type'], 'version' => $version['version']]
					);
			
					return $versionChanged? true : null;
			}
		
		} else {
			return $version['last_author_id'] &&
				($version['last_modified_datetime'] != $version['created_datetime']
			  || $version['last_author_id'] != $version['creating_author_id']);
		}
	}

	public static function publishContent($cID, $cType, $adminId = false) {
		if (!$adminId) {
			$adminId = $_SESSION['admin_userid'] ?? false;
		}
	
		if (!($content = \ze\row::get('content_items', ['admin_version', 'alias', 'status'], ['id' => $cID, 'type' => $cType]))
		 || !($cVersion = $content['admin_version'])
		 || !($version = \ze\row::get('content_item_versions', ['release_date'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]))) {
			return false;
		}
	
	
		$oldStatus = $content['status'];
	
		$content['status'] = 'published';
		$content['lock_owner_id'] = 0;
		$content['locked_datetime'] = null;
		$content['visitor_version'] = $cVersion;
	
		$version['publisher_id'] = $adminId;
		$version['published_datetime'] = \ze\date::now();
		$version['access_code'] = null;
	
		$autoSetReleaseDate = \ze\row::get('content_types', 'auto_set_release_date', ['content_type_id' => $cType]);
		if ($autoSetReleaseDate && !$version['release_date']) {
			$version['release_date'] = \ze\date::now();
		}

		if (\ze::in($cType, 'audio', 'document', 'picture', 'video')) {
			$currentPublishedVersion = \ze\row::get('content_items', 'visitor_version', ['id' => $cID, 'type' => $cType]);
			$currentPublishedFileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $currentPublishedVersion]);

			$currentDraftFileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
			
			//Try to delete old files used by previous versions.
			if ($currentDraftFileId != $currentPublishedFileId) {
				//Only delete unused files if this is a published content item with an unpublished draft.
				//The code below will not be executed for never published first drafts.
				if ($currentPublishedFileId) {
					\ze\file::deleteMediaContentItemFileIfUnused($cID, $cType, $currentPublishedFileId);
				}
			}
		}
	
		\ze\row::update('content_items', $content, ['id' => $cID, 'type' => $cType]);
		\ze\row::update('content_item_versions', $version, ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	
		\ze\pluginAdm::removeUnusedVCs($cID, $cType, $content['admin_version']);
		\ze\contentAdm::syncInlineFileContentLink($cID, $cType, $content['admin_version'], true);
	
		$prev_version = $cVersion - 1;
	
	
	
		$sql = "
			DELETE FROM ". DB_PREFIX. "content_cache
			WHERE content_id = ". (int) $cID. "
			  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
			  AND content_version < ". (int) $cVersion;
		\ze\sql::update($sql);

		\ze\contentAdm::hideOrShowContentItemsMenuNode($cID, $cType, $oldStatus, 'published');
	
		\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);

		if ($cType == 'document') {
			//Rescan extract when publishing
			if (\ze\module::inc('zenario_ctype_document')) {
				\zenario_ctype_document::rescanExtract($cType . '_' . $cID);
			}
		}

		\ze\module::sendSignal("eventContentPublished",["cID" => $cID,"cType" => $cType, "cVersion" => $cVersion]);
	}

	//Set the "archived" flag in the inline_images table,
	//and remove links from the inline_images table where the version has been deleted
	public static function flagImagesInArchivedVersions($cID = false, $cType = false) {
	
		$deletedImages = [];
		$undeletedImages = [];
	
		//Look through every image attached to this content item
		$sql = "
			SELECT
				ii.foreign_key_to, ii.foreign_key_id, ii.foreign_key_char, ii.foreign_key_version,
				ii.image_id, ii.archived,
				f.archived AS f_archived,
				v.id IS NULL AS v_deleted,
				v.version NOT IN (c.visitor_version, c.admin_version) AS v_archived
			FROM ". DB_PREFIX. "inline_images AS ii
			INNER JOIN ". DB_PREFIX. "files AS f
			   ON ii.image_id = f.id
			LEFT JOIN ". DB_PREFIX. "content_items AS c
			   ON ii.foreign_key_id = c.id
			  AND ii.foreign_key_char = c.type
			LEFT JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON ii.foreign_key_id = v.id
			  AND ii.foreign_key_char = v.type
			  AND ii.foreign_key_version = v.version
			WHERE ii.foreign_key_to = 'content'";
	
		if ($cID && $cType) {
			$sql .= "
			  AND ii.foreign_key_id = ". (int) $cID. "
			  AND ii.foreign_key_char = '". \ze\escape::sql($cType). "'";
		}
	
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			$key = $row;
			unset($key['f_archived'], $key['v_archived'], $key['v_deleted']);
		
			//If this version is deleted, remove anything from the inline_images table
			if ($row['v_deleted']) {
				\ze\row::delete('inline_images', $key);
			
				//Look for images that were previously deleted by the admin, but were still in use so had to be marked as
				//archived instead.
				if ($row['f_archived']) {
					$deletedImages[$row['image_id']] = $row['image_id'];
				}
		
			//If the version still exists, update the "archived" flag
			} else {
				if ($row['archived'] != $row['v_archived']) {
					\ze\row::update('inline_images', ['archived' => $row['v_archived']], $key);
				}
			
				//If the file was flagged as archived because it was "deleted" but still in use here,
				//"undelete" it by removing the flag to add it back to the library
				if ($row['f_archived'] && !$row['v_archived']) {
					\ze\row::update('files', ['archived' => 0], $row['image_id']);
					$undeletedImages[$row['image_id']] = $row['image_id'];
				}
			}
		}
	
		//Check to see if we can delete any archived images.
		foreach ($deletedImages as $imageId) {
			if (!isset($undeletedImages[$imageId])) {
				\ze\contentAdm::deleteUnusedImage($imageId);
			}
		}
	}


	//Get/set the content of a WYSIWYG Editor
	public static function getContent($cID, $cType, $cVersion, $slotName = false, $moduleName = 'zenario_wysiwyg_editor', $settingName = 'html') {

		$moduleId = \ze\module::id($moduleName);
	
		if ($slotName === false) {
			$slotName = \ze\contentAdm::mainSlot($cID, $cType, $cVersion, $moduleId);
		}
	
		$key = [
				'module_id' => $moduleId,
				'content_id' => $cID,
				'content_type' => $cType,
				'content_version' => $cVersion,
				'slot_name' => $slotName];
	
		if ($instanceId = \ze\row::get('plugin_instances', 'id', $key)) {
			return \ze\row::get(
				'plugin_settings',
				'value',
				[
					'instance_id' => $instanceId,
					'name' => $settingName,
					'is_content' => 'version_controlled_content']);
		}
	
		return false;
	}


	public static function saveContent($content, $cID, $cType, $cVersion, $slotName = false, $moduleName = 'zenario_wysiwyg_editor', $settingName = 'html') {
	
		$moduleId = \ze\module::id($moduleName);
	
		if ($slotName === false) {
			$slotName = \ze\contentAdm::mainSlot($cID, $cType, $cVersion, $moduleId);
		}
	
		$key = [
				'module_id' => $moduleId,
				'content_id' => $cID,
				'content_type' => $cType,
				'content_version' => $cVersion,
				'slot_name' => $slotName];
	
		if (!$instanceId = \ze\row::get('plugin_instances', 'id', $key)) {
			$instanceId = \ze\row::insert('plugin_instances', $key);
		}
	
		\ze\row::set(
			'plugin_settings',
			[
				'value' => $content,
				'is_content' => 'version_controlled_content'],
			[
				'instance_id' => $instanceId,
				'name' => $settingName]);
	
		\ze\contentAdm::syncInlineFileContentLink($cID, $cType, $cVersion);
	}


	//Get/set the content of any version-controlled plugin (except nests)
	public static function getPluginContent($key) {
	
		$instance = \ze\row::get('plugin_instances', ['id', 'module_id', 'framework', 'css_class'], $key);
	
		if (!empty($instance)) {
			$instance['class_name'] = \ze\module::className($instance['module_id']);
			$instance['settings'] = \ze\row::getAssocs('plugin_settings', true, ['instance_id' => $instance['id'], 'egg_id' => 0]);
		
			foreach ($instance['settings'] as &$setting) {
				unset($setting['instance_id']);
			}
		}
	
		return $instance;
	}

	public static function setPluginContent($key, $instance = false) {
	
		if ($instanceId = \ze\row::get('plugin_instances', 'id', $key)) {
		
			\ze\row::delete('plugin_settings', ['instance_id' => $instanceId, 'egg_id' => 0]);
		
			if ($instance && !empty($instance['settings'])) {
				\ze\row::update('plugin_instances', ['framework' => $instance['framework'], 'css_class' => $instance['css_class']], $instanceId);
	
				foreach ($instance['settings'] as $setting) {
					$setting['instance_id'] = $instanceId;
					$setting['egg_id'] = 0;
					\ze\row::insert('plugin_settings', $setting);
				}
			}
		}
	}



	//This fucntion can be used where you have a WYSIWYG editor, and you want to try and
	//ensure that the URLs used are absolute URLs.
	//For example, this should be used for URLs that will appear in emails.
	public static function addAbsURLsToAdminBoxField(&$field) {
		foreach (['value', 'current_value'] as $value) {
			if (isset($field[$value])) {
				foreach (['"', "'"] as $quote) {
					foreach (['zenario/file.php', 'public/images/'] as $frag) {
						$field[$value] = 
							str_replace(
								$quote. $frag,
								$quote. htmlspecialchars(\ze\link::absolute()). $frag,
								$field[$value]);
				
						//Attempt to work around a bug in the editor where the subdirectory gets added in before the URL
						$field[$value] = 
							str_replace(
								$quote. htmlspecialchars(SUBDIRECTORY). $frag,
								$quote. htmlspecialchars(\ze\link::absolute()). $frag,
								$field[$value]);
					}
				}
			}
		}
	}

	//This fucntion can be used where you have a WYSIWYG editor, and you want to try and
	//ensure that the URLs used are relative URLs.
	//For example, this should be used for URLs that will appear on content items.
	public static function stripAbsURLsFromAdminBoxField(&$field) {
		foreach (['value', 'current_value'] as $value) {
			if (isset($field[$value])) {
				foreach (['"', "'"] as $quote) {
					$field[$value] = 
						str_replace(
							$quote. htmlspecialchars(\ze\link::absolute()),
							$quote,
							$field[$value]);
					
					foreach (['zenario/file.php', 'public/images/'] as $frag) {
						//Attempt to work around a bug in the editor where the subdirectory gets added in before the URL
						$field[$value] = 
							str_replace(
								$quote. htmlspecialchars(SUBDIRECTORY). $frag,
								$quote. $frag,
								$field[$value]);
					}
				}
			}
		}
	}


	public static function stagingModeLink($cID, $cType, $accessCode) {
		return
			\ze\link::protocol(). \ze\link::primaryDomain(). SUBDIRECTORY.
			'staging.php?id='. rawurlencode($cType. '_'. $cID).
			'&code='. rawurlencode($accessCode);
	}


	public static function adminFileLink($fileId) {
		return \ze\link::absolute() . 'zenario/admin/file.php?id=' . $fileId;
	}

	//Scan a Content Item's HTML and other information, and come up with a list of inline files that relate to it
	//Note there is simmilar logic in zenario/admin/db_updates/step_4_migrate_the_data/local.inc.php for migration
	public static function syncInlineFileContentLink($cID, $cType, $cVersion, $publishing = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function syncInlineFileLinks(
		&$files, &$html, &$htmlChanged,
		$usage = 'image',
		$publishingAPublicPage = false,
		$fixWhereLinksGo = true, $fixPublicDir = false
	) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function syncInlineFiles(&$files, $key, $keepOldImagesThatAreNotInUse = true, $isNest = 0, $isSlideshow = 0) {
	
		//Mark all existing images as not in use
		\ze\row::update('inline_images', ['in_use' => 0], $key);
	
		//Add in the ones that we actually found, or mark them as in use if they are already there
		foreach ($files as $file) {
			$key['image_id'] = $file['id'];
			\ze\row::set('inline_images', ['in_use' => 1, 'is_nest' => $isNest, 'is_slideshow' => $isSlideshow], $key);
		}
	
		//Depending on the logic, either delete the unused images from the linking table,
		//or keep them there but flagged as not in use.
		if (!$keepOldImagesThatAreNotInUse) {
			$key['in_use'] = 0;
			unset($key['image_id']);
			\ze\row::delete('inline_images', $key);
		}
	}

	//This function will correct the entries in the inline_images table for any files used in a library plugin
	public static function resyncLibraryPluginFiles($instanceId, $instance = null) {
	
		if (is_null($instance)) {
			$instance = \ze\sql::fetchAssoc('
				SELECT content_id, content_type, content_version, is_nest, is_slideshow
				FROM '. DB_PREFIX. 'plugin_instances
				WHERE id = '. (int) $instanceId
			);
		}
	
		if (!$instance) {
			return;
	
		} elseif ($instance['content_id']) {
			//This function only works for library plugins; \ze\contentAdm::syncInlineFileContentLink() should be used instead if a plugin is version-controlled
			\ze\contentAdm::syncInlineFileContentLink($instance['content_id'], $instance['content_type'], $instance['content_version']);
	
		} else {
			//Get all of the images used in a plugin's settings
			$fileIds = [];
			$sql = "
				SELECT value
				FROM ". DB_PREFIX. "plugin_settings
				WHERE foreign_key_to IN('file', 'multiple_files')
				  AND instance_id = ". (int) $instanceId;
			$result = \ze\sql::select($sql);

			while ($fileIdsInPlugin = \ze\sql::fetchRow($result)) {
				foreach (\ze\ray::explodeAndTrim($fileIdsInPlugin[0], true) as $fileId) {
					$fileIds[$fileId] = $fileId;
				}
			}
			
			if (empty($fileIds)) {
				$files = [];
			} else {
				$files = \ze\row::getAssocs('files', ['id', 'usage', 'privacy'], ['id' => $fileIds]);
			}
	
			\ze\contentAdm::syncInlineFiles(
				$files,
				['foreign_key_to' => 'library_plugin', 'foreign_key_id' => $instanceId],
				$keepOldImagesThatAreNotInUse = false,
				$instance['is_nest'], $instance['is_slideshow']
			);
		}
	}

	//Check to see if an image is not used, and delete it
	//This is only designed to work for files with their usage set to 'image'
	public static function deleteUnusedImage($imageId, $onlyDeleteUnusedArchivedImages = false) {
	
		$key = [
			'image_id' => $imageId,
			'in_use' => 1,
			'archived' => 0,
			'foreign_key_to' => ['content', 'library_plugin', 'menu_node', 'email_template', 'newsletter', 'newsletter_template'],
			'foreign_key_id' => ['!' => 0]
		];
	
		//Check that the file is the correct usage, and is not used anywhere!
		if (($image = \ze\row::get('files', ['archived', 'usage'], $imageId))
		 && (!$onlyDeleteUnusedArchivedImages || $image['archived'])
		 && (!\ze\row::exists('inline_images', $key))) {
		
			//Check to see if the file is archived anywhere.
			$key['archived'] = 1;
			if (\ze\row::exists('inline_images', $key)) {
				//If so, we must keep it in the system, so we'll "delete" it by just flagging it as archived
				if (!$image['archived']) {
					\ze\row::update('files', ['archived' => 1], $imageId);
				}
				\ze\file::deletePublicImage($imageId);
		
			} else {
				//Otherwise delete it straight away
				\ze\file::delete($imageId);
			}
		
			//Remove the image from the linking table anywhere it is unused
			$key['in_use'] = 0;
			unset($key['archived']);
			\ze\row::delete('inline_images', $key);
		}
	}

	//Delete images, even if they're used!
	public static function deleteImage($imageId) {
		
		//If a menu node was using this image, remove it from the menu node
		\ze\row::update('menu_nodes', ['image_id' => 0], ['image_id' => $imageId]);
		\ze\row::update('menu_nodes', ['rollover_image_id' => 0], ['rollover_image_id' => $imageId]);
		
		//Also check the promo menu tables.
		//(N.b. this soft include could be converted to a signal if more modules start using images in linking tables...)
			\ze\row::update('menu_node_feature_image', ['image_id' => 0], ['image_id' => $imageId]);
			\ze\row::update('menu_node_feature_image', ['rollover_image_id' => 0], ['rollover_image_id' => $imageId]);
		
		//If a banner or other plugin was using this image, leave the broken link there
		//\ze\row::delete('plugin_settings', ['foreign_key_to' => 'file', 'foreign_key_id' => $imageId]);
		
		//Remove this image from any content items if it was picked as a feature image
		\ze\row::update('content_item_versions', ['feature_image_id' => 0], ['feature_image_id' => $imageId]);
		
		\ze\row::delete('inline_images', ['image_id' => $imageId]);
		\ze\file::deletePublicImage($imageId);
		\ze\file::delete($imageId);
	}

	//Look for User/Group/Admin files that are not in use, and remove them
	public static function deleteUnusedImagesByUsage($usage) {
	
		if ($usage != 'group' && $usage != 'user' && $usage != 'admin') {
			return;
		}
	
		$sql = "
			SELECT f.id
			FROM ". DB_PREFIX. "files AS f
			LEFT JOIN `". DB_PREFIX. \ze\escape::sql($usage). "s` AS u
			   ON u.image_id = f.id
			WHERE u.image_id IS NULL
			  AND f.location = 'db'
			  AND f.`usage` = '". \ze\escape::asciiInSQL($usage). "'";
	
		$result = \ze\sql::select($sql);
		while ($file = \ze\sql::fetchAssoc($result)) {
			if (!\ze\row::exists('admins', ['image_id' => $file['id']])
			 && !\ze\row::exists('users', ['image_id' => $file['id']])) {
				\ze\file::delete($file['id']);
			}
		}
	}








	public static function deleteUnusedBackgroundImages() {
		$sql = "
			DELETE f.*
			FROM ". DB_PREFIX. "files AS f
			LEFT JOIN ". DB_PREFIX. "layouts AS l
			   ON l.bg_image_id = f.id
			LEFT JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON v.bg_image_id = f.id
			WHERE l.bg_image_id IS NULL
			  AND v.bg_image_id IS NULL
			  AND f.`usage` = 'background_image'";
		\ze\sql::update($sql);
	}



	public static function deleteDraft($cID, $cType, $allowCompleteDeletion = true, $adminId = false) {
		if (!$adminId) {
			$adminId = $_SESSION['admin_userid'] ?? false;
		}
	
		$content = \ze\row::get('content_items', ['status', 'admin_version', 'visitor_version'], ['id' => $cID, 'type' => $cType]);
		$cVersion = $content['admin_version'];
		$content['lock_owner_id'] = 0;
		$content['locked_datetime'] = null;

		if (\ze::in($cType, 'audio', 'document', 'picture', 'video')) {
			$adminVersionFileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $content['admin_version']]);
			$visitorVersionFileId = \ze\row::get('content_item_versions', 'file_id', ['id' => $cID, 'type' => $cType, 'version' => $content['visitor_version']]);

			if ($content['admin_version'] != $content['visitor_version'] && $adminVersionFileId != $visitorVersionFileId) {
				\ze\file::deleteMediaContentItemFileIfUnused($cID, $cType, $adminVersionFileId);
			}
		}
	
		if ($content['status'] == 'first_draft') {
			$content['status'] = 'deleted';
	
		} elseif ($content['status'] == 'published_with_draft') {
			$content['status'] = 'published';
			$content['admin_version'] = $content['visitor_version'];
	
		} elseif ($content['status'] == 'unlisted_with_draft') {
			$content['status'] = 'unlisted';
			$content['admin_version'] = $content['visitor_version'];
	
		} elseif ($content['status'] == 'hidden_with_draft') {
			$content['status'] = 'hidden';
			--$content['admin_version'];
	
		} elseif ($content['status'] == 'trashed_with_draft') {
			$content['status'] = 'trashed';
			--$content['admin_version'];
	
		} else {
			return;
		}
	
		\ze\row::update('content_items', $content, ['id' => $cID, 'type' => $cType]);
	
		//Add a safety catch, that logically should never be reached, but is there just so we don't delete the current version
		if ($cVersion == $content['visitor_version']) {
			return;
	
		} else {
			if ($content['status'] == 'deleted' && $allowCompleteDeletion) {
				\ze\contentAdm::deleteContentItem($cID, $cType);
			} else {
				\ze\contentAdm::deleteVersion($cID, $cType, $cVersion);
				\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);
				\ze\module::sendSignal("eventContentDeleted",["cID" => $cID,"cType" => $cType, "cVersion" => $cVersion]);
			}
		}
	
		return;
	}

	public static function deleteVersion($cID, $cType, $cVersion) {
		\ze\row::delete('content_item_versions', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
		\ze\row::delete('plugin_item_link', ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]);
		\ze\row::delete('content_cache', ['content_id' => $cID, 'content_type' => $cType, 'content_version' => $cVersion]);
	
		\ze\pluginAdm::deleteVC($cID, $cType, $cVersion);
	}

	//Delete all of the archived versions of a content item before a specificied version,
	//or if no version is specified, delete all archived versions
	public static function deleteArchive($cID, $cType, $cVersion = false) {
	
		//If no version is specified, look for the most recent archived version
		if (!$cVersion) {
			if ($content = \ze\row::get('content_items', ['admin_version', 'visitor_version'], ['id' => $cID, 'type' => $cType])) {
				array_map('intval', $content);
				if ($content['visitor_version']) {
					$cVersion = min($content) - 1;
				} else {
					$cVersion = $content['admin_version'] - 1;
				}
			}
		}
	
		if (!$cVersion) {
			return;
		}
	
		$sql = "
			SELECT MIN(version)
			FROM ". DB_PREFIX. "content_item_versions
			WHERE id = ". (int) $cID. "
			  AND type = '". \ze\escape::asciiInSQL($cType). "'";
	
		if (($result = \ze\sql::select($sql))
		 && ($row = \ze\sql::fetchRow($result))
		 && ($v = (int) $row[0])) {
			for (; $v <= $cVersion; ++$v) {
				\ze\contentAdm::deleteVersion($cID, $cType, $v);
			}
		}
	
		\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);
	}

	public static function deleteContentItem($cID, $cType) {
		$content = ['id' => $cID, 'type' => $cType];
	
		$result = \ze\row::query('content_item_versions', ['id', 'type', 'version', 'file_id'], $content);
		while ($version = \ze\sql::fetchAssoc($result)) {
			if (\ze::in($cType, 'audio', 'document', 'picture', 'video')) {
				\ze\file::deleteMediaContentItemFileIfUnused($cID, $cType, $version['file_id']);
			}
			
			\ze\contentAdm::deleteVersion($version['id'], $version['type'], $version['version']);
			\ze\module::sendSignal('eventContentDeleted',['cID' => $version['id'], 'cType' => $version['type'], 'cVersion' => $version['version']]);
		}
	
		\ze\contentAdm::removeItemFromMenu($cID, $cType);
		\ze\contentAdm::removeEquivalence($cID, $cType);
		\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);
		\ze\contentAdm::removeItemFromPluginSettings('content', $cID, $cType);
		\ze\row::delete('plugin_pages_by_mode', ['equiv_id' => $cID, 'content_type' => $cType]);
		
		\ze\row::set('content_items', ['status' => 'deleted', 'admin_version' => 0, 'visitor_version' => 0, 'alias' => ''], $content);
	}

	public static function trashContent($cID, $cType, $adminId = false, $mode = false) {
	
		if (!$adminId) {
			$adminId = $_SESSION['admin_userid'] ?? false;
		}

		$cVersion = \ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
		\ze\row::update('content_items', ['visitor_version' => 0, 'status' => 'trashed', 'alias' => ''], ['id' => $cID, 'type' => $cType]);
		\ze\row::update('content_item_versions', ['concealer_id' => $adminId, 'concealed_datetime' => \ze\date::now(), 'access_code' => null], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
	
		\ze\contentAdm::removeItemFromMenu($cID, $cType);
		\ze\contentAdm::removeEquivalence($cID, $cType);
		\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);
		\ze\contentAdm::removeItemFromPluginSettings('content', $cID, $cType, $mode);
		\ze\row::delete('plugin_pages_by_mode', ['equiv_id' => $cID, 'content_type' => $cType]);
	
		\ze\module::sendSignal("eventContentTrashed",["cID" => $cID,"cType" => $cType]);
	}

	public static function hideContent($cID, $cType, $adminId = false) {
	
		if (!$adminId) {
			$adminId = $_SESSION['admin_userid'] ?? false;
		}
	
		//If this a draft that's not been modified since the previous version, delete the draft
		$content = \ze\row::get('content_items', ['status', 'admin_version'], ['id' => $cID, 'type' => $cType]);
		$oldStatus = $content['status'];
	
		if (($oldStatus == 'published_with_draft' || $oldStatus == 'hidden_with_draft' || $oldStatus == 'trashed_with_draft' || $oldStatus == 'unlisted_with_draft')
		 && !\ze\contentAdm::contentLastModifiedBy($cID, $cType)) {
			\ze\contentAdm::deleteDraft($cID, $cType, $allowCompleteDeletion = false);
			$content = \ze\row::get('content_items', ['status', 'admin_version'], ['id' => $cID, 'type' => $cType]);
		}
	
		//Update the Content Item's status to "hidden"
		\ze\row::update('content_items', ['visitor_version' => 0, 'status' => 'hidden', 'lock_owner_id' => 0], ['id' => $cID, 'type' => $cType]);
		\ze\row::update('content_item_versions', ['concealer_id' => $adminId, 'concealed_datetime' => \ze\date::now(), 'access_code' => null], ['id' => $cID, 'type' => $cType, 'version' => $content['admin_version']]);
	
		\ze\contentAdm::flagImagesInArchivedVersions($cID, $cType);
		\ze\contentAdm::hideOrShowContentItemsMenuNode($cID, $cType, $oldStatus, 'hidden');
	
		\ze\module::sendSignal("eventContentHidden",["cID" => $cID,"cType" => $cType]);
	}

	public static function delistContent($cID, $cType) {
	
		$content = \ze\row::get('content_items', ['status'], ['id' => $cID, 'type' => $cType]);
		
		switch ($content['status']) {
			case 'published':
				$newStatus = 'unlisted';
				break;
			
			case 'published_with_draft':
				$newStatus = 'unlisted_with_draft';
				break;
			
			default:
				return;
		}
		
		\ze\row::update('content_items', ['status' => $newStatus], ['id' => $cID, 'type' => $cType]);
	
		\ze\module::sendSignal('eventContentDelisted', ['cID' => $cID,'cType' => $cType]);
	}

	public static function relistContent($cID, $cType) {
	
		$content = \ze\row::get('content_items', ['status'], ['id' => $cID, 'type' => $cType]);
		
		switch ($content['status']) {
			case 'unlisted':
				$newStatus = 'published';
				break;
			
			case 'unlisted_with_draft':
				$newStatus = 'published_with_draft';
				break;
			
			default:
				return;
		}
		
		\ze\row::update('content_items', ['status' => $newStatus], ['id' => $cID, 'type' => $cType]);
	
		\ze\module::sendSignal('eventContentDelisted', ['cID' => $cID,'cType' => $cType]);
	}

	//If a Content Item is published/hidden, its Menu Node may be shown/hidden as well
	//Check for this case, and clear the cache if needed
	public static function hideOrShowContentItemsMenuNode($cID, $cType, $oldStatus, $newStatus = false) {
		if (\ze\menu::getFromContentItem($cID, $cType)) {
			if (!$newStatus) {
				$newStatus = \ze\content::status($cID, $cType);
			}
		
			if (\ze\content::isPublished($oldStatus) != \ze\content::isPublished($newStatus)) {
				$sql = $ids = $values = false;
				\ze::$dbL->reviewQueryForChanges($sql, $ids, $values, $table = 'menu_nodes');
			}
		}
	}

	//Delete the Menu Node for a Content Item
	public static function removeItemFromMenu($cID, $cType) {
		$languageId = \ze\content::langId($cID, $cType);
		$equivId = $cID;
		\ze\content::langEquivalentItem($equivId, $cType, true);
	
		//Look up any Menu Nodes that point to this Item
		$result = \ze\row::query('menu_nodes', 'id', ['equiv_id' => $equivId, 'content_type' => $cType]);
		while ($row = \ze\sql::fetchAssoc($result)) {
			//Check if any child Menu Nodes exist
			$childrenExist = \ze\row::exists('menu_nodes', ['parent_id' => $row['id']]);
		
			//Check if this Menu Node has translations in another Language than this one
			$otherEquivsExist =
				($equivResult = \ze\row::query('menu_text', 'language_id', ['menu_id' => $row['id']]))
			 && ($equiv = \ze\sql::fetchAssoc($equivResult))
			 && ($equiv['language_id'] != $languageId || \ze\sql::fetchAssoc($equivResult));
		
		
			if ($childrenExist) {
				if ($otherEquivsExist) {
				} else {
					//If this Menu Node has children, only remove the link to this item but keep it in the database as an unlinked Node
					\ze\row::update('menu_nodes', ['equiv_id' => 0, 'content_type' => '', 'target_loc' => 'none'], $row['id']);
				}
			} else {
				if ($otherEquivsExist) {
					//If other languages are still using this Menu Node we cannot delete it completely
					\ze\menuAdm::removeText($row['id'], $languageId);
				} else {
					\ze\menuAdm::delete($row['id']);
				}
			}
		}
	}

	public static function removeItemFromPluginSettings($keyTo, $keyId = 0, $keyChar = '', $mode = false) {
	
		if ($mode == 'remove') {
			$sql = "
				DELETE
				FROM ". DB_PREFIX. "plugin_settings
				WHERE foreign_key_to = '". \ze\escape::sql($keyTo). "'
				  AND foreign_key_id = ". (int) $keyId. "
				  AND foreign_key_char = '". \ze\escape::sql($keyChar). "'";
			\ze\sql::update($sql);
		} elseif ($mode == 'delete_instance') {
			$sql = "
				SELECT
					pi.module_id,
					m.class_name,
					ps.instance_id,
					ps.egg_id,
					pi.content_id,
					pi.content_type,
					pi.content_version,
					pi.slot_name
				FROM ". DB_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				INNER JOIN ". DB_PREFIX. "modules AS m
				   ON m.id = pi.module_id
				WHERE foreign_key_to = '". \ze\escape::sql($keyTo). "'
				  AND foreign_key_id = ". (int) $keyId. "
				  AND foreign_key_char = '". \ze\escape::sql($keyChar). "'";
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchAssoc($result)) {
				if ($row['egg_id']) {
					if (\ze\module::inc($row['class_name'])) {
						call_user_func([$row['class_name'], 'removePlugin'], $row['egg_id'], $row['instance_id']);
					}
				} else {
					//Delete this instance
					\ze\pluginAdm::delete($row['instance_id']);
				}
			}
		} else {
			$sql = "
				DELETE
				FROM ". DB_PREFIX. "plugin_settings
				WHERE dangling_cross_references = 'remove'
				  AND foreign_key_to = '". \ze\escape::sql($keyTo). "'
				  AND foreign_key_id = ". (int) $keyId. "
				  AND foreign_key_char = '". \ze\escape::sql($keyChar). "'";
			\ze\sql::update($sql);
	
			$sql = "
				SELECT
					pi.module_id,
					m.class_name,
					ps.instance_id,
					ps.egg_id,
					pi.content_id,
					pi.content_type,
					pi.content_version,
					pi.slot_name
				FROM ". DB_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				INNER JOIN ". DB_PREFIX. "modules AS m
				   ON m.id = pi.module_id
				WHERE dangling_cross_references = 'delete_instance'
				  AND foreign_key_to = '". \ze\escape::sql($keyTo). "'
				  AND foreign_key_id = ". (int) $keyId. "
				  AND foreign_key_char = '". \ze\escape::sql($keyChar). "'";
		
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchAssoc($result)) {
				if ($row['egg_id']) {
					if (\ze\module::inc($row['class_name'])) {
						//Delete this egg from the nest
						call_user_func([$row['class_name'], 'removePlugin'], $row['egg_id'], $row['instance_id']);
					}
			
				} elseif ($row['content_id']) {
					//Clear the settings for this version controlled module
					\ze\row::delete('plugin_settings', ['instance_id' => $row['instance_id']]);
			
				} else {
					//Delete this instance
					\ze\pluginAdm::delete($row['instance_id']);
				}
			}
		}
	}
	
	const CANT_BECAUSE_SPECIAL_PAGE = 0;

	//Check if a Content Item is in a state where it could be deleted/trashed/hidden. Note that these functions don't check for locks.
	public static function allowDelete($cID, $cType, $status = false, $contentItemLanguageId = false) {
		if (!$status) {
			$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
		}
		
		//Check specific language permissions
		if (\ze\lang::count() > 1 && $contentItemLanguageId && !\ze\priv::onLanguage('_PRIV_PUBLISH_CONTENT_ITEM', $contentItemLanguageId)) {
			return false;
		}

		if (\ze::in($cType, 'audio', 'document', 'picture', 'video')) {
			return true;
		}
	
		if ($status == 'first_draft') {
			if (\ze\content::isSpecialPage($cID, $cType) && !\ze\contentAdm::allowRemoveEquivalence($cID, $cType)) {
				//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
				return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			} else {
				return true;
			}
	
		} else {
			return $status == 'published_with_draft' || $status == 'hidden_with_draft' || $status == 'trashed_with_draft' || $status == 'unlisted_with_draft';
		}
	}

	//\ze\priv::check("_PRIV_PUBLISH_CONTENT_ITEM")
	public static function allowTrash($cID, $cType, $status = false, $lastModified = false, $contentItemLanguageId = false) {
		if (\ze\content::isSpecialPage($cID, $cType) && !\ze\contentAdm::allowRemoveEquivalence($cID, $cType)) {
			//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
			return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			
		//Check specific language permissions
		} elseif (\ze\lang::count() > 1 && $contentItemLanguageId && !\ze\priv::onLanguage('_PRIV_PUBLISH_CONTENT_ITEM', $contentItemLanguageId)) {
			return false;
		
		} else {
			if ($status === false) {
				$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
			}

			if (\ze::in($cType, 'audio', 'document', 'picture', 'video')) {
				return false;
			}
		
			if ($status == 'published'
			 || $status == 'published_with_draft'
			 || $status == 'unlisted'
			 || $status == 'unlisted_with_draft'
			 || $status == 'hidden'
			 || $status == 'hidden_with_draft') {
				return true;
			} else {
				return false;
			}
		}	
	}

	//\ze\priv::check("_PRIV_PUBLISH_CONTENT_ITEM")
	public static function allowHide($cID, $cType, $status = false) {
		
		//Check for special pages without the allow_hide option set.
		//(Though even without the allow_hide option, still allow this page to be hidden if it's a translation.)
		if (($sp = \ze\content::isSpecialPage($cID, $cType))
		 && !(\ze\row::get('special_pages', 'allow_hide', $sp) || \ze\contentAdm::allowRemoveEquivalence($cID, $cType))) {
			//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
			return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
		
		} else {
			if ($status === false) {
				$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
			}
		
			return
				$status == 'first_draft'
			 || $status == 'published_with_draft'
			 || $status == 'unlisted_with_draft'
			 || $status == 'trashed_with_draft'
			 || $status == 'hidden_with_draft'
			 || $status == 'published'
			 || $status == 'unlisted';
		}	
	}

	public static function allowPublishUnlisted($cID, $cType, $status = false) {
		
		//Some special pages are not allowed to be unlisted
		if ($sp = \ze\content::isSpecialPage($cID, $cType)) {
			
			if (\ze\row::exists('special_pages', ['page_type' => $sp, 'listing_policy' => 'must_be_listed'])) {
				//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
				return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			}
		}
		
		if ($status === false) {
			$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
		}
	
		return
			$status == 'first_draft'
		 || $status == 'published_with_draft'
		 || $status == 'unlisted_with_draft'
		 || $status == 'trashed_with_draft'
		 || $status == 'hidden_with_draft'
		 || $status == 'hidden';
	}

	public static function allowPublishListed($cID, $cType, $status = false) {
		
		//Some special pages are not allowed to be listed
		if ($sp = \ze\content::isSpecialPage($cID, $cType)) {
			
			if (\ze\row::exists('special_pages', ['page_type' => $sp, 'listing_policy' => 'must_be_unlisted'])) {
				//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
				return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			}
		}
		
		if ($status === false) {
			$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
		}
	
		return
			$status == 'first_draft'
		 || $status == 'published_with_draft'
		 || $status == 'unlisted_with_draft'
		 || $status == 'trashed_with_draft'
		 || $status == 'hidden_with_draft'
		 || $status == 'hidden';
	}

	public static function allowDelist($cID, $cType, $status = false) {
		
		//Some special pages are not allowed to be unlisted
		if ($sp = \ze\content::isSpecialPage($cID, $cType)) {
			
			if (\ze\row::exists('special_pages', ['page_type' => $sp, 'listing_policy' => 'must_be_listed'])) {
				//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
				return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			}
		}
		
		if ($status === false) {
			$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
		}
	
		return
			$status == 'published_with_draft'
		 || $status == 'published';
	}

	public static function allowRelist($cID, $cType, $status = false) {
		
		//Some special pages are not allowed to be listed
		if ($sp = \ze\content::isSpecialPage($cID, $cType)) {
			
			if (\ze\row::exists('special_pages', ['page_type' => $sp, 'listing_policy' => 'must_be_unlisted'])) {
				//Small hack here, return 0 not false so any caller that cares can tell why, but it still evaluates to false for anyone who doesn't.
				return \ze\contentAdm::CANT_BECAUSE_SPECIAL_PAGE;
			}
		}
		
		if ($status === false) {
			$status = \ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
		}
	
		return
			$status == 'unlisted_with_draft'
		 || $status == 'unlisted';
	}
	
	
	public static function checkForAccessCodes(&$box, &$field, $tagIds, $contentItemsCount, $phraseA, $phraseB, $phraseC) {
		$contentItemsWithAccessCodes = \ze\row::getValues('content_item_versions', ['id', 'type', 'access_code'], ['access_code' => ['!' => null], 'tag_id' => $tagIds]);
		if (empty($contentItemsWithAccessCodes)) {
			$field['hidden'] = true;
		
		} elseif ($contentItemsCount == 1) {
			unset($box['max_height']);
			$mrg = $contentItemsWithAccessCodes[0];
			$field['notices_above']['access_codes_warning']['message'] =
				\ze\admin::phrase($phraseA, $mrg);
			
		} else {
			unset($box['max_height']);
			$codes = count($contentItemsWithAccessCodes);
			$mrg = $contentItemsWithAccessCodes[0];
			$field['notices_above']['access_codes_warning']['message'] =
				\ze\admin::nPhrase($phraseB, $phraseC, $codes, $mrg);
		}
	}




	public static function rerenderWorkingCopyImages($recreateCustomThumbnailOnes = true, $recreateCustomThumbnailTwos = true, $removeOldCopies = false, $jpegOnly = false) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function getImageTagColours($byId = true, $byName = true) {
	
		$tagColours = [];
		$lastColour = false;
		$sql = "
			SELECT id, name, color
			FROM ". DB_PREFIX. "image_tags
			WHERE color != 'blue'
			ORDER BY color";
	
		$result = \ze\sql::select($sql);
		while ($tag = \ze\sql::fetchAssoc($result)) {
			if ($byId) $tagColours[$tag['id']] = $tag['color'];
			if ($byName) $tagColours[$tag['name']] = $tag['color'];
		}
	
		return $tagColours;
	}





	public static function allowDeleteLanguage($langId) {
		return $langId != \ze::$defaultLang;
	}

	public static function deleteLanguage($langId) {
		//Remove all of the Content Items in a Language
		$result = \ze\row::query('content_items', ['id', 'type'], ['language_id' => $langId]);
		while ($content = \ze\sql::fetchAssoc($result)) {
			\ze\contentAdm::deleteContentItem($content['id'], $content['type']);
		}
	
		//Remove any remaining Menu translations in a Language
		\ze\row::delete('menu_text', ['language_id' => $langId]);
	
		//Remove any Menu Nodes that now do not have translations
		$sql = "
			SELECT mn.id
			FROM ". DB_PREFIX. "menu_nodes AS mn
			LEFT JOIN ". DB_PREFIX. "menu_text AS mt
			   ON mt.menu_id = mn.id
			WHERE mt.menu_id IS NULL";
		$result = \ze\sql::select($sql);
		while ($menu = \ze\sql::fetchAssoc($result)) {
			\ze\menuAdm::delete($menu['id']);
		}
	
		//Remove any Visitor Phrases, except for Visitor Pharses from the Common Features Module
		\ze\row::delete('visitor_phrases', ['language_id' => $langId, 'module_class_name' => ['!1' => 'zenario_common_features', '!2' => '']]);
	
		\ze\row::delete('languages', $langId);
	}

	public static function contentLastModifiedBy($cID, $cType) {
		$sql = "
			SELECT last_author_id
			FROM ". DB_PREFIX. "content_item_versions
			WHERE id = ". (int) $cID. "
			  AND type = '". \ze\escape::asciiInSQL($cType). "'
			ORDER BY version DESC
			LIMIT 1";
	
		if (($result = \ze\sql::select($sql)) && ($row = \ze\sql::fetchAssoc($result))) {
			return $row['last_author_id'];
		} else {
			return false;
		}
	}


	public static function importPhrasesForModule($moduleClassName, $langId = false) {

		//Check if this Module uses the old Visitor phrases system, with phrases in CSV files
		if ($path = \ze::moduleDir($moduleClassName, 'phrases/', true)) {
			$importFiles = \ze\phraseAdm::scanModulePhraseDir($moduleClassName, 'language id');
			if (!empty($importFiles)) {
	
				//Check which languages this site uses
				if ($langId === false) {
					$installedLanguages = \ze\row::getValues('languages', 'id', []);
				} else {
					$installedLanguages = [$langId];
				}
	
				if (!empty($installedLanguages)) {
		
					//For every language in the site, try to find a matching csv file
					foreach ($installedLanguages as $installedLang) {
						$bestMatch = false;
						foreach ($importFiles as $languageId => $file) {
							if ($languageId == $installedLang) {
								//If a language on the site matches a language availible to the module, import that
								$bestMatch = $file;
								break;
				
							} elseif (substr($languageId, 0, 2) == substr($installedLang, 0, 2)) {
								//Otherwise if there is a close match, use that
								$bestMatch = $file;
							}
						}
			
						if ($bestMatch) {
							$languageIdFound = false;
							\ze\phraseAdm::importVisitorLanguagePack(CMS_ROOT. $path. $bestMatch, $languageIdFound, $adding = false, $scanning = false, $forceLanguageIdOverride = $installedLang);
						}
					}
				}
			}
		}
	}

	public static function importPhrasesForModules($langId = false) {
		foreach (\ze\module::modules($onlyGetRunningPlugins = true, $ignoreUninstalledPlugins = true, $dbUpdateSafemode = true) as $module) {
			\ze\contentAdm::importPhrasesForModule($module['class_name'], $langId);
		}
	}

	public static function addNeededSpecialPages() {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}



	public static function getItemIconClass($cID, $cType, $checkForSpecialPage = true, $status = false) {
	
		if ($status === false) {
			$status = \ze\content::status($cID, $cType);
		}
	
		$homepage = $specialPage = false;
		if ($checkForSpecialPage) {
			if ($pageType = \ze\content::isSpecialPage($cID, $cType)) {
				if ($pageType == 'zenario_home') {
					$homepage = true;
				} else {
					$specialPage = true;
				}
			}
		}
	
		switch ($status) {
			case 'first_draft':
				return $homepage? 'home_content_draft' : ($specialPage? 'special_content_draft' : 'content_draft');
		
			case 'published':
				return $homepage? 'home_content_published' : ($specialPage? 'special_content_published' : 'content_published');
		
			case 'published_with_draft':
				return $homepage? 'home_content_draft_published' : ($specialPage? 'special_content_draft_published' : 'content_draft_published');
		
			case 'hidden':
				return $specialPage? 'special_content_hidden' : 'content_hidden';
		
			case 'hidden_with_draft':
				return $specialPage? 'special_content_draft_hidden' : 'content_draft_hidden';
		
			case 'trashed':
				return $specialPage? 'special_content_trashed' : 'content_trashed';
		
			case 'trashed_with_draft':
				return $specialPage? 'special_content_draft_trashed' : 'content_draft_trashed';
			
			case 'unlisted':
				return $specialPage? 'special_content_unlisted' : 'content_unlisted';
		
			case 'unlisted_with_draft':
				return $specialPage? 'special_content_draft_unlisted' : 'content_draft_unlisted';
			
			case 'archived':
				return 'content_archived';
		}
	
		return '';
	}



	public static function getContentItemVersionToolbarIcon(&$content, $cVersion, $prefix = '') {
		return $prefix. \ze\contentAdm::getContentItemVersionStatus($content, $cVersion);
	}

	public static function getContentItemVersionStatus($content, $cVersion) {
	
		if ($cVersion == $content['visitor_version']) {
			switch ($content['status']) {
				case 'published_with_draft':
				case 'published':
					return 'published';
				
				case 'unlisted_with_draft':
				case 'unlisted':
					return 'published';
			}
	
		} elseif ($cVersion == $content['admin_version']) {
			switch ($content['status']) {
				case 'hidden':
					return 'hidden';
				case 'trashed':
					return 'trashed';
				default:
					return 'draft';
			}
	
		} elseif ($cVersion == $content['admin_version'] - 1) {
			switch ($content['status']) {
				case 'hidden_with_draft':
					return 'hidden';
				case 'trashed_with_draft':
					return 'trashed';
			}
		}

		return 'archived';
	}

	public static function getContentItemVersionStatusLabel($content, $cVersion) {
	
		if ($cVersion == $content['visitor_version']) {
			switch ($content['status']) {
				case 'published_with_draft':
				case 'published':
					return 'Published';
				
				case 'unlisted_with_draft':
				case 'unlisted':
					return 'Published (unlisted)';
			}
	
		} elseif ($cVersion == $content['admin_version']) {
			switch ($content['status']) {
				case 'hidden':
					return 'Hidden';
				case 'trashed':
					return 'Trashed';
				default:
					return 'Draft';
			}
	
		} elseif ($cVersion == $content['admin_version'] - 1) {
			switch ($content['status']) {
				case 'hidden_with_draft':
					return 'Hidden';
				case 'trashed_with_draft':
					return 'Trashed';
			}
		}

		return 'Archived';
	}




	//Validation function for checking aliases
	public static function validateAlias($alias, $cID = false, $cType = false, $equivId = false, $isSpareAlias = false) {
		$error = [];
		$cIDToCheck = $cTypeToCheck = false;
	
		if ($alias!="") {
			if (preg_match('/\s/', $alias)) {
				$error[] = \ze\admin::phrase("An alias or spare alias cannot contain spaces. Use a - (hyphen) to separate words.");
			}
		
			if ($alias == 'admin' || is_dir(CMS_ROOT. $alias)) {
				$error[] = \ze\admin::phrase("Your alias/spare alias should not contain a directory name (e.g. 'admin', 'cache', 'private', 'public', or 'zenario'). The alias itself is enough for it to be unique.");
		
			} elseif (is_numeric($alias)) {
				$error[] = \ze\admin::phrase("An alias or spare alias must start with a letter, not a digit or special character.");
		
			} elseif (preg_match('/[^a-zA-Z 0-9_-]/', $alias)) {
				$error[] = \ze\admin::phrase("An alias/spare alias can only contain a-z, A-Z, 0-9, - (hyphen) and _ (underscore). Do not enter http/s, a domain name, menu path or language code.");
		
			} elseif (\ze\row::exists('visitor_phrases', ['language_id' => $alias])) {
				$error[] = \ze\admin::phrase("Don't incude a language code (e.g. 'en', 'en-gb', 'en-us', 'es', 'fr').");
		
			} elseif (\ze\content::getCIDAndCTypeFromTagId($cIDToCheck, $cTypeToCheck, $alias)) {
				$allow = false;
				
				if ($isSpareAlias) {
					//If this is meant to be a spare alias, and the parsed string looks like it could be a real content ID and type,
					//check if there is an actual content type like that enabled.
					//Then check if there already is a content item with that ID and type, which has been trashed and deleted.
					
					//Otherwise, block. Do not allow situations where, for example, an admin attempts to create a spare alias
					//html_11, when the highest number created is html_10.
					if (\ze\row::exists('content_types', ['content_type_id' => $cTypeToCheck])) {
						$contentItemStatus = \ze\row::get('content_items', 'status', ['id' => $cIDToCheck, 'type' => $cTypeToCheck]);
					
						if ($contentItemStatus && $contentItemStatus == 'deleted') {
							$allow = true;
						}
					}
				}
				
				if (!$allow) {
					if ($isSpareAlias) {
						$error[] = \ze\admin::phrase("Your spare alias looks like a content item ID (e.g. 'html_1', 'news_2'), which is only allowed when the content item of that ID has been trashed and deleted. Unless you are trying to redirect from such a content item, use a - (hyphen) as a word separator.");
					} else {
						$error[] = \ze\admin::phrase("Your alias looks like a content item ID (e.g. 'html_1', 'news_2'), which isn't allowed. Use a - (hyphen) as a word separator.");
					}
				}
			}
		
		
			if ($cID && $cType && !$equivId) {
				$equivId = \ze\content::equivId($cID, $cType);
			}
		
			//Check other content item aliases...
			$sql = "
				SELECT id, type
				FROM ". DB_PREFIX. "content_items
				WHERE alias = '". \ze\escape::sql($alias). "'";
		
			if ($equivId && $cType) {
				$sql .= "
				  AND (equiv_id != ". (int) $equivId. " OR type != '". \ze\escape::asciiInSQL($cType). "')";
			}
		
			$sql .= "
				LIMIT 1";
		
			$aliasIsUniqueError = false;
			if (($result = \ze\sql::select($sql))
			 && ($row = \ze\sql::fetchAssoc($result))) {
				$tag = \ze\content::formatTag($row['id'], $row['type']);
				
				if ($isSpareAlias) {
					$message = 'You can\'t have a spare alias with the same name as a regular alias. "[[alias]]" is the alias for "[[tag]]".';
				} else {
					$message = 'The alias needs to be unique. "[[alias]]" is the alias for "[[tag]]".';
				}
				
				$error[] = \ze\admin::phrase($message, ['alias' => $alias, 'tag' => $tag]);
				$aliasIsUniqueError = true;
			}

			//... as well as spare aliases.
			$sql = "
				SELECT content_id, content_type
				FROM ". DB_PREFIX. "spare_aliases
				WHERE alias = '". \ze\escape::sql($alias). "'
				LIMIT 1";
			
			if (($result = \ze\sql::select($sql))
			 && ($row = \ze\sql::fetchAssoc($result))) {
				if (!$aliasIsUniqueError) {
					$tag = \ze\content::formatTag($row['content_id'], $row['content_type']);
					$error[] = \ze\admin::phrase('Please enter alias text that is unique. "[[alias]]" is already a spare alias, and it redirects to "[[tag]]".', ['alias' => $alias, 'tag' => $tag]);
				}
			}
		}
	
		if (empty($error)) {
			return false;
		} else {
			return $error;
		}
	}

	public static function tidyAlias($alias) {
	
		$alias = str_replace(" ","-",$alias);
		$alias = preg_replace("/[^a-zA-Z0-9-_]/","",$alias);
	
		return $alias;
	}






	public static function privacyDesc($chain) {
		
		if (is_string($chain)) {
			$privacy = $chain;
			$chain = false;
		} else {
			$privacy = $chain['privacy'];
		}
		
		if ($chain) {
			switch ($privacy) {
				case 'group_members':
					$groupNames = \ze\sql::fetchValues("
						SELECT IFNULL(label, default_label)
						FROM ". DB_PREFIX. "group_link AS gcl
						INNER JOIN ". DB_PREFIX. "custom_dataset_fields cdf
						   ON gcl.link_to_id = cdf.id
						WHERE gcl.link_to = 'group'
						  AND gcl.link_from = 'chain'
						  AND gcl.link_from_id = ". (int) $chain['equiv_id']. "
						  AND gcl.link_from_char = '". \ze\escape::sql($chain['type']). "'");
			
					if (count($groupNames) > 1) {
						$groupNames = [implode(', ', $groupNames)];
						return \ze\admin::phrase('Private, only show to extranet users in the groups: [[0]]', $groupNames);
					} elseif (count($groupNames) == 1) {
						return \ze\admin::phrase('Private, only show to extranet users in the group: [[0]]', $groupNames);
					} else {
						return \ze\admin::phrase('Private, only show to extranet users in the group: [[0]]', [0 => '(error: selected group not found)']);
					}
					break;
			
				case 'in_smart_group':
				case 'logged_in_not_in_smart_group':
					if (($smartGroupId = \ze\row::get('translation_chains', 'smart_group_id', ['equiv_id' => $chain['equiv_id'], 'type' => $chain['type']]))
					 && ($smartGroup = \ze\row::get('smart_groups', ['name'], $smartGroupId))) {
				
						if ($privacy == 'in_smart_group') {
							return \ze\admin::phrase('Private, only show to extranet users in the smart group: [[name]]', $smartGroup);
						} else {
							return \ze\admin::phrase('Private, only show to extranet users NOT in the smart group: [[name]]', $smartGroup);
						}
					}
					break;
			
				case 'with_role':
					if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager')) {
						$roleNames = \ze\sql::fetchValues("
							SELECT ulr.name
							FROM ". DB_PREFIX. "group_link AS gcl
							INNER JOIN ". DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_location_roles AS ulr
							   ON gcl.link_to_id = ulr.id
							WHERE gcl.link_to = 'role'
							  AND gcl.link_from = 'chain'
							  AND gcl.link_from_id = ". (int) $chain['equiv_id']. "
							  AND gcl.link_from_char = '". \ze\escape::sql($chain['type']). "'");
						
						$mrg = [];
						switch ($chain['at_location']) {
							case 'detect':
								$mrg['at'] = \ze\admin::phrase('at the location in the URL, or at ANY location when there is no location in the URL');
								break;
						
							case 'in_url':
								$mrg['at'] = \ze\admin::phrase('at the location in the URL');
								break;
						
							default:
								$mrg['at'] = \ze\admin::phrase('at ANY location');
						}
						
						if (count($roleNames) > 1) {
							$mrg['roles'] = implode(', ', $roleNames);
							return \ze\admin::phrase('Private, only show to extranet users with the following roles [[at]]: [[roles]]', $mrg);
						} elseif (count($roleNames) == 1) {
							$mrg['role'] = $roleNames[0];
							return \ze\admin::phrase('Private, only show to extranet users with the following role [[at]]: [[role]]', $mrg);
						} else {
							return \ze\admin::phrase('Private, only show to extranet users with the following role [[at]]: (error: selected role not found)', $mrg);
						}
					}
					break;
			}
		}
	
		switch ($privacy) {
			case 'public':
				return \ze\admin::phrase('Public, visible to everyone');
			case 'logged_in':
				return \ze\admin::phrase('Private, only show to extranet users');
			case 'group_members':
				return \ze\admin::phrase('Private, only show to extranet users in group(s)...');
			case 'with_role':
				return \ze\admin::phrase('Private, only show to extranet users with role...');
			case 'in_smart_group':
				return \ze\admin::phrase('Private, only show to extranet users in smart group...');
			case 'logged_in_not_in_smart_group':
				return \ze\admin::phrase('Private, only show to extranet users NOT in smart group...');
			case 'logged_out':
				return \ze\admin::phrase('Public, only show to visitors who are NOT logged in');
			case 'call_static_method':
				return \ze\admin::phrase("Call a module's static method to decide");
			case 'send_signal':
				return \ze\admin::phrase('Send a signal to decide');
		}
	}

	public static function getListOfSmartGroupsWithCounts($intendedUsage = 'smart_permissions_group') {
		$smartGroups = \ze\row::getValues('smart_groups', 'name', ['intended_usage' => $intendedUsage], 'name');
		foreach ($smartGroups as $smartGroupId => &$name) {
			$name .= 
				' | '.
				\ze\contentAdm::getSmartGroupDescription($smartGroupId).
				' | '.
				\ze\admin::nPhrase('1 user', '[[count]] users', (int) \ze\smartGroup::countMembers($smartGroupId), [], 'empty');
		}
		return $smartGroups;
	}

	//Smart group description function
	public static function getSmartGroupDescription($smartGroupId) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}







	//
	// Translation functionality
	//
	
	
	//The tag IDs in translation chain pickers have a slightly different format.
	//This is needed for a technical reason, as meta-info about the selected items are stored by ID.
	//This function can be used to convert between them as needed.
	public static function convertBetweenTagIdAndTranslationChainId($id, $useTranslation) {
		
		//Empty values don't need conversion
		if ($id) {
			if ($useTranslation) {
				//Ensure any values are in translation chain ID format
				$equivId = $cType = false;
				if (\ze\content::getEquivIdAndCTypeFromTagId($equivId, $cType, $id)) {
					$id = $cType. '_'. $equivId. '_t';
				}
			} else {
				//Ensure any values are in normal tag ID format
				$cID = $cType = false;
				if (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					$id = $cType. '_'. $cID;
				}
			}
		}
		
		return $id;
	}


	//Add two Content Items into an equivalence
	public static function recordEquivalence($cID1, $cID2, $cType, $onlyValidate = false) {
		//Get the two equivalence keys of the Content Items
		$equiv1 = \ze\content::equivId($cID1, $cType);
		$equiv2 = false;
	
		if ($cID2) {
			$equiv2 = \ze\content::equivId($cID2, $cType);
		}
	
		//Try to get the cID for the default Language
		$default = \ze\row::get('content_items', ['id', 'alias'], ['equiv_id' => $equiv1, 'type' => $cType, 'language_id' => \ze::$defaultLang]);
	
		if (!$default && $equiv2) {
			$default = \ze\row::get('content_items', ['id', 'alias'], ['equiv_id' => $equiv2, 'type' => $cType, 'language_id' => \ze::$defaultLang]);
		}
	
		//Case where a content item is first created in non-default language so the equivId is targeted at the non-defualt language
		//and therefore the alias needs to be taken from the existing content item
		if (empty($default['alias']) && $cID2) {
			$default = \ze\row::get('content_items', ['id', 'alias'], ['id' => $equiv1, 'type' => $cType]);
		}
	
		//If we are merging two different equivs, check the merge will not give us any overlaps
		if ($equiv1 && $equiv2 && $equiv1 != $equiv2) {
			$result = \ze\row::query('content_items', 'language_id', ['equiv_id' => $equiv1, 'type' => $cType]);
			while ($row = \ze\sql::fetchAssoc($result)) {
				if (\ze\row::exists('content_items', ['equiv_id' => $equiv2, 'type' => $cType, 'language_id' => $row['language_id']])) {
					return false;
				}
			}
		}
	
		//Update any existing equivalences to match the new equiv id, which should be the cID for the default Language
		if (!$onlyValidate) {
			if (!empty($default['id'])) {
				$newEquivId = $default['id'];
			} else {
				$newEquivId = $equiv1;
			}
		
			foreach ([$equiv1, $equiv2] as $currentEquivId) {
				if ($currentEquivId && $newEquivId && $currentEquivId != $newEquivId) {
					//Automaticaly update the aliases of any newly added Content Items if they don't currently have aliases
					if (!empty($default['alias'])) {
						\ze\row::update('content_items', ['alias' => $default['alias']], ['equiv_id' => $currentEquivId, 'type' => $cType, 'alias' => '']);
					}
				
					//Change the old equivId to the new equivId
					\ze\row::update('content_items', ['equiv_id' => $newEquivId], ['equiv_id' => $currentEquivId, 'type' => $cType]);
					\ze\row::update('menu_nodes', ['equiv_id' => $newEquivId], ['equiv_id' => $currentEquivId, 'content_type' => $cType]);
					\ze\row::update('special_pages', ['equiv_id' => $newEquivId], ['equiv_id' => $currentEquivId, 'content_type' => $cType]);
					\ze\row::update('plugin_pages_by_mode', ['equiv_id' => $newEquivId], ['equiv_id' => $currentEquivId, 'content_type' => $cType]);
				
					if (!\ze\row::exists('translation_chains', ['equiv_id' => $newEquivId, 'type' => $cType])) {
						\ze\row::update('translation_chains', ['equiv_id' => $newEquivId], ['equiv_id' => $currentEquivId, 'type' => $cType]);
					}
				
					//Update the equivs recorded in links to content item stored in any plugins in the plugin library
					$sql = "
						UPDATE ". DB_PREFIX. "plugin_settings AS ps
						INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
						   ON pi.id = ps.instance_id
						  AND pi.content_id = 0
						SET ps.foreign_key_id = ". (int) $newEquivId. ",
							ps.value = '". \ze\escape::sql($cType. '_'. $newEquivId). "'
						WHERE ps.foreign_key_to = 'content'
						  AND ps.foreign_key_id = ". (int) $currentEquivId. "
						  AND ps.foreign_key_char = '". \ze\escape::sql($cType). "'";
					\ze\sql::update($sql);
				
					\ze\contentAdm::tidyTranslationsTable($currentEquivId, $cType);
				}
			}

			//If the "Alias rules on a multi-language site" site setting is set to
			//"Keep aliases of content items in a translation chain the same",
			//consolidate them.
			$translationsDifferentAliasesSetting = \ze::setting('translations_different_aliases');
			$proFeaturesModuleIsRunning = \ze\module::inc('zenario_pro_features');

			if (!$translationsDifferentAliasesSetting) {
				$defaultLangContentItemAlias = \ze\row::get('content_items', 'alias', ['equiv_id' => $newEquivId, 'type' => $cType, 'language_id' => \ze::$defaultLang]);
				
				if ($defaultLangContentItemAlias) {
					$contentItemsToUpdate = [];
					$currentAliasesQuery = \ze\row::query('content_items', ['id', 'alias'], ['equiv_id' => $newEquivId, 'type' => $cType]);
					while ($row = \ze\sql::fetchAssoc($currentAliasesQuery)) {
						if ($row['alias'] != $defaultLangContentItemAlias) {
							$contentItemsToUpdate[] = ['id' => $row['id'], 'old_alias' => $row['alias']];
						}
					}

					if (count($contentItemsToUpdate) > 0) {
						$idsArray = [];
						foreach ($contentItemsToUpdate as $contentItem) {
							$idsArray[] = $contentItem['id'];

							if ($proFeaturesModuleIsRunning && !\ze\row::exists('spare_aliases', ['content_id' => $contentItem['id'], 'content_type' => $cType, 'alias' => $contentItem['old_alias']])) {
								\ze\row::insert(
									'spare_aliases',
									[
										'content_id' => $contentItem['id'],
										'content_type' => $cType,
										'target_loc' => 'int',
										'alias' => $contentItem['old_alias'],
										'ext_url' => '',
										'created_datetime' => \ze\date::now()
									]
								);
							}
						}
						
						$contentItemsToUpdateCsl = implode(',', $idsArray);
						$sql = '
							UPDATE ' . DB_PREFIX . 'content_items
							SET alias = "' . \ze\escape::sql($defaultLangContentItemAlias) . '"
							WHERE id IN (' . \ze\escape::in($contentItemsToUpdateCsl, 'numeric') . ')
							AND type = "' . \ze\escape::asciiInSQL($cType) . '"';
						\ze\sql::update($sql);
					}
				}
			}
		
			return $newEquivId;
		}
	
		return true;
	}

	public static function tidyTranslationsTable($equivId, $cType) {
		if (!\ze\row::exists('content_items', ['equiv_id' => $equivId, 'type' => $cType])) {
			\ze\row::delete('category_item_link', ['equiv_id' => $equivId, 'content_type' => $cType]);
			\ze\row::delete('group_link', ['link_from' => 'chain', 'link_from_id' => $equivId, 'link_from_char' => $cType]);
			\ze\row::delete('translation_chains', ['equiv_id' => $equivId, 'type' => $cType]);
			\ze\row::delete('translation_chain_privacy', ['equiv_id' => $equivId, 'content_type' => $cType]);
		}
	}

	public static function copyTranslationsTable($oldEquivId, $newEquivId, $cType) {
		$chain = \ze\row::get('translation_chains', true, ['equiv_id' => $oldEquivId, 'type' => $cType]);
		$chain['equiv_id'] = $newEquivId;
	
		//Insert a new row into the translation_chains table for the new chain
		\ze\row::set('translation_chains', $chain, ['equiv_id' => $newEquivId, 'type' => $cType]);
	
		$sql = "
			INSERT IGNORE INTO ". DB_PREFIX. "category_item_link (
				equiv_id, content_type, category_id
			) SELECT ". (int) $newEquivId. ", content_type, category_id
			FROM ". DB_PREFIX. "category_item_link
			WHERE equiv_id = ". (int) $oldEquivId. "
			  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
			ORDER BY category_id";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT IGNORE INTO ". DB_PREFIX. "group_link
				(`link_from`, `link_from_id`, `link_from_char`, `link_to`, `link_to_id`)
			SELECT `link_from`, ". (int) $newEquivId. ", `link_from_char`, `link_to`, `link_to_id`
			FROM ". DB_PREFIX. "group_link
			WHERE link_from = 'chain'
			  AND link_from_id = ". (int) $oldEquivId. "
			  AND link_from_char = '". \ze\escape::sql($cType). "'
			ORDER BY `link_to`, `link_to_id`";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT IGNORE INTO ". DB_PREFIX. "translation_chain_privacy (
				equiv_id, content_type, module_class_name, method_name, param_1, param_2
			) SELECT ". (int) $newEquivId. ", content_type, module_class_name, method_name, param_1, param_2
			FROM ". DB_PREFIX. "translation_chain_privacy
			WHERE equiv_id = ". (int) $oldEquivId. "
			  AND content_type = '". \ze\escape::asciiInSQL($cType). "'";
		\ze\sql::update($sql);
	}

	public static function resyncEquivalence($cID, $cType) {
		\ze\contentAdm::recordEquivalence($cID, false, $cType);
	}

	public static function allowRemoveEquivalence($cID, $cType) {
		//Check if this is a special page
		if (($specialPage = \ze\content::isSpecialPage($cID, $cType))
		 && ($specialPage = \ze\row::get('special_pages', ['equiv_id', 'logic'], ['page_type' => $specialPage]))) {
			//Never allow the main special page to be unlinked.
			return $cID != $specialPage['equiv_id'];
		} else {
			return true;
		}
	}


	//Remove a content equivalence link from the database
	public static function removeEquivalence($cID, $cType) {
	
	
		$content = \ze\row::get('content_items', ['alias', 'equiv_id', 'tag_id'], ['id' => $cID, 'type' => $cType]);
	
		//Two cases here:
		if ($content['equiv_id'] != $cID) {
			//1. This Content Item is not in the default language.
			//   In this case, we only need change its equiv_id
			$vals = [];
			$vals['equiv_id'] = $cID;
		
			//Check if another Content Item is using this alias; if so, we need to remove the alias.
			if ($content['alias'] && \ze\row::exists('content_items', ['alias' => $content['alias'], 'tag_id' => ['!' => $content['tag_id']]])) {
				$vals['alias'] = '';
			}
		
			\ze\row::update('content_items', $vals, ['id' => $cID, 'type' => $cType]);
			\ze\contentAdm::copyTranslationsTable($content['equiv_id'], $vals['equiv_id'], $cType);
	
		} else {
			//2. This Content Item is the default language.
			//   In this case, we only need change its equiv_id for everything *else*
			$newEquivId = false;
			$result = \ze\row::query('content_items', ['id', 'alias'], ['equiv_id' => $content['equiv_id'], 'type' => $cType]);
			while ($row = \ze\sql::fetchAssoc($result)) {
				if ($row['id'] != $cID) {
					if (!$newEquivId) {
						$newEquivId = $row['id'];
					}
					$vals = ['equiv_id' => $newEquivId];
				
					//Check if another Content Item is using the same alias as the default page; if so, we need to remove the alias.
					if ($content['alias'] && $content['alias'] == $row['alias']) {
						$vals['alias'] = '';
					}
				
					\ze\row::update('content_items', $vals, ['id' => $row['id'], 'type' => $cType]);
				}
			}
			if ($newEquivId) {
				\ze\contentAdm::copyTranslationsTable($cID, $newEquivId, $cType);
			}
		}
	}
	
	

	public static function saveLanguage($submission, $lang) {
	
		if (($valid = validateLanguage($submission)) && ($valid['valid'])) {
			//Build up an insert/update statement using the values we have for the fields
			$sql = "";
			foreach(\ze\deprecated::getFields(DB_PREFIX, 'languages') as $field => $details) {
				if (isset($submission[$field])) {
					\ze\deprecated::addFieldToSQL($sql, DB_PREFIX. 'languages', $field, $submission, true, $details);
				}
			}
	
			$sql .= "
				WHERE id = '". \ze\escape::asciiInSQL($lang). "'";
		
			$result = \ze\sql::update($sql);
		}
	}



	//Check a VLP to see if the three bare-minimum required phrases are there
	//If they are, then the VLP can be added as a language
	public static function checkIfLanguageCanBeAdded($languageId) {
		return 3 == \ze\row::count('visitor_phrases', [
			'language_id' => $languageId,
			'code' => ['__LANGUAGE_ENGLISH_NAME__', '__LANGUAGE_LOCAL_NAME__', '__LANGUAGE_FLAG_FILENAME__'],
			'module_class_name' => 'zenario_common_features']);
	}

	public static function aliasURLIsValid($url) {
		return preg_match("/^[0-9A-Za-z\.\-]*\.[0-9A-Za-z\.\-\/]*$/",$url);
	}

	public static function getLanguageSelectListOptions(&$field) {
		$ord = 0;
	
		if (!isset($field['values']) || !is_array($field['values'])) {
			$field['values'] = [];
		}
	
		foreach (\ze\lang::getLanguages() as $lang) {
			$field['values'][$lang['id']] = ['ord' => ++$ord, 'label' => $lang['english_name']. ' ('. $lang['id']. ')'];
		}
	}
	
	
	
	

	//Given a content item, and a module id, work out which slots have wireframes from that plugin on them
	public static function mainSlot($cID, $cType, $cVersion, $moduleId = false, $limitToOne = true, $forceLayoutId = false) {
	
		if (!$moduleId) {
			$moduleId = [\ze\module::id('zenario_wysiwyg_editor')];

		} elseif (!is_array($moduleId)) {
			$moduleId = [$moduleId];
		}
		$sql = "
			SELECT tsl.slot_name
			FROM ". DB_PREFIX. "content_item_versions AS v
			INNER JOIN ". DB_PREFIX. "layouts AS t
			   ON t.layout_id = ". ((int) $forceLayoutId? (int) $forceLayoutId : "v.layout_id"). "
			INNER JOIN ". DB_PREFIX. "layout_slot_link AS tsl
			   ON tsl.layout_id = t.layout_id
			LEFT JOIN ". DB_PREFIX. "plugin_item_link AS piil
			   ON piil.content_id = v.id
			  AND piil.content_type = v.type
			  AND piil.content_version = v.version
			  AND piil.slot_name = tsl.slot_name
			LEFT JOIN ". DB_PREFIX. "plugin_layout_link AS pitl
			   ON pitl.layout_id = t.layout_id
			  AND pitl.slot_name = tsl.slot_name
			WHERE v.id = ". (int) $cID. "
			  AND v.type = '". \ze\escape::asciiInSQL($cType). "'
			  AND v.version = ". (int) $cVersion. "
			  AND IFNULL(piil.module_id, pitl.module_id) in (". \ze\escape::in($moduleId, true). ") 
			  AND IFNULL(piil.instance_id, pitl.instance_id) = 0
			GROUP BY tsl.slot_name
			ORDER BY
				pitl.slot_name IS NOT NULL DESC,
				tsl.slot_name IS NOT NULL DESC,
				tsl.slot_name LIKE '%Main%' DESC,
				tsl.ord,
				tsl.slot_name";
	
		if ($limitToOne) {
			$sql .= "
				LIMIT 1";
		}

		$slots = [];
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			if ($row['slot_name']) {
				if ($limitToOne) {
					return $row['slot_name'];
				} else {
					$slots[] = $row['slot_name'];
				}
			}
		}
	
		if ($limitToOne) {
			return false;
		} else {
			return $slots;
		}
	}
	
	public static function getContentItemsWithPluginsThatMustBeOnPublicOrPrivatePage($pagePrivacyRequirement) {
		if ($pagePrivacyRequirement == 'public_page' || $pagePrivacyRequirement == 'private_page') {
			//Get an array of:
			//all public items with contain plugins that must be on a private page,
			//or all private items with contain plugins that must be on a public page.
			$sql = "
				SELECT DISTINCT c.id, c.type, c.alias, c.language_id, m.class_name
				FROM " . DB_PREFIX . "modules AS m
				INNER JOIN " . DB_PREFIX . "plugin_item_link AS pil
				ON pil.module_id = m.id
				INNER JOIN " . DB_PREFIX . "content_items AS c
				ON pil.content_id = c.id
				AND pil.content_type = c.type
				AND pil.content_version IN (c.visitor_version, c.admin_version)
				INNER JOIN " . DB_PREFIX . "translation_chains AS tc
				ON c.equiv_id = tc.equiv_id
				AND c.type = tc.type
				WHERE m.must_be_on = '" . $pagePrivacyRequirement . "' ";
				if ($pagePrivacyRequirement == 'private_page') {
					$sql .= "AND tc.privacy IN ('public', 'logged_out')";
				} elseif ($pagePrivacyRequirement == 'public_page') {
					$sql .= "AND tc.privacy NOT IN ('public', 'logged_out')";
				}
			$result = \ze\sql::select($sql);
			$resultArray = [];
			$i = 0;
			while ($row = \ze\sql::fetchAssoc($result)) {
				
				$link = htmlspecialchars(\ze\link::toItem($row['id'], $row['type'], true));
				$contentItemName = \ze\content::formatTag($row['id'], $row['type'], $row['alias'], $row['language_id']);
				$contentItemStatusImageClass = '<span class="organizer_item_image ' . self::getItemIconClass($row['id'], $row['type']) . '"></span>';
				
				$contentItem = '<a href="' . $link . '" target="_blank">'
					. $contentItemStatusImageClass . $contentItemName
					. '</a>';
				
				$plugin = $row['class_name'];
				$plugin = '<br>Module: ' . $plugin;
				
				$resultArray[] = '<div class="content_item_plugin_privacy_mismatch">' . $contentItem . $plugin . '</div>';
			}
			
			return $resultArray;
		} else {
			return false;
		}
	}
	
	
	
	public static function debugAndReportLayoutError($file = false) {
		
		if (!is_dir(CMS_ROOT. 'cache/')) {
			\ze\contentAdm::reportLayoutError('The cache/ directory does not exist, please create it.');
		
		} elseif (!is_writable(CMS_ROOT. 'cache/')) {
			\ze\contentAdm::reportLayoutError('The cache/ directory is not writable by the web server, please make it writable.');
		
		} elseif (!is_dir(CMS_ROOT. 'cache/layouts/')) {
			\ze\contentAdm::reportLayoutError('The cache/layouts/ directory does not exist, please create it.');
		
		} elseif (!is_writable(CMS_ROOT. 'cache/layouts/')) {
			\ze\contentAdm::reportLayoutError('The cache/layouts/ directory is not writable by the web server, please make it writable.');
		
		} elseif ($file && is_dir(dirname(CMS_ROOT. $file)) && !is_writable(dirname(CMS_ROOT. $file))) {
			\ze\contentAdm::reportLayoutError('The sub-directories in the cache/layouts/ directory are not writable by the web server, please make them writable.');
		
		} elseif ($file && is_file(CMS_ROOT. $file) && !is_writable(CMS_ROOT. $file)) {
			\ze\contentAdm::reportLayoutError('The files in the cache/layouts/ directory are not writable by the web server, please make them writable.');
		}
		
		exit;
	}
	
	public static function reportLayoutError($msg) {
		echo 
			'<div style="padding:auto; margin:auto; text-align: center; position: absolute; top: 35%; width: 100%;">',
				htmlspecialchars($msg),
			'</div>';
		
		\ze\db::reportError('Directory not writable at: ', $msg);
	}
}
