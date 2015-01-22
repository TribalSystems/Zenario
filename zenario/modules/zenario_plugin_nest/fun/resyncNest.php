<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


$key = array('instance_id' => $instanceId);

switch ($mode) {
	case 'no_tabs':
		//Delete any tabs
		$key['is_tab'] = 1;
		deleteRow('nested_plugins', $key);
		
		break;
	
	
	case 'at_least_one_tab':
		//Ensure there is at least one tab in the nest
		$key['is_tab'] = 1;
		if (!checkRowExists('nested_plugins', $key)) {
			self::addTab($instanceId);
		}
		
		break;
	
	
	case 'one_tab_per_plugin':
		//Look for tabs
		$lastTabNo = false;
		$lastEggId = false;
		$tabsToAdd = array();
		$tabsToInsert = array();
		
		$result = getRows('nested_plugins', array('id', 'is_tab', 'tab'), $key, array('tab', 'ord'));
		while ($row = sqlFetchAssoc($result)) {
			$key['tab'] = $row['tab'];
			
			if ($row['is_tab']) {
				//Remove any tabs that do not have Plugins with them
				$key['is_tab'] = 0;
				if (!checkRowExists('nested_plugins', $key)) {
					deleteRow('nested_plugins', $row['id']);
				}
			
			} else {
				//Add tabs for Plugins that do not have them
				$key['is_tab'] = 1;
				if ($lastTabNo !== $key['tab'] && !checkRowExists('nested_plugins', $key)) {
					$tabsToAdd[] = $key['tab'];
				
				} elseif ($lastTabNo === $key['tab'] && $lastEggId) {
					$tabsToInsert[] = $lastEggId;
				}
				
				$lastTabNo = $row['tab'];
				$lastEggId = $row['is_tab']? false : $row['id'];
			}
		}
		
		//Add in completely missing tabs
		foreach ($tabsToAdd as $newTabNum) {
			self::addTab($instanceId, $title = false, $newTabNum);
		}
		
		//Insert extra tabs where there was more than one Plugin per tab, by adding a new tab
		//and then bumping all the other numbers up apart from the first Plugin and Tab on that ordinal
		foreach ($tabsToInsert as $lastEggId) {
			$oldTabNum = getRow('nested_plugins', 'tab', $lastEggId);
			$newTabId = self::addTab($instanceId, $title = false, $oldTabNum + 1);
			
			$sql = "
				UPDATE ". DB_NAME_PREFIX. "nested_plugins SET
					tab = tab + 1
				WHERE instance_id = ". (int) $instanceId. "
				  AND id NOT IN (". (int) $lastEggId. ", ". (int) $newTabId. ")
				  AND (is_tab, tab) NOT IN ((1, ". (int) $oldTabNum. "))
				  AND tab >= ". (int) $oldTabNum;
			sqlSelect($sql);  //No need to check the cache as the other statements should clear it correctly
		}
		
		break;
}


//Look through a Plugin Nest, and ensure that all of the tab and ordinal numbers are valid by overwriting them
$tab = 0;
$ord = 0;

$sql = "
	SELECT id, tab, ord, is_tab
	FROM ". DB_NAME_PREFIX. "nested_plugins
	WHERE instance_id = ". (int) $instanceId. "
	ORDER BY tab, ord";

$result = sqlQuery($sql);
while ($row = sqlFetchAssoc($result)) {
	if ($row['is_tab']) {
		//Catch the case where a Plugin was moved before the first tab
		if ($tab) {
			//If this is a new tab, reset the ordinal
			$ord = 0;
		}
		++$tab;
		$thisOrd = 0;
	} else {
		$thisOrd = ++$ord;
	}
	
	updateRow(
		'nested_plugins',
		array('tab' => ifNull($tab, 1), 'ord' => $thisOrd),
		array('instance_id' => $instanceId, 'id' => $row['id']));
}


?>