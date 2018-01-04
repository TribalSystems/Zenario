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








//Upload a new file
if (($_POST['upload'] ?? false) && $privCheck) {
	
	//Check to see if an identical file has already been uploaded
	$existingFilename = false;
	if ($_FILES['Filedata']['tmp_name']
	 && ($existingChecksum = md5_file($_FILES['Filedata']['tmp_name']))
	 && ($existingChecksum = base16To64($existingChecksum))) {
		$existingFilename = getRow('files', 'filename', array('checksum' => $existingChecksum, 'usage' => 'image'));
	}
	
	//Try to add the uploaded image to the database
	$fileId = Ze\File::addToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true);
	
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
} elseif (($_POST['add'] ?? false) && $key && $privCheck) {
	foreach (explodeAndTrim($ids, true) as $id) {
		$key['image_id'] = $id;
		setRow('inline_images', array(), $key);
	}
	return $ids;

//Mark images as public
} elseif (($_POST['mark_as_public'] ?? false) && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explodeAndTrim($ids, true) as $id) {
		updateRow('files', array('privacy' => 'public'), $id);
	}

//Mark images as private
} elseif (($_POST['mark_as_private'] ?? false) && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explodeAndTrim($ids, true) as $id) {
		updateRow('files', array('privacy' => 'private'), $id);
		Ze\File::deletePublicImage($id);
	}

//Delete an unused image
} elseif (($_POST['delete'] ?? false) && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explodeAndTrim($ids, true) as $id) {
		deleteUnusedImage($id);
	}

//Detach an image from a content item or newsletter
} elseif (($_POST['remove'] ?? false) && $key && checkPriv('_PRIV_MANAGE_MEDIA')) {
	foreach (explodeAndTrim($ids, true) as $id) {
		$key['image_id'] = $id;
		$key['in_use'] = 0;
		deleteRow('inline_images', $key);
	}



} elseif ($_POST['view_public_link'] ?? false) {
	
	$rememberWhatThisWas = cms_core::$mustUseFullPath;
	cms_core::$mustUseFullPath = false;
	
	$width = $height = $url = false;
	if (Ze\File::imageLink($width, $height, $url, $ids)) {
		
		echo
			'<!--Message_Type:Success-->',
			'<h3>', adminPhrase('The URL to your image is shown below:'), '</h3>',
			'<p>', adminPhrase('Full hyperlink:<br/>[[full]]<br/><br/>Internal hyperlink:<br/>[[internal]]<br/>',
				array(
					'full' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars(absCMSDirURL(). $url). '"/>',
					'internal' => '<input type="text" style="width: 488px;" value="'. htmlspecialchars($url). '"/>'
			)), '</p>',
			'<p>', adminPhrase('If you later make this image private, these links will stop working.'), '</p>';
		
	} else {
		echo
			'<!--Message_Type:Error-->',
			adminPhrase('Could not generate public link');
	}

	cms_core::$mustUseFullPath = $rememberWhatThisWas;
}

return false;