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

class zenario_user_forms__admin_boxes__user_form extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!empty($box['key']['tab'])) {
			$box['tab'] = $box['key']['tab'];
		}
		
		$fields['anti_spam/captcha_type']['values'] = ['math' => 'Maths (Securimage)'];
		
		$link = ze\link::absolute()."zenario/admin/organizer.php?#zenario__administration/panels/site_settings//captcha";
		
		if (ze::setting('google_recaptcha_site_key') && ze::setting('google_recaptcha_secret_key')) {
			$fields['anti_spam/captcha_type']['values']['pictures'] = 'Pictures (Google reCaptcha 2.0)';
			$fields['anti_spam/captcha_type']['note_below'] = 'Captcha settings can be found in  <a href="' . $link. '" target="_blank">Site Settings</a>';
		} else {
			$fields['anti_spam/captcha_type']['note_below'] = 'To enable more kinds of captcha, please check your <a href="' . $link. '" target="_blank">API key details</a> and ensure all keys are completed.';
		}
		
		//Hide profanity settings checkbox if site setting is not checked
		$profanityFilterSetting = ze::setting('zenario_user_forms_set_profanity_filter');
		
		if(!$profanityFilterSetting) {
			$fields['details/profanity_filter_text_fields']['hidden'] = true;
		}
		
		//Hide options that handle logged in users if extranet module not running
		if (!ze\module::inc('zenario_extranet')) {			
			$fields['data/logged_in_user_section_start']['hidden'] = true;
			$fields['data/update_linked_fields']['hidden'] = true;
			$fields['data/no_duplicate_submissions']['hidden'] = true;
			$fields['data/duplicate_submission_message']['hidden'] = true;
			$fields['data/add_logged_in_user_to_group']['hidden'] = true;
		}
		$fields['data/add_user_to_group']['values'] = 
		$fields['data/add_logged_in_user_to_group']['values'] = 
			ze\datasetAdm::listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
		
		if (($_GET['refinerName'] ?? false) == 'archived') {
			foreach($box['tabs'] as &$tab) {
				$tab['edit_mode']['enabled'] = false;
			}
		}
		
		// Get default language english name
		$defaultLanguageName = false;
		$languages = ze\lang::getLanguages(false, true, true);
		foreach($languages as $language) {
			$defaultLanguageName = $language['english_name'];
			break;
		}
		if ($defaultLanguageName) {
			$fields['details/translate_text']['side_note'] = ze\admin::phrase(
				'This will cause all displayable text from this form to be translated when used in a Forms plugin. This should be disabled if you enter non-[[default_language]] text into the form field admin boxes.', ['default_language' => $defaultLanguageName]);
		}
		
		
		if ($id = $box['key']['id']) {
			
			// Fill form fields
			$record = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $id);
			
			if (!$record['partial_completion_message']) {
				$record['partial_completion_message'] = $values['partial_completion_message'];
			}
			if (!$record['clear_partial_data_message']) {
				$record['clear_partial_data_message'] = $values['clear_partial_data_message'];
			}
			if ($record['partial_completion_get_request']) {
				$record['enable_partial_completion_get_request'] = true;
			}
			if ($record['period_to_delete_response_content']) {
				$record['delete_content_sooner'] = true;
			}
			
			$record['error_message_position'] = $record['show_errors_below_fields'] ? 'below' : 'above';
			
			$this->fillFieldValues($fields, $record);
			
			$box['key']['type'] = $record['type'];
			$box['title'] = ze\admin::phrase('Editing settings for the form "[[name]]"', ['name' => $record['name']]);
			
			if ($record['title'] !== null && $record['title'] !== '') {
				$values['details/show_title'] = true;
			}
			
			$values['data/admin_email_options'] = ($record['admin_email_use_template'] ? 'use_template' : 'send_data');
			$values['details/partial_completion_mode__auto'] = ($record['partial_completion_mode'] == 'auto' || $record['partial_completion_mode'] == 'auto_and_button');
			$values['details/partial_completion_mode__button'] = ($record['partial_completion_mode'] == 'button' || $record['partial_completion_mode'] == 'auto_and_button');
			
			if (!empty($record['redirect_after_submission'])) {
				$values['data/success_message_type'] = 'redirect_after_submission';
			} elseif (!empty($record['show_success_message'])) {
				$values['data/success_message_type'] = 'show_success_message';
			} else {
				$values['data/success_message_type'] = 'none';
			}
			
			// Find all text form fields from the selected form
			$formTextFieldLabels = [];
			$formEmailFieldLabels = [];
			$formTextFields = zenario_user_forms::getTextFormFields($box['key']['id']);
			
			foreach ($formTextFields as $formTextField) {
				$formTextFieldLabels[$formTextField['id']] = [
					'ord' => $formTextField['ord'],
					'label' => $formTextField['name']
				];
				if ($formTextField['field_validation'] == 'email' || $formTextField['dataset_field_validation'] == 'email') {
					$formEmailFieldLabels[$formTextField['id']] = [
						'ord' => $formTextField['ord'],
						'label' => $formTextField['name']
					];
				}
			}
			
			if ($record['send_email_to_logged_in_user'] || $record['send_email_to_email_from_field']) {
				$values['data/send_email_to_user'] = true;
				if ($record['send_email_to_logged_in_user'] && !$record['user_email_use_template_for_logged_in_user']) {
					$values['data/user_email_options_logged_in_user'] = 'send_data';
				}
				if ($record['send_email_to_email_from_field'] && !$record['user_email_use_template_for_email_from_field']) {
					$values['data/user_email_options_from_field'] = 'send_data';
				}
			}
			
			$fields['data/user_email_field']['values'] = 
			$fields['data/reply_to_email_field']['values'] =
				$formEmailFieldLabels;
			
			$fields['data/reply_to_first_name']['values'] =
			$fields['data/reply_to_last_name']['values'] =
				$formTextFieldLabels;
			
			// Populate translations tab
			$translatableLanguage = false;
			foreach ($languages as $language) {
				if ($language['translate_phrases']) {
					$translatableLanguage = true;
				}
			}
			if ($translatableLanguage) {
				// Get translatable fields for this field type
				$fieldsToTranslate = [
					'title' => $record['title'],
					'success_message' => $record['success_message'],
					'submit_button_text' => $record['submit_button_text'],
					'duplicate_email_address_error_message' => $record['duplicate_email_address_error_message']];
				
				// Get any existing phrases that translatable fields have
				$existingPhrases = [];
				foreach($fieldsToTranslate as $name => $value) {
					$phrases = ze\row::query('visitor_phrases', 
						['local_text', 'language_id'], 
						['code' => $value, 'module_class_name' => 'zenario_user_forms']);
					while ($row = ze\sql::fetchAssoc($phrases)) {
						$existingPhrases[$name][$row['language_id']] = $row['local_text'];
					}
				}
				$keys = array_keys($fieldsToTranslate);
				$lastKey = end($keys);
				$ord = 0;
				
				$box['tabs']['translations']['fields'] = [];
				
				foreach($fieldsToTranslate as $name => $value) {
					// Create label for field with english translation (if set)
					$label = $fields[$name]['label'];
					$html = '<b>'.$label.'</b>';
					$readOnly = true;
					$sideNote = false;
					if (!empty($value)) {
						$html .= ' "'. $value .'"';
						$readOnly = false;
						$sideNote = ze\admin::phrase('Text must be defined in the site\'s default language in order for you to define a translation');
					} else {
						$html .= ' (No text is defined in the default language)';
					}
					
					$box['tabs']['translations']['fields'][$name] = [
						'ord' => $ord,
						'snippet' => [
							'html' =>  $html]];
					
					// Create an input box for each translatable language and look for existing phrases
					foreach($languages as $language) {
						if ($language['translate_phrases']) {
							$value = '';
							if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
								$value = $existingPhrases[$name][$language['id']];
							}
							$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = [
								'ord' => $ord++,
								'label' => $language['english_name']. ':',
								'type' => 'text',
								'value' => $value,
								'readonly' => $readOnly,
								'side_note' => $sideNote];
						}
					}
					
					// Add linebreak after each field
					if ($name != $lastKey) {
						$box['tabs']['translations']['fields'][$name.'_break'] = [
							'ord' => $ord,
							'snippet' => [
								'html' => '<hr/>']];
					}
					$ord++;
					$box['tabs']['translations']['hidden'] = $record['translate_text'];
				}
			} else {
				unset($box['tabs']['translations']);
			}
			
			if ($record['type'] == 'registration') {
				$values['data/verification_email_template'] = $record['verification_email_template'];
				$values['data/welcome_email_template'] = $record['welcome_email_template'];
				if ($record['welcome_message']) {
					$values['welcome_message'] = $record['welcome_message'];
				}
				$values['welcome_redirect_location'] = $record['welcome_redirect_location'];
				if ($record['welcome_redirect_location']) {
					$values['action_after_verification'] = 'redirect_after_submission';
				}
			}
			
		} else {
			unset($box['tabs']['translations']);
			$box['title'] = ze\admin::phrase('Creating a Form');
			if (!$box['key']['type']) {
				$values['data/save_record'] = true;
				$values['details/submit_button_text'] = 'Submit';
				$values['data/duplicate_email_address_error_message'] = 'Sorry this form has already been completed with this email address';
			} elseif ($box['key']['type'] == 'profile') {
				//TODO
			} elseif ($box['key']['type'] == 'registration') {
				$values['details/show_title'] = true;
				$values['details/title'] = 'Registration form';
				$values['details/submit_button_text'] = 'Register';
			}
		}
		
		$dataset = ze\dataset::details('users');
		$emailDatasetField = ze\dataset::fieldDetails('email', $dataset);
		if (!ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', ['user_form_id' => $box['key']['id'], 'user_field_id' => $emailDatasetField['id']]) && $box['key']['type'] != 'registration') {
			$fields['data/email_html']['hidden'] = false;
			$values['data/save_data'] = false;
			$fields['data/save_data']['disabled'] = true;
		}
		
		if (!$values['use_honeypot']) {
			$values['honeypot_label'] = 'Please don\'t type anything in this field';
		}
		
		//Show a warning if the scheduled task for deleting content is not running.
		if (!ze\module::inc('zenario_scheduled_task_manager') || !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobDataProtectionCleanup')) {
			$box['tabs']['data_deletion']['notices']['scheduled_task_not_running']['show'] = true;
		} else {
			$box['tabs']['data_deletion']['notices']['scheduled_task_running']['show'] = true;
		}
	}
	
	protected function fillFieldValues(&$fields, &$rec){
		foreach($rec as $k => $v){
			if ($v !== null && $v !== '') {
				$fields[$k]['value'] = $v;
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['details/translate_text']['hidden'] = !ze\row::exists('languages', ['translate_phrases' => 1]);
		
		// Display translation boxes for translatable fields with a value entered
		$languages = ze\lang::getLanguages(false, true, true);
		$fieldsToTranslate = ['title', 'success_message', 'submit_button_text', 'duplicate_email_address_error_message'];
		foreach($fieldsToTranslate as $fieldName) {
			$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields[$fieldName]['label'].'</b>';
			if (!empty($values[$fieldName])) {
				$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values[$fieldName].'"';
				$sideNote = false;
				$readOnly = false;
			} else {
				$sideNote = ze\admin::phrase('Text must be defined in the site\'s default language in order for you to define a translation');
				$readOnly = true;
				$fields['translations/'.$fieldName]['snippet']['html'] .= ' (No text is defined in the default language)';
			}
			foreach($languages as $language) {
				$fields['translations/'.$fieldName.'__'.$language['id']]['readonly'] = $readOnly;
				$fields['translations/'.$fieldName.'__'.$language['id']]['side_note'] = $sideNote;
			}
		}
		
		$box['tabs']['translations']['hidden'] = !$values['details/translate_text'];
		
		$fields['anti_spam/honeypot_label']['hidden'] = !$values['anti_spam/use_honeypot'];
		
		$fields['anti_spam/captcha_type']['hidden'] =
		$fields['anti_spam/extranet_users_use_captcha']['hidden'] =
			!$values['anti_spam/use_captcha'];
		
		
		
		$fields['data/user_status']['hidden'] =
		$fields['data/add_user_to_group']['hidden'] =
		$fields['data/duplicate_submission_html']['hidden'] =
		$fields['data/user_duplicate_email_action']['hidden'] =
			!$values['data/save_data'];
	
		$fields['data/create_another_form_submission_record']['hidden'] =
			!$values['data/save_data'] || ($values['data/user_duplicate_email_action'] == 'stop');
	
		$fields['data/duplicate_email_address_error_message']['hidden'] = 
			$fields['data/user_duplicate_email_action']['hidden']
			|| ($values['data/user_duplicate_email_action'] != 'stop');
		
		if (!ze\module::inc('zenario_extranet')) {
			$values['data/log_user_in'] = false;
			$fields['data/log_user_in']['disabled'] = true;
			$fields['data/log_user_in']['note_below'] = ze\admin::phrase('The extranet module must be running to enable this option.');
		}
		
		$fields['data/log_user_in_cookie']['hidden'] =
			!($values['data/save_data'] && ($values['data/log_user_in'] == 1) && ($values['data/user_status'] == 'active'));
	
		$fields['data/log_user_in']['hidden'] =
			!($values['data/save_data'] && ($values['data/user_status'] == 'active'));
		
		$fields['data/send_email_to_logged_in_user']['hidden'] = 
		$fields['data/send_email_to_email_from_field']['hidden'] = 
			!$values['data/send_email_to_user'];
		
		$fields['data/user_email_options_logged_in_user']['hidden'] = 
			!$values['data/send_email_to_user'] || !$values['data/send_email_to_logged_in_user'];
		
		$fields['data/user_email_template_logged_in_user']['hidden'] = 
			!$values['data/send_email_to_user'] || !$values['data/send_email_to_logged_in_user'] || ($values['data/user_email_options_logged_in_user'] != 'use_template');
		
		$fields['data/user_email_field']['hidden'] = 
		$fields['data/user_email_options_from_field']['hidden'] = 
			!$values['data/send_email_to_user'] || !$values['data/send_email_to_email_from_field'];
		
		$fields['data/user_email_template_from_field']['hidden'] = 
			!$values['data/send_email_to_user'] || !$values['data/send_email_to_email_from_field'] || ($values['data/user_email_options_from_field'] != 'use_template');
		
		$fields['data/admin_email_addresses']['hidden'] = 
		$fields['data/admin_email_options']['hidden'] = 
		$fields['data/reply_to']['hidden'] = 
			!$values['data/send_email_to_admin'];  
		
		$fields['data/admin_email_template']['hidden'] = 
			!($values['data/send_email_to_admin'] && ($values['data/admin_email_options'] == 'use_template'));
		
		$fields['data/reply_to_email_field']['hidden'] = 
		$fields['data/reply_to_first_name']['hidden'] = 
		$fields['data/reply_to_last_name']['hidden'] = 
			!($values['data/reply_to'] && $values['data/send_email_to_admin']);
		
		$fields['data/redirect_location']['hidden'] = $values['data/success_message_type'] != 'redirect_after_submission';
		
		$fields['data/success_message']['hidden'] = $values['data/success_message_type'] != 'show_success_message';
		
		if ($values['data_deletion/period_to_delete_response_headers'] == ""
			&& ($siteWideSetting = ze::setting('period_to_delete_the_form_response_log_headers'))
			&& isset($fields['data_deletion/period_to_delete_response_headers']['values'][$siteWideSetting])
		) {
			$fields['data_deletion/period_to_delete_response_headers']['post_field_html'] = '&nbsp;(' . $fields['data_deletion/period_to_delete_response_headers']['values'][$siteWideSetting]['label'] . ')';
		} else {
			$fields['data_deletion/period_to_delete_response_headers']['post_field_html'] = '';
		}
		
		if (ze\module::inc('zenario_extranet')) {
			$fields['data/duplicate_submission_message']['hidden'] = !$values['data/no_duplicate_submissions'];
		}
		
		if (!empty($box['key']['id'])) {
			$box['title'] = ze\admin::phrase('Editing settings for the form "[[name]]"', ['name' => $values['details/name']]);
		}
		
		
		//Fields to hide if this is a registration form...
		if ($box['key']['type'] == 'registration') {
			$fields['data/success_message_type']['hidden'] = true;
			$fields['data/redirect_location']['hidden'] = true;
			$fields['data/success_message']['hidden'] = true;
			$fields['details/profanity_filter_text_fields']['hidden'] = true;
			$fields['details/allow_partial_completion']['hidden'] = true;
			$fields['details/partial_completion_mode__auto']['hidden'] = true;
			$fields['details/partial_completion_mode__button']['hidden'] = true;
			$fields['details/partial_completion_message']['hidden'] = true;
			$fields['details/allow_clear_partial_data']['hidden'] = true;
			$fields['details/clear_partial_data_message']['hidden'] = true;
			$fields['details/enable_summary_page']['hidden'] = true;
			
			//Always create a user...
			$values['data/save_data'] = true;
			$fields['data/save_data']['readonly'] = true;
			$fields['data/user_status']['hidden'] = true;
			$fields['data/log_user_in']['hidden'] = true;
			$fields['data/log_user_in_cookie']['hidden'] = true;
			$fields['data/duplicate_submission_html']['hidden'] = true;
			$fields['data/user_duplicate_email_action']['hidden'] = true;
			$fields['data/duplicate_email_address_error_message']['hidden'] = true;
			
			$fields['data/logged_in_user_section_start']['hidden'] = true;
			$fields['data/update_linked_fields']['hidden'] = true;
			$fields['data/no_duplicate_submissions']['hidden'] = true;
			$fields['data/duplicate_submission_message']['hidden'] = true;
			$fields['data/add_logged_in_user_to_group']['hidden'] = true;
			
			$fields['data/line_br_2']['hidden'] = true;
			
			$fields['data/send_email_to_user']['hidden'] = true;
			$fields['data/user_email_options_logged_in_user']['hidden'] = true;
			$fields['data/user_email_template_logged_in_user']['hidden'] = true;
			$fields['data/send_email_to_email_from_field']['hidden'] = true;
			$fields['data/user_email_field']['hidden'] = true;
			$fields['data/user_email_options_from_field']['hidden'] = true;
			$fields['data/user_email_template_from_field']['hidden'] = true;
			
			$fields['data/line_br_3']['hidden'] = true;
			
			$fields['data/send_email_to_admin']['hidden'] = true;
			$fields['data/admin_email_addresses']['hidden'] = true;
			$fields['data/admin_email_options']['hidden'] = true;
			$fields['data/admin_email_template']['hidden'] = true;
			$fields['data/reply_to']['hidden'] = true;
			$fields['data/reply_to_email_field']['hidden'] = true;
			$fields['data/reply_to_first_name']['hidden'] = true;
			$fields['data/reply_to_last_name']['hidden'] = true;
			
			$fields['anti_spam/extranet_users_use_captcha']['hidden'] = true;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errors = &$box['tabs']['details']['errors'];
		
		if (empty($values['details/name'])) {
			$errors[] = ze\admin::phrase('Please enter a name for this Form.');
		} else {
			$sql = '
				SELECT id
				FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms
				WHERE name = "' . ze\escape::sql($values['details/name']) . '"';
			if ($box['key']['id']) {
				$sql .= ' 
					AND id != ' . (int)$box['key']['id'];
			}
			$result = ze\sql::select($sql);
			if (ze\sql::numRows($result) > 0) {
				$errors[] = ze\admin::phrase('The name "[[name]]" is used by another form.', ['name' => $values['details/name']]);
			}
		}
		
		if ($values['details/allow_partial_completion'] && !$values['details/partial_completion_mode__auto'] && !$values['details/partial_completion_mode__button']) {
			$errors[] = ze\admin::phrase('Please select a method to for the "Save and complete later" feature.');
		}
		
		$errors = &$box['tabs']['data']['errors'];
		// Create an error if the form is doing nothing with data
		if ($saving
			&& !$box['key']['type']
			&& empty($values['data/save_data'])
			&& empty($values['data/save_record'])
			&& empty($values['data/send_signal'])
			&& empty($values['data/send_email_to_user'])
			&& empty($values['data/send_email_to_admin'])) {
			$errors[] = ze\admin::phrase('This form is currently not using the data submitted in any way. Please select at least one of the following options.');
		}
		
		//Make sure you cannot ask content to be stored longer than headers
		$headersDays = $values['data_deletion/period_to_delete_response_headers'];
		$contentDays = $values['data_deletion/period_to_delete_response_content'];
		
		if ($values['data_deletion/delete_content_sooner']
			&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
				|| (is_numeric($headersDays) && $contentDays == 'never_delete')
				|| ($headersDays == 'never_save' && $contentDays != 'never_save')
			)
		) {
			$fields['data_deletion/period_to_delete_response_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_MANAGE_FORMS');
		
		$record = [];
		$record['name'] = $values['name'];
		
		$title = '';
		if ($values['show_title']) {
			$title = $values['title'];
		}
		$record['title'] = $title;
		$record['title_tag'] = $values['title_tag'];
		
		$record['use_honeypot'] = $values['use_honeypot'];
		if ($record['use_honeypot']) {
			$record['honeypot_label'] = $values['honeypot_label'];
		}
		
		$record['show_page_switcher'] = $values['show_page_switcher'];
		if ($record['show_page_switcher']) {
			$record['page_switcher_navigation'] = $values['page_switcher_navigation'];
		}
		
		$record['use_captcha'] = $values['use_captcha'];
		$record['captcha_type'] = ($values['use_captcha'] ? $values['captcha_type'] : null);
		$record['extranet_users_use_captcha'] = $values['extranet_users_use_captcha'];
		$record['admin_email_use_template'] = ($values['admin_email_options'] == 'use_template');
		
		
		$record['send_email_to_logged_in_user'] = 0;
		$record['user_email_use_template_for_logged_in_user'] = 0;
		$record['user_email_template_logged_in_user'] = null;
		$record['send_email_to_email_from_field'] = 0;
		$record['user_email_use_template_for_email_from_field'] = 0;
		$record['user_email_field'] = 0;
		$record['user_email_template_from_field'] = null;
		if ($values['data/send_email_to_user']) {
			if ($record['send_email_to_logged_in_user'] = $values['data/send_email_to_logged_in_user']) {
				if ($record['user_email_use_template_for_logged_in_user'] = ($values['data/user_email_options_logged_in_user'] == 'use_template')) {
					$record['user_email_template_logged_in_user'] = $values['data/user_email_template_logged_in_user'];
				}
			}
			if ($record['send_email_to_email_from_field'] = $values['data/send_email_to_email_from_field']) {
				$record['user_email_field'] = $values['data/user_email_field'];
				if ($record['user_email_use_template_for_email_from_field'] = ($values['data/user_email_options_from_field'] == 'use_template')) {
					$record['user_email_template_from_field'] = $values['data/user_email_template_from_field'];
				}
			}
		}
		
		
		$record['send_email_to_admin'] = (empty($values['send_email_to_admin']) ? 0 : 1);
		$record['admin_email_addresses'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_addresses']);
		$record['admin_email_template'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_template']);
		$removeReplyToFields = empty($values['reply_to']) || empty($values['send_email_to_admin']);
		$record['reply_to'] = ($removeReplyToFields ? 0 : 1);
		$record['reply_to_email_field'] = ($removeReplyToFields ? 0 : $values['reply_to_email_field']);
		$record['reply_to_first_name'] = ($removeReplyToFields ? 0 : $values['reply_to_first_name']);
		$record['reply_to_last_name'] = ($removeReplyToFields ? 0 : $values['reply_to_last_name']);
		$record['save_data'] = $values['save_data'];
		$record['save_record'] = $values['save_record'];
		$record['add_user_to_group'] = (empty($values['save_data']) ? null : $values['add_user_to_group']);
		$record['set_simple_access_cookie'] = (empty($values['set_simple_access_cookie']) ? 0 : 1);
		$record['simple_access_cookie_override_redirect'] = (empty($values['simple_access_cookie_override_redirect']) ? 0 : 1);
		$record['send_signal'] = (empty($values['send_signal']) ? 0 : 1);
		$record['show_success_message'] = ($values['success_message_type'] == 'show_success_message');
		$record['redirect_after_submission'] = ($values['success_message_type'] == 'redirect_after_submission');
		$record['redirect_location'] = (($values['success_message_type'] != 'redirect_after_submission') ? null : $values['redirect_location']);
		$record['success_message'] = (($values['success_message_type'] != 'show_success_message') ? null : $values['success_message']);
		$record['user_status'] = (empty($values['save_data']) ? 'contact' : $values['user_status']);
		$record['log_user_in'] = (empty($values['log_user_in']) ? 0 : 1);
		
		if($record['log_user_in']) {
			$record['log_user_in_cookie'] = (empty($values['log_user_in_cookie']) ? 0 : 1);
			
		} else {
			$record['log_user_in_cookie'] = 0;
		}
		
		//Make sure registration forms cannot update existing users
		if ($box['key']['type'] == 'registration') {
			$record['user_duplicate_email_action'] = 'ignore';
			$record['update_linked_fields'] = false;
			$record['add_logged_in_user_to_group'] = false;
			$record['verification_email_template'] = $values['verification_email_template'];
			$record['welcome_email_template'] = $values['welcome_email_template'];
			$record['welcome_message'] = $values['action_after_verification'] == 'show_welcome_message' ? $values['welcome_message'] : null;
			$record['welcome_redirect_location'] = $values['action_after_verification'] == 'redirect_after_submission' ? $values['welcome_redirect_location'] : null;
			
		} else {
			$record['user_duplicate_email_action'] = (empty($values['user_duplicate_email_action']) ? null : $values['user_duplicate_email_action']);
			$record['update_linked_fields'] = !empty($values['update_linked_fields']);
			$record['add_logged_in_user_to_group'] = $values['add_logged_in_user_to_group'];
		}
		
		$record['duplicate_submission_message'] = null;
		if ($record['no_duplicate_submissions'] = !empty($values['no_duplicate_submissions'])) {
			$record['duplicate_submission_message'] = mb_substr($values['duplicate_submission_message'], 0, 250);
		}
		
		$record['translate_text'] = (empty($values['translate_text']) ? 0 : 1);
		$record['submit_button_text'] = (empty($values['submit_button_text']) ? 'Submit' : $values['submit_button_text']);
		$record['duplicate_email_address_error_message'] = ($values['user_duplicate_email_action'] != 'stop') ? 'Sorry this form has already been completed with this email address' : $values['duplicate_email_address_error_message'];
		$record['profanity_filter_text'] = (empty($values['profanity_filter_text_fields']) ? 0 : 1);
		
		$record['allow_partial_completion'] = !empty($values['allow_partial_completion']);
		
		
		$record['partial_completion_mode'] = null;
		$record['partial_completion_message'] = null;
		$record['allow_clear_partial_data'] = 0;
		$record['clear_partial_data_message'] = null;
		$record['partial_completion_get_request'] = null;
		if (!empty($values['allow_partial_completion'])) {
			if ($values['partial_completion_mode__auto'] && $values['partial_completion_mode__button']) {
				$record['partial_completion_mode'] = 'auto_and_button';
			} elseif ($values['partial_completion_mode__auto']) {
				$record['partial_completion_mode'] = 'auto';
			} elseif ($values['partial_completion_mode__button']) {
				$record['partial_completion_mode'] = 'button';
			}
			
			if ($values['partial_completion_mode__button']) {
				$record['partial_completion_message'] = $values['partial_completion_message'];
			}
			
			if ($record['allow_clear_partial_data'] = !empty($values['allow_clear_partial_data'])) {
				$record['clear_partial_data_message'] = $values['clear_partial_data_message'];
			}
			
			if (!empty($values['enable_partial_completion_get_request'])) {
				$record['partial_completion_get_request'] = $values['partial_completion_get_request'];
			}
		}
		
		$record['enable_summary_page_required_checkbox'] = 0;
		$record['summary_page_required_checkbox_label'] = null;
		$record['summary_page_required_checkbox_error_message'] = null;
		$record['summary_page_lower_text'] = null;
		if ($record['enable_summary_page'] = $values['enable_summary_page']) {
			$record['summary_page_lower_text'] = $values['summary_page_lower_text'];
			if ($record['enable_summary_page_required_checkbox'] = $values['enable_summary_page_required_checkbox']) {
				$record['summary_page_required_checkbox_label'] = $values['summary_page_required_checkbox_label'];
				$record['summary_page_required_checkbox_error_message'] = $values['summary_page_required_checkbox_error_message'];
			}
		}
		
		$record['show_errors_below_fields'] = ($values['error_message_position'] == 'below');
		
		$record['period_to_delete_response_headers'] = $values['period_to_delete_response_headers'];
		if ($values['period_to_delete_response_headers'] && $values['delete_content_sooner']) {
			$record['period_to_delete_response_content'] = $values['period_to_delete_response_content'];
		} else {
			$record['period_to_delete_response_content'] = '';
		}
		
		
		if ($id = $box['key']['id']) {
			ze\row::set(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $record, ['id' => $id]);
			
			if (!$record['partial_completion_message']) {
				zenario_user_forms::deleteOldPartialResponse($id);
			}
			
			
			$formProperties = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['translate_text'], ['id' => $id]);
			// Save translations
			if ($formProperties['translate_text']) { 
				$translatableFields = ['title', 'success_message', 'submit_button_text', 'duplicate_email_address_error_message'];
				
				// Update phrase code if phrases are changed to keep translation chain
				$fieldsToTranslate = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $translatableFields, $id);
				$languages = ze\lang::getLanguages(false, true, true);
				
				foreach($fieldsToTranslate as $name => $oldCode) {
					// Check if old value has more than 1 entry in any translatable field
					$identicalPhraseFound = false;
					if($oldCode) {
						$sql = '
							SELECT '
								.ze\escape::sql(implode(', ', $translatableFields)).'
							FROM 
								'.DB_PREFIX. ZENARIO_USER_FORMS_PREFIX . 'user_forms
							WHERE ( 
									title = "'.ze\escape::sql($oldCode).'"
								OR
									success_message = "'.ze\escape::sql($oldCode).'"
								OR
									submit_button_text = "'.ze\escape::sql($oldCode).'"
								OR
									duplicate_email_address_error_message = "'.ze\escape::sql($oldCode).'"
								)';
						$result = ze\sql::select($sql);
						if (ze\sql::numRows($result) > 1) {
							$identicalPhraseFound = true;
						}
					}
					
					// If another field is using the same phrase code...
					if ($identicalPhraseFound) {
						foreach($languages as $language) {
							// Create or overwrite new phrases with the new english code
							$setArray = ['code' => $values[$name]];
							if (!empty($language['translate_phrases'])) {
								$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '') ? $values['translations/'.$name.'__'.$language['id']] : null;
							}
							ze\row::set('visitor_phrases', 
								$setArray,
								[
									'code' => $values[$name],
									'module_class_name' => 'zenario_user_forms',
									'language_id' => $language['id']]);
						}
					} else {
						// If nothing else is using the same phrase code...
						if (!ze\row::exists('visitor_phrases', ['code' => $values[$name], 'module_class_name' => 'zenario_user_forms'])) {
							ze\row::update('visitor_phrases', 
								['code' => $values[$name]], 
								['code' => $oldCode, 'module_class_name' => 'zenario_user_forms']);
							foreach($languages as $language) {
								if ($language['translate_phrases'] && !empty($values['translations/'.$name.'__'.$language['id']])) {
									ze\row::set('visitor_phrases',
										[
											'local_text' => ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null], 
										[
											'code' => $values[$name], 
											'module_class_name' => 'zenario_user_forms', 
											'language_id' => $language['id']]);
								}
								
							}
						// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
						} else {
							ze\row::delete('visitor_phrases', ['code' => $oldCode, 'module_class_name' => 'zenario_user_forms']);
							if (isset($values[$name]) && !empty($values[$name])) {
								foreach($languages as $language) {
									$setArray = ['code' => $values[$name]];
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
									}
									ze\row::set('visitor_phrases',
										$setArray,
										[
											'code' => $values[$name], 
											'module_class_name' => 'zenario_user_forms', 
											'language_id' => $language['id']]);
								}
							}
						}
					}
				}
			}
		} else {
			$record['type'] = 'standard';
			if ($box['key']['type']) {
				$record['type'] = $box['key']['type'];
			}
			$formId = ze\row::set(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $record, []);
			
			// Add default form fields for form types
			if (!$box['key']['id']) {
				if ($box['key']['type'] == 'profile') {
					//TODO
				} elseif ($box['key']['type'] == 'registration') {
					//Create 1st page
					$pageId = ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'pages', ['form_id' => $formId, 'ord' => 1, 'name' => 'Page 1']);
					
					//salutation, first_name, last_name, email
					$dataset = ze\dataset::details('users');
					$fields = [
						'salutation' => [],
						'first_name' => ['required' => true],
						'last_name' => ['required' => true],
						'email' => ['required' => true]
					];
					$i = 0;
					foreach ($fields as $fieldName => $field) {
						$datasetField = ze\dataset::fieldDetails($fieldName, $dataset);
						$name = $datasetField['label'] ? $datasetField['label'] : $datasetField['default_label'];
						
						$details = [
							'user_form_id' => $formId, 
							'page_id' => $pageId, 
							'user_field_id' => $datasetField['id'],
							'ord' => ++$i,
							'field_type' => $datasetField['type'],
							'name' => $name,
							'label' => $name
						];
						if (!empty($field['required'])) {
							$details['is_required'] = true;
							$details['required_error_message'] = "This field is required.";
						}
						
						ze\row::insert(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $details);
					}
				
				}
			}
			$box['key']['id'] = $formId;
		}
	}
	
}
