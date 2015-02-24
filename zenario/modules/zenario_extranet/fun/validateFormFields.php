<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


//Validate a form submission
$fields = array();
foreach($this->frameworkFields($section, $this->subSections) as $name => $field) {
	if (!$this->checkRequiredField($field)) {
		$this->errors[] = array('Error' => $this->phrase('_ERROR_'. strtoupper($name)));
	
	} elseif (empty($field['pattern'])) {
		//Do nothing
	
	} elseif (($field['pattern'] == 'email' || $field['pattern'] == 'new_email'
			|| $field['pattern'] == 'existing_email' || $field['pattern'] == 'unverified_email') && post($name)) {
		
		if (!validateEmailAddress(post($name))) {
			$this->errors[] = array('Error' => $this->phrase('_ERROR_INVALID_'. strtoupper($name)));
		
		} elseif ($field['pattern'] == 'new_email') {
			if (checkRowExists('users', array('email' => post($name)))
			 || checkRowExists('users', array('screen_name' => post($name)))) {
				if ($this->setting('initial_email_address_status')=='not_verified') {
					if (getRow('users', 'status', array('email' => post($name))) == 'contact') {
						$errorMessage = $this->setting('contact_not_extranet_message');
						$this->errors[] = array('Error' => $this->phrase($errorMessage));
					} else {
						$this->errors[] = array('Error' => $this->phrase('This email address is already in use and cannot be registered again.'));
					}
				} else {
					if (getRow('users', 'status', array('email' => post($name))) == 'contact') {
						$errorMessage = $this->setting('contact_not_extranet_message');
						$this->errors[] = array('Error' => $this->phrase($errorMessage));
					} else {
						$this->errors[] = array('Error' => $this->phrase('This email address is already in use and cannot be registered again.'));
					}
				}
			}
		
		} elseif ($field['pattern'] == 'existing_email' || $field['pattern'] == 'unverified_email') {
			if (!checkRowExists('users', array('email' => post($name)))) {
				$errorMessage = $this->setting('email_not_in_db_message');
				$this->errors[] = array('Error' => $this->phrase($errorMessage));
			
			} else {
				if ($field['pattern'] == 'unverified_email' && getRow('users', 'email_verified', array('email' => post($name)))) {
					$errorMessage = $this->setting('already_verified_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				}
				if ($field['pattern'] == 'unverified_email' && getRow('users', 'status', array('email' => post($name))) == 'contact') {
					$errorMessage = $this->setting('contact_not_extranet_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				}
				if ($field['pattern'] == 'unverified_email' && getRow('users', 'status', array('email' => post($name))) == 'suspended') {
					$errorMessage = $this->setting('account_suspended_message');
					$this->errors[] = array('Error' => $this->phrase($errorMessage));
				}
			}
		}
	
	} elseif (($field['pattern'] == 'screen_name' || $field['pattern'] == 'new_screen_name') && post($name)) {
		if (!validateScreenName(post($name))) {
			$this->errors[] = array('Error' => $this->phrase('_ERROR_INVALID_'. strtoupper($name)));
		
		} elseif ($field['pattern'] == 'new_screen_name') {
			if (checkRowExists('users', array('screen_name' => post($name)))) {
				//screen name already in use
				$this->errors[] = array('Error' => $this->phrase('This Screen Name is already in use on this site. Please choose another one.'));
			}
		}
	}
	
	$fields[$name] = post($name);
}

return empty($this->errors)? $fields : false;

?>