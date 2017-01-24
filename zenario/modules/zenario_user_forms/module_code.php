<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_user_forms extends module_base_class {
	
	public $data = array();
	
	public function init() {
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = true, $clearByFile = false, $clearByModuleData = true);
		
		$this->data['showForm'] = true;
		$formProperties = getRow('user_forms', 
			array('name', 'title', 'title_tag', 'success_message', 'show_success_message', 'use_captcha', 'captcha_type', 'extranet_users_use_captcha', 'send_email_to_admin', 'admin_email_use_template', 'translate_text', 'submit_button_text', 'default_previous_button_text'), 
			$this->setting('user_form')
		);
		
		$translate = $formProperties['translate_text'];
		if (!empty($formProperties['title'])) {
			$this->data['title'] = static::formPhrase($formProperties['title'], array(), $translate);
			$this->data['title_tag'] = $formProperties['title_tag'];
		}
		
		$this->data['submit_button_text'] = static::formPhrase($formProperties['submit_button_text'], array(), $translate);
		$this->data['identifier'] = $this->getFormIdentifier();
		$pageBreakFields = getRowsArray('user_form_fields', 'id', array('field_type' => 'page_break', 'user_form_id' => $this->setting('user_form')), array('ord'));
		if ($pageBreakFields) {
			$this->data['multiPageFormFinalBackButton'] = '<input type="button" name="previous" value="'.static::formPhrase($formProperties['default_previous_button_text'], array(), $translate).'" class="previous"/>';
			$this->data['multiPageFormFinalPageEnd'] = '</fieldset>';
			$this->callScript('zenario_user_forms', 'initMultiPageForm', $this->pluginAJAXLink('validateMultiPageForm=1'), $this->containerId, $this->data['identifier'], ($this->setting('display_mode') == 'in_modal_window'), count($pageBreakFields) + 1);
		}
		
		// Add a captcha to the form
		if ($formProperties['use_captcha'] && ($formProperties['captcha_type'] == 'math')) {
			require_once CMS_ROOT. 'zenario/libraries/mit/securimage/securimage.php';
		}
		if (!post('submit_form')){
			unset($_SESSION['captcha_passed__'. $this->instanceId]);
		}
		
		// Handle form submission
		$formSubmitted = false;
		if (post('submit_form') && ($this->instanceId == post('instanceId'))) {
			
			// Get form errors
			$this->data['errors'] = static::validateUserForm($this->setting('user_form'), $_POST);
			
			// Check captcha if used
			if (!$pageBreakFields && $formProperties['use_captcha'] && empty($_SESSION['captcha_passed__'.$this->instanceId])) {
				if (!userId() || $formProperties['extranet_users_use_captcha']) {
					
					$this->data['captcha_error'] = $this->getCaptchaError($formProperties['captcha_type'], $translate);
				}
			}
			
			// Save data if no errors
			if (empty($this->data['errors']) && empty($this->data['captcha_error'])) {
				unset($_SESSION['captcha_passed__'. $this->instanceId]);
				
				$formSubmitted = true;
				
				$redirectURL = false;
				$userId = static::saveUserForm($this->setting('user_form'), $_POST, $redirectURL, userId());
				if ($redirectURL) {
					$this->headerRedirect($redirectURL);
				} elseif ($this->data['showSuccessMessage'] = $formProperties['show_success_message']) {
					$this->data['successMessage'] = static::formPhrase($formProperties['success_message'], array(), $translate);
				} else {
					if (isset($_SESSION['destURL'])) {
						$link = $_SESSION['destURL'];
						$this->headerRedirect($link);
					}
				}
			}
			
			// $_POST for keeping data after submission error, userId() for preloading user data
			$this->data['formFields'] = static::drawUserForm($this->setting('user_form'), $_POST, false, $this->data['errors'], $this->setting('checkbox_columns'), $this->containerId);
		
		// Handle any centralised lists that are being filtered
		} elseif ((post('filter_list') && ($this->instanceId == post('instanceId'))) || post('preload_from_post')) {
			$this->data['formFields'] = static::drawUserForm($this->setting('user_form'), $_POST, false, array(), $this->setting('checkbox_columns'), $this->containerId);
		
		// Otherwise just draw form
		} else {
			$this->data['formFields'] = static::drawUserForm($this->setting('user_form'), userId(), false, array(), $this->setting('checkbox_columns'), $this->containerId);
		}
		
		$this->callScript('zenario_user_forms', 'initJQueryElements', $this->containerId, $formSubmitted);
		
		if (!$pageBreakFields && $formProperties['use_captcha'] && empty($_SESSION['captcha_passed__'.$this->instanceId])) {
			if (!userId() || $formProperties['extranet_users_use_captcha']) {
				$this->data['captcha'] = true;
				if ($formProperties['captcha_type'] == 'word') {
					$this->data['captcha_html'] = $this->captcha();
				} elseif ($formProperties['captcha_type'] == 'math') {
					$this->data['captcha_html'] = '
						<p>
							<img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="zenario/libraries/mit/securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left">
							
							&nbsp;
							<a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById(\'siimage\').src = \'zenario/libraries/mit/securimage/securimage_show.php?sid=\' + Math.random(); this.blur(); return false">
								<img src="zenario/libraries/mit/securimage/images/refresh.png" alt="Reload Image" onclick="this.blur()" align="bottom" border="0">
							</a><br />
							Do the maths:<br />
							<input type="text" name="captcha_code" size="12" maxlength="16" class="math_captcha_input" id="'.$this->containerId.'_math_captcha_input"/>
						</p>';
				}elseif($formProperties['captcha_type'] == 'pictures'){
					 if(setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')){
						$this->callScript('zenario_user_forms', 'recaptcha');
						$this->data['captcha_html'] = '<div id="zenario_user_forms_google_recaptcha_section"></div>';
					}else{
						$this->data['captcha'] = false;
					}
				}
			}
		}

		$enctype = $this->getFormEncType($this->setting('user_form'));
		$this->data['openForm'] = $this->openForm('', $enctype.'id="'.$this->data['identifier'].'__form"');
		$this->data['closeForm'] = $this->closeForm();
		
		$formFields = static::getUserFormFields($this->setting('user_form'));
		foreach ($formFields as $fieldId => $field) {
			
			// Set form field initial visibility
			if (($field['visibility'] == 'visible_on_condition') && $field['visible_condition_field_id'] && isset($formFields[$field['visible_condition_field_id']])) {
				$visibleConditionField = $formFields[$field['visible_condition_field_id']];
				$visibleConditionFieldType = static::getFieldType($visibleConditionField);
				$this->callScript('zenario_user_forms', 'toggleFieldVisibility', $this->containerId, $fieldId, $field['visible_condition_field_id'], $field['visible_condition_field_value'], $visibleConditionFieldType);
			}
			
			// Init restatement field listeners
			if ($field['field_type'] == 'restatement' && $field['restatement_field'] && isset($formFields[$field['restatement_field']])) {
				$type = static::getFieldType($formFields[$field['restatement_field']]);
				$this->callScript('zenario_user_forms', 'initRestatementField', $this->data['identifier'], $fieldId, $field['restatement_field'], $type, $translate);
			}
			
			// Init calculate field listeners
			if ($field['field_type'] == 'calculated' && $field['numeric_field_1'] && $field['numeric_field_2'] && $field['calculation_type']) {
				// Get any fields that mirror this calculated field
				$mirrorFields = getRowsArray('user_form_fields', 'restatement_field', 
					array('user_form_id' => $this->setting('user_form'), 'field_type' => 'restatement', 'restatement_field' => $fieldId));
				
				$this->callScript('zenario_user_forms', 'initCalculateField', $this->containerId, $fieldId, $field['numeric_field_1'], $field['numeric_field_2'], $field['calculation_type'], $mirrorFields);
			}
		}
		
		// Handle modal window form
		if ($this->setting('display_mode') == 'in_modal_window') {
			if (!empty($_REQUEST['show_user_form']) || (post('containerId') == $this->containerId)) {
				$this->showInFloatingBox();
			} else {
				$this->data['displayText'] = static::formPhrase($this->setting('display_text'), array(), $translate);
				$this->data['showForm'] = false;
				$requests = 'show_user_form=1';
				if (!empty(cms_core::$importantGetRequests)
				 && is_array(cms_core::$importantGetRequests)) {
					foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
						if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
							$requests .= '&'. urlencode($getRequest). '='. urlencode($_GET[$getRequest]);
						}
					}
				}
				$this->data['showFormJS'] = $this->refreshPluginSlotAnchor($requests, false, false);
			}
		}
		
		$this->callScript('zenario_user_forms', 'initFilePickerFields', $this->containerId, $this->pluginAJAXLink('filePickerUpload=1'));
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function addToPageHead() {
		if ($this->setting('user_form')){
			$formProperties = getRow('user_forms', array('captcha_type'), $this->setting('user_form'));
			if(isset($formProperties['captcha_type'])){
				if($formProperties['captcha_type'] == 'pictures' && setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')){
					$siteKey = setting('google_recaptcha_site_key');
					$theme = setting('google_recaptcha_widget_theme');
					echo "<script type='text/javascript'>
							var onloadCallback = function() {
								if (document.getElementById('zenario_user_forms_google_recaptcha_section')) {
									grecaptcha.render('zenario_user_forms_google_recaptcha_section', {
										'sitekey' : '".$siteKey."',
										'theme' : '".$theme."'
									});
								}
								
						};
					</script>";
					
					if ($this->setting('display_mode') == 'in_modal_window') {
						echo '<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback"></script>';
					}else{
						echo '<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>';
					}
					
					
				}
			}
		}
	}
	
	public function handlePluginAJAX() {
		if (request('validateMultiPageForm')) {
			// Validate pages of multi-stage forms
			$errors = static::validateUserForm($this->setting('user_form'), $_POST, (int)post('_pageNo'));
			$formDetails = getRow('user_forms', array('use_captcha', 'captcha_type', 'translate_text'), $this->setting('user_form'));
			// For now, ignore captcha on multipage forms
			/*
			if ((post('_pageNo') == post('_pageCount')) && $formDetails['use_captcha'] && ($error = $this->getCaptchaError($formDetails['captcha_type'], $formDetails['translate_text'])) && empty($_SESSION['captcha_passed__'.$this->instanceId])) {
				$errors['captcha'] = $error;
			}
			*/
			echo json_encode($errors);
		} elseif (request('filePickerUpload')) {
			$data = array('files' => array());
			// Upload the file
			foreach ($_FILES as $fieldName => $file) {
				if (!empty($file['tmp_name']) && is_uploaded_file($_FILES[$fieldName]['tmp_name']) && cleanDownloads()) {
					$randomDir = createRandomDir(30, 'uploads');
					$newName = $randomDir. preg_replace('/\.\./', '.', preg_replace('/[^\w\.-]/', '', $_FILES[$fieldName]['name'])).'.upload';
					if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], CMS_ROOT. $newName)) {
						$data['files'][] = array('name' => urldecode($_FILES[$fieldName]['name']), 'id' => $newName);
						chmod(CMS_ROOT. $newName, 0666);
					}
				}
			}
			echo json_encode($data);
		}
	}
	
	
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case 'zenario__content/panels/content':
				// Get plugins using this form
				$moduleIds = static::getFormModuleIds();
				
				$sqlJoin = '
					INNER JOIN '.DB_NAME_PREFIX.'plugin_item_link pil
						ON (c.id = pil.content_id) AND (c.type = pil.content_type) AND (c.admin_version = pil.content_version)
					INNER JOIN '.DB_NAME_PREFIX.'plugin_settings ps 
						ON (pil.instance_id = ps.instance_id) AND (ps.name = \'user_form\')';
				$sqlWhere = '
					pil.module_id IN ('. inEscape($moduleIds, 'numeric'). ')
						AND ps.value = '.(int)$refinerId;
						
				$panel['refiners']['form_id']['table_join'] = $sqlJoin;
				$panel['refiners']['form_id']['sql'] = $sqlWhere;
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__forms':
				if ($refinerName == 'archived') {
					$panel['db_items']['where_statement'] = 'WHERE TRUE';
					$panel['no_items_message'] = adminPhrase('No forms have been archived.');
				}
				if (!inc('zenario_extranet_registration')) {
					$panel['db_items']['where_statement'] .= '
						AND f.type != "registration"';
					$panel['collection_buttons']['create_registration_form']['hidden'] = true;
				}
				if (!inc('zenario_extranet_profile_edit')) {
					$panel['db_items']['where_statement'] .= '
						AND f.type != "profile"';
					$panel['collection_buttons']['create_profile_form']['hidden'] = true;
				}
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				$record = getRow('user_forms', array('name'), array('id' => $refinerId));
				$panel['title'] = 'Form fields for "' . $record['name'] . '"';
				break;
			case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
				$sql = '
					SELECT id, name
					FROM '.DB_NAME_PREFIX.'user_form_fields
					WHERE user_form_id = '.(int)$refinerId.'
					AND (field_type NOT IN (\'page_break\', \'section_description\', \'restatement\') OR field_type IS NULL)
					ORDER BY ord';
				
				$result = sqlSelect($sql);
				while ($formField = sqlFetchAssoc($result)) {
					$panel['columns']['form_field_'.$formField['id']] = array(
						'title' => $formField['name'],
						'show_by_default' => true,
						'searchable' => true,
						'sortable' => true
					);
				}
				break;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->fillOrganizerPanel($path, $panel, $refinerName, $refinerId, $mode);
		} else {
			switch ($path) {
				case 'zenario__user_forms/panels/zenario_user_forms__forms':
					
					if ($refinerName == 'email_address_setting') {
						unset($panel['collection_buttons']);
						$panel['title'] = adminPhrase('Summary of email addresses used by forms');
						$panel['no_items_message'] = adminPhrase('No forms send emails to a specific address.');
					} else {
						unset($panel['columns']['form_email_addresses']);
					}
					
					// Get plugins using a form
					$moduleIds = static::getFormModuleIds();
					$formPlugins = array();
					$sql = '
						SELECT id, name
						FROM '.DB_NAME_PREFIX.'plugin_instances
						WHERE module_id IN ('. inEscape($moduleIds, 'numeric'). ')
						ORDER BY name';
					
					$result = sqlSelect($sql);
					while ($row = sqlFetchAssoc($result)) {
						$formPlugins[$row['id']] = $row['name'];
					}
					
					// Get content items with a plugin using a form on
					$formUsage = array();
					$contentItemUsage = array();
					$sql = '
						SELECT pil.content_id, pil.content_type, pil.instance_id
						FROM '.DB_NAME_PREFIX.'plugin_item_link pil
						INNER JOIN '.DB_NAME_PREFIX.'content_items c
							ON (pil.content_id = c.id) AND (pil.content_type = c.type) AND (pil.content_version = c.admin_version)
						WHERE c.status NOT IN (\'trashed\',\'deleted\')
						AND pil.module_id IN ('. inEscape($moduleIds, 'numeric'). ')
						GROUP BY pil.content_id, pil.content_type';
					
					$result = sqlSelect($sql);
					while ($row = sqlFetchAssoc($result)) {
						$tagId = formatTag($row['content_id'], $row['content_type']);
						$contentItemUsage[$row['instance_id']][] = $tagId;
					}
					
					foreach($formPlugins as $instanceId => $pluginName) {
						$className = static::getModuleClassNameByInstanceId($instanceId);
						$moduleName = getModuleDisplayNameByClassName($className);
						
						if ($formId = getRow('plugin_settings', 'value', array('instance_id' => $instanceId, 'name' => 'user_form'))) {
							$details = array('pluginName' => $pluginName, 'moduleName' => $moduleName);
							if (isset($contentItemUsage[$instanceId])) {
								$details['contentItems'] = $contentItemUsage[$instanceId];
							}
							$formUsage[$formId][] = $details;
						}
					}
					
					foreach($panel['items'] as $id => &$item) {
						$pluginUsage = '';
						$contentUsage = '';
						$moduleNames = array();
						if (isset($formUsage[$id]) && !empty($formUsage[$id])) {
							$pluginUsage = '"'.$formUsage[$id][0]['pluginName'].'"';
							if (($count = count($formUsage[$id])) > 1) {
								$plural = (($count - 1) == 1) ? '' : 's';
								$pluginUsage .= ' and '.($count - 1).' other plugin'.$plural;
							}
							$count = 0;
							foreach($formUsage[$id] as $plugin) {
								$moduleNames[$plugin['moduleName']] = $plugin['moduleName'];
								if (isset($plugin['contentItems'])) {
									if (empty($contentUsage)) {
										$contentUsage = '"'.$plugin['contentItems'][0].'"';
									}
									$count += count($plugin['contentItems']);
								}
							}
							if ($count > 1) {
								$plural = (($count - 1) == 1) ? '' : 's';
								$contentUsage .= ' and '.($count - 1).' other item'.$plural;
								
							}
						}
						$item['plugin_module_name'] = implode(', ', $moduleNames);
						$item['plugin_usage'] = $pluginUsage;
						$item['plugin_content_items'] = $contentUsage;
						
						if ($item['type'] != 'standard') {
							$item['css_class'] = 'form_type_' . $item['type'];
						}
					}
					break;
				case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
					
					foreach ($panel['items'] as $id => &$item){
						
						$item['css_class'] = 'zenario_char_'. $item['field_type'];
						
						
						if (in_array($item['field_type'], array('checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
							if ($item['user_field_id']) {
								$field_values = getDatasetFieldLOV($item['user_field_id']);
							} else {
								$field_values = static::getUnlinkedFieldLOV($item['id']);
							}
							$item['values_list'] = implode(', ', $field_values);
						} else {
							$item['values_list'] = 'n/a';
						}
					}
					
					$formStatus = getRow('user_forms', 'status', array('id' => $refinerId));
					if ($formStatus == 'archived') {
						unset($panel['collection_buttons']);
						unset($panel['item_buttons']['delete']);
					} else {
						$panel['collection_buttons']['add_field']['admin_box']['key']['form_id'] = 
						$panel['collection_buttons']['add_field_user_characteristic']['admin_box']['key']['form_id'] = 
						$panel['collection_buttons']['add_section_description']['admin_box']['key']['form_id'] = 
							$refinerId;
					}
					$panel['item_buttons']['edit']['admin_box']['key']['form_id'] = $refinerId;
					break;
				case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
					
					// Set panel title
					$formDetails = getRow('user_forms', array('name'), $refinerId);
					$panel['title'] = adminPhrase('Responses for form "[[name]]"', $formDetails);
					
					
					if (!setting('zenario_user_forms_set_profanity_filter')) {
						unset($panel['columns']['blocked_by_profanity_filter']);
						unset($panel['columns']['profanity_filter_score']);
						unset($panel['columns']['profanity_tolerance_limit']);
					} else {
						foreach($panel['items'] as $id => &$item) {
							$profanityValues = getRow(ZENARIO_USER_FORMS_PREFIX. 'user_response',
								array('blocked_by_profanity_filter', 'profanity_filter_score', 'profanity_tolerance_limit'),
								array('id' => $id));
							$profanityValueForPanel = ($profanityValues['blocked_by_profanity_filter'] == 1 ? "Yes" : "No");
							$item['blocked_by_profanity_filter'] = $profanityValueForPanel;
							$item['profanity_filter_score'] = $profanityValues['profanity_filter_score'];
							$item['profanity_tolerance_limit'] = $profanityValues['profanity_tolerance_limit'];
						}
					}
					
					if (!static::isFormCRMEnabled($refinerId)) {
						unset($panel['columns']['crm_response']);
					}
					
					$panel['item_buttons']['view_response']['admin_box']['key']['form_id'] = 
					$panel['collection_buttons']['export']['admin_box']['key']['form_id'] = 
						$refinerId;
					
					$sql = '
						SELECT urd.value, urd.form_field_id, ur.id
						FROM '. DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
						INNER JOIN '. DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
							ON urd.user_response_id = ur.id
						WHERE ur.form_id = '. (int)$refinerId;
					$result = sqlSelect($sql);
					while ($row = sqlFetchAssoc($result)) {
						if (isset($panel['items'][$row['id']])) {
							$panel['items'][$row['id']]['form_field_'.$row['form_field_id']] = $row['value'];
						}
					}
					
					break;
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($c = $this->runSubClass(__FILE__)) {
			return $c->handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId);
		} else {
			switch ($path) {
				case 'zenario__user_forms/panels/zenario_user_forms__forms':
					if (post('archive_form')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						foreach(explode(',', $ids) as $id) {
							updateRow('user_forms', array('status' => 'archived'), array('id' => $id));
						}
					}
					if (post('delete_form')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						foreach (explode(',', $ids) as $formId) {
							
							$error = static::deleteForm($formId);
							if (isError($error)) {
								foreach ($error->errors as $message) {
									echo $message . "\n";
								}
							}
							
						}
					}
					
					if (post('duplicate_form')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						$formProperties = getRow('user_forms', true, $ids);
						$formFields = getRowsArray('user_form_fields', true, array('user_form_id' => $ids));
						$formNameArray = explode(' ', $formProperties['name']);
						$formVersion = end($formNameArray);
						// Remove version number at end of field
						if (preg_match('/\((\d+)\)/', $formVersion, $matches)) {
							array_pop($formNameArray);
							$formProperties['name'] = implode(' ', $formNameArray);
						}
						for ($i = 2; $i < 1000; $i++) {
							$name = $formProperties['name'].' ('.$i.')';
							if (!checkRowExists('user_forms', array('name' => $name))) {
								$formProperties['name'] = $name;
								break;
							}
						}
						
						unset($formProperties['id']);
						$id = insertRow('user_forms', $formProperties);
						foreach ($formFields as $formField) {
							$formFieldValues = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', true, array('form_field_id' => $formField['id']));
							unset($formField['id']);
							$formField['user_form_id'] = $id;
							$fieldId = insertRow('user_form_fields', $formField);
							// Duplicate form field values if any
							foreach ($formFieldValues as $field) {
								$field['form_field_id'] = $fieldId;
								unset($field['id']);
								insertRow(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $field);
							}
						}
					}
					break;
				case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
					$formId = (int)post('refiner__user_form_id');
					if (post('reorder')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						// Update ordinals
						$ids = explode(',', $ids);
						foreach ($ids as $id) {
							if (!empty($_POST['ordinals'][$id])) {
								$sql = "
									UPDATE ". DB_NAME_PREFIX. "user_form_fields SET
										ord = ". (int) $_POST['ordinals'][$id]. "
									WHERE id = ". (int) $id . 
									" AND user_form_id=" . (int)$formId;
								sqlUpdate($sql);
							}
						}
						// Update div wrapper class
						if ($droppedItemId = post('dropped_item')) {
							if (!empty($_POST['ordinals'][$droppedItemId])) {
								$ord = $_POST['ordinals'][$droppedItemId];
								$field = getRow('user_form_fields', array('div_wrap_class', 'field_type'), $droppedItemId);
								$fieldBelow = getRow('user_form_fields', array('div_wrap_class'), array('user_form_id' => $formId, 'ord' => $ord + 1));
								if ($field['field_type'] != 'page_break') {
									if ($fieldBelow && ($fieldBelow['div_wrap_class'] === $field['div_wrap_class'])) {
										// Keep current class
									} elseif ($fieldAbove = getRow('user_form_fields', array('div_wrap_class'), array('user_form_id' => $formId, 'ord' => $ord - 1))) {
										updateRow('user_form_fields', array('div_wrap_class' => $fieldAbove['div_wrap_class']), $droppedItemId);
									}
								}
							}
						}
						return;
					} elseif (post('delete')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						$ids = explode(',', $ids);
						foreach ($ids as $fieldId) {
							$error = static::deleteFormField($fieldId, false);
							if (isError($error)) {
								foreach ($error->errors as $message) {
									echo $message . "\n";
								}
							}
						}
						
						// Update remaining field ordinals
						$formFieldIds = getRowsArray('user_form_fields', 'id', array('user_form_id' => $refinerId), 'ord');
						$ord = 0;
						foreach($formFieldIds as $fieldId) {
							$ord++;
							updateRow('user_form_fields', array('ord' => $ord), $fieldId);
						}
						
					} elseif (post('add_page_break')) {
						exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
						
						$record = array();
						$record['ord'] = static::getMaxOrdinalOfFormFields($formId) + 1;
						$record['name'] = 'Page break '.(static::getPageBreakCount($formId) + 1);
						$record['field_type'] = 'page_break';
						$record['user_form_id'] = $formId;
						$record['next_button_text'] = 'Next';
						$record['previous_button_text'] = 'Back';
						insertRow('user_form_fields', $record);
					}
					break;
			
				case 'zenario__user_forms/panels/zenario_user_forms__user_responses':
					exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
					
					$form_id = $refinerId;
					
					// Delete all responses for a form
					if (post('delete_form_responses') && $form_id) {
						$result = getRows(
							ZENARIO_USER_FORMS_PREFIX . 'user_response', 
							array('id'), 
							array('form_id' => $form_id)
						);
						while ($row = sqlFetchAssoc($result)) {
							// Delete response field data
							deleteRow(
								ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
								array('user_response_id' => $row['id'])
							);
							
							// Delete response record
							deleteRow(
								ZENARIO_USER_FORMS_PREFIX . 'user_response', 
								array('id' => $row['id'])
							);
						}
					}
					break;
			}
		}
	}

	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch($path) {
			case 'site_settings':
				$profanityCsvFilePath = CMS_ROOT . 'zenario/libraries/not_to_redistribute/profanity-filter/profanities.csv';
				if(!file_exists($profanityCsvFilePath)) {
					$sql = "UPDATE ". DB_NAME_PREFIX. "site_settings SET value = '' WHERE 
							name = 'zenario_user_forms_set_profanity_filter' OR name = 'zenario_user_forms_set_profanity_tolerence'";
					sqlQuery($sql);
					
					$values['zenario_user_forms_set_profanity_tolerence'] = "";
					$values['zenario_user_forms_set_profanity_filter'] = "";
					
					$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['disabled'] = true;
					$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_tolerence']['disabled'] = true;
					$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['side_note'] = "";
					$box['tabs']['zenario_user_forms_profanity_filter']['fields']['zenario_user_forms_set_profanity_filter']['note_below'] 
						= 'You must have a list of profanities on the server to enable this feature. The file must be called "profanities.csv" 
						and must be in the directory "zenario/libraries/not_to_redistribute/profanity-filter/".';
				}
				break;
			case 'zenario_user_form_response':
				$box['title'] = adminPhrase('Form response [[id]]', array('id' => $box['key']['id']));
				$responseDetails = getRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('response_datetime', 'crm_response'), $box['key']['id']);
				$values['response_datetime'] = formatDateTimeNicely($responseDetails['response_datetime'], 'vis_date_format_med');
				
				$crmEnabled = false;
				if (static::isFormCRMEnabled($box['key']['form_id'])) {
					$values['crm_response'] = $responseDetails['crm_response'];
				} else {
					unset($box['tabs']['form_fields']['fields']['crm_response']);
				}
				
				$formFields = static::getUserFormFields(request('refiner__form_id'));
				
				$userResponse = array();
				$result = getRows(ZENARIO_USER_FORMS_PREFIX. 'user_response_data',
					array('form_field_id', 'value', 'internal_value'),
					array('user_response_id' => $box['key']['id']));
				while ($row = sqlFetchAssoc($result)) {
					$userResponse[$row['form_field_id']] = array('value' => $row['value'], 'internal_value' => $row['internal_value']);
				}
				
				foreach ($formFields as $fieldId => $formField) {
					
					$type = static::getFieldType($formField);
					
					if ($type == 'page_break' || $type == 'section_description') {
						continue;
					}
					$field = array(
						'class_name' => 'zenario_user_forms',
						'label' => $formField['name'],
						'ord' => $formField['ord'] + 10);
					if ($type == 'attachment' || $type == 'file_picker') {
						$responseValue = isset($userResponse[$fieldId]['internal_value']) ? $userResponse[$fieldId]['internal_value'] : '';
						
						if ($responseValue && ($file = getRow('files', array('mime_type'), $responseValue)) && isImage($file['mime_type'])) {
							$link = 'zenario/file.php?adminDownload=1&download=1&id=' . $responseValue;
							$field['post_field_html'] = '<a href="' . $link . '">' . adminPhrase('Download') . '</a>';
						}
						
						$field['upload'] = array();
						$field['download'] = true;
					} else {
						$responseValue = isset($userResponse[$fieldId]['value']) ? $userResponse[$fieldId]['value'] : '';
						if ($type == 'textarea') {
							$field['type'] = 'textarea';
							$field['rows'] = 5;
						} else {
							$field['type'] = 'text';
						}
					}
					$field['value'] = $responseValue;
					$box['tabs']['form_fields']['fields']['form_field_' . $fieldId] = $field;
				}
				break;
			case 'zenario_user_dataset_field_picker':
				$box['key']['refinerId'] = get('refinerId');
				$box['tabs']['dataset_fields']['fields']['dataset_fields']['values'] =
					listCustomFields('users', $flat = false, $filter = false, $customOnly = false, $useOptGroups = true);
				break;
			case 'zenario_user_admin_box_form':
				
				$fields['captcha/captcha_type']['values'] = array('word' => 'Words', 'math' => 'Maths');
				
				if (setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')) {
					$fields['captcha/captcha_type']['values']['pictures'] = 'Pictures';
				} else {
					$link = absCMSDirURL()."zenario/admin/organizer.php?#zenario__administration/panels/site_settings//captcha";
					$fields['captcha/captcha_type']['note_below'] = 'To enable pictures captcha (most friendly for the user)  please enter the <a href="' . $link. '" target="_blank">api key details</a>';
				}
				
				//Hide profanity settings checkbox if site setting is not checked
				$profanityFilterSetting = setting('zenario_user_forms_set_profanity_filter');
				
				if(!$profanityFilterSetting) {
					$fields['details/profanity_filter_text_fields']['hidden'] = true;
				}
				
				$values['profanity_filter_text_fields'] = getRow('user_forms', 'profanity_filter_text', array('id' => $box['key']['id']));
			
				if (!inc('zenario_extranet')) {
					$fields['data/save_data']['hidden'] = 
					$fields['data/email_html']['hidden'] = 
					$fields['data/user_status']['hidden'] = 
					$fields['data/log_user_in']['hidden'] = 
					$fields['data/log_user_in_cookie']['hidden'] = 
					$fields['data/add_user_to_group']['hidden'] = 
					$fields['data/duplicate_submission_html']['hidden'] = 
					$fields['data/user_duplicate_email_action']['hidden'] = 
					$fields['data/duplicate_email_address_error_message']['hidden'] = 
					$fields['data/create_another_form_submission_record']['hidden'] = 
					$fields['data/line_br_2']['hidden'] = true;
				}
				$fields['data/add_user_to_group']['values'] = 
					listCustomFields('users', $flat = false, 'groups_only', $customOnly = true, $useOptGroups = true);
				
				if (get('refinerName') == 'archived') {
					foreach($box['tabs'] as &$tab) {
						$tab['edit_mode']['enabled'] = false;
					}
				}
				
				// Get default language english name
				$defaultLanguageName = false;
				$languages = getLanguages(false, true, true);
				foreach($languages as $language) {
					$defaultLanguageName = $language['english_name'];
					break;
				}
				if ($defaultLanguageName) {
					$fields['details/translate_text']['side_note'] = adminPhrase(
						'This will cause all displayable text from this form to be translated when used in a Forms plugin. This should be disabled if you enter non-[[default_language]] text into the form field admin boxes.', array('default_language' => $defaultLanguageName));
				}
				
				$formTextFieldLabels = array();
				$formTextFieldLabels[''] = array('label' => '-- Select --');
				
				if ($id = $box['key']['id']) {
					
					// Fill form fields
					$record = getRow('user_forms', true, $id);
					$this->fillFieldValues($fields, $record);
					
					$box['key']['type'] = $record['type'];
					
					if ($record['title'] !== null && $record['title'] !== '') {
						$values['details/show_title'] = true;
					}
					
					$values['data/admin_email_options'] = ($record['admin_email_use_template'] ? 'use_template' : 'send_data');
					$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $record['name']));
					
					if (!empty($record['redirect_after_submission'])) {
						$values['details/success_message_type'] = 'redirect_after_submission';
					} elseif (!empty($record['show_success_message'])) {
						$values['details/success_message_type'] = 'show_success_message';
					} else {
						$values['details/success_message_type'] = 'none';
					}
					
					// Find all text form fields from the selected form
					$formTextFields = $this->getTextFormFields($box['key']['id']);
					foreach ($formTextFields as $formTextField) {
						$formTextFieldLabels[$formTextField['db_column']] = array('label' => $formTextField['label']);
					}
					
					// Populate translations tab
					$translatableLanguage = false;
					foreach ($languages as $language) {
						if ($language['translate_phrases']) {
							$translatableLanguage = true;
						}
					}
					if ($translatableLanguage) {
						// Get translatable fields for this field type
						$fieldsToTranslate = array(
							'title' => $record['title'],
							'success_message' => $record['success_message'],
							'submit_button_text' => $record['submit_button_text'],
							'default_next_button_text' => $record['default_next_button_text'],
							'default_previous_button_text' => $record['default_previous_button_text'],
							'duplicate_email_address_error_message' => $record['duplicate_email_address_error_message']);
						
						// Get any existing phrases that translatable fields have
						$existingPhrases = array();
						foreach($fieldsToTranslate as $name => $value) {
							$phrases = getRows('visitor_phrases', 
								array('local_text', 'language_id'), 
								array('code' => $value, 'module_class_name' => 'zenario_user_forms'));
							while ($row = sqlFetchAssoc($phrases)) {
								$existingPhrases[$name][$row['language_id']] = $row['local_text'];
							}
						}
						$keys = array_keys($fieldsToTranslate);
						$lastKey = end($keys);
						$ord = 0;
						
						foreach($fieldsToTranslate as $name => $value) {
							
							// Create label for field with english translation (if set)
							$label = $fields[$name]['label'];
							$html = '<b>'.$label.'</b>';
							$readOnly = true;
							$sideNote = false;
							if (!empty($value)) {
								$html .= ' "'. $value .'"';
								$readOnly = false;
								$sideNote = adminPhrase('Text must be defined in the site\'s default language in order for you to define a translation');
							} else {
								$html .= ' (No text is defined in the default language)';
							}
							$box['tabs']['translations']['fields'][$name] = array(
								'class_name' => 'zenario_user_forms',
								'ord' => $ord,
								'snippet' => array(
									'html' =>  $html));
							
							// Create an input box for each translatable language and look for existing phrases
							foreach($languages as $language) {
								if ($language['translate_phrases']) {
									$value = '';
									if (isset($existingPhrases[$name]) && isset($existingPhrases[$name][$language['id']])) {
										$value = $existingPhrases[$name][$language['id']];
									}
									$box['tabs']['translations']['fields'][$name.'__'.$language['id']] = array(
										'class_name' => 'zenario_user_forms',
										'ord' => $ord++,
										'label' => $language['english_name']. ':',
										'type' => 'text',
										'value' => $value,
										'read_only' => $readOnly,
										'side_note' => $sideNote);
								}
							}
							
							// Add linebreak after each field
							if ($name != $lastKey) {
								$box['tabs']['translations']['fields'][$name.'_break'] = array(
									'class_name' => 'zenario_user_forms',
									'ord' => $ord,
									'snippet' => array(
										'html' => '<hr/>'));
							}
							$ord++;
							$box['tabs']['translations']['hidden'] = $record['translate_text'];
						}
					} else {
						unset($box['tabs']['translations']);
					}
				} else {
					unset($box['tabs']['translations']);
					$box['title'] = adminPhrase('Creating a Form');
					if (!$box['key']['type']) {
						$values['data/save_data'] = 
						$values['data/save_record'] = true;
						$values['details/submit_button_text'] = 'Submit';
						$values['details/default_next_button_text'] = 'Next';
						$values['details/default_previous_button_text'] = 'Back';
						$values['data/duplicate_email_address_error_message'] = 'Sorry this form has already been completed with this email address';
					} elseif ($box['key']['type'] == 'profile') {
						//TODO
					} elseif ($box['key']['type'] == 'registration') {
						$values['details/show_title'] = true;
						$values['details/title'] = 'Registration form';
						$values['details/submit_button_text'] = 'Register';
					}
				}
				// Set text field select lists (will just be -- Select -- if creating new form)
				$fields['data/reply_to_email_field']['values'] =
				$fields['data/reply_to_first_name']['values'] =
				$fields['data/reply_to_last_name']['values'] =
					$formTextFieldLabels;
				break;
			
			case 'zenario_email_template':
				$forms = getRowsArray('user_forms', 'name', array('status' => 'active'), 'name');
				$fields['body/user_form']['values'] = $forms;
				break;
				
			case 'zenario_user_forms__export_user_responses':
				
				// Fill date ranges with recent dates
				$values['details/date_from'] =  date('Y-m-01');
				$values['details/date_to'] = date('Y-m-d');
				break;
			
			case 'zenario_delete_form_field':
				$fieldId = $box['key']['id'];
				if ($fieldId) {
					$box['title'] = adminPhrase('Deleting "[[field_name]]"', $box['key']);
					if ($box['key']['field_type'] == 'page_break' || $box['key']['field_type'] == 'section_description') {
						$fields['details/warning_message']['snippet']['html'] = 
							'<p>' . adminPhrase('Are you sure you want to delete this [[field_name]]?', array('field_name' => strtolower($box['key']['field_english_type']))) . '</p>';
					} elseif ($box['key']['field_type'] == 'restatement') {
						$fields['details/warning_message']['snippet']['html'] = 
							'<p>' . adminPhrase('Are you sure you want to delete this mirror field?') . '</p>';
					} else {
						$responseCount = (int)selectCount(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $fieldId));
						
						// If no responses delete field normally
						if ($responseCount <= 0) {
							$fields['details/warning_message']['snippet']['html'] = 
								'<p>' . adminPhrase('There are no user responses for this field. Delete this form field?') . '</p>';
						} else {
							
							$box['max_height'] = 260;
							$fields['details/delete_field_options']['hidden'] = false;
							
							$responsesTransferFields = json_decode($box['key']['responses_transfer_fields'], true);
							$responsesTransferFieldsCount = count($responsesTransferFields);
							
							// If no compatible fields disable migration and show message but otherwise delete normally
							if ($responsesTransferFieldsCount <= 0) {
								
								$fields['details/warning_message']['snippet']['html'] = 
									'<p>' . 
									nAdminPhrase(
										'This field has [[count]] response recorded against it, but there are no fields of the same type on the form. If you want to migrate this fields data to another field then create a new field of type "[[type]]".',
										'This field has [[count]] responses recorded against it, but there are no fields of the same type on the form. If you want to migrate this fields data to another field then create a new field of type "[[type]]".',
										$responseCount,
										array('count' => $responseCount, 'type' => $box['key']['field_english_type'])
									) . 
									'</p>';
								
								$fields['details/delete_field_options']['values']['delete_field_but_migrate_data']['disabled'] = true;
							} else {
								$fields['details/warning_message']['snippet']['html'] = 
									'<p>' . 
									nAdminPhrase(
										'This field has [[count]] response recorded against it.',
										'This field has [[count]] responses recorded against it.',
										$responseCount,
										array('count' => $responseCount)
									) . 
									'</p>';
								
								$fields['details/migration_field']['values'] = $responsesTransferFields;
							}
						}
					}
				}
				break;
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			
			case 'zenario_user_admin_box_form':
				$fields['details/translate_text']['hidden'] = !checkRowExists('languages', array('translate_phrases' => 1));
				
				// Display translation boxes for translatable fields with a value entered
				$languages = getLanguages(false, true, true);
				$fieldsToTranslate = array('title', 'success_message', 'submit_button_text', 'default_next_button_text', 'default_previous_button_text', 'duplicate_email_address_error_message');
				foreach($fieldsToTranslate as $fieldName) {
					$fields['translations/'.$fieldName]['snippet']['html'] = '<b>'.$fields[$fieldName]['label'].'</b>';
					if (!empty($values[$fieldName])) {
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' "'.$values[$fieldName].'"';
						$sideNote = false;
						$readOnly = false;
					} else {
						$sideNote = adminPhrase('Text must be defined in the site\'s default language in order for you to define a translation');
						$readOnly = true;
						$fields['translations/'.$fieldName]['snippet']['html'] .= ' (No text is defined in the default language)';
					}
					foreach($languages as $language) {
						$fields['translations/'.$fieldName.'__'.$language['id']]['read_only'] = $readOnly;
						$fields['translations/'.$fieldName.'__'.$language['id']]['side_note'] = $sideNote;
					}
				}
				
				$box['tabs']['translations']['hidden'] = !$values['details/translate_text'];
				
				$fields['captcha/captcha_type']['hidden'] =
				$fields['captcha/extranet_users_use_captcha']['hidden'] =
					!$values['captcha/use_captcha'];
				
				$zenario_extranet = inc('zenario_extranet');
				if ($zenario_extranet) {
					$fields['data/user_status']['hidden'] =
					$fields['data/email_html']['hidden'] =
					$fields['data/add_user_to_group']['hidden'] =
					$fields['data/duplicate_submission_html']['hidden'] =
					$fields['data/user_duplicate_email_action']['hidden'] =
						!$values['data/save_data'];
				
					$fields['data/create_another_form_submission_record']['hidden'] =
						!$values['data/save_data'] || ($values['data/user_duplicate_email_action'] == 'stop');
				
					$fields['data/duplicate_email_address_error_message']['hidden'] = 
						$fields['data/user_duplicate_email_action']['hidden']
						|| ($values['data/user_duplicate_email_action'] != 'stop');
					
					$fields['data/log_user_in_cookie']['hidden'] =
						!($values['data/save_data'] && ($values['data/log_user_in'] == 1) && ($values['data/user_status'] == 'active'));
				
					$fields['data/log_user_in']['hidden'] =
						!($values['data/save_data'] && ($values['data/user_status'] == 'active'));
				
					$fields['data/create_another_form_submission_record']['disabled'] = !$values['data/save_record'];
					if (!$values['data/save_record']) {
						$values['data/create_another_form_submission_record'] = false;
					}
				}
				
				$fields['data/user_email_template']['hidden'] = 
						!$values['data/send_email_to_user'];
				
				$fields['data/admin_email_addresses']['hidden'] = 
				$fields['data/admin_email_options']['hidden'] = 
				$fields['data/reply_to']['hidden'] = 
					!$values['data/send_email_to_admin'];  
				
				$fields['data/admin_email_template']['hidden'] = 
					!($values['data/send_email_to_admin'] && ($values['data/admin_email_options'] == 'use_template'));
				
				$fields['data/reply_to_email_field']['hidden'] = 
				$fields['data/reply_to_first_name']['hidden'] = 
				$fields['data/reply_to_last_name']['hidden'] = 
					!($values['data/reply_to'] && $values['data/send_email_to_admin']);
				
				$fields['details/redirect_location']['hidden'] = $values['details/success_message_type'] != 'redirect_after_submission';
				
				$fields['details/success_message']['hidden'] = $values['details/success_message_type'] != 'show_success_message';
				
				
				if (empty($box['key']['id'])) {
					$values['data/create_another_form_submission_record'] = $values['data/save_record'];
				} else {
					$box['title'] = adminPhrase('Editing the Form "[[name]]"', array('name' => $values['details/name']));
				}
				
				
				if ($box['key']['type'] == 'registration') {
					$fields['details/success_message_type']['hidden'] = true;
					$fields['details/redirect_location']['hidden'] = true;
					$fields['details/success_message']['hidden'] = true;
					$fields['details/default_next_button_text']['hidden'] = true;
					$fields['details/default_previous_button_text']['hidden'] = true;
					
					$fields['data/save_data']['hidden'] = true;
					$fields['data/email_html']['hidden'] = true;
					$fields['data/user_status']['hidden'] = true;
					$fields['data/log_user_in']['hidden'] = true;
					$fields['data/log_user_in_cookie']['hidden'] = true;
					$fields['data/add_user_to_group']['hidden'] = true;
					$fields['data/duplicate_submission_html']['hidden'] = true;
					$fields['data/user_duplicate_email_action']['hidden'] = true;
					$fields['data/duplicate_email_address_error_message']['hidden'] = true;
					$fields['data/create_another_form_submission_record']['hidden'] = true;
					$fields['data/line_br_2']['hidden'] = true;
					
					$fields['data/send_email_to_user']['hidden'] = true;
					$fields['data/user_email_template']['hidden'] = true;
					$fields['data/line_br_3']['hidden'] = true;
					
					$fields['data/send_email_to_admin']['hidden'] = true;
					$fields['data/admin_email_addresses']['hidden'] = true;
					$fields['data/admin_email_options']['hidden'] = true;
					$fields['data/admin_email_template']['hidden'] = true;
					$fields['data/reply_to']['hidden'] = true;
					$fields['data/reply_to_email_field']['hidden'] = true;
					$fields['data/reply_to_first_name']['hidden'] = true;
					$fields['data/reply_to_last_name']['hidden'] = true;
					$fields['data/line_br_4']['hidden'] = true;
					
					$box['tabs']['captcha']['hidden'] = true;
				}
				
				break;
			
			case 'zenario_email_template':
				if ($formId = $values['body/user_form']) {
					// Get list of form fields for form
					$fields['body/user_form_field']['hidden'] = false;
					$sql = '
						SELECT
							uff.id,
							IF(
								uff.name IS NULL or uff.name = "", 
								IFNULL(
									cdf.db_column, 
									CONCAT("unlinked_", uff.field_type, "_", uff.id)
								), 
								uff.label
							) AS name,
							uff.field_type
						FROM '. DB_NAME_PREFIX. 'user_form_fields AS uff
						LEFT JOIN '. DB_NAME_PREFIX.'custom_dataset_fields AS cdf
							ON uff.user_field_id = cdf.id
						WHERE uff.user_form_id = '.(int)$formId. '
						ORDER BY uff.ord';
					
					$result = sqlSelect($sql);
					$formFields = array();
					$formFields['all'] = adminPhrase('Add all to template');
					while ($row = sqlFetchAssoc($result)) {
						if (static::fieldTypeCanRecordValue($row['field_type'])) {
							$formFields[$row['id']] = trim($row['name'], " \t\n\r\0\x0B:");
						}
					}
					$fields['body/user_form_field']['values'] = $formFields;
					
					
					if ($formFieldId = $values['body/user_form_field']) {
						// Add form field mergefield onto end of email template
						$sql = '
							SELECT 
								IFNULL(uff.name, cdf.label) AS name, 
								IFNULL(cdf.db_column, CONCAT(\'unlinked_\', uff.field_type, \'_\', uff.id)) AS mergefield
							FROM '.DB_NAME_PREFIX.'user_form_fields AS uff
							LEFT JOIN '.DB_NAME_PREFIX. 'custom_dataset_fields AS cdf
								ON uff.user_field_id = cdf.id
							WHERE (uff.field_type NOT IN ("page_break", "restatement", "section_description") 
								OR uff.field_type IS NULL)';
						
						if ($formFieldId == 'all') {
							$sql .= ' AND uff.user_form_id = '.(int)$formId;
						} else {
							$sql .= ' AND uff.id = '.(int)$formFieldId;
						}
						
						$result = sqlSelect($sql);
						$mergeFields = '';
						while ($row = sqlFetchAssoc($result)) {
							$mergeFields .= '<p>';
							if ($row['name']) {
								$mergeFields .= trim($row['name'], " \t\n\r\0\x0B:"). ': ';
							}
							$mergeFields .= '[['.$row['mergefield'].']]</p>';
						}
						$values['body/body'] .= $mergeFields;
						$values['body/user_form_field'] = '';
					}
				} else {
					$fields['body/user_form_field']['hidden'] = true;
				}
				break;
			case 'plugin_settings':
				$fields['first_tab/display_text']['hidden'] = ($values['first_tab/display_mode'] != 'in_modal_window');
				break;
			
			case 'zenario_delete_form_field':
				$fields['details/migration_field']['hidden'] = $values['details/delete_field_options'] != 'delete_field_but_migrate_data';
				
				$responseCount = 0;
				
				// If migrating data show warning if selected field has existing responses
				if ($values['details/delete_field_options'] == 'delete_field_but_migrate_data') {
					
					$box['save_button_message'] = adminPhrase('Migrate and delete');
					
					if ($values['details/migration_field'] && is_numeric($values['details/migration_field'])) {
					
						$responseCount = (int)selectCount(
							ZENARIO_USER_FORMS_PREFIX . 'user_response_data', 
							array('form_field_id' => $values['details/migration_field'])
						);
						
						if ($responseCount >= 1) {
							$fields['details/data_migration_warning_message']['snippet']['html'] = 
								'<p>' . 
								nAdminPhrase(
									'That field already has [[count]] response recorded against it. By migrating responses to it any previous responses will be deleted.',
									'That field already has [[count]] responses recorded against it. By migrating responses to it any previous responses will be deleted.',
									$responseCount,
									array('count' => $responseCount)
								) . 
								'</p>';
						}
					}
				}
				
				$fields['details/data_migration_warning_message']['hidden'] = ($responseCount == 0);
				break;
			
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case 'zenario_user_dataset_field_picker':
				if ($values['dataset_fields/dataset_fields'] 
					&& checkRowExists('user_form_fields', 
						array('user_form_id' => $box['key']['refinerId'], 'user_field_id' => $values['dataset_fields/dataset_fields']))) {
					
					$box['tabs']['dataset_fields']['errors'][] = adminPhrase('You cannot add the same dataset field to a form more than once');
				}
				break;
			case 'zenario_user_admin_box_form':
				$errors = &$box['tabs']['details']['errors'];
				
				if (empty($values['details/name'])) {
					$errors[] = adminPhrase('Please enter a name for this Form.');
				} else {
					$sql = '
						SELECT id
						FROM ' . DB_NAME_PREFIX . 'user_forms
						WHERE name = "' . sqlEscape($values['details/name']) . '"';
					if ($box['key']['id']) {
						$sql .= ' 
							AND id != ' . (int)$box['key']['id'];
					}
					$result = sqlQuery($sql);
					if (sqlNumRows($result) > 0) {
						$errors[] = adminPhrase('The name "[[name]]" is used by another form.', array('name' => $values['details/name']));
					}
				}
				
				$errors = &$box['tabs']['data']['errors'];
				// Create an error if the form is doing nothing with data
				if ($saving
					&& !$box['key']['type']
					&& (!inc('zenario_extranet') || empty($values['data/save_data']))
					&& empty($values['data/save_record'])
					&& empty($values['data/send_signal'])
					&& empty($values['data/send_email_to_user'])
					&& empty($values['data/send_email_to_admin'])) {
					$errors[] = adminPhrase('This form is currently not using the data submitted in any way. Please select at least one of the following options.');
				}
				break;
			case 'zenario_user_forms__export_user_responses':
				$errors = &$box['tabs']['details']['errors'];
				if ($values['details/responses_to_export'] === 'specific_date_range') {
					// Validate dates
					if (!$values['details/date_from']) {
						$errors[] = adminPhrase('Please choose a "from date" for the range.');
					} elseif (!$values['details/date_to']) {
						$errors[] = adminPhrase('Please choose a "to date" for the range.');
					} elseif (strtotime($values['details/date_to']) > strtotime($values['details/date_to'])) {
						$errors[] = adminPhrase('The "from date" cannot be before the "to date"	');
					}
				} elseif ($values['details/responses_to_export'] === 'from_id') {
				// Validate ID
					if (!$values['details/response_id']) {
						$errors[] = adminPhrase('Please enter a response ID.');
					} elseif (
						!checkRowExists(
							ZENARIO_USER_FORMS_PREFIX . 'user_response', 
						array('id' => $values['details/response_id'])
						)
					) {
						$errors[] = adminPhrase('Unable to find a response with that ID.');
					}
				}
				break;
			
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case 'site_settings':
				if(empty($values['zenario_user_forms_set_profanity_filter'])) {
					$sql = "UPDATE ". DB_NAME_PREFIX. "user_forms SET profanity_filter_text = 0";
					sqlQuery($sql);
				}
				break;
			case 'zenario_user_dataset_field_picker':
				if (($refinerId = $box['key']['refinerId']) && $values['dataset_fields']) {
					
					$last_ordinal = static::getMaxOrdinalOfFormFields($box['key']['refinerId']);
					$last_ordinal++;
					
					$field_id = (int)$values['dataset_fields'];
					
					$dataset = getDatasetDetails('users');
					$field = getDatasetFieldDetails($field_id, $dataset);
					
					if ($field['label']) {
						$label = $field['label'];
					} else {
						$label = $field['db_column'];
						if ($field['tab_name'] && $field['field_name']) {
							$boxPath = $dataset['extends_admin_box'];
							$moduleFilesLoaded = array();
							$tags = array();
							loadTUIX($moduleFilesLoaded, $tags, $type = 'admin_boxes', $boxPath);
							if (!empty($tags[$boxPath]['tabs'][$field['tab_name']]['fields'][$field['field_name']]['label'])) {
								$label = $tags[$boxPath]['tabs'][$field['tab_name']]['fields'][$field['field_name']]['label'];
							}
						}
					}
					insertRow('user_form_fields', array('label'=>$label, 'name'=>$label, 'user_form_id'=>$refinerId, 'user_field_id'=>$field_id, 'ord'=>$last_ordinal));
				}
				break;
			case 'zenario_user_admin_box_form':
				
				exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
				
				$record = array();
				$record['name'] = $values['name'];
				
				$title = '';
				if ($values['show_title']) {
					$title = $values['title'];
				}
				$record['title'] = $title;
				$record['title_tag'] = $values['title_tag'];
				$record['use_captcha'] = $values['use_captcha'];
				$record['captcha_type'] = ($values['use_captcha'] ? $values['captcha_type'] : 'word');
				$record['extranet_users_use_captcha'] = $values['extranet_users_use_captcha'];
				$record['admin_email_use_template'] = ($values['admin_email_options'] == 'use_template');
				$record['send_email_to_user'] = (empty($values['send_email_to_user']) ? 0 : 1);
				$record['user_email_template'] = (empty($values['send_email_to_user']) ? null : $values['user_email_template']);
				$record['send_email_to_admin'] = (empty($values['send_email_to_admin']) ? 0 : 1);
				$record['admin_email_addresses'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_addresses']);
				$record['admin_email_template'] = (empty($values['send_email_to_admin']) ? null : $values['admin_email_template']);
				$removeReplyToFields = empty($values['reply_to']) || empty($values['send_email_to_admin']);
				$record['reply_to'] = ($removeReplyToFields ? 0 : 1);
				$record['reply_to_email_field'] = ($removeReplyToFields ? null : $values['reply_to_email_field']);
				$record['reply_to_first_name'] = ($removeReplyToFields ? null : $values['reply_to_first_name']);
				$record['reply_to_last_name'] = ($removeReplyToFields ? null : $values['reply_to_last_name']);
				$record['save_data'] = $values['save_data'];
				$record['save_record'] = $values['save_record'];
				$record['add_user_to_group'] = (empty($values['save_data']) ? null : $values['add_user_to_group']);
				$record['send_signal'] = (empty($values['send_signal']) ? 0 : 1);
				$record['show_success_message'] = ($values['success_message_type'] == 'show_success_message');
				$record['redirect_after_submission'] = ($values['success_message_type'] == 'redirect_after_submission');
				$record['redirect_location'] = (($values['success_message_type'] != 'redirect_after_submission') ? null : $values['redirect_location']);
				$record['success_message'] = (($values['success_message_type'] != 'show_success_message') ? null : $values['success_message']);
				$record['user_status'] = (empty($values['save_data']) ? 'contact' : $values['user_status']);
				$record['log_user_in'] = (empty($values['log_user_in']) ? 0 : 1);
				
				if($record['log_user_in']) {
					$record['log_user_in_cookie'] = (empty($values['log_user_in_cookie']) ? 0 : 1);
					
				} else {
					$record['log_user_in_cookie'] = 0;
				}
				$record['user_duplicate_email_action'] = (empty($values['user_duplicate_email_action']) ? null : $values['user_duplicate_email_action']);
				$record['create_another_form_submission_record'] = (empty($values['create_another_form_submission_record']) ? 0 : 1);
				$record['translate_text'] = (empty($values['translate_text']) ? 0 : 1);
				$record['submit_button_text'] = (empty($values['submit_button_text']) ? 'Submit' : $values['submit_button_text']);
				$record['default_next_button_text'] = (empty($values['default_next_button_text']) ? 'Next' : $values['default_next_button_text']);
				$record['default_previous_button_text'] = (empty($values['default_previous_button_text']) ? 'Back' : $values['default_previous_button_text']);
				$record['duplicate_email_address_error_message'] = ($values['user_duplicate_email_action'] != 'stop') ? 'Sorry this form has already been completed with this email address' : $values['duplicate_email_address_error_message'];
				$record['profanity_filter_text'] = (empty($values['profanity_filter_text_fields']) ? 0 : 1);
				
				if ($id = $box['key']['id']) {
					setRow('user_forms', $record, array('id' => $id));
					
					$formProperties = getRow('user_forms', array('translate_text'), array('id' => $id));
					// Save translations
					if ($formProperties['translate_text']) { 
						$translatableFields = array('title', 'success_message', 'submit_button_text', 'default_next_button_text', 'default_previous_button_text', 'duplicate_email_address_error_message');
						
						// Update phrase code if phrases are changed to keep translation chain
						$fieldsToTranslate = getRow('user_forms', $translatableFields, $id);
						$languages = getLanguages(false, true, true);
						
						foreach($fieldsToTranslate as $name => $oldCode) {
							// Check if old value has more than 1 entry in any translatable field
							$identicalPhraseFound = false;
							if($oldCode) {
								$sql = '
									SELECT '
										.sqlEscape(implode(', ', $translatableFields)).'
									FROM 
										'.DB_NAME_PREFIX.'user_forms
									WHERE ( 
											title = "'.sqlEscape($oldCode).'"
										OR
											success_message = "'.sqlEscape($oldCode).'"
										OR
											submit_button_text = "'.sqlEscape($oldCode).'"
										OR
											default_next_button_text = "'.sqlEscape($oldCode).'"
										OR
											default_previous_button_text = "'.sqlEscape($oldCode).'"
										OR
											duplicate_email_address_error_message = "'.sqlEscape($oldCode).'"
										)';
								$result = sqlSelect($sql);
								if (sqlNumRows($result) > 1) {
									$identicalPhraseFound = true;
								}
							}
							
							// If another field is using the same phrase code...
							if ($identicalPhraseFound) {
								foreach($languages as $language) {
									// Create or overwrite new phrases with the new english code
									$setArray = array('code' => $values[$name]);
									if (!empty($language['translate_phrases'])) {
										$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '') ? $values['translations/'.$name.'__'.$language['id']] : null;
									}
									setRow('visitor_phrases', 
										$setArray,
										array(
											'code' => $values[$name],
											'module_class_name' => 'zenario_user_forms',
											'language_id' => $language['id']));
								}
							} else {
								// If nothing else is using the same phrase code...
								if (!checkRowExists('visitor_phrases', array('code' => $values[$name], 'module_class_name' => 'zenario_user_forms'))) {
									updateRow('visitor_phrases', 
										array('code' => $values[$name]), 
										array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									foreach($languages as $language) {
										if ($language['translate_phrases'] && !empty($values['translations/'.$name.'__'.$language['id']])) {
											setRow('visitor_phrases',
												array(
													'local_text' => ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null), 
												array(
													'code' => $values[$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
										
									}
								// If code already exists, and nothing else is using the code, delete current phrases, and update/create new translations
								} else {
									deleteRow('visitor_phrases', array('code' => $oldCode, 'module_class_name' => 'zenario_user_forms'));
									if (isset($values[$name]) && !empty($values[$name])) {
										foreach($languages as $language) {
											$setArray = array('code' => $values[$name]);
											if (!empty($language['translate_phrases'])) {
												$setArray['local_text'] = ($values['translations/'.$name.'__'.$language['id']] !== '' ) ? $values['translations/'.$name.'__'.$language['id']] : null;
											}
											setRow('visitor_phrases',
												$setArray,
												array(
													'code' => $values[$name], 
													'module_class_name' => 'zenario_user_forms', 
													'language_id' => $language['id']));
										}
									}
								}
							}
						}
					}
					
				} else {
					$record['type'] = 'standard';
					if ($box['key']['type']) {
						$record['type'] = $box['key']['type'];
					}
					$formId = setRow('user_forms', $record, array());
					
					// Add default form fields for form types
					if ($box['key']['type'] == 'profile') {
						//TODO
					} elseif ($box['key']['type'] == 'registration') {
						$dataset = getDatasetDetails('users');
						
						$emailField = getDatasetFieldDetails('email', $dataset);
						insertRow(
							'user_form_fields', 
							array(
								'user_form_id' => $formId,
								'user_field_id' => $emailField['id'],
								'ord' => 1,
								'is_required' => 1,
								'label' => 'Email:',
								'name' => 'Email',
								'required_error_message' => 'Please enter your email address',
								'validation' => 'email',
								'validation_error_message' => 'Please enter a valid email address'
							)
						);
						
						$salutationField = getDatasetFieldDetails('salutation', $dataset);
						insertRow(
							'user_form_fields', 
							array(
								'user_form_id' => $formId,
								'user_field_id' => $salutationField['id'],
								'ord' => 2,
								'label' => 'Salutation:',
								'name' => 'Salutation'
							)
						);
						
						$firstNameField = getDatasetFieldDetails('first_name', $dataset);
						insertRow(
							'user_form_fields', 
							array(
								'user_form_id' => $formId,
								'user_field_id' => $firstNameField['id'],
								'ord' => 3,
								'is_required' => 1,
								'label' => 'First name:',
								'name' => 'First name',
								'required_error_message' => 'Please enter your first name'
							)
						);
						
						$lastNameField = getDatasetFieldDetails('last_name', $dataset);
						insertRow(
							'user_form_fields', 
							array(
								'user_form_id' => $formId,
								'user_field_id' => $lastNameField['id'],
								'ord' => 3,
								'is_required' => 1,
								'label' => 'Last name:',
								'name' => 'Last name',
								'required_error_message' => 'Please enter your last name'
							)
						);
					}
					
					$box['key']['id'] = $formId;
				}
				break;
			case 'zenario_user_forms__export_user_responses':
				
				exitIfNotCheckPriv('_PRIV_VIEW_FORM_RESPONSES');
				// Export responses
				
				// Create PHPExcel object
				require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel/Classes/PHPExcel.php';
				$objPHPExcel = new PHPExcel();
				$sheet = $objPHPExcel->getActiveSheet();
				
				// Get headers
				$typesNotToExport = array('page_break', 'section_description', 'restatement');
				$formFields = array();
				$sql = '
					SELECT id, name
					FROM ' . DB_NAME_PREFIX . 'user_form_fields
					WHERE user_form_id = ' . (int)$box['key']['form_id'] . '
					AND (field_type NOT IN (' . inEscape($typesNotToExport) . ')
						OR field_type IS NULL
					)
					ORDER BY ord
				';
				$result = sqlSelect($sql);
				while ($row = sqlFetchAssoc($result)) {
					$formFields[$row['id']] = $row['name'];
				}
				
				$lastColumn = PHPExcel_Cell::stringFromColumnIndex(count($formFields) + 1);
				
				// Set columns to text type
				$sheet->getStyle('A:' . $lastColumn)
					->getNumberFormat()
					->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
				
				// Write headers
				$sheet->setCellValue('A1', 'Response ID');
				$sheet->setCellValue('B1', 'Date/Time Responded');
				$sheet->fromArray($formFields, NULL, 'C1');
				
				// Get data
				$responsesData = array();
				$sql = '
					SELECT urd.value, urd.form_field_id, uff.ord, ur.id
					FROM '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response AS ur
					LEFT JOIN '.DB_NAME_PREFIX. ZENARIO_USER_FORMS_PREFIX .'user_response_data AS urd
						ON ur.id = urd.user_response_id
					LEFT JOIN '.DB_NAME_PREFIX. 'user_form_fields AS uff
						ON urd.form_field_id = uff.id
					WHERE ur.form_id = '. (int)$box['key']['form_id'];
				
				// Add any filters
				switch ($values['details/responses_to_export']) {
					case 'today':
						$date = date('Y-m-d 00:00:00');
						$sql .= '
							AND ur.response_datetime >= "' . sqlEscape($date) . '"';
						break;
					case 'last_2_days':
						$date = date('Y-m-d 00:00:00', strtotime('-1 day'));
						$sql .= '
							AND ur.response_datetime >= "' . sqlEscape($date) . '"';
						break;
					case 'last_week':
						$sql .= '
							AND ur.response_datetime >= (CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY)';
						break;
					case 'specific_date_range':
						$from = $values['details/date_from'] . ' 00:00:00';
						$to = $values['details/date_to'] . ' 23:59:59';
						$sql .= ' AND ur.response_datetime BETWEEN "' . sqlEscape($from) . '" AND "' . sqlEscape($to) . '"'; 
						break;
					case 'from_id':
						$sql .= '
							AND ur.id >= ' . (int)$values['details/response_id'];
						break;
				}
				
				$sql .= '
					ORDER BY ur.response_datetime DESC, uff.ord';
				$result = sqlSelect($sql);
				
				
				while ($row = sqlFetchAssoc($result)) {
					if (!isset($responsesData[$row['id']])) {
						$responsesData[$row['id']] = array();
					}
					if (isset($formFields[$row['form_field_id']])) {
						$responsesData[$row['id']][$row['form_field_id']] = $row['value'];
					}
				}
				
				$responseDates = getRowsArray(
					ZENARIO_USER_FORMS_PREFIX. 'user_response', 
					'response_datetime', 
					array('form_id' => $box['key']['form_id']), 'response_datetime'
				);
				
				// Write data
				$rowPointer = 1;
				foreach ($responsesData as $responseId => $responseData) {
					
					$rowPointer++;
					$response = array();
					$response[0] = $responseId;
					$response[1] = formatDateTimeNicely($responseDates[$responseId], '_MEDIUM');
					
					$j = 1;
					foreach ($formFields as $formFieldId => $name) {
						$response[++$j] = '';
						if (isset($responseData[$formFieldId])) {
							$response[$j] = $responseData[$formFieldId];
						}
					}
					
					foreach ($response as $columnPointer => $value) {
						$sheet->setCellValueExplicitByColumnAndRow($columnPointer, $rowPointer, $value);
					}
				}
				
				$formName = getRow('user_forms', 'name', array('id' => $box['key']['form_id']));
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$formName.' user responses.xls"');
				$objWriter->save('php://output');
				
				$box['key']['form_id'] = '';
				exit;
		}
	}
	
	// Delete a form
	public static function deleteForm($formId) {
		
		$error = new zenario_error();
		
		// Get form details
		$formDetails = getRow('user_forms', array('name'), $formId);
		if ($formDetails === false) {
			$error->add(adminPhrase('Error. Form with ID "[[id]]" does not exist.', array('id' => $formId)));
			return $error;
		}
		
		// Don't delete forms used in plugins
		$moduleIds = static::getFormModuleIds();
		$instanceIds = array();
		$sql = '
			SELECT id
			FROM '.DB_NAME_PREFIX.'plugin_instances
			WHERE module_id IN ('. inEscape($moduleIds, 'numeric'). ')
			ORDER BY name';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$instanceIds[] = $row['id'];
		}
		foreach ($instanceIds as $instanceId) {
			if (checkRowExists('plugin_settings', array('instance_id' => $instanceId, 'name' => 'user_form', 'value' => $formId))) {
				$error->add(adminPhrase('Error. Unable to delete form "[[name]]" as it is used in a plugin.', $formDetails));
				return $error;
			}
		}
		
		// Don't delete forms with logged responses
		if (checkRowExists(ZENARIO_USER_FORMS_PREFIX.'user_response', array('form_id' => $formId))) {
			$error->add(adminPhrase('Error. Unable to delete form "[[name]]" as it has logged user responses.', $formDetails));
			return $error;
		}
		
		// Send signal that the form is now deleted (sent before actual delete in case modules need to look at any metadata or form fields)
		sendSignal('eventFormDeleted', array($formId));
		
		// Delete form
		deleteRow('user_forms', $formId);
		
		// Delete form fields
		$result = getRows('user_form_fields', array('id'), array('user_form_id' => $formId));
		while ($row = sqlFetchAssoc($result)) {
			static::deleteFormField($row['id'], false, false);
		}
		
		// Delete responses
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response', array('form_id' => $formId));
		
		return true;
	}
	
	// Delete a form field
	public static function deleteFormField($fieldId, $updateOrdinals = true, $formExists = true) {
		
		$error = new zenario_error();
		$formFields = static::getUserFormFields(false, $fieldId);
		$formField = arrayKey($formFields, $fieldId);
		
		if ($formExists) {
			
			// Get form field details
			if (empty($formField)) {
				$error->add(adminPhrase('Error. Form field with ID "[[id]]" does not exist.', array('id' => $fieldId)));
				return $error;
			}
			
			// Don't delete form fields used by other fields
			$sql = '
				SELECT id, name
				FROM ' . DB_NAME_PREFIX . 'user_form_fields
				WHERE restatement_field = ' . (int)$fieldId . '
				OR numeric_field_1 = ' . (int)$fieldId . '
				OR numeric_field_2 = ' . (int)$fieldId;
			$result = sqlSelect($sql);
			if (sqlNumRows($result) > 0) {
				$row = sqlFetchAssoc($result);
				$formField['name2'] = $row['name'];
				$error->add(adminPhrase('Unable to delete the field "[[name]]" as the field "[[name2]]" depends on it.', $formField));
				return $error;
			}
		}
		
		// Send signal that the form field is now deleted (sent before actual delete in case modules need to look at any metadata or field values)
		sendSignal('eventFormFieldDeleted', array($fieldId));
		
		// Delete form field
		deleteRow('user_form_fields', $fieldId);
		
		// Update remaining field ordinals
		if ($updateOrdinals && !empty($formField)) {
			$result = getRows('user_form_fields', array('id'), array('user_form_id' => $formField['user_form_id']), 'ord');
			$ord = 0;
			while ($row = sqlFetchAssoc($result)) {
				updateRow('user_form_fields', array('ord' => ++$ord), $row['id']);
			}
		}
		
		// Delete any field values
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('form_field_id' => $fieldId));
		
		// Delete any update links
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', array('target_field_id' => $fieldId));
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', array('source_field_id' => $fieldId));
		
		// Delete any files saved as a response if not used elsewhere
		$type = static::getFieldType($formField);
		if ($type == 'attachment' || $type == 'file_picker') {
			$responses = getRows(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('internal_value'), array('form_field_id' => $fieldId));
			while ($response = sqlFetchAssoc($responses)) {
				if (!empty($response['internal_value'])) {
					$sql = '
						SELECT urd.form_field_id
						FROM ' . DB_NAME_PREFIX . ZENARIO_USER_FORMS_PREFIX . 'user_response_data urd
						INNER JOIN ' . DB_NAME_PREFIX . 'user_form_fields uff
							ON urd.form_field_id = uff.id
						LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields cdf
							ON uff.user_field_id = cdf.id
						WHERE (uff.field_type = "attachment" OR cdf.type = "file_picker")
						AND urd.form_field_id != ' . (int)$fieldId . '
						AND urd.internal_value = ' . (int)$response['internal_value'];
					$otherFieldResponsesWithSameFile = sqlSelect($sql);
					if (sqlNumRows($otherFieldResponsesWithSameFile) <= 0) {
						deleteFile($response['internal_value']);
					}
				}
			}
		}
		
		// Delete any response data
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'user_response_data', array('form_field_id' => $fieldId));
		return true;
	}
	
	public static function deleteFormFieldValue($valueId) {
		deleteRow(ZENARIO_USER_FORMS_PREFIX . 'form_field_values', array('id' => $valueId));
		sendSignal('eventFormFieldValueDeleted', array($valueId));
	}
	
	public static function isDatasetFieldOnForm($formId, $datasetField) {
		$datasetFieldId = $datasetField;
		if (!is_numeric($datasetField)) {
			$dataset = getDatasetDetails('users');
			$datasetField = getDatasetFieldDetails($datasetField, $dataset);
			$datasetFieldId = $datasetField['id'];
		}
		return getRow('user_form_fields', 'id', array('user_form_id' => $formId, 'user_field_id' => $datasetFieldId));
	}
	
	
	public static function drawMenu($nodeId, $cID, $cType) {
		$nodes = array();
		do {
			$text = getRow('menu_text', 'name', array('menu_id' => $nodeId, 'language_id' => setting('default_language')));
			$nodes[] = $text;
			$nodeId = getMenuParent($nodeId);
		} while ($nodeId != 0);
		$homeCID = $homeCType = false;
		langSpecialPage('zenario_home', $homeCID, $homeCType);
		if (!($cID == $homeCID && $cType == $homeCType)) {
			$equivId = equivId($homeCID, $homeCType);
			$sectionId = menuSectionId('Main');
			$menuId = getRow('menu_nodes', 'id', array('section_id' => $sectionId, 'equiv_id' => $equivId, 'content_type' => $homeCType));
			$nodes[] = getRow('menu_text', 'name', array('menu_id' => $menuId, 'language_id' => setting('default_language')));
		}
		return $nodes;
	}
	
	public function getFormIdentifier() {
		return $this->containerId.'_user_form';
	}
	
	private function getCaptchaError($type, $translate) {
		if ($type == 'word') {
			if ($this->checkCaptcha()) {
				$_SESSION['captcha_passed__'. $this->instanceId] = true;
			} else {
				return static::formPhrase('Please verify that you are human.', array(), $translate);
			}
		} elseif ($type == 'math' && isset($_POST['captcha_code'])) {
			require_once CMS_ROOT. 'zenario/libraries/mit/securimage/securimage.php';
			$securimage = new Securimage();
			
			if ($securimage->check($_POST['captcha_code']) != false) {
				$_SESSION['captcha_passed__'. $this->instanceId] = true;
			} else {
				return static::formPhrase('Please verify that you are human.', array(), $translate);
			}
		}elseif($type == 'pictures' && isset($_POST['g-recaptcha-response']) && setting('google_recaptcha_site_key') && setting('google_recaptcha_secret_key')){
			$recaptchaResponse = $_POST['g-recaptcha-response'];
			$secretKey = setting('google_recaptcha_secret_key');
			$URL = "https://www.google.com/recaptcha/api/siteverify?secret=".$secretKey."&response=".$recaptchaResponse;
			$request = file_get_contents($URL);
			$response = json_decode($request, true);

			if(is_array($response)){
				if(isset($response['success'])){
					if($response['success']){
						$_SESSION['captcha_passed__'. $this->instanceId] = true;
					}else{
						return static::formPhrase('Please verify that you are human.', array(), $translate);
					}
				}
			}
		}
		return false;
	}
	
	private static function getFormEncType($formId) {
		$sql = '
			SELECT uff.id
			FROM ' . DB_NAME_PREFIX . 'user_form_fields uff
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ' . (int)$formId . '
			AND (uff.field_type = "attachment"
				OR cdf.type = "file_picker"
			)';
		$result = sqlSelect($sql);
		if (sqlNumRows($result) > 0) {
			return 'enctype="multipart/form-data"';
		}
		return '';
	}
	
	// Get a form fields list of values
	private static function getFormFieldLOV($field, $type) {
		$lov = array();
		if ($type == 'checkboxes' || $type == 'radios' || $type == 'centralised_radios' || $type == 'select') {
			if ($field['user_field_id']) {
				$lov = getDatasetFieldLOV($field['user_field_id']);
			} else {
				$lov = self::getUnlinkedFieldLOV($field['form_field_id']);
			}
		}
		return $lov;
	}
	
	// Get a form fields current value
	private static function getFormFieldValue($field, $type, $submitted, $loadedFieldValue, $userId, $dataset, &$filePickerValueLink = array()) {
		$value = false;
		
		// If form was submitted, use form data
		if ($submitted) {
			if ($type == 'checkboxes') {
				$values = array();
				$labels = array();
				if (!empty($field['user_field_id'])) {
					$valuesList = getDatasetFieldLOV($field['user_field_id']);
				} else {
					$valuesList = static::getUnlinkedFieldLOV($field['form_field_id']);
				}
				$fieldName = static::getFieldName($field);
				
				if ($valuesList && is_array($loadedFieldValue)) {
					foreach ($valuesList as $valueId => $label) {
						if (isset($loadedFieldValue[$valueId . '_' . $fieldName])) {
							$labels[] = $label;
							$values[] = $valueId;
						}
					}
				}
				$value = array(
					'ids' => implode(',', $values),
					'labels' => implode(',', $labels)
				);
			} elseif ($type == 'file_picker') {
				
				$values = array();
				
				$filesCount = 0;
				$maxFilesCount = $field['multiple_select'] ? 5 : 1;
				$files = array_intersect_key($loadedFieldValue, array_flip(preg_grep('/^file_picker_' . $field['form_field_id'] . '_\d+$/', array_keys($loadedFieldValue))));
				foreach ($files as $inputName => $fileValue) {
					
					$values[] = '"' . str_replace('"', '\\"', $fileValue) . '"';
					$filePickerValueLink[$fileValue] = $inputName;
					
					if (++$filesCount >= $maxFilesCount) {
						break;
					}
				}
				$value = implode(',', $values);
				
			} else {
				$value = $loadedFieldValue;
			}
		
		// If preloading users data try and get data
		} elseif ($userId && !empty($field['user_field_id'])) {
			
			if ($type == 'checkboxes') {
				$value = array();
				$value['ids'] = datasetFieldValue($dataset, $field['user_field_id'], $userId);
			} else {
				$value = datasetFieldValue($dataset, $field['user_field_id'], $userId);
			}
		
		// If no data to get, look for a default value
		} elseif (in_array($type, array('radios', 'centralised_radios', 'select', 'centralised_select', 'text', 'textarea', 'checkbox', 'group'))) {
			if ($field['default_value'] !== null) {
				$value = $field['default_value'];
			} elseif (!empty($field['default_value_class_name']) && !empty($field['default_value_method_name'])) {
				inc($field['default_value_class_name']);
				$value = call_user_func(array($field['default_value_class_name'], $field['default_value_method_name']), $field['default_value_param_1'], $field['default_value_param_2']);
			}
		}
		return $value;
	}
	
	public static function getFormDetails($userFormId) {
		return getRow(
			'user_forms', 
			array(
				'name', 
				'title', 
				'title_tag', 
				'success_message', 
				'show_success_message', 
				'use_captcha', 
				'captcha_type', 
				'extranet_users_use_captcha', 
				'send_email_to_admin', 
				'admin_email_use_template', 
				'translate_text', 
				'submit_button_text', 
				'default_previous_button_text'
			), 
			$userFormId
		);
	}
	
	public static function getUserFormFields($userFormId, $formFieldId = false, $type = false) {
		$formFields = array();
		$sql = "
			SELECT 
				uff.id AS form_field_id, 
				uff.user_field_id,
				uff.user_form_id,
				uff.ord, 
				uff.is_readonly, 
				uff.is_required,
				uff.mandatory_condition_field_id,
				uff.mandatory_condition_field_value,
				uff.visibility,
				uff.visible_condition_field_id,
				uff.visible_condition_field_value,
				uff.label AS field_label,
				uff.name,
				uff.placeholder,
				uff.size,
				uff.default_value,
				uff.default_value_class_name,
				uff.default_value_method_name,
				uff.default_value_param_1,
				uff.default_value_param_2,
				uff.note_to_user,
				uff.css_classes,
				uff.div_wrap_class,
				uff.required_error_message,
				uff.validation AS field_validation,
				uff.validation_error_message,
				uff.field_type,
				uff.next_button_text,
				uff.previous_button_text,
				uff.description,
				uff.numeric_field_1,
				uff.numeric_field_2,
				uff.calculation_type,
				uff.restatement_field,
				uff.values_source,
				uff.values_source_filter,
				uff.custom_code_name,
				uff.autocomplete,
				uff.autocomplete_class_name,
				uff.autocomplete_method_name,
				uff.autocomplete_param_1,
				uff.autocomplete_param_2,
				uff.autocomplete_no_filter_placeholder,
				
				cdf.id AS field_id, 
				cdf.type, 
				cdf.db_column, 
				cdf.label,
				cdf.default_label,
				cdf.is_system_field, 
				cdf.dataset_id, 
				cdf.validation, 
				cdf.validation_message,
				cdf.multiple_select,
				cdf.store_file,
				cdf.extensions,
				cdf.values_source AS dataset_values_source
				
			FROM ". DB_NAME_PREFIX ."user_forms AS uf
			INNER JOIN ". DB_NAME_PREFIX ."user_form_fields AS uff
				ON uf.id = uff.user_form_id
			LEFT JOIN ". DB_NAME_PREFIX ."custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE TRUE";
		if ($userFormId !== false) {
			$sql .= "
				AND uf.id = ". (int)$userFormId;
		}
		if ($formFieldId !== false) {
			$sql .= '
				AND uff.id = '.(int)$formFieldId;
		}
		$sql .= "
			ORDER BY uff.ord";
		
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			// Filter on type
			if ($type) {
				$row['type'] = static::getFieldType($row);
				if ($row['type'] != $type) {
					continue;
				}
			}
			
			// Use dataset label for dataset field names
			if ($row['field_id']) {
				$row['name'] = $row['label'] ? $row['label'] : $row['default_label'];
			}
			
			$row['ord'] = (int)$row['ord'];
			$row['form_field_id'] = (int)$row['form_field_id'];
			$row['is_readonly'] = (int)$row['is_readonly'];
			$row['is_required'] = (int)$row['is_required'];
			$row['user_field_id'] = (int)$row['user_field_id'];
			$row['visible_condition_field_id'] = (int)$row['visible_condition_field_id'];
			$row['mandatory_condition_field_id'] = (int)$row['mandatory_condition_field_id'];
			$row['field_id'] = (int)$row['field_id'];
			$row['is_system_field'] = (int)$row['is_system_field'];
			$row['dataset_id'] = (int)$row['dataset_id'];
			$row['numeric_field_1'] = (int)$row['numeric_field_1'];
			$row['numeric_field_2'] = (int)$row['numeric_field_2'];
			$row['autocomplete'] = (int)$row['autocomplete'];
			$formFields[$row['form_field_id']] = $row;
		}
		return $formFields;
	}
	
	public static function drawUserForm($userFormId, $loadData = false, $readOnly = false, $errors = array(), $checkboxColumns = 1, $containerId = '') {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function validateUserForm($userFormId, $data, $pageNo = 0, $registrationOptions = array()) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function saveUserForm($userFormId, &$data, &$redirectURL, $userId = false, $registrationOptions = array()) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	public static function formPhrase($phrase, $mergeFields = array(), $translate) {
		if ($translate) {
			return phrase($phrase, $mergeFields, 'zenario_user_forms');
		}
		return $phrase;
	}
	
	public static function getFieldType($field) {
		return (!empty($field['type']) ? $field['type'] : $field['field_type']);
	}
	
	public static function getFieldName($field) {
		return ($field['db_column'] ? $field['db_column'] : 'unlinked_'. $field['field_type'].'_'.$field['form_field_id']);
	}
	
	public static function filterFormFieldsByPage(&$formFields, $pageNo) {
		$currentPageNo = 1;
		foreach ($formFields as $fieldId => $field) {
			if ($pageNo != $currentPageNo) {
				unset($formFields[$fieldId]);
			}
			if ($field['field_type'] == 'page_break') {
				unset($formFields[$fieldId]);
				$currentPageNo++;
			}
		}
	}
	
	protected static function getTemplateEmailMergeFields($values, $userId, $sendToAdmin = false) {
		$emailMergeFields = array();
		foreach($values as $fieldId => $fieldData) {
			if (isset($fieldData['attachment'])) {
				if ($sendToAdmin) {
					$fieldData['value'] = absCMSDirURL() . 'zenario/file.php?adminDownload=1&id=' . $fieldData['internal_value'];
				} else {
					$fieldData['value'] = absCMSDirURL() . fileLink($fieldData['internal_value']);
				}
			}
			if (!empty($fieldData['type']) && ($fieldData['type'] == 'textarea') && $fieldData['value']) {
				$fieldData['value'] = $fieldData['value'];
			}
			$emailMergeFields[$fieldData['db_column']] = $fieldData['value'];
		}
		
		if ($userId) {
			if (setting('plaintext_extranet_user_passwords')) {
				$userDetails = getUserDetails($userId);
				$emailMergeFields['password'] = $userDetails['password'];
			}
			$emailMergeFields['user_id'] = $userId;
		}
		
		$emailMergeFields['cms_url'] = absCMSDirURL();
		return $emailMergeFields;
	}
	
	protected static function mergeUserData($fields, $userId, $login) {
		$userDetails = getUserDetails($userId);
		$mergeFields = array();
		foreach ($fields as $fieldName => $value) {
			if (isset($userDetails[$fieldName]) && empty($userDetails[$fieldName])) {
				$mergeFields[$fieldName] = $value;
			}
		}
		if ($login) {
			$mergeFields['status'] = 'active';
		}
		saveUser($mergeFields, $userId);
	}
	
	protected static function saveUser($fields, $userId = false) {
		$newId = saveUser($fields, $userId);
		
		if ($userId) {
			sendSignal(
				'eventUserModified',
				array('id' => $userId));
		} else {
			sendSignal(
				'eventUserCreated',
				array('id' => $newId));
		}
		return $newId;
	}
	
	protected static function saveUserCustomData($userCustomFields, $userId, $merge = false) {
		$userDetails = getUserDetails($userId);
		$mergeFields = array();
		// Don't save readonly fields or, if merging, only if no previous field data exists
		foreach ($userCustomFields as $fieldId => $fieldData) {
			if (empty($fieldData['readonly']) && (!$merge || (isset($userDetails[$fieldData['db_column']]) && empty($userDetails[$fieldData['db_column']])))) {
				$mergeFields[$fieldData['db_column']] = ((isset($fieldData['internal_value'])) ? $fieldData['internal_value'] : $fieldData['value']);
			}
		}
		if (!empty($mergeFields)) {
			setRow('users_custom_data', $mergeFields, array('user_id' => $userId));
		}
	}
	
	protected static function saveUserMultiCheckboxData($checkBoxValues, $userId, $merge = false) {
		$dataset = getDatasetDetails('users');
		foreach ($checkBoxValues as $fieldId => $fieldData) {
			if (empty($fieldData['readonly']) && $fieldData['value_type'] != 'unlinked') {
				
				$valuesList = getDatasetFieldLOV($fieldData['user_field_id']);
				$canSave = false;
				
				if ($merge) {
					$valueFound = false;
					// Find if this field has been previously completed
					foreach ($valuesList as $id => $label) {
						if (checkRowExists('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $id, 'linking_id' => $userId))) {
							$valueFound = true;
							break;
						}
					}
					// If no values found, save data
					if (!$valueFound && $fieldData['internal_value']) {
						$canSave = true;
					}
				} else {
					// Delete current saved values
					foreach ($valuesList as $id => $label) {
						deleteRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $id, 'linking_id' => $userId));
					}
					// Save new values
					if ($fieldData['internal_value']) {
						$canSave = true;
					}
				}
				
				if ($canSave) {
					$valuesList = explode(',', $fieldData['internal_value']);
					foreach ($valuesList as $value) {
						insertRow('custom_dataset_values_link', array('dataset_id' => $dataset['id'], 'value_id' => $value, 'linking_id' => $userId));
					}
				}
			}
		}
	}
	
	
	protected static function saveUserFilePickerData($filePickerValues, $userId, $merge = false) {
		$dataset = getDatasetDetails('users');
		foreach ($filePickerValues as $fieldId => $fieldData) {
			if (empty($fieldData['readonly'])) {
				$fileExists = checkRowExists(
					'custom_dataset_files_link', 
					array(
						'dataset_id' => $dataset['id'], 
						'field_id' => $fieldData['user_field_id'], 
						'linking_id' => $userId
					)
				);
				if (!$merge || ($merge && !$fileExists)) {
					
					// Remove other files stored against this field for this user
					deleteRow(
						'custom_dataset_files_link', 
						array(
							'dataset_id' => $dataset['id'], 
							'field_id' => $fieldData['user_field_id'], 
							'linking_id' => $userId
						)
					);
					
					$fileIds = explode(',', $fieldData['internal_value']);
					
					foreach ($fileIds as $fileId) {
						// Add the new file
						setRow(
							'custom_dataset_files_link', 
							array(), 
							array(
								'dataset_id' => $dataset['id'], 
								'field_id' => $fieldData['user_field_id'], 
								'linking_id' => $userId, 
								'file_id' => $fileId)
						);
					}
				}
			}
		}
	}
	
	private static function getFormModuleIds() {
		$ids = array();
		$formModuleClassNames = array('zenario_user_forms', 'zenario_extranet_profile_edit');
		foreach($formModuleClassNames as $moduleClassName) {
			if ($id = getModuleIdByClassName($moduleClassName)) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
	
	private static function getModuleClassNameByInstanceId($id) {
		$sql = '
			SELECT class_name
			FROM '.DB_NAME_PREFIX.'modules m
			INNER JOIN '.DB_NAME_PREFIX.'plugin_instances pi
				ON m.id = pi.module_id
			WHERE pi.id = '.(int)$id;
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		return $row[0];
	}
	
		
	public static function getPageBreakCount($formId) {
		$sql = '
			SELECT COUNT(*)
			FROM '.DB_NAME_PREFIX.'user_form_fields
			WHERE field_type = \'page_break\'
			AND user_form_id = '.(int)$formId;
		$result = sqlSelect($sql);
		$row = sqlFetchRow($result);
		return $row[0];
	}
	
	private function isFormCRMEnabled($formId) {
		if (inc('zenario_crm_form_integration')) {
			$formCRMDetails = getRow(
				ZENARIO_CRM_FORM_INTEGRATION_PREFIX . 'form_crm_data', 
				array('enable_crm_integration'), 
				array('form_id' => $formId)
			);
			if ($formCRMDetails['enable_crm_integration']) {
				return true;
			}
		}
		return false;
	}
	
	
	public static function getMaxOrdinalOfFormFields($formId) {
		$sql = '
			SELECT MAX(ord) from '.DB_NAME_PREFIX. 'user_form_fields
			WHERE user_form_id = '.(int)$formId;
		$result = sqlSelect($sql);
		$ord = sqlFetchRow($result);
		return $ord[0];
	}
	
	protected function getTextFormFields($formId) {
		$formFields = array();
		$sql = "
			SELECT uff.id, cdf.db_column, cdf.label
			FROM ". DB_NAME_PREFIX. "user_form_fields AS uff
			INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE uff.user_form_id = ". (int)$formId. "
				AND cdf.type = 'text'
				AND uff.is_readonly = 0";
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formFields[] = $row;
		}
		return $formFields;
	}
	
	protected function getFormEmailField($formId) {
		// Find the email field on the form here
	}
	
	protected function fillFieldValues(&$fields, &$rec){
		foreach($rec as $k => $v){
			$fields[$k]['value'] = $v;
		}
	}
	
	protected function dynamicallyCreateValueFieldsFromTemplate(&$box, &$fields, &$values) {
		$numValues = (int) $box['key']['numValues'];
		
		for ($i = 1; $i <= $numValues; ++$i) {
			//Add new fields that should be there by copying the template fields
			if (!isset($box['tabs']['lov']['fields']['id'. $i])) {
				$box['tabs']['lov']['fields']['id'. $i] = $box['tabs']['lov']['custom__template_fields']['id'];
				$box['tabs']['lov']['fields']['label'. $i] = $box['tabs']['lov']['custom__template_fields']['label'];
				$box['tabs']['lov']['fields']['delete'. $i] = $box['tabs']['lov']['custom__template_fields']['delete'];
				$box['tabs']['lov']['fields']['nudge_up'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_up'];
				$box['tabs']['lov']['fields']['nudge_down'. $i] = $box['tabs']['lov']['custom__template_fields']['nudge_down'];
				$box['tabs']['lov']['fields']['id'. $i]['ord'] = 10*$i + 1;
				$box['tabs']['lov']['fields']['label'. $i]['ord'] = 10*$i + 2;
				$box['tabs']['lov']['fields']['delete'. $i]['ord'] = 10*$i + 3;
				$box['tabs']['lov']['fields']['nudge_up'. $i]['ord'] = 10*$i + 4;
				$box['tabs']['lov']['fields']['nudge_down'. $i]['ord'] = 10*$i + 5;
			}
			
			$box['tabs']['lov']['fields']['id'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['label'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['delete'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_up'. $i]['hidden'] = false;
			$box['tabs']['lov']['fields']['nudge_down'. $i]['hidden'] = false;
		}
		
		//Don't show the first nudge-up button or the last nudge-down button
		if ($numValues > 0) {
			$box['tabs']['lov']['fields']['nudge_up'. 1]['hidden'] = true;
			$box['tabs']['lov']['fields']['nudge_down'. $numValues]['hidden'] = true;
		}
	}
	
	protected function nudge(&$box, &$fields, &$values, $i, $j) {
		$id = $values['lov/id'. $i];
		$label = $values['lov/label'. $i];
		$values['lov/id'. $i] = $values['lov/id'. $j];
		$values['lov/label'. $i] = $values['lov/label'. $j];
		$values['lov/id'. $j] = $id;
		$values['lov/label'. $j] = $label;
	}
	
	public static function getUnlinkedFieldLOV($formFieldId, $flat = true) {
		$values = array();
		$field = getRowsArray('user_form_fields', 
			array('field_type', 'values_source', 'values_source_filter'), 
			$formFieldId
		);
		$field = $field[$formFieldId];
		
		
		if (chopPrefixOffOfString($field['field_type'], 'centralised_') || $field['field_type'] == 'restatement') {
			$filter = false;
			if (isset($field['values_source_filter'])) {
				$filter = $field['values_source_filter'];
			}
			if ($values = getCentralisedListValues($field['values_source'], $filter)) {
				if (!$flat) {
					cms_core::$dbupCurrentRevision = 0;
					array_walk($values, 'getDatasetFieldLOVFlatArrayToLabeled');
					cms_core::$dbupCurrentRevision = false;
				}
			}
		} else {
			if ($flat) {
				$cols = 'label';
			} else {
				$cols = array('ord', 'label', 'id');
			}
			$values = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $cols, array('form_field_id' => $formFieldId), 'ord');
		}
		return $values;
	}
	
	public static function fieldTypeCanRecordValue($type) {
		return !in_array($type, array('page_break', 'section_description', 'restatement'));
	}
	
	public static function scanTextForProfanities($txt) {
		$profanityCsvFilePath = CMS_ROOT . 'zenario/libraries/not_to_redistribute/profanity-filter/profanities.csv';
		$csvFile = fopen($profanityCsvFilePath,"r");
		
		$profanityArr = array();
		$preparedProfanityWords = array();
		
		while(!feof($csvFile)) {
			$currentProfanity = fgetcsv($csvFile);
			$profanityArr[$currentProfanity[0]] = $currentProfanity[1];
		}
		
		foreach ($profanityArr as $k=>$v) {
			$k = str_replace('-','\\W*',$k);
			$preparedProfanityWords[$k] = $v;
		}
		
		fclose($csvFile);
		
		$profanityCount = 0;
		$txt = strip_tags($txt);
		$txt = html_entity_decode($txt,ENT_QUOTES);
		
		foreach ($preparedProfanityWords as $k=>$v) {
			preg_match_all("#\b".$k."(?:es|s)?\b#si",$txt, $matches, PREG_SET_ORDER);
			$profanityCount += count($matches)*$v;
		}
		
		return $profanityCount;
	}
}
