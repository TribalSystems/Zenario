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


//Redirect the user to the welcome page, if one is set and enabled in the Plugin Settings
$cID = $cType = false;

//Check to see if there is a valid destination URL
$validDestURL = false;
$privacy = false;
if ($_SESSION['destURL'] ?? false) {
	$validDestURL = true;
	
	if ($_SESSION['destCID'] ?? false) {
		//Check to see if the destination URL is not a special page (except for the home page)
		if ($specialPage = isSpecialPage($_SESSION['destCID'] ?? false, ($_SESSION['destCType'] ?? false))) {
			if ($specialPage != 'zenario_home') {
				$validDestURL = false;
			}
		}
		//Check to see if the destination URL is private or public
		$equivId = equivId($_SESSION['destCID'] ?? false, ($_SESSION['destCType'] ?? false));
		$privacy = getRow('translation_chains', 'privacy', array('equiv_id' => $equivId, 'type' => ($_SESSION['destCType'] ?? false)));
	}
}

if ($showWelcomePage
	&& ( $this->getCIDAndCTypeFromSetting($cID, $cType, 'welcome_page'))
	&& ( $this->setting('show_welcome_page') == '_ALWAYS'
	||  ($this->setting('show_welcome_page') == '_IF_NO_PREVIOUS_PAGE' && !$validDestURL)
	||  ($this->setting('show_welcome_page') == '_IF_NO_PREVIOUS_PAGE' && ($privacy == 'public')))
) {
	langEquivalentItem($cID, $cType);
	$this->headerRedirect($this->linkToItem($cID, $cType, true));
//Otherwise attempt to redirect the user back where they came from
} elseif ($redirectBackIfPossible && $validDestURL && ($redirectRegardlessOfPerms || $this->checkPermsOnDestURL())) {
	$this->headerRedirect($_SESSION['destURL']);

//Otherwise stay on the current page
} else {
	$this->headerRedirect($this->linkToItem($this->cID, $this->cType, true));
}
?>