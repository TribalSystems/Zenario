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

class zenario_user_forms__admin_boxes__email_template extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$forms = ze\row::getValues(ZENARIO_USER_FORMS_PREFIX . 'user_forms', 'name', ['status' => 'active'], 'name');
		$fields['meta_data/user_form']['values'] = $forms;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($formId = $values['meta_data/user_form']) {
			//Get list of form fields for form
			$fields['meta_data/user_form_field']['hidden'] = false;
			$sql = '
				SELECT
					uff.id,
					IF(
						uff.name IS NULL or uff.name = "", 
						IFNULL(
							cdf.db_column, 
							CONCAT("unlinked_", uff.field_type, "_", uff.id)
						), 
						uff.name
					) AS name,
					uff.field_type,
					uff.ord,
					uff.page_id,
					p.name AS page_name,
					p.ord AS page_ord
				FROM '. DB_PREFIX. ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
				INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
					ON uff.page_id = p.id
				LEFT JOIN '. DB_PREFIX.'custom_dataset_fields AS cdf
					ON uff.user_field_id = cdf.id
				WHERE uff.user_form_id = '.(int)$formId. '
				ORDER BY p.ord, uff.ord';
			
			$result = ze\sql::select($sql);
			$formFields = [];
			$formFields['all'] = [
				'ord' => -1,
				'label' => ze\admin::phrase('Add all to template')
			];
			while ($row = ze\sql::fetchAssoc($result)) {
				if (!in_array($row['field_type'], ['section_description', 'repeat_start', 'repeat_end', 'restatement'])) {
					if (!isset($formFields['page_' . $row['page_id']])) {
						$formFields['page_' . $row['page_id']] = [
							'ord' => $row['page_ord'] + 10,
							'label' => $row['page_name']
						];
					}
					$formFields[$row['id']] = [
						'ord' => $row['ord'] + 10,
						'label' => trim($row['name'], " \t\n\r\0\x0B:"),
						'parent' => 'page_' . $row['page_id']
					];
				}
			}
			$fields['meta_data/user_form_field']['values'] = $formFields;
			
			
			if ($formFieldId = $values['meta_data/user_form_field']) {
				//Add form field mergefield onto end of email template
				$sql = '
					SELECT 
						IFNULL(uff.name, cdf.label) AS name, 
						IFNULL(cdf.db_column, CONCAT(\'unlinked_\', uff.field_type, \'_\', uff.id)) AS mergefield,
						uff.split_first_name_last_name
					FROM ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_form_fields AS uff
					INNER JOIN ' . DB_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'pages p
						ON uff.page_id = p.id
					LEFT JOIN '.DB_PREFIX. 'custom_dataset_fields AS cdf
						ON uff.user_field_id = cdf.id
					WHERE (uff.field_type NOT IN ("page_break", "restatement", "section_description") 
						OR uff.field_type IS NULL)';
				if ($formFieldId == 'all') {
					$sql .= ' AND uff.user_form_id = '.(int)$formId;
				} else {
					$sql .= ' AND uff.id = '.(int)$formFieldId;
				}
				$sql .= '
					ORDER BY p.ord, uff.ord';
				
				$result = ze\sql::select($sql);
				$mergeFields = '';
				while ($row = ze\sql::fetchAssoc($result)) {
					$mergeFields .= '<p>';
					if ($row['name']) {
						$mergeFields .= trim($row['name'], " \t\n\r\0\x0B:"). ': ';
					}
					if ($row['split_first_name_last_name']) {
						$mergeFields .= '[[first_name]] [[last_name]]';
					} else {
						$mergeFields .= '[['.$row['mergefield'].']]';
					}
					$mergeFields .= '</p>';
				}
				$values['meta_data/body'] .= $mergeFields;
				$values['meta_data/user_form_field'] = '';
			}
		} else {
			$fields['meta_data/user_form_field']['hidden'] = true;
		}
	}
	
}
