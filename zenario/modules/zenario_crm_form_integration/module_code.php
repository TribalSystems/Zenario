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

class zenario_crm_form_integration extends module_base_class {

	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch($path){
			case 'zenario__user_forms/panels/zenario_user_forms__form_fields':
				foreach($panel['items'] as &$item) {
					$formFieldId = $item['id'];
					//keep the same order than dataset fields
					$valuesArray = array();
					$sql="
						SELECT fcfv.value
						FROM ". DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_field_values as fcfv
						LEFT JOIN ". DB_NAME_PREFIX."custom_dataset_field_values as cdfv
						ON fcfv.form_field_value_dataset_id = cdfv.id
						WHERE fcfv.form_field_id =".$formFieldId."
						AND fcfv.form_field_value_dataset_id != 0
						ORDER BY ord ASC";
						$result = sqlQuery($sql);
						while($row = sqlFetchAssoc($result)) {
							$valuesArray[] = $row['value'];
						}
					
					//keep the same order than value list (unlinked)
					if(!$valuesArray){
					$sql="
						SELECT fcfv.value
						FROM ". DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_field_values as fcfv
						LEFT JOIN ". DB_NAME_PREFIX.ZENARIO_USER_FORMS_PREFIX."form_field_values as ffv
						ON fcfv.form_field_value_unlinked_id = ffv.id
						WHERE fcfv.form_field_id =".$formFieldId."
						AND fcfv.form_field_value_unlinked_id != 0
						ORDER BY ord ASC";
						$result = sqlQuery($sql);
						$formFieldTypes = array();
						while($row = sqlFetchAssoc($result)) {
							$valuesArray[] = $row['value'];
						}
					}
					
					
					if ($valuesArray){
						$stringValues = implode(', ',$valuesArray);
						$item['crm_field_values']=$stringValues;
					}else{
						$item['crm_field_values']='n/a';
					}
					//form field name
				$formFieldName = self::getFormCrmField($formFieldId);
				if ($formFieldName){
					$item['crm_field_name'] = $formFieldName ['field_crm_name'];
				}
			}
			break;
		}
	}


	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'zenario_user_admin_box_form':
				$fields['data/send_signal']['note_below'] .= '<br> The checkbox is automatically checked when "CRM integration" is enabled';
				if($box['key']['id']){
					$formId = $box['key']['id'];
					$row = getRow(
										ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
										array(	'crm_url',
												'custom_input_name_1',
												'custom_input_value_1',
												'custom_input_name_2',
												'custom_input_value_2',
												'custom_input_name_3',
												'custom_input_value_3',
												'custom_input_name_4',
												'custom_input_value_4',
												'custom_input_name_5',
												'custom_input_value_5',
												'enable_crm_integration',
												'form_id'
										),
										array('form_id' => $formId)
										);
					$values['crm_integration/crm_url'] = $row['crm_url'];
					$values['crm_integration/name1'] = $row['custom_input_name_1'];
					$values['crm_integration/value1'] = $row['custom_input_value_1'];
					$values['crm_integration/name2'] = $row['custom_input_name_2'];
					$values['crm_integration/value2'] = $row['custom_input_value_2'];
					$values['crm_integration/name3'] = $row['custom_input_name_3'];
					$values['crm_integration/value3'] = $row['custom_input_value_3'];
					$values['crm_integration/name4'] = $row['custom_input_name_4'];
					$values['crm_integration/value4'] = $row['custom_input_value_4'];
					$values['crm_integration/name5'] = $row['custom_input_name_5'];
					$values['crm_integration/value5'] = $row['custom_input_value_5'];
					$values['crm_integration/enable_crm_integration'] = $row['enable_crm_integration'];
				}
				break;
				case 'zenario_user_admin_box_form_field':
					$formFieldId= $box['key']['id'];
					if($formFieldId){
						$formFieldArray = self::getFormCrmField($formFieldId);
						if(isset($formFieldArray['field_crm_name'])){
							$values['crm_integration/crm_field_name'] = $formFieldArray['field_crm_name'];
						}
						unset($box['tabs']['crm_integration_message']);
					}else{
						unset($box['tabs']['crm_integration']);
					}
				break;
				
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		switch ($path){
			case 'zenario_user_admin_box_form':
				$crmIntegration = $values['crm_integration/enable_crm_integration'];
				if ($crmIntegration){
					$fields['crm_integration/crm_url']['hidden'] = false;
					$fields['crm_integration/name1']['hidden'] = false;
					$fields['crm_integration/value1']['hidden'] = false;
					$fields['crm_integration/name2']['hidden'] = false;
					$fields['crm_integration/value2']['hidden'] = false;
					$fields['crm_integration/name3']['hidden'] = false;
					$fields['crm_integration/value3']['hidden'] = false;
					$fields['crm_integration/name4']['hidden'] = false;
					$fields['crm_integration/value4']['hidden'] = false;
					$fields['crm_integration/name5']['hidden'] = false;
					$fields['crm_integration/value5']['hidden'] = false;
					
					//enable sendsignal
					$values['data/send_signal'] = true;
					$fields['data/send_signal']['read_only'] = true;
					
				}else{
					$fields['crm_integration/crm_url']['hidden'] = true;
					$fields['crm_integration/name1']['hidden'] = true;
					$fields['crm_integration/value1']['hidden'] = true;
					$fields['crm_integration/name2']['hidden'] = true;
					$fields['crm_integration/value2']['hidden'] = true;
					$fields['crm_integration/name3']['hidden'] = true;
					$fields['crm_integration/value3']['hidden'] = true;
					$fields['crm_integration/name4']['hidden'] = true;
					$fields['crm_integration/value4']['hidden'] = true;
					$fields['crm_integration/name5']['hidden'] = true;
					$fields['crm_integration/value5']['hidden'] = true;
					$fields['data/send_signal']['read_only'] = false;
				}
			break;
			case 'zenario_user_admin_box_form_field':
				$formFieldId = $box['key']['id'];
				$multiValues = false;
				if ($formFieldId){
					$sql="
						SELECT cdf.type AS type_from_ds, ff.field_type AS type_from_form, ff.user_field_id
						FROM ". DB_NAME_PREFIX."user_form_fields AS ff
						LEFT JOIN ". DB_NAME_PREFIX."custom_dataset_fields as cdf
						ON ff.user_field_id = cdf.id
						where ff.id =".$formFieldId;
				
					$result = sqlQuery($sql);
					
					$formFieldTypes = array();
					while($row = sqlFetchAssoc($result)) {
						$formFieldTypes = $row;
					}
					
					if (isset($formFieldTypes['type_from_ds']) && $formFieldTypes['type_from_ds']){
						$type = $formFieldTypes['type_from_ds'];
						$datasetField = true;
					}elseif(isset($formFieldTypes['type_from_form']) && $formFieldTypes['type_from_form']){
						$type = $formFieldTypes['type_from_form'];
						$datasetField=false;
					}
					
					if ($type == "checkboxes" || $type == "select" || $type == "radios"){
						$multiValues = true;
						if ($datasetField){
							$fieldValues = getRowsArray('custom_dataset_field_values', array('label', 'id'), array('field_id'=>$formFieldTypes['user_field_id']));
						}
					} else {
						$multiValues = false;
					}
				}
				//selecting  db values
				if ($multiValues){
					//only if tab value is enable (no dataset)
					if (!$datasetField) {
						$values['crm_integration/number_of_fields'] = $values['lov/number_of_fields'];
						$numberOfValues = $values['crm_integration/number_of_fields'];
					} else {
						$values['crm_integration/number_of_fields'] = $numberOfValues = count($fieldValues);
					}
					
					/*$i=1;
					while (isset($values['lov/id' . $i])){
						$numberOfFields = $i;
						$i++;
					}*/
					//var_dump($numberOfFields);
					$ord = 100;
					$firstTime =false;
					if (!$datasetField && isset($numberOfValues)) {
						$i =1;
						while (isset($values['crm_integration/custom_field_id' . $i])){
							unset($box['tabs']['crm_integration']['fields']['custom_field_name'. $i]);
							unset($box['tabs']['crm_integration']['fields']['custom_field_value'. $i]);
							unset($box['tabs']['crm_integration']['fields']['custom_field_id'. $i]);
							$i++;
						}
						//unlinked values
						for($i =1; $i <= $numberOfValues; $i++) {
							if(isset($values['lov/id' . $i])) {
								$fieldValueId = $values['lov/id' . $i];
								$fieldValueValue = $values['lov/label' . $i];
								$textFieldName = 'custom_field_name'. $i;
								$textFieldValue = 'custom_field_value'. $i;
								$textFieldId = 'custom_field_id'. $i;
								$crmFieldValuesArray = self::getFormCrmFieldValues($formFieldId);

								if (!isset($box['tabs']['crm_integration']['fields'][$textFieldName])) {
									if (!$firstTime){
										$firstTime = true;
										$name = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_name'];
										$name['pre_field_html'] = "<table><tr><th>Option (unlinked)</th><th>CRM value to be sent</th><th></th></tr><tr><td>";
										$name['same_row'] = false;
									}else{
										$name = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_name'];
									}
									$value = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_value'];
									$hiddenId = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_id'];

									$name['ord'] = $ord ++;
									$value['ord'] = $ord ++;
									
									//Unlink value
									$name['value'] = $fieldValueValue; // values
									$value['value'] ='';
									$found=false;;
									
									foreach ($crmFieldValuesArray as $crmFieldValue){
										if ($crmFieldValue['form_field_value_unlinked_id'] == $fieldValueId){
											$crmValue = $crmFieldValue['value'];
											$value['value'] = $crmValue;
											$found=true;
										}
									}
									if (!$found){
										$value['value'] = $fieldValueValue;
									}
									$hiddenId['value'] = $fieldValueId;
									$box['tabs']['crm_integration']['fields'][$textFieldName] = $name;
									$box['tabs']['crm_integration']['fields'][$textFieldValue] = $value;
									$box['tabs']['crm_integration']['fields'][$textFieldId] = $hiddenId;
								}
							}
						}
					} else {
						//dataset
						$crmFieldValuesArray = self::getFormCrmFieldValues($formFieldId);
					
						//just get dataset values
						foreach ($crmFieldValuesArray as $crmFieldValue){
							if ($crmFieldValue['form_field_value_dataset_id']){
										$datasetvalue[] =  $crmFieldValue;
										//$crmFieldValue['value'];
							}
						}
					
						$i = 1;
						if (isset($fieldValues) && $fieldValues){
							foreach ($fieldValues as $fieldValue) {
								$fieldValueId = $fieldValue['id'];
								$fieldValueValue = $fieldValue['label'];
								$textFieldName = 'custom_field_name'. $i;
								$textFieldValue = 'custom_field_value'. $i;
								$textFieldId = 'custom_field_id'. $i;
					
								if (!isset($box['tabs']['crm_integration']['fields'][$textFieldName])) {
									if (!$firstTime){
										$firstTime = true;
										$name = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_name'];
										$name['pre_field_html'] = "<table><tr><th>Option (linked)</th><th>CRM value to be sent</th><th></th></tr><tr><td>";
										$name['same_row'] = false;
									}else{
										$name = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_name'];
									}
										$value = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_value'];
										$hiddenId = $box['tabs']['crm_integration']['custom_template_fields']['custom_field_id'];
			
										$name['ord'] = $ord++;
										$value['ord'] = $ord++;
										$hiddenId['value'] = $fieldValueId;
										//Data set value
										$name['value'] = $fieldValueValue; 
					
										if (isset($datasetvalue)){
											foreach ($datasetvalue as $dvalue){
												if ($dvalue['form_field_value_dataset_id'] == $fieldValue['id']){
													$value['value'] =  $dvalue['value'];
												}
											}
										}else{
											$value['value'] = $fieldValueValue;
										}
										$box['tabs']['crm_integration']['fields'][$textFieldName] = $name;
										$box['tabs']['crm_integration']['fields'][$textFieldValue] = $value;
										$box['tabs']['crm_integration']['fields'][$textFieldId] = $hiddenId;
									}
								$i++;
							}
						}
					}
				}else{
					//hide the note
					$fields['crm_integration/desc']['hidden'] = true;
				}
			break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving){
		switch ($path){
			case 'zenario_user_admin_box_form':
				if (!$values['crm_integration/crm_url'] && $values['crm_integration/enable_crm_integration']) {
					$fields['crm_integration/crm_url']['error'] = adminPhrase('Please enter the CRM form action URL');
				}
				break;
			case 'zenario_user_admin_box_form_field':
				
				//Validation CRM Field name
				/*
				$formFieldId = $box['key']['id'];
				$formId = self::getFormIdPassingFormFieldId($formFieldId);
				$crmEnable = self::getFormCrmData($formId);
				
				if ($crmEnable['enable_crm_integration']){
					if (!$values['crm_integration/crm_field_name']) {
						$fields['crm_integration/crm_field_name']['error'] = adminPhrase('Please enter the CRM field name');
					}
				}*/
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes){
		switch ($path){
			case 'zenario_user_admin_box_form':
				if($box['key']['id']){
					$formId = $box['key']['id'];
					$enable_crm_integration = $values['crm_integration/enable_crm_integration'];
					$crm_url = $values['crm_integration/crm_url'];
					$name1 = $values['crm_integration/name1'];
					$value1 = $values['crm_integration/value1'];
					$name2 = $values['crm_integration/name2'];
					$value2 = $values['crm_integration/value2'];
					$name3 = $values['crm_integration/name3'];
					$value3 = $values['crm_integration/value3'];
					$name4 = $values['crm_integration/name4'];
					$value4 = $values['crm_integration/value4'];
					$name5 = $values['crm_integration/name5'];
					$value5 = $values['crm_integration/value5'];
					//update db table
					$box['key']['id'] = self::updateFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration);
				}
				break;
			case 'zenario_user_admin_box_form_field':
				if($box['key']['id']){
					$formFieldId = $box['key']['id'];
					$fieldCrmName = $values['crm_integration/crm_field_name'];
					self::updateFormCrmField($formFieldId,$fieldCrmName);
					//get FAB values
					$i=1;
					$crmValues = array();
					$field = array();
					$fieldValuesDetails=getRowsArray(ZENARIO_USER_FORMS_PREFIX."form_field_values", array('id','label'),array('form_field_id'=>$formFieldId));
					
					while (isset($values['crm_integration/custom_field_name'.$i])){
						
						if (isset($values['lov/label' . $i])){
							$field['name'] = $values['lov/label' . $i];
						}
						if (isset($values['crm_integration/custom_field_value'.$i])){
							$field['value']  = $values['crm_integration/custom_field_value'.$i];
						}
						if (isset($values['crm_integration/custom_field_id'.$i])){
							$field['id'] = $values['crm_integration/custom_field_id'.$i];
						}
						/*if(!$field['value']){
							$field['value'] = $values['lov/label' . $i];
						}*/
						if(!$field['id']){
							foreach ($fieldValuesDetails as $fieldValue){
								if ($fieldValue['label']==$field['name']){
									$field['id'] = $fieldValue['id'];
								}
							}
						}
						$crmValues[]= $field;
						$i++;
					}
					//check dataset ids
					$sql="
						SELECT user_form_id as unlinkedId, user_field_id AS datasetId
						FROM ". DB_NAME_PREFIX."user_form_fields AS ff
						where id =".$formFieldId;
							
						$result = sqlQuery($sql);
						$formFieldValuesIds = array();
						while($row = sqlFetchAssoc($result)) {
							$formFieldValuesIds = $row;
						}
						
					if (isset($formFieldValuesIds['datasetId']) && $formFieldValuesIds['datasetId']){
						//dataset values
						$datasetFieldId = $formFieldValuesIds['datasetId'];
						$datasetFieldsValues = getRowsArray('custom_dataset_field_values', array('id','field_id','ord', 'label','note_below'), array('field_id' => $datasetFieldId), 'ord');
						$dataset=true;
						$i=0;
						foreach ($datasetFieldsValues as $fieldValues){
							$formFieldValueDatasetId = $fieldValues['id'];
							$value = $crmValues[$i]['value'];
							$formFieldValueUnlinkedId = 0;
							//save in database dataset values
							self::saveFormCrmFieldValues($formFieldId,$formFieldValueDatasetId,$formFieldValueUnlinkedId,$value);
							$i++;
						}
					}else{
						//Unlinked values
						$unlinkedFieldValues = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', array('id','ord', 'label'), array('form_field_id' => $formFieldId), 'ord');
						$dataset=false;
						foreach ($crmValues as $crmValuesArray){
							$formFieldValueUnlinkedId = $crmValuesArray['id'];
							if($crmValuesArray['id']){
								$idsArray[]=$crmValuesArray['id'];
							}
							$value = $crmValuesArray['value'];
							$formFieldValueDatasetId=0;
							//save database unlinked values
							self::saveFormCrmFieldValues($formFieldId,$formFieldValueDatasetId,$formFieldValueUnlinkedId,$value);
						}
						//clean values unlinked values
						if(isset($idsArray) && $idsArray){
							//self::cleanDbUnlinkedValues($idsArray);
						}
					}
				}
				
				break;
		}
	}
	
	public static function eventUserFormSubmitted($data, $rawData, $fromProperties, $fieldIdValueLink) {
		$formId = $fromProperties['user_form_id'];
		
		$formCrmData = self::getFormCrmData($formId);
		$data=array();
		$url = $formCrmData['crm_url'];	
		if ($formCrmData['custom_input_name_1']) {
			$data[$formCrmData['custom_input_name_1']] = $formCrmData['custom_input_value_1'];
		}
		if ($formCrmData['custom_input_name_2']) {
			$data[$formCrmData['custom_input_name_2']] = $formCrmData['custom_input_value_2'];
		}
		if ($formCrmData['custom_input_name_3']) {
			$data[$formCrmData['custom_input_name_3']] = $formCrmData['custom_input_value_3'];
		}
		if ($formCrmData['custom_input_name_4']) {
			$data[$formCrmData['custom_input_name_4']] = $formCrmData['custom_input_value_4'];
		}
		if ($formCrmData['custom_input_name_5']) {
			$data[$formCrmData['custom_input_name_5']] = $formCrmData['custom_input_value_5'];
		}
		
		foreach($fieldIdValueLink as $fieldId => $value) {
			$fieldCrmName = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields', 'field_crm_name', array('form_field_id' => $fieldId));
			if ($fieldCrmName){
				if(is_array($value)) {
					//this is a select list, multi-checkboxes or radios
					//look up field value crm value
					foreach($value as $fieldValueId => $rawValue) {
						//Dataset values
						$isDataset= getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId,'form_field_value_dataset_id'=>$fieldValueId));
						//Unlinked values
						$isUnlinked= getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', 'value', array('form_field_id' => $fieldId,'form_field_value_unlinked_id'=>$fieldValueId));
						if ($isDataset){
							$crmValue = $isDataset;
						}elseif($isUnlinked){
							$crmValue = $isUnlinked;
						}
						/*
						this code avoid send empty data
						if(!$crmValue) {
							//if not crm value has been use for the field use raw value
							$crmValue = $rawValue;
						}
						*/
					}
					$value = $crmValue;
				}
				$data[$fieldCrmName] = $value;
			}
			
		}
		self::submitCrmCustomValues($url, $data);
		return true;
	}
	
	
	public static function submitCrmCustomValues($url, $data){
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
			),
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
	}
	
	//FormCrmData
	public static function getFormCrmData($formId){
		$formCrmData= getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_data',
			array('form_id',
					'crm_url',
					'custom_input_name_1',
					'custom_input_value_1',
					'custom_input_name_2',
					'custom_input_value_2',
					'custom_input_name_3',
					'custom_input_value_3',
					'custom_input_name_4',
					'custom_input_value_4',
					'custom_input_name_5',
					'custom_input_value_5',
					'enable_crm_integration'
					),
					array('form_id' => $formId)
					);
			return $formCrmData;
	}
	
	public static function updateFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration){
		$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
			array('crm_url' => $crm_url,
					'custom_input_name_1' =>$name1,
					'custom_input_value_1' => $value1,
					'custom_input_name_2' =>$name2,
					'custom_input_value_2' => $value2,
					'custom_input_name_3' =>$name3,
					'custom_input_value_3' => $value3,
					'custom_input_name_4' =>$name4,
					'custom_input_value_4' => $value4,
					'custom_input_name_5' =>$name5,
					'custom_input_value_5' => $value5,
					'enable_crm_integration' =>$enable_crm_integration,
					),
					array('form_id' => $formId)
					);
		return $rowId;
	}
	
	public static function saveFormCrmData($formId,$crm_url,$name1,$value1,$name2,$value2,$name3,$value3,$name4,$value4,$name5,$value5,$enable_crm_integration){
		$rowId = setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_data',
			array('crm_url' => $crm_url,
					'custom_input_name_1' =>$name1,
					'custom_input_value_1' => $value1,
					'custom_input_name_2' =>$name2,
					'custom_input_value_2' => $value2,
					'custom_input_name_3' =>$name3,
					'custom_input_value_3' => $value3,
					'custom_input_name_4' =>$name4,
					'custom_input_value_4' => $value4,
					'custom_input_name_5' =>$name5,
					'custom_input_value_5' => $value5,
					'enable_crm_integration' =>$enable_crm_integration,
					'form_id'=>$formId
					)
					);
		return $rowId;
	}
	
	// Form CRM Fields
	public static function getFormCrmField($formFieldId){
		$formCrmFieldsArray = getRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_fields',
			array('form_field_id','field_crm_name'),
			array('form_field_id'=>$formFieldId));
		return $formCrmFieldsArray;
	}
	
	public static function updateFormCrmField($formFieldId,$fieldCrmName){
		$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields',
			array('field_crm_name' => $fieldCrmName),
			array('form_field_id'=>$formFieldId));
		return $rowId;
	}
	
	public static function saveFormCrmField($formFieldId,$fieldCrmName){
		$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_fields',
			array('form_field_id'=>$formId,'field_crm_name' => $fieldCrmName));
		return $rowId;
	}
	
	//Form CRM Field Values
	public static function getFormCrmFieldValues($formFieldId){
		$formCrmFieldValues = getRowsArray(ZENARIO_CRM_FORM_INTEGRATION_PREFIX.'form_crm_field_values',
			array('form_field_value_dataset_id','form_field_value_unlinked_id','form_field_id','value'),
			array('form_field_id' => $formFieldId));
		return $formCrmFieldValues;
	}
	
	public static function updateFormCrmFieldValues($formFieldId,$formFieldValueDatasetId,$formFieldValueUnlinkedId,$value){
		$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values',
				array('form_field_value_dataset_id'=>$formFieldValueDatasetId,'form_field_value_unlinked_id'=>$formFieldValueUnlinkedId,'value'=>$value),
				array('form_field_id'=>$formFieldId));
		return $rowId;
	}
	
	public static function saveFormCrmFieldValues($formFieldId,$formFieldValueDatasetId,$formFieldValueUnlinkedId,$value){
				
			if ($formFieldValueDatasetId){
				$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values',
					array(	'form_field_id'=>$formFieldId,
							'form_field_value_dataset_id'=>$formFieldValueDatasetId,
							'form_field_value_unlinked_id'=>$formFieldValueUnlinkedId,
							'value'=>$value),
					array('form_field_value_dataset_id'=>$formFieldValueDatasetId)
					);
			}else{
					$rowId=setRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values',
					array(	'form_field_id'=>$formFieldId,
							'form_field_value_dataset_id'=>$formFieldValueDatasetId,
							'form_field_value_unlinked_id'=>$formFieldValueUnlinkedId,
							'value'=>$value),
					array('form_field_value_unlinked_id'=>$formFieldValueUnlinkedId)
					);
			}
		return $rowId;
	}
	
	public static function getFormIdPassingFormFieldId($formFieldId){
		$formId = getRow('user_form_fields',
			array('user_form_id'),
			array('id'=>$formFieldId));
		return $formId['user_form_id'];
	}
	
	public static function cleanDbUnlinkedValues($ids){
		if ($ids){
			$stringOfIds = implode(',',$ids);
			$sql="
					SELECT form_field_value_unlinked_id 
					FROM ". DB_NAME_PREFIX.ZENARIO_CRM_FORM_INTEGRATION_PREFIX."form_crm_field_values
					where form_field_value_unlinked_id NOT IN (".$stringOfIds.") 
					AND form_field_value_dataset_id = 0";
			
			$result = sqlQuery($sql);
			$formFieldTypes = array();
			while($row = sqlFetchAssoc($result)) {
				$idsToDelete[] = $row['form_field_value_unlinked_id'];
			}
			if(isset($idsToDelete) && $idsToDelete){
				foreach ($idsToDelete as $id){
					deleteRow(ZENARIO_CRM_FORM_INTEGRATION_PREFIX. 'form_crm_field_values', array('form_field_value_unlinked_id' => $id));
				}
			}
		}
	}
}