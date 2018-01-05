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


if (is_array($instanceId)) {
	$instanceIdSQL = ' IN (' . ze\escape::in($instanceId) . ')';
} else {
	$instanceIdSQL = ' = ' . (int)$instanceId;
}

$layoutCount = $itemCount = 0;
$usage = "";

if ($instanceId) {
	//Get plugin layout usage
	$layoutSelectSQL = "
		SELECT COUNT(DISTINCT pll.layout_id)";
	$layoutBodySQL = "
		FROM " . DB_NAME_PREFIX . "plugin_layout_link AS pll
		INNER JOIN " . DB_NAME_PREFIX . "layouts AS l
			ON l.layout_id = pll.layout_id
		WHERE pll.instance_id " . $instanceIdSQL . "
			AND l.status = 'active'";
	$sql = $layoutSelectSQL . $layoutBodySQL;
	$result = ze\sql::select($sql);
	$row = ze\sql::fetchRow($result);
	$layoutCount = $row[0];

	//Get plugin item usage
	$itemSelectSQL = "
		SELECT COUNT(DISTINCT ciil.tag_id)";
	$itemBodySQL = "
		FROM " . DB_NAME_PREFIX . "plugin_item_link AS piil
		INNER JOIN " . DB_NAME_PREFIX . "content_items AS ciil
			ON ciil.id = piil.content_id
			AND ciil.type = piil.content_type
			AND piil.content_version IN (ciil.visitor_version, ciil.admin_version)
			AND ciil.status IN ('first_draft', 'published_with_draft', 'hidden_with_draft', 'trashed_with_draft', 'published', 'hidden')
			AND (piil.content_version, ciil.status) IN (
				(ciil.admin_version, 'first_draft'),
				(ciil.admin_version, 'hidden_with_draft'),
				(ciil.admin_version, 'trashed_with_draft'),
				(ciil.admin_version, 'published_with_draft'),
				(ciil.visitor_version, 'published_with_draft'),
				(ciil.visitor_version, 'published'),
				(ciil.admin_version - 1, 'hidden_with_draft'),
				(ciil.admin_version, 'hidden')
			)
		INNER JOIN " . DB_NAME_PREFIX . "content_item_versions AS viil
			ON viil.id = piil.content_id
			AND viil.type = piil.content_type
			AND viil.version = piil.content_version
		INNER JOIN " . DB_NAME_PREFIX . "layouts AS liil
			ON liil.layout_id = viil.layout_id
		INNER JOIN " . DB_NAME_PREFIX . "template_slot_link AS tiil
			ON tiil.family_name = liil.family_name
			AND tiil.file_base_name = liil.file_base_name
			AND tiil.slot_name = piil.slot_name
		WHERE piil.instance_id " . $instanceIdSQL;
	$sql = $itemSelectSQL . $itemBodySQL;
	$result = ze\sql::select($sql);
	$row = ze\sql::fetchRow($result);
	$itemCount = $row[0];
}

//Get an example
if ($itemCount > 0) {
	$itemSelectSQL = "
		SELECT piil.content_id, piil.content_type";
	$sql = $itemSelectSQL . $itemBodySQL;
	$result = ze\sql::select($sql);
	$row = ze\sql::fetchAssoc($result);
	$tag = ze\content::formatTag($row['content_id'], $row['content_type']);
	$usage .= '"' . $tag . '"';
} elseif ($layoutCount > 0) {
	$layoutSelectSQL = "
		SELECT l.file_base_name";
	$sql = $layoutSelectSQL . $layoutBodySQL;
	$result = ze\sql::select($sql);
	$row = ze\sql::fetchAssoc($result);
	$usage .= '"' . $row['file_base_name'] . '"';
}

//No usage
if ($itemCount == 0 && $layoutCount == 0) {
	$usage = 'Not used';
//Multiple content, no layout
} elseif ($itemCount > 1 && $layoutCount == 0) {
	$s = ($itemCount - 1 == 1) ? '' : 's';
	$usage .= ' and ' . ($itemCount - 1) . ' other item' . $s;
//Multiple content, layout
} elseif ($itemCount > 1 && $layoutCount > 0) {
	$s1 = ($itemCount - 1 == 1) ? '' : 's';
	$s2 = ($layoutCount == 1) ? '' : 's';
	$usage .= ', ' . ($itemCount - 1) . ' other item' . $s1 . ' and ' . $layoutCount . ' layout' . $s2;
//Single content, layout
} elseif ($itemCount == 1 && $layoutCount > 0) {
	$s = ($layoutCount == 1) ? '' : 's';
	$usage .= ' and ' . $layoutCount . ' layout' . $s;
//No content, layout
} elseif ($itemCount == 0 && $layoutCount > 1) {
	if ($layoutCount == 2) {
		$row = ze\sql::fetchAssoc($result);
		$usage .= ' and "' . $row['file_base_name'] . '"';
	} else {
		$usage .= ' and ' . ($layoutCount - 1) . ' other layouts';
	}
//No content, 1 layout
} elseif ($itemCount == 0 && $layoutCount == 1) {
	$usage .= ' layout';
}
return $usage;