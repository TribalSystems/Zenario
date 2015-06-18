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
	case 'zenario_newsletter_template':
		$record = array(
			'name' => $values['details/name'],
			'body' => $values['details/body']);
		
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
			$id = $box['key']['id'];
			$record = array(
					'newsletter_name' => $values['meta_data/newsletter_name'],
					'subject' => $values['meta_data/subject'],
					'email_name_from' => $values['meta_data/email_name_from'],
					'email_address_from' => $values['meta_data/email_address_from'],
					'body' =>  $values['meta_data/body'], 
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
							=> $values['add_unsubscribe_link']? $values['unsubscribe_text']: null,
					'delete_account_text' 
							=> $values['add_delete_account_link']? $values['delete_account_text']: null
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
}