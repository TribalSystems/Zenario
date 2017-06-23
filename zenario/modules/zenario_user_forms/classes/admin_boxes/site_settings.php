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

class zenario_user_forms__admin_boxes__site_settings extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$profanityCsvFilePath = CMS_ROOT . 'zenario/libraries/not_to_redistribute/profanity-filter/profanities.csv';
		if(!file_exists($profanityCsvFilePath)) {
			setSetting('zenario_user_forms_set_profanity_filter', '');
			setSetting('zenario_user_forms_set_profanity_tolerence', '');
			
			$values['zenario_user_forms_set_profanity_tolerence'] = "";
			$values['zenario_user_forms_set_profanity_filter'] = "";
			
			$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['disabled'] = true;
			$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_tolerence']['disabled'] = true;
			$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['side_note'] = "";
			$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['note_below'] 
				= 'You must have a list of profanities on the server to enable this feature. The file must be called "profanities.csv" 
				and must be in the directory "zenario/libraries/not_to_redistribute/profanity-filter/".';
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if(empty($values['zenario_user_forms_set_profanity_filter'])) {
			$sql = "UPDATE ". DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX . "user_forms SET profanity_filter_text = 0";
			sqlQuery($sql);
		}
	}
	
}