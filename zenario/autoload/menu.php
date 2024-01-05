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

namespace ze;

class menu {



	//Returns a Content Item that points to a Menu Node
	//Note that this will probably be the Content Item in the Primary Language
	//Formerly "getContentFromMenu()"
	public static function getContentItem($mID, $recurseLimit = 0) {
	
		if ($menu = \ze\row::get('menu_nodes', ['id', 'equiv_id', 'content_type', 'parent_id'], $mID)) {
			if ($menu['equiv_id'] && $menu['content_type']) {
				$menu['content_id'] = $menu['equiv_id'];
				return $menu;
		
			} elseif ($recurseLimit) {
				return \ze\menu::getContentItem($menu['parent_id'], --$recurseLimit);
			}
		}
	
		return false;
	}

	//Formerly "getMenuItemFromContent()"
	public static function getFromContentItem($cID, $cType, $fetchSecondaries = false, $sectionId = false, $allowGhosts = false, $fetchEverything = false) {
		if ($cID && $cType) {
			$sql = "
				SELECT
					m.id AS mID,";
			
			if ($fetchEverything) {
				$sql .= "
					m.*, t.*";
			
			} else {
				$sql .= "
					m.id,
					m.section_id,
					c.language_id,
					t.name,
					m.redundancy, 
					m.parent_id,
					t.ext_url, 
					m.ordinal,
					m.hide_private_item,
					m.invisible,
					m.rel_tag,
					m.css_class";
			}
			
			$sql .= "
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "menu_nodes AS m
				   ON m.equiv_id = c.equiv_id
				  AND m.content_type = c.type
				  AND m.target_loc = 'int'
				". ($allowGhosts? "LEFT" : "INNER"). " JOIN ". DB_PREFIX. "menu_text AS t
				   ON t.menu_id = m.id
				  AND t.language_id = c.language_id
				INNER JOIN ". DB_PREFIX. "menu_text AS mt
				   ON mt.menu_id = m.id
				  AND mt.language_id = c.language_id
				WHERE c.id = ". (int) $cID. "
				  AND c.type = '" . \ze\escape::asciiInSQL($cType) . "'";
		
			if ($sectionId) {
				$sql .= "
				  AND m.section_id = ". (int) \ze\menu::sectionId($sectionId);
			}
		
			$sql .= "
				ORDER BY m.redundancy = 'primary' DESC";
		
			if (!$fetchSecondaries) {
				$sql .= "
					LIMIT 1";
			}
		
			if ($fetchSecondaries) {
				return \ze\sql::fetchAssocs($sql);
		
			} else {
				return \ze\sql::fetchAssoc($sql);
			}
		} else {
			return false;
		}
	}

	//Formerly "getSectionMenuItemFromContent()"
	public static function getIdFromContentItem($equivId, $cType, $section, $mustBePrimary = false) {

		$sql = "
			SELECT id
			FROM ". DB_PREFIX. "menu_nodes
			WHERE equiv_id = ". (int) $equivId. "
			  AND content_type = '". \ze\escape::asciiInSQL($cType). "'
			  AND section_id = ". (int) \ze\menu::sectionId($section). "
			  AND target_loc = 'int'";
	
		if ($mustBePrimary) {
			$sql .= "
			  AND redundancy = 'primary'";
	
		} else {
			$sql .= "
			ORDER BY redundancy = 'primary' DESC";
		}
	
		$sql .= "
			LIMIT 1";
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchAssoc($result)) {
			return $row['id'];
		} else {
			return false;
		}
	}

	//Get the content item in the menu above this one
	//Formerly "getContentItemAbove()"
	public static function getContentItemAbove(&$cID, &$cType, $equivId, $langId = false) {
	
		if ($langId === false) {
			$langId = \ze\content::langId($equivId, $cType);
		}
	
		$sql = "
			SELECT
				m2.equiv_id,
				m2.content_type
			FROM ". DB_PREFIX. "menu_nodes AS m1
			INNER JOIN ". DB_PREFIX. "menu_hierarchy AS mh
			   ON mh.child_id = m1.id
			INNER JOIN ". DB_PREFIX. "menu_nodes AS m2
			   ON m2.id = mh.ancestor_id
			  AND m2.target_loc = 'int'
			WHERE m1.target_loc = 'int'
			  AND m1.equiv_id = ". (int) $equivId. "
			  AND m1.content_type = '". \ze\escape::asciiInSQL($cType). "'
			ORDER BY m1.redundancy = 'primary' DESC, m2.redundancy = 'primary' DESC
			LIMIT 1";
	
		if ($row = \ze\sql::fetchRow($sql)) {
			$cID = $row[0];
			$cType = $row[1];
			return \ze\content::langEquivalentItem($cID, $cType, $langId);
	
		} else {
			return $cID = $cType = false;
		}
	}

	//Formerly "menuSectionId()"
	public static function sectionId($sectionIdOrName, $checkExists = false) {
		if (!is_numeric($sectionIdOrName)) {
			return \ze\row::get('menu_sections', 'id', ['section_name' => $sectionIdOrName]);
	
		} elseif ($checkExists) {
			return \ze\row::get('menu_sections', 'id', ['id' => $sectionIdOrName]);
	
		} else {
			return $sectionIdOrName;
		}
	}

	//Formerly "menuSectionName()"
	public static function sectionName($sectionIdOrName) {
		if (is_numeric($sectionIdOrName)) {
			return \ze\row::get('menu_sections', 'section_name', ['id' => $sectionIdOrName]);
		} else {
			return $sectionIdOrName;
		}
	}

	//Formerly "getMenuInLanguage()"
	public static function getInLanguage($mID, $langId) {
		$sql = "
			SELECT
				m.id AS mID,
				t.name,
				m.target_loc,
				m.open_in_new_window,
				c.equiv_id,
				c.id AS cID,
				c.type AS cType,
				c.alias,
				m.use_download_page,
				m.hide_private_item,
				t.ext_url,
				c.visitor_version,
				m.invisible,
				m.accesskey,
				m.ordinal,
				m.rel_tag,
				m.css_class
			FROM ". DB_PREFIX. "menu_text AS t
			INNER JOIN ". DB_PREFIX. "menu_nodes AS m
			   ON m.id = t.menu_id
			LEFT JOIN ". DB_PREFIX. "content_items AS c
			   ON m.equiv_id = c.equiv_id
			  AND m.content_type = c.type
			  AND m.target_loc = 'int'
			  AND t.language_id = c.language_id
			WHERE t.language_id = '" . \ze\escape::asciiInSQL($langId) . "'
			  AND t.menu_id = ". (int) $mID;
	
		$result = \ze\sql::select($sql);
		return \ze\sql::fetchAssoc($result);
	}

	//Formerly "getMenuNodeDetails()"
	public static function details($mID, $langId = false) {
		$row = \ze\row::get('menu_nodes', true, $mID);
	
		if ($row && $langId) {
			$row['mID'] = $row['id'];
			$row['name'] = null;
			$row['descriptive_text'] = null;
			$row['ext_url'] = null;
		
			if ($text = \ze\row::get('menu_text', ['name', 'descriptive_text', 'ext_url'], ['menu_id' => $mID, 'language_id' => $langId])) {
				$row['name'] = $text['name'];
				$row['descriptive_text'] = $text['descriptive_text'];
				$row['ext_url'] = $text['ext_url'];
			}
		
			if ($row['equiv_id'] && $row['content_type']) {
				$row['content_id'] = $row['equiv_id'];
				\ze\content::langEquivalentItem($row['content_id'], $row['content_type'], $langId);
			
				if (\ze\menu::isUnique($row['redundancy'], $row['equiv_id'], $row['content_type'])) {
					$row['redundancy'] = 'unique';
				}
			}
		}
	
		return $row;
	}
	
	public static function getMenuNodeFeatureImageId($nodeId) {
		return \ze\row::get('menu_node_feature_image', 'image_id', ['node_id' => $nodeId, 'use_feature_image' => 1]);
	}

	//Formerly "isMenuNodeUnique()"
	public static function isUnique($redundancy, $equiv_id, $content_type) {
		if ($redundancy == 'primary') {
			$sql = '
				SELECT COUNT(*)
				FROM ' . DB_PREFIX . 'menu_nodes
				WHERE equiv_id = ' . (int)$equiv_id . '
				AND content_type = "'. \ze\escape::asciiInSQL($content_type) . '"';
			$result = \ze\sql::select($sql);
			$row = \ze\sql::fetchRow($result);
			if ($row[0] == 1) {
				return true;
			}
		}
		return false;
	}

	//Formerly "getMenuName()"
	public static function name($mID, $langId = false, $missingPhrase = '[[name]] ([[language_id]])') {
	
		$markFrom = false;
		if ($langId === false) {
			$langId = \ze\content::visitorLangId();
	
		} elseif ($langId === true) {
			$langId = \ze::$defaultLang;
	
		} elseif (\ze\priv::check()) {
			$markFrom = true;
		}
	
		$sql = "
			SELECT name, language_id
			FROM ". DB_PREFIX. "menu_text AS mt
			WHERE menu_id = ". (int) $mID. "
			ORDER BY
				language_id = '". \ze\escape::asciiInSQL($langId). "' DESC,
				language_id = '". \ze\escape::asciiInSQL(\ze::$defaultLang). "' DESC
			LIMIT 1";
	
		$result = \ze\sql::select($sql);
		if ($row = \ze\sql::fetchAssoc($result)) {
		
			if ($markFrom && $row['language_id'] != $langId) {
				$row['name'] = \ze\admin::phrase($missingPhrase, $row);
			}
		
			return $row['name'];
		} else {
			return false;
		}
	}

	//Formerly "isMenuItemAncestor()"
	public static function isAncestor($childId, $ancestorId) {
		return \ze\row::exists('menu_hierarchy', ['child_id' => $childId, 'ancestor_id' => $ancestorId]);
	}

	//Formerly "getMenuParent()"
	public static function parentId($mID) {
		return \ze\row::get('menu_nodes', 'parent_id', ['id' => $mID]);
	}
	
	
	const privateItemsExist = 1;
	const staticFunctionCalled = 2;

	//Formerly "shouldShowMenuItem()"
	public static function shouldShow(&$row, &$cachingRestrictions, $language, $getFullMenu = false, $adminMode = false) {
	
		// Hide menu node if static method is set
		if (!empty($row['module_class_name'])) {
			$cachingRestrictions = max($cachingRestrictions, self::staticFunctionCalled);
			
			if (!(\ze\module::inc($row['module_class_name']))
			 || !(method_exists($row['module_class_name'], $row['method_name']))
			 || !($overrides = call_user_func(
					[$row['module_class_name'], $row['method_name']],
						$row['param_1'], $row['param_2'])
			)) {
				//A little hack - return null so we can tell the difference
				return null;
		
			} else {
				//If an array is returned, show the menu node but override any
				//of the options it had
				if (is_array($overrides)) {
					foreach ($overrides as $key => &$override) {
						$row[$key] = $override;
					}
			
				//If a string is returned, set the text of the menu node
				//This is an un-documented feature for backwards compatibility
				} elseif (is_string($overrides)) {
					$row['name'] = $overrides;
				}
			}
		}
	
		//Logic for menu nodes that point to content items 
		if ($row['target_loc'] == 'int') {
		
			//Check to see if the content item attached to the menu node was visible
			if (!$row['cID']) {
			
				//Try to show a placeholder page from the default language if the content item in this language
				//was missing or not published, and that option is enabled
				if ($getFullMenu
				 && $row['cType']
				 && $row['equiv_id']
				 && $language !== \ze::$defaultLang) {
				
					$sql = '
						SELECT alias
						FROM '. DB_PREFIX. 'content_items
						WHERE id = '. (int) $row['equiv_id']. '
						  AND `type` = \''. \ze\escape::asciiInSQL($row['cType']). '\'';
					
					if ($adminMode) {
						$sql .= "
							AND status != 'deleted'";
					} else {
						$sql .= "
							AND status IN ('published', 'published_with_draft')";
					}
				
					if ($content = \ze\sql::fetchAssoc($sql)) {
						$row['cID'] = $row['equiv_id'];
						$row['alias'] = $content['alias'];
						$row['placeholder'] = true;
					
						return \ze\menu::shouldShow($row, $cachingRestrictions, $language, $getFullMenu, $adminMode);
					}
				}
			
				//Otherwise the menu node should be hidden
				return false;
			
			//In admin mode, just check whether something is published, and don't check user permissions
			} elseif ($adminMode) {
				return \ze\content::isPublished($row['cID'], $row['cType']);
		
			//Check for Menu Nodes that are only shown if logged in/out as an Extranet User
			} elseif ($row['hide_private_item'] == 3) {
				$cachingRestrictions = max($cachingRestrictions, self::privateItemsExist);
				return !empty($_SESSION['extranetUserID']);
		
			} elseif ($row['hide_private_item'] == 2) {
				$cachingRestrictions = max($cachingRestrictions, self::privateItemsExist);
				return empty($_SESSION['extranetUserID']);
		
			} elseif ($row['hide_private_item']) {
				$cachingRestrictions = max($cachingRestrictions, self::privateItemsExist);
				return \ze\content::checkPerm($row['cID'], $row['cType'], false, false, false);
			}
		//Logic for menu nodes that point to documents
		} elseif ($row['target_loc'] == 'doc') {
			$document = \ze\row::get('documents', ['file_id', 'privacy'], ['id' => $row['document_id'], 'type' => 'file']);
			if ($document) {
				if ($document['privacy'] == 'public' || \ze\admin::id()) {
					return true;
				}
			}
			
			return false;
		}

		return $row['target_loc'] != 'none';
	}


	//Formerly "lookForMenuItems()"
	public static function query($language, $menuId, $byParent = true, $sectionId = false, $showInvisibleMenuItems = false, $getFullMenu = false, $adminMode = false) {
	
		$sql = "
			SELECT
				m.id AS mID,
				m.target_loc,
				m.open_in_new_window,
				m.anchor,
				m.module_class_name,
				m.method_name,
				m.param_1,
				m.param_2,
				m.equiv_id,
				m.document_id,
				c.id AS cID,
				m.content_type AS cType,
				c.alias,
				m.use_download_page,
				m.hide_private_item,
				m.add_registered_get_requests,
				m.custom_get_requests,
				m.invisible,
				m.accesskey,
				m.ordinal,
				m.rel_tag,
				m.image_id,
				m.rollover_image_id,
				m.css_class,
				tc.privacy";
	
		if ($getFullMenu
		 && $language != \ze::$defaultLang) {
			$sql .= ",
				IFNULL(t.name, d.name) AS name,
				IFNULL(t.ext_url, d.ext_url) AS ext_url,
				IFNULL(t.descriptive_text, d.descriptive_text) AS descriptive_text
			FROM ". DB_PREFIX. "menu_nodes AS m
			LEFT JOIN ". DB_PREFIX. "menu_text AS t
			   ON t.menu_id = m.id
			  AND t.language_id = '". \ze\escape::asciiInSQL($language). "'
			LEFT JOIN ". DB_PREFIX. "menu_text AS d
			   ON t.menu_id IS NULL
			  AND d.menu_id = m.id
			  AND d.language_id = '". \ze\escape::asciiInSQL(\ze::$defaultLang). "'";
	
		} else {
			$sql .= ",
				t.name,
				t.ext_url,
				t.descriptive_text
			FROM ". DB_PREFIX. "menu_nodes AS m
			INNER JOIN ". DB_PREFIX. "menu_text AS t
			   ON t.menu_id = m.id
			  AND t.language_id = '". \ze\escape::asciiInSQL($language). "'";
		}
	
		$sql .= "
			LEFT JOIN ".DB_PREFIX."content_items AS c
			   ON m.target_loc = 'int'
			  AND m.equiv_id = c.equiv_id
			  AND m.content_type = c.type
			  AND c.language_id = '". \ze\escape::asciiInSQL($language). "'";
	
		if ($adminMode) {
			$sql .= "
				AND c.status != 'deleted'";
		} else {
			$sql .= "
				AND c.status IN ('published', 'published_with_draft')";
		}

		$sql .= "
			LEFT JOIN ".DB_PREFIX."translation_chains AS tc
			   ON tc.equiv_id = c.equiv_id
			  AND tc.type = c.type";
		
		if ($byParent) {
			$sql .= "
				WHERE m.parent_id = ". (int) $menuId;
		} else {
			$sql .= "
				WHERE m.id = ". (int) $menuId;
		}
	
		if ($sectionId) {
			$sql .= "
			  AND m.section_id = ". (int) $sectionId;
		}
	
		if (!$showInvisibleMenuItems) {
			$sql .= "
			  AND m.invisible != 1";
		}
	
		$sql .= "
			ORDER BY m.ordinal";
	
		return \ze\sql::select($sql);
	}


	//Formerly "getMenuStructure()"
	public static function getStructure(
		&$cachingRestrictions,
		$sectionId,
		$currentMenuId = false,
		$parentMenuId = 0,
		$numLevels = 0,
		$maxLevel1MenuItems = 100,
		$language = false,
		$onlyFollowOnLinks = false,
		$onlyIncludeOnLinks = false,
		$showInvisibleMenuItems = false,
		$showMissingMenuNodes = false,
		$requests = false,
		$getFullMenu = false,
		$recurseCount = 0
	) {
		if ($language === false) {
			$language = \ze::$visLang ?? \ze::$defaultLang;
		}
	
		if (++$recurseCount == 1) {
			$level1counter = 0;
		}
	
		$adminMode = \ze::isAdmin();
	
		//Look up all of the Menu Items on this level
		$edition = \ze::$edition;
		$rows = [];
		if ($showMissingMenuNodes && $language != \ze::$defaultLang) {
			$result = \ze\menu::query(\ze::$defaultLang, $parentMenuId, true, $sectionId, $showInvisibleMenuItems, $getFullMenu, $adminMode);
			while ($row = \ze\sql::fetchAssoc($result)) {
				if (empty($row['css_class'])) {
					$row['css_class'] = 'missing';
				} else {
					$row['css_class'] .= ' missing';
				}
			
				$rows[$row['mID']] = $row;
			}
		}
	
		$result = \ze\menu::query($language, $parentMenuId, true, $sectionId, $showInvisibleMenuItems, $getFullMenu, $adminMode);
		while ($row = \ze\sql::fetchAssoc($result)) {
			$rows[$row['mID']] = $row;
		}
	
		if (!empty($rows)) {
			$menuIds = '';
			$unsets = [];
			foreach ($rows as &$row) {
				$row['on'] = false;
				$row['children'] = false;
				$unsets[$row['mID']] = true;
				$menuIds .= ($menuIds? ',' : ''). $row['mID'];
			}
			unset($row);
		
			//Look for children of the Menu Nodes we will be displaying, so we know which Menu Nodes have no children
			$sql = "
				SELECT DISTINCT ancestor_id
				FROM ". DB_PREFIX. "menu_hierarchy
				WHERE ancestor_id IN (". $menuIds. ")
				  AND separation = 1";
		
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchRow($result)) {
				$rows[$row[0]]['children'] = true;
			}
		
			//Look for Menu Nodes that are ancestors of the current Menu Node
			if ($currentMenuId) {
				$sql = "
					SELECT ancestor_id
					FROM ". DB_PREFIX. "menu_hierarchy
					WHERE ancestor_id IN (". $menuIds. ")
					  AND child_id = ". (int) $currentMenuId;
			
				$result = \ze\sql::select($sql);
				while ($row = \ze\sql::fetchRow($result)) {
					$rows[$row[0]]['on'] = true;
				}
			}
		
			//Loop through each found Menu Item
			foreach ($rows as $menuId => &$row) {
			
				if ($recurseCount == 1) {
					$level1counter++;
				}
			
				if ($onlyIncludeOnLinks && !$row['on']) {
					//Have a "breadcrumbs" option to only show the chain to the current content item
					continue;
			
				} else {
					$row['active'] = $showMenuItem = \ze\menu::shouldShow($row, $cachingRestrictions, $language, $getFullMenu, $adminMode);
					$row['conditionally_hidden'] = $showMenuItem === null;
				
					if ($adminMode) {
						//Always show an Admin a Menu Node
						$showMenuItem = true;
						$row['onclick'] = "if (!window.zenarioA) return true; return zenarioA.openMenuAdminBox({id: ". (int)  $row['mID']. "});";
						if (empty($row['css_class'])) {
							$row['css_class'] = 'zenario_menu_node';
						} else {
							$row['css_class'] .= ' zenario_menu_node';
						}
					}
				}
			
				if ($showMenuItem) {
					\ze\menu::format($row, $requests);
				
				} else {
					$row['url'] = '';
					unset($row['onclick']);
				}
			
				if ($showMenuItem || $row['name']) {
					$goFurther = !$numLevels || $recurseCount < $numLevels;
					$followLink = !$onlyFollowOnLinks || $row['on'];
				
					//If this row has children...
					if ($row['children']) {
						//Recurse down into the child levels and display them, if needed
						if ($goFurther && $followLink) {
							$row['children'] = \ze\menu::getStructure(
													$cachingRestrictions,
													$sectionId, $currentMenuId, $row['mID'],
													$numLevels, $maxLevel1MenuItems, $language,
													$onlyFollowOnLinks, $onlyIncludeOnLinks,
													$showInvisibleMenuItems, $showMissingMenuNodes,
													$requests, $getFullMenu, $recurseCount);
						
							if ($row['target_loc'] == 'none' && $adminMode) {
								//Publishing a Content Item under an unlinked Menu Node will cause that to appear - mark this as so in Admin Mode
								foreach ($row['children'] as &$child) {
									if (!empty($child['active'])) {
										$row['active'] = true;
										break;
									}
								}
							}
					
						//Otherwise if we're not recursing, check that at least one of the children are in fact visible to the current Visitor
						} else {
							$row['children'] = false;
							$result2 = \ze\menu::query($language, $row['mID'], true, $sectionId, $showInvisibleMenuItems, $getFullMenu, $adminMode);
							while ($row2 = \ze\sql::fetchAssoc($result2)) {
								if ($row2['target_loc'] != 'none'
								 && (empty($row2['invisible']) || $showInvisibleMenuItems)
								 && ($adminMode || \ze\menu::shouldShow($row2, $cachingRestrictions, $language, $getFullMenu, $adminMode))
								) {
									$row['children'] = true;
									break;
								}
							}
						}
					
						//Unlinked Menu Items with visible children should still be shown to Visitors.
						//Unlinked Menu Items with no visible children should be shown but marked as inactive to Admins.
						if ($adminMode) {
							$showMenuItem = true;
					
						} elseif (!empty($row['children']) && $followLink && $goFurther) {
							$showMenuItem = true;
							$row['active'] = true;
						}
					}
				
				}
			
			
			
				if ($showMenuItem) {
					//Don't show unlinked Menu Nodes that have no immediate children to Visitors
					if ($row['target_loc'] != 'none' || $row['children'] || $adminMode || $getFullMenu) {
						//Ensure that we show this Menu Node!
						unset($unsets[$menuId]);
					}
				
					if ($recurseCount == 1) {
						if ($level1counter >= $maxLevel1MenuItems) {
							break;
						}
					}
				}
			}
		
			//Remove any Menu Items that we should not display
			foreach ($unsets as $menuId => $dummy) {
				unset($rows[$menuId]);
			}
		}
	
		if ($recurseCount>1000) {
			echo "Aborting; Menu Generation seems to be in an infinite recursion loop!";
			exit;
		}
	
		return $rows;
	}
	
	
	public static function format(&$row, $requests = false) {
		if ($row['target_loc'] == 'ext' && $row['ext_url']) {
			$row['url'] = $row['ext_url'];
	
			//Allow anyone writing a static method to easily add extra requests to the URL
			//by using the extra_requests property
			if (!empty($row['extra_requests'])) {
				if (is_array($row['extra_requests'])) {
					$row['url'] .= '&'. http_build_query($row['extra_requests']);
				} else {
					$row['url'] .= '&'. $row['extra_requests'];
				}
			}
			
		} else if ($row['target_loc'] == 'int' && $row['cID']) {
			$request = '';
			$downloadDocument = ($row['cType'] == 'document' && !$row['use_download_page']);
			if ($downloadDocument) {
				$request = '&download=1';
			}
			if (isset($row['placeholder'])) {
				$language = \ze::$visLang ?? \ze::$defaultLang;
				$request .= '&visLang='. rawurlencode($language);
			}
			if ($requests) {
				$request .= \ze\ring::addAmp($requests);
			}
			//Allow anyone writing a static method to easily add extra requests to the URL
			//by using the extra_requests property
			if (!empty($row['extra_requests'])) {
				if (is_array($row['extra_requests'])) {
					$request .= '&'. http_build_query($row['extra_requests']);
				} else {
					$request .= '&'. $row['extra_requests'];
				}
			}
		
			if (\ze::$cID == $row['cID'] && \ze::$cType == $row['cType'] && \ze::$menuTitle !== false) {
				$row['name'] = \ze::$menuTitle;
			}
			
			if ($row['custom_get_requests']) {
				$request .= '&'. $row['custom_get_requests'];
			}
		
			$link = \ze\link::toItem($row['cID'], $row['cType'], false, $request, $row['alias'], $row['add_registered_get_requests']);
		
			if ($downloadDocument) {
				$row['onclick'] = \ze\file::trackDownload($link);
			}
		
			$row['url'] = $link;
			if (!empty($row['anchor'])) {
				$row['url'] .= '#'.$row['anchor'];
			}

		} else if ($row['target_loc'] == 'doc' && $row['document_id']) {
			$row['url'] = '';

			$document = \ze\row::get('documents', ['file_id', 'privacy'], ['id' => $row['document_id'], 'type' => 'file']);
			if ($document) {
				if (\ze\row::exists('files', ['id' => $document['file_id']])) {
					if ($document['privacy'] == 'public') {
						$row['url'] = \ze\file::link($document['file_id']);
					} else {
						$row['document_privacy_error'] = true;
					}
				} else {
					$row['document_file_not_found'] = true;
				}
			}
		} else {
			$row['url'] = '';
		}
	
		if ($row['accesskey']) {
			$row['title'] = \ze\admin::phrase('_ACCESS_KEY_EQUALS', ['key' => $row['accesskey']]);
		}

		if ($row['open_in_new_window']) {
			$row['target'] = '_blank';
		}
	}
}