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

class zenario_users__admin_boxes__advanced_search extends zenario_users {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($settingGroup) {
			case 'zenario__users/panels/users':
				//If the "Smart Group" key is set, try to turn the advanced search box into the editing tools for a smart group
				if (!empty($box['key']['zenario_pro_features__editing_smart_group'])) {
					//Require the _PRIV_VIEW_USER permission to see this information
					exitIfNotCheckPriv('_PRIV_VIEW_USER');
			
					//Require the _PRIV_EDIT_GROUP permission to edit this information
					if (!checkPriv('_PRIV_EDIT_GROUP')) {
						foreach ($box['tabs'] as $tabName => &$tab) {
							if (!isInfoTag($tabName)) {
								if (isset($tab['edit_mode']['enabled'])) {
									$tab['edit_mode']['enabled'] = false;
								}
							}
						}
					}
			
					$myfields = &$box['tabs']['first_tab']['fields'];
					if ($details = getSmartGroupDetails($box['key']['zenario_pro_features__smart_group_id'])) {
						$values['name'] = $details['name'];
						$box['title'] = adminPhrase('Editing the smart group "[[name]]".', $details);
			
					} else {
						$box['title'] = adminPhrase('Creating a smart group');
					}
					$myfields['name']['label'] = adminPhrase('Smart group name:');
			
					$box['save_button_message'] = '';
				}

				foreach (explode(',', $values['first_tab/indexes']) as $index) {
					if ($index) {
						$this->createSmartGroupFieldSet($box, $index);
					}
				}
		}
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($settingGroup) {
			case 'zenario__users/panels/users':
				$maxIndex =  0;
				$indexes = array();
				$my_indexes = $box['tabs']['first_tab']['fields']['indexes'];
				foreach (explode(',', $values['first_tab/indexes']) as $index) {
					if ($index) {
						$indexes[$index] = $index;
					}
				}

				$myfields = &$box['tabs']['first_tab']['fields'];
		
				foreach ($myfields as $K=>$V) {
					$arr = explode('_', $K);
					$arr_count = (int)$arr[count($arr)-1];
					if ($arr_count > $maxIndex) {
						$indexes[$arr_count] = $arr_count;
						$maxIndex = $arr_count;
					}
				}

				if (arrayKey($myfields, 'rule_create', 'pressed')) {
					$maxIndex++;
					$this->createSmartGroupFieldSet($box, $maxIndex);
					$indexes[$maxIndex] = $maxIndex;
				}


				$prevIndex = 0;
				foreach($indexes as $index) {
					if ($index && arrayKey($myfields, 'rule_delete_' . $index , 'pressed')) {
						$this->deleteSmartGroupFieldSet($box, $index, $prevIndex);
						unset($indexes[$index]) ;
					}
					$prevIndex = $index;
				}

				$prevIndex = 0;
				foreach($indexes as $index) {
					$myfields['rule_type_' . $index]['hidden'] = false;
					$myfields['rule_delete_' . $index]['hidden'] = false;
					$rule_type = arrayKey($values, 'first_tab/rule_type_' . $index);
					$logic_key = 'rule_logic_' . $index;
					$myfields_logic_key_vales = &$myfields[$logic_key]['values'];
					$myfields_rule_characteristic_values_picker = &$myfields['rule_characteristic_values_picker_' . $index];
			
					switch ($rule_type) {
						case 'characteristic':
							$myfields['rule_group_picker_' . $index]['hidden'] = true;

							$myfields['rule_characteristic_picker_' . $index]['hidden'] = false;
							$C = zenario_users::getCharacteristic(arrayKey($values,'first_tab/rule_characteristic_picker_' . $index));

							if ( $C['type'] == 'list_single_select' || $C['type'] == 'list_multi_select' ) {
								$myfields_rule_characteristic_values_picker['hidden'] = false;
								switch ($C['type']) {
									case 'list_single_select': 
										unset($myfields_logic_key_vales['all']);
										$myfields_logic_key_vales['any'] = adminPhrase("user must meet ANY criterion");
										break;
									case 'list_multi_select':
										$myfields_logic_key_vales['all'] = adminPhrase("user must meet ALL criteria");
										$myfields_logic_key_vales['any'] = adminPhrase("user must meet ANY criterion");
										break;
								}
								$myfields[$logic_key]['hidden'] = 
									(count(explode(',', arrayKey($values, 'first_tab/rule_characteristic_values_picker_' . $index))) <=1 );
						
						
								$myfields_rule_characteristic_values_picker['values'] = getDatasetFieldLOV($C['id'], false);
							} else {
								$myfields_rule_characteristic_values_picker['hidden'] = true;
								$myfields[$logic_key]['hidden'] = true;
							}
							break;
						case 'group':
						default:
							$myfields['rule_characteristic_picker_' . $index]['hidden'] = true;
							$myfields_rule_characteristic_values_picker['hidden'] = true;

							$myfields['rule_group_picker_' . $index]['hidden'] = false;
							$myfields[$logic_key]['hidden'] = 	
								(count(explode(',', arrayKey($values, 'first_tab/rule_group_picker_' . $index))) <=1 );
							$myfields_logic_key_vales['all'] = adminPhrase("user must be a member of ALL groups selected above");
							$myfields_logic_key_vales['any'] = adminPhrase("user must be a member of ANY group selected above");
							break;
					}

					if ( $prevIndex ) {
						$myfields['rule_separator_' . $prevIndex]['hidden'] = false;
					}
					$prevIndex = $index;
				}

				$my_exclude_fields = &$box['tabs']['exclude']['fields'];
				if ($values['exclude/enable']) {
					$my_exclude_fields['rule_type']['hidden'] = false;
					$rule_type = arrayKey($values, 'exclude/rule_type');
					switch ($rule_type) {
						case 'characteristic':
							$my_exclude_fields['rule_group_picker']['hidden'] = true;
	
							$my_exclude_fields['rule_characteristic_picker']['hidden'] = false;
							$C = zenario_users::getCharacteristic(arrayKey($values,'exclude/rule_characteristic_picker'));
							if ( $C['type'] == 'list_single_select' || $C['type'] == 'list_multi_select' ) {
								$my_exclude_fields['rule_characteristic_values_picker']['hidden'] = false;
	
								$my_exclude_fields['rule_logic']['hidden'] = 
									(count(explode(',', arrayKey($values, 'exclude/rule_characteristic_values_picker'))) <=1 );
	
								$my_exclude_fields['rule_characteristic_values_picker']['values']  = getDatasetFieldLOV($C['id'], false);
							} else {
								$my_exclude_fields['rule_characteristic_values_picker']['hidden'] = true;
								$my_exclude_fields['rule_logic_' . $index]['hidden'] = true;
							}
							//$my_exclude_fields['rule_logic']['values']['all'] = adminPhrase("user must meet ANY criteria");
							$my_exclude_fields['rule_logic']['values']['any'] = adminPhrase("user must meet ANY criterion");
							break;
						case 'group':
						default:
							$my_exclude_fields['rule_characteristic_picker']['hidden'] = true;
							$my_exclude_fields['rule_characteristic_values_picker']['hidden'] = true;
	
							$my_exclude_fields['rule_group_picker']['hidden'] = false;
							$my_exclude_fields['rule_logic']['hidden'] = 	
								(count(explode(',', arrayKey($values, 'exclude/rule_group_picker'))) <=1 );
							//$my_exclude_fields['rule_logic_' . $index]['values']['all'] = adminPhrase("user must be a member of ALL groups selected above");
							$my_exclude_fields['rule_logic_' . $index]['values']['any'] = adminPhrase("user must be a member of ANY group selected above");
							break;
					}
				} else {
					$my_exclude_fields['rule_type']['hidden'] = 
						$my_exclude_fields['rule_group_picker']['hidden'] = 
							$my_exclude_fields['rule_characteristic_picker']['hidden'] = 
								$my_exclude_fields['rule_characteristic_values_picker']['hidden'] = true;


					$values['exclude/rule_type'] =
					$values['exclude/rule_group_picker'] =
					$values['exclude/rule_characteristic_picker'] =
					$values['exclude/rule_characteristic_values_picker'] = '';
				}
				$values['first_tab/indexes'] = implode(',', $indexes);


				$myfields['rule_create']['value'] = adminPhrase("Create rule");
		}
	}

	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($settingGroup) {
			case 'zenario__users/panels/users':
				//If the "Smart Group" key is set, try to turn the advanced search box into the editing tools for a smart group
				if (!empty($box['key']['zenario_pro_features__editing_smart_group'])) {
			
					$my_first_tab_errors = &$box['tabs']['first_tab']['errors'];
			
					if (!empty($my_first_tab_errors[0])
					 && $my_first_tab_errors[0] == adminPhrase('Please enter a descriptive name for your search.')) {
						$my_first_tab_errors[0] = adminPhrase('Please enter a name for your smart group.');
					}
			
					if ($values['first_tab/name']
					 && checkRowExists(
						'smart_groups',
						array(
							'name' => $values['first_tab/name'],
							'id' => array('!' => $box['key']['zenario_pro_features__smart_group_id']))
					)) {
						$my_first_tab_errors[] = adminPhrase('A smart group with the name "[[name]]" already exists. Please choose another name.', array('name' => $values['first_tab/name']));
					}

					//from pro_business
		
					foreach (explode(',', $values['first_tab/indexes']) as $index) {
						if ($index) {
							if (($values['first_tab/rule_type_' . $index]=='characteristic')) {
								if ($C = zenario_users::getCharacteristic(arrayKey($values,'first_tab/rule_characteristic_picker_' . $index))) {
									switch ($C['type']) {
										case 'list_single_select':
										case 'list_multi_select':
											if (!$values['first_tab/rule_characteristic_values_picker_' . $index]) {
												$my_first_tab_errors[] = adminPhrase('One of your rules has no characteristic value selected.');
											}
									}
								} else {
									$my_first_tab_errors[] = adminPhrase('One of your rules has no characteristic selected.');
								}
							}
						}
					}
		
					if (($values['exclude/rule_type']=='characteristic')) {
						if ($C = zenario_users::getCharacteristic(arrayKey($values,'exclude/rule_characteristic_picker'))) {
							switch ($C['type']) {
								case 'list_single_select':
								case 'list_multi_select':
									if (!$values['exclude/rule_characteristic_values_picker']) {
										$my_first_tab_errors[] = adminPhrase('One of your rules has no characteristic value selected.');
									}
							}
						} else {
							$my_first_tab_errors[] = adminPhrase('One of your rules has no characteristic selected.');
						}
					}
				}
		
				//Clear out any data from hidden fields
				if ($saving) {
					$liveIndicies = array();
					foreach (explode(',', $values['first_tab/indexes']) as $index) {
						if ($index) {
							$liveIndicies[(int) $index] = true;
						}
					}
			
					$index = 0;
					$foundIndex = true;
					while ($foundIndex) {
						++$index;
						$foundIndex = !empty($liveIndicies[$index]);
				
						if (!empty($values['first_tab/rule_group_picker_' . $index])
						 || !empty($values['first_tab/rule_logic_' . $index])) {
							$foundIndex = true;
					
							if (empty($liveIndicies[$index])
							 || empty($values['first_tab/rule_type_' . $index])
							 || $values['first_tab/rule_type_' . $index] != 'group') {
								$values['first_tab/rule_group_picker_' . $index] = '';
								$values['first_tab/rule_logic_' . $index] = '';
							}
						}
						
						if (!empty($values['first_tab/rule_characteristic_picker_' . $index])
						 || !empty($values['first_tab/rule_characteristic_values_picker_' . $index])) {
							$foundIndex = true;
						
							if (empty($liveIndicies[$index])
							 || empty($values['first_tab/rule_type_' . $index])
							 || $values['first_tab/rule_type_' . $index] != 'characteristic') {
								$values['first_tab/rule_characteristic_picker_' . $index] = '';
								$values['first_tab/rule_characteristic_values_picker_' . $index] = '';
							}
						}
						
						if (empty($liveIndicies[$index])
						 && isset($values['first_tab/rule_type_' . $index])) {
							$values['first_tab/rule_type_' . $index] = '';
						}
					}
				}
		}
	}
}