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


class zenario_common_features__admin_boxes__admin_change_email extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
        $box['key']['id'] = ze\admin::id();

        if ($box['key']['id']) {
			if (!$details = ze\row::get('admins', true, $box['key']['id'])) {
				exit;

			} elseif ($details['authtype'] == 'local') {
				$box['tabs']['details']['edit_mode']['enabled'] = true;

                $values['details/email'] = $details['email'];
			} else {
				$fields['details/desc']['snippet']['html'] = ze\admin::phrase("Your details are stored in a global database outside of this site's database. You can only make changes via the control site.");
				$fields['details/send_email_change_request']['disabled'] = true;
			}
        }
    }

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($fields['details/send_email_change_request']['pressed'] ?? false) {
			if (!$values['details/new_email']) {
				$fields['details/new_email']['error'] = ze\admin::phrase('Please enter an email address.');
			} elseif (!ze\ring::validateEmailAddress($values['details/new_email'])) {
				$fields['details/new_email']['error'] = ze\admin::phrase('Please enter a valid email address.');
			} elseif ($values['details/new_email'] == $values['details/email']) {
				$fields['details/new_email']['error'] = ze\admin::phrase('The new email address cannot be the same as the current one.');
			} else {
				//Prepare the email
				$details = ze\admin::details($box['key']['id']);

				$merge = [];
				$merge['NAME'] = ze::ifNull(trim($details['first_name'] . ' ' . $details['last_name']), $details['username']);
				$merge['URL'] = ze\link::protocol(). $_SERVER['HTTP_HOST'];
				$merge['SUBDIRECTORY'] = SUBDIRECTORY;
				$merge['IP'] = preg_replace('[^W\.\:]', '', ze\user::ip());

				$merge['CODE'] = $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_CODE'] = ze\ring::randomFromSet(5, 'ABCDEFGHIJKLMNPPQRSTUVWXYZ');
				$_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_NEW_EMAIL'] = $values['details/new_email'];
				$_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_TIMESTAMP_SET'] = strtotime(ze\date::now());

				$source = [];
				$dir = CMS_ROOT. 'zenario/admin/welcome/';
				$file = 'email_templates.yaml';
				if (substr($file, 0, 1) != '.') {
					$tagsToParse = ze\tuix::readFile($dir. $file);
					ze\tuix::parse($source, $tagsToParse, 'welcome');
					unset($tagsToParse);
				}
		
				$addressToOverriddenBy = false;

				foreach (['old_email' => $details['email'], 'new_email' => $values['details/new_email']] as $key => $emailTo) {
					//Do not send the confirmation code to the old address!
					if ($key == 'old_email') {
						$emailTemplateName = 'change_email_local_admin_edit_self_no_code';
					} else {
						$emailTemplateName = 'change_email_local_admin_edit_self';
					}

					$emailTemplate = $source['welcome']['email_templates'][$emailTemplateName];
				
					$message = $emailTemplate['body'];
					$message = nl2br($message);
				
					if (ze\module::inc('zenario_email_template_manager')) {
						zenario_email_template_manager::putBodyInTemplate($message);
					}
			
					$subject = $emailTemplate['subject'];
			
					foreach ($merge as $pattern => $replacement) {
						$message = str_replace('[['. $pattern. ']]', $replacement, $message);
					};
					
					ze\server::sendEmail(
						$subject, $message,
						$emailTo,
						$addressToOverriddenBy,
						$nameTo = $merge['NAME'],
						$addressFrom = false,
						$nameFrom = $emailTemplate['from'],
						false, false, false,
						$isHTML = true,
						false, false, false, false, '', '', 'To',
						$ignoreDebugMode = true);	//Admin email change emails should always be sent to the intended recipient,
													//even if debug mode is on.
				}

				$box['tabs']['details']['notices']['code_send_success']['show'] = true;
				$box['tabs']['details']['notices']['code_send_success']['message'] =
					ze\admin::phrase('Emails sent to "[[old_email]]" and "[[new_email]]".',
					['old_email' => $values['details/email'], 'new_email' => $values['details/new_email']]
				);

				$fields['details/code']['hidden'] = false;

				unset($fields['meta_data/test_send_button']['pressed']);

				$box['key']['code_already_sent'] = true;
			}
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($box['key']['code_already_sent']) {
			$box['tabs']['details']['notices']['code_send_success']['show'] = false;

			if (!$values['details/code']) {
				$fields['details/code']['error'] = ze\admin::phrase('Please enter the code.');
			} else {
				if (
					!empty($_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_CODE'])
					&& $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_CODE'] == $values['details/code']
					&& !empty($_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_NEW_EMAIL'])
				) {
					if (!empty($_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_TIMESTAMP_SET'])) {
						$date = new DateTime();
						$timestampNow = $date->getTimestamp();

						$timestampExpiryDate = DateTime::createFromFormat('U', $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_TIMESTAMP_SET']);
						$timestampExpiry = $timestampExpiryDate->modify('+10 minutes')->getTimestamp();

						if (!($timestampNow < $timestampExpiry)) {
							$fields['details/code']['error'] = ze\admin::phrase('The code has expired. Please send a new one.');
						}
					}
				} else {
					$fields['details/code']['error'] = ze\admin::phrase('Code not recognised.');
				}
			}
		} else {
			//Catch the case where someone is trying to save without having entered the code.
			if ($saving && !$values['details/code']) {
				$fields['details/code']['error'] = ze\admin::phrase('Please enter the code.');
			}
		}
	}

	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$adminId = ze\admin::id();

		if (!$box['key']['id'] || $box['key']['id'] != $adminId) {
			return false;
		}

		$adminDetails = ze\admin::details($adminId);

		if ($adminDetails['authtype'] != 'local') {
			return false;
		}

		//Change the email address...
		ze\row::set('admins', ['email' => ze\escape::sql($_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_NEW_EMAIL']), 'modified_date' => ze\date::now()], ['id' => (int) $adminId]);

		//... and send confirmation emails to both the new and old email address.
		$merge = [];
		$merge['NAME'] = ze::ifNull(trim($adminDetails['first_name']. ' '. $adminDetails['last_name']), $adminDetails['username']);
		$merge['OLD_EMAIL'] = $adminDetails['email'];
		$merge['NEW_EMAIL'] = $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_NEW_EMAIL'];
		$merge['URL'] = ze\link::protocol(). $_SERVER['HTTP_HOST'];
		$merge['SUBDIRECTORY'] = SUBDIRECTORY;

		$source = [];
		$dir = CMS_ROOT. 'zenario/admin/welcome/';
		$file = 'email_templates.yaml';
		if (substr($file, 0, 1) != '.') {
			$tagsToParse = ze\tuix::readFile($dir. $file);
			ze\tuix::parse($source, $tagsToParse, 'welcome');
			unset($tagsToParse);
		}

		$emailTemplateName = 'change_email_complete_local_admin_edit_self';
		$emailTemplate = $source['welcome']['email_templates'][$emailTemplateName];
	
		$message = $emailTemplate['body'];
		$message = nl2br($message);
	
		if (ze\module::inc('zenario_email_template_manager')) {
			zenario_email_template_manager::putBodyInTemplate($message);
		}

		$subject = $emailTemplate['subject'];

		foreach ($merge as $pattern => $replacement) {
			$message = str_replace('[['. $pattern. ']]', $replacement, $message);
		};

		$addressToOverriddenBy = false;

		foreach ([$merge['OLD_EMAIL'], $merge['NEW_EMAIL']] as $emailTo) {
			ze\server::sendEmail(
				$subject, $message,
				$emailTo,
				$addressToOverriddenBy,
				$nameTo = $merge['NAME'],
				$addressFrom = false,
				$nameFrom = $emailTemplate['from'],
				false, false, false,
				$isHTML = true,
				false, false, false, false, '', '', 'To',
				$ignoreDebugMode = true);	//Admin email change emails should always be sent to the intended recipient,
											//even if debug mode is on.
		}

		unset($_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_CODE'], $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_NEW_EMAIL'], $_SESSION['ADMIN_CHANGE_EMAIL_EDIT_SELF_TIMESTAMP_SET']);

		$message = '<!--Message_Type:Success-->';
		$message .= ze\admin::phrase('Email change successful.');
		ze\tuix::closeWithFlags(['close_with_message' => $message]);
		exit;
	}
}