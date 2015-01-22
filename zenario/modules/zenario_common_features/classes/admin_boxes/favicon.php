<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


class zenario_common_features__admin_boxes__favicon extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$values['favicon/favicon'] = getRow('files', 'id', array('usage' => 'favicon'));
		$values['favicon/mobile_icon'] = getRow('files', 'id', array('usage' => 'mobile_icon'));
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($filepath = getPathOfUploadedFileInCacheDir($values['favicon/favicon'])) {
			$mimeType = documentMimeType($filepath);
			
			if ($mimeType == 'image/gif' || $mimeType == 'image/png'
			 || $mimeType == 'image/vnd.microsoft.icon' || $mimeType == 'image/x-icon') {
			} else {
				$fields['favicon/favicon']['error'] = adminPhrase('The favicon must be a .gif, .ico or a .png file.');
			}
		}
		
		if ($filepath = getPathOfUploadedFileInCacheDir($values['favicon/mobile_icon'])) {
			$mimeType = documentMimeType($filepath);
			
			if ($mimeType == 'image/gif' || $mimeType == 'image/png') {
			} else {
				$fields['favicon/mobile_icon']['error'] = adminPhrase('The home screen icon must be a .gif or a .png file.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			if (!$values['favicon/favicon']) {
				deleteRow('files', array('usage' => 'favicon'));
			
			} else
			if ($filepath = getPathOfUploadedFileInCacheDir($values['favicon/favicon'])) {
				$mimeType = documentMimeType($filepath);
				
				if ($mimeType == 'image/gif' || $mimeType == 'image/png'
				 || $mimeType == 'image/vnd.microsoft.icon' || $mimeType == 'image/x-icon') {
					deleteRow('files', array('usage' => 'favicon'));
					addFileToDatabase('favicon', $filepath);
				}
			}
			
			if (!$values['favicon/mobile_icon']) {
				deleteRow('files', array('usage' => 'mobile_icon'));
			
			} else
			if ($filepath = getPathOfUploadedFileInCacheDir($values['favicon/mobile_icon'])) {
				$mimeType = documentMimeType($filepath);
				
				if ($mimeType == 'image/gif' || $mimeType == 'image/png') {
					deleteRow('files', array('usage' => 'mobile_icon'));
					addFileToDatabase('mobile_icon', $filepath);
				}
			}
		}
	}
}
