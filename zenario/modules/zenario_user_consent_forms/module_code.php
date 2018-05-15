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

class zenario_user_consent_forms extends zenario_user_forms {
	
	public function init() {
		
		$formId = $this->setting('user_form');
		
		//This plugin must have a form selected
		if (!$formId) {
			if (ze\admin::id()) {
				$this->data['form_HTML'] = '<p class="error">' . ze\admin::phrase('You must select a form for this plugin.') . '</p>';
			}
			return true;
		}
		
		$userId = $this->getUserId();
		
		if(!$userId) {
		    $hashDone = ze\row::get('users', 'id', ['consent_hash' => $_GET['hash'] . '-DONE']);
		    if ($hashDone) {
		    	$this->data['form_HTML'] = "<p class='success'>".$this->phrase("You've already responded to this form."). "</p>";
		    	return true;
		    } else {
				$this->data['form_HTML'] = "<p class='error'>".$this->phrase('No user found.'). "</p>";
				return true;
			}
		}
		
		$rv = parent::init();
		return $rv;
	}
	
	protected function getUserId() {
	   $hash = $this->getUserConsentHash();
	   if ($hash) {
	        $userId = (int) ze\row::get('users', 'id', ['consent_hash' => $hash]);
	       
	        
			return $userId;
		} else {
			return 0;
		}
	}
	
	protected function getUserConsentHash() {
	    if(!isset($_GET['hash'])){
	        $hash = false;
	    } else {
	        $hash = $_GET['hash'];
	    }
	    return $_POST['zenario_user_consent_forms__hash'] ?? $hash;
	}
	
	protected function getCustomRequests() {
	    $requests = ['zenario_user_consent_forms__hash' => $this->getUserConsentHash()];
	    return $requests;   
	}
	
	protected function successfulFormSubmit($responseId) {
	    $userId = $this->getUserId();
	    //update hash to null in db
        $hash = ze\row::get('users', 'consent_hash', ['id' => $userId]);
        ze\row::update('users', ['consent_hash' => $hash . '-DONE'] , ['id' => $userId]);
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	    switch ($path) {
		    case "zenario_user_consent_forms__consent_emails":
		        $contentItemArray =  ze\row::get('special_pages',["equiv_id","content_type"],["page_type" => "zenario_user_consent_form"]);
		        $values['content_item'] = $contentItemArray["content_type"]."_".$contentItemArray["equiv_id"];
		      
		       
		    break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	    switch ($path) {
		    case "zenario_user_consent_forms__consent_emails":
		        if ($values['details/smart_group']) {
		        	$fields['details/smart_group']['note_below'] = ze\admin::phrase("<p>This email will be sent to [[count]] recipients.</p>", ['count' => ze\smartGroup::countMembers($values['details/smart_group'])]);
		        }
		       
		    break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
		    case "zenario_user_consent_forms__consent_emails":
		        
                $details['id'] = $values['details/smart_group'];
                $details['email_template'] = $values['details/email_template'];
        
        
                $members = ze\smartGroup::getMemberIds($details['id']);//User Ids from smartGroup Id
			
                if(!empty($members)){
                    $sql = 'select id, email, first_name, last_name from '.DB_PREFIX ."users 
                            where id in (".implode(",",$members).")";
                
                    $result = ze\sql::select($sql);
        
                    while ($row = ze\sql::fetchAssoc($result)) {
           
                        $details['content_item'] = $values['details/content_item'];
                        $hash = ''.\ze\escape::sql(\ze\userAdm::createHash($row['id'], $row['email'])).'';
            
                        ze\sql::update("UPDATE ". DB_PREFIX . "users SET  consent_hash= '".$hash."' where id =".(int)$row['id']);
             
                        $contentLink= ze\link::toItem($details['content_item'], "html", $fullPath = true, $request = '&hash='. $hash);
                        
                        $details['first_name'] = $row['first_name']; 
                        $details['last_name'] = $row['last_name']; 
                        $details['cms_url'] = ze\link::absolute();
                        $details['content_item'] = $contentLink; 
                        zenario_email_template_manager::sendEmailsUsingTemplate($row['email'],$details['email_template'],$details);      
                    }
                 }
               
            break;
        }
	}	
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
		    case "zenario_user_consent_forms__consent_emails":
                $members = ze\smartGroup::getMemberIds($values['details/smart_group']);//User Ids from smartGroup Id
                if(empty($members)){
                    $fields["details/smart_group"]["error"] = "No recipients found in the smart group.";
                }
		    
		    break;
		}
	}
}
