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


switch ($path) {
	case 'plugin_settings':
		
		if (!empty($fields['first_tab/alt_tag'])
		 && empty($fields['first_tab/alt_tag']['hidden'])
		 && $changes['first_tab/alt_tag']
		 && !$values['first_tab/alt_tag']) {
			$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter an alt-tag.');
		}
		
		if (!empty($fields['first_tab/floating_box_title'])
		 && empty($fields['first_tab/floating_box_title']['hidden'])
		 && $changes['first_tab/floating_box_title']
		 && !$values['first_tab/floating_box_title']) {
			$box['tabs']['first_tab']['errors'][] = adminPhrase('Please enter a floating box title attribute.');
		}
		
		//Convert all absolute URLs in the HTML Text to relative URLs when saving
		foreach (array('value', 'current_value') as $value) {
			if (isset($box['tabs']['text']['fields']['text'][$value])) {
				foreach (array('"', "'") as $quote) {
					$box['tabs']['text']['fields']['text'][$value] = 
						str_replace(
							$quote. htmlspecialchars(absCMSDirURL()),
							$quote,
							$box['tabs']['text']['fields']['text'][$value]);
				}
			}
		}

		break;
}