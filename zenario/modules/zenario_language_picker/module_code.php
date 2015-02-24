<?php
/*
 * Copyright (c) 2015, Tribal Limited
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
	
	var $links = array();
	var $mergeFields = array();
	var $sections = array();
	
	function init()	{
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = $this->setting('destination') != 'home', $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$langs = array();
				
		foreach (getLanguages() as $langCfg) {
			$langs[$langCfg['id']] = array(
				'cID' => false,
				'cType' => false,
				'equiv' => false,
				'flag' => $langCfg['flag'],
				'name' => $langCfg['language_local_name'],
				'current_language' => $langCfg['id'] == session('user_lang'));
			
			langSpecialPage('zenario_home', $langs[$langCfg['id']]['cID'], $langs[$langCfg['id']]['cType'], $langCfg['id'], true);
		}

		if ($this->setting('destination') != 'home') {
			foreach (equivalences($this->cID, $this->cType) as $langId => $equiv) {
				if (isset($langs[$langId])) {
					$langs[$langId]['cID'] = $equiv['id'];
					$langs[$langId]['cType'] = $equiv['type'];
					$langs[$langId]['equiv'] = true;
				}
			}
		}
		
		foreach ($langs as $langId => &$lang) {
			if ($this->setting('destination') != 'equiv_only' || $lang['equiv']) {
				if ($content = getRow('content', array('status', 'alias'), array('id' => $lang['cID'], 'type' => $lang['cType']))) {
					if (checkPriv() || isPublished($content['status'])) {
						$this->links[] = array(
							'Flag_Lang' => htmlspecialchars($lang['flag']),
							'Language_Link' => htmlspecialchars($this->linkToItem($lang['cID'], $lang['cType'], false, '', $content['alias'])),
							'Language_Name' => htmlspecialchars($lang['name']),
							'View_This_Page_In_Lang' => 
								phrase('_VIEW_THIS_PAGE_IN_LANG', false, 'zenario_language_picker', $langId),
							'section' => $langId == $_SESSION['user_lang']?
											'Selection_List_Entry_Current_Language'
										  : 'Selection_List_Entry_Other_Languages');
					}
				}
			}
		}
		
		$this->sections['Prompt_Phrase_Section'] = true;
	
		return !empty($this->links);
	}
	
	
	function showSlot()	{
		if (!empty($this->links)) {
			$this->frameworkHead('Outer', 'Selection_List_Entry_Current_Language', $this->mergeFields, $this->sections);
				foreach ($this->links as &$link) {
					$this->framework($link['section'], $link);
				}
			$this->frameworkFoot('Outer', 'Selection_List_Entry_Current_Language', $this->mergeFields, $this->sections);
		}
	}
	
	
}


