<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

//Convert the format of any inline image URLs in Email Templates to use the new email pool
//Also resync all of the images used in them.
if (ze\dbAdm::needRevision(120)) {
	//Get the body text from the newsletters
	$sql = "
		SELECT id, code, body
		FROM ". DB_NAME_PREFIX. "email_templates
		WHERE body LIKE '%file.php%'";
	$result = ze\sql::select($sql);
	
	while ($row = ze\sql::fetchAssoc($result)) {
		$files = array();
		$htmlChanged = false;
		ze\contentAdm::syncInlineFileLinks($files, $row['body'], $htmlChanged);
		
		if ($htmlChanged) {
			ze\row::update('email_templates', array('body' => $row['body']), array('id' => $row['id']));
		}
		
		ze\contentAdm::syncInlineFiles(
			$files,
			array('foreign_key_to' => 'email_template', 'foreign_key_id' => $row['id'], 'foreign_key_char' => $row['code']),
			$keepOldImagesThatAreNotInUse = false);
	}

	ze\dbAdm::revision(120);
}