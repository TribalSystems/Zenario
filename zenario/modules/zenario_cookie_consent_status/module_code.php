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

class zenario_cookie_consent_status extends ze\moduleBaseClass {
	
	protected $sections = [];
	protected $mergeFields = [];
	
	public function init() {
		if (ze::setting('cookie_require_consent') != 'explicit') {
			return false;
		}
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if (ze::setting('individual_cookie_consent')) {
			$this->mergeFields['individual'] = true;
			
			//Save changes
			if (isset($_POST['save'])) {
				$cookieTypes = ['required'];
				if (isset($_POST['functionality'])) {
					$cookieTypes[] = 'functionality';
				}
				if (isset($_POST['analytics'])) {
					$cookieTypes[] = 'analytics';
				}
				if (isset($_POST['social_media'])) {
					$cookieTypes[] = 'social_media';
				}
				ze\cookie::setConsent(implode(',', $cookieTypes));
				$this->mergeFields['saved'] = true;
				$this->callScript('zenario_cookie_consent_status', 'onConsentSave', $this->containerId);
			}
			
			//Load consent data
			$this->mergeFields['functionality'] = ze\cookie::canSet('functionality') ? 'checked' : '';
			$this->mergeFields['analytics'] = ze\cookie::canSet('analytics') ? 'checked' : '';
			$this->mergeFields['social_media'] = ze\cookie::canSet('social_media') ? 'checked' : '';
			
		} else {
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
		}
		
		return true;
	}
	
	public function showSlot() {
		if (ze::setting('cookie_require_consent') != 'explicit') {
			if (ze\priv::check()) {
				echo ze\admin::phrase('This plugin will only be displayed if the Explicit consent policy is chosen in the Cookies panel (see Configuration->Site Settings).');
			}
		
		} else {
			$this->framework('Outer', $this->mergeFields, $this->sections);
		}
	}
}
