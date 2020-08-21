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

class zenario_search_entry_box_predictive_probusiness extends zenario_search_results_pro {
	
	
	
	
	
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		
		if ($this->methodCallIs('showRSS')) {
			//Adding this line would allow predictive searches to be cached.
			//$this->registerGetRequest('searchString');
		} else {
			$this->callScript('zenario_search_entry_box_predictive_probusiness', 'autocomplete', $this->containerId, $this->showRSSLink(), $this->setting('dropdown_position'));
		}
		
		
		return zenario_search_results::init();
	}
	
	
	//From the search entry box
	
	function showSlot() {
		$this->sections = [];
		$this->mergeFields = [];
		
		$cID = $cType = $state = false;
		if ($this->setting('use_specific_search_results_page') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page')) {
		
		} else {
			ze\content::pluginPage($cID, $cType, $state, 'zenario_search_results');
		}
		
		if (!$cID) {
			if (ze\priv::check()) {
				echo
					'<p class="error">',
						ze\admin::phrase('To show a search entry box, you must either publish the <em>Search</em> special page, or enter a search page in the plugin settings.'),
					'</p>';
			}
			
			return;
		}
		
		if ($cID == $this->cID && $cType == $this->cType) {
			$cID = $cType = false;
		}
		
		if ($this->setting('search_label')) {
			$this->sections['Search_Label'] = true;
		}
		
		if ($this->setting('search_placeholder')) {
			$this->sections['Placeholder'] = true;
			$this->sections['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
		}
		
		$this->drawSearchBox($cID, $cType);
		
		$this->framework('Search_Box', $this->mergeFields, $this->sections);

	}
	
	

	public function showRSS() {
		//header('Content-type: application/json');
		header("Content-type: text/javascript; charset=UTF-8");
		
		$output = [];
		
		if ($this->searchString) {
			$this->doSearch($this->searchString);
			
			foreach ($this->results as &$resultType) {
				if (isset($resultType['search_results']) && is_array($resultType['search_results'])) {
					foreach ($resultType['search_results'] as &$result) {
						$output[] = [
							'value' => htmlspecialchars_decode($result['title']),
							'label' => $result['title']. ($result['description']? '<br/><small>'. $result['description']. '</small>' : ''),
							'url' => htmlspecialchars_decode($result['url'])];
					}
				}
			}
		}
		
		echo json_encode($output);
	}
	
	function launchAJAXSearch() {
		$this->mergeFields['Search_Submit'] = "
			var searchSlot;
			if ((window.zenario_search_results && (searchSlot = zenario_search_results.slotName()))
			 || (window.zenario_search_results_pro && (searchSlot = zenario_search_results_pro.slotName()))) {
				zenario.refreshPluginSlot(
					searchSlot,
					'lookup',
					'&searchString=' + encodeURIComponent(ze::get('". $this->mergeFields['Search_Field_ID']. "').value));
				return false;
			}";
	}
	
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if ($box['tabs']['first_tab']['fields']['search_placeholder'] == true
					&& empty($box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'])) {
					$box['tabs']['first_tab']['fields']['search_placeholder_phrase']['value'] = 'Search the site';
				}

				break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if ($values['first_tab/page_size'] == 'maximum_of' && $values['first_tab/maximum_results_number'] < 0) {
			$box['tabs']['first_tab']['fields']['maximum_results_number']['error'] = ze\admin::phrase('The page size cannot be a negative number.');
		} elseif ($values['first_tab/page_size'] == 'maximum_of' && $values['first_tab/maximum_results_number'] > 999) {
			$box['tabs']['first_tab']['fields']['maximum_results_number']['error'] = ze\admin::phrase('The page size cannot exceed 999.');
		}
	}
	
	
	
	
	
	
	protected function setSearchFields() {
		zenario_search_results::setSearchFields();
		/*
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
		*/
	}
	
	
	//public function showSlot() {
		/*if ($this->searchString) {
			$this->sections['Search_Result_Tabs'] = true;
		}
		*/
		//zenario_search_results::showSlot();
	//}
	
	function doSearch($searchTerm) {
		zenario_search_results::doSearch($searchTerm);
		/*
		$this->sections['Search_Result_Tab'] = [];
		foreach($this->fields as $cType => $fields) {
			$this->sections['Search_Result_Tab'][$cType] = [
				'Tab_On' => $this->results[$cType]['Tab_On'],
				'Tab_Onclick' => $this->results[$cType]['Tab_Onclick'],
				'Type' => $this->phrase('_'. $cType),
				'Record_Count' =>$this->results[$cType]['Record_Count']
			];
		}
		*/
	}
	
	protected function searchContent($cType, $fields, $onlyShowFirstPage = true) {
		return zenario_search_results::searchContent($cType, $fields, $onlyShowFirstPage);
	}
	
	/*
	protected function getLanguagesSQLFilter(){
		if ($this->setting('search_in_languages')=='all') {
			return '';
		} else {
			return zenario_search_results::getLanguagesSQLFilter();
		}
	}
	*/

}
