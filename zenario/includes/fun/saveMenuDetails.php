<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

$newMenu = false;
$recalc = false;
$lastEquivId = $lastCType = $lastCItemDifferent = false;


//Look up equiv ids from content ids
if (!empty($submission['content_type'])) {
	if (!empty($submission['content_id']) && empty($submission['equiv_id'])) {
		$submission['equiv_id'] = $submission['content_id'];
		langEquivalentItem($submission['equiv_id'], $submission['content_type'], true);
	}
}

if ($menuId) {
	$submission['section_id'] = getRow('menu_nodes', 'section_id', $menuId);

} elseif (!empty($submission['section_id'])) {
	$submission['section_id'] = menuSectionId($submission['section_id']);
}

if (!$skipSectionChecks) {
	if (empty($submission['section_id'])) {
		echo adminPhrase('No section was set!');
		exit;
	
	} elseif (!checkRowExists('menu_sections', $submission['section_id'])) {
		echo adminPhrase('The Menu Section requested does not exist!');
		exit;
	}
}

if ($menuId && ($lastMenu = getRow('menu_nodes', array('target_loc', 'equiv_id', 'content_type'), $menuId))) {
	if ($lastMenu['target_loc'] == 'int' && $lastMenu['equiv_id'] && $lastMenu['content_type']) {
		$lastEquivId = $lastMenu['equiv_id'];
		$lastCType = $lastMenu['content_type'];
	}
}

//If we are linking to a content item, follow some special logic
if (isset($submission['target_loc'])) {
	if ($submission['target_loc'] == 'int' && !empty($submission['equiv_id']) && !empty($submission['content_type'])) {
		//If we're inserting a new Menu Node to a Content Item, or changing where this Menu Node links, mark it as a secondary
		//but then rely on the logic at the end to correct this to a primary if no other Node links there
		if ($submission['equiv_id'] != $lastEquivId || $submission['content_type'] != $lastCType) {
			$lastCItemDifferent = true;
			$submission['redundancy'] = 'secondary';
		}
	
	} else {
		$submission['redundancy'] = 'primary';
		$submission['hide_private_item'] = 0;
		$submission['equiv_id'] = 0;
		$submission['content_type'] = '';
		
		if ($lastEquivId && $lastCType) {
			$lastCItemDifferent = true;
		}
	}
}


if (!$menuId) {
	$newMenu = true;

} elseif (isset($submission['parentMenuID']) && isset($submission['parent_id']) && $submission['parentMenuID'] != $submission['parent_id']) {
	$recalc = true;
}

//For new Menu Nodes, or Menu Nodes that are being moved, work out a new ordinal for them
if (($newMenu || $recalc) && !isset($submission['ordinal'])) {
	$sql = "
		SELECT max(ordinal)
		FROM ". DB_NAME_PREFIX. "menu_nodes 
		WHERE section_id = ". (int) $submission['section_id']. "
		  AND parent_id = ". (int) ($submission['parent_id'] ?? false);
		
	$result = sqlSelect($sql);
	list($ordinal) = sqlFetchRow($result);
	
	$submission['ordinal'] = $ordinal? ++$ordinal : 1;
}


//Update the Menu Nodes Table
$sql = "";
foreach(getFields(DB_NAME_PREFIX, 'menu_nodes') as $field => $details) {
	if (isset($submission[$field])) {
		addFieldToSQL($sql, DB_NAME_PREFIX. 'menu_nodes', $field, $submission, $menuId, $details);
	}
}

if ($sql) {
	if ($menuId) {
		$sql .= "
			WHERE id = ". (int) $menuId;
	}
	sqlUpdate($sql);
	$newId = sqlInsertId();
	
	//Get the new menu id for new menus
	if (!$menuId) {
		$menuId = $newId;
	}
}





//Check that the primary/secondary flag is set correctly for Menu Nodes
if (isset($submission['redundancy']) && !empty($submission['equiv_id'])) {
	if ($submission['redundancy'] == 'primary') {
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "menu_nodes SET
				redundancy = 'secondary'
			WHERE equiv_id = ". (int) $submission['equiv_id']. "
			  AND content_type = '". sqlEscape($submission['content_type']). "'
			  AND id != ". (int) $menuId;
		sqlQuery($sql);
	} else {
		ensureContentItemHasPrimaryMenuItem($submission['equiv_id'], $submission['content_type']);
	}
}
if ($lastCItemDifferent && $lastEquivId && $lastCType) {
	ensureContentItemHasPrimaryMenuItem($lastEquivId, $lastCType);
}

if ($newMenu) {
	//If we're adding a new Menu Node, add entries into the menu_hierarchy table.
	//There's no need to recalculate the whole table as nothing will have changed; we just need to add the new entries in for this Menu Node
	addNewMenuItemToMenuHierarchy($submission['section_id'], $menuId, ($submission['parent_id'] ?? false));

} elseif ($recalc && $resyncIfNeeded) {
	//If this was a Menu Node being moved, recalculate the entire menu hierarchy
	if ($menu = getRow('menu_nodes', array('section_id'), $menuId)) {
		recalcMenuHierarchy($menu['section_id']);
	}
}


return $menuId;