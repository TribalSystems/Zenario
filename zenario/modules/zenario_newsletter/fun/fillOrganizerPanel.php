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
	case 'zenario__email_template_manager/panels/newsletter_click_throughs':
		
		$details = $this->loadDetails($refinerId);
		$panel['title'] = adminPhrase('Click throughs for Newsletter: "[[newsletter_name]]"', $details);
		
		break;

	case 'zenario__email_template_manager/panels/newsletters':
	
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
		
		break;
		
		
	case 'zenario__email_template_manager/panels/newsletter_log':
		
		$details = $this->loadDetails(get('refiner__newsletter'));
		$panel['title'] = adminPhrase('Recipients of the Newsletter "[[newsletter_name]]"', $details);
		
		foreach($panel['items'] as &$item) {
			$item['cell_css_classes'] = array();
			if (!$item['email_sent']) {
				//$item['cell_css_classes']['email_sent'] = 'zenario_newsletter_log__not_yet_sent';
				$item['email_sent'] = adminPhrase('Not Yet Sent');
			} elseif ($item['email_sent'] == 1) {
				//$item['cell_css_classes']['email_sent'] = 'zenario_newsletter_log__failed';
				$item['email_sent'] = adminPhrase('Failed Sending');
			} else {
				//$item['cell_css_classes']['email_sent'] = 'zenario_newsletter_log__sent';
				$item['email_sent'] = adminPhrase('Sent');
			}
		}
		
		break;
		

	case 'zenario__users/panels/users/groups':
		
		if ($refinerName == 'zenario_newsletter/panel__recipients') {
			
			$details = $this->loadDetails(get('refiner__zenario_newsletter__recipients'));
			$panel['title'] = adminPhrase('Recipient Groups for the Newsletter "[[newsletter_name]]"', $details);
			$panel['no_items_message'] = adminPhrase('No Recipient Groups have been set.');
			
			$unsets = array();
			foreach ($panel['collection_buttons'] as $name => $details) {
				if ($name != 'zenario_newsletter/panel__add_recipients') {
					$unsets[] = $name;
				}
			}
			foreach ($unsets as $name) {
				unset($panel['collection_buttons'][$name]);
			}
			
			$unsets = array();
			foreach ($panel['item_buttons'] as $name => $details) {
				if ($name != 'zenario_newsletter/panel__remove_recipients') {
					$unsets[] = $name;
				}
			}
			foreach ($unsets as $name) {
				unset($panel['item_buttons'][$name]);
			}
		}
		
		break;
	
	
	case 'zenario__content/panels/email_images_for_newsletters':
		
		//Borrow the logic from the image library panel to handle the images
		$c = $this->runSubClass('zenario_common_features', 'organizer', 'zenario__content/panels/image_library');
		$c->fillOrganizerPanel('generic_image_panel', $panel, $refinerName, $refinerId, $mode);
		
		break;
		
		
	case 'zenario__content/panels/image_library':
		foreach ($panel['items'] as $id => &$item) {
			$text = '';
			$comma = false;
			$usage_newsletters = (int)$item['usage_newsletters'];
			$usage_newsletter_templates = (int)$item['usage_newsletter_templates'];
			$newsletterUsage = $usage_newsletters + $usage_newsletter_templates;
			if ($newsletterUsage === 1) {
				if ($usage_newsletters === 1) {
					$sql = '
						SELECT 
							n.newsletter_name
						FROM ' . DB_NAME_PREFIX . 'inline_images ii
						INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletters n
							ON ii.foreign_key_id = n.id
							AND ii.foreign_key_to = "newsletter"
						WHERE image_id = ' . $item['id'] . '
						AND archived = 0';
					$result = sqlSelect($sql);
					$row = sqlFetchAssoc($result);
					$text .= adminPhrase('Used on "[[newsletter_name]]"', $row);
				} else {
					$sql = '
						SELECT 
							nt.name
						FROM ' . DB_NAME_PREFIX . 'inline_images ii
						INNER JOIN ' . DB_NAME_PREFIX . ZENARIO_NEWSLETTER_PREFIX . 'newsletter_templates nt
							ON ii.foreign_key_id = nt.id
							AND ii.foreign_key_to = "newsletter_template"
						WHERE image_id = ' . $item['id'] . '
						AND archived = 0';
					$result = sqlSelect($sql);
					$row = sqlFetchAssoc($result);
					$text .= adminPhrase('Used on "[[name]]"', $row);
				}
			} elseif ($newsletterUsage > 1) {
				$text .= 'Used on ';
				if ($usage_newsletters > 0) {
					$text .= nAdminPhrase(
						'[[count]] newsletter',
						'[[count]] newsletters',
						$usage_newsletters,
						array('count' => $usage_newsletters)
					);
					$comma = true;
				}
				if ($usage_newsletter_templates > 0) {
					if ($comma) {
						$text .= ', ';
					}
					$text .= nAdminPhrase(
						'[[count]] newsletter template',
						'[[count]] newsletter templates',
						$usage_newsletter_templates,
						array('count' => $usage_newsletter_templates)
					);
				}
			}
			$item['all_usage_newsletters'] = $text;
		}
		break;

}