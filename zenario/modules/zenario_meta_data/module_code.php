<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


class zenario_meta_data extends module_base_class {
	
	var $mergeFields = array();
	var $showSections = array();
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = (bool) $this->setting('show_categories'), $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		return true;
	}
	
	//The showSlot method is called by the CMS, and displays the Plugin on the page
	function showSlot() {
		$this->getContentItemMetaData();
		$this->framework('Outer',$this->mergeFields,$this->showSections);
	}
	
	
	//Attempt to look up the publication_date column from the database.
	function getContentItemMetaData() {
		if ($this->setting('show_date') && $this->setting('date_format')){
			$dates = getRow('versions', array('publication_date'), array('id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion));
			if ($this->mergeFields['Date'] = formatDateNicely($dates['publication_date'], $this->setting('date_format'))) {
				$this->showSections['show_date'] = true;
			}
		}
		if ($this->setting('show_writer_name')){
			if ($writerName = getRow('versions', 'writer_name', array('id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion))){
				$this->mergeFields['Writer_name'] = htmlspecialchars($writerName);
				$this->showSections['show_writer_name'] = true;
			}
		}
		if ($this->setting('show_title')){
			if ($this->mergeFields['Title'] = htmlspecialchars(cms_core::$pageTitle)){
				$this->showSections['show_title'] = true;
			}
		}
		if ($this->setting('show_description')){
			if ($this->mergeFields['Description'] = htmlspecialchars(cms_core::$description)){
				$this->showSections['show_description'] = true;
			}
		}
		if ($this->setting('show_summary')){
			$row = getRow('versions',array('content_summary'),array('id'=>$this->cID,'version'=>$this->cVersion,'type'=>$this->cType));
			if ($this->mergeFields['Summary'] = $row['content_summary']){
				$this->showSections['show_summary'] = true;
			}
		}	
		if ($this->setting('show_keywords')){
			if ($this->mergeFields['Keywords'] = htmlspecialchars(cms_core::$keywords)){
				$this->showSections['show_keywords'] = true;
			}
		}	
		if ($this->setting('show_language')){
			if ($this->mergeFields['Language'] = htmlspecialchars(cms_core::$langId)){
				$this->showSections['show_language'] = true;
			}
		}	
		
		if ($this->setting('show_language_name')) {
			if ($this->mergeFields['Language_name'] = getLanguage(cms_core::$langId)) {
				$this->mergeFields['Language_name'] = $this->mergeFields['Language_name']['language_local_name'];
				$this->showSections['show_language_name'] = true;
			}
		}
		
		if ($this->setting('show_categories') && is_array($itemCats = getContentItemCategories($this->cID, $this->cType, true))) {
			$this->showSections['show_categories'] = true;
			$this->showSections['categories'] = array();
			
			$c = -1;
			foreach($itemCats as $cat) {
				++$c;
				$section = array('Category' => htmlspecialchars($cat['public_name']));
				
				if ($cat['landing_page_equiv_id'] && $cat['landing_page_content_type']) {
					langEquivalentItem($cat['landing_page_equiv_id'], $cat['landing_page_content_type']);
					$section['Category_Link'] = $this->linkToItemAnchor($cat['landing_page_equiv_id'], $cat['landing_page_content_type']);
				}
				
				$this->showSections['categories'][] = $section;
			}
			
			$comma = $this->phrase('_COMMA');
			foreach ($this->showSections['categories'] as $i => &$section) {
				if ($i != $c) {
					$section['Comma'] = $comma;
				}
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
		        $box['tabs']['first_tab']['fields']['date_format']['hidden'] = 
		        		!(arrayKey($values,'first_tab/show_date'));
				break;
		}
	}
	
}
