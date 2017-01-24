<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__create_vlp extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (!$values['details/language_id']) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please enter a Language Code.');
		
		} elseif ($values['details/language_id'] != preg_replace('/[^a-z0-9_-]/', '', $values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code can only contain lower-case letters, numbers, underscores or hyphens.');
		
		} elseif (checkIfLanguageCanBeAdded($values['details/language_id'])) {
			$box['tabs']['details']['errors'][] = adminPhrase('The Language Code [[id]] already exists', array('id' => $values['details/language_id']));
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_CONFIG');
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => $values['details/english_name'],
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_ENGLISH_NAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => $values['details/language_local_name'],
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_LOCAL_NAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		setRow(
			'visitor_phrases',
			array(
				'local_text' => decodeItemIdForOrganizer($values['details/flag_filename']),
				'protect_flag' => 1),
			array(
				'code' => '__LANGUAGE_FLAG_FILENAME__',
				'language_id' => $values['details/language_id'],
				'module_class_name' => 'zenario_common_features'));
		
		$box['key']['id'] = $values['details/language_id'];
		
		$box['popout_message'] = '<!--Open_Admin_Box:zenario_setup_language//'. $values['details/language_id']. '-->';
		
	}
}
