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

//Remove a slide, and merge modules in the slide with the next slide
if (($slide = ze\pluginAdm::getNestDetails($slideId, $instanceId)) && ($slide['is_slide'])) {
	
	//Delete any plugins on this slide
	foreach (ze\row::getValues('nested_plugins', 'id', ['instance_id' => $instanceId, 'slide_num' => $slide['slide_num']]) as $eggId) {
		self::removePlugin($eggId, $instanceId, false);
	}
	
	//Delete the slide
	ze\row::delete('nested_plugins', ['instance_id' => $instanceId, 'id' => $slideId]);
	
	//Delete any nested paths
	foreach (ze\ray::explodeAndTrim($slide['states']) as $state) {
		static::deletePath($instanceId, $state);
	}
	
	if ($resync) {
		self::resyncNest($instanceId);

		ze\contentAdm::resyncLibraryPluginFiles($instanceId);

		ze\pluginAdm::setSlideRequestVars($instanceId);
	}
}

