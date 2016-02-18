<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	FROM ". DB_NAME_PREFIX. "content_items AS c
	INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
	   ON v.id = c.id
	  AND v.type = c.type
	  AND v.version = c.admin_version
	WHERE c.tag_id IN (". inEscape($tagIds). ")
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
if ($menuTarget) {
	//If this is a numeric id, look up its details and move next to that Menu Node
	if (is_numeric($menuTarget) && $neighbour = getMenuNodeDetails($menuTarget)) {
		$newParentId = $neighbour['parent_id'];
		$newSectionId = $neighbour['section_id'];
		$newNeighbourId = $menuTarget;
	
	} else {
		//Check for a menu position, in the format CONCAT(section_id, '_', menu_id, '_', is_dummy_child)
		$menu_position = explode('_', $menuTarget);
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