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


class zenario_common_features extends module_base_class {
	
	public static function lookForMenuItems($parentMenuId, $language, $sectionId, $currentMenuId, $recurseCount, $showInvisibleMenuItems) {
	
		$sql = "
			SELECT
				m.id AS mID,
				t.name,
				m.target_loc,
				m.open_in_new_window,
				m.anchor,
				m.module_class_name,
				m.method_name,
				m.param_1,
				m.param_2,
				m.equiv_id,
				c.id AS cID,
				m.content_type AS cType,
				c.alias,
				m.use_download_page,
				m.hide_private_item,
				t.ext_url,
				c.visitor_version,
				m.invisible,
				m.accesskey,
				m.ordinal,
				m.rel_tag,
				m.image_id,
				m.rollover_image_id
			FROM ". DB_NAME_PREFIX. "menu_nodes AS m
			INNER JOIN ". DB_NAME_PREFIX. "menu_text AS t
			   ON t.menu_id = m.id
			  AND t.language_id = '". sqlEscape($language). "'
			LEFT JOIN ".DB_NAME_PREFIX."content AS c
			   ON m.target_loc = 'int'
			  AND m.equiv_id = c.equiv_id
			  AND m.content_type = c.type
			  AND c.language_id = '". sqlEscape($language). "'
			WHERE m.parent_id = ". (int) $parentMenuId. "
			  AND m.section_id = ". (int) $sectionId. "
			ORDER BY m.ordinal";
		
		return sqlQuery($sql);
	}
	
	public function deleteHierarchicalDocument($documentId) {
		$details = getRow('documents', array('type', 'file_id', 'thumbnail_id'), $documentId);
		deleteRow('documents', array('id' => $documentId));
		if ($details && $details['type'] == 'folder') {
			$children = getRows('documents', array('id', 'type'), array('folder_id' => $documentId));
			while ($row = sqlFetchAssoc($children)) {
				self::deleteHierarchicalDocument($row['id']);
			}
		} elseif ($details && $details['type'] == 'file') {
			if ($details['file_id']) {
				$fileDetails = getRow('files', array('path', 'filename', 'location'), $details['file_id']);
				$symPath = CMS_ROOT . 'public' . '/' . $fileDetails['path'] . '/'. $fileDetails['filename'];
				$symFolder = CMS_ROOT . 'public' . '/' . $fileDetails['path'];
				if (file_exists($symPath)) {
					unlink($symPath);
					rmdir($symFolder);
				}
				if ($fileDetails['location'] == 'docstore' &&  $fileDetails['path']) {
					unlink(setting('docstore_dir') . '/'. $fileDetails['path'] . '/' . $fileDetails['filename']);
					rmdir(setting('docstore_dir') . '/'. $fileDetails['path']);
				}
				deleteRow('files', array('id' => $details['file_id']));
			}
			if ($details['thumbnail_id']) {
				deleteRow('files', array('id' => $details['thumbnail_id']));
			}
		}
	}
	
	public function deleteDocumentTag($tagId) {
		deleteRow('document_tags', array('id' => $tagId));
		
	}
	
	
	public static function addExtractToDocument($file_id) {
		$documentProperties = array();
		$extract = array();
		$thumbnailId = false;
		updateDocumentPlainTextExtract($file_id, $extract, $thumbnailId);
		
		if ($extract['extract']) {
			$documentProperties['extract'] = $extract['extract'];
			$documentProperties['extract_wordcount'] = $extract['extract_wordcount'];
		}
		if ($thumbnailId) {
			$documentProperties['thumbnail_id'] = $thumbnailId;
		}
		return $documentProperties;
	}
	
	public static function userStatus() {
		return array('pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'contact' => 'Contact');
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
	
	public function pagCloseWithNPIfNeeded($currentPage, &$pages, &$html, &$links = array()) {
		$this->pageNumbers($currentPage, $pages, $html, 'Close', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false, $links);
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
	
	
	
	protected function drawPageLink($pageName, $request, $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', &$links = array()) {
		$link = array();
		
		$link['active'] = ($page === $currentPage) ? true : false;
		$link['request'] = $request ? $this->refreshPluginSlotAnchor($request) : '';
		$link['rel'] = $page === $prevPage ? 'prev' : ($page === $nextPage? 'next' : '');
		$link['text'] = $pageName;
		
		$links[] = $link;
		
		return '
			<span class="'. $css. ($page === $currentPage? '_on' : ''). '"><span>
				<a '.
					($request? $this->refreshPluginSlotAnchor($request) : '').
					($page === $prevPage? ' rel="prev"' : ($page === $nextPage? ' rel="next"' : '')).
				'>'.
					$pageName.
				'</a>
			</span></span>';
	
	}
		
	protected function pageNumbers($currentPage, &$pages, &$html, $pageNumbers = 'Close', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = false, &$links = array()) {
		$html = '
			<div class="pag_pagination">';
		
		//Find the total number of pages
		$count = count($pages);
		
		//Don't output anything if there is only one page!
		if ($count > 1) {
			//Pages might not be numeric, so get something that is to work with
			$pagesPos = array_keys($pages);
			$currentPos = (int) array_search($currentPage, $pagesPos);
			
			//Work out which pages should be marked as "previous" and "next"
			$prevPage = false;
			$nextPage = false;
			if (($currentPos > 0) && (isset($pagesPos[$currentPos-1]))) {
				$prevPage = $pagesPos[$currentPos-1];
			}
			if (($currentPos < $count - 1) && (isset($pagesPos[$currentPos+1]))) {
				$nextPage = $pagesPos[$currentPos+1];
			}
			
			if ($showFirstLast && $currentPos > ($showNextPrev? 1 : 0)) {
				$html .= $this->drawPageLink($this->phrase('_FIRST'), $pages[$pagesPos[0]], $pagesPos[0], $currentPage, $prevPage, $nextPage, 'pag_first', $links);
			}
			
			if ($showNextPrev && $prevPage !== false) {
				$html .= $this->drawPageLink($this->phrase('_PREV'), $pages[$prevPage], $prevPage, $currentPage, $prevPage, $nextPage, 'pag_prev', $links);
			} elseif ($showNextPrev && $alwaysShowNextPrev) {
				$html .= $this->drawPageLink($this->phrase('_PREV'), '', '', $currentPage, $prevPage, $nextPage, 'pag_prev', $links);
			}
			
			
			if ($pageNumbers == 'Current') {
				$page = $pagesPos[$currentPos];
				$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links);
				
			} elseif ($pageNumbers == 'All') {
				foreach($pages as $page => &$request) {
					$html .= $this->drawPageLink($page, $request, $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links);
				}
				
			} elseif ($pageNumbers == 'Close') {
				//Check if each is there, and include it if so
				for ($pos = $currentPos - 4; $pos <= $currentPos + 4; ++$pos) {
					if (isset($pagesPos[$pos])) {
						$page = $pagesPos[$pos];
						$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links);
					}
				}
				
			} elseif ($pageNumbers == 'Smart') {
				$this->smartPageNumbers($currentPos, $count, $showFirstLast, $pagesPos, $pages, $html, $currentPage, $prevPage, $nextPage);
			}
			
			
			if ($showNextPrev && $nextPage !== false) {
				$html .= $this->drawPageLink($this->phrase('_NEXT'), $pages[$nextPage], $nextPage, $currentPage, $prevPage, $nextPage, 'pag_next', $links);
			} elseif ($showNextPrev && $alwaysShowNextPrev) {
				$html .= $this->drawPageLink($this->phrase('_NEXT'), '', '', $currentPage, $prevPage, $nextPage, 'pag_next', $links);
			}
			
			if ($showFirstLast && $currentPos < $count - ($showNextPrev? 2 : 1)) {
				$html .= $this->drawPageLink($this->phrase('_LAST'), $pages[$pagesPos[$count-1]], $pagesPos[$count-1], $currentPage, $prevPage, $nextPage, 'pag_last', $links);
			}
		}
		
		$html .= '
				</div>';
	}
	
	
	public static function initInstance(
		&$slotContents, $slotName,
		$cID, $cType, $cVersion,
		$layoutId, $templateFamily, $templateFileBaseName,
		$specificInstanceId, $specificSlotName, $ajaxReload,
		$runPlugins
	) {
		$missingPlugin = false;
		if (includeModuleAndDependencies($slotContents[$slotName]['class_name'], $missingPlugin)
		 && method_exists($slotContents[$slotName]['class_name'], 'showSlot')) {
			
			//Fetch the name of the instance, and the name of the swatch being used
			$sql = "
				SELECT name, framework, css_class
				FROM ". DB_NAME_PREFIX. "plugin_instances
				WHERE id = ". (int) $slotContents[$slotName]['instance_id'];
			$result = sqlQuery($sql);
			if ($row = sqlFetchAssoc($result)) {
				//If we found a plugin to display, activate it and set it up
				$slotContents[$slotName]['instance_name'] = $row['name'];
				$slotContents[$slotName]['framework'] = ifNull($row['framework'], $slotContents[$slotName]['default_framework']);
				
				if ($row['css_class']) {
					$slotContents[$slotName]['css_class'] .= ' '. $row['css_class'];
				} else {
					$slotContents[$slotName]['css_class'] .= ' '. $slotContents[$slotName]['css_class_name']. '__default_style';
				}
				
				if ($runPlugins) {
					setInstance($slotContents[$slotName], $cID, $cType, $cVersion, $slotName, $checkForErrorPages = true);
					
					if (initInstance($slotContents[$slotName])) {
						if (!$ajaxReload && ($location = $slotContents[$slotName]['class']->checkHeaderRedirectLocation())) {
							header("Location: ". $location);
							exit;
						}
					}
				}
				
			} else {
				$details = getModuleDetails($slotContents[$slotName]['module_id']);
				
				if ($runPlugins) {
					setupNewBaseClassPlugin($slotName);
					$slotContents[$slotName]['error'] = adminPhrase('[Plugin Instance not found for the Module &quot;[[module]]&quot;]', array('module' => htmlspecialchars($details['display_name'])));
				}
			}
		} else {
			$details = getModuleDetails($slotContents[$slotName]['module_id']);
			
			if ($runPlugins) {
				setupNewBaseClassPlugin($slotName);
				$slotContents[$slotName]['error'] = adminPhrase('[Selected Module &quot;[[module]]&quot; not found, not running, or has missing dependencies]', array('module' => htmlspecialchars($details['display_name'])));
			}
		}
	}
	
	public static function preSlot($slotName, $showPlaceholderMethod) {
		
	}
	
	public static function postSlot($slotName, $showPlaceholderMethod) {
		
	}
	
	public static function poweredBy() {
		echo 'Powered by <a href="http://zenar.io">Zenario</a>';
	}
	
	//These have been left blank, but anyone writing an edition Module can write them should they wish
	//to clear various caches after a Database Query.
	//Note that setting $runSql to true should cause this function to return the results of a sqlAffectedRows() call.
	public static function reviewDatabaseQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
		if ($runSql) {
			sqlUpdate($sql, false);
			return sqlAffectedRows();
		}
	}
	
	
	
	
	public static function fillInfoBox(&$infoBox) {
		$infoBox[10]['fields'][10]['value'] = adminPhrase('Community');
		$infoBox[10]['fields'][20]['value'] = adminPhrase('BSD');
	}
	
	
	
	
	//
	//	Admin functions
	//
	
	
	
	
	public static function publishContent($cID, $cType, $cVersion, $prev_version, $adminId = false) {
		deleteArchive($cID, $cType, $prev_version);
	}


	public function handleAJAX() {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	protected function validateChangeSingleLayout(&$box, $cID, $cType, $cVersion, $newLayoutId, $saving) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	private static function setMenuPath(&$fields, $field, $value) {
		
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
	//	Storekeeper functions
	//
	
	public function lineStorekeeperCSV($path, &$columns, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->lineStorekeeperCSV($path, $columns, $refinerName, $refinerId);
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
		
		if ($path == 'zenario__content/panels/content/test_bespoke_dynamic_html') {
			//A test standalone application in Organizer, written dynamically using plain HTML
			$panel['html'] = '
				<h1>A test standalone application in Organizer, written dynamically using plain HTML</h1>
				<p>The time is currently '. formatDateTimeNicely(now()). '</p>';
		
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public static function advancedSearchWhereStatement($path, $values, $tablePrefix) {
		return '';
	}
	
	public static function advancedSearchTableJoins($path, $values, $tablePrefix) {
		return array();
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleAdminToolbarAJAX($cID, $cType, $cVersion, $ids) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	protected function languageImportResults($numberOf, $error = false, $changeButtonHTML = false) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}

	protected function deleteCategory($id, $recurseCount = 0) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function processDocumentRules($documentIds) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function fileSizeConvert($bytes) {
		$bytes = floatval($bytes);
			$arBytes = array(
				0 => array(
					"UNIT" => "TB",
					"VALUE" => pow(1024, 4)
				),
				1 => array(
					"UNIT" => "GB",
					"VALUE" => pow(1024, 3)
				),
				2 => array(
					"UNIT" => "MB",
					"VALUE" => pow(1024, 2)
				),
				3 => array(
					"UNIT" => "KB",
					"VALUE" => 1024
				),
				4 => array(
					"UNIT" => "bytes",
					"VALUE" => 1
				),
			);
		
		foreach($arBytes as $arItem) {
			if($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = strval(round($result, 2)). " " .$arItem["UNIT"];
				break;
			}
		}
		return $result;
	}
	
	public static function jobPublishContent($serverTime) {
		
		$sql = "
			SELECT id, type, version
			FROM ". DB_NAME_PREFIX. "versions
			WHERE scheduled_publish_datetime <= STR_TO_DATE('". sqlEscape($serverTime). "', '%Y-%m-%d %H:%i:%s')";
		$result = sqlQuery($sql);
		
		$action = false;
		while($citem = sqlFetchAssoc($result)) {
			
			if (isDraft($citem['id'], $citem['type'], $citem['version'])) {
				// Publish marked draft items
				$adminId = getRow('content', 'lock_owner_id', array('id'=>$citem['id'], 'type'=>$citem['type']));
				publishContent($citem['id'], $citem['type'], $adminId);
				$action = true;
				echo adminPhrase('Published Content Item [[tag]]', array('tag' => formatTag($citem['id'], $citem['type']))), "\n";
				
				// Update scheduled time
				updateRow('versions', array('scheduled_publish_datetime'=>NULL), array('id'=>$citem['id'], 'type'=>$citem['type'], 'version'=>$citem['version']));
			}
		}
		
		if (!$action) {
			echo adminPhrase('No Content Items to Publish'), "\n";
		}
		
		return $action;
	}
}