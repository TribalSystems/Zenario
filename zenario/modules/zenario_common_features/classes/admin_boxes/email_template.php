<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class zenario_common_features__admin_boxes__email_template extends zenario_common_features {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$href = 'organizer.php#zenario__administration/panels/site_settings//email~.site_settings~ttemplate~k{"id"%3A"email"}';
		$mrg = ['standard_email_template_link' => 'View the <a href="' . htmlspecialchars($href) . '" target="_blank">standard email template</a>.'];
		ze\lang::applyMergeFields(
			$fields['meta_data/use_standard_email_template']['note_below'],
			$mrg);
				
		//Show site-setting value next to field and a link to the settings panel
		$values['meta_data/from_details'] = ze::setting('email_address_from') . ' (' . ze::setting('email_name_from') . ')';
		
		$linkStart = "<a href='".ze\link::absolute()."organizer.php?#zenario__administration/panels/site_settings//email~.site_settings~temail~k{\"id\"%3A\"email\"}' target='_blank'>";
		$linkEnd = "</a>";
		$fields['meta_data/from_details']['note_below'] = ze\admin::phrase('To change this, go to [[link_start]]site settings for email[[link_end]].', ['link_start' => $linkStart, 'link_end' => $linkEnd]);
		
		if ($box['key']['id']) {
			$details = $this->getTemplateByCode($box['key']['id']);
			
			if (empty($details)) {
				exit;
			}
			
			if ($box['key']['duplicate']) {
				$box['title'] = ze\admin::phrase('Duplicating the email template "[[template_name]]"', $details);
				unset($box['title_for_existing_records']);
				unset($box['identifier']);
			} else {
				$box['identifier']['value'] = $details['id'];
			}
			$box['key']['numeric_id'] = $details['id'];
			
			$values['meta_data/template_name'] = $details['template_name'];
			$values['meta_data/subject'] = $details['subject'];
			
			if ($details['debug_override']) {
				$values['meta_data/mode'] = 'debug';
				$values['meta_data/debug_email_address'] = $details['debug_email_address'];
			}
			
			$values['meta_data/send_cc'] = $details['send_cc'];
			$values['meta_data/cc_email_address'] = $details['cc_email_address'];
			$values['meta_data/send_bcc'] = $details['send_bcc'];
			$values['meta_data/bcc_email_address'] = $details['bcc_email_address'];
			
			$values['meta_data/include_a_fixed_attachment'] = $details['include_a_fixed_attachment'];
			if ($details['include_a_fixed_attachment']) {
				$values['meta_data/selected_attachment'] = $details['selected_attachment'];
			}
			$values['meta_data/allow_visitor_uploaded_attachments'] = $details['allow_visitor_uploaded_attachments'];
			$values['meta_data/when_sending_attachments'] = $details['when_sending_attachments'];
			
			switch ($details['use_standard_email_template']) {
				case 0:
					$values['meta_data/use_standard_email_template'] = 'no';
					break;
				case 1:
					$values['meta_data/use_standard_email_template'] = 'yes';
					break;
				case 2:
					$values['meta_data/use_standard_email_template'] = 'twig';
					break;
			}
			
			$values['meta_data/body'] = $details['body'];
			$values['meta_data/apply_css_rules'] = $details['apply_css_rules'];
			
			$values['data_deletion/period_to_delete_log_headers'] = $details['period_to_delete_log_headers'];
			if ($details['period_to_delete_log_content']) {
				$values['data_deletion/delete_log_content_sooner'] = true;
				$values['data_deletion/period_to_delete_log_content'] = $details['period_to_delete_log_content'];
			}
			
			$extraDetails = self::checkTemplateIsProtectedAndGetCreatedDetails($box['key']['id']);
			if ($extraDetails['created_by_class_name']) {
				$fields['protection/created_by']['snippet']['html'] = $this->phrase('Created by [[created_by_class_name]] ([[created_by_display_name]]) on [[date_created]].', $extraDetails);
			} elseif ($extraDetails['created_by_admin']) {
				$fields['protection/created_by']['snippet']['html'] = $this->phrase('Created manually by [[created_by_admin]] on [[date_created]].', $extraDetails);
			} 
			
			if ($extraDetails['protected']) {
				$fields['protection/template_protection_note']['snippet']['html'] = $this->phrase(
					'This template is protected, code name is [[code]].<br />Plugins of the [[created_by_display_name]] module rely on this email template for their functionality.',
					['code' => $details['code'], 'created_by_display_name' => $extraDetails['created_by_display_name']]
				);
			} else {
				$fields['protection/template_protection_note']['snippet']['html'] = $this->phrase('This template is not protected.');
			}
			
			$createdAndEditedInfo = [
				'last_edited' => $details['date_modified'],
				'last_edited_admin_id' => $details['modified_by_id'],
				'last_edited_user_id' => false,
				'last_edited_username' => false,
				'created' => $details['date_created'],
				'created_admin_id' => $details['created_by_id'],
				'created_user_id' => false,
				'created_username' => false
			];
			$box['last_updated'] = ze\admin::formatLastUpdated($createdAndEditedInfo);
		} else {
			$values['meta_data/use_standard_email_template'] = 'yes';
			
			$values['meta_data/email_address_from'] = ze::setting('email_address_from');
			$values['meta_data/email_name_from'] = ze::setting('email_name_from');
		}
		
		$styleFormats = ze\site::description('email_style_formats');
		if (!empty($styleFormats)) {
			//If custom email styles exist, load them and add them to the toolbar.
			//Please note: by default, the full featured editor DOES NOT have the styles dropdown.
			
			$fields['meta_data/body']['editor_options']['style_formats'] = 
			$fields['preview/body']['editor_options']['style_formats'] = $styleFormats;
		}
		
		//Set current admin's email as test send address
		$adminDetails = ze\admin::details(ze\admin::id());
		$values['meta_data/test_send_email_address'] = $adminDetails['admin_email'];
		
		//Show a warning if the scheduled task for deleting content is not running.
		if (!ze\module::inc('zenario_scheduled_task_manager') || !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobDataProtectionCleanup')) {
			$box['tabs']['data_deletion']['notices']['scheduled_task_not_running']['show'] = true;
		} else {
			$box['tabs']['data_deletion']['notices']['scheduled_task_running']['show'] = true;
		}
		
		//Show a warning if the email template is in debug mode
		if (ze::setting('debug_override_enable')) {
			$sendToDebugAddressOrDontSentAtAll = ze::setting('send_to_debug_address_or_dont_send_at_all');
			$box['tabs']['meta_data']['notices']['debug_mode']['show'] = true;

			if ($sendToDebugAddressOrDontSentAtAll == 'send_to_debug_email_address') {
				$box['tabs']['meta_data']['notices']['debug_mode']['message'] =
					ze\admin::phrase('This site is in email debug mode. Emails sent by this site will be redirected to "[[email]]".',
					['email' => trim(ze::setting('debug_override_email_address'))]);
			} elseif ($sendToDebugAddressOrDontSentAtAll == 'dont_send_at_all') {
				$box['tabs']['meta_data']['notices']['debug_mode']['message'] =
					ze\admin::phrase('Email debug mode is enabled, emails will not be sent at all.');
			}
		} elseif ($values['meta_data/mode'] == 'debug') {
			$box['tabs']['meta_data']['notices']['debug_mode']['show'] = true;
			$box['tabs']['meta_data']['notices']['debug_mode']['message'] = ze\admin::phrase('This email template is in debug mode. Emails sent with this template will be redirected to the specified email address.');
		} else {
			$box['tabs']['meta_data']['notices']['debug_mode']['show'] = false;
		}

		if (isset($fields['meta_data/test_send_button']) && !ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
			$fields['meta_data/test_send_button']['disabled'] = true;
			$fields['meta_data/test_send_button']['class'] = 'zenario_disabled_button';
		}
		
		$linkStart = "<a href='organizer.php#zenario__administration/panels/site_settings//email~.site_settings~tcss_rules~k{\"id\"%3A\"email\"}' target='_blank'>";
		$linkEnd = "</a>";
		ze\lang::applyMergeFields($fields['meta_data/apply_css_rules']['post_field_html'], ['link_start' => $linkStart, 'link_end' => $linkEnd]);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Try and ensure that we use absolute URLs where possible
		ze\contentAdm::addAbsURLsToAdminBoxField($fields['meta_data/body']);
		
		$fields['meta_data/selected_attachment']['notices_below']['attachment_is_deleted_or_offline']['hidden'] = true;
		if ($values['meta_data/include_a_fixed_attachment'] == true && $values['meta_data/selected_attachment']) {
			//Display a warning if the document is deleted or offline.
			$document = ze\row::get('documents', ['privacy'], ['id' => $values['meta_data/selected_attachment']]);
			
			if ($document) {
				if ($document['privacy'] == 'offline') {
					$fields['meta_data/selected_attachment']['notices_below']['attachment_is_deleted_or_offline']['hidden'] = false;
					$fields['meta_data/selected_attachment']['notices_below']['attachment_is_deleted_or_offline']['message'] = ze\admin::phrase(
						'The selected document is [[privateOrOffline]] and will not be sent. Please change its privacy settings, or choose a different document.', ['privateOrOffline' => $document['privacy']]
					);
				}
			} else {
				$fields['meta_data/selected_attachment']['notices_below']['attachment_is_deleted_or_offline']['hidden'] = false;
				$fields['meta_data/selected_attachment']['notices_below']['attachment_is_deleted_or_offline']['message'] = ze\admin::phrase(
					"The selected document is missing and will not be sent. Please choose a different document."
				);
			}
		} else {
			unset($fields['meta_data/selected_attachment']['note_below']);
		}
		
		//Show site-setting value if no template setting is set
		if ($values['data_deletion/period_to_delete_log_headers'] == ""
			&& ($siteWideSetting = ze::setting('period_to_delete_the_email_template_sending_log_headers'))
			&& (isset($fields['data_deletion/period_to_delete_log_headers']['values'][$siteWideSetting]))
		) {
			$fields['data_deletion/period_to_delete_log_headers']['post_field_html'] = '&nbsp;(' . $fields['data_deletion/period_to_delete_log_headers']['values'][$siteWideSetting]['label'] . ')';
		} else {
			$fields['data_deletion/period_to_delete_log_headers']['post_field_html'] = '';
		}
		
		//Use a code editor or a rich text editor depending on the options
		if ($values['meta_data/use_standard_email_template'] == 'twig') {
			$fields['meta_data/body']['type'] = 'code_editor';
		} else {
			$fields['meta_data/body']['type'] = 'editor';
		}
		
		//Watch out for when someone changes the format. Attempt to change the merge fields between each format (where this is simple to do).
		//If we switch email template to always use curly brackets, we can start to ignore this!
		if ($box['key']['lastFormatOption']
		 && $box['key']['lastFormatOption'] != $values['meta_data/use_standard_email_template']) {
			
			//Catch the case where they change it from Twig to HTML
			if ($box['key']['lastFormatOption'] == 'twig'
			 && $values['meta_data/use_standard_email_template'] != 'twig') {
				$values['meta_data/body'] = preg_replace('@\{\{(\s*\w+?\s*)\}\}@', '[[$1]]', $values['meta_data/body']);
			
			//Catch the case where they change it from HTML to Twig
			} else
			if ($box['key']['lastFormatOption'] != 'twig'
			 && $values['meta_data/use_standard_email_template'] == 'twig') {
				$values['meta_data/body'] = preg_replace('@\[\[(\s*\w+?\s*)\]\]@', '{{$1}}', $values['meta_data/body']);
			}
		}
		$box['key']['lastFormatOption'] = $values['meta_data/use_standard_email_template'];
		
		//Update body label
		if ($values['meta_data/use_standard_email_template'] == 'no') {
			$fields['meta_data/body_label']['label'] = ze\admin::phrase('Entire email:');
		} else {
			$fields['meta_data/body_label']['label'] = ze\admin::phrase('Email body:');
		}
		
		//Only show the preview when using the standard email template
		$box['tabs']['preview']['hidden'] = $values['meta_data/use_standard_email_template'] == 'no';
		
		//Show a preview if using the standard email template
		$values['preview/body'] = $values['meta_data/body'];
		static::putBodyInTemplate($values['preview/body']);
		
		if ($values['meta_data/apply_css_rules']) {
			$fields['preview/body']['editor_options']['content_style'] = $fields['meta_data/body']['editor_options']['content_style'] = ze::setting('email_css_rules');
		} else {
			unset($fields['preview/body']['editor_options']['content_style']);
			unset($fields['meta_data/body']['editor_options']['content_style']);
		}
		
		
		//Send a test email for the email template
		$box['tabs']['meta_data']['notices']['test_send_error']['show'] = false;
		$box['tabs']['meta_data']['notices']['test_send_sucesses']['show'] = false;
		$box['tabs']['meta_data']['notices']['test_send_attachment_not_sent']['show'] = false;
		if (($fields['meta_data/test_send_button']['pressed'] ?? false) && ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
			$error = '';
			$success = '';
			if (!$values['meta_data/test_send_email_address']) {
				$error = ze\admin::phrase('Please enter an email address.');
			} else {
				$adminDetails = ze\admin::details($_SESSION['admin_userid'] ?? false);
				
				//Try and ensure that we use absolute URLs where possible
				ze\contentAdm::addAbsURLsToAdminBoxField($fields['meta_data/body']);
				
				$body = $values['meta_data/body'];
				
				if ($values['meta_data/use_standard_email_template'] == 'twig') {
					$body = ze\twig::render("\n". $body, []);
				}
				
				if ($values['meta_data/apply_css_rules']) {
					$cssRules = ze::setting('email_css_rules');
					static::putHeadOnBody($cssRules, $body);
				}
				
				if ($values['meta_data/use_standard_email_template'] != 'no') {
					static::putBodyInTemplate($body);
				}
				
				$emailAddressFrom = ze::setting('email_address_from');
				$emailNameFrom = ze::setting('email_name_from');
				
				foreach (ze\ray::explodeAndTrim($values['meta_data/test_send_email_address']) as $email) {
					$attachments = [];
					if ($values['meta_data/include_a_fixed_attachment'] && $values['meta_data/selected_attachment']) {
						$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $values['meta_data/selected_attachment']]);
						
						if ($document) {
							if ($document['privacy'] != 'offline') {
								$file = ze\file::link($document['file_id']);
								$attachments[] = realpath(rawurldecode($file));
							} else {
								$box['tabs']['meta_data']['notices']['test_send_attachment_not_sent']['message'] = ze\admin::phrase('The selected attachment is [[privateOrOffline]] and will not be sent. Please change its privacy settings, or choose a different document.', ['privateOrOffline' => $document['privacy']]);
								$box['tabs']['meta_data']['notices']['test_send_attachment_not_sent']['show'] = true;
							}
						} else {
							$box['tabs']['meta_data']['notices']['test_send_attachment_not_sent']['message'] = ze\admin::phrase('The selected attachment is missing and will not be sent. Please choose a different document.');
							$box['tabs']['meta_data']['notices']['test_send_attachment_not_sent']['show'] = true;
						}
					}
					
					if (!ze\ring::validateEmailAddress($email)) {
						$error .= ($error? "\n" : ''). ze\admin::phrase('"[[email]]" is not a valid email address.', ['email' => $email]);
					} elseif (!$values['meta_data/body']) {
						$error .= ($error? "\n" : ''). ze\admin::phrase('The test email(s) could not be sent because your email body is blank.');
						break;
					} elseif ($box['key']['id'] 
						&& !static::testSendEmailTemplate(
							$box['key']['numeric_id'],
							$body,
							$adminDetails, 
							$email, 
							$values['meta_data/subject'], 
							$emailAddressFrom,
							$emailNameFrom,
							$attachments
						)
					) {
						$error .= ($error? "\n" : ''). ze\admin::phrase("The test email(s) could not be sent. There could be a problem with the site's email system.");
					} else {
						$success .= ($success? "\n" : ''). ze\admin::phrase('Test email sent to "[[email]]".', ['email' => $email]);
					}
				}
			}
			
			if ($error) {
				$box['tabs']['meta_data']['notices']['test_send_error']['show'] = true;
				$box['tabs']['meta_data']['notices']['test_send_error']['message'] = $error;
			}
			if ($success) {
				$box['tabs']['meta_data']['notices']['test_send_sucesses']['show'] = true;
				$box['tabs']['meta_data']['notices']['test_send_sucesses']['message'] = $success;
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			
			$key = ['template_name' => $values['meta_data/template_name']];
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$key['code'] = ['!' => $box['key']['id']];
			}
			
			if (ze\row::exists('email_templates', $key)) {
				$box['tabs']['meta_data']['errors'][] = ze\admin::phrase('That name is in use. Please ensure you give a unique name to this email template.');
			}
			
			$headersDays = $values['data_deletion/period_to_delete_log_headers'];
			$contentDays = $values['data_deletion/period_to_delete_log_content'];
			
			if ($values['data_deletion/delete_log_content_sooner']
				&& ((is_numeric($headersDays) && is_numeric($contentDays) && ($contentDays > $headersDays))
					|| (is_numeric($headersDays) && $contentDays == 'never_delete')
				)
			) {
				$fields['data_deletion/period_to_delete_log_content']['error'] = ze\admin::phrase('You cannot save content for longer than the headers.');
			}
			
			if ($values['meta_data/include_a_fixed_attachment'] == true && $values['meta_data/selected_attachment']) {
				$privacy = ze\row::get('documents', 'privacy', ['id' => $values['meta_data/selected_attachment']]);
				
				if (!$privacy) {
					$fields['meta_data/selected_attachment']['error'] = ze\admin::phrase('The selected document is missing and will not be sent. Please choose a different document.');
				} elseif ($privacy == 'offline') {
					$fields['meta_data/selected_attachment']['error'] = ze\admin::phrase('The selected document is [[privateOrOffline]] and will not be sent. Please change its privacy settings, or choose a different document.', ['privateOrOffline' => $privacy]);
				}
			}
			
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$values['meta_data/body'] = ze\ring::sanitiseWYSIWYGEditorHTML($values['meta_data/body'], $preserveMergeFields = true, $allowAdvancedInlineStyles = true);
	
		$files = [];
		$columns = [];
		
		if (ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['template_name'] = $values['meta_data/template_name'];
			$columns['subject'] = $values['meta_data/subject'];
			
			if ($values['meta_data/mode'] == 'debug') {
				$columns['debug_override'] = true;
				$columns['debug_email_address'] = $values['meta_data/debug_email_address'];
			} else {
				$columns['debug_override'] = false;
				$columns['debug_email_address'] = '';
			}

			$columns['cc_email_address'] =
				($columns['send_cc'] = $values['meta_data/send_cc'])? $values['meta_data/cc_email_address'] : '';

			$columns['bcc_email_address'] =
				($columns['send_bcc'] = $values['meta_data/send_bcc'])? $values['meta_data/bcc_email_address'] : '';
				
			$columns['include_a_fixed_attachment'] = $values['meta_data/include_a_fixed_attachment'];
			$columns['selected_attachment'] = ($values['meta_data/include_a_fixed_attachment']) ? $values['meta_data/selected_attachment'] : false;
			$columns['allow_visitor_uploaded_attachments'] = $values['meta_data/allow_visitor_uploaded_attachments'];
			$columns['when_sending_attachments'] = $values['meta_data/when_sending_attachments'];
			
			
			switch ($values['meta_data/use_standard_email_template']) {
				case 'no':
					$columns['use_standard_email_template'] = 0;
					break;
				case 'yes':
					$columns['use_standard_email_template'] = 1;
					break;
				case 'twig':
					$columns['use_standard_email_template'] = 2;
					break;
			}
			
			$htmlChanged = false;
			ze\fileAdm::addImageDataURIsToDatabase($values['meta_data/body'], ze\link::absolute());
			ze\contentAdm::syncInlineFileLinksWithoutTranscoding($files, $values['meta_data/body'], $htmlChanged);
			
			//Try and ensure that we use absolute URLs where possible
			ze\contentAdm::addAbsURLsToAdminBoxField($fields['meta_data/body']);
			
			$columns['body'] = $values['meta_data/body'];
		}
		
		if (ze\ring::engToBoolean($box['tabs']['data_deletion']['edit_mode']['on'] ?? false)) {
			ze\priv::exitIfNot('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['period_to_delete_log_headers'] = $values['data_deletion/period_to_delete_log_headers'];
			if ($values['data_deletion/period_to_delete_log_headers'] && $values['data_deletion/delete_log_content_sooner']) {
				$columns['period_to_delete_log_content'] = $values['data_deletion/period_to_delete_log_content'];
			} else {
				$columns['period_to_delete_log_content'] = '';
			}
		}
		
		$columns['apply_css_rules'] = $values['meta_data/apply_css_rules'];
		
		if (!empty($columns)) {
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				$columns['date_modified'] = ze\date::now();
				$columns['modified_by_id'] = ze\admin::id();
				ze\row::update('email_templates', $columns, ['code' => $box['key']['id']]);
			} else {
				$columns['date_created'] = ze\date::now();
				$columns['created_by_id'] = ze\admin::id();
				$columns['code'] = microtime(). session_id();
				$box['key']['id'] = $box['key']['numeric_id'] = ze\row::insert('email_templates', $columns);
				ze\row::update('email_templates', ['code' => $box['key']['id']], ['id' => $box['key']['id']]);
			}
		}
		
		//Record the images used in this email template.
		$key = ['foreign_key_to' => 'email_template', 'foreign_key_id' => $box['key']['numeric_id'], 'foreign_key_char' => $box['key']['id']];
		ze\contentAdm::syncInlineFiles($files, $key, $keepOldImagesThatAreNotInUse = false);
		
		//The image picker will not allow an admin to pick private or auto-detect images.
		//However, if one or more of such images were picked in the past for this email template,
		//make them public now.
		
		if (!empty($files)) {
			foreach ($files as $file) {
				ze\row::update('files', ['privacy' => 'public'], ['id' => $file['id']]);
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}