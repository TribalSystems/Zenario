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


//Validate a form submission
$fields = [];

//We've lost the frameworkFields() function in version 8, as Twig doesn't support it.
//So we'll have to generate the entire framework to see what fields were on it
$this->twigFramework(array_merge([$section => true], $this->subSections), $return = true);

foreach($this->frameworkFields as $name => $field) {
	if (!$this->checkRequiredField($field)) {
		$this->errors[] = ['Error' => $this->phrase('_ERROR_'. strtoupper($name))];
	
	} elseif (empty($field['pattern'])) {
		//Do nothing
	
	} elseif (ze::in($field['pattern'], 'email', 'new_email', 'existing_email', 'unverified_email') && ($_POST[$name] ?? false)) {
		
		if (!ze\ring::validateEmailAddress($_POST[$name] ?? false)) {
			$this->errors[] = ['Error' => $this->phrase('_ERROR_INVALID_'. strtoupper($name))];
		
		} elseif ($field['pattern'] == 'new_email') {
			if ($user = ze\row::get('users', ['status'], ['email' => ($_POST[$name] ?? false)])) {
				if ($user['status'] == 'contact') {
					if (!$contactsCountAsUnregistered) {
						$errorMessage = $this->setting('contact_not_extranet_message');
						$this->errors[] = ['Error' => $this->phrase($errorMessage)];
					}
				} else {
					$errorMessage = $this->setting('email_already_registered');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
			}
		
		} elseif ($field['pattern'] == 'existing_email' || $field['pattern'] == 'unverified_email') {
			if (!ze\row::exists('users', ['email' => ($_POST[$name] ?? false)])) {
				$errorMessage = $this->setting('email_not_in_db_message');
				$this->errors[] = ['Error' => $this->phrase($errorMessage)];
			
			} else {
				if ($field['pattern'] == 'unverified_email' && ze\row::get('users', 'email_verified', ['email' => ($_POST[$name] ?? false)])) {
					$errorMessage = $this->setting('already_verified_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
				if ($field['pattern'] == 'unverified_email' && ze\row::get('users', 'status', ['email' => ($_POST[$name] ?? false)]) == 'contact') {
					$errorMessage = $this->setting('contact_not_extranet_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
				if ($field['pattern'] == 'unverified_email' && ze\row::get('users', 'status', ['email' => ($_POST[$name] ?? false)]) == 'suspended') {
					$errorMessage = $this->setting('account_suspended_message');
					$this->errors[] = ['Error' => $this->phrase($errorMessage)];
				}
			}
		}
	
	} elseif (($field['pattern'] == 'screen_name' || $field['pattern'] == 'new_screen_name') && ($_POST[$name] ?? false)) {
		if (!ze\ring::validateScreenName($_POST[$name] ?? false)) {
			$this->errors[] = ['Error' => $this->phrase('_ERROR_INVALID_'. strtoupper($name))];
		
		} elseif ($field['pattern'] == 'new_screen_name') {
			if (ze\row::exists('users', ['screen_name' => ($_POST[$name] ?? false)])) {
				$errorMessage = $this->setting('screen_name_in_use');
				$this->errors[] = ['Error' => $this->phrase($errorMessage)];
			}
		}
	}
	
	$fields[$name] = $_POST[$name] ?? false;
}

return empty($this->errors)? $fields : false;
