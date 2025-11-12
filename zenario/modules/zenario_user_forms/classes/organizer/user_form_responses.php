<?php
/*
 * Copyright (c) 2025, Tribal Limited
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
		$whereStatement = '';
		$formsProfanityFilterSiteSetting = ze::setting('zenario_user_forms_set_profanity_filter');

		if ($refinerName == 'form_id') {
			$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name', 'profanity_filter_text', 'period_to_delete_response_headers', 'show_checkbox_for_allocating_form_responses', 'form_responses_allocate_checkbox_label'], $refinerId);
			$panel['title'] = ze\admin::phrase('Responses for form "[[name]]" (ID: [[form_id]])', ['name' => $form['name'], 'form_id' => (int) $refinerId]);

			//Information to view Data Protection settings
			$phrase = $noticeType = '';
			
			zenario_user_forms::formatDataProtectionValueNicelyForOrganizerNotice($individualFormSetting = $form['period_to_delete_response_headers'], $phrase, $noticeType);

			$href = ze\link::absolute() .'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$linkStart = "<a target='_blank' href='" . $href . "'>";
			$linkEnd = "</a>";

			$phrase .= " [[link_start]]View Data Protection settings[[link_end]].";
			
			$panel['notice']['show'] = true;
			$panel['notice']['type'] = $noticeType;
			$panel['notice']['message'] = ze\admin::phrase($phrase, ['link_start' => $linkStart, 'link_end' => $linkEnd]);
			$panel['notice']['html'] = true;

			if (!$formsProfanityFilterSiteSetting || !$form['profanity_filter_text']) {
				unset($panel['columns']['blocked_by_profanity_filter']);
				unset($panel['columns']['profanity_filter_score']);
				unset($panel['columns']['profanity_tolerance_limit']);
			} else {
				foreach($panel['items'] as $id => &$item) {
					$profanityValues = ze\row::get('user_response',
						['blocked_by_profanity_filter', 'profanity_filter_score', 'profanity_tolerance_limit'],
						['id' => $id]);
					$profanityValueForPanel = ($profanityValues['blocked_by_profanity_filter'] == 1 ? "Yes" : "No");
					$item['blocked_by_profanity_filter'] = $profanityValueForPanel;
					$item['profanity_filter_score'] = $profanityValues['profanity_filter_score'];
					$item['profanity_tolerance_limit'] = $profanityValues['profanity_tolerance_limit'];
				}
			}
			
			if (ze\module::inc('zenario_crm_form_integration')) {
				$panel['columns']['crm_response']['show_by_default'] = true;
			}

			if (!zenario_user_forms::isFormCRMEnabled($refinerId, false)) {
				unset($panel['columns']['crm_response']);
			}

			$panel['collection_buttons']['export']['admin_box']['key']['form_id'] = $refinerId;
			
			$whereStatement = '
				WHERE ur.form_id = '. (int) $refinerId;
			
			$panel['columns']['form']['hidden'] = true;
			unset($panel['columns']['form']['always_show']);

			$panel['collection_buttons']['delete_form_responses']['ajax']['confirm']['message'] = ze\admin::phrase("Are you sure you wish to delete every response for this form?");
		} elseif ($refinerName == 'user_id') {
			//Information to view Data Protection settings
			$setting = ze::setting('period_to_delete_the_form_response_log_headers');

			$phrase = $noticeType = '';
			zenario_user_forms::formatDataProtectionValueNicelyForOrganizerNotice($individualFormSetting = null, $phrase, $noticeType);

			$href = ze\link::absolute() .'organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
			$linkStart = "<a target='_blank' href='" . $href . "'>";
			$linkEnd = "</a>";

			$phrase .= " [[link_start]]View Data Protection settings[[link_end]].";
			
			$panel['notice']['show'] = true;
			$panel['notice']['type'] = $noticeType;
			$panel['notice']['message'] = ze\admin::phrase($phrase, ['link_start' => $linkStart, 'link_end' => $linkEnd]);
			$panel['notice']['html'] = true;

			//Set panel title, columns and button values. Hide the "Export" button, as it's designed to export responses for 1 form
			//rather than potentially multiple forms coming from the same user.
			$user = ze\row::get('users', ['identifier'], $refinerId);
			$panel['title'] = ze\admin::phrase('Form responses for user "[[identifier]]"', $user);
			$panel['collection_buttons']['export']['hidden'] = true;

			$whereStatement = '
				WHERE ur.user_id = '. (int) $refinerId;
			
			$panel['columns']['user']['hidden'] = true;
			unset($panel['columns']['user']['always_show']);

			$panel['collection_buttons']['delete_form_responses']['ajax']['confirm']['message'] = ze\admin::phrase("Are you sure you wish to delete this user's response to every form?");
		}
		
		
		$sql = '
			SELECT urd.value, urd.form_field_id, ur.id
			FROM '. DB_PREFIX. 'user_response_data AS urd
			INNER JOIN '. DB_PREFIX. 'user_response AS ur
				ON urd.user_response_id = ur.id' . 
			$whereStatement;
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			if (isset($panel['items'][$row['id']])) {
				$panel['items'][$row['id']]['form_field_'.$row['form_field_id']] = $row['value'];
			}
		}
		
		//Get user and email from form response details if they cannot be found from the recorded user Id
		if ($refinerName == 'form_id') {
			$responsesCanBeAllocated = $form['show_checkbox_for_allocating_form_responses'];
			
			//Check if the user identifier column is encrypted
			$identifierIsEncrypted = false;
			if (ze::$dbL->columnIsEncrypted('users', 'identifier')) {
				$identifierIsEncrypted = true;
			}
			
			foreach ($panel['items'] as $responseId => &$response) {
				$userPhrase = '';
				if ($response['user_id']) {
					if ($identifierIsEncrypted) {
						$response['user_identifier'] = ze\row::get('users', 'identifier', ['id' => $response['user_id']]);
					}
					
					if ($response['user_identifier']) {
						$usersPanelLink = ze\link::absolute() . 'organizer.php#zenario__users/panels/users//' . (int) $response['user_id'] . '~-' . $response['user_identifier'];
						$userPhrase = '<a href="' . $usersPanelLink . '" target="_blank">' . $panel['items'][$responseId]['user_identifier'] . '</a>';
					}
				} else {
					if ($response['user_deleted']) {
						$userPhrase = 'Deleted user account, view response for details';
					} else {
						$userPhrase = 'Visitor, view response for details';
					}
				}
				
				$panel['items'][$responseId]['user'] = ze\admin::phrase($userPhrase);
				
				if ($responsesCanBeAllocated) {
					$label = $form['form_responses_allocate_checkbox_label'];
					$labelMergeFields = [];
					
					if (!empty($response['allocated_to_admin_id']) && !empty($response['allocated_to_admin_datetime'])) {
						$label .= ' [[allocated_relative_date]] by [[admin_name]]';
			
						$timestampAllocated = strtotime($response['allocated_to_admin_datetime']);
						$labelMergeFields = ['allocated_relative_date' => ze\date::formatRelativeDateTime($timestampAllocated, $maxPeriod = "day", $addFullTime = false), 'admin_name' => ze\admin::formatName($response['allocated_to_admin_id'])];
						
						$response['allocated_to_admin'] = ze\admin::phrase($label, $labelMergeFields);
					}
				}
				
				if ($response['sent_to_admins_or_users']) {
					$response['processing_actions'] = ze\admin::phrase('Sent to [[sent_to_admins_or_users]]', ['sent_to_admins_or_users' => $response['sent_to_admins_or_users']]);
				}
			}
			
			if (!$responsesCanBeAllocated) {
				$panel['columns']['allocated_to_admin']['hidden'] = true;
			}
		} elseif ($refinerName == 'user_id') {
			if (!$formsProfanityFilterSiteSetting) {
				unset($panel['columns']['blocked_by_profanity_filter']);
				unset($panel['columns']['profanity_filter_score']);
				unset($panel['columns']['profanity_tolerance_limit']);
			}

			foreach ($panel['items'] as $responseId => $response) {
				$formsPanelLink = ze\link::absolute() . 'organizer.php#zenario__user_forms/panels/user_forms//' . (int) $response['form_id'] . '~-' . (int) $response['form_id'];
				$panel['items'][$responseId]['form'] = '<a href="' . $formsPanelLink . '" target="_blank">' . $panel['items'][$responseId]['form'] . ' (ID: ' . (int) $response['form_id'] . ')</a>';

				$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name', 'profanity_filter_text', 'period_to_delete_response_headers'], $response['form_id']);

				if ($formsProfanityFilterSiteSetting) {
					$profanityValues = ze\row::get('user_response',
						['blocked_by_profanity_filter', 'profanity_filter_score', 'profanity_tolerance_limit'],
						['id' => $responseId]);
					$profanityValueForPanel = ($profanityValues['blocked_by_profanity_filter'] == 1 ? "Yes" : "No");
					$panel['items'][$responseId]['blocked_by_profanity_filter'] = $profanityValueForPanel;
					$panel['items'][$responseId]['profanity_filter_score'] = $profanityValues['profanity_filter_score'];
					$panel['items'][$responseId]['profanity_tolerance_limit'] = $profanityValues['profanity_tolerance_limit'];
				}
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
		
		//Delete all responses
		if (ze::post('delete_form_responses')) {
			if ($refinerName == 'form_id' && ($formId = $refinerId)) {
				$result = ze\row::query('user_response', ['id'], ['form_id' => $formId]);

				while ($row = ze\sql::fetchAssoc($result)) {
					zenario_user_forms::deleteFormResponse($row['id']);
				}
			} elseif ($refinerName == 'user_id' && ($userId = $refinerId)) {
				$result = ze\row::query('user_response', ['id'], ['user_id' => $userId]);

				while ($row = ze\sql::fetchAssoc($result)) {
					zenario_user_forms::deleteFormResponse($row['id']);
				}
			}
		//Delete single response or multiple selected responses
		} else if (ze::post('delete_form_response')) {
			foreach (explode(',', $ids) as $id) {
				zenario_user_forms::deleteFormResponse($id);
			}
		}
	}
}