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


if (get('refiner__template')) {
	$details = getRow('layouts', array('name', 'content_type'), get('refiner__template'));
	$panel['title'] = adminPhrase('Content using the Layout "[[name]]"', $details);
	$panel['no_items_message'] = adminPhrase('There is no Content using the Layout "[[name]]."', $details);
	
	foreach ($panel['items'] as $id => &$item) {
		$sql = "
			SELECT COUNT(*)
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = c.admin_version
			  AND v.layout_id = ". (int) get('refiner__template'). "
			WHERE c.language_id = '". sqlEscape($id). "'
			  AND c.status NOT IN ('trashed','deleted')
			  AND c.type = '". sqlEscape($details['content_type']). "'";
		
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		$item['item_count'] = $row[0];
	}

} elseif (get('refiner__content_type')) {
	$mrg = array(
		'ctype' => getContentTypeName(get('refiner__content_type')));
	$panel['title'] = adminPhrase('Content Items of the type "[[ctype]]"', $mrg);
	$panel['no_items_message'] = adminPhrase('There are no Content Items of the type "[[ctype]]".', $mrg);
	
	foreach ($panel['items'] as $id => &$item) {
		$sql = "
			SELECT COUNT(*)
			FROM ". DB_NAME_PREFIX. "content_items AS c
			WHERE c.language_id = '". sqlEscape($id). "'
			  AND c.status NOT IN ('trashed','deleted')
			  AND c.type = '". sqlEscape(get('refiner__content_type')). "'";
		
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		$item['item_count'] = $row[0];
	}

} else {
	unset($panel['allow_bypass']);
	
	if (!$refinerName) {
		foreach ($panel['items'] as $id => &$item) {
			$sql = "
				SELECT COUNT(*)
				FROM ". DB_NAME_PREFIX. "content_items AS c
				WHERE c.language_id = '". sqlEscape($id). "'
				  AND c.status NOT IN ('trashed','deleted')";
			
			$result = sqlQuery($sql);
			$row = sqlFetchRow($result);
			$item['item_count'] = $row[0];
		}
		
		//Count how many Content Equivalences exist in total
		$sql = "
			SELECT COUNT(DISTINCT equiv_id, type)
			FROM ". DB_NAME_PREFIX. "content_items
			WHERE status NOT IN ('trashed','deleted')";
		$result = sqlQuery($sql);
		$row = sqlFetchRow($result);
		$totalEquivs = $row[0];
		
		foreach ($panel['items'] as $id => &$item) {
			$item['item_count'] .= ' / '. $totalEquivs;
		}
	}
}

if (empty($panel['items']) && !checkRowExists('languages', array())) {
	foreach ($panel['collection_buttons'] as &$button) {
		$button['hidden'] = true;
	}
	$panel['no_items_message'] = adminPhrase('No Languages have been enabled. You must enable a Language before creating any Content Items.');

}

return false;