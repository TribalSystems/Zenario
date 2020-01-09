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

namespace ze;

class menuAdm {



	//Formerly "getMenuItemStorekeeperDeepLink()"
	public static function organizerLink($menuId, $langId = false, $sectionId = false) {
		if ($langId === false) {
			$langId = \ze\content::currentLangId();
	
		} elseif ($langId === true) {
			$langId = \ze::$defaultLang;
		}
	
		if (!$sectionId && $menuId) {
			$menuDetails = \ze\menu::details($menuId);
			$sectionId = $menuDetails['section_id'];
		}
	
		//Build up a link to the current parent in Organizer
		$path = 'zenario__menu/panels/by_language/item//'. $langId. '//item//'. $sectionId. '//';
	
		if ($menuId) {
			$path .= $menuId;
		}
	
		return $path;
	}
	
	public static function cssClass($menuNode) {
		$internalTarget = $menuNode['target_loc'] == 'int' && $menuNode['equiv_id'];
		
		if ($internalTarget) {
			if ($menuNode['redundancy'] == 'unique') {
				$cssClass = 'zenario_menunode_internal_unique';
			} elseif ($menuNode['redundancy'] == 'primary') {
				$cssClass = 'zenario_menunode_internal';
			} else {
				$cssClass = 'zenario_menunode_internal_secondary';
			}

		} elseif ($menuNode['target_loc'] == 'ext' && $menuNode['ext_url']) {
			$cssClass = 'zenario_menunode_external';

		} else {
			$cssClass = 'zenario_menunode_unlinked';
		}
		
		if (empty($item['parent_id'])) {
			$cssClass .= ' zenario_menunode_toplevel';
		}
		if (!empty($item['children'])) {
			$cssClass .= ' zenario_menunode_with_children';
		}
		
		return $cssClass;
	}

	//Formerly "getMenuPathWithMenuSection()"
	public static function pathWithSection($menuId, $langId = false, $separator = ' -> ', $useOrdinal = false) {
		return \ze\menu::sectionName(\ze\row::get('menu_nodes', 'section_id', $menuId)). $separator. \ze\menuAdm::path($menuId, $langId, $separator, $useOrdinal);
	}
	
	private static $maxOrdinal = null;

	//Formerly "getMenuPath()"
	public static function path($menuId, $langId = false, $separator = ' -> ', $useOrdinal = false) {
		if ($langId === false) {
			$langId = \ze\content::visitorLangId();
	
		} elseif ($langId === true) {
			$langId = \ze::$defaultLang;
		}
	
		$sql = "
			SELECT
				GROUP_CONCAT(";
			
		if ($useOrdinal) {
			if (self::$maxOrdinal === null) {
				self::$maxOrdinal = ceil(log(1 + (int) \ze\row::max('menu_nodes', 'ordinal'), 10));
			}
		
			$sql .= "
					LPAD(mi.ordinal, ". (int) self::$maxOrdinal. ", '0')";
	
		} else {
			$sql .= "
					(
						SELECT CONCAT(mt.name, IF(mt.language_id = '". \ze\escape::sql($langId). "', '', CONCAT(' (', mt.language_id, ')')))
						FROM ". DB_PREFIX. "menu_text AS mt
						WHERE mt.menu_id = mi.id
						ORDER BY
							mt.language_id = '". \ze\escape::sql($langId). "' DESC,
							mt.language_id = '". \ze\escape::sql(\ze::$defaultLang). "' DESC
						LIMIT 1
					)";
		}
	
		$sql .= "
					ORDER BY mh.separation DESC SEPARATOR '". \ze\escape::sql($separator). "'
				)
			FROM ". DB_PREFIX. "menu_hierarchy AS mh
			INNER JOIN ". DB_PREFIX. "menu_nodes AS mi
			   ON mi.id = mh.ancestor_id
			WHERE mh.child_id = ". (int) $menuId;
	
		if (($result = \ze\sql::select($sql)) && ($row = \ze\sql::fetchRow($result))) {
			return $row[0];
		} else {
			return '';
		}
	}


	//Formerly "getMenuItemLevel()"
	public static function level($mID) {
		$sql = "
			SELECT IFNULL(MAX(separation), 0) + 1
			FROM ". DB_PREFIX. "menu_hierarchy
			WHERE child_id = ". (int) $mID;
	
		if (($result = \ze\sql::select($sql)) && ($row = \ze\sql::fetchRow($result))) {
			return $row[0];
		} else {
			return 0;
		}
	}

	//Formerly "saveMenuDetails()"
	public static function save($submission, $menuId = false, $resyncIfNeeded = true, $skipSectionChecks = false) {
		$newMenu = false;
		$recalc = false;
		$lastEquivId = $lastCType = $lastCItemDifferent = false;


		//Look up equiv ids from content ids
		if (!empty($submission['content_type'])) {
			if (!empty($submission['content_id']) && empty($submission['equiv_id'])) {
				$submission['equiv_id'] = $submission['content_id'];
				\ze\content::langEquivalentItem($submission['equiv_id'], $submission['content_type'], true);
			}
		}

		if ($menuId) {
			$submission['section_id'] = \ze\row::get('menu_nodes', 'section_id', $menuId);

		} elseif (!empty($submission['section_id'])) {
			$submission['section_id'] = \ze\menu::sectionId($submission['section_id']);
		}

		if (!$skipSectionChecks) {
			if (empty($submission['section_id'])) {
				echo \ze\admin::phrase('No section was set!');
				exit;
	
			} elseif (!\ze\row::exists('menu_sections', $submission['section_id'])) {
				echo \ze\admin::phrase('The Menu Section requested does not exist!');
				exit;
			}
		}

		if ($menuId && ($lastMenu = \ze\row::get('menu_nodes', ['target_loc', 'equiv_id', 'content_type'], $menuId))) {
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
				FROM ". DB_PREFIX. "menu_nodes 
				WHERE section_id = ". (int) $submission['section_id']. "
				  AND parent_id = ". (int) ($submission['parent_id'] ?? false);
		
			$result = \ze\sql::select($sql);
			list($ordinal) = \ze\sql::fetchRow($result);
	
			$submission['ordinal'] = $ordinal? ++$ordinal : 1;
		}


		//Update the Menu Nodes Table
		$sql = "";
		foreach(\ze\deprecated::getFields(DB_PREFIX, 'menu_nodes') as $field => $details) {
			if (isset($submission[$field])) {
				\ze\deprecated::addFieldToSQL($sql, DB_PREFIX. 'menu_nodes', $field, $submission, $menuId, $details);
			}
		}

		if ($sql) {
			if ($menuId) {
				$sql .= "
					WHERE id = ". (int) $menuId;
			}
			\ze\sql::update($sql);
			$newId = \ze\sql::insertId();
	
			//Get the new menu id for new menus
			if (!$menuId) {
				$menuId = $newId;
			}
		}





		//Check that the primary/secondary flag is set correctly for Menu Nodes
		if (isset($submission['redundancy']) && !empty($submission['equiv_id'])) {
			if ($submission['redundancy'] == 'primary') {
				$sql = "
					UPDATE ". DB_PREFIX. "menu_nodes SET
						redundancy = 'secondary'
					WHERE equiv_id = ". (int) $submission['equiv_id']. "
					  AND content_type = '". \ze\escape::sql($submission['content_type']). "'
					  AND id != ". (int) $menuId;
				\ze\sql::update($sql);
			} else {
				\ze\menuAdm::ensureContentItemHasPrimaryNode($submission['equiv_id'], $submission['content_type']);
			}
		}
		if ($lastCItemDifferent && $lastEquivId && $lastCType) {
			\ze\menuAdm::ensureContentItemHasPrimaryNode($lastEquivId, $lastCType);
		}

		if ($newMenu) {
			//If we're adding a new Menu Node, add entries into the menu_hierarchy table.
			//There's no need to recalculate the whole table as nothing will have changed; we just need to add the new entries in for this Menu Node
			\ze\menuAdm::addNewNodeToHierarchy($submission['section_id'], $menuId, ($submission['parent_id'] ?? false));

		} elseif ($recalc && $resyncIfNeeded) {
			//If this was a Menu Node being moved, recalculate the entire menu hierarchy
			if ($menu = \ze\row::get('menu_nodes', ['section_id'], $menuId)) {
				\ze\menuAdm::recalcHierarchy($menu['section_id']);
			}
		}


		return $menuId;
	}

	//Formerly "saveMenuText()"
	public static function saveText($menuId, $languageId, $submission, $neverCreate = false) {

		$textExists = \ze\row::get('menu_text', ['name'], ['menu_id' => $menuId, 'language_id' => $languageId]);
	
		//Update or create a new entry, depending on whether one already exists
		if (!$textExists) {
			if ($neverCreate) {
				return;
			}
		
			$submission['menu_id'] = $menuId;
			$submission['language_id'] = $languageId;
		
			//For new translations of an existing Menu Node with an external URL, default the URL to the
			//URL of an existing translation if it was not provided.
			if (!isset($submission['ext_url'])
			 && (($url = \ze\row::get('menu_text', 'ext_url', ['menu_id' => $menuId, 'language_id' => \ze::$defaultLang]))
			  || ($url = \ze\row::get('menu_text', 'ext_url', ['menu_id' => $menuId])))) {
				$submission['ext_url'] = $url;
			}
	
		} else {
			unset($submission['menu_id'], $submission['language_id']);
		}
	
		$sql = "";
		$hadUsefulField = false;
		foreach(\ze\deprecated::getFields(DB_PREFIX, 'menu_text') as $field => $details) {
			if (isset($submission[$field])) {
				\ze\deprecated::addFieldToSQL($sql, DB_PREFIX. 'menu_text', $field, $submission, $textExists, $details);
			
				if ($field != 'language_id' && $field != 'menu_id') {
					$hadUsefulField = true;
				}
			}
		}
	
		if ($sql && $hadUsefulField) {
			if ($textExists) {
				$sql .= "
					WHERE language_id = '". \ze\escape::sql($languageId). "'
					  AND menu_id = ". (int) $menuId;	
			}
		
			\ze\sql::update($sql);
		
			if (isset($submission['name'])) {
				if (!$textExists) {
					\ze\module::sendSignal('eventMenuNodeTextAdded', ['menuId' => $menuId, 'languageId' => $languageId, 'newText' => $submission['name']]);
			
				} elseif ($submission['name'] != $textExists['name']) {
					\ze\module::sendSignal('eventMenuNodeTextUpdated', ['menuId' => $menuId, 'languageId' => $languageId, 'newText' => $submission['name'], 'oldText' => $textExists['name']]);
				}
			}
		}
	}

	//Formerly "removeMenuText()"
	public static function removeText($menuId, $languageId) {
		\ze\row::delete('menu_text', ['language_id' => $languageId, 'menu_id' => $menuId]);
	}

	//Formerly "addContentItemsToMenu()"
	public static function addContentItems($tagIds, $menuTarget, $afterNeighbour = 0) {
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
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = c.admin_version
			WHERE c.tag_id IN (". \ze\escape::in($tagIds). ")
			ORDER BY
				c.type,
				c.equiv_id,
				c.language_id = '". \ze\escape::sql(\ze::$defaultLang). "' DESC,
				c.language_id,
				v.title";

		$menuId = false;
		$lastEquivTag = false;
		$menuIds = [];
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			if ($lastEquivTag != $row['content_type']. '_'. $row['equiv_id']) {
				$lastEquivTag = $row['content_type']. '_'. $row['equiv_id'];
				$menuId = false;
			}
	
			$contentType = \ze\row::get('content_types', ['hide_private_item', 'hide_menu_node'], $row['content_type']);
	
			if ($contentType['hide_menu_node']) {
				$row['invisible'] = true;
			}
			$row['hide_private_item'] = (int) $contentType['hide_private_item'];
	
			$menuId = \ze\menuAdm::save($row, $menuId, $resyncIfNeeded = false, $skipSectionChecks = true);
			\ze\menuAdm::saveText($menuId, $row['language_id'], $row);
			$menuIds[$menuId] = $menuId;
		}

		//By default, just move to the top level
		$newParentId = 0;
		$newSectionId = $_POST['child__refiner__section'] ?? false;
		$newNeighbourId = 0;

		//Look for a menu node in the request
		if ($menuTarget) {
			//If this is a numeric id, look up its details and move next to that Menu Node
			if (is_numeric($menuTarget) && $neighbour = \ze\menu::details($menuTarget)) {
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
						$newParentId = \ze\menu::parentId($menu_position[1]);
						$newNeighbourId = $menu_position[1];
					}
				}
			}
		}

		\ze\menuAdm::moveMenuNode(
			$menuIds,
			$newSectionId,
			$newParentId,
			$newNeighbourId,
			$afterNeighbour);
		
		return $menuIds;
	}

	//Formerly "moveMenuNode()"
	public static function moveMenuNode($ids, $newSectionId, $newParentId, $newNeighbourId, $afterNeighbour = 0, $languageId = false) {
		$numMoves = 0;
		$idsList = '';
		$sectionId = false;
		$sectionIds = [];
		$menuNodes = [];
		$newNeighbour = false;
		$newSectionId = \ze\menu::sectionId($newSectionId);

		if (!is_array($ids)) {
			$ids = \ze\ray::explodeAndTrim($ids);
		}

		//If a specific node was picked, move the selected nodes to that ordinal
		if ($newNeighbourId) {
			$newNeighbour = \ze\row::get('menu_nodes', 'ordinal', $newNeighbourId);
		}

		if ($newParentId && ($parentDetails = \ze\menu::details($newParentId))) {
	
			//Move one or more Menu Nodes under a Parent Menu Node
			foreach ($ids as $id) {
				if ($id == $parentDetails['id']) {
					echo \ze\admin::phrase('A Menu Node cannot be its own Parent. Please choose a different Menu Node to be the Parent.');
					exit;
		
				} elseif (\ze\menu::isAncestor($parentDetails['id'], $id)) {
					echo \ze\admin::phrase('A Menu Node cannot be both the Child and the Parent of another Menu Node. Please choose a different Menu Node to be the Parent.');
					exit;
				}
			}
	
			foreach ($ids as $id) {
				if ($menuDetails = \ze\row::get('menu_nodes', ['section_id', 'parent_id', 'ordinal'], $id)) {
					$menuNodes[$id] = $menuDetails;
					$idsList .= ($idsList? ',' : ''). (int) $id;
			
					//Remove the ordinal, to be fixed later
					\ze\row::update('menu_nodes', ['ordinal' => 0], $id);
				}
			}
	
			foreach ($menuNodes as $id => $menuDetails) {
		
				//If this is a different section, move the Menu Node and its children to that section.
				//(This will mess up the menu_hierarchy, but that will be fixed later.)
				if ($menuDetails['section_id'] != ($sectionId = $parentDetails['section_id'])) {
					$sql = "
						UPDATE ". DB_PREFIX. "menu_hierarchy AS h
						INNER JOIN ". DB_PREFIX. "menu_nodes AS m
						   ON m.id = h.child_id
						  SET m.section_id = ". (int) $parentDetails['section_id']. ",
							  h.section_id = ". (int) $parentDetails['section_id']. "
						WHERE h.ancestor_id = ". (int) $id;
					\ze\sql::update($sql);
		
					$sectionIds[$menuDetails['section_id']] = true;
				}
		
				$submission = [
					'parent_id' => $newParentId,
					'parentMenuID' => -1];
		
				//If there was a specific ordinal chosen, move there
				if ($newNeighbour !== false) {
					$submission['ordinal'] = $newNeighbour + $afterNeighbour + $numMoves++;
				}
		
				\ze\menuAdm::save($submission, $id, false);
			}

		} else {
			foreach ($ids as $id) {
				if ($menuDetails = \ze\row::get('menu_nodes', ['section_id', 'parent_id', 'ordinal'], $id)) {
					$menuNodes[$id] = $menuDetails;
					$idsList .= ($idsList? ',' : ''). (int) $id;
			
					//Remove the ordinal, to be fixed later
					\ze\row::update('menu_nodes', ['ordinal' => 0], $id);
				}
			}
	
			//Move one or more Menu Nodes to the Top Level
			foreach ($menuNodes as $id => $menuDetails) {
		
				if ($menuDetails['section_id'] != ($sectionId = $newSectionId)) {
			
					//If this is a different section, move the Menu Node and its children to that section.
					//(This will mess up the menu_hierarchy, but that will be fixed later.)
					$sql = "
						UPDATE ". DB_PREFIX. "menu_hierarchy AS h
						INNER JOIN ". DB_PREFIX. "menu_nodes AS m
						   ON m.id = h.child_id
						  SET m.section_id = ". (int) $newSectionId. ",
							  h.section_id = ". (int) $newSectionId. "
						WHERE h.ancestor_id = ". (int) $id;
					\ze\sql::update($sql);
			
					$sectionIds[$menuDetails['section_id']] = true;
				}
		
				$submission = [
					'parent_id' => 0,
					'parentMenuID' => -1];
		
				//If there was a specific ordinal chosen, move there
				if ($newNeighbour !== false) {
					$submission['ordinal'] = $newNeighbour + $afterNeighbour + $numMoves++;
				}
		
				\ze\menuAdm::save($submission, $id, false);
			}
		}

		//If there was a specific ordinal chosen, we'll need to bump up the ordinals of the existing Menu Node(s) after that ordinal
		if ($newNeighbour !== false && $numMoves && $idsList) {
			$sql = "
				UPDATE ". DB_PREFIX. "menu_nodes
				SET ordinal = ordinal + ". (int) $numMoves. "
				WHERE section_id = ". (int) $sectionId. "
				  AND parent_id = ". (int) $newParentId. "
				  AND id NOT IN (". $idsList. ")
				  AND ordinal >= ". (int) ($newNeighbour + $afterNeighbour);
			\ze\sql::update($sql);
		}

		//Renumber the ordinal of all the sections we've just moved from
		$renumbers = [];
		foreach ($menuNodes as $id => $menuDetails) {
			if (!isset($renumbers[$menuDetails['section_id']. '_'. $menuDetails['parent_id']])) {
		
				$result = \ze\row::query('menu_nodes', ['id', 'ordinal'], ['section_id' => $menuDetails['section_id'], 'parent_id' => $menuDetails['parent_id']], 'ordinal');
				$o = 0;
				while ($row = \ze\sql::fetchAssoc($result)) {
					if ($row['ordinal'] != ++$o) {
						\ze\row::set('menu_nodes', ['ordinal' => $o], $row['id']);
					}
				}
		
				$renumbers[$menuDetails['section_id']. '_'. $menuDetails['parent_id']] = true;
			}
		}


		$sectionIds[$sectionId] = true;

		//Delete and recalculate the menu hierarchy for every section that has been effected
		foreach ($sectionIds as $sectionId => $dummy) {
			\ze\row::delete('menu_hierarchy', ['section_id' => $sectionId]);
		}

		foreach ($sectionIds as $sectionId => $dummy) {
			\ze\menuAdm::recalcHierarchy($sectionId);
		}
	}


	//Delete a Menu Item and all of its children
	//Formerly "deleteMenuNode()"
	public static function delete($id, $firstCall = true) {
		if (!$id) {
			return;
		}
	
		$result = \ze\row::query('menu_nodes', 'id', ['parent_id' => $id]);
		while ($row = \ze\sql::fetchAssoc($result)) {
			\ze\menuAdm::delete($row['id'], false);
		}
	
		$content = \ze\menu::getContentItem($id);
	
		\ze\row::delete('menu_nodes', ['id' => $id]);
		\ze\row::delete('menu_text', ['menu_id' => $id]);
		\ze\row::delete('menu_positions', ['menu_id' => $id]);
		\ze\row::delete('menu_hierarchy', ['child_id' => $id]);
		\ze\row::delete('menu_hierarchy', ['ancestor_id' => $id]);
		\ze\module::sendSignal('eventMenuNodeDeleted', ['menuId' => $id]);
	
		//If this Content Item had any other Menu Nodes, make sure that one of the remaining is a primary
		if ($content) {
			\ze\menuAdm::ensureContentItemHasPrimaryNode($content['equiv_id'], $content['content_type']);
		}
	}

	//Formerly "ensureContentItemHasPrimaryMenuItem()"
	public static function ensureContentItemHasPrimaryNode($equivId, $cType) {
		$sql = "
			UPDATE ". DB_PREFIX. "menu_nodes SET
				redundancy = 'primary'
			WHERE equiv_id = ". (int) $equivId. "
			  AND content_type = '". \ze\escape::sql($cType). "'
			ORDER BY redundancy = 'primary' DESC
			LIMIT 1";
		\ze\sql::update($sql);
	}

	//Formerly "addNewMenuItemToMenuHierarchy()"
	public static function addNewNodeToHierarchy($sectionId, $menuId, $parentId = false) {

		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_hierarchy (
				section_id, child_id, ancestor_id, separation
			) VALUES (
				". (int) $sectionId. ", ". (int) $menuId. ", ". (int) $menuId. ", 0
			)";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) VALUES (
				'". (int) $sectionId. "_". (int) $parentId. "_0',
				'". (int) $sectionId. "_". (int) $menuId. "_0',
				". (int) $sectionId. ", ". (int) $menuId. ", 0
			)";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) VALUES (
				'". (int) $sectionId. "_". (int) $menuId. "_0',
				'". (int) $sectionId. "_". (int) $menuId. "_1',
				". (int) $sectionId. ", ". (int) $menuId. ", 1
			)";
		\ze\sql::update($sql);
	
		if ($parentId) {
			$sql = "
				INSERT INTO ". DB_PREFIX. "menu_hierarchy (
					section_id, child_id, ancestor_id, separation
				) SELECT
					section_id, ". (int) $menuId. ", ancestor_id, separation + 1
				FROM ". DB_PREFIX. "menu_hierarchy
				WHERE child_id = ". (int) $parentId. "
				ORDER BY section_id, child_id, ancestor_id, separation";
			\ze\sql::update($sql);
		}
	}

	//Formerly "recalcAllMenuHierarchy()"
	public static function recalcAllHierarchy() {
		$sql = "
			TRUNCATE TABLE ". DB_PREFIX. "menu_hierarchy";
		\ze\sql::update($sql);
	
	
		$sql = "
			SELECT id
			FROM ". DB_PREFIX. "menu_sections";
	
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			\ze\menuAdm::recalcHierarchy($row['id']);
		}
	}


	//Formerly "recalcMenuHierarchy()"
	public static function recalcHierarchy($sectionId) {
		\ze\row::delete('menu_hierarchy', ['section_id' => $sectionId]);
		\ze\row::delete('menu_positions', ['section_id' => $sectionId, 'menu_id' => ['!' => 0]]);
		\ze\menuAdm::recalcTopLevelPositions();
	
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_hierarchy(
				section_id, child_id, ancestor_id, separation
			) SELECT
				section_id, id, id, 0
			FROM ". DB_PREFIX. "menu_nodes
			WHERE section_id = ". (int) $sectionId. "
			ORDER BY id";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) SELECT
				CONCAT(section_id, '_', parent_id, '_', 0),
				CONCAT(section_id, '_', id, '_', 0),
				section_id, id, 0
			FROM ". DB_PREFIX. "menu_nodes
			WHERE section_id = ". (int) $sectionId. "
			ORDER BY id";
		\ze\sql::update($sql);
	
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) SELECT
				CONCAT(section_id, '_', id, '_', 0),
				CONCAT(section_id, '_', id, '_', 1),
				section_id, id, 1
			FROM ". DB_PREFIX. "menu_nodes
			WHERE section_id = ". (int) $sectionId. "
			ORDER BY id";
		\ze\sql::update($sql);
	
		$ancestors = [];
		self::recalcHierarchyR($sectionId, 0, $ancestors, 0);
	}

	private static function recalcHierarchyR($sectionId, $parentId, &$ancestors, $separation) {
	
		if ($parentId) {
			$sql = "
				SELECT id, section_id
				FROM ". DB_PREFIX. "menu_nodes
				WHERE parent_id = ". (int) $parentId;
	
		} else {
			$sql = "
				SELECT id, section_id
				FROM ". DB_PREFIX. "menu_nodes
				WHERE section_id = ". (int) $sectionId. "
				  AND parent_id = 0";
		}
		$result = \ze\sql::select($sql);
	
		++$separation;
		while ($row = \ze\sql::fetchAssoc($result)) {
		
			if ($row['section_id'] != $sectionId) {
				\ze\row::update('menu_nodes', ['section_id' => $sectionId], $row['id']);
			}
		
			foreach ($ancestors as $ancestor => $separationOffset) {
				$sql = "
					INSERT INTO ". DB_PREFIX. "menu_hierarchy SET
						section_id = ". (int) $sectionId. ",
						child_id = ". (int) $row['id']. ",
						ancestor_id = ". (int) $ancestor. ",
						separation = ". (int) ($separation - $separationOffset);
				\ze\sql::update($sql);
			}
		
			$ancestors[$row['id']] = $separation;
			self::recalcHierarchyR($sectionId, $row['id'], $ancestors, $separation);
			unset($ancestors[$row['id']]);
		}
	}


	//Formerly "recalcMenuPositionsTopLevel()"
	public static function recalcTopLevelPositions() {
	
		//First, do some tidying up.
		//Delete any positions from sections that do not exist.
		$sql = "
			DELETE mp.*
			FROM ". DB_PREFIX. "menu_positions AS mp
			LEFT JOIN ". DB_PREFIX. "menu_sections AS ms
			   ON ms.id = mp.section_id
			WHERE ms.id IS NULL";
		\ze\sql::update($sql);
	
		//Delete all of the top-level entries
		\ze\row::delete('menu_positions', ['menu_id' => 0]);
	
		//Insert new entries
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) SELECT
				'0',
				CONCAT(id, '_', 0, '_', 0),
				id, 0, 0
			FROM ". DB_PREFIX. "menu_sections
			ORDER BY id";
		\ze\sql::update($sql);
	
		//Insert new entries
		$sql = "
			INSERT INTO ". DB_PREFIX. "menu_positions (
				parent_tag,
				tag,
				section_id, menu_id, is_dummy_child
			) SELECT
				CONCAT(id, '_', 0, '_', 0),
				CONCAT(id, '_', 0, '_', 1),
				id, 0, 1
			FROM ". DB_PREFIX. "menu_sections
			ORDER BY id";
		\ze\sql::update($sql);
	}


}