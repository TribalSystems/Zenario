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


class zenario_common_features__organizer__skins extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skins') return;
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skins') return;
		
		if (($refinerName == 'template_family' || $refinerName == 'template_family__panel_above')
		 && $templateFamily = ze\ring::decodeIdForOrganizer($_GET['refiner__template_family'] ?? false)) {
			$panel['title'] = ze\admin::phrase('Skins in the template directory "[[family]]"', ['family' => $templateFamily]);
			$panel['no_items_message'] = ze\admin::phrase('There are no skins for this template directory.');
			unset($panel['columns']['family_name']['title']);
		
		} elseif ($refinerName == 'usable_in_template_family'
		 && $templateFamily = ze\ring::decodeIdForOrganizer($_GET['refiner__usable_in_template_family'] ?? false)) {
			$panel['title'] = ze\admin::phrase('Skins in the directory "[[family]]"', ['family' => $templateFamily]);
			$panel['no_items_message'] = ze\admin::phrase('There are no skins in the directory "[[family]]"', ['family' => $templateFamily]);
			unset($panel['columns']['family_name']['title']);
		}
		
		foreach ($panel['items'] as &$item) {
			$status = '';
			if ($item['missing'] && $item['usage_layouts']) {
				$status = ze\admin::phrase('Skin is missing from the file system but is referred to by some layouts');
			} elseif (!$item['missing'] && $item['usage_layouts']) {
				$status = ze\admin::phrase('Skin was found in the file system and is referred to by some layouts');
			} elseif ($item['missing'] && !$item['usage_layouts']) {
				$status = ze\admin::phrase('Skin is missing from the file system and is not referred to by any layouts');
			} elseif (!$item['missing'] && !$item['usage_layouts']) {
				$status = ze\admin::phrase('Skin was found in the file system but is not referred to by any layouts');
			}
			$item['status'] = $status;
			if (!$item['display_name']) {
				$item['display_name'] = $item['name'];
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__layouts/panels/skins') return;
		
		if (($_POST['make_default'] ?? false) && ze\priv::check('_PRIV_EDIT_TEMPLATE')) {
			ze\row::update('template_families', ['skin_id' => $ids], ze\ring::decodeIdForOrganizer($_REQUEST['refiner__template_family'] ?? false));
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}