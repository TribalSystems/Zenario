<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

//Load details on document auto-processing rules, and the documents dataset
if ((!$dataset = \ze\dataset::details('documents'))
 || (!$fields = \ze\datasetAdm::listCustomFields($dataset, false))
 || (!$rules = \ze\row::getAssocs('document_rules', true, [], 'ordinal'))
 || (empty($rules))) {
	return false;
}

//Look up all of the fields that have been used
$lovs = [];
foreach ($rules as $rule) {
	if ($rule['field_id'] && !isset($lovs[$rule['field_id']])) {
		$lovs[$rule['field_id']] = \ze\dataset::fieldLOV($rule['field_id']);
	}
}

//Loop through each of the document ids that we are to update
if (is_numeric($documentIds)) {
	$documentIds = [$documentIds];
} elseif (!is_array($documentIds)) {
	$documentIds = \ze\ray::explodeAndTrim($documentIds, true);
}

foreach ($documentIds as $documentId) {
	
	//Attempt to get details of the document, including the filename
	//Only look for documents without the dont_autoset_metadata flag set
	if (($fileId = \ze\row::get('documents', 'file_id', ['id' => $documentId, 'type' => 'file', 'dont_autoset_metadata' => 0]))
	 && ($filename = \ze\row::get('files', 'filename', $fileId))) {
		
		$stops = [];
		$values = [];
		$checkboxesValues = [];
		$moveToFolder = false;
		
		//Get the name and extension from the filename
		$name = explode('.', $filename);
		$extension = array_splice($name, -1);
		$extension = $extension[0];
		$name = implode('.', $name);
		
		//Loop through each rule, checking and processing each
		foreach ($rules as $rule) {
			//If we've run into a "stop" for this type of rule before, don't process this rule
			if (isset($stops[$rule['field_id']])) {
				continue;
			}
			
			//Work out what we are using to match the pattern on
			$use = '';
			switch ($rule['use']) {
				case 'filename_without_extension':
					$use = $name;
					break;
				case 'filename_and_extension':
					$use = $filename;
					break;
				case 'extension':
					$use = $extension;
			}
			
			//Check if this rule matches. If not, don't process this rule
			if (!@preg_match($rule['pattern'], $use)) {
				continue;
			}
			
			switch ($rule['action']) {
				//Move the document into a folder.
				case 'move_to_folder':
					//This can be overridden, so to avoid repeated moves I won't actually bother doing the
					//database update until the end.
					$moveToFolder = $rule['folder_id'];
					break;
				
				//Change the value of a field
				case 'set_field':
					
					//Get the details of this field
					if (empty($fields[$rule['field_id']])) {
						continue;
					}
					$field = $fields[$rule['field_id']];
					
					if ($rule['replacement_is_regexp']) {
						$replacement = preg_replace($rule['pattern'], $rule['replacement'], $use);
						
						if ($rule['apply_second_pass']) {
							$replacement = preg_replace($rule['second_pattern'], $rule['second_replacement'], $replacement);
						}
					
						//For list of value fields, we say that the replacement should match the display
						//value, not the stored value. But we will be wanting the stored value in order
						//to save the field!
						//If this field is a list of values, try to look for a display value that matches
						//the replacement and switch it with the stored value.
						if (!empty($lovs[$rule['field_id']])) {
							$value = array_search($replacement, $lovs[$rule['field_id']]);
						
							if ($value !== false) {
								$replacement = $value;
							} else {
								$replacement = '';
							}
						}
					} else {
						$replacement = $rule['replacement'];
					}
					
					//As with folder moves, I don't want to action these changes immediately,
					//so I'll store the columns/values I want to set later in an array.
					
					//Checkboxes fields can have multiple values, and one checkbox field can
					//have different values set by different rules without them overwriting
					//each other if the values are different, so the values of the checkboxes
					//fields need to be stored in a 2D array.
					if ($field['type'] == 'checkboxes') {
						if ($replacement) {
							if (!isset($checkboxesValues[$rule['field_id']])) {
								$checkboxesValues[$rule['field_id']] = [];
							}
							$checkboxesValues[$rule['field_id']][$replacement] = true;
						}
					
					//Other fields can be just stored in a flat array; if we get two rules
					//for the same field the second will overwrite the first
					} else {
                        //For checkbox-type fields use \ze\ring::engToBoolean() on the resulting answer.
						if ($field['type'] == 'checkbox') {
							$values[$field['db_column']] = \ze\ring::engToBoolean($replacement);
						
                        //For date-type fields, use strtotime() on the resulting answer,
                        //then convert it to the mysql format.
                        //If we can't manage this, don't attempt to change the field.
						} elseif ($field['type'] == 'date') {
							if (($replacement = strtotime($replacement))
							 && ($replacement = date('Y-m-d', $replacement))) {
								$values[$field['db_column']] = $replacement;
							}
						
						//Otherwise don't use any special type of formatting
						} else {
							$values[$field['db_column']] = $replacement;
						}
					}
			}
			
			if ($rule['stop_processing_rules']) {
				$stops[$rule['field_id']] = true;
			}
	
		}
		
		//Move the document to a folder if asked to
		if ($moveToFolder) {
			\ze\row::update('documents', ['folder_id' => $moveToFolder], $documentId);
		}
		
		//Save all of the values of the fields
		if (!empty($values)) {
			\ze\row::set('documents_custom_data', $values, $documentId);
		}
		
		//And do checkboxes-type fields separately
		if (!empty($checkboxesValues)) {
			foreach ($checkboxesValues as $fieldId => $selectedValues) {
				\ze\dataset::updateCheckboxField($dataset['id'], $fieldId, $documentId, array_keys($selectedValues));
			}
		}
	}

}


return false;