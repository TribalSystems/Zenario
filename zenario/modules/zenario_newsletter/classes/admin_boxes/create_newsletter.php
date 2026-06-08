<?php
/*
 * Copyright (c) 2026, Tribal Limited
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


class zenario_newsletter__admin_boxes__create_newsletter extends zenario_newsletter {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		//...
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$useDisabledButtonTemplate = false;
		$replace = [];
		$buttonHtml = '';
		
		if ($values['create_empty_newsletter']) {
			$replace = ['action' => 'create_empty_newsletter', 'newsletter_template' => 0, 'previous_newsletter' => 0];
		} elseif ($values['create_newsletter_from_template']) {
			if ($values['create_newsletter/newsletter_template']) {
				$replace = ['action' => 'create_newsletter_from_template', 'newsletter_template' => $values['create_newsletter/newsletter_template'], 'previous_newsletter' => 0];
			} else {
				$useDisabledButtonTemplate = true;
			}
		} elseif ($values['create_newsletter_from_previous_newsletter']) {
			if ($values['create_newsletter/previous_newsletter']) {
				$replace = ['action' => 'create_newsletter_from_previous_newsletter', 'newsletter_template' => 0, 'previous_newsletter' => $values['create_newsletter/previous_newsletter']];
			} else {
				$useDisabledButtonTemplate = true;
			}
		}
		
		if ($useDisabledButtonTemplate) {
			$buttonHtml .= $box['extra_button_html_disabled_template'];
		} else {
			$buttonHtml .= '
<input
	type="button"
	class="zenario_fabExtraButton zenario_fabExtraButtonMenuProp zenario_submit_button"
	value="' . ze\admin::phrase('Continue') . '"
	onclick="
		var key = $.extend(true, {}, zenarioAB.tuix.key);
		key.action = \'' . htmlspecialchars($replace['action']) . '\';
		key.newsletter_template = ' . (int) $replace['newsletter_template'] . ';
		key.previous_newsletter = ' . (int) $replace['previous_newsletter'] . ';
		zenarioAB.close();
		zenarioA.openMenuAdminBox(key, \'zenario_newsletter\');
	"
/>';
			
		}
		
		$buttonHtml .= $box['cancel_button_html'];
		
		$box['extra_button_html'] = $buttonHtml;
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}