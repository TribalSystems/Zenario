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

switch ($path) {
	case 'zenario__email_template_manager/panels/newsletters':
		if ($refinerName == 'archive') {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_archive'];
			$panel['default_sort_column'] = 'date_sent';
			$panel['bold_columns_in_list_view'] = 'newsletter_name';
		} elseif ($refinerName == 'outbox') {
			$panel['db_items']['where_statement'] = '';
		}
		
		if ($refinerName == 'outbox' || $refinerName == 'archive') {
			unset($panel['columns']['recipients']);
			$panel['item_buttons']['edit']['label'] = 'Show newsletter details';
		}
		
		if ($refinerName != 'outbox') {
			unset($panel['columns']['progress_sent']);
			unset($panel['columns']['progress_total']);
		}
		
		if ($refinerName != 'archive') {
			unset($panel['columns']['recipient_users']);
			unset($panel['columns']['smart_group_rules']);
			
			if ($refinerName != 'outbox') {
				unset($panel['columns']['date_sent']);
			}
		}
		
		if (!windowsServer()) {
			if (inc('zenario_scheduled_task_manager')) {
				if (zenario_scheduled_task_manager::checkScheduledTaskRunning('jobSendNewsletters')) {
					unset($panel['item_buttons']['resume']);
				}
			}
		}

		
		break;
	
	
	case 'zenario__content/panels/email_images_for_newsletters':
		$details = $this->loadDetails($refinerId);
		$panel['title'] = adminPhrase('Images in the Newsletter "[[newsletter_name]]"', $details);
		$panel['no_items_message'] = adminPhrase('There are no images in this Newsletter.');
		
		if ($details['status'] != '_DRAFT') {
			unset($panel['collection_buttons']['add']);
			unset($panel['collection_buttons']['upload']);
			unset($panel['collection_buttons']['delete_inline_file']);
		}
		
		//Borrow the logic from the image library panel to handle the images
		$c = $this->runSubClass('zenario_common_features', 'organizer', 'zenario__content/panels/image_library');
		$c->preFillOrganizerPanel('generic_image_panel', $panel, $refinerName, $refinerId, $mode);
		
		break;
		

	case 'zenario__users/panels/users':
		
		if ($refinerName == 'zenario_newsletter__recipients') {
			
			$details = $this->loadDetails(get('refiner__zenario_newsletter__recipients'));
			$panel['title'] = adminPhrase('Recipients for the Newsletter "[[newsletter_name]]"', $details);
			
			foreach (array('collection_buttons', 'item_buttons') as $tag) {
				foreach ($panel[$tag] as $name => &$button) {
					if (is_array($button)) {
						if (substr($name, 0, 20) != 'zenario_newsletter__') {
							$button['hidden'] = true;
						}
					}
				}
			}
			
			if ($sql = zenario_newsletter::newsletterRecipients($refinerId, 'get_sql')) {
				$panel['refiners']['zenario_newsletter__recipients']['table_join'] = $sql['table_join'];
				$panel['refiners']['zenario_newsletter__recipients']['sql'] = $sql['where_statement'];
				
				$panel['no_items_message'] = adminPhrase('No Recipients are included in this Newsletter.');
			} else {
				$panel['no_items_message'] = adminPhrase('No Recipients have been set.');
			}
		}
		
		break;
}