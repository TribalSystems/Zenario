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


switch ($path) {
	case 'zenario_email_template':
		
		$href = 'organizer.php#zenario__administration/panels/site_settings//email~.site_settings~ttemplate~k{"id"%3A"email"}';
		$mrg = ['standard_email_template_link' => 'View the <a href="' . htmlspecialchars($href) . '" target="_blank">standard email template</a>.'];
		ze\lang::applyMergeFields(
			$fields['meta_data/use_standard_email_template']['note_below'],
			$mrg);
				
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
			$values['meta_data/from_details'] = $details['from_details'];
						
			$values['meta_data/email_address_from'] = $details['email_address_from'];
			$values['meta_data/email_name_from'] = $details['email_name_from'];
			
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
		}
		
		$linkStart = "<a href='organizer.php#zenario__administration/panels/site_settings//email~.site_settings~tcss_rules~k{\"id\"%3A\"email\"}' target='_blank'>";
		$linkEnd = "</a>";
		ze\lang::applyMergeFields($fields['meta_data/apply_css_rules']['post_field_html'], ['link_start' => $linkStart, 'link_end' => $linkEnd]);
		
		break;
		
	case 'zenario_email_log_view':
		if ($box['key']['id']) {
			$logRecord = self::getLogRecordById($box['key']['id']);
			if (is_array($logRecord) && count($logRecord) > 0) {
				$box['title'] = 'Email sent to "' .  htmlspecialchars($logRecord['email_address_to'] ?? false) . '" on ' .  ze\admin::formatDate($logRecord['sent_datetime'] ?? false) . ' ' . ze\date::formatTime($logRecord['sent_datetime'] ?? false,ze::setting('vis_time_format'),'');
				$values['email_subject'] = $logRecord['email_subject'];
				$values['email_address_to'] = $logRecord['email_address_to'];
				$values['email_address_replyto'] = $logRecord['email_address_replyto'];
				$values['email_address_from'] = $logRecord['email_address_from'];
				$values['email_name_from'] = $logRecord['email_name_from'];
				$values['email_ccs'] = $logRecord['email_ccs'];
				
				$box['tabs']['email']['fields']['email_body_non_escaped']['snippet']['html'] = $logRecord['email_body'];
				
				
				//Display a one-liner showing where the email came from
				$info = [];
				$sayFrom = false;
				
				if (!empty($logRecord['content_id'])) {
					$link = ze\link::toItem($logRecord['content_id'], $logRecord['content_type']);
					$name = ze\content::formatTag($logRecord['content_id'], $logRecord['content_type']);
					$info[] = ze\admin::phrase('<a href="' . $link . '" target="_blank">' . $name . '</a>');
					$sayFrom = true;
				}
				
				if ($logRecord['module_id'] && ($module = ze\row::get('modules', ['class_name', 'display_name'], $logRecord['module_id']))) {
					$info[] = ze\admin::phrase('Module: [[class_name]] ([[display_name]])', $module);
					$sayFrom = true;
				}
				
				if ($logRecord['instance_id'] && ($instance = ze\row::get('plugin_instances', ['name', 'id'], $logRecord['instance_id']))) {
					$info[] = ze\admin::phrase('Plugin: P[[id]] ([[name]])', $instance);
					$sayFrom = true;
				}
				
				if ($logRecord['email_template_id'] && $logRecord['email_template_name']) {
					$template = ze\row::get('email_templates', ['id', 'code'], ['id' => $logRecord['email_template_id']]);
					$templateLink = ze\link::absolute() . 'organizer.php#zenario__email_template_manager/panels/email_templates//' . $template['code'];
					$info[] = ze\admin::phrase('Email template: <a href="' . $templateLink . '" target="_blank">' . $logRecord['email_template_name'] . '</a> (ID' . $template['id'] . ')');
				} else {
					$info[] = ze\admin::phrase('No template');
				}
				
				if (isset($logRecord['attachment_present'])) {
					if ($logRecord['attachment_present']) {
						$info[] = ze\admin::phrase('Attachment');
					} else {
						$info[] = ze\admin::phrase('No attachment');
					}
				}
				
				$html = '';
				if ($sayFrom) {
					$html .= ze\admin::phrase('Sent from:'). ' ';
				}
				$html .= implode('<br/>', $info);
				$fields['email/sent_form_text']['snippet']['html'] = $html;
			} else {
				exit('The requested ID was not found in the database. Please refresh Organizer and try again.');
			}
		}
		break;
	
	case 'site_settings':
		if ($settingGroup == 'data_protection') {
			
			//Show the number of sent emails currently stored
			$count = ze\row::count('email_template_sending_log');
			$note = ze\admin::nphrase('1 record currently stored.', '[[count]] records currently stored.', $count);
			
			if ($count) {
				$min = ze\row::min('email_template_sending_log', 'sent_datetime');
				$note .= ' ' . ze\admin::phrase('Oldest record from [[date]].', ['date' => ze\admin::formatDateTime($min, '_MEDIUM')]);
			}
			
			$link = ze\link::absolute() . 'organizer.php#zenario__email_template_manager/panels/email_log';
			$note .= ' ' . '<a target="_blank" href="' . $link . '">View</a>';
			$fields['data_protection/period_to_delete_the_email_template_sending_log_headers']['note_below'] = $note;
			
		}
		break;
}
