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


class zenario_common_features__admin_boxes__spare_domains extends module_base_class {
	
	protected function fillFieldValues(&$values, &$rec){
		foreach($rec as $k => $v){
			if (isset($values[$k])) {
				$values[$k] = $v;
			}
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($box['key']['id']) {
			$record = getRow('spare_domain_names', true, array('requested_url' => decodeItemIdForOrganizer($box['key']['id'])));
			//$this->fillFieldValues($values, $record);

			$values['details/requested_url'] = $record['requested_url'];
			$values['details/content'] = $record['content_type']. '_'. $record['content_id'];
			
			$box['title'] = adminPhrase('View/Edit a spare domain');
			$fields['requested_url']['readonly'] = true;
			$fields['add_www']['hidden'] = true;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
	
		if(!$box['key']['id']) {
			if (!$values['details/requested_url']) {
				$box['tabs']['details']['errors'][] = adminPhrase('Please enter a domain name.');
			
			} elseif (!aliasURLIsValid($values['details/requested_url'])) {
				$box['tabs']['details']['errors'][] = adminPhrase('Please enter a valid domain name.');
			
			} else {
				if (checkRowExists('spare_domain_names', array('requested_url' => $values['details/requested_url']))) {
					$box['tabs']['details']['errors'][] =
						adminPhrase('The domain "[[details/requested_url]]" is already used as a spare domain name.', $values);
				
				} elseif (checkRowExists('languages', array('domain' => $values['details/requested_url']))) {
					$box['tabs']['details']['errors'][] =
						adminPhrase('The domain "[[details/requested_url]]" is already used as a language specific domain.', $values);
				}
				
				if (!empty($values['details/add_www'])) {
					if (checkRowExists('spare_domain_names', array('requested_url' => 'www.'. $values['details/requested_url']))) {
						$box['tabs']['details']['errors'][] =
							adminPhrase('The domain "[[domain]]" is already used as a spare domain name.',
								array('domain' => 'www.'. $values['details/requested_url']));
					
					} elseif (checkRowExists('languages', array('domain' => 'www.'. $values['details/requested_url']))) {
						$box['tabs']['details']['errors'][] =
							adminPhrase('The domain "[[domain]]" is already used as a language specific domain.',
								array('domain' => 'www.'. $values['details/requested_url']));
					}
				}
			}			
		}

		if (!$values['details/content']) {
			$box['tabs']['details']['errors'][] = adminPhrase('Please select a content item.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
			
		$cID = $cType = false;
		getCIDAndCTypeFromTagId($cID, $cType, $values['details/content']);
		
		if ($requested_url = decodeItemIdForOrganizer($box['key']['id'])) {
			$sql = "
				UPDATE ". DB_NAME_PREFIX. "spare_domain_names SET
					content_id = ". (int) $cID. ",
					content_type = '". sqlEscape($cType). "'
				WHERE requested_url = '". sqlEscape($requested_url). "'";
			sqlQuery($sql);
			
		} else {
			$sql = "
				INSERT INTO ". DB_NAME_PREFIX. "spare_domain_names SET
					requested_url = '". sqlEscape($values['details/requested_url']). "',
					content_id = ". (int) $cID. ",
					content_type = '". sqlEscape($cType). "'";
			sqlQuery($sql);
			
			$box['key']['id'] = $values['details/requested_url'];
			
			if (!empty($values['details/add_www'])) {
				$sql = "
					INSERT INTO ". DB_NAME_PREFIX. "spare_domain_names SET
						requested_url = 'www.". sqlEscape($values['details/requested_url']). "',
						content_id = ". (int) $cID. ",
						content_type = '". sqlEscape($cType). "'";
				sqlQuery($sql);
			}
		}
	}
}
