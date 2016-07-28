<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

class zenario_user_forms_2 extends module_base_class {
	
	private $data = array();
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = false, $ifCookieSet = false);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByUser = true, $clearByFile = false, $clearByModuleData = true);
		
		$formId = $this->setting('user_form');
		$data = static::preloadCustomData();
		$errors = array();
		$nextPage = false;
		$currentPage = false;
		$showSuccessMessage = false;
		
		// If this form has been submited
		if (post('submitted') && ($this->instanceId == post('instanceId'))) {
			$data = $_POST;
			
			// Get the requested page
			if (post('form_page')) {
				$currentPage = (int)post('form_page');
				// Move to next page
				if (post('next')) {
					$nextPage = $currentPage + 1;
				// Move to previous page
				} elseif (post('previous')) {
					$nextPage = $currentPage - 1;
				// Stay on current page (refresh for filter)
				} else {
					$nextPage = $currentPage;
				}
			}
			
			// Don't validate or save if filtering a list of values
			if (!post('filter')) {
				// Only validate if submitting form or moving onto the next page
				if (post('submit_form') || ($nextPage > $currentPage)) {
					$errors = static::validateForm($formId, $this->instanceId, $data, $currentPage);
				}
				
				if (empty($errors)) {
					
					$fullSave = $currentPage;
					if (post('submit_form')) {
						$fullSave = true;
					}
					
					static::saveForm($formId, $data, userId(), $redirectURL, $this->instanceId, $fullSave);
					
					if ($fullSave === true) {
						// Actions after form is complete
						$form = static::getForm($formId);
						if ($form['redirect_after_submission'] && $redirectURL) {
							$this->headerRedirect($redirectURL);
							return true;
						} elseif ($form['show_success_message']) {
							$showSuccessMessage = true;
						}
					}
				} else {
					// Stay on page if errors
					$nextPage = $currentPage;
				}
			}
		} else {
			unset($_SESSION['custom_form_data'][$this->instanceId]);
		}
		
		// $data can be a userId to load saved data or $_POST to load submitted form data
		$this->loadForm($formId, $data, $errors, $showSuccessMessage, $this->setting('display_mode'), $this->setting('display_text'), $nextPage);
		return true;
	}
	
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	
	// An overwritable method to preload custom data for the form fields
	protected static function preloadCustomData() {
		return userId();
	}
	
	
	// An overwritable method to parse an array of requests which are added as hidden inputs on the form
	protected static function getCustomRequests() {
		return false;
	}
	
	
	// An overwritable method to add custom HTML to the buttons area
	// Position can be "first", "center", "last"
	protected static function getCustomButtons($page, $position) {
		return false;
	}
	
	
	// Load merge fields in $this->data to parse to the twig framework and JS
	private function loadForm($formId, $data, $errors, $showSuccessMessage, $displayMode, $buttonText, $page) {
		// Whether to display in a popup window or not
		$buttonJS = false;
		$showButton = false;
		if ($displayMode == 'in_modal_window') {
			// Show form only if errors or button pressed
			if ((post('submitted') && ($this->instanceId == post('instanceId'))) || get('show_user_form')) {
				$this->showInFloatingBox(true, array('escKey' => false, 'overlayClose' => false));
			// Otherwise show button to open the form
			} else {
				$showButton = true;
				$requests = 'show_user_form=1';
				$buttonJS = $this->refreshPluginSlotAnchor($requests, false, false);
			}
		}
		
		// Get form HTML
		$this->data['form_HTML'] = static::getFormHTML($formId, $this->containerId, $this->instanceId, $data, $errors, $this->openForm(), $this->closeForm(), $showSuccessMessage, $showButton, $buttonText, $buttonJS, $page);
		// Init form JS
		$this->callScript('zenario_user_forms_2', 'initForm', $this->containerId);
	}
	
	
	// Get HTML for a full form
	public static function getFormHTML($formId, $containerId, $instanceId, $data, $errors, $openForm, $closeForm, $showSuccessMessage, $showButton, $buttonText, $buttonJS, $page) {
		$form = static::getForm($formId);
		$t = $form['translate_text'];
		$html = '';
		
		if ($showSuccessMessage) {
			if ($form['success_message']) {
				$successMessage = static::fPhrase($form['success_message'], array(), $t);
			} elseif (adminId()) {
				$successMessage = adminPhrase('Your success message will go here when you set it.');
			} else {
				$successMessage = 'Form submission successful!';
			}
			$html .= '<div class="success">' . $successMessage . '</div>';
		
		} elseif ($showButton) {
			$html .= '<div class="user_form_click_here" ' . $buttonJS . '>';
			$html .= '<h3>' . static::fPhrase($buttonText, array(), $t) . '</h3>';
			$html .= '</div>';
			
		} else {
			if ($form['title'] && $form['title_tag']) {
				$html .= '<' . htmlspecialchars($form['title_tag']) . '>';
				$html .= static::fPhrase($form['title'], array(), $t);
				$html .= '</' . htmlspecialchars($form['title_tag']) . '>';
			}
			
			if (isset($errors['global_top'])) {
				$html .= '<div class="form_error global top">' . self::fPhrase($errors['global_top'], array(), $t) . '</div>';
			}
			
			$html .= '<div id="' . $containerId . '_user_form">';
			$html .= $openForm;
			
			// Hidden input to tell when we are filtering values (so no validation or saving if set)
			$html .= '<input type="hidden" class="filter_form" name="filter" value="0"/>';
			// Hidden input to tell whenever the form has been submitted
			$html .= '<input type="hidden" name="submitted" value="1"/>';
			// Add any extra requests
			$extraRequests = static::getCustomRequests();
			if ($extraRequests) {
				foreach ($extraRequests as $name => $value) {
					$html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '"/>';
				}
			}
			
			$html .= static::getFieldsHTML($form, $containerId, $instanceId, $data, false, $errors, $page);
			$html .= $closeForm;
			$html .= '</div>';
		}
		
		return $html;
	}
	
	
	// Get the HTML for a forms fields
	public static function getFieldsHTML($form, $containerId, $instanceId, $data = false, $readonly = false, $errors = array(), $page = false) {
		$html = '';
		$t = $form['translate_text'];
		$dataset = getDatasetDetails('users');
		$fields = static::getFields($form['id']);
		$lastPageBreak = static::isFormMultiPage($form['id']);
		
		$page = $page ? $page : 1;
		// Keeps track of field page in loop
		$tPage = 1;
		$onLastPage = false;
		$pageBreakId = false;
		// Variables to handle wrapper divs
		$currentDivWrapClass = false;
		$wrapDivOpen = false;
		
		if ($lastPageBreak) {
			$html .= '<fieldset id="' . $containerId . '_page_' . $page . '" class="page_' . $page . '">';
		}
		
		$html .= '<div class="form_fields">';
		foreach ($fields as $fieldId => $field) {
			// End of page
			$fieldType = static::getFieldType($field);
			if ($fieldType == 'page_break') {
				$pageBreakId = $fieldId;
				++$tPage;
				if ($fieldId == $lastPageBreak && $tPage == $page) {
					$onLastPage = true;
				}
				if ($tPage > $page) {
					break;
				}
				continue;
			}
			
			// Skip fields not on current page
			if ($page > $tPage) {
				continue;
			}
			
			// Create wrapper divs
			if ($wrapDivOpen && ($currentDivWrapClass != $field['div_wrap_class'])) {
				$wrapDivOpen = false;
				$html .= '</div>';
			}
			if (!$wrapDivOpen && $field['div_wrap_class']) {
				$html .= '<div class="' . htmlspecialchars($field['div_wrap_class']) . '">';
				$wrapDivOpen = true;
			}
			$currentDivWrapClass = $field['div_wrap_class'];
			
			$fieldHTML = static::getFieldHTML($fieldId, $fields, $data, $errors, $containerId, $instanceId, $readonly, $translatePhrases = $form['translate_text'], $dataset);
			$html .= $fieldHTML;
		}
		// Close final wrapper div
		if ($wrapDivOpen) {
			$html .= '</div>';
		}
		$html .= '</div>';
		
		if (isset($errors['global_bottom'])) {
			$html .= '<div class="form_error global bottom">' . self::fPhrase($errors['global_bottom'], array(), $t) . '</div>';
		}
		
		$html .= '<div class="form_buttons">';
		
		$button = static::getCustomButtons($page, 'first');
		if ($button) {
			$html .= $button;
		}
		
		// Previous page button
		if ($lastPageBreak && $page > 1) {
			$pageBreak = $fields[$pageBreakId];
			$text = !$onLastPage && $pageBreak['previous_button_text'] ? $pageBreak['previous_button_text'] : $form['default_previous_button_text'];
			$html .= '<input type="submit" name="previous" value="'. static::fPhrase($text, array(), $t) . '" class="previous"/>';
		}
		
		$button = static::getCustomButtons($page, 'center');
		if ($button) {
			$html .= $button;
		}
		
		// Next page button
		if ($lastPageBreak && !$onLastPage) {
			$pageBreak = $fields[$pageBreakId];
			$text = !$onLastPage && $pageBreak['next_button_text'] ? $pageBreak['next_button_text'] : $form['default_next_button_text'];
			$html .= '<input type="submit" name="next" value="' . self::fPhrase($text, array(), $t) . '" class="next"/>';
		}
		// Final submit button
		if (!$lastPageBreak || $onLastPage) {
			$html .= '<input type="submit" name="submit_form" class="next submit" value="' . static::fPhrase($form['submit_button_text'], array(), $t) . '"/>';
		}
		
		$button = static::getCustomButtons($page, 'last');
		if ($button) {
			$html .= $button;
		}
		
		$html .= '</div>';
		
		if ($lastPageBreak) {
			$html .= '<input type="hidden" name="form_page" value="' . $page . '"/>';
			$html .= '</fieldset>';
		}
		
		return $html;
	}
	
	
	// Get the HTML for a single field
	public static function getFieldHTML($fieldId, $fields, $data = false, $errors = array(), $containerId, $instanceId, $readonly = false, $translatePhrases = false, $dataset = false) {
		$t = $translatePhrases;
		$field = $fields[$fieldId];
		$fieldName = static::getFieldName($fieldId);
		$fieldType = static::getFieldType($field);
		$fieldElementId = $containerId . '__' . $fieldName;
		$ignoreSession = !empty($errors);
		$fieldValue = static::getFieldCurrentValue($field, $data, $dataset, $instanceId, $ignoreSession);
		$readonly = $readonly || $field['is_readonly'];
		
		$html = '';
		$extraClasses = '';
		$errorHTML = '';
		
		if (isset($errors[$fieldId])) {
			$errorHTML = '<div class="form_error">' . self::fPhrase($errors[$fieldId], array(), $t) . '</div>';
		}
		
		// Add the fields label, checkboxes have a label element
		if ($fieldType != 'group' && $fieldType != 'checkbox') {
			$html .= '<div class="field_title">' . self::fPhrase($field['label'], array(), $t) . '</div>';
			$html .= $errorHTML;
		}
		
		// Get HTML for type
		switch ($fieldType) {
			case 'group':
			case 'checkbox':
				$html .= $errorHTML;
				$html .= '<input type="checkbox"';
				if ($fieldValue) {
					$html .= ' checked="checked"';
				}
				if ($readonly) {
					$html .= ' disabled="disabled"';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '"/>';
				$html .= '<label class="field_title" for="' . $fieldElementId . '">' . static::fPhrase($field['label'], array(), $t) . '</label>';
				break;
			case 'url':
			case 'text':
				$autocompleteHTML = '';
				$useTextFieldName = true;
				// Autocomplete options for text fields
				if ($field['autocomplete']) {
					if ($field['values_source']) {
						$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
						$autocompleteFieldLOV = array();
						foreach ($fieldLOV as $listValueId => $listValue) {
							$autocompleteFieldLOV[] = array('v' => $listValueId, 'l' => $listValue);
						}
						// Autocomplete fields with no values are readonly
						if (empty($autocompleteFieldLOV)) {
							$readonly = true;
						}
						
						$autocompleteHTML .= '<div class="autocomplete_json" data-id="' . $fieldId . '" style="display:none;"';
						// Add data attribute for JS events if other fields need to update when this field changes
						$isSourceField = checkRowExists(
							ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
							array('source_field_id' => $fieldId)
						);
						if ($isSourceField) {
							$autocompleteHTML .= ' data-source_field="1"';
						}
						// Add data attribute for JS event to update placeholder if no values in list after click
						$isTargetField = checkRowExists(
							ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
							array('target_field_id' => $fieldId)
						);
						if ($isTargetField && !$filtered && $field['autocomplete_no_filter_placeholder']) {
							$autocompleteHTML .= ' data-auto_placeholder="' . htmlspecialchars(static::fPhrase($field['autocomplete_no_filter_placeholder'], array(), $t)) . '"';
						}
						
						$autocompleteHTML .= '>';
						$autocompleteHTML .= json_encode($autocompleteFieldLOV);
						$autocompleteHTML .= '</div>';
						
						$autocompleteHTML .= '<input type="hidden" name="' . $fieldName  . '" ';
						if (isset($fieldLOV[$fieldValue])) {
							$autocompleteHTML .= ' value="' . $fieldValue . '"';
							$fieldValue = $fieldLOV[$fieldValue];
						} else {
							$fieldValue = '';
						}
						$autocompleteHTML .= '/>';
						// Use hidden field as whats submitted and the text field is only for display
						$useTextFieldName = false;
					}
				}
				
				// Set type to "email" if validation is for an email address
				$fieldType = 'text';
				if ($field['field_validation'] == 'email') {
					$fieldType = 'email';
				}
				$html .= '<input type="' . $fieldType . '"';
				if ($readonly) {
					$html .= ' readonly ';
				}
				if ($useTextFieldName) {
					$html .= ' name="' . $fieldName . '"';
				}
				$html .= ' id="' . $fieldElementId . '"';
				if ($fieldValue !== false) {
					$html .= ' value="' . htmlspecialchars($fieldValue) . '"';
				}
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], array(), $t)) . '"';
				}
				// Set maxlength to 255, or shorter for system field special cases
				$maxlength = 255;
				switch ($field['db_column']) {
					case 'salutation':
						$maxlength = 25;
						break;
					case 'screen_name':
					case 'password':
						$maxlength = 50;
						break;
					case 'first_name':
					case 'last_name':
					case 'email':
						$maxlength = 100;
						break;
				}	
				$html .= ' maxlength="' . $maxlength . '" />';
				$html .= $autocompleteHTML;
				break;
			case 'date':
				$html .= '<input type="text" readonly class="jquery_form_datepicker" ';
				if ($readonly) {
					$html .= ' disabled ';
				}
				$html .= ' id="' . $fieldElementId . '"/>';
				$html .= '<input type="hidden" name="' . $fieldName . '" id="' . $fieldElementId . '__0"';
				if ($fieldValue !== false) {
					$html .= ' value="' . htmlspecialchars($fieldValue) . '"';
				}
				$html .= '/>';
				break;
			case 'textarea':
				$html .= '<textarea rows="4" cols="51"';
				if ($field['placeholder'] !== '' && $field['placeholder'] !== null) {
					$html .= ' placeholder="' . htmlspecialchars(static::fPhrase($field['placeholder'], array(), $t)) . '"';
				}
				if ($readonly) {
					$html .= ' readonly ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '">';
				if ($fieldValue !== false) {
					$html .= htmlspecialchars($fieldValue);
				}
				$html .= '</textarea>';
				break;
			case 'section_description':
				$html .= '<div class="description"><p>' . static::fPhrase($field['description'], array(), $t) . '</p></div>';
				break;
			case 'radios':
				$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
				foreach ($fieldLOV as $value => $label) {
					$radioElementId = $fieldElementId . '_' . $value;
					$html .= '<div class="field_radio">';
					$html .= '<input type="radio"  value="' . htmlspecialchars($value) . '"';
					if ($value == $fieldValue) {
						$html .= ' checked="checked" ';
					}
					if ($readonly) {
						$html .= ' disabled ';
					}
					$html .= ' name="'. $fieldName. '" id="' . $radioElementId . '"/>';
					$html .= '<label for="' . $radioElementId . '">' . static::fPhrase($label, array(), $t) . '</label>';
					$html .= '</div>'; 
				}
				if ($readonly && !empty($fieldValue)) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="'.htmlspecialchars($fieldValue).'" />';
				}
				break;
			case 'centralised_radios':
				$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
				$isCountryList = static::isCentralisedListOfCountries($field);
				$radioCount = 0;
				foreach ($fieldLOV as $value => $label) {
					$radioElementId = $fieldElementId . '_' . ++$radioCount;
					$html .= '<div class="field_radio">';
					$html .= '<input type="radio"  value="' . htmlspecialchars($value) . '"';
					if ($value == $fieldValue) {
						$html .= ' checked="checked" ';
					}
					if ($readonly) {
						$html .= ' disabled ';
					}
					$html .= ' name="'. $fieldName. '" id="' . $radioElementId . '"/>';
					$html .= '<label for="' . $radioElementId . '">';
					// Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= phrase('_COUNTRY_NAME_' . $value, array(), 'zenario_country_manager');
					} else {
						$html .= static::fPhrase($label, array(), $t);
					}
					$html .= '</label>';
					$html .= '</div>'; 
				}
				if ($readonly && !empty($fieldValue)) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="'.htmlspecialchars($fieldValue).'" />';
				}
				break;
			case 'select':
				$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '">';
				$html .= '<option value="">' . self::fPhrase('-- Select --', array(), $t) . '</option>';
				foreach ($fieldLOV as $value => $label) {
					$html .= '<option value="' . htmlspecialchars($value) . '"';
					if ($value == $fieldValue) {
						$html .= ' selected="selected" ';
					}
					$html .= '>' . self::fPhrase($label, array(), $t) . '</option>';
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($fieldValue) . '"/>';
				}
				break;
			case 'centralised_select':
				$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
				$isCountryList = static::isCentralisedListOfCountries($field);
				$html .= '<select ';
				if ($readonly) {
					$html .= 'disabled ';
				}
				$html .= ' name="' . $fieldName . '" id="' . $fieldElementId . '"';
				// Add class for JS events if other fields need to update when this field changes
				$isSourceField = checkRowExists(
					ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
					array('source_field_id' => $fieldId)
				);
				if ($isSourceField) {
					$html .= ' class="source_field"';
				}
				$html .= '>';
				$html .= '<option value="">' . self::fPhrase('-- Select --', array(), $t) . '</option>';
				foreach ($fieldLOV as $value => $label) {
					$html .= '<option value="' . htmlspecialchars($value) . '"';
					if ($value == $fieldValue) {
						$html .= ' selected="selected" ';
					}
					$html .= '>';
					// Make sure to use system country phrases if showing a list of countries
					if ($isCountryList && $t) {
						$html .= phrase('_COUNTRY_NAME_' . $value, array(), 'zenario_country_manager');
					} else {
						$html .= static::fPhrase($label, array(), $t);
					}
					$html .= '</option>';
				}
				$html .= '</select>';
				if ($readonly) {
					$html .= '<input type="hidden" name="' . $fieldName . '" value="' . htmlspecialchars($fieldValue) . '"/>';
				}
				break;
			case 'checkboxes':
			case 'attachment':
			case 'file_picker':
				//TODO
				break;
		}
		if (!empty($field['note_to_user'])) {
			$html .= '<div class="note_to_user">'. static::fPhrase($field['note_to_user'], array(), $t) .'</div>';
		}
		
		
		
		
		// Field containing div open
		$containerHTML = '<div id="' . $containerId . '_field_' . (int)$fieldId . '" data-id="' . (int)$fieldId . '" ';
		if ($field['visibility'] == 'visible_on_condition') {
			$containerHTML .= ' data-cfieldid="' . $field['visible_condition_field_id'] . '"';
			$containerHTML .= ' data-cfieldvalue="' . $field['visible_condition_field_value'] . '"';
		}
		// Check if field is hidden
		if (static::isFieldHidden($field, $fields, $data, $dataset, $instanceId)) {
			$containerHTML .= ' style="display:none;"';
		}
		// Containing div css classes
		$containerHTML .= 'class="form_field field_' . $fieldType . ' ' . htmlspecialchars($field['css_classes']);
		if ($readonly) {
			$containerHTML .= ' readonly';
		}
		if ($field['visibility'] == 'visible_on_condition') {
			$containerHTML .= ' visible_on_condition';
		}
		$containerHTML .= $extraClasses;
		$containerHTML .= '">';
		
		$html = $containerHTML . $html . '</div>';
		return $html;
	}
	
	
	// Check if a centralised list is a list of countries
	public static function isCentralisedListOfCountries($field) {
		$source = $field['dataset_field_id'] ? $field['dataset_values_source'] : $field['values_source'];
		return ($source == 'zenario_country_manager::getActiveCountries');
	}
	
	
	// Get the ID of the last page break on a form
	public static function isFormMultiPage($formId) {
		$sql = '
			SELECT id
			FROM ' . DB_NAME_PREFIX . 'user_form_fields
			WHERE user_form_id = ' . (int)$formId . '
			AND field_type = "page_break"
			ORDER BY ord DESC
			LIMIT 1';
		$result = sqlSelect($sql);
		$field = sqlFetchAssoc($result);
		if ($field) {
			return $field['id'];
		}
		return false;
	}
	
	
	// Get a fields current list of values
	public static function getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, &$filtered) {
		$field = $fields[$fieldId];
		$values = array();
		$fieldType = static::getFieldType($field);
		if (in_array($fieldType, array('radios', 'centralised_radios', 'select', 'checkboxes'))) {
			if ($field['dataset_field_id']) {
				$values = getDatasetFieldLOV($field['dataset_field_id']);
			} else {
				$values = static::getUnlinkedFieldLOV($field['id']);
			}
		// Where field lists can depend on another fields value (text fields can have autocomplete lists)
		} elseif ($fieldType == 'centralised_select' || $fieldType == 'text') {
			// Check if this field has a source field to filter the list
			$filter = false;
			$sourceFieldId = getRow(
				ZENARIO_USER_FORMS_PREFIX . 'form_field_update_link', 
				'source_field_id', 
				array('target_field_id' => $field['id'])
			);
			// Get source fields current value for filtering
			if ($sourceFieldId && isset($fields[$sourceFieldId])) {
				$filter = static::getFieldCurrentValue($fields[$sourceFieldId], $data, $dataset, $instanceId);
				if ($filter) {
					$filtered = true;
				}
			}
			// Handle the case where a static filter is set but the field is also being dynamically filtered by another field
			$showValues = true;
			$datasetField = false;
			if ($field['dataset_field_id']) {
				$datasetField = getDatasetFieldDetails($field['dataset_field_id']);
				if ($filtered && $datasetField['values_source_filter'] && $filter != $datasetField['values_source_filter']) {
					$showValues = false;
				}
			} else {
				if ($filtered && $field['values_source_filter'] && $filter != $field['values_source_filter']) {
					$showValues = false;
				}
			}
			// If this field is filtered by another field but the value of that field is empty, show no values
			if ($showValues && (!$sourceFieldId || $filter)) {
				if ($field['dataset_field_id']) {
					$values = getDatasetFieldLOV($datasetField, true, $filter);
				} else {
					$values = static::getUnlinkedFieldLOV($field, true, $filter);
				}
			}
		}
		return $values;
	}
	
	
	//TODO rewrite this!
	public static function getUnlinkedFieldLOV($field, $flat = true, $filter = false) {
		if (!is_array($field)) {
			$field = getRow('user_form_fields', 
				array('id', 'field_type', 'values_source', 'values_source_filter'), 
				$field
			);
		}
		$values = array();
		if (chopPrefixOffOfString($field['field_type'], 'centralised_') || $field['field_type'] == 'restatement' || $field['field_type'] == 'text') {
			if (!empty($field['values_source_filter'])) {
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
			$values = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $cols, array('form_field_id' => $field['id']), 'ord');
		}
		return $values;
	}
	
	
	// Get a fields current value
	public static function getFieldCurrentValue($field, $data = false, $dataset = false, $instanceId = false, $ignoreSession = false) {
		$value = false;
		$fieldType = static::getFieldType($field);
		$fieldName = static::getFieldName($field['id']);
		
		// If value is array, the form has been submitted, load from this data
		if (is_array($data)) {
			if ($field['type'] == 'checkboxes') {
				$fields = array($field['id'] => $field);
				$fieldLOV = static::getFieldCurrentLOV($field['id'], $fields, $data, $dataset, $instanceId, $filtered);
				foreach ($fieldLOV as $value => $label) {
					//TODO
				}
			} else {
				if (isset($data[$fieldName])) {
					$value = $data[$fieldName];
				} elseif (!$ignoreSession && isset($_SESSION['custom_form_data'][$instanceId][$field['id']])) {
					$value = $_SESSION['custom_form_data'][$instanceId][$field['id']];
				}
			}
		
		// If value is a number, load the value stored against the user
		} elseif (is_numeric($data) && $field['dataset_field_id']) {
			$userId = $data;
			$value = datasetFieldValue($dataset, $field['dataset_field_id'], $userId);
			
		// If field can have a default value load it
		} elseif (in_array($fieldType, array('radios', 'centralised_radios', 'select', 'centralised_select', 'text', 'textarea', 'checkbox', 'group'))) {
			// Static default value
			if ($field['default_value'] !== null) {
				$value = $field['default_value'];
			// Get value with static method
			} elseif (!empty($field['default_value_class_name']) && !empty($field['default_value_method_name'])) {
				inc($field['default_value_class_name']);
				$value = call_user_func(
					array(
						$field['default_value_class_name'], 
						$field['default_value_method_name']
					),
					$field['default_value_param_1'], 
					$field['default_value_param_2']
				);
			}
		}
		
		return $value;
	}
	
	
	// Check if a field if hidden or not
	public static function isFieldHidden($field, $fields, $data, $dataset, $instanceId) {
		if ($field['visibility'] == 'hidden'){
			return true;
		} elseif ($field['visibility'] == 'visible_on_condition'
			&& !empty($field['visible_condition_field_id'])
			&& isset($fields[$field['visible_condition_field_id']])
		) {
			$cField = $fields[$field['visible_condition_field_id']];
			$cFieldName = static::getFieldName($cField['id']);
			$cFieldType = static::getFieldType($cField);
			
			$cFieldValue = static::getFieldCurrentValue($cField, $data, $dataset, $instanceId);
			
			if ($cFieldType == 'checkbox' || $cFieldType == 'group') {
				if ($field['visible_condition_field_value'] != $cFieldValue) {
					return true;
				}
			} elseif ($cFieldType == 'radios' || $cFieldType == 'select' || $cFieldType == 'centralised_radios' || $cFieldType == 'centralised_select') {
				if ($field['visible_condition_field_value'] != $cFieldValue) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	// Get the name of the field element on the form
	public static function getFieldName($fieldId) {
		return 'field_' . $fieldId;
	}
	
	
	// Get a fields type (comes from either form or dataset field)
	public static function getFieldType($field) {
		return (!empty($field['type']) ? $field['type'] : $field['field_type']);
	}
	
	
	// Get the fields of a form
	public static function getFields($formId, $fieldId = false) {
		$fields = array();
		$sql = '
			SELECT 
				uff.id, 
				uff.user_form_id,
				uff.ord, 
				uff.is_readonly, 
				uff.is_required,
				uff.mandatory_condition_field_id,
				uff.mandatory_condition_field_value,
				uff.visibility,
				uff.visible_condition_field_id,
				uff.visible_condition_field_value,
				uff.label,
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
				uff.validation_error_message AS field_validation_error_message,
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
				
				cdf.id AS dataset_field_id, 
				cdf.type, 
				cdf.db_column, 
				cdf.label AS dataset_field_label,
				cdf.default_label,
				cdf.is_system_field, 
				cdf.dataset_id, 
				cdf.validation AS dataset_field_validation, 
				cdf.validation_message AS dataset_field_validation_message,
				cdf.multiple_select,
				cdf.store_file,
				cdf.extensions,
				cdf.values_source AS dataset_values_source
			FROM ' . DB_NAME_PREFIX . 'user_forms AS uf
			INNER JOIN ' . DB_NAME_PREFIX . 'user_form_fields AS uff
				ON uf.id = uff.user_form_id
			LEFT JOIN ' . DB_NAME_PREFIX . 'custom_dataset_fields AS cdf
				ON uff.user_field_id = cdf.id
			WHERE TRUE';
		if ($formId) {
			$sql .= '
				AND uff.user_form_id = ' . (int)$formId;
		}
		if ($fieldId) {
			$sql .= '
				AND uff.id = ' . (int)$fieldId;
		}
		$sql .= '
			ORDER BY uff.ord';
		
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$fields[$row['id']] = $row;
		}
		return $fields;
	}
	
	
	// Get a forms details
	public static function getForm($formId) {
		$form = getRow(
			'user_forms', 
			array(
				'id',
				'type',
				'name',
				'title',
				'title_tag',
				'send_email_to_user',
				'user_email_template',
				'send_email_to_admin',
				'admin_email_use_template',
				'admin_email_addresses',
				'admin_email_template',
				'reply_to',
				'reply_to_email_field',
				'reply_to_first_name',
				'reply_to_last_name',
				'save_data',
				'save_record',
				'send_signal',
				'redirect_after_submission',
				'redirect_location',
				'user_status',
				'log_user_in',
				'log_user_in_cookie' ,
				'add_user_to_group',
				'create_another_form_submission_record',
				'use_captcha',
				'captcha_type',
				'extranet_users_use_captcha',
				'profanity_filter_text',
				'user_duplicate_email_action',
				'duplicate_email_address_error_message',
				'translate_text', 
				'default_next_button_text', 
				'default_previous_button_text',
				'submit_button_text',
				'show_success_message',
				'success_message',
				
			), 
			$formId
		);
		return $form;
	}
	
	
	public static function fPhrase($phrase, $mergeFields, $translate) {
		if ($translate) {
			return phrase($phrase, $mergeFields, 'zenario_user_forms');
		}
		return $phrase;
	}
	
	
	// Validate a form submission
	public static function validateForm($formId, $instanceId, $data, $page = false) {
		$errors = array();
		$dataset = getDatasetDetails('users');
		$form = static::getForm($formId);
		$fields = static::getFields($formId);
		
		// Validate a single page
		if ($page) {
			$pageFields = static::filterFormFieldsByPage($fields, $page);
		} else {
			$pageFields = $fields;
		}
		
		foreach ($pageFields as $fieldId => $field) {
			$error = static::getFieldErrorMessage($form, $fieldId, $fields, $data, $dataset, $instanceId);
			if ($error) {
				$errors[$fieldId] = $error;
			}
		}
		
		$customErrors = static::getCustomErrors($fields, $pageFields, $data, $dataset, $instanceId);
		if (is_array($customErrors)) {
			foreach ($customErrors as $fieldId => $error) {
				if (!isset($errors[$fieldId])) {
					$errors[$fieldId] = $error;
				}
			}
		}
		return $errors;
	}
	
	
	// An overwritable method for custom validation on form fields
	protected static function getCustomErrors($fields, $pageFields, $data, $dataset, $instanceId) {
		return false;
	}
	
	
	public static function filterFormFieldsByPage($fields, $page) {
		$currentPageNo = 1;
		foreach ($fields as $fieldId => $field) {
			if ($page != $currentPageNo) {
				unset($fields[$fieldId]);
			}
			if ($field['field_type'] == 'page_break') {
				unset($fields[$fieldId]);
				$currentPageNo++;
			}
		}
		return $fields;
	}
	
	
	// Check if a field is valid and return any error message
	public static function getFieldErrorMessage($form, $fieldId, $fields, $data, $dataset, $instanceId) {
		$field = $fields[$fieldId];
		$fieldType = static::getFieldType($field);
		$fieldValue = static::getFieldCurrentValue($field, $data, $dataset, $instanceId);
		$t = $form['translate_text'];
		
		// If this field is conditionally mandatory, see if the condition is met
		if ($field['mandatory_condition_field_id']) {
			$requiredFieldId = $field['mandatory_condition_field_id'];
			$requiredField = $fields[$requiredFieldId];
			$requiredFieldName = static::getFieldName($requiredField);
			$requiredFieldType = static::getFieldType($requiredField);
			$requiredFieldValue = static::getFieldCurrentValue($requiredField, $data, $dataset, $instanceId);
			
			switch($requiredFieldType) {
				case 'checkbox':
					if ($field['mandatory_condition_field_value'] == 1) {
						if ($requiredFieldValue) {
							$field['is_required'] = true;
						}
					} elseif ($field['mandatory_condition_field_value'] == 0) {
						if (!$requiredFieldValue) {
							$field['is_required'] = true;
						}
					}
					break;
				case 'radios':
				case 'select':
				case 'centralised_radios':
				case 'centralised_select':
					if (($requiredFieldValue === $field['mandatory_condition_field_value']) 
						|| (!$field['mandatory_condition_field_value'] && $requiredFieldValue !== '')
					) {
						$field['is_required'] = true;
					}
					break;
			}
		}
		
		// Check if field is required but has no data
		if ($field['is_required']) {
			switch ($fieldType) {
				case 'group':
				case 'checkbox':
				case 'radios':
				case 'centralised_radios':
				case 'centralised_select':
				case 'select':
					if (!$fieldValue) {
						return static::fPhrase($field['required_error_message'], array(), $t);
					}
					break;
				case 'text':
				case 'date':
				case 'textarea':
				case 'url':
					if ($fieldValue === null || $fieldValue === '') {
						return static::fPhrase($field['required_error_message'], array(), $t);
					}
					break;
				case 'attachment':
				case 'file_picker':
				case 'checkboxes':
					//TODO
					break;
			}
		}
		
		// Check if user is allowed more than one submission
		if ($field['db_column'] == 'email' 
			&& $form['save_data']
			&& $form['user_duplicate_email_action'] == 'stop'
			&& $form['duplicate_email_address_error_message']
		) {
			$userId = getRow('users', 'id', array('email' => $fieldValue));
			$responseExists = checkRowExists(
				ZENARIO_USER_FORMS_PREFIX. 'user_response', 
				array('user_id' => $userId, 'form_id' => $form['id'])
			);
			
			if ($responseExists) {
				return static::fPhrase($form['duplicate_email_address_error_message'], array(), $t);
			}
		}
		
		// Text field validation
		if ($fieldType == 'text' && $field['field_validation'] && $fieldValue !== '') {
			switch ($field['field_validation']) {
				case 'email':
					if (!validateEmailAddress($fieldValue)) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'URL':
					if (($fieldValue !== '') && filter_var($fieldValue, FILTER_VALIDATE_URL) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'integer':
					if (($fieldValue !== '') && filter_var($fieldValue, FILTER_VALIDATE_INT) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'number':
					if (($fieldValue !== '') && !is_numeric($fieldValue)) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
				case 'floating_point':
					if (($fieldValue !== '') && filter_var($fieldValue, FILTER_VALIDATE_FLOAT) === false) {
						return static::fPhrase($field['field_validation_error_message'], array(), $t);
					}
					break;
			}
		}
		
		// Dataset field validation
		if ($field['dataset_field_id'] && $field['dataset_field_validation'] && $fieldValue !== '') {
			switch ($field['dataset_field_validation']) {
				case 'email':
					if (!validateEmailAddress($fieldValue)) {
						return static::fPhrase('Please enter a valid email address', array(), $t);
					}
					break;
				case 'emails':
					if (!validateEmailAddress($fieldValue, true)) {
						return static::fPhrase('Please enter a valid list of email addresses', array(), $t);
					}
					break;
				case 'no_spaces':
					if (preg_replace('/\S/', '', $fieldValue)) {
						return static::fPhrase('This field cannot contain spaces', array(), $t);
					}
					break;
				case 'numeric':
					if (!is_numeric($fieldValue)) {
						return static::fPhrase('This field must be numeric', array(), $t);
					}
					break;
				case 'screen_name':
					if (empty($fieldValue)) {
						$validationMessage = static::fPhrase('Please enter a screen name', array(), $t);
					} elseif (!validateScreenName($fieldValue)) {
						$validationMessage = static::fPhrase('Please enter a valid screen name', array(), $t);
					} elseif ((userId() && checkRowExists('users', array('screen_name' => $fieldValue, 'id' => array('!' => userId())))) 
						|| (!userId() && checkRowExists('users', array('screen_name' => $fieldValue)))
					) {
						return static::fPhrase('The screen name you entered is in use', array(), $t);
					}
					break;
			}
		}
		return false;
	}
	
	
	// Save a form submission TODO rewrite this!
	public static function saveForm($formId, $data, $userId, &$redirectURL, $instanceId, $fullSave = true) {
		$dataset = getDatasetDetails('users');
		$formFields = static::getFields($formId);
		
		if ($fullSave !== true) {
			$page = $fullSave;
			$pageFields = static::filterFormFieldsByPage($formFields, $page);
			static::tempSaveFormFields($pageFields, $data, $dataset, $instanceId);
			return true;
		}
		
		$formProperties = static::getForm($formId);
		
		$userSystemFields = array();
		$userCustomFields = array();
		$unlinkedFields = array();
		
		$checkBoxValues = array();
		$filePickerValues = array();
		
		$fieldIdValueLink = array();
		
		static::getFormSaveData($formFields, $data, $dataset, $instanceId, $fileIdValueLink, $userSystemFields, $userCustomFields, $unlinkedFields, $checkBoxValues, $filePickerValues);
		
		// Save data against user record
		if ($formProperties['save_data'] && inc('zenario_extranet')) {
			$fields = array();
			foreach ($userSystemFields as $fieldData) {
				if (empty($fieldData['readonly'])) {
					$fields[$fieldData['db_column']] = $fieldData['value'];
				}
			}
			
			// Try to save data if email field is on form
			if (isset($fields['email']) || $userId) { 
				// Duplicate email found
				if (($userId || ($userId = getRow('users', 'id', array('email' => $fields['email'])))) 
					&& ($formProperties['type'] != 'registration')
				) {
					switch ($formProperties['user_duplicate_email_action']) {
						// Don’t change previously populated fields
						case 'merge': 
							$fields['modified_date'] = now();
							static::mergeUserData($fields, $userId, $formProperties['log_user_in']);
							static::saveUserCustomData($userCustomFields, $userId, true);
							static::saveUserMultiCheckboxData($checkBoxValues, $userId, true);
							static::saveUserFilePickerData($filePickerValues, $userId, true);
							break;
						// Change previously populated fields
						case 'overwrite': 
							$fields['modified_date'] = now();
							$userId = static::saveUser($fields, $userId);
							static::saveUserCustomData($userCustomFields, $userId);
							static::saveUserMultiCheckboxData($checkBoxValues, $userId);
							static::saveUserFilePickerData($filePickerValues, $userId);
							break;
						// Don’t update any fields
						case 'ignore': 
							break;
					}
				// No duplicate email found
				} elseif (!empty($fields['email']) && validateEmailAddress($fields['email'])) {
					// Set new user fields
					$fields['status'] = $formProperties['user_status'];
					$fields['password'] = createPassword();
					$fields['ip'] = visitorIP();
					if (!empty($fields['screen_name'])) {
						$fields['screen_name_confirmed'] = true;
					}
					
					// Custom logic for creating users from a registration form
					if ($formProperties['type'] == 'registration') {
						$fields['status'] = 'active';
						// Email verification
						if (!empty($registrationOptions['initial_email_address_status'])) {
							if ($registrationOptions['initial_email_address_status'] == 'not_verified' 
								&& isset($registrationOptions['initial_account_status'])
							) {
								if ($registrationOptions['initial_account_status'] == 'pending') {
									$fields['status'] = 'pending';
								} else {
									$fields['status'] = 'contact';
								}
								$fields['email_verified'] = 0;
							} else {
								$fields['email_verified'] = 1;
							}
						}
					}
					
					// Create new user
					$userId = static::saveUser($fields);
					
					// Save new user custom data
					static::saveUserCustomData($userCustomFields, $userId);
					static::saveUserMultiCheckboxData($checkBoxValues, $userId);
					static::saveUserFilePickerData($filePickerValues, $userId);
					//TODO Check if file picker save is needed here!
				}
				if ($userId && ($formProperties['type'] != 'registration')) {
					
					addUserToGroup($userId, $formProperties['add_user_to_group']);
					// Log user in
					if ($formProperties['log_user_in']) {
						
						$user = logUserIn($userId);
						
						if($formProperties['log_user_in_cookie'] && canSetCookie()) {
							setCookieOnCookieDomain('LOG_ME_IN_COOKIE', $user['login_hash']);
						}
					}
				}
			}
		}
		
		// Save a record of the submission
		$user_response_id = false;
		if ($formProperties['save_record']) {
			// Save record only if there is no duplicate response by the identified user
			// Or if there is a response but the appropriate options have been checked,
			// Or no user could be found from the data
			if (!$userId 
				|| !$formProperties['save_data']
				|| !checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) 
				|| (checkRowExists(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('user_id' => $userId)) 
					&& $formProperties['create_another_form_submission_record']
				)
			) {
				$values = $userSystemFields + $userCustomFields + $unlinkedFields + $filePickerValues + $checkBoxValues;
				$user_response_id = static::createFormResponse($userId, $formId, $values);
			}
		}
		
		// Send emails
		// Profanity check
		$profanityFilterEnabled = setting('zenario_user_forms_set_profanity_filter');
		$profanityToleranceLevel = setting('zenario_user_forms_set_profanity_tolerence');
		
		if ($formProperties['profanity_filter_text']) {
			
			$profanityValuesToCheck = $userSystemFields + $userCustomFields + $unlinkedFields;
			$wordsCount = 0;
			$allValuesFromText = "";
			
			foreach ($profanityValuesToCheck as $text) {
				$wordsCount = $wordsCount + str_word_count($text['value']);
				$isSpace = substr($text['value'], -1);
				
				if ($isSpace != " ") {
					$allValuesFromText .= $text['value'] . " ";
				} else {
					$allValuesFromText .= $text['value'];
				}
			}
			
			$profanityRating = zenario_user_forms::scanTextForProfanities($allValuesFromText);
		}

		if (!$formProperties['profanity_filter_text'] || ($profanityRating < $profanityToleranceLevel)) {
			
			$sendEmailToUser = ($formProperties['send_email_to_user'] && $formProperties['user_email_template'] && isset($data['email']));
			$sendEmailToAdmin = ($formProperties['send_email_to_admin'] && $formProperties['admin_email_addresses']);
			$values = array();
			$userEmailMergeFields = 
			$adminEmailMergeFields = false;
			
			if ($sendEmailToUser || $sendEmailToAdmin) {
				$values = $userSystemFields + $userCustomFields + $checkBoxValues + $unlinkedFields;
			}
			
			// Send an email to the user
			if ($sendEmailToUser) {
				
				// Get merge fields
				$userEmailMergeFields = static::getTemplateEmailMergeFields($values, $userId);
				
				// Send email
				zenario_email_template_manager::sendEmailsUsingTemplate($data['email'], $formProperties['user_email_template'], $userEmailMergeFields, array());
			}
		
			// Send an email to administrators
			if ($sendEmailToAdmin) {
				
				// Get merge fields
				$adminEmailMergeFields = static::getTemplateEmailMergeFields($values, $userId, true);
				
				// Set reply to address and name
				$replyToEmail = false;
				$replyToName = false;
				if ($formProperties['reply_to'] && $formProperties['reply_to_email_field']) {
					if (isset($data[$formProperties['reply_to_email_field']])) {
						$replyToEmail = $data[$formProperties['reply_to_email_field']];
						$replyToName = '';
						if (isset($data[$formProperties['reply_to_first_name']])) {
							$replyToName .= $data[$formProperties['reply_to_first_name']];
						}
						if (isset($data[$formProperties['reply_to_last_name']])) {
							$replyToName .= ' '.$data[$formProperties['reply_to_last_name']];
						}
						if (!$replyToName) {
							$replyToName = $replyToEmail;
						}
					}
				}
		
				// Send email
				if ($formProperties['admin_email_use_template'] && $formProperties['admin_email_template']) {
					zenario_email_template_manager::sendEmailsUsingTemplate(
						$formProperties['admin_email_addresses'],
						$formProperties['admin_email_template'],
						$adminEmailMergeFields,
						$attachments,
						array(),
						false,
						$replyToEmail,
						$replyToName
					);
				} else {
					$emailValues = array();
					foreach ($values as $fieldId => $fieldData) {
						if (isset($fieldData['attachment'])) {
							$fieldData['value'] = absCMSDirURL() . 'zenario/file.php?adminDownload=1&id=' . $fieldData['internal_value'];
						}
						if (!empty($fieldData['type']) && ($fieldData['type'] == 'textarea') && $fieldData['value']) {
							$fieldData['value'] = '<br/>' . nl2br($fieldData['value']);
						}
						$emailValues[$fieldData['ord']] = array($formFields[$fieldId]['name'], $fieldData['value']);
					}
					ksort($emailValues);
					
					$formName = trim($formProperties['name']);
					$formName = empty($formName) ? phrase('[blank name]', array(), 'zenario_user_forms') : $formProperties['name'];
					$body =
						'<p>Dear admin,</p>
						<p>The form "'.$formName.'" was submitted with the following data:</p>';
					
					
					// Get menu path of current page
					$menuNodeString = '';
					if ($formProperties['send_email_to_admin'] && !$formProperties['admin_email_use_template']) {
						$currentMenuNode = getMenuItemFromContent(cms_core::$cID, cms_core::$cType);
						if ($currentMenuNode && isset($currentMenuNode['mID']) && !empty($currentMenuNode['mID'])) {
							$nodes = static::drawMenu($currentMenuNode['mID'], cms_core::$cID, cms_core::$cType);
							for ($i = count($nodes) - 1; $i >= 0; $i--) {
								$menuNodeString .= $nodes[$i].' ';
								if ($i > 0) {
									$menuNodeString .= '&#187; ';
								}
							}
						}
					}
					if ($menuNodeString) {
						$body .= '<p>Page submitted from: '. $menuNodeString .'</p>';
					}
					
					foreach ($emailValues as $ordinal => $value) {
						$body .= '<p>'.trim($value[0], " \t\n\r\0\x0B:").': '.$value[1].'</p>';
					}
					
					$url = linkToItem(cms_core::$cID, cms_core::$cType, true, '', false, false, true);
					if (!$url) {
						$url = absCMSDirURL();
					}
			
					$body .= '<p>This is an auto-generated email from '.$url.'</p>';
					$recipients = $formProperties['admin_email_addresses'];
					$subject = phrase('New form submission for: [[name]]', array('name' => $formName), 'zenario_user_forms');
					$addressFrom = setting('email_address_from');
					$nameFrom = setting('email_name_from');
		
					zenario_email_template_manager::sendEmails(
						$recipients,
						$subject,
						$addressFrom,
						$nameFrom,
						$body,
						array(),
						$attachments,
						array(),
						0,
						false,
						$replyToEmail,
						$replyToName
					);
				}
			}
		} else {
			// Update if profanity filter was set in responses
			if($profanityFilterEnabled) {
				updateRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', array('blocked_by_profanity_filter' => 1), array('id' => $user_response_id));
			}
		}
	
		//Set default values for this form submission for profanity filter
		if ($formProperties['profanity_filter_text']) {
			updateRow(ZENARIO_USER_FORMS_PREFIX. 'user_response', 
				array(
					'profanity_filter_score' => $profanityRating, 
					'profanity_tolerance_limit' => $profanityToleranceLevel
				), 
				array('id' => $user_response_id)
			);
		}
		
		// Send a signal if specified
		if ($formProperties['send_signal']) {
			$formProperties['user_form_id'] = $formId;
			$values = $userSystemFields + $userCustomFields + $checkBoxValues + $unlinkedFields;
			$formattedData = static::getTemplateEmailMergeFields($values, $userId);
			$params = array(
				'data' => $formattedData, 
				'rawData' => $data, 
				'formProperties' => $formProperties, 
				'fieldIdValueLink' => $fieldIdValueLink);
			if ($user_response_id) {
				$params['responseId'] = $user_response_id;
			}
			sendSignal('eventUserFormSubmitted', $params);
		} 
		// Redirect to page if speficied
		if ($formProperties['redirect_after_submission'] && $formProperties['redirect_location']) {
			$cID = $cType = false;
			getCIDAndCTypeFromTagId($cID, $cType, $formProperties['redirect_location']);
			langEquivalentItem($cID, $cType);
			$redirectURL = linkToItem($cID, $cType);
		}
		
		unset($_SESSION['custom_form_data'][$instanceId]);
		return $userId;
	}
	
	
	public static function getFormSaveData($fields, $data, $dataset, $instanceId, &$fileIdValueLink, &$userSystemFields, &$userCustomFields, &$unlinkedFields, &$checkBoxValues, &$filePickerValues) {
		foreach ($fields as $fieldId => $field) {
			$userFieldId = $field['dataset_field_id'];
			$fieldType = static::getFieldType($field);
			$fieldName = static::getFieldColumn($field);
			$fieldValue = static::getFieldCurrentValue($field, $data, $dataset, $instanceId);
			
			if ($field['is_system_field']){
				$valueType = 'system';
				$values = &$userSystemFields;
			} elseif ($userFieldId) {
				$valueType = 'custom';
				$values = &$userCustomFields;
			} else {
				$valueType = 'unlinked';
				$values = &$unlinkedFields;
			}
			
			// Array to store link between input name and file picker value
			$filePickerValueLink = array();
			
			switch ($fieldType){
				case 'group':
				case 'checkbox':
					if (!empty($fieldValue)) {
						$checked = 1;
						$eng = adminPhrase('Yes');
					} else {
						$checked = 0;
						$eng = adminPhrase('No');
					}
					
					$values[$fieldId] = array('value' => $eng, 'internal_value' => $checked);
					$fieldIdValueLink[$fieldId] = $checked;
					break;
				case 'checkboxes':
					// Store checkbox values to save in record
					$checkBoxValues[$fieldId] = array(
						'internal_value' => $fieldValue['ids'], 
						'value' => $fieldValue['labels'], 
						'ord' => $field['ord'], 
						'db_column' => $fieldName,
						'value_type' => $valueType,
						'user_field_id' => $userFieldId,
						'type' => $fieldType,
						'readonly' => $field['is_readonly']
					);
					break;
				case 'date':
					$date = '';
					if ($fieldValue) {
						$date = $fieldValue;
					}
					$values[$fieldId] = array('value' => $date);
					$fieldIdValueLink[$fieldId] = $date;
					break;
				case 'radios':
				case 'select':
				case 'centralised_radios':
				case 'centralised_select':
					$values[$fieldId] = array();
					$fieldIdValueLink[$fieldId] = array();
					if ($userFieldId) {
						$valuesList = getDatasetFieldLOV($userFieldId);
					} else {
						$valuesList = static::getUnlinkedFieldLOV($fieldId);
					}
					
					$values[$fieldId]['internal_value'] = $fieldValue;
					if (isset($valuesList[$fieldValue])) {
						$fieldIdValueLink[$fieldId][$fieldValue] = $valuesList[$fieldValue];
						$values[$fieldId]['value'] = $valuesList[$fieldValue];
					}
					break;
				case 'text':
				case 'url':
				case 'calculated':
					$values[$fieldId] = array();
					if ($field['autocomplete']) {
						$fieldLOV = static::getFieldCurrentLOV($fieldId, $fields, $data, $dataset, $instanceId, $filtered);
						$values[$fieldId]['internal_value'] = $fieldValue;
						$fieldValue = $fieldLOV[$fieldValue];
					}
					$value = $fieldValue ? $fieldValue : '';
					switch ($field['db_column']) {
						case 'salutation':
							$value = substr($value, 0, 25);
							break;
						case 'screen_name':
						case 'password':
							$value = substr($value, 0, 50);
							break;
						case 'first_name':
						case 'last_name':
						case 'email':
							$value = substr($value, 0, 100);
							break;
						default:
							$value = substr($value, 0, 255);
							break;
					}
					$values[$fieldId]['value'] = $value;
					$fieldIdValueLink[$fieldId] = $value;
					break;
				case 'editor':
				case 'textarea':
					$value = $fieldValue ? $fieldValue : '';
					$values[$fieldId] = array('value' => $value);
					$fieldIdValueLink[$fieldId] = $fieldValue;
					break;
				case 'attachment':
					$fileId = false;
					if (!empty($fieldValue) && file_exists(CMS_ROOT . $fieldValue)) {
						$filename = substr(basename($fieldValue), 0, -7);
						$fileId = addFileToDatabase('forms', CMS_ROOT . $fieldValue, $filename);
						$values[$fieldId] = array('value' => $filename, 'internal_value' => $fileId, 'attachment' => true);
						if (setting('zenario_user_forms_admin_email_attachments')) {
							$attachments[] = fileLink($fileId);
						}
					}
					$fieldIdValueLink[$fieldId] = $fileId;
					break;
				
				case 'file_picker':
					
					$labelValues = array();
					$internalValues = array();
					
					if ($fieldValue) {
						$fileIds = str_getcsv((string)$fieldValue, ',', '"', '//');
						foreach ($fileIds as $fileId) {
							
							//TODO check $userId here works!
							
							// If numeric file ID do nothing as this is already saved
							if (is_numeric($fileId) && checkRowExists('custom_dataset_files_link', array('dataset_id' => $dataset['id'], 'field_id' => $userFieldId, 'linking_id' => $userId, 'file_id' => $fileId))) {
								$filename = getRow('files', 'filename', $fileId);
								$labelValues[] = $filename;
								$internalValues[] = $fileId;
							} elseif (file_exists(CMS_ROOT . $fileId)) {
								
								// Validate file size (10MB)
								if (filesize(CMS_ROOT . $fileId) > (1024 * 1024 * 10)) {
									continue;
								}
								
								$filename = substr(basename($fileId), 0, -7);
								
								// Validate file extension
								if (trim($field['extensions'])) {
									$type = explode('.', $filename);
									$type = $type[count($type) - 1];
									$extensions = explode(',', $field['extensions']);
									foreach ($extensions as $index => $extension) {
										$extensions[$index] = trim(str_replace('.', '', $extension));
									}
									if (!in_array($type, $extensions)) {
										continue;
									}
								}
								if (!checkDocumentTypeIsAllowed($filename)) {
									continue;
								}
								
								// Just store file as a response attachment if not saving data, otherwise save as user file
								$usage = 'forms';
								if ($formProperties['save_data']) {
									$usage = 'dataset_file';
								}
								
								$postKey = arrayKey($filePickerValueLink, $fileId);
								
								$fileId = addFileToDatabase($usage, CMS_ROOT . $fileId, $filename);
								
								// Change post value to file ID from cache path now file is uploaded
								if ($postKey) {
									$data[$postKey] = $fileId;
								}
								
								if ($fileId) {
									if (setting('zenario_user_forms_admin_email_attachments')) {
										$attachments[] = fileLink($fileId);
									}
									$labelValues[] = $filename;
									$internalValues[] = $fileId;
								}
							}
						}
					}
					
					$filePickerValues[$fieldId] = array(
						'value' => implode(',', $labelValues), 
						'internal_value' => implode(',', $internalValues), 
						'db_column' => $fieldName, 
						'user_field_id' => $userFieldId, 
						'ord' => $field['ord']
					);
					break;
				
			}
			
			if (isset($values[$fieldId])) {
				$values[$fieldId]['type'] = $fieldType;
				$values[$fieldId]['readonly'] = $field['is_readonly'];
				$values[$fieldId]['db_column'] = $fieldName;
				$values[$fieldId]['ord'] = $field['ord'];
			}
		}
	}
	
	
	// Save a forms field data into a session variable when a form has multiple pages
	public static function tempSaveFormFields($fields, $data, $dataset, $instanceId) {
		foreach ($fields as $fieldId => $field) {
			$fieldValue = static::getFieldCurrentValue($field, $data, $dataset, $instanceId, $ignoreSession = true);
			if (!isset($_SESSION['custom_form_data'])) {
				$_SESSION['custom_form_data'] = array();
				if (!isset($_SESSION['custom_form_data'][$instanceId])) {
					$_SESSION['custom_form_data'][$instanceId] = array();
				}
			}
			$tempData = &$_SESSION['custom_form_data'][$instanceId];
			$tempData[$fieldId] = $fieldValue;
		}
	}
	
	
	// Create a user response to a form
	public static function createFormResponse($userId, $formId, $data) {
		$responseId = insertRow(
			ZENARIO_USER_FORMS_PREFIX. 'user_response', 
			array('user_id' => $userId, 'form_id' => $formId, 'response_datetime' => now())
		);
		
		foreach ($data as $fieldId => $field) {
			if (isset($field['value'])) {
				$responseData = array('user_response_id' => $responseId, 'form_field_id' => $fieldId, 'value' => $field['value']);
				if (isset($field['internal_value'])) {
					$responseData['internal_value'] = $field['internal_value'];
				}
				insertRow(ZENARIO_USER_FORMS_PREFIX. 'user_response_data', $responseData);
			}
		}
		
		return $responseId;
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
	
	
	//TODO can I remove this old function?
	public static function getFieldColumn($field) {
		return ($field['db_column'] ? $field['db_column'] : 'unlinked_'. $field['field_type'].'_'.$field['id']);
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
	
}


















