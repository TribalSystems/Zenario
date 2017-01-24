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


$desc = '';
$rules = getRowsArray(
	'smart_group_rules',
	array('field_id', 'field2_id', 'field3_id', 'field4_id', 'field5_id', 'not', 'value'),
	array('smart_group_id' => $smartGroupId),
	'ord'
);

if (empty($rules)) {
	return adminPhrase('All users and contacts');
}


$or = count($rules) > 1
   && getRow('smart_groups', 'must_match', $smartGroupId) == 'any';


foreach ($rules as $rule) {
	
	//Check if a field is set, load the details, and check if it's a supported field. Only add it if it is.
	if ($rule['field_id']
	 && ($field = getDatasetFieldBasicDetails($rule['field_id']))
	 && (in($field['type'], 'group', 'checkbox', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
		
		if ($field['is_system_field'] && $field['label'] == '') {
			$field['label'] = $field['default_label'];
		}
		
		if ($desc !== '') {
			if ($or) {
				$desc .= ' or ';
			} else {
				$desc .= '; ';
			}
		}
		
		if ($field['type'] == 'group') {
			if ($rule['not']) {
				$desc .= 'Not a member of '. $field['label'];
			} else {
				$desc .= 'Member of '. $field['label'];
		
				//If you filter by group, an "OR" logic containing multiple groups is allowed.
				//Check if multiple groups have been picked...
				$groups = array();
				if ($field['type'] == 'group') {
					if ($rule['field2_id']) $groups[] = $rule['field2_id'];
					if ($rule['field3_id']) $groups[] = $rule['field3_id'];
					if ($rule['field4_id']) $groups[] = $rule['field4_id'];
					if ($rule['field5_id']) $groups[] = $rule['field5_id'];
				}
				
				//...if so, list these extra groups too
				if (!empty($groups)) {
					$lastI = count($groups) - 1;
					foreach ($groups as $i => $groupId) {
						if ($i == $lastI) {
							$desc .= ' or ';
						} else {
							$desc .= ', ';
						}
						$desc .= getRow('custom_dataset_fields', 'label', $groupId);
					}
				}
			}
			
		} else {
			$desc .= $field['label'];
			
			if ($rule['not']) {
				$desc .= ' is not ';
			} else {
				$desc .= ' is ';
			}
			
			switch ($field['type']) {
				case 'checkbox':
					$desc .= 'set';
					break;
				
				//List of values work via a numeric value id
				case 'radios':
				case 'select':
					$desc .= '"'. getRow('custom_dataset_field_values', 'label', $rule['value']). '"';
					break;
				
				//Centralised lists work via a text value
				default:
					$desc .= '"'. $rule['value']. '"';
			}
		}
	}
}



return $desc;
