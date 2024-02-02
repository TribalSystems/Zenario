<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


class zenario_common_features__admin_boxes__head_foot_slot extends ze\moduleBaseClass {
	
	
	protected function hasEditPerms(&$box) {
		if ($box['key']['level'] == 'item') {
			$latestVersion = ze\content::latestVersion($box['key']['cID'], $box['key']['cType']);
			
			if ($box['key']['cVersion'] != $latestVersion) {
				return false;
			
			} else if (!ze\content::isDraft($box['key']['cID'], $box['key']['cType']) && !ze\priv::check('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
				return false;
				
			} else {
				return ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $box['key']['cID'], $box['key']['cType']);
			}
		} else {
			return ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT');
		}
	}
	
	protected function tableInfo(&$box) {
		$t = [];
		if ($box['key']['mode'] == 'head') {
			$t['html'] = 'head_html';
			$t['vis'] = 'head_visitor_only';
			$t['overwrite'] = 'head_overwrite';
			$t['cc'] = 'head_cc';
			$t['cc_specific_cookie_types'] = 'head_cc_specific_cookie_types';
		} else {
			$t['html'] = 'foot_html';
			$t['vis'] = 'foot_visitor_only';
			$t['overwrite'] = 'foot_overwrite';
			$t['cc'] = 'foot_cc';
			$t['cc_specific_cookie_types'] = 'foot_cc_specific_cookie_types';
		}
		
		if ($box['key']['level'] == 'item') {
			$t['table'] = 'content_item_versions';
			$t['key'] = ['id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']];
		} else {
			$t['table'] = 'layouts';
			$t['key'] = ['layout_id' => $box['key']['layoutId']];
			$t['overwrite'] = false;
		}
		return $t;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Work out whether this is for the item or the layout layer
		if ($box['key']['level'] == 'item') {
			if (!(($box['key']['cID'] && $box['key']['cType']) || ze\content::getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']))
			 || !($box['key']['cVersion'] || ($box['key']['cVersion'] = ze\content::latestVersion($box['key']['cID'], $box['key']['cType'])))
			 || !($box['key']['layoutId'] = ze\content::layoutId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']))) {
				exit;
			}
			
			$formatTag = ze\content::formatTag($box['key']['cID'], $box['key']['cType']);
		
		} elseif ($box['key']['level'] == 'layout') {
			if ($box['key']['cID'] && $box['key']['cType'] && ($box['key']['cVersion'] = ze\content::latestVersion($box['key']['cID'], $box['key']['cType']))) {
				$box['key']['layoutId'] = ze\content::layoutId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
			} else {
				$box['key']['layoutId'] = ($box['key']['layoutId'] ?: $box['key']['id']);
			}
			
			if (!$layout = ze\content::layoutDetails($box['key']['layoutId'])) {
				exit;
			}
		
		} else {
			exit;
		}
		
		if ($box['key']['mode'] == 'head') {
			unset($fields['slot/html']['note_below']);
			
			if ($box['key']['level'] == 'item') {
				$box['title'] = ze\admin::phrase('<head> HTML/JS for the content item "[[tag]]", version [[version]]', ['tag' => $formatTag, 'version' => $box['key']['cVersion']]);
				$fields['slot/description']['snippet']['html'] =
					ze\admin::phrase('This content item will have the following HTML/JavaScript within the <code>&lt;head&gt;</code> tag (e.g. <code>&lt;meta&gt;</code> and <code>&lt;style&gt;</code> tags):');
				
			} elseif ($box['key']['level'] == 'layout') {
				$box['title'] = ze\admin::phrase('<head> HTML/JS for content items using "[[id_and_name]]"', $layout);
				$fields['slot/description']['snippet']['html'] =
					ze\admin::phrase('All content items using this layout will have the following HTML/JavaScript (e.g. <code>&lt;meta&gt;</code> and <code>&lt;style&gt;</code> tags):');
			}
	
		} else {
			if ($box['key']['level'] == 'item') {
				$box['title'] = ze\admin::phrase('HTML/JS before </body> for the content item "[[tag]]", version [[version]]', ['tag' => $formatTag, 'version' => $box['key']['cVersion']]);
				$fields['slot/description']['snippet']['html'] =
					ze\admin::phrase('This content item will have the following HTML/JavaScript immediately before the <code>&lt;/body&gt;</code> tag (e.g. &lt;script&gt; tags for JavaScript):');
				
			} elseif ($box['key']['level'] == 'layout') {
				$box['title'] = ze\admin::phrase('HTML/JS before </body> for the layout "[[id_and_name]]"', $layout);
				$fields['slot/description']['snippet']['html'] =
					ze\admin::phrase('All content items using this layout will have the following HTML/JavaScript immediately before the <code>&lt;/body&gt;</code> tag (e.g. &lt;script&gt; tags for JavaScript):');
			}
		}
		
		$t = $this->tableInfo($box);
		
		$cols = [$t['html'], $t['cc'], $t['cc_specific_cookie_types'], $t['vis']];
		if ($t['overwrite']) {
			$cols[] = $t['overwrite'];
		}
		
		$settings = ze\row::get($t['table'], $cols, $t['key']);
		$values['slot/html'] = $settings[$t['html']];
		$values['slot/cc'] = $settings[$t['cc']];
		$values['slot/cc_specific_cookie_types'] = $settings[$t['cc_specific_cookie_types']];
		$values['slot/output_in_admin_mode'] = !$settings[$t['vis']];
		
		if ($t['overwrite']) {
			$values['slot/overwrite'] = $settings[$t['overwrite']];
		} else {
			$fields['slot/overwrite']['hidden'] = true;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if (!$this->hasEditPerms($box)) {
			ze\admin::phrase('This Content Item is not a draft and cannot be edited.');
			exit;
		}
		
		if ($box['key']['level'] == 'item') {
			$box['confirm']['show'] = !ze\content::isDraft($box['key']['cID'], $box['key']['cType']);
		
		} else {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] =
				ze\admin::phrase(
					'<p>You are making changes to a slot on a layout.</p><p>This change will affect <span class="zenario_x_published_items">[[published]] published content item(s)</span> <span class="zenario_y_items">([[pages]] content item(s) in total).</span></p><p>Are you sure you wish to continue?</p>',
					['pages' => ze\layoutAdm::usage($box['key']['layoutId'], false),
							'published' => ze\layoutAdm::usage($box['key']['layoutId'], true)]);
	
			$box['confirm']['button_message'] = ze\admin::phrase('Save');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['level'] == 'item') {
			if (!ze\content::isDraft($box['key']['cID'], $box['key']['cType'])) {
				ze\contentAdm::createDraft($box['key']['cID'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['cVersion']);
			}
		}
		
		$t = $this->tableInfo($box);
		
		$html = $values['slot/html'];
		if (trim($html) == '') {
			$html = null;
		}
		
		$cols = [
			$t['html'] => $html,
			$t['cc'] => $values['slot/cc'],
			$t['vis'] => !$values['slot/output_in_admin_mode']
		];

		if ($values['slot/cc'] == 'specific_types' && ze::in($values['slot/cc_specific_cookie_types'], 'functionality', 'analytics', 'social_media')) {
			$cols[$t['cc_specific_cookie_types']] = $values['slot/cc_specific_cookie_types'];
		} else {
			$cols[$t['cc_specific_cookie_types']] = null;
		}
		
		if ($t['overwrite']) {
			$cols[$t['overwrite']] = $values['slot/overwrite'];
		}
		
		if ($box['key']['level'] == 'item') {
			ze\contentAdm::updateVersion($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $cols);
		} else {
			ze\row::update($t['table'], $cols, $t['key']);
		}
	}
}
