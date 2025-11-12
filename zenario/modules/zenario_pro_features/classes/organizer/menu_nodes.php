<?php
/*
 * Copyright (c) 2025, Tribal Limited
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

				
class zenario_pro_features__organizer__menu_nodes extends ze\moduleBaseClass {
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__menu/panels/menu_nodes') return;
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__menu/panels/menu_nodes':
				if (ze::get('make_invisible') && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
					$idsArray = explode(',', $ids);
					
					if ($idsArray) {
						$totalCount = count($idsArray);
						
						if ($totalCount == 1) {
							$message = ze\admin::phrase(
								"Make the selected menu node invisible?
								
								It will no longer appear in the regular menu navigation, but will still appear in the breadcrumb trail."
							);
							
							echo $message;
						} elseif ($totalCount > 1) {
							$sql = "
								SELECT COUNT(*)
								FROM " . DB_PREFIX . "menu_nodes
								WHERE invisible = 1
								AND id IN(" . ze\escape::in($ids) . ")";
							$result = ze\sql::select($sql);
							$invisibleCount = ze\sql::fetchValue($result);
							
							$firstLine = "Make [[total_count]] selected menu nodes invisible?";
							$secondLine = "";
							$thirdLine = "They will no longer appear in the regular menu navigation.";
							$replace = ['total_count' => $totalCount, 'invisible_count' => $invisibleCount];
							
							if ($invisibleCount) {
								$visibleCount = $totalCount - $invisibleCount;
								
								if ($invisibleCount == 1) {
									$secondLine = "Note that [[invisible_count]] is already invisible, so this will only affect [[visible_count]].";
								} elseif ($invisibleCount > 1) {
									$secondLine = "Note that [[invisible_count]] are already invisible, so this will only affect [[visible_count]].";
								}
								
								$replace['visible_count'] = $visibleCount;
								
								$message = ze\admin::phrase(
									$firstLine . "
									
									" . $secondLine . "
									
									" . $thirdLine,
									$replace
								);
							} else {
								$message = ze\admin::phrase(
									$firstLine . "
									
									" . $thirdLine,
									$replace
								);
							}
							
							echo $message;
						}
					}
				
				} elseif (ze::post('make_invisible') && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
					foreach (explode(',', $ids) as $id) {
						ze\row::update('menu_nodes', ['invisible' => 1], $id);
					}
		
				} elseif (ze::get('make_visible') && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
					$idsArray = explode(',', $ids);
					
					if ($idsArray) {
						$totalCount = count($idsArray);
						
						if ($totalCount == 1) {
							$message = ze\admin::phrase(
								"Make the selected menu node visible?
								
								It will then appear in the regular menu navigation."
							);
							
							echo $message;
						} elseif ($totalCount > 1) {
							$sql = "
								SELECT COUNT(*)
								FROM " . DB_PREFIX . "menu_nodes
								WHERE invisible = 0
								AND id IN(" . ze\escape::in($ids) . ")";
							$result = ze\sql::select($sql);
							$visibleCount = ze\sql::fetchValue($result);
							
							$firstLine = "Make [[total_count]] selected menu nodes visible?";
							$secondLine = "";
							$thirdLine = "They will then appear in the regular menu navigation.";
							$replace = ['total_count' => $totalCount, 'visible_count' => $visibleCount];
							
							if ($visibleCount) {
								$invisibleCount = $totalCount - $visibleCount;
								
								if ($visibleCount == 1) {
									$secondLine = "Note that [[visible_count]] is already visible, so this will only affect [[invisible_count]].";
								} elseif ($visibleCount > 1) {
									$secondLine = "Note that [[visible_count]] are already visible, so this will only affect [[invisible_count]].";
								}
								
								$replace['invisible_count'] = $invisibleCount;
								
								$message = ze\admin::phrase(
									$firstLine . "
									
									" . $secondLine . "
									
									" . $thirdLine,
									$replace
								);
							} else {
								$message = ze\admin::phrase(
									$firstLine . "
									
									" . $thirdLine,
									$replace
								);
							}
							
							echo $message;
						}
					}
				
				} elseif (ze::post('make_visible') && ze\priv::check('_PRIV_EDIT_MENU_ITEM')) {
					foreach (explode(',', $ids) as $id) {
						ze\row::update('menu_nodes', ['invisible' => 0], $id);
					}
				}
		
				break;
		}
		
		return false;
	}
}
