<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_crm_form_integration__organizer__crm_fields extends zenario_crm_form_integration {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//Set panel title
		$sql = '
			SELECT uf.name AS form_name, cf.name AS crm_name
			FROM ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields cf
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields uff
				ON cf.form_field_id = uff.id
			INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_forms uf
				ON uff.user_form_id = uf.id
			WHERE cf.form_field_id = '.(int)$refinerId;
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchAssoc($result);
		$panel['title'] = ze\admin::phrase('Form fields for "[[form_name]]" with CRM field name "[[crm_name]]"', $row);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//Handle reordering of CRM fields
		if (ze::post('reorder')) {
			$ids = explode(',', $ids);
			foreach ($ids as $id) {
				if (!empty($_POST['ordinals'][$id])) {
					$sql = '
						UPDATE ' . DB_PREFIX . ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'crm_fields 
						SET ord = '. (int) $_POST['ordinals'][$id]. '
						WHERE form_field_id = '. (int)$id;
					ze\sql::update($sql);
				}
			}
		}
	}
	
}