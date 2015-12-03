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


switch ($path) {
	case 'zenario_plugin_nest__tab':
		if (!empty($box['key']['id'])) {
			$details = getNestDetails($box['key']['id']);
			$instance = getPluginInstanceDetails($box['key']['instanceId']);
			
			if ($instance['content_id']) {
				$box['title'] = adminPhrase('Viewing/Editing the "[[tab]]" Tab in the [[nest]]',
									array('tab' => htmlspecialchars($details['name_or_title']), 'nest' => htmlspecialchars($instance['instance_name'])));
			
			} else {
				$box['title'] = adminPhrase('Viewing/Editing the "[[tab]]" Tab in the Nest "[[nest]]"',
									array('tab' => htmlspecialchars($details['name_or_title']), 'nest' => htmlspecialchars($instance['instance_name'])));
			}
		}
		
		break;
		
		
	case 'plugin_settings':
	
		if (isset($box['tabs']['size']['fields']['max_height'])) {
			$box['tabs']['size']['fields']['max_height']['hidden'] = 
				!$values['size/set_max_height'];
		}
		
		if (isset($box['tabs']['size']['fields']['banner_canvas'])) {
			
			$box['tabs']['size']['fields']['banner_width']['hidden'] = 
				!empty($box['tabs']['size']['fields']['banner_canvas']['hidden'])
			 || !in($values['size/banner_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
	
			$box['tabs']['size']['fields']['banner_height']['hidden'] = 
				!empty($box['tabs']['size']['fields']['banner_canvas']['hidden'])
			 || !in($values['size/banner_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
			
			if ($values['size/banner_canvas'] == 'fixed_width') {
				$box['tabs']['size']['fields']['banner_width']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
			
			} else {
				unset($box['tabs']['size']['fields']['banner_width']['note_below']);
			}

			if ($values['show_heading'] != 1) {
				$fields['heading_text']['hidden'] = true;
			} else {
				$fields['heading_text']['hidden'] = false;
			}

			
			if ($values['size/banner_canvas'] == 'fixed_height'
			 || $values['size/banner_canvas'] == 'fixed_width_and_height') {
				$box['tabs']['size']['fields']['banner_height']['note_below'] =
					adminPhrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
			
			} elseif ($values['size/banner_canvas'] == 'resize_and_crop') {
				$box['tabs']['size']['fields']['banner_height']['note_below'] =
					adminPhrase('Images may be scaled up or down maintaining aspect ratio.');
			
			} else {
				unset($box['tabs']['size']['fields']['banner_height']['note_below']);
			}
			
			if (isset($box['tabs']['size']['fields']['enlarge_canvas'])) {
				$box['tabs']['size']['fields']['enlarge_canvas']['hidden'] = 
					!$values['size/enlarge_image'];
				
				$box['tabs']['size']['fields']['enlarge_width']['hidden'] = 
					!empty($box['tabs']['size']['fields']['enlarge_canvas']['hidden'])
				 || !in($values['size/enlarge_canvas'], 'fixed_width', 'fixed_width_and_height', 'resize_and_crop');
		
				$box['tabs']['size']['fields']['enlarge_height']['hidden'] = 
					!empty($box['tabs']['size']['fields']['enlarge_canvas']['hidden'])
				 || !in($values['size/enlarge_canvas'], 'fixed_height', 'fixed_width_and_height', 'resize_and_crop');
				
				if ($values['size/enlarge_canvas'] == 'fixed_width') {
					$box['tabs']['size']['fields']['enlarge_width']['note_below'] =
						adminPhrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
				
				} else {
					unset($box['tabs']['size']['fields']['enlarge_width']['note_below']);
				}
				
				if ($values['size/enlarge_canvas'] == 'fixed_height'
				 || $values['size/enlarge_canvas'] == 'fixed_width_and_height') {
					$box['tabs']['size']['fields']['enlarge_height']['note_below'] =
						adminPhrase('Images may be scaled down maintaining aspect ratio. Except for SVG images, they will never be scaled up.');
				
				} elseif ($values['size/enlarge_canvas'] == 'resize_and_crop') {
					$box['tabs']['size']['fields']['enlarge_height']['note_below'] =
						adminPhrase('Images may be scaled up or down maintaining aspect ratio.');
				
				} else {
					unset($box['tabs']['size']['fields']['enlarge_height']['note_below']);
				}
			}
		}

		break;
}


?>