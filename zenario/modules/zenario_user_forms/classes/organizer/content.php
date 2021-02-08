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

class zenario_user_forms__organizer__content extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($refinerName == 'form_id') {
			$formId = $refinerId;
			$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name'], $formId);
			$panel['title'] = ze\admin::phrase('Content items on which the form "[[name]]" is used', $form);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the form "[[name]]".', $form);
			$panel['collection_buttons']['create']['hidden'] = true;
			
			//Get plugins using this form
			$instanceIds = [];
			$moduleIds = zenario_user_forms::getFormModuleIds();
			if ($moduleIds) {
				$sql = '
					SELECT id, name, 0 AS egg_id
					FROM '.DB_PREFIX.'plugin_instances
					WHERE module_id IN ('. ze\escape::in($moduleIds, 'numeric'). ')
					ORDER BY name';
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$instanceIds[] = $row['id'];
				}
			
				$sql = "
					SELECT pi.id, pi.name, np.id AS egg_id
					FROM ". DB_PREFIX. "nested_plugins AS np
					INNER JOIN ". DB_PREFIX. "plugin_instances AS pi
					   ON pi.id = np.instance_id
					WHERE np.module_id IN (". ze\escape::in($moduleIds, 'numeric'). ")
					ORDER BY pi.name";
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$instanceIds[] = $row['id'];
				}
			}
		
			$sqlJoin = $sqlWhere = '';
			if ($instanceIds) {
				$sqlJoin = '
					INNER JOIN '.DB_PREFIX.'plugin_item_link pil
						ON (c.id = pil.content_id) AND (c.type = pil.content_type) AND (c.admin_version = pil.content_version)
					INNER JOIN '.DB_PREFIX.'plugin_settings ps 
						ON (pil.instance_id = ps.instance_id) AND (ps.name = \'user_form\')';
				$sqlWhere = '
					pil.instance_id IN ('. ze\escape::in($instanceIds, 'numeric'). ')
						AND ps.value = '.(int)$refinerId;
			} else {
				$sqlWhere = '
					FALSE';
			}
				
			$panel['refiners']['form_id']['table_join'] = $sqlJoin;
			$panel['refiners']['form_id']['sql'] = $sqlWhere;
		}
	}
}