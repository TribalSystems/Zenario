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

switch ($path) {
	case 'plugin_settings':
		$box['tabs']['order']['fields']['pagination_style_posts']['values'] = 
			ze\pluginAdm::paginationOptions();
		
		if (isset($box['tabs']['order']['fields']['pagination_style_threads'])) {
			$box['tabs']['order']['fields']['pagination_style_threads']['values'] = 
				ze\pluginAdm::paginationOptions();
		}
		
		if (isset($box['tabs']['moderation']['fields']['email_address_for_reports']) && !$box['key']['instanceId']) {
			$box['tabs']['moderation']['fields']['email_address_for_reports']['value'] = ze::setting('email_address_admin');
		}
		
		
		$moderators =
			isset($box['tabs']['moderation']['fields']['moderators']);
		$restrict_posting_to_group =
			isset($box['tabs']['posting']['fields']['restrict_posting_to_group']);
		$restrict_new_thread_to_group =
			isset($box['tabs']['posting']['fields']['restrict_new_thread_to_group']);
		
		if ($moderators
		 || $restrict_posting_to_group
		 || $restrict_new_thread_to_group) {
			
			$groups = ze\datasetAdm::listCustomFields('users', $flat = false, $filter = 'groups_only', true, true);
			//ze\datasetAdm::listCustomFields($dataset, $flat = true, $filter = false, $customOnly = true, $useOptGroups = false)
			
			if ($moderators) {
				$box['tabs']['moderation']['fields']['moderators']['values'] = $groups;
			}
			if ($restrict_posting_to_group) {
				$box['tabs']['posting']['fields']['restrict_posting_to_group']['values'] = $groups;
			}
			if ($restrict_new_thread_to_group) {
				$box['tabs']['posting']['fields']['restrict_new_thread_to_group']['values'] = $groups;
			}
		}
		
		foreach ($fields as &$field) {
			if (!empty($field['note_below'])
			 && $field['note_below'] == '_insert_email_template_note_here_') {
				$field['note_below'] =
					ze\admin::phrase('Please see the <a href="[[link]]" target="_blank">Module description</a> to get a full list of merge fields which can be used in the selected email template.',
						array('link' => htmlspecialchars(
							ze\link::absolute().
							'zenario/admin/organizer.php#zenario__modules/panels/modules//'. $box['key']['moduleId']. '/')));
				
				
			}
		}
		
		break;
}