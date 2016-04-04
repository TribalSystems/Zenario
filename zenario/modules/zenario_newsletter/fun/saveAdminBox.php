<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


switch ($path) {
	case 'zenario_newsletter_template':
		
		addAbsURLsToAdminBoxField($box['tabs']['details']['fields']['body']);
		
		
		$record = array(
			'name' => $values['details/name'],
			'body' => $values['details/body'],
			'head' => $values['advanced/head']);
		
		if ($box['key']['id']) {
			$record['date_modified'] = now();
			$record['modified_by_id'] = adminId();
		} else {
			$record['date_created'] = now();
			$record['created_by_id'] = adminId();
		}
		
		$box['key']['id'] = setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', $record, $box['key']['id']);
		
		$body = $values['details/body'];
		$files = array();
		$htmlChanged = false;
		addImageDataURIsToDatabase($body, absCMSDirURL());
		syncInlineFileLinks($files, $body, $htmlChanged);
		syncInlineFiles(
			$files,
			array('foreign_key_to' => 'newsletter_template', 'foreign_key_id' => $box['key']['id']),
			$keepOldImagesThatAreNotInUse = false);
		
		if ($htmlChanged) {
			setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', array('body' => $body), $box['key']['id']);
		}
		
		break;
		
	
	case 'zenario_newsletter':
		if (!checkPriv('_PRIV_EDIT_NEWSLETTER')) {
			exit;
		}
		
		
		if (engToBooleanArray($box['tabs']['meta_data'], 'edit_mode', 'on')) {
			
			addAbsURLsToAdminBoxField($box['tabs']['meta_data']['fields']['body']);
			
			
			$id = $box['key']['id'];
			$record = array(
				'newsletter_name' => $values['meta_data/newsletter_name'],
				'subject' => $values['meta_data/subject'],
				'email_name_from' => $values['meta_data/email_name_from'],
				'email_address_from' => $values['meta_data/email_address_from'],
				'body' =>  $values['meta_data/body'],
				'head' => $values['advanced/head']
			);
			
			if($id) {
				$record['date_modified'] = now();
				$record['modified_by_id'] = adminId();
			} else {
				$record['date_created'] = now();
				$record['status'] = '_DRAFT';
				$record['created_by_id'] = adminId();
			}
			
			$box['key']['id'] = setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', $record, $id);

			deleteRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', array('newsletter_id' => $box['key']['id']));
			foreach (explode(',', $values['unsub_exclude/recipients']) as $smartGroupId) {
				if ((int)$smartGroupId) {
					setRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', array('newsletter_id' => $box['key']['id'], 'smart_group_id' => (int) $smartGroupId)); 
				}
			}


			$body = $values['meta_data/body'];
			$files = array();
			$htmlChanged = false;
			addImageDataURIsToDatabase($body, absCMSDirURL());
			syncInlineFileLinks($files, $body, $htmlChanged);
			syncInlineFiles(
				$files,
				array('foreign_key_to' => 'newsletter', 'foreign_key_id' => $box['key']['id']),
				$keepOldImagesThatAreNotInUse = true);
			
			if ($htmlChanged) {
				setRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', array('body' => $body), $box['key']['id']);
			}
		}
		
		if ($box['key']['id'] && engToBooleanArray($box, 'tabs', 'unsub_exclude', 'edit_mode', 'on')) {
			setRow(
				ZENARIO_NEWSLETTER_PREFIX. 'newsletters',
				array(
					'unsubscribe_text' 
							=> ($values['unsubscribe_link'] == 'unsub') ? $values['unsubscribe_text']: null,
					'delete_account_text' 
							=> ($values['unsubscribe_link'] == 'delete') ? $values['delete_account_text']: null
					),
				$box['key']['id']);

			deleteRow(
				ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
				array('newsletter_id' => $box['key']['id'], 'include' => 0));
			
			if (engToBooleanArray($values,'exclude_previous_newsletters_recipients_enable')) {
				foreach (explode(',', $values['exclude_previous_newsletters_recipients']) as $id) {
					if ($id) {
						insertRow(
							ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
							array('newsletter_id' => $box['key']['id'], 'include' => 0, 'sent_newsletter_id' => $id));
					}
				}
			}
		}
		break;
	
	case 'zenario_live_send':
		
		//Send the newsletter
		if (($ids = $box['key']['id']) && checkPriv('_PRIV_SEND_NEWSLETTER') && zenario_newsletter::checkIfNewsletterIsADraft($ids)) {
			
			//If the admin is trying to send this newsletter, try to populate its recipients table
			if (!zenario_newsletter::newsletterRecipients($ids, 'populate')) {
				echo adminPhrase('This Newsletter has no recipients to send to.');
			
			} else {
				//Update it to the "_IN_PROGRESS" state
				$smartGroupDescriptions = array();
				foreach ( getRowsArray(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', 'smart_group_id', array('newsletter_id' => $ids)) as $smartGroupId )  {
					$smartGroupDescriptions[] = getSmartGroupDescription($smartGroupId);
				}
				$smartGroupDescriptions = (count($smartGroupDescriptions)>1?'(':'') . adminPhrase(implode(") OR (", $smartGroupDescriptions )) . (count($smartGroupDescriptions)>1?')':'');
				
				
				$sql = 
					"UPDATE ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters set
						status = '_IN_PROGRESS',
						date_sent = NOW(),
						sent_by_id = ". (int) $_SESSION['admin_userid']. ",
						smart_group_descriptions_when_sent_out = " . ($smartGroupDescriptions?("'" . sqlEscape($smartGroupDescriptions) . "'"): 'NULL' ) . ",
						url = '". sqlEscape(zenario_newsletter::getTrackerURL()). "'
					WHERE id = ". (int) $ids;
				sqlQuery($sql);
				
				
				//Is the Scheduled Task set up and running?
				if (!windowsServer()) {
					if (inc('zenario_scheduled_task_manager')) {
						if (zenario_scheduled_task_manager::checkScheduledTaskRunning('jobSendNewsletters')) {
							//Rely on the Scheduled Task to send the newsletter
							echo '<!--Message_Type:Success-->
								<p>', adminPhrase('This Newsletter will commence sending within the next 5 minutes.'), '</p>
								<p>', adminPhrase('This may take some time. You can view the Newsletter Outbox to check live sending progress, or the Newsletter Archive to view the receipt status of this Newsletter by User.'), '</p>
								<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/process//', (int) $ids, '//" onclick="zenarioA.closeFloatingBox();">', adminPhrase('View Outbox.'), '</a></p>';
							
							return;
						}
					}
				}
				
				//Is the server a Windows Server? Is the Scheduled Task Manager Module not running?
				//If so: sorry; no batch sending :(
				//We shall have to try and send all of the newsletters now, in this request
				set_time_limit(60 * 10);
				self::sendNewsletterToAdmins($ids, $values['send/admin_options']);
				self::sendNewsletter($ids, true);
				
				echo '<!--Message_Type:Success-->
					<p>', adminPhrase('Newsletter Sent.'), '</p>
					<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/archive//', (int) $ids, '//" onclick="zenarioA.closeFloatingBox();">', adminPhrase('View Sent Newsletter in Archive.'), '</a></p>';
				exit;
			}
		}
		break;
}