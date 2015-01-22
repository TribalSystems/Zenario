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


if (get('quick_create') && (
		(get('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
	 || (!get('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
)) {
	$cID = $cType = false;
	$defaultName = '';
	if (get('parent__id') && getCIDAndCTypeFromTagId($cID, $cType, get('parent__id'))) {
		$defaultName = getItemTitle($cID, $cType);
	
	} elseif (get('parent__cID') && get('parent__cType')) {
		$defaultName = getItemTitle(get('parent__cID'), get('parent__cType'));
	}
	
	echo
		'<p>', 
			adminPhrase('Create a new Menu Node.'), 
		'</p><p>',
			adminPhrase('Name:'),
			' <input type="text" id="quick_create_name" name="quick_create_name" value="', htmlspecialchars($defaultName), '"/>',
		'</p>';

} elseif (post('quick_create') && (
			(post('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
		 || (!post('parentMenuID') && checkPriv('_PRIV_ADD_MENU_ITEM'))
)) {
	
	if (!post('quick_create_name')) {
		echo adminPhrase('Please enter a name for your Menu Node.');
		return false;
	
	} else {
		$submission = array(
			'name' => post('quick_create_name'),
			'section_id' => post('sectionId'),
			'content_id' => 0,
			'target_loc' => 'none',
			'content_type' => '',
			'parent_id' => (int) post('parentMenuID'));
		
		 $menuId = saveMenuDetails($submission, post('languageId'));
		 saveMenuText($menuId, post('languageId'), $submission);
		 return $menuId;
	}
	
//Unlink a Menu Node from its Content Item
} elseif (post('detach') && checkPriv('_PRIV_EDIT_MENU_ITEM')) {
	
	$submission = array(
		'target_loc' => 'none');
	
	saveMenuDetails($submission, post('mID'));
	ensureContentItemHasPrimaryMenuItem(equivId(post('cID'), post('cType')), post('cType'));
	
//Move one or more Menu Nodes to a different parent and/or the top level
} elseif (post('move') && checkPriv('_PRIV_EDIT_MENU_ITEM')) {
	
	//By default, just move to the top level
	$languageId = post('languageId');
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
		$ids,
		$newSectionId,
		$newParentId,
		$newNeighbourId,
		$languageId);
	
	//Go to the location that we've just moved to, if this is Storekeeper Quick
	//if (request('parent__cID') && $ids) {
		//echo '<!--Go_To_Storekeeper_Panel:', getMenuItemStorekeeperDeepLink($ids, ifNull(request('languageId'), request('refiner__language'))), '-->';
	//}
	

} elseif (post('remove') && checkPriv('_PRIV_DELETE_MENU_ITEM') && request('languageId') != setting('default_language')) {
	foreach (explode(',', $ids) as $id) {
		//Only remove translation if another translation still exists
		if (($result = getRows('menu_text', 'menu_id', array('menu_id' => $id)))
		 && (sqlFetchRow($result))
		 && (sqlFetchRow($result))) {
			removeMenuText($id, request('languageId'));
		}
	}

} elseif (post('delete') && checkPriv('_PRIV_DELETE_MENU_ITEM')) {
	foreach (explode(',', $ids) as $id) {
		deleteMenuNode($id);
	}

//Move or reorder Menu Nodes
} elseif ((post('reorder') || post('hierarchy')) && checkPriv('_PRIV_REORDER_MENU_ITEM')) {
	$sectionIds = array();
	
	//Loop through each moved Menu Node
	foreach (explode(',', $ids) as $id) {
		//Look up the current section, parent and ordinal
		if ($menuNode = getRow('menu_nodes', array('section_id', 'parent_id', 'ordinal'), $id)) {
			$cols = array();
			
			//Update the ordinal if it is different
			if (isset($_POST['ordinal__'. $id]) && $_POST['ordinal__'. $id] != $menuNode['ordinal']) {
				$cols['ordinal'] = $_POST['ordinal__'. $id];
			}
			
			//Update the parent id if it is different, and remember that we've done this
			if (isset($_POST['parent_id__'. $id]) && $_POST['parent_id__'. $id] != $menuNode['parent_id']) {
				$cols['parent_id'] = $_POST['parent_id__'. $id];
				$sectionIds[$menuNode['section_id']] = true;
			}
			updateRow('menu_nodes', $cols, $id);
		}
	}
	
	//Recalculate the Menu Hierarchy for any Menu Sections where parent ids have changed
	foreach ($sectionIds as $id => $dummy) {
		recalcMenuHierarchy($id);
	}
}

return false;