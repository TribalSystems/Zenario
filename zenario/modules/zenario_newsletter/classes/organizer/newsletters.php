<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_newsletter__organizer__newsletters extends zenario_newsletter {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($refinerName == 'archive') {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_archive'];
			$panel['default_sort_column'] = 'date_sent';
			$panel['bold_columns_in_list_view'] = 'newsletter_name';
		} elseif ($refinerName == 'outbox') {
			$panel['db_items']['where_statement'] = '';
		}
		
		if ($refinerName == 'outbox' || $refinerName == 'archive') {
			unset($panel['columns']['recipients']);
			$panel['item_buttons']['edit']['label'] = 'Show newsletter details';
		}
		
		if ($refinerName != 'outbox') {
			unset($panel['columns']['progress_sent']);
			unset($panel['columns']['progress_total']);
		}
		
		if ($refinerName != 'archive') {
			unset($panel['columns']['recipient_users']);
			unset($panel['columns']['smart_group_rules']);
			
			if ($refinerName != 'outbox') {
				unset($panel['columns']['date_sent']);
			}
		}
		
		if (!windowsServer()) {
			if (inc('zenario_scheduled_task_manager')) {
				if (zenario_scheduled_task_manager::checkScheduledTaskRunning('jobSendNewsletters')) {
					unset($panel['item_buttons']['resume']);
				}
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$panel['title'] = adminPhrase('Draft Newsletters');
		$panel['item']['css_class'] = 'zenario_newsletter_draft';

		if ($refinerName == 'outbox' || $refinerName == 'archive') {
			$panel['collection_buttons']['create']['hidden'] = true;
			$panel['collection_buttons']['process']['hidden'] = true;
			$panel['collection_buttons']['archive']['hidden'] = true;
			$panel['item_buttons']['group_members']['hidden'] = true;
			$panel['item_buttons']['send']['hidden'] = true;
			$panel['item_buttons']['send_dumby']['hidden'] = true;
			$panel['item_buttons']['duplicate']['hidden'] = true;
			$panel['item_buttons']['delete']['hidden'] = true;
			
			if ($refinerName == 'outbox') {
				$panel['title'] = adminPhrase('Newsletter Outbox');
				$panel['item']['css_class'] = 'zenario_newsletter_in_progress_newsletter';
		
			} elseif ($refinerName == 'archive') {
				$panel['title'] = adminPhrase('Newsletter Archive');
				$panel['item']['css_class'] = 'zenario_newsletter_sent_newsletter';
			}
		} else {
			foreach($panel['items'] as $id => &$item) {
			
				$item['recipients'] = zenario_newsletter::newsletterRecipients($id, 'count');
			} 
		}
		
		if (!($refinerName == 'archive' && setting('zenario_newsletter__enable_opened_emails'))) {
			unset($panel['columns']['opened']);
			unset($panel['columns']['opened_percentage']);
		}
		if ($refinerName != 'archive') {
			unset($panel['columns']['clicked']);
			unset($panel['columns']['clicked_percentage']);
		
		} else {
			$sql = '
				SELECT newsletter_id
				FROM ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_user_link
			';
		
			$result = sqlSelect($sql);
			$archiveExists = sqlFetchAssoc($result);
			
			if($archiveExists) {
				foreach($panel['items'] as $id => &$item) {
					//For calculating percentages
					$sql = '
						SELECT COUNT(DISTINCT user_id) as userCount
						FROM ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_user_link as nul
						WHERE nul.newsletter_id = '. (int) $id;
					
					$result = sqlSelect($sql);
					$smartGroupTotal = sqlFetchAssoc($result);
					
					if(setting('zenario_newsletter__enable_opened_emails')) {
						//For the "Opened" column
						$sql = '
							SELECT COUNT(time_received) as opened
							FROM ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_user_link
							WHERE time_received IS NOT NULL AND newsletter_id = ' . $item['id']
						;
						$result = sqlSelect($sql);
						$row = sqlFetchAssoc($result);
						$item['opened'] = $row['opened'];
						
						if ($item['opened'] && $smartGroupTotal['userCount']) {
							$percentage = $item['opened'] / $smartGroupTotal['userCount'] * 100;
						} else {
							$percentage = 0;
						}
						//For the "Opened %" column
						$item['opened_percentage'] = number_format($percentage, 1) . "%";
						
					}
			
					//For "Clicked" column
					$sql = '
						SELECT COUNT(time_received) as clicked
						FROM ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_user_link
						WHERE time_clicked_through IS NOT NULL AND newsletter_id = ' . $item['id'];
					
					$result = sqlSelect($sql);
					$row = sqlFetchAssoc($result);
					$item['clicked'] = $row['clicked'];
					
					if ($item['clicked'] && $smartGroupTotal['userCount']) {
						$percentage = $item['clicked'] / $smartGroupTotal['userCount'] * 100;
					} else {
						$percentage = 0;
					}
					
					//For "Clicked %" column
					$item['clicked_percentage'] = number_format($percentage, 1) . "%";
				}
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (post('delete') && checkPriv('_PRIV_EDIT_NEWSLETTER')) {
			
			foreach(explode(',', $ids) as $id) {
				zenario_newsletter::deleteNewsletter($id);
			}
		
		
		//Attempt to resume sending the newsletter
		} elseif (post('resume') && checkPriv('_PRIV_SEND_NEWSLETTER') && $this->checkIfNewsletterIsInProgress($ids)) {
			//Note: same code as above
			set_time_limit(60 * 10);
			self::sendNewsletter($ids);
			
			echo '<!--Message_Type:Success-->
				<p>', adminPhrase('Newsletter Sent.'), '</p>
				<p><a href="#zenario__email_template_manager/panels/newsletters/collection_buttons/archive//', (int) $ids, '//" onclick="zenarioA.closeFloatingBox();">', adminPhrase('View Sent Newsletter in Archive.'), '</a></p>';
		
		
		//Duplicate the newsletter
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
			
			sqlUpdate($sql, false, false);
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
		}
	}
	
}