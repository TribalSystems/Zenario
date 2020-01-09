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


class zenario_common_features__organizer__content extends ze\moduleBaseClass {
	
	protected $numSyncAssistLangs = 0;
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if (ze::in($mode, 'full', 'quick', 'select', 'typeahead_search', 'get_matched_ids')
		 && !isset($_GET['refiner__trash'])
		 && isset($panel['db_items']['custom_where_statement__not_trashed'])) {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__not_trashed'];
		}
		
		//Have a refiner that enforces the language filter be set.
		if ($mode != 'typeahead_search'
		 && isset($_GET['refiner__filter_by_lang'])
		 && !isset($_GET['refiner__zenario_trans__chained_in_link'])) {
			
			//If it's not set, set it to one language initially
			if (!$langIdFilter = zenario_organizer::filterValue('language_id')) {
				
				//If an item was selected, use the language from that...
				if ($_REQUEST['_item'] ?? false) {
					$langIdFilter = ze\row::get('content_items', 'language_id', ['tag_id' => ($_REQUEST['_item'] ?? false)]);
				}
				//...otherwise use the default language
				if (!$langIdFilter) {
					$langIdFilter = ze::$defaultLang;
				}
				
				zenario_organizer::setFilterValue('language_id', $langIdFilter);
			}
			unset($panel['quick_filter_buttons']['all_languages']);
		}
		
		//Check if a specific Content Type and/or layout has been set, either by using a content-type refiner or a layout's refiner
		if ($_GET['refiner__template'] ?? false) {
			$panel['key']['layoutId'] = $_GET['refiner__template'] ?? false;
			$panel['key']['cType'] = ze\row::get('layouts', 'content_type', ($_GET['refiner__template'] ?? false));
			
			//When viewing content items that use a particular layout,
			//hide "Create" and "Export" buttons, and Settings Dropdown.
			$panel['collection_buttons']['create']['hidden'] = true;
			$panel['collection_buttons']['export']['hidden'] = true;
			$panel['collection_buttons']['settings_dropdown']['hidden'] = true;
		} elseif ($_GET['refiner__content_type'] ?? false) {
			$panel['key']['cType'] = $_GET['refiner__content_type'] ?? false;
		}
		
		
		//Check which content type we're displaying and whether the current admin has rights to create content items of that type.
		$checkPermsOnCType = $panel['key']['cType'] ?? '';
		$hasPermsOnCType = ze\priv::check('_PRIV_CREATE_FIRST_DRAFT', false, $checkPermsOnCType);
		
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
			}
	
			//Task #9514: Release Date should always be visible if you are looking at a Content Type where it is mandatory.
			if ($details = ze\contentAdm::cTypeDetails($panel['key']['cType'])) {
				foreach ([
					'writer_field' => 'writer_name',
					'description_field' => 'description',
					'keywords_field' => 'keywords',
					//'summary_field' => '...',
					'release_date_field' => 'release_date'
				] as $fieldName => $columnName) {
					if (!isset($details[$fieldName])) {
					
					} elseif ($details[$fieldName] == 'mandatory') {
						$panel['columns'][$columnName]['always_show'] = true;
		
					} elseif ($details[$fieldName] == 'hidden') {
						$panel['columns'][$columnName]['hidden'] = true;
					}
				}
				
				if ($refinerName == 'content_type') {
					unset($panel['collection_buttons']['help']['hide_on_refiner']);
					$panel['collection_buttons']['help']['help']['message'] =
						ze\admin::phrase('Every page of your website is stored as a "content item". This panel shows all of the [[content_type_plural_lower_en]] of your site in a list view.',
							$details);
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
				//'summary_field' => '...',
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
			
			//Create page preview buttons
			$pagePreviews = ze\row::getAssocs('page_preview_sizes', ['width', 'height', 'description', 'ordinal', 'is_default'], [], 'ordinal');
			foreach ($pagePreviews as $pagePreview) {
				$width = $pagePreview['width'];
				$height = $pagePreview['height'];
				$description = $pagePreview['description'];
	
				$pagePreviewButton = [
					'parent' => 'page_preview_sizes',
					'label' => $width.' × '.$height.', '.$description,
					'onclick' => "zenarioA.showPagePreview(". (int) $width. ", ". (int) $height. ", '". ze\escape::js($description). "')"
				];
			
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
		
		$numLanguages = ze\lang::count();
		if ($numLanguages < 2) {
			unset($panel['columns']['sync_assist']);
		} else {
			$syncAssistLangs = ze\row::getValues('languages', 'id', ['sync_assist' => 1, 'id' => ['!' => ze::$defaultLang]]);
			if ($this->numSyncAssistLangs = count($syncAssistLangs)) {
				define('ZENARIO_SYNC_ASSIST_LANGS', ze\escape::in($syncAssistLangs, 'sql'));
			} else {
				unset($panel['columns']['sync_assist']);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {

		if($contentType=($_REQUEST['refiner__content_type'] ?? false)){
			if(ze\row::exists('content_types', ['enable_categories' => 0, 'content_type_id'=>$contentType])){
				unset($panel['inline_buttons']['no_categories']);
				unset($panel['inline_buttons']['one_or_more_categories']);
			}
		}
		
		$numLanguages = count($langs = ze\lang::getLanguages());
		
		//If this panel is for a specific language, don't show the language filter
		//and also set the language id in the key so any FABs that open default to that language.
		if ($panel['key']['language']) {
			$panel['key']['language'] = $panel['key']['language'];
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
		
		//If we're showing trashed items, don't show the status filter
		if (isset($_GET['refiner__trash'])
		 || !ze::in($mode, 'full', 'quick', 'select')) {
			unset($panel['quick_filter_buttons']['status']);
			unset($panel['quick_filter_buttons']['any_status']);
			unset($panel['quick_filter_buttons']['first_draft']);
			unset($panel['quick_filter_buttons']['published_with_draft']);
			unset($panel['quick_filter_buttons']['published']);
			unset($panel['quick_filter_buttons']['hidden']);
		
		//Otherwise if the status filter is set, make sure to change the label of the parent to what was chosen
		} else
		 if (($statusFilter = zenario_organizer::filterValue('status'))
		  && (!empty($panel['quick_filter_buttons'][$statusFilter]['label']))) {
			$panel['quick_filter_buttons']['status']['label'] =
				$panel['quick_filter_buttons'][$statusFilter]['label'];
		}
		
		//If this panel is for a specific layout, don't show the layout filter
		if ($panel['key']['layoutId'] || !ze::in($mode, 'full', 'quick', 'select')) {
			unset($panel['quick_filter_buttons']['layout']);
			unset($panel['quick_filter_buttons']['all_layouts']);
		
		} else {
			$sql = "
				SELECT
					layout_id, name,
					CONCAT('L', IF (layout_id < 10, LPAD(CAST(layout_id AS CHAR), 2, '0'), CAST(layout_id AS CHAR)), ' ', name) AS id_and_name
				FROM ". DB_PREFIX. "layouts
				WHERE status = 'active'";
			
			if ($panel['key']['cType']) {
				$sql .= "
				  AND content_type = '". ze\escape::sql($panel['key']['cType']). "'";
			}
			
			$sql .= "
				ORDER BY layout_id";
			
			//Check the current filter, if there is one
			$layoutIdFilter = zenario_organizer::filterValue('layout_id');
			
			//For each layout, add a filter option
			$ord = 1000;
			$result = ze\sql::select($sql);
			while ($layout = ze\sql::fetchAssoc($result)) {
				
				$label = ze\admin::phrase('[[id_and_name]]', $layout);
				
				$panel['quick_filter_buttons']['layout_'. $layout['layout_id']] = [
					'ord' => ++$ord,
					'parent' => 'layout',
					'column' => 'layout_id',
					'label' => $label,
					'value' => $layout['layout_id']
				];
				
				//If the layout was chosen, change the text on the parent-button
				if ($layoutIdFilter == $layout['layout_id']) {
					$panel['quick_filter_buttons']['layout']['label'] = $label;
				}
				
			}
		}
		
		
		
		
		
		
		if (isset($_GET['refiner__trash']) && !$panel['key']['layoutId']) {
			$panel['title'] = ze\admin::phrase('Trashed content items');
			$panel['no_items_message'] = ze\admin::phrase('There are no trashed content items.');
			$panel['item']['css_class'] = 'content_trashed';
			unset($panel['columns']['status']);
			unset($panel['collection_buttons']['create']);

		} elseif ($_GET['refiner__following_item_link'] ?? false) {
			$panel['title'] = ze\admin::phrase('Linked content item');
			unset($panel['collection_buttons']['create']);
			unset($panel['item_buttons']['trash']);
			unset($panel['item_buttons']['delete']);

		} elseif (($_GET['refinerName'] ?? false) == 'find_duplicates') {
			$panel['title'] = ze\admin::phrase('Items with duplicate file attachments');
			$panel['no_items_message'] = ze\admin::phrase('No items with duplicate file attachments found');
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
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $refinerId);
				$panel['return_if_empty'] = true;
	
			} elseif ($refinerName == 'zenario_trans__chained_in_link__from_menu_node' && ($menu = ze\menu::details($refinerId))) {
				$cID = $menu['equiv_id'];
				$cType = $menu['content_type'];
			}
	
			$panel['title'] = ze\admin::phrase('Translations of "[[tag]]"', ['tag' => ze\content::formatTag($cID, $cType, -1, false, true), 'lang_id' => ze\content::langId($cID, $cType)]);
			$panel['label_format_for_grid_view'] = "[[tag]] \n [[language_id]]";
	
			if (isset($panel['item_buttons']['create_translation'])) {
				$panel['item_buttons']['create_translation']['tooltip'] =
					ze\admin::phrase('Duplicate "[[tag]]" ([[language_id]]) to create a translation in [[lang_name]]', ['tag' => ze\content::formatTag($cID, $cType), 'language_id' => ze\content::langId($cID, $cType)]);
			}

		} elseif ($panel['key']['layoutId'] && $panel['key']['language']) {
			$template = ze\row::get('layouts', ['layout_id', 'name'], $panel['key']['layoutId']);
			$mrg = [
				'layout_id' => str_pad($template['layout_id'], 2, '0', STR_PAD_LEFT),
				'template' => $template['name'],
				'language' => ze\lang::name($panel['key']['language'])];
			$panel['label_format_for_grid_view'] = '[[tag]]';
			$panel['title'] = ze\admin::phrase('Content items using the layout "L[[layout_id]] [[template]]" in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using layout "L[[layout_id]] [[template]]" in [[language]].', $mrg);

		} elseif ($panel['key']['cType'] && $panel['key']['language']) {
			$mrg = [
				'ctype' => ze\content::getContentTypeName($panel['key']['cType']),
				'language' => ze\lang::name($panel['key']['language'])];
			$panel['title'] = ze\admin::phrase('[[ctype]] content items in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no [[ctype]] content items in [[language]].', $mrg);
			$panel['columns']['language_id']['hidden'] = true;
			unset($panel['columns']['type']);

		} elseif ($panel['key']['layoutId']) {
			$template = ze\row::get('layouts', ['layout_id', 'name'], $panel['key']['layoutId']);
			$mrg = [
				'layout_id' => str_pad($template['layout_id'], 2, '0', STR_PAD_LEFT),
				'template' => $template['name']];
			$panel['title'] = ze\admin::phrase('Content items using the layout "L[[layout_id]] [[template]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using layout "L[[layout_id]] [[template]]".', $mrg);

		} elseif ($panel['key']['cType']) {
			$panel['item']['css_class'] = 'content_type_'. $panel['key']['cType'];
			$mrg = ze\contentAdm::cTypeDetails($panel['key']['cType']);
			
			$panel['title'] = ze\admin::phrase('[[content_type_plural_en]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no [[content_type_plural_lower_en]].', $mrg);
			unset($panel['columns']['type']);

		} elseif ($_GET['refiner__menu_children'] ?? false) {
			$mrg = [
				'name' => ze\menu::name($_GET['refiner__menu_children'] ?? false, true)];
			$panel['title'] = ze\admin::phrase('Content items under the menu node "[[name]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items under the menu node "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($panel['key']['language']) {
			$mrg = [
				'language' => ze\lang::name($panel['key']['language'])];
			$panel['title'] = ze\admin::phrase('Content items in [[language]]', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items in [[language]].', $mrg);
			$panel['columns']['language_id']['hidden'] = true;

		} elseif ($_GET['refiner__category'] ?? false) {
			$mrg = [
				'category' => ze\category::name($_GET['refiner__category'] ?? false)];
			$panel['title'] = ze\admin::phrase('Content items in the category "[[category]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items in the category "[[category]]".', $mrg);

		} elseif ($_GET['refiner__module_usage'] ?? false) {
			$mrg = [
				'name' => ze\module::displayName($_GET['refiner__module_usage'] ?? false)];
			$panel['title'] = ze\admin::phrase('Content items on which module "[[name]]" is used', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the module "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($_GET['refiner__module_effective_usage'] ?? false) {
			$mrg = [
				'name' => ze\module::displayName($_GET['refiner__module_effective_usage'] ?? false)];
			$panel['title'] = ze\admin::phrase('Content items on which module "[[name]]" is used (effective usage)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the module "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($_GET['refiner__plugin_instance_usage'] ?? false) {
			$mrg = [
				'name' => ze\plugin::name($_GET['refiner__plugin_instance_usage'] ?? false)];
			$panel['title'] = ze\admin::phrase('Content items on which the plugin "[[name]]" is used', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the plugin "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($_GET['refiner__plugin_instance_effective_usage'] ?? false) {
			$mrg = [
				'name' => ze\plugin::name($_GET['refiner__plugin_instance_effective_usage'] ?? false)];
			$panel['title'] = ze\admin::phrase('Content items on which the plugin "[[name]]" appears (effective usage)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no content items using the plugin "[[name]]".', $mrg);
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'special_pages') {
			$panel['title'] = ze\admin::phrase('Special Pages for the Default Language ([[lang]])', ['lang' => ze::$defaultLang]);
			$panel['item']['css_class'] = 'special_content_published';
			unset($panel['collection_buttons']['create']);

		} elseif ($refinerName == 'work_in_progress') {
			$panel['title'] = ze\admin::phrase('Work in progress');
			$panel['no_items_message'] = ze\admin::phrase('There are no draft content items.');
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
		}


		$panel['columns']['type']['values'] = [];
		foreach (ze\content::getContentTypes() as $cType) {
			$panel['columns']['type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
		}
        
		//If this is full, quick or select mode, and the admin looking at this only has permissions
		//to edit specific content items, we'll need to check if the current admin can edit each
		//content item.
		$showInOrganiser = ze::in($mode, 'full', 'quick', 'select');
		$checkSpecificPerms = $showInOrganiser && ze\admin::hasSpecificPerms();

		foreach ($panel['items'] as $id => &$item) {
			
			//Show last modified date and admin who modified it (WIP dropdown).
			if ($item['last_author_id'] != 0) {
				$item['last_modified_by_admin'] = ze\admin::formatName($item['last_author_id']);
			} else {
				$item['last_modified_by_admin'] = ze\admin::formatName($item['creating_author_id']);
			}
			
			if (!empty($item['created_datetime'])) {
				$item['created_datetime'] = ze\date::relative($item['created_datetime'], "day", true, 'vis_date_format_med', $languageId = \ze::$defaultLang, true, false, false, $showAdminPhrase = true);
			}
			
			if (!empty($item['last_modified_datetime'])) {
				$item['last_modified_datetime'] = ze\date::relative($item['last_modified_datetime'], "day", true, 'vis_date_format_med', $languageId = \ze::$defaultLang, true, false, false, $showAdminPhrase = true);
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

		
				$item['tag'] = ze\content::formatTag($item['id'], $item['type'], $item['alias'], $item['language_id']);
		
				$item['traits'] = [];
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
		
				if (!$item['lock_owner_id'] || $item['lock_owner_id'] == ($_SESSION['admin_userid'] ?? false)) {
					$item['traits']['not_locked'] = true;
				}
		
				if ($item['status'] == 'published') {
					$item['traits']['published'] = true;
				}
				if ($item['status'] == 'hidden') {
					$item['traits']['hidden'] = true;
				}
				if (ze\contentAdm::allowDelete($item['id'], $item['type'], $item['status'])) {
					$item['traits']['deletable'] = true;
				}
				if (ze\contentAdm::allowTrash($item['id'], $item['type'], $item['status'], $item['last_author_id'])) {
					$item['traits']['trashable'] = true;
				}
				if (ze\contentAdm::allowHide($item['id'], $item['type'], $item['status'])) {
					$item['traits']['hideable'] = true;
				}
		
				if (isset($item['menu_id'])) {
					//Handle the case where a content item has a translation but a menu node does not
					if ($path == 'zenario__content/panels/chained' && $item['menu'] === null) {
						$item['menu'] = ze\admin::phrase('[Menu Text missing]');
					} else {
						$item['traits']['linked'] = true;
						$item['menu'] = $item['menu_id'];
					}
					unset($item['menu_id']);
		
				} elseif ($item['status'] != 'trashed') {
					$item['traits']['unlinked'] = true;
					$item['menu'] = ze\admin::phrase('Orphaned');
					$item['cell_css_classes']['menu'] = 'orange';
				}
		
				if ($item['file_id']) {
					if ($item['file_path'] && !ze\file::docstorePath($item['file_path'])) {	
						$item['filename'] .= ' (File is missing)';
						$item['cell_css_classes']['filename'] = "warning";
					} else {
						$item['traits']['has_file'] = true;
				
						if (ze\file::isImageOrSVG($item['mime_type'])) {
							$item['traits']['has_picture'] = true;
						}
					}
				}
				
				if ($mode === 'full' || $mode == 'get_item_data') {
					$item['frontend_link'] = ze\link::toItem(
						$item['id'], $item['type'], false, '&zenario_sk_return=navigation_path', $item['alias'],
						$autoAddImportantRequests = false, $forceAliasInAdminMode = false,
						$item['equiv_id'], $item['language_id']
					);
				}
		
				if ($mode == 'get_item_links') {
					$item['name'] = $item['tag'];
			
					if (($_GET['languageId'] ?? false) && $item['language_id'] != $_GET['languageId'] ?? false) {
						$item['name'] .= ' ('. $item['language_id']. ')';
					}
			
					$item['navigation_path'] = 'zenario__content/panels/content//'. $id;
					
				}
			}
	
			if (isset($item['sync_assist'])
			 && $item['sync_assist'] < $this->numSyncAssistLangs) {
				
				$item['cell_css_classes']['zenario_trans__links'] = 'orange';
			}
			unset($item['sync_assist']);
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
					$item['lang_name'] = ze\lang::name($item['language_id'], false, false);
					$item['tag'] = ze\admin::phrase('MISSING [[lang_name]] ([[language_id]])', $item);
					$item['traits']['ghost'] = true;
	
					if ($checkSpecificPerms && ze\priv::onLanguage(false, $item['language_id'])) {
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
			if (ze::in($refinerName, 'language', 'content_type__language', 'template__language')) {
				$langId = $panel['key']['language'];
			} elseif ($numLanguages > 1 && zenario_organizer::filterValue('language_id') == ze::$defaultLang) {
				$langId = zenario_organizer::filterValue('language_id');
			}
			
			if ($langId) {
				$ord = $panel['columns']['zenario_trans__links']['ord'];
				foreach($langs as $lang) {
					if ($lang['id'] != $langId) {
						$panel['columns']['lang_'. $lang['id']] =
							[
								'ord' => $ord += 0.001,
								'title' => $lang['id'],
								'width' => 'xxsmall',
								'show_by_default' => (!($_REQUEST['refiner__content_type'] ?? false) || ($_REQUEST['refiner__content_type'] ?? false) == 'html')
							];
					}
				}
			}
			
			foreach ($panel['items'] as $id => &$item) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id);
				$isGhost = !empty($item['traits']['ghost']);
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
					$item['traits']['zenario_trans__can_link'] = true;
				}
				if ($isGhost || $item['zenario_trans__links'] > 1) {
					$item['traits']['zenario_trans__linked'] = true;
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
		if((isset($_REQUEST['refinerName']) && $_REQUEST['refinerName']!='work_in_progress') ){
            
		    $panel['collection_buttons']['create']['label']  = "New ".ze\content::getContentTypeName(!empty($_REQUEST['refinerId'])? $_REQUEST['refinerId'] : $_REQUEST['refinerName']);
		    if(isset($_REQUEST['refinerName'])  && ($_REQUEST['refinerName'] == "trash" || $_REQUEST['refinerName'] == "special_pages")){
		        $panel['collection_buttons']['create']['hidden'] = true;
		        $panel['collection_buttons']['new_node_dropdown']['hidden'] = true;
		    } 
		   
		}else {//All content items
		    $panel['collection_buttons']['create']['hidden'] = true;
		    $j=0;  
                    
            foreach(ze\content::getContentTypes() as $content){
            	if (ze\priv::check('_PRIV_CREATE_FIRST_DRAFT', false, $content['content_type_id'])) {
					$j++;
					$panel['collection_buttons']['new_node_'.$j]['label'] = $content['content_type_name_en']; 
					$panel['collection_buttons']['new_node_'.$j]['priv'] = '_PRIV_CREATE_FIRST_DRAFT'; 
					$panel['collection_buttons']['new_node_'.$j]['hide_in_select_mode'] = $panel['collection_buttons']['new_node_'.$j]['hide_on_filter'] = true; 
					$panel['collection_buttons']['new_node_'.$j]['parent'] = 'new_dropdown'; 
					$panel['collection_buttons']['new_node_'.$j]['admin_box']['path'] = 'zenario_content'; 
					$panel['collection_buttons']['new_node_'.$j]['admin_box']['key']['target_cType'] = $content['content_type_id']; 
				}
            }    
   
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (ze::post('mass_add_to_menu') && ze\priv::check('_PRIV_ADD_MENU_ITEM')) {
			ze\menuAdm::addContentItems($ids, $ids2);

		} elseif (ze::post('hide')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowHide($cID, $cType) && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::hideContent($cID, $cType);
					}
				}
			}

		} elseif (ze::post('trash')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowTrash($cID, $cType) && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType)) {
						ze\contentAdm::trashContent($cID, $cType);
					}
				}
			}

		} elseif (ze::post('delete')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id)) {
					if (ze\contentAdm::allowDelete($cID, $cType) && ze\priv::check('_PRIV_DELETE_DRAFT', $cID, $cType)) {
						ze\contentAdm::deleteDraft($cID, $cType);
					}
				}
			}

		} elseif (ze::post('delete_trashed_items') && ze\priv::check('_PRIV_DELETE_TRASHED_CONTENT_ITEMS')) {
			$result = ze\row::query('content_items', ['id', 'type'], ['status' => 'trashed']);
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
		// Set unlock ajax message
		} elseif ($_GET['unlock'] ?? false) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					$contentInfo = ze\row::get('content_items', ['admin_version', 'lock_owner_id'], ['id'=>$cID, 'type'=>$cType]);
					$cVersion = $contentInfo['admin_version'];
					$adminDetails = ze\admin::details($contentInfo['lock_owner_id']);
					echo 'Are you sure that you wish to ';
					if (!ze\priv::check(false, $cID, $cType)) {
						echo 'force-';
					}
					echo 'unlock on this content item? ';
					if ($date = ze\row::get('content_item_versions', 'scheduled_publish_datetime', ['id'=>$cID,'type'=>$cType,'version'=>$cVersion])) {
						echo 'It is scheduled to be published by '.$adminDetails['first_name'].' '.$adminDetails['last_name'].' on '. ze\admin::formatDateTime($date, 'vis_date_format_long');
					} else {
						echo 'Any administrator who has authoring permission will be able to make changes to it.';
					}
				}
			}
		// Unlock a content item
		} elseif (ze::post('unlock')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
			if (ze\content::getCIDAndCTypeFromTagId($cID, $cType, $ids)) {
					// Unlock the item & remove scheduled publication
					if (ze\priv::check('_PRIV_CANCEL_CHECKOUT') || ze\priv::check(false, $cID, $cType)) {
						$cVersion = ze\row::get('content_items', 'admin_version', ['id'=>$cID, 'type'=>$cType]);
						ze\row::update('content_item_versions', ['scheduled_publish_datetime' => null], ['id'=>$cID,'type'=>$cType,'version'=>$cVersion]);
						ze\row::update('content_items', ['lock_owner_id' => 0, 'locked_datetime' => null], ['id' => $cID, 'type' => $cType]);
					}
				}
			}
	
		} elseif ((ze::post('create_draft') || ze::post('redraft')) && ze\priv::check('_PRIV_CREATE_REVISION_DRAFT')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (($content = ze\row::get('content_items', ['id', 'type', 'status', 'admin_version', 'visitor_version'], ['tag_id' => $id]))
				 && (ze\priv::check('_PRIV_CREATE_REVISION_DRAFT', $content['id'], $content['type']))) {
			
					if (ze::post('create_draft') && ze\content::isDraft($content['status'])) {
						continue;
					} elseif (ze::post('redraft') && !ze::in($content['status'],  'hidden', 'trashed')) {
						continue;
					}
			
					$cVersionTo = false;
					ze\contentAdm::createDraft($content['id'], $content['id'], $content['type'], $cVersionTo, ze::post('cVersion') ?: $content['admin_version']);
			
					if (($_GET['method_call'] ?? false) == 'handleAdminToolbarAJAX') {
						$_SESSION['last_item'] = $content['type']. '_'. $content['id'];
				
						if ($_REQUEST['switch_to_edit_mode'] ?? false) {
							$_SESSION['page_mode'] = $_SESSION['page_toolbar'] = 'edit';
						}
					}
				}
			}

		} elseif (ze::post('create_draft_by_copying') && ze\priv::check('_PRIV_CREATE_REVISION_DRAFT')) {
			$sourceCID = $sourceCType = false;
			if (ze\content::getCIDAndCTypeFromTagId($sourceCID, $sourceCType, $ids2)
			 && ($content = ze\row::get('content_items', ['id', 'type', 'status'], ['tag_id' => $ids]))
			 && (ze\priv::check('_PRIV_CREATE_REVISION_DRAFT', $content['id'], $content['type']))) {
				$hasDraft =
					$content['status'] == 'first_draft'
				 || $content['status'] == 'published_with_draft'
				 || $content['status'] == 'hidden_with_draft'
				 || $content['status'] == 'trashed_with_draft';
		
				if (!$hasDraft || ze\priv::check('_PRIV_DELETE_DRAFT', $content['id'], $content['type'])) {
					if ($hasDraft) {
						ze\contentAdm::deleteDraft($content['id'], $content['type'], false);
					}
			
					$cVersionTo = false;
					ze\contentAdm::createDraft($content['id'], $sourceCID, $content['type'], $cVersionTo);
				}
			}

		} elseif (ze::post('delete_archives') && ze\priv::check('_PRIV_HIDE_CONTENT_ITEM')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				$cID = $cType = false;
				if ((ze\content::getCIDAndCTypeFromTagId($cID, $cType, $id))
				 && (ze\priv::check('_PRIV_HIDE_CONTENT_ITEM', $cID, $cType))) {
					ze\contentAdm::deleteArchive($cID, $cType);
				}
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
		
	}
}