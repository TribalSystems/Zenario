<?php
/*
 * Copyright (c) 2022, Tribal Limited
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
	case 'zenario__email_template_manager/panels/email_templates':
		
		if (ze::setting('debug_override_enable')) {
			$sendToDebugAddressOrDontSentAtAll = ze::setting('send_to_debug_address_or_dont_send_at_all');
			$panel['notice']['show'] = true;
			
			if ($sendToDebugAddressOrDontSentAtAll == 'send_to_debug_email_address') {
				$panel['notice']['message'] =
					ze\admin::phrase('Email debug mode is enabled, all emails will be sent to [[email]].',
						['email' => ze::setting('debug_override_email_address')]);
			} elseif ($sendToDebugAddressOrDontSentAtAll == 'dont_send_at_all') {
				$panel['notice']['message'] =
					ze\admin::phrase('Email debug mode is enabled, emails will not be sent at all.');
			}
		}
		
		if ($refinerName == 'email_templates_using_image') {
			$mrg = ze\row::get('files', ['filename'], $refinerId);
			$panel['title'] = ze\admin::phrase('Email templates using the image "[[filename]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no email templates using the image "[[filename]]"', $mrg);
			
			// Hide collection buttons
			$panel['collection_buttons']['create_template']['hidden'] = 
			$panel['collection_buttons']['test']['hidden'] = true;
		}
		
		
		foreach ($panel['items'] as $K=>$item){
			$body_extract = strip_tags($panel['items'][$K]['body_extract']);
			
			$body_extract_length = 250;
			if (strlen($body_extract) > $body_extract_length) {
				if ($length = strpos($body_extract, ' ', $body_extract_length)) {
					$body_extract_length = $length;
				}
			}
			
			$body_extract_snippet = substr($body_extract, 0, $body_extract_length);
			if (strlen($body_extract) > $body_extract_length) {
				$body_extract_snippet .= '...';
			}
			$panel['items'][$K]['body_extract'] = $body_extract_snippet;
			
			$details = self::checkTemplateIsProtectedAndGetCreatedDetails($K);
			if ($details['protected']) {
				$panel['items'][$K]['protected'] = true;
			}
		}
		
		break;
	
	
	case 'zenario__email_template_manager/panels/email_log':

		//Information to view Data Protection settings
		$accessLogDuration = '';
			switch (ze::setting('period_to_delete_the_email_template_sending_log_headers')) {
				case 'never_delete':
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are stored forever.');
					break;
				case 0:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are not stored.');
					break;
				case 1:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 day.');
					break;
				case 7:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 week.');
					break;
				case 30:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 month.');
					break;
				case 90:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 3 months.');
					break;
				case 365:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 1 year.');
					break;
				case 730:
					$accessLogDuration = ze\admin::phrase('Entries in the sent email log are deleted after 2 years.');
					break;
				
			}
			$link = ze\link::absolute(). 'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$accessLogDuration .= ' ' . "<a target='_blank' href='" . $link . "'>View Data Protection settings</a>";
			$panel['notice']['show'] = true;
			$panel['notice']['message'] = $accessLogDuration.".";
			$panel['notice']['html'] = true;
	
		if ($refinerName == 'email_template') {
			$template = self::getTemplateByCode($refinerId);
			$panel['title'] = ze\admin::phrase('Emails sent using the template "[[template_name]]"', $template);
		}
		
		foreach($panel['items'] as &$item) {
			$item['cell_css_classes'] = [];
			if ($item['status']=='success') {
				//To show debug mode 'On' in sent email log panel
				if ($item['debug_mode_flag'] == 1){
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent_debug_mode';
					$item['status'] = ze\admin::phrase('Success (debug mode)');
				} else { 
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent';
					$item['status'] = ze\admin::phrase('Sent');
				}
			} elseif ($item['status']=='failure') {
				if ($item['debug_mode_flag'] == 1){
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
					$item['status'] = ze\admin::phrase('Failed (debug mode)');
				} else { 
					$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
					$item['status'] = ze\admin::phrase('Failed Sending');
				}
				
			}
		}
		break;
	
	case 'zenario__modules/panels/plugins':
		if ($refinerName == 'email_address_setting') {
			$panel['title'] = ze\admin::phrase('Summary of email addresses used by plugins');
			$panel['no_items_message'] = ze\admin::phrase('There are no plugins that send emails');
			$panel['columns']['plugin']['show_by_default'] = true;
			$panel['columns']['plugin']['ord'] = 0.5;
			$panel['bold_columns_in_list_view'] = 'plugin';
			$panel['columns']['code']['html'] = true;
			$panel['columns']['where_used']['hidden'] = true;
			$panel['columns']['framework']['hidden'] = true;
			unset($panel['collection_buttons']);
			unset($panel['item_buttons']);
			
			foreach ($panel['items'] as &$item) {
				$item['code'] = '<a href="organizer.php#zenario__modules/panels/plugins//' . ze\ring::chopPrefix('P', $item['code']) . '" target="_blank">' . $item['code'] . '</a>';
			}
		} else {
			unset($panel['columns']['plugin_email_address']);
		}
		break;
	
	case 'zenario__email_template_manager/panels/other_email_addresses':
		//...

		break;
}
