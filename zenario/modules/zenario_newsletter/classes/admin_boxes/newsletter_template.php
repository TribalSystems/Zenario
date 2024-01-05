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


class zenario_newsletter__admin_boxes__newsletter_template extends zenario_newsletter {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($id = $box['key']['id']) {
			$templateDetails = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', ['name', 'head', 'body'], ['id' => $id]);
			$box['title'] = ze\admin::phrase('Editing the newsletter template "[[name]]"', ['name' => $templateDetails['name']]);
			$values['details/name'] = $templateDetails['name'];
			$values['details/body'] = $templateDetails['body'];
			$values['advanced/head'] = $templateDetails['head'];
		}
		
		$style_formats = ze\site::description('email_style_formats');
		if (!empty($style_formats)) {
			$box['tabs']['details']['fields']['body']['editor_options']['style_formats'] = $style_formats;
			$box['tabs']['details']['fields']['body']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Try and ensure that we use absolute URLs where possible
		ze\contentAdm::addAbsURLsToAdminBoxField($fields['details/body']);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$values['meta_data/body'] = ze\ring::sanitiseWYSIWYGEditorHTML($values['details/body'], true);
		
		//Try and ensure that we use absolute URLs where possible
		ze\contentAdm::addAbsURLsToAdminBoxField($fields['details/body']);
		
		$record = [
			'name' => $values['details/name'],
			'body' => $values['details/body'],
			'head' => $values['advanced/head']];
		
		if ($box['key']['id']) {
			$record['date_modified'] = ze\date::now();
			$record['modified_by_id'] = ze\admin::id();
		} else {
			$record['date_created'] = ze\date::now();
			$record['created_by_id'] = ze\admin::id();
		}
		
		$box['key']['id'] = ze\row::set(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', $record, $box['key']['id']);
		
		$body = $values['details/body'];
		$files = [];
		$htmlChanged = false;
		ze\file::addImageDataURIsToDatabase($body, ze\link::absolute());
		ze\contentAdm::syncInlineFileLinks($files, $body, $htmlChanged);
		ze\contentAdm::syncInlineFiles(
			$files,
			['foreign_key_to' => 'newsletter_template', 'foreign_key_id' => $box['key']['id']],
			$keepOldImagesThatAreNotInUse = false);
		
		if ($htmlChanged) {
			ze\row::set(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', ['body' => $body], $box['key']['id']);
		}
	}
}
