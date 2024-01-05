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


//Remove a Plugin
if (($instance = ze\plugin::details($instanceId))
 && ($egg = ze\pluginAdm::getNestDetails($eggId, $instanceId))
 && (!$egg['is_slide'])) {
	$sql = "
		DELETE FROM ". DB_PREFIX. "plugin_settings
		WHERE instance_id = ". (int) $instanceId. "
		  AND egg_id != 0
		  AND egg_id IN (
			SELECT id
			FROM ". DB_PREFIX. "nested_plugins
			WHERE instance_id = ". (int) $instanceId. "
			  AND id = ". (int) $eggId. "
		  )";
	ze\sql::cacheFriendlyUpdate($sql);  //No need to check the cache as the other statements should clear it correctly
	
	ze\pluginAdm::manageCSSFile('delete', $instanceId, $eggId);
	
	ze\row::delete('nested_plugins', ['instance_id' => $instanceId, 'id' => $eggId]);
	
	
	if ($resync) {
		self::resyncNest($instanceId, $instance);
	
		ze\contentAdm::resyncLibraryPluginFiles($instanceId, $instance);
	
		ze\pluginAdm::setSlideRequestVars($instanceId);
	}
}