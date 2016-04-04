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


class zenario_common_features__admin_boxes__publish extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if ($box['key']['cID']) {
			$count = 1;
			$box['key']['id'] = $box['key']['cType']. '_'. $box['key']['cID'];
		} else {
			$tags = explodeAndTrim($box['key']['id']);
			$count = count($tags);
			
			if ($count == 1) {
				getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $tags[0]);
			}
		}
		
		if ($count == 1) {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				adminPhrase('Are you sure you wish to publish the content item "[[tag]]"?', array('tag' => formatTag($box['key']['cID'], $box['key']['cType'])));
		} else {
			$box['tabs']['publish']['notices']['are_you_sure']['message'] = 
				adminPhrase('Are you sure you wish to publish the [[count]] selected content items?', array('count' => $count));
		}
		
		$clash = static::checkForClashingPublicationDates($box['key']['id']);
		
		if (inc('zenario_scheduled_task_manager')) {
			$allJobsEnabled = setting('jobs_enabled');
			$scheduledPublishingEnabled = getRow('jobs', 'enabled', array('job_name' => 'jobPublishContent', 'module_class_name' => 'zenario_common_features'));
			if (!($allJobsEnabled && $scheduledPublishingEnabled)) {
				$scheduledTaskLink = absCMSDirURL() . 
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
				$fields['publish/publish_options']['values']['immediately']['side_note'] = adminPhrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
				
				$values['publish/publish_date'] = $clash['publication_date'];
				$values['publish/publish_hours'] = 7;
			}
		
		} else {
			$fields['publish/publish_options']['hidden'] = true;
			
			if ($clash) {
				echo adminPhrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
				exit;
			}
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$fields['publish/publish_date']['hidden'] = 
		$fields['publish/publish_hours']['hidden'] = 
		$fields['publish/publish_mins']['hidden'] = 
			(!($values['publish/publish_options'] == 'schedule')
			|| $fields['publish/publish_options']['hidden']);
		$box['max_height'] = (($values['publish/publish_options'] == 'schedule') ? 250 : 150);
	}
	
	protected static function checkForClashingPublicationDates($tagIds, $date = false) {
		$sql = "
			SELECT DATE(v.publication_date) AS publication_date, c.id, c.type
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
			   ON v.id = c.id
			  AND v.type = c.type
			  AND v.version = c.admin_version
			WHERE c.tag_id IN (". inEscape($tagIds, 'sql'). ")
			  AND v.publication_date IS NOT NULL
			  AND v.publication_date > ";
		
		if ($date) {
			$sql .= "'". sqlEscape($date). "'";
		} else {
			$sql .= "DATE(NOW())";
		}
		
		$sql .= "
			ORDER BY publication_date DESC
			LIMIT 1";
		
		if (($result = sqlQuery($sql)) && ($clash = sqlFetchAssoc($result))) {
			
			$clash['tag'] = formatTag($clash['id'], $clash['type']);
			$clash['date'] = formatDateNicely($clash['publication_date'], 'vis_date_format_short');
			return $clash;
		
		} else {
			return false;
		}
	}
		


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		// Make sure chosen time is not in the past
		if ($values['publish/publish_options'] == 'schedule') {
			if (empty($values['publish/publish_date'])) {
				$box['tabs']['publish']['errors'][] = adminPhrase('Please enter a date.');
			
			} else {
				$now = strtotime('now');
				$scheduledDate = strtotime($values['publish/publish_date'].' '. $values['publish/publish_hours'].':'.$values['publish/publish_mins']);
				if ($scheduledDate < $now) {
					$box['tabs']['publish']['errors'][] = adminPhrase('The scheduled publishing time cannot be in the past.');
				} else {
					
					if ($clash = static::checkForClashingPublicationDates($box['key']['id'], $values['publish/publish_date'])) {
						$box['tabs']['publish']['errors']['before'] =
							adminPhrase('You cannot schedule the publishing of a content item before its release date. "[[tag]]" has a release date of [[date]].', $clash);
					}
				}
			}
			
		} else {
			if ($clash = static::checkForClashingPublicationDates($box['key']['id'])) {
				$box['tabs']['publish']['errors']['before'] =
					adminPhrase('You cannot publish a content item before its release date. "[[tag]]" has a release date of [[date]].');
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$ids = (($box['key']['id']) ? $box['key']['id'] : $box['key']['cID']);
		
		foreach (explodeAndTrim($ids) as $id) {
			$cID = $cType = false;
			if (!empty($box['key']['cID']) && !empty($box['key']['cType'])) {
				$cID = $box['key']['cID'];
				$cType = $box['key']['cType'];
			} else {
				getCIDAndCTypeFromTagId($cID, $cType, $id);
			}
			
			if ($cID && $cType && checkPriv('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
				if ($values['publish/publish_options'] == 'immediately') {
					// Publish now
					publishContent($cID, $cType);
					if (session('last_item') == $cType. '_'. $cID) {
						$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'preview';
					}
				} else {
					// Publish at a later date
					$scheduled_publish_datetime = $values['publish/publish_date'].' '.$values['publish/publish_hours'].':'.$values['publish/publish_mins'].':00';
					$cVersion = getRow('content_items', 'admin_version', array('id' => $cID, 'type' => $cType));
					updateRow('content_item_versions', array('scheduled_publish_datetime'=>$scheduled_publish_datetime), array('id' =>$cID, 'type'=>$cType, 'version'=>$cVersion));
					
					// Lock content item
					$adminId = session('admin_userid');
					updateRow('content_items', array('lock_owner_id'=>$adminId, 'locked_datetime'=>date('Y-m-d H:i:s')), array('id' =>$cID, 'type'=>$cType));
				}
			}
		}
		
	}
}
