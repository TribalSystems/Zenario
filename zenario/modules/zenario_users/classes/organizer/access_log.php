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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

class zenario_users__organizer__access_log extends zenario_users {
	
	
	public function formatStorekeeperCSV($path, &$item, $refinerName, $refinerId) {
		$item['Content_Item_Title'] =
		ze\content::title($item['Content_Item_Id'], $item['Content_Item_Type'], $item['Content_Item_Version']);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		ze\tuix::flagEncryptedColumns($panel, 'u', 'users');
	
		$cID = $cType = false;
	
		if ($mode == 'csv') {
			unset($panel['columns']['Content_Item']);
	
		} elseif ($refinerName == 'user') {
			unset($panel['columns']['User_Id']);
			unset($panel['columns']['Screen name']['title']);
			unset($panel['columns']['First_Name']);
			unset($panel['columns']['Last_Name']);
			unset($panel['columns']['Email']);
			unset($panel['columns']['Company_Name']);
			$panel['title'] = ze\admin::phrase('Access Log for the User "[[user]]"', ['user' => ze\user::identifier($refinerId)]);
			// Set user Id to export button
			$panel['collection_buttons']['export']['admin_box']['key']['user_id'] = $refinerId;
		
		} elseif ($refinerName == 'content' && ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId)) {
			unset($panel['columns']['Content_Item']);
			unset($panel['columns']['Content_Item_Id']);
			unset($panel['columns']['Content_Item_Type']);
			unset($panel['columns']['Content_Item_Version']);
			$panel['title'] = ze\admin::phrase('Access Log for the Content Item "[[tag]]"', ['tag' => ze\content::formatTag($cID, $cType)]);
			// Set tag Id to export button
			$panel['collection_buttons']['export']['admin_box']['key']['tag_id'] = $refinerId;
		}
		
		$panel['collection_buttons']['export']['admin_box']['key']['filename'] = $panel['title'];
	}
}
