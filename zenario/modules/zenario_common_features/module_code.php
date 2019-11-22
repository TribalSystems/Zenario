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
}