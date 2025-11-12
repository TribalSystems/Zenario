<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

class zenario_common_features__admin_boxes__email_log_view extends zenario_common_features {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$logRecord = self::getLogRecordById($box['key']['id']);
			if (is_array($logRecord) && count($logRecord) > 0) {
				if ($logRecord['debug_mode']) {
					$fields['email/email_debug_was_on_at_the_time_of_sending']['hidden'] = false;
				}
				
				$box['title'] = ze\admin::phrase(
					'Email sent to "[[email_address_to]]" on [[sent_datetime]]',
					[
						'email_address_to' => htmlspecialchars($logRecord['email_address_to'] ?? false),
						'sent_datetime' => ze\admin::formatDate($logRecord['sent_datetime'] ?? false) . ' ' . ze\date::formatTime($logRecord['sent_datetime'] ?? false, ze::setting('vis_time_format'),'')
					]
				);
				
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
					if ($template) {
						$templateLink = ze\link::absolute() . 'organizer.php#zenario__email_template_manager/panels/email_templates//' . $template['code'];
						$info[] = ze\admin::phrase('Email template: <a href="' . $templateLink . '" target="_blank">' . $logRecord['email_template_name'] . '</a> (ID' . $template['id'] . ')');
					} else {
						$info[] = ze\admin::phrase('Email template: ID[[email_template_id]] (template not found)', ['email_template_id' => $logRecord['email_template_id']]);
					}
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
				
				$userFormsModuleIsRunning = ze\module::inc('zenario_user_forms');
				if ($logRecord['form_response_id'] && $userFormsModuleIsRunning) {
					$sql = "
						SELECT form.id
						FROM " . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . "user_forms AS form
						INNER JOIN " . DB_PREFIX . "user_response AS response
							ON form.id = response.form_id
						WHERE response.id = " . (int) $logRecord['form_response_id'];
					$result = ze\sql::select($sql);
					$userFormId = ze\sql::fetchValue($result);
					
					if ($userFormId) {
						$href = 'organizer.php#zenario__user_forms/panels/user_forms/item_buttons/view_responses//' . (int) $userFormId . '//' . (int) $logRecord['form_response_id'];
						$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
						$linkEnd = '</a>';
						
						$info[] = ze\admin::phrase(
							'[[link_start]]Form response ID [[form_response_id]][[link_end]]',
							['link_start' => $linkStart, 'form_response_id' => (int) $logRecord['form_response_id'], 'link_end' => $linkEnd]
						);
					} else {
						$info[] = ze\admin::phrase('Form response ID [[form_response_id]] (response deleted)', ['form_response_id' => (int) $logRecord['form_response_id']]);
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
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}