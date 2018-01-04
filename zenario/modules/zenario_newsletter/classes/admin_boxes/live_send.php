<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_newsletter__admin_boxes__live_send extends zenario_newsletter {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($id = $box['key']['id']) {
			$recipients = self::newsletterRecipients($id, 'count');
			$newsletter = $this->loadDetails($id);
			$fields['send/desc']['snippet']['html'] = nAdminPhrase(
				'Are you sure you wish to send the Newsletter "[[newsletter_name]]" to [[recipients]] Recipient? Click-throughs counts will be reset.',
				'Are you sure you wish to send the Newsletter "[[newsletter_name]]" to [[recipients]] Recipients? Click-throughs counts will be reset.',
				$recipients,
				array('newsletter_name' => $newsletter['newsletter_name'], 'recipients' => $recipients));
				
			$sql = '
				SELECT COUNT(*) 
				FROM '.DB_NAME_PREFIX.'admins
				WHERE status = \'active\'';
			$result = sqlSelect($sql);
			$row = sqlFetchRow($result);
			
			if ($row[0] > 1) {
				$fields['send/admin_options']['values']['all_admins']['label'] = adminPhrase(
					'Send all [[count]] administrators a copy of the email', 
					array('count' => $row[0]));
			} else {
				unset($fields['send/admin_options']['values']['all_admins']);
			}
		}
		
		
		// Scheduled publishing options
		if (inc('zenario_scheduled_task_manager')) {
			$allJobsEnabled = setting('jobs_enabled');
			$scheduledSendingEnabled = getRow('jobs', 'enabled', array('job_name' => 'jobSendNewsletters', 'module_class_name' => 'zenario_newsletter'));
			if (!($allJobsEnabled && $scheduledSendingEnabled)) {
				$scheduledTaskLink = absCMSDirURL() . 
					'zenario/admin/organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				
				
				$fields['send/send_time_options']['values']['schedule']['disabled'] = true;
				$fields['send/send_time_options']['values']['schedule']['post_field_html'] = "
					<br />
					<br />
					Scheduled sending is not available. The Scheduled Task Manager is installed 
					but the Scheduled Publishing task is not enabled. 
					<a href='".$scheduledTaskLink."'>Click for more info.</a>";
			} else {
				$values['send/send_date'] = date('Y-m-d');
				for ($i = 0; $i <= 23; $i++) {
					$iPadded = (string)str_pad($i, 2, '0', STR_PAD_LEFT);
					$fields['send/send_hours']['values'][$iPadded] = $iPadded;
				}
				$fields['send/send_hours']['value'] = '01';
				for ($i = 0; $i <= 59; $i++) {
					$iPadded = (string)str_pad($i, 2, '0', STR_PAD_LEFT);
					$fields['send/send_mins']['values'][$iPadded] = $iPadded;
				}
				
			}
		
		} else {
			$fields['send/send_time_options']['hidden'] = true;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...your PHP code...//
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['send/send_time_options'] == 'schedule') {
			$now = strtotime('now');
			$scheduledDate = strtotime($values['send/send_date'] . ' ' . $values['send/send_hours'] . ':' . $values['send/send_mins']);
			if ($scheduledDate < $now) {
				$box['tabs']['send']['errors'][] = adminPhrase('The scheduled sending time cannot be in the past.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Send the newsletter
		if (($ids = $box['key']['id']) && checkPriv('_PRIV_SEND_NEWSLETTER') && zenario_newsletter::checkIfNewsletterIsADraft($ids)) {
			
			//If the admin is trying to send this newsletter, try to populate its recipients table
			if (!self::newsletterRecipients($ids, 'populate')) {
				echo adminPhrase('This Newsletter has no recipients to send to.');
			
			} else {
				//Update it to the "_IN_PROGRESS" state
				$smartGroupDescriptions = array();
				foreach ( getRowsArray(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', 'smart_group_id', array('newsletter_id' => $ids)) as $smartGroupId )  {
					$smartGroupDescriptions[] = getSmartGroupDescription($smartGroupId);
				}
				$smartGroupDescriptions = (count($smartGroupDescriptions)>1?'(':'') . adminPhrase(implode(") OR (", $smartGroupDescriptions )) . (count($smartGroupDescriptions)>1?')':'');
				
				
				$sql = "
					UPDATE ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters set
						status = '_IN_PROGRESS',
						sent_by_id = ". (int) $_SESSION['admin_userid']. ",
						smart_group_descriptions_when_sent_out = " . ($smartGroupDescriptions?("'" . sqlEscape($smartGroupDescriptions) . "'"): 'NULL' ) . ",
						url = '". sqlEscape(zenario_newsletter::getTrackerURL()). "'";
				if ($values['send/send_time_options'] == 'schedule') {
					$scheduledSendDate = $values['send/send_date'] . ' ' . $values['send/send_hours'] . ':' . $values['send/send_mins'] . ':00';
					$sql .= "
						,scheduled_send_datetime = '" . sqlEscape($scheduledSendDate) . "'
						,date_sent = '" . sqlEscape($scheduledSendDate) . "'";
				} else {
					$sql .= "
						,date_sent = NOW()";
				}
				$sql .= "
					WHERE id = ". (int) $ids;
				sqlQuery($sql);
				
				
				//Is the Scheduled Task set up and running?
				if (!windowsServer()) {
					if (inc('zenario_scheduled_task_manager')) {
						if (zenario_scheduled_task_manager::checkScheduledTaskRunning('jobSendNewsletters')) {
							
							//Rely on the Scheduled Task to send the newsletter
							$msg = '<!--Message_Type:Success-->';
							
							if ($values['send/send_time_options'] == 'schedule') {
								$date = formatDateTimeNicely($scheduledSendDate, '_MEDIUM');
								$msg .= '<p>'. adminPhrase('This Newsletter will commence sending on [[date]].', array('date' => $date)). '</p>';
							} else {
								$msg .= '<p>'. adminPhrase('This Newsletter will commence sending within the next 5 minutes.'). '</p>';
							}
							
							$msg .= '<p>'. adminPhrase('This may take some time. You can view the Newsletter Outbox to check live sending progress, or the Newsletter Archive to view the receipt status of this Newsletter by User.'). '</p>';
							$msg .= '<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/process//'. (int) $ids. '//" onclick="zenarioA.closeFloatingBox();">'. adminPhrase('View Outbox.'). '</a></p>';
							
							
							closeFABWithFlags(['close_with_message' => $msg]);
							exit;
						}
					}
				}
				
				//Is the server a Windows Server? Is the Scheduled Task Manager Module not running?
				//If so: sorry; no batch sending :(
				//We shall have to try and send all of the newsletters now, in this request
				set_time_limit(60 * 10);
				self::sendNewsletterToAdmins($ids, $values['send/admin_options']);
				self::sendNewsletter($ids, true);
				
				$msg = '<!--Message_Type:Success-->';
				$msg .= '<p>'. adminPhrase('Newsletter Sent.'). '</p>';
				$msg .= '<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/archive//'. (int) $ids. '//" onclick="zenarioA.closeFloatingBox();">'. adminPhrase('View Sent Newsletter in Archive.'). '</a></p>';
				
				closeFABWithFlags(['close_with_message' => $msg]);
				exit;
			}
		}
	}
}
