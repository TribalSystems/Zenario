<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
			$panel['title'] = ze\admin::phrase('Private content item access log for "[[user]]"', ['user' => ze\user::identifier($refinerId)]);
			// Set user Id to export button
			$panel['collection_buttons']['export']['admin_box']['key']['user_id'] = $refinerId;
		
		} elseif ($refinerName == 'content' && ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId)) {
			unset($panel['columns']['Content_Item']);
			unset($panel['columns']['Content_Item_Id']);
			unset($panel['columns']['Content_Item_Type']);
			unset($panel['columns']['Content_Item_Version']);
			$panel['title'] = ze\admin::phrase('User accesses to content item "[[tag]]"', ['tag' => ze\content::formatTag($cID, $cType)]);
			// Set tag Id to export button
			$panel['collection_buttons']['export']['admin_box']['key']['tag_id'] = $refinerId;
		}
		
		if ($mode != 'csv' && ($refinerName == 'user' || $refinerName == 'content' || $path == 'zenario__users/panels/access_log')) {
			//Information to view Data Protection settings
			$accessLogDuration = '';
			switch (ze::setting('period_to_delete_the_user_content_access_log')) {
				case 'never_delete':
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are stored forever.');
					break;
				case 0:
					$accessLogDuration = ze\admin::phrase('Private content item accesses are not recorded.');
					break;
				case 1:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 1 day.');
					break;
				case 7:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 1 week.');
					break;
				case 30:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 1 month.');
					break;
				case 90:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 3 months.');
					break;
				case 365:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 1 year.');
					break;
				case 730:
					$accessLogDuration = ze\admin::phrase('Entries in the private content item access log are deleted after 2 years.');
					break;
				
			}
			$link = ze\link::absolute(). 'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$accessLogDuration .= " <a target='_blank' href='". htmlspecialchars($link). "'>". ze\admin::phrase('View Data Protection settings'). "</a>";
			$panel['notice']['show'] = true;
			$panel['notice']['message'] = $accessLogDuration.".";
			$panel['notice']['html'] = true;
		}
		/*if($path == 'zenario__users/panels/access_log')
			{
				$panel['notice']['show'] = true;
				$panel['notice']['message'] = $accessLogDuration;
			}
		*/
		$panel['collection_buttons']['export']['admin_box']['key']['filename'] = $panel['title'];
	}
}
