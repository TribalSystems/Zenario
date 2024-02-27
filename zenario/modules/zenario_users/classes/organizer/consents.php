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


class zenario_users__organizer__consents extends zenario_users {

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		//If it looks like a site is supposed to be using encryption, but it's not set up properly,
		//show an error message.
		ze\pdeAdm::showNoticeOnPanelIfConfIsBad($panel);
		
		
		foreach ($panel['items'] as $consentId => &$item) {
			//Show user details from consent in a single column
			$item['user'] = static::formatConsentUser($consentId);
			
			
		}
		//check if a column is encrypted and/or hashed.
		if(ze::$dbL->columnIsEncrypted('consents', 'first_name') || ze::$dbL->columnIsHashed('consents', 'first_name') ){
		    $panel["columns"]["user"]['encrypted'] = true;
		    $panel["columns"]["first_name"]['encrypted'] = true;
		} else if(ze::$dbL->columnIsEncrypted('consents', 'email') || ze::$dbL->columnIsHashed('consents', 'email')){
		    $panel["columns"]["user"]['encrypted'] = true;
		    $panel["columns"]["email"]['encrypted'] = true;
		} else if(ze::$dbL->columnIsEncrypted('consents', 'last_name') || ze::$dbL->columnIsHashed('consents', 'last_name')){
		    $panel["columns"]["user"]['encrypted'] =true;
		    $panel["columns"]["last_name"]['encrypted'] = true;
		} else {
		    $panel["columns"]["user"]['encrypted'] = false;
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (isset($_POST['delete'])) {
			foreach (explode(',', $ids) as $consentId) {
				ze\user::deleteConsent($consentId);
			}
		}
    }
}