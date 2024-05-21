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


		$sql = "
			SELECT v.id, v.type, v.version, c.status, c.lock_owner_id, v.last_author_id, v.creating_author_id, v.scheduled_publish_datetime
			FROM ". DB_PREFIX. "content_item_versions AS v
			INNER JOIN ". DB_PREFIX. "content_items AS c
			   ON c.id = v.id
			  AND c.type = v.type
			WHERE v.scheduled_publish_datetime <= STR_TO_DATE('". ze\escape::sql($serverTime). "', '%Y-%m-%d %H:%i:%s')";
		$result = ze\sql::select($sql);
		
		$action = false;
		$emailTemplateManagerModuleRunning = ze\module::inc('zenario_email_template_manager');
		$contentItemPublishedEmailText = self::getEmailTextContentItemPublished();
		$addressFrom = ze::setting('email_address_from');
		$nameFrom = ze::setting('email_name_from');
		$subject = ze\admin::phrase('Content item published');
		
		while ($citem = ze\sql::fetchAssoc($result)) {
			
			if ($citem['status'] == 'hidden' || ze\content::isDraft($citem['status'])) {
				// Publish marked draft items
				$adminId = $citem['lock_owner_id'];
				ze\contentAdm::publishContent($citem['id'], $citem['type'], $adminId);
				$action = true;
				$tag = ze\content::formatTag($citem['id'], $citem['type']);
				$contentItemTitle = ze\content::title($citem['id'], $citem['type'], $citem['version']);
				echo ze\admin::phrase('Published content item [[tag]]', ['tag' => $tag]), "\n";
				
				//Send emails to concerned admins:
				//Admin who requested the scheduled publish...
				$lockingAdminDetails = ze\admin::details($citem['lock_owner_id']);
				
				//... and the last editor, or the author if the content item has never been edited.
				if ($citem['last_author_id'] != 0) {
					$lastEditAdminId = $citem['last_author_id'];
				} else {
					$lastEditAdminId = $citem['creating_author_id'];
				}
				
				$lastEditingAdminDetails = ze\admin::details($lastEditAdminId);
				
				if ($emailTemplateManagerModuleRunning) {
					$text = $contentItemPublishedEmailText;
					zenario_email_template_manager::putBodyInTemplate($text);
					
					$mergeFields = [
						'admin_first_name' => $lockingAdminDetails['first_name'],
						'admin_last_name' => $lockingAdminDetails['last_name'],
						'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $citem['type']]),
						'content_item_title' => $contentItemTitle,
						'date_and_time' => ze\date::formatDateTime($citem['scheduled_publish_datetime']),
						'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
						'content_item' => $tag,
						'content_item_url' => ze\link::toItem($citem['id'], $citem['type'])
					];
					
					zenario_email_template_manager::sendEmails($lockingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
					echo ze\admin::phrase(
						'Sent notification to "' . htmlspecialchars($lockingAdminDetails['email']) . '" admin for content item [[tag]]',
						['tag' => $tag]
					), "\n";
				}
				
				if ($lastEditingAdminDetails['email'] != $lockingAdminDetails['email']) {
					$text = $contentItemPublishedEmailText;
					zenario_email_template_manager::putBodyInTemplate($text);
					
					$mergeFields = [
						'admin_first_name' => $lastEditingAdminDetails['first_name'],
						'admin_last_name' => $lastEditingAdminDetails['last_name'],
						'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $citem['type']]),
						'content_item_title' => $contentItemTitle,
						'date_and_time' => ze\date::formatDateTime($citem['scheduled_publish_datetime']),
						'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
						'content_item' => $tag,
						'content_item_url' => ze\link::toItem($citem['id'], $citem['type'])
					];
					
					zenario_email_template_manager::sendEmails($lastEditingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
					echo ze\admin::phrase(
						'Sent notification to "' . htmlspecialchars($lastEditingAdminDetails['email']) . '" admin for content item [[tag]]',
						['tag' => $tag]
					), "\n";
				}
			}
			
			//Update scheduled time
			ze\row::update('content_item_versions',
				['scheduled_publish_datetime' => null],
				['id' => $citem['id'], 'type' => $citem['type'], 'version' => $citem['version']]
			);
		}
		
		if (!$action) {
			echo ze\admin::phrase('No content items to publish'), "\n";
		}
		
		return $action;