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


class zenario_common_features__organizer__content extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {

		if (in($mode, 'full', 'quick', 'select', 'get_matched_ids')
		 && !isset($_GET['refiner__trash'])
		 && isset($panel['db_items']['custom_where_statement__not_trashed'])) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__not_trashed'];
		}
		
		//Have a refiner that enforces the language filter be set.
		if (isset($_GET['refiner__filter_by_lang'])
		 && !isset($_GET['refiner__zenario_trans__chained_in_link'])) {
			
			//If it's not set, set it to one language initially
			if (!$langIdFilter = columnFilterValue('language_id')) {
				
				//If an item was selected, use the language from that...
				if (request('_item')) {
					$langIdFilter = getRow('content_items', 'language_id', array('tag_id' => request('_item')));
				}
				//...otherwise use the default language
				if (!$langIdFilter) {
					$langIdFilter = setting('default_language');
				}
				
				columnFilterValue('language_id', $langIdFilter);
			}
			unset($panel['quick_filter_buttons']['all_languages']);
		}
		
		//Check if a specific Content Type and/or layout has been set, either by using a content-type refiner or a layout's refiner
		if (get('refiner__template')) {
			$panel['key']['layoutId'] = get('refiner__template');
			$panel['key']['cType'] = getRow('layouts', 'content_type', get('refiner__template'));
		} elseif (get('refiner__content_type')) {
			$panel['key']['cType'] = get('refiner__content_type');
		}
		
		//If this panel doesn't show html content items, or only shows content items in a specific category,
		//skip the quick-create FAB and go straight to the full-create FAB.
		if (isset($panel['collection_buttons']['create'])) {
			if (($panel['key']['cType'] && $panel['key']['cType'] != 'html')
			 || get('refiner__category')) {
				$panel['collection_buttons']['create']['admin_box']['path'] = 'zenario_content';
			}
		}

		//Attempt to customise the defaults slightly depending on the content type
		//These options are only defaults and will be overridden if the Administrator has ever set or changed them.
		if ($panel['key']['cType']) {
			switch ($panel['key']['cType']) {
				case 'news':
					$panel['columns']['title']['show_by_default'] = true;
					$panel['columns']['description']['show_by_default'] = false;
					$panel['columns']['publication_date']['show_by_default'] = true;
					$panel['columns']['inline_files']['show_by_default'] = false;
					$panel['columns']['zenario_trans__links']['show_by_default'] = false;
					$panel['columns']['menu']['show_by_default'] = true;
			
					break;
		
				case 'blog':
					$panel['columns']['publication_date']['show_by_default'] = true;
			
					break;
			}
	
			//Task #9514: Release Date should always be visible if you are looking at a Content Type where it is mandatory.
			if ($details = getContentTypeDetails($panel['key']['cType'])) {
				foreach (array(
					'writer_field' => 'writer_name',
					'description_field' => 'description',
					'keywords_field' => 'keywords',
					//'summary_field' => '...',
					'release_date_field' => 'publication_date'
				) as $fieldName => $columnName) {
		
					if ($details[$fieldName] == 'mandatory') {
						$panel['columns'][$columnName]['always_show'] = true;
		
					} elseif ($details[$fieldName] == 'hidden') {
						$panel['columns'][$columnName]['hidden'] = true;
					}
				}
				
				if ($refinerName == 'content_type') {
					unset($panel['collection_buttons']['help']['hide_on_refiner']);
					$panel['collection_buttons']['help']['help']['message'] =
						adminPhrase('Every page of your website is stored as a "content item". This panel shows all of the [[content_type_plural_lower_en]] of your site in a list view.',
							$details);
				}
			}

		//If this is a panel for multiple content types then we are limited in how much we can customise it.
		//But if any fields are always hidden, we can still hide them
		} else {
			foreach (array(
				'writer_field' => 'writer_name',
				'description_field' => 'description',
				'keywords_field' => 'keywords',
				//'summary_field' => '...',
				'release_date_field' => 'publication_date'
			) as $fieldName => $columnName) {
	
				if (!checkRowExists('content_types', array($fieldName => array('!' => 'hidden')))) {
					$panel['columns'][$columnName]['hidden'] = true;
				}
			}
		}
		
		
		// Create page preview buttons
		if (in($mode, 'full', 'quick', 'select')) {
			$pagePreviews = getRowsArray('page_preview_sizes', array('width', 'height', 'description', 'ordinal', 'is_default'), array(), 'ordinal');
			foreach ($pagePreviews as $pagePreview) {
				$width = $pagePreview['width'];
				$height = $pagePreview['height'];
				$description = $pagePreview['description'];
	
				$pagePreviewButton = array(
					'parent' => 'page_preview_sizes',
					'label' => $width.' × '.$height.', '.$description,
					'onclick' => "zenarioA.showPagePreview(". (int) $width. ", ". (int) $height. ", '". jsEscape($description). "')"
				);
			
				if ($pagePreview['is_default']) {
					$pagePreviewButton['label'] .= ' (Default)';
					 $panel['inline_buttons']['inspect']['custom_width'] = $width;
					 $panel['inline_buttons']['inspect']['custom_height'] = $height;
					 $panel['inline_buttons']['inspect']['custom_description'] = $description;
				}
				$panel['item_buttons']['page_preview_'.$pagePreview['ordinal'].'_'.$width.'x'.$height] = $pagePreviewButton;
			}
		} else {
			unset($panel['item_buttons']['page_preview_sizes']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$numLanguages = count($langs = getLanguages());
		
		//If this panel is for a specific language, don't show the language filter
		//and also set the language id in the key so any FABs that open default to that language.
		if ($panel['key']['language']) {
			$panel['key']['language'] = $panel['key']['language'];
			unset($panel['quick_filter_buttons']['language']);
			unset($panel['quick_filter_buttons']['all_languages']);
		
		//If there is more than one language on this site, show the language filter
		} elseif ($numLanguages > 1 && in($mode, 'full', 'quick', 'select')) {
			
			//Check the current language filter, if there is one
			$langIdFilter = columnFilterValue('language_id');
			
			//For each language, add a filter option
			$ord = 100;
			foreach ($langs as $lang) {
				
				$label = adminPhrase('[[english_name]] ([[id]])', $lang);
				
				$panel['quick_filter_buttons']['lang_'. $lang['id']] = array(
					'ord' => ++$ord,
					'parent' => 'language',
					'column' => 'language_id',
					'label' => $label,
					'value' => $lang['id']
				);
				
				//If the language was chosen, change the text on the parent-button
				//and also set the language id in the key so any FABs that open default to that language.
				if ($langIdFilter == $lang['id']) {
					$panel['quick_filter_buttons']['language']['label'] = $label;
					$panel['key']['language'] = $lang['id'];
				}
			}
		
		//If there is only one language on this site, don't show the language filter
		} else {
			unset($panel['quick_filter_buttons']['language']);
			unset($panel['quick_filter_buttons']['all_languages']);
		}
		
		//If we're showing trashed items, don't show the status filter
		if (isset($_GET['refiner__trash'])
		 || !in($mode, 'full', 'quick', 'select')) {
			unset($panel['quick_filter_buttons']['status']);
			unset($panel['quick_filter_buttons']['any_status']);
			unset($panel['quick_filter_buttons']['first_draft']);
			unset($panel['quick_filter_buttons']['published_with_draft']);
			unset($panel['quick_filter_buttons']['published']);
			unset($panel['quick_filter_buttons']['hidden']);
		
		//Otherwise if the status filter is set, make sure to change the label of the parent to what was chosen
		} else
		 if (($statusFilter = columnFilterValue('status'))
		  && (!empty($panel['quick_filter_buttons'][$statusFilter]['label']))) {
			$panel['quick_filter_buttons']['status']['label'] =
				$panel['quick_filter_buttons'][$statusFilter]['label'];
		}
		
		//If this panel is for a specific layout, don't show the layout filter
		if ($panel['key']['layoutId'] || !in($mode, 'full', 'quick', 'select')) {
			unset($panel['quick_filter_buttons']['layout']);
			unset($panel['quick_filter_buttons']['all_layouts']);
		
		} else {
			$sql = "
				SELECT
					layout_id, name,
					CONCAT('L', IF (layout_id < 10, LPAD(CAST(layout_id AS CHAR), 2, '0'), CAST(layout_id AS CHAR)), ' ', name) AS id_and_name
				FROM ". DB_NAME_PREFIX. "layouts
				WHERE status = 'active'";
			
			if ($panel['key']['cType']) {
				$sql .= "
				  AND content_type = '". sqlEscape($panel['key']['cType']). "'";
			}
			
			$sql .= "
				ORDER BY layout_id";
			
			//Check the current filter, if there is one
			$layoutIdFilter = columnFilterValue('layout_id');
			
			//For each layout, add a filter option
			$ord = 1000;
			$result = sqlSelect($sql);
			while ($layout = sqlFetchAssoc($result)) {
				
				$label = adminPhrase('[[id_and_name]]', $layout);
				
				$panel['quick_filter_buttons']['layout_'. $layout['layout_id']] = array(
					'ord' => ++$ord,
					'parent' => 'layout',
					'column' => 'layout_id',
					'label' => $label,
					'value' => $layout['layout_id']
				);
				
				//If the layout was chosen, change the text on the parent-button
				if ($layoutIdFilter == $layout['layout_id']) {
					$panel['quick_filter_buttons']['layout']['label'] = $label;
				}
				
			}
		}
		
		
		
		
		
		
		if (isset($_GET['refiner__trash']) && !$panel['key']['layoutId']) {
			$panel['title'] = adminPhrase('Trashed content items');
			$panel['no_items_message'] = adminPhrase('There are no trashed content items.');
			unset($panel['columns']['status']);
			unset($panel['collection_buttons']['create']);

		} elseif (get('refiner__following_item_link')) {
			$panel['title'] = adminPhrase('Linked content item');
			unset($panel['collection_buttons']['create']);
			unset($panel['item_buttons']['trash']);
			unset($panel['item_buttons']['delete']);

		} elseif (get('refinerName') == 'find_duplicates') {
			$panel['title'] = adminPhrase('Items with duplicate file attachments');
			$panel['no_items_message'] = adminPhrase('No items with duplicate file attachments found');
			unset($panel['collection_buttons']['diagnostics_dropdown']);
	
			//Attempt to turn off a few columns by default here.
			//These options are only defaults and will be overridden if the Administrator has ever set or changed them.
			foreach ($panel['columns'] as $col_name => &$col) {
				if (is_array($col_name)) {
					$col['show_by_default'] = false;
		
					switch ($col_name) {
						case 'title':
						case 'file_id':
						case 'filename':
						case 'version':
						case 'status':
							$col['always_show'] = false;
					}
				}
			}

		} elseif ($path == 'zenario__content/panels/chained') {
			$cID = $cType = false;
	
			if ($refinerName == 'zenario_trans__chained_in_link') {
				getCIDAndCTypeFromTagId($cID, $cType, $refinerId);
				$panel['return_if_empty'] = true;
	
			} elseif ($refinerName == 'zenario_trans__chained_in_link__from_menu_node' && ($menu = getMenuNodeDetails($refinerId))) {
				$cID = $menu['equiv_id'];
				$cType = $menu['content_type'];
			}
	
			$panel['title'] = adminPhrase('Translations of "[[tag]]"', array('tag' => formatTag($cID, $cType, -1, false, true), 'lang_id' => getContentLang($cID, $cType)));
			$panel['label_format_for_grid_view'] = "[[tag]] \n [[language_id]]";
	
			if (isset($panel['item_buttons']['create_translation'])) {
				$panel['item_buttons']['create_translation']['tooltip'] =
					adminPhrase('Duplicate "[[tag]]" ([[language_id]]) to create a translation in [[lang_name]]', array('tag' => formatTag($cID, $cType), 'language_id' => getContentLang($cID, $cType)));
			}

		} elseif ($panel['key']['layoutId'] && $panel['key']['language']) {
			$mrg = array(
				'template' => getRow('layouts', 'name', $panel['key']['layoutId']),
				'language' => getLanguageName($panel['key']['language']));
			$panel['label_format_for_grid_view'] = '[[tag]]';
			$panel['title'] = adminPhrase('Content items using the layout [[template]] in [[language]]', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using layout [[template]] in [[language]].', $mrg);

		} elseif ($panel['key']['cType'] && $panel['key']['language']) {
			$mrg = array(
				'ctype' => getContentTypeName($panel['key']['cType']),
				'language' => getLanguageName($panel['key']['language']));
			$panel['title'] = adminPhrase('[[ctype]] content items in [[language]]', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no [[ctype]] content items in [[language]].', $mrg);
			$panel['columns']['language_id']['hidden'] = true;
			unset($panel['columns']['type']);

		} elseif ($panel['key']['layoutId']) {
			$mrg = array(
				'template' => getRow('layouts', 'name', $panel['key']['layoutId']));
			$panel['title'] = adminPhrase('Content items using the layout "[[template]]"', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using layout [[template]].', $mrg);

		} elseif ($panel['key']['cType']) {
			$panel['item']['css_class'] = 'content_type_'. $panel['key']['cType'];
			$mrg = getContentTypeDetails($panel['key']['cType']);
			
			$panel['title'] = adminPhrase('[[content_type_plural_en]]', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no [[content_type_plural_lower_en]].', $mrg);
			unset($panel['columns']['type']);

		} elseif (get('refiner__menu_children')) {
			$mrg = array(
				'name' => getMenuName(get('refiner__menu_children'), true));
			$panel['title'] = adminPhrase('Content items under the menu node "[[name]]"', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items under the menu node "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($panel['key']['language']) {
			$mrg = array(
				'language' => getLanguageName($panel['key']['language']));
			$panel['title'] = adminPhrase('Content items in [[language]]', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items in [[language]].', $mrg);
			$panel['columns']['language_id']['hidden'] = true;

		} elseif (get('refiner__category')) {
			$mrg = array(
				'category' => getCategoryName(get('refiner__category')));
			$panel['title'] = adminPhrase('Content items in the category "[[category]]"', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items in the category "[[category]]".', $mrg);

		} elseif (get('refiner__module_usage')) {
			$mrg = array(
				'name' => getModuleDisplayName(get('refiner__module_usage')));
			$panel['title'] = adminPhrase('Content items on which module "[[name]]" is used', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using the module "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (get('refiner__module_effective_usage')) {
			$mrg = array(
				'name' => getModuleDisplayName(get('refiner__module_effective_usage')));
			$panel['title'] = adminPhrase('Content items on which module "[[name]]" is used (effective usage)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using the module "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (get('refiner__plugin_instance_usage')) {
			$mrg = array(
				'name' => getPluginInstanceName(get('refiner__plugin_instance_usage')));
			$panel['title'] = adminPhrase('Content items on which the plugin "[[name]]" is used', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using the plugin "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (get('refiner__plugin_instance_effective_usage')) {
			$mrg = array(
				'name' => getPluginInstanceName(get('refiner__plugin_instance_effective_usage')));
			$panel['title'] = adminPhrase('Content items on which the plugin "[[name]]" appears (effective usage)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no content items using the plugin "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'special_pages') {
			$panel['title'] = adminPhrase('Special Pages for the Default Language ([[lang]])', array('lang' => setting('default_language')));
			$panel['item']['css_class'] = 'special_content_published';
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'your_work_in_progress') {
			$panel['title'] = adminPhrase('Your work in progress');
			$panel['no_items_message'] = adminPhrase('You are not working on any content items.');
			$panel['item']['css_class'] = 'content_draft';
			unset($panel['trash']);
		}


		$panel['columns']['type']['values'] = array();
		foreach (getContentTypes() as $cType) {
			$panel['columns']['type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
		}

		if (!checkPriv('_PRIV_PUBLISH_CONTENT_ITEM') && !checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
			unset($panel['item_buttons']['hidden']['ajax']);
		}

		$contentTypes = getRowsArray('content_types', array('enable_categories'));

		//If this is full, quick or select mode, and the admin looking at this only has permissions
		//to edit specific content items, we'll need to check if the current admin can edit each
		//content item.
		$checkSpecificPerms = in($mode, 'full', 'quick', 'select') && adminHasSpecificPerms();

		foreach ($panel['items'] as $id => &$item) {
			$item['cell_css_classes'] = array();
		
			if ($item['id'] !== null) {
	
				if ($checkSpecificPerms && checkPriv(false, $item['id'], $item['type'])) {
					$item['_specific_perms'] = true;
				}
		
				if ($item['lock_owner_id']) {
					$adminDetails = getAdminDetails($item['lock_owner_id']);
					$item['lock_owner_name'] = $adminDetails['first_name'].' '.$adminDetails['last_name'];
				}
		
				if ($path == 'zenario__content/panels/chained') {
					$panel['key']['equivId'] = $item['equiv_id'];
					$panel['key']['cType'] = $item['type'];
				}
		
		
				$item['css_class'] = getItemIconClass($item['id'], $item['type'], true, $item['status']);
		
				$item['enable_categories'] = $contentTypes[$item['type']]['enable_categories'];
		
				switch (arrayKey($item,'privacy')){
					case 'all_extranet_users':
						$item['cell_css_classes']['privacy'] = 'blue';
						break;
					case 'group_members':
						$item['cell_css_classes']['privacy'] = 'orange';
						break;
					case 'specific_users':
						$item['cell_css_classes']['privacy'] = 'yellow';
						break;
					case 'no_access':
						$item['cell_css_classes']['privacy'] = 'brown';
						break;
					default:
						$item['cell_css_classes']['privacy'] = 'green';
						break;
				}

		
				$item['tag'] = formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']);
		
				$item['traits'] = array();
				switch ($item['status']) {
					case 'first_draft':
						$item['traits']['draft'] = true;
					break;
			
					case 'published':
						//
					break;
			
					case 'published_with_draft':
						$item['traits']['draft'] = true;
					break;
			
					case 'hidden':
						//
					break;
			
					case 'hidden_with_draft':
						$item['traits']['draft'] = true;
					break;
			
					case 'trashed':
						//
					break;
			
					case 'trashed_with_draft':
						$item['traits']['draft'] = true;
					break;
				}
		
				if (!$item['lock_owner_id'] || $item['lock_owner_id'] == session('admin_userid')) {
					$item['traits']['not_locked'] = true;
				}
		
				if ($item['status'] == 'published') {
					$item['traits']['published'] = true;
				}
				if ($item['status'] == 'hidden') {
					$item['traits']['hidden'] = true;
				}
				if (allowDelete($item['id'], $item['type'], $item['status'])) {
					$item['traits']['deletable'] = true;
				}
				if (allowTrash($item['id'], $item['type'], $item['status'], $item['last_author_id'])) {
					$item['traits']['trashable'] = true;
				}
				if (allowHide($item['id'], $item['type'], $item['status'])) {
					$item['traits']['hideable'] = true;
				}
		
				if (isset($item['menu_id'])) {
					//Handle the case where a content item has a translation but a menu node does not
					if ($path == 'zenario__content/panels/chained' && $item['menu'] === null) {
						$item['menu'] = adminPhrase('[Menu Text missing]');
					} else {
						$item['traits']['linked'] = true;
						$item['menu'] = $item['menu_id'];
					}
					unset($item['menu_id']);
		
				} elseif ($item['status'] != 'trashed') {
					$item['traits']['unlinked'] = true;
					$item['menu'] = adminPhrase('Orphaned');
					$item['cell_css_classes']['menu'] = 'orange';
				}
		
				if ($item['file_id']) {
					if ($item['file_path'] && !docstoreFilePath($item['file_path'])) {	
						$item['filename'] .= ' (File is missing)';
						$item['cell_css_classes']['filename'] = "warning";
					} else {
						$item['traits']['has_file'] = true;
				
						if (isImageOrSVG($item['mime_type'])) {
							$item['traits']['has_picture'] = true;
						}
					}
				}
		
				$item['frontend_link'] = DIRECTORY_INDEX_FILENAME. '?cID='. $item['id']. '&cType='. $item['type']. '&zenario_sk_return=navigation_path';
		
				if ($mode == 'get_item_links') {
					$item['name'] = $item['tag'];
			
					if (get('languageId') && $item['language_id'] != get('languageId')) {
						$item['name'] .= ' ('. $item['language_id']. ')';
					}
			
					$item['navigation_path'] = 'zenario__content/panels/content//'. $id;
				}
			}
		}


		//
		// Translation functionality
		//

		if ($path == 'zenario__content/panels/chained') {
			$numEquivs = 0;
			foreach ($panel['items'] as &$item) {
				$item['cell_css_classes']['tag'] = 'lang_flag_'. $item['language_id'];
		
				if ($item['id'] === null) {
					$item['css_class'] = 'content_chained_single ghost';
					$item['cell_css_classes']['tag'] .= ' ghost';
					$item['cell_css_classes']['language_id'] = 'ghost';
					$item['lang_name'] = getLanguageName($item['language_id'], false, false);
					$item['tag'] = adminPhrase('MISSING [[lang_name]] ([[language_id]])', $item);
					$item['traits']['ghost'] = true;
	
					if ($checkSpecificPerms && checkPrivForLanguage(false, $item['language_id'])) {
						$item['_specific_perms'] = true;
					}
				} else {
					++$numEquivs;
				}
			}
	
			if ($numEquivs < $numLanguages) {
				foreach ($panel['items'] as &$item) {
					$item['traits']['zenario_trans__can_link'] = true;
				}
			} else {
				unset($panel['collection_buttons']['zenario_trans__link_to_chain']);
			}
	
			if ($numEquivs > 1) {
				foreach ($panel['items'] as &$item) {
					$item['traits']['zenario_trans__linked'] = true;
				}
			}

		} elseif (!isset($_GET['refiner__trash']) && $numLanguages > 1) {
	
			$langId = false;
			if (in($refinerName, 'language', 'content_type__language', 'template__language') && $langId = $panel['key']['language']) {
				foreach($langs as $lang) {
					if ($lang['id'] != $langId) {
						$panel['columns']['lang_'. $lang['id']] =
							array(
								'title' => $lang['id'],
								'show_by_default' => (!request('refiner__content_type') || request('refiner__content_type') == 'html'));
					}
				}
			}
	
			foreach ($panel['items'] as $id => &$item) {
				$cID = $cType = false;
				getCIDAndCTypeFromTagId($cID, $cType, $id);
				$isGhost = !empty($item['traits']['ghost']);
		
				$item['zenario_trans__links'] = 1;
		
				if (!$isGhost || $mode == 'select') {
					$equivs = equivalences($cID, $cType, $includeCurrent = $isGhost, $item['equiv_id']);
					if (!empty($equivs)) {
				
						foreach($langs as $lang) {
							if (!empty($equivs[$lang['id']])) {
								if ($lang['id'] != $item['language_id']) {
									++$item['zenario_trans__links'];
								}
						
								if ($langId && $lang['id'] != $langId) {
									$item['cell_css_classes']['lang_'. $lang['id']] =
										'zenario_trans_colicon zenario_trans_colicon__'. $equivs[$lang['id']]['status'];
								}
							}
						}
					}
				}
		
				if (!$isGhost && $item['zenario_trans__links'] < $numLanguages) {
					$item['traits']['zenario_trans__can_link'] = true;
				}
				if ($isGhost || $item['zenario_trans__links'] > 1) {
					$item['traits']['zenario_trans__linked'] = true;
				}
		
				if (!$isGhost || $mode == 'select') {
					if ($item['zenario_trans__links'] == 1) {
						$item['zenario_trans__links'] = adminPhrase('untranslated');
					} else {
						$item['zenario_trans__links'] .= ' / '. $numLanguages;
					}
				} else {
					$item['zenario_trans__links'] = '';
				}
			}


		} else {
			unset($panel['item_buttons']['zenario_trans__view']);
			unset($panel['columns']['zenario_trans__links']);
		}
		
		if (!empty($panel['key']['cType']) && isset($panel['collection_buttons']['export'])) {
			$panel['collection_buttons']['export']['admin_box']['key']['type'] = $panel['key']['cType'];
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (post('mass_add_to_menu') && checkPriv('_PRIV_ADD_MENU_ITEM')) {
			addContentItemsToMenu($ids, $ids2);

		} elseif (post('hide')) {
			foreach (explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (allowHide($cID, $cType) && checkPriv('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
						hideContent($cID, $cType);
					}
				}
			}

		} elseif (post('trash')) {
			foreach (explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (allowTrash($cID, $cType) && checkPriv('_PRIV_TRASH_CONTENT_ITEM', $cID, $cType)) {
						trashContent($cID, $cType);
					}
				}
			}

		} elseif (post('delete')) {
			foreach (explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (allowDelete($cID, $cType) && checkPriv('_PRIV_DELETE_DRAFT', $cID, $cType)) {
						deleteDraft($cID, $cType);
					}
				}
			}

		} elseif (post('delete_trashed_items') && checkPriv('_PRIV_DELETE_TRASHED_CONTENT_ITEMS')) {
			$result = getRows('content_items', array('id', 'type'), array('status' => 'trashed'));
			while ($content = sqlFetchAssoc($result)) {
				deleteContentItem($content['id'], $content['type']);
			}

		} elseif (post('lock')) {
			foreach (explodeAndTrim($ids) as $id) {
				if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					if (checkPriv('_PRIV_EDIT_DRAFT', $cID, $cType)) {
						updateRow('content_items', array('lock_owner_id' => session('admin_userid'), 'locked_datetime' => now()), array('id' => $cID, 'type' => $cType));
					}
				}
			}
		// Set unlock ajax message
		} elseif (get('unlock')) {
			foreach (explodeAndTrim($ids) as $id) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					$contentInfo = getRow('content_items', array('admin_version', 'lock_owner_id'), array('id'=>$cID, 'type'=>$cType));
					$cVersion = $contentInfo['admin_version'];
					$adminDetails = getAdminDetails($contentInfo['lock_owner_id']);
					echo 'Are you sure that you wish to ';
					if (!checkPriv(false, $cID, $cType)) {
						echo 'force-';
					}
					echo 'unlock on this content item? ';
					if ($date = getRow('content_item_versions', 'scheduled_publish_datetime', array('id'=>$cID,'type'=>$cType,'version'=>$cVersion))) {
						echo 'It is scheduled to be published by '.$adminDetails['first_name'].' '.$adminDetails['last_name'].' on '. formatDateTimeNicely($date, 'vis_date_format_long');
					} else {
						echo 'Any administrator who has authoring permission will be able to make changes to it.';
					}
				}
			}
		// Unlock a content item
		} elseif (post('unlock')) {
			foreach (explodeAndTrim($ids) as $id) {
			if (getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					// Unlock the item & remove scheduled publication
					if (checkPriv('_PRIV_CANCEL_CHECKOUT') || checkPriv(false, $cID, $cType)) {
						$cVersion = getRow('content_items', 'admin_version', array('id'=>$cID, 'type'=>$cType));
						updateRow('content_item_versions', array('scheduled_publish_datetime' => null), array('id'=>$cID,'type'=>$cType,'version'=>$cVersion));
						updateRow('content_items', array('lock_owner_id' => 0, 'locked_datetime' => null), array('id' => $cID, 'type' => $cType));
					}
				}
			}
	
		} elseif ((post('create_draft') || post('unhide')) && checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
			foreach (explodeAndTrim($ids) as $id) {
				if (($content = getRow('content_items', array('id', 'type', 'status', 'admin_version', 'visitor_version'), array('tag_id' => $id)))
				 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT', $content['id'], $content['type']))) {
			
					if (post('create_draft') && isDraft($content['status'])) {
						continue;
					} elseif (post('unhide') && $content['status'] != 'hidden') {
						continue;
					}
			
					$cVersionTo = false;
					createDraft($content['id'], $content['id'], $content['type'], $cVersionTo, ifNull(post('cVersion'), $content['admin_version']));
			
					if (get('method_call') == 'handleAdminToolbarAJAX') {
						$_SESSION['last_item'] = $content['type']. '_'. $content['id'];
				
						if (request('switch_to_edit_mode')) {
							$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit';
						}
					}
				}
			}

		} elseif (post('create_draft_by_copying') && checkPriv('_PRIV_CREATE_REVISION_DRAFT')) {
			$sourceCID = $sourceCType = false;
			if (getCIDAndCTypeFromTagId($sourceCID, $sourceCType, $ids2)
			 && ($content = getRow('content_items', array('id', 'type', 'status'), array('tag_id' => $ids)))
			 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT', $content['id'], $content['type']))) {
				$hasDraft =
					$content['status'] == 'first_draft'
				 || $content['status'] == 'published_with_draft'
				 || $content['status'] == 'hidden_with_draft'
				 || $content['status'] == 'trashed_with_draft';
		
				if (!$hasDraft || checkPriv('_PRIV_DELETE_DRAFT', $content['id'], $content['type'])) {
					if ($hasDraft) {
						deleteDraft($content['id'], $content['type'], false);
					}
			
					$cVersionTo = false;
					createDraft($content['id'], $sourceCID, $content['type'], $cVersionTo);
				}
			}

		} elseif (post('delete_archives') && checkPriv('_PRIV_TRASH_CONTENT_ITEM')) {
			foreach (explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if ((getCIDAndCTypeFromTagId($cID, $cType, $id))
				 && (checkPriv('_PRIV_TRASH_CONTENT_ITEM', $content['id'], $content['type']))) {
					deleteArchive($cID, $cType);
				}
			}
		}
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		$cID = $cType = false;
		if (post('download') && getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			//Offer a download for a file being used for a Content Item
			header('location: '. absCMSDirURL(). 'zenario/file.php?usage=content&cID='. $cID. '&cType='. $cType);
			exit;
		}
		
	}
}