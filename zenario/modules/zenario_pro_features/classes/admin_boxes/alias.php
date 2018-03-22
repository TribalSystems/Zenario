<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

				
class zenario_pro_features__admin_boxes__alias extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if (ze\priv::check('_PRIV_MANAGE_SPARE_ALIAS')) {
			//Only show the "Create a Spare Alias under the old name" field if there is an existing alias,
			//which is not already in the spare aliases table.
			if (empty($box['tabs']['meta_data']['fields']['alias']['value'])
			 || !ze\content::isPublished($box['key']['cID'], $box['key']['cType'])
			 || ze\row::exists('spare_aliases', ['alias' => $box['tabs']['meta_data']['fields']['alias']['value']])) {
				$box['tabs']['meta_data']['fields']['zenario_pro_features__create_spare_alias']['hidden'] = true;
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//Create a Spare Alias under the old name, if there is an old name, it is being changed, and it was requested
		if (ze\priv::check('_PRIV_MANAGE_SPARE_ALIAS') && ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			if ($values['meta_data/zenario_pro_features__create_spare_alias']
			&& $box['tabs']['meta_data']['fields']['alias']['value'] == $box['tabs']['meta_data']['fields']['alias']['current_value']) {
				$box['tabs']['meta_data']['errors'][] =
				ze\admin::phrase('You cannot create a spare alias under the old name as you have not actually changed the alias.');
			}
		}
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Create a Spare Alias under the old name, if there is an old name, it is being changed, and it was requested
		if (ze\priv::check('_PRIV_MANAGE_SPARE_ALIAS') && ze\ring::engToBoolean($box['tabs']['meta_data']['edit_mode']['on'] ?? false)) {
			if ($values['meta_data/zenario_pro_features__create_spare_alias']
			&& $box['tabs']['meta_data']['fields']['alias']['value']
			&& $box['tabs']['meta_data']['fields']['alias']['value'] != $box['tabs']['meta_data']['fields']['alias']['current_value']) {
		
				$row = [
						'ext_url' => '',
						'content_id' => $box['key']['cID'],
						'content_type' => $box['key']['cType'],
						'created_datetime' => ze\date::now(),
						'alias' => $box['tabs']['meta_data']['fields']['alias']['value']];
		
				ze\row::insert('spare_aliases', $row);
			}
		}
		
	}
}
