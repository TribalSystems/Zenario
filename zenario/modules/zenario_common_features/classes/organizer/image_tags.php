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


class zenario_common_features__organizer__image_tags extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			$panel['columns']['name']['tag_colors'] = ze\contentAdm::getImageTagColours($byId = false, $byName = true);
		
			foreach ($panel['items'] as &$item) {
			
				//When showing the Organizer panel, try to find an image to show for each tag
				//Prefer images that haven't been used for many other tags
				$sql = "
					SELECT itl1.image_id, COUNT(itl2.tag_id)
					FROM ". DB_PREFIX. "image_tag_link as itl1
					INNER JOIN ". DB_PREFIX. "image_tag_link as itl2
					   ON itl1.image_id = itl2.image_id
					WHERE itl1.tag_id = ". (int) $item['id']. "
					GROUP BY itl1.image_id
					ORDER BY 2 ASC
					LIMIT 1";
		
				if (($result = ze\sql::select($sql))
				 && ($row = ze\sql::fetchRow($result))
				 && ($checksum = ze\row::get('files', 'checksum', $row[0]))) {
					$img = 'zenario/file.php?c='. $checksum;
					$item['image'] = $img. '&og=1';
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_MEDIA') && $ids) {
			$sql = "
				DELETE it.*, itl.*
				FROM ". DB_PREFIX. "image_tags as it
				LEFT JOIN ". DB_PREFIX. "image_tag_link as itl
				   ON it.id = itl.tag_id
				WHERE it.name IN (". ze\escape::in($ids, 'sql'). ")";
			
			ze\sql::update($sql);
		}
	}
}