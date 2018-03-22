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

class zenario_user_forms__admin_boxes__import_user_forms extends ze\moduleBaseClass {
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		// Show save button on final tab
		if ($box['key']['show_save_button']) {
			$box['css_class'] = '';
		} else {
			$box['css_class'] = 'zenario_fab_default_style zenario_fab_hide_save_button';
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$box['key']['show_save_button'] = false;
		// Navigation
		switch ($box['tab']) {
			case 'file':
				if (!empty($fields['file/next']['pressed'])) {
					
					if (!$values['file/form_json']) {
						$box['tabs']['file']['errors'][] = ze\admin::phrase('Please upload an import file.');
						break;
					}
					
					$path = ze\file::getPathOfUploadInCacheDir($values['file/form_json']);
					$formJSONString = file_get_contents($path);
					
					if ($formJSONString === false) {
						$box['tabs']['file']['errors'][] = ze\admin::phrase('Failed to read file.');
						break;
					}
					
					$result = zenario_user_forms::validateImportForms($formJSONString);
					if (ze::isError($result)) {
						$box['tabs']['file']['errors'][] = (string)$result;
						break;
					}
					
					// Change to preview tab
					$box['tab'] = 'preview';
					$preview = &$fields['preview/preview']['snippet']['html'];
					$preview = '<h3>' . ze\admin::phrase('Import results preview') . '</h3><br><hr><br>';
					$canImport = true;
					
					$formJSON = json_decode($formJSONString, true);
					foreach ($formJSON['forms'] as $index => $data) {
						$preview .= '<h4>' . ze\admin::phrase('Form: "[[name]]"', ['name' => $data['form']['name']]) . '</h4><br>';
						if (!empty($result['errors'][$index])) {
							$canImport = false;
							$preview .= static::writePreviewList('Errors', $result['errors'][$index]);
						}
						if (!empty($result['warnings'][$index])) {
							$preview .= static::writePreviewList('Warnings', $result['warnings'][$index]);
						}
						if (!empty($result['errors'][$index])) {
							$preview .= '<p>' . ze\admin::phrase('This form will not be imported.') . '</p>';
						}
						if (empty($result['errors'][$index]) && empty($result['warnings'][$index])) {
							$preview .= '<p>' . ze\admin::phrase('No errors or warnings.') . '</p>';
						}
						$preview .= '<hr><br>';
					}
					
					if ($canImport) {
						$box['key']['show_save_button'] = true;
					}
					
				}
				break;
			case 'preview':
				if (!empty($fields['preview/previous']['pressed'])) {
					$box['tab'] = 'file';
				}
				break;
		}
	}
	
	public function writePreviewList($title, $messages) {
		$list = '<h5>' . ze\admin::phrase($title) . '</h5>';
		$list .= '<ul>';
		foreach ($messages as $message) {
			$list .= '<li>' . $message . '</li>';
		} 
		$list .= '</ul>';
		return $list;
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$path = ze\file::getPathOfUploadInCacheDir($values['file/form_json']);
		$formJSONString = file_get_contents($path);
		$rv = zenario_user_forms::importForms($formJSONString);
		if (is_numeric($rv)) {
			$box['key']['id'] = $rv;
		}
	}
}