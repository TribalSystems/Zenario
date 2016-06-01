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
		}
		break;
	
	case 'zenario__content/panels/email_images_for_newsletters':
		$key = array(
			'foreign_key_to' => 'newsletter',
			'foreign_key_id' => $refinerId);
		$privCheck = checkPriv('_PRIV_EDIT_NEWSLETTER');
		
		return require funIncPath(CMS_ROOT. moduleDir('zenario_common_features', 'fun'), 'media.handleOrganizerPanelAJAX');

}