<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


class zenario_contact_form extends module_base_class {
	
	var $errors = array();
	var $message = false;
	var $mode = 'Contact_Form';
	var $objects = array();

	var $mergeFields = array();
	var $sections = array();
	
	function init()	{
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		
		if (($this->setting('display_mode')=='in_modal_window') && (!empty($_REQUEST['show_contact_form']) || $this->checkPostIsMine())) {
			$this->showInFloatingBox();
		}		
		
		if ($this->setting("captcha_type") == "math") {
			require_once CMS_ROOT. 'zenario/libraries/mit/securimage/securimage.php';
		}
		
		if ($this->mode == 'Contact_Form') {
			if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
				if (empty($_SESSION['extranetUserID']) || $this->setting('extranet_users_use_captcha')) {
					if ($this->setting("captcha_type") == "words") {
						$this->mergeFields['Captcha'] = $this->captcha();
						$this->sections['Captcha'] = true;
					} elseif ($this->setting("captcha_type") == "math") {
						$this->sections['Math_Captcha'] = true;
						$this->mergeFields['Math_Captcha'] = 
						
						'<p>
    						<img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="zenario/libraries/mit/securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left">
    						
							&nbsp;
							<a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById(\'siimage\').src = \'zenario/libraries/mit/securimage/securimage_show.php?sid=\' + Math.random(); this.blur(); return false">
								<img src="zenario/libraries/mit/securimage/images/refresh.png" alt="Reload Image" onclick="this.blur()" align="bottom" border="0">
							</a><br />
							Enter Code:<br />
							<input type="text" name="captcha_code" size="12" maxlength="16" class="math_captcha_input"/>
						</p>';
						
						
					}
					
				}
			}
		}
		
		if (post('contact_form_submit')) {
			$this->scrollToTopOfSlot();
			if ($this->submitContactForm()) {
				$this->mode = 'Contact_Form_Sent';
				$this->message = $this->phrase('_CONTACT_FORM_CONFIRMATION_MSG');
			}
		}
	
		return true;
	}
	
	function prepareSectionForDisplay($varName, $phraseName = false) {
		if (!$phraseName) {
			$phraseName = $this->setting($varName. '_name');
		}
		
		if ($this->setting($varName) == 'optional' || $this->setting($varName) == 'mandatory') {
			$this->mergeFields[$varName . '_label'] = $this->phrase($phraseName. ':');
			$this->sections[$varName] = true;
		}
	}
	
	
	function showSlot() {
		
		if (($this->setting('display_mode')=='inline_in_page') 
				|| (($this->setting('display_mode')=='in_modal_window') && (!empty($_REQUEST['show_contact_form']) || $this->checkPostIsMine()))) {
				
			$this->mergeFields['Contact_from_header'] = $this->phrase('_HEADING');
			$this->mergeFields['Clear_Link'] = $this->refreshPluginSlotAnchor();
			$this->mergeFields['Clear_JS'] = $this->refreshPluginSlotJS();
			
			//A message if given
			if ($this->message) {
				$this->sections['Message_Display']=true;
				$this->mergeFields['Message']=$this->message;
			}
			
			//Display any errors we encountered
			if (count($this->errors)){
				$this->sections['Error_Display']=$this->errors;
			}
			
			if ($this->mode == 'Contact_Form') {
				//Display a form that lets the user enter the email address that they used to register,
				//and then have their password emailed to them
				$this->sections['Contact_Form'] = true;
				$this->sections['Contact_Form_Buttons'] = true;
	
				$this->mergeFields['Open_Form']=$this->openForm();
					$this->prepareSectionForDisplay('Name_field', '_NAME');
					$this->prepareSectionForDisplay('Email_field', '_EMAIL');
					$this->prepareSectionForDisplay('Phone_field', '_PHONE');
					$this->prepareSectionForDisplay('Additional_field_1');
					$this->prepareSectionForDisplay('Additional_field_2');
					$this->prepareSectionForDisplay('Additional_field_3');
					$this->prepareSectionForDisplay('Additional_field_4');
					$this->prepareSectionForDisplay('Additional_field_5');
					$this->prepareSectionForDisplay('Additional_field_6');
					$this->prepareSectionForDisplay('Textarea_field');
				$this->mergeFields['Close_Form'] = $this->closeForm();
			}
			$this->framework('Outer', $this->mergeFields, $this->sections);
		} else {
			$this->mergeFields = array('Show_form' => $this->refreshPluginSlotAnchor('show_contact_form=1', false, false));
			$this->framework('Contact_Form_Banner', $this->mergeFields);
		}
	}
	
	function processVar($varName, $phraseName = false) {
		if (!$phraseName) {
			$phraseName = $this->setting($varName. '_name');
		}
		
		$this->objects[$varName . '_label'] = $this->phrase($phraseName. ':');
		
		if (post($varName)) {
			$this->objects[$varName]= post($varName);
		} elseif ($this->setting($varName) == 'mandatory') {
			$this->errors[] = array('Error' => $this->phrase($phraseName. '_INCOMPLETE'));
		}
	}
	
	function processEmailVar($varName, $phraseName = false) {
		if (!$phraseName) {
			$phraseName = $this->setting($varName. '_name');
		}
		
		if ($this->setting($varName) == 'optional' || $this->setting($varName) == 'mandatory') {
			$this->objects[$varName . '_label'] = $this->phrase($phraseName. ':');
			
			if (post($varName)) {
				if (validateEmailAddress(post($varName))) {
					$this->objects[$varName]= post($varName);
				
				} else {
					$this->errors[] = array('Error' => $this->phrase($phraseName. '_INVALID'));
				}
			
			} elseif ($this->setting($varName) == 'mandatory') {
				$this->errors[] = array('Error' => $this->phrase($phraseName. '_INCOMPLETE'));
			}
		}
	}

	function submitContactForm(){
		$this->objects['cms_url'] = absCMSDirURL();
		$this->objects['ip_address'] = visitorIP();
		
		$this->processVar('Name_field', '_NAME');
		$this->processEmailVar('Email_field', '_EMAIL');
		$this->processVar('Phone_field', '_PHONE');
		$this->processVar('Additional_field_1');
		$this->processVar('Additional_field_2');
		$this->processVar('Additional_field_3');
		$this->processVar('Additional_field_4');
		$this->processVar('Additional_field_5');
		$this->processVar('Additional_field_6');
		$this->processVar('Textarea_field');
		
		if ($this->setting('use_captcha') && empty($_SESSION['captcha_passed__'. $this->instanceId])) {
			if (empty($_SESSION['extranetUserID']) || $this->setting('extranet_users_use_captcha')) {
				if ($this->setting("captcha_type") == "words") {
					if ($this->checkCaptcha()) {
						$_SESSION['captcha_passed__'. $this->instanceId] = true;
					} else {
						$this->errors[] = array('Error' => $this->phrase('_CAPTCHA_INVALID'));
					}
				} elseif ($this->setting("captcha_type") == "math") {
					$securimage = new Securimage();
					if ($securimage->check($_POST['captcha_code']) == false) {
						$this->errors[] = array('Error' => $this->phrase('_CAPTCHA_INVALID'));
					} else {
						$_SESSION['captcha_passed__'. $this->instanceId] = true;
					}
				}
				
			}
		}
		
		if (!count($this->errors)){
			if ($this->setting('email_address') && $this->setting('email_template')) {
				unset($_SESSION['captcha_passed__'. $this->instanceId]);
				
				$addressReplyTo = $nameReplyTo = false;
				if($this->setting('add_reply_to')) {
					$addressReplyTo = $this->setting('reply_to_email_address');
					
					if(isset($this->objects[$addressReplyTo])) {
						$addressReplyTo = $this->objects[$addressReplyTo];
						
						$field_first_name = $this->setting('reply_to_first_name');
						if(isset($this->objects[$field_first_name])) {
							$nameReplyTo = $this->objects[$field_first_name];
						}
						$field_last_name = $this->setting('reply_to_last_name');
						if(isset($this->objects[$field_last_name])) {
							$nameReplyTo .= ' ' . $this->objects[$field_last_name];
						}
						if(!$nameReplyTo) {
							$nameReplyTo = "[[$field_first_name]] [[$field_last_name]]";
						}
					}
					
				}
				
				if (zenario_email_template_manager::sendEmailsUsingTemplate($this->setting('email_address'), $this->setting('email_template'), 
								$this->objects, array(), array(), false, $addressReplyTo, $nameReplyTo)) {
					if($this->setting('use_thankyou_page')){
						$cID = $cType = false;
						$linkExists = $this->getCIDAndCTypeFromSetting(
								$cID, $cType,
								'thankyou_page');
						if($linkExists) $this->headerRedirect($this->linkToItem($cID, $cType));
					}
					return true;
				
				} else {
					$this->errors[] = array('Error' => $this->phrase('_EMAIL_MESSAGE_NOT_SENT'));
					return false;
				}
			} else {
				$this->errors[] = array('Error' => $this->phrase('_EMAIL_MESSAGE_NOT_SENT'));
				return false;
			}
		}
		return false;
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'plugin_settings':
				
				$fields["captcha/captcha_type"]["hidden"] = 
				$fields["captcha/extranet_users_use_captcha"]["hidden"] = 
				empty($values['captcha/use_captcha']);
				
				if (empty($values['captcha/use_captcha'])){
					$values["captcha/extranet_users_use_captcha"] = false;
					$values["captcha/captcha_type"] = "words";
				}
				
				if (arrayKey($values,'first_tab/Textarea_field')=='hidden'){
					unset($box['tabs']['first_tab']['fields']['Textarea_field_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_1')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_1_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_2')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_2_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_3')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_3_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_4')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_4_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_5')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_5_name']);
				}
				if (arrayKey($values,'additional_fields/Additional_field_6')=='hidden'){
					unset($box['tabs']['additional_fields']['fields']['Additional_field_6_name']);
				}
				$box['tabs']['first_tab']['fields']['thankyou_page']['hidden'] = !$values['first_tab/use_thankyou_page'];

				$box['tabs']['email']['fields']['email_template']['hidden'] = 
					$box['tabs']['email']['fields']['email_address']['hidden'] = 
						$values['email/send_email']!='send';

				$fields['email/reply_to_email_address']['hidden'] =
				$fields['email/reply_to_first_name']['hidden'] =
				$fields['email/reply_to_last_name']['hidden'] = !$values['email/add_reply_to'];
				
				break;
		}
	}
	
}
