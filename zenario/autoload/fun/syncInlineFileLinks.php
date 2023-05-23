<?php
/*
 * Copyright (c) 2023, Tribal Limited
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
		
		//If we can get the checksum from the url, look up this file and process it.
		//Also, only handle simple image resizes. Don't try to do anything with
		//resize and crop, or crop and zoom images.
		$needsChanging = false;
		$changed = false;
		$isSourceOrSimpleResize = empty($explode[0]) || is_numeric($explode[0]);
		if ($checksum && $isSourceOrSimpleResize) {
			
			$widthInURL = (int) ($explode[0] ?? false);
			$heightInURL = (int) ($explode[1] ?? false);
			
			//Watch out for images that have been resized, either by resizing the image by dragging the handles
			//on the corners in TinyME, or by manually specifying a width and height in the HTML source
			$widthOnPage = $heightOnPage = $matches = false;
			if ((preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
			 || (preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 5], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
				$widthOnPage = 1 * $matches[1];
			}
			if ((preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
			 || (preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 5], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
				$heightOnPage = 1 * $matches[1];
			}
	
		
			//Check to see if this is the checksum of an image, with the correct usage set
			if (!isset($foundChecksums[$checksum])) {
				$foundChecksums[$checksum] =
					\ze\row::get('files',
						['id', 'usage', 'filename', 'mime_type', 'privacy', 'width', 'height', 'checksum', 'short_checksum'],
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
					 && ($existingFile = \ze\row::get('files', ['id', 'usage', 'privacy', 'width', 'height', 'usage', 'filename', 'mime_type'], [$checksumCol => $checksum]))
					 && ($newId = ze\file::copyInDatabase($usage, $existingFile['id'], ($filename ?: $existingFile['filename'])))) {
					
						$existingFile['id'] = $newId;
					
						$foundChecksumsWithTheWrongUsage[$checksum] = $existingFile;
					} else {
						$foundChecksumsWithTheWrongUsage[$checksum] = false;
					}
				}

				if ($file = $foundChecksumsWithTheWrongUsage[$checksum]) {
					//Update the URL to the correct location
					$needsChanging = true;
				}
			}
			
			
			$isSVG = $file && $file['mime_type'] == 'image/svg+xml';
			$knownDimensions = $file && $file['width'] && $file['height'];
			
			
			//Watch out for the case where the width and height of the image is missing,
			//or only one was specified.
			//Try to correct these if we know what the width of height on the image is in the database
			if ($knownDimensions) {
				if ($isSVG) {
					$widthInURL = $file['width'];
					$heightInURL = $file['height'];
				} else {
					if ($widthInURL) {
						if ($heightInURL) {
						} else {
							$heightInURL = (int) ((float) $file['height'] / (float) $file['width'] * (float) $widthInURL);
							$needsChanging = true;
						}
					} else {
						if ($heightInURL) {
							$widthInURL = (int) ((float) $file['width'] / (float) $file['height'] * (float) $heightInURL);
							$needsChanging = true;
						} else {
							$widthInURL = $file['width'];
							$heightInURL = $file['height'];
						}
					}
					if ($widthOnPage) {
						if ($heightOnPage) {
						} else {
							$heightOnPage = (int) ((float) $file['height'] / (float) $file['width'] * (float) $widthOnPage);
							$needsChanging = true;
						}
					} else {
						if ($heightOnPage) {
							$widthOnPage = (int) ((float) $file['width'] / (float) $file['height'] * (float) $heightOnPage);
							$needsChanging = true;
						} else {
							$widthOnPage = $file['width'];
							$heightOnPage = $file['height'];
							$needsChanging = true;
						}
					}
				}
			}
			
			
			//For raster images, compare the size we're trying to display vs the size
			//of the image available. If there's enough detail, double the size and
			//request a retina version of the image.
			$widthForRetina = $widthOnPage;
			$heightForRetina = $heightOnPage;
			if ($knownDimensions) {
				if ($widthOnPage && (2 * $widthOnPage <= $file['width'])) {
					$widthForRetina *= 2;
				}
				if ($widthOnPage && (2 * $heightOnPage <= $file['height'])) {
					$heightForRetina *= 2;
				}
			}
			
			
			//If the width and height used in the URL didn't look like they matched the
			//dimensions used on the page, we'll need to change them
			if ($widthInURL != $widthForRetina) {
				$needsChanging = true;
			}
			if ($heightInURL != $heightForRetina) {
				$needsChanging = true;
			}
			
			
			//If we see a private image (or an "auto" image that's not
			//on a public page) then attempt to switch back to using zenario/file.php URL
			if ($file
			 && ($file['privacy'] == 'private'
			  || ($file['privacy'] == 'auto' && !$publishingAPublicPage))) {
				
				$html .= htmlspecialchars(
					'zenario/file.php?c='. $checksum.
					($widthForRetina? '&width='. $widthForRetina : '').
					($heightForRetina? '&height='. $heightForRetina : '').
					'&filename='. rawurlencode($filename));
				$htmlChanged = true;
				$changed = true;
			
			//Otherwise attempt to regenerate the public link if needed
			} elseif ($needsChanging) {
				$rememberWhatThisWas = \ze::$mustUseFullPath;
				\ze::$mustUseFullPath = false;
				
				$url = '';
				$dummyWidth = $dummyHeight = 0;
				if (ze\file::imageLink(
					$dummyWidth, $dummyHeight, $url, $file['id'], $widthOnPage, $heightOnPage,
					$mode = 'adjust', $offset = 0, $retina = true,
					$fullPath = false, $privacy = 'public'

				)) {
					if (\ze\ring::chopPrefix('public/images/', $url)) {
						$html .= htmlspecialchars($url);
						$htmlChanged = true;			
						$changed = true;
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
		if (!$changed) {
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
		$widthOnPage = $heightOnPage = $matches = false;
		if ((preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]width[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$widthOnPage = $matches[1];
		}
		if ((preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
		 || (preg_match('@[^\w\%-]height[^\w\%-]+(\d+)(\w*)[^\w\%-]@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
			$heightOnPage = $matches[1];
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
		$widthInURL = (int) ($params['width'] ?? false);
		$heightInURL = (int) ($params['height'] ?? false);
	
		//If we can get the checksum from the url, look up this file and process it
		$needsChanging = false;
		$changed = false;
		if ($checksum = \ze::ifNull($params['c'] ?? false, $params['checksum'] ?? false)) {
		
			//Catch old checksums in base 16. Convert these to base 64 so the links will be shorter.
			if (strlen($checksum) == 32
			 && preg_match('/[^ABCDEFabcdef0-9]/', $checksum) === 0) {
				$checksum = \ze::base16To64($checksum);
				$needsChanging = true;
			}
			
			//Watch out for full checksums appearing in the URL.
			//(The full checksums weigh in at about 21-22 characters long.)
			//Replace them with short checksums where we see this
			if (strlen($checksum) < 20) {
				$checksumCol = 'short_checksum';
			} else {
				$checksumCol = 'checksum';
				$needsChanging = true;
			}

		
			//Get the preferred filename from the URL string, if it is set
			$filename = \ze::ifNull(trim(rawurldecode($params['filename'] ?? false)), null, null);
		
		
			//Check to see if this is the checksum of an image, with the correct usage set
			if (!isset($foundChecksums[$checksum])) {
				$foundChecksums[$checksum] =
					\ze\row::get('files',
						['id', 'usage', 'filename', 'mime_type', 'privacy', 'width', 'height', 'checksum', 'short_checksum'],
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
					$needsChanging = true;
				}
			}
			
			

			$isSVG = $file && $file['mime_type'] == 'image/svg+xml';
			$knownDimensions = $file && $file['width'] && $file['height'];
			
			
			//Watch out for the case where the width and height of the image is missing,
			//or only one was specified.
			//Try to correct these if we know what the width of height on the image is in the database
			if ($knownDimensions) {
				if ($isSVG) {
					$widthInURL = $file['width'];
					$heightInURL = $file['height'];
				} else {
					if ($widthInURL) {
						if ($heightInURL) {
						} else {
							$heightInURL = (int) ((float) $file['height'] / (float) $file['width'] * (float) $widthInURL);
							$needsChanging = true;
						}
					} else {
						if ($heightInURL) {
							$widthInURL = (int) ((float) $file['width'] / (float) $file['height'] * (float) $heightInURL);
							$needsChanging = true;
						} else {
							$widthInURL = $file['width'];
							$heightInURL = $file['height'];
						}
					}
					if ($widthOnPage) {
						if ($heightOnPage) {
						} else {
							$heightOnPage = (int) ((float) $file['height'] / (float) $file['width'] * (float) $widthOnPage);
							$needsChanging = true;
						}
					} else {
						if ($heightOnPage) {
							$widthOnPage = (int) ((float) $file['width'] / (float) $file['height'] * (float) $heightOnPage);
							$needsChanging = true;
						} else {
							$widthOnPage = $file['width'];
							$heightOnPage = $file['height'];
							$needsChanging = true;
						}
					}
				}
			}
			
			
			//For raster images, compare the size we're trying to display vs the size
			//of the image available. If there's enough detail, double the size and
			//request a retina version of the image.
			$widthForRetina = $widthOnPage;
			$heightForRetina = $heightOnPage;
			if ($knownDimensions) {
				if ($widthOnPage && (2 * $widthOnPage <= $file['width'])) {
					$widthForRetina *= 2;
				}
				if ($widthOnPage && (2 * $heightOnPage <= $file['height'])) {
					$heightForRetina *= 2;
				}
			}
			
			
			//If the width and height used in the URL didn't look like they matched the
			//dimensions used on the page, we'll need to change them
			if ($widthInURL != $widthForRetina) {
				$needsChanging = true;
			}
			if ($heightInURL != $heightForRetina) {
				$needsChanging = true;
			}
			
		
			//Add a simple checksum to make it harder for visitors to randomly change the widths and heights as they wish just by changing the URL
			$key = false;
			if (($widthForRetina && (!$knownDimensions || $widthForRetina != $file['width']))
			 && ($heightForRetina && (!$knownDimensions || $heightForRetina != $file['height']))) {
				$key = \ze::hash64($file['id']. '_'. $widthForRetina. '_'. $heightForRetina. '_'. $checksum, 10);
			}
			
			if (($params['k'] ?? false) != $key) {
				$needsChanging = true;
			}
			
			
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
					$dummyWidth, $dummyHeight, $url, $file['id'], $widthOnPage, $heightOnPage,
					$mode = 'adjust', $offset = 0, $retina = true,
					$fullPath = false, $privacy = 'public',
				)) {
					if (\ze\ring::chopPrefix('public/images/', $url)) {
						$html .= htmlspecialchars($url);
						$htmlChanged = true;			
						$changed = true;
					}
				}
				\ze::$mustUseFullPath = $rememberWhatThisWas;
			}
			
			
			//Regenerate/tidy up the URL if one of the other flags above was set
			if ($needsChanging && !$changed) {
				$html .= 'zenario/file.php?c=';
				
				if (!empty($file['short_checksum'])) {
					$html .= $file['short_checksum'];
				} else {
					$html .= $checksum;
				}
			
				if ($usage != 'image') {
					$html .= $amp. 'usage='. rawurlencode($usage);
				}
			
				if ($widthForRetina !== false) {
					$html .= $amp. 'width='. $widthForRetina;
				}
			
				if ($heightForRetina !== false) {
					$html .= $amp. 'height='. $heightForRetina;
				}
			
				if ($key !== false) {
					$html .= $amp. 'k='. $key;
				}
			
				if (!empty($params['filename'])) {
					$html .= $amp. 'filename='. $params['filename'];
				}
			
				$htmlChanged = true;
				$changed = true;
			}
		
		
			//Mark down each file that was either in the correct format, or that we corrected the format for.
			if ($file) {
				$files[$file['id']] = $file;
			}
		}
		
		//If we didn't do any conversion, leave the link exactly as it was
		if (!$changed) {
			$html .= 'zenario/file.php?'. $links[$i + 2];
		}
	
		//Remember the html surrounding each link
		$html .= $links[$i + 3];
	}

	$html .= $links[$c];
}