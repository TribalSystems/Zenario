<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

$recurseCount++;


deleteRow('category_item_link', array('category_id' => $id));
deleteRow('categories', array('id' => $id));
deleteRow('visitor_phrases', array('code' => '_CATEGORY_'. (int) $id, 'module_class_name' => 'zenario_common_features'));
removeItemFromPluginSettings('category', $id);

$sql = "
	UPDATE ". DB_NAME_PREFIX. "plugin_settings SET
		value = REPLACE(REPLACE(CONCAT(',,', value, ',,'), ',". (int) $id. ",', ','), ',,', '')
	WHERE dangling_cross_references = 'remove'
	  AND foreign_key_to = 'categories'";
sqlQuery($sql);

$sql = "
	UPDATE ". DB_NAME_PREFIX. "plugin_settings SET
		value = ''
	WHERE dangling_cross_references = 'remove'
	  AND foreign_key_to = 'categories'
	  AND value = ','";
sqlQuery($sql);


sendSignal("eventCategoryDeleted",array("categoryId" => $id));

if ($recurseCount<=10) {
	$sql = "SELECT id
			FROM " . DB_NAME_PREFIX . "categories
			WHERE parent_id = " . $id;
			
	$result = sqlQuery($sql);
	
	if (sqlNumRows($result)>0) {
		while ($row = sqlFetchArray($result)) {
			$this->deleteCategory($row['id'], $recurseCount);
		}
	}
}

return false;