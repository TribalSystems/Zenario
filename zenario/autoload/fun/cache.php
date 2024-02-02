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

//Look in the database for an up to date cache
$sql = "
	SELECT store
	FROM ". DB_PREFIX. "plugin_instance_store
	WHERE last_updated > NOW() - INTERVAL ". (int) $expiryTimeInSeconds. " SECOND
	  AND method_name = '". \ze\escape::sql($methodName). "'
	  AND request = '". \ze\escape::sql($request). "'
	  AND instance_id = ". (int) $this->instanceId;
$result = \ze\sql::select($sql);

//If we found one, then return it and stop.
if ($row = \ze\sql::fetchAssoc($result)) {
	return $row['store'];
}

//Otherwise, get a new value and update the cache
$cache = $this->$methodName($request);

$sql = "
	REPLACE INTO ". DB_PREFIX. "plugin_instance_store SET
		store = '". \ze\escape::sql($cache). "',
		is_cache = 1,
		last_updated = NOW(),
		method_name = '". \ze\escape::sql($methodName). "',
		request = '". \ze\escape::sql($request). "',
		instance_id = ". (int) $this->instanceId;
\ze\sql::update($sql);

return $cache;
