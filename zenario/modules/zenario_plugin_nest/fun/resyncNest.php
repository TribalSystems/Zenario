<?php
/*
 * Copyright (c) 2019, Tribal Limited
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

$key = ['instance_id' => $instanceId];

//Ensure there is at least one slide in the nest
$key['is_slide'] = 1;
if (!ze\row::exists('nested_plugins', $key)) {
	self::addSlide($instanceId);
}


//Look through a Plugin Nest, and ensure that all of the slide and ordinal numbers are valid by overwriting them
$slideNum = 0;
$ord = 0;

$sql = "
	SELECT id, slide_num, ord, is_slide
	FROM ". DB_PREFIX. "nested_plugins
	WHERE instance_id = ". (int) $instanceId. "
	ORDER BY slide_num, ord";

$result = ze\sql::select($sql);
while ($row = ze\sql::fetchAssoc($result)) {
	if ($row['is_slide']) {
		//Catch the case where a Plugin was moved before the first slide
		if ($slideNum) {
			//If this is a new slide, reset the ordinal
			$ord = 0;
		}
		++$slideNum;
		$thisOrd = 0;
	} else {
		$thisOrd = ++$ord;
	}
	
	ze\row::update(
		'nested_plugins',
		['slide_num' => ($slideNum ?: 1), 'ord' => $thisOrd],
		['instance_id' => $instanceId, 'id' => $row['id']]);
}

//Catch the case where a "group with above" (-1) plugin is dragged to the first in the row.
//Change it to a "full width" (0) plugin.
$key['is_slide'] = 0;
$key['ord'] = 1;
$key['cols'] = -1;
ze\row::update('nested_plugins', ['cols' => 0], $key);

//Update the request variables for the slides in this nest
ze\pluginAdm::setSlideRequestVars($instanceId);