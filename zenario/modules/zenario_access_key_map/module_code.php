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





//This Plugin is used to create an Access Keys Map

class zenario_access_key_map extends ze\moduleBaseClass {
	
	public static function sort($m1, $m2) {
		$n1 = strtolower($m1['name']);
		$n2 = strtolower($m2['name']);
		
		if ($n1 == $n2) {
			return strtolower($m1['accesskey']) > strtolower($m2['accesskey']) ? +1 : -1;
		} else {
			return $n1 > $n2 ? +1 : -1;
		}
	}
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	public function showSlot() {
		$cachingRestrictions = 0;
		$mergeFields = [];
		$subSections = [];
		$mergeFields['Access_Key_Map'] = $this->phrase( '_ACCESS_KEY_MAP' );
		$notFound = true;
		$menus = [];
		
		
		//Firstly, get every section/parent id that has an access key in it
		$sql = "
			SELECT DISTINCT section_id, parent_id
			FROM ". DB_PREFIX. "menu_nodes
			WHERE accesskey != ''";
		
		//Call getMenuStructure for each combination, and get every Menu Node that has an access key
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$menuArray = 
				ze\menu::getStructure(
					$cachingRestrictions,
					$sectionId = $row['section_id'],
					$currentMenuId = false,
					$parentMenuId = $row['parent_id'],
					$numLevels = 1,
					$maxLevel1MenuItems = 100,
					$language = false,
					$onlyFollowOnLinks = false,
					$onlyIncludeOnLinks = false,
					$showInvisibleMenuItems = false,
					false,
					ze\content::showUntranslatedContentItems()
				);
			
			foreach ($menuArray as &$menu) {
				if ($menu['accesskey'] != '') {
					$menus[] = $menu;
				}
			}
		}
		
		switch ($cachingRestrictions) {
			case ze\menu::privateItemsExist:
				$this->allowCaching(
					$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
				$this->clearCacheBy(
					$clearByContent = false, $clearByMenu = true, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
				break;
			case ze\menu::staticFunctionCalled:
				$this->allowCaching(false);
				break;
		}
		
		if (empty($menus)) {
			$mergeFields['No_Access_Keys_Found'] = $this->phrase( '_NO_ACCESS_KEYS_FOUND' );
			$subSections['No_Access_Keys_Section'] = true;
			$this->framework( 'Outer', $mergeFields, $subSections );
		
		} else {
			//Sort the Menu Names in order
			usort($menus, ['zenario_access_key_map', 'sort']);
			
			$accessKeys = [];
			foreach ($menus as &$menu) {
				$accessKeys[$menu['accesskey']] = $menu['name'];
			}
			
			$subSections['Access_Keys_Section'] = true;
			$subSections['Items_Section'] = [];
			foreach( $accessKeys as $accessKey => $menuName ) {
				$mergeFields['Menu_Item'] = htmlentities( $menuName, ENT_COMPAT, 'UTF-8');
				$mergeFields['Access_Key'] = strtoupper( htmlentities( $accessKey , ENT_COMPAT, 'UTF-8') );
				$subSections['Items_Section'][] = $mergeFields;
			}
			$this->framework('Outer', $mergeFields, $subSections );
		}	
		
	}
}
