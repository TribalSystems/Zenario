<?php
/*
 * Copyright (c) 2021, Tribal Limited
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
	FROM ". DB_PREFIX. "template_slot_link
	WHERE family_name = '". \ze\escape::sql(\ze::$templateFamily). "'
	  AND file_base_name = '". \ze\escape::sql(\ze::$templateFileBaseName). "'";
$result = \ze\sql::select($sql);

$newSlots = [];
$missingSlots = [];
$existingSlots = [];
while($row = \ze\sql::fetchAssoc($result)) {
	$existingSlots[$row['slot_name']] = true;
}

//Look through \ze::$slotContents, looking for newly found slots or missing slots
foreach (\ze::$slotContents as $slotName => &$details) {
	if (!empty($details['found']) && empty($existingSlots[$slotName])) {
		$newSlots[] = $slotName;
	} elseif (empty($details['found']) && !empty($existingSlots[$slotName])) {
		$missingSlots[] = $slotName;
	}
}

//Remove any missing slots from the database
foreach($missingSlots as $slotName) {
	$sql = "
		DELETE FROM ". DB_PREFIX. "template_slot_link
		WHERE slot_name = '". \ze\escape::sql($slotName). "'
		  AND family_name = '". \ze\escape::sql(\ze::$templateFamily). "'
		  AND file_base_name = '". \ze\escape::sql(\ze::$templateFileBaseName). "'";
	\ze\sql::update($sql);
}

//Add any new slots to the database
foreach($newSlots as $slotName) {
	$sql = "
		REPLACE INTO ". DB_PREFIX. "template_slot_link SET
			slot_name = '". \ze\escape::sql($slotName). "',
			family_name = '". \ze\escape::sql(\ze::$templateFamily). "',
			file_base_name = '". \ze\escape::sql(\ze::$templateFileBaseName). "'";
	\ze\sql::update($sql);
}

//Refresh the page to show the new slots, if needed
if (!empty($newSlots)) {
	echo '
		<script type="text/javascript">
			document.location.href = "', \ze\escape::js(\ze\link::toItem(\ze::$cID, \ze::$cType, true, '', \ze::$alias, true)), '";
		</script>';

} else {
	
	$first = true;
	foreach (\ze::$slotContents as $slot => &$details) {
		if (!isset($details['used']) && !isset($details['error'])
		 && isset($details['instance_id']) && $details['instance_id']
		 && isset($details['class']) && $details['class']
		 && empty($details['egg_id'])) {
			
			if ($first) {
				$first = false;
				echo '
					<div class="zenario_missing_slots">
						<h1 class="zenario_missing_slots_title">', \ze\admin::phrase('Missing Slots'), '</h1>
						<p class="zenario_missing_slots_message">',
							\ze\admin::phrase('The following Plugin(s) were added to Slots that are now missing from your Template file. You should either move each Plugin to another Slot, or remove each Plugin from the Slot.'),
						'</p>';
			}
			
			$unusedSlot = \ze\plugin::slot($slot);
				echo '<div class="slot zenario_missing_slot">';
					$unusedSlot->show();
				echo '</div>';
			$unusedSlot->end();
			
			\ze::$slotContents[$slot]['missing'] = true;
		}
	}
	
	if (!$first) {
		echo '</div>';
	}
}

?>