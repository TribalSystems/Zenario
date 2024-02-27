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

class zenario_user_forms__admin_boxes__field_calculation extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$box['title'] = $box['key']['title'];
		
		$numericFields = json_decode($values['details/dummy_field'], true);
		if ($numericFields) {
			$fields['details/numeric_field']['values'] = $numericFields;
		} else {
			$fields['details/numeric_field']['empty_value'] = '-- No numeric fields --';
		}
		
		$calculationCode = json_decode($box['key']['calculation_code'], true);
		if ($calculationCode) {
			$values['details/calculation_code'] = $box['key']['calculation_code'];
			static::calculationAdminBoxUpdateDisplay($calculationCode, $fields);
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$calculationCode = json_decode($values['details/calculation_code'], true);
		
		$errors = false;
		// Validate calculation code
		$error = zenario_user_forms::validateCalculationCode($calculationCode);
		
		if (ze::isError($error)) {
			$box['tabs']['details']['errors'][] = ze\admin::phrase((string)$error);
			// If there are errors we have to re-draw the equation because the html is reloaded and this was generated with JS.
			static::calculationAdminBoxUpdateDisplay($calculationCode, $fields);
		}
	}
	
	private static function calculationAdminBoxUpdateDisplay($calculationCode, &$fields) {
		$calculationDisplay = '';
		if ($calculationCode) {
			$lastIsParenthesisOpen = false;
			for ($i = 0; $i < count($calculationCode); $i++) {
				if ($lastIsParenthesisOpen) {
					$lastIsParenthesisOpen = false;
				}
				
				$calculationDisplay .= '<br />';
				
				$calculationDisplay .= '<span>';
				
				switch ($calculationCode[$i]['type']) {
					case 'operation_addition':
						$calculationDisplay .= '+';
						break;
					case 'operation_subtraction':
						$calculationDisplay .= '-';
						break;
					case 'operation_multiplication':
						$calculationDisplay .= '×';
						break;
					case 'operation_division':
						$calculationDisplay .= '÷';
						break;
					case 'parentheses_open':
						$calculationDisplay .= '(';
						$lastIsParenthesisOpen = true;
						break;
					case 'parentheses_close':
						$calculationDisplay .= ')';
						break;
					case 'static_value':
						$calculationDisplay .= $calculationCode[$i]['value'];
						break;
					case 'field':
						$name = '';
						if (isset($fields['details/numeric_field']['values'][$calculationCode[$i]['value']])) {
							$name = $fields['details/numeric_field']['values'][$calculationCode[$i]['value']]['label'];
						} else {
							$name = 'UNKNOWN FIELD';
						}
						$calculationDisplay .= '"' . $name . '"';
						break;
				}
				
				$calculationDisplay .= '</span>';
				
				$calculationDisplay = trim($calculationDisplay);
			}
		}
		$fields['details/calculation_display']['snippet']['html'] = '<div id="zenario_calculation_display">' . trim($calculationDisplay) . '</div>';
	}
}