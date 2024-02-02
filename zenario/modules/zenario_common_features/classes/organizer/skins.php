<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__organizer__skins extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skins') return;
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skins') return;
		
			$panel['title'] = ze\admin::phrase('Skins');
			$panel['no_items_message'] = ze\admin::phrase('There are no skins available.');
		
		
		foreach ($panel['items'] as &$item) {
			$status = '';

			if ($item['missing'] && $item['usage_layouts']) {
				$status = ze\admin::phrase('Skin is missing from the file system but is referred to by some layouts');
			} elseif (!$item['missing'] && $item['usage_layouts']) {
				$status = ze\admin::phrase('Skin was found in the file system and is referred to by some layouts');
			} elseif ($item['missing'] && !$item['usage_layouts']) {
				$status = ze\admin::phrase('Skin is missing from the file system and is not referred to by any layouts');
			} elseif (!$item['missing'] && !$item['usage_layouts']) {
				$status = ze\admin::phrase('Skin was found in the file system but is not referred to by any layouts');
			}

			$item['status'] = $status;

			if ($item['missing']) {
				$item['path'] = ze\admin::phrase('Missing from file system');
				$item['link'] = false;
			}

			if ($item['usage_layouts'] > 0) {
				$item['usage_layouts'] = ze\admin::nPhrase('Used by 1 layout', 'Used by [[usage_layouts]] layouts', $item['usage_layouts'], ['usage_layouts' => $item['usage_layouts']]);
			} else {
				$item['usage_layouts'] = ze\admin::phrase('Not used by any layouts');
			}

			if (!$item['display_name']) {
				$item['display_name'] = $item['name'];
			}

			//Extensions - display if:

			//This skin extends a parent skin...
			$extensionsString = '';
			$parentSkin = ze\row::get('skins', 'extension_of_skin', ['name' => ze\escape::sql($item['name'])]);
			$item['extension_of_skin_display_name'] =  ze\row::get('skins', 'display_name', ['name' => ze\escape::sql($parentSkin)]);
			if ($item['extension_of_skin_display_name']) {
				$extensionsString .= 'Extension of skin [[parent_skin]]';
			}

			//and if any skins extend this one.
			$extendedBySkins = [];
			$sql = '
				SELECT display_name
				FROM ' . DB_PREFIX . 'skins
				WHERE extension_of_skin = "' . ze\escape::sql($item['name']) . '"
				ORDER BY display_name DESC';
			$result = ze\sql::select($sql);
			while ($skin = ze\sql::fetchValue($result)) {
				$extendedBySkins[] = $skin;
			}

			$extendedBySkinsCount = count($extendedBySkins);
			$item['extended_by_skins'] = implode(', ', $extendedBySkins);
			if ($item['extended_by_skins']) {
				//Add a line break if necessary.
				if ($item['extension_of_skin_display_name']) {
					$extensionsString .= '<br \>';
				}

				if ($extendedBySkinsCount > 1) {
					$extensionsString .= 'Extended by skins [[extended_by_skins]]';
				} elseif ($extendedBySkinsCount == 1) {
					$extensionsString .= 'Extended by skin [[extended_by_skins]]';
				}
			}

			$item['extensions'] = ze\admin::phrase($extensionsString, ['parent_skin' => $item['extension_of_skin_display_name'], 'extended_by_skins' => $item['extended_by_skins']]);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}