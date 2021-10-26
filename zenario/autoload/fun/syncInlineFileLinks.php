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


$foundChecksums = [];
$foundChecksumsWithTheWrongUsage = [];




//Parse the html, looking for links to images in the public directory.
//URLs to these might look like:
	//public/images/YZVPl/boat-image.jpg
//(for full images) or:
	//public/images/YZVPl/resize_100_185_0/boat-image.jpg
//(for resizes)
if ($usage == 'image'
 && strpos($html, 'public/images/') !== false) {
	
	$links = preg_split('@([^<]+)public/images/([\\w-]+)/(\w+/|)([^"]+)([^>]+)@s', $html, -1,  PREG_SPLIT_DELIM_CAPTURE);
	$c = count($links) - 1;
	$html = '';
	
	
	//Find the details of each link
	for ($i=0; $i < $c; $i += 6) {
		//Remember the html surrounding each link
		$html .= $links[$i];
		$html .= $links[$i + 1];
		
		$checksum = $links[$i + 2];
		$explode = explode('_', $links[$i + 3]);
		$filename = $links[$i + 4];
		$params = [
			'mode' => $explode[0],
			'width' => $width = (int) ($explode[1] ?? false),
			'height' => $height = (int) ($explode[2] ?? false),
			'offset' => $offset = (int) ($explode[3] ?? false)];
		
		$mode = 'unlimited';
		if (\ze::in($params['mode'], 'unlimited', 'stretch', 'adjust', 'resize_and_crop', 'fixed_width', 'fixed_height', 'resize')) {
			$mode = $explode[0];
			
			//Catch a mode name that got renamed
			if ($mode == 'stretch') {
				$mode = 'adjust';
			}
		}
	
		//Watch out for images that have been resized, either by resizing the image by dragging the handles
		//on the corners in TinyME, or by manually specifying a width and height in the HTML source
		$width = $height = $matches = false;
		if ((preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 5], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$width = $matches[1];
		}
		if ((preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 5], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$height = $matches[1];
		}
	
		//If we can get the checksum from the url, look up this file and process it
		$doSomething = false;
		$doneSomething = false;
		if ($checksum) {
		
			//Check to see if this is the checksum of an image, with the correct usage set
			if (!isset($foundChecksums[$checksum])) {
				$foundChecksums[$checksum] =
					\ze\row::get('files',
						['id', 'usage', 'filename', 'privacy', 'width', 'height', 'checksum', 'short_checksum'],
						['usage' => $usage, 'short_checksum' => $checksum]);
			}
			$file = $foundChecksums[$checksum];
			
			//If it is, we've found it and we can continue without any changes
			if ($file) {
		
			//If not, check to see if it is the checksum of an image that exists somewhere on the filesystem,
			//and try to copy it over.
			} else {
				if (!isset($foundChecksumsWithTheWrongUsage[$checksum])) {
					if ($checksum
					 && $checksumCol
					 && ($existingFile = \ze\row::get('files', ['id', 'usage', 'privacy', 'width', 'height', 'usage', 'filename'], [$checksumCol => $checksum]))
					 && ($newId = ze\file::copyInDatabase($usage, $existingFile['id'], ($filename ?: $existingFile['filename'])))) {
					
						$existingFile['id'] = $newId;
					
						$foundChecksumsWithTheWrongUsage[$checksum] = $existingFile;
					} else {
						$foundChecksumsWithTheWrongUsage[$checksum] = false;
					}
				}

				if ($file = $foundChecksumsWithTheWrongUsage[$checksum]) {
					//Update the URL to the correct location
					$doSomething = true;
				}
			}
		
		
			//Don't worry about adding the width/height to the URL if they're not a resize
			if ($file && $width == $file['width']) {
				$width = false;
			}
			if ($file && $height == $file['height']) {
				$height = false;
			}
		
			if (!$width && !$height) {
				$mode = 'unlimited';
			}
		
			//If the image has a width/height listed against its attributes or inline styles,
			//try to put that width/height in the parameters to the file.php program.
			if ($params['mode'] != $mode) {
				$doSomething = true;
			}
			if ($params['width'] != $width) {
				$doSomething = true;
			}
			if ($params['height'] != $height) {
				$doSomething = true;
			}
			if ($params['offset'] != $offset) {
				$doSomething = true;
			}
			
			//If we see a private image (or an "auto" image that's not
			//on a public page) then attempt to switch back to using zenario/file.php URL
			if ($file
			 && ($file['privacy'] == 'private'
			  || ($file['privacy'] == 'auto' && !$publishingAPublicPage))) {
				
				$html .= htmlspecialchars(
					'zenario/file.php?c='. $checksum.
					'&width='. $width. '&height='. $height.
					'&filename='. rawurlencode($filename));
				$htmlChanged = true;
				$doneSomething = true;
			
			//Otherwise attempt to regenerate the public link if needed
			} elseif ($doSomething) {
				$rememberWhatThisWas = \ze::$mustUseFullPath;
				\ze::$mustUseFullPath = false;
				
				$url = '';
				$dummyWidth = $dummyHeight = 0;
				if (ze\file::imageLink(
					$dummyWidth, $dummyHeight, $url, $file['id'], $width, $height,
					$mode, $offset
				)) {
					if (\ze\ring::chopPrefix('public/images/', $url)) {
						$html .= htmlspecialchars($url);
						$htmlChanged = true;			
						$doneSomething = true;
					}
				}
				
				\ze::$mustUseFullPath = $rememberWhatThisWas;
			}
		
		
			//Mark down each file that was either in the correct format, or that we corrected the format for.
			if ($file) {
				$files[$file['id']] = $file;
			}
		}
		
		//If we didn't do any conversion, leave the link exactly as it was
		if (!$doneSomething) {
			$html .= 'public/images/'. $links[$i + 2]. '/'. $links[$i + 3]. $links[$i + 4];
		}
	
		//Remember the html surrounding each link
		$html .= $links[$i + 5];
	}

	$html .= $links[$c];
}






//Parse the html, looking for links to file.php
if (strpos($html, 'zenario/file.php') !== false) {
	
	$links = preg_split('@([^<]+)zenario/file\\.php\\?([^"]+)([^>]+)@s', $html, -1,  PREG_SPLIT_DELIM_CAPTURE);
	$c = count($links) - 1;
	$html = '';


	//Find the details of each link
	for ($i=0; $i < $c; $i += 4) {
		//Remember the html surrounding each link
		$html .= $links[$i];
		$html .= $links[$i + 1];
	
		//Watch out for images that have been resized, either by resizing the image by dragging the handles
		//on the corners in TinyME, or by manually specifying a width and height in the HTML source
		$width = $height = $matches = false;
		if ((preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$width = $matches[1];
		}
		if ((preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$height = $matches[1];
		}
		
		//Check the parameters of the image's URL, and attempt to loop through each request
		$request = preg_split('@(\w+)=([^\&]+)@s', $links[$i + 2], -1,  PREG_SPLIT_DELIM_CAPTURE);
		$d = count($request) - 1;
	
		$amp = '&amp;';
		$params = [];
		for ($j=0; $j < $d; $j += 3) {
			//Note down the seperator being used. Will usually be &amp;
			if (trim($request[$j])) {
				$amp = trim($request[$j]);
			}
		
			//Note down each request
			$params[$request[$j+1]] = $request[$j+2];
		}
	
		//If we can get the checksum from the url, look up this file and process it
		$doSomething = false;
		$doneSomething = false;
		if ($checksum = \ze::ifNull($params['c'] ?? false, $params['checksum'] ?? false)) {
		
			//Catch old checksums in base 16. Convert these to base 64 so the links will be shorter.
			if (strlen($checksum) == 32
			 && preg_match('/[^ABCDEFabcdef0-9]/', $checksum) === 0) {
				$checksum = \ze::base16To64($checksum);
				$doSomething = true;
			}
			
			//Watch out for full checksums appearing in the URL.
			//(The full checksums weigh in at about 21-22 characters long.)
			//Replace them with short checksums where we see this
			if (strlen($checksum) < 20) {
				$checksumCol = 'short_checksum';
			} else {
				$checksumCol = 'checksum';
				$doSomething = true;
			}

		
			//Get the preferred filename from the URL string, if it is set
			$filename = \ze::ifNull(trim(rawurldecode($params['filename'] ?? false)), null, null);
		
		
			//Check to see if this is the checksum of an image, with the correct usage set
			if (!isset($foundChecksums[$checksum])) {
				$foundChecksums[$checksum] =
					\ze\row::get('files',
						['id', 'usage', 'filename', 'privacy', 'width', 'height', 'checksum', 'short_checksum'],
						['usage' => $usage, $checksumCol => $checksum]);
			}
			$file = $foundChecksums[$checksum];
			
			//If it is, we've found it and we can continue without any changes
			if ($file && \ze::ifNull(trim(rawurldecode($params['usage'] ?? false)), 'image') == $usage) {
		
			//If not, check to see if it is the checksum of an image that exists somewhere on the filesystem,
			//and try to copy it over.
			} else {
				if (!isset($foundChecksumsWithTheWrongUsage[$checksum])) {
					if (($existingFile = \ze\row::get('files', ['id', 'usage', 'privacy', 'width', 'height', 'usage', 'filename'], [$checksumCol => $checksum]))
					 && ($newId = ze\file::copyInDatabase($usage, $existingFile['id'], ($filename ?: $existingFile['filename'])))) {
					
						$existingFile['id'] = $newId;
					
						$foundChecksumsWithTheWrongUsage[$checksum] = $existingFile;
					} else {
						$foundChecksumsWithTheWrongUsage[$checksum] = false;
					}
				}

				if ($file = $foundChecksumsWithTheWrongUsage[$checksum]) {
					//Update the URL to the correct location
					$doSomething = true;
				}
			}
		
		
			//Don't worry about adding the width/height to the URL if they're not a resize
			if ($file && $width == $file['width']) {
				$width = false;
			}
			if ($file && $height == $file['height']) {
				$height = false;
			}
		
			//Add a simple checksum to make it harder for visitors to randomly change the widths and heights as they wish just by changing the URL
			$key = false;
			if ($width || $height) {
				$key = \ze::hash64($file['id']. '_'. $width. '_'. $height. '_'. $checksum, 10);
			}
		
			//If the image has a width/height listed against its attributes or inline styles,
			//try to put that width/height in the parameters to the file.php program.
			if (($params['width'] ?? false) != $width) {
				$doSomething = true;
			}
			if (($params['height'] ?? false) != $height) {
				$doSomething = true;
			}
			if (($params['k'] ?? false) != $key) {
				$doSomething = true;
			}
			
			//Currently commented out:
			//This bit of the code will would convert a file.php link to a link to the public/images/
			//directory. As the CMS currently doesn't manage these too well, for now this is
			//not enabled
			/*
			//For public images (or images that are set to "auto" and about to be made public because
			//we're publishing a public page that they are on), try to switch to the image's URL
			//in the public directory.
			if ($file
			 && $usage == 'image'
			 && ($file['privacy'] == 'public'
			  || ($file['privacy'] == 'auto' && $publishingAPublicPage))) {
				
				$dummyWidth = $dummyHeight = 0;
				$url = '';
				
				$rememberWhatThisWas = \ze::$mustUseFullPath;
				\ze::$mustUseFullPath = false;
				if (ze\file::imageLink(
					$dummyWidth, $dummyHeight, $url, $file['id'], $width, $height,
					$mode = 'adjust', $offset = 0,
					$retina = false, $privacy = 'public',
					$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
				)) {
					if (\ze\ring::chopPrefix('public/images/', $url)) {
						$html .= htmlspecialchars($url);
						$htmlChanged = true;			
						$doneSomething = true;
					}
				}
				\ze::$mustUseFullPath = $rememberWhatThisWas;
			}
			*/
			
			
			//Regenerate/tidy up the URL if one of the other flags above was set
			if ($doSomething && !$doneSomething) {
				$html .= 'zenario/file.php?c=';
				
				if (!empty($file['short_checksum'])) {
					$html .= $file['short_checksum'];
				} else {
					$html .= $checksum;
				}
			
				if ($usage != 'image') {
					$html .= $amp. 'usage='. rawurlencode($usage);
				}
			
				if ($width !== false) {
					$html .= $amp. 'width='. $width;
				}
			
				if ($height !== false) {
					$html .= $amp. 'height='. $height;
				}
			
				if ($key !== false) {
					$html .= $amp. 'k='. $key;
				}
			
				if (!empty($params['filename'])) {
					$html .= $amp. 'filename='. $params['filename'];
				}
			
				$htmlChanged = true;
				$doneSomething = true;
			}
		
		
			//Mark down each file that was either in the correct format, or that we corrected the format for.
			if ($file) {
				$files[$file['id']] = $file;
			}
		}
		
		//If we didn't do any conversion, leave the link exactly as it was
		if (!$doneSomething) {
			$html .= 'zenario/file.php?'. $links[$i + 2];
		}
	
		//Remember the html surrounding each link
		$html .= $links[$i + 3];
	}

	$html .= $links[$c];
}