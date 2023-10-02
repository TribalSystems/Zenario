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

class zenario_error_log__organizer__error_log extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$spareAliases = [];
		$sql = '
			SELECT el.id, sa.target_loc, sa.content_id, sa.content_type, sa.ext_url
			FROM ' . DB_PREFIX . 'spare_aliases sa
			INNER JOIN '. DB_PREFIX. 'error_404_log el
				ON sa.alias = el.page_alias';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$spareAliases[$row['id']] = $row;
		}

		$accessLogDuration = '';
		switch (ze::setting('period_to_delete_error_log')) {
			case 'never_delete':
				$accessLogDuration = ze\admin::phrase('Entries in the error log are stored forever.');
				break;
			case 90:
				$accessLogDuration = ze\admin::phrase('Entries in the error log are deleted after 3 months.');
				break;
			case 180:
				$accessLogDuration = ze\admin::phrase('Entries in the error log are deleted after 6 months.');
				break;
			case 365:
				$accessLogDuration = ze\admin::phrase('Entries in the error log are deleted after 1 year.');
				break;
			case 730:
				$accessLogDuration = ze\admin::phrase('Entries in the error log are deleted after 2 years.');
				break;
			case 1095:
				$accessLogDuration = ze\admin::phrase('Entries in the error log are deleted after 3 years.');
				break;
			
		}

		$link = ze\link::absolute() . 'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~t404_log~k{"id"%3A"data_protection"}';
		$accessLogDuration .= ' ' . "<a target='_blank' href='" . $link . "'>View error log settings</a>";
		$panel['notice']['show'] = true;
		$panel['notice']['message'] = $accessLogDuration.".";
		$panel['notice']['html'] = true;
		
		foreach($panel['items'] as $key => &$item) {
			if (isset($spareAliases[$key])) {
				if ($spareAliases[$key]['target_loc'] == 'int') {
					$formattedTagId = ze\content::formatTag($spareAliases[$key]['content_id'], $spareAliases[$key]['content_type'], false, false, true);
					$item['connected_spare_alias_destination'] = $formattedTagId;
				} else {
					$item['connected_spare_alias_destination'] = $spareAliases[$key]['ext_url'];
				}
			}
		}
	}
}