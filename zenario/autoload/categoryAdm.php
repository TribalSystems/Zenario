<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

class categoryAdm {

	//Formerly "setContentItemCategories()"
	public static function setContentItemCategories($cID, $cType, $categories) {
		$equivId = \ze\content::equivId($cID, $cType);
	
		\ze\row::delete('category_item_link', ['equiv_id' => $equivId, 'content_type' => $cType]);
	
		if (is_array($categories)) {
			foreach ($categories as $value) {
				if ($value) {
					\ze\row::insert('category_item_link', ['category_id' => $value, 'equiv_id' => $equivId, 'content_type' => $cType]);
				}
			}
		}
	}
	//Formerly "addSingleContentItemToCategories()"
	public static function addSingleContentItemToCategories($cID, $cType, $categories) {
		$equivId = \ze\content::equivId($cID, $cType);
	
		if (!is_array($categories)) {
			$categories = \ze\ray::explodeAndTrim($categories);
		}
	
		foreach ($categories as $id) {
			if ($id) {
				\ze\row::set('category_item_link', [], ['category_id' => $id, 'equiv_id' => $equivId, 'content_type' => $cType]);
			}
		}
	}
	//Formerly "addContentItemToCategories()"
	public static function addContentItemToCategories($cID, $cType, $categories) {
		$equivId = \ze\content::equivId($cID, $cType);
	
		if (!is_array($categories)) {
			$categories = \ze\ray::explodeAndTrim($categories);
		}
	
		foreach ($categories as $id) {
			if ($id) {
				\ze\row::set('category_item_link', [], ['category_id' => $id, 'equiv_id' => $equivId, 'content_type' => $cType]);
			}
		}
	}

	//Formerly "removeContentItemCategories()"
	public static function removeContentItemCategories($cID, $cType, $categories) {
		$equivId = \ze\content::equivId($cID, $cType);
		foreach ($categories as $value) {
			if ($value) {
				\ze\row::delete('category_item_link', ['category_id' => $value, 'equiv_id' => $equivId, 'content_type' => $cType]);
			}
		}		
	}



	//Formerly "setupCategoryCheckboxes()"
	public static function setupFABCheckboxes(&$field, $showTotals = false, $cID = false, $cType = false, $cVersion = false) {
		$field['values'] = [];
	
		$ord = 0;
		//$result = \ze\row::query('categories', ['id', 'parent_id', 'name'], [], 'name');
		$result = \ze\sql::select('
			SELECT id, parent_id, name
			FROM '. DB_PREFIX. 'categories
			ORDER BY name
		');
	
		while ($row = \ze\sql::fetchAssoc($result)) {
			$field['values'][$row['id']] = ['label' => $row['name'], 'parent' => $row['parent_id'], 'ord' => ++$ord];
		
			if ($showTotals) {
				$sql = "
					SELECT COUNT(DISTINCT c.id, c.type)
					FROM ". DB_PREFIX. "category_item_link AS cil
					INNER JOIN ". DB_PREFIX. "content_items AS c
					   ON c.equiv_id = cil.equiv_id
					  AND c.type = cil.content_type
					  AND c.status NOT IN ('trashed','deleted')
					WHERE cil.category_id = ". (int) $row['id'];
				$result2 = \ze\sql::select($sql);
				$row2 = \ze\sql::fetchRow($result2);
			
				$field['values'][$row['id']]['label'] .= ' ('. $row2[0]. ')';
			}
		}
	
		if ($cID && $cType && $cVersion) {
			$field['value'] = \ze\escape::in(\ze\row::getValues('category_item_link', 'category_id', ['equiv_id' => \ze\content::equivId($cID, $cType), 'content_type' => $cType]), true);
		}
	}

	//Formerly "countCategoryChildren()"
	public static function countChildren($id, $recurseCount = 0) {
		$count = 0;
		++$recurseCount;
	
		$sql = "SELECT id
				FROM " . DB_PREFIX . "categories
				WHERE parent_id = " . (int) $id;
			
		$result = \ze\sql::select($sql);
		while ($row = \ze\sql::fetchAssoc($result)) {
			++$count;
			if ($recurseCount<=10) {
				$count += \ze\categoryAdm::countChildren($row['id'], $recurseCount);
			}
		}
	
		return $count;
	}

	//Formerly "getCategoryAncestors()"
	public static function ancestors($id, &$categoryAncestors, $recurseCount = 0) {
		$recurseCount++;
	
		if ($parentId = \ze\row::get('categories', 'parent_id', $id)) {
			$categoryAncestors[] = $parentId;
		
			if ($recurseCount<=10) {
				\ze\categoryAdm::ancestors($parentId, $categoryAncestors, $recurseCount);
			}
		}
	}

	//Formerly "getCategoryPath()"
	public static function path($id) {
		$path = '';
		$categoryAncestors = [];
		\ze\categoryAdm::ancestors($id, $categoryAncestors);
	
		foreach ($categoryAncestors as $parentId) {
			if ($parentId) {
				$path = \ze\row::get('categories', 'name', $parentId). ' -> '. $path;
			}
		}
	
		return $path. \ze\row::get('categories', 'name', $id);
	}



	//Formerly "checkIfCategoryExists()"
	public static function exists($categoryName, $catId = false, $parentCatId = false) {

		$sql = "SELECT name
				FROM " . DB_PREFIX . "categories
				WHERE name = '" . \ze\escape::sql($categoryName) . "'";
	
		if ($catId) {
			$sql .= "
				  AND id != ". (int) $catId;
		}
	
		if ($parentCatId) {
			$sql .= " AND parent_id = " . (int) $parentCatId;
		}
	
		$result = \ze\sql::select($sql);
		return \ze\sql::numRows($result);
	}


}