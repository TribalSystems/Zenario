<?php
/*
 * Copyright (c) 2024, Tribal Limited
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
						\ze\admin::phrase('The following plugin(s) were added to slots that are now missing from your layout. You should either move each plugin to another slot, or remove each plugin from the slot.'),
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