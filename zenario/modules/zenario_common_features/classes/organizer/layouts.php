<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_common_features__organizer__layouts extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
		}
		
		if (isset($_GET['refiner__archived'])) {
			$panel['title'] = ze\admin::phrase('Retired layouts');
			$panel['no_items_message'] = ze\admin::phrase('Area for layouts used in the past that shouldn\'t be used again.');
			$panel['item']['css_class'] = 'archived_layout';
			
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__archived'];
			
			unset($panel['columns']['archived']['title']);
			unset($panel['columns']['default']);
			unset($panel['collection_buttons']);
		
		} elseif ($refinerName == 'content_type') {
			unset($panel['columns']['archived']['title']);
			$panel['no_items_message'] = ze\admin::phrase('There are no active layouts for this content type.');

		} elseif ($refinerName == 'layouts_using_form') {
			$mrg = [];
			if (ze\module::inc('zenario_user_forms')) {
				$mrg['name'] = zenario_user_forms::getFormName($refinerId);
			}
			$panel['title'] = ze\admin::phrase('Layouts using the form "[[name]]"', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no layouts using the form "[[name]]"', $mrg);
		
		} elseif ($mode == 'typeahead_search') {
			$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement__typeahead_search'];
		
		} elseif ($refinerName || ze::in($mode, 'get_item_name', 'get_item_links')) {
			unset($panel['db_items']['where_statement']);
		}
		
		if (isset($_GET['refiner__content_type'])) {
			unset($panel['columns']['content_type']['title']);
		}
		
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		$panel['key']['disableItemLayer'] = true;
		
		if ($refinerName == 'content_type') {
			$panel['title'] = ze\admin::phrase('Layouts available for the "[[name]]" content type', ['name' => ze\content::getContentTypeName($refinerId)]);
			$panel['no_items_message'] = ze\admin::phrase('There are no layouts available for the "[[name]]" content type', ['name' => ze\content::getContentTypeName($refinerId)]);
		
		} elseif (ze::get('refiner__module_usage')) {
			$mrg = [
				'name' => ze\module::displayName(ze::get('refiner__module_usage'))];
			$panel['title'] = ze\admin::phrase('Layouts on which the module "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no layouts using the module "[[name]]".', $mrg);
		
		} elseif (ze::get('refiner__plugin_instance_usage')) {
			$mrg = [
				'name' => ze\plugin::name(ze::get('refiner__plugin_instance_usage'))];
			$panel['title'] = ze\admin::phrase('Layouts on which the plugin "[[name]]" is used (layout layer)', $mrg);
			$panel['no_items_message'] = ze\admin::phrase('There are no layouts using the plugin "[[name]]".', $mrg);
		
		}
		
		$panel['columns']['content_type']['values'] = [];
		foreach (ze\content::getContentTypes() as $cType) {
			$panel['columns']['content_type']['values'][$cType['content_type_id']] = $cType['content_type_name_en'];
		}
		
		$foundPaths = [];
		$defaultLayouts = ze\row::getValues('content_types', 'default_layout_id', []);
		
		$templatePreview = '';
		
		foreach ($panel['items'] as $id => &$item) {
			$summary = '';
			
			//Format the layout Id
			if ($item['code']) {
				$item['code'] = ze\layoutAdm::codeName($item['code']);
			}
			
			if ($item['status'] == 'active' && $item['default']) {
				$item['status'] = 'active_default';
			}
			
			if ($item['header_and_footer']) {
				$summary .= ze\admin::phrase('Uses site-wide header and footer');
			
			} elseif (!empty($item['cols'])) {
				if (!empty($item['fluid'])) {
					$summary .= ze\admin::phrase('Fluid / [[min_width]] - [[max_width]] px', $item). ' ';
				} else {
					$summary .= ze\admin::phrase('Fixed width / [[max_width]] px', $item). ' ';
				}
				if (!empty($item['responsive'])) {
					$summary .= ze\admin::phrase('/ Responsive', $item). ' ';
				}
				$summary .= ze\admin::phrase('/ [[cols]] columns', $item);
			}
			
			$summary .= ' <br/>' . ze\admin::phrase('[[skin_name]] skin', ['skin_name' => htmlspecialchars($item['skin_name'])]);
			
			$item['summary'] = $summary;
			
			if (!ze\row::exists('content_types', ['default_layout_id' => $id]) && !ze\row::exists('content_item_versions', ['layout_id' => $id])) {
				$item['deletable'] = true;
				$item['delete_disabled'] = false;
				
			} else {
				$item['delete_disabled'] = true;
			}
			
			//Show how many items use a specific layout, and display links if possible.
			$usageContentItems = ze\layoutAdm::usage($id, false, $countItems = false);
			$usageByTrashedContentItems = '';
			
			if (count($usageContentItems) > 0) {
				$usage = [
					'content_item' => $usageContentItems[0] ?? null,
					'content_items' => count($usageContentItems)
				];
	
				$usageLinks = [
					'content_items' => 'zenario__layouts/panels/layouts/item_buttons/view_content//'. (int) $id. '//'
				];
				
				$contentTypeEnname='';
				foreach (ze\content::getContentTypes() as $cType) {
					if($cType['content_type_id'] == $item['content_type']) {
						if(count($usageContentItems)==1) {
							$contentTypeEnname = $cType['content_type_name_en'].' content item';
						} else {
							$contentTypeEnname = $cType['content_type_name_en'].' content items';
						}
					}
				}
				
				if (ze\row::exists('content_types', ['default_layout_id' => $id])) {
					$item['default_used'] = ze\admin::phrase('Used by [[typeCount]] [[contentTypes]], default for this content type', ['contentTypes' => $contentTypeEnname, 'typeCount' => count($usageContentItems)]);
				} else {
					$item['default_used'] = ze\admin::phrase('Used by [[typeCount]] [[contentTypes]]', ['contentTypes' => $contentTypeEnname, 'typeCount' => count($usageContentItems)]);
				}
			} else {
				$usageByTrashedContentItems = ze\layoutAdm::usageByTrashedContentItems($id, $countItems = false);
				$usageByTrashedContentItemsCount = count($usageByTrashedContentItems);
				$usage = [
					'content_item' => $usageByTrashedContentItems[0] ?? null,
					'content_items' => $usageByTrashedContentItemsCount
				];
	
				$usageLinks = [
					'content_items' => 'zenario__content/panels/content/refiners/trash////'
				];
				
				if (ze\row::exists('content_types', ['default_layout_id' => $id])) {
					if ($usageByTrashedContentItemsCount) {
						$item['default_used'] = ze\admin::nPhrase(
							'Only used by [[count]] trashed content item, default for this content type.',
							'Only used by [[count]] trashed content items, default for this content type.',
							$usageByTrashedContentItemsCount
						);
					} else {
						$item['default_used'] = ze\admin::phrase('Not used, default for this content type.');
					}
				} else {
					if ($usageByTrashedContentItemsCount) {
						$item['default_used'] = ze\admin::nPhrase(
							'Only used by [[count]] trashed content item',
							'Only used by [[count]] trashed content items',
							$usageByTrashedContentItemsCount
						);
					} else {
						$item['default_used'] = ze\admin::phrase('Not used');
					}
				}
			}
			
			$item['where_used'] = implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
			
			$item['row_class'] = ' layout_status_' . $item['status'];
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__layouts/panels/layouts') return;
		
		//Delete a layout if it is not in use
		if (ze::post('delete') && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (!ze\row::exists('content_types', ['default_layout_id' => $id])
				 && !ze\row::exists('content_item_versions', ['layout_id' => $id])) {
				 	ze\layoutAdm::delete($id);
				}
			}
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
		
		//Archive/retire a layout
		} elseif (ze::post('archive') && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				if (!ze\row::exists('content_types', ['default_layout_id' => $id])) {
					ze\row::update('layouts', ['status' => 'suspended'], $id);
				}
			}
		
		//Restore a layout
		} elseif (ze::post('restore') && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			foreach (ze\ray::explodeAndTrim($ids) as $id) {
				ze\row::update('layouts', ['status' => 'active'], $id);
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}
