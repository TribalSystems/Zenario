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

switch ($path) {
	case 'zenario__email_template_manager/panels/email_templates':
		
		if ($refinerName == 'module') {
			$moduleDetails = getRow('modules', array('display_name'), $refinerId);
			$panel['title'] = adminPhrase('Email Templates created by the module "[[display_name]]"', $moduleDetails);
			$panel['no_items_message'] = adminPhrase('There are no Email Templates created by the module "[[display_name]]"', $moduleDetails);
			
			// Hide collection buttons
			$panel['collection_buttons']['create_template']['hidden'] = 
			$panel['collection_buttons']['test']['hidden'] = true;
		}
		
		
		foreach ($panel['items'] as $K=>$item){
			$body_extract = strip_tags($panel['items'][$K]['body_extract']);
			
			$body_extract_length = 250;
			if ($length = @strpos($body_extract, ' ', $body_extract_length)) {
				$body_extract_length = $length;
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
				$admin = getAdminDetails($template['created_by_id'] ?? false);
				$panel['items'][$K]['created_by'] = $admin['admin_username'];
			} else {
				$panel['items'][$K]['created_by'] = 'n/a';
			}

			if (!empty($template['modified_by_id'])) {
				$admin = getAdminDetails($template['modified_by_id'] ?? false);
				$panel['items'][$K]['modified_by'] = $admin['admin_username'];
			} else {
				$panel['items'][$K]['modified_by'] = 'n/a';
			}
			
			if ($K==$template['id']){
				$panel['items'][$K]['non_protected']=true;
			} else {
				if (!(count($arr = explode("__",$K)) == 2) || !($mID = getModuleId($arr[0])) || (getModuleStatus($mID) == 'module_not_initialized')) {
					$panel['items'][$K]['non_protected']=true;
				} else {
					$panel['items'][$K]['protected']=true;
				}
			}
		}
		
		break;
	
	
	case 'zenario__email_template_manager/panels/email_log':
		if ($refinerName=='email_template'){
			$template =  self::getTemplateById($refinerId);
			if (!empty($template['template_name'])){
				$panel['title'] = 'Emails sent using the template "' . htmlspecialchars($template['template_name'] ?? false) . '"';
			}
		}

		foreach($panel['items'] as &$item) {
			$item['cell_css_classes'] = array();
			if ($item['status']=='success') {
				$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__sent';
				$item['status'] = adminPhrase('Sent');
			} elseif ($item['status']=='failure') {
				$item['cell_css_classes']['status'] = 'zenario_email_template_manager_log__failed';
				$item['status'] = adminPhrase('Failed Sending');
			}
		}
		break;
	
	case 'zenario__modules/panels/plugins':
		if ($refinerName == 'email_address_setting') {
			$panel['title'] = adminPhrase('Summary of email addresses used by plugins');
			$panel['no_items_message'] = adminPhrase('There are no plugins that send emails');
			$panel['columns']['plugin']['show_by_default'] = true;
			$panel['columns']['plugin']['ord'] = 0.5;
			$panel['bold_columns_in_list_view'] = 'plugin';
		} else {
			unset($panel['columns']['plugin_email_address']);
		}
		break;
}
