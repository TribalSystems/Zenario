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


//Redirect the user to the welcome page, if one is set and enabled in the Plugin Settings
$cID = $cType = false;

//Check to see if there is a valid destination URL
$validDestURL = $homePageRequested = false;
$privacy = false;
if ($_SESSION['destURL'] ?? false) {
	$validDestURL = true;
	
	if ($_SESSION['destCID'] ?? false) {
		//Check to see if the destination URL is not a special page (except for the home page)
		if ($specialPage = ze\content::isSpecialPage($_SESSION['destCID'] ?? false, ($_SESSION['destCType'] ?? false))) {
			if ($specialPage != 'zenario_home') {
				$validDestURL = false;
			} else {
				$homePageRequested = true;
			}
		}
		//Check to see if the destination URL is private or public
		$equivId = ze\content::equivId($_SESSION['destCID'] ?? false, ($_SESSION['destCType'] ?? false));
		$privacy = ze\row::get('translation_chains', 'privacy', ['equiv_id' => $equivId, 'type' => ($_SESSION['destCType'] ?? false)]);
	}
}

//Follow redirect rules. Conditions below explained:
//1) Redirect based on rules: always redirect;
//2) Redirect based on rules, user page request is prioritised: special page requested;
//3) Redirect based on rules, user page request is prioritised: public content item requested;
//4) Redirect based on rules, user page request is prioritised: home page (with privacy setting set to private) requested.
if ($showWelcomePage
	&& ( $this->setting('show_welcome_page') == '_ALWAYS'
	||  ($this->setting('show_welcome_page') == '_IF_NO_PREVIOUS_PAGE' && !$validDestURL)
	||  ($this->setting('show_welcome_page') == '_IF_NO_PREVIOUS_PAGE' && ($privacy == 'public'))
	||  ($this->setting('show_welcome_page') == '_IF_NO_PREVIOUS_PAGE' && $homePageRequested && ($privacy != 'public')))
) {
	//..Get page according to redirect rules
	$allowRoleRedirectRules = ze\module::inc('zenario_organization_manager');
	for ($i = 1; $i <= $this->setting('number_of_redirect_rules'); $i++) {
		if ($this->setting('redirect_rule_type__' . $i) == 'group') {
			if (($groupId = $this->setting('redirect_rule_group__' . $i)) && ze\user::isInGroup($groupId)) {
				$this->getCIDAndCTypeFromSetting($cID, $cType, 'redirect_rule_content_item__' . $i);
				break;
			}
		} elseif ($this->setting('redirect_rule_type__' . $i) == 'role') {
			if ($allowRoleRedirectRules 
				&& ($roleId = $this->setting('redirect_rule_role__' . $i)) 
				&& zenario_organization_manager::getUserRoleLocations(ze\user::id(), $roleId)
			) {
				$this->getCIDAndCTypeFromSetting($cID, $cType, 'redirect_rule_content_item__' . $i);
				break;
			}
		}
	}
	//..Fallback page
	if (!$cID && !$cType) {
		$this->getCIDAndCTypeFromSetting($cID, $cType, 'welcome_page');
	}
	ze\content::langEquivalentItem($cID, $cType);

	if ($returnDestinationOnly) {
		return ze\content::formatTag($cID, $cType, false, false, true);
	}
	$this->headerRedirect($this->linkToItem($cID, $cType, true));
	
//Otherwise attempt to redirect the user back where they came from
} elseif ($redirectBackIfPossible && $validDestURL && ($redirectRegardlessOfPerms || $this->checkPermsOnDestURL())) {
	if ($returnDestinationOnly) {
		return ze\content::formatTag($_SESSION['destCID'], $_SESSION['destCType'], false, false, true);
	}
	$this->headerRedirect($_SESSION['destURL']);

//Otherwise stay on the current page
} else {
	if ($returnDestinationOnly) {
		return ze\content::formatTag($this->cID, $this->cType, false, false, true);
	}
	$this->headerRedirect($this->linkToItem($this->cID, $this->cType, true));
}
