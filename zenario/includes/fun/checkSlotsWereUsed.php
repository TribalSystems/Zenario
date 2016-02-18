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

//Look up the slots on the page
$sql = "
	SELECT slot_name
	FROM ". DB_NAME_PREFIX. "template_slot_link
	WHERE family_name = '". sqlEscape(cms_core::$templateFamily). "'
	  AND file_base_name = '". sqlEscape(cms_core::$templateFileBaseName). "'";
$result = sqlQuery($sql);

$newSlots = array();
$missingSlots = array();
$existingSlots = array();
while($row = sqlFetchAssoc($result)) {
	$existingSlots[$row['slot_name']] = true;
}

//Look through cms_core::$slotContents, looking for newly found slots or missing slots
foreach (cms_core::$slotContents as $slotName => &$details) {
	if (!empty($details['found']) && empty($existingSlots[$slotName])) {
		$newSlots[] = $slotName;
	} elseif (empty($details['found']) && !empty($existingSlots[$slotName])) {
		$missingSlots[] = $slotName;
	}
}

//Remove any missing slots from the database
foreach($missingSlots as $slotName) {
	$sql = "
		DELETE FROM ". DB_NAME_PREFIX. "template_slot_link
		WHERE slot_name = '". sqlEscape($slotName). "'
		  AND family_name = '". sqlEscape(cms_core::$templateFamily). "'
		  AND file_base_name = '". sqlEscape(cms_core::$templateFileBaseName). "'";
	sqlQuery($sql);
}

//Add any new slots to the database
foreach($newSlots as $slotName) {
	$sql = "
		REPLACE INTO ". DB_NAME_PREFIX. "template_slot_link SET
			slot_name = '". sqlEscape($slotName). "',
			family_name = '". sqlEscape(cms_core::$templateFamily). "',
			file_base_name = '". sqlEscape(cms_core::$templateFileBaseName). "'";
	sqlQuery($sql);
}

//Refresh the page to show the new slots, if needed
if (!empty($newSlots)) {
	echo '
		<script type="text/javascript">
			document.location.href = "', jsEscape(linkToItem(cms_core::$cID, cms_core::$cType, true, '', cms_core::$alias, true)), '";
		</script>';

} else {
	
	$first = true;
	foreach (cms_core::$slotContents as $slot => &$details) {
		if (!isset($details['used']) && !isset($details['error'])
		 && isset($details['instance_id']) && $details['instance_id']
		 && isset($details['class']) && $details['class']
		 && empty($details['egg_id'])) {
			
			if ($first) {
				$first = false;
				echo '
					<div class="zenario_missing_slots">
						<h1 class="zenario_missing_slots_title">', adminPhrase('Missing Slots'), '</h1>
						<p class="zenario_missing_slots_message">',
							adminPhrase('The following Plugin(s) were added to Slots that are now missing from your Template file. You should either move each Plugin to another Slot, or remove each Plugin from the Slot.'),
						'</p>';
			}
			
			$unusedSlot = slot($slot);
				echo '<div class="slot zenario_missing_slot">';
					$unusedSlot->show();
				echo '</div>';
			$unusedSlot->end();
			
			cms_core::$slotContents[$slot]['missing'] = true;
		}
	}
	
	if (!$first) {
		echo '</div>';
	}
}

?>