<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


$updatedProperties = [];
$updatedSettings = [];

if (!empty($tags['tabs'])
 && is_array($tags['tabs'])) {
	foreach ($tags['tabs'] as $tabName => $tab) {
		if (!empty($tab['fields'])
		 && is_array($tab['fields'])) {
			foreach ($tab['fields'] as $fieldName => $field) {
				if (is_array($field)) {
					if (!empty($field['plugin_setting']['name'])) {
				
						$ps = $field['plugin_setting'];
						$name = $ps['name'];
				
						$defaultValue = '';
						if (isset($ps['value'])) {
							$defaultValue = $ps['value'];
						} elseif (isset($field['value'])) {
							$defaultValue = $field['value'];
						}
				
						//Don't save a value for a field if it was hidden...
						if (empty($ps['save_value_when_hidden'])
						 && (ze\ring::engToBoolean($tab['hidden'] ?? false)
						  || ze\ring::engToBoolean($tab['_was_hidden_before'] ?? false)
						  || ze\ring::engToBoolean($field['hidden'] ?? false)
						  || ze\ring::engToBoolean($field['_was_hidden_before'] ?? false))) {
					
							if ($ps['save_empty_value_when_hidden'] ?? $defaultValue) {
								//If a setting has a default value, we'll need to store a blank in the database
								//to make it clear that the field was hidden and not set
								$updatedSettings[$name] = '';
							} else {
								//Otherwise we can just delete the row
								$updatedSettings[$name] = null;
							}

						//...or fields that have not changed, and have the "dont_save_default_value"
						//option set.
						} else
						if (!empty($ps['dont_save_default_value'])
						 && $defaultValue
						 && (!isset($field['current_value'])
						  || $field['current_value'] == $defaultValue)) {
							$updatedSettings[$name] = null;

						} else {
							$updatedSettings[$name] = $values[$tabName. '/'. $fieldName];
						}
					}
				}
			}
		}
	}
}


//$tags['reload_parent'] = true;
$tags['close_popout'] = true;
$tags['stop_flow'] = true;
$tags['js'] = '
	lib.closeAndUpdateSlot('. json_encode($updatedProperties, JSON_FORCE_OBJECT). ', '. json_encode($updatedSettings, JSON_FORCE_OBJECT). ', '. json_encode($switchBoxes, JSON_FORCE_OBJECT). ');';