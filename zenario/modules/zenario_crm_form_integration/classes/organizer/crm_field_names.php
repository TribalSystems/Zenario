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


class zenario_crm_form_integration__organizer__crm_field_names extends zenario_crm_form_integration {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$maxFieldCount = 0;
		$panel['item_buttons']['properties']['admin_box']['key']['form_id'] = $refinerId;
		$sql = '
			SELECT MIN(uff.id) as id, cf.name AS field_crm_name, COUNT(uff.id) as field_crm_name_count, uff.name
			FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				ON cf.form_field_id = uff.id
			WHERE uff.user_form_id = ' . (int)$refinerId . '
			GROUP BY cf.name';
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$panel['items'][$row['id']] = [
				'field_name' => $row['name'],
				'field_crm_name' => $row['field_crm_name'], 
				'field_crm_name_count' => $row['field_crm_name_count']];
			$maxFieldCount = $maxFieldCount < $row['field_crm_name_count'] ? $row['field_crm_name_count'] : $maxFieldCount;
		}
		
		if ($maxFieldCount > 1) {
			unset($panel['columns']['field_name']);
		} else {
			unset($panel['item']['link']);
			unset($panel['columns']['field_crm_name_count']);
		}
		
		//Set panel title
		$formName = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', $refinerId);
		$panel['title'] = ze\admin::phrase('CRM field names for "[[name]]" (to make changes edit the form fields)', ['name' => $formName]);
	}
	
}