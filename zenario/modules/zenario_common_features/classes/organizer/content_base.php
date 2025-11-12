<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


class zenario_common_features__organizer__content_base extends zenario_common_features {
	
	protected $numSyncAssistLangs = 0;
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (!ze\module::isRunning('zenario_users')) {
			unset($panel['inline_buttons']['privacy']['admin_box']);
		}

		if (
			$path == 'zenario__content/panels/trashed_content_items'
			&& isset($panel['db_items']['custom_where_statement__trashed'])
		) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__trashed'];
		} elseif (
			$path == 'zenario__content/panels/hidden_content_items'
			&& isset($panel['db_items']['custom_where_statement__hidden'])
		) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__hidden'];
		} elseif (
			ze::in($mode, 'full', 'quick', 'select', 'typeahead_search', 'get_matched_ids')
		 	&& isset($panel['db_items']['custom_where_statement__not_trashed'])
		 ) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__not_trashed'];
		}
		
		//Handle the case where an admin is picking a content item to join into a chain
		if ($refinerName == 'add_translation_to_chain') {
			
			//Note: The requests will be slightly different, depending on whether this was opened from
			//Organizer or the admin toolbar.
			//Try and get the parameters we need from the URL, no matter which format
			$equivId = (int) ($_REQUEST['parent__equivId'] ?? 0);
			$equivCID = (int) ($_REQUEST['parent__cID'] ?? 0);
			$equivType = $_REQUEST['parent__cType'] ?? '';
			$requestedLangId = $refinerId ?: ($_REQUEST['parent__id'] ?? '');
			
			if (!$equivId && $equivCID && $equivType) {
				$equivId = ze\content::equivId($equivCID, $equivType);
			}
			
			if ($equivId && $equivType && $requestedLangId) {
				
				//Flag that we're only showing content items of a specific type and language
				$panel['key']['cType'] = $equivType;
				$panel['key']['language'] = $requestedLangId;

				//Set the refiner SQL to show content items of the requested
				//type and language.
				$panel['refiners']['add_translation_to_chain']['sql'] = '
					  AND c.type = "'. ze\escape::asciiInSQL($equivType). '"
					  AND c.language_id = "'. ze\escape::asciiInSQL($requestedLangId). '"
					  AND c.equiv_id != '. (int) $equivId;
				
				//Add a column to display which might be collisions
				$panel['columns']['is_collision'] = [
					'db_column' => 'collisions.equiv_id IS NOT NULL'
				];
				
				$panel['refiners']['add_translation_to_chain']['table_join'] = '
					LEFT JOIN '. DB_PREFIX. 'content_items AS collisions
					   ON collisions.equiv_id = tc.equiv_id
					  AND collisions.type = tc.type
					  AND collisions.language_id IN (
						SELECT language_id
						FROM '. DB_PREFIX. 'content_items AS existing_translations
						WHERE existing_translations.equiv_id = '. (int) $equivId. '
						  AND existing_translations.type = "'. ze\escape::asciiInSQL($equivType). '"
					  )';
			}
		}

		if (!ze\module::isRunning('zenario_ctype_document')) {
			unset($panel['columns']['attachment_word_count']);
		}

		if ($refinerName == 'content_type') {
			$requestedContentType = $refinerId ?: ($_REQUEST['parent__id'] ?? '');
			if ($requestedContentType != 'document') {
				unset($panel['columns']['attachment_word_count']);
			}

			if ($requestedContentType) {
				$pinningEnabled = ze\row::get('content_types', 'allow_pinned_content', ['content_type_id' => $requestedContentType]);

				if (!$pinningEnabled) {
					unset($panel['inline_buttons']['pinned']);
					unset($panel['inline_buttons']['not_pinned']);
				}
			}
		}

		if ($refinerName == 'filter_by_language_and_content_type') {
			$requestedContentType = $refinerId ?: ($_REQUEST['parent__id'] ?? '');

			if (!$cTypeFilter = zenario_organizer::filterValue('type')) {
				zenario_organizer::setFilterValue('type', $requestedContentType);
			}

			if (!$langIdFilter = zenario_organizer::filterValue('language_id')) {
				$langIdFilter = ze::$defaultLang;
				zenario_organizer::setFilterValue('language_id', $langIdFilter);
			}
		}
		
		//In "Copy from other" mode, do not allow picking the same content item
		//as the source and target. Hide it in the picker panel.
		if (!empty($_GET['_combineItem'])) {
			$panel['db_items']['where_statement'] .= "
				AND c.tag_id != '" . ze\escape::sql($_GET['_combineItem']) . "'";
		}
		
		//Also, only allow the "Copy from other" button to work on specific content type panels.
		//Hide it entirely on "Trashed" and "Hidden" panels,
		//and make it disabled for "All content items", "Draft content items", "Special pages", etc.
		if ($refinerName != 'content_type') {
			if ($path == 'zenario__content/panels/trashed_content_items' || $path == 'zenario__content/panels/hidden_content_items') {
				if (isset($panel['item_buttons']['create_draft_by_copying'])) {
					$panel['item_buttons']['create_draft_by_copying']['hidden'] = true;
				}
				
				if (isset($panel['item_buttons']['create_draft_by_overwriting'])) {
					$panel['item_buttons']['create_draft_by_overwriting']['hidden'] = true;
				}
			} else {
				if (isset($panel['item_buttons']['create_draft_by_copying'])) {
					$panel['item_buttons']['create_draft_by_copying']['disabled'] = true;
				}
				
				if (isset($panel['item_buttons']['create_draft_by_overwriting'])) {
					$panel['item_buttons']['create_draft_by_overwriting']['disabled'] = true;
				}
			}
		}

		//Have a refiner that enforces the language filter be set.
		if ($mode != 'typeahead_search'
		 && (isset($_GET['refiner__filter_by_lang']) || isset($_GET['refiner__filter_exclude_documents']))
		 && !isset($_GET['refiner__zenario_trans__chained_in_link'])) {
			
			//If it's not set, set it to one language initially
			if (!$langIdFilter = zenario_organizer::filterValue('language_id')) {
				
				//If an item was selected, use the language from that...
				if (ze::request('_item')) {
					$langIdFilter = ze\row::get('content_items', 'language_id', ['tag_id' => ze::request('_item')]);
				}
				//...otherwise use the default language
				if (!$langIdFilter) {
					$langIdFilter = ze::$defaultLang;
				}
				
				zenario_organizer::setFilterValue('language_id', $langIdFilter);
			}
			unset($panel['quick_filter_buttons']['all_languages']);
		}
		
		if ($refinerName == 'published_draft_and_unlisted_only') {
			$panel['title'] = ze\admin::phrase('Published, unlisted and draft content items');
		}
		
		//Check if a specific Content Type and/or layout has been set, either by using a content-type refiner or a layout's refiner
		if (ze::get('refiner__template')) {
			$panel['key']['layoutId'] = $_GET['refiner__template'] ?? false;
			$panel['key']['cType'] = ze\row::get('layouts', 'content_type', ze::get('refiner__template'));
			
			//When viewing content items that use a particular layout,
			//hide "Create" and "Export" buttons, and Settings Dropdown.
			$panel['collection_buttons']['create']['hidden'] = true;
			$panel['collection_buttons']['export']['hidden'] = true;
			$panel['collection_buttons']['settings_dropdown']['hidden'] = true;
		} elseif (ze::get('refiner__content_type')) {
			$panel['key']['cType'] = $_GET['refiner__content_type'] ?? false;
		}
		
		
		//Check which content type we're displaying and whether the current admin has rights to create content items of that type.
		$checkPermsOnCType = $panel['key']['cType'] ?? '';
		$hasPermsOnCType = ze\priv::check('_PRIV_EDIT_DRAFT', false, $checkPermsOnCType);
		
		//If they've no permissions, try to make sure that any "create" button is not visible
		//(as the admin would just see a permissions error if they clicked on it).
		if (!$hasPermsOnCType && !empty($panel['collection_buttons'])) {
			unset($panel['collection_buttons']['create']);
			unset($panel['collection_buttons']['duplicate']);
			
			$unsets = [];
			foreach ($panel['collection_buttons'] as $buttonCodeName => $button) {
				if (!empty($button['upload']['request']['create_multiple'])
				 || (isset($button['css_class']) && $button['css_class'] == 'zenario_create_a_new')) {
					$unsets[] = $buttonCodeName;
				}
			}
			foreach ($unsets as $buttonCodeName) {
				unset($panel['collection_buttons'][$buttonCodeName]);
			}
		}
		
		
		//Attempt to customise the defaults slightly depending on the content type
		//These options are only defaults and will be overridden if the Administrator has ever set or changed them.
		if ($panel['key']['cType']) {
			switch ($panel['key']['cType']) {
				case 'news':
					$panel['columns']['title']['show_by_default'] = true;
					$panel['columns']['description']['show_by_default'] = false;
					$panel['columns']['release_date']['show_by_default'] = true;
					$panel['columns']['inline_files']['show_by_default'] = false;
					$panel['columns']['zenario_trans__links']['show_by_default'] = false;
					$panel['columns']['menu']['show_by_default'] = true;
			
					break;
		
				case 'blog':
					$panel['columns']['release_date']['show_by_default'] = true;
			
					break;
				case 'document':
					$panel['item_buttons']['create_draft_by_overwriting']['hidden'] = true;
			
					break;
			}
		
			//Task #9514: Release Date should always be visible if you are looking at a Content Type where it is mandatory.
			if ($details = ze\contentAdm::cTypeDetails($panel['key']['cType'])) {
				foreach ([
					'writer_field' => 'writer_name',
					'description_field' => 'description',
					'keywords_field' => 'keywords',
					'release_date_field' => 'release_date'
				] as $fieldName => $columnName) {
					if (!isset($details[$fieldName])) {
					
					} elseif ($details[$fieldName] == 'mandatory') {
						$panel['columns'][$columnName]['always_show'] = true;
		
					} elseif ($details[$fieldName] == 'hidden') {
						$panel['columns'][$columnName]['hidden'] = true;
					}
				}
			
				if (isset($panel['collection_buttons']['settings_ctype'])) {
					$panel['collection_buttons']['settings_ctype']['hidden'] = false;
					$panel['collection_buttons']['settings_ctype']['admin_box']['key']['id'] = $panel['key']['cType'];
					$panel['collection_buttons']['settings_ctype']['label'] = 
						ze\admin::phrase('Settings for [[content_type_plural_lower_en]]', $details);
				}
			}

		//If this is a panel for multiple content types then we are limited in how much we can customise it.
		//But if any fields are always hidden, we can still hide them
		} else {
			foreach ([
				'writer_field' => 'writer_name',
				'description_field' => 'description',
				'keywords_field' => 'keywords',
				'release_date_field' => 'release_date'
			] as $fieldName => $columnName) {
	
				if (!ze\row::exists('content_types', [$fieldName => ['!' => 'hidden']])) {
					$panel['columns'][$columnName]['hidden'] = true;
				}
			}
		}
		
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			
			//Note down which content types have categories
			$panel['custom__content_types_with_categories'] =
				ze\ray::valuesToKeys(ze\row::getValues('content_types', 'content_type_id', ['enable_categories' => 1]));
		}
		
		//If this is the Translations panel, and it was accessed from the menu nodes panel,
		//make sure the admin box for creating/editing has the correct parameters.
		if ($path == 'zenario__content/panels/chained' && $refinerName == 'zenario_trans__chained_in_link__from_menu_node' && ($menu = ze\menu::details($refinerId))) {
			$panel['item_buttons']['create_translation']['admin_box']['key']['id_is_parent_menu_node_id'] = 1;
			$panel['item_buttons']['create_translation']['admin_box']['key']['edit_linked_content_item'] = 1;
		}
		
		
		//Check to see if someone is using the activity filter.
		$activityFilter = zenario_organizer::filterValue('activity_filter');
		if (!is_null($activityFilter)) {
			
			//Note: I'm implemented in PHP to be able to use a greater-than logic and a dynamic date.
			$ymd = ze\date::ymd();
			
			switch ($activityFilter) {
				case 'today':
					$panel['db_items']['where_statement'] .= "
						AND v.last_activity_datetime >= '". ze\escape::sql($ymd). "'";
					break;
				
				case 'last_3_days':
					$panel['db_items']['where_statement'] .= "
						AND v.last_activity_datetime >= DATE_SUB('". ze\escape::sql($ymd). "', INTERVAL 2 DAY)";
					break;
				
				case 'last_15_days':
					$panel['db_items']['where_statement'] .= "
						AND v.last_activity_datetime >= DATE_SUB('". ze\escape::sql($ymd). "', INTERVAL 14 DAY)";
					break;
				
				//Some options we didn't end up using, but I'll keep the code commented out:
				#case 'this_week':
				#	$panel['db_items']['where_statement'] .= "
				#		AND v.last_activity_datetime >= DATE_SUB('". ze\escape::sql($ymd). "', INTERVAL WEEKDAY('". ze\escape::sql($ymd). "') DAY)";
				#	break;
				#case 'this_month':
				#	$panel['db_items']['where_statement'] .= "
				#		AND v.last_activity_datetime >= DATE_SUB('". ze\escape::sql($ymd). "', INTERVAL DAYOFMONTH('". ze\escape::sql($ymd). "') DAY)";
				#	break;
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		if ($panel['key']['cType']) {
			//Hide the content type quick filter when viewing
			//specific content types, rather than "All content items" panel.
			unset($panel['quick_filter_buttons']['content_type']);
			unset($panel['quick_filter_buttons']['all_content_types']);
			
			if (ze\row::exists('content_types', ['enable_categories' => 0, 'content_type_id'=> $panel['key']['cType']])){
				unset($panel['inline_buttons']['no_categories']);
				unset($panel['inline_buttons']['one_category']);
				unset($panel['inline_buttons']['multiple_categories']);
			}
		}
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			$ord = 1000;
			$panel['columns']['type']['values'] = [];
			foreach (ze\content::getContentTypes(false, false) as $cType) {
				$panel['columns']['type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
	
				//Only populate the content type quick filter
				//when viewing the "All content items" panel.
				if (!$panel['key']['cType']) {
					$panel['quick_filter_buttons'][$cType['content_type_id']] = [
						'ord' => ++$ord,
						'parent' => 'content_type',
						'column' => 'type',
						'label' => $cType['content_type_name_en'],
						'value' => $cType['content_type_id']
					];
				}
			}
			
			
	
			$panel['columns']['last_activity_admin_id']['values'] = [];
			foreach (ze\row::getAssocs('admins', ['username', 'status', 'first_name', 'last_name', 'authtype'], [], ['first_name', 'last_name']) as $adminId => $admin) {
				
				$hidden = $admin['status'] != 'active';
				
				$fullName = '';
				if (!empty($admin['first_name']) && !empty($admin['last_name'])) {
					$fullName = $admin['first_name'] . ' ' . $admin['last_name'];
				}
				
				if ($admin['authtype'] == 'super') {
					$fullName = ze\admin::phrase('By [[first_name]] [[last_name]] ([[username]], multi-site)', $admin);
				} else {
					$fullName = ze\admin::phrase('By [[first_name]] [[last_name]] ([[username]])', $admin);
				}
				
				$panel['columns']['last_activity_admin_id']['values'][$adminId] = ['label' => $fullName, 'hidden' => $hidden];
				
				if (!$hidden) {
					$panel['quick_filter_buttons']['admin_'. $adminId] = [
						'ord' => ++$ord,
						'parent' => 'admins',
						'column' => 'last_activity_admin_id',
						'label' => $fullName,
						'value' => $adminId
					];
				}
			}
		}
		
		$numLanguages = count($langs = ze\lang::getLanguages($includeAllLanguages = false, $orderByEnglishName = true, $defaultLangFirst = true));
		
		//If this panel is for a specific language, don't show the language filter
		//and also set the language id in the key so any FABs that open default to that language.
		if ($panel['key']['language']) {
			unset($panel['quick_filter_buttons']['language']);
			unset($panel['quick_filter_buttons']['all_languages']);
		
		//If there is more than one language on this site, show the language filter
		} elseif ($numLanguages > 1 && ze::in($mode, 'full', 'quick', 'select')) {
			
			//Check the current language filter, if there is one
			$langIdFilter = zenario_organizer::filterValue('language_id');
			
			//For each language, add a filter option
			$ord = 100;
			foreach ($langs as $lang) {
				
				$label = ze\admin::phrase('[[english_name]] ([[id]])', $lang);
				
				if ($lang['id'] == ze::$defaultLang) {
					$label .= ' (' . ze\admin::phrase('default') . ')';
				}
				
				$panel['quick_filter_buttons']['lang_'. $lang['id']] = [
					'ord' => ++$ord,
					'parent' => 'language',
					'column' => 'language_id',
					'label' => $label,
					'value' => $lang['id']
				];
				
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
		
		//If we're showing trashed or hidden items, don't show the status filter.
		//Also don't show the "Scheduled publish date/time" column and the "Start editing" button.
		if ($path == 'zenario__content/panels/trashed_content_items' || $path == 'zenario__content/panels/hidden_content_items'
		 || !ze::in($mode, 'full', 'quick', 'select')) {
			unset(
				$panel['quick_filter_buttons']['status'],
				$panel['quick_filter_buttons']['any_status'],
				$panel['quick_filter_buttons']['first_draft'],
				$panel['quick_filter_buttons']['published_with_draft'],
				$panel['quick_filter_buttons']['published'],
				$panel['quick_filter_buttons']['unlisted_with_draft'],
				$panel['quick_filter_buttons']['unlisted'],
				$panel['quick_filter_buttons']['hidden'],
				$panel['columns']['scheduled_publish_datetime'],
				$panel['item_buttons']['start_editing']
			);
		}

		//If a filter is set, make sure to change the label of the parent to what was chosen.
		//Likewise, change the parent labels for the other filters
		foreach ([
			'status' => 'status',
			'type' => 'content_type',
			'last_activity_admin_id' => 'admins',
			'activity_filter' => 'activity'
		] as $columnName => $filterParentButtonName) {
			$filterValue = zenario_organizer::filterValue($columnName);
			
			if (!is_null($filterValue)) {
				
				if ($filterParentButtonName === 'admins') {
					$filterValue = 'admin_'. $filterValue;
				}
				
				if (isset($panel['quick_filter_buttons'][$filterValue]['label'])) {
					$panel['quick_filter_buttons'][$filterParentButtonName]['label'] =
						$panel['quick_filter_buttons'][$filterValue]['label'];
				}
			}
		}
		
		
		//If this panel is for a specific layout, don't show the layout filter

		// As of 13 Jul 2021, the quick filter for layouts is removed.
		// The logic below is currently disabled in case we ever need to bring it back. --Marcin
		if ($panel['key']['layoutId'] || !ze::in($mode, 'full', 'quick', 'select')) {
			// unset($panel['quick_filter_buttons']['layout']);
			// unset($panel['quick_filter_buttons']['all_layouts']);
		
		} else {
			$sql = "
				SELECT
					layout_id, name,
					CONCAT('L', IF (layout_id < 10, LPAD(CAST(layout_id AS CHAR), 2, '0'), CAST(layout_id AS CHAR)), ' ', name) AS id_and_name
				FROM ". DB_PREFIX. "layouts
				WHERE status = 'active'";
			
			if ($panel['key']['cType']) {
				$sql .= "
				  AND `content_type` = '". ze\escape::asciiInSQL($panel['key']['cType']). "'";
			}
			
			$sql .= "
				ORDER BY layout_id";
			
			//Check the current filter, if there is one
			$layoutIdFilter = zenario_organizer::filterValue('layout_id');
			
			//For each layout, add a filter option

			// As of 13 Jul 2021, the quick filter for layouts is removed.
			// The logic below is currently disabled in case we ever need to bring it back. --Marcin
			// $ord = 1000;
			// $result = ze\sql::select($sql);
			// while ($layout = ze\sql::fetchAssoc($result)) {
				
			// 	$label = ze\admin::phrase('[[id_and_name]]', $layout);
				
			// 	$panel['quick_filter_buttons']['layout_'. $layout['layout_id']] = [
			// 		'ord' => ++$ord,
			// 		'parent' => 'layout',
			// 		'column' => 'layout_id',
			// 		'label' => $label,
			// 		'value' => $layout['layout_id']
			// 	];
				
			// 	//If the layout was chosen, change the text on the parent-button

			// 	if ($layoutIdFilter == $layout['layout_id']) {
			// 		$panel['quick_filter_buttons']['layout']['label'] = $label;
			// 	}
				
			// }
		}
		
		
		
		
		
		
		if ($path == 'zenario__content/panels/trashed_content_items' && !$panel['key']['layoutId']) {
			$panel['title'] = ze\admin::phrase('Trashed content items');
			$panel['no_items_message'] = ze\admin::phrase('No trashed content items found');
			$panel['item']['css_class'] = 'content_trashed';
			
			unset($panel['columns']['status']);
			unset($panel['collection_buttons']['create']);
			
			$panel['columns']['concealed_datetime']['show_by_default'] = true;
			$panel['columns']['concealed_datetime']['title'] = ze\admin::phrase('Trashed');
			$panel['columns']['concealed_by']['show_by_default'] = true;
			$panel['columns']['concealed_by']['title'] = ze\admin::phrase('Trashed by');
			
			$panel['columns']['last_modified_datetime']['show_by_default'] = false;
			$panel['columns']['last_modified_by_admin']['show_by_default'] = false;
			$panel['columns']['word_count']['show_by_default'] = false;
			$panel['columns']['attachment_word_count']['show_by_default'] = false;
			$panel['columns']['menu']['show_by_default'] = false;
			
			$dateToday = ze\date::ymd();
			$dateObject = ze\date::new($dateToday);
			$dateTwoWeeksAgo = $dateObject->modify('-14 days')->format('Y-m-d');
			
			$sql = "
				SELECT COUNT(*)
				FROM " . DB_PREFIX . "content_items c
				INNER JOIN " . DB_PREFIX . "content_item_versions v
					ON v.id = c.id
			  		AND v.type = c.type
			  		AND v.version = c.admin_version
			  	WHERE status = 'trashed'
			  	AND DATE(v.concealed_datetime) < '" . ze\escape::sql($dateTwoWeeksAgo) . "'";
			$result = ze\sql::select($sql);
			$count = ze\sql::fetchValue($result);
			
			if (!$count) {
				$panel['collection_buttons']['delete_trashed_items']['disabled'] = true;
				$panel['collection_buttons']['delete_trashed_items']['disabled_tooltip'] = ze\admin::phrase('There are no content items that were deleted more than 14 days ago');
			}

		} elseif ($path == 'zenario__content/panels/hidden_content_items' && !$panel['key']['layoutId']) {
			$panel['title'] = ze\admin::phrase('Hidden content items');
			$panel['no_items_message'] = ze\admin::phrase('No content items are hidden');
			$panel['item']['css_class'] = 'content_hidden';
			
			unset($panel['columns']['status']);
			unset($panel['collection_buttons']['create']);
			
			$panel['columns']['concealed_datetime']['show_by_default'] = true;
			$panel['columns']['concealed_datetime']['title'] = ze\admin::phrase('Hidden');
			$panel['columns']['concealed_by']['show_by_default'] = true;
			$panel['columns']['concealed_by']['title'] = ze\admin::phrase('Hidden by');
			
			$panel['columns']['last_modified_datetime']['show_by_default'] = false;
			$panel['columns']['last_modified_by_admin']['show_by_default'] = false;
			$panel['columns']['word_count']['show_by_default'] = false;
			$panel['columns']['attachment_word_count']['show_by_default'] = false;
			$panel['columns']['menu']['show_by_default'] = false;

		} elseif (ze::get('refiner__following_item_link')) {
			$panel['title'] = ze\admin::phrase('Linked content item');
			unset($panel['collection_buttons']['create']);
			unset($panel['item_buttons']['trash']);
			unset($panel['item_buttons']['delete']);

		} elseif (ze::get('refinerName') == 'find_duplicates') {
			$panel['notice']['show'] = true;
			unset($panel['collection_buttons']['create']);
			$panel['title'] = ze\admin::phrase('Items with duplicate file attachments');
			$panel['no_items_message'] = ze\admin::phrase('No items with duplicate file attachments found');
			unset($panel['collection_buttons']['diagnostics_dropdown']);

			$panel['columns']['file_id']['always_show'] = $panel['columns']['checksum']['always_show'] = true;
			$panel['columns']['file_id']['ord'] = 1;
			$panel['columns']['checksum']['ord'] = 1.1;
			$panel['columns']['filename']['ord'] = 1.2;
			$panel['columns']['tag']['ord'] = 1.3;
	
			//Attempt to turn off a few columns by default here.
			//These options are only defaults and will be overridden if the Administrator has ever set or changed them.
			foreach ($panel['columns'] as $col_name => &$col) {
				if (is_array($col_name)) {
					$col['show_by_default'] = false;
		
					switch ($col_name) {
						case 'title':
						case 'file_id':
						case 's3_file_id':
						case 'filename':
						case 'version':
						case 'status':
							$col['always_show'] = false;
					}
				}
			}

			//Get an extra property on the "Export" button which will export more columns from the "Find duplicates" panel.
			$panel['collection_buttons']['export']['admin_box']['key']['exportDuplicates'] = true;

		} elseif ($path == 'zenario__content/panels/chained') {
			$cID = $cType = false;
	
			if ($refinerName == 'zenario_trans__chained_in_link') {
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId);
				$panel['return_if_empty'] = true;
	
			} elseif ($refinerName == 'zenario_trans__chained_in_link__from_menu_node' && ($menu = ze\menu::details($refinerId))) {
				$cID = $menu['equiv_id'];
				$cType = $menu['content_type'];
			}
			
			$equivId = ze\content::equivId($cID, $cType);
			$equivContentLanguage = ze\content::langId($equivId, $cType);
			
			$panel['title'] = ze\admin::phrase('Translation chain of "[[tag]]"', ['tag' => ze\content::formatTag($equivId, $cType, -1, false, true), 'lang_id' => $equivContentLanguage]);
			$panel['label_format_for_grid_view'] = "[[tag]] \n [[language_id]]";
			
			//Check what is available.
			//If there is a content item in this chain in the default language, use that as base for translating.
			//Otherwise, do not allow creating any translations. The admin will need to go to the front-end
			//to create a translation, as then it's clear which content item is used as base.
			//Allow adding existing translations into the chain though.
			$defaulltLangCID = ze\row::get('content_items', 'id', ['equiv_id' => $equivId, 'type' => $cType, 'language_id' => ze::$defaultLang]);
			if ($defaulltLangCID) {
				if (isset($panel['item_buttons']['create_translation'])) {
					$panel['item_buttons']['create_translation']['tooltip'] =
						ze\admin::phrase(
							'Duplicate "[[tag]]" ([[language_id]]) to create a translation in [[lang_name]]',
							[
								'tag' => ze\content::formatTag($defaulltLangCID, $cType),
								'language_id' => ze\content::langId($defaulltLangCID, $cType),
								'lang_name' => '[[lang_name]]'
							]
						);
				}
			} else {
				if (isset($panel['item_buttons']['create_translation'])) {
					$panel['item_buttons']['create_translation']['tooltip'] =
						ze\admin::phrase(
							'Disabled: there is no content item in the default language ([[language]]).
							Please go to any existing item in this translation chain on the front-end, and use it as base to create a new translation.',
							[
								'language' => ze\lang::name(ze::$defaultLang)
							]
						);
					
					$panel['item_buttons']['create_translation']['disabled'] = true;
				}
			}

		} elseif ($panel['key']['layoutId'] && $panel['key']['language']) {
			$layout = ze\row::get('layouts', ['layout_id', 'name'], $panel['key']['layoutId']);
			$mrg = [
				'codeName' => ze\layoutAdm::codeName($layout['layout_id']),
				'name' => $layout['name'],
				'language' => ze\lang::name($panel['key']['language'])
			];
			$panel['label_format_for_grid_view'] = '[[tag]]';
			$panel['title'] = ze\admin::phrase('Content items using the layout "[[codeName]] [[name]]" in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('No content items use the layout "[[codeName]] [[name]]" in [[language]]', $mrg);

		} elseif ($panel['key']['cType'] && $panel['key']['language']) {
			$mrg = [
				'ctype' => ze\content::getContentTypeName($panel['key']['cType']),
				'language' => ze\lang::name($panel['key']['language'])
			];
			$panel['title'] = ze\admin::phrase('[[ctype]] content items in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no [[ctype]] content items in [[language]]', $mrg);
			$panel['columns']['language_id']['hidden'] = true;
			unset($panel['columns']['type']);

		} elseif ($panel['key']['layoutId']) {
			$layout = ze\row::get('layouts', ['layout_id', 'name'], $panel['key']['layoutId']);
			$mrg = [
				'codeName' => ze\layoutAdm::codeName($layout['layout_id']),
				'name' => $layout['name']
			];
			$panel['title'] = ze\admin::phrase('Content items using the layout "[[codeName]] [[name]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('No content items use the layout "[[codeName]] [[name]]"', $mrg);

		} elseif ($panel['key']['cType']) {
			$panel['item']['css_class'] = 'content_type_'. $panel['key']['cType'];
			$mrg = ze\contentAdm::cTypeDetails($panel['key']['cType']);
			
			$panel['title'] = ze\admin::phrase('[[content_type_plural_en]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('No [[content_type_plural_lower_en]] found', $mrg);
			unset($panel['columns']['type']);

		} elseif (ze::get('refiner__menu_children')) {
			$mrg = [
				'name' => ze\menu::name($_GET['refiner__menu_children'] ?? false, true)];
			$panel['title'] = ze\admin::phrase('Content items under the menu node "[[name]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('No content items found under this menu node', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($panel['key']['language']) {
			$mrg = [
				'language' => ze\lang::name($panel['key']['language'])];
			$panel['title'] = ze\admin::phrase('Content items in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items in [[language]].', $mrg);
			$panel['columns']['language_id']['hidden'] = true;

		} elseif (ze::get('refiner__category')) {
			unset($panel['collection_buttons']['create']);
			$mrg = [
				'category' => ze\category::name(ze::get('refiner__category'))];
			$panel['title'] = ze\admin::phrase('Content items in the category "[[category]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('No content items found in this category', $mrg);

		} elseif (ze::get('refiner__module_usage')) {
			$mrg = [
				'name' => ze\module::displayName(ze::get('refiner__module_usage'))];
			$panel['title'] = ze\admin::phrase('Content items on which module "[[name]]" is used', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the module "[[name]]"', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (ze::get('refiner__module_effective_usage')) {
			$mrg = [
				'name' => ze\module::displayName(ze::get('refiner__module_effective_usage'))];
			$panel['title'] = ze\admin::phrase('Content items on which module "[[name]]" is used (effective usage)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the module "[[name]]"', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (ze::get('refiner__plugin_instance_usage')) {
			$mrg = [
				'name' => ze\plugin::name(ze::get('refiner__plugin_instance_usage'))];
			$panel['title'] = ze\admin::phrase('Content items on which the plugin "[[name]]" is used', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the plugin "[[name]]"', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif (ze::get('refiner__plugin_instance_effective_usage')) {
			$mrg = [
				'name' => ze\plugin::name(ze::get('refiner__plugin_instance_effective_usage'))];
			$panel['title'] = ze\admin::phrase('Content items on which the plugin "[[name]]" appears (effective usage)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the plugin "[[name]]"', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'special_pages') {
			$panel['title'] = ze\admin::phrase('Special pages for the default language ([[lang]])', ['lang' => ze::$defaultLang]);
			$panel['item']['css_class'] = 'special_content_published';
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'work_in_progress') {
			$panel['title'] = ze\admin::phrase('Work in progress');
			$panel['no_items_message'] = ze\admin::phrase('No draft content items found');
			$panel['item']['css_class'] = 'content_draft';
			unset($panel['trash']);

		} elseif ($refinerName == 'content_items_using_form') {
			$mrg = [];
			if (ze\module::inc('zenario_user_forms')) {
				$mrg['name'] = zenario_user_forms::getFormName($refinerId);
			}
			$panel['title'] = ze\admin::phrase('Content items using the form "[[name]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the form "[[name]]"', $mrg);
			unset($panel['trash']);

		} elseif ($refinerName == 'content_items_using_image') {
			$mrg = ze\row::get('files', ['filename'], $refinerId);
			$panel['title'] = ze\admin::phrase('Content items using the image "[[filename]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the image "[[filename]]"', $mrg);
			unset($panel['trash']);

		} elseif (ze::get('refiner__uses_access_code')) {
			$panel['title'] = ze\admin::phrase('Content items using the access code "[[access_code]]"', ['access_code' => ze::get('refiner__uses_access_code')]);
			$panel['no_items_message'] = ze\admin::phrase('No content item uses the access code "[[access_code]]"', ['access_code' => ze::get('refiner__uses_access_code')]);
			unset($panel['collection_buttons']['create']);
			unset($panel['item_buttons']['trash']);
			unset($panel['item_buttons']['delete']);
			unset($panel['item_buttons']['duplicate']);
			unset($panel['item_buttons']['create_draft_by_copying']);
			unset($panel['item_buttons']['create_draft_by_overwriting']);
		}
		
		if (isset($panel['item_buttons']['create_draft_by_copying'])) {
			$panel['item_buttons']['create_draft_by_copying']['combine_items']['path'] = 'zenario__content/panels/content/refiners/content_type//'. $panel['key']['cType']. '//';
		}
		
		if (isset($panel['item_buttons']['create_draft_by_overwriting'])) {
			$panel['item_buttons']['create_draft_by_overwriting']['combine_items']['path'] = 'zenario__content/panels/content/refiners/content_type//'. $panel['key']['cType']. '//';
		}
        
		//If this is full, quick or select mode, and the admin looking at this only has permissions
		//to edit specific content items, we'll need to check if the current admin can edit each
		//content item.
		$showInOrganiser = ze::in($mode, 'full', 'quick', 'select');
		$checkSpecificPerms = $showInOrganiser && ze\admin::hasSpecificPerms();
		
		$organisationManagerModuleRunning = ze\module::inc('zenario_organization_manager');

		foreach ($panel['items'] as $id => &$item) {
			
			//Show last modified date and admin who modified it (WIP dropdown).
			if ($item['last_author_id'] != 0) {
				$item['last_modified_by_admin'] = ze\admin::formatName($item['last_author_id']);
			} else {
				$item['last_modified_by_admin'] = ze\admin::formatName($item['creating_author_id']);
			}
			
			if (!empty($item['concealed_by'])) {
				$item['concealed_by'] = ze\admin::formatName($item['concealed_by']);
			}
			
			if (!empty($item['created_datetime'])) {
				$item['created_datetime'] = ze\admin::formatRelativeDateTime($item['created_datetime'], $maxPeriod = "day", $addFullTime = false);
			}
			
			if (!empty($item['last_modified_datetime'])) {
				$item['last_modified_datetime'] = ze\admin::formatRelativeDateTime($item['last_modified_datetime'], $maxPeriod = "day", $addFullTime = false);
				$item['unpublished_content_info'] =
					 ze\admin::phrase('Last edit [[time]] by [[admin]].', [
						'time' => $item['last_modified_datetime'],
						'admin' => $item['last_modified_by_admin']]);
			} elseif ($item['created_datetime']) {
				$item['unpublished_content_info'] =
					 ze\admin::phrase('Created [[time]] by [[admin]].', [
					 	'time' => $item['created_datetime'],
					 	'admin' => $item['last_modified_by_admin']]);
			}
			
			$item['cell_css_classes'] = [];
		
			if ($item['id'] !== null) {
	
				if ($checkSpecificPerms && ze\priv::check(false, $item['id'], $item['type'])) {
					$item['_specific_perms'] = true;
				}
				
				if ($item['lock_owner_id']) {
					$adminDetails = ze\admin::details($item['lock_owner_id']);
					$item['lock_owner_name'] = $adminDetails['first_name'].' '.$adminDetails['last_name'];
				}
		
				if ($path == 'zenario__content/panels/chained') {
					$panel['key']['equivId'] = $item['equiv_id'];
					$panel['key']['cType'] = $item['type'];
				}
				
				$item['css_class'] = ze\contentAdm::getItemIconClass($item['id'], $item['type'], true, $item['status']);
				$item['statusPhrase'] = ze\contentAdm::statusPhrase($item['status']);
				$item['tooltip'] = ze\admin::phrase('This content item has status [[statusPhrase]]', $item);
				
				if ($item['scheduled_publish_datetime']) {
					$item['publication_time'] = 
						ze\admin::formatDateTime($item['scheduled_publish_datetime'], 'vis_date_format_med');
				
					$item['tooltip'] = ze\admin::phrase("Scheduled to be published on [[publication_time]].", $item);
				}
				
				// Change code for Special pages tooltip
				if ($refinerName == 'special_pages'){
					$specialPage = ze\row::get('special_pages', ['page_type'], ['equiv_id' => $item['equiv_id'], 'content_type' => $item['type']]);
					$specialPageName = str_replace('_', ' ', ze\ring::chopPrefix('zenario_', $specialPage['page_type'], true));
					$item['tooltip'] = ze\admin::phrase('Special page: [[name]] page', ['name' => $specialPageName]);
				}
				//		
				if ($showInOrganiser && ($privacy = $item['privacy'] ?? false)) {
					$item['row_class'] = ' privacy_'. $privacy;
					
					//If this content item is set to a group or smart group,
					//go get a better description which includes the name.
					if (ze::in($privacy, 'group_members', 'with_role', 'in_smart_group', 'logged_in_not_in_smart_group')) {
						$item['privacy'] =
							ze\admin::phrase('Permissions: [[privacyDesc]]', ['privacyDesc' => ze\contentAdm::privacyDesc($item)]);
					}
				}
				
				if (isset($item['row_class']) && !empty($item['layout_status'])) {
					$item['row_class'] .= ' layout_status_' . $item['layout_status'];
				}
				//Change code for Special page ID/alias
				if ($refinerName == 'special_pages'){
					$item['tag'] = ze\content::formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']).
					' '.
					ze\admin::phrase('([[name]] page)', ['name' => $specialPageName]);
				
				} else {
					$item['tag'] = ze\content::formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']);
				}
				
				$item['draft'] = ze\content::isDraft($item['status']);
				$item['published'] = ze\content::isPublished($item['status']);
		
				if (!$item['lock_owner_id'] || $item['lock_owner_id'] == ($_SESSION['admin_userid'] ?? false)) {
					$item['not_locked'] = true;
				}
		
				if ($item['status'] == 'hidden') {
					$item['hidden'] = true;
				}
				if (ze\contentAdm::allowDelete($item['id'], $item['type'], $item['status'])) {
					$item['deletable'] = true;
				}
				if (ze\contentAdm::allowTrash($item['id'], $item['type'], $item['status'], $item['last_author_id'])) {
					$item['trashable'] = true;
				}
				if (ze\contentAdm::allowHide($item['id'], $item['type'], $item['status'])) {
					$item['hideable'] = true;
				}
				if (ze\content::isUnlisted($item['status'])) {
					if (ze\contentAdm::allowRelist($item['id'], $item['type'], $item['status'])) {
						$item['relistable'] = true;
					}
				} else {
					if (ze\contentAdm::allowDelist($item['id'], $item['type'], $item['status'])) {
						$item['delistable'] = true;
					}
				}
				
				if ($showInOrganiser) {
					ze\contentAdm::formatItemRow($item);
				}
				
				if (isset($item['menu_id'])) {
					//Handle the case where a content item has a translation but a menu node does not
					if ($path == 'zenario__content/panels/chained' && $item['menu'] === null) {
						$item['menu'] = ze\admin::phrase('[Menu Text missing]');
					} else {
						$item['linked'] = true;
						$item['menu'] = $item['menu_id'];
					}
					unset($item['menu_id']);
		
				} elseif ($item['status'] != 'trashed') {
					$item['unlinked'] = true;
					$item['menu'] = ze\admin::phrase('Not in menu');
					$item['cell_css_classes']['menu'] = 'orange';
				}
		
				if ($item['file_id']) {
					if ($item['file_path'] && !ze\file::docstorePath($item['file_path'])) {	
						$item['filename'] .= ' (File is missing)';
						$item['cell_css_classes']['filename'] = "warning";
					} else {
						$item['has_file'] = true;
				
						if (ze\file::isImageOrSVG($item['mime_type'])) {
							$item['has_picture'] = true;
						}
					}
				}
				if (!empty($item['s3_file_id'])) {
					$item['has_s3file'] = true;
					
				}

				if ($item['type'] == 'document' || $item['type'] == 'audio' || $item['type'] == 'video')  {
					$item['allow_copying_from_other'] = false;
					
				} else {
					$item['allow_copying_from_other'] = true;
					
				}
				
				if ($showInOrganiser || $mode == 'get_item_data') {
					$item['frontend_link'] = ze\link::toItemWithAlias(
						$item['id'], $item['type'], false, '', $item['alias'],
						$item['equiv_id'], $item['language_id']
					);
				}
		
				if ($mode == 'get_item_links') {
					$item['name'] = $item['tag'];
			
					if (ze::get('languageId') && $item['language_id'] != $_GET['languageId'] ?? false) {
						$item['name'] .= ' ('. $item['language_id']. ')';
					}
					
					switch ($item['status']) {
						case 'trashed':
							$item['navigation_path'] = 'zenario__content/panels/trashed_content_items//'. $id;
							break;
							
						case 'hidden':
							$item['navigation_path'] = 'zenario__content/panels/hidden_content_items//'. $id;
							break;
							
						default:
							$item['navigation_path'] = 'zenario__content/panels/content/refiners/content_type//'. $item['type']. '//'. $id;
							break;
					}
				}
				
				if (ze\content::isSpecialPage($item['id'], $item['type'])) {
					$item['is_special_page'] = true;
				}
			}
			
			//Content item privacy
			if (!empty($item['permissions'])) {
				switch ($item['permissions']) {
					case 'public':
						$item['permissions'] = ze\admin::phrase('Public');
						break;
					case 'logged_in':
						$item['permissions'] = ze\admin::phrase('Private (all extranet users)');
						break;
					case 'group_members':
						$groups = ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'group', 'link_from' => 'chain', 'link_from_id' => $item['equiv_id'], 'link_from_char' => $item['type']]);
						if (!empty($groups)) {
							$groupLabels = [];
							
							$groupCount = count($groups);
							
							if ($groupCount > 1) {
								$string = 'Private (groups [[group_labels]])';
							} else {
								$string = 'Private (group [[group_labels]])';
							}
							
							foreach ($groups as $groupId) {
								$groupLabels[] = ze\user::getGroupLabel($groupId);
							}
						}
						
						$item['permissions'] = ze\admin::phrase($string, ['group_labels' => implode(', ', $groupLabels)]);
						break;
					case 'in_smart_group':
					case 'logged_in_not_in_smart_group':
						$smartGroup = ze\smartGroup::details($item['permissions_smart_group_id']);
						
						if ($smartGroup) {
							if ($item['permissions'] == 'in_smart_group') {
								$item['permissions'] = ze\admin::phrase('Private (smart group [[smart_group_label]])', ['smart_group_label' => $smartGroup['name']]);
							} elseif ($item['permissions'] == 'logged_in_not_in_smart_group') {
								$item['permissions'] = ze\admin::phrase('Private (not in smart group [[smart_group_label]])', ['smart_group_label' => $smartGroup['name']]);
							}
						} else {
						
						}
						break;
					case 'with_role':
						if ($organisationManagerModuleRunning) {
							$roleIds = ze\row::getValues('group_link', 'link_to_id', ['link_to' => 'role', 'link_from' => 'chain', 'link_from_id' => $item['equiv_id'], 'link_from_char' => $item['type']]);
							
							if (!empty($roleIds)) {
								$roleLabels = [];
								
								$roleCount = count($roleIds);
								
								if ($roleCount > 1) {
									$string = 'Private (roles [[role_labels]])';
								} else {
									$string = 'Private (role [[role_labels]])';
								}
								
								foreach ($roleIds as $roleId) {
									$role = zenario_organization_manager::getRole($roleId);
									
									if ($role) {
										$roleLabels[] = $role['name'];
									}
								}
							}
							
							$item['permissions'] = ze\admin::phrase($string, ['role_labels' => implode(', ', $roleLabels)]);
						}
						
						break;
					case 'logged_out':
						$item['permissions'] = ze\admin::phrase('Public (hide from logged-in users)');
						break;
					case 'call_static_method':
						$item['permissions'] = ze\admin::phrase('Call a static method');
						break;
					case 'send_signal':
						$item['permissions'] = ze\admin::phrase('Send a signal');
						break;
				}
			}
		}


		//
		// Translation functionality
		//

		if ($path == 'zenario__content/panels/chained') {
			$numEquivs = 0;
			
			$aliasesArray = [];
			$aliasesQuery = ze\row::query('content_items', ['id', 'alias'], ['equiv_id' => $panel['key']['equivId'], 'type' => $panel['key']['cType']]);
			while ($aliasRow = ze\sql::fetchAssoc($aliasesQuery)) {
				$aliasesArray[$aliasRow['alias']][] = $aliasRow['id'];
			}

			$existingItemCount = 0;

			ze\lang::applyMergeFields($panel['item_buttons']['remove_translation_from_chain__non_identical_alias']['ajax']['confirm']['message'], ['default_lang' => $langs[ze::$defaultLang]['english_name']]);
			ze\lang::applyMergeFields($panel['item_buttons']['remove_translation_from_chain__identical_alias']['ajax']['confirm']['message'], ['default_lang' => $langs[ze::$defaultLang]['english_name']]);
			
			foreach ($panel['items'] as &$item) {
				$item['cell_css_classes']['tag'] = 'lang_flag_'. $item['language_id'];
		
				if ($item['id'] === null) {
					$item['css_class'] = 'content_chained_single ghost';
					$item['cell_css_classes']['tag'] .= ' ghost';
					$item['cell_css_classes']['language_id'] = 'ghost';
					$item['lang_name'] = ze\lang::name($item['language_id'], false, false);
					$item['tag'] = ze\admin::phrase('MISSING [[lang_name]] ([[language_id]])', $item);
					$item['ghost'] = true;
	
					//We used to have the ability for limited admins to have permissions on a specific language.
					//This would let them create translations in languages they had permissions for.
					//However this option was not being used and has been removed as of 9.2.
					#if ($checkSpecificPerms && ze\priv::onLanguage(false, $item['language_id'])) {
					#	$item['_specific_perms'] = true;
					#}
				} else {
					++$numEquivs;
					$existingItemCount++;

					if ($item['id'] == $cID && $item['type'] == $panel['key']['cType']) {
						$item['deletable'] = false;
					}

					if (isset($aliasesArray[$item['alias']])) {
						if (count($aliasesArray[$item['alias']]) > 1) {
							$item['has_identical_alias_to_other_items'] = true;
						} else {
							$item['has_identical_alias_to_other_items'] = false;
						}
					}
				}
			}

			if ($existingItemCount <= 1) {
				$panel['item_buttons']['remove_translation_from_chain__identical_alias']['hidden'] = $panel['item_buttons']['remove_translation_from_chain__non_identical_alias']['hidden'] = true;
			}
	
			if ($numEquivs < $numLanguages) {
				foreach ($panel['items'] as &$item) {
					$item['zenario_trans__can_link'] = true;
				}
			} else {
				unset($panel['collection_buttons']['zenario_trans__link_to_chain']);
			}
	
			if ($numEquivs > 1) {
				foreach ($panel['items'] as &$item) {
					$item['zenario_trans__linked'] = true;
				}
			}

		} elseif ($path != 'zenario__content/panels/trashed_content_items' && $path != 'zenario__content/panels/hidden_content_items' && $numLanguages > 1) {
	
			$langId = false;
			if (ze::in($refinerName, 'language', 'content_type__language', 'template__language')) {
				$langId = $panel['key']['language'];
			} elseif ($numLanguages > 1 && zenario_organizer::filterValue('language_id') == ze::$defaultLang) {
				$langId = zenario_organizer::filterValue('language_id');
			}
			
			if ($langId) {
				//$ord = $panel['columns']['zenario_trans__links']['ord'];
				$ord = 0.0;
				foreach($langs as $lang) {
					if ($lang['id'] != $langId) {
						$ord += 0.001;
						$panel['columns']['lang_'. $lang['id']] =
							[
								'ord' => 'zenario_trans__links + '. $ord,
								'title' => $lang['id'],
								'width' => 'xxsmall',
								'show_by_default' => (!ze::request('refiner__content_type') || ze::request('refiner__content_type') == 'html')
							];
					}
				}
			}
			
			foreach ($panel['items'] as $id => &$item) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id);
				$isGhost = !empty($item['ghost']);
				$item['zenario_trans__links'] = 1;
				
				if (!$isGhost || $mode == 'select') {
					$equivs = ze\content::equivalences($cID, $cType, $includeCurrent = $isGhost, $item['equiv_id']);
					if (!empty($equivs)) {
						foreach($langs as $lang) {
							if (!empty($equivs[$lang['id']])) {
								if ($lang['id'] != $item['language_id']) {
									++$item['zenario_trans__links'];
								}
								if ($langId && $lang['id'] != $langId) {
									$itemIconClass = ze\contentAdm::getItemIconClass($equivs[$lang['id']]['id'], $equivs[$lang['id']]['type'], true, $equivs[$lang['id']]['status']);
									$item['cell_css_classes']['lang_'. $lang['id']] =
										'zenario_trans_colicon ' . $itemIconClass;
								}
							}
						}
					}
				}
		
				if (!$isGhost && $item['zenario_trans__links'] < $numLanguages) {
					$item['zenario_trans__can_link'] = true;
				}
				if ($isGhost || $item['zenario_trans__links'] > 1) {
					$item['zenario_trans__linked'] = true;
				}
		
				if (!$isGhost || $mode == 'select') {
					if ($item['zenario_trans__links'] == 1) {
						$item['zenario_trans__links'] = ze\admin::phrase('untranslated');
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
		
		if (!isset($panel['collection_buttons']['create'])) {
			//Don't try to customise the create button if it's not there (e.g. due to permissions)
		} else
		if ((isset($_REQUEST['refinerName']) && $_REQUEST['refinerName']!='work_in_progress')) {
            
		    $panel['collection_buttons']['create']['label']  = "New ".ze\content::getContentTypeName(!empty($_REQUEST['refinerId'])? $_REQUEST['refinerId'] : $_REQUEST['refinerName']);
		    if ((isset($_REQUEST['refinerName']) && $_REQUEST['refinerName'] == "special_pages") || $path == 'zenario__content/panels/trashed_content_items' || $path == 'zenario__content/panels/hidden_content_items') {
		        $panel['collection_buttons']['create']['hidden'] = true;
		        $panel['collection_buttons']['new_node_dropdown']['hidden'] = true;
		    }
		   
		} else {//All content items
		    $panel['collection_buttons']['create']['hidden'] = true;
		    $j=0;  
                    
            foreach(ze\content::getContentTypes(true, true) as $content){
            	if (ze\priv::check('_PRIV_EDIT_DRAFT', false, $content['content_type_id'])) {
					$j++;
					$panel['collection_buttons']['new_node_'.$j]['label'] = $content['content_type_name_en']; 
					$panel['collection_buttons']['new_node_'.$j]['priv'] = '_PRIV_EDIT_DRAFT'; 
					$panel['collection_buttons']['new_node_'.$j]['hide_in_select_mode'] = $panel['collection_buttons']['new_node_'.$j]['hide_on_filter'] = true; 
					$panel['collection_buttons']['new_node_'.$j]['parent'] = 'new_dropdown'; 
					$panel['collection_buttons']['new_node_'.$j]['admin_box']['path'] = 'zenario_content'; 
					$panel['collection_buttons']['new_node_'.$j]['admin_box']['key']['target_cType'] = $content['content_type_id']; 
				}
            }
   
		}
		
		if (ze::setting('enable_aws_support') && ze::setting('allow_document_content_items_to_be_stored_on_aws_s3')
		 && $path != 'zenario__content/panels/chained'
		 && ze\module::inc('zenario_ctype_document')) {
			$panel['item_buttons']['download']['label'] = ze\admin::phrase('Download local file');
			$panel['item_buttons']['s3_download']['hidden'] = false;
		} else {
			$panel['item_buttons']['download']['label'] = ze\admin::phrase('Download');
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (ze::post('mass_add_to_menu') && ze\priv::check('_PRIV_CREATE_DELETE_MENU_ITEM')) {
			ze\menuAdm::addContentItems($ids, $ids2);

		} elseif (ze::post('hide')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowHide($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::hideContent($cID, $cType);
					}
				}
			}

		} elseif (ze::post('delist')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowDelist($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::delistContent($cID, $cType);
					}
				}
			}

		} elseif (ze::post('relist')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowRelist($cID, $cType) && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::relistContent($cID, $cType);
					}
				}
			}

		} elseif (ze::get('delete_trashed_items')) {
			//Set the "empty trash" confirmation message
			$dateToday = ze\date::ymd();
			$dateObject = ze\date::new($dateToday);
			$dateTwoWeeksAgo = $dateObject->modify('-14 days')->format('Y-m-d');
			
			$sql = "
				SELECT COUNT(*)
				FROM " . DB_PREFIX . "content_items c
				INNER JOIN " . DB_PREFIX . "content_item_versions v
					ON v.id = c.id
			  		AND v.type = c.type
			  		AND v.version = c.admin_version
			  	WHERE status = 'trashed'
			  	AND DATE(v.concealed_datetime) < '" . ze\escape::sql($dateTwoWeeksAgo) . "'";
			$result = ze\sql::select($sql);
			$count = ze\sql::fetchValue($result);
			
			$message = 'Empty the trash?
            	
            	This will permanently delete all content items in the trash that were trashed more than 14 days ago ([[count]] found). This cannot be undone.';
            echo ze\admin::phrase($message, ['count' => $count]);
		} elseif (ze::post('delete_trashed_items') && ze\priv::check('_PRIV_DELETE_TRASHED_CONTENT_ITEMS')) {
			$dateToday = ze\date::ymd();
			$dateObject = ze\date::new($dateToday);
			$dateTwoWeeksAgo = $dateObject->modify('-14 days')->format('Y-m-d');
			
			$sql = "
				SELECT c.id, c.type
				FROM " . DB_PREFIX . "content_items c
				INNER JOIN " . DB_PREFIX . "content_item_versions v
					ON v.id = c.id
			  		AND v.type = c.type
			  		AND v.version = c.admin_version
			  	WHERE status = 'trashed'
			  	AND DATE(v.concealed_datetime) < '" . ze\escape::sql($dateTwoWeeksAgo) . "'";
			$result = ze\sql::select($sql);
			while ($content = ze\sql::fetchAssoc($result)) {
				ze\contentAdm::deleteContentItem($content['id'], $content['type']);
			}

		} elseif (ze::post('lock')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					if (ze\priv::check('_PRIV_EDIT_DRAFT', $cID, $cType)) {
						ze\row::update('content_items', ['lock_owner_id' => ($_SESSION['admin_userid'] ?? false), 'locked_datetime' => ze\date::now()], ['id' => $cID, 'type' => $cType]);
					}
				}
			}
		} elseif (ze::get('unlock')) {
			// Set the "unlock" confirmation message
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					$contentInfo = ze\row::get('content_items', ['admin_version', 'lock_owner_id'], ['id'=>$cID, 'type'=>$cType]);
					$cVersion = $contentInfo['admin_version'];
					$adminDetails = ze\admin::details($contentInfo['lock_owner_id']);
					
					$date = ze\row::get('content_item_versions', 'scheduled_publish_datetime', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
					
					//Content items scheduled for publishing may not be unlocked.
					if (!$date) {
						if (ze\priv::check(false, $cID, $cType)) {
							echo ze\admin::phrase('Unlock this draft?');
						} else {
							echo ze\admin::phrase('Are you sure that you wish to force-unlock this draft?');
						}
						
						echo ' ';
						echo ze\admin::phrase('Other administrators will then be able to edit it.');
					}
				}
			}
		
		} elseif (ze::post('unlock')) {
			//Unlock content items
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					if (ze\priv::check('_PRIV_CANCEL_CHECKOUT') || ze\priv::check(false, $cID, $cType)) {
						$cVersion = ze\row::get('content_items', 'admin_version', ['id'=>$cID, 'type'=>$cType]);
						
						$date = ze\row::get('content_item_versions', 'scheduled_publish_datetime', ['id' => $cID, 'type' => $cType, 'version' => $cVersion]);
						
						//Content items scheduled for publishing may not be unlocked.
						if (!$date) {
							ze\row::update('content_items', ['lock_owner_id' => 0, 'locked_datetime' => null], ['id' => $cID, 'type' => $cType]);
						}
					}
				}
			}
	
		} elseif ((ze::post('create_draft') || ze::post('redraft')) && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (($content = ze\row::get('content_items', ['id', 'type', 'status', 'admin_version', 'visitor_version'], ['tag_id' => $id]))
				 && (ze\priv::check('_PRIV_EDIT_DRAFT', $content['id'], $content['type']))) {
			
					if (ze::post('create_draft') && ze\content::isDraft($content['status'])) {
						continue;
					} elseif (ze::post('redraft') && !ze::in($content['status'],  'hidden', 'trashed')) {
						continue;
					}
			
					$cVersionTo = false;
					ze\contentAdm::createDraft($content['id'], $content['id'], $content['type'], $cVersionTo, ze::post('cVersion') ?: $content['admin_version']);
			
					if (ze::get('method_call') == 'handleAdminToolbarAJAX') {
						$_SESSION['last_item'] = $content['type']. '_'. $content['id']. '.'. $cVersionTo;
				
						if (ze::request('switch_to_edit_mode')) {
							$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit';
						}
					}
				}
			}

		} elseif (ze::get('create_draft_by_copying')) {
			$message = "
				Copy the contents of the content item [[source_content_item]] over the content item [[target_content_item]]?
                                
                This will create a draft of the current content item with the contents of the one you selected.";
            
            $replace = [
            	'source_content_item' => ze\content::formatTagFromTagId($ids2),
            	'target_content_item' => ze\content::formatTagFromTagId($ids)
            ];
            
            $currentAlias = ze\row::get('content_items', 'alias', ['tag_id' => $ids]);
            if ($currentAlias) {
            	$message .= "
            		
            		This item will keep its alias \"[[current_alias]]\", but its content (meta data, version-controlled content, plugins, nests and slideshows) and choice of layout will be overwritten.";
            	$replace['current_alias'] = $currentAlias;
            }
            
            if (ze\lang::count() > 1) {
            	$sourceContentItemLang = ze\row::get('content_items', 'language_id', ['tag_id' => $ids2]);
            	$targetContentItemLang = ze\row::get('content_items', 'language_id', ['tag_id' => $ids]);
            	
            	if ($sourceContentItemLang != $targetContentItemLang) {
            		$message .= "
            			
            			Note that you are copying from an item in [[source_content_item_lang]] over an item in [[target_content_item_lang]].";
            		
            		$replace['source_content_item_lang'] = ze\lang::name($sourceContentItemLang);
            		$replace['target_content_item_lang'] = ze\lang::name($targetContentItemLang);
            	}
            }
            
			echo ze\admin::phrase($message, $replace);
		} elseif (ze::get('create_draft_by_overwriting')) {
			$message = "
            	Copy the contents of the content item [[source_content_item]] over the current draft of the content item [[target_content_item]]?";
			
			$replace = [
            	'source_content_item' => ze\content::formatTagFromTagId($ids2),
            	'target_content_item' => ze\content::formatTagFromTagId($ids)
            ];
            
            $currentAlias = ze\row::get('content_items', 'alias', ['tag_id' => $ids]);
            if ($currentAlias) {
            	$message .= "
            		
            		This item will keep its alias \"[[current_alias]]\", but its content (meta data, version-controlled content, plugins, nests and slideshows) and choice of layout will be overwritten.";
            	$replace['current_alias'] = $currentAlias;
            }
            
            $message .= "
            	
            	This cannot be undone.";
			
			if (ze\lang::count() > 1) {
            	$sourceContentItemLang = ze\row::get('content_items', 'language_id', ['tag_id' => $ids2]);
            	$targetContentItemLang = ze\row::get('content_items', 'language_id', ['tag_id' => $ids]);
            	
            	if ($sourceContentItemLang != $targetContentItemLang) {
            		$message .= "
            			
            			Note that you are copying from an item in [[source_content_item_lang]] over an item in [[target_content_item_lang]].";
            		
            		$replace['source_content_item_lang'] = ze\lang::name($sourceContentItemLang);
            		$replace['target_content_item_lang'] = ze\lang::name($targetContentItemLang);
            	}
            }
            
			echo ze\admin::phrase($message, $replace);
		} elseif (
			(ze::post('create_draft_by_copying') && ze\priv::check('_PRIV_EDIT_DRAFT'))
			|| (ze::post('create_draft_by_overwriting') && ze\priv::check('_PRIV_EDIT_DRAFT'))
		) {
			$sourceCID = $sourceCType = false;
			if (ze\content::getCIDAndCTypeFromTagId($sourceCID, $sourceCType, $ids2)
			 && ($content = ze\row::get('content_items', ['id', 'type', 'status'], ['tag_id' => $ids]))
			 && (ze\priv::check('_PRIV_EDIT_DRAFT', $content['id'], $content['type']))) {
				$hasDraft = ze\content::isDraft($content['status']);
		
				if (!$hasDraft || ze\priv::check('_PRIV_EDIT_DRAFT', $content['id'], $content['type'])) {
					if ($hasDraft) {
						ze\contentAdm::deleteDraft($content['id'], $content['type'], false);
					}
			
					$cVersionTo = false;
					ze\contentAdm::createDraft($content['id'], $sourceCID, $content['type'], $cVersionTo);
				}
			}

		} elseif (ze::post('delete_archives') && ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if ((ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id))
				 && (ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM', $cID, $cType))) {
					ze\contentAdm::deleteArchive($cID, $cType);
				}
			}
			
		} elseif (ze::post('add_existing_translation_to_chain') && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
			$cID = $cType = false;
			if ((ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids2))
			 && ($equivId = (int) ($_POST['equivId'] ?? 0))) {
				ze\contentAdm::recordEquivalence($equivId, $cID, $cType);
			}
		} elseif (ze::post('remove_translation_from_chain') && ze\priv::check('_PRIV_EDIT_DRAFT')) {
			
			$cID = $cType = false;
			if ((ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids))
			 && ($equivId = (int) ($_POST['equivId'] ?? 0))) {
				ze\contentAdm::removeEquivalence($cID, $cType);
			}
		}
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		$cID = $cType = false;
		
		if (ze::post('download') && ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			//Offer a download for a file being used for a Content Item
			header('location: '. ze\link::absolute(). 'zenario/file.php?usage=content&cID='. $cID. '&cType='. $cType);
			exit;
		}
		//For s3 download files
		if (ze::post('s3_download') && ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
			$presignedUrl = '';	
			$targetVersion = ze\content::showableVersion($cID,$cType);
			$version = ze\row::get('content_item_versions',['s3_file_id'],['id'=> $cID, 'version'=> $targetVersion, 'type'=> $cType]);
			$fileDetails = ze\row::get('files', ['filename','path'], $version['s3_file_id']);
			if ($fileDetails['path']) {
				$fileName = $fileDetails['path'].'/'.$fileDetails['filename'];
			} else {
				$fileName = $fileDetails['filename'];
			}
			if ($fileName) {
				if (ze\module::inc('zenario_ctype_document')) {
					$presignedUrl = zenario_ctype_document::getS3FilePresignedUrl($fileName);
				}
				if ($presignedUrl) {
					echo '<script type="text/javascript">
							window.open("'. $presignedUrl .'", "download")

						 </script>';
				}
			}
			exit;
		}
		
	}
}
