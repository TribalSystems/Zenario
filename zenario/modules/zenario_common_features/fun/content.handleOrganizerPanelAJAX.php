<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


if (post('mass_add_to_menu') && checkPriv('_PRIV_ADD_MENU_ITEM')) {
	/*
	//This code would add Menu Text for every translation of a Content Item
	$sql = "
		SELECT
			'__tmp__' AS section_id,
			e.language_id,
			IF(v.title != '', v.title, IF(c.alias != '', c.alias, c.tag_id)) AS name,
			'int' AS 'target_loc',
			e.equiv_id,
			e.type AS content_type,
			'secondary' AS redundancy
		FROM ". DB_NAME_PREFIX. "content AS c
		INNER JOIN ". DB_NAME_PREFIX. "content AS e
		   ON e.equiv_id = c.equiv_id
		  AND e.type = c.type
		INNER JOIN ". DB_NAME_PREFIX. "versions AS v
		   ON v.id = e.id
		  AND v.type = e.type
		  AND v.version = e.admin_version
		WHERE c.tag_id IN ('". str_replace(',', "', '", preg_replace('/[^\w,]/', '', $ids)). "')
		  AND e.status NOT IN ('hidden','trashed','deleted')
		ORDER BY
			e.type,
			e.equiv_id,
			e.language_id = '". sqlEscape(setting('default_language')). "' DESC,
			e.language_id,
			v.title";
	
	$menuId = false;
	$lastEquivTag = false;
	$menuIds = array();
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		if ($lastEquivTag != $row['content_type']. '_'. $row['equiv_id']) {
			$lastEquivTag = $row['content_type']. '_'. $row['equiv_id'];
			$menuId = false;
		}
		
		$menuId = saveMenuDetails($row, $menuId, $resyncIfNeeded = false, $skipSectionChecks = true);
		saveMenuText($menuId, $row['language_id'], $row);
		$menuIds[$menuId] = $menuId;
	}
	*/
	
	//This code just adds one content item, unless multiple in the same were selected at once
	$sql = "
		SELECT
			'__tmp__' AS section_id,
			c.language_id,
			IF(v.title != '', v.title, IF(c.alias != '', c.alias, c.tag_id)) AS name,
			'int' AS 'target_loc',
			c.equiv_id,
			c.type AS content_type,
			'secondary' AS redundancy
		FROM ". DB_NAME_PREFIX. "content AS c
		INNER JOIN ". DB_NAME_PREFIX. "versions AS v
		   ON v.id = c.id
		  AND v.type = c.type
		  AND v.version = c.admin_version
		WHERE c.tag_id IN ('". str_replace(',', "', '", preg_replace('/[^\w,]/', '', $ids)). "')
		ORDER BY
			c.type,
			c.equiv_id,
			c.language_id = '". sqlEscape(setting('default_language')). "' DESC,
			c.language_id,
			v.title";
	
	$menuId = false;
	$lastEquivTag = false;
	$menuIds = array();
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		if ($lastEquivTag != $row['content_type']. '_'. $row['equiv_id']) {
			$lastEquivTag = $row['content_type']. '_'. $row['equiv_id'];
			$menuId = false;
		}
		
		$menuId = saveMenuDetails($row, $menuId, $resyncIfNeeded = false, $skipSectionChecks = true);
		saveMenuText($menuId, $row['language_id'], $row);
		$menuIds[$menuId] = $menuId;
	}
	define('TEST', $menuId);
	
	//By default, just move to the top level
	$newParentId = 0;
	$newSectionId = post('child__refiner__section');
	$newNeighbourId = 0;
	
	//Look for a menu node in the request
	if ($ids2) {
		//If this is a numeric id, look up its details and move next to that Menu Node
		if (is_numeric($ids2) && $neighbour = getMenuNodeDetails($ids2)) {
			$newParentId = $neighbour['parent_id'];
			$newSectionId = $neighbour['section_id'];
			$newNeighbourId = $ids2;
		
		} else {
			//Check for a menu position, in the format CONCAT(section_id, '_', menu_id, '_', is_dummy_child)
			$menu_position = explode('_', $ids2);
			if (count($menu_position) == 3) {
				
				if ($menu_position[2]) {
					//Move the menu node to where a dummy placeholder is
					$newSectionId = $menu_position[0];
					$newParentId = $menu_position[1];
				
				} elseif ($menu_position[1]) {
					//Move the menu node to where another menu node is
					$newSectionId = $menu_position[0];
					$newParentId = getMenuParent($menu_position[1]);
					$newNeighbourId = $menu_position[1];
				}
			}
		}
	}
	
	moveMenuNode(
		$menuIds,
		$newSectionId,
		$newParentId,
		$newNeighbourId);




//} elseif (post('add_to_menu') && checkPriv('_PRIV_ADD_MENU_ITEM')) {
//	if ($menuDetails = getMenuNodeDetails($ids2)) {
//		if ($menuDetails['target_loc'] == 'none') {
//			$cID = $cType = false;
//			if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
//				$submission = array(
//					'content_id' => $cID,
//					'target_loc' => 'int',
//					'content_type' => $cType);
//				saveMenuDetails($submission, $ids2);
//			}
//		} else {
//			echo adminPhrase('Please select an unlinked Menu Node.');
//		}
//	}

} elseif (post('hide')) {
	foreach (explode(',', $ids) as $id) {
		$cID = $cType = false;
		if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
			if (allowHide($cID, $cType) && checkPriv('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
				hideContent($cID, $cType);
			}
		}
	}

} elseif (post('trash')) {
	foreach (explode(',', $ids) as $id) {
		$cID = $cType = false;
		if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
			if (allowTrash($cID, $cType) && checkPriv('_PRIV_TRASH_CONTENT_ITEM', $cID, $cType)) {
				trashContent($cID, $cType);
			}
		}
	}

} elseif (post('delete')) {
	foreach (explode(',', $ids) as $id) {
		$cID = $cType = false;
		if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
			if (allowDelete($cID, $cType) && checkPriv('_PRIV_DELETE_DRAFT', $cID, $cType)) {
				deleteDraft($cID, $cType);
			}
		}
	}

} elseif (post('delete_trashed_items') && checkPriv('_PRIV_TRASH_CONTENT_ITEM')) {
	$result = getRows('content', array('id', 'type'), array('status' => 'trashed'));
	while ($content = sqlFetchAssoc($result)) {
		deleteContentItem($content['id'], $content['type']);
	}

} elseif (post('lock')) {
	foreach (explode(',', $ids) as $id) {
		if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			if (checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType)) {
				updateRow('content', array('lock_owner_id' => session('admin_userid'), 'locked_datetime' => now()), array('id' => $cID, 'type' => $cType));
			}
		}
	}
// Set unlock ajax message
} elseif (get('unlock')) {
	foreach (explode(',', $ids) as $id) {
	if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			$contentInfo = getRow('content', array('admin_version', 'lock_owner_id'), array('id'=>$cID, 'type'=>$cType));
			$cVersion = $contentInfo['admin_version'];
			$adminDetails = getAdminDetails($contentInfo['lock_owner_id']);
			echo 'Are you sure that you wish to ';
			if (!checkPriv(false, $cID, $cType)) {
				echo 'force-';
			}
			echo 'unlock on this content item? ';
			if ($date = getRow('versions', 'scheduled_publish_datetime', array('id'=>$cID,'type'=>$cType,'version'=>$cVersion))) {
				echo 'It is scheduled to be published by '.$adminDetails['first_name'].' '.$adminDetails['last_name'].' on '. formatDateTimeNicely($date, 'vis_date_format_long');
			} else {
				echo 'Any administrator who has authoring permission will be able to make changes to it.';
			}
		}
	}
// Unlock a content item
} elseif (post('unlock')) {
	foreach (explode(',', $ids) as $id) {
	if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			// Unlock the item & remove scheduled publication
			if (checkPriv('_PRIV_CANCEL_CHECKOUT') || checkPriv(false, $cID, $cType)) {
				$cVersion = getRow('content', 'admin_version', array('id'=>$cID, 'type'=>$cType));
				updateRow('versions', array('scheduled_publish_datetime' => null), array('id'=>$cID,'type'=>$cType,'version'=>$cVersion));
				updateRow('content', array('lock_owner_id' => 0, 'locked_datetime' => null), array('id' => $cID, 'type' => $cType));
			}
		}
	}
	
} elseif ((post('create_draft') || post('unhide')) && checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
	foreach (explode(',', $ids) as $id) {
		if ($content = getRow('content', array('id', 'type', 'status', 'admin_version', 'visitor_version'), array('tag_id' => $id))) {
			
			if (post('create_draft') && isDraft($content['status'])) {
				continue;
			} elseif (post('unhide') && $content['status'] != 'hidden') {
				continue;
			}
			
			$cVersionTo = false;
			createDraft($content['id'], $content['id'], $content['type'], $cVersionTo, ifNull(post('cVersion'), $content['admin_version']));
			
			if (get('method_call') == 'handleAdminToolbarAJAX') {
				$_SESSION['last_item'] = $content['type']. '_'. $content['id'];
				
				if (request('switch_to_edit_mode')) {
					$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit';
				}
			}
		}
	}

} elseif (post('create_draft_by_copying') && checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
	$sourceCID = $sourceCType = false;
	if (getCIDAndCTypeFromTagId($sourceCID, $sourceCType, $ids2)
	 && ($content = getRow('content', array('id', 'type', 'status'), array('tag_id' => $ids)))) {
		$hasDraft =
			$content['status'] == 'first_draft'
		 || $content['status'] == 'published_with_draft'
		 || $content['status'] == 'hidden_with_draft'
		 || $content['status'] == 'trashed_with_draft';
		
		if (!$hasDraft || checkPriv('_PRIV_DELETE_DRAFT', $content['id'], $content['type'])) {
			if ($hasDraft) {
				deleteDraft($content['id'], $content['type'], false);
			}
			
			$cVersionTo = false;
			createDraft($content['id'], $sourceCID, $content['type'], $cVersionTo);
		}
	}

//
// Translation functionality
//

} elseif (post('remove')) {
	foreach (explode(',', $ids) as $id) {
		$cID = $cType = false;
		if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
			if (allowRemoveEquivalence($cID, $cType)) {
				removeEquivalence($cID, $cType);
			}
		}
	}

} elseif (post('declare')) {
	$cID1 = $cType = false;
	$cID2 = $cType2 = false;
	
	getCIDAndCTypeFromTagId($cID1, $cType, $ids);
	getCIDAndCTypeFromTagId($cID2, $cType2, ifNull(post('id2'), post('refiner__zenario_trans__chained_in_link')));
	
	if ($cID1 && $cID2 && $cType) {
		if ($cType != $cType2) {
			echo adminPhrase('Please ensure that both Content Items are of the same Content Type.');
		
		} elseif (($lang = getContentLang($cID1, $cType)) == getContentLang($cID2, $cType)) {
			echo adminPhrase('Please ensure that both Content Items are in a different Language.');
		
		} elseif (!recordEquivalence($cID1, $cID2, $cType, $onlyValidate = true)) {
			echo adminPhrase('A translation chain cannot contain more than one Content Item of the same Language. Please select a different Item.');
		
		} else {
			recordEquivalence($cID1, $cID2, $cType);
		}
	}
}

return false;