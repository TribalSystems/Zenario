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

				
class zenario_pro_features__admin_boxes__pro_features_spare_alias extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if (checkPriv('_PRIV_MANAGE_SPARE_ALIAS')) {
			//Only show the "Create a Spare Alias under the old name" field if there is an existing alias,
			//which is not already in the spare aliases table.
			if (empty($box['tabs']['meta_data']['fields']['alias']['value'])
			 || !isPublished($box['key']['cID'], $box['key']['cType'])
			 || checkRowExists('spare_aliases', array('alias' => $box['tabs']['meta_data']['fields']['alias']['value']))) {
				$box['tabs']['meta_data']['fields']['zenario_pro_features__create_spare_alias']['hidden'] = true;
			}
		}
		
		if ($box['key']['id']
		 && $box['key']['id_is_error_log_id']
		 && inc('zenario_error_log')) {
			$brokenAlias = getRow(ZENARIO_ERROR_LOG_PREFIX.'error_log', 'page_alias', array('id' => $box['key']['id']));
			if (checkRowExists('spare_aliases', array('alias' => $brokenAlias))) {
				$box['key']['id'] = $brokenAlias;
			} else {
				$box['key']['id'] = '';
				$values['spare_alias/alias'] = $brokenAlias;
			}
			
		}
		
		if (!$box['key']['id']) {
			$box['tabs']['spare_alias']['edit_mode']['on'] = true;
			$box['tabs']['spare_alias']['edit_mode']['always_on'] = true;
		
		} else {
			$details = getRow('spare_aliases', true, $box['key']['id']);
			$box['title'] = adminPhrase('Editing the alias "[[alias]]"', array('alias' => ($details['alias'])));
				
			$box['tabs']['spare_alias']['fields']['alias']['value'] = $details['alias'];
			$box['tabs']['spare_alias']['fields']['alias']['read_only'] = true;
				
			$box['tabs']['spare_alias']['fields']['target_loc']['value'] = $details['target_loc'];
				
			if ($details['target_loc'] == 'int') {
				$box['tabs']['spare_alias']['fields']['hyperlink_target']['value'] = $details['content_type']. '_'. $details['content_id'];
			} else {
				$box['tabs']['spare_alias']['fields']['ext_url']['value'] = $details['ext_url'];
			}
		}
		
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$box['tabs']['spare_alias']['fields']['hyperlink_target']['hidden'] = 
			$values['spare_alias/target_loc'] != 'int';
		
		$box['tabs']['spare_alias']['fields']['ext_url']['hidden'] = 
			$values['spare_alias/target_loc'] != 'ext';
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (!$box['key']['id']) {
			if (!$values['spare_alias/alias']) {
				$box['tabs']['spare_alias']['errors'][] = adminPhrase('Please enter an Alias.');
		
			} elseif (checkRowExists('spare_aliases', array('alias' => $values['spare_alias/alias']))) {
				$box['tabs']['spare_alias']['errors'][] = adminPhrase('The spare alias "[[alias]]" is already in use.', array('alias' => $values['spare_alias/alias']));
		
			} elseif ($mistakesInAlias = validateAlias($values['spare_alias/alias'])) {
				$box['tabs']['spare_alias']['errors'] = $mistakesInAlias;
			}
		}
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		exitIfNotCheckPriv('_PRIV_MANAGE_SPARE_ALIAS');
		
		$alias = ifNull($box['key']['id'], $values['spare_alias/alias']);
		
		$row = array(
				'ext_url' => '',
				'content_id' => 0,
				'content_type' => '');
		
		if ($values['spare_alias/target_loc'] == 'int') {
			$row['target_loc'] = 'int';
			getCIdAndCTypeFromTagId($row['content_id'], $row['content_type'], $values['spare_alias/hyperlink_target']);
		
		} elseif ($values['spare_alias/target_loc'] == 'ext') {
			$row['target_loc'] = 'ext';
			$row['ext_url'] = $values['spare_alias/ext_url'];
		
		} else {
			exit;
		}
		
		if (!$box['key']['id']) {
			$row['created_datetime'] = now();
		}
		
		setRow('spare_aliases', $row, array('alias' => $alias));
		
	}
	
}
