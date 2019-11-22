<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

class zenario_pro_features extends zenario_common_features {
	
	
	
	
	
	//The Module Methods from the zenario_common_features class need to be overridden even if there is not extra functionality
	//in this case because we are inheriting from zenario_common_features instead of zenario_base_module
	//and because of that these functions if not declared here will end up calling zenario_common_features twice.
	
	public function showFile() {
		
		//...your PHP code...//
	}

	public function showImage() {
		
		//...your PHP code...//
	}
	
	
	public function fillAllAdminSlotControls(
		&$controls,
		$cID, $cType, $cVersion,
		$slotName, $containerId,
		$level, $moduleId, $instanceId, $isVersionControlled
	) {
		//...your PHP code...//
	}
	
	public function fillAdminToolbar(&$adminToolbar, $cID, $cType, $cVersion) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->fillAdminToolbar($adminToolbar, $cID, $cType, $cVersion);
		}
	}
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->preFillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(static::class)) {
			return $c->organizerPanelDownload($path, $ids, $refinerName, $refinerId);
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class)) {
					return $c->fillAdminBox($path, $settingGroup, $box, $fields, $values);
				}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class)) {
					return $c->formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class)) {
					return $c->validateAdminBox($path, $settingGroup, $box, $fields, $values, $changes, $saving);
				}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class)) {
					return $c->saveAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		switch ($path) {
			default:
				if ($c = $this->runSubClass(static::class)) {
					return $c->adminBoxDownload($path, $settingGroup, $box, $fields, $values, $changes);
				}
		}
	}
	
	
	
	
	
	public function pagSmart($currentPage, &$pages, &$html) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = false, $showFirstLast = false, $alwaysShowNextPrev = false);
	}
	
	public function pagSmartWithNPIfNeeded($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = false, $links, $extraAttributes);
	}
	
	public function pagSmartWithNP($currentPage, &$pages, &$html, $links = [], $extraAttributes = []) {
		$this->pageNumbers($currentPage, $pages, $html, 'Smart', $showNextPrev = true, $showFirstLast = false, $alwaysShowNextPrev = true, $links, $extraAttributes);
	}
	
	protected function smartPageNumbers($currentPos, $count, $showFirstLast, &$pagesPos, &$pages, &$html, $currentPage, $prevPage, $nextPage, $links = [], $extraAttributes = []) {
		//Have a set list of positions that will be displayed, if there
		$positions1 = [
				-999999,
				-100000, -70000, -40000, -20000,
				-10000, -7000, -4000, -2000,
				-1000, -700, -400, -200,
				-100, -70, -40, -20,
				-10, -7, -4, -2,
				-1, 0,
				1, 2, 4, 7,
				10, 20, 40, 70,
				100, 200, 400, 700,
				1000, 2000, 4000, 7000,
				10000, 20000, 40000, 70000,
				100000,
				999999
			];
		$positions2 = [];
		
		//Check if each is there, and include it if so
		foreach ($positions1 as $rel) {
			//Check if the set position is out of range, and replace it with the first/last page in range if needed
			$pos = $currentPos + $rel;
			if ($pos < 0) {
				if ($showFirstLast) {
					continue;
				}
				$pos = 0;
			} elseif ($pos >= $count) {
				if ($showFirstLast) {
					continue;
				}
				$pos = $count-1;
			} else {
				//Otherwise if the numbers are in range then round numbers, depending on how far away they are from the current page
				foreach ([100000, 10000, 1000, 100, 10] as $round) {
					if ($rel < -$round || $round < $rel) {
						$pos = $pos - ($currentPos % $round) - 1;
						break;
					}
				}
				
				if ($pos < 0) {
					$pos = 0;
				} elseif ($pos >= $count) {
					$pos = $count-1;
				}
			}
			
			$positions2[$pos] = true;
		}
		
		foreach ($positions2 as $pos => $dummy) {
			$page = $pagesPos[$pos];
			$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, $css = 'pag_page', $links, $extraAttributes);
		}
	}
	
	
	
	
	
	
	
	
	//
	//	Admin functions
	//
	
	
	
	
	function handleAJAX() {
		
		if ($_POST['getBottomLeftInfo'] ?? false) {
		
			$compressed = ze::setting('compress_web_pages')? ze\admin::phrase('Compressed') : ze\admin::phrase('Not Compressed');
		
			if (ze::setting('caching_enabled')
			&& ze::setting('cache_css_js_wrappers')
			&& ze::setting('css_wrappers')
			&& (ze::setting('css_wrappers') == 'on' || ze::setting('css_wrappers') == 'visitors_only')) {
				echo '1';
			}
			
			
			$wrappers = ze\admin::phrase('On');
			switch (ze::setting('css_wrappers')) {
				case 'visitors_only':
					$wrappers = ze\admin::phrase('On for visitors only');
				case 'on':
					$wrappers .= ', ';
					$wrappers .= $compressed;
					$wrappers .= ', ';
					$wrappers .= ze::setting('caching_enabled') && ze::setting('cache_css_js_wrappers')?
									ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached');
					break;
				
				default:
					$wrappers = ze\admin::phrase('Off');
			}
									
		
			echo
			'~',
			'<h3>',
				ze\admin::phrase('Optimisation'),
			'</h3>',
			'<p>',
				ze\admin::phrase('Web Pages:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_web_pages')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('Plugins:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_plugins')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('AJAX and RSS:'),
				' ',
				$compressed,
				', ',
				ze::setting('caching_enabled') && ze::setting('cache_ajax')? ze\admin::phrase('Cached') : ze\admin::phrase('Not Cached'),
			'</p>',
			'<p>',
				ze\admin::phrase('CSS File Wrappers:'),
				' ',
				$wrappers,
			'</p>',
			'<p>',
				ze\admin::phrase('Other Files:'),
				' ',
				'is_htaccess_working',
			'</p>',
			'<p>',
				ze\admin::phrase('Cookie-free Domain:'),
				' ',
				ze::setting('use_cookie_free_domain') && ze::setting('cookie_free_domain')?
				htmlspecialchars('http://'. ze::setting('cookie_free_domain'). SUBDIRECTORY)
				:	ze\admin::phrase('Not Used'),
			'</p>',
			'~';
		
			//Get the current server time
			if (ze\server::isWindows() || !ze\server::execEnabled()) {
				echo date('H~i~s');
		
			} else {
				echo trim(exec('date +"%H~%M~%S"'));
		
				//Check if the scheduled task manager is running
				if (!ze\module::inc('zenario_scheduled_task_manager')) {
					echo '~~', ze\admin::phrase('The Scheduled Tasks Manager is not installed.');
					return;
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager is installed, but the master switch is not enabled.');
		
				} elseif (!zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true)) {
					echo '~jobs_not_running~', ze\admin::phrase('The Scheduled Tasks Manager is installed, but not correctly configured in your crontab');
		
				} else {
					echo '~jobs_running~', ze\admin::phrase('The Scheduled Tasks Manager is running');
				}
		
				if (ze\priv::check('_PRIV_VIEW_SCHEDULED_TASK')) {
					echo '~zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				}
			}
		
		}
		
		zenario_common_features::handleAJAX();
	}
	
	
	
	
	
	var $categoryHierarchyOutput = "";
	var $categoryChildren = [];
	var $categoryAncestors = [];
	
	
	public function categoryHasChild ($id) {
		$sql = "SELECT id
				FROM " . DB_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getCategoryChildren ($id, $recurseCount = 0) {
		$recurseCount++;
	
		$sql = "SELECT id
				FROM " . DB_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
	
		$result = ze\sql::select($sql);
	
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchRow($result)) {
				$this->categoryChildren[] = $row[0];
	
				if ($recurseCount<=10) {
					$this->getCategoryChildren($row[0],$recurseCount);
				}
			}
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	public static function eventContentDeleted($cID, $cType, $cVersion) {
		if (!ze\row::exists('content_item_versions', ['id' => $cID, 'type' => $cType])) {
			ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
		}
	}
	
	public static function eventContentTrashed($cID, $cType) {
		ze\row::delete('spare_aliases', ['content_id' => $cID, 'content_type' => $cType]);
	}
	
}

