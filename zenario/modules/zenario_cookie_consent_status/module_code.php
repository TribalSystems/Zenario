<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class zenario_cookie_consent_status extends module_base_class {
	
	protected $sections = array();
	protected $mergeFields = array();
	
	public function init() {
		
		if (setting('cookie_require_consent') != 'explicit') {
			return false;
		
		} else {
			//Note that it's perfectly safe to cache this Plugin, as the three different situations
			//(cookies rejected, cookies accepted and not chosen) are stored differently.
			$this->allowCaching(
				$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
			
			
			if (!empty($_SESSION['cookies_rejected'])) {
				$this->mergeFields['Status'] = $this->phrase('_REJECTED');
				$this->mergeFields['Status_Css_Class'] = 'cookie_rejected';
			
			} elseif (!empty($_COOKIE['cookies_accepted'])) {
				$this->mergeFields['Status'] = $this->phrase('_ACCEPTED');
				$this->mergeFields['Status_Css_Class'] = 'cookie_accept';
			
			} else {
				$this->mergeFields['Status'] = $this->phrase('_NOT_CHOSEN');
				$this->mergeFields['Status_Css_Class'] = 'cookie_not_chosen';
			}
			
			if (!empty($_COOKIE['cookies_accepted'])) {
				$this->sections['Accepted_Cookies'] = true;
			} else {
				$this->sections['Rejected_Cookies'] = true;
			}
			
			$this->mergeFields['Accept_Link'] = 'zenario/cookies.php?accept_cookies=1';
			$this->mergeFields['Reject_Link'] = 'zenario/cookies.php?accept_cookies=0';
			
			return true;
		}
	}
	
	public function showSlot() {
		if (setting('cookie_require_consent') != 'explicit') {
			if (checkPriv()) {
				echo adminPhrase('This Plugin will only be displayed if the Explicit consent policy is chosen in the Cookie Site Settings.');
			}
		
		} else {
			$this->framework('Outer', $this->mergeFields, $this->sections);
		}
	}
}