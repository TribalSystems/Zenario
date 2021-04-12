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

class zenario_user_forms__admin_boxes__plugin_settings extends ze\moduleBaseClass {
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($fields['first_tab/display_text'])) {
			$fields['first_tab/display_text']['hidden'] = ($values['first_tab/display_mode'] != 'in_modal_window');
		}
		
		$formId = $values['first_tab/user_form'];
		$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['type', 'allow_partial_completion', 'partial_completion_mode'], $formId);

		if (is_array($form)) {
			$fields['first_tab/partial_completion_button_position']['hidden'] = !($form && $form['allow_partial_completion'] && $form['partial_completion_mode'] == 'button' || $form['partial_completion_mode'] == 'auto_and_button');
			
			//Only show the option to hide extranet links to registration forms
			$fields['first_tab/hide_extranet_links']['hidden'] = $form['type'] != 'registration';
		} else {
			$fields['first_tab/partial_completion_button_position']['hidden'] = $fields['first_tab/hide_extranet_links']['hidden'] = true;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$errors = &$box['tabs']['first_tab']['errors'];
		if (isset($values['first_tab/display_mode']) && $values['first_tab/display_mode'] == 'inline_in_page' && isset($values['first_tab/show_print_page_button']) && $values['first_tab/show_print_page_button']) {
			$pages = $values['first_tab/print_page_button_pages'];
			if (!$pages) {
				$errors[] = ze\admin::phrase('Please enter the pages for the print page button.');
			} else {
				$pages = explode(',', $pages);
				foreach ($pages as $page) {
					if (!ctype_digit($page)) {
						$errors[] = ze\admin::phrase('Please a valid list of pages for the print page button.');
						break;
					}
				}
			}
		}
	}
}