<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
			$templateDetails = getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', array('name', 'body'), array('id' => $id));
			$box['title'] = adminPhrase('Editing the newsletter template "[[name]]"', array('name' => $templateDetails['name']));
			$values['details/name'] = $templateDetails['name'];
			$values['details/body'] = $templateDetails['body'];
		}
		break;
	case 'zenario_newsletter':
		$box['tabs']['unsub_exclude']['fields'] = &$box['tabs']['unsub_exclude']['fields'];
		
		$adminDetails = getAdminDetails(adminId());
		$box['tabs']['meta_data']['fields']['test_send_email_address']['value'] = $adminDetails['admin_email'];
		
		if ($box['key']['id']) {
			$details = $this->loadDetails($box['key']['id']);
			$box['title'] = adminPhrase('Viewing/Editing newsletter "[[newsletter_name]]"', $details);
			
			$box['tabs']['meta_data']['fields']['newsletter_name']['value'] = $details['newsletter_name'];
			$box['tabs']['unsub_exclude']['fields']['recipients']['value'] = 
					inEscape(
						getRowsArray(
							ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link',
							'smart_group_id',
							array('newsletter_id' => $box['key']['id'])
							), 
							true
						);			
			$box['tabs']['meta_data']['fields']['subject']['value'] = $details['subject'];
			$box['tabs']['meta_data']['fields']['email_address_from']['value'] = $details['email_address_from'];
			$box['tabs']['meta_data']['fields']['email_name_from']['value'] = $details['email_name_from'];
			$box['tabs']['meta_data']['fields']['body']['value'] = $details['body'];

			$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['value'] = $details['unsubscribe_text'];
			$box['tabs']['unsub_exclude']['fields']['delete_account_text']['value'] = $details['delete_account_text'];
			$box['tabs']['unsub_exclude']['fields']['exclude_previous_newsletters_recipients']['value'] =
				inEscape(
					getRowsArray(
						ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
						'sent_newsletter_id',
						array('newsletter_id' => $box['key']['id'], 'include' => 0)),
					true);
			
			if (setting('zenario_newsletter__default_unsubscribe_text') && !$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['value']) {
				$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['value'] = setting('zenario_newsletter__default_unsubscribe_text');
			}
			if (setting('zenario_newsletter__default_delete_account_text') && !$box['tabs']['unsub_exclude']['fields']['delete_account_text']['value']) {
				$box['tabs']['unsub_exclude']['fields']['delete_account_text']['value'] = setting('zenario_newsletter__default_delete_account_text');
			}
			
			if (setting('zenario_newsletter__all_newsletters_opt_out') && $details['unsubscribe_text']) {
				$box['tabs']['unsub_exclude']['fields']['add_unsubscribe_link']['value'] = 1;
			}
			if ($details['delete_account_text']) {
				$box['tabs']['unsub_exclude']['fields']['add_delete_account_link']['value'] = 1;
			}
			if ($box['tabs']['unsub_exclude']['fields']['exclude_previous_newsletters_recipients']['value']) {
				$box['tabs']['unsub_exclude']['fields']['exclude_previous_newsletters_recipients_enable']['value'] = 1;
			}
			
			if ($details['status'] != '_DRAFT') {
				$box['tabs']['meta_data']['edit_mode']['enabled'] =
				$box['tabs']['unsub_exclude']['edit_mode']['enabled'] = false;
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
			$box['tabs']['meta_data']['fields']['newsletter_name']['value'] = $nameCandidate;

			if (setting('zenario_newsletter__default_from_name')) {
				$box['tabs']['meta_data']['fields']['email_name_from']['value'] = setting('zenario_newsletter__default_from_name');
			}
			if (setting('zenario_newsletter__default_from_email_address')) {
				$box['tabs']['meta_data']['fields']['email_address_from']['value'] = setting('zenario_newsletter__default_from_email_address');
			}

			if (setting('zenario_newsletter__default_unsubscribe_text')) {
				$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['value'] = setting('zenario_newsletter__default_unsubscribe_text');
			}
			if (setting('zenario_newsletter__default_delete_account_text')) {
				$box['tabs']['unsub_exclude']['fields']['delete_account_text']['value'] = setting('zenario_newsletter__default_delete_account_text');
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
		
		
		$box['tabs']['unsub_exclude']['fields']['example_unsubscribe_url_underlined_and_hidden']['value'] 
				= '<span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'remove_from_groups.php?t=XXXXXXXXXXXXXXX</span>';
		$box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['post_field_html'] 
				= '<div id="unsubscribe_info">Preview: ' . $box['tabs']['unsub_exclude']['fields']['unsubscribe_text']['value'] . ' <span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'remove_from_groups.php?t=XXXXXXXXXXXXXXX</span></div>';

		$box['tabs']['unsub_exclude']['fields']['example_delete_account_url_underlined_and_hidden']['value'] 
				= '<span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'delete_account.php?t=XXXXXXXXXXXXXXX</span>';
		$box['tabs']['unsub_exclude']['fields']['delete_account_text']['post_field_html'] 
				= '<div id="delete_account_info">Preview: ' . $box['tabs']['unsub_exclude']['fields']['delete_account_text']['value'] . ' <span style="text-decoration:underline;">' . zenario_newsletter::getTrackerURL() . 'delete_account.php?t=XXXXXXXXXXXXXXX</span></div>';
		
		break;
}