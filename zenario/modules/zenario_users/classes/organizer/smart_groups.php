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

class zenario_users__organizer__smart_groups extends zenario_users {

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//If it looks like a site is supposed to be using encryption, but it's not set up properly,
		//show an error message.
		ze\pdeAdm::showNoticeOnPanelIfConfIsBad($panel);
		
		
		if (!ze\module::isRunning('zenario_extranet')) {
			unset($panel['collection_buttons']['perms']);
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['members'] = ze\smartGroup::countMembers($id);

			$item['members_formatted_nicely'] = ze\admin::nPhrase('1 member', '[[count]] members', (int) $item['members'], ['count' => (int) $item['members']]);


			if ($item['members'] === false) {
				$item['description'] = ze\admin::phrase('There is a problem with this smart group.');
			} else {
				$item['description'] = ze\contentAdm::getSmartGroupDescription($id);
			}
			
			switch ($item['intended_usage']) {
				case 'smart_newsletter_group':
					$item['css_class'] = 'zenario_smart_news_group';
					$item['intended_usage_formatted_nicely'] = ze\admin::phrase('Smart newsletter group');
					break;
				case 'smart_permissions_group':
					$item['css_class'] = 'zenario_smart_perms_group';
					$item['intended_usage_formatted_nicely'] = ze\admin::phrase('Smart group');
					break;
			}
		}
	}
	
	

	public function handleAJAX() {
		$ids = $_POST['id'] ?? false;
		$this->handleOrganizerPanelAJAX('zenario__users/panels/smart_groups', $ids, '', '', '');
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (ze::post('load_smart_group') && ze\priv::check('_PRIV_VIEW_USER')) {
			header('Content-Type: text/javascript; charset=UTF-8');
			echo ze\row::get('smart_groups', 'values', $ids);
			exit;
	
		} elseif (ze::post('save_smart_group') && ze\priv::check('_PRIV_MANAGE_GROUP')) {
			$json = [];
			$key = [];
			$values = [];
					
			if ($ids) {
					$key = ['id' => $ids];
					$json['exists'] = true;
					$values['name'] = $_POST['name'] ?? false;
	
			} else {
				$key = ['name' => ze::post('name')];
				$json['exists'] = ze\row::exists('smart_groups', $key);
			}
	
			if ($json['exists'] && !ze::post('confirm')) {
				$json['message'] = ze\admin::phrase('The Smart Group "[[name]]" already exists, do you want to overwrite it?', $key);
				$json['message_type'] = 'warning';
				$json['confirm_button_message'] = ze\admin::phrase('Overwrite Smart Group');

			} else {
				ze\priv::exitIfNot('_PRIV_MANAGE_GROUP');
				$values['values'] = $_POST['values'] ?? false;
				$values['last_modified_on'] = ze\date::now();
				$values['last_modified_by'] = ze\admin::id();
		
				if ($json['exists']) {
					$json['message'] = ze\admin::phrase('Updated the Smart Group named "[[name]]".', $key);
					$json['message_type'] = 'success';
							
				} else {
					$values['created_on'] = ze\date::now();
					$values['created_by'] = ze\admin::id();

					$json['message'] = ze\admin::phrase('Created a Smart Group named "[[name]]".', $key);
					$json['message_type'] = 'success';
				}
						
				$json['id'] = ze\row::set('smart_groups', $values, $key);
			}
	
			header('Content-Type: text/javascript; charset=UTF-8');
			echo json_encode($json);
			exit;
	
		
		} elseif (ze::post('delete') && ze\priv::check('_PRIV_MANAGE_GROUP')) {
			foreach (explode(',', $ids) as $id) {
				ze\row::delete('smart_groups', $id);
				ze\row::delete('smart_group_rules', ['smart_group_id' => $id]);
				ze\module::sendSignal("eventSmartGroupDeleted",["smartGroupId" => $id]);
			}
		}
			
	}
}