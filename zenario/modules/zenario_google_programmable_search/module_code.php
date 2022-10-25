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


class zenario_google_programmable_search extends ze\moduleBaseClass {
	
	protected $data = [];
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		$apiKey = ze::setting('google_custom_search_json_api_key');
		if ($apiKey) {
			$this->data['Search_Field_ID'] = 'search_field_' . $this->containerId;
			$this->data['Search_Results_Div_ID'] = 'search_results_'. $this->containerId;
			$this->data['Search_Button_ID'] = 'search_button_'. $this->containerId;
			$this->data['Ajax_link'] = $this->pluginAJAXLink();
			$this->data['Title_Tags'] = $this->setting('title_tags');

			$this->registerGetRequest('page');
			$this->registerGetRequest('searchTerm');

			$scrollToTopOfSlot = false;
			$fadeOutAndIn = true;
			$this->data['onSubmit'] = htmlspecialchars("return zenario.formSubmit(this, " . (bool)$scrollToTopOfSlot . ", " . (bool)$fadeOutAndIn . ", " . ze\escape::js($this->slotName) . ");");
			$this->data['action'] = htmlspecialchars(ze\link::toItem(ze::$cID, ze::$cType, false, '', ze::$alias, true));

			if ($searchTerm = ze::get('searchTerm')) {
				$this->data['searchTerm'] = $searchTerm;

				$customSearchEngineId = $this->setting('custom_search_engine_id');

				$url = ze\link::protocol() .'www.googleapis.com/customsearch/v1?key=' . urlencode($apiKey) . '&cx=' . urlencode($customSearchEngineId) . '&q=' . urlencode($searchTerm) . '&sort=date';
				$page = ze::get('page') ?: 1;

				//Google Custom Search will display 10 results per page.
				//The "start" parameter will be set to 1 on page 1, then to 11 on page 2, and so on.
				$start = null;
				if ($page == 1) {
					$start = 1;
				} elseif ($page > 1) {
					$start = 1 + (($page - 1) * 10);
				}
				
				$noResult = true;

				if ($start) {
					$result = ze\curl::fetch($url . '&start=' . (int) $start);
					
					if ($result) {
						
						$resultDecoded = json_decode($result, true);
						$totalNumOfResults = $resultDecoded['queries']['request'][0]['totalResults'];
						$numPages = ceil($totalNumOfResults / 10);

						if (isset($resultDecoded['items']) && count($resultDecoded['items']) > 0) {
							$noResult = false;
							
							$oddOrEven = 'odd';
							
							foreach ($resultDecoded['items'] as $row) {
								$this->data['Search_Results'][] = [
									'htmlTitle' => $row['htmlTitle'],
									'link' => $row['link'],
									'htmlSnippet' => $row['htmlSnippet'],
									'cse_thumbnail' => $row['pagemap']['cse_thumbnail'][0],
									'oddOrEven' => $oddOrEven
								];

								$oddOrEven = ($oddOrEven == 'even'? 'odd' : 'even');
							}

							$this->data['Search_Result_Title_Tags'] = $this->setting('search_result_title_tags');
							$this->data['showDateAndDescription'] = $this->setting('show_date_and_description');
							$this->data['showIcon'] = $this->setting('show_icon');
						}
					}
				}

				if ($noResult) {
					$this->data['No_Results'] = true;
					$this->data['No_Results_Phrase'] = $this->phrase('No results for "[[search_term]]".', ['search_term' => $searchTerm]);
				} else {
					$pages = [];
					$i = 1;
					while ($i <= $numPages) {
						$pages[$i] = '&searchTerm=' . urlencode($searchTerm) . '&page=' . $i;
						$i++;
					}
					
					$paginationLinks = [];
					if (count($pages) > 1) {
						$this->pagination('pagination_style', $page, $pages, $pagination, $paginationLinks);
					} else {
						$pagination = false;
					}

					$this->data['Pagination'] = $pagination;
				}
			}
		} else {
			if (ze\admin::id()) {
				$googleApiKeySiteSettingLink = ze\link::absolute() . 'zenario/admin/organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tgoogle_custom_search~k{"id"%3A"api_keys"}';
				$linkStart = "<a href='" . $googleApiKeySiteSettingLink . "' target='_blank'>";
				$linkEnd = '</a>';
				$this->data['missingApiKey'] = $this->phrase(
					'Cannot display Google Programmable Search, please set a [[link_start]]Google Custom Search API key[[link_end]].',
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			} else {
				return false;
			}
		}
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (!ze::setting('google_custom_search_json_api_key')) {
			$googleApiKeySiteSettingLink = ze\link::absolute() . 'zenario/admin/organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tgoogle_custom_search~k{"id"%3A"api_keys"}';
			$linkStart = "<a href='" . $googleApiKeySiteSettingLink . "' target='_blank'>";
			$linkEnd = '</a>';
			$fields['first_tab/missing_api_key']['snippet']['html'] = $this->phrase(
				'The Google Programmable Custom Search API key is missing. Please enter it in the [[link_start]]site settings[[link_end]].',
				['link_start' => $linkStart, 'link_end' => $linkEnd]
			);
			$fields['first_tab/missing_api_key']['hidden'] = false;
		}
	}
}