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


class zenario_plugin_nest__organizer__plugins extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		switch ($refinerName) {
			case 'nests':
			case 'slideshows':
				
				foreach ($panel['items'] as $id => &$item) {
					$slides = max(1, 1 * ze\sql::fetchValue('
						SELECT COUNT(DISTINCT slide_num)
						FROM '. DB_PREFIX. 'nested_plugins
						WHERE instance_id = '. (int) $id
					));
					
					if ($slides > 1) {
						$item['contents'] = ze\lang::nphrase('1 slide', '[[count]] slides', $slides);
					
					} else {
						$modules = ze\sql::fetchRows('
							SELECT module_id, COUNT(*)
							FROM '. DB_PREFIX. 'nested_plugins
							WHERE instance_id = '. (int) $id. '
							  AND module_id != 0
							GROUP BY module_id
							ORDER BY 2 DESC'
						);
						
						$contents = [];
						
						foreach ($modules as $module) {
							$mrg = [];
							$mrg['display_name'] = ze\module::displayName($module[0]);
							$mrg['display_name_plural'] = ze\admin::pluralPhrase($mrg['display_name']);
							
							$contents[] = ze\lang::nphrase('1 [[display_name]]', '[[count]] [[display_name_plural]]', $module[1], $mrg);
						}
						
						if (empty($contents)) {
							$item['contents'] = ze\lang::phrase('Empty');
						} else {
							$item['contents'] = implode(', ', $contents);
						}
					}
				}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}