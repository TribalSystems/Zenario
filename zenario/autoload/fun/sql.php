<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

//function smartGroupSQL(&$and, &$tableJoins, $smartGroupId, $list = true, $usersTableAlias = 'u', $customTableAlias = 'ucd')


//A little hack - allow an array of rules to be passed in instead of an id,
//in order to test rules that aren't yet in the database
if (is_array($smartGroupId)) {
	
	if (empty($smartGroupId)) {
		$and = ze\smartGroup::NOT_IN_SMART_GROUP;
		return false;
	}
	
	$rules = $smartGroupId;
	$smartGroup = $rules[0];

} else {
	if (!$smartGroup = \ze\row::get('smart_groups', ['intended_usage', 'must_match'], $smartGroupId)) {
		$and = ze\smartGroup::NOT_IN_SMART_GROUP;
		return false;
	}

	$rules = \ze\row::getAssocs(
		'smart_group_rules',
		['type_of_check', 'field_id', 'field2_id', 'field3_id', 'field4_id', 'field5_id', 'role_id','activity_band_id', 'not', 'value'],
		['smart_group_id' => $smartGroupId],
		'ord'
	);
}

//If there are no rules, newsletter groups should return everyone, but permissions groups should return nobody.
if (empty($rules)) {
	if ($smartGroup['intended_usage'] == 'smart_newsletter_group') {
		$and = "AND TRUE";
		return true;
	} else {
		$and = ze\smartGroup::NOT_IN_SMART_GROUP;
		return false;
	}
}

$or = count($rules) > 1 && $smartGroup['must_match'] == 'any';
$firstAndOr = true;
$hadNoRules = true;

if ($or) {
	$leftOrInnerJoin = "LEFT JOIN ";
} else {
	$leftOrInnerJoin = "INNER JOIN ";
}

$i = 0;
foreach ($rules as $rule) {
	++$i;
	
	if (!$or) {
		$andOr = "
			AND ";
	
	} elseif ($firstAndOr) {
		$andOr = "
			AND (";
	
	} else {
		$andOr = "
			OR ";
	}
	$valid = false;
	$typeOfCheck = $rule['type_of_check'];
	
	
	
	switch ($typeOfCheck) {
		
		//Handle rules on user-fields
		case 'user_field':
			
			//Validate this rule first
				//Check if a field is set, load the details, and check if it's a supported field. Only add it if it is.
			if ($rule['field_id']
			 && ($field = \ze\dataset::fieldBasicDetails($rule['field_id']))
			 && (\ze::in($field['type'], 'group', 'checkbox', 'consent', 'radios', 'centralised_radios', 'select', 'centralised_select'))) {
				
				$hashed = !\ze::in($field['type'], 'group', 'checkbox', 'consent') && \ze::$dbL->columnIsHashed($field['is_system_field']? 'users' : 'users_custom_data', $field['db_column']);
				
				//Work out the table alias and column name
				$col = "`". \ze\escape::sql($field['is_system_field']? $usersTableAlias : $customTableAlias). "`.`". ($hashed? '#' : ''). \ze\escape::sql($field['db_column']). "`";
		
				//If you filter by group, an "OR" logic containing multiple groups is allowed.
				//Check if multiple groups have been picked...
				$groups = [];
				if ($field['type'] == 'group') {
					if ($rule['field2_id']) $groups[] = $rule['field2_id'];
					if ($rule['field3_id']) $groups[] = $rule['field3_id'];
					if ($rule['field4_id']) $groups[] = $rule['field4_id'];
					if ($rule['field5_id']) $groups[] = $rule['field5_id'];
				}
		
				//...if so, handle this using an IN() statement
				if (!empty($groups)) {
			
					$and .= $andOr. "1 IN (". $col;
			
					foreach ($groups as $fieldNId) {
						if ($fieldN = \ze\row::get(
							'custom_dataset_fields',
							['is_system_field', 'db_column'],
							['id' => $fieldNId, 'type' => 'group']
						)) {
							$and .= ", `". \ze\escape::sql($fieldN['is_system_field']? $usersTableAlias : $customTableAlias). "`.`". \ze\escape::sql($fieldN['db_column']). "`";
						}
					}
			
					$and .= ")";
			
				} else {
					if ($hashed) {
						$check = $col. " = '". \ze\escape::sql(\ze\db::hashDBColumn($rule['value'])). "'";
					
					} else {
						switch ($field['type']) {
							//Groups and checkboxes are handled by a tinyint column
							case 'group':
							case 'checkbox':
							case 'consent':
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
								$check = $col. " = '". \ze\escape::sql($rule['value']). "'";
						}
					}
			
					if ($rule['not']) {
						$and .= $andOr. "NOT (". $check. " AND ". $col. " IS NOT NULL)";
					} else {
						$and .= $andOr. $check;
					}
				}
				$firstAndOr = false;
				$valid = true;
			}
			break;
			
		
		//Logic to handle location permissions, and roles at locations
		case 'role':
			
			//Validate this rule first
				//Roles need a role_id chosen in the settings
			if ($rule['role_id']
				//Permission checks need a specific locationId in the URL to check against
			 && ($list || ($locationId = \ze::$vars['locationId']))
				//Oh and the Org Manager module must be installed and running
			 && ($ZENARIO_ORGANIZATION_MANAGER_PREFIX = \ze\module::prefix('zenario_organization_manager', $mustBeRunning = true))) {
				
				$tableJoins .= "
					". $leftOrInnerJoin. DB_PREFIX. $ZENARIO_ORGANIZATION_MANAGER_PREFIX. "user_role_location_link AS urll_". $i. "
					   ON urll_". $i. ".user_id = `". \ze\escape::sql($usersTableAlias). "`.id
					  AND urll_". $i. ".role_id = ". (int) $rule['role_id'];
				
				//If we're just listing users who might be able to see a permission,
				//list any user with that role set at any location.
				//However for permissions checks, check the specific location from the URL.
				if (!$list) {
					$and .= "
					  AND urll_". $i. ".location_id = ". (int) $locationId;
				}
				
				if ($or) {
					$and .= "
						  ". $andOr. " urll_". $i. ".user_id IS NOT NULL";
					$firstAndOr = false;
				}
				
				
				$valid = true;
			}
			break;
		
		case 'activity_band':
			if($ZENARIO_USER_ACTIVITY_BANDS_PREFIX = \ze\module::prefix('zenario_user_activity_bands', $mustBeRunning = true)){
				$tableJoins .= "
				". $leftOrInnerJoin. DB_PREFIX. $ZENARIO_USER_ACTIVITY_BANDS_PREFIX. "user_activity_bands_link AS uabl_". $i. "
				ON uabl_".$i.".user_id = `". \ze\escape::sql($usersTableAlias). "`.id";				
				
				if($rule['not']){
					$and .= "
						  ". $andOr. " uabl_". $i. ".band_id != ".$rule['activity_band_id'];
					$firstAndOr = false;
				}else{
					$and .= "
						  ". $andOr. " uabl_". $i. ".band_id = ".$rule['activity_band_id'];
					$firstAndOr = false;
				}
				$valid = true;
			}
			break;
	}
	
	//If the rule was not valid for whatever reason, always mark it as failed.
	//For OR logic we can keep going, looking for more rules that might match,
	//but for AND logic we should stop straight away
	if (!$valid) {
		if (!$or) {
			$and = ze\smartGroup::NOT_IN_SMART_GROUP;
			return false;
		}
	} else {
		$hadNoRules = false;
	}
}

//Catch the case where there were no valid rules.
//(This should have been caught above, but just in case it wasn't I've added a line here.)
if ($hadNoRules) {
	$and = ze\smartGroup::NOT_IN_SMART_GROUP;
	return false;
}

if ($or) {
	$and .= ")";
}

//Permissions checks will always include a line to check the user is active,
//this is hard-coded into the logic.
if (!$list) {
	$and .= "
		AND `". \ze\escape::sql($usersTableAlias). "`.`status` = 'active'";
}

return true;