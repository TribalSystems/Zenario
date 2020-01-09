<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

ze\module::inc('zenario_abstract_fea');

class zenario_banner__visitor__slot_settings_zenario_banner extends zenario_abstract_fea {
	
	public function returnVisitorTUIXEnabled($path) {
		return ze\user::can('design', 'schema', ze::$vars['schemaId']);
	}


	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		parent::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		
		//Load the values of any plugin settings passed in from the Slide Designer
		$data = $this->loadSlotSettings($path, $tags, $fields, $values);
		
		//The text setting is saved as HTML, but I don't want people writing HTML, so convert it to plain text.
		if (!empty($values['details/text'])) {
			$values['details/text'] = html_entity_decode(str_replace(['<br>', '<br/>', '<br />'], '', $values['details/text']));
		}
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		//Clear the "pressed" status of any prev/next buttons
		$this->handleNavigationInFormat($tags, $fields, $values);
		
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		
		//Code to handle navigation between tabs using previous and next buttons
		$this->handleNavigationInValidate($tags, $fields, $values);
		
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
		//I got people to write plain text, but it's saved as HTML, so convert to HTML when saving
		if (!empty($values['details/text'])) {
			$values['details/text'] = nl2br(htmlspecialchars($values['details/text']));
		}
		
		//Save the values of any plugin settings back to the Slide Designer
		$this->saveSlotSettingsAndClose($tags, $fields, $values);
	}
}