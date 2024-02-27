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




class zenario_forum_list extends zenario_content_list {
	
	protected $forums = [];
	var $showScreennames = false;
	
	public function init() {
		if (zenario_content_list::init()) {
			$this->allowCaching(false);
			
			return true;
		} else {
			return false;
		}
	}
	
	//Overwite certain functionality from zenario_content_list
	function lookForContent($paginationRequired = true) {
		
		$this->showScreennames = (bool) ze::setting('user_use_screen_name');
		
		$sql =
			$this->lookForContentSelect().
			$this->lookForContentSQL().
			$this->orderContentBy();
		
		return ze\sql::select($sql);
	}
	

	protected function lookForContentSelect() {
		return
			zenario_content_list::lookForContentSelect(). ",
			f.id AS forum_id,
			f.date_updated AS forum_date_updated,
			f.updater_id AS forum_updater_id,
			f.thread_count AS forum_thread_count,
			f.post_count AS forum_post_count,
			f.locked AS forum_locked";
	}
	
	protected function lookForContentTableJoins() {
		$sql = zenario_content_list::lookForContentTableJoins();
		
		$sql .= "
			INNER JOIN ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums AS f
			   ON f.forum_content_id = c.id
			  AND f.forum_content_type = c.type";
		
		return $sql;
	}
	
	protected function lookForContentWhere() {
		$sql = "";
		
		//Only return content in the current language
		$sql .= "
		  AND c.language_id = '". ze\escape::asciiInSQL(ze::$langId). "'";
		
		//Exclude this page itself
		$sql .= "
		  AND v.tag_id != '". ze\escape::asciiInSQL($this->cType. '_'. $this->cID). "'";
		
		return $sql;
	}
	
	protected function orderContentBy() {
		$orderBy = "
			ORDER BY ";
		
		if ($this->setting('pinned_content_items') == 'prioritise_pinned') {
			$orderBy .= "v.pinned DESC, ";
		}
		
		$orderBy .= "f.ordinal";
		
		return $orderBy;
	}
	
	
	function addExtraMergeFields(&$row, &$mergeFields) {
		
		$mergeFields['Post_Count'] = $row['forum_post_count'];
		$mergeFields['Thread_Count'] = $row['forum_thread_count'];

		if ($row['forum_updater_id']) {
			$mergeFields['Date_Updated'] = ze\date::formatDateTime($row['forum_date_updated'], $this->setting('date_format'));
			
			$mergeFields['Updated_By'] = $this->getUserScreenNameLink($row['forum_updater_id']);
			
			$mergeFields['Updated_By_On'] = $this->phrase('by [[by]] on [[on]]', ['by' => $mergeFields['Updated_By'], 'on' => $mergeFields['Date_Updated']]);
		}
		
		if (!$row['forum_post_count'] || zenario_forum::markThreadCheckUserHasReadForum($row['forum_id'])) {
			$mergeFields['Status_Class'] = 'read';
		} else {
			$mergeFields['Status_Class'] = 'unread';
		}
		
		if ($row['forum_locked']) {
			$mergeFields['Status_Class'] .= ' locked';
		}
		
		return true;
	}
	
	//Get a user's screen_name, if we're showing screennames
	function getUserScreenName($userId) {
		if (!$userId) {
			return $this->phrase('Anonymous');
		} elseif ($this->showScreennames) {
			return ze\user::screenName($userId);
		} else {
			return '';
		}
	}
	
	//Get a user's screen name, and add a Storekeeper Link if in Admin mode with the correct Perms
	function getUserScreenNameLink($userId, $screenName = false, $alwaysShowLink = false) {
		if ($screenName === false) {
			$screenName = $this->getUserScreenName($userId);
		}
		
		if (!$screenName) {
			return '';
		} elseif ($userId && ($alwaysShowLink || ze\priv::check('_PRIV_VIEW_USER'))) {
			return '<a href="'. ze\link::absolute(). 'organizer.php#zenario__users/panels/users//'. $userId. '/" target="_blank">'. htmlspecialchars($screenName). '</a>';
		} else {
			return htmlspecialchars($screenName);
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				$hidden = !$values['each_item/show_featured_image'];
				$this->showHideImageOptions($fields, $values, 'each_item', $hidden);
				break;
		}
	}
}