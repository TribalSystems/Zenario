<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


$numMoves = 0;
$idsList = '';
$sectionId = false;
$sectionIds = array();
$menuNodes = array();
$newNeighbour = false;
$newSectionId = menuSectionId($newSectionId);

if (!is_array($ids)) {
	$ids = explodeAndTrim($ids);
}

//If a specific node was picked, move the selected nodes to that ordinal
if ($newNeighbourId) {
	$newNeighbour = getRow('menu_nodes', 'ordinal', $newNeighbourId);
}

if ($newParentId && ($parentDetails = getMenuNodeDetails($newParentId))) {
	
	//Move one or more Menu Nodes under a Parent Menu Node
	foreach ($ids as $id) {
		if ($id == $parentDetails['id']) {
			echo adminPhrase('A Menu Node cannot be its own Parent. Please choose a different Menu Node to be the Parent.');
			exit;
		
		} elseif (isMenuItemAncestor($parentDetails['id'], $id)) {
			echo adminPhrase('A Menu Node cannot be both the Child and the Parent of another Menu Node. Please choose a different Menu Node to be the Parent.');
			exit;
		}
	}
	
	foreach ($ids as $id) {
		if ($menuDetails = getRow('menu_nodes', array('section_id', 'parent_id', 'ordinal'), $id)) {
			$menuNodes[$id] = $menuDetails;
			$idsList .= ($idsList? ',' : ''). (int) $id;
			
			//Remove the ordinal, to be fixed later
			updateRow('menu_nodes', array('ordinal' => 0), $id);
		}
	}
	
	foreach ($menuNodes as $id => $menuDetails) {
		
		//If this is a different section, move the Menu Node and its children to that section.
		//(This will mess up the menu_hierarchy, but that will be fixed later.)
		if ($menuDetails['section_id'] != ($sectionId = $parentDetails['section_id'])) {
			$sql = "
				UPDATE ". DB_NAME_PREFIX. "menu_hierarchy AS h
				INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m
				   ON m.id = h.child_id
				  SET m.section_id = ". (int) $parentDetails['section_id']. ",
					  h.section_id = ". (int) $parentDetails['section_id']. "
				WHERE h.ancestor_id = ". (int) $id;
			sqlQuery($sql);
		
			$sectionIds[$menuDetails['section_id']] = true;
		}
		
		$submission = array(
			'parent_id' => $newParentId,
			'parentMenuID' => -1);
		
		//If there was a specific ordinal chosen, move there
		if ($newNeighbour !== false) {
			$submission['ordinal'] = $newNeighbour + $numMoves++;
		}
		
		saveMenuDetails($submission, $id, false);
	}

} else {
	foreach ($ids as $id) {
		if ($menuDetails = getRow('menu_nodes', array('section_id', 'parent_id', 'ordinal'), $id)) {
			$menuNodes[$id] = $menuDetails;
			$idsList .= ($idsList? ',' : ''). (int) $id;
			
			//Remove the ordinal, to be fixed later
			updateRow('menu_nodes', array('ordinal' => 0), $id);
		}
	}
	
	//Move one or more Menu Nodes to the Top Level
	foreach ($menuNodes as $id => $menuDetails) {
		
		if ($menuDetails['section_id'] != ($sectionId = $newSectionId)) {
			
			//If this is a different section, move the Menu Node and its children to that section.
			//(This will mess up the menu_hierarchy, but that will be fixed later.)
			$sql = "
				UPDATE ". DB_NAME_PREFIX. "menu_hierarchy AS h
				INNER JOIN ". DB_NAME_PREFIX. "menu_nodes AS m
				   ON m.id = h.child_id
				  SET m.section_id = ". (int) $newSectionId. ",
					  h.section_id = ". (int) $newSectionId. "
				WHERE h.ancestor_id = ". (int) $id;
			sqlQuery($sql);
			
			$sectionIds[$menuDetails['section_id']] = true;
		}
		
		$submission = array(
			'parent_id' => 0,
			'parentMenuID' => -1);
		
		//If there was a specific ordinal chosen, move there
		if ($newNeighbour !== false) {
			$submission['ordinal'] = $newNeighbour + $numMoves++;
		}
		
		saveMenuDetails($submission, $id, false);
	}
}

//If there was a specific ordinal chosen, we'll need to bump up the ordinals of the existing Menu Node(s) after that ordinal
if ($newNeighbour !== false && $numMoves && $idsList) {
	$sql = "
		UPDATE ". DB_NAME_PREFIX. "menu_nodes
		SET ordinal = ordinal + ". (int) $numMoves. "
		WHERE section_id = ". (int) $sectionId. "
		  AND parent_id = ". (int) $newParentId. "
		  AND id NOT IN (". $idsList. ")
		  AND ordinal >= ". (int) $newNeighbour;
	sqlQuery($sql);
}

//Renumber the ordinal of all the sections we've just moved from
$renumbers = array();
foreach ($menuNodes as $id => $menuDetails) {
	if (!isset($renumbers[$menuDetails['section_id']. '_'. $menuDetails['parent_id']])) {
		
		$result = getRows('menu_nodes', array('id', 'ordinal'), array('section_id' => $menuDetails['section_id'], 'parent_id' => $menuDetails['parent_id']), 'ordinal');
		$o = 0;
		while ($row = sqlFetchAssoc($result)) {
			if ($row['ordinal'] != ++$o) {
				setRow('menu_nodes', array('ordinal' => $o), $row['id']);
			}
		}
		
		$renumbers[$menuDetails['section_id']. '_'. $menuDetails['parent_id']] = true;
	}
}


$sectionIds[$sectionId] = true;

//Delete and recalculate the menu hierarchy for every section that has been effected
foreach ($sectionIds as $sectionId => $dummy) {
	deleteRow('menu_hierarchy', array('section_id' => $sectionId));
}

foreach ($sectionIds as $sectionId => $dummy) {
	recalcMenuHierarchy($sectionId);
}