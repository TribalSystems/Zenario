<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

class zenario_language_picker extends ze\moduleBaseClass {
	
	var $sections = [];
	var $langs = [];
	
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = $this->setting('destination') != 'home', $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		$useEquivs = $this->setting('destination') != 'home';
		$useHomepage = $this->setting('destination') != 'equiv_only';
		
		$adminMode = ze::isAdmin();
		
		$equivs = [];
		if ($useEquivs) {
			$equivs = ze\sql::fetchAssocs(
				'SELECT id, status, alias, language_id
				FROM '. DB_PREFIX. 'content_items
				WHERE equiv_id = '. (int) ze::$equivId. '
				  AND `type` = \''. ze\escape::asciiInSQL(ze::$cType). '\'',
				'language_id'
			);
		}
		
		
		//Loop through all of the languages enabled on this site, adding the details of each to an array
		foreach (ze\lang::getLanguages() as $langId => $langCfg) {
			
			$lpl = $langCfg['language_picker_logic'];
			
			if ($lpl === 'always_hidden') {
				continue;
			}
			
			$lang = [
				'cID' => false,
				'cType' => false,
				'flag' => $langCfg['flag'],
				'name' => $langCfg['language_local_name'],
				'request' => '',
				'current_language' => $langId == ze::$visLang
			];
			
			$alias = false;
			$isPlaceholder = false;
			
			//Unless we should always link to the homepage, try to look up an equivalent content item of the current content item
			//in the language specified. If we find one, use this instead of the homepage link.
			if ($useEquivs
			 && (($equiv = $equivs[$langId] ?? false)
			  || (ze\content::showUntranslatedContentItems($langId) && ($equiv = $equivs[ze::$defaultLang] ?? false) && ($isPlaceholder = true)))
			 && ($adminMode || ze\content::isPublished($equiv['status']))) {
				
				$lang['cID'] = $equiv['id'];
				$lang['cType'] = ze::$cType;
				$lang['alias'] = $equiv['alias'];
				$lang['equivId'] = ze::$equivId;
				
				if ($isPlaceholder) {
					$lang['request'] = ['visLang' => $langId];
				}
			
			} elseif ($useHomepage) {
				//Look up the cID of the homepage in each language and note that down as well
				ze\content::langSpecialPage('zenario_home', $lang['cID'], $lang['cType'], $langId, true);
				$lang['equivId'] = ze::$homeEquivId;
			}
			
			if (!$lang['cID'] && $lpl === 'visible_or_hidden') {
				continue;
			}
			
			$lang['View_This_Page_In_Lang'] = ze\lang::phrase('View this page in English', false, 'zenario_language_picker', $langId);
			$this->langs[$langId] = $lang;
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
				if ($lang['cID']) {
					$lang['link'] = ze\link::toItem(
						$lang['cID'], $lang['cType'], false, $lang['request'] ?? '', $lang['alias'] ?? false,
						$autoAddImportantRequests = true, false,
						$lang['equivId'], $langId);
				}
			}
			
			$this->sections['Languages'] = $this->langs;
			$this->twigFramework($this->sections);
		}
	}
	
	
}


