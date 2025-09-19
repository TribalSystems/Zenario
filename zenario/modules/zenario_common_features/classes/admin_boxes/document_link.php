<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


class zenario_common_features__admin_boxes__document_link extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fields['link/document_full_hyperlink']['hidden'] =
		$fields['link/document_internal_hyperlink']['hidden'] = true;
		
		if ($documentId = $box['key']['id']) {

			$documentDetails = ze\row::get('documents', ['chain_id', 'file_id', 'thumbnail_id', 'extract', 'extract_wordcount', 'title', 'filename', 'folder_name', 'privacy'],  $documentId);
			
			if ($documentDetails) {
				$fileInfo = ze\row::get('files', ['mime_type', 'short_checksum', 'location', 'path'], $documentDetails['file_id']);
				
				$documentName = $documentDetails['filename'];
				$box['title'] = ze\admin::phrase('Public links for document "[[filename]]"', ["filename" => $documentName]);
				
				$box['identifier']['value'] = ze\admin::phrase('Document ID [[id]], checksum "[[checksum]]"', ['id' => $documentId, 'checksum' => $fileInfo['short_checksum']]);
				
				if ($documentDetails['privacy'] == 'public') {
					$result = ze\document::generatePublicLink($documentId);
					$result = str_replace(' ', '%20', $result);
					
					$values['link/document_full_hyperlink'] = ze\link::absolute() . $result;
					$values['link/document_internal_hyperlink'] = $result;
					
					$fields['link/document_full_hyperlink']['hidden'] =
					$fields['link/document_internal_hyperlink']['hidden'] = false;
				}
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
