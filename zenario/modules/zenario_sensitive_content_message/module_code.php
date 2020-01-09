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

class zenario_sensitive_content_message extends ze\moduleBaseClass {
	
	protected $mergeFields = [];
	protected $layoutId = false;
	protected $layoutSensitiveContentMessageEnabled = false;
	
	public function init() {
		return true;
	}
	
	public static function showSensitiveContentMessage() {
		$sensitiveContentMessageSetting = ze::setting('zenario_sensitive_content_message__setting');
		if ($sensitiveContentMessageSetting == 'enabled_for_whole_site') {
			$displayMessage = true;
			
		} elseif ($sensitiveContentMessageSetting == 'enabled_by_layout_and_by_content_item') {
			$layoutId = ze\content::layoutId(ze::$cID, ze::$cType, ze::$cVersion);
			
			$layoutSCMSetting = ze\row::get('layouts', 'sensitive_content_message', $layoutId);
			$currentContentItemSCMSetting =
				ze\row::get('content_item_versions',
							'sensitive_content_message',
							['id' => ze::$cID, 'type' => ze::$cType, 'version' => ze::$cVersion]
					);
					
			$displayMessage = $layoutSCMSetting || $currentContentItemSCMSetting;
			
		} elseif ($sensitiveContentMessageSetting == 'disabled') {
			$displayMessage = false;
		}
		
		if ($displayMessage) {
			echo '
<!--googleoff: all-->
<script type="text/javascript" src="zenario/modules/zenario_sensitive_content_message/fun/sensitive_content_message.php"></script>
<!--googleon: all-->';
		}
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$sensitiveContentMessageSetting = ze::setting('zenario_sensitive_content_message__setting');
		
		switch ($path) {
			case 'zenario_content':
				$this->layoutId = ze\content::layoutId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
				$this->layoutSensitiveContentMessageEnabled = ze\row::get('layouts', 'sensitive_content_message', $this->layoutId);
				
				if ($sensitiveContentMessageSetting == 'enabled_for_whole_site') {
					$values['meta_data/sensitive_content_message_checkbox'] = true;
					$fields['meta_data/sensitive_content_message_checkbox']['note_below'] .= ze\admin::phrase('<br />Enabled for whole site.');
					$fields['meta_data/sensitive_content_message_checkbox']['disabled'] = true;
					
				} elseif ($sensitiveContentMessageSetting == 'enabled_by_layout_and_by_content_item') {
				
					$values['meta_data/sensitive_content_message_checkbox'] =
						ze\row::get('content_item_versions',
									'sensitive_content_message',
									['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']]
						);
						
					$fields['meta_data/sensitive_content_message_checkbox']['disabled'] = $this->layoutSensitiveContentMessageEnabled;
				
				} elseif ($sensitiveContentMessageSetting == 'disabled') {
					$values['meta_data/sensitive_content_message_checkbox'] = false;
					$fields['meta_data/sensitive_content_message_checkbox']['disabled'] = true;
					$fields['meta_data/sensitive_content_message_checkbox']['note_below'] .= ze\admin::phrase('<br />Currently disabled.');
				}
				break;
			case 'zenario_layout':
				$this->layoutId = $box['key']['id'];
				
				if ($sensitiveContentMessageSetting == 'enabled_for_whole_site') {
					$values['template/sensitive_content_message_checkbox'] = true;
					$fields['template/sensitive_content_message_checkbox']['disabled'] = true;
					$fields['template/sensitive_content_message_checkbox']['note_below'] .= ze\admin::phrase('<br />Enabled for whole site.');
					
				} elseif ($sensitiveContentMessageSetting == 'enabled_by_layout_and_by_content_item') {
				
					$values['template/sensitive_content_message_checkbox'] =
						ze\row::get('layouts', 'sensitive_content_message', $this->layoutId);
										
				} elseif ($sensitiveContentMessageSetting == 'disabled') {
					$values['template/sensitive_content_message_checkbox'] = false;
					$fields['template/sensitive_content_message_checkbox']['disabled'] = true;
					$fields['template/sensitive_content_message_checkbox']['note_below'] .= ze\admin::phrase('<br />Currently disabled.');
				}
				
				$values['template/sensitive_content_message_checkbox'] =
					ze\row::get('layouts', 'sensitive_content_message', $this->layoutId);
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'zenario_content':
				if (ze::setting('zenario_sensitive_content_message__setting') == 'enabled_by_layout_and_by_content_item' && !$this->layoutSensitiveContentMessageEnabled) {
					ze\row::set('content_item_versions',
								['sensitive_content_message' => $values['meta_data/sensitive_content_message_checkbox']],
								['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']]
					);
				}
				break;
			case 'zenario_layout':
				if (ze::setting('zenario_sensitive_content_message__setting') == 'enabled_by_layout_and_by_content_item') {
					ze\row::set('layouts',
								['sensitive_content_message' => $values['template/sensitive_content_message_checkbox']],
								['layout_id' => $box['key']['id']]
					);
				}
				break;
		}
		
		switch (ze::setting('zenario_sensitive_content_message__setting')) {
			case 'disabled':
			case 'enabled_for_whole_site':
				break;
			case 'enabled_by_layout_and_by_content_item':
		}
	}
}