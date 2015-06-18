<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
	case 'zenario__email_template_manager/panels/newsletter_templates':
		if (post('delete') && checkPriv('_PRIV_EDIT_NEWSLETTER')) {
			foreach(explode(',', $ids) as $id) {
				zenario_newsletter::deleteNewsletterTemplate($id);
			}
		}
		break;
		
	case 'zenario__email_template_manager/panels/newsletters':
		if (post('delete') && checkPriv('_PRIV_EDIT_NEWSLETTER')) {
			
			foreach(explode(',', $ids) as $id) {
				zenario_newsletter::deleteNewsletter($id);
			}
			
		
		//Send the newsletter
		} elseif (post('duplicate') && checkPriv('_PRIV_EDIT_NEWSLETTER')) {

			$admin_id = adminId();
			$table_newsletters = DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . "newsletters"; 
			$copy_cols = "subject, email_address_from, email_name_from, url, body, 
				status, delete_account_text, smart_group_descriptions_when_sent_out";
			
			$sql = "INSERT INTO $table_newsletters(newsletter_name, $copy_cols, date_created, created_by_id)
				    SELECT CONCAT(nli.newsletter_name, ' (copy ', IFNULL((SELECT COUNT(*) 
					FROM $table_newsletters nlc
					WHERE newsletter_name LIKE CONCAT(nli.newsletter_name, '%')), 0), ')') AS newsletter_name, 
					$copy_cols, CURRENT_TIMESTAMP, $admin_id
				    FROM $table_newsletters AS nli
				    WHERE id=" . (int)$ids;
			
			sqlUpdate($sql, false);
			$new_id = sqlInsertId();
			
			if($new_id) {
			    $table_newsletter_smart_group_link = DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . "newsletter_smart_group_link";
			    $new_id = (int)$new_id;
			    
			    $sql = "INSERT INTO $table_newsletter_smart_group_link(newsletter_id, smart_group_id)
				    SELECT $new_id, smart_group_id FROM $table_newsletter_smart_group_link
				    WHERE newsletter_id=" . (int)$ids;
			    sqlQuery($sql);
				
				//now lets see if we made the first copy and update (copy 1) by (copy)
				$current_newsletter_name = getRow(ZENARIO_NEWSLETTER_PREFIX . "newsletters", "newsletter_name", $new_id);
				$new_newsletter_name = preg_replace('/\(copy 1\)$/', "(copy)", $current_newsletter_name);
				if($new_newsletter_name != $current_newsletter_name) {
					setRow(ZENARIO_NEWSLETTER_PREFIX . "newsletters", array('newsletter_name' => $new_newsletter_name), $new_id);
				}
				
				return $new_id;
			}
		
		//Send the newsletter
		} elseif (post('send') && checkPriv('_PRIV_SEND_NEWSLETTER') && zenario_newsletter::checkIfNewsletterIsADraft($ids)) {
			
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
				self::sendNewsletterToAdmins($ids);
				self::sendNewsletter($ids, true);
				
				echo '<!--Message_Type:Success-->
					<p>', adminPhrase('Newsletter Sent.'), '</p>
					<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/archive//', (int) $ids, '//" onclick="zenarioA.closeFloatingBox();">', adminPhrase('View Sent Newsletter in Archive.'), '</a></p>';
		}
		
		
		//Attempt to resume sending the newsletter
		} elseif (post('resume') && checkPriv('_PRIV_SEND_NEWSLETTER') && $this->checkIfNewsletterIsInProgress($ids)) {
			//Note: same code as above
			set_time_limit(60 * 10);
			self::sendNewsletter($ids);
			
			echo '<!--Message_Type:Success-->
				<p>', adminPhrase('Newsletter Sent.'), '</p>
				<p><a href="#zenario__email_template_manager/panels/newsletters/refiners/drafts////collection_buttons/archive//', (int) $ids, '//" onclick="zenarioA.closeFloatingBox();">', adminPhrase('View Sent Newsletter in Archive.'), '</a></p>';
		}
			
		break;
			
	
	case 'zenario__content/panels/email_images_for_newsletters':
		$key = array(
			'foreign_key_to' => 'newsletter',
			'foreign_key_id' => $refinerId);
		$usage = 'image';
		$privCheck = checkPriv('_PRIV_EDIT_NEWSLETTER');
		$movie = false;
		
		return require funIncPath(CMS_ROOT. moduleDir('zenario_common_features', 'fun'), 'media.handleOrganizerPanelAJAX');

}