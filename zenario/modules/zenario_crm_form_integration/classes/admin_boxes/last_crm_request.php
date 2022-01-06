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


class zenario_crm_form_integration__admin_boxes__last_crm_request extends zenario_crm_form_integration {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$formId = $box['key']['id'];
		if ($formId) {
			$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name'], $formId);
			$box['title'] = ze\admin::phrase('Last CRM request for the form "[[name]]"', $form);
			
			//Show the latest CRM request from this form
			$sql = '
				SELECT lcr.url, lcr.datetime, lcr.request
				FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'last_crm_requests lcr
				INNER JOIN ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link fcr
					ON lcr.link_id = fcr.id
				WHERE fcr.form_id = ' . (int)$formId . '
				ORDER BY lcr.datetime DESC
				LIMIT 1';
			$lastCRMRequest = ze\sql::fetchAssoc($sql);
			if ($lastCRMRequest) {
				$values['details/url'] = $lastCRMRequest['url'];
				$values['details/datetime'] = ze\admin::formatDateTime($lastCRMRequest['datetime'], '_MEDIUM');
				$values['details/request'] = $lastCRMRequest['request'];
			}
		}
	}
	
}