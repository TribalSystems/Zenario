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


class zenario_common_features__organizer__slots extends ze\moduleBaseClass {

	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
	
	}

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__content/panels/slots') return;
	
		$content = $dummyContentItem = ['id' => -1, 'type' => 'x', 'admin_version' => -1, 'head_html' => null, 'head_overwrite' => 0, 'foot_html' => null, 'foot_overwrite' => 0];
		$version = $dummyVersion = ['version' => -1, 'head_html' => null, 'foot_html' => null];


		switch ($refinerName) {
			case 'content_item':
			case 'content_item_from_menu_node':
				if ($refinerName == 'content_item_from_menu_node' && ($menuContentItem = ze\menu::getContentItem($refinerId))) {
					$contentItemTagId = $menuContentItem['content_type'] . '_' . $menuContentItem['equiv_id'];
				} else {
					$contentItemTagId = $refinerId;
				}
			
				if (!($content = ze\row::get('content_items', true, ['tag_id' => $contentItemTagId]))
				 || !($version = ze\row::get('content_item_versions', true, ['id' => $content['id'], 'type' => $content['type'], 'version' => $content['admin_version']]))
				 || !($layout = ze\row::get('layouts', true, $version['layout_id']))) {
					exit;
				}
			
				$lookForSlots = ['layout_id' => $version['layout_id']];
	
				$panel['title'] = ze\admin::phrase('Slots on the Content Item "[[tag]]"', ['tag' => ze\content::formatTagFromTagId($content['tag_id'])]);
				$panel['no_items_message'] = ze\admin::phrase('There are no slots on the chosen Layout.'); 
	
	
				$layers = [1 => 'content_item', 2 => 'layout', 3 => 'sitewide'];
	
	
				//Check that there is a draft and the admin has permissions to make changes.
				if (!ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
					//Remove the buttons if they do not have the permision to make changes
					unset($panel['item_buttons']);
	
				} elseif (!ze\priv::check('_PRIV_MANAGE_ITEM_SLOT', $content['id'], $content['type'], $content['admin_version'])) {
					//Disable the buttons if there is no draft or the item is locked
					foreach ($panel['item_buttons'] as $id => &$button) {
						if (is_array($button)) {
							if ($id != 'edit_reusable') {
								$button['disabled'] = true;
								$button['disabled_tooltip'] =
									ze\admin::phrase('This content item cannot be changed as it is not in draft state, or it is locked.');
							}
						}
					}
				}
	
	
				break;

			case 'layout':
				if (!($layout = ze\row::get('layouts', true, $refinerId))) {
					exit;
				}
				$lookForSlots = ['layout_id' => $refinerId];
	
				$panel['title'] =
					ze\admin::phrase('Slots on the Layout "[[codeName]] [[name]]"', [
						'name' => $layout['name'],
						'codeName' => ze\layoutAdm::codeName($layout['layout_id'])
					]);
	
				$panel['no_items_message'] = ze\admin::phrase('There are no slots on the chosen Layout.');
	
				$panel['key']['disableItemLayer'] = true;
	
				unset($panel['columns']['visitor_sees']);
				unset($panel['columns']['content_item']);
				unset($panel['item_buttons']['edit_wireframe']);
			
				$layers = [2 => 'layout', 3 => 'sitewide'];

				//On the Layout Layer, add an option to insert a Wireframe version of each Plugin
				//that is flagged as uses wireframe.
				if (ze\priv::check('_PRIV_MANAGE_TEMPLATE_SLOT')) {
					$i = 0;
					foreach (ze\row::getAssocs(
						'modules',
						['id', 'display_name'],
						['status' => 'module_running', 'is_pluggable' => 1, 'can_be_version_controlled' => 1],
						'display_name'
					) as $module) {
			
						$button = $panel['custom_template_buttons']['insert_version_controlled_plugin'];
						$button['ord'] = ++$i;
						$button['label'] =
						$button['ajax']['confirm']['button_message'] = ze\admin::phrase('Insert a [[display_name]]', $module);
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
		$panel['key']['layoutId'] = $layout['layout_id'];


		//Get the slots on this Layout, and calculate their contents
		$ord = 0;
		foreach(ze\row::getAssocs('layout_slot_link', ['ord', 'slot_name', 'is_header', 'is_footer'], $lookForSlots, ['ord', 'slot_name']) as $slot) {
			$item = [
				'ord' => $slot['ord']? $slot['ord'] : ++$ord,
				'slotname' => $slot['slot_name'],
				'empty' => true
			];
			
			if ($slot['is_header'] || $slot['is_footer']) {
				$item['visitor_sees'] = ze\admin::phrase('Nothing');
				$item['content_item'] = ze\admin::phrase('Transparent');
				$item['layout'] = ze\admin::phrase('-');
				$item['sitewide'] = ze\admin::phrase('Empty');
			} else {
				$item['visitor_sees'] = ze\admin::phrase('Nothing');
				$item['content_item'] = ze\admin::phrase('Transparent');
				$item['layout'] = ze\admin::phrase('Empty');
				$item['sitewide'] = ze\admin::phrase('-');
			}
			
			$panel['items'][$slot['slot_name']] = $item;
		}


		foreach ($layers as $level => $layer) {

			if ($layer == 'layout') {
				$content = $dummyContentItem;
				$version = $dummyVersion;
			}
			$slotContents = [];
			ze\plugin::checkSlotContents(
				$slotContents,
				$content['id'], $content['type'], $content['admin_version'],
				$layout['layout_id']
			);

			foreach ($slotContents as $slotName => $slot) {
				if (isset($panel['items'][$slotName])) {
					if ($layer == $refinerName || ($refinerName == 'content_item_from_menu_node' && $layer == 'content_item')) {
					
						$usageLinks = [
							'content_items' => 'zenario__layouts/panels/layouts/item_buttons/view_content//'. (int) $layout['layout_id']. '//'
						];
						unset($panel['items'][$slotName]['empty']);
			
						if (!$slot->moduleId()) {
							$panel['items'][$slotName]['opaque'] = true;
			
						} else {
							$panel['items'][$slotName]['module_id'] = $slot->moduleId();
							$panel['items'][$slotName]['full'] = true;
							$panel['items'][$slotName]['instance_id'] = $slot->instanceId();
						
				
							if (!$slot->isVersionControlled() && ($instance = ze\plugin::details($instanceId = $slot->instanceId()))) {
							
								$usage = [];
								switch ($instance['class_name']) {
									case 'zenario_plugin_nest':
										$usage = [
											'nests' => 1,
											'nest' => $instanceId
										];
										break;
									
									case 'zenario_slideshow':
									case 'zenario_slideshow_simple':
										$usage = [
											'slideshows' => 1,
											'slideshow' => $instanceId
										];
										break;
								
									default:
										$usage = [
											'plugins' => 1,
											'plugin' => $instanceId
										];
										break;
								}
							
								$panel['items'][$slotName]['visitor_sees'] =
									implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
							
								$panel['items'][$slotName]['reusable'] = true;
							} else {
								$usage = [
									'modules' => 1,
									'module' => $slot->moduleId()
								];
								$panel['items'][$slotName]['visitor_sees'] =
									implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
								$panel['items'][$slotName]['wireframe'] = true;
							
								//Show how many items use a specific to slotName, and display links if possible.
								$usageContentItems = ze\layoutAdm::slotUsage($layout['layout_id'], $slotName);
								$usage = [
									'content_item' => $usageContentItems[0] ?? null,
									'content_items' => count($usageContentItems)
								];
							
								if (!empty($usageContentItems[0])) {
									$panel['items'][$slotName]['visitor_sees'] .=
										' ('.
										ze\admin::phrase('with content on').
										' '.
										implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks)).
										')';
								} else {
									$panel['items'][$slotName]['visitor_sees'] .=
										' ('.
										ze\admin::phrase('with no content').
										')';
								}
							}
						}
					}
		
		
					if ($slot->level() == $level) {
						$panel['items'][$slotName][$layer] = true;
			
						if (!$slot->moduleId()) {
							$panel['items'][$slotName][$layer] = ze\admin::phrase('Opaque');
			
						} else {
							if (!$slot->isVersionControlled() && ($instance = ze\plugin::details($instanceId = $slot->instanceId()))) {
							
								$usage = [];
								switch ($instance['class_name']) {
									case 'zenario_plugin_nest':
										$usage = [
											'nests' => 1,
											'nest' => $instanceId
										];
										break;
									
									case 'zenario_slideshow':
									case 'zenario_slideshow_simple':
										$usage = [
											'slideshows' => 1,
											'slideshow' => $instanceId
										];
										break;
								
									default:
										$usage = [
											'plugins' => 1,
											'plugin' => $instanceId
										];
										break;
								}
							
								$panel['items'][$slotName][$layer] = 
									implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
							} else {
								$usage = [
									'modules' => 1,
									'module' => $slot->moduleId()
								];
								$panel['items'][$slotName][$layer] =
									implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks));
							}
						}
						
						$panel['items'][$slotName][$layer. '_plain_text'] = htmlspecialchars_decode(strip_tags($panel['items'][$slotName][$layer]));
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
		if (ze::request('addPluginInstance') && $ids2) {
			$_GET['addPluginInstance'] = $_REQUEST['addPluginInstance'] = $_POST['addPluginInstance'] = $ids2;
		}
	
		$zenario_common_features = new zenario_common_features;
		$zenario_common_features->handleAJAX();
	}

	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
	
	}
}