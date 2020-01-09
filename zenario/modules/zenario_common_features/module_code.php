<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_common_features extends ze\moduleBaseClass {
	
	
	
	
	// Centralised list for user status
	public static function userStatus($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return [
					'pending' => 'Pending', 
					'active' => 'Active', 
					'suspended' => 'Suspended', 
					'contact' => 'Contact'
				];
		}
	}
	
	
	/*	Pagination  */
	
	public function pagSelectList($currentPage, &$pages, &$html) {
		
		$html = '
			<select onChange="eval(this.value);" class="pagination">';
			
		foreach($pages as $page => &$params) {
			$html .= '
				<option '. ($currentPage == $page? 'selected="selected"' : ''). '" value="'.
					$this->refreshPluginSlotJS($params).
				'">'.
					htmlspecialchars($page).
				'</options>';
		}
			
		$html .= '
			</select>';
	}
	
	
	
	public function pagCurrentWithNP($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Current', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = true);
	}
	
	public function pagCurrentWithFNPL($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Current', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = true);
	}
	
	public function pagAll($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'All', $showNextPrev = false, $showFirstLast = false, $alwaysShowNextPrev = false);
	}
	
	public function pagAllWithNPIfNeeded($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'All', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false);
	}
	
	public function pagCloseWithNPIfNeeded($currentPage, &$pages, &$html, &$links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Close', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false, $links, $extraAttributes);
	}
	
	public function pagCloseWithNP($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Close', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = true);
	}
	
	public function pagCloseWithFNPLIfNeeded($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Close', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = false);
	}
	
	public function pagCloseWithFNPL($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Close', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = true);
	}
	
	
	
	protected function drawPageLink($pageName, $request, $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', &$links = [], $extraAttributes = []) {
		$link = [];
		
		$link['active'] = ($page === $currentPage) ? true : false;
		$link['request'] = $request ? $this->refreshPluginSlotAnchor($request) : '';
		$link['rel'] = $page === $prevPage ? 'prev' : ($page === $nextPage? 'next' : '');
		$link['text'] = $pageName;
		
		$links[] = $link;
		
		$extraAttributes = isset($extraAttributes[$page]) ? $extraAttributes[$page] : '';
		
		return '
			<span class="'. $css. ($page === $currentPage? '_on' : ''). '" ' . $extraAttributes . '><span>
				<a '.
					($request? $this->refreshPluginSlotAnchor($request) : '').
					($page === $prevPage? ' rel="prev"' : ($page === $nextPage? ' rel="next"' : '')).
				'>'.
					$pageName.
				'</a>
			</span></span>';
	}
		
	protected function pageNumbers($currentPage, &$pages, &$html, $pageNumbers = 'Close', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = false, &$links = [], $extraAttributes = []) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	
	//
	//	Admin functions
	//
	
	
	
	
	public function handleAJAX() {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->adminBoxDownload($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public static function setMenuPath(&$fields, $field, $value) {
		
		if (!empty($fields[$field][$value])) {
			
			if (!empty($fields['parent_path_of__'. $field]['value'])) {
				$fields['path_of__'. $field][$value] =
					$fields['parent_path_of__'. $field]['value']. ' -> '. $fields[$field][$value];
			
			} else {
				$fields['path_of__'. $field][$value] =
					$fields[$field][$value];
			}
		
		} else {
			unset($fields['path_of__'. $field][$value]);
		}
	}
	
	public static function sortFieldsByOrd($a, $b) {
		if (empty($a['ord']) || empty($b['ord']) || $a['ord'] == $b['ord']) {
			return 0;
		}
		return ($a['ord'] < $b['ord']) ? -1 : 1;
	}
	
	
	
	
	
	
	
	
	
	
	//
	//	Organizer functions
	//
	
	public function fillOrganizerNav(&$nav) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class, 'organizer', $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		} else {
			return require ze::funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleAdminToolbarAJAX($cID, $cType, $cVersion, $ids) {
		return require ze::funIncPath(__FILE__, __FUNCTION__);
	}

	public static function deleteCategory($id, $recurseCount = 0) {
		require ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function jobPublishContent($serverTime) {
		$sql = "
			SELECT v.id, v.type, v.version, c.status
			FROM ". DB_PREFIX. "content_item_versions AS v
			INNER JOIN ". DB_PREFIX. "content_items AS c
			   ON c.id = v.id
			  AND c.type = v.type
			WHERE v.scheduled_publish_datetime <= STR_TO_DATE('". ze\escape::sql($serverTime). "', '%Y-%m-%d %H:%i:%s')";
		$result = ze\sql::select($sql);
		
		$action = false;
		while($citem = ze\sql::fetchAssoc($result)) {
			
			if ($citem['status'] == 'hidden' || ze\content::isDraft($citem['status'])) {
				// Publish marked draft items
				$adminId = ze\row::get('content_items', 'lock_owner_id', ['id'=>$citem['id'], 'type'=>$citem['type']]);
				ze\contentAdm::publishContent($citem['id'], $citem['type'], $adminId);
				$action = true;
				echo ze\admin::phrase('Published Content Item [[tag]]', ['tag' => ze\content::formatTag($citem['id'], $citem['type'])]), "\n";
			}
			
			// Update scheduled time
			ze\row::update('content_item_versions',
				['scheduled_publish_datetime' => null],
				['id' => $citem['id'], 'type' => $citem['type'], 'version' => $citem['version']]
			);
		}
		
		if (!$action) {
			echo ze\admin::phrase('No Content Items to Publish'), "\n";
		}
		
		return $action;
	}
	
	//A scheduled task to delete stored content
	public static function jobDataProtectionCleanup() {
		$actionsTaken = 0;
		
		//Modules that want to clear some kind of data have a clearOldData public static method that deletes it
		//based on some site-setting.
		$modulesWithDataToClear = [
			'zenario_email_template_manager',
			'zenario_scheduled_task_manager',
			'zenario_incoming_email_manager',
			'zenario_users',
			'zenario_user_forms'
		];
		
		foreach ($modulesWithDataToClear as $moduleName) {
			if (ze\module::inc($moduleName)) {
				$actionsTaken += call_user_func([$moduleName, 'clearOldData']);
			}
		}
		
		return $actionsTaken > 0;
	}
	
	
	
	
	public static function canCreateAdditionalAdmins() {
		$limit = ze\site::description('max_local_administrators');
		return !$limit || ze\row::count('admins', ['is_client_account' => 1, 'status' => 'active']) < $limit;
	}
	
	//Get the salutation LoV
	public static function getSalutations($mode, $value = false) {
		switch ($mode) {
			case ze\dataset::LIST_MODE_INFO:
				return ['can_filter' => false];
			case ze\dataset::LIST_MODE_LIST:
				return ze\row::getValues('lov_salutations', 'name', [], 'name', 'name');
			case ze\dataset::LIST_MODE_VALUE:
				return $value;
		}
	}
	
	public static function getTranslationsAndPluginsLinkingToThisContentItem($ids, &$box, &$fields, &$values, $panelName, $totalRowNum) {
		
		//Get plugins linking to this content item.
		$message = '';
		$pluginsLinkingToThisContentItem = false;
		foreach ($ids as $tagId) {
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			$sql = "
				SELECT
					pi.module_id,
					pi.name,
					m.class_name,
					m.display_name,
					ps.instance_id,
					ps.egg_id,
					pi.content_id,
					pi.content_type,
					pi.content_version,
					pi.slot_name,
					c.alias
				FROM ". DB_PREFIX. "plugin_settings AS ps
				INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
				   ON pi.id = ps.instance_id
				INNER JOIN ". DB_PREFIX. "modules AS m
				   ON m.id = pi.module_id
				LEFT JOIN ". DB_PREFIX. "content_items AS c
				   ON pi.content_id = c.id AND pi.content_type = c.type
				WHERE foreign_key_to = 'content'
				  AND foreign_key_id = ".(int)$cID."
				  AND foreign_key_char = '".ze\escape::sql($cType)."'
				ORDER BY display_name, name DESC";
			$result = ze\sql::select($sql);
			
			//If attempting to trash/delete multiple content items, show headings with the appropriate content item tags.
			if ((count($ids) > 1) && ze\sql::numRows($result)) {
				$message .= '<br/><p><b>'.ze\content::formatTag($cID, $cType).'</b></p><br/>';
			}
			
			$currentRow = [];
			$prevModuleId = false;
			$skLink = 'zenario/admin/organizer.php?fromCID='.(int)$cID.'&fromCType='.urlencode($cType);
			
			while ($row = ze\sql::fetchAssoc($result)) {
				if ($prevModuleId !== $row['module_id']) {
					if ($prevModuleId) {
						self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
						$pluginsLinkingToThisContentItem = true;
					}
					$prevModuleId = $row['module_id'];
					$plugabbleCount = $versionControlledCount = 0;
					$linkToLibraryPlugin = $linkToVersionControlledPlugin = '';
					
					switch ($row['class_name']) {
						case 'zenario_plugin_nest':
							$pluginsLink = '#zenario__modules/panels/plugins/refiners/nests////';
							break;
							
						case 'zenario_slideshow':
							$pluginsLink = '#zenario__modules/panels/plugins/refiners/slideshows////';
							break;
							
						default:
							$pluginsLink = '#zenario__modules/panels/modules/item//'. $row['module_id']. '//';
					}
				}
				if ($row['content_id']) {
					if (!$linkToVersionControlledPlugin) {
						$linkToVersionControlledPlugin = '<a href="'.ze\link::toItem($row['content_id'], $row['content_type'], true, '', false, false, true).'" target="_blank">'.ze\content::formatTag($row['content_id'], $row['content_type']).'</a>';
					}
					$versionControlledCount++;
				} else {
					if (!$linkToLibraryPlugin) {
						$linkToLibraryPlugin = '<a href="'.$skLink.$pluginsLink.$row['instance_id'].'" target="_blank">'.$row['name'].'</a>';
					}
					$plugabbleCount++;
				}
				$currentRow = $row;
			}
			
			if ($prevModuleId) {
				self::addToMessage($message, $plugabbleCount, $versionControlledCount, $currentRow, $linkToLibraryPlugin, $linkToVersionControlledPlugin);
				$pluginsLinkingToThisContentItem = true;
			}
			
			//Show content translations if any exist			
			$sql = "
				SELECT
					ci.id,
					ci.type,
					ci.equiv_id,
					ci.status,
					ci.language_id
				FROM ". DB_PREFIX. "content_items AS ci
				WHERE ci.equiv_id = " . ze\escape::sql(ze\content::equivId($cID, $cType)) . "
				AND ci.type = '" . ze\escape::sql($cType) . "'";
			
			$result = ze\sql::select($sql);
		
			$numTranslations = ze\sql::numRows($result);
			$showParentAlias = true;
			if ($numTranslations) {
				
				while ($row = ze\sql::fetchAssoc($result)) {
					if (!in_array($row['type'] . '_' . $row['id'], $ids)) {
						++$totalRowNum;
					
						$suffix = '__' . $totalRowNum;
					
						if ($showParentAlias) {
							$values[$panelName . '/content_item' . $suffix] = ze\content::formatTag($cID, $cType);
						} else {
							$values[$panelName . '/content_item' . $suffix] = '';
						}
						$values[$panelName . '/translation' . $suffix] = ze\content::formatTag($row['id'], $row['type']);
						$values[$panelName . '/status' . $suffix] = ze\contentAdm::statusPhrase($row['status']);
						$values[$panelName . '/language_id' . $suffix] = $row['language_id'];
					
						$showParentAlias = false;
					}
				}
				
				if ($totalRowNum > 0) {
					
					//Show the translations table if any content item has translations.
					$fields[$panelName . '/th_content_item']['hidden'] = 
					$fields[$panelName . '/th_translation']['hidden'] = 
					$fields[$panelName . '/th_status']['hidden'] = 
					$fields[$panelName . '/th_action']['hidden'] = 
					$fields[$panelName . '/table_end']['hidden'] = false;
				
					$fields[$panelName . '/translations_warning']['snippet']['html'] = 
						ze\admin::nPhrase(
							'There is 1 content item translation available. Please select what you wish to do with it.',
							'There are [[count]] content item translations available. Please select what you wish to do with them.',
							$totalRowNum,
							['count' => $totalRowNum]
						);
					$fields[$panelName . '/translations_warning']['hidden'] = false;
					$box['max_height'] = false;
				}
			}
		}
		
		$changes = [];
		ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = true,
			$box['tabs'][$panelName]['custom_template_fields'],
			$totalRowNum,
			$minNumRows = 0,
			$tabName = $panelName
		);
		
		//Disable "Trash/Delete translation" option for content items which can't be trashed/deleted
		$startAt = 1;
		for ($n = $startAt; (($suffix = '__'. $n) && (!empty($fields[$panelName . '/translation'. $suffix]))); ++$n) {
			$tagId = $values[$panelName . '/translation'. $suffix];
			
			ze\content::removeFormattingFromTag($tagId);
			
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			
			switch ($panelName) {
				case 'trash':
					if (!ze\contentAdm::allowTrash($cID, $cType, false, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
						$fields[$panelName . '/action'. $suffix]['values']['trash']['disabled'] = true;
						$fields[$panelName . '/action'. $suffix]['values']['trash']['label'] = ze\admin::phrase('This translation cannot be trashed.');
						if (ze\contentAdm::allowDelete($cID, $cType, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
							$fields[$panelName . '/action'. $suffix]['values']['delete']['label'] = ze\admin::phrase('Delete draft translation');
						}
					}
					break;
				case 'delete_draft':
					if (!ze\contentAdm::allowDelete($cID, $cType, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
						$fields[$panelName . '/action'. $suffix]['values']['delete']['disabled'] = true;
						$fields[$panelName . '/action'. $suffix]['values']['delete']['label'] = ze\admin::phrase('This translation cannot be deleted.');
						if (ze\contentAdm::allowTrash($cID, $cType, false, false, $contentItemLanguageId = $values[$panelName . '/language_id'. $suffix])) {
							$fields[$panelName . '/action'. $suffix]['values']['trash']['label'] = ze\admin::phrase('Trash translation');
						}
					}
					break;
			}
			
		}
		
		if ($message) {
			if ($pluginsLinkingToThisContentItem) {
				switch ($panelName) {
					case 'trash':
						$fields[$panelName . '/trash_options']['hidden'] = false;
						break;
					case 'delete_draft':
						$fields[$panelName . '/delete_options']['hidden'] = false;
						break;
				}
			}
			
			$fields[$panelName . '/links_warning']['hidden'] = false;
			$fields[$panelName . '/links_warning']['snippet']['html'] = $message;
			$box['max_height'] = false;
		}
	}
	
	public static function addToMessage(&$message, $plugabbleCount, $versionControlledCount, $row, $linkToLibraryPlugin, $linkToVersionControlledPlugin) {
		if ($plugabbleCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] "[[display_name]]" library plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" library plugins linking to this Content Item. "[[link]]" and [[count2]] other[[s]].</p>', 
				$plugabbleCount,
				[
					'count' => $plugabbleCount, 
					'count2' => $plugabbleCount - 1, 
					'display_name' => $row['display_name'], 
					'link' => $linkToLibraryPlugin,
					's' => ($plugabbleCount - 1) == 1 ? '' : 's']);
		}
		if ($versionControlledCount) {
			$message .= ze\admin::nPhrase(
				'<p>There is [[count]] "[[display_name]]" version controlled plugin linking to this Content Item. "[[link]]".</p>',
				'<p>There are [[count]] "[[display_name]]" version controlled plugins linking to this Content Item. "[[link]]" plus [[count2]] other plugin[[s]].</p>',
				$versionControlledCount,
				[
					'count' => $versionControlledCount, 
					'count2' => $versionControlledCount - 1,
					'display_name' => $row['display_name'], 
					'link' => $linkToVersionControlledPlugin,
					's' => ($versionControlledCount - 1) == 1 ? '' : 's']);
		}
	}
	
	public static function deleteOrTrashTranslations(&$fields, &$values, $tabName) {
		$startAt = 1;
		for ($n = $startAt; (($suffix = '__'. $n) && (!empty($fields[$tabName . '/translation'. $suffix]))); ++$n) {
			
			$tagId = $values[$tabName . '/translation'. $suffix];
			
			ze\content::removeFormattingFromTag($tagId);
			
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			
			switch ($values[$tabName . '/action'. $suffix]) {
				case 'delete':
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				
					if (ze\contentAdm::allowDelete($cID, $cType) && ze\priv::check('_PRIV_DELETE_DRAFT', $cID, $cType)) {
						ze\contentAdm::deleteDraft($cID, $cType);
					}
					break;
					
				case 'trash':
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				
					if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::trashContent($cID, $cType);
					}
					break;
			}
		}
	}
}