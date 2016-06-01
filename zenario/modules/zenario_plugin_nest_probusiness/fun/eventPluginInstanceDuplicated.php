<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


$sql = "
	SELECT np_old.id AS old_tab_id, np_new.id AS new_tab_id
	FROM ". DB_NAME_PREFIX. "nested_plugins AS np_old
	INNER JOIN ". DB_NAME_PREFIX. "nested_plugins AS np_new
	   ON np_old.tab = np_new.tab
	WHERE np_old.instance_id = ". (int) $oldInstanceId. "
	  AND np_new.instance_id = ". (int) $newInstanceId. "
	  AND np_old.is_tab = 1
	  AND np_new.is_tab = 1";

$result = sqlQuery($sql);
while ($row = sqlFetchAssoc($result)) {
	if ($condition = getRow(ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs', true, array('tab_id' => $row['old_tab_id']))) {
		$condition['tab_id'] = $row['new_tab_id'];
		insertRow(ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs', $condition);
	}
}


?>