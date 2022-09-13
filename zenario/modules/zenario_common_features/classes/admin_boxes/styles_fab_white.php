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


	//This file and empty class declaration is needed because, from Zenario version 8.7 onwards,
	//any module that uses the /classes/ directory must have a file & class declaration for
 	//everything in TUIX that the module adds in a YAML file.

class zenario_common_features__admin_boxes__styles_fab_white extends  zenario_common_features {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		foreach ($box['tabs']['as_span']['fields'] as $codeName => &$field) {
			$field['show_as_a_span_when_readonly'] = true;
		}
		
		$fields['as_span/radios']['value'] =
		$fields['as_span/select']['value'] = 'left top';
		$fields['as_span/picker']['value'] = 'html_1';
		$fields['as_span/text']['value'] =
		$fields['as_span/textarea']['value'] = 'Hello world!';
		
		$fields['readonly_tab/radios']['value'] =
		$fields['readonly_tab/select']['value'] = 'left top';
		$fields['readonly_tab/picker']['value'] = 'html_1';
		$fields['readonly_tab/text']['value'] =
		$fields['readonly_tab/textarea']['value'] = 'Hello world!';
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		//...
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//...
	}
}