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

class zenario_user_forms__organizer__user_forms extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($refinerName == 'archived') {
			$panel['db_items']['where_statement'] = 'WHERE TRUE';
			$panel['no_items_message'] = ze\admin::phrase('No forms have been archived.');
		}

		if (!ze\module::inc('zenario_extranet_profile_edit')) {
			$panel['db_items']['where_statement'] .= '
				AND f.type != "profile"';
			$panel['collection_buttons']['create_profile_form']['hidden'] = true;
		}

		if (!ze\module::inc('zenario_crm_form_integration')) {
			unset($panel['columns']['crm']);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$addFullDetails = ze::in($mode, 'full', 'quick', 'select');
		
		if ($refinerName == 'email_address_setting') {
			unset($panel['collection_buttons']);
			unset($panel['item_buttons']);
			$panel['title'] = ze\admin::phrase('Summary of email addresses used by forms');
			$panel['no_items_message'] = ze\admin::phrase('No forms send emails to a specific address.');
			
			$panel['columns']['field_count']['hidden'] = true;
			$panel['columns']['response_count']['hidden'] = true;
			$panel['columns']['latest_response']['hidden'] = true;
			$panel['columns']['where_used']['hidden'] = true;
			$panel['columns']['name']['html'] = true;
			foreach ($panel['items'] as &$item) {
				$item['name'] = '<a href="organizer.php#zenario__user_forms/panels/user_forms//' . $item['id'] . '" target="_blank">' . $item['name'] . '</a>';
			}
		} else {
			unset($panel['columns']['form_email_addresses']);
		}
		
		if ($refinerName == 'type' && $refinerId == 'registration') {
			$panel['no_items_message'] = ze\admin::phrase('There are no registration forms.');
		}
		
		if (!ze::setting('zenario_user_forms_enable_predefined_text')) {
			$panel['item_buttons']['edit_predefined_text']['hidden'] = true;
		}
		
		foreach ($panel['items'] as $id => &$item) {
			
			if ($addFullDetails) {
				//Get plugin instances using this form...
				$usage = [];
				$usageLinks = false;
				
				$instanceIds = zenario_user_forms::getFormPlugins($id);
				
				if (!empty($instanceIds)) {
					$pluginIds = zenario_user_forms::getFormPlugins($id, 'plugins');
					$nestIds = zenario_user_forms::getFormPlugins($id, 'nests');
					$slideshowIds = zenario_user_forms::getFormPlugins($id, 'slideshows');
					
					$instanceId = $instanceIds[0];
					
					$usage = ze\pluginAdm::getUsage($instanceIds);
					
					if (!empty($pluginIds)) {
						$usage['plugins'] = count($pluginIds);
						$usage['plugin'] = $pluginIds[0];
					}
					if (!empty($nestIds)) {
						$usage['nests'] = count($nestIds);
						$usage['nest'] = $nestIds[0];
					}
					if (!empty($slideshowIds)) {
						$usage['slideshows'] = count($slideshowIds);
						$usage['slideshow'] = $slideshowIds[0];
					}
					
					if (!empty($usage['content_items']) || !empty($usage['layouts'])) {
						$item['plugin_is_used'] = true;
					}
				}
			
				$usageLinks = [
					'plugins' => 'zenario__user_forms/panels/user_forms/hidden_nav/plugins_using_form//'. (int) $id. '//', 
					'nests' => 'zenario__user_forms/panels/user_forms/hidden_nav/nests_using_form//'. (int) $id. '//', 
					'slideshows' => 'zenario__user_forms/panels/user_forms/hidden_nav/slideshows_using_form//'. (int) $id. '//', 
					'content_items' => 'zenario__user_forms/panels/user_forms/hidden_nav/content_items_using_form//'. (int) $id. '//', 
					'layouts' => 'zenario__user_forms/panels/user_forms/hidden_nav/layouts_using_form//'. (int) $id. '//'
				];
				
				//If a form is not used anywhere, the "not used" string below will be passed to the phrase function.
				$overrideNotUsedMessage = "Not used. To use this form, create a Form Container plugin in a slot on a content item.";
				$item['where_used'] = implode('; ', ze\miscAdm::getUsageText($usage, $usageLinks, null, $overrideNotUsedMessage));
			}
			
			//Show a seperate icon for different types of forms
			if ($item['type'] != 'standard') {
				$item['css_class'] = 'form_type_' . $item['type'];
			}
			
			if ($item['latest_response']) {
				$item['latest_response'] = ze\admin::formatDateTime($item['latest_response'], '_MEDIUM');
			}

			$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['save_record', 'save_data'], $id);
			
			if (!$form['save_record']) {
				$item['latest_response'] = ze\admin::phrase("Doesn't log responses");
			}

			switch ($form['save_data']) {
				case 0:
					$createsUserRecordPhrase = ze\admin::phrase("Doesn't create a user/contact record");
					break;
				case 1:
					$createsUserRecordPhrase = ze\admin::phrase("Always creates a user/contact record");
					break;
				case 2:
					$createsUserRecordPhrase = ze\admin::phrase("Creates a user/contact record on condition of consent field");
					break;
			}
			
			$item['type'] = zenario_user_forms::getFormTypeEnglish($item['type']);

			$item['crm'] = '';
			if (ze\module::inc('zenario_crm_form_integration')) {
				$formEnabledCRM = [];
				$formEnabledCRMFormattedNicely = [];

				$formCRMQuery = ze\row::query(ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_link', 'crm_id', ['enable' => true, 'form_id' => (int) $id]);
				while ($CRMId = ze\sql::fetchValue($formCRMQuery)) {
					$formEnabledCRM[] = $CRMId;
				}

				if ($formEnabledCRM) {
					if (in_array('salesforce', $formEnabledCRM)) {
						$formEnabledCRMFormattedNicely[] = ze\admin::phrase('Salesforce');
					}

					if (in_array('mailchimp', $formEnabledCRM)) {
						$formEnabledCRMFormattedNicely[] = ze\admin::phrase('MailChimp');
					}

					if (in_array('360lifecycle', $formEnabledCRM)) {
						$formEnabledCRMFormattedNicely[] = ze\admin::phrase('360Lifecycle');
					}

					if (in_array('generic', $formEnabledCRM)) {
						$formEnabledCRMFormattedNicely[] = ze\admin::phrase('Other CRM');
					}

					$item['crm'] = implode(', ', $formEnabledCRMFormattedNicely);
				}
			}

			if ($item['crm']) {
				$crmPhrase = ze\admin::phrase("Sends to a CRM");
			} else {
				$crmPhrase = ze\admin::phrase("Doesn't send to a CRM");
			}

			$item['user_record_and_crm_info'] = $createsUserRecordPhrase . ", " . $crmPhrase;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		ze\priv::exitIfNot('_PRIV_MANAGE_FORMS');
		if ($_POST['archive_form'] ?? false) {
			foreach(explode(',', $ids) as $id) {
				ze\row::update(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['status' => 'archived'], ['id' => $id]);
			}
		} elseif ($_POST['delete_form'] ?? false) {
			foreach (explode(',', $ids) as $formId) {
				$error = zenario_user_forms::deleteForm($formId);
				if (ze::isError($error)) {
					foreach ($error->errors as $message) {
						echo $message . "\n";
					}
				}
				
			}
		} elseif ($_POST['duplicate_form'] ?? false) {
			static::duplicateForm($ids);
		}
	}
	
	public static function duplicateForm($formId) {
		$form = ze\row::get(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $formId);
		
		//Add version number to form name
		$formNameArray = explode(' ', $form['name']);
		$formVersion = end($formNameArray);
		if (preg_match('/\((\d+)\)/', $formVersion, $matches)) {
			array_pop($formNameArray);
			$form['name'] = implode(' ', $formNameArray);
		}
		for ($i = 2; $i < 1000; $i++) {
			$name = $form['name'].' ('.$i.')';
			if (!ze\row::exists(ZENARIO_USER_FORMS_PREFIX . 'user_forms', ['name' => $name])) {
				$form['name'] = $name;
				break;
			}
		}
		
		//Use the import/export functions to easily duplicate a form
		$formsJSON = static::getFormsExportJSON($formId);
		$formsJSON['forms'][0]['form']['name'] = $name;
		
		zenario_user_forms::importForms(json_encode($formsJSON));
	}
	
	public static function getFormsExportJSON($formIds) {
		$formIds = ze\ray::explodeAndTrim($formIds);
		$formsJSON = [
			'major_version' => ZENARIO_MAJOR_VERSION,
			'minor_version' => ZENARIO_MINOR_VERSION,
			'forms' => []
		];
		foreach ($formIds as $formId) {
			$formJSON = zenario_user_forms::getFormJSON($formId);
			$formsJSON['forms'][] = $formJSON;
		}
		return $formsJSON;
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		ze\priv::exitIfNot('_PRIV_MANAGE_FORMS');
		if ($_POST['export_forms'] ?? false) {
			$formsJSON = json_encode(static::getFormsExportJSON($ids));
			
			$filename = tempnam(sys_get_temp_dir(), 'forms_export');
			file_put_contents($filename, $formsJSON);
			//Offer file as download
			header('Content-Type: application/json');
			header('Content-Disposition: attachment; filename="Zenario forms.json"');
			header('Content-Length: ' . filesize($filename));
			readfile($filename);
			//Remove file from temp directory
			@unlink($filename);
			exit;
		}
	}
	
	public static function getModuleClassNameByInstanceId($id) {
		$sql = '
			SELECT class_name
			FROM '.DB_PREFIX.'modules m
			INNER JOIN '.DB_PREFIX.'plugin_instances pi
				ON m.id = pi.module_id
			WHERE pi.id = '.(int)$id;
		$result = ze\sql::select($sql);
		$row = ze\sql::fetchRow($result);
		return $row[0];
	}
}