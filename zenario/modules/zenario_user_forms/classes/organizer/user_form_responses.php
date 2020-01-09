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

class zenario_user_forms__organizer__user_form_responses extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name', 'profanity_filter_text'], $refinerId);
		$panel['title'] = ze\admin::phrase('Responses for form "[[name]]"', $form);
		
		if (!ze::setting('zenario_user_forms_set_profanity_filter') || !$form['profanity_filter_text']) {
			unset($panel['columns']['blocked_by_profanity_filter']);
			unset($panel['columns']['profanity_filter_score']);
			unset($panel['columns']['profanity_tolerance_limit']);
		} else {
			foreach($panel['items'] as $id => &$item) {
				$profanityValues = ze\row::get(ZENARIO_USER_FORMS_PREFIX. 'user_response',
					['blocked_by_profanity_filter', 'profanity_filter_score', 'profanity_tolerance_limit'],
					['id' => $id]);
				$profanityValueForPanel = ($profanityValues['blocked_by_profanity_filter'] == 1 ? "Yes" : "No");
				$item['blocked_by_profanity_filter'] = $profanityValueForPanel;
				$item['profanity_filter_score'] = $profanityValues['profanity_filter_score'];
				$item['profanity_tolerance_limit'] = $profanityValues['profanity_tolerance_limit'];
			}
		}
		
		if (!zenario_user_forms::isFormCRMEnabled($refinerId, false)) {
			unset($panel['columns']['crm_response']);
		}
		
		$panel['item_buttons']['view_response']['admin_box']['key']['form_id'] = 
		$panel['collection_buttons']['export']['admin_box']['key']['form_id'] = 
			$refinerId;
		
		
		$sql = '
			SELECT urd.value, urd.form_field_id, ur.id
			FROM '. DB_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
			INNER JOIN '. DB_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
				ON urd.user_response_id = ur.id
			WHERE ur.form_id = '. (int)$refinerId;
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if (isset($panel['items'][$row['id']])) {
				$panel['items'][$row['id']]['form_field_'.$row['form_field_id']] = $row['value'];
			}
		}
		
		//Get user and email from form response details if they cannot be found from the recorded user Id
		foreach ($panel['items'] as $responseId => $response) {
			if (!$response['user']) {
				$panel['items'][$responseId]['user'] = ze\admin::phrase('[Anonymous visitor]');
			}
		}
		
		//Hide these buttons if no items
		if (!$panel['items']) {
			$panel['collection_buttons']['export']['hidden'] = true;
			$panel['collection_buttons']['delete_form_responses']['hidden'] = true;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		ze\priv::exitIfNot('_PRIV_MANAGE_FORMS');
		$formId = $refinerId;
		
		//Delete all responses
		if (($_POST['delete_form_responses'] ?? false) && $formId) {
			$result = ze\row::query(
				ZENARIO_USER_FORMS_PREFIX . 'user_response', 
				['id'], 
				['form_id' => $formId]
			);
			while ($row = ze\sql::fetchAssoc($result)) {
				zenario_user_forms::deleteFormResponse($row['id']);
			}
		//Delete single response
		} else if ($_POST['delete_form_response'] ?? false) {
			zenario_user_forms::deleteFormResponse($ids);
		}
	}
}