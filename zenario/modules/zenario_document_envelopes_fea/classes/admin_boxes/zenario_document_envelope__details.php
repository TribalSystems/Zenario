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


class zenario_document_envelopes_fea__admin_boxes__document_envelope__details extends zenario_document_envelopes_fea {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$envelopeId = $box['key']['id'];
		
		$fields['details/language_id']['values'] = ze\dataset::centralisedListValues('zenario_document_envelopes_fea::getEnvelopeLanguages');
		if ($envelopeId) {
			$envelopeData = ze\row::get(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', true, $envelopeId);
			ze\lang::applyMergeFields($box['title'], ['id' => $envelopeId]);
			$values['details/code'] = $envelopeData['code'];
			$values['details/name'] = $envelopeData['name'];
			$values['details/description'] = $envelopeData['description'];
			$values['details/keywords'] = $envelopeData['keywords'];
			$values['details/thumbnail_id'] = $envelopeData['thumbnail_id'];
			$values['details/language_id'] = $envelopeData['language_id'];
			$values['details/pinned'] = $envelopeData['pinned'];
			
			$box['last_updated'] = ze\admin::formatLastUpdated($envelopeData);
		} else {
			$box['title'] = ze\admin::phrase('Creating a document envelope');
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$envelopeId = $box['key']['id'];
		$details = [
			'code' => $values['details/code'],
			'name' => $values['details/name'],
			'description' => $values['details/description'],
			'keywords' => $values['details/keywords'],
			'language_id' => $values['details/language_id'],
			'pinned' => $values['details/pinned']
		];
		
		if (!empty($values['details/thumbnail_id'])) {
			if (is_numeric($values['details/thumbnail_id'])) {
				$details['thumbnail_id'] = $values['details/thumbnail_id'];
			} else {
				$thumbnailFilePath = ze\file::getPathOfUploadInCacheDir($values['details/thumbnail_id']);
				$thumbnailFileFilename = basename(ze\file::getPathOfUploadInCacheDir($values['details/thumbnail_id']));
				$details['thumbnail_id'] = ze\file::addToDatabase('image', $thumbnailFilePath, $thumbnailFileFilename);
			}
		} else {
			$details['thumbnail_id'] = null;
		}
		
		ze\admin::setLastUpdated($details, !$envelopeId);
		
		$box['key']['id'] = ze\row::set(ZENARIO_DOCUMENT_ENVELOPES_FEA_PREFIX . 'document_envelopes', $details, ($envelopeId ?? false));
	}
	
}