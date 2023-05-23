<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_crm_form_integration__admin_boxes__site_settings extends zenario_crm_form_integration {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$soapEnabled = extension_loaded('soap');
		if (!$soapEnabled) {
			$values['360lifecycle/zenario_crm_form_integration__enable_360lifecycle'] = false;
			$fields['360lifecycle/zenario_crm_form_integration__enable_360lifecycle']['readonly'] = true;
			$box['tabs']['360lifecycle']['notices']['soap_not_enabled']['show'] = true;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($fields['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__salesforce_test_connection_button']['pressed'] ?? false) {
			$error = '';
			$success = '';
			if (!$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__client_id']
				|| !$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__client_secret']
				|| !$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__username']
				|| !$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__password']
				|| !$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__security_token']
				|| !$values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__login_uri']
			) {
				$error = ze\admin::phrase('Please make sure the "Username", "Password", "Client ID", "Client secret key", "User\'s security token" and "Login URI" are not blank.');
			} else {
				$clientId = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__client_id'];
				$clientSecretKey = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__client_secret'];
				$username = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__username'];
				$password = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__password'];
				$userSecurityToken = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__security_token'];
				$loginURI = $values['zenario_salesforce_api_form_integration/zenario_salesforce_api_form_integration__login_uri'];
				
				$result = zenario_crm_form_integration::testSalesforceConnection($clientId, $clientSecretKey, $username, $password, $userSecurityToken, $loginURI);
				if ($result) {
					$success = ze\admin::phrase('Connection successful.');
				} else {
					$error = ze\admin::phrase('Connection failed.');
				}
			}
			
			if ($error) {
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_success']['show'] = false;
				
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_error']['show'] = true;
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_error']['message'] = $error;
			}
			if ($success) {
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_error']['show'] = false;
				
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_success']['show'] = true;
				$box['tabs']['zenario_salesforce_api_form_integration']['notices']['test_connection_success']['message'] = $success;
			}
		}	
	}
	
}