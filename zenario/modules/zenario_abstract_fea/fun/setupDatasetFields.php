<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


foreach ($datasetFieldIds as $datasetFieldId) {
	$datasetField = ze\dataset::fieldDetails($datasetFieldId, $dataset);
	if (!$datasetField) {
		continue;
	}
	
	$newInput = [
		'ord' => $startOrd++,
		'label'=> $datasetField['label'].":",
		'type' => $datasetField['type'],
		'placeholder' => $this->phrase("Optional"),
		'value' => ''
	];
	if ($edit) {
		if ($recordId) {
			$newInput['value'] = ze\dataset::fieldValue($dataset, $datasetField, $recordId);
		}
	} else {
		$newInput['type'] = 'text';
		$newInput['readonly'] = true;
		$newInput['show_as_a_span_when_readonly'] = true;
		$newInput['value'] = ze\dataset::fieldValue($dataset, $datasetField, $recordId, true, true);
	}
	
	if ($datasetField['type'] == 'checkbox' || $datasetField['type'] == 'group') {
		if ($edit) {
			$newInput['type'] = 'checkbox';
			$newInput['label'] = $datasetField['label'];
		} else {
			$newInput['value'] = $newInput['value'] ? $this->phrase('Yes') : $this->phrase('No');
		}
	} elseif ($datasetField['type'] == 'centralised_select' || $datasetField['type'] == 'select') {
		if ($edit) {
			$newInput['type'] = "select";
			$newInput['empty_value'] = " -- Select --";
		} else {
			$list = ze\dataset::fieldLOV($datasetField);
			if (isset($list[$newInput['value']])) {
				$newInput['value'] = $list[$newInput['value']];
			}
		}
	} elseif ($datasetField['type'] == 'centralised_radios') {
		if ($edit) {
			$newInput['type'] = "radios";
		}
	}
	
	if ($edit && in_array($datasetField['type'], ['centralised_select', 'centralised_radios', 'select', 'radios', 'checkboxes'])) {
		$newInput['values'] = ze\dataset::fieldLOV($datasetField, $flat);
	}
	
	//Merge any custom tuix with fields
	if ($datasetField['db_column']) {
		$identifier = $datasetField['db_column'];
		if (isset($tags['tabs'][$tab]['fields']['custom_field_' . $datasetFieldId])) {
			ze\tuix::merge($newInput, $tags['tabs'][$tab]['fields']['custom_field_' . $datasetFieldId]);
			unset($tags['tabs'][$tab]['fields']['custom_field_' . $datasetFieldId]);
		}
	} else {
		$identifier = $datasetFieldId;
	}
	if (isset($tags['tabs'][$tab]['fields']['custom_field_' . $identifier])) {
		ze\tuix::merge($newInput, $tags['tabs'][$tab]['fields']['custom_field_' . $identifier]);
	}
	$tags['tabs'][$tab]['fields']['custom_field_' . $identifier] = $newInput;
}
ze\tuix::addOrdinalsToTUIX($tags['tabs'][$tab]['fields']);
ze\tuix::readValues($tags, $fields, $values, $changes, $filling = true, $resetErrors = false);