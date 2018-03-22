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


class zenario_common_features__admin_boxes__page_preview_size extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($id = $box['key']['id']) {
			$pagePreviewSize = ze\row::get('page_preview_sizes', ['width', 'height', 'description', 'type'], $id);
			$box['title'] = ze\admin::phrase('Editing page preview size "[[width]] x [[height]] [[description]]"', 
				[
					'width' => $pagePreviewSize['width'],
					'height' => $pagePreviewSize['height'],
					'description' => $pagePreviewSize['description']]);
			$values['details/width'] = $pagePreviewSize['width'];
			$values['details/height'] = $pagePreviewSize['height'];
			$values['details/description'] = $pagePreviewSize['description'];
			$values['details/type'] = $pagePreviewSize['type'];
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		ze\priv::exitIfNot('_PRIV_EDIT_SITE_SETTING');
		
		if (!$id = $box['key']['id']) {
			$sql = 'SELECT max(ordinal) FROM '.DB_NAME_PREFIX.'page_preview_sizes';
			$result = ze\sql::select($sql);
			$row = ze\sql::fetchArray($result);
			$maxOrdinal = $row[0];
			$isDefault = 0;
			if ($maxOrdinal !== null) {
				$maxOrdinal = ++$maxOrdinal;
			} else {
				$isDefault = 1;
			}
			ze\row::insert(
				'page_preview_sizes', 
				[
					'width' => $values['details/width'], 
					'height' => $values['details/height'], 
					'description' => $values['details/description'],
					'ordinal' => (int)$maxOrdinal,
					'is_default' => $isDefault,
					'type' => $values['details/type']]);
		} else {
			ze\row::update(
				'page_preview_sizes', 
				[
					'width' => $values['details/width'],
					'height' => $values['details/height'],
					'description' => $values['details/description'],
					'type' => $values['details/type']]
				, $id);
		}
	}
}