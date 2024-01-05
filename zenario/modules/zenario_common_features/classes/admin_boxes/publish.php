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


class zenario_common_features__admin_boxes__publish extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$tags = ze\ray::explodeAndTrim($box['key']['id']);
		
		if ($box['key']['cID']) {
			$tagsCount = 1;
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			$tagsCount = count($tags);
			if ($tagsCount == 1) {
				ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $tags[0]);
			}
		}
		
		if ($tagsCount == 1) {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				ze\admin::phrase('Are you sure you wish to publish the content item "[[tag]]"?', ['tag' => ze\content::formatTag($box['key']['cID'], $box['key']['cType'])]);
		} else {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				ze\admin::phrase('Are you sure you wish to publish the [[count]] selected content items?', ['count' => $tagsCount]);
		}
		
		//Look for any access codes in use
		ze\contentAdm::checkForAccessCodes($box, $fields['publish/access_codes_warning'], $tags, $tagsCount,
			'This content item has a staging code ([[access_code]]). This will be removed when it is published.',
			'One content item has a staging code ([[access_code]]). This will be removed when it is published.',
			'[[count]] content items have a staging code. These will be removed when it is published.'
		);
		
		$clash = static::checkForClashingPublicationDates($box['key']['id']);
		
		// Scheduled publishing options
		$scheduledTaskManagerInc = ze\module::inc('zenario_scheduled_task_manager');
		if ($scheduledTaskManagerInc) {
			$allJobsEnabled = ze::setting('jobs_enabled');
			$scheduledPublishingEnabled = ze\row::get('jobs', 'enabled', ['job_name' => 'jobPublishContent', 'module_class_name' => 'zenario_common_features']);
			if (!($allJobsEnabled && $scheduledPublishingEnabled)) {
				$scheduledTaskHref = ze\link::absolute() . 'organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				$linkStart = '<a href="' . htmlspecialchars($scheduledTaskHref) . '" target="_blank">';
				$linkEnd = "</a>";

				$string = "Scheduled publishing is not available. The Scheduled Task Manager is installed but the scheduled publishing task (jobPublishContent) is not enabled. [[link_start]]Click for more info.[[link_end]]";

				$fields['publish/publish_options']['values']['schedule']['disabled'] = true;
				$fields['publish/publish_options']['values']['schedule']['note_below'] = ze\admin::phrase($string, ['link_start' => $linkStart, 'link_end' => $linkEnd]);
			} else {
				$values['publish/publish_date'] = date('Y-m-d');
			}
			
			if ($clash) {
				$href = ze\link::absolute() . 'organizer.php#zenario__content/panels/content/refiners/content_type//' . $box['key']['cType'] . '//' . $box['key']['id'] . '~.zenario_content~tmeta_data~k{"id"%3A"' . $box['key']['id'] . '"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="blank">';
				$linkEnd = '</a>';
				ze\lang::applyMergeFields($fields['publish/publishing_before_release_date_warning']['snippet']['html'],
					[
						'publishing_before_release_date_warning_note' => ze\admin::phrase(
							'This content item has a release date of [[date]], which is in the future. If that is not correct, [[link_start]]edit its meta data[[link_end]] to change the release date.',
							[
								'link_start' => $linkStart,
								'date' => $clash['date'],
								'link_end' => $linkEnd
							]
						)
					]
				);
			} else {
				$fields['publish/publishing_before_release_date_warning']['hidden'] = true;
			}
		
		} else {
			$fields['publish/publish_options']['hidden'] = true;
			$fields['publish/publishing_before_release_date_warning']['hidden'] = true;
			
			if ($clash) {
				echo ze\admin::phrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
				exit;
			}
		}
		
		//Show a note if any of these items are scheduled to be published
		$sql = "
			SELECT c.id, c.type, v.scheduled_publish_datetime, c.lock_owner_id
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = c.admin_version
			WHERE c.tag_id in (". ze\escape::in($tags, 'asciiInSQL'). ")
			  AND v.scheduled_publish_datetime IS NOT NULL";
		
		$result = ze\sql::select($sql);
		$count = ze\sql::numRows($result);
		$row = ze\sql::fetchAssoc($result);
		
		if ($count > 0) {
			$box['tabs']['publish']['notices']['scheduled_warning']['show'] = true;
			$box['save_button_message'] = ze\admin::phrase('Submit');
			
			if ($count > 1
			 || count($tags) > 1) {
				$box['tabs']['publish']['notices']['scheduled_warning']['message'] =
					ze\admin::phrase('These items are scheduled to be published on various times. Please check each one for details.');
			
			} else {
				$row['publication_time'] = 
					ze\admin::formatDateTime($row['scheduled_publish_datetime'], 'vis_date_format_med');
				
				if ($row['lock_owner_id']) {
					$adminDetails = ze\admin::details($row['lock_owner_id']);
					$row['first_name'] = $adminDetails['first_name'];
					$row['last_name'] = $adminDetails['last_name'];

					$scheduledWarningPhrase = "This item is scheduled by [[first_name]] [[last_name]] to be published on [[publication_time]].";
				} else {
					$scheduledWarningPhrase = "This item is scheduled to be published on [[publication_time]].";
				}

				$box['tabs']['publish']['notices']['scheduled_warning']['message'] = ze\admin::phrase($scheduledWarningPhrase, $row);
				
				$values['publish/publish_options'] = 'schedule';
				
				$sdate = ze\date::new($row['scheduled_publish_datetime']);
				$values['publish/publish_hours'] = $sdate->format('G');
				$values['publish/publish_mins'] = $sdate->format('i');
				$values['publish/publish_date'] = $sdate->format('Y-m-d');
			}
		} else {
			unset($fields['publish/publish_options']['values']['cancel']);
			
			if ($scheduledTaskManagerInc && $allJobsEnabled && $scheduledPublishingEnabled) {
				//Set the default date to be +1 hr in the future. Round up to the nearest 5 mins.
				//This will push the date +1 day forward if needed.
				$sdate = ze\date::new(ze\date::now());
				$sdate->modify('+1 hour');
				$sdate->setTime($sdate->format('H'), ceil($sdate->format('i') / 5) * 5, 0);
				
				$values['publish/publish_hours'] = $sdate->format('G');
				$values['publish/publish_mins'] = $sdate->format('i');
				$values['publish/publish_date'] = $sdate->format('Y-m-d');
			}
		}
		
		//Settings for IndexNow
// 		if (ze::setting('enable_indexnow') && ze::setting('indexnow_api_key')) {
// 			if ($tagsCount == 1) {
// 				unset($box['tabs']['publish']['fields']['publish_multiple_indexnow_snippet']);
// 				$values['publish/notify_indexnow'] = true;
// 			} elseif ($tagsCount > 1) {
// 				unset($box['tabs']['publish']['fields']['notify_indexnow']);
// 			}
// 		} else {
// 			unset($box['tabs']['publish']['fields']['notify_indexnow'], $box['tabs']['publish']['fields']['publish_multiple_indexnow_snippet']);
// 		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['publish/publish_date']['hidden'] = 
		$fields['publish/publish_hours']['hidden'] = 
		$fields['publish/publish_mins']['hidden'] = 
			(!($values['publish/publish_options'] == 'schedule')
			|| $fields['publish/publish_options']['hidden']);
		
// 		if (ze::setting('enable_indexnow') && ze::setting('indexnow_api_key') && ze\module::inc('zenario_user_timers')) {
// 			if (isset($fields['publish/notify_indexnow'])) {
// 				if ($values['publish/publish_options'] != 'immediately') {
// 					$fields['publish/notify_indexnow']['hidden'] = true;
// 				} else {
// 					$fields['publish/notify_indexnow']['hidden'] = false;
// 				}
// 			}
// 		}
	}
	
	protected static function checkForClashingPublicationDates($tagIds, $date = false) {
		$sql = "
			SELECT DATE(v.release_date) AS release_date, c.id, c.type
			FROM ". DB_PREFIX. "content_items AS c
			INNER JOIN ". DB_PREFIX. "content_types AS ct
			   ON ct.content_type_id = c.type
			  AND ct.release_date_field != 'hidden'
			INNER JOIN ". DB_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = c.admin_version
			WHERE c.tag_id IN (". ze\escape::in($tagIds, 'asciiInSQL'). ")
			  AND v.release_date IS NOT NULL
			  AND DATE(v.release_date) > ";
		
		if ($date) {
			$sql .= "DATE('". ze\escape::sql($date). "')";
		} else {
			$sql .= "DATE(NOW())";
		}
		
		$sql .= "
			ORDER BY release_date DESC
			LIMIT 1";
		
		if (($result = ze\sql::select($sql)) && ($clash = ze\sql::fetchAssoc($result))) {
			
			$clash['tag'] = ze\content::formatTag($clash['id'], $clash['type']);
			$clash['date'] = ze\admin::formatDate($clash['release_date'], 'vis_date_format_med');
			return $clash;
		
		} else {
			return false;
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		// Make sure chosen time is not in the past
		if ($values['publish/publish_options'] == 'schedule') {
			if (empty($values['publish/publish_date'])) {
				$box['tabs']['publish']['errors'][] = ze\admin::phrase('Please enter a date.');
			
			} else {
				$now = strtotime('now');
				$scheduledDate = strtotime($values['publish/publish_date'].' '. $values['publish/publish_hours'].':'.$values['publish/publish_mins']);
				if ($scheduledDate < $now) {
					$box['tabs']['publish']['errors'][] = ze\admin::phrase('The scheduled publishing time cannot be in the past.');
				}
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = (($box['key']['id']) ? $box['key']['id'] : $box['key']['cID']);
		
		$ids = ze\ray::explodeAndTrim($ids);
		$tagsCount = count($ids);
		
		//Settings for IndexNow
// 		$notifyIndexNow = false;
// 		$indexNowApiKey = '';
// 		if (ze::setting('enable_indexnow') && ($indexNowApiKey = ze::setting('indexnow_api_key'))) {
// 			if ($tagsCount == 1 && isset($fields['publish/notify_indexnow']) && $values['publish/notify_indexnow']) {
// 				if (!(ze\module::inc('zenario_user_timers') && $values['publish/publish_options'] != 'immediately')) {
// 					$notifyIndexNow = true;
// 				}
// 			}
// 		}
		
		foreach ($ids as $id) {
			$cID = $cType = false;
			if (!empty($box['key']['cID']) && !empty($box['key']['cType'])) {
				$cID = $box['key']['cID'];
				$cType = $box['key']['cType'];
			} else {
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id);
			}
			
			if ($cID && $cType && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
				$adminId = $_SESSION['admin_userid'] ?? false;
				if ($values['publish/publish_options'] == 'immediately') {
					// Publish now
					if ($box['tabs']['publish']['notices']['scheduled_warning']['show']) {
						//If this content item was scheduled for publishing, but now an admin immediately publishes it,
						//remove all the scheduled information.
						$cVersion = ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
						ze\row::update('content_item_versions', ['scheduled_publish_datetime' => NULL], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);

						//Unlock content item
						ze\row::update('content_items', ['lock_owner_id' => 0, 'locked_datetime' => NULL], ['id' => $cID, 'type' => $cType]);
					}
					ze\contentAdm::publishContent($cID, $cType);
					if (ze\ring::chopPrefix($cType. '_'. $cID. '.', ze::session('last_item'))) {
						unset($_SESSION['last_item'], $_SESSION['page_mode'], $_SESSION['page_toolbar']);
					}
					
					//If a content item was previously scheduled, but an admin has published it immediately,
					//send email notifications.
					if ($box['tabs']['publish']['notices']['scheduled_warning']['show']) {
						$emailTemplateManagerModuleRunning = ze\module::inc('zenario_email_template_manager');
						if ($emailTemplateManagerModuleRunning) {
							$addressFrom = ze::setting('email_address_from');
							$nameFrom = ze::setting('email_name_from');
							$subject = ze\admin::phrase('Content item publishing schedule cancelled, published immediately');
							$text = self::getEmailTextContentItemScheduleCancelledButItemPublishedImmediately();
							zenario_email_template_manager::putBodyInTemplate($text);
						
							$tag = ze\content::formatTag($cID, $cType);
							$cVersion = ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
							$contentItemTitle = ze\content::title($cID, $cType, $cVersion);
							$currentStatus = ze\admin::phrase('Scheduled publishing cancelled, item published immediately');
						
							$lockingAdminDetails = ze\admin::details($adminId);
						
							$mergeFields = [
								'admin_first_name' => $lockingAdminDetails['first_name'],
								'admin_last_name' => $lockingAdminDetails['last_name'],
								'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
								'content_item_title' => $contentItemTitle,
								'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
								'content_item' => $tag,
								'content_item_url' => ze\link::toItem($cID, $cType),
								'current_status' => $currentStatus
							];
					
							zenario_email_template_manager::sendEmails($lockingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
						
							$citemLastAuthorAndCreatingAuthor = ze\row::get('content_item_versions', ['last_author_id', 'creating_author_id'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
							if ($citemLastAuthorAndCreatingAuthor['last_author_id'] != 0) {
								$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['last_author_id'];
							} else {
								$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['creating_author_id'];
							}
				
							$lastEditingAdminDetails = ze\admin::details($lastEditAdminId);
						
							if ($lastEditingAdminDetails['email'] != $lockingAdminDetails['email']) {
								$mergeFields = [
									'admin_first_name' => $lastEditingAdminDetails['first_name'],
									'admin_last_name' => $lastEditingAdminDetails['last_name'],
									'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
									'content_item_title' => $contentItemTitle,
									'date_and_time' => ze\date::formatDateTime($scheduled_publish_datetime),
									'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
									'content_item' => $tag,
									'content_item_url' => ze\link::toItem($cID, $cType),
									'current_status' => $currentStatus
								];
					
								zenario_email_template_manager::sendEmails($lastEditingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
							}
						}
					}
					
// 					if ($notifyIndexNow && $indexNowApiKey) {
// 						$urlChanged = ze\link::toItem($cID, $cType, $fullPath = true);
// 						$indexNowUrl = 'https://www.bing.com/indexnow?url=' . urlencode($urlChanged) . '&key=' . urlencode($indexNowApiKey);
// 						var_dump($indexNowUrl);
// 						
// 						$keyLocation = CMS_ROOT . 'zenario_custom/' . $indexNowApiKey . '.txt';
// 						$indexNowUrl = 'https://api.indexnow.org/indexnow?url=' . urlencode($urlChanged) . '&key=' . urlencode($indexNowApiKey) . '&keyLocation=' . $keyLocation;
// 						
// 						$indexNowCallResult = ze\curl::fetch($indexNowUrl, $post = true);
// 					}
				} elseif ($values['publish/publish_options'] == 'schedule') {
					// Publish on a later date
					$scheduled_publish_datetime = $values['publish/publish_date'].' '.$values['publish/publish_hours'].':'.$values['publish/publish_mins'].':00';
					$cVersion = ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
					ze\row::update('content_item_versions', ['scheduled_publish_datetime' => $scheduled_publish_datetime], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
					
					// Lock content item
					ze\row::update('content_items', ['lock_owner_id' => $adminId, 'locked_datetime'=>date('Y-m-d H:i:s')], ['id' => $cID, 'type' => $cType]);
					
					//Inform the admins: the one who requested the scheduling, and the last editor (or creator if never edited).
					$emailTemplateManagerModuleRunning = ze\module::inc('zenario_email_template_manager');
					if ($emailTemplateManagerModuleRunning) {
						$addressFrom = ze::setting('email_address_from');
						$nameFrom = ze::setting('email_name_from');
						$subject = ze\admin::phrase('Content item scheduled for publishing');
						$text = self::getEmailTextContentItemScheduledForPublishing();
						zenario_email_template_manager::putBodyInTemplate($text);
						
						$tag = ze\content::formatTag($cID, $cType);
						$contentItemTitle = ze\content::title($cID, $cType, $cVersion);
						
						$lockingAdminDetails = ze\admin::details($adminId);
						
						$mergeFields = [
							'admin_first_name' => $lockingAdminDetails['first_name'],
							'admin_last_name' => $lockingAdminDetails['last_name'],
							'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
							'content_item_title' => $contentItemTitle,
							'date_and_time' => ze\date::formatDateTime($scheduled_publish_datetime),
							'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
							'content_item' => $tag,
							'content_item_url' => ze\link::toItem($cID, $cType)
						];
					
						zenario_email_template_manager::sendEmails($lockingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
						
						$citemLastAuthorAndCreatingAuthor = ze\row::get('content_item_versions', ['last_author_id', 'creating_author_id'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
						if ($citemLastAuthorAndCreatingAuthor['last_author_id'] != 0) {
							$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['last_author_id'];
						} else {
							$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['creating_author_id'];
						}
				
						$lastEditingAdminDetails = ze\admin::details($lastEditAdminId);
						
						if ($lastEditingAdminDetails['email'] != $lockingAdminDetails['email']) {
							$mergeFields = [
								'admin_first_name' => $lastEditingAdminDetails['first_name'],
								'admin_last_name' => $lastEditingAdminDetails['last_name'],
								'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
								'content_item_title' => $contentItemTitle,
								'date_and_time' => ze\date::formatDateTime($scheduled_publish_datetime),
								'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
								'content_item' => $tag,
								'content_item_url' => ze\link::toItem($cID, $cType)
							];
					
							zenario_email_template_manager::sendEmails($lastEditingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
						}
					}
				} elseif ($values['publish/publish_options'] == 'cancel') {
					//Cancel publishing
					$cVersion = ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
					ze\row::update('content_item_versions', ['scheduled_publish_datetime' => NULL], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);

					// Unlock content item
					ze\row::update('content_items', ['lock_owner_id' => 0, 'locked_datetime' => NULL], ['id' => $cID, 'type' => $cType]);
					
					//Inform the admins: the one who requested the scheduling, and the last editor (or creator if never edited).
					$emailTemplateManagerModuleRunning = ze\module::inc('zenario_email_template_manager');
					if ($emailTemplateManagerModuleRunning) {
						$addressFrom = ze::setting('email_address_from');
						$nameFrom = ze::setting('email_name_from');
						$subject = ze\admin::phrase('Content item publishing schedule cancelled');
						$text = self::getEmailTextContentItemScheduleCancelled();
						zenario_email_template_manager::putBodyInTemplate($text);
						
						$tag = ze\content::formatTag($cID, $cType);
						$contentItemTitle = ze\content::title($cID, $cType, $cVersion);
						$currentStatus = ze\admin::phrase('Scheduled publishing cancelled');
						
						$lockingAdminDetails = ze\admin::details($adminId);
						
						$mergeFields = [
							'admin_first_name' => $lockingAdminDetails['first_name'],
							'admin_last_name' => $lockingAdminDetails['last_name'],
							'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
							'content_item_title' => $contentItemTitle,
							'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
							'content_item' => $tag,
							'content_item_url' => ze\link::toItem($cID, $cType),
							'current_status' => $currentStatus
						];
					
						zenario_email_template_manager::sendEmails($lockingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
						
						$citemLastAuthorAndCreatingAuthor = ze\row::get('content_item_versions', ['last_author_id', 'creating_author_id'], ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
						if ($citemLastAuthorAndCreatingAuthor['last_author_id'] != 0) {
							$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['last_author_id'];
						} else {
							$lastEditAdminId = $citemLastAuthorAndCreatingAuthor['creating_author_id'];
						}
				
						$lastEditingAdminDetails = ze\admin::details($lastEditAdminId);
						
						if ($lastEditingAdminDetails['email'] != $lockingAdminDetails['email']) {
							$mergeFields = [
								'admin_first_name' => $lastEditingAdminDetails['first_name'],
								'admin_last_name' => $lastEditingAdminDetails['last_name'],
								'content_type' => ze\row::get('content_types', 'content_type_name_en', ['content_type_id' => $cType]),
								'content_item_title' => $contentItemTitle,
								'requesting_admin' => ze\admin::formatName($lockingAdminDetails),
								'content_item' => $tag,
								'content_item_url' => ze\link::toItem($cID, $cType),
								'current_status' => $currentStatus
							];
					
							zenario_email_template_manager::sendEmails($lastEditingAdminDetails['email'], $subject, $addressFrom, $nameFrom, $text, $mergeFields);
						}
					}
				}
			}
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = (($box['key']['id']) ? $box['key']['id'] : $box['key']['cID']);
		$tags = ze\ray::explodeAndTrim($ids);

		//If it looks like this was opened from the front-end
		//(i.e. there's no sign of any of Organizer's variables)
		//then try to redirect the admin to whatever the visitor URL should be
		if (!isset($_GET['refinerName']) && count($tags) == 1) {
			$link = ze\link::toItem(
				$box['key']['cID'], $box['key']['cType'],
				$fullPath = true, '', false,
				false, $forceAliasInAdminMode = true
			);
			
			ze\tuix::closeWithFlags(['go_to_url' => $link]);
			exit;
		}
	}
	
	public static function getEmailTextContentItemScheduledForPublishing() {
		$emailText = "
Dear [[admin_first_name]] [[admin_last_name]],

You are receiving this email because a content item ([[content_type]], \"[[content_item_title]]\") has been scheduled for publishing.

Content item: [[content_item]]
Date and time for publishing: [[date_and_time]]
Requesting admin: [[requesting_admin]]

<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[content_item_url]]\">View content item</a></p>";
		
		return nl2br($emailText);
	}
	
	public static function getEmailTextContentItemScheduleCancelled() {
		$emailText = "
Dear [[admin_first_name]] [[admin_last_name]],

You are receiving this email because a content item ([[content_type]], \"[[content_item_title]]\") is no longer scheduled for publishing.

Content item: [[content_item]]
Requesting admin: [[requesting_admin]]
Current status: [[current_status]]

<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[content_item_url]]\">View content item</a></p>";
		
		return nl2br($emailText);
	}
	
	public static function getEmailTextContentItemScheduleCancelledButItemPublishedImmediately() {
		$emailText = "
Dear [[admin_first_name]] [[admin_last_name]],

You are receiving this email because a content item ([[content_type]], \"[[content_item_title]]\") is no longer scheduled for publishing.

Content item: [[content_item]]
Requesting admin: [[requesting_admin]]
Current status: [[current_status]]

<p style=\"text-align: center;\"><a style=\"background: #015ca1; color: white; text-decoration: none; padding: 20px 40px; font-size: 16px;\" href=\"[[content_item_url]]\">View content item</a></p>";
		
		return nl2br($emailText);
	}
}
