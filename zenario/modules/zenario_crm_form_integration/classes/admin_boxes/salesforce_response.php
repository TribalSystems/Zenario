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


class zenario_crm_form_integration__admin_boxes__salesforce_response extends zenario_crm_form_integration {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($logId = $box['key']['id']) {
			$log = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'salesforce_response_log', true, $logId);
			$fields['details/datetime']['snippet']['html'] = ze\admin::formatDateTime($log['datetime'], '_MEDIUM');
			
			$box['title'] = ze\admin::phrase('Salesforce response [[id]]', $log);
			
			$fields['details/oauth_status']['snippet']['html'] = $log['oauth_status'];
			if ($log['oauth_response']) {
				$value = '<pre>' . json_encode(json_decode($log['oauth_response'], true), JSON_PRETTY_PRINT) . '</pre>';
			} else {
				$value = ze\admin::phrase('No response.');
			}
			$fields['details/oauth_response']['snippet']['html'] = $value;
			
			$fields['details/salesforce_status']['snippet']['html'] = $log['salesforce_status'];
			if ($log['salesforce_response']) {
				$value = '<pre>' . json_encode(json_decode($log['salesforce_response'], true), JSON_PRETTY_PRINT) . '</pre>';
			} else {
				$value = ze\admin::phrase('No response.');
			}
			$fields['details/salesforce_response']['snippet']['html'] = $value;
		}
	}
	
}