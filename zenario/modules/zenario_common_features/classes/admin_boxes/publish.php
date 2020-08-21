<?php
/*
 * Copyright (c) 2020, Tribal Limited
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
			$count = 1;
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			$count = count($tags);
			if ($count == 1) {
				ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $tags[0]);
			}
		}
		
		if ($count == 1) {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				ze\admin::phrase('Are you sure you wish to publish the content item "[[tag]]"?', ['tag' => ze\content::formatTag($box['key']['cID'], $box['key']['cType'])]);
		} else {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				ze\admin::phrase('Are you sure you wish to publish the [[count]] selected content items?', ['count' => $count]);
		}
		
		$clash = static::checkForClashingPublicationDates($box['key']['id']);
		
		// Scheduled publishing options
		if (ze\module::inc('zenario_scheduled_task_manager')) {
			$allJobsEnabled = ze::setting('jobs_enabled');
			$scheduledPublishingEnabled = ze\row::get('jobs', 'enabled', ['job_name' => 'jobPublishContent', 'module_class_name' => 'zenario_common_features']);
			if (!($allJobsEnabled && $scheduledPublishingEnabled)) {
				$scheduledTaskLink = ze\link::absolute() . 
					'zenario/admin/organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks';
				$fields['publish/publish_options']['values']['schedule']['disabled'] = true;
				$fields['publish/publish_options']['values']['schedule']['post_field_html'] = "
					<br />
					<br />
					Scheduled publishing is not available. The Scheduled Task Manager is installed 
					but the Scheduled Publishing task is not enabled. 
					<a href='".$scheduledTaskLink."'>Click for more info.</a>";
			} else {
				$values['publish/publish_date'] = date('Y-m-d');
			}
			
			if ($clash) {
				$values['publish/publish_options'] = 'schedule';
				$fields['publish/publish_options']['values']['immediately']['disabled'] = true;
				$fields['publish/publish_options']['values']['immediately']['side_note'] = ze\admin::phrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
				
				$values['publish/publish_date'] = $clash['release_date'];
				$values['publish/publish_hours'] = 7;
			}
		
		} else {
			$fields['publish/publish_options']['hidden'] = true;
			
			if ($clash) {
				echo ze\admin::phrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
				exit;
			}
		}
		// Scheduled publishing datetime option enabled
		$pIdArr = [];
		foreach($tags as $tagId){
			$pId = explode('_',$tagId);
			$pIdArr = $pId[1];
		}
		$checkIfPublishsql = "SELECT id,scheduled_publish_datetime from ". DB_PREFIX. "content_item_versions 
					WHERE id IN (". ze\escape::in($pIdArr, 'checkIfPublishsql'). ")
			  AND scheduled_publish_datetime IS NOT NULL
			  AND published_datetime IS NULL AND publisher_id=0" ;
		$checkIfPublish = ze\sql::select($checkIfPublishsql);
		$getresult = ze\sql::fetchAssoc($checkIfPublish);
	
		if($getresult && $checkIfPublish)
		{
			if(sizeof($getresult)>1 )
			{
				$values['publish/publish_options'] = 'schedule';
			
					$sdate = $getresult['scheduled_publish_datetime'];
					$sdate = strtotime($sdate);
					$values['publish/publish_hours'] = date('G', $sdate);
					$values['publish/publish_mins'] = date('i', $sdate);
					$values['publish/publish_date'] = date('Y-m-d',$sdate);
				
			}
			
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['publish/publish_date']['hidden'] = 
		$fields['publish/publish_hours']['hidden'] = 
		$fields['publish/publish_mins']['hidden'] = 
			(!($values['publish/publish_options'] == 'schedule')
			|| $fields['publish/publish_options']['hidden']);
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
			WHERE c.tag_id IN (". ze\escape::in($tagIds, 'sql'). ")
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
			$clash['date'] = ze\admin::formatDate($clash['release_date'], 'vis_date_format_short');
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
				} else {
					
					if ($clash = static::checkForClashingPublicationDates($box['key']['id'], $values['publish/publish_date'])) {
						$box['tabs']['publish']['errors']['before'] =
							ze\admin::phrase('You cannot schedule the publishing of a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
					}
				}
			}
			
		} else {
			if ($clash = static::checkForClashingPublicationDates($box['key']['id'])) {
				$box['tabs']['publish']['errors']['before'] =
					ze\admin::phrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = (($box['key']['id']) ? $box['key']['id'] : $box['key']['cID']);
		foreach (ze\ray::explodeAndTrim($ids) as $id) {
			$cID = $cType = false;
			if (!empty($box['key']['cID']) && !empty($box['key']['cType'])) {
				$cID = $box['key']['cID'];
				$cType = $box['key']['cType'];
			} else {
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id);
			}
			
			if ($cID && $cType && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
				if ($values['publish/publish_options'] == 'immediately') {
					// Publish now
					ze\contentAdm::publishContent($cID, $cType);
					if (ze::session('last_item') == $cType. '_'. $cID) {
						$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
					}
				} else {
					// Publish at a later date
					$scheduled_publish_datetime = $values['publish/publish_date'].' '.$values['publish/publish_hours'].':'.$values['publish/publish_mins'].':00';
					$cVersion = ze\row::get('content_items', 'admin_version', ['id' => $cID, 'type' => $cType]);
					ze\row::update('content_item_versions', ['scheduled_publish_datetime'=>$scheduled_publish_datetime], ['id' =>$cID, 'type'=>$cType, 'version'=>$cVersion]);
					
					// Lock content item
					$adminId = $_SESSION['admin_userid'] ?? false;
					ze\row::update('content_items', ['lock_owner_id'=>$adminId, 'locked_datetime'=>date('Y-m-d H:i:s')], ['id' =>$cID, 'type'=>$cType]);
				}
			}
		}
		
	}
}
