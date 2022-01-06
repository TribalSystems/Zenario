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


class zenario_common_features__organizer__categories extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__content/panels/categories') return;
		
		if (!$refinerName && !ze::in($mode, 'typeahead_search', 'get_item_name', 'get_item_links')) {
			$panel['title'] = ze\admin::phrase('Categories (top level)');
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_top_level'];
		}
		
		if ($refinerName && $refinerName != 'parent_category') {
			unset($panel['item']['link']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__content/panels/categories') return;
		
		$langs = ze\lang::getLanguages();
		foreach($langs as $lang) {
			$panel['columns']['lang_'. $lang['id']] = ['title' => ' Visitor name ('.$lang['id'].')'];
		}
		
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = [];
			
			if ($item['id']){
				$sql =" SELECT count(id) as number_of_categories
						FROM ". DB_PREFIX."categories 
						WHERE parent_id = ".(int)$item['id'];
					
				$result = ze\sql::select($sql);
				$row = ze\sql::fetchAssoc($result);

				if(!$row['number_of_categories']){
					$item['link'] = false;
				}
			}

			$accessType = ' (private) ';
			if ($item['public']) {
				$accessType = ' (public) ';
				$item['traits']['public'] = true;
				
				foreach($langs as $lang) {
						$item['lang_'. $lang['id']] =
							ze\row::get('visitor_phrases', 'local_text',
										['language_id' => $lang['id'], 'code' => '_CATEGORY_'. (int) $id, 'module_class_name' => 'zenario_common_features']);
				}
			}

			if ($item['landing_page_equiv_id'] && $item['landing_page_content_type']) {
				$item['landing_page'] = ze\content::formatTag($item['landing_page_equiv_id'], $item['landing_page_content_type']);
			}
			
			$item['children'] = ze\categoryAdm::countChildren($id);
			$item['path'] = ze\categoryAdm::path($id);
			
			$item['full_path_label'] = $item['name'];
			
			//In FAB pickers, show the full category path (including the names of any parent categories)
			if (($mode == 'get_item_name' || $mode == 'typeahead_search' || $mode == 'get_item_links' || $mode == 'select') && $item['id']) {
				if ($item['parent_id']) {
					$currentParent = $item['parent_id'];
					$fullPathLabel = [];
					$iteration = 1;
					while ($currentParent) {
						$result = ze\row::get('categories', ['parent_id', 'name'], ['id' => $currentParent]);
						if ($result) {
							$fullPathLabel[$iteration] = $result['name'];
							$currentParent = $result['parent_id'];
						} else {
							$currentParent = false;
						}
						$iteration++;
						//Prevent infinite loops
						if ($iteration >= 500) {
							break;
						}
					}
					
					krsort($fullPathLabel);
					$fullPathLabel[] = $item['name'];
					$fullPathLabel = implode(' / ', $fullPathLabel);
				} else {
					$fullPathLabel = $item['name'];
				}
				
				$item['full_path_label'] = $fullPathLabel.$accessType;
				
				
			}
		}
		
		
		if ($_GET['refiner__parent_category'] ?? false) {
			$mrg = [
				'category' => ze\category::name($_GET['refiner__parent_category'] ?? false)];
			$panel['title'] = ze\admin::phrase('Sub-categories of "[[category]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('Category "[[category]]" has no sub-categories.', $mrg);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__content/panels/categories') return;
		
		if (($_POST['delete'] ?? false) && ze\priv::check('_PRIV_MANAGE_CATEGORY')) {
			foreach (explode(',', $ids) as $id) {
				zenario_common_features::deleteCategory($id);
			}
		}
	}
}