<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

class zenario_error_log__admin_boxes__delete_error_log extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['id']) { 
			$deleteAlias = ze\row::get(ZENARIO_ERROR_LOG_PREFIX.'error_log', 'page_alias', ['id' => $box['key']['id']]);
			if ($deleteAlias) {
				$aliasCount = ze\row::count(ZENARIO_ERROR_LOG_PREFIX.'error_log', ['page_alias' => $deleteAlias]);
				
				$box['tabs']['delete']['notices']['are_you_sure']['message'] = ze\admin::phrase('Are you sure you wish to delete [[number]] entries of the requested alias "[[alias]]"?', ['alias' => $deleteAlias, 'number' => $aliasCount]);
			}
		}
		
	}
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$deleteAliasLog = ze\row::get(ZENARIO_ERROR_LOG_PREFIX.'error_log', 'page_alias', ['id' => $box['key']['id']]);
		$sql = '
			DELETE FROM ' . DB_PREFIX . ZENARIO_ERROR_LOG_PREFIX . 'error_log
			WHERE page_alias = "' . ze\escape::sql($deleteAliasLog) . '"';
		ze\sql::update($sql);
	}
}