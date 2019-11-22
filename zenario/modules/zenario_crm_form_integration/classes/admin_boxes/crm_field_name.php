<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


class zenario_crm_form_integration__admin_boxes__crm_field_name extends zenario_crm_form_integration {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$formFieldId = $box['key']['id'];
		if ($formFieldId) {
			//Load crm field name
			$name = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', 'name', ['form_field_id' => $formFieldId]);
			$values['details/field_name'] = $name;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Update all fields using this name on this form
		$formFieldId = $box['key']['id'];
		$oldFieldName = ze\row::get(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', 'name', $formFieldId);
		if ($oldFieldName !== '' && $oldFieldName !== null) {
			$sql = '
				SELECT cf.form_field_id, cf.name
				FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
					ON cf.form_field_id = uff.id
				WHERE uff.user_form_id = '.(int)$box['key']['form_id'].'
				AND cf.name = "' . ze\escape::sql($oldFieldName) . '"';
			$result = ze\sql::select($sql);
			while ($row = ze\sql::fetchAssoc($result)) {
				ze\row::update(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields', ['name' => $values['details/field_name']], $row['form_field_id']);
			}
		}
	}
	
}