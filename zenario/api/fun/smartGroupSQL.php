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

//$smartGroupId, $usersTableAlias = 'u', $customTableAlias

$sql = '';
$first = true;

//A little hack - allow an array of rules to be passed in instead of an id,
//in order to test rules that aren't yet in the database
if (is_array($smartGroupId)) {
	$rules = $smartGroupId;
	$or = arrayKey($rules, 1, 'must_match') == 'any';

} else {
	$rules = getRowsArray(
		'smart_group_rules',
		array('field_id', 'field2_id', 'field3_id', 'field4_id', 'field5_id', 'not', 'value'),
		array('smart_group_id' => $smartGroupId),
		'ord'
	);
	$or = count($rules) > 1
	   && getRow('smart_groups', 'must_match', $smartGroupId) == 'any';
}


foreach ($rules as $rule) {
	//Check if a field is set, load the details, and check if it's a supported field. Only add it if it is.
	if ($rule['field_id']
	 && ($field = getDatasetFieldBasicDetails($rule['field_id']))
	 && (in($field['type'], 'group', 'checkbox', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
		
		if (!$or) {
			$and = "
				AND ";
		
		} elseif ($first) {
			$and = "
				AND (";
		
		} else {
			$and = "
				OR ";
		}
		
		//Work out the table alias and column name
		$col = "`". sqlEscape($field['is_system_field']? $usersTableAlias : $customTableAlias). "`.`". sqlEscape($field['db_column']). "`";
		
		//If you filter by group, an "OR" logic containing multiple groups is allowed.
		//Check if multiple groups have been picked...
		$groups = array();
		if ($field['type'] == 'group') {
			if ($rule['field2_id']) $groups[] = $rule['field2_id'];
			if ($rule['field3_id']) $groups[] = $rule['field3_id'];
			if ($rule['field4_id']) $groups[] = $rule['field4_id'];
			if ($rule['field5_id']) $groups[] = $rule['field5_id'];
		}
		
		//...if so, handle this using an IN() statement
		if (!empty($groups)) {
			
			$sql .= $and. "1 IN (". $col;
			
			foreach ($groups as $fieldNId) {
				if ($fieldN = getRow(
					'custom_dataset_fields',
					array('is_system_field', 'db_column'),
					array('id' => $fieldNId, 'type' => 'group')
				)) {
					$sql .= ", `". sqlEscape($fieldN['is_system_field']? $usersTableAlias : $customTableAlias). "`.`". sqlEscape($fieldN['db_column']). "`";
				}
			}
			
			$sql .= ")";
			
		} else {
			switch ($field['type']) {
				//Groups and checkboxes are handled by a tinyint column
				case 'group':
				case 'checkbox':
					$check = $col. " = 1";
					break;
				
				//List of values work via a numeric value id
				case 'radios':
				case 'select':
					if ($rule['value'] == '') {
						continue 2;
					}
					$check = $col. " = ". (int) $rule['value'];
					break;
				
				//Centralised lists work via a text value
				default:
					if ($rule['value'] == '') {
						continue 2;
					}
					$check = $col. " = '". sqlEscape($rule['value']). "'";
			}
			
			if ($rule['not']) {
				$sql .= $and. "NOT (". $check. " AND ". $col. " IS NOT NULL)";
			} else {
				$sql .= $and. $check;
			}
		}
		$first = false;
	}
}

if ($or && !$first) {
	$sql .= ")";
}

return $sql;