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

function getCIDAndCTypeFromTagId(&$cID, &$cType, $tagId) {
	if ($tagId
	 && ($tagId = explode('_', trim($tagId), 2))
	 && (!empty($tagId[1]))
	 && (!preg_match('/[^a-zA-Z]/', $tagId[0]))
	 && (!preg_match('/[^0-9]/', $tagId[1]))
	 && ($cType = $tagId[0])
	 && ($cID = (int) $tagId[1])) {
		return true;
	} else {
		return $cID = $cType = false;
	}
}

function getEquivIdAndCTypeFromTagId(&$equivId, &$cType, $tagId) {
	if ((getCIDAndCTypeFromTagId($equivId, $cType, $tagId))
	 && ($equivId = equivId($equivId, $cType))) {
		return true;
	} else {
		return $equivId = $cType = false;
	}
}

function contentVersion($cID, $cType) {
	return getRow('content_items', checkPriv()? 'admin_version' : 'visitor_version', array('id' => $cID, 'type' => $cType));
}

cms_core::$whitelist[] = 'categoryPublicName';
function categoryPublicName($catId, $languageId = false) {
	return phrase('_CATEGORY_'. $catId, false, '', $languageId);
}

//Get an item's title
//	function getItemTitle($cID, $cType, $cVersion = false) {}

//Check what Language an Item is in
//	function getContentLang($cID, $cType, $cVersion = false) {}

//Get an item's description
//	function getItemDescription($cID, $cType, $cVersion) {}

function isDraft($statusOrCID, $cType = false, $cVersion = false) {
	
	if (is_numeric($statusOrCID) && $cType) {
		if (!($content = getRow('content_items', array('admin_version', 'status'), array('id' => $statusOrCID, 'type' => $cType)))
		 || ($cVersion && $cVersion != $content['admin_version'])) {
			return false;
		}
		
		$statusOrCID = $content['status'];
	}
	
	return $statusOrCID == 'first_draft'
		|| $statusOrCID == 'published_with_draft'
		|| $statusOrCID == 'hidden_with_draft'
		|| $statusOrCID == 'trashed_with_draft';
}

function isPublished($statusOrCID, $cType = false, $cVersion = false) {
	
	if (is_numeric($statusOrCID) && $cType) {
		if (!($content = getRow('content_items', array('visitor_version', 'status'), array('id' => $statusOrCID, 'type' => $cType)))
		 || ($cVersion && $cVersion != $content['visitor_version'])) {
			return false;
		}
		
		$statusOrCID = $content['status'];
	}
	
	return $statusOrCID == 'published'
		|| $statusOrCID == 'published_with_draft';
}

//Get an item's description
//	function isSpecialPage($cID, $cType) {}

cms_core::$whitelist[] = 'langEquivalentItem';
//	function langEquivalentItem(&$cID, &$cType, $langId = false) {}

cms_core::$whitelist[] = 'phrase';
//	function phrase($code, $replace = array()) {}

cms_core::$whitelist[] = 'nphrase';
//	function nphrase($text, $pluralText = false, $n = 1, $replace = array()) {}

//Automatically generate SQL to search through Content, for example for a content list
//A bit of a techy function so we've included the full code here, so you can see exactly what it does
function sqlToSearchContentTable($hidePrivateItems = true, $onlyShow = false, $extraJoinSQL = '', $includeSpecialPages = false) {


	$sql = "
		FROM ". DB_NAME_PREFIX. "content_item_versions AS v
		INNER JOIN ". DB_NAME_PREFIX. "content_items AS c
		   ON v.id = c.id
		  AND v.type = c.type
		INNER JOIN ". DB_NAME_PREFIX. "translation_chains AS tc
		   ON c.equiv_id = tc.equiv_id
		  AND c.type = tc.type";
	
	if (checkPriv()) {
		$sql .= "
		  AND v.version = c.admin_version
		  AND c.status IN ('first_draft','published_with_draft','hidden_with_draft','trashed_with_draft','published')";
	} else {
		$sql .= "
		  AND v.version = c.visitor_version";
	}
	
	$sql .= "
		". $extraJoinSQL;
	
	
	$userId = userId();
	
	//Filter by whether the current viewer can see each item
	if (checkPriv()) {
		//Show Admins everything, even including private drafts
		$sql .= "
		WHERE TRUE";
	
	} elseif (!$hidePrivateItems) {
		//If show_private_items is enabled, show all items
		$sql .= "
		WHERE TRUE";
		
	} elseif (!$userId && $onlyShow == 'private') {
		//Private items can only be seen by logged in users...
		$sql .= "
		WHERE FALSE";
		  
	} elseif (!$userId || $onlyShow == 'public') {
		//If the visitor is not logged in, only show public items
		$sql .= "
		WHERE tc.privacy IN ('public', 'logged_out')";
	
	} else {
		//If the visitor is logged in, check which items they can see
		
		$groupsList = "FALSE";
		foreach (getUserGroups($userId) as $groupId => $groupName) {
			$sql .= "
				LEFT JOIN ". DB_NAME_PREFIX. "group_link AS gcl". $groupId. "
				   ON gcl". $groupId. ".link_from = 'chain'
				  AND gcl". $groupId. ".link_from_id = tc.equiv_id
				  AND gcl". $groupId. ".link_from_char = tc.type
				  AND gcl". $groupId. ".link_to = 'group'
				  AND gcl". $groupId. ".link_to_id = ". $groupId;
			
			if ($groupsList == "FALSE") {
				$groupsList = "";
			} else {
				$groupsList .= " OR ";
			}
			
			$groupsList .= "gcl". $groupId. ".link_to_id IS NOT NULL";
		}
		
		$rolesList = "FALSE";
		if ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = getModulePrefix('zenario_organization_manager')) {
			foreach (sqlFetchValues("
				SELECT DISTINCT role_id
				FROM ". DB_NAME_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link
				WHERE user_id = ". (int) $userId
			) as $roleId) {
				$sql .= "
					LEFT JOIN ". DB_NAME_PREFIX. "group_link AS rcl". $roleId. "
					   ON rcl". $roleId. ".link_from = 'chain'
					  AND rcl". $roleId. ".link_from_id = tc.equiv_id
					  AND rcl". $roleId. ".link_from_char = tc.type
					  AND rcl". $roleId. ".link_to = 'role'
					  AND rcl". $roleId. ".link_to_id = ". $roleId;
			
				if ($rolesList == "FALSE") {
					$rolesList = "";
				} else {
					$rolesList .= " OR ";
				}
			
				$rolesList .= "rcl". $roleId. ".link_to_id IS NOT NULL";
			}
		}
		
		$sql .= "
		WHERE IF (tc.privacy = 'group_members',
			". $groupsList. ",
			IF (tc.privacy = 'with_role',
				". $rolesList. ",
				tc.privacy IN ('public', 'logged_in')
			)
		)";
	}
	
	if ($onlyShow == 'public') {
		$sql .= "
		  AND tc.privacy IN ('public', 'logged_out')";
	
	} elseif ($onlyShow == 'private') {
		$sql .= "
		  AND tc.privacy IN ('logged_in', 'group_members', 'with_role', 'in_smart_group', 'logged_in_not_in_smart_group')";
	}
	
	//Ensure that special pages are not included in the search results
	if (!$includeSpecialPages) {
		$sql .= "
			  AND c.tag_id NOT IN ('". implode("', '", array_map('sqlEscape', cms_core::$specialPages)). "')";
	}

	
	return $sql;
}