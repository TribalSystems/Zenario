<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_newsletter__organizer__users extends zenario_newsletter {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		 if ($refinerName == 'zenario_newsletter__recipients') {
			
			$details = $this->loadDetails($_GET['refiner__zenario_newsletter__recipients'] ?? false);
			$panel['title'] = ze\admin::phrase('Recipients for the Newsletter "[[newsletter_name]]"', $details);
			
			foreach (['collection_buttons', 'item_buttons'] as $tag) {
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
				
				$panel['no_items_message'] = ze\admin::phrase('No Recipients are included in this Newsletter.');
			} else {
				$panel['no_items_message'] = ze\admin::phrase('No Recipients have been set.');
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...your PHP code...//
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		//...your PHP code...//
	}
	
}