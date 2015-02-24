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

$sql = '';
$escaped_tablePrefix = sqlEscape($tablePrefix);

if (arrayKey($values, 'exclude','enable') && (arrayKey($values, 'exclude','rule_type') == 'group')) {
	$groups = arrayKey($values, 'exclude','rule_group_picker');
	if ($groups && (count(explode(',', $groups)) >= 1)) {
		// removed because gul_excl_ was not found in the query and caused an error
		/*
		switch (arrayKey($values, 'exclude','rule_logic')) {
			case 'all':
				foreach (explode(',', $groups) as $group) {
					$sql .= " AND gul_excl_" . $escaped_tablePrefix . "_" . (int) $group . ".user_id IS NULL ";
				}
				break;
			case 'any':
			default:
				$sql = " AND gul_excl_" . $escaped_tablePrefix . ".user_id IS NULL ";
				break;
		}
		*/
	}
}

if (arrayKey($values, 'exclude','enable') && (arrayKey($values, 'exclude','rule_type') == 'characteristic')) {
	if ($C = zenario_users::getCharacteristic( arrayKey($values, 'exclude','rule_characteristic_picker') )) {
		switch ($C['type']) {
			case 'boolean':
				$sql = " AND ascul_excl_" . $escaped_tablePrefix . ".user_id IS NULL ";
				break;
			case 'list_single_select':
			case 'list_multi_select':
				$CValues = arrayKey($values, 'exclude','rule_characteristic_values_picker');
				if ($CValues && (count(explode(',',$CValues)) >= 1)) {
					switch (arrayKey($values, 'exclude','rule_logic')) {
						case 'all':
							foreach (explode(',', $CValues) as $value) {
								$sql .= " AND ascul_excl_" . $escaped_tablePrefix . "_" . (int) $value . ".user_id IS NULL ";
							}
							break;
						case 'any':
						default:
							$sql = " AND ascul_excl_" . $escaped_tablePrefix . ".user_id IS NULL ";
							break;
					}
				}
				break;
		}
	}
}

return $sql;
