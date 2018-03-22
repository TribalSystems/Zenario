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

class zenario_user_forms__admin_boxes__site_settings extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($settingGroup == 'zenario_user_forms__site_settings_group') {
			$profanityCsvFilePath = CMS_ROOT . 'zenario/libs/not_to_redistribute/profanity-filter/profanities.csv';
			if(!file_exists($profanityCsvFilePath)) {
				ze\site::setSetting('zenario_user_forms_set_profanity_filter', '');
				ze\site::setSetting('zenario_user_forms_set_profanity_tolerence', '');
			
				$values['zenario_user_forms_set_profanity_tolerence'] = "";
				$values['zenario_user_forms_set_profanity_filter'] = "";
			
				$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['disabled'] = true;
				$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_tolerence']['disabled'] = true;
				$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['side_note'] = "";
				$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['note_below'] 
					= 'You must have a list of profanities on the server to enable this feature. The file must be called "profanities.csv" 
					and must be in the directory "zenario/libs/not_to_redistribute/profanity-filter/".';
			}
			
			$link = ze\link::absolute() . '/zenario/admin/organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$fields['zenario_user_forms_emails/data_protection_link']['snippet']['html'] = ze\admin::phrase('See the <a target="_blank" href="[[link]]">data protection</a> panel for settings on how long to store form responses.', ['link' => htmlspecialchars($link)]);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($settingGroup == 'data_protection') {
			//Make sure you cannot ask content to be stored longer than headers
			$headersDays = $values['data_protection/period_to_delete_the_form_response_log_headers'];
			$contentDays = $values['data_protection/period_to_delete_the_form_response_log_content'];
			
			if ($values['data_protection/delete_email_template_sending_log_content_sooner']
				&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
					|| (is_numeric($headersDays) && $contentDays == 'never_delete')
					|| ($headersDays == 'never_save' && $contentDays != 'never_save')
				)
			) {
				$fields['data_protection/period_to_delete_the_form_response_log_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
			}
		}
	} 
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($settingGroup == 'zenario_user_forms__site_settings_group') {
			if(empty($values['zenario_user_forms_set_profanity_filter'])) {
				$sql = "UPDATE ". DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX . "user_forms SET profanity_filter_text = 0";
				ze\sql::update($sql);
			}
		}
	}
	
}