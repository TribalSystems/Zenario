<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


class zenario_common_features__organizer__tuix_snippets extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {


		//Don't show anything in this panel if there are no visitor/FEA plugins running on the site
		if (!ze\sql::numRows("
			SELECT 1
			FROM ". DB_PREFIX. "modules AS m
			INNER JOIN ". DB_PREFIX. "tuix_file_contents AS tfc
			   ON tfc.type = 'visitor'
			  AND tfc.path NOT LIKE 'slot_settings_%'
			  AND tfc.path NOT IN ('slot_plugin_and_mode', 'zenario_slide_info')
			  AND tfc.module_class_name = m.class_name
			WHERE m.status = 'module_running'
			LIMIT 1
		")) {
			unset($panel['db_items']);
			unset($panel['collection_buttons']);
			unset($panel['item_buttons']);
			$panel['no_items_message'] = ' ';
			$panel['notice']['show'] = true;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if (!empty($panel['items'])) {
			foreach ($panel['items'] as &$item) {
				if ($tuix = json_decode($item['custom_json'], true)) {
					foreach (['columns', 'collection_buttons', 'item_buttons'] as $tag) {
						if (isset($tuix[$tag]) && is_array($tuix[$tag])) {
							$item['num_'. $tag] = count($tuix[$tag]);
						}
					}
				}
			}
		}
	}

	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
			$sql = '
				DELETE FROM '. DB_PREFIX. 'tuix_snippets
				WHERE id IN ('. ze\escape::in($ids, 'numeric'). ')';
			ze\sql::update($sql);
		}
	}
}