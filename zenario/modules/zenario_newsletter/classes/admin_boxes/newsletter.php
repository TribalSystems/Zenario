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


class zenario_newsletter__admin_boxes__newsletter extends zenario_newsletter {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$style_formats = siteDescription('email_style_formats');
		if (!empty($style_formats)) {
			$box['tabs']['meta_data']['fields']['body']['editor_options']['style_formats'] = $style_formats;
			$box['tabs']['meta_data']['fields']['body']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
		
		//We're using an Organizer picker to select smart groups
		//This code would be needed if it was a select list instead
		//$fields['unsub_exclude/recipients']['values'] = getListOfSmartGroupsWithCounts('smart_newsletter_group');
		
		$box['tabs']['unsub_exclude']['fields'] = &$box['tabs']['unsub_exclude']['fields'];
		
		$adminDetails = getAdminDetails(adminId());
		$values['meta_data/test_send_email_address'] = $adminDetails['admin_email'];
		$box['tabs']['meta_data']['fields']['add_user_field']['values'] =
			listCustomFields('users', $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
		
		
		
		if ($box['key']['id']) {
			$details = $this->loadDetails($box['key']['id']);
			$box['title'] = adminPhrase('Viewing/Editing newsletter "[[newsletter_name]]"', $details);
			
			$values['meta_data/newsletter_name'] = $details['newsletter_name'];
			$values['unsub_exclude/recipients'] = 
					inEscape(
						getRowsArray(
							ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link',
							'smart_group_id',
							array('newsletter_id' => $box['key']['id'])
							), 
							true
						);			
			$values['meta_data/subject'] = $details['subject'];
			$values['meta_data/email_address_from'] = $details['email_address_from'];
			$values['meta_data/email_name_from'] = $details['email_name_from'];
			$values['meta_data/body'] = $details['body'];
			$values['advanced/head'] = $details['head'];

			$values['unsub_exclude/unsubscribe_text'] = $details['unsubscribe_text'];
			$values['unsub_exclude/delete_account_text'] = $details['delete_account_text'];
			$values['unsub_exclude/exclude_previous_newsletters_recipients'] =
				inEscape(
					getRowsArray(
						ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
						'sent_newsletter_id',
						array('newsletter_id' => $box['key']['id'], 'include' => 0)),
					true);
			
			if (setting('zenario_newsletter__default_unsubscribe_text') && !$values['unsub_exclude/unsubscribe_text']) {
				$values['unsub_exclude/unsubscribe_text'] = setting('zenario_newsletter__default_unsubscribe_text');
			}
			if (setting('zenario_newsletter__default_delete_account_text') && !$values['unsub_exclude/delete_account_text']) {
				$values['unsub_exclude/delete_account_text'] = setting('zenario_newsletter__default_delete_account_text');
			}
			$values['unsub_exclude/unsubscribe_link'] = 'none';
			if (setting('zenario_newsletter__all_newsletters_opt_out') && $details['unsubscribe_text']) {
				$values['unsub_exclude/unsubscribe_link'] = 'unsub';
			}
			if ($details['delete_account_text']) {
				$values['unsub_exclude/unsubscribe_link'] = 'delete';
			}
			if ($values['unsub_exclude/exclude_previous_newsletters_recipients']) {
				$values['unsub_exclude/exclude_previous_newsletters_recipients_enable'] = 1;
			}
			
			if ($details['status'] != '_DRAFT') {
						
				$box['tabs']['meta_data']['edit_mode']['enabled'] =
				$box['tabs']['unsub_exclude']['edit_mode']['enabled'] =
				$box['tabs']['advanced']['edit_mode']['enabled'] = false;
				$box['tabs']['meta_data']['fields']['test_send_button']['hidden'] =
				$box['tabs']['meta_data']['fields']['test_send_button_dummy']['hidden'] = false;
			}


		} else {
			$i = 1;
			$fuse = 100;
			$nameCandidate = '';
			while ($fuse--) {
				$nameCandidate = adminPhrase('Newsletter ' . formatDateNicely(date('Y-m-d'), '_LONG') . ($i>1?(' (' . (int) $i . ')'):''));
				if (!checkRowExists(ZENARIO_NEWSLETTER_PREFIX . "newsletters", array('newsletter_name' => $nameCandidate))) {
					break;
				}
				$i++;
			}
			$values['meta_data/newsletter_name'] = $nameCandidate;

			if (setting('zenario_newsletter__default_from_name')) {
				$values['meta_data/email_name_from'] = setting('zenario_newsletter__default_from_name');
			}
			if (setting('zenario_newsletter__default_from_email_address')) {
				$values['meta_data/email_address_from'] = setting('zenario_newsletter__default_from_email_address');
			}

			if (setting('zenario_newsletter__default_unsubscribe_text')) {
				$values['unsub_exclude/unsubscribe_text'] = setting('zenario_newsletter__default_unsubscribe_text');
			}
			if (setting('zenario_newsletter__default_delete_account_text')) {
				$values['unsub_exclude/delete_account_text'] = setting('zenario_newsletter__default_delete_account_text');
			}
			
		}

		$pick_items = &$box['tabs']['meta_data']['fields']['body']['insert_image_button']['pick_items'];
		if ($box['key']['id']) {
			$pick_items['path'] = 'zenario__email_template_manager/panels/newsletters/item_buttons/images//'. (int) $box['key']['id']. '//';
			$pick_items['min_path'] =
			$pick_items['max_path'] =
			$pick_items['target_path'] = 'zenario__content/panels/email_images_for_newsletters';
		} else {
			$pick_items['path'] =
			$pick_items['min_path'] =
			$pick_items['max_path'] =
			$pick_items['target_path'] = 'zenario__content/panels/image_library';
		}
		
		
		$values['unsub_exclude/example_unsubscribe_url_underlined_and_hidden'] 
				= '<span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'remove_from_groups.php?t=XXXXXXXXXXXXXXX</span>';
		$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['post_field_html'] 
				= '<div id="unsubscribe_info">Preview: ' . $values['unsub_exclude/unsubscribe_text'] . ' <span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'remove_from_groups.php?t=XXXXXXXXXXXXXXX</span></div>';

		$values['unsub_exclude/example_delete_account_url_underlined_and_hidden'] 
				= '<span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'delete_account.php?t=XXXXXXXXXXXXXXX</span>';
		$box['tabs']['unsub_exclude']['fields']['delete_account_text']['post_field_html'] 
				= '<div id="delete_account_info">Preview: ' . $values['unsub_exclude/delete_account_text'] . ' <span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'delete_account.php?t=XXXXXXXXXXXXXXX</span></div>';
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
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
				$emailTemplate = zenario_email_template_manager::getTemplateByCode($values['meta_data/load_content_source_email_template']);
				$values['meta_data/body'] = $emailTemplate['body'];
			}
		}
		if (($values['meta_data/load_content_source'] == 'use_newsletter_template')
			&& $values['meta_data/load_content_source_newsletter_template'] 
				&& (engToBooleanArray($box,'tabs','meta_data','fields','load_content_continue','pressed') || (!$values['meta_data/body'])) ) {

			$clearCopyFromSourceFields = true;
			$emailTemplate = getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', array('head', 'body'), array('id' => $values['meta_data/load_content_source_newsletter_template']));
			$values['meta_data/body'] = $emailTemplate['body'];
			$values['advanced/head'] = $emailTemplate['head'];
		}
		if (($values['meta_data/load_content_source'] == 'copy_from_archived_newsletter')
				&& $values['meta_data/load_content_source_archived_newsletter'] 
					&& (engToBooleanArray($box,'tabs','meta_data','fields','load_content_continue','pressed') || (!$values['meta_data/body'])) ) {

			$clearCopyFromSourceFields= true;
			$newsletter = $this->loadDetails($values['meta_data/load_content_source_archived_newsletter']);
			$values['meta_data/body'] = $newsletter['body'];
			$values['advanced/head'] = $newsletter['head'];
		}

		if (engToBooleanArray($box,'tabs','meta_data','fields','load_content_cancel','pressed')) {
			$clearCopyFromSourceFields= true;
		}

		if ($clearCopyFromSourceFields) {
			$values['meta_data/load_content_source'] = 'nothing_selected';
			$values['meta_data/load_content_source'] = 'nothing_selected';
			$values['meta_data/load_content_source_email_template'] = '';
			$values['meta_data/load_content_source_newsletter_template'] = '';
			$values['meta_data/load_content_source_archived_newsletter'] = '';

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
				
		if (($values['unsub_exclude/unsubscribe_link'] == 'unsub') && !setting('zenario_newsletter__all_newsletters_opt_out')) {
			$values['unsub_exclude/unsubscribe_link'] = 'none';
			$box['tabs']['unsub_exclude']['notices']['no_opt_out_group']['show'] = true;
			$box['tabs']['unsub_exclude']['notices']['no_opt_out_group']['message'] =
				adminPhrase('You must select an Unsubscribe user flag in Configuration->Site Settings->Newsletters');
			if (isset($box['tabs']['unsub_exclude']['fields']['exclude_recipients_with_opt_out'])) {
				unset($box['tabs']['unsub_exclude']['fields']['exclude_recipients_with_opt_out']);
			}
		}
		$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['hidden'] =
		$box['tabs']['unsub_exclude']['fields']['example_unsubscribe_url_underlined_and_hidden']['hidden'] =
			($values['unsub_exclude/unsubscribe_link'] != 'unsub');

		$box['tabs']['unsub_exclude']['fields']['delete_account_text']['hidden'] =
		$box['tabs']['unsub_exclude']['fields']['example_delete_account_url_underlined_and_hidden']['hidden'] =
			($values['unsub_exclude/unsubscribe_link'] != 'delete');

		$box['tabs']['unsub_exclude']['fields']['exclude_previous_newsletters_recipients']['hidden'] =
			!$values['unsub_exclude/exclude_previous_newsletters_recipients_enable'];
		
		if (engToBoolean($box['tabs']['meta_data']['fields']['test_send_button']['pressed'] ?? false)) {
			$box['tabs']['meta_data']['notices']['test_send']['show'] = true;
			
			$error = '';
			$success = '';
			if (!$values['meta_data/test_send_email_address']) {
				$error = adminPhrase('Please enter an email address.');
			
			} else {
				$adminDetails = getAdminDetails($_SESSION['admin_userid'] ?? false);
				foreach (explodeAndTrim($values['meta_data/test_send_email_address']) as $email) {
					$body = $values['meta_data/body'];
					if ($values['unsub_exclude/unsubscribe_link'] == 'unsub') {
						$body .= '<p>' . htmlspecialchars($values['unsub_exclude/unsubscribe_text']) . ' <a href="[[REMOVE_FROM_GROUPS_LINK]]">[[REMOVE_FROM_GROUPS_LINK]]</a></p>';
					}
					if ($values['unsub_exclude/unsubscribe_link'] == 'delete') {
						$body .= '<p>' . htmlspecialchars($values['unsub_exclude/delete_account_text']) . ' <a href="[[DELETE_ACCOUNT_LINK]]">[[DELETE_ACCOUNT_LINK]]</a></p>';
					}
					
					if (!validateEmailAddress($email)) {
						$error .= ($error? "\n" : ''). adminPhrase('"[[email]]" is not a valid email address.', array('email' => $email));
					
					} elseif (!$values['meta_data/body']) {
						$error .= ($error? "\n" : ''). adminPhrase('The test email(s) could not be sent because your Newsletter is blank.');
						break;
					
					} else
					if (($box['key']['id']) &&!$this->testSendNewsletter(
						$values['advanced/head'],
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
		exitIfNotCheckPriv('_PRIV_EDIT_NEWSLETTER');
		
		if (engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			if (checkRowExists(
				ZENARIO_NEWSLETTER_PREFIX. 'newsletters',
				array('newsletter_name' => $values['meta_data/newsletter_name'], 'id' => array('!' => $box['key']['id']))
			)) {
				$box['tabs']['meta_data']['errors'][] = adminPhrase('Please ensure the Name you give this Newsletter is Unique.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_EDIT_NEWSLETTER');
		
		if (engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			
			addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['body']);
			
			
			$id = $box['key']['id'];
			$record = array(
				'newsletter_name' => $values['meta_data/newsletter_name'],
				'subject' => $values['meta_data/subject'],
				'email_name_from' => $values['meta_data/email_name_from'],
				'email_address_from' => $values['meta_data/email_address_from'],
				'body' =>  $values['meta_data/body'],
				'head' => $values['advanced/head']
			);
			
			if($id) {
				$record['date_modified'] = now();
				$record['modified_by_id'] = adminId();
			} else {
				$record['date_created'] = now();
				$record['status'] = '_DRAFT';
				$record['created_by_id'] = adminId();
			}
			
			$box['key']['id'] = setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', $record, $id);

			deleteRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', array('newsletter_id' => $box['key']['id']));
			foreach (explode(',', $values['unsub_exclude/recipients']) as $smartGroupId) {
				if ((int)$smartGroupId) {
					setRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', array('newsletter_id' => $box['key']['id'], 'smart_group_id' => (int) $smartGroupId)); 
				}
			}


			$body = $values['meta_data/body'];
			$files = array();
			$htmlChanged = false;
			Ze\File::addImageDataURIsToDatabase($body, absCMSDirURL());
			syncInlineFileLinks($files, $body, $htmlChanged);
			syncInlineFiles(
				$files,
				array('foreign_key_to' => 'newsletter', 'foreign_key_id' => $box['key']['id']),
				$keepOldImagesThatAreNotInUse = true);
			
			if ($htmlChanged) {
				setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', array('body' => $body), $box['key']['id']);
			}
		}
		
		if ($box['key']['id'] && engToBoolean($box['tabs']['unsub_exclude']['edit_mode']['on'] ?? false)) {
			setRow(
				ZENARIO_NEWSLETTER_PREFIX. 'newsletters',
				array(
					'unsubscribe_text' 
							=> ($values['unsubscribe_link'] == 'unsub') ? $values['unsubscribe_text']: null,
					'delete_account_text' 
							=> ($values['unsubscribe_link'] == 'delete') ? $values['delete_account_text']: null
					),
				$box['key']['id']);

			deleteRow(
				ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
				array('newsletter_id' => $box['key']['id'], 'include' => 0));
			
			if (engToBooleanArray($values,'exclude_previous_newsletters_recipients_enable')) {
				foreach (explode(',', $values['exclude_previous_newsletters_recipients']) as $id) {
					if ($id) {
						insertRow(
							ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
							array('newsletter_id' => $box['key']['id'], 'include' => 0, 'sent_newsletter_id' => $id));
					}
				}
			}
		}
	}
}
