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

class zenario_common_features__organizer__email_templates extends zenario_common_features {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...your PHP code...//
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if (ze::setting('debug_override_enable')) {
			$sendToDebugAddressOrDontSentAtAll = ze::setting('send_to_debug_address_or_dont_send_at_all');
			$panel['notice']['show'] = true;
			
			if ($sendToDebugAddressOrDontSentAtAll == 'send_to_debug_email_address') {
				$panel['notice']['message'] =
					ze\admin::phrase('Email debug mode is enabled, all emails will be sent to [[email]].',
						['email' => ze::setting('debug_override_email_address')]);
			} elseif ($sendToDebugAddressOrDontSentAtAll == 'dont_send_at_all') {
				$panel['notice']['message'] =
					ze\admin::phrase('Email debug mode is enabled, emails will not be sent at all.');
			}
		}
		
		if ($refinerName == 'email_templates_using_image') {
			$mrg = ze\row::get('files', ['filename'], $refinerId);
			$panel['title'] = ze\admin::phrase('Email templates using the image "[[filename]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no email templates using the image "[[filename]]"', $mrg);
			
			// Hide collection buttons
			$panel['collection_buttons']['create_template']['hidden'] = 
			$panel['collection_buttons']['test']['hidden'] = true;
		}
		
		$panelItemKeys = array_keys($panel['items']);
		
		if ($panelItemKeys) {
			$sql = "
				SELECT t.code, COUNT(etsl.id) AS sent_email_count
				FROM " . DB_PREFIX . "email_template_sending_log AS etsl
				LEFT JOIN " . DB_PREFIX . "email_templates AS t
					ON etsl.email_template_id = t.id
				WHERE t.code IN (" . ze\escape::in($panelItemKeys) . ")
				GROUP BY t.code";
		
			$result = ze\sql::fetchAssocs($sql);
		
			$emailTemplateSentEmailCountArray = [];
		
			if ($result) {
				foreach ($result as $templateSentEmailRow) {
					$emailTemplateSentEmailCountArray[$templateSentEmailRow['code']] = $templateSentEmailRow['sent_email_count'];
				}
			}
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
			
			$details = self::checkTemplateIsProtectedAndGetCreatedDetails($K);
			if ($details['protected']) {
				$panel['items'][$K]['protected'] = true;
			}
			
			if (!empty($emailTemplateSentEmailCountArray[$K])) {
				$panel['items'][$K]['sent_email_count'] = $emailTemplateSentEmailCountArray[$K];
			} else {
				$panel['items'][$K]['sent_email_count'] = 0;
			}
		}
		
		if (($adminId = ze\admin::id()) && ($adminDetails = ze\admin::details($adminId))) {
			$adminEmail = $adminDetails['email'];
			ze\lang::applyMergeFields($panel['item_buttons']['test_send_template']['ajax']['confirm']['message'], ['admin_email_address' => $adminEmail]);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$action = ze::post('action');
		if ($action == 'delete_template' && ze\priv::check('_PRIV_MANAGE_EMAIL_TEMPLATE')) {
			foreach (explode(',',$ids) as $code) {
				$sql = "
					DELETE FROM "
							. DB_PREFIX.  "email_templates
					WHERE 
						code = '". ze\escape::sql($code) . "'";
				ze\sql::update($sql);
				
				ze\contentAdm::removeItemFromPluginSettings('email_template', 0, $code);
			}
		} elseif ($action == 'test_send_template') {
			foreach (explode(',',$ids) as $code) {
				if ($emailTemplate = self::getTemplateByCode($code)) {
					$adminDetails = ze\admin::details(ze\admin::id());
					$email = $adminDetails['email'];
					
					//Try and ensure that we use absolute URLs where possible
					ze\contentAdm::addAbsURLsToAdminBoxField($emailTemplate['body']);
					
					$body = $emailTemplate['body'];
					
					$useStandardEmailTemplate = '';
					switch ($emailTemplate['use_standard_email_template']) {
						case 0:
							$useStandardEmailTemplate = 'no';
							break;
						case 1:
							$useStandardEmailTemplate = 'yes';
							break;
						case 2:
							$useStandardEmailTemplate = 'twig';
							break;
					}
					
					if ($useStandardEmailTemplate == 'twig') {
						$body = ze\twig::render("\n". $body, []);
					}
					
					if ($emailTemplate['apply_css_rules']) {
						$cssRules = ze::setting('email_css_rules');
						static::putHeadOnBody($cssRules, $body);
					}
					
					if ($useStandardEmailTemplate != 'no') {
						static::putBodyInTemplate($body);
					}
					
					$emailAddressFrom = ze::setting('email_address_from');
					$emailNameFrom = ze::setting('email_name_from');
					
					$error = false;
					$attachments = [];
					if ($emailTemplate['include_a_fixed_attachment'] && $emailTemplate['selected_attachment']) {
						$document = ze\row::get('documents', ['file_id', 'privacy'], ['id' => $emailTemplate['selected_attachment']]);
						
						if ($document) {
							if ($document['privacy'] != 'offline') {
								$file = ze\file::link($document['file_id']);
								$attachments[] = realpath(rawurldecode($file));
							} else {
								$error = true;
								echo '<p>' . ze\admin::phrase('The test email could not be sent. The selected email template has an attachment which is [[privateOrOffline]]. Please change its privacy settings, or choose a different document.', ['privateOrOffline' => $document['privacy']]) . '</p>';
							}
						} else {
							$error = true;
							echo '<p>' . ze\admin::phrase('The test email could not be sent. The selected email template has an attachment which is missing. Please choose a different document.') . '</p>';
						}
					}
					
					if (!$error) {
						if (!static::testSendEmailTemplate(
								$emailTemplate['id'],
								$body,
								$adminDetails, 
								$email, 
								$emailTemplate['subject'], 
								$emailAddressFrom,
								$emailNameFrom,
								$attachments
							)
						) {
							echo '<p>' . ze\admin::phrase("The test email could not be sent. There could be a problem with the site's email system.") . '</p>';;
						} else {
							ze\escape::bFlag('MESSAGE_TYPE', 'Success');
							echo '<p>' . ze\admin::phrase('Test email sent to [[email]].', ['email' => $email]) , '</p>';
						}
					}
				}
			
			}
			
			
			
			
			
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		//...your PHP code...//
	}
}