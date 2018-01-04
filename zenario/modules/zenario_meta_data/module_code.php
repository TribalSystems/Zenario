<?php
/*
 * Copyright (c) 2018, Tribal Limited
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
			$dates = getRow('content_item_versions', array('publication_date'), array('id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion));
			if ($this->mergeFields['Date'] = formatDateNicely($dates['publication_date'], $this->setting('date_format'))) {
				$this->showSections['show_date'] = true;
			}
		}
		if ($this->setting('show_writer_name')){
			if ($writerName = getRow('content_item_versions', 'writer_name', array('id'=>$this->cID, 'type'=>$this->cType, 'version'=>$this->cVersion))){
				$this->mergeFields['Writer_name'] = htmlspecialchars($writerName);
				$this->showSections['show_writer_name'] = true;
			}
		}
		if ($this->setting('show_writer_image')){
			$sql = '
				SELECT f.id, f.width, f.height, f.alt_tag
				FROM '.DB_NAME_PREFIX.'content_item_versions v
				INNER JOIN '.DB_NAME_PREFIX.'admins a
					ON v.writer_id = a.id
				INNER JOIN '.DB_NAME_PREFIX.'files f
					ON a.image_id = f.id
				WHERE v.id = '.(int)$this->cID.'
					AND v.type = "'.sqlEscape($this->cType).'"
					AND v.version = '.(int)$this->cVersion;
			$result = sqlSelect($sql);
			$file = sqlFetchAssoc($result);
			if (!empty($file)) {
				$width = $height = $url = false;
				Ze\File::imageLink($width, $height, $url, $file['id'], $this->setting('width'), $this->setting('height'), $this->setting('canvas'), $this->setting('offset'));
				if ($this->mergeFields['Writer_Src'] = $url) {
					$this->mergeFields['Writer_Alt'] = $file['alt_tag'];
					$this->showSections['show_writer_image'] = true;
				}
			}
		}
		if ($this->setting('show_sticky_image')) {
			$sql = '
				SELECT f.id, f.alt_tag
				FROM '.DB_NAME_PREFIX.'content_item_versions v
				INNER JOIN '.DB_NAME_PREFIX.'files f
					ON v.feature_image_id = f.id
				WHERE v.id = '.(int)$this->cID.'
					AND v.type = "'.sqlEscape($this->cType).'"
					AND v.version = '.(int)$this->cVersion;
			$result = sqlSelect($sql);
			$file = sqlFetchAssoc($result);
			if (!empty($file)) {
				$width = $height = $url = false;
				Ze\File::imageLink($width, $height, $url, $file['id'], $this->setting('sticky_image_width'), $this->setting('sticky_image_height'), $this->setting('sticky_image_canvas'), $this->setting('sticky_image_offset'));
				if ($this->mergeFields['Sticky_Image_Src'] = $url) {
					$this->mergeFields['Sticky_Image_Alt'] = $file['alt_tag'];
					$this->showSections['show_sticky_image'] = true;
				}
			}
		}
		
		if ($this->setting('show_title')){
			if ($this->mergeFields['Title'] = htmlspecialchars(cms_core::$pageTitle)){
				$this->showSections['show_title'] = true;
			}
		}
		if ($this->setting('show_description')){
			if ($this->mergeFields['Description'] = htmlspecialchars(cms_core::$pageDesc)){
				$this->showSections['show_description'] = true;
			}
		}
		if ($this->setting('show_summary')){
			$row = getRow('content_item_versions',array('content_summary'),array('id'=>$this->cID,'version'=>$this->cVersion,'type'=>$this->cType));
			if ($this->mergeFields['Summary'] = $row['content_summary']){
				$this->showSections['show_summary'] = true;
			}
		}	
		if ($this->setting('show_keywords')){
			if ($this->mergeFields['Keywords'] = htmlspecialchars(cms_core::$pageKeywords)){
				$this->showSections['show_keywords'] = true;
			}
		}	
		if ($this->setting('show_language')){
			if ($this->mergeFields['Language'] = htmlspecialchars(cms_core::$langId)){
				$this->showSections['show_language'] = true;
			}
		}	
		
		if ($this->setting('show_language_name')) {
			$this->mergeFields['Language_name'] = getLanguageLocalName();
			$this->showSections['show_language_name'] = true;
		}
		
		if ($this->setting('show_categories') && is_array($itemCats = getContentItemCategories($this->cID, $this->cType, true))) {
			$this->showSections['show_categories'] = true;
			$this->showSections['categories'] = array();
			
			$c = -1;
			foreach($itemCats as $cat) {
				++$c;
				$section = array('Category' => htmlspecialchars($cat['public_name']));
				
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
				$fields['first_tab/date_format']['hidden'] = !$values['first_tab/show_date'];
				
				$hidden = !$values['first_tab/show_writer_image'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden);
				
				$hidden = !$values['first_tab/show_sticky_image'];
				$this->showHideImageOptions($fields, $values, 'first_tab', $hidden, 'sticky_image_');
				break;
		}
	}
	
}
