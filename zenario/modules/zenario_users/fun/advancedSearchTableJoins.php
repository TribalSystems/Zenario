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

$joins = array();
$escaped_tablePrefix = sqlEscape($tablePrefix);

foreach (explode(',', arrayKey($values, 'first_tab','indexes')) as $index) {
	
	$postfix = $escaped_tablePrefix . "_"  . (int) $index;
	$as = "ucdb_inc" . $postfix;
	
	if (arrayKey($values, 'first_tab','rule_type_' . $index) == 'characteristic') {
		if ($C = zenario_users::getCharacteristic( arrayKey($values, 'first_tab','rule_characteristic_picker_' . $index) )) {
			
			$uTable = $uIdColumn = '';
			if($C['is_system_field']){
				$uTable = 'users';
				$uIdColumn = 'id';
			} else {
				$uTable = 'users_custom_data';
				$uIdColumn = 'user_id';
			}
			
			switch ($C['type']) {
				case 'boolean':
				case 'group':
					$joins[] = "
							INNER JOIN "
							. DB_NAME_PREFIX . $uTable . " AS ". $as . "
							ON
									". $as . "." . $uIdColumn . " = u.id
								AND ". $as . ".`" . $C['db_column'] . "`= 1 ";
					break;
					
				case 'list_single_select':
					if ($CValueIds = arrayKey($values, 'first_tab','rule_characteristic_values_picker_' . $index)) {
						if (!empty($CValueIds)) {
							$joins[] = "
									INNER JOIN "
									. DB_NAME_PREFIX . $uTable . " AS ". $as . "
									ON
											". $as . "." . $uIdColumn . " = u.id 
											AND  ". $as . ".`" . $C['db_column'] . "` IN("
															. inEscape($CValueIds) . ") ";
						}
					}
					break;
					
				case 'list_multi_select':
					$CValues = arrayKey($values, 'first_tab','rule_characteristic_values_picker_' . $index);
					if ($CValues && (count(explode(',',$CValues)) >= 1)) {
						switch (arrayKey($values, 'first_tab','rule_logic_' . $index)) {
							case 'all':
								foreach (explode(',', $CValues) as $value) {
									$escaped_tablePrefix_idx_value = $postfix . "_" . (int) $value;
									$joins[] = "
										INNER JOIN "
										. DB_NAME_PREFIX . "custom_dataset_values_link AS ascul_inc_" . $postfix . "
										ON
												ascul_inc_" . $postfix . ".linking_id = u.id
											AND ascul_inc_" . $postfix . ".value_id = " . (int) $value;
									
								}
								break;
							case 'any':
							default:
								$joins[] = "
										INNER JOIN "
										. DB_NAME_PREFIX . "custom_dataset_values_link AS ascul_inc_" . $postfix . "
										ON
												ascul_inc_" . $postfix . ".linking_id = u.id
											AND ascul_inc_" . $postfix . ".value_id IN (" . inEscape($CValues) . ")";
								break;
						}
					}
					break;
			}
		}
	}
	
	if (arrayKey($values, 'first_tab' , 'rule_type_' . $index) == 'group') {
		$groups = arrayKey($values, 'first_tab', 'rule_group_picker_' . $index);
		if ($groups && (count(explode(',', $groups)) >= 1)) {

			$sql = "
					INNER JOIN "
					. DB_NAME_PREFIX . "users_custom_data AS ". $as . "
					ON
							". $as . ".user_id = u.id ";
			
			switch (arrayKey($values, 'first_tab' , 'rule_logic_' . $index)) {
				case 'all':
							
					foreach (explode(',', $groups) as $group) {
						$G = zenario_users::getCharacteristic( $group );
						if(isset($G['db_column']) && $G['db_column'] && !$G['is_system_field']) {
							$sql .= " AND ". $as . ".`" . preg_replace('@\W@', '', $G['db_column']) . "`= 1 ";
						}
					}
					$joins[] = $sql;
					break;
				case 'any':
				default:

					$sql .= " AND (";
					$loop_count = 0;
					foreach (explode(',', $groups) as $group) {
						
						if (!$loop_count) {
							$sql .= "1=1";
						}
						
						if ($G = zenario_users::getCharacteristic( $group )) {
							if($loop_count) {
								$sql .= " OR ";
							}
							if(isset($G['db_column']) && $G['db_column'] && !$G['is_system_field']) {
								if (!$loop_count) {
									$sql .= " AND";
								}
								$sql .= " ". $as . ".`" . preg_replace('@\W@', '', $G['db_column']) . "`= 1 ";
								$loop_count++;
							}
						}
						
					}
					$joins[] = $sql . ") ";
			}
		}
	}

}

if (arrayKey($values, 'exclude','enable') ) {
	if(arrayKey($values, 'exclude','rule_type') == 'characteristic') {
		if ($C = zenario_users::getCharacteristic( arrayKey($values, 'exclude','rule_characteristic_picker') )) {
			$uTable = "users_custom_data";
			$uIdColumn = "user_id";
			if($C['is_system_field']) {
				$uTable = "users";
				$uIdColumn = "id";
			}
			
			switch ($C['type']) {
				case 'group':
				case 'boolean':
					$joins[] = "
							LEFT JOIN "
							. DB_NAME_PREFIX . $uTable . " AS ascul_excl_" . $escaped_tablePrefix . "
							ON
									ascul_excl_" . $escaped_tablePrefix . "." . $uIdColumn . " = u.id
								AND ascul_excl_" . $escaped_tablePrefix . ".`" . $C['db_column'] . "`= 1 ";
					break;
					
				case 'list_single_select':
					$CValues = arrayKey($values, 'exclude','rule_characteristic_values_picker');
					if ($CValues) {
						$joins[] = "
								INNER JOIN "
								. DB_NAME_PREFIX . $uTable . " AS ascul_excl_" . $postfix . "
								ON
										ascul_excl_" . $postfix . "." . $uIdColumn . " = u.id
									AND ascul_excl_" . $postfix . ".`" . $C['db_column'] . "` NOT IN("
														. inEscape($CValues) . ") ";
								break;
					}
					break;
					
				case 'list_multi_select':
					$CValues = arrayKey($values, 'exclude','rule_characteristic_values_picker');
					if ($CValues && (count(explode(',',$CValues)) >= 1)) {
						switch (arrayKey($values, 'exclude','rule_logic')) {
							case 'all':
								foreach (explode(',', $CValues) as $value) {
									$escaped_tablePrefix_value = $escaped_tablePrefix . "_"  . (int) $value;
									$joins[] = "
										INER JOIN "
											. DB_NAME_PREFIX . "custom_dataset_values_link AS ascul_excl_" . $escaped_tablePrefix_value . "
											ON
												ascul_excl_" . $postfix . ".linking_id = u.id
											AND ascul_excl_" . $postfix . ".value_id <> " . (int) $value;
													
								}
								break;
							case 'any':
							default:
								$joins[] = "
										INNER JOIN "
										. DB_NAME_PREFIX . "custom_dataset_values_link AS ascul_excl_" . $escaped_tablePrefix . "
										ON
													ascul_excl_" . $postfix . ".linking_id = u.id
												AND ascul_excl_" . $postfix . ".value_id NOT IN (" . inEscape($CValues) . ")";
								break;
						}
					}
					break;
			}
		}
	}
	
	if(arrayKey($values, 'exclude' , 'rule_type') == 'group') {
		$groups = arrayKey($values, 'exclude', 'rule_group_picker');
		if ($groups && (count(explode(',', $groups)) >= 1)) {
			
			$sql = "
					INNER JOIN "
					. DB_NAME_PREFIX . "users_custom_data AS ucdb_excl" . $postfix . "
					ON
							ucdb_excl" . $postfix . ".user_id = u.id ";
				
			switch (arrayKey($values, 'exclude' , 'rule_logic_' . $index)) {
				case 'all':
						
					foreach (explode(',', $groups) as $group) {
						$G = zenario_users::getCharacteristic( $group );
						if(isset($G['db_column']) && $G['db_column'] && !$G['is_system_field']) {
							$sql .= " AND ucdb_excl" . $postfix . ".`" . preg_replace('@\W@', '', $G['db_column']) . "`= 0 ";
						}
					}
					$joins[] = $sql;
					break;
				case 'any':
				default:
			
					$sql .= " AND NOT (";
					$loop_count = 0;
					foreach (explode(',', $groups) as $group) {
						$G = zenario_users::getCharacteristic( $group );
						if(isset($G['db_column']) && $G['db_column'] && !$G['is_system_field']) {
							if($loop_count) {
								$sql .= " OR ";
							}
							$sql .= " ucdb_excl" . $postfix . ".`" . preg_replace('@\W@', '', $G['db_column']) . "`= 1 ";
							$loop_count++;
						}
					}
					if(!$loop_count) {
						$sql .= "1=2";
					}
					$joins[] = $sql . ") ";
			}
		}
	}
}

return $joins;
