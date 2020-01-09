<?php
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); 
/*
 * Copyright (c) 2020, Tribal Limited
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

// This plugin lets visitors email this page to a friend
class zenario_email_a_friend extends ze\moduleBaseClass {
	
	var $mergeFields = [];
	var $sections = [];
	var $displaySections = [];
	var $errors = '';
	
	function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = true, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if (($_REQUEST['floatingBoxMode'] ?? false)=='1'){
			$this->showInFloatingBox(); 
		}
		
		return true;
	}
	
	function checkVar($varName,$varLabel,$len){
		$errStr = '';
		if ($_POST[$varName] ?? false) {
			if (strlen($_POST[$varName] ?? false) > $len){
				$errStr = $this->phrase('_FIELD_LENGTH_EXCEEDED',['field_name'=>$varLabel]) . '<br/>';
				$this->mergeFields[$varName]='';
			}
		} else {
			$errStr = $this->phrase('_FIELD_MISSING_' . strtoupper($varName)) . '<br/>';
		}
		if ($errStr) {
			$this->errors .= $errStr;
			return false; 
		} else {
			return true;
		}
	}
	
	function checkEmailVar ($varName,$varLabel,$len){
		$errStr = '';
		if ($this->checkVar($varName,$varLabel,$len)) {
			if (!ze\ring::validateEmailAddress($_POST[$varName] ?? false)){
				$errStr = $this->phrase('_INVALID_EMAIL_ADDRESS',['field_name'=>$varLabel]) . '<br/>';
			}
		}
		if ($errStr){
			$this->errors .= $errStr;
			return false; 
		} else {
			return true;
		}
	}
	
	function showSlot() {
		if ($_REQUEST['floatingBoxMode'] ?? false){
			$this->displaySections['Email_friend_popup_header'] = true;
	
			$this->mergeFields['email'] = $_POST['email'] ?? false;
			$this->mergeFields['name'] = $_POST['name'] ?? false;
			$this->mergeFields['msg'] = $_POST['msg'] ?? false;
			$this->mergeFields['email_to'] = $_POST['email_to'] ?? false;
			
			$this->checkEmailVar('email_to',$this->phrase('_EMAIL_ADDRESS'),100);
			$this->checkEmailVar('email',$this->phrase('_EMAIL_FROM'),100);
			$this->checkVar('msg',$this->phrase('_MESSAGE'),1000);
			$this->checkVar('name',$this->phrase('_SENDER_NAME'),100);
			
			if ((!$this->errors) && $this->setting('email_template') ) {
				$mergeFields = ['sender_name'=>ze::post('name'), 
									'your_email'=>ze::post('email'),
									'sender_message'=>ze::post('msg'),
									'link_to_page'=>$this->linkToItem($this->cID,$this->cType,true),
									'page_title'=>ze\content::title($this->cID,$this->cType),
									'page_description'=>ze\content::description($this->cID,$this->cType,$this->cVersion)];
																		
				if (zenario_email_template_manager::sendEmailsUsingTemplate($_POST['email_to'] ?? false,$this->setting('email_template'),$mergeFields,[])){
					$this->displaySections['Email_friend_popup_msg_sent']=true;
				} else {
					$this->displaySections['Email_friend_sending_error']=true;
				}
			}else{
				if ($_POST['Go'] ?? false){
					$this->mergeFields['Error_message']=$this->errors;
					$this->displaySections['Input_error_section']=true;
					}
					
				$this->displaySections['Email_friend_popup_form_elements']=true;	
				$this->mergeFields['Open_form']=$this->openForm(). $this->remember('floatingBoxMode');
				}
			$this->framework('Email_friend_popup_window',$this->mergeFields,$this->displaySections);
		} else {
			$this->mergeFields['email_friend_onclick_attr_plus_script_plus_href'] = $this->refreshPluginSlotAnchor('floatingBoxMode=1');
			if ($this->setting('email_template')
				&& zenario_email_template_manager::
					getTemplateByCode($this->setting('email_template'))) {
				$this->sections['Email_friend_section'] = true;
			}
			
			$this->framework("Outer", $this->mergeFields, $this->sections);
		}
	}
}