<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

if (empty($instance)) {
	$instance = ze\plugin::details($instanceId);
}

if ($instance['class_name'] == 'zenario_slideshow_simple') {
	
	//Reorder byte images in a simple slideshow
	$sql = "
		SELECT banner.id AS bannerId, slide.id AS slideId
		FROM ". DB_PREFIX. "nested_plugins AS banner
		INNER JOIN ". DB_PREFIX. "nested_plugins AS slide
		   ON slide.instance_id = banner.instance_id
		  AND slide.is_slide = 1
		  AND slide.slide_num = banner.slide_num
		WHERE banner.is_slide = 0
		  AND banner.id IN (". ze\escape::in($ids, 'numeric'). ")
		  AND banner.instance_id = ". (int) $instanceId;
	
	foreach (ze\sql::select($sql) as $image) {
		if (isset($ordinals[$image['bannerId']])) {
			$newOrd = $ordinals[$image['bannerId']];
			
			ze\row::update('nested_plugins', ['slide_num' => $newOrd], $image['bannerId']);
			ze\row::update('nested_plugins', ['slide_num' => $newOrd], $image['slideId']);
		}
	}

} else {

	//Loop through each changed slide, and set its ordinal
	foreach ($ids as $id) {
		$key = ['id' => $id, 'instance_id' => $instanceId, 'is_slide' => 1];
		if ($slide = ze\row::get('nested_plugins', ['states'], $key)) {
			$newOrd = $ordinals[$id];
			$newParent = $parentIds[$id];
		
			//Tabs sholudn't be children of other tabs
			if (!$newParent) {
				ze\row::update('nested_plugins', ['slide_num' => $newOrd], $key);
			
				//For the conductor, correct the slide numbers on any paths as well
				foreach (ze\ray::explodeAndTrim($slide['states']) as $state) {
					$key = ['instance_id' => $instanceId, 'from_state' => $state];
					ze\row::update('nested_paths', ['slide_num' => $newOrd], $key);
				}
			}
		}
	}

	//Loop through each changed plugin
	foreach ($ids as $id) {
		$key = ['id' => $id, 'instance_id' => $instanceId, 'is_slide' => 0];
		if (ze\row::exists('nested_plugins', $key)) {
			$newOrd = $ordinals[$id];
			$newParent = $parentIds[$id];
		
			//Plugins must be children of tabs
			if ($newParent) {
				//Convert the parent id to a slide number
				$slideNum = ze\row::get('nested_plugins', 'slide_num', ['id' => $newParent, 'instance_id' => $instanceId, 'is_slide' => 1]);
				//Update to the new slide number
				ze\row::update('nested_plugins', ['slide_num' => ($slideNum ?: (0 ?: 0)), 'ord' => $newOrd], $key);
			}
		}
	}

	//Update the request variables for the slides in this nest, just in case any eggs have been moved to a different slide
	if ($instance['class_name'] == 'zenario_plugin_nest') {
		ze\pluginAdm::setSlideRequestVars($instanceId);
	}
}