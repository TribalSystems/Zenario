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

class zenario_user_forms__admin_boxes__user_form extends module_base_class {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fields['anti_spam/captcha_type']['values'] = array('word' => 'Words', 'math' => 'Maths');
		
		if (setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')) {
			$fields['anti_spam/captcha_type']['values']['pictures'] = 'Pictures';
		} else {
			$link = absCMSDirURL()."zenario/admin/organizer.php?#zenario__administration/panels/site_settings//captcha";
			$fields['anti_spam/captcha_type']['note_below'] = 'To enable pictures captcha (most friendly for the user)  please enter the <a href="' . $link. '" target="_blank">api key details</a>';
		}
		
		//Hide profanity settings checkbox if site setting is not checked
		$profanityFilterSetting = setting('zenario_user_forms_set_profanity_filter');
		
		if(!$profanityFilterSetting) {
			$fields['details/profanity_filter_text_fields']['hidden'] = true;
		}
		
		//Only allow extranet users to be made if module is running, otherwise only allow contacts
		if (!inc('zenario_extranet')) {
			$values['data/user_status'] = 'contact';
			$fields['data/user_status']['values']['active']['disabled'] = true;
			$fields['data/user_status']['values']['active']['note_below'] = adminPhrase('Extranet module must be running to create active users.');
			
			$fields['data/logged_in_user_section_start']['hidden'] = true;
			$fields['data/update_linked_fields']['hidden'] = true;
			$fields['data/no_duplicate_submissions']['hidden'] = true;
			$fields['data/duplicate_submission_message']['hidden'] = true;
			$fields['data/add_logged_in_user_to_group']['hidden'] = true;
		}
		$fields['data/add_user_to_group']['values'] = 
		$fields['data/add_logged_in_user_to_group']['values'] = 
			listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
		
		if (($_GET['refinerName'] ?? false) == 'archived') {
			foreach($box['tabs'] as &$tab) {
				$tab['edit_mode']['enabled'] = false;
			}
		}
		
		// Get default language english name
		$defaultLanguageName = false;
		$languages = getLanguages(false, true, true);
		foreach($languages as $language) {
			$defaultLanguageName = $language['english_name'];
			break;
		}
		if ($defaultLanguageName) {
			$fields['details/translate_text']['side_note'] = adminPhrase(
				'This will cause all displayable text from this form to be translated when used in a Forms plugin. This should be disabled if you enter non-[[default_language]] text into the form field admin boxes.', array('default_language' => $defaultLanguageName));
		}
		
		
		if ($id = $box['key']['id']) {
			
			// Fill form fields
			$record = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $id);
			
			if (!$record['partial_completion_message']) {
				$record['partial_completion_message'] = $values['partial_completion_message'];
			}
			if (!$record['clear_partial_data_message']) {
				$record['clear_partial_data_message'] = $values['clear_partial_data_message'];
			}	
			
			$this->fillFieldValues($fields, $record);
			
			$box['key']['type'] = $record['type'];
			$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $record['name']));
			
			if ($record['title'] !== null && $record['title'] !== '') {
				$values['details/show_title'] = true;
			}
			
			$values['data/admin_email_options'] = ($record['admin_email_use_template'] ? 'use_template' : 'send_data');
			$values['details/partial_completion_mode__auto'] = ($record['partial_completion_mode'] == 'auto' || $record['partial_completion_mode'] == 'auto_and_button');
			$values['details/partial_completion_mode__button'] = ($record['partial_completion_mode'] == 'button' || $record['partial_completion_mode'] == 'auto_and_button');
			
			if (!empty($record['redirect_after_submission'])) {
				$values['details/success_message_type'] = 'redirect_after_submission';
			} elseif (!empty($record['show_success_message'])) {
				$values['details/success_message_type'] = 'show_success_message';
			} else {
				$values['details/success_message_type'] = 'none';
			}
			
			// Find all text form fields from the selected form
			$formTextFieldLabels = array();
			$formEmailFieldLabels = array();
			$formTextFields = zenario_user_forms::getTextFormFields($box['key']['id']);
			
			foreach ($formTextFields as $formTextField) {
				$formTextFieldLabels[$formTextField['id']] = array(
					'ord' => $formTextField['ord'],
					'label' => $formTextField['name']
				);
				if ($formTextField['field_validation'] == 'email' || $formTextField['dataset_field_validation'] == 'email') {
					$formEmailFieldLabels[$formTextField['id']] = array(
						'ord' => $formTextField['ord'],
						'label' => $formTextField['name']
					);
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
				$fieldsToTranslate = array(
					'title' => $record['title'],
					'success_message' => $record['success_message'],
					'submit_button_text' => $record['submit_button_text'],
					'duplicate_email_address_error_message' => $record['duplicate_email_address_error_message']);
				
				// Get any existing phrases that translatable fields have
				$existingPhrases = array();
				foreach($fieldsToTranslate as $name => $value) {
					$phrases = getRows('visitor_phrases', 
						array('local_text', 'language_id'), 
						array('code' => $value, 'module_class_name' => 'zenario_user_forms'));
					while ($row = sqlFetchAssoc($phrases)) {
						$existingPhrases[$name][$row['language_id']] = $row['local_text'];
					}
				}
				$keys = array_keys($fieldsToTranslate);
				$lastKey = end($keys);
				$ord = 0;
				
				foreach($fieldsToTranslate as $name => $value) {
					
					// Create label for field with english translation (if set)
					$label = $fields[$name]['label'];
					$html = '<b>'.$label.'</b>';
					$readOnly = true;
					$sideNote = false;
					if (!empty($value)) {
						$html .= ' "'. $value .'"';
						$readOnly = false;
						$sideNote = adminPhrase('Text must be defined in the site\'s default language in order for you to define a translation');
					} else {
						$html .= ' (No text is defined in the default language)';
					}
					$box['tabs']['translations']['fields'] = array();
					$box['tabs']['translations']['fields'][$name] = array(
						'class_name' => 'zenario_user_forms',
						'ord' => $ord,
						'snippet' => array(
							'html' =>  $html));
					
					// Create an input box for each translatable language and look for existing phrases
					foreach($languages as $language) {
						if ($language['translate_phrases']) {
							$value = '';
							if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
								$value = $existingPhrases[$name][$language['id']];
							}
							$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = array(
								'class_name' => 'zenario_user_forms',
								'ord' => $ord++,
								'label' => $language['english_name']. ':',
								'type' => 'text',
								'value' => $value,
								'readonly' => $readOnly,
								'side_note' => $sideNote);
						}
					}
					
					// Add linebreak after each field
					if ($name != $lastKey) {
						$box['tabs']['translations']['fields'][$name.'_break'] = array(
							'class_name' => 'zenario_user_forms',
							'ord' => $ord,
							'snippet' => array(
								'html' => '<hr/>'));
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
			$box['title'] = adminPhrase('Creating a Form');
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
		
		$dataset = getDatasetDetails('users');
		$emailDatasetField = getDatasetFieldDetails('email', $dataset);
		if (!checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', array('user_form_id' => $box['key']['id'], 'user_field_id' => $emailDatasetField['id'])) && $box['key']['type'] != 'registration') {
			$fields['data/email_html']['hidden'] = false;
			$values['data/save_data'] = false;
			$fields['data/save_data']['disabled'] = true;
		}
		
		if (!$values['use_honeypot']) {
			$values['honeypot_label'] = 'Please don\'t type anything in this field';
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
		$fields['details/translate_text']['hidden'] = !checkRowExists('languages', array('translate_phrases' => 1));
		
		// Display translation boxes for translatable fields with a value entered
		$languages = getLanguages(false, true, true);
		$fieldsToTranslate = array('title', 'success_message', 'submit_button_text', 'duplicate_email_address_error_message');
		foreach($fieldsToTranslate as $fieldName) {
			$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields[$fieldName]['label'].'</b>';
			if (!empty($values[$fieldName])) {
				$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values[$fieldName].'"';
				$sideNote = false;
				$readOnly = false;
			} else {
				$sideNote = adminPhrase('Text must be defined in the site\'s default language in order for you to define a translation');
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
		
		$fields['details/redirect_location']['hidden'] = $values['details/success_message_type'] != 'redirect_after_submission';
		
		$fields['details/success_message']['hidden'] = $values['details/success_message_type'] != 'show_success_message';
		
		if (inc('zenario_extranet')) {
			$fields['data/duplicate_submission_message']['hidden'] = !$values['data/no_duplicate_submissions'];
		}
		
		if (!empty($box['key']['id'])) {
			$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $values['details/name']));
		}
		
		
		//Fields to hide if this is a registration form...
		if ($box['key']['type'] == 'registration') {
			$fields['details/success_message_type']['hidden'] = true;
			$fields['details/redirect_location']['hidden'] = true;
			$fields['details/success_message']['hidden'] = true;
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
			$errors[] = adminPhrase('Please enter a name for this Form.');
		} else {
			$sql = '
				SELECT id
				FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms
				WHERE name = "' . sqlEscape($values['details/name']) . '"';
			if ($box['key']['id']) {
				$sql .= ' 
					AND id != ' . (int)$box['key']['id'];
			}
			$result = sqlQuery($sql);
			if (sqlNumRows($result) > 0) {
				$errors[] = adminPhrase('The name "[[name]]" is used by another form.', array('name' => $values['details/name']));
			}
		}
		
		if ($values['details/allow_partial_completion'] && !$values['details/partial_completion_mode__auto'] && !$values['details/partial_completion_mode__button']) {
			$errors[] = adminPhrase('Please select a method to for the "Save and complete later" feature.');
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
			$errors[] = adminPhrase('This form is currently not using the data submitted in any way. Please select at least one of the following options.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
		
		$record = array();
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
		$record['captcha_type'] = ($values['use_captcha'] ? $values['captcha_type'] : 'word');
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
		}
		$record['enable_summary_page'] = $values['enable_summary_page'];
		
		if ($id = $box['key']['id']) {
			setRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $record, array('id' => $id));
			
			if (!$record['partial_completion_message']) {
				zenario_user_forms::deleteOldPartialResponse($id);
			}
			
			
			$formProperties = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('translate_text'), array('id' => $id));
			// Save translations
			if ($formProperties['translate_text']) { 
				$translatableFields = array('title', 'success_message', 'submit_button_text', 'duplicate_email_address_error_message');
				
				// Update phrase code if phrases are changed to keep translation chain
				$fieldsToTranslate = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $translatableFields, $id);
				$languages = getLanguages(false, true, true);
				
				foreach($fieldsToTranslate as $name => $oldCode) {
					// Check if old value has more than 1 entry in any translatable field
					$identicalPhraseFound = false;
					if($oldCode) {
						$sql = '
							SELECT '
								.sqlEscape(implode(', ', $translatableFields)).'
							FROM 
								'.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX . 'user_forms
							WHERE ( 
									title = "'.sqlEscape($oldCode).'"
								OR
									success_message = "'.sqlEscape($oldCode).'"
								OR
									submit_button_text = "'.sqlEscape($oldCode).'"
								OR
									duplicate_email_address_error_message = "'.sqlEscape($oldCode).'"
								)';
						$result = sqlSelect($sql);
						if (sqlNumRows($result) > 1) {
							$identicalPhraseFound = true;
						}
					}
					
					// If another field is using the same phrase code...
					if ($identicalPhraseFound) {
						foreach($languages as $language) {
							// Create or overwrite new phrases with the new english code
							$setArray = array('code' => $values[$name]);
							if (!empty($language['translate_phrases'])) {
								$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '') ? $values['translations/'.$name.'__'.$language['id']] : null;
							}
							setRow('visitor_phrases', 
								$setArray,
								array(
									'code' => $values[$name],
									'module_class_name' => 'zenario_user_forms',
									'language_id' => $language['id']));
						}
					} else {
						// If nothing else is using the same phrase code...
						if (!checkRowExists('visitor_phrases', array('code' => $values[$name], 'module_class_name' => 'zenario_user_forms'))) {
							updateRow('visitor_phrases', 
								array('code' => $values[$name]), 
								array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
							foreach($languages as $language) {
								if ($language['translate_phrases'] && !empty($values['translations/'.$name.'__'.$language['id']])) {
									setRow('visitor_phrases',
										array(
											'local_text' => ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null), 
										array(
											'code' => $values[$name], 
											'module_class_name' => 'zenario_user_forms', 
											'language_id' => $language['id']));
								}
								
							}
						// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
						} else {
							deleteRow('visitor_phrases', array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
							if (isset($values[$name]) && !empty($values[$name])) {
								foreach($languages as $language) {
									$setArray = array('code' => $values[$name]);
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
									}
									setRow('visitor_phrases',
										$setArray,
										array(
											'code' => $values[$name], 
											'module_class_name' => 'zenario_user_forms', 
											'language_id' => $language['id']));
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
			$formId = setRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $record, array());
			
			// Add default form fields for form types
			if ($box['key']['type'] == 'profile') {
				//TODO
			} elseif ($box['key']['type'] == 'registration') {
				//TODO
			}
			$box['key']['id'] = $formId;
		}
	}
	
}