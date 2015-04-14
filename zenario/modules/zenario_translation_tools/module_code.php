<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


class zenario_translation_tools extends module_base_class {
	
	
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/language_equivs':
			case 'zenario__menu/panels/menu_nodes':
				zenario_translation_tools::checkDefaultLanguageIsSynced();
				
				$result = sqlSelect("SELECT COUNT(*) FROM " . DB_NAME_PREFIX . "languages WHERE sync_assist = 1");
				$numAssistLanguages = sqlFetchRow($result);
				
				if ($numAssistLanguages[0] > 1) {
					$panel['zenario_translation_tools__num_assist_languages'] = $numAssistLanguages[0];
				} else {
					unset($panel['columns']['zenario_translation_tools__sync_assist']);
				}
			
			break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
			case 'zenario__content/panels/language_equivs':
			case 'zenario__menu/panels/menu_nodes':
				
				if (!empty($panel['zenario_translation_tools__num_assist_languages'])) {
					foreach ($panel['items'] as &$item) {
						if ($item['zenario_translation_tools__sync_assist'] < $panel['zenario_translation_tools__num_assist_languages']) {
							$item['cell_css_classes'] = array();
							if (isset($item['translations'])) {
								$item['cell_css_classes']['translations'] = 'orange';
							}
							if (isset($item['zenario_trans__links'])) {
								$item['cell_css_classes']['zenario_trans__links'] = 'orange';
							}
						}
						unset($item['zenario_translation_tools__sync_assist']);
					}
				}
			
			break;
		}
	}
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
		
			case 'zenario_setup_language':
				zenario_translation_tools::checkDefaultLanguageIsSynced();
				
				if (isset($box['tabs']['settings']['fields']['sync_assist'])) {
					$box['tabs']['settings']['fields']['sync_assist']['value'] = getRow('languages', 'sync_assist', $box['key']['id']);
				}
				
				if ($box['key']['id'] == setting('default_language')) {
					$box['tabs']['settings']['fields']['sync_assist']['read_only'] = true;
				}
				
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
		
			case 'zenario_setup_language':
				if (isset($box['tabs']['settings']['fields']['sync_assist']) && checkPriv('_PRIV_MANAGE_LANGUAGE_CONFIG')) {
					updateRow('languages', array('sync_assist' => $values['settings/sync_assist']), $box['key']['id']);
				}
				
				break;
			
		}
	}
	
	protected static function checkDefaultLanguageIsSynced() {
		if (setting('default_language')
		 && ($lang = getRow('languages', array('sync_assist'), setting('default_language')))
		 && (!$lang['sync_assist'])) {
			updateRow('languages', array('sync_assist' => 1), setting('default_language'));
		}
	}
}
?>