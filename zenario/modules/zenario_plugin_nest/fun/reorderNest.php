<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

if (!$keepTabsOneToOneWithPlugins) {
	//Change the ordinal of the Tabs and Eggs to exactly the ordinal that was submitted.
	//We will then rely on the resync function to sort out any quirks
	foreach (explode(',', $ids) as $id) {
		if (!empty($_POST['ordinals'][$id])) {
			
			$tabOrd = explode('.', $_POST['ordinals'][$id]);
			
			updateRow(
				'nested_plugins',
				array('tab' => arrayKey($tabOrd, 0), 'ord' => arrayKey($tabOrd, 1)),
				array('id' => $id, 'instance_id' => post('refiner__nest')));
		}
	}

} else {
	//Change the ordinal of the Tabs and Eggs and eggs together, making sure to keep each egg next to each tab
	//Do this by looking out for each ordinal in the request that has changed, and updating both the egg and tab
	//with the new ordinal
	$idsToTabs = array();
	$tabsToIds = array();
	
	$result = getRows('nested_plugins', array('id', 'tab'), array('instance_id' => post('refiner__nest')));
	while ($row = sqlFetchAssoc($result)) {
		$idsToTabs[$row['id']] = $row['tab'];
		
		if (empty($tabsToIds[$row['tab']])) {
			$tabsToIds[$row['tab']] = array();
		}
		$tabsToIds[$row['tab']][] = $row['id'];
	}
	
	
	
	$changedTabs = array();
	foreach (explode(',', $ids) as $id) {
		if (!empty($_POST['ordinals'][$id])
		 && isset($idsToTabs[$id])) {
			
			$tabOrd = explode('.', $_POST['ordinals'][$id]);
			$tab = (int) arrayKey($tabOrd, 0);
			
			if ($tab != $idsToTabs[$id]) {
				foreach ($tabsToIds[$idsToTabs[$id]] as $id2) {
					updateRow('nested_plugins', array('tab' => $tab), $id2);
				}
			}
		}
	}
}

