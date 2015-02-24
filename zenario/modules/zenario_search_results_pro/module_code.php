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

class zenario_search_results_pro extends zenario_search_results {
	
	public function init() {
		
		$defaultTab = $this->setting('search_html')? 'html' : ($this->setting('search_document')? 'document' : 'news');
		$this->cTypeToSearch = ifNull(get('ctab'), $defaultTab);
		
		//$this->registerGetRequest('ctab', $defaultTab);
		
		return zenario_search_results::init();
	}
	
	protected function setSearchFields() {
		zenario_search_results::setSearchFields();
		
		if ($this->setting('search_html')) {
			$this->fields['html'] = $this->fields['%all%'];
		}
		
		if ($this->setting('search_document')) {
			$this->fields['document'] = $this->fields['%all%'];
		}
		
		if ($this->setting('search_news')) {
			$this->fields['news'] = $this->fields['%all%'];
		}
		
		unset($this->fields['%all%']);
	}
	
	
	public function showSlot() {
		if ($this->searchString) {
			$this->sections['Search_Result_Tabs'] = true;
		}
		
		zenario_search_results::showSlot();
	}
	
	function doSearch($searchTerm) {
		zenario_search_results::doSearch($searchTerm);
		
		$this->sections['Search_Result_Tab'] = array();
		foreach($this->fields as $cType => $fields) {
			$this->sections['Search_Result_Tab'][$cType] = array(
				'Tab_On' => $this->results[$cType]['Tab_On'],
				'Tab_Onclick' => $this->results[$cType]['Tab_Onclick'],
				'Type' => $this->phrase('_'. $cType),
				'Record_Count' =>$this->results[$cType]['Record_Count']
			);
		}
	}

	protected function getLanguagesSQLFilter(){
		if ($this->setting('search_in_languages')=='all') {
			return '';
		} else {
			return zenario_search_results::getLanguagesSQLFilter();
		}
	}

}
