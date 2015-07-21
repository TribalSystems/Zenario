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








//Upload a new file
if (post('upload') && $privCheck) {
	
	//Check to see if an identical file has already been uploaded
	$existingFilename = false;
	if ($_FILES['Filedata']['tmp_name']
	 && ($existingChecksum = md5_file($_FILES['Filedata']['tmp_name']))
	 && ($existingChecksum = base16To64($existingChecksum))) {
		$existingFilename = getRow('files', 'filename', array('checksum' => $existingChecksum, 'usage' => 'image'));
	}
	
	//Try to add the uploaded image to the database
	$fileId = addFileToDatabase('image', $_FILES['Filedata']['tmp_name'], $_FILES['Filedata']['name'], true);
	
	if ($fileId) {
		
		//If this was a content item or newsletter, attach the uploaded image to the content item/newsletter
		if ($key) {
			$key['image_id'] = $fileId;
			setRow('inline_images', array(), $key);
		}
		
		if ($existingFilename && $existingFilename != $_FILES['Filedata']['name']) {
			echo '<!--Message_Type:Warning-->',
				adminPhrase('This file already existed on the system, but with a different name. "[[old_name]]" has now been renamed to "[[new_name]]".',
					array('old_name' => $existingFilename, 'new_name' => $_FILES['Filedata']['name']));
		} else {
			echo 1;
		}
		
		
		return $fileId;
	
	} else {
		echo adminPhrase('Please upload a valid GIF, JPG or PNG image.');
		return false;
	}

//Add an image from the library
} elseif (post('add') && $key && $privCheck) {
	foreach (explode(',', $ids) as $id) {
		$key['image_id'] = $id;
		setRow('inline_images', array(), $key);
	}
	return $ids;

//Mark images as public
} elseif (post('mark_as_public') && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		updateRow('files', array('privacy' => 'public'), $id);
	}

//Mark images as private
} elseif (post('mark_as_private') && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		updateRow('files', array('privacy' => 'private'), $id);
		deletePublicImage($id);
	}

//Delete an unused image
} elseif (post('delete') && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		deleteUnusedImage($id);
	}

//Detach an image from a content item or newsletter
} elseif (post('remove') && $key && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explode(',', $ids) as $id) {
		$key['image_id'] = $id;
		$key['in_use'] = 0;
		deleteRow('inline_images', $key);
	}



} elseif (post('view_public_link')) {
	$width = $height = $url = false;
	if (imageLink($width, $height, $url, $ids)) {
		//if ($url
		
		echo
			'<!--Message_Type:Success-->',
			adminPhrase('<h3>The URL to your image is shown below:</h3><p>Full hyperlink:<br>[[full]]<br>Internal hyperlink:<br>[[internal]]</p>',
				array(
					'full' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars(absCMSDirURL(). $url). '"/>',
					'internal' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars($url). '"/>'
				));
		
	} else {
		echo
			'<!--Message_Type:Error-->',
			adminPhrase('Could not generate public link');
	}
	
}

return false;