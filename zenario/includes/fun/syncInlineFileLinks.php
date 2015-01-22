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


$foundChecksums = array();
$foundChecksumsWithTheWrongUsage = array();


//Parse the html, looking for links
$links = preg_split('@([^<]+)zenario/file\.php\?([^"]+)([^>]+)@s', $html, -1,  PREG_SPLIT_DELIM_CAPTURE);
$c = count($links) - 1;
$html = '';


//Find the details of each link
for ($i=0; $i < $c; $i += 4) {
	//Remember the html surrounding each link
	$html .= $links[$i];
	$html .= $links[$i + 1];
	
	//Does this image have a width/height set?
	$width = $height = $matches = false;
	if ((preg_match('@\Wwidth\W+(\d+)(\w*)\W@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
	 || (preg_match('@\Wwidth\W+(\d+)(\w*)\W@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
		$width = $matches[1];
	}
	if ((preg_match('@\Wheight\W+(\d+)(\w*)\W@', $links[$i + 1], $matches)) && (!$matches[2] || $matches[2] == 'px')
	 || (preg_match('@\Wheight\W+(\d+)(\w*)\W@', $links[$i + 3], $matches)) && (!$matches[2] || $matches[2] == 'px')) {
		$height = $matches[1];
	}
	
	//Attempt to loop through each request
	$request = preg_split('@(\w+)=([^\&]+)@s', $links[$i + 2], -1,  PREG_SPLIT_DELIM_CAPTURE);
	$d = count($request) - 1;
	
	$amp = '&amp;';
	$params = array();
	for ($j=0; $j < $d; $j += 3) {
		//Note down the seperator being used. Will usually be &amp;
		if (trim($request[$j])) {
			$amp = trim($request[$j]);
		}
		
		//Note down each request
		$params[$request[$j+1]] = $request[$j+2];
	}
	
	$doSomething = false;
	//If we can get the checksum from the filename, look up this file and process it
	if ($checksum = ifNull(arrayKey($params, 'c'), arrayKey($params, 'checksum'))) {
		
		//Get the preferred filename from the URL string, if it is set
		$filename = ifNull(trim(rawurldecode(arrayKey($params, 'filename'))), null, null);
		
		
		//Check to see if this is the checksum of an image, with the correct usage set
		if (!isset($foundChecksums[$checksum])) {
			$foundChecksums[$checksum] =
				getRow('files', array('id', 'width', 'height'), array('usage' => $usage, 'checksum' => $checksum));
		}
		$file = $foundChecksums[$checksum];
		
		//If it is, we've found it and we can continue without any changes
		if ($file && ifNull(trim(rawurldecode(arrayKey($params, 'usage'))), 'inline') == $usage) {
		
		//If not, check to see if it is the checksum of an image that exists somewhere on the filesystem,
		//and try to copy it over.
		} else {
			if (!isset($foundChecksumsWithTheWrongUsage[$checksum])) {
				if (($existingFile = getRow('files', array('id', 'width', 'height', 'usage', 'filename'), array('checksum' => $checksum)))
				 && ($newId = copyFileInDatabase($usage, $existingFile['id'], ifNull($filename, $existingFile['filename'])))) {
					
					if ($existingFile['usage'] == 'editor_temp_file') {
						deleteRow('files', $existingFile['id']);
					}
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
			$key = hash64($file['id']. '_'. $width. '_'. $height. '_'. $checksum, 10);
		}
		
		//If the image has a width/height listed against its attributes or inline styles,
		//try to put that width/height in the parameters to the file.php program.
		if (arrayKey($params, 'width') != $width) {
			$doSomething = true;
		}
		if (arrayKey($params, 'height') != $height) {
			$doSomething = true;
		}
		if (arrayKey($params, 'k') != $key) {
			$doSomething = true;
		}
		
		
		//Regenerate the URL if needed
		if ($doSomething) {
			$html .= 'zenario/file.php?c='. $checksum;
			
			if ($usage != 'inline') {
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
		}
		
		
		//Mark down each file that was either in the correct format, or that we corrected the format for.
		if ($file) {
			$files[$file['id']] = array('id' => $file['id']);
		}
	}
		
	//If we didn't do any conversion, leave the link exactly as it was
	if (!$doSomething) {
		$html .= 'zenario/file.php?'. $links[$i + 2];
	}
	
	//Remember the html surrounding each link
	$html .= $links[$i + 3];
}

$html .= $links[$c];