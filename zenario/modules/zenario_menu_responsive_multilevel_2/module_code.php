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

class zenario_menu_responsive_multilevel_2 extends zenario_menu {
	
	public function init(){
		if (parent::init()) {
			ze::requireJsLib('zenario/libs/manually_maintained/mit/slimmenu/jquery.slimmenu.min.js');
			if ($this->setting('show_link_to_home_page')) {
				if ($tagId = $this->setting('home_page')) {
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
					$this->mergeFields['Home_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}

			if ($this->setting('show_search_box')) {
				$this->mergeFields['Search_Box'] = true;
			
				//Get cID and cType if "Use a specific Search Results Page" was selected.
				$cID = $cType = $state = false;
				if ($this->setting('show_search_box') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page')) {
					
				} else {
					ze\content::pluginPage($cID, $cType, $state, 'zenario_search_results');
				}
			
				if ($cID == $this->cID && $cType == $this->cType) {
					$cID = $cType = false;
				}
				
				if ($this->setting('search_placeholder') && $this->setting('search_placeholder_phrase')) {
					$this->mergeFields['Placeholder'] = true;
					$this->mergeFields['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
				}
				
				$this->mergeFields['Search_Page_Alias'] = $cID;
				$this->mergeFields['Search_Page_cType'] = $cType;
			}

			if ($this->setting('show_link_to_registration_page')) {
				if ($tagId = $this->setting('registration_page')) {
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
					$this->mergeFields['Registration_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}

			if ($this->setting('show_link_to_login_page')) {
				if ($tagId = $this->setting('login_page')) {
					$cID = $cType = false;
					ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
					$this->mergeFields['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}
			
			$this->callScript(
				'zenario_menu_responsive_multilevel_2', 'init',
				$this->containerId. '_slimmenu', (int) ze::$minWidth,
				$this->setting('easing_effect'), $this->setting('anim_speed'));
			
			return true;
		} else {
			return false;
		}
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		parent::fillAdminBox($path, $settingGroup, $box, $fields, $values);

		if (!$box['key']['id']) {
			if(isset($values['links/home_page']) && !$values['links/home_page']){
				$values['links/home_page'] = "html_1";
			}
		}
	}
}