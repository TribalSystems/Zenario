<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
	case 'zenario_newsletter_template':
		
		addAbsURLsToAdminBoxField($box['tabs']['details']['fields']['body']);
		
		break;
		
		
	case 'zenario_newsletter':
		addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['body']);
		
		$box['tabs']['meta_data']['notices']['test_send_error']['show'] =
		$box['tabs']['meta_data']['notices']['test_send_sucesses']['show'] =
		$box['tabs']['unsub_exclude']['notices']['no_opt_out_group']['show'] = false;
		$clearCopyFromSourceFields = false;
		
		if (!empty($values['meta_data/add_user_field'])) {
			$fieldId = $values['meta_data/add_user_field'];
			$fieldDetails = getDatasetFieldDetails($fieldId, 'users');
			$values['meta_data/body'] .= trim($fieldDetails['label'], " \t\n\r\0\x0B:").': [['.$fieldDetails['db_column'].']]';
			$values['meta_data/add_user_field'] = '';
		}
		
		if (($values['meta_data/load_content_source'] == 'use_email_template')
			&& $values['meta_data/load_content_source_email_template'] 
				&& (engToBooleanArray($box,'tabs','meta_data','fields','load_content_continue','pressed') || (!$values['meta_data/body'])) ) {

			$clearCopyFromSourceFields = true;
			if (inc('zenario_email_template_manager')) {
				$email = zenario_email_template_manager::getTemplateByCode($values['meta_data/load_content_source_email_template']);
				$box['tabs']['meta_data']['fields']['body']['current_value'] = $email['body'];
			}
		}
		if (($values['meta_data/load_content_source'] == 'use_newsletter_template')
			&& $values['meta_data/load_content_source_newsletter_template'] 
				&& (engToBooleanArray($box,'tabs','meta_data','fields','load_content_continue','pressed') || (!$values['meta_data/body'])) ) {

			$clearCopyFromSourceFields = true;
			$email = getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', 'body', array('id' => $values['meta_data/load_content_source_newsletter_template']));
			$box['tabs']['meta_data']['fields']['body']['current_value'] = $email;
		}
		if (($values['meta_data/load_content_source'] == 'copy_from_archived_newsletter')
				&& $values['meta_data/load_content_source_archived_newsletter'] 
					&& (engToBooleanArray($box,'tabs','meta_data','fields','load_content_continue','pressed') || (!$values['meta_data/body'])) ) {

			$clearCopyFromSourceFields= true;
			$newsletter = $this->loadDetails($values['meta_data/load_content_source_archived_newsletter']);
			$box['tabs']['meta_data']['fields']['body']['current_value'] = $newsletter['body'];
		}

		if (engToBooleanArray($box,'tabs','meta_data','fields','load_content_cancel','pressed')) {
			$clearCopyFromSourceFields= true;
		}

		if ($clearCopyFromSourceFields) {
			$values['meta_data/load_content_source'] = 'nothing_selected';
			$box['tabs']['meta_data']['fields']['load_content_source']['current_value'] = 'nothing_selected';
			$box['tabs']['meta_data']['fields']['load_content_source_email_template']['current_value'] = '';
			$box['tabs']['meta_data']['fields']['load_content_source_newsletter_template']['current_value'] = '';
			$box['tabs']['meta_data']['fields']['load_content_source_archived_newsletter']['current_value'] = '';

			$box['tabs']['meta_data']['fields']['load_content_continue']['pressed'] = '';
			$box['tabs']['meta_data']['fields']['load_content_cancel']['pressed'] = '';
		}
		
		$box['tabs']['meta_data']['fields']['load_content_source_newsletter_template']['hidden']	
				= $values['meta_data/load_content_source'] != 'use_newsletter_template'; 
		
		$box['tabs']['meta_data']['fields']['load_content_source_email_template']['hidden']	
				= $values['meta_data/load_content_source'] != 'use_email_template'; 

		$box['tabs']['meta_data']['fields']['load_content_source_archived_newsletter']['hidden']	
				= $values['meta_data/load_content_source'] != 'copy_from_archived_newsletter'; 
				
		$box['tabs']['meta_data']['fields']['load_content_cancel']['hidden'] =
			$box['tabs']['meta_data']['fields']['load_content_continue']['hidden'] =
				!($values['meta_data/body'] 
					&& ($values['meta_data/load_content_source'] == 'use_email_template' && $values['meta_data/load_content_source_email_template'])
						|| ($values['meta_data/load_content_source'] == 'use_newsletter_template' && $values['meta_data/load_content_source_newsletter_template'])
							|| ($values['meta_data/load_content_source'] == 'copy_from_archived_newsletter' && $values['meta_data/load_content_source_archived_newsletter']));
				
		if ($values['unsub_exclude/add_unsubscribe_link'] && !setting('zenario_newsletter__all_newsletters_opt_out')) {
			$values['unsub_exclude/add_unsubscribe_link'] = false;
			$box['tabs']['unsub_exclude']['notices']['no_opt_out_group']['show'] = true;
			$box['tabs']['unsub_exclude']['notices']['no_opt_out_group']['message'] =
				adminPhrase('You must select an unsubscribe user characteristic in newsletter configuration settings');
			if (isset($box['tabs']['unsub_exclude']['fields']['exclude_recipients_with_opt_out'])) {
				unset($box['tabs']['unsub_exclude']['fields']['exclude_recipients_with_opt_out']);
			}
		}
		$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['hidden'] =
		$box['tabs']['unsub_exclude']['fields']['example_unsubscribe_url_underlined_and_hidden']['hidden'] =
			!$values['unsub_exclude/add_unsubscribe_link'];

		$box['tabs']['unsub_exclude']['fields']['delete_account_text']['hidden'] =
		$box['tabs']['unsub_exclude']['fields']['example_delete_account_url_underlined_and_hidden']['hidden'] =
			!$values['unsub_exclude/add_delete_account_link'];

		$box['tabs']['unsub_exclude']['fields']['exclude_previous_newsletters_recipients']['hidden'] =
			!$values['unsub_exclude/exclude_previous_newsletters_recipients_enable'];
		
		if (engToBooleanArray($box['tabs']['meta_data']['fields']['test_send_button'], 'pressed')) {
			$box['tabs']['meta_data']['notices']['test_send']['show'] = true;
			
			$error = '';
			$success = '';
			if (!$values['meta_data/test_send_email_address']) {
				$error = adminPhrase('Please enter an email address.');
			
			} else {
				$adminDetails = getAdminDetails(session('admin_userid'));
				foreach (explode(',', $values['meta_data/test_send_email_address']) as $email) {
					if ($email = trim($email)) {
						
						$body = $values['meta_data/body'];
						if ($values['unsub_exclude/add_unsubscribe_link']) {
							$body .= '<p>' . htmlspecialchars($values['unsub_exclude/unsubscribe_text']) . ' [[REMOVE_FROM_GROUPS_LINK]]</p>';
						}
						if ($values['unsub_exclude/add_delete_account_link']) {
							$body .= '<p>' . htmlspecialchars($values['unsub_exclude/delete_account_text']) . ' [[DELETE_ACCOUNT_LINK]]</p>';
						}
						
						if (!validateEmailAddress($email)) {
							$error .= ($error? "\n" : ''). adminPhrase('"[[email]]" is not a valid email address.', array('email' => $email));
						
						} elseif (!$values['meta_data/body']) {
							$error .= ($error? "\n" : ''). adminPhrase('The test email(s) could not be sent because your Newsletter is blank.');
							break;
						
						} else
						if (($box['key']['id']) &&!$this->testSendNewsletter(
							$body, $adminDetails, $email,
							$values['meta_data/subject'],
							$values['meta_data/email_address_from'],
							$values['meta_data/email_name_from'], $box['key']['id'])
						) {
							$error .= ($error? "\n" : ''). adminPhrase("The test email(s) could not be sent. There could be a problem with the site's email system.");
							break;
						
						} else {
							$success .= ($success? "\n" : ''). adminPhrase('Test email sent to "[[email]]".', array('email' => $email));
						}
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
		
		break;
}