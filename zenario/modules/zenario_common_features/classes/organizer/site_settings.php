<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_common_features__organizer__site_settings extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__administration/panels/site_settings') return;
		
		//Either show the "site disabled" icon or the "site enabled" icon,
		//depending on whether the site is enabled or not
		if (setting('site_enabled')) {
			unset($panel['items']['site_disabled']);
		} else {
			unset($panel['items']['site_enabled']);
		}
		
		//If a favicon is set, change the icon of the favicon to that icon
		if (setting('favicon')
		 && ($icon = getRow('files', array('id', 'mime_type', 'filename', 'checksum'), setting('favicon')))) {
			if ($icon['mime_type'] == 'image/vnd.microsoft.icon' || $icon['mime_type'] == 'image/x-icon') {
				$url = fileLink($icon['id']);
			} else {
				$width = $height = $url = false;
				imageLink($width, $height, $url, $icon['id'], 24, 23);
			}
			$panel['items']['favicon']['list_image'] = $url;
		}
		
		//Same for the site logo and the rebranding
		if (setting('brand_logo') == 'custom' && setting('custom_logo')) {
			$width = $height = $url = false;
			imageLink($width, $height, $url, setting('custom_logo'), 24, 23);
			$panel['items']['branding']['list_image'] = $url;
		}
		
		//Add options for every installed content type
		foreach (getRowsArray(
			'content_types', array('content_type_name_en', 'content_type_plural_en'), array(), 'content_type_name_en'
		) as $cType => $details) {
			$details['content_type_plural_en'] = ifNull($details['content_type_plural_en'], $details['content_type_name_en']);
	
			$panel['items']['content_type_'. $cType] = array(
				'css_class' => 'content_type_'. $cType,
				'name' => adminPhrase('[[content_type_plural_en]]', $details),
				'desc' => adminPhrase('Settings for the content type "[[content_type_plural_en]]"', $details),
				'is_not_site_setting' => true,
				'is_ctype' => true
			);
		}

	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}