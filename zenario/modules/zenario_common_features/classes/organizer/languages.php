<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_common_features__organizer__languages extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {

	
			case 'zenario__content/panels/languages':
				//Check if a specific Content Type has been set
				if (get('refiner__content_type')) {
					$panel['key']['cType'] = get('refiner__content_type');
				} elseif (get('refiner__template')) {
					$panel['key']['cType'] = getRow('layouts', 'content_type', get('refiner__template'));
				}
		
				break;

			
			case 'zenario__languages/panels/languages':
			
				if ($refinerName == 'plugin') {
					$panel['db_items']['table'] = '
								[[DB_NAME_PREFIX]]languages AS l
							LEFT JOIN 
								[[DB_NAME_PREFIX]]visitor_phrases AS vp
							ON
								l.id = vp.language_id
							LEFT JOIN 
								[[DB_NAME_PREFIX]]modules pl
							ON 
								vp.module_class_name=pl.class_name';
	
					$panel['db_items']['id_column'] = 'l.id';
	
	
					foreach ($panel['columns'] as &$column) {
						if (trim(arrayKey($column, 'db_column')) == 'vp.language_id') {
							$column['db_column'] = 'l.id';
						}
					}
	
					$panel['columns']['phrase_count']['db_column'] = 'COUNT(DISTINCT IF (NOT [[REFINER__PLUGIN]] OR pl.id=[[REFINER__PLUGIN]],vp.code,NULL))';
					unset($panel['view_content']);

				} elseif (($atLeastOneLanguageEnabled = checkRowExists('languages', array())) && $refinerName != 'not_enabled') {
					$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_at_least_one_language_enabled'];
				
				} else {
					unset($panel['view_content']);
					unset($panel['item_buttons']['delete']);
					unset($panel['collection_buttons']['add']);
					$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_languages_enabled'];
	
					if (!$atLeastOneLanguageEnabled) {
						$panel['title'] = adminPhrase('Enable a Language');
						$panel['popout_message'] =
							'<!--Message_Type:Warning-->'.
							adminPhrase(
<<<_text
<p>Zenario needs at least one Language to be enabled in order to run.</p>

<p>Please select a Language Pack from the panel - click it once and then click the &quot;Enable Language&quot; button.</p>

<p>If the language you require is not shown, you can create it by clicking on the button &quot;Create a Custom Language&quot;.</p>
_text
						);
	
					} else {
						$panel['title'] = adminPhrase('Enable another Language');
					}
				}

				if (in($mode, 'select', 'quick')) {
					unset($panel['popout_message']);
				}	
				
				
				break;


		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			
			case 'zenario__content/panels/languages':
				if (get('refiner__template')) {
					$details = getRow('layouts', array('name', 'content_type'), get('refiner__template'));
					$panel['title'] = adminPhrase('Content using the Layout "[[name]]"', $details);
					$panel['no_items_message'] = adminPhrase('There is no Content using the Layout "[[name]]."', $details);
	
					foreach ($panel['items'] as $id => &$item) {
						$sql = "
							SELECT COUNT(*)
							FROM ". DB_NAME_PREFIX. "content_items AS c
							INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
							   ON v.id = c.id
							  AND v.type = c.type
							  AND v.version = c.admin_version
							  AND v.layout_id = ". (int) get('refiner__template'). "
							WHERE c.language_id = '". sqlEscape($id). "'
							  AND c.status NOT IN ('trashed','deleted')
							  AND c.type = '". sqlEscape($details['content_type']). "'";
		
						$result = sqlQuery($sql);
						$row = sqlFetchRow($result);
						$item['item_count'] = $row[0];
					}

				} elseif (get('refiner__content_type')) {
					$mrg = array(
						'ctype' => getContentTypeName(get('refiner__content_type')));
					$panel['title'] = adminPhrase('[[ctype]] content items', $mrg);
					$panel['no_items_message'] = adminPhrase('There are no [[ctype]] content items.', $mrg);
					
					foreach ($panel['items'] as $id => &$item) {
						$item['item_count'] = selectCount('content_items', array(
							'language_id' => $id,
							'status' => array('!1' => 'trashed', '!2' => 'deleted'),
							'type' => get('refiner__content_type')
						));
					}

				} else {
					unset($panel['allow_bypass']);
	
					if (!$refinerName) {
						foreach ($panel['items'] as $id => &$item) {
							$item['item_count'] = selectCount('content_items', array(
								'language_id' => $id,
								'status' => array('!1' => 'trashed', '!2' => 'deleted')
							));
						}
		
						//Count how many Content Equivalences exist in total
						$sql = "
							SELECT COUNT(DISTINCT equiv_id, type)
							FROM ". DB_NAME_PREFIX. "content_items
							WHERE status NOT IN ('trashed','deleted')";
						$result = sqlQuery($sql);
						$row = sqlFetchRow($result);
						$totalEquivs = $row[0];
		
						foreach ($panel['items'] as $id => &$item) {
							$item['item_count'] .= ' / '. $totalEquivs;
						}
					}
				}

				if (empty($panel['items']) && !checkRowExists('languages', array())) {
					foreach ($panel['collection_buttons'] as &$button) {
						$button['hidden'] = true;
					}
					$panel['no_items_message'] = adminPhrase('No Languages have been enabled. You must enable a Language before creating any Content Items.');

				}
			
				break;
				
			
			case 'zenario__languages/panels/languages':
				if ($mode != 'xml') {
			
					$enabledCount = 0;
					foreach ($panel['items'] as $id => &$item) {
						//If we're looking up a Language Name, we can't rely on the formatting that Storekeeper provides and must use the actual Language Name
						$item['name'] = getLanguageName($id, $addIdInBracketsToEnd = true);
						if (!$item['enabled']) {
							$item['traits'] = array('not_enabled' => true);
				
						} else {
							$item['traits'] = array('enabled' => true);
							++$enabledCount;
					
							if (allowDeleteLanguage($id)) {
								$item['traits']['can_delete'] = true;
							}
					
							$cID = $cType = false;
							if (langSpecialPage('zenario_home', $cID, $cType, $id, true)) {
								$item['frontend_link'] = linkToItem($cID, $cType, false, 'zenario_sk_return=navigation_path');
								$item['homepage_id'] = $cType. '_'. $cID;
								$item['traits']['has_homepage'] = true;
							}
						}
					}
			
					if ($enabledCount < 2) {
						unset($panel['collection_buttons']['default_language']);
					}
			
					//If a language specific domain is in use, show that column by default. Otherwise hide it.
					$langSpecificDomainsUsed = checkRowExists('languages', array('domain' => array('!' => '')));
					if ($langSpecificDomainsUsed) {
						$panel['columns']['domain']['show_by_default'] = true;
					}
			
			
			
					$maxEnabledLanguageCount = siteDescription('max_enabled_languages');
					$enabledLanguages = getLanguages();
					if ($maxEnabledLanguageCount && (count($enabledLanguages) >= $maxEnabledLanguageCount)) {
						if ($maxEnabledLanguageCount == 1) {
							unset($panel['collection_buttons']['create']);
						}
						if (isset($panel['collection_buttons']['add'])) {
							$panel['collection_buttons']['add']['css_class'] = '';
							$panel['collection_buttons']['add']['disabled'] = true;
							if ($maxEnabledLanguageCount == 1) {
								$message = adminPhrase('
								<p>
									This Community CMS allows one language per site. To make this site multi-lingual, please upgrade to Pro. 
								</p><p>
									Otherwise you will need to reset your site if you want to change to a different language.
								</p>'
								);
							} else {
								$message = adminPhrase('The maximun number of enabled languages on this site is [[count]]', array('count' => $maxEnabledLanguageCount));
							}
							$panel['collection_buttons']['add']['disabled_tooltip'] = $message;
						}
						unset($panel['item_buttons']['add_language']);
					}
				}
				
				// When picking a language in select mode hide unnecessary columns
				if ($mode === 'select') {
					
					$columnsVisibleInSelectMode = array('name', 'language_local_name');
					
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
				if (post('delete') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
					$sql = "
						DELETE
						FROM " . DB_NAME_PREFIX . "visitor_phrases
						WHERE language_id = '" . sqlEscape($_POST['id']) . "'
						  AND '" . sqlEscape($_POST['id']) . "' NOT IN (
							SELECT id
							FROM " . DB_NAME_PREFIX . "languages
						)";
					sqlQuery($sql);
				}
		
				// Enable a language
				if (post('enable_language') && post('id')) {
					echo '<!--Open_Admin_Box:zenario_setup_language//' . post('id') . '-->';
				}
		
				break;
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}
