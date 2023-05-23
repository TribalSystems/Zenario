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


class zenario_common_features__organizer__sections extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/sections') return;
		
		if ($_GET['refiner__language'] ?? false) {
			$panel['title'] = ze\admin::phrase('Menu sections (language [[lang]])', ['lang' => ze\lang::name($_GET['refiner__language'] ?? false)]);
			if ($_GET['refiner__language'] != ze::$defaultLang) {

				$panel['notice']['show'] = false;
				
			}
			$panel['message'] = ze\admin::phrase('Advanced', ['lang' => ze\lang::name($_GET['refiner__language'] ?? false)]);
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = ['empty' => !ze\row::exists('menu_nodes', ['section_id' => $id])];
		}
		
		//In Organizer full mode, if this is the default language,
		//don't allow the Admin to navigate back up to the panel containing  the list of
		//menu sections in English, as this is copied into the second-level nav.
		if ($mode == 'full'
		 && !empty($_GET['_queued'])
		 && ($_GET['refiner__language'] ?? '') == ze::$defaultLang) {
			$panel['no_return'] = true;
		}
		
		//If there's only one language, the only time we'll ever see this panel
		//if when the admin clicks on the "Manage Sections" link.
		//Disable the folder click-through in this case.
		if (ze\lang::count() == 1) {
			unset($panel['item']['link']);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}
