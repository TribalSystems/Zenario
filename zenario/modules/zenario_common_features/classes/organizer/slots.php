<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__organizer__slots extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__content/panels/slots') return;
		
		$content = $dummyContentItem = array('id' => -1, 'type' => 'x', 'admin_version' => -1, 'head_html' => null, 'head_overwrite' => 0, 'foot_html' => null, 'foot_overwrite' => 0);
		$version = $dummyVersion = array('version' => -1, 'head_html' => null, 'foot_html' => null);


		switch ($refinerName) {
			case 'content_item':
				if (!($content = getRow('content_items', true, array('tag_id' => $refinerId)))
				 || !($version = getRow('content_item_versions', true, array('id' => $content['id'], 'type' => $content['type'], 'version' => $content['admin_version'])))
				 || !($template = getRow('layouts', true, $version['layout_id']))) {
					exit;
				}
		
				$lookForSlots = array('family_name' => $template['family_name'], 'file_base_name' => $template['file_base_name']);
		
				$panel['title'] = adminPhrase('Slots on the Content Item "[[tag]]"', array('tag' => formatTagFromTagId($refinerId)));
				$panel['no_items_message'] = adminPhrase('There are no slots on the chosen Layout.'); 
		
				$layers = array(1 => 'content_item', 2 => 'template');
		
		
				//Check that there is a draft and the admin has permissions to make changes.
				if (!checkPriv('_PRIV_MANAGE_ITEM_SLOT')) {
					//Remove the buttons if they do not have the permision to make changes
					unset($panel['item_buttons']);
		
				} elseif (!checkPriv('_PRIV_MANAGE_ITEM_SLOT', $content['id'], $content['type'], $content['admin_version'])) {
					//Disable the buttons if there is no draft or the item is locked
					foreach ($panel['item_buttons'] as $id => &$button) {
						if (is_array($button)) {
							if ($id != 'edit_reusable') {
								$button['disabled'] = true;
								$button['disabled_tooltip'] =
									adminPhrase('This content item cannot be changed as it is not in draft state, or it is locked.');
							}
						}
					}
				}
		
		
				break;
	
			case 'template':
				if (!($template = getRow('layouts', true, $refinerId))) {
					exit;
				}
		
				$lookForSlots = array('family_name' => $template['family_name'], 'file_base_name' => $template['file_base_name']);
		
				$panel['title'] =
					adminPhrase('Slots on the Layout "L[[layout_id]] [[template]]"',
						array(
							'template' => $template['name'],
							'layout_id' => str_pad($template['layout_id'], 2, '0', STR_PAD_LEFT)));
		
				$panel['no_items_message'] = adminPhrase('There are no slots on the chosen Layout.');
		
				$panel['key']['disableItemLayer'] = true;
		
				unset($panel['columns']['visitor_sees']);
				unset($panel['columns']['content_item']);
				unset($panel['item_buttons']['edit_wireframe']);
		
				$layers = array(2 => 'template');
	
				//On the Layout Layer, add an option to insert a Wireframe version of each Plugin
				//that is flagged as uses wireframe.
				if (checkPriv('_PRIV_MANAGE_TEMPLATE_SLOT')) {
					$i = 0;
					foreach (getRowsArray(
						'modules',
						array('id', 'display_name'),
						array('status' => 'module_running', 'is_pluggable' => 1, 'can_be_version_controlled' => 1),
						'display_name'
					) as $module) {
				
						$button = $panel['custom_template_buttons']['insert_version_controlled_plugin'];
						$button['ord'] = ++$i;
						$button['label'] =
						$button['ajax']['confirm']['button_message'] = adminPhrase('Insert a [[display_name]]', $module);
						$button['ajax']['request']['addPlugin'] = $module['id'];
				
						$panel['item_buttons'][] = $button;
					}
				}
		
				break;
	
			default:
				exit;
		}


	
		$panel['key']['cID'] = $content['id'];
		$panel['key']['cType'] = $content['type'];
		$panel['key']['cVersion'] = $version['version'];
		$panel['key']['layoutId'] = $template['layout_id'];
		$panel['key']['templateFamily'] = $template['family_name'];


		//Get the slots on this Layout, and calculate their contents
		$ord = 0;
		foreach(getRowsArray('template_slot_link', array('ord', 'slot_name'), $lookForSlots, array('ord', 'slot_name')) as $slot) {
			$panel['items'][$slot['slot_name']] =
				array(
					'ord' => $slot['ord']? $slot['ord'] : ++$ord,
					'slotname' => $slot['slot_name'],
					'visitor_sees' => adminPhrase('Nothing'),
					'content_item' => adminPhrase('Transparent'),
					'template' => adminPhrase('Empty'),
					'traits' => array('empty' => true));
		}


		foreach ($layers as $level => $layer) {
	
			if ($layer == 'template') {
				$content = $dummyContentItem;
				$version = $dummyVersion;
			}
	
	
			$slotContents = array();
			getSlotContents(
				$slotContents,
				$content['id'], $content['type'], $content['admin_version'],
				$template['layout_id'], $template['family_name'], $template['file_base_name'],
				$specificInstanceId = false, $specificSlotName = false, $ajaxReload = false,
				$runPlugins = false);
	
			foreach ($slotContents as $slotName => $slot) {
				if (isset($panel['items'][$slotName])) {
					if ($layer == $refinerName) {
						unset($panel['items'][$slotName]['traits']['empty']);
				
						if (!$slot['module_id']) {
							$panel['items'][$slotName]['traits']['opaque'] = true;
				
						} else {
							$panel['items'][$slotName]['module'] = getModuleDisplayName($slot['module_id']);
							$panel['items'][$slotName]['module_id'] = $slot['module_id'];
							$panel['items'][$slotName]['traits']['full'] = true;
							$panel['items'][$slotName]['instance_id'] = $slot['instance_id'];
					
							if (empty($slot['content_id']) && ($instance = getPluginInstanceDetails($slot['instance_id']))) {
								$panel['items'][$slotName]['visitor_sees'] = adminPhrase('Plugin: [[instance_name]]', $instance);
								$panel['items'][$slotName]['traits']['reusable'] = true;
							} else {
								$panel['items'][$slotName]['visitor_sees'] = adminPhrase('[[module]]', $panel['items'][$slotName]);
								$panel['items'][$slotName]['traits']['wireframe'] = true;
							}
						}
					}
			
			
					if ($slot['level'] == $level) {
						$panel['items'][$slotName]['traits'][$layer] = true;
				
						if (!$slot['module_id']) {
							$panel['items'][$slotName][$layer] = adminPhrase('Opaque');
				
						} else {
							if (empty($slot['content_id']) && ($instance = getPluginInstanceDetails($slot['instance_id']))) {
								$panel['items'][$slotName][$layer] = adminPhrase('Plugin: [[instance_name]]', $instance);
							} else {
								$panel['items'][$slotName][$layer] = adminPhrase('[[module]]', array('module' => getModuleDisplayName($slot['module_id'])));
							}
						}
					}
				}
			}
		}

	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != 'zenario__content/panels/slots') return;
		
		//Most of the logic to handle changing slots is in handleAJAX(), so call that
		
		//A little hack: set the requests up in the same way that handleAJAX() expects
		$_GET['slotName'] = $_REQUEST['slotName'] = $_POST['slotName'] = $ids;
		if (($_REQUEST['addPluginInstance'] ?? false) && $ids2) {
			$_GET['addPluginInstance'] = $_REQUEST['addPluginInstance'] = $_POST['addPluginInstance'] = $ids2;
		}
		
		$zenario_common_features = new zenario_common_features;
		$zenario_common_features->handleAJAX();
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}