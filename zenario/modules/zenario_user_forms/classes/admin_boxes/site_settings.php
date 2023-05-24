<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
			
			$link = ze\link::absolute() . '/organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$fields['zenario_user_forms_emails/data_protection_link']['snippet']['html'] = ze\admin::phrase('See the <a target="_blank" href="[[link]]">data protection</a> panel for settings on how long to store form responses.', ['link' => htmlspecialchars($link)]);
			
			//Max file upload size settings
			$apacheMaxFilesize = ze\dbAdm::apacheMaxFilesize();
			
			$zenarioMaxFilesizeValue = ze::setting('content_max_filesize');
			$zenarioMaxFilesizeUnit = ze::setting('content_max_filesize_unit');
			$zenarioMaxFilesize = ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
			
			$maxFileSize = min($apacheMaxFilesize, $zenarioMaxFilesize);
			$maxFileSizeFormatted = ze\file::fileSizeConvert($maxFileSize);
			
			ze\lang::applyMergeFields(
				$fields['zenario_user_forms_emails/zenario_user_forms_max_attachment_file_size_override']['values']['use_global_max_attachment_file_size']['label'],
				['global_max_attachment_file_size' => $maxFileSizeFormatted]
			);
			
			$linkStart = "<a target='_blank' href='" . ze\link::absolute() . '/organizer.php#zenario__administration/panels/site_settings//files_and_images~.site_settings~tfilesizes~k{"id"%3A"files_and_images"}' . "'>";
			$linkEnd = '</a>';
			$fields['zenario_user_forms_emails/zenario_user_forms_max_attachment_file_size_override']['notices_below']['max_upload_size_site_setting_link']['message'] = ze\admin::phrase(
				'See the [[link_start]]Documents, images and file handling[[link_end]] panel to change the global setting.',
				['link_start' => $linkStart, 'link_end' => $linkEnd]
			);
		
		} elseif ($settingGroup == 'data_protection') {
			
			//Show the number of form responses currently stored
			$count = ze\row::count(ZENARIO_USER_FORMS_PREFIX . 'user_response');
			$note = ze\admin::nphrase('1 record currently stored.', '[[count]] records currently stored.', $count);
						
			if ($count) {
				$min = ze\row::min(ZENARIO_USER_FORMS_PREFIX . 'user_response', 'response_datetime');
				$note .= ' ' . ze\admin::phrase('Oldest record from [[date]].', ['date' => ze\admin::formatDateTime($min, '_MEDIUM')]);
			}
			
			$link = ze\link::absolute() . 'organizer.php#zenario__user_forms/panels/user_forms';
			$note .= ' ' . '<a target="_blank" href="' . $link . '">View</a>';
			$fields['data_protection/period_to_delete_the_form_response_log_headers']['note_below'] = $note;
			
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($settingGroup == 'zenario_user_forms__site_settings_group') {
			//Max file upload size settings
			if ($values['zenario_user_forms_emails/zenario_user_forms_max_attachment_file_size_override'] == 'limit_max_attachment_file_size') {
				if ($values['zenario_user_forms_emails/zenario_user_forms_content_max_filesize'] && $values['zenario_user_forms_emails/zenario_user_forms_content_max_filesize_unit']) {
					$apacheMaxFilesize = ze\dbAdm::apacheMaxFilesize();
			
					$zenarioMaxFilesizeValue = ze::setting('content_max_filesize');
					$zenarioMaxFilesizeUnit = ze::setting('content_max_filesize_unit');
					$zenarioMaxFilesize = ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
			
					$maxFileSize = min($apacheMaxFilesize, $zenarioMaxFilesize);
					$maxFileSizeFormatted = ze\file::fileSizeConvert($maxFileSize);
			
					$userFormsMaxFilesizeValue = $values['zenario_user_forms_emails/zenario_user_forms_content_max_filesize'];
					$userFormsMaxFilesizeUnit = $values['zenario_user_forms_emails/zenario_user_forms_content_max_filesize_unit'];
					$userFormsMaxFilesize = ze\file::fileSizeBasedOnUnit($userFormsMaxFilesizeValue, $userFormsMaxFilesizeUnit);
			
					if ($userFormsMaxFilesize > $maxFileSize) {
						$fields['zenario_user_forms_emails/zenario_user_forms_content_max_filesize']['error'] =
							ze\admin::phrase(
								'The User Forms maximum file size may not exceed [[global_max_attachment_file_size]].',
								['global_max_attachment_file_size' => $maxFileSizeFormatted]
							);
						$fields['zenario_user_forms_emails/zenario_user_forms_content_max_filesize_unit']['error'] = true;
					}
				}
			}
		} elseif ($settingGroup == 'data_protection') {
			//Make sure you cannot ask content to be stored longer than headers
			$headersDays = $values['data_protection/period_to_delete_the_form_response_log_headers'];
			$contentDays = $values['data_protection/period_to_delete_the_form_response_log_content'];
			
			if ($values['data_protection/delete_email_template_sending_log_content_sooner']
				&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
					|| (is_numeric($headersDays) && $contentDays == 'never_delete')
				)
			) {
				$fields['data_protection/period_to_delete_the_form_response_log_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
			}
		}
	} 
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($settingGroup == 'zenario_user_forms__site_settings_group') {
			if(empty($values['zenario_user_forms_set_profanity_filter'])) {
				$sql = "UPDATE ". DB_PREFIX. ZENARIO_USER_FORMS_PREFIX . "user_forms SET profanity_filter_text = 0";
				ze\sql::update($sql);
			}
		}
	}
	
}
