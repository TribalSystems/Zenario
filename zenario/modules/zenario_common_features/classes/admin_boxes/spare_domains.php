<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_common_features__admin_boxes__spare_domains extends ze\moduleBaseClass {
	
	protected function fillFieldValues(&$values, &$rec){
		foreach($rec as $k => $v){
			if (isset($values[$k])) {
				$values[$k] = $v;
			}
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$record = ze\row::get('spare_domain_names', true, ['requested_url' => ze\ring::decodeIdForOrganizer($box['key']['id'])]);
			//$this->fillFieldValues($values, $record);

			$values['details/requested_url'] = $record['requested_url'];
			$values['details/content'] = $record['content_type']. '_'. $record['content_id'];
			
			$box['title'] = ze\admin::phrase('Edit a domain name redirect');
			$fields['requested_url']['readonly'] = true;
			$fields['add_www']['hidden'] = true;
		}
		
		ze\lang::applyMergeFields($fields['description']['snippet']['html'], ['domain_link' => 'organizer.php#zenario__administration/panels/site_settings//domains']);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		if(!$box['key']['id']) {
			if (!$values['details/requested_url']) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a domain name.');
			
			} elseif (!ze\contentAdm::aliasURLIsValid($values['details/requested_url'])) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('Please enter a valid domain name.');
			
			} else {
				if (ze\row::exists('spare_domain_names', ['requested_url' => $values['details/requested_url']])) {
					$box['tabs']['details']['errors'][] =
						ze\admin::phrase('The domain "[[details/requested_url]]" is already used as a spare domain name.', $values);
				
				} elseif (ze\row::exists('languages', ['domain' => $values['details/requested_url']])) {
					$box['tabs']['details']['errors'][] =
						ze\admin::phrase('The domain "[[details/requested_url]]" is already used as a language specific domain.', $values);
				}
				
				if (!empty($values['details/add_www'])) {
					if (ze\row::exists('spare_domain_names', ['requested_url' => 'www.'. $values['details/requested_url']])) {
						$box['tabs']['details']['errors'][] =
							ze\admin::phrase('The domain "[[domain]]" is already used as a spare domain name.',
								['domain' => 'www.'. $values['details/requested_url']]);
					
					} elseif (ze\row::exists('languages', ['domain' => 'www.'. $values['details/requested_url']])) {
						$box['tabs']['details']['errors'][] =
							ze\admin::phrase('The domain "[[domain]]" is already used as a language specific domain.',
								['domain' => 'www.'. $values['details/requested_url']]);
					}
				}
			}			
		}

		if (!$values['details/content']) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase('Please select a content item.');
		} else {
			$cID = $cType = false;
			ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['details/content']);
			if (!ze\link::toItem($cID, $cType)) {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('Content item not found. Please select a different one.');
			}
			$contentItemStatus = ze\row::get('content_items', 'status', ['id' => $cID, 'type' => $cType]);
			if ($contentItemStatus == 'deleted') {
				$box['tabs']['details']['errors'][] = ze\admin::phrase('This content item was deleted. Please select a different one.');
			}
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
			
		$cID = $cType = false;
		ze\content::getCIDAndCTypeFromTagId($cID, $cType, $values['details/content']);
		
		if ($requested_url = ze\ring::decodeIdForOrganizer($box['key']['id'])) {
			$sql = "
				UPDATE ". DB_PREFIX. "spare_domain_names SET
					content_id = ". (int) $cID. ",
					content_type = '". ze\escape::sql($cType). "'
				WHERE requested_url = '". ze\escape::sql($requested_url). "'";
			ze\sql::update($sql);
			
		} else {
			$sql = "
				INSERT INTO ". DB_PREFIX. "spare_domain_names SET
					requested_url = '". ze\escape::sql($values['details/requested_url']). "',
					content_id = ". (int) $cID. ",
					content_type = '". ze\escape::sql($cType). "'";
			ze\sql::update($sql);
			
			$box['key']['id'] = $values['details/requested_url'];
			
			if (!empty($values['details/add_www'])) {
				$sql = "
					INSERT INTO ". DB_PREFIX. "spare_domain_names SET
						requested_url = 'www.". ze\escape::sql($values['details/requested_url']). "',
						content_id = ". (int) $cID. ",
						content_type = '". ze\escape::sql($cType). "'";
				ze\sql::update($sql);
			}
		}
	}
}
