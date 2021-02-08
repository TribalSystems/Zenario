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

class zenario_users__organizer__groups extends zenario_users {

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		foreach ($panel['items'] as $id => &$item) {
			$sql = '
				SELECT COUNT(*)
				FROM '. DB_PREFIX. 'users_custom_data AS ucd
				INNER JOIN '. DB_PREFIX. 'users AS u
				   ON ucd.user_id = u.id
				'. ze\row::whereCol('users_custom_data', 'ucd', $item['db_column'], '=', 1, $first = true). '
				'. ze\row::whereCol('users', 'u', 'status', '!=', 'suspended');
			$item['members'] = (int) ze\sql::fetchValue($sql);
			
			$sql = '
				SELECT COUNT(*)
				FROM '.DB_PREFIX.'group_link gcl
				INNER JOIN '.DB_PREFIX.'content_items c
					ON c.status != \'trashed\'
					AND gcl.link_from_id = c.equiv_id
					AND gcl.link_from_char = c.type
				WHERE gcl.link_from = \'chain\'
				  AND gcl.link_to = \'group\'
				  AND gcl.link_to_id = '. (int) $id;
			$result = ze\sql::select($sql);
			$data = ze\sql::fetchRow($result);
			$item['content_items'] = $data[0];
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (($_POST['action'] ?? false) == 'delete') {
			ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
			ze\datasetAdm::deleteField($ids);
		}
	}
}