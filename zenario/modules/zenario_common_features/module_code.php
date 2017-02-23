<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
	
	public static function deleteHierarchicalDocumentPubliclink($documentId) {
		$document = getRow('documents', array('file_id'), $documentId);
		$file = getRow('files',  array('short_checksum'), $document['file_id']);
		
		if (deleteCacheDir(CMS_ROOT. 'public/downloads/'. $file['short_checksum'], 1)) {
			updateRow('documents', array('privacy' => 'auto'), array('file_id' => $document['file_id']));
			return true;
			
		} else {
			return adminPhrase("Unable to public link directory as it is not empty.");
		}
	}
	
	public static function deleteHierarchicalDocument($documentId) {
		$details = getRow('documents', array('type', 'file_id', 'thumbnail_id'), $documentId);
		
		if ($details && $details['type'] == 'folder') {
			deleteRow('documents', array('id' => $documentId));
			$children = getRows('documents', array('id', 'type'), array('folder_id' => $documentId));
			while ($row = sqlFetchAssoc($children)) {
				self::deleteHierarchicalDocument($row['id']);
			}
		} elseif ($details && $details['type'] == 'file') {
			
			$fileDetails = getRow('files', array('path', 'filename', 'location'), $details['file_id']);
			$document = getRow('documents', array('file_id', 'filename'), array('id'=>$documentId));
			$fileIdsInDocument = getRowsArray('documents', array('file_id', 'filename'), array('file_id'=>$document['file_id']));
			$numberFileIds =count($fileIdsInDocument);
			
			$file = getRow('files', 
							array('id', 'filename', 'path', 'created_datetime'),
							$document['file_id']);
			
			if($file['filename']) {
				self::deleteHierarchicalDocumentPubliclink($documentId);
				//check to see if file used by another document before deleting or used in ctype documents
				if (($numberFileIds == 1) && !checkRowExists('content_item_versions', array('file_id' => $details['file_id']))) {
					deleteRow('files', array('id' => $details['file_id']));
					if ($details['thumbnail_id']) {
						deleteRow('files', array('id' => $details['thumbnail_id']));
					}
					if ($fileDetails['location'] == 'docstore' &&  $fileDetails['path']) {
						unlink(setting('docstore_dir') . '/'. $fileDetails['path'] . '/' . $fileDetails['filename']);
						rmdir(setting('docstore_dir') . '/'. $fileDetails['path']);
					}
				}
			}
			deleteRow('documents', array('id' => $documentId));
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
	
	public static function generateDocumentPublicLink($documentId) {
		$error = new zenario_error();
		
		$document = getRow('documents', array('file_id', 'filename'), $documentId);
		$file = getRow(
			'files', 
			array('id', 'filename', 'path', 'created_datetime', 'short_checksum'),
			array('id' => $document['file_id'])
		);
		if($file['filename']) {
			if (cleanDownloads()) {
				$dirPath = createCacheDir($file['short_checksum'], 'public/downloads', false);
			}
			if (!$dirPath) {
				$error->add('message', 'Could not generate public link because public file structure incorrect');
				return $error;
			}
			
			$symFolder =  CMS_ROOT . $dirPath;
			$symPath = $symFolder . $document['filename'];
			
			$frontLink = $dirPath . $document['filename'];
			if (!windowsServer() && ($path = docstoreFilePath($file['id'], false))) {
				if (!file_exists($symPath)) {
					if(!file_exists($symFolder)) {
						mkdir($symFolder);
					}
					symlink($path, $symPath);
				} 
				
				updateRow('documents', array('privacy' => 'public'), $documentId);
				
				return $frontLink;
				
			} else {
				if (windowsServer()) {
					$error->add('message', 'Could not generate public link because the CMS is installed on a windows server.');
				} else {
					$error->add('message', 'Could not generate public link because this document is not stored in the Docstore.</br>Make sure the Docstore directory is correctly setup and re-upload this document.');
				}
			}
		} else {
			$error->add('message', 'Could not generate public link because no file exists');
		}
		return $error;
	}
	
	
	
	
	
	
	// Centralised list for user status
	public static function userStatus($mode, $value = false) {
		switch ($mode) {
			case ZENARIO_CENTRALISED_LIST_MODE_INFO:
				return array('can_filter' => false);
			case ZENARIO_CENTRALISED_LIST_MODE_LIST:
				return array(
					'pending' => 'Pending', 
					'active' => 'Active', 
					'suspended' => 'Suspended', 
					'contact' => 'Contact'
				);
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
	
	public function pagCloseWithNPIfNeeded($currentPage, &$pages, &$html, &$links = array(), $extraAttributes = array()) {
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
	
	
	
	protected function drawPageLink($pageName, $request, $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', &$links = array(), $extraAttributes = array()) {
		$link = array();
		
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
		
	protected function pageNumbers($currentPage, &$pages, &$html, $pageNumbers = 'Close', $showNextPrev = true, $showFirstLast = true, $alwaysShowNextPrev = false, &$links = array(), $extraAttributes = array()) {
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
				$html .= $this->drawPageLink($this->phrase('First'), $pages[$pagesPos[0]], $pagesPos[0], $currentPage, $prevPage, $nextPage, 'pag_first', $links, $extraAttributes);
			}
			
			if ($showNextPrev && $prevPage !== false) {
				$html .= $this->drawPageLink($this->phrase('Prev'), $pages[$prevPage], $prevPage, $currentPage, $prevPage, $nextPage, 'pag_prev', $links, $extraAttributes);
			} elseif ($showNextPrev && $alwaysShowNextPrev) {
				$html .= $this->drawPageLink($this->phrase('Prev'), '', '', $currentPage, $prevPage, $nextPage, 'pag_prev', $links, $extraAttributes);
			}
			
			
			if ($pageNumbers == 'Current') {
				$page = $pagesPos[$currentPos];
				$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
				
			} elseif ($pageNumbers == 'All') {
				foreach($pages as $page => &$request) {
					$html .= $this->drawPageLink($page, $request, $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
				}
				
			} elseif ($pageNumbers == 'Close') {
				//Check if each is there, and include it if so
				for ($pos = $currentPos - 4; $pos <= $currentPos + 4; ++$pos) {
					if (isset($pagesPos[$pos])) {
						$page = $pagesPos[$pos];
						$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
					}
				}
				
			} elseif ($pageNumbers == 'Smart') {
				$this->smartPageNumbers($currentPos, $count, $showFirstLast, $pagesPos, $pages, $html, $currentPage, $prevPage, $nextPage, $links, $extraAttributes);
			}
			
			
			if ($showNextPrev && $nextPage !== false) {
				$html .= $this->drawPageLink($this->phrase('Next'), $pages[$nextPage], $nextPage, $currentPage, $prevPage, $nextPage, 'pag_next', $links, $extraAttributes);
			} elseif ($showNextPrev && $alwaysShowNextPrev) {
				$html .= $this->drawPageLink($this->phrase('Next'), '', '', $currentPage, $prevPage, $nextPage, 'pag_next', $links, $extraAttributes);
			}
			
			if ($showFirstLast && $currentPos < $count - ($showNextPrev? 2 : 1)) {
				$html .= $this->drawPageLink($this->phrase('Last'), $pages[$pagesPos[$count-1]], $pagesPos[$count-1], $currentPage, $prevPage, $nextPage, 'pag_last', $links, $extraAttributes);
			}
		}
		
		$html .= '
				</div>';
	}
	
	
	public static function loadPluginInstance(
		&$slotContents, $slotName,
		$cID, $cType, $cVersion,
		$layoutId, $templateFamily, $templateFileBaseName,
		$specificInstanceId, $specificSlotName, $ajaxReload,
		$runPlugins, $overrideSettings = false, $overrideFrameworkAndCSS = false
	) {
		$missingPlugin = false;
		$slot = &$slotContents[$slotName];
		
		if (includeModuleAndDependencies($slot['class_name'], $missingPlugin)
		 && method_exists($slot['class_name'], 'showSlot')) {
			
			//Fetch the name of the instance, and the name of the swatch being used
			$sql = "
				SELECT name, framework, css_class
				FROM ". DB_NAME_PREFIX. "plugin_instances
				WHERE id = ". (int) $slot['instance_id'];
			$result = sqlQuery($sql);
			if ($row = sqlFetchAssoc($result)) {
				
				//If we found a plugin to display, activate it and set it up
				$slot['instance_name'] = $row['name'];
				
				
				//Set the framework for this plugin
				if ($overrideFrameworkAndCSS !== false
				 && !empty($overrideFrameworkAndCSS['framework_tab/framework'])) {
					$slot['framework'] = $overrideFrameworkAndCSS['framework_tab/framework'];
				
				} elseif (!empty($row['framework'])) {
					$slot['framework'] = $row['framework'];
				
				} else {
					$slot['framework'] = $slot['default_framework'];
				}
				
				
				//Set the CSS class for this plugin
				$baseCSSName = $slot['css_class_name'];
				
				if ($overrideFrameworkAndCSS !== false
				 && isset($overrideFrameworkAndCSS['this_css_tab/css_class'])) {
					
					switch ($overrideFrameworkAndCSS['this_css_tab/css_class']) {
						case '#default#':
							$slot['css_class'] .= ' '. $baseCSSName. '__default_style';
							break;
						
						case '#custom#':
							$slot['css_class'] .= ' '. $overrideFrameworkAndCSS['this_css_tab/css_class_custom'];
							break;
						
						default:
							$slot['css_class'] .= ' '. $overrideFrameworkAndCSS['this_css_tab/css_class'];
							break;
					}
				} else {
					if ($row['css_class']) {
						$slot['css_class'] .= ' '. $row['css_class'];
					} else {
						$slot['css_class'] .= ' '. $baseCSSName. '__default_style';
					}
				}
				
				
				//Add a CSS class for this version controller plugin, or this library plugin
				if (!empty($slot['content_id'])) {
					if ($cID !== -1) {
						$slot['css_class'] .=
							' '. $cType. '_'. $cID. '_'. $slotName.
							'_'. $baseCSSName;
					}
				} else {
					$slot['css_class'] .=
						' '. $baseCSSName.
						'_'. $slot['instance_id'];
				}
					
				
				if ($runPlugins) {
					setInstance($slot, $cID, $cType, $cVersion, $slotName, $checkForErrorPages = true, $overrideSettings);
					
					if (initPluginInstance($slot)) {
						if (!$ajaxReload && ($location = $slot['class']->checkHeaderRedirectLocation())) {
							header("Location: ". $location);
							exit;
						}
					}
				}
				
			} else {
				$module = getModuleDetails($slot['module_id']);
				
				if ($runPlugins) {
					
					//If this is a layout preview, any version controlled plugin won't have an instance id
					//and can't be displayed properly, but set it up as best we can.
					if ($cID === -1
					 && inc($className = $module['class_name'])) {
						cms_core::$slotContents[$slotName]['class'] = new $className;
						cms_core::$slotContents[$slotName]['class']->setInstance(array(
							cms_core::$cID, cms_core::$cType, cms_core::$cVersion, $slotName,
							false, false,
							$className, $module['vlp_class'],
							$module['id'],
							$module['default_framework'], $module['default_framework'],
							$module['css_class_name'],
							false, true));
					
					//Otherwise if this is a layout preview, then no instance id is an error!
					} else {
						setupNewBaseClassPlugin($slotName);
						$slot['error'] = adminPhrase('[Plugin Instance not found for the Module &quot;[[module]]&quot;]', array('module' => htmlspecialchars($module['display_name'])));
					}
				}
			}
		} else {
			$module = getModuleDetails($slot['module_id']);
			
			if ($runPlugins) {
				setupNewBaseClassPlugin($slotName);
				$slot['error'] = adminPhrase('[Selected Module &quot;[[module]]&quot; not found, not running, or has missing dependencies]', array('module' => htmlspecialchars($module['display_name'])));
			}
		}
	}
	
	public static function preSlot($slotName, $showPlaceholderMethod, $useOb = true) {
	}
	
	public static function postSlot($slotName, $showPlaceholderMethod, $useOb = true) {
	}
	
	public static function reviewDatabaseQueryForChanges(&$sql, &$ids, &$values, $table = false, $runSql = false) {
		if ($runSql) {
			sqlUpdate($sql, false, false);
			return sqlAffectedRows();
		}
	}
	

	
	//
	//	Admin functions
	//
	
	
	
	
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
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->adminBoxSaveCompleted($path, $settingGroup, $box, $fields, $values, $changes);
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($c = $this->runSubClass(__FILE__)) {
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
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function lineStorekeeperCSV($path, &$columns, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->lineStorekeeperCSV($path, $columns, $refinerName, $refinerId);
		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__, 'organizer', $path)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		} else {
			return require funIncPath(__FILE__, __FUNCTION__);
		}
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleAdminToolbarAJAX($cID, $cType, $cVersion, $ids) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}

	public static function deleteCategory($id, $recurseCount = 0) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function processDocumentRules($documentIds) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function jobPublishContent($serverTime) {
		
		$sql = "
			SELECT id, type, version
			FROM ". DB_NAME_PREFIX. "content_item_versions
			WHERE scheduled_publish_datetime <= STR_TO_DATE('". sqlEscape($serverTime). "', '%Y-%m-%d %H:%i:%s')";
		$result = sqlQuery($sql);
		
		$action = false;
		while($citem = sqlFetchAssoc($result)) {
			
			if (isDraft($citem['id'], $citem['type'], $citem['version'])) {
				// Publish marked draft items
				$adminId = getRow('content_items', 'lock_owner_id', array('id'=>$citem['id'], 'type'=>$citem['type']));
				publishContent($citem['id'], $citem['type'], $adminId);
				$action = true;
				echo adminPhrase('Published Content Item [[tag]]', array('tag' => formatTag($citem['id'], $citem['type']))), "\n";
				
				// Update scheduled time
				updateRow('content_item_versions', array('scheduled_publish_datetime'=>NULL), array('id'=>$citem['id'], 'type'=>$citem['type'], 'version'=>$citem['version']));
			}
		}
		
		if (!$action) {
			echo adminPhrase('No Content Items to Publish'), "\n";
		}
		
		return $action;
	}
	
	public static function canCreateAdditionalAdmins() {
		$limit = siteDescription('max_local_administrators');
		return !$limit || selectCount('admins', array('is_client_account' => 1, 'status' => 'active')) < $limit;
	}
	
}