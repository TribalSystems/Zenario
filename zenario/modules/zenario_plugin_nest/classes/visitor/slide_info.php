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

class zenario_plugin_nest__visitor__slide_info extends zenario_abstract_fea {
	
	public function returnVisitorTUIXEnabled($path) {
		
		if ($sl = \ze\row::get('slide_layouts', ['id', 'privacy', 'layout_for', 'layout_for_id'], ze::request('slideLayoutId'))) {
			switch ($sl['layout_for']) {
				case 'schema':
					return ze\user::can('design', 'schema', $sl['layout_for_id']);
			}
		}
		
		return false;
	}


	public function fillVisitorTUIX($path, &$tags, &$fields, &$values) {
		parent::fillVisitorTUIX($path, $tags, $fields, $values);
		$this->translatePhrasesInTUIX($tags, $path);
		
		
		if (!ze::isAdmin()) {
			$tags['key']['cVersion'] = ze\content::publishedVersion($tags['key']['cID'], $tags['key']['cType']);
		}
		
		$sl = \ze\row::get('slide_layouts', ['id', 'layout_for', 'layout_for_id', 'ord', 'name', 'privacy'], ze::request('slideLayoutId'));
		$slide = \ze\row::get('nested_plugins', ['id', 'slide_num'], ze::request('slideId'));
		
		$values['details/state'] = ze::request('state');
		
		$fields['details/sl_title']['snippet']['h2'] = $this->phrase('Slide layout "[[ord]]. [[name]]"', $sl);
		

		$values['details/privacy'] = $this->slideLayoutPrivacyDesc($sl);
		$values['details/change_slide_layout'] = $sl['id'];
		
		$commands = json_decode(ze::request('commands'));
		if (!empty($commands)) {
			sort($commands);
			$values['details/commands'] = implode(', ', $commands);
		}
		
		
		
		//Check what other slide layouts can be seen by the current user.
		//(Usually all of them as this screen needs you to be a super-user to see.)
		$sql = "
			SELECT id AS slide_layout_id, CONCAT(ord, '. ', name) AS label, ord, privacy
			FROM ". DB_PREFIX. "slide_layouts
			WHERE layout_for = '". ze\escape::sql($sl['layout_for']). "'
			  AND layout_for_id = ". (int) $sl['layout_for_id']. "
			ORDER BY ord";
		
		$tags['key']['naturalSlideLayoutId'] = 0;
		
		foreach (ze\sql::select($sql) as $otherSL) {
			$priv = ze\content::checkItemPrivacy($otherSL, $otherSL, $tags['key']['cID'], $tags['key']['cType'], $tags['key']['cVersion'], $roleLocationMustMatch = true);
			
			if ($priv) {
				//Note down which slide layout the super user would normally see
				if (!$tags['key']['naturalSlideLayoutId']) {
					$tags['key']['naturalSlideLayoutId'] = $otherSL['slide_layout_id'];
				}
			} else {
				$otherSL['disabled'] = true;
			}
			
			$otherSL['label'] .= ' ('. $this->slideLayoutPrivacyDesc($otherSL). ')';
			
			$fields['details/change_slide_layout']['values'][$otherSL['slide_layout_id']] = $otherSL;
		}
		
		
		switch ($sl['layout_for']) {
			case 'schema':
				$fields['details/slide_designer']['custom__popout']['href'] .= '?schemaId='. $sl['layout_for_id'];
		}
	}
	
	public function formatVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
	}
	
	public function validateVisitorTUIX($path, &$tags, &$fields, &$values, &$changes, $saving) {
		
	}
	
	public function saveVisitorTUIX($path, &$tags, &$fields, &$values, &$changes) {
		
	}
}