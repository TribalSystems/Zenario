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


class zenario_common_features__organizer__languages extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {

	
			case 'zenario__content/panels/languages':
				//Check if a specific Content Type has been set
				if ($_GET['refiner__content_type'] ?? false) {
					$panel['key']['cType'] = $_GET['refiner__content_type'] ?? false;
				} elseif ($_GET['refiner__template'] ?? false) {
					$panel['key']['cType'] = ze\row::get('layouts', 'content_type', ($_GET['refiner__template'] ?? false));
				}
		
				break;

			
			case 'zenario__languages/panels/languages':
			
				if ($refinerName == 'plugin') {
					$panel['db_items']['table'] = '
								[[DB_PREFIX]]languages AS l
							LEFT JOIN 
								[[DB_PREFIX]]visitor_phrases AS vp
							ON
								l.id = vp.language_id
							LEFT JOIN 
								[[DB_PREFIX]]modules pl
							ON 
								vp.module_class_name=pl.class_name';
	
					$panel['db_items']['id_column'] = 'l.id';
	
	
					foreach ($panel['columns'] as &$column) {
						if (trim($column['db_column'] ?? false) == 'vp.language_id') {
							$column['db_column'] = 'l.id';
						}
					}
	
					$panel['columns']['phrase_count']['db_column'] = 'COUNT(DISTINCT IF (NOT [[REFINER__PLUGIN]] OR pl.id=[[REFINER__PLUGIN]],vp.code,NULL))';
					unset($panel['view_content']);

				} elseif (($atLeastOneLanguageEnabled = ze\row::exists('languages', [])) && $refinerName != 'not_enabled') {
					$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_at_least_one_language_enabled'];
				
				} else {
					unset($panel['view_content']);
					unset($panel['item_buttons']['delete']);
					unset($panel['collection_buttons']['add']);
					$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_languages_enabled'];
	
					if (!$atLeastOneLanguageEnabled) {
						$panel['title'] = ze\admin::phrase('Enable a language');
						$panel['popout_message'] =
							'<!--Message_Type:Warning-->'.
							ze\admin::phrase(
<<<_text
<p>Zenario needs at least one language to be enabled in order to run.</p>

<p>Please select a language from the panel: with it selected, click the &quot;Enable this language&quot; button.</p>

<p>The panel shows many languages, but if the language you require is not shown, you can create it by clicking on the button &quot;Define a language&quot;.</p>
_text
						);
	
					} else {
						$panel['title'] = ze\admin::phrase('Enable another language');
					}
				}

				if (ze::in($mode, 'select', 'quick')) {
					unset($panel['popout_message']);
				}	
				
				
				break;


		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			
			case 'zenario__content/panels/languages':
				if ($_GET['refiner__template'] ?? false) {
					$details = ze\row::get('layouts', ['name', 'content_type'], ($_GET['refiner__template'] ?? false));
					$panel['title'] = ze\admin::phrase('Content using the Layout "[[name]]"', $details);
					$panel['no_items_message'] = ze\admin::phrase('There is no Content using the Layout "[[name]]."', $details);
	
					foreach ($panel['items'] as $id => &$item) {
						$sql = "
							SELECT COUNT(*)
							FROM ". DB_PREFIX. "content_items AS c
							INNER JOIN ". DB_PREFIX. "content_item_versions AS v
							   ON v.id = c.id
							  AND v.type = c.type
							  AND v.version = c.admin_version
							  AND v.layout_id = ". (int) ($_GET['refiner__template'] ?? false). "
							WHERE c.language_id = '". ze\escape::asciiInSQL($id). "'
							  AND c.status NOT IN ('trashed','deleted')
							  AND c.type = '". ze\escape::asciiInSQL($details['content_type']). "'";
		
						$result = ze\sql::select($sql);
						$row = ze\sql::fetchRow($result);
						$item['item_count'] = $row[0];
					}

				} elseif ($_GET['refiner__content_type'] ?? false) {
					$mrg = [
						'ctype' => ze\content::getContentTypeName($_GET['refiner__content_type'] ?? false)];
					$panel['title'] = ze\admin::phrase('[[ctype]] content items', $mrg);
					$panel['no_items_message'] = ze\admin::phrase('There are no [[ctype]] content items.', $mrg);
					
					foreach ($panel['items'] as $id => &$item) {
						$item['item_count'] = ze\row::count('content_items', [
							'language_id' => $id,
							'status' => ['!1' => 'trashed', '!2' => 'deleted'],
							'type' => ($_GET['refiner__content_type'] ?? false)
						]);
					}

				} else {
					unset($panel['allow_bypass']);
	
					if (!$refinerName) {
						foreach ($panel['items'] as $id => &$item) {
							$item['item_count'] = ze\row::count('content_items', [
								'language_id' => $id,
								'status' => ['!1' => 'trashed', '!2' => 'deleted']
							]);
						}
		
						//Count how many Content Equivalences exist in total
						$sql = "
							SELECT COUNT(DISTINCT equiv_id, type)
							FROM ". DB_PREFIX. "content_items
							WHERE status NOT IN ('trashed','deleted')";
						$result = ze\sql::select($sql);
						$row = ze\sql::fetchRow($result);
						$totalEquivs = $row[0];
		
						foreach ($panel['items'] as $id => &$item) {
							$item['item_count'] .= ' / '. $totalEquivs;
						}
					}
				}

				if (empty($panel['items']) && !ze\row::exists('languages', [])) {
					foreach ($panel['collection_buttons'] as &$button) {
						$button['hidden'] = true;
					}
					$panel['no_items_message'] = ze\admin::phrase('No languages have been enabled. You must enable at least one language before creating any content items.');

				}
			
				break;
				
			
			case 'zenario__languages/panels/languages':
				if ($mode != 'xml') {
			
					$enabledCount = 0;
					foreach ($panel['items'] as $id => &$item) {
						//If we're looking up a Language Name, we can't rely on the formatting that Storekeeper provides and must use the actual Language Name
						$item['name'] = ze\lang::name($id, $addIdInBracketsToEnd = true);
						if (!$item['enabled']) {
							$item['traits'] = ['not_enabled' => true];
				
						} else {
							$item['traits'] = ['enabled' => true];
							++$enabledCount;
					
							if (ze\contentAdm::allowDeleteLanguage($id)) {
								$item['traits']['can_delete'] = true;
							}
					
							$cID = $cType = false;
							if (ze\content::langSpecialPage('zenario_home', $cID, $cType, $id, true)) {
								$item['frontend_link'] = ze\link::toItem($cID, $cType, false, 'zenario_sk_return=navigation_path');
								$item['homepage_id'] = $cType. '_'. $cID;
								$item['traits']['has_homepage'] = true;
							}
						}
					}
			
					if ($enabledCount < 2) {
						unset($panel['collection_buttons']['default_language']);
					}
			
					//If a language specific domain is in use, show that column by default. Otherwise hide it.
					$langSpecificDomainsUsed = ze\row::exists('languages', ['domain' => ['!' => '']]);
					if ($langSpecificDomainsUsed) {
						$panel['columns']['domain']['show_by_default'] = true;
					}
			
			
			
					$maxEnabledLanguageCount = ze\site::description('max_enabled_languages');
					$enabledLanguages = ze\lang::getLanguages();
					if ($maxEnabledLanguageCount && (count($enabledLanguages) >= $maxEnabledLanguageCount)) {
						if ($maxEnabledLanguageCount == 1) {
							unset($panel['collection_buttons']['create']);
						}
						if (isset($panel['collection_buttons']['add'])) {
							$panel['collection_buttons']['add']['css_class'] = '';
							$panel['collection_buttons']['add']['disabled'] = true;
							if ($maxEnabledLanguageCount == 1) {
								$message = ze\admin::phrase('
								<p>
									This Community CMS allows one language per site. To make this site multi-lingual, please upgrade to Pro. 
								</p><p>
									Otherwise you will need to reset your site if you want to change to a different language.
								</p>'
								);
							} else {
								$message = ze\admin::phrase('The maximun number of enabled languages on this site is [[count]]', ['count' => $maxEnabledLanguageCount]);
							}
							$panel['collection_buttons']['add']['disabled_tooltip'] = $message;
						}
						unset($panel['item_buttons']['add_language']);
					}
				}
				
				// When picking a language in select mode hide unnecessary columns
				if ($mode === 'select') {
					
					$columnsVisibleInSelectMode = ['name', 'language_local_name'];
					
					foreach ($panel['columns'] as $name => $column) {
						if (!in_array($name, $columnsVisibleInSelectMode)) {
							unset($panel['columns'][$name]);
						} else {
							$panel['columns'][$name]['hidden'] = false;
						}
					}
				}
				
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {

	
			case 'zenario__languages/panels/languages':
				if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
					$sql = "
						DELETE
						FROM " . DB_PREFIX . "visitor_phrases
						WHERE language_id = '" . ze\escape::asciiInSQL($_POST['id']) . "'
						  AND '" . ze\escape::asciiInSQL($_POST['id'] ?? '') . "' NOT IN (
							SELECT id
							FROM " . DB_PREFIX . "languages
						)";
					ze\sql::update($sql);
				}
		
				// Enable a language
				if (($_POST['enable_language'] ?? false) && ($_POST['id'] ?? false)) {
					echo '<!--Open_Admin_Box:zenario_setup_language//' . ze\escape::hyp($_POST['id'] ?? false) . '-->';
				}
		
				break;
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}
