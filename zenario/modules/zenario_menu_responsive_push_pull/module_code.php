<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_menu_responsive_push_pull extends zenario_menu {
	
	public function init(){
		if (parent::init()) {

			$this->callScript(
				'zenario_menu_responsive_push_pull', 
				'pageReady',
				$this->containerId
			);

			return true;
		} else {
			return false;
		}
	}
	
	public function showSlot() {
		$this->mergeFields = [];

		//The menu title will be translated in the framework.
		$this->mergeFields['Show_menu_title'] = $this->setting('menu_title');
		$this->mergeFields['Menu_title'] = $this->setting('menu_title');

		if ($this->setting('show_search_box')) {
			$this->mergeFields['Search_Box'] = true;
			$this->mergeFields['Search_Field_ID'] = $this->containerId . '_search_input_box';
		
			//Get cID and cType if "Use a specific Search Results Page" was selected.
			$cID = $cType = $state = false;
			if ($this->setting('show_search_box') && $this->getCIDAndCTypeFromSetting($cID, $cType, 'specific_search_results_page')) {
				
			} else {
				ze\content::pluginPage($cID, $cType, $state, 'zenario_search_results');
			}
			
			if ($this->setting('search_placeholder') && $this->setting('search_placeholder_phrase')) {
				$this->mergeFields['Placeholder'] = true;
				$this->mergeFields['Placeholder_Phrase'] = $this->setting('search_placeholder_phrase');
			}
			
			$this->mergeFields['Search_Target'] = ze\link::toItem($cID, $cType);
		}
		
		if ($this->setting('show_link_to_registration_page')) {
			if ($tagId = $this->setting('registration_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				if (ze\content::checkPerm($cID, $cType)) {
					$this->mergeFields['Registration_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}
		}
		
		if ($this->setting('show_link_to_login_page')) {
			if ($tagId = $this->setting('login_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				if (ze\content::checkPerm($cID, $cType)) {
					$this->mergeFields['Login_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}
		}

		if ($this->setting('show_link_to_contact_page')) {
			if ($tagId = $this->setting('contact_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				if (ze\content::checkPerm($cID, $cType)) {
					$this->mergeFields['Contact_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}
		}

		if ($this->setting('show_link_to_home_page')) {
			if ($tagId = $this->setting('home_page')) {
				$cID = $cType = false;
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
				if (ze\content::checkPerm($cID, $cType)) {
					$this->mergeFields['Home_Link'] = $this->linkToItemAnchor($cID, $cType);
				}
			}
		}
		
		parent::showSlot();
		$this->twigFramework($this->mergeFields);
	}

	function getMenuMergeFields(&$menuArray, $depth = 1, $parentId = 0) {
		//Override the parent module method.
		if ($depth>1000) {
			echo "Function aborted due to infinite recursion loop";
			exit;
		}
		
		$topLevelNodeId = 0;
		$menuMergeFields = [];
		$childrenArray = [];
		$parentIdsAndNames = [];
		$childrenExist = false;
		
		if (is_array($menuArray)) {
			$i = 0;
			foreach ($menuArray as &$row) {
				if (!$topLevelNodeId) {
					$topLevelNodeId = $row['mID'];
				}

				if ($menuNodeMergeFields = $this->getMenuNodeMergeFields($depth, ++$i, $row)) {
					if (!empty($row['children']) && (!$this->numLevels || $this->numLevels > 1)) {
						$childrenExist = true;
						$menuNodeMergeFields['hasChildren'] = true;

						$childrenArray[$depth][] = $row['mID'];
						$parentIdsAndNames[$row['mID']] = ['id' => $parentId, 'name' => $menuNodeMergeFields['Name']];

						$menuNodeMergeFields['parentDivForNextButton'] = 'zz_node_' . $topLevelNodeId;
						$menuNodeMergeFields['childDivForNextButton'] = 'zz_child_of_node_' . $row['mID'];
					}

					$menuMergeFields[$depth][$parentId][$row['mID']] = $menuNodeMergeFields;
				}
			}

			if (empty($childrenArray)) {
				$childrenExist = false;
			}

			while ($childrenExist) {
				$parentDepth = $depth;
				$depth++;

				$childrenToProcess = $childrenArray;
				$childrenArray = [];

				if (!$this->numLevels || $depth <= $this->numLevels) {
					foreach ($childrenToProcess[$parentDepth] as $parentId) {
						$subchildrenMenuArray =
							ze\menu::getStructure(
								$cachingRestrictions,
								$this->sectionId, $this->currentMenuId, $parentId,
								$this->numLevels, $this->maxLevel1MenuItems, $this->language,
								$this->onlyFollowOnLinks, $this->onlyIncludeOnLinks, 
								$this->showInvisibleMenuItems,
								$this->showMissingMenuNodes,
								$this->requests,
								ze\content::showUntranslatedContentItems()
							);

						foreach ($subchildrenMenuArray as $subchild) {
							$childrenExist = true;

							if ($menuNodeMergeFields = $this->getMenuNodeMergeFields($depth, ++$i, $subchild)) {
								$parentIdsAndNames[$subchild['mID']] = ['id' => $parentId, 'name' => $menuNodeMergeFields['Name']];
								
								$menuNodeMergeFields['parentId'] = $parentId;
								$menuNodeMergeFields['parentName'] = $parentIdsAndNames[$parentId]['name'];

								$menuNodeMergeFields['childDivForNextButton'] = 'zz_child_of_node_' . $subchild['mID'];

								if ($depth == 1 && (!$this->numLevels || $this->numLevels > 1)) {
									$menuNodeMergeFields['parentDivForNextButton'] = 'zz_node_' . $topLevelNodeId;
								} elseif ($depth == 2) {
									$menuNodeMergeFields['parentDivForPrevButton'] = 'zz_node_' . $topLevelNodeId;
									$menuNodeMergeFields['childDivForPrevButton'] = 'zz_child_of_node_' . $parentId;
									
									if (!$this->numLevels || (($depth + 1) <= $this->numLevels)) {
										$menuNodeMergeFields['parentDivForNextButton'] = 'zz_child_of_node_' . $parentId;
									}
								} else {
									$menuNodeMergeFields['parentDivForPrevButton'] = 'zz_child_of_node_' . $parentIdsAndNames[$parentId]['id'];
									$menuNodeMergeFields['childDivForPrevButton'] = 'zz_child_of_node_' . $parentIdsAndNames[$subchild['mID']]['id'];
									
									if (!$this->numLevels || (($depth + 1) <= $this->numLevels)) {
										$menuNodeMergeFields['parentDivForNextButton'] = 'zz_child_of_node_' . $parentId;
									}
								}

								if (!empty($subchild['children']) && isset($menuNodeMergeFields['parentDivForNextButton'])) {
									$menuNodeMergeFields['hasChildren'] = true;
								}
								
								$childrenArray[$depth][] = $subchild['mID'];
								$menuMergeFields[$depth][$parentId][$subchild['mID']] = $menuNodeMergeFields;
							}
						}
					}
				}

				if (empty($childrenArray)) {
					$childrenExist = false;
				}
			}
		}
		
		return $menuMergeFields;
	}

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$fields['first_tab/reverse_order']['hidden'] = true;
		$fields['first_tab/menu_show_all_branches']['hidden'] = true;

		$fields['first_tab/menu_number_of_levels']['values'] = [
			'all' => ['ord' => 1, 'label' => "Generate all levels"],
			'1' => ['ord' => 2, 'label' =>  "Only generate 1 level"],
			'2' => ['ord' => 3, 'label' => "Only generate 2 levels"],
			'3' => ['ord' => 4, 'label' => "Only generate 3 levels"],
			'4' => ['ord' => 5, 'label' => "Only generate 4 levels"]
		];
		
		if (!$box['key']['id']) {
			if (isset($values['push_pull/home_page']) && !$values['push_pull/home_page']) {
				$values['push_pull/home_page'] = "html_1";
			}
		}
	}
}