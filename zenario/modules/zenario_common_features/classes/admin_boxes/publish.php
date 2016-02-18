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
			$box['tabs']['publish']['fields']['desc']['snippet']['html'] = 
				adminPhrase('Are you sure you wish to publish the content item &quot;[[tag]]&quot;?', array('tag' => htmlspecialchars(formatTag($box['key']['cID'], $box['key']['cType']))));
		} else {
			$box['tabs']['publish']['fields']['desc']['snippet']['html'] = 
				adminPhrase('Are you sure you wish to publish the [[count]] selected content items?', array('count' => $count));
		}
		
		$status = getModuleStatusByClassName('zenario_scheduled_task_manager');
		if ($status != 'module_running') {
			$fields['publish/publish_options']['hidden'] = true;
		} else {
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


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		// Make sure chosen time is not in the past
		if ($values['publish/publish_options'] == 'schedule') {
			if (!empty($values['publish/publish_date'])) {
				$now = strtotime('now');
				$scheduledDate = strtotime($values['publish/publish_date'].' '. $values['publish/publish_hours'].':'.$values['publish/publish_mins']);
				if ($now < $scheduledDate) {
					return;
				} else {
					$box['tabs']['publish']['errors'][] = 'The scheduled publishing time cannot be in the past.';
				}
			} else {
				$box['tabs']['publish']['errors'][] = 'Please enter a date.';
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
