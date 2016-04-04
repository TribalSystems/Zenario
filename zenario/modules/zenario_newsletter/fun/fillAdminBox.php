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
		if ($id = $box['key']['id']) {
			$templateDetails = getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', array('name', 'head', 'body'), array('id' => $id));
			$box['title'] = adminPhrase('Editing the newsletter template "[[name]]"', array('name' => $templateDetails['name']));
			$values['details/name'] = $templateDetails['name'];
			$values['details/body'] = $templateDetails['body'];
			$values['advanced/head'] = $templateDetails['head'];
		}
		
		$style_formats = siteDescription('email_style_formats');
		if (!empty($style_formats)) {
			$box['tabs']['details']['fields']['body']['editor_options']['style_formats'] = $style_formats;
			$box['tabs']['details']['fields']['body']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
		
		break;
	
	
	case 'zenario_newsletter':
		
		$style_formats = siteDescription('email_style_formats');
		if (!empty($style_formats)) {
			$box['tabs']['meta_data']['fields']['body']['editor_options']['style_formats'] = $style_formats;
			$box['tabs']['meta_data']['fields']['body']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
		
		
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
		
		break;
	case 'zenario_live_send':
		if ($id = $box['key']['id']) {
			$recipients = self::newsletterRecipients($id, 'count');
			$newsletter = $this->loadDetails($id);
			$fields['send/desc']['snippet']['html'] = nAdminPhrase(
				'Are you sure you wish to send the Newsletter "[[newsletter_name]]" to [[recipients]] Recipient? Click-throughs counts will be reset.',
				'Are you sure you wish to send the Newsletter "[[newsletter_name]]" to [[recipients]] Recipients? Click-throughs counts will be reset.',
				$recipients,
				array('newsletter_name' => $newsletter['newsletter_name'], 'recipients' => $recipients));
				
			$sql = '
				SELECT COUNT(*) 
				FROM '.DB_NAME_PREFIX.'admins
				WHERE status = \'active\'';
			$result = sqlSelect($sql);
			$row = sqlFetchRow($result);
			
			if ($row[0] > 1) {
				$fields['send/admin_options']['values']['all_admins']['label'] = adminPhrase(
					'Send all [[count]] administrators a copy of the email', 
					array('count' => $row[0]));
			} else {
				unset($fields['send/admin_options']['values']['all_admins']);
			}
		}
		break;
	
	
	case 'site_settings':
	
		switch($settingGroup) {
			case 'zenario_newsletter__site_settings_group':
				$box['tabs']['zenario_newsletter__site_settings']['fields']['zenario_newsletter__all_newsletters_opt_out']['values'] =
					listCustomFields('users', $flat = false, 'boolean_and_groups_only', $customOnly = true, $useOptGroups = true);

		}
}