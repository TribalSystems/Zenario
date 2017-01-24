<?php
/*
 * Copyright (c) 2017, Tribal Limited
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




//Display a language picker, with hyperlinks to each language that a site is published in

class zenario_language_picker extends module_base_class {
	
	var $sections = array();
	var $langs = array();
	
	function init()	{
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = $this->setting('destination') != 'home', $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$langs = array();
		
		//Loop through all of the languages enabled on this site, adding the details of each to an array
		foreach (getLanguages() as $langCfg) {
			$langs[$langCfg['id']] = array(
				'cID' => false,
				'cType' => false,
				'equivId' => cms_core::$homeEquivId,
				'flag' => $langCfg['flag'],
				'name' => $langCfg['language_local_name'],
				'current_language' => $langCfg['id'] == session('user_lang'),
				'isEquivOfThisPage' => false);
			
			//Look up the cID of the homepage in each language and note that down as well
			langSpecialPage('zenario_home', $langs[$langCfg['id']]['cID'], $langs[$langCfg['id']]['cType'], $langCfg['id'], true);
		}
		
		//Unless we should always link to the homepage, try to look up an equivalent content item of the current content item
		//in the language specified. If we find one, use this instead of the homepage link.
		if ($this->setting('destination') != 'home') {
			foreach (equivalences($this->cID, $this->cType) as $langId => $equiv) {
				if (isset($langs[$langId])) {
					$langs[$langId]['cID'] = $equiv['id'];
					$langs[$langId]['cType'] = $equiv['type'];
					$langs[$langId]['equivId'] = cms_core::$equivId;
					$langs[$langId]['isEquivOfThisPage'] = true;
				}
			}
		}
		
		//For each of the content items we found, check that they're good to link to. If they are, include the link to that language.
		foreach ($langs as $langId => &$lang) {
			if ($this->setting('destination') != 'equiv_only' || $lang['isEquivOfThisPage']) {
				if ($content = getRow('content_items', array('status', 'alias'), array('id' => $lang['cID'], 'type' => $lang['cType']))) {
					if (checkPriv() || isPublished($content['status'])) {
						$lang['alias'] = $content['alias'];
						$lang['View_This_Page_In_Lang'] = phrase('_VIEW_THIS_PAGE_IN_LANG', false, 'zenario_language_picker', $langId);
						$this->langs[$langId] = $lang;
					}
				}
			}
		}
		
		//Always show the "choose your language" text
		//(Did there used to be some logic here to hide this?!?)
		$this->sections['Prompt_Phrase_Section'] = true;
		
		//Don't show the picker if there were no links!
		return !empty($this->langs);
	}
	
	
	function showSlot()	{
		if (!empty($this->langs)) {
			
			//Loop through each language, generate a link.
			//This is done in showSlot() and not init() to give other plugins time to register GET requests.
			foreach ($this->langs as $langId => &$lang) {
				$lang['link'] = linkToItem(
					$lang['cID'], $lang['cType'], false, '', $lang['alias'],
					$autoAddImportantRequests = true, false,
					$lang['equivId'], $langId);
			}
			
			if ($this->frameworkIsTwig()) {
				$this->sections['Languages'] = $this->langs;
				$this->twigFramework($this->sections);
			
			} else {
				$this->frameworkHead('Outer', 'Selection_List_Entry_Current_Language', array(), $this->sections);
					foreach ($this->langs as $langId => &$lang) {
						
						$section = $lang['current_language']?
							'Selection_List_Entry_Current_Language'
						 :	'Selection_List_Entry_Other_Languages';
						
						$lang['Flag_Lang'] = htmlspecialchars($lang['flag']);
						$lang['Language_Link'] = htmlspecialchars($lang['link']);
						$lang['Language_Name'] = htmlspecialchars($lang['name']);
						
						$this->framework($section, $lang);
					}
				$this->frameworkFoot('Outer', 'Selection_List_Entry_Current_Language', array(), $this->sections);
			}
		}
	}
	
	
}


