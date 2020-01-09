<?php
/*
 * Copyright (c) 2020, Tribal Limited
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
		
		if ($refinerName == 'module') {
			$moduleDetails = ze\row::get('modules', ['display_name'], $refinerId);
			$panel['title'] = ze\admin::phrase('Email Templates created by the module "[[display_name]]"', $moduleDetails);
			$panel['no_items_message'] = ze\admin::phrase('There are no Email Templates created by the module "[[display_name]]"', $moduleDetails);
			
			// Hide collection buttons
			$panel['collection_buttons']['create_template']['hidden'] = 
			$panel['collection_buttons']['test']['hidden'] = true;
		
		} elseif ($refinerName == 'email_templates_using_image') {
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
			$template = self::getTemplateByCode($K);
			if ($K!=$template['id']) {
				if (count($arr=explode("__",$K))==2){
					$panel['items'][$K]['created_by'] = 'Module ' . $arr[0];
				} else {
					$panel['items'][$K]['created_by'] = 'n/a';
				}
			} elseif ($template['created_by_id'] ?? false) {
				$admin = ze\admin::details($template['created_by_id'] ?? false);
				$panel['items'][$K]['created_by'] = $admin['admin_username'];
			} else {
				$panel['items'][$K]['created_by'] = 'n/a';
			}
			
			if (!empty($template['modified_by_id'])) {
				$admin = ze\admin::details($template['modified_by_id'] ?? false);
				$panel['items'][$K]['modified_by'] = $admin['admin_username'];
			} else {
				$panel['items'][$K]['modified_by'] = 'n/a';
			}
			
			if ($K==$template['id']){
				$panel['items'][$K]['non_protected']=true;
			} else {
				if (!(count($arr = explode("__",$K)) == 2) || !($mID = ze\module::id($arr[0])) || (ze\module::status($mID) == 'module_not_initialized')) {
					$panel['items'][$K]['non_protected']=true;
				} else {
					$panel['items'][$K]['protected']=true;
				}
			}
			
			if ($item['from_details'] == 'site_settings') {
				$panel['items'][$K]['email_name_from'] = ze::setting('email_name_from');
				$panel['items'][$K]['email_address_from'] = ze::setting('email_address_from');
			}
			
		}
		
		break;
	
	
	case 'zenario__email_template_manager/panels/email_log':
		if ($refinerName == 'email_template') {
			$template = self::getTemplateByCode($refinerId);
			$panel['title'] = ze\admin::phrase('Emails sent using the template "[[template_name]]"', $template);
		}

		foreach($panel['items'] as &$item) {
			$item['cell_css_classes'] = [];
			if ($item['status']=='success') {
				$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent';
				$item['status'] = ze\admin::phrase('Sent');
			} elseif ($item['status']=='failure') {
				$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
				$item['status'] = ze\admin::phrase('Failed Sending');
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
}
