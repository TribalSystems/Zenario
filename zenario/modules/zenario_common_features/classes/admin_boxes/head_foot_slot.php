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


class zenario_common_features__admin_boxes__head_foot_slot extends module_base_class {
	
	
	protected function hasEditPerms(&$box) {
		if ($box['key']['level'] == 'item') {
			$latestVersion = getLatestVersion($box['key']['cID'], $box['key']['cType']);
			
			if ($box['key']['cVersion'] != $latestVersion) {
				return false;
			
			} else if (!isDraft($box['key']['cID'], $box['key']['cType']) && !checkPriv('_PRIV_CREATE_REVISION_DRAFT', $box['key']['cID'], $box['key']['cType'])) {
				return false;
				
			} else {
				return checkPriv('_PRIV_MANAGE_ITEM_SLOT', $box['key']['cID'], $box['key']['cType']);
			}
		} else {
			return checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT');
		}
	}
	
	protected function tableInfo(&$box) {
		$t = array();
		if ($box['key']['mode'] == 'head') {
			$t['html'] = 'head_html';
			$t['vis'] = 'head_visitor_only';
			$t['overwrite'] = 'head_overwrite';
			$t['cc'] = 'head_cc';
		} else {
			$t['html'] = 'foot_html';
			$t['vis'] = 'foot_visitor_only';
			$t['overwrite'] = 'foot_overwrite';
			$t['cc'] = 'foot_cc';
		}
		
		if ($box['key']['level'] == 'item') {
			$t['table'] = 'content_item_versions';
			$t['key'] = array('id' => $box['key']['cID'], 'type' => $box['key']['cType'], 'version' => $box['key']['cVersion']);
		} else {
			$t['table'] = 'layouts';
			$t['key'] = array('layout_id' => $box['key']['layoutId']);
			$t['overwrite'] = false;
		}
		return $t;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//Work out whether this is for the item or the layout layer
		if ($box['key']['level'] == 'item') {
			if (!(($box['key']['cID'] && $box['key']['cType']) || getCIDAndCTypeFromTagId($box['key']['cID'], $box['key']['cType'], $box['key']['id']))
			 || !($box['key']['cVersion'] || ($box['key']['cVersion'] = getLatestVersion($box['key']['cID'], $box['key']['cType'])))
			 || !($box['key']['layoutId'] = getContentItemLayout($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']))) {
				exit;
			}
			
			$formatTag = formatTag($box['key']['cID'], $box['key']['cType']);
		
		} elseif ($box['key']['level'] == 'layout') {
			if ($box['key']['cID'] && $box['key']['cType'] && ($box['key']['cVersion'] = getLatestVersion($box['key']['cID'], $box['key']['cType']))) {
				$box['key']['layoutId'] = getContentItemLayout($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
			} else {
				$box['key']['layoutId'] = ifNull($box['key']['layoutId'], $box['key']['id']);
			}
			
			if (!$layout = getTemplateDetails($box['key']['layoutId'])) {
				exit;
			}
		
		} else {
			exit;
		}
		
		if ($box['key']['mode'] == 'head') {
			unset($fields['slot/html']['note_below']);
			
			if ($box['key']['level'] == 'item') {
				$box['title'] = adminPhrase('HTML in the head of page for the content item "[[tag]]", version [[version]]', array('tag' => $formatTag, 'version' => $box['key']['cVersion']));
			} elseif ($box['key']['level'] == 'layout') {
				$box['title'] = adminPhrase('HTML in the head of page for the layout "[[id_and_name]]"', $layout);
			}
	
		} else {
			if ($box['key']['level'] == 'item') {
				$box['title'] = adminPhrase('HTML at the foot of page for the content item "[[tag]]", version [[version]]', array('tag' => $formatTag, 'version' => $box['key']['cVersion']));
			} elseif ($box['key']['level'] == 'layout') {
				$box['title'] = adminPhrase('HTML at the foot of page for the layout "[[id_and_name]]"', $layout);
			}
		}
		
		$t = $this->tableInfo($box);
		
		$cols = array($t['html'], $t['cc'], $t['vis']);
		if ($t['overwrite']) {
			$cols[] = $t['overwrite'];
		}
		
		$settings = getRow($t['table'], $cols, $t['key']);
		$values['slot/html'] = $settings[$t['html']];
		$values['slot/cc'] = $settings[$t['cc']];
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
			adminPhrase('This Content Item is not a draft and cannot be edited.');
			exit;
		}
		
		if ($box['key']['level'] == 'item') {
			$box['confirm']['show'] = !isDraft($box['key']['cID'], $box['key']['cType']);
		
		} else {
			$box['confirm']['show'] = true;
			$box['confirm']['message'] =
				adminPhrase(
					'<p>You are making changes to a Slot on a Layout.</p><p>This change will affect <span class="zenario_x_published_items">[[published]] Published Content Item(s)</span> <span class="zenario_y_items">([[pages]] Content Item(s) in total).</span></p><p>Are you sure you wish to continue?</p>',
					array('pages' => checkTemplateUsage($box['key']['layoutId'], false, false),
							'published' => checkTemplateUsage($box['key']['layoutId'], false, true)));
	
			$box['confirm']['button_message'] = adminPhrase('Save');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($box['key']['level'] == 'item') {
			if (!isDraft($box['key']['cID'], $box['key']['cType'])) {
				createDraft($box['key']['cID'], $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion'], $box['key']['cVersion']);
			}
		}
		
		$t = $this->tableInfo($box);
		
		$html = $values['slot/html'];
		if (trim($html) == '') {
			$html = null;
		}
		
		$cols = array(
			$t['html'] => $html,
			$t['cc'] => $values['slot/cc'],
			$t['vis'] => !$values['slot/output_in_admin_mode']);
		
		if ($t['overwrite']) {
			$cols[$t['overwrite']] = $values['slot/overwrite'];
		}
		
		updateRow($t['table'], $cols, $t['key']);
	}
}
