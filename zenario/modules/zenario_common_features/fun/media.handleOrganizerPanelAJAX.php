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


//Upload a new file
if (post('upload') && $privCheck) {
	//Try to add the uploaded image to the database
	$fileId = addFileToDatabase($usage, $_FILES['Filedata']['tmp_name'], $_FILES['Filedata']['name'], true);
	
	if ($fileId) {
		if ($key) {
			$key['file_id'] = $fileId;
			setRow('inline_file_link', array(), $key);
		} else {
			updateRow('files', array('shared' => 1), $fileId);
		}
		
		//Look for images with the same name, that are not in use, and remove them
		deleteUnusedFilesByName($usage, $_FILES['Filedata']['name'], $fileId);
		
		echo 1;
		return $fileId;
	
	} else {
		echo adminPhrase('Please upload a valid GIF, JPG or PNG image.');
		return false;
	}

//Add an image from the shared pool
} elseif (post('add') && $key && $privCheck) {
	foreach (explode(',', $ids) as $id) {
		$key['file_id'] = $id;
		setRow('inline_file_link', array(), $key);
	}
	return $ids;

//Delete an unused image
} elseif (post('delete') && !$key && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		deleteUnusedInlineFile($id, true);
	}

//Remove an image, and delete it if unused
} elseif (post('remove') && $key && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		$key['file_id'] = $id;
		$key['in_use'] = 0;
		deleteRow('inline_file_link', $key);
		deleteUnusedInlineFile($id);
	}

//Share an image
} elseif (post('share') && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		updateRow('files', array('shared' => 1), $id);
	}

//Stop sharing an image
} elseif (post('unshare') && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		updateRow('files', array('shared' => 0), $id);
		deleteUnusedInlineFile($id);
	}
}

return false;