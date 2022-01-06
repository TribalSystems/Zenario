<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class category {


	//Formerly "getCategoryName()"
	public static function name($id) {
		return \ze\row::get('categories', 'name', ['id' => $id]);
	}
	
	public static function codeName($id) {
		return \ze\row::get('categories', 'code_name', ['id' => $id]);
	}

	const publicNameFromTwig = true;
	//Formerly "categoryPublicName()"
	public static function publicName($catId, $languageId = false) {
		return \ze\lang::phrase('_CATEGORY_'. $catId, false, 'zenario_common_features', $languageId);
	}

	//Formerly "getContentItemCategories()"
	public static function contentItemCategories($cID, $cType, $publicOnly = false, $langId = false, $sql = false) {
	
		$equivId = \ze\content::equivId($cID, $cType);

		if ($sql === false) {
			$sql = "
				SELECT 
					c.id,
					c.parent_id,
					c.name,
					c.code_name,
					c.public,
					c.landing_page_equiv_id,
					c.landing_page_content_type
				FROM " . DB_PREFIX . "categories AS c
				INNER JOIN " . DB_PREFIX . "category_item_link AS cil
					ON c.id = cil.category_id
				WHERE cil.equiv_id = " . (int) $equivId . "
					AND cil.content_type = '" . \ze\escape::asciiInSQL($cType) . "'";
		}
	
		if ($publicOnly) {
			$sql .= "
				AND c.public = 1";
		}
				
		$result = \ze\sql::select($sql);
	
		if (\ze\sql::numRows($result)>0) {
			$categories = [];
			
			while ($row = \ze\sql::fetchAssoc($result)) {
				if (!$row['public']) {
					$row['public_name'] = false;
				} else {
					$row['public_name'] = \ze\lang::phrase('_CATEGORY_'. $row['id'], false, 'zenario_common_features', $langId);
				}
			
				$categories[] = $row;
			}
		
			return $categories;
		} else {
			return false;
		}
	}
}