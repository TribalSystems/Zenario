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





class zenario_user_redirector extends ze\moduleBaseClass {

	protected $data = [];

	public function init() {

		if ((int)($_SESSION['admin_userid'] ?? false)) {
			$this->data["admin"] = true;
			
			// If an admin is logged in, redirects will not work. Try and get the page the plugin wants to
			// redirect us to, and display it to the admin so they are aware it exists!
			$tagId = $this->redirectToPage(true, true, true, $returnDestinationOnly=true);
			$success = ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
			if ($success) {
				$this->linkToItem($cID, $cType, true);
				$this->data["admin_message"] = ze\admin::phrase(
					"Redirector plugin here: redirection suspended because you are an administrator. <a href='[[link]]'>Click here to follow the redirection rule to [[tag_id]]</a>",
					["link" => $this->linkToItem($cID, $cType, true), "tag_id" => ze\content::formatTag($cID, $cType)]
				);
			// Fallback just in case we were unable to get a link for some reason...
			} else {
				$this->data["admin_message"] = ze\admin::phrase("Redirector plugin here: redirection suspended because you are an administrator.");
			}
			return true;
		}
		
		if ($_SESSION['extranetUserID'] ?? false) {
			$this->redirectToPage();
			return true;
		}
		
		if ($this->setting('show_default')
		 && $this->getCIDAndCTypeFromSetting($cID, $cType, 'redirect_default')) {
			$this->headerRedirect($this->linkToItem($cID, $cType, true));
			return true;
		}
		
		return false;
	}
	
	function showSlot() {
		$this->twigFramework($this->data);
	}

	protected function checkPermsOnDestURL() {
		return !empty($_SESSION['destCID']) && !empty($_SESSION['destCType']) && ze\content::checkPerm($_SESSION['destCID'], $_SESSION['destCType']);
	}
	
	public function setupRedirectRuleRows(&$box, &$fields, &$values, $changes, $filling, $addRows = 0) {
		return ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = false,
			$box['tabs']['first_tab']['redirect_rule_template_fields'],
			$addRows,
			$minNumRows = 0,
			$tabName = 'first_tab',
			$deleteButtonCodeName = 'remove__znz'
		);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch($path) {
			case 'plugin_settings':
				//Load lists for redirect rules and disable "role" based rules if organization manager is not running.
				$box['lovs']['groups'] = ze\datasetAdm::listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true, $hideEmptyOptGroupParents = true);
				if ($allowRoleRedirectRules = ze\module::inc('zenario_organization_manager')) {
					$box['lovs']['roles'] = ze\row::getValues(ZENARIO_ORGANIZATION_MANAGER_PREFIX . 'user_location_roles', 'name', [], 'name', 'id');
				} else {
					$box['tabs']['first_tab']['redirect_rule_template_fields']['redirect_rule_type__znz']['values']['role']['disabled'] = true;
				}
				
				//Setup multi-rows for redirect rules.
				if (isset($fields['first_tab/number_of_redirect_rules'])) {
					$addRows = (int)$values['first_tab/number_of_redirect_rules'];
					$changes = [];
					$multiRows = $this->setupRedirectRuleRows($box, $fields, $values, $changes, $filling = true, $addRows);
					$values['first_tab/number_of_redirect_rules'] = $multiRows['numRows'];
				
					$valuesInDB = [];
					ze\tuix::loadAllPluginSettings($box, $valuesInDB);
					for ($i = 1; $i <= $addRows; $i++) {
						$type = $valuesInDB['redirect_rule_type__' . $i] ?? false;
						if ($type && ($type != 'role' || $allowRoleRedirectRules)) {
							$values['first_tab/redirect_rule_type__' . $i] = $type;
							$values['first_tab/redirect_rule_group__' . $i] = $valuesInDB['redirect_rule_group__' . $i] ?? false;
							$values['first_tab/redirect_rule_role__' . $i] = $valuesInDB['redirect_rule_role__' . $i] ?? false;
							$values['first_tab/redirect_rule_content_item__' . $i] = $valuesInDB['redirect_rule_content_item__' . $i] ?? false;
						}
					}
				}
				
				//Select the home page as the default redirect page.
				$fields['first_tab/welcome_page']['value'] = ze::$specialPages['zenario_home'] ?? '';
				
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				if (isset($box['tabs']['first_tab']['fields']['welcome_page'])
				 && isset($box['tabs']['first_tab']['fields']['show_welcome_page'])) {
					$box['tabs']['first_tab']['fields']['welcome_page']['hidden'] = 
						$values['first_tab/show_welcome_page'] != '_ALWAYS'
					 && $values['first_tab/show_welcome_page'] != '_IF_NO_PREVIOUS_PAGE';
				}
				
				//Handle redirect rules multi-row updates
				if (isset($fields['first_tab/number_of_redirect_rules'])) {
					$addRows = !empty($box['tabs']['first_tab']['fields']['add_redirect_rule']['pressed']);
					$multiRows = $this->setupRedirectRuleRows($box, $fields, $values, $changes, $filling = false, $addRows);
					$values['first_tab/number_of_redirect_rules'] = $multiRows['numRows'];
				}
				
				break;
		}		
	}
	
}
	