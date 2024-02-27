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

class zenario_common_features__admin_boxes__content_staging_mode extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		if (!ze::setting('enable_staging_mode')) {
			echo ze\admin::phrase('Staging mode is not enabled on this site.');
			exit;
		}
		
		$cID =
		$cType = false;
		
		
		if (!empty($box['key']['id'])
		 && (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $box['key']['id']))
		 && ($cVersion = ze\content::latestVersion($cID, $cType))
		 && ($version = ze\sql::fetchAssoc("
				SELECT
					v.version, v.access_code
					/*
					v.title, v.description, v.keywords,
					v.layout_id, v.css_class, v.feature_image_id,
					v.release_date, v.published_datetime, v.created_datetime,
					v.rss_slot_name, v.rss_nest
					*/
				FROM ". DB_PREFIX. "content_item_versions AS v
				WHERE v.id = ". (int) $cID. "
				  AND v.type = '". ze\escape::asciiInSQL($cType). "'
				  AND v.version = ". (int) $cVersion))
		 && ($content = \ze\sql::fetchAssoc("
				SELECT
					equiv_id, id, type, language_id, alias,
					visitor_version, admin_version, status, lock_owner_id
				FROM ". DB_PREFIX. "content_items
				WHERE id = ". (int) $cID. "
				  AND type = '". \ze\escape::asciiInSQL($cType). "'"))
		 && ($chain = \ze\sql::fetchAssoc("
				SELECT equiv_id, type, privacy, at_location, smart_group_id
				FROM ". DB_PREFIX. "translation_chains
				WHERE equiv_id = ". (int) $content['equiv_id']. "
				  AND type = '". \ze\escape::asciiInSQL($cType). "'"))

		//
		//	Some checks that need to be true to show a draft in staging mode.
		//

		//1. The content item must have a draft.
		 && (ze\content::isDraft($content['status']))

		//2. This only works on the draft version.
		 && ($version['version'] == $content['admin_version'])

		//2. This only works on public content items. 
		 && ($chain['privacy'] == 'public')


		) {
			//OK everything looks fine.

		} else {
			echo ze\admin::phrase('Staging mode only works on draft content items');
			exit;
		}
		
		$box['key']['cID'] = $cID;
		$box['key']['cType'] = $cType;
		$box['key']['cVersion'] = $cVersion;
		
		
		if (!is_null($version['access_code'])) {
			$values['staging_mode/use_access_code'] = 1;
			$values['staging_mode/access_code'] = $version['access_code'];
		}
		
		$box['title'] =
			ze\admin::phrase('Staging mode for the content item "[[tag]]"', ['tag' => ze\content::formatTag($box['key']['cID'], $box['key']['cType'])]);
		
		$fields['staging_mode/use_access_code']['label'] =
			ze\admin::phrase('Enable staging mode for this version of this content item (version [[version]])', $version);
		
		
		//Get a list for the auto-complete drop-down
		$codes = ze\row::getDistinctValues('content_item_versions', 'access_code', ['access_code' => ['!' => null]], 'access_code');
		if (empty($codes)) {
			$fields['staging_mode/existing_codes']['hidden'] = true;
		} else {
			$ord = 0;
			$fields['staging_mode/existing_codes']['values'] = [];
			
			foreach ($codes as $code) {
				$fields['staging_mode/existing_codes']['values'][$code] = [
					'ord' => ++$ord,
					'label' => $code
				];
			}
			
			$fields['staging_mode/suggest_code']['value'] =
				ze\admin::phrase('Suggest a different code');
		}
		
	}
	

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Watch out for the admin selecting something from the existing values select list
		if (!empty($values['staging_mode/existing_codes'])) {
			
			$values['staging_mode/access_code'] = $values['staging_mode/existing_codes'];
			$values['staging_mode/existing_codes'] = '';
		
		//Watch out for the suggest button being pressed
		} elseif (!empty($fields['staging_mode/suggest_code']['pressed'])) {
			$values['staging_mode/access_code'] = ze\ring::randomFromSetNoProfanities();
		}
		unset($fields['staging_mode/suggest_code']['pressed']);
		
		//Update the URL field with the resulting URL
		if ($box['key']['cID']
		 && $box['key']['cType']
		 && $values['staging_mode/access_code'] != '') {
			$values['staging_mode/copy_code'] =
				ze\contentAdm::stagingModeLink($box['key']['cID'], $box['key']['cType'], $values['staging_mode/access_code']);
			
			
			$otherContentItems = ze\row::getValues('content_item_versions', ['id', 'type'], ['access_code' => $values['staging_mode/access_code'], 'tag_id' => ['!' => $box['key']['cType']. '_'. $box['key']['cID']]]);
			
			if (empty($otherContentItems)) {
				$fields['staging_mode/suggest_code']['notices_below']['code_reuse']['hidden'] = true;
			} else {
				$others = count($otherContentItems) - 1;
				$mrg = $otherContentItems[0];
				
				$mrg['tag'] =
					'<a href="'. ze\link::toItem($mrg['id'], $mrg['type']). '" target="_blank">'.
						htmlspecialchars(ze\content::formatTag($mrg['id'], $mrg['type'])).
					'</a>';
				
				$fields['staging_mode/suggest_code']['notices_below']['code_reuse']['hidden'] = false;
				$fields['staging_mode/suggest_code']['notices_below']['code_reuse']['message'] =
					ze\admin::nPhrase(
						"The access code you've chosen is the same as that for [[tag]] and [[count]] other content item in staging mode.",
						"The access code you've chosen is the same as that for [[tag]] and [[count]] other content items in staging mode.",
						$others, $mrg,
						"The access code you've chosen is the same as that for [[tag]], also in staging mode.",
					);
			}
			
		} else {
			$values['staging_mode/copy_code'] = '';
		}
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//Update the access code, if set
		ze\row::update('content_item_versions',[
			'access_code' => $values['staging_mode/use_access_code']? $values['staging_mode/access_code'] : null
		], [
			'id' => $box['key']['cID'],
			'type' => $box['key']['cType'],
			'version' => $box['key']['cVersion']
		]);
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}
